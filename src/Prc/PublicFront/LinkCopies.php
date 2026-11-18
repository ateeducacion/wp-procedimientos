<?php
/**
 * Private copy of the PDF behind each link of the procedure (ADR-0048).
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\Access\ProcedureAccess;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ProcedurePostType;

/**
 * Al guardar «Enlaces», se descarga el PDF de cada enlace y se guarda en el
 * almacén privado de {@see ApplicationFiles}, con nombre opaco y sin pasar
 * por la biblioteca de medios. En público se sigue enlazando la dirección
 * oficial; la copia es para cuando esa dirección muera.
 *
 * Sin cron: la copia se toma al guardar, y se reintenta al volver a guardar
 * mientras no la haya.
 */
final class LinkCopies {

	/**
	 * Query arg: the procedure whose copy is asked for.
	 */
	public const ARG_PROCEDURE = 'prc_link_procedure';

	/**
	 * Query arg: the meta key of the link.
	 */
	public const ARG_LINK = 'prc_link_copy';

	/**
	 * Query arg of the return URL: why a copy failed, by link.
	 */
	public const ARG_FAILED = 'prc_sin_copia';

	/**
	 * The biggest PDF that gets copied, in bytes.
	 */
	public const MAX_BYTES = 20 * MB_IN_BYTES;

	/**
	 * Seconds to wait for the official site.
	 */
	public const TIMEOUT = 15;

	/**
	 * Seconds for every download of one save together.
	 *
	 * Por debajo de los 30 s por defecto de `max_execution_time`: tres
	 * enlaces lentos no tumban el guardado; los que no caben se avisan y se
	 * reintentan al guardar otra vez.
	 */
	public const BUDGET = 20;

	/**
	 * Why a copy was not saved, by reason code.
	 */
	private const WHY = array(
		'red'     => 'el enlace no respondió o devolvió un error',
		'pdf'     => 'lo que hay en el enlace no es un PDF',
		'grande'  => 'el PDF ocupa más de 20 MB',
		'almacen' => 'no se pudo escribir en el almacén privado',
	);

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'handle' ), 20 );
		add_action( 'before_delete_post', array( self::class, 'on_delete' ), 10, 2 );
	}

	/**
	 * The three links that get a copy, with what the copy is called.
	 *
	 * @return array<string, array{0:string, 1:string}> Meta key => [label, file name].
	 */
	public static function links(): array {
		return array(
			ProcedureMetaKeys::RESOLUTION_URL       => array( 'de la resolución', 'resolucion.pdf' ),
			ProcedureMetaKeys::PROVISIONAL_LIST_URL => array( 'del listado provisional', 'listado-provisional.pdf' ),
			ProcedureMetaKeys::FINAL_LIST_URL       => array( 'del listado definitivo', 'listado-definitivo.pdf' ),
		);
	}

	/**
	 * The stored copies of a procedure, by link meta key.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return array<string, array{url:string, stored:string, saved_at:int, size:int}>
	 */
	public static function copies( int $procedure_id ): array {
		$copies = get_post_meta( $procedure_id, ProcedureMetaKeys::LINK_COPIES, true );
		return is_array( $copies ) ? $copies : array();
	}

	/**
	 * Bring the copies in line with the links just saved.
	 *
	 * Enlace vacío: fuera la copia. Enlace con copia de esa misma dirección:
	 * nada que hacer. Si no, se descarga; si falla, la copia anterior se
	 * conserva —puede ser la única que queda de un documento retirado—.
	 *
	 * @param int                   $procedure_id Procedure post ID.
	 * @param array<string, string> $urls         Link meta key => URL as saved.
	 * @return array<string, string> Link meta key => reason code, for the links whose copy failed.
	 */
	public static function sync( int $procedure_id, array $urls ): array {
		$failed   = array();
		$deadline = time() + self::BUDGET;
		foreach ( array_intersect_key( $urls, self::links() ) as $key => $url ) {
			$url     = esc_url_raw( $url );
			$current = self::copies( $procedure_id )[ $key ] ?? null;
			if ( '' === $url ) {
				if ( null !== $current ) {
					self::write( $procedure_id, $key, null );
					ApplicationFiles::erase( $current );
				}
				continue;
			}
			// Misma dirección y el fichero sigue ahí: nada que hacer. Si se
			// perdió el fichero, se vuelve a descargar.
			$path = null === $current ? '' : ApplicationFiles::path( $current );
			if ( null !== $current && $current['url'] === $url && '' !== $path && file_exists( $path ) ) {
				continue;
			}
			$bytes = self::fetch( $url, $deadline - time() );
			if ( isset( self::WHY[ $bytes ] ) ) {
				$failed[ $key ] = $bytes;
				continue;
			}
			$stored = ApplicationFiles::put( $bytes, 'pdf' );
			if ( '' === $stored ) {
				$failed[ $key ] = 'almacen';
				continue;
			}
			// Primero la meta y después se borra el anterior: si algo se
			// corta entre medias, sobra un fichero, pero la meta nunca
			// apunta a uno que ya no está.
			self::write(
				$procedure_id,
				$key,
				array(
					'url'      => $url,
					'stored'   => $stored,
					'saved_at' => time(),
					'size'     => strlen( $bytes ),
				)
			);
			if ( null !== $current ) {
				ApplicationFiles::erase( $current );
			}
		}
		return $failed;
	}

	/**
	 * Write or remove the copy of one link, rereading the rest.
	 *
	 * @param int                       $procedure_id Procedure post ID.
	 * @param string                    $key          Link meta key.
	 * @param array<string, mixed>|null $copy         New entry, or null to remove it.
	 * @return void
	 */
	private static function write( int $procedure_id, string $key, ?array $copy ): void {
		$copies = self::copies( $procedure_id );
		if ( null === $copy ) {
			unset( $copies[ $key ] );
		} else {
			$copies[ $key ] = $copy;
		}
		update_post_meta( $procedure_id, ProcedureMetaKeys::LINK_COPIES, $copies );
	}

	/**
	 * Download one PDF, or say why not.
	 *
	 * `wp_safe_remote_get()` rechaza las direcciones internas y vuelve a
	 * comprobar cada redirección. Que sea un PDF lo dicen los bytes —la
	 * cabecera `%PDF-` en el primer kilobyte, como la busca un lector—, no la
	 * extensión ni el `Content-Type`.
	 *
	 * @param string $url     Official URL.
	 * @param int    $seconds What is left of the time budget.
	 * @return string The bytes, or a key of self::WHY.
	 */
	private static function fetch( string $url, int $seconds ): string {
		if ( $seconds < 1 ) {
			return 'red';
		}
		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'             => min( self::TIMEOUT, $seconds ),
				'redirection'         => 3,
				'limit_response_size' => self::MAX_BYTES + 1,
			)
		);
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return 'red';
		}
		$bytes = (string) wp_remote_retrieve_body( $response );
		if ( strlen( $bytes ) > self::MAX_BYTES ) {
			return 'grande';
		}
		return false !== strpos( substr( $bytes, 0, 1024 ), '%PDF-' ) ? $bytes : 'pdf';
	}

	/**
	 * The notice after saving the links.
	 *
	 * @param array<string, mixed> $query The request query.
	 * @return array{tipo:string, texto:string}
	 */
	public static function notice( array $query ): array {
		$failed    = isset( $query[ self::ARG_FAILED ] ) && is_array( $query[ self::ARG_FAILED ] ) ? $query[ self::ARG_FAILED ] : array();
		$sentences = array();
		foreach ( self::links() as $key => $text ) {
			$reason = isset( $failed[ $key ] ) ? sanitize_key( (string) $failed[ $key ] ) : '';
			if ( isset( self::WHY[ $reason ] ) ) {
				$sentences[] = 'No se pudo guardar una copia ' . $text[0] . ': ' . self::WHY[ $reason ] . '.';
			}
		}
		if ( array() === $sentences ) {
			return array(
				'tipo'  => 'ok',
				'texto' => 'Enlaces guardados.',
			);
		}
		return array(
			'tipo'  => 'aviso',
			'texto' => 'Enlaces guardados. ' . implode( ' ', $sentences ) . ' Se volverá a intentar la próxima vez que guarde los enlaces.',
		);
	}

	/**
	 * What «Enlaces» says about each copy.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return array<string, array{date:string, url:string, stale:bool}>
	 */
	public static function summary( int $procedure_id ): array {
		$out = array();
		foreach ( self::copies( $procedure_id ) as $key => $copy ) {
			$out[ $key ] = array(
				'date'  => (string) wp_date( 'd/m/Y', (int) $copy['saved_at'] ),
				'url'   => self::url( $procedure_id, $key ),
				'stale' => (string) get_post_meta( $procedure_id, $key, true ) !== $copy['url'],
			);
		}
		return $out;
	}

	/**
	 * The URL that serves one copy. Never the file's own.
	 *
	 * @param int    $procedure_id Procedure post ID.
	 * @param string $key          Link meta key.
	 * @return string
	 */
	public static function url( int $procedure_id, string $key ): string {
		return add_query_arg(
			array(
				self::ARG_PROCEDURE => $procedure_id,
				self::ARG_LINK      => $key,
			),
			home_url( '/' )
		);
	}

	/**
	 * Serve one copy, when whoever asks may open the procedure.
	 *
	 * @return void
	 */
	public static function handle(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- es una lectura; autoriza ProcedureAccess, como los documentos (ADR-0028).
		$key = isset( $_GET[ self::ARG_LINK ] ) ? sanitize_key( wp_unslash( $_GET[ self::ARG_LINK ] ) ) : '';
		if ( '' === $key ) {
			return;
		}
		$procedure_id = isset( $_GET[ self::ARG_PROCEDURE ] ) ? absint( wp_unslash( $_GET[ self::ARG_PROCEDURE ] ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$links = self::links();
		if ( ! isset( $links[ $key ] ) || ! ProcedureAccess::can_download_link_copy( get_current_user_id(), $procedure_id ) ) {
			ApplicationFiles::deny();
			return;
		}

		$copy  = self::copies( $procedure_id )[ $key ] ?? null;
		$bytes = null === $copy ? null : ApplicationFiles::read( $copy );
		if ( null === $bytes ) {
			ApplicationFiles::deny( 404, 'No hay copia guardada de ese enlace.' );
			return;
		}

		$descriptor = array(
			'name' => $links[ $key ][1],
			'mime' => 'application/pdf',
		);
		foreach ( ApplicationFiles::headers( $descriptor, strlen( $bytes ) ) as $line ) {
			Shell::send_header( $line );
		}
		echo $bytes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- es el PDF, servido como descarga y con nosniff.
		Shell::leave();
	}

	/**
	 * Take the copies with the procedure, when it really goes.
	 *
	 * @param int           $post_id Post being deleted for good.
	 * @param \WP_Post|null $post    Its object.
	 * @return void
	 */
	public static function on_delete( int $post_id, $post = null ): void {
		$type = $post instanceof \WP_Post ? (string) $post->post_type : (string) get_post_type( $post_id );
		if ( ProcedurePostType::POST_TYPE !== $type ) {
			return;
		}
		foreach ( self::copies( $post_id ) as $copy ) {
			ApplicationFiles::erase( $copy );
		}
	}
}
