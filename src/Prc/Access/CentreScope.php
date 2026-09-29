<?php
/**
 * The school a person belongs to, read from a configurable user meta.
 *
 * @package Prc
 */

namespace Prc\Access;

/**
 * El centro de la persona: una meta de usuario con clave configurable.
 *
 * Hoy el código de centro lo escribe al iniciar sesión un fragmento de código
 * que consulta el directorio corporativo, en una meta cuyo nombre es de esa
 * instalación. El aplicativo no sabe de dónde viene: lee la meta cuya clave
 * devuelve el filtro `prc_centre_code_meta_key` (ADR-0016) y, sin un código
 * con forma de código, falla en cerrado: `prc_apply` no sirve para nada.
 */
final class CentreScope {

	/**
	 * User meta con el código de centro, si nadie dice otra.
	 */
	public const META_KEY = 'prc_centre_code';

	/**
	 * Which user meta holds the school code on this site.
	 *
	 * @return string Meta key; empty when the filter answered nonsense.
	 */
	public static function meta_key(): string {
		/**
		 * Filter the user meta key that carries the school code.
		 *
		 * @param string $key Meta key. Default `prc_centre_code`.
		 */
		$key = apply_filters( 'prc_centre_code_meta_key', self::META_KEY );
		return is_string( $key ) ? trim( $key ) : '';
	}

	/**
	 * The school code of a person, or nothing.
	 *
	 * @param int $user_id User ID (0 = current).
	 * @return string Empty when there is no valid code.
	 */
	public static function code_for( int $user_id = 0 ): string {
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
		$key = self::meta_key();
		if ( $user_id <= 0 || '' === $key ) {
			return '';
		}
		$raw  = get_user_meta( $user_id, $key, true );
		$code = is_scalar( $raw ) ? trim( (string) $raw ) : '';
		return self::is_code( $code ) ? $code : '';
	}

	/**
	 * Whether a string has the shape of a school code.
	 *
	 * Solo la forma: letras y cifras, sin espacios. Que exista lo dice el
	 * catálogo, y el catálogo puede ir por detrás de la realidad.
	 *
	 * @param string $code Raw code.
	 * @return bool
	 */
	public static function is_code( string $code ): bool {
		return (bool) preg_match( '/^[A-Za-z0-9]{3,20}$/', $code );
	}
}
