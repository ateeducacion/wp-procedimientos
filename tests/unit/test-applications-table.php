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
use Prc\PublicFront\ApplicationQuery;

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
	 * Pintar una página de 50 sobre 300 no cuesta más consultas que sobre 60.
	 */
	public function test_a_page_of_fifty_does_not_cost_queries_in_proportion_to_the_total() {
		global $wpdb;
		$medir   = function ( int $n ) use ( $wpdb ): int {
			list( , $proc ) = $this->mio();
			$autor          = $this->school_head( 'C0001' );
			for ( $i = 0; $i < $n; $i++ ) {
				$this->application( $proc, $autor, 0 === $i % 2 ? 'C0001' : 'C0002' );
			}
			wp_cache_flush();
			\Prc\Domain\CentreCatalog::flush();
			$antes = $wpdb->num_queries;
			$r     = $this->consulta( $proc, array( ApplicationFilter::ARG_ISLAND => 'Isla Uno' ) );
			\Prc\PublicFront\Applications::rows( $proc, $r['posts'] );
			$this->assertCount( 50, $r['posts'] );
			return $wpdb->num_queries - $antes;
		};
		$con_100 = $medir( 100 );
		$con_300 = $medir( 300 );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- la cifra se lee en la salida de la prueba.
		fwrite( STDERR, sprintf( "\n[rendimiento] página de 50: %d consultas sobre 100 solicitudes, %d sobre 300\n", $con_100, $con_300 ) );
		$this->assertLessThanOrEqual( $con_100 + 2, $con_300 );
		$this->assertLessThan( 30, $con_300 );
	}
}
