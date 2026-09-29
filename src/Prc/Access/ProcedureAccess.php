<?php
/**
 * Capability checks for procedures and applications: who may do what, scoped
 * by ámbito and by school.
 *
 * @package Prc
 */

namespace Prc\Access;

use Prc\Domain\CentreCatalog;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ApplicationPostType;
use Prc\PostType\ProcedurePostType;
use Prc\Taxonomy\ProcedureTaxonomies;

/**
 * El único guardián del aplicativo: aquí se responde «¿quién puede qué?».
 *
 * Dos acotados y los dos fallan en cerrado (ADR-0014, ADR-0016):
 *
 * - **Por ámbito**: la meta de usuario `prc_area` lleva uno o varios términos
 *   de `prc_area`, y un término incluye a sus descendientes. Sin ámbito y sin
 *   `prc_manage_all_areas`, no se edita ni se ve nada en el taller.
 * - **Por centro**: {@see CentreScope::code_for()}. Sin código, `prc_apply`
 *   no sirve para nada.
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
	public const CAP_APPLY = 'prc_apply';

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
		} elseif ( ProcedurePostType::POST_TYPE === $tipo ) {
			$data['post_parent'] = 0;
		}
		return $data;
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
		if ( ! $request->has_param( ProcedureTaxonomies::AREA ) ) {
			if ( $post_id > 0 || self::can_edit_all_areas() ) {
				return $prepared;
			}
			$assigned = self::user_areas();
			if ( array() === $assigned ) {
				return new \WP_Error( 'prc_area_forbidden', self::scope_assignment_message( get_current_user_id() ), array( 'status' => 403 ) );
			}
			$requested = $assigned;
		} else {
			$requested = (array) $request->get_param( ProcedureTaxonomies::AREA );
		}
		$final = self::resolve_area_assignment( $post_id, $requested );
		if ( is_wp_error( $final ) ) {
			return $final;
		}
		if ( 0 === $post_id && ! self::can_edit_all_areas() && in_array( $request->get_param( 'status' ), array( 'publish', 'future', 'private' ), true ) ) {
			return new \WP_Error( 'prc_scope_draft_first', 'Cree el procedimiento como borrador antes de publicarlo, para que tenga ámbito desde el principio.', array( 'status' => 400 ) );
		}
		$request->set_param( ProcedureTaxonomies::AREA, $final );
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
	 * Read-only inventory of editorial accounts for deployment preparation.
	 *
	 * @return array<int, array{user_id:int,login:string,state:string,ids:int[],invalid:string[],legacy:bool}>
	 */
	public static function scope_diagnostics(): array {
		$rows = array();
		foreach ( get_users() as $user ) {
			if ( ! ( $user instanceof \WP_User ) || self::can_edit_all_areas( $user->ID ) ) {
				continue;
			}
			if ( ! user_can( $user, self::CAP_MANAGE_PROCEDURES ) && ! array_intersect( array( 'editor', 'prc_manager' ), $user->roles ) ) {
				continue;
			}
			$rows[] = array_merge(
				array(
					'user_id' => $user->ID,
					'login'   => $user->user_login,
				),
				self::scope_assignment_state( $user->ID )
			);
		}
		return $rows;
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
	 * procedimiento se comprueba además que esté publicado y que se dirija a
	 * centros de la titularidad de este, si el catálogo lo conoce; un centro
	 * que no está en el catálogo solicita igual, porque el catálogo puede ir
	 * por detrás de la realidad.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Procedure ID (0 = just the capability and the school).
	 * @return bool
	 */
	public static function can_apply( int $user_id, int $post_id = 0 ): bool {
		if ( $user_id <= 0 || ! user_can( $user_id, self::CAP_APPLY ) ) {
			return false;
		}
		$code = CentreScope::code_for( $user_id );
		if ( '' === $code ) {
			return false;
		}
		if ( $post_id <= 0 ) {
			return true;
		}
		if ( ProcedurePostType::POST_TYPE !== get_post_type( $post_id ) || 'publish' !== get_post_status( $post_id ) ) {
			return false;
		}
		$centro = CentreCatalog::find( $code );
		if ( array() === $centro ) {
			return true;
		}
		$ownership = get_post_meta( $post_id, ProcedureMetaKeys::OWNERSHIP, true );
		$ownership = is_array( $ownership ) ? $ownership : array();
		return array() === $ownership || in_array( $centro['ownership'], $ownership, true );
	}

	/**
	 * Whether the user may see this application: their school's, or one they review.
	 *
	 * @param int $user_id        User ID.
	 * @param int $application_id Application ID.
	 * @return bool
	 */
	public static function can_view_application( int $user_id, int $application_id ): bool {
		if ( $user_id <= 0 || ApplicationPostType::POST_TYPE !== get_post_type( $application_id ) ) {
			return false;
		}
		if ( self::can_review( $user_id, $application_id ) ) {
			return true;
		}
		if ( ! user_can( $user_id, self::CAP_APPLY ) ) {
			return false;
		}
		$code  = CentreScope::code_for( $user_id );
		$owner = (string) get_post_meta( $application_id, ApplicationMetaKeys::CENTRE_CODE, true );
		return '' !== $code && $code === $owner;
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
	public static function can_read( int $user_id, int $post_id ): bool {
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
	public static function may_use_area( int $user_id, int $term_id ): bool {
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
