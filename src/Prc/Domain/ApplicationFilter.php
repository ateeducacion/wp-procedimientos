<?php
/**
 * The filter of the applications table: what the URL asks for, checked.
 *
 * @package Prc
 */

namespace Prc\Domain;

use Prc\Meta\ApplicationMetaKeys;

/**
 * El filtro de la tabla de solicitudes del taller. Puro: no lee la petición
 * ni consulta nada.
 *
 * Todo viaja por GET —se puede guardar como marcador y funciona sin guion—,
 * así que lo que llega puede ser cualquier cosa. Lo que no es un valor del
 * filtro se ignora: un enlace viejo o tecleado a mano abre la tabla, no un
 * error. La isla y la zona se miden además contra las que de verdad tienen
 * las solicitudes del procedimiento ({@see against()}).
 */
final class ApplicationFilter {

	/** Query arg of the review state. */
	public const ARG_REVIEW = 'revision';

	/** Query arg of the island. */
	public const ARG_ISLAND = 'isla';

	/** Query arg of the zone: the code of the zone centre. */
	public const ARG_ZONE = 'zona';

	/** Query arg of «solo las incompletas»: `1`. */
	public const ARG_INCOMPLETE = 'incompletas';

	/** Query arg of the free-text search. */
	public const ARG_SEARCH = 'buscar';

	/** Query arg of the page, from 1. */
	public const ARG_PAGE = 'pagina';

	/** Applications per page. */
	public const PER_PAGE = 50;

	/** Longest search term kept, in characters. */
	public const MAX_SEARCH = 100;

	/**
	 * Each key of the filter and the query arg it travels in, in URL order.
	 *
	 * @var array<string, string>
	 */
	private const ARGS = array(
		'review'     => self::ARG_REVIEW,
		'island'     => self::ARG_ISLAND,
		'zone'       => self::ARG_ZONE,
		'incomplete' => self::ARG_INCOMPLETE,
		'search'     => self::ARG_SEARCH,
		'page'       => self::ARG_PAGE,
	);

	/**
	 * Letters folded for the search: lower case and without accents.
	 *
	 * Se busca a mano, no con `remove_accents()`: esto es puro y lo que hace
	 * falta son las letras del castellano y poco más.
	 *
	 * @var array<string, string>
	 */
	private const FOLD = array(
		'á' => 'a',
		'à' => 'a',
		'â' => 'a',
		'ä' => 'a',
		'é' => 'e',
		'è' => 'e',
		'ê' => 'e',
		'ë' => 'e',
		'í' => 'i',
		'ì' => 'i',
		'î' => 'i',
		'ï' => 'i',
		'ó' => 'o',
		'ò' => 'o',
		'ô' => 'o',
		'ö' => 'o',
		'ú' => 'u',
		'ù' => 'u',
		'û' => 'u',
		'ü' => 'u',
		'ñ' => 'n',
		'ç' => 'c',
	);

	/**
	 * The query args of the filter, in URL order.
	 *
	 * @return array<string, string> Filter key => query arg.
	 */
	public static function args(): array {
		return self::ARGS;
	}

	/**
	 * What the request asks for, checked.
	 *
	 * Llega ya sin barras y saneado como texto; aquí se decide qué es un
	 * valor del filtro. Un estado que no es de revisión, una casilla que no
	 * dice `1` o una página que no es un entero positivo se ignoran.
	 *
	 * @param array<string, mixed> $raw Query arg => value.
	 * @return array{review:string, island:string, zone:string, incomplete:bool, search:string, page:int}
	 */
	public static function sanitize( array $raw ): array {
		$estado = self::text( $raw, self::ARG_REVIEW, 32 );
		$pagina = self::text( $raw, self::ARG_PAGE, 9 );
		return array(
			'review'     => isset( ApplicationMetaKeys::review_states()[ $estado ] ) ? $estado : '',
			'island'     => self::text( $raw, self::ARG_ISLAND, 100 ),
			'zone'       => self::text( $raw, self::ARG_ZONE, 32 ),
			'incomplete' => '1' === self::text( $raw, self::ARG_INCOMPLETE, 1 ),
			'search'     => self::text( $raw, self::ARG_SEARCH, self::MAX_SEARCH ),
			'page'       => ctype_digit( $pagina ) && (int) $pagina > 0 ? (int) $pagina : 1,
		);
	}

	/**
	 * The filter without an island or a zone that none of the applications has.
	 *
	 * @param array<string, mixed>              $filter  What {@see sanitize()} returned.
	 * @param array{islands:array, zones:array} $options Islands and zones present, keyed by value.
	 * @return array<string, mixed>
	 */
	public static function against( array $filter, array $options ): array {
		foreach ( array(
			'island' => 'islands',
			'zone'   => 'zones',
		) as $clave => $lista ) {
			if ( '' !== $filter[ $clave ] && ! isset( $options[ $lista ][ $filter[ $clave ] ] ) ) {
				$filter[ $clave ] = '';
			}
		}
		return $filter;
	}

	/**
	 * Whether anything narrows the table. The page does not.
	 *
	 * @param array<string, mixed> $filter What {@see sanitize()} returned.
	 * @return bool
	 */
	public static function active( array $filter ): bool {
		return '' !== $filter['review'] || '' !== $filter['island'] || '' !== $filter['zone']
			|| true === $filter['incomplete'] || '' !== $filter['search'];
	}

	/**
	 * The query args that carry the filter, only what is not the default.
	 *
	 * Cambiar cualquier cosa que no sea la página vuelve a la primera: la
	 * página 3 de otro filtro no es la página 3 de este.
	 *
	 * @param array<string, mixed> $filter  What {@see sanitize()} returned.
	 * @param array<string, mixed> $changes Filter keys to change.
	 * @return array<string, string> Query arg => value, not URL-encoded.
	 */
	public static function query( array $filter, array $changes = array() ): array {
		if ( array() !== $changes && ! array_key_exists( 'page', $changes ) ) {
			$changes['page'] = 1;
		}
		$f   = array_merge( $filter, $changes );
		$out = array();
		foreach ( self::ARGS as $clave => $arg ) {
			$valor = $f[ $clave ] ?? '';
			if ( 'incomplete' === $clave ) {
				$valor = true === $valor ? '1' : '';
			} elseif ( 'page' === $clave ) {
				$valor = (int) $valor > 1 ? (string) (int) $valor : '';
			}
			if ( '' !== (string) $valor ) {
				$out[ $arg ] = (string) $valor;
			}
		}
		return $out;
	}

	/**
	 * Which slice of the filtered applications a page shows.
	 *
	 * Una página que no existe cae en la última: el enlace a la página 4
	 * después de revisar las últimas de un filtro sigue enseñando algo.
	 *
	 * @param int $total    Filtered applications.
	 * @param int $page     Page asked for.
	 * @param int $per_page Applications per page.
	 * @return array{page:int, pages:int, offset:int, from:int, to:int} `from` and `to` count from 1; both 0 when there is none.
	 */
	public static function window( int $total, int $page, int $per_page = self::PER_PAGE ): array {
		$total  = max( 0, $total );
		$pages  = max( 1, (int) ceil( $total / max( 1, $per_page ) ) );
		$page   = min( max( 1, $page ), $pages );
		$offset = ( $page - 1 ) * $per_page;
		return array(
			'page'   => $page,
			'pages'  => $pages,
			'offset' => $offset,
			'from'   => 0 === $total ? 0 : $offset + 1,
			'to'     => min( $total, $offset + $per_page ),
		);
	}

	/**
	 * Whether a term is in any of the texts, ignoring case and accents.
	 *
	 * Se compara texto con texto, no con `LIKE`: un `%` o un `_` tecleado es
	 * un `%` o un `_`, no un comodín.
	 *
	 * @param string   $term  What was typed.
	 * @param string[] $texts Where to look.
	 * @return bool True when the term is empty.
	 */
	public static function matches( string $term, array $texts ): bool {
		$term = self::fold( $term );
		if ( '' === $term ) {
			return true;
		}
		foreach ( $texts as $texto ) {
			if ( false !== strpos( self::fold( (string) $texto ), $term ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * A text folded for the search.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	private static function fold( string $text ): string {
		$text = function_exists( 'mb_strtolower' ) ? mb_strtolower( $text, 'UTF-8' ) : strtolower( $text );
		return strtr( trim( $text ), self::FOLD );
	}

	/**
	 * One value of the request as a single line of text, or empty.
	 *
	 * @param array<string, mixed> $raw Request.
	 * @param string               $key Query arg.
	 * @param int                  $max Longest kept, in characters.
	 * @return string
	 */
	private static function text( array $raw, string $key, int $max ): string {
		$valor = $raw[ $key ] ?? '';
		if ( ! is_string( $valor ) && ! is_int( $valor ) ) {
			return '';
		}
		$valor = trim( (string) preg_replace( '/[\s\x00-\x1f]+/u', ' ', (string) $valor ) );
		return function_exists( 'mb_substr' ) ? mb_substr( $valor, 0, $max, 'UTF-8' ) : substr( $valor, 0, $max );
	}
}
