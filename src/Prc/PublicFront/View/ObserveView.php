<?php
/**
 * The read-only consultation, painted from what Observe::model() decided,
 * and the «Acceso de consulta» panel of the workshop.
 *
 * @package Prc
 */

namespace Prc\PublicFront\View;

use Prc\Domain\ApplicationFilter;
use Prc\PublicFront\ApplicationFiles;
use Prc\PublicFront\Assets;
use Prc\PublicFront\Observe;
use Prc\PublicFront\ObserveSettings;
use Prc\PublicFront\ProcedureEditor;
use Prc\PublicFront\Shell;

/**
 * Solo pinta: no lee la petición, no consulta, no decide.
 *
 * La consulta reutiliza las piezas de la tabla del taller —contadores,
 * filtro y páginas de {@see ApplicationsPanelView}— y no lleva ninguna de las
 * que escriben: ni revisar, ni revisar en bloque, ni la lista de personas.
 * El único formulario POST es el de exportar, y solo si el procedimiento lo
 * permite.
 */
final class ObserveView {

	/** Title of the screen. */
	private const TITLE = 'Consulta de solicitudes';

	/**
	 * Paint the screen.
	 *
	 * @param array<string, mixed> $m What Observe::model() returned.
	 * @return string
	 */
	public static function html( array $m ): string {
		if ( 'table' === (string) $m['mode'] ) {
			return self::procedure( $m );
		}
		if ( '' !== (string) $m['aviso'] ) {
			$volver = 'error' === (string) $m['aviso_tipo'] && '' !== (string) $m['list_url']
				? '<p><a href="' . esc_url( (string) $m['list_url'] ) . '">Volver a la consulta</a></p>'
				: '';
			return Shell::render( self::TITLE, '', Shell::notice( (string) $m['aviso_tipo'], (string) $m['aviso'] ) . $volver );
		}
		return Shell::render( self::TITLE, self::subtitle( $m ), self::listing( (array) $m['procedures'] ) );
	}

	/**
	 * Who is looking: their zones, or every one for who manages.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function subtitle( array $m ): string {
		return '' !== (string) $m['zones']
			? 'Solicitudes de los centros de: ' . (string) $m['zones']
			: 'Solicitudes de todas las zonas de sus procedimientos';
	}

	/**
	 * The procedures one may observe.
	 *
	 * @param array<int, array<string, mixed>> $procedures Rows.
	 * @return string
	 */
	private static function listing( array $procedures ): string {
		if ( array() === $procedures ) {
			return '<p class="prc-vacio">Todavía no hay ningún procedimiento con solicitudes que pueda consultar. Saldrán aquí en cuanto un procedimiento admita la consulta y algún centro de su zona solicite.</p>';
		}
		ob_start();
		?>
		<div class="prc-tabla-caja prc-consulta">
			<table class="prc-tabla prc-tabla--densa table">
				<caption class="screen-reader-text">Procedimientos que puede consultar</caption>
				<thead>
					<tr>
						<th class="prc-th" scope="col">Procedimiento</th>
						<th class="prc-th" scope="col">Estado</th>
						<th class="prc-th" scope="col">Solicitudes</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $procedures as $fila ) : ?>
						<tr>
							<td class="prc-celda" data-rotulo="Procedimiento"><a href="<?php echo esc_url( (string) $fila['url'] ); ?>"><?php echo esc_html( (string) $fila['title'] ); ?></a></td>
							<td class="prc-celda" data-rotulo="Estado"><?php echo esc_html( (string) $fila['state'] ); ?></td>
							<td class="prc-celda" data-rotulo="Solicitudes"><?php echo esc_html( (string) (int) $fila['count'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The consultation of one procedure.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function procedure( array $m ): string {
		$t = (array) $m['table'];
		ob_start();
		echo Shell::notice( (string) $m['aviso_tipo'], (string) $m['aviso'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		echo '<p><a href="' . esc_url( (string) $m['list_url'] ) . '">Volver a la consulta</a></p>';
		if ( 0 === (int) $t['all'] ) {
			echo '<p class="prc-vacio">Todavía no hay solicitudes que pueda consultar en este procedimiento.</p>';
			return Shell::render( (string) $m['title'], self::subtitle( $m ), (string) ob_get_clean() );
		}
		echo '<div class="prc-banda-tabla"><p class="prc-recuento">' . esc_html( self::count_text( $t ) ) . '</p>';
		echo self::export( $m, $t ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		echo '</div>';
		if ( array() !== (array) $t['states'] ) {
			echo ApplicationsPanelView::states( $t ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		}
		echo self::filters( $t ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		if ( 0 === (int) $t['total'] ) {
			echo '<p class="prc-vacio">Ninguna solicitud cumple el filtro.';
			echo '' !== (string) $t['reset_url'] ? ' <a href="' . esc_url( (string) $t['reset_url'] ) . '">Quitar los filtros</a>' : '';
			echo '</p>';
		} else {
			echo self::table( (array) $m['columns'], (array) $m['rows'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
			echo ApplicationsPanelView::pager( $t ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		}
		return Shell::render( (string) $m['title'], self::subtitle( $m ), (string) ob_get_clean() );
	}

	/**
	 * «Mostrando…», with the filter when there is one.
	 *
	 * @param array<string, mixed> $t Table model.
	 * @return string
	 */
	private static function count_text( array $t ): string {
		$total = (int) $t['total'];
		if ( empty( $t['active'] ) && 1 === (int) $t['pages'] ) {
			return sprintf( 1 === $total ? 'Mostrando %d solicitud. Solo lectura.' : 'Mostrando %d solicitudes. Solo lectura.', $total );
		}
		if ( 0 === $total ) {
			return sprintf( 'Ninguna de las %d solicitudes cumple el filtro.', (int) $t['all'] );
		}
		$texto = sprintf( 'Mostrando %1$d–%2$d de %3$d solicitudes', (int) $t['from'], (int) $t['to'], $total );
		return $texto . ( empty( $t['active'] ) ? '.' : sprintf( ' que cumplen el filtro, de %d en total.', (int) $t['all'] ) );
	}

	/**
	 * The export button, when the procedure lets this person export.
	 *
	 * Un envío que devuelve un fichero y no cambia nada; lleva el filtro en
	 * campos ocultos para exportar lo mismo que se ve.
	 *
	 * @param array<string, mixed> $m Model.
	 * @param array<string, mixed> $t Table model.
	 * @return string
	 */
	private static function export( array $m, array $t ): string {
		if ( true !== $m['can_export'] ) {
			return '';
		}
		$id = (int) $m['procedure_id'];
		return '<form class="prc-form prc-exportar" method="post" action="">'
			. wp_nonce_field( (string) $m['nonce'], Observe::NONCE_NAME, false, false )
			. '<input type="hidden" name="' . esc_attr( Observe::FIELD_DO ) . '" value="' . esc_attr( Observe::OP_EXPORT ) . '" />'
			. '<input type="hidden" name="' . esc_attr( Observe::FIELD_ID ) . '" value="' . esc_attr( (string) $id ) . '" />'
			. ApplicationsPanelView::carry( (array) $t['args'] )
			. '<button class="' . esc_attr( Assets::button_class() ) . '" type="submit">' . esc_html( empty( $t['active'] ) ? 'Exportar a CSV' : 'Exportar las del filtro a CSV' ) . '</button>'
			. '</form>';
	}

	/**
	 * The filter: search, state when it is shown, island and zone; GET, without script.
	 *
	 * @param array<string, mixed> $t Table model.
	 * @return string
	 */
	private static function filters( array $t ): string {
		$f        = (array) $t['filter'];
		$opciones = (array) $t['options'];
		$out      = '<form class="prc-filtros" method="get" action="' . esc_url( (string) $t['action'] ) . '" role="search" aria-label="Filtrar las solicitudes">'
			. ApplicationsPanelView::carry( (array) $t['hidden'] )
			. '<div class="prc-filtros__campo"><label class="prc-campo__etiqueta" for="prc-filtro-buscar">Centro o código</label>'
			. '<input class="prc-control" type="search" id="prc-filtro-buscar" name="' . esc_attr( ApplicationFilter::ARG_SEARCH ) . '" value="' . esc_attr( (string) $f['search'] ) . '" maxlength="' . esc_attr( (string) ApplicationFilter::MAX_SEARCH ) . '" /></div>';
		if ( array() !== (array) $t['states'] ) {
			$estados = array();
			foreach ( (array) $t['states'] as $estado ) {
				$estados[ (string) $estado['key'] ] = '' === (string) $estado['key'] ? 'Todos los estados' : (string) $estado['label'];
			}
			$out .= ApplicationsPanelView::select( 'prc-filtro-estado', ApplicationFilter::ARG_REVIEW, 'Estado de revisión', $estados, (string) $f['review'] );
		}
		if ( array() !== (array) $opciones['islands'] ) {
			$out .= ApplicationsPanelView::select( 'prc-filtro-isla', ApplicationFilter::ARG_ISLAND, 'Isla', array( '' => 'Todas las islas' ) + (array) $opciones['islands'], (string) $f['island'] );
		}
		// Solo las zonas de las solicitudes que esta persona ve: las suyas.
		if ( array() !== (array) $opciones['zones'] ) {
			$out .= ApplicationsPanelView::select( 'prc-filtro-zona', ApplicationFilter::ARG_ZONE, (string) $t['zone_label'], array( '' => 'Todas' ) + (array) $opciones['zones'], (string) $f['zone'] );
		}
		$out .= '<div class="prc-filtros__acciones"><button class="' . esc_attr( Assets::button_class() ) . '" type="submit">Filtrar</button>';
		if ( '' !== (string) $t['reset_url'] ) {
			$out .= '<a class="prc-filtros__quitar" href="' . esc_url( (string) $t['reset_url'] ) . '">Quitar los filtros</a>';
		}
		return $out . '</div></form>';
	}

	/**
	 * The table of one page, with only the columns of the consultation.
	 *
	 * @param array<string, string>            $columns Column => heading.
	 * @param array<int, array<string, mixed>> $rows    Rows.
	 * @return string
	 */
	private static function table( array $columns, array $rows ): string {
		$out = '<div class="prc-tabla-caja prc-consulta"><table class="prc-tabla prc-tabla--densa table">'
			. '<caption class="screen-reader-text">Solicitudes que puede consultar, en solo lectura</caption><thead><tr>';
		foreach ( $columns as $rotulo ) {
			$out .= '<th class="prc-th" scope="col">' . esc_html( (string) $rotulo ) . '</th>';
		}
		$out .= '</tr></thead><tbody>';
		foreach ( $rows as $fila ) {
			$out .= '<tr>';
			foreach ( $columns as $clave => $rotulo ) {
				$out .= '<td class="prc-celda" data-rotulo="' . esc_attr( (string) $rotulo ) . '">' . self::cell( $fila, (string) $clave ) . '</td>';
			}
			$out .= '</tr>';
		}
		return $out . '</tbody></table></div>';
	}

	/**
	 * One cell: a document as its download link, when allowed; the rest as text.
	 *
	 * @param array<string, mixed> $fila  Row.
	 * @param string               $clave Column key.
	 * @return string
	 */
	private static function cell( array $fila, string $clave ): string {
		$doc = (array) ( $fila['files'][ $clave ] ?? array() );
		if ( '' !== (string) ( $doc['id'] ?? '' ) ) {
			return sprintf(
				'<a class="prc-descarga" href="%1$s" download>%2$s</a>',
				esc_url( ApplicationFiles::url( (int) $doc['application'], (string) $doc['id'] ) ),
				esc_html( '' !== (string) ( $doc['name'] ?? '' ) ? (string) $doc['name'] : 'Descargar' )
			);
		}
		$texto = (string) ( $fila['cells'][ $clave ] ?? '' );
		return '' !== $texto ? esc_html( $texto ) : '<span class="prc-sindato" title="Sin datos">&mdash;</span>';
	}

	// ─── el panel «Acceso de consulta» del taller ──────────────────────────────────

	/**
	 * The «Acceso de consulta» panel: whether the procedure admits it, and what it shows.
	 *
	 * @param array<string, mixed> $m Model of the workshop; `observe` is ObserveSettings::model().
	 * @return string
	 */
	public static function settings( array $m ): string {
		$c  = (array) $m['observe'];
		$op = ProcedureEditor::OP_OBSERVE;
		ob_start();
		?>
		<form class="prc-form prc-form--taller" method="post" action="">
			<?php wp_nonce_field( ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
			<fieldset class="prc-seccion-campos">
				<legend class="prc-seccion__titulo">Consulta de solo lectura</legend>
				<p class="prc-intro">Quien consulta una zona ve, sin poder cambiar nada, las solicitudes de los centros de su zona. Lo que ve lo decide este procedimiento. El DNI o NIE, la nota de revisión y el historial no se enseñan nunca.</p>
				<?php echo self::check( ObserveSettings::FIELD_OBSERVABLE, 'Admitir la consulta de solicitudes por zona', true === $c['observable'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				<?php if ( true === $c['observable'] && '' !== (string) $c['url'] ) : ?>
					<p class="prc-campo__ayuda">Se consulta en <a href="<?php echo esc_url( (string) $c['url'] ); ?>">la consulta de este procedimiento</a>.</p>
				<?php endif; ?>
			</fieldset>
			<fieldset class="prc-seccion-campos">
				<legend class="prc-seccion__titulo">Columnas que se enseñan</legend>
				<p class="prc-intro">Sin ninguna marcada, se enseñan las de la solicitud, el centro y la coordinación.</p>
				<?php foreach ( (array) $c['groups'] as $grupo => $columnas ) : ?>
					<p class="prc-campo__etiqueta"><?php echo esc_html( (string) ( $c['group_labels'][ $grupo ] ?? $grupo ) ); ?></p>
					<div class="prc-opciones">
						<?php foreach ( (array) $columnas as $clave => $rotulo ) : ?>
							<div class="prc-opcion">
								<input type="checkbox" id="<?php echo esc_attr( 'prc-observe-' . sanitize_html_class( str_replace( ':', '-', (string) $clave ) ) ); ?>" name="<?php echo esc_attr( ObserveSettings::FIELD_COLUMNS ); ?>[]" value="<?php echo esc_attr( (string) $clave ); ?>" <?php checked( in_array( (string) $clave, (array) $c['columns'], true ) ); ?> />
								<label for="<?php echo esc_attr( 'prc-observe-' . sanitize_html_class( str_replace( ':', '-', (string) $clave ) ) ); ?>"><?php echo esc_html( (string) $rotulo ); ?></label>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
			</fieldset>
			<fieldset class="prc-seccion-campos">
				<legend class="prc-seccion__titulo">Qué más puede hacer quien consulta</legend>
				<?php echo self::check( ObserveSettings::FIELD_FILES, 'Pueden descargar los documentos de las solicitudes de su zona', true === $c['files'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				<?php echo self::check( ObserveSettings::FIELD_EXPORT, 'Pueden exportar a CSV lo que ven', true === $c['export'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				<?php echo self::check( ObserveSettings::FIELD_NOTIFY, 'Avisar también a quienes consultan la zona de cada alta, cambio o baja de personas', true === $c['notify'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			</fieldset>
			<?php echo PanelParts::submit( 'Guardar la consulta' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * One checkbox with its label.
	 *
	 * @param string $name    Field name.
	 * @param string $label   What it says.
	 * @param bool   $checked Whether it is ticked.
	 * @return string
	 */
	private static function check( string $name, string $label, bool $checked ): string {
		return '<div class="prc-opcion"><input type="checkbox" id="' . esc_attr( 'prc-' . $name ) . '" name="' . esc_attr( $name ) . '" value="1"' . ( $checked ? ' checked="checked"' : '' ) . ' />'
			. '<label for="' . esc_attr( 'prc-' . $name ) . '">' . esc_html( $label ) . '</label></div>';
	}
}
