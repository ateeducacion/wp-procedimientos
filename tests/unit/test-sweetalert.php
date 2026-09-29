<?php
/**
 * Tests for the loose SweetAlert2 snippet.
 *
 * @package Prc
 */

use Prc\PublicFront\Assets;
use Prc\PublicFront\Shell;

/**
 * De dónde sale SweetAlert2, dónde se carga y con qué garantía.
 *
 * Lo que se puede comprobar en PHP es el encolado: la versión, el SRI y que
 * solo está donde corre el guion que lo usa. Lo que pasa dentro del diálogo es
 * del navegador.
 */
class Test_Sweetalert extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Con las páginas del aplicativo ya creadas.
	 */
	public function set_up() {
		parent::set_up();
		$this->pages();
	}

	/**
	 * Las colas de guiones son globales: se dejan como estaban.
	 */
	public function tear_down() {
		wp_dequeue_script( 'sweetalert2' );
		wp_deregister_script( 'sweetalert2' );
		wp_dequeue_script( 'prc-app' );
		parent::tear_down();
	}

	/**
	 * Ponerse en una pantalla del aplicativo, con su guion ya encolado.
	 *
	 * @return void
	 */
	private function en_el_aplicativo(): void {
		$this->acting_as( $this->administrator() );
		$this->go_to( (string) get_permalink( get_page_by_path( Shell::SLUGS['home'] ) ) );
		$this->assertTrue( Shell::is_app_page() );
		Assets::enqueue();
	}

	/**
	 * La misma versión en los tres sitios: package.json, la URL y el `$ver`.
	 *
	 * Sin esto, subir la versión en `package.json` y no tocar ni la URL, ni el
	 * `$ver`, ni el `integrity` deja un SRI que el navegador rechaza **en
	 * silencio** (ADR-0006). Se comprueba la forma y no el número: clavar aquí
	 * la versión sería un cuarto sitio donde vive.
	 */
	public function test_the_pinned_version_matches_package_json() {
		$package = dirname( __DIR__, 2 ) . '/package.json';
		$this->assertFileIsReadable( $package );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- fichero del repositorio, no una petición remota.
		$datos    = json_decode( (string) file_get_contents( $package ), true );
		$esperada = (string) $datos['devDependencies']['sweetalert2'];
		$vendor   = prc_sweetalert_vendor();

		$this->assertMatchesRegularExpression( '/^\d+\.\d+\.\d+$/', $esperada, 'versión exacta, sin ^ ni ~' );
		$this->assertSame( $esperada, $vendor['ver'] );
		$this->assertStringStartsWith( 'https://cdn.jsdelivr.net/npm/sweetalert2@' . $esperada . '/', $vendor['url'] );
		$this->assertStringStartsWith( 'sha384-', $vendor['sri'] );
	}

	/**
	 * Se carga donde corre el guion del aplicativo, y con su versión.
	 */
	public function test_it_is_enqueued_where_the_app_script_runs() {
		$this->en_el_aplicativo();

		prc_sweetalert_assets();

		$vendor = prc_sweetalert_vendor();
		$this->assertTrue( wp_script_is( 'sweetalert2', 'enqueued' ) );
		$this->assertSame( $vendor['url'], wp_scripts()->registered['sweetalert2']->src );
		$this->assertSame( $vendor['ver'], wp_scripts()->registered['sweetalert2']->ver );
	}

	/**
	 * Y en ninguna otra página del subsitio.
	 */
	public function test_it_is_not_enqueued_outside_the_app() {
		$otra = (int) self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => 'Una página cualquiera del subsitio.',
			)
		);
		$this->go_to( (string) get_permalink( $otra ) );

		prc_sweetalert_assets();

		$this->assertFalse( wp_script_is( 'prc-app', 'enqueued' ) );
		$this->assertFalse( wp_script_is( 'sweetalert2', 'registered' ) );
	}

	/**
	 * El filtro apaga la librería sin desactivar el snippet.
	 *
	 * Y apagarla no quita la confirmación: el guion se va al `confirm()`.
	 */
	public function test_the_filter_switches_it_off_without_disabling_the_snippet() {
		$this->en_el_aplicativo();
		add_filter( 'prc_load_sweetalert', '__return_false' );

		prc_sweetalert_assets();

		$this->assertFalse( wp_script_is( 'sweetalert2', 'registered' ) );
	}

	/**
	 * El SRI se pone solo cuando la URL sigue siendo la del CDN (ADR-0006).
	 */
	public function test_integrity_is_added_to_the_cdn_tag_only() {
		$vendor = prc_sweetalert_vendor();
		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- es la etiqueta que recibe el filtro, no un guion que se cargue aquí.
		$etiqueta = "<script src='" . $vendor['url'] . '?ver=' . $vendor['ver'] . "' id='sweetalert2-js'></script>";

		$this->assertStringContainsString(
			'integrity="' . $vendor['sri'] . '" crossorigin="anonymous" src=',
			prc_sweetalert_sri( $etiqueta, 'sweetalert2', $vendor['url'] )
		);

		// En desarrollo la URL es local: el hash ni cuadraría ni hace falta.
		$this->assertSame(
			$etiqueta,
			prc_sweetalert_sri( $etiqueta, 'sweetalert2', 'https://example.org/wp-content/prc-dev/node_modules/sweetalert2/dist/sweetalert2.all.min.js' )
		);

		// Y a lo que no es nuestro no se le toca la etiqueta.
		$this->assertSame( $etiqueta, prc_sweetalert_sri( $etiqueta, 'otra-cosa', 'https://cdn.jsdelivr.net/npm/algo@1.0.0/algo.js' ) );
	}
}
