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
use Prc\PublicFront\AdvancedPanel;
use Prc\PublicFront\ApplicationActionsPanel;
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
		// Guardar «Enlaces» descarga una copia del PDF (ADR-0048): aquí nunca
		// sale a la red; la copia se prueba en test-link-copies.php.
		add_filter( 'pre_http_request', array( $this, 'sin_red' ) );
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
				ProcedureEditor::PANEL_PUBLISH,
				ProcedureEditor::PANEL_ADVANCED,
				ProcedureEditor::PANEL_APPLICATIONS,
			),
			array_keys( $m['panels'] ),
			'en el orden en que se trabaja, sin grupos: lo de preparar, «Avanzado» y las solicitudes'
		);
		$this->assertSame( 'Avanzado', $m['panels'][ ProcedureEditor::PANEL_ADVANCED ]['label'] );
		$this->assertArrayNotHasKey( 'group', $m['panels'][ ProcedureEditor::PANEL_DATA ] );

		$html = ProcedureEditorView::html( $m );
		$this->assertStringNotContainsString( 'prc-tabs-grupo', $html, 'ni grupos ni rótulos de grupo' );
		$this->assertStringNotContainsString( 'role="group"', $html );
		$this->assertStringContainsString( '<span class="prc-tab-n">1</span>', $html, 'el contador sin la clase de Bootstrap que lo pinta en blanco' );
		$this->assertSame( ProcedureEditor::PANEL_DATA, $m['panel'], 'por defecto, los datos' );
		$this->assertSame( 1, $m['panels'][ ProcedureEditor::PANEL_QUESTIONS ]['count'] );
		$this->assertSame( 1, $m['panels'][ ProcedureEditor::PANEL_APPLICATIONS ]['count'] );
		$this->assertNull( $m['panels'][ ProcedureEditor::PANEL_ADVANCED ]['count'] );
		$this->assertArrayNotHasKey( 'enlaces', $m['panels'], '«Enlaces» es una sección de «Datos»' );
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

	/** The editing forms warn about unsaved changes; the action panels do not. */
	public function test_editing_panels_guard_unsaved_changes() {
		list( , $uid, $procedimiento ) = $this->mio();
		$this->acting_as( $uid );

		$paneles = array(
			ProcedureEditor::PANEL_DATA,
			ProcedureEditor::PANEL_QUESTIONS,
			ProcedureEditor::PANEL_PEOPLE,
			ProcedureEditor::PANEL_ADVANCED,
		);
		foreach ( $paneles as $panel ) {
			$html = ProcedureEditorView::html( $this->modelo( $procedimiento, $panel ) );
			$this->assertMatchesRegularExpression( '/<form class="prc-form prc-form--taller"[^>]*data-prc-dirty-guard>/', $html, $panel );
		}
		// En «Avanzado», cada sección con su formulario: los centros y el
		// código y la firma para quien gestiona; administración, además, la consulta.
		$this->assertSame( 3, preg_match_all( '/<form class="prc-form prc-form--taller"[^>]*data-prc-dirty-guard>/', $html ) );
		$this->acting_as( $this->administrator() );
		$m = $this->modelo( $procedimiento, ProcedureEditor::PANEL_ADVANCED );
		$this->assertSame( 4, preg_match_all( '/<form class="prc-form prc-form--taller"[^>]*data-prc-dirty-guard>/', ProcedureEditorView::html( $m ) ) );
		$this->acting_as( $uid );
		$html = ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_PUBLISH ) );
		$this->assertStringNotContainsString( 'data-prc-dirty-guard', $html, 'publicar es un botón, no un formulario que se rellena' );
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
		$html = ProcedureEditorView::html( $this->modelo( $procedure, ProcedureEditor::PANEL_ADVANCED ) );
		$this->assertStringContainsString( 'name="prc_custom_css"', $html );
		$this->assertStringNotContainsString( 'name="prc_custom_js"', $html );
		$this->assertStringNotContainsString( 'prc-custom-js', $html );
		$this->assertStringContainsString( '>Guardar el código</button>', $html, 'guardar y qué' );
	}

	/** Administrators see the executable editor and its yellow public-impact warning. */
	public function test_administrators_see_the_javascript_warning_and_editor() {
		list( , , $procedure ) = $this->mio();
		$this->acting_as( $this->administrator() );
		$html = ProcedureEditorView::html( $this->modelo( $procedure, ProcedureEditor::PANEL_ADVANCED ) );
		$this->assertStringContainsString( 'name="prc_custom_js"', $html );
		$this->assertStringContainsString( 'class="prc-aviso prc-aviso-warning prc-codigo-admin"', $html );
		$this->assertStringContainsString( 'Solo para administración', $html );
	}

	/** The CSS manager gets no JavaScript editor assets or initializer. */
	public function test_code_editor_enqueues_only_the_css_field_for_managers() {
		list( , $uid, $procedure ) = $this->mio();
		update_user_option( $uid, 'syntax_highlighting', 'true', true );
		$this->acting_as( $uid );
		$this->go_to( ProcedureEditor::url( $procedure, ProcedureEditor::PANEL_ADVANCED ) );

		AdvancedPanel::enqueue_code_editor();
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

		$url = ProcedureEditor::url( 12, ProcedureEditor::PANEL_QUESTIONS );
		$this->assertStringContainsString( 'procedimiento=12', $url );
		$this->assertStringContainsString( 'panel=' . ProcedureEditor::PANEL_QUESTIONS, $url );

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
	 * Con profesorado, la coordinación sale escondida; los compromisos
	 * siguen, porque también se le piden a cada docente. Qué centros pueden
	 * solicitar ya no está en «Datos», sino en «Avanzado» (#80).
	 */
	public function test_teachers_hide_coordination_and_the_centre_section() {
		list( , $uid, $docentes ) = $this->mio( array( ProcedureMetaKeys::AUDIENCE => ProcedureMetaKeys::AUDIENCE_TEACHERS ) );
		$this->acting_as( $uid );
		$html = ProcedureEditorView::html( $this->modelo( $docentes, ProcedureEditor::PANEL_DATA ) );

		$this->assertMatchesRegularExpression( '#<fieldset class="prc-campo" data-prc-schools-only hidden>\s*<legend class="prc-campo__etiqueta">¿Pide la solicitud personas coordinadoras\?#u', $html );
		$this->assertDoesNotMatchRegularExpression( '#data-prc-schools-only hidden>\s*<div class="prc-campo">\s*<label class="prc-campo__etiqueta" for="prc-commitments"#u', $html );
		$this->assertStringNotContainsString( 'Qué centros pueden solicitar', $html );
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
	 * Lo que manda el formulario de «Qué centros pueden solicitar», como el
	 * navegador: la lista cerrada viaja siempre, y los interruptores solo
	 * encendidos.
	 *
	 * @param array<string, mixed> $cambios What to add or override.
	 * @return array<string, mixed>
	 */
	private function centros( array $cambios = array() ): array {
		return array_merge( array( ProcedureMetaKeys::ALLOWED_CENTRES => '' ), $cambios );
	}

	/**
	 * «Qué centros pueden solicitar» vive en «Avanzado», con su formulario:
	 * las casillas salen del catálogo, se guardan, y una lista mal escrita no
	 * guarda nada y repinta lo tecleado. «Datos» ya no lo pinta.
	 */
	public function test_the_advanced_panel_narrows_which_centres_may_apply() {
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
		list( , $uid, $procedimiento ) = $this->mio( array( ProcedureMetaKeys::ISLANDS => array( 'Isla Perdida' ) ) );

		$this->acting_as( $uid );
		$datos = ProcedureEditorView::html( $this->modelo( $procedimiento ) );
		$this->assertStringNotContainsString( 'Qué centros pueden solicitar', $datos, 'ya no está en «Datos»' );
		$this->assertStringNotContainsString( 'name="' . ProcedureMetaKeys::ALLOWED_CENTRES . '"', $datos );

		$m    = $this->modelo( $procedimiento, ProcedureEditor::PANEL_ADVANCED );
		$html = ProcedureEditorView::html( $m );
		$this->assertSame( array( 'CEIP', 'IES' ), $m['centre_options']['types'] );
		$this->assertSame( array( 'Isla Dos', 'Isla Perdida', 'Isla Uno' ), $m['centre_options']['islands'], 'lo elegido se ofrece aunque el catálogo ya no lo traiga' );
		$this->assertStringContainsString( 'id="' . ProcedureEditor::SECTION_CENTRES . '"', $html );
		$this->assertStringContainsString( '>Qué centros pueden solicitar</h2>', $html );
		$this->assertStringContainsString( 'value="' . ProcedureEditor::OP_CENTRES . '"', $html );
		$this->assertStringContainsString( 'name="' . ProcedureMetaKeys::CENTRE_TYPES . '[]" value="IES"', $html );
		$this->assertStringContainsString( '>Guardar qué centros pueden solicitar</button>', $html );
		$this->assertStringNotContainsString( '<details class="prc-acotar"', $html, 'en «Avanzado» ya no se pliega' );
		$this->assertStringContainsString( 'data-prc-tick-all="1">Marcar todos</button>', $html, 'los tipos, en masculino' );
		$this->assertStringContainsString( 'data-prc-tick-all="0">Ninguna</button>', $html, 'las islas, en femenino' );

		$url = $this->submit(
			$uid,
			ProcedureEditor::OP_CENTRES,
			$procedimiento,
			0,
			$this->centros(
				array(
					ProcedureEditor::FIELD_LIMIT_TYPES   => '1',
					ProcedureMetaKeys::CENTRE_TYPES      => array( 'CEIP' ),
					ProcedureEditor::FIELD_LIMIT_ISLANDS => '1',
					ProcedureMetaKeys::ISLANDS           => array( 'Isla Uno', 'Isla Uno' ),
					ProcedureMetaKeys::ALLOWED_CENTRES   => " C0001,\nC0002\n\nC0001 ",
				)
			)
		);
		$this->assertSame( 'centros', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
		$this->assertSame( ProcedureEditor::PANEL_ADVANCED, $this->query_arg( (string) $url, ProcedureEditor::ARG_PANEL ), 'vuelve a «Avanzado»' );
		$this->assertSame( array( 'CEIP' ), get_post_meta( $procedimiento, ProcedureMetaKeys::CENTRE_TYPES, true ) );
		$this->assertSame( array( 'Isla Uno' ), get_post_meta( $procedimiento, ProcedureMetaKeys::ISLANDS, true ) );
		$this->assertSame( array( 'C0001', 'C0002' ), get_post_meta( $procedimiento, ProcedureMetaKeys::ALLOWED_CENTRES, true ) );
		$this->acting_as( $uid );
		$this->assertSame( "C0001\nC0002", $this->modelo( $procedimiento )['values'][ ProcedureMetaKeys::ALLOWED_CENTRES ] );

		$this->olvidar_rechazo();
		$this->assertNull(
			$this->submit( $uid, ProcedureEditor::OP_CENTRES, $procedimiento, 0, $this->centros( array( ProcedureMetaKeys::ALLOWED_CENTRES => 'C0001, mal-escrito' ) ) )
		);
		$this->assertSame( array( 'C0001', 'C0002' ), get_post_meta( $procedimiento, ProcedureMetaKeys::ALLOWED_CENTRES, true ), 'no se guarda nada' );
		$m = $this->modelo( $procedimiento, ProcedureEditor::PANEL_ADVANCED );
		$this->assertStringContainsString( 'lista cerrada de centros', $m['flash']['texto'] );
		$this->assertSame( 'C0001, mal-escrito', $m['values'][ ProcedureMetaKeys::ALLOWED_CENTRES ], 'se repinta lo tecleado' );

		// Sin marcar nada se quita el acotado: vuelve a ser para todos.
		$this->olvidar_rechazo();
		$this->assertNotNull( $this->submit( $uid, ProcedureEditor::OP_CENTRES, $procedimiento, 0, $this->centros() ) );
		$this->assertSame( array(), get_post_meta( $procedimiento, ProcedureMetaKeys::CENTRE_TYPES, true ) );
		$this->assertSame( array(), get_post_meta( $procedimiento, ProcedureMetaKeys::ALLOWED_CENTRES, true ) );
	}

	/**
	 * Un interruptor por lista: apagado no acota aunque las casillas vayan
	 * marcadas; encendido guarda exactamente lo marcado, también si es todo
	 * el catálogo; encendido sin nada se rechaza y se repinta como se envió
	 * (#84). El aviso del catálogo vacío, a la vista en la sección.
	 */
	public function test_the_limit_switches_decide_the_centre_lists() {
		list( , $uid, $procedimiento ) = $this->mio( array( ProcedureMetaKeys::OWNERSHIP => array( ProcedureMetaKeys::OWNERSHIP_PUBLIC ) ) );
		$this->acting_as( $uid );
		$this->assertStringContainsString( 'El catálogo de centros está vacío', ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_ADVANCED ) ) );

		add_filter( 'prc_centres', array( $this, 'two_centres' ) );
		\Prc\Domain\CentreCatalog::flush();
		$html = ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_ADVANCED ) );
		$this->assertMatchesRegularExpression( '/name="' . ProcedureEditor::FIELD_LIMIT_TYPES . '" value="1"\s*\/>/', $html, 'el interruptor, apagado' );
		$this->assertStringContainsString( 'Limitar por tipo de centro', $html );
		$this->assertStringContainsString( 'Limitar por isla', $html );
		$ticked = $this->ticked( $html );
		$this->assertSame( array( 'CEIP', 'IES' ), $ticked[ ProcedureMetaKeys::CENTRE_TYPES ], 'al encenderlo, todos marcados' );

		// Apagado, las casillas marcadas no acotan.
		$this->assertNotNull( $this->submit( $uid, ProcedureEditor::OP_CENTRES, $procedimiento, 0, $this->centros( $ticked ) ) );
		$this->assertSame( array(), get_post_meta( $procedimiento, ProcedureMetaKeys::CENTRE_TYPES, true ) );

		// Encendido con todo el catálogo: se guarda tal cual, no se deduce nada.
		$on = array( ProcedureEditor::FIELD_LIMIT_TYPES => '1' ) + $ticked;
		$this->assertNotNull( $this->submit( $uid, ProcedureEditor::OP_CENTRES, $procedimiento, 0, $this->centros( $on ) ) );
		$this->assertSame( array( 'CEIP', 'IES' ), get_post_meta( $procedimiento, ProcedureMetaKeys::CENTRE_TYPES, true ) );
		$this->acting_as( $uid );
		$html = ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_ADVANCED ) );
		$this->assertMatchesRegularExpression( '/name="' . ProcedureEditor::FIELD_LIMIT_TYPES . '" value="1"\s*checked=/', $html );
		$this->assertMatchesRegularExpression( '/name="' . ProcedureEditor::FIELD_LIMIT_ISLANDS . '" value="1"\s*\/>/', $html, 'las islas, sin límite' );

		// Encendido y sin nada: se rechaza, no se guarda y se repinta encendido.
		$this->assertNull( $this->submit( $uid, ProcedureEditor::OP_CENTRES, $procedimiento, 0, $this->centros( array( ProcedureEditor::FIELD_LIMIT_ISLANDS => '1' ) ) ) );
		$this->assertSame( array( 'CEIP', 'IES' ), get_post_meta( $procedimiento, ProcedureMetaKeys::CENTRE_TYPES, true ), 'no se guarda nada' );
		$this->assertSame( array(), get_post_meta( $procedimiento, ProcedureMetaKeys::ISLANDS, true ) );
		$m    = $this->modelo( $procedimiento, ProcedureEditor::PANEL_ADVANCED );
		$html = ProcedureEditorView::html( $m );
		$this->assertStringContainsString( 'marque al menos una isla', $m['flash']['texto'] );
		$this->assertMatchesRegularExpression( '/name="' . ProcedureEditor::FIELD_LIMIT_ISLANDS . '" value="1"\s*checked=/', $html, 'como se envió' );
		$this->assertMatchesRegularExpression( '/name="' . ProcedureEditor::FIELD_LIMIT_TYPES . '" value="1"\s*\/>/', $html, 'el de tipos, como se envió: apagado' );
	}

	/**
	 * Guardar «Datos», que ya no lleva los límites, no los toca; y si pasa a
	 * ser de profesorado, se quitan, como antes.
	 */
	public function test_saving_the_data_panel_keeps_the_centre_limits() {
		list( $area, $uid, $procedimiento ) = $this->mio(
			array(
				ProcedureMetaKeys::CENTRE_TYPES    => array( 'CEIP' ),
				ProcedureMetaKeys::ISLANDS         => array( 'Isla Uno' ),
				ProcedureMetaKeys::ALLOWED_CENTRES => array( 'C0001' ),
			)
		);
		$curso                              = $this->course( '2027-2028' );

		$this->assertNotNull( $this->submit( $uid, ProcedureEditor::PANEL_DATA, $procedimiento, 0, $this->datos( $area, $curso ) ) );
		$this->assertSame( array( 'CEIP' ), get_post_meta( $procedimiento, ProcedureMetaKeys::CENTRE_TYPES, true ) );
		$this->assertSame( array( 'Isla Uno' ), get_post_meta( $procedimiento, ProcedureMetaKeys::ISLANDS, true ) );
		$this->assertSame( array( 'C0001' ), get_post_meta( $procedimiento, ProcedureMetaKeys::ALLOWED_CENTRES, true ) );

		$this->assertNotNull( $this->submit( $uid, ProcedureEditor::PANEL_DATA, $procedimiento, 0, $this->datos( $area, $curso, array( ProcedureMetaKeys::AUDIENCE => ProcedureMetaKeys::AUDIENCE_TEACHERS ) ) ) );
		$this->assertSame( array(), get_post_meta( $procedimiento, ProcedureMetaKeys::CENTRE_TYPES, true ), 'el profesorado no acota centros' );
	}

	/**
	 * Los límites se guardan en cualquier estado hasta histórico (ADR-0050),
	 * y quien no edita el procedimiento no los cambia.
	 */
	public function test_the_centre_limits_save_until_archived_and_only_for_whoever_edits() {
		list( , $uid, $procedimiento ) = $this->en_estado( ProcedureMetaKeys::STATE_CLOSED );
		$this->assertNotNull( $this->submit( $uid, ProcedureEditor::OP_CENTRES, $procedimiento, 0, $this->centros( array( ProcedureMetaKeys::ALLOWED_CENTRES => 'C0001' ) ) ) );
		$this->assertSame( array( 'C0001' ), (array) get_post_meta( $procedimiento, ProcedureMetaKeys::ALLOWED_CENTRES, true ), 'cerrado el plazo, se guarda igual' );
		$this->acting_as( $uid );
		$html = ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_ADVANCED ) );
		$this->assertStringNotContainsString( 'prc-seccion__bloqueo', $html );
		$this->assertMatchesRegularExpression( '/Qué centros pueden solicitar<\/h2>.*?<fieldset class="prc-seccion-campos">/s', $html, 'encendido' );

		$this->olvidar_rechazo();
		$ajeno = $this->procedure( $this->administrator(), array( $this->area( 'Otro ámbito' ) ) );
		$this->assertNull( $this->submit( $uid, ProcedureEditor::OP_CENTRES, $ajeno, 0, $this->centros( array( ProcedureMetaKeys::ALLOWED_CENTRES => 'C0001' ) ) ) );
		$this->assertSame( array(), (array) get_post_meta( $ajeno, ProcedureMetaKeys::ALLOWED_CENTRES, true ), 'de otro ámbito, no' );
	}

	/**
	 * Un procedimiento de profesorado no acota centros: «Avanzado» no pinta
	 * la sección y lo dice.
	 */
	public function test_a_teachers_procedure_has_no_centre_limits() {
		list( , $uid, $procedimiento ) = $this->mio( array( ProcedureMetaKeys::AUDIENCE => ProcedureMetaKeys::AUDIENCE_TEACHERS ) );
		$this->acting_as( $uid );
		$this->assertSame( array( 'signature', 'code' ), AdvancedPanel::sections( $procedimiento ) );
		$html = ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_ADVANCED ) );
		$this->assertStringNotContainsString( 'value="' . ProcedureEditor::OP_CENTRES . '"', $html );
	}

	/**
	 * Las secciones de «Avanzado» según quién mira: «Acceso de consulta»
	 * solo para administración; los centros y el código, para quien abre el
	 * taller. Cada una con su ancla.
	 */
	public function test_the_advanced_sections_follow_who_is_looking() {
		list( , $uid, $procedimiento ) = $this->mio();
		$this->acting_as( $uid );
		$this->assertSame( array( 'centres', 'signature', 'code' ), AdvancedPanel::sections( $procedimiento ) );
		$html = ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_ADVANCED ) );
		$this->assertStringContainsString( 'id="' . ProcedureEditor::SECTION_CODE . '"', $html );
		$this->assertStringNotContainsString( 'id="' . ProcedureEditor::SECTION_OBSERVE . '"', $html );
		$this->assertStringNotContainsString( 'value="' . ProcedureEditor::OP_OBSERVE . '"', $html );

		$this->acting_as( $this->administrator() );
		$this->assertSame( array( 'centres', 'signature', 'observe', 'code' ), AdvancedPanel::sections( $procedimiento ) );
		$html = ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_ADVANCED ) );
		$this->assertStringContainsString( 'id="' . ProcedureEditor::SECTION_OBSERVE . '"', $html );
		$this->assertStringContainsString( '>Acceso de consulta</h2>', $html );
		$this->assertLessThan( strpos( $html, 'id="' . ProcedureEditor::SECTION_CODE . '"' ), strpos( $html, 'id="' . ProcedureEditor::SECTION_OBSERVE . '"' ), 'en el orden de las pestañas que sustituyen' );
	}

	/**
	 * Las pestañas que ya no existen llevan a donde está ahora lo suyo, con
	 * el resto de la dirección: un enlace guardado o un correo viejo no
	 * rompen.
	 */
	public function test_the_old_panels_redirect_to_where_they_live_now() {
		list( , $uid, $procedimiento ) = $this->mio();
		$this->acting_as( $uid );
		$casos = array(
			'entregas' => array( ProcedureEditor::PANEL_QUESTIONS, '' ),
			'enlaces'  => array( ProcedureEditor::PANEL_DATA, ProcedureEditor::SECTION_LINKS ),
			'consulta' => array( ProcedureEditor::PANEL_ADVANCED, ProcedureEditor::SECTION_OBSERVE ),
			'codigo'   => array( ProcedureEditor::PANEL_ADVANCED, ProcedureEditor::SECTION_CODE ),
		);
		foreach ( $casos as $viejo => $nuevo ) {
			$this->go_to( ProcedureEditor::url( $procedimiento, $viejo, array( ProcedureEditor::ARG_NOTICE => 'datos' ) ) );
			$url = $this->exit_url( array( AdvancedPanel::class, 'redirect_old_panel' ) );
			$this->assertNotNull( $url, $viejo );
			$this->assertSame( $nuevo[0], $this->query_arg( (string) $url, ProcedureEditor::ARG_PANEL ), $viejo );
			$this->assertSame( (string) $procedimiento, $this->query_arg( (string) $url, ProcedureEditor::ARG_PROCEDURE ), $viejo );
			$this->assertSame( 'datos', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ), 'el resto de la dirección se queda' );
			if ( '' === $nuevo[1] ) {
				$this->assertStringNotContainsString( '#prc-entregas', (string) $url, 'sin sección propia: ' . $viejo );
			} else {
				$this->assertStringEndsWith( '#' . $nuevo[1], (string) $url, $viejo );
			}
		}

		// Un «&» dentro de un valor no cuela otro parámetro.
		$this->go_to( ProcedureEditor::url( $procedimiento, 'codigo', array( 'x' => rawurlencode( 'a&aviso=publicado' ) ) ) );
		$url = (string) $this->exit_url( array( AdvancedPanel::class, 'redirect_old_panel' ) );
		$this->assertSame( '', $this->query_arg( $url, ProcedureEditor::ARG_NOTICE ), $url );

		// Un envío rechazado desde una pestaña vieja se repinta, no se redirige.
		$_POST = array( ProcedureEditor::FIELD_DO => ProcedureEditor::OP_CUSTOM_CODE );
		$this->assertNull( $this->exit_url( array( AdvancedPanel::class, 'redirect_old_panel' ) ) );
		$_POST = array();
		$this->go_to( ProcedureEditor::url( $procedimiento, ProcedureEditor::PANEL_QUESTIONS ) );
		$this->assertNull( $this->exit_url( array( AdvancedPanel::class, 'redirect_old_panel' ) ), 'una pestaña que existe no se toca' );
	}

	/**
	 * Guardar «Datos» no toca un límite que cubre todo el catálogo: ya no se
	 * deduce «sin límite» (#84).
	 */
	public function test_a_closed_data_group_keeps_a_limit_that_covers_the_catalogue() {
		add_filter( 'prc_centres', array( $this, 'two_centres' ) );
		list( $area, $uid, $procedimiento ) = $this->en_estado( ProcedureMetaKeys::STATE_CLOSED );
		update_post_meta( $procedimiento, ProcedureMetaKeys::CENTRE_TYPES, array( 'CEIP', 'IES' ) );

		$url = $this->submit(
			$uid,
			ProcedureEditor::PANEL_DATA,
			$procedimiento,
			0,
			$this->datos(
				$area,
				$this->course( '2099-2100' ),
				array(
					ProcedureMetaKeys::OPENS_AT  => '2026-01-07',
					ProcedureMetaKeys::CLOSES_AT => '2026-01-20',
				)
			)
		);
		$this->assertSame( 'datos', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
		$this->assertSame( array( 'CEIP', 'IES' ), get_post_meta( $procedimiento, ProcedureMetaKeys::CENTRE_TYPES, true ) );
	}

	/**
	 * A catalogue of two invented centres, of two types and two islands.
	 *
	 * @return array<int, array<string, string>>
	 */
	public function two_centres(): array {
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

	/**
	 * The options ticked in the painted form, as the browser would send them.
	 *
	 * @param string $html Painted workshop.
	 * @return array<string, string[]>
	 */
	private function ticked( string $html ): array {
		$out = array();
		foreach ( array( ProcedureMetaKeys::CENTRE_TYPES, ProcedureMetaKeys::ISLANDS ) as $name ) {
			preg_match_all( '/name="' . preg_quote( $name, '/' ) . '\[\]" value="([^"]*)"\s*checked=/', $html, $matches );
			$out[ $name ] = array_map( 'html_entity_decode', $matches[1] );
		}
		return $out;
	}

	/**
	 * Lo guardado con otras mayúsculas que el catálogo sale marcado, y guardar
	 * sin tocar las casillas no borra el acotado.
	 */
	public function test_the_centre_limits_keep_choices_that_differ_only_in_case() {
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
		list( , $uid, $procedimiento ) = $this->mio(
			array(
				ProcedureMetaKeys::CENTRE_TYPES => array( 'ceip' ),
				ProcedureMetaKeys::ISLANDS      => array( 'isla uno (inventada)' ),
			)
		);

		$this->acting_as( $uid );
		$m    = $this->modelo( $procedimiento, ProcedureEditor::PANEL_ADVANCED );
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

		$switches = array(
			ProcedureEditor::FIELD_LIMIT_TYPES   => '1',
			ProcedureEditor::FIELD_LIMIT_ISLANDS => '1',
		);
		$this->assertNotNull( $this->submit( $uid, ProcedureEditor::OP_CENTRES, $procedimiento, 0, $this->centros( $switches + $marcadas ) ) );
		$this->assertSame( array( 'ceip' ), get_post_meta( $procedimiento, ProcedureMetaKeys::CENTRE_TYPES, true ) );
		$this->assertSame( array( 'isla uno (inventada)' ), get_post_meta( $procedimiento, ProcedureMetaKeys::ISLANDS, true ) );
	}

	/**
	 * El taller avisa de lo que cierra sin verse: «Datos», de una sola
	 * titularidad sin ningún tipo de centro marcado como privado; «Avanzado»,
	 * del catálogo vacío.
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
		$datos = ProcedureEditorView::html( $this->modelo( $procedimiento ) );
		$this->assertStringContainsString( 'ningún tipo de centro marcado como privado', $datos );
		$this->assertStringContainsString( 'El catálogo de centros está vacío', $datos, 'también junto a «Dirigido a»: la titularidad no se puede comprobar' );
		$html = ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_ADVANCED ) );
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
		$this->assertStringNotContainsString( 'prc-aviso-centros', ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_ADVANCED ) ) );
		$this->assertStringNotContainsString( 'prc-aviso-centros', ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_DATA ) ) );
	}

	/**
	 * Solo «Datos» y «Avanzado» construyen el catálogo, para sus avisos y sus
	 * casillas: los demás paneles no lo recorren.
	 */
	public function test_other_panels_do_not_build_the_catalogue() {
		list( , $uid, $procedimiento ) = $this->mio(
			array(
				ProcedureMetaKeys::OWNERSHIP => array( ProcedureMetaKeys::OWNERSHIP_PUBLIC ),
			)
		);
		$this->acting_as( $uid );
		$llamadas = 0;
		add_filter(
			'prc_centres',
			static function ( $centres ) use ( &$llamadas ) {
				++$llamadas;
				return $centres;
			}
		);

		foreach ( array( ProcedureEditor::PANEL_QUESTIONS, ProcedureEditor::PANEL_PUBLISH ) as $panel ) {
			\Prc\Domain\CentreCatalog::flush();
			$this->modelo( $procedimiento, $panel );
		}
		$this->assertSame( 0, $llamadas, 'ni Preguntas ni Publicación leen el catálogo' );

		\Prc\Domain\CentreCatalog::flush();
		$this->modelo( $procedimiento, ProcedureEditor::PANEL_DATA );
		$this->assertSame( 1, $llamadas );
		\Prc\Domain\CentreCatalog::flush();
		$this->modelo( $procedimiento, ProcedureEditor::PANEL_ADVANCED );
		$this->assertSame( 2, $llamadas );
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
		update_user_option( $editor, ProcedureAccess::USER_AREA_META, array( $mine ) );
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
		$this->assertStringContainsString( 'La coordinación ya se da de alta en la lista de la pestaña «Participantes»', $m['flash']['texto'] );
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

		// Con guion, el desplegable apagado no se envía: «una» basta.
		$this->submit( $uid, ProcedureEditor::PANEL_DATA, $id, 0, $this->datos( $area, $curso, array( ProcedureEditor::FIELD_COORDINATOR_KIND => 'one' ) ) );
		$this->assertSame( 1, (int) get_post_meta( $id, ProcedureMetaKeys::COORDINATOR_COUNT, true ) );

		// Sin la opción ni el número (los radios apagados) se queda lo que había.
		$this->submit( $uid, ProcedureEditor::PANEL_DATA, $id, 0, $this->datos( $area, $curso ) );
		$this->assertSame( 1, (int) get_post_meta( $id, ProcedureMetaKeys::COORDINATOR_COUNT, true ) );

		// Con profesorado no hay coordinación: la meta pasa a 0.
		$this->submit( $uid, ProcedureEditor::PANEL_DATA, $id, 0, $this->datos( $area, $curso, array( ProcedureMetaKeys::AUDIENCE => ProcedureMetaKeys::AUDIENCE_TEACHERS ) ) );
		$this->assertSame( 0, (int) get_post_meta( $id, ProcedureMetaKeys::COORDINATOR_COUNT, true ), 'profesorado no lleva coordinación' );
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
	public function test_the_links_section_saves_urls_and_refuses_junk() {
		list( , $uid, $procedimiento ) = $this->mio();

		$url = $this->submit(
			$uid,
			ProcedureEditor::OP_LINKS,
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
				ProcedureEditor::OP_LINKS,
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
		$m = $this->modelo( $procedimiento, ProcedureEditor::PANEL_DATA );
		$this->assertStringContainsString( 'enlace a la resolución', $m['flash']['texto'] );
		$this->assertSame( 'esto no es un enlace', $m['values'][ ProcedureMetaKeys::RESOLUTION_URL ], 'lo tecleado vuelve' );
	}

	/**
	 * Una red que no contesta.
	 *
	 * @return \WP_Error
	 */
	public function sin_red(): \WP_Error {
		return new \WP_Error( 'http_request_failed', 'sin red en los tests' );
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
		$datos = ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_DATA ) );
		$this->assertMatchesRegularExpression( '/<p class="[^"]*alert-warning[^"]*" role="status">[^<]*Falta el curso/', $datos, 'lo que falta para publicar es fijo: no es una alerta' );
		$this->assertStringNotContainsString( 'role="alert"', $datos );
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
		$this->submit( $uid, ProcedureEditor::OP_LINKS, $procedimiento, 0, array( ProcedureMetaKeys::RESOLUTION_URL => 'https://example.org/r.pdf' ) );
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
	 * El rechazo de «Marcar como histórico».
	 *
	 * @return string
	 */
	private function rechazo(): string {
		return (string) \Closure::bind(
			static function (): string {
				return self::$rejected;
			},
			null,
			ProcedureEditor::class
		)();
	}

	/**
	 * Quien gestiona otro ámbito oye «no es suyo», no el estado de un
	 * procedimiento ajeno; con el propio vivo, oye por qué no se marca. En
	 * ningún caso se escribe la marca.
	 */
	public function test_archiving_a_procedure_of_another_area_says_it_is_not_yours() {
		list( , , $procedimiento ) = $this->mio( array( ProcedureMetaKeys::APPLICATIONS_CLOSED => true ) );
		$ajena                     = $this->manager( array( $this->area( 'Otro ámbito' ) ) );

		$this->assertNull( $this->submit( $ajena, ProcedureEditor::OP_ARCHIVE, $procedimiento ) );
		$this->assertStringContainsString( 'no es suyo: solo lo marca', $this->rechazo() );
		$this->assertStringNotContainsString( 'cerrado o resuelto', $this->rechazo() );
		$this->assertFalse( ProcedureAccess::is_archived( $procedimiento ) );
		$this->assertFalse( metadata_exists( 'post', $procedimiento, ProcedureMetaKeys::ARCHIVED ) );
		$this->olvidar_rechazo();

		list( , $propio, $vivo ) = $this->mio();
		$this->assertNull( $this->submit( $propio, ProcedureEditor::OP_ARCHIVE, $vivo ) );
		$this->assertStringContainsString( 'Solo se marca como histórico un procedimiento cerrado o resuelto', $this->rechazo() );
		$this->assertStringNotContainsString( 'no es suyo', $this->rechazo() );
		$this->assertFalse( ProcedureAccess::is_archived( $vivo ) );
		$this->assertFalse( metadata_exists( 'post', $vivo, ProcedureMetaKeys::ARCHIVED ) );
	}

	/**
	 * Sin permiso sobre el procedimiento no se muta NADA.
	 */
	public function test_without_permission_nothing_is_mutated() {
		list( $area, , $procedimiento ) = $this->mio();
		$ajena                          = $this->manager( array( $this->area( 'Otro ámbito' ) ) );

		$this->submit( $ajena, ProcedureEditor::PANEL_DATA, $procedimiento, 0, $this->datos( $area, $this->course(), array( ProcedureEditor::FIELD_TITLE => 'Secuestrado' ) ) );
		$this->submit( $ajena, ProcedureEditor::OP_LINKS, $procedimiento, 0, array( ProcedureMetaKeys::RESOLUTION_URL => 'https://example.org/r.pdf' ) );
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
		$this->submit( $uid, ProcedureEditor::OP_LINKS, $procedimiento, 0, array( ProcedureMetaKeys::RESOLUTION_URL => 'https://example.org/r.pdf' ) );
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
	 * Un procedimiento de antes sin titularidad guardada se pinta dirigido a
	 * todos, y guardar lo que enseña la pantalla no tumba el envío.
	 */
	public function test_an_old_procedure_without_ownership_still_saves_its_dates() {
		list( $area, $uid, $procedimiento ) = $this->en_estado( ProcedureMetaKeys::STATE_CLOSED );
		delete_post_meta( $procedimiento, ProcedureMetaKeys::OWNERSHIP );
		$this->acting_as( $uid );
		$pintada = (array) $this->modelo( $procedimiento )['values'][ ProcedureMetaKeys::OWNERSHIP ];
		$this->assertSame( array_keys( ProcedureMetaKeys::ownerships() ), $pintada );

		$sin                                 = $this->datos(
			$area,
			$this->course( '2099-2100' ),
			array(
				ProcedureMetaKeys::OPENS_AT  => '2026-01-07',
				ProcedureMetaKeys::CLOSES_AT => '2026-01-20',
			)
		);
		$sin[ ProcedureMetaKeys::OWNERSHIP ] = $pintada;
		$url                                 = $this->submit( $uid, ProcedureEditor::PANEL_DATA, $procedimiento, 0, $sin );

		$this->assertSame( 'datos', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
		$this->assertSame( '2026-01-20', get_post_meta( $procedimiento, ProcedureMetaKeys::CLOSES_AT, true ) );
		$this->assertSame( 'Todos los centros', \Prc\PublicFront\View\ProcedureChrome::ownership_label( (array) get_post_meta( $procedimiento, ProcedureMetaKeys::OWNERSHIP, true ) ) );
	}

	/**
	 * Cerrado el plazo, todo se sigue editando (ADR-0050): el título, las
	 * fechas, los compromisos, el curso, los enlaces y los campos. Solo se
	 * rechaza lo que protege las respuestas: cambiar el tipo de un campo
	 * contestado.
	 */
	public function test_a_closed_procedure_saves_everything_but_the_type_of_an_answered_field() {
		list( $area, $uid, $procedimiento ) = $this->en_estado( ProcedureMetaKeys::STATE_CLOSED );
		update_post_meta(
			$procedimiento,
			ProcedureMetaKeys::QUESTIONS,
			array(
				array(
					'key'   => 'q1',
					'label' => 'Modalidad',
					'type'  => ProcedureQuestions::TYPE_TEXT,
				),
			)
		);
		$this->application( $procedimiento, $this->school_head( 'C0001' ), '', array( ApplicationMetaKeys::ANSWERS => array( 'q1' => 'A' ) ) );
		$curso = $this->course( '2099-2100' );

		$url = $this->submit(
			$uid,
			ProcedureEditor::PANEL_DATA,
			$procedimiento,
			0,
			$this->datos(
				$area,
				$curso,
				array(
					ProcedureEditor::FIELD_TITLE   => 'Nombre corregido',
					ProcedureMetaKeys::OPENS_AT    => '2026-01-07',
					ProcedureMetaKeys::CLOSES_AT   => '2026-01-20',
					ProcedureMetaKeys::COMMITMENTS => 'Otros compromisos.',
				)
			)
		);
		$this->assertSame( 'datos', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
		$this->assertSame( '2026-01-20', get_post_meta( $procedimiento, ProcedureMetaKeys::CLOSES_AT, true ) );
		$this->assertSame( 'Nombre corregido', get_the_title( $procedimiento ) );
		$this->assertSame( 'Otros compromisos.', get_post_meta( $procedimiento, ProcedureMetaKeys::COMMITMENTS, true ) );
		$this->assertSame( array( $curso ), wp_get_post_terms( $procedimiento, ProcedureTaxonomies::COURSE, array( 'fields' => 'ids' ) ) );

		$url = $this->submit( $uid, ProcedureEditor::OP_LINKS, $procedimiento, 0, array( ProcedureMetaKeys::RESOLUTION_URL => 'https://example.org/r.pdf' ) );
		$this->assertSame( 'enlaces', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
		$this->assertSame( ProcedureEditor::PANEL_DATA, $this->query_arg( (string) $url, ProcedureEditor::ARG_PANEL ), 'vuelve a «Datos», donde está la sección' );
		$this->assertSame( 'https://example.org/r.pdf', get_post_meta( $procedimiento, ProcedureMetaKeys::RESOLUTION_URL, true ) );

		// El texto de un campo contestado y un campo nuevo, sí.
		$url = $this->submit(
			$uid,
			ProcedureEditor::PANEL_QUESTIONS,
			$procedimiento,
			0,
			array(
				ProcedureEditor::FIELD_Q . 'key'   => array( 'q1', '' ),
				ProcedureEditor::FIELD_Q . 'label' => array( 'Modalidad elegida', 'Observaciones' ),
				ProcedureEditor::FIELD_Q . 'type'  => array( ProcedureQuestions::TYPE_TEXT, ProcedureQuestions::TYPE_TEXT ),
			)
		);
		$this->assertSame( 'preguntas', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
		$this->assertSame( array( 'Modalidad elegida', 'Observaciones' ), array_column( get_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS, true ), 'label' ) );

		// Su tipo, no: las respuestas dadas dejarían de tener sentido.
		$this->assertNull(
			$this->submit(
				$uid,
				ProcedureEditor::PANEL_QUESTIONS,
				$procedimiento,
				0,
				array(
					ProcedureEditor::FIELD_Q . 'key'   => array( 'q1' ),
					ProcedureEditor::FIELD_Q . 'label' => array( 'Modalidad elegida' ),
					ProcedureEditor::FIELD_Q . 'type'  => array( ProcedureQuestions::TYPE_YESNO ),
				)
			)
		);
		$this->assertSame( ProcedureQuestions::TYPE_TEXT, get_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS, true )[0]['type'] );
		$this->assertStringContainsString( 'No se puede cambiar el tipo', $this->modelo( $procedimiento, ProcedureEditor::PANEL_QUESTIONS )['flash']['texto'] );
	}

	/**
	 * The states after closing, for the test below.
	 *
	 * @return array<string, array{0:string}>
	 */
	public function late_states(): array {
		return array(
			'subsanación' => array( ProcedureMetaKeys::STATE_AMENDMENT ),
			'resuelto'    => array( ProcedureMetaKeys::STATE_RESOLVED ),
		);
	}

	/**
	 * En subsanación y resuelto el procedimiento también se edita, y sus
	 * solicitudes se siguen gestionando.
	 *
	 * @dataProvider late_states
	 *
	 * @param string $estado State slug.
	 */
	public function test_amendment_and_resolved_still_edit_the_procedure( string $estado ) {
		list( $area, $uid, $procedimiento, $curso ) = $this->en_estado( $estado );
		$solicitud                                  = $this->application( $procedimiento, $this->school_head( 'C0001' ) );

		$url = $this->submit( $uid, ProcedureEditor::PANEL_DATA, $procedimiento, 0, $this->datos( $area, $curso, array( ProcedureEditor::FIELD_TITLE => 'Nombre corregido' ) ) );
		$this->assertSame( 'datos', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ), $estado );
		$this->assertSame( 'Nombre corregido', get_the_title( $procedimiento ), $estado );
		$this->assertNotNull( $this->submit( $uid, ProcedureEditor::PANEL_QUESTIONS, $procedimiento, 0, array( ProcedureEditor::FIELD_Q . 'label' => array( 'Nuevo campo' ) ) ), $estado );
		$this->assertCount( 1, get_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS, true ), $estado );

		$url = $this->submit(
			$uid,
			ProcedureEditor::OP_REVIEW,
			$procedimiento,
			$solicitud,
			array( ProcedureEditor::FIELD_STATE => ApplicationMetaKeys::REVIEW_ADMITTED )
		);
		$this->assertSame( 'revisada', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ), $estado );
	}

	/**
	 * Y la pantalla no apaga nada: ni aviso del estado, ni bloques en gris,
	 * ni campos en solo lectura. «Enlaces» va al final de «Datos», con su
	 * propio formulario.
	 */
	public function test_the_screen_closes_nothing_until_archived() {
		list( , $uid, $procedimiento ) = $this->en_estado( ProcedureMetaKeys::STATE_CLOSED );
		$this->acting_as( $uid );

		$html = ProcedureEditorView::html( $this->modelo( $procedimiento ) );
		$this->assertStringNotContainsString( 'prc-solo-lectura', $html );
		$this->assertStringNotContainsString( 'disabled>', $html );
		$this->assertStringNotContainsString( 'prc-seccion__bloqueo', $html );
		$this->assertStringNotContainsString( 'Las solicitudes están cerradas', $html );
		$this->assertMatchesRegularExpression( '/Guardar los datos.*?<form[^>]*id="' . ProcedureEditor::SECTION_LINKS . '".*?value="' . ProcedureEditor::OP_LINKS . '".*?name="' . ProcedureMetaKeys::FINAL_LIST_URL . '".*?Guardar los enlaces/s', $html, 'los enlaces, al final de «Datos» y con su botón' );
		$this->assertStringNotContainsString( 'prc-solo-lectura', ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_QUESTIONS ) ) );

		// El fin del plazo pide confirmar un día ya pasado, y el día lo pone el servidor.
		$this->assertMatchesRegularExpression( '/name="' . ProcedureMetaKeys::CLOSES_AT . '"[^>]*data-prc-confirm-before="' . ProcedureState::today() . '"[^>]*data-prc-confirm-blank="prc-opens"[^>]*data-prc-confirm-text="¿Guardar con un fin de plazo que ya ha pasado\?/s', $html );

		// Histórico sí se pinta en solo lectura para su ámbito.
		update_post_meta( $procedimiento, ProcedureMetaKeys::ARCHIVED, true );
		$this->assertStringContainsString( 'prc-solo-lectura', ProcedureEditorView::html( $this->modelo( $procedimiento ) ) );
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
			'Campos adicionales',
			'Participantes',
			'Enlaces',
			'Avanzado',
			'Solicitudes',
			'Publicación',
			'Ver la ficha',
			'Guardar los datos',
		);
		foreach ( $labels as $rotulo ) {
			$this->assertStringContainsString( $rotulo, $html, 'falta ' . $rotulo );
		}
		foreach ( array( 'Datos', 'Campos adicionales', 'Participantes', 'Publicación', 'Avanzado', 'Solicitudes' ) as $pestana ) {
			$this->assertMatchesRegularExpression( '#<a class="prc-tab[^"]*"[^>]*>' . preg_quote( $pestana, '#' ) . '\s#', $html, 'falta la pestaña ' . $pestana );
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
		$this->assertStringContainsString( 'Campo nuevo', $html );
		$this->assertStringContainsString( '>Título</label>', $html, 'cada campo lleva título, no rótulo' );
		$this->assertStringContainsString( 'Guardar los campos', $html );
		// Lo que se lee: el texto y los atributos que se anuncian o se ven.
		preg_match_all( '#(?:aria-label|title|placeholder|data-prc-confirm)="([^"]*)"#', $html, $atributos );
		$legible = wp_strip_all_tags( $html ) . ' ' . implode( ' ', $atributos[1] );
		foreach ( array( 'Pregunta', 'pregunta', 'Rótulo', 'rótulo' ) as $vieja ) {
			$this->assertStringNotContainsString( $vieja, $legible, 'en el panel ya no se dice ' . $vieja );
		}
		$this->assertStringContainsString( 'también después de cerrar la inscripción, hasta que el procedimiento sea histórico', $html, 'la entrega con plazo propio dice cuándo se configura' );
		$this->assertStringNotContainsString( 'rótulo', wp_strip_all_tags( ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_PEOPLE ) ) ), 'los papeles tienen nombre, no rótulo' );
		foreach ( ProcedureQuestions::types() as $rotulo ) {
			$this->assertStringContainsString( esc_html( $rotulo ), $html );
		}

		$html = ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_DATA ) );
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
		$html = ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_APPLICATIONS ) );
		$this->assertStringNotContainsString( 'Plazos de entregas documentales', $html, 'sin entregas con plazo propio, la sección no sale' );
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
		$this->assertStringContainsString( 'los plazos, los campos adicionales y los', $html, 'el alta nombra las pestañas que hay' );
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
	 * Los correos no se apagan en el taller: no hay panel «Avisos» ni su POST (#81).
	 */
	public function test_the_workshop_has_no_notices_panel() {
		list( , $uid, $procedimiento ) = $this->mio();
		$this->acting_as( $uid );
		$m = $this->modelo( $procedimiento, 'avisos' );
		$this->assertArrayNotHasKey( 'avisos', $m['panels'] );
		$this->assertSame( ProcedureEditor::PANEL_DATA, $m['panel'], 'la pestaña de antes cae en los datos' );
		$this->assertStringNotContainsString( 'prc_notify_on', ProcedureEditorView::html( $m ) );

		update_post_meta( $procedimiento, 'prc_notify', array( 'review_changed' ) );
		$this->assertNull( $this->submit( $uid, 'notify', $procedimiento, 0, array( 'prc_notify_on' => array() ) ), 'la operación ya no existe' );
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
		$fields[ $q . 'closes_at' ] = array( '2027-05-01' );
		$this->assertNull( $this->submit( $uid, ProcedureEditor::PANEL_QUESTIONS, $procedure, 0, $fields ) );
		$this->assertSame( $before, get_post_meta( $procedure, ProcedureMetaKeys::QUESTIONS, true ) );
	}

	/**
	 * Cerrada la inscripción, las fechas de una entrega se cambian en su
	 * campo, en «Campos adicionales» (ADR-0050): ya no hay sección aparte
	 * en «Solicitudes» ni su operación.
	 */
	public function test_delivery_dates_are_edited_in_their_field_after_closing() {
		$question                  = array(
			'key'       => 'q1',
			'label'     => 'Memoria final',
			'type'      => ProcedureQuestions::TYPE_FILE,
			'required'  => true,
			'choices'   => array(),
			'delivery'  => true,
			'opens_at'  => $this->dia( '-30 days' ),
			'closes_at' => $this->dia( '-1 day' ),
		);
		list( , $uid, $procedure ) = $this->mio(
			array(
				ProcedureMetaKeys::QUESTIONS => array( $question ),
				ProcedureMetaKeys::OPENS_AT  => $this->dia( '-30 days' ),
				ProcedureMetaKeys::CLOSES_AT => $this->dia( '-1 day' ),
			)
		);
		$this->assertSame( ProcedureMetaKeys::STATE_CLOSED, ProcedureState::of_post( $procedure ) );
		$q        = ProcedureEditor::FIELD_Q;
		$redirect = $this->submit(
			$uid,
			ProcedureEditor::PANEL_QUESTIONS,
			$procedure,
			0,
			array(
				$q . 'key'       => array( 'q1' ),
				$q . 'label'     => array( 'Memoria final' ),
				$q . 'type'      => array( ProcedureQuestions::TYPE_FILE ),
				$q . 'required'  => array( '1' ),
				$q . 'delivery'  => array( '1' ),
				$q . 'opens_at'  => array( $this->dia( '-30 days' ) ),
				$q . 'closes_at' => array( $this->dia( '+30 days' ) ),
			)
		);
		$this->assertSame( 'preguntas', $this->query_arg( (string) $redirect, ProcedureEditor::ARG_NOTICE ) );
		$this->assertSame( $this->dia( '+30 days' ), get_post_meta( $procedure, ProcedureMetaKeys::QUESTIONS, true )[0]['closes_at'] );

		$this->acting_as( $uid );
		$this->assertStringNotContainsString( 'Plazos de entregas documentales', ProcedureEditorView::html( $this->modelo( $procedure, ProcedureEditor::PANEL_APPLICATIONS ) ) );
		$this->assertNull( $this->submit( $uid, 'delivery_dates', $procedure, 0, array( 'prc_delivery_closes' => array( 'q1' => $this->dia( '+60 days' ) ) ) ), 'la operación ya no existe' );
		$this->assertSame( $this->dia( '+30 days' ), get_post_meta( $procedure, ProcedureMetaKeys::QUESTIONS, true )[0]['closes_at'] );
		$this->assertStringContainsString( 'en «Campos adicionales»', $this->modelo( $procedure )['flash']['texto'], 'una pestaña vieja dice dónde se hace ahora' );

		// El cierre de la entrega pide confirmar un día ya pasado, como el del plazo.
		$html = ProcedureEditorView::html( $this->modelo( $procedure, ProcedureEditor::PANEL_QUESTIONS ) );
		$this->assertMatchesRegularExpression( '/name="' . preg_quote( ProcedureEditor::FIELD_Q, '/' ) . 'closes_at\[0\]"[^>]*data-prc-confirm-before="' . ProcedureState::today() . '" data-prc-confirm-text="¿Guardar con un cierre de entrega que ya ha pasado\? La entrega «Memoria final»/', $html );
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
	 * Una opción de «Una opción» con `|` sobrevive a volver a guardar el
	 * panel: el taller la pinta y la guarda tal cual, y lo contestado sigue
	 * casando.
	 */
	public function test_a_single_choice_with_a_bar_survives_saving_the_panel() {
		list( , $uid, $procedimiento ) = $this->mio(
			array(
				ProcedureMetaKeys::QUESTIONS => array(
					array(
						'key'     => 'q1',
						'label'   => 'Curso',
						'type'    => ProcedureQuestions::TYPE_SINGLE,
						'choices' => array( 'Curso | 2024', 'Otro' ),
					),
					array(
						'key'   => 'q2',
						'label' => 'Grupos',
						'type'  => ProcedureQuestions::TYPE_TEXT,
					),
				),
			)
		);
		$this->application( $procedimiento, $this->school_head( 'C0001' ), '', array( ApplicationMetaKeys::ANSWERS => array( 'q1' => 'Curso | 2024' ) ) );

		$this->acting_as( $uid );
		$html = ProcedureEditorView::html( $this->modelo( $procedimiento, ProcedureEditor::PANEL_QUESTIONS ) );
		$this->assertStringContainsString( "Curso | 2024\nOtro</textarea>", $html, 'el textarea pinta las opciones tal cual' );
		$this->assertStringNotContainsString( '| 0', $html );

		for ( $vez = 0; $vez < 2; $vez++ ) {
			$this->assertNotNull(
				$this->submit( $uid, ProcedureEditor::PANEL_QUESTIONS, $procedimiento, 0, $this->preguntas_tal_cual( $procedimiento, array( 'label' => array( 1 => 'Número de grupos' ) ) ) )
			);
		}
		$this->assertSame( array( 'Curso | 2024', 'Otro' ), get_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS, true )[0]['choices'] );
		$this->assertSame( 'Curso | 2024', \Prc\PublicFront\Applications::rows( $procedimiento )[0]['q1'] );
	}

	/**
	 * Una pregunta de archivo con un documento ya subido cuenta como
	 * contestada aunque no tenga respuesta en `prc_answers`: su tipo no cambia.
	 */
	public function test_the_type_of_a_file_question_with_a_document_is_refused() {
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
						'label' => 'Proyecto',
						'type'  => ProcedureQuestions::TYPE_FILE,
					),
				),
			)
		);
		$solicitud                     = $this->application(
			$procedimiento,
			$this->school_head( 'C0001' ),
			'',
			array(
				ApplicationMetaKeys::FILES => array(
					'q2' => array(
						'id'     => str_repeat( 'a', 32 ),
						'stored' => 'ab/cd/' . str_repeat( 'b', 32 ) . '.pdf',
						'sha256' => str_repeat( 'c', 64 ),
						'name'   => 'proyecto.pdf',
						'mime'   => 'application/pdf',
						'size'   => 10,
					),
				),
			)
		);
		$this->assertSame( array( 'q2' ), array_keys( \Prc\PublicFront\ApplicationFiles::descriptors( $solicitud ) ), 'el documento está y no hay respuesta' );
		$this->assertEmpty( get_post_meta( $solicitud, ApplicationMetaKeys::ANSWERS, true ) );
		$antes = get_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS, true );

		$this->assertNull(
			$this->submit( $uid, ProcedureEditor::PANEL_QUESTIONS, $procedimiento, 0, $this->preguntas_tal_cual( $procedimiento, array( 'type' => array( 1 => ProcedureQuestions::TYPE_TEXT ) ) ) ),
			'no se guarda ni vuelve'
		);
		$this->assertSame( $antes, get_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS, true ) );
		$flash = $this->modelo( $procedimiento, ProcedureEditor::PANEL_QUESTIONS )['flash'];
		$this->assertSame( 'error', $flash['tipo'] );
		$this->assertStringContainsString( 'No se puede cambiar el tipo de «Proyecto»', $flash['texto'] );
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
	 * En subsanación también se reordenan: el estado no cierra nada (ADR-0050).
	 */
	public function test_questions_are_reordered_in_any_state() {
		list( , $uid, $procedimiento ) = $this->en_estado( ProcedureMetaKeys::STATE_AMENDMENT );
		update_post_meta(
			$procedimiento,
			ProcedureMetaKeys::QUESTIONS,
			array(
				array( 'label' => 'Una' ),
				array( 'label' => 'Dos' ),
			)
		);

		$this->assertNotNull( $this->submit( $uid, ProcedureEditor::OP_MOVE_QUESTION, $procedimiento, 0, array( ProcedureEditor::FIELD_Q_MOVE => 'q2:up' ) ) );
		$this->assertSame( array( 'q2', 'q1' ), wp_list_pluck( get_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS, true ), 'key' ) );
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

	/**
	 * Un envío rechazado no redirige: vuelve a pintar el taller en la misma
	 * petición. Cada formulario apunta al aviso, para que el navegador lleve
	 * allí el foco y el lector de pantalla empiece por él.
	 */
	public function test_every_workshop_form_comes_back_to_the_notice() {
		list( , , $procedimiento ) = $this->mio();
		$this->application( $procedimiento, $this->school_head( 'C0001' ) );
		$this->acting_as( $this->administrator() );
		$paneles = array(
			ProcedureEditor::PANEL_DATA,
			ProcedureEditor::PANEL_QUESTIONS,
			ProcedureEditor::PANEL_APPLICATIONS,
			ProcedureEditor::PANEL_PEOPLE,
			ProcedureEditor::PANEL_PUBLISH,
			ProcedureEditor::PANEL_ADVANCED,
		);
		foreach ( $paneles as $panel ) {
			$html = ProcedureEditorView::html( $this->modelo( $procedimiento, $panel ) );
			$this->assertStringNotContainsString( 'method="post" action=""', $html, $panel );
		}
		$this->assertStringContainsString( 'method="post" action="#prc-aviso"', $html );
	}
}
