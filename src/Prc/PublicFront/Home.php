<?php
/**
 * Public front page of the procedures application: the list, by course.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\Domain\DateRange;
use Prc\Domain\ProcedureState;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ProcedurePostType;
use Prc\PublicFront\View\HomeView;
use Prc\Taxonomy\ProcedureTaxonomies;

/**
 * Shortcode [prc_home]: la portada pública.
 *
 * Es lo que hoy pinta una rejilla del constructor de páginas con iconos de
 * estado marcados a mano. Aquí el estado sale de las fechas
 * ({@see ProcedureState}) y el listado se agrupa por él dentro de un curso:
 * abiertos, en subsanación, próximos, cerrados, resueltos e históricos. Se
 * elige un curso —el más reciente por defecto— y, si hace falta, un estado o
 * un ámbito. Los tres filtros son enlaces: cada combinación tiene su dirección
 * y se filtra sin JavaScript.
 *
 * Solo se lee: todos sus parámetros van en la URL, y se ve sin sesión.
 */
final class Home {

	public const SHORTCODE = 'prc_home';

	/**
	 * Query var of the course filter.
	 *
	 * Ninguna se llama como una taxonomía: `prc_course` y `prc_area` son
	 * variables de consulta públicas y usarlas aquí convertiría la página en
	 * el archivo de un término, que ya no lleva nuestro shortcode.
	 */
	public const VAR_COURSE = 'prc_filter_course';

	/**
	 * Query var of the ámbito filter.
	 */
	public const VAR_AREA = 'prc_filter_area';

	/**
	 * Query var of the state filter.
	 *
	 * Es el filtro de pestañas de la portada que se sustituye («Todos» y el
	 * estado que tenga algo). Se llama como los otros dos y no `estado`, que
	 * es palabra de uso común en las URL del sitio.
	 */
	public const VAR_STATE = 'prc_filter_state';

	/**
	 * Los estados en el orden en que se leen en la portada.
	 *
	 * @var string[]
	 */
	public const ORDER = array(
		ProcedureMetaKeys::STATE_OPEN,
		ProcedureMetaKeys::STATE_AMENDMENT,
		ProcedureMetaKeys::STATE_UPCOMING,
		ProcedureMetaKeys::STATE_CLOSED,
		ProcedureMetaKeys::STATE_RESOLVED,
		ProcedureMetaKeys::STATE_ARCHIVED,
	);

	/**
	 * Register the shortcode.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'render' ) );
	}

	/**
	 * Read one navigation parameter.
	 *
	 * @param string $key Query var.
	 * @return int
	 */
	public static function input( string $key ): int {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- filtros de lectura: esta pantalla no muta nada.
		return isset( $_GET[ $key ] ) && is_string( $_GET[ $key ] ) ? max( 0, (int) sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) ) : 0;
	}

	/**
	 * Read the state filter: a slug of ours, or nothing.
	 *
	 * @return string One of ProcedureMetaKeys::states(), or ''.
	 */
	public static function state(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- filtros de lectura: esta pantalla no muta nada.
		$crudo = isset( $_GET[ self::VAR_STATE ] ) && is_string( $_GET[ self::VAR_STATE ] ) ? sanitize_key( wp_unslash( $_GET[ self::VAR_STATE ] ) ) : '';
		return in_array( $crudo, self::ORDER, true ) ? $crudo : '';
	}

	/**
	 * The front page URL, keeping what is selected.
	 *
	 * @param int    $course Course term ID (0 = default).
	 * @param int    $area   Ámbito term ID (0 = every one).
	 * @param string $state  State slug ('' = every state).
	 * @return string
	 */
	public static function url( int $course, int $area = 0, string $state = '' ): string {
		$args = array();
		if ( $course > 0 ) {
			$args[ self::VAR_COURSE ] = (string) $course;
		}
		if ( $area > 0 ) {
			$args[ self::VAR_AREA ] = (string) $area;
		}
		if ( in_array( $state, self::ORDER, true ) ) {
			$args[ self::VAR_STATE ] = $state;
		}
		return Shell::url( 'home', $args );
	}

	/**
	 * Everything the screen decides before painting.
	 *
	 * @return array<string, mixed>
	 */
	public static function model(): array {
		$courses = self::courses();
		$course  = self::input( self::VAR_COURSE );
		if ( ! isset( $courses[ $course ] ) ) {
			// El curso más reciente es el primero: los nombres son «2026-2027».
			$course = array() === $courses ? 0 : (int) array_key_first( $courses );
		}

		$rows  = self::collect( $course );
		$areas = self::options( $rows );
		$area  = self::input( self::VAR_AREA );
		if ( ! isset( $areas[ $area ] ) ) {
			$area = 0;
		}
		if ( $area > 0 ) {
			$rows = array_values(
				array_filter(
					$rows,
					static function ( array $row ) use ( $area ): bool {
						return isset( $row['areas'][ $area ] );
					}
				)
			);
		}

		$groups = array();
		foreach ( self::ORDER as $state ) {
			$del_estado = array_values(
				array_filter(
					$rows,
					static function ( array $row ) use ( $state ): bool {
						return $state === $row['state'];
					}
				)
			);
			if ( array() !== $del_estado ) {
				$groups[ $state ] = array(
					'label' => ProcedureState::label( $state ),
					'rows'  => $del_estado,
				);
			}
		}

		// Los estados que se ofrecen son los que tienen algo: un filtro que
		// deja la pantalla vacía no se pinta nunca, igual que con el ámbito.
		$states = array();
		foreach ( $groups as $slug => $grupo ) {
			$states[ $slug ] = (string) $grupo['label'];
		}
		$state = self::state();
		if ( ! isset( $states[ $state ] ) ) {
			$state = '';
		}
		if ( '' !== $state ) {
			$groups = array( $state => $groups[ $state ] );
		}

		return array(
			'courses'    => $courses,
			'course'     => $course,
			'areas'      => $areas,
			'area'       => $area,
			'states'     => $states,
			'state'      => $state,
			'filters'    => self::filters( $courses, $course, $areas, $area, $states, $state ),
			'groups'     => $groups,
			'total'      => count( $rows ),
			'empty_text' => $area > 0
				? 'Ningún procedimiento de este ámbito en este curso. Pruebe con otro ámbito o con otro curso.'
				: 'Todavía no hay ningún procedimiento publicado en este curso.',
			'reset_url'  => $area > 0 ? self::url( $course ) : '',
		);
	}

	/**
	 * The three filter axes, each one a list of links.
	 *
	 * Enlaces y no un formulario: así el filtro funciona sin JavaScript, cada
	 * combinación tiene su dirección y se puede guardar o compartir. Un eje
	 * con una sola opción no se pinta, que no hay nada que elegir.
	 *
	 * @param array<int, string>    $courses Course term ID => nombre.
	 * @param int                   $course  Selected course.
	 * @param array<int, string>    $areas   Ámbito term ID => nombre.
	 * @param int                   $area    Selected ámbito.
	 * @param array<string, string> $states  State slug => rótulo.
	 * @param string                $state   Selected state.
	 * @return array<int, array{label:string, items:array<int, array{label:string, url:string, active:bool}>}>
	 */
	private static function filters( array $courses, int $course, array $areas, int $area, array $states, string $state ): array {
		if ( '' === Shell::url( 'home' ) ) {
			// Sin página de portada no hay adónde enlazar: el filtro sobra.
			return array();
		}

		$ejes = array();

		$items = array();
		foreach ( $courses as $term_id => $nombre ) {
			$items[] = array(
				'label'  => $nombre,
				'url'    => self::url( (int) $term_id, $area, $state ),
				'active' => (int) $term_id === $course,
			);
		}
		if ( count( $items ) > 1 ) {
			$ejes[] = array(
				'label' => 'Curso escolar',
				'items' => $items,
			);
		}

		// El de estado sí se pinta con una sola opción: son «Todos» y ese
		// estado, que es exactamente lo que enseña la portada que se sustituye.
		if ( array() !== $states ) {
			$items = array(
				array(
					'label'  => 'Todos',
					'url'    => self::url( $course, $area ),
					'active' => '' === $state,
				),
			);
			foreach ( $states as $slug => $rotulo ) {
				$items[] = array(
					'label'  => $rotulo,
					'url'    => self::url( $course, $area, $slug ),
					'active' => $slug === $state,
				);
			}
			$ejes[] = array(
				'label' => 'Estado',
				'items' => $items,
			);
		}

		$items = array(
			array(
				'label'  => 'Todos los ámbitos',
				'url'    => self::url( $course, 0, $state ),
				'active' => 0 === $area,
			),
		);
		foreach ( $areas as $term_id => $nombre ) {
			$items[] = array(
				'label'  => $nombre,
				'url'    => self::url( $course, (int) $term_id, $state ),
				'active' => (int) $term_id === $area,
			);
		}
		if ( count( $items ) > 2 ) {
			$ejes[] = array(
				'label' => 'Ámbito',
				'items' => $items,
			);
		}

		return $ejes;
	}

	/**
	 * The screen, from its model.
	 *
	 * @param array<string, mixed> $model What model() returned.
	 * @return string
	 */
	public static function html( array $model ): string {
		return HomeView::html( $model );
	}

	/**
	 * Render the shortcode.
	 *
	 * @param array<string, string>|string $atts Attributes.
	 * @return string
	 */
	public static function render( $atts = array() ): string {
		unset( $atts );
		Assets::enqueue();
		return self::html( self::model() );
	}

	/**
	 * The courses with something published, newest first.
	 *
	 * @return array<int, string> term_id => nombre.
	 */
	private static function courses(): array {
		$terms = get_terms(
			array(
				'taxonomy'   => ProcedureTaxonomies::COURSE,
				'hide_empty' => true,
			)
		);
		if ( ! is_array( $terms ) ) {
			return array();
		}
		$out = wp_list_pluck( $terms, 'name', 'term_id' );
		arsort( $out, SORT_NATURAL );
		return $out;
	}

	/**
	 * The published procedures of one course.
	 *
	 * @param int $course Course term ID (0 = every course).
	 * @return array<int, array<string, mixed>>
	 */
	private static function collect( int $course ): array {
		$args = array(
			'post_type'           => ProcedurePostType::POST_TYPE,
			'post_status'         => 'publish',
			// ponytail: un curso entero en memoria —hoy son unas decenas de
			// convocatorias por curso, y el estado derivado no se puede pedir a
			// la base de datos—; si crece, guardar el estado en meta y paginar.
			'posts_per_page'      => 500, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- la portada agrupa el curso completo.
			'orderby'             => 'title',
			'order'               => 'ASC',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		);
		if ( $course > 0 ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- listar por curso es el requisito.
				array(
					'taxonomy' => ProcedureTaxonomies::COURSE,
					'field'    => 'term_id',
					'terms'    => array( $course ),
				),
			);
		}

		$solicitados = self::applied_by_centre();

		$rows = array();
		foreach ( get_posts( $args ) as $post ) {
			$rows[] = self::row( $post, $solicitados );
		}

		// Lo que vence antes, primero, y lo que no tiene plazo al final: el
		// orden alfabético del sitio que se sustituye pone «Bibliotecas»
		// delante de algo que cierra mañana. A igualdad de fecha, el título.
		usort(
			$rows,
			static function ( array $a, array $b ): int {
				$uno = '' !== (string) $a['closes_at'] ? (string) $a['closes_at'] : '9999-12-31';
				$dos = '' !== (string) $b['closes_at'] ? (string) $b['closes_at'] : '9999-12-31';
				return $uno === $dos
					? strnatcasecmp( (string) $a['title'], (string) $b['title'] )
					: strcmp( $uno, $dos );
			}
		);
		return $rows;
	}

	/**
	 * The procedures the school of whoever is looking has already applied to.
	 *
	 * Una consulta para toda la pantalla y no una por tarjeta. Para una visita
	 * anónima o para quien no tiene centro, el conjunto está vacío y la
	 * portada no cambia.
	 *
	 * @return array<int, bool> Procedure post ID => true.
	 */
	private static function applied_by_centre(): array {
		if ( ! is_user_logged_in() ) {
			return array();
		}
		$centro = Applications::centre_of( get_current_user_id() );
		if ( '' === (string) $centro['code'] ) {
			return array();
		}

		$suyas = array();
		foreach ( Applications::for_centre( (string) $centro['code'] ) as $solicitud ) {
			$suyas[ (int) $solicitud->post_parent ] = true;
		}
		return $suyas;
	}

	/**
	 * Days from today to a day, in the site timezone.
	 *
	 * @param string $ymd Day, Y-m-d.
	 * @return int|null Negative when it already passed; null when it is not a date.
	 */
	private static function days_until( string $ymd ): ?int {
		$ymd = trim( $ymd );
		if ( 1 !== preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $ymd, $trozos ) || ! checkdate( (int) $trozos[2], (int) $trozos[3], (int) $trozos[1] ) ) {
			return null;
		}
		$hoy  = new \DateTimeImmutable( ProcedureState::today() );
		$fin  = new \DateTimeImmutable( $ymd );
		$dias = $hoy->diff( $fin )->format( '%r%a' );
		return (int) $dias;
	}

	/**
	 * One card of the front page.
	 *
	 * @param \WP_Post         $post        Procedure.
	 * @param array<int, bool> $solicitados Procedure IDs the school already applied to.
	 * @return array<string, mixed>
	 */
	private static function row( \WP_Post $post, array $solicitados = array() ): array {
		$id     = (int) $post->ID;
		$state  = ProcedureState::of_post( $id );
		$titulo = trim( (string) $post->post_title );
		$cierra = (string) get_post_meta( $id, ProcedureMetaKeys::CLOSES_AT, true );
		$fin    = ProcedureMetaKeys::STATE_AMENDMENT === $state
			? (string) get_post_meta( $id, ProcedureMetaKeys::AMEND_CLOSES_AT, true )
			: $cierra;
		$quedan = in_array( $state, array( ProcedureMetaKeys::STATE_OPEN, ProcedureMetaKeys::STATE_AMENDMENT ), true )
			? self::days_until( $fin )
			: null;

		return array(
			'id'           => $id,
			'title'        => '' !== $titulo ? $titulo : 'Procedimiento sin título',
			'url'          => (string) get_permalink( $id ),
			'excerpt'      => trim( wp_strip_all_tags( (string) $post->post_excerpt ) ),
			'areas'        => self::terms( $id ),
			'state'        => $state,
			'state_label'  => ProcedureState::label( $state ),
			'closes_at'    => $cierra,
			'days_left'    => $quedan,
			'deadline'     => self::deadline( $quedan ),
			'urgent'       => null !== $quedan && $quedan <= 3,
			'applied'      => isset( $solicitados[ $id ] ),
			'header_color' => ProcedureMetaKeys::header_hex( get_post_meta( $id, ProcedureMetaKeys::HEADER_COLOR, true ) ),
			'dates'        => DateRange::of(
				(string) get_post_meta( $id, ProcedureMetaKeys::OPENS_AT, true ),
				(string) get_post_meta( $id, ProcedureMetaKeys::CLOSES_AT, true )
			),
			'amend_dates'  => DateRange::of(
				(string) get_post_meta( $id, ProcedureMetaKeys::AMEND_OPENS_AT, true ),
				(string) get_post_meta( $id, ProcedureMetaKeys::AMEND_CLOSES_AT, true )
			),
		);
	}

	/**
	 * How the days left are said on the card.
	 *
	 * @param int|null $days Days from today to the last day of the term.
	 * @return string Empty when there is no term to count.
	 */
	private static function deadline( ?int $days ): string {
		if ( null === $days || $days < 0 ) {
			return '';
		}
		if ( 0 === $days ) {
			return 'Último día';
		}
		if ( 1 === $days ) {
			return 'Cierra mañana';
		}
		return sprintf( 'Cierra en %d días', $days );
	}

	/**
	 * Ámbitos of one procedure.
	 *
	 * @param int $post_id Post ID.
	 * @return array<int, string> term_id => nombre.
	 */
	private static function terms( int $post_id ): array {
		$terms = get_the_terms( $post_id, ProcedureTaxonomies::AREA );
		if ( ! is_array( $terms ) ) {
			return array();
		}
		return wp_list_pluck( $terms, 'name', 'term_id' );
	}

	/**
	 * The ámbito options, taken from the rows themselves.
	 *
	 * Así no se ofrece nunca un filtro que dejaría la pantalla vacía.
	 *
	 * @param array<int, array<string, mixed>> $rows Rows of the course.
	 * @return array<int, string> term_id => nombre.
	 */
	private static function options( array $rows ): array {
		$out = array();
		foreach ( $rows as $row ) {
			foreach ( $row['areas'] as $term_id => $nombre ) {
				$out[ $term_id ] = $nombre;
			}
		}
		asort( $out );
		return $out;
	}
}
