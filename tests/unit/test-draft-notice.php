<?php
/**
 * Tests for the notice of a procedure that is not published yet.
 *
 * @package Prc
 */

use Prc\PostType\ProcedurePostType;
use Prc\PublicFront\DraftNotice;
use Prc\PublicFront\View\ProcedureChrome;

/**
 * Un procedimiento en borrador dice que lo está, y no «no se encontró la página» (ADR-0047).
 */
class Test_Draft_Notice extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Con el aplicativo y sus páginas.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
		$this->pages();
	}

	/**
	 * The address of a procedure by number, the one a draft without a name has.
	 *
	 * @param int $id Procedure.
	 * @return string
	 */
	private function by_number( int $id ): string {
		return add_query_arg(
			array(
				'post_type' => ProcedurePostType::POST_TYPE,
				'p'         => $id,
			),
			home_url( '/' )
		);
	}

	/**
	 * Visit an address and return what the notice serves, '' when it lets it through.
	 *
	 * @param string $url Address.
	 * @return string
	 */
	private function visit( string $url ): string {
		$this->go_to( $url );
		return $this->served( array( DraftNotice::class, 'maybe_show' ) );
	}

	/**
	 * Engancha antes de que WordPress elija la plantilla del 404 o adivine otra página.
	 */
	public function test_it_hooks_before_the_404_template() {
		$this->assertSame( DraftNotice::PRIORITY, has_action( 'template_redirect', array( DraftNotice::class, 'maybe_show' ) ) );
		$this->assertLessThan( 10, DraftNotice::PRIORITY, 'redirect_canonical va en la 10' );
	}

	/**
	 * Sin sesión, un borrador dice que lo es, sin su título, con el acceso para responsables.
	 */
	public function test_a_draft_says_so_and_offers_to_log_in() {
		$id = $this->procedure(
			$this->administrator(),
			array( $this->area() ),
			array(),
			array(
				'post_status' => 'draft',
				'post_title'  => 'Convocatoria secreta',
				'post_name'   => 'convocatoria-secreta',
			)
		);
		wp_set_current_user( 0 );

		$html = $this->visit( $this->by_number( $id ) );

		$this->assertStringContainsString( 'modo borrador', $html );
		$this->assertStringContainsString( 'Acceso para responsables', $html );
		$this->assertStringContainsString( 'wp-login.php', $html );
		$this->assertStringContainsString( 'redirect_to=', $html, 'el acceso vuelve al propio procedimiento' );
		$this->assertStringContainsString( '<meta name="robots" content="noindex" />', $html );
		$this->assertStringNotContainsString( 'Convocatoria secreta', $html, 'un borrador no cuenta lo que lleva' );
		$this->assertStringNotContainsString( '<img', $html, 'sin logo configurado, no hay logo' );
	}

	/**
	 * Uno que estuvo publicado y vuelve a revisión avisa también por su ruta de siempre.
	 */
	public function test_a_procedure_back_to_review_gets_the_notice_by_its_path() {
		$this->set_permalink_structure( '/%postname%/' );
		// Las reglas del tipo se pierden al reiniciar las de reescritura.
		ProcedurePostType::register();
		flush_rewrite_rules( false );
		// Un borrador nuevo no tiene nombre todavía: el nombre lo pone publicarlo.
		$admin = $this->administrator();
		$id    = $this->procedure( $admin, array( $this->area() ), array(), array( 'post_name' => 'vuelve-a-revision' ) );
		// Quien lo devuelve a revisión puede publicar: si no, WordPress le quita el nombre.
		wp_set_current_user( $admin );
		wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => 'pending',
			)
		);
		wp_set_current_user( 0 );

		$this->assertTrue( DraftNotice::is_being_prepared( $id ) );
		$html = $this->visit( home_url( '/procedimiento/vuelve-a-revision/' ) );

		$this->assertStringContainsString( 'modo borrador', $html );
	}

	/**
	 * Con sesión y sin permiso, dice con qué cuenta se entró y que esa no puede verlo.
	 */
	public function test_a_logged_in_account_without_permission_is_told_so() {
		$id    = $this->procedure( $this->administrator(), array( $this->area() ), array(), array( 'post_status' => 'draft' ) );
		$quien = (int) self::factory()->user->create(
			array(
				'role'         => 'subscriber',
				'display_name' => 'Persona sin permiso',
			)
		);
		wp_set_current_user( $quien );

		$html = $this->visit( $this->by_number( $id ) );

		$this->assertStringContainsString( 'Persona sin permiso', $html );
		$this->assertStringContainsString( 'no puede ver este borrador', $html );
		$this->assertStringContainsString( 'Entrar con otra cuenta', $html );
		$this->assertStringNotContainsString( 'Acceso para responsables', $html );
	}

	/**
	 * Quien puede verlo ve la ficha; lo publicado y lo que está en la papelera no pasan por aquí.
	 */
	public function test_it_only_answers_procedures_being_prepared() {
		$admin = $this->administrator();
		$id    = $this->procedure( $admin, array( $this->area() ), array(), array( 'post_status' => 'draft' ) );
		$url   = $this->by_number( $id );

		wp_set_current_user( $admin );
		$this->assertSame( '', $this->visit( $url ), 'quien lo prepara ve el procedimiento, no el aviso' );

		wp_set_current_user( 0 );
		wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => 'publish',
			)
		);
		$this->assertSame( '', $this->visit( $url ), 'publicado, no hay aviso' );

		wp_trash_post( $id );
		$this->assertFalse( DraftNotice::is_being_prepared( $id ), 'en la papelera, el 404 de siempre' );
		$this->assertSame( '', $this->visit( $url ), 'en la papelera no hay aviso' );
	}

	/**
	 * Una página cualquiera en borrador no es asunto del aplicativo.
	 */
	public function test_other_post_types_are_not_handled() {
		$page = (int) self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'draft',
			)
		);
		$this->assertFalse( DraftNotice::is_being_prepared( $page ) );
	}

	/**
	 * El logo solo sale si quien despliega lo ha configurado en `prc_chrome`.
	 */
	public function test_the_logo_comes_from_the_chrome_filter() {
		$id = $this->procedure( $this->administrator(), array( $this->area() ), array(), array( 'post_status' => 'draft' ) );
		wp_set_current_user( 0 );
		add_filter(
			ProcedureChrome::HOOK,
			static function ( array $chrome ): array {
				$chrome['brand_logo'] = 'https://example.org/logo.png';
				$chrome['brand_alt']  = 'Logo de prueba';
				return $chrome;
			}
		);

		$html = $this->visit( $this->by_number( $id ) );

		$this->assertStringContainsString( 'src="https://example.org/logo.png"', $html );
		$this->assertStringContainsString( 'alt="Logo de prueba"', $html );
	}
}
