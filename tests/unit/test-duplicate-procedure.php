<?php
/**
 * Tests for duplicating a procedure from the workshop and from «Gestión».
 *
 * @package Prc
 */

use Prc\Access\ProcedureAccess;
use Prc\Domain\ProcedureQuestions;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ProcedurePostType;
use Prc\PublicFront\Applications;
use Prc\PublicFront\ProcedureDuplicate;
use Prc\PublicFront\ProcedureEditor;
use Prc\PublicFront\View\ProcedureEditorView;
use Prc\PublicFront\View\WorkspaceView;
use Prc\PublicFront\Workspace;
use Prc\Taxonomy\ProcedureTaxonomies;

/**
 * Duplicar un procedimiento (PR 8 del PLAN-0003).
 *
 * Cada curso las áreas vuelven a crear a mano el procedimiento del año
 * pasado. Lo que se prueba es la base de datos después de duplicar: qué tiene
 * el duplicado, qué no tiene, y que el original no se ha movido ni un byte.
 */
class Test_Duplicate_Procedure extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Los correos que habrían salido.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private $enviados = array();

	/**
	 * Con el aplicativo, sus páginas, sin catálogo y con el correo capturado.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
		$this->pages();
		remove_all_filters( 'prc_centres' );
		$this->enviados = array();
		add_filter( 'pre_wp_mail', array( $this, 'capturar' ), 10, 2 );
		\Closure::bind(
			static function (): void {
				self::$rejected = '';
			},
			null,
			ProcedureEditor::class
		)();
	}

	/**
	 * Guardar el correo en vez de mandarlo.
	 *
	 * @param mixed                $nada Short-circuit value.
	 * @param array<string, mixed> $atts Mail arguments.
	 * @return bool
	 */
	public function capturar( $nada, array $atts ): bool {
		unset( $nada );
		$this->enviados[] = $atts;
		return true;
	}

	/**
	 * Pedir el duplicado y devolver por dónde salió.
	 *
	 * @param int         $uid          Who submits.
	 * @param int         $procedure_id Original.
	 * @param string|null $nonce        Null for the good one, a string to forge it.
	 * @return string|null
	 */
	private function duplicar( int $uid, int $procedure_id, ?string $nonce = null ): ?string {
		$this->acting_as( $uid );
		$op     = ProcedureEditor::OP_DUPLICATE;
		$campos = array(
			ProcedureEditor::FIELD_DO        => $op,
			ProcedureEditor::FIELD_PROCEDURE => (string) $procedure_id,
		);
		if ( null === $nonce ) {
			$this->post( $campos, ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op ) );
		} else {
			$campos[ ProcedureEditor::nonce_name( $op ) ] = $nonce;
			$this->post( $campos );
		}
		return $this->exit_url( array( ProcedureEditor::class, 'handle' ) );
	}

	/**
	 * Every procedure but the ones given.
	 *
	 * @param int[] $menos IDs to leave out.
	 * @return int[]
	 */
	private function otros( array $menos ): array {
		$ids = get_posts(
			array(
				'post_type'   => ProcedurePostType::POST_TYPE,
				'post_status' => 'any',
				'numberposts' => -1,
				'fields'      => 'ids',
			)
		);
		return array_values( array_diff( array_map( 'intval', $ids ), $menos ) );
	}

	/**
	 * The duplicate a return URL opens.
	 *
	 * @param string|null $url Return URL.
	 * @return int
	 */
	private function copia_de( ?string $url ): int {
		$this->assertIsString( $url, 'duplicar redirige' );
		return (int) $this->query_arg( (string) $url, ProcedureEditor::ARG_PROCEDURE );
	}

	/**
	 * Un procedimiento con todo puesto: lo que se copia y lo que no.
	 *
	 * @param int   $uid   Author.
	 * @param int[] $areas Its ámbitos.
	 * @param array $extra Meta to add or override.
	 * @return int
	 */
	private function completo( int $uid, array $areas, array $extra = array() ): int {
		$id = $this->procedure(
			$uid,
			$areas,
			array_merge(
				array(
					ProcedureMetaKeys::OPENS_AT            => '2026-01-10',
					ProcedureMetaKeys::CLOSES_AT           => '2026-01-20',
					ProcedureMetaKeys::AMEND_OPENS_AT      => '2026-02-01',
					ProcedureMetaKeys::AMEND_CLOSES_AT     => '2026-02-05',
					ProcedureMetaKeys::RESOLUTION_URL      => 'https://example.org/resolucion.pdf',
					ProcedureMetaKeys::PROVISIONAL_LIST_URL => 'https://example.org/provisional.pdf',
					ProcedureMetaKeys::FINAL_LIST_URL      => 'https://example.org/definitivo.pdf',
					ProcedureMetaKeys::APPLICATIONS_CLOSED => true,
					ProcedureMetaKeys::CONTACT_EMAILS      => array( 'ambito@example.org' ),
					ProcedureMetaKeys::HEADER_COLOR        => 'verde',
					ProcedureMetaKeys::AUDIENCE            => ProcedureMetaKeys::AUDIENCE_SCHOOLS,
					ProcedureMetaKeys::OWNERSHIP           => array( ProcedureMetaKeys::OWNERSHIP_PUBLIC ),
					ProcedureMetaKeys::CENTRE_KINDS        => array( ProcedureMetaKeys::KIND_SCHOOL, ProcedureMetaKeys::KIND_RURAL_GROUP ),
					ProcedureMetaKeys::CENTRE_TYPES        => array( 'CEIP' ),
					ProcedureMetaKeys::ISLANDS             => array( 'Isla Uno' ),
					ProcedureMetaKeys::ALLOWED_CENTRES     => array( '99000001', '99000002' ),
					ProcedureMetaKeys::REQUIRES_COORDINATOR => true,
					ProcedureMetaKeys::COORDINATOR_COUNT   => 2,
					ProcedureMetaKeys::COMMITMENTS         => 'Participar en las sesiones.',
					ProcedureMetaKeys::CUSTOM_CSS          => '.prc-ficha { color: #123; }',
					ProcedureMetaKeys::NOTIFY              => array( 'review_changed' ),
					ProcedureMetaKeys::OBSERVABLE          => true,
					ProcedureMetaKeys::OBSERVER_FILES      => true,
					ProcedureMetaKeys::OBSERVER_EXPORT     => true,
					ProcedureMetaKeys::OBSERVER_NOTIFY     => true,
					ProcedureMetaKeys::OBSERVER_COLUMNS    => array( 'centre', 'q7', 'q9:r4', 'q3' ),
					ProcedureMetaKeys::QUESTIONS           => array(
						array(
							'key'   => 'q3',
							'label' => 'Proyecto',
							'type'  => 'text',
						),
						array(
							'key'       => 'q7',
							'label'     => 'Memoria final',
							'type'      => 'file',
							'delivery'  => true,
							'opens_at'  => '2026-05-01',
							'closes_at' => '2026-05-31',
						),
						array(
							'key'     => 'q9',
							'label'   => 'Alumnado por etapa',
							'type'    => 'grid',
							'rows'    => array(
								array(
									'key'   => 'r2',
									'label' => 'Infantil',
								),
								array(
									'key'   => 'r4',
									'label' => 'Primaria',
								),
							),
							'row_seq' => 4,
						),
					),
					ProcedureMetaKeys::QUESTIONS_SEQ       => 11,
					ProcedureMetaKeys::PEOPLE_SPEC         => array(
						'enabled'   => true,
						'roles'     => array(
							array(
								'key'   => 'r3',
								'label' => 'Participante',
							),
						),
						'role_seq'  => 3,
						'min'       => 1,
						'opens_at'  => '2026-03-01',
						'closes_at' => '2026-03-31',
					),
				),
				$extra
			),
			array(
				'post_title'   => 'Red de prueba',
				'post_content' => '<p>Una red de centros.</p>',
				'post_excerpt' => 'La red, en una línea.',
			)
		);
		wp_set_object_terms( $id, array( $this->curso() ), ProcedureTaxonomies::COURSE );
		return $id;
	}

	/**
	 * El curso de los originales: el mismo para todos.
	 *
	 * @return int Term ID.
	 */
	private function curso(): int {
		$existe = term_exists( '2026-2027', ProcedureTaxonomies::COURSE );
		return is_array( $existe ) ? (int) $existe['term_id'] : $this->course();
	}

	/**
	 * Who got the creation mail.
	 *
	 * @return string[]
	 */
	private function destinos_de_alta(): array {
		$destinos = array();
		foreach ( $this->enviados as $correo ) {
			if ( false !== strpos( (string) $correo['subject'], 'Procedimiento creado en borrador' ) ) {
				$destinos = array_merge( $destinos, (array) $correo['to'] );
			}
		}
		return $destinos;
	}

	// ─── lo que se copia ───────────────────────────────────────────────────

	/**
	 * El duplicado nace en borrador, de quien duplica, con lo que describe el
	 * procedimiento y sin nada de su convocatoria.
	 */
	public function test_the_duplicate_is_a_draft_with_what_describes_the_procedure() {
		$area   = $this->area( 'Área de prueba' );
		$autora = $this->manager( array( $area ) );
		$uid    = $this->manager( array( $area ) );
		$id     = $this->completo( $autora, array( $area ), array( ProcedureMetaKeys::CUSTOM_JS => 'console.log(1);' ) );
		$imagen = (int) self::factory()->attachment->create_object( 'cartel.jpg', 0, array( 'post_mime_type' => 'image/jpeg' ) );
		update_post_meta( $id, '_thumbnail_id', $imagen );

		$url   = $this->duplicar( $uid, $id );
		$copia = $this->copia_de( $url );

		$this->assertNotSame( $id, $copia );
		$this->assertSame( ProcedurePostType::POST_TYPE, get_post_type( $copia ) );
		$this->assertSame( 'draft', get_post_status( $copia ) );
		$this->assertSame( $uid, (int) get_post_field( 'post_author', $copia ), 'de quien duplica' );
		$this->assertSame( 'Red de prueba (copia)', get_post_field( 'post_title', $copia ) );
		$this->assertSame( '<p>Una red de centros.</p>', get_post_field( 'post_content', $copia ) );
		$this->assertSame( 'La red, en una línea.', get_post_field( 'post_excerpt', $copia ) );
		$this->assertSame( $imagen, (int) get_post_thumbnail_id( $copia ) );
		$this->assertSame( 0, (int) get_post_field( 'post_parent', $copia ) );
		$this->assertSame( array( $area ), ProcedureAccess::post_areas( $copia ) );
		$this->assertSame( array(), wp_get_post_terms( $copia, ProcedureTaxonomies::COURSE, array( 'fields' => 'ids' ) ), 'el curso queda por elegir' );

		// Va al panel «Datos» del duplicado, con el aviso.
		$this->assertSame( ProcedureEditor::PANEL_DATA, $this->query_arg( (string) $url, ProcedureEditor::ARG_PANEL ) );
		$this->assertSame( ProcedureDuplicate::NOTICE, $this->query_arg( (string) $url, ProcedureEditor::ARG_NOTICE ) );
		$this->assertSame( (string) $id, $this->query_arg( (string) $url, ProcedureDuplicate::ARG_SOURCE ) );
		$this->assertSame( '1', $this->query_arg( (string) $url, ProcedureDuplicate::ARG_NO_JS ), 'quien gestiona no copia el JavaScript' );
		$this->assertSame( '', $this->query_arg( (string) $url, ProcedureDuplicate::ARG_NO_CONTACTS ), 'con los mismos ámbitos, los contactos se copian' );

		foreach ( array(
			ProcedureMetaKeys::CONTACT_EMAILS,
			ProcedureMetaKeys::HEADER_COLOR,
			ProcedureMetaKeys::AUDIENCE,
			ProcedureMetaKeys::OWNERSHIP,
			ProcedureMetaKeys::CENTRE_KINDS,
			ProcedureMetaKeys::CENTRE_TYPES,
			ProcedureMetaKeys::ISLANDS,
			ProcedureMetaKeys::ALLOWED_CENTRES,
			ProcedureMetaKeys::REQUIRES_COORDINATOR,
			ProcedureMetaKeys::COORDINATOR_COUNT,
			ProcedureMetaKeys::COMMITMENTS,
			ProcedureMetaKeys::CUSTOM_CSS,
			ProcedureMetaKeys::NOTIFY,
			ProcedureMetaKeys::OBSERVABLE,
			ProcedureMetaKeys::OBSERVER_FILES,
			ProcedureMetaKeys::OBSERVER_EXPORT,
			ProcedureMetaKeys::OBSERVER_NOTIFY,
		) as $clave ) {
			$this->assertSame( get_post_meta( $id, $clave, true ), get_post_meta( $copia, $clave, true ), 'se copia ' . $clave );
		}
		foreach ( array(
			ProcedureMetaKeys::OPENS_AT,
			ProcedureMetaKeys::CLOSES_AT,
			ProcedureMetaKeys::AMEND_OPENS_AT,
			ProcedureMetaKeys::AMEND_CLOSES_AT,
			ProcedureMetaKeys::RESOLUTION_URL,
			ProcedureMetaKeys::PROVISIONAL_LIST_URL,
			ProcedureMetaKeys::FINAL_LIST_URL,
			ProcedureMetaKeys::APPLICATIONS_CLOSED,
			ProcedureMetaKeys::ARCHIVED,
			ProcedureMetaKeys::CUSTOM_JS,
		) as $clave ) {
			$this->assertFalse( metadata_exists( 'post', $copia, $clave ), 'no se copia ' . $clave );
		}
		$this->assertFalse( metadata_exists( 'post', $copia, '_edit_lock' ), 'ni el bloqueo de edición' );

		// Las preguntas, con claves nuevas desde q1; la entrega, sin fechas.
		$preguntas = Applications::questions( $copia );
		$this->assertSame( array( 'q1', 'q2', 'q3' ), array_column( $preguntas, 'key' ) );
		$this->assertSame( array( 'Proyecto', 'Memoria final', 'Alumnado por etapa' ), array_column( $preguntas, 'label' ) );
		$this->assertSame( 3, (int) get_post_meta( $copia, ProcedureMetaKeys::QUESTIONS_SEQ, true ) );
		$this->assertTrue( ProcedureQuestions::is_delivery( $preguntas[1] ) );
		$this->assertSame( '', $preguntas[1]['opens_at'] );
		$this->assertSame( '', $preguntas[1]['closes_at'] );
		$this->assertSame( array( 'r1', 'r2' ), array_column( $preguntas[2]['rows'], 'key' ) );

		// La consulta apunta a las preguntas nuevas.
		$this->assertSame( array( 'centre', 'q2', 'q3:r2', 'q1' ), get_post_meta( $copia, ProcedureMetaKeys::OBSERVER_COLUMNS, true ) );

		// Las personas, sin su ventana.
		$spec = get_post_meta( $copia, ProcedureMetaKeys::PEOPLE_SPEC, true );
		$this->assertTrue( $spec['enabled'] );
		$this->assertSame( array( 'r1' ), array_column( $spec['roles'], 'key' ) );
		$this->assertSame( '', $spec['opens_at'] );
		$this->assertSame( '', $spec['closes_at'] );
	}

	/**
	 * El duplicado no comparte ninguna solicitud, respuesta, documento,
	 * persona ni historial.
	 */
	public function test_the_duplicate_shares_no_application() {
		$area      = $this->area( 'Área de prueba' );
		$uid       = $this->manager( array( $area ) );
		$id        = $this->completo( $uid, array( $area ) );
		$solicitud = $this->application(
			$id,
			$this->school_head( '99000001' ),
			'99000001',
			array(
				ApplicationMetaKeys::ANSWERS => array( 'q3' => 'Nuestro proyecto' ),
				ApplicationMetaKeys::FILES   => array( 'q7' => array( 'name' => 'memoria.pdf' ) ),
				ApplicationMetaKeys::PEOPLE  => array(
					array(
						'id'   => 'aaaaaaaaaaaa',
						'name' => 'Ana',
					),
				),
				ApplicationMetaKeys::LOG     => array(
					array(
						'at'    => '2026-01-11T10:00:00Z',
						'event' => 'person_added',
					),
				),
			)
		);

		$copia = $this->copia_de( $this->duplicar( $uid, $id ) );

		$this->assertSame( array(), get_children( array( 'post_parent' => $copia ) ) );
		$this->assertSame( 0, Applications::count( $copia ) );
		$this->assertSame( array(), Applications::answered_keys( $copia, true ) );
		$this->assertSame( $id, (int) get_post_field( 'post_parent', $solicitud ), 'la solicitud sigue en el original' );
		$this->assertSame( 1, Applications::count( $id ) );
	}

	/**
	 * El original queda exactamente igual: metas, campos y términos.
	 */
	public function test_the_original_is_left_byte_for_byte() {
		$area = $this->area( 'Área de prueba' );
		$uid  = $this->manager( array( $area ) );
		$id   = $this->completo( $uid, array( $area ) );
		$this->application( $id, $this->school_head( '99000001' ), '99000001' );

		$foto  = function () use ( $id ): array {
			wp_cache_flush();
			global $wpdb;
			return array(
				'post'  => $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE ID = %d", $id ), ARRAY_A ), // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				'meta'  => $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id = %d ORDER BY meta_id", $id ), ARRAY_A ), // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				'terms' => wp_get_object_terms( $id, array( ProcedureTaxonomies::AREA, ProcedureTaxonomies::COURSE ), array( 'fields' => 'ids' ) ),
			);
		};
		$antes = $foto();

		$this->copia_de( $this->duplicar( $uid, $id ) );

		$this->assertSame( $antes, $foto() );
	}

	// ─── en cualquier estado ───────────────────────────────────────────────

	/**
	 * Un histórico se duplica, aunque ya no se edite; el duplicado nace abierto.
	 */
	public function test_an_archived_procedure_is_duplicated() {
		$area = $this->area( 'Área de prueba' );
		$uid  = $this->manager( array( $area ) );
		$id   = $this->completo( $uid, array( $area ), array( ProcedureMetaKeys::ARCHIVED => true ) );
		$this->assertFalse( ProcedureAccess::can_edit( $uid, $id ) );
		$this->assertTrue( ProcedureAccess::can_duplicate( $uid, $id ) );

		$copia = $this->copia_de( $this->duplicar( $uid, $id ) );

		$this->assertFalse( ProcedureAccess::is_archived( $copia ) );
		$this->assertTrue( ProcedureAccess::can_edit( $uid, $copia ) );
		$this->assertTrue( ProcedureAccess::is_archived( $id ), 'y el original sigue cerrado' );
	}

	/**
	 * Duplicar no necesita el bloqueo de edición del original: otra persona
	 * puede tenerlo abierto.
	 */
	public function test_somebody_elses_lock_does_not_stop_a_duplicate() {
		$area = $this->area( 'Área de prueba' );
		$uid  = $this->manager( array( $area ) );
		$otra = $this->manager( array( $area ) );
		$id   = $this->completo( $uid, array( $area ) );
		require_once ABSPATH . 'wp-admin/includes/post.php';
		wp_set_current_user( $otra );
		wp_set_post_lock( $id );

		$copia = $this->copia_de( $this->duplicar( $uid, $id ) );

		$this->assertGreaterThan( 0, $copia );
		$this->assertSame( $otra, (int) wp_check_post_lock( $id ), 'el bloqueo del original sigue donde estaba' );
	}

	/**
	 * Quien duplica desde el taller del original tenía su bloqueo: al irse al
	 * duplicado lo suelta, para que nadie vea el original ocupado un par de
	 * minutos por alguien que ya no está en él.
	 */
	public function test_duplicating_releases_the_originals_lock_of_who_duplicates() {
		$area = $this->area( 'Área de prueba' );
		$uid  = $this->manager( array( $area ) );
		$otra = $this->manager( array( $area ) );
		$id   = $this->completo( $uid, array( $area ) );
		require_once ABSPATH . 'wp-admin/includes/post.php';
		wp_set_current_user( $uid );
		wp_set_post_lock( $id );

		$copia = $this->copia_de( $this->duplicar( $uid, $id ) );

		$this->assertGreaterThan( 0, $copia );
		$this->assertFalse( metadata_exists( 'post', $id, '_edit_lock' ), 'el original queda sin bloqueo' );
		wp_set_current_user( $otra );
		$this->assertSame( 0, (int) wp_check_post_lock( $id ), 'y otra persona lo ve libre' );
	}

	// ─── quién duplica ─────────────────────────────────────────────────────

	/**
	 * Otro ámbito, una cuenta sin ámbito o sin la capacidad: rechazado y sin
	 * crear nada.
	 */
	public function test_only_who_opens_it_and_creates_procedures_duplicates() {
		$mia   = $this->area( 'Área de prueba' );
		$otra  = $this->area( 'Otro ámbito' );
		$uid   = $this->manager( array( $mia ) );
		$id    = $this->completo( $uid, array( $mia ) );
		$ajena = $this->manager( array( $otra ) );
		$nadie = $this->manager();

		$this->assertFalse( ProcedureAccess::can_duplicate( $ajena, $id ) );
		$this->assertFalse( ProcedureAccess::can_duplicate( $nadie, $id ) );
		$this->assertFalse( ProcedureAccess::can_duplicate( $this->school_head( '99000001' ), $id ) );
		$this->assertFalse( ProcedureAccess::can_duplicate( $uid, $this->application( $id, $this->school_head( '99000002' ), '99000002' ) ), 'una solicitud no es un procedimiento' );

		$this->assertFalse( ProcedureAccess::can_duplicate( $this->observer( array( '99000099' ) ), $id ), 'quien consulta no duplica' );

		$this->assertNull( $this->duplicar( $ajena, $id ) );
		$this->assertNull( $this->duplicar( $nadie, $id ) );
		$this->assertSame( array(), $this->otros( array( $id ) ), 'no se creó nada' );

		$this->acting_as( $ajena );
		$_GET = array( ProcedureEditor::ARG_PROCEDURE => (string) $id );
		$this->assertStringContainsString( 'otro ámbito', ProcedureEditor::model()['aviso'] );
	}

	/**
	 * Un procedimiento en la papelera no se duplica: primero se restaura.
	 */
	public function test_a_trashed_procedure_is_not_duplicated() {
		$area = $this->area( 'Área de prueba' );
		$uid  = $this->manager( array( $area ) );
		$id   = $this->completo( $uid, array( $area ) );
		wp_trash_post( $id );

		$this->assertFalse( ProcedureAccess::can_duplicate( $uid, $id ) );
		$this->assertFalse( ProcedureAccess::can_duplicate( $this->administrator(), $id ) );
		$this->assertNull( $this->duplicar( $uid, $id ) );
		$this->assertSame( array(), $this->otros( array( $id ) ) );
	}

	/**
	 * Con un subámbito: los ámbitos del duplicado son los del original que
	 * quien duplica puede usar.
	 */
	public function test_the_duplicate_keeps_only_the_areas_its_author_may_use() {
		$padre = $this->area( 'Servicio de prueba' );
		$hija  = $this->area( 'Área hija', $padre );
		$tia   = $this->area( 'Otra área' );

		// Compartido entre el servicio y su área: quien está en el área se queda con la suya.
		$de_hija = $this->manager( array( $hija ) );
		$id      = $this->completo( $de_hija, array( $padre, $hija, $tia ) );
		$url     = $this->duplicar( $de_hija, $id );
		$copia   = $this->copia_de( $url );
		$this->assertSame( array( $hija ), ProcedureAccess::post_areas( $copia ) );

		// Con menos ámbitos que el original, sus contactos no se copian: el
		// correo de alta no llega a un ámbito que ya no participa.
		$this->assertFalse( metadata_exists( 'post', $copia, ProcedureMetaKeys::CONTACT_EMAILS ) );
		$this->assertSame( '1', $this->query_arg( (string) $url, ProcedureDuplicate::ARG_NO_CONTACTS ) );
		$this->acting_as( $de_hija );
		$aviso = ProcedureDuplicate::notice( wp_parse_args( (string) wp_parse_url( (string) $url, PHP_URL_QUERY ) ) );
		$this->assertStringContainsString( 'indique los correos de contacto', $aviso['texto'] );
		$this->assertNotContains( 'ambito@example.org', $this->destinos_de_alta() );

		// Quien está en el servicio alcanza a sus áreas.
		$de_padre = $this->manager( array( $padre ) );
		$en_hija  = $this->completo( $de_padre, array( $hija ) );
		$copia    = $this->copia_de( $this->duplicar( $de_padre, $en_hija ) );
		$this->assertSame( array( $hija ), ProcedureAccess::post_areas( $copia ) );

		// El área no alcanza a lo que solo es del servicio: no lo abre ni lo duplica.
		$del_padre = $this->completo( $de_padre, array( $padre ) );
		$this->assertFalse( ProcedureAccess::can_duplicate( $de_hija, $del_padre ) );
		$this->assertNull( $this->duplicar( $de_hija, $del_padre ) );
	}

	/**
	 * `can_duplicate()` dice también bajo qué ámbitos: los del original que
	 * puede usar. Sin ninguno —solo pasa con un original sin ámbito y quien
	 * trabaja en todos—, el suyo, como en un alta normal; y sin el suyo, no
	 * se duplica.
	 */
	public function test_can_duplicate_says_under_which_areas() {
		$mia  = $this->area( 'Área de prueba' );
		$otra = $this->area( 'Otro ámbito' );
		$uid  = $this->manager( array( $mia ) );

		$areas = null;
		$this->assertTrue( ProcedureAccess::can_duplicate( $uid, $this->completo( $uid, array( $mia, $otra ) ), $areas ) );
		$this->assertSame( array( $mia ), $areas, 'de los dos, el que puede usar' );

		$sin = $this->procedure( $uid, array() );
		wp_set_object_terms( $sin, array(), ProcedureTaxonomies::AREA );
		$this->assertFalse( ProcedureAccess::can_duplicate( $uid, $sin, $areas ) );
		$this->assertNull( $areas );

		$todas = $this->manager( array( $otra ) );
		get_userdata( $todas )->add_cap( ProcedureAccess::CAP_ALL_AREAS );
		$this->assertTrue( ProcedureAccess::can_duplicate( $todas, $sin, $areas ) );
		$this->assertSame( array( $otra ), $areas, 'el suyo' );

		$admin = $this->administrator();
		$this->assertTrue( ProcedureAccess::can_duplicate( $admin, $sin, $areas ) );
		$this->assertSame( array(), $areas );
		$antes = $this->otros( array() );
		$this->assertNull( $this->duplicar( $admin, $sin ), 'sin ámbito que poner, no se duplica' );
		$this->assertSame( $antes, $this->otros( array() ) );
	}

	/**
	 * Administración duplica cualquiera, con todos sus ámbitos y su JavaScript.
	 */
	public function test_administration_duplicates_anything_with_its_javascript() {
		$una   = $this->area( 'Área de prueba' );
		$otra  = $this->area( 'Otro ámbito' );
		$admin = $this->administrator();
		$id    = $this->completo( $this->manager( array( $una ) ), array( $una, $otra ), array( ProcedureMetaKeys::CUSTOM_JS => 'console.log(1);' ) );

		$url   = $this->duplicar( $admin, $id );
		$copia = $this->copia_de( $url );

		$this->assertEqualsCanonicalizing( array( $una, $otra ), ProcedureAccess::post_areas( $copia ) );
		$this->assertSame( 'console.log(1);', get_post_meta( $copia, ProcedureMetaKeys::CUSTOM_JS, true ) );
		$this->assertSame( '', $this->query_arg( (string) $url, ProcedureDuplicate::ARG_NO_JS ) );
	}

	// ─── el envío ──────────────────────────────────────────────────────────

	/**
	 * Un nonce falso o ninguno: nada.
	 */
	public function test_a_bad_nonce_duplicates_nothing() {
		$area = $this->area( 'Área de prueba' );
		$uid  = $this->manager( array( $area ) );
		$id   = $this->completo( $uid, array( $area ) );

		$this->assertNull( $this->duplicar( $uid, $id, 'basura' ) );
		$this->assertNull( $this->duplicar( $uid, $id, '' ) );
		$this->assertSame( array(), $this->otros( array( $id ) ) );
	}

	/**
	 * Enviar dos veces da dos duplicados, no un error.
	 */
	public function test_submitting_twice_gives_two_duplicates() {
		$area = $this->area( 'Área de prueba' );
		$uid  = $this->manager( array( $area ) );
		$id   = $this->completo( $uid, array( $area ) );

		$una  = $this->copia_de( $this->duplicar( $uid, $id ) );
		$otra = $this->copia_de( $this->duplicar( $uid, $id ) );

		$this->assertNotSame( $una, $otra );
		$this->assertEqualsCanonicalizing( array( $una, $otra ), $this->otros( array( $id ) ) );
	}

	/**
	 * Duplicar es un alta: el correo de alta sale una vez, a cada destinatario.
	 */
	public function test_the_creation_mail_goes_out_once() {
		$area           = $this->area( 'Área de prueba' );
		$uid            = $this->manager( array( $area ) );
		$id             = $this->completo( $uid, array( $area ) );
		$this->enviados = array();
		$antes          = did_action( 'prc_procedure_created' );

		$copia = $this->copia_de( $this->duplicar( $uid, $id ) );

		$this->assertSame( 1, did_action( 'prc_procedure_created' ) - $antes );
		$altas = array_values(
			array_filter(
				$this->enviados,
				static function ( array $correo ): bool {
					return false !== strpos( (string) $correo['subject'], 'Procedimiento creado en borrador: Red de prueba (copia)' );
				}
			)
		);
		$this->assertNotEmpty( $altas );
		$destinos = array();
		foreach ( $altas as $correo ) {
			$destinos = array_merge( $destinos, (array) $correo['to'] );
		}
		$this->assertContains( 'ambito@example.org', $destinos, 'a los contactos copiados' );
		$this->assertSame( array_unique( $destinos ), $destinos, 'una vez a cada destinatario' );
		$this->assertStringContainsString( (string) $copia, (string) $altas[0]['message'], 'con el enlace al taller del duplicado' );
		$this->assertStringContainsString( 'Falta el curso', (string) $altas[0]['message'], 'y lo que falta para publicarlo' );
		$this->assertStringContainsString( 'La entrega «Memoria final» no tiene fechas', (string) $altas[0]['message'] );
	}

	// ─── después ───────────────────────────────────────────────────────────

	/**
	 * El taller del duplicado lo dice, y no deja publicarlo sin curso ni con
	 * una entrega sin fechas.
	 */
	public function test_the_duplicate_is_not_published_until_it_has_a_course_and_delivery_dates() {
		$area  = $this->area( 'Área de prueba' );
		$uid   = $this->manager( array( $area ) );
		$id    = $this->completo( $uid, array( $area ), array( ProcedureMetaKeys::CUSTOM_JS => 'console.log(1);' ) );
		$url   = $this->duplicar( $uid, $id );
		$copia = $this->copia_de( $url );

		$this->acting_as( $uid );
		$_GET = wp_parse_args( (string) wp_parse_url( (string) $url, PHP_URL_QUERY ) );
		$m    = ProcedureEditor::model();
		$this->assertSame( ProcedureEditor::PANEL_DATA, $m['panel'] );
		$this->assertSame( 'ok', $m['flash']['tipo'] );
		$this->assertStringStartsWith( 'Ha duplicado «Red de prueba». Revise las fechas y el curso antes de publicarlo.', $m['flash']['texto'] );
		$this->assertStringContainsString( 'no tiene permiso para el código JavaScript de este procedimiento', $m['flash']['texto'] );
		$this->assertStringNotContainsString( 'administra', $m['flash']['texto'] );
		$this->assertCount( 3, $m['not_ready'] );
		$this->assertStringContainsString( 'curso', $m['not_ready'][0] );
		$this->assertStringContainsString( 'plazo de solicitud', $m['not_ready'][1], 'el duplicado nace sin plazos' );
		$this->assertStringContainsString( '«Memoria final»', $m['not_ready'][2] );
		$this->assertFalse( $m['can_publish'], 'sin el botón de publicar' );
		$html = ProcedureEditorView::html( $m );
		$this->assertStringContainsString( 'Falta el curso', $html );

		// Por POST tampoco.
		$this->assertNull( $this->submit_publish( $uid, $copia ) );
		$this->assertSame( 'draft', get_post_status( $copia ) );

		// Con curso y plazo, todavía falta la entrega.
		wp_set_object_terms( $copia, array( $this->course( '2027-2028' ) ), ProcedureTaxonomies::COURSE );
		update_post_meta( $copia, ProcedureMetaKeys::OPENS_AT, '2027-04-01' );
		$this->assertNull( $this->submit_publish( $uid, $copia ) );
		$this->assertSame( 'draft', get_post_status( $copia ) );

		// Con las fechas puestas, se publica.
		$preguntas                 = Applications::questions( $copia );
		$preguntas[1]['opens_at']  = '2027-05-01';
		$preguntas[1]['closes_at'] = '2027-05-31';
		update_post_meta( $copia, ProcedureMetaKeys::QUESTIONS, $preguntas );
		$this->assertNotNull( $this->submit_publish( $uid, $copia ) );
		$this->assertSame( 'publish', get_post_status( $copia ) );
	}

	/**
	 * Publicar.
	 *
	 * @param int $uid Who.
	 * @param int $id  Procedure.
	 * @return string|null
	 */
	private function submit_publish( int $uid, int $id ): ?string {
		\Closure::bind(
			static function (): void {
				self::$rejected = '';
			},
			null,
			ProcedureEditor::class
		)();
		$this->acting_as( $uid );
		$this->post(
			array(
				ProcedureEditor::FIELD_DO        => ProcedureEditor::OP_PUBLISH,
				ProcedureEditor::FIELD_PROCEDURE => (string) $id,
			),
			ProcedureEditor::nonce_action( ProcedureEditor::OP_PUBLISH ),
			ProcedureEditor::nonce_name( ProcedureEditor::OP_PUBLISH )
		);
		return $this->exit_url( array( ProcedureEditor::class, 'handle' ) );
	}

	/**
	 * Sin JavaScript que dejar atrás, el aviso no lo menciona; y sin un
	 * original que se pueda abrir, no lo nombra.
	 */
	public function test_the_notice_only_says_what_applies() {
		$area = $this->area( 'Área de prueba' );
		$uid  = $this->manager( array( $area ) );
		$id   = $this->completo( $uid, array( $area ) );
		$this->acting_as( $uid );

		$aviso = ProcedureDuplicate::notice( array( ProcedureDuplicate::ARG_SOURCE => (string) $id ) );
		$this->assertSame( 'ok', $aviso['tipo'] );
		$this->assertStringStartsWith( 'Ha duplicado «Red de prueba».', $aviso['texto'] );
		$this->assertStringNotContainsString( 'JavaScript', $aviso['texto'] );

		$ajena = $this->manager( array( $this->area( 'Otro ámbito' ) ) );
		$this->acting_as( $ajena );
		$aviso = ProcedureDuplicate::notice( array( ProcedureDuplicate::ARG_SOURCE => (string) $id ) );
		$this->assertStringNotContainsString( 'Red de prueba', $aviso['texto'] );
		$this->assertStringStartsWith( 'Ha duplicado el procedimiento.', $aviso['texto'] );
	}

	// ─── dónde está el botón ───────────────────────────────────────────────

	/**
	 * El taller ofrece duplicar en cualquier estado, también en solo lectura,
	 * con confirmación.
	 */
	public function test_the_workshop_offers_to_duplicate_even_read_only() {
		$area = $this->area( 'Área de prueba' );
		$uid  = $this->manager( array( $area ) );
		$id   = $this->completo( $uid, array( $area ), array( ProcedureMetaKeys::ARCHIVED => true ) );

		$this->acting_as( $uid );
		$_GET = array( ProcedureEditor::ARG_PROCEDURE => (string) $id );
		$m    = ProcedureEditor::model();
		$this->assertTrue( $m['can_duplicate'] );
		$this->assertFalse( $m['can_edit'] );

		$html = ProcedureEditorView::html( $m );
		$this->assertMatchesRegularExpression( '/<form[^>]+data-prc-confirm="¿Duplicar «Red de prueba»\?[^"]*"[^>]+data-prc-confirm-ok="Duplicar" data-prc-once>/u', $html );
		$this->assertStringContainsString( 'name="' . ProcedureEditor::FIELD_DO . '" value="' . ProcedureEditor::OP_DUPLICATE . '"', $html );
		$this->assertStringContainsString( 'name="' . ProcedureEditor::nonce_name( ProcedureEditor::OP_DUPLICATE ) . '"', $html );
		$this->assertLessThan( strpos( $html, 'prc-solo-lectura' ), strpos( $html, ProcedureEditor::OP_DUPLICATE ), 'fuera de lo que el solo lectura apaga' );
	}

	/**
	 * Cada fila de «Gestión» ofrece duplicar, con confirmación y hacia el
	 * taller del original, que es quien explica un rechazo.
	 */
	public function test_each_row_of_the_workspace_offers_to_duplicate() {
		$area = $this->area( 'Área de prueba' );
		$uid  = $this->manager( array( $area ) );
		$id   = $this->completo( $uid, array( $area ) );
		$otro = $this->procedure( $uid, array( $area ), array(), array( 'post_title' => 'Otro programa' ) );
		wp_set_object_terms( $otro, array( $this->curso() ), ProcedureTaxonomies::COURSE );

		$this->acting_as( $uid );
		$_GET   = array();
		$modelo = Workspace::model();
		foreach ( $modelo['rows'] as $fila ) {
			$this->assertSame( ProcedureAccess::can_duplicate( $uid, (int) $fila['id'] ), $fila['can_duplicate'], 'lo mismo que dice el guardián' );
		}
		$html = WorkspaceView::html( $modelo );

		$this->assertSame( 2, substr_count( $html, 'value="' . ProcedureEditor::OP_DUPLICATE . '"' ) );
		$this->assertSame( 2, substr_count( $html, 'data-prc-once' ), 'un solo envío por botón' );
		$this->assertStringContainsString( '¿Duplicar «Red de prueba»?', $html );
		$this->assertStringContainsString( '¿Duplicar «Otro programa»?', $html );
		$this->assertSame( 2, substr_count( $html, '>Duplicar</button>' ), 'el botón dice lo que hace, con texto' );
		$this->assertStringContainsString( 'name="' . ProcedureEditor::FIELD_PROCEDURE . '" value="' . $otro . '"', $html );
		$this->assertStringContainsString( 'action="' . esc_url( ProcedureEditor::url( $id ) ) . '"', $html );
		$this->assertStringContainsString( 'name="' . ProcedureEditor::nonce_name( ProcedureEditor::OP_DUPLICATE ) . '"', $html );
		$this->assertStringNotContainsString( 'id="' . ProcedureEditor::nonce_name( ProcedureEditor::OP_DUPLICATE ) . '"', $html, 'sin id repetido en cada fila' );

		// Una fila que no se puede duplicar no lleva el botón.
		$modelo['rows'][0]['can_duplicate'] = false;
		$this->assertSame( 1, substr_count( WorkspaceView::html( $modelo ), 'value="' . ProcedureEditor::OP_DUPLICATE . '"' ) );
	}
}
