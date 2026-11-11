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
	 * The error keys of a row refused because it clashes with another one.
	 *
	 * Solo con estos motivos el aviso habla de otra fila y lleva a ella.
	 *
	 * @var string[]
	 */
	public const CLASHES = array( 'duplicate', 'same_person', 'unique_group' );

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
		$errors = self::fields( $spec, $row );
		if ( array() !== $errors ) {
			return $errors;
		}
		return self::against( $spec, self::rows( $people ), $row );
	}

	/**
	 * The row the new one clashes with: the same person, or the taken role.
	 *
	 * Es la fila a la que lleva el aviso: para cambiar a alguien o
	 * sustituirlo se edita su fila, no se da de alta otra.
	 *
	 * @param array<string, mixed>             $spec   Specification of the procedure.
	 * @param array<int, array<string, mixed>> $people The list as it is now.
	 * @param array<string, mixed>             $row    The row that was refused.
	 * @return string The id of that row; empty when there is none.
	 */
	public static function clash( array $spec, array $people, array $row ): string {
		$choque = self::conflict( PeopleSpec::sanitize( $spec ), self::rows( $people ), self::row( $row ) );
		return null === $choque ? '' : (string) $choque['row']['id'];
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
			$checks['group'] = array( $grupo && ( '' !== $row['group'] || empty( $papel['needs_group'] ) ), 'el grupo, que el papel «' . $papel['label'] . '» exige (por ejemplo, el eje)' );
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
	 * @param array<string, mixed>             $spec   Normalised specification.
	 * @param array<int, array<string, mixed>> $people The whole list, the row itself included if it is there.
	 * @param array<string, mixed>             $row    Normalised row.
	 * @return array<string, string>
	 */
	private static function against( array $spec, array $people, array $row ): array {
		$choque = self::conflict( $spec, $people, $row );
		if ( null !== $choque ) {
			$quien  = trim( $choque['row']['name'] . ' ' . $choque['row']['surname'] );
			$donde  = 'use «Sustituir o corregir» en su fila.';
			$textos = array(
				'duplicate'    => 'Esa persona ya está en la lista con el mismo papel y grupo.',
				'same_person'  => $quien . ' ya está en la lista como «' . self::place( $spec, $choque['row'] ) . '». Para cambiarle el papel o sustituirla, ' . $donde,
				'unique_group' => 'Ese papel ya lo tiene ' . $quien . ' en ese grupo, y solo cabe una persona por papel y grupo. Para sustituirla, ' . $donde,
			);
			return array( $choque['error'] => $textos[ $choque['error'] ] );
		}
		$otras = count( self::others( $people, $row ) );
		if ( '' === $row['id'] && $otras + 1 > (int) $spec['max'] ) {
			return array( 'max' => 'La lista ya tiene ' . $otras . ' personas, que es el máximo que admite este procedimiento.' );
		}
		return array();
	}

	/**
	 * The first row the new one clashes with, and why.
	 *
	 * La misma persona con el mismo papel y grupo no entra nunca; con otro
	 * papel u otro grupo, solo si el procedimiento lo permite
	 * («Una persona puede tener varios papeles»). Una fila que ya estaba así
	 * guardada —una lista de antes de la regla— se sigue editando: solo se
	 * rechaza lo que el cambio trae nuevo.
	 *
	 * @param array<string, mixed>             $spec   Normalised specification.
	 * @param array<int, array<string, mixed>> $people The whole list.
	 * @param array<string, mixed>             $row    Normalised row.
	 * @return array{error:string, row:array<string, string>}|null
	 */
	private static function conflict( array $spec, array $people, array $row ): ?array {
		$ya_era = self::was_already( $people, $row );
		foreach ( self::others( $people, $row ) as $otra ) {
			$error = self::clash_kind( $spec, $otra, $row, $ya_era );
			if ( '' !== $error ) {
				return array(
					'error' => $error,
					'row'   => $otra,
				);
			}
		}
		return null;
	}

	/**
	 * Why the new row clashes with another one, if it does.
	 *
	 * @param array<string, mixed> $spec   Normalised specification.
	 * @param array<string, mixed> $otra   Another row of the list.
	 * @param array<string, mixed> $row    Normalised row.
	 * @param bool                 $ya_era Whether the row was already stored like this.
	 * @return string `duplicate`, `same_person`, `unique_group`, or empty.
	 */
	private static function clash_kind( array $spec, array $otra, array $row, bool $ya_era ): string {
		$mismo_sitio = $otra['role'] === $row['role'] && $otra['group'] === $row['group'];
		$misma       = $otra['dni'] === $row['dni'];
		if ( $mismo_sitio && $misma ) {
			return 'duplicate';
		}
		if ( $misma && ! $spec['multi_role'] && ! $ya_era ) {
			return 'same_person';
		}
		return $mismo_sitio && $spec['unique_per_group'] && '' !== $row['group'] ? 'unique_group' : '';
	}

	/**
	 * Whether the row is being edited without touching its person, role or group.
	 *
	 * @param array<int, array<string, mixed>> $people The whole list.
	 * @param array<string, mixed>             $row    Normalised row.
	 * @return bool
	 */
	private static function was_already( array $people, array $row ): bool {
		foreach ( $people as $fila ) {
			if ( '' !== $row['id'] && $fila['id'] === $row['id'] ) {
				return $fila['dni'] === $row['dni'] && $fila['role'] === $row['role'] && $fila['group'] === $row['group'];
			}
		}
		return false;
	}

	/**
	 * The other rows of the list: all but the one being edited.
	 *
	 * @param array<int, array<string, mixed>> $people The whole list.
	 * @param array<string, mixed>             $row    Normalised row.
	 * @return array<int, array<string, mixed>>
	 */
	private static function others( array $people, array $row ): array {
		return array_values(
			array_filter(
				$people,
				static function ( array $otra ) use ( $row ): bool {
					return '' === $row['id'] || $otra['id'] !== $row['id'];
				}
			)
		);
	}

	/**
	 * Role and group of a row, by their labels: «Participante · Eje 1».
	 *
	 * @param array<string, mixed> $spec Specification.
	 * @param array<string, mixed> $row  Row.
	 * @return string
	 */
	private static function place( array $spec, array $row ): string {
		return implode( ' · ', array_filter( array( PeopleSpec::label( $spec, 'roles', (string) $row['role'] ), PeopleSpec::label( $spec, 'groups', (string) $row['group'] ) ), 'strlen' ) );
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
	 * Con `$fields`, solo esos campos: la copia a quien consulta la zona lleva
	 * solo lo que la consulta enseña (ADR-0043); sin ninguno, nada.
	 *
	 * @param array<string, mixed> $spec   Specification.
	 * @param array<string, mixed> $row    Row.
	 * @param string[]|null        $fields Of `name`, `surname`, `email`, `phone`, `role`, `group`; null for all.
	 * @return string
	 */
	public static function describe( array $spec, array $row, ?array $fields = null ): string {
		$row    = self::row( $row );
		$partes = array(
			'name'    => $row['name'],
			'surname' => $row['surname'],
			'email'   => $row['email'],
			'phone'   => $row['phone'],
			'role'    => PeopleSpec::label( $spec, 'roles', $row['role'] ),
			'group'   => PeopleSpec::label( $spec, 'groups', $row['group'] ),
		);
		if ( null !== $fields ) {
			$partes = array_intersect_key( $partes, array_flip( $fields ) );
		}
		$nombre = trim( ( $partes['name'] ?? '' ) . ' ' . ( $partes['surname'] ?? '' ) );
		unset( $partes['name'], $partes['surname'] );
		return implode( ' · ', array_filter( array_merge( array( $nombre ), array_values( $partes ) ), 'strlen' ) );
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
