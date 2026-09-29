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
 * aceptación de los compromisos, la persona coordinadora si se pide, y las
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
	 *                                        `requires_coordinator` (bool), `commitments` (string)
	 *                                        and `questions` (normalised list).
	 * @return array{ok:bool, errors:string[], data:array<string, mixed>}
	 */
	public static function validate( array $raw, array $procedure ): array {
		$errors = array();

		$position = self::text( $raw, 'position' );
		if ( ! isset( ApplicationMetaKeys::positions()[ $position ] ) ) {
			$errors[] = 'position';
			$position = '';
		}

		$coordinator = array(
			'name'  => '',
			'email' => '',
		);
		if ( ! empty( $procedure['requires_coordinator'] ) ) {
			$coordinator['name']  = self::text( $raw, 'coordinator_name' );
			$coordinator['email'] = strtolower( self::text( $raw, 'coordinator_email' ) );
			if ( '' === $coordinator['name'] ) {
				$errors[] = 'coordinator_name';
			}
			if ( ! ProcedureInput::is_email( $coordinator['email'] ) ) {
				$errors[] = 'coordinator_email';
			}
		}

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
				'coordinator' => $coordinator,
				'accept'      => $accepted,
				'answers'     => $respuesta['data'],
			),
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
			'position'          => 'el cargo',
			'coordinator_name'  => 'el nombre de la persona coordinadora',
			'coordinator_email' => 'el correo de la persona coordinadora',
			'accept'            => 'la aceptación de los compromisos',
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
}
