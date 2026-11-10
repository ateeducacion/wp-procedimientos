<?php
/**
 * Tests for the pure filter of the applications table.
 *
 * @package Prc
 */

use Prc\Domain\ApplicationFilter;
use Prc\Meta\ApplicationMetaKeys;

/**
 * El filtro de la tabla de solicitudes, sin WordPress.
 *
 * Todo viaja por GET, así que lo que llega puede ser cualquier cosa: lo que
 * no es un valor del filtro se ignora, nunca se rechaza la pantalla.
 */
class Test_Application_Filter extends WP_UnitTestCase {

	/**
	 * Sin nada en la URL, nada filtrado y la primera página.
	 */
	public function test_an_empty_request_is_no_filter() {
		$f = ApplicationFilter::sanitize( array() );
		$this->assertSame(
			array(
				'review'     => '',
				'island'     => '',
				'zone'       => '',
				'incomplete' => false,
				'search'     => '',
				'page'       => 1,
			),
			$f
		);
		$this->assertFalse( ApplicationFilter::active( $f ) );
		$this->assertSame( array(), ApplicationFilter::query( $f ) );
	}

	/**
	 * Cada valor válido se queda; la página no es un filtro.
	 */
	public function test_valid_values_stay_and_the_page_is_not_a_filter() {
		$f = ApplicationFilter::sanitize(
			array(
				ApplicationFilter::ARG_REVIEW     => ApplicationMetaKeys::REVIEW_ADMITTED,
				ApplicationFilter::ARG_ISLAND     => '  Isla Uno ',
				ApplicationFilter::ARG_ZONE       => 'Z0001',
				ApplicationFilter::ARG_INCOMPLETE => '1',
				ApplicationFilter::ARG_SEARCH     => '  norte   sur ',
				ApplicationFilter::ARG_PAGE       => '3',
			)
		);
		$this->assertSame( ApplicationMetaKeys::REVIEW_ADMITTED, $f['review'] );
		$this->assertSame( 'Isla Uno', $f['island'] );
		$this->assertSame( 'Z0001', $f['zone'] );
		$this->assertTrue( $f['incomplete'] );
		$this->assertSame( 'norte sur', $f['search'], 'los espacios se recogen' );
		$this->assertSame( 3, $f['page'] );
		$this->assertTrue( ApplicationFilter::active( $f ) );
		$this->assertFalse( ApplicationFilter::active( ApplicationFilter::sanitize( array( ApplicationFilter::ARG_PAGE => '4' ) ) ) );
	}

	/**
	 * Lo que no es un valor del filtro se ignora, sin error.
	 */
	public function test_invalid_values_are_ignored() {
		$f = ApplicationFilter::sanitize(
			array(
				ApplicationFilter::ARG_REVIEW     => 'borrada',
				ApplicationFilter::ARG_ISLAND     => array( 'Isla Uno' ),
				ApplicationFilter::ARG_INCOMPLETE => 'si',
				ApplicationFilter::ARG_SEARCH     => array( 'x' ),
				ApplicationFilter::ARG_PAGE       => '-2',
			)
		);
		$this->assertSame( '', $f['review'] );
		$this->assertSame( '', $f['island'] );
		$this->assertFalse( $f['incomplete'] );
		$this->assertSame( '', $f['search'] );
		$this->assertSame( 1, $f['page'] );
		$this->assertSame( 1, ApplicationFilter::sanitize( array( ApplicationFilter::ARG_PAGE => 'abc' ) )['page'] );
		$this->assertSame( ApplicationFilter::MAX_SEARCH, mb_strlen( ApplicationFilter::sanitize( array( ApplicationFilter::ARG_SEARCH => str_repeat( 'á', 300 ) ) )['search'] ) );
	}

	/**
	 * Una isla o una zona que no está entre las solicitudes no filtra.
	 */
	public function test_island_and_zone_must_be_among_the_options() {
		$f = ApplicationFilter::sanitize(
			array(
				ApplicationFilter::ARG_ISLAND => 'Isla Tres',
				ApplicationFilter::ARG_ZONE   => 'Z0001',
			)
		);
		$f = ApplicationFilter::against(
			$f,
			array(
				'islands' => array( 'Isla Uno' => 'Isla Uno' ),
				'zones'   => array( 'Z0001' => 'Zona Inventada' ),
			)
		);
		$this->assertSame( '', $f['island'] );
		$this->assertSame( 'Z0001', $f['zone'] );
	}

	/**
	 * Los argumentos de la URL: solo lo que no es lo de por defecto, en orden.
	 */
	public function test_query_keeps_only_what_is_set() {
		$f = ApplicationFilter::sanitize(
			array(
				ApplicationFilter::ARG_SEARCH => 'a&b=c',
				ApplicationFilter::ARG_REVIEW => ApplicationMetaKeys::REVIEW_AMEND,
				ApplicationFilter::ARG_PAGE   => '2',
			)
		);
		$this->assertSame(
			array(
				ApplicationFilter::ARG_REVIEW => ApplicationMetaKeys::REVIEW_AMEND,
				ApplicationFilter::ARG_SEARCH => 'a&b=c',
				ApplicationFilter::ARG_PAGE   => '2',
			),
			ApplicationFilter::query( $f )
		);
		// Cambiar un filtro vuelve a la primera página; cambiar la página, no.
		$this->assertArrayNotHasKey( ApplicationFilter::ARG_PAGE, ApplicationFilter::query( $f, array( 'review' => '' ) ) );
		$this->assertSame( '5', ApplicationFilter::query( $f, array( 'page' => 5 ) )[ ApplicationFilter::ARG_PAGE ] );
		$this->assertSame( array( ApplicationFilter::ARG_INCOMPLETE => '1' ), ApplicationFilter::query( ApplicationFilter::sanitize( array() ), array( 'incomplete' => true ) ) );
	}

	/**
	 * La ventana de una página: de 50 en 50, y una página fuera de rango
	 * cae en la última.
	 */
	public function test_the_window_of_a_page() {
		$this->assertSame(
			array(
				'page'   => 1,
				'pages'  => 1,
				'offset' => 0,
				'from'   => 0,
				'to'     => 0,
			),
			ApplicationFilter::window( 0, 3 )
		);
		$this->assertSame(
			array(
				'page'   => 2,
				'pages'  => 3,
				'offset' => 50,
				'from'   => 51,
				'to'     => 100,
			),
			ApplicationFilter::window( 120, 2 )
		);
		$fuera = ApplicationFilter::window( 120, 9 );
		$this->assertSame( 3, $fuera['page'] );
		$this->assertSame( 101, $fuera['from'] );
		$this->assertSame( 120, $fuera['to'] );
		$this->assertSame( 1, ApplicationFilter::window( 50, 1 )['pages'] );
	}

	/**
	 * La búsqueda no distingue mayúsculas ni tildes, y los comodines de SQL
	 * son letras como las demás.
	 */
	public function test_matching_ignores_case_and_accents_and_takes_wildcards_literally() {
		$this->assertTrue( ApplicationFilter::matches( 'perez', array( 'CEIP Pérez Galdós' ) ) );
		$this->assertTrue( ApplicationFilter::matches( 'PÉREZ', array( 'ceip perez galdos' ) ) );
		$this->assertTrue( ApplicationFilter::matches( 'peña', array( 'IES La Pena' ) ) );
		$this->assertTrue( ApplicationFilter::matches( '0001', array( 'Otro', 'C0001' ) ) );
		$this->assertFalse( ApplicationFilter::matches( '%', array( 'CEIP Norte' ) ) );
		$this->assertFalse( ApplicationFilter::matches( 'C_001', array( 'C0001' ) ) );
		$this->assertTrue( ApplicationFilter::matches( '100%', array( 'Centro 100% rural' ) ) );
		$this->assertTrue( ApplicationFilter::matches( '', array( 'lo que sea' ) ), 'sin término, todo casa' );
	}
}
