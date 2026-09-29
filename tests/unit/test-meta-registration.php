<?php
/**
 * Tests for the meta registration of procedures and applications.
 *
 * @package Prc
 */

use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ApplicationMetaRegistration;
use Prc\Meta\ProcedureMetaKeys;
use Prc\Meta\ProcedureMetaRegistration;
use Prc\PostType\ApplicationPostType;
use Prc\PostType\ProcedurePostType;

/**
 * Que cada meta esté declarada, y con el guardián que le toca.
 *
 * `register_post_meta()` no es decoración: el `sanitize_callback` es lo que
 * normaliza lo que entra, y el `auth_callback` es **la puerta**. Una meta que
 * se olvide de declarar se escribe igual, pero sin sanear y sin permiso.
 *
 * La raya que más importa: las metas del procedimiento las edita quien puede
 * editarlo; las de una **solicitud** no las edita nadie a mano —las escribe
 * el aplicativo— y su `auth_callback` dice que no siempre.
 */
class Test_Meta_Registration extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Con el aplicativo arrancado.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
	}

	// ─── están declaradas ──────────────────────────────────────────────────

	/**
	 * Toda meta del procedimiento está registrada, con su tipo, y fuera de la REST.
	 */
	public function test_every_procedure_meta_is_registered() {
		$registradas = get_registered_meta_keys( 'post', ProcedurePostType::POST_TYPE );
		$schema      = ProcedureMetaRegistration::schema();

		$this->assertEqualsCanonicalizing( ProcedureMetaKeys::all(), array_keys( $schema ), 'el esquema cubre todas las claves' );
		foreach ( $schema as $clave => $spec ) {
			$this->assertArrayHasKey( $clave, $registradas, $clave . ' no está declarada' );
			$this->assertSame( $spec['type'], $registradas[ $clave ]['type'], $clave );
			$this->assertFalse( $registradas[ $clave ]['show_in_rest'], $clave . ' no sale por REST' );
		}
	}

	/**
	 * Y toda meta de la solicitud.
	 */
	public function test_every_application_meta_is_registered() {
		$registradas = get_registered_meta_keys( 'post', ApplicationPostType::POST_TYPE );

		$this->assertEqualsCanonicalizing( ApplicationMetaKeys::all(), array_keys( ApplicationMetaRegistration::schema() ) );
		foreach ( ApplicationMetaKeys::all() as $clave ) {
			$this->assertArrayHasKey( $clave, $registradas, $clave . ' no está declarada' );
			$this->assertFalse( $registradas[ $clave ]['show_in_rest'], $clave );
		}
	}

	/**
	 * La puerta de una solicitud está cerrada para todo el mundo.
	 */
	public function test_an_application_meta_is_closed_even_to_administration() {
		$registradas = get_registered_meta_keys( 'post', ApplicationPostType::POST_TYPE );
		$this->acting_as( $this->administrator() );

		foreach ( ApplicationMetaKeys::all() as $clave ) {
			$auth = $registradas[ $clave ]['auth_callback'];
			$this->assertFalse(
				(bool) call_user_func( $auth, false, $clave, 1, get_current_user_id(), 'edit_post_meta', array() ),
				$clave . ': las escribe el aplicativo, no una persona'
			);
		}
	}

	/**
	 * La del procedimiento la abre quien puede editarlo, y a nadie más.
	 */
	public function test_a_procedure_meta_opens_for_whoever_edits_the_procedure() {
		$area          = $this->area( 'Área de prueba' );
		$procedimiento = $this->procedure( $this->administrator(), array( $area ) );
		$mio           = $this->manager( array( $area ) );
		$ajeno         = $this->manager( array( $this->area( 'Otro ámbito' ) ) );

		$this->assertTrue( ProcedureMetaRegistration::auth_edit_procedure( false, ProcedureMetaKeys::OPENS_AT, $procedimiento, $mio ) );
		$this->assertFalse( ProcedureMetaRegistration::auth_edit_procedure( false, ProcedureMetaKeys::OPENS_AT, $procedimiento, $ajeno ) );
	}

	/**
	 * La marca de histórico la pone el ámbito y solo la quita administración.
	 */
	public function test_the_archived_mark_follows_its_asymmetry() {
		$area          = $this->area( 'Área de prueba' );
		$mio           = $this->manager( array( $area ) );
		$procedimiento = $this->procedure( $mio, array( $area ) );

		$this->assertTrue( ProcedureMetaRegistration::auth_archived( false, ProcedureMetaKeys::ARCHIVED, $procedimiento, $mio ) );

		update_post_meta( $procedimiento, ProcedureMetaKeys::ARCHIVED, true );
		$this->assertFalse( ProcedureMetaRegistration::auth_archived( false, ProcedureMetaKeys::ARCHIVED, $procedimiento, $mio ) );
		$this->assertTrue( ProcedureMetaRegistration::auth_archived( false, ProcedureMetaKeys::ARCHIVED, $procedimiento, $this->administrator() ) );
	}

	// ─── sanean lo que entra ───────────────────────────────────────────────

	/**
	 * Una fecha es un día del calendario, en Y-m-d, o nada.
	 */
	public function test_a_date_is_a_calendar_day() {
		$this->assertSame( '2026-10-01', ProcedureMetaRegistration::sanitize_date( ' 2026-10-01 ' ) );
		foreach ( array( '2026-02-30', '01/10/2026', '2026-10-1', 'mañana', array( 'no' ) ) as $mala ) {
			$this->assertSame( '', ProcedureMetaRegistration::sanitize_date( $mala ) );
		}
	}

	/**
	 * Un enlace es http(s) o nada; un correo es un correo o nada.
	 */
	public function test_links_and_emails_keep_their_shape_or_are_dropped() {
		$this->assertSame( 'https://example.org/resolucion.pdf', ProcedureMetaRegistration::sanitize_url( ' https://example.org/resolucion.pdf ' ) );
		$this->assertSame( '', ProcedureMetaRegistration::sanitize_url( 'javascript:alert(1)' ) );
		$this->assertSame( '', ProcedureMetaRegistration::sanitize_url( 'ftp://example.org/x' ) );

		$this->assertSame( 'ambito@example.org', ProcedureMetaRegistration::sanitize_email( ' Ambito@Example.org ' ) );
		$this->assertSame( '', ProcedureMetaRegistration::sanitize_email( 'no es un correo' ) );
	}

	/**
	 * Los correos de contacto son hasta tres, y los que no lo son se caen.
	 */
	public function test_the_contact_emails_are_up_to_three_real_addresses() {
		$this->assertSame(
			array( 'uno@example.org', 'dos@example.org' ),
			ProcedureMetaRegistration::sanitize_emails( array( ' UNO@Example.org ', 'no es un correo', 'dos@example.org', 'uno@example.org' ) ),
			'sin lo que no es un correo y sin repetidos'
		);
		$this->assertSame(
			array( 'uno@example.org', 'dos@example.org', 'tres@example.org' ),
			ProcedureMetaRegistration::sanitize_emails( array( 'uno@example.org', 'dos@example.org', 'tres@example.org', 'cuatro@example.org' ) ),
			'el cuarto no cabe'
		);
		$this->assertSame( array( 'uno@example.org' ), ProcedureMetaRegistration::sanitize_emails( 'uno@example.org' ), 'uno suelto es una lista de uno' );
		$this->assertSame( array(), ProcedureMetaRegistration::sanitize_emails( 42 ) );
	}

	/**
	 * El color de cabecera es de la paleta, y el neutro cuando no lo es.
	 */
	public function test_the_header_colour_is_one_of_the_palette() {
		foreach ( array_keys( ProcedureMetaKeys::header_colors() ) as $slug ) {
			$this->assertSame( $slug, ProcedureMetaRegistration::sanitize_header_color( $slug ), $slug );
		}
		foreach ( array( '', '#ff0000', 'fucsia', array( 'verde' ) ) as $fuera ) {
			$this->assertSame(
				ProcedureMetaKeys::HEADER_COLOR_NEUTRAL,
				ProcedureMetaRegistration::sanitize_header_color( $fuera ),
				'sin color de la lista, el neutro'
			);
		}
		$this->assertSame( '#3f4a55', ProcedureMetaKeys::header_hex( 'fucsia' ) );
		$this->assertSame( '#1f6b3a', ProcedureMetaKeys::header_hex( 'verde' ) );
	}

	/**
	 * Y toda la paleta se lee con el rótulo en blanco encima: 4,5:1 (WCAG AA).
	 *
	 * Es el criterio con el que se eligió la paleta, y el que tiene que pasar
	 * cualquier color que se le añada: el contraste no se comprueba a ojo.
	 */
	public function test_every_palette_colour_reads_with_white_on_top() {
		foreach ( ProcedureMetaKeys::header_colors() as $slug => $color ) {
			$this->assertMatchesRegularExpression( '/^#[0-9a-f]{6}$/', $color['hex'], $slug );
			$this->assertNotSame( '', $color['label'], $slug );
			$this->assertGreaterThanOrEqual(
				4.5,
				$this->contraste( $color['hex'], '#ffffff' ),
				$slug . ' no llega a 4,5:1 contra el blanco: el título no se leería encima'
			);
		}
	}

	/**
	 * WCAG 2.1 contrast ratio between two hex colours.
	 *
	 * @param string $uno Hex colour.
	 * @param string $dos Hex colour.
	 * @return float
	 */
	private function contraste( string $uno, string $dos ): float {
		$luz = array( $this->luminancia( $uno ), $this->luminancia( $dos ) );
		return ( max( $luz ) + 0.05 ) / ( min( $luz ) + 0.05 );
	}

	/**
	 * WCAG 2.1 relative luminance of a hex colour.
	 *
	 * @param string $hex Hex colour.
	 * @return float
	 */
	private function luminancia( string $hex ): float {
		$hex     = ltrim( $hex, '#' );
		$canales = array();
		foreach ( array( 0, 2, 4 ) as $i ) {
			$v         = hexdec( substr( $hex, $i, 2 ) ) / 255;
			$canales[] = $v <= 0.03928 ? $v / 12.92 : pow( ( $v + 0.055 ) / 1.055, 2.4 );
		}
		return 0.2126 * $canales[0] + 0.7152 * $canales[1] + 0.0722 * $canales[2];
	}

	/**
	 * Las listas cerradas se quedan en la lista, con su valor por defecto.
	 */
	public function test_closed_lists_stay_closed() {
		$this->assertSame( 'teachers', ProcedureMetaRegistration::sanitize_audience( 'teachers' ) );
		$this->assertSame( 'schools', ProcedureMetaRegistration::sanitize_audience( 'alumnado' ) );

		$this->assertSame( array( 'public', 'private' ), ProcedureMetaRegistration::sanitize_ownership( array( 'private', 'public', 'otra' ) ) );
		$this->assertSame( array( 'private' ), ProcedureMetaRegistration::sanitize_ownership( 'private' ) );
		$this->assertSame( array(), ProcedureMetaRegistration::sanitize_ownership( 42 ) );

		$this->assertSame( 'head', ApplicationMetaRegistration::sanitize_position( 'head' ) );
		$this->assertSame( 'other', ApplicationMetaRegistration::sanitize_position( 'conserje' ) );

		$this->assertSame( 'amend', ApplicationMetaRegistration::sanitize_review_state( 'amend' ) );
		$this->assertSame( 'submitted', ApplicationMetaRegistration::sanitize_review_state( 'inventado' ) );
	}

	/**
	 * Una casilla es un booleano, venga como venga.
	 */
	public function test_a_checkbox_is_a_boolean() {
		foreach ( array( '1', 'on', 'sí', 'yes', true, 1 ) as $si ) {
			$this->assertTrue( ProcedureMetaRegistration::sanitize_bool( $si ) );
		}
		foreach ( array( '', '0', 'no', false, 0, null ) as $no ) {
			$this->assertFalse( ProcedureMetaRegistration::sanitize_bool( $no ) );
		}
	}

	/**
	 * Las preguntas se guardan normalizadas y con los textos limpios.
	 */
	public function test_the_questions_are_stored_normalised_and_clean() {
		$preguntas = ProcedureMetaRegistration::sanitize_questions(
			array(
				array(
					'key'     => 'q1',
					'label'   => '  Modalidad <b>x</b> ',
					'type'    => 'single',
					'choices' => array( ' A ', '<i>B</i>' ),
					'extra'   => 'lo que no existe',
				),
				array( 'label' => '' ),
			)
		);

		$this->assertCount( 1, $preguntas, 'una pregunta sin rótulo no es una pregunta' );
		$this->assertSame( 'Modalidad x', $preguntas[0]['label'] );
		$this->assertSame( array( 'A', 'B' ), $preguntas[0]['choices'] );
		$this->assertArrayNotHasKey( 'extra', $preguntas[0] );
	}

	/**
	 * Las respuestas se guardan por clave de pregunta, y solo esas.
	 */
	public function test_the_answers_keep_only_real_question_keys() {
		$datos = ApplicationMetaRegistration::sanitize_answers(
			array(
				'q1'          => true,
				'q2'          => array( 'Gluten', '<b>Lactosa</b>' ),
				'q3'          => "  algo\nlargo  ",
				'no-es-clave' => 'fuera',
				'q0'          => 'también fuera',
			)
		);

		$this->assertSame( array( 'q1', 'q2', 'q3' ), array_keys( $datos ) );
		$this->assertTrue( $datos['q1'] );
		$this->assertSame( array( 'Gluten', 'Lactosa' ), $datos['q2'] );
		$this->assertSame( "algo\nlargo", $datos['q3'] );
		$this->assertSame( array(), ApplicationMetaRegistration::sanitize_answers( 'esto no es una lista' ) );
	}

	/**
	 * La persona coordinadora son dos campos y nada más.
	 */
	public function test_the_coordinator_is_a_name_and_an_email() {
		$this->assertSame(
			array(
				'name'  => 'Ana Pérez',
				'email' => 'ana@example.org',
			),
			ApplicationMetaRegistration::sanitize_coordinator(
				array(
					'name'  => ' Ana <b>Pérez</b> ',
					'email' => 'Ana@Example.org',
					'phone' => '600000000',
				)
			)
		);
		$this->assertSame(
			array(
				'name'  => '',
				'email' => '',
			),
			ApplicationMetaRegistration::sanitize_coordinator( 'no es una persona' )
		);
	}

	/**
	 * Y todo eso corre de verdad al guardar: la meta escrita sale saneada.
	 */
	public function test_sanitisation_runs_on_update_post_meta() {
		$procedimiento = $this->procedure( $this->administrator() );

		update_post_meta( $procedimiento, ProcedureMetaKeys::OPENS_AT, '2026-02-30' );
		update_post_meta( $procedimiento, ProcedureMetaKeys::OWNERSHIP, array( 'otra', 'public' ) );
		update_post_meta( $procedimiento, ProcedureMetaKeys::ARCHIVED, 'on' );
		update_post_meta( $procedimiento, ProcedureMetaKeys::CONTACT_EMAILS, array( 'UNO@Example.org', 'roto' ) );
		update_post_meta( $procedimiento, ProcedureMetaKeys::HEADER_COLOR, 'fucsia' );

		$this->assertSame( '', get_post_meta( $procedimiento, ProcedureMetaKeys::OPENS_AT, true ) );
		$this->assertSame( array( 'public' ), get_post_meta( $procedimiento, ProcedureMetaKeys::OWNERSHIP, true ) );
		$this->assertSame( array( 'uno@example.org' ), get_post_meta( $procedimiento, ProcedureMetaKeys::CONTACT_EMAILS, true ) );
		$this->assertSame( ProcedureMetaKeys::HEADER_COLOR_NEUTRAL, get_post_meta( $procedimiento, ProcedureMetaKeys::HEADER_COLOR, true ) );
		$this->assertTrue( (bool) get_post_meta( $procedimiento, ProcedureMetaKeys::ARCHIVED, true ) );
		$this->assertSame( array(), get_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS, true ), 'la lista vacía es el valor por defecto' );
	}
}
