<?php
/**
 * Tests for the pure completeness of an application.
 *
 * @package Prc
 */

use Prc\Domain\ApplicationCompleteness;
use Prc\Domain\ProcedureQuestions;
use Prc\Meta\ApplicationMetaKeys;

/**
 * Cuándo una solicitud está incompleta, y por qué, sin WordPress.
 *
 * Incompleta es una de dos: la lista de personas encendida y por debajo del
 * mínimo (ADR-0041), o una entrega obligatoria con plazo propio que ya venció
 * sin documento. Una excluida no cuenta: no hay nada que completar.
 */
class Test_Application_Completeness extends WP_UnitTestCase {

	/**
	 * Una lista de personas encendida con mínimo.
	 *
	 * @param int $min Minimum.
	 * @return array<string, mixed>
	 */
	private function spec( int $min ): array {
		return array(
			'enabled' => true,
			'roles'   => array(
				array(
					'key'   => 'r1',
					'label' => 'Docente',
				),
			),
			'min'     => $min,
		);
	}

	/**
	 * Una persona de la lista.
	 *
	 * @param string $id Row ID.
	 * @return array<string, string>
	 */
	private function persona( string $id ): array {
		return array(
			'id'      => $id,
			'name'    => 'Ana',
			'surname' => 'Inventada',
			'dni'     => '00000000T',
			'email'   => 'ana@example.org',
			'phone'   => '',
			'role'    => 'r1',
			'group'   => '',
		);
	}

	/**
	 * Una entrega con plazo propio.
	 *
	 * @param string $key      Question key.
	 * @param string $label    Label.
	 * @param string $closes   Closing day.
	 * @param bool   $required Whether it is required.
	 * @return array<string, mixed>
	 */
	private function entrega( string $key, string $label, string $closes, bool $required = true ): array {
		return array(
			'key'       => $key,
			'label'     => $label,
			'type'      => ProcedureQuestions::TYPE_FILE,
			'required'  => $required,
			'delivery'  => true,
			'opens_at'  => '2026-01-01',
			'closes_at' => $closes,
		);
	}

	/**
	 * Un documento guardado.
	 *
	 * @return array<string, string>
	 */
	private function documento(): array {
		return array(
			'id'     => str_repeat( 'a', 32 ),
			'stored' => 'x.bin',
			'name'   => 'memoria.pdf',
		);
	}

	/**
	 * Sin lista ni entregas, nada falta.
	 */
	public function test_nothing_asked_is_nothing_missing() {
		$this->assertSame( array(), ApplicationCompleteness::reasons( array(), array(), array(), array(), '2026-06-01', ApplicationMetaKeys::REVIEW_SUBMITTED ) );
		$this->assertFalse( ApplicationCompleteness::may_be_incomplete( array(), array(), '2026-06-01' ) );
	}

	/**
	 * Por debajo del mínimo faltan personas, y se dice cuántas.
	 */
	public function test_below_the_minimum_says_how_many_people_are_missing() {
		$hoy = '2026-06-01';
		$this->assertSame( array( 'Faltan 2 personas' ), ApplicationCompleteness::reasons( $this->spec( 3 ), array( $this->persona( 'aaaaaaaaaaa1' ) ), array(), array(), $hoy, ApplicationMetaKeys::REVIEW_SUBMITTED ) );
		$this->assertSame( array( 'Falta 1 persona' ), ApplicationCompleteness::reasons( $this->spec( 1 ), array(), array(), array(), $hoy, ApplicationMetaKeys::REVIEW_ADMITTED ) );
		$this->assertSame( array(), ApplicationCompleteness::reasons( $this->spec( 1 ), array( $this->persona( 'aaaaaaaaaaa1' ) ), array(), array(), $hoy, ApplicationMetaKeys::REVIEW_SUBMITTED ) );
		$this->assertSame( array(), ApplicationCompleteness::reasons( array( 'enabled' => false ) + $this->spec( 3 ), array(), array(), array(), $hoy, '' ), 'con la lista apagada no falta nadie' );
		$this->assertTrue( ApplicationCompleteness::may_be_incomplete( $this->spec( 1 ), array(), $hoy ) );
	}

	/**
	 * Una entrega obligatoria vencida sin documento falta; una abierta, una
	 * opcional o una con documento, no.
	 */
	public function test_an_overdue_required_delivery_without_document_is_missing() {
		$hoy       = '2026-06-01';
		$preguntas = array(
			$this->entrega( 'q1', 'Memoria final', '2026-05-31' ),
			$this->entrega( 'q2', 'Fotos', '2026-05-01', false ),
			$this->entrega( 'q3', 'Plan', '2026-06-01' ),
			array(
				'key'      => 'q4',
				'label'    => 'Proyecto',
				'type'     => ProcedureQuestions::TYPE_FILE,
				'required' => true,
			),
		);
		$this->assertSame( array( 'Falta: Memoria final' ), ApplicationCompleteness::reasons( array(), array(), $preguntas, array(), $hoy, ApplicationMetaKeys::REVIEW_SUBMITTED ) );
		$this->assertSame( array(), ApplicationCompleteness::reasons( array(), array(), $preguntas, array( 'q1' => $this->documento() ), $hoy, ApplicationMetaKeys::REVIEW_SUBMITTED ) );
		$this->assertSame( array( 'Falta: Memoria final' ), ApplicationCompleteness::reasons( array(), array(), $preguntas, array( 'q1' => array( 'name' => 'roto' ) ), $hoy, ApplicationMetaKeys::REVIEW_SUBMITTED ), 'un descriptor a medias no es un documento' );
		$this->assertSame( array( 'q1' ), array_column( ApplicationCompleteness::overdue( $preguntas, $hoy ), 'key' ) );
		$this->assertFalse( ApplicationCompleteness::may_be_incomplete( array(), $preguntas, '2026-05-30' ) );
	}

	/**
	 * Las dos cosas a la vez dan los dos motivos; excluida, ninguno.
	 */
	public function test_both_reasons_and_none_for_an_excluded_one() {
		$hoy       = '2026-06-01';
		$preguntas = array( $this->entrega( 'q1', 'Memoria final', '2026-05-31' ) );
		$this->assertSame(
			array( 'Faltan 2 personas', 'Falta: Memoria final' ),
			ApplicationCompleteness::reasons( $this->spec( 2 ), 'no es una lista', $preguntas, 'tampoco', $hoy, ApplicationMetaKeys::REVIEW_AMEND )
		);
		$this->assertSame( array(), ApplicationCompleteness::reasons( $this->spec( 2 ), array(), $preguntas, array(), $hoy, ApplicationMetaKeys::REVIEW_EXCLUDED ) );
	}
}
