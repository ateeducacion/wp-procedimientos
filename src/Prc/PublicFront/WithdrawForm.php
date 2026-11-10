<?php
/**
 * Withdrawing one's own application, from the application screen.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\Access\ApplicationActions;
use Prc\Meta\ApplicationMetaKeys;

/**
 * «Quiero darme de baja de esta convocatoria» (PLAN-0003, PR 9; ADR-0044).
 *
 * La operación `withdraw` de la pantalla de la solicitud: quien puede
 * editarla la retira mientras el plazo de solicitud siga abierto, o mientras
 * dure su prórroga. Retirar es enviarla a la papelera (ADR-0007), con
 * confirmación; mientras haya plazo, el centro puede presentar otra.
 *
 * Va aparte de {@see ApplyForm}, que ya pasaba del umbral de complejidad de
 * PHPMD. Quién y cuándo lo decide {@see ApplicationActions::why_not_withdraw()},
 * que es también lo que responde la meta capacidad `prc_withdraw_application`.
 */
final class WithdrawForm {

	/** The field that says this POST is ours, with {@see OP_WITHDRAW}. */
	public const FIELD_OP = 'prc_withdraw_op';

	public const OP_WITHDRAW = 'withdraw';

	/** The application to withdraw. */
	public const FIELD_APPLICATION = 'prc_withdraw_application';

	/** Field carrying the nonce, whose action is {@see nonce_action()}. */
	public const NONCE_FIELD = '_prc_withdraw_nonce';

	/** Anchor of the section on the application screen. */
	public const ANCHOR = 'prc-retirar';

	/** Notice code of ApplyForm once withdrawn. */
	public const NOTICE = 'retirada';

	/**
	 * Why the last withdrawal did not go through.
	 *
	 * @var string
	 */
	private static $rejected = '';

	/**
	 * Register the POST handler.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'handle' ), 20 );
	}

	/**
	 * Nonce action of the withdrawal of one application.
	 *
	 * @param int $application_id Application ID.
	 * @return string
	 */
	public static function nonce_action( int $application_id ): string {
		return 'prc_withdraw_' . $application_id;
	}

	/**
	 * Take a withdrawal, when it is ours.
	 *
	 * @return void
	 */
	public static function handle(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- se comprueba justo debajo, con la solicitud.
		$op    = isset( $_POST[ self::FIELD_OP ] ) && is_string( $_POST[ self::FIELD_OP ] ) ? sanitize_key( wp_unslash( $_POST[ self::FIELD_OP ] ) ) : '';
		$id    = isset( $_POST[ self::FIELD_APPLICATION ] ) && is_string( $_POST[ self::FIELD_APPLICATION ] ) ? absint( wp_unslash( $_POST[ self::FIELD_APPLICATION ] ) ) : 0;
		$nonce = isset( $_POST[ self::NONCE_FIELD ] ) && is_string( $_POST[ self::NONCE_FIELD ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		if ( self::OP_WITHDRAW !== $op || ! is_user_logged_in() ) {
			return;
		}
		if ( false === wp_verify_nonce( $nonce, self::nonce_action( $id ) ) ) {
			self::$rejected = 'El formulario estuvo abierto demasiado tiempo y el envío caducó. Vuelva a intentarlo.';
			return;
		}
		$user_id = get_current_user_id();
		// La capa que protege: la misma regla que pinta el botón, otra vez.
		if ( ! current_user_can( ApplicationActions::CAP_WITHDRAW, $id ) ) {
			self::$rejected = ApplicationActions::why_not_withdraw( $user_id, $id );
			return;
		}
		$procedure_id = (int) wp_get_post_parent_id( $id );
		$codigo       = (string) get_post_meta( $id, ApplicationMetaKeys::CENTRE_CODE, true );
		if ( ! ApplicationOperations::withdraw( $id, $user_id, ApplicationOperations::BY_APPLICANT ) ) {
			self::$rejected = 'No se ha podido retirar la solicitud. Vuelva a intentarlo.';
			return;
		}
		Shell::leave( ApplyForm::url_for_code( $procedure_id, $codigo, array( ApplyForm::ARG_NOTICE => self::NOTICE ) ) );
	}

	/**
	 * What the application screen paints about withdrawing and the extension.
	 *
	 * @param array<string, mixed> $m What ApplyForm::model() returned.
	 * @return array{show:bool, can:bool, why:string, late:string, rejected:string, application:int, action:string, nonce_action:string}
	 */
	public static function model( array $m ): array {
		$id = (int) ( $m['application']['id'] ?? 0 );
		if ( $id <= 0 ) {
			return array(
				'show'         => false,
				'can'          => false,
				'why'          => '',
				'late'         => '',
				'rejected'     => self::$rejected,
				'application'  => 0,
				'action'       => '',
				'nonce_action' => '',
			);
		}
		$user_id = get_current_user_id();
		$porque  = ApplicationActions::why_not_withdraw( $user_id, $id );
		return array(
			'show'         => true,
			'can'          => '' === $porque,
			'why'          => $porque,
			'late'         => ApplicationActions::late_notice( $id ),
			'rejected'     => self::$rejected,
			'application'  => $id,
			'action'       => ApplyForm::url_for_code( (int) ( $m['procedure_id'] ?? 0 ), (string) ( $m['centre']['code'] ?? '' ) ),
			'nonce_action' => self::nonce_action( $id ),
		);
	}
}
