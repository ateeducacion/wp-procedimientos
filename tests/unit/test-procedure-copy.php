<?php
/**
 * Tests for ProcedureCopy: what a duplicate takes from its original.
 *
 * @package Prc
 */

use Prc\Domain\PeopleSpec;
use Prc\Domain\ProcedureCopy;
use Prc\Domain\ProcedureInput;
use Prc\Domain\ProcedureQuestions;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ProcedurePostType;

/**
 * Qué se lleva un duplicado de su original, sin tocar WordPress.
 *
 * Lo importante es la primera prueba: cada meta del procedimiento tiene que
 * estar clasificada —se copia, se transforma o nunca—. El próximo PR que
 * añada una meta y no lo decida se pone en rojo aquí.
 */
class Test_Procedure_Copy extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Con el aplicativo cargado: las metas se registran en `init`.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
	}

	// ─── la clasificación ──────────────────────────────────────────────────

	/**
	 * Toda meta registrada del procedimiento está clasificada, y la
	 * clasificación no nombra ninguna que no exista.
	 */
	public function test_every_registered_procedure_meta_is_classified() {
		$registradas = array_keys( get_registered_meta_keys( 'post', ProcedurePostType::POST_TYPE ) );
		$this->assertNotEmpty( $registradas );

		$sin_decidir = array_values( array_diff( $registradas, array_keys( ProcedureCopy::policy() ) ) );
		$this->assertSame( array(), $sin_decidir, 'cada meta nueva del procedimiento tiene que decir si se copia al duplicar: ProcedureCopy::policy()' );

		$this->assertSame( array(), array_values( array_diff( array_keys( ProcedureCopy::policy() ), $registradas ) ), 'ni una clave clasificada que no esté registrada' );
		$this->assertEqualsCanonicalizing( ProcedureMetaKeys::all(), array_keys( ProcedureCopy::policy() ) );

		foreach ( ProcedureCopy::policy() as $clave => $regla ) {
			$this->assertContains( $regla, array( ProcedureCopy::COPY, ProcedureCopy::TRANSFORM, ProcedureCopy::NEVER ), $clave );
			$this->assertSame( $regla, ProcedureCopy::rule( $clave ) );
		}
		$this->assertSame( '', ProcedureCopy::rule( 'prc_inventada' ) );
	}

	/**
	 * Lo que nunca se copia: el calendario, los enlaces y las marcas de cierre.
	 */
	public function test_dates_links_and_closing_marks_are_never_copied() {
		foreach ( array(
			ProcedureMetaKeys::OPENS_AT,
			ProcedureMetaKeys::CLOSES_AT,
			ProcedureMetaKeys::AMEND_OPENS_AT,
			ProcedureMetaKeys::AMEND_CLOSES_AT,
			ProcedureMetaKeys::RESOLUTION_URL,
			ProcedureMetaKeys::PROVISIONAL_LIST_URL,
			ProcedureMetaKeys::FINAL_LIST_URL,
			ProcedureMetaKeys::ARCHIVED,
			ProcedureMetaKeys::APPLICATIONS_CLOSED,
		) as $clave ) {
			$this->assertSame( ProcedureCopy::NEVER, ProcedureCopy::rule( $clave ), $clave );
		}
		foreach ( array( ProcedureMetaKeys::QUESTIONS, ProcedureMetaKeys::QUESTIONS_SEQ, ProcedureMetaKeys::PEOPLE_SPEC, ProcedureMetaKeys::OBSERVER_COLUMNS, ProcedureMetaKeys::CUSTOM_JS ) as $clave ) {
			$this->assertSame( ProcedureCopy::TRANSFORM, ProcedureCopy::rule( $clave ), $clave );
		}
	}

	// ─── las preguntas ─────────────────────────────────────────────────────

	/**
	 * Las claves con huecos —se borraron preguntas en medio— salen
	 * renumeradas desde `q1`, en su orden, y el contador es el número de
	 * preguntas.
	 */
	public function test_questions_are_renumbered_without_gaps_in_their_order() {
		$copia = ProcedureCopy::questions(
			array(
				array(
					'key'   => 'q7',
					'label' => 'Séptima',
					'type'  => 'text',
				),
				array(
					'key'   => 'q2',
					'label' => 'Segunda',
					'type'  => 'yesno',
				),
				array(
					'key'   => 'q12',
					'label' => 'Duodécima',
					'type'  => 'number',
					'min'   => '1',
					'max'   => '9',
				),
			)
		);

		$this->assertSame( array( 'q1', 'q2', 'q3' ), array_column( $copia['questions'], 'key' ) );
		$this->assertSame( array( 'Séptima', 'Segunda', 'Duodécima' ), array_column( $copia['questions'], 'label' ) );
		$this->assertSame( 3, $copia['seq'] );
		$this->assertSame(
			array(
				'q7'  => 'q1',
				'q2'  => 'q2',
				'q12' => 'q3',
			),
			$copia['map']
		);
		$this->assertSame( 1, $copia['questions'][2]['min'] );
		$this->assertSame( 9, $copia['questions'][2]['max'] );
		$this->assertArrayNotHasKey( 'undated', $copia );
	}

	/**
	 * Las filas de una rejilla también se renumeran, y su clave entra en el mapa.
	 */
	public function test_grid_rows_are_renumbered_too() {
		$copia = ProcedureCopy::questions(
			array(
				array(
					'key'     => 'q4',
					'label'   => 'Alumnado por etapa',
					'type'    => 'grid',
					'rows'    => array(
						array(
							'key'   => 'r3',
							'label' => 'Infantil',
						),
						array(
							'key'   => 'r5',
							'label' => 'Primaria',
						),
					),
					'row_seq' => 5,
				),
			)
		);

		$rejilla = $copia['questions'][0];
		$this->assertSame( 'q1', $rejilla['key'] );
		$this->assertSame( array( 'r1', 'r2' ), array_column( $rejilla['rows'], 'key' ) );
		$this->assertSame( array( 'Infantil', 'Primaria' ), array_column( $rejilla['rows'], 'label' ) );
		$this->assertSame( 2, $rejilla['row_seq'] );
		$this->assertSame( 'q1:r1', $copia['map']['q4:r3'] );
		$this->assertSame( 'q1:r2', $copia['map']['q4:r5'] );
	}

	/**
	 * Una entrega con plazo propio se copia sin sus fechas, y se dice cuál.
	 */
	public function test_deliveries_lose_their_dates() {
		$copia = ProcedureCopy::questions(
			array(
				array(
					'key'       => 'q3',
					'label'     => 'Memoria final',
					'type'      => 'file',
					'delivery'  => true,
					'opens_at'  => '2026-05-01',
					'closes_at' => '2026-05-31',
				),
				array(
					'key'   => 'q5',
					'label' => 'Acta del claustro',
					'type'  => 'file',
				),
			)
		);

		$entrega = $copia['questions'][0];
		$this->assertTrue( ProcedureQuestions::is_delivery( $entrega ), 'sigue siendo una entrega' );
		$this->assertSame( '', $entrega['opens_at'] );
		$this->assertSame( '', $entrega['closes_at'] );
		$this->assertFalse( ProcedureQuestions::valid_delivery( $entrega ) );
		$this->assertArrayNotHasKey( 'opens_at', $copia['questions'][1], 'un archivo de la solicitud no tiene plazo que quitar' );
	}

	// ─── las personas ──────────────────────────────────────────────────────

	/**
	 * La especificación de personas se copia sin su ventana y con papeles y
	 * grupos renumerados.
	 */
	public function test_the_people_spec_loses_its_window_and_is_renumbered() {
		$spec = ProcedureCopy::people_spec(
			array(
				'enabled'          => true,
				'roles'            => array(
					array(
						'key'         => 'r2',
						'label'       => 'Coordinación',
						'needs_group' => false,
					),
					array(
						'key'         => 'r6',
						'label'       => 'Participante',
						'needs_group' => true,
					),
				),
				'role_seq'         => 6,
				'groups'           => array(
					array(
						'key'   => 'g4',
						'label' => 'Eje 1',
					),
				),
				'group_seq'        => 4,
				'min'              => 2,
				'max'              => 20,
				'unique_per_group' => true,
				'opens_at'         => '2026-11-01',
				'closes_at'        => '2026-11-30',
				'admitted_only'    => true,
			)
		);

		$this->assertTrue( $spec['enabled'] );
		$this->assertSame( array( 'r1', 'r2' ), array_column( $spec['roles'], 'key' ) );
		$this->assertSame( array( false, true ), array_column( $spec['roles'], 'needs_group' ) );
		$this->assertSame( 2, $spec['role_seq'] );
		$this->assertSame( array( 'g1' ), array_column( $spec['groups'], 'key' ) );
		$this->assertSame( 1, $spec['group_seq'] );
		$this->assertSame( 2, $spec['min'] );
		$this->assertSame( 20, $spec['max'] );
		$this->assertTrue( $spec['unique_per_group'] );
		$this->assertTrue( $spec['admitted_only'] );
		$this->assertSame( '', $spec['opens_at'] );
		$this->assertSame( '', $spec['closes_at'] );
		$this->assertSame( PeopleSpec::sanitize( $spec ), $spec, 'y es una especificación válida tal cual' );
	}

	// ─── la consulta ───────────────────────────────────────────────────────

	/**
	 * Las columnas de la consulta que apuntan a preguntas se traducen a las
	 * claves nuevas; las que no se pueden traducir se caen.
	 */
	public function test_observer_columns_follow_the_new_keys() {
		$mapa = array(
			'q7'    => 'q1',
			'q4'    => 'q2',
			'q4:r3' => 'q2:r1',
		);
		$this->assertSame(
			array( 'centre', 'q1', 'q2:r1', 'state' ),
			ProcedureCopy::observer_columns( array( 'centre', 'q7', 'q9', 'q2:r1', 'q4:r3', 'q4:r8', 'state', 'q7' ), $mapa )
		);
		$this->assertSame( array(), ProcedureCopy::observer_columns( 'basura', $mapa ) );
	}

	// ─── el conjunto ───────────────────────────────────────────────────────

	/**
	 * Lo que se escribe en el duplicado, a partir de las metas del original.
	 */
	public function test_meta_copies_transforms_and_leaves_out() {
		$original = array(
			ProcedureMetaKeys::OPENS_AT         => '2026-01-10',
			ProcedureMetaKeys::CLOSES_AT        => '2026-01-20',
			ProcedureMetaKeys::RESOLUTION_URL   => 'https://example.org/r.pdf',
			ProcedureMetaKeys::ARCHIVED         => true,
			ProcedureMetaKeys::CONTACT_EMAILS   => array( 'ambito@example.org' ),
			ProcedureMetaKeys::HEADER_COLOR     => 'verde',
			ProcedureMetaKeys::NOTIFY           => array( 'review_changed' ),
			ProcedureMetaKeys::CUSTOM_CSS       => '.prc { color: red; }',
			ProcedureMetaKeys::CUSTOM_JS        => 'console.log(1);',
			ProcedureMetaKeys::OBSERVABLE       => true,
			ProcedureMetaKeys::OBSERVER_COLUMNS => array( 'centre', 'q5' ),
			ProcedureMetaKeys::QUESTIONS        => array(
				array(
					'key'   => 'q5',
					'label' => 'Una',
					'type'  => 'text',
				),
			),
			ProcedureMetaKeys::QUESTIONS_SEQ    => 9,
		);

		$sin_js = ProcedureCopy::meta( $original, false );
		$meta   = $sin_js['meta'];
		foreach ( array( ProcedureMetaKeys::OPENS_AT, ProcedureMetaKeys::CLOSES_AT, ProcedureMetaKeys::RESOLUTION_URL, ProcedureMetaKeys::ARCHIVED, ProcedureMetaKeys::CUSTOM_JS ) as $clave ) {
			$this->assertArrayNotHasKey( $clave, $meta, $clave );
		}
		$this->assertSame( array( 'ambito@example.org' ), $meta[ ProcedureMetaKeys::CONTACT_EMAILS ] );
		$this->assertSame( 'verde', $meta[ ProcedureMetaKeys::HEADER_COLOR ] );
		$this->assertSame( array( 'review_changed' ), $meta[ ProcedureMetaKeys::NOTIFY ] );
		$this->assertSame( '.prc { color: red; }', $meta[ ProcedureMetaKeys::CUSTOM_CSS ] );
		$this->assertTrue( $meta[ ProcedureMetaKeys::OBSERVABLE ] );
		$this->assertSame( array( 'centre', 'q1' ), $meta[ ProcedureMetaKeys::OBSERVER_COLUMNS ] );
		$this->assertSame( array( 'q1' ), array_column( $meta[ ProcedureMetaKeys::QUESTIONS ], 'key' ) );
		$this->assertSame( 1, $meta[ ProcedureMetaKeys::QUESTIONS_SEQ ] );
		$this->assertTrue( $sin_js['js_left'], 'había JavaScript y no se copió' );

		$con_js = ProcedureCopy::meta( $original, true );
		$this->assertSame( 'console.log(1);', $con_js['meta'][ ProcedureMetaKeys::CUSTOM_JS ] );
		$this->assertFalse( $con_js['js_left'] );

		$vacio = ProcedureCopy::meta( array(), false );
		$this->assertSame( array(), $vacio['meta'], 'lo que el original no tiene, el duplicado tampoco' );
		$this->assertFalse( $vacio['js_left'] );
	}

	/**
	 * El título lleva la marca de copia.
	 */
	public function test_the_title_says_it_is_a_copy() {
		$this->assertSame( 'Red de prueba (copia)', ProcedureCopy::title( '  Red de prueba ' ) );
		$this->assertSame( 'Procedimiento sin título (copia)', ProcedureCopy::title( '' ) );
		$this->assertSame( 'Red de prueba (copia)', ProcedureCopy::title( 'Red de prueba (copia)' ), 'la copia de una copia no se marca dos veces' );
	}

	// ─── antes de publicar ─────────────────────────────────────────────────

	/**
	 * Sin curso, o con una entrega sin fechas, el procedimiento no está listo
	 * para publicarse, y se dice por qué.
	 */
	public function test_not_ready_without_course_or_with_undated_deliveries() {
		$entrega = array(
			'key'       => 'q1',
			'label'     => 'Memoria final',
			'type'      => 'file',
			'required'  => false,
			'delivery'  => true,
			'opens_at'  => '',
			'closes_at' => '',
		);
		$this->assertSame( array(), ProcedureInput::not_ready( 3, array(), '2026-04-01' ) );

		$faltan = ProcedureInput::not_ready( 0, array( $entrega ), '2026-04-01' );
		$this->assertCount( 2, $faltan );
		$this->assertStringContainsString( 'curso', $faltan[0] );
		$this->assertStringContainsString( '«Memoria final»', $faltan[1] );

		$entrega['opens_at']  = '2026-05-01';
		$entrega['closes_at'] = '2026-05-31';
		$this->assertSame( array(), ProcedureInput::not_ready( 3, array( $entrega ), '2026-04-01' ) );
	}

	/**
	 * Sin plazo de solicitud no se publica: se quedaría en «Próximo» para
	 * siempre y nadie podría solicitar.
	 */
	public function test_not_ready_without_the_application_window() {
		$faltan = ProcedureInput::not_ready( 3, array(), '' );
		$this->assertSame( array( 'Falta el plazo de solicitud: ponga al menos su fecha de inicio en «Datos».' ), $faltan );
		$this->assertSame( array(), ProcedureInput::not_ready( 3, array(), '2026-04-01' ) );
		$this->assertCount( 1, ProcedureInput::not_ready( 3, array(), 'no es un día' ), 'un día que no existe no es un plazo' );
	}

	/**
	 * «Antes de publicar»: lo que bloquea y lo que solo avisa, en el orden en
	 * que se rellena el taller.
	 */
	public function test_the_checklist_before_publishing() {
		$lista = ProcedureInput::checklist(
			array(
				'course'     => 0,
				'questions'  => array(),
				'opens_at'   => '',
				'closes_at'  => '',
				'resolution' => '',
			)
		);
		$this->assertSame( array( 'course', 'window', 'questions', 'resolution' ), array_column( $lista, 'key' ) );
		$this->assertSame( array( false, false, true, false ), array_column( $lista, 'ok' ) );
		$this->assertSame( array( true, true, false, false ), array_column( $lista, 'blocks' ), 'la resolución avisa y no bloquea' );
		$this->assertSame( 'Falta el curso: elíjalo en «Datos».', $lista[0]['text'] );
		$this->assertStringContainsString( 'nadie podría solicitar', $lista[1]['text'] );
		$this->assertSame( 'Sin preguntas propias: la solicitud pedirá solo los datos de siempre.', $lista[2]['text'] );
		$this->assertStringContainsString( 'Sin enlace a la resolución', $lista[3]['text'] );
		$this->assertStringContainsString( 'puede publicar igual', $lista[3]['text'] );

		$entrega = array(
			'key'       => 'q2',
			'label'     => 'Memoria final',
			'type'      => 'file',
			'required'  => false,
			'delivery'  => true,
			'opens_at'  => '',
			'closes_at' => '',
		);
		$lista   = ProcedureInput::checklist(
			array(
				'course'     => 3,
				'questions'  => array(
					array(
						'key'   => 'q1',
						'label' => 'Modalidad',
						'type'  => 'text',
					),
					$entrega,
				),
				'opens_at'   => '2026-04-01',
				'closes_at'  => '2026-04-15',
				'resolution' => 'https://example.org/resolucion.pdf',
			)
		);
		$this->assertSame( array( 'course', 'window', 'questions', 'delivery', 'resolution' ), array_column( $lista, 'key' ) );
		$this->assertSame( 'Curso elegido.', $lista[0]['text'] );
		$this->assertSame( 'Plazo de solicitud: del 01-04-2026 al 15-04-2026.', $lista[1]['text'] );
		$this->assertSame( 'Preguntas propias: 2.', $lista[2]['text'] );
		$this->assertFalse( $lista[3]['ok'] );
		$this->assertTrue( $lista[3]['blocks'] );
		$this->assertStringContainsString( '«Memoria final»', $lista[3]['text'] );
		$this->assertSame( 'Resolución enlazada.', $lista[4]['text'] );
		$this->assertTrue( $lista[4]['ok'] );
	}
}
