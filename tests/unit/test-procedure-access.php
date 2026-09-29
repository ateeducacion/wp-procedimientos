<?php
/**
 * Tests for ProcedureAccess: who does what, scoped by ámbito and by school.
 *
 * @package Prc
 */

use Prc\Access\ProcedureAccess;
use Prc\Domain\ProcedureState;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ApplicationPostType;
use Prc\PostType\ProcedurePostType;
use Prc\Taxonomy\ProcedureTaxonomies;

/**
 * El único guardián: el acotado por ámbito y el acotado por centro.
 *
 * Se prueban las dos capas a la vez: la respuesta de `ProcedureAccess` y la
 * que de verdad cierra la puerta, `user_can( …, 'edit_post', … )`, porque lo
 * que protege la edición rápida y la REST es la segunda.
 */
class Test_Procedure_Access extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Tipos, taxonomías y roles registrados; sin catálogo de centros.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
		remove_all_filters( 'prc_centres' );
	}

	// ─── por ámbito ────────────────────────────────────────────────────────

	/**
	 * Sin ámbito en el perfil no se edita nada: falla en cerrado.
	 */
	public function test_without_an_area_nobody_edits() {
		$area          = $this->area( 'Área de prueba' );
		$procedimiento = $this->procedure( $this->administrator(), array( $area ) );
		$huerfano      = $this->manager();

		$this->assertSame( array(), ProcedureAccess::user_areas( $huerfano ) );
		$this->assertFalse( ProcedureAccess::can_manage_procedures( $huerfano ) );
		$this->assertFalse( ProcedureAccess::can_edit( $huerfano, $procedimiento ) );
		$this->assertFalse( user_can( $huerfano, 'edit_post', $procedimiento ) );
		$this->assertStringContainsString( 'No tiene ningún ámbito asignado', ProcedureAccess::why_not_editable( $huerfano, $procedimiento ) );
	}

	/**
	 * Con su ámbito edita lo suyo, aunque lo escribiera otra persona, y no lo ajeno.
	 */
	public function test_the_area_scopes_what_is_editable() {
		$mia  = $this->area( 'Área de prueba' );
		$otra = $this->area( 'Otro ámbito' );
		$yo   = $this->manager( array( $mia ) );
		$ella = $this->manager( array( $otra ) );

		// Autoría ajena a propósito: lo que decide es el ámbito, no la autoría.
		$mio   = $this->procedure( $ella, array( $mia ) );
		$ajeno = $this->procedure( $ella, array( $otra ) );

		$this->assertTrue( ProcedureAccess::can_manage_procedures( $yo ) );
		$this->assertTrue( ProcedureAccess::can_edit( $yo, $mio ) );
		$this->assertTrue( user_can( $yo, 'edit_post', $mio ) );
		$this->assertSame( '', ProcedureAccess::why_not_editable( $yo, $mio ) );

		$this->assertFalse( ProcedureAccess::can_edit( $yo, $ajeno ) );
		$this->assertFalse( user_can( $yo, 'edit_post', $ajeno ) );
		$this->assertFalse( user_can( $yo, 'delete_post', $ajeno ) );
		$this->assertStringContainsString( 'de otro ámbito', ProcedureAccess::why_not_editable( $yo, $ajeno ) );
	}

	/**
	 * Un procedimiento de varios ámbitos lo gestiona cualquiera de ellos.
	 *
	 * El cruce es una intersección (ADR-0025): basta pertenecer a uno de los
	 * ámbitos convocantes, y quien no está en ninguno sigue fuera. Es la
	 * consecuencia de permisos de los ámbitos múltiples, y la que hay que
	 * mirar antes de dejar añadir un ámbito ajeno en el taller.
	 */
	public function test_one_shared_area_is_enough_to_manage_the_procedure() {
		$una      = $this->area( 'Área de prueba' );
		$otra     = $this->area( 'Otro ámbito' );
		$tercera  = $this->area( 'Un tercer ámbito' );
		$yo       = $this->manager( array( $una ) );
		$ella     = $this->manager( array( $otra ) );
		$ajena    = $this->manager( array( $tercera ) );
		$a_medias = $this->procedure( $this->administrator(), array( $una, $otra ) );

		$this->assertSame( array( $una, $otra ), ProcedureAccess::post_areas( $a_medias ) );

		foreach ( array( $yo, $ella ) as $quien ) {
			$this->assertTrue( ProcedureAccess::can_edit( $quien, $a_medias ) );
			$this->assertTrue( ProcedureAccess::can_review( $quien, $a_medias ) );
			$this->assertTrue( user_can( $quien, 'edit_post', $a_medias ) );
			$this->assertSame( '', ProcedureAccess::why_not_editable( $quien, $a_medias ) );
		}

		$this->assertFalse( ProcedureAccess::can_edit( $ajena, $a_medias ) );
		$this->assertFalse( user_can( $ajena, 'edit_post', $a_medias ) );
		$this->assertStringContainsString( 'de otro ámbito', ProcedureAccess::why_not_editable( $ajena, $a_medias ) );
	}

	/**
	 * Un término incluye a sus descendientes: quien tiene el servicio
	 * gestiona lo del área que cuelga de él, y no al revés.
	 */
	public function test_an_area_reaches_its_descendants_and_not_its_ancestors() {
		$servicio = $this->area( 'Servicio de prueba' );
		$area     = $this->area( 'Área hija', $servicio );
		$admin    = $this->administrator();
		$del_serv = $this->procedure( $admin, array( $servicio ) );
		$del_area = $this->procedure( $admin, array( $area ) );

		$jefe = $this->manager( array( $servicio ) );
		$this->assertSame( array( $servicio, $area ), ProcedureAccess::scope_areas( $jefe ) );
		$this->assertTrue( ProcedureAccess::can_edit( $jefe, $del_serv ) );
		$this->assertTrue( ProcedureAccess::can_edit( $jefe, $del_area ) );
		$this->assertTrue( user_can( $jefe, 'edit_post', $del_area ) );

		$tecnica = $this->manager( array( $area ) );
		$this->assertSame( array( $area ), ProcedureAccess::scope_areas( $tecnica ) );
		$this->assertTrue( ProcedureAccess::can_edit( $tecnica, $del_area ) );
		$this->assertFalse( ProcedureAccess::can_edit( $tecnica, $del_serv ) );
		$this->assertFalse( user_can( $tecnica, 'edit_post', $del_serv ) );
	}

	/**
	 * `prc_manage_all_areas` se salta el acotado; administración lo tiene.
	 */
	public function test_managing_every_area_skips_the_scoping() {
		$otra          = $this->area( 'Otro ámbito' );
		$procedimiento = $this->procedure( $this->manager( array( $otra ) ), array( $otra ) );
		$admin         = $this->administrator();

		$this->assertSame( array(), ProcedureAccess::user_areas( $admin ), 'la administración no necesita ámbito propio' );
		$this->assertTrue( ProcedureAccess::can_edit_all_areas( $admin ) );
		$this->assertTrue( ProcedureAccess::can_edit( $admin, $procedimiento ) );
		$this->assertTrue( user_can( $admin, 'edit_post', $procedimiento ) );

		$sin_ambito = $this->manager();
		get_user_by( 'id', $sin_ambito )->add_cap( ProcedureAccess::CAP_ALL_AREAS );
		$this->assertTrue( ProcedureAccess::can_manage_procedures( $sin_ambito ) );
		$this->assertTrue( ProcedureAccess::can_edit( $sin_ambito, $procedimiento ) );
		$this->assertFalse( ProcedureAccess::is_manager( $sin_ambito ), 'salirse del ámbito no es administrar el aplicativo' );
	}

	/**
	 * Tener el ámbito no basta sin la capacidad, y no hay persona ni procedimiento cero.
	 */
	public function test_it_fails_closed() {
		$area          = $this->area( 'Área de prueba' );
		$procedimiento = $this->procedure( $this->administrator(), array( $area ) );
		$head          = $this->school_head( 'C0001' );
		update_user_meta( $head, ProcedureAccess::USER_AREA_META, array( $area ) );

		$this->assertFalse( ProcedureAccess::can_edit( $head, $procedimiento ) );
		$this->assertFalse( ProcedureAccess::can_publish( $head, $procedimiento ) );
		$this->assertFalse( ProcedureAccess::can_review( $head, $procedimiento ) );
		$this->assertFalse( user_can( $head, 'edit_post', $procedimiento ) );
		$this->assertStringContainsString( 'Su perfil no gestiona procedimientos', ProcedureAccess::why_not_editable( $head, $procedimiento ) );

		$this->assertFalse( ProcedureAccess::can_edit( 0, $procedimiento ) );
		$this->assertFalse( ProcedureAccess::can_edit( $head, 0 ) );
	}

	/**
	 * A new procedure receives the creator's scope immediately.
	 */
	public function test_a_brand_new_procedure_belongs_to_whoever_created_it() {
		$area          = $this->area( 'Área de prueba' );
		$yo            = $this->manager( array( $area ) );
		$ajena         = $this->manager( array( $this->area( 'Otro ámbito' ) ) );
		$procedimiento = $this->procedure( $yo );

		$this->assertSame( array( $area ), ProcedureAccess::post_areas( $procedimiento ) );
		$this->assertTrue( ProcedureAccess::can_edit( $yo, $procedimiento ) );
		$this->assertFalse( ProcedureAccess::can_edit( $ajena, $procedimiento ) );
	}

	/**
	 * Publicar pide la capacidad y, si se dice cuál, también el ámbito.
	 */
	public function test_publishing_needs_the_capability_and_the_area() {
		$mia   = $this->area( 'Área de prueba' );
		$otra  = $this->area( 'Otro ámbito' );
		$yo    = $this->manager( array( $mia ) );
		$mio   = $this->procedure( $yo, array( $mia ) );
		$ajeno = $this->procedure( $yo, array( $otra ) );

		$this->assertTrue( ProcedureAccess::can_publish( $yo ) );
		$this->assertTrue( ProcedureAccess::can_publish( $yo, $mio ) );
		$this->assertFalse( ProcedureAccess::can_publish( $yo, $ajeno ) );
		$this->assertFalse( user_can( $yo, 'publish_post', $ajeno ) );
		$this->assertFalse( ProcedureAccess::can_publish( 0 ) );
	}

	/**
	 * El filtro no toca lo que no es nuestro: ahí manda el permiso de siempre.
	 */
	public function test_only_our_types_are_scoped() {
		$pagina = (int) self::factory()->post->create( array( 'post_type' => 'page' ) );
		$admin  = $this->administrator();

		$this->assertFalse( ProcedureAccess::can_edit( $admin, $pagina ), 'esto no es un procedimiento' );
		$this->assertSame(
			array( 'edit_pages' ),
			ProcedureAccess::map_meta_cap( array( 'edit_pages' ), 'edit_post', $admin, array( $pagina ) )
		);
		$this->assertSame(
			array( 'manage_options' ),
			ProcedureAccess::map_meta_cap( array( 'manage_options' ), 'manage_options', $admin, array() )
		);
	}

	/**
	 * El ámbito del perfil se lee tanto de una lista como de una cadena separada
	 * por comas, y lo que no es un identificador se cae.
	 */
	public function test_the_profile_area_is_read_from_a_list_or_a_string() {
		$uid = $this->manager();
		$this->assertSame( array(), ProcedureAccess::user_areas( $uid ) );

		$first  = $this->area( 'Primer ámbito' );
		$second = $this->area( 'Segundo ámbito' );
		update_user_meta( $uid, ProcedureAccess::USER_AREA_META, array( $first, (string) $first, $second, 0, 'x' ) );
		$this->assertSame( array(), ProcedureAccess::user_areas( $uid ), 'dos ámbitos históricos no eligen uno por su cuenta' );

		update_user_meta( $uid, ProcedureAccess::USER_AREA_META, $first . ',' . $second );
		$this->assertSame( array(), ProcedureAccess::user_areas( $uid ) );

		update_user_meta( $uid, ProcedureAccess::USER_AREA_META, array( $first ) );
		$this->assertSame( array( $first ), ProcedureAccess::user_areas( $uid ) );

		update_user_meta( $uid, ProcedureAccess::USER_AREA_META, '   ' );
		$this->assertSame( array(), ProcedureAccess::user_areas( $uid ) );
	}

	/** Native editors can edit descendants, but not parents or sibling branches. */
	public function test_editor_scope_covers_descendants_only() {
		$dg      = $this->area( 'DG' );
		$service = $this->area( 'Servicio A' );
		$area    = $this->area( 'Área A1' );
		$team    = $this->area( 'Equipo A1.1' );
		$sibling = $this->area( 'Área A2' );
		$other   = $this->area( 'Servicio B' );
		wp_update_term( $service, ProcedureTaxonomies::AREA, array( 'parent' => $dg ) );
		wp_update_term( $area, ProcedureTaxonomies::AREA, array( 'parent' => $service ) );
		wp_update_term( $team, ProcedureTaxonomies::AREA, array( 'parent' => $area ) );
		wp_update_term( $sibling, ProcedureTaxonomies::AREA, array( 'parent' => $service ) );
		wp_update_term( $other, ProcedureTaxonomies::AREA, array( 'parent' => $dg ) );
		$editor = (int) self::factory()->user->create( array( 'role' => 'editor' ) );
		update_user_meta( $editor, ProcedureAccess::USER_AREA_META, array( $service ) );
		$this->assertEqualsCanonicalizing( array( $service, $area, $team, $sibling ), ProcedureAccess::scope_areas( $editor ) );
		foreach ( array( $service, $area, $team, $sibling ) as $term ) {
			$this->assertTrue( ProcedureAccess::may_assign_areas( array( $term ), $editor ) );
			$this->assertTrue( user_can( $editor, 'assign_term', $term ) );
		}
		foreach ( array( $dg, $other ) as $term ) {
			$this->assertFalse( ProcedureAccess::may_assign_areas( array( $term ), $editor ) );
			$this->assertFalse( user_can( $editor, 'assign_term', $term ) );
		}
		$new_child = $this->area( 'Nueva área', $service );
		$this->assertContains( $new_child, ProcedureAccess::scope_areas( $editor ) );
		wp_update_term( $new_child, ProcedureTaxonomies::AREA, array( 'parent' => $other ) );
		$this->assertNotContains( $new_child, ProcedureAccess::scope_areas( $editor ) );
		foreach ( array( $service, $area, $team, $sibling ) as $term ) {
			$this->assertTrue( ProcedureAccess::can_edit( $editor, $this->procedure( $this->administrator(), array( $term ) ) ) );
		}
		foreach ( array( $dg, $other ) as $term ) {
			$foreign = $this->procedure( $this->administrator(), array( $term ) );
			$this->assertFalse( ProcedureAccess::can_edit( $editor, $foreign ) );
			$this->assertFalse( user_can( $editor, 'edit_post', $foreign ) );
		}
		update_user_meta( $editor, ProcedureAccess::USER_AREA_META, array( $area ) );
		$this->assertEqualsCanonicalizing( array( $area, $team ), ProcedureAccess::scope_areas( $editor ) );
	}

	// ─── el cierre por histórico ───────────────────────────────────────────

	/**
	 * Histórico lo marca el ámbito, lo cierra para el ámbito y solo lo reabre administración.
	 */
	public function test_the_archived_mark_closes_the_procedure_for_its_area() {
		$area          = $this->area( 'Área de prueba' );
		$yo            = $this->manager( array( $area ) );
		$admin         = $this->administrator();
		$procedimiento = $this->procedure( $yo, array( $area ) );

		$this->assertTrue( ProcedureAccess::can_archive( $yo, $procedimiento ) );
		$this->assertTrue( ProcedureAccess::can_toggle_archived( $yo, $procedimiento ) );
		$this->assertFalse( ProcedureAccess::can_unarchive( $yo ) );

		update_post_meta( $procedimiento, ProcedureMetaKeys::ARCHIVED, true );

		$this->assertTrue( ProcedureAccess::is_archived( $procedimiento ) );
		$this->assertTrue( ProcedureAccess::can_open( $yo, $procedimiento ), 'se sigue consultando desde su taller' );
		$this->assertFalse( ProcedureAccess::can_edit( $yo, $procedimiento ) );
		$this->assertFalse( user_can( $yo, 'edit_post', $procedimiento ) );
		$this->assertFalse( ProcedureAccess::can_review( $yo, $procedimiento ), 'histórico no se gestiona' );
		$this->assertFalse( ProcedureAccess::can_toggle_archived( $yo, $procedimiento ) );
		$this->assertStringContainsString( 'marcado como histórico', ProcedureAccess::why_not_editable( $yo, $procedimiento ) );

		$this->assertTrue( ProcedureAccess::can_edit( $admin, $procedimiento ) );
		$this->assertTrue( ProcedureAccess::can_review( $admin, $procedimiento ) );
		$this->assertTrue( ProcedureAccess::can_toggle_archived( $admin, $procedimiento ) );
	}

	// ─── el cierre por estado ──────────────────────────────────────────────

	/**
	 * El estado cierra grupos por encima del permiso: cerrado el plazo quedan
	 * las fechas y los enlaces, en subsanación no queda nada y resuelto solo
	 * deja los enlaces. Gestionar solicitudes no se toca.
	 */
	public function test_the_state_closes_groups_on_top_of_the_permission() {
		$area = $this->area( 'Área de prueba' );
		$yo   = $this->manager( array( $area ) );
		$ella = $this->manager( array( $this->area( 'Otro ámbito' ) ) );

		$plazo    = array(
			ProcedureMetaKeys::OPENS_AT  => gmdate( 'Y-m-d', strtotime( '-20 days' ) ),
			ProcedureMetaKeys::CLOSES_AT => gmdate( 'Y-m-d', strtotime( '-10 days' ) ),
		);
		$abierto  = $this->procedure(
			$yo,
			array( $area ),
			array(
				ProcedureMetaKeys::OPENS_AT  => gmdate( 'Y-m-d', strtotime( '-5 days' ) ),
				ProcedureMetaKeys::CLOSES_AT => gmdate( 'Y-m-d', strtotime( '+5 days' ) ),
			)
		);
		$cerrado  = $this->procedure( $yo, array( $area ), $plazo );
		$subsana  = $this->procedure(
			$yo,
			array( $area ),
			array_merge(
				$plazo,
				array(
					ProcedureMetaKeys::AMEND_OPENS_AT  => gmdate( 'Y-m-d', strtotime( '-2 days' ) ),
					ProcedureMetaKeys::AMEND_CLOSES_AT => gmdate( 'Y-m-d', strtotime( '+2 days' ) ),
				)
			)
		);
		$resuelto = $this->procedure( $yo, array( $area ), array_merge( $plazo, array( ProcedureMetaKeys::FINAL_LIST_URL => 'https://example.org/definitivo.pdf' ) ) );

		$esperado = array(
			$abierto  => array( 'data', 'dates', 'links', 'questions' ),
			$cerrado  => array( 'dates', 'links' ),
			$subsana  => array(),
			$resuelto => array( 'links' ),
		);
		foreach ( $esperado as $procedimiento => $abiertos ) {
			foreach ( ProcedureState::groups() as $grupo ) {
				$aviso = ProcedureState::of_post( $procedimiento ) . ' / ' . $grupo;
				$this->assertSame( in_array( $grupo, $abiertos, true ), ProcedureAccess::can_edit_group( $yo, $procedimiento, $grupo ), $aviso );
				$this->assertFalse( ProcedureAccess::can_edit_group( $ella, $procedimiento, $grupo ), 'otro ámbito no edita nada: ' . $aviso );
			}
			$this->assertTrue( ProcedureAccess::can_edit( $yo, $procedimiento ), 'el procedimiento se sigue abriendo y editando en parte' );
			$this->assertTrue( ProcedureAccess::can_review( $yo, $procedimiento ), 'las solicitudes se gestionan en todos ellos' );
		}

		// Y el motivo se cuenta por grupo, no por procedimiento.
		$this->assertSame( '', ProcedureAccess::why_not_editable( $yo, $cerrado ) );
		$this->assertSame( '', ProcedureAccess::why_not_editable( $yo, $cerrado, ProcedureState::GROUP_DATES ) );
		$this->assertStringContainsString( 'plazo de solicitud está cerrado', ProcedureAccess::why_not_editable( $yo, $cerrado, ProcedureState::GROUP_QUESTIONS ) );
		$this->assertStringContainsString( 'subsanación', ProcedureAccess::why_not_editable( $yo, $subsana, ProcedureState::GROUP_DATES ) );
		$this->assertStringContainsString( 'resuelto', ProcedureAccess::why_not_editable( $yo, $resuelto, ProcedureState::GROUP_DATA ) );
		$this->assertStringContainsString( 'de otro ámbito', ProcedureAccess::why_not_editable( $ella, $cerrado, ProcedureState::GROUP_DATES ), 'primero el permiso, luego el estado' );
	}

	/**
	 * Histórico no lo decide la tabla de estados, sino `can_edit()`: su ámbito
	 * no toca nada y administración lo toca todo, que es quien va a reabrirlo.
	 */
	public function test_the_archived_state_is_decided_by_can_edit_and_not_by_the_table() {
		$area          = $this->area( 'Área de prueba' );
		$yo            = $this->manager( array( $area ) );
		$admin         = $this->administrator();
		$procedimiento = $this->procedure( $yo, array( $area ), array( ProcedureMetaKeys::ARCHIVED => true ) );

		$this->assertSame( ProcedureMetaKeys::STATE_ARCHIVED, ProcedureState::of_post( $procedimiento ) );
		foreach ( ProcedureState::groups() as $grupo ) {
			$this->assertFalse( ProcedureAccess::can_edit_group( $yo, $procedimiento, $grupo ), $grupo );
			$this->assertTrue( ProcedureAccess::can_edit_group( $admin, $procedimiento, $grupo ), $grupo );
		}
		$this->assertStringContainsString( 'marcado como histórico', ProcedureAccess::why_not_editable( $yo, $procedimiento, ProcedureState::GROUP_LINKS ) );
	}

	// ─── revisar solicitudes ───────────────────────────────────────────────

	/**
	 * Revisa quien gestiona el ámbito del procedimiento, y desde la solicitud
	 * se llega al mismo sitio.
	 */
	public function test_reviewing_follows_the_area_of_the_procedure() {
		$mia           = $this->area( 'Área de prueba' );
		$otra          = $this->area( 'Otro ámbito' );
		$yo            = $this->manager( array( $mia ) );
		$ella          = $this->manager( array( $otra ) );
		$procedimiento = $this->procedure( $this->administrator(), array( $mia ) );
		$solicitud     = $this->application( $procedimiento, $this->school_head( 'C0001' ) );

		$this->assertSame( $procedimiento, ProcedureAccess::root_id( $solicitud ) );
		$this->assertSame( array( $mia ), ProcedureAccess::post_areas( $solicitud ) );

		$this->assertTrue( ProcedureAccess::can_review( $yo, $procedimiento ) );
		$this->assertTrue( ProcedureAccess::can_review( $yo, $solicitud ) );
		$this->assertTrue( user_can( $yo, 'read_post', $solicitud ) );
		$this->assertTrue( user_can( $yo, 'edit_post', $solicitud ) );
		$this->assertTrue( user_can( $yo, 'delete_post', $solicitud ) );

		$this->assertFalse( ProcedureAccess::can_review( $ella, $procedimiento ) );
		$this->assertFalse( user_can( $ella, 'read_post', $solicitud ) );
		$this->assertFalse( user_can( $ella, 'edit_post', $solicitud ) );
		$this->assertFalse( user_can( $ella, 'delete_post', $solicitud ) );
	}

	// ─── por centro ────────────────────────────────────────────────────────

	/**
	 * Solicita quien tiene `prc_apply` y un código de centro; sin código, nada.
	 */
	public function test_applying_needs_the_capability_and_a_school_code() {
		$procedimiento = $this->procedure( $this->administrator(), array( $this->area( 'Área de prueba' ) ) );

		$con = $this->school_head( 'C0001' );
		$this->assertTrue( ProcedureAccess::can_apply( $con ) );
		$this->assertTrue( ProcedureAccess::can_apply( $con, $procedimiento ) );

		$sin = $this->school_head();
		$this->assertFalse( ProcedureAccess::can_apply( $sin ) );
		$this->assertFalse( ProcedureAccess::can_apply( $sin, $procedimiento ) );

		$manager = $this->manager();
		update_user_meta( $manager, \Prc\Access\CentreScope::META_KEY, 'C0001' );
		$this->assertFalse( ProcedureAccess::can_apply( $manager ), 'el código sin la capacidad tampoco' );
		$this->assertFalse( ProcedureAccess::can_apply( 0, $procedimiento ) );
	}

	/**
	 * A un borrador no se solicita, ni a lo que no es un procedimiento.
	 */
	public function test_nobody_applies_to_a_draft() {
		$head     = $this->school_head( 'C0001' );
		$borrador = $this->procedure( $this->administrator(), array(), array(), array( 'post_status' => 'draft' ) );
		$pagina   = (int) self::factory()->post->create( array( 'post_type' => 'page' ) );

		$this->assertFalse( ProcedureAccess::can_apply( $head, $borrador ) );
		$this->assertFalse( ProcedureAccess::can_apply( $head, $pagina ) );
		$this->assertFalse( user_can( $head, 'read_post', $borrador ), 'el centro no ve un borrador' );
	}

	/**
	 * La titularidad del centro se cruza con la del procedimiento, si el
	 * catálogo conoce el centro; si no, se solicita igual.
	 */
	public function test_ownership_is_crossed_with_the_catalogue() {
		add_filter(
			'prc_centres',
			static function () {
				return array(
					array(
						'code'      => 'PUB01',
						'name'      => 'Público',
						'ownership' => 'public',
					),
					array(
						'code'      => 'PRI01',
						'name'      => 'Privado',
						'ownership' => 'private',
					),
				);
			}
		);
		$admin        = $this->administrator();
		$solo_publico = $this->procedure( $admin, array(), array( ProcedureMetaKeys::OWNERSHIP => array( 'public' ) ) );
		$para_todos   = $this->procedure( $admin, array(), array( ProcedureMetaKeys::OWNERSHIP => array( 'public', 'private' ) ) );
		$sin_decir    = $this->procedure( $admin );

		$publico     = $this->school_head( 'PUB01' );
		$privado     = $this->school_head( 'PRI01' );
		$desconocido = $this->school_head( 'XXX99' );

		$this->assertTrue( ProcedureAccess::can_apply( $publico, $solo_publico ) );
		$this->assertFalse( ProcedureAccess::can_apply( $privado, $solo_publico ) );
		$this->assertTrue( ProcedureAccess::can_apply( $privado, $para_todos ) );
		$this->assertTrue( ProcedureAccess::can_apply( $privado, $sin_decir ), 'sin titularidad dicha, cualquiera' );
		$this->assertTrue( ProcedureAccess::can_apply( $desconocido, $solo_publico ), 'el catálogo puede ir por detrás de la realidad' );
	}

	/**
	 * Un centro ve su solicitud y no la de otro; y no la edita desde el
	 * escritorio, porque la escribe el aplicativo.
	 */
	public function test_a_school_sees_its_own_application_and_nobody_elses() {
		$procedimiento = $this->procedure( $this->administrator(), array( $this->area( 'Área de prueba' ) ) );
		$yo            = $this->school_head( 'C0001' );
		$otro          = $this->school_head( 'C0002' );
		$mia           = $this->application( $procedimiento, $yo );
		$suya          = $this->application( $procedimiento, $otro );

		$this->assertTrue( ProcedureAccess::can_view_application( $yo, $mia ) );
		$this->assertTrue( user_can( $yo, 'read_post', $mia ) );
		$this->assertFalse( ProcedureAccess::can_view_application( $yo, $suya ) );
		$this->assertFalse( user_can( $yo, 'read_post', $suya ) );
		$this->assertFalse( user_can( $yo, 'edit_post', $mia ), 'la solicitud la escribe el aplicativo, no el escritorio' );

		// Es el centro, no la cuenta: otra persona del mismo centro la ve.
		$companera = $this->school_head( 'C0001' );
		$this->assertTrue( ProcedureAccess::can_view_application( $companera, $mia ) );

		// Y sin código, ni la propia.
		delete_user_meta( $yo, \Prc\Access\CentreScope::META_KEY );
		$this->assertFalse( ProcedureAccess::can_view_application( $yo, $mia ) );
		$this->assertFalse( user_can( $yo, 'read_post', $mia ) );

		$this->assertFalse( ProcedureAccess::can_view_application( $yo, $procedimiento ), 'esto no es una solicitud' );
	}

	/**
	 * Quien administra el aplicativo pasa por encima de todo.
	 */
	public function test_the_manager_sees_everything() {
		$otra          = $this->area( 'Otro ámbito' );
		$procedimiento = $this->procedure( $this->manager( array( $otra ) ), array( $otra ) );
		$solicitud     = $this->application( $procedimiento, $this->school_head( 'C0001' ) );
		$admin         = $this->administrator();

		$this->assertTrue( ProcedureAccess::is_manager( $admin ) );
		$this->assertTrue( ProcedureAccess::can_edit( $admin, $procedimiento ) );
		$this->assertTrue( ProcedureAccess::can_view_application( $admin, $solicitud ) );
		$this->assertTrue( user_can( $admin, 'edit_post', $solicitud ) );
		$this->assertFalse( ProcedureAccess::is_manager( $this->manager( array( $otra ) ) ) );
	}

	/** Editors may change their own organisers while retaining foreign ones. */
	public function test_resolve_shared_area_assignment() {
		$service = $this->area( 'Ámbito 1' );
		$a1      = $this->area( 'Subámbito 1', $service );
		$a2      = $this->area( 'Subámbito 2', $service );
		$foreign = $this->area( 'Ámbito 2' );
		$editor  = (int) self::factory()->user->create( array( 'role' => 'editor' ) );
		update_user_meta( $editor, ProcedureAccess::USER_AREA_META, array( $service ) );
		$post = $this->procedure( $this->administrator(), array( $a1, $foreign ) );
		$this->assertEqualsCanonicalizing( array( $a2, $foreign ), ProcedureAccess::resolve_area_assignment( $post, array( $a2 ), $editor ) );
		$this->assertSame( array( $foreign ), ProcedureAccess::resolve_area_assignment( $post, array(), $editor ) );
		$this->assertWPError( ProcedureAccess::resolve_area_assignment( $post, array( $foreign ), $editor ) );
		$this->assertWPError( ProcedureAccess::resolve_area_assignment( $post, array( 99999999 ), $editor ) );
		$this->assertWPError( ProcedureAccess::resolve_area_assignment( $post, array( array( $a1 ) ), $editor ) );
		$own = $this->procedure( $this->administrator(), array( $a1 ) );
		$this->assertWPError( ProcedureAccess::resolve_area_assignment( $own, array(), $editor ) );
	}

	/** Legacy cardinality and invalid IDs never grant an ambiguous scope. */
	public function test_historical_scope_states() {
		$first = $this->area( 'Ámbito 1' );
		$other = $this->area( 'Ámbito 2' );
		$user  = $this->manager();
		foreach ( array( (string) $first, array( $first ) ) as $raw ) {
			update_user_meta( $user, ProcedureAccess::USER_AREA_META, $raw );
			$this->assertSame( 'resolved', ProcedureAccess::scope_assignment_state( $user )['state'] );
			$this->assertSame( array( $first ), ProcedureAccess::user_areas( $user ) );
		}
		foreach ( array( array( $first, $other ), $first . ',' . $other ) as $raw ) {
			update_user_meta( $user, ProcedureAccess::USER_AREA_META, $raw );
			$this->assertSame( 'ambiguous', ProcedureAccess::scope_assignment_state( $user )['state'] );
			$this->assertSame( array(), ProcedureAccess::user_areas( $user ) );
		}
		foreach ( array( array( $first, 99999999 ), '99999999' ) as $raw ) {
			update_user_meta( $user, ProcedureAccess::USER_AREA_META, $raw );
			$this->assertSame( 'invalid', ProcedureAccess::scope_assignment_state( $user )['state'] );
			$this->assertSame( array(), ProcedureAccess::user_areas( $user ) );
		}
	}

	/** Administration can repair an orphan while an editor cannot create one. */
	public function test_admin_can_repair_an_unscoped_procedure() {
		$admin  = $this->administrator();
		$editor = (int) self::factory()->user->create( array( 'role' => 'editor' ) );
		$area   = $this->area( 'Ámbito 1' );
		update_user_meta( $editor, ProcedureAccess::USER_AREA_META, array( $area ) );
		$post = $this->procedure( $admin, array( $area ) );
		$this->assertSame( array(), ProcedureAccess::resolve_area_assignment( $post, array(), $admin ) );
		$this->assertWPError( ProcedureAccess::resolve_area_assignment( $post, array(), $editor ) );
	}

	/** The deployment report lists unresolved editors without migrating them. */
	public function test_scope_diagnostics_classifies_without_migrating() {
		$area  = $this->area( 'Ámbito 1' );
		$other = $this->area( 'Ámbito 2' );
		$user  = (int) self::factory()->user->create( array( 'role' => 'editor' ) );
		$raw   = $area . ',' . $other;
		update_user_meta( $user, ProcedureAccess::USER_AREA_META, $raw );
		$rows = array_column( ProcedureAccess::scope_diagnostics(), null, 'user_id' );
		$this->assertSame( 'ambiguous', $rows[ $user ]['state'] );
		$this->assertTrue( $rows[ $user ]['legacy'] );
		$this->assertSame( $raw, get_user_meta( $user, ProcedureAccess::USER_AREA_META, true ) );
	}

	// ─── el padre ──────────────────────────────────────────────────────────

	/**
	 * Una solicitud no se muda al procedimiento de otro ámbito, ni al propio.
	 *
	 * `map_meta_cap` mira el padre que tiene; la edición rápida y `post.php`
	 * mandan el que se pide, y los dos acaban en `wp_update_post()`.
	 */
	public function test_an_application_never_changes_procedure() {
		$mia       = $this->area( 'Área de prueba' );
		$otra      = $this->area( 'Otro ámbito' );
		$yo        = $this->manager( array( $mia ) );
		$propio    = $this->procedure( $this->administrator(), array( $mia ) );
		$otro_mio  = $this->procedure( $this->administrator(), array( $mia ) );
		$ajeno     = $this->procedure( $this->administrator(), array( $otra ) );
		$solicitud = $this->application( $propio, $this->school_head( 'C0001' ) );

		$this->acting_as( $yo );
		foreach ( array( $ajeno, $otro_mio, 0 ) as $pedido ) {
			wp_update_post(
				array(
					'ID'          => $solicitud,
					'post_parent' => $pedido,
				)
			);
			$this->assertSame( $propio, (int) get_post_field( 'post_parent', $solicitud ) );
		}
	}

	/**
	 * Un procedimiento no tiene padre: con uno, heredaría el ámbito de otro.
	 */
	public function test_a_procedure_cannot_get_a_parent() {
		$mia   = $this->area( 'Área de prueba' );
		$yo    = $this->manager( array( $mia ) );
		$ajeno = $this->procedure( $this->administrator(), array( $this->area( 'Otro ámbito' ) ) );
		$mio   = $this->procedure( $yo, array( $mia ) );

		$this->acting_as( $yo );
		wp_update_post(
			array(
				'ID'          => $mio,
				'post_parent' => $ajeno,
			)
		);
		$this->assertSame( 0, (int) get_post_field( 'post_parent', $mio ) );

		$nuevo = (int) wp_insert_post(
			array(
				'post_type'   => ProcedurePostType::POST_TYPE,
				'post_status' => 'draft',
				'post_title'  => 'Intruso',
				'post_parent' => $ajeno,
			)
		);
		$this->assertSame( 0, (int) get_post_field( 'post_parent', $nuevo ) );
	}

	/**
	 * Solicitar sigue siendo crear una solicitud en un procedimiento de cualquier ámbito.
	 */
	public function test_an_application_is_still_created_under_its_procedure() {
		$procedimiento = $this->procedure( $this->administrator(), array( $this->area( 'Otro ámbito' ) ) );
		$this->acting_as( $this->school_head( 'C0001' ) );

		$solicitud = (int) wp_insert_post(
			array(
				'post_type'   => ApplicationPostType::POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => 'Centro de prueba',
				'post_parent' => $procedimiento,
			)
		);
		$this->assertSame( $procedimiento, (int) get_post_field( 'post_parent', $solicitud ) );
	}
}
