<?php
/**
 * Tests for the procedure workshop: its five panels and every mutation.
 *
 * @package Prc
 */

use Prc\Access\ProcedureAccess;
use Prc\Domain\ProcedureQuestions;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ProcedurePostType;
use Prc\PublicFront\ProcedureEditor;
use Prc\PublicFront\View\ProcedureEditorView;
use Prc\Taxonomy\ProcedureTaxonomies;

/**
 * El taller de un procedimiento.
 *
 * Hoy dar de alta una convocatoria es un formulario de más de cien campos y
 * gestionar sus solicitudes, unas vistas filtradas por la URL. Aquí son cinco
 * pestañas, todo por POST con su nonce y con `ProcedureAccess` comprobado,
 * así que lo que se prueba no es solo el aviso —eso lo dice cualquiera— sino
 * el estado de la base de datos DESPUÉS de intentarlo.
 */
class Test_Procedure_Editor extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Con el aplicativo, sus páginas, sin catálogo y sin rechazos heredados.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
		$this->pages();
		remove_all_filters( 'prc_centres' );
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
			ProcedureEditor::class
		)();
	}

	/**
	 * Mandar una operación del taller y devolver por dónde salió.
	 *
	 * @param int                  $uid          Who submits.
	 * @param string               $op           Operation.
	 * @param int                  $procedure_id Procedure, 0 to create one.
	 * @param int                  $row          Application, for the review.
	 * @param array<string, mixed> $extra        Extra fields.
	 * @param string|null          $nonce        Null for the good one, a string to forge it, '' for none.
	 * @return string|null
	 */
	private function submit( int $uid, string $op, int $procedure_id, int $row = 0, array $extra = array(), ?string $nonce = null ): ?string {
		$this->acting_as( $uid );
		$campos = array_merge(
			array(
				ProcedureEditor::FIELD_DO        => $op,
				ProcedureEditor::FIELD_PROCEDURE => (string) $procedure_id,
				ProcedureEditor::FIELD_ROW       => (string) $row,
			),
			$extra
		);
		if ( null === $nonce ) {
			$this->post( $campos, ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op, $row ) );
		} else {
			$campos[ ProcedureEditor::nonce_name( $op, $row ) ] = $nonce;
			$this->post( $campos );
		}
		return $this->exit_url( array( ProcedureEditor::class, 'handle' ) );
	}

	/**
	 * Los campos del panel «Datos» que pasan la validación.
	 *
	 * @param int|int[]            $area    Term ID(s) of prc_area.
	 * @param int                  $curso   Term ID of prc_course.
	 * @param array<string, mixed> $cambios What to override.
	 * @return array<string, mixed>
	 */
	private function datos( $area, int $curso, array $cambios = array() ): array {
		return array_merge(
			array(
				ProcedureEditor::FIELD_TITLE       => 'Red de prueba',
				ProcedureEditor::FIELD_DESCRIPTION => '<p>Una red de centros.</p>',
				ProcedureEditor::FIELD_AREA        => array_map( 'strval', (array) $area ),
				ProcedureEditor::FIELD_COURSE      => (string) $curso,
				ProcedureMetaKeys::OPENS_AT        => '2026-10-01',
				ProcedureMetaKeys::CLOSES_AT       => '2026-10-15',
				ProcedureMetaKeys::CONTACT_EMAILS  => array( 'ambito@example.org' ),
				ProcedureMetaKeys::OWNERSHIP       => array( ProcedureMetaKeys::OWNERSHIP_PUBLIC ),
			),
			$cambios
		);
	}

	/**
	 * El modelo del taller abierto por una pestaña.
	 *
	 * @param int    $procedure_id Procedure ID.
	 * @param string $panel        Panel key; empty for the default one.
	 * @return array<string, mixed>
	 */
	private function modelo( int $procedure_id, string $panel = '' ): array {
		$_GET[ ProcedureEditor::ARG_PROCEDURE ] = (string) $procedure_id;
		if ( '' !== $panel ) {
			$_GET[ ProcedureEditor::ARG_PANEL ] = $panel;
		}
		return ProcedureEditor::model();
	}

	/**
	 * Un ámbito, quien lo gestiona y un procedimiento publicado suyo.
	 *
	 * @param array<string, mixed> $meta Meta of the procedure.
	 * @param array<string, mixed> $args Post fields of the procedure.
	 * @return array{0:int, 1:int, 2:int} Area, manager, procedure.
	 */
	private function mio( array $meta = array(), array $args = array() ): array {
		$area = $this->area( 'Área de prueba' );
		$uid  = $this->manager( array( $area ) );
		return array( $area, $uid, $this->procedure( $uid, array( $area ), $meta, array_merge( array( 'post_title' => 'Red de prueba' ), $args ) ) );
	}

	// ─── el modelo ─────────────────────────────────────────────────────────

	/**
	 * Sin sesión, sin procedimiento o con uno de otro ámbito, no se abre el taller.
	 */
	public function test_the_workshop_does_not_open_without_permission() {
		$mia  = $this->area( 'Área de prueba' );
		$otra = $this->area( 'Otro ámbito' );
		$uid  = $this->manager( array( $mia ) );

		$this->acting_as( 0 );
		$this->assertStringContainsString( 'Debe iniciar sesión', ProcedureEditor::model()['aviso'] );

		// Sin `?procedimiento=` esto no es «no hay taller»: es la pantalla de crear uno.
		$this->acting_as( $uid );
		$this->assertTrue( ProcedureEditor::model()['nuevo'] );

		// Sin ámbito en el perfil, ni crear.
		$this->acting_as( $this->manager() );
		$m = ProcedureEditor::model();
		$this->assertFalse( $m['nuevo'] );
		$this->assertStringContainsString( 'no puede crear procedimientos', $m['aviso'] );

		$this->acting_as( $uid );
		$pagina = (int) self::factory()->post->create( array( 'post_type' => 'page' ) );
		$this->assertStringContainsString( 'ya no existe', $this->modelo( $pagina )['aviso'] );

		$ajeno = $this->procedure( $this->administrator(), array( $otra ) );
		$m     = $this->modelo( $ajeno );
		$this->assertStringContainsString( 'de otro ámbito', $m['aviso'] );
		$this->assertSame( 'error', $m['aviso_tipo'] );
		$this->assertSame( array(), $m['panels'], 'ni una pestaña de lo que no se abre' );
	}

	/**
	 * Las cinco pestañas, la que se pide en la URL y sus recuentos.
	 */
	public function test_the_five_panels_and_the_one_asked_for() {
		list( , $uid, $procedimiento ) = $this->mio(
			array(
				ProcedureMetaKeys::QUESTIONS => array(
					array(
						'label' => 'Modalidad',
						'type'  => ProcedureQuestions::TYPE_TEXT,
					),
				),
			)
		);
		$this->application( $procedimiento, $this->school_head( 'C0001' ) );
		$this->acting_as( $uid );

		$m = $this->modelo( $procedimiento );
		$this->assertSame(
			array(
				ProcedureEditor::PANEL_DATA,
				ProcedureEditor::PANEL_QUESTIONS,
				ProcedureEditor::PANEL_LINKS,
				ProcedureEditor::PANEL_APPLICATIONS,
				ProcedureEditor::PANEL_PUBLISH,
			),
			array_keys( $m['panels'] )
		);
		$this->assertSame( ProcedureEditor::PANEL_DATA, $m['panel'], 'por defecto, los datos' );
		$this->assertSame( 1, $m['panels'][ ProcedureEditor::PANEL_QUESTIONS ]['count'] );
		$this->assertSame( 1, $m['panels'][ ProcedureEditor::PANEL_APPLICATIONS ]['count'] );
		$this->assertNull( $m['panels'][ ProcedureEditor::PANEL_LINKS ]['count'] );
		$this->assertSame( $procedimiento, $m['procedure_id'] );
		$this->assertSame( (string) get_permalink( $procedimiento ), $m['view_url'] );
		$this->assertTrue( $m['can_edit'] );
		$this->assertTrue( $m['can_review'] );
		$this->assertFalse( $m['can_publish'], 'ya está publicado' );
		$this->assertTrue( $m['can_archive'] );
		$this->assertSame( 'Área de prueba', $m['area_names'] );
		$this->assertSame( array(), $m['applications'], 'la tabla solo se carga en su pestaña' );

		$m = $this->modelo( $procedimiento, ProcedureEditor::PANEL_APPLICATIONS );
		$this->assertSame( ProcedureEditor::PANEL_APPLICATIONS, $m['panel'] );
		$this->assertCount( 1, $m['applications'] );
		$this->assertSame( 'Modalidad', $m['app_cols']['q1'] );

		$this->assertSame( ProcedureEditor::PANEL_DATA, $this->modelo( $procedimiento, 'inventado' )['panel'] );
	}

	/**
	 * Las direcciones del taller y de sus pestañas, y un nonce por acción y fila.
	 */
	public function test_the_urls_and_the_nonces() {
		$this->assertStringNotContainsString( 'procedimiento=', ProcedureEditor::url( 0 ) );
		$this->assertNotSame( '', ProcedureEditor::url( 0 ), 'la pantalla de crear' );

		$url = ProcedureEditor::url( 12, ProcedureEditor::PANEL_LINKS );
		$this->assertStringContainsString( 'procedimiento=12', $url );
		$this->assertStringContainsString( 'panel=' . ProcedureEditor::PANEL_LINKS, $url );

		$this->assertNotSame( ProcedureEditor::nonce_name( 'review', 3 ), ProcedureEditor::nonce_name( 'review', 4 ) );
		$this->assertNotSame( ProcedureEditor::nonce_action( 'publish' ), ProcedureEditor::nonce_action( 'archive' ) );
	}

	// ─── crear ─────────────────────────────────────────────────────────────

	/**
	 * Crear un procedimiento: nace en borrador, con su ámbito, y abre su taller.
	 */
	public function test_the_form_creates_the_procedure_as_a_draft() {
		$area  = $this->area( 'Área de prueba' );
		$curso = $this->course();
		$uid   = $this->manager( array( $area ) );

		$url = $this->submit( $uid, ProcedureEditor::PANEL_DATA, 0, 0, $this->datos( $area, $curso ) );

		// Un borrador no recibe `post_name` hasta que se publica: se busca por título.
		$creados = get_posts(
			array(
				'post_type'   => ProcedurePostType::POST_TYPE,
				'title'       => 'Red de prueba',
				'post_status' => 'any',
				'numberposts' => -1,
			)
		);
		$this->assertCount( 1, $creados );
		$id = (int) $creados[0]->ID;
		$this->assertSame( 'draft', $creados[0]->post_status, 'en borrador: todavía no tiene preguntas ni enlaces' );
		$this->assertSame( $uid, (int) $creados[0]->post_author );
		$this->assertSame( array( $area ), ProcedureAccess::post_areas( $id ) );
		$this->assertSame( array( $curso ), wp_get_post_terms( $id, ProcedureTaxonomies::COURSE, array( 'fields' => 'ids' ) ) );
		$this->assertSame( '2026-10-01', get_post_meta( $id, ProcedureMetaKeys::OPENS_AT, true ) );
		$this->assertSame( array( ProcedureMetaKeys::OWNERSHIP_PUBLIC ), get_post_meta( $id, ProcedureMetaKeys::OWNERSHIP, true ) );

		$this->assertSame( (string) $id, $this->query_arg( (string) $url, ProcedureEditor::ARG_PROCEDURE ), 'y abre su taller' );
		$this->assertSame( 'creado', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
	}

	/**
	 * Crear pide la capacidad y uno de los ámbitos propios: el desplegable no
	 * es la forma de regalarle el procedimiento a otro ámbito.
	 */
	public function test_creating_needs_the_capability_and_one_of_my_areas() {
		$mia   = $this->area( 'Área de prueba' );
		$otra  = $this->area( 'Otro ámbito' );
		$curso = $this->course();
		$uid   = $this->manager( array( $mia ) );

		$this->assertTrue( ProcedureEditor::may_set_area( $uid, array( $mia ) ) );
		$this->assertFalse( ProcedureEditor::may_set_area( $uid, array( $otra ) ) );
		$this->assertFalse( ProcedureEditor::may_set_area( $uid, array( $mia, $otra ) ), 'se comprueban todos, no el primero' );
		$this->assertFalse( ProcedureEditor::may_set_area( $uid, array() ) );
		$this->assertTrue( ProcedureEditor::may_set_area( $this->administrator(), array( $mia, $otra ) ) );

		$this->assertNull( $this->submit( $uid, ProcedureEditor::PANEL_DATA, 0, 0, $this->datos( $otra, $curso ) ) );
		$this->assertNull( $this->submit( $this->school_head( 'C0001' ), ProcedureEditor::PANEL_DATA, 0, 0, $this->datos( $mia, $curso ) ) );

		$this->assertSame(
			array(),
			get_posts(
				array(
					'post_type'   => ProcedurePostType::POST_TYPE,
					'post_status' => 'any',
					'numberposts' => -1,
				)
			),
			'no se creó nada'
		);

		// Lo tecleado vuelve, con el motivo.
		$this->acting_as( $uid );
		$_GET = array();
		$m    = ProcedureEditor::model();
		$this->assertTrue( $m['nuevo'] );
		$this->assertSame( 'error', $m['flash']['tipo'] );
		$this->assertStringContainsString( 'ámbito', $m['flash']['texto'] );
		$this->assertSame( 'Red de prueba', $m['values'][ ProcedureEditor::FIELD_TITLE ] );
	}

	// ─── Datos ─────────────────────────────────────────────────────────────

	/**
	 * El panel «Datos» guarda el título, la descripción, la clasificación y las metas.
	 */
	public function test_the_data_panel_saves_the_procedure() {
		list( $area, $uid, $procedimiento ) = $this->mio();
		$curso                              = $this->course( '2027-2028' );

		$url = $this->submit(
			$uid,
			ProcedureEditor::PANEL_DATA,
			$procedimiento,
			0,
			$this->datos(
				$area,
				$curso,
				array(
					ProcedureEditor::FIELD_TITLE       => 'Red de prueba 2027',
					ProcedureMetaKeys::AMEND_OPENS_AT  => '2026-10-20',
					ProcedureMetaKeys::AMEND_CLOSES_AT => '2026-10-25',
					ProcedureMetaKeys::CONTACT_EMAILS  => array( 'Ambito@Example.org', 'Segundo@Example.org', '' ),
					ProcedureMetaKeys::REQUIRES_COORDINATOR => '1',
					ProcedureMetaKeys::COMMITMENTS     => 'El centro se compromete.',
					ProcedureMetaKeys::OWNERSHIP       => array( 'public', 'private', 'inventada' ),
				)
			)
		);

		$this->assertSame( ProcedureEditor::PANEL_DATA, $this->query_arg( (string) $url, ProcedureEditor::ARG_PANEL ) );
		$this->assertSame( 'datos', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
		$this->assertSame( 'Red de prueba 2027', get_the_title( $procedimiento ) );
		$this->assertSame( '<p>Una red de centros.</p>', get_post_field( 'post_content', $procedimiento ) );
		$this->assertSame( array( $curso ), wp_get_post_terms( $procedimiento, ProcedureTaxonomies::COURSE, array( 'fields' => 'ids' ) ) );
		$this->assertSame( '2026-10-15', get_post_meta( $procedimiento, ProcedureMetaKeys::CLOSES_AT, true ) );
		$this->assertSame( '2026-10-25', get_post_meta( $procedimiento, ProcedureMetaKeys::AMEND_CLOSES_AT, true ) );
		$this->assertSame(
			array( 'ambito@example.org', 'segundo@example.org' ),
			get_post_meta( $procedimiento, ProcedureMetaKeys::CONTACT_EMAILS, true ),
			'tres campos, dos escritos'
		);
		$this->assertTrue( (bool) get_post_meta( $procedimiento, ProcedureMetaKeys::REQUIRES_COORDINATOR, true ) );
		$this->assertSame( 'El centro se compromete.', get_post_meta( $procedimiento, ProcedureMetaKeys::COMMITMENTS, true ) );
		$this->assertSame( array( 'public', 'private' ), get_post_meta( $procedimiento, ProcedureMetaKeys::OWNERSHIP, true ), 'solo la lista cerrada' );
	}

	/**
	 * Un procedimiento se convoca desde varios ámbitos, y se vuelven a leer todos.
	 */
	public function test_the_data_panel_saves_several_areas() {
		$mia   = $this->area( 'Área de prueba' );
		$otra  = $this->area( 'Otro ámbito', $mia );
		$curso = $this->course( '2027-2028' );
		$uid   = $this->manager( array( $mia ) );
		$id    = $this->procedure( $uid, array( $mia ), array(), array( 'post_title' => 'Red de prueba' ) );

		$this->assertNotNull( $this->submit( $uid, ProcedureEditor::PANEL_DATA, $id, 0, $this->datos( array( $mia, $otra ), $curso ) ) );
		$this->assertSame( array( $mia, $otra ), ProcedureAccess::post_areas( $id ) );

		$this->acting_as( $uid );
		$this->assertSame( array( $mia, $otra ), $this->modelo( $id )['values'][ ProcedureEditor::FIELD_AREA ], 'y el taller los repinta' );

		// Y se quitan: guardar uno solo deja uno solo, no dos.
		$this->assertNotNull( $this->submit( $uid, ProcedureEditor::PANEL_DATA, $id, 0, $this->datos( $otra, $curso ) ) );
		$this->assertSame( array( $otra ), ProcedureAccess::post_areas( $id ) );
	}

	/** Editing one branch of a shared procedure preserves the foreign branch. */
	public function test_shared_procedure_keeps_foreign_scope() {
		$mine    = $this->area( 'Ámbito 1' );
		$foreign = $this->area( 'Ámbito 2' );
		$course  = $this->course( '2027-2028' );
		$editor  = (int) self::factory()->user->create( array( 'role' => 'editor' ) );
		update_user_meta( $editor, ProcedureAccess::USER_AREA_META, array( $mine ) );
		$post = $this->procedure( $editor, array( $mine, $foreign ) );
		$this->assertNotNull( $this->submit( $editor, ProcedureEditor::PANEL_DATA, $post, 0, $this->datos( array( $mine ), $course ) ) );
		$this->assertEqualsCanonicalizing( array( $mine, $foreign ), ProcedureAccess::post_areas( $post ) );
	}

	/**
	 * La pantalla de alta pinta lo que el original pinta, y nada de lo que
	 * el original esconde.
	 *
	 * Las dos cosas importan igual: el calco es el que la gente reconoce
	 * —bandas de sección, quién solicita, árbol de ámbitos, tres correos y paleta—
	 * y los dos campos que allí van ocultos o forzados (el identificador
	 * interno y el estado) aquí **no existen**: el primero es el `ID` del post
	 * y el segundo lo derivan las fechas (ADR-0013).
	 */
	public function test_the_create_screen_is_the_express_form_without_its_hidden_fields() {
		$area  = $this->area( 'Área de prueba' );
		$curso = $this->course();
		$uid   = $this->manager( array( $area ) );
		$this->acting_as( $uid );

		$_GET = array();
		$html = ProcedureEditorView::html( ProcedureEditor::model() );

		// El subtítulo y las cuatro bandas son los literales del sistema que se
		// sustituye: quien da de alta pidió esta pantalla igual que aquella. Las
		// mayúsculas de la banda las pone el CSS: escritas, un lector de pantalla
		// las deletrea.
		$this->assertStringContainsString( '<strong>Formato Express</strong>', $html );
		foreach ( array( 'Ajustes generales', 'Promociona', 'Más información', 'Aspectos de diseño' ) as $banda ) {
			$this->assertStringContainsString( '<legend class="prc-seccion__titulo">' . $banda . '</legend>', $html );
		}
		$this->assertSame( 4, substr_count( $html, 'class="prc-seccion__titulo"' ), 'cuatro secciones y ni una más' );

		// Los cuatro controles de «Ajustes generales».
		$this->assertStringContainsString( 'name="' . ProcedureEditor::FIELD_TITLE . '"', $html );
		$this->assertStringContainsString( 'maxlength="120"', $html );
		$this->assertStringContainsString( 'name="' . ProcedureEditor::FIELD_COURSE . '"', $html );
		$this->assertStringContainsString( 'name="' . ProcedureMetaKeys::AUDIENCE . '"', $html, 'quién solicita' );
		// El rótulo dice a quién se dirige el procedimiento, que no es lo que
		// significaba «Tipología» en el sistema que se sustituye.
		$this->assertStringContainsString( 'for="prc-audience">Quién solicita ', $html );
		$this->assertStringNotContainsString( 'Tipología', $html );
		$this->assertStringContainsString( 'name="' . ProcedureMetaKeys::OWNERSHIP . '[]"', $html );
		foreach ( ProcedureMetaKeys::audiences() as $rotulo ) {
			$this->assertStringContainsString( esc_html( $rotulo ), $html );
		}

		// El ámbito, como árbol de casillas y no como desplegable.
		$this->assertStringContainsString( 'class="prc-arbol"', $html );
		$this->assertStringContainsString( 'name="' . ProcedureEditor::FIELD_AREA . '[]" value="' . $area . '"', $html );
		$this->assertStringNotContainsString( '<select class="prc-control" id="prc-area"', $html );

		// Los tres correos y las diez muestras de color.
		$this->assertSame( ProcedureMetaKeys::CONTACT_EMAILS_MAX, substr_count( $html, 'name="' . ProcedureMetaKeys::CONTACT_EMAILS . '[]"' ) );
		$this->assertSame( count( ProcedureMetaKeys::header_colors() ), substr_count( $html, 'name="' . ProcedureMetaKeys::HEADER_COLOR . '"' ) );
		$this->assertStringContainsString( 'Crear el procedimiento', $html );

		// Y lo que no se pinta: ni identificador interno, ni estado, ni los
		// plazos y los compromisos, que son del taller.
		$this->assertStringNotContainsString( 'post-id', $html );
		$this->assertStringNotContainsString( 'name="' . ProcedureMetaKeys::OPENS_AT . '"', $html );
		$this->assertStringNotContainsString( 'name="' . ProcedureMetaKeys::COMMITMENTS . '"', $html );
		$this->assertStringNotContainsString( '>Estado<', $html );

		// El alta llega hecha a medias: un solo ámbito viene marcado, y el
		// correo de contacto, con el de quien entra.
		$this->assertMatchesRegularExpression( '/value="' . $area . '"\s+checked/', $html );
		$this->assertStringContainsString( esc_attr( (string) get_userdata( $uid )->user_email ), $html );

		// Y lo que se teclee se guarda: es el mismo envío que el del taller.
		$this->assertNotNull(
			$this->submit(
				$uid,
				ProcedureEditor::PANEL_DATA,
				0,
				0,
				$this->datos(
					$area,
					$curso,
					array(
						ProcedureMetaKeys::AUDIENCE       => ProcedureMetaKeys::AUDIENCE_TEACHERS,
						ProcedureMetaKeys::CONTACT_EMAILS => array( 'uno@example.org', '', 'tres@example.org' ),
						ProcedureMetaKeys::HEADER_COLOR   => 'granate',
					)
				)
			)
		);
		$creados = get_posts(
			array(
				'post_type'   => ProcedurePostType::POST_TYPE,
				'post_status' => 'any',
				'numberposts' => -1,
				'fields'      => 'ids',
			)
		);
		$this->assertCount( 1, $creados );
		$id = (int) $creados[0];
		$this->assertSame( ProcedureMetaKeys::AUDIENCE_TEACHERS, get_post_meta( $id, ProcedureMetaKeys::AUDIENCE, true ) );
		$this->assertSame( array( 'uno@example.org', 'tres@example.org' ), get_post_meta( $id, ProcedureMetaKeys::CONTACT_EMAILS, true ) );
		$this->assertSame( 'granate', get_post_meta( $id, ProcedureMetaKeys::HEADER_COLOR, true ) );
	}

	/**
	 * El árbol de ámbitos se pliega como está archivado, y una rama con algo
	 * marcado nace abierta.
	 *
	 * El plegado es una casilla **sin `name`**: no viaja en el envío, y por eso
	 * el árbol funciona sin una línea de JavaScript.
	 */
	public function test_the_area_tree_nests_and_opens_where_something_is_ticked() {
		$madre = $this->area( 'Ámbito raíz' );
		$hija  = $this->area( 'Ámbito hijo', $madre );
		$sola  = $this->area( 'Ámbito suelto' );
		$uid   = $this->manager( array( $madre ) );
		$id    = $this->procedure( $uid, array( $hija ), array(), array( 'post_title' => 'Red de prueba' ) );
		$this->acting_as( $uid );

		$m = $this->modelo( $id );
		$this->assertSame(
			array( $madre ),
			array_column( (array) $m['terms']['area'], 'id' ),
			'las raíces, y la hija colgando de la suya'
		);
		$this->assertSame( array( $hija ), array_column( (array) $m['terms']['area'][0]['children'], 'id' ) );

		$html = ProcedureEditorView::html( $m );
		$this->assertMatchesRegularExpression( '/id="prc-rama-' . $madre . '"\s+checked/', $html, 'la rama con la hija marcada nace abierta' );
		$this->assertStringNotContainsString( 'id="prc-rama-' . $sola . '"', $html, 'una hoja no tiene triángulo' );
		$this->assertMatchesRegularExpression( '/value="' . $hija . '"\s+checked/', $html );
		$this->assertStringNotContainsString( 'name="prc-rama', $html, 'el plegado no se envía' );
	}

	/**
	 * Basta un ámbito ajeno entre los marcados para tumbar el envío.
	 *
	 * El ámbito es además quién edita y quién revisa las solicitudes, y el
	 * cruce es una intersección (ADR-0025): colar uno ajeno es meter a otro
	 * servicio en el procedimiento sin que se entere.
	 */
	public function test_a_single_foreign_area_refuses_the_data_panel() {
		list( $mia, $uid, $procedimiento ) = $this->mio();
		$otra                              = $this->area( 'Otro ámbito' );
		$curso                             = $this->course( '2027-2028' );

		$this->assertNull( $this->submit( $uid, ProcedureEditor::PANEL_DATA, $procedimiento, 0, $this->datos( array( $mia, $otra ), $curso ) ) );
		$this->assertSame( array( $mia ), ProcedureAccess::post_areas( $procedimiento ), 'no entró ninguno de los dos' );

		$this->acting_as( $uid );
		$this->assertStringContainsString( 'ámbito', $this->modelo( $procedimiento )['flash']['texto'] );

		// Quien gestiona todos los ámbitos sí puede repartirlo.
		$this->assertNotNull( $this->submit( $this->administrator(), ProcedureEditor::PANEL_DATA, $procedimiento, 0, $this->datos( array( $mia, $otra ), $curso ) ) );
		$this->assertSame( array( $mia, $otra ), ProcedureAccess::post_areas( $procedimiento ) );
	}

	/**
	 * El color de cabecera se guarda si es de la paleta, y si no, no se guarda nada.
	 */
	public function test_the_data_panel_only_saves_a_colour_of_the_palette() {
		list( $area, $uid, $procedimiento ) = $this->mio();
		$curso                              = $this->course( '2027-2028' );

		$this->assertNotNull(
			$this->submit( $uid, ProcedureEditor::PANEL_DATA, $procedimiento, 0, $this->datos( $area, $curso, array( ProcedureMetaKeys::HEADER_COLOR => 'granate' ) ) )
		);
		$this->assertSame( 'granate', get_post_meta( $procedimiento, ProcedureMetaKeys::HEADER_COLOR, true ) );

		$this->assertNull(
			$this->submit( $uid, ProcedureEditor::PANEL_DATA, $procedimiento, 0, $this->datos( $area, $curso, array( ProcedureMetaKeys::HEADER_COLOR => '#ff0000' ) ) ),
			'un color de fuera de la lista no se cuela'
		);
		$this->assertSame( 'granate', get_post_meta( $procedimiento, ProcedureMetaKeys::HEADER_COLOR, true ), 'y no pisa el que había' );

		// Sin elegir ninguno, el neutro.
		$this->assertNotNull( $this->submit( $uid, ProcedureEditor::PANEL_DATA, $procedimiento, 0, $this->datos( $area, $curso ) ) );
		$this->assertSame( ProcedureMetaKeys::HEADER_COLOR_NEUTRAL, get_post_meta( $procedimiento, ProcedureMetaKeys::HEADER_COLOR, true ) );
	}

	/**
	 * Un dato mal no borra los otros: no se guarda nada y se repinta lo tecleado.
	 */
	public function test_a_rejected_data_panel_repaints_what_was_typed() {
		list( $area, $uid, $procedimiento ) = $this->mio();

		$this->assertNull(
			$this->submit(
				$uid,
				ProcedureEditor::PANEL_DATA,
				$procedimiento,
				0,
				$this->datos(
					$area,
					$this->course(),
					array(
						ProcedureEditor::FIELD_TITLE      => 'Nombre nuevo',
						ProcedureMetaKeys::AMEND_OPENS_AT => '2026-10-10',
					)
				)
			)
		);

		$this->assertSame( 'Red de prueba', get_the_title( $procedimiento ), 'no se guarda nada a medias' );
		$this->assertSame( '', get_post_meta( $procedimiento, ProcedureMetaKeys::OPENS_AT, true ) );

		$m = $this->modelo( $procedimiento );
		$this->assertSame( 'error', $m['flash']['tipo'] );
		$this->assertStringContainsString( 'subsanación', $m['flash']['texto'] );
		$this->assertSame( 'Nombre nuevo', $m['values'][ ProcedureEditor::FIELD_TITLE ], 'lo tecleado vuelve' );
		$this->assertSame( '2026-10-10', $m['values'][ ProcedureMetaKeys::AMEND_OPENS_AT ] );
	}

	// ─── Preguntas ─────────────────────────────────────────────────────────

	/**
	 * Las preguntas se guardan con su clave, la clave no cambia al reescribir
	 * y una clave borrada no se reutiliza (ADR-0019).
	 */
	public function test_the_questions_panel_keeps_the_keys() {
		list( , $uid, $procedimiento ) = $this->mio();
		$q                             = ProcedureEditor::FIELD_Q;

		$url = $this->submit(
			$uid,
			ProcedureEditor::PANEL_QUESTIONS,
			$procedimiento,
			0,
			array(
				$q . 'key'      => array( '', '' ),
				$q . 'label'    => array( 'Modalidad', '' ),
				$q . 'help'     => array( 'Elija una', '' ),
				$q . 'type'     => array( ProcedureQuestions::TYPE_SINGLE, ProcedureQuestions::TYPE_TEXT ),
				$q . 'choices'  => array( "A\nB\n\nA", '' ),
				$q . 'required' => array( '1' ),
			)
		);

		$this->assertSame( 'preguntas', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
		$guardadas = get_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS, true );
		$this->assertCount( 1, $guardadas, 'la fila en blanco no es una pregunta' );
		$this->assertSame( 'q1', $guardadas[0]['key'] );
		$this->assertSame( 'Modalidad', $guardadas[0]['label'] );
		$this->assertSame( array( 'A', 'B' ), $guardadas[0]['choices'] );
		$this->assertTrue( $guardadas[0]['required'] );

		// Se reescribe el rótulo y se añade otra: la primera conserva su clave.
		$this->submit(
			$uid,
			ProcedureEditor::PANEL_QUESTIONS,
			$procedimiento,
			0,
			array(
				$q . 'key'     => array( 'q1', '' ),
				$q . 'label'   => array( 'Modalidad elegida', 'Tiene huerto' ),
				$q . 'type'    => array( ProcedureQuestions::TYPE_SINGLE, ProcedureQuestions::TYPE_YESNO ),
				$q . 'choices' => array( "A\nB", '' ),
			)
		);
		$guardadas = get_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS, true );
		$this->assertSame( array( 'q1', 'q2' ), wp_list_pluck( $guardadas, 'key' ) );
		$this->assertSame( 'Modalidad elegida', $guardadas[0]['label'] );

		// Se borra la primera y se añade una tercera: q1 no vuelve a usarse.
		$this->submit(
			$uid,
			ProcedureEditor::PANEL_QUESTIONS,
			$procedimiento,
			0,
			array(
				$q . 'key'   => array( 'q1', 'q2', '' ),
				$q . 'label' => array( '', 'Tiene huerto', 'Observaciones' ),
				$q . 'type'  => array( ProcedureQuestions::TYPE_SINGLE, ProcedureQuestions::TYPE_YESNO, ProcedureQuestions::TYPE_TEXT ),
			)
		);
		$this->assertSame( array( 'q2', 'q3' ), wp_list_pluck( get_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS, true ), 'key' ) );
	}

	// ─── Enlaces ───────────────────────────────────────────────────────────

	/**
	 * Los tres enlaces se guardan; con el definitivo el procedimiento está
	 * resuelto; y lo que no es una URL no entra ni borra lo que había.
	 */
	public function test_the_links_panel_saves_urls_and_refuses_junk() {
		list( , $uid, $procedimiento ) = $this->mio();

		$url = $this->submit(
			$uid,
			ProcedureEditor::PANEL_LINKS,
			$procedimiento,
			0,
			array(
				ProcedureMetaKeys::RESOLUTION_URL       => 'https://example.org/resolucion.pdf',
				ProcedureMetaKeys::PROVISIONAL_LIST_URL => 'https://example.org/provisional.pdf',
				ProcedureMetaKeys::FINAL_LIST_URL       => 'https://example.org/definitivo.pdf',
			)
		);

		$this->assertSame( 'enlaces', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
		$this->assertSame( 'https://example.org/resolucion.pdf', get_post_meta( $procedimiento, ProcedureMetaKeys::RESOLUTION_URL, true ) );
		$this->assertSame( 'https://example.org/definitivo.pdf', get_post_meta( $procedimiento, ProcedureMetaKeys::FINAL_LIST_URL, true ) );
		$this->assertSame( ProcedureMetaKeys::STATE_RESOLVED, $this->modelo( $procedimiento )['state'] );

		$this->assertNull(
			$this->submit(
				$uid,
				ProcedureEditor::PANEL_LINKS,
				$procedimiento,
				0,
				array(
					ProcedureMetaKeys::RESOLUTION_URL => 'esto no es un enlace',
					ProcedureMetaKeys::FINAL_LIST_URL => '',
				)
			)
		);
		$this->assertSame( 'https://example.org/resolucion.pdf', get_post_meta( $procedimiento, ProcedureMetaKeys::RESOLUTION_URL, true ), 'no se guarda nada a medias' );
		$this->assertSame( 'https://example.org/definitivo.pdf', get_post_meta( $procedimiento, ProcedureMetaKeys::FINAL_LIST_URL, true ) );
		$m = $this->modelo( $procedimiento, ProcedureEditor::PANEL_LINKS );
		$this->assertStringContainsString( 'enlace a la resolución', $m['flash']['texto'] );
		$this->assertSame( 'esto no es un enlace', $m['values'][ ProcedureMetaKeys::RESOLUTION_URL ], 'lo tecleado vuelve' );
	}

	// ─── Publicación ───────────────────────────────────────────────────────

	/**
	 * Publicar, marcar como histórico y desmarcar: lo último, solo administración.
	 */
	public function test_publish_archive_and_unarchive() {
		list( , $uid, $procedimiento ) = $this->mio( array(), array( 'post_status' => 'draft' ) );
		$this->acting_as( $uid );
		$this->assertTrue( $this->modelo( $procedimiento )['can_publish'] );

		$url = $this->submit( $uid, ProcedureEditor::OP_PUBLISH, $procedimiento );
		$this->assertSame( 'publish', get_post_status( $procedimiento ) );
		$this->assertSame( 'publicado', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
		$this->assertFalse( $this->modelo( $procedimiento )['can_publish'] );

		$url = $this->submit( $uid, ProcedureEditor::OP_ARCHIVE, $procedimiento );
		$this->assertTrue( ProcedureAccess::is_archived( $procedimiento ) );
		$this->assertSame( 'archivado', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
		$m = $this->modelo( $procedimiento );
		$this->assertTrue( $m['archived'] );
		$this->assertFalse( $m['can_edit'], 'histórico cierra la edición a su ámbito' );
		$this->assertFalse( $m['can_review'] );
		$this->assertFalse( $m['can_archive'] );
		$this->assertFalse( $m['can_unarchive'] );
		$this->assertSame( ProcedureMetaKeys::STATE_ARCHIVED, $m['state'] );

		// Su ámbito ya no lo toca: ni los datos ni la marca.
		$this->assertNull( $this->submit( $uid, ProcedureEditor::OP_UNARCHIVE, $procedimiento ) );
		$this->assertTrue( ProcedureAccess::is_archived( $procedimiento ) );
		$this->submit( $uid, ProcedureEditor::PANEL_LINKS, $procedimiento, 0, array( ProcedureMetaKeys::RESOLUTION_URL => 'https://example.org/r.pdf' ) );
		$this->assertSame( '', get_post_meta( $procedimiento, ProcedureMetaKeys::RESOLUTION_URL, true ) );

		$admin = $this->administrator();
		$this->acting_as( $admin );
		$this->assertTrue( $this->modelo( $procedimiento )['can_unarchive'] );
		$url = $this->submit( $admin, ProcedureEditor::OP_UNARCHIVE, $procedimiento );
		$this->assertFalse( ProcedureAccess::is_archived( $procedimiento ) );
		$this->assertSame( 'desarchivado', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
	}

	// ─── Solicitudes ───────────────────────────────────────────────────────

	/**
	 * Admitir, excluir y pedir subsanar desde la tabla, con la nota cuando toca.
	 */
	public function test_the_review_from_the_applications_panel() {
		list( , $uid, $procedimiento ) = $this->mio();
		$solicitud                     = $this->application( $procedimiento, $this->school_head( 'C0001' ) );
		$ajena                         = $this->application( $this->procedure( $uid, array( $this->area( 'Área de prueba' ) ) ), $this->school_head( 'C0002' ) );

		$this->assertNull(
			$this->submit( $uid, ProcedureEditor::OP_REVIEW, $procedimiento, $solicitud, array( ProcedureEditor::FIELD_STATE => ApplicationMetaKeys::REVIEW_AMEND ) )
		);
		$this->assertSame( ApplicationMetaKeys::REVIEW_SUBMITTED, get_post_meta( $solicitud, ApplicationMetaKeys::REVIEW_STATE, true ), 'sin nota no se pide subsanar' );
		$this->assertStringContainsString( 'nota', $this->modelo( $procedimiento )['flash']['texto'] );

		$url = $this->submit(
			$uid,
			ProcedureEditor::OP_REVIEW,
			$procedimiento,
			$solicitud,
			array(
				ProcedureEditor::FIELD_STATE => ApplicationMetaKeys::REVIEW_AMEND,
				ProcedureEditor::FIELD_NOTE  => 'Falta la persona coordinadora.',
			)
		);
		$this->assertSame( ApplicationMetaKeys::REVIEW_AMEND, get_post_meta( $solicitud, ApplicationMetaKeys::REVIEW_STATE, true ) );
		$this->assertSame( 'Falta la persona coordinadora.', get_post_meta( $solicitud, ApplicationMetaKeys::REVIEW_NOTE, true ) );
		$this->assertSame( ProcedureEditor::PANEL_APPLICATIONS, $this->query_arg( (string) $url, ProcedureEditor::ARG_PANEL ) );
		$this->assertSame( 'revisada', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );

		// Una solicitud de otro procedimiento no se toca desde este taller.
		$this->assertNull(
			$this->submit( $uid, ProcedureEditor::OP_REVIEW, $procedimiento, $ajena, array( ProcedureEditor::FIELD_STATE => ApplicationMetaKeys::REVIEW_ADMITTED ) )
		);
		$this->assertSame( ApplicationMetaKeys::REVIEW_SUBMITTED, get_post_meta( $ajena, ApplicationMetaKeys::REVIEW_STATE, true ) );

		// Revisar pide su capacidad: dirección de un centro no revisa.
		$this->assertNull(
			$this->submit( $this->school_head( 'C0001' ), ProcedureEditor::OP_REVIEW, $procedimiento, $solicitud, array( ProcedureEditor::FIELD_STATE => ApplicationMetaKeys::REVIEW_ADMITTED ) )
		);
		$this->assertSame( ApplicationMetaKeys::REVIEW_AMEND, get_post_meta( $solicitud, ApplicationMetaKeys::REVIEW_STATE, true ) );
	}

	// ─── permisos, nonces y bloqueo ────────────────────────────────────────

	/**
	 * Sin permiso sobre el procedimiento no se muta NADA.
	 */
	public function test_without_permission_nothing_is_mutated() {
		list( $area, , $procedimiento ) = $this->mio();
		$ajena                          = $this->manager( array( $this->area( 'Otro ámbito' ) ) );

		$this->submit( $ajena, ProcedureEditor::PANEL_DATA, $procedimiento, 0, $this->datos( $area, $this->course(), array( ProcedureEditor::FIELD_TITLE => 'Secuestrado' ) ) );
		$this->submit( $ajena, ProcedureEditor::PANEL_LINKS, $procedimiento, 0, array( ProcedureMetaKeys::RESOLUTION_URL => 'https://example.org/r.pdf' ) );
		$this->submit( $ajena, ProcedureEditor::PANEL_QUESTIONS, $procedimiento, 0, array( ProcedureEditor::FIELD_Q . 'label' => array( 'Colada' ) ) );
		$this->submit( $ajena, ProcedureEditor::OP_ARCHIVE, $procedimiento );

		$this->assertSame( 'Red de prueba', get_the_title( $procedimiento ) );
		$this->assertSame( '', get_post_meta( $procedimiento, ProcedureMetaKeys::RESOLUTION_URL, true ) );
		$this->assertSame( array(), get_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS, true ), 'la meta es de tipo array: su vacío es array(), no cadena' );
		$this->assertFalse( ProcedureAccess::is_archived( $procedimiento ) );
	}

	/**
	 * Con el nonce mal, la operación ni siquiera se intenta.
	 */
	public function test_a_bad_nonce_mutates_nothing() {
		list( , $uid, $procedimiento ) = $this->mio( array(), array( 'post_status' => 'draft' ) );

		$this->assertNull( $this->submit( $uid, ProcedureEditor::OP_PUBLISH, $procedimiento, 0, array(), 'basura' ) );
		$this->assertNull( $this->submit( $uid, ProcedureEditor::OP_PUBLISH, $procedimiento, 0, array(), '' ) );
		$this->assertSame( 'draft', get_post_status( $procedimiento ) );

		// Y el nonce de otra acción no vale para esta.
		$this->acting_as( $uid );
		$this->post(
			array(
				ProcedureEditor::FIELD_DO        => ProcedureEditor::OP_PUBLISH,
				ProcedureEditor::FIELD_PROCEDURE => (string) $procedimiento,
			),
			ProcedureEditor::nonce_action( ProcedureEditor::OP_ARCHIVE ),
			ProcedureEditor::nonce_name( ProcedureEditor::OP_PUBLISH )
		);
		$this->assertNull( $this->exit_url( array( ProcedureEditor::class, 'handle' ) ) );
		$this->assertSame( 'draft', get_post_status( $procedimiento ) );

		$this->assertNull( $this->submit( $uid, 'formatear', $procedimiento ), 'una operación que no es nuestra se ignora' );
	}

	/**
	 * El bloqueo de edición es el de WordPress (ADR-0008): con otra persona
	 * dentro, el taller se abre en solo lectura y guardar responde 409.
	 */
	public function test_somebody_elses_lock_makes_the_workshop_read_only() {
		list( $area, $uid, $procedimiento ) = $this->mio();
		$otra                               = $this->manager( array( $area ) );

		require_once ABSPATH . 'wp-admin/includes/post.php';
		$this->acting_as( $otra );
		wp_set_post_lock( $procedimiento );

		$this->acting_as( $uid );
		$m = $this->modelo( $procedimiento );
		$this->assertFalse( $m['can_edit'] );
		$this->assertFalse( $m['can_archive'] );
		$this->assertSame( $otra, $m['lock']['owner'] );
		$this->assertNotSame( '', $m['lock']['name'] );
		$this->assertStringContainsString( 'prc-solo-lectura', ProcedureEditorView::html( $m ) );

		$this->expectException( 'WPDieException' );
		$this->submit( $uid, ProcedureEditor::PANEL_LINKS, $procedimiento, 0, array( ProcedureMetaKeys::RESOLUTION_URL => 'https://example.org/r.pdf' ) );
	}

	// ─── el cierre por estado ──────────────────────────────────────────────

	/**
	 * Un procedimiento en el estado que se pida, con todo lo que la validación
	 * necesita: ámbito, curso, titularidad y compromisos.
	 *
	 * @param string $estado One of ProcedureMetaKeys::states().
	 * @return array{0:int, 1:int, 2:int, 3:int} Ámbito, quien gestiona, procedimiento, curso.
	 */
	private function en_estado( string $estado ): array {
		$plazos = array(
			ProcedureMetaKeys::STATE_OPEN      => array( '-5 days', '+5 days', '', '' ),
			ProcedureMetaKeys::STATE_CLOSED    => array( '-20 days', '-10 days', '', '' ),
			ProcedureMetaKeys::STATE_AMENDMENT => array( '-20 days', '-10 days', '-2 days', '+2 days' ),
			ProcedureMetaKeys::STATE_RESOLVED  => array( '-20 days', '-10 days', '', '' ),
		);
		$claves = array(
			ProcedureMetaKeys::OPENS_AT,
			ProcedureMetaKeys::CLOSES_AT,
			ProcedureMetaKeys::AMEND_OPENS_AT,
			ProcedureMetaKeys::AMEND_CLOSES_AT,
		);
		$meta   = array(
			ProcedureMetaKeys::OWNERSHIP      => array( ProcedureMetaKeys::OWNERSHIP_PUBLIC ),
			ProcedureMetaKeys::COMMITMENTS    => 'Los compromisos de siempre.',
			ProcedureMetaKeys::CONTACT_EMAILS => array( 'ambito@example.org' ),
		);
		foreach ( $claves as $i => $clave ) {
			$dia            = (string) $plazos[ $estado ][ $i ];
			$meta[ $clave ] = '' === $dia ? '' : gmdate( 'Y-m-d', strtotime( $dia ) );
		}
		if ( ProcedureMetaKeys::STATE_RESOLVED === $estado ) {
			$meta[ ProcedureMetaKeys::FINAL_LIST_URL ] = 'https://example.org/definitivo.pdf';
		}

		list( $area, $uid, $procedimiento ) = $this->mio( $meta );
		$curso                              = $this->course();
		wp_set_object_terms( $procedimiento, array( $curso ), ProcedureTaxonomies::COURSE, false );

		$this->assertSame( $estado, ProcedureState::of_post( $procedimiento ), 'la fixture no está en el estado que dice' );
		return array( $area, $uid, $procedimiento, $curso );
	}

	/**
	 * Cerrado el plazo se cambian las fechas y los enlaces y nada más: el
	 * título, los compromisos y las preguntas se quedan como estaban.
	 */
	public function test_a_closed_procedure_only_saves_dates_and_links() {
		list( $area, $uid, $procedimiento, $curso ) = $this->en_estado( ProcedureMetaKeys::STATE_CLOSED );
		update_post_meta(
			$procedimiento,
			ProcedureMetaKeys::QUESTIONS,
			array(
				array(
					'label' => 'Modalidad',
					'type'  => ProcedureQuestions::TYPE_TEXT,
				),
			)
		);

		// El envío trae de todo, como quien lo arma a mano contra la pantalla
		// de antes: solo tienen que entrar las fechas.
		$url = $this->submit(
			$uid,
			ProcedureEditor::PANEL_DATA,
			$procedimiento,
			0,
			$this->datos(
				$area,
				$this->course( '2099-2100' ),
				array(
					ProcedureEditor::FIELD_TITLE   => 'Nombre secuestrado',
					ProcedureMetaKeys::OPENS_AT    => '2026-01-07',
					ProcedureMetaKeys::CLOSES_AT   => '2026-01-20',
					ProcedureMetaKeys::COMMITMENTS => 'Otros compromisos.',
				)
			)
		);

		$this->assertSame( 'datos', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
		$this->assertSame( '2026-01-07', get_post_meta( $procedimiento, ProcedureMetaKeys::OPENS_AT, true ), 'las fechas sí' );
		$this->assertSame( '2026-01-20', get_post_meta( $procedimiento, ProcedureMetaKeys::CLOSES_AT, true ) );
		$this->assertSame( 'Red de prueba', get_the_title( $procedimiento ), 'el título no' );
		$this->assertSame( 'Los compromisos de siempre.', get_post_meta( $procedimiento, ProcedureMetaKeys::COMMITMENTS, true ) );
		$this->assertSame( array( $curso ), wp_get_post_terms( $procedimiento, ProcedureTaxonomies::COURSE, array( 'fields' => 'ids' ) ), 'ni el curso' );

		// Los enlaces, también.
		$url = $this->submit( $uid, ProcedureEditor::PANEL_LINKS, $procedimiento, 0, array( ProcedureMetaKeys::RESOLUTION_URL => 'https://example.org/r.pdf' ) );
		$this->assertSame( 'enlaces', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
		$this->assertSame( 'https://example.org/r.pdf', get_post_meta( $procedimiento, ProcedureMetaKeys::RESOLUTION_URL, true ) );

		// Las preguntas, no: borrar una con solicitudes presentadas descoloca
		// su tabla y su CSV.
		$this->assertNull(
			$this->submit(
				$uid,
				ProcedureEditor::PANEL_QUESTIONS,
				$procedimiento,
				0,
				array(
					ProcedureEditor::FIELD_Q . 'key'   => array( 'q1' ),
					ProcedureEditor::FIELD_Q . 'label' => array( '' ),
				)
			)
		);
		$this->assertCount( 1, get_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS, true ), 'la pregunta sigue ahí' );

		$m = $this->modelo( $procedimiento, ProcedureEditor::PANEL_QUESTIONS );
		$this->assertSame( 'error', $m['flash']['tipo'] );
		$this->assertStringContainsString( 'plazo de solicitud está cerrado', $m['flash']['texto'] );
	}

	/**
	 * En subsanación el procedimiento no se toca —ni datos, ni fechas, ni
	 * enlaces, ni preguntas—, pero sus solicitudes se gestionan igual.
	 */
	public function test_an_amendment_freezes_the_procedure_but_not_its_applications() {
		list( $area, $uid, $procedimiento, $curso ) = $this->en_estado( ProcedureMetaKeys::STATE_AMENDMENT );
		$solicitud                                  = $this->application( $procedimiento, $this->school_head( 'C0001' ) );
		$abria                                      = (string) get_post_meta( $procedimiento, ProcedureMetaKeys::OPENS_AT, true );

		$this->assertNull( $this->submit( $uid, ProcedureEditor::PANEL_DATA, $procedimiento, 0, $this->datos( $area, $curso, array( ProcedureEditor::FIELD_TITLE => 'Nombre secuestrado' ) ) ) );
		$this->assertNull( $this->submit( $uid, ProcedureEditor::PANEL_LINKS, $procedimiento, 0, array( ProcedureMetaKeys::RESOLUTION_URL => 'https://example.org/r.pdf' ) ) );
		$this->assertNull( $this->submit( $uid, ProcedureEditor::PANEL_QUESTIONS, $procedimiento, 0, array( ProcedureEditor::FIELD_Q . 'label' => array( 'Colada' ) ) ) );

		$this->assertSame( 'Red de prueba', get_the_title( $procedimiento ) );
		$this->assertSame( $abria, get_post_meta( $procedimiento, ProcedureMetaKeys::OPENS_AT, true ), 'las fechas tampoco' );
		$this->assertSame( '', get_post_meta( $procedimiento, ProcedureMetaKeys::RESOLUTION_URL, true ) );
		$this->assertSame( array(), get_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS, true ) );

		// Lo que sí: revisar. Es otra capacidad y no la toca el estado.
		$url = $this->submit(
			$uid,
			ProcedureEditor::OP_REVIEW,
			$procedimiento,
			$solicitud,
			array( ProcedureEditor::FIELD_STATE => ApplicationMetaKeys::REVIEW_ADMITTED )
		);
		$this->assertSame( 'revisada', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
		$this->assertSame( ApplicationMetaKeys::REVIEW_ADMITTED, get_post_meta( $solicitud, ApplicationMetaKeys::REVIEW_STATE, true ) );

		$m = $this->modelo( $procedimiento );
		$this->assertTrue( $m['can_review'] );
		$this->assertSame( array(), array_filter( (array) $m['groups'] ), 'ni un grupo abierto' );
		$this->assertStringContainsString( 'subsanación está en marcha', ProcedureEditorView::html( $m ) );
		$this->assertStringContainsString( 'prc-solo-lectura', ProcedureEditorView::html( $m ) );
	}

	/**
	 * Resuelto solo se cambian los enlaces: las fechas ya no.
	 */
	public function test_a_resolved_procedure_only_saves_links() {
		list( $area, $uid, $procedimiento, $curso ) = $this->en_estado( ProcedureMetaKeys::STATE_RESOLVED );
		$cerraba                                    = (string) get_post_meta( $procedimiento, ProcedureMetaKeys::CLOSES_AT, true );

		$url = $this->submit(
			$uid,
			ProcedureEditor::PANEL_LINKS,
			$procedimiento,
			0,
			array(
				ProcedureMetaKeys::PROVISIONAL_LIST_URL => 'https://example.org/provisional.pdf',
				ProcedureMetaKeys::FINAL_LIST_URL       => 'https://example.org/definitivo.pdf',
			)
		);
		$this->assertSame( 'enlaces', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
		$this->assertSame( 'https://example.org/provisional.pdf', get_post_meta( $procedimiento, ProcedureMetaKeys::PROVISIONAL_LIST_URL, true ) );

		$this->assertNull(
			$this->submit(
				$uid,
				ProcedureEditor::PANEL_DATA,
				$procedimiento,
				0,
				$this->datos(
					$area,
					$curso,
					array(
						ProcedureMetaKeys::OPENS_AT  => '2026-02-01',
						ProcedureMetaKeys::CLOSES_AT => '2026-02-10',
					)
				)
			)
		);
		$this->assertSame( $cerraba, get_post_meta( $procedimiento, ProcedureMetaKeys::CLOSES_AT, true ) );
		$this->assertStringContainsString( 'resuelto', $this->modelo( $procedimiento )['flash']['texto'] );
	}

	/**
	 * Y la pantalla lo cuenta: el aviso del estado, los bloques que no se
	 * tocan apagados y el panel de preguntas en solo lectura.
	 */
	public function test_the_screen_says_why_and_paints_the_closed_groups() {
		list( , $uid, $procedimiento ) = $this->en_estado( ProcedureMetaKeys::STATE_CLOSED );
		$this->acting_as( $uid );

		$m = $this->modelo( $procedimiento );
		$this->assertSame(
			array(
				ProcedureState::GROUP_DATA      => false,
				ProcedureState::GROUP_DATES     => true,
				ProcedureState::GROUP_LINKS     => true,
				ProcedureState::GROUP_QUESTIONS => false,
			),
			(array) $m['groups']
		);
		$this->assertStringContainsString( 'plazo de solicitud está cerrado', (string) $m['state_notice'] );

		$html = ProcedureEditorView::html( $m );
		$this->assertStringContainsString( 'plazo de solicitud está cerrado', $html );
		$this->assertStringNotContainsString( 'prc-solo-lectura', $html, 'las fechas todavía se guardan' );
		$this->assertSame( 5, substr_count( $html, '<fieldset class="prc-seccion-campos" disabled>' ), 'los cinco bloques que ya no se tocan' );
		$this->assertStringContainsString( '<fieldset class="prc-seccion-campos"><legend class="prc-seccion__titulo">Plazos</legend>', $html, 'los plazos no' );
		// Y cada bloque apagado dice por qué lo está, junto al bloque.
		$this->assertSame( 5, substr_count( $html, 'class="prc-seccion__bloqueo"' ) );

		$this->assertStringContainsString( 'prc-solo-lectura', ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_QUESTIONS ) ), 'las preguntas no se pintan editables' );
		$this->assertStringNotContainsString( 'prc-solo-lectura', ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_LINKS ) ), 'los enlaces sí' );
	}

	// ─── lo que se pinta ───────────────────────────────────────────────────

	/**
	 * Cada pestaña enseña lo suyo: una clave que ya no está en el modelo aquí
	 * es un error fatal, no un hueco de estilo.
	 */
	public function test_every_panel_paints_its_form() {
		list( , $uid, $procedimiento ) = $this->mio(
			array(
				ProcedureMetaKeys::QUESTIONS => array(
					array(
						'label'   => 'Modalidad',
						'type'    => ProcedureQuestions::TYPE_SINGLE,
						'choices' => array( 'A', 'B' ),
					),
				),
			)
		);
		$this->application( $procedimiento, $this->school_head( 'C0001' ), '', array( ApplicationMetaKeys::ANSWERS => array( 'q1' => 'B' ) ) );
		$this->acting_as( $uid );

		$html = ProcedureEditorView::html( $this->modelo( $procedimiento ) );
		foreach ( array( 'Datos', 'Preguntas', 'Enlaces', 'Solicitudes', 'Publicación', 'Ver la ficha pública', 'Guardar los datos' ) as $rotulo ) {
			$this->assertStringContainsString( $rotulo, $html, 'falta ' . $rotulo );
		}
		$this->assertStringContainsString( 'name="' . ProcedureEditor::FIELD_TITLE . '"', $html );
		$this->assertStringContainsString( 'name="' . ProcedureMetaKeys::OPENS_AT . '"', $html );
		$this->assertStringContainsString( 'name="' . ProcedureMetaKeys::OWNERSHIP . '[]"', $html );
		$this->assertStringContainsString( 'value="' . ProcedureEditor::PANEL_DATA . '"', $html, 'la operación viaja en un campo oculto' );
		$this->assertStringNotContainsString( 'prc-solo-lectura', $html );

		$html = ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_QUESTIONS ) );
		$this->assertStringContainsString( 'Modalidad', $html );
		$this->assertStringContainsString( ProcedureEditor::FIELD_Q . 'label[0]', $html );
		$this->assertStringContainsString( ProcedureEditor::FIELD_Q . 'label[1]', $html, 'la fila en blanco del final' );
		$this->assertStringContainsString( 'Pregunta nueva', $html );
		foreach ( ProcedureQuestions::types() as $rotulo ) {
			$this->assertStringContainsString( esc_html( $rotulo ), $html );
		}

		$html = ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_LINKS ) );
		$this->assertStringContainsString( 'name="' . ProcedureMetaKeys::FINAL_LIST_URL . '"', $html );
		$this->assertStringContainsString( 'Guardar los enlaces', $html );

		$html = ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_APPLICATIONS ) );
		$this->assertStringContainsString( 'C0001', $html );
		$this->assertStringContainsString( 'Centro de prueba', $html );
		$this->assertStringContainsString( '<dd>B</dd>', $html, 'la respuesta sale en la revisión' );
		// En pantalla, el día se escribe como en el resto del aplicativo.
		$this->assertStringContainsString( 'data-rotulo="Fecha">' . gmdate( 'd-m-Y' ) . '</td>', $html );
		$this->assertStringContainsString( 'Guardar la revisión', $html );
		$this->assertStringContainsString( 'Exportar a CSV', $html );
		$this->assertStringContainsString( 'value="' . ProcedureEditor::OP_EXPORT . '"', $html );
		$this->assertStringContainsString( 'method="post"', $html );
		foreach ( ApplicationMetaKeys::review_states() as $rotulo ) {
			$this->assertStringContainsString( esc_html( $rotulo ), $html );
		}

		$html = ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_PUBLISH ) );
		$this->assertStringContainsString( 'Marcar como histórico', $html );
		$this->assertStringNotContainsString( 'Publicar el procedimiento', $html, 'ya está publicado' );
		$this->assertStringNotContainsString( 'Solo administración', $html );
	}

	/**
	 * Sin solicitudes se dice; en borrador se ofrece publicar; histórico se
	 * abre en solo lectura y administración ve el recuadro para reabrirlo.
	 */
	public function test_the_empty_the_draft_and_the_archived_screens() {
		list( , $uid, $procedimiento ) = $this->mio( array(), array( 'post_status' => 'draft' ) );
		$this->acting_as( $uid );

		$this->assertStringContainsString( 'Todavía no hay solicitudes', ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_APPLICATIONS ) ) );
		$this->assertStringContainsString( 'Publicar el procedimiento', ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_PUBLISH ) ) );

		$_GET = array();
		$html = ProcedureEditorView::html( ProcedureEditor::model() );
		$this->assertStringContainsString( 'Crear un procedimiento', $html );
		$this->assertStringContainsString( 'Crear el procedimiento', $html );

		update_post_meta( $procedimiento, ProcedureMetaKeys::ARCHIVED, true );
		$html = ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_PUBLISH ) );
		$this->assertStringContainsString( 'prc-solo-lectura', $html );
		$this->assertStringContainsString( 'histórico', $html );
		$this->assertStringNotContainsString( 'Volver a abrir', $html );

		$this->acting_as( $this->administrator() );
		$html = ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_PUBLISH ) );
		$this->assertStringContainsString( 'Solo administración', $html );
		$this->assertStringContainsString( 'Volver a abrir el procedimiento', $html );
		$this->assertStringContainsString( 'value="' . ProcedureEditor::OP_UNARCHIVE . '"', $html );
	}

	/**
	 * El arranque engancha el shortcode y el que atiende los envíos.
	 */
	public function test_register_hooks_the_shortcode_and_the_handler() {
		ProcedureEditor::register();

		$this->assertTrue( shortcode_exists( ProcedureEditor::SHORTCODE ) );
		$this->assertSame( 20, has_action( 'init', array( ProcedureEditor::class, 'handle' ) ) );
	}
}
