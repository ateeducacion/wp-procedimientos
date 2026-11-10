<?php
/**
 * The read-only consultation of the applications of a procedure, scoped by
 * the zones of whoever looks.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\Access\ProcedureAccess;
use Prc\Domain\ApplicationFilter;
use Prc\Domain\ProcedureQuestions;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ApplicationPostType;
use Prc\PostType\ProcedurePostType;
use Prc\PublicFront\View\ObserveView;

/**
 * Shortcode [prc_observe]: la consulta de solo lectura (ADR-0043).
 *
 * Hoy, para que quien acompaña a los centros de una zona pueda seguirlos en
 * un procedimiento, alguien monta a mano una hoja de consulta por
 * procedimiento. Aquí es una pantalla del aplicativo:
 *
 * - **Sin procedimiento**, los procedimientos publicados que admiten consulta
 *   y en los que la persona tiene algo que ver, con cuántas solicitudes de
 *   sus zonas hay en cada uno.
 * - **Con procedimiento**, la tabla: la misma consulta, el mismo filtro y las
 *   mismas páginas que el panel del taller ({@see ApplicationQuery}), acotada
 *   a los centros de sus zonas, con las columnas que el procedimiento decide
 *   ({@see ObserveSettings}) y sin el DNI ni la nota, nunca.
 *
 * Quién ve qué lo decide {@see ProcedureAccess::can_observe()}. Aquí no hay
 * ningún formulario que escriba: el único envío es la exportación, que
 * devuelve un fichero y no cambia nada. Un procedimiento que no admite
 * consulta responde exactamente igual que uno que no existe.
 */
final class Observe {

	public const SHORTCODE = 'prc_observe';

	/** Query arg with the procedure. */
	public const ARG_PROCEDURE = Shell::ARG_PROCEDURE;

	/** POST field with the operation, and the only operation there is. */
	public const FIELD_DO   = 'prc_observe_do';
	public const OP_EXPORT  = 'export';
	public const FIELD_ID   = 'prc_observe_procedure';
	public const NONCE_NAME = 'prc_observe_nonce';

	/**
	 * Lo que se dice de un procedimiento que no existe, que no está publicado,
	 * que no admite consulta o que esta persona no puede consultar: lo mismo,
	 * para no confirmar que existe.
	 */
	public const NOT_FOUND = 'Ese procedimiento no existe o no tiene consulta de solicitudes. Elija uno de la lista.';

	/**
	 * Why the last export did not go through.
	 *
	 * @var string
	 */
	private static $rejected = '';

	/**
	 * Register the shortcode and the export handler.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'render' ) );
		add_action( 'init', array( self::class, 'handle' ), 20 );
	}

	/**
	 * URL of the consultation, of one procedure or of the list.
	 *
	 * @param int                  $procedure_id Procedure post ID; 0 for the list.
	 * @param array<string, mixed> $args         More query args.
	 * @return string Empty when the page does not exist.
	 */
	public static function url( int $procedure_id = 0, array $args = array() ): string {
		if ( $procedure_id > 0 ) {
			$args = array( self::ARG_PROCEDURE => $procedure_id ) + $args;
		}
		return Shell::url( 'observe', $args );
	}

	/**
	 * Nonce action of the export of one procedure.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return string
	 */
	public static function nonce_action( int $procedure_id ): string {
		return 'prc_observe_export_' . $procedure_id;
	}

	// ─── el filtro ─────────────────────────────────────────────────────────

	/**
	 * The filter a request carries, narrowed to what a consultation offers.
	 *
	 * Sin la columna del estado de revisión no se filtra por él, y las
	 * incompletas son cosa de quien revisa: sus motivos no se enseñan aquí.
	 *
	 * @param array<string, mixed>  $source  `$_GET` or `$_POST`, still slashed.
	 * @param array<string, string> $columns The columns the consultation shows.
	 * @return array<string, mixed>
	 */
	public static function filter( array $source, array $columns ): array {
		$filtro               = ApplicationsPanel::filter( $source );
		$filtro['incomplete'] = false;
		if ( ! isset( $columns['state'] ) ) {
			$filtro['review'] = '';
		}
		return $filtro;
	}

	// ─── el modelo ─────────────────────────────────────────────────────────

	/**
	 * Everything the screen decides before painting.
	 *
	 * @param array<string, mixed> $request `$_GET`.
	 * @return array<string, mixed>
	 */
	public static function model( array $request ): array {
		$m       = array(
			'aviso'      => '',
			'aviso_tipo' => 'aviso',
			'mode'       => 'list',
			'list_url'   => self::url(),
			'zones'      => '',
			'procedures' => array(),
		);
		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			$m['aviso'] = 'Debe iniciar sesión con su usuario para consultar solicitudes.';
			return $m;
		}
		if ( ! ProcedureAccess::can_observe( $user_id ) ) {
			$m['aviso'] = self::why_not( $user_id );
			return $m;
		}
		$m['zones']   = ProcedureAccess::can_manage_procedures( $user_id ) ? '' : Shell::zone_names( $user_id );
		$procedure_id = isset( $request[ self::ARG_PROCEDURE ] ) && is_string( $request[ self::ARG_PROCEDURE ] ) ? absint( $request[ self::ARG_PROCEDURE ] ) : 0;
		if ( $procedure_id <= 0 ) {
			$m['procedures'] = self::procedures( $user_id );
			return $m;
		}
		$ambito = null;
		if ( ! ProcedureAccess::can_observe( $user_id, $procedure_id, $ambito ) ) {
			$m['aviso']      = self::NOT_FOUND;
			$m['aviso_tipo'] = 'error';
			return $m;
		}
		return array_merge( $m, self::table( $user_id, $procedure_id, $ambito, $request ) );
	}

	/**
	 * Why a person has no consultation at all, in words.
	 *
	 * @param int $user_id User ID.
	 * @return string
	 */
	private static function why_not( int $user_id ): string {
		if ( ! user_can( $user_id, ProcedureAccess::CAP_VIEW_APPLICATIONS ) ) {
			return 'Su perfil no consulta solicitudes. Si debería hacerlo, pídalo a quien administre el aplicativo.';
		}
		return array() === ProcedureAccess::observer_zones( $user_id )
			? 'Su cuenta no tiene ninguna zona asignada, así que no hay solicitudes que consultar. La zona la pone quien administra el aplicativo.'
			: 'Ninguna de las zonas de su cuenta tiene centros en el catálogo, así que no hay solicitudes que consultar. Revíselo con quien administre el aplicativo.';
	}

	/**
	 * The procedures this person may observe, with how many applications they see in each.
	 *
	 * Quien consulta una zona, los que tienen alguna solicitud de sus centros;
	 * quien gestiona, los de su ámbito, tengan o no.
	 *
	 * @param int $user_id User ID.
	 * @return array<int, array{id:int, title:string, state:string, count:int, url:string}>
	 */
	private static function procedures( int $user_id ): array {
		$ids = get_posts(
			array(
				'post_type'        => ProcedurePostType::POST_TYPE,
				'post_status'      => 'publish',
				'numberposts'      => -1,
				'fields'           => 'ids',
				'orderby'          => 'title',
				'order'            => 'ASC',
				'meta_key'         => ProcedureMetaKeys::OBSERVABLE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- solo los que admiten consulta (ADR-0043).
				'meta_value'       => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- ídem.
				'suppress_filters' => true,
			)
		);
		$ids = array_map( 'intval', is_array( $ids ) ? $ids : array() );
		if ( array() === $ids ) {
			return array();
		}
		$codigos = self::codes_by_procedure( $ids );
		$out     = array();
		foreach ( $ids as $id ) {
			$ambito = null;
			if ( ! ProcedureAccess::can_observe( $user_id, $id, $ambito ) ) {
				continue;
			}
			$suyas = (array) ( $codigos[ $id ] ?? array() );
			if ( isset( $ambito['codes'] ) ) {
				$suyas = array_intersect( $suyas, $ambito['codes'] );
				if ( array() === $suyas ) {
					continue;
				}
			}
			$out[] = array(
				'id'    => $id,
				'title' => (string) get_post_field( 'post_title', $id ),
				'state' => ProcedureState::label( ProcedureState::of_post( $id ) ),
				'count' => count( $suyas ),
				'url'   => self::url( $id ),
			);
		}
		return $out;
	}

	/**
	 * The centre code of every published application of these procedures, in one query.
	 *
	 * @param int[] $procedure_ids Procedure post IDs.
	 * @return array<int, string[]> Procedure ID => one code per application.
	 */
	private static function codes_by_procedure( array $procedure_ids ): array {
		global $wpdb;
		$huecos = implode( ',', array_fill( 0, count( $procedure_ids ), '%d' ) );
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- los huecos son %d y se preparan; contar por centro sin cargar cada solicitud.
		$filas = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.post_parent, COALESCE(m.meta_value, '') AS code FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = %s WHERE p.post_type = %s AND p.post_status = 'publish' AND p.post_parent IN ({$huecos})",
				array_merge( array( ApplicationMetaKeys::CENTRE_CODE, ApplicationPostType::POST_TYPE ), $procedure_ids )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		$out = array();
		foreach ( (array) $filas as $fila ) {
			$out[ (int) $fila->post_parent ][] = (string) $fila->code;
		}
		return $out;
	}

	/**
	 * The table of one procedure, as this person sees it.
	 *
	 * @param int                     $user_id      Who is looking.
	 * @param int                     $procedure_id Procedure post ID.
	 * @param array<string, string[]> $scope        What {@see ProcedureAccess::can_observe()} left in its scope.
	 * @param array<string, mixed>    $request      `$_GET`.
	 * @return array<string, mixed>
	 */
	private static function table( int $user_id, int $procedure_id, array $scope, array $request ): array {
		$columnas = ObserveSettings::columns( $procedure_id );
		$res      = ApplicationQuery::run( $procedure_id, self::filter( $request, $columnas ), $scope );
		$filtro   = $res['filter'];
		$url      = static function ( array $changes = array() ) use ( $procedure_id, $filtro ): string {
			return self::url( $procedure_id, array_map( 'rawurlencode', ApplicationFilter::query( $filtro, $changes ) ) );
		};
		$base     = self::url( $procedure_id );
		return array(
			'mode'         => 'table',
			'aviso'        => self::$rejected,
			'aviso_tipo'   => 'error',
			'procedure_id' => $procedure_id,
			'title'        => (string) get_post_field( 'post_title', $procedure_id ),
			'columns'      => $columnas,
			// El enlace se pinta si el procedimiento deja descargar; quien
			// decide cada descarga es ProcedureAccess::can_view_application().
			'rows'         => self::rows( $procedure_id, $res['posts'], $columnas, (bool) get_post_meta( $procedure_id, ProcedureMetaKeys::OBSERVER_FILES, true ) ),
			'can_export'   => self::may_export( $procedure_id ),
			'nonce'        => self::nonce_action( $procedure_id ),
			'table'        => array(
				'filter'     => $filtro,
				'active'     => $res['active'],
				'args'       => ApplicationFilter::query( array( 'page' => 1 ) + $filtro ),
				'all'        => (int) $res['all'],
				'total'      => (int) $res['total'],
				'page'       => (int) $res['page'],
				'pages'      => (int) $res['pages'],
				'from'       => (int) $res['from'],
				'to'         => (int) $res['to'],
				'states'     => isset( $columnas['state'] ) ? self::states( $res, $procedure_id ) : array(),
				'options'    => $res['options'],
				'zone_label' => CentreColumns::zone_label(),
				'teachers'   => ProcedureMetaKeys::AUDIENCE_TEACHERS === get_post_meta( $procedure_id, ProcedureMetaKeys::AUDIENCE, true ),
				'pager'      => ApplicationsPanel::pager( $url, $filtro, (int) $res['pages'] ),
				'action'     => strtok( $base, '?' ),
				'hidden'     => wp_parse_args( (string) wp_parse_url( $base, PHP_URL_QUERY ) ),
				'reset_url'  => $res['active'] ? $base : '',
			),
		);
	}

	/**
	 * How many there are in each review state, each a shortcut to filter by it.
	 *
	 * @param array<string, mixed> $res          What {@see ApplicationQuery::run()} returned.
	 * @param int                  $procedure_id Procedure post ID.
	 * @return array<int, array{key:string, label:string, count:int, url:string, current:bool}>
	 */
	private static function states( array $res, int $procedure_id ): array {
		$vacio = ApplicationFilter::sanitize( array() );
		$out   = array(
			array(
				'key'     => '',
				'label'   => 'Todas',
				'count'   => (int) $res['all'],
				'url'     => self::url( $procedure_id ),
				'current' => '' === $res['filter']['review'],
			),
		);
		foreach ( ApplicationMetaKeys::review_states() as $clave => $rotulo ) {
			$out[] = array(
				'key'     => $clave,
				'label'   => $rotulo,
				'count'   => (int) ( $res['counts'][ $clave ] ?? 0 ),
				'url'     => self::url( $procedure_id, array_map( 'rawurlencode', ApplicationFilter::query( $vacio, array( 'review' => $clave ) ) ) ),
				'current' => $clave === $res['filter']['review'],
			);
		}
		return $out;
	}

	/**
	 * The rows of one page: only the columns the consultation shows.
	 *
	 * La fila entera del taller no sale de aquí: se recorta a las columnas de
	 * la consulta, así que lo que no está en ellas —el DNI, la nota— no llega
	 * a la vista. Los documentos se pintan como enlace solo si el
	 * procedimiento deja descargarlos; si no, se dice su nombre.
	 *
	 * @param int                   $procedure_id Procedure post ID.
	 * @param \WP_Post[]            $posts        Applications of the page.
	 * @param array<string, string> $columns      Column => heading.
	 * @param bool                  $files        Whether documents may be downloaded.
	 * @return array<int, array{id:int, cells:array<string, string>, files:array<string, array{application:int, id:string, name:string}>}>
	 */
	private static function rows( int $procedure_id, array $posts, array $columns, bool $files ): array {
		$catalogo  = array() !== array_intersect_key( $columns, array_flip( CentreColumns::KEYS ) );
		$personas  = array() !== array_intersect_key( $columns, PeopleExport::COLUMNS ) ? PeopleExport::spec( $procedure_id ) : null;
		$ficheros  = array();
		$preguntas = Applications::questions( $procedure_id );
		foreach ( $preguntas as $pregunta ) {
			if ( ProcedureQuestions::TYPE_FILE === (string) ( $pregunta['type'] ?? '' ) && isset( $columns[ (string) $pregunta['key'] ] ) ) {
				$ficheros[ (string) $pregunta['key'] ] = true;
			}
		}
		$out = array();
		foreach ( Applications::rows( $procedure_id, $posts, $catalogo ) as $fila ) {
			if ( null !== $personas ) {
				$fila += PeopleExport::cells( $personas, People::of( (int) $fila['id'] ) );
			}
			$celdas = array();
			foreach ( array_keys( $columns ) as $clave ) {
				$celdas[ $clave ] = (string) ( $fila[ $clave ] ?? '' );
			}
			$out[] = array(
				'id'    => (int) $fila['id'],
				'cells' => $celdas,
				'files' => $files ? array_intersect_key( (array) $fila[ Applications::KEY_FILES ], $ficheros ) : array(),
			);
		}
		return $out;
	}

	// ─── la exportación ────────────────────────────────────────────────────

	/**
	 * Serve the CSV of what this person sees, when the procedure lets them.
	 *
	 * Las columnas de la consulta, el filtro que viaja en el envío y, siempre,
	 * solo las solicitudes de sus zonas: los ID salen de la misma consulta que
	 * la tabla, con el mismo acotado. No guarda nada: ni la elección, que en
	 * la consulta no se elige.
	 *
	 * @return void
	 */
	public static function handle(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- se comprueba justo abajo, en cuanto se sabe de qué procedimiento es.
		if ( self::OP_EXPORT !== sanitize_key( wp_unslash( (string) ( $_POST[ self::FIELD_DO ] ?? '' ) ) ) || ! is_user_logged_in() ) {
			return;
		}
		$procedure_id = absint( wp_unslash( $_POST[ self::FIELD_ID ] ?? 0 ) );
		$nonce        = sanitize_text_field( wp_unslash( (string) ( $_POST[ self::NONCE_NAME ] ?? '' ) ) );
		$post         = $_POST;
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		if ( false === wp_verify_nonce( $nonce, self::nonce_action( $procedure_id ) ) ) {
			return;
		}
		$cuerpo = self::csv( get_current_user_id(), $procedure_id, $post );
		if ( null === $cuerpo ) {
			self::$rejected = 'Este procedimiento no permite exportar su consulta.';
			return;
		}
		$nombre = ApplicationExport::filename( (string) get_post_field( 'post_title', $procedure_id ), ApplicationExport::FORMAT_CSV );
		Shell::send_header( 'Content-Type: text/csv; charset=utf-8' );
		Shell::send_header( 'Content-Disposition: attachment; filename="' . $nombre . '"' );
		Shell::send_header( 'Content-Length: ' . strlen( $cuerpo ) );
		Shell::send_header( 'Cache-Control: no-store, no-cache, must-revalidate' );
		echo $cuerpo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- es un CSV, no HTML; ApplicationExport lo entrecomilla y desactiva las fórmulas.
		Shell::leave();
	}

	/**
	 * Whether the procedure lets its consultation be exported.
	 *
	 * Quién la consulta lo decide {@see ProcedureAccess::can_observe()}; esto
	 * es solo lo que el procedimiento permite (`prc_observer_export`).
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return bool
	 */
	private static function may_export( int $procedure_id ): bool {
		return (bool) get_post_meta( $procedure_id, ProcedureMetaKeys::OBSERVER_EXPORT, true );
	}

	/**
	 * The CSV a person may export of one procedure, or null when they may not.
	 *
	 * @param int                  $user_id      Who exports.
	 * @param int                  $procedure_id Procedure post ID.
	 * @param array<string, mixed> $source       Where the filter travels: `$_POST`, still slashed.
	 * @return string|null
	 */
	public static function csv( int $user_id, int $procedure_id, array $source ): ?string {
		$ambito = null;
		if ( ! ProcedureAccess::can_observe( $user_id, $procedure_id, $ambito ) || ! self::may_export( $procedure_id ) ) {
			return null;
		}
		$columnas = ObserveSettings::columns( $procedure_id );
		// Sin columnas, ApplicationExport exportaría todas, DNI incluido: eso no.
		if ( array() === $columnas ) {
			return null;
		}
		$ids = ApplicationQuery::ids( $procedure_id, self::filter( $source, $columnas ), $ambito );
		return ApplicationExport::csv( $procedure_id, array_keys( $columnas ), ApplicationExport::SHAPE_APPLICATION, $ids );
	}

	/**
	 * Render the shortcode.
	 *
	 * @param array<string, string>|string $atts Attributes.
	 * @return string
	 */
	public static function render( $atts = array() ): string {
		unset( $atts );
		Assets::enqueue();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- el procedimiento, el filtro y la página son de lectura.
		return ObserveView::html( self::model( $_GET ) );
	}
}
