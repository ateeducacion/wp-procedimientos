<?php
/**
 * Pure handling of what a procedure asks about the people of an application.
 *
 * @package Prc
 */

namespace Prc\Domain;

use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;

/**
 * La especificación de la lista de personas de un procedimiento (ADR-0041).
 *
 * Pura: ni una llamada a WordPress. Se guarda en la meta `prc_people_spec`
 * del procedimiento con esta forma:
 *
 * ```
 * { enabled: true,
 *   roles:  [ { key: 'r1', label: 'Coordinación', needs_group: false },
 *             { key: 'r2', label: 'Participante', needs_group: true } ],
 *   role_seq: 2,
 *   groups: [ { key: 'g1', label: 'Eje 1' } ], group_seq: 1,
 *   min: 1, max: 20, unique_per_group: false,
 *   opens_at: '2026-11-01', closes_at: '2026-11-30', admitted_only: false }
 * ```
 *
 * Las claves de papeles y grupos las genera el aplicativo y salen de un
 * contador que nunca baja, como las preguntas (ADR-0040): renombrar no
 * descoloca a nadie y uno nuevo no hereda las personas de uno borrado.
 */
final class PeopleSpec {

	/** Most roles a procedure may define. */
	public const MAX_ROLES = 10;

	/** Most groups a procedure may define. */
	public const MAX_GROUPS = 20;

	/** Most people one application may list. */
	public const MAX_PEOPLE = 200;

	/**
	 * The marker that says, in the workshop text, that a role needs a group.
	 */
	public const GROUP_MARK = 'grupo';

	/**
	 * A stored or typed specification, normalised.
	 *
	 * @param mixed $raw Stored value.
	 * @return array{enabled:bool, roles:array<int, array{key:string, label:string, needs_group:bool}>, role_seq:int, groups:array<int, array{key:string, label:string}>, group_seq:int, min:int, max:int, unique_per_group:bool, opens_at:string, closes_at:string, admitted_only:bool}
	 */
	public static function sanitize( $raw ): array {
		$raw    = is_array( $raw ) ? $raw : array();
		$roles  = self::keyed( $raw['roles'] ?? array(), 'r', (int) ( $raw['role_seq'] ?? 0 ), self::MAX_ROLES );
		$groups = self::keyed( $raw['groups'] ?? array(), 'g', (int) ( $raw['group_seq'] ?? 0 ), self::MAX_GROUPS );
		$max    = self::number( $raw['max'] ?? null );
		$min    = self::number( $raw['min'] ?? null );
		return array(
			'enabled'          => ! empty( $raw['enabled'] ),
			'roles'            => array_map(
				static function ( array $role ): array {
					return array(
						'key'         => $role['key'],
						'label'       => $role['label'],
						'needs_group' => ! empty( $role['needs_group'] ),
					);
				},
				$roles['items']
			),
			'role_seq'         => $roles['seq'],
			'groups'           => array_map(
				static function ( array $group ): array {
					return array(
						'key'   => $group['key'],
						'label' => $group['label'],
					);
				},
				$groups['items']
			),
			'group_seq'        => $groups['seq'],
			'min'              => null === $min ? 0 : max( 0, min( $min, self::MAX_PEOPLE ) ),
			'max'              => null === $max || $max <= 0 ? self::MAX_PEOPLE : min( $max, self::MAX_PEOPLE ),
			'unique_per_group' => ! empty( $raw['unique_per_group'] ),
			'opens_at'         => self::date( $raw['opens_at'] ?? '' ),
			'closes_at'        => self::date( $raw['closes_at'] ?? '' ),
			'admitted_only'    => ! empty( $raw['admitted_only'] ),
		);
	}

	/**
	 * Normalise a list of `{key, label}` items and key the new ones.
	 *
	 * Un elemento sin rótulo o repetido se cae; uno sin clave válida recibe
	 * la siguiente al contador, que se devuelve actualizado.
	 *
	 * @param mixed  $raw    List of items.
	 * @param string $prefix Key prefix.
	 * @param int    $seq    Highest number given so far.
	 * @param int    $max    How many fit.
	 * @return array{items:array<int, array<string, mixed>>, seq:int}
	 */
	private static function keyed( $raw, string $prefix, int $seq, int $max ): array {
		$items = array();
		$mayor = max( 0, $seq );
		foreach ( is_array( $raw ) ? $raw : array() as $item ) {
			$item  = is_array( $item ) ? $item : array( 'label' => $item );
			$label = is_scalar( $item['label'] ?? null ) ? trim( (string) $item['label'] ) : '';
			$key   = is_scalar( $item['key'] ?? null ) ? strtolower( trim( (string) $item['key'] ) ) : '';
			if ( '' === $label || in_array( $label, array_column( $items, 'label' ), true ) || count( $items ) >= $max ) {
				continue;
			}
			$key     = preg_match( '/^' . $prefix . '[1-9]\d{0,3}$/', $key ) && ! in_array( $key, array_column( $items, 'key' ), true ) ? $key : '';
			$mayor   = max( $mayor, (int) substr( $key, 1 ) );
			$items[] = array(
				'key'         => $key,
				'label'       => $label,
				'needs_group' => ! empty( $item['needs_group'] ),
			);
		}
		foreach ( $items as $i => $item ) {
			if ( '' === $item['key'] ) {
				++$mayor;
				$items[ $i ]['key'] = $prefix . $mayor;
			}
		}
		return array(
			'items' => $items,
			'seq'   => $mayor,
		);
	}

	/**
	 * What the «Personas» panel of the workshop sent, as a specification.
	 *
	 * Los papeles y los grupos llegan como texto, uno por línea; un papel que
	 * termina en «| grupo» exige grupo. La clave de cada línea se deduce de lo
	 * que había, como las filas de una rejilla
	 * ({@see QuestionGrid::match_rows()}): una línea igual es esa; una
	 * cambiada en su mismo sitio es la misma renombrada.
	 *
	 * @param array<string, mixed> $raw      `enabled`, `roles`, `groups`, `min`, `max`, `unique_per_group`, `opens_at`, `closes_at`, `admitted_only`.
	 * @param array<string, mixed> $previous The stored specification.
	 * @return array{spec:array<string, mixed>, error:string} Error empty when it can be stored.
	 */
	public static function from_input( array $raw, array $previous ): array {
		$previous = self::sanitize( $previous );
		$roles    = self::typed_roles( self::text( $raw, 'roles' ), $previous['roles'] );
		$groups   = QuestionGrid::match_rows( self::text( $raw, 'groups' ), $previous['groups'] );
		$min      = self::text( $raw, 'min' );
		$max      = self::text( $raw, 'max' );
		$spec     = self::sanitize(
			array(
				'enabled'          => ! empty( $raw['enabled'] ),
				'roles'            => $roles,
				'role_seq'         => $previous['role_seq'],
				'groups'           => $groups,
				'group_seq'        => $previous['group_seq'],
				'min'              => $min,
				'max'              => $max,
				'unique_per_group' => ! empty( $raw['unique_per_group'] ),
				'opens_at'         => self::text( $raw, 'opens_at' ),
				'closes_at'        => self::text( $raw, 'closes_at' ),
				'admitted_only'    => ! empty( $raw['admitted_only'] ),
			)
		);
		$error    = self::input_error( $raw, $spec, count( $roles ), count( $groups ) );
		return array(
			'spec'  => $spec,
			'error' => $spec['enabled'] || '' === $error ? $error : '',
		);
	}

	/**
	 * The roles typed in the workshop, matched to the ones there were.
	 *
	 * @param string                           $text     One role per line.
	 * @param array<int, array<string, mixed>> $previous Stored roles.
	 * @return array<int, array{key:string, label:string, needs_group:bool}>
	 */
	private static function typed_roles( string $text, array $previous ): array {
		$marcas = array();
		$lineas = array();
		foreach ( (array) preg_split( '/\r\n|\r|\n/', $text ) as $linea ) {
			$partes = array_map( 'trim', explode( '|', (string) $linea ) );
			$marca  = count( $partes ) > 1 && self::GROUP_MARK === strtolower( (string) end( $partes ) );
			if ( $marca ) {
				array_pop( $partes );
			}
			$rotulo   = implode( ' | ', $partes );
			$lineas[] = $rotulo;
			$marcas[] = $marca;
		}
		$out = array();
		$i   = 0;
		foreach ( QuestionGrid::match_rows( implode( "\n", $lineas ), $previous ) as $role ) {
			// match_rows() se salta las líneas vacías: se avanza hasta la suya.
			while ( isset( $lineas[ $i ] ) && $lineas[ $i ] !== $role['label'] ) {
				++$i;
			}
			$out[] = $role + array( 'needs_group' => ! empty( $marcas[ $i ] ) );
			++$i;
		}
		return $out;
	}

	/**
	 * Why a typed specification cannot be stored, in words.
	 *
	 * @param array<string, mixed> $raw    What was typed.
	 * @param array<string, mixed> $spec   The normalised specification.
	 * @param int                  $roles  How many roles were typed.
	 * @param int                  $groups How many groups were typed.
	 * @return string Empty when it can.
	 */
	private static function input_error( array $raw, array $spec, int $roles, int $groups ): string {
		$fechas = array( self::text( $raw, 'opens_at' ), self::text( $raw, 'closes_at' ) );
		$checks = array(
			'Caben como mucho ' . self::MAX_ROLES . ' papeles.' => $roles > self::MAX_ROLES,
			'Caben como mucho ' . self::MAX_GROUPS . ' grupos.' => $groups > self::MAX_GROUPS,
			'Escriba al menos un papel, uno por línea.' => array() === $spec['roles'],
			'Un papel exige grupo, pero no hay grupos: escriba los grupos o quite «| grupo».' => array() === $spec['groups'] && in_array( true, array_column( $spec['roles'], 'needs_group' ), true ),
			'El mínimo y el máximo de personas tienen que ser números enteros, sin decimales.' => ! self::is_count( self::text( $raw, 'min' ) ) || ! self::is_count( self::text( $raw, 'max' ) ),
			'El máximo de personas no puede pasar de ' . self::MAX_PEOPLE . '.' => (int) self::text( $raw, 'max' ) > self::MAX_PEOPLE,
			'El mínimo de personas no puede ser mayor que el máximo.' => $spec['min'] > $spec['max'],
			'Las fechas de la lista de personas tienen que ser días que existen.' => ( '' !== $fechas[0] && '' === $spec['opens_at'] ) || ( '' !== $fechas[1] && '' === $spec['closes_at'] ),
			'La lista de personas no puede cerrarse antes de abrirse.' => '' !== $spec['opens_at'] && '' !== $spec['closes_at'] && $spec['closes_at'] < $spec['opens_at'],
		);
		foreach ( $checks as $texto => $falla ) {
			if ( $falla ) {
				return $texto;
			}
		}
		return '';
	}

	/**
	 * Why a new specification would leave people without their role or group.
	 *
	 * Quitar un papel o un grupo que alguna solicitud usa se rechaza: las
	 * personas dadas de alta con él se quedarían sin rótulo. Renombrarlo sí
	 * se puede, porque la clave no cambia.
	 *
	 * @param array<string, mixed>               $before The stored specification.
	 * @param array<string, mixed>               $after  The new one.
	 * @param array<string, array<string, bool>> $used   `roles` and `groups`: key => true.
	 * @return string Empty when nothing used goes away.
	 */
	public static function refusal( array $before, array $after, array $used ): string {
		foreach ( array(
			'roles'  => 'El papel',
			'groups' => 'El grupo',
		) as $lista => $nombre ) {
			$quedan = array_column( (array) ( $after[ $lista ] ?? array() ), 'key' );
			foreach ( (array) ( $before[ $lista ] ?? array() ) as $item ) {
				if ( ! in_array( $item['key'], $quedan, true ) && ! empty( $used[ $lista ][ $item['key'] ] ) ) {
					return $nombre . ' «' . $item['label'] . '» tiene personas dadas de alta en alguna solicitud: no se puede quitar. Puede cambiarle el nombre. No se ha guardado nada.';
				}
			}
		}
		return '';
	}

	/**
	 * The roles as the workshop writes them: one per line, «| grupo» behind.
	 *
	 * @param array<string, mixed> $spec Specification.
	 * @return string
	 */
	public static function roles_text( array $spec ): string {
		$lineas = array();
		foreach ( (array) ( $spec['roles'] ?? array() ) as $role ) {
			$lineas[] = (string) $role['label'] . ( ! empty( $role['needs_group'] ) ? ' | ' . self::GROUP_MARK : '' );
		}
		return implode( "\n", $lineas );
	}

	/**
	 * The groups as the workshop writes them: one per line.
	 *
	 * @param array<string, mixed> $spec Specification.
	 * @return string
	 */
	public static function groups_text( array $spec ): string {
		return implode( "\n", array_column( (array) ( $spec['groups'] ?? array() ), 'label' ) );
	}

	/**
	 * Label of one role or group, by key.
	 *
	 * @param array<string, mixed> $spec Specification.
	 * @param string               $which `roles` or `groups`.
	 * @param string               $key  Key.
	 * @return string Empty when the key is not there.
	 */
	public static function label( array $spec, string $which, string $key ): string {
		foreach ( (array) ( $spec[ $which ] ?? array() ) as $item ) {
			if ( $key === (string) $item['key'] ) {
				return (string) $item['label'];
			}
		}
		return '';
	}

	/**
	 * Why the list of an application cannot be written now, in words.
	 *
	 * Primero lo que la cierra siempre —apagada, profesorado, histórico,
	 * excluida, «solo admitidas»— y después la ventana. Con fechas propias,
	 * la ventana es esa, día de apertura y de cierre incluidos, aunque el
	 * plazo de solicitud esté cerrado: es lo que hoy obliga a reabrir. Sin
	 * fechas, es la de la solicitud: mientras el centro pueda editarla.
	 *
	 * @param array<string, mixed> $spec Specification.
	 * @param array<string, mixed> $ctx  `today` (Y-m-d), `archived`, `teacher`, `review` (review state), `state` (procedure state).
	 * @return string Empty when it can be written.
	 */
	public static function why_closed( array $spec, array $ctx ): string {
		$spec   = self::sanitize( $spec );
		$review = (string) ( $ctx['review'] ?? '' );
		$checks = array(
			'Este procedimiento no pide una lista de personas.' => ! $spec['enabled'] || ! empty( $ctx['teacher'] ),
			'El procedimiento está marcado como histórico: la lista de personas ya no se cambia.' => ! empty( $ctx['archived'] ),
			'La solicitud está excluida: su lista de personas ya no se cambia.' => ApplicationMetaKeys::REVIEW_EXCLUDED === $review,
			'La lista de personas se gestiona cuando la solicitud esté admitida, y todavía no lo está.' => $spec['admitted_only'] && ApplicationMetaKeys::REVIEW_ADMITTED !== $review,
		);
		foreach ( $checks as $texto => $cierra ) {
			if ( $cierra ) {
				return $texto;
			}
		}
		return self::window( $spec, (string) ( $ctx['today'] ?? '' ), (string) ( $ctx['state'] ?? '' ), $review );
	}

	/**
	 * Whether the window of the list is open today, and why not.
	 *
	 * @param array<string, mixed> $spec   Normalised specification.
	 * @param string               $today  Y-m-d.
	 * @param string               $state  Procedure state.
	 * @param string               $review Review state.
	 * @return string Empty when open.
	 */
	private static function window( array $spec, string $today, string $state, string $review ): string {
		$abre   = (string) $spec['opens_at'];
		$cierra = (string) $spec['closes_at'];
		if ( '' === $abre && '' === $cierra ) {
			return self::application_editable( $state, $review )
				? ''
				: 'La lista de personas sigue el plazo de la solicitud, y no está abierto para la suya.';
		}
		if ( '' !== $abre && $today < $abre ) {
			return 'La lista de personas se abre el ' . self::day( $abre ) . '.';
		}
		if ( '' !== $cierra && $today > $cierra ) {
			return 'El plazo de la lista de personas terminó el ' . self::day( $cierra ) . '.';
		}
		return '';
	}

	/**
	 * Whether the school may edit its application in this state: the rule of
	 * the application screen (ADR-0020).
	 *
	 * @param string $state  Procedure state.
	 * @param string $review Review state.
	 * @return bool
	 */
	public static function application_editable( string $state, string $review ): bool {
		return ProcedureState::accepts_applications( $state )
			|| ( ProcedureMetaKeys::STATE_AMENDMENT === $state && ApplicationMetaKeys::REVIEW_AMEND === $review );
	}

	/**
	 * A Y-m-d day as d-m-Y.
	 *
	 * @param string $ymd Day.
	 * @return string
	 */
	private static function day( string $ymd ): string {
		return implode( '-', array_reverse( explode( '-', $ymd ) ) );
	}

	/**
	 * A valid calendar day, or ''.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private static function date( $value ): string {
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';
		return ProcedureInput::is_valid_date( $value ) ? $value : '';
	}

	/**
	 * A whole number, or null.
	 *
	 * @param mixed $value Raw value.
	 * @return int|null
	 */
	private static function number( $value ): ?int {
		if ( is_int( $value ) ) {
			return $value;
		}
		return is_scalar( $value ) ? ProcedureQuestions::integer( (string) $value ) : null;
	}

	/**
	 * Whether a typed count is empty or a whole number from zero.
	 *
	 * @param string $value Typed value.
	 * @return bool
	 */
	private static function is_count( string $value ): bool {
		$n = ProcedureQuestions::integer( $value );
		return '' === $value || ( null !== $n && $n >= 0 );
	}

	/**
	 * One typed field, as trimmed text.
	 *
	 * @param array<string, mixed> $raw Raw fields.
	 * @param string               $key Field.
	 * @return string
	 */
	private static function text( array $raw, string $key ): string {
		$value = $raw[ $key ] ?? '';
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}
}
