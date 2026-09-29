<?php
/**
 * Idempotent role registration for local provision (wp eval-file).
 *
 * Relies on the roles snippet functions after Code Snippets has activated
 * them, or loads the versioned file directly if functions are missing.
 *
 * @package Prc
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$prc_snippet = dirname( __DIR__ ) . '/snippets/roles-and-profiles.php';
if ( ! function_exists( 'prc_register_roles' ) && is_readable( $prc_snippet ) ) {
	require_once $prc_snippet;
}

if ( ! function_exists( 'prc_register_roles' ) ) {
	throw new RuntimeException( 'No está disponible prc_register_roles().' );
}

prc_register_roles();

// Las capacidades de los dos tipos de contenido las reparte el aplicativo en
// `init` prioridad 11. En la primera provisión los roles todavía no existían
// cuando pasó ese `init`, así que se vuelve a llamar aquí: es idempotente y
// aditiva, y así los roles salen completos en la misma ejecución.
if ( class_exists( '\\Prc\\PostType\\ProcedurePostType' ) ) {
	\Prc\PostType\ProcedurePostType::grant_caps_to_roles();
} else {
	echo "AVISO: el bundle del aplicativo no está cargado; los roles quedan sin las capacidades de los tipos de contenido.\n";
}

$prc_slugs = function_exists( 'prc_role_slugs' ) ? prc_role_slugs() : array();
echo esc_html( 'Roles PRC asegurados: ' . implode( ', ', $prc_slugs ) ) . "\n";
foreach ( $prc_slugs as $prc_slug ) {
	$prc_role = get_role( $prc_slug );
	if ( ! $prc_role ) {
		throw new RuntimeException( esc_html( 'No se pudo crear el rol ' . $prc_slug . '.' ) );
	}
	// translators: 1: role slug, 2: capability count.
	echo esc_html( sprintf( '- %s: OK (%d caps)', $prc_slug, count( $prc_role->capabilities ) ) ) . "\n";
}
