<?php
/**
 * Derived state of a procedure.
 *
 * @package Prc
 */

namespace Prc\Domain;

use Prc\Meta\ProcedureMetaKeys;

/**
 * Deriva el estado del procedimiento de sus fechas y sus enlaces.
 *
 * Hoy el estado es un desplegable de siete valores que alguien tiene que
 * acordarse de cambiar, y por eso hay iconos para «abierta con el plazo
 * finalizado» y «cerrada con el plazo abierto». Un dato que se puede calcular
 * no se guarda (ADR-0013).
 *
 * {@see of()} es pura: ni una llamada a WordPress. {@see of_post()} es el
 * envoltorio que lee el post, para que todas las pantallas lo pregunten igual.
 */
final class ProcedureState {

	/**
	 * Grupo «lo que el procedimiento es»: título, descripción, clasificación,
	 * a quién se dirige, contacto y compromisos.
	 */
	public const GROUP_DATA = 'data';

	/**
	 * Grupo «las fechas»: el plazo de solicitud y el de subsanación.
	 */
	public const GROUP_DATES = 'dates';

	/**
	 * Grupo «los enlaces»: resolución, listado provisional y listado definitivo.
	 */
	public const GROUP_LINKS = 'links';

	/**
	 * Grupo «las preguntas» del procedimiento (ADR-0019).
	 */
	public const GROUP_QUESTIONS = 'questions';

	/**
	 * State of a procedure given its meta, a reference day and its status.
	 *
	 * El orden de la tabla de la SDD: el primero que se cumple manda. Sin
	 * fecha de cierre, el plazo dura el día de apertura; lo mismo con la
	 * subsanación. La comparación es por día, en `Y-m-d`.
	 *
	 * @param array<string, mixed> $meta      Meta key => value, keyed by ProcedureMetaKeys.
	 * @param string               $today     Reference day, Y-m-d.
	 * @param bool                 $published Whether post_status is `publish`.
	 * @return string One of ProcedureMetaKeys::states().
	 */
	public static function of( array $meta, string $today, bool $published ): string {
		if ( ! $published ) {
			return ProcedureMetaKeys::STATE_DRAFT;
		}
		if ( ! empty( $meta[ ProcedureMetaKeys::ARCHIVED ] ) ) {
			return ProcedureMetaKeys::STATE_ARCHIVED;
		}
		if ( '' !== self::text( $meta, ProcedureMetaKeys::FINAL_LIST_URL ) ) {
			return ProcedureMetaKeys::STATE_RESOLVED;
		}

		$today = trim( $today );

		$amend_opens  = self::text( $meta, ProcedureMetaKeys::AMEND_OPENS_AT );
		$amend_closes = self::text( $meta, ProcedureMetaKeys::AMEND_CLOSES_AT );
		if ( '' === $amend_closes ) {
			$amend_closes = $amend_opens;
		}
		if ( '' !== $amend_opens && $amend_opens <= $today && $today <= $amend_closes ) {
			return ProcedureMetaKeys::STATE_AMENDMENT;
		}

		$opens  = self::text( $meta, ProcedureMetaKeys::OPENS_AT );
		$closes = self::text( $meta, ProcedureMetaKeys::CLOSES_AT );
		if ( '' === $closes ) {
			$closes = $opens;
		}
		// Un procedimiento sin plazo se está preparando: se publica igual y
		// nadie puede solicitar hasta que lo tenga.
		if ( '' === $opens || $today < $opens ) {
			return ProcedureMetaKeys::STATE_UPCOMING;
		}
		if ( $today <= $closes ) {
			return ProcedureMetaKeys::STATE_OPEN;
		}
		return ProcedureMetaKeys::STATE_CLOSED;
	}

	/**
	 * State of a stored procedure, today.
	 *
	 * @param int    $post_id Procedure ID.
	 * @param string $today   Reference day, Y-m-d ('' = today, site timezone).
	 * @return string One of ProcedureMetaKeys::states().
	 */
	public static function of_post( int $post_id, string $today = '' ): string {
		$meta = array();
		foreach ( ProcedureMetaKeys::all() as $key ) {
			$meta[ $key ] = get_post_meta( $post_id, $key, true );
		}
		// Las dos salen a su propia variable porque en medio de una lista de
		// argumentos PHPCS lee la coma de atrás y da la comparación por no-Yoda.
		$dia       = '' !== trim( $today ) ? $today : self::today();
		$publicado = 'publish' === get_post_status( $post_id );
		return self::of( $meta, $dia, $publicado );
	}

	/**
	 * Today, in the site timezone.
	 *
	 * @return string Y-m-d.
	 */
	public static function today(): string {
		return (string) current_time( 'Y-m-d' );
	}

	/**
	 * Human label for a state.
	 *
	 * @param string $state State slug.
	 * @return string Empty when the slug is not one of ours.
	 */
	public static function label( string $state ): string {
		return (string) ( ProcedureMetaKeys::states()[ $state ] ?? '' );
	}

	/**
	 * Every group of data the workshop writes.
	 *
	 * @return string[]
	 */
	public static function groups(): array {
		return array( self::GROUP_DATA, self::GROUP_DATES, self::GROUP_LINKS, self::GROUP_QUESTIONS );
	}

	/**
	 * What may still be edited in this state, by group.
	 *
	 * Es la columna «Quien gestiona» de la tabla «Lo que cada estado permite»
	 * de la SDD, en código: borrador, próximo y abierto se editan enteros;
	 * cerrado el plazo quedan las fechas y los enlaces; resuelto, los enlaces;
	 * en subsanación no se toca el procedimiento —solo se gestionan sus
	 * solicitudes—; e histórico, nada.
	 *
	 * Lo que de verdad protege son las preguntas: borrar una de un
	 * procedimiento con solicitudes presentadas descoloca su tabla y su CSV.
	 *
	 * Pura, como {@see of()}: la cruza con quién pregunta
	 * {@see \Prc\Access\ProcedureAccess::can_edit_group()}.
	 *
	 * @param string $state State slug.
	 * @return string[] Group slugs; empty when nothing may be touched.
	 */
	public static function editable_fields( string $state ): array {
		$todo  = self::groups();
		$tabla = array(
			ProcedureMetaKeys::STATE_DRAFT    => $todo,
			ProcedureMetaKeys::STATE_UPCOMING => $todo,
			ProcedureMetaKeys::STATE_OPEN     => $todo,
			ProcedureMetaKeys::STATE_CLOSED   => array( self::GROUP_DATES, self::GROUP_LINKS ),
			ProcedureMetaKeys::STATE_RESOLVED => array( self::GROUP_LINKS ),
		);
		return $tabla[ $state ] ?? array();
	}

	/**
	 * Why this state closes part of the workshop, in words.
	 *
	 * Vive aquí y no en cada pantalla para que todas lo cuenten igual: el
	 * envío rechazado y el panel que se pinta apagado dicen lo mismo.
	 *
	 * @param string $state State slug.
	 * @return string Empty when the state edits everything, y también en
	 *                histórico, que no lo decide el estado sino ADR-0023.
	 */
	public static function why_locked( string $state ): string {
		$textos = array(
			ProcedureMetaKeys::STATE_CLOSED    => 'El plazo de solicitud está cerrado: solo se pueden cambiar las fechas y los enlaces. Las solicitudes se siguen gestionando.',
			ProcedureMetaKeys::STATE_AMENDMENT => 'La subsanación está en marcha: el procedimiento ya no se edita, para no mover el suelo a quien está subsanando. Las solicitudes se siguen gestionando.',
			ProcedureMetaKeys::STATE_RESOLVED  => 'El procedimiento está resuelto: solo se pueden cambiar los enlaces.',
		);
		return (string) ( $textos[ $state ] ?? '' );
	}

	/**
	 * Whether a school may submit or edit its application in this state.
	 *
	 * Solo el plazo: en subsanación solo edita quien tiene la solicitud «a
	 * subsanar», y eso lo decide la pantalla mirando la solicitud.
	 *
	 * @param string $state State slug.
	 * @return bool
	 */
	public static function accepts_applications( string $state ): bool {
		return ProcedureMetaKeys::STATE_OPEN === $state;
	}

	/**
	 * One trimmed meta value.
	 *
	 * @param array<string, mixed> $meta Meta values.
	 * @param string               $key  Meta key.
	 * @return string
	 */
	private static function text( array $meta, string $key ): string {
		$value = $meta[ $key ] ?? '';
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}
}
