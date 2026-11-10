<?php
/**
 * Tests for the application screen of a school: what it paints and what it does.
 *
 * @package Prc
 */

use Prc\Domain\ProcedureQuestions;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PublicFront\ApplyForm;
use Prc\PublicFront\Applications;
use Prc\PublicFront\View\ApplyFormView;
use Prc\PublicFront\View\ProcedureChrome;

/**
 * La solicitud de un centro, desde la pantalla.
 *
 * Lo que se comprueba aquí y en ningún otro sitio: que la pantalla falla en
 * cerrado —sin sesión, sin capacidad, sin código de centro no hay formulario,
 * y un POST directo tampoco guarda nada—, que el centro sale de solo lectura
 * porque viene de la cuenta (ADR-0016), que el plazo se respeta en el POST y
 * no solo en el botón, y que en subsanación solo edita el centro al que se le
 * pidió (ADR-0020).
 *
 * Lo que ya se prueba en `test-applications.php` —una solicitud por centro y
 * procedimiento, la revisión, las columnas— no se repite: aquí se mira lo que
 * añade la pantalla.
 */
class Test_Apply_Form extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Con el aplicativo, sus páginas, un catálogo inventado y sin rechazos heredados.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
		$this->pages();
		remove_all_filters( 'prc_centres' );
		add_filter( 'prc_centres', array( $this, 'catalogo' ) );
		$this->olvidar_rechazo();
	}

	/**
	 * Dos centros de prueba, uno de cada titularidad.
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
				'ownership' => ProcedureMetaKeys::OWNERSHIP_PRIVATE,
			),
		);
	}

	/**
	 * El motivo del último envío rechazado vive en una estática: en producción
	 * muere con la petición; aquí hay que soltarlo entre tests.
	 *
	 * @return void
	 */
	private function olvidar_rechazo(): void {
		\Closure::bind(
			static function (): void {
				self::$rejected = '';
			},
			null,
			ApplyForm::class
		)();
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
	 * Un procedimiento publicado con el plazo abierto hoy.
	 *
	 * @param array<string, mixed> $meta Meta key => value to add or override.
	 * @return int
	 */
	private function procedimiento( array $meta = array() ): int {
		return $this->procedure(
			$this->administrator(),
			array( $this->area() ),
			array_merge(
				array(
					ProcedureMetaKeys::OPENS_AT  => $this->dia( -1 ),
					ProcedureMetaKeys::CLOSES_AT => $this->dia( 1 ),
				),
				$meta
			),
			array( 'post_title' => 'Red de prueba' )
		);
	}

	/**
	 * Un procedimiento con las cuatro clases de pregunta, coordinación y compromisos.
	 *
	 * @return int
	 */
	private function procedimiento_completo(): int {
		return $this->procedimiento(
			array(
				ProcedureMetaKeys::REQUIRES_COORDINATOR => true,
				ProcedureMetaKeys::COMMITMENTS          => 'El centro se compromete a sostener la red.',
				ProcedureMetaKeys::QUESTIONS            => array(
					array(
						'label' => 'Observaciones',
						'type'  => ProcedureQuestions::TYPE_TEXT,
					),
					array(
						'label' => 'Tiene huerto',
						'type'  => ProcedureQuestions::TYPE_YESNO,
					),
					array(
						'label'   => 'Modalidad',
						'type'    => ProcedureQuestions::TYPE_SINGLE,
						'choices' => array( 'A', 'B' ),
					),
					array(
						'label'   => 'Etapas',
						'type'    => ProcedureQuestions::TYPE_MULTIPLE,
						'choices' => array( 'Infantil', 'Primaria' ),
					),
				),
			)
		);
	}

	/**
	 * El modelo de la pantalla abierta sobre un procedimiento.
	 *
	 * @param int $procedure_id Procedure ID.
	 * @return array<string, mixed>
	 */
	private function modelo( int $procedure_id ): array {
		$_GET[ ApplyForm::ARG_PROCEDURE ] = (string) $procedure_id;
		return ApplyForm::model();
	}

	/**
	 * La pantalla pintada.
	 *
	 * @param int $procedure_id Procedure ID.
	 * @return string
	 */
	private function pintar( int $procedure_id ): string {
		return ApplyFormView::html( $this->modelo( $procedure_id ) );
	}

	/**
	 * Un envío del formulario, y por dónde salió.
	 *
	 * @param int                  $uid          Who submits.
	 * @param int                  $procedure_id Procedure ID.
	 * @param array<string, mixed> $campos       Fields to add or override.
	 * @param string|null          $nonce        Null for the good one, a string to forge it.
	 * @return string|null La URL de vuelta; null si el envío se rechazó.
	 */
	private function enviar( int $uid, int $procedure_id, array $campos = array(), ?string $nonce = null ): ?string {
		$this->acting_as( $uid );
		$campos = array_merge(
			array(
				ApplyForm::FIELD_OP        => ApplyForm::OP_APPLY,
				ApplyForm::FIELD_PROCEDURE => (string) $procedure_id,
				ApplyForm::FIELD_POSITION  => ApplicationMetaKeys::POSITION_HEAD,
			),
			$campos
		);
		if ( null === $nonce ) {
			$this->post( $campos, ApplyForm::NONCE_ACTION, ApplyForm::NONCE_FIELD );
		} else {
			$campos[ ApplyForm::NONCE_FIELD ] = $nonce;
			$this->post( $campos );
		}
		return $this->exit_url( array( ApplyForm::class, 'handle' ) );
	}

	/**
	 * Un núcleo guardado, como lo deja ApplicationInput::validate() en `data`.
	 *
	 * @param array<string, mixed> $cambios What to override.
	 * @return array<string, mixed>
	 */
	private function nucleo( array $cambios = array() ): array {
		return array_merge(
			array(
				'position'    => ApplicationMetaKeys::POSITION_HEAD,
				'coordinator' => array(
					'name'  => '',
					'email' => '',
				),
				'answers'     => array(),
			),
			$cambios
		);
	}

	// ─── quién entra ───────────────────────────────────────────────────────

	/**
	 * Sin sesión no se enseña el formulario: se pide entrar.
	 */
	public function test_the_screen_asks_for_a_session() {
		$procedimiento = $this->procedimiento();
		$this->acting_as( 0 );

		$m = $this->modelo( $procedimiento );
		$this->assertStringContainsString( 'Debe iniciar sesión', $m['aviso'] );
		$this->assertFalse( $m['can_submit'] );
		$this->assertStringNotContainsString( '<form', ApplyFormView::html( $m ) );
	}

	/**
	 * Y un perfil que no presenta solicitudes tampoco la ve, aunque tenga sesión.
	 */
	public function test_the_screen_asks_for_the_capability() {
		$procedimiento = $this->procedimiento();
		$this->acting_as( $this->manager( array( $this->area() ) ) );

		$m = $this->modelo( $procedimiento );
		$this->assertStringContainsString( 'Su perfil no presenta solicitudes de centro', $m['aviso'] );
		$this->assertStringNotContainsString( '<form', ApplyFormView::html( $m ) );
	}

	/**
	 * Sin código de centro se dice, no se ofrece el formulario y un POST
	 * directo tampoco guarda nada: el centro viene de la cuenta (ADR-0016) y
	 * no se teclea, así que sin él no hay por dónde seguir.
	 */
	public function test_without_a_school_code_it_says_so_and_offers_no_form() {
		$procedimiento = $this->procedimiento();
		$sin_centro    = $this->school_head();
		$this->acting_as( $sin_centro );

		$m = $this->modelo( $procedimiento );
		$this->assertStringContainsString( 'no tiene centro asignado', $m['aviso'] );
		$this->assertSame( '', $m['centre']['code'] );
		$this->assertFalse( $m['can_submit'] );
		$this->assertStringNotContainsString( '<form', ApplyFormView::html( $m ) );

		$this->assertNull( $this->enviar( $sin_centro, $procedimiento ), 'un envío rechazado no redirige' );
		$this->assertSame( 0, Applications::count( $procedimiento ) );
	}

	/**
	 * Un centro de una titularidad que el procedimiento no admite no solicita,
	 * aunque el plazo esté abierto: primero quién, luego cuándo.
	 */
	public function test_a_school_of_an_ownership_the_procedure_does_not_take_cannot_apply() {
		$procedimiento = $this->procedimiento(
			array( ProcedureMetaKeys::OWNERSHIP => array( ProcedureMetaKeys::OWNERSHIP_PUBLIC ) )
		);
		$privado       = $this->school_head( 'C0002' );
		$this->acting_as( $privado );

		$m = $this->modelo( $procedimiento );
		$this->assertFalse( $m['can_submit'] );
		$this->assertSame( 'Este procedimiento es solo para centros públicos.', $m['why'] );
		$this->assertStringNotContainsString( '<form', ApplyFormView::html( $m ) );

		$this->assertNull( $this->enviar( $privado, $procedimiento ) );
		$this->assertSame( 0, Applications::count( $procedimiento ) );

		$this->olvidar_rechazo();
		$this->assertNotNull( $this->enviar( $this->school_head( 'C0001' ), $procedimiento ), 'el público sí solicita' );
		$this->assertSame( 1, Applications::count( $procedimiento ) );
	}

	/**
	 * La pantalla de «no puede» no se queda en el aviso: dice a quién
	 * escribir, vuelve a la ficha y lleva a la ayuda.
	 */
	public function test_the_refused_screen_says_what_to_do_next() {
		$procedimiento = $this->procedimiento(
			array(
				ProcedureMetaKeys::OWNERSHIP      => array( ProcedureMetaKeys::OWNERSHIP_PUBLIC ),
				ProcedureMetaKeys::CONTACT_EMAILS => array( 'contacto@example.org' ),
			)
		);
		$this->acting_as( $this->school_head( 'C0002' ) );

		$m = $this->modelo( $procedimiento );
		$this->assertSame( 'Este procedimiento es solo para centros públicos. Si cree que es un error, escriba a contacto@example.org.', $m['why'] );
		$html = ApplyFormView::html( $m );
		$this->assertStringContainsString( 'Volver a la ficha del procedimiento', $html );
		$this->assertStringContainsString( esc_url( (string) get_permalink( $procedimiento ) ), $html );
		$this->assertStringContainsString( 'Ayuda: cómo presentar una solicitud', $html );
		$this->assertStringContainsString( esc_url( \Prc\PublicFront\Help::url( \Prc\PublicFront\HelpContent::GUIDE_APPLY, 'antes-de-empezar' ) ), $html );
		$this->assertStringContainsString( 'Salir y entrar con otra cuenta', $html, 'el motivo es de la cuenta: se puede entrar con otra' );
		$this->assertSame( 1, substr_count( $html, 'contacto@example.org' ), 'el correo, una vez: ya va en el motivo' );

		// Con el plazo cerrado el motivo no es la cuenta: no se ofrece cambiarla,
		// pero sí el correo de contacto.
		$cerrado = $this->procedimiento(
			array(
				ProcedureMetaKeys::OPENS_AT       => $this->dia( -20 ),
				ProcedureMetaKeys::CLOSES_AT      => $this->dia( -10 ),
				ProcedureMetaKeys::CONTACT_EMAILS => array( 'contacto@example.org' ),
			)
		);
		$this->acting_as( $this->school_head( 'C0001' ) );
		$html = ApplyFormView::html( $this->modelo( $cerrado ) );
		$this->assertStringContainsString( 'Volver a la ficha del procedimiento', $html );
		$this->assertStringNotContainsString( 'Salir y entrar con otra cuenta', $html );
		$this->assertStringContainsString( 'mailto:contacto@example.org', $html );
	}

	/**
	 * Sin el perfil de dirección, la pantalla ofrece salir y entrar con otra cuenta.
	 */
	public function test_a_profile_that_does_not_apply_can_switch_accounts() {
		$procedimiento = $this->procedimiento();
		$this->acting_as( (int) self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$m    = $this->modelo( $procedimiento );
		$html = ApplyFormView::html( $m );
		$this->assertStringContainsString( 'cuenta genérica del centro', $html );
		$this->assertStringContainsString( 'Salir y entrar con otra cuenta', $html );
		$this->assertStringContainsString( esc_url( wp_logout_url( wp_login_url( ApplyForm::url( $procedimiento ) ) ) ), $html );
		$this->assertStringContainsString( 'Ayuda: cómo presentar una solicitud', $html );
	}

	/**
	 * La cabecera de la solicitud lleva a la ayuda de rellenarla.
	 */
	public function test_the_header_offers_help_to_fill_it_in() {
		$procedimiento = $this->procedimiento();
		$this->acting_as( $this->school_head( 'C0001' ) );
		$this->assertStringContainsString( '¿Dudas al rellenar? <a', $this->pintar( $procedimiento ) );
	}

	/**
	 * Acotado después de presentar: la elegibilidad se mira al crear, así que
	 * el centro que ya presentó su solicitud la sigue editando; otro que no la
	 * tiene no la crea, tampoco por un POST directo.
	 */
	public function test_narrowing_after_submitting_keeps_editing_but_not_creating() {
		$procedimiento = $this->procedimiento();
		$norte         = $this->school_head( 'C0001' );
		$this->assertNotNull( $this->enviar( $norte, $procedimiento ) );
		$solicitud = (int) Applications::find( $procedimiento, 'C0001' );
		$this->assertGreaterThan( 0, $solicitud );

		update_post_meta( $procedimiento, ProcedureMetaKeys::ALLOWED_CENTRES, array( 'C0002' ) );
		$this->olvidar_rechazo();

		$m = $this->modelo( $procedimiento );
		$this->assertTrue( $m['can_submit'], 'la suya, sí' );
		$this->assertSame( $solicitud, (int) $m['application']['id'] );

		$this->assertNotNull(
			$this->enviar(
				$norte,
				$procedimiento,
				array(
					ApplyForm::FIELD_POSITION => ApplicationMetaKeys::POSITION_SECRETARY,
					ApplyForm::FIELD_CENTRE   => 'C0001',
				)
			)
		);
		$this->assertSame( ApplicationMetaKeys::POSITION_SECRETARY, get_post_meta( $solicitud, ApplicationMetaKeys::APPLICANT_POSITION, true ) );
		$this->assertSame( 1, Applications::count( $procedimiento ) );

		// Un centro sin solicitud, del catálogo pero fuera de la lista: no la crea.
		$this->olvidar_rechazo();
		$publico = $this->school_head( 'C0002' );
		update_post_meta( $procedimiento, ProcedureMetaKeys::ALLOWED_CENTRES, array( 'C0003' ) );
		$this->acting_as( $publico );
		$this->assertSame( 'Este procedimiento es solo para una lista cerrada de centros, y el suyo no está en ella.', $this->modelo( $procedimiento )['why'] );
		$this->assertNull( $this->enviar( $publico, $procedimiento, array( ApplyForm::FIELD_CENTRE => 'C0002' ) ) );
		$this->assertSame( 1, Applications::count( $procedimiento ) );
	}

	/**
	 * Subsanar tras acotar: a quien se le pidió subsanar, subsana, aunque el
	 * procedimiento ya no lo admitiría como solicitud nueva.
	 */
	public function test_amending_after_narrowing() {
		$procedimiento = $this->procedimiento(
			array(
				ProcedureMetaKeys::OPENS_AT        => $this->dia( -20 ),
				ProcedureMetaKeys::CLOSES_AT       => $this->dia( -10 ),
				ProcedureMetaKeys::AMEND_OPENS_AT  => $this->dia( -1 ),
				ProcedureMetaKeys::AMEND_CLOSES_AT => $this->dia( 1 ),
				ProcedureMetaKeys::OWNERSHIP       => array( ProcedureMetaKeys::OWNERSHIP_PUBLIC ),
			)
		);
		$this->assertSame( ProcedureMetaKeys::STATE_AMENDMENT, ProcedureState::of_post( $procedimiento ) );
		$norte     = $this->school_head( 'C0001' );
		$solicitud = $this->application( $procedimiento, $norte, 'C0001', array( ApplicationMetaKeys::REVIEW_STATE => ApplicationMetaKeys::REVIEW_AMEND ) );

		update_post_meta( $procedimiento, ProcedureMetaKeys::OWNERSHIP, array( ProcedureMetaKeys::OWNERSHIP_PRIVATE ) );
		$this->assertNotNull( $this->enviar( $norte, $procedimiento, array( ApplyForm::FIELD_POSITION => ApplicationMetaKeys::POSITION_SECRETARY ) ) );
		$this->assertSame( ApplicationMetaKeys::POSITION_SECRETARY, get_post_meta( $solicitud, ApplicationMetaKeys::APPLICANT_POSITION, true ) );
		$this->assertSame( ApplicationMetaKeys::REVIEW_SUBMITTED, get_post_meta( $solicitud, ApplicationMetaKeys::REVIEW_STATE, true ), 'subsanada vuelve a presentada' );
	}

	/**
	 * La pantalla de solicitud construye el catálogo una sola vez.
	 */
	public function test_the_screen_builds_the_catalogue_once() {
		$procedimiento = $this->procedimiento( array( ProcedureMetaKeys::OWNERSHIP => array( ProcedureMetaKeys::OWNERSHIP_PUBLIC ) ) );
		$this->acting_as( $this->school_head( 'C0001' ) );
		$veces = 0;
		add_filter(
			'prc_centres',
			static function ( $centros ) use ( &$veces ) {
				++$veces;
				return $centros;
			},
			99
		);
		\Prc\Domain\CentreCatalog::flush();

		$m = $this->modelo( $procedimiento );
		$this->assertTrue( $m['can_submit'] );
		$this->assertSame( 1, $veces );

		update_option( \Prc\Domain\CentreCatalog::OPTION_PRIVATE_TYPES, array( 'Otro' ) );
		\Prc\Domain\CentreCatalog::find( 'C0001' );
		$this->assertSame( 2, $veces, 'cambiar los tipos privados lo vacía' );
		delete_option( \Prc\Domain\CentreCatalog::OPTION_PRIVATE_TYPES );
	}

	// ─── lo que pinta ──────────────────────────────────────────────────────

	/**
	 * El formulario pinta el núcleo fijo y las preguntas por su clase: el
	 * centro de solo lectura, el cargo, la coordinación y los compromisos.
	 */
	public function test_the_form_paints_the_fixed_core_and_the_questions() {
		$procedimiento = $this->procedimiento_completo();
		$this->acting_as( $this->school_head( 'C0001' ) );

		$html = $this->pintar( $procedimiento );

		$this->assertStringContainsString( 'value="C0001" readonly', $html, 'el código del centro' );
		$this->assertStringContainsString( 'value="Centro de prueba Norte" readonly', $html, 'y su denominación' );
		$this->assertStringNotContainsString( 'name="prc_centre', $html, 'el centro no se teclea' );

		$this->assertStringContainsString( 'name="' . ApplyForm::FIELD_POSITION . '"', $html );
		$this->assertStringContainsString( 'Jefatura de estudios', $html );

		$this->assertStringContainsString( 'name="' . ApplyForm::FIELD_COORDINATORS . '[0][name]"', $html );
		$this->assertStringContainsString( 'name="' . ApplyForm::FIELD_COORDINATORS . '[0][dni]"', $html );
		$this->assertStringContainsString( 'name="' . ApplyForm::FIELD_COORDINATORS . '[0][email]"', $html );

		$this->assertStringContainsString( 'El centro se compromete a sostener la red.', $html );
		$this->assertStringContainsString( 'name="' . ApplyForm::FIELD_ACCEPT . '"', $html );

		$this->assertStringContainsString( '<textarea id="prc-q-q1" name="' . ApplyForm::FIELD_ANSWERS . '[q1]"', $html, 'texto largo' );
		$this->assertStringContainsString( 'type="checkbox" id="prc-q-q2" name="' . ApplyForm::FIELD_ANSWERS . '[q2]"', $html, 'sí/no' );
		$this->assertStringContainsString( 'type="radio" id="prc-q-q3-0"', $html, 'una opción' );
		$this->assertStringContainsString( 'role="radiogroup"', $html, 'y el grupo se anuncia como tal' );
		$this->assertStringContainsString( 'name="' . ApplyForm::FIELD_ANSWERS . '[q4][]"', $html, 'varias opciones' );
		$this->assertStringContainsString( 'Infantil', $html );

		$this->assertStringContainsString( 'Presentar la solicitud', $html );
		$this->assertStringContainsString( ApplyForm::NONCE_FIELD, $html );
	}

	/**
	 * Lo que el procedimiento no pide no se pregunta.
	 */
	public function test_what_the_procedure_does_not_ask_is_not_painted() {
		$procedimiento = $this->procedimiento();
		$this->acting_as( $this->school_head( 'C0001' ) );

		$html = $this->pintar( $procedimiento );

		$this->assertStringContainsString( 'name="' . ApplyForm::FIELD_POSITION . '"', $html, 'el cargo es del núcleo y siempre está' );
		$this->assertStringNotContainsString( ApplyForm::FIELD_COORD_NAME, $html, 'sin coordinación pedida no se pregunta' );
		$this->assertStringNotContainsString( ApplyForm::FIELD_ACCEPT, $html, 'sin compromisos no hay casilla que marcar' );
		$this->assertStringNotContainsString( ApplyForm::FIELD_ANSWERS, $html, 'sin preguntas no hay preguntas' );
	}

	/**
	 * Y lo pinta con la forma de la pantalla que se sustituye: secciones con
	 * su rótulo en mayúsculas y su filete, retícula de doce columnas, campos
	 * bloqueados que se distinguen por el color y el botón de envío.
	 */
	public function test_the_screen_keeps_the_shape_of_the_one_it_replaces() {
		$procedimiento = $this->procedimiento_completo();
		$this->acting_as( $this->school_head( 'C0001' ) );

		$html = $this->pintar( $procedimiento );

		$this->assertStringContainsString( 'class="prc-form prc-form--solicitud"', $html );
		foreach ( array( 'DATOS DEL CENTRO EDUCATIVO', 'DATOS PERSONALES DE LA DIRECCIÓN DEL CENTRO', 'SOLICITA', 'DATOS DE LA COORDINACIÓN' ) as $rotulo ) {
			$this->assertStringContainsString( '<h3 class="prc-seccion__titulo">' . $rotulo . '</h3>', $html, $rotulo );
		}

		$this->assertStringContainsString( 'class="prc-seccion__cuerpo"', $html, 'la retícula de doce columnas' );
		$this->assertStringContainsString( 'prc-campo prc-campo--cuarto', $html, 'un campo de tres columnas' );
		$this->assertStringContainsString( 'prc-control prc-control--bloqueado', $html, 'el campo bloqueado' );
		$this->assertStringContainsString( 'class="prc-campo__obligatorio" aria-hidden="true">*', $html, 'el asterisco es decorativo' );
		$this->assertStringContainsString( 'placeholder="Escriba aquí"', $html, 'la caja de respuesta libre' );
		$this->assertStringContainsString( 'class="prc-boton-enviar"', $html );
	}

	/** A procedure requesting two coordinators shows two independently labelled rows. */
	public function test_multiple_requested_coordinators_have_separate_fields() {
		$procedure = $this->procedimiento( array( ProcedureMetaKeys::COORDINATOR_COUNT => 2 ) );
		$this->acting_as( $this->school_head( 'C0001' ) );
		$html = $this->pintar( $procedure );
		$this->assertStringContainsString( 'Persona coordinadora 1', $html );
		$this->assertStringContainsString( 'Persona coordinadora 2', $html );
		$this->assertStringContainsString( 'name="' . ApplyForm::FIELD_COORDINATORS . '[1][email]"', $html );
		$this->assertStringContainsString( 'name="' . ApplyForm::FIELD_COORDINATORS . '[1][dni]"', $html );
		$this->assertStringContainsString( 'name="' . ApplyForm::FIELD_COORDINATORS . '[1][surname]"', $html );
		$this->assertStringContainsString( 'for="prc-coordinator-surname-1"', $html );
		$this->assertStringNotContainsString( ApplyForm::FIELD_COORD_NAME . '"', $html );
	}

	/**
	 * Una solicitud guardada antes de los apellidos abre con el nombre partido
	 * por el primer espacio: sin esto, «Nombre» llevaría el nombre entero y
	 * los apellidos saldrían dos veces al guardar.
	 */
	public function test_an_old_coordinator_without_surname_opens_split() {
		$procedure = $this->procedimiento( array( ProcedureMetaKeys::COORDINATOR_COUNT => 1 ) );
		$uid       = $this->school_head( 'C0001' );
		Applications::save(
			$procedure,
			$uid,
			$this->nucleo(
				array(
					'coordinator' => array(
						'name'  => 'Ana García López',
						'dni'   => '12345678Z',
						'email' => 'ana@example.org',
					),
				)
			)
		);
		$this->acting_as( $uid );
		$html = $this->pintar( $procedure );

		$this->assertMatchesRegularExpression( '/id="prc-coordinator-name-0"[^>]*value="Ana"/s', $html );
		$this->assertMatchesRegularExpression( '/id="prc-coordinator-surname-0"[^>]*value="García López"/s', $html );
	}

	/** Multiple coordinator rows are validated and saved together. */
	public function test_multiple_coordinators_are_saved_on_the_application() {
		$procedure = $this->procedimiento( array( ProcedureMetaKeys::COORDINATOR_COUNT => 2 ) );
		$uid       = $this->school_head( 'C0001' );
		$redirect  = $this->enviar(
			$uid,
			$procedure,
			array(
				ApplyForm::FIELD_COORDINATORS => array(
					array(
						'name'    => 'Ana',
						'surname' => 'Pérez',
						'dni'     => '12345678Z',
						'email'   => 'ana@example.org',
					),
					array(
						'name'    => 'Luis',
						'surname' => 'Díaz',
						'dni'     => 'X0000000T',
						'email'   => 'luis@example.org',
					),
				),
			)
		);
		$this->assertNotNull( $redirect );
		$id = Applications::find( $procedure, 'C0001' );
		$this->assertSame(
			array(
				array(
					'name'    => 'Ana',
					'surname' => 'Pérez',
					'dni'     => '12345678Z',
					'email'   => 'ana@example.org',
				),
				array(
					'name'    => 'Luis',
					'surname' => 'Díaz',
					'dni'     => 'X0000000T',
					'email'   => 'luis@example.org',
				),
			),
			Applications::meta( (int) $id )[ ApplicationMetaKeys::COORDINATOR ]
		);
	}

	/**
	 * Un campo bloqueado sin valor escribe un guion: así no se confunde «este
	 * centro no consta en el catálogo» con «esto no ha cargado».
	 */
	public function test_a_locked_field_without_a_value_writes_a_dash() {
		$procedimiento = $this->procedimiento();
		$this->acting_as( $this->school_head( 'C9999' ) );

		$html = $this->pintar( $procedimiento );

		$this->assertStringContainsString( 'value="—" readonly', $html );
		$this->assertStringContainsString( 'no está en el catálogo', $html );
	}

	/**
	 * El aviso de protección de datos es configuración del despliegue, no
	 * contenido versionado: sin configurar no se pinta, y lo que llega pasa
	 * por el filtro de contenido (ADR-0009).
	 */
	public function test_the_data_protection_notice_is_configuration_not_content() {
		$procedimiento = $this->procedimiento();
		$this->acting_as( $this->school_head( 'C0001' ) );

		// La clave existe en el armazón y llega vacía: es un hueco declarado de
		// la configuración del despliegue, no algo que haya que adivinar.
		$this->assertArrayHasKey( 'privacy_notice', ProcedureChrome::chrome() );
		$this->assertSame( '', ProcedureChrome::chrome()['privacy_notice'] );
		$this->assertStringNotContainsString( 'prc-aviso-legal', $this->pintar( $procedimiento ), 'sin configurar, nada' );

		add_filter(
			'prc_chrome',
			static function ( array $chrome ): array {
				$chrome['privacy_notice'] = '<p>Aviso del despliegue.</p><script>alert(1)</script>';
				return $chrome;
			}
		);

		$html = $this->pintar( $procedimiento );

		$this->assertStringContainsString( '<div class="prc-aviso-legal"><p>Aviso del despliegue.</p>', $html );
		$this->assertStringNotContainsString( '<script>alert(1)</script>', $html, 'y llega filtrado' );
	}

	/**
	 * La nota de quien revisa va encima del formulario que hay que corregir,
	 * con el estado, su tono y hasta cuándo se puede subsanar.
	 */
	public function test_the_review_note_goes_above_the_form() {
		$procedimiento = $this->procedimiento(
			array(
				ProcedureMetaKeys::AMEND_OPENS_AT  => $this->dia( 2 ),
				ProcedureMetaKeys::AMEND_CLOSES_AT => $this->dia( 5 ),
			)
		);
		$direccion     = $this->school_head( 'C0001' );
		$solicitud     = Applications::save( $procedimiento, $direccion, $this->nucleo() );
		Applications::review( $solicitud, ApplicationMetaKeys::REVIEW_AMEND, 'Falta la memoria.' );

		$this->acting_as( $direccion );
		$html = $this->pintar( $procedimiento );

		$this->assertStringContainsString( 'class="prc-solicitud-estado prc-aviso prc-aviso-warning', $html, 'el tono del estado' );
		$this->assertStringContainsString( '>edit_note<', $html, 'y su icono' );
		$this->assertStringContainsString( 'Su solicitud: A subsanar · hasta el ', $html );
		$this->assertStringContainsString( 'Falta la memoria.', $html );
		// El día, como en el resto del aplicativo y no en ISO.
		$this->assertStringContainsString( 'Presentada el ' . current_time( 'd-m-Y' ) . '.', $html );
		$this->assertTrue( $this->modelo( $procedimiento )['can_submit'], 'y el formulario sigue abierto' );
	}

	// ─── presentar y editar ────────────────────────────────────────────────

	/**
	 * Presentar crea una solicitud; volver a enviar edita la que hay, y se
	 * nota en el aviso de vuelta y en el rótulo del botón.
	 */
	public function test_submitting_creates_one_application_and_the_second_time_edits_it() {
		$procedimiento = $this->procedimiento_completo();
		$direccion     = $this->school_head( 'C0001' );
		$respuestas    = array(
			ApplyForm::FIELD_ANSWERS => array(
				'q1' => 'Nada que añadir',
				'q2' => '1',
				'q3' => 'B',
				'q4' => array( 'Infantil', 'Primaria' ),
			),
		);

		$vuelta = $this->enviar(
			$direccion,
			$procedimiento,
			array_merge(
				$respuestas,
				array(
					ApplyForm::FIELD_COORD_NAME    => 'Ana',
					ApplyForm::FIELD_COORD_SURNAME => 'Pérez',
					ApplyForm::FIELD_COORD_DNI     => '12345678Z',
					ApplyForm::FIELD_COORD_EMAIL   => 'ana@example.org',
					ApplyForm::FIELD_ACCEPT        => '1',
				)
			)
		);

		$this->assertNotNull( $vuelta );
		$this->assertSame( 'presentada', $this->query_arg( (string) $vuelta, ApplyForm::ARG_NOTICE ) );
		$this->assertSame( 1, Applications::count( $procedimiento ) );

		$id = Applications::find( $procedimiento, 'C0001' );
		$this->assertNotNull( $id );
		$meta = Applications::meta( $id );
		$this->assertSame( ApplicationMetaKeys::POSITION_HEAD, $meta[ ApplicationMetaKeys::APPLICANT_POSITION ] );
		$this->assertSame( 'ana@example.org', $meta[ ApplicationMetaKeys::COORDINATOR ][0]['email'] );
		$this->assertSame(
			array(
				'q1' => 'Nada que añadir',
				'q2' => true,
				'q3' => 'B',
				'q4' => array( 'Infantil', 'Primaria' ),
			),
			$meta[ ApplicationMetaKeys::ANSWERS ]
		);

		$otra = $this->enviar(
			$direccion,
			$procedimiento,
			array_merge(
				$respuestas,
				array(
					ApplyForm::FIELD_POSITION      => ApplicationMetaKeys::POSITION_SECRETARY,
					ApplyForm::FIELD_COORD_NAME    => 'Ana',
					ApplyForm::FIELD_COORD_SURNAME => 'Pérez',
					ApplyForm::FIELD_COORD_DNI     => '12345678Z',
					ApplyForm::FIELD_COORD_EMAIL   => 'ana@example.org',
					ApplyForm::FIELD_ACCEPT        => '1',
				)
			)
		);

		$this->assertSame( 'guardada', $this->query_arg( (string) $otra, ApplyForm::ARG_NOTICE ), 'la segunda vez se guarda, no se presenta' );
		$this->assertSame( 1, Applications::count( $procedimiento ), 'y sigue habiendo una sola solicitud' );
		$this->assertSame( $id, Applications::find( $procedimiento, 'C0001' ) );
		$this->assertSame( ApplicationMetaKeys::POSITION_SECRETARY, get_post_meta( $id, ApplicationMetaKeys::APPLICANT_POSITION, true ) );

		$this->acting_as( $direccion );
		$html = $this->pintar( $procedimiento );
		$this->assertStringContainsString( 'Guardar los cambios', $html, 'y el botón ya no dice presentar' );
		$this->assertStringContainsString( 'Su solicitud', $html );
	}

	/**
	 * Un envío sin un nonce bueno no guarda nada.
	 */
	public function test_without_a_good_nonce_nothing_is_stored() {
		$procedimiento = $this->procedimiento();

		$this->assertNull( $this->enviar( $this->school_head( 'C0001' ), $procedimiento, array(), 'falsificado' ) );
		$this->assertSame( 0, Applications::count( $procedimiento ) );
	}

	/**
	 * Fuera de plazo el botón no está y el POST directo se rechaza: el plazo
	 * se comprueba al guardar, no solo al pintar.
	 */
	public function test_out_of_the_window_the_post_is_refused() {
		$proximo   = $this->procedimiento(
			array(
				ProcedureMetaKeys::OPENS_AT  => $this->dia( 5 ),
				ProcedureMetaKeys::CLOSES_AT => $this->dia( 10 ),
			)
		);
		$cerrado   = $this->procedimiento(
			array(
				ProcedureMetaKeys::OPENS_AT  => $this->dia( -10 ),
				ProcedureMetaKeys::CLOSES_AT => $this->dia( -5 ),
			)
		);
		$direccion = $this->school_head( 'C0001' );
		$this->acting_as( $direccion );

		$this->assertSame( 'El plazo de solicitud todavía no está abierto.', $this->modelo( $proximo )['why'] );
		$this->assertSame( 'Las solicitudes están cerradas.', $this->modelo( $cerrado )['why'] );

		$this->assertNull( $this->enviar( $direccion, $proximo ) );
		$this->olvidar_rechazo();
		$this->assertNull( $this->enviar( $direccion, $cerrado ) );

		$this->assertSame( 0, Applications::count( $proximo ) );
		$this->assertSame( 0, Applications::count( $cerrado ) );
	}

	/**
	 * En subsanación solo edita el centro al que se le pidió subsanar (ADR-0020).
	 */
	public function test_in_the_amendment_window_only_the_school_asked_to_amend_edits() {
		$procedimiento = $this->procedimiento(
			array(
				ProcedureMetaKeys::OPENS_AT        => $this->dia( -20 ),
				ProcedureMetaKeys::CLOSES_AT       => $this->dia( -10 ),
				ProcedureMetaKeys::AMEND_OPENS_AT  => $this->dia( -1 ),
				ProcedureMetaKeys::AMEND_CLOSES_AT => $this->dia( 1 ),
			)
		);
		$norte         = $this->school_head( 'C0001' );
		$sur           = $this->school_head( 'C0002' );
		$suya          = Applications::save( $procedimiento, $norte, $this->nucleo() );
		$otra          = Applications::save( $procedimiento, $sur, $this->nucleo() );
		Applications::review( $otra, ApplicationMetaKeys::REVIEW_AMEND, 'Falta la memoria.' );

		$this->acting_as( $norte );
		$m = $this->modelo( $procedimiento );
		$this->assertFalse( $m['can_submit'] );
		$this->assertStringContainsString( 'plazo de subsanación', $m['why'] );

		$this->assertNull( $this->enviar( $norte, $procedimiento, array( ApplyForm::FIELD_POSITION => ApplicationMetaKeys::POSITION_SECRETARY ) ) );
		$this->assertSame( ApplicationMetaKeys::POSITION_HEAD, get_post_meta( $suya, ApplicationMetaKeys::APPLICANT_POSITION, true ), 'no se le tocó nada' );

		$this->olvidar_rechazo();
		$this->acting_as( $sur );
		$this->assertTrue( $this->modelo( $procedimiento )['can_submit'], 'a quien se le pidió subsanar, sí' );

		$vuelta = $this->enviar( $sur, $procedimiento, array( ApplyForm::FIELD_POSITION => ApplicationMetaKeys::POSITION_SECRETARY ) );
		$this->assertSame( 'guardada', $this->query_arg( (string) $vuelta, ApplyForm::ARG_NOTICE ) );
		$this->assertSame( 2, Applications::count( $procedimiento ), 'y subsanar no crea otra solicitud' );
	}

	/**
	 * La tabla del plazo, en una función pura: quién puede presentar o editar
	 * en cada estado del procedimiento.
	 */
	public function test_the_window_says_when_the_form_is_open() {
		$this->assertSame( '', ApplyForm::window( ProcedureMetaKeys::STATE_OPEN, '' ) );
		$this->assertSame( '', ApplyForm::window( ProcedureMetaKeys::STATE_OPEN, ApplicationMetaKeys::REVIEW_ADMITTED ) );
		$this->assertSame( '', ApplyForm::window( ProcedureMetaKeys::STATE_AMENDMENT, ApplicationMetaKeys::REVIEW_AMEND ) );

		$this->assertNotSame( '', ApplyForm::window( ProcedureMetaKeys::STATE_AMENDMENT, '' ) );
		$this->assertNotSame( '', ApplyForm::window( ProcedureMetaKeys::STATE_AMENDMENT, ApplicationMetaKeys::REVIEW_SUBMITTED ) );
		$this->assertSame( 'El plazo de solicitud todavía no está abierto.', ApplyForm::window( ProcedureMetaKeys::STATE_UPCOMING, '' ) );
		$this->assertSame(
			'La inscripción se ha cerrado manualmente, antes de la fecha prevista.',
			ApplyForm::window( ProcedureMetaKeys::STATE_CLOSED, '', true )
		);

		foreach ( array( ProcedureMetaKeys::STATE_CLOSED, ProcedureMetaKeys::STATE_DRAFT, ProcedureMetaKeys::STATE_RESOLVED, ProcedureMetaKeys::STATE_ARCHIVED ) as $estado ) {
			$this->assertSame( 'Las solicitudes están cerradas.', ApplyForm::window( $estado, ApplicationMetaKeys::REVIEW_AMEND ), $estado );
		}
	}

	// ─── las respuestas ────────────────────────────────────────────────────

	/**
	 * Lo respondido se guarda bajo la clave de su pregunta y sobrevive a que
	 * la pregunta se borre: deja de pintarse y se queda en la meta (ADR-0019).
	 */
	public function test_answers_are_stored_by_question_key_and_survive_deleting_a_question() {
		$procedimiento = $this->procedimiento(
			array(
				ProcedureMetaKeys::QUESTIONS => array(
					array(
						'label'   => 'Modalidad',
						'type'    => ProcedureQuestions::TYPE_SINGLE,
						'choices' => array( 'A', 'B' ),
					),
					array(
						'label' => 'Observaciones',
						'type'  => ProcedureQuestions::TYPE_TEXT,
					),
				),
			)
		);
		$direccion     = $this->school_head( 'C0001' );

		$this->enviar(
			$direccion,
			$procedimiento,
			array(
				ApplyForm::FIELD_ANSWERS => array(
					'q1' => 'B',
					'q2' => 'Lo que sea',
				),
			)
		);

		$id = Applications::find( $procedimiento, 'C0001' );
		$this->assertNotNull( $id );
		$this->assertSame(
			array(
				'q1' => 'B',
				'q2' => 'Lo que sea',
			),
			get_post_meta( $id, ApplicationMetaKeys::ANSWERS, true ),
			'la respuesta se guarda bajo la clave, no bajo el rótulo'
		);

		// Se borra la primera pregunta del procedimiento.
		$preguntas = Applications::questions( $procedimiento );
		unset( $preguntas[0] );
		update_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS, array_values( $preguntas ) );

		$this->acting_as( $direccion );
		$m = $this->modelo( $procedimiento );
		$this->assertCount( 1, $m['spec']['questions'] );
		$this->assertSame( 'q2', $m['spec']['questions'][0]['key'], 'la que queda conserva su clave' );

		$html = $this->pintar( $procedimiento );
		$this->assertStringNotContainsString( 'Modalidad', $html, 'la pregunta borrada deja de pintarse' );
		$this->assertStringContainsString( 'Lo que sea', $html, 'y lo respondido a la que queda sigue ahí' );

		$this->assertSame(
			'B',
			get_post_meta( $id, ApplicationMetaKeys::ANSWERS, true )['q1'],
			'la respuesta a la pregunta borrada se conserva en la meta'
		);
	}

	/** Individual applications use the teacher account and omit centre fields. */
	public function test_teacher_can_submit_and_see_an_individual_application() {
		$procedure = $this->procedimiento(
			array(
				ProcedureMetaKeys::AUDIENCE    => ProcedureMetaKeys::AUDIENCE_TEACHERS,
				ProcedureMetaKeys::COMMITMENTS => 'Completar la formación.',
			)
		);
		$teacher   = (int) self::factory()->user->create(
			array(
				'role'         => 'prc_teacher',
				'display_name' => 'Docente de prueba',
			)
		);
		$this->acting_as( $teacher );
		$model = $this->modelo( $procedure );
		$this->assertTrue( $model['can_submit'] );
		$this->assertSame( ApplicationMetaKeys::TYPE_TEACHER, $model['applicant_type'] );
		$school_procedure = $this->procedimiento();
		$this->assertFalse( \Prc\Access\ProcedureAccess::can_apply( $teacher, $school_procedure ), 'el perfil docente no solicita procedimientos de centros' );

		$html = $this->pintar( $procedure );
		$this->assertStringContainsString( 'DATOS DE LA PERSONA PARTICIPANTE', $html );
		$this->assertStringNotContainsString( 'DATOS DEL CENTRO EDUCATIVO', $html );
		$this->assertStringNotContainsString( 'Cargo en el centro', $html );

		$url = $this->enviar( $teacher, $procedure, array( ApplyForm::FIELD_ACCEPT => '1' ) );
		$this->assertNotNull( $url );
		$id = Applications::find_for_teacher( $procedure, $teacher );
		$this->assertNotNull( $id );
		$this->assertSame( $teacher, (int) get_post_field( 'post_author', $id ) );
		$this->assertSame( ApplicationMetaKeys::TYPE_TEACHER, get_post_meta( $id, ApplicationMetaKeys::APPLICANT_TYPE, true ) );
		$this->assertSame( '', get_post_meta( $id, ApplicationMetaKeys::CENTRE_CODE, true ) );
		$this->assertSame( array(), get_post_meta( $id, ApplicationMetaKeys::COORDINATOR, true ) );
	}

	// ─── número, fecha, rejilla y puntos (ADR-0040) ────────────────────────

	/**
	 * Un procedimiento abierto con los tipos nuevos.
	 *
	 * @param bool $obligatorias Whether the questions are required.
	 * @return int
	 */
	private function con_tipos_nuevos( bool $obligatorias = false ): int {
		return $this->procedimiento(
			array(
				ProcedureMetaKeys::QUESTIONS => array(
					array(
						'key'      => 'q1',
						'label'    => 'Grupos',
						'type'     => ProcedureQuestions::TYPE_NUMBER,
						'required' => $obligatorias,
						'max'      => 12,
					),
					array(
						'key'      => 'q2',
						'label'    => 'Fecha del claustro',
						'type'     => ProcedureQuestions::TYPE_DATE,
						'required' => $obligatorias,
					),
					array(
						'key'      => 'q3',
						'label'    => 'Alumnado',
						'type'     => ProcedureQuestions::TYPE_GRID,
						'required' => $obligatorias,
						'rows'     => "Infantil\nPrimaria",
					),
					array(
						'key'     => 'q4',
						'label'   => 'Méritos',
						'type'    => ProcedureQuestions::TYPE_MULTIPLE,
						'choices' => "Huerto | 3\nRadio",
					),
				),
			)
		);
	}

	/**
	 * Cada tipo nuevo se pinta con su control, y los puntos no se ven.
	 */
	public function test_the_new_types_are_painted_with_their_controls() {
		$procedimiento = $this->con_tipos_nuevos( true );
		$this->acting_as( $this->school_head( 'C0001' ) );
		$html = $this->pintar( $procedimiento );
		$n    = ApplyForm::FIELD_ANSWERS;

		$this->assertMatchesRegularExpression( '/<input type="number"[^>]*id="prc-q-q1"[^>]*name="' . $n . '\[q1\]"[^>]*min="0"[^>]*max="12"[^>]*required/', $html );
		$this->assertMatchesRegularExpression( '/<input type="date"[^>]*id="prc-q-q2"[^>]*name="' . $n . '\[q2\]"[^>]*required/', $html );
		$this->assertStringContainsString( 'name="' . $n . '[q3][r1]"', $html );
		$this->assertStringContainsString( 'name="' . $n . '[q3][r2]"', $html );
		$this->assertStringContainsString( '<label class="prc-campo__etiqueta" for="prc-q-q3-r1">Infantil</label>', $html );
		$this->assertStringContainsString( 'value="Huerto"', $html );
		$this->assertStringNotContainsString( '| 3', $html, 'quien solicita no ve los puntos' );
	}

	/**
	 * Lo que se presenta se guarda con su forma: entero, día y una cantidad por fila.
	 */
	public function test_new_answers_are_stored_with_their_shape() {
		$procedimiento = $this->con_tipos_nuevos();
		$direccion     = $this->school_head( 'C0001' );

		$vuelta = $this->enviar(
			$direccion,
			$procedimiento,
			array(
				ApplyForm::FIELD_ANSWERS => array(
					'q1' => ' 007 ',
					'q2' => '2026-02-28',
					'q3' => array(
						'r1' => '0',
						'r2' => '',
					),
					'q4' => array( 'Huerto' ),
				),
			)
		);

		$this->assertNotNull( $vuelta );
		$id = Applications::find( $procedimiento, 'C0001' );
		$this->assertSame(
			array(
				'q1' => 7,
				'q2' => '2026-02-28',
				'q3' => array(
					'r1' => 0,
					'r2' => '',
				),
				'q4' => array( 'Huerto' ),
			),
			get_post_meta( $id, ApplicationMetaKeys::ANSWERS, true )
		);

		$this->acting_as( $direccion );
		$html = $this->pintar( $procedimiento );
		$this->assertMatchesRegularExpression( '/name="' . ApplyForm::FIELD_ANSWERS . '\[q1\]"[^>]*value="7"/', $html, 'se vuelve a abrir con lo guardado' );
		$this->assertMatchesRegularExpression( '/name="' . ApplyForm::FIELD_ANSWERS . '\[q3\]\[r1\]"[^>]*value="0"/', $html, 'y el cero sigue siendo cero' );
		$this->assertMatchesRegularExpression( '/name="' . ApplyForm::FIELD_ANSWERS . '\[q3\]\[r2\]"[^>]*value=""/', $html );

		// Con el plazo cerrado, lo presentado se lee.
		update_post_meta( $procedimiento, ProcedureMetaKeys::CLOSES_AT, $this->dia( -1 ) );
		update_post_meta( $procedimiento, ProcedureMetaKeys::OPENS_AT, $this->dia( -3 ) );
		$html = $this->pintar( $procedimiento );
		$this->assertStringContainsString( '28/02/2026', $html, 'la fecha, como día/mes/año' );
		$this->assertStringContainsString( 'Infantil: 0', $html, 'y la rejilla, en una línea' );
	}

	/**
	 * Un número fuera de rango, un día que no existe o una rejilla obligatoria
	 * a medias no se presentan, y el motivo nombra la pregunta.
	 */
	public function test_wrong_new_answers_are_refused() {
		$procedimiento = $this->con_tipos_nuevos( true );
		$direccion     = $this->school_head( 'C0001' );
		$bien          = array(
			'q1' => '0',
			'q2' => '2026-02-28',
			'q3' => array(
				'r1' => '1',
				'r2' => '2',
			),
		);

		foreach ( array(
			'q1' => array( 'q1' => '1,5' ),
			'q2' => array( 'q2' => '2026-02-30' ),
			'q3' => array( 'q3' => array( 'r1' => '1' ) ),
		) as $cual => $mal ) {
			$this->olvidar_rechazo();
			$this->assertNull( $this->enviar( $direccion, $procedimiento, array( ApplyForm::FIELD_ANSWERS => array_merge( $bien, $mal ) ) ), $cual );
			$this->acting_as( $direccion );
			$this->assertStringContainsString( 'Revise la respuesta a', $this->modelo( $procedimiento )['flash']['texto'], $cual );
		}
		$this->assertNull( Applications::find( $procedimiento, 'C0001' ) );

		$this->olvidar_rechazo();
		$this->assertNotNull( $this->enviar( $direccion, $procedimiento, array( ApplyForm::FIELD_ANSWERS => $bien ) ), 'cero responde una obligatoria' );
	}
}
