<?php
/**
 * Register the prc_application custom post type.
 *
 * @package Prc
 */

namespace Prc\PostType;

/**
 * CPT registration for the applications of schools.
 *
 * Una solicitud cuelga de su procedimiento por `post_parent` (ADR-0018): de
 * ahí salen solos el ámbito que la acota y el cierre por histórico. Hoy la
 * relación es una clave compuesta «id de convocatoria · código de centro»
 * escrita en un campo de texto del gestor de formularios.
 *
 * Se registra sin URL, sin REST y sin búsqueda: nada de esto se pinta fuera
 * del aplicativo. El escritorio sí se abre (`show_ui`), solo para mirar
 * cuando algo no cuadra; lo que protege lo que hay dentro es `map_meta_cap`.
 */
final class ApplicationPostType {

	public const POST_TYPE = 'prc_application';

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function register(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'          => 'Solicitudes',
					'singular_name' => 'Solicitud',
					'edit_item'     => 'Solicitud',
					'search_items'  => 'Buscar solicitudes',
					'not_found'     => 'No se encontraron solicitudes',
					'menu_name'     => 'Solicitudes',
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'show_ui'             => true,
				'show_in_menu'        => 'edit.php?post_type=' . ProcedurePostType::POST_TYPE,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => false,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => array( 'title' ),
				'capability_type'     => array( 'prc_application', 'prc_applications' ),
				'map_meta_cap'        => true,
				'capabilities'        => self::capabilities(),
				'delete_with_user'    => false,
			)
		);
	}

	/**
	 * Custom capabilities for the CPT.
	 *
	 * @return array<string, string>
	 */
	public static function capabilities(): array {
		return ProcedurePostType::cap_map( 'prc_application', 'prc_applications' );
	}
}
