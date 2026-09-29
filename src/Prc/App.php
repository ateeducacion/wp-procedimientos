<?php
/**
 * Application bootstrap for the procedures CPT app.
 *
 * @package Prc
 */

namespace Prc;

use Prc\Access\ProcedureAccess;
use Prc\Admin\ProcedureAdmin;
use Prc\Admin\Settings;
use Prc\Centre\CentreCatalogueSync;
use Prc\Meta\ApplicationMetaRegistration;
use Prc\Meta\ProcedureMetaRegistration;
use Prc\PostType\ApplicationPostType;
use Prc\PostType\ProcedurePostType;
use Prc\PublicFront\Applications;
use Prc\PublicFront\ApplicationFiles;
use Prc\PublicFront\ApplyForm;
use Prc\PublicFront\Assets;
use Prc\PublicFront\EditLock;
use Prc\PublicFront\Home;
use Prc\PublicFront\MyCentre;
use Prc\PublicFront\ProcedureEditor;
use Prc\PublicFront\ProcedureView;
use Prc\PublicFront\Shell;
use Prc\PublicFront\Workspace;
use Prc\Taxonomy\ProcedureTaxonomies;

/**
 * Wires hooks for the modular procedures application.
 *
 * Se llama `App` y no `Plugin` a propósito: el validador de Code Snippets
 * compara los nombres declarados ignorando el namespace, así que un
 * `Prc\Plugin` chocaría con el `Code_Snippets\Plugin` del propio plugin y el
 * snippet se rechazaría con «code did not pass validation».
 */
final class App {

	/**
	 * Option holding the parent page the application pages hang from.
	 *
	 * La escribe `scripts/setup-pages.php` y la lee {@see page_slug()}: sin
	 * ella `get_page_by_path()` buscaría «solicitud» en la raíz del sitio y
	 * no encontraría la hija de «/procedimientos/solicitud».
	 */
	public const PAGES_PARENT = 'prc_pages_parent';

	/**
	 * The front-end modules, in the order they hook.
	 *
	 * El armazón primero: manda al acceso a quien no ha entrado y deja
	 * registrada la hoja de estilos que las pantallas dan por puesta. Luego
	 * cada pantalla engancha su shortcode y, si muta, su manejador de POST en
	 * `init` 20, después de los tipos y sus capacidades. La ficha pública del
	 * procedimiento va al final: no es una pantalla del armazón (ADR-0022).
	 *
	 * @var string[]
	 */
	private const FRONT = array(
		Assets::class,
		Shell::class,
		EditLock::class,
		Applications::class,
		// Los documentos privados de una solicitud: el manejador de descarga y
		// la limpieza al borrarla. **No son adjuntos de WordPress** y no tocan
		// la biblioteca de medios (ADR-0028).
		ApplicationFiles::class,
		ApplyForm::class,
		MyCentre::class,
		Workspace::class,
		ProcedureEditor::class,
		Home::class,
		ProcedureView::class,
	);

	/**
	 * Boot the application (idempotent).
	 *
	 * @return void
	 */
	public static function boot(): void {
		static $booted = false;
		if ( $booted ) {
			return;
		}
		$booted = true;

		add_action( 'init', array( ProcedureTaxonomies::class, 'register' ), 9 );
		add_action( 'init', array( ProcedurePostType::class, 'register' ), 10 );
		add_action( 'init', array( ApplicationPostType::class, 'register' ), 10 );
		add_action( 'init', array( ProcedurePostType::class, 'grant_caps_to_roles' ), 11 );

		ProcedureMetaRegistration::register();
		ApplicationMetaRegistration::register();
		ProcedureAccess::register();

		// Las páginas cuelgan de una madre si quien despliega lo pidió, y los
		// enlaces del armazón tienen que llevar la ruta entera.
		add_filter( 'prc_page_slug', array( self::class, 'page_slug' ), 10, 2 );

		// El frontal se engancha módulo a módulo y solo si está cargado: si el
		// snippet llega a medias, el sitio no se rompe.
		foreach ( self::FRONT as $modulo ) {
			if ( class_exists( $modulo ) && method_exists( $modulo, 'register' ) ) {
				call_user_func( array( $modulo, 'register' ) );
			}
		}

		ProcedureAdmin::register();
		Settings::register();
		CentreCatalogueSync::register_cron();
	}

	/**
	 * Prefix a section slug with the parent page, when there is one.
	 *
	 * @param string $slug    Page path proposed by the shell.
	 * @param string $section Section key.
	 * @return string
	 */
	public static function page_slug( string $slug, string $section = '' ): string {
		unset( $section );
		$padre = trim( (string) get_option( self::PAGES_PARENT, '' ), '/' );
		if ( '' === $padre || '' === $slug || 0 === strpos( $slug, $padre . '/' ) ) {
			return $slug;
		}
		return $padre . '/' . $slug;
	}
}
