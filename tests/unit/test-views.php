<?php
/**
 * Rendering smoke tests for the read-only views of the application.
 *
 * @package Prc
 */

use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PublicFront\Assets;
use Prc\PublicFront\Home;
use Prc\PublicFront\MyCentre;
use Prc\PublicFront\ProcedureView;
use Prc\PublicFront\Shell;
use Prc\PublicFront\View\HomeView;
use Prc\PublicFront\View\MyCentreView;
use Prc\PublicFront\View\PanelParts;
use Prc\PublicFront\View\ProcedureChrome;
use Prc\PublicFront\View\WorkspaceView;
use Prc\PublicFront\Workspace;
use Prc\Taxonomy\ProcedureTaxonomies;

/**
 * Que cada pantalla se pinte de verdad, y que lo pinte escapado.
 *
 * Los tests de las pantallas comprueban lo que decide `model()`; estos
 * comprueban lo que sale por la otra punta. Una constante mal escrita o una
 * clave que ya no está en el modelo son un error fatal que solo aparece al
 * pintar, y una cadena sin escapar es un agujero que no se ve hasta que
 * alguien lo usa: las dos cosas se cazan aquí.
 */
class Test_Views extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Con el aplicativo, sus páginas y sin el catálogo del entorno de desarrollo.
	 */
	public function set_up() {
		parent::set_up();
		$this->app();
		$this->pages();
		remove_all_filters( 'prc_centres' );
	}

	/**
	 * Las fechas de un plazo abierto hoy.
	 *
	 * @return array<string, string>
	 */
	private function abierto(): array {
		return array(
			ProcedureMetaKeys::OPENS_AT  => gmdate( 'Y-m-d', strtotime( '-3 days' ) ),
			ProcedureMetaKeys::CLOSES_AT => gmdate( 'Y-m-d', strtotime( '+3 days' ) ),
		);
	}

	/**
	 * Un título que intenta escaparse de donde se pinte.
	 */
	private const VENENO = 'Red "><script>alert(1)</script>';

	// ─── la portada ────────────────────────────────────────────────────────

	/**
	 * La portada pinta sus grupos, sus tarjetas y su filtro.
	 */
	public function test_the_front_page_paints_its_groups() {
		$curso = $this->course( '2026-2027' );
		$una   = $this->area( 'Área de prueba' );
		$otra  = $this->area( 'Otro ámbito' );

		foreach ( array( $una, $otra ) as $area ) {
			$id = $this->procedure(
				$this->administrator(),
				array( $area ),
				$this->abierto(),
				array(
					'post_title'   => 'Red de ' . $area,
					'post_excerpt' => 'Una red de centros.',
				)
			);
			wp_set_object_terms( $id, array( $curso ), ProcedureTaxonomies::COURSE );
		}

		$this->acting_as( 0 );
		$html = HomeView::html( Home::model() );

		$this->assertStringContainsString( 'prc-grupo-' . ProcedureMetaKeys::STATE_OPEN, $html );
		$this->assertStringContainsString( 'prc-bloque--' . ProcedureMetaKeys::STATE_OPEN, $html );
		$this->assertStringContainsString( 'Abierto', $html );
		$this->assertStringContainsString( 'prc-rejilla', $html );
		$this->assertStringContainsString( 'prc-proc__marco', $html );
		$this->assertStringContainsString( 'Todos los ámbitos', $html, 'con dos ámbitos hay algo que elegir' );
		$this->assertStringContainsString( Home::VAR_AREA . '=', $html, 'el filtro es un enlace, no un desplegable' );
	}

	/**
	 * Y lo escapa: lo que llega en el modelo sale como texto, no como etiquetas.
	 *
	 * Se envenena el modelo y no el post: al guardarlo, WordPress ya limpia el
	 * título, y entonces esto no probaría a la vista sino al núcleo. Lo que hay
	 * que afirmar aquí es que la vista escapa lo que le den.
	 */
	public function test_the_front_page_escapes_what_it_paints() {
		$curso = $this->course( '2026-2027' );
		$area  = $this->area( 'Área de prueba' );
		$id    = $this->procedure( $this->administrator(), array( $area ), $this->abierto() );
		wp_set_object_terms( $id, array( $curso ), ProcedureTaxonomies::COURSE );

		$this->acting_as( 0 );
		$m    = Home::model();
		$fila = &$m['groups'][ ProcedureMetaKeys::STATE_OPEN ]['rows'][0];

		$fila['title']        = self::VENENO;
		$fila['state_label']  = '<b>Abierto</b>';
		$fila['deadline']     = '"><img src=x onerror=alert(1)>';
		$fila['header_color'] = '#fff" onload="alert(1)';
		$fila['url']          = 'javascript:alert(1)';
		unset( $fila );

		// Y el filtro, que es lo único que pinta nombres de término.
		$m['filters'][0]['label']             = '<b>Eje</b>';
		$m['filters'][0]['items'][0]['label'] = '<b>Ámbito</b>';
		$m['filters'][0]['items'][0]['url']   = 'javascript:alert(1)';

		$html = HomeView::html( $m );

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringNotContainsString( '<img src=x', $html );
		$this->assertStringNotContainsString( '<b>Ámbito</b>', $html );
		$this->assertStringNotContainsString( '<b>Abierto</b>', $html );
		$this->assertStringNotContainsString( 'onload="alert(1)"', $html );
		$this->assertStringNotContainsString( 'href="javascript:', $html );
		$this->assertStringContainsString( '&lt;script&gt;', $html );
	}

	/**
	 * Sin nada que enseñar, el texto y el enlace para quitar el filtro.
	 */
	public function test_the_front_page_paints_why_it_is_empty() {
		$this->acting_as( 0 );
		$html = HomeView::html( Home::model() );

		$this->assertStringContainsString( 'prc-vacio', $html );
		$this->assertStringContainsString( 'Todavía no hay ningún procedimiento', $html );
		$this->assertStringNotContainsString( 'Ver todos los ámbitos', $html, 'no hay filtro puesto que quitar' );
	}

	// ─── el listado de gestión ─────────────────────────────────────────────

	/**
	 * El listado pinta sus cifras, sus filtros y su tabla.
	 */
	public function test_the_workspace_paints_its_table() {
		$area  = $this->area( 'Área de prueba' );
		$curso = $this->course( '2026-2027' );
		$quien = $this->manager( array( $area ) );
		$id    = $this->procedure( $quien, array( $area ), $this->abierto(), array( 'post_title' => 'Red de centros de prueba' ) );
		wp_set_object_terms( $id, array( $curso ), ProcedureTaxonomies::COURSE );
		$this->application( $id, $this->school_head( 'C0001' ) );

		$this->acting_as( $quien );
		$html = WorkspaceView::html( Workspace::model() );

		// Las cifras del calco: la línea de recuento y el recuento que lleva
		// cada estado del desplegable. La tira de contadores que pintaba el
		// esqueleto —`prc-cifras`— no está en la pantalla que se calca.
		$this->assertStringContainsString( 'prc-recuento', $html );
		$this->assertStringContainsString( 'Mostrando <strong>1</strong> procedimiento', $html );
		$this->assertStringNotContainsString( 'prc-cifras', $html );
		foreach ( array( 'Todos (1)', 'Abierto (1)', 'En subsanación (0)', 'Borrador (0)' ) as $cifra ) {
			$this->assertStringContainsString( $cifra, $html, 'falta la cifra ' . $cifra );
		}
		$this->assertStringContainsString( 'Red de centros de prueba', $html );
		$this->assertStringContainsString( 'Área de prueba', $html );
		$this->assertStringContainsString( '2026-2027', $html );
		$this->assertStringContainsString( 'name="' . Workspace::VAR_STATE . '"', $html );
		$this->assertStringContainsString( 'Nuevo procedimiento', $html );
		$this->assertStringContainsString( 'prc-tabla', $html );
	}

	/**
	 * Un procedimiento histórico lleva su pastilla, aunque su estado sea otro.
	 */
	public function test_the_workspace_marks_what_is_historic() {
		$area  = $this->area( 'Área de prueba' );
		$quien = $this->manager( array( $area ) );
		$id    = $this->procedure( $quien, array( $area ), $this->abierto(), array( 'post_title' => 'La cerrada' ) );

		// «Histórico» es también una opción del desplegable de estado, así que
		// lo que se mira es la pastilla de la fila, no la palabra suelta.
		$this->acting_as( $quien );
		$this->assertStringNotContainsString( 'prc-state-' . ProcedureMetaKeys::STATE_ARCHIVED, WorkspaceView::html( Workspace::model() ) );

		update_post_meta( $id, ProcedureMetaKeys::ARCHIVED, true );
		$this->assertStringContainsString( 'prc-state-' . ProcedureMetaKeys::STATE_ARCHIVED, WorkspaceView::html( Workspace::model() ) );

		// Y cuando el estado derivado dice otra cosa —un borrador cerrado a
		// edición—, la marca va aparte para que no se pierda de vista.
		wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => 'draft',
			)
		);
		$html = WorkspaceView::html( Workspace::model() );
		$this->assertStringContainsString( 'Histórico', $html );
		$this->assertStringContainsString( 'Cerrado a edición', $html );
	}

	/**
	 * Y lo escapa todo: el título, el ámbito y el curso pasan por `esc_html()`.
	 */
	public function test_the_workspace_escapes_what_it_paints() {
		$area  = $this->area( 'Área de prueba' );
		$quien = $this->manager( array( $area ) );
		$this->procedure( $quien, array( $area ), $this->abierto() );

		$this->acting_as( $quien );
		$m                       = Workspace::model();
		$m['rows'][0]['title']   = self::VENENO;
		$m['rows'][0]['areas']   = array( $area => '<b>Ámbito</b>' );
		$m['rows'][0]['courses'] = array( 1 => '"><img src=x onerror=alert(1)>' );
		$m['rows'][0]['url']     = 'javascript:alert(1)';

		$html = WorkspaceView::html( $m );

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringNotContainsString( '<b>Ámbito</b>', $html );
		$this->assertStringNotContainsString( '<img src=x', $html );
		$this->assertStringNotContainsString( 'href="javascript:', $html );
		$this->assertStringContainsString( '&lt;script&gt;', $html );
	}

	/**
	 * Sin poder gestionar, el listado es el aviso y nada más.
	 */
	public function test_the_workspace_without_permission_paints_only_the_notice() {
		$this->acting_as( $this->manager() );
		$html = WorkspaceView::html( Workspace::model() );

		$this->assertStringContainsString( 'ningún ámbito asignado', $html );
		$this->assertStringNotContainsString( 'prc-tabla', $html );
		$this->assertStringNotContainsString( 'Nuevo procedimiento', $html );
		$this->assertStringNotContainsString( '<form', $html );
	}

	// ─── la ficha pública ──────────────────────────────────────────────────

	/**
	 * La ficha pinta el título, la caja resumen, las pestañas y los enlaces.
	 */
	public function test_the_public_card_paints_its_sidebar() {
		$area  = $this->area( 'Área de prueba' );
		$curso = $this->course( '2026-2027' );
		$id    = $this->procedure(
			$this->administrator(),
			array( $area ),
			array_merge(
				$this->abierto(),
				array(
					ProcedureMetaKeys::AMEND_OPENS_AT  => '2026-11-02',
					ProcedureMetaKeys::AMEND_CLOSES_AT => '2026-11-06',
					ProcedureMetaKeys::CONTACT_EMAILS  => array( 'contacto@example.org' ),
					ProcedureMetaKeys::REQUIRES_COORDINATOR => true,
					ProcedureMetaKeys::FINAL_LIST_URL  => 'https://example.org/definitivo.pdf',
				)
			),
			array(
				'post_title'   => 'Red de centros de prueba',
				'post_content' => '<p>Las bases de la convocatoria.</p>',
			)
		);
		wp_set_object_terms( $id, array( $curso ), ProcedureTaxonomies::COURSE );

		$this->acting_as( 0 );
		$html = ProcedureChrome::html( ProcedureView::model( $id ) );

		$this->assertStringContainsString( 'prc-ficha', $html );
		// El título del procedimiento es de la ficha, no del armazón.
		$this->assertStringContainsString( 'prc-titulo-proc', $html );
		$this->assertStringContainsString( 'Red de centros de prueba', $html );
		$this->assertStringContainsString( 'Promovido por Área de prueba', $html );

		// La caja resumen: el estado con su icono y las filas que tienen dato.
		$this->assertStringContainsString( 'prc-resumen__estado--resolved', $html );
		$this->assertStringContainsString( 'view_list', $html );
		$this->assertStringContainsString( '2026-2027', $html );
		$this->assertStringContainsString( 'Inscripción', $html );
		$this->assertStringContainsString( 'Subsanación', $html );
		$this->assertStringContainsString( 'Pide una persona coordinadora', $html );
		$this->assertStringContainsString( 'mailto:contacto@example.org', $html );
		$this->assertStringContainsString( 'prc-resumen__enlaces', $html );
		$this->assertStringContainsString( 'Listado definitivo de admitidos', $html );

		// A quién se dirige, en la tarjeta de acceso y en el pie.
		$this->assertStringContainsString( 'Todos los centros', $html );
		$this->assertStringContainsString( 'Las bases de la convocatoria.', $html );
	}

	/**
	 * Sin datos no se pinta la fila vacía, ni la lista vacía, ni el contador.
	 *
	 * Es la regla de la caja resumen: nunca «Subsanación: —». Y sin plazo
	 * vivo tampoco hay contador, que en el original se quedaba a cero.
	 */
	public function test_the_public_card_without_dates_or_links() {
		$id = $this->procedure( $this->administrator(), array( $this->area( 'Área de prueba' ) ) );

		$this->acting_as( 0 );
		$html = ProcedureChrome::html( ProcedureView::model( $id ) );

		$this->assertStringContainsString( 'prc-resumen', $html );
		$this->assertStringNotContainsString( 'prc-resumen__datos', $html );
		$this->assertStringNotContainsString( 'prc-resumen__enlaces', $html );
		$this->assertStringNotContainsString( 'prc-cuenta', $html );
		// Con un solo panel no se pinta la barra: es una ficha larga y ya.
		$this->assertStringNotContainsString( 'prc-tabs-ficha__barra', $html );
	}

	/**
	 * Y lo escapa: el título va en el marco y el cuerpo pasa por `wp_kses_post()`.
	 */
	public function test_the_public_card_escapes_what_it_paints() {
		$id = $this->procedure( $this->administrator(), array( $this->area( 'Área de prueba' ) ), $this->abierto() );

		$this->acting_as( 0 );
		$m          = ProcedureView::model( $id );
		$m['title'] = self::VENENO;
		$m['areas'] = '<b>Ámbito</b>';
		// El cuerpo ya no se pinta de una pieza: son los paneles de las
		// pestañas, y cada uno pasa por `wp_kses_post()`.
		$m['tabs']          = array(
			array(
				'id'    => 'prc-panel-1',
				'label' => self::VENENO,
				'html'  => '<p>Bases</p><script>alert(2)</script>',
			),
		);
		$m['image']         = 'javascript:alert(3)';
		$m['contact_email'] = '"><img src=x onerror=alert(4)>';
		$m['action']        = array(
			'label'   => self::VENENO,
			'url'     => 'javascript:alert(5)',
			'icon'    => '"><script>alert(6)</script>',
			'primary' => true,
		);
		$html               = Shell::render( (string) $m['title'], (string) $m['excerpt'], ProcedureChrome::html( $m ) );

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringNotContainsString( '<b>Ámbito</b>', $html );
		$this->assertStringNotContainsString( '<img src=x', $html );
		$this->assertStringNotContainsString( 'javascript:', $html );
		$this->assertStringContainsString( '&lt;script&gt;', $html );
		$this->assertStringContainsString( 'Bases', $html, 'el cuerpo pasa por wp_kses_post() y se queda con lo suyo' );
	}

	/**
	 * A quién se dirige la convocatoria, escrito.
	 */
	public function test_the_ownership_is_written_out() {
		$todas = array_keys( ProcedureMetaKeys::ownerships() );

		$this->assertSame( 'Todos los centros', ProcedureChrome::ownership_label( array() ) );
		$this->assertSame( 'Todos los centros', ProcedureChrome::ownership_label( $todas ), 'pedirlas todas es no pedir ninguna' );
		$this->assertSame( 'Todos los centros', ProcedureChrome::ownership_label( array( 'lo-que-sea' ) ) );
		$this->assertSame(
			ProcedureMetaKeys::ownerships()[ ProcedureMetaKeys::OWNERSHIP_PUBLIC ],
			ProcedureChrome::ownership_label( array( ProcedureMetaKeys::OWNERSHIP_PUBLIC ) )
		);
	}

	// ─── lo que repone el armazón al servir la página solo ─────────────────

	/**
	 * El aviso de cookies y la analítica **no traen nada dentro**: sin
	 * configurarlos no se pinta ninguno y no se envía ni una visita (ADR-0009).
	 */
	public function test_the_consent_and_the_analytics_are_empty_until_somebody_fills_them() {
		$this->assertSame( '', ProcedureChrome::consent() );
		$this->assertSame( '', ProcedureChrome::analytics() );

		add_filter(
			'prc_chrome',
			static function ( array $chrome ): array {
				return array_merge(
					$chrome,
					array(
						'consent_css'  => 'https://example.org/aviso.css',
						'consent_js'   => 'https://example.org/aviso.js',
						'consent_init' => 'https://example.org/aviso-init.js',
						'matomo_api'   => 'https://example.org/analitica/api.php',
						'matomo_js'    => 'https://example.org/analitica/tracker.js',
						'matomo_site'  => 4,
					)
				);
			}
		);

		$aviso = ProcedureChrome::consent();
		$this->assertStringContainsString( 'https://example.org/aviso.css', $aviso );
		$this->assertStringContainsString( 'defer', $aviso );

		// Fuera de producción tampoco se emite: mandaría visitas de un portátil
		// a la estadística de verdad.
		$this->assertSame( '', ProcedureChrome::analytics() );

		add_filter( 'prc_analytics_enabled', '__return_true' );
		$analitica = ProcedureChrome::analytics();
		$this->assertStringContainsString( 'requireCookieConsent', $analitica, 'sin consentimiento no se escriben cookies' );
		$this->assertStringContainsString( ProcedureChrome::CONSENT_COOKIE, $analitica );
		$this->assertStringContainsString( '"setSiteId",4', $analitica );
		$this->assertStringContainsString( 'https://example.org/analitica/tracker.js', $analitica );
	}

	// ─── mi centro ─────────────────────────────────────────────────────────

	/**
	 * «Mi centro» pinta la tabla de sus solicitudes con su revisión y su nota.
	 */
	public function test_my_centre_paints_its_applications() {
		add_filter(
			'prc_centres',
			static function (): array {
				return array(
					array(
						'code'      => 'C0001',
						'name'      => 'Centro de Educación Norte',
						'ownership' => 'public',
					),
				);
			}
		);

		$area          = $this->area( 'Área de prueba' );
		$procedimiento = $this->procedure(
			$this->administrator(),
			array( $area ),
			$this->abierto(),
			array( 'post_title' => 'Red de centros de prueba' )
		);
		$direccion     = $this->school_head( 'C0001' );
		$this->application(
			$procedimiento,
			$direccion,
			'',
			array(
				ApplicationMetaKeys::REVIEW_STATE => ApplicationMetaKeys::REVIEW_AMEND,
				ApplicationMetaKeys::REVIEW_NOTE  => 'Falta el nombre de la coordinación.',
			)
		);

		$this->acting_as( $direccion );
		$html = MyCentreView::html( MyCentre::model() );

		$this->assertStringContainsString( 'Centro de Educación Norte', $html );
		$this->assertStringContainsString( 'C0001', $html );
		$this->assertStringContainsString( 'Red de centros de prueba', $html );
		$this->assertStringContainsString( 'Falta el nombre de la coordinación.', $html );
		$this->assertStringContainsString( ApplicationMetaKeys::review_states()[ ApplicationMetaKeys::REVIEW_AMEND ], $html );
	}

	/**
	 * Y sin solicitudes, sin centro o sin el perfil, un aviso y nada más.
	 */
	public function test_my_centre_says_why_there_is_nothing() {
		$this->acting_as( 0 );
		$this->assertStringContainsString( 'Debe iniciar sesión', MyCentreView::html( MyCentre::model() ) );

		$this->acting_as( $this->manager( array( $this->area( 'Área de prueba' ) ) ) );
		$this->assertStringContainsString( 'no presenta solicitudes de centro', MyCentreView::html( MyCentre::model() ) );

		$this->acting_as( $this->school_head() );
		$this->assertStringContainsString( 'no tiene centro asignado', MyCentreView::html( MyCentre::model() ) );

		$this->acting_as( $this->school_head( 'C0001' ) );
		$html = MyCentreView::html( MyCentre::model() );
		$this->assertStringContainsString( 'todavía no ha presentado ninguna solicitud', $html );
		$this->assertStringContainsString( 'Centro C0001', $html, 'sin catálogo, el código' );
		$this->assertStringNotContainsString( 'prc-tabla', $html );
	}

	// ─── las piezas compartidas ────────────────────────────────────────────

	/**
	 * El botón de icono lleva su texto para quien no ve el icono, y escapado.
	 */
	public function test_the_icon_link_carries_its_text() {
		$html = PanelParts::icon_link( 'https://example.org/taller?a=1&b=2', 'lapiz', 'Abrir el <taller>' );

		$this->assertStringContainsString( '<svg', $html );
		$this->assertStringContainsString( 'screen-reader-text', $html );
		$this->assertStringContainsString( 'Abrir el &lt;taller&gt;', $html );
		$this->assertStringNotContainsString( 'Abrir el <taller>', $html );
		$this->assertStringContainsString( 'a=1&#038;b=2', $html );

		$this->assertSame( '', PanelParts::icon_link( '', 'lapiz', 'A ningún sitio' ), 'sin dirección no hay enlace' );
	}

	/**
	 * El armazón se sirve entero: todas las hojas de `assets/css/` viajan
	 * dentro del documento, con la del armazón delante.
	 *
	 * En producción no hay directorio desde el que servir un fichero, así que
	 * si una hoja se queda fuera de esta lista no se carga en ningún sitio: la
	 * pantalla sale sin la mitad de su estilo y nada falla por el camino.
	 */
	public function test_the_whole_stylesheet_is_served_inline() {
		$hojas = Assets::files( 'css' );
		$this->assertSame( 'css/prc-app.css', $hojas[0], 'el armazón va primero: el resto se monta encima' );
		foreach ( (array) glob( dirname( __DIR__, 2 ) . '/assets/css/*.css' ) as $ruta ) {
			$this->assertContains( 'css/' . basename( (string) $ruta ), $hojas, 'ninguna hoja se queda fuera' );
		}
		$this->assertSame( 'js/prc-app.js', Assets::files( 'js' )[0] );

		// Y con la lista que inlinea el empaquetador: el armazón primero y las
		// de cada pantalla detrás por orden alfabético, que es lo que decide
		// qué regla gana cuando dos dicen lo mismo.
		Assets::set_inline(
			array(
				'css/prc-taller.css' => '',
				'css/prc-app.css'    => '',
				'css/prc-ficha.css'  => '',
				'js/prc-app.js'      => '',
			)
		);
		$this->assertSame(
			array( 'css/prc-app.css', 'css/prc-ficha.css', 'css/prc-taller.css' ),
			Assets::files( 'css' )
		);
		Assets::set_inline( array() );

		// Y la pantalla se encola con la hoja dentro, no con un enlace a un
		// fichero que en producción no existe.
		$GLOBALS['wp_styles'] = null;
		$this->acting_as( $this->administrator() );
		$this->go_to( (string) get_permalink( get_page_by_path( Shell::SLUGS['home'] ) ) );
		Assets::enqueue();

		$en_linea = implode( '', (array) wp_styles()->get_data( 'prc-app', 'after' ) );
		$this->assertStringContainsString( '--prc-fila-max', $en_linea, 'los tokens' );
		$this->assertStringContainsString( '.prc-tab', $en_linea, 'y el armazón' );
	}

	/**
	 * Las tipografías se piden aparte, y la petición no lleva la dirección de
	 * la página detrás.
	 *
	 * No llevan `integrity`: el servicio devuelve un CSS distinto según el
	 * navegador que lo pide, así que no hay un hash que valga para todos.
	 */
	public function test_the_fonts_are_asked_for_without_the_page_behind() {
		$this->assertArrayHasKey( 'prc-fuentes', Assets::fonts() );
		$this->assertArrayHasKey( 'prc-iconos', Assets::fonts() );

		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- es la etiqueta que recibe el filtro, no una hoja que se cargue aquí.
		$etiqueta = '<link rel="stylesheet" href="https://example.org/fuentes.css" />';
		$this->assertStringContainsString( 'referrerpolicy="no-referrer"', Assets::font_tag( $etiqueta, 'prc-fuentes' ) );
		$this->assertSame( $etiqueta, Assets::font_tag( $etiqueta, 'otra-hoja' ), 'y a lo demás no se le toca la etiqueta' );

		// Quien no quiera pedirle nada a un tercero las quita o las reapunta.
		add_filter( 'prc_fonts', '__return_empty_array' );
		$this->assertSame( array(), Assets::fonts() );
	}

	/**
	 * Un día se escribe como se lee en voz alta, y lo que no es un día se calla.
	 */
	public function test_a_day_is_written_the_way_it_is_read() {
		$this->assertSame( 'Sin fecha', PanelParts::day( '' ) );
		// El nombre del mes lo pone el idioma del sitio; la forma, no.
		$this->assertMatchesRegularExpression( '/^2 de \S+ de 2026$/u', PanelParts::day( '2026-11-02' ) );
		$this->assertSame( 'no-es-una-fecha', PanelParts::day( 'no-es-una-fecha' ) );
	}
}
