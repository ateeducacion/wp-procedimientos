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
	 * Huella: un cambio sobre una fila que otra cuenta cambió o quitó desde
	 * que se pintó el formulario se rechaza y no pisa nada; dos altas
	 * seguidas de dos cuentas quedan las dos. Es secuencial: la carrera de
	 * verdad, con una escritura entre la lectura y la escritura, está en
	 * {@see test_a_write_in_between_is_not_lost()}.
	 */
	public function test_a_change_on_a_row_changed_since_it_was_painted_is_refused() {
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

	/**
	 * Hace que otra cuenta escriba la lista entre la lectura y la escritura:
	 * en las `$veces` primeras escrituras de `prc_people`, mete antes, por
	 * debajo de WordPress, la lista que se le da.
	 *
	 * @param int                               $solicitud Application ID.
	 * @param array<int, array<string, string>> $lista     What the other account writes.
	 * @param int                               $veces     How many writes to get in front of.
	 * @return void
	 */
	private function otra_escribe_entremedias( int $solicitud, array $lista, int $veces ): void {
		$hechas  = 0;
		$colarse = static function ( $corto, $object_id ) use ( $solicitud, $lista, $veces, &$hechas ) {
			global $wpdb;
			if ( (int) $object_id !== $solicitud || $hechas >= $veces ) {
				return $corto;
			}
			++$hechas;
			// Cada vez otra cosa: la otra cuenta sigue escribiendo.
			$suya                                = $lista;
			$suya[ count( $suya ) - 1 ]['phone'] = '60000000' . $hechas;
			$valor                               = maybe_serialize( $suya );
			if ( null === get_metadata_raw( 'post', $solicitud, ApplicationMetaKeys::PEOPLE, true ) ) {
				$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- simula otra petición.
					$wpdb->postmeta,
					array(
						'post_id'    => $solicitud,
						'meta_key'   => ApplicationMetaKeys::PEOPLE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
						'meta_value' => $valor, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					)
				);
			} else {
				$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- simula otra petición.
					$wpdb->postmeta,
					array( 'meta_value' => $valor ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					array(
						'post_id'  => $solicitud,
						'meta_key' => ApplicationMetaKeys::PEOPLE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					)
				);
			}
			return $corto;
		};
		add_filter( 'update_post_metadata', $colarse, 10, 2 );
		add_filter( 'add_post_metadata', $colarse, 10, 2 );
	}

	/**
	 * Carrera: otra cuenta escribe la lista entre la lectura y la escritura.
	 * La escritura no casa, se relee, se revalida y se reintenta: quedan las
	 * dos personas. Vale para la primera escritura, sin meta todavía, y para
	 * las siguientes.
	 */
	public function test_a_write_in_between_is_not_lost() {
		$procedimiento = $this->procedimiento();
		$director      = $this->school_head( 'C0001' );
		$otra          = ApplicationPeople::row(
			array( 'id' => 'otra00000001' ) + $this->persona(
				array(
					'name'  => 'Luis',
					'dni'   => '11111111H',
					'email' => 'luis@example.org',
				)
			)
		);

		$primera = $this->application( $procedimiento, $director );
		$this->otra_escribe_entremedias( $primera, array( $otra ), 1 );
		$this->assertTrue( People::add( $primera, $director, $this->persona() )['ok'] );
		$this->assertSame( array( 'Luis', 'Ana' ), array_column( People::of( $primera ), 'name' ), 'sin meta: ninguna se pierde' );

		remove_all_filters( 'update_post_metadata' );
		remove_all_filters( 'add_post_metadata' );
		$segunda = $this->application( $procedimiento, $this->school_head( 'C0002' ), 'C0002' );
		$uid     = (int) get_post_field( 'post_author', $segunda );
		$this->assertTrue( People::add( $segunda, $uid, $this->persona( array( 'group' => 'g2' ) ) )['ok'] );
		$ya = People::of( $segunda );
		$this->otra_escribe_entremedias( $segunda, array_merge( $ya, array( $otra ) ), 1 );
		$this->assertTrue( People::add( $segunda, $uid, $this->persona() )['ok'] );
		$this->assertSame( array( 'Ana', 'Luis', 'Ana' ), array_column( People::of( $segunda ), 'name' ), 'con meta: ninguna se pierde' );
	}

	/**
	 * Si otra cuenta vuelve a escribir en el reintento, se rechaza con aviso,
	 * y ni se anota en el historial ni se avisa por correo.
	 */
	public function test_a_lost_race_is_refused_without_log_or_mail() {
		$procedimiento = $this->procedimiento();
		$director      = $this->school_head( 'C0001' );
		$solicitud     = $this->application( $procedimiento, $director );
		$otra          = ApplicationPeople::row(
			array( 'id' => 'otra00000001' ) + $this->persona(
				array(
					'name'  => 'Luis',
					'dni'   => '11111111H',
					'email' => 'luis@example.org',
				)
			)
		);
		$avisos        = 0;
		add_action(
			'prc_people_changed',
			static function () use ( &$avisos ) {
				++$avisos;
			}
		);
		$this->otra_escribe_entremedias( $solicitud, array( $otra ), 5 );

		$res = People::add( $solicitud, $director, $this->persona() );
		$this->assertFalse( $res['ok'] );
		$this->assertStringContainsString( 'a la vez', $res['error'] );
		$this->assertSame( array( 'Luis' ), array_column( People::of( $solicitud ), 'name' ) );
		$this->assertSame( array(), ApplicationLog::all( $solicitud ), 'sin lista escrita, sin historial' );
		$this->assertSame( 0, $avisos );
		$this->assertSame( array(), $this->enviados );
	}

	/**
	 * Un «cambio» que deja la fila igual no escribe, no anota y no avisa.
	 */
	public function test_a_change_without_differences_does_nothing() {
		$procedimiento = $this->procedimiento();
		$director      = $this->school_head( 'C0001' );
		$solicitud     = $this->application( $procedimiento, $director );
		People::add( $solicitud, $director, $this->persona() );
		$this->enviados = array();
		$fila           = People::of( $solicitud )[0];

		$res = People::change( $solicitud, $director, $fila['id'], ApplicationPeople::fingerprint( $fila ), $this->persona() );
		$this->assertTrue( $res['ok'] );
		$this->assertFalse( $res['changed'] );
		$this->assertCount( 1, ApplicationLog::all( $solicitud ) );
		$this->assertSame( array(), $this->enviados );
	}

	/**
	 * Tocar personas no es guardar la solicitud: ni `prc_application_saved`,
	 * ni cambia su fecha de modificación.
	 */
	public function test_people_do_not_save_the_application() {
		$procedimiento = $this->procedimiento();
		$director      = $this->school_head( 'C0001' );
		$solicitud     = $this->application( $procedimiento, $director );
		$antes         = get_post_field( 'post_modified_gmt', $solicitud );
		$guardadas     = 0;
		add_action(
			'prc_application_saved',
			static function () use ( &$guardadas ) {
				++$guardadas;
			}
		);
		People::add( $solicitud, $director, $this->persona() );
		$fila = People::of( $solicitud )[0];
		People::change( $solicitud, $director, $fila['id'], ApplicationPeople::fingerprint( $fila ), $this->persona( array( 'phone' => '611111111' ) ) );
		clean_post_cache( $solicitud );
		$this->assertSame( 0, $guardadas );
		$this->assertSame( $antes, get_post_field( 'post_modified_gmt', $solicitud ) );
	}
}
