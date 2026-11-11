<?php
/**
 * Tests for the «Publicación» panel: the list before publishing, the
 * buttons with consequences and the «histórico» switch at the end.
 *
 * @package Prc
 */

use Prc\Meta\ProcedureMetaKeys;
use Prc\PublicFront\Assets;
use Prc\PublicFront\ProcedureEditor;
use Prc\PublicFront\View\ApplicationActionsView;
use Prc\PublicFront\View\ProcedureEditorView;
use Prc\Taxonomy\ProcedureTaxonomies;

/**
 * Lo que se ve en «Publicación»: antes de publicar, una lista con lo que
 * falta y lo que solo avisa; publicado, las acciones de más consecuencia con
 * aspecto de peligro y su consecuencia al lado, y el histórico al final y
 * solo cuando el procedimiento ya terminó.
 */
class Test_Publication_Panel extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Con el aplicativo y sus páginas.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
		$this->pages();
		remove_all_filters( 'prc_centres' );
	}

	/**
	 * Un día contado desde hoy.
	 *
	 * @param string $relative Offset for strtotime().
	 * @return string Y-m-d.
	 */
	private function dia( string $relative ): string {
		return gmdate( 'Y-m-d', strtotime( $relative ) );
	}

	/**
	 * Un ámbito, quien lo gestiona y un procedimiento suyo.
	 *
	 * @param array<string, mixed> $meta Meta of the procedure.
	 * @param array<string, mixed> $args Post fields.
	 * @return array{0:int, 1:int} Manager, procedure.
	 */
	private function mio( array $meta = array(), array $args = array() ): array {
		$area = $this->area( 'Área de prueba' );
		$uid  = $this->manager( array( $area ) );
		return array( $uid, $this->procedure( $uid, array( $area ), $meta, array_merge( array( 'post_title' => 'Red de prueba' ), $args ) ) );
	}

	/**
	 * La pestaña «Publicación», pintada.
	 *
	 * @param int $uid          Who looks.
	 * @param int $procedure_id Procedure.
	 * @return string
	 */
	private function panel( int $uid, int $procedure_id ): string {
		$this->acting_as( $uid );
		$_GET = array(
			ProcedureEditor::ARG_PROCEDURE => (string) $procedure_id,
			ProcedureEditor::ARG_PANEL     => ProcedureEditor::PANEL_PUBLISH,
		);
		return ProcedureEditorView::html( ProcedureEditor::model() );
	}

	/**
	 * Las tres variantes de botón: principal, secundaria y de peligro.
	 */
	public function test_the_button_variants() {
		$this->assertSame( 'prc-btn prc-btn-primary btn btn-primary', Assets::button_class( true ) );
		$this->assertSame( 'prc-btn prc-btn-sec btn btn-outline-secondary', Assets::button_class() );
		$this->assertSame( 'prc-btn prc-btn-sec btn btn-outline-secondary', Assets::button_class( false ) );
		$this->assertSame( 'prc-btn prc-btn-primary prc-btn-final btn btn-primary btn-lg', Assets::button_class( 'final' ), 'la acción final, más grande' );
		$this->assertSame( 'prc-btn prc-btn-borrar btn btn-outline-danger', Assets::button_class( 'danger' ) );
	}

	/**
	 * En borrador, «Antes de publicar» dice punto por punto qué está y qué
	 * falta; sin plazo no se ofrece publicar.
	 */
	public function test_the_draft_shows_the_checklist() {
		list( $uid, $id ) = $this->mio( array(), array( 'post_status' => 'draft' ) );
		wp_set_object_terms( $id, array( $this->course() ), ProcedureTaxonomies::COURSE );

		$html = $this->panel( $uid, $id );
		$this->assertStringContainsString( 'Antes de publicar', $html );
		$this->assertMatchesRegularExpression( '/prc-comprobacion--ok[^>]*>.*Hecho:.*Curso elegido\./sU', $html );
		$this->assertMatchesRegularExpression( '/prc-comprobacion--falta[^>]*>.*Falta:.*Falta el plazo de solicitud/sU', $html );
		$this->assertMatchesRegularExpression( '/prc-comprobacion--aviso[^>]*>.*Aviso:.*Sin enlace a la resolución/sU', $html );
		$this->assertStringContainsString( 'guia=gestionar', $html, 'enlaza a la ayuda' );
		$this->assertStringNotContainsString( 'Publicar el procedimiento', $html );

		update_post_meta( $id, ProcedureMetaKeys::OPENS_AT, $this->dia( '+10 days' ) );
		update_post_meta( $id, ProcedureMetaKeys::CLOSES_AT, $this->dia( '+20 days' ) );
		$html = $this->panel( $uid, $id );
		$this->assertStringNotContainsString( 'prc-comprobacion--falta', $html );
		$this->assertStringContainsString( 'Sin enlace a la resolución', $html, 'la resolución avisa aunque se pueda publicar' );
		$this->assertStringContainsString( 'Publicar el procedimiento', $html );
	}

	/**
	 * Publicado y vivo: sin lista, sin histórico, y «Cerrar solicitudes» y
	 * «Devolver a borrador» con aspecto de peligro y su consecuencia.
	 */
	public function test_a_live_procedure_has_danger_buttons_and_no_archive() {
		list( $uid, $id ) = $this->mio(
			array(
				ProcedureMetaKeys::OPENS_AT  => $this->dia( '-1 day' ),
				ProcedureMetaKeys::CLOSES_AT => $this->dia( '+10 days' ),
			)
		);

		$html    = $this->panel( $uid, $id );
		$peligro = preg_quote( Assets::button_class( 'danger' ), '/' );
		$this->assertStringNotContainsString( 'Antes de publicar', $html );
		$this->assertMatchesRegularExpression( '/class="' . $peligro . '" type="submit">\s*Cerrar solicitudes ahora/', $html );
		$this->assertMatchesRegularExpression( '/class="' . $peligro . '" type="submit">Devolver a borrador/', $html );
		$this->assertStringContainsString( 'Deja de verse en la portada', $html );
		$this->assertStringNotContainsString( 'Marcar como histórico</button>', $html );
		$this->assertStringNotContainsString( 'Dar el procedimiento por terminado', $html );
	}

	/**
	 * Cerrado: el histórico se ofrece, con aspecto de peligro, y lo último.
	 */
	public function test_a_closed_procedure_offers_the_archive_last() {
		list( $uid, $id ) = $this->mio(
			array(
				ProcedureMetaKeys::OPENS_AT  => $this->dia( '-20 days' ),
				ProcedureMetaKeys::CLOSES_AT => $this->dia( '-10 days' ),
			)
		);

		$html = $this->panel( $uid, $id );
		$this->assertStringContainsString( 'class="' . Assets::button_class( 'danger' ) . '">Marcar como histórico</button>', $html );
		$this->assertGreaterThan( strpos( $html, 'Devolver a borrador' ), strpos( $html, 'Dar el procedimiento por terminado' ), 'el histórico va al final' );
		$this->assertGreaterThan( strpos( $html, 'Etapas del procedimiento' ), strpos( $html, 'Dar el procedimiento por terminado' ) );
	}

	/**
	 * Retirar una solicitud también es una acción de peligro.
	 */
	public function test_withdrawing_an_application_looks_dangerous() {
		$html = ApplicationActionsView::withdraw(
			array(
				'show'         => true,
				'can'          => true,
				'rejected'     => '',
				'action'       => '',
				'nonce_action' => 'prc_withdraw',
				'application'  => 7,
			)
		);
		$this->assertStringContainsString( 'class="' . Assets::button_class( 'danger' ) . '" type="submit">Retirar la solicitud', $html );
	}
}
