<?php
/**
 * The «Personas» panel of the workshop: what a procedure asks about the
 * people of each application.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\Domain\PeopleSpec;
use Prc\Meta\ProcedureMetaKeys;

/**
 * El panel «Personas» del taller (ADR-0041).
 *
 * Lee lo que manda el panel y lo guarda en `prc_people_spec`; no comprueba
 * ni el nonce ni quién pregunta, que ya lo ha hecho
 * {@see ProcedureEditor::handle()} antes de llamar aquí, ni pinta nada, que
 * es de {@see View\PeopleView::spec_panel()}.
 *
 * Se puede tocar mientras el procedimiento se pueda editar, en cualquier
 * estado: la lista de personas tiene su propia ventana precisamente para
 * poder abrirla o alargarla con el plazo de solicitud cerrado. Lo que no se
 * puede es quitar un papel o un grupo que alguna solicitud usa
 * ({@see PeopleSpec::refusal()}).
 */
final class PeopleSpecPanel {

	public const FIELD_ENABLED  = 'prc_people_enabled';
	public const FIELD_ROLES    = 'prc_people_roles';
	public const FIELD_GROUPS   = 'prc_people_groups';
	public const FIELD_MIN      = 'prc_people_min';
	public const FIELD_MAX      = 'prc_people_max';
	public const FIELD_UNIQUE   = 'prc_people_unique';
	public const FIELD_OPENS    = 'prc_people_opens_at';
	public const FIELD_CLOSES   = 'prc_people_closes_at';
	public const FIELD_ADMITTED = 'prc_people_admitted_only';

	/** «Una persona puede tener varios papeles». */
	public const FIELD_MULTI_ROLE = 'prc_people_multi_role';

	/** «Exige indicar el grupo», one checkbox per role row. */
	public const FIELD_ROLE_GROUP = 'prc_people_role_group';

	/**
	 * Query arg of the «Solicitudes» panel: the application whose history is open.
	 */
	public const ARG_HISTORY = 'historial';

	/**
	 * The field each key of the typed specification comes from.
	 *
	 * @var array<string, string>
	 */
	private const FIELDS = array(
		'enabled'          => self::FIELD_ENABLED,
		'roles'            => self::FIELD_ROLES,
		'groups'           => self::FIELD_GROUPS,
		'min'              => self::FIELD_MIN,
		'max'              => self::FIELD_MAX,
		'unique_per_group' => self::FIELD_UNIQUE,
		'multi_role'       => self::FIELD_MULTI_ROLE,
		'opens_at'         => self::FIELD_OPENS,
		'closes_at'        => self::FIELD_CLOSES,
		'admitted_only'    => self::FIELD_ADMITTED,
	);

	/**
	 * Store what the panel sent.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return string Why it was not stored; empty when it was.
	 */
	public static function save( int $procedure_id ): string {
		if ( ProcedureMetaKeys::AUDIENCE_TEACHERS === get_post_meta( $procedure_id, ProcedureMetaKeys::AUDIENCE, true ) ) {
			return 'Los procedimientos para profesorado no tienen lista de personas: cada solicitud es de una sola persona.';
		}
		$antes = People::spec( $procedure_id );
		$res   = PeopleSpec::from_input( self::typed(), $antes );
		if ( '' !== $res['error'] ) {
			return $res['error'] . ' No se ha guardado nada.';
		}
		$porque = PeopleSpec::refusal( $antes, $res['spec'], People::used_keys( $procedure_id ) );
		if ( '' !== $porque ) {
			return $porque;
		}
		$coordina = Applications::coordinator_count( $procedure_id );
		$porque   = PeopleSpec::coordination_clash( $antes, $res['spec'], $coordina, $coordina );
		if ( '' !== $porque ) {
			return $porque;
		}
		update_post_meta( $procedure_id, ProcedureMetaKeys::PEOPLE_SPEC, wp_slash( $res['spec'] ) );
		return '';
	}

	/**
	 * What the panel sent, in the shape PeopleSpec::from_input() wants.
	 *
	 * Los papeles llegan en filas, cada uno con su casilla «Exige indicar el
	 * grupo», y se vuelven el texto de siempre: uno por línea, «| grupo»
	 * detrás del que lo exige.
	 *
	 * @return array<string, string>
	 */
	private static function typed(): array {
		$out = array();
		foreach ( self::FIELDS as $clave => $campo ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- el nonce lo comprobó ProcedureEditor::handle() antes de llamar aquí; se sanea justo debajo.
			$valor         = isset( $_POST[ $campo ] ) && is_string( $_POST[ $campo ] ) ? wp_unslash( $_POST[ $campo ] ) : '';
			$out[ $clave ] = in_array( $clave, array( 'roles', 'groups' ), true ) ? sanitize_textarea_field( $valor ) : sanitize_text_field( $valor );
		}
		// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- el nonce lo comprobó ProcedureEditor::handle(); cada rótulo se sanea en el bucle.
		if ( isset( $_POST[ self::FIELD_ROLES ] ) && is_array( $_POST[ self::FIELD_ROLES ] ) ) {
			$filas  = wp_unslash( $_POST[ self::FIELD_ROLES ] );
			$grupos = (array) wp_unslash( $_POST[ self::FIELD_ROLE_GROUP ] ?? array() );
			$lineas = array();
			foreach ( $filas as $i => $rotulo ) {
				$rotulo   = is_string( $rotulo ) ? sanitize_text_field( $rotulo ) : '';
				$lineas[] = '' !== $rotulo && ! empty( $grupos[ $i ] ) ? $rotulo . ' | ' . PeopleSpec::GROUP_MARK : $rotulo;
			}
			$out['roles'] = implode( "\n", $lineas );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return $out;
	}

	/**
	 * What the panel shows: the stored specification as the panel writes it,
	 * or what was typed when the last submit was refused.
	 *
	 * @param int  $procedure_id Procedure post ID.
	 * @param bool $rejected     Whether the last submit was refused.
	 * @return array<string, mixed>
	 */
	public static function model( int $procedure_id, bool $rejected ): array {
		$spec    = People::spec( $procedure_id );
		$valores = array(
			'enabled'          => $spec['enabled'] ? '1' : '',
			'roles'            => PeopleSpec::roles_text( $spec ),
			'groups'           => PeopleSpec::groups_text( $spec ),
			'min'              => (string) $spec['min'],
			'max'              => (string) $spec['max'],
			// Una lista que nunca se guardó empieza con una persona por papel
			// y grupo: es lo habitual, y quitarla es una decisión.
			'unique_per_group' => $spec['unique_per_group'] || ! metadata_exists( 'post', $procedure_id, ProcedureMetaKeys::PEOPLE_SPEC ) ? '1' : '',
			'multi_role'       => $spec['multi_role'] ? '1' : '',
			'opens_at'         => (string) $spec['opens_at'],
			'closes_at'        => (string) $spec['closes_at'],
			'admitted_only'    => $spec['admitted_only'] ? '1' : '',
		);
		// Un envío rechazado repinta lo tecleado, solo si era de este panel.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- solo se mira si el envío era de este panel; no se guarda nada.
		$de_aqui = $rejected && isset( $_POST[ self::FIELD_ROLES ] );
		$valores = $de_aqui ? self::typed() : $valores;
		return array(
			'teachers'   => ProcedureMetaKeys::AUDIENCE_TEACHERS === get_post_meta( $procedure_id, ProcedureMetaKeys::AUDIENCE, true ),
			'values'     => $valores,
			'role_rows'  => PeopleSpec::role_lines( (string) $valores['roles'] ),
			'max_people' => PeopleSpec::MAX_PEOPLE,
			'max_roles'  => PeopleSpec::MAX_ROLES,
			'max_groups' => PeopleSpec::MAX_GROUPS,
		);
	}

	/**
	 * The people and history of each application, for the review in the
	 * «Solicitudes» panel.
	 *
	 * El enlace al historial conserva el filtro y la página de la tabla.
	 *
	 * @param \WP_Post[]            $applications Applications already loaded.
	 * @param array<string, string> $args         Query args of the table, already URL-encoded.
	 * @return array<int, array<string, mixed>> Application ID => what PeopleForm::review() returns.
	 */
	public static function reviews( array $applications, array $args = array() ): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo elige qué historial se lee; leerlo lo autoriza el panel.
		$pedido = absint( wp_unslash( $_GET[ self::ARG_HISTORY ] ?? 0 ) );
		$out    = array();
		foreach ( $applications as $solicitud ) {
			$out[ (int) $solicitud->ID ] = PeopleForm::review( (int) $solicitud->ID, (int) $solicitud->ID === $pedido, $args );
		}
		return $out;
	}
}
