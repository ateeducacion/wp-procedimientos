<?php
/**
 * What acting on one application writes: withdraw, restore, extend, the
 * internal note and an application submitted on behalf of a school.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\Access\ApplicationActions;
use Prc\Meta\ApplicationMetaKeys;
use Prc\PostType\ApplicationPostType;

/**
 * Lo que escriben las actuaciones sobre una solicitud (PLAN-0003, PR 9;
 * ADR-0044).
 *
 * **Aquí no se decide quién puede**: eso es de
 * {@see ApplicationActions}, y lo preguntan las pantallas antes de llamar.
 * Cada operación emite su acción de WordPress después de escribir, y el
 * historial ({@see ApplicationHistory}) y el correo las escuchan: ninguna
 * pantalla anota ni manda nada por su cuenta (ADR-0038).
 *
 * Retirar es enviar a la papelera (ADR-0007), también donde el núcleo la
 * tiene desactivada ({@see send_to_trash()}), y la acción
 * `prc_application_withdrawn` sale de `trashed_post`: así también se anota
 * una solicitud que administración manda a la papelera desde el escritorio.
 */
final class ApplicationOperations {

	/** Who withdrew: the school —or teacher— itself. */
	public const BY_APPLICANT = 'applicant';

	/** Who withdrew: whoever reviews, from the review, with a reason. */
	public const BY_REVIEWER = 'reviewer';

	/** Who withdrew: someone from the WordPress dashboard, without a reason. */
	public const BY_DESK = 'desk';

	/**
	 * What the withdrawal under way is: who, why and from where.
	 *
	 * Lo deja {@see withdraw()} para que {@see on_trashed()} lo cuente; sin él,
	 * es el escritorio.
	 *
	 * @var array{user:int, by:string, reason:string}|null
	 */
	private static $context = null;

	/**
	 * Why a new application was not kept: another one of the same school got in first.
	 */
	public const DUPLICATE = 'Ya hay una solicitud de este centro en este procedimiento, así que no se ha creado otra. Vuelva a abrir la solicitud para ver la que hay.';

	/**
	 * Why the last save did not keep anything, when it is not a plain failure.
	 *
	 * @var string
	 */
	private static $refusal = '';

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'trashed_post', array( self::class, 'on_trashed' ) );
		add_action( 'untrashed_post', array( self::class, 'on_untrashed' ) );
	}

	// ─── una por centro ───────────────────────────────────────────────────

	/**
	 * Whether a brand new application lost the race to another of the same school.
	 *
	 * La unicidad es una búsqueda, no un índice (ADR-0018): dos envíos a la vez
	 * pueden crear dos. Después de crear se vuelve a mirar, y si hay otra
	 * publicada de ese centro con un ID más bajo, la nueva se borra para
	 * siempre —no tiene historial ni avisos todavía— y {@see why_not_saved()}
	 * dice por qué.
	 *
	 * @param int    $id           The application just created, its centre code already written.
	 * @param int    $procedure_id Procedure post ID.
	 * @param string $code         School code.
	 * @return bool Whether it lost, and is gone.
	 */
	public static function lost_race( int $id, int $procedure_id, string $code ): bool {
		$ids = get_posts(
			array(
				'post_type'        => ApplicationPostType::POST_TYPE,
				'post_parent'      => $procedure_id,
				'post_status'      => 'publish',
				'numberposts'      => 1,
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'meta_key'         => ApplicationMetaKeys::CENTRE_CODE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- una por centro (ADR-0018).
				'meta_value'       => $code, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- ídem.
				'suppress_filters' => true,
			)
		);
		if ( ! is_array( $ids ) || array() === $ids || (int) $ids[0] === $id ) {
			return false;
		}
		wp_delete_post( $id, true );
		self::$refusal = self::DUPLICATE;
		return true;
	}

	/**
	 * Why the last save kept nothing, in words: the race, or the generic text.
	 *
	 * @param string $generic What to say when it was a plain failure.
	 * @return string
	 */
	public static function why_not_saved( string $generic ): string {
		return '' !== self::$refusal ? self::$refusal : $generic;
	}

	/**
	 * Forget why the last save kept nothing: a new save starts clean.
	 *
	 * @return void
	 */
	public static function forget_refusal(): void {
		self::$refusal = '';
	}

	// ─── retirar y restaurar ───────────────────────────────────────────────

	/**
	 * Send an application to the bin.
	 *
	 * @param int    $application_id Application ID.
	 * @param int    $user_id        Who withdraws it.
	 * @param string $by             {@see BY_APPLICANT} or {@see BY_REVIEWER}.
	 * @param string $reason         Why, in words; whoever reviews has to give one.
	 * @return bool Whether it is in the bin now.
	 */
	public static function withdraw( int $application_id, int $user_id, string $by, string $reason = '' ): bool {
		if ( ApplicationPostType::POST_TYPE !== get_post_type( $application_id ) ) {
			return false;
		}
		self::$context = array(
			'user'   => $user_id,
			'by'     => $by,
			'reason' => $reason,
		);
		try {
			self::send_to_trash( $application_id );
		} finally {
			self::$context = null;
		}
		return 'trash' === get_post_status( $application_id );
	}

	/**
	 * Send an application to the bin, even where the core bin is switched off.
	 *
	 * Con la papelera activa es `wp_trash_post()`. Desactivada —`EMPTY_TRASH_DAYS`
	 * a 0—, `wp_trash_post()` borraría para siempre la solicitud con sus
	 * documentos y su historial; aquí se hace lo mismo que hace el núcleo con
	 * la papelera activa, con los mismos filtros y acciones, para que retirar
	 * siga siendo reversible (ADR-0044).
	 *
	 * @param int $application_id Application ID.
	 * @return void
	 */
	private static function send_to_trash( int $application_id ): void {
		if ( ApplicationActions::trash_enabled() ) {
			wp_trash_post( $application_id );
			return;
		}
		$post = get_post( $application_id );
		if ( ! $post instanceof \WP_Post || 'trash' === $post->post_status ) {
			return;
		}
		$previous_status = $post->post_status;
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- el filtro del núcleo, como en wp_trash_post().
		if ( null !== apply_filters( 'pre_trash_post', null, $post, $previous_status ) ) {
			return;
		}
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- la acción del núcleo, como en wp_trash_post().
		do_action( 'wp_trash_post', $application_id, $previous_status );
		add_post_meta( $application_id, '_wp_trash_meta_status', $previous_status );
		add_post_meta( $application_id, '_wp_trash_meta_time', time() );
		$updated = wp_update_post(
			array(
				'ID'          => $application_id,
				'post_status' => 'trash',
			)
		);
		if ( ! $updated ) {
			return;
		}
		wp_trash_post_comments( $application_id );
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- la acción del núcleo: el historial y el correo la escuchan.
		do_action( 'trashed_post', $application_id, $previous_status );
	}

	/**
	 * Tell whoever listens that an application has just gone to the bin.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public static function on_trashed( $post_id ): void {
		$post_id = (int) $post_id;
		if ( ApplicationPostType::POST_TYPE !== get_post_type( $post_id ) ) {
			return;
		}
		// La papelera del núcleo se vacía sola a los EMPTY_TRASH_DAYS días, y
		// lo que mira es esta meta: sin ella, una retirada no caduca y no se
		// pierden ni sus documentos ni su historial (ADR-0044).
		delete_post_meta( $post_id, '_wp_trash_meta_time' );
		$ctx = self::$context ?? array(
			'user'   => get_current_user_id(),
			'by'     => self::BY_DESK,
			'reason' => '',
		);

		/**
		 * An application has just been withdrawn: it is in the bin.
		 *
		 * @param int    $application_id Application ID.
		 * @param int    $user_id        Account that withdrew it.
		 * @param string $by             `applicant`, `reviewer` or `desk`.
		 * @param string $reason         Why, when whoever reviews withdrew it.
		 */
		do_action( 'prc_application_withdrawn', $post_id, (int) $ctx['user'], (string) $ctx['by'], (string) $ctx['reason'] );
	}

	/**
	 * Bring a withdrawn application back from the bin, as it was.
	 *
	 * Si el centro presentó otra entretanto, WordPress no la saca
	 * ({@see ApplicationActions::guard_untrash()}).
	 *
	 * @param int $application_id Application ID.
	 * @return bool Whether it is published again.
	 */
	public static function restore( int $application_id ): bool {
		if ( ApplicationPostType::POST_TYPE !== get_post_type( $application_id ) ) {
			return false;
		}
		wp_untrash_post( $application_id );
		return 'publish' === get_post_status( $application_id );
	}

	/**
	 * Tell whoever listens that an application is back from the bin.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public static function on_untrashed( $post_id ): void {
		$post_id = (int) $post_id;
		if ( ApplicationPostType::POST_TYPE !== get_post_type( $post_id ) ) {
			return;
		}

		/**
		 * A withdrawn application has just been restored.
		 *
		 * @param int $application_id Application ID.
		 * @param int $user_id        Account that restored it.
		 */
		do_action( 'prc_application_restored', $post_id, get_current_user_id() );
	}

	/**
	 * The withdrawn applications of a procedure, most recently withdrawn first.
	 *
	 * @param int $procedure_id Procedure ID.
	 * @return \WP_Post[]
	 */
	public static function withdrawn( int $procedure_id ): array {
		if ( $procedure_id <= 0 ) {
			return array();
		}
		$posts = get_posts(
			array(
				'post_type'        => ApplicationPostType::POST_TYPE,
				'post_parent'      => $procedure_id,
				'post_status'      => 'trash',
				'numberposts'      => -1,
				'orderby'          => 'modified',
				'order'            => 'DESC',
				'suppress_filters' => true,
			)
		);
		return is_array( $posts ) ? $posts : array();
	}

	/**
	 * How many withdrawn applications a procedure has.
	 *
	 * @param int $procedure_id Procedure ID.
	 * @return int
	 */
	public static function withdrawn_count( int $procedure_id ): int {
		if ( $procedure_id <= 0 ) {
			return 0;
		}
		$ids = get_posts(
			array(
				'post_type'        => ApplicationPostType::POST_TYPE,
				'post_parent'      => $procedure_id,
				'post_status'      => 'trash',
				'numberposts'      => -1,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => true,
			)
		);
		return is_array( $ids ) ? count( $ids ) : 0;
	}

	// ─── la prórroga y la nota interna ─────────────────────────────────────

	/**
	 * Set, change or take away the extension of an application.
	 *
	 * Sin cambio no escribe, ni anota, ni avisa.
	 *
	 * @param int    $application_id Application ID.
	 * @param int    $user_id        Who does it.
	 * @param string $until          Last day, Y-m-d; empty to take it away.
	 * @return bool Whether something changed.
	 */
	public static function extend( int $application_id, int $user_id, string $until ): bool {
		$antes = ApplicationActions::late_until( $application_id );
		if ( $antes === $until ) {
			return false;
		}
		if ( '' === $until ) {
			delete_post_meta( $application_id, ApplicationMetaKeys::LATE_UNTIL );
		} else {
			update_post_meta( $application_id, ApplicationMetaKeys::LATE_UNTIL, $until );
		}

		/**
		 * The extension of an application has just been set, changed or taken away.
		 *
		 * @param int    $application_id Application ID.
		 * @param string $until          Last day now, Y-m-d; empty when taken away.
		 * @param string $previous       Last day before; empty when it had none.
		 * @param int    $user_id        Account that did it.
		 */
		do_action( 'prc_application_extended', $application_id, $until, $antes, $user_id );
		return true;
	}

	/**
	 * Store the internal note of whoever reviews.
	 *
	 * Solo la ven quienes revisan: ni el centro, ni quien consulta, ni ningún
	 * correo. El historial anota que cambió, nunca qué dice.
	 *
	 * @param int    $application_id Application ID.
	 * @param int    $user_id        Who writes it.
	 * @param string $note           The note; empty to clear it.
	 * @return bool Whether it changed.
	 */
	public static function set_internal_note( int $application_id, int $user_id, string $note ): bool {
		$note  = trim( $note );
		$antes = trim( (string) get_post_meta( $application_id, ApplicationMetaKeys::INTERNAL_NOTE, true ) );
		if ( $antes === $note ) {
			return false;
		}
		update_post_meta( $application_id, ApplicationMetaKeys::INTERNAL_NOTE, wp_slash( $note ) );

		/**
		 * The internal note of an application has just changed.
		 *
		 * @param int $application_id Application ID.
		 * @param int $user_id        Account that wrote it.
		 */
		do_action( 'prc_internal_note_changed', $application_id, $user_id );
		return true;
	}

	// ─── en nombre de un centro ────────────────────────────────────────────

	/**
	 * Create an empty application on behalf of a school, in «presentada».
	 *
	 * Lo que se comprueba antes —quién, qué centro, si ya tiene, si cumple—
	 * es de {@see ApplicationActions::why_not_submit_for()}. Aquí se crea la
	 * solicitud vacía, con la prórroga hasta la que el centro puede
	 * completarla con su formulario de siempre, y marcada con quién la creó.
	 * No manda ningún correo: el catálogo no trae la dirección del centro.
	 *
	 * @param int    $procedure_id Procedure ID.
	 * @param int    $user_id      Who reviews and submits it.
	 * @param string $code         School code, already checked.
	 * @param string $until        Last day of the extension, Y-m-d, already checked.
	 * @param string $ineligible   Why the school does not meet the requirements; empty when it does.
	 * @return int Application ID; 0 on failure.
	 */
	public static function submit_for( int $procedure_id, int $user_id, string $code, string $until, string $ineligible = '' ): int {
		$centro = ApplicationActions::catalogue_centre( $code );
		if ( array() === $centro ) {
			return 0;
		}
		$codigo = (string) $centro['code'];
		$nombre = (string) ( $centro['name'] ?? '' );
		$id     = wp_insert_post(
			array(
				'post_type'   => ApplicationPostType::POST_TYPE,
				'post_parent' => $procedure_id,
				'post_author' => $user_id,
				'post_status' => 'publish',
				'post_title'  => wp_slash( ( '' !== $nombre ? $nombre : $codigo ) . ' — ' . (string) get_post_field( 'post_title', $procedure_id ) ),
			),
			true
		);
		if ( is_wp_error( $id ) || ! $id ) {
			return 0;
		}
		$id = (int) $id;
		foreach ( array(
			ApplicationMetaKeys::APPLICANT_TYPE => ApplicationMetaKeys::TYPE_CENTRE,
			ApplicationMetaKeys::CENTRE_CODE    => $codigo,
			ApplicationMetaKeys::CENTRE_NAME    => $nombre,
			ApplicationMetaKeys::REVIEW_STATE   => ApplicationMetaKeys::REVIEW_SUBMITTED,
			ApplicationMetaKeys::LATE_UNTIL     => $until,
			ApplicationMetaKeys::CREATED_BY     => $user_id,
		) as $clave => $valor ) {
			update_post_meta( $id, $clave, wp_slash( $valor ) );
		}
		// Una por centro: si el centro presentó la suya entretanto, gana la suya.
		if ( self::lost_race( $id, $procedure_id, $codigo ) ) {
			return 0;
		}

		/**
		 * An application has just been submitted on behalf of a school.
		 *
		 * @param int                  $application_id Application ID.
		 * @param int                  $user_id        Account that submitted it.
		 * @param array<string, mixed> $data           `code`, `until` and `ineligible`: why it did not meet the requirements, when it was submitted anyway.
		 */
		do_action(
			'prc_application_created_for',
			$id,
			$user_id,
			array(
				'code'       => $codigo,
				'until'      => $until,
				'ineligible' => $ineligible,
			)
		);
		return $id;
	}

	/**
	 * Whether an application was submitted on behalf of its school and the school has not completed it yet.
	 *
	 * @param int $application_id Application ID.
	 * @return bool
	 */
	public static function pending_for_school( int $application_id ): bool {
		$creada_por = (int) get_post_meta( $application_id, ApplicationMetaKeys::CREATED_BY, true );
		return $creada_por > 0 && (int) get_post_field( 'post_author', $application_id ) === $creada_por;
	}

	/**
	 * The school takes over an application submitted on its behalf, the first time it saves it.
	 *
	 * Quien la completa pasa a ser quien la presenta (`post_author`): es a
	 * quien van el acuse y los avisos de revisión. Quién la creó sigue en
	 * `prc_created_by`.
	 *
	 * @param int $application_id Application ID.
	 * @param int $user_id        Account of the school that saves it.
	 * @return bool Whether it was taken over now.
	 */
	public static function adopt( int $application_id, int $user_id ): bool {
		if ( $user_id <= 0 || ! self::pending_for_school( $application_id ) ) {
			return false;
		}
		$hecho = wp_update_post(
			array(
				'ID'          => $application_id,
				'post_author' => $user_id,
			),
			true
		);
		return ! is_wp_error( $hecho ) && (int) $hecho > 0;
	}

	/**
	 * Whether the commitments of an existing application were already accepted.
	 *
	 * Una solicitud presentada por el centro ya los aceptó; una presentada en
	 * su nombre, no, hasta que el centro la complete.
	 *
	 * @param array<string, mixed> $application The application as ApplyForm reads it; empty for none.
	 * @return bool
	 */
	public static function accepted( array $application ): bool {
		return array() !== $application && ! self::pending_for_school( (int) ( $application['id'] ?? 0 ) );
	}
}
