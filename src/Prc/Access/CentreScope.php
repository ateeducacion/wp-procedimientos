<?php
/**
 * The school a person belongs to, read from a configurable user meta.
 *
 * @package Prc
 */

namespace Prc\Access;

use Prc\Domain\CentreCatalog;
use Prc\Meta\ProcedureMetaKeys;

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
	 * User meta con el código de la agrupación de escuelas rurales que
	 * coordina, si nadie dice otra (ADR-0031).
	 */
	public const GROUP_META_KEY = 'prc_rural_group_code';

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
		return self::read( $user_id, self::meta_key() );
	}

	/**
	 * Which user meta holds the rural group code on this site.
	 *
	 * @return string Meta key; empty when the filter answered nonsense.
	 */
	public static function group_meta_key(): string {
		/**
		 * Filter the user meta key that carries the rural group code.
		 *
		 * @param string $key Meta key. Default `prc_rural_group_code`.
		 */
		$key = apply_filters( 'prc_rural_group_code_meta_key', self::GROUP_META_KEY );
		return is_string( $key ) ? trim( $key ) : '';
	}

	/**
	 * The code of the rural group a person coordinates besides their school, or nothing.
	 *
	 * @param int $user_id User ID (0 = current).
	 * @return string Empty when there is no valid code.
	 */
	public static function group_code_for( int $user_id = 0 ): string {
		return self::read( $user_id, self::group_meta_key() );
	}

	/**
	 * Every code a person may apply with, school first, and what each one is.
	 *
	 * La agrupación de la segunda meta es siempre una agrupación. El código de
	 * centro es lo que diga el catálogo: quien coordina una agrupación a tiempo
	 * completo la tiene como código de centro.
	 *
	 * @param int $user_id User ID (0 = current).
	 * @return array<string, string> Code => one of ProcedureMetaKeys::centre_kinds().
	 */
	public static function codes_for( int $user_id = 0 ): array {
		$out  = array();
		$code = self::code_for( $user_id );
		if ( '' !== $code ) {
			$out[ $code ] = (string) ( CentreCatalog::find( $code )['kind'] ?? ProcedureMetaKeys::KIND_SCHOOL );
		}
		$group = self::group_code_for( $user_id );
		if ( '' !== $group && ! isset( $out[ $group ] ) ) {
			$out[ $group ] = ProcedureMetaKeys::KIND_RURAL_GROUP;
		}
		return $out;
	}

	/**
	 * A code from one user meta, or nothing.
	 *
	 * @param int    $user_id User ID (0 = current).
	 * @param string $key     Meta key; empty fails closed.
	 * @return string
	 */
	private static function read( int $user_id, string $key ): string {
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
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
