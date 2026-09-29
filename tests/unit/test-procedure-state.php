<?php
/**
 * Tests for ProcedureState: the seven states, derived and in priority order.
 *
 * @package Prc
 */

use Prc\Domain\ProcedureState;
use Prc\Meta\ProcedureMetaKeys;

/**
 * El estado del procedimiento, que se calcula y no se guarda.
 *
 * Hoy es un desplegable que alguien tiene que acordarse de cambiar; aquí
 * sale de las fechas y los enlaces, así que lo que hay que probar bien son
 * el orden de prioridad y los bordes de cada plazo.
 */
class Test_Procedure_State extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * A procedure meta array with the given keys set.
	 *
	 * @param array<string, mixed> $meta Overrides.
	 * @return array<string, mixed>
	 */
	private function meta( array $meta = array() ): array {
		return array_merge(
			array(
				ProcedureMetaKeys::OPENS_AT        => '2026-10-01',
				ProcedureMetaKeys::CLOSES_AT       => '2026-10-15',
				ProcedureMetaKeys::AMEND_OPENS_AT  => '2026-10-20',
				ProcedureMetaKeys::AMEND_CLOSES_AT => '2026-10-25',
			),
			$meta
		);
	}

	/**
	 * Estado según el día, bordes incluidos.
	 */
	public function test_state_by_day_including_the_edges() {
		$casos = array(
			array( '2026-09-30', ProcedureMetaKeys::STATE_UPCOMING ),
			array( '2026-10-01', ProcedureMetaKeys::STATE_OPEN ),
			array( '2026-10-08', ProcedureMetaKeys::STATE_OPEN ),
			array( '2026-10-15', ProcedureMetaKeys::STATE_OPEN ),
			array( '2026-10-16', ProcedureMetaKeys::STATE_CLOSED ),
			array( '2026-10-19', ProcedureMetaKeys::STATE_CLOSED ),
			array( '2026-10-20', ProcedureMetaKeys::STATE_AMENDMENT ),
			array( '2026-10-25', ProcedureMetaKeys::STATE_AMENDMENT ),
			array( '2026-10-26', ProcedureMetaKeys::STATE_CLOSED ),
			array( '2027-01-01', ProcedureMetaKeys::STATE_CLOSED ),
		);

		foreach ( $casos as $caso ) {
			$this->assertSame( $caso[1], ProcedureState::of( $this->meta(), $caso[0], true ), $caso[0] );
		}
	}

	/**
	 * El orden de prioridad de la tabla: borrador, histórico, resuelto,
	 * subsanación, abierto, próximo, cerrado.
	 */
	public function test_the_priority_order_of_the_table() {
		$todo = $this->meta(
			array(
				ProcedureMetaKeys::FINAL_LIST_URL => 'https://example.org/definitivo.pdf',
				ProcedureMetaKeys::ARCHIVED       => true,
			)
		);

		// Sin publicar es borrador, diga lo que diga lo demás.
		$this->assertSame( ProcedureMetaKeys::STATE_DRAFT, ProcedureState::of( $todo, '2026-10-08', false ) );
		// Publicado y archivado: histórico, aunque esté resuelto.
		$this->assertSame( ProcedureMetaKeys::STATE_ARCHIVED, ProcedureState::of( $todo, '2026-10-08', true ) );
		// Sin la marca: resuelto, aunque el plazo esté abierto.
		unset( $todo[ ProcedureMetaKeys::ARCHIVED ] );
		$this->assertSame( ProcedureMetaKeys::STATE_RESOLVED, ProcedureState::of( $todo, '2026-10-08', true ) );
		// Un listado provisional no resuelve nada.
		$todo[ ProcedureMetaKeys::FINAL_LIST_URL ]       = '';
		$todo[ ProcedureMetaKeys::PROVISIONAL_LIST_URL ] = 'https://example.org/provisional.pdf';
		$this->assertSame( ProcedureMetaKeys::STATE_OPEN, ProcedureState::of( $todo, '2026-10-08', true ) );
		// Si la subsanación se solapa con el plazo, gana la subsanación.
		$todo[ ProcedureMetaKeys::AMEND_OPENS_AT ] = '2026-10-05';
		$this->assertSame( ProcedureMetaKeys::STATE_AMENDMENT, ProcedureState::of( $todo, '2026-10-08', true ) );
	}

	/**
	 * Sin fecha de cierre, el plazo dura el día de apertura; lo mismo con la
	 * subsanación.
	 */
	public function test_a_missing_closing_day_is_the_opening_day() {
		$meta = array(
			ProcedureMetaKeys::OPENS_AT       => '2026-10-01',
			ProcedureMetaKeys::AMEND_OPENS_AT => '2026-10-20',
		);

		$this->assertSame( ProcedureMetaKeys::STATE_OPEN, ProcedureState::of( $meta, '2026-10-01', true ) );
		$this->assertSame( ProcedureMetaKeys::STATE_CLOSED, ProcedureState::of( $meta, '2026-10-02', true ) );
		$this->assertSame( ProcedureMetaKeys::STATE_AMENDMENT, ProcedureState::of( $meta, '2026-10-20', true ) );
		$this->assertSame( ProcedureMetaKeys::STATE_CLOSED, ProcedureState::of( $meta, '2026-10-21', true ) );
	}

	/**
	 * Sin fechas se está preparando: próximo, y nadie puede solicitar.
	 */
	public function test_without_dates_it_is_upcoming() {
		$this->assertSame( ProcedureMetaKeys::STATE_UPCOMING, ProcedureState::of( array(), '2026-10-08', true ) );
		$this->assertSame( ProcedureMetaKeys::STATE_UPCOMING, ProcedureState::of( array( ProcedureMetaKeys::CLOSES_AT => '2026-10-15' ), '2026-10-08', true ) );
		// Solo la subsanación sin plazo de solicitud: se respeta igual.
		$solo_subsanacion = array(
			ProcedureMetaKeys::AMEND_OPENS_AT  => '2026-10-20',
			ProcedureMetaKeys::AMEND_CLOSES_AT => '2026-10-25',
		);
		$this->assertSame( ProcedureMetaKeys::STATE_AMENDMENT, ProcedureState::of( $solo_subsanacion, '2026-10-22', true ) );
		$this->assertSame( ProcedureMetaKeys::STATE_UPCOMING, ProcedureState::of( $solo_subsanacion, '2026-10-26', true ) );
	}

	/**
	 * Cambio de año, espacios sobrantes y valores que no son cadenas.
	 */
	public function test_it_survives_stray_values() {
		$meta = array(
			ProcedureMetaKeys::OPENS_AT  => ' 2026-12-31 ',
			ProcedureMetaKeys::CLOSES_AT => ' 2027-01-02 ',
			ProcedureMetaKeys::ARCHIVED  => '',
			ProcedureMetaKeys::QUESTIONS => array( 'no', 'importa' ),
		);
		$this->assertSame( ProcedureMetaKeys::STATE_OPEN, ProcedureState::of( $meta, ' 2027-01-01 ', true ) );
		$this->assertSame( ProcedureMetaKeys::STATE_CLOSED, ProcedureState::of( $meta, '2027-01-03', true ) );
		$this->assertSame( ProcedureMetaKeys::STATE_UPCOMING, ProcedureState::of( array( ProcedureMetaKeys::OPENS_AT => array( 'x' ) ), '2027-01-03', true ) );
	}

	// ─── lo que cada estado deja tocar ─────────────────────────────────────

	/**
	 * La tabla «Lo que cada estado permite» de la SDD, columna de quien gestiona.
	 */
	public function test_editable_fields_is_the_table_of_the_sdd() {
		$todo = array(
			ProcedureState::GROUP_DATA,
			ProcedureState::GROUP_DATES,
			ProcedureState::GROUP_LINKS,
			ProcedureState::GROUP_QUESTIONS,
		);

		$this->assertSame( $todo, ProcedureState::groups() );
		$this->assertSame( $todo, ProcedureState::editable_fields( ProcedureMetaKeys::STATE_DRAFT ) );
		$this->assertSame( $todo, ProcedureState::editable_fields( ProcedureMetaKeys::STATE_UPCOMING ) );
		$this->assertSame( $todo, ProcedureState::editable_fields( ProcedureMetaKeys::STATE_OPEN ) );
		$this->assertSame(
			array( ProcedureState::GROUP_DATES, ProcedureState::GROUP_LINKS ),
			ProcedureState::editable_fields( ProcedureMetaKeys::STATE_CLOSED )
		);
		$this->assertSame( array( ProcedureState::GROUP_LINKS ), ProcedureState::editable_fields( ProcedureMetaKeys::STATE_RESOLVED ) );
		$this->assertSame( array(), ProcedureState::editable_fields( ProcedureMetaKeys::STATE_AMENDMENT ), 'en subsanación se gestionan solicitudes, no el procedimiento' );
		$this->assertSame( array(), ProcedureState::editable_fields( ProcedureMetaKeys::STATE_ARCHIVED ) );
		$this->assertSame( array(), ProcedureState::editable_fields( 'convocatoria-abierta' ), 'un estado que no es nuestro no abre nada' );

		// Las preguntas son el motivo de todo esto: borrar una de un
		// procedimiento con solicitudes presentadas descoloca su tabla y su CSV.
		$cerrados = array(
			ProcedureMetaKeys::STATE_CLOSED,
			ProcedureMetaKeys::STATE_AMENDMENT,
			ProcedureMetaKeys::STATE_RESOLVED,
			ProcedureMetaKeys::STATE_ARCHIVED,
		);
		foreach ( $cerrados as $estado ) {
			$this->assertNotContains( ProcedureState::GROUP_QUESTIONS, ProcedureState::editable_fields( $estado ), $estado );
		}
	}

	/**
	 * Y por qué, con las mismas palabras lo cuente quien lo cuente.
	 */
	public function test_why_locked_only_explains_the_states_that_close_something() {
		$this->assertStringContainsString( 'plazo de solicitud está cerrado', ProcedureState::why_locked( ProcedureMetaKeys::STATE_CLOSED ) );
		$this->assertStringContainsString( 'subsanación', ProcedureState::why_locked( ProcedureMetaKeys::STATE_AMENDMENT ) );
		$this->assertStringContainsString( 'resuelto', ProcedureState::why_locked( ProcedureMetaKeys::STATE_RESOLVED ) );

		$this->assertSame( '', ProcedureState::why_locked( ProcedureMetaKeys::STATE_DRAFT ) );
		$this->assertSame( '', ProcedureState::why_locked( ProcedureMetaKeys::STATE_UPCOMING ) );
		$this->assertSame( '', ProcedureState::why_locked( ProcedureMetaKeys::STATE_OPEN ) );
		$this->assertSame( '', ProcedureState::why_locked( ProcedureMetaKeys::STATE_ARCHIVED ), 'histórico lo explica su propio aviso (ADR-0023), no el estado' );
	}

	/**
	 * Solo en abierto se presenta o edita la solicitud.
	 */
	public function test_only_open_accepts_applications() {
		foreach ( array_keys( ProcedureMetaKeys::states() ) as $estado ) {
			$this->assertSame( ProcedureMetaKeys::STATE_OPEN === $estado, ProcedureState::accepts_applications( $estado ), $estado );
		}
	}

	/**
	 * Las etiquetas son las siete del vocabulario, en castellano, y solo esas.
	 */
	public function test_labels_answer_only_for_our_own_states() {
		$this->assertSame( 'Borrador', ProcedureState::label( ProcedureMetaKeys::STATE_DRAFT ) );
		$this->assertSame( 'Histórico', ProcedureState::label( ProcedureMetaKeys::STATE_ARCHIVED ) );
		$this->assertSame( 'Resuelto', ProcedureState::label( ProcedureMetaKeys::STATE_RESOLVED ) );
		$this->assertSame( 'En subsanación', ProcedureState::label( ProcedureMetaKeys::STATE_AMENDMENT ) );
		$this->assertSame( 'Abierto', ProcedureState::label( ProcedureMetaKeys::STATE_OPEN ) );
		$this->assertSame( 'Próximo', ProcedureState::label( ProcedureMetaKeys::STATE_UPCOMING ) );
		$this->assertSame( 'Cerrado', ProcedureState::label( ProcedureMetaKeys::STATE_CLOSED ) );
		$this->assertSame( '', ProcedureState::label( 'convocatoria-abierta' ) );
		$this->assertSame( '', ProcedureState::label( '' ) );

		$this->assertSame(
			array( 'draft', 'archived', 'resolved', 'amendment', 'open', 'upcoming', 'closed' ),
			array_keys( ProcedureMetaKeys::states() )
		);
	}

	/**
	 * Y el envoltorio lee el post de verdad: metas y estado de publicación.
	 */
	public function test_of_post_reads_the_stored_procedure() {
		$procedimiento = $this->procedure(
			$this->administrator(),
			array(),
			array(
				ProcedureMetaKeys::OPENS_AT  => '2026-10-01',
				ProcedureMetaKeys::CLOSES_AT => '2026-10-15',
			)
		);

		$this->assertSame( ProcedureMetaKeys::STATE_OPEN, ProcedureState::of_post( $procedimiento, '2026-10-08' ) );
		$this->assertSame( ProcedureMetaKeys::STATE_CLOSED, ProcedureState::of_post( $procedimiento, '2026-11-08' ) );

		update_post_meta( $procedimiento, ProcedureMetaKeys::FINAL_LIST_URL, 'https://example.org/definitivo.pdf' );
		$this->assertSame( ProcedureMetaKeys::STATE_RESOLVED, ProcedureState::of_post( $procedimiento, '2026-10-08' ) );

		wp_update_post(
			array(
				'ID'          => $procedimiento,
				'post_status' => 'draft',
			)
		);
		$this->assertSame( ProcedureMetaKeys::STATE_DRAFT, ProcedureState::of_post( $procedimiento, '2026-10-08' ) );

		// Sin día de referencia, el día de referencia es hoy, en la hora del sitio.
		$this->assertSame( current_time( 'Y-m-d' ), ProcedureState::today() );
		$this->assertSame( ProcedureState::of_post( $procedimiento ), ProcedureState::of_post( $procedimiento, ProcedureState::today() ) );
	}
}
