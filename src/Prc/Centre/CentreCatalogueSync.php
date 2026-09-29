<?php
/**
 * Synchronizer for the external educational centres catalogue.
 *
 * @package Prc
 */

namespace Prc\Centre;

use RuntimeException;

/**
 * Sincroniza el catálogo de centros educativos desde la fuente maestra externa.
 *
 * Utiliza manifest.json para verificar versiones y SHA-256 antes de descargar
 * centros.min.json. Reemplaza el catálogo local de forma atómica únicamente tras
 * validar íntegramente la estructura y el contenido.
 */
final class CentreCatalogueSync {

	public const OPTION_MANIFEST_URL = 'prc_centres_manifest_url';

	public const OPTION_CATALOGUE_URL = 'prc_centres_catalogue_url';

	public const OPTION_LOCK = 'prc_centres_sync_lock';

	public const LOCK_TTL = 300;

	public const CRON_HOOK = 'prc_centres_cron_sync';

	public const HTTP_TIMEOUT = 30;

	/**
	 * Register cron schedule and hook.
	 *
	 * @return void
	 */
	public static function register_cron(): void {
		add_action( self::CRON_HOOK, array( self::class, 'cron_sync' ) );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + 3600, 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Cron callback to sync periodically.
	 *
	 * @return void
	 */
	public static function cron_sync(): void {
		try {
			self::sync( false );
		} catch ( RuntimeException $e ) {
			// Las excepciones en cron quedan registradas en el estado de diagnóstico.
			unset( $e );
		}
	}

	/**
	 * Verify if a URL is a valid absolute HTTPS URL.
	 *
	 * @param string $url URL to validate.
	 * @return bool
	 */
	public static function is_valid_https_url( string $url ): bool {
		$url = trim( $url );
		if ( '' === $url ) {
			return false;
		}
		$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
		$host   = wp_parse_url( $url, PHP_URL_HOST );
		return 'https' === strtolower( (string) $scheme ) && '' !== trim( (string) $host );
	}

	/**
	 * Get the configured manifest URL.
	 *
	 * @return string
	 */
	public static function manifest_url(): string {
		$url = '';
		if ( defined( 'PRC_CENTRES_MANIFEST_URL' ) ) {
			$url = (string) PRC_CENTRES_MANIFEST_URL;
		}
		if ( '' === $url ) {
			$url = (string) get_option( self::OPTION_MANIFEST_URL, '' );
		}
		/**
		 * Filter the external manifest URL.
		 *
		 * @param string $url Manifest URL.
		 */
		$filtered = apply_filters( 'prc_centres_manifest_url', $url );
		return is_string( $filtered ) ? trim( $filtered ) : '';
	}

	/**
	 * Get the configured catalogue URL (centros.min.json).
	 *
	 * @param string $manifest_url Optional manifest URL to derive default path.
	 * @return string
	 */
	public static function catalogue_url( string $manifest_url = '' ): string {
		$url = '';
		if ( defined( 'PRC_CENTRES_CATALOGUE_URL' ) ) {
			$url = (string) PRC_CENTRES_CATALOGUE_URL;
		}
		if ( '' === $url ) {
			$url = (string) get_option( self::OPTION_CATALOGUE_URL, '' );
		}
		if ( '' === $url && '' !== $manifest_url ) {
			$dir = dirname( $manifest_url );
			if ( 'http:' === $dir || 'https:' === $dir ) {
				$dir = $manifest_url;
			}
			$url = trailingslashit( $dir ) . 'centros.min.json';
		}
		/**
		 * Filter the external catalogue URL.
		 *
		 * @param string $url Catalogue URL.
		 */
		$filtered = apply_filters( 'prc_centres_catalogue_url', $url );
		return is_string( $filtered ) ? trim( $filtered ) : '';
	}

	/**
	 * Acquire execution lock with an ownership token.
	 *
	 * Utiliza add_option() para garantizar concurrencia atómica sin condiciones
	 * de carrera. Si el candado previo expiró (proceso muerto), lo retira y
	 * lo vuelve a reclamar.
	 *
	 * @return string|false Token si se adquirió el candado, false si hay otra sincronización en curso.
	 */
	public static function acquire_lock() {
		$token   = wp_generate_password( 32, false );
		$payload = array(
			'token' => $token,
			'time'  => time(),
		);

		wp_cache_delete( self::OPTION_LOCK, 'options' );
		wp_cache_delete( 'notoptions', 'options' );

		if ( add_option( self::OPTION_LOCK, $payload, '', 'no' ) ) {
			return $token;
		}

		$current = get_option( self::OPTION_LOCK );
		if ( is_array( $current ) && isset( $current['time'] ) ) {
			$elapsed = time() - (int) $current['time'];
			if ( $elapsed > self::LOCK_TTL ) {
				global $wpdb;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Compare-and-delete atómico de candado expirado.
				$deleted = $wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s",
						self::OPTION_LOCK,
						maybe_serialize( $current )
					)
				);

				if ( $deleted ) {
					wp_cache_delete( self::OPTION_LOCK, 'options' );
					wp_cache_delete( 'notoptions', 'options' );

					if ( add_option( self::OPTION_LOCK, $payload, '', 'no' ) ) {
						return $token;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Release execution lock, only if the token matches.
	 *
	 * @param string $token Ownership token.
	 * @return bool True if released, false otherwise.
	 */
	public static function release_lock( string $token ): bool {
		wp_cache_delete( self::OPTION_LOCK, 'options' );
		$current = get_option( self::OPTION_LOCK );
		if ( is_array( $current ) && isset( $current['token'] ) && hash_equals( (string) $current['token'], $token ) ) {
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Compare-and-delete atómico para liberar candado propio.
			$deleted = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s",
					self::OPTION_LOCK,
					maybe_serialize( $current )
				)
			);
			if ( $deleted ) {
				wp_cache_delete( self::OPTION_LOCK, 'options' );
				wp_cache_delete( 'notoptions', 'options' );
				return true;
			}
		}
		return false;
	}

	/**
	 * Run synchronization from external source.
	 *
	 * @param bool $force Whether to force download even if SHA-256 is unchanged.
	 * @return array{status:string, sha256:string, records:int, active:int}
	 * @throws RuntimeException On download, validation or checksum failure.
	 */
	public static function sync( bool $force = false ): array {
		$token = self::acquire_lock();
		if ( false === $token ) {
			throw new RuntimeException( 'Hay otra sincronización de centros en curso. Espere a que finalice.' );
		}

		$status = CentreCatalogue::status();

		try {
			$manifest_url = self::manifest_url();
			if ( ! self::is_valid_https_url( $manifest_url ) ) {
				throw new RuntimeException( 'URL de manifest de centros inválida o no utiliza HTTPS.' );
			}

			// 1. Descargar manifest.json.
			$manifest_response = wp_safe_remote_get(
				$manifest_url,
				array(
					'timeout'    => self::HTTP_TIMEOUT,
					'sslverify'  => true,
					'user-agent' => 'WordPress/Prc',
				)
			);

			if ( is_wp_error( $manifest_response ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new RuntimeException( 'Error al descargar manifest.json: ' . $manifest_response->get_error_message() );
			}

			$code = wp_remote_retrieve_response_code( $manifest_response );
			if ( 200 !== $code ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new RuntimeException( sprintf( 'El servidor devolvió HTTP %d al solicitar manifest.json.', $code ) );
			}

			$manifest_body = wp_remote_retrieve_body( $manifest_response );
			$manifest      = json_decode( $manifest_body, true );

			if ( ! is_array( $manifest ) ) {
				throw new RuntimeException( 'El archivo manifest.json no contiene un JSON válido.' );
			}

			// 2. Validar manifest.json.
			if ( empty( $manifest['schema_version'] ) || 1 !== (int) $manifest['schema_version'] ) {
				throw new RuntimeException( 'Versión de esquema incompatible en manifest.json.' );
			}

			if ( empty( $manifest['files']['centros.min.json']['sha256'] ) ) {
				throw new RuntimeException( 'El manifest.json no declara la entrada de centros.min.json con su sha256.' );
			}

			$remote_sha256 = trim( (string) $manifest['files']['centros.min.json']['sha256'] );
			$remote_count  = isset( $manifest['files']['centros.min.json']['records'] )
				? (int) $manifest['files']['centros.min.json']['records']
				: 0;
			$updated_at    = isset( $manifest['catalogue_updated_at'] ) && is_scalar( $manifest['catalogue_updated_at'] )
				? trim( (string) $manifest['catalogue_updated_at'] )
				: '';

			// 3. Comparar hash: si no ha cambiado y no se fuerza, salir.
			if ( ! $force && $remote_sha256 === $status['sha256'] && ! empty( $status['sha256'] ) && CentreCatalogue::count() > 0 ) {
				$status['last_checked_at'] = current_time( 'mysql' );
				$status['last_error']      = '';
				update_option( CentreCatalogue::OPTION_STATUS, $status, false );

				return array(
					'status'  => 'unchanged',
					'sha256'  => $remote_sha256,
					'records' => $status['record_count'],
					'active'  => $status['active_count'],
				);
			}

			// 4. Descargar centros.min.json.
			$catalogue_url = self::catalogue_url( $manifest_url );
			if ( ! self::is_valid_https_url( $catalogue_url ) ) {
				throw new RuntimeException( 'URL del catálogo de centros inválida o no utiliza HTTPS.' );
			}

			$cat_response = wp_safe_remote_get(
				$catalogue_url,
				array(
					'timeout'    => 45,
					'sslverify'  => true,
					'user-agent' => 'WordPress/Prc',
				)
			);

			if ( is_wp_error( $cat_response ) ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new RuntimeException( 'Error al descargar centros.min.json: ' . $cat_response->get_error_message() );
			}

			$cat_code = wp_remote_retrieve_response_code( $cat_response );
			if ( 200 !== $cat_code ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new RuntimeException( sprintf( 'El servidor devolvió HTTP %d al solicitar centros.min.json.', $cat_code ) );
			}

			$cat_body = wp_remote_retrieve_body( $cat_response );

			// 5. Verificar SHA-256.
			$computed_sha256 = hash( 'sha256', $cat_body );
			if ( ! hash_equals( $remote_sha256, $computed_sha256 ) ) {
				throw new RuntimeException( 'El hash SHA-256 del archivo descargado no coincide con el declarado en manifest.json.' );
			}

			// 6. Validar JSON.
			$items = json_decode( $cat_body, true );
			if ( ! is_array( $items ) ) {
				throw new RuntimeException( 'El archivo centros.min.json no contiene un array JSON válido.' );
			}

			if ( $remote_count > 0 && count( $items ) !== $remote_count ) {
				$msg = sprintf( 'El número de registros (%d) no coincide con el declarado en el manifest (%d).', count( $items ), $remote_count );
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new RuntimeException( $msg );
			}

			$indexed      = array();
			$active_count = 0;

			foreach ( $items as $idx => $item ) {
				if ( ! is_array( $item ) ) {
					$msg = sprintf( 'Registro en posición %d no es un objeto válido.', $idx );
					// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
					throw new RuntimeException( $msg );
				}

				$code = isset( $item['code'] ) && is_scalar( $item['code'] ) ? trim( (string) $item['code'] ) : '';
				if ( 1 !== preg_match( '/^\d{8}$/', $code ) ) {
					$msg = sprintf( 'Código de centro inválido en registro %d (debe tener exactamente 8 dígitos): "%s".', $idx, $code );
					// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
					throw new RuntimeException( $msg );
				}

				if ( isset( $indexed[ $code ] ) ) {
					$msg = sprintf( 'Código oficial duplicado en centros.min.json: "%s".', $code );
					// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
					throw new RuntimeException( $msg );
				}

				$name = isset( $item['name'] ) && is_scalar( $item['name'] ) ? trim( (string) $item['name'] ) : '';
				if ( '' === $name ) {
					$msg = sprintf( 'Denominación vacía para el centro con código "%s".', $code );
					// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
					throw new RuntimeException( $msg );
				}

				if ( ! isset( $item['active'] ) || ! is_bool( $item['active'] ) ) {
					$msg = sprintf( 'El campo "active" debe ser booleano para el centro con código "%s".', $code );
					// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
					throw new RuntimeException( $msg );
				}

				$is_active = (bool) $item['active'];
				if ( $is_active ) {
					++$active_count;
				}

				$indexed[ $code ] = array(
					'code'         => $code,
					'name'         => $name,
					'island'       => isset( $item['island'] ) && is_scalar( $item['island'] ) ? trim( (string) $item['island'] ) : '',
					'municipality' => isset( $item['municipality'] ) && is_scalar( $item['municipality'] ) ? trim( (string) $item['municipality'] ) : '',
					'type'         => isset( $item['type'] ) && is_scalar( $item['type'] ) ? trim( (string) $item['type'] ) : '',
					'active'       => $is_active,
				);
			}

			// 7. Reemplazo atómico.
			update_option( CentreCatalogue::OPTION_CATALOGUE, $indexed, false );

			$status = array(
				'schema_version'       => 1,
				'sha256'               => $remote_sha256,
				'catalogue_updated_at' => $updated_at,
				'last_checked_at'      => current_time( 'mysql' ),
				'last_success_at'      => current_time( 'mysql' ),
				'last_error'           => '',
				'record_count'         => count( $indexed ),
				'active_count'         => $active_count,
			);
			update_option( CentreCatalogue::OPTION_STATUS, $status, false );

			return array(
				'status'  => 'updated',
				'sha256'  => $remote_sha256,
				'records' => count( $indexed ),
				'active'  => $active_count,
			);
		} catch ( RuntimeException $e ) {
			$status['last_checked_at'] = current_time( 'mysql' );
			$status['last_error']      = $e->getMessage();
			update_option( CentreCatalogue::OPTION_STATUS, $status, false );

			throw $e;
		} finally {
			self::release_lock( $token );
		}
	}
}
