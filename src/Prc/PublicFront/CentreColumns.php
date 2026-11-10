<?php
/**
 * The columns the catalogue adds to the applications: island, municipality,
 * type, ownership and zone of each centre.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\Domain\CentreCatalog;
use Prc\Meta\ProcedureMetaKeys;

/**
 * Lo que el catálogo de centros añade a la tabla y a la exportación (ADR-0042).
 *
 * Va aparte de {@see Applications} y de {@see ApplicationExport} para que no
 * pasen del umbral de complejidad de PHPMD. Los atributos se leen del
 * catálogo **al pintar**: la solicitud solo guarda código y nombre
 * (ADR-0029), así que dicen lo que dice el catálogo hoy.
 *
 * Estas columnas se ofrecen, pero no son del CSV de siempre: sin elegirlas,
 * no salen.
 */
final class CentreColumns {

	/**
	 * Column keys, in the order they go after the code.
	 *
	 * @var string[]
	 */
	public const KEYS = array( 'island', 'municipality', 'centre_type', 'ownership', 'zone', 'zone_code' );

	/**
	 * The columns with their heading.
	 *
	 * @param bool $wanted Whether they are wanted at all; false gives none.
	 * @return array<string, string>
	 */
	public static function columns( bool $wanted = true ): array {
		if ( ! $wanted ) {
			return array();
		}
		$zona = self::zone_label();
		return array(
			'island'       => 'Isla',
			'municipality' => 'Municipio',
			'centre_type'  => 'Tipo de centro',
			'ownership'    => 'Titularidad',
			'zone'         => $zona,
			'zone_code'    => $zona . ' (código)',
		);
	}

	/**
	 * What people call the zone of a centre on this installation.
	 *
	 * El nombre que le da cada instalación no se escribe en el código
	 * (ADR-0009): lo contesta el filtro.
	 *
	 * @return string
	 */
	public static function zone_label(): string {
		/**
		 * Filter the label of the zone a centre is attached to.
		 *
		 * @param string $label Label. Default «Zona».
		 */
		$rotulo = apply_filters( 'prc_zone_label', 'Zona' );
		$rotulo = is_string( $rotulo ) ? trim( wp_strip_all_tags( $rotulo ) ) : '';
		return '' !== $rotulo ? $rotulo : 'Zona';
	}

	/**
	 * What the catalogue says today about the centre of a code.
	 *
	 * Es lo que leen los filtros por isla y por zona (ADR-0042): se pasa el
	 * `prc_centre_code` de la solicitud. Una de profesorado no tiene código, y
	 * sale vacía.
	 *
	 * @param string                                   $code  School code.
	 * @param array<string, array<string, mixed>>|null $index {@see CentreCatalog::index()}, when already read; null to look the code up.
	 * @return array{island:string, municipality:string, type:string, ownership:string, zone:string, zone_name:string} Every field empty when the code is not in the catalogue.
	 */
	public static function attributes( string $code, ?array $index = null ): array {
		$code   = trim( $code );
		$centro = array();
		if ( '' !== $code ) {
			$centro = null === $index ? CentreCatalog::find( $code ) : (array) ( $index[ $code ] ?? array() );
		}
		$out = array();
		foreach ( array( 'island', 'municipality', 'type', 'ownership', 'zone', 'zone_name' ) as $campo ) {
			$out[ $campo ] = (string) ( $centro[ $campo ] ?? '' );
		}
		return $out;
	}

	/**
	 * The catalogue indexed by code for some applications: read once, and
	 * not at all when there are none.
	 *
	 * @param \WP_Post[] $applications Applications about to be painted.
	 * @return array<string, array<string, mixed>>
	 */
	public static function index_for( array $applications ): array {
		return array() === $applications ? array() : CentreCatalog::index();
	}

	/**
	 * The cells of one row: the zone by its name and by its code.
	 *
	 * @param string                                   $code  School code.
	 * @param array<string, array<string, mixed>>|null $index Catalogue index, or null.
	 * @return array<string, string> Keys of {@see KEYS}.
	 */
	public static function cells( string $code, ?array $index ): array {
		$centro = self::attributes( $code, $index );
		return array(
			'island'       => $centro['island'],
			'municipality' => $centro['municipality'],
			'centre_type'  => $centro['type'],
			'ownership'    => ProcedureMetaKeys::ownership_name( $centro['ownership'] ),
			'zone'         => $centro['zone_name'],
			'zone_code'    => $centro['zone'],
		);
	}

	/**
	 * The chosen columns in the order they go to the file. Pure.
	 *
	 * Ninguna, o justo las de siempre aunque sea en otro orden: el CSV de
	 * siempre, sin un byte de diferencia. Todas las de siempre y además alguna
	 * de estas: en el orden de la tabla. Cualquier otra elección, en el orden
	 * pedido.
	 *
	 * @param array<string, string> $chosen    Valid chosen columns, key => heading.
	 * @param array<string, string> $always    The columns of the CSV «de siempre».
	 * @param array<string, string> $available Every column one may choose, in table order.
	 * @return array<string, string>
	 */
	public static function ordered( array $chosen, array $always, array $available ): array {
		$faltan = array_diff_key( $always, $chosen );
		if ( array() === $chosen || ( array() === $faltan && count( $chosen ) === count( $always ) ) ) {
			return $always;
		}
		return array() === $faltan ? array_intersect_key( $available, $chosen ) : $chosen;
	}

	/**
	 * «Todas las columnas» with these ticked on top. Pure.
	 *
	 * Sin ninguna de estas marcada, «todas» es la lista vacía: el CSV de
	 * siempre. Con alguna, las de siempre más esas.
	 *
	 * @param array<int, mixed> $ticked Column keys ticked in the dialog.
	 * @param string[]          $always Keys of the CSV «de siempre».
	 * @return string[]
	 */
	public static function all_plus( array $ticked, array $always ): array {
		$extras = array_values( array_intersect( self::KEYS, $ticked ) );
		return array() === $extras ? array() : array_merge( $always, $extras );
	}
}
