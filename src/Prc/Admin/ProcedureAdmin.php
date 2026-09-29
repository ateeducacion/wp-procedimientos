<?php
/**
 * Admin list tables for procedures and applications: ámbito scoping and columns.
 *
 * @package Prc
 */

namespace Prc\Admin;

use Prc\Access\ProcedureAccess;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\PostType\ApplicationPostType;
use Prc\PostType\ProcedurePostType;
use Prc\Taxonomy\ProcedureTaxonomies;

/**
 * Lo que ve cada ámbito en el escritorio.
 *
 * El acotado de aquí solo esconde filas; quien de verdad cierra la puerta es
 * `ProcedureAccess::map_meta_cap()`. Las dos capas hacen falta: sin esta, el
 * listado enseña los procedimientos de todos los ámbitos aunque no se puedan
 * abrir.
 */
final class ProcedureAdmin {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'pre_get_posts', array( self::class, 'scope_admin_query' ) );
		add_filter( 'manage_' . ProcedurePostType::POST_TYPE . '_posts_columns', array( self::class, 'columns' ) );
		add_action( 'manage_' . ProcedurePostType::POST_TYPE . '_posts_custom_column', array( self::class, 'column_content' ), 10, 2 );
		add_filter( 'manage_' . ApplicationPostType::POST_TYPE . '_posts_columns', array( self::class, 'application_columns' ) );
		add_action( 'manage_' . ApplicationPostType::POST_TYPE . '_posts_custom_column', array( self::class, 'application_column_content' ), 10, 2 );
	}

	/**
	 * Limit both lists to the ámbitos of whoever is looking.
	 *
	 * @param \WP_Query $query Query.
	 * @return void
	 */
	public static function scope_admin_query( $query ): void {
		if ( ! is_admin() || ! ( $query instanceof \WP_Query ) || ! $query->is_main_query() ) {
			return;
		}
		$tipo = (string) $query->get( 'post_type' );
		if ( ! in_array( $tipo, ProcedureAccess::scoped_types(), true ) ) {
			return;
		}
		if ( ProcedureAccess::can_edit_all_areas() ) {
			return;
		}

		$areas = ProcedureAccess::scope_areas();
		if ( array() === $areas ) {
			// Falla en cerrado: sin ámbito en el perfil, ni una fila.
			$query->set( 'post__in', array( 0 ) );
			return;
		}

		if ( ProcedurePostType::POST_TYPE === $tipo ) {
			$tax_query   = (array) $query->get( 'tax_query' );
			$tax_query[] = array(
				'taxonomy'         => ProcedureTaxonomies::AREA,
				'field'            => 'term_id',
				'terms'            => $areas,
				'include_children' => false,
			);
			$query->set( 'tax_query', $tax_query );
			return;
		}

		// Las solicitudes no llevan ámbito: se acotan por el procedimiento del que cuelgan.
		$procedures = get_posts(
			array(
				'post_type'      => ProcedurePostType::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- acotar por ámbito es el requisito.
					array(
						'taxonomy'         => ProcedureTaxonomies::AREA,
						'field'            => 'term_id',
						'terms'            => $areas,
						'include_children' => false,
					),
				),
			)
		);
		$query->set( 'post_parent__in', array() === $procedures ? array( 0 ) : array_map( 'intval', $procedures ) );
	}

	/**
	 * Add the ámbito and state columns after the title.
	 *
	 * @param array<string, string> $columns Columns.
	 * @return array<string, string>
	 */
	public static function columns( array $columns ): array {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['prc_area']  = 'Ámbito';
				$new['prc_state'] = 'Estado';
			}
		}
		return $new;
	}

	/**
	 * Render the custom column cells of a procedure.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public static function column_content( string $column, int $post_id ): void {
		if ( 'prc_area' === $column ) {
			$terms = get_the_terms( $post_id, ProcedureTaxonomies::AREA );
			echo is_array( $terms ) && array() !== $terms
				? esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) )
				: '—';
			return;
		}
		if ( 'prc_state' === $column ) {
			echo esc_html( ProcedureState::label( ProcedureState::of_post( $post_id ) ) );
		}
	}

	/**
	 * Add the procedure, school and review columns after the title.
	 *
	 * @param array<string, string> $columns Columns.
	 * @return array<string, string>
	 */
	public static function application_columns( array $columns ): array {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['prc_procedure'] = 'Procedimiento';
				$new['prc_centre']    = 'Centro';
				$new['prc_review']    = 'Revisión';
			}
		}
		return $new;
	}

	/**
	 * Render the custom column cells of an application.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public static function application_column_content( string $column, int $post_id ): void {
		if ( 'prc_procedure' === $column ) {
			$parent = (int) get_post_field( 'post_parent', $post_id );
			echo $parent > 0 ? esc_html( (string) get_the_title( $parent ) ) : '—';
			return;
		}
		if ( 'prc_centre' === $column ) {
			$code = (string) get_post_meta( $post_id, ApplicationMetaKeys::CENTRE_CODE, true );
			$name = (string) get_post_meta( $post_id, ApplicationMetaKeys::CENTRE_NAME, true );
			echo esc_html( trim( $name . ' (' . $code . ')' ) );
			return;
		}
		if ( 'prc_review' === $column ) {
			$state = (string) get_post_meta( $post_id, ApplicationMetaKeys::REVIEW_STATE, true );
			echo esc_html( (string) ( ApplicationMetaKeys::review_states()[ $state ] ?? '—' ) );
		}
	}
}
