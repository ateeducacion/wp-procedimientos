<?php
/**
 * Paints the number, date and grid questions of the application form.
 *
 * @package Prc
 */

namespace Prc\PublicFront\View;

use Prc\Domain\ProcedureQuestions;
use Prc\PublicFront\ApplyForm;

/**
 * Los controles de las preguntas que miden algo: número, fecha y rejilla (ADR-0040).
 *
 * Solo pinta: recibe la pregunta normalizada y lo guardado o tecleado, y
 * devuelve HTML escapado. Lo llama {@see ApplyFormView} para esos tres tipos.
 */
final class MeasuredQuestionView {

	/**
	 * A number, a date or a grid question (ADR-0040).
	 *
	 * El navegador ayuda —`type="number"` con su mínimo y su máximo,
	 * `type="date"`—, pero quien decide es el servidor
	 * ({@see ProcedureQuestions::validate_answers()}): sin guion o con un
	 * navegador que no los conozca, se teclea igual. En una rejilla, cada
	 * fila es un número con su propia etiqueta.
	 *
	 * @param array<string, mixed>  $p     Normalised question.
	 * @param mixed                 $valor   Stored or typed answer.
	 * @param array<string, string> $invalid Failing field code => what names it.
	 * @return string
	 */
	public static function html( array $p, $valor, array $invalid = array() ): string {
		$key     = (string) $p['key'];
		$id      = 'prc-q-' . $key;
		$nombre  = ApplyForm::FIELD_ANSWERS . '[' . $key . ']';
		$obliga  = ! empty( $p['required'] );
		$ayuda   = (string) $p['help'];
		$codigo  = 'answer:' . $key;
		$pie     = FieldErrorView::attrs( $invalid, $codigo, '' !== $ayuda ? $id . '-ayuda' : '' );
		$rejilla = ProcedureQuestions::TYPE_GRID === (string) $p['type'];

		$control = '';
		if ( $rejilla ) {
			$dadas = is_array( $valor ) ? $valor : array();
			foreach ( (array) ( $p['rows'] ?? array() ) as $fila ) {
				$rk       = (string) $fila['key'];
				$control .= '<div class="prc-campo prc-campo--cuarto">'
					. '<label class="prc-campo__etiqueta" for="' . esc_attr( $id . '-' . $rk ) . '">' . esc_html( (string) $fila['label'] ) . '</label>'
					. self::number_input( $id . '-' . $rk, $nombre . '[' . $rk . ']', $dadas[ $rk ] ?? '', 0, null, $obliga )
					. '</div>';
			}
			$control = '<div class="prc-opciones prc-rejilla" role="group" id="' . esc_attr( $id ) . '" aria-labelledby="' . esc_attr( $id . '-rotulo' ) . '"' . $pie . '>' . $control . '</div>';
		} elseif ( ProcedureQuestions::TYPE_NUMBER === (string) $p['type'] ) {
			$control = self::number_input( $id, $nombre, $valor, ProcedureQuestions::integer( $p['min'] ?? null ) ?? 0, ProcedureQuestions::integer( $p['max'] ?? null ), $obliga, $pie );
		} else {
			$control = '<input type="date" class="prc-control" id="' . esc_attr( $id ) . '" name="' . esc_attr( $nombre ) . '" value="' . esc_attr( is_scalar( $valor ) ? (string) $valor : '' ) . '"'
				. ( $obliga ? ' required' : '' ) . $pie . ' />';
		}

		return '<div class="prc-campo prc-pregunta ' . esc_attr( $rejilla ? 'prc-campo--completo' : 'prc-campo--medio' ) . '">'
			. self::label( $rejilla ? $id . '-rotulo' : $id, (string) $p['label'], $obliga, $rejilla )
			. $control
			. FieldErrorView::feedback( $invalid, $codigo )
			. ( '' !== $ayuda ? '<div class="prc-campo__ayuda" id="' . esc_attr( $id . '-ayuda' ) . '">' . esc_html( $ayuda ) . '</div>' : '' )
			. '</div>';
	}

	/**
	 * One whole-number input.
	 *
	 * @param string   $id      Control id.
	 * @param string   $nombre  Field name.
	 * @param mixed    $valor   Stored or typed value.
	 * @param int      $minimo  Lowest value.
	 * @param int|null $maximo  Highest value, null for none.
	 * @param bool     $obliga  Whether it is required.
	 * @param string   $extra   Extra attributes, already escaped.
	 * @return string
	 */
	private static function number_input( string $id, string $nombre, $valor, int $minimo, ?int $maximo, bool $obliga, string $extra = '' ): string {
		return '<input type="number" class="prc-control" inputmode="numeric" step="1" id="' . esc_attr( $id ) . '" name="' . esc_attr( $nombre ) . '"'
			. ' value="' . esc_attr( is_scalar( $valor ) ? (string) $valor : '' ) . '" min="' . esc_attr( (string) $minimo ) . '"'
			. ( null !== $maximo ? ' max="' . esc_attr( (string) $maximo ) . '"' : '' )
			. ( $obliga ? ' required' : '' ) . $extra . ' />';
	}

	/**
	 * The label of the question: a `label` for one control, a `p` for a grid.
	 *
	 * @param string $id          Control id, or the id of the paragraph.
	 * @param string $rotulo      Label.
	 * @param bool   $obligatorio Whether it is required.
	 * @param bool   $grupo       Whether it names a group of controls.
	 * @return string
	 */
	private static function label( string $id, string $rotulo, bool $obligatorio, bool $grupo ): string {
		$atributo = $grupo ? ' id="' . esc_attr( $id ) . '"' : ' for="' . esc_attr( $id ) . '"';
		$etiqueta = $grupo ? 'p' : 'label';
		return '<' . $etiqueta . ' class="prc-campo__etiqueta"' . $atributo . '>'
			. esc_html( $rotulo )
			. ( $obligatorio ? ' <span class="prc-campo__obligatorio" aria-hidden="true">*</span>' : '' )
			. '</' . $etiqueta . '>';
	}
}
