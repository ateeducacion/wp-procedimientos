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
	 * Cache group whose `last_changed` stamps the memo: a cache flush, or
	 * {@see flush()}, leaves it behind.
	 */
	private const CACHE_GROUP = 'prc_centres';

	/**
	 * The catalogue already built in this request, and what it was built from.
	 *
	 * @var array{key:string|null, all:array<int, array<string, mixed>>, index:array<string, array<string, mixed>>|null}
	 */
	private static $memo = array(
		'key'   => null,
		'all'   => array(),
		'index' => null,
	);

	/**
	 * Hook registration: forget the memo when the catalogue or the private types change.
	 *
	 * @return void
	 */
	public static function register(): void {
		foreach ( array( 'added_option', 'updated_option', 'deleted_option' ) as $accion ) {
			add_action( $accion, array( self::class, 'on_option' ) );
		}
	}

	/**
	 * Forget the memo when one of the options it is built from changes.
	 *
	 * @param string $option Option name.
	 * @return void
	 */
	public static function on_option( $option ): void {
		if ( in_array( $option, array( \Prc\Centre\CentreCatalogue::OPTION_CATALOGUE, self::OPTION_PRIVATE_TYPES ), true ) ) {
			self::flush();
		}
	}

	/**
	 * Forget the catalogue built in this request. Tests call it too.
	 *
	 * @return void
	 */
	public static function flush(): void {
		self::$memo = array(
			'key'   => null,
			'all'   => array(),
			'index' => null,
		);
		wp_cache_delete( 'last_changed', self::CACHE_GROUP );
	}

	/**
	 * Every school, normalised.
	 *
	 * Se construye una vez por petición: la pantalla que pregunta por varios
	 * códigos no vuelve a recorrer el catálogo. Lo que lo cambia —la opción
	 * del catálogo, la de los tipos privados o quién contesta los filtros—
	 * lo vacía.
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
		$clave = self::memo_key();
		if ( $clave === self::$memo['key'] ) {
			return self::$memo['all'];
		}
		$default = class_exists( '\Prc\Centre\CentreCatalogue' ) ? \Prc\Centre\CentreCatalogue::for_domain() : array();
		if ( empty( $default ) ) {
			$default = self::from_uploads();
		}
		$raw = apply_filters( 'prc_centres', $default );
		$raw = is_array( $raw ) ? $raw : array();

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
		self::$memo = array(
			'key'   => $clave,
			'all'   => $out,
			'index' => null,
		);
		return $out;
	}

	/**
	 * What the memo depends on: the cache stamp and who answers the filters.
	 *
	 * @return string
	 */
	private static function memo_key(): string {
		global $wp_filter;
		$clave = (string) wp_cache_get_last_changed( self::CACHE_GROUP );
		foreach ( array( 'prc_centres', 'prc_private_centre_types' ) as $hook ) {
			$clave  .= '|' . $hook;
			$ganchos = isset( $wp_filter[ $hook ] ) && $wp_filter[ $hook ] instanceof \WP_Hook ? $wp_filter[ $hook ]->callbacks : array();
			foreach ( $ganchos as $prioridad => $funciones ) {
				$clave .= ':' . $prioridad . '=' . implode( ',', array_keys( $funciones ) );
			}
		}
		return $clave;
	}

	/**
	 * The catalogue indexed by code, for whoever looks up many codes at once.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function index(): array {
		$todos = self::all();
		if ( null === self::$memo['index'] ) {
			self::$memo['index'] = array_column( $todos, null, 'code' );
		}
		return self::$memo['index'];
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
		return self::index()[ $code ] ?? array();
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
	 * What the workshop offers to narrow a procedure: the types and islands
	 * of the catalogue, and the chosen ones even if the catalogue lost them.
	 *
	 * @param bool     $wanted  Whether the screen paints them; false reads nothing.
	 * @param string[] $types   Types already chosen.
	 * @param string[] $islands Islands already chosen.
	 * @return array{types:string[], islands:string[]}
	 */
	public static function choices( bool $wanted, array $types, array $islands ): array {
		if ( ! $wanted ) {
			return array(
				'types'   => array(),
				'islands' => array(),
			);
		}
		return array(
			'types'   => CentreEligibility::values( array_merge( self::values( 'type' ), $types ) ),
			'islands' => CentreEligibility::values( array_merge( self::values( 'island' ), $islands ) ),
		);
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
