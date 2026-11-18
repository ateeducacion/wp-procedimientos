<?php
/**
 * The help screen: the guides and the frequently asked questions.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\PublicFront\View\HelpView;
use Prc\PublicFront\View\ProcedureChrome;

/**
 * Shortcode [prc_help]: la ayuda del aplicativo (ADR-0045).
 *
 * Una pantalla más, pública como la portada: quien todavía no ha entrado es
 * quien más la necesita. Tres pestañas —cómo solicitar, cómo dar de alta un
 * procedimiento y las preguntas frecuentes— que son enlaces con su `?guia=`:
 * cada guía tiene su dirección y se lee sin JavaScript. El texto vive en
 * {@see HelpContent}; aquí solo se elige la guía y se arma el modelo.
 *
 * La ayuda técnica —a quién escribir cuando no se puede entrar o algo
 * falla— es de cada instalación y **no está en el código**: sale de la clave
 * `help_contact` del filtro `prc_chrome`, vacía por defecto (ADR-0009).
 */
final class Help {

	public const SHORTCODE = 'prc_help';

	/**
	 * Query arg that picks the guide.
	 */
	public const ARG_GUIDE = 'guia';

	/**
	 * Sections asked for by a help link on this page, waiting for the footer.
	 *
	 * @var array<string, array{guide:string, anchor:string, url:string}>
	 */
	private static array $asked = array();

	/**
	 * Register the shortcode.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'render' ) );
		add_action( 'wp_footer', array( self::class, 'print_dialogs' ) );
	}

	/**
	 * Queue the dialog of the section a help URL points to.
	 *
	 * Cada «¿Dudas?» abre su apartado en un `<dialog>` sin salir de la página
	 * (#86). El diálogo no se pinta junto al enlace sino en el pie, una vez
	 * por apartado aunque lo pidan varios enlaces; el enlace sigue llevando a
	 * la ayuda entera para quien no tiene JavaScript.
	 *
	 * @param string $url A URL from url(), with its guide and its anchor.
	 * @return string Id of the dialog; empty when the URL names no known section.
	 */
	public static function ask( string $url ): string {
		$partes = wp_parse_url( $url );
		$ancla  = (string) ( $partes['fragment'] ?? '' );
		wp_parse_str( (string) ( $partes['query'] ?? '' ), $query );
		$guia = self::known( sanitize_key( (string) ( $query[ self::ARG_GUIDE ] ?? '' ) ) );
		if ( '' === $ancla || ! in_array( $ancla, array_column( HelpContent::raw( $guia ), 'id' ), true ) ) {
			return '';
		}
		$id                 = 'prc-ayuda-' . $guia . '-' . $ancla;
		self::$asked[ $id ] = array(
			'guide'  => $guia,
			'anchor' => $ancla,
			'url'    => $url,
		);
		return $id;
	}

	/**
	 * The queued dialogs, painted, and the queue emptied.
	 *
	 * @return string
	 */
	public static function dialogs(): string {
		$html = '';
		foreach ( self::$asked as $id => $pedido ) {
			foreach ( HelpContent::sections( $pedido['guide'] ) as $apartado ) {
				if ( $apartado['id'] === $pedido['anchor'] ) {
					$html .= HelpView::dialog( $id, $apartado, $pedido['url'] );
				}
			}
		}
		self::$asked = array();
		return $html;
	}

	/**
	 * Print the queued dialogs: hooked to wp_footer.
	 *
	 * @return void
	 */
	public static function print_dialogs(): void {
		echo self::dialogs(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado en HelpView.
	}

	/**
	 * The guide asked for in the URL; the default one when there is none or it is unknown.
	 *
	 * @return string One of HelpContent::GUIDES.
	 */
	public static function guide(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo elige qué se lee: esta pantalla no muta nada.
		$crudo = isset( $_GET[ self::ARG_GUIDE ] ) && is_string( $_GET[ self::ARG_GUIDE ] ) ? sanitize_key( wp_unslash( $_GET[ self::ARG_GUIDE ] ) ) : '';
		return self::known( $crudo );
	}

	/**
	 * A guide key we know, or the default one.
	 *
	 * @param string $guide Guide key.
	 * @return string
	 */
	private static function known( string $guide ): string {
		return isset( HelpContent::GUIDES[ $guide ] ) ? $guide : HelpContent::GUIDE_APPLY;
	}

	/**
	 * URL of one guide and, if given, of one of its sections.
	 *
	 * Es lo que usan las demás pantallas para su «¿Dudas?»: el apartado va en
	 * el ancla, así que el enlace lleva a él y no al principio de la guía.
	 *
	 * @param string $guide  Guide key; an unknown one is the default guide.
	 * @param string $anchor Section id; empty for the top of the guide.
	 * @return string Empty when the help page does not exist.
	 */
	public static function url( string $guide, string $anchor = '' ): string {
		$url = Shell::url( 'help', array( self::ARG_GUIDE => self::known( $guide ) ) );
		if ( '' === $url ) {
			return '';
		}
		$anchor = sanitize_title( $anchor );
		return '' === $anchor ? $url : $url . '#' . $anchor;
	}

	/**
	 * The technical help of this installation, sanitised; empty when nothing is configured.
	 *
	 * Un texto libre de quien despliega: un buzón, un teléfono, un formulario
	 * de incidencias. Pasa por la misma lista corta que el resto de la ayuda,
	 * así que no puede traer guiones ni imágenes.
	 *
	 * @return string
	 */
	public static function support(): string {
		$texto = ProcedureChrome::chrome()['help_contact'] ?? '';
		return is_string( $texto ) ? trim( wp_kses( $texto, HelpContent::ALLOWED ) ) : '';
	}

	/**
	 * Everything the screen decides before painting.
	 *
	 * @return array<string, mixed>
	 */
	public static function model(): array {
		$guia     = self::guide();
		$pestanas = array();
		foreach ( HelpContent::GUIDES as $clave => $datos ) {
			$pestanas[ $clave ] = array(
				'label'  => $datos['label'],
				'url'    => self::url( $clave ),
				'active' => $clave === $guia,
			);
		}

		return array(
			'guide'    => $guia,
			'label'    => HelpContent::GUIDES[ $guia ]['label'],
			'intro'    => HelpContent::GUIDES[ $guia ]['intro'],
			'tabs'     => $pestanas,
			'sections' => HelpContent::sections( $guia ),
			'support'  => self::support(),
		);
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
		return HelpView::html( self::model() );
	}
}
