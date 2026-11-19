<?php
/**
 * The signature section of the application screen, painted from what ReceiptSignature::model() decided.
 *
 * @package Prc
 */

namespace Prc\PublicFront\View;

use Prc\PublicFront\Assets;
use Prc\PublicFront\ReceiptSignature;
use Prc\PublicFront\Shell;

/**
 * Solo pinta: no lee la petición, no consulta, no decide.
 *
 * Los dos botones —AutoFirma y descargar el PDF— nacen escondidos y los
 * enseña el guion cuando tiene con qué: sin jsPDF no hay PDF, y sin AutoScript
 * no hay AutoFirma. La subida es un formulario normal y funciona sin guion
 * (ADR-0049).
 */
final class ReceiptSignatureView {

	/**
	 * The section, or nothing when the procedure does not ask for the signature.
	 *
	 * @param array<string, mixed> $s What ReceiptSignature::model() returned.
	 * @return string
	 */
	public static function section( array $s ): string {
		if ( empty( $s['show'] ) ) {
			return '';
		}
		$datos = empty( $s['can'] ) ? '' : (string) wp_json_encode(
			array(
				'receipt'  => (array) $s['data'],
				'servlets' => (array) $s['servlets'],
			)
		);
		ob_start();
		?>
		<section class="prc-seccion prc-firma" id="<?php echo esc_attr( ReceiptSignature::ANCHOR ); ?>" aria-labelledby="prc-firma-titulo"<?php echo '' !== $datos ? ' data-prc-firma="' . esc_attr( $datos ) . '"' : ''; ?>>
			<h2 class="prc-seccion__titulo" id="prc-firma-titulo">Firma electrónica del justificante</h2>
			<?php echo Shell::notice( 'error', (string) ( $s['rejected'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			<p class="prc-firma__estado-actual"><strong><?php echo esc_html( (string) $s['label'] ); ?></strong>
				<?php if ( ! empty( $s['signed'] ) ) : ?>
					· <a class="prc-descarga" href="<?php echo esc_url( (string) $s['download_url'] ); ?>" download>Descargar el justificante firmado</a>
				<?php endif; ?>
			</p>
			<?php if ( ! empty( $s['can'] ) ) : ?>
				<p class="prc-intro">Este procedimiento pide el justificante firmado electrónicamente. Su solicitud ya está presentada: firmarlo no cambia nada más. Puede firmarlo con AutoFirma desde esta pantalla, o descargarlo en PDF, firmarlo con otra aplicación y subirlo.</p>
				<p class="prc-firma__botones" data-prc-firma-botones hidden>
					<button type="button" class="<?php echo esc_attr( Assets::button_class( true ) ); ?>" data-prc-firmar hidden>Firmar justificante con AutoFirma</button>
					<button type="button" class="<?php echo esc_attr( Assets::button_class() ); ?>" data-prc-firma-pdf>Descargar justificante en PDF</button>
				</p>
				<p class="prc-firma__aviso" role="status" aria-live="polite" data-prc-firma-estado></p>
				<noscript><p class="prc-campo__ayuda">Para generar el PDF del justificante hace falta JavaScript. Si ya lo tiene firmado, súbalo aquí abajo.</p></noscript>
				<form class="prc-form prc-firma__subida" method="post" enctype="multipart/form-data" action="<?php echo esc_url( (string) $s['action'] ); ?>" data-prc-firma-form>
					<?php wp_nonce_field( (string) $s['nonce_action'], ReceiptSignature::NONCE_FIELD, false ); ?>
					<input type="hidden" name="<?php echo esc_attr( ReceiptSignature::FIELD_OP ); ?>" value="<?php echo esc_attr( ReceiptSignature::OP_SIGN ); ?>" />
					<input type="hidden" name="<?php echo esc_attr( ReceiptSignature::FIELD_APPLICATION ); ?>" value="<?php echo esc_attr( (string) (int) $s['application'] ); ?>" />
					<input type="hidden" name="<?php echo esc_attr( ReceiptSignature::FIELD_SIGNED ); ?>" value="" data-prc-firma-resultado />
					<div class="prc-campo">
						<label class="prc-campo__etiqueta" for="prc-firma-fichero">Justificante firmado (PDF)</label>
						<input class="prc-control" type="file" id="prc-firma-fichero" name="<?php echo esc_attr( ReceiptSignature::FIELD_FILE ); ?>" accept="application/pdf,.pdf" aria-describedby="prc-firma-fichero-ayuda" />
						<p class="prc-campo__ayuda" id="prc-firma-fichero-ayuda"><?php echo esc_html( 'El justificante de esta solicitud, firmado una sola vez y sin cambios después de firmar. Hasta ' . size_format( (int) $s['max_bytes'] ) . '.' ); ?></p>
					</div>
					<button class="<?php echo esc_attr( Assets::button_class() ); ?>" type="submit">Subir el justificante firmado</button>
				</form>
			<?php endif; ?>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}
