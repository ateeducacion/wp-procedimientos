<?php
/**
 * Tests for the educational centres catalogue, synchronization and storage.
 *
 * @package Prc
 */

use Prc\Admin\Settings;
use Prc\Centre\CentreCatalogue;
use Prc\Centre\CentreCatalogueSync;
use Prc\Domain\CentreCatalog;
use Prc\Meta\ApplicationMetaKeys;
use Prc\PublicFront\Applications;

/**
 * Pruebas unitarias del catálogo de centros educativos en procedimientos.
 */
class Test_Centres extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Set up test environment before each test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();
		delete_option( CentreCatalogue::OPTION_CATALOGUE );
		delete_option( CentreCatalogue::OPTION_STATUS );
		delete_option( CentreCatalogueSync::OPTION_MANIFEST_URL );
		delete_option( CentreCatalogueSync::OPTION_CATALOGUE_URL );
		delete_option( CentreCatalogueSync::OPTION_LOCK );
	}

	/**
	 * Clean up test environment after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		delete_option( CentreCatalogue::OPTION_CATALOGUE );
		delete_option( CentreCatalogue::OPTION_STATUS );
		delete_option( CentreCatalogueSync::OPTION_MANIFEST_URL );
		delete_option( CentreCatalogueSync::OPTION_CATALOGUE_URL );
		delete_option( CentreCatalogueSync::OPTION_LOCK );
		remove_all_filters( 'pre_http_request' );
		remove_all_filters( 'prc_centres' );
		remove_all_filters( 'prc_centres_manifest_url' );
		remove_all_filters( 'prc_centres_catalogue_url' );
		parent::tear_down();
	}

	/**
	 * Helper to create a fake manifest and catalogue payload.
	 *
	 * @param array<int, array<string, mixed>> $items Records.
	 * @return array{manifest_json:string, catalogue_json:string, sha256:string}
	 */
	private function make_payload( array $items ): array {
		$catalogue_json = (string) wp_json_encode( $items );
		$sha256         = hash( 'sha256', $catalogue_json );
		$manifest       = array(
			'schema_version'       => 1,
			'catalogue_updated_at' => '2026-09-20T10:00:00Z',
			'files'                => array(
				'centros.min.json' => array(
					'sha256'  => $sha256,
					'records' => count( $items ),
					'bytes'   => strlen( $catalogue_json ),
				),
			),
		);
		$manifest_json  = (string) wp_json_encode( $manifest );

		return array(
			'manifest_json'  => $manifest_json,
			'catalogue_json' => $catalogue_json,
			'sha256'         => $sha256,
		);
	}

	/**
	 * Helper to mock HTTP responses for manifest and catalogue.
	 *
	 * @param string|WP_Error $manifest_body  Manifest response body or WP_Error.
	 * @param string|WP_Error $catalogue_body Catalogue response body or WP_Error.
	 * @param int             $manifest_code  HTTP code for manifest.
	 * @param int             $catalogue_code HTTP code for catalogue.
	 * @return void
	 */
	private function mock_http( $manifest_body, $catalogue_body, int $manifest_code = 200, int $catalogue_code = 200 ): void {
		add_filter(
			'pre_http_request',
			static function ( $pre, $parsed_args, $url ) use ( $manifest_body, $catalogue_body, $manifest_code, $catalogue_code ) {
				unset( $pre, $parsed_args );
				if ( false !== strpos( $url, 'manifest.json' ) ) {
					if ( is_wp_error( $manifest_body ) ) {
						return $manifest_body;
					}
					return array(
						'response' => array( 'code' => $manifest_code ),
						'body'     => (string) $manifest_body,
					);
				}
				if ( false !== strpos( $url, 'centros.min.json' ) ) {
					if ( is_wp_error( $catalogue_body ) ) {
						return $catalogue_body;
					}
					return array(
						'response' => array( 'code' => $catalogue_code ),
						'body'     => (string) $catalogue_body,
					);
				}
				return false;
			},
			10,
			3
		);
	}

	/**
	 * 1. Catálogo válido se almacena en la opción con su status.
	 */
	public function test_valid_catalogue_is_stored(): void {
		$items = array(
			array(
				'code'         => '90000101',
				'name'         => 'CIFP Ejemplo',
				'island'       => 'Zona Norte',
				'municipality' => 'Municipio 1',
				'type'         => 'CIFP',
				'active'       => true,
			),
			array(
				'code'         => '90000102',
				'name'         => 'CEIP Antiguo',
				'island'       => 'Zona Sur',
				'municipality' => 'Municipio 2',
				'type'         => 'CEIP',
				'active'       => false,
			),
		);

		$payload = $this->make_payload( $items );
		$this->mock_http( $payload['manifest_json'], $payload['catalogue_json'] );

		update_option( CentreCatalogueSync::OPTION_MANIFEST_URL, 'https://example.org/manifest.json', false );

		$result = CentreCatalogueSync::sync();

		$this->assertSame( 'updated', $result['status'] );
		$this->assertSame( 2, $result['records'] );
		$this->assertSame( 1, $result['active'] );

		$stored = CentreCatalogue::all();
		$this->assertCount( 2, $stored );
		$this->assertArrayHasKey( '90000101', $stored );
		$this->assertArrayHasKey( '90000102', $stored );
		$this->assertSame( 'CIFP Ejemplo', $stored['90000101']['name'] );
		$this->assertTrue( $stored['90000101']['active'] );
		$this->assertFalse( $stored['90000102']['active'] );

		$status = CentreCatalogue::status();
		$this->assertSame( 1, $status['schema_version'] );
		$this->assertSame( $payload['sha256'], $status['sha256'] );
		$this->assertSame( 2, $status['record_count'] );
		$this->assertSame( 1, $status['active_count'] );
		$this->assertEmpty( $status['last_error'] );

		// Métodos de conteo y consulta.
		$this->assertSame( 2, CentreCatalogue::count() );
		$this->assertSame( 1, CentreCatalogue::active_count() );
		$this->assertNotNull( CentreCatalogue::find( '90000101' ) );
		$this->assertNull( CentreCatalogue::find( '99999999' ) );
		$this->assertNull( CentreCatalogue::find( '' ) );
		$this->assertTrue( CentreCatalogue::is_active( '90000101' ) );
		$this->assertFalse( CentreCatalogue::is_active( '90000102' ) );
		$this->assertFalse( CentreCatalogue::is_active( '99999999' ) );
	}

	/**
	 * 2. Catálogo con hash incorrecto se rechaza y no sobreescribe.
	 */
	public function test_invalid_hash_is_rejected(): void {
		$items = array(
			array(
				'code'         => '90000101',
				'name'         => 'CIFP Bueno',
				'island'       => 'Zona Norte',
				'municipality' => 'Municipio 1',
				'type'         => 'CIFP',
				'active'       => true,
			),
		);

		$payload  = $this->make_payload( $items );
		$manifest = json_decode( $payload['manifest_json'], true );
		$manifest['files']['centros.min.json']['sha256'] = '0000000000000000000000000000000000000000000000000000000000000000';

		$bad_manifest = (string) wp_json_encode( $manifest );

		$this->mock_http( $bad_manifest, $payload['catalogue_json'] );
		update_option( CentreCatalogueSync::OPTION_MANIFEST_URL, 'https://example.org/manifest.json', false );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'El hash SHA-256 del archivo descargado no coincide' );

		CentreCatalogueSync::sync();
	}

	/**
	 * 3. JSON incorrecto en centros.min.json se rechaza.
	 */
	public function test_invalid_json_is_rejected(): void {
		$bad_body = '<html>404 Not Found</html>';
		$sha256   = hash( 'sha256', $bad_body );
		$manifest = (string) wp_json_encode(
			array(
				'schema_version' => 1,
				'files'          => array(
					'centros.min.json' => array(
						'sha256'  => $sha256,
						'records' => 1,
					),
				),
			)
		);

		$this->mock_http( $manifest, $bad_body );
		update_option( CentreCatalogueSync::OPTION_MANIFEST_URL, 'https://example.org/manifest.json', false );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'El archivo centros.min.json no contiene un array JSON válido' );

		CentreCatalogueSync::sync();
	}

	/**
	 * 4. Código oficial duplicado se rechaza.
	 */
	public function test_duplicate_code_is_rejected(): void {
		$items = array(
			array(
				'code'         => '90000101',
				'name'         => 'CIFP Uno',
				'island'       => 'Zona 1',
				'municipality' => 'Mun 1',
				'type'         => 'CIFP',
				'active'       => true,
			),
			array(
				'code'         => '90000101',
				'name'         => 'CIFP Duplicado',
				'island'       => 'Zona 2',
				'municipality' => 'Mun 2',
				'type'         => 'CIFP',
				'active'       => true,
			),
		);

		$payload = $this->make_payload( $items );
		$this->mock_http( $payload['manifest_json'], $payload['catalogue_json'] );
		update_option( CentreCatalogueSync::OPTION_MANIFEST_URL, 'https://example.org/manifest.json', false );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Código oficial duplicado en centros.min.json: "90000101"' );

		CentreCatalogueSync::sync();
	}

	/**
	 * 5. Manifest con versión incompatible se rechaza.
	 */
	public function test_incompatible_manifest_schema_is_rejected(): void {
		$manifest = (string) wp_json_encode(
			array(
				'schema_version' => 2,
				'files'          => array(
					'centros.min.json' => array(
						'sha256'  => 'abcdef',
						'records' => 1,
					),
				),
			)
		);

		$this->mock_http( $manifest, '[]' );
		update_option( CentreCatalogueSync::OPTION_MANIFEST_URL, 'https://example.org/manifest.json', false );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Versión de esquema incompatible' );

		CentreCatalogueSync::sync();
	}

	/**
	 * 6. Error HTTP conserva el último catálogo bueno local.
	 */
	public function test_http_error_preserves_last_good_catalogue(): void {
		$initial = array(
			'90000101' => array(
				'code'         => '90000101',
				'name'         => 'Centro Existente',
				'island'       => 'Zona',
				'municipality' => 'Mun',
				'type'         => 'CEIP',
				'active'       => true,
			),
		);
		update_option( CentreCatalogue::OPTION_CATALOGUE, $initial, false );
		$initial_status = array(
			'schema_version'       => 1,
			'sha256'               => 'prev_sha256',
			'catalogue_updated_at' => '2026-09-19',
			'last_checked_at'      => '2026-09-19 10:00:00',
			'last_success_at'      => '2026-09-19 10:00:00',
			'last_error'           => '',
			'record_count'         => 1,
			'active_count'         => 1,
		);
		update_option( CentreCatalogue::OPTION_STATUS, $initial_status, false );

		$this->mock_http( '', '', 500, 500 );
		update_option( CentreCatalogueSync::OPTION_MANIFEST_URL, 'https://example.org/manifest.json', false );

		try {
			CentreCatalogueSync::sync();
			$this->fail( 'Se esperaba una excepción.' );
		} catch ( RuntimeException $e ) {
			$this->assertStringContainsString( 'HTTP 500', $e->getMessage() );
		}

		$stored = CentreCatalogue::all();
		$this->assertCount( 1, $stored );
		$this->assertSame( 'Centro Existente', $stored['90000101']['name'] );

		$status = CentreCatalogue::status();
		$this->assertSame( 'prev_sha256', $status['sha256'] );
		$this->assertSame( '2026-09-19 10:00:00', $status['last_success_at'] );
		$this->assertSame( 1, $status['record_count'] );
		$this->assertStringContainsString( 'HTTP 500', $status['last_error'] );
		$this->assertNotSame( '2026-09-19 10:00:00', $status['last_checked_at'] );
	}

	/**
	 * 7. Mismo hash no vuelve a descargar centros.min.json.
	 */
	public function test_same_hash_does_not_download_again(): void {
		$items   = array(
			array(
				'code'         => '90000101',
				'name'         => 'CIFP Ejemplo',
				'island'       => 'Zona',
				'municipality' => 'Mun',
				'type'         => 'CIFP',
				'active'       => true,
			),
		);
		$payload = $this->make_payload( $items );
		$this->mock_http( $payload['manifest_json'], $payload['catalogue_json'] );
		update_option( CentreCatalogueSync::OPTION_MANIFEST_URL, 'https://example.org/manifest.json', false );

		$res1 = CentreCatalogueSync::sync();
		$this->assertSame( 'updated', $res1['status'] );

		$this->mock_http( $payload['manifest_json'], '', 200, 500 );

		$res2 = CentreCatalogueSync::sync();
		$this->assertSame( 'unchanged', $res2['status'] );
	}

	/**
	 * 8. Selector contiene únicamente centros activos.
	 */
	public function test_selector_contains_only_active_centres(): void {
		$catalog = array(
			'90000101' => array(
				'code'         => '90000101',
				'name'         => 'CIFP Activo',
				'island'       => 'Zona',
				'municipality' => 'Mun',
				'type'         => 'CIFP',
				'active'       => true,
			),
			'90000102' => array(
				'code'         => '90000102',
				'name'         => 'CEIP Cerrado',
				'island'       => 'Zona',
				'municipality' => 'Mun',
				'type'         => 'CEIP',
				'active'       => false,
			),
		);
		update_option( CentreCatalogue::OPTION_CATALOGUE, $catalog, false );

		$options = CentreCatalogue::active_options();
		$this->assertArrayHasKey( '90000101', $options );
		$this->assertSame( 'CIFP Activo', $options['90000101'] );
		$this->assertArrayNotHasKey( '90000102', $options );
	}

	/**
	 * 9. CentreCatalog::all() usa CentreCatalogue::for_domain() cuando está poblado.
	 */
	public function test_centre_catalog_uses_centre_catalogue(): void {
		$catalog = array(
			'90000101' => array(
				'code'         => '90000101',
				'name'         => 'CIFP En Icod',
				'island'       => 'Zona',
				'municipality' => 'Mun',
				'type'         => 'CIFP',
				'active'       => true,
			),
		);
		update_option( CentreCatalogue::OPTION_CATALOGUE, $catalog, false );

		$all = CentreCatalog::all();
		$this->assertNotEmpty( $all );
		$this->assertSame( '90000101', $all[0]['code'] );
		$this->assertSame( 'CIFP En Icod', $all[0]['name'] );

		$found = CentreCatalog::find( '90000101' );
		$this->assertSame( 'CIFP En Icod', $found['name'] );
	}

	/**
	 * 10. Applications::save() guarda código oficial y snapshot textual del nombre.
	 */
	public function test_applications_save_stores_code_and_name_snapshot(): void {
		$catalog = array(
			'90000101' => array(
				'code'         => '90000101',
				'name'         => 'CIFP Oficial',
				'island'       => 'Zona',
				'municipality' => 'Mun',
				'type'         => 'CIFP',
				'active'       => true,
			),
		);
		update_option( CentreCatalogue::OPTION_CATALOGUE, $catalog, false );

		$autor       = $this->school_head( '90000101' );
		$admin       = $this->administrator();
		$procedim_id = $this->procedure( $admin );

		$app_id = Applications::save(
			$procedim_id,
			$autor,
			array(
				'position'    => 'director',
				'coordinator' => array(
					'name'  => 'Coord',
					'email' => 'c@example.org',
				),
				'answers'     => array(),
			)
		);

		$this->assertGreaterThan( 0, $app_id );
		$this->assertSame( '90000101', get_post_meta( $app_id, ApplicationMetaKeys::CENTRE_CODE, true ) );
		$this->assertSame( 'CIFP Oficial', get_post_meta( $app_id, ApplicationMetaKeys::CENTRE_NAME, true ) );
	}

	/**
	 * 11. Modificar el catálogo no altera snapshots de solicitudes históricas.
	 */
	public function test_modifying_catalogue_does_not_alter_application_snapshot(): void {
		$catalog = array(
			'90000101' => array(
				'code'         => '90000101',
				'name'         => 'Denominación Antigua',
				'island'       => 'Zona',
				'municipality' => 'Mun',
				'type'         => 'CIFP',
				'active'       => true,
			),
		);
		update_option( CentreCatalogue::OPTION_CATALOGUE, $catalog, false );

		$autor       = $this->school_head( '90000101' );
		$admin       = $this->administrator();
		$procedim_id = $this->procedure( $admin );

		$app_id = Applications::save(
			$procedim_id,
			$autor,
			array(
				'position'    => 'director',
				'coordinator' => array(
					'name'  => 'Coord',
					'email' => 'c@example.org',
				),
				'answers'     => array(),
			)
		);

		// Cambiar el catálogo (nuevo nombre e inactivo).
		$updated_catalog = array(
			'90000101' => array(
				'code'         => '90000101',
				'name'         => 'Denominación Nueva',
				'island'       => 'Zona',
				'municipality' => 'Mun',
				'type'         => 'CIFP',
				'active'       => false,
			),
		);
		update_option( CentreCatalogue::OPTION_CATALOGUE, $updated_catalog, false );

		// El snapshot en la solicitud previa se conserva intacto.
		$this->assertSame( '90000101', get_post_meta( $app_id, ApplicationMetaKeys::CENTRE_CODE, true ) );
		$this->assertSame( 'Denominación Antigua', get_post_meta( $app_id, ApplicationMetaKeys::CENTRE_NAME, true ) );
	}

	/**
	 * 12. Pantalla de ajustes comprueba capacidad administrativa.
	 */
	public function test_manual_refresh_checks_capability(): void {
		$user_id = $this->factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$this->expectException( WPDieException::class );
		Settings::render();
	}

	/**
	 * 13. Settings::render renderiza correctamente para administrador.
	 */
	public function test_settings_centres_render_page_admin(): void {
		$admin = $this->administrator();
		$this->acting_as( $admin );

		// 1. Catálogo vacío con notices.
		$_GET['updated'] = 'synced';
		$_GET['msg']     = 'Catálogo actualizado';
		ob_start();
		Settings::render();
		$html = (string) ob_get_clean();
		$this->assertStringContainsString( 'Catálogo actualizado', $html );
		$this->assertStringContainsString( 'No disponible (catálogo vacío)', $html );
		$this->assertStringContainsString( 'Actualizar catálogo ahora', $html );

		// 2. Con error y catálogo poblado.
		$catalog = array(
			'90000101' => array(
				'code'         => '90000101',
				'name'         => 'CIFP Ejemplo',
				'island'       => 'Zona',
				'municipality' => 'Mun',
				'type'         => 'CIFP',
				'active'       => true,
			),
		);
		update_option( CentreCatalogue::OPTION_CATALOGUE, $catalog, false );
		update_option(
			CentreCatalogue::OPTION_STATUS,
			array(
				'schema_version'       => 1,
				'sha256'               => 'abcdef',
				'catalogue_updated_at' => '2026-09-20',
				'last_checked_at'      => '2026-09-20 12:00:00',
				'last_success_at'      => '2026-09-20 12:00:00',
				'last_error'           => 'Fallo previo',
				'record_count'         => 1,
				'active_count'         => 1,
			),
			false
		);

		unset( $_GET['msg'], $_GET['updated'] );
		$_GET['error'] = 'Error fatal simulado';
		ob_start();
		Settings::render();
		$html2 = (string) ob_get_clean();
		$this->assertStringContainsString( 'Error fatal simulado', $html2 );
		$this->assertStringContainsString( 'Fallo previo', $html2 );
		$this->assertStringContainsString( 'abcdef', $html2 );
		$this->assertStringContainsString( 'Disponible', $html2 );
	}

	/**
	 * 14. Settings::handle_actions maneja sync_centres.
	 */
	public function test_settings_handle_actions(): void {
		$admin = $this->administrator();
		$this->acting_as( $admin );
		set_current_screen( 'edit.php?post_type=prc_procedure' );

		// 1. sync_centres con éxito.
		$items   = array(
			array(
				'code'         => '90000101',
				'name'         => 'CIFP Test',
				'island'       => 'Zona',
				'municipality' => 'Mun',
				'type'         => 'CIFP',
				'active'       => true,
			),
		);
		$payload = $this->make_payload( $items );
		$this->mock_http( $payload['manifest_json'], $payload['catalogue_json'] );
		update_option( CentreCatalogueSync::OPTION_MANIFEST_URL, 'https://example.org/manifest.json', false );

		$this->post(
			array(
				'prc_action' => 'sync_centres',
			),
			Settings::NONCE_SYNC_CENTRES,
			'_prc_centres_nonce'
		);

		$url = $this->exit_url( array( Settings::class, 'handle_actions' ) );
		$this->assertNotNull( $url );
		$this->assertSame( 'synced', $this->query_arg( $url, 'updated' ) );

		// 2. sync_centres con excepción.
		$this->mock_http( '', '', 500, 500 );
		$this->post(
			array(
				'prc_action' => 'sync_centres',
			),
			Settings::NONCE_SYNC_CENTRES,
			'_prc_centres_nonce'
		);
		$url_err = $this->exit_url( array( Settings::class, 'handle_actions' ) );
		$this->assertNotNull( $url_err );
		$this->assertNotEmpty( $this->query_arg( $url_err, 'error' ) );

		// 3. Usuario sin permisos no ejecuta nada.
		$sub = $this->factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->acting_as( $sub );
		$this->post(
			array(
				'prc_action' => 'sync_centres',
			),
			Settings::NONCE_SYNC_CENTRES,
			'_prc_centres_nonce'
		);
		$url_sub = $this->exit_url( array( Settings::class, 'handle_actions' ) );
		$this->assertNull( $url_sub );
	}

	/**
	 * 15. CentreCli y CentreSettings han sido eliminados de la arquitectura.
	 */
	public function test_centre_cli_and_centre_settings_do_not_exist(): void {
		$this->assertFalse( class_exists( 'Prc\Centre\CentreCli' ) );
		$this->assertFalse( class_exists( 'Prc\Centre\CentreSettings' ) );
	}

	/**
	 * 16. CentreCatalogueSync: WP-Cron, locks atómicos y URLs configurables.
	 */
	public function test_sync_cron_locks_and_urls(): void {
		// 1. Cron.
		CentreCatalogueSync::register_cron();
		$this->assertNotFalse( has_action( CentreCatalogueSync::CRON_HOOK, array( CentreCatalogueSync::class, 'cron_sync' ) ) );
		$this->assertNotFalse( wp_next_scheduled( CentreCatalogueSync::CRON_HOOK ) );

		// cron_sync() no propaga excepción si falla.
		$this->mock_http( '', '', 500, 500 );
		CentreCatalogueSync::cron_sync(); // No lanza excepción.

		// 2. Locks atómicos con token.
		$token = CentreCatalogueSync::acquire_lock();
		$this->assertIsString( $token );
		$this->assertNotEmpty( $token );
		$this->assertFalse( CentreCatalogueSync::acquire_lock() );
		$this->assertFalse( CentreCatalogueSync::release_lock( 'wrong-token' ) );
		$this->assertTrue( CentreCatalogueSync::release_lock( $token ) );

		// Expiración de candado: tras 300 segundos se recupera.
		$token1 = CentreCatalogueSync::acquire_lock();
		$this->assertIsString( $token1 );
		update_option(
			CentreCatalogueSync::OPTION_LOCK,
			array(
				'token' => $token1,
				'time'  => time() - 301,
			)
		);
		$token2 = CentreCatalogueSync::acquire_lock();
		$this->assertIsString( $token2 );
		$this->assertNotSame( $token1, $token2 );
		$this->assertTrue( CentreCatalogueSync::release_lock( $token2 ) );

		// 3. Carrera de recuperación de candado caducado: compare-and-delete atómico.
		$expired_token = 'token-antiguo';
		$expired_time  = time() - 305;
		$expired_lock  = array(
			'token' => $expired_token,
			'time'  => $expired_time,
		);
		update_option( CentreCatalogueSync::OPTION_LOCK, $expired_lock, false );

		// Simulamos que otro proceso B se adelanta y adquiere un candado nuevo legítimo.
		$new_lock = array(
			'token' => 'token-nuevo-proceso-b',
			'time'  => time(),
		);
		update_option( CentreCatalogueSync::OPTION_LOCK, $new_lock, false );

		// Proceso A, que tenía en memoria $expired_lock, no debe poder borrar el lock nuevo.
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Simulación de carrera en test.
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s",
				CentreCatalogueSync::OPTION_LOCK,
				maybe_serialize( $expired_lock )
			)
		);
		$this->assertSame( 0, $deleted );
		$current_lock = get_option( CentreCatalogueSync::OPTION_LOCK );
		$this->assertSame( 'token-nuevo-proceso-b', $current_lock['token'] );

		// 4. URLs.
		update_option( CentreCatalogueSync::OPTION_MANIFEST_URL, 'https://example.org/directorio/manifest.json', false );
		$this->assertSame( 'https://example.org/directorio/manifest.json', CentreCatalogueSync::manifest_url() );
		$this->assertSame( 'https://example.org/directorio/centros.min.json', CentreCatalogueSync::catalogue_url( 'https://example.org/directorio/manifest.json' ) );

		// Filtros de URL.
		add_filter(
			'prc_centres_manifest_url',
			static function () {
				return 'https://filtrado.org/manifest.json';
			}
		);
		add_filter(
			'prc_centres_catalogue_url',
			static function () {
				return 'https://filtrado.org/centros.min.json';
			}
		);
		$this->assertSame( 'https://filtrado.org/manifest.json', CentreCatalogueSync::manifest_url() );
		$this->assertSame( 'https://filtrado.org/centros.min.json', CentreCatalogueSync::catalogue_url() );
	}

	/**
	 * 17. CentreCatalogueSync: validación de URLs HTTPS obligatorias.
	 */
	public function test_https_validation(): void {
		$this->assertTrue( CentreCatalogueSync::is_valid_https_url( 'https://example.org/manifest.json' ) );
		$this->assertFalse( CentreCatalogueSync::is_valid_https_url( 'http://example.org/manifest.json' ) );
		$this->assertFalse( CentreCatalogueSync::is_valid_https_url( 'ftp://example.org/manifest.json' ) );
		$this->assertFalse( CentreCatalogueSync::is_valid_https_url( 'file:///etc/passwd' ) );
		$this->assertFalse( CentreCatalogueSync::is_valid_https_url( '//example.org/manifest.json' ) );
		$this->assertFalse( CentreCatalogueSync::is_valid_https_url( '/manifest.json' ) );
		$this->assertFalse( CentreCatalogueSync::is_valid_https_url( '' ) );
		$this->assertFalse( CentreCatalogueSync::is_valid_https_url( 'https://' ) );
	}

	/**
	 * 18. CentreCatalogueSync: ramas de error en sync.
	 */
	public function test_sync_error_branches(): void {
		// 1. Bloqueo ya adquirido.
		$token = CentreCatalogueSync::acquire_lock();
		$this->assertIsString( $token );
		try {
			CentreCatalogueSync::sync();
			$this->fail( 'Se esperaba fallo por bloqueo.' );
		} catch ( RuntimeException $e ) {
			$this->assertStringContainsString( 'otra sincronización de centros en curso', $e->getMessage() );
		}
		CentreCatalogueSync::release_lock( $token );

		// 2. URL de manifest vacía.
		delete_option( CentreCatalogueSync::OPTION_MANIFEST_URL );
		try {
			CentreCatalogueSync::sync();
			$this->fail( 'Se esperaba fallo por URL vacía.' );
		} catch ( RuntimeException $e ) {
			$this->assertStringContainsString( 'no utiliza HTTPS', $e->getMessage() );
		}

		// 3. URL de manifest no HTTPS.
		update_option( CentreCatalogueSync::OPTION_MANIFEST_URL, 'http://example.org/manifest.json', false );
		try {
			CentreCatalogueSync::sync();
			$this->fail( 'Se esperaba fallo por URL no HTTPS.' );
		} catch ( RuntimeException $e ) {
			$this->assertStringContainsString( 'no utiliza HTTPS', $e->getMessage() );
		}

		update_option( CentreCatalogueSync::OPTION_MANIFEST_URL, 'https://example.org/manifest.json', false );

		// 4. Error de red (WP_Error) al descargar manifest.
		$this->mock_http( new WP_Error( 'http_err', 'Fallo de conexión' ), '' );
		try {
			CentreCatalogueSync::sync();
			$this->fail( 'Se esperaba fallo por WP_Error en manifest.' );
		} catch ( RuntimeException $e ) {
			$this->assertStringContainsString( 'Fallo de conexión', $e->getMessage() );
		}

		// 5. Manifest con JSON inválido.
		$this->mock_http( 'esto no es json', '' );
		try {
			CentreCatalogueSync::sync();
			$this->fail( 'Se esperaba fallo por JSON inválido en manifest.' );
		} catch ( RuntimeException $e ) {
			$this->assertStringContainsString( 'JSON válido', $e->getMessage() );
		}

		// 6. Manifest sin sha256.
		$bad_manifest = (string) wp_json_encode(
			array(
				'schema_version' => 1,
				'files'          => array( 'centros.min.json' => array() ),
			)
		);
		$this->mock_http( $bad_manifest, '[]' );
		try {
			CentreCatalogueSync::sync();
			$this->fail( 'Se esperaba fallo por sha256 ausente en manifest.' );
		} catch ( RuntimeException $e ) {
			$this->assertStringContainsString( 'no declara la entrada de centros.min.json con su sha256', $e->getMessage() );
		}

		// 7. URL de catálogo no HTTPS.
		$payload = $this->make_payload( array() );
		$this->mock_http( $payload['manifest_json'], '[]' );
		add_filter(
			'prc_centres_catalogue_url',
			static function () {
				return 'http://inseguro.org/centros.min.json';
			}
		);
		try {
			CentreCatalogueSync::sync();
			$this->fail( 'Se esperaba fallo por catálogo no HTTPS.' );
		} catch ( RuntimeException $e ) {
			$this->assertStringContainsString( 'no utiliza HTTPS', $e->getMessage() );
		}
		remove_all_filters( 'prc_centres_catalogue_url' );

		// 8. Error de red (WP_Error) al descargar centros.min.json.
		$this->mock_http( $payload['manifest_json'], new WP_Error( 'cat_err', 'Error al descargar catálogo' ) );
		try {
			CentreCatalogueSync::sync();
			$this->fail( 'Se esperaba fallo por WP_Error en catálogo.' );
		} catch ( RuntimeException $e ) {
			$this->assertStringContainsString( 'Error al descargar catálogo', $e->getMessage() );
		}

		// 9. HTTP distinto de 200 en centros.min.json.
		$this->mock_http( $payload['manifest_json'], 'Error', 200, 403 );
		try {
			CentreCatalogueSync::sync();
			$this->fail( 'Se esperaba fallo por HTTP 403 en catálogo.' );
		} catch ( RuntimeException $e ) {
			$this->assertStringContainsString( 'HTTP 403', $e->getMessage() );
		}

		// 10. Discrepancia de recuento declarado vs contenido.
		$manifest_count_mismatch = array(
			'schema_version' => 1,
			'files'          => array(
				'centros.min.json' => array(
					'sha256'  => hash( 'sha256', '[]' ),
					'records' => 5,
				),
			),
		);
		$this->mock_http( (string) wp_json_encode( $manifest_count_mismatch ), '[]' );
		try {
			CentreCatalogueSync::sync();
			$this->fail( 'Se esperaba fallo por discrepancia de recuento.' );
		} catch ( RuntimeException $e ) {
			$this->assertStringContainsString( 'no coincide con el declarado en el manifest', $e->getMessage() );
		}

		// 11. Elemento del array no es un objeto.
		$bad_item_payload = $this->make_payload( array( 'no es un objeto' ) );
		$this->mock_http( $bad_item_payload['manifest_json'], $bad_item_payload['catalogue_json'] );
		try {
			CentreCatalogueSync::sync();
			$this->fail( 'Se esperaba fallo por registro no array.' );
		} catch ( RuntimeException $e ) {
			$this->assertStringContainsString( 'no es un objeto válido', $e->getMessage() );
		}

		// 12. Código de centro con formato inválido (no son 8 dígitos).
		$bad_code_payload = $this->make_payload(
			array(
				array(
					'code'   => '1234',
					'name'   => 'Test',
					'active' => true,
				),
			)
		);
		$this->mock_http( $bad_code_payload['manifest_json'], $bad_code_payload['catalogue_json'] );
		try {
			CentreCatalogueSync::sync();
			$this->fail( 'Se esperaba fallo por código no de 8 dígitos.' );
		} catch ( RuntimeException $e ) {
			$this->assertStringContainsString( 'debe tener exactamente 8 dígitos', $e->getMessage() );
		}

		// 13. Denominación vacía.
		$empty_name_payload = $this->make_payload(
			array(
				array(
					'code'   => '90000101',
					'name'   => '',
					'active' => true,
				),
			)
		);
		$this->mock_http( $empty_name_payload['manifest_json'], $empty_name_payload['catalogue_json'] );
		try {
			CentreCatalogueSync::sync();
			$this->fail( 'Se esperaba fallo por denominación vacía.' );
		} catch ( RuntimeException $e ) {
			$this->assertStringContainsString( 'Denominación vacía', $e->getMessage() );
		}

		// 14. Campo active no es booleano.
		$bad_active_payload = $this->make_payload(
			array(
				array(
					'code'   => '90000101',
					'name'   => 'CIFP Test',
					'active' => 'si',
				),
			)
		);
		$this->mock_http( $bad_active_payload['manifest_json'], $bad_active_payload['catalogue_json'] );
		try {
			CentreCatalogueSync::sync();
			$this->fail( 'Se esperaba fallo por active no booleano.' );
		} catch ( RuntimeException $e ) {
			$this->assertStringContainsString( 'debe ser booleano', $e->getMessage() );
		}
	}
}
