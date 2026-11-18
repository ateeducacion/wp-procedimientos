<?php
/**
 * Tests for CentreEligibility: which schools a procedure takes.
 *
 * @package Prc
 */

use Prc\Domain\CentreEligibility;
use Prc\Domain\ProcedureInput;

/**
 * Los acotados de un procedimiento sobre los centros (ADR-0042), sin WordPress.
 */
class Test_Centre_Eligibility extends WP_UnitTestCase {

	/**
	 * Un centro del catálogo, inventado.
	 *
	 * @param array<string, mixed> $over Fields to change.
	 * @return array<string, mixed>
	 */
	private function centro( array $over = array() ): array {
		return array_merge(
			array(
				'code'      => 'C0001',
				'name'      => 'Escuela Inventada',
				'ownership' => 'public',
				'type'      => 'CEIP',
				'island'    => 'Isla Uno',
				'zone'      => 'Z0001',
			),
			$over
		);
	}

	/**
	 * La lista cerrada se lee con espacios, comas, líneas y repetidos, y lo mal formado se dice.
	 */
	public function test_the_closed_list_is_read_as_typed() {
		$leida = CentreEligibility::parse_codes( "  C0001 , C0002\nC0001\r\n\n C0003;C-04 ,  ab, x\t90000001  " );

		$this->assertSame( array( 'C0001', 'C0002', 'C0003', '90000001' ), $leida['codes'] );
		$this->assertSame( array( 'C-04', 'ab', 'x' ), $leida['invalid'] );
		$this->assertSame( array( 'C0001', 'C0002' ), CentreEligibility::codes( array( ' C0001 ', 'C0002', 'C0001', 'mal formado', 42 ) ) );
		$this->assertSame( array(), CentreEligibility::codes( null ) );
	}

	/**
	 * El formulario rechaza la lista con un código mal escrito o con más de 2000.
	 */
	public function test_the_form_refuses_a_bad_list() {
		$bien = CentreEligibility::input( array( 'allowed_centres' => "C0001\nC0002" ), true );
		$this->assertSame( array(), $bien['errors'] );
		$this->assertSame( array( 'C0001', 'C0002' ), $bien['data']['allowed_centres'] );

		$mal = CentreEligibility::input( array( 'allowed_centres' => 'C0001, C-02' ), true );
		$this->assertSame( array( 'allowed_centres' ), $mal['errors'] );

		$codigos = array();
		for ( $i = 1; $i <= CentreEligibility::MAX_CODES + 1; $i++ ) {
			$codigos[] = sprintf( 'C%05d', $i );
		}
		$muchos = CentreEligibility::input( array( 'allowed_centres' => implode( "\n", $codigos ) ), true );
		$this->assertSame( array( 'allowed_centres' ), $muchos['errors'] );
		$this->assertCount( CentreEligibility::MAX_CODES, CentreEligibility::codes( $codigos ), 'la meta se queda con las 2000 primeras' );

		$docentes = CentreEligibility::input(
			array(
				'allowed_centres' => 'C-02',
				'centre_types'    => array( 'CEIP' ),
			),
			false
		);
		$this->assertSame( array(), $docentes['errors'], 'un procedimiento de profesorado no acota centros' );
		$this->assertSame( array(), $docentes['data']['centre_types'] );

		$this->assertSame( array( 'CEIP', 'IES' ), CentreEligibility::values( array( ' CEIP ', 'ceip', '', 'IES', array() ) ) );
		$this->assertSame( array( 'Isla Uno', 'Isla Dos' ), CentreEligibility::values( "Isla  Uno\nIsla Dos\n" ) );
	}

	/**
	 * El error de la lista se cuenta en castellano.
	 */
	public function test_the_list_error_reads_well() {
		$r = ProcedureInput::validate( array( 'allowed_centres' => 'C-01' ) );
		$this->assertContains( 'allowed_centres', $r['errors'] );
		$this->assertStringContainsString( 'lista cerrada de centros', ProcedureInput::why( array( 'allowed_centres' ) ) );
		$this->assertSame( array(), $r['data']['allowed_centres'] );
	}

	/**
	 * Sin acotados no se mira nada, ni siquiera el catálogo; todas las titularidades no acotan.
	 */
	public function test_without_rules_everybody_may() {
		$sin   = CentreEligibility::rules( array() );
		$todas = CentreEligibility::rules( array( 'ownership' => array( 'public', 'private' ) ) );

		$this->assertFalse( CentreEligibility::restricted( $sin ) );
		$this->assertFalse( CentreEligibility::restricted( $todas ) );
		$this->assertSame( '', CentreEligibility::why_not( $sin, 'X9999', array() ) );
		$this->assertSame( '', CentreEligibility::why_not( $todas, 'X9999', array() ) );
		$this->assertTrue( CentreEligibility::restricted( CentreEligibility::rules( array( 'ownership' => array( 'public' ) ) ) ) );
		$this->assertTrue( CentreEligibility::restricted( CentreEligibility::rules( array( 'codes' => array( 'C0001' ) ) ) ) );
	}

	/**
	 * Cada acotado dice por qué no.
	 */
	public function test_each_rule_says_why_not() {
		$publico = CentreEligibility::rules( array( 'ownership' => array( 'public' ) ) );
		$this->assertSame( '', CentreEligibility::why_not( $publico, 'C0001', $this->centro() ) );
		$this->assertSame( 'Este procedimiento es solo para centros públicos.', CentreEligibility::why_not( $publico, 'C0001', $this->centro( array( 'ownership' => 'private' ) ) ) );
		$this->assertStringContainsString( 'no está en el catálogo de centros', CentreEligibility::why_not( $publico, 'X9999', array() ) );

		$tipos = CentreEligibility::rules( array( 'types' => array( 'ceip', 'CEO' ) ) );
		$this->assertSame( '', CentreEligibility::why_not( $tipos, 'C0001', $this->centro() ), 'sin distinguir mayúsculas' );
		$this->assertSame( 'Este procedimiento es solo para centros de tipo ceip o CEO.', CentreEligibility::why_not( $tipos, 'C0001', $this->centro( array( 'type' => 'IES' ) ) ) );

		$islas = CentreEligibility::rules( array( 'islands' => array( 'Isla Dos', 'Isla Tres', 'Isla Cuatro' ) ) );
		$this->assertSame( 'Este procedimiento es solo para centros de Isla Dos, Isla Tres o Isla Cuatro.', CentreEligibility::why_not( $islas, 'C0001', $this->centro() ) );
		$this->assertSame( '', CentreEligibility::why_not( $islas, 'C0001', $this->centro( array( 'island' => ' isla  dos ' ) ) ) );

		$lista = CentreEligibility::rules( array( 'codes' => array( 'C0001', 'X9999' ) ) );
		$this->assertSame( '', CentreEligibility::why_not( $lista, 'C0001', $this->centro() ) );
		$this->assertSame( 'Este procedimiento es solo para una lista cerrada de centros, y el suyo no está en ella.', CentreEligibility::why_not( $lista, 'C0002', $this->centro( array( 'code' => 'C0002' ) ) ) );
		$this->assertSame( '', CentreEligibility::why_not( $lista, 'X9999', array() ), 'la lista sola: en ella basta' );
	}

	/**
	 * Con el catálogo entero vacío, la titularidad sola no cierra: no hay con
	 * qué comprobarla y se hace lo de antes. Tipos e islas sí cierran.
	 */
	public function test_an_empty_catalogue_does_not_close_on_ownership_alone() {
		$publico = CentreEligibility::rules( array( 'ownership' => array( 'public' ) ) );
		$this->assertSame( '', CentreEligibility::why_not( $publico, 'X9999', array(), false ), 'catálogo vacío: como antes' );
		$this->assertStringContainsString( 'no está en el catálogo', CentreEligibility::why_not( $publico, 'X9999', array(), true ), 'con catálogo, fuera de él no' );

		$tipos = CentreEligibility::rules(
			array(
				'ownership' => array( 'public' ),
				'types'     => array( 'CEIP' ),
			)
		);
		$this->assertStringContainsString( 'no está en el catálogo', CentreEligibility::why_not( $tipos, 'X9999', array(), false ) );
	}

	/**
	 * Un código de la lista cerrada solicita aunque no esté en el catálogo si
	 * la lista es el único acotado; con más acotados, hace falta el catálogo.
	 */
	public function test_a_listed_code_needs_no_catalogue_when_the_list_is_alone() {
		$lista = CentreEligibility::rules( array( 'codes' => array( 'X9999' ) ) );
		$this->assertSame( '', CentreEligibility::why_not( $lista, 'X9999', array() ) );
		$this->assertSame( '', CentreEligibility::why_not( $lista, 'X9999', array(), false ) );

		$con_mas = CentreEligibility::rules(
			array(
				'codes'   => array( 'X9999' ),
				'islands' => array( 'Isla Uno' ),
			)
		);
		$this->assertStringContainsString( 'no está en el catálogo', CentreEligibility::why_not( $con_mas, 'X9999', array() ) );

		// Titularidad «todas» más lista: la lista sola manda.
		$todas = CentreEligibility::rules(
			array(
				'codes'     => array( 'X9999' ),
				'ownership' => array( 'public', 'private' ),
			)
		);
		$this->assertSame( '', CentreEligibility::why_not( $todas, 'X9999', array() ) );
	}

	/**
	 * La lista cerrada no distingue mayúsculas: se guarda en mayúsculas y se compara igual.
	 */
	public function test_the_closed_list_ignores_case() {
		$this->assertSame( array( 'AB001', 'C0002' ), CentreEligibility::codes( array( 'ab001', 'AB001', 'c0002' ) ) );
		$lista = CentreEligibility::rules( array( 'codes' => array( 'ab001' ) ) );
		$this->assertSame( '', CentreEligibility::why_not( $lista, 'Ab001', array( 'code' => 'Ab001' ) ) );
		$this->assertSame( '', CentreEligibility::why_not( $lista, 'aB001', array() ) );
	}

	/**
	 * Los avisos del taller dicen la consecuencia real.
	 */
	public function test_the_workshop_warnings() {
		$publico = CentreEligibility::rules( array( 'ownership' => array( 'public' ) ) );
		$privado = CentreEligibility::rules( array( 'ownership' => array( 'private' ) ) );
		$tipos   = CentreEligibility::rules( array( 'types' => array( 'CEIP' ) ) );

		$this->assertSame( array(), CentreEligibility::warnings( CentreEligibility::rules( array() ), true, true ) );
		$this->assertSame( array(), CentreEligibility::warnings( $publico, false, false ) );

		$aviso = CentreEligibility::warnings( $publico, false, true );
		$this->assertStringContainsString( 'ningún tipo de centro marcado como privado', $aviso['ownership'] );
		$this->assertStringContainsString( 'todos los centros cuentan como públicos', $aviso['ownership'] );
		$this->assertStringContainsString( 'nadie', CentreEligibility::warnings( $privado, false, true )['ownership'] );

		$this->assertStringContainsString( 'puede solicitar cualquier centro', CentreEligibility::warnings( $publico, true, false )['catalogue'] );
		$this->assertStringContainsString( 'ningún centro puede solicitar', CentreEligibility::warnings( $tipos, true, false )['catalogue'] );
	}
}
