<?php
/**
 * The export dialog of the «Solicitudes» panel.
 *
 * @package Prc
 */

namespace Prc\PublicFront\View;

use Prc\PublicFront\ApplicationExport;
use Prc\PublicFront\ApplicationsPanel;
use Prc\PublicFront\Assets;
use Prc\PublicFront\CentreColumns;
use Prc\PublicFront\ProcedureEditor;
use Prc\PublicFront\Shell;

/**
 * Solo pinta el diálogo de exportar (ADR-0039): no lee la petición ni decide.
 *
 * Va aparte de {@see ApplicationsPanelView} para que ninguna de las dos pase
 * del umbral de PHPMD.
 */
final class ExportDialogView {

	/**
	 * The export dialog: a POST with its nonce, never a link.
	 *
	 * Sin guion es un desplegable con el formulario dentro, y funciona igual:
	 * «Todas las columnas» es un campo que el servidor lee, no un atajo del
	 * guion. Con guion, `prc-app.js` lo abre como diálogo modal, marca y
	 * desmarca las casillas con «Todas» y enseña solo lo del formato elegido
	 * (ADR-0039).
	 *
	 * Con un filtro en la tabla, el diálogo dice cuántas salen y deja elegir
	 * entre las del filtro —lo marcado— y todas; el filtro viaja en campos
	 * ocultos, y los recuentos de quién se queda fuera son de las del filtro.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	public static function html( array $m ): string {
		$op       = ProcedureEditor::OP_EXPORT;
		$export   = (array) ( $m['export'] ?? array() );
		$eleccion = (array) ( $export['choice'] ?? array() );
		$columnas = (array) ( $eleccion['columns'] ?? array() );
		$quienes  = (array) ( $eleccion['who'] ?? array() );
		$formato  = (string) ( $eleccion['format'] ?? ApplicationExport::FORMAT_CSV );
		$forma    = (string) ( $eleccion['shape'] ?? ApplicationExport::SHAPE_APPLICATION );

		$radios_formato = self::radios(
			ProcedureEditor::FIELD_EXPORT_FORMAT,
			array(
				ApplicationExport::FORMAT_CSV    => 'Hoja de cálculo (CSV)',
				ApplicationExport::FORMAT_MOODLE => 'Fichero de alta de usuarios de Moodle',
			),
			$formato,
			'data-prc-export-format'
		);
		$radios_forma   = self::radios(
			ProcedureEditor::FIELD_EXPORT_SHAPE,
			array(
				ApplicationExport::SHAPE_APPLICATION => 'Solicitud',
				ApplicationExport::SHAPE_PERSON      => 'Persona coordinadora: la solicitud se repite en cada una',
			),
			$forma
		);

		ob_start();
		?>
		<details class="prc-exportar" data-prc-export>
			<summary class="<?php echo esc_attr( Assets::button_class() ); ?> prc-exportar__abrir">Exportar…</summary>
			<form class="prc-form prc-form--taller prc-exportar__form" method="post" action="<?php echo esc_url( '#' . Shell::NOTICE_ANCHOR ); ?>" aria-labelledby="prc-exportar-titulo">
				<h2 class="prc-exportar__titulo" id="prc-exportar-titulo">Exportar las solicitudes</h2>
				<?php wp_nonce_field( ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op ), false ); ?>
				<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
				<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
				<?php echo self::scope( $export ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>

				<fieldset class="prc-exportar__grupo">
					<legend>Formato</legend>
					<?php echo $radios_formato; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				</fieldset>

				<div class="prc-exportar__formato" data-prc-export-for="<?php echo esc_attr( ApplicationExport::FORMAT_CSV ); ?>">
					<fieldset class="prc-exportar__grupo">
						<legend>Una fila por</legend>
						<?php echo $radios_forma; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
					</fieldset>
					<fieldset class="prc-exportar__grupo">
						<legend>Columnas</legend>
						<label class="prc-casilla">
							<input type="checkbox" name="<?php echo esc_attr( ProcedureEditor::FIELD_EXPORT_ALL ); ?>" value="1" data-prc-export-all <?php checked( array() === $columnas ); ?> />
							Todas las columnas
						</label>
						<p class="prc-campo__ayuda">Marcada, se exportan todas las de siempre y no se miran las de abajo, salvo las del catálogo del centro (isla, municipio, tipo, titularidad y <?php echo esc_html( mb_strtolower( CentreColumns::zone_label() ) ); ?>), que solo salen si se marcan.</p>
						<?php foreach ( (array) ( $export['groups'] ?? array() ) as $grupo => $de_grupo ) : ?>
							<?php
							if ( array() === $de_grupo ) {
								continue;
							}
							?>
							<fieldset class="prc-exportar__columnas">
								<legend><?php echo esc_html( (string) ( $export['group_labels'][ $grupo ] ?? $grupo ) ); ?></legend>
								<?php foreach ( (array) $de_grupo as $clave => $rotulo ) : ?>
									<?php
									// Las del catálogo del centro y la nota interna no son de
									// «Todas»: el guion no las marca con ella, y sin elegirlas no
									// salen (ADR-0042; PLAN-0003, PR 9).
									$extra = in_array( (string) $clave, CentreColumns::extras(), true );
									?>
									<label class="prc-casilla">
										<input type="checkbox" name="<?php echo esc_attr( ProcedureEditor::FIELD_EXPORT_COLUMNS ); ?>[]" value="<?php echo esc_attr( (string) $clave ); ?>" <?php echo $extra ? 'data-prc-export-extra' : 'data-prc-export-col'; ?> <?php checked( ( ! $extra && array() === $columnas ) || in_array( (string) $clave, $columnas, true ) ); ?> />
										<?php echo esc_html( (string) $rotulo ); ?>
									</label>
								<?php endforeach; ?>
							</fieldset>
						<?php endforeach; ?>
					</fieldset>
				</div>

				<fieldset class="prc-exportar__grupo prc-exportar__formato" data-prc-export-for="<?php echo esc_attr( ApplicationExport::FORMAT_MOODLE ); ?>">
					<legend>Quiénes salen en el fichero de usuarios</legend>
					<?php foreach ( (array) ( $export['who'] ?? array() ) as $clave => $quien ) : ?>
						<label class="prc-casilla">
							<input type="checkbox" name="<?php echo esc_attr( ProcedureEditor::FIELD_EXPORT_WHO ); ?>[]" value="<?php echo esc_attr( (string) $clave ); ?>" <?php checked( array() === $quienes || in_array( (string) $clave, $quienes, true ) ); ?> />
							<?php echo self::who_counts( (array) $quien, ! empty( $export['filtered'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
						</label>
					<?php endforeach; ?>
					<p class="prc-campo__ayuda">Columnas <code><?php echo esc_html( ApplicationExport::MOODLE_HEADER ); ?></code>, una fila por persona y sin repetir usuario. No salen: quien no tiene un correo válido; las incompletas, sin nombre, apellidos o usuario; y quien comparte usuario con otra persona de distinto correo, que se queda con la primera.</p>
				</fieldset>

				<div class="prc-acciones">
					<button class="<?php echo esc_attr( Assets::button_class( true ) ); ?>" type="submit">Descargar el fichero</button>
				</div>
			</form>
		</details>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Which applications go out: with a filter, how many and a choice.
	 *
	 * Sin filtro no hay nada que elegir: salen todas, como siempre.
	 *
	 * @param array<string, mixed> $export Export model.
	 * @return string
	 */
	private static function scope( array $export ): string {
		if ( empty( $export['filtered'] ) ) {
			return '';
		}
		$cuenta = (int) ( $export['count'] ?? 0 );
		$todas  = (int) ( $export['all'] ?? 0 );
		return ApplicationsPanelView::carry( (array) ( $export['args'] ?? array() ) )
			. '<fieldset class="prc-exportar__grupo prc-exportar__cuales">'
			. '<legend>Qué solicitudes</legend>'
			. '<p class="prc-campo__ayuda">' . esc_html( sprintf( 1 === $cuenta ? 'Se exportará %d solicitud (filtro activo).' : 'Se exportarán %d solicitudes (filtro activo).', $cuenta ) ) . ' Los recuentos de quién sale en el fichero de usuarios se dan para las dos opciones.</p>'
			. self::radios(
				ApplicationsPanel::FIELD_EXPORT_SCOPE,
				array(
					ApplicationsPanel::SCOPE_FILTER => sprintf( 'Las del filtro (%d)', $cuenta ),
					ApplicationsPanel::SCOPE_ALL    => sprintf( 'Todas (%d)', $todas ),
				),
				ApplicationsPanel::SCOPE_FILTER,
				'data-prc-export-scope'
			)
			. '</fieldset>';
	}

	/**
	 * One closed choice of the export dialog, as radio buttons.
	 *
	 * @param string                $name     Field name.
	 * @param array<string, string> $options  value => label.
	 * @param string                $selected Chosen value.
	 * @param string                $marca    Extra data attribute, or empty.
	 * @return string
	 */
	private static function radios( string $name, array $options, string $selected, string $marca = '' ): string {
		$out = '';
		foreach ( $options as $valor => $rotulo ) {
			$out .= sprintf(
				'<label class="prc-casilla"><input type="radio" name="%1$s" value="%2$s"%3$s%4$s /> %5$s</label>',
				esc_attr( $name ),
				esc_attr( (string) $valor ),
				'' !== $marca ? ' ' . esc_attr( $marca ) : '',
				checked( (string) $valor, $selected, false ),
				esc_html( $rotulo )
			);
		}
		return $out;
	}

	/**
	 * One source of the user upload file with its counts: those of the filter
	 * and those of all when there is a filter, each with its own label.
	 *
	 * Sin guion se leen las dos; con guion, `prc-app.js` esconde la que no
	 * corresponde a la opción elegida en «Qué solicitudes».
	 *
	 * @param array<string, mixed> $quien    One source: `label`, its counts over all and, with a filter, `filter`.
	 * @param bool                 $filtered Whether there is a filter.
	 * @return string
	 */
	private static function who_counts( array $quien, bool $filtered ): string {
		if ( ! $filtered ) {
			return esc_html( self::who_label( $quien ) );
		}
		$out = esc_html( (string) ( $quien['label'] ?? '' ) );
		foreach ( array(
			ApplicationsPanel::SCOPE_FILTER => array( 'las del filtro', (array) ( $quien['filter'] ?? array() ) ),
			ApplicationsPanel::SCOPE_ALL    => array( 'todas', $quien ),
		) as $ambito => list( $rotulo, $cuenta ) ) {
			$out .= ' <span data-prc-export-scope-count="' . esc_attr( $ambito ) . '">'
				. esc_html( self::who_label( array( 'label' => $rotulo . ':' ) + $cuenta ) ) . '</span>';
		}
		return $out;
	}

	/**
	 * Who goes in the user upload file, with how many and who is left out, and why.
	 *
	 * Cada motivo con su rótulo: sin correo válido, incompletas (sin nombre,
	 * apellidos o usuario), usuario en conflicto (el mismo usuario con otro
	 * correo) y usuario no válido para la plataforma. Una persona repetida con
	 * el mismo correo no es una omisión.
	 *
	 * @param array<string, mixed> $quien One source: `label`, `count`, `email`, `incomplete`, `conflict`, `invalid`.
	 * @return string
	 */
	private static function who_label( array $quien ): string {
		$cuenta  = (int) ( $quien['count'] ?? 0 );
		$motivos = array(
			'email'      => array( '%d sin correo válido', '%d sin correo válido' ),
			'incomplete' => array( '%d incompleta', '%d incompletas' ),
			'conflict'   => array( '%d con el usuario en conflicto', '%d con el usuario en conflicto' ),
			'invalid'    => array( '%d con un usuario no válido para la plataforma', '%d con un usuario no válido para la plataforma' ),
		);
		$fuera   = array();
		foreach ( $motivos as $clave => list( $una, $varias ) ) {
			$n = (int) ( $quien[ $clave ] ?? 0 );
			if ( $n > 0 ) {
				$fuera[] = sprintf( 1 === $n ? $una : $varias, $n );
			}
		}
		$texto = sprintf( 1 === $cuenta ? '%d persona' : '%d personas', $cuenta );
		if ( array() !== $fuera ) {
			$texto .= '. No saldrán: ' . implode( ', ', $fuera );
		}
		$rotulo = (string) ( $quien['label'] ?? '' );
		// «Coordinación (3 personas)» o, por opción, «(todas: 3 personas)».
		return ':' === substr( $rotulo, -1 ) ? '(' . $rotulo . ' ' . $texto . ')' : $rotulo . ' (' . $texto . ')';
	}
}
