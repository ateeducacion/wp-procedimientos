<?php
/**
 * Tests for «Gestión de procedimientos»: what each ámbito lists, and why.
 *
 * @package Prc
 */

use Prc\Meta\ProcedureMetaKeys;
use Prc\PublicFront\Shell;
use Prc\PublicFront\Workspace;
use Prc\Taxonomy\ProcedureTaxonomies;

/**
 * El taller: la pantalla en la que se empieza el día.
 *
 * Aquí no se muta nada, así que lo que se prueba es qué se enumera y qué no:
 * el acotado por ámbito —lo que convoca otro ámbito no sale—, el recuento de
 * solicitudes de cada fila, los filtros de la URL y lo que se le dice a quien
 * todavía no puede gestionar nada. Falla en cerrado: sin ámbito no hay lista.
 */
class Test_Workspace extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Con el aplicativo y sus páginas.
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
	 * Los títulos de las filas del modelo, para comparar sin escribir bucles.
	 *
	 * @param array<string, mixed> $m What model() returned.
	 * @return string[]
	 */
	private function titulos( array $m ): array {
		return array_map( 'strval', array_column( (array) $m['rows'], 'title' ) );
	}

	// ─── quién no ve nada ──────────────────────────────────────────────────

	/**
	 * Sin sesión, sin capacidad o sin ámbito no hay lista, y se dice por qué.
	 *
	 * Falta el permiso y falta el ámbito no es lo mismo, y no se arregla en el
	 * mismo sitio: la pantalla lo cuenta distinto.
	 */
	public function test_it_says_why_there_is_no_list_at_all() {
		$this->acting_as( 0 );
		$m = Workspace::model();
		$this->assertFalse( $m['can_use'] );
		$this->assertSame( array(), $m['rows'] );
		$this->assertStringContainsString( 'Debe iniciar sesión', $m['reason'] );

		$this->acting_as( (int) self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->assertStringContainsString( 'todavía no gestiona procedimientos', Workspace::model()['reason'] );

		$this->acting_as( $this->school_head( 'C0001' ) );
		$this->assertStringContainsString( 'todavía no gestiona procedimientos', Workspace::model()['reason'] );

		// Con la capacidad pero sin ámbito en el perfil: falla en cerrado.
		$this->acting_as( $this->manager() );
		$m = Workspace::model();
		$this->assertFalse( $m['can_use'] );
		$this->assertStringContainsString( 'ningún ámbito asignado', $m['reason'] );
	}

	/**
	 * Y sin poder usarla, la pantalla es el aviso y nada más: ni tabla, ni botones.
	 */
	public function test_without_permission_nothing_is_painted() {
		$this->acting_as( $this->manager() );

		$html = Workspace::render();

		$this->assertStringContainsString( 'ningún ámbito asignado', $html );
		$this->assertStringNotContainsString( 'Nuevo procedimiento', $html );
		$this->assertStringNotContainsString( 'prc-tabla', $html );
		$this->assertStringNotContainsString( 'prc-cifras', $html );
	}

	// ─── el acotado por ámbito ─────────────────────────────────────────────

	/**
	 * Solo los procedimientos de los ámbitos de quien mira.
	 */
	public function test_the_list_is_scoped_to_the_areas_of_whoever_looks() {
		$mia   = $this->area( 'Área de prueba' );
		$otra  = $this->area( 'Otro ámbito' );
		$quien = $this->manager( array( $mia ) );

		$this->procedure( $quien, array( $mia ), $this->abierto(), array( 'post_title' => 'Convocatoria de mi ámbito' ) );
		$this->procedure( $this->manager( array( $otra ) ), array( $otra ), $this->abierto(), array( 'post_title' => 'Zanja de otro ámbito' ) );

		$this->acting_as( $quien );
		$m = Workspace::model();

		$this->assertTrue( $m['can_use'] );
		$this->assertTrue( $m['scoped'] );
		$this->assertStringContainsString( 'Solo los procedimientos de su ámbito', $m['subtitle'] );
		$this->assertSame( array( 'Convocatoria de mi ámbito' ), $this->titulos( $m ) );

		// Administración trabaja sobre todos los ámbitos.
		$this->acting_as( $this->administrator() );
		$m = Workspace::model();
		$this->assertFalse( $m['scoped'] );
		$this->assertStringContainsString( 'Todos los procedimientos', $m['subtitle'] );
		$this->assertSame( array( 'Convocatoria de mi ámbito', 'Zanja de otro ámbito' ), $this->titulos( $m ) );
	}

	/**
	 * Un ámbito alcanza a los que cuelgan de él: quien tiene el servicio ve sus áreas.
	 */
	public function test_an_area_reaches_its_descendants() {
		$madre = $this->area( 'Servicio de prueba' );
		$hija  = $this->area( 'Área de prueba', $madre );
		$quien = $this->manager( array( $madre ) );

		$this->procedure( $quien, array( $hija ), $this->abierto(), array( 'post_title' => 'La del área hija' ) );

		$this->acting_as( $quien );
		$this->assertSame( array( 'La del área hija' ), $this->titulos( Workspace::model() ) );
	}

	/**
	 * Un procedimiento recién creado todavía no tiene ámbito, y quien lo creó
	 * es justo quien tiene que ponérselo: no se le puede perder de vista.
	 */
	public function test_a_brand_new_procedure_stays_with_whoever_created_it() {
		$area  = $this->area( 'Área de prueba' );
		$quien = $this->manager( array( $area ) );

		$this->procedure(
			$quien,
			array(),
			array(),
			array(
				'post_title'  => 'Sin ámbito todavía',
				'post_status' => 'draft',
			)
		);

		$this->acting_as( $quien );
		$m = Workspace::model();
		$this->assertSame( array( 'Sin ámbito todavía' ), $this->titulos( $m ) );
		$this->assertSame( ProcedureMetaKeys::STATE_DRAFT, $m['rows'][0]['state'] );

		// Y no se lo ve quien gestiona otro ámbito.
		$this->acting_as( $this->manager( array( $this->area( 'Otro ámbito' ) ) ) );
		$this->assertSame( array(), $this->titulos( Workspace::model() ) );
	}

	/**
	 * La papelera no es un estado del listado: lo borrado no se enumera.
	 */
	public function test_what_is_in_the_bin_is_not_listed() {
		$area  = $this->area( 'Área de prueba' );
		$quien = $this->manager( array( $area ) );
		$id    = $this->procedure( $quien, array( $area ), $this->abierto(), array( 'post_title' => 'La que se borra' ) );

		wp_trash_post( $id );

		$this->acting_as( $quien );
		$this->assertSame( array(), $this->titulos( Workspace::model() ) );
	}

	// ─── las cifras y los filtros ──────────────────────────────────────────

	/**
	 * Cada fila dice cuántas solicitudes le han llegado.
	 */
	public function test_each_row_carries_its_number_of_applications() {
		$area  = $this->area( 'Área de prueba' );
		$quien = $this->manager( array( $area ) );
		$con   = $this->procedure( $quien, array( $area ), $this->abierto(), array( 'post_title' => 'Con solicitudes' ) );
		$sin   = $this->procedure( $quien, array( $area ), $this->abierto(), array( 'post_title' => 'Sin solicitudes' ) );

		$this->application( $con, $this->school_head( 'C0001' ) );
		$this->application( $con, $this->school_head( 'C0002' ) );
		// Una solicitud de otro procedimiento no se le suma a este.
		$this->application( $sin, $this->school_head( 'C0003' ) );
		wp_trash_post( $this->application( $con, $this->school_head( 'C0004' ) ) );

		$this->acting_as( $quien );
		$cuenta = array_combine( $this->titulos( Workspace::model() ), array_column( Workspace::model()['rows'], 'applications' ) );

		$this->assertSame( 2, $cuenta['Con solicitudes'], 'la que está en la papelera no cuenta' );
		$this->assertSame( 1, $cuenta['Sin solicitudes'] );
	}

	/**
	 * Las cifras se cuentan sobre el curso entero, antes de acotar por estado.
	 */
	public function test_the_figures_are_counted_before_the_state_filter() {
		$area  = $this->area( 'Área de prueba' );
		$curso = $this->course( '2026-2027' );
		$quien = $this->manager( array( $area ) );

		foreach ( array( 'Una abierta', 'Otra abierta' ) as $titulo ) {
			$id = $this->procedure( $quien, array( $area ), $this->abierto(), array( 'post_title' => $titulo ) );
			wp_set_object_terms( $id, array( $curso ), ProcedureTaxonomies::COURSE );
		}
		$borrador = $this->procedure(
			$quien,
			array( $area ),
			$this->abierto(),
			array(
				'post_title'  => 'Un borrador',
				'post_status' => 'draft',
			)
		);
		wp_set_object_terms( $borrador, array( $curso ), ProcedureTaxonomies::COURSE );

		$this->acting_as( $quien );

		$_GET[ Workspace::VAR_STATE ] = ProcedureMetaKeys::STATE_OPEN;
		$m                            = Workspace::model();

		$this->assertSame( array( 'Otra abierta', 'Una abierta' ), $this->titulos( $m ) );
		$this->assertSame( 3, $m['counts']['all'], 'las cifras no las acota el estado elegido' );
		$this->assertSame( 2, $m['counts'][ ProcedureMetaKeys::STATE_OPEN ] );
		$this->assertSame( 1, $m['counts'][ ProcedureMetaKeys::STATE_DRAFT ] );
	}

	/**
	 * El curso acota, y sin pedir ninguno se enseña el más reciente.
	 */
	public function test_the_course_filter_defaults_to_the_newest_one() {
		$area  = $this->area( 'Área de prueba' );
		$viejo = $this->course( '2025-2026' );
		$nuevo = $this->course( '2026-2027' );
		$quien = $this->manager( array( $area ) );

		$uno = $this->procedure( $quien, array( $area ), $this->abierto(), array( 'post_title' => 'La del curso pasado' ) );
		$dos = $this->procedure( $quien, array( $area ), $this->abierto(), array( 'post_title' => 'La de este curso' ) );
		wp_set_object_terms( $uno, array( $viejo ), ProcedureTaxonomies::COURSE );
		wp_set_object_terms( $dos, array( $nuevo ), ProcedureTaxonomies::COURSE );

		$this->acting_as( $quien );

		$m = Workspace::model();
		$this->assertSame( $nuevo, $m['selection']['course'] );
		$this->assertSame( array( $nuevo, $viejo ), array_keys( $m['courses'] ) );
		$this->assertSame( array( 'La de este curso' ), $this->titulos( $m ) );

		$_GET[ Workspace::VAR_COURSE ] = (string) $viejo;
		$this->assertSame( array( 'La del curso pasado' ), $this->titulos( Workspace::model() ) );

		// Un curso que no está deja de acotar en vez de vaciar la pantalla.
		$_GET[ Workspace::VAR_COURSE ] = '999999';
		$m                             = Workspace::model();
		$this->assertSame( 0, $m['selection']['course'] );
		$this->assertSame( array( 'La de este curso', 'La del curso pasado' ), $this->titulos( $m ) );
	}

	/**
	 * Cuando los filtros dejan la tabla vacía se dice, y se ofrece quitarlos.
	 */
	public function test_an_empty_table_says_whether_the_filters_are_to_blame() {
		$area  = $this->area( 'Área de prueba' );
		$quien = $this->manager( array( $area ) );

		// A quien trabaja sobre todos los ámbitos no se le habla del suyo.
		$this->acting_as( $this->administrator() );
		$this->assertStringContainsString( 'Cree el primero', Workspace::model()['empty_text'] );

		$this->acting_as( $quien );
		$m = Workspace::model();
		$this->assertStringContainsString( 'Todavía no hay ningún procedimiento de su ámbito', $m['empty_text'] );
		$this->assertSame( '', $m['reset_url'], 'sin filtrar no hay nada que quitar' );

		// Y con un filtro puesto, lo que se dice es que el filtro tiene arreglo.
		$this->procedure( $quien, array( $area ), $this->abierto(), array( 'post_title' => 'La única' ) );
		$_GET[ Workspace::VAR_STATE ] = ProcedureMetaKeys::STATE_RESOLVED;
		$m                            = Workspace::model();
		$this->assertSame( array(), $m['rows'] );
		$this->assertStringContainsString( 'Pruebe a quitar algún filtro', $m['empty_text'] );
		$this->assertNotSame( '', $m['reset_url'] );
	}

	/**
	 * Lo que se lee de la URL: el estado es una lista cerrada.
	 */
	public function test_the_selection_comes_from_the_url_and_is_closed() {
		$this->assertSame(
			array(
				'course' => 0,
				'state'  => 'all',
				'q'      => '',
			),
			Workspace::selection()
		);

		$_GET[ Workspace::VAR_STATE ]  = ProcedureMetaKeys::STATE_OPEN;
		$_GET[ Workspace::VAR_COURSE ] = '-4';
		$_GET[ Workspace::VAR_SEARCH ] = '  red  ';
		$this->assertSame(
			array(
				'course' => 0,
				'state'  => ProcedureMetaKeys::STATE_OPEN,
				'q'      => 'red',
			),
			Workspace::selection()
		);
		unset( $_GET[ Workspace::VAR_SEARCH ] );

		$_GET[ Workspace::VAR_STATE ] = 'inventado';
		$this->assertSame( 'all', Workspace::selection()['state'] );

		$this->assertSame( 'de-oficio', Workspace::input( 'prc_no_esta', 'de-oficio' ) );
		$this->assertArrayHasKey( 'all', Workspace::state_filters() );
		$this->assertSame( 'Todos', Workspace::state_filters()['all'] );
	}

	/**
	 * `url()` lleva puesto lo elegido, y no escribe lo que es el valor de siempre.
	 */
	public function test_the_url_keeps_what_is_selected() {
		$seleccion = array(
			'course' => 4,
			'state'  => ProcedureMetaKeys::STATE_OPEN,
			'q'      => 'red',
		);

		$url = Workspace::url( $seleccion );
		$this->assertStringContainsString( Workspace::VAR_COURSE . '=4', $url );
		$this->assertStringContainsString( Workspace::VAR_STATE . '=' . ProcedureMetaKeys::STATE_OPEN, $url );
		$this->assertStringContainsString( Workspace::VAR_SEARCH . '=red', $url );

		$limpia = Workspace::url(
			$seleccion,
			array(
				'course' => 0,
				'state'  => 'all',
				'q'      => '',
			)
		);
		$this->assertSame( Shell::url( 'workspace' ), $limpia );
	}

	/**
	 * Lo que otra pantalla dejó dicho al llegar aquí.
	 */
	public function test_the_flash_notice_of_the_other_screens() {
		$area  = $this->area( 'Área de prueba' );
		$quien = $this->manager( array( $area ) );
		$this->acting_as( $quien );

		$this->assertSame(
			array(
				'type' => '',
				'text' => '',
			),
			Workspace::model()['notice']
		);

		$_GET[ Workspace::VAR_NOTICE ] = 'creado';
		$aviso                         = Workspace::model()['notice'];
		$this->assertSame( 'ok', $aviso['type'] );
		$this->assertStringContainsString( 'Procedimiento creado', $aviso['text'] );

		$_GET[ Workspace::VAR_NOTICE ] = 'permiso';
		$this->assertSame( 'error', Workspace::model()['notice']['type'] );

		$_GET[ Workspace::VAR_NOTICE ] = 'lo-que-sea';
		$this->assertSame( '', Workspace::model()['notice']['type'] );
	}

	// ─── lo que pinta ──────────────────────────────────────────────────────

	/**
	 * La tabla se pinta con su fila, su estado, su recuento y sus dos accesos.
	 */
	public function test_the_table_is_painted_with_its_row() {
		$area  = $this->area( 'Área de prueba' );
		$curso = $this->course( '2026-2027' );
		$quien = $this->manager( array( $area ) );
		$id    = $this->procedure( $quien, array( $area ), $this->abierto(), array( 'post_title' => 'Red de centros de prueba' ) );
		wp_set_object_terms( $id, array( $curso ), ProcedureTaxonomies::COURSE );
		$this->application( $id, $this->school_head( 'C0001' ) );

		$this->acting_as( $quien );
		$html = Workspace::render();

		$this->assertStringContainsString( 'Red de centros de prueba', $html );
		$this->assertStringContainsString( 'Área de prueba', $html );
		$this->assertStringContainsString( '2026-2027', $html );
		$this->assertStringContainsString( 'Abierto', $html );
		$this->assertStringContainsString( 'Solicitudes', $html );
		$this->assertStringContainsString( 'Nuevo procedimiento', $html );
		$this->assertStringContainsString( 'Abrir el taller de este procedimiento', $html );
		$this->assertStringContainsString( 'Ver la ficha pública', $html );
		$this->assertStringContainsString( esc_url( Shell::url( 'editor', array( Shell::ARG_PROCEDURE => $id ) ) ), $html );
	}

	/**
	 * Un borrador se mira en previsualización: no tiene ficha pública todavía.
	 */
	public function test_a_draft_is_previewed_and_not_linked_as_public() {
		$area  = $this->area( 'Área de prueba' );
		$quien = $this->manager( array( $area ) );
		$this->procedure(
			$quien,
			array( $area ),
			array(),
			array(
				'post_title'  => 'Un borrador',
				'post_status' => 'draft',
			)
		);

		$this->acting_as( $quien );
		$m = Workspace::model();

		$this->assertSame( 'draft', $m['rows'][0]['status'] );
		$this->assertStringContainsString( 'preview', $m['rows'][0]['view_url'] );
		$this->assertStringContainsString( 'Previsualizar la ficha', Workspace::render() );
	}

	/**
	 * El buscador filtra en el servidor: título, número y ámbito.
	 */
	public function test_the_search_box_filters_on_the_server() {
		$area  = $this->area( 'Área de prueba' );
		$otra  = $this->area( 'Otro ámbito de prueba', $area );
		$quien = $this->manager( array( $area ) );
		$id    = $this->procedure( $quien, array( $area ), $this->abierto(), array( 'post_title' => 'Red de centros' ) );
		$this->procedure( $quien, array( $otra ), $this->abierto(), array( 'post_title' => 'Plan de lectura' ) );

		$this->acting_as( $quien );

		$_GET[ Workspace::VAR_SEARCH ] = 'red';
		$m                             = Workspace::model();
		$this->assertSame( array( 'Red de centros' ), $this->titulos( $m ), 'busca en el título, sin distinguir mayúsculas' );
		$this->assertSame( 1, $m['total'] );
		$this->assertSame( 1, $m['counts']['all'], 'el recuento cuenta lo que se enseña' );

		// El número del procedimiento es lo que se copia de un correo.
		$_GET[ Workspace::VAR_SEARCH ] = (string) $id;
		$this->assertSame( array( 'Red de centros' ), $this->titulos( Workspace::model() ) );

		// Y el ámbito que gestiona, que también se lee en la tabla.
		$_GET[ Workspace::VAR_SEARCH ] = 'Otro ámbito';
		$this->assertSame( array( 'Plan de lectura' ), $this->titulos( Workspace::model() ) );

		// Sin coincidencias se dice que es cosa del filtro, y se ofrece quitarlo.
		$_GET[ Workspace::VAR_SEARCH ] = 'no existe';
		$m                             = Workspace::model();
		$this->assertSame( array(), $m['rows'] );
		$this->assertStringContainsString( 'Pruebe a quitar algún filtro', $m['empty_text'] );
		$this->assertStringNotContainsString( Workspace::VAR_SEARCH, $m['reset_url'], 'quitar los filtros quita también la búsqueda' );

		// Y el buscador se pinta con lo que se buscó, para poder corregirlo.
		$_GET[ Workspace::VAR_SEARCH ] = 'red';
		$html                          = Workspace::render();
		$this->assertStringContainsString( 'Buscar en la tabla', $html );
		$this->assertStringContainsString( 'value="red"', $html );
		$seguido = (string) preg_replace( '/\s+/', ' ', $html );
		$this->assertStringContainsString( 'Mostrando <strong>1</strong> procedimiento de <strong>todos los cursos</strong> que contiene «<strong>red</strong>»:', $seguido, 'una fila es un procedimiento, no «1 procedimientos»' );
	}

	/**
	 * La pantalla es el calco: héroe, leyenda, pestañas por curso y tabla de
	 * dos niveles con la miniatura de color y las fechas como se leen.
	 */
	public function test_the_screen_is_the_copy_of_the_old_workshop() {
		$area  = $this->area( 'Área de prueba' );
		$viejo = $this->course( '2025-2026' );
		$nuevo = $this->course( '2026-2027' );
		$quien = $this->manager( array( $area ) );
		$id    = $this->procedure(
			$quien,
			array( $area ),
			array_merge(
				$this->abierto(),
				array( ProcedureMetaKeys::HEADER_COLOR => 'granate' )
			),
			array(
				'post_title' => 'Red de centros de prueba',
				'post_date'  => '2026-05-08 09:00:00',
			)
		);
		wp_set_object_terms( $id, array( $nuevo ), ProcedureTaxonomies::COURSE );
		$otro = $this->procedure( $quien, array( $area ), $this->abierto(), array( 'post_title' => 'La del curso pasado' ) );
		wp_set_object_terms( $otro, array( $viejo ), ProcedureTaxonomies::COURSE );
		$this->application( $id, $this->school_head( 'C0001' ) );

		$this->acting_as( $quien );
		$m       = Workspace::model();
		$html    = Workspace::render();
		$seguido = (string) preg_replace( '/\s+/', ' ', $html );

		// El título grande y centrado, el de la portada y el del taller.
		$this->assertStringContainsString( 'prc-hero', $html );

		// La leyenda: los siete estados del aplicativo, con su icono, más el
		// aviso en que se convirtieron las dos entradas contradictorias.
		$this->assertStringContainsString( 'prc-leyenda', $html );
		foreach ( Workspace::state_icons() as $glifo ) {
			$this->assertStringContainsString( '>' . $glifo . '<', $html );
		}
		$this->assertSame( 7, count( Workspace::state_icons() ) );
		$this->assertStringContainsString( 'fechas no cuadran', $html );

		// Una pestaña por curso, navegables por URL, más la de todos.
		// El rótulo de la pestaña es el nombre del término tal cual: en el sitio
		// el vocabulario ya se llama «Curso 2026-2027» y aquí «2026-2027».
		$this->assertStringContainsString( 'aria-current="page"> 2026-2027 </a>', $seguido );
		$this->assertStringContainsString( '> 2025-2026 </a>', $seguido );
		$this->assertStringContainsString( 'Todos los cursos', $html );
		$this->assertStringContainsString( esc_url( Workspace::url( $m['selection'], array( 'course' => $viejo ) ) ), $html );

		// El recuento del curso que se está mirando.
		$this->assertStringContainsString( 'Mostrando <strong>1</strong> procedimiento en <strong>2026-2027</strong>:', $seguido );

		// La tabla: dos niveles de cabecera, miniatura de color, «Dirigido a»,
		// las fechas en día-mes-año y la acción de solicitudes con su número.
		$this->assertStringContainsString( 'Detalles del procedimiento', $html );
		$this->assertStringContainsString( 'Fecha límite', $html );
		$this->assertStringContainsString( 'prc-tabla__grupo--3', $html );
		$this->assertStringContainsString( 'prc-mini-color', $html );
		$this->assertStringContainsString( ProcedureMetaKeys::header_hex( 'granate' ), $html );
		$this->assertStringContainsString( 'Dirigido a', $html );
		$this->assertStringContainsString( 'Centros educativos', $html );
		$this->assertStringContainsString( '08-05-2026', $html, 'la fecha de creación va en día-mes-año' );
		$this->assertSame( '08-05-2026', $m['rows'][0]['created'] );
		$this->assertSame( gmdate( 'd-m-Y', strtotime( '+3 days' ) ), $m['rows'][0]['closes'] );
		$this->assertStringContainsString( 'Ver solicitudes', $html );
		$this->assertStringContainsString( 'prc-accion__contador', $html );
	}

	/**
	 * Unas fechas que no cuadran son un aviso, no un estado.
	 *
	 * En el sistema que se sustituye, «abierta con el plazo vencido» y
	 * «cerrada con el plazo abierto» eran dos de los ocho iconos de la
	 * leyenda. Aquí el estado sale de las fechas y esas dos no pueden darse:
	 * lo que queda es el aviso de que alguien escribió unas fechas imposibles.
	 */
	public function test_dates_that_do_not_add_up_become_a_warning() {
		$area  = $this->area( 'Área de prueba' );
		$quien = $this->manager( array( $area ) );
		$this->procedure( $quien, array( $area ), $this->abierto(), array( 'post_title' => 'La que cuadra' ) );
		$this->procedure(
			$quien,
			array( $area ),
			array(
				ProcedureMetaKeys::OPENS_AT  => '2026-06-01',
				ProcedureMetaKeys::CLOSES_AT => '2026-05-01',
			),
			array( 'post_title' => 'La del plazo al revés' )
		);
		$this->procedure(
			$quien,
			array( $area ),
			array_merge(
				$this->abierto(),
				array( ProcedureMetaKeys::AMEND_OPENS_AT => gmdate( 'Y-m-d', strtotime( '-1 day' ) ) )
			),
			array( 'post_title' => 'La de la subsanación adelantada' )
		);

		$this->acting_as( $quien );
		$avisos = array_combine( $this->titulos( Workspace::model() ), array_column( Workspace::model()['rows'], 'warning' ) );

		$this->assertSame( '', $avisos['La que cuadra'] );
		$this->assertStringContainsString( 'cierra antes de abrirse', $avisos['La del plazo al revés'] );
		$this->assertStringContainsString( 'subsanación empieza antes', $avisos['La de la subsanación adelantada'] );
		$this->assertStringContainsString( 'cierra antes de abrirse', Workspace::render() );
	}

	/**
	 * El arranque engancha el shortcode.
	 */
	public function test_register_hooks_the_shortcode() {
		Workspace::register();

		$this->assertTrue( shortcode_exists( Workspace::SHORTCODE ) );
		$this->assertSame( Workspace::SHORTCODE, Shell::SHORTCODES['workspace'] );
	}
}
