<?php
/**
 * Capability checks for procedures and applications: who may do what, scoped
 * by ámbito and by school.
 *
 * @package Prc
 */

namespace Prc\Access;

use Prc\Domain\CentreCatalog;
use Prc\Domain\CentreEligibility;
use Prc\Domain\PeopleSpec;
use Prc\Domain\ProcedureInput;
use Prc\Domain\ProcedureQuestions;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ApplicationPostType;
use Prc\PostType\ProcedurePostType;
use Prc\Taxonomy\ProcedureTaxonomies;

/**
 * El único guardián del aplicativo: aquí se responde «¿quién puede qué?».
 *
 * Tres acotados y los tres fallan en cerrado (ADR-0014, ADR-0016, ADR-0043):
 *
 * - **Por ámbito**: la meta de usuario `prc_area` lleva uno o varios términos
 *   de `prc_area`, y un término incluye a sus descendientes. Sin ámbito y sin
 *   `prc_manage_all_areas`, no se edita ni se ve nada en el taller.
 * - **Por centro**: {@see CentreScope::code_for()}. Sin código, `prc_apply`
 *   no sirve para nada.
 * - **Por zona**: {@see ObserverScope::zones_for()}. Sin zona con centros en
 *   el catálogo, `prc_view_applications` no abre nada; y solo lee, nunca
 *   escribe.
 *
 * La capa que protege es {@see map_meta_cap()}: el acotado del listado del
 * escritorio solo esconde.
 */
final class ProcedureAccess {

	/**
	 * User meta con uno o varios term_id de `prc_area`.
	 */
	public const USER_AREA_META = 'prc_area';

	/**
	 * Presentar y editar la solicitud del propio centro.
	 */
	public const CAP_APPLY            = 'prc_apply';
	public const CAP_APPLY_AS_TEACHER = 'prc_apply_as_teacher';

	/**
	 * Crear, editar, publicar y archivar procedimientos de sus ámbitos.
	 */
	public const CAP_MANAGE_PROCEDURES = 'prc_manage_procedures';

	/**
	 * Ver, admitir, excluir, pedir subsanar y exportar solicitudes.
	 */
	public const CAP_REVIEW = 'prc_review_applications';

	/**
	 * Lo anterior en todos los ámbitos.
	 */
	public const CAP_ALL_AREAS = 'prc_manage_all_areas';

	/**
	 * Ajustes, diagnóstico y desarchivar.
	 */
	public const CAP_MANAGE = 'prc_manage_app';

	/**
	 * Meta capacidad: dar de alta, cambiar o dar de baja personas de la
	 * lista de una solicitud. La resuelve {@see map_meta_cap()} con
	 * {@see can_edit_people()}; no la tiene ningún rol (ADR-0041).
	 */
	public const CAP_EDIT_PEOPLE = 'prc_edit_people';

	/**
	 * Consultar, en solo lectura, las solicitudes de los centros de su zona en
	 * los procedimientos que lo admiten (ADR-0043).
	 */
	public const CAP_VIEW_APPLICATIONS = 'prc_view_applications';

	/**
	 * What {@see can_view_application()} asks: only if it is the user's own, or
	 * whether one of its documents may be downloaded.
	 */
	public const VIEW_OWN      = 'own';
	public const VIEW_DOCUMENT = 'document';

	/**
	 * The procedure whose REST write already passed the publication check.
	 *
	 * La REST trae el curso en la petición y lo escribe después del post:
	 * `guard_parent()` solo vería el de antes y lo devolvería a borrador.
	 *
	 * @var int
	 */
	private static $rest_checked = 0;

	/**
	 * Register filters.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'map_meta_cap', array( self::class, 'map_meta_cap' ), 10, 4 );
		add_filter( 'rest_pre_insert_prc_procedure', array( self::class, 'validate_rest_areas' ), 10, 2 );
		add_action( 'admin_init', array( self::class, 'validate_admin_areas' ) );
		add_filter( 'wp_insert_post_data', array( self::class, 'guard_parent' ), 10, 2 );
		add_action( 'save_post_' . ProcedurePostType::POST_TYPE, array( self::class, 'stamp_area' ), 20 );
	}

	/**
	 * Keep the parent of a procedure or an application from being changed by hand.
	 *
	 * `map_meta_cap` mira el padre que el contenido **tiene**, no el que se le
	 * pide, y los dos tipos tienen pantalla en el escritorio: por `post.php`
	 * (`parent_id`) o por la edición rápida (`post_parent`), quien revisa una
	 * solicitud de su ámbito podía colgarla del procedimiento de otro, y quien
	 * edita un procedimiento, darle un padre y con él el ámbito de otro
	 * (`root_id()`). Un procedimiento no tiene padre nunca y una solicitud no
	 * cambia de procedimiento nunca (ADR-0018). Va en `wp_insert_post_data`
	 * porque es la costura por la que pasan todos esos caminos; el filtro no
	 * puede devolver un error, así que se queda el padre que había.
	 *
	 * El alta de una solicitud no se mira: la crea el aplicativo cuando un
	 * centro solicita, en un procedimiento de cualquier ámbito.
	 *
	 * Es también donde se para la publicación de un procedimiento que no está
	 * listo ({@see is_unready_publication()}).
	 *
	 * @param array<string, mixed> $data    Sanitized post fields about to be written.
	 * @param array<string, mixed> $postarr Raw post array, with the ID on updates.
	 * @return array<string, mixed>
	 */
	public static function guard_parent( array $data, array $postarr ): array {
		$tipo = (string) ( $data['post_type'] ?? '' );
		// Sin nadie delante —cron, la provisión— no hay ámbito que acotar.
		if ( get_current_user_id() <= 0 || ! in_array( $tipo, self::scoped_types(), true ) ) {
			return $data;
		}
		$post_id = absint( $postarr['ID'] ?? 0 );
		if ( $post_id > 0 ) {
			$data['post_parent'] = (int) get_post_field( 'post_parent', $post_id );
			// La misma costura sirve para no publicar a medias por el
			// escritorio, la edición rápida o un `wp_update_post()`: sin curso
			// o con entregas sin fechas, se queda en borrador. La REST ya lo
			// rechazó con su motivo en `validate_rest_areas()`.
			if ( ProcedurePostType::POST_TYPE === $tipo ) {
				$por_rest           = self::$rest_checked === $post_id;
				self::$rest_checked = 0;
				if ( ! $por_rest && self::is_unready_publication( $post_id, (string) ( $data['post_status'] ?? '' ), $postarr['tax_input'][ ProcedureTaxonomies::COURSE ] ?? null ) ) {
					$data['post_status'] = 'draft';
				}
			}
		} elseif ( ProcedurePostType::POST_TYPE === $tipo ) {
			$data['post_parent'] = 0;
		}
		return $data;
	}

	/**
	 * Whether this write would publish a procedure that is not ready yet.
	 *
	 * Sin curso o con una entrega con plazo propio sin fechas
	 * ({@see ProcedureInput::not_ready()}) no se publica, entre por donde
	 * entre. Solo en el paso de no publicado a publicado o programado: lo que
	 * ya está publicado se sigue guardando igual, aunque no tenga curso.
	 *
	 * @param int        $post_id Procedure ID; 0 when it is being created.
	 * @param string     $status  Status the write asks for.
	 * @param mixed      $course  Course the write asks for: term IDs or names; null keeps the stored one.
	 * @param array|null $why     Set to what is missing.
	 * @return bool
	 */
	private static function is_unready_publication( int $post_id, string $status, $course, ?array &$why = null ): bool {
		$why        = array();
		$publicados = array( 'publish', 'future' );
		if ( ! in_array( $status, $publicados, true ) || ( $post_id > 0 && in_array( get_post_status( $post_id ), $publicados, true ) ) ) {
			return false;
		}
		if ( null === $course ) {
			$course = $post_id > 0 ? wp_get_post_terms( $post_id, ProcedureTaxonomies::COURSE, array( 'fields' => 'ids' ) ) : array();
		}
		$curso = 0;
		foreach ( is_array( $course ) ? $course : explode( ',', (string) $course ) as $valor ) {
			$term = is_numeric( $valor ) ? get_term( (int) $valor, ProcedureTaxonomies::COURSE ) : get_term_by( 'name', trim( (string) $valor ), ProcedureTaxonomies::COURSE );
			if ( $term instanceof \WP_Term && 0 === $curso ) {
				$curso = (int) $term->term_id;
			}
		}
		$preguntas = ProcedureQuestions::sanitize( $post_id > 0 ? get_post_meta( $post_id, ProcedureMetaKeys::QUESTIONS, true ) : array() );
		$why       = ProcedureInput::not_ready( $curso, $preguntas );
		return array() !== $why;
	}

	/**
	 * Assign a new procedure the creator's scope before it can remain unscoped.
	 *
	 * @param int $post_id Procedure ID.
	 */
	public static function stamp_area( int $post_id ): void {
		if ( array() !== self::post_areas( $post_id ) ) {
			return;
		}
		$areas = self::user_areas( (int) get_post_field( 'post_author', $post_id ) );
		if ( array() !== $areas ) {
			wp_set_object_terms( $post_id, $areas, ProcedureTaxonomies::AREA );
		}
	}

	/**
	 * Resolve REST creation and explicit assignment before saving the post.
	 *
	 * @param mixed            $prepared Prepared post or prior error.
	 * @param \WP_REST_Request $request REST request.
	 * @return mixed
	 */
	public static function validate_rest_areas( $prepared, $request ) {
		if ( is_wp_error( $prepared ) ) {
			return $prepared;
		}
		$post_id = absint( $request->get_url_params()['id'] ?? 0 );
		$final   = null;
		if ( $request->has_param( ProcedureTaxonomies::AREA ) ) {
			$final = self::resolve_area_assignment( $post_id, (array) $request->get_param( ProcedureTaxonomies::AREA ) );
		} elseif ( 0 === $post_id && ! self::can_edit_all_areas() ) {
			$assigned = self::user_areas();
			$final    = array() === $assigned
				? new \WP_Error( 'prc_area_forbidden', self::scope_assignment_message( get_current_user_id() ), array( 'status' => 403 ) )
				: self::resolve_area_assignment( 0, $assigned );
		}
		if ( is_wp_error( $final ) ) {
			return $final;
		}
		if ( 0 === $post_id && ! self::can_edit_all_areas() && in_array( $request->get_param( 'status' ), array( 'publish', 'future', 'private' ), true ) ) {
			return new \WP_Error( 'prc_scope_draft_first', 'Cree el procedimiento como borrador antes de publicarlo, para que tenga ámbito desde el principio.', array( 'status' => 400 ) );
		}
		// Sin curso o con entregas sin fechas no se publica, y la REST dice por qué.
		$faltan = array();
		if ( self::is_unready_publication( $post_id, (string) $request->get_param( 'status' ), $request->has_param( ProcedureTaxonomies::COURSE ) ? $request->get_param( ProcedureTaxonomies::COURSE ) : null, $faltan ) ) {
			return new \WP_Error( 'prc_not_ready', 'Todavía no se puede publicar. ' . implode( ' ', $faltan ), array( 'status' => 400 ) );
		}
		self::$rest_checked = $post_id;
		if ( null !== $final ) {
			$request->set_param( ProcedureTaxonomies::AREA, $final );
		}
		return $prepared;
	}

	/**
	 * Resolve selected terms while preserving organisers outside the editor's scope.
	 *
	 * @param int               $post_id   Existing post ID, or zero for a new post.
	 * @param array<int, mixed> $requested Terms explicitly selected by this actor.
	 * @param int               $user_id   User ID (0 = current).
	 * @return int[]|\WP_Error Final term IDs or a denial.
	 */
	public static function resolve_area_assignment( int $post_id, array $requested, int $user_id = 0 ) {
		$user_id = $user_id > 0 ? $user_id : get_current_user_id();
		$ids     = array();
		foreach ( $requested as $value ) {
			if ( ! is_scalar( $value ) ) {
				return new \WP_Error( 'prc_area_invalid', 'El ámbito solicitado no existe.', array( 'status' => 400 ) );
			}
			$value = trim( (string) $value );
			$id    = ctype_digit( $value ) ? absint( $value ) : 0;
			if ( $id <= 0 || ! ( get_term( $id, ProcedureTaxonomies::AREA ) instanceof \WP_Term ) ) {
				return new \WP_Error( 'prc_area_invalid', 'El ámbito solicitado no existe.', array( 'status' => 400 ) );
			}
			$ids[] = $id;
		}
		$ids = array_values( array_unique( $ids ) );
		if ( self::can_edit_all_areas( $user_id ) ) {
			return $ids;
		}
		$allowed = self::scope_areas( $user_id );
		if ( array() === $allowed ) {
			return new \WP_Error( 'prc_area_forbidden', self::scope_assignment_message( $user_id ), array( 'status' => 403 ) );
		}
		if ( array_diff( $ids, $allowed ) ) {
			return new \WP_Error( 'prc_area_forbidden', 'No puede asignar un ámbito de otra rama.', array( 'status' => 403 ) );
		}
		$foreign = $post_id > 0 ? array_diff( self::post_areas( $post_id ), $allowed ) : array();
		$final   = array_values( array_unique( array_merge( $ids, $foreign ) ) );
		if ( array() === $final ) {
			return new \WP_Error( 'prc_area_required', 'El contenido debe conservar al menos un ámbito organizador.', array( 'status' => 400 ) );
		}
		return $final;
	}

	/**
	 * Check every term requested for a scoped procedure.
	 *
	 * @param array<int, mixed> $requested Term IDs.
	 * @param int               $user_id User ID (0 = current).
	 * @return bool
	 */
	public static function may_assign_areas( array $requested, int $user_id = 0 ): bool {
		if ( self::can_edit_all_areas( $user_id ) ) {
			return true;
		}
		if ( array() === $requested ) {
			return false;
		}
		$effective_user = $user_id > 0 ? $user_id : get_current_user_id();
		$allowed        = self::scope_areas( $effective_user );
		foreach ( $requested as $term_id ) {
			$id = absint( $term_id );
			if ( ! ( get_term( $id, ProcedureTaxonomies::AREA ) instanceof \WP_Term ) || ! in_array( $id, $allowed, true ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Validate only the interactive wp-admin post form.
	 */
	public static function validate_admin_areas(): void {
		global $pagenow;
		// Core verifies the post nonce before writing; this guard does not persist data.
		// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$action = isset( $_POST['action'] ) ? (string) $_POST['action'] : '';
		if ( ! ( 'post.php' === $pagenow && 'editpost' === $action ) && ! ( 'admin-ajax.php' === $pagenow && 'inline-save' === $action ) && ! ( 'edit.php' === $pagenow && in_array( $action, array( 'edit', 'bulk_edit' ), true ) ) ) {
			return;
		}
		$final = self::admin_area_assignment( wp_unslash( $_POST ) );
		if ( null === $final ) {
			return;
		}
		if ( is_wp_error( $final ) ) {
			wp_die( esc_html( $final->get_error_message() ), 'Ámbito no permitido', array( 'response' => absint( $final->get_error_data()['status'] ?? 403 ) ) );
		}
		$_POST['tax_input'][ ProcedureTaxonomies::AREA ] = $final;
		// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}

	/**
	 * Resolve the ámbito an interactive admin post request asks for, before it alters the terms.
	 *
	 * @param array<string, mixed> $request Unslashed admin request.
	 * @return int[]|\WP_Error|null Final term IDs, a denial, or null when the request does not touch the ámbito.
	 */
	public static function admin_area_assignment( array $request ) {
		if ( ProcedurePostType::POST_TYPE !== ( $request['post_type'] ?? '' ) || ( ! isset( $request['tax_input'][ ProcedureTaxonomies::AREA ] ) && empty( $request['prc_area_present'] ) ) ) {
			return null;
		}
		$requested = $request['tax_input'][ ProcedureTaxonomies::AREA ] ?? array();
		$requested = is_array( $requested ) ? $requested : explode( ',', (string) $requested );
		return self::resolve_area_assignment( absint( $request['post_ID'] ?? 0 ), $requested );
	}

	/**
	 * The post types this guard scopes.
	 *
	 * @return string[]
	 */
	public static function scoped_types(): array {
		return array( ProcedurePostType::POST_TYPE, ApplicationPostType::POST_TYPE );
	}

	/**
	 * Whether the user administers the application itself.
	 *
	 * @param int $user_id User ID (0 = current).
	 * @return bool
	 */
	public static function is_manager( int $user_id = 0 ): bool {
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
		return user_can( $user_id, self::CAP_MANAGE ) || user_can( $user_id, 'manage_options' );
	}

	/**
	 * Whether a user may save JavaScript for a procedure.
	 *
	 * `unfiltered_html` stays required because the snippet executes on a public page;
	 * WordPress removes it from site administrators on multisite installations.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Procedure ID.
	 * @return bool
	 */
	public static function can_edit_custom_js( int $user_id, int $post_id ): bool {
		return self::is_manager( $user_id ) && user_can( $user_id, 'unfiltered_html' ) && self::can_edit( $user_id, $post_id );
	}

	/**
	 * Whether the user works across every ámbito (administración).
	 *
	 * @param int $user_id User ID (0 = current).
	 * @return bool
	 */
	public static function can_edit_all_areas( int $user_id = 0 ): bool {
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
		return user_can( $user_id, self::CAP_ALL_AREAS ) || self::is_manager( $user_id );
	}

	/**
	 * Whether the user may manage procedures at all, and see the workspace.
	 *
	 * Falla en cerrado: la capacidad sin ningún ámbito en el perfil no abre nada.
	 *
	 * @param int $user_id User ID (0 = current).
	 * @return bool
	 */
	public static function can_manage_procedures( int $user_id = 0 ): bool {
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
		if ( $user_id <= 0 || ! user_can( $user_id, self::CAP_MANAGE_PROCEDURES ) ) {
			return false;
		}
		return self::can_edit_all_areas( $user_id ) || array() !== self::user_areas( $user_id );
	}

	/**
	 * Ámbitos the user belongs to, as written in the profile.
	 *
	 * @param int $user_id User ID (0 = current).
	 * @return int[] Term IDs of prc_area; empty when the profile has none.
	 */
	public static function user_areas( int $user_id = 0 ): array {
		$assignment = self::scope_assignment_state( $user_id );
		return 'resolved' === $assignment['state'] ? $assignment['ids'] : array();
	}

	/**
	 * Classify the stored profile without changing it or granting ambiguous access.
	 *
	 * @param int $user_id User ID (0 = current).
	 * @return array{state:string,ids:int[],invalid:string[],legacy:bool}
	 */
	public static function scope_assignment_state( int $user_id = 0 ): array {
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
		if ( $user_id <= 0 ) {
			return array(
				'state'   => 'empty',
				'ids'     => array(),
				'invalid' => array(),
				'legacy'  => false,
			);
		}
		$raw     = get_user_meta( $user_id, self::USER_AREA_META, true );
		$scalar  = is_scalar( $raw ) ? trim( (string) $raw ) : '';
		$legacy  = ! is_array( $raw ) && '' !== $scalar;
		$values  = is_array( $raw ) ? $raw : ( '' === $scalar ? array() : explode( ',', $scalar ) );
		$ids     = array();
		$invalid = array();
		foreach ( $values as $value ) {
			$value = is_scalar( $value ) ? trim( (string) $value ) : '';
			$id    = ctype_digit( $value ) ? absint( $value ) : 0;
			if ( $id > 0 && get_term( $id, ProcedureTaxonomies::AREA ) instanceof \WP_Term ) {
				$ids[] = $id;
			} else {
				$invalid[] = $value;
			}
		}
		$ids   = array_values( array_unique( $ids ) );
		$state = $invalid ? 'invalid' : ( count( $ids ) > 1 ? 'ambiguous' : ( $ids ? 'resolved' : 'empty' ) );
		return compact( 'state', 'ids', 'invalid', 'legacy' );
	}

	/**
	 * Human explanation shared by lists and object-level denial messages.
	 *
	 * @param int $user_id User ID.
	 * @return string Explanation.
	 */
	public static function scope_assignment_message( int $user_id ): string {
		$state = self::scope_assignment_state( $user_id )['state'];
		if ( 'ambiguous' === $state ) {
			return 'Su perfil conserva varios ámbitos y necesita que administración elija uno.';
		}
		if ( 'invalid' === $state ) {
			return 'Su perfil contiene un ámbito inválido y necesita que administración lo revise.';
		}
		return 'No tiene ningún ámbito asignado en su perfil. El ámbito lo pone quien administra el aplicativo.';
	}

	/**
	 * Ámbitos the user reaches: the profile ones and every descendant.
	 *
	 * Un servicio alcanza a sus áreas: quien tiene el término padre gestiona
	 * lo que cuelga de él sin que nadie le añada cada hija a mano.
	 *
	 * @param int $user_id User ID (0 = current).
	 * @return int[] Term IDs of prc_area.
	 */
	public static function scope_areas( int $user_id = 0 ): array {
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
		static $cache = array();
		$assigned     = self::user_areas( $user_id );
		$key          = $user_id . ':' . implode( ',', $assigned ) . ':' . wp_cache_get_last_changed( 'terms' );
		if ( isset( $cache[ $key ] ) ) {
			return $cache[ $key ];
		}
		$areas = array();
		foreach ( $assigned as $term_id ) {
			$children = get_term_children( $term_id, ProcedureTaxonomies::AREA );
			if ( is_wp_error( $children ) ) {
				return array();
			}
			$areas = array_merge( $areas, array( $term_id ), $children );
		}
		$cache[ $key ] = self::clean_ids( $areas );
		return $cache[ $key ];
	}

	/**
	 * The accounts that manage the ámbitos of a procedure.
	 *
	 * Quien tiene la capacidad de gestionar procedimientos y un ámbito en su
	 * perfil que alcanza a alguno de los del procedimiento —el mismo o uno
	 * por encima—: la regla de {@see in_scope()}, leída al revés. Quien
	 * trabaja sobre todos los ámbitos no entra: alcanzarlos todos no es
	 * gestionar este, y sería mandarle cada aviso del sitio.
	 *
	 * @param int $procedure_id Procedure ID, or an application under it.
	 * @return int[] User IDs.
	 */
	public static function area_managers( int $procedure_id ): array {
		$theirs = $procedure_id > 0 ? self::post_areas( $procedure_id ) : array();
		if ( array() === $theirs ) {
			return array();
		}
		// Una consulta para las cuentas, con sus metas ya en caché
		// (`all_with_meta`): lo que se mira después de cada una —capacidades y
		// ámbito— no vuelve a la base de datos.
		$candidatos = get_users(
			array(
				'capability'   => self::CAP_MANAGE_PROCEDURES,
				'fields'       => 'all_with_meta',
				'meta_key'     => self::USER_AREA_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- solo quien tiene un ámbito en el perfil puede gestionarlo.
				'meta_compare' => 'EXISTS',
			)
		);
		$out        = array();
		foreach ( (array) $candidatos as $user ) {
			if ( ! $user instanceof \WP_User || self::can_edit_all_areas( (int) $user->ID ) ) {
				continue;
			}
			if ( array() !== array_intersect( self::scope_areas( (int) $user->ID ), $theirs ) ) {
				$out[] = (int) $user->ID;
			}
		}
		return $out;
	}

	/**
	 * Ámbitos a post belongs to.
	 *
	 * Una solicitud no lleva ámbito propio: el de su procedimiento manda.
	 *
	 * @param int $post_id Post ID.
	 * @return int[] Term IDs of prc_area.
	 */
	public static function post_areas( int $post_id ): array {
		$terms = get_the_terms( self::root_id( $post_id ), ProcedureTaxonomies::AREA );
		if ( ! is_array( $terms ) ) {
			return array();
		}
		return self::clean_ids( wp_list_pluck( $terms, 'term_id' ) );
	}

	/**
	 * The procedure a post belongs to (itself when it is the procedure).
	 *
	 * @param int $post_id Post ID.
	 * @return int
	 */
	public static function root_id( int $post_id ): int {
		$parent = (int) get_post_field( 'post_parent', $post_id );
		return $parent > 0 ? $parent : $post_id;
	}

	/**
	 * Whether this procedure is closed for good: the «histórico» mark.
	 *
	 * Se mira siempre en el procedimiento, así que alcanza a sus solicitudes.
	 *
	 * @param int $post_id Post ID; the procedure, or an application under it.
	 * @return bool
	 */
	public static function is_archived( int $post_id ): bool {
		if ( $post_id <= 0 ) {
			return false;
		}
		return (bool) get_post_meta( self::root_id( $post_id ), ProcedureMetaKeys::ARCHIVED, true );
	}

	/**
	 * Whether the user may MARK this procedure as «histórico».
	 *
	 * Lo marca el ámbito que lo convocó. La puerta es {@see can_open()} y no
	 * {@see can_edit()} a propósito: `can_edit()` lleva el cierre encima, y
	 * lo primero que hace la marca es quitar esa posibilidad.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Procedure ID.
	 * @return bool
	 */
	public static function can_archive( int $user_id, int $post_id ): bool {
		return self::can_open( $user_id, $post_id );
	}

	/**
	 * Whether the user may UNMARK a procedure as «histórico».
	 *
	 * Solo administración (ADR-0023): cerrar lo tuyo es tuyo, reabrirlo
	 * necesita a otra persona.
	 *
	 * @param int $user_id User ID (0 = current).
	 * @return bool
	 */
	public static function can_unarchive( int $user_id = 0 ): bool {
		return self::is_manager( $user_id );
	}

	/**
	 * Whether the user may write the «histórico» mark as it stands today.
	 *
	 * Lo que pregunta el `auth_callback` de la meta, que no sabe qué valor se
	 * va a escribir: abierto, la marca la toca su ámbito; cerrado, solo
	 * administración.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Procedure ID.
	 * @return bool
	 */
	public static function can_toggle_archived( int $user_id, int $post_id ): bool {
		return self::is_archived( $post_id )
			? self::can_unarchive( $user_id )
			: self::can_archive( $user_id, $post_id );
	}

	/**
	 * Whether the user may open this procedure in the workspace, even if only to read it.
	 *
	 * Es la regla del ámbito, sin el cierre por encima: un procedimiento
	 * histórico se sigue consultando y exportando desde su taller.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Procedure ID, or an application under it.
	 * @return bool
	 */
	public static function can_open( int $user_id, int $post_id ): bool {
		if ( $user_id <= 0 || ! user_can( $user_id, self::CAP_MANAGE_PROCEDURES ) ) {
			return false;
		}
		return self::in_scope( $user_id, $post_id );
	}

	/**
	 * Whether the user may duplicate this procedure, and under which ámbitos (PR 8 del PLAN-0003).
	 *
	 * Quien lo abre en el taller y puede crear procedimientos —quien solo
	 * consulta, no—, y nunca desde la papelera: duplicar es
	 * leer el original y dar de alta otro. Por eso vale en cualquier estado,
	 * histórico incluido —el original no se toca—, y quien no tiene ámbito no
	 * duplica, igual que no crea.
	 *
	 * Los ámbitos del duplicado son los del original que quien duplica puede
	 * poner (ADR-0025): con todos, todos; si no, los que alcanza su perfil. Si
	 * no alcanza ninguno, el suyo, como en un alta normal.
	 *
	 * @param int        $user_id User ID.
	 * @param int        $post_id Procedure ID.
	 * @param array|null $areas   Set to the term IDs of prc_area of the duplicate; null when it may not.
	 * @return bool
	 */
	public static function can_duplicate( int $user_id, int $post_id, ?array &$areas = null ): bool {
		$areas = null;
		// En la papelera no: primero se restaura, y luego se duplica.
		if ( ProcedurePostType::POST_TYPE !== get_post_type( $post_id ) || 'trash' === get_post_status( $post_id ) || ! self::can_open( $user_id, $post_id ) || ! self::can_manage_procedures( $user_id ) ) {
			return false;
		}
		$suyos = self::post_areas( $post_id );
		$areas = self::can_edit_all_areas( $user_id ) ? $suyos : array_values( array_intersect( $suyos, self::scope_areas( $user_id ) ) );
		$areas = array() !== $areas ? $areas : self::user_areas( $user_id );
		return true;
	}

	/**
	 * Whether the user may edit this procedure.
	 *
	 * La regla del ámbito y, por encima, el cierre: histórico no lo edita el
	 * ámbito que lo convocó, y sí administración. `map_meta_cap()` manda aquí
	 * `edit_post`, `delete_post` y `publish_post`, así que alcanza también al
	 * escritorio, a la edición rápida y a la REST.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Procedure ID.
	 * @return bool
	 */
	public static function can_edit( int $user_id, int $post_id ): bool {
		if ( ! self::can_open( $user_id, $post_id ) ) {
			return false;
		}
		return ! self::is_archived( $post_id ) || self::is_manager( $user_id );
	}

	/**
	 * Whether the user may edit that group of data, in the state the procedure is in today.
	 *
	 * El permiso es del dominio y no de la pantalla: qué deja tocar cada
	 * estado lo dice {@see ProcedureState::editable_fields()} y aquí se cruza
	 * con quién pregunta. Lo comprueba el POST antes de escribir; la pantalla
	 * solo pinta apagado lo mismo.
	 *
	 * @param int    $user_id      User ID.
	 * @param int    $procedure_id Procedure ID.
	 * @param string $group        One of {@see ProcedureState::groups()}.
	 * @return bool
	 */
	public static function can_edit_group( int $user_id, int $procedure_id, string $group ): bool {
		if ( ! self::can_edit( $user_id, $procedure_id ) ) {
			return false;
		}
		$estado = ProcedureState::of_post( $procedure_id );
		// Histórico no lo juzga la tabla de estados: ya lo decidió can_edit()
		// —cerrado para el ámbito que convocó y abierto para administración,
		// que es quien tiene que poder rematarlo y reabrirlo (ADR-0023)—.
		return ProcedureMetaKeys::STATE_ARCHIVED === $estado
			|| in_array( $group, ProcedureState::editable_fields( $estado ), true );
	}

	/**
	 * Whether the user may publish procedures, and this one in particular.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Procedure ID (0 = just the capability).
	 * @return bool
	 */
	public static function can_publish( int $user_id, int $post_id = 0 ): bool {
		if ( $user_id <= 0 || ! user_can( $user_id, 'publish_prc_procedures' ) ) {
			return false;
		}
		if ( $post_id <= 0 ) {
			return true;
		}
		return self::can_edit( $user_id, $post_id );
	}

	/**
	 * Whether the user may review the applications of this procedure.
	 *
	 * La capacidad, el ámbito y el cierre: histórico no se gestiona, salvo
	 * por administración.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Procedure ID, or an application under it.
	 * @return bool
	 */
	public static function can_review( int $user_id, int $post_id ): bool {
		if ( $user_id <= 0 || ! user_can( $user_id, self::CAP_REVIEW ) || ! self::in_scope( $user_id, $post_id ) ) {
			return false;
		}
		return ! self::is_archived( $post_id ) || self::is_manager( $user_id );
	}

	/**
	 * Whether the user may apply on behalf of a school, and to this procedure.
	 *
	 * Quién, no cuándo: el plazo lo mira la pantalla con el estado. Con
	 * procedimiento, lo que dice {@see apply_codes()}; con código, además, que
	 * sea uno de esos: un código que no es de la cuenta no sirve nunca.
	 *
	 * @param int    $user_id User ID.
	 * @param int    $post_id Procedure ID (0 = just the capability and the school).
	 * @param string $code    Code to apply with; empty for any of the person's.
	 * @return bool
	 */
	public static function can_apply( int $user_id, int $post_id = 0, string $code = '' ): bool {
		if ( $user_id <= 0 || ! user_can( $user_id, self::CAP_APPLY ) ) {
			return false;
		}
		if ( $post_id <= 0 ) {
			return user_can( $user_id, self::CAP_APPLY_AS_TEACHER ) || array() !== CentreScope::codes_for( $user_id );
		}
		if ( ProcedurePostType::POST_TYPE !== get_post_type( $post_id ) || 'publish' !== get_post_status( $post_id ) ) {
			return false;
		}
		if ( ProcedureMetaKeys::AUDIENCE_TEACHERS === get_post_meta( $post_id, ProcedureMetaKeys::AUDIENCE, true ) ) {
			return '' === $code && user_can( $user_id, self::CAP_APPLY_AS_TEACHER );
		}
		$codes = $post_id > 0 ? self::apply_codes( $user_id, $post_id ) : CentreScope::codes_for( $user_id );
		return '' === $code ? array() !== $codes : isset( $codes[ $code ] );
	}

	/**
	 * The codes of a person that this procedure takes, school first.
	 *
	 * Tiene que estar publicado, admitir la clase del código —centro o
	 * agrupación de escuelas rurales, ADR-0031; sin la meta, todas— y que el
	 * código pase los acotados del procedimiento: titularidad, tipo, isla y
	 * lista cerrada ({@see CentreEligibility}). Cada código se mira con su
	 * propia entrada del catálogo, también el de una agrupación, salvo la
	 * titularidad, que a una agrupación como colectivo no la filtra
	 * (ADR-0031). Con algún
	 * acotado, un código que no está en el catálogo no solicita; sin ninguno,
	 * sí, porque el catálogo puede ir por detrás de la realidad (ADR-0042).
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Procedure ID.
	 * @return array<string, string> Code => kind, as CentreScope::codes_for().
	 */
	public static function apply_codes( int $user_id, int $post_id ): array {
		$out = array();
		foreach ( self::kind_codes( $user_id, $post_id ) as $code => $kind ) {
			if ( '' === self::why_not_code( $post_id, (string) $code, $kind ) || self::has_application( $post_id, (string) $code ) ) {
				$out[ $code ] = $kind;
			}
		}
		return $out;
	}

	/**
	 * Whether that code already has an application to this procedure.
	 *
	 * La elegibilidad se mira al **crear** la solicitud: un centro que ya la
	 * presentó la sigue editando o subsanando cuando el estado lo permita,
	 * aunque el procedimiento se acote después (ADR-0042). Solo se pregunta
	 * cuando el código no pasa los acotados.
	 *
	 * @param int    $post_id Procedure ID.
	 * @param string $code    School code.
	 * @return bool
	 */
	private static function has_application( int $post_id, string $code ): bool {
		$ids = get_posts(
			array(
				'post_type'        => ApplicationPostType::POST_TYPE,
				'post_parent'      => $post_id,
				'post_status'      => 'publish',
				'numberposts'      => 1,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'meta_key'         => ApplicationMetaKeys::CENTRE_CODE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- la solicitud de un centro se busca por su código (ADR-0018).
				'meta_value'       => $code, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- ídem.
				'suppress_filters' => true,
			)
		);
		return array() !== $ids;
	}

	/**
	 * The codes of a person whose kind this procedure takes, eligible or not.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Procedure ID.
	 * @return array<string, string> Code => kind; empty when it is not a published procedure for schools.
	 */
	private static function kind_codes( int $user_id, int $post_id ): array {
		if ( ProcedurePostType::POST_TYPE !== get_post_type( $post_id ) || 'publish' !== get_post_status( $post_id ) ) {
			return array();
		}
		if ( ProcedureMetaKeys::AUDIENCE_SCHOOLS !== ProcedureMetaKeys::in_list( get_post_meta( $post_id, ProcedureMetaKeys::AUDIENCE, true ), ProcedureMetaKeys::audiences(), ProcedureMetaKeys::AUDIENCE_SCHOOLS ) ) {
			return array();
		}
		return array_intersect( CentreScope::codes_for( $user_id ), self::centre_kinds( $post_id ) );
	}

	/**
	 * The rules a procedure narrows its schools with, as stored.
	 *
	 * @param int $post_id Procedure ID.
	 * @return array{ownership:string[], types:string[], islands:string[], codes:string[]}
	 */
	public static function eligibility( int $post_id ): array {
		$leer = static function ( string $clave ) use ( $post_id ): array {
			$valor = get_post_meta( $post_id, $clave, true );
			return is_array( $valor ) ? $valor : array();
		};
		return CentreEligibility::rules(
			array(
				'ownership' => $leer( ProcedureMetaKeys::OWNERSHIP ),
				'types'     => $leer( ProcedureMetaKeys::CENTRE_TYPES ),
				'islands'   => $leer( ProcedureMetaKeys::ISLANDS ),
				'codes'     => $leer( ProcedureMetaKeys::ALLOWED_CENTRES ),
			)
		);
	}

	/**
	 * Why that code may not apply to this procedure by its rules, in words.
	 *
	 * Solo los acotados del centro: quién es la cuenta y qué clase de código
	 * admite el procedimiento se miran antes. La titularidad no se le pide a
	 * una agrupación de escuelas rurales que solicita como colectivo.
	 *
	 * @param int    $post_id Procedure ID.
	 * @param string $code    School code.
	 * @param string $kind    One of ProcedureMetaKeys::centre_kinds().
	 * @return string Empty when it may.
	 */
	private static function why_not_code( int $post_id, string $code, string $kind = ProcedureMetaKeys::KIND_SCHOOL ): string {
		$rules = self::eligibility( $post_id );
		if ( ProcedureMetaKeys::KIND_RURAL_GROUP === $kind ) {
			$rules['ownership'] = array();
		}
		return CentreEligibility::restricted( $rules )
			? CentreEligibility::why_not( $rules, $code, CentreCatalog::find( $code ), array() !== CentreCatalog::all() )
			: '';
	}

	/**
	 * Why none of the person's codes —or that one— may apply by the procedure's rules.
	 *
	 * Es lo que lee el centro en su pantalla: «este procedimiento es solo
	 * para…». Con un código, el motivo de ese; sin él, el del primero. Un
	 * código que ya tiene solicitud no tiene motivo: la sigue editando.
	 *
	 * @param int    $user_id User ID.
	 * @param int    $post_id Procedure ID.
	 * @param string $code    Code asked for; empty for any of the person's.
	 * @return string Empty when some code may, or when the reason is not the rules.
	 */
	public static function why_not_eligible( int $user_id, int $post_id, string $code = '' ): string {
		$codes = self::kind_codes( $user_id, $post_id );
		if ( '' !== $code ) {
			$codes = array_intersect_key( $codes, array( $code => true ) );
		}
		$primero = '';
		foreach ( $codes as $uno => $kind ) {
			$porque = self::why_not_code( $post_id, (string) $uno, $kind );
			if ( '' === $porque || self::has_application( $post_id, (string) $uno ) ) {
				return '';
			}
			$primero = '' !== $primero ? $primero : $porque;
		}
		return $primero;
	}

	/**
	 * The centre kinds a procedure takes; every kind when it says none.
	 *
	 * @param int $post_id Procedure ID.
	 * @return string[]
	 */
	public static function centre_kinds( int $post_id ): array {
		$kinds = ProcedureInput::centre_kinds( get_post_meta( $post_id, ProcedureMetaKeys::CENTRE_KINDS, true ) );
		return array() === $kinds ? array_keys( ProcedureMetaKeys::centre_kinds() ) : $kinds;
	}

	/**
	 * Whether the user may see this application: their school's, one they
	 * review, or one of their zone in a procedure that admits consultation.
	 *
	 * `$purpose` acota la pregunta:
	 *
	 * - {@see VIEW_OWN}: solo si es **suya** —de su centro, o su solicitud
	 *   individual de profesorado—. Es lo que pregunta el correo que lleva la
	 *   nota de revisión: verla por la consulta no basta.
	 * - {@see VIEW_DOCUMENT}: si puede descargar el documento de la pregunta
	 *   `$question_key`. Quien la presentó y quien la revisa, siempre; quien la
	 *   consulta por su zona, solo con `prc_observer_files` y si esa pregunta
	 *   está entre las columnas que la consulta enseña. Lo pregunta la descarga
	 *   de {@see \Prc\PublicFront\ApplicationFiles}, que no se fía del
	 *   identificador del documento (ADR-0028, ADR-0043).
	 *
	 * @param int    $user_id        User ID.
	 * @param int    $application_id Application ID.
	 * @param string $purpose        Empty to read it; {@see VIEW_OWN} or {@see VIEW_DOCUMENT}.
	 * @param string $question_key   With {@see VIEW_DOCUMENT}, the question the document answers.
	 * @return bool
	 */
	public static function can_view_application( int $user_id, int $application_id, string $purpose = '', string $question_key = '' ): bool {
		if ( $user_id <= 0 || ApplicationPostType::POST_TYPE !== get_post_type( $application_id ) ) {
			return false;
		}
		$propia = self::is_own_application( $user_id, $application_id );
		if ( self::VIEW_OWN === $purpose ) {
			return $propia;
		}
		if ( $propia || self::can_review( $user_id, $application_id ) ) {
			return true;
		}
		if ( ! self::can_observe_application( $user_id, $application_id ) ) {
			return false;
		}
		if ( self::VIEW_DOCUMENT !== $purpose ) {
			return true;
		}
		$raiz = self::root_id( $application_id );
		return '' !== $question_key
			&& (bool) get_post_meta( $raiz, ProcedureMetaKeys::OBSERVER_FILES, true )
			&& in_array( $question_key, (array) get_post_meta( $raiz, ProcedureMetaKeys::OBSERVER_COLUMNS, true ), true );
	}

	/**
	 * Whether this application is of the user's school, or the user's own as a teacher.
	 *
	 * @param int $user_id        User ID.
	 * @param int $application_id Application ID.
	 * @return bool
	 */
	private static function is_own_application( int $user_id, int $application_id ): bool {
		if ( ! user_can( $user_id, self::CAP_APPLY ) ) {
			return false;
		}
		if ( ApplicationMetaKeys::TYPE_TEACHER === get_post_meta( $application_id, ApplicationMetaKeys::APPLICANT_TYPE, true ) ) {
			return (int) get_post_field( 'post_author', $application_id ) === $user_id
				&& user_can( $user_id, self::CAP_APPLY_AS_TEACHER );
		}
		$owner = (string) get_post_meta( $application_id, ApplicationMetaKeys::CENTRE_CODE, true );
		return '' !== $owner && isset( CentreScope::codes_for( $user_id )[ $owner ] );
	}

	/**
	 * Whether a school may deliver this document for its existing application.
	 *
	 * @param int                  $user_id Current user.
	 * @param int                  $application_id Application ID.
	 * @param array<string, mixed> $question Stored question, never request data.
	 * @return bool
	 */
	public static function can_deliver( int $user_id, int $application_id, array $question ): bool {
		if ( ! self::can_view_application( $user_id, $application_id ) || ! user_can( $user_id, self::CAP_APPLY ) ) {
			return false;
		}
		$procedure_id = (int) wp_get_post_parent_id( $application_id );
		$teacher      = ApplicationMetaKeys::TYPE_TEACHER === get_post_meta( $application_id, ApplicationMetaKeys::APPLICANT_TYPE, true );
		$code         = (string) get_post_meta( $application_id, ApplicationMetaKeys::CENTRE_CODE, true );
		if ( ( $teacher ? ( ProcedureMetaKeys::AUDIENCE_TEACHERS !== get_post_meta( $procedure_id, ProcedureMetaKeys::AUDIENCE, true ) || ! self::can_apply( $user_id, $procedure_id ) ) : ! isset( CentreScope::codes_for( $user_id )[ $code ] ) )
			|| 'publish' !== get_post_status( $application_id )
			|| ProcedurePostType::POST_TYPE !== get_post_type( $procedure_id )
			|| 'publish' !== get_post_status( $procedure_id ) || self::is_archived( $procedure_id ) ) {
			return false;
		}
		return ApplicationMetaKeys::REVIEW_EXCLUDED !== get_post_meta( $application_id, ApplicationMetaKeys::REVIEW_STATE, true )
			&& ProcedureQuestions::delivery_is_open( $question, ProcedureState::today() );
	}

	/**
	 * Whether the user may add, change or remove people of this application now.
	 *
	 * Quién: el equipo directivo del centro de la solicitud —cualquier cuenta
	 * con su código, no solo la que la presentó—. Quien revisa no la escribe:
	 * la lista es del centro. Cuándo: lo dice
	 * {@see PeopleSpec::why_closed()}, con su ventana propia, aunque el plazo
	 * de solicitud esté cerrado. Pasa también por `map_meta_cap`
	 * ({@see CAP_EDIT_PEOPLE}).
	 *
	 * @param int $user_id        User ID.
	 * @param int $application_id Application ID.
	 * @return bool
	 */
	public static function can_edit_people( int $user_id, int $application_id ): bool {
		return '' === self::why_not_people( $user_id, $application_id );
	}

	/**
	 * Why the user cannot touch the people of this application now, in words.
	 *
	 * @param int $user_id        User ID.
	 * @param int $application_id Application ID.
	 * @return string Empty when they can.
	 */
	public static function why_not_people( int $user_id, int $application_id ): string {
		$ajena = 'Solo gestiona las personas de una solicitud el equipo directivo de su centro.';
		if ( ! self::can_view_application( $user_id, $application_id ) || ! user_can( $user_id, self::CAP_APPLY ) || 'publish' !== get_post_status( $application_id ) ) {
			return $ajena;
		}
		$docente = ApplicationMetaKeys::TYPE_TEACHER === get_post_meta( $application_id, ApplicationMetaKeys::APPLICANT_TYPE, true );
		if ( ! $docente && ! isset( CentreScope::codes_for( $user_id )[ (string) get_post_meta( $application_id, ApplicationMetaKeys::CENTRE_CODE, true ) ] ) ) {
			return $ajena;
		}
		$procedure_id = (int) wp_get_post_parent_id( $application_id );
		if ( ProcedurePostType::POST_TYPE !== get_post_type( $procedure_id ) || 'publish' !== get_post_status( $procedure_id ) ) {
			return 'Ese procedimiento no existe o no está publicado.';
		}
		return PeopleSpec::why_closed(
			get_post_meta( $procedure_id, ProcedureMetaKeys::PEOPLE_SPEC, true ),
			array(
				'today'    => ProcedureState::today(),
				'archived' => self::is_archived( $procedure_id ),
				'teacher'  => $docente || ProcedureMetaKeys::AUDIENCE_TEACHERS === get_post_meta( $procedure_id, ProcedureMetaKeys::AUDIENCE, true ),
				'review'   => (string) get_post_meta( $application_id, ApplicationMetaKeys::REVIEW_STATE, true ),
				'state'    => ProcedureState::of_post( $procedure_id ),
			)
		);
	}

	/**
	 * Why the user cannot touch this procedure, or that group of it, in words.
	 *
	 * Vive aquí y no en cada pantalla para que todas lo cuenten igual.
	 *
	 * @param int    $user_id User ID.
	 * @param int    $post_id Procedure ID.
	 * @param string $group   Group being written; empty for the procedure as a whole.
	 * @return string Empty when there is nothing to explain.
	 */
	public static function why_not_editable( int $user_id, int $post_id, string $group = '' ): string {
		if ( self::can_edit( $user_id, $post_id ) ) {
			// Puede editarlo, así que lo único que puede faltar es que el
			// estado de hoy cierre ese grupo.
			return '' === $group || self::can_edit_group( $user_id, $post_id, $group )
				? ''
				: ProcedureState::why_locked( ProcedureState::of_post( $post_id ) );
		}
		if ( self::can_open( $user_id, $post_id ) ) {
			return 'Este procedimiento está marcado como histórico: se puede consultar y exportar, pero ya no se edita. Para volver a abrirlo, pídalo a quien administre el aplicativo.';
		}
		if ( ! user_can( $user_id, self::CAP_MANAGE_PROCEDURES ) ) {
			return 'Su perfil no gestiona procedimientos. Si debería hacerlo, pídalo a quien administre el aplicativo.';
		}
		if ( array() === self::user_areas( $user_id ) ) {
			return self::scope_assignment_message( $user_id );
		}
		return 'Este procedimiento es de otro ámbito. Solo lo edita el ámbito que lo convoca o quien administra el aplicativo.';
	}

	// ─── la consulta por zona (ADR-0043) ────────────────────────────────────

	/**
	 * The zones a person observes, as written in the profile.
	 *
	 * @param int $user_id User ID.
	 * @return string[] Zone codes.
	 */
	public static function observer_zones( int $user_id ): array {
		return ObserverScope::zones_for( $user_id );
	}

	/**
	 * The codes of the centres of the zones a person observes, from today's catalogue.
	 *
	 * Se lee del catálogo al preguntar: un centro que cambia de zona deja de
	 * verse desde la antigua sin que nadie toque nada.
	 *
	 * @param int $user_id User ID.
	 * @return string[]
	 */
	public static function observer_codes( int $user_id ): array {
		$zonas = self::observer_zones( $user_id );
		return array() === $zonas ? array() : ObserverScope::codes_in( $zonas, CentreCatalog::index() );
	}

	/**
	 * Whether the user may open the consultation of this procedure, and what of it.
	 *
	 * Quien gestiona su ámbito —o todos— la ve entera, igual que ve el taller;
	 * quien consulta, solo los centros de sus zonas. Un procedimiento que no
	 * admite consulta no lo ve nadie por aquí. Sin procedimiento, si tiene
	 * alguna consulta que abrir: gestiona procedimientos, o consulta una zona
	 * con centros en el catálogo.
	 *
	 * @param int        $user_id      User ID.
	 * @param int        $procedure_id Procedure ID; 0 for «any».
	 * @param array|null $scope        Set to what they see: empty for every application, `codes` for only those centres; null when they may not.
	 * @return bool
	 */
	public static function can_observe( int $user_id, int $procedure_id = 0, ?array &$scope = null ): bool {
		$scope = 0 === $procedure_id ? null : self::observe_scope( $user_id, $procedure_id );
		if ( 0 !== $procedure_id ) {
			return null !== $scope;
		}
		return self::can_manage_procedures( $user_id )
			|| ( $user_id > 0 && user_can( $user_id, self::CAP_VIEW_APPLICATIONS ) && array() !== self::observer_codes( $user_id ) );
	}

	/**
	 * Whether the user may read this application through the consultation.
	 *
	 * Solo leer: `map_meta_cap()` lo pregunta para `read_post` y nunca para
	 * editar, borrar ni publicar. Una solicitud de profesorado no tiene
	 * centro, y un centro que no está en el catálogo no es de ninguna zona:
	 * por zona, ninguna de las dos se ve.
	 *
	 * @param int $user_id        User ID.
	 * @param int $application_id Application ID.
	 * @return bool
	 */
	public static function can_observe_application( int $user_id, int $application_id ): bool {
		if ( ApplicationPostType::POST_TYPE !== get_post_type( $application_id ) || 'publish' !== get_post_status( $application_id ) ) {
			return false;
		}
		$ambito = self::observe_scope( $user_id, (int) wp_get_post_parent_id( $application_id ) );
		if ( null === $ambito ) {
			return false;
		}
		if ( ! isset( $ambito['codes'] ) ) {
			return true;
		}
		$codigo = (string) get_post_meta( $application_id, ApplicationMetaKeys::CENTRE_CODE, true );
		return '' !== $codigo && in_array( $codigo, $ambito['codes'], true );
	}

	/**
	 * What the user sees of the consultation of one procedure.
	 *
	 * @param int $user_id      User ID.
	 * @param int $procedure_id Procedure ID.
	 * @return array<string, string[]>|null Null when they may not; empty for every application; `codes` for only those centres.
	 */
	private static function observe_scope( int $user_id, int $procedure_id ): ?array {
		if ( $user_id <= 0 || ! ObserverScope::is_observable( $procedure_id ) ) {
			return null;
		}
		if ( self::can_open( $user_id, $procedure_id ) ) {
			return array();
		}
		$codigos = user_can( $user_id, self::CAP_VIEW_APPLICATIONS ) ? self::observer_codes( $user_id ) : array();
		return array() === $codigos ? null : array( 'codes' => $codigos );
	}

	/**
	 * Turn the policy into a denial for the core meta caps this guard owns.
	 *
	 * Esta es la capa que de verdad protege: el acotado del listado solo
	 * esconde, y deja abiertos el enlace directo, la edición rápida y la REST.
	 * Son las mismas capacidades que mira el núcleo por cualquiera de esas
	 * puertas, así que la regla se escribe una vez y vale para todas.
	 *
	 * @param string[] $caps    Primitive caps.
	 * @param string   $cap     Meta cap.
	 * @param int      $user_id User ID.
	 * @param array    $args    Cap args (post or term ID in the first position).
	 * @return string[]
	 */
	public static function map_meta_cap( array $caps, string $cap, int $user_id, array $args ): array {
		if ( 'assign_term' === $cap ) {
			return self::may_use_area( $user_id, isset( $args[0] ) ? (int) $args[0] : 0 )
				? $caps
				: array( 'do_not_allow' );
		}
		if ( self::CAP_EDIT_PEOPLE === $cap ) {
			return self::can_edit_people( $user_id, isset( $args[0] ) ? (int) $args[0] : 0 ) ? array( 'exist' ) : array( 'do_not_allow' );
		}
		if ( ! in_array( $cap, array( 'edit_post', 'delete_post', 'publish_post', 'read_post' ), true ) ) {
			return $caps;
		}
		$post_id = isset( $args[0] ) ? (int) $args[0] : 0;
		$tipo    = $post_id > 0 ? (string) get_post_type( $post_id ) : '';
		if ( ! in_array( $tipo, self::scoped_types(), true ) ) {
			return $caps;
		}

		if ( ApplicationPostType::POST_TYPE === $tipo ) {
			$permitido = 'read_post' === $cap
				? self::can_view_application( $user_id, $post_id )
				: self::can_review( $user_id, $post_id );
		} else {
			$permitido = 'read_post' === $cap
				? self::can_read( $user_id, $post_id )
				: self::can_edit( $user_id, $post_id );
		}

		return $permitido ? $caps : array( 'do_not_allow' );
	}

	/**
	 * Whether the user may read this procedure.
	 *
	 * Un procedimiento publicado lo lee cualquiera: su ficha es pública y esa
	 * es la puerta de entrada del aplicativo. Lo que todavía no está en un
	 * estado público —borrador, privado, programado— es del ámbito que lo
	 * convoca y de nadie más, así que ahí vuelve a mandar la regla del ámbito.
	 *
	 * Sin esta distinción, la única barrera que queda delante de un
	 * procedimiento privado es `read_private_prc_procedures`, que tiene todo
	 * el rol de gestión: cualquier ámbito leería el de cualquier otro.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Procedure ID.
	 * @return bool
	 */
	private static function can_read( int $user_id, int $post_id ): bool {
		$estado = get_post_status_object( (string) get_post_status( $post_id ) );
		if ( null !== $estado && ! empty( $estado->public ) ) {
			return true;
		}
		return self::is_manager( $user_id ) || self::can_open( $user_id, $post_id );
	}

	/**
	 * Whether the user may file a procedure under that ámbito.
	 *
	 * Marcar un ámbito es dar acceso (ADR-0025): quien esté en cualquiera de
	 * los del procedimiento lo edita entero y ve todas sus solicitudes. Por
	 * eso el término que se pone tiene que ser de los propios.
	 *
	 * Vive en el guardián y no en la pantalla porque `assign_term` es la
	 * capacidad que el núcleo comprueba término a término cuando la
	 * asignación entra por la REST o por el editor de bloques, donde la
	 * pantalla del taller no pinta nada.
	 *
	 * @param int $user_id User ID.
	 * @param int $term_id Term ID; only prc_area terms are scoped.
	 * @return bool
	 */
	private static function may_use_area( int $user_id, int $term_id ): bool {
		$term = $term_id > 0 ? get_term( $term_id ) : null;
		if ( $term instanceof \WP_Term && ProcedureTaxonomies::AREA !== $term->taxonomy ) {
			// El curso y cualquier otra taxonomía no se acotan por ámbito.
			return true;
		}
		if ( self::can_edit_all_areas( $user_id ) ) {
			return true;
		}
		// Un término que no existe cae aquí y no pasa: falla en cerrado.
		return in_array( $term_id, self::scope_areas( $user_id ), true );
	}

	/**
	 * The ámbito rule alone: every area, or a shared one, or brand new and mine.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Procedure ID, or an application under it.
	 * @return bool
	 */
	private static function in_scope( int $user_id, int $post_id ): bool {
		if ( $post_id <= 0 || ! in_array( (string) get_post_type( $post_id ), self::scoped_types(), true ) ) {
			return false;
		}
		if ( self::can_edit_all_areas( $user_id ) ) {
			return true;
		}

		$mine = self::scope_areas( $user_id );
		if ( array() === $mine ) {
			return false;
		}

		$theirs = self::post_areas( $post_id );
		if ( array() === $theirs ) {
			// Recién creado y todavía sin ámbito: lo edita quien lo creó, que
			// es quien tiene que ponérselo.
			return 'auto-draft' === get_post_status( self::root_id( $post_id ) )
				&& (int) get_post_field( 'post_author', self::root_id( $post_id ) ) === $user_id;
		}

		return array() !== array_intersect( $mine, $theirs );
	}

	/**
	 * Keep positive integers only, without repeats.
	 *
	 * @param array<int, mixed> $values Raw values.
	 * @return int[]
	 */
	private static function clean_ids( array $values ): array {
		$out = array();
		foreach ( $values as $value ) {
			$id = (int) $value;
			if ( $id > 0 ) {
				$out[] = $id;
			}
		}
		return array_values( array_unique( $out ) );
	}
}
