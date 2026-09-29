<?php
/**
 * Tests for the two custom post types and their capabilities.
 *
 * @package Prc
 */

use Prc\PostType\ApplicationPostType;
use Prc\PostType\ProcedurePostType;

/**
 * Los tipos de contenido tal y como los pide la SDD.
 */
class Test_Post_Types extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Tipos, taxonomías y roles registrados.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
	}

	/**
	 * El procedimiento es público, plano y con REST: su ficha es el `single`.
	 */
	public function test_the_procedure_is_public_and_flat() {
		$tipo = get_post_type_object( ProcedurePostType::POST_TYPE );

		$this->assertNotNull( $tipo );
		$this->assertFalse( $tipo->hierarchical );
		$this->assertTrue( $tipo->public );
		$this->assertTrue( $tipo->show_in_rest );
		$this->assertTrue( $tipo->map_meta_cap );
		$this->assertSame( 'procedimiento', $tipo->rewrite['slug'] );
		foreach ( array( 'title', 'editor', 'excerpt', 'thumbnail' ) as $soporte ) {
			$this->assertTrue( post_type_supports( ProcedurePostType::POST_TYPE, $soporte ), $soporte );
		}
		// Se registra como array( singular, plural ), pero WP_Post_Type::set_props()
		// deriva de ahí todas las capacidades y se queda solo con el singular.
		$this->assertSame( 'prc_procedure', $tipo->capability_type );
		$this->assertSame( 'edit_prc_procedures', $tipo->cap->edit_posts );
		$this->assertSame( 'publish_prc_procedures', $tipo->cap->publish_posts );
	}

	/**
	 * La base de la URL se cambia con un filtro, sin tocar el código.
	 */
	public function test_the_rewrite_slug_is_filterable() {
		add_filter(
			'prc_procedure_rewrite_slug',
			static function () {
				return 'convocatoria';
			}
		);
		ProcedurePostType::register();

		$this->assertSame( 'convocatoria', get_post_type_object( ProcedurePostType::POST_TYPE )->rewrite['slug'] );
	}

	/**
	 * Las capacidades del procedimiento son exactamente las del contrato.
	 */
	public function test_the_procedure_capabilities_are_the_contract_ones() {
		$cap = get_post_type_object( ProcedurePostType::POST_TYPE )->cap;

		$esperadas = array(
			'edit_post'              => 'edit_prc_procedure',
			'read_post'              => 'read_prc_procedure',
			'delete_post'            => 'delete_prc_procedure',
			'edit_posts'             => 'edit_prc_procedures',
			'edit_others_posts'      => 'edit_others_prc_procedures',
			'publish_posts'          => 'publish_prc_procedures',
			'read_private_posts'     => 'read_private_prc_procedures',
			'delete_posts'           => 'delete_prc_procedures',
			'delete_others_posts'    => 'delete_others_prc_procedures',
			'edit_published_posts'   => 'edit_published_prc_procedures',
			'delete_published_posts' => 'delete_published_prc_procedures',
		);
		foreach ( $esperadas as $clave => $valor ) {
			$this->assertSame( $valor, $cap->$clave, $clave );
		}

		// Sin `create_posts` propia: crear un procedimiento es editarlos.
		$this->assertSame( 'edit_prc_procedures', $cap->create_posts );
	}

	/**
	 * La solicitud existe cerrada: sin URL, sin REST, sin búsqueda, con
	 * escritorio solo para mirar, y colgando del menú de procedimientos.
	 */
	public function test_the_application_is_closed_but_visible_in_the_desktop() {
		$tipo = get_post_type_object( ApplicationPostType::POST_TYPE );

		$this->assertNotNull( $tipo );
		$this->assertFalse( $tipo->public );
		$this->assertFalse( $tipo->publicly_queryable );
		$this->assertFalse( $tipo->show_in_rest );
		$this->assertTrue( $tipo->exclude_from_search );
		$this->assertFalse( $tipo->rewrite );
		$this->assertFalse( $tipo->query_var );
		$this->assertTrue( $tipo->show_ui );
		$this->assertSame( 'edit.php?post_type=' . ProcedurePostType::POST_TYPE, $tipo->show_in_menu );
		$this->assertSame( 'edit_prc_applications', $tipo->cap->edit_posts );
		$this->assertSame( 'publish_prc_applications', $tipo->cap->publish_posts );
	}

	/**
	 * Y una solicitud es de verdad hija de su procedimiento, no otra cosa.
	 */
	public function test_an_application_hangs_from_its_procedure() {
		$procedimiento = $this->procedure( $this->administrator() );
		$solicitud     = $this->application( $procedimiento, $this->school_head( 'C0001' ) );

		$this->assertSame( $procedimiento, (int) get_post_field( 'post_parent', $solicitud ) );
		$this->assertSame( ApplicationPostType::POST_TYPE, get_post_type( $solicitud ) );
		$this->assertSame( 'publish', get_post_status( $solicitud ) );
	}

	/**
	 * Las capacidades no son papel mojado: el reparto se las da al rol de
	 * gestión para los dos tipos, y le deja fuera las que no le tocan.
	 */
	public function test_the_manager_role_really_holds_those_capabilities() {
		$manager = $this->manager();

		$tiene = array(
			'edit_prc_procedures',
			'edit_others_prc_procedures',
			'publish_prc_procedures',
			'read_private_prc_procedures',
			'delete_prc_procedures',
			'delete_published_prc_procedures',
			'edit_published_prc_procedures',
			'edit_prc_applications',
			'edit_others_prc_applications',
			'delete_prc_applications',
		);
		foreach ( $tiene as $cap ) {
			$this->assertTrue( user_can( $manager, $cap ), $cap );
		}

		$no_tiene = array( 'delete_others_prc_procedures', 'edit_private_prc_procedures', 'publish_prc_applications', 'prc_manage_all_areas', 'prc_manage_app' );
		foreach ( $no_tiene as $cap ) {
			$this->assertFalse( user_can( $manager, $cap ), $cap );
		}
	}

	/**
	 * La dirección de un centro no toca ningún tipo desde el escritorio: solo
	 * tiene `prc_apply`, y la solicitud la escribe el aplicativo.
	 */
	public function test_the_school_head_holds_no_post_type_capability() {
		$head = $this->school_head( 'C0001' );

		$this->assertTrue( user_can( $head, \Prc\Access\ProcedureAccess::CAP_APPLY ) );
		foreach ( array( 'edit_prc_procedures', 'edit_prc_applications', 'publish_prc_applications', 'prc_manage_procedures' ) as $cap ) {
			$this->assertFalse( user_can( $head, $cap ), $cap );
		}
	}

	/**
	 * El reparto es aditivo: lo concedido a mano sigue ahí después.
	 */
	public function test_granting_capabilities_never_takes_one_away() {
		get_role( 'prc_manager' )->add_cap( 'delete_others_prc_procedures' );

		ProcedurePostType::grant_caps_to_roles();

		$this->assertTrue( get_role( 'prc_manager' )->has_cap( 'delete_others_prc_procedures' ) );
		get_role( 'prc_manager' )->remove_cap( 'delete_others_prc_procedures' );
	}
}
