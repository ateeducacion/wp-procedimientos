<?php
/**
 * Local cached catalogue of educational centres.
 *
 * @package Prc
 */

namespace Prc\Centre;

use Prc\Meta\ProcedureMetaKeys;

/**
 * Acceso al catálogo de centros educativos cacheado localmente en WordPress.
 *
 * El catálogo maestro es externo y este aplicativo solo conserva una copia
 * local en una opción (autoload = false) para no hacer peticiones remotas al
 * renderizar formularios o validar solicitudes.
 */
final class CentreCatalogue {

	public const OPTION_CATALOGUE = 'prc_centres_catalogue';

	public const OPTION_STATUS = 'prc_centres_catalogue_status';

	/**
	 * All stored centres (both active and inactive), indexed by 8-digit code.
	 *
	 * @return array<string, array{code:string, name:string, island:string, municipality:string, type:string, zone?:string, ownership?:string, active:bool}>
	 */
	public static function all(): array {
		$raw = get_option( self::OPTION_CATALOGUE, array() );
		return is_array( $raw ) ? $raw : array();
	}

	/**
	 * All active centres, indexed by code.
	 *
	 * @return array<string, array{code:string, name:string, island:string, municipality:string, type:string, zone?:string, ownership?:string, active:bool}>
	 */
	public static function all_active(): array {
		$all = self::all();
		$out = array();
		foreach ( $all as $code => $centre ) {
			if ( ! empty( $centre['active'] ) ) {
				$out[ $code ] = $centre;
			}
		}
		return $out;
	}

	/**
	 * Map of active centres [ code => name ] sorted alphabetically by name.
	 *
	 * @return array<string, string>
	 */
	public static function active_options(): array {
		$active = self::all_active();
		$out    = array();
		foreach ( $active as $code => $centre ) {
			$name = isset( $centre['name'] ) ? trim( (string) $centre['name'] ) : '';
			if ( '' !== $name ) {
				$out[ (string) $code ] = $name;
			}
		}

		uasort(
			$out,
			static function ( string $a, string $b ): int {
				return strcoll( $a, $b );
			}
		);

		return $out;
	}

	/**
	 * Formatted list for Procedure CentreCatalog::all().
	 *
	 * La titularidad solo va si está guardada: la fuente no la trae, y es
	 * {@see \Prc\Domain\CentreCatalog} quien la deriva del tipo. Rellenarla
	 * aquí con «público» es lo que hacía que todo centro sincronizado saliera
	 * público (ADR-0042). Un catálogo guardado antes de la zona no la tiene:
	 * sale vacía.
	 *
	 * @return array<int, array<string, mixed>> {code, name, island, municipality, type, zone, active} and `ownership` when stored.
	 */
	public static function for_domain(): array {
		$out = array();
		foreach ( self::all() as $code => $centre ) {
			if ( ! is_array( $centre ) ) {
				continue;
			}
			$fila = array(
				'code'   => (string) $code,
				'active' => ! isset( $centre['active'] ) || (bool) $centre['active'],
			);
			foreach ( array( 'name', 'island', 'municipality', 'type', 'zone' ) as $campo ) {
				$fila[ $campo ] = isset( $centre[ $campo ] ) && is_scalar( $centre[ $campo ] ) ? (string) $centre[ $campo ] : '';
			}
			$titular = isset( $centre['ownership'] ) && is_string( $centre['ownership'] ) ? $centre['ownership'] : '';
			if ( '' !== ProcedureMetaKeys::in_list( $titular, ProcedureMetaKeys::ownerships() ) ) {
				$fila['ownership'] = $titular;
			}
			$out[] = $fila;
		}
		return $out;
	}

	/**
	 * Find a centre by code (active or inactive).
	 *
	 * @param string $code 8-digit official code.
	 * @return array{code:string, name:string, island:string, municipality:string, type:string, zone?:string, ownership?:string, active:bool}|null
	 */
	public static function find( string $code ): ?array {
		$code = trim( $code );
		if ( '' === $code ) {
			return null;
		}
		$all = self::all();
		return isset( $all[ $code ] ) && is_array( $all[ $code ] ) ? $all[ $code ] : null;
	}

	/**
	 * Check if a centre exists and is currently active.
	 *
	 * @param string $code 8-digit official code.
	 * @return bool
	 */
	public static function is_active( string $code ): bool {
		$centre = self::find( $code );
		return null !== $centre && ! empty( $centre['active'] );
	}

	/**
	 * Current synchronization status and metadata.
	 *
	 * @return array{schema_version:int, sha256:string, catalogue_updated_at:string, last_checked_at:string, last_success_at:string, last_error:string, record_count:int, active_count:int}
	 */
	public static function status(): array {
		$defaults = array(
			'schema_version'       => 0,
			'sha256'               => '',
			'catalogue_updated_at' => '',
			'last_checked_at'      => '',
			'last_success_at'      => '',
			'last_error'           => '',
			'record_count'         => 0,
			'active_count'         => 0,
		);

		$raw = get_option( self::OPTION_STATUS, array() );
		if ( ! is_array( $raw ) ) {
			return $defaults;
		}

		return array_merge( $defaults, $raw );
	}

	/**
	 * Total count of stored centres.
	 *
	 * @return int
	 */
	public static function count(): int {
		return count( self::all() );
	}

	/**
	 * Count of active centres.
	 *
	 * @return int
	 */
	public static function active_count(): int {
		return count( self::all_active() );
	}
}
