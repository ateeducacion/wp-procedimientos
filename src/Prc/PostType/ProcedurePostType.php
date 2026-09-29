<?php
/**
 * Register the prc_procedure custom post type.
 *
 * @package Prc
 */

namespace Prc\PostType;

/**
 * CPT registration for procedures (ADR-0011).
 *
 * Un procedimiento es una convocatoria: tiene su ficha pública —el `single`
 * del tipo, pintado entero por el aplicativo (ADR-0022)—, y de él cuelgan
 * las solicitudes de los centros por `post_parent`.
 */
final class ProcedurePostType {

	public const POST_TYPE = 'prc_procedure';

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function register(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'               => 'Procedimientos',
					'singular_name'      => 'Procedimiento',
					'add_new'            => 'Añadir procedimiento',
					'add_new_item'       => 'Añadir procedimiento',
					'edit_item'          => 'Editar procedimiento',
					'new_item'           => 'Nuevo procedimiento',
					'view_item'          => 'Ver procedimiento',
					'search_items'       => 'Buscar procedimientos',
					'not_found'          => 'No se encontraron procedimientos',
					'not_found_in_trash' => 'No hay procedimientos en la papelera',
					'menu_name'          => 'Procedimientos',
				),
				'public'          => true,
				'hierarchical'    => false,
				'show_in_menu'    => true,
				'menu_position'   => 21,
				'menu_icon'       => 'dashicons-clipboard',
				'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
				'has_archive'     => false,
				'rewrite'         => array(
					/**
					 * Filter the public URL base of a procedure.
					 *
					 * Conservar las URL de hoy es una decisión pendiente ligada a
					 * la migración; mientras tanto, quien despliega elige la base.
					 *
					 * @param string $slug URL base. Default `procedimiento`.
					 */
					'slug'       => (string) apply_filters( 'prc_procedure_rewrite_slug', 'procedimiento' ),
					'with_front' => false,
				),
				'capability_type' => array( 'prc_procedure', 'prc_procedures' ),
				'map_meta_cap'    => true,
				'capabilities'    => self::capabilities(),
				'show_in_rest'    => true,
			)
		);
	}

	/**
	 * Custom capabilities for the CPT.
	 *
	 * No se declara `create_posts`: sin declararla, WordPress usa
	 * `edit_prc_procedures` para «Añadir nuevo», que es justo lo que tiene el
	 * rol de gestión.
	 *
	 * @return array<string, string>
	 */
	public static function capabilities(): array {
		return self::cap_map( 'prc_procedure', 'prc_procedures' );
	}

	/**
	 * Build the WordPress cap map for a singular/plural pair.
	 *
	 * @param string $one  Singular cap base.
	 * @param string $many Plural cap base.
	 * @return array<string, string>
	 */
	public static function cap_map( string $one, string $many ): array {
		return array(
			'edit_post'              => 'edit_' . $one,
			'read_post'              => 'read_' . $one,
			'delete_post'            => 'delete_' . $one,
			'edit_posts'             => 'edit_' . $many,
			'edit_others_posts'      => 'edit_others_' . $many,
			'publish_posts'          => 'publish_' . $many,
			'read_private_posts'     => 'read_private_' . $many,
			'delete_posts'           => 'delete_' . $many,
			'delete_private_posts'   => 'delete_private_' . $many,
			'delete_published_posts' => 'delete_published_' . $many,
			'delete_others_posts'    => 'delete_others_' . $many,
			'edit_private_posts'     => 'edit_private_' . $many,
			'edit_published_posts'   => 'edit_published_' . $many,
		);
	}

	/**
	 * Grant the CPT caps of the two content types to the two roles.
	 *
	 * Idempotente y aditiva: crea lo que falta y nunca quita nada. Las
	 * capacidades propias del aplicativo (`prc_*`) las reparte el snippet de
	 * roles (ADR-0015); aquí solo van las que WordPress deriva del tipo, que
	 * son las que abren el escritorio y las que `map_meta_cap` exige antes de
	 * que el guardián diga nada.
	 *
	 * @return void
	 */
	public static function grant_caps_to_roles(): void {
		$maps = array(
			self::POST_TYPE                => self::capabilities(),
			ApplicationPostType::POST_TYPE => ApplicationPostType::capabilities(),
		);

		$every = array_keys( self::capabilities() );

		// Del procedimiento, la gestión de un ámbito publica y retira lo suyo
		// y lo de sus compañeras, pero no lo privado ni lo de otra persona.
		// Lo que la acota al ámbito es ProcedureAccess, no la capacidad.
		$manager_procedures = array(
			'edit_post',
			'read_post',
			'delete_post',
			'edit_posts',
			'edit_others_posts',
			'publish_posts',
			'read_private_posts',
			'delete_posts',
			'delete_published_posts',
			'edit_published_posts',
		);

		// De las solicitudes, la gestión lee y borra las de sus procedimientos
		// pero no las publica: no las escribe una persona, las escribe el
		// aplicativo cuando un centro solicita (ADR-0018).
		$manager_applications = array(
			'edit_post',
			'read_post',
			'delete_post',
			'edit_posts',
			'edit_others_posts',
			'read_private_posts',
			'delete_posts',
			'delete_others_posts',
			'delete_private_posts',
			'delete_published_posts',
			'edit_private_posts',
			'edit_published_posts',
		);

		$by_role = array(
			'editor'        => array(
				self::POST_TYPE                => $manager_procedures,
				ApplicationPostType::POST_TYPE => $manager_applications,
			),
			'prc_manager'   => array(
				self::POST_TYPE                => $manager_procedures,
				ApplicationPostType::POST_TYPE => $manager_applications,
			),
			'administrator' => array_fill_keys( array_keys( $maps ), $every ),
		);

		foreach ( $by_role as $slug => $por_tipo ) {
			$role = get_role( $slug );
			if ( ! $role ) {
				continue;
			}
			foreach ( $maps as $tipo => $map ) {
				foreach ( $por_tipo[ $tipo ] as $key ) {
					if ( isset( $map[ $key ] ) && ! $role->has_cap( $map[ $key ] ) ) {
						$role->add_cap( $map[ $key ] );
					}
				}
			}
		}
	}
}
