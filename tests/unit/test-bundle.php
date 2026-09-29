<?php
/**
 * Tests for the generated Code Snippets bundle.
 *
 * @package Prc
 */

/**
 * El bundle es un artefacto, y por eso mismo nadie lo mira.
 *
 * `tests/bootstrap.php` carga los módulos de `src/Prc/` y **se salta** el
 * bundle, así que ni un solo test del resto de la suite lo toca. Lo único que
 * lo vigilaba era el `php -l` de `make bundle`, y eso solo dice que es PHP
 * válido: un empaquetador que se coma media línea por el medio produce PHP
 * perfectamente válido y un aplicativo roto.
 *
 * Aquí el bundle se lee **como texto** y no se incluye: sus clases son las
 * mismas que `src/Prc/` ya cargó, y requerirlo las redeclararía.
 *
 * Estos tests son el contrato del artefacto: que la cabecera que lee Code
 * Snippets siga entera y con la versión del CHANGELOG, que lleve dentro todo lo
 * que dice `load-order.php` —y esté al día—, que el CSS y el JavaScript
 * inlineados lleguen **byte a byte**, y que la guarda del doble `eval()` siga
 * en su sitio.
 */
class Test_Bundle extends WP_UnitTestCase {

	/**
	 * Repository root.
	 *
	 * @return string
	 */
	private function raiz(): string {
		return dirname( __DIR__, 2 ) . '/';
	}

	/**
	 * El bundle generado, como texto.
	 *
	 * @return string
	 */
	private function bundle(): string {
		$ruta = $this->raiz() . 'snippets/prc-procedimientos-app.bundle.php';
		$this->assertFileIsReadable( $ruta, 'no está el bundle: ejecute `make bundle`' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- fichero del repositorio.
		return (string) file_get_contents( $ruta );
	}

	/**
	 * La cabecera sobrevive al empaquetado, con las cuatro claves que se leen.
	 *
	 * No es decoración: de ahí saca Code Snippets el nombre, el ámbito y la
	 * prioridad con los que el snippet se crea, y `make release` busca el
	 * `@version` para negarse a publicar un bundle que no sea el de la versión
	 * del CHANGELOG.
	 */
	public function test_the_snippet_header_survives() {
		$bundle = $this->bundle();

		foreach ( array( 'Snippet Name:', 'Description:', 'Scope:', 'Priority:', '@version' ) as $clave ) {
			$this->assertStringContainsString( $clave, $bundle, 'falta ' . $clave . ' en la cabecera' );
		}

		// Y va en las primeras líneas, donde el analizador de cabeceras mira.
		$this->assertStringContainsString( 'Snippet Name:', substr( $bundle, 0, 600 ) );
	}

	/**
	 * El `@version` es el de la versión de arriba del CHANGELOG.
	 *
	 * El CHANGELOG es la única fuente de verdad de la versión: `make bundle` la
	 * copia al `@version`. Un bundle con otra versión es un bundle viejo, y
	 * desplegado no se distingue del bueno más que por este número.
	 */
	public function test_the_version_is_the_one_in_the_changelog() {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- fichero del repositorio.
		$changelog = (string) file_get_contents( $this->raiz() . 'CHANGELOG.md' );

		$this->assertSame( 1, preg_match( '/^## \[(\d+\.\d+\.\d+)\]/m', $changelog, $suya ), 'el CHANGELOG no abre con una versión X.Y.Z' );
		$this->assertSame( 1, preg_match( '/@version (\d+\.\d+\.\d+)/', $this->bundle(), $mia ), 'el bundle no declara @version' );
		$this->assertSame( $suya[1], $mia[1], 'el bundle no es el de la versión del CHANGELOG: ejecute `make bundle`' );
	}

	/**
	 * El bundle no declara tipos estrictos.
	 *
	 * Code Snippets **evalúa** el snippet, y `declare(strict_types=1)` solo vale
	 * como primera sentencia de un fichero: dentro de un `eval()` es un error
	 * fatal. Por eso `src/Prc/` no lo usa; esto vigila que no se cuele por el
	 * empaquetado.
	 */
	public function test_it_declares_no_strict_types() {
		$this->assertStringNotContainsString( 'strict_types', $this->bundle(), 'el bundle trae declare(strict_types=1) y Code Snippets no puede evaluarlo' );
	}

	/**
	 * La guarda del doble `eval()` sigue detrás del primer `namespace`.
	 */
	public function test_the_double_eval_guard_is_in_place() {
		$bundle = $this->bundle();

		$this->assertSame(
			1,
			preg_match( '/namespace [^;]+;(.*?)PRC_BUNDLE_LOADED/s', $bundle, $entre ),
			'la guarda PRC_BUNDLE_LOADED no está detrás del primer namespace'
		);
		// Entre el namespace y la guarda solo puede haber la comprobación de
		// acceso directo: cualquier otra cosa sería código que corre dos veces.
		$this->assertStringContainsString( "defined( 'ABSPATH' )", $entre[1] );
		$this->assertStringContainsString( "\\define( 'PRC_BUNDLE_LOADED', true );", $bundle, 'la guarda se comprueba pero no se pone' );
	}

	/**
	 * Todo lo que declara `src/Prc/` está dentro del bundle, y al día.
	 *
	 * La lista de carga es la fuente de verdad del empaquetado, así que se
	 * recorre entera y se busca en el bundle cada clase, cada función y cada
	 * constante que declara. No es una comprobación de estilo: un bundle
	 * generado antes del último cambio se ve exactamente igual que el bueno, y
	 * lo que le falta es justo esto —el método nuevo, la clase nueva, la
	 * constante nueva— que en producción sale por un fatal.
	 */
	public function test_everything_in_the_load_order_is_inside() {
		$src    = $this->raiz() . 'src/Prc/';
		$orden  = (array) require $src . 'load-order.php';
		$bundle = $this->bundle();

		$this->assertNotEmpty( $orden, 'load-order.php está vacío' );
		foreach ( $orden as $rel ) {
			$ruta = $src . $rel;
			$this->assertFileIsReadable( $ruta, $rel . ' está en la lista de carga pero no en el disco' );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- fichero del repositorio.
			$codigo = (string) file_get_contents( $ruta );

			preg_match_all( '/^(?:final |abstract )?(?:class|interface|trait) (\w+)/m', $codigo, $clases );
			preg_match_all( '/function (\w+)\s*\(/', $codigo, $funciones );
			preg_match_all( '/^\s*(?:public |private |protected )?const (\w+)/m', $codigo, $constantes );

			$declarado = array_merge( $clases[1], $funciones[1], $constantes[1] );
			$this->assertNotEmpty( $declarado, $rel . ' no declara nada: ¿qué hace en la lista de carga?' );
			foreach ( $declarado as $nombre ) {
				// `assertNotFalse` y no `assertStringContainsString`: el bundle
				// son 270 kB, y el fallo del segundo los vuelca enteros.
				$this->assertNotFalse(
					strpos( $bundle, $nombre ),
					$rel . ' declara ' . $nombre . ' y el bundle no lo trae: está viejo, ejecute `make bundle`'
				);
			}
		}
	}

	/**
	 * El CSS y el JavaScript llegan **byte a byte**.
	 *
	 * Es la comprobación que importa de verdad, porque es la que `php -l` no
	 * puede hacer: los assets viajan **dentro de cadenas PHP**, así que
	 * cualquier cosa que el empaquetador les haga por el medio —recortar
	 * espacios, colapsar líneas, normalizar saltos— produce PHP perfectamente
	 * válido y un aplicativo que se ve mal.
	 */
	public function test_the_inlined_assets_are_byte_identical() {
		$bundle = $this->bundle();

		$this->assertSame(
			1,
			preg_match( '/set_inline\( (array \(.*?\n\) )\);/s', $bundle, $coincidencia ),
			'no se encontró el mapa de assets inlineados'
		);

		// phpcs:ignore Squiz.PHP.Eval.Discouraged -- es el propio artefacto del repositorio, y solo se evalúa un literal de array.
		$inlineados = eval( 'return ' . $coincidencia[1] . ';' );
		$this->assertIsArray( $inlineados );
		$this->assertNotEmpty( $inlineados, 'el bundle no lleva ningún asset dentro' );

		$assets = $this->raiz() . 'assets/';
		foreach ( $inlineados as $rel => $contenido ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- fichero del repositorio.
			$real = (string) file_get_contents( $assets . $rel );
			$this->assertSame( $real, $contenido, $rel . ' no llega igual al bundle' );
		}

		// Y ninguno se queda fuera: la lista sale del directorio, no de la mano.
		foreach ( (array) glob( $assets . '*/*' ) as $ruta ) {
			$rel = substr( (string) $ruta, strlen( $assets ) );
			$this->assertArrayHasKey( $rel, $inlineados, $rel . ' no viaja dentro del bundle' );
		}
	}

	/**
	 * El cuerpo va sin comentarios: es lo que hace el snippet manejable.
	 *
	 * Se cuenta sobre el código, no sobre el fichero entero: la cabecera es un
	 * docblock a propósito, y dentro del CSS y el JavaScript inlineados hay
	 * comentarios que son **contenido de esos ficheros** y siguen ahí.
	 */
	public function test_the_body_carries_no_php_comments() {
		$bundle = $this->bundle();

		$docblocks = preg_match_all( '#^\s*/\*\*#m', $bundle );
		$this->assertLessThanOrEqual(
			1,
			$docblocks,
			'el cuerpo del bundle trae docblocks: solo tenía que quedar el de la cabecera'
		);

		$this->assertStringNotContainsString(
			'@package Prc',
			substr( $bundle, 600 ),
			'los docblocks de los ficheros de src/Prc no se han quitado'
		);
	}
}
