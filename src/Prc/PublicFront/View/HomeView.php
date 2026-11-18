<?php
/**
 * The public front page, painted from what Home::model() decided.
 *
 * @package Prc
 */

namespace Prc\PublicFront\View;

use Prc\PublicFront\Assets;
use Prc\PublicFront\Shell;

/**
 * Solo pinta: no lee la petición, no consulta y no decide.
 *
 * La portada que se sustituye es un héroe en mayúsculas, una banda clara con
 * la entradilla y los filtros, y debajo un bloque gris por estado con una
 * rejilla de tarjetas. La tarjeta es el cartel en vertical, tipo cartelera
 * —o el logo del ámbito si no hay cartel—, el título completo sin truncar y,
 * fuera del enlace, el ámbito y la etiqueta del estado. Añade lo que el
 * original no tiene: los días que quedan de plazo y la marca de que el centro
 * de quien mira ya ha solicitado.
 */
final class HomeView {

	/**
	 * Cards in the first row on a desktop screen: five posters per row.
	 */
	private const FIRST_ROW = 5;

	/**
	 * Rendered width of a poster, matching `.prc-rejilla` in prc-portada.css:
	 * two columns up to 530px, three to five up to 980px, five of about 190px above.
	 */
	private const SIZES = '(max-width: 530px) calc(50vw - 28px), (max-width: 980px) calc(33vw - 24px), 190px';

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

			<?php $before = 0; ?>
			<?php foreach ( (array) $m['groups'] as $state => $grupo ) : ?>
				<?php self::group( (string) $state, (array) $grupo, $before ); ?>
				<?php $before += count( (array) $grupo['rows'] ); ?>
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
	 * @param string               $state  State slug.
	 * @param array<string, mixed> $grupo  Its label and rows.
	 * @param int                  $before Cards already painted above this block.
	 * @return void
	 */
	private static function group( string $state, array $grupo, int $before ): void {
		$clase = sanitize_html_class( $state );
		?>
		<section class="prc-banda prc-bloque prc-bloque--<?php echo esc_attr( $clase ); ?> prc-grupo-<?php echo esc_attr( $clase ); ?>">
			<div class="prc-fila">
				<h2 class="prc-bloque__titulo">
					Procedimientos en estado
					<strong class="prc-bloque__rotulo"><?php echo esc_html( (string) $grupo['label'] ); ?></strong>
				</h2>
				<ul class="prc-rejilla">
					<?php foreach ( array_values( (array) $grupo['rows'] ) as $n => $row ) : ?>
						<?php self::card( (array) $row, $before + $n ); ?>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
		<?php
	}

	/**
	 * One card: the poster, the whole title, its ámbito and what is known of its term.
	 *
	 * El ámbito y la etiqueta de estado van **fuera** del enlace: dentro
	 * entrarían en su nombre accesible, que es el título del procedimiento. Por
	 * eso la imagen lleva `alt` vacío. La pastilla es la del resto del
	 * aplicativo ({@see Assets::state_class()}).
	 *
	 * @param array<string, mixed> $row      One row of a group.
	 * @param int                  $position Its place on the page, from 0.
	 * @return void
	 */
	private static function card( array $row, int $position ): void {
		// The first row is above the fold: no lazy loading, and the first poster first.
		$loading  = $position < self::FIRST_ROW ? 'eager' : 'lazy';
		$priority = 0 === $position ? 'high' : 'auto';
		?>
		<li class="prc-proc" style="--prc-banda: <?php echo esc_attr( (string) $row['header_color'] ); ?>">
			<a class="prc-proc__enlace" href="<?php echo esc_url( (string) $row['url'] ); ?>">
				<?php if ( '' !== (string) $row['poster'] ) : ?>
					<span class="prc-proc__cartel"><img src="<?php echo esc_url( (string) $row['poster'] ); ?>"<?php echo '' !== (string) $row['poster_set'] ? ' srcset="' . esc_attr( (string) $row['poster_set'] ) . '" sizes="' . esc_attr( self::SIZES ) . '"' : ''; ?> alt="" loading="<?php echo esc_attr( $loading ); ?>" fetchpriority="<?php echo esc_attr( $priority ); ?>" decoding="async"></span>
				<?php elseif ( '' !== (string) $row['area_logo'] ) : ?>
					<span class="prc-proc__cartel prc-proc__cartel--logo"><img src="<?php echo esc_url( (string) $row['area_logo'] ); ?>" alt="" loading="<?php echo esc_attr( $loading ); ?>" fetchpriority="<?php echo esc_attr( $priority ); ?>" decoding="async"></span>
				<?php else : ?>
					<span class="prc-proc__cartel prc-proc__cartel--vacio"></span>
				<?php endif; ?>
				<span class="prc-proc__titulo"><?php echo esc_html( (string) $row['title'] ); ?></span>
			</a>
			<?php if ( array() !== (array) $row['areas'] ) : ?>
				<p class="prc-proc__ambito"><?php echo esc_html( implode( ' · ', array_map( 'strval', (array) $row['areas'] ) ) ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== (string) $row['state_label'] ) : ?>
				<p class="prc-proc__estado"><span class="<?php echo esc_attr( Assets::state_class( (string) $row['state'] ) ); ?>"><?php echo esc_html( (string) $row['state_label'] ); ?></span></p>
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
