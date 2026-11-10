<?php
/**
 * Pure rules of what a duplicated procedure takes from its original.
 *
 * @package Prc
 */

namespace Prc\Domain;

use Prc\Meta\ProcedureMetaKeys;

/**
 * Qué se lleva un duplicado de su original (PR 8 del PLAN-0003).
 *
 * Pura: ni una llamada a WordPress. Cada curso las áreas vuelven a convocar
 * «el mismo procedimiento del año pasado, con las mismas casillas»: lo que
 * describe el procedimiento se copia; lo que es de una convocatoria concreta
 * —el calendario, los enlaces de la resolución, las marcas de cierre— no.
 *
 * La lista de qué se copia vive aquí, junto a una prueba que recorre todas
 * las metas registradas del procedimiento: una meta nueva que no se clasifique
 * pone la prueba en rojo, y así quien la añade tiene que decidirlo.
 */
final class ProcedureCopy {

	/**
	 * Se copia tal cual.
	 */
	public const COPY = 'copy';

	/**
	 * Se copia transformada: claves nuevas, sin fechas, o solo si quien
	 * duplica puede escribirla.
	 */
	public const TRANSFORM = 'transform';

	/**
	 * No se copia nunca.
	 */
	public const NEVER = 'never';

	/**
	 * Lo que se añade al título del duplicado.
	 */
	public const SUFFIX = ' (copia)';

	/**
	 * What happens to each procedure meta key when the procedure is duplicated.
	 *
	 * @return array<string, string> Meta key => COPY, TRANSFORM or NEVER.
	 */
	public static function policy(): array {
		return array(
			// El calendario y los enlaces son de cada convocatoria.
			ProcedureMetaKeys::OPENS_AT             => self::NEVER,
			ProcedureMetaKeys::CLOSES_AT            => self::NEVER,
			ProcedureMetaKeys::AMEND_OPENS_AT       => self::NEVER,
			ProcedureMetaKeys::AMEND_CLOSES_AT      => self::NEVER,
			ProcedureMetaKeys::RESOLUTION_URL       => self::NEVER,
			ProcedureMetaKeys::PROVISIONAL_LIST_URL => self::NEVER,
			ProcedureMetaKeys::FINAL_LIST_URL       => self::NEVER,
			// Las marcas de cierre son del original: el duplicado nace abierto.
			ProcedureMetaKeys::ARCHIVED             => self::NEVER,
			ProcedureMetaKeys::APPLICATIONS_CLOSED  => self::NEVER,
			// Lo que el procedimiento es y a quién se dirige.
			ProcedureMetaKeys::CONTACT_EMAILS       => self::COPY,
			ProcedureMetaKeys::HEADER_COLOR         => self::COPY,
			ProcedureMetaKeys::AUDIENCE             => self::COPY,
			ProcedureMetaKeys::OWNERSHIP            => self::COPY,
			ProcedureMetaKeys::CENTRE_KINDS         => self::COPY,
			ProcedureMetaKeys::CENTRE_TYPES         => self::COPY,
			ProcedureMetaKeys::ISLANDS              => self::COPY,
			ProcedureMetaKeys::ALLOWED_CENTRES      => self::COPY,
			ProcedureMetaKeys::REQUIRES_COORDINATOR => self::COPY,
			ProcedureMetaKeys::COORDINATOR_COUNT    => self::COPY,
			ProcedureMetaKeys::COMMITMENTS          => self::COPY,
			ProcedureMetaKeys::CUSTOM_CSS           => self::COPY,
			ProcedureMetaKeys::NOTIFY               => self::COPY,
			ProcedureMetaKeys::OBSERVABLE           => self::COPY,
			ProcedureMetaKeys::OBSERVER_FILES       => self::COPY,
			ProcedureMetaKeys::OBSERVER_EXPORT      => self::COPY,
			ProcedureMetaKeys::OBSERVER_NOTIFY      => self::COPY,
			// Claves nuevas desde el principio, y sin fechas.
			ProcedureMetaKeys::QUESTIONS            => self::TRANSFORM,
			ProcedureMetaKeys::QUESTIONS_SEQ        => self::TRANSFORM,
			ProcedureMetaKeys::PEOPLE_SPEC          => self::TRANSFORM,
			ProcedureMetaKeys::OBSERVER_COLUMNS     => self::TRANSFORM,
			// Solo si quien duplica puede escribir JavaScript en el original.
			ProcedureMetaKeys::CUSTOM_JS            => self::TRANSFORM,
		);
	}

	/**
	 * The rule of one meta key.
	 *
	 * @param string $key Meta key.
	 * @return string COPY, TRANSFORM, NEVER, or '' when it is not classified.
	 */
	public static function rule( string $key ): string {
		return self::policy()[ $key ] ?? '';
	}

	/**
	 * The title of the duplicate.
	 *
	 * @param string $title Title of the original.
	 * @return string
	 */
	public static function title( string $title ): string {
		$title = trim( $title );
		$title = '' !== $title ? $title : 'Procedimiento sin título';
		// La copia de una copia no se marca dos veces.
		return substr( $title, -strlen( self::SUFFIX ) ) === self::SUFFIX ? $title : $title . self::SUFFIX;
	}

	/**
	 * The meta the duplicate is born with.
	 *
	 * Solo lo que el original tiene: una meta que no existe allí no se crea
	 * aquí, salvo el contador de preguntas, que acompaña a las preguntas.
	 *
	 * @param array<string, mixed> $source  The original's meta that exists, key => stored value.
	 * @param bool                 $with_js Whether whoever duplicates may write its JavaScript.
	 * @return array{meta:array<string, mixed>, js_left:bool}
	 */
	public static function meta( array $source, bool $with_js ): array {
		$meta = array();
		foreach ( self::policy() as $key => $rule ) {
			if ( self::COPY === $rule && array_key_exists( $key, $source ) ) {
				$meta[ $key ] = $source[ $key ];
			}
		}

		$preguntas = self::questions( $source[ ProcedureMetaKeys::QUESTIONS ] ?? array() );
		if ( array_key_exists( ProcedureMetaKeys::QUESTIONS, $source ) ) {
			$meta[ ProcedureMetaKeys::QUESTIONS ]     = $preguntas['questions'];
			$meta[ ProcedureMetaKeys::QUESTIONS_SEQ ] = $preguntas['seq'];
		}
		if ( array_key_exists( ProcedureMetaKeys::PEOPLE_SPEC, $source ) ) {
			$meta[ ProcedureMetaKeys::PEOPLE_SPEC ] = self::people_spec( $source[ ProcedureMetaKeys::PEOPLE_SPEC ] );
		}
		if ( array_key_exists( ProcedureMetaKeys::OBSERVER_COLUMNS, $source ) ) {
			$meta[ ProcedureMetaKeys::OBSERVER_COLUMNS ] = self::observer_columns( $source[ ProcedureMetaKeys::OBSERVER_COLUMNS ], $preguntas['map'] );
		}

		$js = $source[ ProcedureMetaKeys::CUSTOM_JS ] ?? '';
		$js = is_string( $js ) ? $js : '';
		if ( $with_js && '' !== $js ) {
			$meta[ ProcedureMetaKeys::CUSTOM_JS ] = $js;
		}

		return array(
			'meta'    => $meta,
			'js_left' => ! $with_js && '' !== trim( $js ),
		);
	}

	/**
	 * The questions of the duplicate: new keys from `q1`, in their order.
	 *
	 * Un procedimiento nuevo no tiene respuestas que proteger, así que no
	 * hereda los huecos del original: las claves se renumeran sin saltos y el
	 * contador es el número de preguntas (ADR-0040). Las filas de una rejilla,
	 * igual. Una entrega con plazo propio se queda sin fechas: son las de
	 * aquella convocatoria, y sin ellas el taller no deja publicar.
	 *
	 * @param mixed $raw Stored question list.
	 * @return array{questions:array<int, array<string, mixed>>, seq:int, map:array<string, string>} `map` is old key => new key, rows as `q4:r1`.
	 */
	public static function questions( $raw ): array {
		$out = array();
		$map = array();
		foreach ( ProcedureQuestions::sanitize( $raw ) as $i => $pregunta ) {
			$antes           = (string) $pregunta['key'];
			$pregunta['key'] = 'q' . ( $i + 1 );
			$map[ $antes ]   = $pregunta['key'];
			if ( ProcedureQuestions::is_delivery( $pregunta ) ) {
				$pregunta['opens_at']  = '';
				$pregunta['closes_at'] = '';
			}
			if ( ProcedureQuestions::TYPE_GRID === $pregunta['type'] ) {
				$filas               = self::renumber( (array) $pregunta['rows'], 'r' );
				$pregunta['rows']    = $filas['items'];
				$pregunta['row_seq'] = count( $filas['items'] );
				foreach ( $filas['map'] as $fila => $nueva ) {
					$map[ $antes . ':' . $fila ] = $pregunta['key'] . ':' . $nueva;
				}
			}
			$out[] = $pregunta;
		}
		return array(
			'questions' => $out,
			'seq'       => count( $out ),
			'map'       => $map,
		);
	}

	/**
	 * The people specification of the duplicate: renumbered, without its window.
	 *
	 * La ventana propia de la lista es un calendario como el de la solicitud:
	 * se vacía, y sin fechas la lista sigue el plazo de la solicitud (ADR-0041).
	 *
	 * @param mixed $raw Stored specification.
	 * @return array<string, mixed>
	 */
	public static function people_spec( $raw ): array {
		$spec   = PeopleSpec::sanitize( $raw );
		$roles  = self::renumber( $spec['roles'], 'r' );
		$groups = self::renumber( $spec['groups'], 'g' );

		$spec['roles']     = $roles['items'];
		$spec['role_seq']  = count( $roles['items'] );
		$spec['groups']    = $groups['items'];
		$spec['group_seq'] = count( $groups['items'] );
		$spec['opens_at']  = '';
		$spec['closes_at'] = '';
		return $spec;
	}

	/**
	 * The consultation columns of the duplicate, pointing at the new question keys.
	 *
	 * Una columna que nombra una pregunta o una fila que no se puede traducir
	 * se cae: con las claves renumeradas, señalaría otra pregunta.
	 *
	 * @param mixed                 $raw Stored columns.
	 * @param array<string, string> $map Old key => new key, from {@see questions()}.
	 * @return string[]
	 */
	public static function observer_columns( $raw, array $map ): array {
		$out = array();
		foreach ( is_array( $raw ) ? $raw : array() as $columna ) {
			$columna = is_string( $columna ) ? $columna : '';
			if ( preg_match( '/^q\d+(:r\d+)?$/', $columna ) ) {
				$columna = $map[ $columna ] ?? '';
			}
			if ( '' !== $columna && ! in_array( $columna, $out, true ) ) {
				$out[] = $columna;
			}
		}
		return $out;
	}

	/**
	 * Give a keyed list new keys from 1, in its order.
	 *
	 * @param array<int, array<string, mixed>> $items  Items with a `key`.
	 * @param string                           $prefix Key prefix.
	 * @return array{items:array<int, array<string, mixed>>, map:array<string, string>}
	 */
	private static function renumber( array $items, string $prefix ): array {
		$out = array();
		$map = array();
		foreach ( array_values( $items ) as $i => $item ) {
			$nueva                        = $prefix . ( $i + 1 );
			$map[ (string) $item['key'] ] = $nueva;
			$item['key']                  = $nueva;
			$out[]                        = $item;
		}
		return array(
			'items' => $out,
			'map'   => $map,
		);
	}
}
