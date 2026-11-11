<?php
/**
 * Tests for the help screen: guides, index, technical help, menu and contextual links.
 *
 * @package Prc
 */

use Prc\Meta\ProcedureMetaKeys;
use Prc\Domain\ProcedureState;
use Prc\PublicFront\ApplyForm;
use Prc\PublicFront\Help;
use Prc\PublicFront\HelpContent;
use Prc\PublicFront\ProcedureEditor;
use Prc\PublicFront\ProcedureView;
use Prc\PublicFront\Shell;
use Prc\PublicFront\View\ApplyFormView;
use Prc\PublicFront\View\HelpView;
use Prc\PublicFront\View\ProcedureChrome;
use Prc\PublicFront\View\ProcedureEditorView;

/**
 * La ayuda del aplicativo (ADR-0045).
 *
 * Es una pantalla más, pública como la portada: dos guías y las preguntas
 * frecuentes, con el contenido escrito en el código y la ayuda técnica sacada
 * de la configuración. Lo que se prueba es que se lee sin sesión, que cada
 * guía tiene su dirección y su índice, que lo configurado se sanea, que
 * «Ayuda» está siempre en el menú y que las pantallas que más dudas levantan
 * enlazan a su apartado.
 */
class Test_Help extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Con el aplicativo, sus páginas y un catálogo de un centro.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
		$this->pages();
		remove_all_filters( 'prc_centres' );
		add_filter(
			'prc_centres',
			static function (): array {
				return array(
					array(
						'code'      => 'C0001',
						'name'      => 'Centro de prueba Norte',
						'ownership' => ProcedureMetaKeys::OWNERSHIP_PUBLIC,
					),
				);
			}
		);
		$_GET = array();
	}

	/**
	 * Sin dejar nada en la petición para la prueba siguiente.
	 */
	public function tear_down() {
		$_GET = array();
		parent::tear_down();
	}

	/**
	 * Un procedimiento publicado con el plazo abierto.
	 *
	 * @return int
	 */
	private function abierto(): int {
		return $this->procedure(
			$this->administrator(),
			array( $this->area() ),
			array(
				ProcedureMetaKeys::OPENS_AT  => gmdate( 'Y-m-d', (int) strtotime( ProcedureState::today() . ' -1 days' ) ),
				ProcedureMetaKeys::CLOSES_AT => gmdate( 'Y-m-d', (int) strtotime( ProcedureState::today() . ' +1 days' ) ),
			),
			array( 'post_title' => 'Red de prueba' )
		);
	}

	/**
	 * La pantalla pintada con una guía pedida.
	 *
	 * @param string $guia Value of ?guia=, empty for none.
	 * @return string
	 */
	private function pintar( string $guia = '' ): string {
		$_GET = '' === $guia ? array() : array( Help::ARG_GUIDE => $guia );
		return HelpView::html( Help::model() );
	}

	// ─── la pantalla ───────────────────────────────────────────────────────

	/**
	 * La ayuda tiene su página y se lee sin sesión: no manda al acceso.
	 */
	public function test_the_help_page_is_public() {
		$this->assertSame( 'ayuda', Shell::SLUGS['help'] );
		$this->assertSame( 'prc_help', Shell::SHORTCODES['help'] );
		$this->assertTrue( Shell::is_public( 'help' ) );
		$this->assertTrue( shortcode_exists( Help::SHORTCODE ) );

		$this->acting_as( 0 );
		$this->go_to( Shell::url( 'help' ) );
		$this->assertSame( 'help', Shell::current_section() );
		add_filter( 'prc_exit_throws', '__return_true' );
		Shell::require_login();
		$this->assertStringContainsString( 'Cómo presentar una solicitud', do_shortcode( '[prc_help]' ) );
	}

	/**
	 * Cada `?guia=` pinta la suya, y lo que no se conoce cae en la de solicitar.
	 */
	public function test_each_guide_has_its_address() {
		$this->acting_as( 0 );

		$this->assertSame( HelpContent::GUIDE_APPLY, Help::model()['guide'], 'por defecto, la de solicitar' );

		$solicitar = $this->pintar( 'solicitar' );
		$this->assertStringContainsString( 'id="antes-de-empezar"', $solicitar );
		$this->assertStringNotContainsString( 'id="crear-o-duplicar"', $solicitar );

		$gestionar = $this->pintar( 'gestionar' );
		$this->assertStringContainsString( 'id="crear-o-duplicar"', $gestionar );
		$this->assertStringContainsString( 'id="taller-preguntas"', $gestionar );
		$this->assertStringNotContainsString( 'id="antes-de-empezar"', $gestionar );

		$preguntas = $this->pintar( 'preguntas' );
		$this->assertStringContainsString( 'id="no-veo-el-boton-solicitar"', $preguntas );

		$this->assertStringContainsString( 'id="antes-de-empezar"', $this->pintar( 'otra-cosa' ), 'una guía desconocida cae en la de solicitar' );
		$this->assertStringContainsString( 'id="antes-de-empezar"', $this->pintar( '<script>' ) );
	}

	/**
	 * Las tres pestañas son enlaces que recargan con su `?guia=`, y la que se
	 * está leyendo va marcada.
	 */
	public function test_the_tabs_are_links_to_each_guide() {
		$html = $this->pintar( 'gestionar' );

		foreach ( array( 'Cómo presentar una solicitud', 'Cómo dar de alta un procedimiento', 'Preguntas frecuentes' ) as $rotulo ) {
			$this->assertStringContainsString( '>' . $rotulo . '</a>', $html, $rotulo );
		}
		foreach ( array_keys( HelpContent::GUIDES ) as $guia ) {
			$this->assertStringContainsString( esc_url( Help::url( $guia ) ), $html, $guia );
		}
		$this->assertMatchesRegularExpression( '/<a[^>]*aria-current="page"[^>]*>Cómo dar de alta un procedimiento<\/a>/u', $html );
	}

	/**
	 * El índice de cada guía enlaza a anclas que existen en esa misma guía.
	 */
	public function test_the_index_links_to_anchors_that_exist() {
		foreach ( array_keys( HelpContent::GUIDES ) as $guia ) {
			$html = $this->pintar( $guia );
			$this->assertSame( 1, preg_match( '/<nav class="prc-ayuda__indice"[^>]*>(.*?)<\/nav>/s', $html, $indice ), $guia );
			preg_match_all( '/href="#([^"]+)"/', $indice[1], $anclas );
			$this->assertCount( count( HelpContent::sections( $guia ) ), $anclas[1], $guia . ': un enlace por apartado' );
			foreach ( $anclas[1] as $ancla ) {
				$this->assertSame( 1, substr_count( $html, 'id="' . $ancla . '"' ), $guia . ' #' . $ancla );
				$this->assertMatchesRegularExpression( '/^[a-z0-9-]+$/', $ancla, 'en castellano sin tildes ni mayúsculas' );
			}
		}
	}

	/**
	 * Los identificadores no se repiten entre guías: un enlace a un apartado
	 * lleva siempre al mismo.
	 */
	public function test_the_anchors_are_unique() {
		$todas = array();
		foreach ( array_keys( HelpContent::GUIDES ) as $guia ) {
			foreach ( HelpContent::sections( $guia ) as $apartado ) {
				$todas[] = $apartado['id'];
			}
		}
		$this->assertSame( count( $todas ), count( array_unique( $todas ) ) );
	}

	/**
	 * Ningún texto de la ayuda lleva una etiqueta fuera de la lista permitida,
	 * y los enlaces internos se resuelven a páginas del aplicativo.
	 */
	public function test_the_content_only_uses_the_allowed_tags() {
		foreach ( array_keys( HelpContent::GUIDES ) as $guia ) {
			foreach ( HelpContent::raw( $guia ) as $apartado ) {
				$this->assertSame( wp_kses( $apartado['html'], HelpContent::ALLOWED ), $apartado['html'], $guia . ' #' . $apartado['id'] );
				$this->assertSame( wp_strip_all_tags( $apartado['title'] ), $apartado['title'] );
				foreach ( $apartado['shots'] as $captura ) {
					$this->assertSame( '', $captura['image'], 'las capturas llegan más tarde' );
					$this->assertNotSame( '', $captura['caption'] );
				}
			}
			foreach ( HelpContent::sections( $guia ) as $apartado ) {
				$this->assertStringNotContainsString( '%', $apartado['html'], $guia . ' #' . $apartado['id'] . ': marcador sin resolver' );
				preg_match_all( '/href="([^"]+)"/', $apartado['html'], $enlaces );
				foreach ( $enlaces[1] as $enlace ) {
					$this->assertStringStartsWith( home_url( '/' ), html_entity_decode( $enlace ), 'solo enlaces internos' );
				}
			}
		}
	}

	/**
	 * Cada hueco de captura se pinta como tal, con su texto, hasta que llegue la imagen.
	 */
	public function test_the_missing_screenshots_are_marked() {
		$html = $this->pintar( 'solicitar' );
		$this->assertStringContainsString( 'prc-ayuda__captura--pendiente', $html );
		$this->assertStringContainsString( 'Captura pendiente: cabecera con la sesión iniciada', $html );
		$this->assertStringNotContainsString( '<img', $html );
	}

	// ─── la ayuda técnica ──────────────────────────────────────────────────

	/**
	 * Sin configurar, solo remite al correo de contacto de cada procedimiento.
	 */
	public function test_the_technical_help_is_empty_until_configured() {
		$html = $this->pintar();
		$this->assertStringContainsString( '¿Necesita ayuda técnica?', $html );
		$this->assertStringContainsString( 'correo de contacto', $html );
		$this->assertSame( '', Help::model()['support'] );
	}

	/**
	 * Lo configurado sale tal cual, y lo que no está permitido se cae.
	 */
	public function test_the_technical_help_comes_from_the_configuration_and_is_sanitised() {
		add_filter(
			'prc_chrome',
			static function ( array $chrome ): array {
				$chrome['help_contact'] = '<p>Escriba a <a href="mailto:ayuda@example.org">ayuda@example.org</a>.</p><script>alert(1)</script><img src="x" onerror="alert(2)">';
				return $chrome;
			}
		);

		$html = $this->pintar();
		$this->assertStringContainsString( '<a href="mailto:ayuda@example.org">ayuda@example.org</a>', $html );
		$this->assertStringNotContainsString( '<script>alert(1)', $html );
		$this->assertStringNotContainsString( 'onerror', $html );
		$this->assertStringNotContainsString( '<img', $html );
	}

	// ─── el menú ───────────────────────────────────────────────────────────

	/**
	 * «Ayuda» está en el menú sin configurar nada, también sin sesión.
	 */
	public function test_the_help_entry_is_always_in_the_menu() {
		$url = esc_url( Shell::url( 'help' ) );

		$this->acting_as( 0 );
		$html = Shell::render( 'Procedimientos', '', '' );
		$this->assertStringContainsString( '>Ayuda</a>', $html );
		$this->assertStringContainsString( 'href="' . $url . '"', $html );

		$this->acting_as( $this->school_head( 'C0001' ) );
		$this->assertStringContainsString( '>Ayuda</a>', Shell::render( 'Mi centro', '', '' ) );
	}

	// ─── las direcciones ───────────────────────────────────────────────────

	/**
	 * `Help::url()` lleva a la guía y, si se pide, a un apartado.
	 */
	public function test_url_points_to_a_guide_and_a_section() {
		$pagina = (string) get_permalink( get_page_by_path( 'ayuda' ) );

		$this->assertSame( add_query_arg( Help::ARG_GUIDE, 'gestionar', $pagina ), Help::url( 'gestionar' ) );
		$this->assertSame( add_query_arg( Help::ARG_GUIDE, 'preguntas', $pagina ) . '#varias-casillas', Help::url( 'preguntas', 'varias-casillas' ) );
		$this->assertSame( add_query_arg( Help::ARG_GUIDE, 'solicitar', $pagina ), Help::url( 'no-existe' ), 'una guía desconocida es la de solicitar' );
		$this->assertStringEndsWith( '#antes-de-empezar', Help::url( 'solicitar', 'Antes de empezar' ), 'el ancla se sanea' );

		wp_delete_post( (int) get_page_by_path( 'ayuda' )->ID, true );
		$this->assertSame( '', Help::url( 'solicitar' ), 'sin página, sin enlace' );
	}

	// ─── los enlaces contextuales ──────────────────────────────────────────

	/**
	 * Cada enlace contextual apunta a un apartado que existe.
	 *
	 * @param string $html  The screen.
	 * @param string $guia  Guide key.
	 * @param string $ancla Section id.
	 * @return void
	 */
	private function assert_help_link( string $html, string $guia, string $ancla ): void {
		$this->assertStringContainsString( 'Consulte la ayuda', $html );
		$this->assertStringContainsString( esc_url( Help::url( $guia, $ancla ) ), $html );
		$this->assertContains( $ancla, array_column( HelpContent::sections( $guia ), 'id' ) );
	}

	/**
	 * La ficha pública, junto al acceso.
	 */
	public function test_the_public_card_links_to_the_help() {
		$id = $this->abierto();
		$this->acting_as( 0 );
		$this->assert_help_link( ProcedureChrome::html( ProcedureView::model( $id ) ), 'solicitar', 'antes-de-empezar' );
	}

	/**
	 * La cabecera de la solicitud.
	 */
	public function test_the_apply_form_links_to_the_help() {
		$id = $this->abierto();
		$this->acting_as( $this->school_head( 'C0001' ) );
		$_GET[ ApplyForm::ARG_PROCEDURE ] = (string) $id;
		$this->assert_help_link( ApplyFormView::html( ApplyForm::model() ), 'solicitar', 'rellenar-la-solicitud' );
	}

	/**
	 * El alta de un procedimiento nuevo y el panel «Preguntas» del taller.
	 */
	public function test_the_workshop_links_to_the_help() {
		$area = $this->area( 'Área de prueba' );
		$this->acting_as( $this->manager( array( $area ) ) );

		$this->assert_help_link( ProcedureEditorView::html( ProcedureEditor::model() ), 'gestionar', 'crear-o-duplicar' );

		$id                                     = $this->procedure( $this->administrator(), array( $area ) );
		$_GET[ ProcedureEditor::ARG_PROCEDURE ] = (string) $id;
		$_GET[ ProcedureEditor::ARG_PANEL ]     = ProcedureEditor::PANEL_QUESTIONS;
		$this->assert_help_link( ProcedureEditorView::html( ProcedureEditor::model() ), 'gestionar', 'taller-preguntas' );
	}
}
