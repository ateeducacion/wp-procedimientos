<?php
/**
 * The procedure workshop, painted from what ProcedureEditor::model() decided.
 *
 * @package Prc
 */

namespace Prc\PublicFront\View;

use Prc\Domain\CentreEligibility;
use Prc\Domain\ProcedureQuestions;
use Prc\Domain\QuestionChanges;
use Prc\Domain\QuestionPoints;
use Prc\Domain\ProcedureState;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PublicFront\Assets;
use Prc\PublicFront\ProcedureEditor;
use Prc\PublicFront\Shell;

/**
 * Solo pinta: no lee la petición, no consulta, no decide.
 *
 * El marco del taller —la cabecera del procedimiento, las pestañas y el
 * aviso de lo último que se hizo— y los paneles del taller.
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
		// Lo que falta para publicar: el curso y las fechas de las entregas.
		echo Shell::notice( 'aviso', implode( ' ', (array) $m['not_ready'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.

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
			echo ApplicationsPanelView::html( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		} elseif ( ProcedureEditor::PANEL_DELIVERIES === $panel ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
			echo self::deliveries_panel( $m );
		} elseif ( ProcedureEditor::PANEL_PUBLISH === $panel ) {
			echo self::publish_panel( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		} elseif ( ProcedureEditor::PANEL_NOTICES === $panel ) {
			echo self::notices_panel( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		} elseif ( ProcedureEditor::PANEL_PEOPLE === $panel ) {
			echo PeopleView::spec_panel( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		} elseif ( ProcedureEditor::PANEL_OBSERVE === $panel ) {
			echo ObserveView::settings( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		} elseif ( ProcedureEditor::PANEL_CODE === $panel ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
			echo self::custom_code_panel( $m );
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
			// El panel apaga él mismo lo que escribe: filtrar y pasar de
			// página siguen abiertos aunque no se gestionen las solicitudes.
			return false;
		}
		if ( ProcedureEditor::PANEL_DELIVERIES === $panel ) {
			return true !== $m['can_edit'];
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
	 * Extend the closing date of independent deliveries.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function deliveries_panel( array $m ): string {
		ob_start();
		?>
		<section aria-labelledby="prc-deliveries-title">
			<h2 id="prc-deliveries-title">Plazos de entregas documentales</h2>
			<p>Puede ampliar el cierre sin reabrir la inscripción. El inicio y las preguntas se conservan; el nuevo cierre no puede ser anterior al actual.</p>
			<?php if ( empty( $m['deliveries'] ) ) : ?>
				<p>Este procedimiento no tiene entregas con plazo propio.</p>
			<?php else : ?>
				<form class="prc-form" method="post" action="">
					<?php wp_nonce_field( ProcedureEditor::nonce_action( ProcedureEditor::OP_DELIVERY_DATES ), ProcedureEditor::nonce_name( ProcedureEditor::OP_DELIVERY_DATES ), false ); ?>
					<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( ProcedureEditor::OP_DELIVERY_DATES ); ?>" />
					<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
					<?php foreach ( $m['deliveries'] as $delivery ) : ?>
						<div class="prc-campo">
							<label class="prc-campo__etiqueta" for="prc-delivery-close-<?php echo esc_attr( (string) $delivery['key'] ); ?>"><?php echo esc_html( (string) $delivery['label'] ); ?> — cierre actual: <?php echo esc_html( (string) $delivery['closes_at'] ); ?></label>
							<input class="prc-control" type="date" id="prc-delivery-close-<?php echo esc_attr( (string) $delivery['key'] ); ?>" name="<?php echo esc_attr( ProcedureEditor::FIELD_DELIVERY_CLOSES . '[' . $delivery['key'] . ']' ); ?>" min="<?php echo esc_attr( (string) $delivery['closes_at'] ); ?>" value="<?php echo esc_attr( (string) $delivery['closes_at'] ); ?>" required />
						</div>
					<?php endforeach; ?>
					<?php echo PanelParts::submit( 'Guardar nuevos cierres' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				</form>
			<?php endif; ?>
		</section>
		<?php
		return (string) ob_get_clean();
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
		echo PanelParts::help_link( (string) ( $m['help_new_url'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
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
			<?php echo self::badge( 'audience', (string) ( $m['audience_label'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			<span class="prc-acciones">
				<?php echo PanelParts::icon_link( (string) $m['view_url'], 'ojo', 'Ver la ficha pública' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				<?php echo self::duplicate_form( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			</span>
		</div>
		<p class="prc-sub"><?php echo esc_html( 'Ámbito: ' . (string) $m['area_names'] ); ?></p>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * «Duplicar»: otro borrador como este, en cualquier estado.
	 *
	 * Va en la cabecera, fuera de lo que el solo lectura apaga: un histórico
	 * o un procedimiento que otra persona tiene abierto también se duplican.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function duplicate_form( array $m ): string {
		if ( true !== $m['can_duplicate'] ) {
			return '';
		}
		$op = ProcedureEditor::OP_DUPLICATE;
		ob_start();
		?>
		<form class="prc-acciones" method="post" action=""
			data-prc-confirm="<?php echo esc_attr( self::duplicate_question( (string) $m['title'] ) ); ?>"
			data-prc-confirm-ok="Duplicar" data-prc-once>
			<?php wp_nonce_field( ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
			<button type="submit" class="<?php echo esc_attr( Assets::button_class() . ' prc-mini' ); ?>">Duplicar</button>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * What «Duplicar» asks before going on: the same words in the workshop and in «Gestión».
	 *
	 * @param string $title Title of the procedure.
	 * @return string
	 */
	public static function duplicate_question( string $title ): string {
		return sprintf(
			'¿Duplicar «%s»? Se creará un borrador nuevo con sus datos, sus preguntas y su configuración, sin plazos, sin curso y sin solicitudes.',
			'' !== $title ? $title : 'Procedimiento sin título'
		);
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
			$secciones .= PanelParts::section( 'Qué centros pueden solicitar', self::eligibility_fields( $m, $v ), $apagar, $motivo );
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
		$clases      = (array) $v[ ProcedureMetaKeys::CENTRE_KINDS ];
		$modo        = in_array( ProcedureMetaKeys::KIND_SCHOOL, $clases, true ) && in_array( ProcedureMetaKeys::KIND_RURAL_GROUP, $clases, true )
			? 'both'
			: ( in_array( ProcedureMetaKeys::KIND_RURAL_GROUP, $clases, true ) ? ProcedureMetaKeys::KIND_RURAL_GROUP : ProcedureMetaKeys::KIND_SCHOOL );
		$modalidades = array(
			ProcedureMetaKeys::KIND_SCHOOL      => 'Cada centro, con su código',
			ProcedureMetaKeys::KIND_RURAL_GROUP => 'El CER como colectivo, con su código',
			'both'                              => 'Ambos',
		);
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
				<p class="prc-campo__ayuda" id="prc-audience-ayuda">
					<span data-prc-schools-only <?php echo ProcedureMetaKeys::AUDIENCE_SCHOOLS === $v[ ProcedureMetaKeys::AUDIENCE ] ? '' : 'hidden'; ?>>Solicita la dirección del centro con su cuenta personal, no con la cuenta genérica del centro, y con el cargo de dirección en su perfil.</span>
					<span class="prc-solo-docentes">Cada docente presenta su propia solicitud con su cuenta personal.</span>
				</p>
			</div>
			<div class="prc-campo prc-campo--medio" data-prc-schools-only <?php echo ProcedureMetaKeys::AUDIENCE_SCHOOLS === $v[ ProcedureMetaKeys::AUDIENCE ] ? '' : 'hidden'; ?>>
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
					<p class="prc-campo__ayuda">Marque al menos una. Un centro de otra titularidad no podrá solicitar, y la ficha le dirá por qué.</p>
					<?php echo self::centre_warning( $m, 'ownership' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				</fieldset>
			</div>
			<div class="prc-campo prc-campo--medio" data-prc-schools-only <?php echo ProcedureMetaKeys::AUDIENCE_SCHOOLS === $v[ ProcedureMetaKeys::AUDIENCE ] ? '' : 'hidden'; ?>>
				<fieldset aria-describedby="prc-centre-kind-mode-help">
					<legend class="prc-campo__etiqueta">Modalidad para centros CER <?php echo self::obl(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?></legend>
					<div class="prc-opciones prc-opciones--vertical">
						<?php foreach ( $modalidades as $clave => $rotulo ) : ?>
							<div class="prc-opcion">
								<input type="radio" id="prc-centre-kind-<?php echo esc_attr( $clave ); ?>"
									name="<?php echo esc_attr( ProcedureMetaKeys::CENTRE_KINDS ); ?>" value="<?php echo esc_attr( $clave ); ?>"
									required <?php checked( $modo, $clave ); ?> />
								<label for="prc-centre-kind-<?php echo esc_attr( $clave ); ?>"><?php echo esc_html( $rotulo ); ?></label>
							</div>
						<?php endforeach; ?>
					</div>
					<p class="prc-campo__ayuda" id="prc-centre-kind-mode-help">Si elige «Ambos», quien dirige un centro y coordina un CER podrá solicitar como centro, como CER o de las dos formas. Cada solicitud se presenta por separado.</p>
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
		$numero = (int) $v[ ProcedureMetaKeys::COORDINATOR_COUNT ];
		$tipo   = 0 === $numero ? 'none' : ( 1 === $numero ? 'one' : 'several' );
		$varias = max( 2, $numero );

		ob_start();
		?>
		<fieldset class="prc-campo">
			<legend class="prc-campo__etiqueta">¿Pide la solicitud personas coordinadoras?</legend>
			<div class="prc-opciones prc-opciones--vertical">
				<div class="prc-opcion">
					<label>
						<input type="radio" name="<?php echo esc_attr( ProcedureEditor::FIELD_COORDINATOR_KIND ); ?>" value="none" <?php checked( 'none', $tipo ); ?> />
						No se piden
					</label>
				</div>
				<div class="prc-opcion">
					<label>
						<input type="radio" name="<?php echo esc_attr( ProcedureEditor::FIELD_COORDINATOR_KIND ); ?>" value="one" <?php checked( 'one', $tipo ); ?> />
						Sí, una persona
					</label>
				</div>
				<div class="prc-opcion prc-opcion--varias">
					<label>
						<input type="radio" name="<?php echo esc_attr( ProcedureEditor::FIELD_COORDINATOR_KIND ); ?>" value="several" <?php checked( 'several', $tipo ); ?> />
						Sí, varias personas:
					</label>
					<select class="prc-control" id="prc-coordinator-count" name="<?php echo esc_attr( ProcedureMetaKeys::COORDINATOR_COUNT ); ?>" aria-label="Número de personas coordinadoras">
						<?php for ( $count = 2; $count <= ProcedureMetaKeys::COORDINATOR_COUNT_MAX; ++$count ) : ?>
							<option value="<?php echo esc_attr( (string) $count ); ?>" <?php selected( $count, $varias ); ?>><?php echo esc_html( (string) $count ); ?></option>
						<?php endfor; ?>
					</select>
				</div>
			</div>
			<p class="prc-campo__ayuda">Lo habitual es pedir una persona coordinadora. El centro indicará su nombre y apellidos, DNI o NIE y correo.</p>
		</fieldset>
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
	 * «Qué centros pueden solicitar»: tipos, islas y una lista cerrada de códigos.
	 *
	 * Las casillas son los tipos y las islas que trae el catálogo, más los ya
	 * elegidos aunque el catálogo los haya perdido. Ninguna marcada es «todos»
	 * (ADR-0042).
	 *
	 * @param array<string, mixed> $m Model.
	 * @param array<string, mixed> $v Field values.
	 * @return string
	 */
	private static function eligibility_fields( array $m, array $v ): string {
		$opciones = (array) ( $m['centre_options'] ?? array() );
		$listas   = array(
			ProcedureMetaKeys::CENTRE_TYPES => array( 'Tipos de centro', (array) ( $opciones['types'] ?? array() ), 'Ninguno marcado: todos los tipos.' ),
			ProcedureMetaKeys::ISLANDS      => array( 'Islas', (array) ( $opciones['islands'] ?? array() ), 'Ninguna marcada: todas.' ),
		);

		$elegidos = array();
		foreach ( array_keys( $listas ) as $nombre ) {
			$elegidos[ $nombre ] = array_map( array( CentreEligibility::class, 'fold' ), array_map( 'strval', (array) $v[ $nombre ] ) );
		}

		ob_start();
		?>
		<div class="prc-seccion__cuerpo" data-prc-schools-only <?php echo ProcedureMetaKeys::AUDIENCE_SCHOOLS === $v[ ProcedureMetaKeys::AUDIENCE ] ? '' : 'hidden'; ?>>
			<div class="prc-campo prc-campo--completo">
				<p class="prc-campo__ayuda">Sin nada marcado ni escrito, puede solicitar cualquier centro, también uno que todavía no esté en el catálogo. Con tipos o islas, solo los centros del catálogo que los cumplan; con la lista cerrada sola, los de la lista, estén o no en el catálogo. Los límites se miran al presentar una solicitud nueva: un centro que ya la presentó la sigue editando o subsanando cuando el plazo lo permita, aunque el procedimiento se acote después.</p>
				<?php echo self::centre_warning( $m, 'catalogue' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			</div>
			<?php foreach ( $listas as $nombre => $lista ) : ?>
				<div class="prc-campo prc-campo--medio">
					<fieldset>
						<legend class="prc-campo__etiqueta"><?php echo esc_html( $lista[0] ); ?></legend>
						<?php if ( array() === $lista[1] ) : ?>
							<p class="prc-campo__ayuda">El catálogo de centros no trae ninguno.</p>
						<?php endif; ?>
						<div class="prc-opciones prc-opciones--vertical">
							<?php foreach ( $lista[1] as $i => $valor ) : ?>
								<div class="prc-opcion">
									<input type="checkbox" id="<?php echo esc_attr( $nombre . '-' . $i ); ?>"
										name="<?php echo esc_attr( $nombre ); ?>[]" value="<?php echo esc_attr( (string) $valor ); ?>"
										<?php checked( in_array( CentreEligibility::fold( (string) $valor ), $elegidos[ $nombre ], true ) ); ?> />
									<label for="<?php echo esc_attr( $nombre . '-' . $i ); ?>"><?php echo esc_html( (string) $valor ); ?></label>
								</div>
							<?php endforeach; ?>
						</div>
						<p class="prc-campo__ayuda"><?php echo esc_html( $lista[2] ); ?></p>
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
	 * One warning about the centre rules, when the model has it.
	 *
	 * @param array<string, mixed> $m   Model.
	 * @param string               $key `ownership` or `catalogue`.
	 * @return string
	 */
	private static function centre_warning( array $m, string $key ): string {
		$texto = (string) ( $m['centre_warnings'][ $key ] ?? '' );
		return '' === $texto ? '' : '<p class="prc-aviso prc-aviso-warning prc-aviso-centros" role="status">' . esc_html( $texto ) . '</p>';
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
	 * «Subir» y «bajar» son botones de otro formulario, el de mover, que va
	 * fuera de este (`form="prc-q-mover"`): mueven una pregunta por POST y
	 * nada más (ADR-0040).
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
				«Subir» y «Bajar» cambian el orden al momento: guarde antes lo que haya escrito.
			</p>
			<?php echo PanelParts::help_link( (string) ( $m['help_questions_url'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>

			<?php foreach ( $filas as $i => $pregunta ) : ?>
				<?php echo self::question_row( (int) $i, (array) $pregunta, (array) $m['q_types'], count( (array) $m['questions'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			<?php endforeach; ?>

			<?php echo PanelParts::submit( 'Guardar las preguntas' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
		</form>
		<form id="prc-q-mover" method="post" action="">
			<?php wp_nonce_field( ProcedureEditor::nonce_action( ProcedureEditor::OP_MOVE_QUESTION ), ProcedureEditor::nonce_name( ProcedureEditor::OP_MOVE_QUESTION ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( ProcedureEditor::OP_MOVE_QUESTION ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The «Subir» and «Bajar» buttons of one saved question.
	 *
	 * @param int                  $i     Row index.
	 * @param array<string, mixed> $p     Question.
	 * @param int                  $total Saved questions.
	 * @return string
	 */
	private static function move_buttons( int $i, array $p, int $total ): string {
		$botones = array(
			QuestionChanges::MOVE_UP   => array( 'Subir', 0 === $i ),
			QuestionChanges::MOVE_DOWN => array( 'Bajar', $i >= $total - 1 ),
		);
		$out     = '';
		foreach ( $botones as $hacia => $boton ) {
			$out .= sprintf(
				'<button type="submit" class="%1$s" form="prc-q-mover" name="%2$s" value="%3$s" aria-label="%4$s"%5$s>%6$s</button> ',
				esc_attr( Assets::button_class( true ) ),
				esc_attr( ProcedureEditor::FIELD_Q_MOVE ),
				esc_attr( (string) $p['key'] . ':' . $hacia ),
				esc_attr( $boton[0] . ' la pregunta ' . ( $i + 1 ) ),
				$boton[1] ? ' disabled' : '',
				esc_html( $boton[0] )
			);
		}
		return '<div class="prc-campo prc-pregunta__orden">' . $out . '</div>';
	}

	/**
	 * The fields only some types use: the range of a number and the rows of a grid.
	 *
	 * @param int                  $i Row index.
	 * @param array<string, mixed> $p Question.
	 * @return string
	 */
	private static function type_fields( int $i, array $p ): string {
		$campo = ProcedureEditor::FIELD_Q;
		$filas = implode( "\n", array_map( 'strval', wp_list_pluck( (array) ( $p['rows'] ?? array() ), 'label' ) ) );

		ob_start();
		?>
		<div class="prc-seccion__cuerpo">
			<?php
			foreach ( array(
				'min' => 'Mínimo',
				'max' => 'Máximo',
			) as $limite => $rotulo ) :
				?>
				<div class="prc-campo prc-campo--medio">
					<label class="prc-campo__etiqueta" for="prc-q-<?php echo esc_attr( $limite . '-' . $i ); ?>"><?php echo esc_html( $rotulo ); ?></label>
					<input class="prc-control" type="number" step="1" id="prc-q-<?php echo esc_attr( $limite . '-' . $i ); ?>" name="<?php echo esc_attr( $campo . $limite ); ?>[<?php echo esc_attr( (string) $i ); ?>]"
						value="<?php echo esc_attr( (string) ( $p[ $limite ] ?? '' ) ); ?>" aria-describedby="prc-q-n-help-<?php echo esc_attr( (string) $i ); ?>" />
				</div>
			<?php endforeach; ?>
		</div>
		<p class="prc-campo__ayuda" id="prc-q-n-help-<?php echo esc_attr( (string) $i ); ?>">Solo para «Número»: un número entero. Sin mínimo, se admite desde 0; sin máximo, no hay tope.</p>
		<div class="prc-campo">
			<label class="prc-campo__etiqueta" for="prc-q-rows-<?php echo esc_attr( (string) $i ); ?>">Filas, una por línea</label>
			<textarea class="prc-control" id="prc-q-rows-<?php echo esc_attr( (string) $i ); ?>" name="<?php echo esc_attr( $campo . 'rows' ); ?>[<?php echo esc_attr( (string) $i ); ?>]" rows="3"
				aria-describedby="prc-q-rows-help-<?php echo esc_attr( (string) $i ); ?>"><?php echo esc_textarea( $filas ); ?></textarea>
			<p class="prc-campo__ayuda" id="prc-q-rows-help-<?php echo esc_attr( (string) $i ); ?>">Solo para «Cantidades por fila»: hasta <?php echo esc_html( (string) ProcedureQuestions::MAX_ROWS ); ?> filas, y en cada una se pide una cantidad. Para cambiar el nombre de una fila, escríbalo en su misma línea; para quitarla, deje su línea en blanco.</p>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * One question row.
	 *
	 * @param int                   $i     Row index.
	 * @param array<string, mixed>  $p     Question.
	 * @param array<string, string> $tipos Question types.
	 * @param int                   $total Saved questions, to know the last one.
	 * @return string
	 */
	private static function question_row( int $i, array $p, array $tipos, int $total ): string {
		$nueva  = '' === (string) $p['key'];
		$campo  = ProcedureEditor::FIELD_Q;
		$rotulo = $nueva ? 'Pregunta nueva' : 'Pregunta ' . ( $i + 1 );

		ob_start();
		?>
		<fieldset class="prc-seccion-campos prc-pregunta">
			<legend class="prc-seccion__titulo"><?php echo esc_html( $rotulo ); ?></legend>
			<?php echo $nueva ? '' : self::move_buttons( $i, $p, $total ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
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
				<textarea class="prc-control" id="prc-q-c-<?php echo esc_attr( (string) $i ); ?>" name="<?php echo esc_attr( $campo . 'choices' ); ?>[<?php echo esc_attr( (string) $i ); ?>]" rows="3"
					aria-describedby="prc-q-c-help-<?php echo esc_attr( (string) $i ); ?>"><?php echo esc_textarea( implode( "\n", QuestionPoints::lines( $p ) ) ); ?></textarea>
				<p class="prc-campo__ayuda" id="prc-q-c-help-<?php echo esc_attr( (string) $i ); ?>">Solo para «Una opción» y «Varias opciones». En «Varias opciones», una opción escrita «Texto | 3» vale 3 puntos y quien solicita solo ve «Texto»; la solicitud suma los puntos de lo que marque.</p>
			</div>
			<?php echo self::type_fields( $i, $p ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			<div class="prc-campo prc-opcion">
				<input type="checkbox" id="prc-q-d-<?php echo esc_attr( (string) $i ); ?>" name="<?php echo esc_attr( $campo . 'delivery' ); ?>[<?php echo esc_attr( (string) $i ); ?>]" value="1" <?php checked( ProcedureQuestions::is_delivery( $p ) ); ?> aria-describedby="prc-q-d-help-<?php echo esc_attr( (string) $i ); ?>" />
				<label for="prc-q-d-<?php echo esc_attr( (string) $i ); ?>">Entrega con plazo propio (solo para «Archivo»)</label>
				<p class="prc-campo__ayuda" id="prc-q-d-help-<?php echo esc_attr( (string) $i ); ?>">Se entrega por separado después de presentar la solicitud. Puede seguir abierta tras la inscripción o el listado definitivo. Configure sus fechas antes de cerrar las preguntas.</p>
			</div>
			<?php
			foreach ( array(
				'opens_at'  => 'Apertura de la entrega',
				'closes_at' => 'Cierre de la entrega (inclusive)',
			) as $date_key => $date_label ) :
				?>
				<div class="prc-campo">
					<label for="prc-q-<?php echo esc_attr( $date_key . '-' . $i ); ?>"><?php echo esc_html( $date_label ); ?></label>
					<input class="prc-control" type="date" id="prc-q-<?php echo esc_attr( $date_key . '-' . $i ); ?>" name="<?php echo esc_attr( $campo . $date_key ); ?>[<?php echo esc_attr( (string) $i ); ?>]" value="<?php echo esc_attr( (string) ( $p[ $date_key ] ?? '' ) ); ?>" aria-describedby="prc-q-d-help-<?php echo esc_attr( (string) $i ); ?>" />
				</div>
			<?php endforeach; ?>
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

	// ─── Publicación ───────────────────────────────────────────────────────

	/**
	 * The «Publicación» panel: publish, and what the state means.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function publish_panel( array $m ): string {
		$op                 = ProcedureEditor::OP_PUBLISH;
		$state              = (string) $m['state'];
		$close_confirmation = sprintf(
			'¿Cerrar la inscripción de «%s» ahora? No se aceptarán nuevas solicitudes; '
				. 'se mantienen los plazos de subsanación y entrega documental que ya estén abiertos.',
			(string) $m['title']
		);
		$draft_confirmation = sprintf(
			'¿Devolver «%s» a borrador? Dejará de verse públicamente y no se podrá solicitar '
				. 'hasta que vuelva a publicarse.',
			(string) $m['title']
		);
		$stage              = 1;
		if ( ProcedureMetaKeys::STATE_DRAFT === $state ) {
			$stage = 0;
		} elseif ( ProcedureMetaKeys::STATE_ARCHIVED === $state ) {
			$stage = 4;
		} elseif ( ProcedureMetaKeys::STATE_RESOLVED === $state ) {
			$stage = 3;
		} elseif ( ProcedureMetaKeys::STATE_CLOSED === $state ) {
			$stage = $m['pending_documentation'] ? 2 : 3;
		}
		$steps = array(
			array( 'Borrador', 'Solo quienes gestionan el procedimiento pueden verlo; los centros aún no pueden solicitar.' ),
			array( 'Publicado', 'La ficha se muestra públicamente. Los centros pueden solicitar cuando se abre el plazo.' ),
			array(
				'Pendiente de documentación',
				'Las solicitudes están cerradas y los centros ya inscritos aún pueden '
					. 'entregar documentos dentro de su plazo.',
			),
			array(
				'Cerrado',
				'No se admiten solicitudes. Cuando ya no quede nada que gestionar, '
					. 'puede marcar el procedimiento como histórico.',
			),
			array( 'Histórico', 'Se conserva para consulta y exportación; el procedimiento queda en solo lectura.' ),
		);

		ob_start();
		?>
		<section class="prc-seccion-campos">
			<h2 class="prc-seccion__titulo">Estado</h2>
			<ol class="prc-ciclo-publicacion" aria-label="Etapas del procedimiento">
				<?php foreach ( $steps as $index => $step ) : ?>
					<li class="prc-ciclo-publicacion__paso<?php echo $index < $stage ? ' prc-ciclo-publicacion__paso--completado' : ( $index === $stage ? ' prc-ciclo-publicacion__paso--actual' : '' ); ?>"<?php echo $index === $stage ? ' aria-current="step"' : ''; ?>>
						<h3><?php echo esc_html( $step[0] ); ?>
						<?php
						if ( $index === $stage ) :
							?>
							<span class="prc-ciclo-publicacion__actual">Etapa actual</span><?php endif; ?></h3>
						<p><?php echo esc_html( $step[1] ); ?></p>
					</li>
				<?php endforeach; ?>
			</ol>
			<p class="prc-intro">
				<?php echo self::badge( (string) $m['state'], (string) $m['state_label'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				El estado sale de las fechas y los enlaces; no hay que marcarlo a mano. Con el listado definitivo enlazado pasa a «Resuelto».
				Si necesita cerrar las solicitudes antes, puede hacerlo aquí sin cambiar esos datos ni los plazos de entrega documental.
			</p>
			<?php if ( true === $m['can_publish'] ) : ?>
				<p class="prc-intro">En borrador no se ve fuera y ningún centro puede solicitar. Al publicarlo sale en la portada,
					y los centros solicitan cuando abra el plazo.</p>
				<form class="prc-acciones" method="post" action=""
					data-prc-confirm="<?php echo esc_attr( sprintf( '¿Publicar «%s»? Pasará a verse en la portada.', (string) $m['title'] ) ); ?>"
					data-prc-confirm-ok="Publicar">
					<?php wp_nonce_field( ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op ), false ); ?>
					<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
					<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
					<button class="<?php echo esc_attr( Assets::button_class( true ) . ' prc-envio' ); ?>" type="submit">Publicar el procedimiento</button>
				</form>
			<?php elseif ( 'publish' === (string) $m['status'] ) : ?>
				<p class="prc-intro">Publicado: se ve en la portada y en su ficha. Si necesita retirarlo temporalmente,
					puede devolverlo a borrador.</p>
			<?php endif; ?>
			<?php if ( true === $m['can_toggle_applications'] && 'publish' === (string) $m['status'] ) : ?>
				<p class="prc-intro">
					<?php if ( true === $m['applications_closed'] ) : ?>
						La inscripción está cerrada manualmente. Se mantienen los plazos de subsanación y de entrega
						de documentos ya solicitados.
					<?php else : ?>
						<?php if ( true === $m['pending_documentation'] ) : ?>
							Hay documentos pendientes de entrega. Cerrar solicitudes no cerrará esos plazos.
						<?php else : ?>
							Las solicitudes se abren y cierran según las fechas configuradas.
						<?php endif; ?>
					<?php endif; ?>
				</p>
				<?php $toggle = ProcedureEditor::OP_TOGGLE_APPLICATIONS; ?>
				<form class="prc-acciones" method="post" action=""
					<?php if ( false === $m['applications_closed'] ) : ?>
						data-prc-confirm="<?php echo esc_attr( $close_confirmation ); ?>"
						data-prc-confirm-ok="Cerrar solicitudes"
					<?php endif; ?>
				>
					<?php wp_nonce_field( ProcedureEditor::nonce_action( $toggle ), ProcedureEditor::nonce_name( $toggle ), false ); ?>
					<input type="hidden"
						name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>"
						value="<?php echo esc_attr( $toggle ); ?>" />
					<input type="hidden"
						name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>"
						value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
					<button class="<?php echo esc_attr( Assets::button_class() ); ?>" type="submit">
						<?php echo true === $m['applications_closed'] ? 'Volver al calendario automático' : 'Cerrar solicitudes ahora'; ?>
					</button>
				</form>
			<?php endif; ?>
			<?php if ( true === $m['can_unpublish'] ) : ?>
				<?php $op_borrador = ProcedureEditor::OP_UNPUBLISH; ?>
				<form class="prc-acciones" method="post" action=""
					data-prc-confirm="<?php echo esc_attr( $draft_confirmation ); ?>"
					data-prc-confirm-ok="Devolver a borrador">
					<?php wp_nonce_field( ProcedureEditor::nonce_action( $op_borrador ), ProcedureEditor::nonce_name( $op_borrador ), false ); ?>
					<input type="hidden"
						name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>"
						value="<?php echo esc_attr( $op_borrador ); ?>" />
					<input type="hidden"
						name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>"
						value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
					<button class="<?php echo esc_attr( Assets::button_class() ); ?>" type="submit">Devolver a borrador</button>
				</form>
			<?php endif; ?>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The «Avisos» panel: one checkbox per mail this procedure may send.
	 *
	 * Marcada es encendida. Lo que no se marque no sale para este
	 * procedimiento; el correo del alta no está porque ya salió (ADR-0038).
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function notices_panel( array $m ): string {
		$op       = ProcedureEditor::OP_NOTIFY;
		$apagados = (array) ( $m['values'][ ProcedureMetaKeys::NOTIFY ] ?? array() );
		ob_start();
		?>
		<form class="prc-form prc-form--taller" method="post" action="">
			<?php wp_nonce_field( ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
			<fieldset class="prc-seccion-campos">
				<legend class="prc-seccion__titulo">Avisos por correo</legend>
				<p class="prc-intro">Los correos que manda el aplicativo para este procedimiento. Desmarque los que no quiera enviar; el resto sale solo, en texto plano y desde el remitente que configure administración.</p>
				<div class="prc-opciones prc-opciones--vertical">
					<?php foreach ( (array) $m['notify_events'] as $evento => $rotulo ) : ?>
						<div class="prc-opcion">
							<input type="checkbox" id="prc-notify-<?php echo esc_attr( (string) $evento ); ?>"
								name="<?php echo esc_attr( ProcedureEditor::FIELD_NOTIFY ); ?>[]" value="<?php echo esc_attr( (string) $evento ); ?>"
								<?php checked( ! in_array( (string) $evento, $apagados, true ) ); ?> />
							<label for="prc-notify-<?php echo esc_attr( (string) $evento ); ?>"><?php echo esc_html( (string) $rotulo ); ?></label>
						</div>
					<?php endforeach; ?>
				</div>
			</fieldset>
			<?php echo PanelParts::submit( 'Guardar los avisos' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * CSS for managers and executable JavaScript for trusted administrators.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function custom_code_panel( array $m ): string {
		$op = ProcedureEditor::OP_CUSTOM_CODE;
		ob_start();
		?>
		<section class="prc-seccion-campos">
			<h2 class="prc-seccion__titulo">Código adicional</h2>
			<p class="prc-intro">El CSS se aplica solo a la ficha pública de este procedimiento.</p>
			<form class="prc-form prc-form--taller" method="post" action="">
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
				<button class="<?php echo esc_attr( Assets::button_class( true ) . ' prc-envio' ); ?>" type="submit"<?php disabled( empty( $m['can_edit'] ) ); ?>>Guardar código</button>
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
	public static function badge( string $slug, string $label ): string {
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
