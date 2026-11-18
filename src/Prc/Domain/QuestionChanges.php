<?php
/**
 * Pure checks on how the workshop changes a question list.
 *
 * @package Prc
 */

namespace Prc\Domain;

/**
 * Lo que el taller cambia en la lista de preguntas de un procedimiento (ADR-0040).
 *
 * Pura: ni una llamada a WordPress.
 *
 * El orden se cambia de una en una, subiendo o bajando, y la `key` va con la
 * pregunta: las respuestas no se tocan. El tipo de una pregunta que ya tiene
 * respuestas no se cambia; el rótulo, la ayuda, las opciones o el rango sí.
 */
final class QuestionChanges {

	public const MOVE_UP   = 'up';
	public const MOVE_DOWN = 'down';

	/**
	 * Move one question a place up or down; the key goes with it.
	 *
	 * En los extremos, o con una clave que no está, devuelve la lista tal cual.
	 *
	 * @param array<int, array<string, mixed>> $questions Normalised questions.
	 * @param string                           $key       Question key.
	 * @param string                           $direction {@see MOVE_UP} or {@see MOVE_DOWN}.
	 * @return array<int, array<string, mixed>>
	 */
	public static function move( array $questions, string $key, string $direction ): array {
		$desde = array_search( $key, array_column( $questions, 'key' ), true );
		$paso  = array(
			self::MOVE_UP   => -1,
			self::MOVE_DOWN => 1,
		)[ $direction ] ?? 0;
		$lista = array_values( $questions );
		if ( false === $desde || 0 === $paso || ! isset( $lista[ $desde + $paso ] ) ) {
			return $questions;
		}
		$otra                    = $lista[ $desde + $paso ];
		$lista[ $desde + $paso ] = $lista[ $desde ];
		$lista[ $desde ]         = $otra;
		return $lista;
	}

	/**
	 * Why a new question list cannot replace the old one, or '' when it can.
	 *
	 * Dos motivos: un número con el mínimo por encima del máximo, y el tipo de
	 * una pregunta que ya tiene respuestas. Lo segundo no se arregla al leer
	 * —«tres» no es un número—, así que se para antes de guardar. El rótulo,
	 * la ayuda, las opciones o el rango sí cambian.
	 *
	 * @param array<int, array<string, mixed>> $before   Questions stored now.
	 * @param array<int, array<string, mixed>> $after    Questions about to be stored.
	 * @param array<string, bool>              $answered Question keys some application answered.
	 * @return string
	 */
	public static function refusal( array $before, array $after, array $answered ): string {
		$tipos     = array_column( $before, 'type', 'key' );
		$cambiadas = array();
		foreach ( $after as $pregunta ) {
			$key = (string) ( $pregunta['key'] ?? '' );
			// Sin mínimo, el mínimo es cero: un máximo negativo tampoco cabe.
			if ( isset( $pregunta['max'] ) && ( $pregunta['min'] ?? 0 ) > $pregunta['max'] ) {
				return 'En «' . (string) $pregunta['label'] . '» el mínimo es mayor que el máximo. No se han guardado las preguntas.';
			}
			if ( isset( $tipos[ $key ] ) && $tipos[ $key ] !== $pregunta['type'] && ! empty( $answered[ $key ] ) ) {
				$cambiadas[] = '«' . (string) $pregunta['label'] . '»';
			}
		}
		if ( array() === $cambiadas ) {
			return '';
		}
		return 'No se puede cambiar el tipo de ' . implode( ', ', $cambiadas )
			. ': ya hay solicitudes que la han contestado. Si necesita otro tipo, cree una pregunta nueva. No se han guardado las preguntas.';
	}

	/**
	 * Why what the workshop typed cannot even be read, or '' when it can.
	 *
	 * Se mira lo tecleado, antes de normalizar: unos puntos mal escritos tras
	 * el último `|` de una opción de «varias opciones» («B | -2», «C | 1.5»)
	 * se guardarían como parte del texto, y un mínimo o un máximo que no es
	 * un entero se perdería sin aviso. Lo que no parece un número tras el `|`
	 * («A | B») es texto, y vale.
	 *
	 * Solo se revisan las líneas nuevas o cambiadas: una opción guardada antes
	 * de los puntos («Nota media | 7,5») vuelve tal cual, o como la escribe
	 * {@see QuestionPoints::lines()}, y no impide guardar el panel. Una
	 * pregunta sin rótulo se borra, y no se revisa.
	 *
	 * @param array<int, array<string, mixed>> $raw    Questions as the workshop sent them.
	 * @param array<int, array<string, mixed>> $before Questions stored now.
	 * @return string
	 */
	public static function input_errors( array $raw, array $before = array() ): string {
		$guardadas = array_column( $before, null, 'key' );
		foreach ( $raw as $pregunta ) {
			if ( '' === trim( (string) ( $pregunta['label'] ?? '' ) ) ) {
				continue;
			}
			$rotulo = '«' . (string) ( $pregunta['label'] ?? '' ) . '»';
			$tipo   = (string) ( $pregunta['type'] ?? '' );
			$antes  = $guardadas[ (string) ( $pregunta['key'] ?? '' ) ] ?? array();
			$mal    = ProcedureQuestions::TYPE_MULTIPLE === $tipo ? self::bad_points( $pregunta['choices'] ?? '', self::known_lines( $antes ) ) : '';
			if ( '' !== $mal ) {
				return 'En ' . $rotulo . ' la opción «' . $mal . '» lleva unos puntos que no son un número entero de 0 en adelante. No se han guardado las preguntas.';
			}
			foreach ( array(
				'min' => 'mínimo',
				'max' => 'máximo',
			) as $limite => $nombre ) {
				$valor = is_scalar( $pregunta[ $limite ] ?? null ) ? trim( (string) $pregunta[ $limite ] ) : '';
				if ( ProcedureQuestions::TYPE_NUMBER === $tipo && '' !== $valor && null === ProcedureQuestions::integer( $valor ) ) {
					return 'En ' . $rotulo . ' el ' . $nombre . ' tiene que ser un número entero. No se han guardado las preguntas.';
				}
			}
		}
		return '';
	}

	/**
	 * The choice lines a stored question already had: as stored and as the workshop writes them.
	 *
	 * @param array<string, mixed> $question Stored question, or empty.
	 * @return string[]
	 */
	private static function known_lines( array $question ): array {
		if ( array() === $question ) {
			return array();
		}
		return array_merge( array_map( 'strval', (array) ( $question['choices'] ?? array() ) ), QuestionPoints::lines( $question ) );
	}

	/**
	 * The first new choice line whose points, after the last `|`, look like a number but are not one.
	 *
	 * @param mixed    $choices The workshop's text, one choice per line.
	 * @param string[] $known   Lines the stored question already had; not checked.
	 * @return string The line, or ''.
	 */
	private static function bad_points( $choices, array $known ): string {
		foreach ( is_string( $choices ) ? (array) preg_split( '/\r\n|\r|\n/', $choices ) : array() as $linea ) {
			$linea = trim( (string) $linea );
			$cola  = trim( (string) substr( (string) strrchr( $linea, '|' ), 1 ) );
			if ( in_array( $linea, $known, true ) ) {
				continue;
			}
			if ( false !== strpos( $linea, '|' ) && preg_match( '/^[-+]?[\d.,]*\d[\d.,]*$/', $cola ) && ! preg_match( '/^\d{1,6}$/', $cola ) ) {
				return $linea;
			}
		}
		return '';
	}
}
