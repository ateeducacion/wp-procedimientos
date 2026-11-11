<?php
/**
 * Tests for ApplicationLog: the history of an application.
 *
 * @package Prc
 */

use Prc\Meta\ApplicationMetaKeys;
use Prc\PostType\ApplicationPostType;
use Prc\PublicFront\ApplicationLog;

/**
 * El historial de una solicitud: quién, cuándo y qué (ADR-0041).
 *
 * Lo escribe el aplicativo y nadie más: ni el escritorio, ni la REST, ni
 * quien revisa.
 */
class Test_Application_Log extends WP_UnitTestCase {

	use Prc_Fixtures;

	/**
	 * Una solicitud de un centro a un procedimiento de prueba.
	 *
	 * @return int Application ID.
	 */
	private function solicitud(): int {
		$procedimiento = $this->procedure( $this->administrator(), array( $this->area( 'Área de prueba' ) ) );
		return $this->application( $procedimiento, $this->school_head( 'C0001' ) );
	}

	/**
	 * Cada entrada lleva quién, cuándo —en UTC, ISO 8601—, qué y sus datos,
	 * y salen en el orden en que se escribieron.
	 */
	public function test_each_entry_says_who_when_and_what() {
		$id  = $this->solicitud();
		$uid = $this->school_head( 'C0001' );
		$this->acting_as( $uid );
		ApplicationLog::add(
			$id,
			'person_added',
			array(
				'before' => array(),
				'after'  => array( 'name' => 'Ana' ),
			)
		);
		ApplicationLog::add( $id, 'person_removed', array( 'before' => array( 'name' => 'Ana' ) ) );

		$todo = ApplicationLog::all( $id );
		$this->assertCount( 2, $todo );
		$this->assertSame( array( 'person_added', 'person_removed' ), array_column( $todo, 'event' ) );
		$this->assertSame( $uid, $todo[0]['user'] );
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $todo[0]['at'] );
		$this->assertSame( 'Ana', $todo[0]['data']['after']['name'] );
	}

	/**
	 * Lo que no tiene forma de entrada no se guarda, y los textos se limpian.
	 */
	public function test_what_is_not_an_entry_is_dropped() {
		$limpio = ApplicationLog::sanitize(
			array(
				'no es una entrada',
				array( 'event' => 'Con Espacios' ),
				array(
					'event' => 'person_added',
					'at'    => '2026-11-10T09:00:00Z',
					'user'  => '7',
					'data'  => array( 'after' => array( 'name' => '<b>Ana</b>' ) ),
				),
			)
		);
		$this->assertCount( 1, $limpio );
		$this->assertSame( 7, $limpio[0]['user'] );
		$this->assertSame( 'Ana', $limpio[0]['data']['after']['name'] );
	}

	/**
	 * El historial tiene tope: al llenarse se van las entradas más antiguas
	 * y la primera dice cuántas se han ido, sumando las de cada vez.
	 */
	public function test_the_history_keeps_the_newest_entries_and_says_how_many_went() {
		$id    = $this->solicitud();
		$viejo = array();
		for ( $i = 0; $i < ApplicationLog::MAX; ++$i ) {
			$viejo[] = array(
				'event' => 'old',
				'at'    => '2026-01-01T00:00:00Z',
				'user'  => 1,
				'data'  => array( 'n' => $i ),
			);
		}
		update_post_meta( $id, ApplicationMetaKeys::LOG, $viejo );
		ApplicationLog::add( $id, 'new', array() );

		$todo = ApplicationLog::all( $id );
		$this->assertCount( ApplicationLog::MAX, $todo );
		$this->assertSame( ApplicationLog::TRIMMED, $todo[0]['event'] );
		$this->assertSame( 2, $todo[0]['data']['dropped'] );
		$this->assertSame( 2, $todo[1]['data']['n'], 'las dos primeras se fueron' );
		$this->assertSame( 'new', end( $todo )['event'] );

		ApplicationLog::add( $id, 'newer', array() );
		$todo = ApplicationLog::all( $id );
		$this->assertCount( ApplicationLog::MAX, $todo );
		$this->assertSame( 3, $todo[0]['data']['dropped'] );
		$this->assertSame( 3, $todo[1]['data']['n'] );
	}

	/**
	 * Una entrada que otra petición escribe entre la lectura y la escritura
	 * no se pierde.
	 */
	public function test_an_entry_written_in_between_is_not_lost() {
		$id = $this->solicitud();
		ApplicationLog::add( $id, 'first', array() );
		$hecho   = false;
		$colarse = static function ( $corto, $object_id, $meta_key ) use ( $id, &$hecho ) {
			global $wpdb;
			if ( $hecho || (int) $object_id !== $id || ApplicationMetaKeys::LOG !== $meta_key ) {
				return $corto;
			}
			$hecho  = true;
			$suyo   = ApplicationLog::all( $id );
			$suyo[] = array(
				'at'    => '2026-11-10T10:00:00Z',
				'user'  => 1,
				'event' => 'other',
				'data'  => array(),
			);
			$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- simula otra petición.
				$wpdb->postmeta,
				array( 'meta_value' => maybe_serialize( $suyo ) ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				array(
					'post_id'  => $id,
					'meta_key' => ApplicationMetaKeys::LOG, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				)
			);
			return $corto;
		};
		add_filter( 'update_post_metadata', $colarse, 10, 3 );
		$this->assertTrue( ApplicationLog::add( $id, 'mine', array() ) );
		wp_cache_delete( $id, 'post_meta' );
		$this->assertSame( array( 'first', 'other', 'mine' ), array_column( ApplicationLog::all( $id ), 'event' ) );
	}

	/**
	 * Nadie lo escribe por fuera del aplicativo: ni administración por la
	 * meta, ni la REST, que no la publica.
	 */
	public function test_nobody_writes_it_from_outside() {
		$id = $this->solicitud();
		$this->assertFalse( user_can( $this->administrator(), 'edit_post_meta', $id, ApplicationMetaKeys::LOG ) );
		$this->assertFalse( user_can( $this->administrator(), 'edit_post_meta', $id, ApplicationMetaKeys::PEOPLE ) );
		$registradas = get_registered_meta_keys( 'post', ApplicationPostType::POST_TYPE );
		$this->assertFalse( $registradas[ ApplicationMetaKeys::LOG ]['show_in_rest'] );
		$this->assertFalse( $registradas[ ApplicationMetaKeys::PEOPLE ]['show_in_rest'] );
	}
}
