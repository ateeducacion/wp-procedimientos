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
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'menu' ) );
		add_action( 'admin_init', array( self::class, 'handle_actions' ) );
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
			<p class="description" style="max-width:46rem">
				Esta pantalla no guarda nada todavía: cuenta lo que el aplicativo tiene montado
				en este sitio. Si algo sale «sin registrar», es que el snippet correspondiente
				no está activo.
			</p>

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
