<?php
/**
 * Tests that provisioning leaves roles, vocabulary, pages and demo data in place.
 *
 * @package Prc
 */

use Prc\Access\CentreScope;
use Prc\Access\ProcedureAccess;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ApplicationPostType;
use Prc\PostType\ProcedurePostType;
use Prc\PublicFront\Shell;
use Prc\Taxonomy\ProcedureTaxonomies;

/**
 * Los cuatro guiones de `make provision`, corridos aquí en el mismo orden.
 *
 * Cada uno define sus funciones detrás de `function_exists` y se ejecuta al
 * final si `init` ya pasó, así que se pueden requerir una vez por test y el
 * entorno de cada test sale provisionado desde cero. Lo que se comprueba es
 * lo que promete la SDD: roles, el árbol de ámbitos, una página por pantalla,
 * las cuatro cuentas, un procedimiento en cada estado y una solicitud en cada
 * estado de revisión; y que repetirlo no duplica nada.
 */
class Test_Provision extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Run the four provisioning scripts, silenced.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
		$this->provision();
	}

	/**
	 * Run the four scripts in the Makefile order and return what they printed.
	 *
	 * @return string
	 */
	private function provision(): string {
		ob_start();
		try {
			foreach ( array( 'provision-roles', 'setup-vocabulary', 'setup-pages', 'seed-demo' ) as $script ) {
				require dirname( __DIR__, 2 ) . '/scripts/' . $script . '.php';
			}
		} finally {
			$salida = (string) ob_get_clean();
		}
		return $salida;
	}

	/**
	 * Los dos roles, con sus capacidades propias y las de los tipos.
	 */
	public function test_roles_exist_with_their_capabilities() {
		$this->assertTrue( get_role( 'prc_manager' )->has_cap( 'prc_manage_procedures' ) );
		$this->assertTrue( get_role( 'prc_manager' )->has_cap( 'edit_prc_procedures' ), 'las del tipo las reparte el aplicativo en la misma pasada' );
		$this->assertTrue( get_role( 'prc_school_head' )->has_cap( 'prc_apply' ) );
	}

	/**
	 * El árbol de ámbitos —servicio → área— y los cursos.
	 */
	public function test_vocabulary_is_a_tree_of_areas_and_flat_courses() {
		$servicio = get_term_by( 'slug', 'ambito-1', ProcedureTaxonomies::AREA );
		$area     = get_term_by( 'slug', 'subambito-1a', ProcedureTaxonomies::AREA );
		$this->assertInstanceOf( WP_Term::class, $servicio );
		$this->assertInstanceOf( WP_Term::class, $area );
		$this->assertSame( 0, $servicio->parent );
		$this->assertSame( $servicio->term_id, $area->parent, 'el área cuelga de su servicio' );

		$this->assertInstanceOf( WP_Term::class, get_term_by( 'slug', '2026-2027', ProcedureTaxonomies::COURSE ) );
	}

	/**
	 * Una página por pantalla, con su shortcode, y la madre anotada.
	 */
	public function test_every_screen_has_its_page_with_its_shortcode() {
		foreach ( Shell::SLUGS as $seccion => $slug ) {
			$shortcode = Shell::SHORTCODES[ $seccion ] ?? '';
			if ( '' === $shortcode ) {
				continue;
			}
			$page = get_page_by_path( $slug );
			$this->assertInstanceOf( WP_Post::class, $page, $slug );
			$this->assertTrue( has_shortcode( $page->post_content, $shortcode ), "«{$slug}» lleva [{$shortcode}]" );
		}
		$this->assertSame( '', get_option( \Prc\App::PAGES_PARENT ), 'sin PRC_PAGES_PARENT las páginas van en la raíz' );
	}

	/**
	 * Las cuatro cuentas, con su ámbito o su centro.
	 */
	public function test_demo_accounts_have_their_scope() {
		foreach ( prc_demo_accounts() as $login => $account ) {
			$user = get_user_by( 'login', $login );
			$this->assertInstanceOf( WP_User::class, $user, $login );
			$this->assertSame( array( $account['role'] ), $user->roles, $login );
			$this->assertSame( array() !== $account['areas'], ProcedureAccess::can_manage_procedures( $user->ID ), $login );
			$this->assertSame( $account['centre'], CentreScope::code_for( $user->ID ), $login );
		}
	}

	/**
	 * Un procedimiento en cada estado derivado, con preguntas de los cuatro tipos.
	 */
	public function test_procedures_cover_every_state_and_question_type() {
		$estados = array();
		$tipos   = array();
		foreach ( prc_demo_procedures() as $procedure ) {
			$post = get_page_by_path( $procedure['slug'], OBJECT, ProcedurePostType::POST_TYPE );
			$this->assertInstanceOf( WP_Post::class, $post, $procedure['slug'] );
			$estados[] = ProcedureState::of_post( $post->ID );
			$this->assertNotEmpty( wp_get_object_terms( $post->ID, ProcedureTaxonomies::COURSE ), 'lleva curso' );
			foreach ( (array) get_post_meta( $post->ID, ProcedureMetaKeys::QUESTIONS, true ) as $pregunta ) {
				$tipos[] = $pregunta['type'];
			}
		}
		sort( $estados );
		$esperados = array_keys( ProcedureMetaKeys::states() );
		sort( $esperados );
		$this->assertSame( $esperados, $estados, 'uno por estado, sin repetir' );

		$this->assertSame( array(), array_diff( array( 'text', 'single', 'multiple', 'yesno' ), $tipos ), 'hay preguntas de los cuatro tipos' );
	}

	/**
	 * Una solicitud en cada estado de revisión, cada una del centro de quien la presenta.
	 */
	public function test_applications_cover_every_review_state() {
		$solicitudes = get_posts(
			array(
				'post_type'   => ApplicationPostType::POST_TYPE,
				'post_status' => 'any',
				'numberposts' => -1,
			)
		);
		$estados     = array();
		foreach ( $solicitudes as $solicitud ) {
			$estados[] = get_post_meta( $solicitud->ID, ApplicationMetaKeys::REVIEW_STATE, true );
			$this->assertSame(
				CentreScope::code_for( (int) $solicitud->post_author ),
				get_post_meta( $solicitud->ID, ApplicationMetaKeys::CENTRE_CODE, true ),
				'la solicitud es del centro de quien la presenta'
			);
			$this->assertSame( ProcedurePostType::POST_TYPE, get_post_type( $solicitud->post_parent ), 'cuelga de su procedimiento' );
		}
		$esperados = array_keys( ApplicationMetaKeys::review_states() );
		sort( $esperados );
		$estados = array_values( array_unique( $estados ) );
		sort( $estados );
		$this->assertSame( $esperados, $estados );
	}

	/**
	 * Repetir la provisión no duplica nada: ni páginas, ni términos, ni cuentas,
	 * ni procedimientos, ni solicitudes.
	 */
	public function test_provisioning_twice_creates_nothing_new() {
		$cuenta = static function (): array {
			$posts = static function ( string $tipo ): int {
				return count(
					get_posts(
						array(
							'post_type'   => $tipo,
							'post_status' => 'any',
							'numberposts' => -1,
						)
					)
				);
			};
			return array(
				'pages'        => $posts( 'page' ),
				'areas'        => wp_count_terms(
					array(
						'taxonomy'   => ProcedureTaxonomies::AREA,
						'hide_empty' => false,
					)
				),
				'users'        => count_users()['total_users'],
				'procedures'   => $posts( ProcedurePostType::POST_TYPE ),
				'applications' => $posts( ApplicationPostType::POST_TYPE ),
			);
		};

		$antes = $cuenta();
		$this->provision();

		$this->assertSame( $antes, $cuenta() );
	}
}
