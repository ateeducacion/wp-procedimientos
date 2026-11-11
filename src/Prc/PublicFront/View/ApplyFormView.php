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
use Prc\Meta\ApplicationMetaRegistration;
use Prc\PublicFront\ApplicationFiles;
use Prc\PublicFront\ApplicationProgress;
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
 * mayúsculas —las pone el CSS: el texto va en caja normal— y su filete, retícula de doce columnas y campos bloqueados que se
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
	 * Paint the screen.
	 *
	 * @param array<string, mixed> $m What ApplyForm::model() returned.
	 * @return string
	 */
	public static function html( array $m ): string {
		if ( '' !== (string) $m['aviso'] ) {
			return Shell::render( 'Solicitud', '', Shell::notice( 'aviso', (string) $m['aviso'] ) . self::next_steps( $m ) . self::mine_link( $m ) );
		}

		$progreso = (array) ( $m['progress'] ?? array() );
		if ( ! empty( $progreso['receipt'] ) ) {
			return ApplicationProgressView::receipt( $m );
		}

		ob_start();
		echo self::head( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		echo ApplicationProgressView::notice( (array) ( $progreso['flash'] ?? $m['flash'] ), (array) ( $m['invalid'] ?? array() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		echo self::choices( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		echo ApplicationProgressView::card( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		// La prórroga de esta solicitud, si la tiene (PLAN-0003, PR 9).
		echo Shell::notice( 'info', (string) ( $m['withdraw']['late'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		echo ApplicationProgressView::steps( $progreso ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.

		if ( true === $m['can_submit'] ) {
			echo self::form( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		} else {
			echo Shell::notice( 'aviso', (string) $m['why'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
			echo array() === (array) $m['application'] ? self::next_steps( $m ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
			echo self::summary( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		}
		// La lista de personas, con su propia ventana: fuera del formulario de
		// la solicitud, que puede estar cerrado mientras ella sigue abierta. Va
		// antes de las entregas: es el paso que más se olvida.
		echo PeopleView::section( (array) ( $m['people'] ?? array() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		if ( array() !== (array) ( $m['deliveries'] ?? array() ) ) {
			echo '<div id="' . esc_attr( ApplicationProgress::ANCHOR_DELIVERIES ) . '">';
			self::deliveries( $m );
			echo '</div>';
		}
		// Retirar la solicitud, al final: es lo último que se busca.
		echo ApplicationActionsView::withdraw( (array) ( $m['withdraw'] ?? array() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado.
		return Shell::render( '', '', (string) ob_get_clean() );
	}

	/**
	 * Paint independent document deliveries, including their saved download.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return void
	 */
	private static function deliveries( array $m ): void {
		foreach ( (array) ( $m['deliveries'] ?? array() ) as $question ) {
			$key   = (string) $question['key'];
			$dates = DateRange::of( (string) $question['opens_at'], (string) $question['closes_at'] );
			?>
			<section class="prc-seccion">
				<h2 class="prc-seccion__titulo"><?php echo esc_html( (string) $question['label'] ); ?></h2>
				<p><?php echo esc_html( '' !== $dates ? 'Plazo de entrega: ' . lcfirst( $dates ) . '.' : 'Plazo de entrega pendiente de configurar.' ); ?></p>
				<p>Documento presentado: <?php echo wp_kses_post( self::attached( (array) $m['application'], $key ) ); ?></p>
				<?php if ( ! empty( $question['can_submit'] ) ) : ?>
					<form class="prc-form" method="post" action="<?php echo esc_url( ApplyForm::url_for_code( (int) $m['procedure_id'], (string) $m['centre']['code'] ) ); ?>" enctype="multipart/form-data">
						<?php wp_nonce_field( ApplyForm::NONCE_ACTION, ApplyForm::NONCE_FIELD, false ); ?>
						<input type="hidden" name="<?php echo esc_attr( ApplyForm::FIELD_OP ); ?>" value="<?php echo esc_attr( ApplyForm::OP_DELIVER ); ?>" />
						<input type="hidden" name="<?php echo esc_attr( ApplyForm::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) $m['procedure_id'] ); ?>" />
						<input type="hidden" name="<?php echo esc_attr( ApplyForm::FIELD_CENTRE ); ?>" value="<?php echo esc_attr( (string) $m['centre']['code'] ); ?>" />
						<input type="hidden" name="<?php echo esc_attr( ApplyForm::FIELD_DELIVERY ); ?>" value="<?php echo esc_attr( $key ); ?>" />
						<?php
						$question['required'] = true;
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- question() escapa cada valor según su contexto.
						echo self::question( $question, array() );
						?>
						<p>Si ya entregó un documento, esta entrega lo sustituirá. La solicitud y su revisión se conservan.</p>
						<button type="submit" class="<?php echo esc_attr( Assets::button_class() ); ?>">Entregar documento</button>
					</form>
				<?php else : ?>
					<p>No se admiten entregas: hace falta una solicitud vigente, un plazo abierto y un procedimiento publicado sin archivar.</p>
				<?php endif; ?>
			</section>
			<?php
		}
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
		<p class="prc-sub"><a href="<?php echo esc_url( (string) $m['mine_url'] ); ?>">&larr; <?php echo esc_html( Shell::mine_label( ApplicationMetaKeys::TYPE_TEACHER === (string) $m['applicant_type'] ) ); ?></a></p>
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
		<?php echo PanelParts::help_link( (string) ( $m['help_url'] ?? '' ), '¿Dudas al rellenar?' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * What to do after a «no»: write to the contact, go back, switch account, read the help.
	 *
	 * El correo no se repite cuando el motivo ya lo dice. «Salir y entrar con
	 * otra cuenta» solo sale cuando el motivo es la cuenta: con el plazo
	 * cerrado, cambiar de cuenta no arregla nada.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function next_steps( array $m ): string {
		$correo = (string) ( $m['contact_email'] ?? '' );
		$motivo = (string) $m['why'] . ' ' . (string) $m['aviso'];
		$volver = (string) ( $m['view_url'] ?? '' );
		$cambio = (string) ( $m['switch_url'] ?? '' );

		ob_start();
		?>
		<div class="prc-siguiente">
			<?php if ( '' !== $correo && false === strpos( $motivo, $correo ) ) : ?>
				<p>¿Cree que es un error? Escriba a <a href="<?php echo esc_url( 'mailto:' . $correo ); ?>"><?php echo esc_html( $correo ); ?></a>.</p>
			<?php endif; ?>
			<?php if ( '' !== $volver || '' !== $cambio ) : ?>
				<p class="prc-acciones">
					<?php if ( '' !== $volver ) : ?>
						<a class="<?php echo esc_attr( Assets::button_class() ); ?>" href="<?php echo esc_url( $volver ); ?>">Volver a la ficha del procedimiento</a>
					<?php endif; ?>
					<?php if ( '' !== $cambio ) : ?>
						<a class="<?php echo esc_attr( Assets::button_class() ); ?>" href="<?php echo esc_url( $cambio ); ?>">Salir y entrar con otra cuenta</a>
					<?php endif; ?>
				</p>
			<?php endif; ?>
			<?php echo PanelParts::help_link( (string) ( $m['start_help_url'] ?? '' ), '', 'Ayuda: cómo presentar una solicitud' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * On behalf of which code: only when the person has more than one the procedure takes.
	 *
	 * Enlaces y no un desplegable: cada código tiene su propia solicitud, así
	 * que elegir es ir a otra pantalla, y un enlace funciona sin JavaScript
	 * (ADR-0031).
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function choices( array $m ): string {
		$choices = (array) ( $m['choices'] ?? array() );
		if ( count( $choices ) < 2 ) {
			return '';
		}
		$actual = (string) $m['centre']['code'];

		ob_start();
		?>
		<nav class="prc-seccion" aria-labelledby="prc-apply-centre">
			<h2 class="prc-seccion__titulo" id="prc-apply-centre">Solicita en nombre de</h2>
			<div class="prc-seccion__cuerpo">
				<p class="prc-campo__ayuda">Su cuenta tiene más de un código y este procedimiento admite los dos. Cada uno tiene su propia solicitud: elija con cuál quiere verla o presentarla.</p>
				<ul class="prc-opciones prc-opciones--vertical">
					<?php foreach ( $choices as $code => $rotulo ) : ?>
						<li class="prc-opcion">
							<?php if ( (string) $code === $actual ) : ?>
								<strong aria-current="page"><?php echo esc_html( (string) $rotulo ); ?></strong>
							<?php else : ?>
								<a href="<?php echo esc_url( ApplyForm::url_for_code( (int) $m['procedure_id'], (string) $code ) ); ?>"><?php echo esc_html( (string) $rotulo ); ?></a>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</nav>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The help under the locked school code: it depends on whether there is a choice.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function centre_help( array $m ): string {
		return count( (array) ( $m['choices'] ?? array() ) ) > 1
			? 'Es uno de los códigos de su cuenta: para solicitar con el otro, elíjalo arriba.'
			: 'El centro es el de su cuenta: no se puede cambiar desde aquí.';
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
		$v       = (array) $m['values'];
		$spec    = (array) $m['spec'];
		$centro  = (array) $m['centre'];
		$quien   = (array) ( $m['applicant'] ?? array() );
		$nueva   = array() === (array) $m['application'];
		$docente = ApplicationMetaKeys::TYPE_TEACHER === (string) $m['applicant_type'];
		$falla   = (array) ( $m['invalid'] ?? array() );

		// Los campos bloqueados se arman antes: dentro del `echo` de la
		// plantilla, el escape ya no se ve desde fuera.
		$del_centro = self::locked(
			'prc-centre-code',
			'Código',
			(string) $centro['code'],
			3,
			self::centre_help( $m )
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
		<form class="prc-form prc-form--solicitud" id="<?php echo esc_attr( ApplicationProgress::ANCHOR_FORM ); ?>" method="post" action="" enctype="multipart/form-data">
			<?php wp_nonce_field( ApplyForm::NONCE_ACTION, ApplyForm::NONCE_FIELD, false ); ?>
			<input type="hidden" name="<?php echo esc_attr( ApplyForm::FIELD_OP ); ?>" value="<?php echo esc_attr( ApplyForm::OP_APPLY ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( ApplyForm::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( ApplyForm::FIELD_CENTRE ); ?>" value="<?php echo esc_attr( (string) $centro['code'] ); ?>" />

			<?php echo self::privacy(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>

			<?php if ( ! $docente ) : ?>
				<section class="prc-seccion">
					<h2 class="prc-seccion__titulo">Datos del centro educativo</h2>
					<div class="prc-seccion__cuerpo">
						<?php echo $del_centro; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
					</div>
				</section>
			<?php endif; ?>

			<section class="prc-seccion">
				<h2 class="prc-seccion__titulo"><?php echo esc_html( $docente ? 'Datos de la persona participante' : 'Datos personales de la dirección del centro' ); ?></h2>
				<div class="prc-seccion__cuerpo">
					<?php echo $de_quien; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
					<?php if ( ! $docente ) : ?>
						<div class="prc-campo prc-campo--cuarto prc-campo--abre-fila">
							<?php echo self::label( 'prc-position', 'Cargo en el centro', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
							<select class="prc-control<?php echo esc_attr( FieldErrorView::css( $falla, 'position' ) ); ?>" id="prc-position" name="<?php echo esc_attr( ApplyForm::FIELD_POSITION ); ?>" required<?php echo FieldErrorView::attrs( $falla, 'position' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>>
								<option value="">— Elija —</option>
								<?php foreach ( (array) $m['positions'] as $clave => $rotulo ) : ?>
									<option value="<?php echo esc_attr( (string) $clave ); ?>" <?php selected( (string) $clave, (string) $v['position'] ); ?>><?php echo esc_html( (string) $rotulo ); ?></option>
								<?php endforeach; ?>
							</select>
							<?php echo FieldErrorView::feedback( $falla, 'position' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
						</div>
					<?php endif; ?>
				</div>
			</section>

			<?php if ( '' !== trim( (string) $spec['commitments'] ) ) : ?>
				<section class="prc-seccion">
					<h2 class="prc-seccion__titulo">Solicita</h2>
					<div class="prc-seccion__cuerpo">
						<div class="prc-nota prc-campo--completo">
							<p><?php echo esc_html( $docente ? 'Su participación en la presente convocatoria, comprometiéndose a cumplir los siguientes compromisos:' : 'La participación de su centro en la presente convocatoria, comprometiéndose a cumplir los siguientes compromisos:' ); ?></p>
							<p><?php echo nl2br( esc_html( (string) $spec['commitments'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapado antes de nl2br(). ?></p>
						</div>
						<div class="prc-campo prc-campo--completo">
							<div class="prc-opciones prc-opciones--vertical">
								<div class="prc-opcion">
									<label for="prc-accept">
										<input type="checkbox" class="<?php echo esc_attr( trim( FieldErrorView::css( $falla, 'accept' ) ) ); ?>" id="prc-accept" name="<?php echo esc_attr( ApplyForm::FIELD_ACCEPT ); ?>" value="1" required <?php checked( (bool) $v['accept'] ); ?><?php echo FieldErrorView::attrs( $falla, 'accept' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?> />
										<?php echo esc_html( $docente ? 'Acepto estos compromisos' : 'El centro acepta estos compromisos' ); ?>
									</label>
								</div>
							</div>
							<?php echo FieldErrorView::feedback( $falla, 'accept' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
						</div>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( array() !== (array) $spec['questions'] ) : ?>
				<section class="prc-seccion prc-seccion--sin-titulo" aria-labelledby="prc-preguntas">
					<h2 class="screen-reader-text" id="prc-preguntas">Preguntas del procedimiento</h2>
					<div class="prc-seccion__cuerpo">
						<?php foreach ( (array) $spec['questions'] as $pregunta ) : ?>
							<?php echo self::question( (array) $pregunta, (array) $v['answers'], $subidos, $falla ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>

			<?php echo self::coordination( $m, (array) $v['coordinators'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>

			<?php echo ApplicationProgressView::submit( $m, $nueva ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The coordinators the form asks for, or where they are asked when it is the list.
	 *
	 * @param array<string, mixed>             $m            Model.
	 * @param array<int, array<string, mixed>> $coordinators What was typed or stored.
	 * @return string
	 */
	private static function coordination( array $m, array $coordinators ): string {
		$spec = (array) $m['spec'];
		if ( ! empty( $spec['coordinators_in_list'] ) ) {
			$hasta   = (string) ( $m['people']['until'] ?? '' );
			$despues = (string) ( $m['people']['after_submit'] ?? '' );
			$cuando  = '' !== $hasta ? ' Allí podrá cambiarlas hasta el ' . $hasta . ', incluido.' : '';
			return '<div class="prc-nota prc-campo--completo"><p>'
				. esc_html( 'Las personas coordinadoras se dan de alta en la lista de personas, después de presentar la solicitud.' . ( '' !== $despues ? ' ' . $despues : $cuando ) )
				. '</p></div>';
		}
		$cuantas = (int) ( $spec['coordinator_count'] ?? 0 );
		if ( $cuantas < 1 ) {
			return '';
		}
		$campos = '';
		for ( $index = 0; $index < $cuantas; ++$index ) {
			$campos .= self::coordinator_fields( $index, (array) ( $coordinators[ $index ] ?? array() ), (array) ( $m['invalid'] ?? array() ) );
		}
		return '<section class="prc-seccion"><h2 class="prc-seccion__titulo">Datos de la coordinación</h2><div class="prc-seccion__cuerpo">'
			. '<div class="prc-nota prc-campo--completo"><p>Las personas coordinadoras aceptan el compromiso de colaborar en el desarrollo del programa, proyecto o red tal como se expresa en la convocatoria.</p></div>'
			. $campos . '</div></section>';
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
	 * @param array<string, mixed>  $p       Normalised question.
	 * @param array<string, mixed>  $answers Stored or typed answers, keyed by question key.
	 * @param array<string, mixed>  $files   Documents already attached, keyed by question key.
	 * @param array<string, string> $invalid Failing field code => what names it.
	 * @return string
	 */
	private static function question( array $p, array $answers, array $files = array(), array $invalid = array() ): string {
		if ( in_array( (string) $p['type'], array( ProcedureQuestions::TYPE_NUMBER, ProcedureQuestions::TYPE_DATE, ProcedureQuestions::TYPE_GRID ), true ) ) {
			return MeasuredQuestionView::html( $p, $answers[ (string) $p['key'] ] ?? '', $invalid );
		}
		$key    = (string) $p['key'];
		$subido = isset( $files[ $key ] ) && is_array( $files[ $key ] ) ? $files[ $key ] : array();
		$nombre = ApplyForm::FIELD_ANSWERS . '[' . $key . ']';
		$valor  = $answers[ $key ] ?? '';
		$tipo   = (string) $p['type'];
		$obliga = ! empty( $p['required'] );
		$ayuda  = (string) $p['help'];
		$id     = 'prc-q-' . $key;
		$codigo = 'answer:' . $key;
		$pie    = FieldErrorView::attrs( $invalid, $codigo, '' !== $ayuda ? $id . '-ayuda' : '' );
		$clase  = 'prc-control' . FieldErrorView::css( $invalid, $codigo );
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
				<textarea id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $nombre ); ?>" class="<?php echo esc_attr( $clase ); ?>" rows="3"
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
					class="<?php echo esc_attr( $clase ); ?>" accept="<?php echo esc_attr( implode( ',', array_values( ApplicationFiles::mimes() ) ) ); ?>"
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
				<div class="prc-opciones prc-opciones--vertical" role="<?php echo $varias ? 'group' : 'radiogroup'; ?>" id="<?php echo esc_attr( $id ); ?>"
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
			<?php echo FieldErrorView::feedback( $invalid, $codigo ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
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
	 * The fields of one requested coordinator: name, surname, DNI or NIE and email.
	 *
	 * Nombre y apellidos van en campos distintos (ADR-0039): el fichero de
	 * alta de usuarios los pide por separado.
	 *
	 * @param int                   $index       Position, from zero.
	 * @param array<string, mixed>  $coordinator What was typed or stored.
	 * @param array<string, string> $invalid     Failing field code => what names it.
	 * @return string
	 */
	private static function coordinator_fields( int $index, array $coordinator, array $invalid = array() ): string {
		$campos = array(
			'name'    => array( 'Nombre', 'text', 'prc-campo--cuarto prc-campo--abre-fila', '' ),
			'surname' => array( 'Apellidos', 'text', 'prc-campo--cuarto', '' ),
			'dni'     => array( 'DNI o NIE', 'text', 'prc-campo--cuarto', 'Escriba el DNI o NIE completo, con la letra.' ),
			'email'   => array( 'Correo electrónico', 'email', 'prc-campo--cuarto', '' ),
		);

		ob_start();
		?>
		<fieldset class="prc-seccion-campos">
			<legend><?php echo esc_html( sprintf( 'Persona coordinadora %d', $index + 1 ) ); ?></legend>
			<?php foreach ( $campos as $campo => list( $rotulo, $tipo, $clases, $ayuda ) ) : ?>
				<?php
				$id     = 'prc-coordinator-' . $campo . '-' . $index;
				$codigo = 'coordinator_' . $campo . ':' . $index;
				?>
				<div class="prc-campo <?php echo esc_attr( $clases ); ?>">
					<?php echo self::label( $id, $rotulo, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
					<input class="prc-control<?php echo esc_attr( FieldErrorView::css( $invalid, $codigo ) ); ?>" type="<?php echo esc_attr( $tipo ); ?>" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( ApplyForm::FIELD_COORDINATORS . '[' . $index . '][' . $campo . ']' ); ?>" required
						<?php if ( 'dni' === $campo ) : ?>
							autocomplete="off"
						<?php endif; ?>
						<?php echo FieldErrorView::attrs( $invalid, $codigo, '' !== $ayuda ? 'prc-coordinator-dni-ayuda-' . $index : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
						value="<?php echo esc_attr( (string) ( $coordinator[ $campo ] ?? '' ) ); ?>" />
					<?php echo FieldErrorView::feedback( $invalid, $codigo ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
					<?php if ( '' !== $ayuda ) : ?>
						<p class="prc-campo__ayuda" id="<?php echo esc_attr( 'prc-coordinator-dni-ayuda-' . $index ); ?>"><?php echo esc_html( $ayuda ); ?></p>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</fieldset>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * What was submitted, read-only, when it can no longer be edited.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	public static function summary( array $m ): string {
		$s = (array) $m['application'];
		if ( array() === $s ) {
			return '';
		}
		$coord     = ApplicationMetaRegistration::sanitize_coordinator( $s['coordinator'] );
		$preguntas = (array) ( $m['spec']['questions'] ?? array() );

		ob_start();
		?>
		<section class="prc-seccion prc-solicitud-resumen" id="<?php echo esc_attr( ApplicationProgress::ANCHOR_FORM ); ?>">
			<h2 class="prc-seccion__titulo">Lo que presentó</h2>
			<dl class="prc-respuestas">
				<?php if ( '' !== (string) $s['position'] ) : ?>
					<dt>Cargo</dt>
					<dd><?php echo esc_html( (string) $s['position'] ); ?></dd>
				<?php endif; ?>
				<?php foreach ( $coord as $index => $persona ) : ?>
					<?php if ( '' !== (string) $persona['name'] ) : ?>
						<dt><?php echo esc_html( sprintf( 1 === count( $coord ) ? 'Persona coordinadora' : 'Persona coordinadora %d', $index + 1 ) ); ?></dt>
						<dd><?php echo esc_html( implode( ' · ', array_filter( array( ApplicationMetaRegistration::full_name( $persona ), $persona['dni'], $persona['email'] ) ) ) ); ?></dd>
					<?php endif; ?>
				<?php endforeach; ?>
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
		$rotulo = Shell::mine_label( ApplicationMetaKeys::TYPE_TEACHER === (string) ( $m['applicant_type'] ?? '' ) );
		return '<p><a class="' . esc_attr( Assets::button_class( true ) ) . '" href="' . esc_url( $url ) . '">' . esc_html( $rotulo ) . '</a></p>';
	}
}
