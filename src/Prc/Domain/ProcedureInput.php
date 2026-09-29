<?php
/**
 * Pure validation for procedure form input.
 *
 * @package Prc
 */

namespace Prc\Domain;

use Prc\Meta\ProcedureMetaKeys;

/**
 * Validates and normalises the raw fields of a procedure.
 *
 * Pura: ni una llamada a WordPress, para que se pueda probar sin cargarlo.
 * Los términos (ámbitos y curso) llegan como identificadores y aquí solo se
 * exige que los haya: que existan lo comprueba la pantalla, que es quien tiene
 * WordPress delante. Los ámbitos son varios (ADR-0025): al menos uno, y quien
 * edita solo puede poner los suyos, lo que ya no es asunto de esta clase pura
 * sino de {@see \Prc\PublicFront\ProcedureEditor::may_set_area()}.
 */
final class ProcedureInput {

	/**
	 * Validate a submitted procedure payload.
	 *
	 * Normalizaciones de la SDD: sin fecha de cierre se copia la de apertura;
	 * sin fin de subsanación se copia su inicio; un plazo de subsanación que
	 * empieza antes de que cierre el de solicitud es un error.
	 *
	 * @param array<string, mixed> $raw Raw fields.
	 * @return array{ok:bool, errors:string[], data:array<string, mixed>}
	 */
	public static function validate( array $raw ): array {
		$errors = array();

		$title        = self::text( $raw, 'title' );
		$description  = self::text( $raw, 'description' );
		$areas        = self::term_ids( $raw['areas'] ?? array() );
		$course       = isset( $raw['course'] ) ? (int) $raw['course'] : 0;
		$opens        = self::text( $raw, 'opens_at' );
		$closes       = self::text( $raw, 'closes_at' );
		$amend_opens  = self::text( $raw, 'amend_opens_at' );
		$amend_closes = self::text( $raw, 'amend_closes_at' );
		$emails       = self::emails( $raw['contact_emails'] ?? array() );
		$color        = self::text( $raw, 'header_color' );
		$audience     = ProcedureMetaKeys::in_list( $raw['audience'] ?? '', ProcedureMetaKeys::audiences(), ProcedureMetaKeys::AUDIENCE_SCHOOLS );
		$ownership    = self::ownership( $raw['ownership'] ?? array() );
		$urls         = array();

		if ( '' === $title ) {
			$errors[] = 'title';
		}
		if ( array() === $areas ) {
			$errors[] = 'areas';
		}
		if ( $course <= 0 ) {
			$errors[] = 'course';
		}

		foreach ( array( 'opens_at', 'closes_at', 'amend_opens_at', 'amend_closes_at' ) as $campo ) {
			$fecha = self::text( $raw, $campo );
			if ( '' !== $fecha && ! self::is_valid_date( $fecha ) ) {
				$errors[] = $campo;
			}
		}
		$opens        = self::is_valid_date( $opens ) ? $opens : '';
		$closes       = self::is_valid_date( $closes ) ? $closes : $opens;
		$amend_opens  = self::is_valid_date( $amend_opens ) ? $amend_opens : '';
		$amend_closes = self::is_valid_date( $amend_closes ) ? $amend_closes : $amend_opens;

		if ( '' !== $closes && '' === $opens ) {
			$errors[] = 'opens_at';
		}
		if ( '' !== $opens && $closes < $opens ) {
			$errors[] = 'date_order';
		}
		if ( '' !== $amend_opens && $amend_closes < $amend_opens ) {
			$errors[] = 'amend_order';
		}
		// La subsanación viene después del plazo de solicitud, nunca dentro, y
		// el mismo día del cierre ya es dentro: el plazo de solicitud es
		// inclusive y `amendment` gana a `open`, así que una subsanación que
		// abre ese día deja el procedimiento «En subsanación» mientras a los
		// centros les queda todavía ese día para presentar (adenda 2026-09-16).
		if ( '' !== $amend_opens && '' !== $closes && $amend_opens <= $closes ) {
			$errors[] = 'amend_order';
		}

		foreach ( array( 'resolution_url', 'provisional_list_url', 'final_list_url' ) as $campo ) {
			$url = self::text( $raw, $campo );
			if ( '' !== $url && ! self::is_url( $url ) ) {
				$errors[] = $campo;
				$url      = '';
			}
			$urls[ $campo ] = $url;
		}

		// El primero es obligatorio y los otros dos, opcionales; lo que se
		// escriba tiene que ser un correo, así que un campo relleno y mal
		// tumba el envío en vez de caerse sin avisar.
		foreach ( self::listed( $raw['contact_emails'] ?? array() ) as $escrito ) {
			if ( ! self::is_email( strtolower( trim( (string) $escrito ) ) ) ) {
				$errors[] = 'contact_emails';
			}
		}
		if ( array() === $emails ) {
			$errors[] = 'contact_emails';
		}
		if ( '' !== $color && '' === ProcedureMetaKeys::in_list( $color, ProcedureMetaKeys::header_colors() ) ) {
			$errors[] = 'header_color';
			$color    = '';
		}
		$color = '' === $color ? ProcedureMetaKeys::HEADER_COLOR_NEUTRAL : $color;
		if ( array() === $ownership ) {
			$errors[] = 'ownership';
		}
		$errors = array_values( array_unique( $errors ) );

		return array(
			'ok'     => array() === $errors,
			'errors' => $errors,
			'data'   => array_merge(
				array(
					'title'                => $title,
					'description'          => $description,
					'areas'                => $areas,
					'course'               => max( 0, $course ),
					'opens_at'             => $opens,
					'closes_at'            => $closes,
					'amend_opens_at'       => $amend_opens,
					'amend_closes_at'      => $amend_closes,
					'contact_emails'       => $emails,
					'header_color'         => $color,
					'audience'             => $audience,
					'ownership'            => $ownership,
					'requires_coordinator' => ! empty( $raw['requires_coordinator'] ),
					'commitments'          => self::text( $raw, 'commitments' ),
				),
				$urls
			),
		);
	}

	/**
	 * Why a procedure was refused, in Spanish.
	 *
	 * Vive aquí y no en cada pantalla para que todas lo cuenten igual.
	 *
	 * @param string[] $errors Error codes.
	 * @return string
	 */
	public static function why( array $errors ): string {
		$textos = array(
			'title'                => 'el título',
			'areas'                => 'los ámbitos convocantes',
			'course'               => 'el curso',
			'opens_at'             => 'la fecha de inicio del plazo',
			'closes_at'            => 'la fecha de fin del plazo',
			'amend_opens_at'       => 'la fecha de inicio de la subsanación',
			'amend_closes_at'      => 'la fecha de fin de la subsanación',
			'date_order'           => 'el orden de las fechas del plazo',
			'amend_order'          => 'el orden de las fechas de subsanación, que va después del plazo de solicitud',
			'resolution_url'       => 'el enlace a la resolución',
			'provisional_list_url' => 'el enlace al listado provisional',
			'final_list_url'       => 'el enlace al listado definitivo',
			'contact_emails'       => 'los correos de contacto, que empiezan por uno obligatorio',
			'header_color'         => 'el color de la cabecera',
			'ownership'            => 'a qué centros se dirige',
		);

		$faltan = array();
		foreach ( $errors as $error ) {
			if ( isset( $textos[ $error ] ) && ! in_array( $textos[ $error ], $faltan, true ) ) {
				$faltan[] = $textos[ $error ];
			}
		}

		if ( array() === $faltan ) {
			return 'No se ha podido guardar el procedimiento.';
		}
		if ( 1 === count( $faltan ) ) {
			return 'Revise ' . $faltan[0] . '.';
		}

		$ultimo = array_pop( $faltan );
		return 'Revise ' . implode( ', ', $faltan ) . ' y ' . $ultimo . '.';
	}

	/**
	 * Positive term IDs, without repeats, in the order they came.
	 *
	 * @param mixed $raw Raw value: a list, one ID, or a comma-separated string.
	 * @return int[]
	 */
	public static function term_ids( $raw ): array {
		$out = array();
		foreach ( self::listed( $raw ) as $valor ) {
			$id = (int) $valor;
			if ( $id > 0 && ! in_array( $id, $out, true ) ) {
				$out[] = $id;
			}
		}
		return $out;
	}

	/**
	 * Up to three addresses, each one an address; what is not, drops.
	 *
	 * Quien mande cuatro se queda con los tres primeros, como la meta: el
	 * formulario solo pinta tres campos.
	 *
	 * @param mixed $raw Raw value: a list, or one address.
	 * @return string[]
	 */
	public static function emails( $raw ): array {
		$out = array();
		foreach ( self::listed( $raw ) as $valor ) {
			$email = strtolower( trim( (string) $valor ) );
			if ( self::is_email( $email ) && ! in_array( $email, $out, true ) ) {
				$out[] = $email;
			}
		}
		return array_slice( $out, 0, ProcedureMetaKeys::CONTACT_EMAILS_MAX );
	}

	/**
	 * What came, as a list of scalars, with the blanks already gone.
	 *
	 * Un campo en blanco del formulario no es un error: es el segundo o el
	 * tercer correo que nadie escribió, o la casilla que nadie marcó.
	 *
	 * @param mixed $raw Raw value: a list, one scalar, or a comma-separated string.
	 * @return array<int, scalar>
	 */
	private static function listed( $raw ): array {
		if ( is_string( $raw ) ) {
			$raw = explode( ',', $raw );
		}
		if ( ! is_array( $raw ) ) {
			$raw = array( $raw );
		}
		$out = array();
		foreach ( $raw as $valor ) {
			if ( is_scalar( $valor ) && '' !== trim( (string) $valor ) ) {
				$out[] = $valor;
			}
		}
		return $out;
	}

	/**
	 * Keep only the ownerships of the closed list, in list order.
	 *
	 * @param mixed $raw Raw value: an array or a comma-separated string.
	 * @return string[]
	 */
	public static function ownership( $raw ): array {
		if ( is_string( $raw ) ) {
			$raw = explode( ',', $raw );
		}
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$raw = array_map( 'trim', array_map( 'strval', array_filter( $raw, 'is_scalar' ) ) );
		return array_values( array_intersect( array_keys( ProcedureMetaKeys::ownerships() ), $raw ) );
	}

	/**
	 * Whether the string is a calendar date in Y-m-d form.
	 *
	 * @param string $date Date string.
	 * @return bool
	 */
	public static function is_valid_date( string $date ): bool {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return false;
		}
		$parts = array_map( 'intval', explode( '-', $date ) );
		return checkdate( $parts[1], $parts[2], $parts[0] );
	}

	/**
	 * Whether a string looks like an http(s) address.
	 *
	 * Sin `esc_url_raw()` para que la clase siga siendo pura: el saneado de
	 * verdad lo hace la meta al guardar.
	 *
	 * @param string $value Raw value.
	 * @return bool
	 */
	public static function is_url( string $value ): bool {
		return (bool) preg_match( '#^https?://[^\s/$.?\#].[^\s]*$#i', $value );
	}

	/**
	 * Whether an address looks like an address.
	 *
	 * @param string $value Raw value.
	 * @return bool
	 */
	public static function is_email( string $value ): bool {
		return (bool) preg_match( '/^[^@\s]+@[^@\s.]+(\.[^@\s.]+)+$/', $value );
	}

	/**
	 * One trimmed field.
	 *
	 * @param array<string, mixed> $raw Raw fields.
	 * @param string               $key Field name.
	 * @return string
	 */
	private static function text( array $raw, string $key ): string {
		$value = $raw[ $key ] ?? '';
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}
}
