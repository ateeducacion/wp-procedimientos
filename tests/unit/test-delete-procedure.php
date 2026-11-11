<?php
/**
 * Tests for deleting a procedure from its workshop: the rule, the menu and the POST.
 *
 * @package Prc
 */

use Prc\Access\ProcedureDeletion;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PublicFront\ProcedureDelete;
use Prc\PublicFront\ProcedureEditor;
use Prc\PublicFront\Shell;
use Prc\PublicFront\View\ProcedureEditorView;
use Prc\PublicFront\Workspace;

/**
 * Borrar un procedimiento es enviarlo a la papelera (ADR-0007), y solo un
 * borrador sin ninguna solicitud —tampoco retirada— se borra desde el taller.
 *
 * Lo que se prueba es la base de datos después del envío: si el
 * procedimiento sigue donde estaba o está en la papelera, nunca borrado del
 * todo.
 */
class Test_Delete_Procedure extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Con el aplicativo, sus páginas, sin catálogo y sin rechazo heredado.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
		$this->pages();
		remove_all_filters( 'prc_centres' );
		$this->reset_rejected();
	}

	/**
	 * Que el motivo de un envío rechazado no pase al siguiente.
	 *
	 * @return void
	 */
	private function reset_rejected(): void {
		\Closure::bind(
			static function (): void {
				self::$rejected = '';
			},
			null,
			ProcedureEditor::class
		)();
	}

	/**
	 * A manager and one draft of their ámbito.
	 *
	 * @param array<string, mixed> $args Post fields to override.
	 * @param array<string, mixed> $meta Meta to add.
	 * @return array{uid:int, id:int, area:int}
	 */
	private function borrador( array $args = array(), array $meta = array() ): array {
		$area = $this->area( 'Área de prueba' );
		$uid  = $this->manager( array( $area ) );
		$id   = $this->procedure(
			$uid,
			array( $area ),
			$meta,
			array_merge(
				array(
					'post_status' => 'draft',
					'post_title'  => 'Red de prueba',
				),
				$args
			)
		);
		return array(
			'uid'  => $uid,
			'id'   => $id,
			'area' => $area,
		);
	}

	/**
	 * Send the delete POST and return where it left.
	 *
	 * @param int         $uid          Who submits.
	 * @param int         $procedure_id Procedure.
	 * @param string      $palabra      What was typed in the confirmation box.
	 * @param int|null    $nonce_de     Procedure the nonce was made for; null for this one.
	 * @param string|null $nonce        A forged nonce, if any.
	 * @return string|null
	 */
	private function borrar( int $uid, int $procedure_id, string $palabra = 'BORRAR', ?int $nonce_de = null, ?string $nonce = null ): ?string {
		$this->reset_rejected();
		$this->acting_as( $uid );
		$op     = ProcedureEditor::OP_DELETE;
		$campos = array(
			ProcedureEditor::FIELD_DO        => $op,
			ProcedureEditor::FIELD_PROCEDURE => (string) $procedure_id,
			ProcedureEditor::FIELD_CONFIRM   => $palabra,
		);
		if ( null !== $nonce ) {
			$campos[ ProcedureEditor::nonce_name( $op, $procedure_id ) ] = $nonce;
			$this->post( $campos );
		} else {
			$this->post(
				$campos,
				ProcedureEditor::nonce_action( $op, null === $nonce_de ? $procedure_id : $nonce_de ),
				ProcedureEditor::nonce_name( $op, $procedure_id )
			);
		}
		return $this->exit_url( array( ProcedureEditor::class, 'handle' ) );
	}

	/**
	 * The workshop model, as somebody, with these query args.
	 *
	 * @param int                  $uid  Who looks.
	 * @param int                  $id   Procedure.
	 * @param array<string, mixed> $args Extra query args.
	 * @return array<string, mixed>
	 */
	private function modelo( int $uid, int $id, array $args = array() ): array {
		$this->acting_as( $uid );
		$_GET = array_merge( array( ProcedureEditor::ARG_PROCEDURE => (string) $id ), $args );
		return ProcedureEditor::model();
	}

	/**
	 * The rejection the last submit left, as the model sees it.
	 *
	 * @param int $uid Who looks.
	 * @param int $id  Procedure.
	 * @return string
	 */
	private function motivo( int $uid, int $id ): string {
		$this->acting_as( $uid );
		$_GET = array( ProcedureEditor::ARG_PROCEDURE => (string) $id );
		$m    = ProcedureEditor::model();
		$this->assertSame( 'error', $m['flash']['tipo'] );
		return (string) $m['flash']['texto'];
	}

	// ─── la regla ──────────────────────────────────────────────────────────

	/**
	 * Un borrador sin solicitudes se borra; con una, aunque esté retirada, no.
	 */
	public function test_only_a_draft_without_any_application_is_deleted() {
		$c = $this->borrador();
		$this->assertTrue( ProcedureDeletion::can_delete_procedure( $c['uid'], $c['id'] ) );
		$this->assertSame( '', ProcedureDeletion::why_not_delete_procedure( $c['uid'], $c['id'] ) );

		$solicitud = $this->application( $c['id'], $this->school_head( 'C0001' ) );
		$this->assertFalse( ProcedureDeletion::can_delete_procedure( $c['uid'], $c['id'] ) );
		$this->assertStringContainsString( 'solicitudes', ProcedureDeletion::why_not_delete_procedure( $c['uid'], $c['id'] ) );

		// Publicado y devuelto a borrador con una solicitud retirada: tampoco.
		wp_trash_post( $solicitud );
		$this->assertSame( 'trash', get_post_status( $solicitud ) );
		$this->assertFalse( ProcedureDeletion::can_delete_procedure( $c['uid'], $c['id'] ), 'la retirada también cuenta' );
	}

	/**
	 * Publicado, programado, histórico o ya en la papelera: no.
	 */
	public function test_neither_published_nor_archived_nor_trashed_is_deleted() {
		$c = $this->borrador( array( 'post_status' => 'publish' ) );
		$this->assertFalse( ProcedureDeletion::can_delete_procedure( $c['uid'], $c['id'] ) );
		$this->assertStringContainsString( 'borrador', ProcedureDeletion::why_not_delete_procedure( $c['uid'], $c['id'] ) );

		$archivado = $this->procedure( $c['uid'], array( $c['area'] ), array( ProcedureMetaKeys::ARCHIVED => true ), array( 'post_status' => 'draft' ) );
		$this->assertFalse( ProcedureDeletion::can_delete_procedure( $c['uid'], $archivado ) );
		$this->assertFalse( ProcedureDeletion::can_delete_procedure( $this->administrator(), $archivado ), 'tampoco administración, desde el taller' );

		$tirado = $this->procedure( $c['uid'], array( $c['area'] ), array(), array( 'post_status' => 'draft' ) );
		wp_trash_post( $tirado );
		$this->assertFalse( ProcedureDeletion::can_delete_procedure( $c['uid'], $tirado ) );
	}

	/**
	 * Quien no lo edita no lo borra: otro ámbito, un centro, nadie.
	 */
	public function test_who_cannot_edit_it_does_not_delete_it() {
		$c     = $this->borrador();
		$ajena = $this->manager( array( $this->area( 'Otro ámbito' ) ) );

		$this->assertFalse( ProcedureDeletion::can_delete_procedure( $ajena, $c['id'] ) );
		$this->assertStringContainsString( 'otro ámbito', ProcedureDeletion::why_not_delete_procedure( $ajena, $c['id'] ) );
		$this->assertFalse( ProcedureDeletion::can_delete_procedure( $this->school_head( 'C0001' ), $c['id'] ) );
		$this->assertFalse( ProcedureDeletion::can_delete_procedure( 0, $c['id'] ) );
		$this->assertFalse( ProcedureDeletion::can_delete_procedure( $c['uid'], $this->application( $c['id'], $this->school_head( 'C0002' ) ) ), 'una solicitud no es un procedimiento' );
	}

	/**
	 * `delete_post` dice lo mismo para gestión, y administración conserva lo que podía.
	 */
	public function test_delete_post_follows_the_rule_for_management_only() {
		$c         = $this->borrador();
		$admin     = $this->administrator();
		$publicado = $this->procedure( $c['uid'], array( $c['area'] ), array(), array( 'post_status' => 'publish' ) );
		$archivado = $this->procedure( $c['uid'], array( $c['area'] ), array( ProcedureMetaKeys::ARCHIVED => true ), array( 'post_status' => 'draft' ) );
		$con       = $this->procedure( $c['uid'], array( $c['area'] ), array(), array( 'post_status' => 'draft' ) );
		$this->application( $con, $this->school_head( 'C0001' ) );
		$tirado = $this->procedure( $c['uid'], array( $c['area'] ), array(), array( 'post_status' => 'draft' ) );
		wp_trash_post( $tirado );

		$this->acting_as( $c['uid'] );
		$this->assertTrue( current_user_can( 'delete_post', $c['id'] ), 'borrador sin solicitudes' );
		$this->assertFalse( current_user_can( 'delete_post', $publicado ), 'publicado' );
		$this->assertFalse( current_user_can( 'delete_post', $archivado ), 'histórico' );
		$this->assertFalse( current_user_can( 'delete_post', $con ), 'con solicitudes' );
		$this->assertFalse( current_user_can( 'delete_post', $tirado ), 'en la papelera: ni restaurar ni borrar para siempre' );

		$this->acting_as( $admin );
		foreach ( array( $c['id'], $publicado, $archivado, $con, $tirado ) as $id ) {
			$this->assertTrue( current_user_can( 'delete_post', $id ), 'administración, como antes: ' . $id );
		}
	}

	/**
	 * Por la REST tampoco se envía a la papelera uno publicado.
	 */
	public function test_rest_does_not_trash_a_published_procedure_for_management() {
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init', $wp_rest_server );

		$c         = $this->borrador();
		$publicado = $this->procedure( $c['uid'], array( $c['area'] ), array(), array( 'post_status' => 'publish' ) );
		$this->acting_as( $c['uid'] );

		$respuesta = rest_do_request( new WP_REST_Request( 'DELETE', '/wp/v2/prc_procedure/' . $publicado ) );
		$this->assertSame( 403, $respuesta->get_status() );
		$this->assertSame( 'publish', get_post_status( $publicado ) );

		$respuesta = rest_do_request( new WP_REST_Request( 'DELETE', '/wp/v2/prc_procedure/' . $c['id'] ) );
		$this->assertSame( 200, $respuesta->get_status() );
		$this->assertSame( 'trash', get_post_status( $c['id'] ), 'un borrador sin solicitudes, a la papelera' );

		$wp_rest_server = null;
	}

	// ─── el envío ──────────────────────────────────────────────────────────

	/**
	 * Con BORRAR escrito —sin mirar mayúsculas ni espacios—, a la papelera y a «Gestión».
	 */
	public function test_the_right_word_trashes_it_and_goes_back_to_the_list() {
		$c   = $this->borrador();
		$url = $this->borrar( $c['uid'], $c['id'] );

		$this->assertIsString( $url );
		$this->assertSame( 'trash', get_post_status( $c['id'] ), 'a la papelera' );
		$this->assertInstanceOf( WP_Post::class, get_post( $c['id'] ), 'nunca borrado para siempre' );
		$this->assertStringStartsWith( Shell::url( 'workspace' ), (string) $url );
		$this->assertSame( ProcedureDelete::NOTICE, $this->query_arg( (string) $url, Workspace::VAR_NOTICE ) );
		$this->assertSame( (string) $c['id'], $this->query_arg( (string) $url, ProcedureDelete::ARG_DELETED ) );

		$this->acting_as( $c['uid'] );
		$_GET = wp_parse_args( (string) wp_parse_url( (string) $url, PHP_URL_QUERY ) );
		$m    = Workspace::model();
		$this->assertSame( 'ok', $m['notice']['type'] );
		$this->assertSame( 'Se ha enviado «Red de prueba» a la papelera.', $m['notice']['text'] );

		$otro = $this->procedure( $c['uid'], array( $c['area'] ), array(), array( 'post_status' => 'draft' ) );
		$this->assertIsString( $this->borrar( $c['uid'], $otro, '  borrar ' ) );
		$this->assertSame( 'trash', get_post_status( $otro ), 'sin distinguir mayúsculas ni espacios alrededor' );
	}

	/**
	 * Otra palabra o ninguna: no se toca nada, y se dice por qué.
	 */
	public function test_a_wrong_or_empty_word_touches_nothing() {
		$c = $this->borrador();

		foreach ( array( 'BORRA', '', 'B O R R A R', 'sí' ) as $palabra ) {
			$this->assertNull( $this->borrar( $c['uid'], $c['id'], $palabra ), 'con «' . $palabra . '»' );
			$this->assertSame( 'draft', get_post_status( $c['id'] ) );
			$this->assertStringContainsString( 'BORRAR', $this->motivo( $c['uid'], $c['id'] ) );
		}
	}

	/**
	 * El nonce es de cada procedimiento: el de otro, uno falso o ninguno no valen.
	 */
	public function test_the_nonce_of_another_procedure_does_not_delete() {
		$c    = $this->borrador();
		$otro = $this->procedure( $c['uid'], array( $c['area'] ), array(), array( 'post_status' => 'draft' ) );

		$this->assertNull( $this->borrar( $c['uid'], $c['id'], 'BORRAR', $otro ) );
		$this->assertNull( $this->borrar( $c['uid'], $c['id'], 'BORRAR', null, 'basura' ) );
		$this->assertNull( $this->borrar( $c['uid'], $c['id'], 'BORRAR', null, '' ) );
		$this->assertSame( 'draft', get_post_status( $c['id'] ) );
		$this->assertSame( 'draft', get_post_status( $otro ) );
	}

	/**
	 * Otra cuenta sin permiso, con su propio nonce bueno: no.
	 */
	public function test_another_account_without_permission_does_not_delete() {
		$c     = $this->borrador();
		$ajena = $this->manager( array( $this->area( 'Otro ámbito' ) ) );

		$this->assertNull( $this->borrar( $ajena, $c['id'] ) );
		$this->assertNull( $this->borrar( $this->school_head( 'C0001' ), $c['id'] ) );
		$this->assertSame( 'draft', get_post_status( $c['id'] ) );
	}

	/**
	 * Publicado o histórico: el envío armado a mano se rechaza con el motivo.
	 */
	public function test_a_published_or_archived_procedure_is_not_deleted_by_post() {
		$c         = $this->borrador( array( 'post_status' => 'publish' ) );
		$archivado = $this->procedure( $c['uid'], array( $c['area'] ), array( ProcedureMetaKeys::ARCHIVED => true ), array( 'post_status' => 'draft' ) );

		$this->assertNull( $this->borrar( $c['uid'], $c['id'] ) );
		$this->assertSame( 'publish', get_post_status( $c['id'] ) );
		$this->assertStringContainsString( 'borrador', $this->motivo( $c['uid'], $c['id'] ) );

		$this->assertNull( $this->borrar( $c['uid'], $archivado ) );
		$this->assertSame( 'draft', get_post_status( $archivado ) );
		$this->assertNull( $this->borrar( $this->administrator(), $archivado ) );
		$this->assertSame( 'draft', get_post_status( $archivado ) );
	}

	/**
	 * Entre pintar la confirmación y enviarla llegó una solicitud: no se borra.
	 */
	public function test_an_application_that_arrives_meanwhile_stops_it() {
		$c = $this->borrador();
		$this->assertTrue( $this->modelo( $c['uid'], $c['id'], array( ProcedureEditor::ARG_DELETE => '1' ) )['confirm_delete'] );

		$this->application( $c['id'], $this->school_head( 'C0001' ) );
		$this->assertNull( $this->borrar( $c['uid'], $c['id'] ) );
		$this->assertSame( 'draft', get_post_status( $c['id'] ) );
		$this->assertStringContainsString( 'solicitudes', $this->motivo( $c['uid'], $c['id'] ) );
	}

	/**
	 * Borrar suelta el bloqueo de quien borra; el de otra persona para el envío.
	 */
	public function test_deleting_releases_the_lock_of_who_deletes() {
		$c = $this->borrador();
		$this->acting_as( $c['uid'] );
		wp_set_post_lock( $c['id'] );
		$this->assertTrue( metadata_exists( 'post', $c['id'], '_edit_lock' ) );

		$this->assertIsString( $this->borrar( $c['uid'], $c['id'] ) );
		$this->assertFalse( metadata_exists( 'post', $c['id'], '_edit_lock' ), 'el bloqueo se suelta' );

		$otro = $this->procedure( $c['uid'], array( $c['area'] ), array(), array( 'post_status' => 'draft' ) );
		$ella = $this->manager( array( $c['area'] ) );
		$this->acting_as( $ella );
		wp_set_post_lock( $otro );
		$this->assertFalse( $this->modelo( $c['uid'], $otro )['can_delete'], 'abierto por otra persona, no se ofrece' );
		try {
			$this->borrar( $c['uid'], $otro );
			$this->fail( 'el bloqueo ajeno para el envío' );
		} catch ( WPDieException $e ) {
			unset( $e );
		}
		$this->assertSame( 'draft', get_post_status( $otro ) );
	}

	// ─── el menú y la confirmación ────────────────────────────────────────

	/**
	 * En un borrador sin solicitudes, el menú lleva la vista previa, «Duplicar» y «Borrar».
	 */
	public function test_the_menu_of_a_draft_offers_preview_duplicate_and_delete() {
		$c = $this->borrador();
		$m = $this->modelo( $c['uid'], $c['id'] );

		$this->assertTrue( $m['can_delete'] );
		$this->assertFalse( $m['confirm_delete'] );
		$this->assertStringContainsString( 'preview', $m['view_url'], 'un borrador se mira en vista previa' );
		$this->assertSame( ProcedureEditor::url( $c['id'], '', array( ProcedureEditor::ARG_DELETE => 1 ) ), $m['delete_url'] );

		$html = ProcedureEditorView::html( $m );
		$this->assertMatchesRegularExpression( '#<details class="prc-mas" data-prc-menu>\s*<summary[^>]*>#', $html, 'sin JavaScript, un details que se abre y se cierra' );
		$this->assertStringContainsString( 'Más acciones para este procedimiento', $html );
		$this->assertStringContainsString( '>Vista previa</a>', $html );
		$this->assertStringContainsString( 'value="' . ProcedureEditor::OP_DUPLICATE . '"', $html );
		$this->assertMatchesRegularExpression( '/<form[^>]+data-prc-confirm="¿Duplicar «Red de prueba»\?[^"]*"[^>]+data-prc-confirm-ok="Duplicar" data-prc-once>/u', $html );
		$this->assertStringContainsString( 'href="' . esc_url( $m['delete_url'] ) . '"', $html );
		$this->assertStringContainsString( '>Borrar</a>', $html );
		$this->assertSame( 1, substr_count( $html, 'data-prc-menu' ), 'un solo menú' );
		$this->assertLessThan( strpos( $html, 'prc-tabs--taller' ), strpos( $html, 'data-prc-menu' ), 'en la cabecera' );
	}

	/**
	 * Publicado: «Ver la ficha» y «Duplicar», sin «Borrar» —ni apagado—.
	 */
	public function test_the_menu_of_a_published_procedure_has_no_delete() {
		$c = $this->borrador( array( 'post_status' => 'publish' ) );
		$m = $this->modelo( $c['uid'], $c['id'] );

		$this->assertFalse( $m['can_delete'] );
		$this->assertSame( (string) get_permalink( $c['id'] ), $m['view_url'] );
		$html = ProcedureEditorView::html( $m );
		$this->assertStringContainsString( '>Ver la ficha</a>', $html );
		$this->assertStringContainsString( 'value="' . ProcedureEditor::OP_DUPLICATE . '"', $html );
		$this->assertStringNotContainsString( '>Borrar<', $html );
		$this->assertStringNotContainsString( ProcedureEditor::ARG_DELETE . '=', $html );
	}

	/**
	 * Un borrador que tuvo una solicitud, ya retirada, no ofrece «Borrar».
	 */
	public function test_a_draft_with_a_withdrawn_application_has_no_delete() {
		$c = $this->borrador();
		wp_trash_post( $this->application( $c['id'], $this->school_head( 'C0001' ) ) );

		$m = $this->modelo( $c['uid'], $c['id'] );
		$this->assertFalse( $m['can_delete'] );
		$this->assertStringNotContainsString( '>Borrar<', ProcedureEditorView::html( $m ) );
	}

	/**
	 * Sin ninguna opción, no hay menú.
	 */
	public function test_without_any_option_there_is_no_menu() {
		$c                  = $this->borrador();
		$m                  = $this->modelo( $c['uid'], $c['id'] );
		$m['view_url']      = '';
		$m['can_duplicate'] = false;
		$m['can_delete']    = false;
		$this->assertStringNotContainsString( 'data-prc-menu', ProcedureEditorView::html( $m ) );
	}

	/**
	 * Con `borrar=1`, el taller pinta la confirmación: título, consecuencia y la palabra.
	 */
	public function test_the_confirmation_without_javascript() {
		$c = $this->borrador();
		$m = $this->modelo( $c['uid'], $c['id'], array( ProcedureEditor::ARG_DELETE => '1' ) );
		$this->assertTrue( $m['confirm_delete'] );

		$html = ProcedureEditorView::html( $m );
		$this->assertStringContainsString( '¿Borrar «Red de prueba»?', $html );
		$this->assertStringContainsString( 'Se envía a la papelera; quien administra el aplicativo puede recuperarlo.', $html );
		$this->assertMatchesRegularExpression( '#<input[^>]+name="' . ProcedureEditor::FIELD_CONFIRM . '"[^>]+required#', $html, 'el campo es obligatorio' );
		$this->assertStringContainsString( 'name="' . ProcedureEditor::nonce_name( ProcedureEditor::OP_DELETE, $c['id'] ) . '"', $html );
		$this->assertStringContainsString( 'value="' . ProcedureEditor::OP_DELETE . '"', $html );
		$this->assertStringContainsString( 'Enviar a la papelera', $html );
		$this->assertStringContainsString( 'href="' . esc_url( ProcedureEditor::url( $c['id'] ) ) . '"', $html, 'y «Cancelar» vuelve al taller' );
		$this->assertStringNotContainsString( 'prc-tabs--taller', $html, 'solo la confirmación, sin los paneles' );

		// Lo que no se puede borrar no pinta la confirmación, aunque se pida.
		$p = $this->borrador( array( 'post_status' => 'publish' ) );
		$m = $this->modelo( $p['uid'], $p['id'], array( ProcedureEditor::ARG_DELETE => '1' ) );
		$this->assertFalse( $m['confirm_delete'] );
		$this->assertStringNotContainsString( 'name="' . ProcedureEditor::FIELD_CONFIRM . '"', ProcedureEditorView::html( $m ) );
	}
}
