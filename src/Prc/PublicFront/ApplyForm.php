<?php
/**
 * The application form of a school, and what it does when submitted.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\Access\CentreScope;
use Prc\Access\ProcedureAccess;
use Prc\Domain\ApplicationInput;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ProcedurePostType;
use Prc\PublicFront\View\ApplyFormView;

/**
 * Shortcode [prc_apply]: la solicitud de un centro a un procedimiento.
 *
 * Lee la petición y decide; **no pinta nada**, que es de {@see ApplyFormView}.
 *
 * Quien solicita es el equipo directivo de un centro, con sesión y con su
 * código de centro en el perfil (ADR-0016): el centro **nunca se teclea**. Hay
 * una solicitud por centro y procedimiento (ADR-0018), así que volver es
 * editar la que hay. Cuándo se puede presentar o editar lo dice el estado del
 * procedimiento ({@see window()}), y en subsanación solo edita el centro al
 * que se le pidió (ADR-0020).
 *
 * Quien dirige un centro y coordina una agrupación de escuelas rurales tiene
 * dos códigos (ADR-0031). Si el procedimiento admite uno solo, se solicita con
 * ese sin preguntar; si admite los dos, la pantalla deja elegir, y cada código
 * tiene su propia solicitud. Elegir no es teclear: el código que llega en la
 * URL o en el POST tiene que ser uno de los de la cuenta.
 */
final class ApplyForm {

	public const SHORTCODE = 'prc_apply';

	/**
	 * Query arg carrying the procedure the application is for: the shell's.
	 */
	public const ARG_PROCEDURE = Shell::ARG_PROCEDURE;

	/**
	 * Query arg carrying what the last submit did.
	 */
	public const ARG_NOTICE = 'aviso';

	/**
	 * Query arg carrying which of the person's codes the application is for.
	 */
	public const ARG_CENTRE = 'centro';

	/**
	 * The field that says this POST is ours.
	 */
	public const FIELD_OP = 'prc_apply_op';

	public const OP_APPLY = 'apply';

	public const FIELD_PROCEDURE   = 'prc_apply_procedure';
	public const FIELD_CENTRE      = 'prc_apply_centre';
	public const FIELD_POSITION    = 'prc_position';
	public const FIELD_COORD_NAME  = 'prc_coordinator_name';
	public const FIELD_COORD_EMAIL = 'prc_coordinator_email';
	public const FIELD_ACCEPT      = 'prc_accept';
	public const FIELD_ANSWERS     = 'prc_q';

	public const NONCE_ACTION = 'prc_apply';
	public const NONCE_FIELD  = '_prc_apply_nonce';

	/**
	 * What each notice code in the URL says.
	 *
	 * @var array<string, string>
	 */
	private const DONE = array(
		'presentada' => 'Solicitud presentada. Puede volver a esta pantalla para consultarla, y editarla mientras el plazo siga abierto.',
		'guardada'   => 'Solicitud guardada.',
	);

	/**
	 * Why the last submit did not go through.
	 *
	 * Un envío rechazado no redirige: la misma petición repinta el formulario
	 * con lo tecleado y el motivo.
	 *
	 * @var string
	 */
	private static $rejected = '';

	/**
	 * Register the shortcode and the POST handler.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'render' ) );
		add_action( 'init', array( self::class, 'handle' ), 20 );
	}

	/**
	 * URL of the application screen of one procedure.
	 *
	 * @param int                  $procedure_id Procedure post ID.
	 * @param array<string, mixed> $args         Extra query arguments.
	 * @return string Empty when there is no page for the screen.
	 */
	public static function url( int $procedure_id, array $args = array() ): string {
		if ( $procedure_id > 0 ) {
			$args[ self::ARG_PROCEDURE ] = $procedure_id;
		}
		return Shell::url( 'apply', $args );
	}

	/**
	 * URL of the application of one procedure made with one of the person's codes.
	 *
	 * El código de centro no lleva nada en la URL: es el de siempre. Solo el de
	 * la agrupación de escuelas rurales necesita decirse.
	 *
	 * @param int                  $procedure_id Procedure post ID.
	 * @param string               $code         Code the application is for.
	 * @param array<string, mixed> $args         Extra query arguments.
	 * @return string
	 */
	public static function url_for_code( int $procedure_id, string $code, array $args = array() ): string {
		if ( '' !== $code && CentreScope::code_for() !== $code ) {
			$args[ self::ARG_CENTRE ] = $code;
		}
		return self::url( $procedure_id, $args );
	}

	/**
	 * Which of the person's codes the screen works with.
	 *
	 * El pedido, si el procedimiento lo admite; si no, el primero que admite,
	 * y con eso el código del centro va antes que el de la agrupación.
	 *
	 * @param int    $user_id      User ID.
	 * @param int    $procedure_id Procedure post ID.
	 * @param string $wanted       Code asked for; empty for none.
	 * @return string Empty when the procedure takes none of them.
	 */
	public static function chosen( int $user_id, int $procedure_id, string $wanted ): string {
		$codes = ProcedureAccess::apply_codes( $user_id, $procedure_id );
		if ( '' !== $wanted && isset( $codes[ $wanted ] ) ) {
			return $wanted;
		}
		return array() === $codes ? '' : (string) array_key_first( $codes );
	}

	/**
	 * Whether a school may submit or edit its application right now, and why not.
	 *
	 * La tabla de la SDD, en código: abierto se presenta o se edita; en
	 * subsanación solo edita quien tiene la solicitud «a subsanar»; en el
	 * resto se mira y ya. Quién puede —capacidad, centro, titularidad— lo
	 * dice {@see ProcedureAccess::can_apply()} antes de llegar aquí.
	 *
	 * @param string $state        One of ProcedureMetaKeys::states().
	 * @param string $review_state Review state of the existing application, '' when there is none.
	 * @return string Empty when the form is open; otherwise why it is not.
	 */
	public static function window( string $state, string $review_state ): string {
		if ( ProcedureState::accepts_applications( $state ) ) {
			return '';
		}
		if ( ProcedureMetaKeys::STATE_AMENDMENT === $state ) {
			return ApplicationMetaKeys::REVIEW_AMEND === $review_state
				? ''
				: 'El procedimiento está en plazo de subsanación: solo pueden editar su solicitud los centros a los que se ha pedido subsanar.';
		}
		if ( ProcedureMetaKeys::STATE_UPCOMING === $state ) {
			return 'El plazo de solicitud todavía no está abierto.';
		}
		return 'El plazo de solicitud está cerrado.';
	}

	/**
	 * Take a submit, when it is ours.
	 *
	 * @return void
	 */
	public static function handle(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- se comprueba justo debajo.
		$op    = sanitize_key( wp_unslash( (string) ( $_POST[ self::FIELD_OP ] ?? '' ) ) );
		$nonce = sanitize_text_field( wp_unslash( (string) ( $_POST[ self::NONCE_FIELD ] ?? '' ) ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		if ( self::OP_APPLY !== $op || ! is_user_logged_in() ) {
			return;
		}
		if ( false === wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			self::$rejected = 'El formulario estuvo abierto demasiado tiempo y el envío caducó. Vuelva a enviarlo.';
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- comprobado arriba.
		$procedure_id = absint( wp_unslash( $_POST[ self::FIELD_PROCEDURE ] ?? 0 ) );
		$user_id      = get_current_user_id();

		// Un código que no llega se elige como en la pantalla; uno que llega
		// tiene que ser de la cuenta y admitido, o no se guarda nada.
		$pedido = self::field( self::FIELD_CENTRE );
		if ( ! ProcedureAccess::can_apply( $user_id, $procedure_id, $pedido ) ) {
			self::$rejected = self::why_not_allowed( $user_id, $procedure_id, $pedido );
			return;
		}
		$code      = '' !== $pedido ? $pedido : self::chosen( $user_id, $procedure_id, '' );
		$existente = Applications::find( $procedure_id, $code );
		$cerrado   = self::window(
			ProcedureState::of_post( $procedure_id ),
			null !== $existente ? (string) get_post_meta( $existente, ApplicationMetaKeys::REVIEW_STATE, true ) : ''
		);
		if ( '' !== $cerrado ) {
			self::$rejected = $cerrado;
			return;
		}

		$spec     = Applications::spec( $procedure_id );
		$revisado = ApplicationInput::validate( self::submitted(), $spec );

		// Los documentos se comprueban **antes** de guardar nada. Lo que ya
		// tiene la solicitud cuenta: reenviar el formulario sin volver a
		// adjuntar no vacía lo presentado ni falla por obligatorio (ADR-0028).
		$ficheros = ApplicationFiles::submitted(
			(array) $spec['questions'],
			null !== $existente ? ApplicationFiles::descriptors( $existente ) : array()
		);

		if ( ! $revisado['ok'] || ! $ficheros['ok'] ) {
			$porque         = ApplicationFiles::why( $ficheros['errors'] );
			self::$rejected = '' !== $porque && $revisado['ok']
				? $porque
				: ApplicationInput::why( $revisado['errors'], (array) $spec['questions'] );
			return;
		}

		$id = Applications::save( $procedure_id, $user_id, $revisado['data'], $code );
		if ( $id <= 0 ) {
			self::$rejected = 'No se ha podido guardar la solicitud. Vuelva a intentarlo.';
			return;
		}

		// Y si guardar un documento falla, la solicitud no puede quedarse a
		// medias: una que acababa de nacer se borra entera; una que ya existía
		// se queda **exactamente como estaba** —`store_all()` no ha tocado ni
		// un descriptor anterior— y se dice que no.
		if ( ! ApplicationFiles::store_all( $id, $ficheros['files'] ) ) {
			if ( null === $existente ) {
				wp_delete_post( $id, true );
			}
			self::$rejected = 'No se ha podido guardar el documento que adjuntó, así que la solicitud no se ha presentado. Vuelva a intentarlo.';
			return;
		}
		Shell::leave( self::url_for_code( $procedure_id, $code, array( self::ARG_NOTICE => null === $existente ? 'presentada' : 'guardada' ) ) );
	}

	/**
	 * What the form sent, in the shape ApplicationInput wants.
	 *
	 * @return array<string, mixed>
	 */
	private static function submitted(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- el nonce lo comprobó handle().
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- las respuestas las valida ProcedureQuestions contra las preguntas, y el sanitize de la meta las limpia al guardar.
		$respuestas = (array) wp_unslash( $_POST[ self::FIELD_ANSWERS ] ?? array() );
		$acepta     = ! empty( $_POST[ self::FIELD_ACCEPT ] );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		return array(
			'position'          => self::field( self::FIELD_POSITION ),
			'coordinator_name'  => self::field( self::FIELD_COORD_NAME ),
			'coordinator_email' => self::field( self::FIELD_COORD_EMAIL ),
			'accept'            => $acepta,
			'answers'           => self::clean_answers( $respuestas ),
		);
	}

	/**
	 * Submitted answers with their texts cleaned, keyed as they came.
	 *
	 * @param array<string, mixed> $raw Raw answers.
	 * @return array<string, mixed>
	 */
	private static function clean_answers( array $raw ): array {
		$out = array();
		foreach ( $raw as $key => $valor ) {
			if ( is_array( $valor ) ) {
				$out[ (string) $key ] = array_map( 'sanitize_text_field', array_filter( $valor, 'is_scalar' ) );
			} elseif ( is_scalar( $valor ) ) {
				$out[ (string) $key ] = sanitize_textarea_field( (string) $valor );
			}
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
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- el nonce lo comprobó handle() antes de llamar aquí.
		return trim( sanitize_text_field( wp_unslash( (string) ( $_POST[ $nombre ] ?? '' ) ) ) );
	}

	/**
	 * Why this person cannot apply to this procedure, in words.
	 *
	 * @param int    $user_id      User ID.
	 * @param int    $procedure_id Procedure post ID.
	 * @param string $code         Code asked for; empty for none.
	 * @return string
	 */
	private static function why_not_allowed( int $user_id, int $procedure_id, string $code = '' ): string {
		if ( ! user_can( $user_id, ProcedureAccess::CAP_APPLY ) ) {
			return 'Su perfil no presenta solicitudes de centro. Si forma parte del equipo directivo de un centro, pídalo a quien administre el aplicativo.';
		}
		$suyos = CentreScope::codes_for( $user_id );
		if ( array() === $suyos ) {
			return 'Su cuenta no tiene centro asignado, así que no puede presentar solicitudes. El código de centro lo pone quien administra el aplicativo.';
		}
		if ( '' !== $code && ! isset( $suyos[ $code ] ) ) {
			return 'Ese código de centro no es de su cuenta, así que no puede solicitar con él.';
		}
		if ( ProcedurePostType::POST_TYPE !== get_post_type( $procedure_id ) || 'publish' !== get_post_status( $procedure_id ) ) {
			return 'Ese procedimiento no existe o todavía no está publicado.';
		}
		$admite = ProcedureAccess::centre_kinds( $procedure_id );
		if ( array() === array_intersect( $suyos, $admite ) ) {
			$rotulos = array_map( 'lcfirst', array_intersect_key( ProcedureMetaKeys::centre_kinds(), array_flip( $admite ) ) );
			return 'Este procedimiento solo admite solicitudes de ' . implode( ' y de ', $rotulos ) . ', y su cuenta no tiene un código así.';
		}
		return 'Este procedimiento no se dirige a centros de la titularidad del suyo.';
	}

	/**
	 * Everything the screen decides before painting.
	 *
	 * @return array<string, mixed>
	 */
	public static function model(): array {
		$m = array(
			'aviso'           => '',
			'flash'           => array(
				'tipo'  => '',
				'texto' => '',
			),
			'procedure_id'    => 0,
			'title'           => '',
			'view_url'        => '',
			'mine_url'        => Shell::url( 'mine' ),
			'state'           => '',
			'state_label'     => '',
			'opens_at'        => '',
			'closes_at'       => '',
			'amend_opens_at'  => '',
			'amend_closes_at' => '',
			'final_list_url'  => '',
			'centre'          => array(
				'code'      => '',
				'name'      => '',
				'ownership' => '',
			),
			// Con qué códigos de la cuenta se puede solicitar a este
			// procedimiento: código => rótulo. Con más de uno, se elige.
			'choices'         => array(),
			// Quien entra, para la cabecera de solo lectura: sale de la cuenta
			// y no se guarda en la solicitud (ADR-0016).
			'applicant'       => array(
				'name'  => '',
				'email' => '',
			),
			'application'     => array(),
			'max_file_size'   => ApplicationFiles::max_bytes(),
			'file_mimes'      => ApplicationFiles::mimes(),
			'can_submit'      => false,
			'why'             => '',
			'spec'            => array(
				'requires_coordinator' => false,
				'commitments'          => '',
				'questions'            => array(),
			),
			'positions'       => ApplicationMetaKeys::positions(),
			'review_states'   => ApplicationMetaKeys::review_states(),
			'values'          => array(),
		);

		if ( ! is_user_logged_in() ) {
			$m['aviso'] = 'Debe iniciar sesión con su usuario para presentar la solicitud de su centro.';
			return $m;
		}
		$user_id = get_current_user_id();
		if ( ! user_can( $user_id, ProcedureAccess::CAP_APPLY ) ) {
			$m['aviso'] = self::why_not_allowed( $user_id, 0 );
			return $m;
		}
		$quien          = wp_get_current_user();
		$m['applicant'] = array(
			'name'  => (string) $quien->display_name,
			'email' => (string) $quien->user_email,
		);
		$m['centre']    = Applications::centre_of( $user_id );
		if ( array() === CentreScope::codes_for( $user_id ) ) {
			$m['aviso'] = self::why_not_allowed( $user_id, 0 );
			return $m;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- lectura del procedimiento y del aviso; mutar lleva su nonce.
		$procedure_id = absint( wp_unslash( $_GET[ self::ARG_PROCEDURE ] ?? 0 ) );
		$hecho        = sanitize_key( wp_unslash( (string) ( $_GET[ self::ARG_NOTICE ] ?? '' ) ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ProcedurePostType::POST_TYPE !== get_post_type( $procedure_id ) || 'publish' !== get_post_status( $procedure_id ) ) {
			$m['aviso'] = $procedure_id > 0
				? 'Ese procedimiento no existe o todavía no está publicado.'
				: 'Abra la solicitud desde la ficha del procedimiento, o desde las solicitudes de su centro.';
			return $m;
		}

		$m['procedure_id']    = $procedure_id;
		$m['title']           = (string) get_post_field( 'post_title', $procedure_id );
		$m['view_url']        = (string) get_permalink( $procedure_id );
		$m['state']           = ProcedureState::of_post( $procedure_id );
		$m['state_label']     = ProcedureState::label( (string) $m['state'] );
		$m['opens_at']        = (string) get_post_meta( $procedure_id, ProcedureMetaKeys::OPENS_AT, true );
		$m['closes_at']       = (string) get_post_meta( $procedure_id, ProcedureMetaKeys::CLOSES_AT, true );
		$m['amend_opens_at']  = (string) get_post_meta( $procedure_id, ProcedureMetaKeys::AMEND_OPENS_AT, true );
		$m['amend_closes_at'] = (string) get_post_meta( $procedure_id, ProcedureMetaKeys::AMEND_CLOSES_AT, true );
		$m['final_list_url']  = (string) get_post_meta( $procedure_id, ProcedureMetaKeys::FINAL_LIST_URL, true );
		$m['spec']            = Applications::spec( $procedure_id );

		// Sin ningún código admitido se queda el del centro, para decir por qué.
		$code         = self::chosen( $user_id, $procedure_id, self::wanted() );
		$m['centre']  = Applications::centre_of( $user_id, $code );
		$m['choices'] = self::choices( $user_id, $procedure_id );

		$existente = Applications::find( $procedure_id, $m['centre']['code'] );
		if ( null !== $existente ) {
			$meta             = Applications::meta( $existente );
			$estado           = (string) $meta[ ApplicationMetaKeys::REVIEW_STATE ];
			$m['application'] = array(
				'id'          => $existente,
				'date'        => (string) get_the_date( 'd-m-Y', $existente ),
				'state'       => $estado,
				'state_label' => (string) ( ApplicationMetaKeys::review_states()[ $estado ] ?? '' ),
				'note'        => (string) $meta[ ApplicationMetaKeys::REVIEW_NOTE ],
				'position'    => (string) ( ApplicationMetaKeys::positions()[ $meta[ ApplicationMetaKeys::APPLICANT_POSITION ] ] ?? '' ),
				'coordinator' => (array) $meta[ ApplicationMetaKeys::COORDINATOR ],
				'answers'     => (array) $meta[ ApplicationMetaKeys::ANSWERS ],
				'files'       => ApplicationFiles::descriptors( $existente ),
			);
		}

		// Primero quién, luego cuándo: la titularidad cierra la puerta aunque el
		// plazo esté abierto, y se dice.
		if ( ! ProcedureAccess::can_apply( $user_id, $procedure_id, $code ) ) {
			$m['why'] = self::why_not_allowed( $user_id, $procedure_id );
		} else {
			$m['why'] = self::window( (string) $m['state'], (string) ( $m['application']['state'] ?? '' ) );
		}
		$m['can_submit'] = '' === $m['why'];
		$m['values']     = self::values( (array) $m['application'] );
		$m['flash']      = '' !== self::$rejected
			? array(
				'tipo'  => 'error',
				'texto' => self::$rejected,
			)
			: array(
				'tipo'  => isset( self::DONE[ $hecho ] ) ? 'ok' : '',
				'texto' => (string) ( self::DONE[ $hecho ] ?? '' ),
			);

		return $m;
	}

	/**
	 * The code the screen was asked for: the URL's, or the one a refused submit carried.
	 *
	 * @return string
	 */
	private static function wanted(): string {
		if ( '' !== self::$rejected ) {
			return self::field( self::FIELD_CENTRE );
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo elige cuál de los códigos de la cuenta se enseña; chosen() descarta cualquier otro.
		return sanitize_text_field( wp_unslash( (string) ( $_GET[ self::ARG_CENTRE ] ?? '' ) ) );
	}

	/**
	 * The person's codes this procedure takes, as the screen names them.
	 *
	 * @param int $user_id      User ID.
	 * @param int $procedure_id Procedure post ID.
	 * @return array<string, string> Code => label.
	 */
	private static function choices( int $user_id, int $procedure_id ): array {
		$out = array();
		foreach ( ProcedureAccess::apply_codes( $user_id, $procedure_id ) as $code => $kind ) {
			$centro       = Applications::centre_of( $user_id, $code );
			$out[ $code ] = ( '' !== $centro['name'] ? $centro['name'] . ' (' . $code . ')' : $code )
				. ( ProcedureMetaKeys::KIND_RURAL_GROUP === $kind ? ', como agrupación de escuelas rurales' : '' );
		}
		return $out;
	}

	/**
	 * What every form field shows: what was typed on a refused submit, or what is stored.
	 *
	 * @param array<string, mixed> $application The existing application, empty for none.
	 * @return array{position:string, coordinator_name:string, coordinator_email:string, accept:bool, answers:array<string, mixed>}
	 */
	private static function values( array $application ): array {
		if ( '' !== self::$rejected ) {
			return self::submitted();
		}
		$coord = (array) ( $application['coordinator'] ?? array() );
		$meta  = array() !== $application ? Applications::meta( (int) $application['id'] ) : array();
		return array(
			'position'          => (string) ( $meta[ ApplicationMetaKeys::APPLICANT_POSITION ] ?? '' ),
			'coordinator_name'  => (string) ( $coord['name'] ?? '' ),
			'coordinator_email' => (string) ( $coord['email'] ?? '' ),
			// Una solicitud ya presentada ya aceptó los compromisos.
			'accept'            => array() !== $application,
			'answers'           => (array) ( $application['answers'] ?? array() ),
		);
	}

	/**
	 * Render the shortcode.
	 *
	 * @param array<string, string>|string $atts Attributes.
	 * @return string
	 */
	public static function render( $atts = array() ): string {
		unset( $atts );
		Assets::enqueue();
		return ApplyFormView::html( self::model() );
	}
}
