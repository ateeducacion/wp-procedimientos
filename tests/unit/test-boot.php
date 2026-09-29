<?php
/**
 * Tests for App::boot(): what it hooks and that it only hooks it once.
 *
 * @package Prc
 */

use Prc\Access\ProcedureAccess;
use Prc\Admin\ProcedureAdmin;
use Prc\Admin\Settings;
use Prc\App;
use Prc\Meta\ApplicationMetaRegistration;
use Prc\Meta\ProcedureMetaRegistration;
use Prc\PostType\ApplicationPostType;
use Prc\PostType\ProcedurePostType;
use Prc\Taxonomy\ProcedureTaxonomies;

/**
 * El arranque del aplicativo.
 *
 * `App::boot()` se llama desde `src/Prc/bootstrap.php` y desde el bundle: si no
 * fuese idempotente, tener los dos activos a la vez engancharía cada módulo dos
 * veces y cada `init` haría el trabajo por duplicado.
 */
class Test_Boot extends WP_UnitTestCase {

	/**
	 * How many callbacks hang off each hook the boot touches, across every priority.
	 *
	 * @return array<string, int>
	 */
	private function enganchadas(): array {
		global $wp_filter;

		$cuenta = array();
		foreach ( array( 'init', 'map_meta_cap', 'pre_get_posts', 'admin_menu', 'prc_page_slug' ) as $gancho ) {
			$total = 0;
			if ( isset( $wp_filter[ $gancho ] ) ) {
				foreach ( $wp_filter[ $gancho ]->callbacks as $prioridad ) {
					$total += count( $prioridad );
				}
			}
			$cuenta[ $gancho ] = $total;
		}
		return $cuenta;
	}

	/**
	 * Un segundo arranque no vuelve a enganchar nada.
	 */
	public function test_boot_is_idempotent() {
		$antes = $this->enganchadas();

		App::boot();
		App::boot();

		$this->assertSame( $antes, $this->enganchadas() );
	}

	/**
	 * Cada módulo cuelga de su gancho y en su prioridad: las taxonomías antes
	 * que los tipos, las capacidades después de los tipos, y las metas al final.
	 */
	public function test_boot_wired_every_module() {
		$this->assertSame( 9, has_action( 'init', array( ProcedureTaxonomies::class, 'register' ) ) );
		$this->assertSame( 10, has_action( 'init', array( ProcedurePostType::class, 'register' ) ) );
		$this->assertSame( 10, has_action( 'init', array( ApplicationPostType::class, 'register' ) ) );
		$this->assertSame( 11, has_action( 'init', array( ProcedurePostType::class, 'grant_caps_to_roles' ) ) );
		$this->assertSame( 12, has_action( 'init', array( ProcedureMetaRegistration::class, 'register_meta' ) ) );
		$this->assertSame( 12, has_action( 'init', array( ApplicationMetaRegistration::class, 'register_meta' ) ) );

		$this->assertSame( 10, has_filter( 'map_meta_cap', array( ProcedureAccess::class, 'map_meta_cap' ) ) );
		$this->assertSame( 10, has_action( 'pre_get_posts', array( ProcedureAdmin::class, 'scope_admin_query' ) ) );
		$this->assertSame( 10, has_action( 'admin_menu', array( Settings::class, 'menu' ) ) );
		$this->assertSame( 10, has_filter( 'prc_page_slug', array( App::class, 'page_slug' ) ) );
	}

	/**
	 * Y al terminar `init`, los dos tipos y las dos taxonomías están montados.
	 */
	public function test_boot_registered_the_post_types_and_the_taxonomies() {
		foreach ( array( ProcedurePostType::POST_TYPE, ApplicationPostType::POST_TYPE ) as $slug ) {
			$this->assertTrue( post_type_exists( $slug ), $slug );
		}
		foreach ( array( ProcedureTaxonomies::AREA, ProcedureTaxonomies::COURSE ) as $slug ) {
			$this->assertTrue( taxonomy_exists( $slug ), $slug );
		}
	}

	/**
	 * Las páginas cuelgan de la madre que diga la opción, y solo una vez.
	 */
	public function test_page_slugs_hang_from_the_parent_page_option() {
		$this->assertSame( 'solicitud', App::page_slug( 'solicitud' ) );

		update_option( App::PAGES_PARENT, '/procedimientos/' );
		$this->assertSame( 'procedimientos/solicitud', App::page_slug( 'solicitud' ) );
		$this->assertSame( 'procedimientos/solicitud', App::page_slug( 'procedimientos/solicitud' ), 'no se prefija dos veces' );
		$this->assertSame( '', App::page_slug( '' ) );
	}
}
