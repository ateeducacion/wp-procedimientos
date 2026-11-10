<?php
/**
 * Tests for the plain-text messages of the application's mail.
 *
 * @package Prc
 */

use Prc\Notifications\Mailer;
use Prc\Notifications\Messages;

/**
 * Lo que dice cada correo, sin mandar ninguno.
 *
 * `Messages` recibe el contexto ya resuelto —textos, listas y enlaces— y
 * devuelve asunto y cuerpo en texto plano. Lo que se comprueba aquí es lo que
 * lee la persona: que estén los datos que necesita y que no se cuele nada que
 * no le dice nada, como una clave interna.
 */
class Test_Messages extends WP_UnitTestCase {

	/**
	 * Un contexto de solicitud completo.
	 *
	 * @param array<string, mixed> $cambios What to override.
	 * @return array<string, mixed>
	 */
	private function solicitud( array $cambios = array() ): array {
		return array_merge(
			array(
				'procedure' => 'Red de huertos escolares',
				'centre'    => 'Centro de prueba Norte (C0001)',
				'applicant' => 'Persona de prueba',
				'date'      => '10-11-2026 09:30',
				'answers'   => array(
					array(
						'label' => 'Observaciones',
						'text'  => 'Tenemos invernadero',
					),
					array(
						'label' => 'Tiene huerto',
						'text'  => 'Sí',
					),
				),
				'url'       => 'https://example.org/solicitud/',
			),
			$cambios
		);
	}

	/**
	 * El acuse lleva el procedimiento, el centro, la fecha, una línea por
	 * respuesta y el enlace; el asunto, el prefijo.
	 */
	public function test_the_acknowledgement_has_what_was_submitted() {
		$correo = Messages::render( Mailer::EVENT_APPLICATION_SUBMITTED, $this->solicitud() );

		$this->assertStringStartsWith( '[Procedimientos] ', $correo['subject'] );
		$this->assertStringContainsString( 'Red de huertos escolares', $correo['subject'] );
		foreach ( array( 'Red de huertos escolares', 'Centro de prueba Norte (C0001)', '10-11-2026 09:30', 'Persona de prueba', 'https://example.org/solicitud/' ) as $dato ) {
			$this->assertStringContainsString( $dato, $correo['message'] );
		}
		$this->assertStringContainsString( "- Observaciones: Tenemos invernadero\n", $correo['message'] );
		$this->assertStringContainsString( "- Tiene huerto: Sí\n", $correo['message'] );
		$this->assertStringNotContainsString( '<', $correo['message'], 'texto plano, sin etiquetas' );
	}

	/**
	 * El acuse es un justificante: número, fecha y hora, quién y para qué
	 * centro, si está completa y, con lista de personas, hasta cuándo se
	 * cambian y dónde.
	 */
	public function test_the_acknowledgement_is_a_receipt() {
		$correo = Messages::render(
			Mailer::EVENT_APPLICATION_SUBMITTED,
			$this->solicitud(
				array(
					'number'       => '123',
					'submitted'    => '10-11-2026 a las 09:30',
					'missing'      => array( 'Faltan 2 personas' ),
					'people_until' => '20-11-2026',
					'people_url'   => 'https://example.org/solicitud/#prc-personas',
					'receipt_url'  => 'https://example.org/solicitud/?justificante=1',
				)
			)
		);

		$this->assertSame( '[Procedimientos] Solicitud presentada n.º 123: Red de huertos escolares', $correo['subject'] );
		$this->assertStringContainsString( 'Se ha presentado la solicitud n.º 123 al procedimiento «Red de huertos escolares» el 10-11-2026 a las 09:30, por Persona de prueba, para Centro de prueba Norte (C0001). Guarde este correo: es su justificante.', $correo['message'] );
		$this->assertStringContainsString( "Estado: presentada, pero incompleta: faltan 2 personas.\n", $correo['message'] );
		$this->assertStringContainsString( "Puede dar de alta, cambiar o sustituir a las personas que participan hasta el 20-11-2026, incluido, en:\nhttps://example.org/solicitud/#prc-personas\n", $correo['message'] );
		$this->assertStringContainsString( "Justificante para imprimir o guardar en PDF:\nhttps://example.org/solicitud/?justificante=1\n", $correo['message'] );

		$completa = Messages::render(
			Mailer::EVENT_APPLICATION_SUBMITTED,
			$this->solicitud(
				array(
					'number'    => '124',
					'submitted' => '10-11-2026 a las 09:30',
				)
			)
		);
		$this->assertStringContainsString( "Estado: completa.\n", $completa['message'] );
		$this->assertStringNotContainsString( 'personas que participan', $completa['message'], 'sin lista, nada de personas' );
	}

	/**
	 * Una respuesta vacía se dice, en vez de dejar la línea colgando.
	 */
	public function test_an_empty_answer_says_so() {
		$correo = Messages::render(
			Mailer::EVENT_APPLICATION_SUBMITTED,
			$this->solicitud(
				array(
					'answers' => array(
						array(
							'label' => 'Observaciones',
							'text'  => '',
						),
					),
				)
			)
		);
		$this->assertStringContainsString( '- Observaciones: (sin respuesta)', $correo['message'] );
	}

	/**
	 * El prefijo del asunto lo cambia un filtro, y vacío no deja un espacio delante.
	 */
	public function test_the_subject_prefix_is_a_filter() {
		add_filter(
			'prc_mail_subject_prefix',
			static function (): string {
				return '[Convocatorias]';
			}
		);
		$this->assertStringStartsWith( '[Convocatorias] ', Messages::render( Mailer::EVENT_APPLICATION_UPDATED, $this->solicitud() )['subject'] );

		add_filter( 'prc_mail_subject_prefix', '__return_empty_string', 20 );
		$this->assertStringStartsWith( 'Solicitud modificada', Messages::render( Mailer::EVENT_APPLICATION_UPDATED, $this->solicitud() )['subject'] );
	}

	/**
	 * La copia a los contactos distingue una solicitud nueva de una modificada.
	 */
	public function test_the_copy_to_the_contacts_says_whether_it_is_new() {
		$nueva      = Messages::render( Mailer::EVENT_APPLICATION_RECEIVED, $this->solicitud( array( 'created' => true ) ) );
		$modificada = Messages::render( Mailer::EVENT_APPLICATION_RECEIVED, $this->solicitud( array( 'created' => false ) ) );

		$this->assertStringContainsString( 'Nueva solicitud', $nueva['subject'] );
		$this->assertStringContainsString( 'Solicitud modificada', $modificada['subject'] );
		$this->assertStringContainsString( 'Centro de prueba Norte (C0001)', $nueva['subject'] );
		$this->assertStringContainsString( '- Tiene huerto: Sí', $nueva['message'] );
	}

	/**
	 * El cambio de revisión dice el estado y, si la hay, la nota.
	 */
	public function test_the_review_says_the_state_and_the_note() {
		$con = Messages::render(
			Mailer::EVENT_REVIEW_CHANGED,
			$this->solicitud(
				array(
					'state' => 'A subsanar',
					'note'  => 'Falta el acta del claustro.',
				)
			)
		);
		$this->assertStringContainsString( '«A subsanar»', $con['message'] );
		$this->assertStringContainsString( 'Falta el acta del claustro.', $con['message'] );

		$sin = Messages::render( Mailer::EVENT_REVIEW_CHANGED, $this->solicitud( array( 'state' => 'Admitida' ) ) );
		$this->assertStringContainsString( '«Admitida»', $sin['message'] );
		$this->assertStringNotContainsString( 'Nota', $sin['message'] );
	}

	/**
	 * Retirar: el acuse dice que se puede presentar otra; el aviso a los
	 * contactos, que está en «Retiradas». Ninguno lleva las respuestas.
	 */
	public function test_the_withdrawal_tells_each_one_what_matters() {
		$acuse = Messages::render( Mailer::EVENT_APPLICATION_WITHDRAWN, $this->solicitud( array( 'ack' => true ) ) );
		$aviso = Messages::render( Mailer::EVENT_APPLICATION_WITHDRAWN, $this->solicitud() );
		$this->assertStringStartsWith( '[Procedimientos] Solicitud retirada: ', $acuse['subject'] );
		$this->assertStringContainsString( 'presentar una nueva', $acuse['message'] );
		$this->assertStringContainsString( 'Centro de prueba Norte (C0001)', $aviso['subject'] );
		$this->assertStringContainsString( '«Retiradas»', $aviso['message'] );
		foreach ( array( $acuse, $aviso ) as $correo ) {
			$this->assertStringNotContainsString( 'Tiene huerto', $correo['message'] );
		}
	}

	/**
	 * La prórroga dice hasta qué día, incluido.
	 */
	public function test_the_extension_says_until_when() {
		$correo = Messages::render( Mailer::EVENT_APPLICATION_EXTENDED, $this->solicitud( array( 'until' => '20-11-2026' ) ) );
		$this->assertStringStartsWith( '[Procedimientos] Prórroga de su solicitud', $correo['subject'] );
		$this->assertStringContainsString( 'hasta el 20-11-2026, incluido', $correo['message'] );
	}

	/**
	 * La entrega dice qué documento llegó y con qué nombre.
	 */
	public function test_the_delivery_names_the_document() {
		$correo = Messages::render(
			Mailer::EVENT_DELIVERY_RECEIVED,
			$this->solicitud(
				array(
					'document' => 'Memoria final',
					'file'     => 'memoria.pdf',
				)
			)
		);
		$this->assertStringContainsString( 'Memoria final', $correo['subject'] );
		$this->assertStringContainsString( 'memoria.pdf', $correo['message'] );
		$this->assertStringContainsString( 'Centro de prueba Norte (C0001)', $correo['message'] );
	}

	/**
	 * El alta dice qué falta, los dos pasos, los dos enlaces y el párrafo de
	 * la instalación cuando lo hay.
	 */
	public function test_the_created_mail_has_the_steps_the_links_and_the_extra() {
		$ctx    = array(
			'procedure'     => 'Red de huertos escolares',
			'missing'       => array( 'Plazo de solicitud', 'Enlace a la resolución' ),
			'editor_url'    => 'https://example.org/editar-procedimiento/',
			'workspace_url' => 'https://example.org/gestion-de-procedimientos/',
			'extra'         => 'Escriba a la unidad de apoyo si tiene dudas.',
		);
		$correo = Messages::render( Mailer::EVENT_PROCEDURE_CREATED, $ctx );

		$this->assertStringContainsString( 'Red de huertos escolares', $correo['subject'] );
		$this->assertStringContainsString( "- Plazo de solicitud\n", $correo['message'] );
		$this->assertStringContainsString( "- Enlace a la resolución\n", $correo['message'] );
		$this->assertStringContainsString( '1. ', $correo['message'] );
		$this->assertStringContainsString( '2. ', $correo['message'] );
		$this->assertStringContainsString( 'enlace la resolución', $correo['message'] );
		$this->assertStringContainsString( 'https://example.org/editar-procedimiento/', $correo['message'] );
		$this->assertStringContainsString( 'https://example.org/gestion-de-procedimientos/', $correo['message'] );
		$this->assertStringContainsString( 'Escriba a la unidad de apoyo si tiene dudas.', $correo['message'] );

		$ctx['extra']   = '';
		$ctx['missing'] = array();
		$sin            = Messages::render( Mailer::EVENT_PROCEDURE_CREATED, $ctx );
		$this->assertStringNotContainsString( 'unidad de apoyo', $sin['message'] );
		$this->assertStringNotContainsString( 'Qué falta', $sin['message'] );
	}

	/**
	 * Un evento que no existe no tiene correo.
	 */
	public function test_an_unknown_event_has_no_message() {
		$this->assertSame(
			array(
				'subject' => '',
				'message' => '',
			),
			Messages::render( 'inventado', $this->solicitud() )
		);
	}

	/**
	 * Un salto de línea en un título no parte el asunto.
	 */
	public function test_the_subject_is_one_line() {
		$correo = Messages::render( Mailer::EVENT_APPLICATION_SUBMITTED, $this->solicitud( array( 'procedure' => "Red\r\nBcc: alguien@example.org" ) ) );
		$this->assertStringNotContainsString( "\n", $correo['subject'] );
		$this->assertStringNotContainsString( "\r", $correo['subject'] );
	}
}
