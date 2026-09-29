<?php
/**
 * Run Code Snippets' own save-time validation over every PRC snippet.
 *
 * Saving an active snippet makes the plugin evaluate its code a second time
 * in the same request. A snippet without the double-eval guard either gets
 * disabled in silence («Cannot redeclare class») or kills the request. This
 * reproduces that check and fails loudly instead.
 *
 * Usage (Docker):
 *   npx wp-env run cli wp eval-file wp-content/prc-dev/scripts/snippet-check.php
 *
 * Runs under `wp eval-file`; never depends on WP_CLI. Idempotent: reads only.
 *
 * @package Prc
 */

if ( ! function_exists( 'Code_Snippets\get_snippets' ) || ! function_exists( 'Code_Snippets\test_snippet_code' ) ) {
	echo "AVISO: Code Snippets no está activo; no hay nada que comprobar.\n";
	return;
}

$prc_fallos = 0;

// First pass: the snippets that ran on this request must have actually loaded
// (a guard that trips too early leaves the classes declared but nothing booted).
// Lo que se comprueba es el efecto observable de cada snippet, no que exista
// una clase: el bundle registra el CPT en `init`, y el snippet de roles define
// sus funciones al cargarse.
$prc_cargado = array(
	'el aplicativo (CPT prc_procedure registrado)' => post_type_exists( 'prc_procedure' ),
	'las taxonomías (prc_area registrada)'         => taxonomy_exists( 'prc_area' ),
	'los roles (prc_register_roles() disponible)'  => function_exists( 'prc_register_roles' ),
);
foreach ( $prc_cargado as $prc_que => $prc_ok ) {
	if ( ! $prc_ok ) {
		++$prc_fallos;
	}
	echo esc_html( sprintf( '%s Primera pasada: %s', $prc_ok ? '✓' : '✗', $prc_que ) ) . "\n";
}

// Second pass: what the plugin does when an active snippet is saved.
foreach ( Code_Snippets\get_snippets() as $prc_snippet ) {
	if ( 0 !== strpos( (string) $prc_snippet->name, 'PRC' ) ) {
		continue;
	}
	// 3.10.x leaves the verdict in `code_error` (message, line); a fatal in the
	// second eval() would not even get here, which is a verdict too.
	$prc_snippet->code_error = null;
	Code_Snippets\test_snippet_code( $prc_snippet );
	if ( empty( $prc_snippet->code_error ) ) {
		echo esc_html( sprintf( '✓ %s', $prc_snippet->name ) ) . "\n";
		continue;
	}
	++$prc_fallos;
	echo esc_html( sprintf( '✗ %s: %s', $prc_snippet->name, wp_json_encode( $prc_snippet->code_error, JSON_UNESCAPED_UNICODE ) ) ) . "\n";
}

if ( $prc_fallos > 0 ) {
	echo esc_html( sprintf( '%d snippet(s) no sobreviven al guardado de Code Snippets.', $prc_fallos ) ) . "\n";
	exit( 1 );
}
