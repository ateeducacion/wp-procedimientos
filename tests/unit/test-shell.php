<?php
/**
 * Tests for the Shell: sections per profile, URLs, chrome, notices and the exit seam.
 *
 * @package Prc
 */

use Prc\Access\ProcedureAccess;
use Prc\App;
use Prc\PublicFront\ExitSignal;
use Prc\PublicFront\Shell;

/**
 * El armazón que comparten todas las pantallas.
 *
 * Es la única lista de secciones del aplicativo, la única forma de resolver
 * una dirección y el único `exit`: si algo de esto se tuerce, se tuercen las
 * cinco pantallas a la vez. Y es quien sirve el documento entero, sin el tema
 * (ADR-0022), así que aquí se comprueba también que no se cuela nada sin
 * escapar por el camino.
 */
class Test_Shell extends WP_UnitTestCase {

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

	// ─── las secciones ─────────────────────────────────────────────────────

	/**
	 * Cada perfil ve sus secciones, y quien no tiene el ámbito o el centro
	 * solo ve la portada: falla en cerrado.
	 */
	public function test_sections_per_profile() {
		$this->acting_as( 0 );
		$this->assertSame( array(), Shell::sections(), 'sin sesión no hay secciones' );

		$this->acting_as( (int) self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->assertSame( array( 'home' ), array_keys( Shell::sections() ), 'la portada la ve cualquiera' );

		// Con el rol pero sin ámbito en el perfil, la gestión no se ofrece.
		$huerfano = $this->manager();
		$this->acting_as( $huerfano );
		$this->assertFalse( ProcedureAccess::can_manage_procedures( $huerfano ) );
		$this->assertSame( array( 'home' ), array_keys( Shell::sections() ) );

		$this->acting_as( $this->manager( array( $this->area( 'Área de prueba' ) ) ) );
		$this->assertSame( array( 'home', 'workspace' ), array_keys( Shell::sections() ) );

		// Y con el rol del equipo directivo pero sin código de centro, tampoco.
		$this->acting_as( $this->school_head() );
		$this->assertSame( array( 'home' ), array_keys( Shell::sections() ) );

		$this->acting_as( $this->school_head( 'C0001' ) );
		$this->assertSame( array( 'home', 'mine' ), array_keys( Shell::sections() ) );

		// Administración gestiona todos los ámbitos, pero no tiene centro con
		// el que solicitar: «Mi centro» no es suya.
		$this->acting_as( $this->administrator() );
		$this->assertSame( array( 'home', 'workspace' ), array_keys( Shell::sections() ) );
	}

	/**
	 * La sección cuya página todavía no existe no se ofrece: llevaría a un 404.
	 */
	public function test_a_section_without_its_page_is_not_offered() {
		$this->acting_as( $this->manager( array( $this->area( 'Área de prueba' ) ) ) );

		add_filter(
			'prc_page_slug',
			static function ( string $slug, string $seccion ): string {
				return 'workspace' === $seccion ? 'una-pagina-que-no-existe' : $slug;
			},
			20,
			2
		);

		$this->assertSame( '', Shell::url( 'workspace' ) );
		$this->assertSame( array( 'home' ), array_keys( Shell::sections() ) );
	}

	// ─── las direcciones ───────────────────────────────────────────────────

	/**
	 * `url()` resuelve por slug, admite parámetros y se puede reapuntar.
	 */
	public function test_url_resolves_the_page_of_a_section() {
		$pagina = get_page_by_path( Shell::SLUGS['home'] );
		$this->assertInstanceOf( WP_Post::class, $pagina );
		$this->assertSame( get_permalink( $pagina ), Shell::url( 'home' ) );

		$this->assertSame( '', Shell::url( 'no-existe' ), 'una sección inventada no tiene dirección' );

		$con_args = Shell::url( 'apply', array( Shell::ARG_PROCEDURE => 7 ) );
		$this->assertStringContainsString( Shell::ARG_PROCEDURE . '=7', $con_args );
		$this->assertStringContainsString(
			(string) get_permalink( get_page_by_path( Shell::SLUGS['apply'] ) ),
			$con_args,
			'los parámetros se le añaden a la página de la sección'
		);

		// Quien despliega puede colgar la página de otra dirección sin tocar código.
		$otra = (int) self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_name'   => 'tramites-de-centro',
				'post_title'  => 'Trámites de centro',
			)
		);
		add_filter(
			'prc_page_slug',
			static function ( string $slug, string $seccion ): string {
				return 'workspace' === $seccion ? 'tramites-de-centro' : $slug;
			},
			20,
			2
		);
		$this->assertSame( get_permalink( $otra ), Shell::url( 'workspace' ) );
	}

	/**
	 * Y las páginas pueden colgar de una madre: la opción la escribe quien
	 * despliega y el armazón resuelve la ruta entera.
	 *
	 * Sin esto, `get_page_by_path()` buscaría «solicitud» en la raíz del sitio
	 * y la hija de «/tramites/solicitud» no aparecería: media pantalla se
	 * quedaría sin sus botones.
	 */
	public function test_the_pages_can_hang_from_a_parent_page() {
		$madre = (int) self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_name'   => 'tramites',
				'post_title'  => 'Trámites',
			)
		);
		$hija  = (int) self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_parent'  => $madre,
				'post_name'    => Shell::SLUGS['apply'],
				'post_title'   => 'Solicitud',
				'post_content' => '[' . Shell::SHORTCODES['apply'] . ']',
			)
		);

		update_option( App::PAGES_PARENT, '/tramites/' );

		$this->assertSame( (string) get_permalink( $hija ), Shell::url( 'apply' ) );
		$this->assertNotSame( (string) get_permalink( get_page_by_path( Shell::SLUGS['apply'] ) ), Shell::url( 'apply' ), 'y no la que cuelga de la raíz' );
	}

	// ─── el marco ──────────────────────────────────────────────────────────

	/**
	 * `render()` pinta la cabecera, el título, el cuerpo y el pie.
	 */
	public function test_render_paints_the_chrome_around_the_screen() {
		$this->acting_as( $this->manager( array( $this->area( 'Área de prueba' ) ) ) );

		$html = Shell::render( 'Procedimientos', 'Solo los de su ámbito.', '<p id="cuerpo">Contenido</p>' );

		$this->assertStringContainsString( '<h1 class="prc-h1">Procedimientos</h1>', $html );
		$this->assertStringContainsString( 'Solo los de su ámbito.', $html );
		$this->assertStringContainsString( '<p id="cuerpo">Contenido</p>', $html );
		$this->assertStringContainsString( 'Gestión de procedimientos', $html, 'la cabecera dice con qué perfil se mira' );
		$this->assertStringContainsString( 'Área de prueba', $html, 'y con qué ámbito' );
		$this->assertStringContainsString( 'prc-pie', $html );
	}

	/**
	 * El rótulo de la organización y el pie salen del filtro, y **están vacíos
	 * por defecto**: este repositorio no lleva dentro la marca de nadie
	 * (ADR-0009).
	 */
	public function test_the_chrome_carries_nothing_of_anybody_until_it_is_configured() {
		$this->acting_as( $this->administrator() );

		$limpio = Shell::render( 'Procedimientos', '', '<p>cuerpo</p>' );
		$this->assertStringNotContainsString( 'prc-logo', $limpio );
		$this->assertStringNotContainsString( 'prc-marca"', $limpio );
		$this->assertStringNotContainsString( 'prc-pie-credito', $limpio );

		add_filter(
			'prc_chrome',
			static function ( array $chrome ): array {
				return array_merge(
					$chrome,
					array(
						'owner'        => 'Organización de ejemplo',
						'owner_url'    => 'https://example.org/',
						'org'          => 'Servicio de ejemplo',
						'credit'       => 'Entorno de desarrollo',
						'footer_links' => array(
							array(
								'label' => 'Aviso legal',
								'url'   => 'https://example.org/aviso-legal',
							),
						),
					)
				);
			}
		);

		$puesto = Shell::render( 'Procedimientos', '', '<p>cuerpo</p>' );
		$this->assertStringContainsString( 'Organización de ejemplo', $puesto );
		$this->assertStringContainsString( 'Servicio de ejemplo', $puesto );
		$this->assertStringContainsString( 'Entorno de desarrollo', $puesto );
		$this->assertStringContainsString( 'example.org/aviso-legal', $puesto );
	}

	/**
	 * El título y el subtítulo se escapan, y sin ellos no se pinta su etiqueta.
	 */
	public function test_render_escapes_what_it_is_given() {
		$this->acting_as( $this->administrator() );

		$html = Shell::render( '<b>Título</b>', '', '<p>cuerpo</p>' );

		$this->assertStringContainsString( '&lt;b&gt;Título&lt;/b&gt;', $html );
		$this->assertStringNotContainsString( '<b>Título</b>', $html );
		$this->assertStringNotContainsString( 'prc-sub', $html, 'sin subtítulo no hay párrafo vacío' );
	}

	/**
	 * Con el filtro en «no», el armazón se aparta y solo queda la pantalla.
	 */
	public function test_the_chrome_can_be_switched_off() {
		$this->acting_as( $this->administrator() );
		add_filter( 'prc_show_chrome', '__return_false' );

		$this->assertSame( '<div class="prc-hoja"><p>cuerpo</p></div>', Shell::render( 'Procedimientos', 'Algo', '<p>cuerpo</p>' ) );
	}

	/**
	 * Con una sola entrada no hay nada que elegir: no se pinta el menú.
	 */
	public function test_the_tabs_only_appear_when_there_is_something_to_choose() {
		$this->acting_as( (int) self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->assertStringNotContainsString( 'prc-tabs', Shell::render( 'Procedimientos', '', '' ) );

		$this->acting_as( $this->manager( array( $this->area( 'Área de prueba' ) ) ) );
		$html = Shell::render( 'Procedimientos', '', '' );
		$this->assertStringContainsString( 'prc-tabs', $html );
		// El rótulo de la pestaña era «Gestión»; ahora la entrada de primer
		// nivel es «Administrar» y lo que lleva al taller cuelga de ella.
		$this->assertStringContainsString( '>Gestionar procedimientos<', $html );
	}

	/**
	 * El menú es el del sitio que se sustituye —Inicio, Sus solicitudes,
	 * Administrar y Ayuda—, pero de cada entrada solo se pinta lo que esa
	 * persona puede abrir, y la que se queda sin nada dentro no se pinta.
	 *
	 * Esconder una entrada no es control de acceso: cada pantalla comprueba su
	 * capacidad al servirse. Esto es la ayuda para navegar.
	 */
	public function test_the_menu_paints_only_what_each_profile_can_open() {
		// Equipo directivo: lo suyo son las solicitudes de su centro.
		$this->acting_as( $this->school_head( 'C0001' ) );
		$html = Shell::render( 'Mi centro', '', '' );
		$this->assertStringContainsString( '>Inicio<', $html );
		$this->assertStringContainsString( '>Sus solicitudes<', $html );
		$this->assertStringContainsString( '>De su centro<', $html );
		$this->assertStringNotContainsString( '>Administrar<', $html );

		// Gestión: administrar. «Sus solicitudes» no, que no tiene centro con
		// el que solicitar.
		$this->acting_as( $this->manager( array( $this->area( 'Área de prueba' ) ) ) );
		$html = Shell::render( 'Gestión de procedimientos', '', '' );
		$this->assertStringContainsString( '>Administrar<', $html );
		$this->assertStringContainsString( '>Gestión de procedimientos<', $html, 'el rótulo del grupo del desplegable' );
		$this->assertStringContainsString( '>Crear procedimiento<', $html );
		$this->assertStringContainsString( '>Gestionar procedimientos<', $html );
		$this->assertStringNotContainsString( '>Sus solicitudes<', $html );

		// Y sin sesión no hay menú: no hay nada que ofrecer.
		$this->acting_as( 0 );
		$this->assertStringNotContainsString( 'prc-tabs', Shell::render( 'Procedimientos', '', '' ) );
	}

	/**
	 * «Ayuda» es de quien despliega: sin configurarla no existe (ADR-0009).
	 */
	public function test_the_help_entry_is_what_the_deployment_configured() {
		$this->acting_as( $this->school_head( 'C0001' ) );
		$this->assertStringNotContainsString( '>Ayuda<', Shell::render( 'Mi centro', '', '' ) );

		add_filter(
			'prc_chrome',
			static function ( array $chrome ): array {
				return array_merge(
					$chrome,
					array(
						'footer_links' => array(
							array(
								'label' => 'Incidencias técnicas',
								'url'   => 'https://example.org/ayuda',
							),
						),
					)
				);
			}
		);

		$html = Shell::render( 'Mi centro', '', '' );
		$this->assertStringContainsString( '>Ayuda<', $html );
		$this->assertStringContainsString( '>Incidencias técnicas<', $html );
		$this->assertStringContainsString( 'example.org/ayuda', $html );
	}

	/**
	 * Dónde se está se marca dos veces, como en el original: la entrada de
	 * primer nivel que contiene la página, y dentro la que **es** la página.
	 */
	public function test_the_menu_marks_where_you_are() {
		$this->acting_as( $this->manager( array( $this->area( 'Área de prueba' ) ) ) );
		$this->go_to( (string) get_permalink( get_page_by_path( Shell::SLUGS['workspace'] ) ) );

		$html = Shell::render( 'Gestión de procedimientos', '', '' );
		$this->assertStringContainsString( '<summary class="prc-tab prc-tab-on">Administrar</summary>', $html );
		$this->assertMatchesRegularExpression(
			'/aria-current="page"\s+href="' . preg_quote( esc_url( Shell::url( 'workspace' ) ), '/' ) . '">Gestionar procedimientos</',
			$html
		);

		// En la portada la marca se va con ella: «Inicio» es la entrada, y
		// «Administrar» deja de estar resaltada.
		$this->go_to( (string) get_permalink( get_page_by_path( Shell::SLUGS['home'] ) ) );
		$html = Shell::render( 'Procedimientos', '', '' );
		$this->assertStringContainsString( 'class="prc-tab prc-tab-on"', $html );
		$this->assertStringNotContainsString( '<summary class="prc-tab prc-tab-on">', $html );
	}

	/**
	 * El héroe y el `h1` corriente son dos componentes, no uno con variantes.
	 */
	public function test_the_hero_is_not_the_ordinary_heading() {
		$this->acting_as( $this->administrator() );

		$this->assertStringContainsString( '<h1 class="prc-h1">Mi centro</h1>', Shell::render( 'Mi centro', '', '' ) );
		$this->assertStringContainsString( '<h1 class="prc-hero">Procedimientos</h1>', Shell::render( 'Procedimientos', '', '', true ) );
	}

	/**
	 * El perfil de la cabecera: el cargo y el ámbito o el centro con el que se mira.
	 */
	public function test_the_profile_says_the_role_and_the_scope() {
		$this->acting_as( 0 );
		$this->assertSame(
			array(
				'role'  => '',
				'scope' => '',
			),
			Shell::profile()
		);

		$area = $this->area( 'Área de prueba' );
		$this->acting_as( $this->manager( array( $area ) ) );
		$this->assertSame( 'Gestión de procedimientos', Shell::profile()['role'] );
		$this->assertSame( 'Área de prueba', Shell::profile()['scope'] );

		$this->acting_as( $this->manager() );
		$this->assertSame( 'Sin ámbito asignado', Shell::profile()['scope'] );

		$this->acting_as( $this->school_head( 'C0001' ) );
		$this->assertSame( 'Equipo directivo', Shell::profile()['role'] );
		$this->assertSame( 'C0001', Shell::profile()['scope'], 'sin catálogo, el código' );

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
		$this->assertSame( 'Centro de Educación Norte (C0001)', Shell::centre_name( get_current_user_id() ) );

		$this->acting_as( $this->school_head() );
		$this->assertSame( 'Sin centro asignado', Shell::profile()['scope'] );

		$this->acting_as( $this->administrator() );
		$this->assertSame( 'Administración', Shell::profile()['role'] );
		$this->assertSame( 'Todos los ámbitos', Shell::profile()['scope'] );
	}

	/**
	 * Las iniciales del avatar salen del nombre, no de los signos que lo rodean.
	 *
	 * Los nombres que se muestran traen el cargo entre paréntesis y partiendo
	 * por espacios la segunda inicial era el propio paréntesis.
	 */
	public function test_the_avatar_initials_skip_the_punctuation() {
		$quien = $this->manager( array( $this->area( 'Área de prueba' ) ) );
		wp_update_user(
			array(
				'ID'           => $quien,
				'display_name' => 'Gestión (Área de prueba)',
			)
		);
		$this->acting_as( $quien );

		$html = Shell::render( 'Procedimientos', '', '<p>cuerpo</p>' );
		$this->assertMatchesRegularExpression( '/prc-yo-ava[^>]*>\s*GÁ\s*</u', $html );
		$this->assertStringNotContainsString( '>G(<', $html );
	}

	// ─── avisos, recuadros e iconos ────────────────────────────────────────

	/**
	 * Los avisos: su tono, su texto escapado, y ninguno cuando no hay nada que decir.
	 */
	public function test_the_notices_carry_their_tone() {
		$this->assertStringContainsString( 'alert-success', Shell::notice( 'ok', 'Guardado.' ) );
		$this->assertStringContainsString( 'Guardado.', Shell::notice( 'ok', 'Guardado.' ) );
		$this->assertStringContainsString( 'alert-warning', Shell::notice( 'aviso', 'Cuidado.' ) );
		$this->assertStringContainsString( 'alert-danger', Shell::notice( 'error', 'No se pudo.' ) );
		$this->assertStringContainsString( 'alert-info', Shell::notice( 'lo-que-sea', 'Nota.' ) );

		$this->assertSame( '', Shell::notice( 'ok', '' ), 'sin texto no hay aviso' );
		$this->assertStringContainsString( '&lt;script&gt;', Shell::notice( 'ok', '<script>alert(1)</script>' ) );
		$this->assertStringNotContainsString( '<script>', Shell::notice( 'ok', '<script>alert(1)</script>' ) );
	}

	/**
	 * El recuadro amarillo dice en palabras por qué solo lo ve una persona.
	 */
	public function test_the_admin_box_says_why_only_one_person_sees_it() {
		$html = Shell::admin_box( '<b>Reabrir</b>', '<p id="dentro">Un botón</p>' );

		$this->assertStringContainsString( 'Solo administración', $html );
		$this->assertStringContainsString( Shell::ADMIN_BOX_WHY, $html );
		$this->assertStringContainsString( '&lt;b&gt;Reabrir&lt;/b&gt;', $html, 'el título se escapa' );
		$this->assertStringContainsString( '<p id="dentro">Un botón</p>', $html, 'el cuerpo llega ya escapado' );

		$this->assertStringContainsString( 'Porque lo dice la ley.', Shell::admin_box( 'Reabrir', '', 'Porque lo dice la ley.' ) );
	}

	/**
	 * Los iconos van en línea, sin texto y sin inventarse ninguno.
	 */
	public function test_the_icons_are_inline_and_closed() {
		$this->assertStringContainsString( '<svg', Shell::icon( 'lapiz' ) );
		$this->assertStringContainsString( 'aria-hidden="true"', Shell::icon( 'ojo' ) );
		$this->assertSame( '', Shell::icon( 'un-icono-que-no-existe' ) );
		$this->assertStringContainsString( '<svg', Shell::icon_plus() );
	}

	// ─── la costura de salida ──────────────────────────────────────────────

	/**
	 * `leave()` es la costura de salida: bajo el filtro lanza ExitSignal con la URL.
	 */
	public function test_leave_throws_the_exit_signal() {
		$destino = home_url( '/gestion-de-procedimientos/?aviso=creado' );

		$this->assertSame(
			$destino,
			$this->exit_url(
				static function () use ( $destino ): void {
					Shell::leave( $destino );
				}
			)
		);

		// Sin URL: se acaba de servir un documento y no hay a dónde ir.
		$this->assertSame(
			'',
			$this->exit_url(
				static function (): void {
					Shell::leave();
				}
			)
		);

		// Y la excepción lleva la dirección para quien la mire de cerca.
		add_filter( 'prc_exit_throws', '__return_true' );
		try {
			Shell::leave( $destino );
			$this->fail( 'leave() tenía que haber lanzado ExitSignal' );
		} catch ( ExitSignal $e ) {
			$this->assertSame( $destino, $e->url );
		}
	}

	/**
	 * `back_url()` nunca devuelve a la portada del sitio: vuelve al aplicativo.
	 */
	public function test_back_url_falls_back_to_the_application() {
		$this->acting_as( $this->administrator() );

		$this->assertSame( Shell::url( 'workspace' ), Shell::back_url( 'workspace' ) );

		$_REQUEST['_wp_http_referer'] = '/gestion-de-procedimientos/?prc_filter_state=open';
		$this->assertStringContainsString( 'prc_filter_state=open', (string) Shell::back_url( 'workspace' ) );
	}

	// ─── la petición ───────────────────────────────────────────────────────

	/**
	 * Sin sesión, una pantalla privada manda al acceso; la portada y la ficha, no.
	 */
	public function test_only_the_private_pages_send_you_to_the_login() {
		$this->acting_as( 0 );

		$gestion = get_page_by_path( Shell::SLUGS['workspace'] );
		$this->go_to( (string) get_permalink( $gestion ) );
		$this->assertTrue( Shell::is_app_page() );
		$this->assertSame( 'workspace', Shell::current_section() );
		$this->assertFalse( Shell::is_public( 'workspace' ) );
		$this->assertSame(
			wp_login_url( (string) get_permalink( $gestion ) ),
			$this->exit_url( array( Shell::class, 'require_login' ) )
		);

		// La portada es pública: se lee sin sesión.
		$this->go_to( (string) get_permalink( get_page_by_path( Shell::SLUGS['home'] ) ) );
		$this->assertTrue( Shell::is_public( Shell::current_section() ) );
		$this->assertNull( $this->exit_url( array( Shell::class, 'require_login' ) ) );

		// Y la ficha de un procedimiento también, que es su `single`.
		$procedimiento = $this->procedure( $this->administrator(), array( $this->area( 'Área de prueba' ) ) );
		$this->acting_as( 0 );
		$this->go_to( (string) get_permalink( $procedimiento ) );
		$this->assertSame( Shell::SECTION_PROCEDURE, Shell::current_section() );
		$this->assertNull( $this->exit_url( array( Shell::class, 'require_login' ) ) );

		// Con sesión no se va a ninguna parte.
		$this->acting_as( $this->administrator() );
		$this->go_to( (string) get_permalink( $gestion ) );
		$this->assertNull( $this->exit_url( array( Shell::class, 'require_login' ) ) );
	}

	/**
	 * La pantalla se sirve sola: el documento entero, y sin la plantilla del tema.
	 */
	public function test_the_application_serves_its_own_document() {
		$this->acting_as( $this->administrator() );
		$pagina = get_page_by_path( Shell::SLUGS['home'] );
		$this->go_to( (string) get_permalink( $pagina ) );

		$documento = $this->served( array( Shell::class, 'render_standalone' ) );

		$this->assertStringContainsString( '<!doctype html>', $documento );
		$this->assertStringContainsString( '<body ', $documento );
		$this->assertStringContainsString( '</html>', $documento );
		$this->assertStringContainsString( 'prc-app', $documento, 'la clase que marca nuestras páginas' );
		$this->assertStringContainsString( 'prc-hoja', $documento, 'y la pantalla dentro' );

		// Fuera del aplicativo, la plantilla del tema sigue mandando.
		$otra = (int) self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);
		$this->go_to( (string) get_permalink( $otra ) );
		$this->assertNull( $this->exit_url( array( Shell::class, 'render_standalone' ) ) );

		// Y con el filtro en «no», tampoco se sirve solo.
		$this->go_to( (string) get_permalink( $pagina ) );
		add_filter( 'prc_standalone_page', '__return_false' );
		$this->assertNull( $this->exit_url( array( Shell::class, 'render_standalone' ) ) );
	}

	/**
	 * El documento no se cuela con salida sin escapar: lo que trae el título
	 * de la página sale escapado.
	 */
	public function test_the_document_escapes_what_the_page_carries() {
		$this->acting_as( $this->administrator() );

		$documento = Shell::document( '<p id="cuerpo">Contenido</p>' );

		$this->assertStringContainsString( '<p id="cuerpo">Contenido</p>', $documento, 'el cuerpo llega ya escapado' );
		$this->assertStringNotContainsString( '<script>alert', $documento );

		$html = Shell::render( 'Procedimientos', '"><script>alert(1)</script>', '' );
		$this->assertStringNotContainsString( '<script>alert(1)</script>', $html );
		$this->assertStringContainsString( '&lt;script&gt;', $html );
	}

	/**
	 * Lo que trae el tema no se encola en nuestras pantallas; lo demás, sí.
	 */
	public function test_theme_assets_are_dropped_on_app_pages() {
		$this->acting_as( $this->administrator() );
		$this->go_to( (string) get_permalink( get_page_by_path( Shell::SLUGS['home'] ) ) );

		wp_enqueue_style( 'un-tema', 'https://example.org/wp-content/themes/un-tema/style.css', array(), '1' );
		wp_enqueue_style( 'una-cache', 'https://example.org/wp-content/et-cache/1/un-tema.css', array(), '1' );
		wp_enqueue_style( 'un-plugin', 'https://example.org/wp-content/plugins/algo/estilo.css', array(), '1' );
		wp_enqueue_style( 'wp-block-library', 'https://example.org/wp-includes/css/dist/block-library/style.css', array(), '1' );
		add_action( 'wp_head', 'print_emoji_detection_script', 7 );

		Shell::drop_theme_assets();

		$this->assertFalse( wp_style_is( 'un-tema', 'enqueued' ) );
		$this->assertFalse( wp_style_is( 'una-cache', 'enqueued' ), 'la caché del constructor del tema también es del tema' );
		$this->assertFalse( wp_style_is( 'wp-block-library', 'enqueued' ) );
		$this->assertFalse( has_action( 'wp_head', 'print_emoji_detection_script' ) );
		$this->assertTrue( wp_style_is( 'un-plugin', 'enqueued' ), 'lo que no es del tema se queda' );

		// Y la red de al escribir la etiqueta, para lo que se encole más tarde.
		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- es la etiqueta que recibe el filtro, no una hoja que se cargue aquí.
		$etiqueta = '<link rel="stylesheet" href="x">';
		$this->assertSame( '', Shell::drop_theme_tag( $etiqueta, 'x', 'https://example.org/wp-content/et-cache/1/tarde.css' ) );
		$this->assertSame( $etiqueta, Shell::drop_theme_tag( $etiqueta, 'x', 'https://example.org/wp-content/plugins/algo/estilo.css' ) );
	}

	/**
	 * Fuera del aplicativo no se toca la cola de nadie.
	 */
	public function test_outside_the_app_nothing_is_dropped() {
		$this->acting_as( $this->administrator() );
		$otra = (int) self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => 'Una página del sitio.',
			)
		);
		$this->go_to( (string) get_permalink( $otra ) );

		$this->assertFalse( Shell::is_app_page() );
		$this->assertSame( '', Shell::current_section() );

		wp_enqueue_style( 'otro-tema', 'https://example.org/wp-content/themes/un-tema/otro.css', array(), '1' );
		Shell::drop_theme_assets();
		$this->assertTrue( wp_style_is( 'otro-tema', 'enqueued' ) );

		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- la etiqueta que recibe el filtro.
		$etiqueta = '<link rel="stylesheet" href="x">';
		$this->assertSame(
			$etiqueta,
			Shell::drop_theme_tag( $etiqueta, 'x', 'https://example.org/wp-content/themes/un-tema/otro.css' )
		);
		$this->assertNotContains( 'prc-app', Shell::body_class( array() ) );
	}

	/**
	 * La sección se calcula una vez por contenido, no una vez por etiqueta impresa.
	 *
	 * `drop_theme_tag()` la pregunta por cada <link> y <script>. Si el
	 * shortcode desaparece del registro después de la primera pregunta, la
	 * respuesta recordada sigue siendo la misma; con otro contenido, se vuelve
	 * a mirar.
	 */
	public function test_current_section_is_remembered_per_content() {
		$this->acting_as( $this->administrator() );
		$pagina = (int) self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_content' => '[prc_workspace] ' . uniqid( 'memo-', true ),
			)
		);
		$this->go_to( (string) get_permalink( $pagina ) );
		$this->assertSame( 'workspace', Shell::current_section() );

		global $shortcode_tags;
		$registrado = $shortcode_tags['prc_workspace'];
		remove_shortcode( 'prc_workspace' );
		try {
			$this->assertSame( 'workspace', Shell::current_section(), 'la segunda pregunta no vuelve a pasar la expresión' );

			// Otro contenido es otra clave: se mira de nuevo, y ya no hay shortcode.
			$GLOBALS['post']->post_content = '[prc_workspace] ' . uniqid( 'memo-', true );
			$this->assertSame( '', Shell::current_section() );
		} finally {
			add_shortcode( 'prc_workspace', $registrado );
		}
	}

	/**
	 * La barra de administración solo la ve quien administra.
	 */
	public function test_the_toolbar_is_only_for_administrators() {
		$this->acting_as( $this->manager( array( $this->area( 'Área de prueba' ) ) ) );
		$this->assertFalse( Shell::show_admin_bar() );

		$this->acting_as( $this->administrator() );
		$this->assertTrue( Shell::show_admin_bar() );
	}

	/**
	 * El armazón engancha lo que sirve la pantalla sola y lo que quita el tema.
	 */
	public function test_register_hooks_the_standalone_render() {
		Shell::register();

		$this->assertNotFalse( has_action( 'template_redirect', array( Shell::class, 'require_login' ) ) );
		$this->assertSame( 20, has_action( 'template_redirect', array( Shell::class, 'render_standalone' ) ) );
		$this->assertSame( 100, has_action( 'wp_enqueue_scripts', array( Shell::class, 'drop_theme_assets' ) ) );
		$this->assertNotFalse( has_filter( 'style_loader_tag', array( Shell::class, 'drop_theme_tag' ) ) );
		$this->assertNotFalse( has_filter( 'script_loader_tag', array( Shell::class, 'drop_theme_tag' ) ) );
		$this->assertNotFalse( has_filter( 'body_class', array( Shell::class, 'body_class' ) ) );
	}
}
