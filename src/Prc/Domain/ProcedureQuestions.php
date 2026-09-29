<?php
/**
 * Pure handling of the per-procedure questions and their answers.
 *
 * @package Prc
 */

namespace Prc\Domain;

/**
 * Las preguntas propias de un procedimiento, y lo que se contesta a ellas.
 *
 * Pura: ni una llamada a WordPress.
 *
 * Hoy las «opciones» de una convocatoria son cuatro pares título/indicaciones
 * con respuesta libre, más un subformulario de casillas o lista, más —si no
 * basta— un formulario anexo clonado de una plantilla por convocatoria. Aquí
 * son una lista de hasta veinte preguntas de cinco tipos (ADR-0019, y `file`
 * desde la ADR-0028), y el
 * tipo es toda la validación que existe: ni condiciones ni reglas propias.
 *
 * Cada pregunta lleva una `key` que genera el aplicativo (`q` + número) y que
 * no cambia al reordenar: las respuestas se guardan bajo ella, así que
 * reescribir un rótulo no desconecta lo ya contestado.
 */
final class ProcedureQuestions {

	/**
	 * Cuántas preguntas como mucho.
	 */
	public const MAX = 20;

	/**
	 * Cuántas opciones como mucho en una pregunta.
	 */
	public const MAX_CHOICES = 50;

	/**
	 * Cuántos caracteres como mucho en una respuesta libre.
	 */
	public const TEXT_MAX = 2000;

	public const TYPE_TEXT     = 'text';
	public const TYPE_SINGLE   = 'single';
	public const TYPE_MULTIPLE = 'multiple';
	public const TYPE_YESNO    = 'yesno';

	/**
	 * Pedir un documento: una autorización, un certificado, un acta.
	 *
	 * Añadido por la ADR-0028, que también decide dónde vive el fichero: en el
	 * almacén privado del aplicativo y **no** como adjunto de WordPress. La
	 * raya de la ADR-0019 sigue donde estaba: un `file` tiene rótulo,
	 * indicaciones, tipo y si es obligatorio, **y nada más**. Ni tamaño, ni
	 * tipos, ni varios ficheros por pregunta: eso es del aplicativo entero y
	 * vive en {@see \Prc\PublicFront\ApplicationFiles}.
	 */
	public const TYPE_FILE = 'file';

	/**
	 * The five question types, with their label.
	 *
	 * @return array<string, string>
	 */
	public static function types(): array {
		return array(
			self::TYPE_TEXT     => 'Respuesta libre',
			self::TYPE_SINGLE   => 'Una opción',
			self::TYPE_MULTIPLE => 'Varias opciones',
			self::TYPE_YESNO    => 'Sí o no',
			self::TYPE_FILE     => 'Archivo',
		);
	}

	/**
	 * Whether a question type carries a list of choices.
	 *
	 * @param string $type Question type.
	 * @return bool
	 */
	public static function has_choices( string $type ): bool {
		return self::TYPE_SINGLE === $type || self::TYPE_MULTIPLE === $type;
	}

	/**
	 * Whether a key is one we could have generated.
	 *
	 * @param string $key Question key.
	 * @return bool
	 */
	public static function is_key( string $key ): bool {
		return (bool) preg_match( '/^q[1-9]\d{0,3}$/', $key );
	}

	/**
	 * Normalise a question list: shape, closed types, capped, keyed.
	 *
	 * Lo que llega puede venir del constructor, de una versión anterior o
	 * estar a medias: se normaliza siempre y lo que no se entiende se cae. Una
	 * pregunta sin rótulo no es una pregunta. Las que llegan sin `key` —recién
	 * creadas— reciben la siguiente libre, contando desde la mayor vista, así
	 * que borrar una y crear otra nunca reutiliza una clave con respuestas.
	 *
	 * @param mixed $raw Raw list.
	 * @return array<int, array{key:string, label:string, help:string, type:string, required:bool, choices:string[]}>
	 */
	public static function sanitize( $raw ): array {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$out    = array();
		$vistas = array();
		$mayor  = 0;
		foreach ( $raw as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$pregunta = self::one( $item );
			if ( '' === $pregunta['label'] || isset( $vistas[ $pregunta['key'] ] ) ) {
				continue;
			}
			if ( '' !== $pregunta['key'] ) {
				$vistas[ $pregunta['key'] ] = true;
				$mayor                      = max( $mayor, (int) substr( $pregunta['key'], 1 ) );
			}
			$out[] = $pregunta;
			if ( count( $out ) >= self::MAX ) {
				break;
			}
		}

		foreach ( $out as $i => $pregunta ) {
			if ( '' === $pregunta['key'] ) {
				++$mayor;
				$out[ $i ]['key'] = 'q' . $mayor;
			}
		}
		return $out;
	}

	/**
	 * Normalise one question.
	 *
	 * @param array<string, mixed> $raw Raw question.
	 * @return array{key:string, label:string, help:string, type:string, required:bool, choices:string[]}
	 */
	private static function one( array $raw ): array {
		$type = isset( $raw['type'] ) && is_scalar( $raw['type'] ) ? (string) $raw['type'] : '';
		if ( ! isset( self::types()[ $type ] ) ) {
			$type = self::TYPE_TEXT;
		}
		$key = isset( $raw['key'] ) && is_scalar( $raw['key'] ) ? strtolower( trim( (string) $raw['key'] ) ) : '';

		return array(
			'key'      => self::is_key( $key ) ? $key : '',
			'label'    => self::text( $raw, 'label' ),
			'help'     => self::text( $raw, 'help' ),
			'type'     => $type,
			'required' => ! empty( $raw['required'] ),
			'choices'  => self::has_choices( $type ) ? self::choices( $raw['choices'] ?? array() ) : array(),
		);
	}

	/**
	 * Normalise a list of choices: trimmed, no blanks, no repeats, capped.
	 *
	 * @param mixed $raw Raw choices: an array or one per line.
	 * @return string[]
	 */
	public static function choices( $raw ): array {
		if ( is_string( $raw ) ) {
			$raw = preg_split( '/\r\n|\r|\n/', $raw );
		}
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$out = array();
		foreach ( $raw as $opcion ) {
			if ( ! is_scalar( $opcion ) ) {
				continue;
			}
			$opcion = trim( (string) $opcion );
			if ( '' === $opcion || in_array( $opcion, $out, true ) ) {
				continue;
			}
			$out[] = $opcion;
			if ( count( $out ) >= self::MAX_CHOICES ) {
				break;
			}
		}
		return $out;
	}

	/**
	 * Validate the answers of one school against the questions of the procedure.
	 *
	 * El tipo es toda la validación que hay: una opción de la lista es una de
	 * la lista, y un texto es un texto de hasta {@see TEXT_MAX} caracteres.
	 *
	 * @param array<int, array<string, mixed>> $questions Normalised questions.
	 * @param array<string, mixed>             $answers   Raw answers, keyed by question key.
	 * @return array{ok:bool, errors:string[], data:array<string, mixed>}
	 */
	public static function validate_answers( array $questions, array $answers ): array {
		$errores = array();
		$datos   = array();

		foreach ( $questions as $pregunta ) {
			$key   = (string) ( $pregunta['key'] ?? '' );
			$valor = $answers[ $key ] ?? null;

			// Un documento no pasa por aquí, ni para validarse ni para
			// guardarse: esto es puro y no lee `$_FILES`. El fichero es del
			// borde de la aplicación, se guarda en su propia meta y lo
			// comprueba `PublicFront\ApplicationFiles` (ADR-0028).
			if ( self::TYPE_FILE === ( $pregunta['type'] ?? '' ) ) {
				continue;
			}

			switch ( $pregunta['type'] ?? self::TYPE_TEXT ) {
				case self::TYPE_YESNO:
					$datos[ $key ] = self::yes( $valor );
					if ( ! empty( $pregunta['required'] ) && ! $datos[ $key ] ) {
						$errores[] = $key;
					}
					break;

				case self::TYPE_SINGLE:
					$elegida       = is_scalar( $valor ) ? trim( (string) $valor ) : '';
					$datos[ $key ] = in_array( $elegida, (array) ( $pregunta['choices'] ?? array() ), true ) ? $elegida : '';
					if ( ! empty( $pregunta['required'] ) && '' === $datos[ $key ] ) {
						$errores[] = $key;
					}
					break;

				case self::TYPE_MULTIPLE:
					$limpias = array();
					foreach ( is_array( $valor ) ? $valor : array() as $una ) {
						$una = is_scalar( $una ) ? trim( (string) $una ) : '';
						if ( in_array( $una, (array) ( $pregunta['choices'] ?? array() ), true ) && ! in_array( $una, $limpias, true ) ) {
							$limpias[] = $una;
						}
					}
					$datos[ $key ] = $limpias;
					if ( ! empty( $pregunta['required'] ) && array() === $limpias ) {
						$errores[] = $key;
					}
					break;

				default:
					$texto         = is_scalar( $valor ) ? trim( (string) $valor ) : '';
					$datos[ $key ] = mb_substr( $texto, 0, self::TEXT_MAX );
					if ( ! empty( $pregunta['required'] ) && '' === $datos[ $key ] ) {
						$errores[] = $key;
					}
			}
		}

		return array(
			'ok'     => array() === $errores,
			'errors' => $errores,
			'data'   => $datos,
		);
	}

	/**
	 * One stored answer, as the text that goes in a column.
	 *
	 * @param array<string, mixed> $question Normalised question.
	 * @param mixed                $answer   Stored answer.
	 * @return string
	 */
	public static function as_text( array $question, $answer ): string {
		if ( self::TYPE_YESNO === ( $question['type'] ?? '' ) ) {
			return self::yes( $answer ) ? 'Sí' : 'No';
		}
		if ( is_array( $answer ) ) {
			return implode( ', ', array_map( 'strval', $answer ) );
		}
		return is_scalar( $answer ) ? (string) $answer : '';
	}

	/**
	 * A checkbox or a stored boolean, as a boolean.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	private static function yes( $value ): bool {
		if ( is_string( $value ) ) {
			return in_array( strtolower( trim( $value ) ), array( '1', 'on', 'true', 'si', 'sí', 'yes' ), true );
		}
		return (bool) $value;
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
