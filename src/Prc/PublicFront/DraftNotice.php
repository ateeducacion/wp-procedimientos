<?php
/**
 * What a procedure that is not published says to whoever cannot see it yet.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\PostType\ProcedurePostType;
use Prc\PublicFront\View\ProcedureChrome;

/**
 * Un procedimiento en borrador no es un «no se encontró la página» (ADR-0047).
 *
 * El enlace de una convocatoria que se está preparando circula antes de
 * tiempo: se comparte para revisarlo, o alguien lo encuentra. WordPress
 * contesta con su 404 de siempre, que dice que la página no existe —y sí
 * existe— y no deja entrar a quien la prepara. Aquí, en vez de eso, una página
 * corta que dice que el procedimiento **está en borrador**, que se informará
 * cuando se publique, y un enlace de **acceso para responsables**.
 *
 * Con la sesión iniciada y sin permiso para verlo, lo dice también: «ha
 * iniciado sesión como X y esa cuenta no puede verlo». Es lo que separa «me
 * falta permiso» de «no he iniciado sesión» sin tener que preguntar a nadie.
 *
 * No dice el título ni nada del procedimiento: un borrador puede llevar lo que
 * todavía no se puede contar. Lo que está en la papelera sigue dando un 404.
 * Quien sí puede verlo no pasa por aquí: WordPress encuentra la entrada, no
 * hay 404 y la ficha la pinta {@see ProcedureView}.
 */
final class DraftNotice {

	/**
	 * Statuses of something that is being prepared, not gone.
	 */
	public const STATUSES = array( 'draft', 'pending', 'future', 'private' );

	/**
	 * Before WordPress picks the 404 template, and before redirect_canonical guesses another page.
	 */
	public const PRIORITY = 1;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'template_redirect', array( self::class, 'maybe_show' ), self::PRIORITY );
	}

	/**
	 * Serve the notice instead of the 404 when the request is a procedure being prepared.
	 *
	 * @return void
	 */
	public static function maybe_show(): void {
		if ( is_admin() || ! is_404() ) {
			return;
		}
		$id = self::requested();
		if ( $id <= 0 || ! self::is_being_prepared( $id ) ) {
			return;
		}

		status_header( 404 );
		nocache_headers();
		Shell::send_header( 'Content-Type: text/html; charset=utf-8' );
		Shell::send_header( 'X-Robots-Tag: noindex' );
		echo self::document( $id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado en document().
		Shell::leave();
	}

	/**
	 * Whether this procedure exists and is still being prepared.
	 *
	 * @param int $post_id Procedure.
	 * @return bool
	 */
	public static function is_being_prepared( int $post_id ): bool {
		if ( ProcedurePostType::POST_TYPE !== get_post_type( $post_id ) ) {
			return false;
		}
		return in_array( (string) get_post_status( $post_id ), self::STATUSES, true );
	}

	/**
	 * The procedure the 404 was about, 0 when it is not one.
	 *
	 * Dos formas de pedirlo: por número (`?post_type=prc_procedure&p=N`, que
	 * es el enlace de un borrador sin nombre) y por la ruta bajo la base del
	 * tipo (`/procedimiento/<nombre>/`).
	 *
	 * @return int
	 */
	public static function requested(): int {
		foreach ( array( 'p', 'page_id' ) as $clave ) {
			$id = absint( get_query_var( $clave ) );
			if ( $id > 0 && ProcedurePostType::POST_TYPE === get_post_type( $id ) ) {
				return $id;
			}
		}
		foreach ( array( ProcedurePostType::POST_TYPE, 'name', 'pagename' ) as $clave ) {
			$ruta = trim( (string) get_query_var( $clave ), '/' );
			if ( '' === $ruta ) {
				continue;
			}
			$post = get_page_by_path( $ruta, OBJECT, ProcedurePostType::POST_TYPE );
			if ( $post instanceof \WP_Post ) {
				return (int) $post->ID;
			}
		}
		return 0;
	}

	/**
	 * The whole notice, as its own small document.
	 *
	 * @param int $post_id Procedure.
	 * @return string
	 */
	public static function document( int $post_id ): string {
		$aqui = (string) get_permalink( $post_id );

		$cuerpo = '<p>Este procedimiento no ha sido publicado y se encuentra en <strong>modo borrador</strong>. '
			. 'Todavía no se ha publicado la participación de los centros educativos en esta convocatoria.</p>';
		if ( is_user_logged_in() ) {
			$user    = wp_get_current_user();
			$cuerpo .= '<p>' . esc_html(
				sprintf(
					'Ha iniciado sesión como %s, y esa cuenta no puede ver este borrador. Si tiene que prepararlo, pida a la administración del sitio que le asigne el ámbito del procedimiento.',
					'' !== (string) $user->display_name ? $user->display_name : $user->user_login
				)
			) . '</p>';
			$cuerpo .= sprintf(
				'<p><a href="%1$s">Entrar con otra cuenta</a></p>',
				esc_url( wp_logout_url( wp_login_url( $aqui ) ) )
			);
		} else {
			$cuerpo .= '<p>Se informará cuando lo sea, a través de los canales habituales y en la página de inicio de este sitio. '
				. sprintf( '(<a href="%1$s">Acceso para responsables</a>)', esc_url( wp_login_url( $aqui ) ) )
				. '</p>';
		}

		// El logo es de quien despliega: sin configurarlo en `prc_chrome`, no hay (ADR-0009).
		$chrome = ProcedureChrome::chrome();
		$logo   = '' !== (string) $chrome['brand_logo']
			? sprintf(
				'<p class="logo"><img src="%1$s" alt="%2$s" width="300" /></p>',
				esc_url( (string) $chrome['brand_logo'] ),
				esc_attr( (string) $chrome['brand_alt'] )
			)
			: '';

		return '<!doctype html><html lang="es"><head><meta charset="utf-8" />'
			. '<meta name="viewport" content="width=device-width, initial-scale=1" />'
			. '<meta name="robots" content="noindex" />'
			. '<title>' . esc_html( 'Procedimiento en borrador – ' . get_bloginfo( 'name' ) ) . '</title>'
			. '<style>'
			. 'body{background:#f0f0f1;color:#1d2327;font:16px/1.5 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;margin:0;padding:2rem 1rem}'
			. 'main{background:#fff;max-width:40rem;margin:0 auto;padding:1.5rem 2rem;box-shadow:0 1px 3px rgba(0,0,0,.13)}'
			. '.logo img{max-width:100%;height:auto}a{color:#2271b1}'
			. '</style></head><body><main role="main">'
			. $logo
			. $cuerpo
			. '</main></body></html>';
	}
}
