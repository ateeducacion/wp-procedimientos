<?php
/**
 * Public view of a procedure: the single of prc_procedure, painted whole.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\Access\CentreScope;
use Prc\Access\ProcedureAccess;
use Prc\Domain\DateRange;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ProcedurePostType;
use Prc\PublicFront\View\ProcedureChrome;
use Prc\Taxonomy\ProcedureTaxonomies;

/**
 * Lo que ve quien visita un procedimiento.
 *
 * Hoy cada convocatoria es una página del constructor de páginas creada por
 * una acción del gestor de formularios, con el estado pintado a mano. Aquí
 * la ficha es el `single` del tipo de contenido, pintado entero en
 * `template_redirect` con el marco del armazón (ADR-0022): el título, la
 * resolución, el contador del plazo que corre, la caja resumen, las pestañas
 * de contenido, la tarjeta de acceso y el pie. Y un botón que dice lo que va a
 * pasar al pulsarlo: «Solicitar», «Editar mi solicitud» o «Ver mi solicitud»
 * para el equipo directivo, y el enlace al taller para quien lo convocó.
 *
 * El documento lo sirve {@see Shell::serve()}, igual que las pantallas con
 * shortcode; la única diferencia es que el cuerpo lo decide {@see model()} y
 * lo pinta {@see ProcedureChrome::html()}.
 */
final class ProcedureView {

	/**
	 * Prioridad en `template_redirect`: la misma que usa el Shell.
	 */
	public const PRIORITY = 20;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'template_redirect', array( self::class, 'render' ), self::PRIORITY );
	}

	/**
	 * Whether we paint this request ourselves instead of the theme.
	 *
	 * @return bool
	 */
	public static function takes_over(): bool {
		return Shell::is_standalone() && Shell::SECTION_PROCEDURE === Shell::current_section();
	}

	/**
	 * Serve the whole document of a procedure page.
	 *
	 * @return void
	 */
	public static function render(): void {
		if ( ! self::takes_over() ) {
			return;
		}
		$m = self::model();
		Assets::enqueue();
		// El título no lo pinta el armazón: en la ficha es el **título del
		// procedimiento** —30px, peso 800, mayúsculas y centrado— y vive
		// dentro de la columna izquierda de la cabecera, no encima de ella.
		Shell::serve( Shell::render( '', '', ProcedureChrome::html( $m ) ) );
	}

	/**
	 * Everything the public view decides before painting.
	 *
	 * @param int $post_id Procedure (0 = the one being viewed).
	 * @return array<string, mixed>
	 */
	public static function model( int $post_id = 0 ): array {
		$post_id = $post_id > 0 ? $post_id : (int) get_queried_object_id();
		$m       = self::empty_model();
		if ( $post_id <= 0 || ProcedurePostType::POST_TYPE !== get_post_type( $post_id ) ) {
			return $m;
		}

		$meta = array();
		foreach ( ProcedureMetaKeys::all() as $key ) {
			$meta[ $key ] = get_post_meta( $post_id, $key, true );
		}
		$state     = ProcedureState::of_post( $post_id );
		$user_id   = get_current_user_id();
		$ownership = is_array( $meta[ ProcedureMetaKeys::OWNERSHIP ] ) ? $meta[ ProcedureMetaKeys::OWNERSHIP ] : array();
		$cartel    = (int) get_post_thumbnail_id( $post_id );
		$correos   = is_array( $meta[ ProcedureMetaKeys::CONTACT_EMAILS ] ) ? $meta[ ProcedureMetaKeys::CONTACT_EMAILS ] : array();
		$plazo     = self::deadline( $meta, $state );
		$cuenta    = $plazo['running'] ? self::countdown( $plazo['ymd'] ) : array();
		$mia       = self::mine( $post_id, $user_id );
		$audiencia = ProcedureMetaKeys::in_list( $meta[ ProcedureMetaKeys::AUDIENCE ], ProcedureMetaKeys::audiences(), ProcedureMetaKeys::AUDIENCE_SCHOOLS );
		$contenido = (string) apply_filters( 'the_content', (string) get_post_field( 'post_content', $post_id ) );

		return array_merge(
			$m,
			array(
				'id'                   => $post_id,
				'title'                => (string) get_the_title( $post_id ),
				'url'                  => (string) get_permalink( $post_id ),
				'content'              => $contenido,
				'tabs'                 => self::tabs( $contenido ),
				'excerpt'              => trim( wp_strip_all_tags( (string) get_post_field( 'post_excerpt', $post_id ) ) ),
				'state'                => $state,
				'state_label'          => ProcedureState::label( $state ),
				'state_icon'           => self::state_icon( $state ),
				'dates'                => DateRange::of( (string) $meta[ ProcedureMetaKeys::OPENS_AT ], (string) $meta[ ProcedureMetaKeys::CLOSES_AT ] ),
				'amend_dates'          => DateRange::of( (string) $meta[ ProcedureMetaKeys::AMEND_OPENS_AT ], (string) $meta[ ProcedureMetaKeys::AMEND_CLOSES_AT ] ),
				// El plazo que corre ahora: qué es, cuándo acaba y cuánto
				// queda. Sin plazo vivo el contador no se pinta (mejora M2), y
				// con el plazo vencido su hueco lo ocupa el aviso (M3).
				'deadline_label'       => $plazo['label'],
				// El mismo día dos veces a propósito: un día suelto se escribe
				// «27 de agosto de 2026», y `DateRange::of()` con un solo
				// argumento escribe «Desde el 27 de agosto de 2026», que es
				// un intervalo abierto y aquí diría otra cosa.
				'deadline_text'        => DateRange::of( $plazo['ymd'], $plazo['ymd'] ),
				'deadline_iso'         => (string) ( $cuenta['iso'] ?? '' ),
				'countdown'            => $cuenta,
				'expired'              => ! $plazo['running'] && '' !== $plazo['ymd'],
				'areas'                => self::terms( $post_id, ProcedureTaxonomies::AREA ),
				'courses'              => self::terms( $post_id, ProcedureTaxonomies::COURSE ),
				'audience'             => (string) ( ProcedureMetaKeys::audiences()[ $audiencia ] ?? '' ),
				'ownership'            => ProcedureChrome::ownership_label( $ownership ),
				'requires_coordinator' => (bool) $meta[ ProcedureMetaKeys::REQUIRES_COORDINATOR ],
				// `contact_email` es el primero de la lista: la caja resumen
				// pinta uno solo, el que atiende.
				'contact_emails'       => $correos,
				'contact_email'        => (string) ( $correos[0] ?? '' ),
				'header_color'         => ProcedureMetaKeys::header_hex( $meta[ ProcedureMetaKeys::HEADER_COLOR ] ),
				'links'                => self::links( $meta ),
				'resolution_url'       => trim( (string) $meta[ ProcedureMetaKeys::RESOLUTION_URL ] ),
				'image'                => $cartel > 0 ? (string) wp_get_attachment_image_url( $cartel, 'large' ) : '',
				'image_alt'            => $cartel > 0 ? (string) get_post_meta( $cartel, '_wp_attachment_image_alt', true ) : '',
				'mine'                 => $mia,
				'action'               => self::action( $post_id, $state, $user_id, $mia ),
				'manage_url'           => self::manage_url( $post_id, $user_id ),
			)
		);
	}

	/**
	 * The model of a page that is not a procedure: nothing to say.
	 *
	 * @return array<string, mixed>
	 */
	private static function empty_model(): array {
		return array(
			'id'                   => 0,
			'title'                => '',
			'url'                  => '',
			'content'              => '',
			'tabs'                 => array(),
			'excerpt'              => '',
			'state'                => '',
			'state_label'          => '',
			'state_icon'           => '',
			'dates'                => '',
			'amend_dates'          => '',
			'deadline_label'       => '',
			'deadline_text'        => '',
			'deadline_iso'         => '',
			'countdown'            => array(),
			'expired'              => false,
			'areas'                => '',
			'courses'              => '',
			'audience'             => '',
			'ownership'            => '',
			'requires_coordinator' => false,
			'contact_emails'       => array(),
			'contact_email'        => '',
			'header_color'         => ProcedureMetaKeys::header_hex( '' ),
			'links'                => array(),
			'resolution_url'       => '',
			'image'                => '',
			'image_alt'            => '',
			'mine'                 => array(),
			'action'               => self::no_action(),
			'manage_url'           => '',
		);
	}

	/**
	 * The icon of each derived state (risk R1 of the visual system).
	 *
	 * La leyenda del sistema que se sustituye tiene **ocho** cruces porque su
	 * estado se etiquetaba a mano y podía contradecir a las fechas. Aquí el
	 * estado se deriva (ADR-0013), así que son **siete** y ninguno puede
	 * contradecirse: esta es la correspondencia.
	 *
	 * @param string $state One of ProcedureMetaKeys::states().
	 * @return string Material Symbols ligature; empty when the state is not ours.
	 */
	private static function state_icon( string $state ): string {
		$iconos = array(
			ProcedureMetaKeys::STATE_OPEN      => 'task_alt',
			ProcedureMetaKeys::STATE_CLOSED    => 'cancel',
			ProcedureMetaKeys::STATE_AMENDMENT => 'contact_support',
			ProcedureMetaKeys::STATE_RESOLVED  => 'view_list',
			ProcedureMetaKeys::STATE_UPCOMING  => 'schedule',
			ProcedureMetaKeys::STATE_DRAFT     => 'reset_brightness',
			ProcedureMetaKeys::STATE_ARCHIVED  => 'tonality',
		);
		return (string) ( $iconos[ $state ] ?? '' );
	}

	/**
	 * The deadline that matters right now, and whether it is still running.
	 *
	 * El contador cuenta **una** cosa, y hay que decir cuál (mejora M2): con
	 * la subsanación en marcha cuenta al cierre de la subsanación y en plazo
	 * de solicitud, al cierre de la solicitud. En cualquier otro estado no hay
	 * plazo vivo y no se pinta contador; con el plazo cerrado sí hay fecha,
	 * pero ya pasó, y su hueco lo ocupa el aviso de vencimiento (M3).
	 *
	 * @param array<string, mixed> $meta  Meta values.
	 * @param string               $state Derived state.
	 * @return array{ymd:string, label:string, running:bool}
	 */
	private static function deadline( array $meta, string $state ): array {
		$ultimo = static function ( $fin, $principio ): string {
			$fin = trim( (string) $fin );
			return '' !== $fin ? $fin : trim( (string) $principio );
		};

		if ( ProcedureMetaKeys::STATE_AMENDMENT === $state ) {
			return array(
				'ymd'     => $ultimo( $meta[ ProcedureMetaKeys::AMEND_CLOSES_AT ], $meta[ ProcedureMetaKeys::AMEND_OPENS_AT ] ),
				'label'   => 'subsanación',
				'running' => true,
			);
		}
		if ( ProcedureMetaKeys::STATE_OPEN === $state || ProcedureMetaKeys::STATE_CLOSED === $state ) {
			return array(
				'ymd'     => $ultimo( $meta[ ProcedureMetaKeys::CLOSES_AT ], $meta[ ProcedureMetaKeys::OPENS_AT ] ),
				'label'   => 'inscripción',
				'running' => ProcedureMetaKeys::STATE_OPEN === $state,
			);
		}
		return array(
			'ymd'     => '',
			'label'   => '',
			'running' => false,
		);
	}

	/**
	 * What is left of a deadline, already written, plus the instant in ISO 8601.
	 *
	 * El plazo se guarda como día (`Y-m-d`) porque el estado se compara por
	 * día, y el contador necesita un instante: el **último día a las 23:59:00**
	 * en la zona del sitio (riesgo R10). El contador no puede cambiar el
	 * estado: solo lo cuenta.
	 *
	 * Las cifras salen ya calculadas del servidor para que el componente se
	 * vea bien sin guion; con guion, `prc-ficha.js` las reescribe cada segundo.
	 *
	 * @param string $ymd Last day of the deadline, Y-m-d.
	 * @return array{iso:string, dias:string, horas:string, minutos:string, segundos:string}|array{} Empty when it is not a real date.
	 */
	private static function countdown( string $ymd ): array {
		$ymd = trim( $ymd );
		if ( 1 !== preg_match( '/^\d{4}-\d{1,2}-\d{1,2}$/', $ymd ) ) {
			return array();
		}
		$fin = \DateTimeImmutable::createFromFormat( 'Y-n-j H:i:s', $ymd . ' 23:59:00', wp_timezone() );
		if ( false === $fin ) {
			return array();
		}

		$queda = max( 0, $fin->getTimestamp() - time() );
		return array(
			'iso'      => $fin->format( 'c' ),
			// Los días con tres dígitos y el resto con dos, como el original.
			'dias'     => sprintf( '%03d', intdiv( $queda, DAY_IN_SECONDS ) ),
			'horas'    => sprintf( '%02d', intdiv( $queda % DAY_IN_SECONDS, HOUR_IN_SECONDS ) ),
			'minutos'  => sprintf( '%02d', intdiv( $queda % HOUR_IN_SECONDS, MINUTE_IN_SECONDS ) ),
			'segundos' => sprintf( '%02d', $queda % MINUTE_IN_SECONDS ),
		);
	}

	/**
	 * Whether the school of whoever is looking already applied, and how it went.
	 *
	 * Es el bloque de la caja resumen que ahorra el viaje «portada → ficha →
	 * mis solicitudes» (mejora M1). Solo tiene sentido para quien ha entrado
	 * con un centro detrás: para todos los demás la caja no lo pinta.
	 *
	 * @param int $post_id Procedure.
	 * @param int $user_id Who is looking (0 = nobody).
	 * @return array{has:bool, state:string, label:string, date:string, iso:string, url:string}|array{} Empty when there is no school to talk about.
	 */
	private static function mine( int $post_id, int $user_id ): array {
		if ( $user_id <= 0 || ! user_can( $user_id, ProcedureAccess::CAP_APPLY ) || ! class_exists( Applications::class ) ) {
			return array();
		}
		$code = CentreScope::code_for( $user_id );
		if ( '' === $code ) {
			return array();
		}

		$url   = Shell::url( 'apply', array( Shell::ARG_PROCEDURE => $post_id ) );
		$vacia = array(
			'has'   => false,
			'state' => '',
			'label' => '',
			'date'  => '',
			'iso'   => '',
			'url'   => $url,
		);

		$solicitud = (int) Applications::find( $post_id, $code );
		if ( $solicitud <= 0 ) {
			return $vacia;
		}

		$estado = (string) get_post_meta( $solicitud, ApplicationMetaKeys::REVIEW_STATE, true );
		return array_merge(
			$vacia,
			array(
				'has'   => true,
				'state' => $estado,
				'label' => (string) ( ApplicationMetaKeys::review_states()[ $estado ] ?? '' ),
				'date'  => (string) get_the_date( 'd-m-Y', $solicitud ),
				'iso'   => (string) get_post_time( 'Y-m-d', false, $solicitud ),
			)
		);
	}

	/**
	 * The description, cut into the panels of the content tabs.
	 *
	 * El original tiene tres pestañas fijas —«Objetivos:», «Dirigido a:»,
	 * «Calendario:»— que allí son tres campos del constructor de páginas. Aquí
	 * la descripción es una sola y las pestañas salen de sus `h2`: cada
	 * encabezado es una pestaña y lo que va debajo, su panel. Lo que hay antes
	 * del primer `h2` es el primero, con el rótulo del original.
	 *
	 * Una pestaña sin contenido no se pinta (§3.3), y con una sola no se pinta
	 * la barra: es una ficha larga y legible, que es lo que hace sin guion.
	 *
	 * @param string $html The description, already through `the_content`.
	 * @return array<int, array{id:string, label:string, html:string}>
	 */
	private static function tabs( string $html ): array {
		$trozos = preg_split( '#<h2\b[^>]*>(.*?)</h2>#is', trim( $html ), -1, PREG_SPLIT_DELIM_CAPTURE );
		if ( ! is_array( $trozos ) || array() === $trozos ) {
			return array();
		}

		$paneles = array();
		$intro   = trim( (string) array_shift( $trozos ) );
		if ( '' !== trim( wp_strip_all_tags( $intro ) ) ) {
			$paneles[] = array(
				'label' => 'Objetivos:',
				'html'  => $intro,
			);
		}
		while ( array() !== $trozos ) {
			$rotulo = trim( wp_strip_all_tags( (string) array_shift( $trozos ) ) );
			$cuerpo = trim( (string) array_shift( $trozos ) );
			if ( '' === $rotulo || '' === trim( wp_strip_all_tags( $cuerpo ) ) ) {
				continue;
			}
			$paneles[] = array(
				'label' => $rotulo,
				'html'  => $cuerpo,
			);
		}

		foreach ( array_keys( $paneles ) as $i ) {
			$paneles[ $i ]['id'] = 'prc-panel-' . ( $i + 1 );
		}
		return $paneles;
	}

	/**
	 * No button at all, in the shape the view expects.
	 *
	 * @return array{label:string, url:string, icon:string, primary:bool}
	 */
	private static function no_action(): array {
		return array(
			'label'   => '',
			'url'     => '',
			'icon'    => '',
			'primary' => false,
		);
	}

	/**
	 * The one button of a school: apply, or open what it already sent.
	 *
	 * Quién puede lo dice {@see ProcedureAccess::can_apply()}; cuándo, el
	 * estado. Con una solicitud ya presentada el botón se sigue viendo en
	 * cualquier estado (ADR-0018), pero **dice lo que va a pasar al pulsarlo**
	 * (mejora M4): con el plazo abierto se edita y con el plazo cerrado se
	 * consulta, y entonces deja de ser el botón principal de la pantalla. Sin
	 * sesión, «Solicitar» lleva al acceso y vuelve al formulario.
	 *
	 * @param int                  $post_id Procedure.
	 * @param string               $state   Derived state.
	 * @param int                  $user_id Who is looking (0 = nobody).
	 * @param array<string, mixed> $mine    What mine() found out about this school.
	 * @return array{label:string, url:string, icon:string, primary:bool}
	 */
	private static function action( int $post_id, string $state, int $user_id, array $mine ): array {
		$vacio = self::no_action();
		$apply = Shell::url( 'apply', array( Shell::ARG_PROCEDURE => $post_id ) );
		if ( '' === $apply ) {
			return $vacio;
		}
		$abierto = ProcedureState::accepts_applications( $state );

		if ( $user_id <= 0 ) {
			return $abierto ? array(
				'label'   => 'Solicitar',
				'url'     => wp_login_url( $apply ),
				'icon'    => 'edit_note',
				'primary' => true,
			) : $vacio;
		}
		if ( ! user_can( $user_id, ProcedureAccess::CAP_APPLY ) ) {
			return $vacio;
		}

		if ( ! empty( $mine['has'] ) ) {
			return $abierto ? array(
				'label'   => 'Editar mi solicitud',
				'url'     => $apply,
				'icon'    => 'edit_note',
				'primary' => true,
			) : array(
				'label'   => 'Ver mi solicitud',
				'url'     => $apply,
				'icon'    => 'visibility',
				'primary' => false,
			);
		}
		if ( $abierto && ProcedureAccess::can_apply( $user_id, $post_id ) ) {
			return array(
				'label'   => 'Solicitar',
				'url'     => $apply,
				'icon'    => 'edit_note',
				'primary' => true,
			);
		}
		return $vacio;
	}

	/**
	 * Link to the editor of this procedure, for whoever may open it.
	 *
	 * `can_open()` y no `can_edit()`: un procedimiento histórico se sigue
	 * consultando y exportando desde su taller (ADR-0023), y esconderle el
	 * botón a quien lo convocó es dejarlo sin la puerta.
	 *
	 * @param int $post_id Procedure.
	 * @param int $user_id Who is looking.
	 * @return string Empty when this person cannot open it, or the page is not created yet.
	 */
	private static function manage_url( int $post_id, int $user_id ): string {
		if ( ! ProcedureAccess::can_open( $user_id, $post_id ) ) {
			return '';
		}
		return Shell::url( 'editor', array( Shell::ARG_PROCEDURE => $post_id ) );
	}

	/**
	 * The resolution and the lists, only the ones that exist (ADR-0021).
	 *
	 * Cada uno con su icono, que es el que la caja resumen pone delante.
	 *
	 * @param array<string, mixed> $meta Meta values.
	 * @return array<int, array{label:string, url:string, icon:string}>
	 */
	private static function links( array $meta ): array {
		$rotulos = array(
			ProcedureMetaKeys::RESOLUTION_URL       => array( 'Resolución del procedimiento', 'quick_reference_all' ),
			ProcedureMetaKeys::PROVISIONAL_LIST_URL => array( 'Listado provisional de admitidos', 'view_list' ),
			ProcedureMetaKeys::FINAL_LIST_URL       => array( 'Listado definitivo de admitidos', 'view_list' ),
		);
		$out     = array();
		foreach ( $rotulos as $key => $rotulo ) {
			$url = trim( (string) $meta[ $key ] );
			if ( '' !== $url ) {
				$out[] = array(
					'label' => $rotulo[0],
					'url'   => $url,
					'icon'  => $rotulo[1],
				);
			}
		}
		return $out;
	}

	/**
	 * Term names of one taxonomy on one post, written out.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $taxonomy Taxonomy.
	 * @return string Empty when there are none.
	 */
	private static function terms( int $post_id, string $taxonomy ): string {
		$terms = get_the_terms( $post_id, $taxonomy );
		if ( ! is_array( $terms ) ) {
			return '';
		}
		return implode( ' · ', wp_list_pluck( $terms, 'name' ) );
	}
}
