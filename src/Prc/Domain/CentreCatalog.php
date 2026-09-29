<?php
/**
 * The catalogue of schools, answered by a filter.
 *
 * @package Prc
 */

namespace Prc\Domain;

use Prc\Meta\ProcedureMetaKeys;

/**
 * El catálogo de centros: código, nombre y titularidad.
 *
 * El catálogo real es de quien despliega y no se versiona (ADR-0017): el
 * aplicativo lo **pregunta** con el filtro `prc_centres` y, si nadie
 * contesta, lee `wp-content/uploads/prc/centres.json`. Sin fichero, lista
 * vacía: se puede solicitar igual, con el nombre en blanco, porque el catálogo
 * puede ir por detrás de la realidad.
 */
final class CentreCatalog {

	/**
	 * Cuántos resultados devuelve una búsqueda como mucho.
	 */
	public const SEARCH_LIMIT = 20;

	/**
	 * Every school, normalised.
	 *
	 * @return array<int, array{code:string, name:string, ownership:string}>
	 */
	public static function all(): array {
		/**
		 * Filter the school catalogue.
		 *
		 * Quien despliega contesta desde un fragmento de código suelto; en el
		 * entorno local contesta el mu-plugin con una docena inventada.
		 *
		 * @param array<int, array<string, mixed>> $centres Schools as {code, name, ownership}.
		 */
		$default = class_exists( '\Prc\Centre\CentreCatalogue' ) ? \Prc\Centre\CentreCatalogue::for_domain() : array();
		if ( empty( $default ) ) {
			$default = self::from_uploads();
		}
		$raw = apply_filters( 'prc_centres', $default );
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$out    = array();
		$vistos = array();
		foreach ( $raw as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$code = isset( $item['code'] ) && is_scalar( $item['code'] ) ? trim( (string) $item['code'] ) : '';
			if ( '' === $code || isset( $vistos[ $code ] ) ) {
				continue;
			}
			$vistos[ $code ] = true;
			$out[]           = array(
				'code'      => $code,
				'name'      => isset( $item['name'] ) && is_scalar( $item['name'] ) ? trim( (string) $item['name'] ) : '',
				'ownership' => ProcedureMetaKeys::in_list( $item['ownership'] ?? '', ProcedureMetaKeys::ownerships(), ProcedureMetaKeys::OWNERSHIP_PUBLIC ),
			);
		}
		return $out;
	}

	/**
	 * One school by its code.
	 *
	 * @param string $code School code.
	 * @return array{code:string, name:string, ownership:string}|array{} Empty when it is not in the catalogue.
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
	 * @return array<int, array{code:string, name:string, ownership:string}> At most SEARCH_LIMIT.
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
