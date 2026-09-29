<?php
/**
 * The procedure workshop, painted from what ProcedureEditor::model() decided.
 *
 * @package Prc
 */

namespace Prc\PublicFront\View;

use Prc\Domain\ProcedureQuestions;
use Prc\Domain\ProcedureState;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PublicFront\ApplicationFiles;
use Prc\PublicFront\Applications;
use Prc\PublicFront\Assets;
use Prc\PublicFront\ProcedureEditor;
use Prc\PublicFront\Shell;

/**
 * Solo pinta: no lee la petición, no consulta, no decide.
 *
 * El marco del taller —la cabecera del procedimiento, las pestañas y el
 * aviso de lo último que se hizo— y los cinco paneles: Datos, Preguntas,
 * Enlaces, Solicitudes y Publicación.
 */
final class ProcedureEditorView {

	/**
	 * Paint the workshop.
	 *
	 * @param array<string, mixed> $m What ProcedureEditor::model() returned.
	 * @return string
	 */
	public static function html( array $m ): string {
		if ( '' !== (string) $m['aviso'] ) {
			return Shell::render(
				'Taller del procedimiento',
				'',
				Shell::notice( (string) $m['aviso_tipo'], (string) $m['aviso'] ) . self::back_link( $m )
			);
		}
		if ( true === $m['nuevo'] ) {
			return self::new_procedure( $m );
		}

		$panel = (string) $m['panel'];

		ob_start();
		echo self::head( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		echo self::tabs( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		echo self::flash( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		echo self::archived_notice( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		// Por qué media pantalla está apagada: lo dice el dominio, no la vista.
		echo Shell::notice( 'aviso', (string) $m['state_notice'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.

		// El interruptor de histórico va fuera de lo que se apaga: si estuviera
		// dentro del panel bloqueado, marcar sería un viaje sin vuelta.
		if ( ProcedureEditor::PANEL_PUBLISH === $panel ) {
			echo self::archive_switch( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		}

		// Solo lectura: un `fieldset` desactivado apaga de una vez todos los
		// controles de dentro. Los enlaces siguen funcionando: consultar y
		// exportar no se cierran.
		$cerrado = self::read_only( $m, $panel );
		if ( $cerrado ) {
			echo '<fieldset class="prc-solo-lectura" disabled><legend class="screen-reader-text">Procedimiento en solo lectura</legend>';
		}

		if ( ProcedureEditor::PANEL_QUESTIONS === $panel ) {
			echo self::questions_panel( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		} elseif ( ProcedureEditor::PANEL_LINKS === $panel ) {
			echo self::links_panel( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		} elseif ( ProcedureEditor::PANEL_APPLICATIONS === $panel ) {
			echo self::applications_panel( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		} elseif ( ProcedureEditor::PANEL_PUBLISH === $panel ) {
			echo self::publish_panel( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		} else {
			echo self::data_panel( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		}

		if ( $cerrado ) {
			echo '</fieldset>';
		}
		return Shell::render( '', '', (string) ob_get_clean() );
	}

	/**
	 * Whether this panel opens read-only: por permiso, por bloqueo o por el
	 * estado del procedimiento.
	 *
	 * El panel de datos escribe dos grupos y basta con que quede uno para que
	 * siga habiendo algo que guardar; los demás paneles son de un solo grupo,
	 * así que cerrado el grupo, cerrado el panel.
	 *
	 * @param array<string, mixed> $m     Model.
	 * @param string               $panel Panel slug.
	 * @return bool
	 */
	private static function read_only( array $m, string $panel ): bool {
		if ( ProcedureEditor::PANEL_APPLICATIONS === $panel ) {
			return true !== $m['can_review'];
		}
		if ( ProcedureEditor::PANEL_QUESTIONS === $panel ) {
			return ! self::open_group( $m, ProcedureState::GROUP_QUESTIONS );
		}
		if ( ProcedureEditor::PANEL_LINKS === $panel ) {
			return ! self::open_group( $m, ProcedureState::GROUP_LINKS );
		}
		if ( ProcedureEditor::PANEL_DATA === $panel ) {
			return ! self::open_group( $m, ProcedureState::GROUP_DATA ) && ! self::open_group( $m, ProcedureState::GROUP_DATES );
		}
		return true !== $m['can_edit'];
	}

	/**
	 * Whether the model leaves that group of data open.
	 *
	 * @param array<string, mixed> $m     Model.
	 * @param string               $group One of {@see ProcedureState::groups()}.
	 * @return bool
	 */
	private static function open_group( array $m, string $group ): bool {
		return true === ( ( (array) $m['groups'] )[ $group ] ?? false );
	}

	/**
	 * The «create a procedure» screen: the data panel and nothing else.
	 *
	 * El alta es la misma pantalla de «Datos» con lo mínimo: el título, el
	 * curso, a quién se dirige, desde dónde se convoca, a quién se escribe y
	 * de qué color sale la cabecera. Ni identificador interno —es el `ID` del
	 * post, y sale en la URL del taller— ni estado: nace en borrador y a
	 * partir de ahí lo derivan las fechas (ADR-0013).
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function new_procedure( array $m ): string {
		ob_start();
		?>
		<p class="prc-sub"><a href="<?php echo esc_url( (string) $m['workspace_url'] ); ?>">&larr; Mis procedimientos</a></p>
		<div class="prc-h1-fila">
			<h1 class="prc-h1">Crear un procedimiento</h1>
		</div>
		<h2 class="prc-sub"><strong>Formato Express</strong></h2>
		<p class="prc-intro">
			En este formulario <strong>solo aparecen los campos mínimos</strong> para crear el
			procedimiento. Nace en borrador y no se ve fuera hasta que lo publique. Una vez creado
			se abre su taller, y desde ahí se añaden <strong>los plazos, las preguntas y los
			enlaces</strong>.
		</p>
		<?php
		echo self::flash( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		echo self::data_panel( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		return Shell::render( '', '', (string) ob_get_clean() );
	}

	/**
	 * The notice of the last action, if any.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function flash( array $m ): string {
		$flash = (array) $m['flash'];
		return Shell::notice( (string) $flash['tipo'], (string) $flash['texto'] );
	}

	/**
	 * The name of the procedure, its state, its ámbito, and where to see it.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function head( array $m ): string {
		ob_start();
		?>
		<p class="prc-sub"><a href="<?php echo esc_url( (string) $m['workspace_url'] ); ?>">&larr; Mis procedimientos</a></p>
		<div class="prc-h1-fila">
			<h1 class="prc-h1"><?php echo esc_html( '' !== (string) $m['title'] ? (string) $m['title'] : 'Procedimiento sin título' ); ?></h1>
			<?php echo self::badge( (string) $m['state'], (string) $m['state_label'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			<?php echo self::badge( (string) $m['status'], (string) $m['status_label'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			<span class="prc-acciones">
				<?php echo PanelParts::icon_link( (string) $m['view_url'], 'ojo', 'Ver la ficha pública' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			</span>
		</div>
		<p class="prc-sub"><?php echo esc_html( 'Ámbito: ' . (string) $m['area_names'] ); ?></p>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The inner tabs, each with its count where there is something to count.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function tabs( array $m ): string {
		$activa = (string) $m['panel'];

		ob_start();
		?>
		<nav class="prc-tabs" aria-label="Paneles del procedimiento">
			<div class="prc-tabs-fila">
				<?php foreach ( (array) $m['panels'] as $clave => $panel ) : ?>
					<a class="prc-tab<?php echo $clave === $activa ? ' prc-tab-on' : ''; ?>"
						<?php echo $clave === $activa ? ' aria-current="page"' : ''; ?>
						href="<?php echo esc_url( (string) $panel['url'] ); ?>"><?php echo esc_html( (string) $panel['label'] ); ?>
						<?php if ( null !== ( $panel['count'] ?? null ) ) : ?>
							<span class="prc-tab-n badge"><?php echo esc_html( (string) (int) $panel['count'] ); ?></span>
						<?php endif; ?></a>
				<?php endforeach; ?>
			</div>
		</nav>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Why this workshop opens without a single «Guardar».
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function archived_notice( array $m ): string {
		if ( true !== $m['archived'] ) {
			return '';
		}
		return Shell::notice(
			'aviso',
			true === $m['can_edit']
				? 'Este procedimiento está marcado como histórico: su ámbito ya no puede editarlo. Usted sí, porque administra el aplicativo.'
				: 'Este procedimiento está marcado como histórico: se puede consultar y exportar, pero ya no se edita. Para volver a abrirlo, pídalo a quien administre el aplicativo.'
		);
	}

	// ─── Datos ─────────────────────────────────────────────────────────────

	/**
	 * The «Datos» panel: what the procedure is, when, and to whom.
	 *
	 * Es también la pantalla de alta: con `nuevo` solo se pintan las cuatro
	 * secciones que hacen falta para crearlo, y los plazos y lo que se le pide
	 * al centro quedan para el taller, con el procedimiento ya en su sitio.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function data_panel( array $m ): string {
		$v      = (array) $m['values'];
		$listas = (array) $m['terms'];
		$op     = ProcedureEditor::PANEL_DATA;
		$nuevo  = true === $m['nuevo'];
		// Cerrado el plazo solo quedan las fechas: lo demás se pinta apagado
		// en vez de esconderse, para que se siga leyendo lo que pone.
		$apagar = ! self::open_group( $m, ProcedureState::GROUP_DATA );
		$plazos = ! self::open_group( $m, ProcedureState::GROUP_DATES );
		// Corta a propósito: el motivo entero ya está en el aviso de arriba, y
		// aquí se repetiría una vez por sección. Lo que falta junto al bloque
		// apagado no es la explicación, sino saber que está apagado aposta.
		$motivo = '' !== ProcedureState::why_locked( (string) $m['state'] )
			? 'Cerrado por el estado del procedimiento.'
			: '';

		// Los cuatro rótulos son los literales del sistema que se sustituye: quien
		// da de alta lleva años leyéndolos y pidió esta pantalla igual que aquella.
		$secciones  = PanelParts::section( 'Ajustes generales', self::general_fields( $m, $v, $listas, $nuevo ), $apagar, $motivo );
		$secciones .= '<div class="prc-form__mitades">'
			. PanelParts::section( 'Promociona', self::area_field( $v, (array) ( $listas['area'] ?? array() ), (array) ( $m['foreign_areas'] ?? array() ) ), $apagar, $motivo )
			. PanelParts::section( 'Más información', self::email_fields( $v ), $apagar, $motivo )
			. '</div>';
		$secciones .= PanelParts::section( 'Aspectos de diseño', self::palette_field( $m, $v ), $apagar, $motivo );
		if ( ! $nuevo ) {
			$secciones .= PanelParts::section( 'Plazos', self::date_fields( $v ), $plazos, $motivo );
			$secciones .= PanelParts::section( 'La solicitud', self::request_fields( $v ), $apagar, $motivo );
		}

		ob_start();
		?>
		<form class="prc-form prc-form--taller<?php echo $nuevo ? ' prc-form--alta' : ''; ?>" method="post" action="">
			<?php wp_nonce_field( ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
			<?php echo $secciones; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			<?php echo PanelParts::submit( $nuevo ? 'Crear el procedimiento' : 'Guardar los datos' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * «Ajustes generales»: título, curso, quién solicita y a qué centros se dirige.
	 *
	 * @param array<string, mixed> $m      Model.
	 * @param array<string, mixed> $v      Field values.
	 * @param array<string, mixed> $listas Term lists.
	 * @param bool                 $nuevo  Whether this is the create screen.
	 * @return string
	 */
	private static function general_fields( array $m, array $v, array $listas, bool $nuevo ): string {
		// El desplegable se arma antes de la plantilla: dentro de ella el
		// escapado ya lo hace `term_select()`, y así el `phpcs:ignore` cabe en
		// la misma línea del `echo`.
		$sel_course = self::term_select(
			'prc-course',
			ProcedureEditor::FIELD_COURSE,
			'Curso',
			(array) ( $listas['course'] ?? array() ),
			(int) $v[ ProcedureEditor::FIELD_COURSE ],
			'En la forma 2026-2027.'
		);

		ob_start();
		?>
		<div class="prc-seccion__cuerpo">
			<div class="prc-campo prc-campo--medio">
				<label class="prc-campo__etiqueta" for="prc-title">Título del procedimiento <?php echo self::obl(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></label>
				<input class="prc-control" type="text" id="prc-title" name="<?php echo esc_attr( ProcedureEditor::FIELD_TITLE ); ?>"
					maxlength="120" required aria-describedby="prc-title-ayuda"
					value="<?php echo esc_attr( (string) $v[ ProcedureEditor::FIELD_TITLE ] ); ?>" />
				<p class="prc-campo__ayuda" id="prc-title-ayuda">Máximo 120 caracteres. El curso no se escribe aquí: se elige al lado.</p>
			</div>
			<div class="prc-campo prc-campo--cuarto">
				<?php echo $sel_course; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			</div>
			<div class="prc-campo prc-campo--cuarto">
				<label class="prc-campo__etiqueta" for="prc-audience">Quién solicita <?php echo self::obl(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></label>
				<select class="prc-control" id="prc-audience" name="<?php echo esc_attr( ProcedureMetaKeys::AUDIENCE ); ?>" required aria-describedby="prc-audience-ayuda">
					<?php foreach ( (array) $m['audiences'] as $clave => $rotulo ) : ?>
						<option value="<?php echo esc_attr( (string) $clave ); ?>" <?php selected( (string) $clave, (string) $v[ ProcedureMetaKeys::AUDIENCE ] ); ?>><?php echo esc_html( (string) $rotulo ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="prc-campo__ayuda" id="prc-audience-ayuda">Casi siempre, el centro educativo.</p>
			</div>
			<div class="prc-campo prc-campo--medio">
				<fieldset>
					<legend class="prc-campo__etiqueta">Dirigido a <?php echo self::obl(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></legend>
					<div class="prc-opciones prc-opciones--vertical">
						<?php foreach ( (array) $m['ownerships'] as $clave => $rotulo ) : ?>
							<div class="prc-opcion">
								<input type="checkbox" id="prc-ownership-<?php echo esc_attr( (string) $clave ); ?>"
									name="<?php echo esc_attr( ProcedureMetaKeys::OWNERSHIP ); ?>[]" value="<?php echo esc_attr( (string) $clave ); ?>"
									<?php checked( in_array( $clave, (array) $v[ ProcedureMetaKeys::OWNERSHIP ], true ) ); ?> />
								<label for="prc-ownership-<?php echo esc_attr( (string) $clave ); ?>"><?php echo esc_html( (string) $rotulo ); ?></label>
							</div>
						<?php endforeach; ?>
					</div>
					<p class="prc-campo__ayuda">Un centro de otra titularidad no ve el botón de solicitar.</p>
				</fieldset>
			</div>
			<?php if ( ! $nuevo ) : ?>
				<div class="prc-campo prc-campo--completo">
					<label class="prc-campo__etiqueta" for="prc-description">Descripción</label>
					<textarea class="prc-control" id="prc-description" name="<?php echo esc_attr( ProcedureEditor::FIELD_DESCRIPTION ); ?>" rows="8"
						aria-describedby="prc-description-ayuda"><?php echo esc_textarea( (string) $v[ ProcedureEditor::FIELD_DESCRIPTION ] ); ?></textarea>
					<p class="prc-campo__ayuda" id="prc-description-ayuda">De qué va y a quién se dirige. Es lo que se lee en la ficha, debajo del título.</p>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * «Promociona»: el árbol de ámbitos, con sus casillas.
	 *
	 * @param array<string, mixed>             $v     Field values.
	 * @param array<int, array<string, mixed>> $arbol Ámbito tree.
	 * @param string[]                         $foreign Read-only organiser labels.
	 * @return string
	 */
	private static function area_field( array $v, array $arbol, array $foreign ): string {
		$elegidos = array_map( 'intval', (array) $v[ ProcedureEditor::FIELD_AREA ] );

		ob_start();
		?>
		<div class="prc-campo">
			<fieldset>
				<legend class="prc-campo__etiqueta">Ámbito convocante <?php echo self::obl(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></legend>
				<?php if ( array() === $arbol ) : ?>
					<p class="prc-vacio">No tiene ningún ámbito asignado. Pídalo a quien administre el aplicativo.</p>
				<?php else : ?>
					<ul class="prc-arbol">
						<?php foreach ( $arbol as $nodo ) : ?>
							<?php echo self::area_node( (array) $nodo, $elegidos ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<?php if ( $foreign ) : ?>
					<p>Otros ámbitos organizadores (solo lectura):</p>
					<ul>
					<?php
					foreach ( $foreign as $label ) :
						?>
						<li><?php echo esc_html( $label ); ?></li><?php endforeach; ?></ul>
					<p>Se conservarán al guardar. Solo administración o una persona de ese ámbito puede modificar su participación.</p>
				<?php endif; ?>
				<p class="prc-campo__ayuda">
					Puede marcar varios: todos ellos editan el procedimiento y gestionan sus
					solicitudes. Solo salen los suyos; para pasarlo a otro, pídalo a quien
					administra el aplicativo.
				</p>
			</fieldset>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * One node of the ámbito tree: its checkbox and, if it has any, its branch.
	 *
	 * El plegado es una casilla sin `name` —no se envía— que una regla de CSS
	 * usa para esconder la rama: sin JavaScript el árbol se pliega igual, y
	 * donde `:has()` no llegue se queda abierto, que es el fallo bueno. Una
	 * rama viene abierta cuando ella o alguno de los suyos está marcado.
	 *
	 * @param array<string, mixed> $nodo     Node.
	 * @param int[]                $elegidos Selected term IDs.
	 * @return string
	 */
	private static function area_node( array $nodo, array $elegidos ): string {
		$id      = (int) $nodo['id'];
		$hijos   = (array) $nodo['children'];
		$rama    = array() !== $hijos;
		$abierta = self::area_selected( $nodo, $elegidos );

		ob_start();
		?>
		<li class="prc-arbol__nodo<?php echo $rama ? ' prc-arbol__nodo--rama' : ''; ?>">
			<div class="prc-arbol__fila prc-opcion">
				<?php if ( $rama ) : ?>
					<input class="prc-arbol__pliegue" type="checkbox" id="prc-rama-<?php echo esc_attr( (string) $id ); ?>" <?php checked( $abierta ); ?> />
					<label class="prc-arbol__tri" for="prc-rama-<?php echo esc_attr( (string) $id ); ?>">
						<span class="screen-reader-text"><?php echo esc_html( 'Desplegar o plegar «' . (string) $nodo['name'] . '»' ); ?></span>
					</label>
				<?php else : ?>
					<span class="prc-arbol__hueco" aria-hidden="true"></span>
				<?php endif; ?>
				<input class="prc-arbol__casilla" type="checkbox" id="prc-area-<?php echo esc_attr( (string) $id ); ?>"
					name="<?php echo esc_attr( ProcedureEditor::FIELD_AREA ); ?>[]" value="<?php echo esc_attr( (string) $id ); ?>"
					<?php checked( in_array( $id, $elegidos, true ) ); ?> />
				<label for="prc-area-<?php echo esc_attr( (string) $id ); ?>"><?php echo esc_html( (string) $nodo['name'] ); ?></label>
			</div>
			<?php if ( $rama ) : ?>
				<ul class="prc-arbol prc-arbol--rama">
					<?php foreach ( $hijos as $hijo ) : ?>
						<?php echo self::area_node( (array) $hijo, $elegidos ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</li>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Whether this node or any below it is selected.
	 *
	 * @param array<string, mixed> $nodo     Node.
	 * @param int[]                $elegidos Selected term IDs.
	 * @return bool
	 */
	private static function area_selected( array $nodo, array $elegidos ): bool {
		if ( in_array( (int) $nodo['id'], $elegidos, true ) ) {
			return true;
		}
		foreach ( (array) $nodo['children'] as $hijo ) {
			if ( self::area_selected( (array) $hijo, $elegidos ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * «Más información»: los tres correos de contacto.
	 *
	 * @param array<string, mixed> $v Field values.
	 * @return string
	 */
	private static function email_fields( array $v ): string {
		$correos = array_pad( array_values( (array) $v[ ProcedureMetaKeys::CONTACT_EMAILS ] ), ProcedureMetaKeys::CONTACT_EMAILS_MAX, '' );
		$rotulos = array( 'Correo de contacto', '2.º correo', '3.er correo' );

		ob_start();
		?>
		<?php foreach ( $rotulos as $i => $rotulo ) : ?>
			<div class="prc-campo">
				<label class="prc-campo__etiqueta" for="prc-contact-<?php echo esc_attr( (string) $i ); ?>">
					<?php echo esc_html( $rotulo ); ?>
					<?php
					if ( 0 === $i ) {
						echo self::obl(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
					}
					?>
				</label>
				<input class="prc-control" type="email" id="prc-contact-<?php echo esc_attr( (string) $i ); ?>"
					name="<?php echo esc_attr( ProcedureMetaKeys::CONTACT_EMAILS ); ?>[]"
					<?php echo 0 === $i ? 'required' : ''; ?>
					value="<?php echo esc_attr( (string) $correos[ $i ] ); ?>" />
			</div>
		<?php endforeach; ?>
		<p class="prc-campo__ayuda">
			Para las dudas de los centros sobre el procedimiento, no sobre el aplicativo. El
			primero sale en la ficha pública; los otros dos son opcionales.
		</p>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * «Aspecto»: la paleta de color de la cabecera.
	 *
	 * Diez muestras, no cuarenta y nueve, y ninguna es una fotografía: la
	 * lista está cerrada y todas contrastan con el blanco del rótulo que
	 * llevan encima. La elegida se ve elegida, que en el original no se veía.
	 *
	 * @param array<string, mixed> $m Model.
	 * @param array<string, mixed> $v Field values.
	 * @return string
	 */
	private static function palette_field( array $m, array $v ): string {
		$elegido = (string) $v[ ProcedureMetaKeys::HEADER_COLOR ];
		$elegido = '' !== $elegido ? $elegido : ProcedureMetaKeys::HEADER_COLOR_NEUTRAL;

		ob_start();
		?>
		<div class="prc-campo">
			<fieldset>
				<legend class="prc-campo__etiqueta">Color de cabecera <?php echo self::obl(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></legend>
				<div class="prc-paleta">
					<?php foreach ( (array) $m['header_colors'] as $clave => $color ) : ?>
						<label class="prc-muestra" style="--prc-muestra: <?php echo esc_attr( (string) $color['hex'] ); ?>">
							<input type="radio" name="<?php echo esc_attr( ProcedureMetaKeys::HEADER_COLOR ); ?>"
								value="<?php echo esc_attr( (string) $clave ); ?>" <?php checked( (string) $clave, $elegido ); ?> />
							<span class="prc-muestra__nombre"><?php echo esc_html( (string) $color['label'] ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
				<p class="prc-campo__ayuda">Es la banda de color de la tarjeta de la portada y de la ficha.</p>
			</fieldset>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * «Plazos»: las cuatro fechas de las que sale el estado.
	 *
	 * @param array<string, mixed> $v Field values.
	 * @return string
	 */
	private static function date_fields( array $v ): string {
		$fechas = array(
			ProcedureMetaKeys::OPENS_AT        => array( 'prc-opens', 'Inicio del plazo de solicitud', 'Sin fecha, el procedimiento se publica como próximo y nadie puede solicitar.' ),
			ProcedureMetaKeys::CLOSES_AT       => array( 'prc-closes', 'Fin del plazo de solicitud', 'Inclusive. En blanco, el plazo dura el día de inicio.' ),
			ProcedureMetaKeys::AMEND_OPENS_AT  => array( 'prc-amend-opens', 'Inicio de la subsanación', 'Después del plazo de solicitud. Solo editan los centros a los que se pida subsanar.' ),
			ProcedureMetaKeys::AMEND_CLOSES_AT => array( 'prc-amend-closes', 'Fin de la subsanación', 'Inclusive. En blanco, dura el día de inicio.' ),
		);

		ob_start();
		?>
		<p class="prc-campo__ayuda">De estas fechas sale el estado del procedimiento —próximo, abierto, cerrado, en subsanación—, así que no hay que marcarlo a mano en ningún sitio.</p>
		<div class="prc-seccion__cuerpo">
			<?php foreach ( $fechas as $clave => $texto ) : ?>
				<div class="prc-campo prc-campo--medio">
					<label class="prc-campo__etiqueta" for="<?php echo esc_attr( $texto[0] ); ?>"><?php echo esc_html( $texto[1] ); ?></label>
					<input class="prc-control" type="date" id="<?php echo esc_attr( $texto[0] ); ?>" name="<?php echo esc_attr( (string) $clave ); ?>"
						value="<?php echo esc_attr( (string) $v[ $clave ] ); ?>" />
					<p class="prc-campo__ayuda"><?php echo esc_html( $texto[2] ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * «La solicitud»: lo que se le pide al centro además de las preguntas.
	 *
	 * @param array<string, mixed> $v Field values.
	 * @return string
	 */
	private static function request_fields( array $v ): string {
		ob_start();
		?>
		<div class="prc-campo">
			<div class="prc-opcion">
				<input type="checkbox" id="prc-coordinator"
					name="<?php echo esc_attr( ProcedureMetaKeys::REQUIRES_COORDINATOR ); ?>" value="1"
					<?php checked( '' !== (string) $v[ ProcedureMetaKeys::REQUIRES_COORDINATOR ] ); ?> />
				<label for="prc-coordinator">La solicitud pide una persona coordinadora</label>
			</div>
			<p class="prc-campo__ayuda">Nombre y correo de quien coordina en el centro.</p>
		</div>
		<div class="prc-campo">
			<label class="prc-campo__etiqueta" for="prc-commitments">Compromisos del centro</label>
			<textarea class="prc-control" id="prc-commitments" name="<?php echo esc_attr( ProcedureMetaKeys::COMMITMENTS ); ?>" rows="5"
				aria-describedby="prc-commitments-ayuda"><?php echo esc_textarea( (string) $v[ ProcedureMetaKeys::COMMITMENTS ] ); ?></textarea>
			<p class="prc-campo__ayuda" id="prc-commitments-ayuda">Lo que el centro acepta al solicitar, con una casilla obligatoria. En blanco, no se pide nada.</p>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The asterisk that says a field is required.
	 *
	 * @return string
	 */
	private static function obl(): string {
		return '<abbr class="prc-campo__obligatorio" title="obligatorio">*</abbr>';
	}

	/**
	 * One taxonomy dropdown, with its help line.
	 *
	 * @param string             $id       Field id.
	 * @param string             $nombre   Field name.
	 * @param string             $rotulo   Label.
	 * @param array<int, string> $terminos term_id => nombre.
	 * @param int                $elegido  Selected term ID.
	 * @param string             $ayuda    Help text.
	 * @return string
	 */
	private static function term_select( string $id, string $nombre, string $rotulo, array $terminos, int $elegido, string $ayuda ): string {
		ob_start();
		?>
		<label class="prc-campo__etiqueta" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $rotulo ); ?> <?php echo self::obl(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></label>
		<select class="prc-control" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $nombre ); ?>" required aria-describedby="<?php echo esc_attr( $id . '-ayuda' ); ?>">
			<option value="0">— Elija —</option>
			<?php foreach ( $terminos as $term_id => $texto ) : ?>
				<option value="<?php echo esc_attr( (string) (int) $term_id ); ?>" <?php selected( (int) $term_id, $elegido ); ?>><?php echo esc_html( (string) $texto ); ?></option>
			<?php endforeach; ?>
		</select>
		<p class="prc-campo__ayuda" id="<?php echo esc_attr( $id . '-ayuda' ); ?>"><?php echo esc_html( $ayuda ); ?></p>
		<?php
		return (string) ob_get_clean();
	}

	// ─── Preguntas ─────────────────────────────────────────────────────────

	/**
	 * The «Preguntas» panel: the question list, plus one blank row to add another.
	 *
	 * **No es un constructor de formularios**: una pregunta tiene rótulo,
	 * indicaciones, tipo, opciones y si es obligatoria. Ni condiciones ni
	 * reglas (ADR-0019). Sin JavaScript: las filas son campos paralelos y la
	 * última va en blanco; se borra el rótulo y la pregunta se va.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function questions_panel( array $m ): string {
		$op    = ProcedureEditor::PANEL_QUESTIONS;
		$filas = (array) $m['questions'];
		if ( count( $filas ) < (int) $m['q_max'] ) {
			$filas[] = array(
				'key'      => '',
				'label'    => '',
				'help'     => '',
				'type'     => ProcedureQuestions::TYPE_TEXT,
				'required' => false,
				'choices'  => array(),
			);
		}

		ob_start();
		?>
		<form class="prc-form prc-form--taller" method="post" action="">
			<?php wp_nonce_field( ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />

			<p class="prc-intro">
				Las opciones propias de este procedimiento, hasta <?php echo esc_html( (string) (int) $m['q_max'] ); ?>. El resto de la
				solicitud —centro, cargo, coordinación y compromisos— es siempre el mismo y se ajusta en «Datos».
				Para quitar una pregunta, borre su rótulo y guarde: lo que ya hubiera contestado un centro no se pierde.
			</p>

			<?php foreach ( $filas as $i => $pregunta ) : ?>
				<?php echo self::question_row( (int) $i, (array) $pregunta, (array) $m['q_types'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			<?php endforeach; ?>

			<?php echo PanelParts::submit( 'Guardar las preguntas' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * One question row.
	 *
	 * @param int                   $i     Row index.
	 * @param array<string, mixed>  $p     Question.
	 * @param array<string, string> $tipos Question types.
	 * @return string
	 */
	private static function question_row( int $i, array $p, array $tipos ): string {
		$nueva  = '' === (string) $p['key'];
		$campo  = ProcedureEditor::FIELD_Q;
		$rotulo = $nueva ? 'Pregunta nueva' : 'Pregunta ' . ( $i + 1 );

		ob_start();
		?>
		<fieldset class="prc-seccion-campos prc-pregunta">
			<legend class="prc-seccion__titulo"><?php echo esc_html( $rotulo ); ?></legend>
			<input type="hidden" name="<?php echo esc_attr( $campo . 'key' ); ?>[<?php echo esc_attr( (string) $i ); ?>]" value="<?php echo esc_attr( (string) $p['key'] ); ?>" />
			<div class="prc-seccion__cuerpo">
				<div class="prc-campo prc-campo--medio">
					<label class="prc-campo__etiqueta" for="prc-q-l-<?php echo esc_attr( (string) $i ); ?>">Rótulo</label>
					<input class="prc-control" type="text" id="prc-q-l-<?php echo esc_attr( (string) $i ); ?>" name="<?php echo esc_attr( $campo . 'label' ); ?>[<?php echo esc_attr( (string) $i ); ?>]"
						maxlength="200" value="<?php echo esc_attr( (string) $p['label'] ); ?>" />
				</div>
				<div class="prc-campo prc-campo--medio">
					<label class="prc-campo__etiqueta" for="prc-q-t-<?php echo esc_attr( (string) $i ); ?>">Tipo</label>
					<select class="prc-control" id="prc-q-t-<?php echo esc_attr( (string) $i ); ?>" name="<?php echo esc_attr( $campo . 'type' ); ?>[<?php echo esc_attr( (string) $i ); ?>]">
						<?php foreach ( $tipos as $valor => $nombre ) : ?>
							<option value="<?php echo esc_attr( (string) $valor ); ?>" <?php selected( (string) $valor, (string) $p['type'] ); ?>><?php echo esc_html( (string) $nombre ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<div class="prc-campo">
				<label class="prc-campo__etiqueta" for="prc-q-h-<?php echo esc_attr( (string) $i ); ?>">Indicaciones</label>
				<input class="prc-control" type="text" id="prc-q-h-<?php echo esc_attr( (string) $i ); ?>" name="<?php echo esc_attr( $campo . 'help' ); ?>[<?php echo esc_attr( (string) $i ); ?>]"
					value="<?php echo esc_attr( (string) $p['help'] ); ?>" />
				<p class="prc-campo__ayuda">Una línea bajo la pregunta que ayude a contestarla. Puede dejarse en blanco.</p>
			</div>
			<div class="prc-campo">
				<label class="prc-campo__etiqueta" for="prc-q-c-<?php echo esc_attr( (string) $i ); ?>">Opciones, una por línea</label>
				<textarea class="prc-control" id="prc-q-c-<?php echo esc_attr( (string) $i ); ?>" name="<?php echo esc_attr( $campo . 'choices' ); ?>[<?php echo esc_attr( (string) $i ); ?>]" rows="3"><?php echo esc_textarea( implode( "\n", (array) $p['choices'] ) ); ?></textarea>
				<p class="prc-campo__ayuda">Solo para «Una opción» y «Varias opciones».</p>
			</div>
			<div class="prc-campo prc-opcion">
				<input type="checkbox" id="prc-q-r-<?php echo esc_attr( (string) $i ); ?>" name="<?php echo esc_attr( $campo . 'required' ); ?>[<?php echo esc_attr( (string) $i ); ?>]" value="1"
					<?php checked( (bool) $p['required'] ); ?> />
				<label for="prc-q-r-<?php echo esc_attr( (string) $i ); ?>">Obligatoria</label>
			</div>
		</fieldset>
		<?php
		return (string) ob_get_clean();
	}

	// ─── Enlaces ───────────────────────────────────────────────────────────

	/**
	 * The «Enlaces» panel: resolution and lists are links, not copies (ADR-0021).
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function links_panel( array $m ): string {
		$v      = (array) $m['values'];
		$op     = ProcedureEditor::PANEL_LINKS;
		$campos = array(
			ProcedureMetaKeys::RESOLUTION_URL       => array( 'Resolución', 'La resolución que aprueba el procedimiento, donde esté publicada.' ),
			ProcedureMetaKeys::PROVISIONAL_LIST_URL => array( 'Listado provisional', 'Los centros admitidos provisionalmente.' ),
			ProcedureMetaKeys::FINAL_LIST_URL       => array( 'Listado definitivo', 'Con este enlace el procedimiento pasa a «Resuelto» y los centros lo ven en su solicitud.' ),
		);

		ob_start();
		?>
		<form class="prc-form prc-form--taller" method="post" action="">
			<?php wp_nonce_field( ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
			<fieldset class="prc-seccion-campos">
				<legend class="prc-seccion__titulo">Resolución y listados</legend>
				<p class="prc-intro">Son enlaces a donde ya están publicados: aquí no se guarda ninguna copia.</p>
				<?php foreach ( $campos as $clave => $texto ) : ?>
					<div class="prc-campo">
						<label class="prc-campo__etiqueta" for="<?php echo esc_attr( $clave ); ?>"><?php echo esc_html( $texto[0] ); ?></label>
						<input class="prc-control" type="url" id="<?php echo esc_attr( $clave ); ?>" name="<?php echo esc_attr( $clave ); ?>" placeholder="https://"
							value="<?php echo esc_attr( (string) $v[ $clave ] ); ?>" />
						<p class="prc-campo__ayuda"><?php echo esc_html( $texto[1] ); ?></p>
					</div>
				<?php endforeach; ?>
			</fieldset>
			<?php echo PanelParts::submit( 'Guardar los enlaces' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	// ─── Solicitudes ───────────────────────────────────────────────────────

	/**
	 * The «Solicitudes» panel: the table, the review of each one and the export.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function applications_panel( array $m ): string {
		$filas = (array) $m['applications'];
		if ( array() === $filas ) {
			return '<section class="prc-seccion-campos"><h2 class="prc-seccion__titulo">Todavía no hay solicitudes</h2>'
				. '<p class="prc-vacio">Cuando un centro solicite saldrá en esta tabla, y desde ella podrá admitirlo, pedirle subsanar o excluirlo, y exportar la lista a CSV.</p></section>';
		}
		$cuenta = count( $filas );

		ob_start();
		?>
		<div class="prc-banda-tabla">
			<p class="prc-recuento">
				<?php echo esc_html( sprintf( 1 === $cuenta ? 'Mostrando %d solicitud.' : 'Mostrando %d solicitudes.', $cuenta ) ); ?>
				Cada una se revisa desde su fila; la exportación lleva todas las columnas, respuestas incluidas.
			</p>
			<?php echo self::export_form( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
		</div>
		<div class="prc-tabla-caja prc-panel-solicitudes">
			<table class="prc-tabla prc-tabla--densa table">
				<caption class="screen-reader-text">Solicitudes presentadas a este procedimiento</caption>
				<thead>
					<tr>
						<th class="prc-th" scope="col">Centro</th>
						<th class="prc-th" scope="col">Código</th>
						<th class="prc-th" scope="col">Fecha</th>
						<th class="prc-th" scope="col">Cargo</th>
						<th class="prc-th" scope="col">Coordinación</th>
						<th class="prc-th" scope="col">Revisión</th>
						<th class="prc-th" scope="col">Acciones</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $filas as $fila ) : ?>
						<tr data-estado="<?php echo esc_attr( (string) $fila['state_key'] ); ?>">
							<td class="prc-celda" data-rotulo="Centro"><?php echo esc_html( '' !== (string) $fila['centre'] ? (string) $fila['centre'] : '(centro fuera del catálogo)' ); ?></td>
							<td class="prc-celda" data-rotulo="Código"><?php echo esc_html( (string) $fila['code'] ); ?></td>
							<td class="prc-celda" data-rotulo="Fecha"><?php echo esc_html( (string) $fila['date_label'] ); ?></td>
							<td class="prc-celda" data-rotulo="Cargo"><?php echo esc_html( (string) $fila['position'] ); ?></td>
							<td class="prc-celda" data-rotulo="Coordinación"><?php echo self::dash( trim( (string) $fila['coordinator'] . ' ' . (string) $fila['coordinator_email'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></td>
							<td class="prc-celda" data-rotulo="Revisión"><?php echo self::badge( (string) $fila['state_key'], (string) $fila['state'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></td>
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
	 * @param array<string, mixed>  $m    Model.
	 * @param array<string, string> $fila Row.
	 * @return string
	 */
	private static function review_form( array $m, array $fila ): string {
		$op = ProcedureEditor::OP_REVIEW;
		$id = (int) $fila['id'];

		ob_start();
		?>
		<details class="prc-revision">
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
			<form class="prc-form prc-form--taller prc-revision__form" method="post" action="">
				<?php wp_nonce_field( ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op, $id ), false ); ?>
				<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
				<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
				<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_ROW ); ?>" value="<?php echo esc_attr( (string) $id ); ?>" />
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

	/**
	 * The export button: a POST with its nonce, never a link.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function export_form( array $m ): string {
		$op = ProcedureEditor::OP_EXPORT;

		ob_start();
		?>
		<form class="prc-acciones" method="post" action="">
			<?php wp_nonce_field( ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
			<button class="<?php echo esc_attr( Assets::button_class() ); ?>" type="submit">Exportar a CSV</button>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	// ─── Publicación ───────────────────────────────────────────────────────

	/**
	 * The «Publicación» panel: publish, and what the state means.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function publish_panel( array $m ): string {
		$op = ProcedureEditor::OP_PUBLISH;

		ob_start();
		?>
		<section class="prc-seccion-campos">
			<h2 class="prc-seccion__titulo">Estado</h2>
			<p class="prc-intro">
				<?php echo self::badge( (string) $m['state'], (string) $m['state_label'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				El estado sale de las fechas y de los enlaces: no hay nada que marcar a mano. Con el listado definitivo enlazado pasa a «Resuelto».
			</p>
			<?php if ( true === $m['can_publish'] ) : ?>
				<p class="prc-intro">En borrador no se ve fuera y ningún centro puede solicitar. Al publicarlo sale en la portada, y los centros solicitan cuando abra el plazo.</p>
				<form class="prc-acciones" method="post" action=""
					data-prc-confirm="<?php echo esc_attr( sprintf( '¿Publicar «%s»? Pasará a verse en la portada.', (string) $m['title'] ) ); ?>"
					data-prc-confirm-ok="Publicar">
					<?php wp_nonce_field( ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op ), false ); ?>
					<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
					<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
					<button class="<?php echo esc_attr( Assets::button_class( true ) . ' prc-envio' ); ?>" type="submit">Publicar el procedimiento</button>
				</form>
			<?php elseif ( 'publish' === (string) $m['status'] ) : ?>
				<p class="prc-intro">Publicado: se ve en la portada y en su ficha.</p>
			<?php endif; ?>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The switch that closes the procedure for good, or opens it again.
	 *
	 * Cerrarlo es una acción normal del ámbito que convoca; reabrirlo es de
	 * administración y va en el recuadro amarillo (ADR-0023).
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function archive_switch( array $m ): string {
		if ( true === $m['can_unarchive'] ) {
			return Shell::admin_box(
				'Volver a abrir el procedimiento',
				self::archive_form( $m, false ),
				'Cerrar un procedimiento lo hace su ámbito; volver a abrirlo, solo quien administra el aplicativo.'
			);
		}
		if ( true !== $m['can_archive'] ) {
			return '';
		}

		ob_start();
		?>
		<section class="prc-seccion-campos">
			<h2 class="prc-seccion__titulo">Dar el procedimiento por terminado</h2>
			<p class="prc-intro">Cuando ya no quede nada que tocar —resuelto, con los listados enlazados y las solicitudes revisadas—, márquelo como histórico y quedará cerrado tal y como está. Seguirá entrando a consultarlo y a exportarlo, y la ficha pública se verá igual; lo que ya no podrá es cambiar nada. <strong>Para volver a abrirlo tendrá que pedírselo a quien administre el aplicativo.</strong></p>
			<?php echo self::archive_form( $m, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The form of one half of the switch.
	 *
	 * @param array<string, mixed> $m      Model.
	 * @param bool                 $marcar Whether this is the marking half.
	 * @return string
	 */
	private static function archive_form( array $m, bool $marcar ): string {
		$op       = $marcar ? ProcedureEditor::OP_ARCHIVE : ProcedureEditor::OP_UNARCHIVE;
		$rotulo   = $marcar ? 'Marcar como histórico' : 'Volver a abrir el procedimiento';
		$pregunta = $marcar
			? sprintf( '¿Marcar «%s» como histórico? Dejará de poder editarlo y de gestionar sus solicitudes, y no hay vuelta atrás: solo quien administre el aplicativo puede volver a abrirlo.', (string) $m['title'] )
			: '¿Volver a abrir este procedimiento? Su ámbito podrá editarlo otra vez.';

		ob_start();
		?>
		<form class="prc-acciones" method="post" action=""
			data-prc-confirm="<?php echo esc_attr( $pregunta ); ?>"
			data-prc-confirm-ok="<?php echo esc_attr( $rotulo ); ?>">
			<?php wp_nonce_field( ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
			<button type="submit" class="<?php echo esc_attr( Assets::button_class() ); ?>"><?php echo esc_html( $rotulo ); ?></button>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	// ─── piezas ────────────────────────────────────────────────────────────

	/**
	 * A state chip: procedure state, post status or review state.
	 *
	 * @param string $slug  State slug.
	 * @param string $label Human label.
	 * @return string
	 */
	private static function badge( string $slug, string $label ): string {
		if ( '' === $label ) {
			return '';
		}
		return '<span class="' . esc_attr( Assets::state_class( $slug ) ) . '">' . esc_html( $label ) . '</span>';
	}

	/**
	 * Way out when there is no procedure to work on.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function back_link( array $m ): string {
		$url = (string) $m['workspace_url'];
		if ( '' === $url ) {
			return '';
		}
		return '<p><a class="' . esc_attr( Assets::button_class( true ) ) . '" href="' . esc_url( $url ) . '">Ver mis procedimientos</a></p>';
	}
}
