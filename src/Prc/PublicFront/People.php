<?php
/**
 * The list of people of an application: read it and change it one row at a time.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\Access\ProcedureAccess;
use Prc\Domain\ApplicationPeople;
use Prc\Domain\PeopleSpec;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;

/**
 * Las personas de una solicitud, guardadas (ADR-0041).
 *
 * La lista es una meta de la solicitud (`prc_people`) y se escribe **de fila
 * en fila**: dar de alta, cambiar o dar de baja una persona por su `id`. Cada
 * operación relee la meta en el momento de escribir —sin caché—, así que dos
 * cuentas del mismo centro que trabajan a la vez no se borran lo que la otra
 * acaba de añadir. Y un cambio o una baja llevan la huella de la fila tal
 * como se pintó ({@see ApplicationPeople::fingerprint()}): si otra cuenta la
 * cambió o la quitó desde entonces, se rechaza con aviso en vez de pisarla.
 *
 * Quién y cuándo lo decide {@see ProcedureAccess::can_edit_people()}, aquí
 * también y no solo en la pantalla: un POST armado a mano se para igual.
 *
 * Tocar personas **no** devuelve la solicitud a «presentada» ni cambia la
 * nota de revisión: solo escribe `prc_people`, una entrada en el historial
 * ({@see ApplicationLog}) y emite `prc_people_changed`.
 */
final class People {

	public const ADDED   = 'added';
	public const CHANGED = 'changed';
	public const REMOVED = 'removed';

	/**
	 * The specification of a procedure, normalised.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return array<string, mixed>
	 */
	public static function spec( int $procedure_id ): array {
		return PeopleSpec::sanitize( $procedure_id > 0 ? get_post_meta( $procedure_id, ProcedureMetaKeys::PEOPLE_SPEC, true ) : array() );
	}

	/**
	 * Whether a procedure asks for a list of people: switched on, and for schools.
	 *
	 * Las solicitudes individuales de profesorado no tienen lista (ADR-0037).
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return bool
	 */
	public static function enabled( int $procedure_id ): bool {
		return true === self::spec( $procedure_id )['enabled']
			&& ProcedureMetaKeys::AUDIENCE_TEACHERS !== get_post_meta( $procedure_id, ProcedureMetaKeys::AUDIENCE, true );
	}

	/**
	 * The people of an application, as stored.
	 *
	 * @param int $application_id Application post ID.
	 * @return array<int, array<string, string>>
	 */
	public static function of( int $application_id ): array {
		return ApplicationPeople::rows( get_post_meta( $application_id, ApplicationMetaKeys::PEOPLE, true ) );
	}

	/**
	 * Add one person to the list.
	 *
	 * @param int                  $application_id Application post ID.
	 * @param int                  $user_id        Who does it.
	 * @param array<string, mixed> $raw            The person: name, surname, dni, email, phone, role, group.
	 * @return array{ok:bool, error:string, errors:array<string, string>}
	 */
	public static function add( int $application_id, int $user_id, array $raw ): array {
		$raw['id'] = '';
		return self::apply( $application_id, $user_id, self::ADDED, '', '', $raw );
	}

	/**
	 * Change one person: to correct it or to put someone else in their place.
	 *
	 * @param int                  $application_id Application post ID.
	 * @param int                  $user_id        Who does it.
	 * @param string               $id             Row ID.
	 * @param string               $huella         Fingerprint of the row as it was painted.
	 * @param array<string, mixed> $raw            The person as it should stay.
	 * @return array{ok:bool, error:string, errors:array<string, string>}
	 */
	public static function change( int $application_id, int $user_id, string $id, string $huella, array $raw ): array {
		return self::apply( $application_id, $user_id, self::CHANGED, $id, $huella, $raw );
	}

	/**
	 * Remove one person from the list.
	 *
	 * @param int    $application_id Application post ID.
	 * @param int    $user_id        Who does it.
	 * @param string $id             Row ID.
	 * @param string $huella         Fingerprint of the row as it was painted.
	 * @return array{ok:bool, error:string, errors:array<string, string>}
	 */
	public static function remove( int $application_id, int $user_id, string $id, string $huella ): array {
		return self::apply( $application_id, $user_id, self::REMOVED, $id, $huella, array() );
	}

	/**
	 * Do one change, re-reading the list right before writing it.
	 *
	 * @param int                  $application_id Application post ID.
	 * @param int                  $user_id        Who does it.
	 * @param string               $change         One of the three changes.
	 * @param string               $id             Row ID; empty when adding.
	 * @param string               $huella         Fingerprint as painted; empty when adding.
	 * @param array<string, mixed> $raw            The person; empty when removing.
	 * @return array{ok:bool, error:string, errors:array<string, string>}
	 */
	private static function apply( int $application_id, int $user_id, string $change, string $id, string $huella, array $raw ): array {
		if ( ! user_can( $user_id, ProcedureAccess::CAP_EDIT_PEOPLE, $application_id ) ) {
			return self::refused( ProcedureAccess::why_not_people( $user_id, $application_id ) );
		}
		// Lo que hay ahora en la base de datos, no lo que había al pintar.
		wp_cache_delete( $application_id, 'post_meta' );
		$lista = self::of( $application_id );
		$sitio = self::position( $lista, $id );
		if ( self::ADDED !== $change ) {
			$stale = self::stale( $lista, $sitio, $huella );
			if ( '' !== $stale ) {
				return self::refused( $stale );
			}
		}
		$antes = null === $sitio ? array() : $lista[ $sitio ];
		if ( self::REMOVED === $change ) {
			array_splice( $lista, (int) $sitio, 1 );
			return self::write( $application_id, $user_id, $change, $antes, array(), $lista );
		}

		$fila       = ApplicationPeople::row( $raw );
		$fila['id'] = self::ADDED === $change ? bin2hex( random_bytes( 6 ) ) : $id;
		$errores    = ApplicationPeople::validate( self::spec( (int) wp_get_post_parent_id( $application_id ) ), $lista, self::ADDED === $change ? array( 'id' => '' ) + $fila : $fila );
		if ( array() !== $errores ) {
			return array(
				'ok'     => false,
				'error'  => self::why( $errores ),
				'errors' => $errores,
			);
		}
		if ( null === $sitio ) {
			$lista[] = $fila;
		} else {
			$lista[ $sitio ] = $fila;
		}
		return self::write( $application_id, $user_id, $change, $antes, $fila, $lista );
	}

	/**
	 * Why a change on a painted row cannot go on: the row went away or changed.
	 *
	 * @param array<int, array<string, string>> $rows  The list now.
	 * @param int|null                          $where Where the row is, or null.
	 * @param string                            $huella Fingerprint as painted.
	 * @return string Empty when the row is as it was painted.
	 */
	private static function stale( array $rows, ?int $where, string $huella ): string {
		if ( null === $where ) {
			return 'Esa persona ya no está en la lista: otra cuenta de su centro la ha dado de baja mientras tanto. Revise la lista tal como está ahora.';
		}
		if ( ! hash_equals( ApplicationPeople::fingerprint( $rows[ $where ] ), $huella ) ) {
			return 'Otra cuenta de su centro ha cambiado esta persona mientras usted la tenía abierta, y no se ha guardado nada para no pisar su cambio. Revise los datos actuales y vuelva a intentarlo.';
		}
		return '';
	}

	/**
	 * Write the list, log the change and tell whoever listens.
	 *
	 * @param int                               $application_id Application post ID.
	 * @param int                               $user_id        Who did it.
	 * @param string                            $change         Change.
	 * @param array<string, string>             $before         The row before; empty when added.
	 * @param array<string, string>             $after          The row after; empty when removed.
	 * @param array<int, array<string, string>> $rows           The whole list after.
	 * @return array{ok:bool, error:string, errors:array<string, string>}
	 */
	private static function write( int $application_id, int $user_id, string $change, array $before, array $after, array $rows ): array {
		update_post_meta( $application_id, ApplicationMetaKeys::PEOPLE, wp_slash( array_values( $rows ) ) );
		ApplicationLog::add(
			$application_id,
			'person_' . $change,
			array(
				'before' => $before,
				'after'  => $after,
			),
			$user_id
		);

		/**
		 * One person of the list of an application has just been added, changed or removed.
		 *
		 * @param int                   $application_id Application post ID.
		 * @param string                $change         `added`, `changed` or `removed`.
		 * @param array<string, string> $before         The row before; empty when added.
		 * @param array<string, string> $after          The row after; empty when removed.
		 * @param int                   $user_id        Account that did it.
		 */
		do_action( 'prc_people_changed', $application_id, $change, $before, $after, $user_id );

		return array(
			'ok'     => true,
			'error'  => '',
			'errors' => array(),
		);
	}

	/**
	 * Where a row is in the list.
	 *
	 * @param array<int, array<string, string>> $rows The list.
	 * @param string                            $id   Row ID.
	 * @return int|null
	 */
	private static function position( array $rows, string $id ): ?int {
		foreach ( $rows as $i => $fila ) {
			if ( '' !== $id && $fila['id'] === $id ) {
				return (int) $i;
			}
		}
		return null;
	}

	/**
	 * Which roles and groups some application of the procedure uses.
	 *
	 * Es lo que decide si el taller puede quitar uno ({@see PeopleSpec::refusal()}).
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return array{roles:array<string, bool>, groups:array<string, bool>}
	 */
	public static function used_keys( int $procedure_id ): array {
		$out = array(
			'roles'  => array(),
			'groups' => array(),
		);
		foreach ( Applications::for_procedure( $procedure_id ) as $solicitud ) {
			foreach ( self::of( (int) $solicitud->ID ) as $fila ) {
				$out['roles'][ $fila['role'] ] = true;
				if ( '' !== $fila['group'] ) {
					$out['groups'][ $fila['group'] ] = true;
				}
			}
		}
		return $out;
	}

	/**
	 * Why a row was refused, in one sentence.
	 *
	 * @param array<string, string> $errors Error key => why.
	 * @return string
	 */
	public static function why( array $errors ): string {
		$campos = array_intersect_key( $errors, array_flip( array( 'name', 'surname', 'dni', 'email', 'phone', 'role', 'group' ) ) );
		if ( array() === $campos ) {
			return (string) reset( $errors );
		}
		$ultimo = array_pop( $campos );
		return 'Revise ' . ( array() === $campos ? '' : implode( ', ', $campos ) . ' y ' ) . $ultimo . '.';
	}

	/**
	 * A refusal, with its reason.
	 *
	 * @param string $why Why.
	 * @return array{ok:bool, error:string, errors:array<string, string>}
	 */
	private static function refused( string $why ): array {
		return array(
			'ok'     => false,
			'error'  => $why,
			'errors' => array(),
		);
	}
}
