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
use Prc\Domain\ProcedureState;
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

	/**
	 * Prefix of the nonce action: the operation and the application follow
	 * ({@see nonce_action()}).
	 */
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
	 * Notices that say nothing was done, and why.
	 *
	 * @var array<string, string>
	 */
	private const NEUTRAL = array(
		'persona-sin-cambios' => 'Esa persona ya tenía esos datos: no había nada que cambiar.',
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
	 * Whether the last refusal was because the row changed since it was painted.
	 *
	 * @var bool
	 */
	private static $stale = false;

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
	 * Nonce action of one operation on one application: a nonce of one
	 * application does not serve for another.
	 *
	 * @param string $op             Operation.
	 * @param int    $application_id Application post ID.
	 * @return string
	 */
	public static function nonce_action( string $op, int $application_id ): string {
		return self::NONCE_ACTION . '_' . $op . '_' . $application_id;
	}

	/**
	 * Take a submit, when it is ours.
	 *
	 * @return void
	 */
	public static function handle(): void {
		$op = self::posted_op();
		if ( ! isset( self::NOTICE[ $op ] ) || ! is_user_logged_in() ) {
			return;
		}
		$fila               = self::field( self::FIELD_ROW );
		self::$rejected_row = self::OP_ADD === $op ? self::NEW_ROW : $fila;
		if ( ! self::nonce_ok() ) {
			self::$rejected = 'El formulario estuvo abierto demasiado tiempo y el envío caducó. Vuelva a enviarlo.';
			return;
		}

		$solicitud = self::posted_application();
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
			self::$stale    = $res['stale'];
			return;
		}
		Shell::leave( self::url( $solicitud, array( ApplyForm::ARG_NOTICE => $res['changed'] ? self::NOTICE[ $op ] : 'persona-sin-cambios' ) ) );
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
			&& false !== wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::nonce_action( self::posted_op(), self::posted_application() ) );
	}

	/**
	 * The operation the POST asks for, before checking its nonce: the nonce
	 * action is made from it.
	 *
	 * @return string
	 */
	private static function posted_op(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- solo compone la acción del nonce, que se comprueba con ella.
		return isset( $_POST[ self::FIELD_OP ] ) && is_string( $_POST[ self::FIELD_OP ] ) ? sanitize_key( wp_unslash( $_POST[ self::FIELD_OP ] ) ) : '';
	}

	/**
	 * The application the POST is about, before checking its nonce.
	 *
	 * @return int
	 */
	private static function posted_application(): int {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- solo compone la acción del nonce, que se comprueba con ella.
		return isset( $_POST[ self::FIELD_APPLICATION ] ) && is_string( $_POST[ self::FIELD_APPLICATION ] ) ? absint( wp_unslash( $_POST[ self::FIELD_APPLICATION ] ) ) : 0;
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
		$despues   = $solicitud > 0 || empty( $apply['can_submit'] ) ? '' : self::after_submit( $spec, $procedure_id );
		// El último día solo se dice cuando la lista se puede escribir, o se
		// podrá nada más presentar: con ella cerrada, lo que se dice es el motivo.
		$abierta = $solicitud > 0 ? $puede : '' === $despues;
		return array(
			'show'           => true,
			'application_id' => $solicitud,
			'roles'          => array_column( $spec['roles'], 'label', 'key' ),
			'groups'         => array_column( $spec['groups'], 'label', 'key' ),
			'min'            => (int) $spec['min'],
			'max'            => (int) $spec['max'],
			'window'         => self::window( $spec ),
			// El último día, dicho siempre: es lo que se pregunta después de presentar.
			'until'          => $abierta ? self::day( PeopleSpec::until( $spec, (string) ( $apply['closes_at'] ?? '' ), ProcedureState::today() ) ) : '',
			'after_submit'   => $despues,
			'rows'           => $filas,
			'complete'       => ApplicationPeople::is_complete( $spec, $filas ),
			'can_edit'       => $puede,
			'why'            => '' !== $despues ? $despues : self::why( $solicitud, $user_id, $puede ),
			'flash'          => self::flash(),
			// Rechazado por la huella, el formulario de esa fila se repinta con
			// lo guardado ahora y su huella nueva, no con lo tecleado: así un
			// segundo envío no pisa a ciegas el cambio de la otra cuenta.
			'typed'          => '' !== self::$rejected && ! self::$stale ? self::person() : array(),
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
	 * Why the list will still be closed right after submitting, in words.
	 *
	 * Lo que diría {@see ProcedureAccess::why_not_people()} de la solicitud
	 * recién presentada: «solo admitidas», una ventana propia que todavía
	 * no se ha abierto o que ya terminó, o un procedimiento histórico. Sin
	 * eso, el anuncio de antes de presentar promete lo que la lista rechaza.
	 *
	 * @param array<string, mixed> $spec         Specification.
	 * @param int                  $procedure_id Procedure post ID.
	 * @return string Empty when the list opens as soon as it is submitted.
	 */
	private static function after_submit( array $spec, int $procedure_id ): string {
		return PeopleSpec::why_closed(
			$spec,
			array(
				'today'    => ProcedureState::today(),
				'archived' => ProcedureAccess::is_archived( $procedure_id ),
				'review'   => ApplicationMetaKeys::REVIEW_SUBMITTED,
				'state'    => ProcedureState::of_post( $procedure_id ),
			)
		);
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
		if ( isset( self::NEUTRAL[ $hecho ] ) ) {
			return array(
				'tipo'  => 'aviso',
				'texto' => self::NEUTRAL[ $hecho ],
			);
		}
		return array(
			'tipo'  => isset( self::DONE[ $hecho ] ) ? 'ok' : '',
			'texto' => (string) ( self::DONE[ $hecho ] ?? '' ),
		);
	}

	/**
	 * What the workshop shows of one application: its people, read-only.
	 *
	 * Su historial ya no va aquí: es el de la solicitud entera
	 * ({@see ApplicationHistory}), que el panel lee solo para la que se abre.
	 * Los dos argumentos de detrás se quedan por compatibilidad y no cambian
	 * nada.
	 *
	 * @param int                   $application_id Application post ID.
	 * @param bool                  $history        Unused.
	 * @param array<string, string> $args           Unused.
	 * @return array<string, mixed> `show` false when the procedure has no list.
	 */
	public static function review( int $application_id, bool $history = false, array $args = array() ): array {
		unset( $history, $args );
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
		);
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
