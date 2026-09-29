<?php
/**
 * Tests for ProcedureInput pure validation and normalisation.
 *
 * @package Prc
 */

use Prc\Domain\ProcedureInput;
use Prc\Meta\ProcedureMetaKeys;

/**
 * El alta y la edición de un procedimiento.
 */
class Test_Procedure_Input extends WP_UnitTestCase {

	/**
	 * A complete, valid payload.
	 *
	 * @param array<string, mixed> $overrides Fields to override.
	 * @return array<string, mixed>
	 */
	private function valido( array $overrides = array() ): array {
		return array_merge(
			array(
				'title'                => '  Programa de prueba  ',
				'description'          => '<p>Objetivos</p>',
				'areas'                => array( '7', '9' ),
				'course'               => 3,
				'opens_at'             => ' 2026-10-01 ',
				'closes_at'            => '2026-10-15',
				'amend_opens_at'       => '2026-10-20',
				'amend_closes_at'      => '2026-10-25',
				'resolution_url'       => 'https://example.org/resolucion.pdf',
				'contact_emails'       => array( ' Ambito@Example.org ', 'otro@example.org', '' ),
				'header_color'         => 'verde',
				'audience'             => 'schools',
				'ownership'            => array( 'public', 'private' ),
				'requires_coordinator' => '1',
				'commitments'          => ' El centro se compromete a… ',
			),
			$overrides
		);
	}

	/**
	 * Un procedimiento válido se acepta y se recorta.
	 */
	public function test_a_valid_procedure_is_accepted_and_trimmed() {
		$r = ProcedureInput::validate( $this->valido() );

		$this->assertTrue( $r['ok'] );
		$this->assertSame( array(), $r['errors'] );
		$this->assertSame( 'Programa de prueba', $r['data']['title'] );
		$this->assertSame( '<p>Objetivos</p>', $r['data']['description'] );
		$this->assertSame( array( 7, 9 ), $r['data']['areas'] );
		$this->assertSame( 3, $r['data']['course'] );
		$this->assertSame( '2026-10-01', $r['data']['opens_at'] );
		$this->assertSame( '2026-10-15', $r['data']['closes_at'] );
		$this->assertSame( '2026-10-20', $r['data']['amend_opens_at'] );
		$this->assertSame( '2026-10-25', $r['data']['amend_closes_at'] );
		$this->assertSame( 'https://example.org/resolucion.pdf', $r['data']['resolution_url'] );
		$this->assertSame( '', $r['data']['provisional_list_url'] );
		$this->assertSame( '', $r['data']['final_list_url'] );
		$this->assertSame( array( 'ambito@example.org', 'otro@example.org' ), $r['data']['contact_emails'], 'el tercero en blanco no es un correo que falte' );
		$this->assertSame( 'verde', $r['data']['header_color'] );
		$this->assertSame( 'schools', $r['data']['audience'] );
		$this->assertSame( array( 'public', 'private' ), $r['data']['ownership'] );
		$this->assertTrue( $r['data']['requires_coordinator'] );
		$this->assertSame( 'El centro se compromete a…', $r['data']['commitments'] );
	}

	/**
	 * Título, ámbitos, curso, correo de contacto y a quién se dirige son
	 * obligatorios; las fechas no.
	 */
	public function test_what_is_required_and_what_is_not() {
		$r = ProcedureInput::validate( array() );

		$this->assertFalse( $r['ok'] );
		$this->assertSame( array( 'title', 'areas', 'course', 'contact_emails', 'ownership' ), $r['errors'] );

		$r = ProcedureInput::validate(
			array(
				'title'          => 'Programa',
				'areas'          => 7,
				'course'         => 3,
				'contact_emails' => 'ambito@example.org',
				'ownership'      => 'public',
			)
		);
		$this->assertTrue( $r['ok'], 'sin fechas es un procedimiento próximo, y se guarda' );
		$this->assertSame( '', $r['data']['opens_at'] );
		$this->assertSame( array( 'public' ), $r['data']['ownership'] );
		$this->assertSame( 'schools', $r['data']['audience'], 'la audiencia por defecto son los centros' );
		$this->assertSame( array( 7 ), $r['data']['areas'], 'un ámbito suelto también es una lista' );
		$this->assertSame( ProcedureMetaKeys::HEADER_COLOR_NEUTRAL, $r['data']['header_color'], 'sin color elegido, el neutro' );
	}

	/**
	 * Sin fecha de cierre se copia la de apertura; sin fin de subsanación, su inicio.
	 */
	public function test_missing_closing_days_copy_the_opening_days() {
		$r = ProcedureInput::validate(
			$this->valido(
				array(
					'closes_at'       => '',
					'amend_closes_at' => '',
				)
			)
		);

		$this->assertTrue( $r['ok'] );
		$this->assertSame( '2026-10-01', $r['data']['closes_at'] );
		$this->assertSame( '2026-10-20', $r['data']['amend_closes_at'] );
	}

	/**
	 * Lo que no es un día del calendario no es una fecha, en ninguno de los cuatro campos.
	 */
	public function test_it_rejects_anything_that_is_not_a_calendar_day() {
		$malas = array( '2026-02-30', '2026-13-01', '10-03-2026', '2026-3-10', '20260310', '2026-03-10T09:00', 'mañana' );
		foreach ( array( 'opens_at', 'closes_at', 'amend_opens_at', 'amend_closes_at' ) as $campo ) {
			foreach ( $malas as $mala ) {
				$r = ProcedureInput::validate( $this->valido( array( $campo => $mala ) ) );
				$this->assertFalse( $r['ok'], $campo . ' ' . $mala );
				$this->assertContains( $campo, $r['errors'], $campo . ' ' . $mala );
			}
		}
	}

	/**
	 * El plazo no se invierte, y la subsanación va después del plazo, nunca dentro.
	 */
	public function test_the_order_of_the_dates() {
		$r = ProcedureInput::validate( $this->valido( array( 'closes_at' => '2026-09-30' ) ) );
		$this->assertContains( 'date_order', $r['errors'] );

		$r = ProcedureInput::validate( $this->valido( array( 'amend_closes_at' => '2026-10-19' ) ) );
		$this->assertContains( 'amend_order', $r['errors'] );

		$r = ProcedureInput::validate( $this->valido( array( 'amend_opens_at' => '2026-10-10' ) ) );
		$this->assertContains( 'amend_order', $r['errors'], 'la subsanación no puede empezar con el plazo abierto' );

		$r = ProcedureInput::validate( $this->valido( array( 'amend_opens_at' => '2026-10-15' ) ) );
		$this->assertContains( 'amend_order', $r['errors'], 'ni el mismo día que cierra' );

		$r = ProcedureInput::validate( $this->valido( array( 'amend_opens_at' => '2026-10-16' ) ) );
		$this->assertTrue( $r['ok'], 'al día siguiente sí' );

		// Un cierre sin apertura no es un plazo.
		$r = ProcedureInput::validate(
			$this->valido(
				array(
					'opens_at'        => '',
					'amend_opens_at'  => '',
					'amend_closes_at' => '',
				)
			)
		);
		$this->assertContains( 'opens_at', $r['errors'] );
	}

	/**
	 * Los enlaces son http(s) y el correo tiene forma de correo; lo demás es error y se vacía.
	 */
	public function test_links_and_email_need_their_shape() {
		foreach ( array( 'resolution_url', 'provisional_list_url', 'final_list_url' ) as $campo ) {
			$r = ProcedureInput::validate( $this->valido( array( $campo => 'javascript:alert(1)' ) ) );
			$this->assertContains( $campo, $r['errors'], $campo );
			$this->assertSame( '', $r['data'][ $campo ], $campo );

			$r = ProcedureInput::validate( $this->valido( array( $campo => 'https://example.org/listado?x=1' ) ) );
			$this->assertTrue( $r['ok'], $campo );
			$this->assertSame( 'https://example.org/listado?x=1', $r['data'][ $campo ], $campo );
		}

		$r = ProcedureInput::validate( $this->valido( array( 'contact_emails' => array( 'no es un correo' ) ) ) );
		$this->assertContains( 'contact_emails', $r['errors'] );
		$this->assertSame( array(), $r['data']['contact_emails'] );
	}

	/**
	 * Tres correos: el primero obligatorio, el segundo y el tercero opcionales.
	 */
	public function test_the_first_contact_email_is_the_only_one_required() {
		$r = ProcedureInput::validate( $this->valido( array( 'contact_emails' => array( 'uno@example.org' ) ) ) );
		$this->assertTrue( $r['ok'], 'con uno basta' );
		$this->assertSame( array( 'uno@example.org' ), $r['data']['contact_emails'] );

		$r = ProcedureInput::validate( $this->valido( array( 'contact_emails' => array( '', '', '' ) ) ) );
		$this->assertContains( 'contact_emails', $r['errors'], 'el primero es obligatorio' );

		$r = ProcedureInput::validate( $this->valido( array( 'contact_emails' => array( 'uno@example.org', 'roto' ) ) ) );
		$this->assertContains( 'contact_emails', $r['errors'], 'un segundo escrito y mal tumba el envío' );

		$r = ProcedureInput::validate(
			$this->valido(
				array(
					'contact_emails' => array( 'UNO@example.org', 'dos@example.org', 'tres@example.org', 'cuatro@example.org' ),
				)
			)
		);
		$this->assertSame(
			array( 'uno@example.org', 'dos@example.org', 'tres@example.org' ),
			$r['data']['contact_emails'],
			'caben tres, y en minúsculas'
		);
	}

	/**
	 * Los ámbitos son varios, y al menos uno.
	 */
	public function test_a_procedure_has_at_least_one_area_and_may_have_several() {
		$r = ProcedureInput::validate( $this->valido( array( 'areas' => array( 7, 9, 7, '0', 'no' ) ) ) );
		$this->assertTrue( $r['ok'] );
		$this->assertSame( array( 7, 9 ), $r['data']['areas'], 'sin repetidos, sin ceros y sin lo que no es un identificador' );

		foreach ( array( array(), array( 0 ), array( '' ), 'no', 0 ) as $ninguno ) {
			$r = ProcedureInput::validate( $this->valido( array( 'areas' => $ninguno ) ) );
			$this->assertContains( 'areas', $r['errors'] );
			$this->assertSame( array(), $r['data']['areas'] );
		}
	}

	/**
	 * El color de cabecera sale de la paleta cerrada y de ningún otro sitio.
	 */
	public function test_the_header_colour_comes_from_the_closed_palette() {
		foreach ( array_keys( ProcedureMetaKeys::header_colors() ) as $slug ) {
			$r = ProcedureInput::validate( $this->valido( array( 'header_color' => $slug ) ) );
			$this->assertTrue( $r['ok'], $slug );
			$this->assertSame( $slug, $r['data']['header_color'] );
		}

		foreach ( array( '#ff0000', 'fucsia', '<b>verde</b>', 'VERDE' ) as $fuera ) {
			$r = ProcedureInput::validate( $this->valido( array( 'header_color' => $fuera ) ) );
			$this->assertContains( 'header_color', $r['errors'], $fuera );
			$this->assertSame( ProcedureMetaKeys::HEADER_COLOR_NEUTRAL, $r['data']['header_color'], $fuera );
		}
	}

	/**
	 * Las listas cerradas se quedan en la lista.
	 */
	public function test_the_closed_lists_stay_closed() {
		$r = ProcedureInput::validate( $this->valido( array( 'audience' => 'alumnado' ) ) );
		$this->assertSame( 'schools', $r['data']['audience'] );

		$r = ProcedureInput::validate( $this->valido( array( 'ownership' => array( 'otra', 'private' ) ) ) );
		$this->assertSame( array( 'private' ), $r['data']['ownership'] );

		$r = ProcedureInput::validate( $this->valido( array( 'ownership' => 'private, public' ) ) );
		$this->assertSame( array( 'public', 'private' ), $r['data']['ownership'], 'en el orden de la lista, venga como venga' );

		$r = ProcedureInput::validate( $this->valido( array( 'ownership' => array( 'otra' ) ) ) );
		$this->assertContains( 'ownership', $r['errors'] );
	}

	/**
	 * Lo que llega de un formulario no siempre es una cadena.
	 */
	public function test_it_survives_values_that_are_not_strings() {
		$r = ProcedureInput::validate(
			array(
				'title'                => 0,
				'areas'                => '7',
				'course'               => '3',
				'contact_emails'       => 'ambito@example.org',
				'ownership'            => array( 'public' ),
				'opens_at'             => null,
				'commitments'          => 12,
				'requires_coordinator' => '',
				'resolution_url'       => array( 'no' ),
			)
		);

		$this->assertTrue( $r['ok'] );
		$this->assertSame( '0', $r['data']['title'] );
		$this->assertSame( '12', $r['data']['commitments'] );
		$this->assertSame( '', $r['data']['opens_at'] );
		$this->assertSame( '', $r['data']['resolution_url'] );
		$this->assertFalse( $r['data']['requires_coordinator'] );
	}

	/**
	 * Y el motivo se cuenta en una frase, sin repetir campos.
	 */
	public function test_why_names_the_fields_in_one_sentence() {
		$this->assertSame( 'Revise el título.', ProcedureInput::why( array( 'title' ) ) );
		$this->assertSame( 'Revise el título, los ámbitos convocantes y el curso.', ProcedureInput::why( array( 'title', 'areas', 'course', 'title' ) ) );
		$this->assertSame( 'No se ha podido guardar el procedimiento.', ProcedureInput::why( array( 'inventado' ) ) );
	}
}
