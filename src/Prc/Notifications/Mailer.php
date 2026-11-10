<?php
/**
 * The single mail exit of the application and the events it listens to.
 *
 * @package Prc
 */

namespace Prc\Notifications;

use Prc\Access\CentreScope;
use Prc\Access\ProcedureAccess;
use Prc\Domain\ProcedureQuestions;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ApplicationMetaRegistration;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ApplicationPostType;
use Prc\PostType\ProcedurePostType;
use Prc\PublicFront\ApplicationFiles;
use Prc\PublicFront\Applications;
use Prc\PublicFront\ApplyForm;
use Prc\PublicFront\ProcedureEditor;
use Prc\PublicFront\Shell;

/**
 * Los correos del aplicativo: una sola salida y los eventos que la usan.
 *
 * Las pantallas no mandan correo: emiten una acción de WordPress
 * (`prc_procedure_created`, `prc_application_saved`,
 * `prc_application_reviewed`, `prc_delivery_saved`) y este módulo la escucha
 * desde {@see register()}. Así el correo no sabe de pantallas ni las pantallas
 * de correo, y quien despliegue puede escuchar las mismas acciones para otra
 * cosa (ADR-0038).
 *
 * **Todo correo sale por {@see mail()}**, en texto plano y pasando por el
 * filtro `prc_mail`. Un fallo al enviar —servidor caído, un filtro de otro que
 * revienta— devuelve `false` y nada más: **nunca** impide guardar una
 * solicitud ni cambia el aviso de éxito de la pantalla.
 *
 * El remitente, la respuesta y la dirección de copia **son configuración, no
 * código** (ADR-0009): opciones vacías por defecto que se ponen en los
 * ajustes del aplicativo. Con el remitente vacío no se toca el de WordPress.
 */
final class Mailer {

	public const EVENT_APPLICATION_SUBMITTED = 'application_submitted';
	public const EVENT_APPLICATION_UPDATED   = 'application_updated';
	public const EVENT_APPLICATION_RECEIVED  = 'application_received';
	public const EVENT_REVIEW_CHANGED        = 'review_changed';
	public const EVENT_DELIVERY_RECEIVED     = 'delivery_received';
	public const EVENT_PROCEDURE_CREATED     = 'procedure_created';

	/**
	 * Name the mails are sent as. Empty: WordPress decides.
	 */
	public const OPTION_FROM_NAME = 'prc_mail_from_name';

	/**
	 * Address the mails are sent from. Empty: WordPress decides.
	 */
	public const OPTION_FROM = 'prc_mail_from';

	/**
	 * Address the replies go to. Empty: no Reply-To.
	 */
	public const OPTION_REPLY_TO = 'prc_mail_reply_to';

	/**
	 * Address that gets a copy of every `procedure_created` and `application_received`.
	 */
	public const OPTION_COPY = 'prc_mail_copy';

	/**
	 * Free paragraph appended to the `procedure_created` mail.
	 */
	public const OPTION_CREATED_EXTRA = 'prc_mail_created_extra';

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'prc_procedure_created', array( self::class, 'on_procedure_created' ) );
		add_action( 'prc_application_saved', array( self::class, 'on_application_saved' ), 10, 2 );
		add_action( 'prc_application_reviewed', array( self::class, 'on_application_reviewed' ), 10, 3 );
		add_action( 'prc_delivery_saved', array( self::class, 'on_delivery_saved' ), 10, 2 );
	}

	/**
	 * Every event the application sends mail for.
	 *
	 * @return string[]
	 */
	public static function events(): array {
		return array(
			self::EVENT_APPLICATION_SUBMITTED,
			self::EVENT_APPLICATION_UPDATED,
			self::EVENT_APPLICATION_RECEIVED,
			self::EVENT_REVIEW_CHANGED,
			self::EVENT_DELIVERY_RECEIVED,
			self::EVENT_PROCEDURE_CREATED,
		);
	}

	/**
	 * Every option of the mail, in the order the settings paint them.
	 *
	 * @return string[]
	 */
	public static function options(): array {
		return array(
			self::OPTION_FROM_NAME,
			self::OPTION_FROM,
			self::OPTION_REPLY_TO,
			self::OPTION_COPY,
			self::OPTION_CREATED_EXTRA,
		);
	}

	// ─── la salida única ───────────────────────────────────────────────────

	/**
	 * Send one mail. The only place of the application that calls wp_mail().
	 *
	 * Con `procedure_id` en el contexto, un evento apagado en ese
	 * procedimiento (`prc_notify`) no sale. El filtro `prc_mail` recibe
	 * `to`, `subject`, `message` y `headers`, y devolver `false` lo descarta.
	 *
	 * @param string               $event   One of {@see events()}.
	 * @param string               $to      Recipient address.
	 * @param string               $subject Subject.
	 * @param string               $message Plain-text body.
	 * @param array<string, mixed> $ctx     Context; `procedure_id` is read here.
	 * @return bool Whether wp_mail() accepted it.
	 */
	public static function mail( string $event, string $to, string $subject, string $message, array $ctx = array() ): bool {
		$to = self::sanitize_address( $to );
		if ( '' === $to || '' === trim( $message ) ) {
			return false;
		}
		$procedure_id = isset( $ctx['procedure_id'] ) ? (int) $ctx['procedure_id'] : 0;
		if ( $procedure_id > 0 && ! self::enabled( $procedure_id, $event ) ) {
			return false;
		}

		try {
			/**
			 * Filter one mail of the application before it goes out.
			 *
			 * @param array{to:string, subject:string, message:string, headers:string[]}|false $correo The mail; false discards it.
			 * @param string               $event Event.
			 * @param array<string, mixed> $ctx   Context it was built from.
			 */
			$correo = apply_filters(
				'prc_mail',
				array(
					'to'      => $to,
					'subject' => $subject,
					'message' => $message,
					'headers' => self::headers(),
				),
				$event,
				$ctx
			);
			if ( ! is_array( $correo ) ) {
				return false;
			}
			return (bool) wp_mail(
				(string) ( $correo['to'] ?? '' ),
				(string) ( $correo['subject'] ?? '' ),
				(string) ( $correo['message'] ?? '' ),
				(array) ( $correo['headers'] ?? array() )
			);
		} catch ( \Throwable $e ) {
			// El correo es un aviso, no parte de la solicitud: si algo revienta
			// aquí, la solicitud ya está guardada y así se queda.
			unset( $e );
			return false;
		}
	}

	/**
	 * Send one event to several addresses: each one once, whatever its case.
	 *
	 * @param string               $event     One of {@see events()}.
	 * @param string[]             $addresses Recipients; invalid ones are dropped.
	 * @param array<string, mixed> $ctx       Context for {@see Messages::render()}.
	 * @return int How many went out.
	 */
	public static function send( string $event, array $addresses, array $ctx ): int {
		$correo = Messages::render( $event, $ctx );
		if ( '' === $correo['message'] ) {
			return 0;
		}
		$hechos = 0;
		foreach ( self::unique( $addresses ) as $to ) {
			if ( self::mail( $event, $to, $correo['subject'], $correo['message'], $ctx ) ) {
				++$hechos;
			}
		}
		return $hechos;
	}

	/**
	 * Whether a procedure leaves this event switched on.
	 *
	 * @param int    $procedure_id Procedure ID.
	 * @param string $event        Event.
	 * @return bool
	 */
	public static function enabled( int $procedure_id, string $event ): bool {
		$apagados = get_post_meta( $procedure_id, ProcedureMetaKeys::NOTIFY, true );
		return ! is_array( $apagados ) || ! in_array( $event, $apagados, true );
	}

	/**
	 * A valid address in lower case, or ''.
	 *
	 * También es el saneado de las opciones de dirección de los ajustes.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_address( $value ): string {
		$email = is_scalar( $value ) ? strtolower( trim( (string) $value ) ) : '';
		return is_email( $email ) ? $email : '';
	}

	/**
	 * The extra headers: plain text, and the sender only when configured.
	 *
	 * @return string[]
	 */
	private static function headers(): array {
		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
		$from    = self::sanitize_address( get_option( self::OPTION_FROM, '' ) );
		if ( '' !== $from ) {
			// Ni comillas, ni ángulos, ni saltos: el nombre no puede cerrar la
			// cabecera y abrir otra.
			$nombre    = trim( (string) preg_replace( '/[\r\n"<>]+/', ' ', (string) get_option( self::OPTION_FROM_NAME, '' ) ) );
			$headers[] = 'From: ' . ( '' !== $nombre ? $nombre . ' <' . $from . '>' : $from );
		}
		$reply = self::sanitize_address( get_option( self::OPTION_REPLY_TO, '' ) );
		if ( '' !== $reply ) {
			$headers[] = 'Reply-To: ' . $reply;
		}
		return $headers;
	}

	/**
	 * Valid addresses, once each, case-insensitively.
	 *
	 * @param array<int, mixed> $addresses Raw addresses.
	 * @return string[]
	 */
	private static function unique( array $addresses ): array {
		$out = array();
		foreach ( $addresses as $address ) {
			$email = self::sanitize_address( $address );
			if ( '' !== $email ) {
				$out[ $email ] = $email;
			}
		}
		return array_values( $out );
	}

	// ─── los eventos ───────────────────────────────────────────────────────

	/**
	 * A procedure has just been created as a draft.
	 *
	 * Sale una vez: solo lo emite el alta del taller, no guardarlo después.
	 *
	 * @param int $procedure_id Procedure ID.
	 * @return void
	 */
	public static function on_procedure_created( $procedure_id ): void {
		self::safely(
			static function () use ( $procedure_id ): void {
				$procedure_id = (int) $procedure_id;
				if ( ProcedurePostType::POST_TYPE !== get_post_type( $procedure_id ) ) {
					return;
				}
				$destinos = self::contacts( $procedure_id );
				foreach ( ProcedureAccess::area_managers( $procedure_id ) as $user_id ) {
					$destinos[] = self::email_of( $user_id );
				}
				$destinos[] = (string) get_option( self::OPTION_COPY, '' );

				self::send(
					self::EVENT_PROCEDURE_CREATED,
					$destinos,
					array(
						'procedure_id'  => $procedure_id,
						'procedure'     => self::title( $procedure_id ),
						'missing'       => self::missing( $procedure_id ),
						'editor_url'    => ProcedureEditor::url( $procedure_id ),
						'workspace_url' => Shell::url( 'workspace' ),
						'extra'         => sanitize_textarea_field( (string) get_option( self::OPTION_CREATED_EXTRA, '' ) ),
					)
				);
			}
		);
	}

	/**
	 * An application has just been stored: new or edited.
	 *
	 * @param int  $application_id Application ID.
	 * @param bool $created        Whether it did not exist before.
	 * @return void
	 */
	public static function on_application_saved( $application_id, $created = false ): void {
		self::safely(
			static function () use ( $application_id, $created ): void {
				$ctx = self::application_context( (int) $application_id );
				if ( array() === $ctx ) {
					return;
				}
				$ctx['created'] = (bool) $created;

				self::send(
					$created ? self::EVENT_APPLICATION_SUBMITTED : self::EVENT_APPLICATION_UPDATED,
					array( self::email_of( (int) get_post_field( 'post_author', (int) $application_id ) ) ),
					$ctx
				);

				$destinos   = self::contacts( (int) $ctx['procedure_id'] );
				$destinos[] = (string) get_option( self::OPTION_COPY, '' );
				$ctx['url'] = ProcedureEditor::url( (int) $ctx['procedure_id'], ProcedureEditor::PANEL_APPLICATIONS );
				self::send( self::EVENT_APPLICATION_RECEIVED, $destinos, $ctx );
			}
		);
	}

	/**
	 * The review state of an application has been set.
	 *
	 * Revisar sin cambiar de estado no manda nada. La nota viaja solo cuando
	 * se pide subsanar o se excluye, que es cuando el centro tiene que leerla.
	 *
	 * @param int    $application_id Application ID.
	 * @param string $state          New review state.
	 * @param string $previous       Review state before.
	 * @return void
	 */
	public static function on_application_reviewed( $application_id, $state = '', $previous = '' ): void {
		if ( (string) $state === (string) $previous ) {
			return;
		}
		self::safely(
			static function () use ( $application_id, $state ): void {
				$ctx = self::application_context( (int) $application_id );
				if ( array() === $ctx ) {
					return;
				}
				$ctx['state'] = (string) ( ApplicationMetaKeys::review_states()[ $state ] ?? '' );
				$ctx['note']  = in_array( $state, ApplicationMetaKeys::states_needing_note(), true )
					? (string) get_post_meta( (int) $application_id, ApplicationMetaKeys::REVIEW_NOTE, true )
					: '';
				self::send(
					self::EVENT_REVIEW_CHANGED,
					array( self::email_of( (int) get_post_field( 'post_author', (int) $application_id ) ) ),
					$ctx
				);
			}
		);
	}

	/**
	 * A document with its own deadline has just been delivered.
	 *
	 * @param int    $application_id Application ID.
	 * @param string $question_key   Key of the delivery question.
	 * @return void
	 */
	public static function on_delivery_saved( $application_id, $question_key = '' ): void {
		self::safely(
			static function () use ( $application_id, $question_key ): void {
				$ctx = self::application_context( (int) $application_id );
				if ( array() === $ctx ) {
					return;
				}
				foreach ( Applications::questions( (int) $ctx['procedure_id'] ) as $pregunta ) {
					if ( (string) $question_key === (string) $pregunta['key'] ) {
						$ctx['document'] = (string) $pregunta['label'];
					}
				}
				$ctx['file'] = (string) ( ApplicationFiles::descriptors( (int) $application_id )[ (string) $question_key ]['name'] ?? '' );
				$ctx['date'] = (string) wp_date( 'd-m-Y H:i' );
				unset( $ctx['answers'] );

				$destinos   = self::contacts( (int) $ctx['procedure_id'] );
				$destinos[] = self::email_of( (int) get_post_field( 'post_author', (int) $application_id ) );
				self::send( self::EVENT_DELIVERY_RECEIVED, $destinos, $ctx );
			}
		);
	}

	// ─── el contexto ───────────────────────────────────────────────────────

	/**
	 * What every mail about one application says, resolved to text.
	 *
	 * Con rótulos y no con claves: la persona lee «Tiene huerto: Sí», no
	 * `q2 = 1`. Las preguntas son las que hoy tiene el procedimiento, en su
	 * orden, y las entregas con plazo propio no entran: no son de la
	 * solicitud (ADR-0033).
	 *
	 * @param int $application_id Application ID.
	 * @return array<string, mixed> Empty when it is not an application.
	 */
	private static function application_context( int $application_id ): array {
		if ( ApplicationPostType::POST_TYPE !== get_post_type( $application_id ) ) {
			return array();
		}
		$procedure_id = (int) get_post_field( 'post_parent', $application_id );
		$meta         = Applications::meta( $application_id );
		$autor        = get_userdata( (int) get_post_field( 'post_author', $application_id ) );
		$docente      = ApplicationMetaKeys::TYPE_TEACHER === ApplicationMetaRegistration::sanitize_applicant_type( $meta[ ApplicationMetaKeys::APPLICANT_TYPE ] );
		$codigo       = (string) $meta[ ApplicationMetaKeys::CENTRE_CODE ];
		$nombre       = (string) $meta[ ApplicationMetaKeys::CENTRE_NAME ];
		$coordinacion = ApplicationMetaRegistration::sanitize_coordinator( $meta[ ApplicationMetaKeys::COORDINATOR ] );

		$args = array();
		if ( '' !== $codigo && false !== $autor && CentreScope::code_for( (int) $autor->ID ) !== $codigo ) {
			$args[ ApplyForm::ARG_CENTRE ] = $codigo;
		}

		return array(
			'procedure_id' => $procedure_id,
			'procedure'    => self::title( $procedure_id ),
			'centre'       => $docente || '' === $codigo ? '' : ( '' !== $nombre ? $nombre . ' (' . $codigo . ')' : $codigo ),
			'applicant'    => false !== $autor ? (string) $autor->display_name : '',
			'position'     => (string) ( ApplicationMetaKeys::positions()[ $meta[ ApplicationMetaKeys::APPLICANT_POSITION ] ] ?? '' ),
			'coordinator'  => implode( ', ', array_filter( array_column( $coordinacion, 'name' ) ) ),
			'date'         => (string) wp_date( 'd-m-Y H:i', (int) get_post_timestamp( $application_id, 'modified' ) ),
			'answers'      => self::answers( $procedure_id, $application_id, (array) $meta[ ApplicationMetaKeys::ANSWERS ] ),
			'url'          => ApplyForm::url( $procedure_id, $args ),
		);
	}

	/**
	 * One `{label, text}` per question of the application.
	 *
	 * @param int                  $procedure_id   Procedure ID.
	 * @param int                  $application_id Application ID.
	 * @param array<string, mixed> $respuestas     Stored answers.
	 * @return array<int, array{label:string, text:string}>
	 */
	private static function answers( int $procedure_id, int $application_id, array $respuestas ): array {
		$documentos = ApplicationFiles::descriptors( $application_id );
		$out        = array();
		foreach ( ProcedureQuestions::application_questions( Applications::questions( $procedure_id ) ) as $pregunta ) {
			$key   = (string) $pregunta['key'];
			$out[] = array(
				'label' => (string) $pregunta['label'],
				'text'  => ProcedureQuestions::TYPE_FILE === (string) ( $pregunta['type'] ?? '' )
					? (string) ( $documentos[ $key ]['name'] ?? '' )
					: ProcedureQuestions::as_text( $pregunta, $respuestas[ $key ] ?? null ),
			);
		}
		return $out;
	}

	/**
	 * What a fresh draft still lacks, in words.
	 *
	 * @param int $procedure_id Procedure ID.
	 * @return string[]
	 */
	private static function missing( int $procedure_id ): array {
		$falta = array();
		if ( '' === trim( (string) get_post_field( 'post_content', $procedure_id ) ) ) {
			$falta[] = 'Descripción';
		}
		if ( '' === (string) get_post_meta( $procedure_id, ProcedureMetaKeys::OPENS_AT, true ) || '' === (string) get_post_meta( $procedure_id, ProcedureMetaKeys::CLOSES_AT, true ) ) {
			$falta[] = 'Plazo de solicitud: fechas de apertura y cierre';
		}
		if ( array() === Applications::questions( $procedure_id ) ) {
			$falta[] = 'Preguntas de la solicitud, si las necesita';
		}
		if ( '' === (string) get_post_meta( $procedure_id, ProcedureMetaKeys::RESOLUTION_URL, true ) ) {
			$falta[] = 'Enlace a la resolución';
		}
		return $falta;
	}

	/**
	 * The contact addresses of a procedure.
	 *
	 * @param int $procedure_id Procedure ID.
	 * @return string[]
	 */
	private static function contacts( int $procedure_id ): array {
		$correos = get_post_meta( $procedure_id, ProcedureMetaKeys::CONTACT_EMAILS, true );
		return is_array( $correos ) ? array_map( 'strval', $correos ) : array();
	}

	/**
	 * The address of an account, or ''.
	 *
	 * @param int $user_id User ID.
	 * @return string
	 */
	private static function email_of( int $user_id ): string {
		$user = $user_id > 0 ? get_userdata( $user_id ) : false;
		return false !== $user ? (string) $user->user_email : '';
	}

	/**
	 * A post title as plain text.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private static function title( int $post_id ): string {
		return html_entity_decode( wp_strip_all_tags( (string) get_post_field( 'post_title', $post_id ) ), ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Run a listener without letting a failure reach whoever emitted the action.
	 *
	 * @param callable $listener Listener body.
	 * @return void
	 */
	private static function safely( callable $listener ): void {
		try {
			$listener();
		} catch ( \Throwable $e ) {
			// Ver mail(): el aviso nunca tumba lo que lo disparó.
			unset( $e );
		}
	}
}
