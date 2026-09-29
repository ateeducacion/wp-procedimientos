<?php
/**
 * «Gestión de procedimientos», painted from what Workspace::model() decided.
 *
 * @package Prc
 */

namespace Prc\PublicFront\View;

use Prc\Meta\ProcedureMetaKeys;
use Prc\PublicFront\Assets;
use Prc\PublicFront\Shell;
use Prc\PublicFront\Workspace;

/**
 * Solo pinta: no lee la petición, no consulta y no decide.
 *
 * Es el calco del taller que se sustituye: título grande, banda con el
 * buscador a la izquierda y la leyenda de estados a la derecha, una pestaña
 * por curso, la línea de recuento y una tabla de dos niveles. Lo que cambia
 * respecto del original está anotado donde cambia.
 */
final class WorkspaceView {

	/**
	 * The legend, in the order it is read.
	 *
	 * Las ocho entradas del original eran el cruce de un estado escrito a mano
	 * con la fecha límite. Aquí el estado se deriva de las fechas, así que son
	 * los siete estados del aplicativo; las dos entradas contradictorias de
	 * aquella leyenda se han convertido en la octava, que es lo que siempre
	 * fueron: un aviso de que los datos no cuadran.
	 *
	 * @var array<string, string>
	 */
	private const LEGEND = array(
		ProcedureMetaKeys::STATE_OPEN      => 'Procedimiento <strong>abierto</strong>. Plazo de solicitud <strong>abierto</strong>.',
		ProcedureMetaKeys::STATE_AMENDMENT => 'Procedimiento en <strong>subsanación</strong>. Plazo de solicitud <strong>finalizado</strong>.',
		ProcedureMetaKeys::STATE_UPCOMING  => 'Procedimiento <strong>próximo</strong>. El plazo de solicitud todavía no ha empezado.',
		ProcedureMetaKeys::STATE_CLOSED    => 'Procedimiento <strong>cerrado</strong>. Plazo de solicitud <strong>finalizado</strong>.',
		ProcedureMetaKeys::STATE_RESOLVED  => 'Procedimiento <strong>resuelto</strong>, con su listado definitivo publicado.',
		ProcedureMetaKeys::STATE_DRAFT     => 'Procedimiento <strong>en borrador</strong>: todavía no se ve en la portada.',
		ProcedureMetaKeys::STATE_ARCHIVED  => 'Procedimiento <strong>histórico</strong>: se consulta y se exporta, pero ya no se edita.',
	);

	/**
	 * What the legend says about the row that carries a warning.
	 */
	private const LEGEND_WARNING = 'Las <strong>fechas no cuadran</strong> y hay que repasarlas: el aviso dice qué.';

	/**
	 * The screen, from its model.
	 *
	 * @param array<string, mixed> $m What Workspace::model() returned.
	 * @return string
	 */
	public static function html( array $m ): string {
		if ( empty( $m['can_use'] ) ) {
			return Shell::render( 'Gestión de procedimientos', '', Shell::notice( 'aviso', (string) $m['reason'] ) );
		}

		ob_start();
		?>
		<div class="prc-taller">
			<?php echo Shell::notice( (string) $m['notice']['type'], (string) $m['notice']['text'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shell::notice escapa su texto. ?>
			<?php self::band( $m ); ?>
			<?php self::courses( $m ); ?>
			<?php self::count( $m ); ?>
			<?php self::table( $m ); ?>
		</div>
		<?php
		// El título grande y centrado del original, no el `h1` de las pantallas
		// de dentro: esta y la portada son las dos que lo llevan.
		return Shell::render( 'Gestión de procedimientos', '', (string) ob_get_clean(), true );
	}

	/**
	 * The header band: search on the left, legend on the right.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return void
	 */
	private static function band( array $m ): void {
		?>
		<div class="prc-taller__banda">
			<div class="prc-taller__izq">
				<?php self::search( $m ); ?>
				<p class="prc-taller__nota"><?php echo esc_html( (string) $m['subtitle'] ); ?></p>
				<?php if ( ! empty( $m['can_create'] ) ) : ?>
					<p class="prc-acciones">
						<a class="<?php echo esc_attr( Assets::button_class( true ) ); ?>" href="<?php echo esc_url( (string) $m['create_url'] ); ?>">
							<?php echo Shell::icon_plus(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG literal. ?>
							Nuevo procedimiento
						</a>
					</p>
				<?php endif; ?>
			</div>
			<div class="prc-taller__der">
				<?php self::legend(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * The search box, and the state filter next to it.
	 *
	 * Filtra el servidor: es un `GET` de toda la vida, funciona sin
	 * JavaScript y el resultado se puede guardar como marcador. El curso viaja
	 * escondido para no perder la pestaña al buscar.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return void
	 */
	private static function search( array $m ): void {
		$s = $m['selection'];
		?>
		<form class="prc-buscador" method="get" action="" role="search">
			<?php if ( (int) $m['page_id'] > 0 ) : ?>
				<input type="hidden" name="page_id" value="<?php echo esc_attr( (string) $m['page_id'] ); ?>" />
			<?php endif; ?>
			<?php if ( (int) $s['course'] > 0 ) : ?>
				<input type="hidden" name="<?php echo esc_attr( Workspace::VAR_COURSE ); ?>" value="<?php echo esc_attr( (string) $s['course'] ); ?>" />
			<?php endif; ?>
			<label class="screen-reader-text" for="prc-buscar">Buscar en la tabla</label>
			<input type="search" id="prc-buscar" class="prc-buscador__campo" size="20"
				name="<?php echo esc_attr( Workspace::VAR_SEARCH ); ?>"
				value="<?php echo esc_attr( (string) $s['q'] ); ?>" />
			<label class="screen-reader-text" for="prc-estado">Estado</label>
			<select class="prc-buscador__estado" id="prc-estado" name="<?php echo esc_attr( Workspace::VAR_STATE ); ?>">
				<?php foreach ( Workspace::state_filters() as $clave => $rotulo ) : ?>
					<option value="<?php echo esc_attr( $clave ); ?>" <?php selected( (string) $s['state'], $clave ); ?>>
						<?php echo esc_html( sprintf( '%s (%d)', $rotulo, (int) ( $m['counts'][ $clave ] ?? 0 ) ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<button class="prc-buscador__boton" type="submit">Buscar en la tabla</button>
			<?php if ( '' !== (string) $m['reset_url'] ) : ?>
				<a class="prc-buscador__quitar" href="<?php echo esc_url( (string) $m['reset_url'] ); ?>">Quitar los filtros</a>
			<?php endif; ?>
		</form>
		<?php
	}

	/**
	 * The legend of states: what each icon of the table means.
	 *
	 * @return void
	 */
	private static function legend(): void {
		$iconos = Workspace::state_icons();
		?>
		<div class="prc-leyenda">
			<ul class="prc-leyenda__lista">
				<?php foreach ( self::LEGEND as $estado => $frase ) : ?>
					<li class="prc-leyenda__item">
						<?php self::icon( (string) ( $iconos[ $estado ] ?? '' ), $estado ); ?>
						<span><?php echo wp_kses( $frase, array( 'strong' => array() ) ); ?></span>
					</li>
				<?php endforeach; ?>
				<li class="prc-leyenda__item">
					<?php self::icon( 'error', 'aviso' ); ?>
					<span><?php echo wp_kses( self::LEGEND_WARNING, array( 'strong' => array() ) ); ?></span>
				</li>
			</ul>
		</div>
		<?php
	}

	/**
	 * One decorative icon of the legend or of a cell.
	 *
	 * El glifo es texto —sin la tipografía cargada se lee el nombre del
	 * icono—, así que va oculto para quien escucha siempre que al lado haya
	 * rótulo, y con `aria-label` cuando el icono es la única información.
	 *
	 * @param string $glifo  Material Symbols ligature.
	 * @param string $estado State slug, for the colour.
	 * @param string $rotulo Accessible name; empty leaves the icon decorative.
	 * @return void
	 */
	private static function icon( string $glifo, string $estado, string $rotulo = '' ): void {
		if ( '' === $glifo ) {
			return;
		}
		$clases = 'material-symbols-outlined prc-icono-estado prc-icono-estado--' . sanitize_html_class( $estado );
		if ( '' === $rotulo ) {
			printf( '<span class="%s" aria-hidden="true">%s</span>', esc_attr( $clases ), esc_html( $glifo ) );
			return;
		}
		printf(
			'<span class="%s" role="img" aria-label="%s" title="%s">%s</span>',
			esc_attr( $clases ),
			esc_attr( $rotulo ),
			esc_attr( $rotulo ),
			esc_html( $glifo )
		);
	}

	/**
	 * One tab per curso escolar, navigable by URL.
	 *
	 * En el original son pestañas de JavaScript y sin él la pantalla se queda
	 * en blanco: aquí son enlaces, y el servidor sirve el curso que se pide.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return void
	 */
	private static function courses( array $m ): void {
		if ( array() === (array) $m['courses'] ) {
			return;
		}
		$s      = $m['selection'];
		$cursos = (array) $m['courses'] + array( 0 => 'Todos los cursos' );
		?>
		<nav class="prc-cursos" aria-label="Curso escolar">
			<ul class="prc-cursos__lista">
				<?php foreach ( $cursos as $term_id => $nombre ) : ?>
					<?php $activa = (int) $s['course'] === (int) $term_id; ?>
					<li>
						<a class="prc-cursos__tab<?php echo $activa ? ' prc-cursos__tab--on' : ''; ?>"
							href="<?php echo esc_url( Workspace::url( $s, array( 'course' => (int) $term_id ) ) ); ?>"
							<?php echo $activa ? 'aria-current="page"' : ''; ?>>
							<?php echo esc_html( (string) $nombre ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>
		<?php
	}

	/**
	 * The line that says how many rows are being shown, and of what.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return void
	 */
	private static function count( array $m ): void {
		$n     = (int) $m['total'];
		$curso = (string) $m['course_label'];
		$q     = (string) $m['selection']['q'];

		// Se arma de una pieza y no a trozos en el marcado: entre dos etiquetas
		// de PHP siempre queda un espacio, y aquí se notaría antes del punto.
		$frase = sprintf(
			'Mostrando <strong>%1$s</strong> %2$s %3$s',
			esc_html( (string) $n ),
			esc_html( 1 === $n ? 'procedimiento' : 'procedimientos' ),
			'' !== $curso
				? 'en <strong>' . esc_html( $curso ) . '</strong>'
				: 'de <strong>todos los cursos</strong>'
		);
		if ( '' !== $q ) {
			$frase .= sprintf(
				' %s «<strong>%s</strong>»',
				1 === $n ? 'que contiene' : 'que contienen',
				esc_html( $q )
			);
		}
		?>
		<p class="prc-recuento"><?php echo wp_kses( $frase . ':', array( 'strong' => array() ) ); ?></p>
		<?php
	}

	/**
	 * The table itself, or why it is empty.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return void
	 */
	private static function table( array $m ): void {
		if ( array() === $m['rows'] ) {
			?>
			<p class="prc-vacio"><?php echo esc_html( (string) $m['empty_text'] ); ?></p>
			<?php
			return;
		}
		$curso = (string) $m['course_label'];
		?>
		<div class="prc-tabla-caja">
			<table class="prc-tabla prc-tabla--densa prc-tabla--taller">
				<caption class="screen-reader-text">
					<?php echo esc_html( '' !== $curso ? 'Procedimientos de ' . $curso : 'Procedimientos de todos los cursos' ); ?>
				</caption>
				<thead>
					<tr class="prc-tabla__grupos">
						<th scope="colgroup" colspan="6" class="prc-tabla__grupo prc-tabla__grupo--1">Detalles del procedimiento</th>
						<th scope="colgroup" colspan="1" class="prc-tabla__grupo prc-tabla__grupo--2">Fecha límite</th>
						<th scope="colgroup" colspan="3" class="prc-tabla__grupo prc-tabla__grupo--3">Gestión</th>
					</tr>
					<tr>
						<th scope="col" class="prc-th--a prc-th--centro prc-th--color">
							<span class="material-symbols-outlined" aria-hidden="true">image</span>
							<span class="screen-reader-text">Color de cabecera</span>
						</th>
						<th scope="col" class="prc-th--a prc-th--centro">#</th>
						<th scope="col" class="prc-th--a">Título</th>
						<th scope="col" class="prc-th--a prc-th--centro">Dirigido a</th>
						<th scope="col" class="prc-th--a prc-th--centro">Estado</th>
						<th scope="col" class="prc-th--a">Creado</th>
						<th scope="col" class="prc-th--b">Límite<br />solicitud</th>
						<th scope="col" class="prc-th--c prc-th--gestiona">Gestiona</th>
						<th scope="col" class="prc-th--c prc-th--centro">Detalles</th>
						<th scope="col" class="prc-th--c prc-th--centro">Solicitudes</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $m['rows'] as $row ) : ?>
						<?php self::row( $row ); ?>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * One row of the table.
	 *
	 * @param array<string, mixed> $row One row of the model.
	 * @return void
	 */
	private static function row( array $row ): void {
		$estado = (string) $row['state'];
		$aviso  = (string) $row['warning'];
		$mirar  = 'publish' === (string) $row['status']
			? 'Ver la ficha pública'
			: 'Previsualizar la ficha, que está en borrador';
		?>
		<tr data-estado="<?php echo esc_attr( $estado ); ?>" data-procedimiento="<?php echo esc_attr( (string) $row['id'] ); ?>">
			<td class="prc-celda-color" data-rotulo="Color">
				<span class="prc-mini-color" style="background-color:<?php echo esc_attr( (string) $row['color'] ); ?>"></span>
			</td>
			<th scope="row" class="prc-celda-num" data-rotulo="Número">
				<?php self::icon( (string) ( Workspace::state_icons()[ $estado ] ?? '' ), $estado, (string) $row['state_label'] ); ?>
				<br /><?php echo esc_html( (string) $row['id'] ); ?>
			</th>
			<td class="prc-celda-titulo" data-rotulo="Título">
				<?php if ( '' !== (string) $row['url'] ) : ?>
					<a href="<?php echo esc_url( (string) $row['url'] ); ?>" title="Abrir el taller de este procedimiento">
						<?php echo esc_html( (string) $row['title'] ); ?>
					</a>
				<?php else : ?>
					<?php echo esc_html( (string) $row['title'] ); ?>
				<?php endif; ?>
			</td>
			<td class="prc-celda-destinatario" data-rotulo="Dirigido a">
				<span class="material-symbols-outlined prc-icono-azul" aria-hidden="true"><?php echo esc_html( ProcedureMetaKeys::AUDIENCE_TEACHERS === (string) $row['audience'] ? 'group' : 'home_work' ); ?></span>
				<br /><?php echo esc_html( (string) $row['audience_label'] ); ?>
			</td>
			<td class="prc-celda-estado" data-rotulo="Estado">
				<span class="<?php echo esc_attr( Assets::state_class( $estado ) ); ?>"><?php echo esc_html( (string) $row['state_label'] ); ?></span>
				<?php if ( ! empty( $row['archived'] ) && ProcedureMetaKeys::STATE_ARCHIVED !== $estado ) : ?>
					<span class="<?php echo esc_attr( Assets::state_class( ProcedureMetaKeys::STATE_ARCHIVED ) ); ?>"
						title="Cerrado a edición: se consulta y se exporta, pero no se cambia.">Histórico</span>
				<?php endif; ?>
				<?php if ( '' !== $aviso ) : ?>
					<?php self::icon( 'error', 'aviso', $aviso ); ?>
				<?php endif; ?>
				<?php if ( array() !== (array) $row['courses'] ) : ?>
					<br /><em class="prc-state__curso"><?php echo esc_html( implode( ' · ', (array) $row['courses'] ) ); ?></em>
				<?php endif; ?>
			</td>
			<td class="prc-celda-fecha" data-rotulo="Creado"><?php echo esc_html( (string) $row['created'] ); ?></td>
			<td class="prc-celda-fecha" data-rotulo="Límite de solicitud"><?php echo esc_html( (string) $row['closes'] ); ?></td>
			<td class="prc-celda-ambitos" data-rotulo="Gestiona">
				<?php if ( array() !== (array) $row['areas'] ) : ?>
					<ol class="prc-ambitos">
						<?php foreach ( (array) $row['areas'] as $nombre ) : ?>
							<li><?php echo esc_html( (string) $nombre ); ?></li>
						<?php endforeach; ?>
					</ol>
				<?php endif; ?>
			</td>
			<td class="prc-celda-accion" data-rotulo="Detalles">
				<?php self::action( (string) $row['view_url'], 'visibility', $mirar ); ?>
			</td>
			<td class="prc-celda-accion" data-rotulo="Solicitudes">
				<?php self::action( (string) $row['applications_url'], 'view_list', 'Ver solicitudes' ); ?>
				<span class="prc-accion__contador"><?php echo esc_html( (string) $row['applications'] ); ?></span>
			</td>
		</tr>
		<?php
	}

	/**
	 * One icon action of a cell: el icono es lo único que dice qué hace.
	 *
	 * @param string $url    Where it goes; empty paints nothing.
	 * @param string $glifo  Material Symbols ligature.
	 * @param string $rotulo What it does.
	 * @return void
	 */
	private static function action( string $url, string $glifo, string $rotulo ): void {
		if ( '' === $url ) {
			return;
		}
		?>
		<a class="prc-accion" href="<?php echo esc_url( $url ); ?>" title="<?php echo esc_attr( $rotulo ); ?>">
			<span class="material-symbols-outlined" aria-hidden="true"><?php echo esc_html( $glifo ); ?></span>
			<span class="screen-reader-text"><?php echo esc_html( $rotulo ); ?></span>
		</a>
		<?php
	}
}
