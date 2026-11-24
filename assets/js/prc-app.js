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

	/*
	 * Las opciones del diálogo de SweetAlert2, las mismas para enviar algo sin
	 * vuelta atrás y para salir con cambios sin guardar.
	 */
	function dialogo( pregunta, ok ) {
		// «¿Marcar «X» como histórico? No hay vuelta atrás.» → titular y detalle.
		var corte = pregunta.indexOf( '? ' );
		return {
			title: -1 === corte ? pregunta : pregunta.slice( 0, corte + 1 ),
			text: -1 === corte ? '' : pregunta.slice( corte + 2 ),
			icon: 'warning',
			showCancelButton: true,
			confirmButtonText: ok,
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
		};
	}

	/*
	 * La pregunta de este envío: la fija del formulario o, si un campo de
	 * fecha lleva `data-prc-confirm-before`, la suya cuando se cambió a un
	 * día anterior a ese —un cierre que, ya pasado, cierra al guardar—. En
	 * blanco, el cierre es el campo que nombra `data-prc-confirm-blank` (el
	 * fin del plazo vacío dura el día de inicio). «AAAA-MM-DD» se compara bien
	 * como texto, y el día de hoy lo pone el servidor: el reloj del navegador
	 * no manda.
	 */
	function preguntaDe( form ) {
		var fija = form.getAttribute( 'data-prc-confirm' );
		if ( fija ) {
			return fija;
		}
		var campos = form.querySelectorAll( '[data-prc-confirm-before]' );
		for ( var i = 0; i < campos.length; i++ ) {
			var campo = campos[ i ];
			var otro = document.getElementById( campo.getAttribute( 'data-prc-confirm-blank' ) || '' );
			var dia = campo.value || ( otro ? otro.value : '' );
			if ( campo.value !== campo.defaultValue && dia && dia < campo.getAttribute( 'data-prc-confirm-before' ) ) {
				return campo.getAttribute( 'data-prc-confirm-text' ) || '';
			}
		}
		return '';
	}

	document.addEventListener( 'submit', function ( e ) {
		var form = e.target;
		// La marca la pone el envío que sale del diálogo: se deja pasar.
		if ( ! ( form instanceof HTMLFormElement ) || '1' === form.dataset.prcConfirmado ) {
			return;
		}
		var pregunta = preguntaDe( form );
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
		window.Swal.fire( dialogo( pregunta, verbo( form, boton ) ) ).then( function ( respuesta ) {
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
		numeroDeCoordinadores();
	}

	/*
	 * El número de personas coordinadoras solo se elige con «Sí, varias
	 * personas»; con profesorado las opciones están apagadas y el número
	 * también, para que no se envíe solo. Sin guion queda activo.
	 */
	function numeroDeCoordinadores() {
		var numero = document.getElementById( 'prc-coordinator-count' );
		var varias = document.querySelector( 'input[name="prc_coordinator_kind"][value="several"]' );
		if ( numero && varias ) {
			numero.disabled = ! varias.checked || varias.disabled;
		}
	}
	document.addEventListener( 'change', function ( e ) {
		if ( e.target && 'prc-audience' === e.target.id ) {
			mostrarCamposDeCentro();
		} else if ( e.target && 'prc_coordinator_kind' === e.target.name ) {
			numeroDeCoordinadores();
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
		// Con sus hijos, no solo el texto: el icono viaja con el rótulo.
		Array.prototype.forEach.call( resumen.childNodes, function ( nodo ) {
			abrir.appendChild( nodo.cloneNode( true ) );
		} );
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

	/* --- 6 bis. La ayuda en un diálogo --------------------------------- */

	/*
	 * `data-prc-help` en un enlace «¿Dudas?»: apunta al `<dialog>` que el
	 * servidor pintó en el pie con solo ese apartado (#86). Se abre con
	 * `showModal()` —foco en el título del apartado— y se cierra sin guion
	 * propio: con Escape o con el «Cerrar» de su `<form method="dialog">`. Al
	 * cerrarse, el foco vuelve al enlace. Sin guion, sin `showModal()` o con una tecla
	 * que pide otra pestaña, el enlace lleva a la ayuda entera como siempre.
	 * Va antes del aviso de cambios sin guardar (sección 10): abrir la ayuda
	 * no sale de la página, y ese aviso no mira un clic ya atendido.
	 */
	document.addEventListener( 'click', function ( e ) {
		var enlace = e.target.closest ? e.target.closest( 'a[data-prc-help]' ) : null;
		var dialogo = enlace ? document.getElementById( enlace.getAttribute( 'data-prc-help' ) ) : null;
		if ( dialogo && dialogo.showModal && ! e.defaultPrevented && 0 === e.button &&
			! e.ctrlKey && ! e.metaKey && ! e.shiftKey && ! e.altKey ) {
			e.preventDefault();
			dialogo.prcVuelta = enlace;
			dialogo.showModal();
			// El título, no el primer enlace: se lee desde arriba. `tabindex`
			// en el `<dialog>` no está permitido; en el título, sí.
			var titulo = dialogo.querySelector( 'h3, h4' );
			if ( titulo ) {
				titulo.tabIndex = -1;
				titulo.focus();
			}
		}
	} );

	// `close` no sube: se escucha en captura. Vale también para Escape.
	document.addEventListener( 'close', function ( e ) {
		if ( e.target.prcVuelta ) {
			e.target.prcVuelta.focus();
			e.target.prcVuelta = null;
		}
	}, true );

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

	/*
	 * «Marcar todos» y «Ninguno» (`data-prc-tick-all="1"` o `"0"`) dentro de un
	 * `data-prc-tick-group`: marcan o desmarcan las casillas del grupo. Salen
	 * escondidos y solo los enseña el guion; sin él, las casillas se marcan
	 * una a una. «Imprimir justificante» (`data-prc-print`), igual: sin guion,
	 * se imprime desde el menú del navegador. Se enseña el contenedor propio
	 * del botón, nunca un antepasado más lejano.
	 */
	document.addEventListener( 'DOMContentLoaded', function () {
		Array.prototype.forEach.call( document.querySelectorAll( '[data-prc-tick-all], [data-prc-print]' ), function ( button ) {
			button.parentElement.hidden = false;
		} );
	} );

	document.addEventListener( 'click', function ( e ) {
		var button = e.target.closest ? e.target.closest( '[data-prc-tick-all]' ) : null;
		var group = button ? button.closest( '[data-prc-tick-group]' ) : null;
		if ( ! group ) {
			return;
		}
		var tick = '1' === button.getAttribute( 'data-prc-tick-all' );
		Array.prototype.forEach.call( group.querySelectorAll( 'input[type="checkbox"]' ), function ( box ) {
			box.checked = tick;
		} );
		// Cuenta como un cambio del formulario, para el aviso de cambios sin guardar.
		marcar( { target: button } );
	} );

	// «Imprimir justificante» abre el diálogo de imprimir del navegador.
	document.addEventListener( 'click', function ( e ) {
		if ( e.target.closest && e.target.closest( '[data-prc-print]' ) ) {
			window.print();
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

	/* --- 10. Cambios sin guardar en el taller ---------------------------- */

	/*
	 * `data-prc-dirty-guard` en los formularios de edición del taller. Al
	 * primer `input` o `change` el formulario queda «sucio» y, mientras lo
	 * esté, salir de la página pide confirmación: el aviso estándar del
	 * navegador al cerrar o recargar, y la pregunta de la sección 1 —SweetAlert2
	 * o `confirm()`— al pulsar una pestaña u otro enlace del sitio. Enviar el
	 * propio formulario limpia la marca. Sin guion, se sale sin aviso.
	 */
	var SIN_GUARDAR = 'Hay cambios sin guardar. ¿Salir sin guardar?';

	function sucios() {
		return document.querySelectorAll( 'form[data-prc-dirty-guard][data-prc-sucio]' );
	}

	function marcar( e ) {
		var form = e.target && e.target.form;
		if ( form && form.hasAttribute( 'data-prc-dirty-guard' ) ) {
			form.setAttribute( 'data-prc-sucio', '' );
		}
	}
	document.addEventListener( 'input', marcar );
	document.addEventListener( 'change', marcar );

	/*
	 * CodeMirror («Código adicional») borra y salta de línea en `keydown`, sin
	 * `input`: se escucha su propio `change`, una vez por editor, al entrar en él.
	 */
	document.addEventListener( 'focusin', function ( e ) {
		var envoltorio = e.target.closest ? e.target.closest( '.CodeMirror' ) : null;
		var cm = envoltorio && envoltorio.CodeMirror;
		if ( ! cm || cm.prcVigilado ) {
			return;
		}
		cm.prcVigilado = true;
		cm.on( 'change', function () {
			marcar( { target: cm.getTextArea() } );
		} );
	} );

	// En `window`, detrás de la confirmación: un envío cancelado sigue sucio.
	window.addEventListener( 'submit', function ( e ) {
		if ( ! e.defaultPrevented && e.target instanceof HTMLFormElement ) {
			e.target.removeAttribute( 'data-prc-sucio' );
		}
	} );

	window.addEventListener( 'beforeunload', function ( e ) {
		if ( sucios().length ) {
			e.preventDefault();
			// Navegadores antiguos: sin `returnValue` no preguntan.
			e.returnValue = '';
		}
	} );

	document.addEventListener( 'click', function ( e ) {
		var enlace = e.target.closest ? e.target.closest( 'a[href]' ) : null;
		if ( ! enlace || e.defaultPrevented || 0 !== e.button || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey ||
			( enlace.target && '_self' !== enlace.target ) || enlace.hasAttribute( 'download' ) ||
			enlace.origin !== window.location.origin || ! sucios().length ) {
			return;
		}
		// Un ancla de la misma página no se lleva lo escrito.
		if ( enlace.hash && enlace.pathname === window.location.pathname && enlace.search === window.location.search ) {
			return;
		}
		e.preventDefault();
		var salir = function () {
			Array.prototype.forEach.call( sucios(), function ( form ) {
				form.removeAttribute( 'data-prc-sucio' );
			} );
			// El mismo enlace, ya limpio: el navegador lo sigue como siempre.
			enlace.click();
		};
		// Dentro de un <dialog> modal —el del bloqueo de edición— SweetAlert2
		// quedaría debajo, inerte; confirm() sale por encima.
		if ( ! window.Swal || enlace.closest( 'dialog[open]' ) ) {
			if ( window.confirm( SIN_GUARDAR ) ) {
				salir();
			}
			return;
		}
		window.Swal.fire( dialogo( SIN_GUARDAR, 'Salir sin guardar' ) ).then( function ( respuesta ) {
			if ( respuesta.isConfirmed ) {
				salir();
			}
		} );
	} );

	/* --- 11. El cartel de «Datos»: vista previa, aspa y «Deshacer» ------- */

	/*
	 * `data-prc-cartel` en el campo. Sin guion ya funciona: la zona es una
	 * etiqueta de la entrada de fichero y «Quitar el cartel» es una casilla.
	 * Con guion, la entrada cubre la zona entera —pulsar o soltar en cualquier
	 * punto—, la imagen elegida se ve al momento, el aspa marca esa misma
	 * casilla (que se esconde) y «Deshacer» la desmarca. El servidor sigue
	 * decidiendo: tipo, peso y qué se guarda.
	 *
	 * El estado va en `data-prc-cartel-estado` y lo pinta la hoja:
	 *   vacia    — sin cartel: la zona de soltar.
	 *   guardada — el cartel guardado, con el aspa y «Reemplazar».
	 *   nueva    — una imagen elegida, aún sin guardar.
	 *   quitada  — el guardado, marcado para quitar: la zona y «Deshacer».
	 */
	var POSTER_STATE = 'data-prc-cartel-estado';

	function posterParts( root ) {
		return {
			root: root,
			frame: root.querySelector( '.prc-cartel__marco' ),
			img: root.querySelector( '.prc-cartel__img' ),
			input: root.querySelector( '.prc-cartel__fichero' ),
			remove: root.querySelector( '.prc-cartel__quitar' ),
			undo: root.querySelector( '.prc-cartel__deshacer' ),
			details: root.querySelector( '.prc-cartel__datos' ),
			error: root.querySelector( '.prc-cartel__error' ),
			checkbox: root.querySelector( '.prc-cartel__casilla input[type="checkbox"]' )
		};
	}

	function posterState( p, state ) {
		p.root.setAttribute( POSTER_STATE, state );
		// El aspa dice lo que hace: sobre una imagen recién elegida, la descarta.
		var label = 'nueva' === state ? 'Descartar la imagen elegida' : 'Quitar el cartel';
		p.remove.setAttribute( 'aria-label', label );
		p.remove.setAttribute( 'title', label );
		p.input.setAttribute( 'title', 'guardada' === state || 'nueva' === state ? 'Reemplazar la imagen' : 'Elija una imagen o arrástrela aquí' );
	}

	function posterError( p, text ) {
		p.error.textContent = text;
		p.root.classList.toggle( 'prc-cartel--error', '' !== text );
	}

	// La vista previa anterior, si era de un fichero elegido, ya no se usa.
	function releasePreview( p ) {
		var src = p.img.getAttribute( 'src' ) || '';
		if ( 0 === src.indexOf( 'blob:' ) && window.URL && URL.revokeObjectURL ) {
			URL.revokeObjectURL( src );
		}
	}

	// Vuelve a lo guardado: el cartel que había, o la zona vacía.
	function showSavedPoster( p ) {
		var original = p.root.getAttribute( 'data-prc-cartel-original' ) || '';
		releasePreview( p );
		if ( original ) {
			p.img.setAttribute( 'src', original );
		} else {
			p.img.removeAttribute( 'src' );
		}
		p.details.textContent = p.details.getAttribute( 'data-prc-cartel-guardado' ) || '';
		posterState( p, original ? 'guardada' : 'vacia' );
	}

	/*
	 * Si el formulario vuelve a estar como se pintó —sin fichero, casillas y
	 * textos como venían—, deja de estar «sucio» (sección 10): descartar una
	 * elección o deshacer no es un cambio sin guardar.
	 */
	function forgetIfPristine( form ) {
		if ( ! form ) {
			return;
		}
		var changed = Array.prototype.some.call( form.elements, function ( el ) {
			if ( 'file' === el.type ) {
				return !! ( el.files && el.files.length );
			}
			if ( 'checkbox' === el.type || 'radio' === el.type ) {
				return el.checked !== el.defaultChecked;
			}
			return 'value' in el && 'defaultValue' in el && el.value !== el.defaultValue;
		} );
		if ( ! changed ) {
			form.removeAttribute( 'data-prc-sucio' );
		}
	}

	function setRemove( p, remove ) {
		if ( p.checkbox && p.checkbox.checked !== remove ) {
			p.checkbox.checked = remove;
			// Para el aviso de cambios sin guardar (sección 10).
			p.checkbox.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		}
	}

	// Lo que el servidor rechazaría, dicho antes de enviar y con sus palabras.
	function refusal( p, file ) {
		var types = ( p.root.getAttribute( 'data-prc-cartel-tipos' ) || '' ).split( ',' );
		var max = parseInt( p.root.getAttribute( 'data-prc-cartel-max' ), 10 ) || 0;
		// Sin `type` (algunos sistemas no lo dan), decide el servidor.
		if ( file.type && -1 === types.indexOf( file.type ) ) {
			return p.root.getAttribute( 'data-prc-cartel-error-tipo' ) || '';
		}
		if ( max > 0 && file.size > max ) {
			return p.root.getAttribute( 'data-prc-cartel-error-peso' ) || '';
		}
		return '';
	}

	// Rechazar lo elegido: el motivo, la entrada vacía y vuelta a lo de antes.
	function refuseChoice( p, reason ) {
		posterError( p, reason );
		p.input.value = '';
		if ( 'quitada' !== p.root.getAttribute( POSTER_STATE ) ) {
			showSavedPoster( p );
		}
		forgetIfPristine( p.root.closest( 'form' ) );
	}

	function choosePoster( p ) {
		var file = p.input.files && p.input.files[ 0 ];
		var reason = file ? refusal( p, file ) : '';
		if ( ! file || reason ) {
			refuseChoice( p, reason );
			return;
		}
		posterError( p, '' );
		// Elegir otra imagen ya sustituye: quitar a la vez lo rechazaría el servidor.
		setRemove( p, false );
		releasePreview( p );
		posterState( p, 'nueva' );
		p.details.textContent = file.name;
		if ( window.URL && URL.createObjectURL ) {
			p.img.setAttribute( 'src', URL.createObjectURL( file ) );
		}
	}

	function setUpPoster( root ) {
		var p = posterParts( root );
		root.setAttribute( 'data-prc-cartel-original', p.img.getAttribute( 'src' ) || '' );
		root.classList.add( 'prc-cartel--js' );
		p.img.addEventListener( 'load', function () {
			var file = p.input.files && p.input.files[ 0 ];
			// Las medidas de la imagen elegida, cuando el navegador la ha leído.
			if ( 'nueva' === root.getAttribute( POSTER_STATE ) && file && p.img.naturalWidth ) {
				p.details.textContent = file.name + ' · ' + p.img.naturalWidth + ' × ' + p.img.naturalHeight + ' px';
			}
		} );
		p.img.addEventListener( 'error', function () {
			// Pasa por imagen pero el navegador no la entiende: tampoco el servidor.
			if ( 'nueva' === root.getAttribute( POSTER_STATE ) ) {
				refuseChoice( p, root.getAttribute( 'data-prc-cartel-error-tipo' ) || '' );
			}
		} );
		// Lo que el navegador devuelve al recargar o al volver atrás: la casilla
		// marcada o un fichero elegido. Se pinta lo que se enviaría.
		if ( p.input.files && p.input.files.length ) {
			choosePoster( p );
		} else if ( p.checkbox && p.checkbox.checked ) {
			posterState( p, 'quitada' );
		}
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		Array.prototype.forEach.call( document.querySelectorAll( '[data-prc-cartel]' ), setUpPoster );
	} );

	document.addEventListener( 'change', function ( e ) {
		var root = e.target.matches && e.target.matches( '.prc-cartel__fichero' ) ? e.target.closest( '[data-prc-cartel]' ) : null;
		if ( root ) {
			choosePoster( posterParts( root ) );
		}
	} );

	document.addEventListener( 'click', function ( e ) {
		var button = e.target.closest ? e.target.closest( '.prc-cartel__quitar, .prc-cartel__deshacer' ) : null;
		var root = button && button.closest( '[data-prc-cartel]' );
		if ( ! root ) {
			return;
		}
		var p = posterParts( root );
		var form = root.closest( 'form' );
		posterError( p, '' );
		if ( button === p.undo ) {
			setRemove( p, false );
			showSavedPoster( p );
			forgetIfPristine( form );
			p.remove.focus();
			return;
		}
		// El aspa sobre una imagen recién elegida solo descarta la elección.
		if ( 'nueva' === root.getAttribute( POSTER_STATE ) ) {
			p.input.value = '';
			showSavedPoster( p );
			forgetIfPristine( form );
			( 'guardada' === root.getAttribute( POSTER_STATE ) ? p.remove : p.input ).focus();
			return;
		}
		setRemove( p, true );
		posterState( p, 'quitada' );
		p.undo.focus();
	} );

	/*
	 * Arrastrar: la zona se resalta y la entrada recibe el fichero. Soltarlo
	 * en cualquier otro punto de la página —el aspa, el margen— abriría la
	 * imagen en la pestaña y se perdería lo escrito: ahí no se suelta.
	 */
	function carriesFiles( e ) {
		var types = e.dataTransfer && e.dataTransfer.types;
		return !! types && -1 !== Array.prototype.indexOf.call( types, 'Files' );
	}

	function highlight( e, on ) {
		var frame = e.target.closest ? e.target.closest( '[data-prc-cartel] .prc-cartel__marco' ) : null;
		if ( frame && ( on || ! frame.contains( e.relatedTarget ) ) ) {
			frame.classList.toggle( 'prc-cartel__marco--encima', on );
		}
	}

	function keepFilesOut( e ) {
		var target = e.target;
		if ( ! carriesFiles( e ) || ( target.matches && target.matches( 'input[type="file"]' ) ) ) {
			return;
		}
		e.preventDefault();
		if ( e.dataTransfer ) {
			e.dataTransfer.dropEffect = 'none';
		}
	}

	document.addEventListener( 'dragenter', function ( e ) {
		highlight( e, true );
	} );
	document.addEventListener( 'dragover', function ( e ) {
		highlight( e, true );
		keepFilesOut( e );
	} );
	document.addEventListener( 'dragleave', function ( e ) {
		highlight( e, false );
	} );
	document.addEventListener( 'drop', function ( e ) {
		highlight( e, false );
		keepFilesOut( e );
	} );
	/*
	 * El índice de secciones de «Datos» y «Avanzado» (#129): sin guion son
	 * enlaces normales; con él, se marca la última sección que ha entrado en
	 * la franja de arriba de la ventana, que es la que se está leyendo.
	 */
	document.addEventListener( 'DOMContentLoaded', function () {
		var indice = document.querySelector( '[data-prc-indice]' );
		if ( ! indice || ! ( 'IntersectionObserver' in window ) ) {
			return;
		}
		var enlaces = Array.prototype.slice.call( indice.querySelectorAll( 'a[href^="#"]' ) );
		var vistas = {};
		// Al pie de la página, la última sección no llega nunca a la franja
		// de arriba: ahí se marca ella.
		function alFondo() {
			return window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2;
		}
		function marcar() {
			var actual = alFondo() ? enlaces[ enlaces.length - 1 ] : enlaces.filter( function ( a ) {
				return vistas[ a.hash.slice( 1 ) ];
			} ).pop();
			if ( ! actual ) {
				return;
			}
			enlaces.forEach( function ( a ) {
				if ( a === actual ) {
					a.setAttribute( 'aria-current', 'true' );
				} else {
					a.removeAttribute( 'aria-current' );
				}
			} );
		}
		var observador = new window.IntersectionObserver( function ( entradas ) {
			entradas.forEach( function ( entrada ) {
				vistas[ entrada.target.id ] = entrada.isIntersecting;
			} );
			marcar();
		}, { rootMargin: '0px 0px -60% 0px' } );
		window.addEventListener( 'scroll', marcar, { passive: true } );
		enlaces.forEach( function ( a ) {
			var destino = document.getElementById( a.hash.slice( 1 ) );
			if ( destino ) {
				observador.observe( destino );
			}
		} );
	} );
}() );
