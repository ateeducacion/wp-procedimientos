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
 * pregunta: las respuestas no se tocan. Lo que dejaría una respuesta ya dada
 * sin sitio, o le cambiaría el sentido sin avisar, no se guarda
 * ({@see refusal()}, ADR-0051); el rótulo, la ayuda, las opciones nuevas o
 * el rango sí.
 */
final class QuestionChanges {

	public const MOVE_UP   = 'up';
	public const MOVE_DOWN = 'down';

	/**
	 * The checkbox that confirms a change of points on chosen options.
	 */
	public const POINTS_OK_LABEL = 'Aplicar el cambio de puntos a las solicitudes presentadas';

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
	 * Un número con el mínimo por encima del máximo no se guarda nunca. Lo
	 * demás protege lo ya presentado, sea cual sea el estado del procedimiento
	 * (ADR-0050, ADR-0051): de un campo contestado no se cambia el tipo ni la
	 * «Entrega con plazo propio», no se quita el campo, ni una opción que
	 * alguien eligió, ni una fila con cantidades; y cambiar los puntos de una
	 * opción elegida, que cambia la puntuación de quien la eligió, pide
	 * `$points_ok`. El rótulo, la ayuda, el orden, una opción o fila nueva y
	 * una que nadie usó sí cambian.
	 *
	 * @param array<int, array<string, mixed>>   $before    Questions stored now.
	 * @param array<int, array<string, mixed>>   $after     Questions about to be stored.
	 * @param array<string, bool>                $answered  Question keys some application answered.
	 * @param array<string, array<string, bool>> $used      Question key => chosen options or answered row keys.
	 * @param bool                               $points_ok Whether the workshop confirmed a change of points.
	 * @return string
	 */
	public static function refusal( array $before, array $after, array $answered, array $used = array(), bool $points_ok = false ): string {
		foreach ( $after as $pregunta ) {
			// Sin mínimo, el mínimo es cero: un máximo negativo tampoco cabe.
			if ( isset( $pregunta['max'] ) && ( $pregunta['min'] ?? 0 ) > $pregunta['max'] ) {
				return 'En «' . (string) $pregunta['label'] . '» el mínimo es mayor que el máximo. No se han guardado los campos.';
			}
		}
		$despues = array_column( $after, null, 'key' );
		$motivos = array();
		foreach ( $before as $antes ) {
			$key = (string) ( $antes['key'] ?? '' );
			if ( empty( $answered[ $key ] ) ) {
				continue;
			}
			$ahora  = $despues[ $key ] ?? null;
			$motivo = null === $ahora ? 'gone' : self::broken( $antes, $ahora, array_map( 'strval', array_keys( $used[ $key ] ?? array() ) ), $points_ok );
			if ( '' !== $motivo ) {
				$motivos[ $motivo ][] = '«' . (string) ( $ahora['label'] ?? $antes['label'] ?? '' ) . '»';
			}
		}
		foreach ( array( 'type', 'gone', 'delivery', 'choices', 'rows', 'points' ) as $motivo ) {
			if ( isset( $motivos[ $motivo ] ) ) {
				return self::message( $motivo, $motivos[ $motivo ] );
			}
		}
		return '';
	}

	/**
	 * What a change to one answered question would break, or ''.
	 *
	 * @param array<string, mixed> $before    The question stored now.
	 * @param array<string, mixed> $after     The same key about to be stored.
	 * @param string[]             $used      Chosen options, or answered row keys.
	 * @param bool                 $points_ok Whether a change of points was confirmed.
	 * @return string A reason {@see message()} knows, or ''.
	 */
	private static function broken( array $before, array $after, array $used, bool $points_ok ): string {
		$tipo = (string) ( $before['type'] ?? '' );
		if ( (string) ( $after['type'] ?? '' ) !== $tipo ) {
			return 'type';
		}
		if ( ProcedureQuestions::is_delivery( $before ) !== ProcedureQuestions::is_delivery( $after ) ) {
			return 'delivery';
		}
		if ( ProcedureQuestions::has_choices( $tipo ) && array() !== array_diff( $used, array_map( 'strval', (array) ( $after['choices'] ?? array() ) ) ) ) {
			return 'choices';
		}
		if ( ProcedureQuestions::TYPE_GRID === $tipo && array() !== array_diff( $used, array_map( 'strval', array_column( (array) ( $after['rows'] ?? array() ), 'key' ) ) ) ) {
			return 'rows';
		}
		if ( ! $points_ok && ProcedureQuestions::TYPE_MULTIPLE === $tipo && self::points_of( $before, $used ) !== self::points_of( $after, $used ) ) {
			return 'points';
		}
		return '';
	}

	/**
	 * The points of the given options of a «varias opciones» question, by text.
	 *
	 * @param array<string, mixed> $question Normalised question.
	 * @param string[]             $options  Option texts.
	 * @return array<string, int>
	 */
	private static function points_of( array $question, array $options ): array {
		$puntos = (array) ( $question['points'] ?? array() );
		$out    = array();
		foreach ( array_values( (array) ( $question['choices'] ?? array() ) ) as $i => $texto ) {
			if ( in_array( (string) $texto, $options, true ) ) {
				$out[ (string) $texto ] = (int) ( $puntos[ $i ] ?? 0 );
			}
		}
		ksort( $out );
		return $out;
	}

	/**
	 * The refusal, in the workshop's words.
	 *
	 * @param string   $reason A reason from {@see broken()}, or `gone`.
	 * @param string[] $labels Quoted labels of the questions.
	 * @return string
	 */
	private static function message( string $reason, array $labels ): string {
		$lista  = implode( ', ', $labels );
		$lo     = count( $labels ) > 1 ? 'los' : 'lo';
		$textos = array(
			'type'     => 'No se puede cambiar el tipo de ' . $lista . ': ya hay solicitudes que ' . $lo . ' han contestado. Si necesita otro tipo, cree un campo nuevo.',
			'gone'     => 'No se puede quitar ' . $lista . ': ya hay solicitudes que ' . $lo . ' han contestado, y sus respuestas dejarían de verse. Corrija su título si hace falta.',
			'delivery' => 'No se puede cambiar «Entrega con plazo propio» en ' . $lista . ': ya hay documentos entregados. Si necesita otra entrega, cree un campo nuevo.',
			'choices'  => 'En ' . $lista . ' falta una opción que ya han elegido algunas solicitudes. Una opción elegida no se quita ni cambia de texto: déjela como estaba y, si hace falta, añada otra.',
			'rows'     => 'En ' . $lista . ' falta una fila que ya tiene cantidades en alguna solicitud. Una fila con cantidades no se quita: para cambiar su nombre, escríbalo en su misma línea.',
			'points'   => 'En ' . $lista . ' cambian los puntos de opciones que ya han elegido algunas solicitudes, y con ellos su puntuación. Si es lo que quiere, marque «' . self::POINTS_OK_LABEL . '» y guarde otra vez.',
		);
		return $textos[ $reason ] . ' No se han guardado los campos.';
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
				return 'En ' . $rotulo . ' la opción «' . $mal . '» lleva unos puntos que no son un número entero de 0 en adelante. No se han guardado los campos.';
			}
			foreach ( array(
				'min' => 'mínimo',
				'max' => 'máximo',
			) as $limite => $nombre ) {
				$valor = is_scalar( $pregunta[ $limite ] ?? null ) ? trim( (string) $pregunta[ $limite ] ) : '';
				if ( ProcedureQuestions::TYPE_NUMBER === $tipo && '' !== $valor && null === ProcedureQuestions::integer( $valor ) ) {
					return 'En ' . $rotulo . ' el ' . $nombre . ' tiene que ser un número entero. No se han guardado los campos.';
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
