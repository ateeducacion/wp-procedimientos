<?php
/**
 * Front-end asset registration for the procedures application.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

/**
 * La hoja y el guion del aplicativo, en línea.
 *
 * El único artefacto de producción es el bundle de Code Snippets: no hay
 * repositorio en disco ni URL de plugin desde la que servir un fichero, así
 * que el CSS y el JS viajan dentro del propio bundle como contenido en línea.
 * En desarrollo y en tests se leen de `assets/`, que sí está ahí.
 *
 * Bootstrap 5 y sus iconos no van aquí: los encola `snippets/bootstrap5.php`
 * desde el CDN con SRI (ADR-0006), y este módulo solo pregunta si están. Las
 * tipografías sí ({@see FONTS}): son parte del diseño, no una librería, y
 * quien despliega puede servirlas desde donde quiera con el filtro.
 */
final class Assets {

	/**
	 * Web font stylesheets of the design system.
	 *
	 * Las tres del original: la del cuerpo, la de las cifras del contador y la
	 * de los iconos. **No llevan `integrity`**: el servicio devuelve un CSS
	 * distinto según el navegador que lo pide —`woff2`, `woff` o `ttf`— y por
	 * eso no hay un hash que valga para todos; un SRI aquí rompería la página
	 * en cuanto cambiara el agente de usuario. Lo que sí se fija es la lista
	 * exacta de familias y ejes en la URL, la etiqueta viaja con
	 * `referrerpolicy="no-referrer"` ({@see font_tag()}) y la hoja degrada
	 * sola: cada familia lleva su alternativa del sistema en los tokens.
	 *
	 * Son dos peticiones y no una porque los iconos piden `display=block` —con
	 * `swap` se lee el nombre del icono durante medio segundo— y el texto pide
	 * `display=swap`, y `display` es uno por petición.
	 *
	 * @var array<string, string>
	 */
	private const FONTS = array(
		'prc-fuentes' => 'https://fonts.googleapis.com/css2?family=Days+One&family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap',
		'prc-iconos'  => 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=block',
	);

	/**
	 * Asset contents inlined by the bundler, keyed by path relative to assets/.
	 *
	 * @var array<string, string>
	 */
	private static $inline = array();

	/**
	 * Receive the asset contents that `build/pack-snippet.php` inlined.
	 *
	 * @param array<string, string> $assets Map of path relative to assets/ => contents.
	 * @return void
	 */
	public static function set_inline( array $assets ): void {
		self::$inline = $assets;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		// Pronto: un tema de bloques pinta el shortcode antes de `wp_enqueue_scripts`.
		add_action( 'init', array( self::class, 'register_assets' ), 20 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'register_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_app' ), 100 );
		add_filter( 'body_class', array( self::class, 'body_class' ) );
		add_filter( 'style_loader_tag', array( self::class, 'font_tag' ), 10, 2 );
	}

	/**
	 * Register (not always enqueue) the fonts and our own assets. Idempotent.
	 *
	 * Todo el CSS de `assets/css/` va en el mismo handle, y todo el JavaScript
	 * de `assets/js/` en el suyo: así el orden lo decide la lista
	 * ({@see files()}) y no la cadena de dependencias, y las hojas de cada
	 * pantalla entran detrás del armazón sin que haya que declarar nada.
	 *
	 * @return void
	 */
	public static function register_assets(): void {
		if ( wp_style_is( 'prc-app', 'registered' ) ) {
			return;
		}

		// phpcs:disable WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Inline handles have no URL to version, and the font URLs carry their own.
		foreach ( self::fonts() as $handle => $url ) {
			wp_register_style( $handle, $url, array(), null );
		}

		// La hoja depende de las tipografías: así se piden antes y no hay un
		// primer pintado con la familia del sistema y otro con la buena.
		wp_register_style( 'prc-app', false, array_keys( self::fonts() ), null );
		foreach ( self::files( 'css' ) as $rel ) {
			wp_add_inline_style( 'prc-app', self::contents( $rel ) );
		}

		wp_register_script( 'prc-app', false, array(), null, true );
		foreach ( self::files( 'js' ) as $rel ) {
			wp_add_inline_script( 'prc-app', self::contents( $rel ) );
		}
		// phpcs:enable WordPress.WP.EnqueuedResourceParameters.MissingVersion
	}

	/**
	 * The web font stylesheets, as handle => URL.
	 *
	 * El filtro es la salida para quien no quiera pedirle nada a un tercero:
	 * devolviendo otras URL las sirve de su propio sitio, y devolviendo un
	 * array vacío se queda con las familias del sistema.
	 *
	 * @return array<string, string>
	 */
	public static function fonts(): array {
		/**
		 * Filter the web font stylesheets the application loads.
		 *
		 * @param array<string, string> $fonts Handle => stylesheet URL.
		 */
		return array_filter( (array) apply_filters( 'prc_fonts', self::FONTS ) );
	}

	/**
	 * Keep the page URL out of the request for the fonts.
	 *
	 * @param string $tag    The tag WordPress is about to print.
	 * @param string $handle Its handle.
	 * @return string
	 */
	public static function font_tag( string $tag, string $handle ): string {
		if ( ! isset( self::fonts()[ $handle ] ) ) {
			return $tag;
		}
		return str_replace( ' href=', ' referrerpolicy="no-referrer" href=', $tag );
	}

	/**
	 * The stylesheets or scripts of the application, in load order.
	 *
	 * `prc-app` primero —lleva los tokens y el armazón, y el resto se monta
	 * encima— y las demás por orden alfabético. La lista sale del mapa que
	 * inlinea el empaquetador y, cuando no lo hay, del propio directorio: en
	 * ninguno de los dos casos hay que acordarse de apuntar el fichero nuevo.
	 *
	 * @param string $tipo `css` or `js`.
	 * @return string[] Paths relative to assets/.
	 */
	public static function files( string $tipo ): array {
		$rel = array();
		foreach ( array_keys( self::$inline ) as $clave ) {
			if ( 0 === strpos( $clave, $tipo . '/' ) ) {
				$rel[] = $clave;
			}
		}
		if ( array() === $rel ) {
			foreach ( (array) glob( dirname( __DIR__, 3 ) . '/assets/' . $tipo . '/*.' . $tipo ) as $ruta ) {
				$rel[] = $tipo . '/' . basename( (string) $ruta );
			}
		}
		sort( $rel );

		$armazon = $tipo . '/prc-app.' . $tipo;
		if ( in_array( $armazon, $rel, true ) ) {
			$rel = array_merge( array( $armazon ), array_values( array_diff( $rel, array( $armazon ) ) ) );
		}
		return $rel;
	}

	/**
	 * Enqueue the stylesheet and the script on our own pages.
	 *
	 * El shortcode llega después del encabezado: esperar a él repinta la
	 * página. Se encola en `wp_enqueue_scripts`, y {@see enqueue()} queda como
	 * respaldo para quien llame al shortcode desde otro sitio. La ficha pública
	 * del procedimiento también es «nuestra» para {@see Shell::is_app_page()}.
	 *
	 * @return void
	 */
	public static function enqueue_app(): void {
		if ( Shell::is_app_page() ) {
			self::enqueue();
		}
	}

	/**
	 * Enqueue the stylesheet and the script, registering them if needed.
	 *
	 * @return void
	 */
	public static function enqueue(): void {
		self::register_assets();
		wp_enqueue_style( 'prc-app' );
		wp_enqueue_script( 'prc-app' );
	}

	/**
	 * Whether Bootstrap 5 already styles the page.
	 *
	 * Mirar la cola de estilos es adivinar: un handle llamado «bootstrap» no
	 * dice de qué versión es. La verdad la pone quien la conoce:
	 * `snippets/bootstrap5.php` fija este filtro cuando de verdad ha encolado
	 * Bootstrap 5 en esta página. Si no está, nuestra hoja pinta botones y
	 * pastillas decentes por su cuenta.
	 *
	 * @return bool
	 */
	public static function has_bootstrap(): bool {
		/**
		 * Filter whether Bootstrap 5 styles the page.
		 *
		 * @param bool $present Whether Bootstrap 5 was loaded for this page.
		 */
		return (bool) apply_filters( 'prc_has_bootstrap', false );
	}

	/**
	 * Mark the page when Bootstrap is absent, so our own skin applies.
	 *
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public static function body_class( array $classes ): array {
		if ( ! self::has_bootstrap() ) {
			$classes[] = 'prc-sin-bootstrap';
		}
		return $classes;
	}

	/**
	 * Classes for a page-level notice.
	 *
	 * @param string $tono info|warning|success|danger.
	 * @return string
	 */
	public static function alert_class( string $tono = 'info' ): string {
		$tonos = array( 'info', 'warning', 'success', 'danger' );
		$tono  = in_array( $tono, $tonos, true ) ? $tono : 'info';
		return sprintf( 'prc-aviso prc-aviso-%1$s alert alert-%1$s', $tono );
	}

	/**
	 * Classes for a button or a button-styled link.
	 *
	 * Los dos vocabularios a la vez: `prc-btn` para nuestro CSS y las de
	 * Bootstrap para que el botón sea el del resto del sitio. Sin Bootstrap,
	 * las clases sobrantes no hacen nada.
	 *
	 * @param bool $primary Whether this is the primary action.
	 * @return string
	 */
	public static function button_class( bool $primary = false ): string {
		return $primary
			? 'prc-btn prc-btn-primary btn btn-primary'
			: 'prc-btn btn btn-light';
	}

	/**
	 * Classes for the chip of a derived state, of a procedure or of a review.
	 *
	 * @param string $estado One of ProcedureMetaKeys::states() or ApplicationMetaKeys::review_states().
	 * @return string
	 */
	public static function state_class( string $estado ): string {
		return sprintf(
			'prc-state prc-state-%s',
			sanitize_html_class( '' !== $estado ? $estado : 'na' )
		);
	}

	/**
	 * Contents of one asset: inlined by the bundler, or read from the repo.
	 *
	 * @param string $rel Path relative to assets/, e.g. `js/prc-app.js`.
	 * @return string Empty when neither source is available.
	 */
	public static function contents( string $rel ): string {
		if ( isset( self::$inline[ $rel ] ) ) {
			return self::$inline[ $rel ];
		}

		$path = dirname( __DIR__, 3 ) . '/assets/' . $rel;
		if ( ! is_readable( $path ) ) {
			return '';
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local asset, not a remote request.
		return (string) file_get_contents( $path );
	}
}
