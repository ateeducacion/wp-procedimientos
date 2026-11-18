<?php
/**
 * Pure validation of the people listed in an application.
 *
 * @package Prc
 */

namespace Prc\Domain;

/**
 * Las personas de una solicitud: una fila por persona (ADR-0041).
 *
 * Pura: ni una llamada a WordPress. Se guardan en la meta `prc_people` de la
 * solicitud, una lista de filas con esta forma:
 *
 * ```
 * { id: '4f1c2a9b0e7d', name: 'Ana', surname: 'Pérez Díaz',
 *   dni: '12345678Z', email: 'ana@example.org', phone: '',
 *   role: 'r2', group: 'g1' }
 * ```
 *
 * `id` lo pone el aplicativo al dar de alta y no cambia nunca: sustituir a
 * alguien es editar su fila. `role` y `group` son claves de la
 * especificación del procedimiento ({@see PeopleSpec}), no rótulos.
 */
final class ApplicationPeople {

	/**
	 * The fields of a row, in order.
	 *
	 * @var string[]
	 */
	public const FIELDS = array( 'id', 'name', 'surname', 'dni', 'email', 'phone', 'role', 'group' );

	/**
	 * One row, normalised: texts trimmed, DNI and email as they are compared.
	 *
	 * @param mixed $raw Raw row.
	 * @return array{id:string, name:string, surname:string, dni:string, email:string, phone:string, role:string, group:string}
	 */
	public static function row( $raw ): array {
		$raw = is_array( $raw ) ? $raw : array();
		$out = array();
		foreach ( self::FIELDS as $campo ) {
			$valor         = $raw[ $campo ] ?? '';
			$out[ $campo ] = is_scalar( $valor ) ? trim( (string) preg_replace( '/\s+/u', ' ', (string) $valor ) ) : '';
		}
		$out['id']    = preg_match( '/^[a-z0-9]{1,32}$/', strtolower( $out['id'] ) ) ? strtolower( $out['id'] ) : '';
		$out['dni']   = strtoupper( (string) preg_replace( '/[ -]/', '', $out['dni'] ) );
		$out['email'] = strtolower( $out['email'] );
		$out['role']  = strtolower( $out['role'] );
		$out['group'] = strtolower( $out['group'] );
		return $out;
	}

	/**
	 * A stored list, normalised: rows without id or name are dropped, and so
	 * is a repeated id.
	 *
	 * @param mixed $raw Stored value.
	 * @return array<int, array<string, string>>
	 */
	public static function rows( $raw ): array {
		$out = array();
		foreach ( is_array( $raw ) ? $raw : array() as $item ) {
			$fila = self::row( $item );
			if ( '' === $fila['id'] || isset( $out[ $fila['id'] ] ) ) {
				continue;
			}
			$out[ $fila['id'] ] = $fila;
		}
		return array_slice( array_values( $out ), 0, PeopleSpec::MAX_PEOPLE );
	}

	/**
	 * What is wrong with a row, against the specification and the rest of the list.
	 *
	 * La fila con el mismo `id` que `$row` no cuenta como «otra»: editar una
	 * persona no choca consigo misma. Por debajo del mínimo no es un error:
	 * la solicitud queda incompleta ({@see is_complete()}).
	 *
	 * @param array<string, mixed>             $spec   Specification of the procedure.
	 * @param array<int, array<string, mixed>> $people The list as it is now.
	 * @param array<string, mixed>             $row    The row to store, normalised.
	 * @return array<string, string> Error key => why, in Spanish; empty when it can be stored.
	 */
	public static function validate( array $spec, array $people, array $row ): array {
		$spec   = PeopleSpec::sanitize( $spec );
		$row    = self::row( $row );
		$otras  = array_values(
			array_filter(
				self::rows( $people ),
				static function ( array $otra ) use ( $row ): bool {
					return '' === $row['id'] || $otra['id'] !== $row['id'];
				}
			)
		);
		$errors = self::fields( $spec, $row );
		if ( array() !== $errors ) {
			return $errors;
		}
		return self::against( $spec, $otras, $row );
	}

	/**
	 * What is wrong with the fields of a row on their own.
	 *
	 * @param array<string, mixed> $spec Normalised specification.
	 * @param array<string, mixed> $row  Normalised row.
	 * @return array<string, string>
	 */
	private static function fields( array $spec, array $row ): array {
		$papel  = self::find( $spec['roles'], $row['role'] );
		$checks = array(
			'name'    => array( '' !== $row['name'], 'el nombre' ),
			'surname' => array( '' !== $row['surname'], 'los apellidos' ),
			'dni'     => array( ApplicationInput::is_dni( $row['dni'] ), 'el DNI o NIE, completo y con su letra' ),
			'email'   => array( ProcedureInput::is_email( $row['email'] ), 'el correo electrónico' ),
			'phone'   => array( '' === $row['phone'] || (bool) preg_match( '/^\+?[0-9][0-9 ]{5,19}$/', $row['phone'] ), 'el teléfono, solo con cifras, espacios y un «+» delante si hace falta' ),
			'role'    => array( array() !== $papel, 'el papel' ),
		);
		if ( array() !== $papel ) {
			$grupo           = '' === $row['group'] || array() !== self::find( $spec['groups'], $row['group'] );
			$checks['group'] = array( $grupo && ( '' !== $row['group'] || empty( $papel['needs_group'] ) ), 'el grupo, que este papel exige' );
		}
		$out = array();
		foreach ( $checks as $clave => list( $bien, $texto ) ) {
			if ( ! $bien ) {
				$out[ $clave ] = $texto;
			}
		}
		return $out;
	}

	/**
	 * What is wrong with a row against the rest of the list.
	 *
	 * @param array<string, mixed>             $spec  Normalised specification.
	 * @param array<int, array<string, mixed>> $otras The other rows.
	 * @param array<string, mixed>             $row   Normalised row.
	 * @return array<string, string>
	 */
	private static function against( array $spec, array $otras, array $row ): array {
		foreach ( $otras as $otra ) {
			$mismo_sitio = $otra['role'] === $row['role'] && $otra['group'] === $row['group'];
			if ( $mismo_sitio && $otra['dni'] === $row['dni'] ) {
				return array( 'duplicate' => 'Esa persona ya está en la lista con el mismo papel y grupo.' );
			}
			if ( $mismo_sitio && $spec['unique_per_group'] && '' !== $row['group'] ) {
				return array( 'unique_group' => 'Ese papel ya lo tiene otra persona en ese grupo: solo cabe una por papel y grupo.' );
			}
		}
		if ( '' === $row['id'] && count( $otras ) + 1 > (int) $spec['max'] ) {
			return array( 'max' => 'La lista ya tiene ' . count( $otras ) . ' personas, que es el máximo que admite este procedimiento.' );
		}
		return array();
	}

	/**
	 * Whether the list reaches the minimum the procedure asks for.
	 *
	 * Por debajo del mínimo la solicitud no se rechaza: queda incompleta, y
	 * así se ve en la revisión. Sin lista, no hay nada que completar.
	 *
	 * @param array<string, mixed>             $spec   Specification.
	 * @param array<int, array<string, mixed>> $people The list.
	 * @return bool
	 */
	public static function is_complete( array $spec, array $people ): bool {
		$spec = PeopleSpec::sanitize( $spec );
		return ! $spec['enabled'] || count( self::rows( $people ) ) >= $spec['min'];
	}

	/**
	 * A short print of a row: changes with any of its data.
	 *
	 * Viaja en un campo oculto con el formulario de cada fila: si al guardar
	 * la fila ya no tiene la misma huella, otra persona la cambió mientras
	 * tanto y el cambio se rechaza en vez de pisarla.
	 *
	 * @param array<string, mixed> $row Row.
	 * @return string
	 */
	public static function fingerprint( array $row ): string {
		$row = self::row( $row );
		return substr( hash( 'sha256', implode( "\x1f", $row ) ), 0, 16 );
	}

	/**
	 * One person as a line of text: name, email, phone, role and group by
	 * their label. Never the DNI: this goes in mails.
	 *
	 * @param array<string, mixed> $spec Specification.
	 * @param array<string, mixed> $row  Row.
	 * @return string
	 */
	public static function describe( array $spec, array $row ): string {
		$row = self::row( $row );
		return implode(
			' · ',
			array_filter(
				array(
					trim( $row['name'] . ' ' . $row['surname'] ),
					$row['email'],
					$row['phone'],
					PeopleSpec::label( $spec, 'roles', $row['role'] ),
					PeopleSpec::label( $spec, 'groups', $row['group'] ),
				),
				'strlen'
			)
		);
	}

	/**
	 * A DNI with all but its last four characters hidden.
	 *
	 * @param string $dni DNI or NIE.
	 * @return string
	 */
	public static function masked_dni( string $dni ): string {
		$dni = trim( $dni );
		return strlen( $dni ) <= 4 ? $dni : str_repeat( '•', strlen( $dni ) - 4 ) . substr( $dni, -4 );
	}

	/**
	 * One item of a list of the specification, by key.
	 *
	 * @param array<int, array<string, mixed>> $items Roles or groups.
	 * @param string                           $key   Key.
	 * @return array<string, mixed> Empty when not found.
	 */
	private static function find( array $items, string $key ): array {
		foreach ( $items as $item ) {
			if ( '' !== $key && $key === (string) $item['key'] ) {
				return $item;
			}
		}
		return array();
	}
}
