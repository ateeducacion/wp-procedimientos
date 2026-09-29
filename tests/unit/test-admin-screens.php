<?php
/**
 * Tests for the screens the application adds to the WordPress desktop.
 *
 * @package Prc
 */

use Prc\Access\ProcedureAccess;
use Prc\Admin\ProcedureAdmin;
use Prc\Admin\Settings;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ApplicationPostType;
use Prc\PostType\ProcedurePostType;

/**
 * El escritorio: las columnas de los dos listados, su acotado por ámbito y
 * la pantalla de diagnóstico.
 *
 * La de diagnóstico es justo la que se abre cuando algo no está donde
 * debería, y una columna que revienta tumba el listado entero.
 */
class Test_Admin_Screens extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Con el aplicativo arrancado.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
	}

	/**
	 * Sin dejar el escritorio simulado puesto para el siguiente test.
	 */
	public function tear_down() {
		unset( $GLOBALS['current_screen'] );
		parent::tear_down();
	}

	// ─── Las columnas del listado de procedimientos ────────────────────────

	/**
	 * Ámbito y estado entran justo detrás del título, y no al final.
	 */
	public function test_the_columns_go_right_after_the_title() {
		$columnas = ProcedureAdmin::columns(
			array(
				'cb'    => '',
				'title' => 'Título',
				'date'  => 'Fecha',
			)
		);

		$this->assertSame( array( 'cb', 'title', 'prc_area', 'prc_state', 'date' ), array_keys( $columnas ) );
		$this->assertSame( 'Ámbito', $columnas['prc_area'] );
		$this->assertSame( 'Estado', $columnas['prc_state'] );
	}

	/**
	 * La columna de ámbito enseña los términos; sin ámbito, una raya.
	 */
	public function test_the_area_column_shows_the_terms_or_a_dash() {
		$con = $this->procedure( $this->administrator(), array( $this->area( 'Innovación de prueba' ) ) );
		$sin = $this->procedure( $this->administrator() );

		$this->assertSame( 'Innovación de prueba', $this->celda( 'prc_area', $con ) );
		$this->assertSame( '—', $this->celda( 'prc_area', $sin ) );
	}

	/**
	 * El estado sale derivado, con su etiqueta, y dice cuándo es histórico.
	 */
	public function test_the_state_column_derives_from_the_dates() {
		$procedimiento = $this->procedure(
			$this->administrator(),
			array(),
			array(
				ProcedureMetaKeys::OPENS_AT  => '2020-01-01',
				ProcedureMetaKeys::CLOSES_AT => '2020-01-02',
			)
		);

		$this->assertSame( 'Cerrado', $this->celda( 'prc_state', $procedimiento ) );

		update_post_meta( $procedimiento, ProcedureMetaKeys::ARCHIVED, '1' );
		$this->assertSame( 'Histórico', $this->celda( 'prc_state', $procedimiento ) );
	}

	/**
	 * Una columna que no es nuestra no pinta nada.
	 */
	public function test_a_column_of_somebody_else_prints_nothing() {
		$procedimiento = $this->procedure( $this->administrator() );
		$this->assertSame( '', $this->celda( 'author', $procedimiento ) );
	}

	// ─── Las columnas del listado de solicitudes ───────────────────────────

	/**
	 * Procedimiento, centro y revisión, detrás del título.
	 */
	public function test_the_application_columns_say_what_and_who() {
		$columnas = ProcedureAdmin::application_columns(
			array(
				'cb'    => '',
				'title' => 'Título',
				'date'  => 'Fecha',
			)
		);
		$this->assertSame( array( 'cb', 'title', 'prc_procedure', 'prc_centre', 'prc_review', 'date' ), array_keys( $columnas ) );

		$procedimiento = $this->procedure( $this->administrator(), array(), array(), array( 'post_title' => 'Red de prueba' ) );
		$solicitud     = $this->application( $procedimiento, $this->school_head( 'C0001' ), '', array( ApplicationMetaKeys::REVIEW_STATE => 'amend' ) );

		$this->assertSame( 'Red de prueba', $this->celda_solicitud( 'prc_procedure', $solicitud ) );
		$this->assertSame( 'Centro de prueba (C0001)', $this->celda_solicitud( 'prc_centre', $solicitud ) );
		$this->assertSame( 'A subsanar', $this->celda_solicitud( 'prc_review', $solicitud ) );
	}

	// ─── El acotado de los listados ────────────────────────────────────────

	/**
	 * Cada ámbito ve sus procedimientos y los de sus áreas hijas, y nada más;
	 * sin ámbito, ninguno; administración, todos.
	 */
	public function test_the_procedure_list_is_scoped_by_area() {
		$servicio = $this->area( 'Servicio de prueba' );
		$area     = $this->area( 'Área hija', $servicio );
		$otro     = $this->area( 'Otro ámbito' );
		$admin    = $this->administrator();
		$mio      = $this->procedure( $admin, array( $servicio ) );
		$hijo     = $this->procedure( $admin, array( $area ) );
		$ajeno    = $this->procedure( $admin, array( $otro ) );

		$this->acting_as( $this->manager( array( $servicio ) ) );
		$this->assertEqualsCanonicalizing( array( $mio, $hijo ), $this->listado( ProcedurePostType::POST_TYPE ) );

		$this->acting_as( $this->manager() );
		$this->assertSame( array(), $this->listado( ProcedurePostType::POST_TYPE ), 'sin ámbito, ni una fila' );

		$this->acting_as( $admin );
		$this->assertEqualsCanonicalizing( array( $mio, $hijo, $ajeno ), $this->listado( ProcedurePostType::POST_TYPE ) );
	}

	/**
	 * Y las solicitudes se acotan por el procedimiento del que cuelgan.
	 */
	public function test_the_application_list_is_scoped_by_the_procedure_area() {
		$area  = $this->area( 'Área de prueba' );
		$otro  = $this->area( 'Otro ámbito' );
		$admin = $this->administrator();
		$head  = $this->school_head( 'C0001' );
		$mia   = $this->application( $this->procedure( $admin, array( $area ) ), $head );
		$ajena = $this->application( $this->procedure( $admin, array( $otro ) ), $head );

		$this->acting_as( $this->manager( array( $area ) ) );
		$this->assertSame( array( $mia ), $this->listado( ApplicationPostType::POST_TYPE ) );

		$this->acting_as( $this->manager() );
		$this->assertSame( array(), $this->listado( ApplicationPostType::POST_TYPE ) );

		$this->acting_as( $admin );
		$this->assertEqualsCanonicalizing( array( $mia, $ajena ), $this->listado( ApplicationPostType::POST_TYPE ) );
	}

	/**
	 * Fuera del escritorio el acotado no toca nada: el frontal hace el suyo.
	 */
	public function test_outside_the_desktop_the_query_is_left_alone() {
		$area  = $this->area( 'Área de prueba' );
		$ajeno = $this->procedure( $this->administrator(), array( $area ) );
		$this->acting_as( $this->manager() );

		$this->assertSame( array( $ajeno ), $this->listado( ProcedurePostType::POST_TYPE, false ) );
	}

	// ─── La pantalla de diagnóstico ────────────────────────────────────────

	/**
	 * Quien administra ve el diagnóstico, con los tipos, las taxonomías y el catálogo.
	 */
	public function test_the_diagnostics_screen_lists_what_is_registered() {
		$this->acting_as( $this->administrator() );
		$this->assertTrue( ProcedureAccess::is_manager() );

		ob_start();
		Settings::render();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'Ajustes y diagnóstico de procedimientos', $html );
		foreach ( array( 'Procedimientos', 'Solicitudes', 'Ámbito', 'Curso escolar', 'Catálogo de centros', 'todos los ámbitos' ) as $rotulo ) {
			$this->assertStringContainsString( $rotulo, $html );
		}
		$this->assertStringContainsString( '<td>Registrado</td>', $html );
		$this->assertStringNotContainsString( '<td>Sin registrar</td>', $html );
	}

	/**
	 * La pantalla está en el menú, y colgando del listado de procedimientos.
	 */
	public function test_the_diagnostics_screen_hangs_from_the_procedures_menu() {
		global $submenu;
		$this->acting_as( $this->administrator() );

		$submenu = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- se restaura solo entre tests.
		Settings::menu();

		$padre = 'edit.php?post_type=' . ProcedurePostType::POST_TYPE;
		$this->assertArrayHasKey( $padre, $submenu );
		$this->assertContains( Settings::PAGE, wp_list_pluck( $submenu[ $padre ], 2 ) );
	}

	/**
	 * Lo que pinta una celda del listado de procedimientos.
	 *
	 * @param string $columna Column key.
	 * @param int    $post_id Post ID.
	 * @return string
	 */
	private function celda( string $columna, int $post_id ): string {
		ob_start();
		ProcedureAdmin::column_content( $columna, $post_id );
		return trim( (string) ob_get_clean() );
	}

	/**
	 * Lo que pinta una celda del listado de solicitudes.
	 *
	 * @param string $columna Column key.
	 * @param int    $post_id Post ID.
	 * @return string
	 */
	private function celda_solicitud( string $columna, int $post_id ): string {
		ob_start();
		ProcedureAdmin::application_column_content( $columna, $post_id );
		return trim( (string) ob_get_clean() );
	}

	/**
	 * The IDs the main list query returns for whoever is logged in.
	 *
	 * La consulta principal del escritorio, simulada: `set_current_screen()`
	 * hace que `is_admin()` diga que sí, y ponerla en `$wp_the_query` hace
	 * que `is_main_query()` diga que sí. `pre_get_posts` hace el resto.
	 *
	 * @param string $tipo      Post type.
	 * @param bool   $escritorio Whether to simulate the desktop.
	 * @return int[]
	 */
	private function listado( string $tipo, bool $escritorio = true ): array {
		if ( $escritorio ) {
			set_current_screen( 'edit-' . $tipo );
		} else {
			unset( $GLOBALS['current_screen'] );
		}
		$query                   = new WP_Query();
		$GLOBALS['wp_the_query'] = $query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- WP_UnitTestCase lo repone en tear_down.
		$query->query(
			array(
				'post_type'      => $tipo,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);
		return array_map( 'intval', $query->posts );
	}
}
