<?php
/**
 * Tests for the «Personas» section of the application screen and its POST.
 *
 * @package Prc
 */

use Prc\Domain\ApplicationPeople;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PublicFront\ApplyForm;
use Prc\PublicFront\MyCentre;
use Prc\PublicFront\People;
use Prc\PublicFront\PeopleForm;

/**
 * La sección «Personas» de la solicitud (ADR-0041).
 *
 * Lo que se mira aquí: que se pinta solo cuando el procedimiento pide la
 * lista y el centro ya ha solicitado, que dice por qué no se puede cuando no
 * se puede, que la baja pide confirmación y que el POST —también uno armado a
 * mano— pasa por el mismo guardián que la pantalla.
 */
class Test_People_Form extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Con el aplicativo, sus páginas, un catálogo inventado y sin rechazos heredados.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
		$this->pages();
		remove_all_filters( 'prc_centres' );
		add_filter(
			'prc_centres',
			static function (): array {
				return array(
					array(
						'code'      => 'C0001',
						'name'      => 'Centro de prueba Norte',
						'ownership' => ProcedureMetaKeys::OWNERSHIP_PUBLIC,
					),
				);
			}
		);
		add_filter( 'pre_wp_mail', '__return_true' );
		foreach ( array(
			ApplyForm::class  => array( 'rejected' ),
			PeopleForm::class => array( 'rejected', 'rejected_row', 'stale', 'clash' ),
		) as $clase => $estaticas ) {
			\Closure::bind(
				static function () use ( $estaticas ): void {
					foreach ( $estaticas as $estatica ) {
						self::$$estatica = '';
					}
				},
				null,
				$clase
			)();
		}
	}

	/**
	 * Un día relativo a hoy, en Y-m-d.
	 *
	 * @param int $dias Days from today.
	 * @return string
	 */
	private function dia( int $dias ): string {
		return gmdate( 'Y-m-d', (int) strtotime( ProcedureState::today() . ' ' . sprintf( '%+d', $dias ) . ' days' ) );
	}

	/**
	 * Un procedimiento con el plazo de solicitud cerrado y la lista de personas abierta.
	 *
	 * @param array<string, mixed>|false $spec Specification overrides, or false for none.
	 * @return int Procedure ID.
	 */
	private function procedimiento( $spec = array() ): int {
		$meta = array(
			ProcedureMetaKeys::OPENS_AT  => $this->dia( -30 ),
			ProcedureMetaKeys::CLOSES_AT => $this->dia( -10 ),
		);
		if ( false !== $spec ) {
			$meta[ ProcedureMetaKeys::PEOPLE_SPEC ] = array_merge(
				array(
					'enabled'   => true,
					'roles'     => array(
						array(
							'key'   => 'r1',
							'label' => 'Coordinación',
						),
						array(
							'key'         => 'r2',
							'label'       => 'Participante',
							'needs_group' => true,
						),
					),
					'groups'    => array(
						array(
							'key'   => 'g1',
							'label' => 'Eje 1',
						),
					),
					'min'       => 2,
					'max'       => 2,
					'opens_at'  => $this->dia( -1 ),
					'closes_at' => $this->dia( 1 ),
				),
				$spec
			);
		}
		return $this->procedure( $this->administrator(), array( $this->area( 'Área de prueba' ) ), $meta );
	}

	/**
	 * La pantalla de la solicitud pintada para quien está dentro.
	 *
	 * @param int $procedure_id Procedure ID.
	 * @return string
	 */
	private function pintar( int $procedure_id ): string {
		$_GET[ ApplyForm::ARG_PROCEDURE ] = (string) $procedure_id;
		return ApplyForm::render();
	}

	/**
	 * Un envío de la sección, y por dónde salió.
	 *
	 * @param array<string, mixed> $campos Fields.
	 * @param bool                 $nonce  Whether it carries the good nonce.
	 * @return string|null
	 */
	private function enviar( array $campos, bool $nonce = true ): ?string {
		if ( $nonce ) {
			$this->post( $campos, PeopleForm::nonce_action( (string) $campos[ PeopleForm::FIELD_OP ], (int) $campos[ PeopleForm::FIELD_APPLICATION ] ), PeopleForm::NONCE_FIELD );
		} else {
			$this->post( $campos + array( PeopleForm::NONCE_FIELD => 'falso' ) );
		}
		return $this->exit_url( array( PeopleForm::class, 'handle' ) );
	}

	/**
	 * Una persona válida, como la manda el formulario.
	 *
	 * @param array<string, string> $extra Overrides.
	 * @return array<string, string>
	 */
	private function persona( array $extra = array() ): array {
		return array_merge(
			array(
				'name'    => 'Ana',
				'surname' => 'Pérez',
				'dni'     => '12345678Z',
				'email'   => 'ana@example.org',
				'phone'   => '',
				'role'    => 'r2',
				'group'   => 'g1',
			),
			$extra
		);
	}

	/**
	 * Con la lista abierta y el plazo de solicitud cerrado: la solicitud ya
	 * no se edita, y la sección sí, con su alta, su cambio y su baja con
	 * confirmación.
	 */
	public function test_the_section_is_open_while_the_application_is_closed() {
		$procedimiento = $this->procedimiento();
		$director      = $this->school_head( 'C0001' );
		$solicitud     = $this->application( $procedimiento, $director );
		People::add( $solicitud, $director, $this->persona() );
		$this->acting_as( $director );

		$html = $this->pintar( $procedimiento );
		$this->assertStringContainsString( 'Las solicitudes están cerradas.', $html, 'la solicitud, cerrada' );
		$this->assertStringContainsString( 'id="' . PeopleForm::ANCHOR . '"', $html );
		$this->assertStringContainsString( 'PERSONAS QUE PARTICIPAN', $html );
		$this->assertStringContainsString( 'Dar de alta a una persona', $html );
		$this->assertStringContainsString( 'Sustituir o corregir', $html );
		$this->assertStringContainsString( 'data-prc-confirm="¿Dar de baja a Ana Pérez de la lista de personas?', $html, 'la baja pide confirmación' );
		$this->assertStringContainsString( 'Participante', $html, 'el papel, por su rótulo' );
		$this->assertStringContainsString( 'queda incompleta', $html, 'una de dos: incompleta, no rechazada' );
	}

	/**
	 * La sección dice siempre, la primera y en negrita, hasta qué día se
	 * cambian las personas: es lo que se pregunta después de presentar.
	 */
	public function test_the_section_says_until_when_people_can_be_changed() {
		$procedimiento = $this->procedimiento();
		$director      = $this->school_head( 'C0001' );
		$this->application( $procedimiento, $director );
		$this->acting_as( $director );

		$html = $this->pintar( $procedimiento );
		$dia  = implode( '-', array_reverse( explode( '-', $this->dia( 1 ) ) ) );
		$this->assertStringContainsString( '<strong>Puede dar de alta, cambiar o sustituir personas hasta el ' . $dia . ', incluido.</strong>', $html );

		// Sin fechas propias, el cierre del plazo de solicitud.
		$sin_fechas = $this->procedure(
			$this->administrator(),
			array( $this->area( 'Área de prueba' ) ),
			array(
				ProcedureMetaKeys::OPENS_AT    => $this->dia( -1 ),
				ProcedureMetaKeys::CLOSES_AT   => $this->dia( 3 ),
				ProcedureMetaKeys::PEOPLE_SPEC => array(
					'enabled' => true,
					'roles'   => array(
						array(
							'key'   => 'r1',
							'label' => 'Coordinación',
						),
					),
					'min'     => 1,
					'max'     => 2,
				),
			)
		);
		$this->acting_as( $director );
		$html = $this->pintar( $sin_fechas );
		$this->assertStringContainsString( 'hasta el ' . implode( '-', array_reverse( explode( '-', $this->dia( 3 ) ) ) ) . ', incluido.', $html, 'antes de presentar también' );
	}

	/**
	 * Sin especificación no hay sección, y la pantalla es la de siempre.
	 */
	public function test_without_spec_there_is_no_section() {
		$procedimiento = $this->procedimiento( false );
		$director      = $this->school_head( 'C0001' );
		$this->application( $procedimiento, $director );
		$this->acting_as( $director );
		$html = $this->pintar( $procedimiento );
		$this->assertStringNotContainsString( PeopleForm::ANCHOR, $html );
		$this->assertStringNotContainsString( 'PERSONAS QUE PARTICIPAN', $html );
	}

	/**
	 * Antes de solicitar se dice que la lista viene después; fuera de plazo,
	 * por qué no; y en los dos casos sin formularios.
	 */
	public function test_it_says_why_it_cannot() {
		$director = $this->school_head( 'C0001' );
		$this->acting_as( $director );
		$html = $this->pintar( $this->procedimiento() );
		$this->assertStringContainsString( 'Cuando presente la solicitud', $html );
		$this->assertStringNotContainsString( 'Dar de alta a una persona', $html );

		$cerrada = $this->procedimiento(
			array(
				'opens_at'  => $this->dia( -5 ),
				'closes_at' => $this->dia( -2 ),
			)
		);
		$this->application( $cerrada, $director );
		$html = $this->pintar( $cerrada );
		$this->assertStringContainsString( 'El plazo de la lista de personas terminó el', $html );
		$this->assertStringNotContainsString( 'Dar de alta a una persona', $html );
	}

	/**
	 * Alta por POST: vuelve a la sección con el aviso; el tope se dice.
	 */
	public function test_adding_by_post_comes_back_with_the_notice() {
		$procedimiento = $this->procedimiento();
		$director      = $this->school_head( 'C0001' );
		$solicitud     = $this->application( $procedimiento, $director );
		$this->acting_as( $director );

		$url = $this->enviar(
			array(
				PeopleForm::FIELD_OP          => PeopleForm::OP_ADD,
				PeopleForm::FIELD_APPLICATION => (string) $solicitud,
				PeopleForm::FIELD_PERSON      => $this->persona(),
			)
		);
		$this->assertNotNull( $url );
		$this->assertStringEndsWith( '#' . PeopleForm::ANCHOR, (string) $url );
		$this->assertSame( 'persona-alta', $this->query_arg( strtok( (string) $url, '#' ), ApplyForm::ARG_NOTICE ) );
		$this->assertCount( 1, People::of( $solicitud ) );
	}

	/**
	 * Fuera de la ventana, un POST armado a mano no escribe; sin el nonce
	 * bueno, tampoco.
	 */
	public function test_a_direct_post_out_of_the_window_or_without_nonce_writes_nothing() {
		$director  = $this->school_head( 'C0001' );
		$cerrada   = $this->procedimiento(
			array(
				'opens_at'  => $this->dia( 1 ),
				'closes_at' => $this->dia( 5 ),
			)
		);
		$solicitud = $this->application( $cerrada, $director );
		$this->acting_as( $director );

		$campos = array(
			PeopleForm::FIELD_OP          => PeopleForm::OP_ADD,
			PeopleForm::FIELD_APPLICATION => (string) $solicitud,
			PeopleForm::FIELD_PERSON      => $this->persona(),
		);
		$this->assertNull( $this->enviar( $campos ) );
		$this->assertSame( array(), People::of( $solicitud ) );
		$html = $this->pintar( $cerrada );
		$this->assertStringContainsString( 'La lista de personas se abre el', $html );

		$abierta = $this->application( $this->procedimiento(), $director );
		$this->assertNull( $this->enviar( array( PeopleForm::FIELD_APPLICATION => (string) $abierta ) + $campos, false ) );
		$this->assertSame( array(), People::of( $abierta ) );
	}

	/**
	 * Un cambio con una huella vieja se rechaza y la pantalla lo dice, con
	 * lo tecleado en su formulario.
	 */
	public function test_a_stale_change_is_refused_on_screen() {
		$procedimiento = $this->procedimiento();
		$director      = $this->school_head( 'C0001' );
		$solicitud     = $this->application( $procedimiento, $director );
		People::add( $solicitud, $director, $this->persona() );
		$fila = People::of( $solicitud )[0];
		People::change( $solicitud, $director, $fila['id'], ApplicationPeople::fingerprint( $fila ), $this->persona( array( 'phone' => '600000000' ) ) );
		$this->acting_as( $director );

		$this->assertNull(
			$this->enviar(
				array(
					PeopleForm::FIELD_OP          => PeopleForm::OP_CHANGE,
					PeopleForm::FIELD_APPLICATION => (string) $solicitud,
					PeopleForm::FIELD_ROW         => $fila['id'],
					PeopleForm::FIELD_PRINT       => ApplicationPeople::fingerprint( $fila ),
					PeopleForm::FIELD_PERSON      => $this->persona( array( 'name' => 'Ana Tecleada' ) ),
				)
			)
		);
		$html  = $this->pintar( $procedimiento );
		$ahora = People::of( $solicitud )[0];
		$this->assertStringContainsString( 'ha cambiado esta persona', $html );
		$this->assertStringNotContainsString( 'value="Ana Tecleada"', $html, 'se repinta lo guardado ahora, no lo tecleado' );
		$this->assertStringContainsString( 'value="600000000"', $html );
		$this->assertStringContainsString( 'value="' . ApplicationPeople::fingerprint( $ahora ) . '"', $html, 'con la huella nueva' );
		$this->assertStringContainsString( '<details class="prc-personas__cambio" open>', $html, 'el formulario de esa fila, abierto' );
		$this->assertSame( '600000000', $ahora['phone'] );
	}

	/**
	 * El nonce es de la operación y de la solicitud: el de otra no sirve, y un
	 * centro no escribe en la lista de otro aunque mande su número.
	 */
	public function test_another_school_or_another_nonce_writes_nothing() {
		$procedimiento = $this->procedimiento();
		$director      = $this->school_head( 'C0001' );
		$suya          = $this->application( $procedimiento, $director );
		$intruso       = $this->school_head( 'C0002' );
		$propia        = $this->application( $procedimiento, $intruso, 'C0002' );

		$this->acting_as( $intruso );
		$campos = array(
			PeopleForm::FIELD_OP          => PeopleForm::OP_ADD,
			PeopleForm::FIELD_APPLICATION => (string) $suya,
			PeopleForm::FIELD_PERSON      => $this->persona(),
		);
		$this->assertNull( $this->enviar( $campos ), 'otro centro, con su nonce bueno' );
		$this->assertSame( array(), People::of( $suya ) );

		// El nonce de su propia solicitud, con el número de la otra.
		$this->post( $campos, PeopleForm::nonce_action( PeopleForm::OP_ADD, $propia ), PeopleForm::NONCE_FIELD );
		$this->assertNull( $this->exit_url( array( PeopleForm::class, 'handle' ) ) );
		$this->assertSame( array(), People::of( $suya ) );
	}

	/**
	 * Guardar una persona sin cambiar nada vuelve con un aviso neutro.
	 */
	public function test_a_change_without_differences_says_so() {
		$procedimiento = $this->procedimiento();
		$director      = $this->school_head( 'C0001' );
		$solicitud     = $this->application( $procedimiento, $director );
		People::add( $solicitud, $director, $this->persona() );
		$fila = People::of( $solicitud )[0];
		$this->acting_as( $director );
		$url = $this->enviar(
			array(
				PeopleForm::FIELD_OP          => PeopleForm::OP_CHANGE,
				PeopleForm::FIELD_APPLICATION => (string) $solicitud,
				PeopleForm::FIELD_ROW         => $fila['id'],
				PeopleForm::FIELD_PRINT       => ApplicationPeople::fingerprint( $fila ),
				PeopleForm::FIELD_PERSON      => $this->persona(),
			)
		);
		$this->assertSame( 'persona-sin-cambios', $this->query_arg( strtok( (string) $url, '#' ), ApplyForm::ARG_NOTICE ) );
	}

	/**
	 * «Mi centro» enlaza con la sección cuando el procedimiento pide la lista.
	 */
	public function test_my_centre_links_to_the_section() {
		$con       = $this->procedimiento();
		$sin       = $this->procedimiento( false );
		$director  = $this->school_head( 'C0001' );
		$solicitud = $this->application( $con, $director );
		$this->application( $sin, $director );
		People::add( $solicitud, $director, $this->persona() );
		$this->acting_as( $director );

		$filas = array_column( MyCentre::model()['rows'], null, 'procedure_id' );
		$this->assertStringEndsWith( '#' . PeopleForm::ANCHOR, (string) $filas[ (string) $con ]['people_url'] );
		$this->assertSame( 1, $filas[ (string) $con ]['people_count'] );
		$this->assertSame( '', $filas[ (string) $sin ]['people_url'] );
		$this->assertStringContainsString( 'Falta 1 persona', MyCentre::render() );
	}

	/**
	 * Cada fila tiene «Sustituir o corregir» a la vista, con su ancla, y
	 * encima del alta se recuerda que sustituir es editar la fila.
	 */
	public function test_each_row_offers_to_replace_it() {
		$procedimiento = $this->procedimiento();
		$director      = $this->school_head( 'C0001' );
		$solicitud     = $this->application( $procedimiento, $director );
		People::add( $solicitud, $director, $this->persona() );
		$fila = People::of( $solicitud )[0];
		$this->acting_as( $director );

		$html = $this->pintar( $procedimiento );
		$this->assertStringContainsString( '<tr id="prc-persona-' . $fila['id'] . '">', $html );
		$this->assertMatchesRegularExpression( '/<summary class="[^"]*btn[^"]*">Sustituir o corregir<\/summary>/', $html );
		$this->assertStringContainsString( '¿Viene a sustituir a alguien de la lista? Use «Sustituir o corregir» en su fila', $html );
		$this->assertStringContainsString( 'Grupo (obligatorio para: Participante)', $html );
	}

	/**
	 * Dar de alta a quien ya está, o en un papel ocupado, lleva a su fila y
	 * la abre.
	 */
	public function test_a_clash_takes_to_the_row() {
		$procedimiento = $this->procedimiento();
		$director      = $this->school_head( 'C0001' );
		$solicitud     = $this->application( $procedimiento, $director );
		People::add( $solicitud, $director, $this->persona() );
		$fila = People::of( $solicitud )[0];
		$this->acting_as( $director );

		$url = $this->enviar(
			array(
				PeopleForm::FIELD_OP          => PeopleForm::OP_ADD,
				PeopleForm::FIELD_APPLICATION => (string) $solicitud,
				PeopleForm::FIELD_PERSON      => $this->persona(
					array(
						'role'  => 'r1',
						'group' => '',
					)
				),
			)
		);
		$this->assertNull( $url );
		$html = $this->pintar( $procedimiento );
		$this->assertStringContainsString( 'ya está en la lista como «Participante · Eje 1»', $html );
		$this->assertStringContainsString( 'href="#prc-persona-' . $fila['id'] . '">Ir a su fila</a>', $html );
		$this->assertMatchesRegularExpression( '/<tr id="prc-persona-' . $fila['id'] . '">.*<details class="prc-personas__cambio" open>/sU', $html );
		$this->assertCount( 1, People::of( $solicitud ) );
	}

	/**
	 * Rechazado por otro motivo —aquí, un papel que exige grupo y llega sin
	 * él—, no se lleva a la fila de quien tiene el mismo DNI: el aviso no
	 * habla de ella.
	 */
	public function test_a_refusal_that_is_not_a_clash_does_not_take_to_a_row() {
		$procedimiento = $this->procedimiento();
		$director      = $this->school_head( 'C0001' );
		$solicitud     = $this->application( $procedimiento, $director );
		People::add( $solicitud, $director, $this->persona() );
		$fila = People::of( $solicitud )[0];
		$this->acting_as( $director );

		$url = $this->enviar(
			array(
				PeopleForm::FIELD_OP          => PeopleForm::OP_ADD,
				PeopleForm::FIELD_APPLICATION => (string) $solicitud,
				PeopleForm::FIELD_PERSON      => $this->persona( array( 'group' => '' ) ),
			)
		);
		$this->assertNull( $url );
		$html = $this->pintar( $procedimiento );
		$this->assertStringContainsString( 'el grupo, que el papel «Participante» exige', $html );
		$this->assertStringNotContainsString( 'Ir a su fila', $html );
		$this->assertDoesNotMatchRegularExpression( '/<tr id="prc-persona-' . $fila['id'] . '">.*<details class="prc-personas__cambio" open>/sU', $html );
	}
}
