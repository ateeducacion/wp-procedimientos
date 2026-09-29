<?php
/**
 * Write the throwaway plugin wrapper that lets Plugin Check read our code.
 *
 * Este repositorio **no es un plugin** y no va a serlo (ADR-0001): el artefacto
 * de producción es un snippet que se pega en Code Snippets. Pero las
 * comprobaciones de Plugin Check —escapado tardío, saneado, consultas directas,
 * i18n, encolados— son sobre el código, y el código es el mismo.
 *
 * Así que para pasarlas se monta un envoltorio con una cabecera de plugin de
 * mentira, se revisa y se borra. Lo escribe este guion y no el Makefile porque
 * una cabecera de plugin dentro de una receta de make es una ristra de
 * `printf` que nadie va a querer leer ni tocar.
 *
 * **Lo que genera es desechable**: `make check-plugin` lo crea y lo borra, y
 * `.gitignore` ignora su carpeta. Si alguna vez aparece versionado, algo ha
 * ido mal.
 *
 * Uso: php build/plugin-check-wrapper.php <ruta/del/fichero.php>
 *
 * @package Prc
 */

$prc_destino = $argv[1] ?? '';

if ( '' === $prc_destino ) {
	fwrite( STDERR, "Uso: php build/plugin-check-wrapper.php <ruta/del/fichero.php>\n" );
	exit( 1 );
}

$prc_directorio = dirname( $prc_destino );

if ( ! is_dir( $prc_directorio ) ) {
	fwrite( STDERR, "No existe el directorio {$prc_directorio}\n" );
	exit( 1 );
}

$prc_cabecera = <<<'PHP'
<?php
/**
 * Plugin Name: PRC — Procedimientos (envoltorio de comprobación)
 * Description: Envoltorio desechable. Existe solo para que Plugin Check pueda leer el código que en producción se pega en Code Snippets. No se despliega y no se versiona.
 * Version: 0.0.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package Prc
 */

defined( 'ABSPATH' ) || exit;

PHP;

if ( false === file_put_contents( $prc_destino, $prc_cabecera ) ) {
	fwrite( STDERR, "No se pudo escribir {$prc_destino}\n" );
	exit( 1 );
}
