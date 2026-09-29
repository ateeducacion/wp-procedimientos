<?php
/**
 * Fixtures compartidas por los tests: los tipos y taxonomías del aplicativo,
 * las personas con su rol, su ámbito o su centro, los procedimientos con sus
 * metas y términos, las solicitudes, las páginas del aplicativo y la mecánica
 * de un POST que acaba en Shell::leave().
 *
 * @package Prc
 */

use Prc\Access\CentreScope;
use Prc\Access\ProcedureAccess;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ApplicationMetaRegistration;
use Prc\Meta\ProcedureMetaRegistration;
use Prc\PostType\ApplicationPostType;
use Prc\PostType\ProcedurePostType;
use Prc\PublicFront\ExitSignal;
use Prc\PublicFront\Shell;
use Prc\Taxonomy\ProcedureTaxonomies;

/**
 * Se usa con `use Prc_Fixtures;` dentro de un WP_UnitTestCase.
 */
trait Prc_Fixtures {

	/**
	 * Roles, capacidades, tipos de contenido, taxonomías y metas del aplicativo.
	 *
	 * `init` ya lo registró todo al arrancar, pero la base de datos se deshace
	 * tras cada test y los roles se van con ella; y `reset_post_types()` de
	 * WP_UnitTestCase desregistra en cada `tear_down` los tipos que no son del
	 * núcleo, llevándose sus `register_post_meta()`. Volver a registrarlo es
	 * barato y deja cada test en pie por sí solo.
	 *
	 * @return void
	 */
	protected function app(): void {
		prc_register_roles();
		ProcedureTaxonomies::register();
		ProcedurePostType::register();
		ApplicationPostType::register();
		ProcedureMetaRegistration::register_meta();
		ApplicationMetaRegistration::register_meta();
		ProcedurePostType::grant_caps_to_roles();
	}

	/**
	 * Un ámbito, colgando de otro si se dice.
	 *
	 * @param string $nombre Term name; empty for a generated one.
	 * @param int    $padre  Parent term ID, 0 for a top-level one.
	 * @return int Term ID.
	 */
	protected function area( string $nombre = '', int $padre = 0 ): int {
		$this->app();
		// El mismo nombre dos veces en un test es el mismo ámbito: wp_insert_term
		// rechaza el duplicado y devolvería un WP_Error que se colaría como 0.
		$existe = '' === $nombre ? null : term_exists( $nombre, ProcedureTaxonomies::AREA, $padre );
		if ( is_array( $existe ) ) {
			return (int) $existe['term_id'];
		}
		$args = array( 'taxonomy' => ProcedureTaxonomies::AREA );
		if ( '' !== $nombre ) {
			$args['name'] = $nombre;
		}
		if ( $padre > 0 ) {
			$args['parent'] = $padre;
		}
		return (int) self::factory()->term->create( $args );
	}

	/**
	 * Un curso escolar.
	 *
	 * @param string $nombre Term name, e.g. `2026-2027`.
	 * @return int Term ID.
	 */
	protected function course( string $nombre = '2026-2027' ): int {
		$this->app();
		return (int) self::factory()->term->create(
			array(
				'taxonomy' => ProcedureTaxonomies::COURSE,
				'name'     => $nombre,
			)
		);
	}

	/**
	 * Alguien que gestiona procedimientos en uno o varios ámbitos.
	 *
	 * Sin ámbitos es el caso que más importa: el perfil a medio rellenar con
	 * el que el acotado tiene que fallar en cerrado.
	 *
	 * @param int[] $areas Term IDs of prc_area.
	 * @return int User ID.
	 */
	protected function manager( array $areas = array() ): int {
		$this->app();
		$id = (int) self::factory()->user->create( array( 'role' => 'prc_manager' ) );
		update_user_meta( $id, ProcedureAccess::USER_AREA_META, array_map( 'intval', $areas ) );
		return $id;
	}

	/**
	 * Alguien del equipo directivo de un centro.
	 *
	 * Sin código es el perfil con el que `prc_apply` no sirve para nada.
	 *
	 * @param string $centre School code; empty for none.
	 * @return int User ID.
	 */
	protected function school_head( string $centre = '' ): int {
		$this->app();
		$id = (int) self::factory()->user->create( array( 'role' => 'prc_school_head' ) );
		if ( '' !== $centre ) {
			update_user_meta( $id, CentreScope::meta_key(), $centre );
		}
		return $id;
	}

	/**
	 * Administración: el rol nativo de WordPress, que trabaja sobre todos los ámbitos.
	 *
	 * @return int User ID.
	 */
	protected function administrator(): int {
		$this->app();
		return (int) self::factory()->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * Un procedimiento publicado, con su ámbito y sus metas.
	 *
	 * @param int                  $autor Author user ID.
	 * @param int[]                $areas Term IDs of prc_area; empty for one still without ámbito.
	 * @param array<string, mixed> $meta  Meta key => value.
	 * @param array<string, mixed> $args  Post fields to override (título, estado…).
	 * @return int Post ID.
	 */
	protected function procedure( int $autor, array $areas = array(), array $meta = array(), array $args = array() ): int {
		$this->app();
		$id = (int) self::factory()->post->create(
			array_merge(
				array(
					'post_type'   => ProcedurePostType::POST_TYPE,
					'post_status' => 'publish',
					'post_author' => $autor,
					'post_title'  => 'Programa de prueba',
				),
				$args
			)
		);
		if ( array() !== $areas ) {
			wp_set_object_terms( $id, array_map( 'intval', $areas ), ProcedureTaxonomies::AREA );
		}
		foreach ( $meta as $clave => $valor ) {
			update_post_meta( $id, $clave, $valor );
		}
		return $id;
	}

	/**
	 * Una solicitud de un centro a un procedimiento.
	 *
	 * @param int                  $procedimiento Parent procedure ID.
	 * @param int                  $autor         Who submits it.
	 * @param string               $centre        School code; by default the author's.
	 * @param array<string, mixed> $meta          Extra meta key => value.
	 * @return int Post ID.
	 */
	protected function application( int $procedimiento, int $autor, string $centre = '', array $meta = array() ): int {
		$this->app();
		if ( '' === $centre ) {
			$centre = CentreScope::code_for( $autor );
		}
		$id   = (int) self::factory()->post->create(
			array(
				'post_type'   => ApplicationPostType::POST_TYPE,
				'post_status' => 'publish',
				'post_parent' => $procedimiento,
				'post_author' => $autor,
				'post_title'  => 'Centro de prueba — ' . get_the_title( $procedimiento ),
			)
		);
		$meta = array_merge(
			array(
				ApplicationMetaKeys::CENTRE_CODE  => $centre,
				ApplicationMetaKeys::CENTRE_NAME  => 'Centro de prueba',
				ApplicationMetaKeys::REVIEW_STATE => ApplicationMetaKeys::REVIEW_SUBMITTED,
			),
			$meta
		);
		foreach ( $meta as $clave => $valor ) {
			update_post_meta( $id, $clave, $valor );
		}
		return $id;
	}

	/**
	 * Las páginas del aplicativo, cada una con su shortcode dentro.
	 *
	 * `Shell::url()` las busca por su slug y `Shell::is_app_page()` mira el
	 * shortcode: sin ellas, media pantalla devuelve la cadena vacía.
	 *
	 * @return void
	 */
	protected function pages(): void {
		foreach ( Shell::SLUGS as $seccion => $slug ) {
			if ( get_page_by_path( $slug ) ) {
				continue;
			}
			self::factory()->post->create(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_name'    => $slug,
					'post_title'   => $slug,
					'post_content' => '[' . Shell::SHORTCODES[ $seccion ] . ']',
				)
			);
		}
	}

	/**
	 * Entrar como alguien.
	 *
	 * @param int $uid User ID.
	 * @return void
	 */
	protected function acting_as( int $uid ): void {
		wp_set_current_user( $uid );
	}

	/**
	 * Preparar un POST, con su nonce si la acción lo lleva.
	 *
	 * @param array<string, mixed> $campos       Fields.
	 * @param string               $accion_nonce Nonce action; empty for none.
	 * @param string               $campo_nonce  Field carrying the nonce.
	 * @return void
	 */
	protected function post( array $campos, string $accion_nonce = '', string $campo_nonce = '_wpnonce' ): void {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST                     = $campos;
		if ( '' !== $accion_nonce ) {
			$nonce                    = wp_create_nonce( $accion_nonce );
			$_POST[ $campo_nonce ]    = $nonce;
			$_REQUEST[ $campo_nonce ] = $nonce;
		}
	}

	/**
	 * Ejecutar un handler y devolver por dónde salió.
	 *
	 * La costura de salida: con `prc_exit_throws` puesto, `Shell::leave()`
	 * lanza {@see ExitSignal} con la URL en vez de terminar el proceso, así
	 * que el test puede leer a dónde iba.
	 *
	 * @param callable $handler What to run.
	 * @return string|null La URL de vuelta; '' si sirvió un documento; null si no salió.
	 */
	protected function exit_url( callable $handler ): ?string {
		add_filter( 'prc_exit_throws', '__return_true' );
		try {
			$handler();
		} catch ( ExitSignal $e ) {
			return $e->url;
		}
		return null;
	}

	/**
	 * Ejecutar algo que sirve un documento y devolver lo servido.
	 *
	 * @param callable $handler What to run.
	 * @return string Output until Shell::leave().
	 */
	protected function served( callable $handler ): string {
		add_filter( 'prc_exit_throws', '__return_true' );
		ob_start();
		try {
			$handler();
		} catch ( ExitSignal $e ) {
			unset( $e );
		} finally {
			$cuerpo = (string) ob_get_clean();
		}
		return $cuerpo;
	}

	/**
	 * El valor de un parámetro en la URL de vuelta.
	 *
	 * @param string $url   Return URL.
	 * @param string $clave Query arg.
	 * @return string Empty when absent.
	 */
	protected function query_arg( string $url, string $clave ): string {
		$args = wp_parse_args( (string) wp_parse_url( $url, PHP_URL_QUERY ) );
		return (string) ( $args[ $clave ] ?? '' );
	}

	/**
	 * Que un test no herede la petición del anterior.
	 */
	public function tear_down() {
		$_POST    = array();
		$_GET     = array();
		$_REQUEST = array();
		$_FILES   = array();
		unset( $_SERVER['REQUEST_METHOD'] );
		parent::tear_down();
	}
}
