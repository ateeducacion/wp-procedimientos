<?php
/**
 * Tests for DateRange: the dates of a procedure, written in Spanish.
 *
 * @package Prc
 */

use Prc\Domain\DateRange;

/**
 * El intervalo de fechas tal y como se lee. Copia del aplicativo de eventos (ADR-0010).
 *
 * El defecto que trae esta clase al mundo se vio en la portada del aplicativo de eventos:
 * «Del septiembre 12, 2026 al septiembre 14, 2026», que es el formato `F j, Y`
 * de un WordPress en inglés pegado dos veces con un «al». Aquí se fija cada
 * frase entera, letra a letra, porque el fallo era precisamente la frase.
 *
 * Los nombres de mes se fuerzan a los castellanos en `set_up()`: así los
 * asertos son cadenas literales y no dependen del idioma con el que esté
 * levantado el WordPress de pruebas, que hoy está en inglés.
 */
class Test_Date_Range extends WP_UnitTestCase {

	/**
	 * Month names of the locale before the test replaced them.
	 *
	 * @var array<string, string>
	 */
	private $meses_originales = array();

	/**
	 * Con los meses en castellano.
	 */
	public function set_up() {
		parent::set_up();
		$this->meses_originales      = $GLOBALS['wp_locale']->month;
		$GLOBALS['wp_locale']->month = array(
			'01' => 'enero',
			'02' => 'febrero',
			'03' => 'marzo',
			'04' => 'abril',
			'05' => 'mayo',
			'06' => 'junio',
			'07' => 'julio',
			'08' => 'agosto',
			'09' => 'septiembre',
			'10' => 'octubre',
			'11' => 'noviembre',
			'12' => 'diciembre',
		);
	}

	/**
	 * Y se devuelven como estaban.
	 */
	public function tear_down() {
		$GLOBALS['wp_locale']->month = $this->meses_originales;
		parent::tear_down();
	}

	/**
	 * Los seis casos del enunciado, con la frase completa.
	 */
	public function test_the_six_ways_of_writing_a_range() {
		$casos = array(
			// Un solo día: el principio y el fin son el mismo día.
			array( '2026-09-12', '2026-09-12', '12 de septiembre de 2026' ),
			// Mismo mes y mismo año: el mes y el año se dicen una vez.
			array( '2026-09-12', '2026-09-14', 'Del 12 al 14 de septiembre de 2026' ),
			// Mismo año, meses distintos: el año se dice una vez.
			array( '2026-09-30', '2026-10-02', 'Del 30 de septiembre al 2 de octubre de 2026' ),
			// Años distintos: las dos fechas van enteras.
			array( '2026-12-30', '2027-01-02', 'Del 30 de diciembre de 2026 al 2 de enero de 2027' ),
			// Solo se sabe cuándo empieza.
			array( '2026-09-12', '', 'Desde el 12 de septiembre de 2026' ),
			// Sin fecha de inicio no hay nada que escribir.
			array( '', '2026-09-14', '' ),
		);

		foreach ( $casos as $caso ) {
			$this->assertSame( $caso[2], DateRange::of( $caso[0], $caso[1] ), $caso[0] . ' → ' . $caso[1] );
		}
	}

	/**
	 * Y así es como NO se escribe: el defecto que se arregla.
	 */
	public function test_it_is_not_two_loose_dates_glued_with_an_al() {
		$frase = DateRange::of( '2026-09-12', '2026-09-14' );

		$this->assertStringNotContainsString( 'septiembre 12', $frase, 'el mes no va delante del día' );
		$this->assertStringNotContainsString( ',', $frase, 'ni comas del formato inglés' );
		// El mes y el año se dicen una sola vez, no uno por fecha.
		$this->assertSame( 1, substr_count( $frase, 'septiembre' ) );
		$this->assertSame( 1, substr_count( $frase, '2026' ) );
	}

	/**
	 * Los bordes: fin anterior al inicio, cambio de mes, cambio de año, día 1 y día 31.
	 */
	public function test_the_edges() {
		$casos = array(
			// Un fin anterior al inicio es una errata: no se invierte el intervalo.
			array( '2026-09-14', '2026-09-12', '14 de septiembre de 2026' ),
			array( '2027-01-02', '2026-12-30', '2 de enero de 2027' ),
			// El último día del mes y el primero del siguiente.
			array( '2026-01-31', '2026-02-01', 'Del 31 de enero al 1 de febrero de 2026' ),
			array( '2026-08-31', '2026-09-01', 'Del 31 de agosto al 1 de septiembre de 2026' ),
			// El último día del año y el primero del siguiente.
			array( '2026-12-31', '2027-01-01', 'Del 31 de diciembre de 2026 al 1 de enero de 2027' ),
			// Día 1 y día 31 del mismo mes: el intervalo entero.
			array( '2026-05-01', '2026-05-31', 'Del 1 al 31 de mayo de 2026' ),
			// Un solo día que además es el 1.
			array( '2026-05-01', '2026-05-01', '1 de mayo de 2026' ),
			// 29 de febrero: el año bisiesto existe y el que no lo es, no.
			array( '2028-02-29', '2028-02-29', '29 de febrero de 2028' ),
			array( '2026-02-29', '2026-03-01', '' ),
		);

		foreach ( $casos as $caso ) {
			$this->assertSame( $caso[2], DateRange::of( $caso[0], $caso[1] ), $caso[0] . ' → ' . $caso[1] );
		}
	}

	/**
	 * El día va sin el cero de relleno, que es como se dice.
	 */
	public function test_the_day_never_carries_a_leading_zero() {
		$this->assertSame( '2 de enero de 2027', DateRange::of( '2027-01-02', '2027-01-02' ) );
		$this->assertSame( 'Del 1 al 9 de marzo de 2026', DateRange::of( '2026-03-01', '2026-03-09' ) );
		$this->assertStringNotContainsString( ' 0', DateRange::of( '2026-03-01', '2026-04-02' ) );
	}

	/**
	 * Lo que no es una fecha no se pinta: cadena vacía y la línea se calla.
	 */
	public function test_what_is_not_a_date_is_not_painted() {
		foreach ( array( '', '   ', 'mañana', '12/09/2026', '2026-13-01', '2026-02-30', '2026-09-12 10:00' ) as $basura ) {
			$this->assertSame( '', DateRange::of( $basura, '2026-09-14' ), '«' . $basura . '»' );
		}

		// Un fin que no es fecha se ignora, pero el inicio se sigue escribiendo.
		$this->assertSame( 'Desde el 12 de septiembre de 2026', DateRange::of( '2026-09-12', 'cuando acabe' ) );
		$this->assertSame( 'Desde el 12 de septiembre de 2026', DateRange::of( '2026-09-12', '2026-02-31' ) );
	}

	/**
	 * Los espacios sobrantes de lo que llegue de la migración no estorban.
	 */
	public function test_stray_whitespace_does_not_get_in_the_way() {
		$this->assertSame(
			'Del 12 al 14 de septiembre de 2026',
			DateRange::of( "  2026-09-12\n", ' 2026-09-14 ' )
		);
	}

	/**
	 * Sin el cero de relleno también: así llegan algunas fechas de hoy.
	 */
	public function test_dates_without_their_padding_zero_still_parse_and_sort() {
		$this->assertSame( 'Del 9 al 10 de marzo de 2026', DateRange::of( '2026-3-9', '2026-3-10' ) );
		// Y el orden se compara por número, no por cadena: '9' no va después de '10'.
		$this->assertSame( '10 de marzo de 2026', DateRange::of( '2026-3-10', '2026-3-9' ) );
	}

	/**
	 * Los meses salen de la locale y no de un array escrito aquí a mano.
	 */
	public function test_the_month_names_come_from_the_locale() {
		$GLOBALS['wp_locale']->month['09'] = 'Setembre';

		$frase = DateRange::of( '2026-09-12', '2026-09-14' );

		$this->assertStringContainsString( 'setembre', $frase, 'el nombre lo pone la locale' );
		$this->assertStringNotContainsString( 'Setembre', $frase, 'y va en minúscula' );
		$this->assertStringNotContainsString( 'septiembre', $frase, 'no hay meses escritos a mano' );
	}
}
