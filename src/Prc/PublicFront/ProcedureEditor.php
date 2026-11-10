<?php
/**
 * The workshop of one procedure: its data, questions, links, applications
 * and publication.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\Access\ProcedureAccess;
use Prc\Domain\CentreCatalog;
use Prc\Domain\CentreEligibility;
use Prc\Domain\ProcedureInput;
use Prc\Domain\ProcedureQuestions;
use Prc\Domain\QuestionChanges;
use Prc\Domain\QuestionGrid;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\Meta\ProcedureMetaRegistration;
use Prc\PostType\ProcedurePostType;
use Prc\PublicFront\View\ProcedureEditorView;
use Prc\Taxonomy\ProcedureTaxonomies;

/**
 * Shortcode [prc_editor]: el taller de un procedimiento.
 *
 * Hoy dar de alta una convocatoria es un formulario de más de cien campos del
 * gestor de formularios, y gestionar sus solicitudes, unas vistas filtradas
 * por parámetros de la URL. Aquí es una pantalla con cinco pestañas
 * (`?panel=datos|preguntas|enlaces|solicitudes|publicacion`) y cada una
 * enseña lo suyo y nada más.
 *
 * Todo lo que muta va por POST con su nonce propio y con
 * {@see ProcedureAccess} comprobado, y vuelve a la misma pestaña con el aviso
 * en la URL (`?aviso=`): nunca se muta en GET. Un envío rechazado no
 * redirige: la misma petición repinta el formulario con lo tecleado y el
 * motivo, para que no se pierdan veinte campos por una fecha mal escrita.
 */
final class ProcedureEditor {

	public const SHORTCODE = 'prc_editor';

	/**
	 * Query arg carrying the procedure this workshop is about: the shell's.
	 */
	public const ARG_PROCEDURE = Shell::ARG_PROCEDURE;

	/**
	 * Query arg carrying the inner tab.
	 */
	public const ARG_PANEL = 'panel';

	/**
	 * Query arg carrying what the last action did.
	 */
	public const ARG_NOTICE = 'aviso';

	public const PANEL_DATA         = 'datos';
	public const PANEL_QUESTIONS    = 'preguntas';
	public const PANEL_LINKS        = 'enlaces';
	public const PANEL_APPLICATIONS = 'solicitudes';
	public const PANEL_DELIVERIES   = 'entregas';
	public const PANEL_PUBLISH      = 'publicacion';
	public const PANEL_NOTICES      = 'avisos';
	public const PANEL_CODE         = 'codigo';
	public const PANEL_PEOPLE       = 'personas';

	public const OP_REVIEW              = 'review';
	public const OP_BULK_REVIEW         = 'bulk_review';
	public const OP_EXPORT              = 'export';
	public const OP_PUBLISH             = 'publish';
	public const OP_UNPUBLISH           = 'unpublish';
	public const OP_TOGGLE_APPLICATIONS = 'toggle_applications';
	public const OP_ARCHIVE             = 'archive';
	public const OP_UNARCHIVE           = 'unarchive';
	public const OP_DELIVERY_DATES      = 'delivery_dates';
	public const OP_CUSTOM_CODE         = 'custom_code';
	public const OP_NOTIFY              = 'notify';
	public const OP_MOVE_QUESTION       = 'move_question';
	public const OP_PEOPLE_SPEC         = 'people_spec';
	public const FIELD_DELIVERY_CLOSES  = 'prc_delivery_closes';

	/**
	 * Hidden field naming the operation a POST asks for.
	 */
	public const FIELD_DO = 'prc_do';

	/**
	 * Hidden field with the procedure a POST is about.
	 */
	public const FIELD_PROCEDURE = 'prc_procedure';

	/**
	 * Hidden field with the application a review is about.
	 */
	public const FIELD_ROW = 'prc_row';

	/**
	 * Review state chosen for one application.
	 */
	public const FIELD_STATE = 'prc_state';

	/**
	 * Review note of one application.
	 */
	public const FIELD_NOTE = 'prc_note';

	public const FIELD_TITLE       = 'prc_title';
	public const FIELD_DESCRIPTION = 'prc_description';
	public const FIELD_AREA        = 'prc_area_term';
	public const FIELD_COURSE      = 'prc_course_term';

	/**
	 * Radio that says whether the application asks for none, one or several coordinators.
	 *
	 * No se guarda: lo que se guarda es cuántas, en `prc_coordinator_count`.
	 */
	public const FIELD_COORDINATOR_KIND = 'prc_coordinator_kind';

	/**
	 * Prefix of the parallel fields of the question rows.
	 */
	public const FIELD_Q = 'prc_q_';

	/**
	 * Button of «subir» or «bajar»: `q3:up`, `q3:down` (ADR-0040).
	 */
	public const FIELD_Q_MOVE = 'prc_q_move';

	/**
	 * Checkboxes of the «Avisos» panel: the mail events left switched on.
	 *
	 * Lo que se guarda es lo contrario, los apagados, en `prc_notify`: así un
	 * procedimiento que nunca pasó por este panel los tiene todos.
	 */
	public const FIELD_NOTIFY = 'prc_notify_on';

	/**
	 * Fields of the export dialog (ADR-0039).
	 *
	 * «Todas las columnas», las columnas una a una, la forma (una fila por
	 * solicitud o por persona), el formato (CSV o fichero de alta de
	 * usuarios) y, para ese fichero, quiénes salen.
	 */
	public const FIELD_EXPORT_ALL     = 'prc_export_all';
	public const FIELD_EXPORT_COLUMNS = 'prc_export_columns';
	public const FIELD_EXPORT_SHAPE   = 'prc_export_shape';
	public const FIELD_EXPORT_FORMAT  = 'prc_export_format';
	public const FIELD_EXPORT_WHO     = 'prc_export_who';

	/**
	 * Every operation this screen accepts by POST.
	 *
	 * @var string[]
	 */
	private const OPS = array(
		self::PANEL_DATA,
		self::PANEL_QUESTIONS,
		self::OP_MOVE_QUESTION,
		self::OP_DELIVERY_DATES,
		self::OP_CUSTOM_CODE,
		self::OP_NOTIFY,
		self::OP_PEOPLE_SPEC,
		self::PANEL_LINKS,
		self::OP_REVIEW,
		self::OP_BULK_REVIEW,
		self::OP_EXPORT,
		self::OP_PUBLISH,
		self::OP_UNPUBLISH,
		self::OP_TOGGLE_APPLICATIONS,
		self::OP_ARCHIVE,
		self::OP_UNARCHIVE,
	);

	/**
	 * What each notice code in the URL says.
	 *
	 * @var array<string, string>
	 */
	private const DONE = array(
		'creado'       => 'Procedimiento creado, en borrador: no se ve fuera hasta que lo publique. Añada sus preguntas y sus enlaces, y publíquelo cuando esté listo.',
		'datos'        => 'Datos del procedimiento guardados.',
		'preguntas'    => 'Preguntas guardadas.',
		'ordenadas'    => 'Orden de las preguntas guardado.',
		'entregas'     => 'Plazos de entrega actualizados.',
		'enlaces'      => 'Enlaces guardados.',
		'codigo'       => 'Código adicional guardado.',
		'avisos'       => 'Avisos por correo guardados.',
		'personas'     => 'Lista de personas guardada.',
		'revisada'     => 'Solicitud revisada.',
		'publicado'    => 'Procedimiento publicado: ya se ve en la portada y los centros pueden solicitar cuando abra el plazo.',
		'borrador'     => 'Procedimiento devuelto a borrador: deja de verse públicamente y no admite solicitudes.',
		'cerradas'     => 'Inscripción cerrada. Los plazos de subsanación y de entrega documental '
			. 'se mantienen como estaban.',
		'reabiertas'   => 'La inscripción vuelve a regirse por las fechas configuradas.',
		'archivado'    => 'Procedimiento marcado como histórico. Ya no se edita ni se gestionan sus solicitudes; la ficha pública se sigue viendo igual. Para volver a abrirlo hay que pedírselo a quien administre el aplicativo.',
		'desarchivado' => 'Procedimiento desmarcado: su ámbito vuelve a poder editarlo.',
	);

	/**
	 * Why nobody without capability and ámbito gets to create a procedure.
	 *
	 * La misma condición —`can_manage_procedures()`, que pide la capacidad y
	 * al menos un ámbito— se cuenta con las mismas palabras la pinte quien la
	 * pinte: el envío rechazado y la pantalla de «nuevo procedimiento».
	 *
	 * @var string
	 */
	private const CANNOT_CREATE = 'Su perfil no puede crear procedimientos: hace falta gestionar procedimientos y tener un ámbito asignado. Pídalo a quien administre el aplicativo.';

	/**
	 * Why the last submit did not go through.
	 *
	 * Un envío rechazado no redirige: la misma petición vuelve a pintar la
	 * pantalla, así que el motivo no tiene que sobrevivir a nada.
	 *
	 * @var string
	 */
	private static $rejected = '';

	/**
	 * Register the shortcode and the POST handler.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_code_editor' ), 20 );
		// Después de que el CPT y sus capacidades estén registrados (init 10 y
		// 11), y antes de que se pinte nada: aquí todavía se puede redirigir.
		add_action( 'init', array( self::class, 'handle' ), 20 );
	}

	/** Enqueue WordPress CodeMirror before the page head, only on this tab. */
	public static function enqueue_code_editor(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- lectura de la pestaña; mutar lleva su nonce.
		if ( 'editor' !== Shell::current_section() || self::PANEL_CODE !== sanitize_key( wp_unslash( (string) ( $_GET[ self::ARG_PANEL ] ?? '' ) ) ) ) {
			return;
		}
		$procedure_id = absint( wp_unslash( $_GET[ self::ARG_PROCEDURE ] ?? 0 ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$user_id = get_current_user_id();
		if ( ProcedureAccess::can_edit( $user_id, $procedure_id ) ) {
			$settings = wp_enqueue_code_editor( array( 'type' => 'text/css' ) );
			if ( is_array( $settings ) ) {
				wp_add_inline_script( 'code-editor', sprintf( 'jQuery(function(){wp.codeEditor.initialize("prc-custom-css",%s);});', wp_json_encode( $settings, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES ) ), 'after' );
			}
		}
		if ( ProcedureAccess::can_edit_custom_js( $user_id, $procedure_id ) ) {
			$settings = wp_enqueue_code_editor( array( 'type' => 'text/javascript' ) );
			if ( is_array( $settings ) ) {
				wp_add_inline_script( 'code-editor', sprintf( 'jQuery(function(){wp.codeEditor.initialize("prc-custom-js",%s);});', wp_json_encode( $settings, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES ) ), 'after' );
			}
		}
	}

	/*
	 * -----------------------------------------------------------------------
	 * Direcciones
	 * -----------------------------------------------------------------------
	 */

	/**
	 * URL of the workshop of one procedure, on one of its tabs.
	 *
	 * @param int                  $procedure_id Procedure post ID; 0 for the create screen.
	 * @param string               $panel        Tab slug; empty for the default one.
	 * @param array<string, mixed> $args         Extra query arguments.
	 * @return string Empty when there is no page for the screen.
	 */
	public static function url( int $procedure_id, string $panel = '', array $args = array() ): string {
		if ( $procedure_id > 0 ) {
			$args[ self::ARG_PROCEDURE ] = $procedure_id;
		}
		if ( '' !== $panel ) {
			$args[ self::ARG_PANEL ] = $panel;
		}
		return Shell::url( 'editor', $args );
	}

	/**
	 * The five inner tabs, with where each one goes.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return array<string, array{label:string, url:string, count:?int}>
	 */
	public static function panels( int $procedure_id ): array {
		$rotulos = array(
			self::PANEL_DATA         => 'Datos',
			self::PANEL_QUESTIONS    => 'Preguntas',
			self::PANEL_LINKS        => 'Enlaces',
			self::PANEL_APPLICATIONS => 'Solicitudes',
			self::PANEL_DELIVERIES   => 'Entregas',
			self::PANEL_PEOPLE       => 'Personas',
			self::PANEL_PUBLISH      => 'Publicación',
			self::PANEL_NOTICES      => 'Avisos',
			self::PANEL_CODE         => 'Código',
		);
		$cuentas = array(
			self::PANEL_QUESTIONS    => count( Applications::questions( $procedure_id ) ),
			self::PANEL_APPLICATIONS => Applications::count( $procedure_id ),
			self::PANEL_DELIVERIES   => count( array_filter( Applications::questions( $procedure_id ), array( ProcedureQuestions::class, 'is_delivery' ) ) ),
		);

		$out = array();
		foreach ( $rotulos as $slug => $rotulo ) {
			$out[ $slug ] = array(
				'label' => $rotulo,
				'url'   => self::url( $procedure_id, $slug ),
				'count' => $cuentas[ $slug ] ?? null,
			);
		}
		return $out;
	}

	/**
	 * Name of the nonce field of one operation.
	 *
	 * Un nonce por acción y, en la revisión, por solicitud: así cada botón de
	 * la tabla lleva el suyo.
	 *
	 * @param string $op  Operation.
	 * @param int    $row Application, 0 outside the review.
	 * @return string
	 */
	public static function nonce_name( string $op, int $row = 0 ): string {
		return 'prc_nonce_' . $op . '_' . $row;
	}

	/**
	 * Nonce action of one operation.
	 *
	 * @param string $op Operation.
	 * @return string
	 */
	public static function nonce_action( string $op ): string {
		return 'prc_ed_' . $op;
	}

	/*
	 * -----------------------------------------------------------------------
	 * Las mutaciones
	 * -----------------------------------------------------------------------
	 */

	/**
	 * Apply what a POST asked for, then go back to the same tab.
	 *
	 * Se mira `$_POST` y nada más: un `GET` no lo rellena, así que no hay
	 * forma de disparar esto desde un enlace pegado en un correo.
	 *
	 * @return void
	 */
	public static function handle(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- se comprueba en verify(), en cuanto se sabe qué acción es.
		$op           = sanitize_key( wp_unslash( (string) ( $_POST[ self::FIELD_DO ] ?? '' ) ) );
		$procedure_id = absint( wp_unslash( $_POST[ self::FIELD_PROCEDURE ] ?? 0 ) );
		$row_id       = absint( wp_unslash( $_POST[ self::FIELD_ROW ] ?? 0 ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		if ( ! in_array( $op, self::OPS, true ) || ! is_user_logged_in() ) {
			return;
		}
		if ( ! self::verify( $op, self::OP_REVIEW === $op ? $row_id : 0 ) ) {
			return;
		}

		$user_id = get_current_user_id();

		// Crear va lo primero, antes de todo lo que da por hecho que el
		// procedimiento existe.
		if ( self::PANEL_DATA === $op && $procedure_id <= 0 ) {
			self::create( $user_id );
			return;
		}
		if ( ProcedurePostType::POST_TYPE !== get_post_type( $procedure_id ) ) {
			self::$rejected = 'Ese procedimiento ya no existe.';
			return;
		}
		if ( self::OP_CUSTOM_CODE === $op ) {
			EditLock::require_available( $procedure_id );
			if ( ! ProcedureAccess::can_edit( $user_id, $procedure_id ) ) {
				self::$rejected = ProcedureAccess::why_not_editable( $user_id, $procedure_id );
				return;
			}
			// phpcs:disable WordPress.Security.NonceVerification.Missing -- verify() ya comprobó el nonce.
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitize_custom_code() procesa el valor tras wp_unslash().
			$css = isset( $_POST['prc_custom_css'] ) && is_string( $_POST['prc_custom_css'] ) ? ProcedureMetaRegistration::sanitize_custom_code( wp_unslash( $_POST['prc_custom_css'] ) ) : null;
			if ( ! is_string( $css ) ) {
				self::$rejected = 'El CSS no tiene un formato válido.';
				return;
			}
			update_post_meta( $procedure_id, ProcedureMetaKeys::CUSTOM_CSS, $css );
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- solo se escribe con capability.
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitize_custom_code() procesa el valor tras wp_unslash().
			$js = isset( $_POST['prc_custom_js'] ) && is_string( $_POST['prc_custom_js'] ) ? ProcedureMetaRegistration::sanitize_custom_code( wp_unslash( $_POST['prc_custom_js'] ) ) : null;
			// phpcs:enable WordPress.Security.NonceVerification.Missing
			if ( is_string( $js ) && ProcedureAccess::can_edit_custom_js( $user_id, $procedure_id ) ) {
				update_post_meta( $procedure_id, ProcedureMetaKeys::CUSTOM_JS, $js );
			}
			Shell::leave( self::url( $procedure_id, self::PANEL_CODE, array( self::ARG_NOTICE => 'codigo' ) ) );
			return;
		}
		// Lo que se guarda en cualquier estado mientras el procedimiento se
		// pueda editar: los avisos, el cierre de las entregas y la lista de
		// personas, que tiene su propia ventana (ADR-0041).
		$sueltas = array(
			self::OP_NOTIFY         => 'save_notify',
			self::OP_DELIVERY_DATES => 'save_delivery_dates',
			self::OP_PEOPLE_SPEC    => 'save_people_spec',
		);
		if ( isset( $sueltas[ $op ] ) ) {
			EditLock::require_available( $procedure_id );
			if ( ! ProcedureAccess::can_edit( $user_id, $procedure_id ) ) {
				self::$rejected = ProcedureAccess::why_not_editable( $user_id, $procedure_id );
				return;
			}
			call_user_func( array( self::class, $sueltas[ $op ] ), $procedure_id );
			return;
		}

		if ( in_array( $op, array( self::OP_EXPORT, self::OP_REVIEW, self::OP_BULK_REVIEW ), true ) ) {
			// Gestionar solicitudes pide su capacidad, no la de editar: es otra
			// cosa, y no toca el procedimiento, así que tampoco pide su bloqueo.
			if ( ! ProcedureAccess::can_review( $user_id, $procedure_id ) ) {
				self::$rejected = 'Su perfil no gestiona las solicitudes de este procedimiento.';
				return;
			}
			$solicitudes = array(
				self::OP_EXPORT      => 'export',
				self::OP_REVIEW      => 'review',
				self::OP_BULK_REVIEW => 'bulk_review',
			);
			call_user_func( array( self::class, $solicitudes[ $op ] ), $procedure_id, self::OP_REVIEW === $op ? $row_id : $user_id );
			return;
		}

		// Antes de escribir una sola meta, un término o un estado: si otra
		// persona tiene abierto el procedimiento, este envío trae la pantalla
		// de antes y pisaría lo que esté escribiendo. Responde 409 y no vuelve.
		EditLock::require_available( $procedure_id );

		// La marca de histórico va antes de la comprobación de edición: en
		// cuanto está puesta `can_edit()` dice que no, y desmarcar sería imposible.
		if ( self::OP_ARCHIVE === $op || self::OP_UNARCHIVE === $op ) {
			self::save_archived( $op, $procedure_id, $user_id );
			return;
		}

		if ( ! ProcedureAccess::can_edit( $user_id, $procedure_id ) ) {
			self::$rejected = ProcedureAccess::why_not_editable( $user_id, $procedure_id );
			return;
		}

		if ( self::OP_PUBLISH === $op ) {
			self::publish( $procedure_id, $user_id );
			return;
		}
		if ( self::OP_UNPUBLISH === $op ) {
			self::unpublish( $procedure_id, $user_id );
			return;
		}
		if ( self::OP_TOGGLE_APPLICATIONS === $op ) {
			self::toggle_applications( $procedure_id, $user_id );
			return;
		}
		if ( self::PANEL_DATA === $op ) {
			self::save_data( $procedure_id, $user_id );
			return;
		}
		if ( self::PANEL_QUESTIONS === $op || self::OP_MOVE_QUESTION === $op ) {
			if ( ! self::allows_group( $user_id, $procedure_id, ProcedureState::GROUP_QUESTIONS ) ) {
				return;
			}
			self::PANEL_QUESTIONS === $op ? self::save_questions( $procedure_id ) : self::move_question( $procedure_id );
			return;
		}
		if ( ! self::allows_group( $user_id, $procedure_id, ProcedureState::GROUP_LINKS ) ) {
			return;
		}
		self::save_links( $procedure_id );
	}

	/**
	 * Whether the state the procedure is in today still lets that group be written.
	 *
	 * Esta es la capa que protege: la pantalla pinta apagado lo que el estado
	 * cierra, pero quien arme el POST a mano se para aquí, antes de escribir.
	 *
	 * @param int    $user_id      Who is asking.
	 * @param int    $procedure_id Procedure post ID.
	 * @param string $group        One of {@see ProcedureState::groups()}.
	 * @return bool
	 */
	private static function allows_group( int $user_id, int $procedure_id, string $group ): bool {
		if ( ProcedureAccess::can_edit_group( $user_id, $procedure_id, $group ) ) {
			return true;
		}
		self::$rejected = ProcedureAccess::why_not_editable( $user_id, $procedure_id, $group );
		return false;
	}

	/**
	 * Whether the POST carries the nonce of this very operation.
	 *
	 * @param string $op  Operation.
	 * @param int    $row Application, for the review.
	 * @return bool
	 */
	private static function verify( string $op, int $row ): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- esto es la comprobación del nonce.
		$valor = sanitize_text_field( wp_unslash( (string) ( $_POST[ self::nonce_name( $op, $row ) ] ?? '' ) ) );
		return false !== wp_verify_nonce( $valor, self::nonce_action( $op ) );
	}

	/**
	 * Create a brand new procedure from the «Datos» form, then open its workshop.
	 *
	 * Nace en **borrador**: un procedimiento recién creado no tiene preguntas
	 * ni enlaces, y publicarlo por el mero hecho de crearlo es publicar una
	 * ficha a medias. El ámbito se pone ya, para que el acotado sepa de quién es.
	 *
	 * @param int $user_id Who is asking.
	 * @return void
	 */
	private static function create( int $user_id ): void {
		if ( ! ProcedureAccess::can_manage_procedures( $user_id ) ) {
			self::$rejected = self::CANNOT_CREATE;
			return;
		}
		$revisado = self::validate_data( $user_id, self::submitted_data() );
		if ( ! $revisado['ok'] ) {
			self::$rejected = $revisado['area_error'] ?? ProcedureInput::why( $revisado['errors'] );
			return;
		}

		$id = wp_insert_post(
			array(
				'post_type'    => ProcedurePostType::POST_TYPE,
				'post_title'   => wp_slash( (string) $revisado['data']['title'] ),
				'post_content' => wp_slash( (string) $revisado['data']['description'] ),
				'post_status'  => 'draft',
				'post_author'  => $user_id,
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			self::$rejected = 'No se ha podido crear el procedimiento: ' . $id->get_error_message();
			return;
		}
		self::write_data( (int) $id, $revisado['data'] );

		/**
		 * A procedure has just been created, as a draft, from the workshop.
		 *
		 * Una sola vez: guardar el borrador después no la vuelve a emitir. Se
		 * emite con las metas y los ámbitos ya escritos, que es lo que lee el
		 * correo del alta (ADR-0038).
		 *
		 * @param int $id Procedure post ID.
		 */
		do_action( 'prc_procedure_created', (int) $id );
		Shell::leave( self::url( (int) $id, self::PANEL_DATA, array( self::ARG_NOTICE => 'creado' ) ) );
	}

	/**
	 * Save the «Datos» panel, with what the state closes left as it was.
	 *
	 * El panel escribe dos grupos —lo que el procedimiento es y sus fechas— y
	 * hay un estado, `closed`, que deja solo el segundo: ahí el envío se
	 * acepta a medias en vez de rechazarse entero.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @param int $user_id      Who is asking.
	 * @return void
	 */
	private static function save_data( int $procedure_id, int $user_id ): void {
		$datos  = ProcedureAccess::can_edit_group( $user_id, $procedure_id, ProcedureState::GROUP_DATA );
		$fechas = ProcedureAccess::can_edit_group( $user_id, $procedure_id, ProcedureState::GROUP_DATES );
		if ( ! $datos && ! $fechas && ! self::allows_group( $user_id, $procedure_id, ProcedureState::GROUP_DATA ) ) {
			return;
		}

		$crudo = self::gated_data( $procedure_id, $datos, $fechas );
		if ( ! $datos && ! ProcedureAccess::can_edit_all_areas( $user_id ) ) {
			$crudo['areas'] = array_intersect( (array) $crudo['areas'], ProcedureAccess::scope_areas( $user_id ) );
		}
		$revisado = self::validate_data( $user_id, $crudo, $procedure_id );
		if ( ! $revisado['ok'] ) {
			self::$rejected = $revisado['area_error'] ?? ProcedureInput::why( $revisado['errors'] );
			return;
		}
		wp_update_post(
			array(
				'ID'           => $procedure_id,
				'post_title'   => wp_slash( (string) $revisado['data']['title'] ),
				'post_content' => wp_slash( (string) $revisado['data']['description'] ),
			)
		);
		$loses_access = ! ProcedureAccess::can_edit_all_areas( $user_id ) && ! array_intersect( (array) $revisado['data']['areas'], ProcedureAccess::scope_areas( $user_id ) );
		self::write_data( $procedure_id, $revisado['data'] );
		if ( $loses_access ) {
			Shell::leave( add_query_arg( Workspace::VAR_NOTICE, 'retirado', Shell::url( 'workspace' ) ) );
			return;
		}
		Shell::leave( self::url( $procedure_id, self::PANEL_DATA, array( self::ARG_NOTICE => 'datos' ) ) );
	}

	/**
	 * Validate the «Datos» form, with the ámbito rule on top.
	 *
	 * Quien no gestiona todos los ámbitos solo puede poner los suyos: si no,
	 * las casillas son la forma de regalarle el procedimiento a otro ámbito
	 * —y de perderlo de vista— con dos clics. Se comprueban **todas** las
	 * marcadas: basta una ajena para tumbar el envío (ADR-0025).
	 *
	 * @param int                  $user_id Who is asking.
	 * @param array<string, mixed> $crudo   Payload to validate.
	 * @param int                  $post_id Existing procedure, or zero for creation.
	 * @return array{ok:bool, errors:string[], data:array<string, mixed>}
	 */
	private static function validate_data( int $user_id, array $crudo, int $post_id = 0 ): array {
		$areas = ProcedureAccess::resolve_area_assignment( $post_id, (array) ( $crudo['areas'] ?? array() ), $user_id );
		if ( is_wp_error( $areas ) ) {
			$crudo['areas']         = array();
			$revisado               = ProcedureInput::validate( $crudo );
			$revisado['ok']         = false;
			$revisado['errors'][]   = 'areas';
			$revisado['area_error'] = $areas->get_error_message();
			return $revisado;
		}
		$crudo['areas'] = $areas;
		$revisado       = ProcedureInput::validate( $crudo );
		return $revisado;
	}

	/**
	 * Whether this person may file the procedure under those ámbitos.
	 *
	 * Todos, no uno: para gestionarlo después basta pertenecer a uno de ellos
	 * ({@see ProcedureAccess::post_areas()} se cruza por intersección), así
	 * que dejar colar uno ajeno es dejar que dos ámbitos se repartan un
	 * procedimiento sin que el segundo se entere.
	 *
	 * @param int   $user_id  Who is asking.
	 * @param int[] $area_ids Term IDs of prc_area.
	 * @return bool
	 */
	public static function may_set_area( int $user_id, array $area_ids ): bool {
		return ProcedureAccess::may_assign_areas( ProcedureInput::term_ids( $area_ids ), $user_id );
	}

	/**
	 * The centre kinds the «Datos» form starts with.
	 *
	 * Uno nuevo empieza solo con centros: admitir agrupaciones de escuelas
	 * rurales como colectivo es una decisión, no un descuido (ADR-0031).
	 *
	 * @param int $procedure_id Procedure post ID, 0 for a new one.
	 * @return string[]
	 */
	private static function centre_kinds_value( int $procedure_id ): array {
		return $procedure_id > 0 ? ProcedureAccess::centre_kinds( $procedure_id ) : array( ProcedureMetaKeys::KIND_SCHOOL );
	}

	/**
	 * What the «Datos» form sent, in the shape ProcedureInput wants.
	 *
	 * @param int $procedure_id Existing procedure, or 0 on creation.
	 * @return array<string, mixed>
	 */
	private static function submitted_data( int $procedure_id = 0 ): array {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- el nonce lo comprobó handle().
		$descripcion = wp_kses_post( wp_unslash( (string) ( $_POST[ self::FIELD_DESCRIPTION ] ?? '' ) ) );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- lista cerrada: ProcedureInput::ownership() se queda solo con lo que está en ella.
		$titularidad = (array) wp_unslash( $_POST[ ProcedureMetaKeys::OWNERSHIP ] ?? array() );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- lista cerrada: ProcedureInput::centre_kinds() se queda solo con lo que está en ella.
		$clases = (array) wp_unslash( $_POST[ ProcedureMetaKeys::CENTRE_KINDS ] ?? array() );
		$tipo   = isset( $_POST[ self::FIELD_COORDINATOR_KIND ] )
			? sanitize_key( wp_unslash( (string) $_POST[ self::FIELD_COORDINATOR_KIND ] ) )
			: '';
		if ( in_array( $tipo, array( 'none', 'one', 'several' ), true ) ) {
			if ( 'none' === $tipo ) {
				$coordina = '0';
			} elseif ( 'one' === $tipo ) {
				$coordina = '1';
			} else {
				$coordina = (string) max( 2, min( ProcedureMetaKeys::COORDINATOR_COUNT_MAX, (int) self::field( ProcedureMetaKeys::COORDINATOR_COUNT ) ) );
			}
		} else {
			$coordina = isset( $_POST[ ProcedureMetaKeys::COORDINATOR_COUNT ] )
				? self::field( ProcedureMetaKeys::COORDINATOR_COUNT )
				: ( $procedure_id > 0 ? (string) Applications::coordinator_count( $procedure_id ) : '0' );
		}
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- identificadores de término: ProcedureInput::term_ids() se queda solo con los enteros positivos.
		$ambitos = (array) wp_unslash( $_POST[ self::FIELD_AREA ] ?? array() );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- correos: ProcedureInput::emails() se queda solo con los que lo son.
		$correos = (array) wp_unslash( $_POST[ ProcedureMetaKeys::CONTACT_EMAILS ] ?? array() );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- textos: CentreEligibility::values() los limpia y la meta los sanea al guardar.
		$tipos = (array) wp_unslash( $_POST[ ProcedureMetaKeys::CENTRE_TYPES ] ?? array() );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- textos: CentreEligibility::values() los limpia y la meta los sanea al guardar.
		$islas = (array) wp_unslash( $_POST[ ProcedureMetaKeys::ISLANDS ] ?? array() );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$datos                      = array(
			'title'                => self::field( self::FIELD_TITLE ),
			'description'          => $descripcion,
			'areas'                => $ambitos,
			'course'               => (int) self::field( self::FIELD_COURSE ),
			'opens_at'             => self::field( ProcedureMetaKeys::OPENS_AT ),
			'closes_at'            => self::field( ProcedureMetaKeys::CLOSES_AT ),
			'amend_opens_at'       => self::field( ProcedureMetaKeys::AMEND_OPENS_AT ),
			'amend_closes_at'      => self::field( ProcedureMetaKeys::AMEND_CLOSES_AT ),
			'contact_emails'       => $correos,
			'header_color'         => self::field( ProcedureMetaKeys::HEADER_COLOR ),
			'audience'             => self::field( ProcedureMetaKeys::AUDIENCE ),
			'ownership'            => $titularidad,
			'centre_kinds'         => $clases,
			'requires_coordinator' => '' !== $coordina && '0' !== $coordina,
			'commitments'          => self::textarea( ProcedureMetaKeys::COMMITMENTS ),
			'centre_types'         => $tipos,
			'islands'              => $islas,
			'allowed_centres'      => self::textarea( ProcedureMetaKeys::ALLOWED_CENTRES ),
		);
		$datos['coordinator_count'] = $coordina;
		return $datos;
	}

	/**
	 * The «Datos» payload, with the groups this state closes taken from what
	 * is stored instead of from the POST.
	 *
	 * Se sustituye antes de validar y no después: un título que no llega
	 * —la pantalla lo pinta apagado, y un control apagado no se envía—
	 * tumbaría el envío entero por un campo que nadie quería tocar.
	 *
	 * @param int  $procedure_id Procedure post ID.
	 * @param bool $datos        Whether the data group may be written.
	 * @param bool $fechas       Whether the dates group may be written.
	 * @return array<string, mixed>
	 */
	private static function gated_data( int $procedure_id, bool $datos, bool $fechas ): array {
		$crudo = self::submitted_data( $procedure_id );
		if ( ! $fechas ) {
			$plazos = array(
				'opens_at'        => ProcedureMetaKeys::OPENS_AT,
				'closes_at'       => ProcedureMetaKeys::CLOSES_AT,
				'amend_opens_at'  => ProcedureMetaKeys::AMEND_OPENS_AT,
				'amend_closes_at' => ProcedureMetaKeys::AMEND_CLOSES_AT,
			);
			foreach ( $plazos as $campo => $clave ) {
				$crudo[ $campo ] = self::meta( $procedure_id, $clave );
			}
		}
		if ( ! $datos ) {
			// En crudo, sin los filtros de `display`: esto se va a reescribir
			// tal cual, no a pintar.
			$post                       = get_post( $procedure_id );
			$crudo                      = array_merge(
				$crudo,
				array(
					'title'                => $post instanceof \WP_Post ? $post->post_title : '',
					'description'          => $post instanceof \WP_Post ? $post->post_content : '',
					'areas'                => self::terms_of( $procedure_id, ProcedureTaxonomies::AREA ),
					'course'               => self::first_term( $procedure_id, ProcedureTaxonomies::COURSE ),
					'contact_emails'       => get_post_meta( $procedure_id, ProcedureMetaKeys::CONTACT_EMAILS, true ),
					'header_color'         => self::meta( $procedure_id, ProcedureMetaKeys::HEADER_COLOR ),
					'audience'             => self::meta( $procedure_id, ProcedureMetaKeys::AUDIENCE ),
					'ownership'            => get_post_meta( $procedure_id, ProcedureMetaKeys::OWNERSHIP, true ),
					'centre_kinds'         => ProcedureAccess::centre_kinds( $procedure_id ),
					'requires_coordinator' => (bool) get_post_meta( $procedure_id, ProcedureMetaKeys::REQUIRES_COORDINATOR, true ),
					'commitments'          => self::meta( $procedure_id, ProcedureMetaKeys::COMMITMENTS ),
				),
				CentreEligibility::form_values( ProcedureAccess::eligibility( $procedure_id ) )
			);
			$crudo['coordinator_count'] = Applications::coordinator_count( $procedure_id );
		}
		return $crudo;
	}

	/**
	 * Write the meta and the terms of the «Datos» panel.
	 *
	 * Los sanitize de `register_post_meta()` corren dentro de cada
	 * `update_post_meta()`: aquí no se vuelve a limpiar nada.
	 *
	 * @param int                  $procedure_id Procedure post ID.
	 * @param array<string, mixed> $limpio       What ProcedureInput normalised.
	 * @return void
	 */
	private static function write_data( int $procedure_id, array $limpio ): void {
		$meta = array(
			ProcedureMetaKeys::OPENS_AT             => $limpio['opens_at'],
			ProcedureMetaKeys::CLOSES_AT            => $limpio['closes_at'],
			ProcedureMetaKeys::AMEND_OPENS_AT       => $limpio['amend_opens_at'],
			ProcedureMetaKeys::AMEND_CLOSES_AT      => $limpio['amend_closes_at'],
			ProcedureMetaKeys::CONTACT_EMAILS       => $limpio['contact_emails'],
			ProcedureMetaKeys::HEADER_COLOR         => $limpio['header_color'],
			ProcedureMetaKeys::AUDIENCE             => $limpio['audience'],
			ProcedureMetaKeys::OWNERSHIP            => $limpio['ownership'],
			ProcedureMetaKeys::CENTRE_KINDS         => $limpio['centre_kinds'],
			ProcedureMetaKeys::REQUIRES_COORDINATOR => $limpio['requires_coordinator'],
			ProcedureMetaKeys::COMMITMENTS          => $limpio['commitments'],
			ProcedureMetaKeys::CENTRE_TYPES         => $limpio['centre_types'],
			ProcedureMetaKeys::ISLANDS              => $limpio['islands'],
			ProcedureMetaKeys::ALLOWED_CENTRES      => $limpio['allowed_centres'],
		);
		$meta[ ProcedureMetaKeys::COORDINATOR_COUNT ] = $limpio['coordinator_count'];
		foreach ( $meta as $clave => $valor ) {
			update_post_meta( $procedure_id, $clave, wp_slash( $valor ) );
		}
		wp_set_object_terms( $procedure_id, ProcedureInput::term_ids( $limpio['areas'] ), ProcedureTaxonomies::AREA, false );
		wp_set_object_terms( $procedure_id, array( (int) $limpio['course'] ), ProcedureTaxonomies::COURSE, false );
	}

	/**
	 * Save the question list of the procedure.
	 *
	 * Campos paralelos, sin JavaScript: una fila por índice. La que llegue sin
	 * rótulo se cae sola en {@see ProcedureQuestions::sanitize()}, y es lo que
	 * hace que la fila en blanco del final sirva para añadir una pregunta y
	 * también para no añadir ninguna. Las respuestas ya dadas a una pregunta
	 * que se borra se quedan bajo su clave (ADR-0019).
	 *
	 * Las filas de una rejilla llegan como texto y se casan con las que tenía
	 * guardadas para conservar su clave ({@see QuestionGrid::match_rows()}).
	 * El tipo de una pregunta que ya tiene respuestas no cambia: el envío
	 * entero se rechaza y no se guarda nada (ADR-0040).
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return void
	 */
	private static function save_questions( int $procedure_id ): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- el nonce lo comprobó handle(); son listas y cada elemento se sanea en el bucle de abajo.
		$claves     = (array) wp_unslash( $_POST[ self::FIELD_Q . 'key' ] ?? array() );
		$rotulos    = (array) wp_unslash( $_POST[ self::FIELD_Q . 'label' ] ?? array() );
		$ayudas     = (array) wp_unslash( $_POST[ self::FIELD_Q . 'help' ] ?? array() );
		$tipos      = (array) wp_unslash( $_POST[ self::FIELD_Q . 'type' ] ?? array() );
		$opciones   = (array) wp_unslash( $_POST[ self::FIELD_Q . 'choices' ] ?? array() );
		$obligadas  = (array) wp_unslash( $_POST[ self::FIELD_Q . 'required' ] ?? array() );
		$deliveries = (array) wp_unslash( $_POST[ self::FIELD_Q . 'delivery' ] ?? array() );
		$opens      = (array) wp_unslash( $_POST[ self::FIELD_Q . 'opens_at' ] ?? array() );
		$closes     = (array) wp_unslash( $_POST[ self::FIELD_Q . 'closes_at' ] ?? array() );
		$minimos    = (array) wp_unslash( $_POST[ self::FIELD_Q . 'min' ] ?? array() );
		$maximos    = (array) wp_unslash( $_POST[ self::FIELD_Q . 'max' ] ?? array() );
		$filas      = (array) wp_unslash( $_POST[ self::FIELD_Q . 'rows' ] ?? array() );
		// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$antes   = Applications::questions( $procedure_id );
		$previas = array_column( $antes, null, 'key' );
		$crudas  = array();
		foreach ( $rotulos as $i => $rotulo ) {
			$key      = isset( $claves[ $i ] ) ? sanitize_key( (string) $claves[ $i ] ) : '';
			$crudas[] = array(
				'key'       => $key,
				'label'     => sanitize_text_field( (string) $rotulo ),
				'help'      => sanitize_text_field( (string) ( $ayudas[ $i ] ?? '' ) ),
				'type'      => isset( $tipos[ $i ] ) ? sanitize_key( (string) $tipos[ $i ] ) : ProcedureQuestions::TYPE_TEXT,
				'choices'   => sanitize_textarea_field( (string) ( $opciones[ $i ] ?? '' ) ),
				'required'  => ! empty( $obligadas[ $i ] ),
				'delivery'  => ! empty( $deliveries[ $i ] ),
				'opens_at'  => isset( $opens[ $i ] ) && is_string( $opens[ $i ] ) ? sanitize_text_field( $opens[ $i ] ) : '',
				'closes_at' => isset( $closes[ $i ] ) && is_string( $closes[ $i ] ) ? sanitize_text_field( $closes[ $i ] ) : '',
				'min'       => sanitize_text_field( (string) ( $minimos[ $i ] ?? '' ) ),
				'max'       => sanitize_text_field( (string) ( $maximos[ $i ] ?? '' ) ),
				// Línea a línea y sin recortar el conjunto: una línea en blanco
				// al final es una fila que se quita, y cuenta.
				'rows'      => QuestionGrid::match_rows(
					implode( "\n", array_map( 'sanitize_text_field', (array) preg_split( '/\r\n|\r|\n/', (string) ( $filas[ $i ] ?? '' ) ) ) ),
					(array) ( $previas[ $key ]['rows'] ?? array() )
				),
				'row_seq'   => (int) ( $previas[ $key ]['row_seq'] ?? 0 ),
			);
		}

		self::$rejected = QuestionChanges::input_errors( $crudas, $antes );
		if ( '' !== self::$rejected ) {
			return;
		}
		$seq       = self::question_seq( $procedure_id, $antes );
		$questions = ProcedureQuestions::sanitize( $crudas, $seq );
		foreach ( $questions as $question ) {
			if ( ProcedureQuestions::is_delivery( $question ) && ! ProcedureQuestions::valid_delivery( $question ) ) {
				self::$rejected = 'Cada entrega con plazo propio necesita fechas válidas de apertura y cierre, en ese orden. No se han guardado las preguntas.';
				return;
			}
		}
		self::$rejected = QuestionChanges::refusal( $antes, $questions, Applications::answered_keys( $procedure_id ) );
		if ( '' !== self::$rejected ) {
			return;
		}
		update_post_meta( $procedure_id, ProcedureMetaKeys::QUESTIONS_SEQ, $seq );
		update_post_meta( $procedure_id, ProcedureMetaKeys::QUESTIONS, wp_slash( $questions ) );
		Shell::leave( self::url( $procedure_id, self::PANEL_QUESTIONS, array( self::ARG_NOTICE => 'preguntas' ) ) );
	}

	/**
	 * The question counter of a procedure: the highest key it ever gave.
	 *
	 * Un procedimiento de antes del contador empieza por la mayor clave entre
	 * sus preguntas y las que aparecen en las respuestas y documentos de sus
	 * solicitudes, para no dar a una pregunta nueva la clave de una borrada
	 * (ADR-0040).
	 *
	 * @param int                              $procedure_id Procedure post ID.
	 * @param array<int, array<string, mixed>> $questions    Its questions today.
	 * @return int
	 */
	private static function question_seq( int $procedure_id, array $questions ): int {
		$claves = array_column( $questions, 'key' );
		if ( ! metadata_exists( 'post', $procedure_id, ProcedureMetaKeys::QUESTIONS_SEQ ) ) {
			$claves = array_merge( $claves, array_keys( Applications::answered_keys( $procedure_id, true ) ) );
		}
		return max( (int) get_post_meta( $procedure_id, ProcedureMetaKeys::QUESTIONS_SEQ, true ), ProcedureQuestions::highest_key( $claves ) );
	}

	/**
	 * Move one question a place up or down, and nothing else.
	 *
	 * La `key` va con la pregunta y las respuestas no se tocan. En los
	 * extremos, o con un botón que no es uno de los nuestros, no cambia nada
	 * y se vuelve al panel sin aviso (ADR-0040).
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return void
	 */
	private static function move_question( int $procedure_id ): void {
		$pedido  = explode( ':', self::field( self::FIELD_Q_MOVE ) . ':' );
		$antes   = Applications::questions( $procedure_id );
		$despues = QuestionChanges::move( $antes, $pedido[0], $pedido[1] );
		if ( $despues === $antes ) {
			Shell::leave( self::url( $procedure_id, self::PANEL_QUESTIONS ) );
			return;
		}
		update_post_meta( $procedure_id, ProcedureMetaKeys::QUESTIONS, wp_slash( $despues ) );
		Shell::leave( self::url( $procedure_id, self::PANEL_QUESTIONS, array( self::ARG_NOTICE => 'ordenadas' ) ) );
	}

	/**
	 * Extend the closing date of existing independent deliveries without
	 * changing their questions or opening dates.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return void
	 */
	private static function save_delivery_dates( int $procedure_id ): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- handle() verified this operation's nonce.
		$closes = wp_unslash( $_POST[ self::FIELD_DELIVERY_CLOSES ] ?? array() );
		// phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! is_array( $closes ) ) {
			self::$rejected = 'No se han podido validar los nuevos plazos. No se ha cambiado nada.';
			return;
		}

		$questions = Applications::questions( $procedure_id );
		$updated   = 0;
		foreach ( $questions as &$question ) {
			if ( ! ProcedureQuestions::is_delivery( $question ) || ! array_key_exists( $question['key'], $closes ) ) {
				continue;
			}
			$new_close = is_string( $closes[ $question['key'] ] ) ? sanitize_text_field( $closes[ $question['key'] ] ) : '';
			++$updated;
			if ( ! ProcedureInput::is_valid_date( $new_close ) || $new_close < $question['closes_at'] ) {
				self::$rejected = 'El nuevo cierre debe ser una fecha válida igual o posterior al actual. No se ha cambiado ningún plazo.';
				return;
			}
			$question['closes_at'] = $new_close;
		}
		unset( $question );

		if ( 0 === $updated ) {
			self::$rejected = 'No hay entregas documentales cuyos plazos se puedan ampliar.';
			return;
		}
		update_post_meta( $procedure_id, ProcedureMetaKeys::QUESTIONS, wp_slash( $questions ) );
		Shell::leave( self::url( $procedure_id, self::PANEL_DELIVERIES, array( self::ARG_NOTICE => 'entregas' ) ) );
	}

	/**
	 * Save which mails this procedure sends: the panel ticks the ones on,
	 * the meta keeps the ones off.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return void
	 */
	private static function save_notify( int $procedure_id ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- handle() verified the nonce; only slugs of the closed list survive array_intersect().
		$encendidos = (array) wp_unslash( $_POST[ self::FIELD_NOTIFY ] ?? array() );
		$encendidos = array_map( 'sanitize_key', array_filter( $encendidos, 'is_string' ) );
		$apagados   = array_values( array_diff( array_keys( ProcedureMetaKeys::notify_events() ), $encendidos ) );
		update_post_meta( $procedure_id, ProcedureMetaKeys::NOTIFY, $apagados );
		Shell::leave( self::url( $procedure_id, self::PANEL_NOTICES, array( self::ARG_NOTICE => 'avisos' ) ) );
	}

	/**
	 * Save what the procedure asks about the people of each application.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return void
	 */
	private static function save_people_spec( int $procedure_id ): void {
		self::$rejected = PeopleSpecPanel::save( $procedure_id );
		if ( '' === self::$rejected ) {
			Shell::leave( self::url( $procedure_id, self::PANEL_PEOPLE, array( self::ARG_NOTICE => 'personas' ) ) );
		}
	}

	/**
	 * Save the three links: resolution, provisional list, final list.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return void
	 */
	private static function save_links( int $procedure_id ): void {
		$claves = array( ProcedureMetaKeys::RESOLUTION_URL, ProcedureMetaKeys::PROVISIONAL_LIST_URL, ProcedureMetaKeys::FINAL_LIST_URL );
		$urls   = array();
		foreach ( $claves as $clave ) {
			$url = self::field( $clave );
			if ( '' !== $url && ! ProcedureInput::is_url( $url ) ) {
				self::$rejected = ProcedureInput::why( array( substr( $clave, 4 ) ) );
				return;
			}
			$urls[ $clave ] = $url;
		}
		foreach ( $urls as $clave => $url ) {
			update_post_meta( $procedure_id, $clave, $url );
		}
		Shell::leave( self::url( $procedure_id, self::PANEL_LINKS, array( self::ARG_NOTICE => 'enlaces' ) ) );
	}

	/**
	 * Publish the procedure.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @param int $user_id      Who is asking.
	 * @return void
	 */
	private static function publish( int $procedure_id, int $user_id ): void {
		if ( ! ProcedureAccess::can_publish( $user_id, $procedure_id ) ) {
			self::$rejected = 'Su perfil no publica procedimientos: puede editarlo, pero no publicarlo.';
			return;
		}
		wp_update_post(
			array(
				'ID'          => $procedure_id,
				'post_status' => 'publish',
			)
		);
		Shell::leave( self::url( $procedure_id, self::PANEL_PUBLISH, array( self::ARG_NOTICE => 'publicado' ) ) );
	}

	/**
	 * Return the procedure to draft without changing its configured dates.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @param int $user_id      Who is asking.
	 * @return void
	 */
	private static function unpublish( int $procedure_id, int $user_id ): void {
		if ( ! ProcedureAccess::can_publish( $user_id, $procedure_id ) ) {
			self::$rejected = 'Su perfil no puede devolver este procedimiento a borrador.';
			return;
		}
		$result = wp_update_post(
			array(
				'ID'          => $procedure_id,
				'post_status' => 'draft',
			),
			true
		);
		if ( is_wp_error( $result ) ) {
			self::$rejected = 'No se pudo devolver el procedimiento a borrador. No se ha cambiado.';
			return;
		}
		Shell::leave( self::url( $procedure_id, self::PANEL_PUBLISH, array( self::ARG_NOTICE => 'borrador' ) ) );
	}

	/**
	 * Pause or resume new applications while leaving delivery deadlines alone.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @param int $user_id      Who is asking.
	 * @return void
	 */
	private static function toggle_applications( int $procedure_id, int $user_id ): void {
		if ( 'publish' !== get_post_status( $procedure_id ) ) {
			self::$rejected = 'Solo se puede cerrar la inscripción de un procedimiento publicado.';
			return;
		}
		if ( ! ProcedureAccess::can_edit( $user_id, $procedure_id ) ) {
			self::$rejected = ProcedureAccess::why_not_editable( $user_id, $procedure_id );
			return;
		}
		$closed = ! (bool) get_post_meta( $procedure_id, ProcedureMetaKeys::APPLICATIONS_CLOSED, true );
		update_post_meta( $procedure_id, ProcedureMetaKeys::APPLICATIONS_CLOSED, $closed );
		if ( $closed xor (bool) get_post_meta( $procedure_id, ProcedureMetaKeys::APPLICATIONS_CLOSED, true ) ) {
			self::$rejected = 'No se pudo cambiar el cierre de solicitudes. No se ha aplicado el cambio.';
			return;
		}
		Shell::leave(
			self::url( $procedure_id, self::PANEL_PUBLISH, array( self::ARG_NOTICE => $closed ? 'cerradas' : 'reabiertas' ) )
		);
	}

	/**
	 * Mark or unmark the procedure as «histórico».
	 *
	 * Las dos mitades no piden lo mismo: marcar lo hace el ámbito que convoca;
	 * desmarcar, solo administración (ADR-0023).
	 *
	 * @param string $op           archive | unarchive.
	 * @param int    $procedure_id Procedure post ID.
	 * @param int    $user_id      Who is asking.
	 * @return void
	 */
	private static function save_archived( string $op, int $procedure_id, int $user_id ): void {
		$marcar = self::OP_ARCHIVE === $op;
		$puede  = $marcar
			? ProcedureAccess::can_archive( $user_id, $procedure_id )
			: ProcedureAccess::can_unarchive( $user_id );
		if ( ! $puede ) {
			self::$rejected = $marcar
				? 'Este procedimiento no es suyo: solo lo marca como histórico el ámbito que lo convoca.'
				: 'Volver a abrir un procedimiento histórico solo lo hace quien administra el aplicativo.';
			return;
		}
		update_post_meta( $procedure_id, ProcedureMetaKeys::ARCHIVED, $marcar );
		// Cerrado, aquí ya no queda nada que editar: se suelta el bloqueo
		// —solo el propio— para que administración pueda entrar sin esperar.
		if ( $marcar ) {
			EditLock::release( $procedure_id );
		}
		Shell::leave( self::url( $procedure_id, self::PANEL_PUBLISH, array( self::ARG_NOTICE => $marcar ? 'archivado' : 'desarchivado' ) ) );
	}

	/**
	 * Admit, exclude or ask to amend one application, with its note.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @param int $row_id       Application post ID.
	 * @return void
	 */
	private static function review( int $procedure_id, int $row_id ): void {
		if ( (int) get_post_field( 'post_parent', $row_id ) !== $procedure_id ) {
			self::$rejected = 'Esa solicitud no es de este procedimiento.';
			return;
		}
		$res = Applications::review( $row_id, self::field( self::FIELD_STATE ), self::textarea( self::FIELD_NOTE ) );
		if ( ! $res['ok'] ) {
			$textos         = array(
				'note'  => 'Para pedir subsanar o para excluir hay que escribir una nota: el centro tiene que leer qué se le pide o por qué.',
				'state' => 'Ese estado de revisión no existe.',
			);
			self::$rejected = $textos[ $res['error'] ] ?? 'No se ha podido revisar la solicitud.';
			return;
		}
		// Vuelve a la misma vista: el filtro y la página viajan en el formulario.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- el nonce lo comprobó handle(); solo se lee el filtro de vuelta.
		Shell::leave( ApplicationsPanel::url( $procedure_id, ApplicationsPanel::filter( $_POST ), array( self::ARG_NOTICE => 'revisada' ) ) );
	}

	/**
	 * Review the ticked applications at once, with the same state and note.
	 *
	 * Lo que se rechaza entero —nada marcado, más del tope, un estado que no
	 * existe o la nota que falta— no redirige: repinta con el motivo. Lo
	 * demás vuelve a la misma vista con cuántas cambiaron y cuántas no.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return void
	 */
	private static function bulk_review( int $procedure_id ): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- el nonce lo comprobó handle() antes de llamar aquí.
		$ids    = ApplicationsPanel::posted_ids( $_POST );
		$filtro = ApplicationsPanel::filter( $_POST );
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		$res = ApplicationsPanel::bulk( $procedure_id, $ids, self::field( self::FIELD_STATE ), self::textarea( self::FIELD_NOTE ) );
		if ( '' !== $res['error'] ) {
			self::$rejected = $res['error'];
			return;
		}
		Shell::leave(
			ApplicationsPanel::url(
				$procedure_id,
				$filtro,
				array(
					self::ARG_NOTICE              => ApplicationsPanel::NOTICE_BULK,
					ApplicationsPanel::ARG_DONE   => $res['done'],
					ApplicationsPanel::ARG_FAILED => $res['failed'],
				)
			)
		);
	}

	/**
	 * Send the applications of this procedure down, as the dialog asked.
	 *
	 * Va por POST y con nonce como cualquier otra acción, aunque no escriba
	 * nada en el procedimiento: un enlace en GET que descarga la lista entera
	 * de centros es justo lo que no se quiere que se pueda pegar en un correo.
	 * Lo único que escribe es la elección, en la meta de quien exporta, para
	 * que la próxima vez el diálogo salga como lo dejó (ADR-0039).
	 *
	 * Un envío sin los campos del diálogo —el de antes— exporta lo de antes:
	 * todas las columnas, una fila por solicitud, en CSV.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @param int $user_id      Who exports.
	 * @return void
	 */
	private static function export( int $procedure_id, int $user_id ): void {
		$eleccion = ApplicationExport::sanitize_choice( $procedure_id, self::export_request() );
		ApplicationExport::remember( $user_id, $procedure_id, $eleccion );
		// Las del filtro de la tabla, o todas (PR 6 del PLAN-0003).
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- el nonce lo comprobó handle() antes de llamar aquí.
		$ids = ApplicationsPanel::export_ids( $procedure_id, $_POST );

		$cuerpo = ApplicationExport::FORMAT_MOODLE === $eleccion['format']
			? ApplicationExport::moodle(
				$procedure_id,
				array(
					'who' => $eleccion['who'],
					'ids' => $ids,
				)
			)
			: ApplicationExport::csv( $procedure_id, $eleccion['columns'], $eleccion['shape'], $ids );
		$nombre = ApplicationExport::filename( (string) get_post_field( 'post_title', $procedure_id ), $eleccion['format'] );

		Shell::send_header( 'Content-Type: text/csv; charset=utf-8' );
		Shell::send_header( 'Content-Disposition: attachment; filename="' . $nombre . '"' );
		Shell::send_header( 'Content-Length: ' . strlen( $cuerpo ) );
		Shell::send_header( 'Cache-Control: no-store, no-cache, must-revalidate' );
		echo $cuerpo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- es un CSV, no HTML; ApplicationExport lo entrecomilla y desactiva las fórmulas.
		Shell::leave();
	}

	/**
	 * What the export dialog sent, in the shape ApplicationExport wants.
	 *
	 * @return array<string, mixed>
	 */
	private static function export_request(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- el nonce lo comprobó handle() antes de llamar aquí.
		$listas = array();
		// Las columnas, con `sanitize_text_field()` y no `sanitize_key()`: la de
		// una fila de rejilla lleva dos puntos (`q4:r1`, ADR-0040). Lo que no
		// sea exactamente una columna lo descarta ApplicationExport.
		foreach ( array(
			self::FIELD_EXPORT_COLUMNS => 'sanitize_text_field',
			self::FIELD_EXPORT_WHO     => 'sanitize_key',
		) as $campo => $limpia ) {
			$valor            = isset( $_POST[ $campo ] ) ? wp_unslash( $_POST[ $campo ] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- cada clave pasa por su saneado justo abajo.
			$listas[ $campo ] = is_array( $valor ) ? array_map( $limpia, array_filter( $valor, 'is_string' ) ) : array();
		}
		$todas = ! empty( $_POST[ self::FIELD_EXPORT_ALL ] );
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		return array(
			'all'     => $todas,
			'columns' => $listas[ self::FIELD_EXPORT_COLUMNS ],
			'shape'   => sanitize_key( self::field( self::FIELD_EXPORT_SHAPE ) ),
			'format'  => sanitize_key( self::field( self::FIELD_EXPORT_FORMAT ) ),
			'who'     => $listas[ self::FIELD_EXPORT_WHO ],
		);
	}

	/**
	 * One submitted field, trimmed and sanitised.
	 *
	 * @param string $nombre Field name.
	 * @return string
	 */
	private static function field( string $nombre ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- el nonce lo comprobó handle() antes de llamar aquí.
		return trim( sanitize_text_field( wp_unslash( (string) ( $_POST[ $nombre ] ?? '' ) ) ) );
	}

	/**
	 * One submitted multi-line field, trimmed and sanitised.
	 *
	 * @param string $nombre Field name.
	 * @return string
	 */
	private static function textarea( string $nombre ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- el nonce lo comprobó handle() antes de llamar aquí.
		return trim( sanitize_textarea_field( wp_unslash( (string) ( $_POST[ $nombre ] ?? '' ) ) ) );
	}

	/*
	 * -----------------------------------------------------------------------
	 * El modelo
	 * -----------------------------------------------------------------------
	 */

	/**
	 * Everything the workshop decides before painting anything.
	 *
	 * @return array<string, mixed>
	 */
	public static function model(): array {
		$m = self::blank();

		if ( ! is_user_logged_in() ) {
			$m['aviso'] = 'Debe iniciar sesión con su usuario para gestionar procedimientos.';
			return $m;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- lectura del procedimiento y de la pestaña; mutar lleva su nonce.
		$procedure_id = absint( wp_unslash( $_GET[ self::ARG_PROCEDURE ] ?? 0 ) );
		$pedido       = sanitize_key( wp_unslash( (string) ( $_GET[ self::ARG_PANEL ] ?? '' ) ) );
		$hecho        = sanitize_key( wp_unslash( (string) ( $_GET[ self::ARG_NOTICE ] ?? '' ) ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$user_id = get_current_user_id();
		if ( $procedure_id <= 0 ) {
			return self::blank_procedure( $m, $user_id );
		}

		$procedimiento = get_post( $procedure_id );
		if ( ! $procedimiento instanceof \WP_Post || ProcedurePostType::POST_TYPE !== $procedimiento->post_type ) {
			$m['aviso'] = 'Ese procedimiento ya no existe. Elija uno en la lista para abrir su taller.';
			return $m;
		}
		// `can_open()` y no `can_edit()`: un procedimiento histórico se sigue
		// abriendo, en solo lectura. Guardar es de `handle()`, que sí pregunta
		// por `can_edit()`.
		if ( ! ProcedureAccess::can_open( $user_id, $procedure_id ) ) {
			$m['aviso']      = ProcedureAccess::why_not_editable( $user_id, $procedure_id );
			$m['aviso_tipo'] = 'error';
			return $m;
		}

		$m = self::fill( $m, $procedimiento, $user_id, $pedido, $hecho );
		// Solo el panel «Datos» las pinta: los demás no recorren el catálogo.
		$m['centre_options'] = CentreCatalog::choices( self::PANEL_DATA === $m['panel'], (array) $m['values'][ ProcedureMetaKeys::CENTRE_TYPES ], (array) $m['values'][ ProcedureMetaKeys::ISLANDS ] );
		// Lo que el taller tiene que avisar de los acotados guardados: un
		// catálogo vacío o ningún tipo de centro marcado como privado (ADR-0042).
		$m['centre_warnings'] = CentreEligibility::warnings( ProcedureAccess::eligibility( $procedure_id ), array() === CentreCatalog::all(), array() === CentreCatalog::private_types() );
		return $m;
	}

	/**
	 * The model of a workshop that cannot be opened.
	 *
	 * @return array<string, mixed>
	 */
	private static function blank(): array {
		return array(
			'aviso'                   => '',
			'aviso_tipo'              => 'aviso',
			'nuevo'                   => false,
			'procedure_id'            => 0,
			'title'                   => '',
			'panel'                   => self::PANEL_DATA,
			'panels'                  => array(),
			'flash'                   => array(
				'tipo'  => '',
				'texto' => '',
			),
			'can_edit'                => false,
			'groups'                  => array(),
			'state_notice'            => '',
			'can_review'              => false,
			'can_edit_custom_js'      => false,
			'can_publish'             => false,
			'can_unpublish'           => false,
			'can_toggle_applications' => false,
			'applications_closed'     => false,
			'pending_documentation'   => false,
			'can_archive'             => false,
			'can_unarchive'           => false,
			'archived'                => false,
			'lock'                    => EditLock::none(),
			'state'                   => '',
			'state_label'             => '',
			'status'                  => '',
			'status_label'            => '',
			'area_names'              => '',
			'foreign_areas'           => array(),
			'view_url'                => '',
			'workspace_url'           => Shell::url( 'workspace' ),
			'values'                  => array(),
			'terms'                   => array(
				'area'   => array(),
				'course' => array(),
			),
			'ownerships'              => ProcedureMetaKeys::ownerships(),
			'centre_warnings'         => array(),
			'centre_options'          => array(
				'types'   => array(),
				'islands' => array(),
			),
			'centre_kinds'            => ProcedureMetaKeys::centre_kinds(),
			'audiences'               => ProcedureMetaKeys::audiences(),
			'header_colors'           => ProcedureMetaKeys::header_colors(),
			'questions'               => array(),
			'q_types'                 => ProcedureQuestions::types(),
			'q_max'                   => ProcedureQuestions::MAX,
			'applications'            => array(),
			'app_cols'                => array(),
			'export'                  => array(),
			'table'                   => array(),
			'review_states'           => ApplicationMetaKeys::review_states(),
			'notify_events'           => ProcedureMetaKeys::notify_events(),
			'people_spec'             => array(),
			'people'                  => array(),
		);
	}

	/**
	 * The model of the «create a procedure» screen.
	 *
	 * @param array<string, mixed> $m       What blank() returned.
	 * @param int                  $user_id Who is looking.
	 * @return array<string, mixed>
	 */
	private static function blank_procedure( array $m, int $user_id ): array {
		if ( ! ProcedureAccess::can_manage_procedures( $user_id ) ) {
			$m['aviso']      = self::CANNOT_CREATE;
			$m['aviso_tipo'] = 'error';
			return $m;
		}
		$m['nuevo']    = true;
		$m['can_edit'] = true;
		// Nace en borrador, y en borrador se edita todo.
		$m['groups']       = array_fill_keys( ProcedureState::editable_fields( ProcedureMetaKeys::STATE_DRAFT ), true );
		$m['flash']        = self::flash( '' );
		$m['status']       = 'draft';
		$m['status_label'] = self::status_label( 'draft' );
		$m['values']       = self::values( 0 );
		$m['terms']        = self::term_lists( $user_id );

		// El alta llega hecha a medias, como la de siempre: si esta persona
		// convoca desde un solo ámbito no hay nada que elegir, y el correo de
		// contacto empieza por el suyo. Solo cuando no se ha tecleado nada:
		// un envío rechazado repinta lo que se escribió.
		if ( '' === self::$rejected ) {
			$suyos = ProcedureAccess::scope_areas( $user_id );
			if ( 1 === count( $suyos ) ) {
				$m['values'][ self::FIELD_AREA ] = $suyos;
			}
			$quien = get_userdata( $user_id );
			if ( false !== $quien && '' !== (string) $quien->user_email ) {
				$m['values'][ ProcedureMetaKeys::CONTACT_EMAILS ] = array( (string) $quien->user_email );
			}
		}
		return $m;
	}

	/**
	 * Everything the workshop shows once the procedure is known and allowed.
	 *
	 * @param array<string, mixed> $m             What blank() returned.
	 * @param \WP_Post             $procedimiento The procedure.
	 * @param int                  $user_id       Who is looking.
	 * @param string               $pedido        Tab asked for in the URL.
	 * @param string               $hecho         Notice code in the URL.
	 * @return array<string, mixed>
	 */
	private static function fill( array $m, \WP_Post $procedimiento, int $user_id, string $pedido, string $hecho ): array {
		$procedure_id = (int) $procedimiento->ID;
		$paneles      = self::panels( $procedure_id );

		$m['procedure_id']       = $procedure_id;
		$m['title']              = (string) $procedimiento->post_title;
		$m['panels']             = $paneles;
		$m['panel']              = isset( $paneles[ $pedido ] ) ? $pedido : self::PANEL_DATA;
		$m['flash']              = self::flash( $hecho );
		$m['can_edit']           = ProcedureAccess::can_edit( $user_id, $procedure_id );
		$m['can_edit_custom_js'] = ProcedureAccess::can_edit_custom_js( $user_id, $procedure_id );
		$m['can_review']         = ProcedureAccess::can_review( $user_id, $procedure_id );
		$m['archived']           = ProcedureAccess::is_archived( $procedure_id );
		// Abrir el taller toma el bloqueo del procedimiento, igual que abrir el
		// editor del escritorio: es el mismo bloqueo (ADR-0008). Si ya lo tiene
		// otra persona, el taller pasa a solo lectura.
		$m['lock'] = EditLock::status( $procedure_id, (bool) $m['can_edit'] );
		if ( (int) $m['lock']['owner'] > 0 ) {
			$m['can_edit'] = false;
		}
		$libre                        = 0 === (int) $m['lock']['owner'];
		$m['can_archive']             = $libre && ! $m['archived'] && ProcedureAccess::can_archive( $user_id, $procedure_id );
		$m['can_unarchive']           = $libre && $m['archived'] && ProcedureAccess::can_unarchive( $user_id );
		$m['can_publish']             = $m['can_edit'] && 'publish' !== $procedimiento->post_status && ProcedureAccess::can_publish( $user_id, $procedure_id );
		$m['can_unpublish']           = $m['can_edit']
			&& 'publish' === $procedimiento->post_status
			&& ProcedureAccess::can_publish( $user_id, $procedure_id );
		$m['can_toggle_applications'] = $libre && $m['can_edit'] && 'publish' === $procedimiento->post_status;
		$m['applications_closed']     = (bool) get_post_meta( $procedure_id, ProcedureMetaKeys::APPLICATIONS_CLOSED, true );
		$m['pending_documentation']   = false;
		if ( 0 < (int) $paneles[ self::PANEL_APPLICATIONS ]['count'] ) {
			foreach ( Applications::questions( $procedure_id ) as $question ) {
				if ( ! ProcedureQuestions::is_delivery( $question ) ) {
					continue;
				}
				if ( ProcedureQuestions::delivery_is_open( $question, ProcedureState::today() ) ) {
					$m['pending_documentation'] = true;
					break;
				}
			}
		}
		$m['state']       = ProcedureState::of_post( $procedure_id );
		$m['state_label'] = ProcedureState::label( (string) $m['state'], $m['applications_closed'] );
		// Qué grupos deja tocar el estado de hoy, y por qué cierra el resto.
		// Lo decide el dominio; la pantalla solo pinta apagado lo que aquí
		// salga en `false`.
		foreach ( ProcedureState::groups() as $grupo ) {
			$m['groups'][ $grupo ] = true === $m['can_edit'] && ProcedureAccess::can_edit_group( $user_id, $procedure_id, $grupo );
		}
		$m['state_notice']  = true === $m['can_edit'] ? ProcedureState::why_locked( (string) $m['state'] ) : '';
		$m['status']        = (string) $procedimiento->post_status;
		$m['status_label']  = self::status_label( (string) $procedimiento->post_status );
		$m['area_names']    = self::area_names( ProcedureAccess::post_areas( $procedure_id ) );
		$foreign_ids        = ProcedureAccess::can_edit_all_areas( $user_id ) ? array() : array_diff( ProcedureAccess::post_areas( $procedure_id ), ProcedureAccess::scope_areas( $user_id ) );
		$all_labels         = ProcedureTaxonomies::area_options( 0, true );
		$m['foreign_areas'] = array_values( array_intersect_key( $all_labels, array_flip( $foreign_ids ) ) );
		$m['view_url']      = (string) get_permalink( $procedimiento );
		$m['values']        = self::values( $procedure_id );
		$m['terms']         = self::term_lists( $user_id );
		$m['questions']     = Applications::questions( $procedure_id );
		$m['deliveries']    = array_values( array_filter( $m['questions'], array( ProcedureQuestions::class, 'is_delivery' ) ) );
		$m['people_spec']   = PeopleSpecPanel::model( $procedure_id, '' !== self::$rejected );

		if ( self::PANEL_APPLICATIONS === $m['panel'] ) {
			// La tabla filtrada, una página, y lo que cuenta el diálogo de
			// exportar: sin leer las metas de todas las solicitudes.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- el filtro de la tabla es de lectura.
			$m = array_merge( $m, ApplicationsPanel::model( $procedure_id, $user_id, (array) $m['questions'], $_GET ) );
		}
		return $m;
	}

	/**
	 * The notice of this request: what the URL says was done, or why the
	 * submit was refused.
	 *
	 * @param string $hecho Notice code in the URL.
	 * @return array{tipo:string, texto:string}
	 */
	private static function flash( string $hecho ): array {
		if ( '' !== self::$rejected ) {
			return array(
				'tipo'  => 'error',
				'texto' => self::$rejected,
			);
		}
		if ( ApplicationsPanel::NOTICE_BULK === $hecho ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo se leen dos cifras para el aviso.
			return ApplicationsPanel::bulk_notice( $_GET );
		}
		return array(
			'tipo'  => isset( self::DONE[ $hecho ] ) ? 'ok' : '',
			'texto' => (string) ( self::DONE[ $hecho ] ?? '' ),
		);
	}

	/**
	 * What every form field shows: what was typed on a refused submit, or what is stored.
	 *
	 * @param int $procedure_id Procedure post ID; 0 when creating.
	 * @return array<string, mixed>
	 */
	private static function values( int $procedure_id ): array {
		$guardado = array(
			self::FIELD_TITLE                       => $procedure_id > 0 ? (string) get_post_field( 'post_title', $procedure_id ) : '',
			self::FIELD_DESCRIPTION                 => $procedure_id > 0 ? (string) get_post_field( 'post_content', $procedure_id ) : '',
			self::FIELD_AREA                        => self::terms_of( $procedure_id, ProcedureTaxonomies::AREA ),
			self::FIELD_COURSE                      => (string) self::first_term( $procedure_id, ProcedureTaxonomies::COURSE ),
			ProcedureMetaKeys::OPENS_AT             => self::meta( $procedure_id, ProcedureMetaKeys::OPENS_AT ),
			ProcedureMetaKeys::CLOSES_AT            => self::meta( $procedure_id, ProcedureMetaKeys::CLOSES_AT ),
			ProcedureMetaKeys::AMEND_OPENS_AT       => self::meta( $procedure_id, ProcedureMetaKeys::AMEND_OPENS_AT ),
			ProcedureMetaKeys::AMEND_CLOSES_AT      => self::meta( $procedure_id, ProcedureMetaKeys::AMEND_CLOSES_AT ),
			ProcedureMetaKeys::CONTACT_EMAILS       => ProcedureInput::emails( get_post_meta( $procedure_id, ProcedureMetaKeys::CONTACT_EMAILS, true ) ),
			ProcedureMetaKeys::HEADER_COLOR         => self::meta( $procedure_id, ProcedureMetaKeys::HEADER_COLOR ),
			ProcedureMetaKeys::AUDIENCE             => $procedure_id > 0
				? ProcedureMetaKeys::in_list( self::meta( $procedure_id, ProcedureMetaKeys::AUDIENCE ), ProcedureMetaKeys::audiences(), ProcedureMetaKeys::AUDIENCE_SCHOOLS )
				: ProcedureMetaKeys::AUDIENCE_SCHOOLS,
			ProcedureMetaKeys::OWNERSHIP            => $procedure_id > 0
				? ProcedureInput::ownership( get_post_meta( $procedure_id, ProcedureMetaKeys::OWNERSHIP, true ) )
				: array_keys( ProcedureMetaKeys::ownerships() ),
			ProcedureMetaKeys::CENTRE_KINDS         => self::centre_kinds_value( $procedure_id ),
			ProcedureMetaKeys::REQUIRES_COORDINATOR => Applications::coordinator_count( $procedure_id ) > 0 ? '1' : '',
			ProcedureMetaKeys::COMMITMENTS          => self::meta( $procedure_id, ProcedureMetaKeys::COMMITMENTS ),
			ProcedureMetaKeys::CUSTOM_CSS           => self::meta( $procedure_id, ProcedureMetaKeys::CUSTOM_CSS ),
			ProcedureMetaKeys::CUSTOM_JS            => self::meta( $procedure_id, ProcedureMetaKeys::CUSTOM_JS ),
			ProcedureMetaKeys::RESOLUTION_URL       => self::meta( $procedure_id, ProcedureMetaKeys::RESOLUTION_URL ),
			ProcedureMetaKeys::PROVISIONAL_LIST_URL => self::meta( $procedure_id, ProcedureMetaKeys::PROVISIONAL_LIST_URL ),
			ProcedureMetaKeys::FINAL_LIST_URL       => self::meta( $procedure_id, ProcedureMetaKeys::FINAL_LIST_URL ),
			ProcedureMetaKeys::NOTIFY               => $procedure_id > 0 ? (array) get_post_meta( $procedure_id, ProcedureMetaKeys::NOTIFY, true ) : array(),
		);
		$guardado[ ProcedureMetaKeys::COORDINATOR_COUNT ] = (string) Applications::coordinator_count( $procedure_id );
		$acotados                                       = CentreEligibility::form_values( ProcedureAccess::eligibility( $procedure_id ) );
		$guardado[ ProcedureMetaKeys::CENTRE_TYPES ]    = $acotados['centre_types'];
		$guardado[ ProcedureMetaKeys::ISLANDS ]         = $acotados['islands'];
		$guardado[ ProcedureMetaKeys::ALLOWED_CENTRES ] = $acotados['allowed_centres'];
		if ( '' === self::$rejected ) {
			return $guardado;
		}

		// Lo tecleado manda sobre lo guardado, pero solo en los campos que
		// existen: del POST no entra ninguna clave nueva.
		$tecleado = self::submitted_data( $procedure_id );
		$mapa     = array(
			self::FIELD_TITLE                  => 'title',
			self::FIELD_DESCRIPTION            => 'description',
			self::FIELD_AREA                   => 'areas',
			self::FIELD_COURSE                 => 'course',
			ProcedureMetaKeys::OPENS_AT        => 'opens_at',
			ProcedureMetaKeys::CLOSES_AT       => 'closes_at',
			ProcedureMetaKeys::AMEND_OPENS_AT  => 'amend_opens_at',
			ProcedureMetaKeys::AMEND_CLOSES_AT => 'amend_closes_at',
			ProcedureMetaKeys::CONTACT_EMAILS  => 'contact_emails',
			ProcedureMetaKeys::HEADER_COLOR    => 'header_color',
			ProcedureMetaKeys::AUDIENCE        => 'audience',
			ProcedureMetaKeys::OWNERSHIP       => 'ownership',
			ProcedureMetaKeys::CENTRE_KINDS    => 'centre_kinds',
			ProcedureMetaKeys::COMMITMENTS     => 'commitments',
			ProcedureMetaKeys::CENTRE_TYPES    => 'centre_types',
			ProcedureMetaKeys::ISLANDS         => 'islands',
			ProcedureMetaKeys::ALLOWED_CENTRES => 'allowed_centres',
		);
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- solo se repinta lo tecleado; el nonce lo comprobó handle().
		$enviados                 = array_keys( (array) $_POST );
		$tecleado['ownership']    = ProcedureInput::ownership( $tecleado['ownership'] );
		$tecleado['centre_kinds'] = ProcedureInput::centre_kinds( $tecleado['centre_kinds'] );
		foreach ( $mapa as $campo => $clave ) {
			if ( in_array( $campo, $enviados, true ) ) {
				$valor              = $tecleado[ $clave ];
				$guardado[ $campo ] = is_array( $valor ) ? $valor : (string) $valor;
			}
		}
		if ( in_array( ProcedureMetaKeys::COORDINATOR_COUNT, $enviados, true ) ) {
			$guardado[ ProcedureMetaKeys::COORDINATOR_COUNT ] = (string) $tecleado['coordinator_count'];
		}
		foreach ( array( ProcedureMetaKeys::RESOLUTION_URL, ProcedureMetaKeys::PROVISIONAL_LIST_URL, ProcedureMetaKeys::FINAL_LIST_URL ) as $clave ) {
			if ( in_array( $clave, $enviados, true ) ) {
				$guardado[ $clave ] = self::field( $clave );
			}
		}
		return $guardado;
	}

	/**
	 * What the classification offers: the ámbitos as a tree, and every course.
	 *
	 * @param int $user_id   Who is looking.
	 * @return array{area:array<int, array<string, mixed>>, course:array<int, string>}
	 */
	private static function term_lists( int $user_id ): array {
		$solo = ProcedureAccess::can_edit_all_areas( $user_id )
			? array()
			: ProcedureAccess::scope_areas( $user_id );
		return array(
			'area'   => self::area_tree( $solo ),
			'course' => self::term_options( ProcedureTaxonomies::COURSE ),
		);
	}

	/**
	 * The ámbitos this person may file under, nested as they are filed.
	 *
	 * El original pinta un árbol de casillas de cuatro niveles, y el ámbito
	 * es jerárquico también aquí. Se acota **antes** de anidar: un ámbito
	 * cuyo padre no esté en la lista pasa a ser raíz, en vez de desaparecer
	 * con él o de arrastrar a la pantalla ramas que esta persona no puede
	 * marcar.
	 *
	 * @param int[] $solo Term IDs to keep; empty for all of them.
	 * @return array<int, array{id:int, name:string, children:array<int, mixed>}>
	 */
	private static function area_tree( array $solo = array() ): array {
		$terms = get_terms(
			array(
				'taxonomy'   => ProcedureTaxonomies::AREA,
				'hide_empty' => false,
			)
		);
		if ( ! is_array( $terms ) ) {
			return array();
		}

		$nombres = array();
		foreach ( $terms as $term ) {
			if ( $term instanceof \WP_Term && ( array() === $solo || in_array( (int) $term->term_id, $solo, true ) ) ) {
				$nombres[ (int) $term->term_id ] = array( (int) $term->parent, (string) $term->name );
			}
		}

		$hijos = array();
		foreach ( $nombres as $id => $dato ) {
			$padre             = isset( $nombres[ $dato[0] ] ) ? (int) $dato[0] : 0;
			$hijos[ $padre ][] = (int) $id;
		}
		return self::branch( $hijos, $nombres, 0 );
	}

	/**
	 * One branch of the ámbito tree, and under it the branches of its children.
	 *
	 * @param array<int, int[]>                  $hijos   Parent term ID => child term IDs.
	 * @param array<int, array<int, int|string>> $nombres Term ID => parent and name.
	 * @param int                                $padre   Parent term ID; 0 for the roots.
	 * @return array<int, array{id:int, name:string, children:array<int, mixed>}>
	 */
	private static function branch( array $hijos, array $nombres, int $padre ): array {
		$out = array();
		foreach ( (array) ( $hijos[ $padre ] ?? array() ) as $id ) {
			$out[] = array(
				'id'       => (int) $id,
				'name'     => (string) $nombres[ $id ][1],
				'children' => self::branch( $hijos, $nombres, (int) $id ),
			);
		}
		return $out;
	}

	/**
	 * Terms of one taxonomy, optionally narrowed to a handful.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @param int[]  $solo     Term IDs to keep; empty for all of them.
	 * @return array<int, string> term_id => nombre.
	 */
	private static function term_options( string $taxonomy, array $solo = array() ): array {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'fields'     => 'id=>name',
			)
		);
		if ( ! is_array( $terms ) ) {
			return array();
		}
		return array() === $solo ? $terms : array_intersect_key( $terms, array_flip( $solo ) );
	}

	/**
	 * Every term of one taxonomy on the procedure.
	 *
	 * @param int    $procedure_id Procedure post ID.
	 * @param string $taxonomy     Taxonomy name.
	 * @return int[] Term IDs.
	 */
	private static function terms_of( int $procedure_id, string $taxonomy ): array {
		if ( $procedure_id <= 0 ) {
			return array();
		}
		return ProcedureInput::term_ids( wp_get_post_terms( $procedure_id, $taxonomy, array( 'fields' => 'ids' ) ) );
	}

	/**
	 * The first term of one taxonomy on the procedure.
	 *
	 * @param int    $procedure_id Procedure post ID.
	 * @param string $taxonomy     Taxonomy name.
	 * @return int Term ID, or 0.
	 */
	private static function first_term( int $procedure_id, string $taxonomy ): int {
		if ( $procedure_id <= 0 ) {
			return 0;
		}
		$ids = wp_get_post_terms( $procedure_id, $taxonomy, array( 'fields' => 'ids' ) );
		return is_array( $ids ) ? (int) ( $ids[0] ?? 0 ) : 0;
	}

	/**
	 * The ámbitos of the procedure, written out.
	 *
	 * @param int[] $ids Term IDs of prc_area.
	 * @return string
	 */
	private static function area_names( array $ids ): string {
		$nombres = array();
		foreach ( $ids as $term_id ) {
			$term = get_term( (int) $term_id, ProcedureTaxonomies::AREA );
			if ( $term instanceof \WP_Term ) {
				$nombres[] = $term->name;
			}
		}
		return array() === $nombres ? 'Sin ámbito' : implode( ' · ', $nombres );
	}

	/**
	 * The human name of a post status.
	 *
	 * @param string $status Post status.
	 * @return string
	 */
	private static function status_label( string $status ): string {
		$objeto = get_post_status_object( $status );
		return null !== $objeto ? (string) $objeto->label : $status;
	}

	/**
	 * One meta value, as a string.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $clave   Meta key.
	 * @return string
	 */
	private static function meta( int $post_id, string $clave ): string {
		return $post_id > 0 ? (string) get_post_meta( $post_id, $clave, true ) : '';
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
		$m = self::model();
		// El aviso del bloqueo va delante de la pantalla: cuando hay dueño se
		// pinta como `<dialog open>` y es lo primero que se lee al entrar.
		return EditLock::render( EditLock::claim( (array) $m['lock'] ) ) . ProcedureEditorView::html( $m );
	}
}
