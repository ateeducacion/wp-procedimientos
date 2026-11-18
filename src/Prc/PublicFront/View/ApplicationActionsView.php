<?php
/**
 * What acting on an application looks like: withdrawing one's own, and what
 * whoever reviews does from the «Solicitudes» panel.
 *
 * @package Prc
 */

namespace Prc\PublicFront\View;

use Prc\Meta\ApplicationMetaKeys;
use Prc\PublicFront\ApplicationActionsPanel;
use Prc\PublicFront\Assets;
use Prc\PublicFront\ProcedureEditor;
use Prc\PublicFront\Shell;
use Prc\PublicFront\WithdrawForm;

/**
 * Solo pinta: no lee la petición, no consulta, no decide (PLAN-0003, PR 9).
 *
 * Retirar —el centro o quien revisa— pide confirmación con el patrón de tres
 * escalones (`data-prc-confirm`: SweetAlert2, `confirm()` y, sin guion, el
 * envío). El historial y la nota interna solo se pintan dentro del panel
 * «Solicitudes», que es de quien revisa.
 */
final class ApplicationActionsView {

	// ─── quien solicita ────────────────────────────────────────────────────

	/**
	 * The «Retirar la solicitud» section of the application screen.
	 *
	 * @param array<string, mixed> $w What WithdrawForm::model() returned.
	 * @return string
	 */
	public static function withdraw( array $w ): string {
		if ( empty( $w['show'] ) || ( empty( $w['can'] ) && '' === (string) ( $w['rejected'] ?? '' ) ) ) {
			return Shell::notice( 'error', (string) ( $w['rejected'] ?? '' ) );
		}
		ob_start();
		?>
		<section class="prc-seccion" id="<?php echo esc_attr( WithdrawForm::ANCHOR ); ?>">
			<h3 class="prc-seccion__titulo">Retirar la solicitud</h3>
			<?php echo Shell::notice( 'error', (string) $w['rejected'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			<?php if ( ! empty( $w['can'] ) ) : ?>
				<p>Si ya no quiere participar en este procedimiento, puede retirar la solicitud mientras el plazo siga abierto. Dejará de contar y, mientras haya plazo, podrá presentar otra nueva.</p>
				<form class="prc-form prc-acciones" method="post" action="<?php echo esc_url( (string) $w['action'] ); ?>"
					data-prc-confirm="¿Retirar su solicitud? Dejará de contar para este procedimiento. Mientras el plazo siga abierto podrá presentar otra, pero tendrá que rellenarla de nuevo."
					data-prc-confirm-ok="Retirar la solicitud" data-prc-once>
					<?php wp_nonce_field( (string) $w['nonce_action'], WithdrawForm::NONCE_FIELD, false ); ?>
					<input type="hidden" name="<?php echo esc_attr( WithdrawForm::FIELD_OP ); ?>" value="<?php echo esc_attr( WithdrawForm::OP_WITHDRAW ); ?>" />
					<input type="hidden" name="<?php echo esc_attr( WithdrawForm::FIELD_APPLICATION ); ?>" value="<?php echo esc_attr( (string) (int) $w['application'] ); ?>" />
					<button class="<?php echo esc_attr( Assets::button_class( 'danger' ) ); ?>" type="submit">Retirar la solicitud</button>
				</form>
			<?php else : ?>
				<p><?php echo esc_html( (string) $w['why'] ); ?></p>
			<?php endif; ?>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	// ─── quien revisa: arriba del panel ────────────────────────────────────

	/**
	 * The bar over the table: «Retiradas (N)» and «Presentar en nombre de un centro».
	 *
	 * @param array<string, mixed> $m Model of the workshop.
	 * @return string
	 */
	public static function bar( array $m ): string {
		$a = (array) ( $m['actions'] ?? array() );
		if ( array() === $a || true !== ( $m['can_review'] ?? false ) ) {
			return '';
		}
		$out  = '<p class="prc-retiradas-enlace">';
		$out .= ! empty( $a['withdrawn_open'] )
			? '<a href="' . esc_url( (string) $a['back_url'] ) . '">Volver a las solicitudes</a>'
			: '<a href="' . esc_url( (string) $a['withdrawn_url'] ) . '">' . esc_html( sprintf( 'Retiradas (%d)', (int) $a['withdrawn_count'] ) ) . '</a>';
		return $out . '</p>' . self::submit_for( (int) $m['procedure_id'], (array) $a['submit_for'] );
	}

	/**
	 * «Presentar en nombre de un centro»: a search in the catalogue and the form.
	 *
	 * @param int                  $procedure_id Procedure ID.
	 * @param array<string, mixed> $s            What ApplicationActionsPanel::model() returned in `submit_for`.
	 * @return string
	 */
	private static function submit_for( int $procedure_id, array $s ): string {
		if ( empty( $s['can'] ) ) {
			return '';
		}
		$op = ApplicationActionsPanel::OP_SUBMIT_FOR;
		ob_start();
		?>
		<details class="prc-en-nombre"<?php echo empty( $s['open'] ) ? '' : ' open'; ?>>
			<summary class="prc-revision__abrir">Presentar en nombre de un centro</summary>
			<p class="prc-campo__ayuda">Para un centro que no pudo entrar a tiempo: se crea su solicitud vacía, en «Presentada», y el centro la completa con su formulario de siempre hasta el día que ponga aquí. El centro no recibe ningún correo: avísele por su cuenta.</p>
			<form class="prc-filtros" method="get" action="<?php echo esc_url( (string) $s['action'] ); ?>" role="search" aria-label="Buscar un centro en el catálogo">
				<?php echo ApplicationsPanelView::carry( (array) $s['hidden'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				<div class="prc-filtros__campo">
					<label class="prc-campo__etiqueta" for="prc-buscar-centro">Buscar un centro por código o nombre</label>
					<input class="prc-control" type="search" id="prc-buscar-centro" name="<?php echo esc_attr( ApplicationActionsPanel::ARG_FIND ); ?>" value="<?php echo esc_attr( (string) $s['search'] ); ?>" maxlength="100" />
				</div>
				<div class="prc-filtros__acciones"><button class="<?php echo esc_attr( Assets::button_class() ); ?>" type="submit">Buscar</button></div>
			</form>
			<?php if ( '' !== (string) $s['search'] ) : ?>
				<?php if ( array() === (array) $s['results'] ) : ?>
					<p class="prc-vacio">Ningún centro del catálogo tiene ese código o ese nombre.</p>
				<?php else : ?>
					<ul class="prc-centros-encontrados">
						<?php foreach ( (array) $s['results'] as $centro ) : ?>
							<li><a href="<?php echo esc_url( add_query_arg( ApplicationActionsPanel::ARG_PICK, rawurlencode( (string) $centro['code'] ), (string) $s['pick_url'] ) ); ?>"><?php echo esc_html( (string) $centro['name'] . ' (' . (string) $centro['code'] . ')' ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			<?php endif; ?>
			<form class="prc-form prc-form--taller" method="post" action="" data-prc-once>
				<?php wp_nonce_field( ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op ), false ); ?>
				<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
				<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) $procedure_id ); ?>" />
				<div class="prc-campo">
					<label class="prc-campo__etiqueta" for="prc-en-nombre-codigo">Código del centro</label>
					<input class="prc-control" type="text" id="prc-en-nombre-codigo" name="<?php echo esc_attr( ApplicationActionsPanel::FIELD_CODE ); ?>" value="<?php echo esc_attr( (string) $s['code'] ); ?>" required maxlength="20" autocomplete="off" />
				</div>
				<div class="prc-campo">
					<label class="prc-campo__etiqueta" for="prc-en-nombre-hasta">Puede completarla hasta el</label>
					<input class="prc-control" type="date" id="prc-en-nombre-hasta" name="<?php echo esc_attr( ApplicationActionsPanel::FIELD_LATE ); ?>" value="<?php echo esc_attr( (string) $s['late'] ); ?>" min="<?php echo esc_attr( (string) $s['today'] ); ?>" required />
				</div>
				<label class="prc-casilla">
					<input type="checkbox" name="<?php echo esc_attr( ApplicationActionsPanel::FIELD_OVERRIDE ); ?>" value="1" <?php checked( ! empty( $s['override'] ) ); ?> />
					Aunque no cumpla los requisitos del procedimiento (queda anotado en su historial)
				</label>
				<?php echo PanelParts::submit( 'Presentar en su nombre' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			</form>
		</details>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The withdrawn applications, each with «Restaurar» and its history.
	 *
	 * @param array<string, mixed> $m Model of the workshop.
	 * @return string
	 */
	public static function withdrawn( array $m ): string {
		$a     = (array) ( $m['actions'] ?? array() );
		$filas = (array) ( $a['withdrawn'] ?? array() );
		if ( array() === $filas ) {
			return '<section class="prc-seccion-campos"><h2 class="prc-seccion__titulo">Retiradas</h2><p class="prc-vacio">No hay solicitudes retiradas en este procedimiento.</p></section>';
		}
		$op = ApplicationActionsPanel::OP_RESTORE;
		ob_start();
		?>
		<section class="prc-seccion-campos">
			<h2 class="prc-seccion__titulo">Retiradas</h2>
			<p class="prc-campo__ayuda">Solicitudes en la papelera: ya no cuentan, no salen en la consulta ni en las exportaciones. Se restauran como estaban, salvo que el centro haya presentado otra entretanto.</p>
			<div class="prc-tabla-caja">
				<table class="prc-tabla prc-tabla--densa table">
					<caption class="screen-reader-text">Solicitudes retiradas de este procedimiento</caption>
					<thead><tr><th class="prc-th" scope="col">Solicitud</th><th class="prc-th" scope="col">Retirada</th><th class="prc-th" scope="col">Por</th><th class="prc-th" scope="col">Motivo</th><th class="prc-th" scope="col">Acciones</th></tr></thead>
					<tbody>
						<?php foreach ( $filas as $fila ) : ?>
							<tr>
								<td class="prc-celda" data-rotulo="Solicitud"><?php echo esc_html( (string) $fila['who'] ); ?></td>
								<td class="prc-celda" data-rotulo="Retirada"><?php echo esc_html( (string) $fila['when'] ); ?></td>
								<td class="prc-celda" data-rotulo="Por"><?php echo esc_html( (string) $fila['by'] ); ?></td>
								<td class="prc-celda" data-rotulo="Motivo"><?php echo esc_html( '' !== (string) $fila['reason'] ? (string) $fila['reason'] : '—' ); ?></td>
								<td class="prc-celda" data-rotulo="Acciones">
									<?php if ( ! empty( $fila['can'] ) ) : ?>
										<form class="prc-acciones" method="post" action="">
											<?php wp_nonce_field( ProcedureEditor::nonce_action( $op, (int) $fila['id'] ), ProcedureEditor::nonce_name( $op, (int) $fila['id'] ), false ); ?>
											<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
											<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
											<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_ROW ); ?>" value="<?php echo esc_attr( (string) (int) $fila['id'] ); ?>" />
											<input type="hidden" name="<?php echo esc_attr( ApplicationActionsPanel::ARG_WITHDRAWN ); ?>" value="1" />
											<button class="<?php echo esc_attr( Assets::button_class() ); ?>" type="submit">Restaurar</button>
										</form>
									<?php else : ?>
										<span class="prc-campo__ayuda"><?php echo esc_html( (string) $fila['why'] ); ?></span>
									<?php endif; ?>
									<?php echo self::entries( (array) $fila['history'], (int) $fila['id'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	// ─── quien revisa: en la revisión de cada solicitud ────────────────────

	/**
	 * The internal note, inside the review form of one application.
	 *
	 * @param array<string, mixed> $m  Model of the workshop.
	 * @param int                  $id Application ID.
	 * @return string
	 */
	public static function internal_note( array $m, int $id ): string {
		$fila = (array) ( $m['actions']['rows'][ $id ] ?? array() );
		return '<div class="prc-campo">'
			. '<label class="prc-campo__etiqueta" for="prc-nota-interna-' . esc_attr( (string) $id ) . '">Nota interna</label>'
			. '<textarea class="prc-control" id="prc-nota-interna-' . esc_attr( (string) $id ) . '" name="' . esc_attr( ApplicationActionsPanel::FIELD_INTERNAL_NOTE ) . '" rows="2" aria-describedby="prc-nota-interna-ayuda-' . esc_attr( (string) $id ) . '">'
			. esc_textarea( (string) ( $fila['internal_note'] ?? '' ) ) . '</textarea>'
			. '<p class="prc-campo__ayuda" id="prc-nota-interna-ayuda-' . esc_attr( (string) $id ) . '">Solo la ven quienes revisan: ni el centro, ni quien consulta, ni ningún correo. No sale en la exportación salvo que se elija su columna.</p>'
			. '</div>';
	}

	/**
	 * Under the review of one application: its extension, «Retirar» and its history.
	 *
	 * @param array<string, mixed> $m    Model of the workshop.
	 * @param array<string, mixed> $fila Row of the table.
	 * @return string
	 */
	public static function tools( array $m, array $fila ): string {
		$id = (int) $fila['id'];
		$a  = (array) ( $m['actions']['rows'][ $id ] ?? array() );
		if ( array() === $a ) {
			return '';
		}
		$quien = '' !== (string) ( $fila['centre'] ?? '' ) ? (string) $fila['centre'] : (string) ( $fila['applicant'] ?? '' );
		$out   = ! empty( $a['pending'] ) ? '<p class="prc-campo__ayuda">Presentada en nombre del centro: todavía no la ha completado.</p>' : '';
		if ( ! empty( $a['can_extend'] ) ) {
			$out .= self::extend_form( $m, $id, $a ) . self::retire_form( $m, $id, $quien );
		}
		return $out . self::history( (array) $a['history'], $id );
	}

	/**
	 * The extension form of one application.
	 *
	 * @param array<string, mixed> $m  Model of the workshop.
	 * @param int                  $id Application ID.
	 * @param array<string, mixed> $a  Its actions row.
	 * @return string
	 */
	private static function extend_form( array $m, int $id, array $a ): string {
		$op    = ApplicationActionsPanel::OP_EXTEND;
		$hasta = (string) $a['late_until'];
		ob_start();
		?>
		<form class="prc-form prc-form--taller prc-prorroga" method="post" action="">
			<?php echo self::hidden( $m, $op, $id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			<p class="prc-campo__ayuda">
				<?php
				echo esc_html(
					'' === $hasta
						? 'Prórroga: deja a este centro editar su solicitud entera hasta el día que ponga, incluido, aunque el plazo general haya cerrado. No afecta a las demás.'
						: ( ! empty( $a['late_open'] ) ? 'Tiene prórroga hasta el ' . (string) $a['late_label'] . ', incluido.' : 'Su prórroga venció el ' . (string) $a['late_label'] . '.' )
				);
				?>
			</p>
			<?php foreach ( self::extend_warnings( $m, $a ) as $aviso ) : ?>
				<p class="prc-campo__ayuda prc-aviso-prorroga"><?php echo esc_html( $aviso ); ?></p>
			<?php endforeach; ?>
			<div class="prc-campo">
				<label class="prc-campo__etiqueta" for="prc-prorroga-<?php echo esc_attr( (string) $id ); ?>">Puede editarla hasta el</label>
				<input class="prc-control" type="date" id="prc-prorroga-<?php echo esc_attr( (string) $id ); ?>" name="<?php echo esc_attr( ApplicationActionsPanel::FIELD_LATE ); ?>" value="<?php echo esc_attr( $hasta ); ?>" min="<?php echo esc_attr( (string) ( $m['actions']['today'] ?? '' ) ); ?>" required />
			</div>
			<p class="prc-acciones">
				<button class="<?php echo esc_attr( Assets::button_class() ); ?>" type="submit">Guardar la prórroga</button>
				<?php if ( '' !== $hasta ) : ?>
					<button class="<?php echo esc_attr( Assets::button_class() ); ?>" type="submit" name="<?php echo esc_attr( ApplicationActionsPanel::FIELD_LATE_REMOVE ); ?>" value="1" formnovalidate>Quitar la prórroga</button>
				<?php endif; ?>
			</p>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * What whoever gives an extension has to know before giving it.
	 *
	 * Guardar con prórroga devuelve la solicitud a «Presentada» (ADR-0020), y
	 * en un procedimiento resuelto es una decisión expresa (ADR-0044).
	 *
	 * @param array<string, mixed> $m Model of the workshop.
	 * @param array<string, mixed> $a Actions row of the application.
	 * @return string[]
	 */
	private static function extend_warnings( array $m, array $a ): array {
		$out = array();
		if ( in_array( (string) ( $a['review'] ?? '' ), array( ApplicationMetaKeys::REVIEW_ADMITTED, ApplicationMetaKeys::REVIEW_EXCLUDED ), true ) ) {
			$out[] = 'Esta solicitud ya está revisada: si el centro la modifica con la prórroga, volverá a «Presentada» y habrá que revisarla de nuevo.';
		}
		if ( ! empty( $m['actions']['resolved'] ) ) {
			$out[] = 'Este procedimiento ya está resuelto: una prórroga deja cambiar una solicitud después de la resolución.';
		}
		return $out;
	}

	/**
	 * «Retirar esta solicitud», with its reason and the confirmation.
	 *
	 * @param array<string, mixed> $m     Model of the workshop.
	 * @param int                  $id    Application ID.
	 * @param string               $quien Who applied, to name it in the question.
	 * @return string
	 */
	private static function retire_form( array $m, int $id, string $quien ): string {
		$op       = ApplicationActionsPanel::OP_RETIRE;
		$pregunta = '¿Retirar la solicitud de ' . $quien . '? Pasará a «Retiradas»: dejará de contar, de salir en la consulta y en las exportaciones. Se puede restaurar mientras el centro no presente otra.';
		ob_start();
		?>
		<details class="prc-retirar">
			<summary class="prc-revision__abrir">Retirar esta solicitud</summary>
			<form class="prc-form prc-form--taller" method="post" action=""
				data-prc-confirm="<?php echo esc_attr( $pregunta ); ?>" data-prc-confirm-ok="Retirar la solicitud" data-prc-once>
				<?php echo self::hidden( $m, $op, $id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				<div class="prc-campo">
					<label class="prc-campo__etiqueta" for="prc-motivo-<?php echo esc_attr( (string) $id ); ?>">Motivo</label>
					<textarea class="prc-control" id="prc-motivo-<?php echo esc_attr( (string) $id ); ?>" name="<?php echo esc_attr( ApplicationActionsPanel::FIELD_REASON ); ?>" rows="2" required></textarea>
					<p class="prc-campo__ayuda">Obligatorio: queda en el historial de la solicitud. Úselo para lo que se presentó por error; para decir que no se admite, está «Excluida».</p>
				</div>
				<p class="prc-acciones"><button class="<?php echo esc_attr( Assets::button_class( 'danger' ) ); ?>" type="submit">Retirar la solicitud</button></p>
			</form>
		</details>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The hidden fields of an operation on one application, with the filter of the table.
	 *
	 * @param array<string, mixed> $m  Model of the workshop.
	 * @param string               $op Operation.
	 * @param int                  $id Application ID.
	 * @return string
	 */
	private static function hidden( array $m, string $op, int $id ): string {
		return wp_nonce_field( ProcedureEditor::nonce_action( $op, $id ), ProcedureEditor::nonce_name( $op, $id ), false, false )
			. '<input type="hidden" name="' . esc_attr( ProcedureEditor::FIELD_DO ) . '" value="' . esc_attr( $op ) . '" />'
			. '<input type="hidden" name="' . esc_attr( ProcedureEditor::FIELD_PROCEDURE ) . '" value="' . esc_attr( (string) (int) $m['procedure_id'] ) . '" />'
			. '<input type="hidden" name="' . esc_attr( ProcedureEditor::FIELD_ROW ) . '" value="' . esc_attr( (string) $id ) . '" />'
			. ApplicationsPanelView::carry( (array) ( $m['table']['args'] ?? array() ) );
	}

	/**
	 * The history of one application: a link that reads it, or its entries.
	 *
	 * @param array<string, mixed> $h  `open`, `url` and `entries`.
	 * @param int                  $id Application ID.
	 * @return string
	 */
	public static function history( array $h, int $id ): string {
		if ( empty( $h['open'] ) ) {
			return '<p class="prc-historial-enlace"><a href="' . esc_url( (string) ( $h['url'] ?? '' ) ) . '">Ver el historial</a></p>';
		}
		return self::entries( (array) ( $h['entries'] ?? array() ), $id );
	}

	/**
	 * The entries of a history, newest first.
	 *
	 * @param array<int, array<string, string>> $entries What ApplicationHistory::entries() returned.
	 * @param int                               $id      Application ID.
	 * @return string
	 */
	private static function entries( array $entries, int $id ): string {
		$out = '<details class="prc-historial-caja" id="prc-historial-' . esc_attr( (string) $id ) . '" open><summary class="prc-revision__abrir">Historial</summary>';
		if ( array() === $entries ) {
			return $out . '<p class="prc-vacio">Esta solicitud todavía no tiene historial.</p></details>';
		}
		$out .= '<ol class="prc-historial">';
		foreach ( $entries as $entrada ) {
			$out .= '<li>' . esc_html( implode( ' · ', array_filter( array( $entrada['when'], $entrada['who'], $entrada['what'] ), 'strlen' ) ) );
			$out .= '' !== $entrada['before'] ? '<br />' . esc_html( 'Antes: ' . $entrada['before'] ) : '';
			$out .= '' !== $entrada['after'] ? '<br />' . esc_html( 'Después: ' . $entrada['after'] ) : '';
			$out .= '</li>';
		}
		return $out . '</ol></details>';
	}
}
