<?php
/**
 * The history of an application: who did what, and when.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\MetaSwap;
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
 * van las entradas más antiguas, y la primera dice cuántas se han ido.
 */
final class ApplicationLog {

	/** Most entries one application keeps, counting the one that says how many went. */
	public const MAX = 500;

	/** Event of the first entry once old ones were dropped: `data.dropped` says how many. */
	public const TRIMMED = 'log_trimmed';

	/**
	 * Append one entry to the history of an application.
	 *
	 * Lee la meta en el momento de escribir y escribe solo si nadie escribió
	 * entretanto ({@see MetaSwap}); si no, la relee y lo intenta otra vez,
	 * para no pisar una entrada que otra petición acabe de añadir.
	 *
	 * @param int                  $application_id Application post ID.
	 * @param string               $event          What happened: lowercase letters and underscores.
	 * @param array<string, mixed> $data           What the event tells.
	 * @param int                  $user_id        Account that acted; 0 for the current one.
	 * @return bool Whether the entry was written.
	 */
	public static function add( int $application_id, string $event, array $data, int $user_id = 0 ): bool {
		if ( ApplicationPostType::POST_TYPE !== get_post_type( $application_id ) ) {
			return false;
		}
		$entrada = array(
			'at'    => gmdate( 'Y-m-d\TH:i:s\Z' ),
			'user'  => $user_id > 0 ? $user_id : get_current_user_id(),
			'event' => $event,
			'data'  => $data,
		);
		$res     = MetaSwap::change(
			$application_id,
			ApplicationMetaKeys::LOG,
			static function ( $leido ) use ( $entrada ): array {
				return array( 'value' => self::append( self::sanitize( $leido ), $entrada ) );
			},
			3
		);
		return (bool) $res['ok'];
	}

	/**
	 * The history with one more entry, within the cap.
	 *
	 * Al pasar de {@see MAX} se descartan las entradas más antiguas y la
	 * primera pasa a ser una `log_trimmed` que dice cuántas se han ido en
	 * total: el historial no esconde que le faltan entradas.
	 *
	 * @param array<int, array<string, mixed>> $todo    The history now.
	 * @param array<string, mixed>             $entrada The new entry.
	 * @return array<int, array<string, mixed>>
	 */
	public static function append( array $todo, array $entrada ): array {
		$idas = 0;
		if ( isset( $todo[0] ) && self::TRIMMED === $todo[0]['event'] ) {
			$idas = (int) ( $todo[0]['data']['dropped'] ?? 0 );
			array_shift( $todo );
		}
		$todo[] = $entrada;
		if ( count( $todo ) + ( $idas > 0 ? 1 : 0 ) <= self::MAX ) {
			return $idas > 0 ? array_merge( array( self::trimmed( $idas, $todo[0]['at'] ) ), $todo ) : $todo;
		}
		$quedan = array_slice( $todo, - ( self::MAX - 1 ) );
		$idas  += count( $todo ) - count( $quedan );
		return array_merge( array( self::trimmed( $idas, (string) $quedan[0]['at'] ) ), $quedan );
	}

	/**
	 * The entry that says how many old entries were dropped.
	 *
	 * @param int    $dropped How many, in total.
	 * @param string $at      When: that of the oldest entry kept.
	 * @return array<string, mixed>
	 */
	private static function trimmed( int $dropped, string $at ): array {
		return array(
			'at'    => $at,
			'user'  => 0,
			'event' => self::TRIMMED,
			'data'  => array( 'dropped' => $dropped ),
		);
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
