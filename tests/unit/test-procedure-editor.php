<?php
/**
 * Tests for the procedure workshop: its five panels and every mutation.
 *
 * @package Prc
 */

use Prc\Access\ProcedureAccess;
use Prc\Domain\ProcedureInput;
use Prc\Domain\ProcedureQuestions;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\Notifications\Mailer;
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
			$this->post( $campos, ProcedureEditor::nonce_action( $op, $row ), ProcedureEditor::nonce_name( $op, $row ) );
		} else {
			$campos[ ProcedureEditor::nonce_name( $op, $row ) ] = $nonce;
			$this->post( $campos );
		}
		return $this->exit_url( array( ProcedureEditor::class, 'handle' ) );
	}

	/**
	 * Un día contado desde hoy, para que el procedimiento siga en plazo
	 * cuando corra el test: con fechas fijas, en cuanto pasa el cierre el
	 * panel «Datos» se bloquea y los tests de guardar dejan de probar nada.
	 *
	 * @param string $relative Offset for strtotime(), e.g. '+10 days'.
	 * @return string Y-m-d.
	 */
	private function dia( string $relative ): string {
		return gmdate( 'Y-m-d', strtotime( $relative ) );
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
				ProcedureMetaKeys::OPENS_AT        => $this->dia( '+10 days' ),
				ProcedureMetaKeys::CLOSES_AT       => $this->dia( '+24 days' ),
				ProcedureMetaKeys::CONTACT_EMAILS  => array( 'ambito@example.org' ),
				ProcedureMetaKeys::OWNERSHIP       => array( ProcedureMetaKeys::OWNERSHIP_PUBLIC ),
				ProcedureMetaKeys::CENTRE_KINDS    => array( ProcedureMetaKeys::KIND_SCHOOL ),
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
	 * Las seis pestañas, la que se pide en la URL y sus recuentos.
	 */
	public function test_the_workshop_panels_and_the_one_asked_for() {
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
				ProcedureEditor::PANEL_PEOPLE,
				ProcedureEditor::PANEL_LINKS,
				ProcedureEditor::PANEL_PUBLISH,
				ProcedureEditor::PANEL_APPLICATIONS,
				ProcedureEditor::PANEL_DELIVERIES,
				ProcedureEditor::PANEL_NOTICES,
				ProcedureEditor::PANEL_OBSERVE,
				ProcedureEditor::PANEL_CODE,
			),
			array_keys( $m['panels'] ),
			'en el orden en que se trabaja: preparar, gestionar y el resto'
		);
		$this->assertSame(
			array( 'Preparar', 'Preparar', 'Preparar', 'Preparar', 'Preparar', 'Gestionar', 'Gestionar', 'Más ajustes', 'Más ajustes', 'Más ajustes' ),
			array_values( wp_list_pluck( $m['panels'], 'group' ) )
		);
		$this->assertSame( 'Acceso de consulta', $m['panels'][ ProcedureEditor::PANEL_OBSERVE ]['label'], 'no se llama como el menú de arriba' );

		$html = ProcedureEditorView::html( $m );
		$this->assertStringContainsString( 'role="group" aria-label="Gestionar"', $html, 'cada grupo se nombra' );
		$this->assertStringContainsString( '<span class="prc-tabs-grupo__rotulo" aria-hidden="true">Más ajustes</span>', $html );
		$this->assertStringContainsString( '<span class="prc-tab-n">1</span>', $html, 'el contador sin la clase de Bootstrap que lo pinta en blanco' );
		$this->assertSame( ProcedureEditor::PANEL_DATA, $m['panel'], 'por defecto, los datos' );
		$this->assertSame( 1, $m['panels'][ ProcedureEditor::PANEL_QUESTIONS ]['count'] );
		$this->assertSame( 1, $m['panels'][ ProcedureEditor::PANEL_APPLICATIONS ]['count'] );
		$this->assertSame( 0, $m['panels'][ ProcedureEditor::PANEL_DELIVERIES ]['count'] );
		$this->assertNull( $m['panels'][ ProcedureEditor::PANEL_LINKS ]['count'] );
		$this->assertSame( $procedimiento, $m['procedure_id'] );
		$this->assertSame( (string) get_permalink( $procedimiento ), $m['view_url'] );
		$this->assertTrue( $m['can_edit'] );
		$this->assertTrue( $m['can_review'] );
		$this->assertFalse( $m['can_publish'], 'ya está publicado' );
		$this->assertFalse( $m['can_archive'], 'vivo no se marca como histórico' );
		$this->assertSame( 'Área de prueba', $m['area_names'] );
		$this->assertSame( array(), $m['applications'], 'la tabla solo se carga en su pestaña' );

		$m = $this->modelo( $procedimiento, ProcedureEditor::PANEL_APPLICATIONS );
		$this->assertSame( ProcedureEditor::PANEL_APPLICATIONS, $m['panel'] );
		$this->assertCount( 1, $m['applications'] );
		$this->assertSame( 'Modalidad', $m['app_cols']['q1'] );

		$this->assertSame( ProcedureEditor::PANEL_DATA, $this->modelo( $procedimiento, 'inventado' )['panel'] );
	}

	/** A manager's forged JavaScript is ignored while CSS is saved. */
	public function test_managers_can_save_css_but_cannot_save_javascript() {
		list( , $uid, $procedure ) = $this->mio();
		$this->submit(
			$uid,
			ProcedureEditor::OP_CUSTOM_CODE,
			$procedure,
			0,
			array(
				'prc_custom_css' => 'body { color: red; }',
				'prc_custom_js'  => 'alert("forged");',
			)
		);
		$this->assertSame( 'body { color: red; }', get_post_meta( $procedure, ProcedureMetaKeys::CUSTOM_CSS, true ) );
		$this->assertSame( '', get_post_meta( $procedure, ProcedureMetaKeys::CUSTOM_JS, true ) );
	}

	/** Managers see CSS only; JavaScript is entirely hidden from their screen. */
	public function test_custom_code_panel_explains_and_limits_javascript_access() {
		list( , $uid, $procedure ) = $this->mio( array( ProcedureMetaKeys::CUSTOM_CSS => 'h1 { color: red; }' ) );
		$this->acting_as( $uid );
		$html = ProcedureEditorView::html( $this->modelo( $procedure, ProcedureEditor::PANEL_CODE ) );
		$this->assertStringContainsString( 'name="prc_custom_css"', $html );
		$this->assertStringNotContainsString( 'name="prc_custom_js"', $html );
		$this->assertStringNotContainsString( 'JavaScript', $html );
		$this->assertStringContainsString( '>Guardar el código</button>', $html, 'guardar y qué' );
	}

	/** Administrators see the executable editor and its yellow public-impact warning. */
	public function test_administrators_see_the_javascript_warning_and_editor() {
		list( , , $procedure ) = $this->mio();
		$this->acting_as( $this->administrator() );
		$html = ProcedureEditorView::html( $this->modelo( $procedure, ProcedureEditor::PANEL_CODE ) );
		$this->assertStringContainsString( 'name="prc_custom_js"', $html );
		$this->assertStringContainsString( 'class="prc-aviso prc-aviso-warning prc-codigo-admin"', $html );
		$this->assertStringContainsString( 'Solo para administración', $html );
	}

	/** The CSS manager gets no JavaScript editor assets or initializer. */
	public function test_code_editor_enqueues_only_the_css_field_for_managers() {
		list( , $uid, $procedure ) = $this->mio();
		update_user_option( $uid, 'syntax_highlighting', 'true', true );
		$this->acting_as( $uid );
		$this->go_to( ProcedureEditor::url( $procedure, ProcedureEditor::PANEL_CODE ) );

		ProcedureEditor::enqueue_code_editor();
		$scripts = wp_scripts();
		$inline  = implode( '', (array) ( $scripts->registered['code-editor']->extra['after'] ?? array() ) );
		$this->assertTrue( $scripts->query( 'code-editor', 'enqueued' ) );
		$this->assertStringContainsString( 'prc-custom-css', $inline );
		$this->assertStringNotContainsString( 'prc-custom-js', $inline );
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
		$this->assertSame( $this->dia( '+10 days' ), get_post_meta( $id, ProcedureMetaKeys::OPENS_AT, true ) );
		$this->assertSame( array( ProcedureMetaKeys::OWNERSHIP_PUBLIC ), get_post_meta( $id, ProcedureMetaKeys::OWNERSHIP, true ) );

		$this->assertSame( (string) $id, $this->query_arg( (string) $url, ProcedureEditor::ARG_PROCEDURE ), 'y abre su taller' );
		$this->assertSame( 'creado', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
	}

	// ─── a quién se dirige ─────────────────────────────────────────────────

	/**
	 * «Dirigido a» nace sin nada marcado: quien no lo toca no abre el
	 * procedimiento a todos sin querer, y crear sin elegir se rechaza.
	 */
	public function test_a_new_procedure_starts_without_an_ownership() {
		$area  = $this->area( 'Área de prueba' );
		$curso = $this->course();
		$uid   = $this->manager( array( $area ) );
		$this->acting_as( $uid );

		$_GET = array();
		$html = ProcedureEditorView::html( ProcedureEditor::model() );
		foreach ( array_keys( ProcedureMetaKeys::ownerships() ) as $clave ) {
			$this->assertDoesNotMatchRegularExpression( '#id="prc-ownership-' . $clave . '"[^>]*checked#s', $html, 'nada marcado de antemano: ' . $clave );
		}

		$sin = $this->datos( $area, $curso, array( ProcedureMetaKeys::OWNERSHIP => array() ) );
		unset( $sin[ ProcedureMetaKeys::OWNERSHIP ] );
		$this->assertNull( $this->submit( $uid, ProcedureEditor::PANEL_DATA, 0, 0, $sin ), 'sin elegir no se crea' );
		$this->assertStringContainsString( 'públicos, privados y concertados, o los dos', ProcedureInput::why( array( 'ownership' ) ) );
	}

	/**
	 * Uno de antes sin titularidad guardada se dirige a todos, y el taller lo
	 * enseña así: se puede abrir y guardar sin que nadie lo descubra a la fuerza.
	 */
	public function test_an_old_procedure_without_ownership_shows_both() {
		list( , $uid, $procedimiento ) = $this->mio();
		delete_post_meta( $procedimiento, ProcedureMetaKeys::OWNERSHIP );
		$this->acting_as( $uid );

		$m = $this->modelo( $procedimiento, ProcedureEditor::PANEL_DATA );
		$this->assertSame( array_keys( ProcedureMetaKeys::ownerships() ), $m['values'][ ProcedureMetaKeys::OWNERSHIP ] );

		update_post_meta( $procedimiento, ProcedureMetaKeys::OWNERSHIP, array( ProcedureMetaKeys::OWNERSHIP_PRIVATE ) );
		$this->assertSame( array( ProcedureMetaKeys::OWNERSHIP_PRIVATE ), $this->modelo( $procedimiento, ProcedureEditor::PANEL_DATA )['values'][ ProcedureMetaKeys::OWNERSHIP ] );
	}

	/**
	 * La ayuda de «Quién solicita» dice lo que toca a la opción elegida, y la
	 * de centros avisa de la cuenta personal y del cargo de dirección.
	 */
	public function test_the_audience_help_follows_the_choice() {
		$area = $this->area( 'Área de prueba' );
		$this->acting_as( $this->manager( array( $area ) ) );
		$_GET = array();
		$html = ProcedureEditorView::html( ProcedureEditor::model() );

		$this->assertMatchesRegularExpression( '#<p class="prc-campo__ayuda" id="prc-audience-ayuda">\s*<span data-prc-schools-only\s*>[^<]*cuenta personal[^<]*cuenta genérica del centro[^<]*cargo de dirección[^<]*</span>\s*<span class="prc-solo-docentes">Cada docente presenta su propia solicitud con su cuenta personal\.</span>#u', $html );

		list( , $uid, $docentes ) = $this->mio( array( ProcedureMetaKeys::AUDIENCE => ProcedureMetaKeys::AUDIENCE_TEACHERS ) );
		$this->acting_as( $uid );
		$html = ProcedureEditorView::html( $this->modelo( $docentes, ProcedureEditor::PANEL_DATA ) );
		$this->assertMatchesRegularExpression( '#<span data-prc-schools-only hidden>#', $html, 'sin guion se ve la que toca a lo guardado' );
	}

	/**
	 * La cabecera del taller dice a quién se dirige, junto al estado.
	 */
	public function test_the_workshop_head_says_who_may_apply() {
		list( , $uid, $procedimiento ) = $this->mio( array( ProcedureMetaKeys::OWNERSHIP => array( ProcedureMetaKeys::OWNERSHIP_PUBLIC ) ) );
		$this->acting_as( $uid );
		$this->assertStringContainsString( '<span class="prc-state prc-state-audience">Centros públicos</span>', ProcedureEditorView::html( $this->modelo( $procedimiento ) ) );

		update_post_meta( $procedimiento, ProcedureMetaKeys::ISLANDS, array( 'La Palma' ) );
		$this->assertStringContainsString( '<span class="prc-state prc-state-audience">Centros públicos de La Palma</span>', ProcedureEditorView::html( $this->modelo( $procedimiento ) ) );

		update_post_meta( $procedimiento, ProcedureMetaKeys::AUDIENCE, ProcedureMetaKeys::AUDIENCE_TEACHERS );
		$this->assertStringContainsString( '<span class="prc-state prc-state-audience">Profesorado</span>', ProcedureEditorView::html( $this->modelo( $procedimiento ) ) );
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
					ProcedureEditor::FIELD_TITLE         => 'Red de prueba 2027',
					ProcedureMetaKeys::AMEND_OPENS_AT    => $this->dia( '+29 days' ),
					ProcedureMetaKeys::AMEND_CLOSES_AT   => $this->dia( '+34 days' ),
					ProcedureMetaKeys::CONTACT_EMAILS    => array( 'Ambito@Example.org', 'Segundo@Example.org', '' ),
					ProcedureMetaKeys::COORDINATOR_COUNT => '2',
					ProcedureMetaKeys::COMMITMENTS       => 'El centro se compromete.',
					ProcedureMetaKeys::OWNERSHIP         => array( 'public', 'private', 'inventada' ),
				)
			)
		);

		$this->assertSame( ProcedureEditor::PANEL_DATA, $this->query_arg( (string) $url, ProcedureEditor::ARG_PANEL ) );
		$this->assertSame( 'datos', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
		$this->assertSame( 'Red de prueba 2027', get_the_title( $procedimiento ) );
		$this->assertSame( '<p>Una red de centros.</p>', get_post_field( 'post_content', $procedimiento ) );
		$this->assertSame( array( $curso ), wp_get_post_terms( $procedimiento, ProcedureTaxonomies::COURSE, array( 'fields' => 'ids' ) ) );
		$this->assertSame( $this->dia( '+24 days' ), get_post_meta( $procedimiento, ProcedureMetaKeys::CLOSES_AT, true ) );
		$this->assertSame( $this->dia( '+34 days' ), get_post_meta( $procedimiento, ProcedureMetaKeys::AMEND_CLOSES_AT, true ) );
		$this->assertSame(
			array( 'ambito@example.org', 'segundo@example.org' ),
			get_post_meta( $procedimiento, ProcedureMetaKeys::CONTACT_EMAILS, true ),
			'tres campos, dos escritos'
		);
		$this->assertSame( 2, (int) get_post_meta( $procedimiento, ProcedureMetaKeys::COORDINATOR_COUNT, true ) );
		$this->assertSame( 'El centro se compromete.', get_post_meta( $procedimiento, ProcedureMetaKeys::COMMITMENTS, true ) );
		$this->assertSame( array( 'public', 'private' ), get_post_meta( $procedimiento, ProcedureMetaKeys::OWNERSHIP, true ), 'solo la lista cerrada' );
	}

	/**
	 * «Qué centros pueden solicitar»: las casillas salen del catálogo, se
	 * guardan, y una lista mal escrita no guarda nada y repinta lo tecleado.
	 */
	public function test_the_data_panel_narrows_which_centres_may_apply() {
		add_filter(
			'prc_centres',
			static function () {
				return array(
					array(
						'code'   => 'C0001',
						'name'   => 'Escuela Inventada',
						'type'   => 'CEIP',
						'island' => 'Isla Uno',
					),
					array(
						'code'   => 'C0002',
						'name'   => 'Instituto Inventado',
						'type'   => 'IES',
						'island' => 'Isla Dos',
					),
				);
			}
		);
		list( $area, $uid, $procedimiento ) = $this->mio( array( ProcedureMetaKeys::ISLANDS => array( 'Isla Perdida' ) ) );
		$curso                              = $this->course( '2027-2028' );

		$this->acting_as( $uid );
		$m    = $this->modelo( $procedimiento );
		$html = ProcedureEditorView::html( $m );
		$this->assertSame( array( 'CEIP', 'IES' ), $m['centre_options']['types'] );
		$this->assertSame( array( 'Isla Dos', 'Isla Perdida', 'Isla Uno' ), $m['centre_options']['islands'], 'lo elegido se ofrece aunque el catálogo ya no lo traiga' );
		$this->assertStringContainsString( '<legend class="prc-seccion__titulo">Qué centros pueden solicitar</legend>', $html );
		$this->assertStringContainsString( 'name="' . ProcedureMetaKeys::CENTRE_TYPES . '[]" value="IES"', $html );
		$this->assertStringContainsString( 'name="' . ProcedureMetaKeys::ALLOWED_CENTRES . '"', $html );

		$url = $this->submit(
			$uid,
			ProcedureEditor::PANEL_DATA,
			$procedimiento,
			0,
			$this->datos(
				$area,
				$curso,
				array(
					ProcedureMetaKeys::CENTRE_TYPES    => array( 'CEIP' ),
					ProcedureMetaKeys::ISLANDS         => array( 'Isla Uno', 'Isla Uno' ),
					ProcedureMetaKeys::ALLOWED_CENTRES => " C0001,\nC0002\n\nC0001 ",
				)
			)
		);
		$this->assertSame( 'datos', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
		$this->assertSame( array( 'CEIP' ), get_post_meta( $procedimiento, ProcedureMetaKeys::CENTRE_TYPES, true ) );
		$this->assertSame( array( 'Isla Uno' ), get_post_meta( $procedimiento, ProcedureMetaKeys::ISLANDS, true ) );
		$this->assertSame( array( 'C0001', 'C0002' ), get_post_meta( $procedimiento, ProcedureMetaKeys::ALLOWED_CENTRES, true ) );
		$this->acting_as( $uid );
		$this->assertSame( "C0001\nC0002", $this->modelo( $procedimiento )['values'][ ProcedureMetaKeys::ALLOWED_CENTRES ] );

		$this->olvidar_rechazo();
		$this->assertNull(
			$this->submit( $uid, ProcedureEditor::PANEL_DATA, $procedimiento, 0, $this->datos( $area, $curso, array( ProcedureMetaKeys::ALLOWED_CENTRES => 'C0001, mal-escrito' ) ) )
		);
		$this->assertSame( array( 'C0001', 'C0002' ), get_post_meta( $procedimiento, ProcedureMetaKeys::ALLOWED_CENTRES, true ), 'no se guarda nada' );
		$m = $this->modelo( $procedimiento );
		$this->assertStringContainsString( 'lista cerrada de centros', $m['flash']['texto'] );
		$this->assertSame( 'C0001, mal-escrito', $m['values'][ ProcedureMetaKeys::ALLOWED_CENTRES ], 'se repinta lo tecleado' );

		// Sin marcar nada se quita el acotado: vuelve a ser para todos.
		$this->olvidar_rechazo();
		$this->assertNotNull( $this->submit( $uid, ProcedureEditor::PANEL_DATA, $procedimiento, 0, $this->datos( $area, $curso ) ) );
		$this->assertSame( array(), get_post_meta( $procedimiento, ProcedureMetaKeys::CENTRE_TYPES, true ) );
		$this->assertSame( array(), get_post_meta( $procedimiento, ProcedureMetaKeys::ALLOWED_CENTRES, true ) );
	}

	/**
	 * Lo guardado con otras mayúsculas que el catálogo sale marcado, y guardar
	 * «Datos» sin tocar las casillas no borra el acotado.
	 */
	public function test_the_data_panel_keeps_choices_that_differ_only_in_case() {
		add_filter(
			'prc_centres',
			static function () {
				return array(
					array(
						'code'   => 'C0001',
						'name'   => 'Escuela Inventada',
						'type'   => 'CEIP',
						'island' => 'Isla Uno (inventada)',
					),
					array(
						'code'   => 'C0002',
						'name'   => 'Instituto Inventado',
						'type'   => 'IES',
						'island' => 'Isla Dos (inventada)',
					),
				);
			}
		);
		list( $area, $uid, $procedimiento ) = $this->mio(
			array(
				ProcedureMetaKeys::CENTRE_TYPES => array( 'ceip' ),
				ProcedureMetaKeys::ISLANDS      => array( 'isla uno (inventada)' ),
			)
		);
		$curso                              = $this->course( '2027-2028' );

		$this->acting_as( $uid );
		$m    = $this->modelo( $procedimiento );
		$html = ProcedureEditorView::html( $m );
		$this->assertSame( array( 'ceip', 'IES' ), $m['centre_options']['types'], 'una sola casilla, con la grafía guardada' );
		$this->assertSame( array( 'Isla Dos (inventada)', 'isla uno (inventada)' ), $m['centre_options']['islands'] );

		// Lo que mandaría el navegador: las casillas marcadas, tal cual.
		$marcadas = array();
		foreach ( array( ProcedureMetaKeys::CENTRE_TYPES, ProcedureMetaKeys::ISLANDS ) as $nombre ) {
			preg_match_all( '/name="' . preg_quote( $nombre, '/' ) . '\[\]" value="([^"]*)"\s*checked=/', $html, $coincidencias );
			$marcadas[ $nombre ] = array_map( 'html_entity_decode', $coincidencias[1] );
		}
		$this->assertSame( array( 'ceip' ), $marcadas[ ProcedureMetaKeys::CENTRE_TYPES ], 'la casilla sale marcada' );
		$this->assertSame( array( 'isla uno (inventada)' ), $marcadas[ ProcedureMetaKeys::ISLANDS ], 'la casilla sale marcada' );

		$this->assertNotNull( $this->submit( $uid, ProcedureEditor::PANEL_DATA, $procedimiento, 0, $this->datos( $area, $curso, $marcadas ) ) );
		$this->assertSame( array( 'ceip' ), get_post_meta( $procedimiento, ProcedureMetaKeys::CENTRE_TYPES, true ) );
		$this->assertSame( array( 'isla uno (inventada)' ), get_post_meta( $procedimiento, ProcedureMetaKeys::ISLANDS, true ) );
	}

	/**
	 * El panel «Datos» avisa de lo que cierra sin verse: el catálogo vacío y
	 * una sola titularidad sin ningún tipo de centro marcado como privado.
	 */
	public function test_the_data_panel_warns_about_silent_closures() {
		remove_all_filters( 'prc_private_centre_types' );
		list( , $uid, $procedimiento ) = $this->mio(
			array(
				ProcedureMetaKeys::OWNERSHIP    => array( ProcedureMetaKeys::OWNERSHIP_PUBLIC ),
				ProcedureMetaKeys::CENTRE_TYPES => array( 'CEIP' ),
			)
		);
		$this->acting_as( $uid );
		$html = ProcedureEditorView::html( $this->modelo( $procedimiento ) );
		$this->assertStringContainsString( 'ningún tipo de centro marcado como privado', $html );
		$this->assertStringContainsString( 'El catálogo de centros está vacío', $html );
		$this->assertStringContainsString( 'ningún centro puede solicitar hasta que se cargue', $html );
		$this->assertStringContainsString( 'un centro que ya la presentó la sigue editando o subsanando', $html );

		// Con catálogo y con tipos privados, nada que avisar.
		add_filter(
			'prc_centres',
			static function () {
				return array(
					array(
						'code' => 'C0001',
						'name' => 'Escuela Inventada',
						'type' => 'CEIP',
					),
				);
			}
		);
		add_filter(
			'prc_private_centre_types',
			static function () {
				return array( 'Colegio privado inventado' );
			}
		);
		$html = ProcedureEditorView::html( $this->modelo( $procedimiento ) );
		$this->assertStringNotContainsString( 'prc-aviso-centros', $html );
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
	public function test_the_create_screen_asks_the_minimum_without_its_hidden_fields() {
		$area  = $this->area( 'Área de prueba' );
		$curso = $this->course();
		$uid   = $this->manager( array( $area ) );
		$this->acting_as( $uid );

		$_GET = array();
		$html = ProcedureEditorView::html( ProcedureEditor::model() );

		// Las cuatro bandas son los literales del sistema que se sustituye;
		// «Formato Express», su jerga, ya no. Las mayúsculas de la banda las
		// pone el CSS: escritas, un lector de pantalla las deletrea.
		$this->assertStringNotContainsString( 'Formato Express', $html );
		$this->assertStringContainsString( 'Rellene lo mínimo para crearlo.', $html );
		$this->assertStringContainsString( '&larr; Gestión de procedimientos</a>', $html, 'la vuelta se llama como la pantalla' );
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
		$this->assertStringContainsString( 'Modalidad para centros CER ', $html );
		$this->assertSame( 3, substr_count( $html, 'name="' . ProcedureMetaKeys::CENTRE_KINDS . '" value="' ) );
		$this->assertStringContainsString( 'data-prc-schools-only', $html );
		$this->assertStringContainsString( 'value="school"', $html );
		$this->assertStringContainsString( 'value="rural_group"', $html );
		$this->assertStringContainsString( 'value="both"', $html );
		$this->assertStringContainsString( '>Ambos</label>', $html );
		$this->assertStringNotContainsString( 'Tipología', $html );
		$this->assertStringContainsString( 'name="' . ProcedureMetaKeys::OWNERSHIP . '[]"', $html );
		$this->assertStringNotContainsString( '¿Pide la solicitud personas coordinadoras?', $html, 'la configuración de la solicitud aparece en el taller después del alta' );
		foreach ( ProcedureMetaKeys::audiences() as $rotulo ) {
			$this->assertStringContainsString( esc_html( $rotulo ), $html );
		}

		// El ámbito, como árbol de casillas y no como desplegable.
		$this->assertStringContainsString( 'class="prc-arbol"', $html );
		$this->assertStringContainsString( 'name="' . ProcedureEditor::FIELD_AREA . '[]" value="' . $area . '"', $html );
		$this->assertStringNotContainsString( '<select class="prc-control" id="prc-area"', $html );

		// Los tres correos —el primero, y los otros como un grupo opcional— y
		// las diez muestras de color, sin asterisco: ya viene una elegida.
		$this->assertSame( ProcedureMetaKeys::CONTACT_EMAILS_MAX, substr_count( $html, 'name="' . ProcedureMetaKeys::CONTACT_EMAILS . '[]"' ) );
		$this->assertStringContainsString( 'Otros correos de contacto (opcional)', $html );
		$this->assertStringNotContainsString( '2.º correo', $html );
		$this->assertMatchesRegularExpression( '#<legend class="prc-campo__etiqueta">Color de cabecera\s*</legend>#', $html );
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
	 * La coordinación se pregunta en tres casos —ninguna, una o varias— y el
	 * número va dentro de la opción «varias».
	 *
	 * Es la misma meta de siempre: el formulario no guarda el caso, guarda
	 * cuántas. Así lo que ya estaba escrito como cero o uno se sigue leyendo.
	 */
	public function test_the_coordinator_question_offers_none_one_or_several() {
		list( , $uid, $id ) = $this->mio( array( ProcedureMetaKeys::COORDINATOR_COUNT => 5 ) );
		$this->acting_as( $uid );

		$html = ProcedureEditorView::html( $this->modelo( $id ) );

		$this->assertStringContainsString( '¿Pide la solicitud personas coordinadoras?', $html );
		$this->assertStringContainsString( 'No se piden', $html );
		$this->assertStringContainsString( 'Sí, una persona', $html );
		$this->assertStringContainsString( 'Sí, varias personas:', $html );
		foreach ( array( 'none', 'one', 'several' ) as $tipo ) {
			$this->assertStringContainsString( 'name="' . ProcedureEditor::FIELD_COORDINATOR_KIND . '" value="' . $tipo . '"', $html );
		}
		$this->assertMatchesRegularExpression( '/value="several"\s+checked/', $html );
		$this->assertStringContainsString( 'id="prc-coordinator-count"', $html );
		$this->assertMatchesRegularExpression( '/value="5"\s+selected/', $html );
	}

	/**
	 * Con la lista de «Personas» recogiendo la coordinación, «Datos» no la
	 * pide también: se rechaza y lo dice. «No se piden» se guarda, y la ayuda
	 * remite a «Personas» para varios papeles o ejes.
	 */
	public function test_datos_does_not_ask_for_coordination_the_list_collects() {
		list( $area, $uid, $id ) = $this->mio(
			array(
				ProcedureMetaKeys::PEOPLE_SPEC => array(
					'enabled' => true,
					'roles'   => array(
						array(
							'key'   => 'r1',
							'label' => 'Coordinación general',
						),
					),
					'min'     => 1,
				),
			)
		);
		$curso                   = $this->course( '2027-2028' );

		$this->assertNull( $this->submit( $uid, ProcedureEditor::PANEL_DATA, $id, 0, $this->datos( $area, $curso, array( ProcedureEditor::FIELD_COORDINATOR_KIND => 'one' ) ) ) );
		$this->assertSame( 0, (int) get_post_meta( $id, ProcedureMetaKeys::COORDINATOR_COUNT, true ) );
		$m = $this->modelo( $id );
		$this->assertStringContainsString( 'La coordinación ya se da de alta en la lista de la pestaña «Personas»', $m['flash']['texto'] );
		$this->olvidar_rechazo();

		$this->assertNotNull( $this->submit( $uid, ProcedureEditor::PANEL_DATA, $id, 0, $this->datos( $area, $curso, array( ProcedureEditor::FIELD_COORDINATOR_KIND => 'none' ) ) ) );
		$this->assertStringContainsString( 'Si necesita varios papeles o ejes', ProcedureEditorView::html( $this->modelo( $id ) ) );
	}

	/**
	 * El radio manda sobre el número: «ninguna» y «una» no dependen de lo que
	 * quede en el desplegable, y «varias» nunca guarda menos de dos.
	 */
	public function test_the_coordinator_radio_decides_the_number_that_is_saved() {
		list( $area, $uid, $id ) = $this->mio();
		$curso                   = $this->course( '2027-2028' );

		$this->assertNotNull(
			$this->submit(
				$uid,
				ProcedureEditor::PANEL_DATA,
				$id,
				0,
				$this->datos(
					$area,
					$curso,
					array(
						ProcedureEditor::FIELD_COORDINATOR_KIND => 'several',
						ProcedureMetaKeys::COORDINATOR_COUNT    => '6',
					)
				)
			)
		);
		$this->assertSame( 6, (int) get_post_meta( $id, ProcedureMetaKeys::COORDINATOR_COUNT, true ) );

		$this->submit(
			$uid,
			ProcedureEditor::PANEL_DATA,
			$id,
			0,
			$this->datos(
				$area,
				$curso,
				array(
					ProcedureEditor::FIELD_COORDINATOR_KIND => 'one',
					ProcedureMetaKeys::COORDINATOR_COUNT => '6',
				)
			)
		);
		$this->assertSame( 1, (int) get_post_meta( $id, ProcedureMetaKeys::COORDINATOR_COUNT, true ) );

		$this->submit(
			$uid,
			ProcedureEditor::PANEL_DATA,
			$id,
			0,
			$this->datos(
				$area,
				$curso,
				array(
					ProcedureEditor::FIELD_COORDINATOR_KIND => 'none',
					ProcedureMetaKeys::COORDINATOR_COUNT => '6',
				)
			)
		);
		$this->assertSame( 0, (int) get_post_meta( $id, ProcedureMetaKeys::COORDINATOR_COUNT, true ) );

		$this->submit(
			$uid,
			ProcedureEditor::PANEL_DATA,
			$id,
			0,
			$this->datos(
				$area,
				$curso,
				array(
					ProcedureEditor::FIELD_COORDINATOR_KIND => 'several',
					ProcedureMetaKeys::COORDINATOR_COUNT => '1',
				)
			)
		);
		$this->assertSame( 2, (int) get_post_meta( $id, ProcedureMetaKeys::COORDINATOR_COUNT, true ), 'varias no puede quedarse en una' );
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
						ProcedureMetaKeys::AMEND_OPENS_AT => $this->dia( '+19 days' ),
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
		$this->assertSame( $this->dia( '+19 days' ), $m['values'][ ProcedureMetaKeys::AMEND_OPENS_AT ] );
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

	/**
	 * Un enlace nuevo tiene que empezar por https://; uno de antes que no
	 * cambia se sigue guardando. Lo guardado se puede abrir desde el panel.
	 */
	public function test_new_links_need_https_and_saved_ones_can_be_opened() {
		list( , $uid, $procedimiento ) = $this->mio( array( ProcedureMetaKeys::PROVISIONAL_LIST_URL => 'http://example.org/antiguo.pdf' ) );

		$this->assertNull(
			$this->submit(
				$uid,
				ProcedureEditor::PANEL_LINKS,
				$procedimiento,
				0,
				array(
					ProcedureMetaKeys::RESOLUTION_URL => 'http://example.org/resolucion.pdf',
					ProcedureMetaKeys::PROVISIONAL_LIST_URL => 'http://example.org/antiguo.pdf',
				)
			)
		);
		$this->assertSame( '', get_post_meta( $procedimiento, ProcedureMetaKeys::RESOLUTION_URL, true ) );
		$m = $this->modelo( $procedimiento, ProcedureEditor::PANEL_LINKS );
		$this->assertStringContainsString( 'Revise el enlace a la resolución. Pegue la dirección completa, que empieza por https://', $m['flash']['texto'] );
		$this->olvidar_rechazo();

		$this->assertNotNull(
			$this->submit(
				$uid,
				ProcedureEditor::PANEL_LINKS,
				$procedimiento,
				0,
				array(
					ProcedureMetaKeys::RESOLUTION_URL => 'https://example.org/resolucion.pdf',
					ProcedureMetaKeys::PROVISIONAL_LIST_URL => 'http://example.org/antiguo.pdf',
				)
			),
			'el enlace de antes, sin tocar, no impide guardar'
		);
		$this->assertSame( 'https://example.org/resolucion.pdf', get_post_meta( $procedimiento, ProcedureMetaKeys::RESOLUTION_URL, true ) );

		$html = ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_LINKS ) );
		$this->assertStringContainsString( '<a href="https://example.org/resolucion.pdf" target="_blank" rel="noopener noreferrer">Abrir el enlace guardado ↗', $html );
		$this->assertSame( 2, substr_count( $html, 'Abrir el enlace guardado' ), 'el definitivo, vacío, no tiene enlace' );
	}

	// ─── Publicación ───────────────────────────────────────────────────────

	/**
	 * Publicar, marcar como histórico y desmarcar: lo último, solo administración.
	 */
	public function test_publish_archive_and_unarchive() {
		list( , $uid, $procedimiento ) = $this->mio( array( ProcedureMetaKeys::OPENS_AT => $this->dia( '+10 days' ) ), array( 'post_status' => 'draft' ) );
		$this->acting_as( $uid );

		// Sin curso no se publica: no se ofrece ni se acepta (PR 8 del PLAN-0003).
		$m = $this->modelo( $procedimiento );
		$this->assertFalse( $m['can_publish'] );
		$this->assertStringContainsString( 'Falta el curso', implode( ' ', $m['not_ready'] ) );
		$this->assertNull( $this->submit( $uid, ProcedureEditor::OP_PUBLISH, $procedimiento ) );
		$this->assertSame( 'draft', get_post_status( $procedimiento ) );
		$this->olvidar_rechazo();

		wp_set_object_terms( $procedimiento, array( $this->course() ), ProcedureTaxonomies::COURSE );
		$this->assertTrue( $this->modelo( $procedimiento )['can_publish'] );

		$url = $this->submit( $uid, ProcedureEditor::OP_PUBLISH, $procedimiento );
		$this->assertSame( 'publish', get_post_status( $procedimiento ) );
		$this->assertSame( 'publicado', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
		$this->assertFalse( $this->modelo( $procedimiento )['can_publish'] );

		// Vivo —«Próximo»— no se marca como histórico, y se dice por qué.
		$this->assertFalse( $this->modelo( $procedimiento )['can_archive'] );
		$this->assertNull( $this->submit( $uid, ProcedureEditor::OP_ARCHIVE, $procedimiento ) );
		$this->assertFalse( ProcedureAccess::is_archived( $procedimiento ) );
		$this->assertStringContainsString( 'Solo se marca como histórico un procedimiento cerrado o resuelto', $this->modelo( $procedimiento, ProcedureEditor::PANEL_PUBLISH )['flash']['texto'] );
		$this->olvidar_rechazo();

		update_post_meta( $procedimiento, ProcedureMetaKeys::APPLICATIONS_CLOSED, true );
		$this->assertTrue( $this->modelo( $procedimiento )['can_archive'] );
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

	/** A manager may pause applications, keep delivery dates, resume by calendar, and return a procedure to draft. */
	public function test_close_pause_keeps_delivery_windows_and_unpublish_returns_to_draft() {
		list( , $uid, $procedure ) = $this->mio();
		$closes_at                 = get_post_meta( $procedure, ProcedureMetaKeys::CLOSES_AT, true );
		$before                    = ProcedureState::of_post( $procedure );
		$questions                 = ProcedureQuestions::sanitize(
			array(
				array(
					'label'     => 'Memoria',
					'type'      => ProcedureQuestions::TYPE_FILE,
					'delivery'  => true,
					'opens_at'  => $this->dia( '-1 day' ),
					'closes_at' => $this->dia( '+1 day' ),
				),
			)
		);
		update_post_meta( $procedure, ProcedureMetaKeys::QUESTIONS, $questions );

		$url = $this->submit( $uid, ProcedureEditor::OP_TOGGLE_APPLICATIONS, $procedure );
		$this->assertTrue( (bool) get_post_meta( $procedure, ProcedureMetaKeys::APPLICATIONS_CLOSED, true ) );
		$this->assertSame( $closes_at, get_post_meta( $procedure, ProcedureMetaKeys::CLOSES_AT, true ) );
		$this->assertSame( $questions, get_post_meta( $procedure, ProcedureMetaKeys::QUESTIONS, true ) );
		$this->assertTrue( ProcedureQuestions::delivery_is_open( $questions[0], ProcedureState::today() ) );
		$this->assertSame( ProcedureMetaKeys::STATE_CLOSED, ProcedureState::of_post( $procedure ) );
		$this->assertSame( 'cerradas', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
		$this->assertFalse( $this->modelo( $procedure, ProcedureEditor::PANEL_PUBLISH )['pending_documentation'] );
		$this->application( $procedure, $this->school_head( 'C0001' ) );
		$this->assertTrue( $this->modelo( $procedure, ProcedureEditor::PANEL_PUBLISH )['pending_documentation'] );

		$url = $this->submit( $uid, ProcedureEditor::OP_TOGGLE_APPLICATIONS, $procedure );
		$this->assertFalse( (bool) get_post_meta( $procedure, ProcedureMetaKeys::APPLICATIONS_CLOSED, true ) );
		$this->assertSame( $before, ProcedureState::of_post( $procedure ) );
		$this->assertSame( 'reabiertas', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );

		$url = $this->submit( $uid, ProcedureEditor::OP_UNPUBLISH, $procedure );
		$this->assertSame( 'draft', get_post_status( $procedure ) );
		$this->assertSame( ProcedureMetaKeys::STATE_DRAFT, ProcedureState::of_post( $procedure ) );
		$this->assertSame( 'borrador', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
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

	/** The review table for teacher calls shows the applicant, not centre data. */
	public function test_teacher_applications_panel_shows_teacher_identity() {
		list( , $uid, $procedure ) = $this->mio( array( ProcedureMetaKeys::AUDIENCE => ProcedureMetaKeys::AUDIENCE_TEACHERS ) );
		$teacher                   = (int) self::factory()->user->create(
			array(
				'role'         => 'prc_teacher',
				'display_name' => 'Docente de prueba',
			)
		);
		$application               = $this->application(
			$procedure,
			$teacher,
			'',
			array( ApplicationMetaKeys::APPLICANT_TYPE => ApplicationMetaKeys::TYPE_TEACHER )
		);
		$this->acting_as( $uid );
		$html = ProcedureEditorView::html( $this->modelo( $procedure, ProcedureEditor::PANEL_APPLICATIONS ) );
		$this->assertStringContainsString( '<th class="prc-th" scope="col">Docente</th>', $html );
		$this->assertStringContainsString( 'Docente de prueba', $html );
		$this->assertStringContainsString( 'docente de prueba', strtolower( $html ) );
		$this->assertStringNotContainsString( '>Código</th>', $html );
		$this->assertStringNotContainsString( '>Cargo</th>', $html );
		$this->assertStringContainsString( 'value="' . $application . '"', $html );
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
	 * Un procedimiento de antes sin titularidad guardada sigue guardando sus
	 * fechas con los datos cerrados: la casilla que nadie puede tocar no
	 * tumba el envío.
	 */
	public function test_an_old_procedure_without_ownership_still_saves_its_dates() {
		list( $area, $uid, $procedimiento ) = $this->en_estado( ProcedureMetaKeys::STATE_CLOSED );
		delete_post_meta( $procedimiento, ProcedureMetaKeys::OWNERSHIP );

		$sin = $this->datos(
			$area,
			$this->course( '2099-2100' ),
			array(
				ProcedureMetaKeys::OPENS_AT  => '2026-01-07',
				ProcedureMetaKeys::CLOSES_AT => '2026-01-20',
			)
		);
		unset( $sin[ ProcedureMetaKeys::OWNERSHIP ] );
		$url = $this->submit( $uid, ProcedureEditor::PANEL_DATA, $procedimiento, 0, $sin );

		$this->assertSame( 'datos', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
		$this->assertSame( '2026-01-20', get_post_meta( $procedimiento, ProcedureMetaKeys::CLOSES_AT, true ) );
		$this->assertSame( 'Todos los centros', \Prc\PublicFront\View\ProcedureChrome::ownership_label( (array) get_post_meta( $procedimiento, ProcedureMetaKeys::OWNERSHIP, true ) ) );
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
		$this->assertStringContainsString( 'Las solicitudes están cerradas', $m['flash']['texto'] );
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
		$this->assertStringContainsString( 'Las solicitudes están cerradas', (string) $m['state_notice'] );

		$html = ProcedureEditorView::html( $m );
		$this->assertStringContainsString( 'Las solicitudes están cerradas', $html );
		$this->assertStringNotContainsString( 'prc-solo-lectura', $html, 'las fechas todavía se guardan' );
		$this->assertSame( 6, substr_count( $html, '<fieldset class="prc-seccion-campos" disabled>' ), 'los seis bloques que ya no se tocan, con «Qué centros pueden solicitar»' );
		$this->assertStringContainsString( '<fieldset class="prc-seccion-campos"><legend class="prc-seccion__titulo">Plazos</legend>', $html, 'los plazos no' );
		// Y cada bloque apagado dice por qué lo está, junto al bloque.
		$this->assertSame( 6, substr_count( $html, 'class="prc-seccion__bloqueo"' ) );

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
		$this->acting_as( $uid );
		wp_update_post(
			array(
				'ID'          => $procedimiento,
				'post_status' => 'publish',
			)
		);
		$this->application( $procedimiento, $this->school_head( 'C0001' ), '', array( ApplicationMetaKeys::ANSWERS => array( 'q1' => 'B' ) ) );
		$this->acting_as( $uid );

		$html   = ProcedureEditorView::html( $this->modelo( $procedimiento ) );
		$labels = array(
			'Datos',
			'Preguntas',
			'Entregas',
			'Enlaces',
			'Solicitudes',
			'Publicación',
			'Ver la ficha pública',
			'Guardar los datos',
		);
		foreach ( $labels as $rotulo ) {
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
		$this->assertStringContainsString( 'data-rotulo="Fecha">' . current_time( 'd-m-Y' ) . '</td>', $html );
		$this->assertStringContainsString( 'Guardar la revisión', $html );
		$this->assertStringContainsString( 'Exportar las solicitudes', $html );
		$this->assertStringContainsString( 'value="' . ProcedureEditor::OP_EXPORT . '"', $html );
		$this->assertStringContainsString( 'method="post"', $html );
		foreach ( ApplicationMetaKeys::review_states() as $rotulo ) {
			$this->assertStringContainsString( esc_html( $rotulo ), $html );
		}

		$html = ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_PUBLISH ) );
		$this->assertStringNotContainsString( 'Marcar como histórico</button>', $html, 'vivo no se ofrece marcarlo' );
		$this->assertStringContainsString( 'Cerrar solicitudes ahora', $html );
		$this->assertStringContainsString( 'Devolver a borrador', $html );
		$this->assertStringNotContainsString( 'Publicar el procedimiento', $html, 'ya está publicado' );
		$this->assertStringNotContainsString( 'Solo administración', $html );
		$html = ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_DELIVERIES ) );
		$this->assertStringContainsString( 'Este procedimiento no tiene entregas con plazo propio.', $html );
	}

	/**
	 * Sin solicitudes se dice; en borrador se ofrece publicar; histórico se
	 * abre en solo lectura y administración ve el recuadro para reabrirlo.
	 */
	public function test_the_empty_the_draft_and_the_archived_screens() {
		list( , $uid, $procedimiento ) = $this->mio( array( ProcedureMetaKeys::OPENS_AT => $this->dia( '+10 days' ) ), array( 'post_status' => 'draft' ) );
		wp_set_object_terms( $procedimiento, array( $this->course() ), ProcedureTaxonomies::COURSE );
		$this->acting_as( $uid );

		$this->assertStringContainsString( 'Todavía no hay solicitudes', ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_APPLICATIONS ) ) );
		$html = ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_PUBLISH ) );
		$this->assertStringContainsString( 'Publicar el procedimiento', $html );
		$this->assertStringContainsString( 'aria-current="step"', $html );
		$this->assertMatchesRegularExpression( '/<h3>Borrador\s*<span[^>]*>Etapa actual/s', $html );

		$_GET = array();
		$html = ProcedureEditorView::html( ProcedureEditor::model() );
		$this->assertStringContainsString( 'Crear un procedimiento', $html );
		$this->assertStringContainsString( 'Crear el procedimiento', $html );

		// El sistema no marca como histórico un borrador: primero se publica.
		wp_update_post(
			array(
				'ID'          => $procedimiento,
				'post_status' => 'publish',
			)
		);
		update_post_meta( $procedimiento, ProcedureMetaKeys::ARCHIVED, true );
		$html = ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_PUBLISH ) );
		$this->assertStringContainsString( 'prc-solo-lectura', $html );
		$this->assertStringContainsString( 'histórico', $html );
		$this->assertMatchesRegularExpression( '/<h3>Histórico\s*<span[^>]*>Etapa actual/s', $html );
		$this->assertStringNotContainsString( 'Volver a abrir', $html );
		$html = ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_DELIVERIES ) );
		$this->assertStringContainsString( 'prc-solo-lectura', $html, 'el ámbito consulta el histórico sin poder ampliar plazos' );

		$this->acting_as( $this->administrator() );
		$html = ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_PUBLISH ) );
		$this->assertStringContainsString( 'Solo administración', $html );
		$this->assertStringContainsString( 'Volver a abrir el procedimiento', $html );
		$this->assertStringContainsString( 'value="' . ProcedureEditor::OP_UNARCHIVE . '"', $html );
	}

	/** The publication timeline follows the derived procedure lifecycle. */
	public function test_the_publication_timeline_marks_each_derived_stage() {
		list( , $uid, $procedure ) = $this->mio();
		$this->acting_as( $uid );
		wp_update_post(
			array(
				'ID'          => $procedure,
				'post_status' => 'publish',
			)
		);
		$panel = ProcedureEditor::PANEL_PUBLISH;
		update_post_meta(
			$procedure,
			ProcedureMetaKeys::QUESTIONS,
			array(
				array(
					'key'       => 'documento',
					'label'     => 'Documento',
					'type'      => ProcedureQuestions::TYPE_FILE,
					'delivery'  => true,
					'opens_at'  => $this->dia( '-2 days' ),
					'closes_at' => $this->dia( '+2 days' ),
				),
			)
		);
		$this->application( $procedure, $this->school_head( 'C0001' ) );
		$this->assertMatchesRegularExpression( '/<h3>Publicado\s*<span[^>]*>Etapa actual/s', ProcedureEditorView::html( $this->modelo( $procedure, $panel ) ) );

		update_post_meta( $procedure, ProcedureMetaKeys::OPENS_AT, $this->dia( '-10 days' ) );
		update_post_meta( $procedure, ProcedureMetaKeys::CLOSES_AT, $this->dia( '-1 day' ) );
		$this->assertMatchesRegularExpression( '/<h3>Pendiente de documentación\s*<span[^>]*>Etapa actual/s', ProcedureEditorView::html( $this->modelo( $procedure, $panel ) ) );

		update_post_meta( $procedure, ProcedureMetaKeys::FINAL_LIST_URL, 'https://example.org/listado.pdf' );
		$this->assertMatchesRegularExpression( '/<h3>Cerrado\s*<span[^>]*>Etapa actual/s', ProcedureEditorView::html( $this->modelo( $procedure, $panel ) ) );

		update_post_meta( $procedure, ProcedureMetaKeys::ARCHIVED, true );
		$this->assertMatchesRegularExpression( '/<h3>Histórico\s*<span[^>]*>Etapa actual/s', ProcedureEditorView::html( $this->modelo( $procedure, $panel ) ) );
	}

	/**
	 * El arranque engancha el shortcode y el que atiende los envíos.
	 */
	public function test_register_hooks_the_shortcode_and_the_handler() {
		ProcedureEditor::register();

		$this->assertTrue( shortcode_exists( ProcedureEditor::SHORTCODE ) );
		$this->assertSame( 20, has_action( 'init', array( ProcedureEditor::class, 'handle' ) ) );
	}

	/**
	 * El panel «Avisos»: una casilla por evento, encendida salvo que se apague;
	 * lo que se guarda es la lista de los apagados.
	 */
	public function test_the_notices_panel_saves_what_is_switched_off() {
		list( , $uid, $procedimiento ) = $this->mio();
		$todos                         = array_keys( ProcedureMetaKeys::notify_events() );

		// Recién creado, todo encendido.
		$this->acting_as( $uid );
		$html = ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_NOTICES ) );
		foreach ( ProcedureMetaKeys::notify_events() as $evento => $rotulo ) {
			$this->assertStringContainsString( esc_html( $rotulo ), $html );
			$this->assertMatchesRegularExpression( '/value="' . $evento . '"[^>]*checked/', $html, $evento . ' encendido' );
		}

		$url = $this->submit(
			$uid,
			ProcedureEditor::OP_NOTIFY,
			$procedimiento,
			0,
			array( ProcedureEditor::FIELD_NOTIFY => array( Mailer::EVENT_APPLICATION_SUBMITTED, Mailer::EVENT_REVIEW_CHANGED, 'inventado' ) )
		);
		$this->assertSame( 'avisos', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
		$this->assertSame( ProcedureEditor::PANEL_NOTICES, $this->query_arg( (string) $url, ProcedureEditor::ARG_PANEL ) );
		$this->assertEqualsCanonicalizing(
			array_values( array_diff( $todos, array( Mailer::EVENT_APPLICATION_SUBMITTED, Mailer::EVENT_REVIEW_CHANGED ) ) ),
			get_post_meta( $procedimiento, ProcedureMetaKeys::NOTIFY, true )
		);

		$this->acting_as( $uid );
		$html = ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_NOTICES ) );
		$this->assertDoesNotMatchRegularExpression( '/value="' . Mailer::EVENT_APPLICATION_RECEIVED . '"[^>]*checked/', $html, 'apagado, sin marcar' );

		// Ninguna marcada: todas apagadas.
		$this->submit( $uid, ProcedureEditor::OP_NOTIFY, $procedimiento );
		$this->assertEqualsCanonicalizing( $todos, get_post_meta( $procedimiento, ProcedureMetaKeys::NOTIFY, true ) );

		// Otro ámbito no toca nada.
		$ajena = $this->manager( array( $this->area( 'Otro ámbito' ) ) );
		$this->assertNull( $this->submit( $ajena, ProcedureEditor::OP_NOTIFY, $procedimiento, 0, array( ProcedureEditor::FIELD_NOTIFY => $todos ) ) );
		$this->assertEqualsCanonicalizing( $todos, get_post_meta( $procedimiento, ProcedureMetaKeys::NOTIFY, true ) );
	}
	/** The editor persists complete delivery windows and rejects malformed dates without replacing them. */
	public function test_the_editor_saves_and_validates_delivery_dates() {
		list( , $uid, $procedure ) = $this->mio();
		$q                         = ProcedureEditor::FIELD_Q;
		$fields                    = array(
			$q . 'label'     => array( 'Memoria' ),
			$q . 'type'      => array( 'file' ),
			$q . 'delivery'  => array( '1' ),
			$q . 'opens_at'  => array( '2027-06-01' ),
			$q . 'closes_at' => array( '2027-06-30' ),
		);
		$this->assertNotNull( $this->submit( $uid, ProcedureEditor::PANEL_QUESTIONS, $procedure, 0, $fields ) );
		$before = get_post_meta( $procedure, ProcedureMetaKeys::QUESTIONS, true );
		$this->assertTrue( $before[0]['delivery'] );
		$this->assertSame( '2027-06-30', $before[0]['closes_at'] );
		$this->assertSame( 1, $this->modelo( $procedure )['panels'][ ProcedureEditor::PANEL_DELIVERIES ]['count'] );
		$fields[ $q . 'closes_at' ] = array( '2027-05-01' );
		$this->assertNull( $this->submit( $uid, ProcedureEditor::PANEL_QUESTIONS, $procedure, 0, $fields ) );
		$this->assertSame( $before, get_post_meta( $procedure, ProcedureMetaKeys::QUESTIONS, true ) );
	}

	/** A scoped manager may only extend an existing delivery close after questions lock. */
	public function test_delivery_closes_can_be_extended_without_reopening_questions() {
		$procedure_close           = $this->dia( '-1 day' );
		$question                  = array(
			'key'       => 'q1',
			'label'     => 'Memoria final',
			'type'      => ProcedureQuestions::TYPE_FILE,
			'required'  => true,
			'choices'   => array(),
			'delivery'  => true,
			'opens_at'  => $this->dia( '-30 days' ),
			'closes_at' => $procedure_close,
		);
		list( , $uid, $procedure ) = $this->mio(
			array(
				ProcedureMetaKeys::QUESTIONS => array( $question ),
				ProcedureMetaKeys::OPENS_AT  => $this->dia( '-30 days' ),
				ProcedureMetaKeys::CLOSES_AT => $procedure_close,
			)
		);
		$before                    = get_post_meta( $procedure, ProcedureMetaKeys::QUESTIONS, true );
		$this->assertFalse( ProcedureAccess::can_edit_group( $uid, $procedure, ProcedureState::GROUP_QUESTIONS ) );
		$new_close = $this->dia( '+30 days' );
		$redirect  = $this->submit( $uid, ProcedureEditor::OP_DELIVERY_DATES, $procedure, 0, array( ProcedureEditor::FIELD_DELIVERY_CLOSES => array( 'q1' => $new_close ) ) );

		$this->assertNotNull( $redirect );
		$this->assertSame( 'entregas', $this->query_arg( (string) $redirect, ProcedureEditor::ARG_NOTICE ) );
		$html = ProcedureEditorView::html( $this->modelo( $procedure, ProcedureEditor::PANEL_DELIVERIES ) );
		$this->assertStringContainsString( 'Guardar los nuevos cierres', $html );
		$this->assertStringNotContainsString( 'prc-solo-lectura', $html );
		$after = get_post_meta( $procedure, ProcedureMetaKeys::QUESTIONS, true );
		$this->assertSame( $new_close, $after[0]['closes_at'] );
		$after[0]['closes_at'] = $before[0]['closes_at'];
		$this->assertSame( $before, $after, 'no cambió rótulo, apertura, obligatoriedad ni respuesta' );
	}

	/** Invalid or shorter dates reject the entire update; arbitrary keys are ignored. */
	public function test_delivery_close_updates_reject_shorten_and_invalid_dates() {
		$current_close             = $this->dia( '+10 days' );
		$question                  = array(
			'key'       => 'q1',
			'label'     => 'Memoria',
			'type'      => ProcedureQuestions::TYPE_FILE,
			'required'  => true,
			'choices'   => array(),
			'delivery'  => true,
			'opens_at'  => $this->dia( '-1 day' ),
			'closes_at' => $current_close,
		);
		list( , $uid, $procedure ) = $this->mio( array( ProcedureMetaKeys::QUESTIONS => array( $question ) ) );
		$before                    = get_post_meta( $procedure, ProcedureMetaKeys::QUESTIONS, true );
		foreach ( array( $this->dia( '+9 days' ), '2026-02-30' ) as $date ) {
			$this->assertNull(
				$this->submit(
					$uid,
					ProcedureEditor::OP_DELIVERY_DATES,
					$procedure,
					0,
					array(
						ProcedureEditor::FIELD_DELIVERY_CLOSES => array(
							'q1'   => $date,
							'q999' => '2027-01-01',
						),
					)
				)
			);
			$this->assertSame( $before, get_post_meta( $procedure, ProcedureMetaKeys::QUESTIONS, true ) );
		}
		$this->assertNull( $this->submit( $uid, ProcedureEditor::OP_DELIVERY_DATES, $procedure, 0, array( ProcedureEditor::FIELD_DELIVERY_CLOSES => array( 'q1' => array( '2027-01-01' ) ) ) ) );
		$this->assertSame( $before, get_post_meta( $procedure, ProcedureMetaKeys::QUESTIONS, true ) );
		$this->assertNull( $this->submit( $uid, ProcedureEditor::OP_DELIVERY_DATES, $procedure, 0, array( ProcedureEditor::FIELD_DELIVERY_CLOSES => 'not-an-array' ) ) );
		$this->assertSame( $before, get_post_meta( $procedure, ProcedureMetaKeys::QUESTIONS, true ) );
	}

	/** A submission without a delivery key cannot update unrelated questions. */
	public function test_delivery_dates_reject_submissions_without_existing_deliveries() {
		$question                  = array(
			'key'      => 'q1',
			'label'    => 'Texto',
			'type'     => ProcedureQuestions::TYPE_TEXT,
			'required' => false,
			'choices'  => array(),
		);
		list( , $uid, $procedure ) = $this->mio( array( ProcedureMetaKeys::QUESTIONS => array( $question ) ) );
		$before                    = get_post_meta( $procedure, ProcedureMetaKeys::QUESTIONS, true );

		$this->assertNull( $this->submit( $uid, ProcedureEditor::OP_DELIVERY_DATES, $procedure, 0, array( ProcedureEditor::FIELD_DELIVERY_CLOSES => array( 'q1' => $this->dia( '+30 days' ) ) ) ) );
		$this->assertSame( $before, get_post_meta( $procedure, ProcedureMetaKeys::QUESTIONS, true ) );
		$this->acting_as( $uid );
		$model = $this->modelo( $procedure, ProcedureEditor::PANEL_DELIVERIES );
		$this->assertSame( 'error', $model['flash']['tipo'] );
		$this->assertSame( 'No hay entregas documentales cuyos plazos se puedan ampliar.', $model['flash']['texto'] );
		$this->assertStringContainsString( 'Este procedimiento no tiene entregas con plazo propio.', ProcedureEditorView::html( $model ) );
	}

	// ─── tipos de pregunta y orden (ADR-0040) ──────────────────────────────

	/**
	 * Un procedimiento propio con tres preguntas: texto, rejilla y méritos con puntos.
	 *
	 * @return array{0:int, 1:int} Manager, procedure.
	 */
	private function con_preguntas(): array {
		list( , $uid, $procedimiento ) = $this->mio(
			array(
				ProcedureMetaKeys::QUESTIONS => array(
					array(
						'key'   => 'q1',
						'label' => 'Grupos',
						'type'  => ProcedureQuestions::TYPE_TEXT,
					),
					array(
						'key'   => 'q2',
						'label' => 'Alumnado',
						'type'  => ProcedureQuestions::TYPE_GRID,
						'rows'  => "Infantil\nPrimaria\nSecundaria",
					),
					array(
						'key'     => 'q3',
						'label'   => 'Méritos',
						'type'    => ProcedureQuestions::TYPE_MULTIPLE,
						'choices' => "Huerto | 3\nRadio",
					),
				),
			)
		);
		return array( $uid, $procedimiento );
	}

	/**
	 * Lo que el panel enviaría hoy, sin tocar nada, con los cambios que se digan.
	 *
	 * @param int                  $procedimiento Procedure ID.
	 * @param array<string, array> $cambios       Field suffix => values by row.
	 * @return array<string, mixed>
	 */
	private function preguntas_tal_cual( int $procedimiento, array $cambios = array() ): array {
		$q      = ProcedureEditor::FIELD_Q;
		$campos = array();
		foreach ( get_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS, true ) as $i => $p ) {
			$campos[ $q . 'key' ][ $i ]     = $p['key'];
			$campos[ $q . 'label' ][ $i ]   = $p['label'];
			$campos[ $q . 'type' ][ $i ]    = $p['type'];
			$campos[ $q . 'choices' ][ $i ] = implode( "\n", \Prc\Domain\QuestionPoints::lines( $p ) );
			$campos[ $q . 'rows' ][ $i ]    = implode( "\n", wp_list_pluck( (array) ( $p['rows'] ?? array() ), 'label' ) );
		}
		foreach ( $cambios as $sufijo => $valores ) {
			foreach ( $valores as $i => $valor ) {
				$campos[ $q . $sufijo ][ $i ] = $valor;
			}
		}
		return $campos;
	}

	/**
	 * Número, fecha y rejilla se guardan desde el taller, con su rango y sus filas.
	 */
	public function test_the_panel_saves_number_date_and_grid() {
		list( , $uid, $procedimiento ) = $this->mio();
		$q                             = ProcedureEditor::FIELD_Q;

		$url = $this->submit(
			$uid,
			ProcedureEditor::PANEL_QUESTIONS,
			$procedimiento,
			0,
			array(
				$q . 'key'     => array( '', '', '', '' ),
				$q . 'label'   => array( 'Grupos', 'Fecha del claustro', 'Alumnado', 'Méritos' ),
				$q . 'type'    => array( ProcedureQuestions::TYPE_NUMBER, ProcedureQuestions::TYPE_DATE, ProcedureQuestions::TYPE_GRID, ProcedureQuestions::TYPE_MULTIPLE ),
				$q . 'min'     => array( '1', '', '', '' ),
				$q . 'max'     => array( '12', '', '', '' ),
				$q . 'rows'    => array( '', '', "Infantil\nPrimaria", '' ),
				$q . 'choices' => array( '', '', '', "Huerto | 3\nRadio" ),
			)
		);

		$this->assertSame( 'preguntas', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
		$guardadas = get_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS, true );
		$this->assertSame( array( 1, 12 ), array( $guardadas[0]['min'], $guardadas[0]['max'] ) );
		$this->assertSame( ProcedureQuestions::TYPE_DATE, $guardadas[1]['type'] );
		$this->assertSame(
			array(
				array(
					'key'   => 'r1',
					'label' => 'Infantil',
				),
				array(
					'key'   => 'r2',
					'label' => 'Primaria',
				),
			),
			$guardadas[2]['rows']
		);
		$this->assertSame( array( 'Huerto', 'Radio' ), $guardadas[3]['choices'] );
		$this->assertSame( array( 3, 0 ), $guardadas[3]['points'] );

		// Un mínimo por encima del máximo no se guarda.
		$this->assertNull(
			$this->submit(
				$uid,
				ProcedureEditor::PANEL_QUESTIONS,
				$procedimiento,
				0,
				$this->preguntas_tal_cual(
					$procedimiento,
					array(
						'min' => array( '20' ),
						'max' => array( '12' ),
					)
				)
			)
		);
		$this->assertSame( $guardadas, get_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS, true ) );
		$this->assertStringContainsString( 'mínimo', $this->modelo( $procedimiento, ProcedureEditor::PANEL_QUESTIONS )['flash']['texto'] );
	}

	/**
	 * Con respuestas, el tipo de una pregunta no cambia; el rótulo, sí.
	 */
	public function test_the_type_of_an_answered_question_is_refused() {
		list( $uid, $procedimiento ) = $this->con_preguntas();
		$this->application( $procedimiento, $this->school_head( 'C0001' ), '', array( ApplicationMetaKeys::ANSWERS => array( 'q1' => 'tres' ) ) );
		$antes = get_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS, true );

		$this->assertNull(
			$this->submit( $uid, ProcedureEditor::PANEL_QUESTIONS, $procedimiento, 0, $this->preguntas_tal_cual( $procedimiento, array( 'type' => array( ProcedureQuestions::TYPE_NUMBER ) ) ) ),
			'no se guarda ni vuelve'
		);
		$this->assertSame( $antes, get_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS, true ) );
		$flash = $this->modelo( $procedimiento, ProcedureEditor::PANEL_QUESTIONS )['flash'];
		$this->assertSame( 'error', $flash['tipo'] );
		$this->assertStringContainsString( 'No se puede cambiar el tipo de «Grupos»', $flash['texto'] );
		$this->olvidar_rechazo();

		$url = $this->submit( $uid, ProcedureEditor::PANEL_QUESTIONS, $procedimiento, 0, $this->preguntas_tal_cual( $procedimiento, array( 'label' => array( 'Número de grupos' ) ) ) );
		$this->assertSame( 'preguntas', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ), 'el rótulo sí cambia' );

		// La de méritos no tiene respuestas: su tipo sí puede cambiar.
		$url = $this->submit( $uid, ProcedureEditor::PANEL_QUESTIONS, $procedimiento, 0, $this->preguntas_tal_cual( $procedimiento, array( 'type' => array( 2 => ProcedureQuestions::TYPE_SINGLE ) ) ) );
		$this->assertNotNull( $url );
		$this->assertSame( ProcedureQuestions::TYPE_SINGLE, get_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS, true )[2]['type'] );
	}

	/**
	 * Una fila renombrada en su línea sigue con sus cantidades; una borrada
	 * dejando su línea en blanco deja de salir y su clave no se reutiliza.
	 */
	public function test_renaming_and_removing_grid_rows_keeps_the_answers_in_place() {
		list( $uid, $procedimiento ) = $this->con_preguntas();
		$this->application(
			$procedimiento,
			$this->school_head( 'C0001' ),
			'',
			array(
				ApplicationMetaKeys::ANSWERS => array(
					'q2' => array(
						'r1' => 3,
						'r2' => 5,
						'r3' => 7,
					),
				),
			)
		);

		$this->assertNotNull(
			$this->submit( $uid, ProcedureEditor::PANEL_QUESTIONS, $procedimiento, 0, $this->preguntas_tal_cual( $procedimiento, array( 'rows' => array( 1 => "Infantil\nEducación Primaria\n" ) ) ) )
		);
		$rejilla = get_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS, true )[1];
		$this->assertSame( array( 'r1', 'r2' ), wp_list_pluck( $rejilla['rows'], 'key' ) );
		$this->assertSame( 3, $rejilla['row_seq'] );

		$fila = \Prc\PublicFront\Applications::rows( $procedimiento )[0];
		$this->assertSame( 'Infantil: 3; Educación Primaria: 5', $fila['q2'], 'la renombrada sigue con lo suyo, y la borrada no sale' );

		// Una fila nueva no hereda las cantidades de la borrada.
		$this->assertNotNull(
			$this->submit( $uid, ProcedureEditor::PANEL_QUESTIONS, $procedimiento, 0, $this->preguntas_tal_cual( $procedimiento, array( 'rows' => array( 1 => "Infantil\nEducación Primaria\nBachillerato" ) ) ) )
		);
		$rejilla = get_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS, true )[1];
		$this->assertSame( array( 'r1', 'r2', 'r4' ), wp_list_pluck( $rejilla['rows'], 'key' ) );
		$this->assertSame( 'Infantil: 3; Educación Primaria: 5', \Prc\PublicFront\Applications::rows( $procedimiento )[0]['q2'] );
	}

	/**
	 * Subir y bajar mueven la pregunta y nada más: la clave va con ella y las
	 * respuestas no se tocan. En los extremos no hacen nada, y sin error.
	 */
	public function test_moving_a_question_keeps_keys_and_answers() {
		list( $uid, $procedimiento ) = $this->con_preguntas();
		$solicitud                   = $this->application( $procedimiento, $this->school_head( 'C0001' ), '', array( ApplicationMetaKeys::ANSWERS => array( 'q1' => 'tres' ) ) );
		$respuestas                  = get_post_meta( $solicitud, ApplicationMetaKeys::ANSWERS, true );
		$mover                       = function ( string $pedido ) use ( $uid, $procedimiento ): ?string {
			return $this->submit( $uid, ProcedureEditor::OP_MOVE_QUESTION, $procedimiento, 0, array( ProcedureEditor::FIELD_Q_MOVE => $pedido ) );
		};
		$claves                      = function () use ( $procedimiento ): array {
			return wp_list_pluck( get_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS, true ), 'key' );
		};

		$url = $mover( 'q2:' . \Prc\Domain\QuestionChanges::MOVE_UP );
		$this->assertSame( 'ordenadas', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
		$this->assertSame( array( 'q2', 'q1', 'q3' ), $claves() );

		$this->assertNotNull( $mover( 'q1:' . \Prc\Domain\QuestionChanges::MOVE_DOWN ) );
		$this->assertSame( array( 'q2', 'q3', 'q1' ), $claves() );

		$url = $mover( 'q2:' . \Prc\Domain\QuestionChanges::MOVE_UP );
		$this->assertNotNull( $url, 'la primera hacia arriba vuelve sin error' );
		$this->assertSame( '', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
		$this->assertNotNull( $mover( 'q1:' . \Prc\Domain\QuestionChanges::MOVE_DOWN ), 'y la última hacia abajo, igual' );
		$this->assertNotNull( $mover( 'basura' ) );
		$this->assertSame( array( 'q2', 'q3', 'q1' ), $claves() );

		$this->assertSame( $respuestas, get_post_meta( $solicitud, ApplicationMetaKeys::ANSWERS, true ), 'las respuestas no se tocan' );
		$this->assertSame( 'tres', \Prc\PublicFront\Applications::rows( $procedimiento )[0]['q1'] );

		// Sin el nonce de mover, o con el estado cerrado, no se mueve.
		$this->assertNull( $this->submit( $uid, ProcedureEditor::OP_MOVE_QUESTION, $procedimiento, 0, array( ProcedureEditor::FIELD_Q_MOVE => 'q1:up' ), 'falso' ) );
		$this->assertSame( array( 'q2', 'q3', 'q1' ), $claves() );
		$ajena = $this->manager( array( $this->area( 'Otro ámbito' ) ) );
		$this->assertNull( $this->submit( $ajena, ProcedureEditor::OP_MOVE_QUESTION, $procedimiento, 0, array( ProcedureEditor::FIELD_Q_MOVE => 'q1:up' ) ) );
		$this->assertSame( array( 'q2', 'q3', 'q1' ), $claves() );
	}

	/**
	 * Con las preguntas cerradas por el estado, tampoco se reordenan.
	 */
	public function test_a_locked_question_list_is_not_reordered() {
		list( , $uid, $procedimiento ) = $this->en_estado( ProcedureMetaKeys::STATE_AMENDMENT );
		update_post_meta(
			$procedimiento,
			ProcedureMetaKeys::QUESTIONS,
			array(
				array( 'label' => 'Una' ),
				array( 'label' => 'Dos' ),
			)
		);

		$this->assertNull( $this->submit( $uid, ProcedureEditor::OP_MOVE_QUESTION, $procedimiento, 0, array( ProcedureEditor::FIELD_Q_MOVE => 'q2:up' ) ) );
		$this->assertSame( array( 'q1', 'q2' ), wp_list_pluck( get_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS, true ), 'key' ) );
	}

	/**
	 * El panel pinta el rango, las filas, los puntos y los botones de mover,
	 * que envían su propio formulario; la tabla de solicitudes, los puntos.
	 */
	public function test_the_panels_paint_the_new_fields() {
		list( , $procedimiento ) = $this->con_preguntas();
		$this->acting_as( $this->administrator() );

		$html = ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_QUESTIONS ) );
		$this->assertStringContainsString( 'name="' . ProcedureEditor::FIELD_Q . 'min[0]"', $html );
		$this->assertStringContainsString( 'name="' . ProcedureEditor::FIELD_Q . 'max[0]"', $html );
		$this->assertStringContainsString( "Infantil\nPrimaria\nSecundaria</textarea>", $html );
		$this->assertStringContainsString( "Huerto | 3\nRadio | 0</textarea>", $html );
		$this->assertStringContainsString( 'id="prc-q-mover"', $html );
		$this->assertStringContainsString( 'value="' . ProcedureEditor::OP_MOVE_QUESTION . '"', $html );
		$this->assertStringContainsString( 'form="prc-q-mover" name="' . ProcedureEditor::FIELD_Q_MOVE . '" value="q2:up"', $html );
		$this->assertMatchesRegularExpression( '/value="q1:up"[^>]*disabled/', $html, 'la primera no sube' );
		$this->assertMatchesRegularExpression( '/value="q3:down"[^>]*disabled/', $html, 'la última no baja' );

		$this->application( $procedimiento, $this->school_head( 'C0001' ), '', array( ApplicationMetaKeys::ANSWERS => array( 'q3' => array( 'Huerto' ) ) ) );
		$html = ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_APPLICATIONS ) );
		$this->assertStringContainsString( '>Puntos</th>', $html );
		$this->assertStringContainsString( 'data-rotulo="Puntos">3</td>', $html );

		list( , , $sin ) = $this->mio();
		$this->application( $sin, $this->school_head( 'C0002' ) );
		$this->assertStringNotContainsString( 'Puntos', ProcedureEditorView::html( $this->modelo( $sin, ProcedureEditor::PANEL_APPLICATIONS ) ) );
	}

	// ─── correcciones de la revisión ───────────────────────────────────────

	/**
	 * Borrar la última pregunta, contestada, y añadir otra de otro tipo en el
	 * mismo envío no es un cambio de tipo; y la nueva no hereda su clave ni
	 * sus respuestas, tampoco en un envío posterior.
	 */
	public function test_a_deleted_last_question_never_gives_its_key_away() {
		list( $uid, $procedimiento ) = $this->con_preguntas();
		$this->application( $procedimiento, $this->school_head( 'C0001' ), '', array( ApplicationMetaKeys::ANSWERS => array( 'q3' => array( 'Huerto' ) ) ) );

		$campos = $this->preguntas_tal_cual(
			$procedimiento,
			array(
				'key'   => array( 3 => '' ),
				'label' => array(
					2 => '',
					3 => 'Grupos nuevos',
				),
				'type'  => array( 3 => ProcedureQuestions::TYPE_NUMBER ),
			)
		);
		$url    = $this->submit( $uid, ProcedureEditor::PANEL_QUESTIONS, $procedimiento, 0, $campos );
		$this->assertSame( 'preguntas', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ), 'no es un cambio de tipo' );
		$this->assertSame( array( 'q1', 'q2', 'q4' ), wp_list_pluck( get_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS, true ), 'key' ) );
		$this->assertSame( 4, (int) get_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS_SEQ, true ) );

		// Se borra la nueva, que era la última, y se crea otra: tampoco hereda.
		$campos = $this->preguntas_tal_cual(
			$procedimiento,
			array(
				'label' => array( 2 => '' ),
			)
		);
		$this->submit( $uid, ProcedureEditor::PANEL_QUESTIONS, $procedimiento, 0, $campos );
		$campos = $this->preguntas_tal_cual(
			$procedimiento,
			array(
				'key'   => array( 2 => '' ),
				'label' => array( 2 => 'Otra' ),
				'type'  => array( 2 => ProcedureQuestions::TYPE_TEXT ),
			)
		);
		$this->submit( $uid, ProcedureEditor::PANEL_QUESTIONS, $procedimiento, 0, $campos );
		$this->assertSame( array( 'q1', 'q2', 'q5' ), wp_list_pluck( get_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS, true ), 'key' ) );
		$this->assertSame( '', \Prc\PublicFront\Applications::rows( $procedimiento )[0]['q5'], 'ninguna respuesta heredada' );
	}

	/**
	 * Un procedimiento de antes, sin contador, empieza por la mayor clave
	 * entre sus preguntas y las respuestas guardadas.
	 */
	public function test_an_old_procedure_starts_the_counter_from_its_answers() {
		list( $uid, $procedimiento ) = $this->con_preguntas();
		delete_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS_SEQ );
		$this->application( $procedimiento, $this->school_head( 'C0001' ), '', array( ApplicationMetaKeys::ANSWERS => array( 'q7' => 'de una pregunta borrada' ) ) );

		$this->submit(
			$uid,
			ProcedureEditor::PANEL_QUESTIONS,
			$procedimiento,
			0,
			$this->preguntas_tal_cual(
				$procedimiento,
				array(
					'key'   => array( 3 => '' ),
					'label' => array( 3 => 'Nueva' ),
					'type'  => array( 3 => ProcedureQuestions::TYPE_TEXT ),
				)
			)
		);
		$this->assertSame( 'q8', get_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS, true )[3]['key'] );
		$this->assertSame( 8, (int) get_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS_SEQ, true ) );
	}

	/**
	 * Unos puntos o un rango mal escritos no se guardan: se avisa.
	 */
	public function test_badly_written_points_or_range_are_refused() {
		list( $uid, $procedimiento ) = $this->con_preguntas();
		$antes                       = get_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS, true );

		$this->assertNull( $this->submit( $uid, ProcedureEditor::PANEL_QUESTIONS, $procedimiento, 0, $this->preguntas_tal_cual( $procedimiento, array( 'choices' => array( 2 => "Huerto | 3\nRadio | -2" ) ) ) ) );
		$this->assertStringContainsString( '«Radio | -2»', $this->modelo( $procedimiento, ProcedureEditor::PANEL_QUESTIONS )['flash']['texto'] );
		$this->olvidar_rechazo();

		$campos = $this->preguntas_tal_cual(
			$procedimiento,
			array(
				'type' => array( ProcedureQuestions::TYPE_NUMBER ),
				'max'  => array( '1,5' ),
			)
		);
		$this->assertNull( $this->submit( $uid, ProcedureEditor::PANEL_QUESTIONS, $procedimiento, 0, $campos ) );
		$this->assertStringContainsString( 'máximo', $this->modelo( $procedimiento, ProcedureEditor::PANEL_QUESTIONS )['flash']['texto'] );
		$this->assertSame( $antes, get_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS, true ) );
	}

	/**
	 * Una rejilla con todas las filas vacías no está contestada: su tipo cambia.
	 */
	public function test_an_empty_grid_is_not_an_answer() {
		list( $uid, $procedimiento ) = $this->con_preguntas();
		$this->application(
			$procedimiento,
			$this->school_head( 'C0001' ),
			'',
			array(
				ApplicationMetaKeys::ANSWERS => array(
					'q2' => array(
						'r1' => '',
						'r2' => '',
						'r3' => '',
					),
				),
			)
		);
		$this->assertArrayNotHasKey( 'q2', \Prc\PublicFront\Applications::answered_keys( $procedimiento ) );
		$this->assertNotNull( $this->submit( $uid, ProcedureEditor::PANEL_QUESTIONS, $procedimiento, 0, $this->preguntas_tal_cual( $procedimiento, array( 'type' => array( 1 => ProcedureQuestions::TYPE_TEXT ) ) ) ) );
	}
}
