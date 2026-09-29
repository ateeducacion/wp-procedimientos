<?php
/**
 * Snippet Name: PRC — Roles y perfiles
 * Description: Registra los dos roles del aplicativo de procedimientos (prc_manager, prc_school_head), sus capacidades propias y los campos de perfil «Ámbitos» y «Código de centro» que acotan lo que cada persona ve, edita o solicita. La administración es el rol nativo de WordPress y solo recibe capacidades. Las capacidades de los tipos de contenido las reparte el aplicativo, no este snippet. Los roles se revisan en WPFront User Role Editor.
 * Scope: global
 * Priority: 5
 *
 * @package Prc
 */

// Code Snippets evalúa esto, no lo incluye como fichero, así que aquí no hay
// «acceso directo» que valga. La guarda va igual porque no cuesta nada y porque
// el día que este código acabe en un fichero servido —una copia, un envoltorio,
// una carpeta de plugins— la diferencia entre volcar el código y no volcarlo es
// esta línea.
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'prc_role_slugs' ) ) {
	/**
	 * Return the product role slugs.
	 *
	 * @return string[]
	 */
	function prc_role_slugs(): array {
		return array( 'prc_manager', 'prc_school_head' );
	}
}

if ( ! function_exists( 'prc_role_definitions' ) ) {
	/**
	 * Role labels and the capabilities this snippet owns.
	 *
	 * Aquí solo van las capacidades propias del aplicativo (ADR-0015). Las de
	 * los tipos de contenido (`edit_prc_procedures`, `publish_prc_procedures`,
	 * …) las reparte `Prc\PostType\ProcedurePostType::grant_caps_to_roles()`
	 * en `init` prioridad 11, para no tener el mismo mapa escrito en dos
	 * sitios que se separan.
	 *
	 * @return array<string, array{label:string, caps:string[]}>
	 */
	function prc_role_definitions(): array {
		return array(
			'prc_manager'     => array(
				'label' => 'Gestión de procedimientos',
				'caps'  => array(
					'read',
					'prc_manage_procedures',
					'prc_review_applications',
				),
			),
			'prc_school_head' => array(
				'label' => 'Dirección de centro',
				'caps'  => array(
					'read',
					'prc_apply',
				),
			),
		);
	}
}

if ( ! function_exists( 'prc_forbidden_role_caps' ) ) {
	/**
	 * Capabilities no product role may ever hold.
	 *
	 * Es la raya del aplicativo: salirse del ámbito (`prc_manage_all_areas`)
	 * y administrar el aplicativo (`prc_manage_app`: ajustes, términos de las
	 * taxonomías, los campos de la ficha de usuario y desarchivar) son de la
	 * administración y de nadie más. Quien gestiona usa los ámbitos que hay;
	 * crearlos es administrar el aplicativo, no convocar un procedimiento.
	 *
	 * Están escritas aquí y no solo omitidas de `prc_role_definitions()`: lo
	 * que no se nombra no se comprueba, y esta lista es la que mira
	 * `prc_roles_status()` para avisar si alguien las concede a mano en WPFront.
	 * Este snippet nunca quita capacidades —es aditivo a propósito—, así que
	 * avisa y quien administra decide.
	 *
	 * @return string[]
	 */
	function prc_forbidden_role_caps(): array {
		return array( 'prc_manage_all_areas', 'prc_manage_app', 'unfiltered_html' );
	}
}

if ( ! function_exists( 'prc_audited_role_definitions' ) ) {
	/** Product-owned roles plus the native editor used by the product. */
	function prc_audited_role_definitions(): array {
		return array_merge(
			prc_role_definitions(),
			array(
				'editor' => array(
					'label' => 'Editor de procedimientos',
					'caps'  => array( 'prc_manage_procedures', 'prc_review_applications', 'edit_prc_procedures', 'publish_prc_procedures' ),
				),
			)
		);
	}
}

if ( ! function_exists( 'prc_register_roles' ) ) {
	/**
	 * Idempotently register product roles and grant caps to administrators.
	 *
	 * Aditiva: crea el rol si falta y añade la capacidad si falta, nunca quita.
	 * Así lo que se conceda a mano en WPFront sigue ahí en la siguiente carga,
	 * y quitar una capacidad se hace en el código, que es donde se ve.
	 *
	 * @return void
	 */
	function prc_register_roles(): void {
		foreach ( prc_role_definitions() as $slug => $def ) {
			$role = get_role( $slug );
			if ( ! $role ) {
				add_role( $slug, $def['label'], array() );
				$role = get_role( $slug );
			}
			if ( ! $role ) {
				continue;
			}
			foreach ( $def['caps'] as $cap ) {
				if ( ! $role->has_cap( $cap ) ) {
					$role->add_cap( $cap );
				}
			}
		}
		$editor = get_role( 'editor' );
		if ( $editor ) {
			foreach ( array( 'prc_manage_procedures', 'prc_review_applications' ) as $cap ) {
				if ( ! $editor->has_cap( $cap ) ) {
					$editor->add_cap( $cap );
				}
			}
		}

		// La administración gestiona y revisa en todos los ámbitos y administra
		// el aplicativo. No solicita: solicitar es cosa de un centro, y la
		// administración no tiene centro.
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( array( 'prc_manage_procedures', 'prc_review_applications', 'prc_manage_all_areas', 'prc_manage_app' ) as $cap ) {
				if ( ! $admin->has_cap( $cap ) ) {
					$admin->add_cap( $cap );
				}
			}
		}
	}
}

if ( ! function_exists( 'prc_role_exists' ) ) {
	/**
	 * Whether a role exists, asking WPFront User Role Editor when it is there.
	 *
	 * Los roles se administran en WPFront: preguntarle a él es preguntar a la
	 * misma lista que ve quien los mantiene. Sin el plugin, el registro de
	 * WordPress dice lo mismo.
	 *
	 * @param string $slug Role slug.
	 * @return bool
	 */
	function prc_role_exists( string $slug ): bool {
		$helper = '\\WPFront\\URE\\WPFront_User_Role_Editor_Roles_Helper';
		if ( class_exists( $helper ) && method_exists( $helper, 'is_role' ) ) {
			return (bool) call_user_func( array( $helper, 'is_role' ), $slug );
		}
		return null !== get_role( $slug );
	}
}

if ( ! function_exists( 'prc_roles_status' ) ) {
	/**
	 * What is missing for each product role to work, and what it should not have.
	 *
	 * `missing` es lo que le falta; `forbidden`, lo que le sobra y es peligroso
	 * ({@see prc_forbidden_role_caps()}). Las dos listas vacías es lo correcto.
	 *
	 * @return array<string, array{label:string, exists:bool, missing:string[], forbidden:string[]}>
	 */
	function prc_roles_status(): array {
		$out = array();
		foreach ( prc_audited_role_definitions() as $slug => $def ) {
			$exists  = prc_role_exists( $slug );
			$role    = $exists ? get_role( $slug ) : null;
			$missing = array();
			foreach ( $def['caps'] as $cap ) {
				if ( ! $role || ! $role->has_cap( $cap ) ) {
					$missing[] = $cap;
				}
			}
			$sobran    = array();
			$forbidden = 'editor' === $slug ? array_diff( prc_forbidden_role_caps(), array( 'unfiltered_html' ) ) : prc_forbidden_role_caps();
			foreach ( $forbidden as $cap ) {
				if ( $role && $role->has_cap( $cap ) ) {
					$sobran[] = $cap;
				}
			}
			$out[ $slug ] = array(
				'label'     => $def['label'],
				'exists'    => $exists,
				'missing'   => $missing,
				'forbidden' => $sobran,
			);
		}
		return $out;
	}
}

if ( ! function_exists( 'prc_centre_code_meta_key' ) ) {
	/**
	 * Which user meta holds the school code on this site (ADR-0016).
	 *
	 * La misma pregunta que hace `Prc\Access\CentreScope::meta_key()`, hecha
	 * aquí sin depender de que el bundle esté cargado: este snippet va suelto.
	 *
	 * @return string
	 */
	function prc_centre_code_meta_key(): string {
		/** This filter is documented in src/Prc/Access/CentreScope.php */
		$key = apply_filters( 'prc_centre_code_meta_key', 'prc_centre_code' );
		return is_string( $key ) ? trim( $key ) : '';
	}
}

if ( ! function_exists( 'prc_can_edit_admin_only_fields' ) ) {
	/**
	 * Whether the current user may set somebody's ámbitos or school code.
	 *
	 * @return bool
	 */
	function prc_can_edit_admin_only_fields(): bool {
		return current_user_can( 'manage_options' );
	}
}

if ( ! function_exists( 'prc_render_profile_fields' ) ) {
	/**
	 * Render the ámbitos and school code fields on the user profile screens.
	 *
	 * @param WP_User $user User being edited.
	 * @return void
	 */
	function prc_render_profile_fields( $user ): void {
		if ( ! ( $user instanceof WP_User ) || ! taxonomy_exists( 'prc_area' ) ) {
			return;
		}

		if ( ! class_exists( '\Prc\Taxonomy\ProcedureTaxonomies' ) ) {
			return;
		}
		$terms = \Prc\Taxonomy\ProcedureTaxonomies::area_options();

		$assignment = \Prc\Access\ProcedureAccess::scope_assignment_state( $user->ID );
		$mine       = 'resolved' === $assignment['state'] ? $assignment['ids'][0] : 0;
		$unresolved = in_array( $assignment['state'], array( 'ambiguous', 'invalid' ), true );

		$key  = prc_centre_code_meta_key();
		$code = '' !== $key ? (string) get_user_meta( $user->ID, $key, true ) : '';

		$puede  = prc_can_edit_admin_only_fields();
		$editor = array() !== array_intersect( array( 'editor', 'prc_manager' ), $user->roles );

		echo '<h2>Procedimientos</h2>';
		if ( $puede ) {
			echo '<input type="hidden" name="prc_profile_present" value="1" />';
			wp_nonce_field( 'prc_profile_scope_' . $user->ID, 'prc_profile_scope_nonce' );
		}
		echo '<table class="form-table" role="presentation">';

		// Un ámbito incluye a sus hijas.
		if ( $puede && $editor ) {
			echo '<tr><th><label for="prc_area">Ámbito</label></th><td>';
			echo '<select name="prc_area" id="prc_area" class="regular-text">';
			if ( $unresolved ) {
				echo '<option value="__keep_unresolved__" selected="selected">Pendiente de resolver (conservar datos)</option>';
			}
			printf( '<option value=""%s>Sin ámbito</option>', 'empty' === $assignment['state'] ? ' selected="selected"' : '' );
			foreach ( $terms as $term_id => $label ) {
				printf(
					'<option value="%1$d"%2$s>%3$s</option>',
					(int) $term_id,
					(int) $term_id === $mine ? ' selected="selected"' : '',
					esc_html( $label )
				);
			}
			echo '</select>';
			if ( $unresolved ) {
				$labels = \Prc\Taxonomy\ProcedureTaxonomies::area_options( 0, true );
				$names  = array_map(
					static function ( $id ) use ( $labels ) {
						return $labels[ $id ] ?? (string) $id;
					},
					$assignment['ids']
				);
				if ( $assignment['invalid'] ) {
					$names[] = 'IDs inválidos: ' . implode( ', ', $assignment['invalid'] );
				}
				printf( '<p class="notice notice-warning">Este perfil conserva ámbitos históricos: %s. Debe elegir uno para recuperar el acceso; mientras tanto, el usuario no accede a contenidos acotados. Si guarda sin elegir, se conservarán los datos.</p>', esc_html( implode( ', ', $names ) ) );
			}
			echo '<p class="description">Un ámbito incluye sus descendientes. Sin ámbito, esta persona no ve ni edita ningún procedimiento.</p>';
			echo '</td></tr>';
		}

		// Código de centro: la meta cuya clave da el filtro (ADR-0016).
		echo '<tr><th><label for="prc_centre_code">Código de centro</label></th><td>';
		if ( ! $puede ) {
			echo esc_html( '' === $code ? 'Sin centro asignado.' : $code );
			echo '<p class="description">El centro lo asigna quien administra el aplicativo: es el que decide qué solicitudes presenta y ve.</p>';
		} else {
			printf( '<input type="text" name="prc_centre_code" id="prc_centre_code" class="regular-text" value="%s" />', esc_attr( $code ) );
			echo '<p class="description">Código del centro por el que solicita. Sin él, esta persona no puede presentar ninguna solicitud.</p>';
		}
		echo '</td></tr></table>';
	}
}

if ( ! function_exists( 'prc_save_profile_fields' ) ) {
	/**
	 * Persist the ámbitos and school code fields.
	 *
	 * Los dos campos deciden qué procedimientos toca y por qué centro solicita
	 * cada persona, así que quien está acotado por ellos no puede escribirlos:
	 * WordPress deja a cualquiera editar su propio perfil, y eso convertiría
	 * el campo en la puerta de al lado.
	 *
	 * @param int $user_id User ID being saved.
	 * @return void
	 */
	function prc_save_profile_fields( int $user_id ): void {
		$user = get_user_by( 'id', $user_id );
		if ( ! ( $user instanceof WP_User ) || ! current_user_can( 'edit_user', $user_id ) || ! prc_can_edit_admin_only_fields() ) {
			return;
		}
		if ( ! isset( $_POST['prc_profile_scope_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['prc_profile_scope_nonce'] ) ), 'prc_profile_scope_' . $user_id ) ) {
			return;
		}
		$post = wp_unslash( $_POST );
		if ( empty( $post['prc_profile_present'] ) ) {
			return;
		}

		if ( array() !== array_intersect( array( 'editor', 'prc_manager' ), $user->roles ) ) {
			$raw = $post['prc_area'] ?? null;
			if ( is_scalar( $raw ) ) {
				$raw = sanitize_text_field( (string) $raw );
				if ( '__keep_unresolved__' !== $raw && ( '' === $raw || ctype_digit( $raw ) ) ) {
					$term_id = absint( $raw );
					$term    = $term_id > 0 ? get_term( $term_id, 'prc_area' ) : null;
					if ( 0 === $term_id || $term instanceof WP_Term ) {
						update_user_meta( $user_id, 'prc_area', $term_id > 0 ? array( $term_id ) : array() );
					}
				}
			}
		}

		$key = prc_centre_code_meta_key();
		if ( '' === $key ) {
			return;
		}
		$code = isset( $post['prc_centre_code'] ) ? sanitize_text_field( (string) $post['prc_centre_code'] ) : '';
		if ( '' === $code ) {
			delete_user_meta( $user_id, $key );
		} else {
			update_user_meta( $user_id, $key, $code );
		}
	}
}

add_action( 'init', 'prc_register_roles', 5 );
add_action( 'show_user_profile', 'prc_render_profile_fields' );
add_action( 'edit_user_profile', 'prc_render_profile_fields' );
add_action( 'personal_options_update', 'prc_save_profile_fields' );
add_action( 'edit_user_profile_update', 'prc_save_profile_fields' );
