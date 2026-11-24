<?php
/**
 * Tests for choosing the poster of a procedure from «Datos».
 *
 * @package Prc
 */

use Prc\PublicFront\ProcedureEditor;
use Prc\PublicFront\ProcedurePoster;
use Prc\PublicFront\View\ProcedureEditorView;

/**
 * El cartel es la imagen destacada del procedimiento. Lo sube quien edita el
 * procedimiento desde el taller, sin `upload_files`: el taller guarda la
 * imagen en medios por su cuenta y no abre la biblioteca del sitio (#126).
 */
class Test_Procedure_Poster extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Temporary upload files to remove.
	 *
	 * @var string[]
	 */
	private $temporales = array();

	/**
	 * Con el aplicativo y sus páginas.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
		$this->pages();
	}

	/**
	 * Y sin los temporales de la subida.
	 */
	public function tear_down() {
		foreach ( $this->temporales as $tmp ) {
			if ( file_exists( $tmp ) ) {
				wp_delete_file( $tmp );
			}
		}
		parent::tear_down();
	}

	/**
	 * A 1×1 PNG.
	 *
	 * @return string
	 */
	private function png(): string {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- imagen fija del test.
		return (string) base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==' );
	}

	/**
	 * Send the «Cartel» section as $uid.
	 *
	 * @param int                  $uid          Who sends it.
	 * @param int                  $procedure_id Procedure.
	 * @param string|null          $bytes        File sent, or null for none.
	 * @param string               $name         File name.
	 * @param array<string,string> $extra        More fields.
	 * @return string|null Return URL, or null when refused.
	 */
	private function enviar( int $uid, int $procedure_id, ?string $bytes, string $name = 'cartel.png', array $extra = array() ): ?string {
		$this->acting_as( $uid );
		$_FILES = array();
		if ( null !== $bytes ) {
			$tmp = (string) tempnam( get_temp_dir(), 'prccartel' );
			file_put_contents( $tmp, $bytes ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- fichero temporal del test.
			$this->temporales[] = $tmp;
			$_FILES             = array(
				ProcedurePoster::FIELD_FILE => array(
					'name'     => $name,
					'tmp_name' => $tmp,
					'size'     => strlen( $bytes ),
					'error'    => UPLOAD_ERR_OK,
				),
			);
		}
		$this->post(
			array(
				ProcedureEditor::FIELD_DO        => ProcedurePoster::OP,
				ProcedureEditor::FIELD_PROCEDURE => (string) $procedure_id,
			) + $extra,
			ProcedureEditor::nonce_action( ProcedurePoster::OP ),
			ProcedureEditor::nonce_name( ProcedurePoster::OP )
		);
		return $this->exit_url( array( ProcedureEditor::class, 'handle' ) );
	}

	/**
	 * «Datos» tiene la sección, que sube con multipart, y quien gestiona sin
	 * `upload_files` deja la imagen como cartel, con su texto alternativo.
	 */
	public function test_whoever_edits_the_procedure_uploads_the_poster_without_upload_files() {
		$area      = $this->area( 'Ámbito' );
		$manager   = $this->manager( array( $area ) );
		$procedure = $this->procedure( $manager, array( $area ) );
		$this->assertFalse( user_can( $manager, 'upload_files' ) );

		$this->acting_as( $manager );
		$_GET = array(
			ProcedureEditor::ARG_PROCEDURE => (string) $procedure,
			ProcedureEditor::ARG_PANEL     => ProcedureEditor::PANEL_DATA,
		);
		$html = ProcedureEditorView::html( ProcedureEditor::model() );
		$this->assertStringContainsString( 'id="' . ProcedurePoster::SECTION . '"', $html );
		$this->assertStringContainsString( 'enctype="multipart/form-data"', $html );
		$this->assertStringContainsString( 'value="' . ProcedurePoster::OP . '"', $html );

		$url = $this->enviar( $manager, $procedure, $this->png(), 'cartel.png', array( ProcedurePoster::FIELD_ALT => 'Cartel de la red' ) );
		$this->assertNotNull( $url );
		$this->assertSame( ProcedurePoster::NOTICE, $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
		$imagen = (int) get_post_thumbnail_id( $procedure );
		$this->assertGreaterThan( 0, $imagen );
		$this->assertSame( 'image/png', get_post_mime_type( $imagen ) );
		$this->assertSame( 'Cartel de la red', get_post_meta( $imagen, '_wp_attachment_image_alt', true ) );
		$this->assertSame( $procedure, (int) get_post_field( 'post_parent', $imagen ) );

		// Ya puesto, la sección lo enseña y deja quitarlo.
		$_GET = array(
			ProcedureEditor::ARG_PROCEDURE => (string) $procedure,
			ProcedureEditor::ARG_PANEL     => ProcedureEditor::PANEL_DATA,
		);
		$html = ProcedureEditorView::html( ProcedureEditor::model() );
		$this->assertStringContainsString( (string) wp_get_attachment_image_url( $imagen, 'medium' ), $html );
		$this->assertStringContainsString( 'name="' . ProcedurePoster::FIELD_REMOVE . '"', $html );

		// Cambiar solo el texto alternativo no pide otra imagen.
		$this->assertNotNull( $this->enviar( $manager, $procedure, null, '', array( ProcedurePoster::FIELD_ALT => 'Otro texto' ) ) );
		$this->assertSame( $imagen, (int) get_post_thumbnail_id( $procedure ) );
		$this->assertSame( 'Otro texto', get_post_meta( $imagen, '_wp_attachment_image_alt', true ) );

		$this->assertNotNull( $this->enviar( $manager, $procedure, null, '', array( ProcedurePoster::FIELD_REMOVE => '1' ) ) );
		$this->assertSame( 0, (int) get_post_thumbnail_id( $procedure ) );
	}

	/**
	 * Lo que no es una imagen JPEG, PNG o WebP no se guarda, se llame como se llame.
	 */
	public function test_what_is_not_an_image_is_refused() {
		$area      = $this->area( 'Ámbito' );
		$manager   = $this->manager( array( $area ) );
		$procedure = $this->procedure( $manager, array( $area ) );

		$this->assertNull( $this->enviar( $manager, $procedure, '<?php echo 1;', 'cartel.png' ) );
		$this->assertNull( $this->enviar( $manager, $procedure, $this->png(), 'cartel.svg' ) );
		$this->assertNull( $this->enviar( $manager, $procedure, null, '' ) );
		$this->assertSame( 0, (int) get_post_thumbnail_id( $procedure ) );
		$this->assertSame(
			array(),
			get_posts(
				array(
					'post_type'   => 'attachment',
					'post_status' => 'any',
					'fields'      => 'ids',
				)
			)
		);
	}

	/**
	 * Quien no edita ese procedimiento no le pone cartel aunque fabrique el envío.
	 */
	public function test_a_manager_of_another_area_cannot_set_it() {
		$area      = $this->area( 'Ámbito' );
		$procedure = $this->procedure( $this->manager( array( $area ) ), array( $area ) );
		$ajeno     = $this->manager( array( $this->area( 'Otro ámbito' ) ) );

		$this->assertNull( $this->enviar( $ajeno, $procedure, $this->png() ) );
		$this->assertSame( 0, (int) get_post_thumbnail_id( $procedure ) );
	}
}
