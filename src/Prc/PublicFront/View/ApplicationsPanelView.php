<?php
/**
 * The «Solicitudes» panel of the workshop, painted from what
 * ApplicationsPanel::model() decided.
 *
 * @package Prc
 */

namespace Prc\PublicFront\View;

use Prc\Domain\ApplicationFilter;
use Prc\Domain\ProcedureQuestions;
use Prc\PublicFront\ApplicationFiles;
use Prc\PublicFront\Applications;
use Prc\PublicFront\ApplicationsPanel;
use Prc\PublicFront\Assets;
use Prc\PublicFront\ProcedureEditor;

/**
 * Solo pinta: no lee la petición, no consulta, no decide.
 *
 * La tabla de solicitudes con su barra —contadores por estado, filtros y
 * páginas—, la revisión de cada una y la revisión en bloque. El filtro y las
 * páginas son enlaces y un formulario GET: funcionan sin guion. Lo que
 * escribe —revisar, revisar en bloque y exportar— se apaga entero cuando la
 * persona no gestiona las solicitudes; filtrar y pasar de página, no.
 */
final class ApplicationsPanelView {

	/** ID of the bulk review form: the row checkboxes point at it with `form`. */
	public const BULK_FORM = 'prc-bloque';

	/**
	 * Paint the panel.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	public static function html( array $m ): string {
		$t       = (array) ( $m['table'] ?? array() );
		$docente = ! empty( $t['teachers'] );
		if ( 0 === (int) ( $t['all'] ?? 0 ) ) {
			return '<section class="prc-seccion-campos"><h2 class="prc-seccion__titulo">Todavía no hay solicitudes</h2>'
				. '<p class="prc-vacio">Cuando ' . ( $docente ? 'un docente' : 'un centro' ) . ' solicite saldrá en esta tabla, y desde ella podrá revisar la solicitud y exportar la lista a CSV.</p></section>';
		}
		$cerrado = true !== $m['can_review'];

		ob_start();
		?>
		<div class="prc-banda-tabla">
			<p class="prc-recuento"><?php echo esc_html( self::count_text( $t ) ); ?></p>
			<?php echo $cerrado ? '<fieldset class="prc-solo-lectura" disabled><legend class="screen-reader-text">Solo lectura</legend>' : ''; ?>
			<?php echo ExportDialogView::html( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			<?php echo $cerrado ? '</fieldset>' : ''; ?>
		</div>
		<?php
		echo self::states( $t ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		echo self::filters( $m, $t ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		if ( 0 === (int) $t['total'] ) {
			echo '<p class="prc-vacio">Ninguna solicitud cumple el filtro.';
			echo '' !== (string) $t['reset_url'] ? ' <a href="' . esc_url( (string) $t['reset_url'] ) . '">Quitar los filtros</a>' : '';
			echo '</p>';
			return (string) ob_get_clean();
		}
		echo $cerrado ? '<fieldset class="prc-solo-lectura" disabled><legend class="screen-reader-text">Solo lectura</legend>' : '';
		echo self::bulk_form( $m, $t ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		echo self::table( $m, $docente ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		echo $cerrado ? '</fieldset>' : '';
		echo self::pager( $t ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		return (string) ob_get_clean();
	}

	/**
	 * Hidden fields that carry the filter and the page back to the table.
	 *
	 * @param array<string, string> $args Query args of the filter, not encoded.
	 * @return string
	 */
	public static function carry( array $args ): string {
		$out = '';
		foreach ( $args as $nombre => $valor ) {
			$out .= '<input type="hidden" name="' . esc_attr( (string) $nombre ) . '" value="' . esc_attr( (string) $valor ) . '" />';
		}
		return $out;
	}

	/**
	 * «Mostrando…»: with one page and no filter, as it always said.
	 *
	 * @param array<string, mixed> $t Table model.
	 * @return string
	 */
	private static function count_text( array $t ): string {
		$total = (int) $t['total'];
		if ( empty( $t['active'] ) && 1 === (int) $t['pages'] ) {
			return sprintf( 1 === $total ? 'Mostrando %d solicitud.' : 'Mostrando %d solicitudes.', $total )
				. ' Cada una se revisa desde su fila, o varias a la vez marcándolas; al exportar se eligen las columnas, una fila por solicitud o por persona, o el fichero de alta de usuarios.';
		}
		if ( 0 === $total ) {
			return sprintf( 'Ninguna de las %d solicitudes cumple el filtro.', (int) $t['all'] );
		}
		$texto = sprintf( 'Mostrando %1$d–%2$d de %3$d solicitudes', (int) $t['from'], (int) $t['to'], $total );
		return $texto . ( empty( $t['active'] ) ? '.' : sprintf( ' que cumplen el filtro, de %d en total.', (int) $t['all'] ) );
	}

	/**
	 * How many there are in each review state, each a shortcut to filter by it.
	 *
	 * Se cuentan sobre el total, sin filtro: son el resumen del procedimiento.
	 *
	 * @param array<string, mixed> $t Table model.
	 * @return string
	 */
	private static function states( array $t ): string {
		$out = '<ul class="prc-contadores" aria-label="Solicitudes por estado de revisión">';
		foreach ( (array) $t['states'] as $estado ) {
			$out .= sprintf(
				'<li><a class="prc-contadores__enlace" href="%1$s"%2$s>%3$s <span class="prc-contadores__cifra">%4$d</span></a></li>',
				esc_url( (string) $estado['url'] ),
				$estado['current'] ? ' aria-current="true"' : '',
				esc_html( (string) $estado['label'] ),
				(int) $estado['count']
			);
		}
		return $out . '</ul>';
	}

	/**
	 * The filter: a GET form, so it can be bookmarked and works without script.
	 *
	 * @param array<string, mixed> $m Model.
	 * @param array<string, mixed> $t Table model.
	 * @return string
	 */
	private static function filters( array $m, array $t ): string {
		$f        = (array) $t['filter'];
		$opciones = (array) $t['options'];
		$estados  = array( '' => 'Todos los estados' ) + (array) $m['review_states'];
		ob_start();
		?>
		<form class="prc-filtros" method="get" action="<?php echo esc_url( (string) $t['action'] ); ?>" role="search" aria-label="Filtrar las solicitudes">
			<?php echo self::carry( (array) $t['hidden'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			<div class="prc-filtros__campo">
				<label class="prc-campo__etiqueta" for="prc-filtro-buscar"><?php echo esc_html( empty( $t['teachers'] ) ? 'Centro o código' : 'Nombre de la persona' ); ?></label>
				<input class="prc-control" type="search" id="prc-filtro-buscar" name="<?php echo esc_attr( ApplicationFilter::ARG_SEARCH ); ?>" value="<?php echo esc_attr( (string) $f['search'] ); ?>" maxlength="<?php echo esc_attr( (string) ApplicationFilter::MAX_SEARCH ); ?>" />
			</div>
			<?php echo self::select( 'prc-filtro-estado', ApplicationFilter::ARG_REVIEW, 'Estado de revisión', $estados, (string) $f['review'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			<?php
			if ( array() !== (array) $opciones['islands'] ) {
				echo self::select( 'prc-filtro-isla', ApplicationFilter::ARG_ISLAND, 'Isla', array( '' => 'Todas las islas' ) + (array) $opciones['islands'], (string) $f['island'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
			}
			if ( array() !== (array) $opciones['zones'] ) {
				echo self::select( 'prc-filtro-zona', ApplicationFilter::ARG_ZONE, (string) $t['zone_label'], array( '' => 'Todas' ) + (array) $opciones['zones'], (string) $f['zone'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
			}
			?>
			<?php if ( ! empty( $t['incomplete']['possible'] ) ) : ?>
				<label class="prc-casilla prc-filtros__casilla">
					<input type="checkbox" name="<?php echo esc_attr( ApplicationFilter::ARG_INCOMPLETE ); ?>" value="1" <?php checked( true === $f['incomplete'] ); ?> />
					<?php echo esc_html( sprintf( 'Solo las incompletas (%d)', (int) $t['incomplete']['count'] ) ); ?>
				</label>
			<?php endif; ?>
			<div class="prc-filtros__acciones">
				<button class="<?php echo esc_attr( Assets::button_class() ); ?>" type="submit">Filtrar</button>
				<?php if ( '' !== (string) $t['reset_url'] ) : ?>
					<a class="prc-filtros__quitar" href="<?php echo esc_url( (string) $t['reset_url'] ); ?>">Quitar los filtros</a>
				<?php endif; ?>
			</div>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * One select of the filter.
	 *
	 * @param string                $id       Element ID.
	 * @param string                $name     Query arg.
	 * @param string                $label    Visible label.
	 * @param array<string, string> $options  value => label.
	 * @param string                $selected Chosen value.
	 * @return string
	 */
	private static function select( string $id, string $name, string $label, array $options, string $selected ): string {
		$out = '<div class="prc-filtros__campo"><label class="prc-campo__etiqueta" for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label>'
			. '<select class="prc-control" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
		foreach ( $options as $valor => $rotulo ) {
			$out .= '<option value="' . esc_attr( (string) $valor ) . '"' . selected( (string) $valor, $selected, false ) . '>' . esc_html( (string) $rotulo ) . '</option>';
		}
		return $out . '</select></div>';
	}

	/**
	 * The bulk review: one state and one note for the ticked ones.
	 *
	 * Las casillas de cada fila están en la tabla y apuntan aquí con el
	 * atributo `form`. «Marcar las de esta página» es un campo que lee el
	 * servidor —sin guion, marca la página entera—; con guion, además, marca
	 * y desmarca las casillas de las filas.
	 *
	 * @param array<string, mixed> $m Model.
	 * @param array<string, mixed> $t Table model.
	 * @return string
	 */
	private static function bulk_form( array $m, array $t ): string {
		$op     = ProcedureEditor::OP_BULK_REVIEW;
		$pagina = array_map( 'intval', (array) $t['page_ids'] );
		ob_start();
		?>
		<details class="prc-bloque">
			<summary class="prc-revision__abrir">Revisar varias a la vez</summary>
			<form class="prc-form prc-form--taller prc-bloque__form" id="<?php echo esc_attr( self::BULK_FORM ); ?>" method="post" action=""
				data-prc-confirm="¿Cambiar la revisión de las solicitudes marcadas? Cada una recibe el mismo estado y la misma nota, y a cada centro le llega su aviso por correo."
				data-prc-confirm-ok="Cambiar la revisión">
				<?php wp_nonce_field( ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op ), false ); ?>
				<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
				<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
				<input type="hidden" name="<?php echo esc_attr( ApplicationsPanel::FIELD_PAGE_ROWS ); ?>" value="<?php echo esc_attr( implode( ',', $pagina ) ); ?>" />
				<?php echo self::carry( (array) $t['args'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				<p class="prc-campo__ayuda">Marque las solicitudes en la primera columna de la tabla, o todas las de esta página. Como mucho <?php echo esc_html( (string) (int) $t['bulk_max'] ); ?> a la vez: a cada centro le sale su correo en el mismo envío, así que con muchas tarda un poco.</p>
				<label class="prc-casilla">
					<input type="checkbox" name="<?php echo esc_attr( ApplicationsPanel::FIELD_PAGE ); ?>" value="1" data-prc-bulk-page />
					<?php echo esc_html( sprintf( 'Marcar las de esta página (%d)', count( $pagina ) ) ); ?>
				</label>
				<div class="prc-campo">
					<label class="prc-campo__etiqueta" for="prc-bloque-estado">Estado</label>
					<select class="prc-control" id="prc-bloque-estado" name="<?php echo esc_attr( ProcedureEditor::FIELD_STATE ); ?>">
						<?php foreach ( (array) $m['review_states'] as $clave => $rotulo ) : ?>
							<option value="<?php echo esc_attr( (string) $clave ); ?>"><?php echo esc_html( (string) $rotulo ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="prc-campo">
					<label class="prc-campo__etiqueta" for="prc-bloque-nota">Nota para los centros</label>
					<textarea class="prc-control" id="prc-bloque-nota" name="<?php echo esc_attr( ProcedureEditor::FIELD_NOTE ); ?>" rows="3" aria-describedby="prc-bloque-nota-ayuda"></textarea>
					<p class="prc-campo__ayuda" id="prc-bloque-nota-ayuda">La nota se aplica a todas las marcadas y sustituye la que tuvieran. En blanco, cada una conserva la suya; al pedir subsanar y al excluir es obligatoria. Cada centro la lee en su solicitud.</p>
				</div>
				<?php echo PanelParts::submit( 'Cambiar la revisión de las marcadas' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			</form>
		</details>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The table of one page.
	 *
	 * @param array<string, mixed> $m       Model.
	 * @param bool                 $docente Whether the applications are of teachers.
	 * @return string
	 */
	private static function table( array $m, bool $docente ): string {
		ob_start();
		?>
		<div class="prc-tabla-caja prc-panel-solicitudes">
			<table class="prc-tabla prc-tabla--densa table">
				<caption class="screen-reader-text">Solicitudes presentadas a este procedimiento</caption>
				<thead>
					<tr>
						<th class="prc-th prc-th--marca" scope="col"><span class="screen-reader-text">Marcar para revisar en bloque</span></th>
						<?php if ( $docente ) : ?>
							<th class="prc-th" scope="col">Docente</th>
							<th class="prc-th" scope="col">Correo</th>
						<?php else : ?>
							<th class="prc-th" scope="col">Centro</th>
							<th class="prc-th" scope="col">Código</th>
						<?php endif; ?>
						<th class="prc-th" scope="col">Fecha</th>
						<?php if ( ! $docente ) : ?>
							<th class="prc-th" scope="col">Cargo</th>
							<th class="prc-th" scope="col">Coordinación</th>
						<?php endif; ?>
						<?php echo self::score_cell( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
						<th class="prc-th" scope="col">Revisión</th>
						<th class="prc-th" scope="col">Acciones</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( (array) $m['applications'] as $fila ) : ?>
						<?php $quien = $docente ? (string) $fila['applicant'] : ( '' !== (string) $fila['centre'] ? (string) $fila['centre'] : (string) $fila['code'] ); ?>
						<tr data-estado="<?php echo esc_attr( (string) $fila['state_key'] ); ?>"<?php echo array() !== (array) ( $fila['reasons'] ?? array() ) ? ' data-incompleta' : ''; ?>>
							<td class="prc-celda prc-celda--marca" data-rotulo="Marcar">
								<input type="checkbox" form="<?php echo esc_attr( self::BULK_FORM ); ?>" name="<?php echo esc_attr( ApplicationsPanel::FIELD_ROWS ); ?>[]" value="<?php echo esc_attr( (string) (int) $fila['id'] ); ?>" data-prc-bulk-row
									aria-label="<?php echo esc_attr( 'Marcar la solicitud de ' . $quien ); ?>" />
							</td>
							<?php if ( $docente ) : ?>
								<td class="prc-celda" data-rotulo="Docente"><?php echo esc_html( (string) $fila['applicant'] ); ?></td>
								<td class="prc-celda" data-rotulo="Correo"><?php echo esc_html( (string) $fila['email'] ); ?></td>
							<?php else : ?>
								<td class="prc-celda" data-rotulo="Centro"><?php echo esc_html( '' !== (string) $fila['centre'] ? (string) $fila['centre'] : '(centro fuera del catálogo)' ); ?></td>
								<td class="prc-celda" data-rotulo="Código"><?php echo esc_html( (string) $fila['code'] ); ?></td>
							<?php endif; ?>
							<td class="prc-celda" data-rotulo="Fecha"><?php echo esc_html( (string) $fila['date_label'] ); ?></td>
							<?php if ( ! $docente ) : ?>
								<td class="prc-celda" data-rotulo="Cargo"><?php echo esc_html( (string) $fila['position'] ); ?></td>
								<td class="prc-celda" data-rotulo="Coordinación"><?php echo self::dash( trim( (string) $fila['coordinator'] . ' ' . (string) ( $fila['coordinator_dni'] ?? '' ) . ' ' . (string) $fila['coordinator_email'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></td>
							<?php endif; ?>
							<?php echo self::score_cell( $m, $fila ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
							<td class="prc-celda" data-rotulo="Revisión">
								<?php echo ProcedureEditorView::badge( (string) $fila['state_key'], (string) $fila['state'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
								<?php echo self::reasons( (array) ( $fila['reasons'] ?? array() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
							</td>
							<td class="prc-celda" data-rotulo="Acciones"><?php echo self::review_form( $m, $fila ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Why an application is incomplete, under its review state.
	 *
	 * @param string[] $reasons Reasons, in words.
	 * @return string
	 */
	private static function reasons( array $reasons ): string {
		if ( array() === $reasons ) {
			return '';
		}
		$out = '<div class="prc-incompleta"><span class="prc-incompleta__titulo">Incompleta</span><ul class="prc-incompleta__motivos">';
		foreach ( $reasons as $motivo ) {
			$out .= '<li>' . esc_html( (string) $motivo ) . '</li>';
		}
		return $out . '</ul></div>';
	}

	/**
	 * The links to the other pages, when there is more than one.
	 *
	 * @param array<string, mixed> $t Table model.
	 * @return string
	 */
	private static function pager( array $t ): string {
		if ( (int) $t['pages'] <= 1 ) {
			return '';
		}
		$p   = (array) $t['pager'];
		$out = '<nav class="prc-paginacion" aria-label="Páginas de solicitudes"><ul class="prc-paginacion__lista">';
		if ( '' !== (string) $p['prev'] ) {
			$out .= '<li><a class="prc-paginacion__enlace" href="' . esc_url( (string) $p['prev'] ) . '" rel="prev">Anterior</a></li>';
		}
		foreach ( (array) $p['pages'] as $una ) {
			if ( 0 === (int) $una['n'] ) {
				$out .= '<li class="prc-paginacion__hueco" aria-hidden="true">…</li>';
			} elseif ( (int) $una['n'] === (int) $t['page'] ) {
				$out .= '<li><span class="prc-paginacion__actual" aria-current="page">' . (int) $una['n'] . '</span></li>';
			} else {
				$out .= '<li><a class="prc-paginacion__enlace" href="' . esc_url( (string) $una['url'] ) . '">' . (int) $una['n'] . '</a></li>';
			}
		}
		if ( '' !== (string) $p['next'] ) {
			$out .= '<li><a class="prc-paginacion__enlace" href="' . esc_url( (string) $p['next'] ) . '" rel="next">Siguiente</a></li>';
		}
		return $out . '</ul></nav>';
	}

	/**
	 * The «Puntos» heading, or one row's score, when some question gives points.
	 *
	 * Los puntos no se guardan: es una columna calculada que solo existe si
	 * alguna pregunta los da (ADR-0040).
	 *
	 * @param array<string, mixed>      $m    Model.
	 * @param array<string, mixed>|null $fila Row; null for the heading.
	 * @return string
	 */
	private static function score_cell( array $m, ?array $fila = null ): string {
		if ( ! isset( $m['app_cols']['score'] ) ) {
			return '';
		}
		if ( null === $fila ) {
			return '<th class="prc-th" scope="col">Puntos</th>';
		}
		return '<td class="prc-celda" data-rotulo="Puntos">' . esc_html( (string) ( $fila['score'] ?? '' ) ) . '</td>';
	}

	/**
	 * A cell that may be empty, with the dash the whole application uses.
	 *
	 * @param string $texto What the cell says.
	 * @return string
	 */
	private static function dash( string $texto ): string {
		return '' !== $texto ? esc_html( $texto ) : '<span class="prc-sindato" title="Sin datos">&mdash;</span>';
	}

	/**
	 * The download link of one private document of an application.
	 *
	 * El enlace se compone **aquí**, al pintar: es presentación, y por eso no
	 * viaja dentro de la fila ni acaba en el CSV (ADR-0028). Apunta al
	 * manejador del aplicativo, que vuelve a comprobar quién pregunta con
	 * `ProcedureAccess::can_view_application()`: este enlace no autoriza nada.
	 *
	 * @param array<string, mixed> $fila One row.
	 * @param string               $key  Question key.
	 * @return string
	 */
	private static function download( array $fila, string $key ): string {
		$adjuntos = isset( $fila[ Applications::KEY_FILES ] ) && is_array( $fila[ Applications::KEY_FILES ] )
			? $fila[ Applications::KEY_FILES ]
			: array();
		$doc      = isset( $adjuntos[ $key ] ) && is_array( $adjuntos[ $key ] ) ? $adjuntos[ $key ] : array();
		if ( array() === $doc || '' === (string) ( $doc['id'] ?? '' ) ) {
			return '<span class="prc-sindato" title="Sin documento">&mdash;</span>';
		}

		return sprintf(
			'<a class="prc-descarga" href="%1$s" download>%2$s</a>',
			esc_url( ApplicationFiles::url( (int) ( $doc['application'] ?? 0 ), (string) $doc['id'] ) ),
			esc_html( '' !== (string) ( $doc['name'] ?? '' ) ? (string) $doc['name'] : 'Descargar' )
		);
	}

	/**
	 * The review of one application: its answers, its state and its note.
	 *
	 * El filtro y la página viajan ocultos: guardar vuelve a la misma vista.
	 *
	 * @param array<string, mixed>  $m    Model.
	 * @param array<string, string> $fila Row.
	 * @return string
	 */
	private static function review_form( array $m, array $fila ): string {
		$op = ProcedureEditor::OP_REVIEW;
		$id = (int) $fila['id'];

		ob_start();
		?>
		<details class="prc-revision"<?php echo empty( $m['people'][ $id ]['open'] ) ? '' : ' open'; ?>>
			<summary class="prc-revision__abrir">Revisar</summary>
			<dl class="prc-respuestas">
				<dt>Presentada por</dt>
				<dd><?php echo esc_html( trim( (string) $fila['applicant'] . ' ' . (string) $fila['email'] ) ); ?></dd>
				<?php foreach ( (array) $m['questions'] as $pregunta ) : ?>
					<dt><?php echo esc_html( (string) $pregunta['label'] ); ?></dt>
					<?php
					// Un documento se pinta como enlace de descarga; lo demás,
					// como texto. Lo decide el tipo de la pregunta (ADR-0028).
					$respuesta = ProcedureQuestions::TYPE_FILE === (string) ( $pregunta['type'] ?? '' )
						? self::download( $fila, (string) $pregunta['key'] )
						: esc_html( '' !== (string) ( $fila[ $pregunta['key'] ] ?? '' ) ? (string) $fila[ $pregunta['key'] ] : '—' );
					?>
					<dd><?php echo $respuesta; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado justo arriba. ?></dd>
				<?php endforeach; ?>
			</dl>
			<?php echo PeopleView::review( (array) ( $m['people'][ $id ] ?? array() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			<form class="prc-form prc-form--taller prc-revision__form" method="post" action="">
				<?php wp_nonce_field( ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op, $id ), false ); ?>
				<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
				<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
				<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_ROW ); ?>" value="<?php echo esc_attr( (string) $id ); ?>" />
				<?php echo self::carry( (array) ( $m['table']['args'] ?? array() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				<div class="prc-campo">
					<label class="prc-campo__etiqueta" for="prc-state-<?php echo esc_attr( (string) $id ); ?>">Estado</label>
					<select class="prc-control" id="prc-state-<?php echo esc_attr( (string) $id ); ?>" name="<?php echo esc_attr( ProcedureEditor::FIELD_STATE ); ?>">
						<?php foreach ( (array) $m['review_states'] as $clave => $rotulo ) : ?>
							<option value="<?php echo esc_attr( (string) $clave ); ?>" <?php selected( (string) $clave, (string) $fila['state_key'] ); ?>><?php echo esc_html( (string) $rotulo ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="prc-campo">
					<label class="prc-campo__etiqueta" for="prc-note-<?php echo esc_attr( (string) $id ); ?>">Nota para el centro</label>
					<textarea class="prc-control" id="prc-note-<?php echo esc_attr( (string) $id ); ?>" name="<?php echo esc_attr( ProcedureEditor::FIELD_NOTE ); ?>" rows="3"
						aria-describedby="prc-note-ayuda-<?php echo esc_attr( (string) $id ); ?>"><?php echo esc_textarea( (string) $fila['note'] ); ?></textarea>
					<p class="prc-campo__ayuda" id="prc-note-ayuda-<?php echo esc_attr( (string) $id ); ?>">Obligatoria al pedir subsanar y al excluir: el centro la lee en su solicitud.</p>
				</div>
				<?php echo PanelParts::submit( 'Guardar la revisión' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			</form>
		</details>
		<?php
		return (string) ob_get_clean();
	}
}
