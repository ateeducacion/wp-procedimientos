<?php
/**
 * Send a draft procedure to the bin from its workshop (adenda a la ADR-0007).
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\Access\ProcedureAccess;
use Prc\Access\ProcedureDeletion;

/**
 * La operación `delete` del taller: un borrador sin solicitudes, a la papelera.
 *
 * Quién y cuándo lo decide {@see ProcedureDeletion}; aquí se comprueba la
 * palabra escrita, se envía a la papelera y se vuelve a «Gestión». Nunca se
 * borra para siempre: es la papelera —también donde el sitio la tiene
 * desactivada, {@see ApplicationOperations::send_to_trash()}—, y lo recupera
 * quien administra el aplicativo.
 */
final class ProcedureDelete {

	/**
	 * Notice code in the URL of «Gestión» after deleting.
	 */
	public const NOTICE = 'borrado';

	/**
	 * Query arg with the procedure just deleted, to name it in the notice.
	 */
	public const ARG_DELETED = 'borrado';

	/**
	 * The word that confirms it, typed in the confirmation box.
	 */
	public const WORD = 'BORRAR';

	/**
	 * Trash the procedure and go back to «Gestión».
	 *
	 * La regla se vuelve a mirar aquí y no solo al pintar: entre pintar la
	 * confirmación y enviarla pueden haber llegado una solicitud o la
	 * publicación. Si otra persona tiene el taller abierto, se para antes
	 * con el 409 del bloqueo de edición.
	 *
	 * @param int    $procedure_id Procedure ID.
	 * @param int    $user_id      Who deletes.
	 * @param string $typed        What was typed in the confirmation box.
	 * @return string Why it was refused; on success it leaves and returns nothing.
	 */
	public static function handle( int $procedure_id, int $user_id, string $typed ): string {
		// No se le quita el procedimiento a quien lo tiene abierto: 409, como al guardar.
		EditLock::require_available( $procedure_id );
		$reason = ProcedureDeletion::why_not_delete_procedure( $user_id, $procedure_id );
		if ( '' !== $reason ) {
			return $reason;
		}
		if ( ! self::confirmed( $typed ) ) {
			return 'Para borrarlo, escriba ' . self::WORD . ' en el campo de confirmación. No se ha borrado nada.';
		}
		// Con la papelera del sitio desactivada, wp_trash_post() borraría para siempre.
		ApplicationOperations::send_to_trash( $procedure_id );
		if ( 'trash' !== get_post_status( $procedure_id ) ) {
			return 'No se ha podido enviar el procedimiento a la papelera.';
		}
		// Quien borra deja el taller: su bloqueo se suelta ya, y solo si es suyo.
		EditLock::release( $procedure_id );
		Shell::leave(
			Shell::url(
				'workspace',
				array(
					Workspace::VAR_NOTICE => self::NOTICE,
					self::ARG_DELETED     => $procedure_id,
				)
			)
		);
		return '';
	}

	/**
	 * Whether what was typed is the word, without minding case or spaces around it.
	 *
	 * @param string $typed What was typed.
	 * @return bool
	 */
	public static function confirmed( string $typed ): bool {
		return self::WORD === strtoupper( trim( $typed ) );
	}

	/**
	 * What «Gestión» says after deleting.
	 *
	 * El procedimiento se nombra solo si está de verdad en la papelera y quien
	 * mira todavía puede abrirlo: el enlace se puede escribir a mano.
	 *
	 * @param array<string, mixed> $request The query args of the request.
	 * @return string
	 */
	public static function notice( array $request ): string {
		$id = absint( $request[ self::ARG_DELETED ] ?? 0 );
		if ( $id > 0 && 'trash' === get_post_status( $id ) && ProcedureAccess::can_open( get_current_user_id(), $id ) ) {
			return 'Se ha enviado «' . get_post_field( 'post_title', $id, 'raw' ) . '» a la papelera.';
		}
		return 'Se ha enviado el procedimiento a la papelera.';
	}
}
