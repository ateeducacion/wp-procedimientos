<?php
/**
 * Tests for the pure rules of the signed receipt: its fingerprint and what a
 * signed PDF has to carry.
 *
 * @package Prc
 */

use Prc\Domain\SignedReceipt;

/**
 * El justificante firmado, sin WordPress (ADR-0049).
 *
 * El PDF lo genera el navegador, así que el servidor no puede cotejar lo que
 * sirvió: lo que mira es que sea un PDF, que lleve una sola firma que lo
 * cubra entero y que sea el justificante de esta solicitud.
 */
class Test_Signed_Receipt extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Los datos de un justificante.
	 *
	 * @return array<string, string>
	 */
	private function datos(): array {
		return array(
			'number'       => '57',
			'procedure'    => 'Red de prueba',
			'centre'       => 'Centro de prueba (C0001)',
			'submitted'    => '10-11-2026 a las 12:34',
			'coordination' => 'Ana Pérez (DNI o NIE 12345678Z)',
		);
	}

	/**
	 * Un PDF firmado de esa solicitud.
	 *
	 * @param string $tail Appended after signing.
	 * @param int    $sigs Signatures.
	 * @return string
	 */
	private function firmado( string $tail = '', int $sigs = 1 ): string {
		$huella = SignedReceipt::fingerprint( $this->datos() );
		return $this->signed_pdf( array( 'Justificante de presentacion', 'Solicitud: 57', 'Huella SHA-256: ' . $huella ), $tail, $sigs );
	}

	/**
	 * La huella es SHA-256 de los cinco datos, siempre la misma, y cambia
	 * si cambia cualquiera: también el DNI o NIE de coordinación.
	 */
	public function test_the_fingerprint_is_the_sha256_of_the_five_fields() {
		$huella = SignedReceipt::fingerprint( $this->datos() );
		$this->assertMatchesRegularExpression( '/^[a-f0-9]{64}$/', $huella );
		$this->assertSame( $huella, SignedReceipt::fingerprint( array_reverse( $this->datos(), true ) ) );
		foreach ( array_keys( $this->datos() ) as $clave ) {
			$otro           = $this->datos();
			$otro[ $clave ] = $otro[ $clave ] . 'x';
			$this->assertNotSame( $huella, SignedReceipt::fingerprint( $otro ), $clave );
		}
	}

	/**
	 * La fecha de creación del PDF sale en UTC con el formato que acepta jsPDF.
	 */
	public function test_the_creation_date_is_a_pdf_date_in_utc() {
		$this->assertSame( "D:20261110113400+00'00'", SignedReceipt::pdf_date( (int) strtotime( '2026-11-10 11:34:00 UTC' ) ) );
	}

	/**
	 * Un justificante firmado una vez, entero, de esta solicitud, pasa.
	 */
	public function test_a_whole_signed_receipt_of_this_application_passes() {
		$this->assertSame( '', SignedReceipt::refuse( $this->firmado(), '57', SignedReceipt::fingerprint( $this->datos() ), 1000000 ) );
	}

	/**
	 * Cada cosa que falta se dice por su nombre.
	 */
	public function test_each_failure_has_its_reason() {
		$huella = SignedReceipt::fingerprint( $this->datos() );
		$bueno  = $this->firmado();

		$this->assertSame( 'too_big', SignedReceipt::refuse( $bueno, '57', $huella, strlen( $bueno ) - 1 ) );
		$this->assertSame( 'not_pdf', SignedReceipt::refuse( 'PK' . $bueno, '57', $huella, 1000000 ) );
		$this->assertSame( 'not_signed', SignedReceipt::refuse( $this->signed_pdf( array( 'Solicitud: 57', 'Huella SHA-256: ' . $huella ), '', 0 ), '57', $huella, 1000000 ) );
		$this->assertSame( 'several', SignedReceipt::refuse( $this->firmado( '', 2 ), '57', $huella, 1000000 ) );
		$this->assertSame( 'not_covered', SignedReceipt::refuse( $this->firmado( "1 0 obj\n<< >>\nendobj\n" ), '57', $huella, 1000000 ) );
		$this->assertSame( 'not_this', SignedReceipt::refuse( $bueno, '58', $huella, 1000000 ) );
		$this->assertSame( 'not_this', SignedReceipt::refuse( $bueno, '5', $huella, 1000000 ) );
		$this->assertSame( 'not_this', SignedReceipt::refuse( $bueno, '57', str_repeat( 'a', 64 ), 1000000 ) );
		foreach ( array( 'too_big', 'not_pdf', 'not_signed', 'several', 'not_covered', 'not_this' ) as $codigo ) {
			$this->assertNotSame( '', SignedReceipt::why( $codigo ), $codigo );
		}
	}

	/**
	 * El hueco del `/ByteRange` tiene que ser la cadena de `/Contents`: un
	 * rango que deja fuera otra cosa no cubre el fichero.
	 */
	public function test_the_gap_has_to_be_the_signature_contents() {
		$huella = SignedReceipt::fingerprint( $this->datos() );
		$pdf    = $this->firmado();
		preg_match( '~/ByteRange \[0 (\d+) (\d+) (\d+)\]~', $pdf, $m );
		$movido = str_replace( $m[0], sprintf( '/ByteRange [0 %010d %010d %010d]', (int) $m[1] - 5, (int) $m[2], (int) $m[3] ), $pdf );
		$this->assertSame( 'not_covered', SignedReceipt::refuse( $movido, '57', $huella, 1000000 ) );
	}

	/**
	 * Un firmador que reescribe el fichero y comprime el texto se lee igual:
	 * se miran también los flujos comprimidos.
	 */
	public function test_the_text_is_also_read_in_compressed_streams() {
		$huella = SignedReceipt::fingerprint( $this->datos() );
		$pdf    = $this->firmado();
		$claro  = '';
		$pdf    = preg_replace_callback(
			"~stream\n(.*?)endstream~s",
			static function ( array $m ) use ( &$claro ): string {
				$claro = $m[1];
				return "stream\n" . gzcompress( $m[1] ) . "\nendstream";
			},
			$pdf,
			1
		);
		$this->assertNotSame( '', $claro );
		$this->assertStringNotContainsString( 'Solicitud: 57', $pdf );
		// Al comprimir cambian los tamaños: se rehace la firma sobre el resultado.
		$pdf = substr( $pdf, 0, (int) strpos( $pdf, "5 0 obj\n" ) );
		$pdf = $this->resign( $pdf );
		$this->assertSame( '', SignedReceipt::refuse( $pdf, '57', $huella, 1000000 ) );
	}

	/**
	 * Un flujo que se infla sin fin no se descomprime entero: lo que se lee
	 * no pasa del máximo, y sin la huella dentro no es este justificante.
	 */
	public function test_a_compression_bomb_is_not_inflated_whole() {
		$huella = SignedReceipt::fingerprint( $this->datos() );
		$bomba  = gzcompress( str_repeat( "\0", 20000000 ) );
		$pdf    = $this->resign( "%PDF-1.3\n4 0 obj\n<< /Length " . strlen( $bomba ) . " >>\nstream\n" . $bomba . "\nendstream\nendobj\n" );
		$antes  = memory_get_usage();
		$this->assertSame( 'not_this', SignedReceipt::refuse( $pdf, '57', $huella, 100000 ) );
		$this->assertLessThan( 5000000, memory_get_usage() - $antes );
	}

	/**
	 * Muchas bombas pequeñas no obligan a inflar cada una: un intento fallido
	 * también cuenta, y pasado el tope ya no se lee nada más. El texto del
	 * justificante, detrás de ellas, no llega a leerse.
	 */
	public function test_many_small_bombs_stop_the_reading() {
		$huella = SignedReceipt::fingerprint( $this->datos() );
		$bomba  = gzcompress( str_repeat( "\0", 200000 ) );
		$texto  = gzcompress( 'BT (Solicitud: 57) Tj (Huella SHA-256: ' . $huella . ") Tj ET /Type /Sig\n" );
		$con    = function ( int $bombas ) use ( $bomba, $texto ): string {
			$pdf = "%PDF-1.3\n" . str_repeat( "4 0 obj\n<< >>\nstream\n" . $bomba . "\nendstream\nendobj\n", $bombas );
			return $this->resign( $pdf . "6 0 obj\n<< >>\nstream\n" . $texto . "\nendstream\nendobj\n" );
		};

		$antes = microtime( true );
		$this->assertSame( 'not_this', SignedReceipt::refuse( $con( 300 ), '57', $huella, 100000 ) );
		$this->assertLessThan( 2.0, microtime( true ) - $antes, 'se corta pronto' );

		// Con unas pocas, el mismo texto sí se lee.
		$this->assertSame( '', SignedReceipt::refuse( $con( 3 ), '57', $huella, 100000 ) );
	}

	/**
	 * Añadir una revisión de firma a unos bytes cualquiera.
	 *
	 * @param string $pdf Unsigned PDF.
	 * @return string
	 */
	private function resign( string $pdf ): string {
		$hueco = '<' . str_repeat( '0', 64 ) . '>';
		$pdf  .= "5 0 obj\n<< /Type /Sig /ByteRange [0 0000000000 0000000000 0000000000] /Contents " . $hueco . " >>\nendobj\n%%EOF\n";
		$desde = strrpos( $pdf, $hueco );
		$hasta = $desde + strlen( $hueco );
		return str_replace( '/ByteRange [0 0000000000 0000000000 0000000000]', sprintf( '/ByteRange [0 %010d %010d %010d]', $desde, $hasta, strlen( $pdf ) - $hasta ), $pdf );
	}
}
