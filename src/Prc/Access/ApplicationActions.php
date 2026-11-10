<?php
/**
 * Who may act on one application, and when: withdraw it, restore it, extend
 * it or submit one on behalf of a school.
 *
 * @package Prc
 */

namespace Prc\Access;

use Prc\Domain\CentreCatalog;
use Prc\Domain\CentreEligibility;
use Prc\Domain\ProcedureInput;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ApplicationPostType;
use Prc\PostType\ProcedurePostType;

/**
 * Las reglas de las cuatro actuaciones sobre una solicitud (PLAN-0003, PR 9;
 * ADR-0044): retirarla, restaurarla, darle una prórroga y presentarla en
 * nombre de un centro.
 *
 * Va aparte de {@see ProcedureAccess}, que estaba en el tope de métodos
 * públicos de PHPMD, pero **la decisión sigue enganchada allí**:
 * `ProcedureAccess::map_meta_cap()` resuelve las cuatro meta capacidades de
 * {@see CAPS} preguntando aquí, así que valen igual para la pantalla, para un
 * POST armado a mano, para el escritorio y para la REST. Aquí no se escribe
 * nada: lo que escribe es {@see \Prc\PublicFront\ApplicationOperations}.
 *
 * Cada regla devuelve el motivo en castellano, vacío cuando se puede: la
 * pantalla y el envío rechazado dicen lo mismo.
 */
final class ApplicationActions {

	/**
	 * Meta capacidad: retirar su propia solicitud (quien solicita).
	 */
	public const CAP_WITHDRAW = 'prc_withdraw_application';

	/**
	 * Meta capacidad: sacar de la papelera una solicitud retirada (quien revisa).
	 */
	public const CAP_RESTORE = 'prc_restore_application';

	/**
	 * Meta capacidad: conceder, cambiar o quitar la prórroga de una solicitud (quien revisa).
	 */
	public const CAP_EXTEND = 'prc_extend_application';

	/**
	 * Meta capacidad: presentar una solicitud en nombre de un centro; el
	 * argumento es el procedimiento (quien revisa).
	 */
	public const CAP_SUBMIT_FOR = 'prc_submit_for_centre';

	/**
	 * Each meta capability and the rule that answers it.
	 *
	 * @var array<string, string>
	 */
	public const CAPS = array(
		self::CAP_WITHDRAW   => 'why_not_withdraw',
		self::CAP_RESTORE    => 'why_not_restore',
		self::CAP_EXTEND     => 'why_not_extend',
		self::CAP_SUBMIT_FOR => 'why_not_submit_for',
	);

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_filter( 'pre_untrash_post', array( self::class, 'guard_untrash' ), 10, 2 );
		add_filter( 'pre_delete_post', array( self::class, 'guard_delete' ), 10, 2 );
		add_filter( 'wp_untrash_post_status', array( self::class, 'untrash_status' ), 10, 3 );
	}

	/**
	 * Whether the user holds one of the meta capabilities of {@see CAPS} on that post.
	 *
	 * Lo pregunta `ProcedureAccess::map_meta_cap()`.
	 *
	 * @param string $cap     One of the keys of {@see CAPS}.
	 * @param int    $user_id User ID.
	 * @param int    $post_id Application ID; the procedure for {@see CAP_SUBMIT_FOR}.
	 * @return bool
	 */
	public static function allows( string $cap, int $user_id, int $post_id ): bool {
		$regla = self::CAPS[ $cap ] ?? '';
		return '' !== $regla && '' === (string) call_user_func( array( self::class, $regla ), $user_id, $post_id );
	}

	// ─── retirar (quien solicita) ──────────────────────────────────────────

	/**
	 * Whether the user may withdraw this application now.
	 *
	 * @param int $user_id        User ID.
	 * @param int $application_id Application ID.
	 * @return bool
	 */
	public static function can_withdraw( int $user_id, int $application_id ): bool {
		return '' === self::why_not_withdraw( $user_id, $application_id );
	}

	/**
	 * Why the user may not withdraw this application now, in words.
	 *
	 * Quién: quien puede editarla —el equipo directivo de su centro, o la
	 * persona docente en una individual—. Cuándo: mientras el plazo de
	 * solicitud esté abierto, o mientras dure la prórroga de esa solicitud;
	 * nunca con el procedimiento histórico. Retirar es enviar a la papelera
	 * (ADR-0007): se puede volver a presentar mientras haya plazo.
	 *
	 * @param int $user_id        User ID.
	 * @param int $application_id Application ID.
	 * @return string Empty when they may.
	 */
	public static function why_not_withdraw( int $user_id, int $application_id ): string {
		if ( ! self::is_live( $application_id ) || ! ProcedureAccess::can_view_application( $user_id, $application_id, ProcedureAccess::VIEW_OWN ) ) {
			return 'Solo retira una solicitud el equipo directivo de su centro, o la persona docente que la presentó.';
		}
		$procedure_id = (int) wp_get_post_parent_id( $application_id );
		if ( ProcedurePostType::POST_TYPE !== get_post_type( $procedure_id ) || 'publish' !== get_post_status( $procedure_id ) || ProcedureAccess::is_archived( $procedure_id ) ) {
			return 'Este procedimiento está cerrado: la solicitud ya no se puede retirar.';
		}
		$estado = ProcedureState::of_post( $procedure_id );
		if ( ProcedureState::accepts_applications( $estado ) || ProcedureState::late_open( $estado, self::late_until( $application_id ), ProcedureState::today() ) ) {
			return '';
		}
		return 'El plazo de solicitud está cerrado, así que la solicitud ya no se puede retirar. Si tiene que retirarla, pídalo a quien gestiona el procedimiento.';
	}

	// ─── la prórroga ───────────────────────────────────────────────────────

	/**
	 * The last day of the extension of an application, as stored.
	 *
	 * @param int $application_id Application ID.
	 * @return string Y-m-d; empty for none.
	 */
	public static function late_until( int $application_id ): string {
		if ( $application_id <= 0 ) {
			return '';
		}
		$valor = get_post_meta( $application_id, ApplicationMetaKeys::LATE_UNTIL, true );
		$valor = is_string( $valor ) ? trim( $valor ) : '';
		return ProcedureInput::is_valid_date( $valor ) ? $valor : '';
	}

	/**
	 * Whether the extension of this application still lets its school edit it today.
	 *
	 * La regla es pura y vive en {@see ProcedureState::late_open()}: hasta el
	 * último día incluido, nunca con el procedimiento histórico ni sin publicar.
	 *
	 * @param int $application_id Application ID; 0 for none.
	 * @return bool
	 */
	public static function is_late_open( int $application_id ): bool {
		if ( ! self::is_live( $application_id ) ) {
			return false;
		}
		$late = self::late_until( $application_id );
		return '' !== $late && ProcedureState::late_open( ProcedureState::of_post( (int) wp_get_post_parent_id( $application_id ) ), $late, ProcedureState::today() );
	}

	/**
	 * What the school reads about its extension; empty when it has none alive.
	 *
	 * @param int $application_id Application ID.
	 * @return string
	 */
	public static function late_notice( int $application_id ): string {
		if ( ! self::is_late_open( $application_id ) ) {
			return '';
		}
		return 'Esta solicitud tiene de plazo hasta el ' . self::day( self::late_until( $application_id ) ) . ', incluido, para completarla o modificarla, aunque el plazo general esté cerrado.';
	}

	/**
	 * Why a date may not be the last day of an extension, in words. Pure.
	 *
	 * @param string $until The day asked for, Y-m-d.
	 * @param string $today Reference day, Y-m-d.
	 * @return string Empty when it may.
	 */
	public static function why_not_late_date( string $until, string $today ): string {
		if ( ! ProcedureInput::is_valid_date( $until ) ) {
			return 'Escriba el último día de la prórroga como una fecha válida.';
		}
		return $until < $today ? 'La prórroga no puede terminar antes de hoy.' : '';
	}

	/**
	 * Why the user may not give, change or take away the extension of this application.
	 *
	 * Quien revisa ese procedimiento; nunca con el procedimiento histórico.
	 * Quien solicita no se concede prórrogas y quien consulta no hace nada.
	 *
	 * @param int $user_id        User ID.
	 * @param int $application_id Application ID.
	 * @return string Empty when they may.
	 */
	public static function why_not_extend( int $user_id, int $application_id ): string {
		if ( ! self::is_live( $application_id ) ) {
			return 'Esa solicitud no existe o está retirada.';
		}
		return self::why_not_reviewer( $user_id, $application_id );
	}

	// ─── retirar y restaurar (quien revisa) ────────────────────────────────

	/**
	 * Why the user may not send this application to the bin from the review.
	 *
	 * Es «se inscribió por error»: lo hace quien revisa, con motivo, y se
	 * puede deshacer desde «Retiradas».
	 *
	 * @param int $user_id        User ID.
	 * @param int $application_id Application ID.
	 * @return string Empty when they may.
	 */
	public static function why_not_retire( int $user_id, int $application_id ): string {
		return self::why_not_extend( $user_id, $application_id );
	}

	/**
	 * Why the user may not restore this withdrawn application.
	 *
	 * Quien revisa, y solo si el centro —o la persona docente— no ha
	 * presentado otra entretanto: restaurarla haría dos.
	 *
	 * @param int $user_id        User ID.
	 * @param int $application_id Application ID.
	 * @return string Empty when they may.
	 */
	public static function why_not_restore( int $user_id, int $application_id ): string {
		if ( ApplicationPostType::POST_TYPE !== get_post_type( $application_id ) || 'trash' !== get_post_status( $application_id ) ) {
			return 'Esa solicitud no está retirada.';
		}
		$porque = self::why_not_reviewer( $user_id, $application_id );
		if ( '' !== $porque ) {
			return $porque;
		}
		return null !== self::live_twin( $application_id )
			? 'No se puede restaurar: el centro ya ha presentado otra solicitud a este procedimiento, y serían dos. Si hay que quedarse con esta, retire antes la otra.'
			: '';
	}

	/**
	 * Keep WordPress from restoring an application that would make two.
	 *
	 * Por cualquier puerta —el aplicativo o el escritorio—: si el centro ya
	 * presentó otra, la retirada se queda en la papelera.
	 *
	 * @param mixed    $untrash Null to go on; anything else stops it.
	 * @param \WP_Post $post    The post.
	 * @return mixed
	 */
	public static function guard_untrash( $untrash, $post ) {
		if ( null !== $untrash || ! $post instanceof \WP_Post || ApplicationPostType::POST_TYPE !== $post->post_type ) {
			return $untrash;
		}
		// En un procedimiento histórico no se restaura nada, tampoco
		// administración (ADR-0023).
		return null === self::live_twin( (int) $post->ID ) && ! ProcedureAccess::is_archived( (int) $post->ID ) ? null : false;
	}

	/**
	 * Whether the user may send this application to the bin or, from the bin, delete it for good.
	 *
	 * Es lo que `map_meta_cap()` contesta a `delete_post` sobre una solicitud:
	 *
	 * - con el procedimiento histórico, nadie, tampoco administración
	 *   (ADR-0023): ni a la papelera, ni restaurar por el escritorio, que
	 *   pregunta lo mismo;
	 * - en la papelera —borrar para siempre, «Vaciar papelera»—, solo quien
	 *   administra el aplicativo: quien revisa retira y restaura, no borra;
	 * - publicada —enviarla a la papelera—, quien revisa.
	 *
	 * @param int $user_id        User ID.
	 * @param int $application_id Application ID.
	 * @return bool
	 */
	public static function may_delete( int $user_id, int $application_id ): bool {
		if ( ProcedureAccess::is_archived( $application_id ) ) {
			return false;
		}
		return 'trash' === get_post_status( $application_id )
			? ProcedureAccess::is_manager( $user_id )
			: ProcedureAccess::can_review( $user_id, $application_id );
	}

	/**
	 * Keep a withdrawn application from being deleted for good by anyone but administration.
	 *
	 * La capa de abajo de {@see may_delete()}: vale también para lo que no
	 * pregunta por capacidades, como el vaciado automático de la papelera del
	 * núcleo (`wp_scheduled_delete()`, sin nadie delante). Una retirada se
	 * queda con sus documentos y su historial hasta que administración la
	 * borre a mano.
	 *
	 * @param mixed    $delete Null to go on; anything else stops it.
	 * @param \WP_Post $post   The post.
	 * @return mixed
	 */
	public static function guard_delete( $delete, $post ) {
		if ( null !== $delete || ! $post instanceof \WP_Post || ApplicationPostType::POST_TYPE !== $post->post_type ) {
			return $delete;
		}
		if ( 'trash' !== $post->post_status ) {
			// Con la papelera desactivada, wp_trash_post() borra para siempre:
			// quien quería retirarla perdería documentos e historial. Una
			// solicitud viva solo se borra a propósito, con wp_delete_post().
			return self::trash_enabled() || ! self::inside_core_trash() ? $delete : false;
		}
		return ProcedureAccess::is_manager( get_current_user_id() ) ? null : false;
	}

	/**
	 * Whether the core bin keeps what goes to it, instead of deleting it for good.
	 *
	 * Con `EMPTY_TRASH_DAYS` a 0 —hay servidores que desactivan así la
	 * papelera—, `wp_trash_post()` borra para siempre. La constante no se
	 * puede cambiar en marcha; el filtro `prc_trash_enabled` sí.
	 *
	 * @return bool
	 */
	public static function trash_enabled(): bool {
		return (bool) apply_filters( 'prc_trash_enabled', (bool) EMPTY_TRASH_DAYS );
	}

	/**
	 * Whether the deletion under way comes from inside `wp_trash_post()`.
	 *
	 * El núcleo no deja ningún enganche antes de borrar con la papelera
	 * desactivada: solo la pila de llamadas dice de dónde viene.
	 *
	 * @return bool
	 */
	private static function inside_core_trash(): bool {
		$pila = wp_debug_backtrace_summary( null, 0, false ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_wp_debug_backtrace_summary -- no es depuración: distingue retirar de borrar.
		return is_array( $pila ) && in_array( 'wp_trash_post', $pila, true );
	}

	/**
	 * An application comes back from the bin as it was: published.
	 *
	 * WordPress devuelve lo restaurado como borrador; la ADR-0007 lo quiere así
	 * para el procedimiento, que no debe reaparecer publicado sin mirarlo. Una
	 * solicitud en borrador, en cambio, no la ve nadie: vuelve como estaba.
	 *
	 * @param string $status          Status WordPress would give it.
	 * @param int    $post_id         Post ID.
	 * @param string $previous_status Status before the bin.
	 * @return string
	 */
	public static function untrash_status( $status, $post_id = 0, $previous_status = '' ): string {
		if ( ApplicationPostType::POST_TYPE === get_post_type( (int) $post_id ) && '' !== (string) $previous_status ) {
			return (string) $previous_status;
		}
		return (string) $status;
	}

	/**
	 * The live application of the same school —or teacher— to the same procedure, if any.
	 *
	 * @param int $application_id Application ID, live or withdrawn.
	 * @return int|null The other one; null when there is none.
	 */
	public static function live_twin( int $application_id ): ?int {
		$args = array(
			'post_type'        => ApplicationPostType::POST_TYPE,
			'post_parent'      => (int) wp_get_post_parent_id( $application_id ),
			'post_status'      => 'publish',
			'post__not_in'     => array( $application_id ),
			'numberposts'      => 1,
			'fields'           => 'ids',
			'no_found_rows'    => true,
			'suppress_filters' => true,
		);
		if ( ApplicationMetaKeys::TYPE_TEACHER === get_post_meta( $application_id, ApplicationMetaKeys::APPLICANT_TYPE, true ) ) {
			$args['author']     = (int) get_post_field( 'post_author', $application_id );
			$args['meta_key']   = ApplicationMetaKeys::APPLICANT_TYPE; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- una solicitud individual se identifica por su autor y tipo.
			$args['meta_value'] = ApplicationMetaKeys::TYPE_TEACHER; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- ídem.
		} else {
			$codigo = (string) get_post_meta( $application_id, ApplicationMetaKeys::CENTRE_CODE, true );
			if ( '' === $codigo ) {
				return null;
			}
			$args['meta_key']   = ApplicationMetaKeys::CENTRE_CODE; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- una por centro (ADR-0018).
			$args['meta_value'] = $codigo; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- ídem.
		}
		$ids = get_posts( $args );
		return is_array( $ids ) && array() !== $ids ? (int) $ids[0] : null;
	}

	// ─── presentar en nombre de un centro (quien revisa) ───────────────────

	/**
	 * Why the user may not submit an application on behalf of that school, in words.
	 *
	 * Sin código, si puede hacerlo en ese procedimiento: quien revisa, publicado,
	 * no histórico y para centros —en uno de profesorado cada solicitud es de
	 * una persona—. Con código, además: que el centro esté en el catálogo y
	 * activo, que el procedimiento admita su clase de código, que no tenga ya
	 * solicitud —tampoco retirada: esa se restaura— y que pase los acotados
	 * del procedimiento (ADR-0042), salvo que quien revisa marque
	 * `$override`; el motivo por el que no los pasa queda en `$ineligible`.
	 *
	 * @param int         $user_id      User ID.
	 * @param int         $procedure_id Procedure ID.
	 * @param string      $code         School code; empty for the procedure alone.
	 * @param bool        $override     Whether to go on although it does not meet the requirements.
	 * @param string|null $ineligible   Set to why the school does not meet them; empty when it does.
	 * @return string Empty when they may.
	 */
	public static function why_not_submit_for( int $user_id, int $procedure_id, string $code = '', bool $override = false, ?string &$ineligible = null ): string {
		$ineligible = '';
		if ( ProcedurePostType::POST_TYPE !== get_post_type( $procedure_id ) || 'publish' !== get_post_status( $procedure_id ) ) {
			return 'Ese procedimiento no existe o no está publicado.';
		}
		$porque = self::why_not_reviewer( $user_id, $procedure_id );
		if ( '' !== $porque ) {
			return $porque;
		}
		if ( ProcedureMetaKeys::AUDIENCE_TEACHERS === get_post_meta( $procedure_id, ProcedureMetaKeys::AUDIENCE, true ) ) {
			return 'Este procedimiento es para el profesorado: cada solicitud es de una persona, y no se presenta en nombre de nadie.';
		}
		return '' === trim( $code ) ? '' : self::why_not_centre( $procedure_id, $code, $override, $ineligible );
	}

	/**
	 * The school of a code as the catalogue has it, looked up as typed and in capitals.
	 *
	 * @param string $code School code as typed.
	 * @return array<string, mixed> Empty when it is not in the catalogue.
	 */
	public static function catalogue_centre( string $code ): array {
		$code   = trim( $code );
		$centro = CentreCatalog::find( $code );
		return array() !== $centro ? $centro : CentreCatalog::find( strtoupper( $code ) );
	}

	/**
	 * Why that school may not get an application submitted on its behalf.
	 *
	 * @param int         $procedure_id Procedure ID.
	 * @param string      $code         School code.
	 * @param bool        $override     Whether to go on although it does not meet the requirements.
	 * @param string|null $ineligible   Set to why it does not meet them.
	 * @return string Empty when it may.
	 */
	private static function why_not_centre( int $procedure_id, string $code, bool $override, ?string &$ineligible ): string {
		$centro = self::catalogue_centre( $code );
		if ( array() === $centro ) {
			return 'Ese código no está en el catálogo de centros. Compruébelo: solo se presenta en nombre de un centro del catálogo.';
		}
		if ( isset( $centro['active'] ) && false === $centro['active'] ) {
			return 'Ese centro figura como inactivo en el catálogo.';
		}
		$kind = (string) ( $centro['kind'] ?? '' );
		$kind = isset( ProcedureMetaKeys::centre_kinds()[ $kind ] ) ? $kind : ProcedureMetaKeys::KIND_SCHOOL;
		if ( ! in_array( $kind, ProcedureAccess::centre_kinds( $procedure_id ), true ) ) {
			return ProcedureMetaKeys::KIND_RURAL_GROUP === $kind
				? 'Este procedimiento no admite solicitudes de agrupaciones de escuelas rurales como colectivo.'
				: 'Este procedimiento solo admite solicitudes de agrupaciones de escuelas rurales como colectivo, y ese código es de un centro.';
		}
		$codigo = (string) $centro['code'];
		$antes  = self::existing( $procedure_id, $codigo );
		if ( 'publish' === $antes ) {
			return 'Ese centro ya tiene una solicitud en este procedimiento: ábrala desde la tabla.';
		}
		if ( 'trash' === $antes ) {
			return 'Ese centro tiene una solicitud retirada en este procedimiento: restáurela desde «Retiradas» en vez de crear otra.';
		}
		$ineligible = self::why_not_eligible( $procedure_id, $codigo, $kind );
		if ( '' !== $ineligible && ! $override ) {
			return $ineligible . ' Si aun así hay que presentarla, marque «Aunque no cumpla los requisitos».';
		}
		return '';
	}

	/**
	 * Whether that school already has an application to the procedure: live, withdrawn or none.
	 *
	 * @param int    $procedure_id Procedure ID.
	 * @param string $code         School code.
	 * @return string `publish`, `trash` or empty.
	 */
	private static function existing( int $procedure_id, string $code ): string {
		$ids     = get_posts(
			array(
				'post_type'        => ApplicationPostType::POST_TYPE,
				'post_parent'      => $procedure_id,
				'post_status'      => array( 'publish', 'trash' ),
				'numberposts'      => -1,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'meta_key'         => ApplicationMetaKeys::CENTRE_CODE, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- una por centro (ADR-0018).
				'meta_value'       => $code, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- ídem.
				'suppress_filters' => true,
			)
		);
		$estados = array_map( 'get_post_status', is_array( $ids ) ? $ids : array() );
		if ( in_array( 'publish', $estados, true ) ) {
			return 'publish';
		}
		return in_array( 'trash', $estados, true ) ? 'trash' : '';
	}

	/**
	 * Why that code does not pass the rules of the procedure, in words.
	 *
	 * Los mismos acotados que mira `ProcedureAccess::apply_codes()` al
	 * presentar: titularidad —que no filtra a una agrupación de escuelas
	 * rurales—, tipo, isla y lista cerrada (ADR-0042).
	 *
	 * @param int    $procedure_id Procedure ID.
	 * @param string $code         School code.
	 * @param string $kind         One of ProcedureMetaKeys::centre_kinds().
	 * @return string Empty when it passes.
	 */
	private static function why_not_eligible( int $procedure_id, string $code, string $kind ): string {
		$rules = ProcedureAccess::eligibility( $procedure_id );
		if ( ProcedureMetaKeys::KIND_RURAL_GROUP === $kind ) {
			$rules['ownership'] = array();
		}
		return CentreEligibility::restricted( $rules )
			? CentreEligibility::why_not( $rules, $code, CentreCatalog::find( $code ), array() !== CentreCatalog::all() )
			: '';
	}

	// ─── lo común ──────────────────────────────────────────────────────────

	/**
	 * Why the user may not act as a reviewer on this procedure or application now.
	 *
	 * @param int $user_id User ID.
	 * @param int $post_id Procedure ID, or an application under it.
	 * @return string Empty when they may.
	 */
	private static function why_not_reviewer( int $user_id, int $post_id ): string {
		if ( ! ProcedureAccess::can_review( $user_id, $post_id ) ) {
			return 'Su perfil no gestiona las solicitudes de este procedimiento.';
		}
		return ProcedureAccess::is_archived( $post_id )
			? 'Este procedimiento está marcado como histórico: sus solicitudes ya no se tocan.'
			: '';
	}

	/**
	 * Whether this is a published application.
	 *
	 * @param int $application_id Application ID.
	 * @return bool
	 */
	private static function is_live( int $application_id ): bool {
		return $application_id > 0 && ApplicationPostType::POST_TYPE === get_post_type( $application_id ) && 'publish' === get_post_status( $application_id );
	}

	/**
	 * A Y-m-d day as d-m-Y.
	 *
	 * @param string $ymd Day.
	 * @return string
	 */
	public static function day( string $ymd ): string {
		return '' === $ymd ? '' : implode( '-', array_reverse( explode( '-', $ymd ) ) );
	}
}
