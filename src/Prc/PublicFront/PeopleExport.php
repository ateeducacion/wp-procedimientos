<?php
/**
 * The columns and rows the list of people adds to the export.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\Domain\QuestionGrid;

/**
 * Lo que la lista de personas añade a la exportación a medida (ADR-0041).
 *
 * Va aparte de {@see ApplicationExport} para que esta no pase del umbral de
 * complejidad de PHPMD. Solo existe cuando el procedimiento pide la lista: sin
 * ella, ni columnas ni filas, y el CSV es el de siempre.
 */
final class PeopleExport {

	/**
	 * Columns of the list of people, in order, with their heading.
	 *
	 * @var array<string, string>
	 */
	public const COLUMNS = array(
		'person_name'    => 'Nombre (persona)',
		'person_surname' => 'Apellidos (persona)',
		'person_dni'     => 'DNI o NIE (persona)',
		'person_email'   => 'Correo (persona)',
		'person_phone'   => 'Teléfono (persona)',
		'person_role'    => 'Papel (persona)',
		'person_group'   => 'Grupo (persona)',
	);

	/**
	 * The field of a painted row each column takes.
	 *
	 * @var array<string, string>
	 */
	private const FIELDS = array(
		'person_name'    => 'name',
		'person_surname' => 'surname',
		'person_dni'     => 'dni',
		'person_email'   => 'email',
		'person_phone'   => 'phone',
		'person_role'    => 'role_label',
		'person_group'   => 'group_label',
	);

	/**
	 * Every column one can export, in the order of the table, with each grid
	 * open in its rows (ADR-0040) and the people ones after the coordination,
	 * when the procedure asks for the list.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return array<string, string> key => heading.
	 */
	public static function all_columns( int $procedure_id ): array {
		$preguntas = Applications::questions( $procedure_id );
		$columns   = QuestionGrid::expand( Applications::columns( $preguntas ), $preguntas );
		if ( ! People::enabled( $procedure_id ) ) {
			return $columns;
		}
		$out = array();
		foreach ( $columns as $clave => $rotulo ) {
			$out[ $clave ] = $rotulo;
			if ( 'coordinator_email' === $clave ) {
				$out += self::COLUMNS;
			}
		}
		return $out;
	}

	/**
	 * The people columns of some people, each column joined, as in the
	 * coordination.
	 *
	 * @param array<string, mixed>|null         $spec Specification of the list; null without one.
	 * @param array<int, array<string, string>> $rows People; none gives empty columns.
	 * @return array<string, string> Empty without a list.
	 */
	public static function cells( ?array $spec, array $rows ): array {
		if ( null === $spec ) {
			return array();
		}
		$filas = PeopleForm::rows( $spec, $rows );
		$out   = array();
		foreach ( self::FIELDS as $columna => $campo ) {
			$out[ $columna ] = implode( ', ', array_column( $filas, $campo ) );
		}
		return $out;
	}

	/**
	 * One export row per person of the list, with the coordination empty: a
	 * person of the list is not of the coordination.
	 *
	 * @param array<string, mixed>|null $spec           Specification of the list; null without one.
	 * @param array<string, mixed>      $row            The application row.
	 * @param int                       $application_id Application post ID.
	 * @return array<int, array<string, mixed>>
	 */
	public static function rows( ?array $spec, array $row, int $application_id ): array {
		$out = array();
		foreach ( null === $spec ? array() : People::of( $application_id ) as $persona ) {
			$out[] = array_merge(
				$row,
				array_fill_keys( array( 'coordinator', 'coordinator_dni', 'coordinator_email' ), '' ),
				self::cells( $spec, array( $persona ) )
			);
		}
		return $out;
	}

	/**
	 * The specification of the list of a procedure, or null when it asks for none.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return array<string, mixed>|null
	 */
	public static function spec( int $procedure_id ): ?array {
		return People::enabled( $procedure_id ) ? People::spec( $procedure_id ) : null;
	}

	/**
	 * A list of the export dialog without the entry of the list of people,
	 * when the procedure asks for none.
	 *
	 * @param array<string, string> $items        Key => label.
	 * @param string                $key          Key of the people entry.
	 * @param int                   $procedure_id Procedure post ID; 0 keeps everything.
	 * @return array<string, string>
	 */
	public static function offered( array $items, string $key, int $procedure_id ): array {
		if ( $procedure_id > 0 && ! People::enabled( $procedure_id ) ) {
			unset( $items[ $key ] );
		}
		return $items;
	}

	/**
	 * The people of the list of one application, as people of the user upload file.
	 *
	 * Con la forma de {@see ApplicationExport::people()}, todavía sin
	 * usuario: lo pone la exportación con la parte local del correo.
	 *
	 * @param int $application_id Application post ID.
	 * @return array<int, array<string, mixed>>
	 */
	public static function users( int $application_id ): array {
		$out = array();
		foreach ( People::of( $application_id ) as $persona ) {
			$out[] = array(
				'source'         => ApplicationExport::WHO_PEOPLE,
				'application_id' => $application_id,
				'user_id'        => 0,
				'username'       => '',
				'firstname'      => $persona['name'],
				'lastname'       => $persona['surname'],
				'email'          => $persona['email'],
			);
		}
		return $out;
	}
}
