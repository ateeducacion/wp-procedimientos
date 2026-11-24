<?php
/**
 * The «Avanzado» tab of the workshop: which sections it has, what it saves and where the old tabs went.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\Access\ProcedureAccess;
use Prc\Domain\CentreEligibility;
use Prc\Domain\ProcedureInput;
use Prc\Meta\ProcedureMetaKeys;

/**
 * «Avanzado» reúne en secciones lo que rara vez se toca (#80): qué centros
 * pueden solicitar, «Acceso de consulta» y «Código». Las pestañas que eran
 * —y «Entregas», que ahora va al final de «Solicitudes»— redirigen aquí.
 *
 * El taller sigue siendo {@see ProcedureEditor}: aquí solo vive lo propio
 * de esta pestaña, para no engordarlo.
 */
final class AdvancedPanel {

	/** Operation of «Firma electrónica» (ADR-0049). */
	public const OP_SIGNATURE = 'signature';

	/** Anchor of «Firma electrónica». */
	public const SECTION_SIGNATURE = 'prc-avanzado-firma';

	/**
	 * The sections of «Avanzado» this person sees, in order.
	 *
	 * Qué centros pueden solicitar, solo en un procedimiento para centros;
	 * «Firma electrónica», siempre (ADR-0049); «Acceso de consulta», solo para administración
	 * (`ProcedureAccess::can_configure_observe()`); «Código», siempre: quien
	 * no lo edita lo lee.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return string[] Among `centres`, `signature`, `observe` and `code`.
	 */
	public static function sections( int $procedure_id ): array {
		return array_keys(
			array_filter(
				array(
					'centres'   => self::for_schools( $procedure_id ),
					'signature' => true,
					'observe'   => ProcedureAccess::can_configure_observe( get_current_user_id(), $procedure_id ),
					'code'      => true,
				)
			)
		);
	}

	/**
	 * Whether the stored audience of the procedure is schools.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return bool
	 */
	private static function for_schools( int $procedure_id ): bool {
		return ProcedureMetaKeys::AUDIENCE_SCHOOLS === ProcedureMetaKeys::in_list( (string) get_post_meta( $procedure_id, ProcedureMetaKeys::AUDIENCE, true ), ProcedureMetaKeys::audiences(), ProcedureMetaKeys::AUDIENCE_SCHOOLS );
	}

	/**
	 * Save «Qué centros pueden solicitar», a section of «Avanzado».
	 *
	 * Validated with {@see CentreEligibility::input()} against the stored
	 * audience, as when it lived in «Datos».
	 *
	 * Lo llama {@see ProcedureEditor::handle()} después de comprobar el
	 * nonce, el bloqueo y `ProcedureAccess::can_edit()`.
	 *
	 * @param int                  $procedure_id Procedure post ID.
	 * @param array<string, mixed> $raw          What the form sent, as ProcedureEditor reads it.
	 * @return string Why it was refused; it does not return when it saves.
	 */
	public static function save_centres( int $procedure_id, array $raw ): string {
		$acotados = CentreEligibility::input( $raw, self::for_schools( $procedure_id ) );
		if ( array() !== $acotados['errors'] ) {
			return ProcedureInput::why( $acotados['errors'] );
		}
		update_post_meta( $procedure_id, ProcedureMetaKeys::CENTRE_TYPES, wp_slash( $acotados['data']['centre_types'] ) );
		update_post_meta( $procedure_id, ProcedureMetaKeys::ISLANDS, wp_slash( $acotados['data']['islands'] ) );
		update_post_meta( $procedure_id, ProcedureMetaKeys::ALLOWED_CENTRES, wp_slash( $acotados['data']['allowed_centres'] ) );
		Shell::leave( ProcedureEditor::url( $procedure_id, ProcedureEditor::PANEL_ADVANCED, array( ProcedureEditor::ARG_NOTICE => 'centros' ) ) );
		return '';
	}

	/**
	 * Save «Firma electrónica»: whether the procedure asks for the signed receipt.
	 *
	 * En cualquier estado mientras el procedimiento se pueda editar: lo llama
	 * {@see ProcedureEditor::handle()} después de comprobar el nonce, el
	 * bloqueo y `ProcedureAccess::can_edit()`.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return void
	 */
	public static function save_signature( int $procedure_id ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- handle() comprobó el nonce de esta operación.
		update_post_meta( $procedure_id, ProcedureMetaKeys::SIGNATURE_REQUIRED, ! empty( $_POST[ ProcedureMetaKeys::SIGNATURE_REQUIRED ] ) );
		Shell::leave( ProcedureEditor::url( $procedure_id, ProcedureEditor::PANEL_ADVANCED, array( ProcedureEditor::ARG_NOTICE => 'firma' ) ) . '#' . self::SECTION_SIGNATURE );
	}

	/**
	 * Send a tab that no longer exists to the one that holds it now.
	 *
	 * El resto de la dirección se queda —el procedimiento, el aviso—, vuelto a
	 * codificar, y se añade el ancla de la sección. Un envío no se redirige:
	 * si se rechazó, la misma petición repinta lo tecleado y el motivo.
	 *
	 * @return void
	 */
	public static function redirect_old_panel(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo lee a qué pestaña se va.
		$pedido = sanitize_key( wp_unslash( (string) ( $_GET[ ProcedureEditor::ARG_PANEL ] ?? '' ) ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- solo mira si hay un envío.
		if ( ! empty( $_POST ) || ! isset( ProcedureEditor::OLD_PANELS[ $pedido ] ) || 'editor' !== Shell::current_section() ) {
			return;
		}
		list( $panel, $seccion ) = ProcedureEditor::OLD_PANELS[ $pedido ];
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- se reenvía la misma lectura a otra dirección.
		$args = array_map( 'sanitize_text_field', array_filter( wp_unslash( $_GET ), 'is_string' ) );
		unset( $args[ ProcedureEditor::ARG_PANEL ] );
		// add_query_arg() no codifica: un «&» dentro de un valor no puede colar otro parámetro.
		$args = array_map( 'rawurlencode', $args );
		Shell::leave( ProcedureEditor::url( 0, $panel, $args ) . ( '' !== $seccion ? '#' . $seccion : '' ) );
	}

	/** Enqueue WordPress CodeMirror before the page head, only on «Avanzado». */
	public static function enqueue_code_editor(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- lectura de la pestaña; mutar lleva su nonce.
		if ( 'editor' !== Shell::current_section() || ProcedureEditor::PANEL_ADVANCED !== sanitize_key( wp_unslash( (string) ( $_GET[ ProcedureEditor::ARG_PANEL ] ?? '' ) ) ) ) {
			return;
		}
		$procedure_id = absint( wp_unslash( $_GET[ ProcedureEditor::ARG_PROCEDURE ] ?? 0 ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$user_id = get_current_user_id();
		if ( ProcedureAccess::can_edit( $user_id, $procedure_id ) ) {
			$settings = wp_enqueue_code_editor( array( 'type' => 'text/css' ) );
			if ( is_array( $settings ) ) {
				wp_add_inline_script( 'code-editor', sprintf( 'jQuery(function(){wp.codeEditor.initialize("prc-custom-css",%s);});', wp_json_encode( $settings, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES ) ), 'after' );
			}
		}
		if ( ProcedureAccess::can_edit_custom_js( $user_id, $procedure_id ) ) {
			$settings = wp_enqueue_code_editor( array( 'type' => 'text/javascript' ) );
			if ( is_array( $settings ) ) {
				wp_add_inline_script( 'code-editor', sprintf( 'jQuery(function(){wp.codeEditor.initialize("prc-custom-js",%s);});', wp_json_encode( $settings, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES ) ), 'after' );
			}
		}
	}
}
