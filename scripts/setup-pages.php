<?php
/**
 * Snippet Name: PRC — Crear las páginas del aplicativo (una sola vez)
 * Description: Crea las páginas de las pantallas del aplicativo, cada una con su shortcode, y las cuelga de la página madre que se indique. Idempotente: a la que ya existe sin su shortcode se lo añade al final, y a la que lo tiene no la toca. En producción se pega en Code Snippets como snippet de ejecución única; en local lo llama `make provision`.
 *
 * Una sola lista de páginas, y no está aquí: los slugs son los de
 * `Shell::SLUGS` y los shortcodes los de `Shell::SHORTCODES`, que es lo que el
 * aplicativo resuelve luego con `get_page_by_path()`. Escribirlos otra vez en
 * este fichero sería una segunda fuente de verdad que se desincroniza en
 * silencio: la pantalla dejaría de tener página y su pestaña desaparecería sin
 * un solo error. Aquí solo se pone el título, que es lo único que el armazón no
 * sabe. La ficha pública del procedimiento no es una página: es el `single`
 * del tipo (ADR-0022) y no tiene shortcode, así que aquí se salta sola.
 *
 * La madre se guarda en la opción `prc_pages_parent`, que es de donde
 * `App::page_slug()` saca la ruta al construir los enlaces.
 *
 * @package Prc
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─── Editar antes de pegarlo en Code Snippets ────────────────────────────────
// Página de la que cuelgan las del aplicativo. En un subsitio propio lo normal
// es dejarlo vacío: las páginas van en la raíz del subsitio.
$prc_padre = '';

// El entorno local lo pasa por variable de entorno; en Code Snippets no la hay.
$prc_padre = '' !== $prc_padre ? $prc_padre : (string) getenv( 'PRC_PAGES_PARENT' );
$prc_padre = trim( $prc_padre, '/' );

if ( ! function_exists( 'prc_page_titles' ) ) {
	/**
	 * Page titles of every screen, by section key of `Shell::SLUGS`.
	 *
	 * @return array<string, string>
	 */
	function prc_page_titles(): array {
		return array(
			'home'      => 'Procedimientos',
			'workspace' => 'Gestión de procedimientos',
			'editor'    => 'Editar procedimiento',
			'apply'     => 'Solicitud',
			'mine'      => 'Mi centro',
		);
	}
}

if ( ! function_exists( 'prc_setup_pages' ) ) {
	/**
	 * Create, complete and hang the application pages.
	 *
	 * @param string $prc_padre Parent page slug; empty for root pages.
	 * @return void
	 * @throws RuntimeException If the application is not loaded, the parent is missing or a page cannot be saved.
	 */
	function prc_setup_pages( string $prc_padre ): void {
		if ( ! class_exists( '\Prc\PublicFront\Shell' ) ) {
			throw new RuntimeException( 'El aplicativo no está cargado (falta Prc\PublicFront\Shell). Ejecute antes `make bundle && make sync-snippets`.' );
		}

		$prc_padre_id = 0;
		if ( '' !== $prc_padre ) {
			$prc_madre = get_page_by_path( $prc_padre );
			if ( ! $prc_madre instanceof WP_Post ) {
				throw new RuntimeException( esc_html( "No existe la página «{$prc_padre}»: créala antes o deja la madre vacía." ) );
			}
			$prc_padre_id = (int) $prc_madre->ID;
		}

		$prc_titulos     = prc_page_titles();
		$prc_creadas     = 0;
		$prc_habia       = 0;
		$prc_completadas = 0;
		$prc_saltadas    = 0;

		foreach ( \Prc\PublicFront\Shell::SLUGS as $prc_seccion => $prc_slug ) {
			$prc_shortcode = \Prc\PublicFront\Shell::SHORTCODES[ $prc_seccion ] ?? '';

			// Una pantalla que todavía no existe no tiene página: lo que se
			// publicaría es una página con el corchete del shortcode escrito a la
			// vista. Cuando la clase entre en el bundle, la siguiente provisión la
			// crea sola.
			if ( '' === $prc_shortcode || ! shortcode_exists( $prc_shortcode ) ) {
				echo esc_html( "La pantalla «{$prc_seccion}» no está en el aplicativo todavía: no se crea «{$prc_slug}»." ) . "\n";
				++$prc_saltadas;
				continue;
			}

			$prc_ruta = '' !== $prc_padre ? $prc_padre . '/' . $prc_slug : $prc_slug;
			$prc_hay  = get_page_by_path( $prc_ruta );

			if ( $prc_hay instanceof WP_Post && has_shortcode( (string) $prc_hay->post_content, $prc_shortcode ) ) {
				// La que ya está con su shortcode no se toca: puede llevar texto
				// propio alrededor, y esto se ejecuta también en producción.
				echo esc_html( "«{$prc_ruta}» ya existía (ID {$prc_hay->ID})." ) . "\n";
				++$prc_habia;
				continue;
			}

			if ( $prc_hay instanceof WP_Post ) {
				// Existe pero sin su shortcode —una página creada a mano como
				// marcador—: se le añade al final, sin tocar lo que ya tuviera.
				$prc_id = wp_update_post(
					array(
						'ID'           => $prc_hay->ID,
						'post_content' => rtrim( (string) $prc_hay->post_content ) . "\n\n<!-- wp:shortcode -->\n[{$prc_shortcode}]\n<!-- /wp:shortcode -->",
					),
					true
				);
				if ( is_wp_error( $prc_id ) ) {
					throw new RuntimeException( esc_html( "No se pudo completar «{$prc_ruta}»: " . $prc_id->get_error_message() ) );
				}
				echo esc_html( "«{$prc_ruta}» existía sin su shortcode (ID {$prc_hay->ID}): se le añadió [{$prc_shortcode}]." ) . "\n";
				++$prc_completadas;
				continue;
			}

			$prc_id = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_name'    => $prc_slug,
					'post_title'   => $prc_titulos[ $prc_seccion ] ?? $prc_slug,
					'post_parent'  => $prc_padre_id,
					'post_content' => "<!-- wp:shortcode -->\n[{$prc_shortcode}]\n<!-- /wp:shortcode -->",
				),
				true
			);
			if ( is_wp_error( $prc_id ) ) {
				throw new RuntimeException( esc_html( "No se pudo crear «{$prc_ruta}»: " . $prc_id->get_error_message() ) );
			}

			echo esc_html( "Creada «{$prc_ruta}» (ID {$prc_id}) con [{$prc_shortcode}]." ) . "\n";
			++$prc_creadas;
		}

		// El aplicativo construye sus enlaces con esta opción (App::page_slug()):
		// sin ella buscaría las páginas en la raíz y no las encontraría.
		update_option( \Prc\App::PAGES_PARENT, $prc_padre );

		echo esc_html(
			sprintf(
				'Páginas del aplicativo: %1$d creada(s), %2$d completada(s) con su shortcode, %3$d ya estaban, %4$d sin pantalla todavía. Madre: %5$s.',
				$prc_creadas,
				$prc_completadas,
				$prc_habia,
				$prc_saltadas,
				'' !== $prc_padre ? '/' . $prc_padre . '/' : 'la raíz del sitio'
			)
		) . "\n";

		// Las reglas de reescritura se guardaron al instalar WordPress, cuando el
		// aplicativo todavía no existía: sin refrescarlas, la ficha pública de
		// cada procedimiento —/procedimiento/<slug>/— responde «Página no
		// encontrada» hasta que alguien entre en Ajustes → Enlaces permanentes.
		// Aquí es donde toca: acaban de cambiar las páginas del aplicativo y ya
		// están registrados los dos tipos de contenido.
		flush_rewrite_rules( true );
		echo "Reglas de enlaces permanentes regeneradas.\n";
	}
}

// En Code Snippets esto corre antes de `init`, y crear páginas tan pronto
// revienta: los `wp_insert_post` disparan ganchos de otros plugins que todavía
// no se han enganchado. Con `wp eval-file` init ya pasó, así que se ejecuta al
// momento.
if ( did_action( 'init' ) ) {
	prc_setup_pages( $prc_padre );
} else {
	add_action(
		'init',
		static function () use ( $prc_padre ) {
			prc_setup_pages( $prc_padre );
		},
		20
	);
}
