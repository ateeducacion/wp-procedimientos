<?php
/**
 * Tests for the private copy of the resolution and lists PDF.
 *
 * @package Prc
 */

use Prc\Domain\ProcedureCopy;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PublicFront\ApplicationFiles;
use Prc\PublicFront\LinkCopies;
use Prc\PublicFront\ProcedureEditor;
use Prc\PublicFront\View\ProcedureEditorView;

/**
 * Al guardar «Enlaces», el aplicativo se queda una copia privada del PDF
 * (ADR-0048). En público se sigue enlazando la dirección oficial; la copia
 * solo la descarga quien abre el procedimiento en su taller.
 *
 * La red no se toca nunca: cada test contesta él mismo por `pre_http_request`.
 */
class Test_Link_Copies extends WP_UnitTestCase {

	use Prc_Fixtures {
		tear_down as fixtures_tear_down;
	}

	/**
	 * Lo que contesta la «red» simulada: un array de respuesta o un WP_Error.
	 *
	 * @var array<string, mixed>|\WP_Error
	 */
	private $respuesta;

	/**
	 * Las peticiones que se hicieron, con sus argumentos.
	 *
	 * @var array<int, array{url:string, args:array<string, mixed>}>
	 */
	private $pedidas = array();

	/**
	 * Raíz privada propia y red simulada.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->pages();
		add_filter( 'prc_private_files_dir', array( $this, 'raiz_de_prueba' ) );
		add_filter( 'pre_http_request', array( $this, 'red' ), 10, 3 );
		$this->respuesta = $this->respuesta_pdf();
		$this->pedidas   = array();
	}

	/**
	 * Y se la lleva al terminar.
	 *
	 * @return void
	 */
	public function tear_down() {
		global $wp_filesystem;
		if ( $wp_filesystem instanceof \WP_Filesystem_Base && $wp_filesystem->is_dir( $this->raiz_de_prueba() ) ) {
			$wp_filesystem->delete( $this->raiz_de_prueba(), true );
		}
		remove_filter( 'prc_private_files_dir', array( $this, 'raiz_de_prueba' ) );
		remove_filter( 'pre_http_request', array( $this, 'red' ), 10 );
		$this->fixtures_tear_down();
	}

	/**
	 * La raíz privada de los tests.
	 *
	 * @return string
	 */
	public function raiz_de_prueba(): string {
		return rtrim( get_temp_dir(), '/' ) . '/prc-private-test-links';
	}

	/**
	 * La red simulada: anota la petición y contesta lo preparado.
	 *
	 * @param false|array|\WP_Error $pre  Short-circuit value.
	 * @param array<string, mixed>  $args Request args.
	 * @param string                $url  Request URL.
	 * @return array|\WP_Error
	 */
	public function red( $pre, $args, $url ) {
		unset( $pre );
		$this->pedidas[] = array(
			'url'  => (string) $url,
			'args' => (array) $args,
		);
		return $this->respuesta;
	}

	/**
	 * Una respuesta HTTP con ese cuerpo.
	 *
	 * @param string $cuerpo Body.
	 * @param int    $codigo Status.
	 * @param string $tipo   Content-Type.
	 * @return array<string, mixed>
	 */
	private function respuesta( string $cuerpo, int $codigo = 200, string $tipo = 'application/pdf' ): array {
		return array(
			'headers'  => array( 'content-type' => $tipo ),
			'body'     => $cuerpo,
			'response' => array(
				'code'    => $codigo,
				'message' => 'OK',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	/**
	 * Un PDF mínimo.
	 *
	 * @return array<string, mixed>
	 */
	private function respuesta_pdf(): array {
		return $this->respuesta( "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n" );
	}

	/**
	 * Guardar el panel «Enlaces» como quien gestiona el procedimiento.
	 *
	 * @param int                   $uid          Who saves.
	 * @param int                   $procedure_id Procedure.
	 * @param array<string, string> $urls         Links.
	 * @return string Return URL.
	 */
	private function guardar( int $uid, int $procedure_id, array $urls ): string {
		$this->acting_as( $uid );
		$this->post(
			array_merge(
				array(
					ProcedureEditor::FIELD_DO        => ProcedureEditor::OP_LINKS,
					ProcedureEditor::FIELD_PROCEDURE => (string) $procedure_id,
					ProcedureEditor::FIELD_ROW       => '0',
				),
				$urls
			),
			ProcedureEditor::nonce_action( ProcedureEditor::OP_LINKS, 0 ),
			ProcedureEditor::nonce_name( ProcedureEditor::OP_LINKS, 0 )
		);
		return (string) $this->exit_url( array( ProcedureEditor::class, 'handle' ) );
	}

	/**
	 * El aviso que pinta el taller después de volver con esa URL.
	 *
	 * @param string $url Return URL.
	 * @return array{tipo:string, texto:string}
	 */
	private function aviso( string $url ): array {
		$_POST = array();
		$_GET  = wp_parse_args( (string) wp_parse_url( $url, PHP_URL_QUERY ) );
		return LinkCopies::notice( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- test.
	}

	/**
	 * Un ámbito, quien lo gestiona y un procedimiento suyo.
	 *
	 * @return array{0:int, 1:int, 2:int}
	 */
	private function mio(): array {
		$area = $this->area( 'Área de prueba' );
		$uid  = $this->manager( array( $area ) );
		return array( $area, $uid, $this->procedure( $uid, array( $area ) ) );
	}

	/**
	 * Pedir la copia de un enlace y devolver lo servido.
	 *
	 * @param int    $procedure_id Procedure.
	 * @param string $key          Link meta key.
	 * @return string
	 */
	private function pedir( int $procedure_id, string $key ): string {
		$_GET = array(
			LinkCopies::ARG_PROCEDURE => $procedure_id,
			LinkCopies::ARG_LINK      => $key,
		);
		return $this->served( array( LinkCopies::class, 'handle' ) );
	}

	// ─── guardar ───────────────────────────────────────────────────────────

	/**
	 * Un PDF de verdad se guarda en el almacén privado, sin adjunto.
	 */
	public function test_a_real_pdf_is_copied_privately() {
		list( , $uid, $p ) = $this->mio();
		$adjuntos          = (int) wp_count_posts( 'attachment' )->inherit;

		$url = $this->guardar( $uid, $p, array( ProcedureMetaKeys::RESOLUTION_URL => 'https://example.org/resolucion.pdf' ) );

		$this->assertSame( 'enlaces', $this->query_arg( $url, ProcedureEditor::ARG_NOTICE ) );
		$this->assertSame( 'ok', $this->aviso( $url )['tipo'] );
		$copia = LinkCopies::copies( $p )[ ProcedureMetaKeys::RESOLUTION_URL ];
		$this->assertSame( 'https://example.org/resolucion.pdf', $copia['url'] );
		$this->assertSame( '%PDF-', substr( (string) ApplicationFiles::read( $copia ), 0, 5 ) );
		$this->assertStringNotContainsString( 'resolucion', $copia['stored'], 'el nombre físico no dice nada' );
		$this->assertSame( $adjuntos, (int) wp_count_posts( 'attachment' )->inherit, 'no es un adjunto de WordPress' );
	}

	/**
	 * La petición es la segura de WordPress, con tiempo y tamaño acotados.
	 */
	public function test_the_request_is_safe_and_bounded() {
		list( , $uid, $p ) = $this->mio();

		$this->guardar( $uid, $p, array( ProcedureMetaKeys::RESOLUTION_URL => 'https://example.org/r.pdf' ) );

		$this->assertCount( 1, $this->pedidas );
		$args = $this->pedidas[0]['args'];
		$this->assertTrue( $args['reject_unsafe_urls'], 'wp_safe_remote_get(): nada de direcciones internas' );
		$this->assertLessThanOrEqual( LinkCopies::TIMEOUT, $args['timeout'] );
		$this->assertLessThan( 30, LinkCopies::BUDGET, 'los tres juntos, por debajo de max_execution_time' );
		$this->assertSame( LinkCopies::MAX_BYTES + 1, $args['limit_response_size'] );
	}

	/**
	 * Lo que no empieza por `%PDF-` no se guarda, diga lo que diga la cabecera.
	 */
	public function test_something_that_is_not_a_pdf_is_not_copied() {
		list( , $uid, $p ) = $this->mio();
		$this->respuesta   = $this->respuesta( '<html>Página no encontrada</html>', 200, 'application/pdf' );

		$url = $this->guardar( $uid, $p, array( ProcedureMetaKeys::RESOLUTION_URL => 'https://example.org/r.pdf' ) );

		$this->assertSame( 'https://example.org/r.pdf', get_post_meta( $p, ProcedureMetaKeys::RESOLUTION_URL, true ), 'el enlace se guarda igual' );
		$this->assertSame( array(), LinkCopies::copies( $p ) );
		$aviso = $this->aviso( $url );
		$this->assertSame( 'aviso', $aviso['tipo'] );
		$this->assertStringContainsString( 'No se pudo guardar una copia de la resolución', $aviso['texto'] );
		$this->assertStringContainsString( 'no es un PDF', $aviso['texto'] );
	}

	/**
	 * Si el enlace no responde, se guarda igual y se avisa.
	 */
	public function test_a_network_error_saves_the_link_and_warns() {
		list( , $uid, $p ) = $this->mio();
		$this->respuesta   = new \WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' );

		$url = $this->guardar( $uid, $p, array( ProcedureMetaKeys::FINAL_LIST_URL => 'https://example.org/d.pdf' ) );

		$this->assertSame( 'https://example.org/d.pdf', get_post_meta( $p, ProcedureMetaKeys::FINAL_LIST_URL, true ) );
		$this->assertSame( array(), LinkCopies::copies( $p ) );
		$this->assertStringContainsString( 'no respondió', $this->aviso( $url )['texto'] );
	}

	/**
	 * Un error HTTP tampoco es una copia.
	 */
	public function test_an_http_error_is_not_a_copy() {
		list( , $uid, $p ) = $this->mio();
		$this->respuesta   = $this->respuesta( '%PDF-1.4 página de error', 404 );

		$url = $this->guardar( $uid, $p, array( ProcedureMetaKeys::FINAL_LIST_URL => 'https://example.org/d.pdf' ) );

		$this->assertSame( array(), LinkCopies::copies( $p ) );
		$this->assertStringContainsString( 'no respondió', $this->aviso( $url )['texto'] );
	}

	/**
	 * Lo que pasa del tope no se guarda.
	 */
	public function test_a_pdf_over_the_limit_is_not_copied() {
		list( , $uid, $p ) = $this->mio();
		$this->respuesta   = $this->respuesta( '%PDF-' . str_repeat( 'x', LinkCopies::MAX_BYTES ) );

		$url = $this->guardar( $uid, $p, array( ProcedureMetaKeys::PROVISIONAL_LIST_URL => 'https://example.org/p.pdf' ) );

		$this->assertSame( array(), LinkCopies::copies( $p ) );
		$this->assertStringContainsString( 'más de 20 MB', $this->aviso( $url )['texto'] );
	}

	/**
	 * Un PDF con algo delante de la cabecera, dentro del primer kilobyte, es un PDF.
	 */
	public function test_a_pdf_with_leading_bytes_is_still_a_pdf() {
		list( , $uid, $p ) = $this->mio();
		$this->respuesta   = $this->respuesta( "\xEF\xBB\xBF\n%PDF-1.7\n%%EOF\n" );

		$this->guardar( $uid, $p, array( ProcedureMetaKeys::RESOLUTION_URL => 'https://example.org/r.pdf' ) );

		$this->assertArrayHasKey( ProcedureMetaKeys::RESOLUTION_URL, LinkCopies::copies( $p ) );
	}

	/**
	 * Una dirección con espacios codificados llega entera a la petición y a la meta.
	 */
	public function test_an_encoded_url_is_kept_as_is() {
		list( , $uid, $p ) = $this->mio();
		$url               = 'https://example.org/Resolucion%20final.pdf';

		$this->guardar( $uid, $p, array( ProcedureMetaKeys::RESOLUTION_URL => $url ) );

		$this->assertSame( $url, $this->pedidas[0]['url'] );
		$this->assertSame( $url, get_post_meta( $p, ProcedureMetaKeys::RESOLUTION_URL, true ) );
		$this->assertSame( $url, LinkCopies::copies( $p )[ ProcedureMetaKeys::RESOLUTION_URL ]['url'] );
	}

	/**
	 * Si la copia perdió su fichero, guardar la misma dirección la descarga otra vez.
	 */
	public function test_a_copy_without_its_file_is_downloaded_again() {
		list( , $uid, $p ) = $this->mio();
		$enlace            = array( ProcedureMetaKeys::RESOLUTION_URL => 'https://example.org/r.pdf' );
		$this->guardar( $uid, $p, $enlace );
		ApplicationFiles::erase( LinkCopies::copies( $p )[ ProcedureMetaKeys::RESOLUTION_URL ] );

		$this->guardar( $uid, $p, $enlace );

		$this->assertCount( 2, $this->pedidas );
		$this->assertNotNull( ApplicationFiles::read( LinkCopies::copies( $p )[ ProcedureMetaKeys::RESOLUTION_URL ] ) );
	}

	/**
	 * Volver a guardar la misma dirección no vuelve a descargar.
	 */
	public function test_an_unchanged_link_is_not_downloaded_again() {
		list( , $uid, $p ) = $this->mio();
		$enlace            = array( ProcedureMetaKeys::RESOLUTION_URL => 'https://example.org/r.pdf' );

		$this->guardar( $uid, $p, $enlace );
		$this->guardar( $uid, $p, $enlace );

		$this->assertCount( 1, $this->pedidas );
	}

	/**
	 * Sin copia, guardar otra vez lo reintenta.
	 */
	public function test_a_link_without_copy_is_tried_again() {
		list( , $uid, $p ) = $this->mio();
		$enlace            = array( ProcedureMetaKeys::RESOLUTION_URL => 'https://example.org/r.pdf' );
		$this->respuesta   = new \WP_Error( 'http_request_failed', 'caído' );
		$this->guardar( $uid, $p, $enlace );

		$this->respuesta = $this->respuesta_pdf();
		$this->guardar( $uid, $p, $enlace );

		$this->assertArrayHasKey( ProcedureMetaKeys::RESOLUTION_URL, LinkCopies::copies( $p ) );
	}

	/**
	 * Cambiar la dirección sustituye la copia y borra la anterior.
	 */
	public function test_a_new_link_replaces_the_copy() {
		list( , $uid, $p ) = $this->mio();
		$this->guardar( $uid, $p, array( ProcedureMetaKeys::RESOLUTION_URL => 'https://example.org/r1.pdf' ) );
		$vieja = LinkCopies::copies( $p )[ ProcedureMetaKeys::RESOLUTION_URL ];

		$this->guardar( $uid, $p, array( ProcedureMetaKeys::RESOLUTION_URL => 'https://example.org/r2.pdf' ) );

		$nueva = LinkCopies::copies( $p )[ ProcedureMetaKeys::RESOLUTION_URL ];
		$this->assertSame( 'https://example.org/r2.pdf', $nueva['url'] );
		$this->assertNull( ApplicationFiles::read( $vieja ), 'la anterior se borra' );
	}

	/**
	 * Si la dirección nueva falla, se conserva la copia que había: puede ser
	 * la única que queda de un documento retirado.
	 */
	public function test_a_failed_new_link_keeps_the_previous_copy() {
		list( , $uid, $p ) = $this->mio();
		$this->guardar( $uid, $p, array( ProcedureMetaKeys::RESOLUTION_URL => 'https://example.org/r1.pdf' ) );

		$this->respuesta = new \WP_Error( 'http_request_failed', 'caído' );
		$this->guardar( $uid, $p, array( ProcedureMetaKeys::RESOLUTION_URL => 'https://example.org/r2.pdf' ) );

		$copia = LinkCopies::copies( $p )[ ProcedureMetaKeys::RESOLUTION_URL ];
		$this->assertSame( 'https://example.org/r1.pdf', $copia['url'] );
		$this->assertNotNull( ApplicationFiles::read( $copia ) );
	}

	/**
	 * Quitar el enlace quita su copia.
	 */
	public function test_removing_the_link_removes_its_copy() {
		list( , $uid, $p ) = $this->mio();
		$this->guardar( $uid, $p, array( ProcedureMetaKeys::RESOLUTION_URL => 'https://example.org/r.pdf' ) );
		$copia = LinkCopies::copies( $p )[ ProcedureMetaKeys::RESOLUTION_URL ];

		$this->guardar( $uid, $p, array( ProcedureMetaKeys::RESOLUTION_URL => '' ) );

		$this->assertSame( array(), LinkCopies::copies( $p ) );
		$this->assertNull( ApplicationFiles::read( $copia ) );
	}

	/**
	 * Borrar el procedimiento para siempre se lleva sus copias.
	 */
	public function test_deleting_the_procedure_for_good_deletes_its_copies() {
		list( , $uid, $p ) = $this->mio();
		$this->guardar( $uid, $p, array( ProcedureMetaKeys::RESOLUTION_URL => 'https://example.org/r.pdf' ) );
		$copia = LinkCopies::copies( $p )[ ProcedureMetaKeys::RESOLUTION_URL ];

		wp_delete_post( $p, true );

		$this->assertNull( ApplicationFiles::read( $copia ) );
	}

	/**
	 * El duplicado no se lleva las copias.
	 */
	public function test_a_duplicate_never_takes_the_copies() {
		$this->assertSame( ProcedureCopy::NEVER, ProcedureCopy::rule( ProcedureMetaKeys::LINK_COPIES ) );
	}

	// ─── descargar ─────────────────────────────────────────────────────────

	/**
	 * Quien gestiona el ámbito se la descarga.
	 */
	public function test_the_manager_of_the_scope_downloads_the_copy() {
		list( , $uid, $p ) = $this->mio();
		$this->guardar( $uid, $p, array( ProcedureMetaKeys::RESOLUTION_URL => 'https://example.org/r.pdf' ) );

		$this->acting_as( $uid );

		$this->assertSame( '%PDF-', substr( $this->pedir( $p, ProcedureMetaKeys::RESOLUTION_URL ), 0, 5 ) );
	}

	/**
	 * Quien gestiona otro ámbito no.
	 */
	public function test_a_manager_of_another_scope_gets_nothing() {
		list( , $uid, $p ) = $this->mio();
		$this->guardar( $uid, $p, array( ProcedureMetaKeys::RESOLUTION_URL => 'https://example.org/r.pdf' ) );

		$this->acting_as( $this->manager( array( $this->area( 'Otro ámbito' ) ) ) );
		$cuerpo = $this->pedir( $p, ProcedureMetaKeys::RESOLUTION_URL );

		$this->assertStringNotContainsString( '%PDF', $cuerpo );
		$this->assertStringContainsString( 'No puede descargar', $cuerpo );
	}

	/**
	 * Sin sesión, no; y un centro tampoco.
	 */
	public function test_anonymous_and_schools_get_nothing() {
		list( , $uid, $p ) = $this->mio();
		$this->guardar( $uid, $p, array( ProcedureMetaKeys::RESOLUTION_URL => 'https://example.org/r.pdf' ) );

		$this->acting_as( 0 );
		$this->assertStringNotContainsString( '%PDF', $this->pedir( $p, ProcedureMetaKeys::RESOLUTION_URL ) );
		$this->acting_as( $this->school_head( 'C0001' ) );
		$this->assertStringNotContainsString( '%PDF', $this->pedir( $p, ProcedureMetaKeys::RESOLUTION_URL ) );
	}

	/**
	 * Otra clave que no sea un enlace no sirve nada.
	 */
	public function test_a_key_that_is_not_a_link_is_denied() {
		list( , $uid, $p ) = $this->mio();
		$this->acting_as( $uid );

		$this->assertStringNotContainsString( '%PDF', $this->pedir( $p, ProcedureMetaKeys::CUSTOM_JS ) );
	}

	/**
	 * Sin nuestro argumento, la petición no es nuestra.
	 */
	public function test_a_request_without_our_argument_is_left_alone() {
		$_GET = array();
		$this->assertSame( '', $this->served( array( LinkCopies::class, 'handle' ) ) );
	}

	// ─── pantalla ──────────────────────────────────────────────────────────

	/**
	 * «Enlaces» dice cuándo se guardó la copia y ofrece descargarla.
	 */
	public function test_the_links_section_shows_the_copy() {
		list( , $uid, $p ) = $this->mio();
		$this->guardar( $uid, $p, array( ProcedureMetaKeys::RESOLUTION_URL => 'https://example.org/r.pdf' ) );

		$_POST = array();
		$_GET  = array(
			ProcedureEditor::ARG_PROCEDURE => (string) $p,
			ProcedureEditor::ARG_PANEL     => ProcedureEditor::PANEL_DATA,
		);
		$html  = ProcedureEditorView::html( ProcedureEditor::model() );

		$this->assertStringContainsString( 'Copia guardada el ' . wp_date( 'd/m/Y' ), $html );
		$this->assertStringContainsString( esc_url( LinkCopies::url( $p, ProcedureMetaKeys::RESOLUTION_URL ) ), $html );
	}
}
