<?php
/**
 * Export the applications of a procedure: chosen columns, one row per
 * application or per person, and a user upload file for Moodle.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\Domain\ProcedureInput;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ApplicationMetaRegistration;
use Prc\PostType\ApplicationPostType;

/**
 * Exportar a medida las solicitudes de un procedimiento (ADR-0039).
 *
 * Dos formatos:
 *
 * - **CSV** para una hoja de cálculo, con el mismo formato de siempre
 *   ({@see Applications::csv_lines()}), las columnas que se elijan y una fila
 *   por solicitud o por persona coordinadora.
 * - **Fichero de alta de usuarios** de Moodle: `username,firstname,lastname,email`,
 *   una fila por persona y sin repetir usuario.
 *
 * Aquí no se lee la petición ni se comprueba quién pregunta: eso lo hace
 * {@see ProcedureEditor}, con su nonce y `ProcedureAccess::can_review()`.
 * Las solicitudes se leen por tandas, no todas a la vez.
 */
final class ApplicationExport {

	/** One row per application: today's shape. */
	public const SHAPE_APPLICATION = 'application';

	/** One row per coordinator; the application columns repeat in each. */
	public const SHAPE_PERSON = 'person';

	public const FORMAT_CSV    = 'csv';
	public const FORMAT_MOODLE = 'moodle';

	/** People typed in the form as the application's coordination. */
	public const WHO_COORDINATORS = 'coordinators';

	/** The account that submitted the application. */
	public const WHO_APPLICANT = 'applicant';

	/** Exact first line of the user upload file. */
	public const MOODLE_HEADER = 'username,firstname,lastname,email';

	/**
	 * Prefix of the user meta that remembers the last choice; the procedure ID follows.
	 */
	public const CHOICE_META = 'prc_export_';

	/** Applications read per query. */
	private const BATCH = 100;

	/**
	 * Fixed columns of each group, keys of {@see Applications::columns()}.
	 *
	 * @var array<string, string[]>
	 */
	private const GROUPS = array(
		'application'  => array( 'date' ),
		'centre'       => array( 'centre', 'code' ),
		'applicant'    => array( 'applicant', 'email', 'position' ),
		'coordination' => array( 'coordinator', 'coordinator_dni', 'coordinator_email' ),
		'review'       => array( 'state', 'note' ),
	);

	// ─── las columnas ──────────────────────────────────────────────────────

	/**
	 * Heading of each group of columns, in the order the dialog paints them.
	 *
	 * @return array<string, string>
	 */
	public static function group_labels(): array {
		return array(
			'application'  => 'Solicitud',
			'centre'       => 'Centro',
			'applicant'    => 'Quien presenta',
			'coordination' => 'Coordinación',
			'review'       => 'Revisión',
			'questions'    => 'Preguntas',
		);
	}

	/**
	 * The columns one can export, grouped.
	 *
	 * Las preguntas son las que el procedimiento tiene hoy, por su `key`: una
	 * borrada ya no se ofrece, y su respuesta se queda en la meta (ADR-0019).
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return array<string, array<string, string>> group => (column key => heading).
	 */
	public static function groups( int $procedure_id ): array {
		$preguntas = Applications::questions( $procedure_id );
		$todas     = Applications::columns( $preguntas );
		$out       = array();
		foreach ( self::GROUPS as $grupo => $claves ) {
			$out[ $grupo ] = array_intersect_key( $todas, array_flip( $claves ) );
		}
		$out['questions'] = array();
		foreach ( $preguntas as $pregunta ) {
			$out['questions'][ (string) $pregunta['key'] ] = (string) $pregunta['label'];
		}
		return $out;
	}

	/**
	 * The chosen columns that exist, in the order asked, without repeats.
	 *
	 * @param int   $procedure_id Procedure post ID.
	 * @param array $keys         Column keys asked for; anything else is ignored.
	 * @return array<string, string> key => heading; empty when none is valid.
	 */
	private static function valid_columns( int $procedure_id, array $keys ): array {
		$todas = Applications::columns( Applications::questions( $procedure_id ) );
		$out   = array();
		foreach ( $keys as $clave ) {
			if ( is_string( $clave ) && isset( $todas[ $clave ] ) ) {
				$out[ $clave ] = $todas[ $clave ];
			}
		}
		return $out;
	}

	// ─── el CSV ────────────────────────────────────────────────────────────

	/**
	 * The applications as a CSV file, with the chosen columns and shape.
	 *
	 * Sin ninguna columna válida, o con todas elegidas, salen todas en el
	 * orden de la tabla: es el CSV de siempre. El formato no cambia: `;`,
	 * BOM, CRLF y cada celda entrecomillada y neutralizada
	 * ({@see Applications::csv_lines()}).
	 *
	 * @param int      $procedure_id Procedure post ID.
	 * @param string[] $columns      Column keys, in order; empty for all.
	 * @param string   $shape        {@see SHAPE_APPLICATION} or {@see SHAPE_PERSON}.
	 * @return string
	 */
	public static function csv( int $procedure_id, array $columns = array(), string $shape = self::SHAPE_APPLICATION ): string {
		$preguntas = Applications::questions( $procedure_id );
		$todas     = Applications::columns( $preguntas );
		$columnas  = self::valid_columns( $procedure_id, $columns );
		// Ninguna válida, o todas aunque sea en otro orden: el CSV de siempre.
		if ( array() === $columnas || count( $columnas ) === count( $todas ) ) {
			$columnas = $todas;
		}
		$claves = array_flip( array_keys( $columnas ) );
		$filas  = array();
		foreach ( self::batches( $procedure_id ) as $tanda ) {
			foreach ( $tanda as $solicitud ) {
				$fila = Applications::row( $solicitud, $preguntas );
				foreach ( self::SHAPE_PERSON === $shape ? self::per_person( $solicitud, $fila ) : array( $fila ) as $una ) {
					// Solo lo que se va a escribir: la fila entera no se guarda.
					$filas[] = array_intersect_key( $una, $claves );
				}
			}
		}
		return Applications::csv_lines( $columnas, $filas );
	}

	/**
	 * One application row per coordinator, each with that person's columns.
	 *
	 * Sin coordinación —una solicitud de profesorado, o un procedimiento que
	 * no la pide— la solicitud sale en una fila, la de quien la presenta.
	 *
	 * @param \WP_Post             $application The application.
	 * @param array<string, mixed> $row         Its row from {@see Applications::row()}.
	 * @return array<int, array<string, mixed>>
	 */
	private static function per_person( \WP_Post $application, array $row ): array {
		$out = array();
		foreach ( self::coordinators( $application ) as $persona ) {
			$out[] = array_merge(
				$row,
				array(
					'coordinator'       => ApplicationMetaRegistration::full_name( $persona ),
					'coordinator_dni'   => $persona['dni'],
					'coordinator_email' => $persona['email'],
				)
			);
		}
		return array() === $out ? array( $row ) : $out;
	}

	// ─── el fichero de alta de usuarios ────────────────────────────────────

	/**
	 * Who can go in the user upload file, with the label the dialog shows.
	 *
	 * Es el punto de extensión de las fuentes de personas: el PR 4 añade
	 * aquí las personas de la solicitud, y su caso en {@see from_source()}.
	 *
	 * @return array<string, string>
	 */
	public static function who(): array {
		return array(
			self::WHO_COORDINATORS => 'Coordinación',
			self::WHO_APPLICANT    => 'Quien presenta la solicitud',
		);
	}

	/**
	 * The people of a procedure for the user upload file, without repeats.
	 *
	 * Una persona sale una vez aunque esté en varias solicitudes: manda el
	 * `username`, sin distinguir mayúsculas, y gana la primera. Lo que no sale
	 * se cuenta aparte en `left_out`, que es lo que el diálogo avisa antes de
	 * descargar:
	 *
	 * - `email`: sin un correo válido;
	 * - `incomplete`: sin nombre, sin apellidos o sin usuario —una cuenta sin
	 *   nombre cuyo nombre público es el usuario, una coordinación antigua de
	 *   una sola palabra, o el filtro que devuelve vacío—: Moodle no crea esa
	 *   cuenta;
	 * - `conflict`: el mismo usuario con **otro** correo. No se funde en
	 *   silencio: se queda la primera y esta se cuenta. Con el mismo correo es
	 *   la misma persona, y no cuenta.
	 *
	 * Un hueco de coordinación vacío no es una persona y no cuenta.
	 *
	 * @param int      $procedure_id Procedure post ID.
	 * @param string[] $who          Keys of {@see who()}; empty for all.
	 * @return array{people:array<int, array<string, mixed>>, left_out:array{email:int, incomplete:int, conflict:int}}
	 */
	public static function people( int $procedure_id, array $who = array() ): array {
		$cuenta = self::tally( self::batches( $procedure_id ), array( 'all' => self::sources( $who ) ) )['all'];
		return array(
			'people'   => array_values( $cuenta['people'] ),
			'left_out' => $cuenta['left_out'],
		);
	}

	/**
	 * For each source, how many people would go in the user upload file and
	 * how many would be left out, and why.
	 *
	 * Una sola pasada por las solicitudes, y las que se le dan: el panel ya
	 * las ha cargado para pintar la tabla, con sus metas y sus cuentas, así
	 * que añadir una fuente no añade consultas.
	 *
	 * @param \WP_Post[] $applications Applications already loaded.
	 * @param string[]   $who          Keys of {@see who()}; empty for all.
	 * @return array<string, array{count:int, email:int, incomplete:int, conflict:int}>
	 */
	public static function who_counts( array $applications, array $who = array() ): array {
		$grupos = array();
		foreach ( self::sources( $who ) as $fuente ) {
			$grupos[ $fuente ] = array( $fuente );
		}
		$out = array();
		foreach ( self::tally( array( $applications ), $grupos ) as $fuente => $cuenta ) {
			$out[ $fuente ] = array( 'count' => count( $cuenta['people'] ) ) + $cuenta['left_out'];
		}
		return $out;
	}

	/**
	 * The sources asked for, in the order of {@see who()}; all when none is valid.
	 *
	 * @param array $who Keys asked for.
	 * @return string[]
	 */
	private static function sources( array $who ): array {
		$fuentes = array_values( array_intersect( array_keys( self::who() ), $who ) );
		return array() === $fuentes ? array_keys( self::who() ) : $fuentes;
	}

	/**
	 * Go through the applications once and gather the people of each group of sources.
	 *
	 * @param iterable                $batches Applications, a batch (array of \WP_Post) at a time.
	 * @param array<string, string[]> $groups  Group => sources.
	 * @return array<string, array{people:array<string, array<string, mixed>>, left_out:array{email:int, incomplete:int, conflict:int}}>
	 */
	private static function tally( iterable $batches, array $groups ): array {
		$out = array();
		foreach ( array_keys( $groups ) as $grupo ) {
			$out[ $grupo ] = array(
				'people'   => array(),
				'left_out' => array(
					'email'      => 0,
					'incomplete' => 0,
					'conflict'   => 0,
				),
			);
		}
		foreach ( $batches as $tanda ) {
			foreach ( $tanda as $solicitud ) {
				// Cada fuente se lee una vez por solicitud, la pidan los grupos que la pidan.
				$de = array();
				foreach ( $groups as $grupo => $fuentes ) {
					foreach ( $fuentes as $fuente ) {
						$de[ $fuente ] = $de[ $fuente ] ?? self::from_source( $fuente, $solicitud );
						foreach ( $de[ $fuente ] as $persona ) {
							self::add( $out[ $grupo ], $persona );
						}
					}
				}
			}
		}
		return $out;
	}

	/**
	 * Add one person to a tally, or count why not.
	 *
	 * @param array<string, mixed> $tally  The tally, changed in place.
	 * @param array<string, mixed> $person The person.
	 * @return void
	 */
	private static function add( array &$tally, array $person ): void {
		if ( ! ProcedureInput::is_email( (string) $person['email'] ) ) {
			++$tally['left_out']['email'];
			return;
		}
		$person['username'] = self::username( $person );
		if ( '' === $person['username'] || '' === $person['firstname'] || '' === $person['lastname'] ) {
			++$tally['left_out']['incomplete'];
			return;
		}
		$clave = self::lower( $person['username'] );
		if ( ! isset( $tally['people'][ $clave ] ) ) {
			$tally['people'][ $clave ] = $person;
		} elseif ( self::lower( (string) $tally['people'][ $clave ]['email'] ) !== self::lower( (string) $person['email'] ) ) {
			++$tally['left_out']['conflict'];
		}
	}

	/**
	 * Lower case, multibyte when it can.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	private static function lower( string $value ): string {
		return function_exists( 'mb_strtolower' ) ? mb_strtolower( $value ) : strtolower( $value );
	}

	/**
	 * The user upload file of Moodle.
	 *
	 * @param int                  $procedure_id Procedure post ID.
	 * @param array<string, mixed> $options      `who`: keys of {@see who()}; empty for all.
	 * @return string
	 */
	public static function moodle( int $procedure_id, array $options = array() ): string {
		$who = is_array( $options['who'] ?? null ) ? $options['who'] : array();
		return self::moodle_lines( self::people( $procedure_id, $who )['people'] );
	}

	/**
	 * People as the user upload file of Moodle. Pure: no WordPress.
	 *
	 * Exactamente la cabecera {@see MOODLE_HEADER}, separado por comas, UTF-8
	 * sin BOM, saltos LF y sin salto tras la última fila. Un valor va entre
	 * comillas solo si lo exige —una coma, unas comillas o un salto—. El
	 * nombre y los apellidos que empiecen por `=`, `+`, `-` o `@` llevan una
	 * comilla simple delante, como en el CSV: el fichero también se abre en
	 * una hoja de cálculo. El usuario y el correo salen tal cual: ya están
	 * validados, y una comilla delante los convertiría en otros.
	 *
	 * @param array<int, array<string, mixed>> $people Rows with `username`, `firstname`, `lastname`, `email`.
	 * @return string
	 */
	public static function moodle_lines( array $people ): string {
		$lineas = array( self::MOODLE_HEADER );
		foreach ( $people as $persona ) {
			$campos = array();
			foreach ( array( 'username', 'firstname', 'lastname', 'email' ) as $clave ) {
				$valor    = (string) ( $persona[ $clave ] ?? '' );
				$campos[] = self::moodle_cell( 'firstname' === $clave || 'lastname' === $clave ? self::neutralize( $valor ) : $valor );
			}
			$lineas[] = implode( ',', $campos );
		}
		return implode( "\n", $lineas );
	}

	/**
	 * One value of the user upload file, quoted only if needed.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	private static function moodle_cell( string $value ): string {
		return false === strpbrk( $value, ",\"\r\n" ) ? $value : '"' . str_replace( '"', '""', $value ) . '"';
	}

	/**
	 * A value a spreadsheet would take for a formula, with a quote in front.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	public static function neutralize( string $value ): string {
		return '' !== $value && false !== strpos( "=+-@\t\r", $value[0] ) ? "'" . $value : $value;
	}

	/**
	 * The people one source gives for one application.
	 *
	 * @param string   $source      Key of {@see who()}.
	 * @param \WP_Post $application The application.
	 * @return array<int, array<string, mixed>>
	 */
	private static function from_source( string $source, \WP_Post $application ): array {
		switch ( $source ) {
			case self::WHO_APPLICANT:
				return self::applicant( $application );
			case self::WHO_COORDINATORS:
				$out = array();
				foreach ( self::coordinators( $application ) as $persona ) {
					// Un hueco vacío del formulario no es una persona.
					if ( '' === $persona['name'] . $persona['surname'] . $persona['email'] ) {
						continue;
					}
					$persona = ApplicationMetaRegistration::with_surname( $persona );
					$out[]   = self::person( self::WHO_COORDINATORS, $application, 0, '', $persona['name'], $persona['surname'], $persona['email'] );
				}
				return $out;
		}
		return array();
	}

	/**
	 * The account that submitted the application, as a person.
	 *
	 * Su usuario es el `user_login`; su nombre, el nombre y los apellidos de
	 * la cuenta y, si falta alguno, el nombre público partido por el primer
	 * espacio, salvo que el nombre público sea el propio usuario.
	 *
	 * @param \WP_Post $application The application.
	 * @return array<int, array<string, mixed>> One person, or none.
	 */
	private static function applicant( \WP_Post $application ): array {
		$cuenta = get_userdata( (int) $application->post_author );
		if ( false === $cuenta ) {
			return array();
		}
		$nombre    = trim( (string) $cuenta->first_name );
		$apellidos = trim( (string) $cuenta->last_name );
		// El nombre público de una cuenta sin nombre suele ser el propio
		// usuario: eso no es un nombre, y la persona queda incompleta.
		$publico = (string) $cuenta->display_name;
		if ( ( '' === $nombre || '' === $apellidos ) && $publico !== (string) $cuenta->user_login ) {
			$partido   = ApplicationMetaRegistration::split_name( $publico );
			$nombre    = '' !== $nombre ? $nombre : $partido[0];
			$apellidos = '' !== $apellidos ? $apellidos : $partido[1];
		}
		return array( self::person( self::WHO_APPLICANT, $application, (int) $cuenta->ID, (string) $cuenta->user_login, $nombre, $apellidos, (string) $cuenta->user_email ) );
	}

	/**
	 * One person of the user upload file, before its username is decided.
	 *
	 * @param string   $source      Key of {@see who()}.
	 * @param \WP_Post $application Where it comes from.
	 * @param int      $user_id     Account, or 0 for someone typed in a form.
	 * @param string   $login       Account login, or empty.
	 * @param string   $firstname   First name.
	 * @param string   $lastname    Surname.
	 * @param string   $email       Email.
	 * @return array<string, mixed>
	 */
	private static function person( string $source, \WP_Post $application, int $user_id, string $login, string $firstname, string $lastname, string $email ): array {
		return array(
			'source'         => $source,
			'application_id' => (int) $application->ID,
			'user_id'        => $user_id,
			'username'       => $login,
			'firstname'      => $firstname,
			'lastname'       => $lastname,
			'email'          => trim( $email ),
		);
	}

	/**
	 * The username of one person.
	 *
	 * El usuario corporativo: el `user_login` de una cuenta y, para quien se
	 * tecleó en un formulario, la parte local de su correo en minúsculas.
	 * Cada instalación lo cambia con el filtro `prc_export_username`.
	 *
	 * @param array<string, mixed> $person The person.
	 * @return string Empty to leave the person out.
	 */
	private static function username( array $person ): string {
		$usuario = (string) $person['username'];
		if ( '' === $usuario ) {
			$arroba  = strrpos( (string) $person['email'], '@' );
			$usuario = false === $arroba ? '' : strtolower( substr( (string) $person['email'], 0, $arroba ) );
		}

		/**
		 * The username a person gets in the user upload file.
		 *
		 * @param string               $username `user_login`, or the local part of the email.
		 * @param array<string, mixed> $person   `source`, `application_id`, `user_id`, `firstname`, `lastname`, `email`.
		 */
		$usuario = apply_filters( 'prc_export_username', $usuario, $person );
		return is_string( $usuario ) ? trim( $usuario ) : '';
	}

	// ─── la elección ───────────────────────────────────────────────────────

	/**
	 * A choice of the dialog, checked against what exists.
	 *
	 * `all` gana a las casillas: es «Todas las columnas», que sin guion es
	 * la única forma de pedirlas todas sin marcar una a una. Columnas vacías
	 * significa todas.
	 *
	 * @param int                  $procedure_id Procedure post ID.
	 * @param array<string, mixed> $raw          `all`, `columns`, `shape`, `format`, `who`.
	 * @return array{columns:string[], shape:string, format:string, who:string[]}
	 */
	public static function sanitize_choice( int $procedure_id, array $raw ): array {
		$columnas = is_array( $raw['columns'] ?? null ) && empty( $raw['all'] ) ? $raw['columns'] : array();
		$quienes  = is_array( $raw['who'] ?? null ) ? array_filter( $raw['who'], 'is_string' ) : array();
		return array(
			'columns' => array_keys( self::valid_columns( $procedure_id, $columnas ) ),
			'shape'   => self::SHAPE_PERSON === ( $raw['shape'] ?? '' ) ? self::SHAPE_PERSON : self::SHAPE_APPLICATION,
			'format'  => self::FORMAT_MOODLE === ( $raw['format'] ?? '' ) ? self::FORMAT_MOODLE : self::FORMAT_CSV,
			'who'     => array_values( array_intersect( array_keys( self::who() ), $quienes ) ),
		);
	}

	/**
	 * The last choice of one person for one procedure; today's export if none.
	 *
	 * Se vuelve a validar al leer: una pregunta borrada desde entonces se cae.
	 *
	 * @param int $user_id      Who.
	 * @param int $procedure_id Procedure post ID.
	 * @return array{columns:string[], shape:string, format:string, who:string[]}
	 */
	public static function choice( int $user_id, int $procedure_id ): array {
		$guardada = get_user_meta( $user_id, self::CHOICE_META . $procedure_id, true );
		return self::sanitize_choice( $procedure_id, is_array( $guardada ) ? $guardada : array() );
	}

	/**
	 * Remember the choice of one person for one procedure.
	 *
	 * @param int                  $user_id      Who.
	 * @param int                  $procedure_id Procedure post ID.
	 * @param array<string, mixed> $choice       What {@see sanitize_choice()} returned.
	 * @return void
	 */
	public static function remember( int $user_id, int $procedure_id, array $choice ): void {
		if ( $user_id > 0 && $procedure_id > 0 ) {
			update_user_meta( $user_id, self::CHOICE_META . $procedure_id, self::sanitize_choice( $procedure_id, $choice ) );
		}
	}

	/**
	 * The file name an export gets.
	 *
	 * @param string $title  Procedure title.
	 * @param string $format {@see FORMAT_CSV} or {@see FORMAT_MOODLE}.
	 * @return string
	 */
	public static function filename( string $title, string $format ): string {
		$nombre = Applications::filename( $title );
		return self::FORMAT_MOODLE === $format ? 'usuarios-' . substr( $nombre, strlen( 'solicitudes-' ) ) : $nombre;
	}

	// ─── leer ──────────────────────────────────────────────────────────────

	/**
	 * The stored coordinators of one application.
	 *
	 * @param \WP_Post $application The application.
	 * @return array<int, array{name:string, surname:string, dni:string, email:string}>
	 */
	private static function coordinators( \WP_Post $application ): array {
		return ApplicationMetaRegistration::sanitize_coordinator( get_post_meta( (int) $application->ID, ApplicationMetaKeys::COORDINATOR, true ) );
	}

	/**
	 * The applications of one procedure, oldest first, a batch at a time.
	 *
	 * Por tandas y no todas de golpe: un procedimiento con cientos de
	 * solicitudes y decenas de preguntas no tiene por qué pasar entero por
	 * memoria para escribir un fichero.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return \Generator<int, \WP_Post[]>
	 */
	private static function batches( int $procedure_id ): \Generator {
		if ( $procedure_id <= 0 ) {
			return;
		}
		for ( $pagina = 1; ; ++$pagina ) {
			$tanda = get_posts(
				array(
					'post_type'        => ApplicationPostType::POST_TYPE,
					'post_parent'      => $procedure_id,
					'post_status'      => 'publish',
					'posts_per_page'   => self::BATCH,
					'paged'            => $pagina,
					'no_found_rows'    => true,
					// El ID desempata: sin él, dos del mismo segundo podrían
					// salir en dos tandas o en ninguna.
					'orderby'          => array(
						'date' => 'ASC',
						'ID'   => 'ASC',
					),
					'suppress_filters' => false,
				)
			);
			if ( ! is_array( $tanda ) || array() === $tanda ) {
				return;
			}
			// Quién presentó cada una: todas las cuentas de la tanda en una consulta.
			cache_users( array_unique( array_map( 'intval', wp_list_pluck( $tanda, 'post_author' ) ) ) );
			yield $tanda;
			if ( count( $tanda ) < self::BATCH ) {
				return;
			}
		}
	}
}
