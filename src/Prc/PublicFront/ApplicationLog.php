<?php
/**
 * The history of an application: who did what, and when.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\Meta\ApplicationMetaKeys;
use Prc\PostType\ApplicationPostType;

/**
 * El historial de una solicitud (ADR-0041; desarrolla lo que la ADR-0005
 * dejó por escribir para las solicitudes).
 *
 * Se guarda con la solicitud, en su meta `prc_log`, que no se publica en la
 * REST y cuyo `auth_callback` dice que no a todo el mundo: solo lo escribe el
 * aplicativo, con {@see add()}. Cada entrada es:
 *
 * ```
 * { at: '2026-11-10T09:30:00Z', user: 12, event: 'person_changed',
 *   data: { before: {…}, after: {…} } }
 * ```
 *
 * `at` en UTC e ISO 8601; `user`, la cuenta que actuó; `data`, lo que el
 * evento necesite contar —en los cambios, el antes y el después—. Es un
 * registro interno: guarda los datos tal cual, y quien lo pinta decide qué
 * enseña y a quién.
 *
 * Tiene tope, como decía la ADR-0005 de la auditoría en meta: al llenarse se
 * van las entradas más antiguas.
 */
final class ApplicationLog {

	/** Most entries one application keeps. */
	public const MAX = 500;

	/**
	 * Append one entry to the history of an application.
	 *
	 * Lee la meta en el momento de escribir, sin caché, para no pisar una
	 * entrada que otra petición acabe de añadir.
	 *
	 * @param int                  $application_id Application post ID.
	 * @param string               $event          What happened: lowercase letters and underscores.
	 * @param array<string, mixed> $data           What the event tells.
	 * @return void
	 */
	public static function add( int $application_id, string $event, array $data ): void {
		if ( ApplicationPostType::POST_TYPE !== get_post_type( $application_id ) ) {
			return;
		}
		wp_cache_delete( $application_id, 'post_meta' );
		$todo   = self::all( $application_id );
		$todo[] = array(
			'at'    => gmdate( 'Y-m-d\TH:i:s\Z' ),
			'user'  => get_current_user_id(),
			'event' => $event,
			'data'  => $data,
		);
		update_post_meta( $application_id, ApplicationMetaKeys::LOG, wp_slash( array_slice( $todo, -self::MAX ) ) );
	}

	/**
	 * The whole history of an application, oldest first.
	 *
	 * @param int $application_id Application post ID.
	 * @return array<int, array{at:string, user:int, event:string, data:array<string, mixed>}>
	 */
	public static function all( int $application_id ): array {
		return self::sanitize( get_post_meta( $application_id, ApplicationMetaKeys::LOG, true ) );
	}

	/**
	 * A stored history, normalised: what is not an entry is dropped.
	 *
	 * Es también el saneado de la meta.
	 *
	 * @param mixed $value Raw value.
	 * @return array<int, array{at:string, user:int, event:string, data:array<string, mixed>}>
	 */
	public static function sanitize( $value ): array {
		$out = array();
		foreach ( is_array( $value ) ? $value : array() as $entrada ) {
			$evento = is_array( $entrada ) && is_string( $entrada['event'] ?? null ) ? $entrada['event'] : '';
			if ( ! preg_match( '/^[a-z][a-z0-9_]{0,39}$/', $evento ) ) {
				continue;
			}
			$momento = is_string( $entrada['at'] ?? null ) ? $entrada['at'] : '';
			$out[]   = array(
				'at'    => preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $momento ) ? $momento : '',
				'user'  => absint( $entrada['user'] ?? 0 ),
				'event' => $evento,
				'data'  => self::data( is_array( $entrada['data'] ?? null ) ? $entrada['data'] : array(), 3 ),
			);
		}
		return array_slice( $out, -self::MAX );
	}

	/**
	 * The data of an entry: plain text, numbers and booleans, a few levels deep.
	 *
	 * @param array<mixed> $data  Raw data.
	 * @param int          $depth Levels left.
	 * @return array<string, mixed>
	 */
	private static function data( array $data, int $depth ): array {
		$out = array();
		foreach ( $data as $clave => $valor ) {
			$clave = is_int( $clave ) ? (string) $clave : sanitize_key( (string) $clave );
			if ( is_array( $valor ) ) {
				$out[ $clave ] = $depth > 1 ? self::data( $valor, $depth - 1 ) : array();
			} elseif ( is_int( $valor ) || is_bool( $valor ) ) {
				$out[ $clave ] = $valor;
			} elseif ( is_scalar( $valor ) ) {
				$out[ $clave ] = sanitize_text_field( (string) $valor );
			}
		}
		return $out;
	}
}
