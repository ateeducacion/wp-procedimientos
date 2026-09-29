<?php
/**
 * Private documents attached to an application: store, authorise and serve.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\Access\ProcedureAccess;
use Prc\Domain\ProcedureQuestions;
use Prc\Meta\ApplicationMetaKeys;
use Prc\PostType\ApplicationPostType;

/**
 * Los documentos que aporta un centro con su solicitud.
 *
 * **Un documento de una solicitud no es un adjunto de WordPress** (ADR-0028).
 * Aquí no se llama a `wp_insert_attachment()`, ni a `media_handle_upload()`,
 * ni a `media_handle_sideload()`, y no se guarda ningún identificador de
 * adjunto. No es que se escondan después: es que **no existen**. Un adjunto
 * trae de serie la biblioteca de medios, su página propia, `wp/v2/media`, el
 * AJAX de medios y XML-RPC, y cada una de esas puertas habría que guardarla.
 * La que no se abre no se guarda.
 *
 * El reparto es el de la ADR-0028:
 *
 * - **Contenido público del sitio** —lo que se publica y se comparte— va por
 *   el camino normal de WordPress, con su biblioteca y su URL.
 * - **Documento aportado en una solicitud** → aquí, con nombre físico opaco,
 *   descriptor en la meta de su solicitud y descarga autorizada por el
 *   guardián del aplicativo.
 *
 * La política —qué tipos y qué tamaño— es **del aplicativo entero y no de cada
 * pregunta**: una pregunta de tipo `file` acepta un fichero y no configura
 * nada más. Está en {@see mimes()} y {@see max_bytes()}, en un solo sitio. Una
 * solicitud puede llevar varios documentos, uno por cada pregunta de ese tipo.
 */
final class ApplicationFiles {

	/**
	 * Field the application form sends its files in, keyed by question key.
	 */
	public const FIELD = 'prc_qf';

	/**
	 * Query argument carrying the application a download is about.
	 */
	public const ARG_APPLICATION = 'prc_app';

	/**
	 * Query argument carrying the opaque ID of the file being asked for.
	 */
	public const ARG_FILE = 'prc_file';

	/**
	 * Directory under `uploads/` where the private files live.
	 */
	public const DIR = 'prc-private';

	/**
	 * Hard ceiling for one file, in bytes.
	 *
	 * Diez mebibytes, y el límite real es el menor de este y el del servidor
	 * ({@see max_bytes()}): prometer más de lo que PHP acepta es un formulario
	 * que falla sin decir por qué. Es del aplicativo, no de la pregunta.
	 */
	public const MAX_BYTES = 10 * MB_IN_BYTES;

	/**
	 * Mode a stored file is left in: write, no read.
	 *
	 * El nombre aleatorio **no es la protección**, es lo que evita que el
	 * nombre cuente algo. Lo que protege es que el fichero no se pueda leer
	 * desde fuera del aplicativo, y para leerlo hay que abrirlo a propósito
	 * ({@see read()}).
	 */
	private const MODE_CLOSED = 0200;

	/**
	 * Mode while the application is reading one.
	 */
	private const MODE_OPEN = 0400;

	/**
	 * Shape a `stored` path may have, and no other.
	 *
	 * Se compone aquí —dos niveles de dos dígitos hexadecimales, el nombre y
	 * la extensión ya validada—, así que comprobarlo contra esta forma cierra
	 * el paso a `..`, a una ruta absoluta y a cualquier cosa que no hayamos
	 * escrito nosotros, sin depender de resolver enlaces.
	 */
	private const STORED_SHAPE = '#^[a-f0-9]{2}/[a-f0-9]{2}/[a-f0-9]{32}\.[a-z0-9]{1,8}$#';

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function register(): void {
		// En `init` 20, como el resto de manejadores: los tipos y sus
		// capacidades ya están registrados y todavía se pueden mandar
		// cabeceras.
		add_action( 'init', array( self::class, 'handle' ), 20 );
		// Solo el borrado definitivo. Borrar es enviar a la papelera
		// (ADR-0007), y una solicitud en la papelera se puede restaurar:
		// restaurarla sin sus documentos es restaurar otra cosa.
		add_action( 'before_delete_post', array( self::class, 'on_delete' ), 10, 2 );
	}

	// ─── la política, en un solo sitio ─────────────────────────────────────

	/**
	 * The file types a school may attach, extension => MIME.
	 *
	 * Lista cerrada y conservadora: documentos y fotografías, que es lo que se
	 * adjunta a una solicitud. **Nada que un navegador pueda ejecutar o
	 * interpretar** —SVG, HTML, JavaScript—, y nada empaquetado, que es un
	 * contenedor y no un documento. Si un procedimiento concreto necesitara
	 * otro formato, esa ampliación se decide a propósito y se escribe.
	 *
	 * @return array<string, string>
	 */
	public static function mimes(): array {
		$mimes = array(
			'pdf'          => 'application/pdf',
			'jpg|jpeg|jpe' => 'image/jpeg',
			'png'          => 'image/png',
			'docx'         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'odt'          => 'application/vnd.oasis.opendocument.text',
		);

		/**
		 * Filter the file types a school may attach to its application.
		 *
		 * Es la válvula para un despliegue que necesite uno más, y se audita
		 * porque se ve escrita. Lo que llegue se cruza igualmente con lo que
		 * diga `wp_check_filetype_and_ext()`.
		 *
		 * @param array<string, string> $mimes Extension pattern => MIME type.
		 */
		$mimes = apply_filters( 'prc_private_file_mimes', $mimes );
		return is_array( $mimes ) ? $mimes : array();
	}

	/**
	 * The biggest file that gets accepted, in bytes.
	 *
	 * @return int
	 */
	public static function max_bytes(): int {
		$servidor = (int) wp_max_upload_size();
		return $servidor > 0 ? min( self::MAX_BYTES, $servidor ) : self::MAX_BYTES;
	}

	/**
	 * Where the private files live, without a trailing slash.
	 *
	 * Un subdirectorio propio de `uploads/` y **nunca mezclado con los medios
	 * públicos**: así una copia de seguridad lo incluye sin pensar, y una
	 * regla del servidor que lo cierre es una sola regla.
	 *
	 * @return string Empty when `uploads/` is not usable.
	 */
	public static function root(): string {
		$uploads = wp_upload_dir();
		$base    = isset( $uploads['basedir'] ) && ! $uploads['error'] ? (string) $uploads['basedir'] : '';
		$raiz    = '' === $base ? '' : $base . '/' . self::DIR;

		/**
		 * Filter the directory the private application files live in.
		 *
		 * @param string $raiz Absolute path, no trailing slash.
		 */
		return rtrim( (string) apply_filters( 'prc_private_files_dir', $raiz ), '/' );
	}

	// ─── lo que manda el formulario ────────────────────────────────────────

	/**
	 * The files this request brings for the `file` questions of the procedure.
	 *
	 * **El único sitio del aplicativo que lee `$_FILES`.** Lo de dentro se
	 * comprueba entero antes de guardar nada: si una pregunta obligatoria se
	 * queda sin documento, o el que viene no pasa la política, la solicitud no
	 * se guarda (ADR-0028).
	 *
	 * Una solicitud se edita mientras el plazo siga abierto (ADR-0019), así
	 * que `$stored` dice qué documentos ya tiene: volver a enviar el
	 * formulario sin tocar el campo de fichero **no** vacía lo que ya se
	 * presentó ni salta la comprobación de obligatoriedad.
	 *
	 * @param array<int, array<string, mixed>>    $questions Normalised questions.
	 * @param array<string, array<string, mixed>> $stored    Descriptors the application already has.
	 * @return array{ok:bool, errors:string[], files:array<string, array<string, mixed>>}
	 */
	public static function submitted( array $questions, array $stored = array() ): array {
		$errores = array();
		$traidos = array();

		foreach ( $questions as $pregunta ) {
			if ( ProcedureQuestions::TYPE_FILE !== ( $pregunta['type'] ?? '' ) ) {
				continue;
			}
			$key     = (string) ( $pregunta['key'] ?? '' );
			$fichero = self::from_request( $key );

			if ( null === $fichero ) {
				if ( ! empty( $pregunta['required'] ) && ! isset( $stored[ $key ] ) ) {
					$errores[] = 'file_missing';
				}
				continue;
			}

			$porque = self::refuse( $fichero );
			if ( '' !== $porque ) {
				$errores[] = $porque;
				continue;
			}
			$traidos[ $key ] = $fichero;
		}

		return array(
			'ok'     => array() === $errores,
			'errors' => $errores,
			'files'  => $traidos,
		);
	}

	/**
	 * One entry of `$_FILES`, normalised, or nothing when none was sent.
	 *
	 * @param string $question_key Question key.
	 * @return array{name:string, tmp_name:string, size:int, error:int}|null
	 */
	private static function from_request( string $question_key ): ?array {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- el nonce lo comprobó ApplyForm::handle().
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- se sanea campo a campo justo debajo.
		$campo = isset( $_FILES[ self::FIELD ] ) && is_array( $_FILES[ self::FIELD ] ) ? $_FILES[ self::FIELD ] : array();
		if ( ! isset( $campo['name'][ $question_key ] ) || ! is_scalar( $campo['name'][ $question_key ] ) ) {
			return null;
		}

		$nombre = sanitize_text_field( (string) $campo['name'][ $question_key ] );
		$tmp    = isset( $campo['tmp_name'][ $question_key ] ) ? (string) $campo['tmp_name'][ $question_key ] : '';
		$error  = isset( $campo['error'][ $question_key ] ) ? (int) $campo['error'][ $question_key ] : UPLOAD_ERR_NO_FILE;
		$tamano = isset( $campo['size'][ $question_key ] ) ? (int) $campo['size'][ $question_key ] : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( '' === $nombre && UPLOAD_ERR_NO_FILE === $error ) {
			return null;
		}

		// `is_uploaded_file()` **no se llama aquí, y no es un olvido**: esta
		// función lee `$_FILES` y nada más, y `$_FILES` lo rellena PHP con el
		// cuerpo de la petición —no hay sintaxis con la que quien llama meta
		// una ruta suya en `tmp_name`—. Esa comprobación protege a las APIs
		// que reciben el array ya montado por otro código, que no es el caso:
		// esto es privado y solo lo llama {@see submitted()}.
		//
		// El nombre que llega sí es del navegador: se queda solo con la última
		// parte, así que «../../evil.pdf» es «evil.pdf» y nada más.
		return array(
			'name'     => basename( $nombre ),
			'tmp_name' => $tmp,
			'size'     => $tamano,
			'error'    => $error,
		);
	}

	/**
	 * Why one submitted file is not acceptable, '' when it is.
	 *
	 * Del `type` que manda el navegador **no se fía nadie**: lo dice quien
	 * sube el fichero. Quien decide es `wp_check_filetype_and_ext()`, que mira
	 * lo que hay dentro del fichero y lo cruza con la extensión, y por encima
	 * nuestra propia lista cerrada.
	 *
	 * @param array{name:string, tmp_name:string, size:int, error:int} $fichero Normalised upload.
	 * @return string Error code.
	 */
	public static function refuse( array $fichero ): string {
		if ( UPLOAD_ERR_INI_SIZE === $fichero['error'] || UPLOAD_ERR_FORM_SIZE === $fichero['error'] ) {
			return 'file_too_big';
		}
		if ( UPLOAD_ERR_OK !== $fichero['error'] || '' === $fichero['tmp_name'] ) {
			return 'file_broken';
		}
		if ( $fichero['size'] <= 0 || $fichero['size'] > self::max_bytes() ) {
			return 'file_too_big';
		}

		$mimes    = self::mimes();
		$revisado = wp_check_filetype_and_ext( $fichero['tmp_name'], $fichero['name'], $mimes );
		$tipo     = isset( $revisado['type'] ) && is_string( $revisado['type'] ) ? $revisado['type'] : '';
		$ext      = isset( $revisado['ext'] ) && is_string( $revisado['ext'] ) ? $revisado['ext'] : '';

		if ( '' === $tipo || '' === $ext || ! in_array( $tipo, array_values( $mimes ), true ) ) {
			return 'file_type';
		}
		return '';
	}

	// ─── guardar ───────────────────────────────────────────────────────────

	/**
	 * Store every file of one application, or leave nothing behind.
	 *
	 * Todo o nada: si uno falla, se borran los que acababan de guardarse y se
	 * dice que no, **sin tocar los descriptores que ya había**. Lo que no
	 * puede quedar es un descriptor apuntando a nada ni un fichero suelto sin
	 * solicitud.
	 *
	 * Al sustituir un documento el orden es siempre el mismo: se guarda el
	 * nuevo, se actualiza el descriptor y solo entonces se borra el viejo.
	 * Nunca al revés.
	 *
	 * @param int                                 $application_id Application post ID.
	 * @param array<string, array<string, mixed>> $files          What submitted() returned.
	 * @return bool
	 */
	public static function store_all( int $application_id, array $files ): bool {
		if ( $application_id <= 0 ) {
			return false;
		}
		if ( array() === $files ) {
			return true;
		}

		$nuevos = array();
		foreach ( $files as $question_key => $fichero ) {
			$descriptor = self::store( $fichero );
			if ( null === $descriptor ) {
				foreach ( $nuevos as $hecho ) {
					self::erase( $hecho );
				}
				return false;
			}
			$nuevos[ (string) $question_key ] = $descriptor;
		}

		$antes = self::descriptors( $application_id );
		update_post_meta( $application_id, ApplicationMetaKeys::FILES, wp_slash( array_merge( $antes, $nuevos ) ) );

		// Y ahora, con el descriptor nuevo ya guardado, el fichero al que
		// ninguno apunta.
		foreach ( $nuevos as $key => $descriptor ) {
			if ( isset( $antes[ $key ] ) && $antes[ $key ]['stored'] !== $descriptor['stored'] ) {
				self::erase( $antes[ $key ] );
			}
		}
		return true;
	}

	/**
	 * Put one validated file in the private store and describe it.
	 *
	 * El nombre físico es **aleatorio y nada más**: no lleva el nombre del
	 * centro, ni su código, ni el de quien solicita, ni el del procedimiento,
	 * ni el nombre original del fichero. La extensión sale del tipo ya
	 * validado, no de lo que venía escrito.
	 *
	 * @param array{name:string, tmp_name:string, size:int, error:int} $fichero Normalised upload.
	 * @return array<string, mixed>|null Descriptor, or null when it could not be stored.
	 */
	private static function store( array $fichero ): ?array {
		if ( '' !== self::refuse( $fichero ) ) {
			return null;
		}

		$raiz = self::root();
		$fs   = self::filesystem();
		if ( '' === $raiz || null === $fs ) {
			return null;
		}

		$revisado = wp_check_filetype_and_ext( $fichero['tmp_name'], $fichero['name'], self::mimes() );
		$ext      = strtolower( (string) $revisado['ext'] );
		$mime     = (string) $revisado['type'];

		$bytes = $fs->get_contents( $fichero['tmp_name'] );
		if ( ! is_string( $bytes ) || '' === $bytes ) {
			return null;
		}

		$opaco  = bin2hex( random_bytes( 16 ) );
		$stored = substr( $opaco, 0, 2 ) . '/' . substr( $opaco, 2, 2 ) . '/' . $opaco . '.' . $ext;
		$camino = $raiz . '/' . $stored;

		if ( ! self::prepare_dir( dirname( $camino ) ) ) {
			return null;
		}
		if ( ! $fs->put_contents( $camino, $bytes, self::MODE_CLOSED ) ) {
			return null;
		}
		// Y se comprueba que quedó cerrado de verdad: guardar y dejarlo
		// legible es peor que no guardarlo, porque no se nota.
		$fs->chmod( $camino, self::MODE_CLOSED );

		return array(
			'id'     => bin2hex( random_bytes( 16 ) ),
			'name'   => sanitize_file_name( $fichero['name'] ),
			'mime'   => $mime,
			'size'   => strlen( $bytes ),
			'sha256' => hash( 'sha256', $bytes ),
			'stored' => $stored,
		);
	}

	/**
	 * Create the directory of a file, with its deny rules the first time.
	 *
	 * El `.htaccess` es un cinturón, no el pantalón: **nginx no lo lee**. Lo
	 * que cierra el paso es el modo del fichero y que la descarga pase por el
	 * aplicativo; esto se pone porque en Apache es gratis (ADR-0028).
	 *
	 * @param string $dir Absolute directory.
	 * @return bool
	 */
	private static function prepare_dir( string $dir ): bool {
		$raiz = self::root();
		$fs   = self::filesystem();
		if ( '' === $raiz || null === $fs ) {
			return false;
		}

		if ( ! $fs->is_dir( $raiz ) && ! wp_mkdir_p( $raiz ) ) {
			return false;
		}
		if ( ! $fs->exists( $raiz . '/.htaccess' ) ) {
			$fs->put_contents(
				$raiz . '/.htaccess',
				"# Los documentos de las solicitudes no se sirven directamente (ADR-0028).\n"
					. "<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n"
					. "<IfModule !mod_authz_core.c>\n\tDeny from all\n</IfModule>\n",
				FS_CHMOD_FILE
			);
		}
		if ( ! $fs->exists( $raiz . '/index.php' ) ) {
			$fs->put_contents( $raiz . '/index.php', "<?php\n// Silence is golden.\n", FS_CHMOD_FILE );
		}

		return $fs->is_dir( $dir ) || wp_mkdir_p( $dir );
	}

	// ─── leer ──────────────────────────────────────────────────────────────

	/**
	 * The descriptors of one application, keyed by question key.
	 *
	 * @param int $application_id Application post ID.
	 * @return array<string, array<string, mixed>>
	 */
	public static function descriptors( int $application_id ): array {
		if ( $application_id <= 0 ) {
			return array();
		}
		$datos = get_post_meta( $application_id, ApplicationMetaKeys::FILES, true );
		if ( ! is_array( $datos ) ) {
			return array();
		}

		$out = array();
		foreach ( $datos as $question_key => $descriptor ) {
			if ( is_array( $descriptor ) && isset( $descriptor['id'], $descriptor['stored'] ) ) {
				$out[ (string) $question_key ] = $descriptor;
			}
		}
		return $out;
	}

	/**
	 * The descriptor with this opaque ID, or nothing.
	 *
	 * @param int    $application_id Application post ID.
	 * @param string $file_id        Opaque file ID.
	 * @return array<string, mixed>|null
	 */
	public static function find( int $application_id, string $file_id ): ?array {
		if ( ! (bool) preg_match( '/^[a-f0-9]{32}$/', $file_id ) ) {
			return null;
		}
		foreach ( self::descriptors( $application_id ) as $descriptor ) {
			if ( hash_equals( (string) $descriptor['id'], $file_id ) ) {
				return $descriptor;
			}
		}
		return null;
	}

	/**
	 * The bytes of one stored file.
	 *
	 * Se abre a propósito y se vuelve a cerrar en el `finally`: el fichero
	 * pasa el resto de su vida sin permiso de lectura.
	 *
	 * @param array<string, mixed> $descriptor Stored descriptor.
	 * @return string|null Null when it is not there or cannot be read.
	 */
	public static function read( array $descriptor ): ?string {
		$camino = self::path( $descriptor );
		$fs     = self::filesystem();
		if ( '' === $camino || null === $fs || ! $fs->exists( $camino ) ) {
			return null;
		}

		try {
			$fs->chmod( $camino, self::MODE_OPEN );
			$bytes = $fs->get_contents( $camino );
		} finally {
			$fs->chmod( $camino, self::MODE_CLOSED );
		}
		return is_string( $bytes ) ? $bytes : null;
	}

	/**
	 * The absolute path of a descriptor, '' when it is not one of ours.
	 *
	 * Dos comprobaciones y las dos hacen falta: la **forma** de `stored`, que
	 * no admite ni `..` ni una ruta absoluta, y que lo compuesto siga colgando
	 * de la raíz privada.
	 *
	 * @param array<string, mixed> $descriptor Stored descriptor.
	 * @return string
	 */
	public static function path( array $descriptor ): string {
		$stored = isset( $descriptor['stored'] ) ? (string) $descriptor['stored'] : '';
		$raiz   = self::root();
		if ( '' === $raiz || ! (bool) preg_match( self::STORED_SHAPE, $stored ) ) {
			return '';
		}
		$camino = $raiz . '/' . $stored;
		return 0 === strpos( $camino, $raiz . '/' ) ? $camino : '';
	}

	// ─── borrar ────────────────────────────────────────────────────────────

	/**
	 * Take the private files of an application with it, when it really goes.
	 *
	 * @param int           $post_id Post being deleted for good.
	 * @param \WP_Post|null $post    Its object.
	 * @return void
	 */
	public static function on_delete( int $post_id, $post = null ): void {
		$tipo = $post instanceof \WP_Post ? (string) $post->post_type : (string) get_post_type( $post_id );
		if ( ApplicationPostType::POST_TYPE !== $tipo ) {
			return;
		}
		self::delete_all( $post_id );
	}

	/**
	 * Erase every private file of one application, and its descriptors.
	 *
	 * @param int $application_id Application post ID.
	 * @return void
	 */
	public static function delete_all( int $application_id ): void {
		foreach ( self::descriptors( $application_id ) as $descriptor ) {
			self::erase( $descriptor );
		}
		delete_post_meta( $application_id, ApplicationMetaKeys::FILES );
	}

	/**
	 * Erase one stored file.
	 *
	 * @param array<string, mixed> $descriptor Stored descriptor.
	 * @return void
	 */
	private static function erase( array $descriptor ): void {
		$camino = self::path( $descriptor );
		$fs     = self::filesystem();
		if ( '' !== $camino && null !== $fs && $fs->exists( $camino ) ) {
			$fs->delete( $camino );
		}
	}

	// ─── la descarga ───────────────────────────────────────────────────────

	/**
	 * The application URL that serves one file.
	 *
	 * Nunca la del fichero: `stored` no se convierte en URL en ningún sitio,
	 * y esta dirección no dice dónde está nada (ADR-0028).
	 *
	 * @param int    $application_id Application post ID.
	 * @param string $file_id        Opaque file ID.
	 * @return string
	 */
	public static function url( int $application_id, string $file_id ): string {
		return add_query_arg(
			array(
				self::ARG_APPLICATION => $application_id,
				self::ARG_FILE        => $file_id,
			),
			home_url( '/' )
		);
	}

	/**
	 * Serve one private file, when whoever is asking may have it.
	 *
	 * El identificador del fichero **no autoriza nada por sí solo**: quien
	 * decide es la política de solicitudes que ya existe,
	 * {@see ProcedureAccess::can_view_application()}, sobre **esta** solicitud.
	 * No se inventa aquí ningún sistema de roles, y no vale una capacidad
	 * genérica: el centro que la presentó ve la suya, y quien revisa ese
	 * procedimiento ve las de su ámbito.
	 *
	 * @return void
	 */
	public static function handle(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- es una lectura, y la autorización es la política de solicitudes, no un nonce.
		$file_id = isset( $_GET[ self::ARG_FILE ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::ARG_FILE ] ) ) : '';
		if ( '' === $file_id ) {
			return;
		}
		$application_id = isset( $_GET[ self::ARG_APPLICATION ] ) ? absint( wp_unslash( $_GET[ self::ARG_APPLICATION ] ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( $application_id <= 0
			|| ApplicationPostType::POST_TYPE !== (string) get_post_type( $application_id )
			|| ! ProcedureAccess::can_view_application( get_current_user_id(), $application_id ) ) {
			self::deny();
			return;
		}

		$descriptor = self::find( $application_id, $file_id );
		$bytes      = null === $descriptor ? null : self::read( $descriptor );
		if ( null === $descriptor || null === $bytes ) {
			self::deny( 404, 'Ese documento ya no está.' );
			return;
		}

		foreach ( self::headers( $descriptor, strlen( $bytes ) ) as $linea ) {
			Shell::send_header( $linea );
		}
		echo $bytes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- es el fichero, no HTML: se sirve como descarga y con nosniff.
		Shell::leave();
	}

	/**
	 * The response headers of a download, in order.
	 *
	 * Siempre como descarga y nunca en línea: el navegador no interpreta nada
	 * de lo que se sirve desde aquí. `nosniff` cierra el paso a que lo adivine
	 * por su cuenta, y `no-store` a que quede en una caché compartida.
	 *
	 * Aparte del manejador para poder comprobarlas: en la línea de órdenes la
	 * salida ya empezó y `header()` no llega a mandarse, así que un test que
	 * mire las cabeceras de verdad no comprobaría nada.
	 *
	 * @param array<string, mixed> $descriptor Stored descriptor.
	 * @param int                  $bytes      Length of the body.
	 * @return string[]
	 */
	public static function headers( array $descriptor, int $bytes ): array {
		$nombre = sanitize_file_name( (string) ( $descriptor['name'] ?? '' ) );
		if ( '' === $nombre ) {
			$nombre = 'documento';
		}

		return array(
			'Content-Type: ' . sanitize_mime_type( (string) ( $descriptor['mime'] ?? '' ) ),
			'Content-Disposition: attachment; filename="' . $nombre . '"',
			'Content-Length: ' . $bytes,
			'X-Content-Type-Options: nosniff',
			'Cache-Control: private, no-store',
		);
	}

	/**
	 * Refuse, without saying whether the file exists.
	 *
	 * @param int    $codigo HTTP status.
	 * @param string $texto  What to say.
	 * @return void
	 */
	private static function deny( int $codigo = 403, string $texto = 'No puede descargar este documento.' ): void {
		status_header( $codigo );
		Shell::send_header( 'Content-Type: text/plain; charset=utf-8' );
		Shell::send_header( 'X-Content-Type-Options: nosniff' );
		Shell::send_header( 'Cache-Control: private, no-store' );
		echo esc_html( $texto );
		Shell::leave();
	}

	// ─── lo que se dice en pantalla ────────────────────────────────────────

	/**
	 * Why the documents of an application were refused, in Spanish.
	 *
	 * @param string[] $errors Error codes.
	 * @return string Empty when there is nothing to say.
	 */
	public static function why( array $errors ): string {
		$textos = array(
			'file_missing' => 'Falta un documento obligatorio.',
			'file_too_big' => 'El documento es demasiado grande: el máximo son ' . size_format( self::max_bytes() ) . '.',
			'file_type'    => 'Ese tipo de documento no se admite. Se aceptan PDF, JPG, PNG, DOCX y ODT.',
			'file_broken'  => 'El documento no ha llegado completo. Vuelva a adjuntarlo.',
		);

		foreach ( $errors as $error ) {
			if ( isset( $textos[ $error ] ) ) {
				return $textos[ $error ];
			}
		}
		return '';
	}

	/**
	 * The WordPress filesystem API, ready to use.
	 *
	 * @return \WP_Filesystem_Base|null
	 */
	private static function filesystem(): ?\WP_Filesystem_Base {
		global $wp_filesystem;
		if ( ! $wp_filesystem instanceof \WP_Filesystem_Base ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}
		return $wp_filesystem instanceof \WP_Filesystem_Base ? $wp_filesystem : null;
	}
}
