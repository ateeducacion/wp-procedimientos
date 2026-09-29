<?php
/**
 * The public front page, painted from what Home::model() decided.
 *
 * @package Prc
 */

namespace Prc\PublicFront\View;

use Prc\PublicFront\Shell;

/**
 * Solo pinta: no lee la petición, no consulta y no decide.
 *
 * La portada que se sustituye es un héroe en mayúsculas, una banda clara con
 * la entradilla y los filtros, y debajo un bloque gris por estado con una
 * rejilla de cuatro tarjetas por fila. La tarjeta es un marco blanco con una
 * banda de color, el título completo sin truncar y, fuera del enlace, la
 * etiqueta del estado. Aquí se calca eso, con dos añadidos que el original no
 * tiene: los días que quedan de plazo y la marca de que el centro de quien
 * mira ya ha solicitado.
 */
final class HomeView {

	/**
	 * The screen, from its model.
	 *
	 * @param array<string, mixed> $m What Home::model() returned.
	 * @return string
	 */
	public static function html( array $m ): string {
		ob_start();
		?>
		<div class="prc-portada">
			<section class="prc-banda prc-portada__intro">
				<div class="prc-fila">
					<p class="prc-portada__entrada">Procedimientos a los que un centro educativo puede presentar su solicitud.</p>
					<?php self::filters( (array) $m['filters'] ); ?>
				</div>
			</section>

			<?php if ( array() === (array) $m['groups'] ) : ?>
				<div class="prc-fila">
					<p class="prc-vacio"><?php echo esc_html( (string) $m['empty_text'] ); ?>
						<?php if ( '' !== (string) $m['reset_url'] ) : ?>
							<a href="<?php echo esc_url( (string) $m['reset_url'] ); ?>">Ver todos los ámbitos</a>
						<?php endif; ?>
					</p>
				</div>
			<?php endif; ?>

			<?php foreach ( (array) $m['groups'] as $state => $grupo ) : ?>
				<?php self::group( (string) $state, (array) $grupo ); ?>
			<?php endforeach; ?>
		</div>
		<?php
		return Shell::render( 'Procedimientos para los centros educativos', '', (string) ob_get_clean(), true );
	}

	/**
	 * The filter bar: state, course and ámbito, each one a row of links.
	 *
	 * Enlaces de verdad: sin JavaScript se filtra igual, porque cada opción es
	 * una dirección que el servidor ya sabe pintar.
	 *
	 * @param array<int, array<string, mixed>> $ejes What Home::model() put in `filters`.
	 * @return void
	 */
	private static function filters( array $ejes ): void {
		if ( array() === $ejes ) {
			return;
		}
		?>
		<nav class="prc-filtros" aria-label="Filtros de la portada">
			<?php foreach ( $ejes as $eje ) : ?>
				<div class="prc-filtros__eje">
					<span class="prc-filtros__rotulo"><?php echo esc_html( (string) $eje['label'] ); ?></span>
					<ul>
						<?php foreach ( (array) $eje['items'] as $opcion ) : ?>
							<li><a href="<?php echo esc_url( (string) $opcion['url'] ); ?>"<?php echo empty( $opcion['active'] ) ? '' : ' class="is-activo" aria-current="page"'; ?>><?php echo esc_html( (string) $opcion['label'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endforeach; ?>
		</nav>
		<?php
	}

	/**
	 * One block: the procedures of one state, on their grey band.
	 *
	 * @param string               $state State slug.
	 * @param array<string, mixed> $grupo Its label and rows.
	 * @return void
	 */
	private static function group( string $state, array $grupo ): void {
		$clase = sanitize_html_class( $state );
		?>
		<section class="prc-banda prc-bloque prc-bloque--<?php echo esc_attr( $clase ); ?> prc-grupo-<?php echo esc_attr( $clase ); ?>">
			<div class="prc-fila">
				<h2 class="prc-bloque__titulo">
					Procedimientos en estado
					<strong class="prc-bloque__rotulo"><?php echo esc_html( (string) $grupo['label'] ); ?></strong>
				</h2>
				<ul class="prc-rejilla">
					<?php foreach ( (array) $grupo['rows'] as $row ) : ?>
						<?php self::card( (array) $row ); ?>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
		<?php
	}

	/**
	 * One card: the colour band, the whole title and what is known of its term.
	 *
	 * La etiqueta de estado va **fuera** del enlace: dentro entraría en el
	 * nombre accesible del enlace, que es el título del procedimiento.
	 *
	 * @param array<string, mixed> $row One row of a group.
	 * @return void
	 */
	private static function card( array $row ): void {
		?>
		<li class="prc-proc">
			<a class="prc-proc__enlace" href="<?php echo esc_url( (string) $row['url'] ); ?>">
				<span class="prc-proc__marco">
					<span class="prc-proc__banda" style="--prc-banda: <?php echo esc_attr( (string) $row['header_color'] ); ?>"></span>
				</span>
				<span class="prc-proc__titulo"><?php echo esc_html( (string) $row['title'] ); ?></span>
			</a>
			<?php if ( '' !== (string) $row['state_label'] ) : ?>
				<p class="prc-proc__estado"><?php echo esc_html( (string) $row['state_label'] ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== (string) $row['deadline'] ) : ?>
				<p class="prc-proc__plazo<?php echo empty( $row['urgent'] ) ? '' : ' prc-proc__plazo--urgente'; ?>">
					<span class="material-symbols-outlined" aria-hidden="true">schedule</span>
					<?php echo esc_html( (string) $row['deadline'] ); ?>
				</p>
			<?php endif; ?>
			<?php if ( ! empty( $row['applied'] ) ) : ?>
				<p class="prc-proc__marca">
					<span class="material-symbols-outlined" aria-hidden="true">task_alt</span>
					Su centro ya ha solicitado
				</p>
			<?php endif; ?>
		</li>
		<?php
	}
}
