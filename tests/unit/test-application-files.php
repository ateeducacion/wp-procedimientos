<?php
/**
 * Tests for the private documents of an application.
 *
 * @package Prc
 */

use Prc\Domain\ProcedureQuestions;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ApplicationMetaRegistration;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ApplicationPostType;
use Prc\PublicFront\ApplicationFiles;
use Prc\PublicFront\ApplyForm;
use Prc\PublicFront\Applications;
use Prc\PublicFront\ProcedureEditor;
use Prc\PublicFront\View\ApplyFormView;
use Prc\PublicFront\View\ProcedureEditorView;

/**
 * Un documento aportado en una solicitud **no es un adjunto de WordPress**.
 *
 * Es la invariante de la ADR-0028, y se comprueba de las dos maneras que
 * tienen sentido:
 *
 * 1. Después de guardar un documento de un centro, en `wp_posts` **no hay ni
 *    un adjunto más**. No hace falta filtrar nada para que no salga en la
 *    biblioteca de medios, en `wp/v2/media` ni en una página de adjunto:
 *    sencillamente no existe como adjunto.
 * 2. Y a la vez, una imagen normal del sitio **sigue siendo** un adjunto con
 *    su URL pública. La privacidad la decide quién creó el fichero y para qué,
 *    no volver privada la biblioteca entera.
 *
 * Lo demás que se comprueba aquí es lo que sostiene esa decisión: la lista
 * cerrada de tipos, el tope de tamaño, el nombre físico que no cuenta nada, la
 * autorización por la política de solicitudes que ya existe, y que un fallo no
 * deja estado a medias.
 */
class Test_Application_Files extends WP_UnitTestCase {

	// Con alias porque esta clase define su propio `tear_down()`: un método de
	// la clase gana al del trait, así que sin esto el de las fixtures **no se
	// llamaría** y `$_FILES` y `$_GET` se colarían de un test al siguiente.
	use Prc_Fixtures {
		tear_down as fixtures_tear_down;
	}

	/**
	 * Los ficheros temporales que ha fabricado un test.
	 *
	 * @var string[]
	 */
	private $temporales = array();

	/**
	 * Cada test empieza con la raíz privada vacía y suya.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		add_filter( 'prc_private_files_dir', array( $this, 'raiz_de_prueba' ) );
		$this->olvidar_rechazo();
	}

	/**
	 * El motivo del último envío rechazado vive en una estática: en producción
	 * muere con la petición; aquí hay que soltarlo entre tests.
	 *
	 * @return void
	 */
	private function olvidar_rechazo(): void {
		\Closure::bind(
			static function (): void {
				self::$rejected = '';
			},
			null,
			ApplyForm::class
		)();
	}

	/**
	 * Por qué se rechazó el último envío.
	 *
	 * Comprobarlo importa: sin esto, un test que solo mire que no hubo
	 * redirección pasa igual **aunque el envío se haya rechazado por otro
	 * motivo**, y entonces no prueba lo que dice probar.
	 *
	 * @return string
	 */
	private function el_rechazo(): string {
		return (string) \Closure::bind(
			static function (): string {
				return self::$rejected;
			},
			null,
			ApplyForm::class
		)();
	}

	/**
	 * Y se la lleva al terminar, con los temporales que fabricó.
	 *
	 * @return void
	 */
	public function tear_down() {
		$raiz = $this->raiz_de_prueba();
		if ( $this->fs()->is_dir( $raiz ) ) {
			// Recursivo: un fichero en modo 0200 se borra igual, porque el
			// permiso que hace falta para borrar es el del directorio.
			$this->fs()->delete( $raiz, true );
		}
		foreach ( $this->temporales as $ruta ) {
			$this->fs()->delete( $ruta );
		}
		$this->temporales = array();
		// Y **todos**, no solo el nuestro: `almacen_roto()` engancha un cierre
		// anónimo que no se puede quitar por referencia, y dejarlo puesto
		// haría que el test siguiente escribiera en una raíz rota.
		remove_all_filters( 'prc_private_files_dir' );
		$this->fixtures_tear_down();
	}

	/**
	 * La raíz privada de los tests, fuera de `uploads/`.
	 *
	 * @return string
	 */
	public function raiz_de_prueba(): string {
		return rtrim( get_temp_dir(), '/' ) . '/prc-private-test';
	}

	/**
	 * La API de ficheros de WordPress, que es con la que trabaja el aplicativo.
	 *
	 * @return \WP_Filesystem_Base
	 */
	private function fs(): \WP_Filesystem_Base {
		global $wp_filesystem;
		if ( ! $wp_filesystem instanceof \WP_Filesystem_Base ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}
		return $wp_filesystem;
	}

	// ─── utilidades ────────────────────────────────────────────────────────

	/**
	 * Un fichero temporal con ese contenido, listo para entrar por `$_FILES`.
	 *
	 * @param string $nombre    Original file name.
	 * @param string $contenido Bytes.
	 * @return array{name:string, tmp_name:string, size:int, error:int}
	 */
	private function fichero( string $nombre, string $contenido ): array {
		$tmp = tempnam( get_temp_dir(), 'prcq' );
		$this->fs()->put_contents( $tmp, $contenido, FS_CHMOD_FILE );
		$this->temporales[] = $tmp;

		return array(
			'name'     => $nombre,
			'tmp_name' => $tmp,
			'size'     => strlen( $contenido ),
			'error'    => UPLOAD_ERR_OK,
		);
	}

	/**
	 * Un PDF que `finfo` reconoce como tal.
	 *
	 * @param string $nombre Original file name.
	 * @return array{name:string, tmp_name:string, size:int, error:int}
	 */
	private function un_pdf( string $nombre = 'acta-del-claustro-CEIP-Ejemplo.pdf' ): array {
		return $this->fichero( $nombre, "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n" );
	}

	/**
	 * Una pregunta de tipo fichero, normalizada.
	 *
	 * @param bool $obligatoria Whether it is required.
	 * @return array<int, array<string, mixed>>
	 */
	private function preguntas( bool $obligatoria = true ): array {
		return ProcedureQuestions::sanitize(
			array(
				array(
					'key'      => 'q1',
					'label'    => 'Acta del claustro',
					'type'     => ProcedureQuestions::TYPE_FILE,
					'required' => $obligatoria,
				),
			)
		);
	}

	/**
	 * Un procedimiento con una pregunta de fichero, y una solicitud suya.
	 *
	 * @return array{procedure:int, application:int, area:int, head:int}
	 */
	private function una_solicitud_con_pregunta_de_fichero(): array {
		$this->app();
		$area   = $this->area( 'Innovación' );
		$centro = $this->school_head( 'C0001' );
		$proc   = $this->procedure(
			$this->administrator(),
			array( $area ),
			array( ProcedureMetaKeys::QUESTIONS => $this->preguntas() )
		);

		return array(
			'procedure'   => $proc,
			'application' => $this->application( $proc, $centro, 'C0001' ),
			'area'        => $area,
			'head'        => $centro,
		);
	}

	/**
	 * Cuántos adjuntos hay ahora mismo en la base de datos.
	 *
	 * @return int
	 */
	private function cuantos_adjuntos(): int {
		return count(
			get_posts(
				array(
					'post_type'   => 'attachment',
					'post_status' => 'any',
					'numberposts' => -1,
					'fields'      => 'ids',
				)
			)
		);
	}

	// ─── el modelo ─────────────────────────────────────────────────────────

	/**
	 * `file` es un tipo válido, sin opciones y con su clave intacta.
	 */
	public function test_file_is_a_type_without_choices() {
		$leidas = ProcedureQuestions::sanitize(
			array(
				array(
					'key'      => 'q3',
					'label'    => 'Acta del claustro',
					'type'     => 'file',
					'choices'  => array( 'esto', 'sobra' ),
					'required' => true,
				),
			)
		);

		$this->assertCount( 1, $leidas );
		$this->assertSame( ProcedureQuestions::TYPE_FILE, $leidas[0]['type'] );
		$this->assertSame( 'q3', $leidas[0]['key'], 'La clave de una pregunta no cambia.' );
		$this->assertSame( array(), $leidas[0]['choices'], 'Un fichero no tiene opciones.' );
		$this->assertTrue( $leidas[0]['required'] );
		$this->assertFalse( ProcedureQuestions::has_choices( ProcedureQuestions::TYPE_FILE ) );
	}

	/**
	 * Una pregunta de fichero no es una respuesta: ni dato ni error en la meta.
	 */
	public function test_a_file_question_never_lands_in_the_answers() {
		$preguntas = ProcedureQuestions::sanitize(
			array(
				array(
					'key'      => 'q1',
					'label'    => 'Acta del claustro',
					'type'     => 'file',
					'required' => true,
				),
				array(
					'key'      => 'q2',
					'label'    => 'Motivación',
					'type'     => 'text',
					'required' => false,
				),
			)
		);

		$v = ProcedureQuestions::validate_answers( $preguntas, array( 'q2' => 'porque sí' ) );

		$this->assertTrue( $v['ok'], 'El fichero se comprueba en el borde, no aquí.' );
		$this->assertSame( array(), $v['errors'] );
		$this->assertArrayNotHasKey( 'q1', $v['data'] );
		$this->assertSame( 'porque sí', $v['data']['q2'] );
	}

	// ─── guardar ───────────────────────────────────────────────────────────

	/**
	 * La invariante: un documento de un centro no crea ningún adjunto.
	 */
	public function test_a_school_document_creates_no_wordpress_attachment() {
		$datos  = $this->una_solicitud_con_pregunta_de_fichero();
		$cuando = $this->cuantos_adjuntos();

		$this->assertTrue( ApplicationFiles::store_all( $datos['application'], array( 'q1' => $this->un_pdf() ) ) );

		$this->assertSame(
			$cuando,
			$this->cuantos_adjuntos(),
			'Guardar un documento privado no puede crear un adjunto de WordPress (ADR-0028).'
		);
		$this->assertSame(
			array(),
			get_posts(
				array(
					'post_type'   => 'attachment',
					'post_status' => 'any',
					'numberposts' => -1,
					's'           => 'acta',
					'fields'      => 'ids',
				)
			)
		);
	}

	/**
	 * El descriptor guardado es un descriptor, y no una ruta ni una dirección.
	 */
	public function test_the_descriptor_holds_no_path_and_no_url() {
		$datos = $this->una_solicitud_con_pregunta_de_fichero();
		$pdf   = $this->un_pdf();

		$this->assertTrue( ApplicationFiles::store_all( $datos['application'], array( 'q1' => $pdf ) ) );

		$d = ApplicationFiles::descriptors( $datos['application'] )['q1'];

		$this->assertMatchesRegularExpression( '/^[a-f0-9]{32}$/', $d['id'] );
		$this->assertSame( 'acta-del-claustro-CEIP-Ejemplo.pdf', $d['name'] );
		$this->assertSame( 'application/pdf', $d['mime'] );
		$this->assertSame( $pdf['size'], $d['size'] );
		$this->assertSame( hash( 'sha256', (string) $this->fs()->get_contents( $pdf['tmp_name'] ) ), $d['sha256'] );

		$this->assertMatchesRegularExpression(
			'#^[a-f0-9]{2}/[a-f0-9]{2}/[a-f0-9]{32}\.pdf$#',
			$d['stored'],
			'`stored` es relativo a la raíz privada, y nada más.'
		);
		$json = (string) wp_json_encode( $d );
		$this->assertStringNotContainsString( 'http', $json );
		$this->assertStringNotContainsString( ABSPATH, $json );
		$this->assertStringNotContainsString( 'uploads', $json );
	}

	/**
	 * El nombre físico no cuenta de qué centro es, ni cómo se llamaba.
	 */
	public function test_the_stored_name_says_nothing() {
		$datos = $this->una_solicitud_con_pregunta_de_fichero();
		ApplicationFiles::store_all( $datos['application'], array( 'q1' => $this->un_pdf() ) );

		$d = ApplicationFiles::descriptors( $datos['application'] )['q1'];
		foreach ( array( 'acta', 'claustro', 'ejemplo', 'c0001', 'programa' ) as $dato ) {
			$this->assertStringNotContainsStringIgnoringCase( $dato, $d['stored'] );
		}
	}

	/**
	 * Un tipo que no está en la lista no entra, se llame como se llame.
	 */
	public function test_a_forbidden_type_is_refused() {
		$prohibidos = array(
			$this->fichero( 'shell.php', "<?php echo 'hola';" ),
			$this->fichero( 'pagina.html', '<html><body>hola</body></html>' ),
			$this->fichero( 'dibujo.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>' ),
			$this->fichero( 'guion.js', 'alert(1);' ),
			$this->fichero( 'paquete.zip', "PK\x03\x04" . str_repeat( "\0", 40 ) ),
			// El truco de siempre: extensión permitida, contenido que no lo es.
			$this->fichero( 'shell.pdf', "<?php echo 'hola';" ),
		);

		foreach ( $prohibidos as $fichero ) {
			$this->assertSame(
				'file_type',
				ApplicationFiles::refuse( $fichero ),
				'No se admite ' . $fichero['name'] . '.'
			);
		}
	}

	/**
	 * Una imagen permitida se guarda igual.
	 */
	public function test_an_allowed_image_is_stored() {
		$datos = $this->una_solicitud_con_pregunta_de_fichero();
		$png   = $this->fichero( 'foto.png', (string) $this->fs()->get_contents( DIR_TESTDATA . '/images/test-image.png' ) );

		$this->assertTrue( ApplicationFiles::store_all( $datos['application'], array( 'q1' => $png ) ) );
		$this->assertSame( 'image/png', ApplicationFiles::descriptors( $datos['application'] )['q1']['mime'] );
	}

	/**
	 * Un fichero por encima del tope no entra.
	 */
	public function test_a_file_over_the_limit_is_refused() {
		$grande         = $this->un_pdf();
		$grande['size'] = ApplicationFiles::max_bytes() + 1;
		$this->assertSame( 'file_too_big', ApplicationFiles::refuse( $grande ) );

		$del_servidor          = $this->un_pdf();
		$del_servidor['error'] = UPLOAD_ERR_INI_SIZE;
		$this->assertSame( 'file_too_big', ApplicationFiles::refuse( $del_servidor ) );

		$this->assertLessThanOrEqual( 10 * MB_IN_BYTES, ApplicationFiles::max_bytes() );
	}

	/**
	 * Un envío a medias no entra.
	 */
	public function test_a_broken_upload_is_refused() {
		$roto          = $this->un_pdf();
		$roto['error'] = UPLOAD_ERR_PARTIAL;
		$this->assertSame( 'file_broken', ApplicationFiles::refuse( $roto ) );
	}

	/**
	 * Si uno falla, no queda ni el que ya se había guardado ni el descriptor.
	 */
	public function test_a_failure_leaves_no_partial_state() {
		$datos = $this->una_solicitud_con_pregunta_de_fichero();

		$ok = ApplicationFiles::store_all(
			$datos['application'],
			array(
				'q1' => $this->un_pdf(),
				'q2' => $this->fichero( 'shell.php', "<?php echo 'hola';" ),
			)
		);

		$this->assertFalse( $ok );
		$this->assertSame( array(), ApplicationFiles::descriptors( $datos['application'] ), 'Ni un descriptor sin fichero.' );
		$this->assertSame( array(), glob( $this->raiz_de_prueba() . '/*/*/*' ), 'Ni un fichero sin descriptor.' );
	}

	/**
	 * Sustituir un documento guarda primero el nuevo y borra después el viejo.
	 */
	public function test_replacing_a_document_keeps_the_old_one_until_the_new_one_is_in() {
		$datos = $this->una_solicitud_con_pregunta_de_fichero();
		ApplicationFiles::store_all( $datos['application'], array( 'q1' => $this->un_pdf( 'primera.pdf' ) ) );
		$antes = ApplicationFiles::descriptors( $datos['application'] )['q1'];

		ApplicationFiles::store_all( $datos['application'], array( 'q1' => $this->un_pdf( 'segunda.pdf' ) ) );
		$ahora = ApplicationFiles::descriptors( $datos['application'] )['q1'];

		$this->assertSame( 'segunda.pdf', $ahora['name'] );
		$this->assertNotSame( $antes['stored'], $ahora['stored'] );
		$this->assertFileExists( ApplicationFiles::path( $ahora ) );
		$this->assertFileDoesNotExist( ApplicationFiles::path( $antes ), 'El viejo se va cuando el nuevo ya está.' );
	}

	/**
	 * Un fallo al sustituir deja la solicitud exactamente como estaba.
	 */
	public function test_a_failed_replacement_changes_nothing() {
		$datos = $this->una_solicitud_con_pregunta_de_fichero();
		ApplicationFiles::store_all( $datos['application'], array( 'q1' => $this->un_pdf( 'buena.pdf' ) ) );
		$antes = ApplicationFiles::descriptors( $datos['application'] );

		$this->assertFalse(
			ApplicationFiles::store_all( $datos['application'], array( 'q1' => $this->fichero( 'mala.php', '<?php exit;' ) ) )
		);

		$this->assertSame( $antes, ApplicationFiles::descriptors( $datos['application'] ) );
		$this->assertFileExists( ApplicationFiles::path( $antes['q1'] ) );
	}

	/**
	 * Reenviar el formulario sin adjuntar no vacía lo presentado.
	 */
	public function test_resubmitting_without_a_file_keeps_the_stored_one() {
		$preguntas = $this->preguntas( true );
		$guardados = array(
			'q1' => array(
				'id'     => str_repeat( 'a', 32 ),
				'name'   => 'acta.pdf',
				'stored' => 'aa/bb/' . str_repeat( 'a', 32 ) . '.pdf',
			),
		);

		$sin_nada = ApplicationFiles::submitted( $preguntas, $guardados );
		$this->assertTrue( $sin_nada['ok'], 'Ya tiene el documento: no se vuelve a pedir.' );
		$this->assertSame( array(), $sin_nada['files'] );

		$sin_nada_ni_antes = ApplicationFiles::submitted( $preguntas, array() );
		$this->assertFalse( $sin_nada_ni_antes['ok'] );
		$this->assertSame( array( 'file_missing' ), $sin_nada_ni_antes['errors'] );
	}

	/**
	 * El fichero guardado no se puede leer directamente del disco.
	 */
	public function test_a_stored_file_is_not_readable_from_outside() {
		$datos = $this->una_solicitud_con_pregunta_de_fichero();
		ApplicationFiles::store_all( $datos['application'], array( 'q1' => $this->un_pdf() ) );

		$d      = ApplicationFiles::descriptors( $datos['application'] )['q1'];
		$camino = ApplicationFiles::path( $d );

		$this->assertFileExists( $camino );
		$this->assertSame( '0200', substr( sprintf( '%o', fileperms( $camino ) ), -4 ) );
		$this->assertFileExists( $this->raiz_de_prueba() . '/.htaccess' );
		// Y el aplicativo sí lo lee, abriéndolo y volviéndolo a cerrar.
		$this->assertSame( '%PDF', substr( (string) ApplicationFiles::read( $d ), 0, 4 ) );
		$this->assertSame( '0200', substr( sprintf( '%o', fileperms( $camino ) ), -4 ) );
	}

	/**
	 * Un `stored` que no tenga nuestra forma no resuelve a ninguna ruta.
	 */
	public function test_a_path_outside_the_private_root_resolves_to_nothing() {
		foreach ( array( '../../wp-config.php', '/etc/passwd', 'ab/cd/../../../x.pdf', 'ab/cd/ef.exe' ) as $malo ) {
			$this->assertSame( '', ApplicationFiles::path( array( 'stored' => $malo ) ) );
		}
	}

	// ─── la autorización ───────────────────────────────────────────────────

	/**
	 * Pedir un documento y devolver lo servido.
	 *
	 * @param int    $application_id Application post ID.
	 * @param string $file_id        Opaque file ID.
	 * @return string
	 */
	private function pedir( int $application_id, string $file_id ): string {
		$_GET = array(
			ApplicationFiles::ARG_APPLICATION => $application_id,
			ApplicationFiles::ARG_FILE        => $file_id,
		);
		return $this->served( array( ApplicationFiles::class, 'handle' ) );
	}

	/**
	 * Un documento guardado, con quién es quién alrededor.
	 *
	 * @return array{procedure:int, application:int, area:int, head:int, file:string}
	 */
	private function un_documento_guardado(): array {
		$datos = $this->una_solicitud_con_pregunta_de_fichero();
		ApplicationFiles::store_all( $datos['application'], array( 'q1' => $this->un_pdf() ) );
		$datos['file'] = ApplicationFiles::descriptors( $datos['application'] )['q1']['id'];
		return $datos;
	}

	/**
	 * El centro que la presentó se descarga su documento.
	 */
	public function test_the_school_that_applied_downloads_its_own_document() {
		$d = $this->un_documento_guardado();
		$this->acting_as( $d['head'] );

		$this->assertSame( '%PDF-1.4', substr( $this->pedir( $d['application'], $d['file'] ), 0, 8 ) );
	}

	/**
	 * Otro centro no se descarga el de este.
	 */
	public function test_another_school_gets_nothing() {
		$d     = $this->un_documento_guardado();
		$otros = $this->school_head( 'C0002' );

		$this->acting_as( $otros );
		$cuerpo = $this->pedir( $d['application'], $d['file'] );

		$this->assertStringNotContainsString( '%PDF', $cuerpo );
		$this->assertStringContainsString( 'No puede descargar', $cuerpo );
	}

	/**
	 * Quien revisa ese procedimiento sí.
	 */
	public function test_whoever_reviews_the_procedure_downloads_it() {
		$d      = $this->un_documento_guardado();
		$gestor = $this->manager( array( $d['area'] ) );

		$this->acting_as( $gestor );

		$this->assertSame( '%PDF-1.4', substr( $this->pedir( $d['application'], $d['file'] ), 0, 8 ) );
	}

	/**
	 * Quien gestiona otro ámbito no.
	 */
	public function test_a_manager_of_another_scope_gets_nothing() {
		$d     = $this->un_documento_guardado();
		$otra  = $this->area( 'Otro ámbito' );
		$ajeno = $this->manager( array( $otra ) );

		$this->acting_as( $ajeno );
		$cuerpo = $this->pedir( $d['application'], $d['file'] );

		$this->assertStringNotContainsString( '%PDF', $cuerpo );
		$this->assertStringContainsString( 'No puede descargar', $cuerpo );
	}

	/**
	 * Sin sesión, no.
	 */
	public function test_anonymous_gets_nothing() {
		$d = $this->un_documento_guardado();
		$this->acting_as( 0 );

		$this->assertStringNotContainsString( '%PDF', $this->pedir( $d['application'], $d['file'] ) );
	}

	/**
	 * El identificador de un documento de otra solicitud no vale aquí.
	 */
	public function test_a_file_id_of_another_application_is_not_found() {
		$uno  = $this->un_documento_guardado();
		$otro = $this->application( $uno['procedure'], $this->school_head( 'C0003' ), 'C0003' );
		ApplicationFiles::store_all( $otro, array( 'q1' => $this->un_pdf() ) );

		$this->assertNull(
			ApplicationFiles::find( $otro, $uno['file'] ),
			'Un identificador opaco solo vale dentro de su solicitud.'
		);
	}

	/**
	 * La respuesta va como descarga, con su tipo y sin caché compartida.
	 */
	public function test_the_response_headers_are_the_right_ones() {
		$this->assertSame(
			array(
				'Content-Type: application/pdf',
				'Content-Disposition: attachment; filename="acta.pdf"',
				'Content-Length: 1234',
				'X-Content-Type-Options: nosniff',
				'Cache-Control: private, no-store',
			),
			ApplicationFiles::headers(
				array(
					'name' => 'acta.pdf',
					'mime' => 'application/pdf',
				),
				1234
			)
		);
	}

	// ─── el ciclo de vida ──────────────────────────────────────────────────

	/**
	 * Borrar definitivamente una solicitud se lleva sus documentos.
	 */
	public function test_deleting_an_application_for_good_deletes_its_files() {
		$d      = $this->un_documento_guardado();
		$camino = ApplicationFiles::path( ApplicationFiles::descriptors( $d['application'] )['q1'] );
		$this->assertFileExists( $camino );

		wp_delete_post( $d['application'], true );

		$this->assertFileDoesNotExist( $camino, 'El borrado definitivo se lleva el fichero (ADR-0028).' );
	}

	/**
	 * La papelera no: borrar es enviar a la papelera (ADR-0007) y se restaura.
	 */
	public function test_trashing_an_application_keeps_its_files() {
		$d      = $this->un_documento_guardado();
		$camino = ApplicationFiles::path( ApplicationFiles::descriptors( $d['application'] )['q1'] );

		wp_trash_post( $d['application'] );

		$this->assertFileExists( $camino, 'Restaurar una solicitud sin sus documentos es restaurar otra cosa.' );
	}

	// ─── lo que manda el navegador de verdad ───────────────────────────────

	/**
	 * Poner esos ficheros en `$_FILES`, como los manda el formulario.
	 *
	 * @param array<string, array<string, mixed>> $por_pregunta Question key => file.
	 * @return void
	 */
	private function en_files( array $por_pregunta ): void {
		$campo = array(
			'name'     => array(),
			'tmp_name' => array(),
			'size'     => array(),
			'error'    => array(),
			'type'     => array(),
		);
		foreach ( $por_pregunta as $key => $fichero ) {
			$campo['name'][ $key ]     = $fichero['name'];
			$campo['tmp_name'][ $key ] = $fichero['tmp_name'];
			$campo['size'][ $key ]     = $fichero['size'];
			$campo['error'][ $key ]    = $fichero['error'];
			// Lo que dice el navegador, que es justo de lo que no nos fiamos.
			$campo['type'][ $key ] = 'application/pdf';
		}
		$_FILES[ ApplicationFiles::FIELD ] = $campo;
	}

	/**
	 * Un documento que llega por `$_FILES` se recoge bajo su pregunta.
	 */
	public function test_a_file_arrives_under_its_question_key() {
		$this->en_files( array( 'q1' => $this->un_pdf() ) );

		$v = ApplicationFiles::submitted( $this->preguntas() );

		$this->assertTrue( $v['ok'] );
		$this->assertSame( array( 'q1' ), array_keys( $v['files'] ) );
		$this->assertSame( 'acta-del-claustro-CEIP-Ejemplo.pdf', $v['files']['q1']['name'] );
	}

	/**
	 * El nombre que manda el navegador se queda en su última parte.
	 *
	 * Es lo único que se puede teclear de un `$_FILES`, así que es por donde
	 * se intentaría salir del directorio.
	 */
	public function test_the_submitted_name_cannot_walk_out_of_its_directory() {
		$this->en_files( array( 'q1' => $this->un_pdf( '../../../etc/passwd.pdf' ) ) );

		$v = ApplicationFiles::submitted( $this->preguntas() );

		$this->assertSame( 'passwd.pdf', $v['files']['q1']['name'] );
	}

	/**
	 * Uno que no pasa la política se rechaza, y dice por qué.
	 */
	public function test_a_forbidden_file_from_the_browser_is_refused() {
		$this->en_files( array( 'q1' => $this->fichero( 'shell.php', "<?php echo 'hola';" ) ) );

		$v = ApplicationFiles::submitted( $this->preguntas() );

		$this->assertFalse( $v['ok'] );
		$this->assertSame( array( 'file_type' ), $v['errors'] );
		$this->assertSame( array(), $v['files'] );
	}

	/**
	 * Un campo de fichero que se deja vacío no es un error, si no es obligatorio.
	 *
	 * Es lo que manda el navegador de verdad cuando no se elige nada: el campo
	 * viaja igual, con el nombre en blanco y `UPLOAD_ERR_NO_FILE`.
	 */
	public function test_an_empty_file_field_is_not_a_file() {
		$_FILES[ ApplicationFiles::FIELD ] = array(
			'name'     => array( 'q1' => '' ),
			'tmp_name' => array( 'q1' => '' ),
			'size'     => array( 'q1' => 0 ),
			'error'    => array( 'q1' => UPLOAD_ERR_NO_FILE ),
			'type'     => array( 'q1' => '' ),
		);

		$v = ApplicationFiles::submitted( $this->preguntas( false ) );

		$this->assertTrue( $v['ok'] );
		$this->assertSame( array(), $v['files'] );
	}

	/**
	 * Una pregunta que no es de fichero no aporta ni error ni fichero.
	 */
	public function test_questions_that_are_not_files_are_skipped() {
		$mezcla = ProcedureQuestions::sanitize(
			array(
				array(
					'key'      => 'q1',
					'label'    => 'Observaciones',
					'type'     => ProcedureQuestions::TYPE_TEXT,
					'required' => true,
				),
			)
		);

		$v = ApplicationFiles::submitted( $mezcla );

		$this->assertTrue( $v['ok'], 'Sin preguntas de fichero no hay nada que comprobar aquí.' );
		$this->assertSame( array(), $v['files'] );
	}

	// ─── el borde: peticiones que no son nuestras, o que vienen tocadas ────

	/**
	 * Una petición sin nuestro parámetro no la toca nadie.
	 *
	 * Esto corre en **cada carga de página del sitio**, así que tiene que
	 * salirse sin mirar nada más.
	 */
	public function test_a_request_without_our_argument_is_left_alone() {
		$_GET = array( 'otra' => 'cosa' );

		$this->assertSame( '', $this->served( array( ApplicationFiles::class, 'handle' ) ) );
	}

	/**
	 * Un identificador de solicitud tocado a mano no abre nada.
	 */
	public function test_a_tampered_application_id_is_denied() {
		$d = $this->un_documento_guardado();
		$this->acting_as( $d['head'] );

		foreach ( array( 0, -1, $d['procedure'] ) as $tocado ) {
			$cuerpo = $this->pedir( (int) $tocado, $d['file'] );
			$this->assertStringNotContainsString( '%PDF', $cuerpo, 'No abre con: ' . $tocado );
			$this->assertStringContainsString( 'No puede descargar', $cuerpo );
		}
	}

	/**
	 * Un identificador de fichero que no tiene nuestra forma no se busca.
	 */
	public function test_a_malformed_file_id_finds_nothing() {
		$d = $this->un_documento_guardado();

		foreach ( array( 'nope', '../../etc/passwd', str_repeat( 'z', 32 ), '' ) as $malo ) {
			$this->assertNull( ApplicationFiles::find( $d['application'], $malo ) );
		}
	}

	/**
	 * Con el descriptor puesto y el fichero ya no en disco, se responde 404.
	 *
	 * Pasa de verdad: una restauración a medias, o una limpieza a mano. Lo que
	 * no puede pasar es que el aplicativo sirva basura o se caiga.
	 */
	public function test_a_descriptor_without_its_file_answers_that_it_is_gone() {
		$d = $this->un_documento_guardado();
		$this->fs()->delete( ApplicationFiles::path( ApplicationFiles::descriptors( $d['application'] )['q1'] ) );

		$this->acting_as( $d['head'] );
		$cuerpo = $this->pedir( $d['application'], $d['file'] );

		$this->assertStringContainsString( 'ya no está', $cuerpo );
		$this->assertNull( ApplicationFiles::read( ApplicationFiles::descriptors( $d['application'] )['q1'] ) );
	}

	/**
	 * Borrar cualquier otra cosa no dispara la limpieza.
	 *
	 * El gancho es global: cuelga de `before_delete_post` y lo recibe el
	 * borrado de cualquier entrada del sitio.
	 */
	public function test_deleting_anything_else_touches_no_file() {
		$d      = $this->un_documento_guardado();
		$camino = ApplicationFiles::path( ApplicationFiles::descriptors( $d['application'] )['q1'] );

		wp_delete_post( (int) self::factory()->post->create( array( 'post_type' => 'post' ) ), true );

		$this->assertFileExists( $camino );
		$this->assertCount( 1, ApplicationFiles::descriptors( $d['application'] ) );
	}

	// ─── guardas pequeñas y lo que se dice en pantalla ─────────────────────

	/**
	 * Sin solicitud no hay descriptores ni hay nada que guardar.
	 */
	public function test_no_application_means_nothing_to_store_or_read() {
		$this->app();

		$this->assertSame( array(), ApplicationFiles::descriptors( 0 ) );
		$this->assertFalse( ApplicationFiles::store_all( 0, array() ) );
		$this->assertTrue( ApplicationFiles::store_all( 1, array() ), 'Sin ficheros no hay nada que hacer.' );
	}

	/**
	 * La dirección de descarga lleva la solicitud y el fichero, y nada más.
	 */
	public function test_the_download_url_carries_only_what_it_needs() {
		$d   = $this->un_documento_guardado();
		$url = ApplicationFiles::url( $d['application'], $d['file'] );

		$this->assertSame( (string) $d['application'], $this->query_arg( $url, ApplicationFiles::ARG_APPLICATION ) );
		$this->assertSame( $d['file'], $this->query_arg( $url, ApplicationFiles::ARG_FILE ) );
		$this->assertStringNotContainsString( 'prc-private', $url, 'La dirección no dice dónde está nada.' );
	}

	/**
	 * Un descriptor sin nombre se sirve igual, con uno genérico.
	 */
	public function test_a_descriptor_without_a_name_still_downloads() {
		$cabeceras = ApplicationFiles::headers( array( 'mime' => 'application/pdf' ), 0 );

		$this->assertContains( 'Content-Disposition: attachment; filename="documento"', $cabeceras );
	}

	/**
	 * Cada motivo de rechazo se explica en castellano; lo que no conocemos, no.
	 */
	public function test_every_refusal_says_why() {
		$this->assertStringContainsString( 'obligatorio', ApplicationFiles::why( array( 'file_missing' ) ) );
		$this->assertStringContainsString( 'demasiado grande', ApplicationFiles::why( array( 'file_too_big' ) ) );
		$this->assertStringContainsString( 'no se admite', ApplicationFiles::why( array( 'file_type' ) ) );
		$this->assertStringContainsString( 'completo', ApplicationFiles::why( array( 'file_broken' ) ) );
		$this->assertSame( '', ApplicationFiles::why( array( 'lo_que_sea' ) ) );
		$this->assertSame( '', ApplicationFiles::why( array() ) );
	}

	/**
	 * La meta tira lo que no tenga forma de descriptor.
	 *
	 * Es la última red antes de la base de datos: aunque algo llegue aquí por
	 * un camino que hoy no existe, lo que no sea un descriptor nuestro no se
	 * guarda. En particular, **una ruta absoluta o una URL no pasan**.
	 */
	public function test_the_meta_throws_away_anything_that_is_not_a_descriptor() {
		$bueno = array(
			'id'     => str_repeat( 'a', 32 ),
			'name'   => 'acta.pdf',
			'mime'   => 'application/pdf',
			'size'   => 10,
			'sha256' => str_repeat( 'b', 64 ),
			'stored' => 'aa/bb/' . str_repeat( 'a', 32 ) . '.pdf',
		);

		$limpio = ApplicationMetaRegistration::sanitize_files(
			array(
				'q1'            => $bueno,
				// Una clave que no es de una pregunta nuestra.
				'no-es-una-key' => $bueno,
				// Un descriptor que no lo es.
				'q2'            => 'una cadena',
				// Ruta absoluta en `stored`.
				'q3'            => array_merge( $bueno, array( 'stored' => '/etc/passwd' ) ),
				// Una URL en `stored`.
				'q4'            => array_merge( $bueno, array( 'stored' => 'https://example.org/x.pdf' ) ),
				// Un identificador que no tiene nuestra forma.
				'q5'            => array_merge( $bueno, array( 'id' => 'corto' ) ),
				// Y un hash que tampoco.
				'q6'            => array_merge( $bueno, array( 'sha256' => 'nope' ) ),
			)
		);

		$this->assertSame( array( 'q1' ), array_keys( $limpio ) );
		$this->assertSame( $bueno, $limpio['q1'] );
		$this->assertSame( array(), ApplicationMetaRegistration::sanitize_files( 'ni esto es una lista' ) );
	}

	// ─── el almacén que no se puede escribir ───────────────────────────────

	/**
	 * Dejar la raíz privada donde no se puede crear.
	 *
	 * Un fichero no es un directorio, así que `wp_mkdir_p()` no puede colgar
	 * nada de él: es la forma limpia de comprobar qué pasa cuando el almacén
	 * no está disponible, sin romper nada más.
	 *
	 * @return void
	 */
	private function almacen_roto(): void {
		$tapon = tempnam( get_temp_dir(), 'prcno' );
		$this->fs()->put_contents( $tapon, 'no soy un directorio', FS_CHMOD_FILE );
		$this->temporales[] = $tapon;

		remove_filter( 'prc_private_files_dir', array( $this, 'raiz_de_prueba' ) );
		add_filter(
			'prc_private_files_dir',
			static function () use ( $tapon ): string {
				return $tapon . '/dentro';
			}
		);
	}

	/**
	 * Sin almacén no se guarda nada, y se dice que no.
	 */
	public function test_without_a_usable_store_nothing_is_saved() {
		$datos = $this->una_solicitud_con_pregunta_de_fichero();
		$this->almacen_roto();

		$this->assertFalse( ApplicationFiles::store_all( $datos['application'], array( 'q1' => $this->un_pdf() ) ) );
		$this->assertSame( array(), ApplicationFiles::descriptors( $datos['application'] ) );
	}

	// ─── lo que ve el centro y lo que ve quien revisa ──────────────────────

	/**
	 * Un procedimiento con el plazo abierto y una pregunta de fichero.
	 *
	 * @param bool $obligatoria Whether the file question is required.
	 * @return array{procedure:int, area:int, head:int}
	 */
	private function un_plazo_abierto( bool $obligatoria = true ): array {
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

		$hoy  = ProcedureState::today();
		$area = $this->area( 'Innovación' );

		return array(
			'procedure' => $this->procedure(
				$this->administrator(),
				array( $area ),
				array(
					ProcedureMetaKeys::OPENS_AT  => gmdate( 'Y-m-d', (int) strtotime( $hoy . ' -1 days' ) ),
					ProcedureMetaKeys::CLOSES_AT => gmdate( 'Y-m-d', (int) strtotime( $hoy . ' +1 days' ) ),
					ProcedureMetaKeys::QUESTIONS => $this->preguntas( $obligatoria ),
				)
			),
			'area'      => $area,
			'head'      => $this->school_head( 'C0001' ),
		);
	}

	/**
	 * La pantalla de solicitud, pintada para ese centro.
	 *
	 * @param array{procedure:int, head:int} $datos What un_plazo_abierto() returned.
	 * @return string
	 */
	private function pantalla( array $datos ): string {
		$this->acting_as( $datos['head'] );
		$_GET[ ApplyForm::ARG_PROCEDURE ] = (string) $datos['procedure'];
		return ApplyFormView::html( ApplyForm::model() );
	}

	/**
	 * El formulario del centro pinta el campo de fichero, y viaja como fichero.
	 *
	 * Es lo único que ve quien solicita, así que es lo que hay que comprobar:
	 * sin `multipart/form-data` el documento no llega, y el fallo no se nota
	 * hasta producción.
	 */
	public function test_the_application_form_paints_a_file_field() {
		$html = $this->pantalla( $this->un_plazo_abierto() );

		$this->assertStringContainsString( 'enctype="multipart/form-data"', $html, 'Sin esto el documento no sube.' );
		$this->assertStringContainsString( 'type="file"', $html );
		$this->assertStringContainsString( 'name="' . ApplicationFiles::FIELD . '[q1]"', $html );
		$this->assertStringContainsString( 'Acta del claustro', $html );
		$this->assertStringContainsString( 'application/pdf', $html );
		$this->assertStringContainsString( esc_html( size_format( ApplicationFiles::max_bytes() ) ), $html );
		// Sin Base64 por ningún lado: sube como fichero HTTP normal.
		$this->assertStringNotContainsString( 'base64', strtolower( $html ) );
	}

	/**
	 * Con un documento ya presentado, la pantalla lo ofrece y no vuelve a exigirlo.
	 *
	 * Una solicitud se edita mientras el plazo siga abierto (ADR-0019): si el
	 * campo siguiera siendo obligatorio, cambiar una coma obligaría a volver a
	 * adjuntar el acta.
	 */
	public function test_an_already_attached_document_is_offered_and_not_demanded_again() {
		$datos = $this->un_plazo_abierto( true );
		$this->acting_as( $datos['head'] );
		$solicitud = $this->application( $datos['procedure'], $datos['head'], 'C0001' );
		ApplicationFiles::store_all( $solicitud, array( 'q1' => $this->un_pdf() ) );
		$file = ApplicationFiles::descriptors( $solicitud )['q1']['id'];

		$_GET[ ApplyForm::ARG_PROCEDURE ] = (string) $datos['procedure'];
		$html                             = ApplyFormView::html( ApplyForm::model() );

		$this->assertStringContainsString( 'Ya adjuntó', $html );
		$this->assertStringContainsString( 'acta-del-claustro-CEIP-Ejemplo.pdf', $html );
		$this->assertStringContainsString( ApplicationFiles::ARG_FILE . '=' . $file, $html );
		$this->assertStringNotContainsString( 'prc-private', $html, 'Nunca la ruta física.' );
	}

	/**
	 * Cerrado el plazo, el resumen enseña el documento con su descarga.
	 */
	public function test_the_read_only_summary_offers_the_document() {
		$datos     = $this->un_plazo_abierto();
		$solicitud = $this->application( $datos['procedure'], $datos['head'], 'C0001' );
		ApplicationFiles::store_all( $solicitud, array( 'q1' => $this->un_pdf() ) );
		$file = ApplicationFiles::descriptors( $solicitud )['q1']['id'];

		// El plazo se cierra: ya no se edita, pero se consulta.
		$ayer = gmdate( 'Y-m-d', (int) strtotime( ProcedureState::today() . ' -1 days' ) );
		update_post_meta( $datos['procedure'], ProcedureMetaKeys::CLOSES_AT, $ayer );

		$html = $this->pantalla( $datos );

		$this->assertStringContainsString( 'LO QUE PRESENTÓ', $html );
		$this->assertStringContainsString( ApplicationFiles::ARG_FILE . '=' . $file, $html );
		$this->assertStringContainsString( 'acta-del-claustro-CEIP-Ejemplo.pdf', $html );
	}

	/**
	 * El panel «Solicitudes» del taller, pintado por quien lo gestiona.
	 *
	 * Con el modelo de verdad, no uno inventado: lo que se comprueba es que el
	 * enlace llega hasta la pantalla.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @param int $area         Its ámbito, to give the manager the scope.
	 * @return string
	 */
	private function panel_de_solicitudes( int $procedure_id, int $area ): string {
		$this->pages();
		$this->acting_as( $this->manager( array( $area ) ) );
		$_GET[ ProcedureEditor::ARG_PROCEDURE ] = (string) $procedure_id;
		$_GET[ ProcedureEditor::ARG_PANEL ]     = ProcedureEditor::PANEL_APPLICATIONS;
		return ProcedureEditorView::html( ProcedureEditor::model() );
	}

	/**
	 * Quien revisa ve el documento con su enlace, no con su ruta.
	 */
	public function test_the_review_panel_offers_the_download() {
		$d    = $this->un_documento_guardado();
		$html = $this->panel_de_solicitudes( $d['procedure'], $d['area'] );

		$this->assertStringContainsString( ApplicationFiles::ARG_FILE . '=' . $d['file'], $html );
		$this->assertStringContainsString( 'acta-del-claustro-CEIP-Ejemplo.pdf', $html );
		$this->assertStringNotContainsString( 'prc-private', $html );
	}

	/**
	 * Sin documento, la celda de esa pregunta dice que no hay, y no enlaza.
	 */
	public function test_the_review_panel_says_when_there_is_no_document() {
		$datos = $this->una_solicitud_con_pregunta_de_fichero();
		$html  = $this->panel_de_solicitudes( $datos['procedure'], $datos['area'] );

		$this->assertStringNotContainsString( ApplicationFiles::ARG_FILE . '=', $html );
		$this->assertStringContainsString( 'Sin documento', $html );
	}

	// ─── el alta entera ────────────────────────────────────────────────────

	/**
	 * Presentar la solicitud con su documento, por el camino del formulario.
	 *
	 * @param array{procedure:int, head:int} $datos What un_plazo_abierto() returned.
	 * @return string|null La URL de vuelta; null si el envío se rechazó.
	 */
	private function presentar( array $datos ): ?string {
		$this->acting_as( $datos['head'] );
		$this->post(
			array(
				ApplyForm::FIELD_OP        => ApplyForm::OP_APPLY,
				ApplyForm::FIELD_PROCEDURE => (string) $datos['procedure'],
				ApplyForm::FIELD_POSITION  => ApplicationMetaKeys::POSITION_HEAD,
			),
			ApplyForm::NONCE_ACTION,
			ApplyForm::NONCE_FIELD
		);
		return $this->exit_url( array( ApplyForm::class, 'handle' ) );
	}

	/**
	 * El alta completa guarda la solicitud y su documento, y sigue sin adjuntos.
	 */
	public function test_a_whole_application_stores_its_document_and_no_attachment() {
		$datos   = $this->un_plazo_abierto();
		$cuantos = $this->cuantos_adjuntos();
		$this->en_files( array( 'q1' => $this->un_pdf() ) );

		$this->assertNotNull( $this->presentar( $datos ), 'El envío sale por su redirección.' );

		$solicitud = Applications::find( $datos['procedure'], 'C0001' );
		$this->assertNotNull( $solicitud );
		$this->assertCount( 1, ApplicationFiles::descriptors( (int) $solicitud ) );
		$this->assertSame( $cuantos, $this->cuantos_adjuntos() );
	}

	/**
	 * Sin el documento obligatorio no se crea ninguna solicitud, y se dice.
	 */
	public function test_a_missing_required_document_creates_no_application() {
		$datos = $this->un_plazo_abierto( true );

		$this->assertNull( $this->presentar( $datos ), 'Un envío rechazado no redirige.' );
		$this->assertSame(
			'Falta un documento obligatorio.',
			$this->el_rechazo(),
			'Y se rechaza por el documento que falta, no por otra cosa.'
		);
		$this->assertNull( Applications::find( $datos['procedure'], 'C0001' ) );
	}

	/**
	 * Un tipo prohibido tampoco crea solicitud, y dice por qué.
	 */
	public function test_a_forbidden_document_creates_no_application() {
		$datos = $this->un_plazo_abierto();
		$this->en_files( array( 'q1' => $this->fichero( 'shell.php', "<?php echo 'hola';" ) ) );

		$this->assertNull( $this->presentar( $datos ) );
		$this->assertStringContainsString( 'no se admite', $this->el_rechazo() );
		$this->assertNull( Applications::find( $datos['procedure'], 'C0001' ) );
	}

	/**
	 * Y si además falla el núcleo, manda el núcleo.
	 *
	 * Los dos motivos se pueden dar a la vez; se dice el del formulario, que
	 * es el que la persona puede arreglar mirando la pantalla.
	 */
	public function test_when_the_core_also_fails_the_core_is_what_gets_said() {
		$datos = $this->un_plazo_abierto();
		$this->en_files( array( 'q1' => $this->fichero( 'shell.php', '<?php exit;' ) ) );

		$this->acting_as( $datos['head'] );
		$this->post(
			array(
				ApplyForm::FIELD_OP        => ApplyForm::OP_APPLY,
				ApplyForm::FIELD_PROCEDURE => (string) $datos['procedure'],
				// Sin cargo: el núcleo tampoco pasa.
				ApplyForm::FIELD_POSITION  => '',
			),
			ApplyForm::NONCE_ACTION,
			ApplyForm::NONCE_FIELD
		);
		$this->assertNull( $this->exit_url( array( ApplyForm::class, 'handle' ) ) );

		$this->assertStringContainsString( 'el cargo', $this->el_rechazo() );
		$this->assertNull( Applications::find( $datos['procedure'], 'C0001' ) );
	}

	/**
	 * Si el documento no se puede guardar, no queda solicitud.
	 *
	 * Es la regla de la ADR-0028 comprobada de punta a punta y por el camino
	 * que de verdad se recorre: el formulario.
	 */
	public function test_an_application_whose_document_cannot_be_stored_leaves_nothing() {
		$datos = $this->un_plazo_abierto();
		$this->en_files( array( 'q1' => $this->un_pdf() ) );
		$this->almacen_roto();

		$this->assertNull( $this->presentar( $datos ) );
		$this->assertStringContainsString( 'no se ha presentado', $this->el_rechazo() );
		$this->assertNull(
			Applications::find( $datos['procedure'], 'C0001' ),
			'La solicitud recién creada se deshace entera: fail closed (ADR-0028).'
		);
	}

	/**
	 * Y una solicitud que ya existía se queda exactamente como estaba.
	 *
	 * Aquí **no** se borra: lo que había presentado ese centro no se pierde
	 * porque un documento nuevo no se pueda guardar.
	 */
	public function test_a_failed_document_does_not_destroy_an_existing_application() {
		$datos = $this->un_plazo_abierto();
		$this->acting_as( $datos['head'] );
		$solicitud = $this->application( $datos['procedure'], $datos['head'], 'C0001' );
		ApplicationFiles::store_all( $solicitud, array( 'q1' => $this->un_pdf( 'buena.pdf' ) ) );
		$antes = ApplicationFiles::descriptors( $solicitud );

		$this->en_files( array( 'q1' => $this->un_pdf( 'segunda.pdf' ) ) );
		$this->almacen_roto();

		$this->assertNull( $this->presentar( $datos ) );
		$this->assertStringContainsString( 'no se ha presentado', $this->el_rechazo() );
		$this->assertSame( $solicitud, Applications::find( $datos['procedure'], 'C0001' ), 'Sigue estando.' );
		$this->assertSame( $antes, ApplicationFiles::descriptors( $solicitud ), 'Y con su documento de antes.' );
	}

	// ─── la tabla y el CSV ─────────────────────────────────────────────────

	/**
	 * El CSV lleva el nombre del documento y ninguna ruta ni dirección.
	 */
	public function test_the_csv_carries_the_name_and_never_a_path() {
		$d     = $this->un_documento_guardado();
		$filas = Applications::rows( $d['procedure'] );

		$this->assertCount( 1, $filas );
		$this->assertSame( 'acta-del-claustro-CEIP-Ejemplo.pdf', $filas[0]['q1'] );

		$csv = Applications::csv( $d['procedure'] );
		$this->assertStringContainsString( 'acta-del-claustro-CEIP-Ejemplo.pdf', $csv );
		foreach ( array( 'prc-private', 'wp-content/uploads', 'http', $d['file'], ABSPATH ) as $prohibido ) {
			$this->assertStringNotContainsString( $prohibido, $csv );
		}
	}

	/**
	 * Y la fila lleva aparte lo que la pantalla necesita para el enlace.
	 */
	public function test_the_row_carries_the_documents_for_the_screen_only() {
		$d     = $this->un_documento_guardado();
		$filas = Applications::rows( $d['procedure'] );

		$this->assertArrayHasKey( Applications::KEY_FILES, $filas[0] );
		$this->assertSame( $d['file'], $filas[0][ Applications::KEY_FILES ]['q1']['id'] );
		$this->assertSame( $d['application'], $filas[0][ Applications::KEY_FILES ]['q1']['application'] );
		$this->assertArrayNotHasKey( 'stored', $filas[0][ Applications::KEY_FILES ]['q1'] );
		$this->assertArrayNotHasKey(
			Applications::KEY_FILES,
			Applications::columns( Applications::questions( $d['procedure'] ) ),
			'No es una columna, así que no llega al CSV.'
		);
	}

	// ─── y lo público sigue público ────────────────────────────────────────

	/**
	 * Una imagen normal del sitio sigue siendo un adjunto con su URL pública.
	 *
	 * Es la otra mitad de la ADR-0028: la privacidad la decide quién creó el
	 * fichero y para qué, no volver privada la biblioteca entera.
	 */
	public function test_a_public_image_is_still_a_normal_attachment() {
		$d = $this->un_documento_guardado();

		$imagen = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg', $d['procedure'] );

		$this->assertSame( 'attachment', get_post_type( $imagen ) );
		$this->assertNotFalse( wp_get_attachment_url( $imagen ), 'La imagen pública tiene su URL, como siempre.' );
		$this->assertTrue( wp_attachment_is_image( $imagen ) );
		$this->assertContains(
			$imagen,
			get_posts(
				array(
					'post_type'   => 'attachment',
					'post_status' => 'any',
					'numberposts' => -1,
					'fields'      => 'ids',
				)
			),
			'Y sigue en la biblioteca de medios.'
		);
	}

	/**
	 * La solicitud sigue sin abrir ninguna puerta, tampoco con ficheros.
	 */
	public function test_the_files_meta_is_as_closed_as_the_rest() {
		$this->app();
		$metas = get_registered_meta_keys( 'post', ApplicationPostType::POST_TYPE );

		$this->assertArrayHasKey( ApplicationMetaKeys::FILES, $metas );
		$this->assertFalse( $metas[ ApplicationMetaKeys::FILES ]['show_in_rest'] );
		$this->assertFalse(
			call_user_func( $metas[ ApplicationMetaKeys::FILES ]['auth_callback'] ),
			'Ninguna vía escribe esta meta desde fuera del aplicativo (ADR-0018).'
		);
	}
}
