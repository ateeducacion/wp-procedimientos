<?php
/**
 * «Gestión de procedimientos», painted from what Workspace::model() decided.
 *
 * @package Prc
 */

namespace Prc\PublicFront\View;

use Prc\Meta\ProcedureMetaKeys;
use Prc\PublicFront\Assets;
use Prc\PublicFront\ProcedureEditor;
use Prc\PublicFront\Shell;
use Prc\PublicFront\Workspace;

/**
 * Solo pinta: no lee la petición, no consulta y no decide.
 *
 * Nació como calco del taller que se sustituye —título grande, leyenda de
 * ocho iconos y una tabla de once columnas en dos niveles— y se ha quedado con
 * lo que se usa: el título corriente de las pantallas de dentro con «Nuevo
 * procedimiento» a su lado, el buscador con su rótulo, la leyenda plegada, una
 * pestaña por curso y seis columnas. El estado sale una vez, en su pastilla.
 */
final class WorkspaceView {

	/**
	 * The legend, in the order it is read: what each state means.
	 *
	 * El estado se deriva de las fechas y del cierre manual de solicitudes;
	 * las dos entradas contradictorias de la leyenda original se convirtieron
	 * en el aviso de que las fechas no cuadran.
	 *
	 * @var array<string, string>
	 */
	private const LEGEND = array(
		ProcedureMetaKeys::STATE_OPEN      => 'Plazo de solicitud <strong>abierto</strong>.',
		ProcedureMetaKeys::STATE_AMENDMENT => 'Plazo de solicitud <strong>finalizado</strong>; el de subsanación, abierto.',
		ProcedureMetaKeys::STATE_UPCOMING  => 'El plazo de solicitud todavía no ha empezado.',
		ProcedureMetaKeys::STATE_CLOSED    => 'Plazo de solicitud <strong>finalizado</strong>.',
		ProcedureMetaKeys::STATE_RESOLVED  => 'Con su listado definitivo publicado.',
		ProcedureMetaKeys::STATE_DRAFT     => 'Todavía no se ve en la portada.',
		ProcedureMetaKeys::STATE_ARCHIVED  => 'Se consulta y se exporta, pero ya no se edita.',
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
		<?php self::heading( $m ); ?>
		<div class="prc-taller">
			<?php echo Shell::notice( (string) $m['notice']['type'], (string) $m['notice']['text'], true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shell::notice escapa su texto. ?>
			<?php self::band( $m ); ?>
			<?php self::courses( $m ); ?>
			<?php self::count( $m ); ?>
			<?php self::table( $m ); ?>
		</div>
		<?php
		// El título va en la pantalla, y no en Shell::render(), para llevar al
		// lado la acción principal: el h1 corriente, no el de la portada.
		return Shell::render( '', '', (string) ob_get_clean() );
	}

	/**
	 * The heading, with «Nuevo procedimiento» next to it, or why there is none.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return void
	 */
	private static function heading( array $m ): void {
		?>
		<div class="prc-h1-fila">
			<h1 class="prc-h1">Gestión de procedimientos</h1>
			<?php if ( ! empty( $m['can_create'] ) ) : ?>
				<p class="prc-acciones">
					<a class="<?php echo esc_attr( Assets::button_class( true ) ); ?>" href="<?php echo esc_url( (string) $m['create_url'] ); ?>">
						<?php echo Shell::icon_plus(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG literal. ?>
						Nuevo procedimiento
					</a>
				</p>
			<?php elseif ( '' !== (string) ( $m['create_reason'] ?? '' ) ) : ?>
				<p class="prc-acciones prc-taller__sin-alta"><?php echo esc_html( (string) $m['create_reason'] ); ?></p>
			<?php endif; ?>
		</div>
		<p class="prc-taller__nota"><?php echo esc_html( (string) $m['subtitle'] ); ?></p>
		<?php
	}

	/**
	 * The band under the heading: the search, and the legend folded next to it.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return void
	 */
	private static function band( array $m ): void {
		?>
		<div class="prc-taller__banda">
			<?php self::search( $m ); ?>
			<?php self::legend(); ?>
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
			<label class="prc-buscador__rotulo" for="prc-buscar">Buscar por título, número o ámbito</label>
			<span class="prc-buscador__fila">
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
				<button class="<?php echo esc_attr( Assets::button_class() ); ?>" type="submit">Buscar</button>
			</span>
			<?php if ( '' !== (string) $m['reset_url'] ) : ?>
				<a class="prc-buscador__quitar" href="<?php echo esc_url( (string) $m['reset_url'] ); ?>">Quitar los filtros</a>
			<?php endif; ?>
		</form>
		<?php
	}

	/**
	 * The legend of states, folded: each pill and what it means.
	 *
	 * @return void
	 */
	private static function legend(): void {
		$rotulos = Workspace::state_filters();
		?>
		<details class="prc-leyenda"><summary>¿Qué significa cada estado?</summary>
			<ul class="prc-leyenda__lista">
				<?php foreach ( self::LEGEND as $estado => $frase ) : ?>
					<li class="prc-leyenda__item">
						<span class="<?php echo esc_attr( Assets::state_class( $estado ) ); ?>"><?php echo esc_html( (string) ( $rotulos[ $estado ] ?? $estado ) ); ?></span>
						<span><?php echo wp_kses( $frase, array( 'strong' => array() ) ); ?></span>
					</li>
				<?php endforeach; ?>
				<li class="prc-leyenda__item">
					<?php self::icon( 'error', 'aviso' ); ?>
					<span><?php echo wp_kses( self::LEGEND_WARNING, array( 'strong' => array() ) ); ?></span>
				</li>
			</ul>
		</details>
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
		$cursos = (array) $m['courses'];
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
			'Mostrando <strong>%1$s</strong> %2$s%3$s',
			esc_html( (string) $n ),
			esc_html( 1 === $n ? 'procedimiento' : 'procedimientos' ),
			'' !== $curso
				? ' en <strong>' . esc_html( $curso ) . '</strong>'
				: ''
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
					<?php echo esc_html( '' !== $curso ? 'Procedimientos de ' . $curso : 'Procedimientos' ); ?>
				</caption>
				<thead>
					<tr>
						<th scope="col" class="prc-th--titulo">Título</th>
						<th scope="col">Estado</th>
						<th scope="col">Plazo</th>
						<th scope="col">Dirigido a</th>
						<th scope="col">Solicitudes</th>
						<th scope="col">Acciones</th>
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
	 * El número y los ámbitos van bajo el título, en pequeño: el número es lo
	 * que se copia de un correo y lo que encuentra el buscador.
	 *
	 * @param array<string, mixed> $row One row of the model.
	 * @return void
	 */
	private static function row( array $row ): void {
		$estado = (string) $row['state'];
		$aviso  = (string) $row['warning'];
		$meta   = implode( ' · ', array_merge( array( 'N.º ' . (string) $row['id'] ), array_map( 'strval', (array) $row['areas'] ) ) );
		?>
		<tr data-estado="<?php echo esc_attr( $estado ); ?>" data-procedimiento="<?php echo esc_attr( (string) $row['id'] ); ?>">
			<th scope="row" class="prc-celda-titulo" data-rotulo="Título">
				<?php if ( '' !== (string) $row['url'] ) : ?>
					<a href="<?php echo esc_url( (string) $row['url'] ); ?>" title="Abrir el taller de este procedimiento"><?php echo esc_html( (string) $row['title'] ); ?></a>
				<?php else : ?>
					<?php echo esc_html( (string) $row['title'] ); ?>
				<?php endif; ?>
				<span class="prc-celda-titulo__meta"><?php echo esc_html( $meta ); ?></span>
			</th>
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
			<td class="prc-celda-fecha" data-rotulo="Plazo"><?php echo esc_html( '' !== (string) $row['dates'] ? (string) $row['dates'] : 'Sin plazo' ); ?></td>
			<td class="prc-celda-destinatario" data-rotulo="Dirigido a">
				<?php echo esc_html( (string) $row['audience_label'] ); ?>
				<?php if ( '' !== (string) ( $row['ownership_label'] ?? '' ) ) : ?>
					<br /><span class="<?php echo esc_attr( Assets::state_class( 'audience' ) ); ?>"><?php echo esc_html( (string) $row['ownership_label'] ); ?></span>
				<?php endif; ?>
			</td>
			<td class="prc-celda-solicitudes" data-rotulo="Solicitudes">
				<?php self::applications( $row ); ?>
			</td>
			<td class="prc-celda-accion" data-rotulo="Acciones">
				<p class="prc-acciones">
					<?php if ( '' !== (string) $row['view_url'] ) : ?>
						<a class="<?php echo esc_attr( Assets::button_class() . ' btn-sm' ); ?>" href="<?php echo esc_url( (string) $row['view_url'] ); ?>"
							title="<?php echo esc_attr( 'publish' === (string) $row['status'] ? 'Ver la ficha pública' : 'Previsualizar la ficha, que está en borrador' ); ?>"><?php echo esc_html( 'publish' === (string) $row['status'] ? 'Ver la ficha' : 'Previsualizar' ); ?></a>
					<?php endif; ?>
					<?php if ( ! empty( $row['can_duplicate'] ) ) : ?>
						<?php self::duplicate( $row ); ?>
					<?php endif; ?>
				</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * How many applications, as the link to them.
	 *
	 * @param array<string, mixed> $row One row of the model.
	 * @return void
	 */
	private static function applications( array $row ): void {
		$n     = (int) $row['applications'];
		$texto = 1 === $n ? '1 solicitud' : $n . ' solicitudes';
		if ( '' === (string) $row['applications_url'] ) {
			echo esc_html( $texto );
			return;
		}
		?>
		<a href="<?php echo esc_url( (string) $row['applications_url'] ); ?>"><?php echo esc_html( $texto ); ?></a>
		<?php
	}

	/**
	 * «Duplicar» de una fila: el mismo POST que el del taller.
	 *
	 * Solo en las filas que el modelo marca con `can_duplicate`. El
	 * formulario va al taller del original, que es quien explica un rechazo.
	 * El nonce va sin `id`: hay uno por fila y un `id` repetido no es HTML.
	 *
	 * @param array<string, mixed> $row One row of the model.
	 * @return void
	 */
	private static function duplicate( array $row ): void {
		$op = ProcedureEditor::OP_DUPLICATE;
		?>
		<form class="prc-acciones" method="post" action="<?php echo esc_url( (string) $row['url'] ); ?>"
			data-prc-confirm="<?php echo esc_attr( ProcedureEditorView::duplicate_question( (string) $row['title'] ) ); ?>"
			data-prc-confirm-ok="Duplicar" data-prc-once>
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::nonce_name( $op ) ); ?>" value="<?php echo esc_attr( wp_create_nonce( ProcedureEditor::nonce_action( $op ) ) ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) $row['id'] ); ?>" />
			<button type="submit" class="<?php echo esc_attr( Assets::button_class() . ' btn-sm' ); ?>" title="Duplicar este procedimiento">Duplicar</button>
		</form>
		<?php
	}
}
