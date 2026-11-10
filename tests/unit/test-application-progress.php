<?php
/**
 * Tests for how far an application has got: its steps, whether it is
 * complete, the notice after submitting and its printable receipt.
 *
 * @package Prc
 */

use Prc\Domain\ProcedureQuestions;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PublicFront\ApplicationProgress;
use Prc\PublicFront\ApplyForm;
use Prc\PublicFront\People;
use Prc\PublicFront\PeopleForm;

/**
 * La solicitud se termina y se demuestra.
 *
 * Lo que se mira aquí es lo que contesta a «¿ha quedado presentada?» y a «¿me
 * falta algo?»: los pasos con «Hecho» o «Falta», las personas antes de las
 * entregas, el aviso de vuelta que dice lo que falta, la línea «Completa» o
 * «Incompleta» de la tarjeta y el justificante que se imprime sin JavaScript.
 */
class Test_Application_Progress extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Con el aplicativo, sus páginas, un catálogo inventado, el correo
	 * apagado y sin rechazos heredados.
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
		\Closure::bind(
			static function (): void {
				self::$rejected = '';
			},
			null,
			ApplyForm::class
		)();
		unset( $_GET[ ApplyForm::ARG_NOTICE ], $_GET[ ApplicationProgress::ARG_RECEIPT ] );
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
	 * Un procedimiento abierto, con lista de personas y una entrega obligatoria, o sin nada.
	 *
	 * @param bool $con_todo Whether it has the list and the delivery.
	 * @return int
	 */
	private function procedimiento( bool $con_todo = true ): int {
		$meta = array(
			ProcedureMetaKeys::OPENS_AT  => $this->dia( -1 ),
			ProcedureMetaKeys::CLOSES_AT => $this->dia( 5 ),
		);
		if ( $con_todo ) {
			$meta[ ProcedureMetaKeys::PEOPLE_SPEC ] = array(
				'enabled' => true,
				'roles'   => array(
					array(
						'key'   => 'r1',
						'label' => 'Coordinación',
					),
				),
				'min'     => 2,
				'max'     => 4,
			);
			$meta[ ProcedureMetaKeys::QUESTIONS ]   = array(
				array(
					'label'     => 'Memoria final',
					'type'      => ProcedureQuestions::TYPE_FILE,
					'delivery'  => true,
					'required'  => true,
					'opens_at'  => $this->dia( 10 ),
					'closes_at' => $this->dia( 20 ),
				),
			);
		}
		return $this->procedure( $this->administrator(), array( $this->area( 'Área de prueba' ) ), $meta, array( 'post_title' => 'Red de prueba' ) );
	}

	/**
	 * La pantalla pintada, con lo que la URL diga.
	 *
	 * @param int                   $procedure_id Procedure ID.
	 * @param array<string, string> $get          Query args.
	 * @return string
	 */
	private function pintar( int $procedure_id, array $get = array() ): string {
		$_GET[ ApplyForm::ARG_PROCEDURE ] = (string) $procedure_id;
		foreach ( $get as $clave => $valor ) {
			$_GET[ $clave ] = $valor;
		}
		return ApplyForm::render();
	}

	/**
	 * Una persona válida de la lista.
	 *
	 * @param string $dni DNI.
	 * @return array<string, string>
	 */
	private function persona( string $dni ): array {
		return array(
			'name'    => 'Ana',
			'surname' => 'Pérez',
			'dni'     => $dni,
			'email'   => 'ana@example.org',
			'phone'   => '',
			'role'    => 'r1',
			'group'   => '',
		);
	}

	/**
	 * Antes de presentar, los pasos dicen qué falta y por dónde se empieza;
	 * cada uno lleva a su sección.
	 */
	public function test_the_steps_say_what_is_done_and_what_is_missing() {
		$procedimiento = $this->procedimiento();
		$this->acting_as( $this->school_head( 'C0001' ) );

		$html = $this->pintar( $procedimiento );
		$this->assertStringContainsString( '<ol class="prc-pasos"', $html );
		$this->assertMatchesRegularExpression( '/<li class="prc-paso prc-paso--falta" aria-current="step">.*?href="#prc-solicitud-datos".*?Datos de la solicitud.*?Falta/s', $html );
		$this->assertMatchesRegularExpression( '/href="#' . PeopleForm::ANCHOR . '".*?Personas que participan.*?Falta/s', $html );
		$this->assertMatchesRegularExpression( '/href="#prc-entregas".*?Documentos con plazo propio.*?Falta/s', $html );
		$this->assertStringContainsString( 'id="prc-solicitud-datos"', $html );
		$this->assertStringContainsString( 'Después de presentar, en esta misma pantalla tendrá que dar de alta a las personas que participan (al menos 2).', $html, 'el paso 2 se anuncia antes de presentar' );
	}

	/**
	 * Con la lista completa, sus pasos están hechos y el siguiente que falta
	 * es el que se marca.
	 */
	public function test_a_complete_list_is_a_done_step() {
		$procedimiento = $this->procedimiento();
		$director      = $this->school_head( 'C0001' );
		$solicitud     = $this->application( $procedimiento, $director );
		People::add( $solicitud, $director, $this->persona( '12345678Z' ) );
		People::add( $solicitud, $director, $this->persona( 'X0000000T' ) );
		$this->acting_as( $director );

		$html = $this->pintar( $procedimiento );
		$this->assertMatchesRegularExpression( '/<li class="prc-paso prc-paso--hecho">.*?Datos de la solicitud.*?Hecho/s', $html );
		$this->assertMatchesRegularExpression( '/<li class="prc-paso prc-paso--hecho">.*?Personas que participan.*?Hecho/s', $html );
		$this->assertMatchesRegularExpression( '/<li class="prc-paso prc-paso--falta" aria-current="step">.*?Documentos con plazo propio/s', $html );
	}

	/**
	 * Sin lista ni entregas hay un solo paso, y una lista de un paso no dice nada.
	 */
	public function test_a_single_step_is_not_listed() {
		$this->acting_as( $this->school_head( 'C0001' ) );
		$this->assertStringNotContainsString( 'prc-pasos', $this->pintar( $this->procedimiento( false ) ) );
	}

	/**
	 * Las personas van antes de las entregas: es el paso que más se olvida.
	 */
	public function test_people_come_before_the_deliveries() {
		$procedimiento = $this->procedimiento();
		$director      = $this->school_head( 'C0001' );
		$this->application( $procedimiento, $director );
		$this->acting_as( $director );

		$html = $this->pintar( $procedimiento );
		$this->assertNotFalse( strpos( $html, 'id="prc-entregas"' ) );
		$this->assertLessThan( strpos( $html, 'id="prc-entregas"' ), strpos( $html, 'id="' . PeopleForm::ANCHOR . '"' ) );
	}

	/**
	 * Presentada con la lista por hacer, el aviso no es verde: dice lo que
	 * falta, hasta cuándo, y lleva a la lista.
	 */
	public function test_the_notice_after_submitting_says_what_is_missing() {
		$procedimiento = $this->procedimiento();
		$director      = $this->school_head( 'C0001' );
		$this->application( $procedimiento, $director );
		$this->acting_as( $director );

		$html = $this->pintar( $procedimiento, array( ApplyForm::ARG_NOTICE => 'presentada' ) );
		$this->assertStringContainsString( 'Solicitud presentada, pero todavía no está completa: falta dar de alta a las personas que participan (el procedimiento pide al menos 2).', $html );
		$this->assertStringContainsString( 'href="#' . PeopleForm::ANCHOR . '">Ir a la lista de personas</a>', $html );
		$this->assertStringNotContainsString( 'no tiene que hacer nada más', $html );
	}

	/**
	 * Completa, el aviso lo dice con la fecha y la hora, y que no hay que hacer nada más.
	 */
	public function test_the_notice_after_submitting_a_complete_one_says_so() {
		$procedimiento = $this->procedimiento( false );
		$director      = $this->school_head( 'C0001' );
		$solicitud     = $this->application( $procedimiento, $director );
		$this->acting_as( $director );

		$html = $this->pintar( $procedimiento, array( ApplyForm::ARG_NOTICE => 'presentada' ) );
		$this->assertStringContainsString( 'Solicitud presentada el ' . get_the_date( 'd-m-Y', $solicitud ) . ' a las ' . get_the_time( 'H:i', $solicitud ) . '. Está completa: no tiene que hacer nada más.', $html );
	}

	/**
	 * La tarjeta de estado dice si está completa y, si no, qué le falta, con
	 * el enlace a la lista y al justificante.
	 */
	public function test_the_status_card_says_complete_or_incomplete() {
		$procedimiento = $this->procedimiento();
		$director      = $this->school_head( 'C0001' );
		$solicitud     = $this->application( $procedimiento, $director );
		People::add( $solicitud, $director, $this->persona( '12345678Z' ) );
		$this->acting_as( $director );

		$html = $this->pintar( $procedimiento );
		$this->assertStringContainsString( 'Incompleta: falta 1 persona', $html );
		$this->assertStringContainsString( 'Presentada el ' . get_the_date( 'd-m-Y', $solicitud ) . ' a las ' . get_the_time( 'H:i', $solicitud ) . '.', $html );
		$this->assertMatchesRegularExpression( '/href="[^"]*' . ApplicationProgress::ARG_RECEIPT . '=1[^"]*">Ver e imprimir el justificante</', $html );

		$sin = $this->procedimiento( false );
		$this->application( $sin, $director );
		$this->assertStringContainsString( 'Completa: no le falta nada.', $this->pintar( $sin ) );
	}

	/**
	 * Una solicitud excluida no está ni completa ni incompleta.
	 */
	public function test_an_excluded_application_has_no_completeness_line() {
		$procedimiento = $this->procedimiento( false );
		$director      = $this->school_head( 'C0001' );
		$this->application( $procedimiento, $director, '', array( ApplicationMetaKeys::REVIEW_STATE => ApplicationMetaKeys::REVIEW_EXCLUDED ) );
		$this->acting_as( $director );

		$html = $this->pintar( $procedimiento );
		$this->assertStringNotContainsString( 'Completa: no le falta nada.', $html );
		$this->assertStringNotContainsString( 'Incompleta', $html );
	}

	/**
	 * El justificante: número, cuándo y quién, la completitud y lo
	 * presentado, sin formularios; se imprime con el navegador.
	 */
	public function test_the_receipt_proves_what_was_submitted() {
		$procedimiento = $this->procedimiento();
		$director      = $this->school_head( 'C0001' );
		$solicitud     = $this->application( $procedimiento, $director, '', array( ApplicationMetaKeys::APPLICANT_POSITION => ApplicationMetaKeys::POSITION_HEAD ) );
		People::add( $solicitud, $director, $this->persona( '12345678Z' ) );
		$this->acting_as( $director );

		$html = $this->pintar( $procedimiento, array( ApplicationProgress::ARG_RECEIPT => '1' ) );
		$this->assertStringContainsString( 'class="prc-justificante"', $html );
		$this->assertStringContainsString( 'Justificante de presentación', $html );
		$this->assertStringContainsString( '<dd>' . $solicitud . '</dd>', $html );
		$this->assertStringContainsString( 'Red de prueba', $html );
		$this->assertStringContainsString( 'Centro de prueba Norte (C0001)', $html );
		$this->assertStringContainsString( 'El ' . get_the_date( 'd-m-Y', $solicitud ) . ' a las ' . get_the_time( 'H:i', $solicitud ) . ', por ' . get_userdata( $director )->display_name, $html );
		$this->assertStringContainsString( 'Incompleta: falta 1 persona', $html );
		$this->assertStringContainsString( 'Ana Pérez', $html, 'con la lista de personas' );
		$this->assertStringContainsString( 'Guardar como PDF', $html );
		$this->assertStringNotContainsString( '<form', $html, 'solo lectura' );
	}

	/**
	 * Sin solicitud no hay justificante: la pantalla es la de siempre.
	 */
	public function test_without_an_application_there_is_no_receipt() {
		$procedimiento = $this->procedimiento();
		$this->acting_as( $this->school_head( 'C0001' ) );

		$html = $this->pintar( $procedimiento, array( ApplicationProgress::ARG_RECEIPT => '1' ) );
		$this->assertStringNotContainsString( 'Justificante de presentación', $html );
		$this->assertStringContainsString( '<form', $html );
	}

	/**
	 * Lo que le falta a una solicitud, leído de lo guardado: lo mismo que ve
	 * quien revisa.
	 */
	public function test_missing_reads_the_stored_application() {
		$procedimiento = $this->procedimiento();
		$director      = $this->school_head( 'C0001' );
		$solicitud     = $this->application( $procedimiento, $director );
		$this->assertSame( array( 'Faltan 2 personas' ), ApplicationProgress::missing( $solicitud ) );
		$this->assertSame( array(), ApplicationProgress::missing( $this->application( $this->procedimiento( false ), $director ) ) );
	}
}
