<?php
/**
 * Pure handling of the points of «varias opciones» questions.
 *
 * @package Prc
 */

namespace Prc\Domain;

/**
 * Los puntos de las opciones de una pregunta de «varias opciones» (ADR-0040).
 *
 * Pura: ni una llamada a WordPress.
 *
 * Una opción escrita `Texto | 3` vale 3 y se llama «Texto»; sin `|`, vale 0.
 * Solo cuenta el último `| entero`, así que «A | B | 2» se llama «A | B».
 * Quien solicita ve y marca el texto, y lo que se guarda es el texto: unas
 * respuestas guardadas antes de poner puntos siguen casando, y cambiar los
 * puntos después no toca ninguna respuesta.
 *
 * La puntuación de una solicitud no se guarda: se calcula al leer, con los
 * puntos de hoy.
 *
 * Aquí vive también la validación de una respuesta de opciones, que es lo
 * que casa lo marcado con el texto de cada opción.
 */
final class QuestionPoints {

	/**
	 * The choices of a question and, for «varias opciones», their points.
	 *
	 * Lo que llega del taller es texto, una opción por línea, y de ahí salen
	 * los puntos. Lo ya normalizado trae `points` aparte, un entero por
	 * opción, y entonces el texto se toma tal cual: así normalizar dos veces
	 * no cambia nada. Sin ninguna opción con puntos, no hay `points`.
	 *
	 * @param string               $type Question type, one with choices.
	 * @param array<string, mixed> $raw  Raw question.
	 * @return array{choices:string[], points?:int[]}
	 */
	public static function choices( string $type, array $raw ): array {
		$valor = $raw['choices'] ?? array();
		if ( ProcedureQuestions::TYPE_MULTIPLE !== $type ) {
			return array( 'choices' => ProcedureQuestions::choices( $valor ) );
		}

		$con    = isset( $raw['points'] ) && is_array( $raw['points'] );
		$textos = array();
		$valen  = array();
		foreach ( self::items( $valor, $con ? array_values( $raw['points'] ) : null ) as $una ) {
			$con = $con || $una[2];
			if ( '' === $una[0] || in_array( $una[0], $textos, true ) || count( $textos ) >= ProcedureQuestions::MAX_CHOICES ) {
				continue;
			}
			$textos[] = $una[0];
			$valen[]  = $una[1];
		}
		if ( ! $con ) {
			return array( 'choices' => $textos );
		}
		return array(
			'choices' => $textos,
			'points'  => $valen,
		);
	}

	/**
	 * A choice answer: one of the list, or several of it without repeats.
	 *
	 * @param array<string, mixed> $question Normalised question with choices.
	 * @param mixed                $value    Raw answer.
	 * @return array{0:string|string[], 1:bool}
	 */
	public static function answer( array $question, $value ): array {
		$opciones = (array) ( $question['choices'] ?? array() );
		$obliga   = ! empty( $question['required'] );
		if ( ProcedureQuestions::TYPE_SINGLE === ( $question['type'] ?? '' ) ) {
			$elegida = is_scalar( $value ) ? trim( (string) $value ) : '';
			$elegida = in_array( $elegida, $opciones, true ) ? $elegida : '';
			return array( $elegida, $obliga && '' === $elegida );
		}
		$limpias = array();
		foreach ( is_array( $value ) ? $value : array() as $una ) {
			$una = is_scalar( $una ) ? trim( (string) $una ) : '';
			if ( in_array( $una, $opciones, true ) && ! in_array( $una, $limpias, true ) ) {
				$limpias[] = $una;
			}
		}
		return array( $limpias, $obliga && array() === $limpias );
	}

	/**
	 * Each choice as text, points and whether the points were written.
	 *
	 * @param mixed      $value  Raw choices: an array or one per line.
	 * @param int[]|null $points Points by position, when already normalised.
	 * @return array<int, array{0:string, 1:int, 2:bool}>
	 */
	private static function items( $value, ?array $points ): array {
		$lineas = is_string( $value ) ? preg_split( '/\r\n|\r|\n/', $value ) : $value;
		$out    = array();
		foreach ( is_array( $lineas ) ? array_values( $lineas ) : array() as $i => $linea ) {
			if ( ! is_scalar( $linea ) ) {
				continue;
			}
			$texto = trim( (string) $linea );
			$valor = $points[ $i ] ?? 0;
			$out[] = null === $points ? self::split( $texto ) : array( $texto, is_scalar( $valor ) ? (int) $valor : 0, true );
		}
		return $out;
	}

	/**
	 * One choice line split into its text and its points.
	 *
	 * @param string $line Trimmed line.
	 * @return array{0:string, 1:int, 2:bool} Text, points, whether the line said them.
	 */
	private static function split( string $line ): array {
		if ( preg_match( '/^(.*?)\s*\|\s*(\d{1,6})$/u', $line, $partes ) && '' !== trim( $partes[1] ) ) {
			return array( trim( $partes[1] ), (int) $partes[2], true );
		}
		return array( $line, 0, false );
	}

	/**
	 * The choices as the workshop writes them back: `Texto | 3` when there are points.
	 *
	 * @param array<string, mixed> $question Normalised question.
	 * @return string[]
	 */
	public static function lines( array $question ): array {
		$textos = array_map( 'strval', (array) ( $question['choices'] ?? array() ) );
		if ( ! isset( $question['points'] ) || ! is_array( $question['points'] ) ) {
			return $textos;
		}
		$out = array();
		foreach ( $textos as $i => $texto ) {
			$out[] = $texto . ' | ' . (int) ( $question['points'][ $i ] ?? 0 );
		}
		return $out;
	}

	/**
	 * Whether any question of the list gives points.
	 *
	 * @param array<int, array<string, mixed>> $questions Normalised questions.
	 * @return bool
	 */
	public static function has_points( array $questions ): bool {
		foreach ( $questions as $pregunta ) {
			if ( ProcedureQuestions::TYPE_MULTIPLE === ( $pregunta['type'] ?? '' ) && isset( $pregunta['points'] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * The score of one set of answers: the points of the ticked choices.
	 *
	 * Una opción marcada que ya no está en la lista no suma.
	 *
	 * @param array<int, array<string, mixed>> $questions Normalised questions.
	 * @param array<string, mixed>             $answers   Stored answers, keyed by question key.
	 * @return int|null Null when no question gives points.
	 */
	public static function score( array $questions, array $answers ): ?int {
		if ( ! self::has_points( $questions ) ) {
			return null;
		}
		$total = 0;
		foreach ( $questions as $pregunta ) {
			$vale = self::values( $pregunta );
			foreach ( (array) ( $answers[ (string) ( $pregunta['key'] ?? '' ) ] ?? array() ) as $marcada ) {
				$total += is_scalar( $marcada ) ? (int) ( $vale[ (string) $marcada ] ?? 0 ) : 0;
			}
		}
		return $total;
	}

	/**
	 * What each choice of a question is worth.
	 *
	 * @param array<string, mixed> $question Normalised question.
	 * @return array<string, int> Choice text => points; empty without points.
	 */
	private static function values( array $question ): array {
		$choices = array_map( 'strval', (array) ( $question['choices'] ?? array() ) );
		$points  = array_map( 'intval', (array) ( $question['points'] ?? array() ) );
		if ( ProcedureQuestions::TYPE_MULTIPLE !== ( $question['type'] ?? '' ) || count( $choices ) !== count( $points ) ) {
			return array();
		}
		return array_combine( $choices, $points );
	}
}
