<?php
/**
 * Pieces the screens share: icon links, row actions and a day written out.
 *
 * @package Prc
 */

namespace Prc\PublicFront\View;

use Prc\PublicFront\Assets;
use Prc\PublicFront\Shell;

/**
 * Lo que repiten el listado del taller y los paneles del editor.
 *
 * Está aparte para que el botón de icono de todas las pantallas sea **el
 * mismo botón**: mismo nonce por fila, misma confirmación y misma vuelta a
 * la pestaña desde la que se pulsó.
 *
 * Solo pinta: no lee la petición, no consulta, no decide.
 */
final class PanelParts {

	/**
	 * What `wp_kses()` lets through for an inline icon.
	 *
	 * Los iconos los escribe {@see Shell::icon()} y no vienen de fuera, pero
	 * pasan por `wp_kses()` igual: es una lista corta y deja la regla de «toda
	 * la salida escapada» sin excepciones que alguien tenga que recordar.
	 *
	 * @var array<string, array<string, bool>>
	 */
	public const SVG = array(
		'svg'  => array(
			'viewbox'     => true,
			'width'       => true,
			'height'      => true,
			'aria-hidden' => true,
			'focusable'   => true,
		),
		'path' => array(
			'fill' => true,
			'd'    => true,
		),
	);

	/**
	 * An icon link, with the same shape as the icon buttons.
	 *
	 * @param string $url    Where it goes.
	 * @param string $icono  Icon name for {@see Shell::icon()}.
	 * @param string $titulo What it does, in Spanish.
	 * @param string $clases Extra classes.
	 * @return string Empty when there is nowhere to go.
	 */
	public static function icon_link( string $url, string $icono, string $titulo, string $clases = '' ): string {
		if ( '' === $url ) {
			return '';
		}
		$clases = trim( Assets::button_class() . ' prc-mini prc-icono ' . $clases );

		return '<a class="' . esc_attr( $clases ) . '" href="' . esc_url( $url ) . '"'
			. ' title="' . esc_attr( $titulo ) . '" data-bs-toggle="tooltip">'
			. wp_kses( Shell::icon( $icono ), self::SVG )
			. '<span class="screen-reader-text">' . esc_html( $titulo ) . '</span></a>';
	}

	/**
	 * One section of a panel: its beige band, and what it holds.
	 *
	 * La banda es el rótulo del bloque, y va en `<legend>` para que sea
	 * también el nombre accesible del grupo de campos. Un bloque que el
	 * estado cierra se pinta **apagado y con el motivo escrito**: esconderlo
	 * dejaría la pantalla distinta según el día sin decir por qué.
	 *
	 * @param string $rotulo    Section label, in normal capitalisation.
	 * @param string $contenido Already escaped markup of the fields.
	 * @param bool   $apagado   Whether the state closes this block.
	 * @param string $motivo    Why it is closed, in Spanish.
	 * @return string
	 */
	public static function section( string $rotulo, string $contenido, bool $apagado = false, string $motivo = '' ): string {
		$aviso = $apagado && '' !== $motivo
			? '<p class="prc-seccion__bloqueo">' . esc_html( $motivo ) . '</p>'
			: '';

		return '<fieldset class="prc-seccion-campos"' . ( $apagado ? ' disabled' : '' ) . '>'
			. '<legend class="prc-seccion__titulo">' . esc_html( $rotulo ) . '</legend>'
			. $aviso . $contenido
			. '</fieldset>';
	}

	/**
	 * The submit button at the foot of a panel.
	 *
	 * @param string $rotulo What it does, in Spanish.
	 * @return string
	 */
	public static function submit( string $rotulo ): string {
		return '<p class="prc-acciones prc-acciones--pie">'
			. '<button class="' . esc_attr( Assets::button_class( true ) . ' prc-envio' ) . '" type="submit">'
			. esc_html( $rotulo ) . '</button></p>';
	}

	/**
	 * A day written the way it is read out loud.
	 *
	 * @param string $fecha Date in Y-m-d, or empty.
	 * @return string
	 */
	public static function day( string $fecha ): string {
		if ( '' === $fecha ) {
			return 'Sin fecha';
		}
		$marca = strtotime( $fecha . ' 12:00:00' );
		if ( false === $marca ) {
			return $fecha;
		}
		return (string) wp_date( 'j \d\e F \d\e Y', $marca );
	}
}
