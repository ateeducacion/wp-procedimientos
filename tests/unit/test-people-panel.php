<?php
/**
 * Tests for the «Personas» panel of the workshop and the people in the review.
 *
 * @package Prc
 */

use Prc\Domain\ApplicationPeople;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PublicFront\People;
use Prc\PublicFront\PeopleForm;
use Prc\PublicFront\PeopleSpecPanel;
use Prc\PublicFront\ProcedureEditor;
use Prc\PublicFront\View\ProcedureEditorView;

/**
 * El panel «Personas» del taller y la lista en la revisión (ADR-0041).
 */
class Test_People_Panel extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Con el aplicativo, sus páginas y sin rechazos heredados.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
		$this->pages();
		add_filter( 'pre_wp_mail', '__return_true' );
		\Closure::bind(
			static function (): void {
				self::$rejected = '';
			},
			null,
			ProcedureEditor::class
		)();
	}

	/**
	 * Un ámbito, quien lo gestiona y un procedimiento suyo con el plazo de
	 * solicitud ya cerrado.
	 *
	 * @param array<string, mixed> $meta Extra meta.
	 * @return array{0:int, 1:int, 2:int} Area, manager, procedure.
	 */
	private function mio( array $meta = array() ): array {
		$area = $this->area( 'Área de prueba' );
		$uid  = $this->manager( array( $area ) );
		$id   = $this->procedure(
			$uid,
			array( $area ),
			$meta + array(
				ProcedureMetaKeys::OPENS_AT  => gmdate( 'Y-m-d', strtotime( '-30 days' ) ),
				ProcedureMetaKeys::CLOSES_AT => gmdate( 'Y-m-d', strtotime( '-10 days' ) ),
			),
			array( 'post_title' => 'Red de prueba' )
		);
		return array( $area, $uid, $id );
	}

	/**
	 * Mandar el panel y devolver por dónde salió.
	 *
	 * @param int                  $uid          Who submits.
	 * @param int                  $procedure_id Procedure.
	 * @param array<string, mixed> $campos       Fields of the panel.
	 * @return string|null
	 */
	private function guardar( int $uid, int $procedure_id, array $campos ): ?string {
		$this->acting_as( $uid );
		$op = ProcedureEditor::OP_PEOPLE_SPEC;
		$this->post(
			array(
				ProcedureEditor::FIELD_DO        => $op,
				ProcedureEditor::FIELD_PROCEDURE => (string) $procedure_id,
			) + $campos,
			ProcedureEditor::nonce_action( $op ),
			ProcedureEditor::nonce_name( $op )
		);
		return $this->exit_url( array( ProcedureEditor::class, 'handle' ) );
	}

	/**
	 * Los campos del panel con una lista de dos papeles y dos grupos.
	 *
	 * @param array<string, string> $extra Overrides.
	 * @return array<string, string>
	 */
	private function campos( array $extra = array() ): array {
		return array_merge(
			array(
				PeopleSpecPanel::FIELD_ENABLED => '1',
				PeopleSpecPanel::FIELD_ROLES   => "Coordinación\nParticipante | grupo",
				PeopleSpecPanel::FIELD_GROUPS  => "Eje 1\nEje 2",
				PeopleSpecPanel::FIELD_MIN     => '1',
				PeopleSpecPanel::FIELD_MAX     => '10',
				PeopleSpecPanel::FIELD_OPENS   => gmdate( 'Y-m-d', strtotime( '-1 day' ) ),
				PeopleSpecPanel::FIELD_CLOSES  => gmdate( 'Y-m-d', strtotime( '+5 days' ) ),
			),
			$extra
		);
	}

	/**
	 * Con el plazo de solicitud cerrado el panel se guarda: la lista tiene su
	 * propia ventana. Y la pestaña existe y pinta su formulario.
	 */
	public function test_the_panel_saves_with_the_application_deadline_closed() {
		list( , $uid, $procedimiento ) = $this->mio();
		$this->acting_as( $uid );
		$_GET[ ProcedureEditor::ARG_PROCEDURE ] = (string) $procedimiento;
		$_GET[ ProcedureEditor::ARG_PANEL ]     = ProcedureEditor::PANEL_PEOPLE;
		$html                                   = ProcedureEditorView::html( ProcedureEditor::model() );
		$this->assertStringContainsString( 'name="' . PeopleSpecPanel::FIELD_ROLES . '"', $html );
		$this->assertStringContainsString( 'value="' . ProcedureEditor::OP_PEOPLE_SPEC . '"', $html );
		$this->assertStringContainsString( '>Personas', $html, 'la pestaña' );

		$url = $this->guardar( $uid, $procedimiento, $this->campos() );
		$this->assertNotNull( $url );
		$this->assertSame( 'personas', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
		$spec = People::spec( $procedimiento );
		$this->assertTrue( $spec['enabled'] );
		$this->assertSame( array( 'r1', 'r2' ), array_column( $spec['roles'], 'key' ) );
		$this->assertTrue( $spec['roles'][1]['needs_group'] );
		$this->assertSame( 10, $spec['max'] );
	}

	/**
	 * Lo mal escrito no se guarda, y se repinta lo tecleado.
	 */
	public function test_a_wrong_panel_is_refused_and_repainted() {
		list( , $uid, $procedimiento ) = $this->mio();
		$this->assertNull( $this->guardar( $uid, $procedimiento, $this->campos( array( PeopleSpecPanel::FIELD_MAX => '500' ) ) ) );
		$this->assertFalse( People::spec( $procedimiento )['enabled'] );
		$_GET[ ProcedureEditor::ARG_PROCEDURE ] = (string) $procedimiento;
		$_GET[ ProcedureEditor::ARG_PANEL ]     = ProcedureEditor::PANEL_PEOPLE;
		$html                                   = ProcedureEditorView::html( ProcedureEditor::model() );
		$this->assertStringContainsString( 'no puede pasar de 200', $html );
		$this->assertStringContainsString( 'value="500"', $html );
	}

	/**
	 * Renombrar con personas dadas de alta las sigue; quitar un papel en uso
	 * se rechaza sin guardar nada.
	 */
	public function test_renaming_follows_the_people_and_removing_a_used_role_is_refused() {
		list( , $uid, $procedimiento ) = $this->mio();
		$this->guardar( $uid, $procedimiento, $this->campos() );
		$director  = $this->school_head( 'C0001' );
		$solicitud = $this->application( $procedimiento, $director );
		$alta      = People::add(
			$solicitud,
			$director,
			array(
				'name'    => 'Ana',
				'surname' => 'Pérez',
				'dni'     => '12345678Z',
				'email'   => 'ana@example.org',
				'role'    => 'r2',
				'group'   => 'g2',
			)
		);
		$this->assertTrue( $alta['ok'], $alta['error'] );

		$this->guardar(
			$uid,
			$procedimiento,
			$this->campos(
				array(
					PeopleSpecPanel::FIELD_ROLES  => "Coordinación\nDocente participante | grupo",
					PeopleSpecPanel::FIELD_GROUPS => "Eje 1\nEje dos",
				)
			)
		);
		$texto = ApplicationPeople::describe( People::spec( $procedimiento ), People::of( $solicitud )[0] );
		$this->assertStringContainsString( 'Docente participante', $texto );
		$this->assertStringContainsString( 'Eje dos', $texto );

		$antes = get_post_meta( $procedimiento, ProcedureMetaKeys::PEOPLE_SPEC, true );
		$this->assertNull( $this->guardar( $uid, $procedimiento, $this->campos( array( PeopleSpecPanel::FIELD_ROLES => "Coordinación\n" ) ) ) );
		$this->assertSame( $antes, get_post_meta( $procedimiento, ProcedureMetaKeys::PEOPLE_SPEC, true ), 'no se ha guardado nada' );
	}

	/**
	 * Un procedimiento de profesorado no tiene lista; quien gestiona otro
	 * ámbito no la toca.
	 */
	public function test_teachers_and_foreign_managers_cannot_save_it() {
		list( , $uid, $docentes ) = $this->mio( array( ProcedureMetaKeys::AUDIENCE => ProcedureMetaKeys::AUDIENCE_TEACHERS ) );
		$this->assertNull( $this->guardar( $uid, $docentes, $this->campos() ) );
		$this->assertFalse( People::spec( $docentes )['enabled'] );

		list( , , $ajeno ) = $this->mio();
		$otro              = $this->manager( array( $this->area( 'Otro ámbito' ) ) );
		$this->assertNull( $this->guardar( $otro, $ajeno, $this->campos() ) );
		$this->assertFalse( People::spec( $ajeno )['enabled'] );
	}

	/**
	 * En la revisión, la lista y su historial en solo lectura, con el DNI del
	 * historial tapado; sin especificación, nada.
	 */
	public function test_the_review_shows_people_and_history_read_only() {
		list( , $uid, $procedimiento ) = $this->mio();
		$this->guardar( $uid, $procedimiento, $this->campos( array( PeopleSpecPanel::FIELD_MIN => '2' ) ) );
		$director  = $this->school_head( 'C0001' );
		$solicitud = $this->application( $procedimiento, $director );
		People::add(
			$solicitud,
			$director,
			array(
				'name'    => 'Ana',
				'surname' => 'Pérez',
				'dni'     => '12345678Z',
				'email'   => 'ana@example.org',
				'role'    => 'r1',
			)
		);
		$this->acting_as( $uid );
		$_GET[ ProcedureEditor::ARG_PROCEDURE ] = (string) $procedimiento;
		$_GET[ ProcedureEditor::ARG_PANEL ]     = ProcedureEditor::PANEL_APPLICATIONS;
		$html                                   = ProcedureEditorView::html( ProcedureEditor::model() );
		$this->assertStringContainsString( 'Ver el historial de la lista', $html, 'el historial no se lee al pintar la tabla' );
		$this->assertStringNotContainsString( 'DNI •', $html );
		$this->assertStringContainsString( 'Incompleta: pide al menos 2', $html );

		$_GET[ PeopleSpecPanel::ARG_HISTORY ] = (string) $solicitud;
		$html                                 = ProcedureEditorView::html( ProcedureEditor::model() );
		$this->assertStringContainsString( 'Historial de la lista', $html );
		$this->assertStringContainsString( '<details class="prc-revision" open>', $html, 'la revisión de esa solicitud, abierta' );
		$this->assertStringContainsString( '12345678Z', $html, 'en la lista, entero' );
		$this->assertStringContainsString( 'DNI •••••678Z', $html, 'en el historial, tapado' );
		$this->assertStringNotContainsString( PeopleForm::FIELD_OP, $html );

		list( , $otro_uid, $sin ) = $this->mio();
		$this->application( $sin, $this->school_head( 'C0002' ), 'C0002' );
		$this->acting_as( $otro_uid );
		$_GET[ ProcedureEditor::ARG_PROCEDURE ] = (string) $sin;
		$html                                   = ProcedureEditorView::html( ProcedureEditor::model() );
		$this->assertStringNotContainsString( 'historial de la lista', $html );
		$this->assertStringNotContainsString( 'prc-personas-revision', $html );
		$this->assertSame( ApplicationMetaKeys::REVIEW_SUBMITTED, get_post_meta( $solicitud, ApplicationMetaKeys::REVIEW_STATE, true ) );
	}
}
