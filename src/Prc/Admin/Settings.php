<?php
/**
 * Settings and diagnostics page for the application.
 *
 * @package Prc
 */

namespace Prc\Admin;

use Prc\Access\CentreScope;
use Prc\Access\ProcedureAccess;
use Prc\Centre\CentreCatalogue;
use Prc\Centre\CentreCatalogueSync;
use Prc\Notifications\Mailer;
use Prc\PostType\ApplicationPostType;
use Prc\PostType\ProcedurePostType;
use Prc\PublicFront\ExitSignal;
use Prc\Taxonomy\ProcedureTaxonomies;
use RuntimeException;

/**
 * Qué hay montado y qué falta, en una pantalla.
 *
 * Muestra el estado de los tipos, taxonomías, roles, acotado y catálogo
 * de centros educativos, con capacidad para forzar la sincronización manual.
 * Lo único que guarda es el correo: remitente, respuesta, copia y el párrafo
 * del alta, que son de cada instalación y no del código (ADR-0009, ADR-0038).
 */
final class Settings {

	/**
	 * Menu slug.
	 */
	public const PAGE = 'prc-settings';

	/**
	 * Nonce action for manual centre catalogue sync.
	 */
	public const NONCE_SYNC_CENTRES = 'prc_centres_manual_sync';

	/**
	 * Settings API group of the mail options.
	 */
	public const MAIL_GROUP = 'prc_mail';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'menu' ) );
		add_action( 'admin_init', array( self::class, 'handle_actions' ) );
		add_action( 'admin_init', array( self::class, 'register_settings' ) );
		// `options.php` pide `manage_options` si nadie dice otra cosa; aquí
		// manda la capacidad del aplicativo, la misma que abre la pantalla.
		add_filter( 'option_page_capability_' . self::MAIL_GROUP, array( self::class, 'capability' ) );
	}

	/**
	 * Register the mail options with their sanitisers. All empty by default.
	 *
	 * @return void
	 */
	public static function register_settings(): void {
		$saneado = array(
			Mailer::OPTION_FROM_NAME     => 'sanitize_text_field',
			Mailer::OPTION_FROM          => array( Mailer::class, 'sanitize_address' ),
			Mailer::OPTION_REPLY_TO      => array( Mailer::class, 'sanitize_address' ),
			Mailer::OPTION_COPY          => array( Mailer::class, 'sanitize_address' ),
			Mailer::OPTION_CREATED_EXTRA => 'sanitize_textarea_field',
		);
		foreach ( $saneado as $opcion => $callback ) {
			register_setting(
				self::MAIL_GROUP,
				$opcion,
				array(
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => $callback,
					'show_in_rest'      => false,
				)
			);
		}
	}

	/**
	 * The capability that saves the mail options.
	 *
	 * @return string
	 */
	public static function capability(): string {
		return ProcedureAccess::CAP_MANAGE;
	}

	/**
	 * Add the submenu page.
	 *
	 * @return void
	 */
	public static function menu(): void {
		add_submenu_page(
			'edit.php?post_type=' . ProcedurePostType::POST_TYPE,
			'Ajustes y diagnóstico de procedimientos',
			'Ajustes',
			ProcedureAccess::CAP_MANAGE,
			self::PAGE,
			array( self::class, 'render' )
		);
	}

	/**
	 * Handle manual actions like centre catalogue sync.
	 *
	 * @return void
	 */
	public static function handle_actions(): void {
		if ( ! is_admin() || ! ProcedureAccess::is_manager() ) {
			return;
		}

		if (
			isset( $_POST['prc_action'] ) &&
			'sync_centres' === $_POST['prc_action'] &&
			check_admin_referer( self::NONCE_SYNC_CENTRES, '_prc_centres_nonce' )
		) {
			$redirect_args = array(
				'post_type' => ProcedurePostType::POST_TYPE,
				'page'      => self::PAGE,
			);

			try {
				$result = CentreCatalogueSync::sync( true );
				$msg    = sprintf(
					'Catálogo actualizado correctamente (%d centros, %d activos). SHA-256: %s',
					$result['records'],
					$result['active'],
					substr( $result['sha256'], 0, 12 ) . '…'
				);

				$redirect_args['updated'] = 'synced';
				$redirect_args['msg']     = rawurlencode( $msg );
			} catch ( RuntimeException $e ) {
				$redirect_args['error'] = rawurlencode( $e->getMessage() );
			}

			self::leave( add_query_arg( $redirect_args, admin_url( 'edit.php' ) ) );
		}
	}

	/**
	 * Safe exit or throw ExitSignal under tests.
	 *
	 * @param string $url Target redirect URL.
	 * @throws ExitSignal When running under tests with exit filter.
	 * @return void
	 */
	private static function leave( string $url ): void {
		if ( apply_filters( 'prc_exit_throws', false, $url ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- la URL viaja para que el test la lea; no se imprime.
			throw new ExitSignal( $url );
		}
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Render the diagnostics page.
	 *
	 * @return void
	 */
	public static function render(): void {
		if ( ! ProcedureAccess::is_manager() ) {
			wp_die( esc_html( 'No tiene permiso para ver los ajustes del aplicativo de procedimientos.' ), '', array( 'response' => 403 ) );
		}

		$post_types = array(
			ProcedurePostType::POST_TYPE   => 'Procedimientos',
			ApplicationPostType::POST_TYPE => 'Solicitudes',
		);
		$taxonomies = array(
			ProcedureTaxonomies::AREA   => 'Ámbito',
			ProcedureTaxonomies::COURSE => 'Curso escolar',
		);
		?>
		<div class="wrap">
			<h1>Ajustes y diagnóstico de procedimientos</h1>
			<?php
			// Fuera de «Ajustes» WordPress no pinta solo el «guardado» que deja
			// `options.php` al volver: se pide aquí.
			settings_errors();
			?>
			<p class="description" style="max-width:46rem">
				Esta pantalla cuenta lo que el aplicativo tiene montado en este sitio. Si algo
				sale «sin registrar», es que el snippet correspondiente no está activo. Lo único
				que guarda es la configuración del correo.
			</p>

			<h2>Correo</h2>
			<p style="max-width:46rem">
				Vacíos, los correos salen con el remitente de WordPress y sin copia. Cada
				procedimiento decide, en su pestaña «Avisos», cuáles de sus correos se envían.
			</p>
			<form method="post" action="options.php">
				<?php settings_fields( self::MAIL_GROUP ); ?>
				<table class="form-table" role="presentation" style="max-width:46rem">
					<tbody>
						<tr>
							<th scope="row"><label for="<?php echo esc_attr( Mailer::OPTION_FROM_NAME ); ?>">Nombre del remitente</label></th>
							<td><input class="regular-text" type="text" id="<?php echo esc_attr( Mailer::OPTION_FROM_NAME ); ?>" name="<?php echo esc_attr( Mailer::OPTION_FROM_NAME ); ?>" value="<?php echo esc_attr( (string) get_option( Mailer::OPTION_FROM_NAME, '' ) ); ?>" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="<?php echo esc_attr( Mailer::OPTION_FROM ); ?>">Dirección del remitente</label></th>
							<td>
								<input class="regular-text" type="email" id="<?php echo esc_attr( Mailer::OPTION_FROM ); ?>" name="<?php echo esc_attr( Mailer::OPTION_FROM ); ?>" value="<?php echo esc_attr( (string) get_option( Mailer::OPTION_FROM, '' ) ); ?>" />
								<p class="description">Una dirección que el servidor de correo de este sitio pueda usar como remitente.</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="<?php echo esc_attr( Mailer::OPTION_REPLY_TO ); ?>">Responder a</label></th>
							<td><input class="regular-text" type="email" id="<?php echo esc_attr( Mailer::OPTION_REPLY_TO ); ?>" name="<?php echo esc_attr( Mailer::OPTION_REPLY_TO ); ?>" value="<?php echo esc_attr( (string) get_option( Mailer::OPTION_REPLY_TO, '' ) ); ?>" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="<?php echo esc_attr( Mailer::OPTION_COPY ); ?>">Copia</label></th>
							<td>
								<input class="regular-text" type="email" id="<?php echo esc_attr( Mailer::OPTION_COPY ); ?>" name="<?php echo esc_attr( Mailer::OPTION_COPY ); ?>" value="<?php echo esc_attr( (string) get_option( Mailer::OPTION_COPY, '' ) ); ?>" />
								<p class="description">Recibe copia de cada procedimiento creado y de cada solicitud recibida.</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="<?php echo esc_attr( Mailer::OPTION_CREATED_EXTRA ); ?>">Texto del correo de alta</label></th>
							<td>
								<textarea class="large-text" rows="4" id="<?php echo esc_attr( Mailer::OPTION_CREATED_EXTRA ); ?>" name="<?php echo esc_attr( Mailer::OPTION_CREATED_EXTRA ); ?>"><?php echo esc_textarea( (string) get_option( Mailer::OPTION_CREATED_EXTRA, '' ) ); ?></textarea>
								<p class="description">Se añade al final del correo que avisa de un procedimiento recién creado: las instrucciones propias de esta instalación.</p>
							</td>
						</tr>
					</tbody>
				</table>
				<?php submit_button( 'Guardar la configuración del correo' ); ?>
			</form>

			<?php if ( isset( $_GET['updated'] ) && 'synced' === $_GET['updated'] && ! empty( $_GET['msg'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( sanitize_text_field( wp_unslash( (string) $_GET['msg'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?></p></div>
			<?php elseif ( isset( $_GET['error'] ) && ! empty( $_GET['error'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-error is-dismissible"><p><?php echo esc_html( sanitize_text_field( wp_unslash( (string) $_GET['error'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?></p></div>
			<?php endif; ?>

			<h2>Tipos de contenido</h2>
			<table class="widefat striped" style="max-width:46rem">
				<tbody>
				<?php foreach ( $post_types as $slug => $label ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $label ); ?></strong><br /><code><?php echo esc_html( $slug ); ?></code></td>
						<td><?php echo post_type_exists( $slug ) ? 'Registrado' : 'Sin registrar'; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<h2>Taxonomías</h2>
			<table class="widefat striped" style="max-width:46rem">
				<tbody>
				<?php foreach ( $taxonomies as $slug => $label ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $label ); ?></strong><br /><code><?php echo esc_html( $slug ); ?></code></td>
						<td>
							<?php
							if ( ! taxonomy_exists( $slug ) ) {
								echo 'Sin registrar';
							} else {
								$total = wp_count_terms(
									array(
										'taxonomy'   => $slug,
										'hide_empty' => false,
									)
								);
								echo esc_html( sprintf( 'Registrada, %d términos', is_wp_error( $total ) ? 0 : (int) $total ) );
							}
							?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<h2>Roles del aplicativo y Editor nativo</h2>
			<?php if ( ! function_exists( 'prc_roles_status' ) ) : ?>
				<p>El snippet <code>PRC — Roles y perfiles</code> no está activo, así que no hay roles que revisar.</p>
			<?php else : ?>
				<table class="widefat striped" style="max-width:46rem">
					<tbody>
					<?php foreach ( prc_roles_status() as $slug => $estado ) : ?>
						<tr>
							<td><strong><?php echo esc_html( (string) $estado['label'] ); ?></strong><br /><code><?php echo esc_html( $slug ); ?></code></td>
							<td>
								<?php
								if ( empty( $estado['exists'] ) ) {
									echo 'Falta el rol';
								} elseif ( ! empty( $estado['forbidden'] ) ) {
									echo esc_html( 'Capacidades indebidas: ' . implode( ', ', (array) $estado['forbidden'] ) );
								} elseif ( ! empty( $estado['missing'] ) ) {
									echo esc_html( 'Sin ' . implode( ', ', (array) $estado['missing'] ) );
								} else {
									echo 'Correcto';
								}
								?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<h2>Ámbitos de los perfiles editores</h2>
			<p>Diagnóstico de solo lectura. Los perfiles ambiguos o inválidos no reciben acceso hasta que administración elija un ámbito en su perfil.</p>
			<table class="widefat striped" style="max-width:46rem">
				<thead><tr><th>Usuario</th><th>Estado</th><th>IDs válidos</th><th>IDs inválidos</th><th>Formato anterior</th></tr></thead>
				<tbody>
				<?php foreach ( ProcedureAccess::scope_diagnostics() as $row ) : ?>
					<tr>
						<td><a href="<?php echo esc_url( get_edit_user_link( $row['user_id'] ) ); ?>"><?php echo esc_html( $row['login'] ); ?></a></td>
						<td><?php echo esc_html( $row['state'] ); ?></td>
						<td><?php echo esc_html( implode( ', ', $row['ids'] ) ); ?></td>
						<td><?php echo esc_html( implode( ', ', $row['invalid'] ) ); ?></td>
						<td><?php echo $row['legacy'] ? 'Sí' : 'No'; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<h2>Catálogo de centros educativos</h2>
			<?php
			$status       = CentreCatalogue::status();
			$total_count  = CentreCatalogue::count();
			$active_count = CentreCatalogue::active_count();
			$is_ready     = $total_count > 0;
			$has_source   = '' !== CentreCatalogueSync::manifest_url();
			?>
			<table class="widefat striped" style="max-width:46rem">
				<tbody>
					<tr>
						<th style="width:40%;">Estado</th>
						<td>
							<?php if ( $is_ready ) : ?>
								<span class="dashicons dashicons-yes-alt" style="color:#46b450;" aria-hidden="true"></span> Disponible
							<?php else : ?>
								<span class="dashicons dashicons-warning" style="color:#dc3232;" aria-hidden="true"></span> No disponible (catálogo vacío)
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th>Fuente configurada</th>
						<td><?php echo $has_source ? 'Sí' : 'No'; ?></td>
					</tr>
					<tr>
						<th>Registros totales</th>
						<td><strong><?php echo esc_html( (string) $total_count ); ?></strong></td>
					</tr>
					<tr>
						<th>Registros activos</th>
						<td><strong style="color:#46b450;"><?php echo esc_html( (string) $active_count ); ?></strong></td>
					</tr>
					<tr>
						<th>Registros inactivos</th>
						<td><?php echo esc_html( (string) ( $total_count - $active_count ) ); ?></td>
					</tr>
					<tr>
						<th>Fecha del catálogo</th>
						<td><?php echo esc_html( '' !== $status['catalogue_updated_at'] ? $status['catalogue_updated_at'] : '—' ); ?></td>
					</tr>
					<tr>
						<th>Última comprobación</th>
						<td><?php echo esc_html( '' !== $status['last_checked_at'] ? $status['last_checked_at'] : '—' ); ?></td>
					</tr>
					<tr>
						<th>Última actualización correcta</th>
						<td><?php echo esc_html( '' !== $status['last_success_at'] ? $status['last_success_at'] : '—' ); ?></td>
					</tr>
					<tr>
						<th>SHA-256 actual</th>
						<td>
							<?php if ( '' !== $status['sha256'] ) : ?>
								<code style="font-size:0.85em;"><?php echo esc_html( $status['sha256'] ); ?></code>
							<?php else : ?>
								<em>Ninguno</em>
							<?php endif; ?>
						</td>
					</tr>
					<?php if ( '' !== $status['last_error'] ) : ?>
						<tr>
							<th style="color:#dc3232;">Último error</th>
							<td style="color:#dc3232;"><code><?php echo esc_html( $status['last_error'] ); ?></code></td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>

			<div style="margin-top:1rem;">
				<form method="post" action="">
					<?php wp_nonce_field( self::NONCE_SYNC_CENTRES, '_prc_centres_nonce' ); ?>
					<input type="hidden" name="prc_action" value="sync_centres" />
					<button type="submit" class="button button-secondary">
						Actualizar catálogo ahora
					</button>
				</form>
			</div>
			<p style="margin-top:0.75rem;">
				La clave de usuario con el código de centro es <code><?php echo esc_html( CentreScope::meta_key() ); ?></code>.
			</p>

			<h2>Su acotado</h2>
			<?php
			$areas = ProcedureAccess::user_areas();
			$names = array();
			foreach ( $areas as $term_id ) {
				$term = get_term( $term_id, ProcedureTaxonomies::AREA );
				if ( $term instanceof \WP_Term ) {
					$names[] = $term->name;
				}
			}
			$code = CentreScope::code_for();
			?>
			<p>
				<?php if ( ProcedureAccess::can_edit_all_areas() ) : ?>
					Ve y edita los procedimientos de todos los ámbitos.
				<?php elseif ( array() === $names ) : ?>
					No tiene ningún ámbito asignado en su perfil, así que no ve ni edita ningún procedimiento.
				<?php else : ?>
					<?php echo esc_html( 'Acotado a: ' . implode( ', ', $names ) ); ?>
				<?php endif; ?>
				<?php echo esc_html( '' === $code ? 'Sin código de centro.' : 'Código de centro: ' . $code . '.' ); ?>
			</p>
		</div>
		<?php
	}
}
