<?php
/**
 * Tests for the pure domain of the people of an application: the
 * specification of the procedure and the validation of each row.
 *
 * @package Prc
 */

use Prc\Domain\ApplicationPeople;
use Prc\Domain\PeopleSpec;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;

/**
 * Las personas de la solicitud, sin WordPress (ADR-0041).
 *
 * La especificación es del procedimiento —papeles, grupos, mínimo, máximo,
 * ventana— y la lista es de la solicitud: una fila por persona, con un `id`
 * que no cambia al sustituirla.
 */
class Test_Application_People extends WP_UnitTestCase {

	/**
	 * Una especificación con dos papeles, uno que exige grupo, y dos grupos.
	 *
	 * @param array<string, mixed> $extra Overrides.
	 * @return array<string, mixed>
	 */
	private function spec( array $extra = array() ): array {
		return PeopleSpec::sanitize(
			array_merge(
				array(
					'enabled'          => true,
					'roles'            => array(
						array(
							'key'         => 'r1',
							'label'       => 'Coordinación',
							'needs_group' => false,
						),
						array(
							'key'         => 'r2',
							'label'       => 'Participante',
							'needs_group' => true,
						),
					),
					'role_seq'         => 2,
					'groups'           => array(
						array(
							'key'   => 'g1',
							'label' => 'Eje 1',
						),
						array(
							'key'   => 'g2',
							'label' => 'Eje 2',
						),
					),
					'group_seq'        => 2,
					'min'              => 2,
					'max'              => 3,
					'unique_per_group' => false,
				),
				$extra
			)
		);
	}

	/**
	 * Una fila de persona válida.
	 *
	 * @param array<string, string> $extra Overrides.
	 * @return array<string, string>
	 */
	private function fila( array $extra = array() ): array {
		return array_merge(
			array(
				'id'      => '',
				'name'    => 'Ana',
				'surname' => 'Pérez Díaz',
				'dni'     => '12345678Z',
				'email'   => 'ana@example.org',
				'phone'   => '',
				'role'    => 'r2',
				'group'   => 'g1',
			),
			$extra
		);
	}

	// ─── la especificación ─────────────────────────────────────────────────

	/**
	 * Sin nada guardado, la lista está apagada y los topes son los de siempre.
	 */
	public function test_an_empty_spec_is_switched_off() {
		$spec = PeopleSpec::sanitize( '' );
		$this->assertFalse( $spec['enabled'] );
		$this->assertSame( array(), $spec['roles'] );
		$this->assertSame( 0, $spec['min'] );
		$this->assertSame( PeopleSpec::MAX_PEOPLE, $spec['max'] );
		$this->assertSame( '', $spec['opens_at'] );
	}

	/**
	 * Lo escrito en el taller: un papel por línea, «| grupo» detrás si lo
	 * exige; las claves las pone el aplicativo y el contador no baja.
	 */
	public function test_the_workshop_text_becomes_keyed_roles_and_groups() {
		$res = PeopleSpec::from_input(
			array(
				'enabled' => true,
				'roles'   => "Coordinación\nParticipante | grupo",
				'groups'  => "Eje 1\nEje 2",
				'min'     => '1',
				'max'     => '20',
			),
			PeopleSpec::sanitize( array() )
		);
		$this->assertSame( '', $res['error'] );
		$spec = $res['spec'];
		$this->assertSame(
			array(
				array(
					'key'         => 'r1',
					'label'       => 'Coordinación',
					'needs_group' => false,
				),
				array(
					'key'         => 'r2',
					'label'       => 'Participante',
					'needs_group' => true,
				),
			),
			$spec['roles']
		);
		$this->assertSame( array( 'g1', 'g2' ), array_column( $spec['groups'], 'key' ) );
		$this->assertSame( 2, $spec['role_seq'] );
		$this->assertSame( "Coordinación\nParticipante | grupo", PeopleSpec::roles_text( $spec ) );
	}

	/**
	 * Una línea que acaba en «|» o lleva «||» no descoloca la marca «| grupo»
	 * de las siguientes: cada marca va con el papel de su línea.
	 */
	public function test_a_stray_bar_does_not_move_the_group_mark_to_another_role() {
		foreach ( array( "Coordinación |\nParticipante | grupo", "Coordinación ||\nParticipante | grupo" ) as $roles ) {
			$res = PeopleSpec::from_input(
				array(
					'enabled' => true,
					'roles'   => $roles,
					'groups'  => 'Eje 1',
					'min'     => '1',
					'max'     => '20',
				),
				PeopleSpec::sanitize( array() )
			);
			$this->assertSame( '', $res['error'], $roles );
			$this->assertSame(
				array(
					array(
						'key'         => 'r1',
						'label'       => 'Coordinación',
						'needs_group' => false,
					),
					array(
						'key'         => 'r2',
						'label'       => 'Participante',
						'needs_group' => true,
					),
				),
				$res['spec']['roles'],
				$roles
			);
		}

		$res = PeopleSpec::from_input(
			array(
				'enabled' => true,
				'roles'   => "A ||\nB | grupo",
				'groups'  => 'Eje 1',
			),
			PeopleSpec::sanitize( array() )
		);
		$this->assertSame( array( 'A', 'B' ), array_column( $res['spec']['roles'], 'label' ) );
		$this->assertSame( array( false, true ), array_column( $res['spec']['roles'], 'needs_group' ) );

		// Una línea que solo lleva barras o la marca no es un papel.
		$res = PeopleSpec::from_input(
			array(
				'enabled' => true,
				'roles'   => "||\n| grupo\nB | grupo",
				'groups'  => 'Eje 1',
			),
			PeopleSpec::sanitize( array() )
		);
		$this->assertSame( array( 'B' ), array_column( $res['spec']['roles'], 'label' ) );
		$this->assertSame( array( true ), array_column( $res['spec']['roles'], 'needs_group' ) );
	}

	/**
	 * Renombrar un papel o un grupo conserva su clave; uno nuevo no hereda la
	 * de uno borrado.
	 */
	public function test_renaming_keeps_the_key_and_a_new_one_never_reuses_one() {
		$antes     = $this->spec();
		$renombrar = PeopleSpec::from_input(
			array(
				'enabled' => true,
				'roles'   => "Coordinación general\nParticipante | grupo",
				'groups'  => "Eje uno\nEje 2",
				'min'     => '2',
				'max'     => '3',
			),
			$antes
		)['spec'];
		$this->assertSame( array( 'r1', 'r2' ), array_column( $renombrar['roles'], 'key' ) );
		$this->assertSame( 'Coordinación general', $renombrar['roles'][0]['label'] );
		$this->assertSame( array( 'g1', 'g2' ), array_column( $renombrar['groups'], 'key' ) );

		$quitar = PeopleSpec::from_input(
			array(
				'enabled' => true,
				'roles'   => "\nParticipante | grupo",
				'groups'  => "Eje 1\nEje 2",
			),
			$antes
		)['spec'];
		$nuevo  = PeopleSpec::from_input(
			array(
				'enabled' => true,
				'roles'   => "Participante | grupo\nSuplencia",
				'groups'  => "Eje 1\nEje 2",
			),
			$quitar
		)['spec'];
		$this->assertSame( array( 'r2', 'r3' ), array_column( $nuevo['roles'], 'key' ) );
		$this->assertSame( 3, $nuevo['role_seq'] );
	}

	/**
	 * Lo que el taller no guarda: más papeles o grupos de los que caben, un
	 * papel que exige grupo sin grupos, un tope imposible o fechas al revés.
	 */
	public function test_the_workshop_refuses_what_cannot_be_kept() {
		$vacia = PeopleSpec::sanitize( array() );
		$casos = array(
			'demasiados papeles'    => array( 'roles' => implode( "\n", range( 1, 11 ) ) ),
			'demasiados grupos'     => array(
				'roles'  => 'A',
				'groups' => implode( "\n", range( 1, 21 ) ),
			),
			'sin papeles'           => array( 'roles' => '' ),
			'exige grupo sin ellos' => array( 'roles' => 'A | grupo' ),
			'máximo por encima'     => array(
				'roles' => 'A',
				'max'   => '201',
			),
			'mínimo sobre máximo'   => array(
				'roles' => 'A',
				'min'   => '5',
				'max'   => '4',
			),
			'mínimo no entero'      => array(
				'roles' => 'A',
				'min'   => '1,5',
			),
			'cierre antes'          => array(
				'roles'     => 'A',
				'opens_at'  => '2026-10-10',
				'closes_at' => '2026-10-01',
			),
			'fecha que no existe'   => array(
				'roles'    => 'A',
				'opens_at' => '2026-02-30',
			),
		);
		foreach ( $casos as $caso => $crudo ) {
			$res = PeopleSpec::from_input( array( 'enabled' => true ) + $crudo, $vacia );
			$this->assertNotSame( '', $res['error'], $caso );
		}
		// Apagada no exige papeles, pero lo demás se valida igual: al volver
		// a encenderla, lo guardado tiene que servir.
		$this->assertSame( '', PeopleSpec::from_input( array( 'enabled' => false ), $vacia )['error'] );
		$this->assertNotSame(
			'',
			PeopleSpec::from_input(
				array(
					'enabled' => false,
					'min'     => '5',
					'max'     => '4',
				),
				$vacia
			)['error']
		);
	}

	/**
	 * Quitar un papel o un grupo que alguna solicitud usa se rechaza con su nombre.
	 */
	public function test_removing_a_role_or_group_in_use_is_refused() {
		$antes   = $this->spec();
		$despues = PeopleSpec::from_input(
			array(
				'enabled' => true,
				'roles'   => "Coordinación\n",
				'groups'  => "Eje 1\n",
			),
			$antes
		)['spec'];
		$usados  = array(
			'roles'  => array( 'r2' => true ),
			'groups' => array(),
		);
		$this->assertStringContainsString( '«Participante»', PeopleSpec::refusal( $antes, $despues, $usados ) );
		$this->assertSame( '', PeopleSpec::refusal( $antes, $despues, array() ) );
		$this->assertStringContainsString(
			'«Eje 2»',
			PeopleSpec::refusal(
				$antes,
				$despues,
				array(
					'roles'  => array(),
					'groups' => array( 'g2' => true ),
				)
			)
		);
	}

	// ─── la ventana ────────────────────────────────────────────────────────

	/**
	 * Un contexto de solicitud.
	 *
	 * @param array<string, mixed> $extra Overrides.
	 * @return array<string, mixed>
	 */
	private function ctx( array $extra = array() ): array {
		return array_merge(
			array(
				'today'    => '2026-11-10',
				'archived' => false,
				'teacher'  => false,
				'review'   => ApplicationMetaKeys::REVIEW_SUBMITTED,
				'state'    => ProcedureMetaKeys::STATE_CLOSED,
			),
			$extra
		);
	}

	/**
	 * Con fechas propias manda la ventana propia, día de apertura y de cierre
	 * incluidos, aunque el plazo de solicitud esté cerrado.
	 */
	public function test_the_own_window_includes_both_days() {
		$spec = $this->spec(
			array(
				'opens_at'  => '2026-11-10',
				'closes_at' => '2026-11-20',
			)
		);
		$this->assertSame( '', PeopleSpec::why_closed( $spec, $this->ctx() ), 'día de apertura' );
		$this->assertSame( '', PeopleSpec::why_closed( $spec, $this->ctx( array( 'today' => '2026-11-20' ) ) ), 'día de cierre' );
		$this->assertStringContainsString( 'se abre', PeopleSpec::why_closed( $spec, $this->ctx( array( 'today' => '2026-11-09' ) ) ) );
		$this->assertStringContainsString( 'terminó', PeopleSpec::why_closed( $spec, $this->ctx( array( 'today' => '2026-11-21' ) ) ) );
	}

	/**
	 * Sin fechas, la ventana es la de la solicitud: abierta mientras el centro
	 * puede editarla.
	 */
	public function test_without_dates_the_window_is_the_application_one() {
		$spec = $this->spec();
		$this->assertNotSame( '', PeopleSpec::why_closed( $spec, $this->ctx() ) );
		$this->assertSame( '', PeopleSpec::why_closed( $spec, $this->ctx( array( 'state' => ProcedureMetaKeys::STATE_OPEN ) ) ) );
		$this->assertSame(
			'',
			PeopleSpec::why_closed(
				$spec,
				$this->ctx(
					array(
						'state'  => ProcedureMetaKeys::STATE_AMENDMENT,
						'review' => ApplicationMetaKeys::REVIEW_AMEND,
					)
				)
			)
		);
		$this->assertNotSame( '', PeopleSpec::why_closed( $spec, $this->ctx( array( 'state' => ProcedureMetaKeys::STATE_AMENDMENT ) ) ) );
	}

	/**
	 * Hasta cuándo se cambia la lista, para decirlo en la pantalla y en el
	 * acuse: su último día propio o, sin fechas, el cierre del plazo de
	 * solicitud. Lo que ya pasó, o un plazo sin final, no se dice.
	 */
	public function test_until_is_the_last_day_the_list_can_be_changed() {
		$propia = $this->spec(
			array(
				'opens_at'  => '2026-11-10',
				'closes_at' => '2026-11-20',
			)
		);
		$this->assertSame( '2026-11-20', PeopleSpec::until( $propia, '2026-11-15', '2026-11-12' ), 'la fecha propia manda' );
		$this->assertSame( '2026-11-15', PeopleSpec::until( $this->spec(), '2026-11-15', '2026-11-12' ), 'sin fechas, la de la solicitud' );
		$this->assertSame( '', PeopleSpec::until( $this->spec(), '2026-11-15', '2026-11-16' ), 'pasada, no se dice' );
		$this->assertSame( '', PeopleSpec::until( $this->spec( array( 'opens_at' => '2026-11-10' ) ), '2026-11-15', '2026-11-12' ), 'abierta sin final' );
		$this->assertSame( '', PeopleSpec::until( $this->spec( array( 'enabled' => false ) ), '2026-11-15', '2026-11-12' ), 'sin lista' );
		$this->assertSame( '', PeopleSpec::until( $this->spec(), '', '2026-11-12' ), 'sin plazo' );
	}

	/**
	 * Histórico, excluida, profesorado o apagada: nunca; con «solo admitidas»,
	 * solo admitida.
	 */
	public function test_what_closes_the_list_whatever_the_dates() {
		$abierta = $this->spec(
			array(
				'opens_at'  => '2026-11-01',
				'closes_at' => '2026-11-30',
			)
		);
		$this->assertStringContainsString( 'histórico', PeopleSpec::why_closed( $abierta, $this->ctx( array( 'archived' => true ) ) ) );
		$this->assertStringContainsString( 'excluida', PeopleSpec::why_closed( $abierta, $this->ctx( array( 'review' => ApplicationMetaKeys::REVIEW_EXCLUDED ) ) ) );
		$this->assertNotSame( '', PeopleSpec::why_closed( $abierta, $this->ctx( array( 'teacher' => true ) ) ) );
		$this->assertNotSame( '', PeopleSpec::why_closed( $this->spec( array( 'enabled' => false ) ), $this->ctx() ) );

		$admitidas = $this->spec(
			array(
				'opens_at'      => '2026-11-01',
				'closes_at'     => '2026-11-30',
				'admitted_only' => true,
			)
		);
		$this->assertStringContainsString( 'admitida', PeopleSpec::why_closed( $admitidas, $this->ctx() ) );
		$this->assertSame( '', PeopleSpec::why_closed( $admitidas, $this->ctx( array( 'review' => ApplicationMetaKeys::REVIEW_ADMITTED ) ) ) );
	}

	// ─── las filas ─────────────────────────────────────────────────────────

	/**
	 * Una fila válida no tiene errores; el DNI y el correo se normalizan.
	 */
	public function test_a_good_row_passes_and_is_normalised() {
		$fila = ApplicationPeople::row(
			$this->fila(
				array(
					'dni'   => ' 12345678-z ',
					'email' => 'Ana@Example.org',
				)
			)
		);
		$this->assertSame( '12345678Z', $fila['dni'] );
		$this->assertSame( 'ana@example.org', $fila['email'] );
		$this->assertSame( array(), ApplicationPeople::validate( $this->spec(), array(), $fila ) );
	}

	/**
	 * Cada campo que falta o está mal se dice por su clave.
	 */
	public function test_each_wrong_field_is_named() {
		$errores = ApplicationPeople::validate(
			$this->spec(),
			array(),
			ApplicationPeople::row(
				array(
					'name'    => '',
					'surname' => '',
					'dni'     => '12345678A',
					'email'   => 'no-es-correo',
					'phone'   => 'llámame',
					'role'    => 'r9',
					'group'   => 'g9',
				)
			)
		);
		$this->assertSame( array( 'name', 'surname', 'dni', 'email', 'phone', 'role' ), array_keys( $errores ) );
	}

	/**
	 * Un papel que exige grupo no se guarda sin grupo; uno que no lo exige, sí.
	 */
	public function test_a_role_that_needs_a_group_is_refused_without_one() {
		$spec = $this->spec();
		$this->assertArrayHasKey( 'group', ApplicationPeople::validate( $spec, array(), ApplicationPeople::row( $this->fila( array( 'group' => '' ) ) ) ) );
		$this->assertSame(
			array(),
			ApplicationPeople::validate(
				$spec,
				array(),
				ApplicationPeople::row(
					$this->fila(
						array(
							'role'  => 'r1',
							'group' => '',
						)
					)
				)
			)
		);
	}

	/**
	 * La misma persona (DNI) con el mismo papel y grupo, rechazada; con otro
	 * grupo, admitida.
	 */
	public function test_the_same_person_with_the_same_role_and_group_is_refused() {
		$spec  = $this->spec();
		$lista = array( ApplicationPeople::row( $this->fila( array( 'id' => 'a1' ) ) ) );
		$this->assertArrayHasKey( 'duplicate', ApplicationPeople::validate( $spec, $lista, ApplicationPeople::row( $this->fila( array( 'dni' => '12345678z' ) ) ) ) );
		$this->assertSame( array(), ApplicationPeople::validate( $spec, $lista, ApplicationPeople::row( $this->fila( array( 'group' => 'g2' ) ) ) ) );
		// Editar la propia fila no choca consigo misma.
		$this->assertSame( array(), ApplicationPeople::validate( $spec, $lista, ApplicationPeople::row( $this->fila( array( 'id' => 'a1' ) ) ) ) );
	}

	/**
	 * Con «una por papel y grupo», dos personas no comparten papel y grupo.
	 */
	public function test_unique_per_group_keeps_one_person_per_role_and_group() {
		$spec  = $this->spec( array( 'unique_per_group' => true ) );
		$lista = array( ApplicationPeople::row( $this->fila( array( 'id' => 'a1' ) ) ) );
		$otra  = ApplicationPeople::row(
			$this->fila(
				array(
					'dni'   => '11111111H',
					'email' => 'luis@example.org',
				)
			)
		);
		$this->assertArrayHasKey( 'unique_group', ApplicationPeople::validate( $spec, $lista, $otra ) );
		$this->assertArrayNotHasKey( 'unique_group', ApplicationPeople::validate( $this->spec(), $lista, $otra ) );
	}

	/**
	 * Por encima del máximo, rechazada; por debajo del mínimo, no: la
	 * solicitud queda incompleta.
	 */
	public function test_above_the_maximum_is_refused_and_below_the_minimum_is_incomplete() {
		$spec  = $this->spec();
		$lista = array();
		foreach ( array( '12345678Z', '11111111H', 'X1234567L' ) as $i => $dni ) {
			$lista[] = ApplicationPeople::row(
				$this->fila(
					array(
						'id'  => 'a' . $i,
						'dni' => $dni,
					)
				)
			);
		}
		$this->assertArrayHasKey( 'max', ApplicationPeople::validate( $spec, $lista, ApplicationPeople::row( $this->fila( array( 'dni' => '00000000T' ) ) ) ) );
		$this->assertTrue( ApplicationPeople::is_complete( $spec, $lista ) );
		$this->assertFalse( ApplicationPeople::is_complete( $spec, array_slice( $lista, 0, 1 ) ) );
		$this->assertTrue( ApplicationPeople::is_complete( $this->spec( array( 'enabled' => false ) ), array() ), 'sin lista no hay nada que completar' );
	}

	/**
	 * La huella cambia con cualquier dato de la fila y no con el orden de sus claves.
	 */
	public function test_the_fingerprint_follows_the_data() {
		$fila = ApplicationPeople::row( $this->fila( array( 'id' => 'a1' ) ) );
		$this->assertSame( ApplicationPeople::fingerprint( $fila ), ApplicationPeople::fingerprint( array_reverse( $fila, true ) ) );
		$this->assertNotSame( ApplicationPeople::fingerprint( $fila ), ApplicationPeople::fingerprint( array( 'phone' => '600000000' ) + $fila ) );
	}

	/**
	 * Lo que se lee de una persona en un correo: papel y grupo por su rótulo,
	 * y sin el DNI.
	 */
	public function test_describe_uses_labels_and_leaves_the_dni_out() {
		$texto = ApplicationPeople::describe( $this->spec(), ApplicationPeople::row( $this->fila() ) );
		$this->assertSame( 'Ana Pérez Díaz · ana@example.org · Participante · Eje 1', $texto );
		$this->assertStringNotContainsString( '12345678Z', $texto );
	}
}
