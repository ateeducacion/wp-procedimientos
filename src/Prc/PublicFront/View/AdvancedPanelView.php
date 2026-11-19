<?php
/**
 * The «Avanzado» tab of the workshop, painted from what ProcedureEditor::model() decided.
 *
 * @package Prc
 */

namespace Prc\PublicFront\View;

use Prc\Domain\CentreEligibility;
use Prc\Domain\ProcedureState;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PublicFront\Assets;
use Prc\PublicFront\ProcedureEditor;
use Prc\PublicFront\Shell;

/**
 * Solo pinta: no lee la petición, no consulta, no decide.
 *
 * Lo que rara vez se toca, en secciones con su ancla y su formulario: qué
 * centros pueden solicitar, «Acceso de consulta» y «Código» (#80). Cada una
 * guarda por su cuenta, con su operación de siempre.
 */
final class AdvancedPanelView {

	/**
	 * Paint the sections this person sees, in the order of the model.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	public static function html( array $m ): string {
		$secciones = array(
			'centres' => array( self::class, 'centres_section' ),
			'observe' => array( self::class, 'observe_section' ),
			'code'    => array( self::class, 'code_section' ),
		);
		$out       = '';
		foreach ( (array) $m['advanced'] as $seccion ) {
			$out .= call_user_func( $secciones[ $seccion ], $m );
		}
		return $out;
	}

	/**
	 * «Qué centros pueden solicitar», with its own form.
	 *
	 * Es del grupo de «Datos»: cerrado por el estado, se pinta apagado y dice
	 * por qué, como cuando iba allí. El aviso del catálogo vacío, a la vista.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function centres_section( array $m ): string {
		$op     = ProcedureEditor::OP_CENTRES;
		$apagar = true !== ( ( (array) $m['groups'] )[ ProcedureState::GROUP_DATA ] ?? false );
		$motivo = $apagar && '' !== ProcedureState::why_locked( (string) $m['state'] ) ? 'Cerrado por el estado del procedimiento.' : '';
		ob_start();
		?>
		<section class="prc-seccion-campos" id="<?php echo esc_attr( ProcedureEditor::SECTION_CENTRES ); ?>" aria-labelledby="prc-avanzado-centros-titulo">
			<h2 class="prc-seccion__titulo" id="prc-avanzado-centros-titulo">Qué centros pueden solicitar</h2>
			<?php if ( '' !== $motivo ) : ?>
				<p class="prc-seccion__bloqueo"><?php echo esc_html( $motivo ); ?></p>
			<?php endif; ?>
			<?php echo ProcedureEditorView::centre_warning( $m, 'catalogue' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			<form class="prc-form prc-form--taller" method="post" action="<?php echo esc_url( '#' . Shell::NOTICE_ANCHOR ); ?>" data-prc-dirty-guard>
				<?php wp_nonce_field( ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op ), false ); ?>
				<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
				<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
				<fieldset class="prc-seccion-campos"<?php echo $apagar ? ' disabled' : ''; ?>>
					<?php echo self::centre_fields( $m, (array) $m['values'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
					<?php echo PanelParts::submit( 'Guardar qué centros pueden solicitar' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				</fieldset>
			</form>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * «Acceso de consulta», only for administración.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function observe_section( array $m ): string {
		return '<section class="prc-seccion-campos" id="' . esc_attr( ProcedureEditor::SECTION_OBSERVE ) . '" aria-labelledby="prc-avanzado-consulta-titulo">'
			. '<h2 class="prc-seccion__titulo" id="prc-avanzado-consulta-titulo">Acceso de consulta</h2>'
			. ObserveView::settings( $m )
			. '</section>';
	}

	/**
	 * The fields of «Qué centros pueden solicitar»: types, islands and a closed list of codes.
	 *
	 * Tipos e islas llevan cada uno su interruptor; apagado, sus casillas se
	 * esconden —con CSS, sin guion— y salen todas marcadas, para que al
	 * encenderlo se desmarque lo que sobre (CentreEligibility::input(), #84).
	 * Las casillas son los tipos y las islas que trae el catálogo, más los ya
	 * elegidos aunque el catálogo los haya perdido. Ya en «Avanzado», sin
	 * plegar (#80).
	 *
	 * @param array<string, mixed> $m Model.
	 * @param array<string, mixed> $v Field values.
	 * @return string
	 */
	private static function centre_fields( array $m, array $v ): string {
		$options = (array) ( $m['centre_options'] ?? array() );
		$lists   = array(
			ProcedureMetaKeys::CENTRE_TYPES => array( 'Tipos de centro', (array) ( $options['types'] ?? array() ), ProcedureEditor::FIELD_LIMIT_TYPES, 'Limitar por tipo de centro', 'Marque los tipos que pueden solicitar.' ),
			ProcedureMetaKeys::ISLANDS      => array( 'Islas', (array) ( $options['islands'] ?? array() ), ProcedureEditor::FIELD_LIMIT_ISLANDS, 'Limitar por isla', 'Marque las islas que pueden solicitar.' ),
		);

		$chosen = array();
		foreach ( array_keys( $lists ) as $name ) {
			$chosen[ $name ] = array_map( array( CentreEligibility::class, 'fold' ), array_map( 'strval', (array) $v[ $name ] ) );
		}

		ob_start();
		?>
		<div class="prc-seccion__cuerpo">
			<div class="prc-campo prc-campo--completo">
				<p class="prc-campo__ayuda">Sin limitar por tipo ni por isla y con la lista en blanco, puede solicitar cualquier centro, también uno que todavía no esté en el catálogo. Con tipos o islas, solo los centros del catálogo que los cumplan; con la lista cerrada sola, los de la lista, estén o no en el catálogo. Los límites se miran al presentar una solicitud nueva: un centro que ya la presentó la sigue editando o subsanando cuando el plazo lo permita, aunque el procedimiento se acote después.</p>
			</div>
			<?php foreach ( $lists as $name => $list ) : ?>
				<?php $limited = ! empty( $v[ $list[2] ] ); ?>
				<div class="prc-campo prc-campo--medio">
					<fieldset class="prc-acotar__lista">
						<legend class="prc-campo__etiqueta"><?php echo esc_html( $list[0] ); ?></legend>
						<?php if ( array() === $list[1] ) : ?>
							<p class="prc-campo__ayuda">El catálogo de centros no trae ninguno.</p>
						<?php else : ?>
							<div class="prc-opcion prc-acotar__interruptor">
								<input type="checkbox" id="<?php echo esc_attr( $list[2] ); ?>" name="<?php echo esc_attr( $list[2] ); ?>" value="1"
									<?php checked( $limited ); ?> />
								<label for="<?php echo esc_attr( $list[2] ); ?>"><?php echo esc_html( $list[3] ); ?></label>
							</div>
							<div class="prc-acotar__opciones" data-prc-tick-group>
								<p class="prc-campo__ayuda"><?php echo esc_html( $list[4] ); ?></p>
								<p class="prc-acotar__marcar" hidden>
									<button type="button" class="<?php echo esc_attr( Assets::button_class( false ) ); ?>" data-prc-tick-all="1">Marcar todos</button>
									<button type="button" class="<?php echo esc_attr( Assets::button_class( false ) ); ?>" data-prc-tick-all="0">Ninguno</button>
								</p>
								<div class="prc-opciones prc-opciones--vertical">
									<?php foreach ( $list[1] as $i => $value ) : ?>
										<div class="prc-opcion">
											<input type="checkbox" id="<?php echo esc_attr( $name . '-' . $i ); ?>"
												name="<?php echo esc_attr( $name ); ?>[]" value="<?php echo esc_attr( (string) $value ); ?>"
												<?php checked( ! $limited || in_array( CentreEligibility::fold( (string) $value ), $chosen[ $name ], true ) ); ?> />
											<label for="<?php echo esc_attr( $name . '-' . $i ); ?>"><?php echo esc_html( (string) $value ); ?></label>
										</div>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endif; ?>
					</fieldset>
				</div>
			<?php endforeach; ?>
			<div class="prc-campo prc-campo--completo">
				<label class="prc-campo__etiqueta" for="prc-allowed-centres">Lista cerrada de centros</label>
				<textarea class="prc-control" id="prc-allowed-centres" name="<?php echo esc_attr( ProcedureMetaKeys::ALLOWED_CENTRES ); ?>" rows="4"
					aria-describedby="prc-allowed-centres-ayuda"><?php echo esc_textarea( (string) $v[ ProcedureMetaKeys::ALLOWED_CENTRES ] ); ?></textarea>
				<p class="prc-campo__ayuda" id="prc-allowed-centres-ayuda">Códigos de centro, uno por línea o separados por comas, hasta <?php echo esc_html( (string) CentreEligibility::MAX_CODES ); ?>. En blanco, sin lista.</p>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * «Código»: CSS for managers and executable JavaScript for trusted administrators.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function code_section( array $m ): string {
		$op = ProcedureEditor::OP_CUSTOM_CODE;
		ob_start();
		?>
		<section class="prc-seccion-campos" id="<?php echo esc_attr( ProcedureEditor::SECTION_CODE ); ?>" aria-labelledby="prc-avanzado-codigo-titulo">
			<h2 class="prc-seccion__titulo" id="prc-avanzado-codigo-titulo">Código adicional</h2>
			<p class="prc-intro">El CSS se aplica solo a la ficha pública de este procedimiento.</p>
			<?php if ( true === ( $m['observe_status']['observable'] ?? false ) ) : ?>
				<div class="prc-aviso prc-aviso-info">
					<p>La consulta por zona está activa en este procedimiento. La configura quien administra el aplicativo.</p>
					<?php if ( true === ( $m['observe_status']['gone'] ?? false ) ) : ?>
						<p>Pero las columnas que se eligieron ya no existen, así que ahora no enseña ninguna. Avise a quien administra el aplicativo.</p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
			<form class="prc-form prc-form--taller" method="post" action="<?php echo esc_url( '#' . Shell::NOTICE_ANCHOR ); ?>" data-prc-dirty-guard>
				<?php wp_nonce_field( ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op ), false ); ?>
				<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
				<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
				<?php echo self::code_editor_field( 'prc-custom-css', 'prc_custom_css', 'CSS', (string) ( $m['values'][ ProcedureMetaKeys::CUSTOM_CSS ] ?? '' ), empty( $m['can_edit'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				<?php if ( ! empty( $m['can_edit_custom_js'] ) ) : ?>
					<div class="prc-aviso prc-aviso-warning prc-codigo-admin">
						<h3 id="prc-codigo-admin-titulo">Solo para administración</h3>
						<p>Este JavaScript se ejecuta en la ficha pública del procedimiento. Revíselo antes de guardarlo.</p>
					</div>
					<?php echo self::code_editor_field( 'prc-custom-js', 'prc_custom_js', 'JavaScript', (string) ( $m['values'][ ProcedureMetaKeys::CUSTOM_JS ] ?? '' ), false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				<?php endif; ?>
				<button class="<?php echo esc_attr( Assets::button_class( true ) . ' prc-envio' ); ?>" type="submit"<?php disabled( empty( $m['can_edit'] ) ); ?>>Guardar el código</button>
			</form>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Render a labelled textarea that WordPress may enhance with CodeMirror.
	 *
	 * @param string $id       Field ID.
	 * @param string $name     Field name.
	 * @param string $label    Visible label.
	 * @param string $value    Saved value.
	 * @param bool   $disabled Whether editing is unavailable.
	 * @return string
	 */
	private static function code_editor_field( string $id, string $name, string $label, string $value, bool $disabled ): string {
		ob_start();
		?>
		<div class="prc-code">
			<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
			<textarea
				class="prc-control"
				id="<?php echo esc_attr( $id ); ?>"
				name="<?php echo esc_attr( $name ); ?>"
				rows="14"
				spellcheck="false"
				autocapitalize="off"
				autocomplete="off"
				autocorrect="off"
				<?php disabled( $disabled ); ?>
				><?php echo esc_textarea( $value ); ?></textarea>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
