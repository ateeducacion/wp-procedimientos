<?php
/**
 * Tests for Applications: storage, one per school and procedure, review and rows.
 *
 * @package Prc
 */

use Prc\Domain\ApplicationInput;
use Prc\Domain\ProcedureQuestions;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ApplicationPostType;
use Prc\PublicFront\Applications;

/**
 * La solicitud de un centro, de punta a punta.
 *
 * Tres cosas que se comprueban aquí y no en otra parte:
 *
 * 1. Hay **una** solicitud por centro y procedimiento (ADR-0018): volver a
 *    solicitar es editar la que hay, nunca crear otra.
 * 2. El centro **viene de la persona** (ADR-0016) y su nombre es una foto del
 *    catálogo (ADR-0017): nada de eso se teclea.
 * 3. Una respuesta sigue unida a su pregunta por su `key` (ADR-0019): se
 *    reescribe el rótulo y no se descoloca; se borra la pregunta y no se pierde.
 */
class Test_Applications extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Con el aplicativo arrancado y un catálogo de centros inventado.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
		remove_all_filters( 'prc_centres' );
		add_filter( 'prc_centres', array( $this, 'catalogo' ) );
	}

	/**
	 * Dos centros de prueba, uno de cada titularidad.
	 *
	 * @return array<int, array<string, string>>
	 */
	public function catalogo(): array {
		return array(
			array(
				'code'      => 'C0001',
				'name'      => 'Centro de prueba Norte',
				'ownership' => 'public',
			),
			array(
				'code'      => 'C0002',
				'name'      => 'Centro de prueba Sur',
				'ownership' => 'private',
			),
		);
	}

	/**
	 * Un procedimiento publicado, con su ámbito.
	 *
	 * @param array<string, mixed> $meta Meta key => value.
	 * @return int
	 */
	private function procedimiento( array $meta = array() ): int {
		return $this->procedure(
			$this->administrator(),
			array( $this->area( 'Área de prueba' ) ),
			$meta,
			array( 'post_title' => 'Red de prueba' )
		);
	}

	/**
	 * Un núcleo válido, como lo devuelve ApplicationInput::validate() en `data`.
	 *
	 * @param array<string, mixed> $cambios What to override.
	 * @return array<string, mixed>
	 */
	private function nucleo( array $cambios = array() ): array {
		return array_merge(
			array(
				'position'    => ApplicationMetaKeys::POSITION_HEAD,
				'coordinator' => array(
					'name'  => '',
					'email' => '',
				),
				'accept'      => true,
				'answers'     => array(),
			),
			$cambios
		);
	}

	// ─── una por centro y procedimiento ────────────────────────────────────

	/**
	 * La primera vez se crea; la segunda, se edita la misma.
	 */
	public function test_a_school_applies_once_and_the_second_time_edits() {
		$procedimiento = $this->procedimiento();
		$direccion     = $this->school_head( 'C0001' );

		$primera = Applications::save( $procedimiento, $direccion, $this->nucleo() );
		$segunda = Applications::save( $procedimiento, $direccion, $this->nucleo( array( 'position' => ApplicationMetaKeys::POSITION_SECRETARY ) ) );

		$this->assertGreaterThan( 0, $primera );
		$this->assertSame( $primera, $segunda, 'la segunda solicitud del mismo centro es la primera' );
		$this->assertSame( 1, Applications::count( $procedimiento ) );
		$this->assertSame( $primera, Applications::find( $procedimiento, 'C0001' ) );

		$this->assertSame( $procedimiento, (int) get_post_field( 'post_parent', $primera ), 'cuelga de su procedimiento' );
		$this->assertSame( $direccion, (int) get_post_field( 'post_author', $primera ), 'quien solicita es post_author' );
		$this->assertSame( 'Centro de prueba Norte — Red de prueba', get_the_title( $primera ) );

		$meta = Applications::meta( $primera );
		$this->assertSame( 'C0001', $meta[ ApplicationMetaKeys::CENTRE_CODE ] );
		$this->assertSame( 'Centro de prueba Norte', $meta[ ApplicationMetaKeys::CENTRE_NAME ], 'el nombre es una foto del catálogo' );
		$this->assertSame( ApplicationMetaKeys::POSITION_SECRETARY, $meta[ ApplicationMetaKeys::APPLICANT_POSITION ], 'y lo editado se guarda' );
		$this->assertSame( ApplicationMetaKeys::REVIEW_SUBMITTED, $meta[ ApplicationMetaKeys::REVIEW_STATE ] );
	}

	/**
	 * La unicidad es por centro Y por procedimiento: otro centro u otro
	 * procedimiento son otra solicitud.
	 */
	public function test_find_is_per_procedure_and_per_school() {
		$uno   = $this->procedimiento();
		$otro  = $this->procedimiento();
		$norte = $this->school_head( 'C0001' );
		$sur   = $this->school_head( 'C0002' );

		$a = Applications::save( $uno, $norte, $this->nucleo() );
		$b = Applications::save( $uno, $sur, $this->nucleo() );
		$c = Applications::save( $otro, $norte, $this->nucleo() );

		$this->assertCount( 3, array_unique( array( $a, $b, $c ) ) );
		$this->assertSame( $a, Applications::find( $uno, 'C0001' ) );
		$this->assertSame( $b, Applications::find( $uno, 'C0002' ) );
		$this->assertSame( $c, Applications::find( $otro, 'C0001' ) );
		$this->assertNull( Applications::find( $otro, 'C0002' ) );
		$this->assertNull( Applications::find( $uno, '' ) );

		$this->assertEqualsCanonicalizing( array( $a, $b ), wp_list_pluck( Applications::for_procedure( $uno ), 'ID' ) );
		$this->assertEqualsCanonicalizing( array( $a, $c ), wp_list_pluck( Applications::for_centre( 'C0001' ), 'ID' ) );
		$this->assertSame( array(), Applications::for_centre( '' ) );
	}

	/**
	 * Si dos solicitudes del mismo centro llegaran a existir, manda la más
	 * antigua: la que ya estaba, no la que decida la base de datos.
	 */
	public function test_find_returns_the_oldest_when_two_exist() {
		$procedimiento = $this->procedimiento();
		$direccion     = $this->school_head( 'C0001' );

		$nueva   = $this->application( $procedimiento, $direccion, 'C0001' );
		$antigua = $this->application( $procedimiento, $direccion, 'C0001' );
		// La segunda creada se fecha antes: así el ID y la fecha no dicen lo
		// mismo y se ve de verdad por cuál de las dos se ordena.
		wp_update_post(
			array(
				'ID'            => $nueva,
				'post_date'     => '2026-02-02 10:00:00',
				'post_date_gmt' => '2026-02-02 10:00:00',
			)
		);
		wp_update_post(
			array(
				'ID'            => $antigua,
				'post_date'     => '2026-01-01 10:00:00',
				'post_date_gmt' => '2026-01-01 10:00:00',
			)
		);

		$this->assertSame( $antigua, Applications::find( $procedimiento, 'C0001' ) );
	}

	// ─── el centro ─────────────────────────────────────────────────────────

	/**
	 * Sin código de centro en la cuenta no se guarda nada; con un código que
	 * el catálogo no conoce se guarda igual, con el nombre en blanco.
	 */
	public function test_the_school_comes_from_the_person_and_the_catalogue_may_lag() {
		$procedimiento = $this->procedimiento();

		$sin_centro = $this->school_head();
		$this->assertSame( 0, Applications::save( $procedimiento, $sin_centro, $this->nucleo() ) );
		$this->assertSame( 0, Applications::count( $procedimiento ) );

		$desconocido = $this->school_head( 'C9999' );
		$id          = Applications::save( $procedimiento, $desconocido, $this->nucleo() );
		$this->assertGreaterThan( 0, $id, 'el catálogo puede ir por detrás de la realidad' );
		$this->assertSame( 'C9999', get_post_meta( $id, ApplicationMetaKeys::CENTRE_CODE, true ) );
		$this->assertSame( '', get_post_meta( $id, ApplicationMetaKeys::CENTRE_NAME, true ) );
		$this->assertStringStartsWith( 'C9999 — ', get_the_title( $id ), 'sin nombre, el título lleva el código' );

		$centro = Applications::centre_of( $desconocido );
		$this->assertSame( 'C9999', $centro['code'] );
		$this->assertSame( '', $centro['name'] );
		$this->assertSame( '', Applications::centre_of( $sin_centro )['code'] );
	}

	/**
	 * Lo que no es un procedimiento no recibe solicitudes.
	 */
	public function test_only_a_procedure_takes_applications() {
		$pagina = (int) self::factory()->post->create( array( 'post_type' => 'page' ) );
		$this->assertSame( 0, Applications::save( $pagina, $this->school_head( 'C0001' ), $this->nucleo() ) );
		$this->assertSame( 0, Applications::save( 0, $this->school_head( 'C0001' ), $this->nucleo() ) );
	}

	// ─── la revisión ───────────────────────────────────────────────────────

	/**
	 * Pedir subsanar y excluir piden nota; admitir, no; y el estado es de la lista.
	 */
	public function test_review_demands_a_note_to_amend_or_exclude() {
		$procedimiento = $this->procedimiento();
		$id            = Applications::save( $procedimiento, $this->school_head( 'C0001' ), $this->nucleo() );

		$sin_nota = Applications::review( $id, ApplicationMetaKeys::REVIEW_AMEND, '  ' );
		$this->assertFalse( $sin_nota['ok'] );
		$this->assertSame( 'note', $sin_nota['error'] );
		$this->assertSame( ApplicationMetaKeys::REVIEW_SUBMITTED, get_post_meta( $id, ApplicationMetaKeys::REVIEW_STATE, true ), 'y no se escribe nada' );

		$this->assertSame( 'note', Applications::review( $id, ApplicationMetaKeys::REVIEW_EXCLUDED, '' )['error'] );
		$this->assertSame( 'state', Applications::review( $id, 'inventado', 'da igual' )['error'] );
		$this->assertSame( 'application', Applications::review( $procedimiento, ApplicationMetaKeys::REVIEW_ADMITTED, '' )['error'], 'un procedimiento no se revisa' );

		$this->assertTrue( Applications::review( $id, ApplicationMetaKeys::REVIEW_AMEND, 'Falta la persona coordinadora.' )['ok'] );
		$this->assertSame( ApplicationMetaKeys::REVIEW_AMEND, get_post_meta( $id, ApplicationMetaKeys::REVIEW_STATE, true ) );
		$this->assertSame( 'Falta la persona coordinadora.', get_post_meta( $id, ApplicationMetaKeys::REVIEW_NOTE, true ) );

		$this->assertTrue( Applications::review( $id, ApplicationMetaKeys::REVIEW_ADMITTED, '' )['ok'], 'admitir no pide nota' );
		$this->assertSame( ApplicationMetaKeys::REVIEW_ADMITTED, get_post_meta( $id, ApplicationMetaKeys::REVIEW_STATE, true ) );
	}

	/**
	 * Una solicitud editada vuelve a «presentada» y conserva la nota: quien
	 * gestiona tiene que volver a mirarla, y el centro sigue leyendo qué se le pidió.
	 */
	public function test_editing_puts_the_application_back_to_submitted_and_keeps_the_note() {
		$procedimiento = $this->procedimiento();
		$direccion     = $this->school_head( 'C0001' );
		$id            = Applications::save( $procedimiento, $direccion, $this->nucleo() );
		Applications::review( $id, ApplicationMetaKeys::REVIEW_AMEND, 'Falta el correo.' );

		Applications::save( $procedimiento, $direccion, $this->nucleo() );

		$this->assertSame( ApplicationMetaKeys::REVIEW_SUBMITTED, get_post_meta( $id, ApplicationMetaKeys::REVIEW_STATE, true ) );
		$this->assertSame( 'Falta el correo.', get_post_meta( $id, ApplicationMetaKeys::REVIEW_NOTE, true ) );
	}

	// ─── las respuestas y la tabla ─────────────────────────────────────────

	/**
	 * Lo que pide el procedimiento tiene la forma que ApplicationInput espera.
	 */
	public function test_spec_is_what_application_input_wants() {
		$procedimiento = $this->procedimiento(
			array(
				ProcedureMetaKeys::REQUIRES_COORDINATOR => true,
				ProcedureMetaKeys::COMMITMENTS          => 'El centro se compromete.',
				ProcedureMetaKeys::QUESTIONS            => array(
					array(
						'label'    => 'Modalidad',
						'type'     => ProcedureQuestions::TYPE_SINGLE,
						'choices'  => array( 'A', 'B' ),
						'required' => true,
					),
				),
			)
		);

		$spec = Applications::spec( $procedimiento );
		$this->assertTrue( $spec['requires_coordinator'] );
		$this->assertSame( 'El centro se compromete.', $spec['commitments'] );
		$this->assertSame( 'q1', $spec['questions'][0]['key'], 'la clave la genera el aplicativo al guardar' );

		$v = ApplicationInput::validate(
			array(
				'position'          => ApplicationMetaKeys::POSITION_HEAD,
				'coordinator_name'  => 'Ana Pérez',
				'coordinator_email' => 'ana@example.org',
				'accept'            => '1',
				'answers'           => array( 'q1' => 'B' ),
			),
			$spec
		);
		$this->assertTrue( $v['ok'], implode( ', ', $v['errors'] ) );
		$this->assertSame( 'B', $v['data']['answers']['q1'] );
	}

	/**
	 * Cada fila lleva sus respuestas bajo la clave de la pregunta: reescribir
	 * el rótulo mueve la cabecera y no el dato; borrar la pregunta la quita de
	 * la tabla y deja la respuesta guardada.
	 */
	public function test_rows_carry_the_answers_by_question_key() {
		$procedimiento = $this->procedimiento(
			array(
				ProcedureMetaKeys::QUESTIONS => array(
					array(
						'label'   => 'Modalidad',
						'type'    => ProcedureQuestions::TYPE_SINGLE,
						'choices' => array( 'A', 'B' ),
					),
					array(
						'label' => 'Tiene huerto',
						'type'  => ProcedureQuestions::TYPE_YESNO,
					),
					array(
						'label'   => 'Etapas',
						'type'    => ProcedureQuestions::TYPE_MULTIPLE,
						'choices' => array( 'Infantil', 'Primaria' ),
					),
				),
			)
		);
		$id            = Applications::save(
			$procedimiento,
			$this->school_head( 'C0001' ),
			$this->nucleo(
				array(
					'coordinator' => array(
						'name'  => 'Ana Pérez',
						'email' => 'ana@example.org',
					),
					'answers'     => array(
						'q1' => 'B',
						'q2' => true,
						'q3' => array( 'Infantil', 'Primaria' ),
					),
				)
			)
		);

		$columnas = Applications::columns( Applications::questions( $procedimiento ) );
		$this->assertSame( 'Centro', reset( $columnas ) );
		$this->assertSame( 'Modalidad', $columnas['q1'] );
		$this->assertSame( 'Etapas', $columnas['q3'] );

		$filas = Applications::rows( $procedimiento );
		$this->assertCount( 1, $filas );
		$fila = $filas[0];
		$this->assertSame( (string) $id, $fila['id'] );
		$this->assertSame( 'Centro de prueba Norte', $fila['centre'] );
		$this->assertSame( 'C0001', $fila['code'] );
		$this->assertSame( 'Dirección', $fila['position'] );
		$this->assertSame( 'Ana Pérez', $fila['coordinator'] );
		$this->assertSame( 'Presentada', $fila['state'] );
		$this->assertSame( ApplicationMetaKeys::REVIEW_SUBMITTED, $fila['state_key'] );
		$this->assertSame( 'B', $fila['q1'] );
		$this->assertSame( 'Sí', $fila['q2'] );
		$this->assertSame( 'Infantil, Primaria', $fila['q3'] );

		// Se reescribe un rótulo y se borra una pregunta.
		$preguntas             = Applications::questions( $procedimiento );
		$preguntas[0]['label'] = 'Modalidad elegida';
		unset( $preguntas[2] );
		update_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS, array_values( $preguntas ) );

		$columnas = Applications::columns( Applications::questions( $procedimiento ) );
		$this->assertSame( 'Modalidad elegida', $columnas['q1'] );
		$this->assertArrayNotHasKey( 'q3', $columnas, 'la pregunta borrada deja de pintarse' );

		$fila = Applications::rows( $procedimiento )[0];
		$this->assertSame( 'B', $fila['q1'], 'la respuesta sigue bajo su clave' );
		$this->assertArrayNotHasKey( 'q3', $fila );
		$this->assertSame(
			array( 'Infantil', 'Primaria' ),
			get_post_meta( $id, ApplicationMetaKeys::ANSWERS, true )['q3'],
			'y la respuesta a la pregunta borrada se conserva en la meta'
		);
	}

	/**
	 * La solicitud no se enseña fuera del aplicativo.
	 */
	public function test_an_application_opens_no_door() {
		$tipo = get_post_type_object( ApplicationPostType::POST_TYPE );

		$this->assertFalse( $tipo->public, 'sin URL propia' );
		$this->assertFalse( $tipo->show_in_rest, 'sin REST' );
		$this->assertTrue( $tipo->exclude_from_search, 'fuera de la búsqueda' );
	}

	/**
	 * Quién presentó cada solicitud se carga de una vez, no una consulta por fila.
	 */
	public function test_rows_do_not_query_once_per_applicant() {
		$consultas = function ( int $cuantas ): int {
			$procedimiento = $this->procedure( $this->administrator(), array( $this->area() ) );
			for ( $i = 0; $i < $cuantas; $i++ ) {
				$this->application( $procedimiento, $this->school_head( 'C000' . $i ) );
			}
			wp_cache_flush();

			global $wpdb;
			$antes = $wpdb->num_queries;
			$this->assertCount( $cuantas, Applications::rows( $procedimiento ) );
			return $wpdb->num_queries - $antes;
		};

		$this->assertSame( $consultas( 1 ), $consultas( 4 ) );
	}
}
