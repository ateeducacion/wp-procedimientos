<?php
/**
 * The applications of a procedure as the table asks for them: filtered,
 * counted and a page at a time.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\Domain\ApplicationCompleteness;
use Prc\Domain\ApplicationFilter;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\PostType\ApplicationPostType;

/**
 * Leer las solicitudes de un procedimiento para la tabla del taller.
 *
 * Con cientos de solicitudes la tabla no puede leer todas las metas de todas
 * para pintar cincuenta. Así que se lee en dos tiempos:
 *
 * 1. **El censo**: las solicitudes del procedimiento sin sus metas (una
 *    consulta) y, aparte, solo las pocas metas que deciden el filtro —estado
 *    de revisión, código y nombre del centro, tipo, coordinación y, si alguna
 *    puede estar incompleta, personas y documentos— en otra consulta. Con eso
 *    se cuentan los estados, se sacan las islas y las zonas presentes y se
 *    filtra en memoria; la isla y la zona se miran en el catálogo, que se lee
 *    una vez ({@see CentreColumns::index_for()}).
 * 2. **La página**: las metas enteras solo de las cincuenta que se pintan.
 *
 * Son siempre las mismas consultas, haya treinta solicitudes o trescientas.
 * El estado y la búsqueda se resuelven también en el censo y no en SQL: el
 * censo ya hace falta entero para los contadores y las opciones, y buscar en
 * memoria trata un `%` o un `_` como letras y no distingue tildes.
 *
 * `$scope` es un acotado adicional por códigos de centro: quien consulta
 * solo las de su zona (PR 7) pasa aquí sus códigos, y todo —contadores,
 * opciones, filtro, página y exportación— sale ya acotado.
 */
final class ApplicationQuery {

	/**
	 * Metas the census always reads: one short value each.
	 *
	 * @var string[]
	 */
	private const CENSUS = array(
		ApplicationMetaKeys::REVIEW_STATE,
		ApplicationMetaKeys::CENTRE_CODE,
		ApplicationMetaKeys::CENTRE_NAME,
		ApplicationMetaKeys::APPLICANT_TYPE,
		ApplicationMetaKeys::COORDINATOR,
		ApplicationMetaKeys::CREATED_BY,
	);

	/**
	 * The applications of a procedure as the table asks for them.
	 *
	 * @param int                  $procedure_id Procedure post ID.
	 * @param array<string, mixed> $filter       What {@see ApplicationFilter::sanitize()} returned.
	 * @param array<string, mixed> $scope        `codes`: only applications of these centre codes; absent, no narrowing.
	 * @return array{filter:array, active:bool, ids:int[], all:int, total:int, counts:array<string,int>, incomplete:int, may_be_incomplete:bool, options:array{islands:array<string,string>, zones:array<string,string>}, posts:\WP_Post[], reasons:array<int,string[]>, stored:array<int,array<string,mixed>>, page:int, pages:int, offset:int, from:int, to:int}
	 */
	public static function run( int $procedure_id, array $filter, array $scope = array() ): array {
		$census = self::census( $procedure_id, $scope );
		// El catálogo, una vez y solo si alguna es de un centro con código.
		$catalogo = CentreColumns::index_for( array_filter( array_column( $census['stored'], ApplicationMetaKeys::CENTRE_CODE ) ) );
		$opciones = self::options( $census['stored'], $catalogo );
		$filter   = ApplicationFilter::against( $filter, $opciones );
		// Donde ninguna puede estar incompleta, «incompletas» no es un filtro.
		$filter['incomplete'] = $census['check'] && true === $filter['incomplete'];
		$motivos              = $census['check'] ? self::reasons( $procedure_id, $census['stored'], $census['pending'] ) : null;
		$ids                  = self::narrow( $census, $filter, $catalogo, $motivos ?? array() );
		$ventana              = ApplicationFilter::window( count( $ids ), (int) $filter['page'] );

		$filter['page'] = $ventana['page'];
		$pagina         = array_slice( $ids, $ventana['offset'], ApplicationFilter::PER_PAGE );
		return array(
			'filter'            => $filter,
			'active'            => ApplicationFilter::active( $filter ),
			'ids'               => $ids,
			'all'               => count( $census['posts'] ),
			'total'             => count( $ids ),
			'counts'            => self::counts( $census['stored'] ),
			'incomplete'        => count( $motivos ?? array() ),
			'may_be_incomplete' => null !== $motivos,
			'options'           => $opciones,
			'posts'             => self::page( $pagina ),
			'reasons'           => array_intersect_key( $motivos ?? array(), array_flip( $pagina ) ),
			'stored'            => $census['stored'],
		) + $ventana;
	}

	/**
	 * Only the IDs the filter leaves, in table order, every page together.
	 *
	 * Es lo que exporta «las del filtro»: la misma consulta que la tabla.
	 *
	 * @param int                  $procedure_id Procedure post ID.
	 * @param array<string, mixed> $filter       What {@see ApplicationFilter::sanitize()} returned.
	 * @param array<string, mixed> $scope        As in {@see run()}.
	 * @return int[]
	 */
	public static function ids( int $procedure_id, array $filter, array $scope = array() ): array {
		return self::run( $procedure_id, $filter, $scope )['ids'];
	}

	/**
	 * The review state an application counts as: an empty or unknown one is
	 * an application from before the review, so «presentada».
	 *
	 * @param mixed $state Stored review state.
	 * @return string
	 */
	public static function state_of( $state ): string {
		$state = is_string( $state ) ? $state : '';
		return isset( ApplicationMetaKeys::review_states()[ $state ] ) ? $state : ApplicationMetaKeys::REVIEW_SUBMITTED;
	}

	// ─── el censo ──────────────────────────────────────────────────────────

	/**
	 * Every application of the procedure without its metas, and the metas
	 * that decide the filter.
	 *
	 * @param int                  $procedure_id Procedure post ID.
	 * @param array<string, mixed> $scope        As in {@see run()}.
	 * @return array{posts:array<int, \WP_Post>, stored:array<int, array<string, mixed>>, check:bool} `check`: whether people and documents were read, because some may be incomplete.
	 */
	private static function census( int $procedure_id, array $scope ): array {
		$vacio = array(
			'posts'   => array(),
			'stored'  => array(),
			'check'   => false,
			'pending' => array(),
		);
		if ( $procedure_id <= 0 ) {
			return $vacio;
		}
		$posts = get_posts(
			array(
				'post_type'              => ApplicationPostType::POST_TYPE,
				'post_parent'            => $procedure_id,
				'post_status'            => 'publish',
				'numberposts'            => -1,
				// El ID desempata: el mismo orden que la exportación.
				'orderby'                => array(
					'date' => 'ASC',
					'ID'   => 'ASC',
				),
				// Sin metas ni términos: de eso se encarga stored() con solo lo que hace falta.
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'suppress_filters'       => false,
			)
		);
		if ( ! is_array( $posts ) || array() === $posts ) {
			return $vacio;
		}
		$por_id = array();
		foreach ( $posts as $post ) {
			$por_id[ (int) $post->ID ] = $post;
		}
		$claves = self::CENSUS;
		$mirar  = self::can_be_incomplete( $procedure_id );
		if ( $mirar ) {
			$claves[] = ApplicationMetaKeys::PEOPLE;
			$claves[] = ApplicationMetaKeys::FILES;
		}
		$stored = self::stored( $procedure_id, $claves );
		foreach ( array_keys( $por_id ) as $id ) {
			$stored[ $id ] = ( $stored[ $id ] ?? array() ) + array_fill_keys( $claves, '' );
		}
		if ( array_key_exists( 'codes', $scope ) ) {
			$codigos = array_flip( array_map( 'strval', (array) $scope['codes'] ) );
			foreach ( $stored as $id => $meta ) {
				if ( ! isset( $codigos[ (string) $meta[ ApplicationMetaKeys::CENTRE_CODE ] ] ) ) {
					unset( $stored[ $id ], $por_id[ $id ] );
				}
			}
		}
		$pendientes = self::pending( $por_id, $stored );
		return array(
			'posts'   => $por_id,
			'stored'  => array_intersect_key( $stored, $por_id ),
			// Una presentada en nombre del centro y sin completar está
			// incompleta aunque nada más pueda estarlo (ADR-0044).
			'check'   => $mirar || array() !== $pendientes,
			'pending' => $pendientes,
		);
	}

	/**
	 * The applications submitted on behalf of their school that the school has not completed.
	 *
	 * @param array<int, \WP_Post>             $posts  Census posts by ID.
	 * @param array<int, array<string, mixed>> $stored Census metas.
	 * @return array<int, bool> Application ID => true.
	 */
	private static function pending( array $posts, array $stored ): array {
		$out = array();
		foreach ( $posts as $id => $post ) {
			$creada_por = (int) ( $stored[ $id ][ ApplicationMetaKeys::CREATED_BY ] ?? 0 );
			if ( $creada_por > 0 && (int) $post->post_author === $creada_por ) {
				$out[ $id ] = true;
			}
		}
		return $out;
	}

	/**
	 * Some metas of every application of a procedure, in one query.
	 *
	 * No pasa por la caché de metas de WordPress: llenarla a medias haría
	 * creer a `get_post_meta()` que una solicitud no tiene el resto. Como
	 * `get_post_meta( …, true )`, de una clave repetida vale la primera.
	 *
	 * @param int      $procedure_id Procedure post ID.
	 * @param string[] $keys         Meta keys.
	 * @return array<int, array<string, mixed>> Application ID => meta key => value, unserialised.
	 */
	private static function stored( int $procedure_id, array $keys ): array {
		global $wpdb;
		$huecos = implode( ',', array_fill( 0, count( $keys ), '%s' ) );
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- los huecos son %s y se preparan; leer unas pocas metas de muchas solicitudes no lo da la API sin cargarlas todas.
		$filas = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT m.post_id, m.meta_key, m.meta_value FROM {$wpdb->postmeta} m INNER JOIN {$wpdb->posts} p ON p.ID = m.post_id WHERE p.post_type = %s AND p.post_parent = %d AND p.post_status = 'publish' AND m.meta_key IN ({$huecos}) ORDER BY m.meta_id ASC",
				array_merge( array( ApplicationPostType::POST_TYPE, $procedure_id ), $keys )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		$out = array();
		foreach ( (array) $filas as $fila ) {
			$id = (int) $fila->post_id;
			if ( ! isset( $out[ $id ][ $fila->meta_key ] ) ) {
				$out[ $id ][ (string) $fila->meta_key ] = maybe_unserialize( $fila->meta_value );
			}
		}
		return $out;
	}

	/**
	 * Whether some application of the procedure may be incomplete today.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return bool
	 */
	private static function can_be_incomplete( int $procedure_id ): bool {
		return ApplicationCompleteness::may_be_incomplete( self::people_spec( $procedure_id ), Applications::questions( $procedure_id ), ProcedureState::today() );
	}

	/**
	 * The people specification that counts: none for a procedure that does
	 * not ask for the list, or one for teachers.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return array<string, mixed>
	 */
	private static function people_spec( int $procedure_id ): array {
		return People::enabled( $procedure_id ) ? People::spec( $procedure_id ) : array( 'enabled' => false );
	}

	// ─── contar, ofrecer y filtrar ─────────────────────────────────────────

	/**
	 * How many applications are in each review state, without the filter.
	 *
	 * @param array<int, array<string, mixed>> $stored Census metas.
	 * @return array<string, int> Review state => count, every state present.
	 */
	private static function counts( array $stored ): array {
		$out = array_fill_keys( array_keys( ApplicationMetaKeys::review_states() ), 0 );
		foreach ( $stored as $meta ) {
			++$out[ self::state_of( $meta[ ApplicationMetaKeys::REVIEW_STATE ] ) ];
		}
		return $out;
	}

	/**
	 * The islands and zones the applications have, not the whole catalogue.
	 *
	 * @param array<int, array<string, mixed>>    $stored  Census metas.
	 * @param array<string, array<string, mixed>> $catalog Catalogue by code.
	 * @return array{islands:array<string, string>, zones:array<string, string>} Value => label, sorted by label.
	 */
	private static function options( array $stored, array $catalog ): array {
		$islas = array();
		$zonas = array();
		foreach ( $stored as $meta ) {
			$centro = CentreColumns::attributes( (string) $meta[ ApplicationMetaKeys::CENTRE_CODE ], $catalog );
			if ( '' !== $centro['island'] ) {
				$islas[ $centro['island'] ] = $centro['island'];
			}
			if ( '' !== $centro['zone'] ) {
				$zonas[ $centro['zone'] ] = '' !== $centro['zone_name'] ? $centro['zone_name'] : $centro['zone'];
			}
		}
		asort( $islas, SORT_NATURAL | SORT_FLAG_CASE );
		asort( $zonas, SORT_NATURAL | SORT_FLAG_CASE );
		return array(
			'islands' => $islas,
			'zones'   => $zonas,
		);
	}

	/**
	 * What is missing in each application, when any can miss something.
	 *
	 * @param int                              $procedure_id Procedure post ID.
	 * @param array<int, array<string, mixed>> $stored       Census metas, people and documents included when some may miss them.
	 * @param array<int, bool>                 $pending      The ones the school has not completed yet.
	 * @return array<int, string[]> Application ID => reasons, only the incomplete ones.
	 */
	private static function reasons( int $procedure_id, array $stored, array $pending = array() ): array {
		$spec      = self::people_spec( $procedure_id );
		$preguntas = Applications::questions( $procedure_id );
		$hoy       = ProcedureState::today();
		$out       = array();
		foreach ( $stored as $id => $meta ) {
			$motivos = ApplicationCompleteness::reasons( $spec, $meta[ ApplicationMetaKeys::PEOPLE ] ?? '', $preguntas, $meta[ ApplicationMetaKeys::FILES ] ?? '', $hoy, self::state_of( $meta[ ApplicationMetaKeys::REVIEW_STATE ] ), isset( $pending[ $id ] ) );
			if ( array() !== $motivos ) {
				$out[ $id ] = $motivos;
			}
		}
		return $out;
	}

	/**
	 * The applications the filter leaves, in census order.
	 *
	 * @param array<string, mixed>                $census     What census() returned.
	 * @param array<string, mixed>                $filter     Checked filter.
	 * @param array<string, array<string, mixed>> $catalog    Catalogue by code.
	 * @param array<int, string[]>                $incomplete Reasons of the incomplete ones.
	 * @return int[]
	 */
	private static function narrow( array $census, array $filter, array $catalog, array $incomplete ): array {
		if ( '' !== $filter['search'] ) {
			// El nombre de cada docente: todas las cuentas en una consulta, no una por fila.
			cache_users( array_unique( array_map( 'intval', wp_list_pluck( $census['posts'], 'post_author' ) ) ) );
		}
		$out = array();
		foreach ( $census['stored'] as $id => $meta ) {
			$centro = CentreColumns::attributes( (string) $meta[ ApplicationMetaKeys::CENTRE_CODE ], $catalog );
			if ( ( '' !== $filter['review'] && self::state_of( $meta[ ApplicationMetaKeys::REVIEW_STATE ] ) !== $filter['review'] )
				|| ( '' !== $filter['island'] && $centro['island'] !== $filter['island'] )
				|| ( '' !== $filter['zone'] && $centro['zone'] !== $filter['zone'] )
				|| ( true === $filter['incomplete'] && ! isset( $incomplete[ $id ] ) )
				|| ! ApplicationFilter::matches( (string) $filter['search'], self::searchable( $census['posts'][ $id ], $meta ) ) ) {
				continue;
			}
			$out[] = (int) $id;
		}
		return $out;
	}

	/**
	 * What the search looks in: the name and code of the centre and, in an
	 * application of a teacher, the name of the person.
	 *
	 * @param \WP_Post             $post Application.
	 * @param array<string, mixed> $meta Its census metas.
	 * @return string[]
	 */
	private static function searchable( \WP_Post $post, array $meta ): array {
		$textos = array( (string) $meta[ ApplicationMetaKeys::CENTRE_NAME ], (string) $meta[ ApplicationMetaKeys::CENTRE_CODE ] );
		if ( ApplicationMetaKeys::TYPE_TEACHER === $meta[ ApplicationMetaKeys::APPLICANT_TYPE ] ) {
			$cuenta = get_userdata( (int) $post->post_author );
			if ( false !== $cuenta ) {
				$textos[] = (string) $cuenta->display_name;
			}
		}
		return $textos;
	}

	// ─── la página ─────────────────────────────────────────────────────────

	/**
	 * The applications of one page, with every meta, in one go.
	 *
	 * Los posts ya están en la caché desde el censo; aquí solo se leen las
	 * metas de estas, en una consulta, y sus cuentas, en otra.
	 *
	 * @param int[] $ids Application IDs, in order.
	 * @return \WP_Post[]
	 */
	private static function page( array $ids ): array {
		if ( array() === $ids ) {
			return array();
		}
		update_meta_cache( 'post', $ids );
		$posts = array_values( array_filter( array_map( 'get_post', $ids ) ) );
		cache_users( array_unique( array_map( 'intval', wp_list_pluck( $posts, 'post_author' ) ) ) );
		return $posts;
	}
}
