<?php
/**
 * Tests for the read-only consultation scoped by zone: who observes what,
 * decided by ProcedureAccess and closed in map_meta_cap.
 *
 * @package Prc
 */

use Prc\Access\ObserverScope;
use Prc\Access\ProcedureAccess;
use Prc\Domain\CentreCatalog;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;

/**
 * El tercer acotado: la zona de quien consulta (ADR-0043).
 *
 * Se prueban las dos capas, como en el resto del guardián: la respuesta de
 * `ProcedureAccess` y la que de verdad cierra la puerta,
 * `user_can( …, 'read_post', … )`.
 */
class Test_Observer_Access extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Un catálogo inventado: tres centros en dos zonas, y las dos zonas.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
		remove_all_filters( 'prc_centres' );
		remove_all_filters( 'prc_zone_meta_key' );
		add_filter( 'prc_centres', array( $this, 'catalogue' ) );
		CentreCatalog::flush();
	}

	/**
	 * The made-up catalogue.
	 *
	 * @return array<int, array<string, string>>
	 */
	public function catalogue(): array {
		return array(
			array(
				'code' => 'C0001',
				'name' => 'CEIP Uno',
				'zone' => 'Z0001',
			),
			array(
				'code' => 'C0002',
				'name' => 'IES Dos',
				'zone' => 'Z0002',
			),
			array(
				'code' => 'C0003',
				'name' => 'CEO Tres',
				'zone' => 'Z0002',
			),
			array(
				'code' => 'Z0001',
				'name' => 'Zona Uno',
			),
			array(
				'code' => 'Z0002',
				'name' => 'Zona Dos',
			),
		);
	}

	/**
	 * Un procedimiento observable de un ámbito, con una solicitud en cada centro.
	 *
	 * @param array<string, mixed> $meta Procedure meta on top.
	 * @return array{area:int, procedure:int, c1:int, c2:int, c3:int}
	 */
	private function caso( array $meta = array() ): array {
		$area = $this->area( 'Ámbito convocante' );
		$pid  = $this->procedure( $this->administrator(), array( $area ), array_merge( array( ProcedureMetaKeys::OBSERVABLE => true ), $meta ) );
		return array(
			'area'      => $area,
			'procedure' => $pid,
			'c1'        => $this->application( $pid, $this->school_head( 'C0001' ) ),
			'c2'        => $this->application( $pid, $this->school_head( 'C0002' ) ),
			'c3'        => $this->application( $pid, $this->school_head( 'C0003' ) ),
		);
	}

	/**
	 * What the person sees of the consultation of a procedure.
	 *
	 * @param int $uid          User ID.
	 * @param int $procedure_id Procedure ID.
	 * @return array<string, string[]>|null
	 */
	private function scope( int $uid, int $procedure_id ): ?array {
		$ambito = null;
		ProcedureAccess::can_observe( $uid, $procedure_id, $ambito );
		return $ambito;
	}

	// ─── la zona de la persona ─────────────────────────────────────────────

	/**
	 * La zona se lee como lista o como texto; lo que no tiene forma de código se cae.
	 */
	public function test_zones_are_parsed_from_a_list_or_a_text() {
		$this->assertSame( array( 'Z0001', 'Z0002' ), ObserverScope::parse_zones( array( 'Z0001', ' Z0002 ', 'Z0001', '' ) ) );
		$this->assertSame( array( 'Z0001', 'Z0002' ), ObserverScope::parse_zones( 'Z0001, Z0002;Z0001' ) );
		$this->assertSame( array( 'Z0001' ), ObserverScope::parse_zones( array( 'Z0001', 'no vale', array( 'Z0002' ), '<b>' ) ) );
		$this->assertSame( array(), ObserverScope::parse_zones( null ) );
		$this->assertSame( array(), ObserverScope::parse_zones( 42.5 ) );
	}

	/**
	 * Los centros de unas zonas son los del catálogo cuya zona es una de ellas.
	 */
	public function test_codes_in_the_zones_come_from_the_catalogue() {
		$indice = CentreCatalog::index();
		$this->assertSame( array( 'C0002', 'C0003' ), ObserverScope::codes_in( array( 'Z0002' ), $indice ) );
		$this->assertSame( array( 'C0001', 'C0002', 'C0003' ), ObserverScope::codes_in( array( 'Z0001', 'Z0002' ), $indice ) );
		$this->assertSame( array(), ObserverScope::codes_in( array( 'Z9999' ), $indice ) );
		$this->assertSame( array(), ObserverScope::codes_in( array(), $indice ) );
	}

	/**
	 * La clave de la meta la devuelve un filtro, como la del código de centro.
	 */
	public function test_the_zone_meta_key_is_configurable() {
		$this->assertSame( 'prc_zone', ObserverScope::meta_key() );
		$uid = $this->observer();
		update_user_meta( $uid, 'meta_de_la_instalacion', 'Z0001' );
		$this->assertSame( array(), ProcedureAccess::observer_zones( $uid ) );

		add_filter(
			'prc_zone_meta_key',
			static function (): string {
				return 'meta_de_la_instalacion';
			}
		);
		$this->assertSame( array( 'Z0001' ), ProcedureAccess::observer_zones( $uid ) );
		$this->assertSame( array( 'C0001' ), ProcedureAccess::observer_codes( $uid ) );
	}

	// ─── quién consulta qué ────────────────────────────────────────────────

	/**
	 * Sin zona no se ve nada: falla en cerrado.
	 */
	public function test_without_a_zone_nothing_is_observed() {
		$caso = $this->caso();
		$uid  = $this->observer();

		$this->assertSame( array(), ProcedureAccess::observer_codes( $uid ) );
		$this->assertFalse( ProcedureAccess::can_observe( $uid ) );
		$this->assertFalse( ProcedureAccess::can_observe( $uid, $caso['procedure'] ) );
		$this->assertNull( $this->scope( $uid, $caso['procedure'] ) );
		foreach ( array( 'c1', 'c2', 'c3' ) as $clave ) {
			$this->assertFalse( ProcedureAccess::can_view_application( $uid, $caso[ $clave ] ) );
			$this->assertFalse( user_can( $uid, 'read_post', $caso[ $clave ] ) );
		}
	}

	/**
	 * Una zona que no tiene ningún centro en el catálogo tampoco abre nada.
	 */
	public function test_an_unknown_zone_observes_nothing() {
		$caso = $this->caso();
		$uid  = $this->observer( array( 'Z9999' ) );

		$this->assertSame( array( 'Z9999' ), ProcedureAccess::observer_zones( $uid ) );
		$this->assertSame( array(), ProcedureAccess::observer_codes( $uid ) );
		$this->assertFalse( ProcedureAccess::can_observe( $uid, $caso['procedure'] ) );
		$this->assertFalse( user_can( $uid, 'read_post', $caso['c1'] ) );
	}

	/**
	 * Con una zona se leen las solicitudes de sus centros y ninguna más.
	 */
	public function test_one_zone_reads_its_centres_only() {
		$caso = $this->caso();
		$uid  = $this->observer( array( 'Z0002' ) );

		$this->assertTrue( ProcedureAccess::can_observe( $uid ) );
		$this->assertTrue( ProcedureAccess::can_observe( $uid, $caso['procedure'] ) );
		$this->assertSame( array( 'codes' => array( 'C0002', 'C0003' ) ), $this->scope( $uid, $caso['procedure'] ) );
		$this->assertFalse( user_can( $uid, 'read_post', $caso['c1'] ), 'otra zona: ni por enlace directo' );
		$this->assertTrue( user_can( $uid, 'read_post', $caso['c2'] ) );
		$this->assertTrue( user_can( $uid, 'read_post', $caso['c3'] ) );
		$this->assertFalse( ProcedureAccess::can_observe_application( $uid, $caso['c1'] ) );
		$this->assertTrue( ProcedureAccess::can_observe_application( $uid, $caso['c2'] ) );
	}

	/**
	 * Con dos zonas, las de las dos.
	 */
	public function test_two_zones_read_both() {
		$caso = $this->caso();
		$uid  = $this->observer( array( 'Z0001', 'Z0002' ) );

		foreach ( array( 'c1', 'c2', 'c3' ) as $clave ) {
			$this->assertTrue( user_can( $uid, 'read_post', $caso[ $clave ] ) );
		}
	}

	/**
	 * Leer no es escribir: ni editar, ni borrar, ni publicar, ni revisar.
	 */
	public function test_observing_never_edits_nor_deletes() {
		$caso = $this->caso();
		$uid  = $this->observer( array( 'Z0002' ) );

		foreach ( array( 'edit_post', 'delete_post', 'publish_post' ) as $cap ) {
			$this->assertFalse( user_can( $uid, $cap, $caso['c2'] ), $cap );
			$this->assertFalse( user_can( $uid, $cap, $caso['procedure'] ), $cap );
		}
		$this->assertFalse( ProcedureAccess::can_review( $uid, $caso['procedure'] ) );
		$this->assertFalse( ProcedureAccess::can_edit_people( $uid, $caso['c2'] ) );
		$this->assertFalse( user_can( $uid, ProcedureAccess::CAP_EDIT_PEOPLE, $caso['c2'] ) );
		// El listado de solicitudes del escritorio pide esta capacidad: no la tiene.
		$this->assertFalse( user_can( $uid, 'edit_prc_applications' ) );
		$this->assertFalse( user_can( $uid, 'edit_others_prc_applications' ) );
		$this->assertFalse( user_can( $uid, 'read_private_prc_applications' ) );
	}

	/**
	 * Y el listado de solicitudes del escritorio, si llegara a pedirse, sale vacío.
	 */
	public function test_the_admin_list_shows_an_observer_no_application() {
		$this->caso();
		$this->acting_as( $this->observer( array( 'Z0002' ) ) );
		set_current_screen( 'edit-prc_application' );
		global $wp_the_query;
		$antes        = $wp_the_query;
		$consulta     = new WP_Query();
		$wp_the_query = $consulta;
		$consulta->set( 'post_type', \Prc\PostType\ApplicationPostType::POST_TYPE );
		\Prc\Admin\ProcedureAdmin::scope_admin_query( $consulta );
		$wp_the_query = $antes;
		set_current_screen( 'front' );

		$this->assertSame( array( 0 ), $consulta->get( 'post__in' ) );
		$this->assertFalse( current_user_can( get_post_type_object( \Prc\PostType\ApplicationPostType::POST_TYPE )->cap->edit_posts ) );
	}

	/**
	 * Sin `prc_observable` el procedimiento no se consulta, aunque la zona coincida.
	 */
	public function test_a_procedure_that_is_not_observable_is_not_observed() {
		$caso = $this->caso( array( ProcedureMetaKeys::OBSERVABLE => false ) );
		$uid  = $this->observer( array( 'Z0002' ) );

		$this->assertFalse( ObserverScope::is_observable( $caso['procedure'] ) );
		$this->assertFalse( ProcedureAccess::can_observe( $uid, $caso['procedure'] ) );
		$this->assertFalse( user_can( $uid, 'read_post', $caso['c2'] ) );
	}

	/**
	 * Un borrador no se consulta: lo observable es lo publicado.
	 */
	public function test_a_draft_is_not_observed() {
		$caso = $this->caso();
		wp_update_post(
			array(
				'ID'          => $caso['procedure'],
				'post_status' => 'draft',
			)
		);
		$uid = $this->observer( array( 'Z0002' ) );

		$this->assertFalse( ProcedureAccess::can_observe( $uid, $caso['procedure'] ) );
		$this->assertFalse( user_can( $uid, 'read_post', $caso['c2'] ) );
	}

	/**
	 * La capacidad hace falta: la meta de zona sola no abre nada.
	 */
	public function test_a_zone_without_the_capability_opens_nothing() {
		$caso = $this->caso();
		$uid  = $this->observer( array( 'Z0002' ), 'subscriber' );

		$this->assertFalse( ProcedureAccess::can_observe( $uid ) );
		$this->assertFalse( ProcedureAccess::can_observe( $uid, $caso['procedure'] ) );
		$this->assertFalse( user_can( $uid, 'read_post', $caso['c2'] ) );
	}

	/**
	 * La solicitud de profesorado no tiene centro: por zona no se ve.
	 */
	public function test_a_teacher_application_is_not_observed_by_zone() {
		$caso    = $this->caso();
		$docente = $this->application(
			$caso['procedure'],
			(int) self::factory()->user->create( array( 'role' => 'prc_teacher' ) ),
			'',
			array(
				ApplicationMetaKeys::CENTRE_CODE    => '',
				ApplicationMetaKeys::APPLICANT_TYPE => ApplicationMetaKeys::TYPE_TEACHER,
			)
		);
		$uid     = $this->observer( array( 'Z0001', 'Z0002' ) );

		$this->assertFalse( ProcedureAccess::can_observe_application( $uid, $docente ) );
		$this->assertFalse( user_can( $uid, 'read_post', $docente ) );
	}

	/**
	 * Un centro que no está en el catálogo no es de ninguna zona.
	 */
	public function test_a_centre_outside_the_catalogue_is_not_observed() {
		$caso   = $this->caso();
		$fuera  = $this->application( $caso['procedure'], $this->school_head( 'C9999' ) );
		$gestor = $this->manager( array( $caso['area'] ) );
		$uid    = $this->observer( array( 'Z0001', 'Z0002' ) );

		$this->assertFalse( user_can( $uid, 'read_post', $fuera ) );
		$this->assertTrue( user_can( $gestor, 'read_post', $fuera ), 'quien gestiona lo sigue viendo' );
	}

	/**
	 * La zona del centro se lee del catálogo al preguntar, no se copia: si
	 * cambia, la zona antigua deja de verlo.
	 */
	public function test_a_centre_that_changes_zone_leaves_the_old_one() {
		$caso    = $this->caso();
		$antigua = $this->observer( array( 'Z0001' ) );
		$nueva   = $this->observer( array( 'Z0002' ) );
		$this->assertTrue( user_can( $antigua, 'read_post', $caso['c1'] ) );

		remove_all_filters( 'prc_centres' );
		add_filter(
			'prc_centres',
			function (): array {
				$centros            = $this->catalogue();
				$centros[0]['zone'] = 'Z0002';
				return $centros;
			}
		);
		CentreCatalog::flush();

		$this->assertFalse( user_can( $antigua, 'read_post', $caso['c1'] ) );
		$this->assertTrue( user_can( $nueva, 'read_post', $caso['c1'] ) );
	}

	/**
	 * Quien gestiona el ámbito ve todas las zonas; quien gestiona otro, ninguna.
	 */
	public function test_the_managing_area_sees_every_zone_and_another_none() {
		$caso   = $this->caso();
		$propio = $this->manager( array( $caso['area'] ) );
		$ajeno  = $this->manager( array( $this->area( 'Otro ámbito' ) ) );
		$admin  = $this->administrator();

		$this->assertSame( array(), $this->scope( $propio, $caso['procedure'] ) );
		$this->assertSame( array(), $this->scope( $admin, $caso['procedure'] ) );
		$this->assertNull( $this->scope( $ajeno, $caso['procedure'] ) );
		$this->assertTrue( ProcedureAccess::can_observe( $propio ) );
		$this->assertTrue( ProcedureAccess::can_observe( $ajeno ), 'tiene su consulta, de sus procedimientos' );
		foreach ( array( 'c1', 'c2', 'c3' ) as $clave ) {
			$this->assertTrue( ProcedureAccess::can_observe_application( $propio, $caso[ $clave ] ) );
			$this->assertFalse( ProcedureAccess::can_observe_application( $ajeno, $caso[ $clave ] ) );
			$this->assertFalse( user_can( $ajeno, 'read_post', $caso[ $clave ] ) );
		}
	}

	/**
	 * Quien dirige un centro y además consulta una zona no mezcla los permisos:
	 * lee las de su zona y solo escribe en lo de su centro.
	 */
	public function test_a_school_head_who_also_observes_keeps_each_permission_apart() {
		$caso = $this->caso(
			array(
				ProcedureMetaKeys::PEOPLE_SPEC => array(
					'enabled' => true,
					'roles'   => array(
						array(
							'key'   => 'r1',
							'label' => 'Docente',
						),
					),
				),
			)
		);
		$uid  = $this->school_head( 'C0001' );
		get_userdata( $uid )->add_role( 'prc_observer' );
		update_user_meta( $uid, ObserverScope::meta_key(), array( 'Z0002' ) );
		$suya = $this->application( $this->procedure( $this->administrator(), array( $caso['area'] ) ), $uid );

		$this->assertTrue( user_can( $uid, 'read_post', $caso['c1'] ), 'la de su centro' );
		$this->assertTrue( user_can( $uid, 'read_post', $caso['c2'] ), 'la de su zona' );
		$this->assertFalse( ProcedureAccess::can_edit_people( $uid, $caso['c2'] ), 'la de su zona no la escribe' );
		$this->assertFalse( user_can( $uid, ProcedureAccess::CAP_EDIT_PEOPLE, $caso['c2'] ) );
		$this->assertFalse( ProcedureAccess::can_deliver( $uid, $caso['c2'], array( 'key' => 'q1' ) ) );
		$this->assertFalse( user_can( $uid, 'edit_post', $caso['c2'] ) );
		$this->assertFalse( ProcedureAccess::can_view_application( $uid, $caso['c2'], true ), 'sin documentos permitidos, ni los de su zona' );
		$this->assertTrue( ProcedureAccess::can_view_application( $uid, $suya, true ), 'los de su centro, siempre' );
		$this->assertArrayNotHasKey( 'C0002', \Prc\Access\CentreScope::codes_for( $uid ) );
	}

	/**
	 * La meta de columnas no guarda nunca el DNI ni la nota, y el cálculo de
	 * columnas tampoco los devuelve aunque lleguen escritos.
	 */
	public function test_the_columns_never_carry_the_dni_nor_the_note() {
		$caso = $this->caso();
		update_post_meta( $caso['procedure'], ProcedureMetaKeys::OBSERVER_COLUMNS, array( 'code', 'coordinator_dni', 'person_dni', 'note', 'q4:r1', '<b>' ) );
		$this->assertSame( array( 'code', 'q4:r1' ), get_post_meta( $caso['procedure'], ProcedureMetaKeys::OBSERVER_COLUMNS, true ) );

		$grupos = array(
			'centre'       => array(
				'centre' => 'Centro',
				'code'   => 'Código',
			),
			'coordination' => array(
				'coordinator'     => 'Coordinación',
				'coordinator_dni' => 'DNI',
			),
			'review'       => array( 'note' => 'Nota' ),
		);
		$this->assertSame( array( 'code' => 'Código' ), ObserverScope::columns( array( 'code', 'coordinator_dni', 'note' ), $grupos ) );
		$this->assertSame(
			array(
				'centre'      => 'Centro',
				'code'        => 'Código',
				'coordinator' => 'Coordinación',
			),
			ObserverScope::columns( array(), $grupos ),
			'sin elegir, las de por defecto y sin DNI'
		);
		$this->assertArrayNotHasKey( 'review', ObserverScope::allowed( $grupos ), 'un grupo que se queda vacío no se ofrece' );
	}

	// ─── documentos y exportación ──────────────────────────────────────────

	/**
	 * Los documentos de su zona se descargan solo si el procedimiento lo permite.
	 */
	public function test_documents_follow_the_procedure() {
		$sin = $this->caso();
		$uid = $this->observer( array( 'Z0002' ) );
		$this->assertTrue( user_can( $uid, 'read_post', $sin['c2'] ) );
		$this->assertFalse( ProcedureAccess::can_view_application( $uid, $sin['c2'], true ) );

		update_post_meta( $sin['procedure'], ProcedureMetaKeys::OBSERVER_FILES, true );
		$this->assertTrue( ProcedureAccess::can_view_application( $uid, $sin['c2'], true ) );
		$this->assertFalse( ProcedureAccess::can_view_application( $uid, $sin['c1'], true ), 'ni con documentos permitidos, los de otra zona' );
	}

	/**
	 * Exportar es otra casilla del procedimiento.
	 */
	public function test_export_follows_the_procedure() {
		$caso = $this->caso();
		$uid  = $this->observer( array( 'Z0002' ) );
		$this->assertNull( \Prc\PublicFront\Observe::csv( $uid, $caso['procedure'], array() ) );

		update_post_meta( $caso['procedure'], ProcedureMetaKeys::OBSERVER_EXPORT, true );
		$this->assertNotNull( \Prc\PublicFront\Observe::csv( $uid, $caso['procedure'], array() ) );
		$this->assertNull( \Prc\PublicFront\Observe::csv( $this->observer(), $caso['procedure'], array() ), 'sin zona, no' );
	}

	/**
	 * A quién se copia el aviso de personas: las cuentas que consultan la zona del centro.
	 */
	public function test_zone_observers_are_the_accounts_of_the_zone_of_the_centre() {
		$dos   = $this->observer( array( 'Z0002' ) );
		$ambas = $this->observer( array( 'Z0001', 'Z0002' ) );
		$uno   = $this->observer( array( 'Z0001' ) );
		$this->observer( array( 'Z0002' ), 'subscriber' );
		$this->observer();

		$quienes = ObserverScope::observers_of( 'C0002' );
		sort( $quienes );
		$this->assertSame( array( $dos, $ambas ), $quienes );
		$this->assertSame( array( $ambas, $uno ), ObserverScope::observers_of( 'C0001' ) );
		$this->assertSame( array(), ObserverScope::observers_of( 'C9999' ), 'fuera del catálogo, nadie' );
		$this->assertSame( array(), ObserverScope::observers_of( '' ) );
	}
}
