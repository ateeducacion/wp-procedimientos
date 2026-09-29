<?php
/**
 * Tests for the two procedure taxonomies.
 *
 * @package Prc
 */

use Prc\Access\ProcedureAccess;
use Prc\PostType\ProcedurePostType;
use Prc\Taxonomy\ProcedureTaxonomies;

/**
 * Las dos taxonomías: el ámbito, que es el eje de permisos, y el curso.
 */
class Test_Taxonomies extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Tipos, taxonomías y roles registrados.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
	}

	/**
	 * Las dos están montadas sobre el procedimiento y solo sobre él: la
	 * solicitud hereda el ámbito de su procedimiento.
	 */
	public function test_the_two_taxonomies_sit_on_the_procedure() {
		foreach ( array( ProcedureTaxonomies::AREA, ProcedureTaxonomies::COURSE ) as $slug ) {
			$this->assertTrue( taxonomy_exists( $slug ), $slug );

			$tax = get_taxonomy( $slug );
			$this->assertSame( array( ProcedurePostType::POST_TYPE ), $tax->object_type, $slug );
			$this->assertTrue( $tax->show_in_rest, $slug );
			$this->assertTrue( $tax->show_admin_column, $slug );
		}
	}

	/**
	 * El ámbito es jerárquico —servicio → área—; el curso, plano.
	 */
	public function test_the_area_nests_and_the_course_does_not() {
		$this->assertTrue( get_taxonomy( ProcedureTaxonomies::AREA )->hierarchical );
		$this->assertFalse( get_taxonomy( ProcedureTaxonomies::COURSE )->hierarchical );

		$servicio = $this->area( 'Servicio de prueba' );
		$area     = $this->area( 'Área de prueba', $servicio );
		$this->assertSame( array( $area ), get_term_children( $servicio, ProcedureTaxonomies::AREA ) );
	}

	/**
	 * El estado no es una taxonomía: se calcula de las fechas (ADR-0012).
	 */
	public function test_the_state_is_not_a_taxonomy() {
		$this->assertFalse( taxonomy_exists( 'prc_state' ) );
		$this->assertFalse( taxonomy_exists( 'prc_type' ) );
	}

	/**
	 * Las capacidades de los términos son las del contrato y existen de verdad.
	 */
	public function test_the_term_capabilities_exist_for_real() {
		$tax = get_taxonomy( ProcedureTaxonomies::AREA );

		$this->assertSame( ProcedureAccess::CAP_MANAGE, $tax->cap->manage_terms );
		$this->assertSame( ProcedureAccess::CAP_MANAGE, $tax->cap->edit_terms );
		$this->assertSame( ProcedureAccess::CAP_MANAGE, $tax->cap->delete_terms );
		$this->assertSame( ProcedureAccess::CAP_MANAGE_PROCEDURES, $tax->cap->assign_terms );

		$admin = $this->administrator();
		foreach ( (array) $tax->cap as $clave => $cap ) {
			$this->assertTrue( user_can( $admin, $cap ), $clave . ' → ' . $cap );
		}

		// La gestión marca el ámbito de su procedimiento, pero no inventa ámbitos.
		$manager = $this->manager();
		$this->assertTrue( user_can( $manager, $tax->cap->assign_terms ) );
		$this->assertFalse( user_can( $manager, $tax->cap->manage_terms ) );
	}

	/**
	 * Y un término se le puede poner de verdad a un procedimiento.
	 */
	public function test_terms_land_on_the_procedure() {
		$area          = $this->area( 'Área de prueba' );
		$curso         = $this->course( '2026-2027' );
		$procedimiento = $this->procedure( $this->administrator(), array( $area ) );
		wp_set_object_terms( $procedimiento, array( $curso ), ProcedureTaxonomies::COURSE );

		$ambitos = get_the_terms( $procedimiento, ProcedureTaxonomies::AREA );
		$this->assertIsArray( $ambitos );
		$this->assertSame( array( $area ), array_map( 'intval', wp_list_pluck( $ambitos, 'term_id' ) ) );

		$cursos = get_the_terms( $procedimiento, ProcedureTaxonomies::COURSE );
		$this->assertSame( array( '2026-2027' ), wp_list_pluck( $cursos, 'name' ) );
	}

	/** Scope contact metadata requires administration and valid values. */
	public function test_scope_contact_metadata() {
		$area  = $this->area( 'Servicio' );
		$admin = $this->administrator();
		$image = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		$this->acting_as( $admin );
		$_POST = array(
			'prc_scope_meta_nonce'        => wp_create_nonce( 'prc_scope_meta' ),
			ProcedureTaxonomies::EMAIL    => ' Contacto@Example.org ',
			ProcedureTaxonomies::IMAGE_ID => (string) $image,
		);
		ProcedureTaxonomies::save_fields( $area );
		$this->assertSame( 'Contacto@Example.org', get_term_meta( $area, ProcedureTaxonomies::EMAIL, true ) );
		$this->assertSame( $image, (int) get_term_meta( $area, ProcedureTaxonomies::IMAGE_ID, true ) );
		$_POST[ ProcedureTaxonomies::EMAIL ]    = '@@@';
		$_POST[ ProcedureTaxonomies::IMAGE_ID ] = 'not-an-image';
		ProcedureTaxonomies::save_fields( $area );
		$this->assertSame( 'Contacto@Example.org', get_term_meta( $area, ProcedureTaxonomies::EMAIL, true ) );
		$this->assertSame( $image, (int) get_term_meta( $area, ProcedureTaxonomies::IMAGE_ID, true ) );
		$this->acting_as( $this->manager() );
		$_POST[ ProcedureTaxonomies::EMAIL ] = 'attacker@example.org';
		ProcedureTaxonomies::save_fields( $area );
		$this->assertSame( 'Contacto@Example.org', get_term_meta( $area, ProcedureTaxonomies::EMAIL, true ) );
		$this->acting_as( $admin );
		$_POST[ ProcedureTaxonomies::EMAIL ]    = '';
		$_POST[ ProcedureTaxonomies::IMAGE_ID ] = '0';
		ProcedureTaxonomies::save_fields( $area );
		$this->assertFalse( metadata_exists( 'term', $area, ProcedureTaxonomies::EMAIL ) );
		$this->assertFalse( metadata_exists( 'term', $area, ProcedureTaxonomies::IMAGE_ID ) );
		$_POST = array();
	}

	/** Native selectors expose only effective descendants with full paths. */
	public function test_scope_options_follow_the_tree() {
		$root    = $this->area( 'Ámbito general' );
		$service = $this->area( 'Ámbito 1', $root );
		$child   = $this->area( 'Subámbito', $service );
		$other   = $this->area( 'Ámbito 2', $root );
		$editor  = (int) self::factory()->user->create( array( 'role' => 'editor' ) );
		update_user_meta( $editor, ProcedureAccess::USER_AREA_META, array( $service ) );
		$this->assertEqualsCanonicalizing( array( $service, $child ), array_keys( ProcedureTaxonomies::area_options( $editor ) ) );
		$this->assertSame( 'Ámbito general › Ámbito 1 › Subámbito', ProcedureTaxonomies::area_options( $editor )[ $child ] );
		$this->assertArrayNotHasKey( $other, ProcedureTaxonomies::area_options( $editor ) );
		wp_set_current_user( $editor );
		$this->assertEqualsCanonicalizing( array( $service, $child ), ProcedureTaxonomies::rest_area_query( array() )['include'] );
	}
}
