<?php
/**
 * Snippet Name: PRC — Vocabulario base del aplicativo (una sola vez)
 * Description: Deja creados los términos de las dos taxonomías del aplicativo: el árbol de ámbitos convocantes (servicio → área) y los cursos escolares. Idempotente: crea el término que falta por su slug y no toca el que ya está. En producción se pega en Code Snippets como snippet de ejecución única; en local lo llama `make provision`.
 *
 * Lo que tiene que existir antes de que nadie cree el primer procedimiento es
 * el vocabulario que lo clasifica, y sobre todo `prc_area`: es el eje de
 * permisos (ADR-0014), y sin términos nadie puede tener ámbito en su perfil,
 * así que `ProcedureAccess` cierra la puerta a todo el mundo (fail-closed).
 *
 * Los términos de aquí son **de demostración**: nombres inventados para que
 * el entorno local tenga con qué trabajar. Cada instalación crea los suyos
 * (ADR-0009: el organigrama de una organización no se versiona).
 *
 * @package Prc
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'prc_base_vocabulary' ) ) {
	/**
	 * Base vocabulary of the two taxonomies: taxonomy => (slug => name | {name, parent}).
	 *
	 * El ámbito es jerárquico: un servicio y, colgando de él, sus áreas. Quien
	 * tiene el servicio en su ficha llega a todas sus áreas.
	 *
	 * @return array<string, array<string, string|array{name:string, parent:string}>>
	 */
	function prc_base_vocabulary(): array {
		return array(
			'prc_area'   => array(
				'ambito-1'     => 'Ámbito 1',
				'subambito-1a' => array(
					'name'   => 'Subámbito 1A',
					'parent' => 'ambito-1',
				),
				'subambito-1b' => array(
					'name'   => 'Subámbito 1B',
					'parent' => 'ambito-1',
				),
				'subambito-1c' => array(
					'name'   => 'Subámbito 1C',
					'parent' => 'ambito-1',
				),
				'ambito-2'     => 'Ámbito 2',
				'subambito-2a' => array(
					'name'   => 'Subámbito 2A',
					'parent' => 'ambito-2',
				),
				'subambito-2b' => array(
					'name'   => 'Subámbito 2B',
					'parent' => 'ambito-2',
				),
			),
			// Curso escolar. Solo los vivos: los históricos llegarán con la
			// migración, que es la que sabe cuáles hacen falta de verdad.
			'prc_course' => array(
				'2024-2025' => 'Curso 2024-2025',
				'2025-2026' => 'Curso 2025-2026',
				'2026-2027' => 'Curso 2026-2027',
			),
		);
	}
}

if ( ! function_exists( 'prc_setup_vocabulary' ) ) {
	/**
	 * Create the missing terms of the two taxonomies.
	 *
	 * @return void
	 * @throws RuntimeException If a taxonomy is not registered or a term cannot be created.
	 */
	function prc_setup_vocabulary(): void {
		$prc_creados = 0;
		$prc_habia   = 0;

		foreach ( prc_base_vocabulary() as $prc_taxonomy => $prc_terms ) {
			if ( ! taxonomy_exists( $prc_taxonomy ) ) {
				throw new RuntimeException( esc_html( "La taxonomía «{$prc_taxonomy}» no está registrada: el aplicativo no está cargado. Ejecute antes `make bundle && make sync-snippets`." ) );
			}

			// Los padres van antes que las hijas en la lista, así que el padre
			// ya existe cuando toca colgar de él.
			foreach ( $prc_terms as $prc_slug => $prc_def ) {
				$prc_name   = is_array( $prc_def ) ? $prc_def['name'] : $prc_def;
				$prc_parent = is_array( $prc_def ) ? $prc_def['parent'] : '';

				$prc_term = get_term_by( 'slug', $prc_slug, $prc_taxonomy );
				if ( $prc_term instanceof WP_Term ) {
					++$prc_habia;
					continue;
				}

				$prc_args = array( 'slug' => $prc_slug );
				if ( '' !== $prc_parent ) {
					$prc_padre = get_term_by( 'slug', $prc_parent, $prc_taxonomy );
					if ( ! $prc_padre instanceof WP_Term ) {
						throw new RuntimeException( esc_html( "No existe el padre «{$prc_parent}» de «{$prc_slug}» en {$prc_taxonomy}." ) );
					}
					$prc_args['parent'] = (int) $prc_padre->term_id;
				}

				$prc_created = wp_insert_term( $prc_name, $prc_taxonomy, $prc_args );
				if ( is_wp_error( $prc_created ) ) {
					throw new RuntimeException( esc_html( "No se pudo crear «{$prc_name}» en {$prc_taxonomy}: " . $prc_created->get_error_message() ) );
				}

				echo esc_html( "Creado «{$prc_name}» ({$prc_taxonomy}/{$prc_slug})." ) . "\n";
				++$prc_creados;
			}
		}

		echo esc_html( sprintf( 'Vocabulario del aplicativo: %d término(s) creado(s), %d ya estaban.', $prc_creados, $prc_habia ) ) . "\n";
	}
}

// En Code Snippets esto corre antes de `init`, y las taxonomías se registran
// en `init` prioridad 9: hay que esperar. Con `wp eval-file` init ya pasó, así
// que se ejecuta al momento.
if ( did_action( 'init' ) ) {
	prc_setup_vocabulary();
} else {
	add_action( 'init', 'prc_setup_vocabulary', 20 );
}
