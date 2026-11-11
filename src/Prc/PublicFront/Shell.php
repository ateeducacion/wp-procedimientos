<?php
/**
 * Chrome shared by every front-end page of the procedures application.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\Access\CentreScope;
use Prc\Access\ProcedureAccess;
use Prc\Admin\Settings;
use Prc\Domain\CentreCatalog;
use Prc\PostType\ProcedurePostType;
use Prc\PublicFront\View\ProcedureChrome;
use Prc\Taxonomy\ProcedureTaxonomies;

/**
 * La cabecera, el menú y el pie que comparten todas las pantallas.
 *
 * El aplicativo se pinta entero él mismo: cabecera con quién mira y con qué
 * ámbito o centro, un menú con lo que esa persona puede hacer y un pie de una
 * línea. Sin depender del tema: su cabecera, su pie y sus hojas no visten nada
 * nuestro, solo pesan.
 *
 * Una sola lista de secciones ({@see sections()}) la usan el menú y los
 * botones: si mañana hay una sección más, se añade en un sitio. La ficha
 * pública del procedimiento no tiene página propia —es el `single` del tipo—
 * pero es una pantalla del aplicativo a todos los efectos: se sirve sola, sin
 * el tema, y con este mismo marco (ADR-0022).
 */
final class Shell {

	/**
	 * Slugs de las páginas, por sección. En castellano, como las URL de hoy.
	 *
	 * @var array<string, string>
	 */
	public const SLUGS = array(
		'home'      => 'procedimientos',
		'workspace' => 'gestion-de-procedimientos',
		'editor'    => 'editar-procedimiento',
		'apply'     => 'solicitud',
		'mine'      => 'mi-centro',
		'observe'   => 'consulta-de-solicitudes',
		'help'      => 'ayuda',
	);

	/**
	 * Shortcode que pinta cada pantalla, por sección.
	 *
	 * Se mira el shortcode y no el slug para saber si estamos en una página del
	 * aplicativo: las páginas las crea quien despliega y su dirección puede
	 * cambiar; el shortcode no. Y son cadenas, no clases: así el armazón no
	 * depende de que las pantallas estén cargadas.
	 *
	 * @var array<string, string>
	 */
	public const SHORTCODES = array(
		'home'      => 'prc_home',
		'workspace' => 'prc_workspace',
		'editor'    => 'prc_editor',
		'apply'     => 'prc_apply',
		'mine'      => 'prc_mine',
		'observe'   => 'prc_observe',
		'help'      => 'prc_help',
	);

	/**
	 * La sección de la ficha pública: el `single` de `prc_procedure`, sin página propia.
	 */
	public const SECTION_PROCEDURE = 'procedure';

	/**
	 * Secciones que se ven sin sesión: la portada, la ficha del procedimiento y la ayuda.
	 *
	 * @var string[]
	 */
	public const PUBLIC_SECTIONS = array( 'home', self::SECTION_PROCEDURE, 'help' );

	/**
	 * Query arg con el que el taller y la solicitud reciben el procedimiento.
	 */
	public const ARG_PROCEDURE = 'procedimiento';

	/**
	 * El `id` del aviso del resultado, al que vuelve la redirección tras un POST.
	 */
	public const NOTICE_ANCHOR = 'prc-aviso';

	/**
	 * El nombre de la sección de las solicitudes propias, por quién solicita.
	 *
	 * Uno solo y en todas partes: el menú, la miga de pan y el título.
	 *
	 * @var array<string, string>
	 */
	private const MINE_LABELS = array(
		'centre'  => 'Solicitudes de mi centro',
		'teacher' => 'Mis solicitudes',
	);

	/**
	 * Hojas de WordPress que ninguna pantalla del aplicativo usa.
	 *
	 * @var string[]
	 */
	private const CORE_ASSETS = array(
		'wp-block-library',
		'wp-block-library-theme',
		'global-styles',
		'classic-theme-styles',
		'wp-emoji-styles',
	);

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'body_class', array( self::class, 'body_class' ) );
		add_filter( 'document_title_parts', array( self::class, 'document_title_parts' ) );
		add_filter( 'show_admin_bar', array( self::class, 'show_admin_bar' ), 100 );
		// Antes de pintar nada: en `template_redirect` aún se pueden mandar
		// cabeceras, y dentro del shortcode ya no.
		add_action( 'template_redirect', array( self::class, 'require_login' ) );
		// Después de mandar al acceso a quien no ha entrado, y antes de que el
		// tema empiece a pintar.
		add_action( 'template_redirect', array( self::class, 'render_standalone' ), 20 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'drop_theme_assets' ), 100 );
		// Y una red por si algo se encola más tarde: el tema imprime una hoja
		// «late» en el pie, y al escribir la etiqueta se descarta.
		add_filter( 'style_loader_tag', array( self::class, 'drop_theme_tag' ), 10, 3 );
		add_filter( 'script_loader_tag', array( self::class, 'drop_theme_tag' ), 10, 3 );
	}

	/**
	 * Send anonymous visitors of a private application page to the login form.
	 *
	 * La portada y la ficha de un procedimiento son públicas; el resto es de
	 * alguien. Sin sesión se va al formulario de acceso y se vuelve aquí. El
	 * aviso se queda en el modelo de cada pantalla: esa es la guarda de verdad.
	 *
	 * @return void
	 */
	public static function require_login(): void {
		if ( is_user_logged_in() || ! self::is_app_page() || self::is_public( self::current_section() ) ) {
			return;
		}
		self::leave( wp_login_url( self::current_url() ) );
	}

	/**
	 * Whether a section is readable without a session.
	 *
	 * @param string $section Section key.
	 * @return bool
	 */
	public static function is_public( string $section ): bool {
		return in_array( $section, self::PUBLIC_SECTIONS, true );
	}

	/**
	 * Las pantallas con shortcode se sirven solas, sin la plantilla del tema.
	 *
	 * La ficha del procedimiento se sirve igual, pero la monta
	 * {@see ProcedureView::render()}: su contenido no es el de la página.
	 *
	 * @return void
	 */
	public static function render_standalone(): void {
		if ( ! self::is_standalone() || self::SECTION_PROCEDURE === self::current_section() ) {
			return;
		}
		// `is_app_page()` ya ha comprobado que hay una entrada singular.
		$post = get_post();
		self::serve( (string) apply_filters( 'the_content', $post->post_content ) );
	}

	/**
	 * Whether this request is one of ours and paints its own document.
	 *
	 * @return bool
	 */
	public static function is_standalone(): bool {
		/**
		 * Filter whether the application renders its own page, without the theme.
		 *
		 * @param bool $solo Whether to bypass the theme template.
		 */
		return (bool) apply_filters( 'prc_standalone_page', true ) && self::is_app_page();
	}

	/**
	 * Serve one whole document and leave.
	 *
	 * Se imprime el documento entero —`wp_head()` y `wp_footer()` incluidos,
	 * que son los que traen la barra de administración y lo que encolamos— y
	 * se sale por {@see leave()}, que en tests lanza su excepción en vez de
	 * terminar.
	 *
	 * @param string $body Body markup, already escaped.
	 * @return void
	 */
	public static function serve( string $body ): void {
		status_header( 200 );
		nocache_headers();
		self::send_header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );
		echo self::document( $body ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- documento montado en document().
		self::leave();
	}

	/**
	 * The whole HTML document around a body, from the doctype to the closing tag.
	 *
	 * @param string $body Body markup, already escaped.
	 * @return string
	 */
	public static function document( string $body ): string {
		ob_start();
		?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
		<?php
		// El tema declara `title-tag` y `wp_head()` escribe el título; sin esa
		// declaración —o sin tema que la haga— lo escribimos nosotros.
		if ( ! current_theme_supports( 'title-tag' ) ) {
			echo '<title>' . esc_html( wp_get_document_title() ) . '</title>';
		}
		wp_head();
		// El aviso de cookies va al final de la cabecera, detrás de `wp_head()`:
		// es de terceros y no tiene que competir con nuestras hojas.
		echo ProcedureChrome::consent(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- URL escapadas dentro.
		?>
</head>
<body <?php body_class(); ?>>
		<?php
		wp_body_open();
		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- llega ya escapado.
		wp_footer();
		echo ProcedureChrome::analytics(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido en analytics().
		?>
</body>
</html>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * En nuestras páginas no se escribe ninguna etiqueta del tema.
	 *
	 * @param string $tag    The tag WordPress is about to print.
	 * @param string $handle Its handle.
	 * @param string $src    Its URL.
	 * @return string
	 */
	public static function drop_theme_tag( string $tag, string $handle, string $src ): string {
		unset( $handle );
		if ( ! self::is_standalone() ) {
			return $tag;
		}
		return self::is_theme_asset( $src ) ? '' : $tag;
	}

	/**
	 * Lo del tema, fuera de la cola.
	 *
	 * @return void
	 */
	public static function drop_theme_assets(): void {
		if ( ! self::is_standalone() ) {
			return;
		}

		foreach ( array( wp_styles(), wp_scripts() ) as $cola ) {
			foreach ( (array) $cola->queue as $handle ) {
				$src = isset( $cola->registered[ $handle ] ) ? (string) $cola->registered[ $handle ]->src : '';
				if ( '' !== $src && self::is_theme_asset( $src ) ) {
					$cola->dequeue( $handle );
				}
			}
		}

		// Y lo que trae WordPress para lo que aquí no hay: el contenido de la
		// página es un shortcode, no hay bloques que vestir ni ajustes de tema
		// global que aplicar.
		foreach ( self::CORE_ASSETS as $handle ) {
			wp_dequeue_style( $handle );
		}
		// El detector de emoji son trece kilobytes de guion en línea para
		// sustituir caritas que no salen en ninguna pantalla.
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
	}

	/**
	 * Si un fichero viene del tema o de la caché de su constructor.
	 *
	 * Por la ruta y no por una lista de nombres, que cambiaría con cada versión
	 * del tema (`/et-cache/` es la de su constructor).
	 *
	 * @param string $src Its URL.
	 * @return bool
	 */
	private static function is_theme_asset( string $src ): bool {
		return '' !== $src
			&& ( false !== strpos( $src, '/themes/' ) || false !== strpos( $src, '/et-cache/' ) );
	}

	/**
	 * Show the front-end toolbar only for administrators, including switched sessions.
	 *
	 * Cuando administración se cambia a otra persona con WPFront User Role
	 * Editor para comprobar qué ve, la sesión pasa a ser la de esa persona y la
	 * barra se iba con ella: sin barra no hay «Volver a mi cuenta». WPFront no
	 * expone su pila de suplantación, así que se lee su propia cookie, se
	 * descifra con su propia utilidad y se comprueba lo mismo que comprueba él.
	 *
	 * Esto decide SOLO si se pinta la barra. No concede ninguna capacidad a la
	 * persona suplantada. Sin el plugin instalado su clase no existe: se
	 * responde que no y ya.
	 *
	 * @return bool
	 */
	public static function show_admin_bar(): bool {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		if ( ! is_user_logged_in() || ! class_exists( '\WPFront\URE\WPFront_User_Role_Editor_Utils' ) ) {
			return false;
		}

		$clave = 'wpfront_ure_user_switching_stack_' . COOKIEHASH;
		if ( ! isset( $_COOKIE[ $clave ] ) || ! is_string( $_COOKIE[ $clave ] ) ) {
			return false;
		}
		$cookie = sanitize_text_field( wp_unslash( $_COOKIE[ $clave ] ) );
		// Descifrada, la pila es «COOKIEHASH-momento-usuarios-remember»; si no
		// tiene esa forma, no es nuestra y no se mira más.
		$sesion = explode( '-', (string) \WPFront\URE\WPFront_User_Role_Editor_Utils::decrypt( $cookie ), 4 );
		if ( count( $sesion ) < 3 || COOKIEHASH !== $sesion[0] || ! ctype_digit( $sesion[1] ) ) {
			return false;
		}
		$edad = time() - (int) $sesion[1];
		if ( $edad < 0 || $edad > 12 * HOUR_IN_SECONDS ) {
			return false;
		}
		// El primero de la pila es quien empezó el cambio: la barra es suya.
		$usuarios = explode( ',', $sesion[2] );
		return ctype_digit( $usuarios[0] ) && user_can( (int) $usuarios[0], 'manage_options' );
	}

	/**
	 * Mark the pages of the application, so the theme chrome can step aside.
	 *
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public static function body_class( array $classes ): array {
		if ( self::is_app_page() ) {
			$classes[] = 'prc-app';
		}
		return $classes;
	}

	/**
	 * Whether the request is one of our screens: a page with a shortcode, or a procedure.
	 *
	 * @return bool
	 */
	public static function is_app_page(): bool {
		return '' !== self::current_section();
	}

	/**
	 * Which section is being viewed, by the shortcode the page carries.
	 *
	 * @return string Section key, or empty outside the application.
	 */
	public static function current_section(): string {
		if ( is_admin() || ! is_singular() ) {
			return '';
		}
		if ( is_singular( ProcedurePostType::POST_TYPE ) ) {
			return self::SECTION_PROCEDURE;
		}
		$post = get_post();
		if ( ! $post instanceof \WP_Post ) {
			return '';
		}
		// Se llama una vez por cada etiqueta <link> y <script> que se imprime
		// (drop_theme_tag): sin memoria, es una pasada de la expresión de
		// shortcodes por cada shortcode y por etiqueta sobre todo el contenido.
		static $memo = array();
		$contenido   = (string) $post->post_content;
		$clave       = md5( $contenido );
		if ( ! isset( $memo[ $clave ] ) ) {
			$memo[ $clave ] = '';
			foreach ( self::SHORTCODES as $seccion => $codigo ) {
				if ( has_shortcode( $contenido, $codigo ) ) {
					$memo[ $clave ] = $seccion;
					break;
				}
			}
		}
		return $memo[ $clave ];
	}

	/**
	 * URL of one of our pages, or empty when it does not exist.
	 *
	 * @param string               $section Section key, one of SLUGS.
	 * @param array<string, mixed> $args    Query arguments to append.
	 * @return string
	 */
	public static function url( string $section, array $args = array() ): string {
		$slug = self::SLUGS[ $section ] ?? '';
		if ( '' === $slug ) {
			return '';
		}

		/**
		 * Filter the page slug of one section.
		 *
		 * Quien despliega crea las páginas y les pone la dirección que quiera
		 * —o las cuelga de una madre, con la ruta entera—; esto permite
		 * reapuntarlas sin tocar el código.
		 *
		 * @param string $slug    Page path.
		 * @param string $section Section key.
		 */
		$slug = (string) apply_filters( 'prc_page_slug', $slug, $section );

		$page = get_page_by_path( $slug );
		if ( ! $page ) {
			return '';
		}
		$url = (string) get_permalink( $page );
		return array() === $args ? $url : add_query_arg( $args, $url );
	}

	/**
	 * Sections of whoever is looking, and where each one goes.
	 *
	 * Solo lo que se puede abrir sin contexto: el taller y la solicitud piden
	 * un `?procedimiento=<id>` y son destinos de un botón, no pestañas.
	 *
	 * @param int $user_id User ID (0 = current).
	 * @return array<string, array{label:string, url:string}>
	 */
	public static function sections( int $user_id = 0 ): array {
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
		if ( $user_id <= 0 ) {
			return array();
		}

		$out = array(
			'home' => array(
				'label' => 'Procedimientos',
				'url'   => self::url( 'home' ),
			),
		);
		if ( ProcedureAccess::can_manage_procedures( $user_id ) ) {
			$out['workspace'] = array(
				'label' => 'Gestión',
				'url'   => self::url( 'workspace' ),
			);
		}
		if ( ProcedureAccess::can_apply( $user_id ) ) {
			$out['mine'] = array(
				'label' => self::mine_label( user_can( $user_id, ProcedureAccess::CAP_APPLY_AS_TEACHER ) ),
				'url'   => self::url( 'mine' ),
			);
		}
		// La consulta de solo lectura: quien consulta una zona y quien
		// gestiona, que ve la de sus procedimientos entera (ADR-0043).
		if ( ProcedureAccess::can_observe( $user_id ) ) {
			$out['observe'] = array(
				'label' => 'Consulta de solicitudes',
				'url'   => self::url( 'observe' ),
			);
		}

		// Una sección cuya página no existe todavía no se ofrece: una pestaña
		// que lleva a un 404 es peor que no tenerla.
		foreach ( $out as $clave => $seccion ) {
			if ( '' === $seccion['url'] ) {
				unset( $out[ $clave ] );
			}
		}

		return $out;
	}

	/**
	 * The name of the section of one's own applications.
	 *
	 * @param bool $teacher Whether the person applies as a teacher.
	 * @return string
	 */
	public static function mine_label( bool $teacher ): string {
		return self::MINE_LABELS[ $teacher ? 'teacher' : 'centre' ];
	}

	/**
	 * The document title of one's own section, named like its menu entry.
	 *
	 * La pestaña del navegador dice lo mismo que el menú y el H1: la sección
	 * propia se llama según quién mira, y no como se llame la página de
	 * WordPress —que en una instalación anterior puede seguir siendo otro
	 * nombre—.
	 *
	 * @param array<string, string> $parts Title parts.
	 * @return array<string, string>
	 */
	public static function document_title_parts( array $parts ): array {
		if ( 'mine' === self::current_section() ) {
			$parts['title'] = self::mine_label( user_can( get_current_user_id(), ProcedureAccess::CAP_APPLY_AS_TEACHER ) );
		}
		return $parts;
	}

	/**
	 * Who is looking: role and scope (ámbito or school), for the header.
	 *
	 * Dos líneas y no un rótulo: en un aplicativo acotado por ámbito y por
	 * centro, saber con cuál se está mirando es la mitad de la respuesta a
	 * «¿por qué no veo este procedimiento?».
	 *
	 * @param int $user_id User ID (0 = current).
	 * @return array{role:string, scope:string} Empty strings when there is no role.
	 */
	public static function profile( int $user_id = 0 ): array {
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
		if ( $user_id > 0 && ProcedureAccess::is_manager( $user_id ) ) {
			return array(
				'role'  => 'Administración',
				'scope' => 'Todos los ámbitos',
			);
		}
		if ( $user_id > 0 && user_can( $user_id, ProcedureAccess::CAP_MANAGE_PROCEDURES ) ) {
			return array(
				'role'  => 'Gestión de procedimientos',
				'scope' => self::area_names( $user_id ),
			);
		}
		if ( $user_id > 0 && user_can( $user_id, ProcedureAccess::CAP_APPLY ) ) {
			return array(
				'role'  => 'Equipo directivo',
				'scope' => self::centre_name( $user_id ),
			);
		}
		if ( $user_id > 0 && user_can( $user_id, ProcedureAccess::CAP_VIEW_APPLICATIONS ) ) {
			return array(
				'role'  => 'Consulta de solicitudes',
				'scope' => self::zone_names( $user_id ),
			);
		}
		return array(
			'role'  => '',
			'scope' => '',
		);
	}

	/**
	 * The ámbitos of a person, written out.
	 *
	 * @param int $user_id User ID.
	 * @return string
	 */
	private static function area_names( int $user_id ): string {
		$nombres = array();
		foreach ( ProcedureAccess::user_areas( $user_id ) as $term_id ) {
			$term = get_term( $term_id, ProcedureTaxonomies::AREA );
			if ( $term instanceof \WP_Term ) {
				$nombres[] = $term->name;
			}
		}
		return array() === $nombres ? 'Sin ámbito asignado' : implode( ' · ', $nombres );
	}

	/**
	 * The zones a person observes, by name when the catalogue knows them.
	 *
	 * @param int $user_id User ID.
	 * @return string
	 */
	public static function zone_names( int $user_id ): string {
		$nombres = array();
		foreach ( ProcedureAccess::observer_zones( $user_id ) as $zona ) {
			$centro    = CentreCatalog::find( $zona );
			$nombres[] = array() !== $centro && '' !== (string) $centro['name'] ? $centro['name'] . ' (' . $zona . ')' : $zona;
		}
		return array() === $nombres ? 'Sin zona asignada' : implode( ' · ', $nombres );
	}

	/**
	 * The school of a person, by name when the catalogue knows it.
	 *
	 * @param int $user_id User ID.
	 * @return string
	 */
	public static function centre_name( int $user_id ): string {
		$code = CentreScope::code_for( $user_id );
		if ( '' === $code ) {
			return 'Sin centro asignado';
		}
		$centro = CentreCatalog::find( $code );
		return array() !== $centro && '' !== $centro['name'] ? $centro['name'] . ' (' . $code . ')' : $code;
	}

	/**
	 * Up to two initials of a display name, for the avatar.
	 *
	 * Se parte por lo que no es letra ni número, no por espacios: los nombres
	 * que se muestran traen el cargo entre paréntesis y partiendo por espacios
	 * la segunda inicial salía «(».
	 *
	 * @param string $nombre Display name.
	 * @return string
	 */
	private static function initials( string $nombre ): string {
		$partes = preg_split( '/[^\p{L}\p{N}]+/u', trim( $nombre ), -1, PREG_SPLIT_NO_EMPTY );
		$partes = is_array( $partes ) ? $partes : array();
		$letras = '';
		foreach ( array_slice( $partes, 0, 2 ) as $parte ) {
			$letras .= mb_strtoupper( mb_substr( $parte, 0, 1 ) );
		}
		return $letras;
	}

	/**
	 * The whole page: header, menu, the sheet the screen goes in, and the footer.
	 *
	 * El título de pantalla son **dos componentes distintos**, no uno con
	 * variantes: el héroe de la portada y del taller —grande, en mayúsculas y
	 * centrado— y el título corriente de las pantallas de dentro. Los mide
	 * distinto el original y los pinta distinto la hoja; quien llama dice
	 * cuál quiere.
	 *
	 * @param string $title    Page heading.
	 * @param string $subtitle One line under the heading; empty for none.
	 * @param string $body     The screen, already escaped.
	 * @param bool   $hero     Whether the heading is the big centred one.
	 * @return string
	 */
	public static function render( string $title, string $subtitle, string $body, bool $hero = false ): string {
		/**
		 * Filter whether the application paints its own header and footer.
		 *
		 * @param bool $pintar Whether to render the chrome.
		 */
		if ( ! apply_filters( 'prc_show_chrome', true ) ) {
			return '<div class="prc-hoja">' . $body . '</div>';
		}

		ob_start();
		?>
		<div class="prc-hoja">
			<?php if ( '' !== $title ) : ?>
				<h1 class="<?php echo $hero ? 'prc-hero' : 'prc-h1'; ?>"><?php echo esc_html( $title ); ?></h1>
			<?php endif; ?>
			<?php if ( '' !== $subtitle ) : ?>
				<p class="prc-sub"><?php echo esc_html( $subtitle ); ?></p>
			<?php endif; ?>
			<?php echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- la pantalla llega ya escapada. ?>
		</div>
		<?php
		return self::top() . self::tabs() . (string) ob_get_clean() . self::bottom();
	}

	/**
	 * The header: who owns it, «Procedimientos» and who is looking.
	 *
	 * El rótulo de la organización sale de la configuración del armazón
	 * ({@see ProcedureChrome::chrome()}) y **está vacío por defecto**: este
	 * aplicativo no lleva dentro la marca de nadie (ADR-0009).
	 *
	 * @return string
	 */
	private static function top(): string {
		$perfil  = self::profile();
		$usuario = wp_get_current_user();
		$inicio  = self::home_url();
		$chrome  = ProcedureChrome::chrome();
		$duenio  = (string) $chrome['owner'];
		$rotulo  = (string) $chrome['org'];

		ob_start();
		?>
		<div class="prc-top">
			<div class="prc-top-fila">
				<?php if ( '' !== $duenio ) : ?>
					<span class="prc-logo" role="img" aria-label="<?php echo esc_attr( $duenio ); ?>"></span>
				<?php endif; ?>
				<?php if ( '' !== $rotulo ) : ?>
					<span class="prc-marca">
						<small><?php echo esc_html( $rotulo ); ?></small>
					</span>
				<?php endif; ?>
				<a class="prc-marca-app" href="<?php echo esc_url( $inicio ); ?>">Procedimientos</a>
				<?php if ( '' !== $perfil['role'] ) : ?>
					<details class="prc-yo">
						<summary>
							<span class="prc-yo-ava"><?php echo esc_html( self::initials( $usuario->display_name ) ); ?></span>
							<span class="prc-yo-txt">
								<span class="prc-yo-n"><?php echo esc_html( $usuario->display_name ); ?> <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg></span>
								<span class="prc-yo-r"><?php echo esc_html( $perfil['role'] ); ?></span>
								<span class="prc-yo-r prc-yo-a"><?php echo esc_html( $perfil['scope'] ); ?></span>
							</span>
						</summary>
						<div class="prc-yo-menu">
							<?php if ( ProcedureAccess::is_manager() ) : ?>
								<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . ProcedurePostType::POST_TYPE . '&page=' . Settings::PAGE ) ); ?>">Ajustes del aplicativo</a>
							<?php endif; ?>
							<a href="<?php echo esc_url( wp_logout_url( $inicio ) ); ?>">Salir</a>
						</div>
					</details>
				<?php elseif ( ! is_user_logged_in() ) : ?>
					<a class="prc-entrar" href="<?php echo esc_url( wp_login_url( self::current_url() ) ); ?>">Acceder</a>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * One entry of the menu, or of one of its dropdowns.
	 *
	 * @param string $label  What it says.
	 * @param string $url    Where it goes; empty for a group heading.
	 * @param bool   $active Whether the page being viewed is this one.
	 * @param bool   $group  Whether this is a heading and not a link.
	 * @return array{label:string, url:string, active:bool, group:bool, items:array<int, array<string, mixed>>}
	 */
	private static function entry( string $label, string $url = '', bool $active = false, bool $group = false ): array {
		return array(
			'label'  => $label,
			'url'    => $url,
			'active' => $active,
			'group'  => $group,
			'items'  => array(),
		);
	}

	/**
	 * The menu: the four entries of the bar and what hangs from each one.
	 *
	 * Las cuatro del sitio que se sustituye —Inicio, las solicitudes propias,
	 * Administrar y Ayuda—, pero **de cada una solo se pinta lo que esa
	 * persona puede abrir**, y la que se queda sin nada dentro no se pinta:
	 * un desplegable vacío no dice nada y uno con un enlace al que no se
	 * puede entrar, peor. Esconder una entrada no es el control de acceso:
	 * cada pantalla comprueba su capacidad al servirse ({@see require_login()}
	 * y el modelo de cada una). Esto es solo la ayuda para navegar.
	 *
	 * Lo que se puede abrir sale de {@see sections()}, que es la única lista;
	 * aquí solo se reparte en el árbol y se marca dónde se está. La consulta
	 * de solo lectura (ADR-0043) es una quinta entrada, que el sitio anterior
	 * no tenía: allí era una hoja aparte por procedimiento. «Ayuda» sale
	 * siempre, también sin sesión: es una pantalla más (ADR-0045).
	 *
	 * @param int $user_id User ID (0 = current).
	 * @return array<int, array{label:string, url:string, active:bool, group:bool, items:array<int, array<string, mixed>>}>
	 */
	private static function menu( int $user_id = 0 ): array {
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
		$secciones = self::sections( $user_id );
		$activa    = self::current_section();
		$menu      = array();

		// Sin sesión no hay secciones, pero la portada y la ayuda se leen igual.
		$inicio = isset( $secciones['home'] ) ? $secciones['home']['url'] : self::url( 'home' );
		if ( '' !== $inicio ) {
			$menu[] = self::entry( 'Inicio', $inicio, 'home' === $activa );
		}

		// Solicitudes presentadas por el centro o por la persona docente: una
		// entrada directa, con el mismo nombre que el título de la pantalla.
		if ( isset( $secciones['mine'] ) ) {
			$menu[] = self::entry( $secciones['mine']['label'], $secciones['mine']['url'], 'mine' === $activa );
		}

		// «Administrar»: crear y gestionar. El editor no es una sección con
		// pestaña —se abre sobre un procedimiento— pero sin argumento es la
		// pantalla de alta, y ahí sí hay a dónde ir.
		if ( isset( $secciones['workspace'] ) ) {
			$admin  = self::entry( 'Administrar' );
			$crear  = self::url( 'editor' );
			$dentro = in_array( $activa, array( 'workspace', 'editor' ), true );

			$admin['items'][] = self::entry( 'Gestión de procedimientos', '', false, true );
			if ( '' !== $crear ) {
				$admin['items'][] = self::entry( 'Crear procedimiento', $crear );
			}
			$admin['items'][] = self::entry( 'Gestionar procedimientos', $secciones['workspace']['url'], $dentro );
			$menu[]           = $admin;
		}

		$menu = array_merge( $menu, self::observe_entry( $secciones, $activa ), self::help_entry( $activa ) );

		// La entrada que contiene la página actual queda marcada también, como
		// en el original: la de primer nivel y la del desplegable.
		foreach ( $menu as $i => $entrada ) {
			foreach ( $entrada['items'] as $item ) {
				$menu[ $i ]['active'] = $menu[ $i ]['active'] || $item['active'];
			}
		}

		return $menu;
	}

	/**
	 * The entry of the read-only consultation, when the person has one.
	 *
	 * Aparte de {@see menu()} para que el árbol no crezca en ramas: una
	 * entrada de primer nivel, sin desplegable.
	 *
	 * @param array<string, array{label:string, url:string}> $secciones What {@see sections()} returned.
	 * @param string                                         $activa    Section being viewed.
	 * @return array<int, array<string, mixed>> None or one entry.
	 */
	private static function observe_entry( array $secciones, string $activa ): array {
		if ( ! isset( $secciones['observe'] ) ) {
			return array();
		}
		return array( self::entry( 'Consulta', $secciones['observe']['url'], 'observe' === $activa ) );
	}

	/**
	 * «Ayuda»: always there, pointing to the help screen.
	 *
	 * La ayuda es una pantalla del aplicativo y se ofrece a todo el mundo,
	 * también sin sesión (ADR-0045). Si quien despliega configuró enlaces
	 * propios —el buzón de incidencias, un tutorial—, la entrada se vuelve un
	 * desplegable con la guía primero y esos enlaces detrás (ADR-0009).
	 *
	 * @param string $activa Section being viewed.
	 * @return array<int, array<string, mixed>> None or one entry.
	 */
	private static function help_entry( string $activa ): array {
		$guia   = self::url( 'help' );
		$dentro = 'help' === $activa;
		$ayuda  = self::entry( 'Ayuda', $guia, $dentro );
		foreach ( (array) ProcedureChrome::chrome()['footer_links'] as $enlace ) {
			if ( isset( $enlace['label'], $enlace['url'] ) ) {
				$ayuda['items'][] = self::entry( (string) $enlace['label'], (string) $enlace['url'] );
			}
		}
		if ( array() !== $ayuda['items'] && '' !== $guia ) {
			array_unshift( $ayuda['items'], self::entry( 'Guías y preguntas frecuentes', $guia, $dentro ) );
		}
		return '' === $guia && array() === $ayuda['items'] ? array() : array( $ayuda );
	}

	/**
	 * The menu bar. With one single entry there is nothing to choose from.
	 *
	 * Lo que despliega es un `details` nativo: sin JavaScript (ADR-0022), con
	 * teclado y con un nivel. Los tres niveles anidados del menú original no
	 * se podían recorrer ni con el tabulador ni con el dedo.
	 *
	 * @return string
	 */
	private static function tabs(): string {
		$menu = self::menu();
		if ( count( $menu ) < 2 ) {
			return '';
		}

		ob_start();
		?>
		<nav class="prc-tabs" aria-label="Secciones">
			<div class="prc-tabs-fila">
				<?php foreach ( $menu as $entrada ) : ?>
					<?php $marca = $entrada['active'] ? ' prc-tab-on' : ''; ?>
					<?php if ( array() === $entrada['items'] ) : ?>
						<a class="prc-tab<?php echo esc_attr( $marca ); ?>"<?php echo $entrada['active'] ? ' aria-current="page"' : ''; ?> href="<?php echo esc_url( $entrada['url'] ); ?>"><?php echo esc_html( $entrada['label'] ); ?></a>
					<?php else : ?>
						<details class="prc-tab-caja">
							<summary class="prc-tab<?php echo esc_attr( $marca ); ?>"><?php echo esc_html( $entrada['label'] ); ?></summary>
							<div class="prc-desplegable">
								<?php foreach ( $entrada['items'] as $item ) : ?>
									<?php if ( $item['group'] ) : ?>
										<p class="prc-desplegable-grupo"><?php echo esc_html( $item['label'] ); ?></p>
									<?php else : ?>
										<a<?php echo $item['active'] ? ' aria-current="page"' : ''; ?> href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a>
									<?php endif; ?>
								<?php endforeach; ?>
							</div>
						</details>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</nav>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The one-line footer, always at the bottom.
	 *
	 * **Vacío salvo que alguien lo configure** ({@see ProcedureChrome::chrome()}).
	 *
	 * @return string
	 */
	private static function bottom(): string {
		$chrome = ProcedureChrome::chrome();
		$duenio = (string) $chrome['owner'];
		$hecho  = (string) $chrome['credit'];

		ob_start();
		?>
		<div class="prc-pie"><div>
			<span class="prc-pie-quien">
				<?php if ( '' !== $duenio ) : ?>
					<a href="<?php echo esc_url( '' !== (string) $chrome['owner_url'] ? (string) $chrome['owner_url'] : self::home_url() ); ?>">&copy; <?php echo esc_html( $duenio ); ?></a>
				<?php endif; ?>
				<?php if ( '' !== $hecho ) : ?>
					<span class="prc-pie-credito"><?php echo esc_html( $hecho ); ?></span>
				<?php endif; ?>
			</span>
			<span class="prc-pie-enlaces">
				<?php foreach ( (array) $chrome['footer_links'] as $enlace ) : ?>
					<?php if ( isset( $enlace['label'], $enlace['url'] ) ) : ?>
						<a href="<?php echo esc_url( (string) $enlace['url'] ); ?>" rel="noopener"><?php echo esc_html( (string) $enlace['label'] ); ?></a>
					<?php endif; ?>
				<?php endforeach; ?>
			</span>
		</div></div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The application entry page, or the site root when it does not exist yet.
	 *
	 * @return string
	 */
	private static function home_url(): string {
		$inicio = self::url( 'home' );
		return '' !== $inicio ? $inicio : home_url( '/' );
	}

	/**
	 * Where the person is right now, to come back after logging in.
	 *
	 * Solo el permalink: los filtros de la consulta no se arrastran hasta el
	 * formulario de acceso, y volver a la pantalla ya es lo que hace falta.
	 *
	 * @return string
	 */
	private static function current_url(): string {
		$destino = is_singular() ? (string) get_permalink() : '';
		return '' !== $destino ? $destino : home_url( '/' );
	}

	/**
	 * A one-line notice: what just happened, or why a screen is empty.
	 *
	 * @param string $type   ok | aviso | error.
	 * @param string $text   What to say, plain text.
	 * @param bool   $anchor Whether it is the result a redirect comes back to.
	 * @return string
	 */
	public static function notice( string $type, string $text, bool $anchor = false ): string {
		if ( '' === $text ) {
			return '';
		}
		return self::notice_open( $type, $anchor ) . esc_html( $text ) . '</p>';
	}

	/**
	 * The opening tag of a notice, for the ones that carry a link inside.
	 *
	 * El error y la advertencia se anuncian en cuanto aparecen (`alert`); lo
	 * hecho y la nota esperan a que el lector termine (`status`). El aviso del
	 * resultado —el de la vuelta de un POST— lleva además el ancla a la que
	 * llega la redirección ({@see leave()}) y admite el foco, para que quien
	 * navega con teclado o lector de pantalla empiece a leer por él.
	 *
	 * @param string $type   ok | aviso | error.
	 * @param bool   $anchor Whether it is the result a redirect comes back to.
	 * @param string $tag    `p` for one line, `div` for a notice with a list inside.
	 * @return string
	 */
	public static function notice_open( string $type, bool $anchor = false, string $tag = 'p' ): string {
		$tonos = array(
			'ok'    => 'success',
			'aviso' => 'warning',
			'error' => 'danger',
		);
		$tono  = $tonos[ $type ] ?? 'info';
		$papel = in_array( $tono, array( 'warning', 'danger' ), true ) ? 'alert' : 'status';
		$ancla = $anchor ? ' id="' . self::NOTICE_ANCHOR . '" tabindex="-1"' : '';
		return '<' . ( 'div' === $tag ? 'div' : 'p' ) . ' class="' . esc_attr( Assets::alert_class( $tono ) ) . '" role="' . $papel . '"' . $ancla . '>';
	}

	/**
	 * Lo que se dice por defecto sobre por qué este recuadro solo lo ve una persona.
	 */
	public const ADMIN_BOX_WHY = 'Este recuadro solo lo ve quien administra el aplicativo. Ningún otro perfil lo ve ni puede cambiar lo que hay dentro.';

	/**
	 * The yellow box: what only the administration sees.
	 *
	 * Una convención del aplicativo: cualquier cosa que solo vea quien
	 * administra —desarchivar, por ejemplo (ADR-0023)— va aquí dentro, y así
	 * se reconoce a la primera sin leerla. El amarillo no es el único aviso:
	 * la etiqueta «Solo administración» va escrita, y debajo una línea que
	 * explica por qué.
	 *
	 * @param string $titulo      Heading of the box.
	 * @param string $cuerpo      Its contents, already escaped.
	 * @param string $explicacion Why only this person sees it; the default one when empty.
	 * @return string
	 */
	public static function admin_box( string $titulo, string $cuerpo, string $explicacion = '' ): string {
		$porque = '' !== $explicacion ? $explicacion : self::ADMIN_BOX_WHY;

		ob_start();
		?>
		<section class="prc-solo-admin">
			<p class="prc-solo-admin-marca">
				<svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 1 3 5v6c0 5 3.8 9.7 9 11 5.2-1.3 9-6 9-11V5l-9-4Zm0 6a2 2 0 0 1 2 2v1h.5a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-.5.5h-5a.5.5 0 0 1-.5-.5v-4a.5.5 0 0 1 .5-.5H10V9a2 2 0 0 1 2-2Zm0 1.2A.8.8 0 0 0 11.2 9v1h1.6V9a.8.8 0 0 0-.8-.8Z"/></svg>
				Solo administración
			</p>
			<?php if ( '' !== $titulo ) : ?>
				<h3 class="prc-solo-admin-titulo"><?php echo esc_html( $titulo ); ?></h3>
			<?php endif; ?>
			<p class="prc-solo-admin-porque"><?php echo esc_html( $porque ); ?></p>
			<div class="prc-solo-admin-cuerpo">
				<?php echo $cuerpo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- lo pinta quien llama, ya escapado. ?>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Where to go back to after saving something.
	 *
	 * `wp_get_referer()` devuelve `false` justo cuando el referer coincide con
	 * la propia URL, que es siempre en un formulario que se envía a sí mismo
	 * (`action=""` o `action="#prc-aviso"`: el referer no lleva el fragmento). Se usa el
	 * referer crudo y, si no hay, una página del aplicativo: nunca la portada
	 * del sitio.
	 *
	 * @param string $section Section to fall back to.
	 * @return string
	 */
	public static function back_url( string $section = 'workspace' ): string {
		$destino = wp_validate_redirect( (string) wp_get_raw_referer(), '' );
		if ( '' !== $destino ) {
			return $destino;
		}
		$url = self::url( $section );
		return '' !== $url ? $url : home_url( '/' );
	}

	/**
	 * Send a response header, unless the response already started.
	 *
	 * En tests la salida ya empezó —PHPUnit escribe la suya— y `header()`
	 * avisa; aquí se salta, que es lo que hace `nocache_headers()` de
	 * WordPress. En producción se manda siempre.
	 *
	 * @param string $linea Header line, `Nombre: valor`.
	 * @return void
	 */
	public static function send_header( string $linea ): void {
		if ( ! headers_sent() ) {
			header( $linea );
		}
	}

	/**
	 * Leave the request: redirect if given a URL, then stop.
	 *
	 * El único `exit` del aplicativo. En tests, el filtro `prc_exit_throws` lo
	 * convierte en una excepción {@see ExitSignal} con la URL, que el test
	 * captura.
	 *
	 * @param string $url Where to go; empty when a document was just served.
	 * @return void
	 * @throws ExitSignal Under the tests filter, instead of leaving.
	 */
	public static function leave( string $url = '' ): void {
		$url = self::to_notice( $url );
		if ( apply_filters( 'prc_exit_throws', false, $url ) ) {
			throw new ExitSignal( $url ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- la URL viaja para que el test la lea; no se imprime.
		}
		if ( '' !== $url ) {
			wp_safe_redirect( $url );
		}
		exit;
	}

	/**
	 * The address to come back to, pointing at the notice when it carries one.
	 *
	 * Todas las pantallas dicen el resultado de un POST con `?aviso=`; la
	 * vuelta lleva al aviso, salvo que ya lleve a otro sitio de la página.
	 *
	 * @param string $url Where to go.
	 * @return string
	 */
	private static function to_notice( string $url ): string {
		if ( '' === $url || false !== strpos( $url, '#' ) ) {
			return $url;
		}
		$args = wp_parse_args( (string) wp_parse_url( $url, PHP_URL_QUERY ) );
		return isset( $args['aviso'] ) ? $url . '#' . self::NOTICE_ANCHOR : $url;
	}

	/**
	 * One of the app icons, inline.
	 *
	 * **En línea y no con la tipografía de iconos de Bootstrap** aunque esté
	 * cargada: el aplicativo tiene que seguir entendiéndose sin Bootstrap
	 * ({@see Assets::has_bootstrap()}), y un icono que no llega deja un botón
	 * sin nada dentro. Con `currentColor` heredan el color del botón.
	 *
	 * Van siempre con `aria-hidden`: lo que dice qué hace el botón es su texto,
	 * que va al lado en `.screen-reader-text` y en el `title`.
	 *
	 * @param string $nombre Icon name.
	 * @return string Empty when there is no such icon.
	 */
	public static function icon( string $nombre ): string {
		$caminos = array(
			// Lápiz: editar.
			'lapiz' => '<path fill="currentColor" d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8.4 17.6l-3.9.9.9-3.9L16.5 3.5Z"/>',
			// Ojo: ver la ficha pública.
			'ojo'   => '<path fill="currentColor" d="M12 5c-5 0-9 4.5-9 7s4 7 9 7 9-4.5 9-7-4-7-9-7Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Zm0-2a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/>',
		);
		if ( ! isset( $caminos[ $nombre ] ) ) {
			return '';
		}
		return '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false">'
			. $caminos[ $nombre ] . '</svg>';
	}

	/**
	 * Plus sign, inline.
	 *
	 * @return string
	 */
	public static function icon_plus(): string {
		return '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">'
			. '<path fill="currentColor" d="M12 4a1 1 0 0 1 1 1v6h6a1 1 0 1 1 0 2h-6v6a1 1 0 1 1-2 0v-6H5a1 1 0 1 1 0-2h6V5a1 1 0 0 1 1-1Z"/>'
			. '</svg>';
	}
}
