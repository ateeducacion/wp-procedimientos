<?php
/**
 * Snippet Name: PRC — Bootstrap 5 en las pantallas de procedimientos
 * Description: Carga Bootstrap 5 y sus iconos solo en las pantallas del aplicativo de procedimientos y en las páginas de procedimiento, y quita de ellas el Bootstrap 4 que el subsitio carga en todas. El resto del subsitio se queda con su Bootstrap 4 y no se le toca el aspecto a nadie. Sin esto el aplicativo sigue funcionando con su propia piel (`prc-sin-bootstrap`).
 * Scope: front-end
 * Priority: 20
 *
 * @package Prc
 */

// Code Snippets evalúa esto, no lo incluye como fichero, así que aquí no hay
// «acceso directo» que valga. La guarda va igual porque no cuesta nada y porque
// el día que este código acabe en un fichero servido —una copia, un envoltorio,
// una carpeta de plugins— la diferencia entre volcar el código y no volcarlo es
// esta línea.
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'prc_bootstrap5_versions' ) ) {
	/**
	 * Pinned vendor versions and their subresource integrity hashes.
	 *
	 * Los hashes se calculan pidiendo el fichero al mismo CDN que lo va a
	 * servir —jsDelivr minifica al vuelo, así que el hash del paquete no tiene
	 * por qué coincidir—:
	 *
	 *   curl -s <url> | openssl dgst -sha384 -binary | openssl base64 -A
	 *
	 * Calculados el 2026-09-15. La versión está clavada en tres sitios a la
	 * vez: aquí, en la URL y en `package.json`. Si se sube una, se suben las
	 * tres o el SRI deja de cuadrar.
	 *
	 * @return array<string, array{url:string, ver:string, sri:string}>
	 */
	function prc_bootstrap5_versions(): array {
		return array(
			'bootstrap-css'   => array(
				'url' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css',
				'ver' => '5.3.8',
				'sri' => 'sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB',
			),
			'bootstrap-js'    => array(
				'url' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js',
				'ver' => '5.3.8',
				'sri' => 'sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI',
			),
			'bootstrap-icons' => array(
				'url' => 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css',
				'ver' => '1.13.1',
				'sri' => 'sha384-CK2SzKma4jA5H/MXDUU7i1TqZlCFaD4T01vtyDFvPlD97JQyS+IsSh1nI2EFbpyk',
			),
		);
	}
}

if ( ! function_exists( 'prc_is_bootstrap5_page' ) ) {
	/**
	 * Whether the page being viewed is ours, and therefore wants Bootstrap 5.
	 *
	 * Nuestras son las pantallas del aplicativo y las fichas del procedimiento,
	 * y quién es quién lo dice `Shell::current_section()`: una sola lista, y no
	 * una copia aquí que mañana no coincida con ella. Cualquier otra página del
	 * subsitio se queda con su Bootstrap 4.
	 *
	 * @return bool
	 */
	function prc_is_bootstrap5_page(): bool {
		return class_exists( '\\Prc\\PublicFront\\Shell' ) && \Prc\PublicFront\Shell::is_app_page();
	}
}

if ( ! function_exists( 'prc_bootstrap5_assets' ) ) {
	/**
	 * Swap the site-wide Bootstrap 4 for Bootstrap 5 on our own pages.
	 *
	 * El subsitio carga Bootstrap 4.5.2 desde stackpath en todas las páginas,
	 * con los handles `bootstrap-css` y `bootstrap-js`. Las dos versiones a la
	 * vez en la misma página se pisan, así que aquí se quita la 4 y se pone la
	 * 5 —solo en las nuestras—.
	 *
	 * Prioridad 20: después de los snippets que encolan a la prioridad normal.
	 *
	 * @return void
	 */
	function prc_bootstrap5_assets(): void {
		if ( ! prc_is_bootstrap5_page() ) {
			return;
		}

		/**
		 * Permite desactivar el cambio sin desactivar el snippet.
		 *
		 * @param bool $cargar Si se cambia Bootstrap 4 por 5.
		 */
		if ( ! apply_filters( 'prc_load_bootstrap5', true ) ) {
			return;
		}

		// Handles del snippet que carga Bootstrap 4 en todo el subsitio. Se
		// deregistran, no solo se sacan de la cola: los nombres son los mismos
		// que vamos a usar, y un handle ya registrado gana al nuevo.
		foreach ( array( 'bootstrap-css', 'bootstrap-js', 'popper-js' ) as $viejo ) {
			wp_dequeue_style( $viejo );
			wp_deregister_style( $viejo );
			wp_dequeue_script( $viejo );
			wp_deregister_script( $viejo );
		}

		$vendors = prc_bootstrap5_versions();

		wp_enqueue_style( 'bootstrap-css', $vendors['bootstrap-css']['url'], array(), $vendors['bootstrap-css']['ver'] );
		wp_enqueue_style( 'bootstrap-icons', $vendors['bootstrap-icons']['url'], array(), $vendors['bootstrap-icons']['ver'] );
		wp_enqueue_script( 'bootstrap-js', $vendors['bootstrap-js']['url'], array(), $vendors['bootstrap-js']['ver'], true );

		add_filter( 'style_loader_tag', 'prc_bootstrap5_sri', 10, 3 );
		add_filter( 'script_loader_tag', 'prc_bootstrap5_sri', 10, 3 );

		// La verdad la pone quien la conoce: el aplicativo pregunta por este
		// filtro y ya no adivina por el nombre del handle, que no dice qué
		// versión es.
		add_filter( 'prc_has_bootstrap', '__return_true' );
	}
}

if ( ! function_exists( 'prc_bootstrap5_sri' ) ) {
	/**
	 * Add integrity and crossorigin to our vendor tags.
	 *
	 * Solo si la URL sigue siendo la del CDN: en desarrollo el mu-plugin la
	 * reescribe a `node_modules` y entonces el SRI ni cuadra ni hace falta.
	 *
	 * @param string $tag    The tag WordPress is about to print.
	 * @param string $handle Its handle.
	 * @param string $src    Its URL.
	 * @return string
	 */
	function prc_bootstrap5_sri( $tag, $handle, $src ) {
		$vendors = prc_bootstrap5_versions();
		if ( ! isset( $vendors[ $handle ] ) || 0 !== strpos( (string) $src, 'https://cdn.jsdelivr.net/' ) ) {
			return $tag;
		}
		$atributo = ( false !== strpos( $tag, ' src=' ) ) ? ' src=' : ' href=';
		return str_replace(
			$atributo,
			' integrity="' . $vendors[ $handle ]['sri'] . '" crossorigin="anonymous"' . $atributo,
			$tag
		);
	}
}

add_action( 'wp_enqueue_scripts', 'prc_bootstrap5_assets', 20 );
