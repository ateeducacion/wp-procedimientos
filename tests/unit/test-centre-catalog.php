<?php
/**
 * Tests for CentreCatalog: the school catalogue answered by a filter.
 *
 * @package Prc
 */

use Prc\Domain\CentreCatalog;

/**
 * El catálogo de centros, que no se versiona y lo contesta un filtro.
 *
 * El mu-plugin de desarrollo contesta `prc_centres` con una docena
 * inventada; aquí se quita para que cada test diga qué catálogo hay.
 */
class Test_Centre_Catalog extends WP_UnitTestCase {

	/**
	 * Path of the fallback JSON file under uploads.
	 *
	 * @var string
	 */
	private $fichero = '';

	/**
	 * Sin nadie que conteste el filtro, y sin fichero.
	 */
	public function set_up() {
		parent::set_up();
		remove_all_filters( 'prc_centres' );
		$this->fichero = (string) wp_upload_dir()['basedir'] . '/prc/centres.json';
		$this->borrar_fichero();
	}

	/**
	 * Y sin dejar el fichero de prueba atrás.
	 */
	public function tear_down() {
		$this->borrar_fichero();
		parent::tear_down();
	}

	/**
	 * Remove the fallback file, if the test wrote one.
	 *
	 * @return void
	 */
	private function borrar_fichero(): void {
		if ( file_exists( $this->fichero ) ) {
			wp_delete_file( $this->fichero );
		}
	}

	/**
	 * Answer the filter with a small invented catalogue.
	 *
	 * @return void
	 */
	private function catalogo(): void {
		add_filter(
			'prc_centres',
			static function () {
				return array(
					array(
						'code'      => 'C0001',
						'name'      => 'Centro de Educación Norte',
						'ownership' => 'public',
					),
					array(
						'code'      => 'C0002',
						'name'      => 'Colegio Sur',
						'ownership' => 'private',
					),
					array(
						'code' => 'C0003',
						'name' => 'Instituto Este',
					),
				);
			}
		);
	}

	/**
	 * Sin catálogo, lista vacía: se puede solicitar igual.
	 */
	public function test_without_a_catalogue_there_is_nothing() {
		$this->assertSame( array(), CentreCatalog::all() );
		$this->assertSame( array(), CentreCatalog::find( 'C0001' ) );
		$this->assertSame( array(), CentreCatalog::search( 'norte' ) );
	}

	/**
	 * El filtro contesta, y lo que contesta se normaliza.
	 */
	public function test_the_filter_answers_and_is_normalised() {
		$this->catalogo();

		$todos = CentreCatalog::all();
		$this->assertCount( 3, $todos );
		$this->assertSame( array( 'code', 'name', 'ownership', 'kind', 'island', 'municipality', 'type', 'zone', 'zone_name', 'active' ), array_keys( $todos[0] ) );
		$this->assertSame( 'public', $todos[2]['ownership'], 'sin titularidad, público' );
		$this->assertSame( 'school', $todos[2]['kind'], 'sin clase, un centro' );
	}

	/**
	 * Lo que no es un centro se cae: sin código, repetido, titularidad inventada.
	 */
	public function test_what_is_not_a_school_is_dropped() {
		add_filter(
			'prc_centres',
			static function () {
				return array(
					array( 'name' => 'Sin código' ),
					'no es un centro',
					array(
						'code'      => ' C0001 ',
						'name'      => ' Uno ',
						'ownership' => 'inventada',
					),
					array(
						'code' => 'C0001',
						'name' => 'Repetido',
					),
				);
			}
		);

		$this->assertSame(
			array(
				array(
					'code'         => 'C0001',
					'name'         => 'Uno',
					'ownership'    => 'public',
					'kind'         => 'school',
					'island'       => '',
					'municipality' => '',
					'type'         => '',
					'zone'         => '',
					'zone_name'    => '',
					'active'       => true,
				),
			),
			CentreCatalog::all()
		);

		add_filter( 'prc_centres', '__return_false', 20 );
		$this->assertSame( array(), CentreCatalog::all(), 'un filtro que no contesta una lista es una lista vacía' );
	}

	/**
	 * Buscar por código exacto.
	 */
	public function test_find_by_exact_code() {
		$this->catalogo();

		$this->assertSame( 'Colegio Sur', CentreCatalog::find( ' C0002 ' )['name'] );
		$this->assertSame( array(), CentreCatalog::find( 'C000' ) );
		$this->assertSame( array(), CentreCatalog::find( '' ) );
	}

	/**
	 * Buscar por principio de código o por trozo de nombre, sin acentos ni mayúsculas.
	 */
	public function test_search_by_code_prefix_or_name() {
		$this->catalogo();

		$this->assertSame( array( 'C0001', 'C0002', 'C0003' ), wp_list_pluck( CentreCatalog::search( 'c00' ), 'code' ) );
		$this->assertSame( array( 'C0001' ), wp_list_pluck( CentreCatalog::search( 'EDUCACION' ), 'code' ) );
		$this->assertSame( array( 'C0001' ), wp_list_pluck( CentreCatalog::search( 'educación norte' ), 'code' ) );
		$this->assertSame( array(), CentreCatalog::search( '0001' ), 'el código se busca por el principio' );
		$this->assertSame( array(), CentreCatalog::search( '  ' ) );
	}

	/**
	 * Y la búsqueda tiene tope.
	 */
	public function test_search_is_capped() {
		add_filter(
			'prc_centres',
			static function () {
				$centros = array();
				for ( $i = 1; $i <= 30; $i++ ) {
					$centros[] = array(
						'code' => 'C' . $i,
						'name' => 'Centro ' . $i,
					);
				}
				return $centros;
			}
		);

		$this->assertCount( CentreCatalog::SEARCH_LIMIT, CentreCatalog::search( 'centro' ) );
	}

	/**
	 * Sin filtro, se lee `uploads/prc/centres.json` si existe.
	 */
	public function test_the_default_is_a_json_file_under_uploads() {
		wp_mkdir_p( dirname( $this->fichero ) );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- el test escribe el fichero que el aplicativo lee.
		file_put_contents(
			$this->fichero,
			(string) wp_json_encode(
				array(
					array(
						'code'      => 'F0001',
						'name'      => 'Del fichero',
						'ownership' => 'private',
					),
				)
			)
		);

		$this->assertSame( 'Del fichero', CentreCatalog::find( 'F0001' )['name'] );
		$this->assertSame( 'private', CentreCatalog::find( 'F0001' )['ownership'] );

		// Y quien conteste el filtro recibe ese fichero como punto de partida.
		add_filter(
			'prc_centres',
			static function ( $centros ) {
				$centros[] = array(
					'code' => 'F0002',
					'name' => 'Añadido',
				);
				return $centros;
			}
		);
		$this->assertSame( array( 'F0001', 'F0002' ), wp_list_pluck( CentreCatalog::all(), 'code' ) );
	}

	/**
	 * La zona se resuelve en el propio catálogo, en memoria; los valores que
	 * ofrece el taller son los que hay, sin repetir y en orden.
	 */
	public function test_zone_names_and_values_come_from_the_catalogue() {
		add_filter(
			'prc_centres',
			static function () {
				return array(
					array(
						'code'   => 'Z0001',
						'name'   => 'Zona Inventada Uno',
						'type'   => 'ZONA',
						'island' => 'Isla Uno',
					),
					array(
						'code'   => 'C0001',
						'name'   => 'Escuela Uno',
						'type'   => 'ceip',
						'island' => 'Isla Dos',
						'zone'   => 'Z0001',
					),
					array(
						'code'   => 'C0002',
						'name'   => 'Escuela Dos',
						'type'   => 'CEIP',
						'island' => 'Isla Uno',
						'zone'   => 'Z9999',
					),
				);
			}
		);

		$this->assertSame( 'Zona Inventada Uno', CentreCatalog::find( 'C0001' )['zone_name'] );
		$this->assertSame( '', CentreCatalog::find( 'C0002' )['zone_name'], 'una zona que no está en el catálogo no tiene nombre' );
		$this->assertSame( array( 'ceip', 'ZONA' ), CentreCatalog::values( 'type' ), 'sin repetir en otra caja' );
		$this->assertSame( array( 'Isla Dos', 'Isla Uno' ), CentreCatalog::values( 'island' ) );
		$this->assertSame( array( 'Z0001', 'C0001', 'C0002' ), array_keys( CentreCatalog::index() ) );
	}
}
