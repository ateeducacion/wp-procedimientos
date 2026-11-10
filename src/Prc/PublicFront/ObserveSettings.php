<?php
/**
 * The «Consulta» panel of the workshop: whether a procedure admits the
 * read-only consultation by zone, and what it shows.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\Access\ObserverScope;
use Prc\Meta\ProcedureMetaKeys;

/**
 * Lo que el procedimiento decide de su consulta de solo lectura (ADR-0043).
 *
 * Va aparte de {@see ProcedureEditor} para que no pase del umbral de PHPMD.
 * No comprueba ni el nonce ni quién pregunta: eso lo hace
 * {@see ProcedureEditor::handle()} con `ProcedureAccess::can_edit()` antes de
 * llamar a {@see handle()}. Tampoco pinta: es de {@see View\ObserveView::settings()}.
 *
 * Todo apagado por defecto: un procedimiento no se enseña a nadie de fuera de
 * su ámbito hasta que quien lo gestiona lo decide.
 */
final class ObserveSettings {

	/** Checkbox: the procedure admits the consultation. */
	public const FIELD_OBSERVABLE = 'prc_observable';

	/** Checkboxes: the columns it shows. */
	public const FIELD_COLUMNS = 'prc_observer_columns';

	/** Checkbox: who observes downloads the documents. */
	public const FIELD_FILES = 'prc_observer_files';

	/** Checkbox: who observes exports what they see. */
	public const FIELD_EXPORT = 'prc_observer_export';

	/** Checkbox: the people mail goes also to who observes the zone. */
	public const FIELD_NOTIFY = 'prc_observer_notify';

	/**
	 * The columns one may offer, grouped, without what a consultation never shows.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return array<string, array<string, string>> group => (column => heading).
	 */
	public static function offered( int $procedure_id ): array {
		return ObserverScope::allowed( ApplicationExport::groups( $procedure_id ) );
	}

	/**
	 * The columns the consultation of a procedure shows, in order.
	 *
	 * Las elegidas que existen hoy y se pueden enseñar; sin ninguna, las de
	 * solicitud, centro y coordinación. Nunca el DNI ni la nota: se quitan
	 * aquí aunque estuvieran escritos en la meta.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return array<string, string> Column => heading.
	 */
	public static function columns( int $procedure_id ): array {
		return ObserverScope::columns( get_post_meta( $procedure_id, ProcedureMetaKeys::OBSERVER_COLUMNS, true ), ApplicationExport::groups( $procedure_id ) );
	}

	/**
	 * What the panel paints.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return array{observable:bool, columns:string[], groups:array<string, array<string, string>>, group_labels:array<string, string>, files:bool, export:bool, notify:bool, url:string}
	 */
	public static function model( int $procedure_id ): array {
		$leer = static function ( string $clave ) use ( $procedure_id ): bool {
			return (bool) get_post_meta( $procedure_id, $clave, true );
		};
		return array(
			'observable'   => $leer( ProcedureMetaKeys::OBSERVABLE ),
			'columns'      => array_keys( self::columns( $procedure_id ) ),
			'groups'       => self::offered( $procedure_id ),
			'group_labels' => ApplicationExport::group_labels( $procedure_id ),
			'files'        => $leer( ProcedureMetaKeys::OBSERVER_FILES ),
			'export'       => $leer( ProcedureMetaKeys::OBSERVER_EXPORT ),
			'notify'       => $leer( ProcedureMetaKeys::OBSERVER_NOTIFY ),
			'url'          => Observe::url( $procedure_id ),
		);
	}

	/**
	 * Save the panel and go back to it.
	 *
	 * Lo llama {@see ProcedureEditor::handle()} después de comprobar el nonce,
	 * el bloqueo y `ProcedureAccess::can_edit()`.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return void
	 */
	public static function handle( int $procedure_id ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- ProcedureEditor::handle() comprobó el nonce; save() solo guarda columnas ofrecidas y casillas.
		self::save( $procedure_id, $_POST );
		Shell::leave( ProcedureEditor::url( $procedure_id, ProcedureEditor::PANEL_OBSERVE, array( ProcedureEditor::ARG_NOTICE => 'consulta' ) ) );
	}

	/**
	 * Save what the panel sent.
	 *
	 * Solo las columnas que el procedimiento ofrece hoy; lo demás se cae. El
	 * registro de la meta quita además el DNI y la nota por cualquier camino.
	 *
	 * @param int                  $procedure_id Procedure post ID.
	 * @param array<string, mixed> $post         `$_POST`, still slashed.
	 * @return void
	 */
	public static function save( int $procedure_id, array $post ): void {
		$ofrecidas = array();
		foreach ( self::offered( $procedure_id ) as $columnas ) {
			$ofrecidas += $columnas;
		}
		$pedidas  = isset( $post[ self::FIELD_COLUMNS ] ) && is_array( $post[ self::FIELD_COLUMNS ] ) ? wp_unslash( $post[ self::FIELD_COLUMNS ] ) : array();
		$columnas = array();
		foreach ( $pedidas as $clave ) {
			$clave = is_string( $clave ) ? sanitize_text_field( $clave ) : '';
			if ( isset( $ofrecidas[ $clave ] ) ) {
				$columnas[] = $clave;
			}
		}
		update_post_meta( $procedure_id, ProcedureMetaKeys::OBSERVER_COLUMNS, $columnas );
		foreach ( array(
			self::FIELD_OBSERVABLE => ProcedureMetaKeys::OBSERVABLE,
			self::FIELD_FILES      => ProcedureMetaKeys::OBSERVER_FILES,
			self::FIELD_EXPORT     => ProcedureMetaKeys::OBSERVER_EXPORT,
			self::FIELD_NOTIFY     => ProcedureMetaKeys::OBSERVER_NOTIFY,
		) as $campo => $clave ) {
			update_post_meta( $procedure_id, $clave, ! empty( $post[ $campo ] ) );
		}
	}
}
