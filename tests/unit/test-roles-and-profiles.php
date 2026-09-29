<?php
/**
 * Tests for the product roles and the ámbitos / school code profile fields.
 *
 * @package Prc
 */

use Prc\Access\CentreScope;

/**
 * El snippet suelto de roles y perfiles.
 *
 * Es aditivo a propósito: crea lo que falta y no quita nada, para que lo que se
 * conceda a mano en WPFront siga ahí en la siguiente carga.
 */
class Test_Roles_And_Profiles extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Los dos roles que crea el aplicativo. El otro perfil es `administrator`,
	 * el nativo de WordPress, que no se crea: solo recibe capacidades.
	 */
	public function test_role_slugs_are_the_two_of_the_contract() {
		$this->assertSame( array( 'prc_manager', 'prc_school_head' ), prc_role_slugs() );
		$this->assertSame( prc_role_slugs(), array_keys( prc_role_definitions() ) );
	}

	/**
	 * El registro crea los roles con su etiqueta y sus capacidades: las cinco
	 * de la SDD, repartidas como dice la tabla.
	 */
	public function test_register_roles_creates_the_roles_with_their_capabilities() {
		prc_register_roles();

		$manager = get_role( 'prc_manager' );
		$this->assertNotNull( $manager );
		$this->assertSame( 'Gestión de procedimientos', wp_roles()->role_names['prc_manager'] );
		$this->assertTrue( $manager->has_cap( 'read' ) );
		$this->assertTrue( $manager->has_cap( 'prc_manage_procedures' ) );
		$this->assertTrue( $manager->has_cap( 'prc_review_applications' ) );
		$this->assertFalse( $manager->has_cap( 'prc_apply' ), 'la gestión no solicita: no tiene centro' );
		$this->assertFalse( $manager->has_cap( 'prc_manage_all_areas' ), 'la gestión se queda en sus ámbitos' );
		$this->assertFalse( $manager->has_cap( 'prc_manage_app' ), 'administrar el aplicativo no es gestionar' );

		$head = get_role( 'prc_school_head' );
		$this->assertNotNull( $head );
		$this->assertSame( 'Dirección de centro', wp_roles()->role_names['prc_school_head'] );
		$this->assertTrue( $head->has_cap( 'read' ) );
		$this->assertTrue( $head->has_cap( 'prc_apply' ) );
		$this->assertFalse( $head->has_cap( 'prc_manage_procedures' ) );
		$this->assertFalse( $head->has_cap( 'prc_review_applications' ) );

		// La administración gestiona y revisa en todos los ámbitos y administra
		// el aplicativo, pero no solicita.
		$admin = get_role( 'administrator' );
		$this->assertTrue( $admin->has_cap( 'prc_manage_procedures' ) );
		$editor = get_role( 'editor' );
		$this->assertTrue( $editor->has_cap( 'prc_manage_procedures' ) );
		$this->assertFalse( $editor->has_cap( 'prc_manage_app' ) );
		$this->assertFalse( $editor->has_cap( 'prc_manage_all_areas' ) );
		$this->assertTrue( $admin->has_cap( 'prc_review_applications' ) );
		$this->assertTrue( $admin->has_cap( 'prc_manage_all_areas' ) );
		$this->assertTrue( $admin->has_cap( 'prc_manage_app' ) );
		$this->assertFalse( $admin->has_cap( 'prc_apply' ), 'solicitar es cosa de un centro' );
	}

	/**
	 * Registrar dos veces no añade nada: se llama en cada carga de `init`.
	 */
	public function test_register_roles_is_idempotent() {
		prc_register_roles();
		$capacidades = get_role( 'prc_manager' )->capabilities;
		$roles       = array_keys( wp_roles()->roles );

		prc_register_roles();
		prc_register_roles();

		$this->assertSame( $capacidades, get_role( 'prc_manager' )->capabilities );
		$this->assertSame( $roles, array_keys( wp_roles()->roles ), 'no aparece ningún rol nuevo' );
	}

	/**
	 * Y no pisa los roles que no son suyos.
	 */
	public function test_register_roles_does_not_touch_other_roles() {
		$antes = wp_roles()->roles;
		unset( $antes['prc_manager'], $antes['prc_school_head'], $antes['administrator'] );

		prc_register_roles();

		$despues = wp_roles()->roles;
		unset( $despues['prc_manager'], $despues['prc_school_head'], $despues['administrator'] );

		$this->assertSame( $antes, $despues );
	}

	/**
	 * Nunca quita una capacidad: quitar se hace en el código, que es donde se ve.
	 */
	public function test_register_roles_never_takes_a_capability_away() {
		prc_register_roles();
		get_role( 'prc_manager' )->add_cap( 'moderate_comments' );

		prc_register_roles();

		$this->assertTrue( get_role( 'prc_manager' )->has_cap( 'moderate_comments' ), 'lo concedido a mano sigue ahí' );
		get_role( 'prc_manager' )->remove_cap( 'moderate_comments' );
	}

	/**
	 * La comprobación dice qué rol o capacidad falta, qué sobra, y calla
	 * cuando todo está bien.
	 */
	public function test_status_says_which_capability_is_missing_or_forbidden() {
		prc_register_roles();
		$this->assertTrue( prc_role_exists( 'prc_manager' ) );
		$this->assertFalse( prc_role_exists( 'inventado' ) );

		foreach ( prc_roles_status() as $slug => $estado ) {
			$this->assertTrue( $estado['exists'], $slug );
			$this->assertSame( array(), $estado['missing'], $slug );
			$this->assertSame( array(), $estado['forbidden'], $slug );
		}
		get_role( 'editor' )->add_cap( 'prc_manage_all_areas' );
		$this->assertContains( 'prc_manage_all_areas', prc_roles_status()['editor']['forbidden'] );
		get_role( 'editor' )->remove_cap( 'prc_manage_all_areas' );

		get_role( 'prc_manager' )->remove_cap( 'prc_review_applications' );
		$this->assertSame( array( 'prc_review_applications' ), prc_roles_status()['prc_manager']['missing'] );

		prc_register_roles();
		$this->assertSame( array(), prc_roles_status()['prc_manager']['missing'], 'el registro repone la capacidad' );

		get_role( 'prc_manager' )->add_cap( 'prc_manage_app' );
		$this->assertSame( array( 'prc_manage_app' ), prc_roles_status()['prc_manager']['forbidden'], 'avisa de lo que sobra' );
		prc_register_roles();
		$this->assertTrue( get_role( 'prc_manager' )->has_cap( 'prc_manage_app' ), 'pero no lo quita: avisa y quien administra decide' );
		get_role( 'prc_manager' )->remove_cap( 'prc_manage_app' );
	}

	/**
	 * El ámbito y el centro no se los pone uno mismo: WordPress deja a
	 * cualquiera editar su propio perfil, y los campos que deciden qué
	 * procedimientos toca y por qué centro solicita serían la puerta de al lado.
	 */
	public function test_nobody_widens_their_own_scope() {
		prc_register_roles();
		$head = $this->school_head();
		$this->acting_as( $head );

		$this->assertFalse( prc_can_edit_admin_only_fields() );

		$_POST = array(
			'prc_profile_present' => '1',
			'prc_area'            => array( 99 ),
			'prc_centre_code'     => '90000001',
		);
		prc_save_profile_fields( $head );

		$this->assertSame( '', get_user_meta( $head, 'prc_area', true ) );
		$this->assertSame( '', CentreScope::code_for( $head ) );
	}

	/**
	 * Quien administra sí los escribe, y solo con términos que existen.
	 */
	public function test_administration_sets_areas_and_school_code() {
		$area  = $this->area( 'Servicio de prueba' );
		$quien = (int) self::factory()->user->create( array( 'role' => 'editor' ) );
		$this->acting_as( $this->administrator() );

		$this->assertTrue( prc_can_edit_admin_only_fields() );

		$_POST = array(
			'prc_profile_scope_nonce' => wp_create_nonce( 'prc_profile_scope_' . $quien ),
			'prc_profile_present'     => '1',
			'prc_area'                => (string) $area,
			'prc_centre_code'         => ' 90000001 ',
		);
		prc_save_profile_fields( $quien );

		$this->assertSame( array( $area ), get_user_meta( $quien, 'prc_area', true ) );
		$this->assertSame( '90000001', CentreScope::code_for( $quien ) );

		// Vaciar el código lo borra: sin código, `prc_apply` no sirve para nada.
		$_POST['prc_centre_code'] = '';
		prc_save_profile_fields( $quien );
		$this->assertSame( '', CentreScope::code_for( $quien ) );
	}

	/** Scope assignment is visible and writable only to administration. */
	public function test_editor_cannot_see_or_change_own_scope() {
		$area   = $this->area( 'Servicio A' );
		$other  = $this->area( 'Servicio B' );
		$editor = (int) self::factory()->user->create( array( 'role' => 'editor' ) );
		update_user_meta( $editor, 'prc_area', array( $area ) );
		$this->acting_as( $editor );
		ob_start();
		prc_render_profile_fields( get_user_by( 'id', $editor ) );
		$html = ob_get_clean();
		$this->assertStringNotContainsString( 'id="prc_area"', $html );
		$_POST = array(
			'prc_profile_present'     => '1',
			'prc_profile_scope_nonce' => wp_create_nonce( 'prc_profile_scope_' . $editor ),
			'prc_area'                => array( $other ),
		);
		prc_save_profile_fields( $editor );
		$this->assertSame( array( $area ), get_user_meta( $editor, 'prc_area', true ) );
		$this->acting_as( $this->administrator() );
		ob_start();
		prc_render_profile_fields( get_user_by( 'id', $editor ) );
		$html = ob_get_clean();
		$this->assertStringContainsString( 'name="prc_area"', $html );
		$this->assertStringNotContainsString( 'multiple', $html );
	}

	/** An unrelated profile update must leave historical scope data untouched. */
	public function test_historical_profile_needs_explicit_resolution() {
		$first  = $this->area( 'Ámbito 1' );
		$second = $this->area( 'Ámbito 2' );
		$editor = (int) self::factory()->user->create( array( 'role' => 'editor' ) );
		$this->acting_as( $this->administrator() );
		$_POST = array(
			'prc_profile_present'     => '1',
			'prc_profile_scope_nonce' => wp_create_nonce( 'prc_profile_scope_' . $editor ),
			'prc_area'                => '__keep_unresolved__',
		);
		update_user_meta( $editor, 'prc_area', (string) $first );
		ob_start();
		prc_render_profile_fields( get_user_by( 'id', $editor ) );
		$html = ob_get_clean();
		$this->assertStringContainsString( 'value="' . $first . '" selected=', $html );
		$this->assertStringNotContainsString( '__keep_unresolved__', $html );
		$_POST['prc_area'] = (string) $first;
		prc_save_profile_fields( $editor );
		$this->assertSame( array( $first ), get_user_meta( $editor, 'prc_area', true ) );
		$_POST['prc_area'] = '__keep_unresolved__';
		foreach ( array( array( $first, $second ), $first . ',' . $second, array( $first, 99999999 ) ) as $raw ) {
			update_user_meta( $editor, 'prc_area', $raw );
			ob_start();
			prc_render_profile_fields( get_user_by( 'id', $editor ) );
			$this->assertStringContainsString( '__keep_unresolved__', ob_get_clean() );
			prc_save_profile_fields( $editor );
			$this->assertSame( $raw, get_user_meta( $editor, 'prc_area', true ) );
		}
		$_POST['prc_area'] = (string) $first;
		prc_save_profile_fields( $editor );
		$this->assertSame( array( $first ), get_user_meta( $editor, 'prc_area', true ) );
		$_POST['prc_area'] = '';
		prc_save_profile_fields( $editor );
		$this->assertSame( array(), get_user_meta( $editor, 'prc_area', true ) );
	}
}
