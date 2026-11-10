<?php
/**
 * Tests for acting on an application: withdraw it, restore it, extend it,
 * submit one on behalf of a school, the internal note and the history.
 *
 * @package Prc
 */

use Prc\Access\ApplicationActions;
use Prc\Access\ObserverScope;
use Prc\Access\ProcedureAccess;
use Prc\Domain\CentreCatalog;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\Notifications\Mailer;
use Prc\PublicFront\ApplicationActionsPanel;
use Prc\PublicFront\ApplicationExport;
use Prc\PublicFront\ApplicationFiles;
use Prc\PublicFront\ApplicationHistory;
use Prc\PublicFront\ApplicationLog;
use Prc\PublicFront\ApplicationOperations;
use Prc\PublicFront\Applications;
use Prc\PublicFront\ApplyForm;
use Prc\PublicFront\MyCentre;
use Prc\PublicFront\Observe;
use Prc\PublicFront\People;
use Prc\PublicFront\PeopleSpecPanel;
use Prc\PublicFront\ProcedureEditor;
use Prc\PublicFront\View\ApplyFormView;
use Prc\PublicFront\View\MyCentreView;
use Prc\PublicFront\View\ObserveView;
use Prc\PublicFront\View\ProcedureEditorView;
use Prc\PublicFront\WithdrawForm;

/**
 * Actuar sobre una solicitud (PLAN-0003, PR 9; ADR-0044).
 *
 * «Quiero darme de baja», «se inscribió por error», «inscríbanlo ustedes»,
 * «déjenle unos días más» y «¿quién cambió esto y cuándo?».
 */
class Test_Application_Actions extends WP_UnitTestCase {

	use Prc_Fixtures;

	/** A text only the internal note carries. */
	const INTERNA = 'NOTA-INTERNA-RESERVADA';

	/**
	 * Correos capturados.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private $enviados = array();

	/**
	 * Con el aplicativo, un catálogo inventado y el correo capturado.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
		$this->pages();
		remove_all_filters( 'prc_centres' );
		add_filter(
			'prc_centres',
			static function (): array {
				return array(
					array(
						'code'   => 'C0001',
						'name'   => 'Centro Norte',
						'island' => 'Isla Uno',
						'type'   => 'Público de prueba',
						'zone'   => 'Z0001',
					),
					array(
						'code'   => 'C0002',
						'name'   => 'Centro Sur',
						'island' => 'Isla Dos',
						'type'   => 'Público de prueba',
						'zone'   => 'Z0001',
					),
					array(
						'code'   => 'C0003',
						'name'   => 'Centro Cerrado',
						'active' => false,
					),
					array(
						'code' => 'Z0001',
						'name' => 'Zona Inventada',
					),
				);
			}
		);
		CentreCatalog::flush();
		foreach ( array( ApplyForm::class, WithdrawForm::class, ProcedureEditor::class, Observe::class ) as $clase ) {
			\Closure::bind(
				static function (): void {
					self::$rejected = '';
				},
				null,
				$clase
			)();
		}
		\Closure::bind(
			static function (): void {
				self::$posted = array();
			},
			null,
			ApplicationActionsPanel::class
		)();
		$this->enviados = array();
		add_filter(
			'pre_wp_mail',
			function ( $corto, array $atts ): bool {
				unset( $corto );
				$this->enviados[] = $atts;
				return true;
			},
			10,
			2
		);
	}

	// ─── piezas ────────────────────────────────────────────────────────────

	/**
	 * Un día relativo a hoy.
	 *
	 * @param int $dias Days from today.
	 * @return string Y-m-d.
	 */
	private function dia( int $dias ): string {
		return gmdate( 'Y-m-d', (int) strtotime( ProcedureState::today() . ' ' . sprintf( '%+d', $dias ) . ' days' ) );
	}

	/**
	 * Un procedimiento de un ámbito, quien lo revisa y la solicitud de un centro.
	 *
	 * @param bool                 $abierto Whether the application window is open today.
	 * @param array<string, mixed> $meta    More procedure meta.
	 * @return array{area:int, reviewer:int, procedure:int, head:int, application:int}
	 */
	private function caso( bool $abierto = true, array $meta = array() ): array {
		$area      = $this->area( 'Ámbito de prueba' );
		$reviewer  = $this->manager( array( $area ) );
		$procedure = $this->procedure(
			$reviewer,
			array( $area ),
			array_merge(
				array(
					ProcedureMetaKeys::OPENS_AT       => $this->dia( $abierto ? -5 : -20 ),
					ProcedureMetaKeys::CLOSES_AT      => $this->dia( $abierto ? 5 : -10 ),
					ProcedureMetaKeys::CONTACT_EMAILS => array( 'contacto@example.org' ),
				),
				$meta
			),
			array( 'post_title' => 'Red de prueba' )
		);
		$head      = $this->school_head( 'C0001' );
		wp_update_user(
			array(
				'ID'         => $head,
				'user_email' => 'direccion@example.org',
			)
		);
		$application    = $this->application( $procedure, $head, 'C0001', array( ApplicationMetaKeys::CENTRE_NAME => 'Centro Norte' ) );
		$this->enviados = array();
		return compact( 'area', 'reviewer', 'procedure', 'head', 'application' );
	}

	/**
	 * Retirar desde la pantalla de la solicitud.
	 *
	 * @param int         $uid   Who.
	 * @param int         $id    Application ID.
	 * @param string|null $nonce Nonce; null for a good one.
	 * @return string|null Where it went; null when it did not leave.
	 */
	private function retirar( int $uid, int $id, ?string $nonce = null ): ?string {
		$this->acting_as( $uid );
		$campos = array(
			WithdrawForm::FIELD_OP          => WithdrawForm::OP_WITHDRAW,
			WithdrawForm::FIELD_APPLICATION => (string) $id,
		);
		if ( null === $nonce ) {
			$this->post( $campos, WithdrawForm::nonce_action( $id ), WithdrawForm::NONCE_FIELD );
		} else {
			$campos[ WithdrawForm::NONCE_FIELD ] = $nonce;
			$this->post( $campos );
		}
		return $this->exit_url( array( WithdrawForm::class, 'handle' ) );
	}

	/**
	 * Una operación del panel «Solicitudes» del taller.
	 *
	 * @param int                  $uid       Who.
	 * @param string               $op        Operation.
	 * @param int                  $procedure Procedure ID.
	 * @param int                  $row       Application ID; 0 for none.
	 * @param array<string, mixed> $extra     More fields.
	 * @return string|null
	 */
	private function taller( int $uid, string $op, int $procedure, int $row = 0, array $extra = array() ): ?string {
		$this->acting_as( $uid );
		$fila = in_array( $op, ApplicationActionsPanel::ROW_OPS, true ) || ProcedureEditor::OP_REVIEW === $op ? $row : 0;
		$this->post(
			array_merge(
				array(
					ProcedureEditor::FIELD_DO        => $op,
					ProcedureEditor::FIELD_PROCEDURE => (string) $procedure,
					ProcedureEditor::FIELD_ROW       => (string) $row,
				),
				$extra
			),
			ProcedureEditor::nonce_action( $op, $fila ),
			ProcedureEditor::nonce_name( $op, $fila )
		);
		return $this->exit_url( array( ProcedureEditor::class, 'handle' ) );
	}

	/**
	 * El motivo del último rechazo del taller.
	 *
	 * @return string
	 */
	private function rechazo_taller(): string {
		return (string) \Closure::bind(
			static function (): string {
				return self::$rejected;
			},
			null,
			ProcedureEditor::class
		)();
	}

	/**
	 * El motivo del último rechazo al retirar.
	 *
	 * @return string
	 */
	private function rechazo_retirar(): string {
		return (string) \Closure::bind(
			static function (): string {
				return self::$rejected;
			},
			null,
			WithdrawForm::class
		)();
	}

	/**
	 * El panel «Solicitudes» como lo ve alguien.
	 *
	 * @param int                  $uid       Who.
	 * @param int                  $procedure Procedure ID.
	 * @param array<string, mixed> $get       More GET.
	 * @return string
	 */
	private function panel( int $uid, int $procedure, array $get = array() ): string {
		$this->acting_as( $uid );
		$_GET = array_merge(
			array(
				ProcedureEditor::ARG_PROCEDURE => (string) $procedure,
				ProcedureEditor::ARG_PANEL     => ProcedureEditor::PANEL_APPLICATIONS,
			),
			$get
		);
		return ProcedureEditorView::html( ProcedureEditor::model() );
	}

	/**
	 * La pantalla de la solicitud como la ve alguien.
	 *
	 * @param int $uid       Who.
	 * @param int $procedure Procedure ID.
	 * @return string
	 */
	private function solicitud( int $uid, int $procedure ): string {
		$this->acting_as( $uid );
		$_GET = array( ApplyForm::ARG_PROCEDURE => (string) $procedure );
		return ApplyForm::render();
	}

	/**
	 * Los eventos del historial de una solicitud, en orden.
	 *
	 * @param int $id Application ID.
	 * @return string[]
	 */
	private function eventos( int $id ): array {
		return array_column( ApplicationLog::all( $id ), 'event' );
	}

	/**
	 * Los destinatarios de los correos capturados.
	 *
	 * @return string[]
	 */
	private function destinos(): array {
		$out = array_map( 'strval', array_column( $this->enviados, 'to' ) );
		sort( $out );
		return $out;
	}

	/**
	 * El último día de una prórroga es una fecha válida, y no de ayer.
	 */
	public function test_the_last_day_of_an_extension_is_checked() {
		$hoy = '2026-11-10';
		$this->assertSame( '', ApplicationActions::why_not_late_date( '2026-11-10', $hoy ) );
		$this->assertNotSame( '', ApplicationActions::why_not_late_date( '2026-11-09', $hoy ) );
		$this->assertNotSame( '', ApplicationActions::why_not_late_date( '2026-02-30', $hoy ) );
	}

	// ─── retirar (quien solicita) ──────────────────────────────────────────

	/**
	 * Retirar con el plazo abierto: a la papelera, una entrada en el historial,
	 * aviso a los contactos y acuse; y se puede volver a presentar.
	 */
	public function test_withdrawing_in_time_bins_it_and_lets_the_school_apply_again() {
		$c   = $this->caso();
		$url = $this->retirar( $c['head'], $c['application'] );
		$this->assertNotNull( $url, $this->rechazo_retirar() );
		$this->assertSame( WithdrawForm::NOTICE, $this->query_arg( (string) $url, ApplyForm::ARG_NOTICE ) );
		$this->assertSame( 'trash', get_post_status( $c['application'] ) );
		$this->assertSame( array( ApplicationHistory::WITHDRAWN ), $this->eventos( $c['application'] ) );
		$this->assertSame( ApplicationOperations::BY_APPLICANT, ApplicationLog::all( $c['application'] )[0]['data']['by'] );
		$this->assertSame( $c['head'], ApplicationLog::all( $c['application'] )[0]['user'] );
		$this->assertSame( array( 'contacto@example.org', 'direccion@example.org' ), $this->destinos() );
		foreach ( $this->enviados as $correo ) {
			$this->assertStringContainsString( 'Solicitud retirada', (string) $correo['subject'] );
		}

		// Ya no es suya: ni la ve, ni sale en «Mi centro».
		$this->assertFalse( ProcedureAccess::can_view_application( $c['head'], $c['application'] ) );
		$this->acting_as( $c['head'] );
		$this->assertSame( array(), MyCentre::model()['rows'] );
		$this->assertNull( Applications::find( $c['procedure'], 'C0001' ), 'find() no mira la papelera' );

		// Vuelve a presentar: una nueva, distinta de la retirada.
		$nueva = Applications::save( $c['procedure'], $c['head'], array( 'position' => ApplicationMetaKeys::POSITION_HEAD ) );
		$this->assertGreaterThan( 0, $nueva );
		$this->assertNotSame( $c['application'], $nueva );
		$this->assertSame( 'publish', get_post_status( $nueva ) );
	}

	/**
	 * Fuera de plazo no se retira; con prórroga viva, sí; vencida, no.
	 */
	public function test_withdrawing_out_of_time_needs_a_live_extension() {
		$c = $this->caso( false );
		$this->assertNull( $this->retirar( $c['head'], $c['application'] ) );
		$this->assertSame( 'publish', get_post_status( $c['application'] ) );
		$this->assertStringContainsString( 'plazo de solicitud está cerrado', $this->rechazo_retirar() );
		$this->assertFalse( current_user_can( ApplicationActions::CAP_WITHDRAW, $c['application'] ) );

		update_post_meta( $c['application'], ApplicationMetaKeys::LATE_UNTIL, $this->dia( -1 ) );
		$this->assertNull( $this->retirar( $c['head'], $c['application'] ), 'prórroga vencida' );

		update_post_meta( $c['application'], ApplicationMetaKeys::LATE_UNTIL, $this->dia( 0 ) );
		$this->assertNotNull( $this->retirar( $c['head'], $c['application'] ), 'prórroga viva, hoy es su último día' );
		$this->assertSame( 'trash', get_post_status( $c['application'] ) );
	}

	/**
	 * Con el procedimiento histórico no se retira, ni con prórroga.
	 */
	public function test_an_archived_procedure_is_never_withdrawn_from() {
		$c = $this->caso( true, array( ProcedureMetaKeys::ARCHIVED => true ) );
		update_post_meta( $c['application'], ApplicationMetaKeys::LATE_UNTIL, $this->dia( 3 ) );
		$this->assertNull( $this->retirar( $c['head'], $c['application'] ) );
		$this->assertSame( 'publish', get_post_status( $c['application'] ) );
		$this->assertFalse( ApplicationActions::is_late_open( $c['application'] ) );
	}

	/**
	 * Otro centro, quien revisa, quien consulta o un nonce malo no retiran.
	 */
	public function test_only_the_school_withdraws_its_application() {
		$c        = $this->caso();
		$ajeno    = $this->school_head( 'C0002' );
		$consulta = $this->observer( array( 'Z0001' ) );
		foreach ( array( $ajeno, $c['reviewer'], $consulta ) as $uid ) {
			$this->assertNull( $this->retirar( $uid, $c['application'] ) );
			$this->acting_as( $uid );
			$this->assertFalse( current_user_can( ApplicationActions::CAP_WITHDRAW, $c['application'] ) );
		}
		$this->assertNull( $this->retirar( $c['head'], $c['application'], 'malo' ) );
		$this->assertSame( 'publish', get_post_status( $c['application'] ) );

		// Otra cuenta del mismo centro sí.
		$this->assertNotNull( $this->retirar( $this->school_head( 'C0001' ), $c['application'] ) );
	}

	/**
	 * Ni por la REST ni por el escritorio retira quien solicita.
	 */
	public function test_the_school_cannot_bin_it_through_rest_or_the_dashboard() {
		$c = $this->caso();
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init', $wp_rest_server );
		$this->acting_as( $c['head'] );
		$respuesta = rest_do_request( new WP_REST_Request( 'DELETE', '/wp/v2/prc_application/' . $c['application'] ) );
		$this->assertSame( 404, $respuesta->get_status(), 'las solicitudes no se publican en la REST' );
		$wp_rest_server = null;
		$this->assertFalse( current_user_can( 'delete_post', $c['application'] ) );
		$this->assertFalse( current_user_can( 'edit_post', $c['application'] ) );
		$this->assertSame( 'publish', get_post_status( $c['application'] ) );
	}

	/**
	 * La pantalla ofrece retirar con confirmación, y «Mi centro» la enlaza.
	 */
	public function test_the_screens_offer_withdrawing_with_a_confirmation() {
		$c    = $this->caso();
		$html = $this->solicitud( $c['head'], $c['procedure'] );
		$this->assertStringContainsString( 'id="' . WithdrawForm::ANCHOR . '"', $html );
		$this->assertStringContainsString( 'data-prc-confirm="¿Retirar su solicitud?', $html );
		$this->assertStringContainsString( 'data-prc-confirm-ok="Retirar la solicitud"', $html );
		$this->assertStringContainsString( 'name="' . WithdrawForm::NONCE_FIELD . '"', $html );

		$this->acting_as( $c['head'] );
		$_GET = array();
		$this->assertStringContainsString( '#' . WithdrawForm::ANCHOR, MyCentreView::html( MyCentre::model() ) );

		update_post_meta( $c['procedure'], ProcedureMetaKeys::CLOSES_AT, $this->dia( -1 ) );
		$this->assertStringNotContainsString( 'data-prc-confirm="¿Retirar su solicitud?', $this->solicitud( $c['head'], $c['procedure'] ) );
	}

	/**
	 * Apagado en «Avisos», retirar no manda nada.
	 */
	public function test_the_withdrawal_mail_can_be_switched_off() {
		$c = $this->caso( true, array( ProcedureMetaKeys::NOTIFY => array( Mailer::EVENT_APPLICATION_WITHDRAWN ) ) );
		$this->assertNotNull( $this->retirar( $c['head'], $c['application'] ) );
		$this->assertSame( array(), $this->enviados );
		$this->assertArrayHasKey( Mailer::EVENT_APPLICATION_WITHDRAWN, ProcedureMetaKeys::notify_events() );
		$this->assertArrayHasKey( Mailer::EVENT_APPLICATION_EXTENDED, ProcedureMetaKeys::notify_events() );
	}

	/**
	 * Los documentos se quedan en la papelera y se van al borrarla para siempre.
	 */
	public function test_documents_stay_in_the_bin_and_go_when_deleted_for_good() {
		$c   = $this->caso();
		$dir = ApplicationFiles::root();
		$this->assertNotSame( '', $dir );
		wp_mkdir_p( $dir );
		$fichero = 'ab/cd/abcdef0123456789abcdef0123456789.pdf';
		wp_mkdir_p( $dir . '/ab/cd' );
		file_put_contents( $dir . '/' . $fichero, '%PDF-1.4 prueba' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- un fichero de prueba.
		update_post_meta(
			$c['application'],
			ApplicationMetaKeys::FILES,
			array(
				'q1' => array(
					'id'     => 'abcdef0123456789abcdef0123456789',
					'name'   => 'memoria.pdf',
					'stored' => $fichero,
					'mime'   => 'application/pdf',
					'size'   => 15,
					'sha256' => hash( 'sha256', '%PDF-1.4 prueba' ),
				),
			)
		);
		$camino = ApplicationFiles::path( ApplicationFiles::descriptors( $c['application'] )['q1'] ?? array() );
		$this->assertNotSame( '', $camino, 'el descriptor de prueba vale' );
		$this->assertNotNull( $this->retirar( $c['head'], $c['application'] ) );
		$this->assertFileExists( $camino, 'en la papelera, el documento se conserva' );
		$this->acting_as( $c['reviewer'] );
		wp_delete_post( $c['application'], true );
		$this->assertFileExists( $camino, 'quien revisa no la borra para siempre' );
		$this->acting_as( $this->administrator() );
		wp_delete_post( $c['application'], true );
		$this->assertFileDoesNotExist( $camino, 'borrada para siempre, el documento también' );
	}

	/**
	 * Borrar para siempre una retirada es de administración; quien revisa
	 * envía a la papelera y restaura.
	 */
	public function test_only_administration_deletes_a_withdrawn_one_for_good() {
		$c = $this->caso();
		$this->acting_as( $c['reviewer'] );
		$this->assertTrue( current_user_can( 'delete_post', $c['application'] ), 'a la papelera, sí' );
		ApplicationOperations::withdraw( $c['application'], $c['head'], ApplicationOperations::BY_APPLICANT );
		$this->assertFalse( current_user_can( 'delete_post', $c['application'] ), 'borrarla para siempre, no' );
		$this->assertTrue( current_user_can( ApplicationActions::CAP_RESTORE, $c['application'] ) );
		$this->assertFalse( wp_delete_post( $c['application'], true ) );
		$this->assertSame( 'trash', get_post_status( $c['application'] ) );
		$this->acting_as( $this->administrator() );
		$this->assertTrue( current_user_can( 'delete_post', $c['application'] ) );
	}

	/**
	 * La papelera del núcleo no se lleva una retirada antigua.
	 */
	public function test_the_automatic_bin_emptying_keeps_withdrawn_applications() {
		$c = $this->caso();
		ApplicationOperations::withdraw( $c['application'], $c['head'], ApplicationOperations::BY_APPLICANT );
		$this->assertSame( '', get_post_meta( $c['application'], '_wp_trash_meta_time', true ), 'sin fecha de caducidad' );
		// Y aunque la tuviera —una retirada de antes de esta regla—, el vaciado
		// automático, sin nadie delante, no la borra.
		update_post_meta( $c['application'], '_wp_trash_meta_time', time() - 100 * DAY_IN_SECONDS );
		$papelera = $this->procedure( $c['reviewer'], array( $c['area'] ), array(), array( 'post_title' => 'Para la papelera' ) );
		wp_trash_post( $papelera );
		update_post_meta( $papelera, '_wp_trash_meta_time', time() - 100 * DAY_IN_SECONDS );
		$this->acting_as( 0 );
		wp_scheduled_delete();
		$this->assertSame( 'trash', get_post_status( $c['application'] ) );
		$this->assertFalse( get_post_status( $papelera ), 'el resto de la papelera se vacía como siempre' );
		$this->assertNotSame( array(), ApplicationLog::all( $c['application'] ) );
		// Restaurada, vuelve como estaba.
		ApplicationOperations::restore( $c['application'] );
		$this->assertSame( 'publish', get_post_status( $c['application'] ) );
	}

	/**
	 * Con el procedimiento histórico nadie retira ni restaura, tampoco
	 * administración por el escritorio.
	 */
	public function test_an_archived_procedure_freezes_the_bin_for_everyone() {
		$c     = $this->caso();
		$otra  = $this->application( $c['procedure'], $this->school_head( 'C0002' ), 'C0002' );
		$admin = $this->administrator();
		ApplicationOperations::withdraw( $otra, $c['reviewer'], ApplicationOperations::BY_REVIEWER, 'Por error.' );
		update_post_meta( $c['procedure'], ProcedureMetaKeys::ARCHIVED, true );
		$this->acting_as( $admin );
		$this->assertFalse( current_user_can( 'delete_post', $c['application'] ) );
		$this->assertFalse( current_user_can( 'delete_post', $otra ) );
		$this->assertFalse( current_user_can( ApplicationActions::CAP_RESTORE, $otra ) );
		$this->assertNull( $this->taller( $admin, ApplicationActionsPanel::OP_RETIRE, $c['procedure'], $c['application'], array( ApplicationActionsPanel::FIELD_REASON => 'x' ) ) );
		wp_untrash_post( $otra );
		$this->assertSame( 'trash', get_post_status( $otra ), 'tampoco por el escritorio' );
		$this->assertSame( 'publish', get_post_status( $c['application'] ) );
	}

	// ─── retirar y restaurar (quien revisa) ────────────────────────────────

	/**
	 * Quien revisa retira con motivo obligatorio; sin correo al centro.
	 */
	public function test_the_reviewer_withdraws_with_a_reason() {
		$c = $this->caso();
		$this->assertNull( $this->taller( $c['reviewer'], ApplicationActionsPanel::OP_RETIRE, $c['procedure'], $c['application'] ) );
		$this->assertStringContainsString( 'motivo', $this->rechazo_taller() );
		$this->assertSame( 'publish', get_post_status( $c['application'] ) );

		$url = $this->taller( $c['reviewer'], ApplicationActionsPanel::OP_RETIRE, $c['procedure'], $c['application'], array( ApplicationActionsPanel::FIELD_REASON => 'Se inscribió por error.' ) );
		$this->assertSame( 'retirada', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
		$this->assertSame( 'trash', get_post_status( $c['application'] ) );
		$entrada = ApplicationLog::all( $c['application'] )[0];
		$this->assertSame( ApplicationHistory::WITHDRAWN, $entrada['event'] );
		$this->assertSame( 'Se inscribió por error.', $entrada['data']['reason'] );
		$this->assertSame( ApplicationOperations::BY_REVIEWER, $entrada['data']['by'] );
		$this->assertSame( array(), $this->enviados, 'retirar quien revisa no avisa a nadie' );

		// En «Retiradas», con su motivo y el botón de restaurar.
		$html = $this->panel( $c['reviewer'], $c['procedure'], array( ApplicationActionsPanel::ARG_WITHDRAWN => '1' ) );
		$this->assertStringContainsString( 'Se inscribió por error.', $html );
		$this->assertStringContainsString( 'value="' . ApplicationActionsPanel::OP_RESTORE . '"', $html );
		$this->assertStringContainsString( 'Retirada por quien revisa', $html );
		$this->assertStringContainsString( 'Retiradas (1)', $this->panel( $c['reviewer'], $c['procedure'] ) );
	}

	/**
	 * El nonce de una operación sobre una solicitud es de esa solicitud: el de
	 * otra fila no vale.
	 */
	public function test_the_nonce_of_one_row_does_not_serve_another() {
		$c    = $this->caso();
		$otra = $this->application( $c['procedure'], $this->school_head( 'C0002' ), 'C0002' );
		$op   = ApplicationActionsPanel::OP_RETIRE;
		$this->assertNotSame( ProcedureEditor::nonce_action( $op, $c['application'] ), ProcedureEditor::nonce_action( $op, $otra ) );
		$this->acting_as( $c['reviewer'] );
		$this->post(
			array(
				ProcedureEditor::FIELD_DO                 => $op,
				ProcedureEditor::FIELD_PROCEDURE          => (string) $c['procedure'],
				ProcedureEditor::FIELD_ROW                => (string) $otra,
				ApplicationActionsPanel::FIELD_REASON     => 'Por error.',
				ProcedureEditor::nonce_name( $op, $otra ) => wp_create_nonce( ProcedureEditor::nonce_action( $op, $c['application'] ) ),
			)
		);
		$this->assertNull( $this->exit_url( array( ProcedureEditor::class, 'handle' ) ) );
		$this->assertSame( 'publish', get_post_status( $otra ) );
		$this->assertNotNull( $this->taller( $c['reviewer'], $op, $c['procedure'], $otra, array( ApplicationActionsPanel::FIELD_REASON => 'Por error.' ) ) );
	}

	/**
	 * La revisión en bloque no retira, y no toca una retirada.
	 */
	public function test_the_bulk_review_never_touches_a_withdrawn_one() {
		$c = $this->caso();
		ApplicationOperations::withdraw( $c['application'], $c['reviewer'], ApplicationOperations::BY_REVIEWER, 'Por error.' );
		$this->taller(
			$c['reviewer'],
			ProcedureEditor::OP_BULK_REVIEW,
			$c['procedure'],
			0,
			array(
				'prc_rows'                   => array( (string) $c['application'] ),
				ProcedureEditor::FIELD_STATE => ApplicationMetaKeys::REVIEW_ADMITTED,
			)
		);
		$this->assertSame( 'trash', get_post_status( $c['application'] ) );
		$this->assertSame( ApplicationMetaKeys::REVIEW_SUBMITTED, get_post_meta( $c['application'], ApplicationMetaKeys::REVIEW_STATE, true ) );
	}

	/**
	 * Restaurar la devuelve como estaba; con otra ya presentada, no.
	 */
	public function test_restoring_needs_the_school_to_have_no_other_application() {
		$c = $this->caso();
		$this->assertNotNull( $this->retirar( $c['head'], $c['application'] ) );
		$nueva = $this->application( $c['procedure'], $c['head'], 'C0001' );

		$this->assertNull( $this->taller( $c['reviewer'], ApplicationActionsPanel::OP_RESTORE, $c['procedure'], $c['application'] ) );
		$this->assertStringContainsString( 'otra solicitud', $this->rechazo_taller() );
		$this->assertSame( 'trash', get_post_status( $c['application'] ) );
		// Tampoco por la puerta del escritorio.
		wp_untrash_post( $c['application'] );
		$this->assertSame( 'trash', get_post_status( $c['application'] ) );

		wp_trash_post( $nueva );
		$url = $this->taller( $c['reviewer'], ApplicationActionsPanel::OP_RESTORE, $c['procedure'], $c['application'] );
		$this->assertSame( 'restaurada', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
		$this->assertSame( 'publish', get_post_status( $c['application'] ), 'vuelve publicada, no en borrador' );
		$this->assertSame( array( ApplicationHistory::WITHDRAWN, ApplicationHistory::RESTORED ), $this->eventos( $c['application'] ) );
		$this->assertSame( $c['application'], Applications::find( $c['procedure'], 'C0001' ) );
	}

	/**
	 * Restaurar es de quien revisa ese ámbito: ni quien solicita, ni quien
	 * consulta, ni otro ámbito.
	 */
	public function test_only_the_reviewer_of_the_area_restores() {
		$c = $this->caso();
		ApplicationOperations::withdraw( $c['application'], $c['head'], ApplicationOperations::BY_APPLICANT );
		$otro = $this->manager( array( $this->area( 'Otro ámbito' ) ) );
		foreach ( array( $c['head'], $this->observer( array( 'Z0001' ) ), $otro ) as $uid ) {
			$this->acting_as( $uid );
			$this->assertFalse( current_user_can( ApplicationActions::CAP_RESTORE, $c['application'] ) );
			$this->assertFalse( current_user_can( ApplicationActions::CAP_EXTEND, $c['application'] ) );
			$this->assertFalse( current_user_can( ApplicationActions::CAP_SUBMIT_FOR, $c['procedure'] ) );
			$this->assertNull( $this->taller( $uid, ApplicationActionsPanel::OP_RESTORE, $c['procedure'], $c['application'] ) );
		}
		$this->assertSame( 'trash', get_post_status( $c['application'] ) );
		$this->acting_as( $c['reviewer'] );
		$this->assertTrue( current_user_can( ApplicationActions::CAP_RESTORE, $c['application'] ) );
	}

	// ─── la prórroga ───────────────────────────────────────────────────────

	/**
	 * Con prórroga el centro edita su solicitud con el plazo cerrado, hasta su
	 * último día incluido; al día siguiente, no.
	 */
	public function test_an_extension_reopens_that_application_only() {
		$c      = $this->caso( false );
		$otra   = $this->application( $c['procedure'], $this->school_head( 'C0002' ), 'C0002' );
		$campo  = array(
			ApplyForm::FIELD_OP        => ApplyForm::OP_APPLY,
			ApplyForm::FIELD_PROCEDURE => (string) $c['procedure'],
			ApplyForm::FIELD_POSITION  => ApplicationMetaKeys::POSITION_DEPUTY_HEAD,
		);
		$enviar = function ( int $uid ) use ( $campo ): ?string {
			$this->acting_as( $uid );
			$this->post( $campo, ApplyForm::NONCE_ACTION, ApplyForm::NONCE_FIELD );
			return $this->exit_url( array( ApplyForm::class, 'handle' ) );
		};
		$this->assertNull( $enviar( $c['head'] ), 'sin prórroga, cerrado' );

		$url = $this->taller( $c['reviewer'], ApplicationActionsPanel::OP_EXTEND, $c['procedure'], $c['application'], array( ApplicationActionsPanel::FIELD_LATE => $this->dia( 0 ) ) );
		$this->assertSame( 'prorroga', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
		$this->assertSame( $this->dia( 0 ), get_post_meta( $c['application'], ApplicationMetaKeys::LATE_UNTIL, true ) );
		$this->assertStringContainsString( 'Prórroga de su solicitud', (string) end( $this->enviados )['subject'] );
		$this->assertSame( 'direccion@example.org', (string) end( $this->enviados )['to'] );

		$this->assertStringContainsString( 'tiene de plazo hasta el', $this->solicitud( $c['head'], $c['procedure'] ) );
		$this->assertNotNull( $enviar( $c['head'] ), 'el último día, incluido' );
		$this->assertSame( ApplicationMetaKeys::POSITION_DEPUTY_HEAD, get_post_meta( $c['application'], ApplicationMetaKeys::APPLICANT_POSITION, true ) );

		// A la otra solicitud no le afecta.
		$this->assertFalse( ApplicationActions::is_late_open( $otra ) );

		update_post_meta( $c['application'], ApplicationMetaKeys::LATE_UNTIL, $this->dia( -1 ) );
		$this->assertNull( $enviar( $c['head'] ), 'vencida, no deja editar' );
	}

	/**
	 * La prórroga se quita, y quitarla no avisa; una fecha pasada se rechaza.
	 */
	public function test_an_extension_is_taken_away_and_checked() {
		$c = $this->caso( false );
		$this->assertNull( $this->taller( $c['reviewer'], ApplicationActionsPanel::OP_EXTEND, $c['procedure'], $c['application'], array( ApplicationActionsPanel::FIELD_LATE => $this->dia( -1 ) ) ) );
		$this->assertStringContainsString( 'antes de hoy', $this->rechazo_taller() );
		$this->taller( $c['reviewer'], ApplicationActionsPanel::OP_EXTEND, $c['procedure'], $c['application'], array( ApplicationActionsPanel::FIELD_LATE => $this->dia( 4 ) ) );
		$this->enviados = array();
		$url            = $this->taller( $c['reviewer'], ApplicationActionsPanel::OP_EXTEND, $c['procedure'], $c['application'], array( ApplicationActionsPanel::FIELD_LATE_REMOVE => '1' ) );
		$this->assertSame( 'prorroga-quitada', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
		$this->assertSame( '', get_post_meta( $c['application'], ApplicationMetaKeys::LATE_UNTIL, true ) );
		$this->assertSame( array(), $this->enviados );
		$this->assertSame( array( ApplicationHistory::EXTENDED, ApplicationHistory::EXTENDED ), $this->eventos( $c['application'] ) );
	}

	/**
	 * Con el procedimiento histórico no se concede prórroga, ni a quien administra.
	 */
	public function test_no_extension_in_an_archived_procedure() {
		$c = $this->caso( false, array( ProcedureMetaKeys::ARCHIVED => true ) );
		$this->assertNull( $this->taller( $this->administrator(), ApplicationActionsPanel::OP_EXTEND, $c['procedure'], $c['application'], array( ApplicationActionsPanel::FIELD_LATE => $this->dia( 3 ) ) ) );
		$this->assertSame( '', get_post_meta( $c['application'], ApplicationMetaKeys::LATE_UNTIL, true ) );
		$this->assertStringContainsString( 'histórico', $this->rechazo_taller() );
	}

	// ─── en nombre de un centro ────────────────────────────────────────────

	/**
	 * Presentar en nombre de un centro crea una vacía, con prórroga y marca;
	 * el centro la completa con su formulario y pasa a ser quien la presenta.
	 */
	public function test_submitting_on_behalf_creates_an_empty_one_the_school_completes() {
		$c   = $this->caso( false );
		$url = $this->taller(
			$c['reviewer'],
			ApplicationActionsPanel::OP_SUBMIT_FOR,
			$c['procedure'],
			0,
			array(
				ApplicationActionsPanel::FIELD_CODE => 'c0002',
				ApplicationActionsPanel::FIELD_LATE => $this->dia( 7 ),
			)
		);
		$this->assertSame( 'en-nombre', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ), $this->rechazo_taller() );
		$id = (int) Applications::find( $c['procedure'], 'C0002' );
		$this->assertGreaterThan( 0, $id );
		$this->assertSame( ApplicationMetaKeys::REVIEW_SUBMITTED, get_post_meta( $id, ApplicationMetaKeys::REVIEW_STATE, true ) );
		$this->assertSame( $this->dia( 7 ), get_post_meta( $id, ApplicationMetaKeys::LATE_UNTIL, true ) );
		$this->assertSame( $c['reviewer'], (int) get_post_meta( $id, ApplicationMetaKeys::CREATED_BY, true ) );
		$this->assertSame( $c['reviewer'], (int) get_post_field( 'post_author', $id ) );
		$this->assertSame( 'Centro Sur', get_post_meta( $id, ApplicationMetaKeys::CENTRE_NAME, true ) );
		$this->assertSame( array( ApplicationHistory::CREATED_FOR ), $this->eventos( $id ) );
		$this->assertSame( array(), $this->enviados, 'el centro no tiene dirección en el catálogo: no sale nada' );

		// El centro la ve en «Mi centro» y la completa con el plazo cerrado.
		$sur = $this->school_head( 'C0002' );
		$this->acting_as( $sur );
		$_GET = array();
		$this->assertSame( (string) $c['procedure'], MyCentre::model()['rows'][0]['procedure_id'] );
		$this->assertStringContainsString( 'name="' . ApplyForm::FIELD_POSITION . '"', $this->solicitud( $sur, $c['procedure'] ), 'el formulario está abierto' );
		$this->assertStringNotContainsString( 'name="' . ApplyForm::FIELD_ACCEPT . '" value="1" checked', $this->solicitud( $sur, $c['procedure'] ) );
		$this->post(
			array(
				ApplyForm::FIELD_OP        => ApplyForm::OP_APPLY,
				ApplyForm::FIELD_PROCEDURE => (string) $c['procedure'],
				ApplyForm::FIELD_POSITION  => ApplicationMetaKeys::POSITION_HEAD,
			),
			ApplyForm::NONCE_ACTION,
			ApplyForm::NONCE_FIELD
		);
		$this->assertNotNull( $this->exit_url( array( ApplyForm::class, 'handle' ) ) );
		$this->assertSame( $sur, (int) get_post_field( 'post_author', $id ), 'quien la completa pasa a presentarla' );
		$this->assertSame( $c['reviewer'], (int) get_post_meta( $id, ApplicationMetaKeys::CREATED_BY, true ) );
		$this->assertSame( array( ApplicationHistory::CREATED_FOR, ApplicationHistory::SUBMITTED ), $this->eventos( $id ) );
	}

	/**
	 * Un centro que no cumple los requisitos solo con la casilla, y queda anotado.
	 */
	public function test_an_ineligible_school_needs_the_override() {
		$c      = $this->caso( false, array( ProcedureMetaKeys::ISLANDS => array( 'Isla Uno' ) ) );
		$campos = array(
			ApplicationActionsPanel::FIELD_CODE => 'C0002',
			ApplicationActionsPanel::FIELD_LATE => $this->dia( 7 ),
		);
		$this->assertNull( $this->taller( $c['reviewer'], ApplicationActionsPanel::OP_SUBMIT_FOR, $c['procedure'], 0, $campos ) );
		$this->assertStringContainsString( 'Aunque no cumpla los requisitos', $this->rechazo_taller() );
		$this->assertNull( Applications::find( $c['procedure'], 'C0002' ) );

		$this->taller( $c['reviewer'], ApplicationActionsPanel::OP_SUBMIT_FOR, $c['procedure'], 0, $campos + array( ApplicationActionsPanel::FIELD_OVERRIDE => '1' ) );
		$id = (int) Applications::find( $c['procedure'], 'C0002' );
		$this->assertGreaterThan( 0, $id );
		$this->assertNotSame( '', ApplicationLog::all( $id )[0]['data']['ineligible'] );
		$this->assertStringContainsString( 'Aunque no cumple los requisitos', implode( ' ', array_column( ApplicationHistory::entries( $id ), 'what' ) ) );
	}

	/**
	 * Lo que no se presenta en nombre de nadie: un código que no existe, uno
	 * inactivo, un centro que ya tiene solicitud —también retirada—, sin
	 * prórroga, o un procedimiento de profesorado.
	 */
	public function test_submitting_on_behalf_refuses_what_it_must() {
		$c     = $this->caso( false );
		$pedir = function ( array $campos, int $procedure = 0 ) use ( $c ): string {
			$this->assertNull( $this->taller( $c['reviewer'], ApplicationActionsPanel::OP_SUBMIT_FOR, $procedure > 0 ? $procedure : $c['procedure'], 0, $campos ) );
			return $this->rechazo_taller();
		};
		$hasta = array( ApplicationActionsPanel::FIELD_LATE => $this->dia( 7 ) );
		$this->assertStringContainsString( 'no está en el catálogo', $pedir( array( ApplicationActionsPanel::FIELD_CODE => 'X9999' ) + $hasta ) );
		$this->assertStringContainsString( 'inactivo', $pedir( array( ApplicationActionsPanel::FIELD_CODE => 'C0003' ) + $hasta ) );
		$this->assertStringContainsString( 'ya tiene una solicitud', $pedir( array( ApplicationActionsPanel::FIELD_CODE => 'C0001' ) + $hasta ) );
		$this->assertStringContainsString( 'fecha', $pedir( array( ApplicationActionsPanel::FIELD_CODE => 'C0002' ) ) );

		ApplicationOperations::withdraw( $c['application'], $c['head'], ApplicationOperations::BY_APPLICANT );
		$this->assertStringContainsString( 'restáurela', $pedir( array( ApplicationActionsPanel::FIELD_CODE => 'C0001' ) + $hasta ) );

		$docentes = $this->procedure( $c['reviewer'], array( $c['area'] ), array( ProcedureMetaKeys::AUDIENCE => ProcedureMetaKeys::AUDIENCE_TEACHERS ) );
		$this->assertStringContainsString( 'profesorado', $pedir( array( ApplicationActionsPanel::FIELD_CODE => 'C0002' ) + $hasta, $docentes ) );
		$this->assertSame(
			1,
			count(
				get_posts(
					array(
						'post_type'   => 'prc_application',
						'post_status' => array( 'publish', 'trash' ),
						'numberposts' => -1,
						'fields'      => 'ids',
					)
				)
			)
		);
	}

	/**
	 * El buscador del catálogo no pinta el catálogo entero.
	 */
	public function test_the_school_search_paints_only_what_matches() {
		$c    = $this->caso();
		$html = $this->panel( $c['reviewer'], $c['procedure'] );
		$this->assertStringContainsString( 'Presentar en nombre de un centro', $html );
		$this->assertStringNotContainsString( 'Centro Sur (C0002)', $html, 'sin buscar, ningún centro' );
		$html = $this->panel( $c['reviewer'], $c['procedure'], array( ApplicationActionsPanel::ARG_FIND => 'sur' ) );
		$this->assertStringContainsString( 'Centro Sur (C0002)', $html );
		$this->assertStringNotContainsString( 'Centro Norte (C0001)', $html );
		$html = $this->panel( $c['reviewer'], $c['procedure'], array( ApplicationActionsPanel::ARG_PICK => 'C0002' ) );
		$this->assertStringContainsString( 'value="C0002"', $html );
	}

	// ─── la nota interna ───────────────────────────────────────────────────

	/**
	 * La nota interna se guarda con la revisión y no sale nunca hacia fuera.
	 */
	public function test_the_internal_note_never_leaves_the_review() {
		$c = $this->caso(
			true,
			array(
				ProcedureMetaKeys::OBSERVABLE      => true,
				ProcedureMetaKeys::OBSERVER_EXPORT => true,
			)
		);
		$this->taller(
			$c['reviewer'],
			ProcedureEditor::OP_REVIEW,
			$c['procedure'],
			$c['application'],
			array(
				ProcedureEditor::FIELD_STATE => ApplicationMetaKeys::REVIEW_EXCLUDED,
				ProcedureEditor::FIELD_NOTE  => 'Falta la firma.',
				ApplicationActionsPanel::FIELD_INTERNAL_NOTE => self::INTERNA,
			)
		);
		$this->assertSame( self::INTERNA, get_post_meta( $c['application'], ApplicationMetaKeys::INTERNAL_NOTE, true ) );
		$this->assertContains( ApplicationHistory::INTERNAL_NOTE, $this->eventos( $c['application'] ) );
		$this->assertStringNotContainsString( self::INTERNA, wp_json_encode( ApplicationLog::all( $c['application'] ), JSON_UNESCAPED_UNICODE ), 'el historial dice que cambió, no qué dice' );

		// Quien revisa la ve.
		$this->assertStringContainsString( self::INTERNA, $this->panel( $c['reviewer'], $c['procedure'] ) );
		// Ni en la pantalla del centro, ni en «Mi centro», ni en ningún correo.
		$this->assertStringNotContainsString( self::INTERNA, $this->solicitud( $c['head'], $c['procedure'] ) );
		$this->acting_as( $c['head'] );
		$_GET = array();
		$this->assertStringNotContainsString( self::INTERNA, MyCentreView::html( MyCentre::model() ) );
		$this->assertNotSame( array(), $this->enviados, 'el cambio de revisión sí avisa' );
		$this->assertStringNotContainsString( self::INTERNA, wp_json_encode( $this->enviados, JSON_UNESCAPED_UNICODE ) );
		// Ni en la consulta, ni en su CSV.
		$consulta = $this->observer( array( 'Z0001' ) );
		$this->acting_as( $consulta );
		$this->assertStringNotContainsString( self::INTERNA, ObserveView::html( Observe::model( array( Observe::ARG_PROCEDURE => (string) $c['procedure'] ) ) ) );
		$this->assertStringNotContainsString( self::INTERNA, (string) Observe::csv( $consulta, $c['procedure'], array() ) );
		$this->assertNotContains( 'internal_note', ObserverScope::ALLOWED_COLUMNS );
		// Ni en el CSV de siempre ni con «Todas las columnas»; sí si se elige.
		$this->assertStringNotContainsString( self::INTERNA, ApplicationExport::csv( $c['procedure'] ) );
		$todas = ApplicationExport::sanitize_choice( $c['procedure'], array( 'all' => true ) );
		$this->assertStringNotContainsString( self::INTERNA, ApplicationExport::csv( $c['procedure'], $todas['columns'] ) );
		$this->assertStringContainsString( self::INTERNA, ApplicationExport::csv( $c['procedure'], array( 'code', 'internal_note' ) ) );
		$con = ApplicationExport::sanitize_choice(
			$c['procedure'],
			array(
				'all'     => true,
				'columns' => array( 'internal_note' ),
			)
		);
		$this->assertContains( 'internal_note', $con['columns'] );
		$this->assertStringContainsString( self::INTERNA, ApplicationExport::csv( $c['procedure'], $con['columns'] ) );
	}

	/**
	 * Hace que, la próxima vez que se cree una solicitud, otra petición
	 * «gane» y cree antes la gemela de ese centro.
	 *
	 * @param int    $procedure Procedure ID.
	 * @param int    $author    Author of the twin.
	 * @param string $code      School code.
	 * @return void
	 */
	private function gemela_en_medio( int $procedure, int $author, string $code ): void {
		$pendiente = true;
		add_filter(
			'wp_insert_post_data',
			function ( array $data ) use ( &$pendiente, $procedure, $author, $code ): array {
				if ( $pendiente && 'prc_application' === $data['post_type'] ) {
					$pendiente = false;
					$this->application( $procedure, $author, $code );
				}
				return $data;
			}
		);
	}

	/**
	 * Cuántas solicitudes publicadas tiene un centro en un procedimiento.
	 *
	 * @param int    $procedure Procedure ID.
	 * @param string $code      School code.
	 * @return int
	 */
	private function de_centro( int $procedure, string $code ): int {
		$n = 0;
		foreach ( Applications::for_procedure( $procedure ) as $post ) {
			$n += (int) ( get_post_meta( $post->ID, ApplicationMetaKeys::CENTRE_CODE, true ) === $code );
		}
		return $n;
	}

	/**
	 * Una por centro, también con dos envíos a la vez: si otra solicitud del
	 * mismo centro se crea entre la comprobación y la escritura, la nueva se
	 * borra y se dice por qué.
	 */
	public function test_a_twin_created_in_between_loses_the_race() {
		$c   = $this->caso();
		$sur = $this->school_head( 'C0002' );
		$this->gemela_en_medio( $c['procedure'], $sur, 'C0002' );
		$this->assertSame( 0, Applications::save( $c['procedure'], $sur, array( 'position' => ApplicationMetaKeys::POSITION_HEAD ) ) );
		$this->assertSame( Applications::DUPLICATE, Applications::why_not_saved( 'otro' ) );
		$this->assertSame( 1, $this->de_centro( $c['procedure'], 'C0002' ) );

		// Lo mismo en nombre de un centro.
		$this->gemela_en_medio( $c['procedure'], $this->school_head( 'C0003' ), 'C0003' );
		$this->assertSame( 0, ApplicationOperations::submit_for( $c['procedure'], $c['reviewer'], 'C0003', $this->dia( 3 ) ) );
		$this->assertSame( 1, $this->de_centro( $c['procedure'], 'C0003' ) );

		// Sin carrera, se guarda como siempre.
		$this->assertGreaterThan( 0, Applications::save( $c['procedure'], $this->school_head( 'C0001' ), array( 'position' => ApplicationMetaKeys::POSITION_HEAD ) ) );
	}

	/**
	 * Con prórroga, modificar una solicitud revisada la devuelve a
	 * «Presentada»: el formulario lo avisa y el historial lo dice, sin correo
	 * de revisión. En un procedimiento resuelto, también se avisa.
	 */
	public function test_editing_a_reviewed_application_with_an_extension_is_warned_and_logged() {
		$c = $this->caso( false );
		update_post_meta( $c['application'], ApplicationMetaKeys::REVIEW_STATE, ApplicationMetaKeys::REVIEW_ADMITTED );
		update_post_meta( $c['application'], ApplicationMetaKeys::LATE_UNTIL, $this->dia( 3 ) );
		$html = $this->panel( $c['reviewer'], $c['procedure'] );
		$this->assertStringContainsString( 'volverá a «Presentada» y habrá que revisarla de nuevo', $html );
		$this->assertStringNotContainsString( 'ya está resuelto', $html );

		$this->enviados = array();
		$this->assertGreaterThan( 0, Applications::save( $c['procedure'], $c['head'], array( 'position' => ApplicationMetaKeys::POSITION_HEAD ) ) );
		$this->assertSame( ApplicationMetaKeys::REVIEW_SUBMITTED, get_post_meta( $c['application'], ApplicationMetaKeys::REVIEW_STATE, true ) );
		$this->assertSame( array( ApplicationHistory::UPDATED, ApplicationHistory::REVIEWED ), $this->eventos( $c['application'] ) );
		$entrada = ApplicationLog::all( $c['application'] )[1];
		$this->assertSame( ApplicationMetaKeys::REVIEW_ADMITTED, $entrada['data']['previous'] );
		$this->assertSame( 'save', $entrada['data']['by'] );
		$this->assertSame( $c['head'], $entrada['user'] );
		foreach ( $this->enviados as $correo ) {
			$this->assertStringNotContainsString( 'Revisión de su solicitud', (string) $correo['subject'] );
		}
		$this->assertStringContainsString( 'al modificarla quien la presentó', implode( ' ', array_column( ApplicationHistory::entries( $c['application'] ), 'what' ) ) );

		update_post_meta( $c['procedure'], ProcedureMetaKeys::FINAL_LIST_URL, 'https://example.org/listado.pdf' );
		$this->assertStringContainsString( 'ya está resuelto', $this->panel( $c['reviewer'], $c['procedure'] ) );
	}

	/**
	 * La prórroga abre también la lista de personas que sigue el plazo de la
	 * solicitud; con ventana propia, manda la ventana propia.
	 */
	public function test_the_extension_opens_the_people_list_that_follows_the_application() {
		$spec = array(
			'enabled' => true,
			'roles'   => array(
				array(
					'key'   => 'r1',
					'label' => 'Docente',
				),
			),
		);
		$c    = $this->caso( false, array( ProcedureMetaKeys::PEOPLE_SPEC => $spec ) );
		$id   = ApplicationOperations::submit_for( $c['procedure'], $c['reviewer'], 'C0002', $this->dia( 5 ) );
		$sur  = $this->school_head( 'C0002' );
		$this->assertTrue( ProcedureAccess::can_edit_people( $sur, $id ), 'con prórroga, la lista sin fechas se abre' );
		$this->assertTrue(
			People::add(
				$id,
				$sur,
				array(
					'name'    => 'Ana',
					'surname' => 'Pérez',
					'dni'     => '12345678Z',
					'email'   => 'ana@example.org',
					'role'    => 'r1',
				)
			)['ok']
		);
		$this->assertFalse( ProcedureAccess::can_edit_people( $c['head'], $c['application'] ), 'sin prórroga, cerrada' );

		update_post_meta( $id, ApplicationMetaKeys::LATE_UNTIL, $this->dia( -1 ) );
		$this->assertFalse( ProcedureAccess::can_edit_people( $sur, $id ), 'vencida, cerrada' );

		update_post_meta( $id, ApplicationMetaKeys::LATE_UNTIL, $this->dia( 5 ) );
		update_post_meta(
			$c['procedure'],
			ProcedureMetaKeys::PEOPLE_SPEC,
			$spec + array(
				'opens_at'  => $this->dia( -9 ),
				'closes_at' => $this->dia( -2 ),
			)
		);
		$this->assertFalse( ProcedureAccess::can_edit_people( $sur, $id ), 'con ventana propia, manda la ventana propia' );
	}

	// ─── el historial ──────────────────────────────────────────────────────

	/**
	 * Cada actuación queda una vez, y una revisión que no cambia nada, ninguna.
	 */
	public function test_the_history_records_each_action_once() {
		$c  = $this->caso();
		$id = Applications::save( $c['procedure'], $c['head'], array( 'position' => ApplicationMetaKeys::POSITION_HEAD ) );
		$this->assertSame( $c['application'], $id );
		Applications::review( $id, ApplicationMetaKeys::REVIEW_ADMITTED, '' );
		Applications::review( $id, ApplicationMetaKeys::REVIEW_ADMITTED, '' );
		Applications::review( $id, ApplicationMetaKeys::REVIEW_AMEND, 'Corrija la fecha.' );
		Applications::review( $id, ApplicationMetaKeys::REVIEW_AMEND, 'Corrija la fecha y la firma.' );
		do_action( 'prc_delivery_saved', $id, 'q1', $c['head'] );
		ApplicationOperations::extend( $id, $c['reviewer'], $this->dia( 3 ) );
		ApplicationOperations::extend( $id, $c['reviewer'], $this->dia( 3 ) );
		ApplicationOperations::set_internal_note( $id, $c['reviewer'], 'Uno' );
		ApplicationOperations::set_internal_note( $id, $c['reviewer'], 'Uno' );
		$this->assertSame(
			array(
				ApplicationHistory::UPDATED,
				ApplicationHistory::REVIEWED,
				ApplicationHistory::REVIEWED,
				ApplicationHistory::REVIEWED,
				ApplicationHistory::DELIVERED,
				ApplicationHistory::EXTENDED,
				ApplicationHistory::INTERNAL_NOTE,
			),
			$this->eventos( $id )
		);
		$revision = ApplicationLog::all( $id )[3]['data'];
		$this->assertTrue( $revision['note_changed'] );
		$this->assertArrayNotHasKey( 'note', $revision, 'sin volcar la nota' );
	}

	/**
	 * El historial lo ve quien revisa, al abrir esa solicitud; ni quien
	 * solicita ni quien consulta.
	 */
	public function test_only_the_reviewer_sees_the_history() {
		$c = $this->caso( true, array( ProcedureMetaKeys::OBSERVABLE => true ) );
		Applications::review( $c['application'], ApplicationMetaKeys::REVIEW_ADMITTED, '' );
		$html = $this->panel( $c['reviewer'], $c['procedure'] );
		$this->assertStringContainsString( 'Ver el historial', $html );
		$this->assertStringNotContainsString( 'Revisión: Admitida', $html, 'no se lee al pintar la tabla' );
		$html = $this->panel( $c['reviewer'], $c['procedure'], array( PeopleSpecPanel::ARG_HISTORY => (string) $c['application'] ) );
		$this->assertStringContainsString( 'Revisión: Admitida', $html );

		$this->assertStringNotContainsString( 'Revisión: Admitida', $this->solicitud( $c['head'], $c['procedure'] ) );
		$this->assertStringNotContainsString( 'Ver el historial', $this->solicitud( $c['head'], $c['procedure'] ) );
		$consulta = $this->observer( array( 'Z0001' ) );
		$this->acting_as( $consulta );
		$_GET = array( PeopleSpecPanel::ARG_HISTORY => (string) $c['application'] );
		$html = ObserveView::html( Observe::model( array( Observe::ARG_PROCEDURE => (string) $c['procedure'] ) ) );
		$this->assertStringContainsString( 'Centro Norte', $html );
		$this->assertStringNotContainsString( 'Revisión: Admitida', $html );
	}

	// ─── lo retirado no sale ───────────────────────────────────────────────

	/**
	 * Una retirada no sale en la consulta, ni en las exportaciones, ni en el
	 * fichero de usuarios, ni en los contadores de la tabla.
	 */
	public function test_a_withdrawn_application_is_out_of_every_list() {
		$c = $this->caso(
			true,
			array(
				ProcedureMetaKeys::OBSERVABLE      => true,
				ProcedureMetaKeys::OBSERVER_EXPORT => true,
			)
		);
		update_post_meta(
			$c['application'],
			ApplicationMetaKeys::COORDINATOR,
			array(
				array(
					'name'    => 'Ana',
					'surname' => 'Retirada',
					'dni'     => '12345678Z',
					'email'   => 'ana.retirada@example.org',
				),
			)
		);
		$this->assertStringContainsString( 'ana.retirada', ApplicationExport::moodle( $c['procedure'], array( 'who' => array( 'coordinators' ) ) ) );
		ApplicationOperations::withdraw( $c['application'], $c['head'], ApplicationOperations::BY_APPLICANT );

		$this->assertStringNotContainsString( 'C0001', ApplicationExport::csv( $c['procedure'] ) );
		$this->assertStringNotContainsString( 'ana.retirada', ApplicationExport::moodle( $c['procedure'], array( 'who' => array( 'coordinators' ) ) ) );
		$consulta = $this->observer( array( 'Z0001' ) );
		$this->acting_as( $consulta );
		$this->assertStringNotContainsString( 'Centro Norte', ObserveView::html( Observe::model( array( Observe::ARG_PROCEDURE => (string) $c['procedure'] ) ) ) );
		$this->assertStringNotContainsString( 'C0001', (string) Observe::csv( $consulta, $c['procedure'], array() ) );
		$this->assertFalse( ProcedureAccess::can_view_application( $consulta, $c['application'] ) );
		$this->assertSame( 0, Applications::count( $c['procedure'] ) );
		$this->assertSame( 1, ApplicationOperations::withdrawn_count( $c['procedure'] ) );
		$html = $this->panel( $c['reviewer'], $c['procedure'] );
		$this->assertStringContainsString( 'Todavía no hay solicitudes', $html );
		$this->assertStringContainsString( 'Retiradas (1)', $html );
	}
}
