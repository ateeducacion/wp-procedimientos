<?php
/**
 * Tests for the application's single mail exit and the events it listens to.
 *
 * @package Prc
 */

use Prc\Access\CentreScope;
use Prc\Access\ProcedureAccess;
use Prc\Domain\ProcedureQuestions;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\Notifications\Mailer;
use Prc\PublicFront\Applications;
use Prc\PublicFront\ApplyForm;
use Prc\PublicFront\ProcedureEditor;

/**
 * Los correos del aplicativo, capturados antes de salir.
 *
 * Nada de esto manda un correo de verdad: `pre_wp_mail` corta `wp_mail()`
 * antes de PHPMailer y se queda con lo que iba a enviarse. Devolver `false`
 * desde ahí es exactamente lo que ve el aplicativo cuando el servidor de
 * correo falla, y eso también se prueba: presentar no depende del correo.
 */
class Test_Mailer extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Lo que habría salido, en orden.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private $enviados = array();

	/**
	 * Lo que contesta el servidor de correo de mentira.
	 *
	 * @var bool
	 */
	private $servidor = true;

	/**
	 * Con el aplicativo, sus páginas, un catálogo inventado y el correo capturado.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
		$this->pages();
		remove_all_filters( 'prc_centres' );
		add_filter( 'prc_centres', array( $this, 'catalogo' ) );
		$this->enviados = array();
		$this->servidor = true;
		add_filter( 'pre_wp_mail', array( $this, 'capturar' ), 10, 2 );
		foreach ( Mailer::options() as $opcion ) {
			delete_option( $opcion );
		}
		$this->olvidar_rechazos();
	}

	/**
	 * Los motivos de rechazo viven en estáticas: se sueltan entre tests.
	 *
	 * @return void
	 */
	private function olvidar_rechazos(): void {
		foreach ( array( ApplyForm::class, ProcedureEditor::class ) as $clase ) {
			\Closure::bind(
				static function (): void {
					self::$rejected = '';
				},
				null,
				$clase
			)();
		}
	}

	/**
	 * Un centro de prueba.
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
		);
	}

	/**
	 * Captura un envío en vez de mandarlo.
	 *
	 * @param null|bool            $corto Short-circuit value.
	 * @param array<string, mixed> $atts  What wp_mail() got.
	 * @return bool
	 */
	public function capturar( $corto, array $atts ): bool {
		unset( $corto );
		if ( $this->servidor ) {
			$this->enviados[] = $atts;
		}
		return $this->servidor;
	}

	/**
	 * Los envíos que llevaban este asunto.
	 *
	 * @param string $trozo Part of the subject.
	 * @return array<int, array<string, mixed>>
	 */
	private function con_asunto( string $trozo ): array {
		return array_values(
			array_filter(
				$this->enviados,
				static function ( array $correo ) use ( $trozo ): bool {
					return false !== strpos( (string) $correo['subject'], $trozo );
				}
			)
		);
	}

	/**
	 * A quién fue cada envío.
	 *
	 * @param array<int, array<string, mixed>> $correos Sent mails.
	 * @return string[]
	 */
	private function destinos( array $correos ): array {
		$out = array();
		foreach ( $correos as $correo ) {
			$out = array_merge( $out, (array) $correo['to'] );
		}
		sort( $out );
		return $out;
	}

	/**
	 * Un día contado desde hoy.
	 *
	 * @param int $dias Offset in days.
	 * @return string Y-m-d.
	 */
	private function dia( int $dias ): string {
		return gmdate( 'Y-m-d', (int) strtotime( ProcedureState::today() . ' ' . $dias . ' days' ) );
	}

	/**
	 * Un procedimiento abierto, con preguntas y dos contactos.
	 *
	 * @param array<string, mixed> $meta Meta to override.
	 * @return int
	 */
	private function procedimiento( array $meta = array() ): int {
		return $this->procedure(
			$this->administrator(),
			array( $this->area( 'Área de prueba' ) ),
			array_merge(
				array(
					ProcedureMetaKeys::OPENS_AT       => $this->dia( -1 ),
					ProcedureMetaKeys::CLOSES_AT      => $this->dia( 1 ),
					ProcedureMetaKeys::CONTACT_EMAILS => array( 'ambito@example.org', 'apoyo@example.org' ),
					ProcedureMetaKeys::QUESTIONS      => array(
						array(
							'label' => 'Observaciones',
							'type'  => ProcedureQuestions::TYPE_TEXT,
						),
						array(
							'label' => 'Tiene huerto',
							'type'  => ProcedureQuestions::TYPE_YESNO,
						),
						array(
							'label'   => 'Etapas',
							'type'    => ProcedureQuestions::TYPE_MULTIPLE,
							'choices' => array( 'Infantil', 'Primaria' ),
						),
					),
				),
				$meta
			),
			array( 'post_title' => 'Red de huertos escolares' )
		);
	}

	/**
	 * Quien dirige el centro de prueba, con su correo.
	 *
	 * @return int
	 */
	private function direccion(): int {
		$uid = $this->school_head( 'C0001' );
		wp_update_user(
			array(
				'ID'           => $uid,
				'user_email'   => 'direccion@example.org',
				'display_name' => 'Persona de prueba',
			)
		);
		return $uid;
	}

	/**
	 * Otra cuenta del mismo centro, con su correo.
	 *
	 * @return int
	 */
	private function companera(): int {
		$uid = $this->school_head( 'C0001' );
		wp_update_user(
			array(
				'ID'           => $uid,
				'user_email'   => 'jefatura@example.org',
				'display_name' => 'Otra persona de prueba',
			)
		);
		return $uid;
	}

	/**
	 * Un núcleo válido, como lo deja ApplicationInput::validate().
	 *
	 * @return array<string, mixed>
	 */
	private function nucleo(): array {
		return array(
			'position'    => ApplicationMetaKeys::POSITION_HEAD,
			'coordinator' => array(),
			'answers'     => array(
				'q1' => 'Tenemos invernadero',
				'q2' => '1',
				'q3' => array( 'Infantil', 'Primaria' ),
			),
		);
	}

	/**
	 * El texto de un correo sin sus enlaces ni sus fechas: los enlaces llevan
	 * el número del procedimiento en la URL, y eso no es «volcar un
	 * identificador»; y una fecha tiene números que pueden coincidir con uno.
	 *
	 * @param string $texto Body.
	 * @return string
	 */
	private function sin_enlaces( string $texto ): string {
		$texto = (string) preg_replace( '#https?://\S+#', '', $texto );
		return (string) preg_replace( '/\d{2}-\d{2}-\d{4}( \d{2}:\d{2})?/', '', $texto );
	}

	// ─── el acuse ──────────────────────────────────────────────────────────

	/**
	 * Presentar manda el acuse a quien presenta y el aviso a los contactos.
	 */
	public function test_submitting_sends_the_acknowledgement_and_the_copy() {
		$procedimiento = $this->procedimiento();
		$uid           = $this->direccion();

		$id = Applications::save( $procedimiento, $uid, $this->nucleo() );
		$this->assertGreaterThan( 0, $id );

		$acuse = $this->con_asunto( 'Solicitud presentada' );
		$this->assertCount( 1, $acuse );
		$this->assertSame( 'direccion@example.org', $acuse[0]['to'] );

		$cuerpo = (string) $acuse[0]['message'];
		$this->assertStringContainsString( 'Red de huertos escolares', $cuerpo );
		$this->assertStringContainsString( 'Centro de prueba Norte', $cuerpo );
		$this->assertStringContainsString( (string) get_the_date( 'd-m-Y', $id ), $cuerpo );
		$this->assertStringContainsString( '- Observaciones: Tenemos invernadero', $cuerpo );
		$this->assertStringContainsString( '- Tiene huerto: Sí', $cuerpo );
		$this->assertStringContainsString( '- Etapas: Infantil, Primaria', $cuerpo );
		$this->assertStringContainsString( ApplyForm::url( $procedimiento ), $cuerpo, 'lleva el enlace a su solicitud' );

		// Ni claves internas ni números de la base de datos.
		$texto = $this->sin_enlaces( $cuerpo );
		$this->assertStringNotContainsString( 'prc_', $texto );
		$this->assertDoesNotMatchRegularExpression( '/\bq[0-9]+\b/', $texto, 'ninguna clave de pregunta' );
		$this->assertDoesNotMatchRegularExpression( '/\b' . $id . '\b/', $texto, 'ni el número de la solicitud' );
		$this->assertDoesNotMatchRegularExpression( '/\b' . $procedimiento . '\b/', $texto, 'ni el del procedimiento' );
		$this->assertDoesNotMatchRegularExpression( '/\b' . $uid . '\b/', $texto, 'ni el de la cuenta' );

		$copia = $this->con_asunto( 'Nueva solicitud' );
		$this->assertSame( array( 'ambito@example.org', 'apoyo@example.org' ), $this->destinos( $copia ) );
		$this->assertStringContainsString( '- Tiene huerto: Sí', (string) $copia[0]['message'] );
	}

	/**
	 * La coordinación sale en el acuse con nombre y apellidos (ADR-0039).
	 */
	public function test_the_acknowledgement_names_the_coordination_with_surname() {
		$procedimiento         = $this->procedimiento();
		$nucleo                = $this->nucleo();
		$nucleo['coordinator'] = array(
			array(
				'name'    => 'Ana',
				'surname' => 'Pérez Díaz',
				'dni'     => '12345678Z',
				'email'   => 'ana@example.org',
			),
		);
		Applications::save( $procedimiento, $this->direccion(), $nucleo );

		$this->assertStringContainsString( 'Ana Pérez Díaz', (string) $this->con_asunto( 'Solicitud presentada' )[0]['message'] );
	}

	/**
	 * Guardar otra vez la misma es «modificada», a quien presenta y a los contactos.
	 */
	public function test_saving_again_sends_the_updated_mail() {
		$procedimiento = $this->procedimiento();
		$uid           = $this->direccion();
		Applications::save( $procedimiento, $uid, $this->nucleo() );
		$this->enviados = array();

		Applications::save( $procedimiento, $uid, $this->nucleo() );

		$this->assertCount( 0, $this->con_asunto( 'Solicitud presentada' ) );
		$this->assertSame( array( 'direccion@example.org' ), $this->destinos( $this->con_asunto( 'Solicitud modificada: ' ) ) );
		$this->assertSame( array( 'ambito@example.org', 'apoyo@example.org' ), $this->destinos( $this->con_asunto( 'Solicitud modificada —' ) ) );
	}

	/**
	 * La dirección de copia recibe el aviso a los contactos.
	 */
	public function test_the_copy_option_receives_the_received_mail() {
		update_option( Mailer::OPTION_COPY, 'copia@example.org' );
		Applications::save( $this->procedimiento(), $this->direccion(), $this->nucleo() );

		$this->assertSame(
			array( 'ambito@example.org', 'apoyo@example.org', 'copia@example.org' ),
			$this->destinos( $this->con_asunto( 'Nueva solicitud' ) )
		);
		$this->assertSame( array( 'direccion@example.org' ), $this->destinos( $this->con_asunto( 'Solicitud presentada' ) ), 'el acuse no se copia' );
	}

	// ─── lo que lo apaga ───────────────────────────────────────────────────

	/**
	 * Un evento apagado en el procedimiento no sale; los demás, sí.
	 */
	public function test_an_event_switched_off_in_the_procedure_is_not_sent() {
		$procedimiento = $this->procedimiento( array( ProcedureMetaKeys::NOTIFY => array( Mailer::EVENT_APPLICATION_SUBMITTED ) ) );

		Applications::save( $procedimiento, $this->direccion(), $this->nucleo() );

		$this->assertCount( 0, $this->con_asunto( 'Solicitud presentada' ) );
		$this->assertCount( 2, $this->con_asunto( 'Nueva solicitud' ) );
		$this->assertFalse(
			Mailer::mail( Mailer::EVENT_APPLICATION_SUBMITTED, 'x@example.org', 'A', 'B', array( 'procedure_id' => $procedimiento ) ),
			'la salida única también lo mira'
		);
	}

	/**
	 * El filtro `prc_mail` ve lo que va a salir y, devolviendo false, lo descarta.
	 */
	public function test_the_prc_mail_filter_sees_and_can_discard() {
		$visto = array();
		add_filter(
			'prc_mail',
			static function ( $correo, string $evento ) use ( &$visto ) {
				$visto[] = array( $correo, $evento );
				return false;
			},
			10,
			2
		);

		$this->assertFalse( Mailer::mail( Mailer::EVENT_APPLICATION_SUBMITTED, 'x@example.org', 'Asunto', 'Cuerpo' ) );
		$this->assertSame( array(), $this->enviados );
		$this->assertSame( Mailer::EVENT_APPLICATION_SUBMITTED, $visto[0][1] );
		$this->assertSame( array( 'to', 'subject', 'message', 'headers' ), array_keys( $visto[0][0] ) );
		$this->assertSame( 'x@example.org', $visto[0][0]['to'] );
	}

	/**
	 * El filtro también puede reescribir el correo.
	 */
	public function test_the_prc_mail_filter_can_rewrite() {
		add_filter(
			'prc_mail',
			static function ( array $correo ): array {
				$correo['subject'] = 'Otro asunto';
				return $correo;
			}
		);
		$this->assertTrue( Mailer::mail( Mailer::EVENT_APPLICATION_SUBMITTED, 'x@example.org', 'Asunto', 'Cuerpo' ) );
		$this->assertSame( 'Otro asunto', $this->enviados[0]['subject'] );
	}

	/**
	 * Una dirección que no lo es no llega a `wp_mail()`.
	 */
	public function test_a_bad_address_is_not_sent() {
		$this->assertFalse( Mailer::mail( Mailer::EVENT_APPLICATION_SUBMITTED, 'no es un correo', 'A', 'B' ) );
		$this->assertSame( array(), $this->enviados );
	}

	// ─── el servidor de correo caído ───────────────────────────────────────

	/**
	 * Con `wp_mail()` fallando, la salida dice que no y la solicitud se guarda igual.
	 */
	public function test_a_failing_wp_mail_does_not_stop_the_application() {
		$this->servidor = false;
		$this->assertFalse( Mailer::mail( Mailer::EVENT_APPLICATION_SUBMITTED, 'x@example.org', 'A', 'B' ) );

		$procedimiento = $this->procedimiento();
		$id            = Applications::save( $procedimiento, $this->direccion(), $this->nucleo() );
		$this->assertGreaterThan( 0, $id, 'la solicitud se guarda aunque el acuse no salga' );
		$this->assertSame( ApplicationMetaKeys::REVIEW_SUBMITTED, get_post_meta( $id, ApplicationMetaKeys::REVIEW_STATE, true ) );
	}

	/**
	 * Y desde la pantalla: el aviso sigue siendo el de presentada.
	 */
	public function test_a_failing_wp_mail_keeps_the_success_notice() {
		$this->servidor = false;
		$procedimiento  = $this->procedimiento();
		$uid            = $this->direccion();
		$this->acting_as( $uid );
		$this->post(
			array(
				ApplyForm::FIELD_OP        => ApplyForm::OP_APPLY,
				ApplyForm::FIELD_PROCEDURE => (string) $procedimiento,
				ApplyForm::FIELD_POSITION  => ApplicationMetaKeys::POSITION_HEAD,
			),
			ApplyForm::NONCE_ACTION,
			ApplyForm::NONCE_FIELD
		);

		$vuelta = $this->exit_url( array( ApplyForm::class, 'handle' ) );

		$this->assertNotNull( $vuelta );
		$this->assertSame( 'presentada', $this->query_arg( (string) $vuelta, ApplyForm::ARG_NOTICE ) );
		$this->assertNotNull( Applications::find( $procedimiento, 'C0001' ) );
	}

	/**
	 * Un filtro de otro que revienta tampoco tumba la solicitud.
	 */
	public function test_a_throwing_filter_does_not_stop_the_application() {
		add_filter(
			'prc_mail',
			static function () {
				throw new RuntimeException( 'roto' );
			}
		);
		$this->assertGreaterThan( 0, Applications::save( $this->procedimiento(), $this->direccion(), $this->nucleo() ) );
	}

	// ─── la revisión ───────────────────────────────────────────────────────

	/**
	 * Revisar sin cambiar el estado no manda nada; cambiarlo, sí, con la nota.
	 */
	public function test_the_review_mail_only_when_the_state_changes() {
		$procedimiento  = $this->procedimiento();
		$uid            = $this->direccion();
		$id             = Applications::save( $procedimiento, $uid, $this->nucleo() );
		$this->enviados = array();

		Applications::review( $id, ApplicationMetaKeys::REVIEW_SUBMITTED, '' );
		$this->assertSame( array(), $this->enviados, 'mismo estado, ningún correo' );

		Applications::review( $id, ApplicationMetaKeys::REVIEW_AMEND, 'Falta el acta del claustro.' );
		$this->assertCount( 1, $this->enviados );
		$this->assertSame( 'direccion@example.org', $this->enviados[0]['to'] );
		$this->assertStringContainsString( 'Falta el acta del claustro.', (string) $this->enviados[0]['message'] );
		$this->assertStringContainsString( '«' . ApplicationMetaKeys::review_states()[ ApplicationMetaKeys::REVIEW_AMEND ] . '»', (string) $this->enviados[0]['message'] );

		$this->enviados = array();
		Applications::review( $id, ApplicationMetaKeys::REVIEW_ADMITTED, 'Nota interna de trabajo.' );
		$this->assertCount( 1, $this->enviados );
		$this->assertStringNotContainsString( 'Nota interna de trabajo.', (string) $this->enviados[0]['message'], 'la nota solo va al pedir subsanar o al excluir' );
	}

	/**
	 * La acción de revisión lleva el estado nuevo y el de antes.
	 */
	public function test_the_review_action_carries_what_there_was_before() {
		$id    = $this->application( $this->procedimiento(), $this->direccion(), '', array( ApplicationMetaKeys::REVIEW_NOTE => 'Nota de antes.' ) );
		$visto = array();
		add_action(
			'prc_application_reviewed',
			static function ( int $app, string $estado, string $antes, string $nota_antes ) use ( &$visto ): void {
				$visto[] = array( $app, $estado, $antes, $nota_antes );
			},
			10,
			4
		);
		Applications::review( $id, ApplicationMetaKeys::REVIEW_ADMITTED, '' );
		$this->assertSame( array( array( $id, ApplicationMetaKeys::REVIEW_ADMITTED, ApplicationMetaKeys::REVIEW_SUBMITTED, 'Nota de antes.' ) ), $visto );
	}

	// ─── direcciones ───────────────────────────────────────────────────────

	/**
	 * La misma dirección en distinta caja recibe un correo.
	 */
	public function test_the_same_address_in_another_case_receives_one() {
		$enviados = Mailer::send(
			Mailer::EVENT_APPLICATION_RECEIVED,
			array( 'Ambito@Example.org', 'ambito@example.org', ' AMBITO@example.org ', 'otro@example.org', 'basura' ),
			array( 'procedure' => 'Red' )
		);
		$this->assertSame( 2, $enviados );
		$this->assertCount( 2, $this->enviados );
	}

	/**
	 * Sin remitente configurado no se toca el de WordPress; con él, From y Reply-To.
	 */
	public function test_from_and_reply_to_only_when_configured() {
		Mailer::mail( Mailer::EVENT_APPLICATION_SUBMITTED, 'x@example.org', 'A', 'B' );
		$cabeceras = implode( "\n", (array) $this->enviados[0]['headers'] );
		$this->assertStringNotContainsString( 'From:', $cabeceras );
		$this->assertStringNotContainsString( 'Reply-To:', $cabeceras );

		update_option( Mailer::OPTION_FROM_NAME, 'Procedimientos de prueba' );
		update_option( Mailer::OPTION_FROM, 'remite@example.org' );
		update_option( Mailer::OPTION_REPLY_TO, 'responde@example.org' );
		Mailer::mail( Mailer::EVENT_APPLICATION_SUBMITTED, 'x@example.org', 'A', 'B' );

		$this->assertContains( 'From: Procedimientos de prueba <remite@example.org>', (array) $this->enviados[1]['headers'] );
		$this->assertContains( 'Reply-To: responde@example.org', (array) $this->enviados[1]['headers'] );
	}

	/**
	 * Un nombre de remitente con un salto de línea no mete otra cabecera.
	 */
	public function test_the_sender_name_cannot_inject_a_header() {
		update_option( Mailer::OPTION_FROM_NAME, "Nombre\r\nBcc: alguien@example.org" );
		update_option( Mailer::OPTION_FROM, 'remite@example.org' );
		Mailer::mail( Mailer::EVENT_APPLICATION_SUBMITTED, 'x@example.org', 'A', 'B' );

		foreach ( (array) $this->enviados[0]['headers'] as $cabecera ) {
			$this->assertStringNotContainsString( "\n", $cabecera );
			$this->assertStringStartsNotWith( 'Bcc', $cabecera );
		}
	}

	// ─── el alta de un procedimiento ───────────────────────────────────────

	/**
	 * Crear el borrador avisa a sus contactos, a quien gestiona su ámbito y a
	 * la copia; guardarlo después no vuelve a avisar.
	 */
	public function test_the_created_mail_goes_out_once() {
		update_option( Mailer::OPTION_COPY, 'copia@example.org' );
		update_option( Mailer::OPTION_CREATED_EXTRA, 'Escriba a la unidad de apoyo si tiene dudas.' );
		$area   = $this->area( 'Área de prueba' );
		$otra   = $this->area( 'Otra área' );
		$quien  = $this->manager( array( $area ) );
		$colega = $this->manager( array( $area ) );
		$ajena  = $this->manager( array( $otra ) );
		wp_update_user(
			array(
				'ID'         => $colega,
				'user_email' => 'colega@example.org',
			)
		);
		wp_update_user(
			array(
				'ID'         => $ajena,
				'user_email' => 'ajena@example.org',
			)
		);
		wp_update_user(
			array(
				'ID'         => $quien,
				'user_email' => 'quien@example.org',
			)
		);
		$curso = $this->course();

		$this->acting_as( $quien );
		$datos = array(
			ProcedureEditor::FIELD_DO         => ProcedureEditor::PANEL_DATA,
			ProcedureEditor::FIELD_PROCEDURE  => '0',
			ProcedureEditor::FIELD_TITLE      => 'Red de huertos escolares',
			ProcedureEditor::FIELD_AREA       => array( (string) $area ),
			ProcedureEditor::FIELD_COURSE     => (string) $curso,
			ProcedureMetaKeys::CONTACT_EMAILS => array( 'ambito@example.org' ),
			ProcedureMetaKeys::OWNERSHIP      => array( ProcedureMetaKeys::OWNERSHIP_PUBLIC ),
			ProcedureMetaKeys::CENTRE_KINDS   => array( ProcedureMetaKeys::KIND_SCHOOL ),
		);
		$this->post( $datos, ProcedureEditor::nonce_action( ProcedureEditor::PANEL_DATA ), ProcedureEditor::nonce_name( ProcedureEditor::PANEL_DATA ) );
		$vuelta = $this->exit_url( array( ProcedureEditor::class, 'handle' ) );
		$this->assertNotNull( $vuelta, 'el borrador se crea' );
		$id = (int) $this->query_arg( (string) $vuelta, ProcedureEditor::ARG_PROCEDURE );
		$this->assertSame( 'draft', get_post_status( $id ) );

		$alta = $this->con_asunto( 'Procedimiento creado' );
		$this->assertSame(
			array( 'ambito@example.org', 'colega@example.org', 'copia@example.org', 'quien@example.org' ),
			$this->destinos( $alta ),
			'contactos, quienes gestionan su ámbito y la copia; no el otro ámbito'
		);
		$cuerpo = (string) $alta[0]['message'];
		$this->assertStringContainsString( 'Red de huertos escolares', $cuerpo );
		$this->assertStringContainsString( ProcedureEditor::url( $id ), $cuerpo );
		$this->assertStringContainsString( 'Escriba a la unidad de apoyo si tiene dudas.', $cuerpo );
		$this->assertStringContainsString( 'Plazo de solicitud', $cuerpo, 'dice qué falta' );

		// Guardarlo después no es crearlo.
		$this->enviados = array();
		$this->olvidar_rechazos();
		$datos[ ProcedureEditor::FIELD_PROCEDURE ] = (string) $id;
		$datos[ ProcedureEditor::FIELD_TITLE ]     = 'Red de huertos escolares, segunda';
		$this->post( $datos, ProcedureEditor::nonce_action( ProcedureEditor::PANEL_DATA ), ProcedureEditor::nonce_name( ProcedureEditor::PANEL_DATA ) );
		$this->assertNotNull( $this->exit_url( array( ProcedureEditor::class, 'handle' ) ) );
		$this->assertSame( 'Red de huertos escolares, segunda', get_the_title( $id ) );
		$this->assertSame( array(), $this->con_asunto( 'Procedimiento creado' ) );
	}

	/**
	 * Sin párrafo extra no sale ninguno.
	 */
	public function test_the_created_mail_without_extra() {
		$area = $this->area( 'Área de prueba' );
		$id   = $this->procedure( $this->administrator(), array( $area ), array( ProcedureMetaKeys::CONTACT_EMAILS => array( 'ambito@example.org' ) ), array( 'post_status' => 'draft' ) );

		do_action( 'prc_procedure_created', $id );

		$this->assertCount( 1, $this->enviados );
		$this->assertStringNotContainsString( 'unidad de apoyo', (string) $this->enviados[0]['message'] );
	}

	// ─── quién actúa ───────────────────────────────────────────────────────

	/**
	 * Modificar la solicitud de su centro avisa a quien la modifica, no a quien
	 * la presentó: la solicitud es del centro, no de la cuenta.
	 */
	public function test_the_update_goes_to_whoever_acts_for_the_centre() {
		$procedimiento  = $this->procedimiento();
		$autora         = $this->direccion();
		$otra           = $this->companera();
		$id             = Applications::save( $procedimiento, $autora, $this->nucleo() );
		$this->enviados = array();

		$this->assertSame( $id, Applications::save( $procedimiento, $otra, $this->nucleo() ) );

		$this->assertSame( array( 'jefatura@example.org' ), $this->destinos( $this->con_asunto( 'Solicitud modificada: ' ) ) );
		$this->assertStringContainsString( 'Otra persona de prueba', (string) $this->con_asunto( 'Solicitud modificada —' )[0]['message'], 'los contactos leen quién la modificó' );
	}

	/**
	 * La acción del guardado lleva la cuenta que actúa.
	 */
	public function test_the_saved_action_carries_the_acting_account() {
		$procedimiento = $this->procedimiento();
		$visto         = array();
		add_action(
			'prc_application_saved',
			static function ( int $id, bool $creada, int $quien ) use ( &$visto ): void {
				$visto[] = array( $creada, $quien );
			},
			10,
			3
		);
		$autora = $this->direccion();
		$otra   = $this->companera();
		Applications::save( $procedimiento, $autora, $this->nucleo() );
		Applications::save( $procedimiento, $otra, $this->nucleo() );

		$this->assertSame( array( array( true, $autora ), array( false, $otra ) ), $visto );
	}

	/**
	 * La revisión va a la cuenta autora mientras siga pudiendo ver la
	 * solicitud; si ha dejado el centro, a nadie.
	 */
	public function test_the_review_only_goes_to_an_author_who_still_sees_it() {
		$procedimiento = $this->procedimiento();
		$autora        = $this->direccion();
		$this->companera();
		$id = Applications::save( $procedimiento, $autora, $this->nucleo() );

		$this->enviados = array();
		Applications::review( $id, ApplicationMetaKeys::REVIEW_ADMITTED, '' );
		$this->assertSame( array( 'direccion@example.org' ), $this->destinos( $this->enviados ) );

		delete_user_meta( $autora, CentreScope::meta_key() );
		$this->enviados = array();
		Applications::review( $id, ApplicationMetaKeys::REVIEW_EXCLUDED, 'No cumple los requisitos.' );
		$this->assertSame( array(), $this->enviados, 'ni respuestas ni nota a quien ya no es del centro' );
	}

	// ─── la nota y el estado de antes ──────────────────────────────────────

	/**
	 * Cambiar la nota de un estado que la exige es algo nuevo que contar;
	 * reescribir la misma, no.
	 */
	public function test_a_new_note_on_the_same_state_is_sent() {
		$id = Applications::save( $this->procedimiento(), $this->direccion(), $this->nucleo() );
		Applications::review( $id, ApplicationMetaKeys::REVIEW_AMEND, 'Falta el acta.' );

		$this->enviados = array();
		Applications::review( $id, ApplicationMetaKeys::REVIEW_AMEND, 'Falta el acta.' );
		$this->assertSame( array(), $this->enviados, 'misma nota, nada nuevo' );

		Applications::review( $id, ApplicationMetaKeys::REVIEW_AMEND, 'Falta el acta firmada.' );
		$this->assertCount( 1, $this->enviados );
		$this->assertStringContainsString( 'Falta el acta firmada.', (string) $this->enviados[0]['message'] );

		Applications::review( $id, ApplicationMetaKeys::REVIEW_ADMITTED, '' );
		$this->enviados = array();
		Applications::review( $id, ApplicationMetaKeys::REVIEW_ADMITTED, 'Comentario interno.' );
		$this->assertSame( array(), $this->enviados, 'admitida no lleva nota: cambiarla no se cuenta' );
	}

	/**
	 * Una solicitud antigua sin estado guardado que se marca «presentada» no
	 * ha cambiado de nada: no se dice «ha pasado a Presentada».
	 */
	public function test_an_empty_previous_state_to_submitted_is_no_change() {
		$id = $this->application( $this->procedimiento(), $this->direccion() );
		delete_post_meta( $id, ApplicationMetaKeys::REVIEW_STATE );
		$this->enviados = array();

		Applications::review( $id, ApplicationMetaKeys::REVIEW_SUBMITTED, '' );
		$this->assertSame( array(), $this->enviados );

		delete_post_meta( $id, ApplicationMetaKeys::REVIEW_STATE );
		Applications::review( $id, ApplicationMetaKeys::REVIEW_ADMITTED, '' );
		$this->assertCount( 1, $this->enviados, 'a otro estado, sí' );
	}

	// ─── profesorado ───────────────────────────────────────────────────────

	/**
	 * La solicitud individual avisa a quien la presenta al crearla y al
	 * modificarla, sin línea de centro.
	 */
	public function test_a_teacher_application_is_acknowledged_without_centre() {
		$procedimiento = $this->procedimiento( array( ProcedureMetaKeys::AUDIENCE => ProcedureMetaKeys::AUDIENCE_TEACHERS ) );
		$docente       = (int) self::factory()->user->create(
			array(
				'role'         => 'prc_teacher',
				'user_email'   => 'docente@example.org',
				'display_name' => 'Docente de prueba',
			)
		);

		$id = Applications::save_teacher( $procedimiento, $docente, array( 'answers' => array( 'q1' => 'Huerto en el patio' ) ) );
		$this->assertGreaterThan( 0, $id );
		$acuse = $this->con_asunto( 'Solicitud presentada' );
		$this->assertSame( array( 'docente@example.org' ), $this->destinos( $acuse ) );
		$this->assertStringContainsString( 'Presentada por: Docente de prueba', (string) $acuse[0]['message'] );
		$this->assertStringContainsString( '- Observaciones: Huerto en el patio', (string) $acuse[0]['message'] );
		$this->assertStringNotContainsString( 'Centro:', (string) $acuse[0]['message'] );

		$this->enviados = array();
		Applications::save_teacher( $procedimiento, $docente, array( 'answers' => array() ) );
		$this->assertSame( array( 'docente@example.org' ), $this->destinos( $this->con_asunto( 'Solicitud modificada: ' ) ) );
		$this->assertCount( 2, $this->con_asunto( 'Solicitud modificada —' ) );
	}

	// ─── el cableado ───────────────────────────────────────────────────────

	/**
	 * `register()` escucha las cuatro acciones.
	 */
	public function test_register_listens_to_the_actions() {
		$this->assertSame( 10, has_action( 'prc_procedure_created', array( Mailer::class, 'on_procedure_created' ) ) );
		$this->assertSame( 10, has_action( 'prc_application_saved', array( Mailer::class, 'on_application_saved' ) ) );
		global $wp_filter;
		$this->assertSame( 3, $wp_filter['prc_application_saved']->callbacks[10][ Mailer::class . '::on_application_saved' ]['accepted_args'] );
		$this->assertSame( 4, $wp_filter['prc_application_reviewed']->callbacks[10][ Mailer::class . '::on_application_reviewed' ]['accepted_args'] );
		$this->assertSame( 3, $wp_filter['prc_delivery_saved']->callbacks[10][ Mailer::class . '::on_delivery_saved' ]['accepted_args'] );
		$this->assertSame( 10, has_action( 'prc_application_reviewed', array( Mailer::class, 'on_application_reviewed' ) ) );
		$this->assertSame( 10, has_action( 'prc_delivery_saved', array( Mailer::class, 'on_delivery_saved' ) ) );
	}

	/**
	 * Los eventos que se apagan por procedimiento son eventos del correo.
	 */
	public function test_the_switchable_events_are_mail_events() {
		$this->assertNotEmpty( ProcedureMetaKeys::notify_events() );
		foreach ( array_keys( ProcedureMetaKeys::notify_events() ) as $evento ) {
			$this->assertContains( $evento, Mailer::events() );
		}
		$this->assertArrayNotHasKey( Mailer::EVENT_PROCEDURE_CREATED, ProcedureMetaKeys::notify_events(), 'el alta sale antes de que el procedimiento pueda apagar nada' );
	}

	/**
	 * La meta guarda solo eventos conocidos, sin repetir.
	 */
	public function test_the_notify_meta_keeps_known_events() {
		$procedimiento = $this->procedimiento();
		update_post_meta( $procedimiento, ProcedureMetaKeys::NOTIFY, array( Mailer::EVENT_REVIEW_CHANGED, 'inventado', Mailer::EVENT_REVIEW_CHANGED ) );
		$this->assertSame( array( Mailer::EVENT_REVIEW_CHANGED ), get_post_meta( $procedimiento, ProcedureMetaKeys::NOTIFY, true ) );
		$this->assertTrue( Mailer::enabled( $procedimiento, Mailer::EVENT_APPLICATION_SUBMITTED ) );
		$this->assertFalse( Mailer::enabled( $procedimiento, Mailer::EVENT_REVIEW_CHANGED ) );
	}

	/**
	 * Las opciones de ajustes se limpian al guardarse.
	 */
	public function test_the_options_are_sanitised() {
		$this->assertSame( '', Mailer::sanitize_address( 'no es un correo' ) );
		$this->assertSame( 'remite@example.org', Mailer::sanitize_address( ' Remite@Example.org ' ) );
	}

	/**
	 * Quien gestiona un ámbito superior también recibe el alta: su ámbito lo cubre.
	 */
	public function test_the_parent_area_manager_is_told() {
		$padre = $this->area( 'Servicio de prueba' );
		$hija  = $this->area( 'Área hija', $padre );
		$jefa  = $this->manager( array( $padre ) );
		wp_update_user(
			array(
				'ID'         => $jefa,
				'user_email' => 'servicio@example.org',
			)
		);
		$id = $this->procedure( $this->administrator(), array( $hija ), array(), array( 'post_status' => 'draft' ) );

		$this->enviados = array();
		do_action( 'prc_procedure_created', $id );

		$this->assertSame( array( 'servicio@example.org' ), $this->destinos( $this->enviados ) );
		$this->assertContains( $jefa, ProcedureAccess::area_managers( $id ) );
	}
}
