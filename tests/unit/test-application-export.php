<?php
/**
 * Tests for ApplicationExport: chosen columns, one row per person and the
 * user upload file.
 *
 * @package Prc
 */

use Prc\Domain\ProcedureQuestions;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ApplicationMetaRegistration;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PublicFront\ApplicationExport;
use Prc\PublicFront\Applications;
use Prc\PublicFront\ProcedureEditor;
use Prc\PublicFront\View\ProcedureEditorView;

/**
 * Exportar a medida: qué columnas, una fila por solicitud o por persona, y el
 * fichero de alta de usuarios de Moodle (ADR-0039).
 */
class Test_Application_Export extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Con el aplicativo arrancado, las páginas y un catálogo inventado.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
		$this->pages();
		remove_all_filters( 'prc_centres' );
		remove_all_filters( 'prc_export_username' );
		add_filter(
			'prc_centres',
			static function (): array {
				return array(
					array(
						'code'      => 'C0001',
						'name'      => 'Centro de prueba Norte',
						'ownership' => 'public',
					),
					array(
						'code'      => 'C0002',
						'name'      => 'Centro de prueba Sur',
						'ownership' => 'public',
					),
				);
			}
		);
	}

	/**
	 * Un procedimiento con tres preguntas y tres personas coordinadoras.
	 *
	 * @return int Procedure ID.
	 */
	private function procedimiento(): int {
		return $this->procedure(
			$this->administrator(),
			array( $this->area( 'Área de prueba' ) ),
			array(
				ProcedureMetaKeys::COORDINATOR_COUNT => 3,
				ProcedureMetaKeys::QUESTIONS         => array(
					array(
						'key'   => 'q1',
						'label' => 'Observaciones',
						'type'  => ProcedureQuestions::TYPE_TEXT,
					),
					array(
						'key'   => 'q2',
						'label' => 'Tiene huerto',
						'type'  => ProcedureQuestions::TYPE_YESNO,
					),
					array(
						'key'     => 'q3',
						'label'   => 'Modalidad',
						'type'    => ProcedureQuestions::TYPE_SINGLE,
						'choices' => array( 'A', 'B' ),
					),
				),
			),
			array( 'post_title' => 'Red de huertos' )
		);
	}

	/**
	 * Presentar la solicitud de un centro.
	 *
	 * @param int                              $procedimiento Procedure ID.
	 * @param string                           $codigo        School code.
	 * @param array<int, array<string,string>> $coordinacion  Coordinators.
	 * @param array<string, mixed>             $respuestas    Answers.
	 * @param array<string, mixed>             $cuenta        Extra fields of the account.
	 * @return int Application ID.
	 */
	private function solicitud( int $procedimiento, string $codigo, array $coordinacion, array $respuestas = array(), array $cuenta = array() ): int {
		$uid = $this->school_head( $codigo );
		if ( array() !== $cuenta ) {
			wp_update_user( array_merge( array( 'ID' => $uid ), $cuenta ) );
		}
		return Applications::save(
			$procedimiento,
			$uid,
			array(
				'position'    => ApplicationMetaKeys::POSITION_HEAD,
				'coordinator' => $coordinacion,
				'answers'     => array_merge(
					array(
						'q1' => 'Nada',
						'q2' => true,
						'q3' => 'B',
					),
					$respuestas
				),
			)
		);
	}

	/**
	 * Una persona coordinadora.
	 *
	 * @param string $nombre    Name.
	 * @param string $apellidos Surname; empty for one stored before it existed.
	 * @param string $correo    Email.
	 * @return array<string, string>
	 */
	private function persona( string $nombre, string $apellidos, string $correo ): array {
		return array(
			'name'    => $nombre,
			'surname' => $apellidos,
			'dni'     => '12345678Z',
			'email'   => $correo,
		);
	}

	/**
	 * Las líneas de un CSV, sin el BOM ni el último salto.
	 *
	 * @param string $csv CSV text.
	 * @return string[]
	 */
	private function lineas( string $csv ): array {
		return explode( "\r\n", rtrim( substr( $csv, 3 ), "\r\n" ) );
	}

	/**
	 * Pedir la exportación por POST como alguien.
	 *
	 * @param int                  $uid           Who asks.
	 * @param int                  $procedimiento Procedure ID.
	 * @param array<string, mixed> $campos        Export fields.
	 * @return string What was served.
	 */
	private function exportar( int $uid, int $procedimiento, array $campos ): string {
		$this->acting_as( $uid );
		$this->post(
			array_merge(
				array(
					ProcedureEditor::FIELD_DO        => ProcedureEditor::OP_EXPORT,
					ProcedureEditor::FIELD_PROCEDURE => (string) $procedimiento,
				),
				$campos
			),
			ProcedureEditor::nonce_action( ProcedureEditor::OP_EXPORT ),
			ProcedureEditor::nonce_name( ProcedureEditor::OP_EXPORT )
		);
		return $this->served( array( ProcedureEditor::class, 'handle' ) );
	}

	// ─── las columnas ──────────────────────────────────────────────────────

	/**
	 * Las columnas, agrupadas como las pinta el diálogo, y las preguntas al final.
	 */
	public function test_groups_are_the_columns_by_group_and_the_questions() {
		$grupos = ApplicationExport::groups( $this->procedimiento() );

		$this->assertSame( array( 'application', 'centre', 'applicant', 'coordination', 'review', 'questions' ), array_keys( $grupos ) );
		$this->assertSame( array_keys( $grupos ), array_keys( ApplicationExport::group_labels() ) );
		$this->assertSame( array( 'centre', 'code' ), array_keys( $grupos['centre'] ) );
		$this->assertSame( 'DNI o NIE de coordinación', $grupos['coordination']['coordinator_dni'] );
		$this->assertSame(
			array(
				'q1' => 'Observaciones',
				'q2' => 'Tiene huerto',
				'q3' => 'Modalidad',
			),
			$grupos['questions']
		);

		// Cada columna de la tabla está en un grupo, y en uno solo.
		$todas = array();
		foreach ( $grupos as $columnas ) {
			$todas += $columnas;
		}
		$this->assertEqualsCanonicalizing( array_keys( Applications::columns( Applications::questions( $this->procedimiento() ) ) ), array_keys( $todas ) );
	}

	/**
	 * `code` y `q3` devuelven solo esas dos columnas, en el orden pedido.
	 */
	public function test_code_and_q3_give_only_those_two_columns_in_that_order() {
		$procedimiento = $this->procedimiento();
		$this->solicitud( $procedimiento, 'C0001', array( $this->persona( 'Ana', 'Pérez', 'ana@example.org' ) ) );

		$lineas = $this->lineas( ApplicationExport::csv( $procedimiento, array( 'code', 'q3' ), ApplicationExport::SHAPE_APPLICATION ) );
		$this->assertSame( array( '"Código";"Modalidad"', '"C0001";"B"' ), $lineas );

		$lineas = $this->lineas( ApplicationExport::csv( $procedimiento, array( 'q3', 'code' ), ApplicationExport::SHAPE_APPLICATION ) );
		$this->assertSame( array( '"Modalidad";"Código"', '"B";"C0001"' ), $lineas );
	}

	/**
	 * Una clave que no existe se ignora; si no queda ninguna válida, salen todas.
	 */
	public function test_an_unknown_key_is_ignored_and_none_valid_exports_everything() {
		$procedimiento = $this->procedimiento();
		$id            = $this->solicitud(
			$procedimiento,
			'C0001',
			array( $this->persona( 'Ana', 'Pérez', 'ana@example.org' ) ),
			array(),
			array(
				'display_name' => 'Dirección Norte',
				'user_email'   => 'norte@example.org',
			)
		);

		$this->assertSame(
			"\xEF\xBB\xBF\"Código\"\r\n\"C0001\"\r\n",
			ApplicationExport::csv( $procedimiento, array( 'no_existe', 'code', 'post_password' ) )
		);

		$todas = "\xEF\xBB\xBF"
			. '"Centro";"Código";"Fecha";"Presentada por";"Correo";"Cargo";"Coordinación";"DNI o NIE de coordinación";"Correo de coordinación";"Estado de revisión";"Nota";"Observaciones";"Tiene huerto";"Modalidad"' . "\r\n"
			. '"Centro de prueba Norte";"C0001";"' . get_the_date( 'Y-m-d H:i', $id ) . '";"Dirección Norte";"norte@example.org";"Dirección";"Ana Pérez";"12345678Z";"ana@example.org";"Presentada";"";"Nada";"Sí";"B"' . "\r\n";
		$this->assertSame( $todas, ApplicationExport::csv( $procedimiento, array( 'no_existe' ) ) );
		$this->assertSame( $todas, ApplicationExport::csv( $procedimiento, array() ) );
		$this->assertSame( $todas, ApplicationExport::csv( $procedimiento, array( array( 'code' ) ) ), 'una clave que no es texto tampoco cuenta' );

		// Elegidas todas, aunque sea en otro orden, es el CSV de siempre.
		$revueltas = array_reverse( array_keys( Applications::columns( Applications::questions( $procedimiento ) ) ) );
		$this->assertSame( $todas, ApplicationExport::csv( $procedimiento, $revueltas ) );
	}

	/**
	 * Una pregunta borrada con solicitudes ya presentadas no descoloca nada: ni
	 * la selección, que la ignora, ni el CSV de todas las columnas.
	 */
	public function test_a_deleted_question_shifts_nothing() {
		$procedimiento = $this->procedimiento();
		$this->solicitud( $procedimiento, 'C0001', array( $this->persona( 'Ana', 'Pérez', 'ana@example.org' ) ), array( 'q1' => 'Uno' ) );

		$preguntas = Applications::questions( $procedimiento );
		unset( $preguntas[1] );
		update_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS, array_values( $preguntas ) );

		$lineas = $this->lineas( ApplicationExport::csv( $procedimiento, array( 'q1', 'q2', 'q3' ) ) );
		$this->assertSame( array( '"Observaciones";"Modalidad"', '"Uno";"B"' ), $lineas );

		$lineas = $this->lineas( ApplicationExport::csv( $procedimiento ) );
		$this->assertStringEndsWith( ';"Observaciones";"Modalidad"', $lineas[0] );
		$this->assertStringEndsWith( ';"Uno";"B"', $lineas[1] );
		$this->assertSame( substr_count( $lineas[0], '";"' ), substr_count( $lineas[1], '";"' ) );
	}

	// ─── una fila por persona ──────────────────────────────────────────────

	/**
	 * Con forma `person`, tres coordinadoras dan tres filas, y las tres repiten
	 * las columnas de la solicitud.
	 */
	public function test_person_shape_gives_one_row_per_coordinator() {
		$procedimiento = $this->procedimiento();
		$this->solicitud(
			$procedimiento,
			'C0001',
			array(
				$this->persona( 'Ana', 'Pérez', 'ana@example.org' ),
				$this->persona( 'Luis', 'Díaz', 'luis@example.org' ),
				$this->persona( 'Eva', 'Ruiz', 'eva@example.org' ),
			)
		);

		$lineas = $this->lineas( ApplicationExport::csv( $procedimiento, array( 'code', 'coordinator', 'coordinator_email', 'q3' ), ApplicationExport::SHAPE_PERSON ) );
		$this->assertSame(
			array(
				'"Código";"Coordinación";"Correo de coordinación";"Modalidad"',
				'"C0001";"Ana Pérez";"ana@example.org";"B"',
				'"C0001";"Luis Díaz";"luis@example.org";"B"',
				'"C0001";"Eva Ruiz";"eva@example.org";"B"',
			),
			$lineas
		);

		$lineas = $this->lineas( ApplicationExport::csv( $procedimiento, array( 'code', 'coordinator' ), ApplicationExport::SHAPE_APPLICATION ) );
		$this->assertSame( '"C0001";"Ana Pérez, Luis Díaz, Eva Ruiz"', $lineas[1], 'por solicitud, juntas en una celda' );
	}

	/**
	 * Una solicitud de profesorado no tiene coordinación: en forma `person` es
	 * una fila, la de quien la presenta.
	 */
	public function test_a_teacher_application_in_person_shape_is_one_row() {
		$procedimiento = $this->procedure(
			$this->administrator(),
			array(),
			array( ProcedureMetaKeys::AUDIENCE => ProcedureMetaKeys::AUDIENCE_TEACHERS )
		);
		$docente       = (int) self::factory()->user->create(
			array(
				'role'         => 'prc_teacher',
				'display_name' => 'Docente de prueba',
				'user_email'   => 'docente@example.org',
			)
		);
		$this->assertGreaterThan( 0, Applications::save_teacher( $procedimiento, $docente, array( 'answers' => array() ) ) );

		$lineas = $this->lineas( ApplicationExport::csv( $procedimiento, array( 'centre', 'applicant', 'email', 'coordinator' ), ApplicationExport::SHAPE_PERSON ) );
		$this->assertSame(
			array(
				'"Centro";"Presentada por";"Correo";"Coordinación"',
				'"Profesorado";"Docente de prueba";"docente@example.org";""',
			),
			$lineas
		);
	}

	// ─── el fichero de alta de usuarios ────────────────────────────────────

	/**
	 * Exactamente la cabecera de Moodle, coma, sin BOM, LF y sin salto final.
	 */
	public function test_the_user_file_has_the_exact_moodle_shape() {
		$procedimiento = $this->procedimiento();
		$this->solicitud(
			$procedimiento,
			'C0001',
			array(
				$this->persona( 'Ana', 'Pérez', 'Ana.Perez@example.org' ),
				$this->persona( 'Luis', 'Díaz', 'luis@example.org' ),
			)
		);

		$fichero = ApplicationExport::moodle( $procedimiento, array( 'who' => array( ApplicationExport::WHO_COORDINATORS ) ) );
		$this->assertSame(
			"username,firstname,lastname,email\nana.perez,Ana,Pérez,ana.perez@example.org\nluis,Luis,Díaz,luis@example.org",
			$fichero
		);
		$this->assertStringStartsNotWith( "\xEF\xBB\xBF", $fichero );
		$this->assertStringNotContainsString( "\r", $fichero );

		$vacio = ApplicationExport::moodle( $this->procedimiento(), array( 'who' => array( ApplicationExport::WHO_COORDINATORS ) ) );
		$this->assertSame( ApplicationExport::MOODLE_HEADER, $vacio, 'sin personas, solo la cabecera' );
	}

	/**
	 * Comas, comillas, tildes y eñes: se entrecomilla solo lo que lo exige.
	 */
	public function test_commas_quotes_accents_and_enye_survive_the_user_file() {
		$lineas = explode(
			"\n",
			ApplicationExport::moodle_lines(
				array(
					array(
						'username'  => 'mjose',
						'firstname' => 'María José',
						'lastname'  => 'Núñez, "la Peque"',
						'email'     => 'mjose@example.org',
					),
					array(
						'username'  => 'inaki',
						'firstname' => 'Iñaki',
						'lastname'  => "Ordóñez\nBis",
						'email'     => 'inaki@example.org',
					),
				)
			)
		);

		$this->assertSame( 'mjose,María José,"Núñez, ""la Peque""",mjose@example.org', $lineas[1] );
		$this->assertSame( 'inaki,Iñaki,"Ordóñez', $lineas[2], 'un salto dentro de un valor va entrecomillado' );
		$this->assertSame( 'Bis",inaki@example.org', $lineas[3] );
	}

	/**
	 * La misma persona en dos solicitudes da una fila, sin distinguir
	 * mayúsculas; quien no tiene correo válido no sale y se cuenta.
	 */
	public function test_the_same_person_twice_is_one_row_and_no_email_is_counted() {
		$procedimiento = $this->procedimiento();
		$this->solicitud(
			$procedimiento,
			'C0001',
			array(
				$this->persona( 'Ana', 'Pérez', 'ana@example.org' ),
				$this->persona( 'Sin', 'Correo', 'no es un correo' ),
			)
		);
		$this->solicitud( $procedimiento, 'C0002', array( $this->persona( 'Ana', 'Pérez', 'ANA@example.org' ) ) );

		$personas = ApplicationExport::people( $procedimiento, array( ApplicationExport::WHO_COORDINATORS ) );
		$this->assertCount( 1, $personas['people'] );
		$this->assertSame(
			array(
				'email'      => 1,
				'incomplete' => 0,
				'conflict'   => 0,
				'invalid'    => 0,
			),
			$personas['left_out'],
			'la misma persona repetida no es una omisión'
		);
		$this->assertSame( "username,firstname,lastname,email\nana,Ana,Pérez,ana@example.org", ApplicationExport::moodle( $procedimiento, array( 'who' => array( ApplicationExport::WHO_COORDINATORS ) ) ) );
	}

	/**
	 * El mismo usuario con otro correo no se funde en silencio: se queda la
	 * primera y la otra se cuenta como conflicto.
	 */
	public function test_the_same_username_with_another_email_is_a_conflict() {
		$procedimiento = $this->procedimiento();
		$this->solicitud( $procedimiento, 'C0001', array( $this->persona( 'Ana', 'Pérez', 'ana@example.org' ) ) );
		$this->solicitud( $procedimiento, 'C0002', array( $this->persona( 'Ana', 'Ruiz', 'ana@example.net' ) ) );

		$personas = ApplicationExport::people( $procedimiento, array( ApplicationExport::WHO_COORDINATORS ) );
		$this->assertCount( 1, $personas['people'] );
		$this->assertSame( 'ana@example.org', $personas['people'][0]['email'], 'se queda la primera' );
		$this->assertSame( 1, $personas['left_out']['conflict'] );
	}

	/**
	 * Sin nombre o sin apellidos no se crea una cuenta: la fila no sale y se
	 * cuenta como incompleta. Un hueco de coordinación vacío no cuenta.
	 */
	public function test_a_person_without_firstname_or_lastname_is_incomplete() {
		$procedimiento = $this->procedimiento();
		$this->solicitud(
			$procedimiento,
			'C0001',
			array(
				$this->persona( 'Cher', '', 'cher@example.org' ),
				array(
					'name'    => '',
					'surname' => '',
					'dni'     => '',
					'email'   => '',
				),
			),
			array(),
			array( 'display_name' => 'direccionnorte' )
		);

		$coordinacion = ApplicationExport::people( $procedimiento, array( ApplicationExport::WHO_COORDINATORS ) );
		$this->assertSame( array(), $coordinacion['people'] );
		$this->assertSame(
			array(
				'email'      => 0,
				'incomplete' => 1,
				'conflict'   => 0,
				'invalid'    => 0,
			),
			$coordinacion['left_out']
		);
		$this->assertSame( ApplicationExport::MOODLE_HEADER, ApplicationExport::moodle( $procedimiento, array( 'who' => array( ApplicationExport::WHO_COORDINATORS ) ) ) );

		$cuenta = ApplicationExport::people( $procedimiento, array( ApplicationExport::WHO_APPLICANT ) );
		$this->assertSame( 1, $cuenta['left_out']['incomplete'], 'una cuenta cuyo nombre público es una palabra' );
	}

	/**
	 * Un correo que se acepta como válido pero empieza por `=` daría un
	 * usuario y un correo que una hoja de cálculo ejecuta como fórmula: esa
	 * persona no sale y se cuenta como usuario no válido. Una normal sí sale.
	 */
	public function test_a_username_or_email_a_spreadsheet_would_run_is_left_out() {
		$procedimiento = $this->procedimiento();
		$this->solicitud(
			$procedimiento,
			'C0001',
			array(
				$this->persona( 'Ana', 'Pérez', 'ana@example.org' ),
				$this->persona( 'Uno', 'Más', '=1+1@example.org' ),
				$this->persona( 'Calc', 'Ulo', "=cmd|'/ccalc'!a0@example.org" ),
			)
		);

		$personas = ApplicationExport::people( $procedimiento, array( ApplicationExport::WHO_COORDINATORS ) );
		$this->assertSame( array( 'ana' ), array_column( $personas['people'], 'username' ) );
		$this->assertSame(
			array(
				'email'      => 0,
				'incomplete' => 0,
				'conflict'   => 0,
				'invalid'    => 2,
			),
			$personas['left_out']
		);
		$fichero = ApplicationExport::moodle( $procedimiento, array( 'who' => array( ApplicationExport::WHO_COORDINATORS ) ) );
		$this->assertSame( "username,firstname,lastname,email\nana,Ana,Pérez,ana@example.org", $fichero );
	}

	/**
	 * El filtro del usuario no abre la puerta: lo que devuelva tiene que ser
	 * un usuario válido para la plataforma.
	 */
	public function test_a_filtered_username_a_spreadsheet_would_run_is_left_out() {
		$procedimiento = $this->procedimiento();
		$this->solicitud( $procedimiento, 'C0001', array( $this->persona( 'Ana', 'Pérez', 'ana@example.org' ) ) );
		$filtro = static function (): string {
			return '+SUM(1)';
		};
		add_filter( 'prc_export_username', $filtro );
		$personas = ApplicationExport::people( $procedimiento, array( ApplicationExport::WHO_COORDINATORS ) );
		remove_filter( 'prc_export_username', $filtro );

		$this->assertSame( array(), $personas['people'] );
		$this->assertSame( 1, $personas['left_out']['invalid'] );
	}

	/**
	 * Los recuentos del diálogo salen de una sola pasada por las solicitudes
	 * que el panel ya tiene: las consultas no crecen con las fuentes.
	 */
	public function test_who_counts_does_one_pass_whatever_the_sources() {
		$procedimiento = $this->procedimiento();
		$this->solicitud( $procedimiento, 'C0001', array( $this->persona( 'Ana', 'Pérez', 'ana@example.org' ), $this->persona( 'Sin', 'Correo', '' ) ) );
		$this->solicitud( $procedimiento, 'C0002', array( $this->persona( 'Luis', 'Díaz', 'luis@example.org' ) ) );
		// Lo que hace el panel: las solicitudes, una vez, y la tabla con ellas.
		$solicitudes = Applications::for_procedure( $procedimiento );
		Applications::rows( $procedimiento, $solicitudes );

		$consultas = function ( array $who ) use ( $solicitudes ): int {
			global $wpdb;
			$antes = $wpdb->num_queries;
			ApplicationExport::who_counts( $solicitudes, $who );
			return $wpdb->num_queries - $antes;
		};
		$una       = $consultas( array( ApplicationExport::WHO_COORDINATORS ) );
		$todas     = $consultas( array() );
		$this->assertSame( $una, $todas, 'más fuentes, las mismas consultas' );
		$this->assertSame( 0, $todas, 'todo lo necesario ya lo cargó la tabla' );

		$this->assertSame(
			array(
				ApplicationExport::WHO_COORDINATORS => array(
					'count'      => 2,
					'email'      => 1,
					'incomplete' => 0,
					'conflict'   => 0,
					'invalid'    => 0,
				),
				ApplicationExport::WHO_APPLICANT    => array(
					'count'      => 0,
					'email'      => 0,
					'incomplete' => 2,
					'conflict'   => 0,
					'invalid'    => 0,
				),
			),
			ApplicationExport::who_counts( $solicitudes ),
			'las cuentas de prueba no tienen nombre ni apellidos, y su nombre público es el usuario'
		);
	}

	/**
	 * El usuario de quien presenta es el de su cuenta; su nombre, el de la
	 * cuenta o, si falta, el nombre público partido por el primer espacio.
	 * Ninguna fila repite usuario aunque salgan las dos fuentes.
	 */
	public function test_the_applicant_uses_the_account() {
		$procedimiento = $this->procedimiento();
		$this->solicitud(
			$procedimiento,
			'C0001',
			array( $this->persona( 'Ana', 'Pérez', 'ana@example.org' ) ),
			array(),
			array(
				'user_email'   => 'direccion1@example.org',
				'first_name'   => 'Carmen',
				'last_name'    => 'Sosa Vera',
				'display_name' => 'C. Sosa',
			)
		);
		$this->solicitud(
			$procedimiento,
			'C0002',
			array( $this->persona( 'Ana', 'Pérez', 'ana@example.org' ) ),
			array(),
			array(
				'user_email'   => 'direccion2@example.org',
				'display_name' => 'Juan Antonio Mora',
			)
		);

		// Las cuentas de prueba se llaman «User N», con un espacio que la
		// plataforma no acepta: aquí llevan un usuario corporativo.
		global $wpdb;
		foreach ( array(
			'C0001' => 'CSosa',
			'C0002' => 'jamora',
		) as $codigo => $usuario ) {
			$cuenta = (int) get_post_field( 'post_author', (int) Applications::find( $procedimiento, $codigo ) );
			$wpdb->update( $wpdb->users, array( 'user_login' => $usuario ), array( 'ID' => $cuenta ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			clean_user_cache( $cuenta );
		}

		$login = (string) get_userdata( (int) get_post_field( 'post_author', (int) Applications::find( $procedimiento, 'C0001' ) ) )->user_login;
		$this->assertSame( 'CSosa', $login );
		$filas = explode( "\n", ApplicationExport::moodle( $procedimiento, array( 'who' => array( ApplicationExport::WHO_APPLICANT ) ) ) );
		$this->assertCount( 3, $filas );
		$this->assertSame( ApplicationExport::MOODLE_HEADER, $filas[0] );
		$this->assertSame( $login . ',Carmen,Sosa Vera,direccion1@example.org', $filas[1], 'el usuario es el user_login de la cuenta, tal cual' );
		$this->assertStringEndsWith( ',Juan,Antonio Mora,direccion2@example.org', $filas[2] );

		$ambas = ApplicationExport::people( $procedimiento, array() );
		$this->assertCount( 3, $ambas['people'], 'sin elegir quiénes, salen todas las fuentes; Ana una vez' );
	}

	/**
	 * Lo guardado antes de los apellidos se parte por el primer espacio.
	 */
	public function test_a_coordinator_without_surname_is_split_at_the_first_space() {
		$procedimiento = $this->procedimiento();
		$this->solicitud( $procedimiento, 'C0001', array( $this->persona( 'Ana María Pérez', '', 'amp@example.org' ) ) );

		$this->assertSame( array( 'Ana', 'María Pérez' ), ApplicationMetaRegistration::split_name( '  Ana María Pérez ' ) );
		$this->assertSame( array( 'Cher', '' ), ApplicationMetaRegistration::split_name( 'Cher' ) );
		$this->assertSame(
			"username,firstname,lastname,email\namp,Ana,María Pérez,amp@example.org",
			ApplicationExport::moodle( $procedimiento, array( 'who' => array( ApplicationExport::WHO_COORDINATORS ) ) )
		);
	}

	/**
	 * El usuario se puede cambiar con el filtro `prc_export_username`; uno
	 * vacío deja a la persona fuera, contada.
	 */
	public function test_the_username_filter_decides_the_username() {
		$procedimiento = $this->procedimiento();
		$this->solicitud(
			$procedimiento,
			'C0001',
			array(
				$this->persona( 'Ana', 'Pérez', 'ana@example.org' ),
				$this->persona( 'Luis', 'Díaz', 'luis@example.org' ),
			)
		);
		add_filter(
			'prc_export_username',
			static function ( string $username, array $person ): string {
				return 'luis@example.org' === $person['email'] ? '' : 'x-' . $username . '-' . $person['source'];
			},
			10,
			2
		);

		$personas = ApplicationExport::people( $procedimiento, array( ApplicationExport::WHO_COORDINATORS ) );
		$this->assertSame( 'x-ana-coordinators', $personas['people'][0]['username'] );
		$this->assertCount( 1, $personas['people'] );
		$this->assertSame( 1, $personas['left_out']['incomplete'], 'sin usuario no hay cuenta que crear' );
	}

	/**
	 * Un nombre que empieza por `=` sale neutralizado en los dos formatos.
	 */
	public function test_a_formula_is_neutralised_in_both_formats() {
		$procedimiento = $this->procedimiento();
		$this->solicitud( $procedimiento, 'C0001', array( $this->persona( '=HYPERLINK("x")', '-Pérez', 'ana@example.org' ) ) );

		$csv = ApplicationExport::csv( $procedimiento, array( 'coordinator' ), ApplicationExport::SHAPE_PERSON );
		$this->assertSame( '"\'=HYPERLINK(""x"") -Pérez"', $this->lineas( $csv )[1] );

		$this->assertSame(
			"username,firstname,lastname,email\nana,\"'=HYPERLINK(\"\"x\"\")\",'-Pérez,ana@example.org",
			ApplicationExport::moodle( $procedimiento, array( 'who' => array( ApplicationExport::WHO_COORDINATORS ) ) )
		);
		// El usuario y el correo no se neutralizan: una comilla delante los
		// convertiría en otro usuario y en otro correo. Quien los tiene así
		// no sale, y se cuenta.
		add_filter(
			'prc_export_username',
			static function (): string {
				return '-ana';
			}
		);
		$this->assertSame( ApplicationExport::MOODLE_HEADER, ApplicationExport::moodle( $procedimiento, array( 'who' => array( ApplicationExport::WHO_COORDINATORS ) ) ) );
		$this->assertSame( 1, ApplicationExport::people( $procedimiento, array( ApplicationExport::WHO_COORDINATORS ) )['left_out']['invalid'] );
		$this->assertSame( "'@x", ApplicationExport::neutralize( '@x' ) );
		$this->assertSame( "'+1", ApplicationExport::neutralize( '+1' ) );
		$this->assertSame( 'normal', ApplicationExport::neutralize( 'normal' ) );
	}

	// ─── la elección, el diálogo y la descarga ─────────────────────────────

	/**
	 * La elección se valida: columnas que existen, forma, formato y quiénes de
	 * sus listas cerradas.
	 */
	public function test_the_choice_is_validated() {
		$procedimiento = $this->procedimiento();

		$this->assertSame(
			array(
				'columns' => array( 'q3', 'code' ),
				'shape'   => ApplicationExport::SHAPE_PERSON,
				'format'  => ApplicationExport::FORMAT_MOODLE,
				'who'     => array( ApplicationExport::WHO_APPLICANT ),
			),
			ApplicationExport::sanitize_choice(
				$procedimiento,
				array(
					'columns' => array( 'q3', 'nada', 'code', 'q3' ),
					'shape'   => ApplicationExport::SHAPE_PERSON,
					'format'  => ApplicationExport::FORMAT_MOODLE,
					'who'     => array( 'otra', ApplicationExport::WHO_APPLICANT ),
				)
			)
		);
		$this->assertSame(
			array(
				'columns' => array(),
				'shape'   => ApplicationExport::SHAPE_APPLICATION,
				'format'  => ApplicationExport::FORMAT_CSV,
				'who'     => array(),
			),
			ApplicationExport::sanitize_choice(
				$procedimiento,
				array(
					'all'     => '1',
					'columns' => array( 'code' ),
					'shape'   => 'otra',
					'format'  => 'xlsx',
					'who'     => 'no es una lista',
				)
			),
			'«todas» gana a las casillas; lo desconocido vuelve a lo de siempre'
		);
	}

	/**
	 * La descarga a medida va por POST, y la elección se recuerda por persona y
	 * procedimiento en `prc_export_{id}`.
	 */
	public function test_the_download_follows_the_choice_and_remembers_it() {
		$procedimiento = $this->procedimiento();
		$this->solicitud( $procedimiento, 'C0001', array( $this->persona( 'Ana', 'Pérez', 'ana@example.org' ) ) );
		$quien = $this->administrator();

		$csv = $this->exportar(
			$quien,
			$procedimiento,
			array(
				ProcedureEditor::FIELD_EXPORT_COLUMNS => array( 'code', 'q3' ),
				ProcedureEditor::FIELD_EXPORT_SHAPE   => ApplicationExport::SHAPE_PERSON,
				ProcedureEditor::FIELD_EXPORT_FORMAT  => ApplicationExport::FORMAT_CSV,
			)
		);
		$this->assertSame( "\xEF\xBB\xBF\"Código\";\"Modalidad\"\r\n\"C0001\";\"B\"\r\n", $csv );

		$guardada = get_user_meta( $quien, ApplicationExport::CHOICE_META . $procedimiento, true );
		$this->assertSame( array( 'code', 'q3' ), $guardada['columns'] );
		$this->assertSame( ApplicationExport::SHAPE_PERSON, $guardada['shape'] );
		$this->assertSame( $guardada, ApplicationExport::choice( $quien, $procedimiento ) );
		$this->assertSame( '', get_user_meta( $this->administrator(), ApplicationExport::CHOICE_META . $procedimiento, true ), 'es de cada persona' );

		$usuarios = $this->exportar(
			$quien,
			$procedimiento,
			array(
				ProcedureEditor::FIELD_EXPORT_FORMAT => ApplicationExport::FORMAT_MOODLE,
				ProcedureEditor::FIELD_EXPORT_WHO    => array( ApplicationExport::WHO_COORDINATORS ),
			)
		);
		$this->assertSame( "username,firstname,lastname,email\nana,Ana,Pérez,ana@example.org", $usuarios );
		$this->assertSame( ApplicationExport::FORMAT_MOODLE, ApplicationExport::choice( $quien, $procedimiento )['format'] );
	}

	/**
	 * Una elección guardada con una pregunta que ya no existe la pierde al leerla.
	 */
	public function test_a_remembered_choice_forgets_deleted_questions() {
		$procedimiento = $this->procedimiento();
		$quien         = $this->administrator();
		ApplicationExport::remember(
			$quien,
			$procedimiento,
			array(
				'columns' => array( 'q2', 'code' ),
				'shape'   => ApplicationExport::SHAPE_APPLICATION,
				'format'  => ApplicationExport::FORMAT_CSV,
				'who'     => array(),
			)
		);
		update_post_meta( $procedimiento, ProcedureMetaKeys::QUESTIONS, array( Applications::questions( $procedimiento )[0] ) );

		$this->assertSame( array( 'code' ), ApplicationExport::choice( $quien, $procedimiento )['columns'] );
		$this->assertSame( array(), ApplicationExport::choice( $this->administrator(), $procedimiento )['columns'], 'sin elección guardada, todas' );
	}

	/**
	 * El diálogo: casillas por grupo, «todas», forma, formato, quiénes y el
	 * recuento de quien se queda fuera del fichero de usuarios por no tener
	 * correo. Funciona sin guion: es un desplegable con el formulario dentro.
	 */
	public function test_the_dialog_paints_groups_choice_and_who_is_left_out() {
		$procedimiento = $this->procedimiento();
		$this->solicitud(
			$procedimiento,
			'C0001',
			array(
				$this->persona( 'Ana', 'Pérez', 'ana@example.org' ),
				$this->persona( 'Sin', 'Correo', '' ),
				$this->persona( 'Uno', 'Más', '=1+1@example.org' ),
			)
		);
		$quien = $this->administrator();
		ApplicationExport::remember(
			$quien,
			$procedimiento,
			array(
				'columns' => array( 'code' ),
				'shape'   => ApplicationExport::SHAPE_PERSON,
				'format'  => ApplicationExport::FORMAT_CSV,
				'who'     => array( ApplicationExport::WHO_COORDINATORS ),
			)
		);
		$this->acting_as( $quien );
		$_GET[ ProcedureEditor::ARG_PROCEDURE ] = (string) $procedimiento;
		$_GET[ ProcedureEditor::ARG_PANEL ]     = ProcedureEditor::PANEL_APPLICATIONS;
		$html                                   = ProcedureEditorView::html( ProcedureEditor::model() );

		$this->assertStringContainsString( '<details class="prc-exportar" data-prc-export>', $html );
		foreach ( ApplicationExport::group_labels() as $rotulo ) {
			$this->assertStringContainsString( '<legend>' . esc_html( $rotulo ) . '</legend>', $html );
		}
		$this->assertStringContainsString( 'name="' . ProcedureEditor::FIELD_EXPORT_ALL . '"', $html );
		$this->assertMatchesRegularExpression( '/name="' . preg_quote( ProcedureEditor::FIELD_EXPORT_COLUMNS, '/' ) . '\[\]" value="code"[^>]*checked/', $html );
		$this->assertDoesNotMatchRegularExpression( '/name="' . preg_quote( ProcedureEditor::FIELD_EXPORT_COLUMNS, '/' ) . '\[\]" value="q3"[^>]*checked/', $html );
		$this->assertMatchesRegularExpression( '/value="' . ApplicationExport::SHAPE_PERSON . '"[^>]*checked/', $html );
		$this->assertMatchesRegularExpression( '/value="' . ApplicationExport::FORMAT_MOODLE . '"/', $html );
		$this->assertStringContainsString( 'Coordinación (1 persona. No saldrán: 1 sin correo válido, 1 con un usuario no válido para la plataforma)', $html );
		$this->assertStringContainsString( 'Quien presenta la solicitud (0 personas. No saldrán: 1 incompleta)', $html );
	}

	/**
	 * Lo que diga una pregunta sale escapado en el diálogo, nunca como HTML:
	 * ni sus etiquetas, ni sus `&` ni sus comillas.
	 */
	public function test_a_question_label_with_html_is_escaped_in_the_dialog() {
		$procedimiento = $this->procedimiento();
		update_post_meta(
			$procedimiento,
			ProcedureMetaKeys::QUESTIONS,
			array(
				array(
					'key'   => 'q1',
					'label' => '<b>Huerto</b> & "más"',
					'type'  => ProcedureQuestions::TYPE_TEXT,
				),
			)
		);
		$this->solicitud( $procedimiento, 'C0001', array( $this->persona( 'Ana', 'Pérez', 'ana@example.org' ) ) );
		$this->acting_as( $this->administrator() );
		$_GET[ ProcedureEditor::ARG_PROCEDURE ] = (string) $procedimiento;
		$_GET[ ProcedureEditor::ARG_PANEL ]     = ProcedureEditor::PANEL_APPLICATIONS;
		$html                                   = ProcedureEditorView::html( ProcedureEditor::model() );

		$dialogo = substr( $html, (int) strpos( $html, 'data-prc-export' ) );
		// Las etiquetas ya las quita la meta al guardar; lo que llega, sale escapado.
		$this->assertStringContainsString( 'Huerto &amp; &quot;más&quot;', $dialogo );
		$this->assertStringNotContainsString( '<b>', $dialogo );
		$this->assertStringNotContainsString( '& "más"', $dialogo );
	}

	/**
	 * Borrar el procedimiento para siempre se lleva la elección de cada persona.
	 */
	public function test_deleting_the_procedure_forgets_every_choice() {
		$procedimiento = $this->procedimiento();
		$otro          = $this->procedimiento();
		$una           = $this->administrator();
		$dos           = $this->administrator();
		$eleccion      = array( 'columns' => array( 'code' ) );
		ApplicationExport::remember( $una, $procedimiento, $eleccion );
		ApplicationExport::remember( $dos, $procedimiento, $eleccion );
		ApplicationExport::remember( $una, $otro, $eleccion );

		wp_trash_post( $procedimiento );
		$this->assertNotSame( '', get_user_meta( $una, ApplicationExport::CHOICE_META . $procedimiento, true ), 'la papelera no borra nada' );

		wp_delete_post( $procedimiento, true );
		$this->assertSame( '', get_user_meta( $una, ApplicationExport::CHOICE_META . $procedimiento, true ) );
		$this->assertSame( '', get_user_meta( $dos, ApplicationExport::CHOICE_META . $procedimiento, true ) );
		$this->assertNotSame( '', get_user_meta( $una, ApplicationExport::CHOICE_META . $otro, true ), 'la de otro procedimiento se queda' );
	}

	// ─── rejilla y puntos (ADR-0040) ───────────────────────────────────────

	/**
	 * Un procedimiento con una rejilla, unos méritos con puntos y un número.
	 *
	 * @return int Procedure ID.
	 */
	private function con_tipos_nuevos(): int {
		return $this->procedure(
			$this->administrator(),
			array( $this->area( 'Área de prueba' ) ),
			array(
				ProcedureMetaKeys::QUESTIONS => array(
					array(
						'key'     => 'q1',
						'label'   => 'Méritos',
						'type'    => ProcedureQuestions::TYPE_MULTIPLE,
						'choices' => "Huerto | 3\nRadio | 2\nBiblioteca",
					),
					array(
						'key'   => 'q2',
						'label' => 'Grupos',
						'type'  => ProcedureQuestions::TYPE_NUMBER,
					),
					array(
						'key'   => 'q4',
						'label' => 'Alumnado',
						'type'  => ProcedureQuestions::TYPE_GRID,
						'rows'  => "Infantil\nPrimaria",
					),
				),
			),
			array( 'post_title' => 'Red de méritos' )
		);
	}

	/**
	 * Cada fila de una rejilla es una columna `q4:fila` en la exportación, y
	 * los puntos, una columna calculada en el grupo de revisión.
	 */
	public function test_each_grid_row_is_a_column_and_the_score_is_a_review_column() {
		$procedimiento = $this->con_tipos_nuevos();
		$this->solicitud(
			$procedimiento,
			'C0001',
			array(),
			array(
				'q1' => array( 'Huerto', 'Biblioteca' ),
				'q2' => 0,
				'q4' => array(
					'r1' => 3,
					'r2' => '',
				),
			)
		);

		$grupos = ApplicationExport::groups( $procedimiento );
		$this->assertSame(
			array(
				'q1'    => 'Méritos',
				'q2'    => 'Grupos',
				'q4:r1' => 'Alumnado — Infantil',
				'q4:r2' => 'Alumnado — Primaria',
			),
			$grupos['questions']
		);
		$this->assertSame( array( 'state', 'note', 'score' ), array_keys( $grupos['review'] ) );
		$this->assertSame( 'Puntos', $grupos['review']['score'] );

		$lineas = $this->lineas( ApplicationExport::csv( $procedimiento, array( 'code', 'score', 'q2', 'q4:r1', 'q4:r2' ) ) );
		$this->assertSame( array( '"Código";"Puntos";"Grupos";"Alumnado — Infantil";"Alumnado — Primaria"', '"C0001";"3";"0";"3";""' ), $lineas );

		$lineas = $this->lineas( ApplicationExport::csv( $procedimiento ) );
		$this->assertStringEndsWith( ';"Nota";"Puntos";"Méritos";"Grupos";"Alumnado — Infantil";"Alumnado — Primaria"', $lineas[0], 'todas: la rejilla, por filas' );
		$this->assertStringEndsWith( ';"";"3";"Huerto, Biblioteca";"0";"3";""', $lineas[1] );

		// La tabla del taller la lleva en una sola celda.
		$fila = Applications::rows( $procedimiento )[0];
		$this->assertSame( 'Infantil: 3', $fila['q4'] );
		$this->assertSame( '3', $fila['score'] );
		$this->assertArrayHasKey( 'q4', Applications::columns( Applications::questions( $procedimiento ) ) );
	}

	/**
	 * Las columnas de una rejilla llegan al servidor con sus dos puntos y se recuerdan.
	 */
	public function test_a_grid_column_survives_the_dialog() {
		$procedimiento = $this->con_tipos_nuevos();
		$this->solicitud( $procedimiento, 'C0001', array(), array( 'q4' => array( 'r2' => 5 ) ) );
		$quien = $this->administrator();

		$csv = $this->exportar(
			$quien,
			$procedimiento,
			array(
				ProcedureEditor::FIELD_EXPORT_COLUMNS => array( 'code', 'q4:r2' ),
				ProcedureEditor::FIELD_EXPORT_FORMAT  => ApplicationExport::FORMAT_CSV,
			)
		);
		$this->assertSame( "\xEF\xBB\xBF\"Código\";\"Alumnado — Primaria\"\r\n\"C0001\";\"5\"\r\n", $csv );
		$this->assertSame( array( 'code', 'q4:r2' ), ApplicationExport::choice( $quien, $procedimiento )['columns'] );
	}

	/**
	 * Sin puntos no hay columna de puntos, y el CSV de siempre no cambia ni un byte.
	 */
	public function test_without_points_there_is_no_score_column() {
		$procedimiento = $this->procedimiento();

		$this->assertArrayNotHasKey( 'score', Applications::columns( Applications::questions( $procedimiento ) ) );
		$this->assertArrayNotHasKey( 'score', ApplicationExport::groups( $procedimiento )['review'] );
		$id = $this->solicitud( $procedimiento, 'C0001', array( $this->persona( 'Ana', 'Pérez', 'ana@example.org' ) ) );
		$this->assertArrayNotHasKey( 'score', Applications::rows( $procedimiento )[0] );
		$this->assertStringEndsWith( '"Nota";"Observaciones";"Tiene huerto";"Modalidad"', $this->lineas( ApplicationExport::csv( $procedimiento ) )[0] );
		$this->assertSame( 1, substr_count( ApplicationExport::csv( $procedimiento ), (string) get_the_date( 'Y-m-d H:i', $id ) ) );
	}

	/**
	 * Unas opciones guardadas antes de los puntos, con «|» en el texto, siguen
	 * siendo ese texto: la respuesta casa y no aparece la columna de puntos.
	 */
	public function test_old_choices_with_a_bar_keep_their_text() {
		$procedimiento = $this->procedure(
			$this->administrator(),
			array( $this->area( 'Área de prueba' ) ),
			array(
				ProcedureMetaKeys::QUESTIONS => array(
					array(
						'key'     => 'q1',
						'label'   => 'Curso',
						'type'    => ProcedureQuestions::TYPE_MULTIPLE,
						'choices' => array( 'Curso | 2024', 'Otro' ),
					),
				),
			)
		);
		$this->solicitud( $procedimiento, 'C0001', array(), array( 'q1' => array( 'Curso | 2024' ) ) );

		$this->assertArrayNotHasKey( 'score', Applications::columns( Applications::questions( $procedimiento ) ) );
		$fila = Applications::rows( $procedimiento )[0];
		$this->assertSame( 'Curso | 2024', $fila['q1'] );
		$this->assertArrayNotHasKey( 'score', $fila );
	}
}
