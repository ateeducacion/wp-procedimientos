<?php
/**
 * Tests for the public view of a procedure: its state, its dates and its one button.
 *
 * @package Prc
 */

use Prc\Meta\ProcedureMetaKeys;
use Prc\PublicFront\ProcedureView;
use Prc\PublicFront\Shell;
use Prc\Taxonomy\ProcedureTaxonomies;

/**
 * La ficha pública de un procedimiento.
 *
 * Es el `single` del tipo de contenido, pintado entero en `template_redirect`
 * con el marco del armazón (ADR-0022). Lo que se prueba es lo que decide:
 * el estado derivado, las fechas, los enlaces que existen y —sobre todo— el
 * botón que le toca a quien mira, que es distinto para cada perfil y para
 * cada estado.
 */
class Test_Procedure_View extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Con el aplicativo, sus páginas y sin el catálogo del entorno de desarrollo.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
		$this->pages();
		remove_all_filters( 'prc_centres' );
	}

	/**
	 * Las fechas de un plazo abierto hoy.
	 *
	 * @return array<string, string>
	 */
	private function abierto(): array {
		return array(
			ProcedureMetaKeys::OPENS_AT  => gmdate( 'Y-m-d', strtotime( '-3 days' ) ),
			ProcedureMetaKeys::CLOSES_AT => gmdate( 'Y-m-d', strtotime( '+3 days' ) ),
		);
	}

	/**
	 * Las fechas de un plazo que ya pasó.
	 *
	 * @return array<string, string>
	 */
	private function cerrado(): array {
		return array(
			ProcedureMetaKeys::OPENS_AT  => gmdate( 'Y-m-d', strtotime( '-30 days' ) ),
			ProcedureMetaKeys::CLOSES_AT => gmdate( 'Y-m-d', strtotime( '-10 days' ) ),
		);
	}

	// ─── quién pinta esta petición ─────────────────────────────────────────

	/**
	 * La ficha la sirve el aplicativo, y solo la ficha.
	 */
	public function test_it_only_takes_over_a_procedure() {
		$area          = $this->area( 'Área de prueba' );
		$procedimiento = $this->procedure( $this->administrator(), array( $area ), $this->abierto() );

		$this->acting_as( 0 );
		$this->go_to( (string) get_permalink( $procedimiento ) );
		$this->assertTrue( ProcedureView::takes_over() );
		$this->assertSame( Shell::SECTION_PROCEDURE, Shell::current_section() );

		// El armazón no toca esta petición: el cuerpo no es el de la página.
		$this->assertNull( $this->exit_url( array( Shell::class, 'render_standalone' ) ) );

		$this->go_to( (string) get_permalink( get_page_by_path( Shell::SLUGS['home'] ) ) );
		$this->assertFalse( ProcedureView::takes_over() );
		$this->assertNull( $this->exit_url( array( ProcedureView::class, 'render' ) ) );

		// Y con el filtro en «no», tampoco la sirve.
		$this->go_to( (string) get_permalink( $procedimiento ) );
		add_filter( 'prc_standalone_page', '__return_false' );
		$this->assertFalse( ProcedureView::takes_over() );
	}

	/**
	 * Lo que no es un procedimiento no tiene ficha: el modelo viene en blanco.
	 */
	public function test_anything_that_is_not_a_procedure_has_an_empty_model() {
		$pagina = (int) self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);

		foreach ( array( 0, -1, $pagina ) as $id ) {
			$m = ProcedureView::model( $id );
			$this->assertSame( 0, $m['id'], (string) $id );
			$this->assertSame( '', $m['title'] );
			$this->assertSame( '', $m['state'] );
			$this->assertSame( array(), $m['links'] );
			$this->assertSame( '', $m['manage_url'] );
			$this->assertSame(
				array(
					'label'   => '',
					'url'     => '',
					'icon'    => '',
					'primary' => false,
				),
				$m['action']
			);
			$this->assertSame( array(), $m['countdown'] );
			$this->assertSame( array(), $m['tabs'] );
			$this->assertSame( array(), $m['mine'] );
		}
	}

	// ─── lo que dice la ficha ──────────────────────────────────────────────

	/**
	 * El estado, las fechas, el ámbito, el curso y los enlaces que existen.
	 */
	public function test_the_model_says_the_state_the_dates_and_the_links() {
		$area          = $this->area( 'Área de prueba' );
		$curso         = $this->course( '2026-2027' );
		$procedimiento = $this->procedure(
			$this->administrator(),
			array( $area ),
			array_merge(
				$this->abierto(),
				array(
					ProcedureMetaKeys::AMEND_OPENS_AT  => '2026-11-02',
					ProcedureMetaKeys::AMEND_CLOSES_AT => '2026-11-06',
					ProcedureMetaKeys::CONTACT_EMAILS  => array( 'contacto@example.org', 'segundo@example.org' ),
					ProcedureMetaKeys::HEADER_COLOR    => 'granate',
					ProcedureMetaKeys::REQUIRES_COORDINATOR => true,
					ProcedureMetaKeys::OWNERSHIP       => array( ProcedureMetaKeys::OWNERSHIP_PUBLIC ),
					ProcedureMetaKeys::PROVISIONAL_LIST_URL => 'https://example.org/provisional.pdf',
				)
			),
			array(
				'post_title'   => 'Red de centros de prueba',
				'post_excerpt' => 'Una red de centros.',
				'post_content' => '<p>Las bases de la convocatoria.</p>',
			)
		);
		wp_set_object_terms( $procedimiento, array( $curso ), ProcedureTaxonomies::COURSE );

		$this->acting_as( 0 );
		$m = ProcedureView::model( $procedimiento );

		$this->assertSame( $procedimiento, $m['id'] );
		$this->assertSame( 'Red de centros de prueba', $m['title'] );
		$this->assertSame( 'Una red de centros.', $m['excerpt'] );
		$this->assertSame( ProcedureMetaKeys::STATE_OPEN, $m['state'] );
		$this->assertSame( 'Abierto', $m['state_label'] );
		$this->assertStringContainsString( 'Del ', $m['dates'] );
		// El nombre del mes lo pone el idioma del sitio, que en los tests es el
		// de la instalación: lo que se afirma aquí es la forma del intervalo.
		$this->assertMatchesRegularExpression( '/^Del 2 al 6 de \S+ de 2026$/u', $m['amend_dates'] );
		$this->assertSame( 'Área de prueba', $m['areas'] );
		$this->assertSame( '2026-2027', $m['courses'] );
		$this->assertSame( array( 'contacto@example.org', 'segundo@example.org' ), $m['contact_emails'] );
		$this->assertSame( 'contacto@example.org', $m['contact_email'], 'el primero, para el lateral de un solo correo' );
		$this->assertSame( '#8c2230', $m['header_color'] );
		$this->assertTrue( $m['requires_coordinator'] );
		$this->assertStringContainsString( 'Las bases de la convocatoria.', $m['content'] );

		// Solo los enlaces que hay: los que están vacíos no se pintan (ADR-0021).
		$this->assertCount( 1, $m['links'] );
		$this->assertSame( 'Listado provisional de admitidos', $m['links'][0]['label'] );
		$this->assertSame( 'https://example.org/provisional.pdf', $m['links'][0]['url'] );
	}

	// ─── el botón de cada quien ────────────────────────────────────────────

	/**
	 * Sin sesión: «Solicitar» lleva al acceso, y solo mientras hay plazo.
	 */
	public function test_without_a_session_the_button_goes_through_the_login() {
		$area    = $this->area( 'Área de prueba' );
		$abierta = $this->procedure( $this->administrator(), array( $area ), $this->abierto() );
		$cerrada = $this->procedure( $this->administrator(), array( $area ), $this->cerrado() );

		$this->acting_as( 0 );

		$accion = ProcedureView::model( $abierta )['action'];
		$this->assertSame( 'Solicitar', $accion['label'] );
		$this->assertSame( 'edit_note', $accion['icon'] );
		$this->assertTrue( $accion['primary'] );
		$this->assertSame( wp_login_url( Shell::url( 'apply', array( Shell::ARG_PROCEDURE => $abierta ) ) ), $accion['url'] );

		$this->assertSame( '', ProcedureView::model( $cerrada )['action']['label'], 'fuera de plazo no se ofrece nada' );
		$this->assertSame( '', ProcedureView::model( $abierta )['manage_url'] );
	}

	/**
	 * Un centro solicita mientras hay plazo, y después abre la suya.
	 */
	public function test_a_school_sees_apply_and_then_my_application() {
		$area          = $this->area( 'Área de prueba' );
		$procedimiento = $this->procedure( $this->administrator(), array( $area ), $this->abierto() );
		$direccion     = $this->school_head( 'C0001' );
		$solicitud     = Shell::url( 'apply', array( Shell::ARG_PROCEDURE => $procedimiento ) );

		$this->acting_as( $direccion );
		$this->assertSame(
			array(
				'label'   => 'Solicitar',
				'url'     => $solicitud,
				'icon'    => 'edit_note',
				'primary' => true,
			),
			ProcedureView::model( $procedimiento )['action']
		);

		// Con una presentada, el botón es la suya, y lo sigue siendo con el
		// plazo cerrado: se consulta (ADR-0018). Y dice lo que va a pasar al
		// pulsarlo, que con el plazo abierto es editar (mejora M4).
		$this->application( $procedimiento, $direccion );
		$this->assertSame(
			array(
				'label'   => 'Editar mi solicitud',
				'url'     => $solicitud,
				'icon'    => 'edit_note',
				'primary' => true,
			),
			ProcedureView::model( $procedimiento )['action']
		);

		update_post_meta( $procedimiento, ProcedureMetaKeys::OPENS_AT, $this->cerrado()[ ProcedureMetaKeys::OPENS_AT ] );
		update_post_meta( $procedimiento, ProcedureMetaKeys::CLOSES_AT, $this->cerrado()[ ProcedureMetaKeys::CLOSES_AT ] );
		$this->assertSame(
			array(
				'label'   => 'Ver mi solicitud',
				'url'     => $solicitud,
				'icon'    => 'visibility',
				'primary' => false,
			),
			ProcedureView::model( $procedimiento )['action'],
			'con el plazo cerrado ya no se edita: se consulta, y deja de ser el botón principal'
		);
	}

	/**
	 * Y quien no puede solicitar no ve el botón: falla en cerrado.
	 */
	public function test_whoever_cannot_apply_gets_no_button() {
		$area          = $this->area( 'Área de prueba' );
		$procedimiento = $this->procedure( $this->administrator(), array( $area ), $this->abierto() );

		// Sin código de centro en la ficha, `prc_apply` no sirve para nada.
		$this->acting_as( $this->school_head() );
		$this->assertSame( '', ProcedureView::model( $procedimiento )['action']['label'] );

		// Sin la capacidad, tampoco.
		$this->acting_as( (int) self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->assertSame( '', ProcedureView::model( $procedimiento )['action']['label'] );

		// Y si la convocatoria se dirige a otra titularidad, el catálogo lo dice.
		add_filter(
			'prc_centres',
			static function (): array {
				return array(
					array(
						'code'      => 'C0002',
						'name'      => 'Colegio Sur',
						'ownership' => 'private',
					),
				);
			}
		);
		update_post_meta( $procedimiento, ProcedureMetaKeys::OWNERSHIP, array( ProcedureMetaKeys::OWNERSHIP_PUBLIC ) );
		$this->acting_as( $this->school_head( 'C0002' ) );
		$this->assertSame( '', ProcedureView::model( $procedimiento )['action']['label'] );
	}

	/**
	 * Quien gestiona el ámbito ve «Gestionar», y quien gestiona otro no ve nada.
	 */
	public function test_only_the_area_that_convenes_it_can_manage_it() {
		$mia           = $this->area( 'Área de prueba' );
		$otra          = $this->area( 'Otro ámbito' );
		$procedimiento = $this->procedure( $this->manager( array( $mia ) ), array( $mia ), $this->abierto() );
		$taller        = Shell::url( 'editor', array( Shell::ARG_PROCEDURE => $procedimiento ) );

		$this->acting_as( $this->manager( array( $mia ) ) );
		$m = ProcedureView::model( $procedimiento );
		$this->assertSame( $taller, $m['manage_url'] );
		$this->assertSame( '', $m['action']['label'], 'quien gestiona no solicita' );

		$this->acting_as( $this->manager( array( $otra ) ) );
		$this->assertSame( '', ProcedureView::model( $procedimiento )['manage_url'] );

		// Administración llega a todos los ámbitos.
		$this->acting_as( $this->administrator() );
		$this->assertSame( $taller, ProcedureView::model( $procedimiento )['manage_url'] );

		// Y un procedimiento histórico se sigue consultando desde su taller
		// (ADR-0023): el botón no desaparece, aunque ya no se edite.
		update_post_meta( $procedimiento, ProcedureMetaKeys::ARCHIVED, true );
		$this->acting_as( $this->manager( array( $mia ) ) );
		$this->assertSame( $taller, ProcedureView::model( $procedimiento )['manage_url'] );
	}

	// ─── el contador, las pestañas y la caja resumen ───────────────────────

	/**
	 * El contador cuenta al cierre del plazo vivo, y dice a cuál.
	 *
	 * El plazo se guarda como día porque el estado se compara por día
	 * (ADR-0013); el instante del contador es ese día a las 23:59:00 en la
	 * zona del sitio, y no cambia el estado: solo lo cuenta.
	 */
	public function test_the_countdown_counts_to_the_deadline_that_is_running() {
		$area   = $this->area( 'Área de prueba' );
		$cierra = gmdate( 'Y-m-d', strtotime( '+3 days' ) );
		$id     = $this->procedure( $this->administrator(), array( $area ), $this->abierto() );

		$this->acting_as( 0 );
		$m = ProcedureView::model( $id );

		$this->assertSame( 'inscripción', $m['deadline_label'] );
		$this->assertNotSame( array(), $m['countdown'] );
		// Los días con tres dígitos y el resto con dos, como el original.
		$this->assertMatchesRegularExpression( '/^\d{3}$/', $m['countdown']['dias'] );
		$this->assertMatchesRegularExpression( '/^\d{2}$/', $m['countdown']['horas'] );
		$this->assertMatchesRegularExpression( '/^\d{2}$/', $m['countdown']['minutos'] );
		$this->assertMatchesRegularExpression( '/^\d{2}$/', $m['countdown']['segundos'] );
		$this->assertGreaterThanOrEqual( 2, (int) $m['countdown']['dias'], 'el plazo cierra dentro de tres días' );
		$this->assertStringStartsWith( $cierra . 'T23:59:00', $m['deadline_iso'] );
		$this->assertFalse( $m['expired'] );

		// En subsanación cuenta a la subsanación, no a la inscripción.
		update_post_meta( $id, ProcedureMetaKeys::AMEND_OPENS_AT, gmdate( 'Y-m-d', strtotime( '-1 day' ) ) );
		update_post_meta( $id, ProcedureMetaKeys::AMEND_CLOSES_AT, gmdate( 'Y-m-d', strtotime( '+5 days' ) ) );
		$m = ProcedureView::model( $id );
		$this->assertSame( 'subsanación', $m['deadline_label'] );
		$this->assertStringStartsWith( gmdate( 'Y-m-d', strtotime( '+5 days' ) ), $m['deadline_iso'] );
	}

	/**
	 * Sin plazo vivo no hay contador; con el plazo pasado, hay aviso.
	 */
	public function test_without_a_running_deadline_there_is_no_countdown() {
		$area = $this->area( 'Área de prueba' );

		$this->acting_as( 0 );

		// Un procedimiento sin fechas se está preparando: ni contador ni aviso.
		$sin = ProcedureView::model( $this->procedure( $this->administrator(), array( $area ) ) );
		$this->assertSame( array(), $sin['countdown'] );
		$this->assertSame( '', $sin['deadline_iso'] );
		$this->assertFalse( $sin['expired'] );

		// Con el plazo pasado no se cuenta a cero: se dice que terminó.
		$fuera = ProcedureView::model( $this->procedure( $this->administrator(), array( $area ), $this->cerrado() ) );
		$this->assertSame( array(), $fuera['countdown'] );
		$this->assertTrue( $fuera['expired'] );
		$this->assertSame( 'inscripción', $fuera['deadline_label'] );
		// Un día suelto, no un intervalo abierto: el aviso dice «terminó el
		// 27 de agosto de 2026», no «terminó el Desde el 27 de agosto».
		$this->assertStringStartsNotWith( 'Desde', $fuera['deadline_text'] );
		$this->assertMatchesRegularExpression( '/^\d{1,2} de \S+ de \d{4}$/u', $fuera['deadline_text'] );
	}

	/**
	 * Las pestañas de contenido salen de los `h2` de la descripción.
	 *
	 * Una sin contenido no se pinta, y lo que va antes del primer `h2` es la
	 * primera, con el rótulo del sitio que se sustituye.
	 */
	public function test_the_content_tabs_come_from_the_headings() {
		$id = $this->procedure(
			$this->administrator(),
			array( $this->area( 'Área de prueba' ) ),
			$this->abierto(),
			array( 'post_content' => "Lo que persigue.\n\n<h2>Dirigido a:</h2>\nA los centros.\n\n<h2>Calendario:</h2>\n\n<h2>Plazas:</h2>\nCiento veinte." )
		);

		$this->acting_as( 0 );
		$m = ProcedureView::model( $id );

		$this->assertCount( 3, $m['tabs'], 'la pestaña sin contenido no se pinta' );
		$this->assertSame( 'Objetivos:', $m['tabs'][0]['label'] );
		$this->assertStringContainsString( 'Lo que persigue.', $m['tabs'][0]['html'] );
		$this->assertSame( 'Dirigido a:', $m['tabs'][1]['label'] );
		$this->assertSame( 'Plazas:', $m['tabs'][2]['label'] );
		$this->assertSame( 'prc-panel-1', $m['tabs'][0]['id'] );

		// Sin encabezados, un solo panel: la ficha larga de siempre.
		$suelto = $this->procedure(
			$this->administrator(),
			array( $this->area( 'Otro ámbito' ) ),
			$this->abierto(),
			array( 'post_content' => 'Solo un párrafo.' )
		);
		$this->assertCount( 1, ProcedureView::model( $suelto )['tabs'] );
	}

	/**
	 * La caja resumen dice si el centro de quien mira ya ha solicitado.
	 */
	public function test_the_summary_says_whether_this_school_already_applied() {
		$area      = $this->area( 'Área de prueba' );
		$id        = $this->procedure( $this->administrator(), array( $area ), $this->abierto() );
		$direccion = $this->school_head( 'C0001' );

		// Una visita anónima no tiene centro del que hablar.
		$this->acting_as( 0 );
		$this->assertSame( array(), ProcedureView::model( $id )['mine'] );

		// Con sesión y sin solicitud: se dice que aún no.
		$this->acting_as( $direccion );
		$mia = ProcedureView::model( $id )['mine'];
		$this->assertFalse( $mia['has'] );
		$this->assertSame( Shell::url( 'apply', array( Shell::ARG_PROCEDURE => $id ) ), $mia['url'] );

		// Con solicitud: la fecha y el estado de revisión.
		$this->application( $id, $direccion );
		$mia = ProcedureView::model( $id )['mine'];
		$this->assertTrue( $mia['has'] );
		$this->assertSame( 'Presentada', $mia['label'] );
		$this->assertMatchesRegularExpression( '/^\d{2}-\d{2}-\d{4}$/', $mia['date'] );
	}

	// ─── el documento ──────────────────────────────────────────────────────

	/**
	 * La ficha se sirve entera en `template_redirect`, sin la plantilla del tema.
	 */
	public function test_the_whole_document_is_served() {
		$area          = $this->area( 'Área de prueba' );
		$procedimiento = $this->procedure(
			$this->administrator(),
			array( $area ),
			$this->abierto(),
			array(
				'post_title'   => 'Red de centros de prueba',
				'post_excerpt' => 'Una red de centros.',
			)
		);

		$this->acting_as( 0 );
		$this->go_to( (string) get_permalink( $procedimiento ) );

		$documento = $this->served( array( ProcedureView::class, 'render' ) );

		$this->assertStringContainsString( '<!doctype html>', $documento );
		$this->assertStringContainsString( '</html>', $documento );
		$this->assertStringContainsString( 'prc-ficha', $documento );
		$this->assertStringContainsString( 'Red de centros de prueba', $documento );
		$this->assertStringContainsString( 'Una red de centros.', $documento );
		$this->assertStringContainsString( 'Abierto', $documento );
		$this->assertStringContainsString( 'Inscripción', $documento );
		$this->assertStringContainsString( 'Solicitar', $documento );
		// El título del procedimiento lo pinta la ficha, no el armazón.
		$this->assertStringContainsString( 'prc-titulo-proc', $documento );
	}

	/**
	 * Y nada de lo que trae el procedimiento se cuela sin escapar.
	 */
	public function test_nothing_gets_out_unescaped() {
		$area          = $this->area( 'Área de prueba' );
		$procedimiento = $this->procedure(
			$this->administrator(),
			array( $area ),
			$this->abierto(),
			array(
				'post_title'   => 'Red <script>alert(1)</script>',
				'post_excerpt' => '"><script>alert(2)</script>',
				'post_content' => '<p>Bases</p><script>alert(3)</script>',
			)
		);

		$this->acting_as( 0 );
		$this->go_to( (string) get_permalink( $procedimiento ) );

		$documento = $this->served( array( ProcedureView::class, 'render' ) );

		$this->assertStringNotContainsString( '<script>alert(1)', $documento );
		$this->assertStringNotContainsString( '<script>alert(2)', $documento );
		$this->assertStringNotContainsString( '<script>alert(3)', $documento, 'el cuerpo pasa por wp_kses_post()' );
		$this->assertStringContainsString( 'Bases', $documento );
	}

	/**
	 * El arranque cuelga la ficha de `template_redirect`.
	 */
	public function test_register_hooks_the_render() {
		ProcedureView::register();

		$this->assertSame( ProcedureView::PRIORITY, has_action( 'template_redirect', array( ProcedureView::class, 'render' ) ) );
	}
}
