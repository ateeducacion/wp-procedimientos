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
use Prc\PublicFront\ApplicationOperations;
use Prc\PublicFront\ApplicationProgress;
use Prc\PublicFront\Applications;
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
	 * @param bool                 $con_todo Whether it has the list and the delivery.
	 * @param array<string, mixed> $lista    What changes in the list.
	 * @return int
	 */
	private function procedimiento( bool $con_todo = true, array $lista = array() ): int {
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
			) + $lista;
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
		$this->assertMatchesRegularExpression( '/href="#' . PeopleForm::ANCHOR . '".*?Participantes.*?Falta/s', $html );
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
		$this->assertMatchesRegularExpression( '/<li class="prc-paso prc-paso--hecho">.*?Participantes.*?Hecho/s', $html );
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
		$this->assertStringContainsString( 'href="#' . PeopleForm::ANCHOR . '">Ir a la lista de participantes</a>', $html );
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
	 * The receipt: number, when and who, completeness and what was submitted,
	 * with no forms; it prints from the browser, with or without the button.
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
		$this->assertStringContainsString( '<table class="prc-justificante__tabla">', $html, 'los datos, en una tabla' );
		$this->assertStringContainsString( '<th scope="row">N.º de solicitud</th><td>' . $solicitud . '</td>', $html );
		$this->assertStringContainsString( '<th scope="row">Fecha y hora de presentación</th><td>' . get_the_date( 'd-m-Y', $solicitud ) . ' a las ' . get_the_time( 'H:i', $solicitud ) . '</td>', $html );
		$this->assertStringContainsString( 'Red de prueba', $html );
		$this->assertStringContainsString( 'Centro de prueba Norte (C0001)', $html );
		$this->assertStringContainsString( '<th scope="row">Presentada por</th><td>' . get_userdata( $director )->display_name . ' (', $html );
		$this->assertStringContainsString( 'Incompleta: falta 1 persona', $html );
		$this->assertStringContainsString( 'Ana Pérez', $html, 'con la lista de personas' );
		$this->assertStringContainsString( 'Guardar como PDF', $html );
		$this->assertStringNotContainsString( 'pulse «Imprimir justificante»', $html, 'la ayuda vale también sin el botón' );
		$this->assertMatchesRegularExpression( '/<p class="prc-no-imprimir" hidden><button type="button"[^>]* data-prc-print>.*Imprimir justificante<\/button>/s', $html, 'el botón, escondido hasta que el guion lo enseña' );
		$this->assertStringNotContainsString( '<form', $html, 'solo lectura' );
		$this->assertStringNotContainsString( 'prc-justificante__cab', $html, 'sin marca configurada, sin cabecera' );
	}

	/**
	 * The receipt header carries the logo and the name of whoever deploys,
	 * each one only if `prc_chrome` brings it (ADR-0009).
	 */
	public function test_the_receipt_header_has_the_brand_of_whoever_deploys() {
		$procedimiento = $this->procedimiento();
		$director      = $this->school_head( 'C0001' );
		$this->application( $procedimiento, $director );
		$this->acting_as( $director );
		$puesto = array();
		add_filter(
			'prc_chrome',
			static function ( array $chrome ) use ( &$puesto ): array {
				return array_merge( $chrome, $puesto );
			}
		);
		$recibo = function () use ( $procedimiento ): string {
			return $this->pintar( $procedimiento, array( ApplicationProgress::ARG_RECEIPT => '1' ) );
		};

		$puesto = array(
			'brand_logo' => 'https://example.org/escudo.png',
			'brand_alt'  => 'Escudo de ejemplo',
			'org'        => 'Organismo de ejemplo',
		);
		$html   = $recibo();
		$this->assertStringContainsString( '<div class="prc-justificante__cab">', $html );
		$this->assertStringContainsString( '<img src="https://example.org/escudo.png" alt="Escudo de ejemplo" class="prc-justificante__escudo"', $html );
		$this->assertStringContainsString( '<span class="prc-justificante__org">Organismo de ejemplo</span>', $html );

		$puesto = array( 'org' => 'Organismo de ejemplo' );
		$html   = $recibo();
		$this->assertStringContainsString( '<div class="prc-justificante__cab">', $html, 'solo el rótulo' );
		$this->assertStringContainsString( 'prc-justificante__org', $html );
		$this->assertStringNotContainsString( 'prc-justificante__escudo', $html );

		$puesto = array( 'brand_logo' => 'https://example.org/escudo.png' );
		$html   = $recibo();
		$this->assertStringContainsString( '<div class="prc-justificante__cab">', $html, 'solo el escudo' );
		$this->assertStringContainsString( '<img src="https://example.org/escudo.png" alt="" class="prc-justificante__escudo"', $html, 'sin texto alternativo, decorativo' );
		$this->assertStringNotContainsString( 'prc-justificante__org', $html );
	}

	/**
	 * The coordination goes on the receipt with its DNI or NIE, only when known.
	 */
	public function test_the_receipt_carries_the_coordinator_id_only_when_present() {
		$procedimiento = $this->procedimiento();
		$director      = $this->school_head( 'C0001' );
		$solicitud     = $this->application( $procedimiento, $director );
		$this->acting_as( $director );
		$recibo = function () use ( $procedimiento ): string {
			return $this->pintar( $procedimiento, array( ApplicationProgress::ARG_RECEIPT => '1' ) );
		};

		update_post_meta( $solicitud, ApplicationMetaKeys::COORDINATOR, array() );
		$this->assertStringNotContainsString( '<th scope="row">Coordinación</th>', $recibo(), 'sin coordinación, sin fila' );

		$ana = array(
			'name'    => 'Ana',
			'surname' => 'Pérez',
			'dni'     => '12345678-z',
			'email'   => 'ana@example.org',
		);
		update_post_meta( $solicitud, ApplicationMetaKeys::COORDINATOR, array( $ana ) );
		$this->assertStringContainsString( '<th scope="row">Coordinación</th><td>Ana Pérez (DNI o NIE 12345678Z)</td>', $recibo() );

		$ana['dni'] = '';
		update_post_meta( $solicitud, ApplicationMetaKeys::COORDINATOR, array( $ana ) );
		$html = $recibo();
		$this->assertStringContainsString( '<th scope="row">Coordinación</th><td>Ana Pérez</td>', $html, 'sin DNI, solo el nombre' );
		$this->assertStringNotContainsString( 'DNI o NIE', $html );
	}

	/**
	 * Without an author there is no «Presentada por» row, not a lone «(cargo)».
	 */
	public function test_the_receipt_skips_who_submitted_when_nobody_is_known() {
		$procedimiento = $this->procedimiento();
		$director      = $this->school_head( 'C0001' );
		$solicitud     = $this->application( $procedimiento, $director, '', array( ApplicationMetaKeys::APPLICANT_POSITION => ApplicationMetaKeys::POSITION_HEAD ) );
		wp_update_post(
			array(
				'ID'          => $solicitud,
				'post_author' => 0,
			)
		);
		$this->acting_as( $director );

		$html = $this->pintar( $procedimiento, array( ApplicationProgress::ARG_RECEIPT => '1' ) );
		$this->assertStringContainsString( 'Justificante de presentación', $html );
		$this->assertStringNotContainsString( 'Presentada por', $html );
		$this->assertStringNotContainsString( '<td>(', $html );
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

	/**
	 * Una lista que solo gestionan las admitidas: recién presentada no se
	 * puede escribir, así que ni el anuncio de antes ni el aviso de después
	 * mandan a darla de alta «en esta misma pantalla» ni «hasta el»; dicen
	 * por qué todavía no.
	 */
	public function test_a_list_only_for_admitted_ones_is_not_promised_after_submitting() {
		$procedimiento = $this->procedimiento( true, array( 'admitted_only' => true ) );
		$director      = $this->school_head( 'C0001' );
		$this->acting_as( $director );

		$antes = $this->pintar( $procedimiento );
		$this->assertStringNotContainsString( 'en esta misma pantalla', $antes );
		$this->assertStringContainsString( 'La lista de participantes se gestiona cuando la solicitud esté admitida', $antes );

		$this->application( $procedimiento, $director );
		$html = $this->pintar( $procedimiento, array( ApplyForm::ARG_NOTICE => 'presentada' ) );
		$this->assertStringContainsString( 'Solicitud presentada, pero todavía no está completa: falta dar de alta a las personas que participan (el procedimiento pide al menos 2). La lista de participantes se gestiona cuando la solicitud esté admitida, y todavía no lo está.', $html );
		$this->assertStringNotContainsString( 'Hágalo abajo', $html );
		$this->assertStringNotContainsString( 'Puede dar de alta, cambiar o sustituir personas hasta el', $html );
	}

	/**
	 * Una lista que se abre más adelante: lo mismo, con el día en que se abre.
	 */
	public function test_a_list_that_opens_later_is_not_promised_after_submitting() {
		$abre          = $this->dia( 3 );
		$dia           = implode( '-', array_reverse( explode( '-', $abre ) ) );
		$procedimiento = $this->procedimiento(
			true,
			array(
				'opens_at'  => $abre,
				'closes_at' => $this->dia( 8 ),
			)
		);
		$director      = $this->school_head( 'C0001' );
		$this->acting_as( $director );

		$antes = $this->pintar( $procedimiento );
		$this->assertStringNotContainsString( 'en esta misma pantalla', $antes );
		$this->assertStringContainsString( 'La lista de participantes se abre el ' . $dia . '.', $antes );

		$this->application( $procedimiento, $director );
		$html = $this->pintar( $procedimiento, array( ApplyForm::ARG_NOTICE => 'presentada' ) );
		$this->assertStringContainsString( '(el procedimiento pide al menos 2). La lista de participantes se abre el ' . $dia . '.', $html );
		$this->assertStringNotContainsString( 'Hágalo abajo', $html );
	}

	/**
	 * Una entrega obligatoria que todavía no ha vencido no falta, pero queda
	 * por hacer: ni el aviso ni la tarjeta dicen que no hay nada más que
	 * hacer; nombran la entrega y su plazo.
	 */
	public function test_a_pending_required_delivery_is_not_called_complete() {
		$procedimiento = $this->procedure(
			$this->administrator(),
			array( $this->area( 'Área de prueba' ) ),
			array(
				ProcedureMetaKeys::OPENS_AT  => $this->dia( -1 ),
				ProcedureMetaKeys::CLOSES_AT => $this->dia( 5 ),
				ProcedureMetaKeys::QUESTIONS => array(
					array(
						'label'     => 'Memoria final',
						'type'      => ProcedureQuestions::TYPE_FILE,
						'delivery'  => true,
						'required'  => true,
						'opens_at'  => $this->dia( 10 ),
						'closes_at' => $this->dia( 20 ),
					),
				),
			),
			array( 'post_title' => 'Red de prueba' )
		);
		$director      = $this->school_head( 'C0001' );
		$solicitud     = $this->application( $procedimiento, $director );
		$this->acting_as( $director );
		$plazo = 'Memoria final, del ' . implode( '-', array_reverse( explode( '-', $this->dia( 10 ) ) ) ) . ' al ' . implode( '-', array_reverse( explode( '-', $this->dia( 20 ) ) ) );

		$this->assertSame( array( $plazo ), ApplicationProgress::pending( $solicitud ) );
		$html = $this->pintar( $procedimiento, array( ApplyForm::ARG_NOTICE => 'presentada' ) );
		$this->assertStringNotContainsString( 'no tiene que hacer nada más', $html );
		$this->assertStringNotContainsString( 'Completa: no le falta nada.', $html );
		$this->assertStringContainsString( 'Solicitud presentada el ' . get_the_date( 'd-m-Y', $solicitud ) . ' a las ' . get_the_time( 'H:i', $solicitud ) . '. Le queda entregar: ' . $plazo . '.', $html );
		$this->assertStringContainsString( 'Presentada; le queda entregar: ' . $plazo . '.', $html );
	}

	/**
	 * Presentada en nombre del centro (ADR-0044) y sin completar: el centro
	 * todavía no ha presentado nada, así que no hay justificante y el paso
	 * de los datos no está hecho.
	 */
	public function test_an_application_submitted_on_behalf_has_no_receipt_until_the_school_completes_it() {
		$procedimiento = $this->procedimiento();
		$solicitud     = ApplicationOperations::submit_for( $procedimiento, $this->administrator(), 'C0001', $this->dia( 5 ) );
		$this->assertGreaterThan( 0, $solicitud );
		$this->acting_as( $this->school_head( 'C0001' ) );

		$html = $this->pintar( $procedimiento );
		$this->assertStringNotContainsString( 'Ver e imprimir el justificante', $html );
		$this->assertMatchesRegularExpression( '/<li class="prc-paso prc-paso--falta" aria-current="step">.*?Datos de la solicitud.*?Falta/s', $html );
		$this->assertStringNotContainsString( 'Presentada el ', $html );
		$this->assertStringContainsString( 'Registrada en su nombre el ', $html );

		$html = $this->pintar( $procedimiento, array( ApplicationProgress::ARG_RECEIPT => '1' ) );
		$this->assertStringNotContainsString( 'Justificante de presentación', $html );
	}

	/**
	 * Cuando el centro la completa, la presenta él: el justificante y el
	 * acuse dicen ese momento, no el día en que se registró en su nombre,
	 * que sale aparte.
	 */
	public function test_an_adopted_application_receipt_says_when_the_school_completed_it() {
		$procedimiento = $this->procedimiento( false );
		$solicitud     = ApplicationOperations::submit_for( $procedimiento, $this->administrator(), 'C0001', $this->dia( 5 ) );
		$antes         = gmdate( 'Y-m-d H:i:s', time() - 3 * DAY_IN_SECONDS );
		wp_update_post(
			array(
				'ID'            => $solicitud,
				'post_date'     => get_date_from_gmt( $antes ),
				'post_date_gmt' => $antes,
			)
		);
		$registrada = ApplicationProgress::when( (int) get_post_timestamp( $solicitud ) );
		$correos    = array();
		add_filter(
			'pre_wp_mail',
			static function ( $nada, array $atts ) use ( &$correos ) {
				$correos[] = $atts;
				return $nada;
			},
			5,
			2
		);
		$director = $this->school_head( 'C0001' );
		$this->assertSame(
			$solicitud,
			Applications::save(
				$procedimiento,
				$director,
				array(
					'position'    => ApplicationMetaKeys::POSITION_HEAD,
					'coordinator' => array(),
					'answers'     => array(),
				)
			)
		);
		$this->acting_as( $director );

		$html = $this->pintar( $procedimiento, array( ApplicationProgress::ARG_RECEIPT => '1' ) );
		$this->assertStringContainsString( 'Justificante de presentación', $html );
		$this->assertDoesNotMatchRegularExpression( '/<th scope="row">Fecha y hora de presentación<\/th><td>' . preg_quote( $registrada, '/' ) . '/', $html, 'presentada no es el día en que se registró' );
		$this->assertMatchesRegularExpression( '/<th scope="row">Fecha y hora de presentación<\/th><td>' . preg_quote( (string) wp_date( 'd-m-Y' ), '/' ) . '/', $html );
		$this->assertStringContainsString( 'El ' . $registrada . ', por quien gestiona el procedimiento.', $html );

		$acuse = array_values(
			array_filter(
				$correos,
				static function ( array $correo ): bool {
					return false !== strpos( (string) $correo['subject'], 'Solicitud presentada' );
				}
			)
		);
		$this->assertCount( 1, $acuse );
		$this->assertStringNotContainsString( ' el ' . $registrada, (string) $acuse[0]['message'] );
	}
}
