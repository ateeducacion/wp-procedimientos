<?php
/**
 * The poster of a procedure, chosen from «Datos» (#126).
 *
 * @package Prc
 */

namespace Prc\PublicFront;

/**
 * El cartel es la imagen destacada del procedimiento: contenido público del
 * sitio, por el camino normal de WordPress (ADR-0028 solo aparta los
 * documentos de las solicitudes). Lo que no hace es pedir `upload_files`:
 * quien gestiona sube aquí una imagen y el taller la guarda en medios por su
 * cuenta, sin abrirle la biblioteca del sitio. Quién puede, lo decide
 * `ProcedureAccess::can_edit()` en {@see ProcedureEditor::handle()}.
 *
 * Al cambiarlo o quitarlo, la imagen anterior se queda en medios: un
 * duplicado comparte la misma (`ProcedureDuplicate`). Por eso el texto
 * alternativo solo se escribe en una imagen que es de este procedimiento y de
 * ningún otro: el de una compartida cambiaría también la ficha del otro.
 */
final class ProcedurePoster {

	/**
	 * Operation of the section.
	 */
	public const OP = 'poster';

	/**
	 * Anchor of the section inside «Datos».
	 */
	public const SECTION = 'prc-cartel';

	/**
	 * Notice code after saving.
	 */
	public const NOTICE = 'cartel';

	/**
	 * Fields of the section.
	 */
	public const FIELD_FILE   = 'prc_poster';
	public const FIELD_ALT    = 'prc_poster_alt';
	public const FIELD_REMOVE = 'prc_poster_remove';

	/**
	 * The biggest image accepted, in bytes, before the server's own limit.
	 */
	public const MAX_BYTES = 5 * MB_IN_BYTES;

	/**
	 * Names of the accepted types, for the messages.
	 */
	private const TYPE_NAMES = array(
		'image/jpeg' => 'JPEG',
		'image/png'  => 'PNG',
		'image/webp' => 'WebP',
	);

	/**
	 * What may be uploaded: extension pattern => MIME type.
	 */
	public const MIMES = array(
		'jpg|jpeg|jpe' => 'image/jpeg',
		'png'          => 'image/png',
		'webp'         => 'image/webp',
	);

	/**
	 * The biggest image accepted here: ours, or less if the server takes less.
	 *
	 * `wp_max_upload_size()` ya trae el límite de PHP y, en un multisitio, el
	 * `fileupload_maxk` y la cuota que quedan del subsitio.
	 *
	 * @return int
	 */
	public static function max_bytes(): int {
		$servidor = (int) wp_max_upload_size();
		return $servidor > 0 ? min( self::MAX_BYTES, $servidor ) : self::MAX_BYTES;
	}

	/**
	 * The accepted types that this site also allows: extension pattern => MIME type.
	 *
	 * @return array<string,string>
	 */
	public static function mimes(): array {
		return array_intersect( self::MIMES, get_allowed_mime_types() );
	}

	/**
	 * What may be uploaded, for people: «JPEG, PNG o WebP».
	 *
	 * @return string
	 */
	public static function types_label(): string {
		$nombres = array_values( array_intersect_key( self::TYPE_NAMES, array_flip( self::mimes() ) ) );
		$ultimo  = array_pop( $nombres );
		return array() === $nombres ? (string) $ultimo : implode( ', ', $nombres ) . ' o ' . $ultimo;
	}

	/**
	 * The current poster, for the section.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return array{url:string, alt:string}
	 */
	public static function summary( int $procedure_id ): array {
		$imagen = (int) get_post_thumbnail_id( $procedure_id );
		return array(
			'url' => $imagen > 0 ? (string) wp_get_attachment_image_url( $imagen, 'medium' ) : '',
			'alt' => $imagen > 0 ? (string) get_post_meta( $imagen, '_wp_attachment_image_alt', true ) : '',
		);
	}

	/**
	 * Save the section: a new image, only its alternative text, or no poster.
	 *
	 * @param int $procedure_id Procedure post ID; whoever sends it may edit it.
	 * @return string Why nothing was saved; on success it leaves and does not return.
	 */
	public static function save( int $procedure_id ): string {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- ProcedureEditor::handle() comprobó el nonce de esta operación.
		$quitar = ! empty( $_POST[ self::FIELD_REMOVE ] );
		$alt    = sanitize_text_field( wp_unslash( (string) ( $_POST[ self::FIELD_ALT ] ?? '' ) ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		$fichero = self::uploaded();
		if ( $quitar && null !== $fichero ) {
			return 'Ha elegido una imagen y a la vez «Quitar el cartel». Haga solo una de las dos cosas.';
		}
		if ( $quitar ) {
			delete_post_thumbnail( $procedure_id );
			self::leave( $procedure_id );
			return '';
		}

		if ( null !== $fichero ) {
			$imagen = self::store( $procedure_id, $fichero );
			if ( is_string( $imagen ) ) {
				return $imagen;
			}
			set_post_thumbnail( $procedure_id, $imagen );
		}
		$imagen = (int) get_post_thumbnail_id( $procedure_id );
		if ( $imagen <= 0 ) {
			return 'Elija una imagen para el cartel antes de guardar.';
		}
		if ( (string) get_post_meta( $imagen, '_wp_attachment_image_alt', true ) !== $alt ) {
			if ( ! self::owns( $procedure_id, $imagen ) ) {
				return 'Esta imagen la comparte con otro procedimiento, y su texto alternativo cambiaría también allí. Para cambiarlo, vuelva a subir la imagen.';
			}
			update_post_meta( $imagen, '_wp_attachment_image_alt', $alt );
		}
		self::leave( $procedure_id );
		return '';
	}

	/**
	 * Whether the image hangs from this procedure and no other post uses it.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @param int $imagen       Attachment ID.
	 * @return bool
	 */
	private static function owns( int $procedure_id, int $imagen ): bool {
		if ( (int) get_post_field( 'post_parent', $imagen ) !== $procedure_id ) {
			return false;
		}
		$otros = get_posts(
			array(
				'post_type'        => 'any',
				'post_status'      => 'any',
				'fields'           => 'ids',
				'posts_per_page'   => 1,
				'post__not_in'     => array( $procedure_id ),
				'meta_key'         => '_thumbnail_id', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- solo al cambiar el texto alternativo.
				'meta_value'       => (string) $imagen, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- ídem.
				'suppress_filters' => true,
			)
		);
		return array() === $otros;
	}

	/**
	 * The uploaded image, normalised, or null when none was sent.
	 *
	 * @return array{name:string, tmp_name:string, size:int, error:int}|null
	 */
	private static function uploaded(): ?array {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- el nonce, en handle(); se sanea campo a campo justo debajo.
		$campo = isset( $_FILES[ self::FIELD_FILE ] ) && is_array( $_FILES[ self::FIELD_FILE ] ) ? $_FILES[ self::FIELD_FILE ] : array();
		$error = (int) ( $campo['error'] ?? UPLOAD_ERR_NO_FILE );
		if ( UPLOAD_ERR_NO_FILE === $error ) {
			return null;
		}
		// `is_uploaded_file()` no hace falta: `tmp_name` lo pone PHP con el cuerpo de la petición (ver ApplicationFiles::from_request()).
		return array(
			'name'     => basename( sanitize_file_name( is_scalar( $campo['name'] ?? null ) ? (string) $campo['name'] : '' ) ),
			'tmp_name' => is_string( $campo['tmp_name'] ?? null ) ? $campo['tmp_name'] : '',
			'size'     => (int) ( $campo['size'] ?? 0 ),
			'error'    => $error,
		);
	}

	/**
	 * Check the image and put it in the media library, hanging from the procedure.
	 *
	 * El tipo lo decide `wp_check_filetype_and_ext()`, que mira lo que hay
	 * dentro del fichero, contra nuestra lista cerrada: el `type` del
	 * navegador no cuenta.
	 *
	 * @param int                                                      $procedure_id Procedure post ID.
	 * @param array{name:string, tmp_name:string, size:int, error:int} $fichero      Normalised upload.
	 * @return int|string Attachment ID, or why it was refused.
	 */
	private static function store( int $procedure_id, array $fichero ) {
		$tipo = self::check( $fichero );
		if ( is_string( $tipo ) ) {
			return $tipo;
		}
		$nombre = '' !== (string) $tipo['proper_filename'] ? (string) $tipo['proper_filename'] : $fichero['name'];
		$bytes  = file_get_contents( $fichero['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- el temporal de la subida, local.
		$subido = is_string( $bytes ) && '' !== $bytes ? wp_upload_bits( $nombre, null, $bytes ) : array( 'error' => true );
		if ( ! empty( $subido['error'] ) ) {
			return 'No se ha podido guardar el cartel. Vuelva a intentarlo.';
		}
		$imagen = wp_insert_attachment(
			array(
				'guid'           => $subido['url'],
				'post_mime_type' => '' !== (string) ( $subido['type'] ?? '' ) ? $subido['type'] : $tipo['type'],
				'post_title'     => sanitize_text_field( pathinfo( $nombre, PATHINFO_FILENAME ) ),
				'post_status'    => 'inherit',
			),
			$subido['file'],
			$procedure_id,
			true
		);
		if ( is_wp_error( $imagen ) ) {
			wp_delete_file( $subido['file'] );
			return 'No se ha podido guardar el cartel. Vuelva a intentarlo.';
		}
		require_once ABSPATH . 'wp-admin/includes/image.php';
		wp_update_attachment_metadata( $imagen, wp_generate_attachment_metadata( $imagen, $subido['file'] ) );
		return (int) $imagen;
	}

	/**
	 * What the upload really is, or why it is refused.
	 *
	 * @param array{name:string, tmp_name:string, size:int, error:int} $fichero Normalised upload.
	 * @return array{ext:string|false, type:string|false, proper_filename:string|false}|string
	 */
	private static function check( array $fichero ) {
		if ( UPLOAD_ERR_INI_SIZE === $fichero['error'] || UPLOAD_ERR_FORM_SIZE === $fichero['error'] || $fichero['size'] > self::max_bytes() ) {
			return 'El cartel ocupa más de ' . size_format( self::max_bytes() ) . '. Redúzcalo y vuelva a subirlo.';
		}
		if ( UPLOAD_ERR_OK !== $fichero['error'] || '' === $fichero['tmp_name'] || ! is_readable( $fichero['tmp_name'] ) ) {
			return 'No se ha podido subir la imagen. Vuelva a intentarlo.';
		}
		// Solo los tipos que este sitio admite (en un multisitio, `upload_filetypes`): `wp_upload_bits()` rechazaría los demás.
		$tipo = wp_check_filetype_and_ext( $fichero['tmp_name'], $fichero['name'], self::mimes() );
		if ( empty( $tipo['type'] ) || ! in_array( $tipo['type'], self::mimes(), true ) || ! wp_getimagesize( $fichero['tmp_name'] ) ) {
			return 'El cartel tiene que ser una imagen ' . self::types_label() . '.';
		}
		return $tipo;
	}

	/**
	 * Back to «Datos», at the section, with the notice.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return void
	 */
	private static function leave( int $procedure_id ): void {
		Shell::leave( ProcedureEditor::url( $procedure_id, ProcedureEditor::PANEL_DATA, array( ProcedureEditor::ARG_NOTICE => self::NOTICE ) ) . '#' . self::SECTION );
	}
}
