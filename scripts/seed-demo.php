<?php
/**
 * Demo users, demo procedures and demo applications for the development environment.
 *
 * Idempotente: crea lo que falta y repone siempre el rol, el ámbito o el
 * centro de cada cuenta, y los datos y la clasificación de cada procedimiento
 * y cada solicitud. Corre bajo `wp eval-file` y bajo Playground `runPHP`, así
 * que nunca depende de WP_CLI.
 *
 * Esta es la fuente de verdad de la tabla de usuarios de prueba del README.
 *
 * Usage:
 *   npx wp-env run cli wp eval-file wp-content/prc-dev/scripts/seed-demo.php
 *
 * @package Prc
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'prc_demo_accounts' ) ) {
	/**
	 * Demo accounts: login => role, ámbito slugs or school code, and label.
	 *
	 * `gestion` tiene en su ficha un **servicio**, y por eso llega a las tres
	 * áreas que cuelgan de él; `gestion2` tiene un área de otro servicio, que
	 * es lo que enseña el acotado por ámbito. `direccion` y `direccion2` son
	 * dos centros públicos del catálogo inventado del mu-plugin de desarrollo:
	 * cada uno ve solo las solicitudes del suyo. `consulta` consulta en solo
	 * lectura las solicitudes de los centros de una zona inventada (ADR-0043).
	 *
	 * El que trabaja sobre todos los ámbitos es `admin`, el administrador que
	 * ya crea wp-env: la administración es la nativa de WordPress, así que no
	 * hay ninguna cuenta más que sembrar.
	 *
	 * @return array<string, array{role:string, areas:string[], centre:string, zones?:string[], label:string}>
	 */
	function prc_demo_accounts(): array {
		return array(
			'gestion'        => array(
				'role'   => 'prc_manager',
				'areas'  => array( 'ambito-1' ),
				'centre' => '',
				'label'  => 'Gestión (Ámbito 1)',
			),
			'gestion2'       => array(
				'role'   => 'prc_manager',
				'areas'  => array( 'subambito-2a' ),
				'centre' => '',
				'label'  => 'Gestión 2 (Subámbito 2A)',
			),
			'editor-ambito1' => array(
				'role'   => 'editor',
				'areas'  => array( 'ambito-1' ),
				'centre' => '',
				'label'  => 'Editor (Ámbito 1)',
			),
			'editor-ambito2' => array(
				'role'   => 'editor',
				'areas'  => array( 'ambito-2' ),
				'centre' => '',
				'label'  => 'Editor (Ámbito 2)',
			),
			'direccion'      => array(
				'role'   => 'prc_school_head',
				'areas'  => array(),
				'centre' => '90000001',
				'label'  => 'Dirección (CEIP Ejemplo Uno)',
			),
			'direccion2'     => array(
				'role'   => 'prc_school_head',
				'areas'  => array(),
				'centre' => '90000002',
				'label'  => 'Dirección 2 (IES Ejemplo Dos)',
			),
			'profesor'       => array(
				'role'   => 'prc_teacher',
				'areas'  => array(),
				'centre' => '',
				'label'  => 'Docente de ejemplo',
			),
			'consulta'       => array(
				'role'   => 'prc_observer',
				'areas'  => array(),
				'centre' => '',
				'zones'  => array( '90000090' ),
				'label'  => 'Consulta (Zona Norte)',
			),
		);
	}
}

if ( ! function_exists( 'prc_demo_course' ) ) {
	/**
	 * School course slug of a date, as `setup-vocabulary.php` names them.
	 *
	 * @param string $date Date in Y-m-d.
	 * @return string
	 */
	function prc_demo_course( string $date ): string {
		$year = (int) substr( $date, 0, 4 );
		// El curso escolar arranca en septiembre: enero de 2026 sigue siendo 2025-2026.
		if ( (int) substr( $date, 5, 2 ) < 9 ) {
			--$year;
		}
		return $year . '-' . ( $year + 1 );
	}
}

if ( ! function_exists( 'prc_demo_day' ) ) {
	/**
	 * A day relative to today, in the site timezone.
	 *
	 * @param int $days Offset in days (negative for the past).
	 * @return string Y-m-d.
	 */
	function prc_demo_day( int $days ): string {
		return gmdate( 'Y-m-d', strtotime( current_time( 'Y-m-d' ) . ' ' . $days . ' days' ) );
	}
}

if ( ! function_exists( 'prc_demo_questions' ) ) {
	/**
	 * The demo questions: one of each of the four types (ADR-0019).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	function prc_demo_questions(): array {
		return array(
			array(
				'key'      => 'q1',
				'label'    => 'Modalidad',
				'help'     => 'Elija la modalidad en la que participa el centro.',
				'type'     => 'single',
				'required' => true,
				'choices'  => array( 'Modalidad A', 'Modalidad B' ),
			),
			array(
				'key'      => 'q2',
				'label'    => 'Etapas educativas participantes',
				'help'     => '',
				'type'     => 'multiple',
				'required' => true,
				'choices'  => array( 'Infantil', 'Primaria', 'Secundaria', 'Bachillerato' ),
			),
			array(
				'key'      => 'q3',
				'label'    => 'El centro ha participado en ediciones anteriores',
				'help'     => '',
				'type'     => 'yesno',
				'required' => false,
				'choices'  => array(),
			),
			array(
				'key'      => 'q4',
				'label'    => 'Breve descripción del proyecto',
				'help'     => 'Hasta 2.000 caracteres.',
				'type'     => 'text',
				'required' => true,
				'choices'  => array(),
			),
		);
	}
}

if ( ! function_exists( 'prc_demo_procedures' ) ) {
	/**
	 * Demo procedures: one per derived state, with the applications hanging from each.
	 *
	 * Las fechas son relativas al día de hoy y no literales: con fechas fijas
	 * todos acabarían «cerrados» a los pocos meses y las pantallas se verían sin
	 * un solo procedimiento abierto, que es justo lo que hay que enseñar.
	 *
	 * Las solicitudes cubren los cuatro estados de revisión (ADR-0020):
	 * presentada, a subsanar, admitida y excluida.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	function prc_demo_procedures(): array {
		$answers = array(
			'q1' => 'Modalidad A',
			'q2' => array( 'Primaria' ),
			'q3' => true,
			'q4' => 'Un proyecto de centro de demostración, escrito por scripts/seed-demo.php.',
		);

		return array(
			array(
				'slug'         => 'red-de-escuelas-por-la-ciencia',
				'title'        => 'Red de Escuelas por la Ciencia',
				'author'       => 'gestion',
				'areas'        => array( 'subambito-1a' ),
				'status'       => 'publish',
				'meta'         => array(
					'prc_opens_at'             => prc_demo_day( -5 ),
					'prc_closes_at'            => prc_demo_day( 10 ),
					'prc_amend_opens_at'       => prc_demo_day( 15 ),
					'prc_amend_closes_at'      => prc_demo_day( 20 ),
					'prc_contact_emails'       => array( 'ciencia@example.org', 'redes@example.org' ),
					'prc_header_color'         => 'turquesa',
					'prc_resolution_url'       => 'https://www.example.org/ciencia/resolucion.pdf',
					'prc_ownership'            => array( 'public', 'private' ),
					'prc_requires_coordinator' => true,
					'prc_questions'            => prc_demo_questions(),
					'prc_commitments'          => 'El centro se compromete a desarrollar el proyecto durante el curso y a presentar una memoria final.',
				),
				'applications' => array(
					'direccion' => array(
						'state'   => 'submitted',
						'answers' => $answers,
					),
				),
			),
			array(
				'slug'         => 'plan-lector-de-centro',
				'title'        => 'Plan Lector de Centro',
				'author'       => 'gestion',
				// Dos ámbitos convocantes, de dos servicios distintos (ADR-0025):
				// `gestion` llega por «lectura» y `gestion2` por «salud», y los
				// dos lo gestionan sin que nadie les añada nada al perfil.
				'areas'        => array( 'subambito-1b', 'subambito-2a' ),
				'status'       => 'publish',
				'meta'         => array(
					'prc_opens_at'             => prc_demo_day( -30 ),
					'prc_closes_at'            => prc_demo_day( -15 ),
					'prc_amend_opens_at'       => prc_demo_day( -2 ),
					'prc_amend_closes_at'      => prc_demo_day( 5 ),
					'prc_contact_emails'       => array( 'lectura@example.org' ),
					'prc_header_color'         => 'morado',
					'prc_ownership'            => array( 'public', 'private' ),
					'prc_requires_coordinator' => true,
					'prc_questions'            => prc_demo_questions(),
					'prc_commitments'          => 'El centro se compromete a incluir el plan en su programación general anual.',
				),
				'applications' => array(
					'direccion'  => array(
						'state'   => 'amend',
						'note'    => 'Falta indicar la etapa educativa en la que se desarrolla el plan.',
						'answers' => $answers,
					),
					'direccion2' => array(
						'state'   => 'excluded',
						'note'    => 'El centro ya participa en el programa por otra vía.',
						'answers' => $answers,
					),
				),
			),
			array(
				'slug'         => 'programa-de-convivencia-positiva',
				'title'        => 'Programa de Convivencia Positiva',
				'author'       => 'gestion',
				'areas'        => array( 'subambito-1c' ),
				'status'       => 'publish',
				'meta'         => array(
					'prc_opens_at'             => prc_demo_day( -40 ),
					'prc_closes_at'            => prc_demo_day( -20 ),
					'prc_contact_emails'       => array( 'convivencia@example.org' ),
					'prc_header_color'         => 'verde',
					'prc_provisional_list_url' => 'https://www.example.org/convivencia/listado-provisional.pdf',
					'prc_ownership'            => array( 'public' ),
					'prc_requires_coordinator' => false,
					// Se consulta por zona en solo lectura, y se exporta (ADR-0043).
					'prc_observable'           => true,
					'prc_observer_columns'     => array( 'date', 'centre', 'code', 'zone', 'state' ),
					'prc_observer_export'      => true,
				),
				'applications' => array(
					'direccion'  => array( 'state' => 'admitted' ),
					'direccion2' => array( 'state' => 'submitted' ),
				),
			),
			array(
				'slug'         => 'escuelas-promotoras-de-salud',
				'title'        => 'Escuelas Promotoras de Salud',
				'author'       => 'gestion2',
				'areas'        => array( 'subambito-2a' ),
				'status'       => 'publish',
				'meta'         => array(
					'prc_opens_at'             => prc_demo_day( -90 ),
					'prc_closes_at'            => prc_demo_day( -70 ),
					'prc_contact_emails'       => array( 'salud@example.org' ),
					'prc_header_color'         => 'granate',
					'prc_resolution_url'       => 'https://www.example.org/salud/resolucion.pdf',
					'prc_provisional_list_url' => 'https://www.example.org/salud/listado-provisional.pdf',
					'prc_final_list_url'       => 'https://www.example.org/salud/listado-definitivo.pdf',
					'prc_ownership'            => array( 'public', 'private' ),
					'prc_requires_coordinator' => true,
					'prc_questions'            => array(
						array(
							'key'       => 'q1',
							'label'     => 'Memoria final',
							'help'      => 'Adjunte la memoria del proyecto en un único documento.',
							'type'      => 'file',
							'required'  => true,
							'delivery'  => true,
							'opens_at'  => prc_demo_day( -2 ),
							'closes_at' => prc_demo_day( 30 ),
						),
					),
				),
				'applications' => array(
					'direccion' => array( 'state' => 'admitted' ),
				),
			),
			array(
				'slug'         => 'concurso-de-habitos-saludables',
				'title'        => 'Concurso de Hábitos Saludables',
				'author'       => 'gestion2',
				'areas'        => array( 'subambito-2a' ),
				'status'       => 'publish',
				'meta'         => array(
					'prc_opens_at'       => prc_demo_day( 20 ),
					'prc_closes_at'      => prc_demo_day( 40 ),
					'prc_contact_emails' => array( 'salud@example.org' ),
					'prc_header_color'   => 'ocre',
					'prc_ownership'      => array( 'public', 'private' ),
				),
				'applications' => array(),
			),
			array(
				'slug'         => 'red-de-huertos-escolares',
				'title'        => 'Red de Huertos Escolares',
				'author'       => 'gestion',
				'areas'        => array( 'subambito-1a' ),
				'status'       => 'publish',
				'meta'         => array(
					'prc_opens_at'       => prc_demo_day( -400 ),
					'prc_closes_at'      => prc_demo_day( -380 ),
					'prc_contact_emails' => array( 'ciencia@example.org' ),
					'prc_header_color'   => 'oliva',
					'prc_final_list_url' => 'https://www.example.org/huertos/listado-definitivo.pdf',
					'prc_ownership'      => array( 'public' ),
					'prc_archived'       => true,
				),
				'applications' => array(),
			),
			array(
				'slug'         => 'jornadas-de-ciencia-en-familia',
				'title'        => 'Jornadas de Ciencia en Familia',
				'author'       => 'gestion',
				'areas'        => array( 'subambito-1a' ),
				'status'       => 'draft',
				'meta'         => array(
					'prc_opens_at'  => prc_demo_day( 30 ),
					'prc_closes_at' => prc_demo_day( 45 ),
					'prc_ownership' => array( 'public', 'private' ),
				),
				'applications' => array(),
			),
			array(
				'slug'                 => 'formacion-docente-de-ejemplo',
				'title'                => 'Formación docente de ejemplo',
				'author'               => 'gestion',
				'areas'                => array( 'subambito-1a' ),
				'status'               => 'publish',
				'meta'                 => array(
					'prc_audience'       => 'teachers',
					'prc_opens_at'       => prc_demo_day( -2 ),
					'prc_closes_at'      => prc_demo_day( 10 ),
					'prc_contact_emails' => array( 'formacion@example.org' ),
					'prc_ownership'      => array(),
					'prc_questions'      => array(
						array(
							'key'      => 'q1',
							'label'    => '¿Qué espera aprender en esta formación?',
							'help'     => 'Cuente brevemente qué le interesa.',
							'type'     => 'text',
							'required' => true,
						),
					),
					'prc_commitments'    => 'La persona participante se compromete a completar la formación.',
				),
				'applications'         => array(),
				'teacher_applications' => array(
					'profesor' => array(
						'state'   => 'submitted',
						'answers' => array( 'q1' => 'Quiero aprender actividades sencillas para trabajar la ciencia en el aula.' ),
					),
				),
			),
		);
	}
}

if ( ! function_exists( 'prc_seed_account' ) ) {
	/**
	 * Create or update one demo account, its ámbitos and its school code.
	 *
	 * @param string                                                                           $login   Login name.
	 * @param array{role:string, areas:string[], centre:string, zones?:string[], label:string} $account Account definition.
	 * @return int User ID.
	 * @throws RuntimeException If the user cannot be created or an ámbito is missing.
	 */
	function prc_seed_account( string $login, array $account ): int {
		$user = get_user_by( 'login', $login );

		if ( ! $user instanceof WP_User ) {
			$user_id = wp_insert_user(
				array(
					'user_login'   => $login,
					'user_pass'    => 'password',
					'user_email'   => $login . '@example.org',
					'display_name' => $account['label'],
					'role'         => $account['role'],
				)
			);
			if ( is_wp_error( $user_id ) ) {
				throw new RuntimeException( esc_html( "No se pudo crear el usuario «{$login}»: " . $user_id->get_error_message() ) );
			}
			$user = get_user_by( 'id', (int) $user_id );
			echo esc_html( "Creado el usuario «{$login}» ({$account['role']})." ) . "\n";
		}

		if ( ! $user instanceof WP_User ) {
			throw new RuntimeException( esc_html( "No se pudo leer el usuario «{$login}» después de crearlo." ) );
		}

		// El rol y el nombre se reponen siempre: si alguien los cambió probando —o si
		// cambiaron aquí—, la siguiente provisión devuelve el entorno a lo que dice
		// el README.
		$user->set_role( $account['role'] );

		if ( $account['label'] !== $user->display_name ) {
			wp_update_user(
				array(
					'ID'           => $user->ID,
					'display_name' => $account['label'],
				)
			);
		}

		$areas = array();
		foreach ( $account['areas'] as $slug ) {
			$areas[] = prc_demo_term( 'prc_area', $slug );
		}
		update_user_meta( $user->ID, 'prc_area', $areas );

		// El código de centro va en la meta cuya clave da el filtro (ADR-0016).
		$key = \Prc\Access\CentreScope::meta_key();
		if ( '' !== $account['centre'] ) {
			update_user_meta( $user->ID, $key, $account['centre'] );
		} else {
			delete_user_meta( $user->ID, $key );
		}

		// Y las zonas de consulta, en la suya (ADR-0043).
		$zone_key = \Prc\Access\ObserverScope::meta_key();
		if ( array() !== ( $account['zones'] ?? array() ) ) {
			update_user_meta( $user->ID, $zone_key, $account['zones'] );
		} else {
			delete_user_meta( $user->ID, $zone_key );
		}

		return (int) $user->ID;
	}
}

if ( ! function_exists( 'prc_demo_term' ) ) {
	/**
	 * Term ID of one taxonomy term, by slug.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @param string $slug     Term slug.
	 * @return int
	 * @throws RuntimeException If the term does not exist.
	 */
	function prc_demo_term( string $taxonomy, string $slug ): int {
		$term = get_term_by( 'slug', $slug, $taxonomy );
		if ( ! $term instanceof WP_Term ) {
			throw new RuntimeException( esc_html( "No existe «{$slug}» en {$taxonomy}: ejecute antes scripts/setup-vocabulary.php." ) );
		}
		return (int) $term->term_id;
	}
}

if ( ! function_exists( 'prc_seed_procedure' ) ) {
	/**
	 * Create or complete one demo procedure with its applications.
	 *
	 * Se vuelve a pasar por encima del procedimiento que ya existía en vez de
	 * saltárselo: lo que hay aquí son datos de demostración, y las fechas son
	 * relativas a hoy, así que un entorno provisionado hace meses tiene que
	 * volver a enseñar cada estado.
	 *
	 * @param array<string, mixed> $procedure Procedure definition.
	 * @return bool Whether the procedure was created.
	 * @throws RuntimeException If a post cannot be created.
	 */
	function prc_seed_procedure( array $procedure ): bool {
		$author = get_user_by( 'login', $procedure['author'] );
		$author = $author instanceof WP_User ? (int) $author->ID : 0;

		$existente = get_page_by_path( $procedure['slug'], OBJECT, 'prc_procedure' );
		$nuevo     = ! $existente instanceof WP_Post;

		$campos = array(
			'post_type'    => 'prc_procedure',
			'post_status'  => $procedure['status'],
			'post_name'    => $procedure['slug'],
			'post_title'   => $procedure['title'],
			'post_author'  => $author,
			// Tres bloques y no uno: la ficha parte el contenido por sus `<h2>`
			// y hace una pestaña de cada trozo, así que con un solo párrafo la
			// barra de pestañas no se llega a ver en el entorno de prueba.
			// El extracto es el texto de la resolución, que la ficha pinta en
			// mayúsculas encima del enlace al documento.
			'post_excerpt' => 'Resolución por la que se aprueban las instrucciones que regulan este procedimiento y se abre el plazo de solicitud para los centros educativos.',
			'post_content' => "<p>Procedimiento de demostración creado por scripts/seed-demo.php: de qué va, a quién se dirige y cuándo se hace.</p>\n"
				. "<h2>Dirigido a:</h2>\n<p>Centros educativos que quieran participar durante el curso, con el visto bueno de su equipo directivo.</p>\n"
				. "<h2>Calendario:</h2>\n<p><strong>Presentación</strong>: al abrir el plazo de solicitud.<br /><strong>Resolución</strong>: durante el mes siguiente al cierre.</p>",
		);

		if ( $nuevo ) {
			$root = wp_insert_post( $campos, true );
		} else {
			$campos['ID'] = (int) $existente->ID;
			$root         = wp_update_post( $campos, true );
		}
		if ( is_wp_error( $root ) ) {
			throw new RuntimeException( esc_html( "No se pudo guardar el procedimiento «{$procedure['slug']}»: " . $root->get_error_message() ) );
		}
		$root = (int) $root;

		// Todas las metas se reponen: las que la definición no lleva se vacían,
		// para que un procedimiento que dejó de estar «resuelto» en esta lista
		// no se quede resuelto para siempre en el entorno.
		foreach ( \Prc\Meta\ProcedureMetaKeys::all() as $clave ) {
			if ( isset( $procedure['meta'][ $clave ] ) ) {
				update_post_meta( $root, $clave, $procedure['meta'][ $clave ] );
			} else {
				delete_post_meta( $root, $clave );
			}
		}

		// Los términos van por ID: `wp_set_object_terms()` con una cadena en una
		// taxonomía jerárquica crearía un término nuevo si el slug no existiese, y
		// lo que hace falta es enterarse de que falta el vocabulario.
		$ambitos = array();
		foreach ( (array) $procedure['areas'] as $slug ) {
			$ambitos[] = prc_demo_term( 'prc_area', $slug );
		}
		wp_set_object_terms( $root, $ambitos, 'prc_area' );
		wp_set_object_terms( $root, array( prc_demo_term( 'prc_course', prc_demo_course( (string) $procedure['meta']['prc_opens_at'] ) ) ), 'prc_course' );

		$creadas = 0;
		foreach ( $procedure['applications'] as $login => $application ) {
			$creadas += prc_seed_application( $root, $login, $application ) ? 1 : 0;
		}
		foreach ( (array) ( $procedure['teacher_applications'] ?? array() ) as $login => $application ) {
			$creadas += prc_seed_teacher_application( $root, $login, $application ) ? 1 : 0;
		}

		echo esc_html(
			sprintf(
				'%1$s «%2$s» (ID %3$d, %4$s): %5$d solicitud(es) nueva(s) de %6$d.',
				$nuevo ? 'Creado el procedimiento' : 'Actualizado el procedimiento',
				$procedure['slug'],
				$root,
				\Prc\Domain\ProcedureState::of_post( $root ),
				$creadas,
				count( $procedure['applications'] )
			)
		) . "\n";

		return $nuevo;
	}
}

if ( ! function_exists( 'prc_seed_teacher_application' ) ) {
	/**
	 * Create or refresh one demo teacher's individual application.
	 *
	 * @param int                  $root        Procedure post ID.
	 * @param string               $login       Demo teacher login.
	 * @param array<string, mixed> $application Application review data.
	 * @return bool Whether a new application was created.
	 * @throws RuntimeException If the account or post cannot be read or written.
	 */
	function prc_seed_teacher_application( int $root, string $login, array $application ): bool {
		$user = get_user_by( 'login', $login );
		if ( ! $user instanceof WP_User ) {
			throw new RuntimeException( esc_html( "No existe la cuenta «{$login}» para solicitar." ) );
		}
		$existing = get_posts(
			array(
				'post_type'   => 'prc_application',
				'post_parent' => $root,
				'post_status' => 'any',
				'numberposts' => 1,
				'author'      => (int) $user->ID,
				'meta_key'    => 'prc_applicant_type', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- identifica solicitudes individuales del perfil.
				'meta_value'  => 'teacher', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		$new      = array() === $existing;
		$post     = array(
			'post_type'   => 'prc_application',
			'post_status' => 'publish',
			'post_parent' => $root,
			'post_author' => (int) $user->ID,
			'post_title'  => 'Solicitud individual — ' . get_the_title( $root ),
		);
		if ( ! $new ) {
			$post['ID'] = (int) $existing[0]->ID;
		}
		$id = $new ? wp_insert_post( $post, true ) : wp_update_post( $post, true );
		if ( is_wp_error( $id ) ) {
			throw new RuntimeException( esc_html( "No se pudo guardar la solicitud de «{$login}»: " . $id->get_error_message() ) );
		}
		foreach (
			array(
				'prc_applicant_type'     => 'teacher',
				'prc_centre_code'        => '',
				'prc_centre_name'        => '',
				'prc_applicant_position' => '',
				'prc_coordinator'        => array(),
				'prc_answers'            => (array) ( $application['answers'] ?? array() ),
				'prc_review_state'       => (string) $application['state'],
				'prc_review_note'        => (string) ( $application['note'] ?? '' ),
			) as $key => $value
		) {
			update_post_meta( (int) $id, $key, $value );
		}
		return $new;
	}
}

if ( ! function_exists( 'prc_seed_application' ) ) {
	/**
	 * Create or complete the application of one demo school to one procedure.
	 *
	 * Una por centro y procedimiento (ADR-0018): se busca por el código de
	 * centro bajo el procedimiento y, si está, se repone en vez de duplicarla.
	 *
	 * @param int                  $root        Procedure post ID.
	 * @param string               $login       Login of the school head who applies.
	 * @param array<string, mixed> $application Review state, note and answers.
	 * @return bool Whether the application was created.
	 * @throws RuntimeException If the account is missing or the post cannot be written.
	 */
	function prc_seed_application( int $root, string $login, array $application ): bool {
		$user = get_user_by( 'login', $login );
		if ( ! $user instanceof WP_User ) {
			throw new RuntimeException( esc_html( "No existe la cuenta «{$login}» para solicitar." ) );
		}
		$code = \Prc\Access\CentreScope::code_for( (int) $user->ID );
		if ( '' === $code ) {
			throw new RuntimeException( esc_html( "La cuenta «{$login}» no tiene código de centro." ) );
		}
		$centro = \Prc\Domain\CentreCatalog::find( $code );
		$nombre = '' !== ( $centro['name'] ?? '' ) ? $centro['name'] : $code;

		$existentes = get_posts(
			array(
				'post_type'   => 'prc_application',
				'post_parent' => $root,
				'post_status' => 'any',
				'numberposts' => 1,
				'meta_key'    => 'prc_centre_code', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- es la clave de unicidad de la solicitud (ADR-0018).
				'meta_value'  => $code, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		$nuevo      = array() === $existentes;

		$campos = array(
			'post_type'   => 'prc_application',
			'post_status' => 'publish',
			'post_parent' => $root,
			'post_author' => (int) $user->ID,
			'post_title'  => $nombre . ' — ' . get_the_title( $root ),
		);
		if ( ! $nuevo ) {
			$campos['ID'] = (int) $existentes[0]->ID;
		}
		$id = $nuevo ? wp_insert_post( $campos, true ) : wp_update_post( $campos, true );
		if ( is_wp_error( $id ) ) {
			throw new RuntimeException( esc_html( "No se pudo guardar la solicitud de «{$login}»: " . $id->get_error_message() ) );
		}
		$id = (int) $id;

		$meta = array(
			'prc_centre_code'        => $code,
			'prc_centre_name'        => (string) ( $centro['name'] ?? '' ),
			'prc_applicant_position' => 'head',
			'prc_coordinator'        => array(
				'name'  => 'Persona Coordinadora de Ejemplo',
				'email' => 'coordinacion.' . $login . '@example.org',
			),
			'prc_answers'            => (array) ( $application['answers'] ?? array() ),
			'prc_review_state'       => (string) $application['state'],
			'prc_review_note'        => (string) ( $application['note'] ?? '' ),
		);
		foreach ( $meta as $clave => $valor ) {
			update_post_meta( $id, $clave, $valor );
		}

		return $nuevo;
	}
}

if ( ! function_exists( 'prc_seed_demo' ) ) {
	/**
	 * Set example contact details on the demo scopes.
	 *
	 * @return void
	 * @throws RuntimeException If a scope or the demo image cannot be saved.
	 */
	function prc_seed_scope_details(): void {
		$image    = get_posts(
			array(
				'post_type'   => 'attachment',
				'post_status' => 'inherit',
				'numberposts' => 1,
				'fields'      => 'ids',
				'meta_key'    => '_prc_demo_scope_image', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- identifica la imagen de demostración para no duplicarla.
				'meta_value'  => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		$image_id = (int) ( $image[0] ?? 0 );
		if ( ! $image_id ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			$path = __DIR__ . '/assets/demo-scope.png';
			$data = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- es un recurso local del repositorio, no una URL.
			if ( false === $data ) {
				throw new RuntimeException( 'No se pudo leer scripts/assets/demo-scope.png.' );
			}
			$bits = wp_upload_bits( basename( $path ), null, $data );
			if ( ! empty( $bits['error'] ) ) {
				throw new RuntimeException( esc_html( 'No se pudo cargar la imagen de demostración: ' . $bits['error'] ) );
			}
			$image_id = wp_insert_attachment(
				array(
					'post_mime_type' => $bits['type'],
					'post_title'     => 'Imagen de ámbito de demostración',
					'post_status'    => 'inherit',
				),
				$bits['file'],
				0,
				true
			);
			if ( is_wp_error( $image_id ) ) {
				throw new RuntimeException( esc_html( 'No se pudo registrar la imagen de demostración: ' . $image_id->get_error_message() ) );
			}
			wp_update_attachment_metadata( $image_id, wp_generate_attachment_metadata( $image_id, $bits['file'] ) );
			update_post_meta( $image_id, '_wp_attachment_image_alt', 'Ilustración de un centro educativo' );
			update_post_meta( $image_id, '_prc_demo_scope_image', '1' );
		}

		foreach ( array( 'ambito-1', 'subambito-1a', 'subambito-1b', 'subambito-1c', 'ambito-2', 'subambito-2a', 'subambito-2b' ) as $slug ) {
			$term = get_term_by( 'slug', $slug, 'prc_area' );
			if ( ! $term instanceof WP_Term ) {
				throw new RuntimeException( esc_html( "No existe el ámbito de demostración «{$slug}». Ejecute antes scripts/setup-vocabulary.php." ) );
			}
			update_term_meta( $term->term_id, \Prc\Taxonomy\ProcedureTaxonomies::EMAIL, sanitize_email( $slug . '@example.org' ) );
			update_term_meta( $term->term_id, \Prc\Taxonomy\ProcedureTaxonomies::IMAGE_ID, $image_id );
		}
	}

	/**
	 * Seed the whole demo dataset.
	 *
	 * @return void
	 * @throws RuntimeException If the application is not loaded.
	 */
	function prc_seed_demo(): void {
		if ( ! post_type_exists( 'prc_procedure' ) || ! taxonomy_exists( 'prc_area' ) || ! class_exists( '\\Prc\\Access\\CentreScope' ) ) {
			throw new RuntimeException( 'El aplicativo no está cargado (falta prc_procedure o prc_area). Ejecute antes `make bundle && make sync-snippets`.' );
		}
		if ( ! get_role( 'prc_manager' ) || ! get_role( 'prc_school_head' ) || ! get_role( 'prc_observer' ) ) {
			throw new RuntimeException( 'Faltan los roles del aplicativo: ejecute antes scripts/provision-roles.php.' );
		}
		prc_seed_scope_details();

		foreach ( prc_demo_accounts() as $login => $account ) {
			prc_seed_account( $login, $account );
		}

		$creados = 0;
		foreach ( prc_demo_procedures() as $procedure ) {
			$creados += prc_seed_procedure( $procedure ) ? 1 : 0;
		}

		echo esc_html(
			sprintf(
				'Datos de demostración: %1$d cuenta(s) al día, %2$d procedimiento(s) creado(s). Contraseña: password.',
				count( prc_demo_accounts() ),
				$creados
			)
		) . "\n";
	}
}

// Igual que setup-vocabulary.php: en Code Snippets esto correría antes de `init` y
// no habría ni tipos ni taxonomías; con `wp eval-file` init ya pasó.
if ( did_action( 'init' ) ) {
	prc_seed_demo();
} else {
	add_action( 'init', 'prc_seed_demo', 20 );
}
