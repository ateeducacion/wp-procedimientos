<?php
/**
 * Pure rules of the signed receipt: its fingerprint and what a signed PDF has to carry.
 *
 * @package Prc
 */

namespace Prc\Domain;

/**
 * El justificante que se firma con AutoFirma o se sube firmado (ADR-0049).
 *
 * Puro: ni una llamada a WordPress. El PDF lo genera el navegador con jsPDF a
 * partir de cuatro datos y de su huella, así que el servidor no puede cotejar
 * «lo que sirvió»: lo que mira de lo que vuelve es que sea un PDF, que lleve
 * **una sola firma que lo cubra entero** y que sea el justificante de esta
 * solicitud —su número y su huella en el texto—.
 *
 * **No valida la firma**: ni el certificado, ni la cadena, ni si está
 * revocado. Eso lo hace VALIDe o el lector de PDF de quien lo necesite. No es
 * un analizador de PDF: busca lo justo, en los bytes y dentro de los flujos
 * que se dejen descomprimir.
 */
final class SignedReceipt {

	/**
	 * The fields the fingerprint covers, in order.
	 *
	 * @var string[]
	 */
	public const FIELDS = array( 'number', 'procedure', 'centre', 'submitted' );

	/**
	 * Most decompression attempts per PDF, failed ones included.
	 *
	 * El justificante trae unos pocos flujos; un PDF con cientos de bombas
	 * pequeñas no obliga a inflar cada una hasta el tope.
	 *
	 * @var int
	 */
	public const MAX_INFLATES = 64;

	/**
	 * The fingerprint of the receipt: SHA-256 of its four fields, one per line.
	 *
	 * @param array<string, string> $fields `number`, `procedure`, `centre` and `submitted`.
	 * @return string 64 hex characters.
	 */
	public static function fingerprint( array $fields ): string {
		$lineas = array();
		foreach ( self::FIELDS as $clave ) {
			$lineas[] = str_replace( array( "\r", "\n" ), ' ', (string) ( $fields[ $clave ] ?? '' ) );
		}
		return hash( 'sha256', implode( "\n", $lineas ) );
	}

	/**
	 * A moment as a PDF date in UTC, as jsPDF takes it for `/CreationDate`.
	 *
	 * En UTC y no en la hora del navegador: el mismo justificante tiene que
	 * salir igual en cualquier equipo.
	 *
	 * @param int $timestamp Unix time.
	 * @return string `D:YYYYMMDDHHmmSS+00'00'`.
	 */
	public static function pdf_date( int $timestamp ): string {
		return 'D:' . gmdate( 'YmdHis', $timestamp ) . "+00'00'";
	}

	/**
	 * Why these bytes are not the signed receipt of this application, '' when they are.
	 *
	 * En orden: el tamaño, la cabecera, que haya firma, que sea **una sola**
	 * (`/ByteRange` es uno por firma), que cubra el fichero entero —empieza en
	 * 0, el hueco es la cadena hexadecimal de `/Contents` y el segundo tramo
	 * llega al último byte, así que nada se añadió después de firmar— y que el
	 * texto lleve el número y la huella.
	 *
	 * @param string $pdf         Bytes received.
	 * @param string $number      Application number.
	 * @param string $fingerprint Fingerprint worked out today for that application.
	 * @param int    $max         Largest size accepted, in bytes.
	 * @return string `too_big`, `not_pdf`, `not_signed`, `several`, `not_covered` or `not_this`.
	 */
	public static function refuse( string $pdf, string $number, string $fingerprint, int $max ): string {
		$largo = strlen( $pdf );
		if ( $largo > $max ) {
			return 'too_big';
		}
		if ( 0 !== strpos( $pdf, '%PDF' ) ) {
			return 'not_pdf';
		}
		// Lo barato, en los bytes, antes de descomprimir nada.
		$rangos = (int) preg_match_all( '~/ByteRange\s*\[\s*(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s*\]~', $pdf, $m, PREG_SET_ORDER );
		if ( 0 === $rangos ) {
			return 'not_signed';
		}
		if ( $rangos > 1 ) {
			return 'several';
		}
		if ( ! self::covers( $pdf, array_map( 'intval', array_slice( $m[0], 1 ) ) ) ) {
			return 'not_covered';
		}
		$texto = self::text( $pdf, $max );
		if ( ! preg_match( '~/(?:Type|FT)\s*/Sig\b~', $texto ) ) {
			return 'not_signed';
		}
		return self::names( $texto, $number, $fingerprint ) ? '' : 'not_this';
	}

	/**
	 * Whether a `/ByteRange` covers the whole file but the signature contents.
	 *
	 * @param string $pdf   PDF bytes.
	 * @param int[]  $range Its four numbers.
	 * @return bool
	 */
	private static function covers( string $pdf, array $range ): bool {
		list( $inicio, $hueco, $sigue, $resto ) = $range;
		return 0 === $inicio && $hueco < $sigue && strlen( $pdf ) === $sigue + $resto
			&& '<' === substr( $pdf, $hueco, 1 ) && '>' === substr( $pdf, $sigue - 1, 1 )
			&& ctype_xdigit( substr( $pdf, $hueco + 1, $sigue - $hueco - 2 ) );
	}

	/**
	 * Whether the text carries the number and the fingerprint of the application.
	 *
	 * @param string $texto       Text of the PDF.
	 * @param string $number      Application number.
	 * @param string $fingerprint Fingerprint.
	 * @return bool
	 */
	private static function names( string $texto, string $number, string $fingerprint ): bool {
		return '' !== $number && '' !== $fingerprint
			&& preg_match( '~Solicitud: ' . preg_quote( $number, '~' ) . '(?!\d)~', $texto )
			&& false !== strpos( $texto, 'Huella SHA-256: ' . $fingerprint );
	}

	/**
	 * Why it was refused, in Spanish.
	 *
	 * @param string $code What {@see refuse()} returned.
	 * @return string
	 */
	public static function why( string $code ): string {
		$textos = array(
			'too_big'     => 'El justificante firmado pesa demasiado.',
			'not_pdf'     => 'Lo que ha subido no es un PDF.',
			'not_signed'  => 'El PDF no lleva firma electrónica. Fírmelo con AutoFirma u otra aplicación de firma y vuelva a subirlo.',
			'several'     => 'El PDF lleva más de una firma. Suba el justificante firmado una sola vez.',
			'not_covered' => 'El PDF se modificó después de firmarlo. Vuelva a firmar el justificante y súbalo sin cambios.',
			'not_this'    => 'Ese PDF no es el justificante de esta solicitud, o sus datos han cambiado. Descargue de nuevo el justificante, fírmelo y súbalo.',
		);
		return $textos[ $code ] ?? '';
	}

	/**
	 * The file as text plus whatever its streams hold once inflated.
	 *
	 * El texto lo escribe jsPDF en claro y AutoFirma, con revisión incremental,
	 * no lo toca; otro firmador puede reescribir el fichero y comprimirlo.
	 * Un flujo que no se deja descomprimir simplemente no aporta.
	 *
	 * Todo lo descomprimido junto no pasa de `$max`: un flujo que se infla
	 * sin fin (una bomba de compresión) se corta ahí y no se lee. Y el trabajo
	 * también tiene tope: un intento que falla cuesta lo mismo que uno que sale
	 * —inflar hasta lo que queda—, así que cuenta igual, y no se pasa de
	 * {@see self::MAX_INFLATES}. Un flujo que no empieza como zlib (una imagen
	 * JPEG, por ejemplo) ni se intenta.
	 *
	 * @param string $pdf PDF bytes.
	 * @param int    $max Most inflated bytes read, all streams together.
	 * @return string
	 */
	private static function text( string $pdf, int $max ): string {
		$texto = $pdf;
		if ( ! function_exists( 'gzuncompress' ) || ! preg_match_all( '~(?<!end)stream\r?\n~', $pdf, $marcas, PREG_OFFSET_CAPTURE ) ) {
			return $texto;
		}
		$quedan = self::MAX_INFLATES;
		foreach ( $marcas[0] as $marca ) {
			$ini = (int) $marca[1] + strlen( (string) $marca[0] );
			$fin = strpos( $pdf, 'endstream', $ini );
			if ( false === $fin || ! self::looks_zlib( $pdf, $ini ) ) {
				continue;
			}
			$crudo = substr( $pdf, $ini, $fin - $ini );
			// El salto antes de `endstream` puede ser del flujo o del formato: se
			// prueba con y sin él, y sin él solo si había salto.
			$recorte  = rtrim( $crudo, "\r\n" );
			$intentos = $recorte === $crudo ? array( $crudo ) : array( $crudo, $recorte );
			foreach ( $intentos as $intento ) {
				$queda = $max - ( strlen( $texto ) - strlen( $pdf ) );
				if ( $queda <= 0 || $quedan-- <= 0 ) {
					return $texto;
				}
				$claro = @gzuncompress( $intento, $queda ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- un flujo que no es Flate no es un error, es un flujo que no aporta.
				if ( is_string( $claro ) ) {
					$texto .= "\n" . $claro;
					break;
				}
			}
		}
		return $texto;
	}

	/**
	 * Whether the bytes at an offset start like a zlib stream (RFC 1950 header).
	 *
	 * @param string $pdf    PDF bytes.
	 * @param int    $offset Where the stream starts.
	 * @return bool
	 */
	private static function looks_zlib( string $pdf, int $offset ): bool {
		$cmf = ord( (string) substr( $pdf, $offset, 1 ) );
		$flg = ord( (string) substr( $pdf, $offset + 1, 1 ) );
		return 8 === ( $cmf & 0x0f ) && ( $cmf >> 4 ) <= 7 && 0 === ( ( $cmf << 8 ) | $flg ) % 31;
	}
}
