<?php
/**
 * The public page of a procedure, and the chrome whoever deploys configures.
 *
 * @package Prc
 */

namespace Prc\PublicFront\View;

use Prc\Meta\ProcedureMetaKeys;
use Prc\PublicFront\Assets;

/**
 * La ficha pública de un procedimiento: lo que ve quien la visita.
 *
 * Solo pinta: no lee la petición, no consulta, no decide. Todo lo que sale
 * de aquí llega ya escapado y lo monta {@see \Prc\PublicFront\ProcedureView}
 * dentro del marco del armazón ({@see \Prc\PublicFront\Shell::render()}).
 * Aquí no hay secciones ni bloques: una convocatoria es una ficha.
 *
 * Y lo que un tema serviría y el armazón tuvo que reponer al servir la página
 * solo: el pie, el aviso de cookies ({@see consent()}) y la analítica
 * ({@see analytics()}). **Ninguno trae nada dentro**: son de quien despliega
 * y se configuran con el filtro {@see chrome()}; sin configurar, no se pintan
 * (ADR-0009).
 */
final class ProcedureChrome {

	/**
	 * Filter that supplies the chrome of the application.
	 *
	 * **Este repositorio no trae ni una dirección institucional.** El pie, el
	 * aviso de cookies y la analítica son de quien despliega, no del
	 * aplicativo: se rellenan desde fuera con este filtro y, sin nadie que
	 * conteste, no se pinta ninguno de los tres.
	 */
	public const HOOK = 'prc_chrome';

	/**
	 * The cookie the notice writes, when there is a notice.
	 *
	 * Es el único valor con defecto porque no identifica a nadie: es el nombre
	 * que usa la biblioteca de avisos de cookies más extendida.
	 */
	public const CONSENT_COOKIE = 'cookieconsent_status';

	/**
	 * Everything the pages need from whoever deploys them.
	 *
	 * Todo vacío por defecto, y eso es la decisión: lo que no se configura no
	 * se pinta. Así el aplicativo se publica sin llevar dentro nada de nadie y
	 * una instalación nueva no envía datos a ningún sitio sin decirlo.
	 *
	 * @return array<string, mixed>
	 */
	public static function chrome(): array {
		$defecto = array(
			// Pie y cabecera: de quién es el sitio y quién lo hizo.
			'owner'          => '',
			'owner_url'      => '',
			'org'            => '',
			'credit'         => '',
			'footer_links'   => array(),
			// Aviso de protección de datos de la pantalla de solicitud: el órgano
			// responsable y el tratamiento son del despliegue, no del aplicativo.
			'privacy_notice' => '',
			// Aviso de cookies: las tres piezas que lo pintan.
			'consent_css'    => '',
			'consent_js'     => '',
			'consent_init'   => '',
			'consent_cookie' => self::CONSENT_COOKIE,
			// Analítica: sin esto no se carga nada y no se envía ni una visita.
			'matomo_api'     => '',
			'matomo_js'      => '',
			'matomo_site'    => 0,
		);

		/**
		 * Filter the chrome of the application pages.
		 *
		 * @param array<string, mixed> $chrome Empty defaults.
		 */
		$puesto = apply_filters( self::HOOK, $defecto );

		return is_array( $puesto ) ? array_merge( $defecto, $puesto ) : $defecto;
	}

	/**
	 * The whole ficha, from its model.
	 *
	 * La cabecera es de dos columnas —57,8 % de título, resolución y promotor;
	 * 36,7 % de contador y caja resumen—, y debajo van las pestañas de
	 * contenido, la tarjeta de acceso con su botón, el filete y el pie. El
	 * orden del DOM es el que hace falta al colapsar a una columna: el
	 * contador queda **debajo** del título, no encima.
	 *
	 * @param array<string, mixed> $m What ProcedureView::model() decided.
	 * @return string
	 */
	public static function html( array $m ): string {
		ob_start();
		?>
		<div class="prc-ficha">
			<div class="prc-ficha-cab">
				<div class="prc-ficha-izq">
					<h1 class="prc-titulo-proc"><?php echo esc_html( (string) $m['title'] ); ?></h1>
					<?php
					echo self::resolution( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapado dentro.
					?>
					<?php if ( '' !== (string) $m['image'] ) : ?>
						<img class="prc-ficha-cartel" src="<?php echo esc_url( (string) $m['image'] ); ?>" alt="<?php echo esc_attr( (string) $m['image_alt'] ); ?>" />
					<?php endif; ?>
					<?php if ( '' !== (string) $m['areas'] ) : ?>
						<p class="prc-promotor"><em>Promovido por <?php echo esc_html( (string) $m['areas'] ); ?></em></p>
					<?php endif; ?>
				</div>
				<div class="prc-ficha-der">
					<?php
					echo self::countdown( $m );  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapado dentro.
					echo self::summary( $m );    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapado dentro.
					?>
				</div>
			</div>

			<?php
			echo self::tabs( $m );   // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapado dentro.
			echo self::access( $m ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapado dentro.
			?>

			<hr class="prc-filete" />
			<div class="prc-ficha-pie">
				<p class="prc-ficha-meta">
					<?php foreach ( self::footer_meta( $m ) as $linea ) : ?>
						<?php echo esc_html( $linea ); ?><br />
					<?php endforeach; ?>
				</p>
				<?php if ( '' !== (string) $m['manage_url'] ) : ?>
					<a class="<?php echo esc_attr( Assets::button_class() ); ?>" href="<?php echo esc_url( (string) $m['manage_url'] ); ?>">
						<span class="material-symbols-outlined" aria-hidden="true">edit_note</span>
						Editar procedimiento</a>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The resolution, with the link to the document when there is one.
	 *
	 * El bloque entero va en mayúsculas por CSS: el texto se guarda escrito
	 * como se escribe. El documento de la resolución es un **enlace**, no un
	 * fichero subido (ADR-0021), así que el rótulo es el del enlace y no el
	 * nombre de un fichero.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string Empty when there is neither text nor link.
	 */
	private static function resolution( array $m ): string {
		$texto  = (string) $m['excerpt'];
		$enlace = (string) $m['resolution_url'];
		if ( '' === $texto && '' === $enlace ) {
			return '';
		}

		ob_start();
		?>
		<div class="prc-caja prc-resolucion">
			<?php if ( '' !== $texto ) : ?>
				<p class="prc-resolucion__texto"><?php echo esc_html( $texto ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $enlace ) : ?>
				<p class="prc-resolucion__doc">
					<a href="<?php echo esc_url( $enlace ); ?>" title="Acceder al documento de la resolución">
						<span class="material-symbols-outlined" aria-hidden="true">quick_reference_all</span>
						<span class="prc-resolucion__archivo">Resolución del procedimiento</span>
					</a>
				</p>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The countdown, the expired notice, or nothing at all.
	 *
	 * Tres casos y **un solo hueco**, para que la página no dé saltos: con
	 * plazo vivo, las cuatro cifras y el rótulo que dice a qué plazo cuentan
	 * (mejora M2); con el plazo pasado, el aviso de vencimiento (M3); y sin
	 * plazo, nada —ni el contador ni la caja que lo envuelve—, que es mejor
	 * que cuatro ceros.
	 *
	 * Las cifras llegan calculadas del servidor: sin guion el componente se ve
	 * bien, solo se queda quieto.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function countdown( array $m ): string {
		$cuenta = (array) $m['countdown'];
		$plazo  = (string) $m['deadline_label'];
		$fecha  = (string) $m['deadline_text'];

		if ( array() === $cuenta ) {
			if ( empty( $m['expired'] ) || '' === $fecha ) {
				return '';
			}
			ob_start();
			?>
			<p class="prc-aviso prc-aviso-warning prc-cuenta-vencida">
				<span class="material-symbols-outlined" aria-hidden="true">error</span>
				El plazo de <?php echo esc_html( $plazo ); ?> terminó el <?php echo esc_html( $fecha ); ?>
			</p>
			<?php
			return (string) ob_get_clean();
		}

		$bloques = array(
			array( 'dias', 'Día', 'días' ),
			array( 'horas', 'Hrs', 'horas' ),
			array( 'minutos', 'Min', 'minutos' ),
			array( 'segundos', 'Seg', 'segundos' ),
		);

		ob_start();
		?>
		<div class="prc-caja prc-cuenta-caja">
			<p class="prc-cuenta__pie">Quedan para el cierre de <strong><?php echo esc_html( $plazo ); ?></strong></p>
			<div class="prc-cuenta" data-fin="<?php echo esc_attr( (string) $m['deadline_iso'] ); ?>" aria-live="off">
				<?php foreach ( $bloques as $i => $bloque ) : ?>
					<?php if ( $i > 0 ) : ?>
						<div class="prc-cuenta__sep" aria-hidden="true"><p>:</p></div>
					<?php endif; ?>
					<div class="prc-cuenta__bloque">
						<p class="prc-cuenta__valor" data-prc-cuenta="<?php echo esc_attr( $bloque[0] ); ?>"><?php echo esc_html( (string) $cuenta[ $bloque[0] ] ); ?></p>
						<p class="prc-cuenta__rotulo"><span aria-hidden="true"><?php echo esc_html( $bloque[1] ); ?></span><span class="screen-reader-text"><?php echo esc_html( $bloque[2] ); ?></span></p>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The summary box: the one thing everybody reads (improvement M1).
	 *
	 * El original decía una sola cosa —el plazo de inscripción— y es el punto
	 * de la pantalla que más se mira. Aquí dice el estado con su color y su
	 * icono, los plazos que existen (la fila sin dato no se pinta), los
	 * enlaces publicados y, para quien ha entrado con un centro detrás, si su
	 * centro ya solicitó. Sigue siendo la misma caja informativa: no cambia el
	 * peso visual de la pantalla, solo lo que cuenta.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function summary( array $m ): string {
		$filas = self::summary_rows( $m );
		ob_start();
		?>
		<aside class="prc-aviso prc-aviso-info prc-resumen" aria-labelledby="prc-resumen-tit">
			<h2 id="prc-resumen-tit" class="prc-resumen__tit">Resumen</h2>

			<?php if ( '' !== (string) $m['state_label'] ) : ?>
				<p class="prc-resumen__estado prc-resumen__estado--<?php echo esc_attr( sanitize_html_class( (string) $m['state'] ) ); ?>">
					<?php if ( '' !== (string) $m['state_icon'] ) : ?>
						<span class="material-symbols-outlined" aria-hidden="true"><?php echo esc_html( (string) $m['state_icon'] ); ?></span>
					<?php endif; ?>
					<?php echo esc_html( (string) $m['state_label'] ); ?>
				</p>
			<?php endif; ?>

			<?php if ( array() !== $filas ) : ?>
				<dl class="prc-resumen__datos">
					<?php foreach ( $filas as $fila ) : ?>
						<div>
							<dt><?php echo esc_html( $fila['dt'] ); ?></dt>
							<dd>
								<?php if ( '' !== $fila['mailto'] ) : ?>
									<a href="<?php echo esc_url( 'mailto:' . $fila['mailto'] ); ?>"><?php echo esc_html( $fila['dd'] ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $fila['dd'] ); ?>
								<?php endif; ?>
							</dd>
						</div>
					<?php endforeach; ?>
				</dl>
			<?php endif; ?>

			<?php if ( array() !== (array) $m['links'] ) : ?>
				<ul class="prc-resumen__enlaces">
					<?php foreach ( (array) $m['links'] as $enlace ) : ?>
						<li>
							<a href="<?php echo esc_url( (string) $enlace['url'] ); ?>">
								<span class="material-symbols-outlined" aria-hidden="true"><?php echo esc_html( (string) $enlace['icon'] ); ?></span>
								<?php echo esc_html( (string) $enlace['label'] ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php
			echo self::summary_mine( (array) $m['mine'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escapado dentro.
			?>
		</aside>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The rows of the summary list, without the ones that have no data.
	 *
	 * Nunca se pinta «Subsanación: —»: la fila sin dato no está. Es la regla
	 * de la mejora M1 y la que hace que la caja se lea de un vistazo.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return array<int, array{dt:string, dd:string, mailto:string}>
	 */
	private static function summary_rows( array $m ): array {
		$crudas = array(
			array( 'Curso', (string) $m['courses'], '' ),
			array( 'Inscripción', (string) $m['dates'], '' ),
			array( 'Subsanación', (string) $m['amend_dates'], '' ),
			array( 'Coordinación', empty( $m['requires_coordinator'] ) ? '' : 'Pide una persona coordinadora', '' ),
			array( 'Contacto', (string) $m['contact_email'], (string) $m['contact_email'] ),
		);

		$filas = array();
		foreach ( $crudas as $fila ) {
			if ( '' !== $fila[1] ) {
				$filas[] = array(
					'dt'     => $fila[0],
					'dd'     => $fila[1],
					'mailto' => $fila[2],
				);
			}
		}
		return $filas;
	}

	/**
	 * Whether the school of whoever is looking already applied.
	 *
	 * Ahorra el viaje «ficha → mis solicitudes → volver». Solo se pinta para
	 * quien ha entrado con un centro detrás; para el resto de la gente la caja
	 * termina en los enlaces.
	 *
	 * @param array<string, mixed> $mia What ProcedureView::mine() found out.
	 * @return string
	 */
	private static function summary_mine( array $mia ): string {
		if ( array() === $mia ) {
			return '';
		}
		$tiene = ! empty( $mia['has'] );

		ob_start();
		?>
		<p class="prc-resumen__mia">
			<span class="material-symbols-outlined" aria-hidden="true"><?php echo $tiene ? 'task_alt' : 'error'; ?></span>
			<?php if ( $tiene ) : ?>
				Su centro ya ha solicitado
				<?php if ( '' !== (string) $mia['date'] ) : ?>
					el <time datetime="<?php echo esc_attr( (string) $mia['iso'] ); ?>"><?php echo esc_html( (string) $mia['date'] ); ?></time>
				<?php endif; ?>
				<?php if ( '' !== (string) $mia['label'] ) : ?>
					· <strong><?php echo esc_html( (string) $mia['label'] ); ?></strong>
				<?php endif; ?>
				<a href="<?php echo esc_url( (string) $mia['url'] ); ?>">Ver mi solicitud</a>
			<?php else : ?>
				Su centro aún no ha solicitado
			<?php endif; ?>
		</p>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The content tabs, which without a script are one section after another.
	 *
	 * El servidor pinta la barra como una lista de anclas de verdad y los
	 * paneles enteros, cada uno con su rótulo visible: sin guion la ficha es
	 * larga y legible, y la barra salta a cada sección. `prc-ficha.js` la
	 * asciende a pestañas de verdad —roles, `aria-selected`, flechas— y
	 * esconde lo que no toca. Con un solo panel no hay barra que pintar.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function tabs( array $m ): string {
		$paneles = (array) $m['tabs'];
		if ( array() === $paneles ) {
			return '';
		}
		$barra = count( $paneles ) > 1;

		ob_start();
		?>
		<div class="prc-tabs-ficha">
			<?php if ( $barra ) : ?>
				<ul class="prc-tabs-ficha__barra">
					<?php foreach ( $paneles as $panel ) : ?>
						<li>
							<a id="tab-<?php echo esc_attr( (string) $panel['id'] ); ?>" href="#<?php echo esc_attr( (string) $panel['id'] ); ?>"><?php echo esc_html( (string) $panel['label'] ); ?></a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<div class="prc-tabs-ficha__paneles">
				<?php foreach ( $paneles as $panel ) : ?>
					<section class="prc-tabs-ficha__panel" id="<?php echo esc_attr( (string) $panel['id'] ); ?>" aria-labelledby="titulo-<?php echo esc_attr( (string) $panel['id'] ); ?>">
						<h2 class="prc-tabs-ficha__titulo" id="titulo-<?php echo esc_attr( (string) $panel['id'] ); ?>"><?php echo esc_html( (string) $panel['label'] ); ?></h2>
						<?php echo wp_kses_post( (string) $panel['html'] ); ?>
					</section>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The access card, with the button that says what pressing it will do.
	 *
	 * Tres columnas iguales de las que solo se rellena una: es la retícula del
	 * original. El botón es contextual (mejora M4) y **no se pinta** cuando no
	 * hay nada que hacer: sin plazo y sin solicitud, la tarjeta se queda con
	 * el texto, que es lo que hacía el original.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string
	 */
	private static function access( array $m ): string {
		$accion = (array) $m['action'];
		$icono  = (string) ( $accion['icon'] ?? '' );
		$manda  = ! empty( $accion['primary'] );
		$clases = Assets::button_class( $manda ) . ( $manda ? '' : ' prc-btn--sec' );

		ob_start();
		?>
		<div class="prc-accesos">
			<div class="prc-acceso">
				<h2 class="prc-acceso__rotulo">Acceso con la cuenta del equipo directivo</h2>
				<?php if ( '' !== (string) $m['ownership'] ) : ?>
					<p class="prc-acceso__cifra"><?php echo esc_html( (string) $m['ownership'] ); ?></p>
				<?php endif; ?>
				<p class="prc-acceso__texto">Solicita la dirección del centro, validándose con su
					<strong>cuenta personal</strong> en el enlace de acceso de la cabecera.</p>
				<?php if ( '' !== (string) $accion['url'] ) : ?>
					<p class="prc-acceso__accion">
						<a class="<?php echo esc_attr( $clases ); ?>" href="<?php echo esc_url( (string) $accion['url'] ); ?>">
							<?php if ( '' !== $icono ) : ?>
								<span class="material-symbols-outlined" aria-hidden="true"><?php echo esc_html( $icono ); ?></span>
							<?php endif; ?>
							<?php echo esc_html( (string) $accion['label'] ); ?>
						</a>
					</p>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The three lines of the footer meta: audience, ownership and state.
	 *
	 * @param array<string, mixed> $m Model.
	 * @return string[] Only the ones with something to say.
	 */
	private static function footer_meta( array $m ): array {
		$lineas = array(
			(string) $m['audience'],
			(string) $m['ownership'],
			(string) $m['state_label'],
		);
		return array_values( array_filter( $lineas, static fn( string $l ): bool => '' !== $l ) );
	}

	/**
	 * The cookie notice, for the head of the document.
	 *
	 * Al pintar el documento entero, lo que el tema metía antes de `</head>`
	 * deja de ponerse solo. En un sitio público de una administración el aviso
	 * de cookies no es opcional, así que hay dónde reponerlo — pero **las URL
	 * son de quien despliega** ({@see chrome()}) y aquí no hay ninguna.
	 *
	 * Los dos guiones van con `defer`: si uno no llega, el fallo se queda
	 * dentro de él y la página no se rompe.
	 *
	 * @return string
	 */
	public static function consent(): string {
		$chrome = self::chrome();
		$html   = '';

		// phpcs:disable WordPress.WP.EnqueuedResources -- el documento lo escribimos nosotros entero: el aviso legal no puede depender de que nadie lo saque de la cola.
		if ( '' !== (string) $chrome['consent_css'] ) {
			$html .= '<link rel="stylesheet" href="' . esc_url( (string) $chrome['consent_css'] ) . '" />' . "\n";
		}
		if ( '' !== (string) $chrome['consent_js'] ) {
			$html .= '<script src="' . esc_url( (string) $chrome['consent_js'] ) . '" defer></script>' . "\n";
		}
		if ( '' !== (string) $chrome['consent_init'] ) {
			$html .= '<script src="' . esc_url( (string) $chrome['consent_init'] ) . '" defer></script>' . "\n";
		}
		// phpcs:enable WordPress.WP.EnqueuedResources

		return $html;
	}

	/**
	 * Matomo, for the end of the body, and only when it counts for real.
	 *
	 * **No se emite nada salvo que se configure** ({@see chrome()}). Y respeta
	 * el consentimiento: con `requireCookieConsent` Matomo cuenta la visita
	 * sin escribir ni una cookie, y solo las escribe cuando el aviso dice que
	 * sí. Fuera de producción tampoco se emite: en desarrollo mandaría visitas
	 * de `localhost` a la estadística de verdad.
	 *
	 * @return string Empty when there is no site to count for.
	 */
	public static function analytics(): string {
		$chrome = self::chrome();
		$site   = (int) $chrome['matomo_site'];
		$api    = (string) $chrome['matomo_api'];
		$js_url = (string) $chrome['matomo_js'];

		/**
		 * Filter whether the analytics snippet is emitted at all.
		 *
		 * @param bool $encendida Whether to emit the analytics snippet.
		 */
		$encendida = (bool) apply_filters( 'prc_analytics_enabled', 'production' === wp_get_environment_type() );

		if ( ! $encendida || $site <= 0 || '' === $api || '' === $js_url ) {
			return '';
		}

		$cookie = (string) $chrome['consent_cookie'];
		$js     = 'var _paq=window._paq=window._paq||[];'
			. '_paq.push(["requireCookieConsent"]);'
			. ( '' !== $cookie
				? 'if(/(^|;\s*)' . $cookie . '=(allow|dismiss)(;|$)/.test(document.cookie)){_paq.push(["setCookieConsentGiven"]);}'
				: '' )
			. '_paq.push(["setTrackerUrl",' . wp_json_encode( $api, JSON_UNESCAPED_SLASHES ) . ']);'
			. '_paq.push(["setSiteId",' . $site . ']);'
			. '_paq.push(["trackPageView"]);'
			. '_paq.push(["enableLinkTracking"]);';

		// phpcs:disable WordPress.WP.EnqueuedResources -- el documento lo escribimos nosotros entero.
		$html = '<script id="prc-matomo">' . $js . '</script>' . "\n"
			. '<script src="' . esc_url( $js_url ) . '" async defer></script>' . "\n";
		// phpcs:enable WordPress.WP.EnqueuedResources
		return $html;
	}

	/**
	 * The ownerships of a procedure, written out.
	 *
	 * @param string[] $ownership Stored list, keys of ProcedureMetaKeys::ownerships().
	 * @return string
	 */
	public static function ownership_label( array $ownership ): string {
		$lista  = ProcedureMetaKeys::ownerships();
		$nombre = array();
		foreach ( $ownership as $clave ) {
			if ( isset( $lista[ $clave ] ) ) {
				$nombre[] = $lista[ $clave ];
			}
		}
		// Sin titularidad guardada se dirige a todos: es lo que hace can_apply().
		return array() === $nombre || count( $nombre ) === count( $lista ) ? 'Todos los centros' : implode( ' y ', $nombre );
	}
}
