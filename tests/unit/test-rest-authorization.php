<?php
/**
 * Tests for the REST surface of the application: what each role reaches
 * through wp/v2, which is the door the workshop screens do not guard.
 *
 * @package Prc
 */

use Prc\Access\ProcedureAccess;
use Prc\PostType\ApplicationPostType;
use Prc\PostType\ProcedurePostType;
use Prc\PublicFront\ProcedureEditor;
use Prc\Taxonomy\ProcedureTaxonomies;

/**
 * El acotado por ámbito, comprobado por donde de verdad entra una petición.
 *
 * El escritorio y el taller se acotan en la pantalla; la REST no pasa por
 * ninguna de las dos. Aquí se despacha contra el servidor REST de verdad
 * —`rest_do_request()`— para que lo que se comprueba sea la respuesta que
 * recibiría quien llame a `/wp-json/`, y no una capacidad mirada a mano.
 */
class Test_Rest_Authorization extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Secret body of the fixtures that must never reach a foreign ámbito.
	 */
	const SECRETO = 'CONTENIDO-RESERVADO-DEL-AMBITO';

	/**
	 * A REST server with the routes of the application registered.
	 *
	 * @var WP_REST_Server
	 */
	private $server;

	/**
	 * Tipos, taxonomías y roles registrados, y el servidor REST en pie.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
		remove_all_filters( 'prc_centres' );

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;
		do_action( 'rest_api_init', $this->server );
	}

	/**
	 * Tear the REST server down again.
	 */
	public function tear_down() {
		global $wp_rest_server;
		$wp_rest_server = null;
		$this->server   = null;
		parent::tear_down();
	}

	/**
	 * Dispatch one REST request as somebody.
	 *
	 * @param int                  $user_id User ID; 0 for anonymous.
	 * @param string               $method  HTTP method.
	 * @param string               $route   Route, without the /wp-json prefix.
	 * @param array<string, mixed> $params  Request parameters.
	 * @return WP_REST_Response
	 */
	private function request( int $user_id, string $method, string $route, array $params = array() ) {
		wp_set_current_user( $user_id );
		$request = new WP_REST_Request( $method, $route );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}
		return rest_do_request( $request );
	}

	/**
	 * The IDs a collection response carries.
	 *
	 * @param WP_REST_Response $response Response.
	 * @return int[]
	 */
	private function ids( $response ): array {
		$data = $response->get_data();
		return is_array( $data ) ? array_map( 'intval', wp_list_pluck( $data, 'id' ) ) : array();
	}

	/**
	 * Two ámbitos, two managers, and one procedure of the first one.
	 *
	 * @param string $status Post status of the procedure.
	 * @return array{mine:int, theirs:int, procedure:int, area:int}
	 */
	private function two_areas( string $status ): array {
		$area_mio   = $this->area( 'Ámbito propio' );
		$area_ajeno = $this->area( 'Ámbito ajeno' );
		$mio        = $this->manager( array( $area_mio ) );
		$ajeno      = $this->manager( array( $area_ajeno ) );

		return array(
			'mine'      => $mio,
			'theirs'    => $ajeno,
			'area'      => $area_mio,
			'procedure' => $this->procedure(
				$mio,
				array( $area_mio ),
				array(),
				array(
					'post_status'  => $status,
					'post_title'   => 'Convocatoria reservada',
					'post_content' => self::SECRETO,
				)
			),
		);
	}

	/** A REST scope change outside the user's tree fails without altering terms. */
	public function test_rest_rejects_foreign_scope_assignment() {
		$mine    = $this->area( 'Servicio A' );
		$foreign = $this->area( 'Servicio B' );
		$editor  = (int) self::factory()->user->create( array( 'role' => 'editor' ) );
		update_user_meta( $editor, ProcedureAccess::USER_AREA_META, array( $mine ) );
		$post     = $this->procedure( $editor, array( $mine ) );
		$response = $this->request( $editor, 'POST', '/wp/v2/prc_procedure/' . $post, array( 'prc_area' => array( $foreign ) ) );
		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( array( $mine ), wp_get_post_terms( $post, 'prc_area', array( 'fields' => 'ids' ) ) );
	}

	/** Saving one branch of a shared procedure preserves the other branch. */
	public function test_rest_preserves_foreign_scope_on_shared_procedure() {
		$mine    = $this->area( 'Servicio A' );
		$foreign = $this->area( 'Servicio B' );
		$editor  = (int) self::factory()->user->create( array( 'role' => 'editor' ) );
		update_user_meta( $editor, ProcedureAccess::USER_AREA_META, array( $mine ) );
		$post     = $this->procedure( $editor, array( $mine, $foreign ) );
		$response = $this->request( $editor, 'POST', '/wp/v2/prc_procedure/' . $post, array( 'prc_area' => array( $mine ) ) );
		$this->assertSame( 200, $response->get_status() );
		$this->assertEqualsCanonicalizing( array( $mine, $foreign ), wp_get_post_terms( $post, 'prc_area', array( 'fields' => 'ids' ) ) );
	}

	/** Admin form rejects a foreign term before saving. */
	public function test_classic_post_rejects_foreign_scope_assignment() {
		$mine    = $this->area( 'Servicio A' );
		$foreign = $this->area( 'Servicio B' );
		$editor  = (int) self::factory()->user->create( array( 'role' => 'editor' ) );
		update_user_meta( $editor, ProcedureAccess::USER_AREA_META, array( $mine ) );
		$post = $this->procedure( $editor, array( $mine ) );
		wp_set_current_user( $editor );
		$result = ProcedureAccess::admin_area_assignment(
			array(
				'post_type' => 'prc_procedure',
				'tax_input' => array( 'prc_area' => array( $foreign ) ),
			)
		);
		$this->assertWPError( $result );
		$this->assertSame( array( $mine ), wp_get_post_terms( $post, 'prc_area', array( 'fields' => 'ids' ) ) );
		$this->assertFalse( has_filter( 'wp_insert_post_empty_content', array( ProcedureAccess::class, 'validate_classic_areas' ) ) );
	}

	// ─── leer: el ámbito acota lo que todavía no es público ────────────────

	/**
	 * Un procedimiento privado no lo lee el ámbito de al lado, ni en la
	 * colección ni pidiéndolo por su identificador.
	 */
	public function test_a_foreign_area_does_not_read_a_private_procedure() {
		$caso = $this->two_areas( 'private' );

		$item = $this->request( $caso['theirs'], 'GET', '/wp/v2/prc_procedure/' . $caso['procedure'] );
		$this->assertSame( 403, $item->get_status(), 'la ficha privada de otro ámbito' );
		$this->assertStringNotContainsString( self::SECRETO, wp_json_encode( $item->get_data() ) );

		foreach ( array( 'private', 'any' ) as $estado ) {
			$lista = $this->request( $caso['theirs'], 'GET', '/wp/v2/prc_procedure', array( 'status' => $estado ) );
			$this->assertNotContains( $caso['procedure'], $this->ids( $lista ), 'la colección con status=' . $estado );
			$this->assertStringNotContainsString( self::SECRETO, wp_json_encode( $lista->get_data() ) );
		}
	}

	/**
	 * Un borrador tampoco, que es el mismo acotado por el otro estado.
	 */
	public function test_a_foreign_area_does_not_read_a_draft_procedure() {
		$caso = $this->two_areas( 'draft' );

		$item = $this->request( $caso['theirs'], 'GET', '/wp/v2/prc_procedure/' . $caso['procedure'] );
		$this->assertSame( 403, $item->get_status() );

		$lista = $this->request( $caso['theirs'], 'GET', '/wp/v2/prc_procedure', array( 'status' => 'draft' ) );
		$this->assertNotContains( $caso['procedure'], $this->ids( $lista ) );
	}

	/**
	 * Ni quien solicita, ni quien pasa por ahí sin entrar.
	 */
	public function test_neither_a_school_nor_a_stranger_reads_a_private_procedure() {
		$caso = $this->two_areas( 'private' );
		$head = $this->school_head( 'C0001' );

		foreach ( array( $head, 0 ) as $quien ) {
			$item = $this->request( $quien, 'GET', '/wp/v2/prc_procedure/' . $caso['procedure'] );
			$this->assertContains( $item->get_status(), array( 401, 403 ) );
			$this->assertStringNotContainsString( self::SECRETO, wp_json_encode( $item->get_data() ) );
		}
	}

	/**
	 * Su propio ámbito sí lo lee: el acotado cierra hacia fuera, no hacia dentro.
	 */
	public function test_the_own_area_still_reads_its_private_procedure() {
		$caso = $this->two_areas( 'private' );

		$item = $this->request( $caso['mine'], 'GET', '/wp/v2/prc_procedure/' . $caso['procedure'] );
		$this->assertSame( 200, $item->get_status() );
		$this->assertStringContainsString( self::SECRETO, wp_json_encode( $item->get_data() ) );

		$lista = $this->request( $caso['mine'], 'GET', '/wp/v2/prc_procedure', array( 'status' => 'private' ) );
		$this->assertContains( $caso['procedure'], $this->ids( $lista ) );
	}

	/**
	 * Y administración, que trabaja en todos los ámbitos.
	 */
	public function test_administration_reads_every_area() {
		$caso = $this->two_areas( 'private' );

		$item = $this->request( $this->administrator(), 'GET', '/wp/v2/prc_procedure/' . $caso['procedure'] );
		$this->assertSame( 200, $item->get_status() );
		$this->assertStringContainsString( self::SECRETO, wp_json_encode( $item->get_data() ) );
	}

	/**
	 * La ficha publicada sigue siendo pública: es la puerta de entrada del
	 * aplicativo y no la cierra nadie.
	 */
	public function test_a_published_procedure_stays_public() {
		$caso = $this->two_areas( 'publish' );

		foreach ( array( 0, $this->school_head( 'C0001' ), $caso['theirs'] ) as $quien ) {
			$item = $this->request( $quien, 'GET', '/wp/v2/prc_procedure/' . $caso['procedure'] );
			$this->assertSame( 200, $item->get_status() );
			$this->assertStringContainsString( self::SECRETO, wp_json_encode( $item->get_data() ) );
		}

		$lista = $this->request( 0, 'GET', '/wp/v2/prc_procedure' );
		$this->assertContains( $caso['procedure'], $this->ids( $lista ) );
	}

	/**
	 * Un procedimiento marcado como histórico se sigue consultando: está
	 * publicado, y cerrarlo cierra la edición, no la lectura.
	 */
	public function test_an_archived_procedure_is_still_readable() {
		$caso = $this->two_areas( 'publish' );
		update_post_meta( $caso['procedure'], \Prc\Meta\ProcedureMetaKeys::ARCHIVED, true );

		$item = $this->request( $caso['mine'], 'GET', '/wp/v2/prc_procedure/' . $caso['procedure'] );
		$this->assertSame( 200, $item->get_status() );
	}

	// ─── escribir: marcar un ámbito es dar acceso (ADR-0025) ───────────────

	/** REST creation without a term defaults only for a resolved Editor. */
	public function test_rest_creation_without_scope_is_fail_closed() {
		$area   = $this->area( 'Ámbito 1' );
		$other  = $this->area( 'Ámbito 2' );
		$editor = (int) self::factory()->user->create( array( 'role' => 'editor' ) );
		$admin  = $this->administrator();
		wp_set_current_user( $admin );
		$before = count(
			get_posts(
				array(
					'post_type'   => ProcedurePostType::POST_TYPE,
					'post_status' => 'any',
					'numberposts' => -1,
				)
			)
		);
		foreach ( array( array(), array( $area, $other ), array( 99999999 ) ) as $raw ) {
			update_user_meta( $editor, ProcedureAccess::USER_AREA_META, $raw );
			foreach ( array( 'draft', 'publish' ) as $status ) {
				$response = $this->request(
					$editor,
					'POST',
					'/wp/v2/prc_procedure',
					array(
						'title'  => 'Sin ámbito',
						'status' => $status,
					)
				);
				$this->assertSame( 403, $response->get_status() );
				wp_set_current_user( $admin );
				$this->assertSame(
					$before,
					count(
						get_posts(
							array(
								'post_type'   => ProcedurePostType::POST_TYPE,
								'post_status' => 'any',
								'numberposts' => -1,
							)
						)
					)
				);
			}
		}
		update_user_meta( $editor, ProcedureAccess::USER_AREA_META, array( $area ) );
		$draft = $this->request(
			$editor,
			'POST',
			'/wp/v2/prc_procedure',
			array(
				'title'  => 'Con ámbito',
				'status' => 'draft',
			)
		);
		$this->assertSame( 201, $draft->get_status() );
		$this->assertSame( array( $area ), wp_get_object_terms( $draft->get_data()['id'], ProcedureTaxonomies::AREA, array( 'fields' => 'ids' ) ) );
		$direct = $this->request(
			$editor,
			'POST',
			'/wp/v2/prc_procedure',
			array(
				'title'  => 'Directo',
				'status' => 'publish',
			)
		);
		$this->assertSame( 400, $direct->get_status() );
		$published = $this->request( $editor, 'POST', '/wp/v2/prc_procedure/' . $draft->get_data()['id'], array( 'status' => 'publish' ) );
		$this->assertSame( 200, $published->get_status() );
		$this->assertSame( array( $area ), wp_get_object_terms( $draft->get_data()['id'], ProcedureTaxonomies::AREA, array( 'fields' => 'ids' ) ) );
		$admin_response = $this->request(
			$admin,
			'POST',
			'/wp/v2/prc_procedure',
			array(
				'title'  => 'Reparación',
				'status' => 'publish',
			)
		);
		$this->assertSame( 201, $admin_response->get_status() );
		$this->assertSame( array(), wp_get_object_terms( $admin_response->get_data()['id'], ProcedureTaxonomies::AREA, array( 'fields' => 'ids' ) ) );
	}

	/** Publication callbacks see the scope after a draft-to-published update. */
	public function test_rest_publish_transition_has_scope() {
		$area   = $this->area( 'Ámbito 1' );
		$editor = (int) self::factory()->user->create( array( 'role' => 'editor' ) );
		update_user_meta( $editor, ProcedureAccess::USER_AREA_META, array( $area ) );
		$seen  = array();
		$watch = static function ( $new_status, $old_status, $post ) use ( &$seen ) {
			if ( ProcedurePostType::POST_TYPE === $post->post_type && 'publish' === $new_status ) {
				$seen[] = wp_get_object_terms( $post->ID, ProcedureTaxonomies::AREA, array( 'fields' => 'ids' ) );
			}
		};
		add_action( 'transition_post_status', $watch, 10, 3 );
		$direct = $this->request(
			$editor,
			'POST',
			'/wp/v2/prc_procedure',
			array(
				'title'    => 'Directo',
				'status'   => 'publish',
				'prc_area' => array( $area ),
			)
		);
		$this->assertSame( 400, $direct->get_status() );
		$this->assertSame( array(), $seen );
		$draft = $this->request(
			$editor,
			'POST',
			'/wp/v2/prc_procedure',
			array(
				'title'    => 'Borrador',
				'status'   => 'draft',
				'prc_area' => array( $area ),
			)
		);
		$this->assertSame( 201, $draft->get_status() );
		$published = $this->request( $editor, 'POST', '/wp/v2/prc_procedure/' . $draft->get_data()['id'], array( 'status' => 'publish' ) );
		remove_action( 'transition_post_status', $watch, 10 );
		$this->assertSame( 200, $published->get_status() );
		$this->assertSame( array( array( $area ) ), $seen );
	}

	/**
	 * Convocar bajo un ámbito ajeno es meter a otro servicio en las propias
	 * solicitudes: no se hace desde el taller, y tampoco por la REST.
	 */
	public function test_a_manager_does_not_file_a_new_procedure_under_a_foreign_area() {
		$caso = $this->two_areas( 'publish' );

		$alta = $this->request(
			$caso['theirs'],
			'POST',
			'/wp/v2/prc_procedure',
			array(
				'title'    => 'Convocatoria colada',
				'status'   => 'draft',
				'prc_area' => array( $caso['area'] ),
			)
		);
		$this->assertSame( 403, $alta->get_status() );

		$colados = get_posts(
			array(
				'post_type'   => ProcedurePostType::POST_TYPE,
				'post_status' => 'any',
				'fields'      => 'ids',
				'title'       => 'Convocatoria colada',
			)
		);
		$this->assertSame( array(), $colados, 'no queda ni el procedimiento a medias' );
	}

	/**
	 * Ni regalando el propio, que es la misma jugada al revés.
	 */
	public function test_a_manager_does_not_hand_its_procedure_to_a_foreign_area() {
		$caso  = $this->two_areas( 'draft' );
		$suyo  = $this->procedure(
			$caso['theirs'],
			array( $this->area( 'Ámbito ajeno' ) ),
			array(),
			array( 'post_status' => 'draft' )
		);
		$antes = wp_get_object_terms( $suyo, ProcedureTaxonomies::AREA, array( 'fields' => 'ids' ) );

		$cambio = $this->request(
			$caso['theirs'],
			'POST',
			'/wp/v2/prc_procedure/' . $suyo,
			array( 'prc_area' => array( $caso['area'] ) )
		);
		$this->assertSame( 403, $cambio->get_status() );
		$this->assertSame( $antes, wp_get_object_terms( $suyo, ProcedureTaxonomies::AREA, array( 'fields' => 'ids' ) ) );
	}

	/**
	 * Bajo el propio sí, que es el alta de todos los días.
	 */
	public function test_a_manager_files_a_new_procedure_under_its_own_area() {
		$caso = $this->two_areas( 'publish' );

		$alta = $this->request(
			$caso['mine'],
			'POST',
			'/wp/v2/prc_procedure',
			array(
				'title'    => 'Convocatoria propia',
				'status'   => 'draft',
				'prc_area' => array( $caso['area'] ),
			)
		);
		$this->assertSame( 201, $alta->get_status() );
		$creado = (int) ( $alta->get_data()['id'] ?? 0 );
		$this->assertSame(
			array( $caso['area'] ),
			wp_get_object_terms( $creado, ProcedureTaxonomies::AREA, array( 'fields' => 'ids' ) )
		);
	}

	/**
	 * El curso no es el eje de permisos: se pone el que sea.
	 */
	public function test_the_course_is_not_scoped_by_area() {
		$caso  = $this->two_areas( 'publish' );
		$curso = $this->course( '2030-2031' );

		$this->assertTrue( ProcedureAccess::may_use_area( $caso['theirs'], $curso ) );

		$alta = $this->request(
			$caso['theirs'],
			'POST',
			'/wp/v2/prc_procedure',
			array(
				'title'      => 'Convocatoria con curso',
				'status'     => 'draft',
				'prc_course' => array( $curso ),
			)
		);
		$this->assertSame( 201, $alta->get_status() );
	}

	/**
	 * Un ámbito que no existe no lo pone nadie: falla en cerrado.
	 */
	public function test_an_unknown_term_is_never_assignable() {
		$caso = $this->two_areas( 'publish' );

		$this->assertFalse( ProcedureAccess::may_use_area( $caso['theirs'], 999999 ) );
		$this->assertFalse( ProcedureEditor::may_set_area( $caso['theirs'], array( 999999 ) ) );
		$this->assertFalse( ProcedureEditor::may_set_area( $caso['theirs'], array( $caso['area'] ) ) );
		$this->assertTrue( ProcedureEditor::may_set_area( $caso['mine'], array( $caso['area'] ) ) );
	}

	/**
	 * Los ámbitos se leen, pero no los crea quien gestiona: eso es administrar
	 * el aplicativo.
	 */
	public function test_a_manager_does_not_create_areas() {
		$caso = $this->two_areas( 'publish' );

		$alta = $this->request( $caso['mine'], 'POST', '/wp/v2/prc_area', array( 'name' => 'Ámbito inventado' ) );
		$this->assertSame( 403, $alta->get_status() );
	}

	// ─── las solicitudes no salen por aquí ─────────────────────────────────

	/**
	 * La solicitud de un centro no tiene ruta REST, y su procedimiento no la
	 * sirve por la suya.
	 */
	public function test_applications_are_not_on_the_rest_api() {
		$caso      = $this->two_areas( 'publish' );
		$head      = $this->school_head( 'C0001' );
		$solicitud = $this->application( $caso['procedure'], $head );

		foreach ( array_keys( $this->server->get_routes() ) as $ruta ) {
			$this->assertStringNotContainsString( ApplicationPostType::POST_TYPE, $ruta );
		}

		foreach ( array( $head, $caso['mine'], $caso['theirs'], 0 ) as $quien ) {
			$item = $this->request( $quien, 'GET', '/wp/v2/prc_procedure/' . $solicitud );
			$this->assertSame( 404, $item->get_status() );
		}
	}

	/**
	 * Y sus metas tampoco viajan en la ficha del procedimiento.
	 */
	public function test_procedure_meta_is_not_exposed_by_rest() {
		$caso = $this->two_areas( 'publish' );
		update_post_meta( $caso['procedure'], \Prc\Meta\ProcedureMetaKeys::CONTACT_EMAILS, array( 'alguien@example.org' ) );

		$item = $this->request( 0, 'GET', '/wp/v2/prc_procedure/' . $caso['procedure'] );
		$this->assertSame( 200, $item->get_status() );
		$this->assertStringNotContainsString( 'alguien@example.org', wp_json_encode( $item->get_data() ) );
	}
}
