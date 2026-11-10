<?php
/**
 * Tests for the read-only consultation screen: the list, the table scoped by
 * zone, what it never shows, the export, the documents, the workshop panel and
 * the mail copy.
 *
 * @package Prc
 */

use Prc\Access\ObserverScope;
use Prc\Domain\ApplicationFilter;
use Prc\Domain\CentreCatalog;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PublicFront\ApplicationFiles;
use Prc\PublicFront\Observe;
use Prc\PublicFront\ObserveSettings;
use Prc\PublicFront\People;
use Prc\PublicFront\ProcedureEditor;
use Prc\PublicFront\Shell;
use Prc\PublicFront\View\ObserveView;
use Prc\PublicFront\View\ProcedureEditorView;

/**
 * La consulta de solo lectura (PLAN-0003, PR 7; ADR-0043).
 */
class Test_Observe extends WP_UnitTestCase {

	use Prc_Fixtures;

	/** DNI of the coordination: must never reach the consultation. */
	const DNI_COORD = '11111111H';

	/** DNI of a person of the list: must never reach the consultation. */
	const DNI_PERSONA = '22222222J';

	/** Review note: must never reach the consultation. */
	const NOTA = 'NOTA-RESERVADA-DE-REVISION';

	/**
	 * Correos capturados.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private $enviados = array();

	/**
	 * Páginas, un catálogo inventado de dos zonas y el correo capturado.
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
						'name'   => 'CEIP Uno',
						'island' => 'Isla Uno',
						'zone'   => 'Z0001',
					),
					array(
						'code'   => 'C0002',
						'name'   => 'IES Dos',
						'island' => 'Isla Dos',
						'zone'   => 'Z0002',
					),
					array(
						'code'   => 'C0003',
						'name'   => 'CEO Tres',
						'island' => 'Isla Uno',
						'zone'   => 'Z0002',
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
		CentreCatalog::flush();
		\Closure::bind(
			static function (): void {
				self::$rejected = '';
			},
			null,
			Observe::class
		)();
		$this->enviados = array();
		add_filter(
			'pre_wp_mail',
			function ( $corto, array $atts ): bool {
				unset( $corto );
				$this->enviados[] = $atts;
				return true;
			},
			10,
			2
		);
	}

	/**
	 * Un procedimiento observable con una solicitud por centro, cada una con
	 * su coordinación (con DNI) y una nota de revisión.
	 *
	 * @param array<string, mixed> $meta Procedure meta on top.
	 * @return array{area:int, procedure:int, c1:int, c2:int, c3:int}
	 */
	private function caso( array $meta = array() ): array {
		$area = $this->area( 'Ámbito convocante' );
		$pid  = $this->procedure(
			$this->administrator(),
			array( $area ),
			array_merge(
				array(
					ProcedureMetaKeys::OBSERVABLE     => true,
					ProcedureMetaKeys::CONTACT_EMAILS => array( 'gestion@example.org' ),
				),
				$meta
			),
			array( 'post_title' => 'Red de prueba' )
		);
		$out  = array(
			'area'      => $area,
			'procedure' => $pid,
		);
		foreach ( array(
			'c1' => array( 'C0001', 'CEIP Uno' ),
			'c2' => array( 'C0002', 'IES Dos' ),
			'c3' => array( 'C0003', 'CEO Tres' ),
		) as $clave => $centro ) {
			$out[ $clave ] = $this->application(
				$pid,
				$this->school_head( $centro[0] ),
				$centro[0],
				array(
					ApplicationMetaKeys::CENTRE_NAME  => $centro[1],
					ApplicationMetaKeys::REVIEW_STATE => ApplicationMetaKeys::REVIEW_AMEND,
					ApplicationMetaKeys::REVIEW_NOTE  => self::NOTA,
					ApplicationMetaKeys::COORDINATOR  => array(
						array(
							'name'    => 'Coordinadora',
							'surname' => 'De ' . $centro[1],
							'dni'     => self::DNI_COORD,
							'email'   => 'coord-' . strtolower( $centro[0] ) . '@example.org',
						),
					),
				)
			);
		}
		return $out;
	}

	/**
	 * Write the column choice without going through its sanitiser, as a
	 * careless snippet or an old value would.
	 *
	 * @param int      $procedure Procedure ID.
	 * @param string[] $columns   Column keys.
	 * @return void
	 */
	private function raw_columns( int $procedure, array $columns ): void {
		global $wpdb;
		delete_post_meta( $procedure, ProcedureMetaKeys::OBSERVER_COLUMNS );
		$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- el test esquiva el saneado a propósito.
			$wpdb->postmeta,
			array(
				'post_id'    => $procedure,
				'meta_key'   => ProcedureMetaKeys::OBSERVER_COLUMNS, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => maybe_serialize( $columns ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		wp_cache_delete( $procedure, 'post_meta' );
	}

	/**
	 * The screen as a person sees it.
	 *
	 * @param int                  $uid     Who.
	 * @param array<string, mixed> $request `$_GET`.
	 * @return string
	 */
	private function pantalla( int $uid, array $request = array() ): string {
		$this->acting_as( $uid );
		return ObserveView::html( Observe::model( $request ) );
	}

	/**
	 * The CSV the export handler serves.
	 *
	 * @param int                  $uid       Who.
	 * @param int                  $procedure Procedure ID.
	 * @param array<string, mixed> $campos    More POST fields: the filter.
	 * @return string
	 */
	private function exportar( int $uid, int $procedure, array $campos = array() ): string {
		$this->acting_as( $uid );
		$this->post(
			array(
				Observe::FIELD_DO => Observe::OP_EXPORT,
				Observe::FIELD_ID => (string) $procedure,
			) + $campos,
			Observe::nonce_action( $procedure ),
			Observe::NONCE_NAME
		);
		return $this->served( array( Observe::class, 'handle' ) );
	}

	// ─── la pantalla ───────────────────────────────────────────────────────

	/**
	 * Quien consulta una zona ve las solicitudes de sus centros y ninguna otra.
	 */
	public function test_the_table_shows_the_centres_of_the_zone_only() {
		$caso = $this->caso();
		$uid  = $this->observer( array( 'Z0002' ) );
		$html = $this->pantalla( $uid, array( Observe::ARG_PROCEDURE => (string) $caso['procedure'] ) );

		$this->assertStringContainsString( 'IES Dos', $html );
		$this->assertStringContainsString( 'CEO Tres', $html );
		$this->assertStringNotContainsString( 'CEIP Uno', $html, 'otra zona' );
		$this->assertStringNotContainsString( 'C0001', $html );
		$this->assertStringContainsString( 'Mostrando 2 solicitudes', $html );
	}

	/**
	 * El filtro de zona solo ofrece las suyas, y una zona ajena en la URL se ignora.
	 */
	public function test_the_zone_filter_offers_only_their_zones() {
		$caso   = $this->caso();
		$uid    = $this->observer( array( 'Z0002' ) );
		$modelo = ( function () use ( $uid, $caso ): array {
			$this->acting_as( $uid );
			return Observe::model(
				array(
					Observe::ARG_PROCEDURE      => (string) $caso['procedure'],
					ApplicationFilter::ARG_ZONE => 'Z0001',
				)
			);
		} )();

		$this->assertSame( array( 'Z0002' => 'Zona Inventada Dos' ), $modelo['table']['options']['zones'] );
		$this->assertSame( '', $modelo['table']['filter']['zone'], 'la zona ajena no es un valor del filtro' );
		$this->assertCount( 2, $modelo['rows'] );
	}

	/**
	 * Con dos zonas, las tres solicitudes; buscar y paginar como en el taller.
	 */
	public function test_two_zones_and_the_search() {
		$caso = $this->caso();
		$uid  = $this->observer( array( 'Z0001', 'Z0002' ) );
		$html = $this->pantalla(
			$uid,
			array(
				Observe::ARG_PROCEDURE        => (string) $caso['procedure'],
				ApplicationFilter::ARG_SEARCH => 'tres',
			)
		);
		$this->assertStringContainsString( 'CEO Tres', $html );
		$this->assertStringNotContainsString( 'IES Dos', $html );
		$this->assertStringContainsString( 'de 3 en total', $html );
	}

	/**
	 * Sin `prc_observable`, la pantalla dice lo mismo que de un ID que no existe.
	 */
	public function test_a_procedure_without_consultation_answers_as_if_it_did_not_exist() {
		$caso      = $this->caso( array( ProcedureMetaKeys::OBSERVABLE => false ) );
		$uid       = $this->observer( array( 'Z0002' ) );
		$cerrado   = $this->pantalla( $uid, array( Observe::ARG_PROCEDURE => (string) $caso['procedure'] ) );
		$no_existe = $this->pantalla( $uid, array( Observe::ARG_PROCEDURE => '999999' ) );

		$this->assertStringContainsString( Observe::NOT_FOUND, $cerrado );
		$this->assertSame( $no_existe, $cerrado, 'la misma respuesta: no confirma que exista' );
		$this->assertStringNotContainsString( 'Red de prueba', $cerrado );
		$this->assertStringNotContainsString( 'IES Dos', $cerrado );
	}

	/**
	 * Quien gestiona el ámbito ve todas las zonas; quien gestiona otro, nada.
	 */
	public function test_a_manager_sees_every_zone_and_another_area_nothing() {
		$caso   = $this->caso();
		$propio = $this->manager( array( $caso['area'] ) );
		$ajeno  = $this->manager( array( $this->area( 'Otro ámbito' ) ) );

		$html = $this->pantalla( $propio, array( Observe::ARG_PROCEDURE => (string) $caso['procedure'] ) );
		foreach ( array( 'CEIP Uno', 'IES Dos', 'CEO Tres' ) as $centro ) {
			$this->assertStringContainsString( $centro, $html );
		}
		$this->assertStringContainsString( Observe::NOT_FOUND, $this->pantalla( $ajeno, array( Observe::ARG_PROCEDURE => (string) $caso['procedure'] ) ) );
	}

	/**
	 * Sin zona, la pantalla lo dice y no lista nada.
	 */
	public function test_an_account_without_zone_is_told_why() {
		$this->caso();
		$html = $this->pantalla( $this->observer() );
		$this->assertStringContainsString( 'no tiene ninguna zona asignada', $html );
		$this->assertStringNotContainsString( 'Red de prueba', $html );

		$html = $this->pantalla( $this->observer( array( 'Z9999' ) ) );
		$this->assertStringContainsString( 'tiene centros en el catálogo', $html );

		$html = $this->pantalla( $this->school_head( 'C0002' ) );
		$this->assertStringContainsString( 'Su perfil no consulta solicitudes', $html );
	}

	/**
	 * Sin procedimiento, la lista: los observables y publicados con solicitudes
	 * de su zona, cada uno con cuántas.
	 */
	public function test_the_list_counts_the_applications_of_the_zone() {
		$caso    = $this->caso();
		$cerrado = $this->procedure( $this->administrator(), array( $caso['area'] ), array(), array( 'post_title' => 'Sin consulta' ) );
		$this->application( $cerrado, $this->school_head( 'C0002' ) );
		$otra = $this->procedure( $this->administrator(), array( $caso['area'] ), array( ProcedureMetaKeys::OBSERVABLE => true ), array( 'post_title' => 'Solo de otra zona' ) );
		$this->application( $otra, $this->school_head( 'C0001' ) );

		$uid = $this->observer( array( 'Z0002' ) );
		$this->acting_as( $uid );
		$lista = Observe::model( array() )['procedures'];

		$this->assertCount( 1, $lista );
		$this->assertSame( $caso['procedure'], $lista[0]['id'] );
		$this->assertSame( 2, $lista[0]['count'] );
		$html = $this->pantalla( $uid );
		$this->assertStringContainsString( 'Red de prueba', $html );
		$this->assertStringNotContainsString( 'Sin consulta', $html );
		$this->assertStringNotContainsString( 'Solo de otra zona', $html );
		$this->assertStringContainsString( 'Zona Inventada Dos', $html, 'dice con qué zona mira' );
	}

	/**
	 * Por defecto: solicitud, centro y coordinación; sin DNI, sin nota, sin estado.
	 */
	public function test_the_default_columns() {
		$caso = $this->caso();
		$uid  = $this->observer( array( 'Z0002' ) );
		$this->acting_as( $uid );
		$modelo = Observe::model( array( Observe::ARG_PROCEDURE => (string) $caso['procedure'] ) );

		$this->assertArrayHasKey( 'date', $modelo['columns'] );
		$this->assertArrayHasKey( 'code', $modelo['columns'] );
		$this->assertArrayHasKey( 'coordinator', $modelo['columns'] );
		$this->assertArrayHasKey( 'coordinator_email', $modelo['columns'] );
		$this->assertArrayNotHasKey( 'coordinator_dni', $modelo['columns'] );
		$this->assertArrayNotHasKey( 'note', $modelo['columns'] );
		$this->assertArrayNotHasKey( 'state', $modelo['columns'] );
		$this->assertArrayNotHasKey( 'applicant', $modelo['columns'] );
		$this->assertSame( array(), $modelo['table']['states'], 'sin la columna del estado, ni contadores ni filtro por estado' );
	}

	/**
	 * El DNI y la nota no salen nunca en el HTML, aunque la meta los pida.
	 */
	public function test_the_dni_and_the_note_never_reach_the_html() {
		$caso = $this->caso(
			array(
				ProcedureMetaKeys::PEOPLE_SPEC     => array(
					'enabled' => true,
					'roles'   => array(
						array(
							'key'   => 'r1',
							'label' => 'Docente',
						),
					),
				),
				ProcedureMetaKeys::OBSERVER_EXPORT => true,
			)
		);
		update_post_meta(
			$caso['c2'],
			ApplicationMetaKeys::PEOPLE,
			array(
				array(
					'id'      => 'a1b2c3d4e5f6',
					'name'    => 'Persona',
					'surname' => 'De la lista',
					'dni'     => self::DNI_PERSONA,
					'email'   => 'persona@example.org',
					'phone'   => '',
					'role'    => 'r1',
					'group'   => '',
				),
			)
		);
		$this->raw_columns( $caso['procedure'], array( 'centre', 'coordinator', 'coordinator_dni', 'person_name', 'person_dni', 'state', 'note' ) );
		$uid  = $this->observer( array( 'Z0002' ) );
		$html = $this->pantalla( $uid, array( Observe::ARG_PROCEDURE => (string) $caso['procedure'] ) );

		$this->assertStringContainsString( 'Persona', $html, 'las personas de la lista se ven si sus columnas están elegidas' );
		$this->assertStringContainsString( 'A subsanar', $html, 'el estado, que sí se puede elegir' );
		$this->assertStringNotContainsString( self::DNI_COORD, $html );
		$this->assertStringNotContainsString( self::DNI_PERSONA, $html );
		$this->assertStringNotContainsString( self::NOTA, $html );
		$this->assertStringNotContainsString( 'DNI', $html );

		$csv = $this->exportar( $uid, $caso['procedure'] );
		$this->assertStringContainsString( 'IES Dos', $csv );
		$this->assertStringNotContainsString( self::DNI_COORD, $csv );
		$this->assertStringNotContainsString( self::DNI_PERSONA, $csv );
		$this->assertStringNotContainsString( self::NOTA, $csv );
		$this->assertStringNotContainsString( 'DNI', $csv );
	}

	/**
	 * Solo lectura: no hay ningún formulario que escriba, ni de revisión ni
	 * de personas, y sin exportar permitido no hay ni siquiera ese.
	 */
	public function test_the_screen_has_no_writing_form() {
		$caso = $this->caso();
		$uid  = $this->observer( array( 'Z0002' ) );
		$html = $this->pantalla( $uid, array( Observe::ARG_PROCEDURE => (string) $caso['procedure'] ) );

		$this->assertStringNotContainsString( 'method="post"', $html );
		$this->assertStringNotContainsString( ProcedureEditor::FIELD_STATE, $html );
		$this->assertStringNotContainsString( ProcedureEditor::FIELD_DO, $html );
		$this->assertStringNotContainsString( 'Exportar', $html );

		update_post_meta( $caso['procedure'], ProcedureMetaKeys::OBSERVER_EXPORT, true );
		$html = $this->pantalla( $uid, array( Observe::ARG_PROCEDURE => (string) $caso['procedure'] ) );
		$this->assertSame( 1, substr_count( $html, 'method="post"' ), 'el de exportar, y nada más' );
		$this->assertStringNotContainsString( ProcedureEditor::FIELD_STATE, $html );
	}

	// ─── exportar ──────────────────────────────────────────────────────────

	/**
	 * Sin exportar permitido, el envío a mano no sirve ningún fichero.
	 */
	public function test_without_export_a_crafted_post_serves_nothing() {
		$caso = $this->caso();
		$uid  = $this->observer( array( 'Z0002' ) );
		$this->assertSame( '', $this->exportar( $uid, $caso['procedure'] ) );
		$this->assertNull( Observe::csv( $uid, $caso['procedure'], array() ) );
	}

	/**
	 * La exportación va acotada a sus zonas y respeta el filtro.
	 */
	public function test_the_export_is_scoped_and_filtered() {
		$caso = $this->caso( array( ProcedureMetaKeys::OBSERVER_EXPORT => true ) );
		$uid  = $this->observer( array( 'Z0002' ) );

		$csv = $this->exportar( $uid, $caso['procedure'] );
		$this->assertStringContainsString( 'IES Dos', $csv );
		$this->assertStringContainsString( 'CEO Tres', $csv );
		$this->assertStringNotContainsString( 'CEIP Uno', $csv, 'otra zona, tampoco en la exportación' );
		$this->assertStringNotContainsString( 'C0001', $csv );

		$csv = $this->exportar( $uid, $caso['procedure'], array( ApplicationFilter::ARG_SEARCH => 'tres' ) );
		$this->assertStringContainsString( 'CEO Tres', $csv );
		$this->assertStringNotContainsString( 'IES Dos', $csv );

		// Ni pidiendo la zona ajena por el filtro.
		$csv = $this->exportar( $uid, $caso['procedure'], array( ApplicationFilter::ARG_ZONE => 'Z0001' ) );
		$this->assertStringNotContainsString( 'CEIP Uno', $csv );
	}

	/**
	 * Sin nonce, o con el de otro procedimiento, no se exporta.
	 */
	public function test_the_export_needs_its_nonce() {
		$caso = $this->caso( array( ProcedureMetaKeys::OBSERVER_EXPORT => true ) );
		$otro = $this->procedure( $this->administrator(), array( $caso['area'] ), array( ProcedureMetaKeys::OBSERVABLE => true ) );
		$uid  = $this->observer( array( 'Z0002' ) );
		$this->acting_as( $uid );
		$this->post(
			array(
				Observe::FIELD_DO => Observe::OP_EXPORT,
				Observe::FIELD_ID => (string) $caso['procedure'],
			),
			Observe::nonce_action( $otro ),
			Observe::NONCE_NAME
		);
		$this->assertSame( '', $this->served( array( Observe::class, 'handle' ) ) );
	}

	/**
	 * El fichero de alta de usuarios no se ofrece a quien solo consulta.
	 */
	public function test_the_user_upload_file_is_not_offered() {
		$caso = $this->caso( array( ProcedureMetaKeys::OBSERVER_EXPORT => true ) );
		$uid  = $this->observer( array( 'Z0002' ) );
		$html = $this->pantalla( $uid, array( Observe::ARG_PROCEDURE => (string) $caso['procedure'] ) );
		$this->assertStringNotContainsString( ProcedureEditor::FIELD_EXPORT_FORMAT, $html );
		$this->assertStringNotContainsString( 'usuarios', $html );

		$csv = $this->exportar( $uid, $caso['procedure'], array( ProcedureEditor::FIELD_EXPORT_FORMAT => 'moodle' ) );
		$this->assertStringNotContainsString( 'username,firstname', $csv );
	}

	// ─── documentos ────────────────────────────────────────────────────────

	/**
	 * Un documento de la solicitud, solo su descriptor.
	 *
	 * @param int $application Application ID.
	 * @return string Opaque file ID.
	 */
	private function documento( int $application ): string {
		$id = str_repeat( 'ab', 16 );
		update_post_meta(
			$application,
			ApplicationMetaKeys::FILES,
			array(
				'q1' => array(
					'id'     => $id,
					'name'   => 'memoria.pdf',
					'mime'   => 'application/pdf',
					'size'   => 10,
					'sha256' => str_repeat( '0', 64 ),
					'stored' => 'ab/cd/' . $id . '.pdf',
				),
			)
		);
		return $id;
	}

	/**
	 * Lo que contesta la descarga de un documento, por una URL escrita a mano.
	 *
	 * @param int    $uid         Who.
	 * @param int    $application Application ID.
	 * @param string $file        Opaque file ID.
	 * @return string
	 */
	private function descargar( int $uid, int $application, string $file ): string {
		$this->acting_as( $uid );
		$_GET = array(
			ApplicationFiles::ARG_APPLICATION => (string) $application,
			ApplicationFiles::ARG_FILE        => $file,
		);
		return $this->served( array( ApplicationFiles::class, 'handle' ) );
	}

	/**
	 * Sin documentos permitidos no hay enlace y la descarga se niega; con
	 * ellos, hay enlace y la descarga pasa la autorización. Los de otra zona,
	 * nunca, ni con la URL adivinada.
	 */
	public function test_documents_are_downloaded_only_when_allowed_and_only_of_the_zone() {
		$caso   = $this->caso(
			array(
				ProcedureMetaKeys::QUESTIONS => array(
					array(
						'key'      => 'q1',
						'label'    => 'Memoria',
						'type'     => 'file',
						'required' => false,
					),
				),
			)
		);
		$propio = $this->documento( $caso['c2'] );
		$ajeno  = $this->documento( $caso['c1'] );
		$uid    = $this->observer( array( 'Z0002' ) );
		$this->raw_columns( $caso['procedure'], array( 'centre', 'q1' ) );
		$html = $this->pantalla( $uid, array( Observe::ARG_PROCEDURE => (string) $caso['procedure'] ) );
		$this->assertStringContainsString( 'memoria.pdf', $html, 'el nombre del documento sí se ve' );
		$this->assertStringNotContainsString( ApplicationFiles::ARG_FILE, $html, 'sin enlace de descarga' );
		$this->assertStringContainsString( 'No puede descargar', $this->descargar( $uid, $caso['c2'], $propio ) );

		update_post_meta( $caso['procedure'], ProcedureMetaKeys::OBSERVER_FILES, true );
		$html = $this->pantalla( $uid, array( Observe::ARG_PROCEDURE => (string) $caso['procedure'] ) );
		$this->assertStringContainsString( ApplicationFiles::ARG_FILE, $html );
		$this->assertStringContainsString( 'Ese documento ya no está', $this->descargar( $uid, $caso['c2'], $propio ), 'autorizada: lo que falta es el fichero de prueba' );
		$this->assertStringContainsString( 'No puede descargar', $this->descargar( $uid, $caso['c1'], $ajeno ), 'otra zona, ni con la URL adivinada' );

		// Con documentos permitidos pero sin la pregunta entre sus columnas, tampoco.
		$this->raw_columns( $caso['procedure'], array( 'centre' ) );
		$this->assertStringContainsString( 'No puede descargar', $this->descargar( $uid, $caso['c2'], $propio ) );
		$this->assertStringContainsString( 'Ese documento ya no está', $this->descargar( (int) get_post_field( 'post_author', $caso['c2'] ), $caso['c2'], $propio ), 'el centro sí: lo que falta es el fichero de prueba' );
	}

	// ─── el panel del taller ───────────────────────────────────────────────

	/**
	 * El panel «Consulta» guarda lo que se marca, y nunca el DNI.
	 */
	public function test_the_workshop_panel_saves_the_settings() {
		$caso   = $this->caso( array( ProcedureMetaKeys::OBSERVABLE => false ) );
		$gestor = $this->manager( array( $caso['area'] ) );
		$this->acting_as( $gestor );
		$op = ProcedureEditor::OP_OBSERVE;
		$this->post(
			array(
				ProcedureEditor::FIELD_DO         => $op,
				ProcedureEditor::FIELD_PROCEDURE  => (string) $caso['procedure'],
				ObserveSettings::FIELD_OBSERVABLE => '1',
				ObserveSettings::FIELD_COLUMNS    => array( 'code', 'coordinator_dni', 'state', 'note', 'inventada' ),
				ObserveSettings::FIELD_EXPORT     => '1',
				ObserveSettings::FIELD_NOTIFY     => '1',
			),
			ProcedureEditor::nonce_action( $op ),
			ProcedureEditor::nonce_name( $op )
		);
		$url = $this->exit_url( array( ProcedureEditor::class, 'handle' ) );

		$this->assertSame( 'consulta', $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
		$this->assertTrue( (bool) get_post_meta( $caso['procedure'], ProcedureMetaKeys::OBSERVABLE, true ) );
		$this->assertSame( array( 'code', 'state' ), get_post_meta( $caso['procedure'], ProcedureMetaKeys::OBSERVER_COLUMNS, true ) );
		$this->assertTrue( (bool) get_post_meta( $caso['procedure'], ProcedureMetaKeys::OBSERVER_EXPORT, true ) );
		$this->assertFalse( (bool) get_post_meta( $caso['procedure'], ProcedureMetaKeys::OBSERVER_FILES, true ) );
		$this->assertTrue( (bool) get_post_meta( $caso['procedure'], ProcedureMetaKeys::OBSERVER_NOTIFY, true ) );
	}

	/**
	 * Quien consulta no guarda el panel, ni armando el envío a mano.
	 */
	public function test_an_observer_cannot_save_the_panel() {
		$caso = $this->caso();
		$this->acting_as( $this->observer( array( 'Z0002' ) ) );
		$op = ProcedureEditor::OP_OBSERVE;
		$this->post(
			array(
				ProcedureEditor::FIELD_DO        => $op,
				ProcedureEditor::FIELD_PROCEDURE => (string) $caso['procedure'],
				ObserveSettings::FIELD_FILES     => '1',
			),
			ProcedureEditor::nonce_action( $op ),
			ProcedureEditor::nonce_name( $op )
		);
		$this->assertNull( $this->exit_url( array( ProcedureEditor::class, 'handle' ) ) );
		$this->assertFalse( (bool) get_post_meta( $caso['procedure'], ProcedureMetaKeys::OBSERVER_FILES, true ) );
	}

	/**
	 * El panel se pinta en el taller, apagado por defecto y sin el DNI entre las columnas.
	 */
	public function test_the_workshop_panel_paints_without_the_dni() {
		$area = $this->area( 'Ámbito convocante' );
		$uid  = $this->manager( array( $area ) );
		$pid  = $this->procedure( $uid, array( $area ) );
		$this->acting_as( $uid );
		$_GET = array(
			ProcedureEditor::ARG_PROCEDURE => (string) $pid,
			ProcedureEditor::ARG_PANEL     => ProcedureEditor::PANEL_OBSERVE,
		);
		$html = ProcedureEditorView::html( ProcedureEditor::model() );

		$this->assertStringContainsString( 'Admitir la consulta de solicitudes por zona', $html );
		$this->assertStringNotContainsString( 'name="' . ObserveSettings::FIELD_OBSERVABLE . '" value="1" checked', $html, 'apagada por defecto' );
		$this->assertStringNotContainsString( 'value="coordinator_dni"', $html );
		$this->assertStringNotContainsString( 'value="note"', $html );
		$this->assertStringContainsString( 'value="coordinator_email"', $html );
	}

	// ─── el correo ─────────────────────────────────────────────────────────

	/**
	 * El aviso de personas se copia a quien consulta la zona del centro si el
	 * procedimiento lo pide; sin DNI y con el enlace a la consulta.
	 */
	public function test_the_people_mail_is_copied_to_the_zone_when_asked() {
		$spec = array(
			'enabled' => true,
			'roles'   => array(
				array(
					'key'   => 'r1',
					'label' => 'Docente',
				),
			),
		);
		$hoy  = \Prc\Domain\ProcedureState::today();
		$caso = $this->caso(
			array(
				ProcedureMetaKeys::PEOPLE_SPEC => $spec,
				ProcedureMetaKeys::OPENS_AT    => gmdate( 'Y-m-d', (int) strtotime( $hoy . ' -1 day' ) ),
				ProcedureMetaKeys::CLOSES_AT   => gmdate( 'Y-m-d', (int) strtotime( $hoy . ' +1 day' ) ),
			)
		);
		$zona = $this->observer( array( 'Z0002' ) );
		wp_update_user(
			array(
				'ID'         => $zona,
				'user_email' => 'consulta-dos@example.org',
			)
		);
		$this->observer( array( 'Z0001' ) );
		$director = (int) get_post_field( 'post_author', $caso['c2'] );
		$persona  = array(
			'name'    => 'Ana',
			'surname' => 'Pérez',
			'dni'     => self::DNI_PERSONA,
			'email'   => 'ana@example.org',
			'phone'   => '',
			'role'    => 'r1',
			'group'   => '',
		);

		// Cambiar el correo de una cuenta avisa a la dirección antigua: eso no es de aquí.
		$this->enviados = array();
		People::add( $caso['c2'], $director, $persona );
		$this->assertSame( array( 'gestion@example.org' ), array_column( $this->enviados, 'to' ), 'apagado por defecto' );

		$this->enviados = array();
		update_post_meta( $caso['procedure'], ProcedureMetaKeys::OBSERVER_NOTIFY, true );
		People::add(
			$caso['c2'],
			$director,
			array(
				'dni'   => '33333333P',
				'email' => 'otra@example.org',
			) + $persona
		);
		$this->assertSame( array( 'gestion@example.org', 'consulta-dos@example.org' ), array_column( $this->enviados, 'to' ) );
		$contactos = (string) $this->enviados[0]['message'];
		$zona      = (string) $this->enviados[1]['message'];
		$this->assertStringContainsString( 'otra@example.org', $contactos, 'a los contactos, como siempre' );
		$this->assertStringNotContainsString( '33333333P', $zona );
		$this->assertStringContainsString( Shell::url( 'observe' ), $zona );
		$this->assertStringNotContainsString( 'editar-procedimiento', $zona );
		// La consulta no enseña ninguna columna de personas: el correo a la
		// zona dice que hubo un alta en el centro y nada de la persona.
		$this->assertStringContainsString( 'IES Dos', $zona );
		foreach ( array( 'Ana', 'Pérez', 'otra@example.org', 'Docente' ) as $dato ) {
			$this->assertStringNotContainsString( $dato, $zona, $dato );
		}

		// Si enseña el nombre y el papel, el correo a la zona lleva eso y nada más.
		$this->enviados = array();
		update_post_meta( $caso['procedure'], ProcedureMetaKeys::OBSERVER_COLUMNS, array( 'centre', 'person_name', 'person_role' ) );
		People::add(
			$caso['c2'],
			$director,
			array(
				'dni'   => '12345678Z',
				'email' => 'tercera@example.org',
				'phone' => '600 111 222',
			) + $persona
		);
		$zona = (string) $this->enviados[1]['message'];
		$this->assertStringContainsString( 'Ana', $zona );
		$this->assertStringContainsString( 'Docente', $zona );
		foreach ( array( 'Pérez', 'tercera@example.org', '600 111 222', '12345678Z' ) as $dato ) {
			$this->assertStringNotContainsString( $dato, $zona, $dato );
		}
		$this->assertStringContainsString( 'Pérez', (string) $this->enviados[0]['message'], 'los contactos lo reciben entero' );
	}

	/**
	 * La nota de revisión va a quien presentó la solicitud solo si sigue
	 * siendo de su centro; poder verla por la consulta no basta.
	 */
	public function test_the_review_note_does_not_reach_an_author_who_only_observes_now() {
		$caso  = $this->caso();
		$autor = (int) get_post_field( 'post_author', $caso['c2'] );
		wp_update_user(
			array(
				'ID'         => $autor,
				'user_email' => 'antes-directora@example.org',
			)
		);

		$this->enviados = array();
		\Prc\PublicFront\Applications::review( $caso['c2'], ApplicationMetaKeys::REVIEW_EXCLUDED, 'Primera nota' );
		$this->assertContains( 'antes-directora@example.org', array_column( $this->enviados, 'to' ), 'mientras es de su centro, le llega' );

		// Cambia de centro y ahora consulta la zona del antiguo.
		update_user_meta( $autor, \Prc\Access\CentreScope::meta_key(), 'C0001' );
		get_userdata( $autor )->add_role( 'prc_observer' );
		update_user_meta( $autor, ObserverScope::meta_key(), array( 'Z0002' ) );
		$this->assertTrue( user_can( $autor, 'read_post', $caso['c2'] ), 'la ve por la consulta' );

		$this->enviados = array();
		\Prc\PublicFront\Applications::review( $caso['c2'], ApplicationMetaKeys::REVIEW_AMEND, self::NOTA . ' segunda' );
		$this->assertNotContains( 'antes-directora@example.org', array_column( $this->enviados, 'to' ) );
	}

	// ─── el menú ───────────────────────────────────────────────────────────

	/**
	 * La consulta se ofrece a quien consulta una zona y a quien gestiona.
	 */
	public function test_the_menu_offers_the_consultation() {
		$this->acting_as( $this->observer( array( 'Z0002' ) ) );
		$this->assertSame( array( 'home', 'observe' ), array_keys( Shell::sections() ) );
		$this->assertSame( 'Consulta de solicitudes', Shell::profile()['role'] );
		$this->assertStringContainsString( 'Zona Inventada Dos', Shell::profile()['scope'] );

		$this->acting_as( $this->observer() );
		$this->assertSame( array( 'home' ), array_keys( Shell::sections() ), 'sin zona, no se ofrece' );

		$this->acting_as( $this->school_head( 'C0002' ) );
		$this->assertArrayNotHasKey( 'observe', Shell::sections() );
	}
}
