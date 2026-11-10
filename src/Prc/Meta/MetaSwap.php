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
 * - con la meta ya guardada, `update_post_meta( …, $nuevo, $leido )`, que
 *   actualiza `WHERE meta_value = $leido` y devuelve `false` si no casa;
 * - sin la meta, `add_post_meta( …, true )`, que no añade si otra petición la
 *   creó entretanto;
 * - para dejarla vacía, `delete_post_meta( …, $leido )`, que tampoco borra si
 *   ya no casa.
 *
 * Si no casa, se relee, se vuelve a calcular el cambio —y a validarlo— y se
 * reintenta. Queda un hueco: si otra petición **borra** la meta entre la
 * lectura y la escritura, `update_post_meta()` la vuelve a crear con lo
 * calculado sobre lo leído. Solo pasa cuando la otra vació la lista entera.
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
	 * @param mixed  $value   What to write; an empty array removes the meta.
	 * @return bool Whether it was written.
	 */
	private static function swap( int $post_id, string $key, $read, $value ): bool {
		if ( null === $read ) {
			return array() === $value || false !== add_post_meta( $post_id, $key, wp_slash( $value ), true );
		}
		if ( empty( $read ) ) {
			// Una meta guardada vacía no se puede comparar: `update_post_meta()`
			// toma un valor anterior vacío como «sin condición». Se quita y se
			// crea de nuevo, otra vez solo si nadie la creó entretanto.
			delete_post_meta( $post_id, $key );
			return array() === $value || false !== add_post_meta( $post_id, $key, wp_slash( $value ), true );
		}
		if ( array() === $value ) {
			return false !== delete_post_meta( $post_id, $key, wp_slash( $read ) );
		}
		return false !== update_post_meta( $post_id, $key, wp_slash( $value ), $read );
	}
}
