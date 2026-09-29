<?php
/**
 * Load modular PRC procedures application classes (development includes).
 *
 * The load order lives in load-order.php, shared with build/pack-snippet.php.
 * The Code Snippets bundle inlines the same files in that same order.
 *
 * @package Prc
 */

if ( ! defined( 'PRC_SRC_DIR' ) ) {
	define( 'PRC_SRC_DIR', __DIR__ );
}

// When the Code Snippets bundle already defined the classes, skip file loads
// (require_once is path-based; the bundle is a different file and would redeclare).
if ( ! class_exists( \Prc\App::class, false ) ) {
	$prc_app_files = require __DIR__ . '/load-order.php';

	foreach ( $prc_app_files as $prc_app_file ) {
		$prc_app_path = PRC_SRC_DIR . '/' . $prc_app_file;
		if ( is_readable( $prc_app_path ) ) {
			require_once $prc_app_path;
		}
	}
}

if ( class_exists( \Prc\App::class ) ) {
	\Prc\App::boot();
}
