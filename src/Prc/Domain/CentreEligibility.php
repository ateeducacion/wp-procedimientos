<?php
/**
 * Which schools a procedure takes: ownership, type, island and a closed list.
 *
 * @package Prc
 */

namespace Prc\Domain;

use Prc\Meta\ProcedureMetaKeys;

/**
 * Los acotados de un procedimiento sobre los centros que pueden solicitar
 * (ADR-0042). Pura: ni una llamada a WordPress.
 *
 * Cuatro acotados, y vacío es «sin acotado»:
 *
 * - la titularidad (`prc_ownership`), que no acota si las pide todas;
 * - los tipos de centro (`prc_centre_types`);
 * - las islas (`prc_islands`);
 * - una lista cerrada de códigos (`prc_allowed_centres`).
 *
 * Con cualquiera de ellos, un centro que no está en el catálogo no solicita:
 * no se puede saber si cumple. Sin ninguno, solicita igual que siempre,
 * porque el catálogo puede ir por detrás de la realidad (ADR-0017).
 */
final class CentreEligibility {

	/**
	 * Cuántos códigos caben en la lista cerrada.
	 */
	public const MAX_CODES = 2000;

	/**
	 * Cuántos tipos o islas caben en una lista.
	 */
	public const MAX_VALUES = 200;

	/**
	 * Lower case, trimmed, with inner spaces collapsed: what is compared.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public static function fold( string $value ): string {
		return mb_strtolower( trim( (string) preg_replace( '/\s+/u', ' ', $value ) ), 'UTF-8' );
	}

	/**
	 * A list of distinct texts, without blanks; repeats in another case drop.
	 *
	 * @param mixed $raw A list, or a text with one value per line.
	 * @return string[]
	 */
	public static function values( $raw ): array {
		if ( is_string( $raw ) ) {
			$raw = preg_split( '/\R/u', $raw );
		}
		$out = array();
		foreach ( is_array( $raw ) ? $raw : array() as $valor ) {
			$valor = is_scalar( $valor ) ? trim( (string) preg_replace( '/\s+/u', ' ', (string) $valor ) ) : '';
			if ( '' !== $valor && ! isset( $out[ self::fold( $valor ) ] ) ) {
				$out[ self::fold( $valor ) ] = $valor;
			}
		}
		return array_slice( array_values( $out ), 0, self::MAX_VALUES );
	}

	/**
	 * Read a closed list of codes as typed: one per line, or separated by commas.
	 *
	 * Los espacios y los repetidos se van, y los códigos pasan a mayúsculas:
	 * la lista no distingue unas de otras. Lo que no tiene forma de código se
	 * devuelve aparte, para decirlo en vez de perderlo.
	 *
	 * @param mixed $raw A text or a list.
	 * @return array{codes:string[], invalid:string[]}
	 */
	public static function parse_codes( $raw ): array {
		$trozos = is_array( $raw ) ? $raw : preg_split( '/[\s,;]+/u', is_scalar( $raw ) ? (string) $raw : '' );
		$codes  = array();
		$malos  = array();
		foreach ( (array) $trozos as $trozo ) {
			$trozo = is_scalar( $trozo ) ? trim( (string) $trozo ) : '';
			if ( '' === $trozo ) {
				continue;
			}
			if ( self::is_code( $trozo ) ) {
				// Sin distinguir mayúsculas: se guarda como se compara.
				$codes[ strtoupper( $trozo ) ] = true;
			} else {
				$malos[ $trozo ] = true;
			}
		}
		return array(
			'codes'   => array_map( 'strval', array_keys( $codes ) ),
			'invalid' => array_map( 'strval', array_keys( $malos ) ),
		);
	}

	/**
	 * The valid codes of a closed list, at most {@see MAX_CODES}.
	 *
	 * @param mixed $raw A text or a list.
	 * @return string[]
	 */
	public static function codes( $raw ): array {
		return array_slice( self::parse_codes( $raw )['codes'], 0, self::MAX_CODES );
	}

	/**
	 * Whether a string has the shape of a school code.
	 *
	 * La misma forma que pide la cuenta (`CentreScope::is_code()`).
	 *
	 * @param string $code Raw code.
	 * @return bool
	 */
	public static function is_code( string $code ): bool {
		return (bool) preg_match( '/^[A-Za-z0-9]{3,20}$/', $code );
	}

	/**
	 * Validate the three lists of the «Datos» form.
	 *
	 * Un procedimiento de profesorado no acota centros: las tres, vacías.
	 *
	 * @param array<string, mixed> $raw     `centre_types`, `islands`, `allowed_centres`.
	 * @param bool                 $schools Whether the procedure is for schools.
	 * @return array{data:array{centre_types:string[], islands:string[], allowed_centres:string[]}, errors:string[]}
	 */
	public static function input( array $raw, bool $schools ): array {
		$alloweda = self::parse_codes( $raw['allowed_centres'] ?? '' );
		$errors   = array() !== $alloweda['invalid'] || count( $alloweda['codes'] ) > self::MAX_CODES ? array( 'allowed_centres' ) : array();
		return array(
			'data'   => array(
				'centre_types'    => $schools ? self::values( $raw['centre_types'] ?? array() ) : array(),
				'islands'         => $schools ? self::values( $raw['islands'] ?? array() ) : array(),
				'allowed_centres' => $schools ? array_slice( $alloweda['codes'], 0, self::MAX_CODES ) : array(),
			),
			'errors' => $schools ? $errors : array(),
		);
	}

	/**
	 * The rules of a procedure, normalised.
	 *
	 * @param array<string, mixed> $raw `ownership`, `types`, `islands`, `codes`, as stored.
	 * @return array{ownership:string[], types:string[], islands:string[], codes:string[]}
	 */
	public static function rules( array $raw ): array {
		$titular = is_array( $raw['ownership'] ?? null ) ? array_map( 'strval', array_filter( $raw['ownership'], 'is_scalar' ) ) : array();
		return array(
			'ownership' => array_values( array_intersect( array_keys( ProcedureMetaKeys::ownerships() ), $titular ) ),
			'types'     => self::values( $raw['types'] ?? array() ),
			'islands'   => self::values( $raw['islands'] ?? array() ),
			'codes'     => self::codes( $raw['codes'] ?? array() ),
		);
	}

	/**
	 * The rules as the «Datos» form holds them: the closed list, one code per line.
	 *
	 * @param array<string, string[]> $rules What {@see rules()} returned.
	 * @return array{centre_types:string[], islands:string[], allowed_centres:string}
	 */
	public static function form_values( array $rules ): array {
		$rules = self::rules( $rules );
		return array(
			'centre_types'    => $rules['types'],
			'islands'         => $rules['islands'],
			'allowed_centres' => implode( "\n", $rules['codes'] ),
		);
	}

	/**
	 * Whether the procedure narrows down the schools that may apply at all.
	 *
	 * Pedir todas las titularidades es no pedir ninguna.
	 *
	 * @param array<string, string[]> $rules What {@see rules()} returned.
	 * @return bool
	 */
	public static function restricted( array $rules ): bool {
		$titular = (array) ( $rules['ownership'] ?? array() );
		return ( array() !== $titular && count( $titular ) < count( ProcedureMetaKeys::ownerships() ) )
			|| array() !== (array) ( $rules['types'] ?? array() )
			|| array() !== (array) ( $rules['islands'] ?? array() )
			|| array() !== (array) ( $rules['codes'] ?? array() );
	}

	/**
	 * Why a school may not apply to a procedure with those rules, in words.
	 *
	 * La lista cerrada se mira primero, sin distinguir mayúsculas. Si es el
	 * único acotado, estar en ella basta, aunque el centro no esté en el
	 * catálogo. Con cualquier otro, hace falta el catálogo para medirlo, salvo
	 * un caso: con el catálogo **entero** vacío, la titularidad no se puede
	 * comprobar con nada y no cierra, como antes de la ADR-0042.
	 *
	 * @param array<string, string[]> $rules     What {@see rules()} returned.
	 * @param string                  $code      The code it applies with.
	 * @param array<string, mixed>    $centre    Its entry in the catalogue; empty when it is not there.
	 * @param bool                    $catalogue Whether the catalogue has any centre at all.
	 * @return string Empty when it may.
	 */
	public static function why_not( array $rules, string $code, array $centre, bool $catalogue = true ): string {
		$rules = self::rules( $rules );
		if ( ! self::restricted( $rules ) ) {
			return '';
		}
		if ( array() !== $rules['codes'] && ! in_array( strtoupper( trim( $code ) ), $rules['codes'], true ) ) {
			return 'Este procedimiento es solo para una lista cerrada de centros, y el suyo no está en ella.';
		}
		$resto = self::without_list( $rules, $catalogue );
		if ( ! self::restricted( $resto ) ) {
			return '';
		}
		if ( array() === $centre ) {
			return 'Este procedimiento es solo para algunos centros, y el suyo no está en el catálogo de centros, así que no se puede comprobar si es uno de ellos. Si cree que es un error, escriba a los correos de contacto del procedimiento.';
		}
		return self::why_not_attributes( $resto, $centre );
	}

	/**
	 * The rules left to measure once the closed list is passed.
	 *
	 * @param array<string, string[]> $rules     Normalised rules.
	 * @param bool                    $catalogue Whether the catalogue has any centre at all.
	 * @return array<string, string[]>
	 */
	private static function without_list( array $rules, bool $catalogue ): array {
		$rules['codes'] = array();
		if ( ! $catalogue ) {
			$rules['ownership'] = array();
		}
		return $rules;
	}

	/**
	 * Ownership, type and island of a school of the catalogue against the rules.
	 *
	 * @param array<string, string[]> $rules  Normalised rules.
	 * @param array<string, mixed>    $centre Its entry in the catalogue.
	 * @return string Empty when it may.
	 */
	private static function why_not_attributes( array $rules, array $centre ): string {
		$titular = $rules['ownership'];
		if ( array() !== $titular && ! in_array( (string) ( $centre['ownership'] ?? '' ), $titular, true ) ) {
			$rotulos = array_map( 'lcfirst', array_intersect_key( ProcedureMetaKeys::ownerships(), array_flip( $titular ) ) );
			return 'Este procedimiento es solo para ' . implode( ' y ', $rotulos ) . '.';
		}
		if ( ! self::takes( $rules['types'], (string) ( $centre['type'] ?? '' ) ) ) {
			return 'Este procedimiento es solo para centros de tipo ' . self::either( $rules['types'] ) . '.';
		}
		if ( ! self::takes( $rules['islands'], (string) ( $centre['island'] ?? '' ) ) ) {
			return 'Este procedimiento es solo para centros de ' . self::either( $rules['islands'] ) . '.';
		}
		return '';
	}

	/**
	 * What the workshop has to warn about these rules today, in words.
	 *
	 * Dos cierres que no se ven desde el taller: un catálogo vacío, que deja
	 * sin medir los acotados, y una sola titularidad sin ningún tipo de centro
	 * marcado como privado en los ajustes, con la que todo centro es público.
	 *
	 * @param array<string, string[]> $rules         What {@see rules()} returned.
	 * @param bool                    $empty_catalogue Whether the catalogue has no centre at all.
	 * @param bool                    $no_private      Whether no centre type is marked as private.
	 * @return array<string, string> `ownership` and `catalogue`, when there is something to say.
	 */
	public static function warnings( array $rules, bool $empty_catalogue, bool $no_private ): array {
		$rules   = self::rules( $rules );
		$out     = array();
		$titular = $rules['ownership'];
		if ( $no_private && 1 === count( $titular ) ) {
			$out['ownership'] = ProcedureMetaKeys::OWNERSHIP_PUBLIC === $titular[0]
				? 'No hay ningún tipo de centro marcado como privado en los ajustes: hoy todos los centros cuentan como públicos, salvo los que el catálogo marque expresamente, así que este límite no deja fuera a casi nadie.'
				: 'No hay ningún tipo de centro marcado como privado en los ajustes: hoy ningún centro cuenta como privado, salvo los que el catálogo marque expresamente, así que nadie o casi nadie puede solicitar.';
		}
		if ( $empty_catalogue && self::restricted( $rules ) ) {
			$resto = self::without_list( $rules, false );
			if ( self::restricted( $resto ) ) {
				$out['catalogue'] = 'El catálogo de centros está vacío: con los tipos o las islas que pide este procedimiento, ningún centro puede solicitar hasta que se cargue.';
			} elseif ( array() !== $rules['codes'] ) {
				$out['catalogue'] = 'El catálogo de centros está vacío: solo pueden solicitar los centros de la lista cerrada.';
			} else {
				$out['catalogue'] = 'El catálogo de centros está vacío, así que la titularidad no se puede comprobar: puede solicitar cualquier centro.';
			}
		}
		return $out;
	}

	/**
	 * Whether a list takes a value: an empty list takes them all.
	 *
	 * @param string[] $allowed Allowed values.
	 * @param string   $value The value.
	 * @return bool
	 */
	private static function takes( array $allowed, string $value ): bool {
		return array() === $allowed || in_array( self::fold( $value ), array_map( array( self::class, 'fold' ), $allowed ), true );
	}

	/**
	 * «A», «A o B», «A, B o C».
	 *
	 * @param string[] $allowed Values.
	 * @return string
	 */
	private static function either( array $allowed ): string {
		$ultimo = (string) array_pop( $allowed );
		return array() === $allowed ? $ultimo : implode( ', ', $allowed ) . ' o ' . $ultimo;
	}
}
