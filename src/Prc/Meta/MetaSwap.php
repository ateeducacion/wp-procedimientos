<?php
/**
 * Compare-and-swap on one post meta: write only if nobody wrote in between.
 *
 * @package Prc
 */

namespace Prc\Meta;

/**
 * Escribir una meta solo si sigue como se leyó (ADR-0041).
 *
 * Leer, cambiar y escribir una meta no es atómico: dos peticiones que leen lo
 * mismo y escriben cada una lo suyo pierden una de las dos escrituras. Aquí
 * la escritura lleva el valor que se leyó y la base de datos solo la hace si
 * la fila sigue teniéndolo:
 *
 * - con la meta ya guardada, un único `UPDATE … WHERE meta_value = $leido`
 *   con comparación binaria —distingue mayúsculas y tildes—, que no escribe
 *   si no casa. No pasa por `update_post_meta()`, que sin fila guardada la
 *   vuelve a crear sin mirar el valor anterior, y compara con la collation de
 *   la tabla, que no distingue mayúsculas;
 * - sin la meta, `add_post_meta( …, true )`, que no añade si otra petición la
 *   creó entretanto.
 *
 * Una lista vacía se guarda vacía, no se borra: así una escritura que llegue
 * después de vaciarla no casa y no resucita lo que se quitó.
 *
 * Si no casa, se relee, se vuelve a calcular el cambio —y a validarlo— y se
 * reintenta.
 */
final class MetaSwap {

	/**
	 * Change one meta from what it holds now, retrying if somebody wrote in between.
	 *
	 * `$change` recibe lo guardado ahora —`null` si no hay meta— y devuelve
	 * `array( 'value' => …, 'error' => '' )` más lo que quiera llevar de vuelta.
	 * Con `error`, no se escribe nada y se devuelve tal cual; con `skip`, no
	 * hay nada que escribir y se da por hecho.
	 *
	 * @param int      $post_id Post ID.
	 * @param string   $key     Meta key.
	 * @param callable $change  What to write, from what there is.
	 * @param int      $tries   How many times to try.
	 * @return array<string, mixed> What `$change` returned last, with `ok`; `error` = `conflict` when every try lost.
	 */
	public static function change( int $post_id, string $key, callable $change, int $tries = 2 ): array {
		$res   = array();
		$veces = max( 1, $tries );
		for ( $i = 0; $i < $veces; ++$i ) {
			wp_cache_delete( $post_id, 'post_meta' );
			$leido = get_metadata_raw( 'post', $post_id, $key, true );
			$res   = (array) $change( $leido ) + array(
				'value' => null,
				'error' => '',
			);
			if ( '' !== (string) $res['error'] || ! empty( $res['skip'] ) ) {
				return array( 'ok' => '' === (string) $res['error'] ) + $res;
			}
			if ( self::swap( $post_id, $key, $leido, $res['value'] ) ) {
				wp_cache_delete( $post_id, 'post_meta' );
				return array( 'ok' => true ) + $res;
			}
		}
		return array(
			'ok'    => false,
			'error' => 'conflict',
		) + $res;
	}

	/**
	 * Write the new value only if the stored one is still the one read.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key.
	 * @param mixed  $read    What was read; null when there was no meta.
	 * @param mixed  $value   What to write; an empty array is stored empty.
	 * @return bool Whether it was written.
	 */
	private static function swap( int $post_id, string $key, $read, $value ): bool {
		global $wpdb;
		if ( null === $read ) {
			return array() === $value || false !== add_post_meta( $post_id, $key, wp_slash( $value ), true );
		}
		$value = sanitize_meta( $key, $value, 'post', (string) get_post_type( $post_id ) );
		// Same short-circuit update_metadata() offers.
		$check = apply_filters( 'update_post_metadata', null, $post_id, $key, $value, $read ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core hook.
		if ( null !== $check ) {
			return (bool) $check;
		}
		$stored = maybe_serialize( $value );
		$prev   = maybe_serialize( $read );
		if ( $stored === $prev ) {
			return true;
		}
		$rows = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- compare-and-swap; the cache is cleared below.
			$wpdb->prepare(
				'UPDATE %i SET meta_value = %s WHERE post_id = %d AND meta_key = %s AND CAST(meta_value AS BINARY) = CAST(%s AS BINARY)',
				$wpdb->postmeta,
				$stored,
				$post_id,
				$key,
				$prev
			)
		);
		wp_cache_delete( $post_id, 'post_meta' );
		return is_int( $rows ) && $rows > 0;
	}
}
