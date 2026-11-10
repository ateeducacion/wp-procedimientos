<?php
/**
 * What goes into the history of an application, and how it reads.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\Domain\ApplicationPeople;
use Prc\Meta\ApplicationMetaKeys;

/**
 * El historial de una solicitud: qué se anota y cómo se lee (ADR-0044, que
 * desarrolla lo que la ADR-0005 dejó por escribir).
 *
 * **Escucha las acciones que ya emiten las operaciones** —presentar,
 * modificar, revisar, entregar, las personas, retirar, restaurar, la
 * prórroga, presentar en nombre de un centro y la nota interna— y anota cada
 * una con {@see ApplicationLog::add()}: ninguna pantalla escribe en el
 * historial por su cuenta, así que cada actuación queda **una vez**.
 *
 * No guarda textos largos: de la revisión, el estado y si cambió la nota; de
 * la nota interna, que cambió, nunca qué dice. Solo lo lee quien revisa
 * ({@see entries()}), al abrir esa solicitud.
 */
final class ApplicationHistory {

	public const SUBMITTED     = 'submitted';
	public const UPDATED       = 'updated';
	public const REVIEWED      = 'review_changed';
	public const DELIVERED     = 'delivery';
	public const WITHDRAWN     = 'withdrawn';
	public const RESTORED      = 'restored';
	public const EXTENDED      = 'extended';
	public const CREATED_FOR   = 'created_for_centre';
	public const INTERNAL_NOTE = 'internal_note';

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'prc_application_saved', array( self::class, 'on_saved' ), 5, 4 );
		add_action( 'prc_application_reviewed', array( self::class, 'on_reviewed' ), 5, 4 );
		add_action( 'prc_delivery_saved', array( self::class, 'on_delivery' ), 5, 3 );
		add_action( 'prc_people_changed', array( self::class, 'on_people' ), 5, 5 );
		add_action( 'prc_application_withdrawn', array( self::class, 'on_withdrawn' ), 5, 4 );
		add_action( 'prc_application_restored', array( self::class, 'on_restored' ), 5, 2 );
		add_action( 'prc_application_extended', array( self::class, 'on_extended' ), 5, 4 );
		add_action( 'prc_application_created_for', array( self::class, 'on_created_for' ), 5, 3 );
		add_action( 'prc_internal_note_changed', array( self::class, 'on_internal_note' ), 5, 2 );
	}

	// ─── lo que se anota ───────────────────────────────────────────────────

	/**
	 * Submitted or changed by its school.
	 *
	 * Si guardar la devolvió a «presentada» desde otro estado —admitida,
	 * excluida, a subsanar—, eso queda además como un cambio de revisión
	 * hecho por el guardado, con el estado de antes.
	 *
	 * @param int    $application_id Application ID.
	 * @param bool   $created        Whether it is new.
	 * @param int    $user_id        Who stored it.
	 * @param string $previous       Review state before saving.
	 * @return void
	 */
	public static function on_saved( $application_id, $created = false, $user_id = 0, $previous = '' ): void {
		ApplicationLog::add( (int) $application_id, $created ? self::SUBMITTED : self::UPDATED, array(), (int) $user_id );
		$previous = (string) $previous;
		if ( $created || '' === $previous || ApplicationMetaKeys::REVIEW_SUBMITTED === $previous ) {
			return;
		}
		ApplicationLog::add(
			(int) $application_id,
			self::REVIEWED,
			array(
				'state'        => ApplicationMetaKeys::REVIEW_SUBMITTED,
				'previous'     => $previous,
				'note_changed' => false,
				'by'           => 'save',
			),
			(int) $user_id
		);
	}

	/**
	 * Reviewed: only when the state or the note changed, and without the note.
	 *
	 * @param int    $application_id Application ID.
	 * @param string $state          New state.
	 * @param string $previous       State before.
	 * @param string $previous_note  Note before.
	 * @return void
	 */
	public static function on_reviewed( $application_id, $state = '', $previous = '', $previous_note = '' ): void {
		$nota   = trim( (string) get_post_meta( (int) $application_id, ApplicationMetaKeys::REVIEW_NOTE, true ) );
		$antes  = '' !== (string) $previous ? (string) $previous : ApplicationMetaKeys::REVIEW_SUBMITTED;
		$cambia = trim( (string) $previous_note ) !== $nota;
		if ( (string) $state === $antes && ! $cambia ) {
			return;
		}
		ApplicationLog::add(
			(int) $application_id,
			self::REVIEWED,
			array(
				'state'        => (string) $state,
				'previous'     => $antes,
				'note_changed' => $cambia,
			)
		);
	}

	/**
	 * A document with its own deadline delivered.
	 *
	 * @param int    $application_id Application ID.
	 * @param string $question_key   Question.
	 * @param int    $user_id        Who delivered it.
	 * @return void
	 */
	public static function on_delivery( $application_id, $question_key = '', $user_id = 0 ): void {
		ApplicationLog::add( (int) $application_id, self::DELIVERED, array( 'question' => (string) $question_key ), (int) $user_id );
	}

	/**
	 * One person of the list added, changed or removed (ADR-0041).
	 *
	 * @param int                  $application_id Application ID.
	 * @param string               $change         `added`, `changed` or `removed`.
	 * @param array<string, mixed> $before         Row before.
	 * @param array<string, mixed> $after          Row after.
	 * @param int                  $user_id        Who did it.
	 * @return void
	 */
	public static function on_people( $application_id, $change = '', $before = array(), $after = array(), $user_id = 0 ): void {
		ApplicationLog::add(
			(int) $application_id,
			'person_' . sanitize_key( (string) $change ),
			array(
				'before' => (array) $before,
				'after'  => (array) $after,
			),
			(int) $user_id
		);
	}

	/**
	 * Withdrawn: by the school, by whoever reviews —with the reason— or from the dashboard.
	 *
	 * @param int    $application_id Application ID.
	 * @param int    $user_id        Who withdrew it.
	 * @param string $by             Where from.
	 * @param string $reason         Why.
	 * @return void
	 */
	public static function on_withdrawn( $application_id, $user_id = 0, $by = '', $reason = '' ): void {
		ApplicationLog::add(
			(int) $application_id,
			self::WITHDRAWN,
			array(
				'by'     => (string) $by,
				'reason' => (string) $reason,
			),
			(int) $user_id
		);
	}

	/**
	 * Restored from the bin.
	 *
	 * @param int $application_id Application ID.
	 * @param int $user_id        Who restored it.
	 * @return void
	 */
	public static function on_restored( $application_id, $user_id = 0 ): void {
		ApplicationLog::add( (int) $application_id, self::RESTORED, array(), (int) $user_id );
	}

	/**
	 * Extension given, changed or taken away.
	 *
	 * @param int    $application_id Application ID.
	 * @param string $until          Last day now.
	 * @param string $previous       Last day before.
	 * @param int    $user_id        Who did it.
	 * @return void
	 */
	public static function on_extended( $application_id, $until = '', $previous = '', $user_id = 0 ): void {
		ApplicationLog::add(
			(int) $application_id,
			self::EXTENDED,
			array(
				'until'    => (string) $until,
				'previous' => (string) $previous,
			),
			(int) $user_id
		);
	}

	/**
	 * Submitted on behalf of its school.
	 *
	 * @param int                  $application_id Application ID.
	 * @param int                  $user_id        Who submitted it.
	 * @param array<string, mixed> $data           `code`, `until`, `ineligible`.
	 * @return void
	 */
	public static function on_created_for( $application_id, $user_id = 0, $data = array() ): void {
		$data = (array) $data;
		ApplicationLog::add(
			(int) $application_id,
			self::CREATED_FOR,
			array(
				'until'      => (string) ( $data['until'] ?? '' ),
				'ineligible' => (string) ( $data['ineligible'] ?? '' ),
			),
			(int) $user_id
		);
	}

	/**
	 * The internal note changed: that it did, never what it says.
	 *
	 * @param int $application_id Application ID.
	 * @param int $user_id        Who wrote it.
	 * @return void
	 */
	public static function on_internal_note( $application_id, $user_id = 0 ): void {
		ApplicationLog::add( (int) $application_id, self::INTERNAL_NOTE, array(), (int) $user_id );
	}

	// ─── cómo se lee ───────────────────────────────────────────────────────

	/**
	 * The history of an application, newest first, in words.
	 *
	 * Las cuentas, en una consulta; las personas, con el DNI tapado
	 * (ADR-0041).
	 *
	 * @param int $application_id Application ID.
	 * @return array<int, array{when:string, who:string, what:string, before:string, after:string}>
	 */
	public static function entries( int $application_id ): array {
		$procedure_id = (int) wp_get_post_parent_id( $application_id );
		$spec         = People::spec( $procedure_id );
		$preguntas    = array_column( Applications::questions( $procedure_id ), null, 'key' );
		$entradas     = array_reverse( ApplicationLog::all( $application_id ) );
		cache_users( array_values( array_unique( array_filter( array_column( $entradas, 'user' ) ) ) ) );
		$out = array();
		foreach ( $entradas as $entrada ) {
			$linea = self::line( $entrada, $spec, $preguntas );
			if ( null === $linea ) {
				continue;
			}
			$cuenta = $entrada['user'] > 0 ? get_userdata( $entrada['user'] ) : false;
			$out[]  = $linea + array(
				'when' => '' !== $entrada['at'] ? (string) wp_date( 'd-m-Y H:i', (int) strtotime( $entrada['at'] ) ) : '',
				'who'  => $entrada['user'] <= 0 ? '' : ( false !== $cuenta ? (string) $cuenta->display_name : '(cuenta que ya no existe)' ),
			);
		}
		return $out;
	}

	/**
	 * What one entry says, before and after included.
	 *
	 * @param array<string, mixed>                $entrada   Entry.
	 * @param array<string, mixed>                $spec      People specification.
	 * @param array<string, array<string, mixed>> $preguntas Questions by key.
	 * @return array{what:string, before:string, after:string}|null Null for an event it does not know.
	 */
	private static function line( array $entrada, array $spec, array $preguntas ): ?array {
		$data   = (array) $entrada['data'];
		$fijos  = array(
			self::SUBMITTED         => 'Solicitud presentada',
			self::UPDATED           => 'Solicitud modificada',
			self::RESTORED          => 'Solicitud restaurada desde «Retiradas»',
			self::INTERNAL_NOTE     => 'Nota interna cambiada',
			ApplicationLog::TRIMMED => sprintf( 'Se han descartado las %d entradas más antiguas del historial.', (int) ( $data['dropped'] ?? 0 ) ),
		);
		$evento = (string) $entrada['event'];
		if ( isset( $fijos[ $evento ] ) ) {
			return self::words( $fijos[ $evento ] );
		}
		$personas = array(
			'person_added'   => 'Alta en la lista de personas',
			'person_changed' => 'Cambio en la lista de personas',
			'person_removed' => 'Baja en la lista de personas',
		);
		if ( isset( $personas[ $evento ] ) ) {
			return self::words( $personas[ $evento ], self::person( $spec, (array) ( $data['before'] ?? array() ) ), self::person( $spec, (array) ( $data['after'] ?? array() ) ) );
		}
		return self::described( $evento, $data, $preguntas );
	}

	/**
	 * The events whose words depend on their data.
	 *
	 * @param string                              $evento    Event.
	 * @param array<string, mixed>                $data      Its data.
	 * @param array<string, array<string, mixed>> $preguntas Questions by key.
	 * @return array{what:string, before:string, after:string}|null
	 */
	private static function described( string $evento, array $data, array $preguntas ): ?array {
		$como = array(
			self::REVIEWED    => 'reviewed',
			self::WITHDRAWN   => 'withdrawn',
			self::EXTENDED    => 'extended',
			self::CREATED_FOR => 'created_for',
		);
		if ( self::DELIVERED === $evento ) {
			$clave = (string) ( $data['question'] ?? '' );
			return self::words( 'Documento entregado' . ( isset( $preguntas[ $clave ] ) ? ': ' . (string) $preguntas[ $clave ]['label'] : '' ) );
		}
		return isset( $como[ $evento ] ) ? call_user_func( array( self::class, $como[ $evento ] ), $data ) : null;
	}

	/**
	 * A review: the state before and after, and whether the note changed.
	 *
	 * @param array<string, mixed> $data Data of the entry.
	 * @return array{what:string, before:string, after:string}
	 */
	private static function reviewed( array $data ): array {
		$estados = ApplicationMetaKeys::review_states();
		$estado  = (string) ( $estados[ (string) ( $data['state'] ?? '' ) ] ?? '' );
		$antes   = (string) ( $estados[ (string) ( $data['previous'] ?? '' ) ] ?? '' );
		if ( $antes === $estado ) {
			return self::words( 'Revisión: nota cambiada' );
		}
		$porque = 'save' === ( $data['by'] ?? '' ) ? ', al modificarla quien la presentó' : ( empty( $data['note_changed'] ) ? '' : ', con nota nueva' );
		return self::words( 'Revisión: ' . $estado . $porque, $antes, $estado );
	}

	/**
	 * A withdrawal: who, and why when whoever reviews did it.
	 *
	 * @param array<string, mixed> $data Data of the entry.
	 * @return array{what:string, before:string, after:string}
	 */
	private static function withdrawn( array $data ): array {
		$quien  = array(
			ApplicationOperations::BY_APPLICANT => 'Retirada por el centro',
			ApplicationOperations::BY_REVIEWER  => 'Retirada por quien revisa',
		);
		$motivo = trim( (string) ( $data['reason'] ?? '' ) );
		return self::words( ( $quien[ (string) ( $data['by'] ?? '' ) ] ?? 'Enviada a la papelera desde el escritorio' ) . ( '' !== $motivo ? '. Motivo: ' . $motivo : '' ) );
	}

	/**
	 * An extension given, changed or taken away.
	 *
	 * @param array<string, mixed> $data Data of the entry.
	 * @return array{what:string, before:string, after:string}
	 */
	private static function extended( array $data ): array {
		$hasta = (string) ( $data['until'] ?? '' );
		return self::words( '' === $hasta ? 'Prórroga quitada' : 'Prórroga hasta el ' . self::day( $hasta ), self::day( (string) ( $data['previous'] ?? '' ) ), self::day( $hasta ) );
	}

	/**
	 * Submitted on behalf of the school, and why it was let through if it did not qualify.
	 *
	 * @param array<string, mixed> $data Data of the entry.
	 * @return array{what:string, before:string, after:string}
	 */
	private static function created_for( array $data ): array {
		$porque = trim( (string) ( $data['ineligible'] ?? '' ) );
		return self::words( 'Presentada en nombre del centro, con plazo para completarla hasta el ' . self::day( (string) ( $data['until'] ?? '' ) ) . ( '' !== $porque ? '. Aunque no cumple los requisitos: ' . $porque : '' ) );
	}

	/**
	 * One line in the three parts every entry has.
	 *
	 * @param string $what   What happened.
	 * @param string $before Before, if it says.
	 * @param string $after  After, if it says.
	 * @return array{what:string, before:string, after:string}
	 */
	private static function words( string $what, string $before = '', string $after = '' ): array {
		return array(
			'what'   => $what,
			'before' => $before,
			'after'  => $after,
		);
	}

	/**
	 * One person of the history in a line, with the DNI covered.
	 *
	 * @param array<string, mixed> $spec Specification.
	 * @param array<string, mixed> $row  Row.
	 * @return string Empty for no row.
	 */
	private static function person( array $spec, array $row ): string {
		if ( array() === $row ) {
			return '';
		}
		$dni = ApplicationPeople::masked_dni( (string) ( $row['dni'] ?? '' ) );
		return ApplicationPeople::describe( $spec, $row ) . ( '' !== $dni ? ' · DNI ' . $dni : '' );
	}

	/**
	 * A Y-m-d day as d-m-Y.
	 *
	 * @param string $ymd Day.
	 * @return string
	 */
	private static function day( string $ymd ): string {
		return '' === $ymd ? '' : implode( '-', array_reverse( explode( '-', $ymd ) ) );
	}
}
