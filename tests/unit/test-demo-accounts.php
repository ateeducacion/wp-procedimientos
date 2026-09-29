<?php
/**
 * Tests that the demo accounts the README promises are the ones provisioning creates.
 *
 * @package Prc
 */

/**
 * Las cuentas de prueba: lo que dice el README, lo que siembra el guion y lo
 * que ofrece el «Cambiar a…» del mu-plugin de desarrollo.
 *
 * El README lo dice con todas las letras —«scripts/seed-demo.php es la fuente
 * de verdad de esta tabla»— y aun así las tres listas se separan solas: se
 * quita una cuenta del guion y la tabla se queda prometiéndola, o el
 * conmutador ofrece una que no existe. Quien sigue la documentación entra con
 * un usuario que no existe y no sabe si ha roto algo. Eso se ve aquí y no en
 * la revisión.
 */
class Test_Demo_Accounts extends WP_UnitTestCase {

	/**
	 * One file of this repository, as text.
	 *
	 * @param string $rel Path relative to the repository root.
	 * @return string
	 */
	private function source( string $rel ): string {
		// phpcs:ignore WordPress.WP.AlternativeFunctions -- se lee el propio repositorio, no el sistema de ficheros de un sitio.
		return (string) file_get_contents( dirname( __DIR__, 2 ) . $rel );
	}

	/**
	 * Logins listed in the README table of test users.
	 *
	 * @return string[]
	 */
	private function readme_logins(): array {
		$trozo = strstr( $this->source( '/README.md' ), '### Usuarios de prueba' );
		$trozo = is_string( $trozo ) ? substr( $trozo, 0, 1200 ) : '';

		preg_match_all( '/^\|\s*`([a-z0-9]+)`\s*\|/m', $trozo, $filas );
		$logins = array_values( array_diff( $filas[1], array( 'admin' ) ) );
		sort( $logins );
		return $logins;
	}

	/**
	 * The accounts block of the seeding script, as text.
	 *
	 * `seed-demo.php` no se puede incluir: al final se ejecuta y crearía los
	 * procedimientos de demostración dentro del test. Se lee su fuente, que es
	 * donde está el dato.
	 *
	 * @return string
	 */
	private function accounts_block(): string {
		$trozo = strstr( $this->source( '/scripts/seed-demo.php' ), 'function prc_demo_accounts' );
		return is_string( $trozo ) ? substr( $trozo, 0, 1600 ) : '';
	}

	/**
	 * Logins the seeding script defines.
	 *
	 * @return string[]
	 */
	private function seeded_logins(): array {
		preg_match_all( "/^\t\t\t'([a-z0-9]+)'\s*=>\s*array\(/m", $this->accounts_block(), $filas );
		$logins = $filas[1];
		sort( $logins );
		return $logins;
	}

	/**
	 * Logins the admin-bar switcher of the dev tools offers.
	 *
	 * @return string[]
	 */
	private function switcher_logins(): array {
		$trozo = strstr( $this->source( '/scripts/mu-plugins/prc-dev-tools.php' ), 'function prc_dev_demo_accounts' );
		$trozo = is_string( $trozo ) ? substr( $trozo, 0, 1200 ) : '';

		preg_match_all( "/'login'\s*=>\s*'([a-z0-9]+)'/", $trozo, $filas );
		$logins = array_values( array_diff( $filas[1], array( 'admin' ) ) );
		sort( $logins );
		return $logins;
	}

	/**
	 * La tabla del README, el guion y el conmutador nombran exactamente las
	 * mismas cuentas.
	 */
	public function test_the_readme_the_seeding_script_and_the_switcher_agree() {
		$readme = $this->readme_logins();

		$this->assertNotEmpty( $readme, 'la tabla de usuarios de prueba del README se lee' );
		$this->assertSame(
			$readme,
			$this->seeded_logins(),
			'la tabla del README promete cuentas que scripts/seed-demo.php no crea, o al revés'
		);
		$this->assertSame(
			$readme,
			$this->switcher_logins(),
			'el «Cambiar a…» del mu-plugin ofrece cuentas que scripts/seed-demo.php no crea, o al revés'
		);
	}

	/**
	 * Gestiones y editores en ambas ramas, y dos direcciones en centros
	 * distintos: es lo que enseña los dos acotados, y lo que más fácil se
	 * pierde al tocar el guion.
	 */
	public function test_two_areas_and_two_centres_are_seeded() {
		$bloque = $this->accounts_block();

		preg_match_all( "/'areas'\s*=>\s*array\(([^)]*)\)/", $bloque, $areas );
		$ambitos = array_values( array_filter( array_map( 'trim', $areas[1] ) ) );
		$this->assertCount( 3, array_unique( $ambitos ), 'editores de ambas ramas y gestión compatible en un subámbito' );

		preg_match_all( "/'centre'\s*=>\s*'(\d+)'/", $bloque, $centres );
		$this->assertCount( 2, array_unique( $centres[1] ), 'dos cuentas de dirección con centros distintos' );

		$catalogo = $this->source( '/scripts/mu-plugins/prc-dev-tools.php' );
		foreach ( $centres[1] as $code ) {
			$this->assertStringContainsString( "'{$code}'", $catalogo, "el centro {$code} está en el catálogo inventado del mu-plugin" );
		}
	}
}
