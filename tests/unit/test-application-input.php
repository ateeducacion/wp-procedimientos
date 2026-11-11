<?php
/**
 * Tests for ApplicationInput: the fixed core of the application form.
 *
 * @package Prc
 */

use Prc\Domain\ApplicationInput;
use Prc\Domain\ProcedureQuestions;

/**
 * El núcleo fijo de la solicitud: cargo, compromisos, coordinación y respuestas.
 */
class Test_Application_Input extends WP_UnitTestCase {

	/**
	 * What a procedure asks for, by default everything.
	 *
	 * @param array<string, mixed> $overrides Overrides.
	 * @return array<string, mixed>
	 */
	private function procedimiento( array $overrides = array() ): array {
		return array_merge(
			array(
				'requires_coordinator' => true,
				'commitments'          => 'El centro se compromete a…',
				'questions'            => ProcedureQuestions::sanitize(
					array(
						array(
							'key'      => 'q1',
							'label'    => 'Modalidad',
							'type'     => 'single',
							'required' => true,
							'choices'  => array( 'A', 'B' ),
						),
						array(
							'key'   => 'q2',
							'label' => 'Observaciones',
							'type'  => 'text',
						),
					)
				),
			),
			$overrides
		);
	}

	/**
	 * Una solicitud completa se acepta y se recorta.
	 */
	public function test_a_complete_application_is_accepted_and_trimmed() {
		$r = ApplicationInput::validate(
			array(
				'position'            => 'head',
				'coordinator_name'    => '  Ana ',
				'coordinator_surname' => ' Pérez Díaz ',
				'coordinator_dni'     => ' 12345678z ',
				'coordinator_email'   => ' Ana@Example.org ',
				'accept'              => '1',
				'answers'             => array(
					'q1' => 'B',
					'q2' => ' nada ',
				),
			),
			$this->procedimiento()
		);

		$this->assertTrue( $r['ok'] );
		$this->assertSame( array(), $r['errors'] );
		$this->assertSame( 'head', $r['data']['position'] );
		$this->assertSame(
			array(
				array(
					'name'    => 'Ana',
					'surname' => 'Pérez Díaz',
					'dni'     => '12345678Z',
					'email'   => 'ana@example.org',
				),
			),
			$r['data']['coordinator']
		);
		$this->assertTrue( $r['data']['accept'] );
		$this->assertSame(
			array(
				'q1' => 'B',
				'q2' => 'nada',
			),
			$r['data']['answers']
		);
	}

	/**
	 * El cargo es de la lista cerrada y es obligatorio.
	 */
	public function test_the_position_is_a_closed_list() {
		$r = ApplicationInput::validate( array( 'position' => 'conserje' ), $this->procedimiento() );
		$this->assertContains( 'position', $r['errors'] );
		$this->assertSame( '', $r['data']['position'] );

		$r = ApplicationInput::validate( array(), $this->procedimiento() );
		$this->assertContains( 'position', $r['errors'] );
	}

	/**
	 * La persona coordinadora solo se pide si el procedimiento la pide.
	 */
	public function test_the_coordinator_is_asked_only_when_the_procedure_requires_one() {
		$r = ApplicationInput::validate(
			array(
				'position'          => 'secretary',
				'coordinator_email' => 'no es un correo',
			),
			$this->procedimiento()
		);
		$this->assertContains( 'coordinator_name', $r['errors'] );
		$this->assertContains( 'coordinator_surname', $r['errors'], 'los apellidos van aparte y se piden' );
		$this->assertContains( 'coordinator_email', $r['errors'] );

		$r = ApplicationInput::validate(
			array(
				'position'         => 'secretary',
				'coordinator_name' => 'Nadie',
				'accept'           => '1',
				'answers'          => array( 'q1' => 'A' ),
			),
			$this->procedimiento( array( 'requires_coordinator' => false ) )
		);
		$this->assertTrue( $r['ok'] );
		$this->assertSame(
			array(),
			$r['data']['coordinator'],
			'lo que no se pide no se guarda'
		);
	}

	/** The DNI/NIE must have the control letter that matches its number. */
	public function test_coordinator_dni_and_nie_control_letters_are_checked() {
		$this->assertTrue( ApplicationInput::is_dni( '12345678Z' ) );
		$this->assertTrue( ApplicationInput::is_dni( 'x0000000t' ) );
		$this->assertFalse( ApplicationInput::is_dni( '12345678A' ) );
		$this->assertFalse( ApplicationInput::is_dni( '1234567Z' ) );
	}

	/** A procedure can request no coordinators or several, and validates every requested row. */
	public function test_multiple_coordinators_are_validated_as_a_list() {
		$procedure = $this->procedimiento( array( 'coordinator_count' => 2 ) );
		$valid     = ApplicationInput::validate(
			array(
				'position'     => 'head',
				'coordinators' => array(
					array(
						'name'    => 'Ana',
						'surname' => 'Pérez',
						'dni'     => '12345678Z',
						'email'   => 'ANA@example.org',
					),
					array(
						'name'    => 'Luis',
						'surname' => 'Díaz',
						'dni'     => 'X0000000T',
						'email'   => 'luis@example.org',
					),
				),
				'accept'       => '1',
				'answers'      => array( 'q1' => 'A' ),
			),
			$procedure
		);
		$this->assertTrue( $valid['ok'], implode( ', ', $valid['errors'] ) );
		$this->assertSame( 'ana@example.org', $valid['data']['coordinator'][0]['email'] );
		$this->assertSame( '12345678Z', $valid['data']['coordinator'][0]['dni'] );
		$this->assertSame( 'Luis', $valid['data']['coordinator'][1]['name'] );
		$this->assertSame( 'Díaz', $valid['data']['coordinator'][1]['surname'] );

		$invalid = ApplicationInput::validate(
			array(
				'position'     => 'head',
				'coordinators' => array(
					array(
						'name'  => 'Ana',
						'dni'   => '12345678A',
						'email' => 'ana@example.org',
					),
				),
				'accept'       => '1',
				'answers'      => array( 'q1' => 'A' ),
			),
			$procedure
		);
		$this->assertContains( 'coordinator_name', $invalid['errors'], 'se exige la segunda persona' );
		$this->assertContains( 'coordinator_dni', $invalid['errors'], 'el DNI debe tener una letra válida' );
		$this->assertContains( 'coordinator_surname', $invalid['errors'], 'sin apellidos no vale' );
		$this->assertSame(
			array(),
			ApplicationInput::validate(
				array( 'position' => 'head' ),
				$this->procedimiento(
					array(
						'coordinator_count'    => 0,
						'requires_coordinator' => false,
						'commitments'          => '',
					)
				)
			)['data']['coordinator']
		);
	}

	/**
	 * La casilla de compromisos es obligatoria solo cuando hay compromisos.
	 */
	public function test_the_commitments_box_is_required_only_when_there_are_commitments() {
		$r = ApplicationInput::validate(
			array(
				'position' => 'head',
				'answers'  => array( 'q1' => 'A' ),
			),
			$this->procedimiento( array( 'requires_coordinator' => false ) )
		);
		$this->assertContains( 'accept', $r['errors'] );
		$this->assertFalse( $r['data']['accept'] );

		$r = ApplicationInput::validate(
			array(
				'position' => 'head',
				'answers'  => array( 'q1' => 'A' ),
			),
			$this->procedimiento(
				array(
					'requires_coordinator' => false,
					'commitments'          => '   ',
				)
			)
		);
		$this->assertTrue( $r['ok'] );
	}

	/**
	 * Las respuestas se validan con las preguntas, y sus errores llevan la clave.
	 */
	public function test_answers_are_validated_against_the_questions() {
		$r = ApplicationInput::validate(
			array(
				'position' => 'head',
				'accept'   => '1',
				'answers'  => array( 'q1' => 'C' ),
			),
			$this->procedimiento( array( 'requires_coordinator' => false ) )
		);

		$this->assertFalse( $r['ok'] );
		$this->assertSame( array( 'answer:q1' ), $r['errors'] );
		$this->assertSame( '', $r['data']['answers']['q1'] );
		$this->assertSame( '', $r['data']['answers']['q2'] );

		$r = ApplicationInput::validate(
			array(
				'position' => 'head',
				'accept'   => '1',
				'answers'  => 'no es una lista',
			),
			$this->procedimiento(
				array(
					'requires_coordinator' => false,
					'questions'            => array(),
				)
			)
		);
		$this->assertTrue( $r['ok'], 'sin preguntas no hay respuestas que validar' );
		$this->assertSame( array(), $r['data']['answers'] );
	}

	/**
	 * Y el motivo se cuenta en una frase, con el rótulo de la pregunta.
	 */
	public function test_why_names_the_fields_and_the_questions() {
		$preguntas = $this->procedimiento()['questions'];

		$this->assertSame( 'Revise el cargo.', ApplicationInput::why( array( 'position' ) ) );
		$this->assertSame(
			'Revise la aceptación de los compromisos y la respuesta a «Modalidad».',
			ApplicationInput::why( array( 'accept', 'answer:q1' ), $preguntas )
		);
		$this->assertSame(
			'Revise el nombre de cada persona coordinadora, el correo de cada persona coordinadora y la respuesta a «Modalidad».',
			ApplicationInput::why( array( 'coordinator_name', 'coordinator_email', 'answer:q1', 'answer:q1' ), $preguntas )
		);
		$this->assertSame( 'No se ha podido presentar la solicitud.', ApplicationInput::why( array( 'answer:q1' ) ), 'sin las preguntas no se sabe cuál es' );
		$this->assertSame( 'Revise los apellidos de cada persona coordinadora.', ApplicationInput::why( array( 'coordinator_surname' ) ) );
	}

	/**
	 * Lo que falla se sabe también por campo, para marcarlo en su sitio: la
	 * coordinación con su fila, y cada código con el rótulo que lo nombra.
	 */
	public function test_the_failing_fields_are_known_one_by_one() {
		$r = ApplicationInput::validate(
			array(
				'position'     => 'conserje',
				'coordinators' => array(
					array(
						'name'    => 'Ana',
						'surname' => 'Pérez',
						'dni'     => '12345678Z',
						'email'   => 'ana@example.org',
					),
					array(
						'name'    => 'Luis',
						'surname' => '',
						'dni'     => '12345678A',
						'email'   => 'luis@example.org',
					),
				),
				'answers'      => array(),
			),
			$this->procedimiento( array( 'coordinator_count' => 2 ) )
		);

		$this->assertFalse( $r['ok'] );
		$this->assertSame(
			array( 'position', 'accept', 'answer:q1', 'coordinator_surname:1', 'coordinator_dni:1' ),
			$r['fields'],
			'la coordinación dice la fila; lo demás, su código'
		);
		$this->assertContains( 'coordinator_dni', $r['errors'], 'los códigos de siempre no cambian' );

		$this->assertSame(
			array(
				'position'              => 'el cargo',
				'accept'                => 'la aceptación de los compromisos',
				'answer:q1'             => 'la respuesta a «Modalidad»',
				'coordinator_surname:1' => 'los apellidos de la persona coordinadora 2',
				'coordinator_dni:1'     => 'el DNI o NIE de la persona coordinadora 2',
			),
			ApplicationInput::labels( $r['fields'], $this->procedimiento()['questions'] )
		);
		$this->assertSame( array(), ApplicationInput::labels( array( 'answer:q9' ) ), 'lo que no se sabe nombrar no se enlaza' );
	}
}
