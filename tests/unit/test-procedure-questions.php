<?php
/**
 * Tests for ProcedureQuestions: the per-procedure questions and their answers.
 *
 * @package Prc
 */

use Prc\Domain\ProcedureQuestions;
use Prc\Domain\QuestionChanges;
use Prc\Domain\QuestionGrid;
use Prc\Domain\QuestionPoints;

/**
 * Las preguntas del procedimiento: ocho tipos, cuarenta como mucho, y una
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
		$this->assertSame( array( 'text', 'single', 'multiple', 'yesno', 'file', 'number', 'date', 'grid' ), array_keys( ProcedureQuestions::types() ) );

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
		for ( $i = 1; $i <= ProcedureQuestions::MAX + 5; $i++ ) {
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
	/** Independent deliveries have inclusive calendar dates and never become application questions. */
	public function test_delivery_windows_are_inclusive_and_separate_from_the_application() {
		$questions = ProcedureQuestions::sanitize(
			array(
				array(
					'label'     => 'Memoria',
					'type'      => 'file',
					'delivery'  => true,
					'opens_at'  => '2026-11-01',
					'closes_at' => '2026-11-06',
				),
				array(
					'label'    => 'Autorización',
					'type'     => 'file',
					'required' => true,
				),
			)
		);
		$this->assertTrue( ProcedureQuestions::delivery_is_open( $questions[0], '2026-11-01' ) );
		$this->assertTrue( ProcedureQuestions::delivery_is_open( $questions[0], '2026-11-06' ) );
		$this->assertFalse( ProcedureQuestions::delivery_is_open( $questions[0], '2026-10-31' ) );
		$this->assertFalse( ProcedureQuestions::delivery_is_open( $questions[0], '2026-11-07' ) );
		$this->assertSame( array( $questions[1] ), ProcedureQuestions::application_questions( $questions ) );
	}

	/** Invalid schedules fail closed and never turn into ordinary required uploads. */
	public function test_invalid_delivery_dates_never_open_a_delivery() {
		foreach ( array( array( '', '' ), array( '2026-02-30', '2026-03-01' ), array( '2026-12-01', '2026-11-01' ), array( array(), '2026-11-06' ) ) as $range ) {
			$questions = ProcedureQuestions::sanitize(
				array(
					array(
						'label'     => 'Memoria',
						'type'      => 'file',
						'delivery'  => true,
						'opens_at'  => $range[0],
						'closes_at' => $range[1],
					),
				)
			);
			$this->assertFalse( ProcedureQuestions::valid_delivery( $questions[0] ) );
			$this->assertFalse( ProcedureQuestions::delivery_is_open( $questions[0], '2026-11-06' ) );
			$this->assertSame( array(), ProcedureQuestions::application_questions( $questions ) );
		}
		$questions = ProcedureQuestions::sanitize(
			array(
				array(
					'label'    => 'Texto',
					'type'     => 'text',
					'delivery' => true,
				),
			)
		);
		$this->assertFalse( ProcedureQuestions::is_delivery( $questions[0] ) );
	}

	// ─── número, fecha y rejilla (ADR-0040) ─────────────────────────────────

	/**
	 * Cuarenta preguntas como mucho.
	 */
	public function test_the_cap_is_forty() {
		$this->assertSame( 40, ProcedureQuestions::MAX );
	}

	/**
	 * Un número lleva su mínimo y su máximo, enteros u omitidos.
	 */
	public function test_a_number_keeps_an_integer_range() {
		$preguntas = ProcedureQuestions::sanitize(
			array(
				array(
					'label' => 'Grupos',
					'type'  => 'number',
					'min'   => ' 2 ',
					'max'   => '10',
				),
				array(
					'label' => 'Alumnado',
					'type'  => 'number',
					'min'   => 'abc',
					'max'   => '1,5',
				),
			)
		);

		$this->assertSame(
			array(
				'key'      => 'q1',
				'label'    => 'Grupos',
				'help'     => '',
				'type'     => 'number',
				'required' => false,
				'choices'  => array(),
				'min'      => 2,
				'max'      => 10,
			),
			$preguntas[0]
		);
		$this->assertNull( $preguntas[1]['min'], 'lo que no es un entero no es un límite' );
		$this->assertNull( $preguntas[1]['max'] );
	}

	/**
	 * Un número es un entero dentro de su rango; cero es una respuesta.
	 */
	public function test_a_number_answer_is_an_integer_in_range() {
		$q = ProcedureQuestions::sanitize(
			array(
				array(
					'key'   => 'q1',
					'label' => 'Grupos',
					'type'  => 'number',
					'max'   => 10,
				),
			)
		);

		$casos = array(
			'0'     => 0,
			' 5 '   => 5,
			'007'   => 7,
			'10'    => 10,
			'1,5'   => null,
			'1.5'   => null,
			'abc'   => null,
			'11'    => null,
			'-1'    => null,
			'+5'    => null,
			'1 000' => null,
		);
		foreach ( $casos as $tecleado => $esperado ) {
			$r = ProcedureQuestions::validate_answers( $q, array( 'q1' => $tecleado ) );
			if ( null === $esperado ) {
				$this->assertSame( array( 'q1' ), $r['errors'], 'se rechaza «' . $tecleado . '»' );
			} else {
				$this->assertSame( array(), $r['errors'], 'se acepta «' . $tecleado . '»' );
				$this->assertSame( $esperado, $r['data']['q1'] );
			}
		}

		$vacia = ProcedureQuestions::validate_answers( $q, array( 'q1' => '  ' ) );
		$this->assertTrue( $vacia['ok'], 'opcional, puede quedar sin responder' );
		$this->assertSame( '', $vacia['data']['q1'], 'y sin responder no es cero' );

		$q[0]['required'] = true;
		$this->assertSame( array( 'q1' ), ProcedureQuestions::validate_answers( $q, array( 'q1' => '' ) )['errors'] );
		$this->assertTrue( ProcedureQuestions::validate_answers( $q, array( 'q1' => '0' ) )['ok'], 'cero responde una obligatoria' );

		$q[0]['min'] = -5;
		$this->assertSame( -3, ProcedureQuestions::validate_answers( $q, array( 'q1' => '-3' ) )['data']['q1'], 'con mínimo negativo, caben negativos' );
		$q[0]['min'] = 2;
		$this->assertFalse( ProcedureQuestions::validate_answers( $q, array( 'q1' => '1' ) )['ok'], 'por debajo del mínimo' );

		$this->assertSame( '0', ProcedureQuestions::as_text( $q[0], 0 ) );
		$this->assertSame( '', ProcedureQuestions::as_text( $q[0], '' ) );
	}

	/**
	 * Una fecha es un día que existe, y se lee como día/mes/año.
	 */
	public function test_a_date_answer_is_a_real_day() {
		$q = ProcedureQuestions::sanitize(
			array(
				array(
					'key'   => 'q1',
					'label' => 'Fecha del claustro',
					'type'  => 'date',
				),
			)
		);

		$this->assertSame( array( 'q1' ), ProcedureQuestions::validate_answers( $q, array( 'q1' => '2026-02-30' ) )['errors'] );
		$this->assertSame( array( 'q1' ), ProcedureQuestions::validate_answers( $q, array( 'q1' => '28/02/2026' ) )['errors'] );
		$this->assertSame( '2026-02-28', ProcedureQuestions::validate_answers( $q, array( 'q1' => ' 2026-02-28 ' ) )['data']['q1'] );
		$this->assertSame( '', ProcedureQuestions::validate_answers( $q, array() )['data']['q1'], 'opcional y vacía' );

		$q[0]['required'] = true;
		$this->assertFalse( ProcedureQuestions::validate_answers( $q, array( 'q1' => '' ) )['ok'] );

		$this->assertSame( '28/02/2026', ProcedureQuestions::as_text( $q[0], '2026-02-28' ) );
	}

	/**
	 * Una rejilla. Cada fila, con su clave; una por línea, sin repetir, hasta treinta.
	 *
	 * @param bool $required Whether it is required.
	 * @return array<int, array<string, mixed>>
	 */
	private function rejilla( bool $required = false ): array {
		return ProcedureQuestions::sanitize(
			array(
				array(
					'key'      => 'q4',
					'label'    => 'Alumnado por etapa',
					'type'     => 'grid',
					'required' => $required,
					'rows'     => "Infantil\n Primaria \n\nInfantil",
				),
			)
		);
	}

	/**
	 * Las filas de una rejilla reciben su clave como las preguntas.
	 */
	public function test_a_grid_gets_keyed_rows() {
		$q = $this->rejilla();

		$this->assertSame(
			array(
				array(
					'key'   => 'r1',
					'label' => 'Infantil',
				),
				array(
					'key'   => 'r2',
					'label' => 'Primaria',
				),
			),
			$q[0]['rows']
		);
		$this->assertSame( 2, $q[0]['row_seq'] );
		$this->assertSame( $q, ProcedureQuestions::sanitize( $q ), 'normalizar lo normalizado no cambia nada' );

		$muchas = ProcedureQuestions::sanitize(
			array(
				array(
					'label' => 'Muchas',
					'type'  => 'grid',
					'rows'  => implode( "\n", array_map( 'strval', range( 1, 40 ) ) ),
				),
			)
		);
		$this->assertCount( ProcedureQuestions::MAX_ROWS, $muchas[0]['rows'] );
		$this->assertSame( 30, ProcedureQuestions::MAX_ROWS );

		$this->assertSame(
			array(),
			ProcedureQuestions::sanitize(
				array(
					array(
						'label' => 'Sin filas',
						'type'  => 'grid',
					),
				)
			)[0]['rows']
		);
	}

	/**
	 * Obligatoria, pide todas las filas; opcional, las vacías se quedan vacías.
	 */
	public function test_a_grid_answer_is_one_count_per_row() {
		$q = $this->rejilla();

		$r = ProcedureQuestions::validate_answers(
			$q,
			array(
				'q4' => array(
					'r1' => ' 3 ',
					'r9' => '7',
				),
			)
		);
		$this->assertTrue( $r['ok'] );
		$this->assertSame(
			array(
				'r1' => 3,
				'r2' => '',
			),
			$r['data']['q4'],
			'una fila vacía no es un cero, y una fila que no existe no entra'
		);
		$this->assertSame( 'Infantil: 3', ProcedureQuestions::as_text( $q[0], $r['data']['q4'] ) );

		$this->assertFalse( ProcedureQuestions::validate_answers( $q, array( 'q4' => array( 'r1' => '-1' ) ) )['ok'] );
		$this->assertFalse( ProcedureQuestions::validate_answers( $q, array( 'q4' => array( 'r1' => '1,5' ) ) )['ok'] );
		$this->assertFalse( ProcedureQuestions::validate_answers( $q, array( 'q4' => 'abc' ) )['ok'], 'una rejilla no se contesta con un texto' );

		$obligada = $this->rejilla( true );
		$this->assertSame( array( 'q4' ), ProcedureQuestions::validate_answers( $obligada, array( 'q4' => array( 'r1' => '3' ) ) )['errors'] );
		$r = ProcedureQuestions::validate_answers(
			$obligada,
			array(
				'q4' => array(
					'r1' => '3',
					'r2' => '0',
				),
			)
		);
		$this->assertTrue( $r['ok'] );
		$this->assertSame( 'Infantil: 3; Primaria: 0', ProcedureQuestions::as_text( $obligada[0], $r['data']['q4'] ) );
	}

	/**
	 * Cada fila de una rejilla es una columna propia en la exportación.
	 */
	public function test_each_grid_row_is_its_own_column() {
		$q = $this->rejilla();

		$this->assertSame(
			array(
				'q4:r1' => 'Alumnado por etapa — Infantil',
				'q4:r2' => 'Alumnado por etapa — Primaria',
			),
			QuestionGrid::columns( $q[0] )
		);
		$this->assertSame(
			array(
				'q4:r1' => '3',
				'q4:r2' => '',
			),
			QuestionGrid::cells(
				$q[0],
				array(
					'r1' => 3,
					'r3' => 9,
				)
			)
		);
		$this->assertSame( array(), QuestionGrid::columns( array( 'type' => 'text' ) ) );
	}

	/**
	 * Renombrar una fila conserva su clave; borrarla no se la da a otra.
	 */
	public function test_renaming_or_removing_grid_rows_keeps_the_keys() {
		$antes = array(
			array(
				'key'   => 'r1',
				'label' => 'Infantil',
			),
			array(
				'key'   => 'r2',
				'label' => 'Primaria',
			),
			array(
				'key'   => 'r3',
				'label' => 'Secundaria',
			),
		);

		// Renombrar en su línea.
		$this->assertSame(
			array( 'r1', 'r2', 'r3' ),
			wp_list_pluck( QuestionGrid::match_rows( "Infantil\nEducación Primaria\nSecundaria", $antes ), 'key' )
		);

		// Una renombrada y otra borrada dejando su línea en blanco.
		$filas = QuestionGrid::match_rows( "Infantil\n\nESO", $antes );
		$this->assertSame( array( 'r1', 'r3' ), wp_list_pluck( $filas, 'key' ) );
		$this->assertSame( 'ESO', $filas[1]['label'] );

		// Borrar una línea entera sin renombrar: cada una encuentra la suya.
		$this->assertSame( array( 'r1', 'r3' ), wp_list_pluck( QuestionGrid::match_rows( "Infantil\nSecundaria", $antes ), 'key' ) );

		// Borrar una línea entera y renombrar otra es ambiguo: la renombrada
		// pasa por nueva antes que heredar las respuestas de otra fila.
		$this->assertSame( array( 'r1', '' ), wp_list_pluck( QuestionGrid::match_rows( "Infantil\nESO", $antes ), 'key' ) );

		// Y la nueva no hereda una clave borrada: la cuenta sigue.
		$q = ProcedureQuestions::sanitize(
			array(
				array(
					'key'     => 'q4',
					'label'   => 'Alumnado',
					'type'    => 'grid',
					'rows'    => QuestionGrid::match_rows( "Infantil\nESO", $antes ),
					'row_seq' => 3,
				),
			)
		);
		$this->assertSame( array( 'r1', 'r4' ), wp_list_pluck( $q[0]['rows'], 'key' ) );
		$this->assertSame( 4, $q[0]['row_seq'] );
	}

	/**
	 * Con respuestas guardadas, una fila renombrada sigue con las suyas y una
	 * borrada deja de salir.
	 */
	public function test_stored_grid_answers_follow_their_rows() {
		$antes = $this->rejilla();
		$dadas = array(
			'r1' => 3,
			'r2' => 5,
		);

		$despues = ProcedureQuestions::sanitize(
			array(
				array(
					'key'     => 'q4',
					'label'   => 'Alumnado por etapa',
					'type'    => 'grid',
					'rows'    => QuestionGrid::match_rows( "\nEducación Primaria", $antes[0]['rows'] ),
					'row_seq' => $antes[0]['row_seq'],
				),
			)
		);

		$this->assertSame( 'Educación Primaria: 5', ProcedureQuestions::as_text( $despues[0], $dadas ) );
		$this->assertSame( array( 'q4:r2' => '5' ), QuestionGrid::cells( $despues[0], $dadas ) );
	}

	// ─── puntos ────────────────────────────────────────────────────────────

	/**
	 * Una opción de «varias» puede valer puntos; quien solicita solo ve el texto.
	 */
	public function test_multiple_choices_may_carry_points() {
		$q = ProcedureQuestions::sanitize(
			array(
				array(
					'key'     => 'q1',
					'label'   => 'Méritos',
					'type'    => 'multiple',
					'choices' => "Huerto | 3\nRadio | 0\nBiblioteca\nA | B | 2\nSin número | x",
				),
			)
		);

		$this->assertSame( array( 'Huerto', 'Radio', 'Biblioteca', 'A | B', 'Sin número | x' ), $q[0]['choices'] );
		$this->assertSame( array( 3, 0, 0, 2, 0 ), $q[0]['points'] );
		$this->assertSame( $q, ProcedureQuestions::sanitize( $q ), 'normalizar lo normalizado no cambia nada' );
		$this->assertSame( array( 'Huerto | 3', 'Radio | 0', 'Biblioteca | 0', 'A | B | 2', 'Sin número | x | 0' ), QuestionPoints::lines( $q[0] ) );
		$this->assertTrue( QuestionPoints::has_points( $q ) );

		$sin = ProcedureQuestions::sanitize(
			array(
				array(
					'label'   => 'Ejes',
					'type'    => 'multiple',
					'choices' => "X\nY",
				),
				array(
					'label'   => 'Modalidad',
					'type'    => 'single',
					'choices' => "Una | 3\nOtra",
				),
			)
		);
		$this->assertArrayNotHasKey( 'points', $sin[0], 'sin puntos, la pregunta es la de siempre' );
		$this->assertSame( array( 'Una | 3', 'Otra' ), $sin[1]['choices'], 'una sola opción no lleva puntos' );
		$this->assertFalse( QuestionPoints::has_points( $sin ) );
	}

	/**
	 * La puntuación suma solo lo marcado que sigue en la lista; sin puntos, no hay puntuación.
	 */
	public function test_the_score_adds_only_the_ticked_choices() {
		$q = ProcedureQuestions::sanitize(
			array(
				array(
					'key'     => 'q1',
					'label'   => 'Méritos',
					'type'    => 'multiple',
					'choices' => "Huerto | 3\nRadio | 2\nBiblioteca",
				),
				array(
					'key'     => 'q2',
					'label'   => 'Más',
					'type'    => 'multiple',
					'choices' => "Uno | 10\nDos | 20",
				),
			)
		);

		// Lo guardado antes de poner puntos es el texto de la opción, y casa igual.
		$r = ProcedureQuestions::validate_answers( $q, array( 'q1' => array( 'Huerto', 'Biblioteca' ) ) );
		$this->assertSame( array( 'Huerto', 'Biblioteca' ), $r['data']['q1'] );
		$this->assertSame( 3, QuestionPoints::score( $q, $r['data'] ), 'una opción sin puntos vale cero, y una desmarcada no suma' );
		$this->assertSame( 0, QuestionPoints::score( $q, array() ) );
		$this->assertSame(
			23,
			QuestionPoints::score(
				$q,
				array(
					'q1' => array( 'Huerto' ),
					'q2' => array( 'Dos' ),
				)
			)
		);

		// Una opción borrada después de responder deja de sumar.
		$q[0]['choices'] = array( 'Radio', 'Biblioteca' );
		$q[0]['points']  = array( 2, 0 );
		$this->assertSame( 0, QuestionPoints::score( $q, array( 'q1' => array( 'Huerto', 'Biblioteca' ) ) ) );

		$this->assertNull( QuestionPoints::score( $this->rejilla(), array() ), 'sin puntos no hay puntuación' );
	}

	// ─── el orden y los cambios ────────────────────────────────────────────

	/**
	 * Subir y bajar cambian el orden y nada más; en los extremos no hacen nada.
	 */
	public function test_moving_a_question_keeps_its_key() {
		$q = ProcedureQuestions::sanitize(
			array(
				array( 'label' => 'Una' ),
				array( 'label' => 'Dos' ),
				array( 'label' => 'Tres' ),
			)
		);

		$this->assertSame( array( 'q2', 'q1', 'q3' ), wp_list_pluck( QuestionChanges::move( $q, 'q2', QuestionChanges::MOVE_UP ), 'key' ) );
		$this->assertSame( array( 'q1', 'q3', 'q2' ), wp_list_pluck( QuestionChanges::move( $q, 'q2', QuestionChanges::MOVE_DOWN ), 'key' ) );
		$this->assertSame( 'Dos', QuestionChanges::move( $q, 'q2', QuestionChanges::MOVE_UP )[0]['label'] );
		$this->assertSame( $q, QuestionChanges::move( $q, 'q1', QuestionChanges::MOVE_UP ), 'la primera no sube' );
		$this->assertSame( $q, QuestionChanges::move( $q, 'q3', QuestionChanges::MOVE_DOWN ), 'la última no baja' );
		$this->assertSame( $q, QuestionChanges::move( $q, 'q9', QuestionChanges::MOVE_UP ), 'una que no existe, tampoco' );
		$this->assertSame( $q, QuestionChanges::move( $q, 'q2', 'sideways' ) );
	}

	/**
	 * El tipo de una pregunta con respuestas no cambia; el resto sí.
	 */
	public function test_the_type_of_an_answered_question_cannot_change() {
		$antes              = ProcedureQuestions::sanitize(
			array(
				array(
					'key'   => 'q1',
					'label' => 'Grupos',
					'type'  => 'text',
				),
				array(
					'key'   => 'q2',
					'label' => 'Sin respuestas',
					'type'  => 'text',
				),
			)
		);
		$despues            = $antes;
		$despues[0]['type'] = 'number';
		$despues[1]['type'] = 'date';

		$porque = QuestionChanges::refusal( $antes, $despues, array( 'q1' => true ) );
		$this->assertStringContainsString( '«Grupos»', $porque );
		$this->assertStringNotContainsString( 'Sin respuestas', $porque );
		$this->assertSame( '', QuestionChanges::refusal( $antes, $despues, array() ), 'sin respuestas, el tipo cambia' );

		$renombrada             = $antes;
		$renombrada[0]['label'] = 'Número de grupos';
		$this->assertSame( '', QuestionChanges::refusal( $antes, $renombrada, array( 'q1' => true ) ) );

		$rango = ProcedureQuestions::sanitize(
			array(
				array(
					'label' => 'Grupos',
					'type'  => 'number',
					'min'   => 5,
					'max'   => 2,
				),
			)
		);
		$this->assertStringContainsString( 'mínimo', QuestionChanges::refusal( array(), $rango, array() ) );
	}

	// ─── correcciones de la revisión ───────────────────────────────────────

	/**
	 * Las claves nuevas salen de un contador que nunca baja: borrar la última
	 * y crear otra no le da su clave.
	 */
	public function test_new_keys_follow_a_counter_that_never_goes_down() {
		$seq       = 3;
		$preguntas = ProcedureQuestions::sanitize(
			array(
				array(
					'key'   => 'q1',
					'label' => 'Queda',
				),
				array( 'label' => 'Nueva' ),
			),
			$seq
		);
		$this->assertSame( array( 'q1', 'q4' ), wp_list_pluck( $preguntas, 'key' ), 'q3 se borró: no vuelve' );
		$this->assertSame( 4, $seq );

		$seq = 0;
		ProcedureQuestions::sanitize(
			array(
				array(
					'key'   => 'q7',
					'label' => 'Más alta',
				),
			),
			$seq
		);
		$this->assertSame( 7, $seq, 'el contador sube con la mayor clave vista' );

		$this->assertSame( array( 'q1' ), wp_list_pluck( ProcedureQuestions::sanitize( array( array( 'label' => 'Sin contador' ) ) ), 'key' ) );
	}

	/**
	 * Unas opciones guardadas como lista y sin `points` se toman tal cual:
	 * los puntos solo se leen en el texto que escribe el taller.
	 */
	public function test_stored_choices_without_points_are_taken_literally() {
		$q = ProcedureQuestions::sanitize(
			array(
				array(
					'key'     => 'q1',
					'label'   => 'Curso',
					'type'    => 'multiple',
					'choices' => array( 'Curso | 2024', 'Otro' ),
				),
			)
		);

		$this->assertSame( array( 'Curso | 2024', 'Otro' ), $q[0]['choices'] );
		$this->assertArrayNotHasKey( 'points', $q[0] );
		$this->assertFalse( QuestionPoints::has_points( $q ) );
		$this->assertSame( array( 'Curso | 2024' ), ProcedureQuestions::validate_answers( $q, array( 'q1' => array( 'Curso | 2024' ) ) )['data']['q1'], 'lo guardado sigue casando' );
		$this->assertNull( QuestionPoints::score( $q, array( 'q1' => array( 'Curso | 2024' ) ) ) );
	}

	/**
	 * Una opción antigua con `|` sobrevive a volver a guardar el panel: el
	 * taller la escribe con « | 0» y se lee igual que estaba, sin puntos.
	 */
	public function test_stored_choices_with_a_bar_survive_the_workshop_round_trip() {
		$q = ProcedureQuestions::sanitize(
			array(
				array(
					'key'     => 'q1',
					'label'   => 'Curso',
					'type'    => 'multiple',
					'choices' => array( 'Curso | 2024', 'Otro' ),
				),
			)
		);

		$this->assertSame( array( 'Curso | 2024 | 0', 'Otro | 0' ), QuestionPoints::lines( $q[0] ) );

		$otra = ProcedureQuestions::sanitize(
			array(
				array(
					'key'     => 'q1',
					'label'   => 'Curso',
					'type'    => 'multiple',
					'choices' => implode( "\n", QuestionPoints::lines( $q[0] ) ),
				),
			)
		);
		$this->assertSame( array( 'Curso | 2024', 'Otro' ), $otra[0]['choices'] );
		$this->assertArrayNotHasKey( 'points', $otra[0], 'todo a cero es no tener puntos' );

		$sin_barra = ProcedureQuestions::sanitize(
			array(
				array(
					'label'   => 'Ejes',
					'type'    => 'multiple',
					'choices' => array( 'X', 'Y' ),
				),
			)
		);
		$this->assertSame( array( 'X', 'Y' ), QuestionPoints::lines( $sin_barra[0] ), 'sin `|`, las líneas son el texto' );
	}

	/**
	 * Solo se revisan los puntos de lo tecleado nuevo o cambiado, y no los de
	 * una pregunta que se borra.
	 */
	public function test_only_new_or_changed_choice_lines_are_checked() {
		$antes   = ProcedureQuestions::sanitize(
			array(
				array(
					'key'     => 'q1',
					'label'   => 'Nota',
					'type'    => 'multiple',
					'choices' => array( 'Nota media | 7,5', 'Otra' ),
				),
				array(
					'key'   => 'q2',
					'label' => 'Grupos',
					'type'  => 'text',
				),
			)
		);
		$enviada = function ( string $label, string $choices ) use ( $antes ): array {
			return array(
				array(
					'key'     => 'q1',
					'label'   => $label,
					'type'    => 'multiple',
					'choices' => $choices,
				),
				array(
					'key'   => 'q2',
					'label' => 'Número de grupos',
					'type'  => 'text',
				),
			);
		};

		$this->assertSame( '', QuestionChanges::input_errors( $enviada( 'Nota', implode( "\n", QuestionPoints::lines( $antes[0] ) ) ), $antes ) );
		$this->assertSame( '', QuestionChanges::input_errors( $enviada( 'Nota', "Nota media | 7,5\nOtra" ), $antes ), 'como se pintaba antes, tampoco' );
		$this->assertSame( '', QuestionChanges::input_errors( $enviada( '', "Nota media | 7,5\nB | -2" ), $antes ), 'una pregunta que se borra no se revisa' );
		$this->assertStringContainsString( '«B | -2»', QuestionChanges::input_errors( $enviada( 'Nota', "Nota media | 7,5\nB | -2" ), $antes ) );
	}

	/**
	 * Sin mínimo, el mínimo es cero: un máximo negativo es un rango imposible.
	 */
	public function test_a_negative_maximum_without_minimum_is_refused() {
		$q = ProcedureQuestions::sanitize(
			array(
				array(
					'label' => 'Grupos',
					'type'  => 'number',
					'max'   => -3,
				),
			)
		);
		$this->assertStringContainsString( 'mínimo', QuestionChanges::refusal( array(), $q, array() ) );
	}

	/**
	 * Si una línea igual que una fila de antes cambió de sitio, ninguna otra
	 * hereda clave por posición.
	 */
	public function test_a_shifted_exact_line_stops_positional_keys() {
		$antes = array(
			array(
				'key'   => 'r1',
				'label' => 'Infantil',
			),
			array(
				'key'   => 'r2',
				'label' => 'Primaria',
			),
			array(
				'key'   => 'r3',
				'label' => 'ESO',
			),
		);
		$this->assertSame( array( 'r2', '', '' ), wp_list_pluck( QuestionGrid::match_rows( "Primaria\nX\nY", $antes ), 'key' ) );
	}

	/**
	 * Una fila que llega como lista no es una cantidad: es un error, no un vacío.
	 */
	public function test_a_nested_grid_row_is_an_error() {
		$r = ProcedureQuestions::validate_answers( $this->rejilla(), array( 'q4' => array( 'r1' => array( 'x' ) ) ) );
		$this->assertSame( array( 'q4' ), $r['errors'] );
	}

	/**
	 * Unos puntos o un rango mal escritos en el taller se avisan, no se guardan como texto.
	 */
	public function test_badly_written_points_and_ranges_are_reported() {
		$pregunta = function ( array $cambios ): array {
			return array(
				array_merge(
					array(
						'label' => 'Méritos',
						'type'  => 'multiple',
					),
					$cambios
				),
			);
		};

		$this->assertStringContainsString( '«B | -2»', QuestionChanges::input_errors( $pregunta( array( 'choices' => "A | 3\nB | -2" ) ) ) );
		$this->assertStringContainsString( '«C | 1.5»', QuestionChanges::input_errors( $pregunta( array( 'choices' => 'C | 1.5' ) ) ) );
		$this->assertSame( '', QuestionChanges::input_errors( $pregunta( array( 'choices' => "A | B\nSin número | x\nC | 0\nD" ) ) ) );
		$this->assertSame( '', QuestionChanges::input_errors( $pregunta( array( 'choices' => array( 'B | -2' ) ) ) ), 'lo guardado no se revisa' );
		$this->assertSame(
			'',
			QuestionChanges::input_errors(
				$pregunta(
					array(
						'type'    => 'single',
						'choices' => 'B | -2',
					)
				)
			),
			'una sola opción no lleva puntos'
		);

		$numero = function ( string $min, string $max ): array {
			return array(
				array(
					'label' => 'Grupos',
					'type'  => 'number',
					'min'   => $min,
					'max'   => $max,
				),
			);
		};
		$this->assertStringContainsString( 'mínimo', QuestionChanges::input_errors( $numero( 'abc', '' ) ) );
		$this->assertStringContainsString( 'máximo', QuestionChanges::input_errors( $numero( '', '1,5' ) ) );
		$this->assertSame( '', QuestionChanges::input_errors( $numero( ' -2 ', '10' ) ) );
		$this->assertSame( '', QuestionChanges::input_errors( $numero( '', '' ) ) );
	}
}
