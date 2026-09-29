<?php
/**
 * Native WordPress edit locks, shared by the application and wp-admin.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\Access\ProcedureAccess;

/**
 * Que dos personas no se pisen editando el mismo procedimiento.
 *
 * No se inventa ningún bloqueo: se usa **el nativo de WordPress** —la meta
 * `_edit_lock`, `wp_check_post_lock()` y `wp_set_post_lock()`—, que es
 * exactamente el mismo que usa el escritorio (ADR-0008). Si alguien abre el
 * procedimiento en el editor de WordPress y otra persona en el taller del
 * aplicativo, **se ven**. Un bloqueo propio no lo haría, y habría dos verdades
 * sobre quién está editando.
 *
 * El bloqueo es **del procedimiento**: quien pregunta por una solicitud suya
 * pasa por {@see ProcedureAccess::root_id()} y no tiene que acordarse.
 *
 * Las cuatro piezas:
 *
 * | {@see owner()}             | quién lo tiene, sin caducar               |
 * | {@see require_available()} | antes de escribir nada: si hay dueño, 409 |
 * | {@see claim()}             | tomarlo al abrir, o decir quién lo tiene  |
 * | {@see release()}           | soltar **solo el propio**                 |
 *
 * Y el aviso ({@see render()}), que dice el nombre de quien lo tiene —no
 * «alguien»— y ofrece dos salidas: consultarlo, o tomar posesión por POST con
 * su nonce.
 */
final class EditLock {

	/**
	 * Hidden field naming the operation, so nothing else picks the POST up.
	 */
	public const FIELD_DO = 'prc_lock_do';

	/**
	 * Hidden field with the post the takeover is about.
	 */
	public const FIELD_POST = 'prc_lock_post';

	/**
	 * Nonce field of the takeover form.
	 */
	public const NONCE_FIELD = 'prc_lock_nonce';

	/**
	 * The only operation this class accepts.
	 */
	public const OP_TAKEOVER = 'takeover';

	/**
	 * Register the takeover handler.
	 *
	 * Después de que el CPT y sus capacidades estén registrados (init 10 y
	 * 11), y antes de que se pinte nada: aquí todavía se puede redirigir.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'handle' ), 20 );
	}

	/**
	 * Nonce action of the takeover of one procedure.
	 *
	 * @param int $procedure_id Procedure post ID.
	 * @return string
	 */
	public static function nonce_action( int $procedure_id ): string {
		return 'prc_lock_takeover_' . $procedure_id;
	}

	/**
	 * Who else holds an unexpired lock on the procedure this post belongs to.
	 *
	 * `wp_check_post_lock()` contesta «otra persona», así que a quien tiene el
	 * bloqueo le dice que no hay ninguno: es justo lo que hace falta aquí.
	 *
	 * @param int $post_id Procedure, or an application under it.
	 * @return int User ID, 0 when the procedure is free.
	 */
	public static function owner( int $post_id ): int {
		$procedure_id = ProcedureAccess::root_id( $post_id );
		if ( $procedure_id <= 0 ) {
			return 0;
		}
		require_once ABSPATH . 'wp-admin/includes/post.php';
		return (int) wp_check_post_lock( $procedure_id );
	}

	/**
	 * Reject the write before a single field, term or status can change.
	 *
	 * Va **antes** de escribir y no después, que es lo único que sirve: quien
	 * perdió el bloqueo tiene la pantalla vieja delante y su envío traería
	 * los campos con lo de hace media hora.
	 *
	 * @param int $post_id Procedure, or an application under it.
	 * @return void
	 */
	public static function require_available( int $post_id ): void {
		$owner = self::owner( $post_id );
		if ( $owner <= 0 ) {
			return;
		}
		wp_die(
			esc_html(
				sprintf(
					'%s está editando este procedimiento ahora mismo, aquí o en el escritorio de WordPress. Vuelva atrás y tome posesión antes de guardar: así no se pisa lo que la otra persona esté escribiendo.',
					self::name( $owner )
				)
			),
			'Edición bloqueada',
			array(
				'response'  => 409,
				'back_link' => true,
			)
		);
	}

	/**
	 * How the procedure stands right now: libre, o de quién es. No escribe nada.
	 *
	 * Esto es lo que llama el `model()` de cada pantalla, y por eso no toma el
	 * bloqueo: el modelo decide qué se pinta y no muta. Tomarlo es de
	 * {@see claim()}, que va en el `render()`, cuando la pantalla se le está
	 * enseñando a alguien de verdad.
	 *
	 * A quien solo puede consultar —un procedimiento histórico, por ejemplo—
	 * no se le cuenta ningún bloqueo: no va a escribir, así que ni le estorba
	 * ni tiene por qué quitárselo a quien sí puede.
	 *
	 * @param int  $post_id  Procedure, or an application under it.
	 * @param bool $can_edit Whether this person may write here at all.
	 * @return array{procedure_id:int, owner:int, name:string, lock:string}
	 */
	public static function status( int $post_id, bool $can_edit ): array {
		$procedure_id = ProcedureAccess::root_id( $post_id );
		if ( $procedure_id <= 0 || ! $can_edit ) {
			return self::none();
		}
		$owner = self::owner( $procedure_id );

		return array(
			'procedure_id' => $procedure_id,
			'owner'        => $owner,
			'name'         => $owner > 0 ? self::name( $owner ) : '',
			// Lo que el Heartbeat renueva; lo rellena claim() y solo para quien
			// tenga el bloqueo, porque renovar uno ajeno no tendría sentido.
			'lock'         => '',
		);
	}

	/**
	 * Take the lock of a free procedure, so the next person sees it taken.
	 *
	 * @param array{procedure_id:int, owner:int, name:string, lock:string} $lock What status() returned.
	 * @return array{procedure_id:int, owner:int, name:string, lock:string}
	 */
	public static function claim( array $lock ): array {
		if ( (int) $lock['procedure_id'] <= 0 || (int) $lock['owner'] > 0 ) {
			return $lock;
		}
		require_once ABSPATH . 'wp-admin/includes/post.php';
		$puesto       = wp_set_post_lock( (int) $lock['procedure_id'] );
		$lock['lock'] = is_array( $puesto ) ? implode( ':', $puesto ) : '';

		return $lock;
	}

	/**
	 * No lock to talk about: nothing to paint and nothing to renew.
	 *
	 * @return array{procedure_id:int, owner:int, name:string, lock:string}
	 */
	public static function none(): array {
		return array(
			'procedure_id' => 0,
			'owner'        => 0,
			'name'         => '',
			'lock'         => '',
		);
	}

	/**
	 * Release the lock, and only when it still is this person's own.
	 *
	 * Se compara el usuario que trae la meta antes de borrarla: si mientras
	 * tanto otra persona tomó posesión, el bloqueo es suyo y no se le quita.
	 * Se le pasa el valor exacto a `delete_post_meta()` por lo mismo, que es lo
	 * que hace la comprobación atómica en la base de datos.
	 *
	 * @param int $post_id Procedure, or an application under it.
	 * @return void
	 */
	public static function release( int $post_id ): void {
		$procedure_id = ProcedureAccess::root_id( $post_id );
		if ( $procedure_id <= 0 ) {
			return;
		}
		$lock  = (string) get_post_meta( $procedure_id, '_edit_lock', true );
		$trozo = explode( ':', $lock );
		if ( isset( $trozo[1] ) && get_current_user_id() === (int) $trozo[1] ) {
			delete_post_meta( $procedure_id, '_edit_lock', $lock );
		}
	}

	/**
	 * Apply a «Tomar posesión» POST.
	 *
	 * Por POST y con su nonce, como toda mutación del aplicativo: tomarle el
	 * procedimiento a otra persona por un enlace pegado en un correo no pasa.
	 * Y con `ProcedureAccess::can_edit()` comprobado: el nonce dice que el
	 * envío salió de nuestra pantalla, no que quien lo manda pueda editar.
	 *
	 * @return void
	 */
	public static function handle(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- se comprueba abajo, en cuanto se sabe de qué procedimiento es.
		$op = sanitize_key( wp_unslash( (string) ( $_POST[ self::FIELD_DO ] ?? '' ) ) );
		if ( self::OP_TAKEOVER !== $op ) {
			return;
		}
		$post_id = absint( wp_unslash( $_POST[ self::FIELD_POST ] ?? 0 ) );
		$nonce   = sanitize_text_field( wp_unslash( (string) ( $_POST[ self::NONCE_FIELD ] ?? '' ) ) );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$procedure_id = ProcedureAccess::root_id( $post_id );
		if ( $procedure_id <= 0 || false === wp_verify_nonce( $nonce, self::nonce_action( $procedure_id ) ) ) {
			return;
		}

		if ( ! ProcedureAccess::can_edit( get_current_user_id(), $procedure_id ) ) {
			wp_die(
				esc_html( ProcedureAccess::why_not_editable( get_current_user_id(), $procedure_id ) ),
				'Sin permiso',
				array(
					'response'  => 403,
					'back_link' => true,
				)
			);
		}

		require_once ABSPATH . 'wp-admin/includes/post.php';
		wp_set_post_lock( $procedure_id );

		// A la misma pantalla desde la que se pidió.
		Shell::leave( Shell::back_url( 'workspace' ) );
	}

	/**
	 * The takeover notice: who has the procedure, and the two ways out.
	 *
	 * Un `<dialog>` nativo y no SweetAlert2: esto no es una confirmación, es
	 * el estado de la pantalla, y tiene que verse **también sin JavaScript**.
	 * Un `<dialog open>` lo pinta el navegador solo. El mismo `<dialog>` es el
	 * que el Heartbeat abre con `showModal()` cuando el bloqueo se pierde sin
	 * recargar.
	 *
	 * @param array{procedure_id:int, owner:int, name:string, lock:string} $lock What claim() returned.
	 * @return string Empty when there is no procedure to lock.
	 */
	public static function render( array $lock ): string {
		$procedure_id = (int) $lock['procedure_id'];
		if ( $procedure_id <= 0 ) {
			return '';
		}
		$owner = (int) $lock['owner'];

		// El guion del bloqueo vive en `assets/js/prc-app.js` y necesita el
		// Heartbeat del núcleo, que no se encola en la web pública.
		wp_enqueue_script( 'heartbeat' );

		ob_start();
		?>
		<div id="prc-edit-lock"
			data-post-id="<?php echo esc_attr( (string) $procedure_id ); ?>"
			data-lock="<?php echo esc_attr( (string) $lock['lock'] ); ?>"
			data-release-nonce="<?php echo esc_attr( wp_create_nonce( 'update-post_' . $procedure_id ) ); ?>"
			data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
			<dialog id="prc-lock-dialog" class="prc-dialogo prc-dialogo-bloqueo" aria-labelledby="prc-lock-title"<?php echo $owner > 0 ? ' open' : ''; ?>>
				<h2 id="prc-lock-title">Lo está editando otra persona</h2>
				<p>
					<strong id="prc-lock-owner"><?php echo esc_html( $owner > 0 ? (string) $lock['name'] : 'Otra persona' ); ?></strong>
					tiene abierto este procedimiento ahora mismo, aquí o en el escritorio de WordPress.
				</p>
				<p>
					Puede esperar a que termine y consultarlo mientras tanto, o tomar posesión y
					editarlo usted: la otra persona dejará de poder guardar y verá este mismo aviso.
				</p>
				<form class="prc-acciones" method="post" action="">
					<?php wp_nonce_field( self::nonce_action( $procedure_id ), self::NONCE_FIELD ); ?>
					<input type="hidden" name="<?php echo esc_attr( self::FIELD_DO ); ?>" value="<?php echo esc_attr( self::OP_TAKEOVER ); ?>" />
					<input type="hidden" name="<?php echo esc_attr( self::FIELD_POST ); ?>" value="<?php echo esc_attr( (string) $procedure_id ); ?>" />
					<a class="<?php echo esc_attr( Assets::button_class() ); ?>" href="<?php echo esc_url( self::workspace_url() ); ?>">Dejarlo y volver a mis procedimientos</a>
					<button type="submit" class="<?php echo esc_attr( Assets::button_class( true ) ); ?>">Tomar posesión</button>
				</form>
			</dialog>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Where «dejarlo» goes: the workspace, or the site root as a last resort.
	 *
	 * @return string
	 */
	private static function workspace_url(): string {
		$url = Shell::url( 'workspace' );
		return '' !== $url ? $url : home_url( '/' );
	}

	/**
	 * The name of whoever holds the lock. Nunca «alguien».
	 *
	 * @param int $user_id User ID.
	 * @return string
	 */
	private static function name( int $user_id ): string {
		$user = get_userdata( $user_id );
		if ( false === $user || '' === (string) $user->display_name ) {
			return 'Otra persona';
		}
		return (string) $user->display_name;
	}
}
