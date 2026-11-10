<?php
/**
 * Applications of schools: store, read, review and export.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\Access\CentreScope;
use Prc\Access\ProcedureAccess;
use Prc\Domain\CentreCatalog;
use Prc\Domain\ProcedureQuestions;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ApplicationMetaRegistration;
use Prc\Meta\ProcedureMetaRegistration;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ApplicationPostType;
use Prc\PostType\ProcedurePostType;

/**
 * Las solicitudes de los centros: guardarlas, leerlas, revisarlas y exportarlas.
 *
 * Una solicitud cuelga de su procedimiento por `post_parent` y hay **una por
 * centro y procedimiento** (ADR-0018): antes de crear se pregunta por
 * {@see find()} y, si existe, se edita esa. Hoy esa unicidad es una clave
 * compuesta «id de convocatoria · código de centro» escrita en un campo de
 * texto del gestor de formularios.
 *
 * Aquí no se pinta nada y no se lee la petición: eso es de las pantallas.
 * El centro nunca viene del formulario: lo pone la persona (ADR-0016) y el
 * nombre es una foto del catálogo en el momento de solicitar (ADR-0017).
 */
final class Applications {

	/**
	 * Key a row carries with its private documents, for the screen only.
	 *
	 * **No es una columna.** Es lo que la pantalla necesita para pintar un
	 * enlace de descarga, y por eso no sale en el CSV: ahí va el **nombre** del
	 * documento, en la columna de su pregunta, y nunca una ruta ni una
	 * dirección (ADR-0028). {@see csv_lines()} recorre las columnas, así que
	 * esta clave no llega nunca al fichero. Dentro hay `{application, id,
	 * name}` por documento, y de ahí sale la URL del manejador del aplicativo,
	 * que compone {@see ApplicationFiles::url()} al pintar.
	 */
	public const KEY_FILES = '_files';

	// ─── leer ──────────────────────────────────────────────────────────────

	/**
	 * The application of one school to one procedure, if any.
	 *
	 * @param int    $procedure_id Procedure post ID.
	 * @param string $centre_code  School code.
	 * @return int|null Application post ID; null when the school has none.
	 */
	public static function find( int $procedure_id, string $centre_code ): ?int {
		$centre_code = trim( $centre_code );
		if ( $procedure_id <= 0 || '' === $centre_code ) {
			return null;
		}
		$ids = get_posts(
			array(
				'post_type'        => ApplicationPostType::POST_TYPE,
				'post_parent'      => $procedure_id,
				'post_status'      => 'publish',
				'numberposts'      => 1,
				// Si por lo que sea hubiera dos, manda la más antigua: es la que
				// ya existía, y sin esto quién gana lo decide la base de datos.
				'orderby'          => 'date ID',
				'order'            => 'ASC',
				'fields'           => 'ids',
				'meta_key'         => ApplicationMetaKeys::CENTRE_CODE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- la unicidad por centro es el requisito.
				'meta_value'       => $centre_code, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'suppress_filters' => false,
			)
		);
		return is_array( $ids ) && array() !== $ids ? (int) $ids[0] : null;
	}

	/**
	 * Find one teacher's application to a procedure.
	 *
	 * @param int $procedure_id Procedure ID.
	 * @param int $user_id      Teacher account ID.
	 * @return int|null
	 */
	public static function find_for_teacher( int $procedure_id, int $user_id ): ?int {
		if ( $procedure_id <= 0 || $user_id <= 0 ) {
			return null;
		}
		$ids = get_posts(
			array(
				'post_type'   => ApplicationPostType::POST_TYPE,
				'post_parent' => $procedure_id,
				'post_status' => 'publish',
				'author'      => $user_id,
				'numberposts' => 1,
				'fields'      => 'ids',
				'meta_key'    => ApplicationMetaKeys::APPLICANT_TYPE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- la solicitud individual se identifica por su autor y tipo.
				'meta_value'  => ApplicationMetaKeys::TYPE_TEACHER, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		return is_array( $ids ) && array() !== $ids ? (int) $ids[0] : null;
	}

	/**
	 * The individual applications made by one teacher.
	 *
	 * @param int $user_id Teacher account ID.
	 * @return array<int, \WP_Post>
	 */
	public static function for_teacher( int $user_id ): array {
		if ( $user_id <= 0 ) {
			return array();
		}
		$posts = get_posts(
			array(
				'post_type'   => ApplicationPostType::POST_TYPE,
				'post_status' => 'publish',
				'author'      => $user_id,
				'numberposts' => -1,
				'orderby'     => 'date',
				'order'       => 'DESC',
				'meta_key'    => ApplicationMetaKeys::APPLICANT_TYPE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- filtra las solicitudes individuales del perfil.
				'meta_value'  => ApplicationMetaKeys::TYPE_TEACHER, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);
		return is_array( $posts ) ? $posts : array();
	}

	/**
	 * Every application to one procedure, oldest first.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return \WP_Post[]
	 */
	public static function for_procedure( int $procedure_id ): array {
		if ( $procedure_id <= 0 ) {
			return array();
		}
		$posts = get_posts(
			array(
				'post_type'        => ApplicationPostType::POST_TYPE,
				'post_parent'      => $procedure_id,
				'post_status'      => 'publish',
				'numberposts'      => -1,
				'orderby'          => 'date',
				'order'            => 'ASC',
				'suppress_filters' => false,
			)
		);
		return is_array( $posts ) ? $posts : array();
	}

	/**
	 * Every application of one school, or of several, newest first.
	 *
	 * Varios: los de quien dirige un centro y coordina una agrupación de
	 * escuelas rurales (ADR-0031).
	 *
	 * @param string|string[] $centre_code School code, or codes.
	 * @return \WP_Post[]
	 */
	public static function for_centre( $centre_code ): array {
		$centre_code = array_values( array_filter( array_map( 'trim', array_map( 'strval', (array) $centre_code ) ) ) );
		if ( array() === $centre_code ) {
			return array();
		}
		$posts = get_posts(
			array(
				'post_type'        => ApplicationPostType::POST_TYPE,
				'post_status'      => 'publish',
				'numberposts'      => -1,
				'orderby'          => 'date',
				'order'            => 'DESC',
				'meta_key'         => ApplicationMetaKeys::CENTRE_CODE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- las solicitudes de un centro se buscan por su código.
				'meta_value'       => $centre_code, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_compare'     => 'IN',
				'suppress_filters' => false,
			)
		);
		return is_array( $posts ) ? $posts : array();
	}

	/**
	 * How many applications a procedure has.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return int
	 */
	public static function count( int $procedure_id ): int {
		if ( $procedure_id <= 0 ) {
			return 0;
		}
		$ids = get_posts(
			array(
				'post_type'        => ApplicationPostType::POST_TYPE,
				'post_parent'      => $procedure_id,
				'post_status'      => 'publish',
				'numberposts'      => -1,
				'fields'           => 'ids',
				'suppress_filters' => false,
			)
		);
		return is_array( $ids ) ? count( $ids ) : 0;
	}

	/**
	 * Every meta of one application, in its stored shape.
	 *
	 * @param int $application_id Application post ID.
	 * @return array<string, mixed> Keyed by ApplicationMetaKeys.
	 */
	public static function meta( int $application_id ): array {
		$out = array();
		foreach ( ApplicationMetaKeys::all() as $clave ) {
			$out[ $clave ] = get_post_meta( $application_id, $clave, true );
		}
		$out[ ApplicationMetaKeys::COORDINATOR ] = is_array( $out[ ApplicationMetaKeys::COORDINATOR ] )
			? $out[ ApplicationMetaKeys::COORDINATOR ]
			: array(
				'name'  => '',
				'email' => '',
			);
		$out[ ApplicationMetaKeys::ANSWERS ]     = is_array( $out[ ApplicationMetaKeys::ANSWERS ] ) ? $out[ ApplicationMetaKeys::ANSWERS ] : array();
		return $out;
	}

	/**
	 * The questions of one procedure, normalised.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return array<int, array<string, mixed>>
	 */
	public static function questions( int $procedure_id ): array {
		return ProcedureQuestions::sanitize( get_post_meta( $procedure_id, ProcedureMetaKeys::QUESTIONS, true ) );
	}

	/**
	 * What a procedure asks of a school, in the shape ApplicationInput wants.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return array{coordinator_count:int, requires_coordinator:bool, commitments:string, questions:array<int, array<string, mixed>>}
	 */
	public static function spec( int $procedure_id ): array {
		$teacher = ProcedureMetaKeys::AUDIENCE_TEACHERS === get_post_meta( $procedure_id, ProcedureMetaKeys::AUDIENCE, true );
		return array(
			'applicant_type'       => $teacher ? ApplicationMetaKeys::TYPE_TEACHER : ApplicationMetaKeys::TYPE_CENTRE,
			'coordinator_count'    => $teacher ? 0 : self::coordinator_count( $procedure_id ),
			'requires_coordinator' => ! $teacher && self::coordinator_count( $procedure_id ) > 0,
			'commitments'          => (string) get_post_meta( $procedure_id, ProcedureMetaKeys::COMMITMENTS, true ),
			'questions'            => ProcedureQuestions::application_questions( self::questions( $procedure_id ) ),
		);
	}

	/**
	 * Number of coordinators requested, including procedures saved before the count field existed.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return int
	 */
	public static function coordinator_count( int $procedure_id ): int {
		if ( $procedure_id <= 0 ) {
			return 0;
		}
		$count = metadata_exists( 'post', $procedure_id, ProcedureMetaKeys::COORDINATOR_COUNT )
			? get_post_meta( $procedure_id, ProcedureMetaKeys::COORDINATOR_COUNT, true )
			: ( ! empty( get_post_meta( $procedure_id, ProcedureMetaKeys::REQUIRES_COORDINATOR, true ) ) ? 1 : 0 );
		return ProcedureMetaRegistration::sanitize_coordinator_count( $count );
	}

	/**
	 * The school of a person: its code, and its name if the catalogue knows it.
	 *
	 * Un código que no está en el catálogo solicita igual, con el nombre en
	 * blanco: el catálogo puede ir por detrás de la realidad.
	 *
	 * Con código, el de la agrupación de escuelas rurales que coordina, si es
	 * suyo; uno que no es de la cuenta no se devuelve nunca (ADR-0031).
	 *
	 * @param int    $user_id User ID (0 = current).
	 * @param string $code    One of the person's codes; empty for the school.
	 * @return array{code:string, name:string, ownership:string} Empty code when the person has none.
	 */
	public static function centre_of( int $user_id = 0, string $code = '' ): array {
		if ( '' === $code ) {
			$code = CentreScope::code_for( $user_id );
		} elseif ( ! isset( CentreScope::codes_for( $user_id )[ $code ] ) ) {
			$code = '';
		}
		$centro = '' !== $code ? CentreCatalog::find( $code ) : array();
		return array(
			'code'      => $code,
			'name'      => (string) ( $centro['name'] ?? '' ),
			'ownership' => (string) ( $centro['ownership'] ?? '' ),
		);
	}

	// ─── escribir ──────────────────────────────────────────────────────────

	/**
	 * Store the application of a school: create it, or update the one it has.
	 *
	 * Lo que llega es lo que {@see \Prc\Domain\ApplicationInput::validate()}
	 * devolvió en `data`; aquí no se vuelve a validar. Una solicitud editada
	 * vuelve a «presentada»: si se le pidió subsanar, quien gestiona tiene que
	 * volver a mirarla (ADR-0020). La nota se conserva, para que se sepa qué
	 * se pidió.
	 *
	 * @param int                                 $procedure_id Procedure post ID.
	 * @param int                                 $user_id      Who submits.
	 * @param array<string, mixed>                $data         Validated core: position, coordinator, answers.
	 * @param string                              $code         One of the person's codes; empty for the school.
	 * @param array<string, array<string, mixed>> $files Validated uploads.
	 * @return int Application post ID, 0 on failure.
	 */
	public static function save( int $procedure_id, int $user_id, array $data, string $code = '', array $files = array() ): int {
		if ( $procedure_id <= 0 || $user_id <= 0 || ProcedurePostType::POST_TYPE !== get_post_type( $procedure_id ) ) {
			return 0;
		}
		$centro = self::centre_of( $user_id, $code );
		if ( '' === $centro['code'] ) {
			return 0;
		}

		$created = null === self::find( $procedure_id, $centro['code'] );
		$id      = self::save_post( $procedure_id, $user_id, $centro, $files );
		if ( 0 === $id ) {
			return 0;
		}

		// `wp_slash()` porque `update_post_meta()` desescapa lo que le llega:
		// sin esto una respuesta con una comilla llegaría rota a la base de datos.
		$data += array(
			'position'    => '',
			'coordinator' => array(),
			'answers'     => array(),
		);
		$metas = array(
			ApplicationMetaKeys::APPLICANT_TYPE     => ApplicationMetaKeys::TYPE_CENTRE,
			ApplicationMetaKeys::CENTRE_CODE        => $centro['code'],
			ApplicationMetaKeys::CENTRE_NAME        => $centro['name'],
			ApplicationMetaKeys::APPLICANT_POSITION => (string) $data['position'],
			ApplicationMetaKeys::COORDINATOR        => (array) $data['coordinator'],
			ApplicationMetaKeys::ANSWERS            => (array) $data['answers'],
			ApplicationMetaKeys::REVIEW_STATE       => ApplicationMetaKeys::REVIEW_SUBMITTED,
		);
		foreach ( $metas as $clave => $valor ) {
			update_post_meta( $id, $clave, wp_slash( $valor ) );
		}
		self::saved( $id, $created, $user_id );
		return $id;
	}

	/**
	 * Save the application made by a teacher as an individual.
	 *
	 * @param int                                 $procedure_id Procedure ID.
	 * @param int                                 $user_id      Teacher account ID.
	 * @param array<string, mixed>                $data         Validated answers.
	 * @param array<string, array<string, mixed>> $files        Validated uploads.
	 * @return int Application post ID, or zero on failure.
	 */
	public static function save_teacher( int $procedure_id, int $user_id, array $data, array $files = array() ): int {
		$user = get_userdata( $user_id );
		if ( ! $user || ! ProcedureAccess::can_apply( $user_id, $procedure_id ) || ProcedureMetaKeys::AUDIENCE_TEACHERS !== get_post_meta( $procedure_id, ProcedureMetaKeys::AUDIENCE, true ) ) {
			return 0;
		}
		$created = null === self::find_for_teacher( $procedure_id, $user_id );
		$id      = self::save_post(
			$procedure_id,
			$user_id,
			array(
				'code'      => '',
				'name'      => '',
				'ownership' => '',
			),
			$files,
			ApplicationMetaKeys::TYPE_TEACHER,
			'Solicitud individual'
		);
		if ( 0 === $id ) {
			return 0;
		}
		$data += array( 'answers' => array() );
		foreach (
			array(
				ApplicationMetaKeys::APPLICANT_TYPE     => ApplicationMetaKeys::TYPE_TEACHER,
				ApplicationMetaKeys::CENTRE_CODE        => '',
				ApplicationMetaKeys::CENTRE_NAME        => '',
				ApplicationMetaKeys::APPLICANT_POSITION => '',
				ApplicationMetaKeys::COORDINATOR        => array(),
				ApplicationMetaKeys::ANSWERS            => (array) $data['answers'],
				ApplicationMetaKeys::REVIEW_STATE       => ApplicationMetaKeys::REVIEW_SUBMITTED,
			) as $key => $value
		) {
			update_post_meta( $id, $key, wp_slash( $value ) );
		}
		self::saved( $id, $created, $user_id );
		return $id;
	}

	/**
	 * Tell whoever listens that an application has just been stored.
	 *
	 * Después de escribir todas las metas: quien escucha —el correo, por
	 * ejemplo (ADR-0038)— lee la solicitud ya entera. Lo que haga no cambia lo
	 * que esta función devuelve: la solicitud ya está guardada.
	 *
	 * La cuenta que guarda viaja con la acción porque la solicitud de un
	 * centro es del centro y no de quien la presentó: otra persona del mismo
	 * centro puede modificarla, y el aviso es para ella.
	 *
	 * @param int  $id      Application post ID.
	 * @param bool $created Whether it did not exist before.
	 * @param int  $user_id Account that stored it.
	 * @return void
	 */
	private static function saved( int $id, bool $created, int $user_id ): void {
		/**
		 * An application has just been stored, new or edited.
		 *
		 * @param int  $id      Application post ID.
		 * @param bool $created Whether it did not exist before.
		 * @param int  $user_id Account that stored it.
		 */
		do_action( 'prc_application_saved', $id, $created, $user_id );
	}

	/**
	 * Store the post and documents before changing the application core.
	 *
	 * @param int                                 $procedure_id Procedure ID.
	 * @param int                                 $user_id    Applicant ID.
	 * @param array<string, string>               $centro School snapshot.
	 * @param array<string, array<string, mixed>> $files     Validated uploads.
	 * @param string                              $type      Applicant type.
	 * @param string                              $applicant Applicant title.
	 * @return int Post ID, or zero on failure.
	 */
	private static function save_post( int $procedure_id, int $user_id, array $centro, array $files, string $type = ApplicationMetaKeys::TYPE_CENTRE, string $applicant = '' ): int {
		$titulo = ApplicationMetaKeys::TYPE_TEACHER === $type ? $applicant . ' — ' . (string) get_post_field( 'post_title', $procedure_id ) : self::title( $centro, $procedure_id );
		$id     = ApplicationMetaKeys::TYPE_TEACHER === $type ? self::find_for_teacher( $procedure_id, $user_id ) : self::find( $procedure_id, $centro['code'] );
		$is_new = null === $id;
		if ( null === $id ) {
			$id = wp_insert_post(
				array(
					'post_type'   => ApplicationPostType::POST_TYPE,
					'post_parent' => $procedure_id,
					'post_author' => $user_id,
					'post_status' => 'publish',
					'post_title'  => wp_slash( $titulo ),
				),
				true
			);
		}
		if ( is_wp_error( $id ) || ! $id ) {
			return 0;
		}
		$id = (int) $id;
		if ( ! ApplicationFiles::store_all( $id, $files ) ) {
			if ( $is_new ) {
				wp_delete_post( $id, true );
			}
			return 0;
		}
		if ( ! $is_new ) {
			$id = wp_update_post(
				array(
					'ID'         => $id,
					'post_title' => wp_slash( $titulo ),
				),
				true
			);
			if ( is_wp_error( $id ) || ! $id ) {
				return 0;
			}
		}

		return $id;
	}

	/**
	 * Set the review state of one application, with its note.
	 *
	 * La nota es obligatoria al pedir subsanar y al excluir: un centro al que
	 * se le pide algo tiene que leer qué.
	 *
	 * @param int    $application_id Application post ID.
	 * @param string $state          One of ApplicationMetaKeys::review_states().
	 * @param string $note           Why, in words.
	 * @return array{ok:bool, error:string} Error: `application`, `state` or `note`.
	 */
	public static function review( int $application_id, string $state, string $note ): array {
		if ( ApplicationPostType::POST_TYPE !== get_post_type( $application_id ) ) {
			return self::refused( 'application' );
		}
		if ( ! isset( ApplicationMetaKeys::review_states()[ $state ] ) ) {
			return self::refused( 'state' );
		}
		$note = trim( $note );
		if ( '' === $note && in_array( $state, ApplicationMetaKeys::states_needing_note(), true ) ) {
			return self::refused( 'note' );
		}

		$previous      = (string) get_post_meta( $application_id, ApplicationMetaKeys::REVIEW_STATE, true );
		$previous_note = (string) get_post_meta( $application_id, ApplicationMetaKeys::REVIEW_NOTE, true );
		update_post_meta( $application_id, ApplicationMetaKeys::REVIEW_STATE, $state );
		update_post_meta( $application_id, ApplicationMetaKeys::REVIEW_NOTE, wp_slash( $note ) );

		/**
		 * The review state of an application has just been set.
		 *
		 * Se emite en cada revisión, cambie o no algo: quien escucha decide
		 * con el estado y la nota de antes si hay algo nuevo que contar
		 * (ADR-0038). Un estado de antes vacío es una solicitud anterior a
		 * la revisión, no un estado.
		 *
		 * @param int    $application_id Application post ID.
		 * @param string $state          New review state.
		 * @param string $previous       Review state before; may be empty.
		 * @param string $previous_note  Review note before.
		 */
		do_action( 'prc_application_reviewed', $application_id, $state, $previous, $previous_note );

		return array(
			'ok'    => true,
			'error' => '',
		);
	}

	/**
	 * A refusal, with its code.
	 *
	 * @param string $error Error code.
	 * @return array{ok:bool, error:string}
	 */
	private static function refused( string $error ): array {
		return array(
			'ok'    => false,
			'error' => $error,
		);
	}

	/**
	 * The title an application gets: «{centro} — {procedimiento}».
	 *
	 * @param array{code:string, name:string} $centro       The school.
	 * @param int                             $procedure_id Procedure post ID.
	 * @return string
	 */
	private static function title( array $centro, int $procedure_id ): string {
		$nombre = '' !== $centro['name'] ? $centro['name'] : $centro['code'];
		return $nombre . ' — ' . (string) get_post_field( 'post_title', $procedure_id );
	}

	// ─── la tabla y el CSV ─────────────────────────────────────────────────

	/**
	 * The columns of the applications table, in order, with their heading.
	 *
	 * Las fijas y, detrás, una por pregunta del procedimiento: la clave es la
	 * `key` de la pregunta, así que reescribir un rótulo mueve la cabecera y
	 * no descoloca ni un dato (ADR-0019).
	 *
	 * @param array<int, array<string, mixed>> $questions Normalised questions.
	 * @return array<string, string> clave => rótulo.
	 */
	public static function columns( array $questions ): array {
		$out = array(
			'centre'            => 'Centro',
			'code'              => 'Código',
			'date'              => 'Fecha',
			'applicant'         => 'Presentada por',
			'email'             => 'Correo',
			'position'          => 'Cargo',
			'coordinator'       => 'Coordinación',
			'coordinator_dni'   => 'DNI o NIE de coordinación',
			'coordinator_email' => 'Correo de coordinación',
			'state'             => 'Estado de revisión',
			'note'              => 'Nota',
		);
		foreach ( $questions as $pregunta ) {
			$out[ (string) $pregunta['key'] ] = (string) $pregunta['label'];
		}
		return $out;
	}

	/**
	 * The applications of one procedure as rows of the table.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return array<int, array<string, mixed>> One row per application, keyed by columns(); `id`, `state_key`, `date_label` and {@see KEY_FILES} on top.
	 */
	public static function rows( int $procedure_id ): array {
		$preguntas   = self::questions( $procedure_id );
		$filas       = array();
		$solicitudes = self::for_procedure( $procedure_id );
		// Quién presentó cada una: todas las personas en una consulta, no una por fila.
		cache_users( array_unique( array_map( 'intval', wp_list_pluck( $solicitudes, 'post_author' ) ) ) );
		foreach ( $solicitudes as $solicitud ) {
			$filas[] = self::row( $solicitud, $preguntas );
		}
		return $filas;
	}

	/**
	 * One application as a row of the table.
	 *
	 * @param \WP_Post                         $application The application.
	 * @param array<int, array<string, mixed>> $questions   Normalised questions of its procedure.
	 * @return array<string, mixed> Columns as strings, plus {@see KEY_FILES}, which is not one.
	 */
	public static function row( \WP_Post $application, array $questions ): array {
		$meta   = self::meta( (int) $application->ID );
		$autor  = get_userdata( (int) $application->post_author );
		$coord  = ApplicationMetaRegistration::sanitize_coordinator( $meta[ ApplicationMetaKeys::COORDINATOR ] );
		$estado = (string) $meta[ ApplicationMetaKeys::REVIEW_STATE ];

		$fila = array(
			'id'                => (string) $application->ID,
			'state_key'         => $estado,
			'centre'            => ApplicationMetaKeys::TYPE_TEACHER === ApplicationMetaRegistration::sanitize_applicant_type( $meta[ ApplicationMetaKeys::APPLICANT_TYPE ] ) ? 'Profesorado' : (string) $meta[ ApplicationMetaKeys::CENTRE_NAME ],
			'code'              => (string) $meta[ ApplicationMetaKeys::CENTRE_CODE ],
			// Dos valores de la misma fecha: `date` es la columna del CSV —en ISO,
			// que es lo que ordena bien en una hoja de cálculo— y `date_label` es
			// lo que se lee en pantalla, como el resto del aplicativo.
			'date'              => (string) get_the_date( 'Y-m-d H:i', $application ),
			'date_label'        => (string) get_the_date( 'd-m-Y', $application ),
			'applicant'         => false !== $autor ? (string) $autor->display_name : '',
			'email'             => false !== $autor ? (string) $autor->user_email : '',
			'position'          => (string) ( ApplicationMetaKeys::positions()[ $meta[ ApplicationMetaKeys::APPLICANT_POSITION ] ] ?? '' ),
			'coordinator'       => implode( ', ', array_filter( array_column( $coord, 'name' ) ) ),
			'coordinator_dni'   => implode( ', ', array_filter( array_column( $coord, 'dni' ) ) ),
			'coordinator_email' => implode( ', ', array_filter( array_column( $coord, 'email' ) ) ),
			'state'             => (string) ( ApplicationMetaKeys::review_states()[ $estado ] ?? '' ),
			'note'              => (string) $meta[ ApplicationMetaKeys::REVIEW_NOTE ],
		);
		// Una pregunta borrada deja de pintarse y su respuesta se queda en la
		// meta bajo su clave: solo salen las preguntas que hoy tiene el procedimiento.
		$respuestas = (array) $meta[ ApplicationMetaKeys::ANSWERS ];
		$documentos = ApplicationFiles::descriptors( (int) $application->ID );
		$adjuntos   = array();
		foreach ( $questions as $pregunta ) {
			$key = (string) $pregunta['key'];

			// En la columna de una pregunta de fichero va el **nombre** del
			// documento, que es lo que se lee y lo que se exporta; el enlace lo
			// compone la pantalla con el identificador opaco (ADR-0028).
			if ( ProcedureQuestions::TYPE_FILE === (string) ( $pregunta['type'] ?? '' ) ) {
				$descriptor       = $documentos[ $key ] ?? array();
				$fila[ $key ]     = (string) ( $descriptor['name'] ?? '' );
				$adjuntos[ $key ] = array() === $descriptor
					? array()
					: array(
						'application' => (int) $application->ID,
						'id'          => (string) ( $descriptor['id'] ?? '' ),
						'name'        => (string) ( $descriptor['name'] ?? '' ),
					);
				continue;
			}
			$fila[ $key ] = ProcedureQuestions::as_text( $pregunta, $respuestas[ $key ] ?? null );
		}
		$fila[ self::KEY_FILES ] = $adjuntos;
		return $fila;
	}

	/**
	 * The applications of one procedure as a CSV file.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return string
	 */
	public static function csv( int $procedure_id ): string {
		return self::csv_lines( self::columns( self::questions( $procedure_id ) ), self::rows( $procedure_id ) );
	}

	/**
	 * Rows as CSV text, headings included. Pure: no WordPress.
	 *
	 * Separador **punto y coma** y BOM de UTF-8 al principio, que es lo que
	 * abre bien en una hoja de cálculo en español sin pasar por el asistente
	 * de importación. Cada campo va entrecomillado y las comillas de dentro se
	 * duplican (RFC 4180). Lo que empiece por `=`, `+`, `-` o `@` lleva una
	 * comilla simple delante: sin eso, una hoja de cálculo lo trata como
	 * fórmula y una respuesta tecleada se convierte en ejecución.
	 *
	 * @param array<string, string>             $columns clave => rótulo.
	 * @param array<int, array<string, string>> $rows    Rows keyed by column.
	 * @return string
	 */
	public static function csv_lines( array $columns, array $rows ): string {
		$lineas = array( self::csv_line( array_values( $columns ) ) );
		foreach ( $rows as $fila ) {
			$campos = array();
			foreach ( array_keys( $columns ) as $clave ) {
				$campos[] = (string) ( $fila[ $clave ] ?? '' );
			}
			$lineas[] = self::csv_line( $campos );
		}
		// CRLF y BOM: para que el fichero se abra donde se va a abrir, que es
		// una hoja de cálculo de escritorio y no un editor.
		return "\xEF\xBB\xBF" . implode( "\r\n", $lineas ) . "\r\n";
	}

	/**
	 * The file name an export gets.
	 *
	 * @param string $title Procedure title.
	 * @return string
	 */
	public static function filename( string $title ): string {
		$base = sanitize_title( $title );
		return 'solicitudes-' . ( '' !== $base ? $base : 'procedimiento' ) . '-' . gmdate( 'Y-m-d' ) . '.csv';
	}

	/**
	 * One CSV line, quoted and escaped.
	 *
	 * @param string[] $campos Fields.
	 * @return string
	 */
	private static function csv_line( array $campos ): string {
		$fuera = array();
		foreach ( $campos as $campo ) {
			if ( '' !== $campo && false !== strpos( "=+-@\t\r", $campo[0] ) ) {
				$campo = "'" . $campo;
			}
			$fuera[] = '"' . str_replace( '"', '""', $campo ) . '"';
		}
		return implode( ';', $fuera );
	}
}
