<?php
/**
 * Tests for the CSV export of the applications of a procedure.
 *
 * @package Prc
 */

use Prc\Domain\ProcedureQuestions;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PublicFront\Applications;
use Prc\PublicFront\ProcedureEditor;

/**
 * El CSV: el fichero puro, el de un procedimiento y la descarga.
 *
 * Hoy la exportación la hace un complemento del gestor de formularios desde
 * una vista filtrada por parámetros de la URL. Aquí es una función pura sobre
 * filas, y la descarga va por POST con nonce y con la capacidad de revisar:
 * un enlace en GET que descarga la lista de centros es justo lo que no se
 * quiere que se pueda pegar en un correo.
 */
class Test_Applications_Csv extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Con el aplicativo arrancado, las páginas y un catálogo inventado.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
		$this->pages();
		remove_all_filters( 'prc_centres' );
		add_filter(
			'prc_centres',
			static function (): array {
				return array(
					array(
						'code'      => 'C0001',
						'name'      => 'Centro de prueba Norte',
						'ownership' => 'public',
					),
					array(
						'code'      => 'C0002',
						'name'      => 'Centro de prueba Sur',
						'ownership' => 'public',
					),
				);
			}
		);
	}

	/**
	 * Un procedimiento con una pregunta y dos solicitudes.
	 *
	 * @return array{0:int, 1:int} Procedure ID and its area.
	 */
	private function con_solicitudes(): array {
		$area          = $this->area( 'Área de prueba' );
		$procedimiento = $this->procedure(
			$this->administrator(),
			array( $area ),
			array(
				ProcedureMetaKeys::QUESTIONS => array(
					array(
						'label' => 'Tiene huerto',
						'type'  => ProcedureQuestions::TYPE_YESNO,
					),
				),
			),
			array( 'post_title' => 'Red de huertos' )
		);
		foreach ( array(
			'C0001' => true,
			'C0002' => false,
		) as $codigo => $huerto ) {
			Applications::save(
				$procedimiento,
				$this->school_head( $codigo ),
				array(
					'position'    => ApplicationMetaKeys::POSITION_HEAD,
					'coordinator' => array(
						'name'  => 'Ana Pérez',
						'email' => 'ana@example.org',
					),
					'accept'      => true,
					'answers'     => array( 'q1' => $huerto ),
				)
			);
		}
		return array( $procedimiento, $area );
	}

	/**
	 * Pedir la exportación como alguien.
	 *
	 * @param int  $uid           Who asks.
	 * @param int  $procedimiento Procedure ID.
	 * @param bool $con_nonce    Whether to carry the nonce.
	 * @return string What was served.
	 */
	private function exportar( int $uid, int $procedimiento, bool $con_nonce = true ): string {
		$this->acting_as( $uid );
		$campos = array(
			ProcedureEditor::FIELD_DO        => ProcedureEditor::OP_EXPORT,
			ProcedureEditor::FIELD_PROCEDURE => (string) $procedimiento,
		);
		if ( $con_nonce ) {
			$this->post( $campos, ProcedureEditor::nonce_action( ProcedureEditor::OP_EXPORT ), ProcedureEditor::nonce_name( ProcedureEditor::OP_EXPORT ) );
		} else {
			$this->post( $campos );
		}
		return $this->served( array( ProcedureEditor::class, 'handle' ) );
	}

	// ─── el fichero, sin WordPress ─────────────────────────────────────────

	/**
	 * BOM, punto y coma, CRLF y todo entrecomillado: lo que abre bien en una
	 * hoja de cálculo en español sin pasar por el asistente de importación.
	 */
	public function test_csv_lines_is_a_spreadsheet_friendly_file() {
		$csv = Applications::csv_lines(
			array(
				'centre' => 'Centro',
				'note'   => 'Nota',
			),
			array(
				array(
					'centre' => 'Centro de prueba',
					'note'   => 'Dijo "vale"; y se fue',
				),
				array( 'centre' => 'Sin nota' ),
			)
		);

		$this->assertStringStartsWith( "\xEF\xBB\xBF", $csv, 'BOM de UTF-8' );
		$this->assertSame(
			"\xEF\xBB\xBF\"Centro\";\"Nota\"\r\n\"Centro de prueba\";\"Dijo \"\"vale\"\"; y se fue\"\r\n\"Sin nota\";\"\"\r\n",
			$csv
		);
	}

	/**
	 * La misma fecha, dos valores: `dd-mm-aaaa` para la pantalla —como el resto
	 * del aplicativo— e ISO para el CSV, que es lo que ordena bien en una hoja
	 * de cálculo.
	 */
	public function test_the_screen_reads_the_day_and_the_export_keeps_the_iso() {
		list( $procedimiento ) = $this->con_solicitudes();

		$fila = Applications::rows( $procedimiento )[0];
		$this->assertMatchesRegularExpression( '/^\d{2}-\d{2}-\d{4}$/', $fila['date_label'] );
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $fila['date'] );

		// La columna «Fecha» del fichero es la del ISO, y el rótulo de pantalla
		// no viaja a la exportación.
		$csv = Applications::csv( $procedimiento );
		$this->assertStringContainsString( '"' . $fila['date'] . '"', $csv );
		$this->assertStringNotContainsString( '"' . $fila['date_label'] . '"', $csv );
	}

	/**
	 * Lo que una hoja de cálculo tomaría por fórmula lleva una comilla delante.
	 */
	public function test_a_formula_does_not_run() {
		$csv = Applications::csv_lines(
			array( 'a' => 'A' ),
			array(
				array( 'a' => '=SUM(A1:A9)' ),
				array( 'a' => '+34600000000' ),
				array( 'a' => '-1' ),
				array( 'a' => '@x' ),
				array( 'a' => 'normal' ),
			)
		);

		$this->assertStringContainsString( '"\'=SUM(A1:A9)"', $csv );
		$this->assertStringContainsString( '"\'+34600000000"', $csv );
		$this->assertStringContainsString( '"\'-1"', $csv );
		$this->assertStringContainsString( '"\'@x"', $csv );
		$this->assertStringContainsString( '"normal"', $csv );
	}

	/**
	 * El nombre del fichero es el procedimiento y el día.
	 */
	public function test_the_filename_is_the_procedure_and_the_day() {
		$nombre = Applications::filename( 'Red de «huertos» 2026' );
		$this->assertStringStartsWith( 'solicitudes-red-de-huertos-2026-', $nombre );
		$this->assertStringEndsWith( '.csv', $nombre );
		$this->assertStringStartsWith( 'solicitudes-procedimiento-', Applications::filename( '' ) );
	}

	// ─── el de un procedimiento ────────────────────────────────────────────

	/**
	 * Una fila por solicitud, con las columnas fijas y una por pregunta.
	 */
	public function test_the_csv_of_a_procedure_has_one_row_per_application() {
		list( $procedimiento ) = $this->con_solicitudes();

		$csv    = Applications::csv( $procedimiento );
		$lineas = explode( "\r\n", trim( substr( $csv, 3 ) ) );

		$this->assertCount( 3, $lineas, 'cabecera y dos solicitudes' );
		$this->assertStringContainsString( '"Centro";"Código";"Fecha"', $lineas[0] );
		$this->assertStringEndsWith( ';"Tiene huerto"', $lineas[0], 'la pregunta es la última columna' );

		// Dos solicitudes del mismo segundo no tienen orden fiable: se buscan.
		$norte = $this->linea_de( $lineas, '"C0001"' );
		$sur   = $this->linea_de( $lineas, '"C0002"' );
		$this->assertStringContainsString( '"Centro de prueba Norte";"C0001"', $norte );
		$this->assertStringContainsString( '"Ana Pérez";"ana@example.org"', $norte );
		$this->assertStringContainsString( '"Presentada"', $norte );
		$this->assertStringEndsWith( ';"Sí"', $norte );
		$this->assertStringEndsWith( ';"No"', $sur );

		$vacio = Applications::csv( $this->procedure( $this->administrator() ) );
		$this->assertCount( 1, explode( "\r\n", trim( substr( $vacio, 3 ) ) ), 'sin solicitudes, solo la cabecera' );
	}

	/**
	 * La línea que lleva un trozo, o la cadena vacía.
	 *
	 * @param string[] $lineas Lines.
	 * @param string   $trozo  What to look for.
	 * @return string
	 */
	private function linea_de( array $lineas, string $trozo ): string {
		foreach ( $lineas as $linea ) {
			if ( false !== strpos( $linea, $trozo ) ) {
				return $linea;
			}
		}
		return '';
	}

	// ─── la descarga ───────────────────────────────────────────────────────

	/**
	 * Quien revisa el procedimiento se lo descarga; por POST y con su nonce.
	 */
	public function test_whoever_reviews_downloads_it_by_post_with_a_nonce() {
		list( $procedimiento, $area ) = $this->con_solicitudes();

		$cuerpo = $this->exportar( $this->manager( array( $area ) ), $procedimiento );

		$this->assertStringStartsWith( "\xEF\xBB\xBF", $cuerpo );
		$this->assertStringContainsString( '"C0001"', $cuerpo );
		$this->assertStringContainsString( '"C0002"', $cuerpo );

		$this->assertSame( '', $this->exportar( $this->manager( array( $area ) ), $procedimiento, false ), 'sin nonce no se sirve nada' );
	}

	/**
	 * De otro ámbito, sin ámbito o sin la capacidad, no se descarga nada.
	 */
	public function test_without_permission_nothing_is_served() {
		list( $procedimiento ) = $this->con_solicitudes();

		$this->assertSame( '', $this->exportar( $this->manager( array( $this->area( 'Otro ámbito' ) ) ), $procedimiento ) );
		$this->assertSame( '', $this->exportar( $this->manager(), $procedimiento ) );
		$this->assertSame( '', $this->exportar( $this->school_head( 'C0001' ), $procedimiento ) );
	}

	/**
	 * Histórico cierra la gestión para su ámbito (ADR-0023): lo exporta administración.
	 */
	public function test_an_archived_procedure_is_only_exported_by_administration() {
		list( $procedimiento, $area ) = $this->con_solicitudes();
		update_post_meta( $procedimiento, ProcedureMetaKeys::ARCHIVED, true );

		$this->assertSame( '', $this->exportar( $this->manager( array( $area ) ), $procedimiento ), 'su ámbito ya no gestiona: histórico cierra la revisión' );
		$this->assertStringContainsString( '"C0001"', $this->exportar( $this->administrator(), $procedimiento ), 'administración sí' );
	}
}
