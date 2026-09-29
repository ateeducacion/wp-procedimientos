<?php
/**
 * The procedures someone manages, with their state and their applications.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\Access\ProcedureAccess;
use Prc\Domain\DateRange;
use Prc\Domain\ProcedureState;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ApplicationPostType;
use Prc\PostType\ProcedurePostType;
use Prc\PublicFront\View\WorkspaceView;
use Prc\Taxonomy\ProcedureTaxonomies;

/**
 * Shortcode [prc_workspace]: la pantalla «Gestión de procedimientos».
 *
 * Es la pantalla en la que se empieza el día: qué procedimientos hay en mi
 * ámbito, en qué estado están, cuántas solicitudes han llegado y por dónde
 * se entra a cada uno. Hoy eso es una vista del gestor de formularios
 * filtrada por parámetros de la URL.
 *
 * Aquí no se muta nada: se lee, y por eso todos sus parámetros van en la
 * URL. Crear, publicar, archivar y enviar a la papelera es del taller
 * ({@see ProcedureEditor}). El guardián sigue siendo {@see ProcedureAccess}:
 * lo que no se pueda abrir no se enumera, y quien no tiene ámbito no ve nada
 * (falla en cerrado).
 */
final class Workspace {

	public const SHORTCODE = 'prc_workspace';

	/**
	 * Query var of the curso escolar filter.
	 *
	 * Ninguna se llama como una taxonomía: `prc_course` es una variable de
	 * consulta pública y usarla aquí convertiría la página en el archivo de
	 * un término, que ya no lleva nuestro shortcode.
	 */
	public const VAR_COURSE = 'prc_filter_course';

	/**
	 * Query var of the state filter.
	 */
	public const VAR_STATE = 'prc_filter_state';

	/**
	 * Query var of the free-text search over the table.
	 *
	 * El original buscaba con el nombre de parámetro de su gestor de
	 * formularios; aquí la variable lleva nuestro prefijo, como las demás.
	 */
	public const VAR_SEARCH = 'prc_q';

	/**
	 * Query var other screens use to say what just happened.
	 */
	public const VAR_NOTICE = 'aviso';

	/**
	 * Post statuses a procedure can be in. La papelera no es uno.
	 *
	 * @var string[]
	 */
	private const STATUSES = array( 'publish', 'future', 'draft', 'pending', 'private' );

	/**
	 * Register the shortcode.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'render' ) );
	}

	/**
	 * The state filter, in the order a person reads it.
	 *
	 * @return array<string, string> clave => etiqueta.
	 */
	public static function state_filters(): array {
		$estados = ProcedureMetaKeys::states();
		$orden   = array(
			ProcedureMetaKeys::STATE_OPEN,
			ProcedureMetaKeys::STATE_AMENDMENT,
			ProcedureMetaKeys::STATE_UPCOMING,
			ProcedureMetaKeys::STATE_CLOSED,
			ProcedureMetaKeys::STATE_RESOLVED,
			ProcedureMetaKeys::STATE_DRAFT,
			ProcedureMetaKeys::STATE_ARCHIVED,
		);
		$out     = array( 'all' => 'Todos' );
		foreach ( $orden as $estado ) {
			$out[ $estado ] = $estados[ $estado ];
		}
		return $out;
	}

	/**
	 * The icon of each state, for the legend and for the table.
	 *
	 * Son los iconos de la leyenda del sistema que se sustituye, repartidos
	 * entre los estados que este aplicativo sí tiene: allí eran ocho cruces de
	 * un estado escrito a mano con la fecha límite, y aquí el estado se deriva
	 * de las fechas ({@see ProcedureState}), así que son siete y ninguno puede
	 * contradecir al plazo. El color de cada uno lo pone la hoja.
	 *
	 * @return array<string, string> estado => nombre del icono.
	 */
	public static function state_icons(): array {
		return array(
			ProcedureMetaKeys::STATE_OPEN      => 'task_alt',
			ProcedureMetaKeys::STATE_AMENDMENT => 'contact_support',
			ProcedureMetaKeys::STATE_UPCOMING  => 'schedule',
			ProcedureMetaKeys::STATE_CLOSED    => 'cancel',
			ProcedureMetaKeys::STATE_RESOLVED  => 'view_list',
			ProcedureMetaKeys::STATE_DRAFT     => 'reset_brightness',
			ProcedureMetaKeys::STATE_ARCHIVED  => 'tonality',
		);
	}

	/**
	 * Read one navigation parameter.
	 *
	 * @param string $key      Query var.
	 * @param string $fallback What to return when it is not there.
	 * @return string
	 */
	public static function input( string $key, string $fallback = '' ): string {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- filtros de lectura: esta pantalla no muta nada.
		return isset( $_GET[ $key ] ) && is_string( $_GET[ $key ] )
			? sanitize_text_field( wp_unslash( $_GET[ $key ] ) )
			: $fallback;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * What the URL is asking for.
	 *
	 * @return array{course:int, state:string, q:string}
	 */
	public static function selection(): array {
		$estado = self::input( self::VAR_STATE, 'all' );

		return array(
			'course' => max( 0, (int) self::input( self::VAR_COURSE ) ),
			'state'  => isset( self::state_filters()[ $estado ] ) ? $estado : 'all',
			'q'      => self::input( self::VAR_SEARCH ),
		);
	}

	/**
	 * The list URL, keeping what is selected.
	 *
	 * @param array<string, mixed> $selection What selection() returned.
	 * @param array<string, mixed> $changes   What to change in it.
	 * @return string
	 */
	public static function url( array $selection, array $changes = array() ): string {
		$s    = array_merge( $selection, $changes );
		$args = array();
		if ( (int) $s['course'] > 0 ) {
			$args[ self::VAR_COURSE ] = (string) $s['course'];
		}
		if ( 'all' !== (string) $s['state'] ) {
			$args[ self::VAR_STATE ] = (string) $s['state'];
		}
		if ( '' !== (string) ( $s['q'] ?? '' ) ) {
			$args[ self::VAR_SEARCH ] = (string) $s['q'];
		}
		return Shell::url( 'workspace', $args );
	}

	/**
	 * Everything the screen decides before painting.
	 *
	 * @return array<string, mixed>
	 */
	public static function model(): array {
		$m           = self::blank();
		$m['reason'] = self::why_nothing();
		if ( '' !== $m['reason'] ) {
			return $m;
		}

		$user_id = get_current_user_id();
		$todas   = ProcedureAccess::can_edit_all_areas( $user_id );
		$crear   = Shell::url( 'editor' );
		$m       = array_merge(
			$m,
			array(
				'can_use'    => true,
				'notice'     => self::flash(),
				'page_id'    => '' === (string) get_option( 'permalink_structure' ) ? (int) get_queried_object_id() : 0,
				'scoped'     => ! $todas,
				'subtitle'   => $todas
					? 'Todos los procedimientos, de todos los ámbitos.'
					: 'Solo los procedimientos de su ámbito: los que convocan otros ámbitos no salen aquí.',
				'can_create' => '' !== $crear,
				'create_url' => $crear,
			)
		);

		$rows         = self::collect( $user_id );
		$m['courses'] = self::courses( $rows );
		$s            = self::selection();
		$en_url       = $s['course'] > 0;
		$s['course']  = self::course_shown( $s['course'], $m['courses'] );
		$rows         = self::narrow( $rows, $s );

		// Las cifras se cuentan sobre lo que se está mirando, antes de acotar
		// por estado: si no, el desplegable diría siempre cero en el resto.
		foreach ( $rows as $row ) {
			++$m['counts']['all'];
			++$m['counts'][ $row['state'] ];
		}
		$rows = self::only_state( $rows, (string) $s['state'] );
		usort( $rows, array( self::class, 'compare' ) );

		// ponytail: sin paginar —un curso de un ámbito son unas decenas de
		// filas—; si un curso pasa del centenar, paginar el listado.
		$m['rows']         = self::with_applications( $rows );
		$m['total']        = count( $rows );
		$m['selection']    = $s;
		$m['course_label'] = (string) ( $m['courses'][ $s['course'] ] ?? '' );

		if ( array() === $rows ) {
			$filtrando       = 'all' !== $s['state'] || $en_url || '' !== $s['q'];
			$m['empty_text'] = self::empty_text( $filtrando, $todas );
			$m['reset_url']  = $filtrando
				? self::url(
					$s,
					array(
						'course' => 0,
						'state'  => 'all',
						'q'      => '',
					)
				)
				: '';
		}

		return $m;
	}

	/**
	 * The model of a screen with nothing on it yet.
	 *
	 * @return array<string, mixed>
	 */
	private static function blank(): array {
		return array(
			'can_use'      => false,
			'reason'       => '',
			'notice'       => array(
				'type' => '',
				'text' => '',
			),
			'subtitle'     => '',
			'selection'    => self::selection(),
			'courses'      => array(),
			'scoped'       => false,
			'counts'       => array_fill_keys( array_keys( self::state_filters() ), 0 ),
			'total'        => 0,
			'rows'         => array(),
			'can_create'   => false,
			'create_url'   => '',
			'empty_text'   => '',
			'reset_url'    => '',
			'page_id'      => 0,
			'course_label' => '',
		);
	}

	/**
	 * Why this person gets no list at all.
	 *
	 * @return string Empty when there is a list to show.
	 */
	private static function why_nothing(): string {
		if ( ! is_user_logged_in() ) {
			return 'Debe iniciar sesión con su usuario para gestionar procedimientos.';
		}
		$user_id = get_current_user_id();
		if ( ProcedureAccess::can_manage_procedures( $user_id ) ) {
			return '';
		}
		// Falta el permiso, o falta el ámbito: no es lo mismo y no se arregla
		// en el mismo sitio.
		return user_can( $user_id, ProcedureAccess::CAP_MANAGE_PROCEDURES )
			? ProcedureAccess::scope_assignment_message( $user_id )
			: 'Su usuario todavía no gestiona procedimientos. Pídalo a quien administre el aplicativo.';
	}

	/**
	 * The rows this person may open, before any filter.
	 *
	 * El acotado de la consulta solo esconde; quien decide es siempre
	 * `ProcedureAccess`. Se pregunta por `can_open()` y no por `can_edit()`
	 * porque un procedimiento histórico se sigue consultando: esconderlo del
	 * listado sería perderlo de vista.
	 *
	 * @param int $user_id User ID.
	 * @return array<int, array<string, mixed>>
	 */
	private static function collect( int $user_id ): array {
		$rows = array();
		foreach ( self::scope( $user_id ) as $post ) {
			if ( ProcedureAccess::can_open( $user_id, (int) $post->ID ) ) {
				$rows[] = self::row( $post );
			}
		}
		return $rows;
	}

	/**
	 * The procedures of this person's ámbitos, plus the ones just created.
	 *
	 * @param int $user_id User ID.
	 * @return \WP_Post[]
	 */
	private static function scope( int $user_id ): array {
		$base = array(
			'post_type'           => ProcedurePostType::POST_TYPE,
			'post_status'         => self::STATUSES,
			// ponytail: el ámbito entero en memoria —hoy son unas decenas de
			// procedimientos por ámbito, y el estado derivado no se puede pedir
			// a la base de datos—; si crece, guardar el estado en meta y paginar.
			'posts_per_page'      => 500, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- el listado filtra sobre el ámbito completo.
			'orderby'             => 'date',
			'order'               => 'DESC',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		);

		if ( ProcedureAccess::can_edit_all_areas( $user_id ) ) {
			return get_posts( $base );
		}

		$areas = ProcedureAccess::scope_areas( $user_id );
		if ( array() === $areas ) {
			return array();
		}

		return get_posts(
			array_merge(
				$base,
				array(
					'tax_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- acotar por ámbito es el requisito.
						array(
							'taxonomy'     => ProcedureTaxonomies::AREA,
							'field'        => 'term_id',
							'terms'        => $areas,
						'include_children' => false,
						),
					),
				)
			)
		);
	}

	/**
	 * One row of the table.
	 *
	 * @param \WP_Post $post Procedure.
	 * @return array<string, mixed>
	 */
	private static function row( \WP_Post $post ): array {
		$id       = (int) $post->ID;
		$titulo   = trim( (string) $post->post_title );
		$state    = ProcedureState::of_post( $id );
		$opens    = (string) get_post_meta( $id, ProcedureMetaKeys::OPENS_AT, true );
		$closes   = (string) get_post_meta( $id, ProcedureMetaKeys::CLOSES_AT, true );
		$publicos = ProcedureMetaKeys::audiences();
		$publico  = ProcedureMetaKeys::in_list(
			get_post_meta( $id, ProcedureMetaKeys::AUDIENCE, true ),
			$publicos,
			ProcedureMetaKeys::AUDIENCE_SCHOOLS
		);

		return array(
			'id'               => $id,
			'title'            => '' !== $titulo ? $titulo : 'Procedimiento sin título',
			'areas'            => self::terms( $id, ProcedureTaxonomies::AREA ),
			'courses'          => self::terms( $id, ProcedureTaxonomies::COURSE ),
			'opens'            => $opens,
			'dates'            => DateRange::of( $opens, $closes ),
			// La tabla escribe las fechas como se leen aquí: día, mes y año.
			'created'          => self::dmy( substr( (string) $post->post_date, 0, 10 ) ),
			'closes'           => self::dmy( $closes ),
			'color'            => ProcedureMetaKeys::header_hex( get_post_meta( $id, ProcedureMetaKeys::HEADER_COLOR, true ) ),
			'audience'         => $publico,
			'audience_label'   => (string) $publicos[ $publico ],
			'state'            => $state,
			'state_label'      => ProcedureState::label( $state ),
			'warning'          => self::mismatch( $id, $opens, $closes ),
			'archived'         => ProcedureAccess::is_archived( $id ),
			'status'           => (string) $post->post_status,
			'applications'     => 0,
			'url'              => Shell::url( 'editor', array( Shell::ARG_PROCEDURE => $id ) ),
			'applications_url' => ProcedureEditor::url( $id, ProcedureEditor::PANEL_APPLICATIONS ),
			// **Un borrador también se mira**: estando dentro y con permiso,
			// WordPress lo sirve en previsualización.
			'view_url'         => 'publish' === $post->post_status ? (string) get_permalink( $id ) : (string) get_preview_post_link( $id ),
		);
	}

	/**
	 * Which curso escolar the table is showing.
	 *
	 * @param int                $pedido What the URL asked for.
	 * @param array<int, string> $cursos The courses in scope, newest first.
	 * @return int 0 = todos los cursos.
	 */
	private static function course_shown( int $pedido, array $cursos ): int {
		if ( isset( $cursos[ $pedido ] ) ) {
			return $pedido;
		}
		// Un curso que no está deja de acotar; sin pedir ninguno se enseña el
		// más reciente, que es el que se trabaja.
		return $pedido > 0 || array() === $cursos ? 0 : (int) array_key_first( $cursos );
	}

	/**
	 * The rows of the course being shown, and of what was typed in the box.
	 *
	 * @param array<int, array<string, mixed>> $rows Rows in scope.
	 * @param array<string, mixed>             $s    Selection, with its course resolved.
	 * @return array<int, array<string, mixed>>
	 */
	private static function narrow( array $rows, array $s ): array {
		$curso = (int) $s['course'];
		if ( $curso > 0 ) {
			$rows = array_values(
				array_filter(
					$rows,
					static function ( array $row ) use ( $curso ): bool {
						return isset( $row['courses'][ $curso ] );
					}
				)
			);
		}
		return '' !== (string) $s['q'] ? self::matching( $rows, (string) $s['q'] ) : $rows;
	}

	/**
	 * The rows of one state, or all of them.
	 *
	 * @param array<int, array<string, mixed>> $rows   Rows being painted.
	 * @param string                           $estado State slug, or `all`.
	 * @return array<int, array<string, mixed>>
	 */
	private static function only_state( array $rows, string $estado ): array {
		if ( 'all' === $estado ) {
			return $rows;
		}
		return array_values(
			array_filter(
				$rows,
				static function ( array $row ) use ( $estado ): bool {
					return $estado === $row['state'];
				}
			)
		);
	}

	/**
	 * The rows the search box asks for.
	 *
	 * Busca en lo que se lee en la tabla —el título y los ámbitos que
	 * gestionan— y en el número del procedimiento, que es lo que la gente
	 * copia de un correo. Filtra el servidor, no el navegador: así el recuento
	 * cuadra y el resultado se puede guardar como marcador.
	 *
	 * @param array<int, array<string, mixed>> $rows  Rows in scope.
	 * @param string                           $texto What was typed.
	 * @return array<int, array<string, mixed>>
	 */
	private static function matching( array $rows, string $texto ): array {
		return array_values(
			array_filter(
				$rows,
				static function ( array $row ) use ( $texto ): bool {
					$heno = (string) $row['title'] . ' ' . (string) $row['id'] . ' ' . implode( ' ', (array) $row['areas'] );
					return false !== stripos( $heno, $texto );
				}
			)
		);
	}

	/**
	 * What does not add up in the dates of one procedure, if anything.
	 *
	 * La leyenda del sistema que se sustituye tenía dos cruces imposibles
	 * —«abierta con el plazo vencido» y «cerrada con el plazo vivo»— que solo
	 * existían porque alguien no reetiquetó a tiempo. Aquí el estado sale de
	 * las fechas y esas dos no pueden darse; lo que queda es lo que aquello
	 * siempre fue: un aviso de que las fechas escritas no cuadran.
	 *
	 * @param int    $post_id Procedure ID.
	 * @param string $opens   Application window start, Y-m-d.
	 * @param string $closes  Application window end, Y-m-d.
	 * @return string Empty when the dates add up.
	 */
	private static function mismatch( int $post_id, string $opens, string $closes ): string {
		if ( '' !== $opens && '' !== $closes && $closes < $opens ) {
			return 'El plazo de solicitud cierra antes de abrirse: repase las fechas.';
		}

		$abre   = (string) get_post_meta( $post_id, ProcedureMetaKeys::AMEND_OPENS_AT, true );
		$cierra = (string) get_post_meta( $post_id, ProcedureMetaKeys::AMEND_CLOSES_AT, true );
		if ( '' !== $abre && '' !== $cierra && $cierra < $abre ) {
			return 'La subsanación cierra antes de abrirse: repase las fechas.';
		}
		if ( '' !== $abre && '' !== $closes && $abre <= $closes ) {
			return 'La subsanación empieza antes de que cierre el plazo de solicitud: repase las fechas.';
		}
		return '';
	}

	/**
	 * One day written the way the table writes it.
	 *
	 * @param string $fecha Y-m-d, or anything else.
	 * @return string dd-mm-aaaa, or empty: la celda sin fecha va vacía.
	 */
	private static function dmy( string $fecha ): string {
		return 1 === preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $fecha, $trozos )
			? $trozos[3] . '-' . $trozos[2] . '-' . $trozos[1]
			: '';
	}

	/**
	 * Terms of one taxonomy on one post.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $taxonomy Taxonomy.
	 * @return array<int, string> term_id => nombre.
	 */
	private static function terms( int $post_id, string $taxonomy ): array {
		$terms = get_the_terms( $post_id, $taxonomy );
		if ( ! is_array( $terms ) ) {
			return array();
		}
		return wp_list_pluck( $terms, 'name', 'term_id' );
	}

	/**
	 * The course options, taken from the rows themselves, newest first.
	 *
	 * @param array<int, array<string, mixed>> $rows Rows in scope.
	 * @return array<int, string> term_id => nombre.
	 */
	private static function courses( array $rows ): array {
		$out = array();
		foreach ( $rows as $row ) {
			foreach ( $row['courses'] as $term_id => $nombre ) {
				$out[ $term_id ] = $nombre;
			}
		}
		arsort( $out, SORT_NATURAL );
		return $out;
	}

	/**
	 * How many applications each of the rows has: one query for the whole table.
	 *
	 * @param array<int, array<string, mixed>> $rows Rows being painted.
	 * @return array<int, array<string, mixed>>
	 */
	private static function with_applications( array $rows ): array {
		$ids = array_map( 'intval', array_column( $rows, 'id' ) );
		if ( array() === $ids ) {
			return $rows;
		}
		global $wpdb;
		$huecos = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery -- los huecos son %d y se preparan; un GROUP BY no lo da la API de posts.
		$filas = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_parent, COUNT(*) AS n FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish' AND post_parent IN ({$huecos}) GROUP BY post_parent",
				array_merge( array( ApplicationPostType::POST_TYPE ), $ids )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$cuenta = array();
		foreach ( (array) $filas as $fila ) {
			$cuenta[ (int) $fila->post_parent ] = (int) $fila->n;
		}
		foreach ( $rows as $i => $row ) {
			$rows[ $i ]['applications'] = $cuenta[ $row['id'] ] ?? 0;
		}
		return $rows;
	}

	/**
	 * Newest first; what has no dates yet is what was just created.
	 *
	 * @param array<string, mixed> $a One row.
	 * @param array<string, mixed> $b Another.
	 * @return int
	 */
	private static function compare( array $a, array $b ): int {
		$ka = '' !== $a['opens'] ? $a['opens'] : '9999-12-31';
		$kb = '' !== $b['opens'] ? $b['opens'] : '9999-12-31';
		return $ka === $kb ? strnatcasecmp( $a['title'], $b['title'] ) : strcmp( $kb, $ka );
	}

	/**
	 * What to say when the table has no rows.
	 *
	 * @param bool $filtered  Whether filters are narrowing the list.
	 * @param bool $all_areas Whether this person works across every ámbito.
	 * @return string
	 */
	private static function empty_text( bool $filtered, bool $all_areas ): string {
		if ( $filtered ) {
			return 'Ningún procedimiento coincide con lo que ha pedido. Pruebe a quitar algún filtro.';
		}
		return $all_areas
			? 'Todavía no hay ningún procedimiento. Cree el primero con «Nuevo procedimiento».'
			: 'Todavía no hay ningún procedimiento de su ámbito. Aquí solo salen los que convoca su ámbito; cree el primero con «Nuevo procedimiento».';
	}

	/**
	 * What another screen left said on the way here.
	 *
	 * @return array{type:string, text:string}
	 */
	private static function flash(): array {
		$avisos = array(
			'creado'       => array( 'ok', 'Procedimiento creado. Ya puede completarlo y publicarlo.' ),
			'guardado'     => array( 'ok', 'Cambios guardados.' ),
			'retirado'     => array( 'ok', 'El procedimiento se ha guardado. Su ámbito ya no lo organiza y dejará de tener acceso a su edición.' ),
			'borrado'      => array( 'ok', 'Procedimiento enviado a la papelera. Nada se ha perdido: se restaura desde el escritorio de WordPress.' ),
			'publicado'    => array( 'ok', 'Procedimiento publicado: ya se ve en la portada.' ),
			'despublicado' => array( 'ok', 'Procedimiento devuelto a borrador: deja de verse en la portada y no se pierde nada.' ),
			'archivado'    => array( 'ok', 'Procedimiento marcado como histórico: se consulta y se exporta, pero ya no se edita.' ),
			'permiso'      => array( 'error', 'Ese procedimiento es de otro ámbito: solo lo edita el ámbito que lo convoca o quien administra el aplicativo.' ),
		);

		$aviso = $avisos[ self::input( self::VAR_NOTICE ) ] ?? array( '', '' );

		return array(
			'type' => $aviso[0],
			'text' => $aviso[1],
		);
	}

	/**
	 * The screen, from its model.
	 *
	 * @param array<string, mixed> $model What model() returned.
	 * @return string
	 */
	public static function html( array $model ): string {
		return WorkspaceView::html( $model );
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
		return self::html( self::model() );
	}
}
