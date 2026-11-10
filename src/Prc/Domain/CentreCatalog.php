<?php
/**
 * The catalogue of schools, answered by a filter.
 *
 * @package Prc
 */

namespace Prc\Domain;

use Prc\Meta\ProcedureMetaKeys;

/**
 * El catálogo de centros: código, nombre, titularidad, clase y los atributos
 * por los que un procedimiento puede acotar quién solicita.
 *
 * La clase dice si el código es de un centro o de una agrupación de escuelas
 * rurales (ADR-0031); sin decirlo, es un centro.
 *
 * La titularidad no la trae la fuente sincronizada (ADR-0029): se **deriva
 * del tipo** con la lista de tipos privados que contesta el filtro
 * `prc_private_centre_types`, vacía por defecto. Una titularidad explícita
 * —de la fuente o de quien conteste `prc_centres`— manda (ADR-0042).
 *
 * La zona es el código de **otro** centro del mismo catálogo, y su nombre se
 * resuelve aquí, en memoria, sin una consulta por centro (ADR-0042).
 *
 * El catálogo real es de quien despliega y no se versiona (ADR-0017): el
 * aplicativo lo **pregunta** con el filtro `prc_centres` y, si nadie
 * contesta, lee `wp-content/uploads/prc/centres.json`. Sin fichero, lista
 * vacía: se puede solicitar igual, con el nombre en blanco, a un
 * procedimiento sin acotados.
 */
final class CentreCatalog {

	/**
	 * Cuántos resultados devuelve una búsqueda como mucho.
	 */
	public const SEARCH_LIMIT = 20;

	/**
	 * Opción con los tipos de centro que son privados, uno por elemento.
	 * La escribe `Admin/Settings`; la lee el filtro `prc_private_centre_types`.
	 */
	public const OPTION_PRIVATE_TYPES = 'prc_private_centre_types';

	/**
	 * Every school, normalised.
	 *
	 * @return array<int, array{code:string, name:string, ownership:string, kind:string, island:string, municipality:string, type:string, zone:string, zone_name:string, active:bool}>
	 */
	public static function all(): array {
		/**
		 * Filter the school catalogue.
		 *
		 * Quien despliega contesta desde un fragmento de código suelto; en el
		 * entorno local contesta el mu-plugin con una docena inventada.
		 *
		 * @param array<int, array<string, mixed>> $centres Schools as {code, name, ownership, kind, island, municipality, type, zone, active}.
		 */
		$default = class_exists( '\Prc\Centre\CentreCatalogue' ) ? \Prc\Centre\CentreCatalogue::for_domain() : array();
		if ( empty( $default ) ) {
			$default = self::from_uploads();
		}
		$raw = apply_filters( 'prc_centres', $default );
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$privados = self::private_types();
		$out      = array();
		$nombres  = array();
		foreach ( $raw as $item ) {
			$centro = is_array( $item ) ? self::normalise( $item, $privados ) : null;
			if ( null === $centro || isset( $nombres[ $centro['code'] ] ) ) {
				continue;
			}
			$nombres[ $centro['code'] ] = $centro['name'];
			$out[]                      = $centro;
		}
		// La zona es otro centro del catálogo: su nombre sale de aquí mismo.
		foreach ( $out as $i => $centro ) {
			$out[ $i ]['zone_name'] = '' !== $centro['zone'] ? (string) ( $nombres[ $centro['zone'] ] ?? '' ) : '';
		}
		return $out;
	}

	/**
	 * The catalogue indexed by code, for whoever looks up many codes at once.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function index(): array {
		$out = array();
		foreach ( self::all() as $centro ) {
			$out[ $centro['code'] ] = $centro;
		}
		return $out;
	}

	/**
	 * One school by its code.
	 *
	 * @param string $code School code.
	 * @return array<string, mixed> As {@see all()}; empty when it is not in the catalogue.
	 */
	public static function find( string $code ): array {
		$code = trim( $code );
		if ( '' === $code ) {
			return array();
		}
		foreach ( self::all() as $centro ) {
			if ( $centro['code'] === $code ) {
				return $centro;
			}
		}
		return array();
	}

	/**
	 * Schools whose code starts with, or whose name contains, the query.
	 *
	 * @param string $q Query.
	 * @return array<int, array<string, mixed>> At most SEARCH_LIMIT.
	 */
	public static function search( string $q ): array {
		$q = self::fold( $q );
		if ( '' === $q ) {
			return array();
		}
		$out = array();
		foreach ( self::all() as $centro ) {
			if ( 0 === strpos( self::fold( $centro['code'] ), $q ) || false !== strpos( self::fold( $centro['name'] ), $q ) ) {
				$out[] = $centro;
				if ( count( $out ) >= self::SEARCH_LIMIT ) {
					break;
				}
			}
		}
		return $out;
	}

	/**
	 * The distinct values one attribute takes in the catalogue, sorted.
	 *
	 * Es lo que ofrece el taller para acotar por tipo o por isla: lo que hay,
	 * no una lista escrita a mano.
	 *
	 * @param string $field `type`, `island` or `municipality`.
	 * @return string[]
	 */
	public static function values( string $field ): array {
		$out = array();
		foreach ( self::all() as $centro ) {
			$valor = (string) ( $centro[ $field ] ?? '' );
			if ( '' !== $valor && ! isset( $out[ CentreEligibility::fold( $valor ) ] ) ) {
				$out[ CentreEligibility::fold( $valor ) ] = $valor;
			}
		}
		$out = array_values( $out );
		usort( $out, 'strnatcasecmp' );
		return $out;
	}

	/**
	 * The centre types that are private on this installation.
	 *
	 * Vacía por defecto: sin configurar, todo centro es público. La contesta
	 * la opción de los ajustes, y por encima el filtro.
	 *
	 * @return string[] Folded: lower case, trimmed.
	 */
	public static function private_types(): array {
		$guardados = get_option( self::OPTION_PRIVATE_TYPES, array() );
		/**
		 * Filter the centre types that count as private.
		 *
		 * @param string[] $types Centre types, as the catalogue writes them; compared without case.
		 */
		$tipos = apply_filters( 'prc_private_centre_types', is_array( $guardados ) ? $guardados : array() );
		return array_map( array( CentreEligibility::class, 'fold' ), CentreEligibility::values( $tipos ) );
	}

	/**
	 * One raw record, normalised; null when it is not a school.
	 *
	 * @param array<string, mixed> $item     Raw record.
	 * @param string[]             $privados Folded private types.
	 * @return array<string, mixed>|null
	 */
	private static function normalise( array $item, array $privados ): ?array {
		$code = self::text( $item, 'code' );
		if ( '' === $code ) {
			return null;
		}
		$tipo      = self::text( $item, 'type' );
		$explicita = ProcedureMetaKeys::in_list( $item['ownership'] ?? '', ProcedureMetaKeys::ownerships() );
		$derivada  = in_array( CentreEligibility::fold( $tipo ), $privados, true ) ? ProcedureMetaKeys::OWNERSHIP_PRIVATE : ProcedureMetaKeys::OWNERSHIP_PUBLIC;
		return array(
			'code'         => $code,
			'name'         => self::text( $item, 'name' ),
			'ownership'    => '' !== $explicita ? $explicita : $derivada,
			'kind'         => ProcedureMetaKeys::in_list( $item['kind'] ?? '', ProcedureMetaKeys::centre_kinds(), ProcedureMetaKeys::KIND_SCHOOL ),
			'island'       => self::text( $item, 'island' ),
			'municipality' => self::text( $item, 'municipality' ),
			'type'         => $tipo,
			'zone'         => self::text( $item, 'zone' ),
			'zone_name'    => '',
			'active'       => ! array_key_exists( 'active', $item ) || (bool) $item['active'],
		);
	}

	/**
	 * One scalar field, trimmed; empty otherwise.
	 *
	 * @param array<string, mixed> $item  Raw record.
	 * @param string               $field Field.
	 * @return string
	 */
	private static function text( array $item, string $field ): string {
		return isset( $item[ $field ] ) && is_scalar( $item[ $field ] ) ? trim( (string) $item[ $field ] ) : '';
	}

	/**
	 * The default catalogue: a JSON file under uploads, when there is one.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function from_uploads(): array {
		$file = (string) wp_upload_dir()['basedir'] . '/prc/centres.json';
		if ( ! is_readable( $file ) ) {
			return array();
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- fichero local del sitio, no una URL.
		$data = json_decode( (string) file_get_contents( $file ), true );
		return is_array( $data ) ? $data : array();
	}

	/**
	 * Lower case, no accents, trimmed: what a search compares.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	private static function fold( string $value ): string {
		return mb_strtolower( trim( remove_accents( $value ) ), 'UTF-8' );
	}
}
