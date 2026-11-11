<?php
/**
 * Tests for «mi centro»: the applications of the school of whoever is looking.
 *
 * @package Prc
 */

use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PublicFront\ApplyForm;
use Prc\PublicFront\Applications;
use Prc\PublicFront\MyCentre;
use Prc\PublicFront\People;
use Prc\PublicFront\PeopleForm;
use Prc\PublicFront\View\MyCentreView;

/**
 * Las solicitudes del centro de quien mira, y de nadie más.
 *
 * Hoy esto es una vista del gestor de formularios filtrada por un parámetro de
 * la URL, así que lo que se comprueba aquí es justo eso: que el filtro es el
 * código de centro **de la cuenta** (ADR-0016) y no algo que se pueda cambiar
 * desde fuera, y que cada fila lleva los dos estados que le importan al
 * centro: en qué anda el procedimiento y qué se ha dicho de su solicitud.
 */
class Test_My_Centre extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Con el aplicativo, sus páginas y un catálogo inventado.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
		$this->pages();
		$_GET = array();
		remove_all_filters( 'prc_centres' );
		add_filter( 'prc_centres', array( $this, 'catalogo' ) );
	}

	/**
	 * Dos centros de prueba.
	 *
	 * @return array<int, array<string, string>>
	 */
	public function catalogo(): array {
		return array(
			array(
				'code'      => 'C0001',
				'name'      => 'Centro de prueba Norte',
				'ownership' => ProcedureMetaKeys::OWNERSHIP_PUBLIC,
			),
			array(
				'code'      => 'C0002',
				'name'      => 'Centro de prueba Sur',
				'ownership' => ProcedureMetaKeys::OWNERSHIP_PUBLIC,
			),
		);
	}

	/**
	 * Un día relativo a hoy, en el huso del sitio.
	 *
	 * @param int $dias Days from today; negative for the past.
	 * @return string Y-m-d.
	 */
	private function dia( int $dias ): string {
		return gmdate( 'Y-m-d', (int) strtotime( ProcedureState::today() . ' ' . $dias . ' days' ) );
	}

	/**
	 * Un procedimiento publicado, con su plazo y su título.
	 *
	 * @param string               $titulo Post title.
	 * @param array<string, mixed> $meta   Meta key => value.
	 * @return int
	 */
	private function procedimiento( string $titulo, array $meta = array() ): int {
		return $this->procedure(
			$this->administrator(),
			array( $this->area() ),
			$meta,
			array( 'post_title' => $titulo )
		);
	}

	/**
	 * Un núcleo guardado, como lo deja ApplicationInput::validate() en `data`.
	 *
	 * @return array<string, mixed>
	 */
	private function nucleo(): array {
		return array(
			'position'    => ApplicationMetaKeys::POSITION_HEAD,
			'coordinator' => array(
				'name'  => '',
				'email' => '',
			),
			'answers'     => array(),
		);
	}

	// ─── quién mira ────────────────────────────────────────────────────────

	/**
	 * Sin sesión, sin capacidad o sin código de centro no se lista nada: se
	 * dice por qué y la tabla no llega ni a pintarse.
	 */
	public function test_my_centre_asks_for_a_session_a_capability_and_a_school() {
		$this->acting_as( 0 );
		$m = MyCentre::model();
		$this->assertStringContainsString( 'Debe iniciar sesión', $m['aviso'] );
		$this->assertSame( array(), $m['rows'] );
		$this->assertStringNotContainsString( '<table', MyCentreView::html( $m ) );

		$this->acting_as( $this->manager( array( $this->area() ) ) );
		$m = MyCentre::model();
		$this->assertStringContainsString( 'Su perfil no presenta solicitudes de centro', $m['aviso'] );
		$this->assertSame( array(), $m['rows'] );

		$this->acting_as( $this->school_head() );
		$m = MyCentre::model();
		$this->assertStringContainsString( 'no tiene centro asignado', $m['aviso'] );
		$this->assertSame( '', $m['centre']['code'] );
		$this->assertStringNotContainsString( '<table', MyCentreView::html( $m ) );
	}

	/**
	 * Un centro que todavía no ha presentado nada lo lee dicho, no en una
	 * tabla vacía.
	 */
	public function test_a_school_without_applications_is_told_where_they_are_submitted() {
		$this->acting_as( $this->school_head( 'C0001' ) );

		$m = MyCentre::model();
		$this->assertSame( array(), $m['rows'] );
		$this->assertSame( 'C0001', $m['centre']['code'] );

		$html = MyCentreView::html( $m );
		$this->assertStringContainsString( 'todavía no ha presentado ninguna solicitud', $html );
		$this->assertStringContainsString( 'Centro de prueba Norte', $html );
		$this->assertStringNotContainsString( '<table', $html );
	}

	// ─── lo que lista ──────────────────────────────────────────────────────

	/**
	 * Se listan las solicitudes del centro de quien mira, y las de otro centro
	 * no se ven aunque cuelguen del mismo procedimiento.
	 */
	public function test_it_lists_the_applications_of_the_viewers_school_and_of_nobody_else() {
		$una  = $this->procedimiento( 'Red de prueba' );
		$otra = $this->procedimiento( 'Programa de prueba' );

		$norte = $this->school_head( 'C0001' );
		$sur   = $this->school_head( 'C0002' );
		Applications::save( $una, $norte, $this->nucleo() );
		Applications::save( $otra, $norte, $this->nucleo() );
		Applications::save( $una, $sur, $this->nucleo() );

		$this->acting_as( $norte );
		$filas = MyCentre::model()['rows'];
		$this->assertCount( 2, $filas );
		$this->assertEqualsCanonicalizing(
			array( (string) $una, (string) $otra ),
			wp_list_pluck( $filas, 'procedure_id' )
		);
		$this->assertEqualsCanonicalizing(
			array( 'Red de prueba', 'Programa de prueba' ),
			wp_list_pluck( $filas, 'title' )
		);
		$this->assertSame( ApplyForm::url( $una ), $filas[ array_search( (string) $una, wp_list_pluck( $filas, 'procedure_id' ), true ) ]['url'] );

		$this->acting_as( $sur );
		$filas = MyCentre::model()['rows'];
		$this->assertCount( 1, $filas, 'el otro centro solo ve la suya' );
		$this->assertSame( (string) $una, $filas[0]['procedure_id'] );
	}

	/** Teachers only see their own individual applications and get clear labels. */
	public function test_a_teacher_sees_only_their_individual_applications() {
		$procedure = $this->procedimiento(
			'Formación de prueba',
			array( ProcedureMetaKeys::AUDIENCE => ProcedureMetaKeys::AUDIENCE_TEACHERS )
		);
		$teacher   = (int) self::factory()->user->create(
			array(
				'role'         => 'prc_teacher',
				'display_name' => 'Docente de prueba',
			)
		);
		$other     = (int) self::factory()->user->create( array( 'role' => 'prc_teacher' ) );
		$this->assertGreaterThan( 0, Applications::save_teacher( $procedure, $teacher, $this->nucleo() ) );
		$this->assertGreaterThan( 0, Applications::save_teacher( $procedure, $other, $this->nucleo() ) );

		$this->acting_as( $teacher );
		$model = MyCentre::model();
		$this->assertSame( ApplicationMetaKeys::TYPE_TEACHER, $model['applicant_type'] );
		$this->assertSame( 'Docente de prueba', $model['applicant'] );
		$this->assertCount( 1, $model['rows'] );
		$this->assertSame( 'Docente de prueba', $model['rows'][0]['applicant'] );
		$this->assertSame( ApplyForm::url( $procedure ), $model['rows'][0]['url'] );
		$html = MyCentreView::html( $model );
		$this->assertStringContainsString( 'Mis solicitudes de participación en procedimientos', $html );
		$this->assertStringContainsString( 'Solicitudes presentadas por Docente de prueba', $html );
		$this->assertStringNotContainsString( 'Coordinador/a', $html );
	}

	/**
	 * Cada fila lleva el estado del procedimiento y el de la revisión con su
	 * nota: es lo que el centro viene a mirar.
	 */
	public function test_each_row_carries_both_states_and_the_review_note() {
		$abierto = $this->procedimiento(
			'Red de prueba',
			array(
				ProcedureMetaKeys::OPENS_AT  => $this->dia( -1 ),
				ProcedureMetaKeys::CLOSES_AT => $this->dia( 1 ),
			)
		);
		$cerrado = $this->procedimiento(
			'Programa de prueba',
			array(
				ProcedureMetaKeys::OPENS_AT  => $this->dia( -10 ),
				ProcedureMetaKeys::CLOSES_AT => $this->dia( -5 ),
			)
		);

		$norte = $this->school_head( 'C0001' );
		Applications::save( $abierto, $norte, $this->nucleo() );
		$revisada = Applications::save( $cerrado, $norte, $this->nucleo() );
		Applications::review( $revisada, ApplicationMetaKeys::REVIEW_AMEND, 'Falta la memoria del centro.' );

		$this->acting_as( $norte );
		$filas = array();
		foreach ( MyCentre::model()['rows'] as $fila ) {
			$filas[ $fila['procedure_id'] ] = $fila;
		}

		$this->assertSame( ProcedureMetaKeys::STATE_OPEN, $filas[ (string) $abierto ]['state'] );
		$this->assertSame( 'Abierto', $filas[ (string) $abierto ]['state_label'] );
		$this->assertSame( ApplicationMetaKeys::REVIEW_SUBMITTED, $filas[ (string) $abierto ]['review'] );
		$this->assertSame( 'Presentada', $filas[ (string) $abierto ]['review_label'] );
		$this->assertSame( '', $filas[ (string) $abierto ]['note'] );

		$this->assertSame( ProcedureMetaKeys::STATE_CLOSED, $filas[ (string) $cerrado ]['state'] );
		$this->assertSame( 'Cerrado', $filas[ (string) $cerrado ]['state_label'] );
		$this->assertSame( ApplicationMetaKeys::REVIEW_AMEND, $filas[ (string) $cerrado ]['review'] );
		$this->assertSame( 'A subsanar', $filas[ (string) $cerrado ]['review_label'] );
		$this->assertSame( 'Falta la memoria del centro.', $filas[ (string) $cerrado ]['note'] );

		$html = MyCentreView::html( MyCentre::model() );
		$this->assertStringContainsString( '<table', $html );
		$this->assertStringContainsString( 'A subsanar', $html );
		$this->assertStringContainsString( 'Falta la memoria del centro.', $html );
		$this->assertStringContainsString( 'Procedimientos para Centro de prueba Norte (C0001)', $html );
	}

	// ─── cómo se lee ───────────────────────────────────────────────────────

	/**
	 * La tabla la ordena el servidor: la columna y el sentido viajan en la
	 * URL, contra una lista blanca, y se ven en `aria-sort` y en la flecha.
	 */
	public function test_the_table_is_sorted_by_the_server_against_a_whitelist() {
		$alfa  = $this->procedimiento( 'Alfa de prueba' );
		$zeta  = $this->procedimiento( 'Zeta de prueba' );
		$norte = $this->school_head( 'C0001' );
		Applications::save( $alfa, $norte, $this->nucleo() );
		Applications::save( $zeta, $norte, $this->nucleo() );
		$this->acting_as( $norte );

		$_GET['orden'] = 'procedimiento';
		$_GET['dir']   = 'asc';
		$m             = MyCentre::model();
		$this->assertSame( array( 'Alfa de prueba', 'Zeta de prueba' ), wp_list_pluck( $m['rows'], 'title' ) );
		$this->assertSame( 'ascending', $m['headers']['procedimiento']['sort'] );
		$this->assertSame( 'none', $m['headers']['fecha']['sort'] );
		$html = MyCentreView::html( $m );
		$this->assertStringContainsString( 'aria-sort="ascending"', $html );
		// El enlace de la columna activa da la vuelta al orden.
		$this->assertStringContainsString( 'dir=desc', $m['headers']['procedimiento']['url'] );
		$this->assertStringContainsString( 'dir=asc', $m['headers']['fecha']['url'] );

		$_GET['dir'] = 'desc';
		$this->assertSame( array( 'Zeta de prueba', 'Alfa de prueba' ), wp_list_pluck( MyCentre::model()['rows'], 'title' ) );

		// Una columna que no está en la lista blanca no ordena nada: se cae
		// al orden por omisión, que es la fecha de la más reciente abajo.
		$_GET['orden'] = 'nota"); DROP';
		$_GET['dir']   = 'ascendente';
		$this->assertSame(
			array(
				'col' => 'fecha',
				'dir' => 'desc',
			),
			MyCentre::sort()
		);
	}

	/**
	 * Lo que no tiene dato se marca con el «¿?» de siempre, y no con un hueco
	 * que no se sabe si es que falta o es que no toca.
	 */
	public function test_cells_without_data_keep_the_question_marks_of_the_original() {
		$uno   = $this->procedimiento( 'Red de prueba' );
		$norte = $this->school_head( 'C0001' );
		Applications::save( $uno, $norte, $this->nucleo() );

		$this->acting_as( $norte );
		$fila = MyCentre::model()['rows'][0];
		$this->assertSame( '', $fila['coordinator'] );
		$this->assertSame( array(), $fila['options'] );

		$html = MyCentreView::html( MyCentre::model() );
		$this->assertStringContainsString( 'prc-sindato', $html );
		$this->assertSame( 2, substr_count( $html, '¿?' ), 'coordinación y opciones, las dos sin dato' );
	}

	/**
	 * Una subsanación abierta se dice encima de la tabla y se marca en su
	 * fila: entre filas iguales, el plazo se agota sin que nadie lo vea.
	 */
	public function test_an_open_amendment_is_announced_above_the_table() {
		$uno   = $this->procedimiento(
			'Red de prueba',
			array(
				ProcedureMetaKeys::OPENS_AT        => $this->dia( -20 ),
				ProcedureMetaKeys::CLOSES_AT       => $this->dia( -10 ),
				ProcedureMetaKeys::AMEND_OPENS_AT  => $this->dia( -1 ),
				ProcedureMetaKeys::AMEND_CLOSES_AT => $this->dia( 5 ),
			)
		);
		$norte = $this->school_head( 'C0001' );
		$suya  = Applications::save( $uno, $norte, $this->nucleo() );
		Applications::review( $suya, ApplicationMetaKeys::REVIEW_AMEND, 'Falta la memoria del centro.' );

		$this->acting_as( $norte );
		$m = MyCentre::model();
		$this->assertTrue( $m['rows'][0]['amend'] );
		$this->assertSame( 1, $m['amend']['count'] );
		$this->assertSame( (string) $uno, $m['amend']['id'] );

		$html = MyCentreView::html( $m );
		$this->assertStringContainsString( 'la subsanación abierta', $html );
		$this->assertStringContainsString( gmdate( 'd-m-Y', (int) strtotime( $this->dia( 5 ) ) ), $html );
		$this->assertStringContainsString( 'prc-subsanar', $html );
		$this->assertStringContainsString( '#fila-' . $uno, $html );
	}

	/**
	 * Un procedimiento abierto que pide una lista de entre dos y cuatro personas.
	 *
	 * @param array<string, mixed> $lista What changes in the list.
	 * @return int
	 */
	private function con_lista( array $lista = array() ): int {
		return $this->procedimiento(
			'Red con personas',
			array(
				ProcedureMetaKeys::OPENS_AT    => $this->dia( -1 ),
				ProcedureMetaKeys::CLOSES_AT   => $this->dia( 5 ),
				ProcedureMetaKeys::PEOPLE_SPEC => array(
					'enabled' => true,
					'roles'   => array(
						array(
							'key'   => 'r1',
							'label' => 'Coordinación',
						),
					),
					'min'     => 2,
					'max'     => 4,
				) + $lista,
			)
		);
	}

	/**
	 * La columna «Personas» dice cuántas hay y cuántas pide, y la que no
	 * llega se marca, se anuncia arriba y lleva a completar la lista.
	 */
	public function test_the_people_column_says_which_lists_are_incomplete() {
		$lista = $this->con_lista();
		$norte = $this->school_head( 'C0001' );
		$suya  = Applications::save( $lista, $norte, $this->nucleo() );
		$this->acting_as( $norte );

		$m = MyCentre::model();
		$this->assertTrue( $m['has_people'] );
		$this->assertFalse( $m['rows'][0]['people_complete'] );
		$this->assertSame( 1, $m['incomplete']['count'] );
		$html = MyCentreView::html( $m );
		$this->assertStringContainsString( '>Personas</a></th>', $html );
		$this->assertStringContainsString( 'Faltan 2 personas', $html );
		$this->assertMatchesRegularExpression( '/href="[^"]*#' . PeopleForm::ANCHOR . '">Completar</', $html );
		$this->assertStringContainsString( '<strong>1 solicitud</strong> está incompleta: le faltan personas en la lista.', $html );
		$this->assertStringContainsString( 'href="#fila-' . $lista . '">Ir a la solicitud</a>', $html );

		foreach ( array( '12345678Z', 'X0000000T' ) as $dni ) {
			People::add(
				$suya,
				$norte,
				array(
					'name'    => 'Ana',
					'surname' => 'Pérez',
					'dni'     => $dni,
					'email'   => 'ana@example.org',
					'role'    => 'r1',
				)
			);
		}
		$m    = MyCentre::model();
		$html = MyCentreView::html( $m );
		$this->assertSame( 0, $m['incomplete']['count'] );
		$this->assertStringContainsString( '2 de 2 a 4', $html );
		$this->assertMatchesRegularExpression( '/href="[^"]*#' . PeopleForm::ANCHOR . '">Cambiar</', $html );
		$this->assertStringNotContainsString( 'está incompleta', $html );
	}

	/**
	 * Una lista que todavía no se abre no «ya no se puede cambiar»: la celda
	 * da el motivo, y el aviso de arriba no la cuenta, porque el centro no
	 * puede completarla ahora.
	 */
	public function test_a_list_not_open_yet_says_why_and_is_not_announced() {
		$lista = $this->con_lista( array( 'admitted_only' => true ) );
		$norte = $this->school_head( 'C0001' );
		Applications::save( $lista, $norte, $this->nucleo() );
		$this->acting_as( $norte );

		$m = MyCentre::model();
		$this->assertFalse( $m['rows'][0]['people_complete'] );
		$this->assertSame( 0, $m['incomplete']['count'] );
		$html = MyCentreView::html( $m );
		$this->assertStringNotContainsString( 'ya no se puede cambiar', $html );
		$this->assertStringContainsString( 'La lista de personas se gestiona cuando la solicitud esté admitida, y todavía no lo está.', $html );
		$this->assertStringNotContainsString( 'está incompleta', $html );
	}

	/**
	 * Ni una excluida ni una con la lista ya cerrada entran en el aviso:
	 * no hay nada que el centro pueda completar.
	 */
	public function test_excluded_or_closed_lists_are_not_announced() {
		$norte    = $this->school_head( 'C0001' );
		$excluida = Applications::save( $this->con_lista(), $norte, $this->nucleo() );
		update_post_meta( $excluida, ApplicationMetaKeys::REVIEW_STATE, ApplicationMetaKeys::REVIEW_EXCLUDED );
		$cerrada = $this->con_lista(
			array(
				'opens_at'  => $this->dia( -5 ),
				'closes_at' => $this->dia( -1 ),
			)
		);
		Applications::save( $cerrada, $norte, $this->nucleo() );
		$this->acting_as( $norte );

		$m = MyCentre::model();
		$this->assertCount( 2, $m['rows'] );
		$this->assertSame( 0, $m['incomplete']['count'] );
		$html = MyCentreView::html( $m );
		$this->assertStringContainsString( 'La solicitud está excluida: su lista de personas ya no se cambia.', $html );
		$this->assertStringContainsString( 'El plazo de la lista de personas terminó el', $html );
	}

	/**
	 * Sin ningún procedimiento con lista, la columna no sale.
	 */
	public function test_without_lists_there_is_no_people_column() {
		$norte = $this->school_head( 'C0001' );
		Applications::save( $this->procedimiento( 'Red de prueba' ), $norte, $this->nucleo() );
		$this->acting_as( $norte );

		$m = MyCentre::model();
		$this->assertFalse( $m['has_people'] );
		$this->assertStringNotContainsString( '>Personas</a></th>', MyCentreView::html( $m ) );
	}

	/**
	 * Retirar no está en la tabla, pegado a «Ver la solicitud»: está al
	 * final de la solicitud, con su confirmación. Y ordenar se explica en
	 * una línea.
	 */
	public function test_the_table_has_no_withdraw_and_one_line_of_help() {
		$norte = $this->school_head( 'C0001' );
		Applications::save(
			$this->procedimiento(
				'Red de prueba',
				array(
					ProcedureMetaKeys::OPENS_AT  => $this->dia( -1 ),
					ProcedureMetaKeys::CLOSES_AT => $this->dia( 5 ),
				)
			),
			$norte,
			$this->nucleo()
		);
		$this->acting_as( $norte );

		$html = MyCentreView::html( MyCentre::model() );
		$this->assertStringNotContainsString( 'Retirar la solicitud', $html );
		$this->assertStringContainsString( 'Ver la solicitud', $html );
		$this->assertStringContainsString( '<p class="prc-ayuda-orden">Pulse una cabecera para ordenar la tabla; otra vez, al revés.</p>', $html );
	}

	/**
	 * Sin nada que subsanar no hay aviso: un aviso permanente no es un aviso.
	 */
	public function test_without_an_open_amendment_there_is_no_notice() {
		$uno   = $this->procedimiento( 'Red de prueba' );
		$norte = $this->school_head( 'C0001' );
		Applications::save( $uno, $norte, $this->nucleo() );

		$this->acting_as( $norte );
		$m = MyCentre::model();
		$this->assertSame( 0, $m['amend']['count'] );
		$this->assertStringNotContainsString( 'subsanación abierta', MyCentreView::html( $m ) );
	}

	/**
	 * Las filas no piden a la base de datos una vez por procedimiento.
	 *
	 * Cada fila lee el título, la ficha y las fechas de su procedimiento; con
	 * los procedimientos cargados de una vez, cuatro solicitudes cuestan las
	 * mismas consultas que una.
	 */
	public function test_the_rows_do_not_query_once_per_procedure() {
		$consultas = function ( string $centro, int $cuantas ): int {
			$quien = $this->school_head( $centro );
			for ( $i = 0; $i < $cuantas; $i++ ) {
				$this->application( $this->procedimiento( 'Programa ' . $centro . ' ' . $i ), $quien, $centro );
			}
			$this->acting_as( $quien );
			MyCentre::model();
			wp_cache_flush();

			global $wpdb;
			$antes = $wpdb->num_queries;
			$filas = MyCentre::model()['rows'];
			$this->assertCount( $cuantas, $filas );
			return $wpdb->num_queries - $antes;
		};

		$this->assertSame( $consultas( 'C0002', 1 ), $consultas( 'C0001', 4 ) );
	}
}
