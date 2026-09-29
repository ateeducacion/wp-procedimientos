<?php
/**
 * A range of days written the way Spanish writes it.
 *
 * @package Prc
 */

namespace Prc\Domain;

/**
 * Escribe los plazos de un procedimiento en castellano.
 *
 * Hasta ahora la cabecera pública pegaba dos fechas sueltas con un «al» y las
 * pintaba con el formato del ajuste `date_format` del sitio, que por defecto es
 * el inglés `F j, Y`. El resultado, verificado en la portada, era
 * «Del septiembre 12, 2026 al septiembre 14, 2026». Y no se arregla cambiando
 * el ajuste: un intervalo en castellano no es dos fechas sueltas, porque el mes
 * y el año se dicen una sola vez cuando los dos días los comparten.
 *
 *   un solo día ............. «12 de septiembre de 2026»
 *   mismo mes y año ......... «Del 12 al 14 de septiembre de 2026»
 *   mismo año, otro mes ..... «Del 30 de septiembre al 2 de octubre de 2026»
 *   otro año ................ «Del 30 de diciembre de 2026 al 2 de enero de 2027»
 *   solo el primer día ...... «Desde el 12 de septiembre de 2026»
 *   fecha que no es fecha ... cadena vacía, y quien la pinta se calla la línea
 *
 * Pura salvo por los nombres de mes, que salen de `WP_Locale` y nunca de un
 * array escrito a mano: traducirlos aquí sería tener dos veces lo que
 * WordPress ya tiene traducido.
 */
final class DateRange {

	/**
	 * A range of days, written out.
	 *
	 * @param string $start First day, Y-m-d.
	 * @param string $end   Last day, Y-m-d ('' when there is none yet).
	 * @return string Empty when the first day is missing or is not a real date.
	 */
	public static function of( string $start, string $end = '' ): string {
		$desde = self::parts( $start );
		if ( array() === $desde ) {
			return '';
		}

		$hasta = self::parts( $end );
		if ( array() === $hasta ) {
			return 'Desde el ' . self::day( $desde );
		}

		// Un último día anterior al primero es una errata: el intervalo no se
		// invierte, se escribe el primer día y ya. Igual que hace ProcedureState.
		if ( $hasta['n'] <= $desde['n'] ) {
			return self::day( $desde );
		}
		if ( $hasta['y'] !== $desde['y'] ) {
			return sprintf( 'Del %s al %s', self::day( $desde ), self::day( $hasta ) );
		}
		if ( $hasta['m'] !== $desde['m'] ) {
			return sprintf( 'Del %d de %s al %s', $desde['d'], self::month( $desde['m'] ), self::day( $hasta ) );
		}
		return sprintf( 'Del %d al %s', $desde['d'], self::day( $hasta ) );
	}

	/**
	 * One day, written in full.
	 *
	 * @param array{n:int,y:int,m:int,d:int} $day What parts() returned.
	 * @return string
	 */
	private static function day( array $day ): string {
		return sprintf( '%d de %s de %d', $day['d'], self::month( $day['m'] ), $day['y'] );
	}

	/**
	 * Year, month and day of a Y-m-d string.
	 *
	 * `n` es la fecha como número comparable (20260912): las que llegan de la
	 * migración no siempre traen el cero de relleno, así que comparar cadenas
	 * pondría el 9 después del 10.
	 *
	 * @param string $ymd Day, Y-m-d.
	 * @return array{n:int,y:int,m:int,d:int}|array{} Empty when it is not a real date.
	 */
	private static function parts( string $ymd ): array {
		if ( 1 !== preg_match( '/^(\d{4})-(\d{1,2})-(\d{1,2})$/', trim( $ymd ), $trozos ) ) {
			return array();
		}

		$anio = (int) $trozos[1];
		$mes  = (int) $trozos[2];
		$dia  = (int) $trozos[3];
		if ( ! checkdate( $mes, $dia, $anio ) ) {
			return array();
		}

		return array(
			'n' => $anio * 10000 + $mes * 100 + $dia,
			'y' => $anio,
			'm' => $mes,
			'd' => $dia,
		);
	}

	/**
	 * The name of a month, as the locale writes it.
	 *
	 * En castellano los meses van en minúscula, y el resto de la frase —«Del»,
	 * «al», «de»— ya está en castellano, así que se pasa a minúscula lo que
	 * devuelva la locale.
	 *
	 * @param int $month Month, 1-12.
	 * @return string
	 */
	private static function month( int $month ): string {
		global $wp_locale;
		return mb_strtolower( (string) $wp_locale->get_month( $month ), 'UTF-8' );
	}
}
