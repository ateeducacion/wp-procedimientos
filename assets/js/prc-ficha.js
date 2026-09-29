/*
 * Lo que la ficha de un procedimiento no puede hacer sin guion, y solo eso.
 *
 * Las dos cosas son mejora sobre algo que ya funciona sin ellas: el servidor
 * pinta las cuatro cifras del contador con el tiempo que quedaba al generar la
 * página —sin guion se ve bien, solo se queda quieto— y los paneles de
 * contenido salen todos visibles, cada uno con su rótulo, con la barra como
 * una lista de anclas de verdad. Aquí la barra se asciende a pestañas.
 */
( function () {
	'use strict';

	/* --- 1. El contador regresivo ---------------------------------------- */

	/*
	 * El instante final llega en `data-fin` como ISO 8601 con zona: el último
	 * día del plazo a las 23:59:00 donde esté el sitio, no donde esté quien
	 * mira. Al llegar a cero se para el reloj; el estado del procedimiento no
	 * lo decide esto, que se deriva en el servidor y por día.
	 */
	function cuenta( caja ) {
		var fin = Date.parse( caja.getAttribute( 'data-fin' ) );
		if ( isNaN( fin ) ) {
			return;
		}
		var celdas = {};
		Array.prototype.forEach.call( caja.querySelectorAll( '[data-prc-cuenta]' ), function ( nodo ) {
			celdas[ nodo.getAttribute( 'data-prc-cuenta' ) ] = nodo;
		} );

		function escribe( clave, valor, digitos ) {
			if ( celdas[ clave ] ) {
				celdas[ clave ].textContent = String( valor ).padStart( digitos, '0' );
			}
		}

		function pinta() {
			var queda = Math.max( 0, Math.floor( ( fin - Date.now() ) / 1000 ) );
			escribe( 'dias', Math.floor( queda / 86400 ), 3 );
			escribe( 'horas', Math.floor( queda / 3600 ) % 24, 2 );
			escribe( 'minutos', Math.floor( queda / 60 ) % 60, 2 );
			escribe( 'segundos', queda % 60, 2 );
			if ( 0 === queda ) {
				clearInterval( reloj );
			}
		}

		var reloj = setInterval( pinta, 1000 );
		pinta();
	}

	/* --- 2. Las pestañas de contenido ------------------------------------ */

	/*
	 * Sin guion los paneles son secciones seguidas y la barra salta a cada una,
	 * que es marcado correcto y se lee. Con guion pasan a ser pestañas de
	 * verdad: roles, `aria-selected`, un solo panel visible y navegación por
	 * flechas (Inicio y Fin a los extremos), como manda el patrón.
	 *
	 * Los roles se ponen AQUÍ y no en el servidor a propósito: un `role="tab"`
	 * sobre una lista de anclas que no esconde nada le mentiría a quien navega
	 * con lector de pantalla cuando el guion no llega.
	 */
	function pestanas( caja ) {
		var barra = caja.querySelector( '.prc-tabs-ficha__barra' );
		if ( ! barra ) {
			return;
		}
		var tabs = Array.prototype.slice.call( barra.querySelectorAll( 'a' ) );
		var paneles = tabs.map( function ( tab ) {
			return document.getElementById( ( tab.getAttribute( 'href' ) || '' ).slice( 1 ) );
		} );
		if ( ! tabs.length || paneles.indexOf( null ) !== -1 ) {
			return;
		}

		function muestra( activa, foco ) {
			tabs.forEach( function ( tab, i ) {
				tab.setAttribute( 'aria-selected', i === activa ? 'true' : 'false' );
				tab.setAttribute( 'tabindex', i === activa ? '0' : '-1' );
				paneles[ i ].hidden = i !== activa;
			} );
			if ( foco ) {
				tabs[ activa ].focus();
			}
		}

		barra.setAttribute( 'role', 'tablist' );
		Array.prototype.forEach.call( barra.querySelectorAll( 'li' ), function ( li ) {
			li.setAttribute( 'role', 'presentation' );
		} );

		tabs.forEach( function ( tab, i ) {
			tab.setAttribute( 'role', 'tab' );
			tab.setAttribute( 'aria-controls', paneles[ i ].id );
			paneles[ i ].setAttribute( 'role', 'tabpanel' );
			paneles[ i ].setAttribute( 'aria-labelledby', tab.id );
			// El panel no siempre tiene dentro algo que reciba el foco: sin
			// esto, con teclado no se llega a leerlo.
			paneles[ i ].setAttribute( 'tabindex', '0' );

			tab.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				muestra( i, true );
			} );

			tab.addEventListener( 'keydown', function ( e ) {
				var saltos = {
					ArrowLeft: -1,
					ArrowRight: 1,
					Home: -i,
					End: tabs.length - 1 - i
				};
				if ( ! ( e.key in saltos ) ) {
					return;
				}
				e.preventDefault();
				muestra( ( i + saltos[ e.key ] + tabs.length ) % tabs.length, true );
			} );
		} );

		caja.classList.add( 'prc-tabs-ficha--js' );
		// Con un enlace a un panel concreto se abre ese, no el primero.
		var pedida = paneles.map( function ( panel ) {
			return '#' + panel.id;
		} ).indexOf( window.location.hash );
		muestra( pedida === -1 ? 0 : pedida, false );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		Array.prototype.forEach.call( document.querySelectorAll( '.prc-cuenta[data-fin]' ), cuenta );
		Array.prototype.forEach.call( document.querySelectorAll( '.prc-tabs-ficha' ), pestanas );
	} );
}() );
