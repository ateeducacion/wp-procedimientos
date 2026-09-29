<?php
/**
 * Snippet Name: PRC — SweetAlert2 en las pantallas de procedimientos
 * Description: Carga SweetAlert2 solo en las pantallas del aplicativo de procedimientos, para que las confirmaciones de lo que no tiene vuelta atrás se pidan con un diálogo en castellano y no con el `confirm()` del navegador. Si no llega, el aplicativo pregunta con `confirm()` y sin JavaScript el botón envía el formulario igual.
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

if ( ! function_exists( 'prc_sweetalert_vendor' ) ) {
	/**
	 * Pinned SweetAlert2 build and its subresource integrity hash.
	 *
	 * El hash se calcula pidiendo el fichero al mismo CDN que lo va a servir
	 * —jsDelivr minifica al vuelo, así que el hash del paquete no tiene por qué
	 * coincidir—:
	 *
	 *   curl -s https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25/dist/sweetalert2.all.min.js \
	 *     | openssl dgst -sha384 -binary | openssl base64 -A
	 *
	 * Calculado el 2026-09-13, dos veces y con el mismo resultado. Se usa el
	 * paquete `all`, que trae los estilos dentro: un fichero, un hash y una
	 * ruta que el mu-plugin de desarrollo sabe reescribir; una hoja aparte
	 * sería una URL más que anclar sin ganar nada.
	 *
	 * La versión está clavada en tres sitios a la vez: aquí, en la URL y en
	 * `package.json`. Si se sube una, se suben las tres o el SRI deja de
	 * cuadrar y las pruebas se vuelven a ir al CDN (ADR-0015).
	 *
	 * @return array{url:string, ver:string, sri:string}
	 */
	function prc_sweetalert_vendor(): array {
		return array(
			'url' => 'https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25/dist/sweetalert2.all.min.js',
			'ver' => '11.26.25',
			'sri' => 'sha384-nLoOnA/BDh8A/jxqtckg4DumuCGOBYUnNJLZdQz/zfYNp3wcjGSoWTAzgko06G/2',
		);
	}
}

if ( ! function_exists( 'prc_sweetalert_assets' ) ) {
	/**
	 * Load SweetAlert2 exactly where the script that uses it runs.
	 *
	 * No se vuelve a decidir en qué páginas estamos: quien lo decide es
	 * `Assets::enqueue_app()`, y aquí basta con preguntar si ha encolado el
	 * guion del aplicativo. Una segunda lista de pantallas sería una lista que
	 * mañana no coincide con la primera.
	 *
	 * Prioridad 110: después del 100 con el que el aplicativo encola el suyo.
	 * No hace falta declararlo dependencia —el guion solo mira `window.Swal`
	 * cuando alguien pulsa un botón, no al cargarse—.
	 *
	 * @return void
	 */
	function prc_sweetalert_assets(): void {
		if ( ! wp_script_is( 'prc-app', 'enqueued' ) ) {
			return;
		}

		/**
		 * Permite desactivar el diálogo sin desactivar el snippet.
		 *
		 * Apagarlo no quita la confirmación: el aplicativo se queda con el
		 * `confirm()` del navegador.
		 *
		 * @param bool $cargar Si se carga SweetAlert2.
		 */
		if ( ! apply_filters( 'prc_load_sweetalert', true ) ) {
			return;
		}

		$vendor = prc_sweetalert_vendor();
		wp_enqueue_script( 'sweetalert2', $vendor['url'], array(), $vendor['ver'], true );
		add_filter( 'script_loader_tag', 'prc_sweetalert_sri', 10, 3 );
	}
}

if ( ! function_exists( 'prc_sweetalert_sri' ) ) {
	/**
	 * Add integrity and crossorigin to our vendor tag.
	 *
	 * Solo si la URL sigue siendo la del CDN: en desarrollo el mu-plugin la
	 * reescribe a `node_modules` y entonces el SRI ni cuadra ni hace falta.
	 *
	 * @param string $tag    The tag WordPress is about to print.
	 * @param string $handle Its handle.
	 * @param string $src    Its URL.
	 * @return string
	 */
	function prc_sweetalert_sri( $tag, $handle, $src ) {
		if ( 'sweetalert2' !== $handle || 0 !== strpos( (string) $src, 'https://cdn.jsdelivr.net/' ) ) {
			return $tag;
		}
		return str_replace(
			' src=',
			' integrity="' . prc_sweetalert_vendor()['sri'] . '" crossorigin="anonymous" src=',
			$tag
		);
	}
}

add_action( 'wp_enqueue_scripts', 'prc_sweetalert_assets', 110 );
