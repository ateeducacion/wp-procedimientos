<?php
/**
 * Signing the receipt of an application: who, what is stored and where it shows.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\Access\ProcedureAccess;
use Prc\Domain\SignedReceipt;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ApplicationPostType;

/**
 * La firma electrónica del justificante (ADR-0049).
 *
 * El aplicativo no firma: firma quien presenta, con su certificado, en
 * AutoFirma o con la herramienta que quiera. Aquí se decide quién y cuándo,
 * se dan al navegador los datos con los que jsPDF genera el PDF, se recibe el
 * firmado por un formulario normal —lo devuelva AutoFirma en base64 o se suba
 * a mano— y se guarda en el almacén privado de {@see ApplicationFiles}
 * (ADR-0028). No hay ruta REST: el mismo POST sirve a los dos caminos y
 * funciona sin JavaScript.
 *
 * Las librerías de terceros —jsPDF y AutoScript— vienen de jsDelivr con SRI
 * y versión exacta, solo en la pantalla que firma (ADR-0006); en desarrollo
 * el mu-plugin las sirve de `node_modules`.
 */
final class ReceiptSignature {

	/** The field that says this POST is ours, with {@see OP_SIGN}. */
	public const FIELD_OP = 'prc_sign_op';

	public const OP_SIGN = 'sign';

	/** The application whose receipt is signed. */
	public const FIELD_APPLICATION = 'prc_sign_application';

	/** The signed PDF, uploaded by hand. */
	public const FIELD_FILE = 'prc_signed_pdf';

	/** The signed PDF in base64, as AutoFirma returns it. */
	public const FIELD_SIGNED = 'prc_signed_b64';

	/** Field carrying the nonce, whose action is {@see nonce_action()}. */
	public const NONCE_FIELD = '_prc_sign_nonce';

	/** Anchor of the section on the application screen. */
	public const ANCHOR = 'prc-firma';

	/** Notice code of ApplyForm once signed. */
	public const NOTICE = 'firmada';

	/** Query arg that asks for the signed receipt of an application. */
	public const ARG_DOWNLOAD = 'prc_firmado';

	public const VIA_AUTOFIRMA = 'autofirma';
	public const VIA_UPLOAD    = 'upload';

	public const STATE_SIGNED  = 'signed';
	public const STATE_PENDING = 'pending';

	public const HANDLE_JSPDF      = 'prc-jspdf';
	public const HANDLE_AUTOSCRIPT = 'prc-autoscript';

	/**
	 * Why the last signing did not go through.
	 *
	 * @var string
	 */
	private static $rejected = '';

	/**
	 * Register the POST handler, the download, the cleanup and the SRI.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'handle' ), 20 );
		add_action( 'init', array( self::class, 'serve' ), 20 );
		add_action( 'before_delete_post', array( self::class, 'on_delete' ), 10, 2 );
		add_filter( 'script_loader_tag', array( self::class, 'sri' ), 10, 3 );
	}

	/**
	 * The two libraries, pinned, with the SRI of the file jsDelivr serves.
	 *
	 * Calculado pidiendo el fichero al CDN, dos veces con el mismo resultado:
	 *
	 *   curl -s <url> | openssl dgst -sha384 -binary | openssl base64 -A
	 *
	 * AutoScript de `@erseco/autofirma-client` 1.9.3, la primera estable: hasta
	 * ella solo había versiones *canary*. La versión está clavada aquí, en la
	 * URL y en `package.json`; si se sube una, se suben las tres.
	 *
	 * @return array<string, array{url:string, ver:string, sri:string}>
	 */
	public static function vendors(): array {
		return array(
			self::HANDLE_JSPDF      => array(
				'url' => 'https://cdn.jsdelivr.net/npm/jspdf@4.2.1/dist/jspdf.umd.min.js',
				'ver' => '4.2.1',
				'sri' => 'sha384-qovJwSBbRDPP5cEjCp8S0UP66wrvnjaa60XMOGzTNanrThcrGfXfnZkvgY8N1KT3',
			),
			self::HANDLE_AUTOSCRIPT => array(
				'url' => 'https://cdn.jsdelivr.net/npm/@erseco/autofirma-client@1.9.3/vendor/autoscript.js',
				'ver' => '1.9.3',
				'sri' => 'sha384-F2mPlftZeXB0HJb+lwQbpi4OXcxuJmrQ5KCvank97CWgaVFZv4mEc2ZsmdAM+uGd',
			),
		);
	}

	/**
	 * Add integrity and crossorigin to our vendor tags, only while they come from the CDN.
	 *
	 * @param string $tag    The tag WordPress is about to print.
	 * @param string $handle Its handle.
	 * @param string $src    Its URL.
	 * @return string
	 */
	public static function sri( $tag, $handle, $src ) {
		$vendors = self::vendors();
		if ( ! isset( $vendors[ $handle ] ) || 0 !== strpos( (string) $src, 'https://cdn.jsdelivr.net/' ) ) {
			return $tag;
		}
		return str_replace( ' src=', ' integrity="' . $vendors[ $handle ]['sri'] . '" crossorigin="anonymous" src=', (string) $tag );
	}

	/**
	 * The servlets of the AutoFirma intermediate server, for signing from a phone.
	 *
	 * En escritorio AutoScript habla con AutoFirma por WebSocket y no los usa.
	 * En el móvil no hay WebSocket: sin servidor intermedio, el botón de
	 * AutoFirma no sale y queda la vía manual. Este repositorio no sabe de
	 * ningún servidor intermedio, así que **vacío por defecto**.
	 *
	 * @return array{storage:string, retrieve:string}
	 */
	public static function servlets(): array {
		/**
		 * Filter the servlets of the AutoFirma intermediate server.
		 *
		 * @param array{storage:string, retrieve:string} $servlets Storage and retrieve URLs; empty for none.
		 */
		$servlets = (array) apply_filters(
			'prc_autofirma_servlets',
			array(
				'storage'  => '',
				'retrieve' => '',
			)
		);
		$out      = array();
		foreach ( array( 'storage', 'retrieve' ) as $clave ) {
			$out[ $clave ] = esc_url_raw( (string) ( $servlets[ $clave ] ?? '' ), array( 'https' ) );
		}
		return $out;
	}

	// ─── quién y cuándo ────────────────────────────────────────────────────

	/**
	 * Whether the procedure asks for the signature of the receipt.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return bool
	 */
	public static function required( int $procedure_id ): bool {
		return $procedure_id > 0 && (bool) get_post_meta( $procedure_id, ProcedureMetaKeys::SIGNATURE_REQUIRED, true );
	}

	// ─── lo que se firma ───────────────────────────────────────────────────

	/**
	 * What the receipt says and its fingerprint, from what is stored today.
	 *
	 * Lo calcula igual la pantalla, para que jsPDF lo escriba, y el envío,
	 * para comprobar lo que vuelve: si algo cambió entretanto, la huella ya no
	 * coincide.
	 *
	 * @param int $application_id Application ID.
	 * @return array{number:string, procedure:string, centre:string, submitted:string, coordination:string, fingerprint:string, created:string, file_id:string, filename:string}
	 */
	public static function data( int $application_id ): array {
		$procedure_id = (int) wp_get_post_parent_id( $application_id );
		$cuando       = ApplicationProgress::submitted_at( $application_id );
		if ( ApplicationMetaKeys::TYPE_TEACHER === get_post_meta( $application_id, ApplicationMetaKeys::APPLICANT_TYPE, true ) ) {
			$autor  = get_userdata( (int) get_post_field( 'post_author', $application_id ) );
			$centro = false !== $autor ? (string) $autor->display_name : '';
		} else {
			$centro = (string) get_post_meta( $application_id, ApplicationMetaKeys::CENTRE_NAME, true ) . ' (' . (string) get_post_meta( $application_id, ApplicationMetaKeys::CENTRE_CODE, true ) . ')';
		}
		$datos                = array(
			'number'       => (string) $application_id,
			'procedure'    => (string) get_post_field( 'post_title', $procedure_id ),
			'centre'       => $centro,
			'submitted'    => ApplicationProgress::when( $cuando ),
			// El DNI o NIE de coordinación va en el justificante y en su huella.
			'coordination' => ApplicationProgress::coordination( $application_id ),
		);
		$datos['fingerprint'] = SignedReceipt::fingerprint( $datos );
		$datos['created']     = SignedReceipt::pdf_date( $cuando );
		$datos['file_id']     = substr( $datos['fingerprint'], 0, 32 );
		$datos['filename']    = 'justificante-solicitud-' . $application_id . '.pdf';
		return $datos;
	}

	// ─── lo guardado ───────────────────────────────────────────────────────

	/**
	 * The stored signed receipt, or nothing.
	 *
	 * @param int $application_id Application ID.
	 * @return array<string, mixed> Descriptor plus `signed_at`, `signed_by` and `via`; empty when unsigned.
	 */
	public static function signed( int $application_id ): array {
		return $application_id > 0 ? self::sanitize( get_post_meta( $application_id, ApplicationMetaKeys::SIGNED_RECEIPT, true ) ) : array();
	}

	/**
	 * Keep a well-formed signed receipt, or nothing.
	 *
	 * @param mixed $value Raw value.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $value ): array {
		if ( ! is_array( $value ) || ! preg_match( '/^[a-f0-9]{32}$/', (string) ( $value['id'] ?? '' ) )
			|| ! preg_match( ApplicationFiles::STORED_SHAPE, (string) ( $value['stored'] ?? '' ) ) ) {
			return array();
		}
		return array(
			'id'        => (string) $value['id'],
			'name'      => sanitize_file_name( (string) ( $value['name'] ?? '' ) ),
			'mime'      => 'application/pdf',
			'size'      => absint( $value['size'] ?? 0 ),
			'sha256'    => preg_match( '/^[a-f0-9]{64}$/', (string) ( $value['sha256'] ?? '' ) ) ? (string) $value['sha256'] : '',
			'stored'    => (string) $value['stored'],
			'signed_at' => absint( $value['signed_at'] ?? 0 ),
			'signed_by' => absint( $value['signed_by'] ?? 0 ),
			'via'       => self::VIA_AUTOFIRMA === ( $value['via'] ?? '' ) ? self::VIA_AUTOFIRMA : self::VIA_UPLOAD,
		);
	}

	/**
	 * `signed`, `pending`, or '' when the procedure does not ask for it.
	 *
	 * @param int $application_id Application ID.
	 * @return string
	 */
	public static function state( int $application_id ): string {
		if ( ! self::required( (int) wp_get_post_parent_id( $application_id ) ) ) {
			return '';
		}
		return array() === self::signed( $application_id ) ? self::STATE_PENDING : self::STATE_SIGNED;
	}

	/**
	 * The state in words: «Firmado el … por …», «Pendiente de firma», or ''.
	 *
	 * @param int $application_id Application ID.
	 * @return string
	 */
	public static function label( int $application_id ): string {
		if ( ! self::required( (int) wp_get_post_parent_id( $application_id ) ) ) {
			return '';
		}
		$firma = self::signed( $application_id );
		if ( array() === $firma ) {
			return 'Pendiente de firma';
		}
		$quien = get_userdata( (int) $firma['signed_by'] );
		return 'Firmado el ' . ApplicationProgress::when( (int) $firma['signed_at'] ) . ( false !== $quien ? ' por ' . $quien->display_name : '' );
	}

	/**
	 * The export column, only where the procedure asks for the signature.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return array<string, string>
	 */
	public static function columns( int $procedure_id ): array {
		return self::required( $procedure_id ) ? array( 'signature' => 'Firma del justificante' ) : array();
	}

	/**
	 * The URL that serves the signed receipt; it authorises nothing by itself.
	 *
	 * @param int $application_id Application ID.
	 * @return string
	 */
	public static function url( int $application_id ): string {
		return add_query_arg( self::ARG_DOWNLOAD, $application_id, home_url( '/' ) );
	}

	// ─── la pantalla ───────────────────────────────────────────────────────

	/**
	 * Nonce action of the signing of one application.
	 *
	 * @param int $application_id Application ID.
	 * @return string
	 */
	public static function nonce_action( int $application_id ): string {
		return 'prc_sign_' . $application_id;
	}

	/**
	 * What the application screen paints about the signature.
	 *
	 * Solo encola las librerías cuando hay algo que firmar.
	 *
	 * @param array<string, mixed> $m What ApplyForm::model() returned.
	 * @return array<string, mixed> `show` false when there is nothing to say.
	 */
	public static function model( array $m ): array {
		$id = (int) ( $m['application']['id'] ?? 0 );
		if ( $id <= 0 || ! self::required( (int) wp_get_post_parent_id( $id ) ) || ApplicationOperations::pending_for_school( $id ) ) {
			return array( 'show' => false );
		}
		$puede = ProcedureAccess::can_sign_receipt( get_current_user_id(), $id );
		// El justificante para imprimir no pinta la sección: nada que cargar.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo decide si se cargan las librerías.
		if ( $puede && ! isset( $_GET[ ApplicationProgress::ARG_RECEIPT ] ) ) {
			foreach ( self::vendors() as $handle => $vendor ) {
				wp_enqueue_script( $handle, $vendor['url'], array(), $vendor['ver'], true );
			}
		}
		return array(
			'show'         => true,
			'can'          => $puede,
			'label'        => self::label( $id ),
			'signed'       => array() !== self::signed( $id ),
			'download_url' => self::url( $id ),
			'data'         => $puede ? self::data( $id ) : array(),
			'servlets'     => self::servlets(),
			'rejected'     => self::$rejected,
			'application'  => $id,
			'action'       => ApplyForm::url_for_code( (int) ( $m['procedure_id'] ?? 0 ), (string) ( $m['centre']['code'] ?? '' ) ) . '#' . self::ANCHOR,
			'nonce_action' => self::nonce_action( $id ),
			'max_bytes'    => ApplicationFiles::max_bytes(),
		);
	}

	// ─── el envío ──────────────────────────────────────────────────────────

	/**
	 * Take a signed receipt, when the POST is ours.
	 *
	 * @return void
	 */
	public static function handle(): void {
		if ( self::OP_SIGN !== sanitize_key( self::posted( self::FIELD_OP ) ) || ! is_user_logged_in() ) {
			return;
		}
		$id      = absint( self::posted( self::FIELD_APPLICATION ) );
		$user_id = get_current_user_id();
		$error   = self::refusal( $id, $user_id, sanitize_text_field( self::posted( self::NONCE_FIELD ) ) );
		$bytes   = '';
		$via     = self::VIA_UPLOAD;
		if ( '' === $error ) {
			list( $bytes, $via, $error ) = self::received();
		}
		if ( '' === $error ) {
			$datos = self::data( $id );
			$error = SignedReceipt::why( SignedReceipt::refuse( $bytes, $datos['number'], $datos['fingerprint'], ApplicationFiles::max_bytes() ) );
		}
		if ( '' === $error ) {
			$error = self::store( $id, $bytes, $user_id, $via );
		}
		if ( '' !== $error ) {
			self::$rejected = $error;
			return;
		}

		/**
		 * The receipt of an application has just been signed.
		 *
		 * @param int    $application_id Application ID.
		 * @param int    $user_id        Who signed it.
		 * @param string $via            `autofirma` or `upload`.
		 */
		do_action( 'prc_receipt_signed', $id, $user_id, $via );
		$procedure_id = (int) wp_get_post_parent_id( $id );
		Shell::leave( ApplyForm::url_for_code( $procedure_id, (string) get_post_meta( $id, ApplicationMetaKeys::CENTRE_CODE, true ), array( ApplyForm::ARG_NOTICE => self::NOTICE ) ) . '#' . self::ANCHOR );
	}

	/**
	 * One text field of the POST, unslashed; '' when absent or not text.
	 *
	 * @param string $field Field name.
	 * @return string
	 */
	private static function posted( string $field ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- quien llama sanea según el campo; el nonce se mira en refusal().
		return isset( $_POST[ $field ] ) && is_string( $_POST[ $field ] ) ? trim( wp_unslash( $_POST[ $field ] ) ) : '';
	}

	/**
	 * Why this POST may not sign, '' when it may: the nonce, then the rule.
	 *
	 * @param int    $application_id Application ID.
	 * @param int    $user_id        Who sends it.
	 * @param string $nonce          Nonce sent.
	 * @return string
	 */
	private static function refusal( int $application_id, int $user_id, string $nonce ): string {
		if ( false === wp_verify_nonce( $nonce, self::nonce_action( $application_id ) ) ) {
			return 'El formulario estuvo abierto demasiado tiempo y el envío caducó. Vuelva a intentarlo.';
		}
		if ( ProcedureAccess::can_sign_receipt( $user_id, $application_id ) ) {
			return '';
		}
		return array() === self::signed( $application_id ) ? 'No puede firmar el justificante de esta solicitud.' : 'El justificante de esta solicitud ya está firmado.';
	}

	/**
	 * The bytes that came back: from AutoFirma in base64, or uploaded by hand.
	 *
	 * El tamaño se mira **antes** de decodificar o leer: base64 pesa 4/3.
	 *
	 * @return array{0:string, 1:string, 2:string} Bytes, way, and why not ('' when fine).
	 */
	private static function received(): array {
		$b64 = self::posted( self::FIELD_SIGNED );
		if ( '' === $b64 ) {
			return self::uploaded();
		}
		if ( strlen( $b64 ) > (int) ceil( ApplicationFiles::max_bytes() * 4 / 3 ) + 4 ) {
			return array( '', self::VIA_AUTOFIRMA, SignedReceipt::why( 'too_big' ) );
		}
		// AutoScript ya deshace el base64 «url-safe» de AutoFirma; el strtr cubre a quien lo mande tal cual.
		$bytes = base64_decode( strtr( $b64, '-_', '+/' ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- lo que devuelve AutoFirma.
		return is_string( $bytes ) ? array( $bytes, self::VIA_AUTOFIRMA, '' ) : array( '', self::VIA_AUTOFIRMA, SignedReceipt::why( 'not_pdf' ) );
	}

	/**
	 * The signed PDF uploaded by hand.
	 *
	 * @return array{0:string, 1:string, 2:string} Bytes, way, and why not ('' when fine).
	 */
	private static function uploaded(): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- handle() comprobó el nonce; se sanea campo a campo justo debajo.
		$campo   = isset( $_FILES[ self::FIELD_FILE ] ) && is_array( $_FILES[ self::FIELD_FILE ] ) ? $_FILES[ self::FIELD_FILE ] : array();
		$fichero = array(
			'name'     => basename( sanitize_text_field( is_scalar( $campo['name'] ?? null ) ? (string) $campo['name'] : '' ) ),
			'tmp_name' => is_string( $campo['tmp_name'] ?? null ) ? $campo['tmp_name'] : '',
			'size'     => (int) ( $campo['size'] ?? 0 ),
			'error'    => (int) ( $campo['error'] ?? UPLOAD_ERR_NO_FILE ),
		);
		if ( UPLOAD_ERR_NO_FILE === $fichero['error'] ) {
			return array( '', self::VIA_UPLOAD, 'Elija el justificante firmado, en PDF, antes de subirlo.' );
		}
		// La misma política que el resto del almacén privado (ADR-0028): tamaño, errores y tipo por contenido.
		$porque = ApplicationFiles::refuse( $fichero );
		if ( '' !== $porque ) {
			return array( '', self::VIA_UPLOAD, 'file_too_big' === $porque ? SignedReceipt::why( 'too_big' ) : ApplicationFiles::why( array( $porque ) ) );
		}
		// `is_uploaded_file()` no hace falta: `tmp_name` lo pone PHP con el cuerpo de la petición (ver ApplicationFiles::from_request()).
		$bytes = file_get_contents( $fichero['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- el temporal de la subida, local.
		return array( is_string( $bytes ) ? $bytes : '', self::VIA_UPLOAD, '' );
	}

	/**
	 * Put the signed PDF in the private store and point the application at it.
	 *
	 * `add_post_meta( …, true )`, como {@see \Prc\Meta\MetaSwap} sin meta
	 * guardada: si otro envío la escribió antes, este no la pisa y su fichero
	 * se borra. Mira y luego inserta, así que dos envíos en el mismo instante
	 * aún pueden dejar dos filas; se lee la primera y sobra un fichero.
	 *
	 * @param int    $application_id Application ID.
	 * @param string $bytes          Signed PDF.
	 * @param int    $user_id        Who signed it.
	 * @param string $via            How it came.
	 * @return string Why it was not stored; '' when it was.
	 */
	private static function store( int $application_id, string $bytes, int $user_id, string $via ): string {
		$stored = ApplicationFiles::put( $bytes, 'pdf' );
		if ( '' === $stored ) {
			return 'No se ha podido guardar el justificante firmado. Vuelva a intentarlo.';
		}
		$firma = array(
			'id'        => bin2hex( random_bytes( 16 ) ),
			'name'      => 'justificante-solicitud-' . $application_id . '-firmado.pdf',
			'mime'      => 'application/pdf',
			'size'      => strlen( $bytes ),
			'sha256'    => hash( 'sha256', $bytes ),
			'stored'    => $stored,
			'signed_at' => time(),
			'signed_by' => $user_id,
			'via'       => $via,
		);
		if ( ! add_post_meta( $application_id, ApplicationMetaKeys::SIGNED_RECEIPT, $firma, true ) ) {
			ApplicationFiles::erase( $firma );
			return 'El justificante de esta solicitud ya está firmado.';
		}
		return '';
	}

	// ─── la descarga y el borrado ──────────────────────────────────────────

	/**
	 * Serve the signed receipt, when whoever asks may have it.
	 *
	 * @return void
	 */
	public static function serve(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- es una lectura; la autoriza ProcedureAccess::can_download_signed_receipt().
		$id = isset( $_GET[ self::ARG_DOWNLOAD ] ) && is_string( $_GET[ self::ARG_DOWNLOAD ] ) ? absint( wp_unslash( $_GET[ self::ARG_DOWNLOAD ] ) ) : 0;
		if ( $id <= 0 ) {
			return;
		}
		if ( ! ProcedureAccess::can_download_signed_receipt( get_current_user_id(), $id ) ) {
			ApplicationFiles::deny();
			return;
		}
		$firma = self::signed( $id );
		$bytes = array() === $firma ? null : ApplicationFiles::read( $firma );
		if ( null === $bytes ) {
			ApplicationFiles::deny( 404, 'Ese documento ya no está.' );
			return;
		}
		foreach ( ApplicationFiles::headers( $firma, strlen( $bytes ) ) as $linea ) {
			Shell::send_header( $linea );
		}
		echo $bytes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- es el fichero, no HTML: se sirve como descarga y con nosniff.
		Shell::leave();
	}

	/**
	 * Take the signed file with the application, when it really goes.
	 *
	 * @param int           $post_id Post being deleted for good.
	 * @param \WP_Post|null $post    Its object.
	 * @return void
	 */
	public static function on_delete( int $post_id, $post = null ): void {
		$tipo  = $post instanceof \WP_Post ? (string) $post->post_type : (string) get_post_type( $post_id );
		$firma = ApplicationPostType::POST_TYPE === $tipo ? self::signed( $post_id ) : array();
		if ( array() !== $firma ) {
			ApplicationFiles::erase( $firma );
		}
	}
}
