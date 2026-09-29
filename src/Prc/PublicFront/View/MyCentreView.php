<?php
/**
 * The applications of one school, painted from what MyCentre::model() decided.
 *
 * @package Prc
 */

namespace Prc\PublicFront\View;

use Prc\PublicFront\Assets;
use Prc\PublicFront\Shell;

/**
 * Solo pinta: no lee la petición, no consulta, no decide.
 *
 * La pantalla que sustituye: título, ámbito, la ayuda de ordenación, el
 * recuento y una tabla densa que se ordena pulsando en sus cabeceras. Las
 * cabeceras son enlaces de verdad y el orden lo hace el servidor, así que
 * todo esto funciona con el JavaScript apagado (ADR-0022).
 */
final class MyCentreView {

	/**
	 * Título de la pantalla. Literal fijo: no depende de quién mire.
	 */
	private const TITLE = 'Solicitudes de participación en procedimientos de su centro educativo';

	/**
	 * Paint the screen.
	 *
	 * @param array<string, mixed> $m What MyCentre::model() returned.
	 * @return string
	 */
	public static function html( array $m ): string {
		if ( '' !== (string) $m['aviso'] ) {
			return Shell::render( self::TITLE, '', Shell::notice( 'aviso', (string) $m['aviso'] ) );
		}

		$centro = (array) $m['centre'];
		$filas  = (array) $m['rows'];
		$nombre = '' !== (string) $centro['name'] ? (string) $centro['name'] : 'Centro ' . (string) $centro['code'];
		$ambito = 'Procedimientos para ' . $nombre . ' (' . (string) $centro['code'] . ')';

		if ( array() === $filas ) {
			return Shell::render(
				self::TITLE,
				$ambito,
				'<div class="prc-mi-centro">' . self::count( 0 )
					. '<p class="prc-vacio">Su centro todavía no ha presentado ninguna solicitud. Se presentan desde la ficha de cada procedimiento, mientras su plazo esté abierto.</p></div>'
			);
		}

		ob_start();
		?>
		<div class="prc-mi-centro">
			<p class="prc-ayuda-orden">
				<strong>Pulse en las cabeceras</strong> de las columnas de la tabla <strong>para ordenarla</strong>:<br>
				la primera vez ordenará de menor a mayor (de la A a la Z, o de la más antigua a la más reciente);<br>
				la segunda vez, al revés.
			</p>
			<?php
			echo self::count( count( $filas ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado.
			echo self::amendment( (array) $m['amend'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado.
			?>
			<div class="prc-tabla-caja">
				<table class="prc-tabla prc-tabla--densa prc-mi-tabla">
					<caption class="screen-reader-text"><?php echo esc_html( $ambito ); ?></caption>
					<thead>
						<tr>
							<?php foreach ( (array) $m['headers'] as $columna ) : ?>
								<?php echo self::heading( (array) $columna ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado. ?>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $filas as $fila ) : ?>
							<?php echo self::row( (array) $fila ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado. ?>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
		return Shell::render( self::TITLE, $ambito, (string) ob_get_clean() );
	}

	/**
	 * How many procedures are on the table.
	 *
	 * @param int $total Row count.
	 * @return string
	 */
	private static function count( int $total ): string {
		return '<p class="prc-recuento">Mostrando <strong>' . (int) $total . '</strong> '
			. ( 1 === $total ? 'procedimiento' : 'procedimientos' ) . '.</p>';
	}

	/**
	 * The notice about the amendments still open, or nothing.
	 *
	 * El plazo de subsanación es lo que más caro sale perder, y entre trece
	 * filas iguales no se ve: se dice arriba y se marca la fila.
	 *
	 * @param array{count:int, closes:string, id:string} $amend What the model counted.
	 * @return string
	 */
	private static function amendment( array $amend ): string {
		$cuantas = (int) $amend['count'];
		if ( $cuantas < 1 ) {
			return '';
		}
		$una   = 1 === $cuantas;
		$texto = '<strong>' . $cuantas . ( $una ? ' solicitud</strong> tiene' : ' solicitudes</strong> tienen' )
			. ' la subsanación abierta';
		if ( '' !== (string) $amend['closes'] ) {
			$texto .= ( $una ? ' hasta el <strong>' : ', la más próxima hasta el <strong>' )
				. esc_html( (string) $amend['closes'] ) . '</strong>';
		}
		$texto .= '.';
		if ( '' !== (string) $amend['id'] ) {
			$texto .= ' <a href="#fila-' . esc_attr( (string) $amend['id'] ) . '">Ir a la solicitud</a>';
		}
		return '<p class="' . esc_attr( Assets::alert_class( 'warning' ) ) . '" role="status">' . $texto . '</p>';
	}

	/**
	 * One column heading: a real link, with its arrow and its `aria-sort`.
	 *
	 * El rótulo no va en azul: es la cabecera de la columna, no un enlace de
	 * contenido. La flecha la pone el CSS, que sabe por `aria-sort` cuál toca.
	 *
	 * @param array{label:string, sort:string, url:string} $columna One entry of the model headers.
	 * @return string
	 */
	private static function heading( array $columna ): string {
		$rotulo = esc_html( (string) $columna['label'] );
		if ( '' === (string) $columna['url'] ) {
			return '<th scope="col" class="prc-th">' . $rotulo . '</th>';
		}
		return '<th scope="col" class="prc-th prc-th--ordenable" aria-sort="' . esc_attr( (string) $columna['sort'] ) . '">'
			. '<a class="prc-th-enlace" href="' . esc_url( (string) $columna['url'] ) . '">' . $rotulo . '</a></th>';
	}

	/**
	 * One application, as a row.
	 *
	 * @param array<string, mixed> $fila One entry of the model rows.
	 * @return string
	 */
	private static function row( array $fila ): string {
		$cargo    = (string) $fila['position'];
		$quien    = (string) $fila['applicant'];
		$quien    = '' !== $quien && '' !== $cargo ? $quien . ' (' . $cargo . ')' : $quien;
		$opciones = array_map( 'esc_html', (array) $fila['options'] );

		ob_start();
		?>
		<tr id="fila-<?php echo esc_attr( (string) $fila['procedure_id'] ); ?>"<?php echo empty( $fila['amend'] ) ? '' : ' class="prc-subsanar"'; ?>>
			<th scope="row" class="prc-celda prc-celda--titulo" data-rotulo="Procedimiento">
				<?php if ( '' !== (string) $fila['sheet'] ) : ?>
					<a href="<?php echo esc_url( (string) $fila['sheet'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( (string) $fila['title'] ); ?></a>
				<?php else : ?>
					<?php echo esc_html( (string) $fila['title'] ); ?>
				<?php endif; ?>
				<span class="<?php echo esc_attr( Assets::state_class( (string) $fila['state'] ) ); ?>"><?php echo esc_html( (string) $fila['state_label'] ); ?></span>
			</th>
			<td class="prc-celda prc-celda--fecha" data-rotulo="Fecha"><?php echo esc_html( (string) $fila['date_label'] ); ?></td>
			<td class="prc-celda" data-rotulo="Solicitante (cargo)"><?php echo self::value( $quien ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado. ?></td>
			<td class="prc-celda" data-rotulo="Coordinador/a"><?php echo self::value( (string) $fila['coordinator'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado. ?></td>
			<td class="prc-celda" data-rotulo="Opciones"><?php echo self::value( implode( '<br>', $opciones ), false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya escapado. ?></td>
			<td class="prc-celda prc-celda--revision" data-rotulo="Revisión">
				<span class="<?php echo esc_attr( Assets::state_class( (string) $fila['review'] ) ); ?>"><?php echo esc_html( (string) $fila['review_label'] ); ?></span>
				<?php if ( '' !== (string) $fila['note'] ) : ?>
					<span class="prc-nota-revision"><?php echo esc_html( (string) $fila['note'] ); ?></span>
				<?php endif; ?>
				<?php if ( '' !== (string) $fila['url'] ) : ?>
					<a class="prc-ver-solicitud" href="<?php echo esc_url( (string) $fila['url'] ); ?>">Ver la solicitud</a>
				<?php endif; ?>
			</td>
		</tr>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * One cell value, or the `¿?` of the original when there is no data.
	 *
	 * @param string $valor  The value.
	 * @param bool   $escape Whether the value still has to be escaped.
	 * @return string
	 */
	private static function value( string $valor, bool $escape = true ): string {
		if ( '' === trim( $valor ) ) {
			return '<span class="prc-sindato" title="Sin datos">¿?</span>';
		}
		return $escape ? esc_html( $valor ) : $valor;
	}
}
