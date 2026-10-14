<?php
/**
 * Tests for applying on behalf of a rural group of schools (ADR-0031).
 *
 * @package Prc
 */

use Prc\Access\CentreScope;
use Prc\Access\ProcedureAccess;
use Prc\Domain\ProcedureInput;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PublicFront\Applications;
use Prc\PublicFront\ApplyForm;
use Prc\PublicFront\MyCentre;
use Prc\PublicFront\View\ApplyFormView;

/**
 * Quien dirige un centro y coordina una agrupación de escuelas rurales.
 *
 * Tiene dos códigos: el de su centro y el de la agrupación. El procedimiento
 * dice si admite centros, agrupaciones como colectivo o las dos cosas; si
 * admite una sola, se solicita con ella sin preguntar, y si admite las dos se
 * elige. El código nunca es libre: tiene que ser uno de los de la cuenta.
 */
class Test_Rural_Groups extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Con el aplicativo, sus páginas y un catálogo inventado.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
		$this->pages();
		remove_all_filters( 'prc_centres' );
		add_filter( 'prc_centres', array( $this, 'catalogo' ) );
		\Closure::bind(
			static function (): void {
				self::$rejected = '';
			},
			null,
			ApplyForm::class
		)();
	}

	/**
	 * Un centro, una agrupación y un centro privado.
	 *
	 * @return array<int, array<string, string>>
	 */
	public function catalogo(): array {
		return array(
			array(
				'code' => 'C0001',
				'name' => 'Centro de prueba Norte',
			),
			array(
				'code' => 'G0001',
				'name' => 'Agrupación rural de prueba',
				'kind' => ProcedureMetaKeys::KIND_RURAL_GROUP,
			),
			array(
				'code'      => 'C0002',
				'name'      => 'Centro de prueba Sur',
				'ownership' => ProcedureMetaKeys::OWNERSHIP_PRIVATE,
			),
		);
	}

	/**
	 * Dirección de un centro que además coordina una agrupación.
	 *
	 * @param string $centre School code.
	 * @param string $group  Rural group code.
	 * @return int
	 */
	private function coordinadora( string $centre = 'C0001', string $group = 'G0001' ): int {
		$uid = $this->school_head( $centre );
		update_user_meta( $uid, CentreScope::group_meta_key(), $group );
		return $uid;
	}

	/**
	 * Un procedimiento abierto hoy que admite esas clases de centro.
	 *
	 * @param string[]|null $kinds Null leaves the meta unset, as older procedures have it.
	 * @return int
	 */
	private function procedimiento( ?array $kinds ): int {
		$meta = array(
			ProcedureMetaKeys::OPENS_AT  => gmdate( 'Y-m-d', (int) strtotime( ProcedureState::today() . ' -1 days' ) ),
			ProcedureMetaKeys::CLOSES_AT => gmdate( 'Y-m-d', (int) strtotime( ProcedureState::today() . ' +1 days' ) ),
		);
		if ( null !== $kinds ) {
			$meta[ ProcedureMetaKeys::CENTRE_KINDS ] = $kinds;
		}
		return $this->procedure( $this->administrator(), array( $this->area() ), $meta, array( 'post_title' => 'Red de prueba' ) );
	}

	/**
	 * Un envío del formulario, y por dónde salió.
	 *
	 * @param int    $uid          Who submits.
	 * @param int    $procedure_id Procedure ID.
	 * @param string $centre       Code sent, '' for none.
	 * @return string|null Null when refused.
	 */
	private function enviar( int $uid, int $procedure_id, string $centre = '' ): ?string {
		$this->acting_as( $uid );
		$campos = array(
			ApplyForm::FIELD_OP        => ApplyForm::OP_APPLY,
			ApplyForm::FIELD_PROCEDURE => (string) $procedure_id,
			ApplyForm::FIELD_POSITION  => ApplicationMetaKeys::POSITION_HEAD,
		);
		if ( '' !== $centre ) {
			$campos[ ApplyForm::FIELD_CENTRE ] = $centre;
		}
		$this->post( $campos, ApplyForm::NONCE_ACTION, ApplyForm::NONCE_FIELD );
		return $this->exit_url( array( ApplyForm::class, 'handle' ) );
	}

	/**
	 * El modelo de la pantalla, con el centro pedido en la URL.
	 *
	 * @param int    $procedure_id Procedure ID.
	 * @param string $centre       Code in the URL, '' for none.
	 * @return array<string, mixed>
	 */
	private function modelo( int $procedure_id, string $centre = '' ): array {
		$_GET[ ApplyForm::ARG_PROCEDURE ] = (string) $procedure_id;
		unset( $_GET[ ApplyForm::ARG_CENTRE ] );
		if ( '' !== $centre ) {
			$_GET[ ApplyForm::ARG_CENTRE ] = $centre;
		}
		return ApplyForm::model();
	}

	// ─── la persona ────────────────────────────────────────────────────────

	/**
	 * La agrupación se lee de su propia meta, con clave configurable, y falla en cerrado.
	 */
	public function test_the_group_code_is_a_filterable_meta() {
		$this->assertSame( 'prc_rural_group_code', CentreScope::group_meta_key() );

		$uid = $this->coordinadora();
		$this->assertSame( 'G0001', CentreScope::group_code_for( $uid ) );

		update_user_meta( $uid, 'agrupacion_de_la_casa', ' G0002 ' );
		add_filter(
			'prc_rural_group_code_meta_key',
			static function () {
				return 'agrupacion_de_la_casa';
			}
		);
		$this->assertSame( 'G0002', CentreScope::group_code_for( $uid ) );

		add_filter( 'prc_rural_group_code_meta_key', '__return_false' );
		$this->assertSame( '', CentreScope::group_code_for( $uid ) );
	}

	/**
	 * Los códigos de la persona: el centro primero, la agrupación después y sin repetir.
	 */
	public function test_the_codes_of_a_person() {
		$this->assertSame(
			array(
				'C0001' => ProcedureMetaKeys::KIND_SCHOOL,
				'G0001' => ProcedureMetaKeys::KIND_RURAL_GROUP,
			),
			CentreScope::codes_for( $this->coordinadora() )
		);
		$this->assertSame( array( 'C0001' => ProcedureMetaKeys::KIND_SCHOOL ), CentreScope::codes_for( $this->school_head( 'C0001' ) ) );
		$this->assertSame( array(), CentreScope::codes_for( $this->school_head() ) );

		// Quien coordina la agrupación a tiempo completo la tiene como código
		// de centro: lo que es lo dice el catálogo.
		$this->assertSame(
			array( 'G0001' => ProcedureMetaKeys::KIND_RURAL_GROUP ),
			CentreScope::codes_for( $this->coordinadora( 'G0001', 'G0001' ) )
		);
	}

	// ─── el procedimiento ──────────────────────────────────────────────────

	/**
	 * Qué códigos sirven para cada procedimiento.
	 */
	public function test_the_procedure_says_which_kinds_may_apply() {
		$uid = $this->coordinadora();

		$this->assertSame( array( 'C0001' ), array_keys( ProcedureAccess::apply_codes( $uid, $this->procedimiento( array( ProcedureMetaKeys::KIND_SCHOOL ) ) ) ) );
		$this->assertSame( array( 'G0001' ), array_keys( ProcedureAccess::apply_codes( $uid, $this->procedimiento( array( ProcedureMetaKeys::KIND_RURAL_GROUP ) ) ) ) );
		$this->assertSame( array( 'C0001', 'G0001' ), array_keys( ProcedureAccess::apply_codes( $uid, $this->procedimiento( array_keys( ProcedureMetaKeys::centre_kinds() ) ) ) ) );
		$this->assertSame( array( 'C0001', 'G0001' ), array_keys( ProcedureAccess::apply_codes( $uid, $this->procedimiento( null ) ) ), 'sin la meta, como hasta ahora: no se cierra a nadie' );

		$solo_centros = $this->procedimiento( array( ProcedureMetaKeys::KIND_SCHOOL ) );
		$this->assertFalse( ProcedureAccess::can_apply( $uid, $solo_centros, 'G0001' ) );
		$this->assertTrue( ProcedureAccess::can_apply( $uid, $solo_centros, 'C0001' ) );
		$this->assertFalse( ProcedureAccess::can_apply( $uid, $solo_centros, 'C0009' ), 'un código que no es suyo, nunca' );
		$this->assertFalse( ProcedureAccess::can_apply( $this->school_head( 'C0001' ), $this->procedimiento( array( ProcedureMetaKeys::KIND_RURAL_GROUP ) ) ) );
	}

	/**
	 * El taller exige elegir al menos una clase y no guarda nada fuera de la lista.
	 */
	public function test_the_kinds_are_a_closed_required_list() {
		$this->assertSame( array( ProcedureMetaKeys::KIND_RURAL_GROUP ), ProcedureInput::centre_kinds( array( 'otra', ProcedureMetaKeys::KIND_RURAL_GROUP ) ) );

		$r = ProcedureInput::validate( array( 'centre_kinds' => array() ) );
		$this->assertContains( 'centre_kinds', $r['errors'] );

		$r = ProcedureInput::validate( array( 'centre_kinds' => array( ProcedureMetaKeys::KIND_SCHOOL ) ) );
		$this->assertNotContains( 'centre_kinds', $r['errors'] );
		$this->assertSame( array( ProcedureMetaKeys::KIND_SCHOOL ), $r['data']['centre_kinds'] );
	}

	// ─── la pantalla ───────────────────────────────────────────────────────

	/**
	 * Si el procedimiento admite una sola clase, se solicita con ella sin preguntar.
	 */
	public function test_with_one_kind_the_code_is_taken_without_asking() {
		$uid           = $this->coordinadora();
		$procedimiento = $this->procedimiento( array( ProcedureMetaKeys::KIND_RURAL_GROUP ) );
		$this->acting_as( $uid );

		$m = $this->modelo( $procedimiento );
		$this->assertTrue( $m['can_submit'] );
		$this->assertSame( 'G0001', $m['centre']['code'] );
		$this->assertCount( 1, $m['choices'] );
		$this->assertStringNotContainsString( 'Solicita en nombre de', ApplyFormView::html( $m ) );

		$this->assertNotNull( $this->enviar( $uid, $procedimiento ) );
		$this->assertNotNull( Applications::find( $procedimiento, 'G0001' ) );
		$this->assertNull( Applications::find( $procedimiento, 'C0001' ) );
	}

	/**
	 * Si admite las dos, se elige: cada código tiene su propia solicitud.
	 */
	public function test_with_both_kinds_the_person_chooses() {
		$uid           = $this->coordinadora();
		$procedimiento = $this->procedimiento( array_keys( ProcedureMetaKeys::centre_kinds() ) );
		$this->acting_as( $uid );

		$m = $this->modelo( $procedimiento );
		$this->assertSame( 'C0001', $m['centre']['code'], 'el centro primero' );
		$this->assertSame( array( 'C0001', 'G0001' ), array_keys( $m['choices'] ) );
		$html = ApplyFormView::html( $m );
		$this->assertStringContainsString( 'Solicita en nombre de', $html );
		$this->assertStringContainsString( 'Agrupación rural de prueba', $html );

		$m = $this->modelo( $procedimiento, 'G0001' );
		$this->assertSame( 'G0001', $m['centre']['code'] );
		$this->assertStringContainsString( 'name="' . ApplyForm::FIELD_CENTRE . '" value="G0001"', ApplyFormView::html( $m ) );

		$this->assertNotNull( $this->enviar( $uid, $procedimiento, 'G0001' ) );
		$this->assertNotNull( $this->enviar( $uid, $procedimiento, 'C0001' ) );
		$this->assertSame( 2, Applications::count( $procedimiento ) );

		$grupo = (int) Applications::find( $procedimiento, 'G0001' );
		$this->assertSame( 'Agrupación rural de prueba', get_post_meta( $grupo, ApplicationMetaKeys::CENTRE_NAME, true ) );
		$this->assertTrue( ProcedureAccess::can_view_application( $uid, $grupo ), 've la de la agrupación' );
		$this->assertFalse( ProcedureAccess::can_view_application( $this->school_head( 'C0001' ), $grupo ), 'otra persona del centro, no' );
	}

	/**
	 * Un código que no es de la cuenta no se guarda, venga por donde venga.
	 */
	public function test_a_code_that_is_not_yours_is_refused() {
		$uid           = $this->coordinadora();
		$procedimiento = $this->procedimiento( array_keys( ProcedureMetaKeys::centre_kinds() ) );

		$this->assertNull( $this->enviar( $uid, $procedimiento, 'C0002' ) );
		$this->assertSame( 0, Applications::count( $procedimiento ) );

		$this->acting_as( $uid );
		$m = $this->modelo( $procedimiento, 'C0002' );
		$this->assertSame( 'C0001', $m['centre']['code'], 'en la URL se ignora y se vuelve al primero' );
	}

	/**
	 * Sin una clase que encaje, la pantalla dice por qué.
	 */
	public function test_without_a_kind_that_fits_it_says_so() {
		$this->acting_as( $this->school_head( 'C0001' ) );
		$m = $this->modelo( $this->procedimiento( array( ProcedureMetaKeys::KIND_RURAL_GROUP ) ) );

		$this->assertFalse( $m['can_submit'] );
		$this->assertStringContainsString( 'agrupaciones de escuelas rurales', $m['why'] );
	}

	/**
	 * «Mi centro» enseña las solicitudes de los dos códigos, cada una con su enlace.
	 */
	public function test_my_centre_lists_the_applications_of_both_codes() {
		$uid           = $this->coordinadora();
		$procedimiento = $this->procedimiento( array_keys( ProcedureMetaKeys::centre_kinds() ) );
		$this->enviar( $uid, $procedimiento, 'G0001' );

		$this->acting_as( $uid );
		$m = MyCentre::model();
		$this->assertCount( 1, $m['rows'] );
		$this->assertSame( 'G0001', $this->query_arg( (string) $m['rows'][0]['url'], ApplyForm::ARG_CENTRE ) );
	}
}
