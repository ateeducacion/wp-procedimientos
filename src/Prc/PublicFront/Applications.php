<?php
/**
 * Applications of schools: store, read, review and export.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\Access\CentreScope;
use Prc\Domain\CentreCatalog;
use Prc\Domain\ProcedureQuestions;
use Prc\Meta\ApplicationMetaKeys;
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
	 * Every application of one school, newest first.
	 *
	 * @param string $centre_code School code.
	 * @return \WP_Post[]
	 */
	public static function for_centre( string $centre_code ): array {
		$centre_code = trim( $centre_code );
		if ( '' === $centre_code ) {
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
	 * @return array{requires_coordinator:bool, commitments:string, questions:array<int, array<string, mixed>>}
	 */
	public static function spec( int $procedure_id ): array {
		return array(
			'requires_coordinator' => (bool) get_post_meta( $procedure_id, ProcedureMetaKeys::REQUIRES_COORDINATOR, true ),
			'commitments'          => (string) get_post_meta( $procedure_id, ProcedureMetaKeys::COMMITMENTS, true ),
			'questions'            => self::questions( $procedure_id ),
		);
	}

	/**
	 * The school of a person: its code, and its name if the catalogue knows it.
	 *
	 * Un código que no está en el catálogo solicita igual, con el nombre en
	 * blanco: el catálogo puede ir por detrás de la realidad.
	 *
	 * @param int $user_id User ID (0 = current).
	 * @return array{code:string, name:string, ownership:string} Empty code when the person has none.
	 */
	public static function centre_of( int $user_id = 0 ): array {
		$code   = CentreScope::code_for( $user_id );
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
	 * @param int                  $procedure_id Procedure post ID.
	 * @param int                  $user_id      Who submits.
	 * @param array<string, mixed> $data         Validated core: position, coordinator, answers.
	 * @return int Application post ID, 0 on failure.
	 */
	public static function save( int $procedure_id, int $user_id, array $data ): int {
		if ( $procedure_id <= 0 || $user_id <= 0 || ProcedurePostType::POST_TYPE !== get_post_type( $procedure_id ) ) {
			return 0;
		}
		$centro = self::centre_of( $user_id );
		if ( '' === $centro['code'] ) {
			return 0;
		}

		$titulo = self::title( $centro, $procedure_id );
		$id     = self::find( $procedure_id, $centro['code'] );
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
		} else {
			$id = wp_update_post(
				array(
					'ID'         => $id,
					'post_title' => wp_slash( $titulo ),
				),
				true
			);
		}
		if ( is_wp_error( $id ) || ! $id ) {
			return 0;
		}
		$id = (int) $id;

		// `wp_slash()` porque `update_post_meta()` desescapa lo que le llega:
		// sin esto una respuesta con una comilla llegaría rota a la base de datos.
		$metas = array(
			ApplicationMetaKeys::CENTRE_CODE        => $centro['code'],
			ApplicationMetaKeys::CENTRE_NAME        => $centro['name'],
			ApplicationMetaKeys::APPLICANT_POSITION => (string) ( $data['position'] ?? '' ),
			ApplicationMetaKeys::COORDINATOR        => (array) ( $data['coordinator'] ?? array() ),
			ApplicationMetaKeys::ANSWERS            => (array) ( $data['answers'] ?? array() ),
			ApplicationMetaKeys::REVIEW_STATE       => ApplicationMetaKeys::REVIEW_SUBMITTED,
		);
		foreach ( $metas as $clave => $valor ) {
			update_post_meta( $id, $clave, wp_slash( $valor ) );
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

		update_post_meta( $application_id, ApplicationMetaKeys::REVIEW_STATE, $state );
		update_post_meta( $application_id, ApplicationMetaKeys::REVIEW_NOTE, wp_slash( $note ) );

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
		$coord  = (array) $meta[ ApplicationMetaKeys::COORDINATOR ];
		$estado = (string) $meta[ ApplicationMetaKeys::REVIEW_STATE ];

		$fila = array(
			'id'                => (string) $application->ID,
			'state_key'         => $estado,
			'centre'            => (string) $meta[ ApplicationMetaKeys::CENTRE_NAME ],
			'code'              => (string) $meta[ ApplicationMetaKeys::CENTRE_CODE ],
			// Dos valores de la misma fecha: `date` es la columna del CSV —en ISO,
			// que es lo que ordena bien en una hoja de cálculo— y `date_label` es
			// lo que se lee en pantalla, como el resto del aplicativo.
			'date'              => (string) get_the_date( 'Y-m-d H:i', $application ),
			'date_label'        => (string) get_the_date( 'd-m-Y', $application ),
			'applicant'         => false !== $autor ? (string) $autor->display_name : '',
			'email'             => false !== $autor ? (string) $autor->user_email : '',
			'position'          => (string) ( ApplicationMetaKeys::positions()[ $meta[ ApplicationMetaKeys::APPLICANT_POSITION ] ] ?? '' ),
			'coordinator'       => (string) ( $coord['name'] ?? '' ),
			'coordinator_email' => (string) ( $coord['email'] ?? '' ),
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
