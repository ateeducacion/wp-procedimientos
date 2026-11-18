<?php
/**
 * The help screen, painted from what Help::model() decided.
 *
 * @package Prc
 */

namespace Prc\PublicFront\View;

use Prc\PublicFront\HelpContent;
use Prc\PublicFront\Shell;

/**
 * Solo pinta: no lee la petición, no consulta y no decide.
 *
 * Las pestañas son enlaces de verdad —cada guía es una dirección que el
 * servidor ya sabe pintar— y por eso llevan `aria-current` y no los roles de
 * un `tablist`, que prometerían un comportamiento con flechas que aquí no
 * hay. Debajo, la guía: su título, una línea de para quién es, el índice con
 * un enlace por apartado y los apartados, cada uno con su ancla. Al final, la
 * ayuda técnica.
 */
final class HelpView {

	/**
	 * The screen, from its model.
	 *
	 * @param array<string, mixed> $m What Help::model() returned.
	 * @return string
	 */
	public static function html( array $m ): string {
		$secciones = (array) $m['sections'];

		ob_start();
		?>
		<div class="prc-ayuda">
			<nav class="prc-ayuda__guias" aria-label="Guías de ayuda">
				<ul>
					<?php foreach ( (array) $m['tabs'] as $pestana ) : ?>
						<li><a href="<?php echo esc_url( (string) $pestana['url'] ); ?>"<?php echo empty( $pestana['active'] ) ? '' : ' class="is-activo" aria-current="page"'; ?>><?php echo esc_html( (string) $pestana['label'] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</nav>

			<article class="prc-ayuda__guia" aria-labelledby="prc-ayuda-titulo">
				<h2 class="prc-ayuda__titulo" id="prc-ayuda-titulo"><?php echo esc_html( (string) $m['label'] ); ?></h2>
				<p class="prc-intro"><?php echo esc_html( (string) $m['intro'] ); ?></p>
				<?php
				echo self::index( $secciones ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
				foreach ( $secciones as $apartado ) {
					echo self::section( (array) $apartado ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
				}
				?>
			</article>

			<?php echo self::support( (string) $m['support'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
		</div>
		<?php
		return Shell::render( 'Ayuda', '', (string) ob_get_clean() );
	}

	/**
	 * The index: one link per section, the second level nested under its parent.
	 *
	 * @param array<int, array<string, mixed>> $secciones Sections of the guide.
	 * @return string
	 */
	private static function index( array $secciones ): string {
		$html    = '';
		$abierta = false;
		foreach ( $secciones as $apartado ) {
			$enlace = '<a href="#' . esc_attr( (string) $apartado['id'] ) . '">' . esc_html( (string) $apartado['title'] ) . '</a>';
			if ( 3 === (int) $apartado['level'] ) {
				$html   .= ( $abierta ? '' : '<ul>' ) . '<li>' . $enlace . '</li>';
				$abierta = true;
				continue;
			}
			$html   .= ( $abierta ? '</ul>' : '' ) . ( '' === $html ? '' : '</li>' ) . '<li>' . $enlace;
			$abierta = false;
		}
		if ( '' === $html ) {
			return '';
		}
		$html .= ( $abierta ? '</ul>' : '' ) . '</li>';
		return '<nav class="prc-ayuda__indice" aria-label="Índice de la guía"><h3 class="prc-ayuda__indice-titulo">En esta guía</h3><ol>' . $html . '</ol></nav>';
	}

	/**
	 * One section: its heading, its text and the place of each screenshot.
	 *
	 * @param array<string, mixed> $apartado Section.
	 * @return string
	 */
	private static function section( array $apartado ): string {
		$id     = (string) $apartado['id'];
		$nivel  = 3 === (int) $apartado['level'] ? 'h4' : 'h3';
		$titulo = '<' . $nivel . ' class="prc-ayuda__apartado-titulo" id="' . esc_attr( $id . '-titulo' ) . '">' . esc_html( (string) $apartado['title'] ) . '</' . $nivel . '>';

		$capturas = '';
		foreach ( (array) $apartado['shots'] as $captura ) {
			$capturas .= self::shot( (array) $captura );
		}

		return '<section class="prc-ayuda__apartado prc-ayuda__apartado--' . esc_attr( $nivel ) . '" id="' . esc_attr( $id ) . '" aria-labelledby="' . esc_attr( $id . '-titulo' ) . '">'
			. $titulo
			. wp_kses( (string) $apartado['html'], HelpContent::ALLOWED )
			. $capturas
			. '</section>';
	}

	/**
	 * One screenshot, or the marked place where it will go.
	 *
	 * @param array<string, mixed> $captura Caption and image URL.
	 * @return string
	 */
	private static function shot( array $captura ): string {
		$texto  = (string) ( $captura['caption'] ?? '' );
		$imagen = (string) ( $captura['image'] ?? '' );
		if ( '' !== $imagen ) {
			return '<figure class="prc-ayuda__captura"><img src="' . esc_url( $imagen ) . '" alt="' . esc_attr( $texto ) . '" loading="lazy" />'
				. '<figcaption>' . esc_html( $texto ) . '</figcaption></figure>';
		}
		return '<p class="prc-ayuda__captura prc-ayuda__captura--pendiente">' . esc_html( 'Captura pendiente: ' . $texto ) . '</p>';
	}

	/**
	 * «¿Necesita ayuda técnica?»: the procedure contact always, and what the installation configured.
	 *
	 * @param string $configurada What Help::support() returned, already filtered.
	 * @return string
	 */
	private static function support( string $configurada ): string {
		ob_start();
		?>
		<section class="prc-ayuda__soporte" aria-labelledby="prc-ayuda-soporte">
			<h2 class="prc-ayuda__titulo" id="prc-ayuda-soporte">¿Necesita ayuda técnica?</h2>
			<p>Para las dudas sobre un procedimiento —requisitos, plazos, quién puede participar—, escriba al área convocante, al <strong>correo de contacto</strong> que aparece en la ficha de ese procedimiento.</p>
			<?php echo wp_kses( $configurada, HelpContent::ALLOWED ); ?>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}
