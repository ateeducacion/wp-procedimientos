<?php
/**
 * Tests for the list of people of an application: who writes it, when, and
 * what each change leaves behind.
 *
 * @package Prc
 */

use Prc\Access\ProcedureAccess;
use Prc\Domain\ApplicationPeople;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PublicFront\ApplicationLog;
use Prc\PublicFront\Applications;
use Prc\PublicFront\People;

/**
 * La lista de personas de una solicitud, guardada (ADR-0041).
 *
 * El guardián es `ProcedureAccess::can_edit_people()`: mismo centro, ventana
 * propia, nunca excluida ni histórica, y con «solo admitidas», admitida.
 * Cada alta, cambio o baja relee la lista al escribir, deja una entrada en el
 * historial y emite `prc_people_changed`.
 */
class Test_People_List extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Correos capturados.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private $enviados = array();

	/**
	 * Con el aplicativo, un catálogo inventado y el correo capturado.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
		$this->pages();
		remove_all_filters( 'prc_centres' );
		add_filter(
			'prc_centres',
			static function (): array {
				return array(
					array(
						'code'      => 'C0001',
						'name'      => 'Centro de prueba Norte',
						'ownership' => ProcedureMetaKeys::OWNERSHIP_PUBLIC,
					),
					array(
						'code'      => 'C0002',
						'name'      => 'Centro de prueba Sur',
						'ownership' => ProcedureMetaKeys::OWNERSHIP_PUBLIC,
					),
				);
			}
		);
		$this->enviados = array();
		add_filter(
			'pre_wp_mail',
			function ( $corto, array $atts ): bool {
				unset( $corto );
				$this->enviados[] = $atts;
				return true;
			},
			10,
			2
		);
	}

	/**
	 * Un día relativo a hoy, en Y-m-d.
	 *
	 * @param int $dias Days from today.
	 * @return string
	 */
	private function dia( int $dias ): string {
		return gmdate( 'Y-m-d', (int) strtotime( ProcedureState::today() . ' ' . sprintf( '%+d', $dias ) . ' days' ) );
	}

	/**
	 * La especificación de prueba: dos papeles, uno con grupo, y dos grupos.
	 *
	 * @param array<string, mixed> $extra Overrides.
	 * @return array<string, mixed>
	 */
	private function spec( array $extra = array() ): array {
		return array_merge(
			array(
				'enabled'   => true,
				'roles'     => array(
					array(
						'key'   => 'r1',
						'label' => 'Coordinación',
					),
					array(
						'key'         => 'r2',
						'label'       => 'Participante',
						'needs_group' => true,
					),
				),
				'groups'    => array(
					array(
						'key'   => 'g1',
						'label' => 'Eje 1',
					),
					array(
						'key'   => 'g2',
						'label' => 'Eje 2',
					),
				),
				'min'       => 2,
				'max'       => 5,
				'opens_at'  => $this->dia( -1 ),
				'closes_at' => $this->dia( 1 ),
			),
			$extra
		);
	}

	/**
	 * Un procedimiento con el plazo de solicitud ya cerrado y la lista de personas abierta.
	 *
	 * @param array<string, mixed> $spec Specification overrides; `false` for none at all.
	 * @param array<string, mixed> $meta Extra procedure meta.
	 * @return int Procedure ID.
	 */
	private function procedimiento( $spec = array(), array $meta = array() ): int {
		$base = array(
			ProcedureMetaKeys::OPENS_AT       => $this->dia( -30 ),
			ProcedureMetaKeys::CLOSES_AT      => $this->dia( -10 ),
			ProcedureMetaKeys::CONTACT_EMAILS => array( 'gestion@example.org' ),
		);
		if ( false !== $spec ) {
			$base[ ProcedureMetaKeys::PEOPLE_SPEC ] = $this->spec( $spec );
		}
		return $this->procedure( $this->administrator(), array( $this->area( 'Área de prueba' ) ), $meta + $base, array( 'post_title' => 'Red de prueba' ) );
	}

	/**
	 * Una persona válida.
	 *
	 * @param array<string, string> $extra Overrides.
	 * @return array<string, string>
	 */
	private function persona( array $extra = array() ): array {
		return array_merge(
			array(
				'name'    => 'Ana',
				'surname' => 'Pérez Díaz',
				'dni'     => '12345678Z',
				'email'   => 'ana@example.org',
				'phone'   => '600 000 000',
				'role'    => 'r2',
				'group'   => 'g1',
			),
			$extra
		);
	}

	/**
	 * Alta, cambio y baja dejan tres entradas en el historial, una acción y
	 * un correo a los contactos cada una, y la sustitución conserva el `id`.
	 */
	public function test_add_change_and_remove_leave_three_entries_and_three_mails() {
		$procedimiento = $this->procedimiento();
		$director      = $this->school_head( 'C0001' );
		$solicitud     = $this->application( $procedimiento, $director );
		$acciones      = array();
		add_action(
			'prc_people_changed',
			function ( $id, $change, $before, $after, $user_id ) use ( &$acciones ) {
				$acciones[] = array( $id, $change, $before, $after, $user_id );
			},
			10,
			5
		);

		$alta = People::add( $solicitud, $director, $this->persona() );
		$this->assertTrue( $alta['ok'], $alta['error'] );
		$fila = People::of( $solicitud )[0];
		$this->assertMatchesRegularExpression( '/^[a-z0-9]{12}$/', $fila['id'] );

		$cambio = People::change(
			$solicitud,
			$director,
			$fila['id'],
			ApplicationPeople::fingerprint( $fila ),
			$this->persona(
				array(
					'name'  => 'Luis',
					'dni'   => '11111111H',
					'email' => 'luis@example.org',
				)
			)
		);
		$this->assertTrue( $cambio['ok'], $cambio['error'] );
		$sustituta = People::of( $solicitud )[0];
		$this->assertSame( $fila['id'], $sustituta['id'], 'sustituir es editar la fila' );
		$this->assertSame( 'Luis', $sustituta['name'] );

		$baja = People::remove( $solicitud, $director, $sustituta['id'], ApplicationPeople::fingerprint( $sustituta ) );
		$this->assertTrue( $baja['ok'], $baja['error'] );
		$this->assertSame( array(), People::of( $solicitud ) );

		$historial = ApplicationLog::all( $solicitud );
		$this->assertSame( array( 'person_added', 'person_changed', 'person_removed' ), array_column( $historial, 'event' ) );
		$this->assertSame( $director, $historial[1]['user'] );
		$this->assertSame( 'Ana', $historial[1]['data']['before']['name'] );
		$this->assertSame( 'Luis', $historial[1]['data']['after']['name'] );

		$this->assertSame( array( People::ADDED, People::CHANGED, People::REMOVED ), array_column( $acciones, 1 ) );
		$this->assertSame( $director, $acciones[0][4] );

		$this->assertCount( 3, $this->enviados );
		foreach ( $this->enviados as $correo ) {
			$this->assertSame( 'gestion@example.org', $correo['to'] );
			$this->assertStringNotContainsString( '12345678Z', $correo['message'], 'el DNI no va en el correo' );
			$this->assertStringNotContainsString( '11111111H', $correo['message'] );
		}
		$this->assertStringContainsString( 'Participante', $this->enviados[1]['message'], 'el papel por su rótulo' );
		$this->assertStringContainsString( 'Eje 1', $this->enviados[1]['message'] );
		$this->assertStringContainsString( 'Centro de prueba', $this->enviados[1]['message'] );
		$this->assertStringContainsString( 'Ana Pérez Díaz', $this->enviados[1]['message'], 'el antes' );
		$this->assertStringContainsString( 'Luis Pérez Díaz', $this->enviados[1]['message'], 'el después' );
	}

	/**
	 * El aviso a los contactos se apaga en el panel «Avisos», y la copia de
	 * administración no lo recibe.
	 */
	public function test_the_mail_goes_to_contacts_only_and_can_be_switched_off() {
		update_option( 'prc_mail_copy', 'copia@example.org' );
		$procedimiento = $this->procedimiento();
		$director      = $this->school_head( 'C0001' );
		$solicitud     = $this->application( $procedimiento, $director );
		People::add( $solicitud, $director, $this->persona() );
		$this->assertSame( array( 'gestion@example.org' ), array_column( $this->enviados, 'to' ) );

		$this->enviados = array();
		update_post_meta( $procedimiento, ProcedureMetaKeys::NOTIFY, array( 'people_changed' ) );
		People::add( $solicitud, $director, $this->persona( array( 'group' => 'g2' ) ) );
		$this->assertSame( array(), $this->enviados );
		$this->assertCount( 2, People::of( $solicitud ), 'apagar el correo no apaga el alta' );
		delete_option( 'prc_mail_copy' );
	}

	/**
	 * Tocar personas no devuelve la solicitud a «presentada» ni cambia la nota.
	 */
	public function test_touching_people_keeps_the_review() {
		$procedimiento = $this->procedimiento();
		$director      = $this->school_head( 'C0001' );
		$solicitud     = $this->application(
			$procedimiento,
			$director,
			'',
			array(
				ApplicationMetaKeys::REVIEW_STATE => ApplicationMetaKeys::REVIEW_ADMITTED,
				ApplicationMetaKeys::REVIEW_NOTE  => 'Admitida con dos ejes.',
			)
		);
		$this->assertTrue( People::add( $solicitud, $director, $this->persona() )['ok'] );
		$this->assertSame( ApplicationMetaKeys::REVIEW_ADMITTED, get_post_meta( $solicitud, ApplicationMetaKeys::REVIEW_STATE, true ) );
		$this->assertSame( 'Admitida con dos ejes.', get_post_meta( $solicitud, ApplicationMetaKeys::REVIEW_NOTE, true ) );
	}

	/**
	 * La ventana propia: abierta con el plazo de solicitud cerrado, día de
	 * apertura y de cierre incluidos; fuera de ella no se escribe.
	 */
	public function test_the_own_window_decides_including_both_days() {
		$director = $this->school_head( 'C0001' );
		$casos    = array(
			'abre hoy'    => array( 0, 3, true ),
			'cierra hoy'  => array( -3, 0, true ),
			'abre mañana' => array( 1, 3, false ),
			'cerró ayer'  => array( -3, -1, false ),
		);
		foreach ( $casos as $caso => list( $abre, $cierra, $puede ) ) {
			$procedimiento = $this->procedimiento(
				array(
					'opens_at'  => $this->dia( $abre ),
					'closes_at' => $this->dia( $cierra ),
				)
			);
			$solicitud     = $this->application( $procedimiento, $director );
			$this->assertSame( $puede, ProcedureAccess::can_edit_people( $director, $solicitud ), $caso );
			$this->assertSame( $puede, People::add( $solicitud, $director, $this->persona() )['ok'], $caso );
			$this->assertCount( $puede ? 1 : 0, People::of( $solicitud ), $caso );
		}
	}

	/**
	 * Sin fechas propias, la ventana es la de la solicitud.
	 */
	public function test_without_dates_the_list_follows_the_application() {
		$director = $this->school_head( 'C0001' );
		$cerrado  = $this->procedimiento(
			array(
				'opens_at'  => '',
				'closes_at' => '',
			)
		);
		$this->assertFalse( ProcedureAccess::can_edit_people( $director, $this->application( $cerrado, $director ) ) );

		$abierto = $this->procedimiento(
			array(
				'opens_at'  => '',
				'closes_at' => '',
			),
			array(
				ProcedureMetaKeys::OPENS_AT  => $this->dia( -1 ),
				ProcedureMetaKeys::CLOSES_AT => $this->dia( 1 ),
			)
		);
		$this->assertTrue( ProcedureAccess::can_edit_people( $director, $this->application( $abierto, $director ) ) );
	}

	/**
	 * Con «solo admitidas», una solicitud presentada no puede; admitida, sí.
	 * Una excluida nunca, y un procedimiento histórico tampoco.
	 */
	public function test_admitted_only_excluded_and_archived() {
		$director      = $this->school_head( 'C0001' );
		$procedimiento = $this->procedimiento( array( 'admitted_only' => true ) );
		$presentada    = $this->application( $procedimiento, $director );
		$this->assertFalse( ProcedureAccess::can_edit_people( $director, $presentada ) );
		$this->assertStringContainsString( 'admitida', People::add( $presentada, $director, $this->persona() )['error'] );
		update_post_meta( $presentada, ApplicationMetaKeys::REVIEW_STATE, ApplicationMetaKeys::REVIEW_ADMITTED );
		$this->assertTrue( ProcedureAccess::can_edit_people( $director, $presentada ) );

		$otro     = $this->procedimiento();
		$excluida = $this->application( $otro, $this->school_head( 'C0002' ), 'C0002', array( ApplicationMetaKeys::REVIEW_STATE => ApplicationMetaKeys::REVIEW_EXCLUDED ) );
		$this->assertFalse( ProcedureAccess::can_edit_people( (int) get_post_field( 'post_author', $excluida ), $excluida ) );

		update_post_meta( $procedimiento, ProcedureMetaKeys::ARCHIVED, true );
		$this->assertFalse( ProcedureAccess::can_edit_people( $director, $presentada ) );
	}

	/**
	 * Solo el equipo directivo del mismo centro: ni otro centro, ni quien
	 * gestiona otro ámbito, ni siquiera quien revisa el propio ámbito, y
	 * tampoco por la capacidad de WordPress.
	 */
	public function test_only_the_same_school_manages_its_people() {
		$area          = $this->area( 'Ámbito propio' );
		$procedimiento = $this->procedure(
			$this->administrator(),
			array( $area ),
			array(
				ProcedureMetaKeys::PEOPLE_SPEC => $this->spec(),
				ProcedureMetaKeys::OPENS_AT    => $this->dia( -30 ),
				ProcedureMetaKeys::CLOSES_AT   => $this->dia( -10 ),
			)
		);
		$director      = $this->school_head( 'C0001' );
		$colega        = $this->school_head( 'C0001' );
		$solicitud     = $this->application( $procedimiento, $director );

		$this->assertTrue( ProcedureAccess::can_edit_people( $colega, $solicitud ), 'otra cuenta del mismo centro, sí' );
		$this->assertTrue( user_can( $colega, ProcedureAccess::CAP_EDIT_PEOPLE, $solicitud ), 'y por map_meta_cap' );

		$otros = array(
			'otro centro'           => $this->school_head( 'C0002' ),
			'gestor de otro ámbito' => $this->manager( array( $this->area( 'Otro ámbito' ) ) ),
			'gestor del ámbito'     => $this->manager( array( $area ) ),
			'administración'        => $this->administrator(),
			'sin sesión'            => 0,
		);
		foreach ( $otros as $caso => $uid ) {
			$this->assertFalse( ProcedureAccess::can_edit_people( $uid, $solicitud ), $caso );
			$this->assertFalse( user_can( $uid, ProcedureAccess::CAP_EDIT_PEOPLE, $solicitud ), $caso . ', por map_meta_cap' );
			$this->assertFalse( People::add( $solicitud, $uid, $this->persona() )['ok'], $caso );
		}
		$this->assertSame( array(), People::of( $solicitud ) );
	}

	/**
	 * Las solicitudes individuales de profesorado no tienen lista de personas.
	 */
	public function test_teacher_applications_have_no_list() {
		$procedimiento = $this->procedimiento( array(), array( ProcedureMetaKeys::AUDIENCE => ProcedureMetaKeys::AUDIENCE_TEACHERS ) );
		$docente       = (int) self::factory()->user->create( array( 'role' => 'prc_teacher' ) );
		$solicitud     = $this->application(
			$procedimiento,
			$docente,
			'',
			array( ApplicationMetaKeys::APPLICANT_TYPE => ApplicationMetaKeys::TYPE_TEACHER )
		);
		$this->assertFalse( ProcedureAccess::can_edit_people( $docente, $solicitud ) );
		$this->assertFalse( People::enabled( $procedimiento ) );
	}

	/**
	 * Concurrencia: un cambio sobre una fila que otra cuenta cambió o quitó
	 * desde que se pintó el formulario se rechaza y no pisa nada; dos altas
	 * seguidas de dos cuentas quedan las dos.
	 */
	public function test_two_accounts_do_not_overwrite_each_other() {
		$procedimiento = $this->procedimiento();
		$una           = $this->school_head( 'C0001' );
		$otra          = $this->school_head( 'C0001' );
		$solicitud     = $this->application( $procedimiento, $una );

		People::add( $solicitud, $una, $this->persona() );
		$this->assertTrue( People::add( $solicitud, $otra, $this->persona( array( 'group' => 'g2' ) ) )['ok'] );
		$this->assertCount( 2, People::of( $solicitud ), 'las dos altas quedan' );

		$pintada = People::of( $solicitud )[0];
		$huella  = ApplicationPeople::fingerprint( $pintada );
		// La otra cuenta la cambia mientras tanto.
		$this->assertTrue( People::change( $solicitud, $otra, $pintada['id'], $huella, $this->persona( array( 'phone' => '611 111 111' ) ) )['ok'] );

		$tarde = People::change( $solicitud, $una, $pintada['id'], $huella, $this->persona( array( 'name' => 'Ana María' ) ) );
		$this->assertFalse( $tarde['ok'] );
		$this->assertStringContainsString( 'ha cambiado', $tarde['error'] );
		$this->assertSame( '611 111 111', People::of( $solicitud )[0]['phone'], 'lo de la otra cuenta se queda' );
		$this->assertSame( 'Ana', People::of( $solicitud )[0]['name'] );

		$baja_tarde = People::remove( $solicitud, $una, $pintada['id'], $huella );
		$this->assertFalse( $baja_tarde['ok'], 'tampoco se da de baja lo que cambió otra cuenta' );

		$actual = People::of( $solicitud )[0];
		$this->assertTrue( People::remove( $solicitud, $otra, $actual['id'], ApplicationPeople::fingerprint( $actual ) )['ok'] );
		$ya_no = People::change( $solicitud, $una, $actual['id'], ApplicationPeople::fingerprint( $actual ), $this->persona() );
		$this->assertFalse( $ya_no['ok'] );
		$this->assertStringContainsString( 'ya no está', $ya_no['error'] );
		$this->assertCount( 1, People::of( $solicitud ) );
	}

	/**
	 * Lo que la validación rechaza no se guarda y se explica.
	 */
	public function test_what_the_domain_refuses_is_not_stored() {
		$procedimiento = $this->procedimiento( array( 'max' => 1 ) );
		$director      = $this->school_head( 'C0001' );
		$solicitud     = $this->application( $procedimiento, $director );

		$sin_grupo = People::add( $solicitud, $director, $this->persona( array( 'group' => '' ) ) );
		$this->assertFalse( $sin_grupo['ok'] );
		$this->assertArrayHasKey( 'group', $sin_grupo['errors'] );

		$this->assertTrue( People::add( $solicitud, $director, $this->persona() )['ok'] );
		$tope = People::add( $solicitud, $director, $this->persona( array( 'group' => 'g2' ) ) );
		$this->assertArrayHasKey( 'max', $tope['errors'] );
		$this->assertCount( 1, People::of( $solicitud ) );
		$this->assertCount( 1, ApplicationLog::all( $solicitud ), 'lo rechazado no deja historial' );
	}

	/**
	 * Un procedimiento sin especificación: ni lista, ni historial, ni nada
	 * distinto al guardar la solicitud.
	 */
	public function test_a_procedure_without_spec_is_untouched() {
		$procedimiento = $this->procedimiento(
			false,
			array(
				ProcedureMetaKeys::OPENS_AT  => $this->dia( -1 ),
				ProcedureMetaKeys::CLOSES_AT => $this->dia( 1 ),
			)
		);
		$director      = $this->school_head( 'C0001' );
		$this->assertFalse( People::enabled( $procedimiento ) );
		$id = Applications::save( $procedimiento, $director, array( 'position' => ApplicationMetaKeys::POSITION_HEAD ) );
		$this->assertGreaterThan( 0, $id );
		$this->assertFalse( ProcedureAccess::can_edit_people( $director, $id ) );
		$this->assertFalse( People::add( $id, $director, $this->persona() )['ok'] );
		$this->assertSame( array(), get_post_meta( $id, ApplicationMetaKeys::PEOPLE, true ) );
		$this->assertSame( array(), ApplicationLog::all( $id ) );
	}

	/**
	 * Renombrar un papel o un grupo con personas dadas de alta las deja con
	 * el rótulo nuevo, porque la clave no cambia.
	 */
	public function test_renaming_a_role_or_group_follows_the_people() {
		$procedimiento = $this->procedimiento();
		$director      = $this->school_head( 'C0001' );
		$solicitud     = $this->application( $procedimiento, $director );
		People::add( $solicitud, $director, $this->persona() );

		$spec                       = $this->spec();
		$spec['roles'][1]['label']  = 'Docente participante';
		$spec['groups'][0]['label'] = 'Eje uno';
		update_post_meta( $procedimiento, ProcedureMetaKeys::PEOPLE_SPEC, $spec );

		$texto = ApplicationPeople::describe( People::spec( $procedimiento ), People::of( $solicitud )[0] );
		$this->assertStringContainsString( 'Docente participante', $texto );
		$this->assertStringContainsString( 'Eje uno', $texto );
		$this->assertSame(
			array(
				'roles'  => array( 'r2' => true ),
				'groups' => array( 'g1' => true ),
			),
			People::used_keys( $procedimiento )
		);
	}
}
