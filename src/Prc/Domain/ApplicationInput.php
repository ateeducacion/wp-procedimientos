<?php
/**
 * Pure validation of the fixed core of the application form.
 *
 * @package Prc
 */

namespace Prc\Domain;

use Prc\Meta\ApplicationMetaKeys;

/**
 * Valida y normaliza el núcleo fijo de la solicitud de un centro.
 *
 * Pura: ni una llamada a WordPress. Es la parte del formulario que no cambia
 * de un procedimiento a otro (ADR-0019): el cargo de quien solicita, la
 * aceptación de los compromisos, las personas coordinadoras si se piden, y las
 * respuestas a las preguntas, que valida {@see ProcedureQuestions}.
 *
 * Lo que aquí no está, no se teclea: el centro (código y nombre) lo resuelve
 * el aplicativo desde la persona (ADR-0016), y quien solicita es `post_author`.
 */
final class ApplicationInput {

	/**
	 * Validate a submitted application payload against its procedure.
	 *
	 * @param array<string, mixed> $raw       Raw fields.
	 * @param array<string, mixed> $procedure What the procedure asks for:
	 *                                        `coordinator_count` (int), `commitments` (string)
	 *                                        and `questions` (normalised list).
	 * `errors` son los códigos de siempre, los que lee {@see why()}; `fields`
	 * dice además **dónde**: la coordinación con su fila (`coordinator_dni:1`),
	 * para que la pantalla marque el campo que falla y no solo lo cuente.
	 *
	 * @return array{ok:bool, errors:string[], fields:string[], data:array<string, mixed>}
	 */
	public static function validate( array $raw, array $procedure ): array {
		$errors  = array();
		$teacher = 'teacher' === ( $procedure['applicant_type'] ?? '' );

		$position = $teacher ? '' : self::text( $raw, 'position' );
		if ( ! $teacher && ! isset( ApplicationMetaKeys::positions()[ $position ] ) ) {
			$errors[] = 'position';
			$position = '';
		}

		$count        = $teacher ? 0 : (int) ( $procedure['coordinator_count'] ?? ( ! empty( $procedure['requires_coordinator'] ) ? 1 : 0 ) );
		$coordinacion = self::coordinators( $raw, $count );
		$errors       = array_merge( $errors, $coordinacion['errors'] );
		$coordinators = $coordinacion['data'];

		// La casilla de compromisos solo existe cuando hay compromisos que aceptar.
		$accepted = ! empty( $raw['accept'] );
		if ( '' !== trim( (string) ( $procedure['commitments'] ?? '' ) ) && ! $accepted ) {
			$errors[] = 'accept';
		}

		$answers   = is_array( $raw['answers'] ?? null ) ? $raw['answers'] : array();
		$respuesta = ProcedureQuestions::validate_answers( (array) ( $procedure['questions'] ?? array() ), $answers );
		foreach ( $respuesta['errors'] as $key ) {
			$errors[] = 'answer:' . $key;
		}

		return array(
			'ok'     => array() === $errors,
			'errors' => $errors,
			'fields' => array_values( array_merge( array_diff( $errors, $coordinacion['errors'] ), $coordinacion['fields'] ) ),
			'data'   => array(
				'position'    => $position,
				'coordinator' => $coordinators,
				'accept'      => $accepted,
				'answers'     => $respuesta['data'],
			),
		);
	}

	/**
	 * Validate the requested coordinators: name, surname, DNI or NIE and email each.
	 *
	 * Los apellidos van aparte del nombre desde la ADR-0039: el fichero de
	 * alta de usuarios los pide por separado, y partir un nombre completo por
	 * un espacio se equivoca con los nombres compuestos.
	 *
	 * @param array<string, mixed> $raw   Raw fields: `coordinators`, or the single `coordinator_*` fields.
	 * @param int                  $count How many the procedure asks for.
	 * @return array{errors:string[], fields:string[], data:array<int, array{name:string, surname:string, dni:string, email:string}>}
	 */
	private static function coordinators( array $raw, int $count ): array {
		$filas = is_array( $raw['coordinators'] ?? null ) ? $raw['coordinators'] : array();
		if ( array() === $filas ) {
			$filas[] = array(
				'name'    => $raw['coordinator_name'] ?? '',
				'surname' => $raw['coordinator_surname'] ?? '',
				'dni'     => $raw['coordinator_dni'] ?? '',
				'email'   => $raw['coordinator_email'] ?? '',
			);
		}
		$errors = array();
		$fields = array();
		$data   = array();
		for ( $i = 0; $i < $count; ++$i ) {
			$fila        = is_array( $filas[ $i ] ?? null ) ? $filas[ $i ] : array();
			$coordinator = array(
				'name'    => self::text( $fila, 'name' ),
				'surname' => self::text( $fila, 'surname' ),
				'dni'     => self::dni( self::text( $fila, 'dni' ) ),
				'email'   => strtolower( self::text( $fila, 'email' ) ),
			);
			$checks      = array(
				'coordinator_name'    => '' !== $coordinator['name'],
				'coordinator_surname' => '' !== $coordinator['surname'],
				'coordinator_email'   => ProcedureInput::is_email( $coordinator['email'] ),
				'coordinator_dni'     => self::is_dni( $coordinator['dni'] ),
			);
			foreach ( $checks as $error => $ok ) {
				if ( ! $ok ) {
					$errors[] = $error;
					$fields[] = $error . ':' . $i;
				}
			}
			$data[] = $coordinator;
		}
		return array(
			'errors' => $errors,
			'fields' => $fields,
			'data'   => $data,
		);
	}

	/**
	 * Why an application was refused, in Spanish.
	 *
	 * @param string[]                         $errors    Error codes.
	 * @param array<int, array<string, mixed>> $questions Normalised questions, to name the unanswered ones.
	 * @return string
	 */
	public static function why( array $errors, array $questions = array() ): string {
		$textos = self::texts( $questions );

		$faltan = array();
		foreach ( $errors as $error ) {
			if ( isset( $textos[ $error ] ) && ! in_array( $textos[ $error ], $faltan, true ) ) {
				$faltan[] = $textos[ $error ];
			}
		}

		if ( array() === $faltan ) {
			return 'No se ha podido presentar la solicitud.';
		}
		if ( 1 === count( $faltan ) ) {
			return 'Revise ' . $faltan[0] . '.';
		}

		$ultimo = array_pop( $faltan );
		return 'Revise ' . implode( ', ', $faltan ) . ' y ' . $ultimo . '.';
	}

	/**
	 * What names each failing field, by where it failed.
	 *
	 * Lo que {@see validate()} devuelve en `fields`, con su rótulo: la
	 * coordinación por su número («la persona coordinadora 2»). Lo que no se
	 * sabe nombrar se queda fuera.
	 *
	 * @param string[]                         $fields    What validate() returned in `fields`.
	 * @param array<int, array<string, mixed>> $questions Normalised questions, to name the unanswered ones.
	 * @return array<string, string> Field code => what names it.
	 */
	public static function labels( array $fields, array $questions = array() ): array {
		$textos = self::texts( $questions );
		$out    = array();
		foreach ( $fields as $field ) {
			$partes = explode( ':', (string) $field, 2 );
			$fila   = 0 === strpos( $partes[0], 'coordinator_' ) && isset( $partes[1] ) ? (int) $partes[1] + 1 : 0;
			$texto  = (string) ( $textos[ $fila > 0 ? $partes[0] : (string) $field ] ?? '' );
			if ( '' !== $texto ) {
				$out[ (string) $field ] = $fila > 0 ? str_replace( 'cada persona coordinadora', 'la persona coordinadora ' . $fila, $texto ) : $texto;
			}
		}
		return $out;
	}

	/**
	 * What names each error code, the questions included.
	 *
	 * @param array<int, array<string, mixed>> $questions Normalised questions.
	 * @return array<string, string>
	 */
	private static function texts( array $questions ): array {
		$textos = array(
			'position'            => 'el cargo',
			'coordinator_name'    => 'el nombre de cada persona coordinadora',
			'coordinator_surname' => 'los apellidos de cada persona coordinadora',
			'coordinator_dni'     => 'el DNI o NIE de cada persona coordinadora',
			'coordinator_email'   => 'el correo de cada persona coordinadora',
			'accept'              => 'la aceptación de los compromisos',
		);
		foreach ( $questions as $pregunta ) {
			$textos[ 'answer:' . (string) ( $pregunta['key'] ?? '' ) ] = 'la respuesta a «' . (string) ( $pregunta['label'] ?? '' ) . '»';
		}
		return $textos;
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

	/**
	 * Validate a Spanish DNI or NIE control letter.
	 *
	 * @param string $value DNI or NIE.
	 * @return bool
	 */
	public static function is_dni( string $value ): bool {
		$value = self::dni( $value );
		if ( ! preg_match( '/^(?:[0-9]{8}|[XYZ][0-9]{7})[A-Z]$/', $value ) ) {
			return false;
		}
		$number = strtr(
			substr( $value, 0, -1 ),
			array(
				'X' => '0',
				'Y' => '1',
				'Z' => '2',
			)
		);
		return substr( 'TRWAGMYFPDXBNJZSQVHLCKE', (int) $number % 23, 1 ) === substr( $value, -1 );
	}

	/**
	 * Normalize the document format accepted in the form.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	private static function dni( string $value ): string {
		return strtoupper( (string) preg_replace( '/[ -]/', '', trim( $value ) ) );
	}
}
