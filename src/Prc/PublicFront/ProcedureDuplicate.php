<?php
/**
 * Duplicate a procedure into a new draft (PR 8 del PLAN-0003).
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\Access\ProcedureAccess;
use Prc\Domain\ProcedureCopy;
use Prc\PostType\ProcedurePostType;
use Prc\Taxonomy\ProcedureTaxonomies;

/**
 * La operación `duplicate` del taller: otro borrador como el original.
 *
 * Qué se lleva el duplicado lo decide {@see ProcedureCopy}; aquí solo se lee
 * el original, se escribe el borrador y se cuenta lo que pasó. El original
 * no se escribe nunca, así que duplicar no pide su bloqueo de edición y vale
 * en cualquier estado, histórico incluido. Es un alta: emite
 * `prc_procedure_created` y sale el correo del alta (ADR-0038).
 */
final class ProcedureDuplicate {

	/**
	 * Notice code in the URL after duplicating.
	 */
	public const NOTICE = 'duplicado';

	/**
	 * Query arg with the original, to name it in the notice.
	 */
	public const ARG_SOURCE = 'origen';

	/**
	 * Query arg that says the original's JavaScript was left behind.
	 */
	public const ARG_NO_JS = 'sin_js';

	/**
	 * Duplicate and open the duplicate's workshop on «Datos».
	 *
	 * @param int $procedure_id Original procedure ID.
	 * @param int $user_id      Who duplicates.
	 * @return string Why it was refused; on success it leaves and returns nothing.
	 */
	public static function handle( int $procedure_id, int $user_id ): string {
		$copia = self::duplicate( $procedure_id, $user_id );
		if ( is_wp_error( $copia ) ) {
			return $copia->get_error_message();
		}
		$args = array(
			ProcedureEditor::ARG_NOTICE => self::NOTICE,
			self::ARG_SOURCE            => $procedure_id,
		);
		if ( $copia['js_left'] ) {
			$args[ self::ARG_NO_JS ] = 1;
		}
		Shell::leave( ProcedureEditor::url( $copia['id'], ProcedureEditor::PANEL_DATA, $args ) );
		return '';
	}

	/**
	 * Write the duplicate: a draft of whoever duplicates, without a course.
	 *
	 * @param int $procedure_id Original procedure ID.
	 * @param int $user_id      Who duplicates.
	 * @return array{id:int, js_left:bool}|\WP_Error
	 */
	public static function duplicate( int $procedure_id, int $user_id ) {
		$areas = null;
		if ( ! ProcedureAccess::can_duplicate( $user_id, $procedure_id, $areas ) ) {
			return new \WP_Error(
				'prc_duplicate_forbidden',
				ProcedureAccess::can_open( $user_id, $procedure_id )
					? 'Su perfil no puede crear procedimientos: hace falta gestionar procedimientos y tener un ámbito asignado.'
					: ProcedureAccess::why_not_editable( $user_id, $procedure_id )
			);
		}

		if ( array() === $areas ) {
			return new \WP_Error( 'prc_duplicate_area', 'El duplicado necesita al menos un ámbito, y ni el original ni su perfil tienen uno que pueda usar.' );
		}

		$original = get_post( $procedure_id );
		$id       = wp_insert_post(
			array(
				'post_type'    => ProcedurePostType::POST_TYPE,
				'post_title'   => wp_slash( ProcedureCopy::title( (string) $original->post_title ) ),
				'post_content' => wp_slash( (string) $original->post_content ),
				'post_excerpt' => wp_slash( (string) $original->post_excerpt ),
				'post_status'  => 'draft',
				'post_author'  => $user_id,
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			return new \WP_Error( 'prc_duplicate_failed', 'No se ha podido duplicar el procedimiento: ' . $id->get_error_message() );
		}
		$id = (int) $id;

		// Sin curso: es lo primero que hay que elegir para la convocatoria nueva.
		wp_set_object_terms( $id, $areas, ProcedureTaxonomies::AREA, false );
		$copia = ProcedureCopy::meta( self::source( $procedure_id ), ProcedureAccess::can_edit_custom_js( $user_id, $procedure_id ) );
		foreach ( $copia['meta'] as $clave => $valor ) {
			update_post_meta( $id, $clave, wp_slash( $valor ) );
		}
		$imagen = (int) get_post_thumbnail_id( $procedure_id );
		if ( $imagen > 0 ) {
			update_post_meta( $id, '_thumbnail_id', $imagen );
		}

		/** This action is documented in src/Prc/PublicFront/ProcedureEditor.php */
		do_action( 'prc_procedure_created', $id );

		return array(
			'id'      => $id,
			'js_left' => $copia['js_left'],
		);
	}

	/**
	 * The meta of the original that exists, of the keys the copy rules know.
	 *
	 * @param int $procedure_id Original procedure ID.
	 * @return array<string, mixed>
	 */
	private static function source( int $procedure_id ): array {
		$out = array();
		foreach ( array_keys( ProcedureCopy::policy() ) as $clave ) {
			if ( metadata_exists( 'post', $procedure_id, $clave ) ) {
				$out[ $clave ] = get_post_meta( $procedure_id, $clave, true );
			}
		}
		return $out;
	}

	/**
	 * What the workshop says after duplicating.
	 *
	 * El original se nombra solo si quien mira todavía puede abrirlo.
	 *
	 * @param array<string, mixed> $request The query args of the request.
	 * @return array{tipo:string, texto:string}
	 */
	public static function notice( array $request ): array {
		$origen = absint( $request[ self::ARG_SOURCE ] ?? 0 );
		$texto  = ProcedureAccess::can_open( get_current_user_id(), $origen )
			? 'Has duplicado «' . get_post_field( 'post_title', $origen, 'raw' ) . '».'
			: 'Has duplicado el procedimiento.';
		$texto .= ' Revisa las fechas y el curso antes de publicar.'
			. ' No se copian los plazos, el curso, los enlaces de la resolución y de los listados, ni las solicitudes.';
		if ( ! empty( $request[ self::ARG_NO_JS ] ) ) {
			$texto .= ' El JavaScript propio del original no se ha copiado: solo lo copia quien administra el aplicativo.';
		}
		return array(
			'tipo'  => 'ok',
			'texto' => $texto,
		);
	}
}
