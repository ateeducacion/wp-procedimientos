<?php
/**
 * Tests for the «Preguntas» panel: fields by type, three new rows, removing
 * a question and the notice on an answered one.
 *
 * @package Prc
 */

use Prc\Domain\ProcedureQuestions;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PublicFront\Applications;
use Prc\PublicFront\Assets;
use Prc\PublicFront\ProcedureEditor;
use Prc\PublicFront\View\ProcedureEditorView;

/**
 * La pestaña «Preguntas» se rellena sin errores: cada pregunta enseña solo
 * los campos de su tipo, se añaden tres de una vez, se quitan con una casilla
 * y avisa de que una ya contestada cambia el sentido de lo contestado.
 */
class Test_Questions_Panel extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Con el aplicativo, sus páginas y sin rechazos heredados.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
		$this->pages();
		remove_all_filters( 'prc_centres' );
		\Closure::bind(
			static function (): void {
				self::$rejected = '';
			},
			null,
			ProcedureEditor::class
		)();
	}

	/**
	 * Un ámbito, quien lo gestiona y un procedimiento abierto con preguntas.
	 *
	 * @param array<int, array<string, mixed>> $preguntas Questions.
	 * @return array{0:int, 1:int} Manager, procedure.
	 */
	private function mio( array $preguntas ): array {
		$area = $this->area( 'Área de prueba' );
		$uid  = $this->manager( array( $area ) );
		$id   = $this->procedure(
			$uid,
			array( $area ),
			array(
				ProcedureMetaKeys::OPENS_AT  => gmdate( 'Y-m-d', strtotime( '-1 day' ) ),
				ProcedureMetaKeys::CLOSES_AT => gmdate( 'Y-m-d', strtotime( '+10 days' ) ),
				ProcedureMetaKeys::QUESTIONS => ProcedureQuestions::sanitize( $preguntas ),
			)
		);
		return array( $uid, $id );
	}

	/**
	 * La pestaña, pintada.
	 *
	 * @param int $uid Who looks.
	 * @param int $id  Procedure.
	 * @return string
	 */
	private function panel( int $uid, int $id ): string {
		$this->acting_as( $uid );
		$_GET = array(
			ProcedureEditor::ARG_PROCEDURE => (string) $id,
			ProcedureEditor::ARG_PANEL     => ProcedureEditor::PANEL_QUESTIONS,
		);
		return ProcedureEditorView::html( ProcedureEditor::model() );
	}

	/**
	 * Cada grupo de campos dice para qué tipos es, y el tipo lleva su clase:
	 * el CSS esconde con `:has()` lo que no toca, y sin `:has()` se ve todo.
	 */
	public function test_each_group_of_fields_says_which_types_it_is_for() {
		list( $uid, $id ) = $this->mio(
			array(
				array(
					'label' => 'Modalidad',
					'type'  => ProcedureQuestions::TYPE_TEXT,
				),
			)
		);
		$html             = $this->panel( $uid, $id );
		$this->assertStringContainsString( 'class="prc-control prc-pregunta__tipo"', $html );
		$this->assertMatchesRegularExpression( '/data-prc-q-for="single multiple"[^>]*>.*Opciones, una por línea/sU', $html );
		$this->assertMatchesRegularExpression( '/data-prc-q-for="number"[^>]*>.*Mínimo/sU', $html );
		$this->assertMatchesRegularExpression( '/data-prc-q-for="grid"[^>]*>.*Filas, una por línea/sU', $html );
		$this->assertMatchesRegularExpression( '/data-prc-q-for="file"[^>]*>.*Entrega con plazo propio/sU', $html );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- fichero del repositorio.
		$css = (string) file_get_contents( dirname( __DIR__, 2 ) . '/assets/css/prc-taller.css' );
		foreach ( array_keys( ProcedureQuestions::types() ) as $tipo ) {
			$this->assertStringContainsString( '.prc-pregunta:has(.prc-pregunta__tipo option[value="' . $tipo . '"]:checked) [data-prc-q-for]:not([data-prc-q-for~="' . $tipo . '"])', $css, $tipo );
		}
	}

	/**
	 * Tres preguntas nuevas de una vez: la primera a la vista y las otras
	 * dos en «Añadir otra pregunta». Nunca más de las que caben.
	 */
	public function test_three_new_rows_at_once() {
		list( $uid, $id ) = $this->mio(
			array(
				array(
					'label' => 'Modalidad',
					'type'  => ProcedureQuestions::TYPE_TEXT,
				),
			)
		);
		$html             = $this->panel( $uid, $id );
		foreach ( array( 1, 2, 3 ) as $i ) {
			$this->assertStringContainsString( 'name="' . ProcedureEditor::FIELD_Q . 'label[' . $i . ']"', $html );
		}
		$this->assertStringNotContainsString( 'name="' . ProcedureEditor::FIELD_Q . 'label[4]"', $html );
		$this->assertMatchesRegularExpression( '/<details class="prc-preguntas-mas">\s*<summary>Añadir otra pregunta<\/summary>.*label\[2\].*label\[3\].*<\/details>/sU', $html );
		$this->assertDoesNotMatchRegularExpression( '/<summary>Añadir otra pregunta<\/summary>.*label\[1\]/sU', $html, 'la primera, a la vista' );

		// Al guardar, las que vienen con rótulo se crean todas.
		$this->acting_as( $uid );
		$this->post(
			array(
				ProcedureEditor::FIELD_DO          => ProcedureEditor::PANEL_QUESTIONS,
				ProcedureEditor::FIELD_PROCEDURE   => (string) $id,
				ProcedureEditor::FIELD_Q . 'key'   => array( 'q1', '', '', '' ),
				ProcedureEditor::FIELD_Q . 'label' => array( 'Modalidad', 'Etapa', '', 'Curso' ),
				ProcedureEditor::FIELD_Q . 'type'  => array( 'text', 'text', 'text', 'text' ),
			),
			ProcedureEditor::nonce_action( ProcedureEditor::PANEL_QUESTIONS ),
			ProcedureEditor::nonce_name( ProcedureEditor::PANEL_QUESTIONS )
		);
		$this->assertNotNull( $this->exit_url( array( ProcedureEditor::class, 'handle' ) ) );
		$this->assertSame( array( 'Modalidad', 'Etapa', 'Curso' ), array_column( Applications::questions( $id ), 'label' ) );
	}

	/**
	 * «Quitar esta pregunta» la quita al guardar; lo ya contestado se queda.
	 */
	public function test_removing_a_question_with_its_checkbox() {
		list( $uid, $id ) = $this->mio(
			array(
				array(
					'label' => 'Modalidad',
					'type'  => ProcedureQuestions::TYPE_TEXT,
				),
				array(
					'label' => 'Etapa',
					'type'  => ProcedureQuestions::TYPE_TEXT,
				),
			)
		);
		$html             = $this->panel( $uid, $id );
		$this->assertStringContainsString( 'name="' . ProcedureEditor::FIELD_Q . 'remove[0]"', $html );
		$this->assertStringContainsString( 'Quitar esta pregunta', $html );
		$this->assertStringNotContainsString( 'name="' . ProcedureEditor::FIELD_Q . 'remove[2]"', $html, 'una pregunta nueva no se quita: se deja en blanco' );

		$this->acting_as( $uid );
		$this->post(
			array(
				ProcedureEditor::FIELD_DO           => ProcedureEditor::PANEL_QUESTIONS,
				ProcedureEditor::FIELD_PROCEDURE    => (string) $id,
				ProcedureEditor::FIELD_Q . 'key'    => array( 'q1', 'q2' ),
				ProcedureEditor::FIELD_Q . 'label'  => array( 'Modalidad', 'Etapa' ),
				ProcedureEditor::FIELD_Q . 'type'   => array( 'text', 'text' ),
				ProcedureEditor::FIELD_Q . 'remove' => array( 0 => '1' ),
			),
			ProcedureEditor::nonce_action( ProcedureEditor::PANEL_QUESTIONS ),
			ProcedureEditor::nonce_name( ProcedureEditor::PANEL_QUESTIONS )
		);
		$this->assertNotNull( $this->exit_url( array( ProcedureEditor::class, 'handle' ) ) );
		$this->assertSame( array( 'Etapa' ), array_column( Applications::questions( $id ), 'label' ) );
	}

	/**
	 * Una pregunta ya contestada dice cuántas solicitudes la contestaron,
	 * debajo del rótulo y enlazado a él.
	 */
	public function test_an_answered_question_says_how_many_answered_it() {
		list( $uid, $id ) = $this->mio(
			array(
				array(
					'label' => 'Modalidad',
					'type'  => ProcedureQuestions::TYPE_TEXT,
				),
				array(
					'label' => 'Etapa',
					'type'  => ProcedureQuestions::TYPE_TEXT,
				),
			)
		);
		$this->application( $id, $this->school_head( 'C0001' ), '', array( ApplicationMetaKeys::ANSWERS => array( 'q1' => 'A' ) ) );
		$this->application( $id, $this->school_head( 'C0002' ), '', array( ApplicationMetaKeys::ANSWERS => array( 'q1' => 'B' ) ) );
		$this->assertSame( array( 'q1' => 2 ), Applications::answer_counts( $id ) );

		$html = $this->panel( $uid, $id );
		$this->assertStringContainsString( 'Ya la han contestado 2 solicitudes. Corrija la redacción, no el sentido: si va a preguntar otra cosa, cree una pregunta nueva.', $html );
		$this->assertStringContainsString( 'aria-describedby="prc-q-l-aviso-0"', $html );
		$this->assertSame( 1, substr_count( $html, 'Ya la han contestado' ), 'la que nadie contestó no avisa' );
	}

	/**
	 * «Subir» y «Bajar» son secundarios y dicen qué pregunta mueven; la
	 * introducción recuerda «Duplicar».
	 */
	public function test_move_buttons_are_secondary_and_duplicate_is_suggested() {
		list( $uid, $id ) = $this->mio(
			array(
				array(
					'label' => 'Modalidad',
					'type'  => ProcedureQuestions::TYPE_TEXT,
				),
				array(
					'label' => 'Etapa',
					'type'  => ProcedureQuestions::TYPE_TEXT,
				),
			)
		);
		$html             = $this->panel( $uid, $id );
		$this->assertStringContainsString( 'class="' . Assets::button_class() . '" form="prc-q-mover"', $html );
		$this->assertStringNotContainsString( 'class="' . Assets::button_class( true ) . '" form="prc-q-mover"', $html );
		$this->assertStringContainsString( 'aria-label="Bajar la pregunta 1"', $html );
		$this->assertStringContainsString( '¿Las mismas preguntas que el curso pasado? Abra aquel procedimiento y pulse «Duplicar»', $html );
	}
}
