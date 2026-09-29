<?php
/**
 * The applications of one school.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\Access\ProcedureAccess;
use Prc\Domain\ProcedureQuestions;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PublicFront\View\MyCentreView;

/**
 * Shortcode [prc_mine]: las solicitudes del centro de quien mira.
 *
 * Hoy es una vista del gestor de formularios filtrada por el código de
 * centro de la persona. Aquí es la misma tabla que la gente ya conoce:
 * procedimiento, fecha, quién la presentó con su cargo, coordinación y
 * opciones marcadas, ordenable pulsando en las cabeceras.
 *
 * La ordenación la hace el servidor ({@see sort()}, {@see ordered()}): la
 * columna y el sentido viajan en la URL, contra una lista blanca, y la
 * pantalla funciona entera sin una línea de JavaScript (ADR-0022).
 */
final class MyCentre {

	public const SHORTCODE = 'prc_mine';

	/**
	 * Query var de la columna por la que se ordena.
	 */
	public const VAR_SORT = 'orden';

	/**
	 * Query var del sentido de la ordenación.
	 */
	public const VAR_DIR = 'dir';

	/**
	 * Columna por la que se ordena mientras nadie pida otra cosa: la fecha,
	 * de la más reciente a la más antigua, que es como se entra a mirar.
	 */
	public const SORT_DEFAULT = 'fecha';

	/**
	 * Register the shortcode.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'render' ) );
	}

	/**
	 * The columns of the table: the whitelist of what can be sorted.
	 *
	 * La clave es lo que viaja en la URL —en castellano, como las direcciones
	 * del aplicativo— y `field` es la clave de la fila por la que se compara.
	 * Lo que no esté aquí no ordena nada.
	 *
	 * @return array<string, array{label:string, field:string}>
	 */
	public static function columns(): array {
		return array(
			'procedimiento' => array(
				'label' => 'Procedimiento',
				'field' => 'title',
			),
			'fecha'         => array(
				'label' => 'Fecha',
				'field' => 'date',
			),
			'solicitante'   => array(
				'label' => 'Solicitante (cargo)',
				'field' => 'applicant',
			),
			'coordinador'   => array(
				'label' => 'Coordinador/a',
				'field' => 'coordinator',
			),
			'opciones'      => array(
				'label' => 'Opciones',
				'field' => 'options_text',
			),
			'revision'      => array(
				'label' => 'Revisión',
				'field' => 'review_label',
			),
		);
	}

	/**
	 * What the URL is asking to sort by.
	 *
	 * Una columna que no está en la lista blanca, o un sentido que no es
	 * `asc` ni `desc`, no son un error: se cae al orden por omisión.
	 *
	 * @return array{col:string, dir:string}
	 */
	public static function sort(): array {
		$col = Workspace::input( self::VAR_SORT );
		$dir = Workspace::input( self::VAR_DIR );
		if ( ! isset( self::columns()[ $col ] ) ) {
			$col = self::SORT_DEFAULT;
			$dir = 'desc';
		}
		return array(
			'col' => $col,
			'dir' => 'desc' === $dir ? 'desc' : 'asc',
		);
	}

	/**
	 * Everything the screen decides before painting.
	 *
	 * @return array{aviso:string, centre:array{code:string, name:string, ownership:string}, rows:array<int, array<string, mixed>>, sort:array{col:string, dir:string}, headers:array<string, array{label:string, sort:string, url:string}>, amend:array{count:int, closes:string, id:string}}
	 */
	public static function model(): array {
		$orden = self::sort();
		$m     = array(
			'aviso'   => '',
			'centre'  => array(
				'code'      => '',
				'name'      => '',
				'ownership' => '',
			),
			'rows'    => array(),
			'sort'    => $orden,
			'headers' => self::headers( $orden ),
			'amend'   => array(
				'count'  => 0,
				'closes' => '',
				'id'     => '',
			),
		);

		if ( ! is_user_logged_in() ) {
			$m['aviso'] = 'Debe iniciar sesión con su usuario para ver las solicitudes de su centro.';
			return $m;
		}
		$user_id = get_current_user_id();
		if ( ! user_can( $user_id, ProcedureAccess::CAP_APPLY ) ) {
			$m['aviso'] = 'Su perfil no presenta solicitudes de centro. Si forma parte del equipo directivo de un centro, pídalo a quien administre el aplicativo.';
			return $m;
		}
		$m['centre'] = Applications::centre_of( $user_id );
		if ( '' === $m['centre']['code'] ) {
			$m['aviso'] = 'Su cuenta no tiene centro asignado, así que no puede presentar ni consultar solicitudes. El código de centro lo pone quien administra el aplicativo.';
			return $m;
		}

		$solicitudes = Applications::for_centre( $m['centre']['code'] );
		// Cada fila lee el título, la ficha y las fechas de su procedimiento:
		// se cargan todos de una vez, con sus metas, y no dos consultas por fila.
		_prime_post_caches( array_unique( array_map( 'intval', wp_list_pluck( $solicitudes, 'post_parent' ) ) ), false, true );
		foreach ( $solicitudes as $solicitud ) {
			$m['rows'][] = self::row( $solicitud );
		}
		$m['rows']  = self::ordered( $m['rows'], $orden );
		$m['amend'] = self::amendment( $m['rows'] );
		return $m;
	}

	/**
	 * One application, as the row the table paints.
	 *
	 * @param \WP_Post $application The application.
	 * @return array<string, mixed>
	 */
	private static function row( \WP_Post $application ): array {
		$procedure_id = (int) $application->post_parent;
		$meta         = Applications::meta( (int) $application->ID );
		$autor        = get_userdata( (int) $application->post_author );
		$coord        = (array) $meta[ ApplicationMetaKeys::COORDINATOR ];
		$revision     = (string) $meta[ ApplicationMetaKeys::REVIEW_STATE ];
		$opciones     = self::options( $procedure_id, (array) $meta[ ApplicationMetaKeys::ANSWERS ] );
		$cierre       = (string) get_post_meta( $procedure_id, ProcedureMetaKeys::AMEND_CLOSES_AT, true );
		$estado       = ProcedureState::of_post( $procedure_id );

		// La subsanación está abierta mientras no se haya pasado su último
		// día; sin fecha de cierre no ha vencido, solo no se sabe cuándo.
		$subsanar = ApplicationMetaKeys::REVIEW_AMEND === $revision
			&& ( '' === $cierre || ProcedureState::today() <= $cierre );

		return array(
			'procedure_id' => (string) $procedure_id,
			'title'        => (string) get_post_field( 'post_title', $procedure_id ),
			'url'          => ApplyForm::url( $procedure_id ),
			'sheet'        => (string) get_permalink( $procedure_id ),
			'date'         => (string) get_the_date( 'Y-m-d H:i', $application ),
			'date_label'   => (string) get_the_date( 'd-m-Y', $application ),
			'applicant'    => false !== $autor ? (string) $autor->display_name : '',
			'position'     => (string) ( ApplicationMetaKeys::positions()[ $meta[ ApplicationMetaKeys::APPLICANT_POSITION ] ] ?? '' ),
			'coordinator'  => (string) ( $coord['name'] ?? '' ),
			'options'      => $opciones,
			'options_text' => implode( ' ', $opciones ),
			'state'        => $estado,
			'state_label'  => ProcedureState::label( $estado ),
			'review'       => $revision,
			'review_label' => (string) ( ApplicationMetaKeys::review_states()[ $revision ] ?? '' ),
			'note'         => (string) $meta[ ApplicationMetaKeys::REVIEW_NOTE ],
			'amend'        => $subsanar,
			'amend_closes' => $cierre,
		);
	}

	/**
	 * The options the school ticked, one string each.
	 *
	 * Las «opciones» del original son dos grupos de casillas cableados; aquí
	 * son las preguntas de opción del procedimiento (ADR-0019), sean las que
	 * sean, con su enunciado literal tal como se marcó.
	 *
	 * @param int                  $procedure_id Procedure post ID.
	 * @param array<string, mixed> $answers      Stored answers, by question key.
	 * @return string[]
	 */
	private static function options( int $procedure_id, array $answers ): array {
		$out = array();
		foreach ( Applications::questions( $procedure_id ) as $pregunta ) {
			if ( ! ProcedureQuestions::has_choices( (string) $pregunta['type'] ) ) {
				continue;
			}
			$valor = $answers[ (string) $pregunta['key'] ] ?? '';
			foreach ( is_array( $valor ) ? $valor : array( $valor ) as $opcion ) {
				$opcion = trim( (string) $opcion );
				if ( '' !== $opcion ) {
					$out[] = $opcion;
				}
			}
		}
		return $out;
	}

	/**
	 * The rows, sorted by what the URL asked for.
	 *
	 * Sin dato no hay orden posible: esas filas van al final, se ordene como
	 * se ordene. El resto compara sin tildes ni mayúsculas.
	 *
	 * @param array<int, array<string, mixed>> $rows What row() built.
	 * @param array{col:string, dir:string}    $sort What sort() read.
	 * @return array<int, array<string, mixed>>
	 */
	private static function ordered( array $rows, array $sort ): array {
		$campo = (string) self::columns()[ $sort['col'] ]['field'];
		$signo = 'desc' === $sort['dir'] ? -1 : 1;
		usort(
			$rows,
			static function ( array $a, array $b ) use ( $campo, $signo ): int {
				$x = (string) $a[ $campo ];
				$y = (string) $b[ $campo ];
				if ( ( '' === $x ) !== ( '' === $y ) ) {
					return '' === $x ? 1 : -1;
				}
				return $signo * strnatcasecmp( remove_accents( $x ), remove_accents( $y ) );
			}
		);
		return $rows;
	}

	/**
	 * Every column heading with its link, its arrow and its `aria-sort`.
	 *
	 * El enlace de la columna activa lleva el sentido contrario al que se
	 * está viendo: pulsar dos veces en la misma cabecera la da la vuelta.
	 *
	 * @param array{col:string, dir:string} $sort What sort() read.
	 * @return array<string, array{label:string, sort:string, url:string}>
	 */
	private static function headers( array $sort ): array {
		$base = Shell::url( 'mine' );
		$out  = array();
		foreach ( self::columns() as $clave => $columna ) {
			$activa        = $clave === $sort['col'];
			$vuelta        = $activa && 'asc' === $sort['dir'] ? 'desc' : 'asc';
			$out[ $clave ] = array(
				'label' => (string) $columna['label'],
				'sort'  => $activa ? ( 'asc' === $sort['dir'] ? 'ascending' : 'descending' ) : 'none',
				'url'   => '' === $base ? '' : add_query_arg(
					array(
						self::VAR_SORT => $clave,
						self::VAR_DIR  => $vuelta,
					),
					$base
				),
			);
		}
		return $out;
	}

	/**
	 * The amendments still open, for the notice above the table.
	 *
	 * @param array<int, array<string, mixed>> $rows What row() built.
	 * @return array{count:int, closes:string, id:string} `closes` is the nearest deadline, dd-mm-aaaa; `id` only when there is one.
	 */
	private static function amendment( array $rows ): array {
		$abiertas = array();
		foreach ( $rows as $fila ) {
			if ( ! empty( $fila['amend'] ) ) {
				$abiertas[] = $fila;
			}
		}
		// Las fechas se comparan en `Y-m-d`, que es como se guardan, y solo
		// se escriben del derecho al final: así la más próxima es la primera.
		$fechas = array_filter( wp_list_pluck( $abiertas, 'amend_closes' ) );
		sort( $fechas );
		return array(
			'count'  => count( $abiertas ),
			'closes' => self::day( (string) ( $fechas[0] ?? '' ) ),
			'id'     => 1 === count( $abiertas ) ? (string) $abiertas[0]['procedure_id'] : '',
		);
	}

	/**
	 * One stored day, as the table writes it.
	 *
	 * @param string $ymd Stored day, Y-m-d.
	 * @return string dd-mm-aaaa, or empty.
	 */
	private static function day( string $ymd ): string {
		return '' === trim( $ymd ) ? '' : (string) mysql2date( 'd-m-Y', $ymd );
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
		return MyCentreView::html( self::model() );
	}
}
