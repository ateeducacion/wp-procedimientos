<?php
/**
 * Tests for signing the receipt: the switch in «Avanzado», the buttons and the
 * upload on the application screen, what is stored, who downloads it and where
 * «Pendiente de firma» shows.
 *
 * @package Prc
 */

use Prc\Domain\CentreCatalog;
use Prc\Domain\SignedReceipt;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PublicFront\AdvancedPanel;
use Prc\PublicFront\ApplicationExport;
use Prc\PublicFront\ApplicationFiles;
use Prc\PublicFront\ApplicationHistory;
use Prc\PublicFront\ApplicationLog;
use Prc\PublicFront\ApplicationProgress;
use Prc\PublicFront\ApplicationQuery;
use Prc\PublicFront\Applications;
use Prc\PublicFront\ApplyForm;
use Prc\PublicFront\ProcedureEditor;
use Prc\PublicFront\ReceiptSignature;
use Prc\PublicFront\View\ProcedureEditorView;
use Prc\Domain\ApplicationFilter;

/**
 * La firma electrónica del justificante (ADR-0049).
 *
 * La firma de verdad con AutoFirma no cabe aquí: lo que llega es un PDF
 * firmado sintético ({@see Prc_Fixtures::signed_pdf()}), con su revisión y su
 * `/ByteRange` bien puestos y una firma de ceros.
 */
class Test_Receipt_Signature extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Temporary upload files to remove.
	 *
	 * @var string[]
	 */
	private $temporales = array();

	/**
	 * Con el aplicativo, sus páginas, un catálogo con zonas, el correo apagado
	 * y el almacén privado en un directorio de prueba.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
		$this->pages();
		remove_all_filters( 'prc_centres' );
		add_filter( 'prc_centres', array( $this, 'catalogue' ) );
		CentreCatalog::flush();
		add_filter( 'pre_wp_mail', '__return_true' );
		add_filter( 'prc_private_files_dir', array( $this, 'raiz' ) );
		$this->olvidar();
	}

	/**
	 * Borrar el almacén y los temporales.
	 */
	public function tear_down() {
		global $wp_filesystem;
		if ( $wp_filesystem instanceof \WP_Filesystem_Base && $wp_filesystem->is_dir( $this->raiz() ) ) {
			$wp_filesystem->delete( $this->raiz(), true );
		}
		foreach ( $this->temporales as $ruta ) {
			wp_delete_file( $ruta );
		}
		remove_all_filters( 'prc_autofirma_servlets' );
		parent::tear_down();
	}

	/**
	 * The private root of the tests.
	 *
	 * @return string
	 */
	public function raiz(): string {
		return rtrim( get_temp_dir(), '/' ) . '/prc-private-firma';
	}

	/**
	 * The made-up catalogue, with zones.
	 *
	 * @return array<int, array<string, string>>
	 */
	public function catalogue(): array {
		return array(
			array(
				'code' => 'C0001',
				'name' => 'CEIP Uno',
				'zone' => 'Z0001',
			),
			array(
				'code' => 'C0002',
				'name' => 'IES Dos',
				'zone' => 'Z0001',
			),
			array(
				'code' => 'Z0001',
				'name' => 'Zona Uno',
			),
		);
	}

	/**
	 * Forget the refusals the statics kept from the test before.
	 *
	 * @return void
	 */
	private function olvidar(): void {
		foreach ( array( ApplyForm::class, ReceiptSignature::class, ProcedureEditor::class ) as $clase ) {
			\Closure::bind(
				static function (): void {
					self::$rejected = '';
				},
				null,
				$clase
			)();
		}
	}

	/**
	 * A procedure of an area that asks for the signature, its manager, a school head and its application.
	 *
	 * @param bool $firma Whether it asks for the signature.
	 * @return array{area:int, manager:int, procedure:int, head:int, application:int}
	 */
	private function escenario( bool $firma = true ): array {
		$area          = $this->area( 'Área de prueba' );
		$gestor        = $this->manager( array( $area ) );
		$procedimiento = $this->procedure(
			$gestor,
			array( $area ),
			array(
				ProcedureMetaKeys::SIGNATURE_REQUIRED => $firma,
				ProcedureMetaKeys::OBSERVABLE         => true,
			),
			array( 'post_title' => 'Red de prueba' )
		);
		$jefa          = $this->school_head( 'C0001' );
		return array(
			'area'        => $area,
			'manager'     => $gestor,
			'procedure'   => $procedimiento,
			'head'        => $jefa,
			'application' => $this->application( $procedimiento, $jefa, 'C0001', array( ApplicationMetaKeys::CENTRE_NAME => 'CEIP Uno' ) ),
		);
	}

	/**
	 * The signed receipt of that application, as AutoFirma would return it.
	 *
	 * @param int    $application_id Application.
	 * @param string $tail           Appended after signing.
	 * @return string
	 */
	private function firmado( int $application_id, string $tail = '' ): string {
		$datos = ReceiptSignature::data( $application_id );
		return $this->signed_pdf( array( 'Justificante de presentacion', 'Solicitud: ' . $datos['number'], 'Huella SHA-256: ' . $datos['fingerprint'] ), $tail );
	}

	/**
	 * Send the signing form, with a file or with what AutoFirma returned.
	 *
	 * @param int    $uid            Who sends it.
	 * @param int    $application_id Application.
	 * @param string $pdf            Bytes.
	 * @param bool   $autofirma      Whether it comes back from AutoFirma (base64 field) instead of a file.
	 * @return string|null Where it went; null when it stayed.
	 */
	private function enviar( int $uid, int $application_id, string $pdf, bool $autofirma = false ): ?string {
		$this->acting_as( $uid );
		$campos = array(
			ReceiptSignature::FIELD_OP          => ReceiptSignature::OP_SIGN,
			ReceiptSignature::FIELD_APPLICATION => (string) $application_id,
		);
		if ( $autofirma ) {
			$campos[ ReceiptSignature::FIELD_SIGNED ] = base64_encode( $pdf ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- lo que devuelve AutoFirma.
		} else {
			$tmp = (string) tempnam( get_temp_dir(), 'prcfirma' );
			file_put_contents( $tmp, $pdf ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- fichero temporal del test.
			$this->temporales[] = $tmp;
			$_FILES             = array(
				ReceiptSignature::FIELD_FILE => array(
					'name'     => 'justificante-firmado.pdf',
					'tmp_name' => $tmp,
					'size'     => strlen( $pdf ),
					'error'    => UPLOAD_ERR_OK,
				),
			);
		}
		$this->post( $campos, ReceiptSignature::nonce_action( $application_id ), ReceiptSignature::NONCE_FIELD );
		return $this->exit_url( array( ReceiptSignature::class, 'handle' ) );
	}

	/**
	 * Ask for the signed receipt and return what was served.
	 *
	 * @param int $uid            Who asks.
	 * @param int $application_id Application.
	 * @return string
	 */
	private function descargar( int $uid, int $application_id ): string {
		$this->acting_as( $uid );
		$_GET = array( ReceiptSignature::ARG_DOWNLOAD => (string) $application_id );
		return $this->served( array( ReceiptSignature::class, 'serve' ) );
	}

	/**
	 * The application screen.
	 *
	 * @param int                  $uid          Who looks.
	 * @param int                  $procedure_id Procedure.
	 * @param array<string,string> $get Query args.
	 * @return string
	 */
	private function pantalla( int $uid, int $procedure_id, array $get = array() ): string {
		$this->acting_as( $uid );
		$_GET = array( ApplyForm::ARG_PROCEDURE => (string) $procedure_id ) + $get;
		return ApplyForm::render();
	}

	// ─── «Avanzado» ────────────────────────────────────────────────────────

	/**
	 * «Avanzado» tiene su sección «Firma electrónica» con su interruptor, y
	 * quien edita el procedimiento la guarda con su operación.
	 */
	public function test_whoever_edits_the_procedure_switches_the_signature_on() {
		$e = $this->escenario( false );
		$this->acting_as( $e['manager'] );
		$this->assertContains( 'signature', AdvancedPanel::sections( $e['procedure'] ) );
		$_GET = array(
			ProcedureEditor::ARG_PROCEDURE => (string) $e['procedure'],
			ProcedureEditor::ARG_PANEL     => ProcedureEditor::PANEL_ADVANCED,
		);
		$html = ProcedureEditorView::html( ProcedureEditor::model() );
		$this->assertStringContainsString( 'id="' . AdvancedPanel::SECTION_SIGNATURE . '"', $html );
		$this->assertStringContainsString( '>Firma electrónica</h2>', $html );
		$this->assertStringContainsString( 'value="' . AdvancedPanel::OP_SIGNATURE . '"', $html );
		$this->assertStringContainsString( 'Pedir la firma electrónica del justificante (AutoFirma)', $html );

		$campos = array(
			ProcedureEditor::FIELD_DO             => AdvancedPanel::OP_SIGNATURE,
			ProcedureEditor::FIELD_PROCEDURE      => (string) $e['procedure'],
			ProcedureMetaKeys::SIGNATURE_REQUIRED => '1',
		);
		$this->post( $campos, ProcedureEditor::nonce_action( AdvancedPanel::OP_SIGNATURE ), ProcedureEditor::nonce_name( AdvancedPanel::OP_SIGNATURE ) );
		$url = $this->exit_url( array( ProcedureEditor::class, 'handle' ) );
		$this->assertSame( 'firma', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
		$this->assertSame( ProcedureEditor::PANEL_ADVANCED, $this->query_arg( (string) $url, ProcedureEditor::ARG_PANEL ) );
		$this->assertTrue( ReceiptSignature::required( $e['procedure'] ) );

		// Apagarlo es enviar la sección sin la casilla.
		unset( $campos[ ProcedureMetaKeys::SIGNATURE_REQUIRED ] );
		$this->post( $campos, ProcedureEditor::nonce_action( AdvancedPanel::OP_SIGNATURE ), ProcedureEditor::nonce_name( AdvancedPanel::OP_SIGNATURE ) );
		$this->assertNotNull( $this->exit_url( array( ProcedureEditor::class, 'handle' ) ) );
		$this->assertFalse( ReceiptSignature::required( $e['procedure'] ) );
	}

	/**
	 * Quien no edita ese procedimiento no lo enciende aunque fabrique el envío.
	 */
	public function test_a_manager_of_another_area_cannot_switch_it_on() {
		$e     = $this->escenario( false );
		$ajeno = $this->manager( array( $this->area( 'Otro ámbito' ) ) );
		$this->acting_as( $ajeno );
		$this->post(
			array(
				ProcedureEditor::FIELD_DO             => AdvancedPanel::OP_SIGNATURE,
				ProcedureEditor::FIELD_PROCEDURE      => (string) $e['procedure'],
				ProcedureMetaKeys::SIGNATURE_REQUIRED => '1',
			),
			ProcedureEditor::nonce_action( AdvancedPanel::OP_SIGNATURE ),
			ProcedureEditor::nonce_name( AdvancedPanel::OP_SIGNATURE )
		);
		$this->assertNull( $this->exit_url( array( ProcedureEditor::class, 'handle' ) ) );
		$this->assertFalse( ReceiptSignature::required( $e['procedure'] ) );
	}

	// ─── la pantalla de la solicitud ───────────────────────────────────────

	/**
	 * Con la firma pedida, la solicitud presentada ofrece AutoFirma y la vía
	 * manual, con los datos del justificante para el guion; sin pedirla, nada.
	 */
	public function test_the_application_screen_offers_both_ways_to_sign() {
		$e    = $this->escenario();
		$html = $this->pantalla( $e['head'], $e['procedure'] );
		$this->assertStringContainsString( 'id="' . ReceiptSignature::ANCHOR . '"', $html );
		$this->assertStringContainsString( 'Firmar justificante con AutoFirma', $html );
		$this->assertStringContainsString( 'Descargar justificante en PDF', $html );
		$this->assertStringContainsString( 'name="' . ReceiptSignature::FIELD_FILE . '"', $html );
		$this->assertStringContainsString( 'enctype="multipart/form-data"', $html );
		$this->assertStringContainsString( 'name="' . ReceiptSignature::NONCE_FIELD . '"', $html );
		$this->assertStringContainsString( 'Pendiente de firma', $html );
		$datos = ReceiptSignature::data( $e['application'] );
		$this->assertStringContainsString( $datos['fingerprint'], $html, 'los datos del justificante viajan al guion' );
		$this->assertSame( SignedReceipt::fingerprint( $datos ), $datos['fingerprint'] );
		$this->assertSame( (string) $e['application'], $datos['number'] );
		$this->assertSame( 'Red de prueba', $datos['procedure'] );
		$this->assertSame( 'CEIP Uno (C0001)', $datos['centre'] );
		$this->assertSame( '', $datos['coordination'], 'sin coordinación, vacío' );

		// La coordinación, con su DNI o NIE, entra en el PDF y en su huella.
		update_post_meta(
			$e['application'],
			ApplicationMetaKeys::COORDINATOR,
			array(
				array(
					'name'    => 'Ana',
					'surname' => 'Pérez',
					'dni'     => '12345678Z',
					'email'   => 'ana@example.org',
				),
			)
		);
		$con = ReceiptSignature::data( $e['application'] );
		$this->assertSame( 'Ana Pérez (DNI o NIE 12345678Z)', $con['coordination'] );
		$this->assertNotSame( $datos['fingerprint'], $con['fingerprint'], 'cambiar la coordinación cambia la huella' );
		$this->assertTrue( wp_script_is( ReceiptSignature::HANDLE_JSPDF, 'enqueued' ) );
		$this->assertTrue( wp_script_is( ReceiptSignature::HANDLE_AUTOSCRIPT, 'enqueued' ) );

		$sin = $this->escenario( false );
		$this->assertStringNotContainsString( 'id="' . ReceiptSignature::ANCHOR . '"', $this->pantalla( $sin['head'], $sin['procedure'] ) );
	}

	/**
	 * Las dos librerías vienen del CDN con su SRI, versión exacta.
	 */
	public function test_the_libraries_come_from_the_cdn_with_sri() {
		foreach ( ReceiptSignature::vendors() as $handle => $vendor ) {
			$this->assertStringStartsWith( 'https://cdn.jsdelivr.net/npm/', $vendor['url'] );
			$this->assertStringContainsString( '@' . $vendor['ver'] . '/', $vendor['url'] );
			$etiqueta = ReceiptSignature::sri( '<script src="' . $vendor['url'] . '"></script>', $handle, $vendor['url'] ); // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- es la etiqueta que recibe el filtro.
			$this->assertStringContainsString( 'integrity="' . $vendor['sri'] . '" crossorigin="anonymous"', $etiqueta );
		}
		$paquete = json_decode( (string) file_get_contents( dirname( __DIR__, 2 ) . '/package.json' ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- fichero del repositorio.
		$this->assertSame( ReceiptSignature::vendors()[ ReceiptSignature::HANDLE_JSPDF ]['ver'], $paquete['devDependencies']['jspdf'] );
		$this->assertSame( ReceiptSignature::vendors()[ ReceiptSignature::HANDLE_AUTOSCRIPT ]['ver'], $paquete['devDependencies']['@erseco/autofirma-client'] );
		// Servida desde otro sitio, sin SRI: no cuadraría.
		$local = '<script src="/local.js"></script>'; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- es la etiqueta que recibe el filtro.
		$this->assertSame( $local, ReceiptSignature::sri( $local, ReceiptSignature::HANDLE_JSPDF, '/local.js' ) );
	}

	/**
	 * Los servlets del servidor intermedio son un filtro vacío por defecto.
	 */
	public function test_the_intermediate_servlets_are_a_filter_empty_by_default() {
		$this->assertSame(
			array(
				'storage'  => '',
				'retrieve' => '',
			),
			ReceiptSignature::servlets()
		);
		add_filter(
			'prc_autofirma_servlets',
			static function (): array {
				return array(
					'storage'  => 'https://intermedio.example/StorageService',
					'retrieve' => 'javascript:alert(1)',
				);
			}
		);
		$this->assertSame(
			array(
				'storage'  => 'https://intermedio.example/StorageService',
				'retrieve' => '',
			),
			ReceiptSignature::servlets()
		);
	}

	// ─── la subida ─────────────────────────────────────────────────────────

	/**
	 * Subido a mano, sin guion: se guarda en privado, con fecha y quién, se
	 * anota en el historial y la pantalla dice «Firmado».
	 */
	public function test_an_uploaded_signed_receipt_is_stored_and_logged() {
		$e     = $this->escenario();
		$hecho = array();
		add_action(
			'prc_receipt_signed',
			static function ( $id, $uid, $via ) use ( &$hecho ) {
				$hecho = array( $id, $uid, $via );
			},
			10,
			3
		);
		$url = $this->enviar( $e['head'], $e['application'], $this->firmado( $e['application'] ) );
		$this->assertSame( ReceiptSignature::NOTICE, $this->query_arg( (string) $url, ApplyForm::ARG_NOTICE ) );
		$this->assertSame( array( $e['application'], $e['head'], ReceiptSignature::VIA_UPLOAD ), $hecho );

		$firma = ReceiptSignature::signed( $e['application'] );
		$this->assertSame( $e['head'], $firma['signed_by'] );
		$this->assertGreaterThan( 0, $firma['signed_at'] );
		$this->assertSame( 'application/pdf', $firma['mime'] );
		$this->assertStringStartsWith( '%PDF', (string) ApplicationFiles::read( $firma ) );
		$this->assertStringNotContainsString( 'C0001', $firma['stored'], 'nombre opaco' );

		$this->assertSame( ApplicationHistory::SIGNED, ApplicationLog::all( $e['application'] )[0]['event'] ?? '' );
		$this->assertSame( 'Justificante firmado', ApplicationHistory::entries( $e['application'] )[0]['what'] );
		$this->assertStringStartsWith( 'Firmado el ', ReceiptSignature::label( $e['application'] ) );
		$this->assertSame( ReceiptSignature::STATE_SIGNED, ReceiptSignature::state( $e['application'] ) );

		$this->olvidar();
		$html = $this->pantalla( $e['head'], $e['procedure'] );
		$this->assertStringNotContainsString( 'Firmar justificante con AutoFirma', $html, 'firmado, no se vuelve a firmar' );
		$this->assertStringContainsString( ReceiptSignature::url( $e['application'] ), str_replace( '&amp;', '&', $html ) );
	}

	/**
	 * Lo que devuelve AutoFirma llega en base64 por el mismo formulario.
	 */
	public function test_what_autofirma_returns_arrives_by_the_same_form() {
		$e   = $this->escenario();
		$url = $this->enviar( $e['head'], $e['application'], $this->firmado( $e['application'] ), true );
		$this->assertNotNull( $url );
		$this->assertSame( ReceiptSignature::VIA_AUTOFIRMA, ReceiptSignature::signed( $e['application'] )['via'] );
	}

	/**
	 * Un PDF que no es este justificante, o modificado después de firmar, no
	 * se guarda, y la pantalla dice por qué.
	 */
	public function test_a_receipt_that_is_not_this_one_is_refused() {
		$e = $this->escenario();
		$this->assertNull( $this->enviar( $e['head'], $e['application'], $this->firmado( $e['application'], "% añadido\n" ) ) );
		$this->assertSame( array(), ReceiptSignature::signed( $e['application'] ) );
		$this->assertStringContainsString( SignedReceipt::why( 'not_covered' ), $this->pantalla( $e['head'], $e['procedure'] ) );

		$this->olvidar();
		$otra = $this->application( $e['procedure'], $this->school_head( 'C0002' ), 'C0002' );
		$this->assertNull( $this->enviar( $e['head'], $e['application'], $this->firmado( $otra ) ) );
		$this->assertSame( array(), ReceiptSignature::signed( $e['application'] ) );
	}

	/**
	 * Firma el centro de la solicitud, una vez, en un procedimiento que la pide.
	 */
	public function test_only_the_school_signs_and_only_once() {
		$e = $this->escenario();
		$this->assertFalse( ReceiptSignature::can_sign( $e['manager'], $e['application'] ), 'quien revisa no firma por el centro' );
		$this->assertFalse( ReceiptSignature::can_sign( $this->school_head( 'C0002' ), $e['application'] ) );
		$this->assertTrue( ReceiptSignature::can_sign( $e['head'], $e['application'] ) );
		$this->assertNull( $this->enviar( $this->school_head( 'C0002' ), $e['application'], $this->firmado( $e['application'] ) ) );

		$this->olvidar();
		$this->assertNotNull( $this->enviar( $e['head'], $e['application'], $this->firmado( $e['application'] ) ) );
		$this->assertFalse( ReceiptSignature::can_sign( $e['head'], $e['application'] ) );
		$this->assertNull( $this->enviar( $e['head'], $e['application'], $this->firmado( $e['application'] ) ) );

		$sin = $this->escenario( false );
		$this->assertFalse( ReceiptSignature::can_sign( $sin['head'], $sin['application'] ) );
		update_post_meta( $e['procedure'], ProcedureMetaKeys::ARCHIVED, true );
		$this->assertFalse( ReceiptSignature::can_sign( $e['head'], $e['application'] ) );
	}

	// ─── la descarga ───────────────────────────────────────────────────────

	/**
	 * Lo descargan el centro y quien revisa; ni otro centro, ni quien
	 * consulta la zona, ni sin sesión.
	 */
	public function test_the_school_and_whoever_reviews_download_it() {
		$e = $this->escenario();
		$this->enviar( $e['head'], $e['application'], $this->firmado( $e['application'] ) );

		$this->assertStringStartsWith( '%PDF', $this->descargar( $e['head'], $e['application'] ) );
		$this->assertStringStartsWith( '%PDF', $this->descargar( $e['manager'], $e['application'] ) );
		$observa = $this->observer( array( 'Z0001' ) );
		$this->assertTrue( \Prc\Access\ProcedureAccess::can_view_application( $observa, $e['application'] ), 'quien consulta sí ve la solicitud' );
		foreach ( array( $observa, $this->school_head( 'C0002' ), $this->manager( array( $this->area( 'Otro' ) ) ), 0 ) as $uid ) {
			$cuerpo = $this->descargar( $uid, $e['application'] );
			$this->assertStringNotContainsString( '%PDF', $cuerpo, (string) $uid );
		}
	}

	/**
	 * Borrar la solicitud para siempre se lleva el fichero firmado.
	 */
	public function test_deleting_the_application_for_good_erases_the_file() {
		$e = $this->escenario();
		$this->enviar( $e['head'], $e['application'], $this->firmado( $e['application'] ) );
		$camino = ApplicationFiles::path( ReceiptSignature::signed( $e['application'] ) );
		$this->assertFileExists( $camino );
		// Borrar para siempre es cosa de administración (ADR-0044).
		$this->acting_as( $this->administrator() );
		wp_delete_post( $e['application'], true );
		$this->assertFileDoesNotExist( $camino );
	}

	// ─── dónde se ve ───────────────────────────────────────────────────────

	/**
	 * «Pendiente de firma» o «Firmado» en la fila, en el filtro de la tabla,
	 * en el justificante y en la exportación, solo si el procedimiento la pide.
	 */
	public function test_the_state_shows_in_the_table_the_filter_the_receipt_and_the_export() {
		$e    = $this->escenario();
		$otra = $this->application( $e['procedure'], $this->school_head( 'C0002' ), 'C0002' );
		$this->enviar( $e['head'], $e['application'], $this->firmado( $e['application'] ) );

		$filas = array_column( Applications::rows( $e['procedure'] ), null, 'id' );
		$this->assertStringStartsWith( 'Firmado el ', $filas[ (string) $e['application'] ]['signature'] );
		$this->assertSame( 'Pendiente de firma', $filas[ (string) $otra ]['signature'] );

		$res = ApplicationQuery::run( $e['procedure'], ApplicationFilter::sanitize( array( ApplicationFilter::ARG_UNSIGNED => '1' ) ) );
		$this->assertSame( array( $otra ), $res['ids'] );
		$this->assertSame( 1, $res['unsigned'] );
		$this->assertTrue( $res['may_be_unsigned'] );

		$this->assertArrayHasKey( 'signature', ApplicationExport::groups( $e['procedure'] )['review'] );
		$this->assertStringContainsString( '"Pendiente de firma"', ApplicationExport::csv( $e['procedure'], array( 'code', 'signature' ) ) );
		// Con «Todas las columnas» y la casilla marcada, también sale.
		$this->assertContains(
			'signature',
			ApplicationExport::sanitize_choice(
				$e['procedure'],
				array(
					'all'     => '1',
					'columns' => array( 'code', 'signature' ),
				)
			)['columns']
		);

		$this->olvidar();
		$this->assertStringContainsString( 'Firmado el ', $this->pantalla( $e['head'], $e['procedure'], array( ApplicationProgress::ARG_RECEIPT => '1' ) ) );

		$sin  = $this->escenario( false );
		$fila = Applications::rows( $sin['procedure'] )[0];
		$this->assertSame( '', $fila['signature'] );
		$this->assertArrayNotHasKey( 'signature', ApplicationExport::groups( $sin['procedure'] )['review'] );
		$this->assertFalse( ApplicationQuery::run( $sin['procedure'], ApplicationFilter::sanitize( array() ) )['may_be_unsigned'] );
	}
}
