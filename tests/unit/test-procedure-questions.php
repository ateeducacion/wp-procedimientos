<?php
/**
 * Tests for ProcedureQuestions: the per-procedure questions and their answers.
 *
 * @package Prc
 */

use Prc\Domain\ProcedureQuestions;

/**
 * Las preguntas del procedimiento: cuatro tipos, veinte como mucho, y una
 * clave inmutable por pregunta bajo la que se guardan las respuestas.
 */
class Test_Procedure_Questions extends WP_UnitTestCase {

	/**
	 * Una lista razonable se normaliza sin perder nada.
	 */
	public function test_a_reasonable_list_is_kept_and_normalised() {
		$preguntas = ProcedureQuestions::sanitize(
			array(
				array(
					'key'      => 'Q1',
					'label'    => ' Modalidad ',
					'help'     => ' Elija una ',
					'type'     => 'single',
					'required' => '1',
					'choices'  => " Modalidad A \n\n Modalidad B \n Modalidad A ",
				),
				array(
					'key'   => 'q2',
					'label' => 'Observaciones',
					'type'  => 'text',
				),
			)
		);

		$this->assertCount( 2, $preguntas );
		$this->assertSame(
			array(
				'key'      => 'q1',
				'label'    => 'Modalidad',
				'help'     => 'Elija una',
				'type'     => 'single',
				'required' => true,
				'choices'  => array( 'Modalidad A', 'Modalidad B' ),
			),
			$preguntas[0]
		);
		$this->assertSame( 'q2', $preguntas[1]['key'] );
		$this->assertFalse( $preguntas[1]['required'] );
		$this->assertSame( array(), $preguntas[1]['choices'], 'una respuesta libre no lleva opciones' );
	}

	/**
	 * El tipo es una lista cerrada; lo que no está en ella es texto libre.
	 */
	public function test_the_type_is_a_closed_list() {
		$this->assertSame( array( 'text', 'single', 'multiple', 'yesno', 'file' ), array_keys( ProcedureQuestions::types() ) );

		$preguntas = ProcedureQuestions::sanitize(
			array(
				array(
					'label'   => 'Inventada',
					'type'    => 'fecha',
					'choices' => array( 'A' ),
				),
				array(
					'label' => 'Sí o no',
					'type'  => 'yesno',
				),
			)
		);
		$this->assertSame( 'text', $preguntas[0]['type'] );
		$this->assertSame( array(), $preguntas[0]['choices'] );
		$this->assertSame( 'yesno', $preguntas[1]['type'] );
	}

	/**
	 * Las claves nuevas se generan detrás de la mayor vista, así que borrar
	 * una pregunta y crear otra nunca reutiliza una clave con respuestas; y
	 * reordenar no cambia ninguna.
	 */
	public function test_keys_are_generated_after_the_highest_and_survive_reordering() {
		$preguntas = ProcedureQuestions::sanitize(
			array(
				array(
					'key'   => 'q7',
					'label' => 'Séptima',
				),
				array( 'label' => 'Nueva' ),
				array(
					'key'   => 'q2',
					'label' => 'Segunda',
				),
				array( 'label' => 'Otra nueva' ),
			)
		);

		$this->assertSame( array( 'q7', 'q8', 'q2', 'q9' ), wp_list_pluck( $preguntas, 'key' ) );
	}

	/**
	 * Lo que no es una pregunta se cae: sin rótulo, sin forma, repetida, o de más.
	 */
	public function test_what_is_not_a_question_is_dropped() {
		$raw       = array(
			array( 'label' => '' ),
			'no es una pregunta',
			array(
				'key'   => 'q1',
				'label' => 'Una',
			),
			array(
				'key'   => 'q1',
				'label' => 'La misma clave',
			),
			array(
				'key'   => 'pregunta-1',
				'label' => 'Clave inventada',
			),
		);
		$preguntas = ProcedureQuestions::sanitize( $raw );

		$this->assertSame( array( 'q1', 'q2' ), wp_list_pluck( $preguntas, 'key' ) );
		$this->assertSame( 'Clave inventada', $preguntas[1]['label'], 'la clave inventada se sustituye por una generada' );
		$this->assertSame( array(), ProcedureQuestions::sanitize( 'nada' ) );

		$muchas = array();
		for ( $i = 1; $i <= 25; $i++ ) {
			$muchas[] = array( 'label' => 'Pregunta ' . $i );
		}
		$this->assertCount( ProcedureQuestions::MAX, ProcedureQuestions::sanitize( $muchas ) );
	}

	/**
	 * Solo lo que tiene forma de clave generada es una clave.
	 */
	public function test_only_generated_keys_are_keys() {
		foreach ( array( 'q1', 'q20', 'q9999' ) as $buena ) {
			$this->assertTrue( ProcedureQuestions::is_key( $buena ), $buena );
		}
		foreach ( array( '', 'q', 'q0', 'q01', 'q12345', 'Q1', 'p1', 'q1a' ) as $mala ) {
			$this->assertFalse( ProcedureQuestions::is_key( $mala ), $mala );
		}
	}

	/**
	 * Las respuestas se validan por tipo, y el tipo es toda la validación.
	 */
	public function test_answers_are_validated_by_type() {
		$preguntas = ProcedureQuestions::sanitize(
			array(
				array(
					'key'      => 'q1',
					'label'    => 'Modalidad',
					'type'     => 'single',
					'required' => true,
					'choices'  => array( 'A', 'B' ),
				),
				array(
					'key'     => 'q2',
					'label'   => 'Ejes',
					'type'    => 'multiple',
					'choices' => array( 'X', 'Y', 'Z' ),
				),
				array(
					'key'      => 'q3',
					'label'    => 'Acepta',
					'type'     => 'yesno',
					'required' => true,
				),
				array(
					'key'   => 'q4',
					'label' => 'Observaciones',
					'type'  => 'text',
				),
			)
		);

		$r = ProcedureQuestions::validate_answers(
			$preguntas,
			array(
				'q1'  => 'B',
				'q2'  => array( 'Z', 'X', 'W', 'Z' ),
				'q3'  => 'on',
				'q4'  => '  todo bien  ',
				'q99' => 'no existe',
			)
		);

		$this->assertTrue( $r['ok'] );
		$this->assertSame(
			array(
				'q1' => 'B',
				'q2' => array( 'Z', 'X' ),
				'q3' => true,
				'q4' => 'todo bien',
			),
			$r['data'],
			'lo que no es una pregunta no entra; lo que no es una opción tampoco'
		);
	}

	/**
	 * Obligatoria es obligatoria; el resto puede venir vacío.
	 */
	public function test_required_questions_need_an_answer() {
		$preguntas = ProcedureQuestions::sanitize(
			array(
				array(
					'key'      => 'q1',
					'label'    => 'Modalidad',
					'type'     => 'single',
					'required' => true,
					'choices'  => array( 'A', 'B' ),
				),
				array(
					'key'      => 'q2',
					'label'    => 'Acepta',
					'type'     => 'yesno',
					'required' => true,
				),
				array(
					'key'   => 'q3',
					'label' => 'Opcional',
					'type'  => 'text',
				),
			)
		);

		$r = ProcedureQuestions::validate_answers( $preguntas, array( 'q1' => 'C' ) );

		$this->assertFalse( $r['ok'] );
		$this->assertSame( array( 'q1', 'q2' ), $r['errors'] );
		$this->assertSame( '', $r['data']['q1'], 'una opción que no está en la lista no es una respuesta' );
		$this->assertFalse( $r['data']['q2'] );
		$this->assertSame( '', $r['data']['q3'] );
	}

	/**
	 * Una respuesta libre se corta en dos mil caracteres.
	 */
	public function test_a_free_answer_is_capped() {
		$preguntas = ProcedureQuestions::sanitize( array( array( 'label' => 'Texto' ) ) );
		$r         = ProcedureQuestions::validate_answers( $preguntas, array( 'q1' => str_repeat( 'á', 2500 ) ) );

		$this->assertSame( ProcedureQuestions::TEXT_MAX, mb_strlen( $r['data']['q1'] ) );
	}

	/**
	 * Y cada respuesta se escribe como texto para una columna.
	 */
	public function test_answers_are_written_as_text() {
		$this->assertSame( 'Sí', ProcedureQuestions::as_text( array( 'type' => 'yesno' ), true ) );
		$this->assertSame( 'No', ProcedureQuestions::as_text( array( 'type' => 'yesno' ), '' ) );
		$this->assertSame( 'X, Z', ProcedureQuestions::as_text( array( 'type' => 'multiple' ), array( 'X', 'Z' ) ) );
		$this->assertSame( 'B', ProcedureQuestions::as_text( array( 'type' => 'single' ), 'B' ) );
		$this->assertSame( '', ProcedureQuestions::as_text( array( 'type' => 'text' ), null ) );
	}
}
