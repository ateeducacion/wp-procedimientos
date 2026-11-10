<?php
/**
 * Pure handling of grid questions: one count per row.
 *
 * @package Prc
 */

namespace Prc\Domain;

/**
 * Las preguntas de rejilla: una cantidad entera por fila (ADR-0040).
 *
 * Pura: ni una llamada a WordPress.
 *
 * Las filas se escriben una por línea, hasta {@see ProcedureQuestions::MAX_ROWS},
 * y cada una lleva una clave que genera el aplicativo (`r` + número), igual
 * que las preguntas: las cantidades se guardan bajo ella, así que renombrar
 * una fila no descoloca nada. `row_seq` es la mayor clave que ha tenido la
 * rejilla y nunca baja: una fila nueva no hereda la clave de una borrada.
 *
 * La respuesta guardada es `{ r1: 3, r2: '' }`: una fila sin cantidad se
 * guarda vacía, no como cero.
 */
final class QuestionGrid {

	/**
	 * Whether a key is a row key we could have generated.
	 *
	 * @param string $key Row key.
	 * @return bool
	 */
	public static function is_row_key( string $key ): bool {
		return (bool) preg_match( '/^r[1-9]\d{0,3}$/', $key );
	}

	/**
	 * Normalise the rows of a grid and key the new ones.
	 *
	 * Como las preguntas: una fila sin texto o repetida se cae, y la que llega
	 * sin clave recibe la siguiente a `row_seq`.
	 *
	 * @param mixed $raw Rows: one per line, or a list of `{key, label}`.
	 * @param int   $seq Highest row number this grid ever had.
	 * @return array{rows:array<int, array{key:string, label:string}>, row_seq:int}
	 */
	public static function rows( $raw, int $seq ): array {
		if ( is_string( $raw ) ) {
			$raw = preg_split( '/\r\n|\r|\n/', $raw );
		}
		$filas = array();
		$mayor = max( 0, $seq );
		foreach ( is_array( $raw ) ? $raw : array() as $item ) {
			$fila = self::row( $item );
			if ( '' === $fila['label'] || in_array( $fila['label'], array_column( $filas, 'label' ), true ) || count( $filas ) >= ProcedureQuestions::MAX_ROWS ) {
				continue;
			}
			if ( in_array( $fila['key'], array_column( $filas, 'key' ), true ) ) {
				$fila['key'] = '';
			}
			$mayor   = max( $mayor, (int) substr( $fila['key'], 1 ) );
			$filas[] = $fila;
		}
		foreach ( $filas as $i => $fila ) {
			if ( '' === $fila['key'] ) {
				++$mayor;
				$filas[ $i ]['key'] = 'r' . $mayor;
			}
		}
		return array(
			'rows'    => $filas,
			'row_seq' => $mayor,
		);
	}

	/**
	 * One raw row as `{key, label}`, with a key only if it is one.
	 *
	 * @param mixed $item A line, or a `{key, label}` array.
	 * @return array{key:string, label:string}
	 */
	private static function row( $item ): array {
		if ( ! is_array( $item ) ) {
			return array(
				'key'   => '',
				'label' => is_scalar( $item ) ? trim( (string) $item ) : '',
			);
		}
		$key = is_scalar( $item['key'] ?? null ) ? strtolower( trim( (string) $item['key'] ) ) : '';
		return array(
			'key'   => self::is_row_key( $key ) ? $key : '',
			'label' => is_scalar( $item['label'] ?? null ) ? trim( (string) $item['label'] ) : '',
		);
	}

	/**
	 * The rows typed in the workshop, one per line, matched to the rows the grid had.
	 *
	 * Lo que se escribe es texto, así que la clave de cada fila se deduce:
	 * primero, una línea igual que una fila de antes es esa fila; después,
	 * una línea cambiada es la fila que ocupaba su misma línea —renombrar—.
	 * Esto segundo solo si no se ha quitado ninguna línea: con una menos no
	 * se sabe cuál se fue, y una fila renombrada pasa por nueva antes que
	 * heredar las cantidades de otra. Por eso el taller pide dejar en blanco
	 * la línea de la fila que se quita.
	 *
	 * @param string                           $text     What was typed, one row per line.
	 * @param array<int, array<string, mixed>> $previous Rows the grid had, in the order they were shown.
	 * @return array<int, array{key:string, label:string}> Key '' for a new row.
	 */
	public static function match_rows( string $text, array $previous ): array {
		$lineas = array_map( 'trim', (array) preg_split( '/\r\n|\r|\n/', $text ) );
		$antes  = array_values( array_filter( $previous, 'is_array' ) );
		$claves = self::same_text( $lineas, $antes );
		$usadas = array_flip( array_filter( $claves, 'strlen' ) );

		$en_su_sitio = count( $lineas ) >= count( $antes );
		$out         = array();
		foreach ( $lineas as $i => $linea ) {
			if ( '' === $linea ) {
				continue;
			}
			$suya = (string) ( $antes[ $i ]['key'] ?? '' );
			if ( ! isset( $claves[ $i ] ) && $en_su_sitio && '' !== $suya && ! isset( $usadas[ $suya ] ) ) {
				$claves[ $i ]    = $suya;
				$usadas[ $suya ] = true;
			}
			$out[] = array(
				'key'   => $claves[ $i ] ?? '',
				'label' => $linea,
			);
		}
		return $out;
	}

	/**
	 * The key of each line written exactly as a row of before, each row used once.
	 *
	 * @param string[]                         $lines    Trimmed lines.
	 * @param array<int, array<string, mixed>> $previous Rows of before.
	 * @return array<int, string> Line index => row key.
	 */
	private static function same_text( array $lines, array $previous ): array {
		$libres = array_column( $previous, 'label', 'key' );
		$out    = array();
		foreach ( $lines as $i => $linea ) {
			$key = '' !== $linea ? array_search( $linea, $libres, true ) : false;
			if ( false !== $key ) {
				$out[ $i ] = (string) $key;
				unset( $libres[ $key ] );
			}
		}
		return $out;
	}

	/**
	 * A grid answer: one count, zero or more, per row of today.
	 *
	 * Una fila sin cantidad se guarda vacía y no como cero. Obligatoria,
	 * todas las filas llevan cantidad. Una fila que ya no existe no entra.
	 *
	 * @param array<string, mixed> $question Normalised question.
	 * @param mixed                $value    Raw answer, keyed by row key.
	 * @return array{0:array<string, int|string>, 1:bool}
	 */
	public static function answer( array $question, $value ): array {
		$dadas  = is_array( $value ) ? $value : array();
		$mal    = ! is_array( $value ) && null !== $value && '' !== $value;
		$obliga = ! empty( $question['required'] );
		$out    = array();
		foreach ( (array) ( $question['rows'] ?? array() ) as $fila ) {
			$rk         = (string) $fila['key'];
			$texto      = is_scalar( $dadas[ $rk ] ?? null ) ? trim( (string) $dadas[ $rk ] ) : '';
			$n          = ProcedureQuestions::integer( $texto );
			$bien       = null !== $n && $n >= 0;
			$out[ $rk ] = $bien ? $n : $texto;
			$mal        = $mal || ( '' === $texto ? $obliga : ! $bien );
		}
		return array( $out, $mal );
	}

	/**
	 * A grid answer in one line: «Infantil: 3; Primaria: 0», answered rows only.
	 *
	 * @param array<string, mixed> $question Normalised question.
	 * @param mixed                $answer   Stored answer.
	 * @return string
	 */
	public static function text( array $question, $answer ): string {
		$dadas = is_array( $answer ) ? $answer : array();
		$out   = array();
		foreach ( (array) ( $question['rows'] ?? array() ) as $fila ) {
			$valor = $dadas[ (string) $fila['key'] ] ?? '';
			if ( is_scalar( $valor ) && '' !== (string) $valor ) {
				$out[] = (string) $fila['label'] . ': ' . (string) $valor;
			}
		}
		return implode( '; ', $out );
	}

	/**
	 * The export columns of a grid: one per row, keyed `q4:r1`.
	 *
	 * @param array<string, mixed> $question Normalised question.
	 * @return array<string, string> Column key => «Pregunta — Fila»; empty for any other type.
	 */
	public static function columns( array $question ): array {
		$out = array();
		if ( ProcedureQuestions::TYPE_GRID !== ( $question['type'] ?? '' ) ) {
			return $out;
		}
		foreach ( (array) ( $question['rows'] ?? array() ) as $fila ) {
			$out[ (string) $question['key'] . ':' . (string) $fila['key'] ] = (string) $question['label'] . ' — ' . (string) $fila['label'];
		}
		return $out;
	}

	/**
	 * Table columns with each grid opened into one column per row, in its place.
	 *
	 * Es lo que ofrece la exportación: la tabla del taller lleva la rejilla en
	 * una celda y el CSV, una columna por fila. Una rejilla sin filas se queda
	 * en su columna.
	 *
	 * @param array<string, string>            $columns   Table columns, key => heading.
	 * @param array<int, array<string, mixed>> $questions Normalised questions.
	 * @return array<string, string> key => heading.
	 */
	public static function expand( array $columns, array $questions ): array {
		$rejillas = array();
		foreach ( $questions as $pregunta ) {
			$rejillas[ (string) ( $pregunta['key'] ?? '' ) ] = self::columns( $pregunta );
		}
		$out = array();
		foreach ( $columns as $clave => $rotulo ) {
			$out += array() !== ( $rejillas[ $clave ] ?? array() ) ? $rejillas[ $clave ] : array( $clave => $rotulo );
		}
		return $out;
	}

	/**
	 * The cells of a grid answer, keyed like {@see columns()}.
	 *
	 * @param array<string, mixed> $question Normalised question.
	 * @param mixed                $answer   Stored answer.
	 * @return array<string, string>
	 */
	public static function cells( array $question, $answer ): array {
		$dadas = is_array( $answer ) ? $answer : array();
		$out   = array();
		foreach ( (array) ( $question['rows'] ?? array() ) as $fila ) {
			$valor = $dadas[ (string) $fila['key'] ] ?? '';
			$out[ (string) $question['key'] . ':' . (string) $fila['key'] ] = is_scalar( $valor ) ? (string) $valor : '';
		}
		return $out;
	}
}
