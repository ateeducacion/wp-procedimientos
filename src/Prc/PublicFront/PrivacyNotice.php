<?php
/**
 * The data protection notice of the application screen: where it comes from and how it is cleaned.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\PublicFront\View\ProcedureChrome;

/**
 * El aviso de protección de datos de la solicitud.
 *
 * **El texto es de quien despliega** (ADR-0009, adenda): lo escribe
 * administración en Ajustes y, si el filtro del armazón trae uno, manda él.
 * La opción se lee aquí, solo para la pantalla de solicitud, y no en
 * {@see ProcedureChrome::chrome()}, que corre en todas.
 */
final class PrivacyNotice {

	/**
	 * Option with the notice, written in the settings.
	 */
	public const OPTION = 'prc_privacy_notice';

	/**
	 * Tags the notice may never carry: it sits inside the application form.
	 *
	 * @var string[]
	 */
	private const FORBIDDEN = array( 'form', 'button', 'input', 'select', 'option', 'textarea', 'label', 'fieldset' );

	/**
	 * The notice of this installation, as written: the filter first, then the setting.
	 *
	 * @return string
	 */
	public static function text(): string {
		$filtered = trim( (string) ( ProcedureChrome::chrome()['privacy_notice'] ?? '' ) );
		return '' !== $filtered ? $filtered : trim( (string) get_option( self::OPTION, '' ) );
	}

	/**
	 * Sanitize the notice: post content without form controls, tags balanced.
	 *
	 * Va dentro del formulario de la solicitud: un `<form>`, un botón o una
	 * etiqueta sin cerrar lo romperían. Se aplica al guardar y al pintar.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize( $value ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}
		// kses quita la etiqueta pero deja su contenido: el de un guion o una
		// hoja de estilo no es texto, se va entero (también sin cerrar).
		$value   = (string) preg_replace( '#<(script|style)\b[^>]*>.*?(?:</\1\s*>|$)#is', '', $value );
		$allowed = array_diff_key( wp_kses_allowed_html( 'post' ), array_flip( self::FORBIDDEN ) );
		return trim( force_balance_tags( wp_kses( $value, $allowed ) ) );
	}

	/**
	 * The notice to paint after the sections, when the application form is closed.
	 *
	 * La lista de personas y las entregas tienen su propia ventana: con el
	 * formulario cerrado siguen pidiendo datos, y el aviso va con ellas. Con
	 * el formulario abierto, el aviso ya va dentro, antes de enviar.
	 *
	 * @param array<string, mixed> $m What ApplyForm::render() put together.
	 * @return string
	 */
	public static function after_sections( array $m ): string {
		if ( true === ( $m['can_submit'] ?? false ) ) {
			return '';
		}
		$people = (array) ( $m['people'] ?? array() );
		$open   = array_filter( array_column( (array) ( $m['deliveries'] ?? array() ), 'can_submit' ) );
		if ( array() === $open && ( empty( $people['show'] ) || empty( $people['can_edit'] ) ) ) {
			return '';
		}
		return (string) ( $m['privacy'] ?? '' );
	}
}
