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
			$motivo = ProcedureAccess::why_not_editable( $user_id, $post_id );
			return '' !== $motivo ? $motivo : 'Su perfil no puede borrar este procedimiento.';
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
	 * Whether any application hangs from the procedure, in any status, the bin included.
	 *
	 * @param int $post_id Procedure ID.
	 * @return bool
	 */
	public static function has_applications( int $post_id ): bool {
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
		return is_array( $ids ) && array() !== $ids;
	}
}
