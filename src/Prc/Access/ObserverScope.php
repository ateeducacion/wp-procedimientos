<?php
/**
 * The zones a person observes, read from a configurable user meta, and the
 * columns a read-only consultation never shows.
 *
 * @package Prc
 */

namespace Prc\Access;

/**
 * La zona de quien consulta: una meta de usuario con clave configurable.
 *
 * Es la tercera dimensión del acotado, junto al ámbito de quien gestiona y al
 * centro de quien solicita (ADR-0043). Igual que el código de centro
 * (ADR-0016), la clave de la meta la devuelve un filtro, `prc_zone_meta_key`,
 * para que cada instalación lea la que ya tenga. A diferencia del centro, una
 * persona puede consultar **varias** zonas: hay quien atiende más de una.
 *
 * Una zona es el código de otro centro del catálogo, el mismo valor que el
 * campo `zone` de cada centro (ADR-0042). Qué centros son de una zona se
 * pregunta al catálogo **al decidir**, no se copia: un centro que cambia de
 * zona deja de verse desde la antigua en cuanto el catálogo lo dice.
 *
 * Lo que decide quién ve qué está en {@see ProcedureAccess}; aquí solo se lee
 * la meta, se cruza con el catálogo y se nombra lo que nunca se enseña. Todo
 * salvo {@see zones_for()} y {@see meta_key()} es puro.
 */
final class ObserverScope {

	/**
	 * User meta con la zona o las zonas, si nadie dice otra.
	 */
	public const META_KEY = 'prc_zone';

	/**
	 * Columns a read-only consultation never shows, nor exports, whatever the
	 * procedure ticks: the DNI or NIE of anybody and the review note.
	 *
	 * El DNI no es de la zona: es de cada persona, y lo necesita quien revisa,
	 * no quien acompaña al centro. La nota de revisión es la conversación
	 * entre quien gestiona y el centro (ADR-0043).
	 *
	 * @var string[]
	 */
	public const FORBIDDEN_COLUMNS = array( 'coordinator_dni', 'person_dni', 'note' );

	/**
	 * Groups of columns a consultation shows when the procedure ticked none.
	 *
	 * @var string[]
	 */
	public const DEFAULT_GROUPS = array( 'application', 'centre', 'coordination' );

	/**
	 * Which user meta holds the zones on this site.
	 *
	 * @return string Meta key; empty when the filter answered nonsense.
	 */
	public static function meta_key(): string {
		/**
		 * Filter the user meta key that carries the zones a person observes.
		 *
		 * @param string $key Meta key. Default `prc_zone`.
		 */
		$key = apply_filters( 'prc_zone_meta_key', self::META_KEY );
		return is_string( $key ) ? trim( $key ) : '';
	}

	/**
	 * The zones of a person, as written in the profile.
	 *
	 * @param int $user_id User ID.
	 * @return string[] Zone codes; empty when there is none valid.
	 */
	public static function zones_for( int $user_id ): array {
		$key = self::meta_key();
		if ( $user_id <= 0 || '' === $key ) {
			return array();
		}
		return self::parse_zones( get_user_meta( $user_id, $key, true ) );
	}

	/**
	 * Zone codes out of whatever the meta holds: a list, or a text with the
	 * codes separated by commas, semicolons or spaces. Pure.
	 *
	 * Lo que no tiene forma de código ({@see CentreScope::is_code()}) se cae:
	 * que la zona exista lo dice el catálogo, no la meta.
	 *
	 * @param mixed $raw Stored value.
	 * @return string[] Without repeats, in the order written.
	 */
	public static function parse_zones( $raw ): array {
		if ( is_string( $raw ) ) {
			$raw = preg_split( '/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY );
		}
		$out = array();
		foreach ( is_array( $raw ) ? $raw : array() as $valor ) {
			$codigo = is_string( $valor ) ? trim( $valor ) : '';
			if ( CentreScope::is_code( $codigo ) ) {
				$out[ $codigo ] = $codigo;
			}
		}
		return array_values( $out );
	}

	/**
	 * The codes of the centres the catalogue puts in some of these zones. Pure.
	 *
	 * @param string[]                            $zones     Zone codes.
	 * @param array<string, array<string, mixed>> $catalogue The catalogue by code ({@see \Prc\Domain\CentreCatalog::index()}).
	 * @return string[] In catalogue order.
	 */
	public static function codes_in( array $zones, array $catalogue ): array {
		if ( array() === $zones ) {
			return array();
		}
		$buscadas = array_flip( $zones );
		$out      = array();
		foreach ( $catalogue as $codigo => $centro ) {
			$zona = is_array( $centro ) ? (string) ( $centro['zone'] ?? '' ) : '';
			if ( '' !== $zona && isset( $buscadas[ $zona ] ) ) {
				$out[] = (string) $codigo;
			}
		}
		return $out;
	}

	/**
	 * Groups of columns without what a consultation never shows. Pure.
	 *
	 * @param array<string, array<string, string>> $groups Group => (column => heading).
	 * @return array<string, array<string, string>> The same, without the forbidden columns nor the groups left empty.
	 */
	public static function allowed( array $groups ): array {
		$out = array();
		foreach ( $groups as $grupo => $columnas ) {
			$quedan = array_diff_key( (array) $columnas, array_flip( self::FORBIDDEN_COLUMNS ) );
			if ( array() !== $quedan ) {
				$out[ (string) $grupo ] = $quedan;
			}
		}
		return $out;
	}

	/**
	 * The columns a consultation shows: the chosen ones that are allowed, in
	 * the order of the groups; the default groups when none is. Pure.
	 *
	 * @param mixed                                $chosen Stored choice: column keys.
	 * @param array<string, array<string, string>> $groups Group => (column => heading), every one.
	 * @return array<string, string> Column => heading; never a forbidden one.
	 */
	public static function columns( $chosen, array $groups ): array {
		$permitidas = self::allowed( $groups );
		$elegidas   = is_array( $chosen ) ? array_flip( array_filter( $chosen, 'is_string' ) ) : array();
		$out        = array();
		foreach ( $permitidas as $columnas ) {
			$out += array_intersect_key( $columnas, $elegidas );
		}
		if ( array() !== $out ) {
			return $out;
		}
		foreach ( self::DEFAULT_GROUPS as $grupo ) {
			$out += (array) ( $permitidas[ $grupo ] ?? array() );
		}
		return $out;
	}
}
