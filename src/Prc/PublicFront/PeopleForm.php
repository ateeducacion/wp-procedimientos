<?php
/**
 * The «Personas» section of the application screen, and what it does when submitted.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\Access\ProcedureAccess;
use Prc\Domain\ApplicationPeople;
use Prc\Domain\DateRange;
use Prc\Domain\PeopleSpec;
use Prc\Meta\ApplicationMetaKeys;
use Prc\PostType\ApplicationPostType;

/**
 * La lista de personas en la pantalla de la solicitud (ADR-0041).
 *
 * Lee la petición y decide; no pinta nada, que es de {@see View\PeopleView}.
 * Va aparte de {@see ApplyForm} porque tiene su propia ventana: con el plazo
 * de solicitud cerrado el formulario de la solicitud se apaga, y la lista de
 * personas puede seguir abierta.
 *
 * Tres operaciones, cada una un POST con su nonce sobre **una** fila: dar de
 * alta, cambiar —que es también sustituir: la fila conserva su `id`— y dar
 * de baja, que pide confirmación con `data-prc-confirm`. El cambio y la baja
 * llevan la huella de la fila tal como se pintó; quién y cuándo lo vuelve a
 * comprobar {@see People}, que es quien escribe.
 */
final class PeopleForm {

	public const FIELD_OP = 'prc_people_op';

	public const OP_ADD    = 'add';
	public const OP_CHANGE = 'change';
	public const OP_REMOVE = 'remove';

	public const FIELD_APPLICATION = 'prc_people_application';
	public const FIELD_ROW         = 'prc_people_row';
	public const FIELD_PRINT       = 'prc_people_print';
	public const FIELD_PERSON      = 'prc_person';

	public const NONCE_ACTION = 'prc_people';
	public const NONCE_FIELD  = '_prc_people_nonce';

	/**
	 * Id of the section, and anchor the screen comes back to.
	 */
	public const ANCHOR = 'prc-personas';

	/**
	 * Which form a refusal belongs to when it is the one to add.
	 */
	public const NEW_ROW = 'nueva';

	/**
	 * What each notice code in the URL says.
	 *
	 * @var array<string, string>
	 */
	private const DONE = array(
		'persona-alta'   => 'Persona dada de alta en la lista.',
		'persona-cambio' => 'Cambios guardados en la persona.',
		'persona-baja'   => 'Persona dada de baja de la lista.',
	);

	/**
	 * The notice code of each operation.
	 *
	 * @var array<string, string>
	 */
	private const NOTICE = array(
		self::OP_ADD    => 'persona-alta',
		self::OP_CHANGE => 'persona-cambio',
		self::OP_REMOVE => 'persona-baja',
	);

	/**
	 * Why the last submit did not go through.
	 *
	 * @var string
	 */
	private static $rejected = '';

	/**
	 * The row whose form was refused: its id, or {@see NEW_ROW}.
	 *
	 * @var string
	 */
	private static $rejected_row = '';

	/**
	 * Register the POST handler.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'handle' ), 20 );
	}

	/**
	 * URL of the «Personas» section of one application.
	 *
	 * @param int                  $application_id Application post ID.
	 * @param array<string, mixed> $args           Extra query arguments.
	 * @return string Empty when there is no page for the screen.
	 */
	public static function url( int $application_id, array $args = array() ): string {
		$base = ApplyForm::url_for_code(
			(int) wp_get_post_parent_id( $application_id ),
			(string) get_post_meta( $application_id, ApplicationMetaKeys::CENTRE_CODE, true ),
			$args
		);
		return '' === $base ? '' : $base . '#' . self::ANCHOR;
	}

	/**
	 * Take a submit, when it is ours.
	 *
	 * @return void
	 */
	public static function handle(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- se comprueba justo debajo.
		$op    = isset( $_POST[ self::FIELD_OP ] ) && is_string( $_POST[ self::FIELD_OP ] ) ? sanitize_key( wp_unslash( $_POST[ self::FIELD_OP ] ) ) : '';
		$nonce = isset( $_POST[ self::NONCE_FIELD ] ) && is_string( $_POST[ self::NONCE_FIELD ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		if ( ! isset( self::NOTICE[ $op ] ) || ! is_user_logged_in() ) {
			return;
		}
		$fila               = self::field( self::FIELD_ROW );
		self::$rejected_row = self::OP_ADD === $op ? self::NEW_ROW : $fila;
		if ( false === wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			self::$rejected = 'El formulario estuvo abierto demasiado tiempo y el envío caducó. Vuelva a enviarlo.';
			return;
		}

		$solicitud = absint( self::field( self::FIELD_APPLICATION ) );
		$user_id   = get_current_user_id();
		$huella    = self::field( self::FIELD_PRINT );
		if ( self::OP_ADD === $op ) {
			$res = People::add( $solicitud, $user_id, self::person() );
		} elseif ( self::OP_CHANGE === $op ) {
			$res = People::change( $solicitud, $user_id, $fila, $huella, self::person() );
		} else {
			$res = People::remove( $solicitud, $user_id, $fila, $huella );
		}
		if ( ! $res['ok'] ) {
			self::$rejected = $res['error'];
			return;
		}
		Shell::leave( self::url( $solicitud, array( ApplyForm::ARG_NOTICE => self::NOTICE[ $op ] ) ) );
	}

	/**
	 * The person the form sent, its texts cleaned.
	 *
	 * @return array<string, string>
	 */
	private static function person(): array {
		if ( ! self::nonce_ok() ) {
			return array();
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce_ok() lo comprobó justo arriba; cada campo se sanea justo abajo.
		$crudo = isset( $_POST[ self::FIELD_PERSON ] ) ? wp_unslash( $_POST[ self::FIELD_PERSON ] ) : array();
		$out   = array();
		foreach ( array_diff( ApplicationPeople::FIELDS, array( 'id' ) ) as $campo ) {
			$valor         = is_array( $crudo ) ? ( $crudo[ $campo ] ?? '' ) : '';
			$out[ $campo ] = is_string( $valor ) ? sanitize_text_field( $valor ) : '';
		}
		return $out;
	}

	/**
	 * One submitted field, trimmed and sanitised.
	 *
	 * @param string $nombre Field name.
	 * @return string
	 */
	private static function field( string $nombre ): string {
		if ( ! self::nonce_ok() ) {
			return '';
		}
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce_ok() lo comprobó justo arriba.
		return isset( $_POST[ $nombre ] ) && is_string( $_POST[ $nombre ] )
			? trim( sanitize_text_field( wp_unslash( $_POST[ $nombre ] ) ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Whether the POST carries our nonce.
	 *
	 * @return bool
	 */
	private static function nonce_ok(): bool {
		return isset( $_POST[ self::NONCE_FIELD ] ) && is_string( $_POST[ self::NONCE_FIELD ] )
			&& false !== wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION );
	}

	/**
	 * Everything the section decides before painting, from the model of the
	 * application screen.
	 *
	 * @param array<string, mixed> $apply What ApplyForm::model() returned.
	 * @return array<string, mixed> `show` false when there is no section to paint.
	 */
	public static function model( array $apply ): array {
		$procedure_id = (int) ( $apply['procedure_id'] ?? 0 );
		if ( $procedure_id <= 0 || '' !== (string) ( $apply['aviso'] ?? '' ) || ! People::enabled( $procedure_id )
			|| ApplicationMetaKeys::TYPE_TEACHER === (string) ( $apply['applicant_type'] ?? '' ) ) {
			return array( 'show' => false );
		}
		$spec      = People::spec( $procedure_id );
		$solicitud = (int) ( $apply['application']['id'] ?? 0 );
		$user_id   = get_current_user_id();
		$filas     = $solicitud > 0 ? self::rows( $spec, People::of( $solicitud ) ) : array();
		$puede     = $solicitud > 0 && ProcedureAccess::can_edit_people( $user_id, $solicitud );
		return array(
			'show'           => true,
			'application_id' => $solicitud,
			'roles'          => array_column( $spec['roles'], 'label', 'key' ),
			'groups'         => array_column( $spec['groups'], 'label', 'key' ),
			'min'            => (int) $spec['min'],
			'max'            => (int) $spec['max'],
			'window'         => self::window( $spec ),
			'rows'           => $filas,
			'complete'       => ApplicationPeople::is_complete( $spec, $filas ),
			'can_edit'       => $puede,
			'why'            => self::why( $solicitud, $user_id, $puede ),
			'flash'          => self::flash(),
			'typed'          => '' !== self::$rejected ? self::person() : array(),
			'typed_row'      => '' !== self::$rejected ? self::$rejected_row : '',
		);
	}

	/**
	 * The people of an application as the screens paint them.
	 *
	 * @param array<string, mixed>              $spec Specification.
	 * @param array<int, array<string, string>> $rows Stored rows.
	 * @return array<int, array<string, string>> Each row with `role_label`, `group_label` and `print`.
	 */
	public static function rows( array $spec, array $rows ): array {
		$out = array();
		foreach ( $rows as $fila ) {
			$out[] = $fila + array(
				'role_label'  => PeopleSpec::label( $spec, 'roles', $fila['role'] ),
				'group_label' => PeopleSpec::label( $spec, 'groups', $fila['group'] ),
				'print'       => ApplicationPeople::fingerprint( $fila ),
			);
		}
		return $out;
	}

	/**
	 * The window of the list, in words.
	 *
	 * @param array<string, mixed> $spec Specification.
	 * @return string
	 */
	private static function window( array $spec ): string {
		$fechas = DateRange::of( (string) $spec['opens_at'], (string) $spec['closes_at'] );
		if ( '' !== (string) $spec['opens_at'] && '' !== (string) $spec['closes_at'] && '' !== $fechas ) {
			return 'Plazo para gestionar la lista: ' . lcfirst( $fechas ) . '.';
		}
		if ( '' === (string) $spec['opens_at'] && '' === (string) $spec['closes_at'] ) {
			return 'La lista se gestiona mientras se pueda modificar la solicitud.';
		}
		return '' !== (string) $spec['opens_at']
			? 'La lista se puede gestionar desde el ' . self::day( (string) $spec['opens_at'] ) . '.'
			: 'La lista se puede gestionar hasta el ' . self::day( (string) $spec['closes_at'] ) . '.';
	}

	/**
	 * Why the list cannot be written now, in words.
	 *
	 * @param int  $application_id Application post ID; 0 when there is none yet.
	 * @param int  $user_id        Who is looking.
	 * @param bool $puede          Whether they can.
	 * @return string
	 */
	private static function why( int $application_id, int $user_id, bool $puede ): string {
		if ( $application_id <= 0 ) {
			return 'Cuando presente la solicitud podrá dar de alta aquí a las personas que participan.';
		}
		return $puede ? '' : ProcedureAccess::why_not_people( $user_id, $application_id );
	}

	/**
	 * The notice of this request: what the URL says was done, or why the submit was refused.
	 *
	 * @return array{tipo:string, texto:string}
	 */
	private static function flash(): array {
		if ( '' !== self::$rejected ) {
			return array(
				'tipo'  => 'error',
				'texto' => self::$rejected,
			);
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo elige qué aviso fijo se pinta.
		$hecho = sanitize_key( wp_unslash( (string) ( $_GET[ ApplyForm::ARG_NOTICE ] ?? '' ) ) );
		return array(
			'tipo'  => isset( self::DONE[ $hecho ] ) ? 'ok' : '',
			'texto' => (string) ( self::DONE[ $hecho ] ?? '' ),
		);
	}

	/**
	 * What the workshop shows of one application: its people and its history,
	 * read-only.
	 *
	 * El historial enseña el DNI tapado salvo las cuatro últimas cifras: quien
	 * revisa lo ve entero en la lista, y en el historial basta para saber de
	 * quién se habla.
	 *
	 * @param int $application_id Application post ID.
	 * @return array<string, mixed> `show` false when the procedure has no list.
	 */
	public static function review( int $application_id ): array {
		$procedure_id = (int) wp_get_post_parent_id( $application_id );
		if ( ApplicationPostType::POST_TYPE !== get_post_type( $application_id ) || ! People::enabled( $procedure_id ) ) {
			return array( 'show' => false );
		}
		$spec  = People::spec( $procedure_id );
		$filas = self::rows( $spec, People::of( $application_id ) );
		return array(
			'show'     => true,
			'rows'     => $filas,
			'min'      => (int) $spec['min'],
			'complete' => ApplicationPeople::is_complete( $spec, $filas ),
			'log'      => self::history( $spec, $application_id ),
		);
	}

	/**
	 * The history of the list of an application, newest first, in words.
	 *
	 * @param array<string, mixed> $spec           Specification.
	 * @param int                  $application_id Application post ID.
	 * @return array<int, array{when:string, who:string, what:string, before:string, after:string}>
	 */
	private static function history( array $spec, int $application_id ): array {
		$que = array(
			'person_added'   => 'Alta',
			'person_changed' => 'Cambio',
			'person_removed' => 'Baja',
		);
		$out = array();
		foreach ( array_reverse( ApplicationLog::all( $application_id ) ) as $entrada ) {
			if ( ! isset( $que[ $entrada['event'] ] ) ) {
				continue;
			}
			$cuenta = $entrada['user'] > 0 ? get_userdata( $entrada['user'] ) : false;
			$out[]  = array(
				'when'   => '' !== $entrada['at'] ? (string) wp_date( 'd-m-Y H:i', (int) strtotime( $entrada['at'] ) ) : '',
				'who'    => false !== $cuenta ? (string) $cuenta->display_name : '(cuenta que ya no existe)',
				'what'   => $que[ $entrada['event'] ],
				'before' => self::line( $spec, (array) ( $entrada['data']['before'] ?? array() ) ),
				'after'  => self::line( $spec, (array) ( $entrada['data']['after'] ?? array() ) ),
			);
		}
		return $out;
	}

	/**
	 * One person of the history in a line, with the DNI covered.
	 *
	 * @param array<string, mixed> $spec Specification.
	 * @param array<string, mixed> $row  Row.
	 * @return string Empty for no row.
	 */
	private static function line( array $spec, array $row ): string {
		if ( array() === $row ) {
			return '';
		}
		$dni = ApplicationPeople::masked_dni( (string) ( $row['dni'] ?? '' ) );
		return ApplicationPeople::describe( $spec, $row ) . ( '' !== $dni ? ' · DNI ' . $dni : '' );
	}

	/**
	 * A Y-m-d day as d-m-Y.
	 *
	 * @param string $ymd Day.
	 * @return string
	 */
	private static function day( string $ymd ): string {
		return implode( '-', array_reverse( explode( '-', $ymd ) ) );
	}
}
