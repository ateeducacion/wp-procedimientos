<?php
/**
 * How far an application has got, painted from what ApplicationProgress::model() decided.
 *
 * @package Prc
 */

namespace Prc\PublicFront\View;

use Prc\Meta\ApplicationMetaKeys;
use Prc\PublicFront\Assets;
use Prc\PublicFront\PeopleForm;
use Prc\PublicFront\Shell;

/**
 * Solo pinta: no lee la petición, no consulta, no decide.
 *
 * Los pasos son una lista ordenada con enlaces a cada sección y «Hecho» o
 * «Falta» escrito, no solo un color; el primero que falta lleva
 * `aria-current="step"`. El justificante es la misma pantalla en solo
 * lectura, que la hoja de estilo de impresión deja sin cabecera ni menú.
 */
final class ApplicationProgressView {

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
	 * The numbered steps, with «Hecho» or «Falta».
	 *
	 * @param array<string, mixed> $p What ApplicationProgress::model() returned.
	 * @return string Empty when there are no steps to show.
	 */
	public static function steps( array $p ): string {
		$pasos = (array) ( $p['steps'] ?? array() );
		if ( array() === $pasos ) {
			return '';
		}
		$actual = false;
		$out    = '<nav class="prc-pasos-caja" aria-label="Pasos de la solicitud"><ol class="prc-pasos">';
		foreach ( $pasos as $paso ) {
			$hecho  = ! empty( $paso['done'] );
			$marca  = ! $hecho && ! $actual;
			$actual = $actual || $marca;
			$out   .= '<li class="prc-paso prc-paso--' . ( $hecho ? 'hecho' : 'falta' ) . '"' . ( $marca ? ' aria-current="step"' : '' ) . '>'
				. '<a href="' . esc_url( '#' . (string) $paso['anchor'] ) . '">' . esc_html( (string) $paso['label'] ) . '</a> '
				. '<span class="prc-paso__estado">' . esc_html( $hecho ? 'Hecho' : 'Falta' ) . '</span></li>';
		}
		return $out . '</ol></nav>';
	}

	/**
	 * The notice of the screen, with its way to what is missing when there is one.
	 *
	 * Tras un envío rechazado por campos, el aviso es la lista de esos
	 * campos, cada uno con su enlace ({@see FieldErrorView::summary()}).
	 *
	 * @param array<string, string> $flash   Tone, text and, optionally, a link.
	 * @param array<string, string> $invalid Failing field code => what names it.
	 * @return string
	 */
	public static function notice( array $flash, array $invalid = array() ): string {
		if ( 'error' === (string) ( $flash['tipo'] ?? '' ) && array() !== $invalid ) {
			return FieldErrorView::summary( 'La solicitud no se ha guardado. Revise:', $invalid );
		}
		$texto = (string) ( $flash['texto'] ?? '' );
		$link  = (string) ( $flash['link'] ?? '' );
		if ( '' === $texto || '' === $link ) {
			return Shell::notice( (string) ( $flash['tipo'] ?? '' ), $texto, true );
		}
		return Shell::notice_open( (string) ( $flash['tipo'] ?? '' ), true )
			. esc_html( $texto ) . ' <a href="' . esc_url( $link ) . '">' . esc_html( (string) ( $flash['link_text'] ?? '' ) ) . '</a></p>';
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
	public static function card( array $m ): string {
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
				<?php if ( array() !== (array) ( $m['progress'] ?? array() ) ) : ?>
					<?php echo self::status( (array) $m['progress'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				<?php else : ?>
					<span class="prc-solicitud-estado__fecha"><?php echo esc_html( 'Presentada el ' . (string) $s['date'] . '.' ); ?></span>
				<?php endif; ?>
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
	 * The submit button, with the second step announced before the first one ends.
	 *
	 * @param array<string, mixed> $m     Model.
	 * @param bool                 $nueva Whether there is no application yet.
	 * @return string
	 */
	public static function submit( array $m, bool $nueva ): string {
		$personas = (array) ( $m['people'] ?? array() );
		$despues  = (string) ( $personas['after_submit'] ?? '' );
		$texto    = '' === $despues
			? sprintf( 'Después de presentar, en esta misma pantalla tendrá que dar de alta a las personas que participan (al menos %d). Hasta entonces, la solicitud queda incompleta.', (int) ( $personas['min'] ?? 0 ) )
			: sprintf( 'Después de presentar tendrá que dar de alta a las personas que participan (al menos %d), pero no enseguida: %s Hasta entonces, la solicitud queda incompleta.', (int) ( $personas['min'] ?? 0 ), lcfirst( $despues ) );
		$aviso    = $nueva && ! empty( $personas['show'] )
			? '<p class="prc-form__siguiente">' . esc_html( $texto ) . '</p>'
			: '';
		return '<div class="prc-form__envio">' . $aviso
			. '<button class="prc-boton-enviar" type="submit"><span class="material-symbols-outlined" aria-hidden="true">send</span> '
			. esc_html( $nueva ? 'Presentar la solicitud' : 'Guardar los cambios' ) . '</button></div>';
	}

	/**
	 * The lines of the status card: when, whether it is complete, and the receipt.
	 *
	 * @param array<string, mixed> $p What ApplicationProgress::model() returned.
	 * @return string
	 */
	public static function status( array $p ): string {
		$out = '<span class="prc-solicitud-estado__fecha">' . esc_html( 'Presentada el ' . (string) ( $p['submitted'] ?? '' ) . '.' ) . '</span>';
		if ( ! empty( $p['show_complete'] ) ) {
			$out .= '<span class="prc-solicitud-estado__completa">' . esc_html( self::completeness( $p ) ) . '</span>';
		}
		$enlaces = array();
		if ( ! empty( $p['people_missing'] ) ) {
			$enlaces[] = '<a href="' . esc_url( '#' . PeopleForm::ANCHOR ) . '">Ir a la lista de personas</a>';
		}
		if ( '' !== (string) ( $p['receipt_url'] ?? '' ) ) {
			$enlaces[] = '<a href="' . esc_url( (string) $p['receipt_url'] ) . '">Ver e imprimir el justificante</a>';
		}
		if ( array() !== $enlaces ) {
			$out .= '<span class="prc-solicitud-estado__enlace">' . implode( ' · ', $enlaces ) . '</span>';
		}
		return $out;
	}

	/**
	 * «Completa» or «Incompleta» and what it lacks, in one line.
	 *
	 * @param array<string, mixed> $p What ApplicationProgress::model() returned.
	 * @return string
	 */
	private static function completeness( array $p ): string {
		$falta = (array) ( $p['missing'] ?? array() );
		return array() === $falta ? 'Completa: no le falta nada.' : 'Incompleta: ' . lcfirst( implode( '; ', $falta ) ) . '.';
	}

	/**
	 * The printable receipt: number, when and who, completeness and what was submitted.
	 *
	 * Solo lectura y sin un guion: se imprime, o se guarda en PDF, con el
	 * menú del navegador. La hoja de estilo de impresión quita la cabecera,
	 * el menú, el pie y lo que lleva `prc-no-imprimir`.
	 *
	 * @param array<string, mixed> $m Model of the application screen, with `progress` and `people`.
	 * @return string
	 */
	public static function receipt( array $m ): string {
		$p       = (array) $m['progress'];
		$s       = (array) $m['application'];
		$centro  = (array) $m['centre'];
		$docente = ApplicationMetaKeys::TYPE_TEACHER === (string) $m['applicant_type'];
		$quien   = (string) $p['author'] . ( '' !== (string) ( $s['position'] ?? '' ) ? ' (' . (string) $s['position'] . ')' : '' );
		$datos   = array(
			'N.º de solicitud'    => (string) (int) $p['application'],
			'Procedimiento'       => (string) $m['title'],
			'Centro'              => $docente ? '' : (string) $centro['name'] . ' (' . (string) $centro['code'] . ')',
			'Presentada'          => 'El ' . (string) $p['submitted'] . ( '' !== trim( $quien ) ? ', por ' . $quien : '' ) . '.',
			'Última modificación' => (string) $p['modified'],
			'Estado de revisión'  => (string) ( $s['state_label'] ?? '' ),
			'Estado'              => ! empty( $p['show_complete'] ) ? self::completeness( $p ) : '',
		);

		ob_start();
		?>
		<div class="prc-justificante">
			<p class="prc-sub prc-no-imprimir"><a href="<?php echo esc_url( (string) $p['apply_url'] ); ?>">&larr; Volver a la solicitud</a></p>
			<h1 class="prc-h1">Justificante de presentación</h1>
			<dl class="prc-respuestas prc-justificante__datos">
				<?php foreach ( $datos as $rotulo => $valor ) : ?>
					<?php if ( '' !== $valor ) : ?>
						<dt><?php echo esc_html( $rotulo ); ?></dt>
						<dd><?php echo esc_html( $valor ); ?></dd>
					<?php endif; ?>
				<?php endforeach; ?>
			</dl>
			<?php echo ApplyFormView::summary( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			<?php echo PeopleView::review( (array) ( $m['people'] ?? array() ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			<p class="prc-campo__ayuda prc-no-imprimir">Para imprimirlo o guardarlo, use «Imprimir» en el menú de su navegador y, si lo quiere en un fichero, elija «Guardar como PDF».</p>
		</div>
		<?php
		return Shell::render( '', '', (string) ob_get_clean() );
	}
}
