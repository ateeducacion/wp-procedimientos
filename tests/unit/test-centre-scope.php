<?php
/**
 * Tests for CentreScope: the school of a person, read from a configurable meta.
 *
 * @package Prc
 */

use Prc\Access\CentreScope;

/**
 * El código de centro de la persona, y que sin él no hay centro.
 */
class Test_Centre_Scope extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Por defecto se lee `prc_centre_code`.
	 */
	public function test_the_default_meta_key_is_ours() {
		$this->assertSame( 'prc_centre_code', CentreScope::META_KEY );
		$this->assertSame( 'prc_centre_code', CentreScope::meta_key() );

		$uid = $this->school_head( 'C0001' );
		$this->assertSame( 'C0001', CentreScope::code_for( $uid ) );
		$this->assertSame( 'C0001', get_user_meta( $uid, 'prc_centre_code', true ) );
	}

	/**
	 * Quien despliega dice en qué meta está el código, sin tocar el aplicativo.
	 */
	public function test_the_meta_key_is_filterable() {
		$uid = $this->school_head( 'C0001' );
		update_user_meta( $uid, 'codigo_de_la_casa', ' C0002 ' );

		add_filter(
			'prc_centre_code_meta_key',
			static function () {
				return 'codigo_de_la_casa';
			}
		);

		$this->assertSame( 'codigo_de_la_casa', CentreScope::meta_key() );
		$this->assertSame( 'C0002', CentreScope::code_for( $uid ), 'recortado, y de la meta que dice el filtro' );
	}

	/**
	 * Falla en cerrado: sin meta, con basura, o con un filtro que no contesta
	 * una clave, no hay código.
	 */
	public function test_it_fails_closed() {
		$this->assertSame( '', CentreScope::code_for( $this->school_head() ), 'sin código' );
		$this->assertSame( '', CentreScope::code_for( 0 ), 'sin persona' );

		foreach ( array( 'C 0001', 'C-0001', 'ab', str_repeat( '1', 21 ), array( 'C0001' ) ) as $basura ) {
			$uid = $this->school_head();
			update_user_meta( $uid, CentreScope::META_KEY, $basura );
			$this->assertSame( '', CentreScope::code_for( $uid ), is_array( $basura ) ? 'array' : $basura );
		}

		$uid = $this->school_head( 'C0001' );
		add_filter( 'prc_centre_code_meta_key', '__return_false' );
		$this->assertSame( '', CentreScope::meta_key() );
		$this->assertSame( '', CentreScope::code_for( $uid ), 'un filtro que no contesta una clave cierra la puerta' );
	}

	/**
	 * Sin persona dicha, la persona es quien ha entrado.
	 */
	public function test_the_current_user_is_the_default() {
		$this->assertSame( '', CentreScope::code_for() );

		$this->acting_as( $this->school_head( 'C0003' ) );
		$this->assertSame( 'C0003', CentreScope::code_for() );
	}

	/**
	 * La forma de un código: letras y cifras, sin espacios.
	 */
	public function test_the_shape_of_a_code() {
		foreach ( array( 'C0001', '90000103', 'abc', str_repeat( 'x', 20 ) ) as $bueno ) {
			$this->assertTrue( CentreScope::is_code( $bueno ), $bueno );
		}
		foreach ( array( '', 'ab', 'C 0001', 'C_0001', 'código', str_repeat( 'x', 21 ) ) as $malo ) {
			$this->assertFalse( CentreScope::is_code( $malo ), $malo );
		}
	}
}
