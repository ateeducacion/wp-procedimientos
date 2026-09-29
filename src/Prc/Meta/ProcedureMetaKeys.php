<?php
/**
 * Meta keys and closed vocabularies for the prc_procedure CPT.
 *
 * @package Prc
 */

namespace Prc\Meta;

/**
 * Procedure post meta keys (never hardcode these strings elsewhere).
 *
 * Hoy una convocatoria es una entrada de un formulario de más de cien campos
 * del gestor de formularios: fechas, ficheros, textos, casillas de diseño y un
 * desplegable de estado que alguien tiene que acordarse de cambiar. Aquí se
 * quedan las que describen el procedimiento y nada más: el estado se calcula
 * ({@see \Prc\Domain\ProcedureState}), la resolución y los listados son
 * enlaces (ADR-0021) y las opciones son preguntas configurables (ADR-0019).
 *
 * Las listas cerradas van en inglés en la clave y en castellano en la
 * etiqueta (ADR-0003): la clave se guarda, la etiqueta se pinta.
 */
final class ProcedureMetaKeys {

	/**
	 * Inicio del plazo de solicitud, en Y-m-d.
	 */
	public const OPENS_AT = 'prc_opens_at';

	/**
	 * Fin del plazo de solicitud, en Y-m-d, inclusive.
	 */
	public const CLOSES_AT = 'prc_closes_at';

	/**
	 * Inicio del plazo de subsanación, en Y-m-d (ADR-0020).
	 */
	public const AMEND_OPENS_AT = 'prc_amend_opens_at';

	/**
	 * Fin del plazo de subsanación, en Y-m-d, inclusive.
	 */
	public const AMEND_CLOSES_AT = 'prc_amend_closes_at';

	/**
	 * Enlace a la resolución que aprueba el procedimiento.
	 */
	public const RESOLUTION_URL = 'prc_resolution_url';

	/**
	 * Enlace al listado provisional de admitidos.
	 */
	public const PROVISIONAL_LIST_URL = 'prc_provisional_list_url';

	/**
	 * Enlace al listado definitivo de admitidos. Con él, el procedimiento está resuelto.
	 */
	public const FINAL_LIST_URL = 'prc_final_list_url';

	/**
	 * Correos de contacto del ámbito convocante: hasta tres, el primero obligatorio.
	 */
	public const CONTACT_EMAILS = 'prc_contact_emails';

	/**
	 * Clave del correo único de antes de la adenda del 2026-09-16.
	 *
	 * Ya no se registra ni se guarda: se conserva porque es el nombre del
	 * campo de un solo correo que todavía pinta el taller, y lo que llegue por
	 * él entra como el primero de la lista. Se va con la pantalla de tres.
	 */
	public const CONTACT_EMAIL = 'prc_contact_email';

	/**
	 * Color de la banda de cabecera: una clave de {@see header_colors()}.
	 */
	public const HEADER_COLOR = 'prc_header_color';

	/**
	 * A quién se convoca: centros (fase 1) o profesorado.
	 */
	public const AUDIENCE = 'prc_audience';

	/**
	 * Titularidad de los centros a los que se dirige: lista de {@see ownerships()}.
	 */
	public const OWNERSHIP = 'prc_ownership';

	/**
	 * Si la solicitud pide una persona coordinadora.
	 */
	public const REQUIRES_COORDINATOR = 'prc_requires_coordinator';

	/**
	 * Las preguntas del procedimiento ({@see \Prc\Domain\ProcedureQuestions}).
	 */
	public const QUESTIONS = 'prc_questions';

	/**
	 * Texto de compromiso que el centro acepta al solicitar. Vacío, no se pide.
	 */
	public const COMMITMENTS = 'prc_commitments';

	/**
	 * Procedimiento cerrado a edición para siempre: el estado «histórico».
	 *
	 * La marca la pone el ámbito que lo convocó y solo la quita quien
	 * administra el aplicativo (ADR-0023). No es un despublicado: la ficha
	 * pública se sigue viendo y el taller se abre en solo lectura.
	 */
	public const ARCHIVED = 'prc_archived';

	public const STATE_DRAFT     = 'draft';
	public const STATE_ARCHIVED  = 'archived';
	public const STATE_RESOLVED  = 'resolved';
	public const STATE_AMENDMENT = 'amendment';
	public const STATE_OPEN      = 'open';
	public const STATE_UPCOMING  = 'upcoming';
	public const STATE_CLOSED    = 'closed';

	public const AUDIENCE_SCHOOLS  = 'schools';
	public const AUDIENCE_TEACHERS = 'teachers';

	public const OWNERSHIP_PUBLIC  = 'public';
	public const OWNERSHIP_PRIVATE = 'private';

	/**
	 * El color de quien no eligió ninguno.
	 */
	public const HEADER_COLOR_NEUTRAL = 'pizarra';

	/**
	 * Cuántos correos de contacto caben.
	 */
	public const CONTACT_EMAILS_MAX = 3;

	/**
	 * All meta keys stored on a procedure post.
	 *
	 * @return string[]
	 */
	public static function all(): array {
		return array(
			self::OPENS_AT,
			self::CLOSES_AT,
			self::AMEND_OPENS_AT,
			self::AMEND_CLOSES_AT,
			self::RESOLUTION_URL,
			self::PROVISIONAL_LIST_URL,
			self::FINAL_LIST_URL,
			self::CONTACT_EMAILS,
			self::HEADER_COLOR,
			self::AUDIENCE,
			self::OWNERSHIP,
			self::REQUIRES_COORDINATOR,
			self::QUESTIONS,
			self::COMMITMENTS,
			self::ARCHIVED,
		);
	}

	/**
	 * Closed vocabulary of derived procedure states, in priority order.
	 *
	 * El orden es el de {@see \Prc\Domain\ProcedureState::of()}: el primero
	 * que se cumple manda.
	 *
	 * @return array<string, string> slug => etiqueta.
	 */
	public static function states(): array {
		return array(
			self::STATE_DRAFT     => 'Borrador',
			self::STATE_ARCHIVED  => 'Histórico',
			self::STATE_RESOLVED  => 'Resuelto',
			self::STATE_AMENDMENT => 'En subsanación',
			self::STATE_OPEN      => 'Abierto',
			self::STATE_UPCOMING  => 'Próximo',
			self::STATE_CLOSED    => 'Cerrado',
		);
	}

	/**
	 * Closed vocabulary of audiences. Phase 1 only implements schools.
	 *
	 * @return array<string, string> slug => etiqueta.
	 */
	public static function audiences(): array {
		return array(
			self::AUDIENCE_SCHOOLS  => 'Centros educativos',
			self::AUDIENCE_TEACHERS => 'Profesorado',
		);
	}

	/**
	 * Closed vocabulary of school ownerships.
	 *
	 * Se cruza con el `ownership` del catálogo de centros (ADR-0017) para
	 * decidir si un centro puede solicitar.
	 *
	 * @return array<string, string> slug => etiqueta.
	 */
	public static function ownerships(): array {
		return array(
			self::OWNERSHIP_PUBLIC  => 'Centros públicos',
			self::OWNERSHIP_PRIVATE => 'Centros privados y concertados',
		);
	}

	/**
	 * Closed palette for the header band, slug => label and hex.
	 *
	 * Lista cerrada y no un selector de color, por lo mismo que las demás: el
	 * sistema anterior dejaba escribir el color a mano y el resultado son
	 * bandas donde el título no se lee.
	 *
	 * El criterio de la paleta: **todos** los colores tienen un contraste de
	 * al menos 4,5:1 con el blanco (WCAG 2.1 AA para texto normal) y menor que
	 * ese con el negro. El más flojo contra el blanco es el ocre, 5,93:1. Así
	 * el rótulo de encima es siempre blanco y no hay que decidirlo color a
	 * color ni recalcularlo al añadir uno: para entrar en esta lista, un color
	 * nuevo tiene que pasar ese mismo 4,5:1 contra el blanco.
	 *
	 * Los nombres son de color y nada más: ni marcas, ni áreas, ni programas.
	 *
	 * @return array<string, array{label:string, hex:string}> slug => etiqueta y hexadecimal.
	 */
	public static function header_colors(): array {
		return array(
			self::HEADER_COLOR_NEUTRAL => array(
				'label' => 'Pizarra',
				'hex'   => '#3f4a55',
			),
			'azul'                     => array(
				'label' => 'Azul',
				'hex'   => '#12507e',
			),
			'indigo'                   => array(
				'label' => 'Índigo',
				'hex'   => '#3c3d8f',
			),
			'turquesa'                 => array(
				'label' => 'Turquesa',
				'hex'   => '#0f6168',
			),
			'verde'                    => array(
				'label' => 'Verde',
				'hex'   => '#1f6b3a',
			),
			'oliva'                    => array(
				'label' => 'Oliva',
				'hex'   => '#4d5c16',
			),
			'ocre'                     => array(
				'label' => 'Ocre',
				'hex'   => '#8a5a00',
			),
			'teja'                     => array(
				'label' => 'Teja',
				'hex'   => '#a34118',
			),
			'granate'                  => array(
				'label' => 'Granate',
				'hex'   => '#8c2230',
			),
			'morado'                   => array(
				'label' => 'Morado',
				'hex'   => '#6a2c91',
			),
		);
	}

	/**
	 * The hex of a header colour; the neutral one when the slug is not ours.
	 *
	 * @param mixed $slug Raw value.
	 * @return string
	 */
	public static function header_hex( $slug ): string {
		$colores = self::header_colors();
		$clave   = self::in_list( $slug, $colores, self::HEADER_COLOR_NEUTRAL );
		return (string) $colores[ $clave ]['hex'];
	}

	/**
	 * Keep a value only when the closed list has it.
	 *
	 * @param mixed                $value    Raw value.
	 * @param array<string, mixed> $allowed  One of the vocabularies above.
	 * @param string               $fallback What to return otherwise.
	 * @return string
	 */
	public static function in_list( $value, array $allowed, string $fallback = '' ): string {
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';
		return isset( $allowed[ $value ] ) ? $value : $fallback;
	}
}
