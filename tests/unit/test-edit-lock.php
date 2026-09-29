<?php
/**
 * Tests for the edit lock: two people, one procedure, and nobody stepping on anybody.
 *
 * @package Prc
 */

use Prc\Meta\ProcedureMetaKeys;
use Prc\PublicFront\EditLock;
use Prc\PublicFront\ExitSignal;
use Prc\PublicFront\ProcedureEditor;

/**
 * El bloqueo de edición, que es el NATIVO de WordPress.
 *
 * No se prueba un mecanismo propio: se prueba que el aplicativo usa la meta
 * `_edit_lock` del núcleo —la misma que el escritorio (ADR-0008)— y que la
 * respeta en el único sitio donde importa, que es ANTES de escribir. Por eso
 * casi todas las comprobaciones miran la base de datos después de intentarlo
 * y no el aviso.
 *
 * Y el bloqueo es del procedimiento: quien pregunta por una solicitud suya
 * pasa por `ProcedureAccess::root_id()` y ve el mismo bloqueo.
 */
class Test_Edit_Lock extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * El ámbito que convoca.
	 *
	 * @var int
	 */
	private $ambito;

	/**
	 * Quien abre el procedimiento primero.
	 *
	 * @var int
	 */
	private $primera;

	/**
	 * Quien llega después, con el mismo permiso.
	 *
	 * @var int
	 */
	private $segunda;

	/**
	 * El procedimiento.
	 *
	 * @var int
	 */
	private $procedimiento;

	/**
	 * Dos personas del mismo ámbito y un procedimiento suyo.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
		$this->pages();
		remove_all_filters( 'prc_centres' );

		$this->ambito  = $this->area( 'Área de prueba' );
		$this->primera = $this->manager( array( $this->ambito ) );
		$this->segunda = $this->manager( array( $this->ambito ) );
		wp_update_user(
			array(
				'ID'           => $this->primera,
				'display_name' => 'Primera persona',
			)
		);
		wp_update_user(
			array(
				'ID'           => $this->segunda,
				'display_name' => 'Segunda persona',
			)
		);

		$this->procedimiento = $this->procedure(
			$this->primera,
			array( $this->ambito ),
			array(),
			array( 'post_title' => 'Red de centros original' )
		);
	}

	// ─── ayudas ────────────────────────────────────────────────────────────

	/**
	 * Abrir de verdad el taller: es el `render()` quien toma el bloqueo,
	 * porque el `model()` decide y no muta.
	 *
	 * @param int $uid Who is looking.
	 * @return string La pantalla pintada.
	 */
	private function abrir( int $uid ): string {
		$this->acting_as( $uid );
		$_GET                                   = array();
		$_GET[ ProcedureEditor::ARG_PROCEDURE ] = (string) $this->procedimiento;
		return ProcedureEditor::render();
	}

	/**
	 * Lo que el taller decide, sin llegar a pintarlo ni a tomar nada.
	 *
	 * @param int $uid Who is looking.
	 * @return array<string, mixed>
	 */
	private function modelo( int $uid ): array {
		$this->acting_as( $uid );
		$_GET                                   = array();
		$_GET[ ProcedureEditor::ARG_PROCEDURE ] = (string) $this->procedimiento;
		return ProcedureEditor::model();
	}

	/**
	 * Quién tiene el bloqueo según la meta del núcleo.
	 *
	 * `wp_check_post_lock()` contesta «otra persona», así que a quien acaba de
	 * tomarlo le diría que no hay ninguno: aquí se lee la meta directamente,
	 * que es justo lo que hay que afirmar.
	 *
	 * @return int User ID, 0 when there is no lock.
	 */
	private function duenio(): int {
		$trozo = explode( ':', (string) get_post_meta( $this->procedimiento, '_edit_lock', true ) );
		return isset( $trozo[1] ) ? (int) $trozo[1] : 0;
	}

	/**
	 * Mandar una operación del taller y devolver por dónde salió.
	 *
	 * @param int                  $uid   Who submits.
	 * @param string               $op    Operation.
	 * @param array<string, mixed> $extra Extra fields.
	 * @return string|null
	 */
	private function enviar( int $uid, string $op, array $extra = array() ): ?string {
		$this->acting_as( $uid );
		$this->post(
			array_merge(
				array(
					ProcedureEditor::FIELD_DO        => $op,
					ProcedureEditor::FIELD_PROCEDURE => (string) $this->procedimiento,
					ProcedureEditor::FIELD_ROW       => '0',
				),
				$extra
			),
			ProcedureEditor::nonce_action( $op ),
			ProcedureEditor::nonce_name( $op, 0 )
		);
		return $this->exit_url( array( ProcedureEditor::class, 'handle' ) );
	}

	/**
	 * Preparar un «Tomar posesión».
	 *
	 * @param int         $uid   Who submits.
	 * @param string|null $nonce Null for the good one, a string to forge it.
	 * @return void
	 */
	private function tomar_posesion( int $uid, ?string $nonce = null ): void {
		$this->acting_as( $uid );
		$campos = array(
			EditLock::FIELD_DO   => EditLock::OP_TAKEOVER,
			EditLock::FIELD_POST => (string) $this->procedimiento,
		);
		if ( null === $nonce ) {
			$this->post( $campos, EditLock::nonce_action( $this->procedimiento ), EditLock::NONCE_FIELD );
		} else {
			$campos[ EditLock::NONCE_FIELD ] = $nonce;
			$this->post( $campos );
		}
	}

	/**
	 * Ejecutar algo que tiene que morir con `wp_die()` y devolver el motivo.
	 *
	 * @param callable $handler What to run.
	 * @return string
	 */
	private function murio( callable $handler ): string {
		add_filter( 'prc_exit_throws', '__return_true' );
		try {
			$handler();
		} catch ( WPDieException $e ) {
			return $e->getMessage();
		} catch ( ExitSignal $e ) {
			$this->fail( 'Salió por una redirección en vez de rechazar la escritura: ' . $e->url );
		}
		$this->fail( 'La escritura no se rechazó.' );
	}

	// ─── tomar el bloqueo al abrir ─────────────────────────────────────────

	/**
	 * Abrir el taller toma el bloqueo NATIVO, el mismo que el escritorio.
	 *
	 * Esta es la ventaja de no inventarse uno: si alguien abre el
	 * procedimiento en el editor de WordPress y otra persona en el taller, se
	 * ven.
	 */
	public function test_opening_the_workshop_takes_the_native_lock() {
		$this->assertSame( 0, (int) $this->modelo( $this->primera )['lock']['owner'], 'el procedimiento estaba libre' );
		$this->assertSame( '', (string) get_post_meta( $this->procedimiento, '_edit_lock', true ), 'y mirarlo no lo cogió' );

		$html = $this->abrir( $this->primera );

		$this->assertSame( $this->primera, $this->duenio() );
		$this->assertStringContainsString( 'id="prc-edit-lock"', $html );
		$this->assertMatchesRegularExpression( '/data-lock="\d+:' . $this->primera . '"/', $html, 'el Heartbeat necesita el bloqueo para renovarlo' );
		$this->assertStringNotContainsString( ' open>', $html, 'sin nadie dentro, el aviso no se abre' );

		require_once ABSPATH . 'wp-admin/includes/post.php';
		$this->acting_as( $this->segunda );
		$this->assertSame( $this->primera, (int) wp_check_post_lock( $this->procedimiento ), 'el escritorio ve el mismo bloqueo' );
	}

	/**
	 * A la segunda persona se le avisa con el NOMBRE de quien lo tiene, no con
	 * un «alguien», y se le ofrecen las dos salidas.
	 */
	public function test_the_second_person_is_told_who_has_it() {
		$this->abrir( $this->primera );

		$this->acting_as( $this->segunda );
		$lock = EditLock::status( $this->procedimiento, true );
		$this->assertSame( $this->procedimiento, $lock['procedure_id'] );
		$this->assertSame( $this->primera, $lock['owner'] );
		$this->assertSame( 'Primera persona', $lock['name'] );

		$html = $this->abrir( $this->segunda );

		$this->assertStringContainsString( 'Lo está editando otra persona', $html );
		$this->assertStringContainsString( 'Primera persona', $html );
		$this->assertStringContainsString( 'Tomar posesión', $html );
		$this->assertStringContainsString( 'Dejarlo y volver a mis procedimientos', $html );
		$this->assertStringContainsString( 'data-lock=""', $html, 'no se renueva un bloqueo ajeno' );
		$this->assertStringContainsString( 'aria-labelledby="prc-lock-title" open', $html, 'el aviso se ve sin JavaScript' );
		$this->assertSame( $this->primera, $this->duenio(), 'y mirar no se lo quita a nadie' );
	}

	/**
	 * Quien solo puede consultar no coge el bloqueo: se lo quitaría a quien sí
	 * puede editar, y encima para nada.
	 */
	public function test_a_reader_does_not_take_the_lock() {
		$lock = EditLock::claim( EditLock::status( $this->procedimiento, false ) );

		$this->assertSame( EditLock::none(), $lock );
		$this->assertSame( 0, $lock['procedure_id'] );
		$this->assertSame( '', (string) get_post_meta( $this->procedimiento, '_edit_lock', true ) );
		$this->assertSame( '', EditLock::render( $lock ), 'sin procedimiento no se pinta ningún aviso' );
	}

	/**
	 * Y un procedimiento que no existe no se bloquea.
	 */
	public function test_nothing_is_locked_without_a_procedure() {
		$this->assertSame( 0, EditLock::owner( 0 ) );
		$this->assertSame( EditLock::none(), EditLock::status( 0, true ) );

		// Soltar lo que no hay tampoco revienta.
		EditLock::release( 0 );
		$this->assertSame( 0, $this->duenio() );
	}

	// ─── antes de escribir, no después ─────────────────────────────────────

	/**
	 * El envío de quien perdió el bloqueo se rechaza ANTES de tocar nada: la
	 * pantalla que trae es la de hace media hora y pisaría lo que la otra
	 * persona esté escribiendo.
	 */
	public function test_a_stale_save_is_rejected_before_writing_anything() {
		$this->abrir( $this->primera );

		$motivo = $this->murio(
			function () {
				$this->enviar(
					$this->segunda,
					ProcedureEditor::PANEL_LINKS,
					array( ProcedureMetaKeys::RESOLUTION_URL => 'https://example.org/resolucion.pdf' )
				);
			}
		);

		$this->assertStringContainsString( 'Primera persona', $motivo );
		$this->assertSame( '', (string) get_post_meta( $this->procedimiento, ProcedureMetaKeys::RESOLUTION_URL, true ) );
		$this->assertSame( 'Red de centros original', get_the_title( $this->procedimiento ) );
	}

	/**
	 * Y marcar como histórico es una escritura más: también pasa por el bloqueo.
	 */
	public function test_a_stale_archive_is_rejected_too() {
		$this->abrir( $this->primera );

		$this->murio(
			function () {
				$this->enviar( $this->segunda, ProcedureEditor::OP_ARCHIVE );
			}
		);

		$this->assertSame( '', (string) get_post_meta( $this->procedimiento, ProcedureMetaKeys::ARCHIVED, true ) );
	}

	/**
	 * Quien tiene el bloqueo sí guarda: el guardián no estorba a su dueño.
	 */
	public function test_whoever_holds_the_lock_still_saves() {
		$this->abrir( $this->primera );

		$this->assertNotNull(
			$this->enviar(
				$this->primera,
				ProcedureEditor::PANEL_LINKS,
				array( ProcedureMetaKeys::RESOLUTION_URL => 'https://example.org/resolucion.pdf' )
			)
		);
		$this->assertSame( 'https://example.org/resolucion.pdf', (string) get_post_meta( $this->procedimiento, ProcedureMetaKeys::RESOLUTION_URL, true ) );
	}

	// ─── el bloqueo es del procedimiento ───────────────────────────────────

	/**
	 * Una solicitud no se bloquea aparte: lo que se bloquea es su procedimiento.
	 */
	public function test_the_lock_of_an_application_is_the_lock_of_its_procedure() {
		$solicitud = $this->application( $this->procedimiento, $this->school_head( 'C0001' ) );

		$this->abrir( $this->primera );

		$this->acting_as( $this->segunda );
		$this->assertSame( $this->primera, EditLock::owner( $solicitud ) );
		$this->assertSame( $this->procedimiento, EditLock::status( $solicitud, true )['procedure_id'] );

		// Y soltar por la solicitud suelta el del procedimiento, que es el que hay.
		$this->acting_as( $this->primera );
		EditLock::release( $solicitud );
		$this->assertSame( '', (string) get_post_meta( $this->procedimiento, '_edit_lock', true ) );
	}

	// ─── tomar posesión ────────────────────────────────────────────────────

	/**
	 * «Tomar posesión» se lo quita a la primera persona, y no guarda nada más.
	 */
	public function test_taking_over_moves_the_lock_and_writes_nothing_else() {
		$this->abrir( $this->primera );
		$this->tomar_posesion( $this->segunda );

		$this->assertNotNull( $this->exit_url( array( EditLock::class, 'handle' ) ), 'vuelve a la pantalla desde la que se pidió' );
		$this->assertSame( $this->segunda, $this->duenio() );
		$this->assertSame( 'Red de centros original', get_the_title( $this->procedimiento ) );
	}

	/**
	 * Sin su nonce no se toma nada: por POST y firmado, como toda mutación.
	 *
	 * Tomarle el procedimiento a otra persona por un enlace pegado en un
	 * correo no pasa.
	 */
	public function test_taking_over_needs_its_nonce() {
		$this->abrir( $this->primera );
		$this->tomar_posesion( $this->segunda, 'inventado' );

		$this->assertNull( $this->exit_url( array( EditLock::class, 'handle' ) ), 'ni redirige ni hace nada' );
		$this->assertSame( $this->primera, $this->duenio() );
	}

	/**
	 * Y el nonce no es un permiso: quien no edita este procedimiento no se lo queda.
	 */
	public function test_taking_over_needs_permission_over_the_procedure() {
		$this->abrir( $this->primera );
		$ajena = $this->manager( array( $this->area( 'Otro ámbito' ) ) );

		$this->tomar_posesion( $ajena );
		$this->murio( array( EditLock::class, 'handle' ) );

		$this->assertSame( $this->primera, $this->duenio() );
	}

	/**
	 * Un POST que no es el nuestro se deja pasar sin tocar nada.
	 */
	public function test_another_post_is_left_alone() {
		$this->abrir( $this->primera );

		$this->acting_as( $this->segunda );
		$this->post( array( EditLock::FIELD_DO => 'otra-cosa' ) );

		$this->assertNull( $this->exit_url( array( EditLock::class, 'handle' ) ) );
		$this->assertSame( $this->primera, $this->duenio() );
	}

	// ─── soltar solo lo propio ─────────────────────────────────────────────

	/**
	 * Al soltar se borra SOLO el bloqueo propio: si otra persona ya tomó
	 * posesión, no se le quita.
	 */
	public function test_releasing_never_removes_someone_elses_lock() {
		$this->abrir( $this->primera );
		$this->tomar_posesion( $this->segunda );
		$this->exit_url( array( EditLock::class, 'handle' ) );
		$this->assertSame( $this->segunda, $this->duenio() );

		// La primera persona suelta lo que ya no es suyo: no pasa nada.
		$this->acting_as( $this->primera );
		EditLock::release( $this->procedimiento );
		$this->assertSame( $this->segunda, $this->duenio(), 'el bloqueo de la otra persona sigue en pie' );

		// Y el suyo sí se lo lleva.
		$this->acting_as( $this->segunda );
		EditLock::release( $this->procedimiento );
		$this->assertSame( '', (string) get_post_meta( $this->procedimiento, '_edit_lock', true ) );
		$this->assertSame( 0, EditLock::owner( $this->procedimiento ) );
	}

	/**
	 * Marcarlo como histórico suelta el bloqueo: quien lo marcó ya no lo edita,
	 * y administración no tiene por qué esperar a que caduque para corregirlo.
	 */
	public function test_marking_it_as_historic_releases_the_lock() {
		$this->abrir( $this->primera );
		$this->assertSame( $this->primera, $this->duenio() );

		$this->enviar( $this->primera, ProcedureEditor::OP_ARCHIVE );

		$this->assertTrue( (bool) get_post_meta( $this->procedimiento, ProcedureMetaKeys::ARCHIVED, true ) );
		$this->assertSame( '', (string) get_post_meta( $this->procedimiento, '_edit_lock', true ) );
	}

	/**
	 * El arranque cuelga el manejador de `init`, después de los tipos y sus
	 * capacidades y antes de que se pinte nada.
	 */
	public function test_register_hooks_the_takeover_handler() {
		EditLock::register();

		$this->assertSame( 20, has_action( 'init', array( EditLock::class, 'handle' ) ) );
		$this->assertSame( 'prc_lock_takeover_7', EditLock::nonce_action( 7 ) );
	}
}
