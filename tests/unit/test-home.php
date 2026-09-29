<?php
/**
 * Tests for the public front page: what it groups, what it filters and what it says.
 *
 * @package Prc
 */

use Prc\Meta\ProcedureMetaKeys;
use Prc\PublicFront\Home;
use Prc\PublicFront\Shell;
use Prc\Taxonomy\ProcedureTaxonomies;

/**
 * La portada pública del aplicativo.
 *
 * Es lo que hoy es una rejilla del constructor de páginas con iconos de
 * estado marcados a mano. Aquí el estado sale de las fechas y la portada
 * agrupa por él dentro de un curso. Se lee sin sesión: lo que se prueba es
 * que agrupa por el estado derivado, que acota por ámbito y que no ofrece
 * nunca un filtro que dejaría la pantalla vacía.
 */
class Test_Home extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Con el aplicativo y sus páginas.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
		$this->pages();
	}

	/**
	 * Un procedimiento publicado con su curso, su ámbito y sus fechas.
	 *
	 * @param string               $titulo Post title.
	 * @param int                  $curso  Term ID of prc_course.
	 * @param int                  $area   Term ID of prc_area, 0 for none.
	 * @param array<string, mixed> $meta   Meta key => value.
	 * @param array<string, mixed> $args   Post fields to override.
	 * @return int Post ID.
	 */
	private function convocatoria( string $titulo, int $curso, int $area = 0, array $meta = array(), array $args = array() ): int {
		$id = $this->procedure(
			$this->administrator(),
			$area > 0 ? array( $area ) : array(),
			$meta,
			array_merge( array( 'post_title' => $titulo ), $args )
		);
		wp_set_object_terms( $id, array( $curso ), ProcedureTaxonomies::COURSE );
		return $id;
	}

	/**
	 * Las fechas de un plazo que está abierto hoy.
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

	// ─── lo que decide ─────────────────────────────────────────────────────

	/**
	 * La portada agrupa por el estado derivado, y en el orden en que se lee.
	 */
	public function test_the_front_page_groups_by_the_derived_state() {
		$curso = $this->course( '2026-2027' );
		$area  = $this->area( 'Área de prueba' );

		$this->convocatoria( 'La que está abierta', $curso, $area, $this->abierto() );
		$this->convocatoria( 'La que ya cerró', $curso, $area, $this->cerrado() );
		$this->convocatoria( 'La que viene', $curso, $area, array( ProcedureMetaKeys::OPENS_AT => gmdate( 'Y-m-d', strtotime( '+30 days' ) ) ) );
		// Un borrador no es de la portada: todavía no se ha publicado.
		$this->convocatoria( 'La que se está escribiendo', $curso, $area, $this->abierto(), array( 'post_status' => 'draft' ) );

		$this->acting_as( 0 );
		$m = Home::model();

		$this->assertSame( 3, $m['total'], 'el borrador no cuenta' );
		$this->assertSame(
			array( ProcedureMetaKeys::STATE_OPEN, ProcedureMetaKeys::STATE_UPCOMING, ProcedureMetaKeys::STATE_CLOSED ),
			array_keys( $m['groups'] ),
			'el orden es el de Home::ORDER, no el de la base de datos'
		);
		$this->assertSame( 'La que está abierta', $m['groups'][ ProcedureMetaKeys::STATE_OPEN ]['rows'][0]['title'] );
		$this->assertSame( 'Abierto', $m['groups'][ ProcedureMetaKeys::STATE_OPEN ]['label'] );
		$this->assertStringContainsString( 'Del ', $m['groups'][ ProcedureMetaKeys::STATE_OPEN ]['rows'][0]['dates'] );
	}

	/**
	 * Se enseña un curso, y sin pedir ninguno el más reciente.
	 */
	public function test_the_newest_course_is_the_one_on_screen() {
		$viejo = $this->course( '2025-2026' );
		$nuevo = $this->course( '2026-2027' );
		$area  = $this->area( 'Área de prueba' );

		$this->convocatoria( 'La del curso pasado', $viejo, $area, $this->abierto() );
		$this->convocatoria( 'La de este curso', $nuevo, $area, $this->abierto() );

		$this->acting_as( 0 );

		$m = Home::model();
		$this->assertSame( $nuevo, $m['course'] );
		$this->assertSame( 1, $m['total'] );
		$this->assertSame( 'La de este curso', $m['groups'][ ProcedureMetaKeys::STATE_OPEN ]['rows'][0]['title'] );
		$this->assertSame( array( $nuevo, $viejo ), array_keys( $m['courses'] ), 'el más reciente primero' );

		$_GET[ Home::VAR_COURSE ] = (string) $viejo;
		$m                        = Home::model();
		$this->assertSame( $viejo, $m['course'] );
		$this->assertSame( 'La del curso pasado', $m['groups'][ ProcedureMetaKeys::STATE_OPEN ]['rows'][0]['title'] );

		// Un curso que no está se ignora y se vuelve al de siempre.
		$_GET[ Home::VAR_COURSE ] = '999999';
		$this->assertSame( $nuevo, Home::model()['course'] );
	}

	/**
	 * El ámbito acota lo que se enseña, y solo se ofrecen los que hay.
	 */
	public function test_the_area_filter_narrows_the_list() {
		$curso = $this->course( '2026-2027' );
		$una   = $this->area( 'Área de prueba' );
		$otra  = $this->area( 'Otro ámbito' );
		$nadie = $this->area( 'Ámbito sin nada' );

		$this->convocatoria( 'La de un ámbito', $curso, $una, $this->abierto() );
		$this->convocatoria( 'La del otro', $curso, $otra, $this->abierto() );

		$this->acting_as( 0 );

		$m = Home::model();
		$this->assertSame( array( $otra, $una ), array_keys( $m['areas'] ), 'solo los ámbitos que tienen algo, por nombre' );
		$this->assertArrayNotHasKey( $nadie, $m['areas'], 'un filtro que dejaría la pantalla vacía no se ofrece' );
		$this->assertSame( 0, $m['area'] );
		$this->assertSame( '', $m['reset_url'], 'sin filtrar no hay nada que quitar' );

		$_GET[ Home::VAR_AREA ] = (string) $una;
		$m                      = Home::model();
		$this->assertSame( $una, $m['area'] );
		$this->assertSame( 1, $m['total'] );
		$this->assertSame( 'La de un ámbito', $m['groups'][ ProcedureMetaKeys::STATE_OPEN ]['rows'][0]['title'] );
		$this->assertSame( Home::url( $curso ), $m['reset_url'] );

		// Un ámbito que no está entre las opciones se ignora.
		$_GET[ Home::VAR_AREA ] = (string) $nadie;
		$this->assertSame( 0, Home::model()['area'] );
		$this->assertSame( 2, Home::model()['total'] );
	}

	/**
	 * Cuando no hay nada se dice por qué, y no es lo mismo estar filtrando.
	 */
	public function test_it_says_why_there_is_nothing() {
		$this->acting_as( 0 );

		$m = Home::model();
		$this->assertSame( array(), $m['groups'] );
		$this->assertStringContainsString( 'Todavía no hay ningún procedimiento', $m['empty_text'] );

		// Con un ámbito elegido, lo que se dice es otra cosa: el filtro tiene
		// arreglo y la portada vacía, no.
		$curso = $this->course( '2026-2027' );
		$una   = $this->area( 'Área de prueba' );
		$otra  = $this->area( 'Otro ámbito' );
		$this->convocatoria( 'La de un ámbito', $curso, $una, $this->abierto() );
		$this->convocatoria( 'La del otro', $curso, $otra, $this->cerrado() );

		$_GET[ Home::VAR_AREA ] = (string) $otra;
		$m                      = Home::model();
		$this->assertSame( 1, $m['total'] );
		$this->assertStringContainsString( 'Pruebe con otro ámbito', $m['empty_text'] );
		$this->assertSame( Home::url( $curso ), $m['reset_url'], 'y se ofrece quitarlo' );
	}

	/**
	 * Lo que se lee de la URL es un entero, y nunca negativo.
	 */
	public function test_the_navigation_parameters_are_numbers() {
		$this->assertSame( 0, Home::input( Home::VAR_COURSE ) );

		$_GET[ Home::VAR_COURSE ] = '12';
		$this->assertSame( 12, Home::input( Home::VAR_COURSE ) );

		$_GET[ Home::VAR_COURSE ] = '-3';
		$this->assertSame( 0, Home::input( Home::VAR_COURSE ) );

		$_GET[ Home::VAR_COURSE ] = 'hola';
		$this->assertSame( 0, Home::input( Home::VAR_COURSE ) );

		$_GET[ Home::VAR_COURSE ] = array( '1' );
		$this->assertSame( 0, Home::input( Home::VAR_COURSE ), 'lo que no es una cadena no se mira' );
	}

	/**
	 * `url()` lleva puesto lo que está elegido, y nada más.
	 */
	public function test_the_url_keeps_what_is_selected() {
		$portada = Shell::url( 'home' );

		$this->assertSame( $portada, Home::url( 0 ) );
		$this->assertStringContainsString( Home::VAR_COURSE . '=5', Home::url( 5 ) );
		$this->assertStringNotContainsString( Home::VAR_AREA, Home::url( 5 ) );
		$this->assertStringContainsString( Home::VAR_AREA . '=7', Home::url( 5, 7 ) );
	}

	// ─── lo que pinta ──────────────────────────────────────────────────────

	/**
	 * La portada se pinta, y se pinta sin sesión: es pública.
	 */
	public function test_the_front_page_is_painted_for_whoever_visits() {
		$curso = $this->course( '2026-2027' );
		$area  = $this->area( 'Área de prueba' );
		$this->convocatoria( 'Red de centros de prueba', $curso, $area, $this->abierto() );

		$this->acting_as( 0 );
		$html = Home::render();

		$this->assertStringContainsString( 'Red de centros de prueba', $html );
		$this->assertStringContainsString( 'Abierto', $html );
		$this->assertStringContainsString( 'prc-grupo-' . ProcedureMetaKeys::STATE_OPEN, $html );
		$this->assertStringContainsString( 'prc-hero', $html, 'la portada lleva el título grande, no el de pantalla interior' );
		$this->assertStringContainsString( 'prc-proc__banda', $html, 'y cada tarjeta su banda de color' );
		$this->assertStringContainsString( 'Cierra en 3 días', $html, 'con lo que queda de plazo' );
		$this->assertStringContainsString( 'Acceder', $html, 'sin sesión se ofrece entrar' );
		$this->assertTrue( wp_style_is( 'prc-app', 'enqueued' ), 'y la hoja del aplicativo se encola' );
	}

	/**
	 * Con un solo ámbito no hay nada que elegir: ese eje del filtro no se pinta.
	 *
	 * El de estado sí sale con una sola opción —«Todos» y ese estado—, que es
	 * exactamente lo que enseña la portada que se sustituye.
	 */
	public function test_an_axis_with_one_option_is_not_painted() {
		$curso = $this->course( '2026-2027' );
		$area  = $this->area( 'Área de prueba' );
		$this->convocatoria( 'La única', $curso, $area, $this->abierto() );

		$this->acting_as( 0 );
		$html = Home::render();
		$this->assertStringNotContainsString( 'Todos los ámbitos', $html );
		$this->assertStringNotContainsString( 'Curso escolar', $html );
		$this->assertStringContainsString( 'prc-filtros', $html, 'pero el de estado sí' );

		$otro = $this->area( 'Otro ámbito' );
		$this->convocatoria( 'La otra', $curso, $otro, $this->abierto() );
		$html = Home::render();
		$this->assertStringContainsString( 'Todos los ámbitos', $html );
		$this->assertStringContainsString( 'Otro ámbito', $html );
		$this->assertStringNotContainsString( '<form', $html, 'el filtro son enlaces: funciona sin JavaScript' );
	}

	// ─── el calco de la portada ────────────────────────────────────────────

	/**
	 * El filtro de estado deja un solo bloque, y lo que no está se ignora.
	 */
	public function test_the_state_filter_leaves_one_block() {
		$curso = $this->course( '2026-2027' );
		$area  = $this->area( 'Área de prueba' );
		$this->convocatoria( 'La que está abierta', $curso, $area, $this->abierto() );
		$this->convocatoria( 'La que ya cerró', $curso, $area, $this->cerrado() );

		$this->acting_as( 0 );

		$m = Home::model();
		$this->assertSame( '', $m['state'] );
		$this->assertSame(
			array( ProcedureMetaKeys::STATE_OPEN, ProcedureMetaKeys::STATE_CLOSED ),
			array_keys( $m['states'] ),
			'solo se ofrecen los estados que tienen algo'
		);

		$_GET[ Home::VAR_STATE ] = ProcedureMetaKeys::STATE_CLOSED;
		$m                       = Home::model();
		$this->assertSame( ProcedureMetaKeys::STATE_CLOSED, $m['state'] );
		$this->assertSame( array( ProcedureMetaKeys::STATE_CLOSED ), array_keys( $m['groups'] ) );
		$this->assertSame( 2, $m['total'], 'el recuento del curso no lo toca el filtro' );
		$this->assertStringContainsString( Home::VAR_STATE . '=' . ProcedureMetaKeys::STATE_CLOSED, Home::url( $curso, 0, ProcedureMetaKeys::STATE_CLOSED ) );

		// Un estado sin nada, o que ni siquiera es un estado, se ignora.
		$_GET[ Home::VAR_STATE ] = ProcedureMetaKeys::STATE_RESOLVED;
		$this->assertSame( '', Home::model()['state'] );
		$_GET[ Home::VAR_STATE ] = 'lo-que-sea';
		$this->assertSame( '', Home::model()['state'] );
		$this->assertStringNotContainsString( Home::VAR_STATE, Home::url( $curso, 0, 'lo-que-sea' ) );
	}

	/**
	 * Dentro de un bloque manda lo que cierra antes, no el alfabeto.
	 */
	public function test_what_closes_first_comes_first() {
		$curso = $this->course( '2026-2027' );
		$area  = $this->area( 'Área de prueba' );

		$this->convocatoria(
			'Aaa, la que cierra la semana que viene',
			$curso,
			$area,
			array(
				ProcedureMetaKeys::OPENS_AT  => gmdate( 'Y-m-d', strtotime( '-3 days' ) ),
				ProcedureMetaKeys::CLOSES_AT => gmdate( 'Y-m-d', strtotime( '+7 days' ) ),
			)
		);
		$this->convocatoria(
			'Zzz, la que cierra mañana',
			$curso,
			$area,
			array(
				ProcedureMetaKeys::OPENS_AT  => gmdate( 'Y-m-d', strtotime( '-3 days' ) ),
				ProcedureMetaKeys::CLOSES_AT => gmdate( 'Y-m-d', strtotime( '+1 day' ) ),
			)
		);

		$this->acting_as( 0 );
		$filas = Home::model()['groups'][ ProcedureMetaKeys::STATE_OPEN ]['rows'];

		$this->assertSame( 'Zzz, la que cierra mañana', $filas[0]['title'], 'lo que vence antes, primero' );
		$this->assertSame( 1, $filas[0]['days_left'] );
		$this->assertSame( 'Cierra mañana', $filas[0]['deadline'] );
		$this->assertTrue( $filas[0]['urgent'] );
		$this->assertSame( 'Cierra en 7 días', $filas[1]['deadline'] );
		$this->assertFalse( $filas[1]['urgent'] );
	}

	/**
	 * El último día se dice que lo es, y lo que no tiene plazo no dice nada.
	 */
	public function test_the_last_day_says_so() {
		$curso = $this->course( '2026-2027' );
		$hoy   = gmdate( 'Y-m-d' );

		$this->convocatoria(
			'La que cierra hoy',
			$curso,
			0,
			array(
				ProcedureMetaKeys::OPENS_AT  => gmdate( 'Y-m-d', strtotime( '-3 days' ) ),
				ProcedureMetaKeys::CLOSES_AT => $hoy,
			)
		);
		$this->convocatoria( 'La que viene sin fechas', $curso, 0, array() );

		$this->acting_as( 0 );
		$m = Home::model();

		$this->assertSame( 'Último día', $m['groups'][ ProcedureMetaKeys::STATE_OPEN ]['rows'][0]['deadline'] );
		$this->assertSame( '', $m['groups'][ ProcedureMetaKeys::STATE_UPCOMING ]['rows'][0]['deadline'], 'sin plazo no hay cuenta atrás' );
		$this->assertNull( $m['groups'][ ProcedureMetaKeys::STATE_UPCOMING ]['rows'][0]['days_left'] );
	}

	/**
	 * Con sesión, la tarjeta dice si el centro de quien mira ya ha solicitado.
	 */
	public function test_the_card_says_when_the_school_already_applied() {
		$curso  = $this->course( '2026-2027' );
		$pedida = $this->convocatoria( 'La que ya pidió', $curso, 0, $this->abierto() );
		$this->convocatoria( 'La que no', $curso, 0, $this->abierto() );

		$director = $this->school_head( '90000104' );
		$this->application( $pedida, $director );

		// Una visita anónima ve la portada de siempre.
		$this->acting_as( 0 );
		$filas = Home::model()['groups'][ ProcedureMetaKeys::STATE_OPEN ]['rows'];
		$this->assertFalse( $filas[0]['applied'] );
		$this->assertFalse( $filas[1]['applied'] );

		$this->acting_as( $director );
		$suyas = array();
		foreach ( Home::model()['groups'][ ProcedureMetaKeys::STATE_OPEN ]['rows'] as $fila ) {
			$suyas[ $fila['title'] ] = (bool) $fila['applied'];
		}
		$this->assertTrue( $suyas['La que ya pidió'] );
		$this->assertFalse( $suyas['La que no'] );
		$this->assertStringContainsString( 'Su centro ya ha solicitado', Home::render() );
	}

	/**
	 * El arranque engancha el shortcode.
	 */
	public function test_register_hooks_the_shortcode() {
		Home::register();

		$this->assertTrue( shortcode_exists( Home::SHORTCODE ) );
		$this->assertSame( Home::SHORTCODE, Shell::SHORTCODES['home'] );
	}
}
