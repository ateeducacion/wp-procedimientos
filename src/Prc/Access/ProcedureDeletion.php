<?php
/**
 * Who may send a procedure to the bin, and when.
 *
 * @package Prc
 */

namespace Prc\Access;

use Prc\PostType\ApplicationPostType;
use Prc\PostType\ProcedurePostType;

/**
 * La regla de borrar un procedimiento (adenda de 2026-11-11 a la ADR-0007).
 *
 * Va aparte de {@see ProcedureAccess}, que estaba en el tope de métodos
 * públicos de PHPMD, pero **la decisión sigue enganchada allí**:
 * `ProcedureAccess::map_meta_cap()` resuelve `delete_post` sobre
 * `prc_procedure` preguntando aquí, así que vale igual para el taller, para
 * un POST armado a mano, para el escritorio y para la REST. Aquí no se
 * escribe nada: lo que envía a la papelera es
 * {@see \Prc\PublicFront\ProcedureDelete}.
 *
 * Borrar es enviar a la papelera, y solo se borra lo que no debió existir: un
 * borrador —ni publicado, ni programado, ni histórico— sin ninguna solicitud,
 * tampoco retirada. Un procedimiento publicado que volvió a borrador puede
 * tener solicitudes retiradas en la papelera, y esas siguen siendo de un
 * centro.
 */
final class ProcedureDeletion {

	/**
	 * Post statuses a procedure can be deleted from: the drafts.
	 *
	 * @var string[]
	 */
	private const DRAFT_STATUSES = array( 'draft', 'pending', 'auto-draft' );

	/**
	 * Whether the user may send this procedure to the bin from the application.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Procedure ID.
	 * @return bool
	 */
	public static function can_delete_procedure( int $user_id, int $post_id ): bool {
		return '' === self::why_not_delete_procedure( $user_id, $post_id );
	}

	/**
	 * Why the user may not send this procedure to the bin, in words.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Procedure ID.
	 * @return string Empty when they may.
	 */
	public static function why_not_delete_procedure( int $user_id, int $post_id ): string {
		if ( ProcedurePostType::POST_TYPE !== get_post_type( $post_id ) ) {
			return 'Ese procedimiento ya no existe.';
		}
		if ( 'trash' === get_post_status( $post_id ) ) {
			return 'Este procedimiento ya está en la papelera.';
		}
		if ( ProcedureAccess::is_archived( $post_id ) ) {
			return 'Este procedimiento está marcado como histórico y no se borra: se sigue consultando y exportando.';
		}
		if ( ! ProcedureAccess::can_edit( $user_id, $post_id ) ) {
			$reason = ProcedureAccess::why_not_editable( $user_id, $post_id );
			return '' !== $reason ? $reason : 'Su perfil no puede borrar este procedimiento.';
		}
		if ( ! in_array( (string) get_post_status( $post_id ), self::DRAFT_STATUSES, true ) ) {
			return 'Solo se borra un procedimiento en borrador. Si este ya no hace falta, devuélvalo antes a borrador desde «Publicación».';
		}
		if ( self::has_applications( $post_id ) ) {
			return 'Este procedimiento tiene solicitudes —también cuentan las retiradas— y no se borra: lo que un centro presentó no se pierde así.';
		}
		return '';
	}

	/**
	 * What `delete_post` answers on a procedure.
	 *
	 * Para gestión, la regla de arriba: ni el escritorio ni la REST envían a
	 * la papelera uno publicado, histórico o con solicitudes, ni lo sacan de
	 * ella, ni lo borran para siempre. Administración conserva lo que ya
	 * podía: lo mismo que editar (ADR-0023).
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Procedure ID.
	 * @return bool
	 */
	public static function allows( int $user_id, int $post_id ): bool {
		if ( ProcedureAccess::is_manager( $user_id ) ) {
			return ProcedureAccess::can_edit( $user_id, $post_id );
		}
		return self::can_delete_procedure( $user_id, $post_id );
	}

	/**
	 * The status a procedure write may set: `trash` only for who may delete it.
	 *
	 * Lo llama `ProcedureAccess::guard_parent()`, en `wp_insert_post_data`.
	 * Poner el estado `trash` es borrar, pero `post.php` con
	 * `post_status=trash`, la edición rápida y la edición en bloque solo
	 * preguntan por `edit_post`, y `wp_insert_post()` lo escribe sin más. Aquí
	 * se pregunta por `delete_post` ({@see allows()}): sin
	 * él, se queda el estado que tenía. `wp_trash_post()` pasa por aquí y
	 * sigue funcionando para quien sí puede borrarlo.
	 *
	 * @param int    $post_id Procedure ID.
	 * @param string $status  Status the write asks for.
	 * @return string
	 */
	public static function trash_or_keep( int $post_id, string $status ): string {
		$current = (string) get_post_status( $post_id );
		if ( 'trash' !== $status || 'trash' === $current || current_user_can( 'delete_post', $post_id ) ) {
			return $status;
		}
		return $current;
	}

	/**
	 * Stop `wp_trash_post()` on a procedure the user may not delete, before it writes anything.
	 *
	 * Filtro `pre_trash_post`. {@see trash_or_keep()} para el estado en
	 * `wp_insert_post_data`, pero `wp_trash_post()` ya ha escrito para entonces
	 * sus metas de papelera y, aunque el estado se quede, dispara
	 * `trashed_post` y devuelve el contenido como si lo hubiera enviado. Aquí
	 * se para antes de todo. Sin nadie delante —cron, la provisión— no se mira,
	 * como en `ProcedureAccess::guard_parent()`.
	 *
	 * @param mixed    $check Null to go on; anything else stops the core.
	 * @param \WP_Post $post  Post about to be trashed.
	 * @return mixed
	 */
	public static function veto_trash( $check, $post ) {
		if ( null !== $check || ! $post instanceof \WP_Post || ProcedurePostType::POST_TYPE !== $post->post_type ) {
			return $check;
		}
		if ( get_current_user_id() <= 0 || current_user_can( 'delete_post', $post->ID ) ) {
			return $check;
		}
		return false;
	}

	/**
	 * Whether any application hangs from the procedure, in any status, the bin included.
	 *
	 * `delete_post` lo pregunta en cada fila del listado del escritorio y en
	 * cada pintado del taller: se recuerda por petición, y se vuelve a mirar
	 * en cuanto cambia cualquier contenido (`last_changed` de `posts`).
	 *
	 * @param int $post_id Procedure ID.
	 * @return bool
	 */
	public static function has_applications( int $post_id ): bool {
		$key    = $post_id . ':' . wp_cache_get_last_changed( 'posts' );
		$cached = wp_cache_get( $key, 'prc_has_applications' );
		if ( false !== $cached ) {
			return 'yes' === $cached;
		}
		$ids = get_posts(
			array(
				'post_type'        => ApplicationPostType::POST_TYPE,
				'post_parent'      => $post_id,
				// Todos los estados: `any` deja fuera la papelera.
				'post_status'      => array_values( get_post_stati() ),
				'numberposts'      => 1,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => true,
			)
		);
		$has = is_array( $ids ) && array() !== $ids;
		wp_cache_set( $key, $has ? 'yes' : 'no', 'prc_has_applications' );
		return $has;
	}
}
