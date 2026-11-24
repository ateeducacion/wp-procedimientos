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

	// Con alias porque esta clase define su propio `tear_down()`: sin esto el
	// de las fixtures no se llamaría y `$_FILES` se colaría al test siguiente.
	use Prc_Fixtures {
		tear_down as fixtures_tear_down;
	}

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
		$this->remove_added_uploads();
		$this->fixtures_tear_down();
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
		// Sin cartel, la zona de soltar: una etiqueta de la propia entrada de fichero.
		$this->assertStringContainsString( 'data-prc-cartel-estado="vacia"', $html );
		$this->assertStringContainsString( '<label class="prc-cartel__vacio" for="' . ProcedurePoster::FIELD_FILE . '">', $html );
		$this->assertStringContainsString( '<strong>Elija una imagen</strong> o arrástrela aquí', $html );
		$this->assertMatchesRegularExpression( '/<input class="prc-cartel__fichero" type="file" id="' . ProcedurePoster::FIELD_FILE . '" name="' . ProcedurePoster::FIELD_FILE . '"[^>]* autocomplete="off"/', $html );
		// Los tipos y el peso, en la ayuda: se leen también con un cartel puesto.
		$this->assertStringContainsString( 'Mejor en vertical, como un cartel. ' . ProcedurePoster::types_label() . ', de ' . size_format( ProcedurePoster::max_bytes() ) . ' como mucho.', $html );
		$this->assertStringContainsString( 'data-prc-cartel-error-tipo="' . esc_attr( ProcedurePoster::type_error() ) . '"', $html );
		$this->assertStringContainsString( 'data-prc-cartel-max="' . ProcedurePoster::max_bytes() . '"', $html );
		$this->assertStringNotContainsString( 'name="' . ProcedurePoster::FIELD_REMOVE . '"', $html );

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
		$this->assertStringContainsString( (string) wp_get_attachment_image_url( $imagen, 'large' ), $html );
		$this->assertStringContainsString( 'data-prc-cartel-estado="guardada"', $html );
		$this->assertStringContainsString( 'name="' . ProcedurePoster::FIELD_REMOVE . '" value="1" autocomplete="off"', $html );
		$this->assertStringContainsString( 'title="Reemplazar la imagen"', $html );
		$this->assertStringContainsString( 'aria-label="Quitar el cartel"', $html );
		$this->assertStringContainsString( 'Reemplazar', $html );
		$this->assertStringContainsString( 'Se quitará al guardar.', $html );
		$this->assertStringContainsString( esc_html( wp_basename( (string) get_attached_file( $imagen ) ) ) . ' · 1 × 1 px', $html );

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

	/**
	 * Un duplicado comparte la imagen del original: cambiar ahí el texto
	 * alternativo no toca la ficha del otro, y la sección lo dice.
	 */
	public function test_the_alt_text_of_a_shared_image_is_not_rewritten() {
		$area     = $this->area( 'Ámbito' );
		$manager  = $this->manager( array( $area ) );
		$original = $this->procedure( $manager, array( $area ) );
		$this->assertNotNull( $this->enviar( $manager, $original, $this->png(), 'cartel.png', array( ProcedurePoster::FIELD_ALT => 'Cartel de la red' ) ) );
		$imagen = (int) get_post_thumbnail_id( $original );

		$copia = $this->procedure( $manager, array( $area ) );
		set_post_thumbnail( $copia, $imagen );
		$this->assertNull( $this->enviar( $manager, $copia, null, '', array( ProcedurePoster::FIELD_ALT => 'Otro texto' ) ) );
		$this->assertSame( 'Cartel de la red', get_post_meta( $imagen, '_wp_attachment_image_alt', true ) );

		// Ni siquiera desde el original, mientras la copia la use.
		$this->assertNull( $this->enviar( $manager, $original, null, '', array( ProcedurePoster::FIELD_ALT => 'Otro texto' ) ) );
		$this->assertSame( 'Cartel de la red', get_post_meta( $imagen, '_wp_attachment_image_alt', true ) );

		// Lo tecleado se repinta en el rechazo.
		$_GET = array(
			ProcedureEditor::ARG_PROCEDURE => (string) $original,
			ProcedureEditor::ARG_PANEL     => ProcedureEditor::PANEL_DATA,
		);
		$this->assertStringContainsString( 'value="Otro texto"', ProcedureEditorView::html( ProcedureEditor::model() ) );

		// Guardar sin cambiar el texto sí vale.
		$this->assertNotNull( $this->enviar( $manager, $copia, null, '', array( ProcedurePoster::FIELD_ALT => 'Cartel de la red' ) ) );
	}

	/**
	 * Elegir una imagen y marcar «Quitar el cartel» a la vez no hace ninguna de las dos.
	 */
	public function test_choosing_an_image_and_removing_at_once_is_refused() {
		$area      = $this->area( 'Ámbito' );
		$manager   = $this->manager( array( $area ) );
		$procedure = $this->procedure( $manager, array( $area ) );
		$this->assertNotNull( $this->enviar( $manager, $procedure, $this->png() ) );
		$imagen = (int) get_post_thumbnail_id( $procedure );

		$this->assertNull( $this->enviar( $manager, $procedure, $this->png(), 'otro.png', array( ProcedurePoster::FIELD_REMOVE => '1' ) ) );
		$this->assertSame( $imagen, (int) get_post_thumbnail_id( $procedure ) );
	}

	/**
	 * De una foto grande, WordPress guarda una copia reducida («-scaled»); la
	 * sección enseña el nombre y las medidas del fichero que se subió.
	 */
	public function test_a_scaled_photo_shows_the_name_and_size_that_were_uploaded() {
		$area      = $this->area( 'Ámbito' );
		$manager   = $this->manager( array( $area ) );
		$procedure = $this->procedure( $manager, array( $area ) );
		$threshold = static function () {
			return 10;
		};
		add_filter( 'big_image_size_threshold', $threshold );
		$canvas = imagecreatetruecolor( 40, 20 );
		ob_start();
		imagepng( $canvas );
		$bytes = (string) ob_get_clean();

		$this->assertNotNull( $this->enviar( $manager, $procedure, $bytes, 'foto.png' ) );
		remove_filter( 'big_image_size_threshold', $threshold );
		$image = (int) get_post_thumbnail_id( $procedure );
		$this->assertStringContainsString( '-scaled', (string) get_attached_file( $image ) );

		$poster = ProcedurePoster::summary( $procedure );
		$this->assertSame( wp_basename( (string) wp_get_original_image_path( $image ) ), $poster['name'] );
		$this->assertStringNotContainsString( '-scaled', $poster['name'] );
		$this->assertSame( array( 40, 20 ), array( $poster['width'], $poster['height'] ) );
	}

	/**
	 * El límite es el menor entre el nuestro y el del servidor, y es el que se anuncia.
	 */
	public function test_the_size_limit_follows_the_server() {
		$limite = static function () {
			return MB_IN_BYTES;
		};
		add_filter( 'upload_size_limit', $limite );
		$this->assertSame( MB_IN_BYTES, ProcedurePoster::max_bytes() );

		$area      = $this->area( 'Ámbito' );
		$manager   = $this->manager( array( $area ) );
		$procedure = $this->procedure( $manager, array( $area ) );
		$this->acting_as( $manager );
		$_GET = array(
			ProcedureEditor::ARG_PROCEDURE => (string) $procedure,
			ProcedureEditor::ARG_PANEL     => ProcedureEditor::PANEL_DATA,
		);
		$this->assertStringContainsString( 'de 1 MB como mucho', ProcedureEditorView::html( ProcedureEditor::model() ) );
		remove_filter( 'upload_size_limit', $limite );
	}
}
