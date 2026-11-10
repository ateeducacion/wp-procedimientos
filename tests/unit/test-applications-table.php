<?php
/**
 * Tests for the applications table of the workshop: filters, pages, counts,
 * incomplete applications, bulk review and the export of the filter.
 *
 * @package Prc
 */

use Prc\Domain\ApplicationFilter;
use Prc\Domain\ProcedureQuestions;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ApplicationPostType;
use Prc\PublicFront\ApplicationExport;
use Prc\PublicFront\ApplicationQuery;
use Prc\PublicFront\ApplicationsPanel;
use Prc\PublicFront\PeopleSpecPanel;
use Prc\PublicFront\ProcedureEditor;
use Prc\PublicFront\View\ProcedureEditorView;

/**
 * La tabla de solicitudes del taller (PLAN-0003, PR 6).
 *
 * Todo el filtro viaja por GET; lo que se prueba aquí es qué solicitudes
 * salen, en qué orden y con cuántas consultas.
 */
class Test_Applications_Table extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Un catálogo inventado: dos islas y dos zonas.
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
						'code'   => 'C0001',
						'name'   => 'CEIP Pérez Norte',
						'island' => 'Isla Uno',
						'zone'   => 'Z0001',
					),
					array(
						'code'   => 'C0002',
						'name'   => 'IES Sur',
						'island' => 'Isla Dos',
						'zone'   => 'Z0002',
					),
					array(
						'code'   => 'C0003',
						'name'   => 'CEO Este',
						'island' => 'Isla Uno',
						'zone'   => 'Z0002',
					),
					array(
						'code'   => 'C0004',
						'name'   => 'CEIP Sin solicitud',
						'island' => 'Isla Tres',
						'zone'   => 'Z0003',
					),
					array(
						'code' => 'Z0001',
						'name' => 'Zona Inventada Uno',
					),
					array(
						'code' => 'Z0002',
						'name' => 'Zona Inventada Dos',
					),
				);
			}
		);
		\Prc\Domain\CentreCatalog::flush();
		\Closure::bind(
			static function (): void {
				self::$rejected = '';
			},
			null,
			ProcedureEditor::class
		)();
	}

	/**
	 * Un procedimiento de un ámbito, con quien lo gestiona.
	 *
	 * @param array<string, mixed> $meta Procedure meta.
	 * @return array{0:int, 1:int} Manager, procedure.
	 */
	private function mio( array $meta = array() ): array {
		$area = $this->area( 'Área de prueba' );
		$uid  = $this->manager( array( $area ) );
		return array( $uid, $this->procedure( $uid, array( $area ), $meta, array( 'post_title' => 'Red de prueba' ) ) );
	}

	/**
	 * Una solicitud con su centro, su nombre y su estado, de una fecha dada.
	 *
	 * @param int    $procedure Procedure ID.
	 * @param string $code      Centre code.
	 * @param string $name      Centre name.
	 * @param string $state     Review state.
	 * @param int    $minutes   Minutes after a fixed origin, to order them.
	 * @param array  $meta      Extra meta.
	 * @return int
	 */
	private function solicitud( int $procedure, string $code, string $name, string $state = ApplicationMetaKeys::REVIEW_SUBMITTED, int $minutes = 0, array $meta = array() ): int {
		$id = $this->application(
			$procedure,
			$this->school_head( $code ),
			$code,
			array(
				ApplicationMetaKeys::CENTRE_NAME  => $name,
				ApplicationMetaKeys::REVIEW_STATE => $state,
			) + $meta
		);
		if ( '' === $state ) {
			// Una de antes de la revisión no tiene la meta: el registro no deja guardarla vacía.
			delete_post_meta( $id, ApplicationMetaKeys::REVIEW_STATE );
		}
		wp_update_post(
			array(
				'ID'            => $id,
				'post_date'     => gmdate( 'Y-m-d H:i:s', strtotime( '2026-01-01 08:00:00' ) + 60 * $minutes ),
				'post_date_gmt' => gmdate( 'Y-m-d H:i:s', strtotime( '2026-01-01 08:00:00' ) + 60 * $minutes ),
			)
		);
		return $id;
	}

	/**
	 * Cuatro solicitudes de tres centros en dos islas y dos zonas.
	 *
	 * @return array{0:int, 1:int, 2:int[]} Manager, procedure, application IDs in date order.
	 */
	private function cuatro(): array {
		list( $uid, $proc ) = $this->mio();
		$ids                = array(
			$this->solicitud( $proc, 'C0001', 'CEIP Pérez Norte', ApplicationMetaKeys::REVIEW_SUBMITTED, 1 ),
			$this->solicitud( $proc, 'C0002', 'IES Sur', ApplicationMetaKeys::REVIEW_ADMITTED, 2 ),
			$this->solicitud( $proc, 'C0003', 'CEO Este 100% rural', ApplicationMetaKeys::REVIEW_AMEND, 3, array( ApplicationMetaKeys::REVIEW_NOTE => 'Falta algo.' ) ),
			// Una de antes de la revisión, sin estado: cuenta como presentada.
			$this->solicitud( $proc, 'C9999', 'Centro fuera del catálogo', '', 4 ),
		);
		return array( $uid, $proc, $ids );
	}

	/**
	 * Correr la consulta con lo que diría la URL.
	 *
	 * @param int                  $proc Procedure.
	 * @param array<string, mixed> $get  Query args.
	 * @param array<string, mixed> $scope Extra narrowing.
	 * @return array<string, mixed>
	 */
	private function consulta( int $proc, array $get = array(), array $scope = array() ): array {
		return ApplicationQuery::run( $proc, ApplicationFilter::sanitize( $get ), $scope );
	}

	// ─── la consulta ───────────────────────────────────────────────────────

	/**
	 * Sin filtro salen todas, por fecha, con los contadores por estado.
	 */
	public function test_without_filter_every_application_in_date_order() {
		list( , $proc, $ids ) = $this->cuatro();
		$this->solicitud( $this->procedure( $this->administrator() ), 'C0001', 'Otra', ApplicationMetaKeys::REVIEW_ADMITTED );

		$r = $this->consulta( $proc );
		$this->assertSame( $ids, $r['ids'] );
		$this->assertSame( $ids, array_map( 'intval', wp_list_pluck( $r['posts'], 'ID' ) ) );
		$this->assertSame( 4, $r['all'] );
		$this->assertSame( 4, $r['total'] );
		$this->assertFalse( $r['active'] );
		$this->assertSame(
			array(
				ApplicationMetaKeys::REVIEW_SUBMITTED => 2,
				ApplicationMetaKeys::REVIEW_AMEND     => 1,
				ApplicationMetaKeys::REVIEW_ADMITTED  => 1,
				ApplicationMetaKeys::REVIEW_EXCLUDED  => 0,
			),
			$r['counts']
		);
		$this->assertSame( array( 1, 1, 1, 4 ), array( $r['page'], $r['pages'], $r['from'], $r['to'] ) );
	}

	/**
	 * Cada filtro por separado, y los contadores siguen siendo del total.
	 */
	public function test_each_filter_alone() {
		list( , $proc, $ids ) = $this->cuatro();

		$r = $this->consulta( $proc, array( ApplicationFilter::ARG_REVIEW => ApplicationMetaKeys::REVIEW_SUBMITTED ) );
		$this->assertSame( array( $ids[0], $ids[3] ), $r['ids'], 'la de sin estado cuenta como presentada' );
		$this->assertSame( 2, $r['counts'][ ApplicationMetaKeys::REVIEW_SUBMITTED ] );
		$this->assertSame( 1, $r['counts'][ ApplicationMetaKeys::REVIEW_ADMITTED ], 'los contadores no se filtran' );
		$this->assertTrue( $r['active'] );

		$this->assertSame( array( $ids[0], $ids[2] ), $this->consulta( $proc, array( ApplicationFilter::ARG_ISLAND => 'Isla Uno' ) )['ids'] );
		$this->assertSame( array( $ids[1], $ids[2] ), $this->consulta( $proc, array( ApplicationFilter::ARG_ZONE => 'Z0002' ) )['ids'] );
		$this->assertSame( array( $ids[1] ), $this->consulta( $proc, array( ApplicationFilter::ARG_SEARCH => 'c0002' ) )['ids'] );
		$this->assertSame( array( $ids[0] ), $this->consulta( $proc, array( ApplicationFilter::ARG_SEARCH => 'perez' ) )['ids'], 'sin tilde encuentra la tilde' );
		$this->assertSame( array( $ids[0] ), $this->consulta( $proc, array( ApplicationFilter::ARG_SEARCH => 'PÉREZ' ) )['ids'] );
	}

	/**
	 * Las opciones de isla y zona son las de las solicitudes, no las del catálogo.
	 */
	public function test_options_are_those_of_the_applications() {
		list( , $proc ) = $this->cuatro();
		$r              = $this->consulta( $proc );
		$this->assertSame(
			array(
				'Isla Dos' => 'Isla Dos',
				'Isla Uno' => 'Isla Uno',
			),
			$r['options']['islands']
		);
		$this->assertSame(
			array(
				'Z0002' => 'Zona Inventada Dos',
				'Z0001' => 'Zona Inventada Uno',
			),
			$r['options']['zones']
		);
	}

	/**
	 * Una isla, una zona o un estado que no son de ninguna solicitud se ignoran.
	 */
	public function test_invalid_filter_values_are_ignored() {
		list( , $proc, $ids ) = $this->cuatro();
		$r                    = $this->consulta(
			$proc,
			array(
				ApplicationFilter::ARG_ISLAND => 'Isla Tres',
				ApplicationFilter::ARG_ZONE   => '<b>Z</b>',
				ApplicationFilter::ARG_REVIEW => 'borrada',
			)
		);
		$this->assertSame( $ids, $r['ids'] );
		$this->assertFalse( $r['active'] );
		$this->assertSame( '', $r['filter']['island'] );
	}

	/**
	 * Los filtros se suman.
	 */
	public function test_filters_combine() {
		list( , $proc, $ids ) = $this->cuatro();
		$this->assertSame(
			array( $ids[2] ),
			$this->consulta(
				$proc,
				array(
					ApplicationFilter::ARG_ISLAND => 'Isla Uno',
					ApplicationFilter::ARG_ZONE   => 'Z0002',
				)
			)['ids']
		);
		$this->assertSame(
			array(),
			$this->consulta(
				$proc,
				array(
					ApplicationFilter::ARG_ISLAND => 'Isla Uno',
					ApplicationFilter::ARG_REVIEW => ApplicationMetaKeys::REVIEW_ADMITTED,
				)
			)['ids']
		);
		$this->assertSame(
			array( $ids[0] ),
			$this->consulta(
				$proc,
				array(
					ApplicationFilter::ARG_ISLAND => 'Isla Uno',
					ApplicationFilter::ARG_REVIEW => ApplicationMetaKeys::REVIEW_SUBMITTED,
					ApplicationFilter::ARG_SEARCH => 'norte',
				)
			)['ids']
		);
	}

	/**
	 * Los comodines de SQL se buscan como letras.
	 */
	public function test_sql_wildcards_are_letters() {
		list( , $proc, $ids ) = $this->cuatro();
		$this->assertSame( array(), $this->consulta( $proc, array( ApplicationFilter::ARG_SEARCH => '%X' ) )['ids'] );
		$this->assertSame( array(), $this->consulta( $proc, array( ApplicationFilter::ARG_SEARCH => 'C_00' ) )['ids'] );
		$this->assertSame( array( $ids[2] ), $this->consulta( $proc, array( ApplicationFilter::ARG_SEARCH => '100%' ) )['ids'] );
	}

	/**
	 * En una de profesorado se busca por el nombre de la persona.
	 */
	public function test_a_teacher_application_is_found_by_the_name_of_the_person() {
		list( , $proc ) = $this->mio( array( ProcedureMetaKeys::AUDIENCE => ProcedureMetaKeys::AUDIENCE_TEACHERS ) );
		$ana            = (int) self::factory()->user->create(
			array(
				'role'         => 'prc_teacher',
				'display_name' => 'Ana Martín Inventada',
			)
		);
		$luis           = (int) self::factory()->user->create(
			array(
				'role'         => 'prc_teacher',
				'display_name' => 'Luis Otro',
			)
		);
		$de_ana         = $this->application( $proc, $ana, '', array( ApplicationMetaKeys::APPLICANT_TYPE => ApplicationMetaKeys::TYPE_TEACHER ) );
		$this->application( $proc, $luis, '', array( ApplicationMetaKeys::APPLICANT_TYPE => ApplicationMetaKeys::TYPE_TEACHER ) );
		$this->assertSame( array( $de_ana ), $this->consulta( $proc, array( ApplicationFilter::ARG_SEARCH => 'martin' ) )['ids'] );
	}

	/**
	 * De 50 en 50; una página fuera de rango cae en la última.
	 */
	public function test_pages_of_fifty_and_a_page_out_of_range() {
		list( , $proc ) = $this->mio();
		$autor          = $this->school_head( 'C0001' );
		$ids            = array();
		for ( $i = 0; $i < 55; $i++ ) {
			$ids[] = $this->application( $proc, $autor, sprintf( 'X%04d', $i ) );
		}
		$r = $this->consulta( $proc );
		$this->assertCount( 50, $r['posts'] );
		$this->assertSame( array( 1, 2, 1, 50 ), array( $r['page'], $r['pages'], $r['from'], $r['to'] ) );

		$r = $this->consulta( $proc, array( ApplicationFilter::ARG_PAGE => '2' ) );
		$this->assertSame( array_slice( $ids, 50 ), array_map( 'intval', wp_list_pluck( $r['posts'], 'ID' ) ) );
		$this->assertSame( array( 51, 55 ), array( $r['from'], $r['to'] ) );

		$r = $this->consulta( $proc, array( ApplicationFilter::ARG_PAGE => '99' ) );
		$this->assertSame( 2, $r['page'] );
		$this->assertSame( 2, $r['filter']['page'] );
		$this->assertCount( 5, $r['posts'] );
	}

	/**
	 * Incompletas: personas por debajo del mínimo, o una entrega obligatoria
	 * vencida sin documento; con sus motivos. Una excluida no cuenta.
	 */
	public function test_incomplete_applications_and_their_reasons() {
		$preguntas      = array(
			array(
				'key'       => 'q1',
				'label'     => 'Memoria final',
				'type'      => ProcedureQuestions::TYPE_FILE,
				'required'  => true,
				'delivery'  => true,
				'opens_at'  => gmdate( 'Y-m-d', strtotime( '-20 days' ) ),
				'closes_at' => gmdate( 'Y-m-d', strtotime( '-2 days' ) ),
			),
		);
		list( , $proc ) = $this->mio(
			array(
				ProcedureMetaKeys::QUESTIONS   => $preguntas,
				ProcedureMetaKeys::PEOPLE_SPEC => array(
					'enabled' => true,
					'roles'   => array(
						array(
							'key'   => 'r1',
							'label' => 'Docente',
						),
					),
					'min'     => 2,
				),
			)
		);
		$persona        = array(
			'id'      => 'aaaaaaaaaaa1',
			'name'    => 'Ana',
			'surname' => 'Inventada',
			'email'   => 'ana@example.org',
			'role'    => 'r1',
		);
		$doc            = array(
			'q1' => array(
				'id'     => str_repeat( 'b', 32 ),
				'stored' => 'bb/bb/' . str_repeat( 'b', 32 ) . '.pdf',
				'sha256' => str_repeat( 'c', 64 ),
				'name'   => 'memoria.pdf',
			),
		);
		$falta_todo     = $this->solicitud( $proc, 'C0001', 'Uno', ApplicationMetaKeys::REVIEW_SUBMITTED, 1, array( ApplicationMetaKeys::PEOPLE => array( $persona ) ) );
		$completa       = $this->solicitud(
			$proc,
			'C0002',
			'Dos',
			ApplicationMetaKeys::REVIEW_ADMITTED,
			2,
			array(
				ApplicationMetaKeys::PEOPLE => array( $persona, array( 'id' => 'aaaaaaaaaaa2' ) + $persona ),
				ApplicationMetaKeys::FILES  => $doc,
			)
		);
		$solo_doc       = $this->solicitud( $proc, 'C0003', 'Tres', ApplicationMetaKeys::REVIEW_SUBMITTED, 3, array( ApplicationMetaKeys::PEOPLE => array( $persona, array( 'id' => 'aaaaaaaaaaa2' ) + $persona ) ) );
		$this->solicitud( $proc, 'C9999', 'Excluida', ApplicationMetaKeys::REVIEW_EXCLUDED, 4 );

		$r = $this->consulta( $proc, array( ApplicationFilter::ARG_INCOMPLETE => '1' ) );
		$this->assertSame( array( $falta_todo, $solo_doc ), $r['ids'] );
		$this->assertSame( 2, $r['incomplete'] );
		$this->assertTrue( $r['may_be_incomplete'] );
		$this->assertSame( array( 'Falta 1 persona', 'Falta: Memoria final' ), $r['reasons'][ $falta_todo ] );
		$this->assertSame( array( 'Falta: Memoria final' ), $r['reasons'][ $solo_doc ] );
		$this->assertArrayNotHasKey( $completa, $r['reasons'] );

		// Sin lista ni entregas vencidas, ninguna puede estar incompleta.
		list( , $otro ) = $this->mio();
		$this->solicitud( $otro, 'C0001', 'Uno' );
		$r = $this->consulta( $otro, array( ApplicationFilter::ARG_INCOMPLETE => '1' ) );
		$this->assertFalse( $r['may_be_incomplete'] );
		$this->assertSame( array(), $r['ids'] );
	}

	/**
	 * El acotado por códigos de centro (el del PR 7) va antes que todo:
	 * contadores, opciones y filtro.
	 */
	public function test_a_scope_of_centre_codes_narrows_everything() {
		list( , $proc, $ids ) = $this->cuatro();
		$r                    = $this->consulta( $proc, array(), array( 'codes' => array( 'C0002', 'C0003' ) ) );
		$this->assertSame( array( $ids[1], $ids[2] ), $r['ids'] );
		$this->assertSame( 2, $r['all'] );
		$this->assertSame( 0, $r['counts'][ ApplicationMetaKeys::REVIEW_SUBMITTED ] );
		$this->assertSame( array( 'Isla Dos', 'Isla Uno' ), array_keys( $r['options']['islands'] ) );
		$this->assertSame( array(), $this->consulta( $proc, array(), array( 'codes' => array() ) )['ids'], 'un acotado vacío no deja ver nada' );
	}

	/**
	 * Pintar el panel entero —una página de 50, sus contadores, sus filtros,
	 * las personas de cada una y lo que cuenta el diálogo de exportar— sobre
	 * 300 solicitudes no cuesta más consultas que sobre 100.
	 */
	public function test_a_page_of_fifty_does_not_cost_queries_in_proportion_to_the_total() {
		global $wpdb;
		$medir = function ( int $n, array $get ) use ( $wpdb ): int {
			list( $uid, $proc ) = $this->mio(
				array(
					ProcedureMetaKeys::PEOPLE_SPEC => array(
						'enabled' => true,
						'roles'   => array(
							array(
								'key'   => 'r1',
								'label' => 'Docente',
							),
						),
						'min'     => 1,
					),
				)
			);
			for ( $i = 0; $i < $n; $i++ ) {
				$this->application( $proc, $this->school_head( 'C0001' ), 0 === $i % 2 ? 'C0001' : 'C0002' );
			}
			$this->acting_as( $uid );
			$_GET = array(
				ProcedureEditor::ARG_PROCEDURE => (string) $proc,
				ProcedureEditor::ARG_PANEL     => ProcedureEditor::PANEL_APPLICATIONS,
			) + $get;
			wp_cache_flush();
			\Prc\Domain\CentreCatalog::flush();
			$antes = $wpdb->num_queries;
			$html  = ProcedureEditorView::html( ProcedureEditor::model() );
			$this->assertSame( 50, substr_count( $html, 'data-prc-bulk-row' ) );
			return $wpdb->num_queries - $antes;
		};
		foreach ( array(
			'sin filtro'   => array(),
			'por isla'     => array( ApplicationFilter::ARG_ISLAND => 'Isla Uno' ),
			'por búsqueda' => array( ApplicationFilter::ARG_SEARCH => 'centro' ),
		) as $caso => $get ) {
			$con_100 = $medir( 100, $get );
			$con_300 = $medir( 300, $get );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- la cifra se lee en la salida de la prueba.
			fwrite( STDERR, sprintf( "\n[rendimiento] panel con una página de 50, %s: %d consultas sobre 100 solicitudes, %d sobre 300", $caso, $con_100, $con_300 ) );
			$this->assertLessThanOrEqual( $con_100 + 2, $con_300, $caso );
		}
	}

	// ─── el taller ─────────────────────────────────────────────────────────

	/**
	 * Mandar una operación del taller y devolver por dónde salió.
	 *
	 * @param int                  $uid   Who submits.
	 * @param string               $op    Operation.
	 * @param int                  $proc  Procedure.
	 * @param int                  $row   Application, for the single review.
	 * @param array<string, mixed> $extra Extra fields.
	 * @return string|null
	 */
	private function submit( int $uid, string $op, int $proc, int $row = 0, array $extra = array() ): ?string {
		$this->acting_as( $uid );
		$this->post(
			array_merge(
				array(
					ProcedureEditor::FIELD_DO        => $op,
					ProcedureEditor::FIELD_PROCEDURE => (string) $proc,
					ProcedureEditor::FIELD_ROW       => (string) $row,
				),
				$extra
			),
			ProcedureEditor::nonce_action( $op ),
			ProcedureEditor::nonce_name( $op, ProcedureEditor::OP_REVIEW === $op ? $row : 0 )
		);
		return $this->exit_url( array( ProcedureEditor::class, 'handle' ) );
	}

	/**
	 * El panel pintado, con lo que diga la URL.
	 *
	 * @param int                  $uid  Who looks.
	 * @param int                  $proc Procedure.
	 * @param array<string, mixed> $get  Query args.
	 * @return string
	 */
	private function panel( int $uid, int $proc, array $get = array() ): string {
		$this->acting_as( $uid );
		$_GET = array(
			ProcedureEditor::ARG_PROCEDURE => (string) $proc,
			ProcedureEditor::ARG_PANEL     => ProcedureEditor::PANEL_APPLICATIONS,
		) + $get;
		return ProcedureEditorView::html( ProcedureEditor::model() );
	}

	/**
	 * Un procedimiento con pocas solicitudes y sin filtro se ve como antes:
	 * todas, sin páginas y con el recuento de siempre.
	 */
	public function test_a_small_procedure_without_filter_looks_as_before() {
		list( $uid, $proc, $ids ) = $this->cuatro();
		$html                     = $this->panel( $uid, $proc );
		$this->assertStringContainsString( 'Mostrando 4 solicitudes.', $html );
		$this->assertStringNotContainsString( 'prc-paginacion', $html );
		foreach ( array( 'CEIP Pérez Norte', 'IES Sur', 'C9999', '>Centro<', '>Código<', '>Cargo<', '>Coordinación<', 'Exportar…' ) as $texto ) {
			$this->assertStringContainsString( $texto, $html );
		}
		foreach ( $ids as $id ) {
			$this->assertStringContainsString( 'name="' . ProcedureEditor::FIELD_ROW . '" value="' . $id . '"', $html );
		}
		$this->assertStringNotContainsString( 'Se exportarán', $html, 'sin filtro no hay nada que elegir al exportar' );
		$this->assertStringNotContainsString( 'Solo las incompletas', $html, 'sin lista ni entregas no hay incompletas que buscar' );
		$this->assertStringContainsString( 'prc-contadores', $html );
		$this->assertStringContainsString( 'Isla Uno', $html );
		// La revisión en bloque: casillas que apuntan a su formulario, y la confirmación de siempre.
		$this->assertStringContainsString( 'id="prc-bloque"', $html );
		$this->assertStringContainsString( 'data-prc-confirm="¿Cambiar la revisión de las solicitudes marcadas?', $html );
		$this->assertStringContainsString( 'data-prc-confirm-ok="Cambiar la revisión"', $html );
		$this->assertSame( 4, substr_count( $html, 'form="prc-bloque" name="' . ApplicationsPanel::FIELD_ROWS . '[]"' ) );
		$this->assertStringContainsString( 'name="' . ApplicationsPanel::FIELD_PAGE_ROWS . '" value="' . implode( ',', $ids ) . '"', $html );
	}

	/**
	 * Sin solicitudes, el panel vacío de siempre.
	 */
	public function test_without_applications_the_empty_panel() {
		list( $uid, $proc ) = $this->mio();
		$html               = $this->panel( $uid, $proc );
		$this->assertStringContainsString( 'Todavía no hay solicitudes', $html );
		$this->assertStringNotContainsString( 'prc-filtros', $html );
	}

	/**
	 * Lo tecleado en la búsqueda vuelve a la pantalla escapado.
	 */
	public function test_the_search_term_is_painted_escaped() {
		list( $uid, $proc ) = $this->cuatro();
		$malo               = '"><script>alert(1)</script>';
		$html               = $this->panel( $uid, $proc, array( ApplicationFilter::ARG_SEARCH => $malo ) );
		$this->assertStringNotContainsString( '<script>alert(1)</script>', $html );
		$this->assertStringContainsString( 'name="' . ApplicationFilter::ARG_SEARCH . '" value="&quot;&gt;"', $html, 'sanitize_text_field quita el guion entero y esc_attr escapa las comillas' );
		$this->assertStringContainsString( 'Ninguna solicitud cumple el filtro.', $html );

		$html = $this->panel( $uid, $proc, array( ApplicationFilter::ARG_SEARCH => 'a"b<c' ) );
		$this->assertStringContainsString( 'value="a&quot;b&lt;c"', $html );
	}

	/**
	 * Las páginas, los contadores y el historial conservan el filtro.
	 */
	public function test_links_keep_the_filter() {
		list( $uid, $proc ) = $this->mio();
		$autor              = $this->school_head( 'C0001' );
		for ( $i = 0; $i < 52; $i++ ) {
			$this->application( $proc, $autor, 'C0001', array( ApplicationMetaKeys::CENTRE_NAME => 'Norte & Sur' ) );
		}
		$this->acting_as( $uid );
		$m         = ApplicationsPanel::model(
			$proc,
			$uid,
			array(),
			array(
				ApplicationFilter::ARG_SEARCH => 'Norte & Sur',
				ApplicationFilter::ARG_ISLAND => 'Isla Uno',
			)
		);
		$siguiente = (string) $m['table']['pager']['next'];
		$this->assertSame( 'Norte & Sur', $this->query_arg( $siguiente, ApplicationFilter::ARG_SEARCH ), 'el & va codificado' );
		$this->assertSame( 'Isla Uno', $this->query_arg( $siguiente, ApplicationFilter::ARG_ISLAND ) );
		$this->assertSame( '2', $this->query_arg( $siguiente, ApplicationFilter::ARG_PAGE ) );
		$this->assertSame( (string) $proc, $this->query_arg( $siguiente, ProcedureEditor::ARG_PROCEDURE ) );

		$admitidas = (string) $m['table']['states'][3]['url'];
		$this->assertSame( ApplicationMetaKeys::REVIEW_ADMITTED, $this->query_arg( $admitidas, ApplicationFilter::ARG_REVIEW ) );
		$this->assertSame( 'Isla Uno', $this->query_arg( $admitidas, ApplicationFilter::ARG_ISLAND ) );
		$this->assertSame( '', $this->query_arg( $admitidas, ApplicationFilter::ARG_PAGE ), 'cambiar de estado vuelve a la primera página' );

		// El historial de una persona, con la lista encendida.
		update_post_meta(
			$proc,
			ProcedureMetaKeys::PEOPLE_SPEC,
			array(
				'enabled' => true,
				'roles'   => array(
					array(
						'key'   => 'r1',
						'label' => 'Docente',
					),
				),
			)
		);
		$m   = ApplicationsPanel::model( $proc, $uid, array(), array( ApplicationFilter::ARG_SEARCH => 'Norte & Sur' ) );
		$url = (string) reset( $m['people'] )['log_url'];
		$this->assertSame( 'Norte & Sur', $this->query_arg( $url, ApplicationFilter::ARG_SEARCH ) );
		$this->assertNotSame( '', $this->query_arg( $url, PeopleSpecPanel::ARG_HISTORY ) );
	}

	/**
	 * Revisar una vuelve a la misma vista filtrada y a la misma página.
	 */
	public function test_the_single_review_comes_back_to_the_same_view() {
		list( $uid, $proc, $ids ) = $this->cuatro();
		$url                      = $this->submit(
			$uid,
			ProcedureEditor::OP_REVIEW,
			$proc,
			$ids[0],
			array(
				ProcedureEditor::FIELD_STATE  => ApplicationMetaKeys::REVIEW_ADMITTED,
				ApplicationFilter::ARG_ISLAND => 'Isla Uno',
				ApplicationFilter::ARG_SEARCH => 'a b',
				ApplicationFilter::ARG_PAGE   => '3',
			)
		);
		$this->assertSame( 'revisada', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
		$this->assertSame( 'Isla Uno', $this->query_arg( (string) $url, ApplicationFilter::ARG_ISLAND ) );
		$this->assertSame( 'a b', $this->query_arg( (string) $url, ApplicationFilter::ARG_SEARCH ) );
		$this->assertSame( '3', $this->query_arg( (string) $url, ApplicationFilter::ARG_PAGE ) );
		$this->assertSame( ApplicationMetaKeys::REVIEW_ADMITTED, get_post_meta( $ids[0], ApplicationMetaKeys::REVIEW_STATE, true ) );
	}

	/**
	 * Revisar en bloque: cada una cambia por su cuenta, emite su acción y
	 * manda su correo; vuelve a la misma vista con las cifras.
	 */
	public function test_the_bulk_review_changes_each_and_mails_each() {
		list( $uid, $proc, $ids ) = $this->cuatro();
		$correos                  = array();
		add_filter(
			'prc_mail',
			static function ( $correo, string $evento ) use ( &$correos ) {
				$correos[] = $evento;
				return false;
			},
			10,
			2
		);
		$antes = did_action( 'prc_application_reviewed' );
		$url   = $this->submit(
			$uid,
			ProcedureEditor::OP_BULK_REVIEW,
			$proc,
			0,
			array(
				ApplicationsPanel::FIELD_ROWS => array( (string) $ids[0], (string) $ids[3], (string) $ids[0] ),
				ProcedureEditor::FIELD_STATE  => ApplicationMetaKeys::REVIEW_EXCLUDED,
				ProcedureEditor::FIELD_NOTE   => 'Fuera de plazo.',
				ApplicationFilter::ARG_REVIEW => ApplicationMetaKeys::REVIEW_SUBMITTED,
			)
		);
		$this->assertSame( 2, did_action( 'prc_application_reviewed' ) - $antes, 'una vez cada una, aunque venga repetida' );
		$this->assertSame( array( 'review_changed', 'review_changed' ), $correos );
		foreach ( array( $ids[0], $ids[3] ) as $id ) {
			$this->assertSame( ApplicationMetaKeys::REVIEW_EXCLUDED, get_post_meta( $id, ApplicationMetaKeys::REVIEW_STATE, true ) );
			$this->assertSame( 'Fuera de plazo.', get_post_meta( $id, ApplicationMetaKeys::REVIEW_NOTE, true ) );
		}
		$this->assertSame( ApplicationMetaKeys::REVIEW_ADMITTED, get_post_meta( $ids[1], ApplicationMetaKeys::REVIEW_STATE, true ) );
		$this->assertSame( ApplicationsPanel::NOTICE_BULK, $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
		$this->assertSame( '2', $this->query_arg( (string) $url, ApplicationsPanel::ARG_DONE ) );
		$this->assertSame( '0', $this->query_arg( (string) $url, ApplicationsPanel::ARG_FAILED ) );
		$this->assertSame( ApplicationMetaKeys::REVIEW_SUBMITTED, $this->query_arg( (string) $url, ApplicationFilter::ARG_REVIEW ) );

		$_GET = array(
			ProcedureEditor::ARG_NOTICE   => ApplicationsPanel::NOTICE_BULK,
			ApplicationsPanel::ARG_DONE   => '2',
			ApplicationsPanel::ARG_FAILED => '0',
		);
		$this->assertSame( 'Se ha cambiado la revisión de 2 solicitudes.', $this->panel_flash( $uid, $proc ) );
	}

	/**
	 * El aviso del bloque, leído del taller.
	 *
	 * @param int $uid  Who looks.
	 * @param int $proc Procedure.
	 * @return string
	 */
	private function panel_flash( int $uid, int $proc ): string {
		$this->acting_as( $uid );
		$_GET[ ProcedureEditor::ARG_PROCEDURE ] = (string) $proc;
		$_GET[ ProcedureEditor::ARG_PANEL ]     = ProcedureEditor::PANEL_APPLICATIONS;
		return (string) ProcedureEditor::model()['flash']['texto'];
	}

	/**
	 * «Marcar las de esta página» sin guion marca la página que se pintó.
	 */
	public function test_ticking_the_page_reviews_the_painted_page() {
		list( $uid, $proc, $ids ) = $this->cuatro();
		$this->submit(
			$uid,
			ProcedureEditor::OP_BULK_REVIEW,
			$proc,
			0,
			array(
				ApplicationsPanel::FIELD_PAGE      => '1',
				ApplicationsPanel::FIELD_PAGE_ROWS => $ids[1] . ',' . $ids[2],
				ProcedureEditor::FIELD_STATE       => ApplicationMetaKeys::REVIEW_SUBMITTED,
			)
		);
		$this->assertSame( ApplicationMetaKeys::REVIEW_SUBMITTED, get_post_meta( $ids[1], ApplicationMetaKeys::REVIEW_STATE, true ) );
		$this->assertSame( ApplicationMetaKeys::REVIEW_SUBMITTED, get_post_meta( $ids[2], ApplicationMetaKeys::REVIEW_STATE, true ) );
		// Sin la casilla, la lista de la página no cuenta.
		$this->submit(
			$uid,
			ProcedureEditor::OP_BULK_REVIEW,
			$proc,
			0,
			array(
				ApplicationsPanel::FIELD_PAGE_ROWS => (string) $ids[1],
				ProcedureEditor::FIELD_STATE       => ApplicationMetaKeys::REVIEW_ADMITTED,
			)
		);
		$this->assertSame( ApplicationMetaKeys::REVIEW_SUBMITTED, get_post_meta( $ids[1], ApplicationMetaKeys::REVIEW_STATE, true ) );
		$this->assertStringContainsString( 'Marque al menos una', $this->panel_flash( $uid, $proc ) );
	}

	/**
	 * Solo se tocan las de este procedimiento: las de otro, también de otro
	 * ámbito, se cuentan y no frenan a las demás.
	 */
	public function test_the_bulk_review_skips_applications_of_other_procedures() {
		list( $uid, $proc, $ids ) = $this->cuatro();
		$mismo_ambito             = $this->solicitud( $this->procedure( $uid, \Prc\Access\ProcedureAccess::post_areas( $proc ) ), 'C0002', 'Otro' );
		$otro_ambito              = $this->solicitud( $this->procedure( $this->administrator(), array( $this->area( 'Otro ámbito' ) ) ), 'C0003', 'Ajena' );
		$borrada                  = $this->solicitud( $proc, 'C0004', 'En la papelera' );
		wp_trash_post( $borrada );

		$url = $this->submit(
			$uid,
			ProcedureEditor::OP_BULK_REVIEW,
			$proc,
			0,
			array(
				ApplicationsPanel::FIELD_ROWS => array( (string) $ids[0], (string) $mismo_ambito, (string) $otro_ambito, (string) $borrada, (string) $proc, 'basura', '-3' ),
				ProcedureEditor::FIELD_STATE  => ApplicationMetaKeys::REVIEW_ADMITTED,
			)
		);
		$this->assertSame( '1', $this->query_arg( (string) $url, ApplicationsPanel::ARG_DONE ) );
		$this->assertSame( '4', $this->query_arg( (string) $url, ApplicationsPanel::ARG_FAILED ) );
		$this->assertSame( ApplicationMetaKeys::REVIEW_ADMITTED, get_post_meta( $ids[0], ApplicationMetaKeys::REVIEW_STATE, true ) );
		foreach ( array( $mismo_ambito, $otro_ambito, $borrada ) as $ajena ) {
			$this->assertSame( ApplicationMetaKeys::REVIEW_SUBMITTED, get_post_meta( $ajena, ApplicationMetaKeys::REVIEW_STATE, true ) );
		}
		$this->assertSame( '', get_post_meta( $proc, ApplicationMetaKeys::REVIEW_STATE, true ) );

		$_GET = array(
			ProcedureEditor::ARG_NOTICE   => ApplicationsPanel::NOTICE_BULK,
			ApplicationsPanel::ARG_DONE   => '1',
			ApplicationsPanel::ARG_FAILED => '4',
		);
		$this->assertSame( 'Se ha cambiado la revisión de 1 solicitud. 4 no se han cambiado: no son solicitudes de este procedimiento.', $this->panel_flash( $uid, $proc ) );
	}

	/**
	 * Lo que se rechaza entero no toca ninguna: sin nota donde hace falta,
	 * un estado que no existe, más del tope, o sin permiso.
	 */
	public function test_what_refuses_the_whole_bulk_review() {
		list( $uid, $proc, $ids ) = $this->cuatro();
		$todas                    = array_map( 'strval', $ids );
		$sin_cambios              = function () use ( $ids ): void {
			$this->assertSame(
				array( ApplicationMetaKeys::REVIEW_SUBMITTED, ApplicationMetaKeys::REVIEW_ADMITTED, ApplicationMetaKeys::REVIEW_AMEND, '' ),
				array_map(
					static function ( int $id ): string {
						return (string) get_post_meta( $id, ApplicationMetaKeys::REVIEW_STATE, true );
					},
					$ids
				)
			);
		};

		$antes = did_action( 'prc_application_reviewed' );
		$this->assertNull(
			$this->submit(
				$uid,
				ProcedureEditor::OP_BULK_REVIEW,
				$proc,
				0,
				array(
					ApplicationsPanel::FIELD_ROWS => $todas,
					ProcedureEditor::FIELD_STATE  => ApplicationMetaKeys::REVIEW_AMEND,
					ProcedureEditor::FIELD_NOTE   => '   ',
				)
			)
		);
		$sin_cambios();
		$this->assertStringContainsString( 'nota', $this->panel_flash( $uid, $proc ) );

		$this->assertNull(
			$this->submit(
				$uid,
				ProcedureEditor::OP_BULK_REVIEW,
				$proc,
				0,
				array(
					ApplicationsPanel::FIELD_ROWS => $todas,
					ProcedureEditor::FIELD_STATE  => 'borrada',
				)
			)
		);
		$sin_cambios();

		$muchas = array_map( 'strval', range( 1, ApplicationsPanel::BULK_MAX + 1 ) );
		$this->assertNull(
			$this->submit(
				$uid,
				ProcedureEditor::OP_BULK_REVIEW,
				$proc,
				0,
				array(
					ApplicationsPanel::FIELD_ROWS => array_merge( $todas, $muchas ),
					ProcedureEditor::FIELD_STATE  => ApplicationMetaKeys::REVIEW_ADMITTED,
				)
			)
		);
		$sin_cambios();
		$this->assertStringContainsString( 'Como mucho se cambian 200', $this->panel_flash( $uid, $proc ) );

		// Sin permiso de revisar, ni dirección de un centro ni quien gestiona otro ámbito.
		$this->assertNull(
			$this->submit(
				$this->school_head( 'C0001' ),
				ProcedureEditor::OP_BULK_REVIEW,
				$proc,
				0,
				array(
					ApplicationsPanel::FIELD_ROWS => $todas,
					ProcedureEditor::FIELD_STATE  => ApplicationMetaKeys::REVIEW_ADMITTED,
				)
			)
		);
		$this->assertNull(
			$this->submit(
				$this->manager( array( $this->area( 'Otro ámbito' ) ) ),
				ProcedureEditor::OP_BULK_REVIEW,
				$proc,
				0,
				array(
					ApplicationsPanel::FIELD_ROWS => $todas,
					ProcedureEditor::FIELD_STATE  => ApplicationMetaKeys::REVIEW_ADMITTED,
				)
			)
		);
		$sin_cambios();

		// Y con el nonce de la revisión de una, tampoco.
		$this->acting_as( $uid );
		$this->post(
			array(
				ProcedureEditor::FIELD_DO        => ProcedureEditor::OP_BULK_REVIEW,
				ProcedureEditor::FIELD_PROCEDURE => (string) $proc,
				ApplicationsPanel::FIELD_ROWS    => $todas,
				ProcedureEditor::FIELD_STATE     => ApplicationMetaKeys::REVIEW_ADMITTED,
			),
			ProcedureEditor::nonce_action( ProcedureEditor::OP_REVIEW ),
			ProcedureEditor::nonce_name( ProcedureEditor::OP_BULK_REVIEW )
		);
		$this->assertNull( $this->exit_url( array( ProcedureEditor::class, 'handle' ) ) );
		$sin_cambios();
		$this->assertSame( $antes, did_action( 'prc_application_reviewed' ) );
	}

	/**
	 * Exactamente el tope pasa.
	 */
	public function test_exactly_the_cap_goes_through() {
		list( $uid, $proc ) = $this->mio();
		$autor              = $this->school_head( 'C0001' );
		$ids                = array();
		for ( $i = 0; $i < ApplicationsPanel::BULK_MAX; $i++ ) {
			$ids[] = (string) $this->application( $proc, $autor, 'C0001' );
		}
		add_filter( 'prc_mail', '__return_false' );
		$url = $this->submit(
			$uid,
			ProcedureEditor::OP_BULK_REVIEW,
			$proc,
			0,
			array(
				ApplicationsPanel::FIELD_ROWS => $ids,
				ProcedureEditor::FIELD_STATE  => ApplicationMetaKeys::REVIEW_ADMITTED,
			)
		);
		$this->assertSame( (string) ApplicationsPanel::BULK_MAX, $this->query_arg( (string) $url, ApplicationsPanel::ARG_DONE ) );
	}

	/**
	 * La exportación respeta el filtro: el CSV y el fichero de usuarios.
	 */
	public function test_the_export_follows_the_filter() {
		list( $uid, $proc, $ids ) = $this->cuatro();
		foreach ( $ids as $i => $id ) {
			update_post_meta(
				$id,
				ApplicationMetaKeys::COORDINATOR,
				array(
					array(
						'name'    => 'Persona',
						'surname' => 'Número' . $i,
						'dni'     => '',
						'email'   => 'persona' . $i . '@example.org',
					),
				)
			);
		}
		$exportar  = function ( array $extra ) use ( $uid, $proc ): string {
			$this->acting_as( $uid );
			$this->post(
				array_merge(
					array(
						ProcedureEditor::FIELD_DO        => ProcedureEditor::OP_EXPORT,
						ProcedureEditor::FIELD_PROCEDURE => (string) $proc,
					),
					$extra
				),
				ProcedureEditor::nonce_action( ProcedureEditor::OP_EXPORT ),
				ProcedureEditor::nonce_name( ProcedureEditor::OP_EXPORT )
			);
			return $this->served( array( ProcedureEditor::class, 'handle' ) );
		};
		$admitidas = array( ApplicationFilter::ARG_REVIEW => ApplicationMetaKeys::REVIEW_ADMITTED );

		$csv = $exportar( $admitidas );
		$this->assertStringContainsString( 'IES Sur', $csv );
		$this->assertStringNotContainsString( 'CEIP Pérez Norte', $csv );
		$this->assertCount( 2, array_filter( explode( "\r\n", $csv ) ), 'cabecera y una fila' );

		$csv = $exportar( $admitidas + array( ApplicationsPanel::FIELD_EXPORT_SCOPE => ApplicationsPanel::SCOPE_ALL ) );
		$this->assertStringContainsString( 'CEIP Pérez Norte', $csv );
		$this->assertCount( 5, array_filter( explode( "\r\n", $csv ) ) );

		$usuarios = $exportar(
			$admitidas + array(
				ProcedureEditor::FIELD_EXPORT_FORMAT => ApplicationExport::FORMAT_MOODLE,
				ProcedureEditor::FIELD_EXPORT_WHO    => array( ApplicationExport::WHO_COORDINATORS ),
			)
		);
		$this->assertSame( ApplicationExport::MOODLE_HEADER . "\npersona1,Persona,Número1,persona1@example.org", $usuarios );

		// Un filtro que no deja ninguna exporta solo la cabecera.
		$csv = $exportar( array( ApplicationFilter::ARG_SEARCH => 'no existe' ) );
		$this->assertCount( 1, array_filter( explode( "\r\n", $csv ) ) );
	}

	/**
	 * El diálogo dice cuántas salen con el filtro y cuenta sobre ellas.
	 */
	public function test_the_export_dialog_counts_what_will_be_exported() {
		list( $uid, $proc, $ids ) = $this->cuatro();
		update_post_meta(
			$ids[1],
			ApplicationMetaKeys::COORDINATOR,
			array(
				array(
					'name'    => 'Sin',
					'surname' => 'Correo',
					'dni'     => '',
					'email'   => 'no-es-correo',
				),
			)
		);
		$html = $this->panel( $uid, $proc, array( ApplicationFilter::ARG_REVIEW => ApplicationMetaKeys::REVIEW_ADMITTED ) );
		$this->assertStringContainsString( 'Se exportará 1 solicitud (filtro activo).', $html );
		$this->assertStringContainsString( 'Las del filtro (1)', $html );
		$this->assertStringContainsString( 'Todas (4)', $html );
		$this->assertStringContainsString( 'name="' . ApplicationFilter::ARG_REVIEW . '" value="' . ApplicationMetaKeys::REVIEW_ADMITTED . '"', $html );
		$this->assertStringContainsString( 'Coordinación (0 personas. No saldrán: 1 sin correo válido)', $html );
		$this->assertStringContainsString( 'Quien presenta la solicitud (0 personas. No saldrán: 1 incompleta)', $html, 'una cuenta sin nombre ni apellidos, solo la del filtro' );
		$this->assertStringContainsString( 'Mostrando 1–1 de 1 solicitudes que cumplen el filtro, de 4 en total.', $html );
	}

	/**
	 * Quien no gestiona las solicitudes filtra y pasa de página, pero no revisa.
	 */
	public function test_read_only_still_filters_but_does_not_review() {
		list( $uid, $proc ) = $this->cuatro();
		update_post_meta( $proc, ProcedureMetaKeys::ARCHIVED, '1' );
		$html = $this->panel( $uid, $proc, array( ApplicationFilter::ARG_ISLAND => 'Isla Uno' ) );
		$this->assertStringContainsString( '<form class="prc-filtros"', $html );
		$doc = new DOMDocument();
		libxml_use_internal_errors( true );
		$doc->loadHTML( '<?xml encoding="utf-8"?>' . $html );
		libxml_clear_errors();
		$xp = new DOMXPath( $doc );
		$this->assertSame( 0, $xp->query( '//form[contains(@class,"prc-filtros")]/ancestor::fieldset[@disabled]' )->length, 'el filtro va fuera de lo apagado' );
		$this->assertSame( 1, $xp->query( '//form[@id="prc-bloque"]/ancestor::fieldset[@disabled]' )->length, 'el bloque va dentro' );
		$this->assertSame( 1, $xp->query( '//form[contains(@class,"prc-exportar__form")]/ancestor::fieldset[@disabled]' )->length, 'y la exportación también' );
		$this->assertGreaterThan( 0, $xp->query( '//input[@data-prc-bulk-row]/ancestor::fieldset[@disabled]' )->length );
	}
}
