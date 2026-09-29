<?php
/**
 * Tests for the plans, the design documents, the requirements and their indexes.
 *
 * @package Prc
 */

/**
 * Los planes, los SDD y los requisitos se indexan y sus enlaces llevan a algún sitio.
 *
 * Es el mismo trato que reciben las ADR en `test-adr-registry.php`, y por el
 * mismo motivo: un documento que no entra en su índice no lo encuentra nadie, y
 * uno que enlaza a un fichero que no existe manda a un 404 a quien lo lea dentro
 * de un año. Estos documentos enlazan sobre todo **fuera** de su directorio —a
 * `../adr/` y a `../sdd/`—, así que es justo donde más fácil es equivocarse.
 */
class Test_Plan_Registry extends WP_UnitTestCase {

	/**
	 * Estados que admiten las guías: cualquier otro es una errata.
	 *
	 * El de los requisitos lleva coletilla («Propuesta; el alcance de F1 es…»),
	 * así que se comprueba con qué empieza y no la línea entera.
	 *
	 * @var string[]
	 */
	private const ESTADOS = array( 'Propuesta', 'Aceptada', 'Rechazada', 'Sustituida' );

	/**
	 * Las tres series documentales: directorio => prefijo e índice.
	 *
	 * @return array<string, array{0:string, 1:string, 2:string}>
	 */
	private function series(): array {
		return array(
			'planes'     => array( 'docs/plan/', 'PLAN', 'README.md' ),
			'diseño'     => array( 'docs/sdd/', 'SDD', 'registro.md' ),
			'requisitos' => array( 'docs/requisitos/', 'REQ', 'README.md' ),
		);
	}

	/**
	 * Documents of one series, by ID.
	 *
	 * @param string $dir     Directory, relative to the repository root.
	 * @param string $prefijo Document prefix, e.g. `PLAN`.
	 * @return array<string, string> ID => file name.
	 */
	private function docs( string $dir, string $prefijo ): array {
		$out = array();
		foreach ( (array) glob( $this->raiz( $dir ) . $prefijo . '-*.md' ) as $ruta ) {
			$nombre = basename( (string) $ruta );
			$out[ substr( $nombre, 0, strlen( $prefijo ) + 5 ) ] = $nombre;
		}
		ksort( $out );
		return $out;
	}

	/**
	 * Absolute path of a directory of the repository.
	 *
	 * @param string $dir Directory, relative to the repository root.
	 * @return string
	 */
	private function raiz( string $dir ): string {
		return dirname( __DIR__, 2 ) . '/' . $dir;
	}

	/**
	 * Contents of a file of one series.
	 *
	 * @param string $dir    Directory, relative to the repository root.
	 * @param string $nombre File name.
	 * @return string
	 */
	private function texto( string $dir, string $nombre ): string {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- se lee la documentación del propio repositorio.
		return (string) file_get_contents( $this->raiz( $dir ) . $nombre );
	}

	/**
	 * Todo documento del directorio está en la tabla de su índice, con su enlace.
	 */
	public function test_every_document_is_in_its_index() {
		foreach ( $this->series() as $serie => $datos ) {
			list( $dir, $prefijo, $indice ) = $datos;

			$tabla = $this->texto( $dir, $indice );
			$docs  = $this->docs( $dir, $prefijo );

			$this->assertNotEmpty( $docs, 'no hay ningún documento en ' . $serie . ': algo va mal con la ruta' );
			foreach ( $docs as $id => $nombre ) {
				$this->assertStringContainsString( '(' . $nombre . ')', $tabla, $id . ' no está enlazado en ' . $indice );
				$this->assertStringContainsString( '| [' . $id . ']', $tabla, $id . ' no tiene fila en la tabla de ' . $indice );
			}
		}
	}

	/**
	 * Cada documento abre con su frontmatter completo, y el `id` es el del nombre.
	 */
	public function test_every_document_has_complete_frontmatter() {
		foreach ( $this->series() as $datos ) {
			list( $dir, $prefijo ) = $datos;

			foreach ( $this->docs( $dir, $prefijo ) as $id => $nombre ) {
				$texto = $this->texto( $dir, $nombre );
				$this->assertStringStartsWith( "---\n", $texto, $nombre . ' no abre con frontmatter' );

				foreach ( array( 'title:', 'status:', 'date:', 'supersedes:', 'superseded_by:', 'ai_assistance:' ) as $clave ) {
					$this->assertStringContainsString( "\n" . $clave, $texto, $nombre . ' no declara ' . $clave );
				}
				$this->assertStringContainsString( "\nid: " . $id . "\n", $texto, $nombre . ' declara un id que no es el de su nombre' );
				$this->assertMatchesRegularExpression( '/\ndate: \d{4}-\d{2}-\d{2}\n/', $texto, $nombre . ' no lleva fecha AAAA-MM-DD' );
				$this->assertMatchesRegularExpression( '/\n  tool: "[^"]+"\n/', $texto, $nombre . ' no declara la herramienta de IA' );
				$this->assertMatchesRegularExpression( '/\n  model: "[^"]+"\n/', $texto, $nombre . ' no declara el modelo de IA' );

				$this->assertSame( 1, preg_match( '/\nstatus: (\S+)/', $texto, $m ), $nombre . ' no declara status' );
				$this->assertContains(
					rtrim( $m[1], ';' ),
					self::ESTADOS,
					$nombre . ' declara un estado que no es de la lista de la guía'
				);
			}
		}
	}

	/**
	 * La numeración de cada serie empieza en 0001 y no tiene huecos ni repetidos.
	 */
	public function test_the_numbering_has_no_gaps_or_duplicates() {
		foreach ( $this->series() as $serie => $datos ) {
			list( $dir, $prefijo ) = $datos;

			$docs    = $this->docs( $dir, $prefijo );
			$numeros = array();
			foreach ( array_keys( $docs ) as $id ) {
				$numeros[] = (int) substr( (string) $id, strlen( $prefijo ) + 1 );
			}
			sort( $numeros );

			$this->assertNotEmpty( $numeros, 'no hay ningún documento en ' . $serie );
			$this->assertSame( 1, $numeros[0], 'la serie ' . $prefijo . ' no empieza en la 0001' );
			$this->assertCount(
				count( (array) glob( $this->raiz( $dir ) . $prefijo . '-*.md' ) ),
				$docs,
				'hay dos documentos ' . $prefijo . ' con el mismo identificador'
			);

			$faltan = array_diff( range( 1, (int) max( $numeros ) ), $numeros );
			$this->assertSame(
				array(),
				array_values( $faltan ),
				'faltan números en la serie ' . $prefijo . ': ' . implode( ', ', array_map( 'strval', $faltan ) )
			);
		}
	}

	/**
	 * Ningún enlace relativo de un documento —ni de su índice— apunta a un
	 * fichero que no está. Es lo que más se rompe: casi todos salen del
	 * directorio.
	 */
	public function test_no_document_links_to_a_missing_file() {
		foreach ( $this->series() as $datos ) {
			list( $dir, $prefijo, $indice ) = $datos;

			$ficheros   = $this->docs( $dir, $prefijo );
			$ficheros[] = $indice;

			foreach ( $ficheros as $nombre ) {
				preg_match_all( '/\]\(((?:\.\.\/)*[A-Za-z0-9._\/-]+\.md)(?:#[^)]*)?\)/', $this->texto( $dir, $nombre ), $m );
				foreach ( array_unique( $m[1] ) as $rel ) {
					$this->assertFileExists( $this->raiz( $dir ) . $rel, $nombre . ' enlaza a ' . $rel . ', que no existe' );
				}
			}
		}
	}
}
