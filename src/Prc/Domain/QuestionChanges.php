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
			if ( isset( $pregunta['min'], $pregunta['max'] ) && $pregunta['min'] > $pregunta['max'] ) {
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
}
