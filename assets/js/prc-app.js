/*
 * Lo mínimo que las pantallas del aplicativo no pueden hacer sin guion.
 *
 * Todo es mejora sobre algo que ya funciona sin ellas: si el guion no llega,
 * el formulario se envía igual, el aviso del bloqueo ya viene pintado desde
 * el servidor, el `title` del botón sigue ahí, el botón de publicar hace lo
 * mismo que el interruptor y el diálogo de exportar es un desplegable. Nada aquí valida ni autoriza:
 * eso está en el servidor, con su nonce y su comprobación de ProcedureAccess.
 *
 * Delegado en `document`: las pantallas repintan trozos y un `addEventListener`
 * por nodo se quedaría atrás.
 */
( function () {
	'use strict';

	/* --- 1. Confirmar antes de lo que no tiene vuelta atrás -------------- */

	/*
	 * `data-prc-confirm="¿Seguro que…?"` en el formulario. Se pregunta al
	 * enviar, que es el único momento en que se pierde algo.
	 *
	 * Tres escalones, y los tres hacen la acción:
	 *   1. Con SweetAlert2 (`snippets/sweetalert.php`), un diálogo en
	 *      castellano cuyo botón dice el verbo —«Marcar como histórico»— y no
	 *      «OK». Atrapa el foco y se cierra con Escape; lo hace la librería.
	 *   2. Sin ella —el CDN no contesta, el SRI no cuadra—, el `confirm()` del
	 *      navegador de siempre.
	 *   3. Sin guion, el botón envía el formulario y la acción se hace.
	 * Nunca se pierde una acción porque una librería no llegara.
	 *
	 * El verbo sale de `data-prc-confirm-ok` si el formulario lo pone y, si no,
	 * del `title` del botón, que ya es la acción escrita porque es lo que lee
	 * quien navega con lector de pantalla.
	 */
	function verbo( form, boton ) {
		return form.getAttribute( 'data-prc-confirm-ok' ) ||
			( boton && boton.getAttribute( 'title' ) ) ||
			'Sí, continuar';
	}

	document.addEventListener( 'submit', function ( e ) {
		var form = e.target;
		// La marca la pone el envío que sale del diálogo: se deja pasar.
		if ( ! ( form instanceof HTMLFormElement ) || '1' === form.dataset.prcConfirmado ) {
			return;
		}
		var pregunta = form.getAttribute( 'data-prc-confirm' );
		if ( ! pregunta ) {
			return;
		}

		if ( ! window.Swal ) {
			if ( ! window.confirm( pregunta ) ) {
				e.preventDefault();
			}
			return;
		}

		// SweetAlert2 contesta con una promesa, así que el envío se para y se
		// repite luego; el `confirm()` de arriba, no, y por eso va aparte.
		e.preventDefault();
		var boton = e.submitter || form.querySelector( '[type="submit"]' );
		// «¿Marcar «X» como histórico? No hay vuelta atrás.» → titular y detalle.
		var corte = pregunta.indexOf( '? ' );
		window.Swal.fire( {
			title: -1 === corte ? pregunta : pregunta.slice( 0, corte + 1 ),
			text: -1 === corte ? '' : pregunta.slice( corte + 2 ),
			icon: 'warning',
			showCancelButton: true,
			confirmButtonText: verbo( form, boton ),
			cancelButtonText: 'Cancelar',
			// Lo que no tiene vuelta atrás no se confirma sin querer.
			focusCancel: true,
			reverseButtons: true,
			// Sin tocar el alto del `html`: la barra de pestañas es pegajosa.
			heightAuto: false,
			// Los botones son los de la página, no los de la librería.
			buttonsStyling: false,
			customClass: {
				confirmButton: 'prc-btn prc-btn-borrar btn',
				cancelButton: 'prc-btn prc-btn-sec btn btn-outline-secondary'
			}
		} ).then( function ( respuesta ) {
			if ( ! respuesta.isConfirmed ) {
				return;
			}
			form.dataset.prcConfirmado = '1';
			if ( form.requestSubmit ) {
				// Con el mismo botón: si algún día lleva `name`, sigue viajando.
				form.requestSubmit( boton || undefined );
			} else {
				form.submit();
			}
		} );
	} );

	/* --- 2. El bloqueo de edición, con el Heartbeat de WordPress --------- */

	/*
	 * `EditLock::render()` pinta un `#prc-edit-lock` con el procedimiento, el
	 * bloqueo que se tiene ahora (`data-lock`) y el nonce para soltarlo. Todo
	 * lo de aquí es el mecanismo NATIVO del escritorio, sin inventar nada
	 * (ADR-0008):
	 *
	 *   - `wp-refresh-post-lock` viaja en cada latido del Heartbeat: renueva el
	 *     bloqueo propio y, si otra persona tomó posesión, contesta con su
	 *     nombre. Es la misma llamada que hace el editor de WordPress, así que
	 *     los dos sitios se enteran el uno del otro.
	 *   - `wp-remove-post-lock` suelta el bloqueo al cerrar la pestaña.
	 *
	 * Sin guion no se pierde nada: el aviso ya viene pintado como
	 * `<dialog open>` desde el servidor cuando el procedimiento está cogido, y
	 * el servidor vuelve a comprobarlo con un 409 antes de escribir. Esto solo
	 * evita el susto de estar media hora escribiendo algo que ya no se puede
	 * guardar.
	 *
	 * En `DOMContentLoaded` porque el guion del aplicativo se imprime en el pie
	 * sin depender de jQuery ni del Heartbeat, y a esas alturas los dos ya
	 * están cargados.
	 */
	document.addEventListener( 'DOMContentLoaded', function () {
		var caja = document.getElementById( 'prc-edit-lock' );
		// Sin `data-lock` el bloqueo lo tiene otra persona: no hay nada que
		// renovar, y pedirlo sería pedir la renovación de un bloqueo ajeno.
		if ( ! caja || ! caja.dataset.lock || ! window.jQuery || ! window.wp || ! window.wp.heartbeat ) {
			return;
		}

		var $ = window.jQuery;
		var dialogo = document.getElementById( 'prc-lock-dialog' );
		var cerradura = caja.dataset.lock;
		var perdido = false;
		var enviando = false;

		// Todo lo de la pantalla menos el propio aviso, que es lo único que
		// tiene que seguir funcionando cuando el bloqueo se pierde.
		function fuera( nodo ) {
			return ! caja.contains( nodo );
		}

		$( document ).on( 'heartbeat-send.prcLock', function ( e, data ) {
			if ( ! perdido ) {
				data['wp-refresh-post-lock'] = {
					post_id: Number( caja.dataset.postId ),
					lock: cerradura
				};
			}
		} );

		$( document ).on( 'heartbeat-tick.prcLock', function ( e, data ) {
			var respuesta = data['wp-refresh-post-lock'];
			if ( ! respuesta || perdido ) {
				return;
			}
			if ( respuesta.lock_error ) {
				perdido = true;
				// Congelado, no borrado: lo escrito sigue en pantalla para
				// poder copiarlo antes de tomar posesión o de irse.
				Array.prototype.forEach.call( document.querySelectorAll( 'form' ), function ( form ) {
					if ( fuera( form ) ) {
						form.inert = true;
					}
				} );
				document.getElementById( 'prc-lock-owner' ).textContent = respuesta.lock_error.name;
				if ( dialogo.showModal ) {
					dialogo.showModal();
				} else {
					dialogo.setAttribute( 'open', '' );
				}
			} else if ( respuesta.new_lock ) {
				cerradura = respuesta.new_lock;
			}
		} );

		// El aviso no se cierra con Escape: no es una confirmación, es que no
		// se puede editar, y cerrarlo devolvería una pantalla que miente.
		dialogo.addEventListener( 'cancel', function ( e ) {
			e.preventDefault();
		} );

		// En captura, antes que la confirmación de SweetAlert2: si el bloqueo
		// ya se perdió, el envío no sale ni siquiera al servidor.
		document.addEventListener( 'submit', function ( e ) {
			if ( perdido && fuera( e.target ) ) {
				e.preventDefault();
				e.stopImmediatePropagation();
			}
		}, true );

		// Como el editor clásico: al enviar NO se suelta el bloqueo, que a
		// partir de ahí es del servidor. Soltarlo aquí volvería a crear con la
		// baliza el bloqueo que el propio guardado acaba de borrar.
		window.addEventListener( 'submit', function ( e ) {
			if ( fuera( e.target ) && ! e.defaultPrevented ) {
				enviando = true;
			}
		} );

		window.addEventListener( 'pagehide', function () {
			if ( perdido || enviando || ! navigator.sendBeacon || ! caja.dataset.ajaxUrl ) {
				return;
			}
			var datos = new FormData();
			datos.append( 'action', 'wp-remove-post-lock' );
			datos.append( '_wpnonce', caja.dataset.releaseNonce );
			datos.append( 'post_ID', caja.dataset.postId );
			datos.append( 'active_post_lock', cerradura );
			navigator.sendBeacon( caja.dataset.ajaxUrl, datos );
		} );

		// Una página restaurada del historial trae campos viejos y un bloqueo
		// que ya puede ser de otra persona: se vuelve a preguntar al servidor.
		window.addEventListener( 'pageshow', function ( e ) {
			if ( e.persisted ) {
				window.location.reload();
			}
		} );

		window.wp.heartbeat.interval( 15 );
	} );

	/* --- 3. Bocadillos en los botones de icono --------------------------- */

	/*
	 * Un botón de icono no dice qué hace. Lo dice su `title`, y el navegador ya
	 * lo enseña al posarse encima: **esto es mejora, no requisito**. Con
	 * Bootstrap cargado se cambia por su bocadillo, que sale antes y se lee
	 * mejor; sin Bootstrap —o sin guion— queda el `title` de siempre.
	 *
	 * El texto de verdad para quien navega con lector de pantalla no es el
	 * `title` sino el `.screen-reader-text` que va dentro del botón.
	 */
	function bocadillos( raiz ) {
		if ( ! window.bootstrap || ! window.bootstrap.Tooltip ) {
			return;
		}
		var nodos = ( raiz || document ).querySelectorAll( '[data-bs-toggle="tooltip"]' );
		Array.prototype.forEach.call( nodos, function ( nodo ) {
			if ( ! window.bootstrap.Tooltip.getInstance( nodo ) ) {
				new window.bootstrap.Tooltip( nodo );
			}
		} );
	}
	document.addEventListener( 'DOMContentLoaded', function () {
		bocadillos( document );
	} );

	/* --- 4. El interruptor de publicación -------------------------------- */

	/* --- 5. Ajustes exclusivos para solicitudes de centros ---------------- */

	function mostrarCamposDeCentro() {
		var audiencia = document.getElementById( 'prc-audience' );
		if ( ! audiencia ) {
			return;
		}
		var soloCentros = 'schools' === audiencia.value;
		Array.prototype.forEach.call( document.querySelectorAll( '[data-prc-schools-only]' ), function ( caja ) {
			caja.hidden = ! soloCentros;
			Array.prototype.forEach.call( caja.querySelectorAll( 'input' ), function ( input ) {
				input.disabled = ! soloCentros;
			} );
		} );
	}
	document.addEventListener( 'change', function ( e ) {
		if ( e.target && 'prc-audience' === e.target.id ) {
			mostrarCamposDeCentro();
		}
	} );
	document.addEventListener( 'DOMContentLoaded', mostrarCamposDeCentro );

	/*
	 * `data-prc-switch` en la casilla. Al cambiarla se envía su formulario, que
	 * es lo que se espera de un interruptor: se toca y pasa algo.
	 *
	 * El botón de al lado hace lo mismo y es el que queda sin guion; con guion
	 * se esconde, porque teniendo el interruptor sobra. Se esconde **desde
	 * aquí** y no en el CSS a propósito: si el guion no llega, el botón se ve.
	 */
	document.addEventListener( 'change', function ( e ) {
		var casilla = e.target.closest ? e.target.closest( '[data-prc-switch]' ) : null;
		if ( ! casilla ) {
			return;
		}
		var form = casilla.form;
		if ( form ) {
			casilla.disabled = true;
			form.submit();
		}
	} );
	document.addEventListener( 'DOMContentLoaded', function () {
		var botones = document.querySelectorAll( '.prc-switch-boton' );
		Array.prototype.forEach.call( botones, function ( boton ) {
			boton.hidden = true;
		} );
	} );

	/* --- 6. El diálogo de exportar --------------------------------------- */

	/*
	 * `data-prc-export` en un `<details>` con el formulario dentro: sin guion
	 * se despliega y se envía igual, y «Todas las columnas» es un campo que
	 * lee el servidor. Con guion, el formulario pasa a un `<dialog>` modal y
	 * el `<summary>` se cambia por un `<button>` con `aria-haspopup="dialog"`:
	 * un desplegable que abre un diálogo se anunciaría como lo que no es.
	 * «Todas» marca y desmarca las casillas, y solo se ve lo del formato elegido. Nada se desactiva: lo escondido viaja igual y
	 * el servidor recuerda la elección entera (ADR-0039).
	 */
	function abrirDialogo( dialogo ) {
		if ( dialogo.showModal ) {
			dialogo.showModal();
		} else {
			dialogo.setAttribute( 'open', '' );
		}
	}

	function cerrarDialogo( dialogo ) {
		if ( dialogo.close ) {
			dialogo.close();
		} else {
			dialogo.removeAttribute( 'open' );
		}
	}

	function formatoElegido( form ) {
		var marcado = form.querySelector( '[data-prc-export-format]:checked' );
		Array.prototype.forEach.call( form.querySelectorAll( '[data-prc-export-for]' ), function ( caja ) {
			caja.hidden = !! marcado && caja.getAttribute( 'data-prc-export-for' ) !== marcado.value;
		} );
	}

	/*
	 * Con un filtro en la tabla, el diálogo cuenta quién sale en el fichero
	 * de usuarios para «las del filtro» y para «todas»; sin guion se leen las
	 * dos, con su rótulo. Con guion, solo la de la opción elegida.
	 */
	function ambitoElegido( form ) {
		var marcado = form.querySelector( '[data-prc-export-scope]:checked' );
		Array.prototype.forEach.call( form.querySelectorAll( '[data-prc-export-scope-count]' ), function ( cifra ) {
			cifra.hidden = !! marcado && cifra.getAttribute( 'data-prc-export-scope-count' ) !== marcado.value;
		} );
	}

	function prepararExportar( caja ) {
		var form = caja.querySelector( 'form' );
		var resumen = caja.querySelector( 'summary' );
		if ( ! form || ! resumen || caja.dataset.prcDialog ) {
			return;
		}
		var dialogo = document.createElement( 'dialog' );
		dialogo.className = 'prc-dialogo prc-dialogo--exportar';
		dialogo.id = 'prc-exportar-dialogo-' + document.querySelectorAll( '.prc-dialogo--exportar' ).length;
		var titulo = form.getAttribute( 'aria-labelledby' );
		if ( titulo ) {
			dialogo.setAttribute( 'aria-labelledby', titulo );
		}
		var cancelar = document.createElement( 'button' );
		cancelar.type = 'button';
		cancelar.className = 'prc-btn prc-btn-sec btn btn-outline-secondary';
		cancelar.textContent = 'Cancelar';
		cancelar.setAttribute( 'data-prc-export-close', '' );
		var acciones = form.querySelector( '.prc-acciones' ) || form;
		acciones.appendChild( cancelar );

		var abrir = document.createElement( 'button' );
		abrir.type = 'button';
		abrir.className = resumen.className;
		abrir.textContent = resumen.textContent;
		abrir.setAttribute( 'aria-haspopup', 'dialog' );
		abrir.setAttribute( 'aria-controls', dialogo.id );
		abrir.setAttribute( 'data-prc-export-open', dialogo.id );

		dialogo.appendChild( form );
		caja.parentNode.insertBefore( abrir, caja );
		caja.parentNode.insertBefore( dialogo, caja.nextSibling );
		caja.open = false;
		caja.hidden = true;
		caja.dataset.prcDialog = dialogo.id;
		formatoElegido( form );
		ambitoElegido( form );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		Array.prototype.forEach.call( document.querySelectorAll( '[data-prc-export]' ), prepararExportar );
	} );

	document.addEventListener( 'click', function ( e ) {
		var abrir = e.target.closest ? e.target.closest( '[data-prc-export-open]' ) : null;
		var dialogo = abrir ? document.getElementById( abrir.getAttribute( 'data-prc-export-open' ) ) : null;
		if ( dialogo ) {
			abrirDialogo( dialogo );
			return;
		}
		var cerrar = e.target.closest ? e.target.closest( '[data-prc-export-close]' ) : null;
		if ( cerrar && cerrar.closest( 'dialog' ) ) {
			cerrarDialogo( cerrar.closest( 'dialog' ) );
		}
	} );

	document.addEventListener( 'change', function ( e ) {
		var form = e.target.closest ? e.target.closest( '.prc-exportar__form' ) : null;
		if ( ! form ) {
			return;
		}
		var columnas = form.querySelectorAll( '[data-prc-export-col]' );
		var todas = form.querySelector( '[data-prc-export-all]' );
		if ( e.target.hasAttribute( 'data-prc-export-all' ) ) {
			Array.prototype.forEach.call( columnas, function ( casilla ) {
				casilla.checked = e.target.checked;
			} );
		} else if ( e.target.hasAttribute( 'data-prc-export-col' ) && todas ) {
			todas.checked = Array.prototype.every.call( columnas, function ( casilla ) {
				return casilla.checked;
			} );
		} else if ( e.target.hasAttribute( 'data-prc-export-format' ) ) {
			formatoElegido( form );
		} else if ( e.target.hasAttribute( 'data-prc-export-scope' ) ) {
			ambitoElegido( form );
		}
	} );

	// La descarga es la respuesta del envío: la página no cambia, así que el
	// diálogo se cierra a mano, después de que el envío haya salido.
	document.addEventListener( 'submit', function ( e ) {
		var dialogo = e.target.closest ? e.target.closest( '.prc-dialogo--exportar' ) : null;
		if ( dialogo && ! e.defaultPrevented ) {
			window.setTimeout( function () {
				cerrarDialogo( dialogo );
			}, 0 );
		}
	} );

	/* --- 7. Revisar en bloque: marcar las de esta página ---------------- */

	/*
	 * `data-prc-bulk-page` es la casilla «Marcar las de esta página» y
	 * `data-prc-bulk-row`, la de cada fila, que apunta al formulario del bloque
	 * con el atributo `form`. Sin guion, la casilla de la página es un campo que
	 * lee el servidor y revisa la página entera. Con guion, además, marca y
	 * desmarca las de las filas, y se desmarca sola en cuanto falta alguna: así
	 * lo que se ve marcado es lo que se envía.
	 */
	document.addEventListener( 'change', function ( e ) {
		var casilla = e.target;
		if ( ! casilla || ! casilla.hasAttribute ) {
			return;
		}
		var filas = document.querySelectorAll( '[data-prc-bulk-row]' );
		if ( casilla.hasAttribute( 'data-prc-bulk-page' ) ) {
			Array.prototype.forEach.call( filas, function ( fila ) {
				fila.checked = casilla.checked;
			} );
		} else if ( casilla.hasAttribute( 'data-prc-bulk-row' ) ) {
			var pagina = document.querySelector( '[data-prc-bulk-page]' );
			if ( pagina ) {
				pagina.checked = filas.length > 0 && Array.prototype.every.call( filas, function ( fila ) {
					return fila.checked;
				} );
			}
		}
	} );

	/* --- 8. Un solo envío ------------------------------------------------ */

	/*
	 * `data-prc-once` en el formulario: en cuanto el envío sale —ya
	 * confirmado— se apagan sus botones y un segundo envío no sale. Es para lo
	 * que, repetido, hace dos veces lo mismo: duplicar, por ejemplo. En
	 * `window` y no en `document` para ir detrás de la confirmación, que para
	 * el primer envío mientras pregunta. Sin guion se acepta el doble envío.
	 * Al volver con «Atrás» de la caché del navegador se encienden otra vez.
	 */
	function botones( form, apagados ) {
		Array.prototype.forEach.call( form.querySelectorAll( 'button[type="submit"], input[type="submit"]' ), function ( boton ) {
			boton.disabled = apagados;
		} );
	}

	window.addEventListener( 'submit', function ( e ) {
		var form = e.target;
		// Una vez por envío, aunque el guion se haya cargado dos veces.
		if ( e.prcUnaVez || ! ( form instanceof HTMLFormElement ) || ! form.hasAttribute( 'data-prc-once' ) ) {
			return;
		}
		e.prcUnaVez = true;
		if ( '1' === form.dataset.prcEnviado ) {
			e.preventDefault();
			return;
		}
		if ( e.defaultPrevented ) {
			return;
		}
		form.dataset.prcEnviado = '1';
		botones( form, true );
	} );

	window.addEventListener( 'pageshow', function ( e ) {
		if ( ! e.persisted ) {
			return;
		}
		Array.prototype.forEach.call( document.querySelectorAll( 'form[data-prc-once]' ), function ( form ) {
			delete form.dataset.prcEnviado;
			botones( form, false );
		} );
	} );

	/* --- 9. El menú «Más acciones» --------------------------------------- */

	/*
	 * `data-prc-menu` en un `<details>`: sin guion se abre y se cierra con su
	 * `<summary>`, y todo lo de dentro funciona. Con guion, además, se cierra
	 * como se espera de un menú: con Escape —devolviendo el foco a su botón—,
	 * al pulsar fuera y al abrir otro. Mientras el diálogo de SweetAlert2 está
	 * en pantalla, Escape es suyo: cierra el diálogo y no el menú.
	 */
	function menusAbiertos() {
		return document.querySelectorAll( 'details[data-prc-menu][open]' );
	}

	// `toggle` no sube por el árbol: se escucha en captura.
	document.addEventListener( 'toggle', function ( e ) {
		var menu = e.target;
		if ( ! menu || ! menu.matches || ! menu.matches( 'details[data-prc-menu]' ) || ! menu.open ) {
			return;
		}
		Array.prototype.forEach.call( menusAbiertos(), function ( otro ) {
			if ( otro !== menu ) {
				otro.open = false;
			}
		} );
	}, true );

	document.addEventListener( 'keydown', function ( e ) {
		if ( 'Escape' !== e.key || ( window.Swal && window.Swal.isVisible && window.Swal.isVisible() ) ) {
			return;
		}
		Array.prototype.forEach.call( menusAbiertos(), function ( menu ) {
			menu.open = false;
			var boton = menu.querySelector( 'summary' );
			if ( boton ) {
				boton.focus();
			}
		} );
	} );

	document.addEventListener( 'click', function ( e ) {
		Array.prototype.forEach.call( menusAbiertos(), function ( menu ) {
			if ( ! menu.contains( e.target ) ) {
				menu.open = false;
			}
		} );
	} );
}() );
