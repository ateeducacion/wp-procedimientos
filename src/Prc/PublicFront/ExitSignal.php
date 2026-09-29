<?php
/**
 * Thrown instead of exit() under tests: says where the request was going.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

/**
 * Lo que Shell::leave() lanza cuando el filtro `prc_exit_throws` está puesto
 * —solo en tests—. Lleva la URL de vuelta, o nada si lo que se acaba de servir
 * es un documento.
 */
final class ExitSignal extends \RuntimeException {

	/**
	 * Where the request was being sent; empty after serving a document.
	 *
	 * @var string
	 */
	public $url = '';

	/**
	 * Constructor.
	 *
	 * @param string $url Return URL, or empty.
	 */
	public function __construct( string $url = '' ) {
		parent::__construct( '' === $url ? 'Salida sin redirección' : 'Redirección a ' . $url );
		$this->url = $url;
	}
}
