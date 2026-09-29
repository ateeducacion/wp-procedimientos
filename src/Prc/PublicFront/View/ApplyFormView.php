<?php
/**
 * The application screen of a school, painted from what ApplyForm::model() decided.
 *
 * @package Prc
 */

namespace Prc\PublicFront\View;

use Prc\Domain\DateRange;
use Prc\Domain\ProcedureQuestions;
use Prc\Meta\ApplicationMetaKeys;
use Prc\PublicFront\ApplicationFiles;
use Prc\PublicFront\ApplyForm;
use Prc\PublicFront\Assets;
use Prc\PublicFront\Shell;

/**
 * Solo pinta: no lee la petición, no consulta, no decide.
 *
 * El núcleo de la solicitud —centro, cargo, coordinación, compromisos— es
 * siempre el mismo y va en código (ADR-0019); lo que cambia de un
 * procedimiento a otro son sus preguntas, que se pintan por tipo. El centro
 * sale de solo lectura: se solicita **por** el centro de la persona y no por
 * el que se teclee.
 *
 * La pantalla es el calco de la que se sustituye: cabecera de contexto en vez
 * de los campos técnicos de la convocatoria, secciones con su rótulo en
 * mayúsculas y su filete, retícula de doce columnas y campos bloqueados que se
 * distinguen por el color y no por un fondo gris. Lo que no se calca es el
 * texto del aviso de protección de datos: ese es de quien despliega
 * ({@see ProcedureChrome::chrome()}, clave `privacy_notice`) y sin configurar
 * no se pinta (ADR-0009).
 */
final class ApplyFormView {

	/**
	 * Width modifiers of the twelve-column grid, by how many columns they span.
	 *
	 * @var array<int, string>
	 */
	private const ANCHO = array(
		3  => 'prc-campo--cuarto',
		4  => 'prc-campo--tercio',
		6  => 'prc-campo--medio',
		12 => 'prc-campo--completo',
	);

	/**
	 * Tone of the notice strip, by review state.
	 *
	 * @var array<string, string>
	 */
	private const REVISION_TONO = array(
		ApplicationMetaKeys::REVIEW_SUBMITTED => 'info',
		ApplicationMetaKeys::REVIEW_AMEND     => 'warning',
		ApplicationMetaKeys::REVIEW_ADMITTED  => 'success',
		ApplicationMetaKeys::REVIEW_EXCLUDED  => 'danger',
	);

	/**
	 * Icon of the notice strip, by review state.
	 *
	 * @var array<string, string>
	 */
	private const REVISION_ICONO = array(
		ApplicationMetaKeys::REVIEW_SUBMITTED => 'description',
		ApplicationMetaKeys::REVIEW_AMEND     => 'edit_note',
		ApplicationMetaKeys::REVIEW_ADMITTED  => 'task_alt',
		ApplicationMetaKeys::REVIEW_EXCLUDED  => 'error',
	);

	/**
	 * Paint the screen.
	 *
	 * @param array<string, mixed> $m What ApplyForm::model() returned.
	 * @return string
	 */
	public static function html( array $m ): string {
		if ( '' !== (string) $m['aviso'] ) {
			return Shell::render( 'Solicitud', '', Shell::notice( 'aviso', (string) $m['aviso'] ) . self::mine_link( $m ) );
		}

		$flash = (array) $m['flash'];

		ob_start();
		echo self::head( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		echo Shell::notice( (string) $flash['tipo'], (string) $flash['texto'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		echo self::status_card( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.

		if ( true === $m['can_submit'] ) {
			echo self::form( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		} else {
			echo Shell::notice( 'aviso', (string) $m['why'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
			echo self::summary( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		}
		return Shell::render( '', '', (string) ob_get_clean() );
	}

	/**
	 * The procedure: its name, its state and its dates.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function head( array $m ): string {
		$plazo       = DateRange::of( (string) $m['opens_at'], (string) $m['closes_at'] );
		$subsanacion = DateRange::of( (string) $m['amend_opens_at'], (string) $m['amend_closes_at'] );

		ob_start();
		?>
		<p class="prc-sub"><a href="<?php echo esc_url( (string) $m['mine_url'] ); ?>">&larr; Mi centro</a></p>
		<div class="prc-h1-fila">
			<h1 class="prc-h1"><?php echo esc_html( (string) $m['title'] ); ?></h1>
			<span class="<?php echo esc_attr( Assets::state_class( (string) $m['state'] ) ); ?>"><?php echo esc_html( (string) $m['state_label'] ); ?></span>
			<span class="prc-acciones">
				<?php echo PanelParts::icon_link( (string) $m['view_url'], 'ojo', 'Ver la ficha pública' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			</span>
		</div>
		<?php if ( '' !== $plazo ) : ?>
			<p class="prc-sub"><?php echo esc_html( 'Plazo de solicitud: ' . lcfirst( $plazo ) ); ?></p>
		<?php endif; ?>
		<?php if ( '' !== $subsanacion ) : ?>
			<p class="prc-sub"><?php echo esc_html( 'Plazo de subsanación: ' . lcfirst( $subsanacion ) ); ?></p>
		<?php endif; ?>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The application as it stands: when, in what review state, and the note.
	 *
	 * Una tira sobre el formulario, con el tono y el icono del estado de
	 * revisión: quien tiene que subsanar lee el motivo encima del formulario
	 * que tiene que corregir, y no en un correo.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function status_card( array $m ): string {
		$s = (array) $m['application'];
		if ( array() === $s ) {
			return '';
		}

		$estado = (string) $s['state'];
		$tono   = self::REVISION_TONO[ $estado ] ?? 'info';
		$icono  = self::REVISION_ICONO[ $estado ] ?? 'description';
		$rotulo = (string) $s['state_label'];

		if ( ApplicationMetaKeys::REVIEW_AMEND === $estado && '' !== (string) $m['amend_closes_at'] ) {
			$rotulo .= ' · hasta el ' . PanelParts::day( (string) $m['amend_closes_at'] );
		}

		ob_start();
		?>
		<div class="prc-solicitud-estado <?php echo esc_attr( Assets::alert_class( $tono ) ); ?>" role="status">
			<span class="material-symbols-outlined" aria-hidden="true"><?php echo esc_html( $icono ); ?></span>
			<span class="prc-solicitud-estado__cuerpo">
				<span class="prc-solicitud-estado__estado">Su solicitud: <?php echo esc_html( $rotulo ); ?></span>
				<span class="prc-solicitud-estado__fecha"><?php echo esc_html( 'Presentada el ' . (string) $s['date'] . '.' ); ?></span>
				<?php if ( '' !== (string) $s['note'] ) : ?>
					<span class="prc-solicitud-estado__nota"><?php echo esc_html( (string) $s['note'] ); ?></span>
				<?php endif; ?>
				<?php if ( '' !== (string) $m['final_list_url'] ) : ?>
					<span class="prc-solicitud-estado__enlace"><a href="<?php echo esc_url( (string) $m['final_list_url'] ); ?>" rel="noopener">Listado definitivo de admitidos</a></span>
				<?php endif; ?>
			</span>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The data protection notice of whoever deploys, when there is one.
	 *
	 * **Aquí no hay ni una línea de texto legal**: el órgano responsable, el
	 * tratamiento, el enlace a la resolución y el contacto de protección de
	 * datos son del despliegue y llegan por el filtro del armazón (ADR-0009).
	 * Sin configurar, no se pinta nada.
	 *
	 * @return string
	 */
	private static function privacy(): string {
		$texto = trim( (string) ( ProcedureChrome::chrome()['privacy_notice'] ?? '' ) );
		if ( '' === $texto ) {
			return '';
		}
		return '<div class="prc-aviso-legal">' . wp_kses_post( $texto ) . '</div>';
	}

	/**
	 * The form: the fixed core, the questions and the commitments.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function form( array $m ): string {
		$v      = (array) $m['values'];
		$spec   = (array) $m['spec'];
		$centro = (array) $m['centre'];
		$quien  = (array) ( $m['applicant'] ?? array() );
		$nueva  = array() === (array) $m['application'];

		// Los campos bloqueados se arman antes: dentro del `echo` de la
		// plantilla, el escape ya no se ve desde fuera.
		$del_centro = self::locked(
			'prc-centre-code',
			'Código',
			(string) $centro['code'],
			3,
			'El centro es el de su cuenta: no se puede cambiar desde aquí.'
		) . self::locked(
			'prc-centre',
			'Denominación',
			(string) $centro['name'],
			6,
			'' === (string) $centro['name'] ? 'Su código no está en el catálogo; se solicita igual.' : ''
		);
		$de_quien   = self::locked(
			'prc-applicant',
			'Nombre y apellidos',
			(string) ( $quien['name'] ?? '' ),
			6
		) . self::locked(
			'prc-applicant-email',
			'Correo electrónico',
			(string) ( $quien['email'] ?? '' ),
			6,
			'Se lee de su cuenta: para cambiarlo, cambie el correo de su perfil.'
		);

		// Los documentos ya presentados, con su solicitud, para poder ofrecer la
		// descarga junto al campo y para no pedir otra vez uno obligatorio.
		$subidos = array();
		foreach ( (array) ( $m['application']['files'] ?? array() ) as $clave => $descriptor ) {
			if ( is_array( $descriptor ) ) {
				$descriptor['application']  = (int) ( $m['application']['id'] ?? 0 );
				$subidos[ (string) $clave ] = $descriptor;
			}
		}

		ob_start();
		?>
		<?php // `multipart/form-data` solo porque ahora puede llevar un documento (ADR-0028): sube como fichero HTTP normal, sin Base64 y sin JavaScript. ?>
		<form class="prc-form prc-form--solicitud" method="post" action="" enctype="multipart/form-data">
			<?php wp_nonce_field( ApplyForm::NONCE_ACTION, ApplyForm::NONCE_FIELD, false ); ?>
			<input type="hidden" name="<?php echo esc_attr( ApplyForm::FIELD_OP ); ?>" value="<?php echo esc_attr( ApplyForm::OP_APPLY ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( ApplyForm::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />

			<?php echo self::privacy(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>

			<section class="prc-seccion">
				<h3 class="prc-seccion__titulo">DATOS DEL CENTRO EDUCATIVO</h3>
				<div class="prc-seccion__cuerpo">
					<?php echo $del_centro; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				</div>
			</section>

			<section class="prc-seccion">
				<h3 class="prc-seccion__titulo">DATOS PERSONALES DE LA DIRECCIÓN DEL CENTRO</h3>
				<div class="prc-seccion__cuerpo">
					<?php echo $de_quien; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
					<div class="prc-campo prc-campo--cuarto prc-campo--abre-fila">
						<?php echo self::label( 'prc-position', 'Cargo en el centro', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
						<select class="prc-control" id="prc-position" name="<?php echo esc_attr( ApplyForm::FIELD_POSITION ); ?>" required>
							<option value="">— Elija —</option>
							<?php foreach ( (array) $m['positions'] as $clave => $rotulo ) : ?>
								<option value="<?php echo esc_attr( (string) $clave ); ?>" <?php selected( (string) $clave, (string) $v['position'] ); ?>><?php echo esc_html( (string) $rotulo ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
			</section>

			<?php if ( '' !== trim( (string) $spec['commitments'] ) ) : ?>
				<section class="prc-seccion">
					<h3 class="prc-seccion__titulo">SOLICITA</h3>
					<div class="prc-seccion__cuerpo">
						<div class="prc-nota prc-campo--completo">
							<p>La participación de su centro en la <em>presente convocatoria</em>, comprometiéndose a cumplir los siguientes compromisos:</p>
							<p><?php echo nl2br( esc_html( (string) $spec['commitments'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapado antes de nl2br(). ?></p>
						</div>
						<div class="prc-campo prc-campo--completo">
							<div class="prc-opciones prc-opciones--vertical">
								<div class="prc-opcion">
									<label for="prc-accept">
										<input type="checkbox" id="prc-accept" name="<?php echo esc_attr( ApplyForm::FIELD_ACCEPT ); ?>" value="1" required <?php checked( (bool) $v['accept'] ); ?> />
										El centro acepta estos compromisos
									</label>
								</div>
							</div>
						</div>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( array() !== (array) $spec['questions'] ) : ?>
				<section class="prc-seccion prc-seccion--sin-titulo" aria-labelledby="prc-preguntas">
					<h3 class="screen-reader-text" id="prc-preguntas">Preguntas del procedimiento</h3>
					<div class="prc-seccion__cuerpo">
						<?php foreach ( (array) $spec['questions'] as $pregunta ) : ?>
							<?php echo self::question( (array) $pregunta, (array) $v['answers'], $subidos ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $spec['requires_coordinator'] ) ) : ?>
				<section class="prc-seccion">
					<h3 class="prc-seccion__titulo">DATOS DE LA COORDINACIÓN</h3>
					<div class="prc-seccion__cuerpo">
						<div class="prc-nota prc-campo--completo">
							<p>La persona coordinadora acepta el compromiso de colaborar en el desarrollo del programa, proyecto o red tal como se expresa en la convocatoria del mismo.</p>
							<p><strong>La persona coordinadora es:</strong></p>
						</div>
						<div class="prc-campo prc-campo--medio prc-campo--abre-fila">
							<?php echo self::label( 'prc-coordinator-name', 'Nombre y apellidos', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
							<input class="prc-control" type="text" id="prc-coordinator-name" name="<?php echo esc_attr( ApplyForm::FIELD_COORD_NAME ); ?>" required
								value="<?php echo esc_attr( (string) $v['coordinator_name'] ); ?>" />
						</div>
						<div class="prc-campo prc-campo--cuarto">
							<?php echo self::label( 'prc-coordinator-email', 'Correo electrónico', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
							<input class="prc-control" type="email" id="prc-coordinator-email" name="<?php echo esc_attr( ApplyForm::FIELD_COORD_EMAIL ); ?>" required
								value="<?php echo esc_attr( (string) $v['coordinator_email'] ); ?>" />
						</div>
					</div>
				</section>
			<?php endif; ?>

			<div class="prc-form__envio">
				<button class="prc-boton-enviar" type="submit">
					<span class="material-symbols-outlined" aria-hidden="true">send</span>
					<?php echo esc_html( $nueva ? 'Presentar la solicitud' : 'Guardar los cambios' ); ?>
				</button>
			</div>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The label of a field, with its asterisk when it is required.
	 *
	 * El asterisco es decorativo: la obligatoriedad la dice `required`, y el
	 * lector de pantalla la lee de ahí.
	 *
	 * @param string $id          Id of the control it names.
	 * @param string $rotulo      Label text.
	 * @param bool   $obligatorio Whether the field is required.
	 * @param string $etiqueta    Tag to use: `label` or `p` for a group.
	 * @return string
	 */
	private static function label( string $id, string $rotulo, bool $obligatorio, string $etiqueta = 'label' ): string {
		$atributo = 'label' === $etiqueta ? ' for="' . esc_attr( $id ) . '"' : ' id="' . esc_attr( $id ) . '"';

		return '<' . $etiqueta . ' class="prc-campo__etiqueta"' . $atributo . '>'
			. esc_html( $rotulo )
			. ( $obligatorio ? ' <span class="prc-campo__obligatorio" aria-hidden="true">*</span>' : '' )
			. '</' . $etiqueta . '>';
	}

	/**
	 * A read-only field: what comes from the account or from the catalogue.
	 *
	 * Se distingue solo por el color —texto y borde aclarados, fondo blanco—,
	 * y sin dato escribe un guion, para que no se confunda «este centro no
	 * tiene municipio» con «esto no ha cargado». No lleva `name`: el valor lo
	 * resuelve el servidor y no viaja en el formulario.
	 *
	 * @param string $id       Control id.
	 * @param string $rotulo   Label.
	 * @param string $valor    Value, empty for none.
	 * @param int    $columnas Columns it spans: 3, 4, 6 or 12.
	 * @param string $ayuda    Help under the control.
	 * @return string
	 */
	private static function locked( string $id, string $rotulo, string $valor, int $columnas, string $ayuda = '' ): string {
		$ancho  = self::ANCHO[ $columnas ] ?? self::ANCHO[12];
		$ayudas = '' !== $ayuda ? ' aria-describedby="' . esc_attr( $id . '-ayuda' ) . '"' : '';

		return '<div class="prc-campo ' . esc_attr( $ancho ) . '">'
			. self::label( $id, $rotulo, false )
			. '<input class="prc-control prc-control--bloqueado" type="text" id="' . esc_attr( $id ) . '"'
			. ' value="' . esc_attr( '' !== $valor ? $valor : '—' ) . '" readonly' . $ayudas . ' />'
			. ( '' !== $ayuda
				? '<div class="prc-campo__ayuda" id="' . esc_attr( $id . '-ayuda' ) . '">' . esc_html( $ayuda ) . '</div>'
				: '' )
			. '</div>';
	}

	/**
	 * One question, painted by its type.
	 *
	 * Los cuatro bloques cableados del formulario que se sustituye —enunciado,
	 * indicaciones y caja de respuesta— y sus dos grupos de opciones son aquí
	 * preguntas de verdad (ADR-0019), pero se pintan igual que allí: enunciado
	 * a ancho completo, explicación debajo y caja de tres filas.
	 *
	 * @param array<string, mixed> $p       Normalised question.
	 * @param array<string, mixed> $answers Stored or typed answers, keyed by question key.
	 * @param array<string, mixed> $files   Documents already attached, keyed by question key.
	 * @return string
	 */
	private static function question( array $p, array $answers, array $files = array() ): string {
		$key    = (string) $p['key'];
		$subido = isset( $files[ $key ] ) && is_array( $files[ $key ] ) ? $files[ $key ] : array();
		$nombre = ApplyForm::FIELD_ANSWERS . '[' . $key . ']';
		$valor  = $answers[ $key ] ?? '';
		$tipo   = (string) $p['type'];
		$obliga = ! empty( $p['required'] );
		$ayuda  = (string) $p['help'];
		$id     = 'prc-q-' . $key;
		$pie    = '' !== $ayuda ? ' aria-describedby="' . esc_attr( $id . '-ayuda' ) . '"' : '';
		$ancho  = in_array( $tipo, array( ProcedureQuestions::TYPE_TEXT, ProcedureQuestions::TYPE_FILE ), true )
			? self::ANCHO[12]
			: self::ANCHO[6];

		ob_start();
		?>
		<div class="prc-campo prc-pregunta <?php echo esc_attr( $ancho ); ?>">
			<?php if ( ProcedureQuestions::TYPE_TEXT === $tipo ) : ?>
				<label class="prc-campo__etiqueta prc-campo__etiqueta--enunciado" for="<?php echo esc_attr( $id ); ?>">
					<strong><?php echo esc_html( (string) $p['label'] ); ?></strong>
					<?php if ( $obliga ) : ?>
						<span class="prc-campo__obligatorio" aria-hidden="true">*</span>
					<?php endif; ?>
				</label>
				<textarea id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $nombre ); ?>" class="prc-control" rows="3"
					placeholder="Escriba aquí"
					maxlength="<?php echo esc_attr( (string) ProcedureQuestions::TEXT_MAX ); ?>" <?php echo $obliga ? 'required' : ''; ?>
					<?php echo $pie; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>><?php echo esc_textarea( is_scalar( $valor ) ? (string) $valor : '' ); ?></textarea>
			<?php elseif ( ProcedureQuestions::TYPE_FILE === $tipo ) : ?>
				<?php echo self::label( $id, (string) $p['label'], $obliga ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				<?php if ( array() !== $subido ) : ?>
					<p class="prc-campo__adjunto">
						Ya adjuntó
						<a href="<?php echo esc_url( ApplicationFiles::url( (int) ( $subido['application'] ?? 0 ), (string) ( $subido['id'] ?? '' ) ) ); ?>" download><?php echo esc_html( (string) ( $subido['name'] ?? '' ) ); ?></a>.
						Adjunte otro solo si quiere sustituirlo.
					</p>
				<?php endif; ?>
				<input type="file" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( ApplicationFiles::FIELD . '[' . $key . ']' ); ?>"
					class="prc-control" accept="<?php echo esc_attr( implode( ',', array_values( ApplicationFiles::mimes() ) ) ); ?>"
					<?php echo $obliga && array() === $subido ? 'required' : ''; ?>
					<?php echo $pie; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?> />
				<div class="prc-campo__ayuda">
					Un solo documento, de hasta <?php echo esc_html( size_format( ApplicationFiles::max_bytes() ) ); ?>.
					Se admiten PDF, JPG, PNG, DOCX y ODT.
				</div>
			<?php elseif ( ProcedureQuestions::TYPE_YESNO === $tipo ) : ?>
				<div class="prc-opciones prc-opciones--vertical">
					<div class="prc-opcion">
						<label for="<?php echo esc_attr( $id ); ?>">
							<input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $nombre ); ?>" value="1"
								<?php checked( (bool) $valor ); ?> <?php echo $obliga ? 'required' : ''; ?>
								<?php echo $pie; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?> />
							<?php echo esc_html( (string) $p['label'] ); ?>
						</label>
					</div>
				</div>
			<?php else : ?>
				<?php
				$varias = ProcedureQuestions::TYPE_MULTIPLE === $tipo;
				$unica  = is_scalar( $valor ) ? (string) $valor : '';
				echo self::label( $id . '-rotulo', (string) $p['label'], $obliga, 'p' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
				?>
				<div class="prc-opciones prc-opciones--vertical" role="<?php echo $varias ? 'group' : 'radiogroup'; ?>"
					aria-labelledby="<?php echo esc_attr( $id . '-rotulo' ); ?>"
					<?php echo $pie; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>>
					<?php foreach ( (array) $p['choices'] as $i => $opcion ) : ?>
						<?php
						$marcada = $varias
							? in_array( (string) $opcion, array_map( 'strval', (array) $valor ), true )
							: $unica === (string) $opcion;
						?>
						<div class="prc-opcion">
							<label for="<?php echo esc_attr( $id . '-' . (string) $i ); ?>">
								<input type="<?php echo $varias ? 'checkbox' : 'radio'; ?>" id="<?php echo esc_attr( $id . '-' . (string) $i ); ?>"
									name="<?php echo esc_attr( $nombre . ( $varias ? '[]' : '' ) ); ?>"
									value="<?php echo esc_attr( (string) $opcion ); ?>" <?php checked( $marcada ); ?>
									<?php echo $obliga && ! $varias ? 'required' : ''; ?> />
								<?php echo esc_html( (string) $opcion ); ?>
							</label>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php if ( '' !== $ayuda ) : ?>
				<div class="prc-campo__ayuda" id="<?php echo esc_attr( $id . '-ayuda' ); ?>"><?php echo esc_html( $ayuda ); ?></div>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The document attached to one question, as a download link.
	 *
	 * El enlace se compone **aquí**, al pintar, y apunta al manejador del
	 * aplicativo, que vuelve a comprobar quién pregunta (ADR-0028).
	 *
	 * @param array<string, mixed> $application The application, as the model has it.
	 * @param string               $key         Question key.
	 * @return string
	 */
	private static function attached( array $application, string $key ): string {
		$ficheros = (array) ( $application['files'] ?? array() );
		$doc      = isset( $ficheros[ $key ] ) && is_array( $ficheros[ $key ] ) ? $ficheros[ $key ] : array();
		if ( array() === $doc ) {
			return '&mdash;';
		}
		return sprintf(
			'<a href="%1$s" download>%2$s</a>',
			esc_url( ApplicationFiles::url( (int) ( $application['id'] ?? 0 ), (string) ( $doc['id'] ?? '' ) ) ),
			esc_html( (string) ( $doc['name'] ?? '' ) )
		);
	}

	/**
	 * What was submitted, read-only, when it can no longer be edited.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function summary( array $m ): string {
		$s = (array) $m['application'];
		if ( array() === $s ) {
			return '';
		}
		$coord     = (array) $s['coordinator'];
		$preguntas = (array) ( $m['spec']['questions'] ?? array() );

		ob_start();
		?>
		<section class="prc-seccion prc-solicitud-resumen">
			<h3 class="prc-seccion__titulo">LO QUE PRESENTÓ</h3>
			<dl class="prc-respuestas">
				<dt>Cargo</dt>
				<dd><?php echo esc_html( (string) $s['position'] ); ?></dd>
				<?php if ( '' !== (string) ( $coord['name'] ?? '' ) ) : ?>
					<dt>Persona coordinadora</dt>
					<dd><?php echo esc_html( trim( (string) $coord['name'] . ' ' . (string) ( $coord['email'] ?? '' ) ) ); ?></dd>
				<?php endif; ?>
				<?php foreach ( $preguntas as $pregunta ) : ?>
					<dt><?php echo esc_html( (string) $pregunta['label'] ); ?></dt>
					<?php
					// Un documento se pinta como enlace de descarga; lo demás,
					// como texto. Lo decide el tipo de la pregunta (ADR-0028).
					$respuesta = ProcedureQuestions::TYPE_FILE === (string) $pregunta['type']
						? self::attached( $s, (string) $pregunta['key'] )
						: esc_html( ProcedureQuestions::as_text( (array) $pregunta, $s['answers'][ $pregunta['key'] ] ?? null ) );
					?>
					<dd><?php echo $respuesta; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado justo arriba. ?></dd>
				<?php endforeach; ?>
			</dl>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Way out when there is nothing to apply to.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function mine_link( array $m ): string {
		$url = (string) $m['mine_url'];
		if ( '' === $url ) {
			return '';
		}
		return '<p><a class="' . esc_attr( Assets::button_class( true ) ) . '" href="' . esc_url( $url ) . '">Las solicitudes de mi centro</a></p>';
	}
}
