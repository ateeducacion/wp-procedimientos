<?php
/**
 * A failing field, marked where it is and linked from the notice on top.
 *
 * @package Prc
 */

namespace Prc\PublicFront\View;

use Prc\PublicFront\Shell;

/**
 * Solo pinta: recibe qué campos fallan y cómo se nombran, y lo dice en su sitio.
 *
 * El servidor ya sabe qué campo falla ({@see \Prc\Domain\ApplicationInput::labels()});
 * lo que faltaba era decirlo **en el campo**: `aria-invalid`, la clase
 * `is-invalid` de Bootstrap y una línea de error enlazada con
 * `aria-describedby`, y arriba un aviso con un enlace a cada uno. Funciona sin
 * JavaScript: los enlaces son anclas.
 *
 * Las claves son los códigos de campo de la validación (`position`, `accept`,
 * `answer:q3`, `coordinator_dni:1`); el `id` del control sale de aquí, y es el
 * mismo que pintan las vistas.
 */
final class FieldErrorView {

	/**
	 * The id of the control a field code points at.
	 *
	 * @param string $code Field code.
	 * @return string
	 */
	public static function anchor( string $code ): string {
		$partes = explode( ':', $code, 2 );
		if ( 'answer' === $partes[0] ) {
			return 'prc-q-' . ( $partes[1] ?? '' );
		}
		if ( 0 === strpos( $partes[0], 'coordinator_' ) ) {
			return 'prc-coordinator-' . substr( $partes[0], strlen( 'coordinator_' ) ) . '-' . (int) ( $partes[1] ?? 0 );
		}
		return 'prc-' . $partes[0];
	}

	/**
	 * The ARIA attributes of a control: its help, its error, and whether it fails.
	 *
	 * @param array<string, string> $invalid Field code => what names it.
	 * @param string                $code    Field code of this control.
	 * @param string                $help_id Id of its help line; empty for none.
	 * @return string Attributes, already escaped, with a leading space; empty for none.
	 */
	public static function attrs( array $invalid, string $code, string $help_id = '' ): string {
		$falla  = isset( $invalid[ $code ] );
		$quien  = trim( $help_id . ( $falla ? ' ' . self::anchor( $code ) . '-error' : '' ) );
		$atribs = '' !== $quien ? ' aria-describedby="' . esc_attr( $quien ) . '"' : '';
		return $atribs . ( $falla ? ' aria-invalid="true"' : '' );
	}

	/**
	 * The extra class of a failing control.
	 *
	 * @param array<string, string> $invalid Field code => what names it.
	 * @param string                $code    Field code of this control.
	 * @return string With a leading space; empty when it does not fail.
	 */
	public static function css( array $invalid, string $code ): string {
		return isset( $invalid[ $code ] ) ? ' is-invalid' : '';
	}

	/**
	 * The error line under a failing control.
	 *
	 * @param array<string, string> $invalid Field code => what names it.
	 * @param string                $code    Field code of this control.
	 * @return string
	 */
	public static function feedback( array $invalid, string $code ): string {
		if ( ! isset( $invalid[ $code ] ) ) {
			return '';
		}
		return '<p class="invalid-feedback d-block" id="' . esc_attr( self::anchor( $code ) . '-error' ) . '">'
			. esc_html( 'Revise ' . $invalid[ $code ] . '.' ) . '</p>';
	}

	/**
	 * The notice on top: what failed, with a link to each field.
	 *
	 * @param string                $intro   What happened, in one sentence.
	 * @param array<string, string> $invalid Field code => what names it.
	 * @return string
	 */
	public static function summary( string $intro, array $invalid ): string {
		$items = '';
		foreach ( $invalid as $code => $rotulo ) {
			$items .= '<li><a href="#' . esc_attr( self::anchor( (string) $code ) ) . '">' . esc_html( (string) $rotulo ) . '</a></li>';
		}
		return Shell::notice_open( 'error', true, 'div' )
			. '<p>' . esc_html( $intro ) . '</p><ul class="prc-aviso__campos">' . $items . '</ul></div>';
	}
}
