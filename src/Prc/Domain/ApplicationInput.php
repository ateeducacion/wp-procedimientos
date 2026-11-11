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
	 * @return array{ok:bool, errors:string[], data:array<string, mixed>}
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
	 * @return array{errors:string[], data:array<int, array{name:string, surname:string, dni:string, email:string}>}
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
				}
			}
			$data[] = $coordinator;
		}
		return array(
			'errors' => $errors,
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
