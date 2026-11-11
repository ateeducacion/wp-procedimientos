<?php
/**
 * The «Solicitudes» panel of the workshop: the filtered table, its pages,
 * the bulk review and what the export dialog counts.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\Domain\ApplicationFilter;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ApplicationPostType;

/**
 * El panel «Solicitudes» del taller (PLAN-0003, PR 6).
 *
 * Va aparte de {@see ProcedureEditor} para que no pase del umbral de PHPMD.
 * No comprueba ni el nonce ni quién pregunta: eso lo hace
 * {@see ProcedureEditor::handle()} con `ProcedureAccess::can_review()` antes
 * de llamar aquí. Tampoco pinta: es de {@see View\ApplicationsPanelView}.
 *
 * El filtro viaja por GET y vuelve en cada formulario del panel en campos
 * ocultos con los mismos nombres: revisar una, revisar en bloque o exportar
 * vuelve a la misma vista filtrada y a la misma página.
 */
final class ApplicationsPanel {

	/** Checkbox of each row in the bulk review: application IDs. */
	public const FIELD_ROWS = 'prc_rows';

	/** «Marcar las de esta página»: without script, it is what the server reads. */
	public const FIELD_PAGE = 'prc_rows_page';

	/** Hidden field with the IDs of the page, comma separated. */
	public const FIELD_PAGE_ROWS = 'prc_page_rows';

	/** Export dialog: `filter` or `all`. */
	public const FIELD_EXPORT_SCOPE = 'prc_export_scope';

	public const SCOPE_FILTER = 'filter';
	public const SCOPE_ALL    = 'all';

	/**
	 * Most applications one bulk review changes: one page.
	 *
	 * Cada una manda su correo dentro de la misma petición, sin cola: con más,
	 * el envío tardaría lo que tarden todos los correos.
	 */
	public const BULK_MAX = 50;

	/** Notice code of a bulk review, and the counts that go with it. */
	public const NOTICE_BULK = 'bloque';
	public const ARG_DONE    = 'revisadas';
	public const ARG_FAILED  = 'sin_cambiar';

	/**
	 * The filter a request carries, in GET or in the hidden fields of a POST.
	 *
	 * @param array<string, mixed> $source `$_GET` or `$_POST`, still slashed.
	 * @return array<string, mixed> What {@see ApplicationFilter::sanitize()} returns.
	 */
	public static function filter( array $source ): array {
		$raw = array();
		foreach ( ApplicationFilter::args() as $arg ) {
			if ( isset( $source[ $arg ] ) && is_string( $source[ $arg ] ) ) {
				$raw[ $arg ] = sanitize_text_field( wp_unslash( $source[ $arg ] ) );
			}
		}
		return ApplicationFilter::sanitize( $raw );
	}

	/**
	 * The panel URL with a filter, and anything else on top.
	 *
	 * `add_query_arg()` no codifica los valores: la búsqueda va codificada
	 * aquí, o un `&` tecleado partiría la dirección.
	 *
	 * @param int                  $procedure_id Procedure post ID.
	 * @param array<string, mixed> $filter       What {@see filter()} returned.
	 * @param array<string, mixed> $extra        More query args.
	 * @param array<string, mixed> $changes      Filter keys to change.
	 * @return string
	 */
	public static function url( int $procedure_id, array $filter, array $extra = array(), array $changes = array() ): string {
		$args = array_map( 'rawurlencode', ApplicationFilter::query( $filter, $changes ) );
		return ProcedureEditor::url( $procedure_id, ProcedureEditor::PANEL_APPLICATIONS, $args + $extra );
	}

	// ─── el modelo ─────────────────────────────────────────────────────────

	/**
	 * What the panel paints.
	 *
	 * @param int                              $procedure_id Procedure post ID.
	 * @param int                              $user_id      Who is looking.
	 * @param array<int, array<string, mixed>> $questions    Normalised questions.
	 * @param array<string, mixed>             $request      `$_GET`.
	 * @return array{app_cols:array, applications:array, export:array, people:array, table:array, actions:array}
	 */
	public static function model( int $procedure_id, int $user_id, array $questions, array $request ): array {
		$res   = ApplicationQuery::run( $procedure_id, self::filter( $request ) );
		$filas = Applications::rows( $procedure_id, $res['posts'] );
		foreach ( $filas as $i => $fila ) {
			$filas[ $i ]['reasons'] = $res['reasons'][ (int) $fila['id'] ] ?? array();
		}
		$args = array_map( 'rawurlencode', ApplicationFilter::query( $res['filter'] ) );
		return array(
			'app_cols'     => Applications::columns( $questions ),
			'applications' => $filas,
			'export'       => self::export_model( $procedure_id, $user_id, $res ),
			'people'       => PeopleSpecPanel::reviews( $res['posts'], $args ),
			'table'        => self::table( $procedure_id, $res ),
			// Retirar, restaurar, la prórroga, en nombre de un centro, la nota
			// interna y el historial (PLAN-0003, PR 9).
			'actions'      => ApplicationActionsPanel::model( $procedure_id, $user_id, $request, $res['posts'], $args ),
		);
	}

	/**
	 * The bar over the table: counts, filters, pages and what the forms carry back.
	 *
	 * @param int                  $procedure_id Procedure post ID.
	 * @param array<string, mixed> $res          What {@see ApplicationQuery::run()} returned.
	 * @return array<string, mixed>
	 */
	private static function table( int $procedure_id, array $res ): array {
		$filtro  = $res['filter'];
		$estados = array(
			array(
				'key'     => '',
				'label'   => 'Todas',
				'count'   => (int) $res['all'],
				'url'     => self::url( $procedure_id, ApplicationFilter::sanitize( array() ) ),
				'current' => '' === $filtro['review'],
			),
		);
		foreach ( ApplicationMetaKeys::review_states() as $clave => $rotulo ) {
			$estados[] = array(
				'key'     => $clave,
				'label'   => $rotulo,
				'count'   => (int) ( $res['counts'][ $clave ] ?? 0 ),
				// Atajos sobre el total: el enlace lleva el estado y nada más.
				'url'     => self::url( $procedure_id, ApplicationFilter::sanitize( array() ), array(), array( 'review' => $clave ) ),
				'current' => $clave === $filtro['review'],
			);
		}
		$base = ProcedureEditor::url( $procedure_id, ProcedureEditor::PANEL_APPLICATIONS );
		return array(
			'filter'     => $filtro,
			'active'     => $res['active'],
			'args'       => ApplicationFilter::query( $filtro ),
			'all'        => (int) $res['all'],
			'total'      => (int) $res['total'],
			'page'       => (int) $res['page'],
			'pages'      => (int) $res['pages'],
			'from'       => (int) $res['from'],
			'to'         => (int) $res['to'],
			'states'     => $estados,
			'incomplete' => array(
				'possible' => (bool) $res['may_be_incomplete'],
				'count'    => (int) $res['incomplete'],
			),
			'options'    => $res['options'],
			'zone_label' => CentreColumns::zone_label(),
			'teachers'   => ProcedureMetaKeys::AUDIENCE_TEACHERS === get_post_meta( $procedure_id, ProcedureMetaKeys::AUDIENCE, true ),
			'pager'      => self::pager(
				static function ( array $changes ) use ( $procedure_id, $filtro ): string {
					return self::url( $procedure_id, $filtro, array(), $changes );
				},
				$filtro,
				(int) $res['pages']
			),
			'action'     => strtok( $base, '?' ),
			'hidden'     => wp_parse_args( (string) wp_parse_url( $base, PHP_URL_QUERY ) ),
			'reset_url'  => $res['active'] ? $base : '',
			'page_ids'   => array_map( 'intval', wp_list_pluck( $res['posts'], 'ID' ) ),
			'bulk_max'   => self::BULK_MAX,
		);
	}

	/**
	 * The links to the pages: the first, the last and two on each side of this one.
	 *
	 * Lo usan el panel del taller y la consulta de solo lectura: cada una da
	 * su forma de construir la dirección de una página.
	 *
	 * @param callable             $url    Receives the filter changes, returns the URL.
	 * @param array<string, mixed> $filter Checked filter, with its page.
	 * @param int                  $pages  How many pages.
	 * @return array{prev:string, next:string, pages:array<int, array{n:int, url:string}>} A gap is n 0 and no URL.
	 */
	public static function pager( callable $url, array $filter, int $pages ): array {
		$actual = (int) $filter['page'];
		$out    = array(
			'prev'  => $actual > 1 ? (string) $url( array( 'page' => $actual - 1 ) ) : '',
			'next'  => $actual < $pages ? (string) $url( array( 'page' => $actual + 1 ) ) : '',
			'pages' => array(),
		);
		$antes  = 0;
		for ( $n = 1; $n <= $pages; $n++ ) {
			if ( 1 !== $n && $pages !== $n && abs( $n - $actual ) > 2 ) {
				continue;
			}
			if ( $n - $antes > 1 ) {
				$out['pages'][] = array(
					'n'   => 0,
					'url' => '',
				);
			}
			$out['pages'][] = array(
				'n'   => $n,
				'url' => (string) $url( array( 'page' => $n ) ),
			);
			$antes          = $n;
		}
		return $out;
	}

	/**
	 * What the export dialog shows: the columns, the last choice, how many
	 * would go out and who would be left out of the user upload file.
	 *
	 * La descarga es la respuesta del envío, así que no puede volver con un
	 * aviso: cuántas personas se quedan fuera, y por qué, se cuenta aquí,
	 * antes de descargar (ADR-0039). Con un filtro se cuenta dos veces —las
	 * del filtro y todas—, porque el diálogo deja elegir: `who[clave]` trae
	 * las cifras de todas y, con filtro, `who[clave]['filter']` las de las
	 * del filtro. Las metas ya leídas por el censo bastan para las dos.
	 *
	 * @param int                  $procedure_id Procedure post ID.
	 * @param int                  $user_id      Who is looking.
	 * @param array<string, mixed> $res          What {@see ApplicationQuery::run()} returned.
	 * @return array<string, mixed>
	 */
	private static function export_model( int $procedure_id, int $user_id, array $res ): array {
		$todas = array_values( array_filter( array_map( 'get_post', array_keys( $res['stored'] ) ) ) );
		cache_users( array_unique( array_map( 'intval', wp_list_pluck( $todas, 'post_author' ) ) ) );
		$fuentes = ApplicationExport::who( $procedure_id );
		$quienes = array();
		foreach ( ApplicationExport::who_counts( $todas, array_keys( $fuentes ), $res['stored'] ) as $clave => $cuenta ) {
			$quienes[ $clave ] = array( 'label' => $fuentes[ $clave ] ) + $cuenta;
		}
		if ( $res['active'] ) {
			$del_filtro = array_values( array_filter( array_map( 'get_post', $res['ids'] ) ) );
			foreach ( ApplicationExport::who_counts( $del_filtro, array_keys( $fuentes ), $res['stored'] ) as $clave => $cuenta ) {
				$quienes[ $clave ]['filter'] = $cuenta;
			}
		}
		return array(
			'groups'       => ApplicationExport::groups( $procedure_id ),
			'group_labels' => ApplicationExport::group_labels( $procedure_id ),
			'choice'       => ApplicationExport::choice( $user_id, $procedure_id ),
			'who'          => $quienes,
			'filtered'     => $res['active'],
			'count'        => $res['active'] ? count( $res['ids'] ) : (int) $res['all'],
			'all'          => (int) $res['all'],
			'args'         => ApplicationFilter::query( array( 'page' => 1 ) + $res['filter'] ),
		);
	}

	/**
	 * Which applications an export sends: those of the filter, or all.
	 *
	 * Por defecto, las del filtro si viene alguno: es lo que el diálogo deja
	 * marcado. Sin filtro, o con «todas», todas.
	 *
	 * @param int                  $procedure_id Procedure post ID.
	 * @param array<string, mixed> $post         `$_POST`, still slashed.
	 * @return int[]|null Application IDs; null for every one.
	 */
	public static function export_ids( int $procedure_id, array $post ): ?array {
		$filtro = self::filter( $post );
		$ambito = isset( $post[ self::FIELD_EXPORT_SCOPE ] ) && is_string( $post[ self::FIELD_EXPORT_SCOPE ] ) ? sanitize_key( wp_unslash( $post[ self::FIELD_EXPORT_SCOPE ] ) ) : self::SCOPE_FILTER;
		if ( self::SCOPE_ALL === $ambito || ! ApplicationFilter::active( $filtro ) ) {
			return null;
		}
		return ApplicationQuery::ids( $procedure_id, $filtro );
	}

	// ─── la revisión en bloque ─────────────────────────────────────────────

	/**
	 * The applications a bulk review asks for: the ticked ones and, with
	 * «marcar las de esta página», those of the page.
	 *
	 * @param array<string, mixed> $post `$_POST`, still slashed.
	 * @return int[] Without repeats, in the order sent.
	 */
	public static function posted_ids( array $post ): array {
		$marcadas = isset( $post[ self::FIELD_ROWS ] ) && is_array( $post[ self::FIELD_ROWS ] ) ? $post[ self::FIELD_ROWS ] : array();
		if ( ! empty( $post[ self::FIELD_PAGE ] ) && isset( $post[ self::FIELD_PAGE_ROWS ] ) && is_string( $post[ self::FIELD_PAGE_ROWS ] ) ) {
			$marcadas = array_merge( $marcadas, explode( ',', wp_unslash( $post[ self::FIELD_PAGE_ROWS ] ) ) );
		}
		$ids = array();
		foreach ( $marcadas as $id ) {
			// Un número entero positivo y nada más: `absint()` haría de «-3» un 3.
			$id = is_scalar( $id ) && ctype_digit( trim( (string) $id ) ) ? (int) $id : 0;
			if ( $id > 0 ) {
				$ids[ $id ] = $id;
			}
		}
		return array_values( $ids );
	}

	/**
	 * Review several applications of one procedure with the same state and note.
	 *
	 * Las mismas reglas que una a una: un estado que no existe o la falta de
	 * nota donde es obligatoria rechazan el envío entero, sin tocar ninguna.
	 * Una nota escrita sustituye la de todas; vacía, con un estado que no la
	 * exige, deja la de cada una como estaba.
	 * Cada una cambia por {@see Applications::review()}, que emite
	 * `prc_application_reviewed`: sale su correo como si se hubiera revisado
	 * sola. Una que no es de este procedimiento no se toca y se cuenta; no
	 * impide las demás.
	 *
	 * @param int    $procedure_id Procedure post ID.
	 * @param int[]  $ids          Application IDs, without repeats.
	 * @param string $state        Review state.
	 * @param string $note         Note, common to all.
	 * @return array{error:string, done:int, failed:int} `error` when nothing was changed at all.
	 */
	public static function bulk( int $procedure_id, array $ids, string $state, string $note ): array {
		$out = array(
			'error'  => '',
			'done'   => 0,
			'failed' => 0,
		);
		if ( array() === $ids ) {
			$out['error'] = 'Marque al menos una solicitud para cambiar su revisión.';
			return $out;
		}
		if ( count( $ids ) > self::BULK_MAX ) {
			$out['error'] = sprintf( 'Como mucho se cambian %1$d solicitudes a la vez, y ha marcado %2$d. No se ha cambiado ninguna.', self::BULK_MAX, count( $ids ) );
			return $out;
		}
		if ( ! isset( ApplicationMetaKeys::review_states()[ $state ] ) ) {
			$out['error'] = 'Ese estado de revisión no existe. No se ha cambiado ninguna solicitud.';
			return $out;
		}
		if ( '' === trim( $note ) && in_array( $state, ApplicationMetaKeys::states_needing_note(), true ) ) {
			$out['error'] = 'Para pedir subsanar o para excluir hay que escribir una nota: cada centro tiene que leer qué se le pide o por qué. No se ha cambiado ninguna solicitud.';
			return $out;
		}
		// Vacía y sin hacer falta, la nota de cada una se queda como estaba.
		$nota = '' === trim( $note ) ? null : $note;
		foreach ( $ids as $id ) {
			$suya = ApplicationPostType::POST_TYPE === get_post_type( $id ) && 'publish' === get_post_status( $id ) && (int) wp_get_post_parent_id( $id ) === $procedure_id;
			if ( $suya && Applications::review( $id, $state, $nota )['ok'] ) {
				++$out['done'];
				continue;
			}
			++$out['failed'];
		}
		return $out;
	}

	/**
	 * What the notice of a bulk review says, from the counts in the URL.
	 *
	 * @param array<string, mixed> $request `$_GET`.
	 * @return array{tipo:string, texto:string}
	 */
	public static function bulk_notice( array $request ): array {
		$hechas = isset( $request[ self::ARG_DONE ] ) && is_string( $request[ self::ARG_DONE ] ) ? absint( $request[ self::ARG_DONE ] ) : 0;
		$fallos = isset( $request[ self::ARG_FAILED ] ) && is_string( $request[ self::ARG_FAILED ] ) ? absint( $request[ self::ARG_FAILED ] ) : 0;
		$texto  = 1 === $hechas ? 'Se ha cambiado la revisión de 1 solicitud.' : sprintf( 'Se ha cambiado la revisión de %d solicitudes.', $hechas );
		if ( $fallos > 0 ) {
			$texto .= ' ' . ( 1 === $fallos
				? '1 no se ha podido cambiar (no es de este procedimiento o ya no está publicada).'
				: sprintf( '%d no se han podido cambiar (no son de este procedimiento o ya no están publicadas).', $fallos ) );
		}
		return array(
			'tipo'  => $fallos > 0 ? 'aviso' : 'ok',
			'texto' => $texto,
		);
	}
}
