<?php
/**
 * What whoever reviews does on one application from the «Solicitudes»
 * panel: withdraw it by mistake, restore it, extend it, submit one on behalf
 * of a school, the internal note and the history.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\Access\ApplicationActions;
use Prc\Domain\CentreCatalog;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;

/**
 * Las actuaciones de quien revisa sobre una solicitud (PLAN-0003, PR 9;
 * ADR-0044), en el panel «Solicitudes» del taller.
 *
 * Va aparte de {@see ProcedureEditor} y de {@see ApplicationsPanel}, que ya
 * estaban en el umbral de PHPMD. {@see ProcedureEditor::handle()} comprueba el
 * nonce y llama aquí; aquí se pregunta a la meta capacidad de cada operación
 * —la capa que protege— y a {@see ApplicationActions} por el motivo.
 *
 * - **Retirar por error** (`retire_application`): a la papelera, con motivo
 *   obligatorio que queda en el historial. No hay borrado definitivo desde el
 *   aplicativo, y la revisión en bloque no retira.
 * - **Restaurar** (`restore_application`), desde «Retiradas», si el centro no
 *   ha presentado otra entretanto.
 * - **Prórroga** (`extend_application`): hasta qué día puede el centro
 *   editarla entera, o quitarla.
 * - **Presentar en nombre de un centro** (`submit_for_centre`): una solicitud
 *   vacía, con prórroga obligatoria.
 * - **Nota interna**, que viaja con la revisión de siempre.
 */
final class ApplicationActionsPanel {

	public const OP_RETIRE     = 'retire_application';
	public const OP_RESTORE    = 'restore_application';
	public const OP_EXTEND     = 'extend_application';
	public const OP_SUBMIT_FOR = 'submit_for_centre';

	/** Every operation this panel takes. */
	public const OPS = array( self::OP_RETIRE, self::OP_RESTORE, self::OP_EXTEND, self::OP_SUBMIT_FOR );

	/** The operations on one application, whose nonce is per application. */
	public const ROW_OPS = array( self::OP_RETIRE, self::OP_RESTORE, self::OP_EXTEND );

	/** Why whoever reviews withdraws an application: required. */
	public const FIELD_REASON = 'prc_retire_reason';

	/** Last day of the extension, Y-m-d. */
	public const FIELD_LATE = 'prc_late_until';

	/** Take the extension away: `1`. */
	public const FIELD_LATE_REMOVE = 'prc_late_remove';

	/** Code of the school to submit for. */
	public const FIELD_CODE = 'prc_submit_code';

	/** «Aunque no cumpla los requisitos»: `1`. */
	public const FIELD_OVERRIDE = 'prc_submit_override';

	/** The internal note, in the review of each application. */
	public const FIELD_INTERNAL_NOTE = 'prc_internal_note';

	/** GET: the list of withdrawn applications, `1`. */
	public const ARG_WITHDRAWN = 'retiradas';

	/** GET: search the catalogue by code or name. */
	public const ARG_FIND = 'centro_buscar';

	/** GET: the code picked from the search. */
	public const ARG_PICK = 'centro_elegido';

	/** Notice codes of the workshop, and what they say. */
	public const NOTICES = array(
		'retirada'         => 'Solicitud retirada: está en «Retiradas» y se puede restaurar desde allí mientras el centro no presente otra.',
		'restaurada'       => 'Solicitud restaurada: vuelve a la tabla como estaba.',
		'prorroga'         => 'Prórroga guardada: el centro puede editar esa solicitud entera hasta ese día, incluido.',
		'prorroga-quitada' => 'Prórroga quitada: la solicitud vuelve a seguir el plazo general.',
		'en-nombre'        => 'Solicitud presentada en nombre del centro, vacía y en «Presentada». El centro la ve en «Solicitudes de mi centro» y la completa con su formulario hasta el último día de la prórroga. El aplicativo no le avisa por correo: el catálogo no trae su dirección.',
	);

	/**
	 * Who withdrew an application, as «Retiradas» says it.
	 */
	private const ORIGINS = array(
		ApplicationOperations::BY_APPLICANT => 'Quien la presentó',
		ApplicationOperations::BY_REVIEWER  => 'Quien revisa',
		ApplicationOperations::BY_DESK      => 'Desde el escritorio',
	);

	/**
	 * What a refused submit sent, to paint it again.
	 *
	 * @var array<string, string>
	 */
	private static $posted = array();

	// ─── las operaciones ───────────────────────────────────────────────────

	/**
	 * Do one operation of this panel; the nonce was already checked.
	 *
	 * @param string $op           One of {@see OPS}.
	 * @param int    $procedure_id Procedure ID.
	 * @param int    $user_id      Who acts.
	 * @param int    $row_id       Application ID, for {@see ROW_OPS}.
	 * @return string Why it was refused; it leaves on success.
	 */
	public static function handle( string $op, int $procedure_id, int $user_id, int $row_id ): string {
		if ( self::OP_SUBMIT_FOR === $op ) {
			return self::submit_for( $procedure_id, $user_id );
		}
		if ( (int) wp_get_post_parent_id( $row_id ) !== $procedure_id ) {
			return 'Esa solicitud no es de este procedimiento.';
		}
		$ops = array(
			self::OP_RETIRE  => 'retire',
			self::OP_RESTORE => 'restore',
			self::OP_EXTEND  => 'extend',
		);
		return isset( $ops[ $op ] ) ? (string) call_user_func( array( self::class, $ops[ $op ] ), $procedure_id, $user_id, $row_id ) : '';
	}

	/**
	 * Withdraw an application from the review, with a reason.
	 *
	 * @param int $procedure_id Procedure ID.
	 * @param int $user_id      Who withdraws it.
	 * @param int $row_id       Application ID.
	 * @return string Why not.
	 */
	private static function retire( int $procedure_id, int $user_id, int $row_id ): string {
		$porque = ApplicationActions::why_not_retire( $user_id, $row_id );
		if ( '' !== $porque || ! current_user_can( 'delete_post', $row_id ) ) {
			return '' !== $porque ? $porque : 'Su perfil no puede retirar esta solicitud.';
		}
		$motivo = self::textarea( self::FIELD_REASON );
		if ( '' === $motivo ) {
			return 'Para retirar una solicitud hay que escribir el motivo: queda en su historial.';
		}
		if ( ! ApplicationOperations::withdraw( $row_id, $user_id, ApplicationOperations::BY_REVIEWER, $motivo ) ) {
			return 'No se ha podido retirar la solicitud. Vuelva a intentarlo.';
		}
		self::back( $procedure_id, 'retirada' );
		return '';
	}

	/**
	 * Restore a withdrawn application.
	 *
	 * @param int $procedure_id Procedure ID.
	 * @param int $user_id      Who restores it.
	 * @param int $row_id       Application ID.
	 * @return string Why not.
	 */
	private static function restore( int $procedure_id, int $user_id, int $row_id ): string {
		if ( ! current_user_can( ApplicationActions::CAP_RESTORE, $row_id ) ) {
			return ApplicationActions::why_not_restore( $user_id, $row_id );
		}
		if ( ! ApplicationOperations::restore( $row_id ) ) {
			$porque = ApplicationActions::why_not_restore( $user_id, $row_id );
			return '' !== $porque ? $porque : 'No se ha podido restaurar la solicitud. Vuelva a intentarlo.';
		}
		self::back( $procedure_id, 'restaurada' );
		return '';
	}

	/**
	 * Give, change or take away the extension of an application.
	 *
	 * @param int $procedure_id Procedure ID.
	 * @param int $user_id      Who does it.
	 * @param int $row_id       Application ID.
	 * @return string Why not.
	 */
	private static function extend( int $procedure_id, int $user_id, int $row_id ): string {
		if ( ! current_user_can( ApplicationActions::CAP_EXTEND, $row_id ) ) {
			return ApplicationActions::why_not_extend( $user_id, $row_id );
		}
		$quitar = '1' === self::field( self::FIELD_LATE_REMOVE );
		$hasta  = $quitar ? '' : self::field( self::FIELD_LATE );
		$porque = $quitar ? '' : ApplicationActions::why_not_late_date( $hasta, ProcedureState::today() );
		if ( '' !== $porque ) {
			return $porque;
		}
		ApplicationOperations::extend( $row_id, $user_id, $hasta );
		self::back( $procedure_id, $quitar ? 'prorroga-quitada' : 'prorroga' );
		return '';
	}

	/**
	 * Submit an empty application on behalf of a school.
	 *
	 * @param int $procedure_id Procedure ID.
	 * @param int $user_id      Who submits it.
	 * @return string Why not.
	 */
	private static function submit_for( int $procedure_id, int $user_id ): string {
		self::$posted = array(
			'code'     => self::field( self::FIELD_CODE ),
			'late'     => self::field( self::FIELD_LATE ),
			'override' => self::field( self::FIELD_OVERRIDE ),
		);
		if ( ! current_user_can( ApplicationActions::CAP_SUBMIT_FOR, $procedure_id ) ) {
			return ApplicationActions::why_not_submit_for( $user_id, $procedure_id );
		}
		if ( '' === self::$posted['code'] ) {
			return 'Escriba el código del centro en cuyo nombre presenta la solicitud.';
		}
		$fuera  = '';
		$porque = ApplicationActions::why_not_submit_for( $user_id, $procedure_id, self::$posted['code'], '1' === self::$posted['override'], $fuera );
		if ( '' === $porque ) {
			$porque = ApplicationActions::why_not_late_date( self::$posted['late'], ProcedureState::today() );
		}
		if ( '' !== $porque ) {
			return $porque;
		}
		if ( ApplicationOperations::submit_for( $procedure_id, $user_id, self::$posted['code'], self::$posted['late'], $fuera ) <= 0 ) {
			return ApplicationOperations::why_not_saved( 'No se ha podido crear la solicitud. Vuelva a intentarlo.' );
		}
		$codigo = (string) ( ApplicationActions::catalogue_centre( self::$posted['code'] )['code'] ?? '' );
		Shell::leave( ApplicationsPanel::url( $procedure_id, ApplicationsPanel::filter( array() ), array( ProcedureEditor::ARG_NOTICE => 'en-nombre' ), array( 'search' => $codigo ) ) );
		return '';
	}

	/**
	 * Store the internal note sent with the review of one application.
	 *
	 * Solo si el formulario la trae: la revisión en bloque no la lleva y no
	 * la toca.
	 *
	 * @param int $row_id  Application ID, already checked to be of the procedure and reviewable.
	 * @param int $user_id Who writes it.
	 * @return void
	 */
	public static function save_internal_note( int $row_id, int $user_id ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- el nonce de la revisión lo comprobó ProcedureEditor::handle().
		if ( isset( $_POST[ self::FIELD_INTERNAL_NOTE ] ) && is_string( $_POST[ self::FIELD_INTERNAL_NOTE ] ) ) {
			ApplicationOperations::set_internal_note( $row_id, $user_id, self::textarea( self::FIELD_INTERNAL_NOTE ) );
		}
	}

	/**
	 * Back to the same view of the table, with a notice.
	 *
	 * @param int    $procedure_id Procedure ID.
	 * @param string $notice       Notice code.
	 * @return void
	 */
	private static function back( int $procedure_id, string $notice ): void {
		$extra = array( ProcedureEditor::ARG_NOTICE => $notice );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- el nonce lo comprobó ProcedureEditor::handle(); solo se lee el filtro de vuelta.
		if ( ! empty( $_POST[ self::ARG_WITHDRAWN ] ) ) {
			$extra[ self::ARG_WITHDRAWN ] = 1;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- ídem.
		Shell::leave( ApplicationsPanel::url( $procedure_id, ApplicationsPanel::filter( $_POST ), $extra ) );
	}

	/**
	 * One submitted field, trimmed and sanitised.
	 *
	 * @param string $nombre Field name.
	 * @return string
	 */
	private static function field( string $nombre ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- el nonce lo comprobó ProcedureEditor::handle().
		return isset( $_POST[ $nombre ] ) && is_string( $_POST[ $nombre ] ) ? trim( sanitize_text_field( wp_unslash( $_POST[ $nombre ] ) ) ) : '';
	}

	/**
	 * One submitted multi-line field, trimmed and sanitised.
	 *
	 * @param string $nombre Field name.
	 * @return string
	 */
	private static function textarea( string $nombre ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- el nonce lo comprobó ProcedureEditor::handle().
		return isset( $_POST[ $nombre ] ) && is_string( $_POST[ $nombre ] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST[ $nombre ] ) ) ) : '';
	}

	// ─── el modelo ─────────────────────────────────────────────────────────

	/**
	 * What the panel paints of these actions.
	 *
	 * El historial se lee solo para la solicitud que se abre; las retiradas,
	 * solo cuando se piden; el catálogo, solo cuando se busca en él.
	 *
	 * @param int                   $procedure_id Procedure ID.
	 * @param int                   $user_id      Who is looking.
	 * @param array<string, mixed>  $request      `$_GET`.
	 * @param \WP_Post[]            $posts        Applications of the page.
	 * @param array<string, string> $args         Query args of the table, already URL-encoded.
	 * @return array<string, mixed>
	 */
	public static function model( int $procedure_id, int $user_id, array $request, array $posts, array $args = array() ): array {
		$abiertas = self::get( $request, PeopleSpecPanel::ARG_HISTORY );
		$filas    = array();
		foreach ( $posts as $post ) {
			$id           = (int) $post->ID;
			$hasta        = ApplicationActions::late_until( $id );
			$filas[ $id ] = array(
				'late_until'    => $hasta,
				'late_label'    => ApplicationActions::day( $hasta ),
				'late_open'     => ApplicationActions::is_late_open( $id ),
				'can_extend'    => '' === ApplicationActions::why_not_extend( $user_id, $id ),
				'internal_note' => (string) get_post_meta( $id, ApplicationMetaKeys::INTERNAL_NOTE, true ),
				'pending'       => ApplicationOperations::pending_for_school( $id ),
				'review'        => (string) get_post_meta( $id, ApplicationMetaKeys::REVIEW_STATE, true ),
				'history'       => array(
					'open'    => (string) $id === $abiertas,
					'url'     => ProcedureEditor::url( $procedure_id, ProcedureEditor::PANEL_APPLICATIONS, array( PeopleSpecPanel::ARG_HISTORY => $id ) + $args ) . '#prc-historial-' . $id,
					'entries' => (string) $id === $abiertas ? ApplicationHistory::entries( $id ) : array(),
				),
			);
		}
		$abrir = '1' === self::get( $request, self::ARG_WITHDRAWN );
		return array(
			'rows'            => $filas,
			'today'           => ProcedureState::today(),
			'resolved'        => ProcedureMetaKeys::STATE_RESOLVED === ProcedureState::of_post( $procedure_id ),
			'withdrawn_count' => ApplicationOperations::withdrawn_count( $procedure_id ),
			'withdrawn_open'  => $abrir,
			'withdrawn_url'   => ProcedureEditor::url( $procedure_id, ProcedureEditor::PANEL_APPLICATIONS, array( self::ARG_WITHDRAWN => 1 ) ),
			'back_url'        => ProcedureEditor::url( $procedure_id, ProcedureEditor::PANEL_APPLICATIONS ),
			'withdrawn'       => $abrir ? self::withdrawn( $procedure_id, $user_id ) : array(),
			'submit_for'      => self::submit_for_model( $procedure_id, $user_id, $request ),
		);
	}

	/**
	 * The withdrawn applications as the «Retiradas» list paints them.
	 *
	 * @param int $procedure_id Procedure ID.
	 * @param int $user_id      Who is looking.
	 * @return array<int, array<string, mixed>>
	 */
	private static function withdrawn( int $procedure_id, int $user_id ): array {
		$out   = array();
		$posts = ApplicationOperations::withdrawn( $procedure_id );
		cache_users( array_unique( array_map( 'intval', wp_list_pluck( $posts, 'post_author' ) ) ) );
		foreach ( $posts as $post ) {
			$id     = (int) $post->ID;
			$ultima = array();
			foreach ( ApplicationLog::all( $id ) as $entrada ) {
				$ultima = ApplicationHistory::WITHDRAWN === $entrada['event'] ? $entrada : $ultima;
			}
			$autor  = get_userdata( (int) $post->post_author );
			$nombre = (string) get_post_meta( $id, ApplicationMetaKeys::CENTRE_NAME, true );
			$codigo = (string) get_post_meta( $id, ApplicationMetaKeys::CENTRE_CODE, true );
			$porque = ApplicationActions::why_not_restore( $user_id, $id );
			$out[]  = array(
				'id'      => $id,
				'who'     => '' !== $codigo ? trim( ( '' !== $nombre ? $nombre : '(centro fuera del catálogo)' ) . ' (' . $codigo . ')' ) : ( false !== $autor ? (string) $autor->display_name : '' ),
				'when'    => '' !== (string) ( $ultima['at'] ?? '' ) ? (string) wp_date( 'd-m-Y H:i', (int) strtotime( (string) $ultima['at'] ) ) : (string) get_the_modified_date( 'd-m-Y H:i', $post ),
				'by'      => self::ORIGINS[ (string) ( $ultima['data']['by'] ?? '' ) ] ?? self::ORIGINS[ ApplicationOperations::BY_DESK ],
				'reason'  => (string) ( $ultima['data']['reason'] ?? '' ),
				'can'     => '' === $porque,
				'why'     => $porque,
				'history' => ApplicationHistory::entries( $id ),
			);
		}
		return $out;
	}

	/**
	 * What the «Presentar en nombre de un centro» form paints.
	 *
	 * Sin cargar el catálogo en el HTML: un campo de código que valida el
	 * servidor y, aparte, un buscador por código o nombre que devuelve como
	 * mucho {@see CentreCatalog::SEARCH_LIMIT} centros.
	 *
	 * @param int                  $procedure_id Procedure ID.
	 * @param int                  $user_id      Who is looking.
	 * @param array<string, mixed> $request      `$_GET`.
	 * @return array<string, mixed>
	 */
	private static function submit_for_model( int $procedure_id, int $user_id, array $request ): array {
		$porque = ApplicationActions::why_not_submit_for( $user_id, $procedure_id );
		$buscar = '' === $porque ? self::get( $request, self::ARG_FIND ) : '';
		return array(
			'can'      => '' === $porque,
			'open'     => array() !== self::$posted || '' !== $buscar || '' !== self::get( $request, self::ARG_PICK ),
			'search'   => $buscar,
			'results'  => '' !== $buscar ? CentreCatalog::search( $buscar ) : array(),
			'code'     => (string) ( self::$posted['code'] ?? self::get( $request, self::ARG_PICK ) ),
			'late'     => (string) ( self::$posted['late'] ?? '' ),
			'override' => '1' === ( self::$posted['override'] ?? '' ),
			'today'    => ProcedureState::today(),
			'action'   => strtok( ProcedureEditor::url( $procedure_id, ProcedureEditor::PANEL_APPLICATIONS ), '?' ),
			'hidden'   => wp_parse_args( (string) wp_parse_url( ProcedureEditor::url( $procedure_id, ProcedureEditor::PANEL_APPLICATIONS ), PHP_URL_QUERY ) ),
			'pick_url' => ProcedureEditor::url( $procedure_id, ProcedureEditor::PANEL_APPLICATIONS ),
		);
	}

	/**
	 * One GET value, as text.
	 *
	 * @param array<string, mixed> $request `$_GET`.
	 * @param string               $clave   Query arg.
	 * @return string
	 */
	private static function get( array $request, string $clave ): string {
		return isset( $request[ $clave ] ) && is_scalar( $request[ $clave ] ) ? trim( sanitize_text_field( wp_unslash( (string) $request[ $clave ] ) ) ) : '';
	}
}
