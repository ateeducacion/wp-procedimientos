<?php
/**
 * Sync the versioned snippets/*.php files into the Code Snippets plugin.
 *
 * Runs under `wp eval-file` (Docker) and under Playground `runPHP`/require —
 * it must never depend on WP_CLI. Idempotent: existing snippets (matched by
 * exact name) are updated, never duplicated.
 *
 * Usage:
 *   npx wp-env run cli wp eval-file wp-content/prc-dev/scripts/sync-snippets.php
 *
 * @package Prc
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/lib/snippet-sync.php';

if ( ! prc_code_snippets_is_active() ) {
	throw new RuntimeException( 'El plugin Code Snippets no está activo; no se pueden sincronizar los snippets.' );
}

$prc_snippets_dir = dirname( __DIR__ ) . '/snippets';

echo esc_html( sprintf( 'Sincronizando snippets desde %s ...', $prc_snippets_dir ) ) . "\n";

$prc_results = prc_sync_snippets_from_dir( $prc_snippets_dir );

if ( empty( $prc_results ) ) {
	throw new RuntimeException( 'No se ha encontrado ningún fichero de snippet que sincronizar.' );
}

$prc_created   = 0;
$prc_updated   = 0;
$prc_unchanged = 0;
$prc_errors    = 0;

foreach ( $prc_results as $prc_file => $prc_result ) {
	if ( 'error' === $prc_result['status'] ) {
		++$prc_errors;
		echo esc_html( sprintf( '- %s: ERROR — %s', $prc_file, $prc_result['error'] ) ) . "\n";
		continue;
	}

	switch ( $prc_result['status'] ) {
		case 'created':
			++$prc_created;
			$prc_action = 'creado';
			break;
		case 'updated':
			++$prc_updated;
			$prc_action = 'actualizado';
			break;
		case 'unchanged':
			++$prc_unchanged;
			$prc_action = $prc_result['reactivated'] ? 'sin cambios, reactivado' : 'sin cambios';
			break;
		default:
			++$prc_errors;
			echo esc_html( sprintf( '- %s: ERROR — estado de sincronización inesperado: %s', $prc_file, $prc_result['status'] ) ) . "\n";
			continue 2;
	}

	if ( $prc_result['active'] ) {
		$prc_state = '' === $prc_result['error']
			? 'activo'
			: sprintf( 'activo con AVISO — %s', $prc_result['error'] );
	} else {
		++$prc_errors;
		$prc_state = sprintf( 'ERROR al activar: %s', $prc_result['error'] );
	}

	echo esc_html(
		sprintf(
			'- %1$s → «%2$s» (ID %3$d): %4$s, %5$s',
			$prc_file,
			$prc_result['name'],
			$prc_result['id'],
			$prc_action,
			$prc_state
		)
	) . "\n";
}

echo esc_html(
	sprintf(
		'Resumen: %1$d creado(s), %2$d actualizado(s), %3$d sin cambios, %4$d error(es).',
		$prc_created,
		$prc_updated,
		$prc_unchanged,
		$prc_errors
	)
) . "\n";

if ( $prc_errors > 0 ) {
	throw new RuntimeException( 'La sincronización de snippets terminó con errores; revise el resumen anterior.' );
}
