<?php
/**
 * Tests for the publication guard: no procedure is published without a
 * course or with an undated delivery, whichever door it comes through.
 *
 * @package Prc
 */

use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ProcedurePostType;
use Prc\Taxonomy\ProcedureTaxonomies;

/**
 * El taller ya no ofrece publicar un borrador sin curso; esto comprueba que
 * tampoco se publica por el escritorio, la edición rápida ni la REST, y que
 * lo ya publicado, la provisión y el alta siguen como estaban.
 */
class Test_Publish_Guard extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * A REST server with the routes of the application registered.
	 *
	 * @var WP_REST_Server
	 */
	private $server;

	/**
	 * Tipos, taxonomías y el servidor REST en pie.
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
	 * Un ámbito, quien lo gestiona y un borrador suyo, sin curso y con plazo.
	 *
	 * @param array<string, mixed> $meta Meta of the procedure.
	 * @return array{0:int, 1:int} Manager, procedure.
	 */
	private function borrador( array $meta = array() ): array {
		$area = $this->area( 'Área de prueba' );
		$uid  = $this->manager( array( $area ) );
		$meta = array_merge( array( ProcedureMetaKeys::OPENS_AT => gmdate( 'Y-m-d', strtotime( '+10 days' ) ) ), $meta );
		return array( $uid, $this->procedure( $uid, array( $area ), $meta, array( 'post_status' => 'draft' ) ) );
	}

	/**
	 * Una entrega con plazo propio sin fechas.
	 *
	 * @return array<string, mixed>
	 */
	private function entrega_sin_fechas(): array {
		return array(
			ProcedureMetaKeys::QUESTIONS => array(
				array(
					'key'      => 'q1',
					'label'    => 'Memoria final',
					'type'     => 'file',
					'delivery' => true,
				),
			),
		);
	}

	/**
	 * Dispatch one REST request as somebody.
	 *
	 * @param int                  $user_id User ID.
	 * @param string               $route   Route.
	 * @param array<string, mixed> $params  Parameters.
	 * @return WP_REST_Response
	 */
	private function rest( int $user_id, string $route, array $params ) {
		wp_set_current_user( $user_id );
		$request = new WP_REST_Request( 'POST', $route );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}
		return rest_do_request( $request );
	}

	// ─── escritorio y edición rápida ───────────────────────────────────────

	/**
	 * Por `wp_update_post()` —lo que hacen `post.php` y la edición rápida—
	 * un borrador sin curso se queda en borrador; con curso, se publica.
	 */
	public function test_an_update_without_course_stays_a_draft() {
		list( $uid, $id ) = $this->borrador();
		$this->acting_as( $uid );

		wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => 'publish',
			)
		);
		$this->assertSame( 'draft', get_post_status( $id ) );

		wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => 'future',
				'post_date'   => gmdate( 'Y-m-d H:i:s', strtotime( '+3 days' ) ),
			)
		);
		$this->assertSame( 'draft', get_post_status( $id ), 'ni programado' );

		wp_set_object_terms( $id, array( $this->course() ), ProcedureTaxonomies::COURSE );
		wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => 'publish',
				'post_date'   => gmdate( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
			)
		);
		$this->assertSame( 'publish', get_post_status( $id ) );
	}

	/**
	 * El curso que llega en el mismo envío del escritorio (`tax_input`) cuenta.
	 */
	public function test_the_course_sent_with_the_form_counts() {
		list( $uid, $id ) = $this->borrador();
		$curso            = $this->course( '2030-2031' );
		$this->acting_as( $uid );

		wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => 'publish',
				'tax_input'   => array( ProcedureTaxonomies::COURSE => '2030-2031' ),
			)
		);
		$this->assertSame( 'publish', get_post_status( $id ) );
		$this->assertSame( array( $curso ), wp_get_post_terms( $id, ProcedureTaxonomies::COURSE, array( 'fields' => 'ids' ) ) );
	}

	/**
	 * Con curso pero con una entrega sin fechas, tampoco.
	 */
	public function test_an_undated_delivery_stays_a_draft() {
		list( $uid, $id ) = $this->borrador( $this->entrega_sin_fechas() );
		wp_set_object_terms( $id, array( $this->course() ), ProcedureTaxonomies::COURSE );
		$this->acting_as( $uid );

		wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => 'publish',
			)
		);
		$this->assertSame( 'draft', get_post_status( $id ) );
	}

	/**
	 * Con curso pero sin plazo de solicitud, tampoco: se quedaría en «Próximo»
	 * para siempre. Con el plazo puesto, sí.
	 */
	public function test_without_an_application_window_it_stays_a_draft() {
		list( $uid, $id ) = $this->borrador( array( ProcedureMetaKeys::OPENS_AT => '' ) );
		wp_set_object_terms( $id, array( $this->course() ), ProcedureTaxonomies::COURSE );
		$this->acting_as( $uid );

		wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => 'publish',
			)
		);
		$this->assertSame( 'draft', get_post_status( $id ) );

		$respuesta = $this->rest( $uid, '/wp/v2/prc_procedure/' . $id, array( 'status' => 'publish' ) );
		$this->assertSame( 400, $respuesta->get_status() );
		$this->assertStringContainsString( 'Falta el plazo de solicitud', $respuesta->get_data()['message'] );

		update_post_meta( $id, ProcedureMetaKeys::OPENS_AT, gmdate( 'Y-m-d', strtotime( '+30 days' ) ) );
		wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => 'publish',
			)
		);
		$this->assertSame( 'publish', get_post_status( $id ), 'con fechas futuras se publica, como «Próximo»' );
	}

	/**
	 * Lo que ya está publicado sin curso ni plazo se sigue guardando
	 * publicado: la guarda solo mira el paso a publicado.
	 */
	public function test_a_published_procedure_without_course_is_not_unpublished() {
		$area = $this->area( 'Área de prueba' );
		$uid  = $this->manager( array( $area ) );
		$id   = $this->procedure( $uid, array( $area ) );
		$this->assertSame( 'publish', get_post_status( $id ) );
		$this->acting_as( $uid );

		wp_update_post(
			array(
				'ID'          => $id,
				'post_title'  => 'Otro título',
				'post_status' => 'publish',
			)
		);
		$this->assertSame( 'publish', get_post_status( $id ) );
		$this->assertSame( 'Otro título', get_the_title( $id ) );
	}

	/**
	 * Sin nadie delante —la provisión, el cron— no se mira: la demostración
	 * se siembra igual.
	 */
	public function test_without_a_user_the_provisioning_is_untouched() {
		list( , $id ) = $this->borrador();
		wp_set_current_user( 0 );

		wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => 'publish',
			)
		);
		$this->assertSame( 'publish', get_post_status( $id ) );
	}

	// ─── la REST ───────────────────────────────────────────────────────────

	/**
	 * Por la REST se rechaza con el motivo; con el curso en la misma
	 * petición, se publica.
	 */
	public function test_rest_refuses_with_the_reason_and_accepts_the_course_in_the_request() {
		list( $uid, $id ) = $this->borrador();

		$respuesta = $this->rest( $uid, '/wp/v2/prc_procedure/' . $id, array( 'status' => 'publish' ) );
		$this->assertSame( 400, $respuesta->get_status() );
		$this->assertSame( 'prc_not_ready', $respuesta->get_data()['code'] );
		$this->assertStringContainsString( 'curso', $respuesta->get_data()['message'] );
		$this->assertSame( 'draft', get_post_status( $id ) );

		$respuesta = $this->rest(
			$uid,
			'/wp/v2/prc_procedure/' . $id,
			array(
				'status'                    => 'publish',
				ProcedureTaxonomies::COURSE => array( $this->course() ),
			)
		);
		$this->assertSame( 200, $respuesta->get_status() );
		$this->assertSame( 'publish', get_post_status( $id ) );
	}

	/**
	 * Crear publicado por la REST tampoco, ni siquiera administración: sin
	 * curso, o, con él, sin plazo, que la REST no escribe. Se crea en
	 * borrador, se le ponen las fechas en el taller y se publica.
	 */
	public function test_rest_creation_as_published_needs_a_course() {
		$admin = $this->administrator();
		$antes = count(
			get_posts(
				array(
					'post_type'   => ProcedurePostType::POST_TYPE,
					'post_status' => 'any',
					'numberposts' => -1,
				)
			)
		);

		$respuesta = $this->rest(
			$admin,
			'/wp/v2/prc_procedure',
			array(
				'title'  => 'Directo',
				'status' => 'publish',
			)
		);
		$this->assertSame( 400, $respuesta->get_status() );
		$this->assertSame(
			$antes,
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

		$respuesta = $this->rest(
			$admin,
			'/wp/v2/prc_procedure',
			array(
				'title'                     => 'Directo',
				'status'                    => 'publish',
				ProcedureTaxonomies::COURSE => array( $this->course() ),
			)
		);
		$this->assertSame( 400, $respuesta->get_status() );
		$this->assertStringContainsString( 'Falta el plazo de solicitud', $respuesta->get_data()['message'] );
		$this->assertStringNotContainsString( 'curso', $respuesta->get_data()['message'] );
	}

	/**
	 * Un borrador se sigue creando y guardando por la REST sin curso.
	 */
	public function test_rest_drafts_need_no_course() {
		list( $uid, $id ) = $this->borrador();
		$respuesta        = $this->rest( $uid, '/wp/v2/prc_procedure/' . $id, array( 'title' => 'Cambio' ) );
		$this->assertSame( 200, $respuesta->get_status() );
		$this->assertSame( 'draft', get_post_status( $id ) );
	}
}
