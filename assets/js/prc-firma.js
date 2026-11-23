/*
 * La firma electrónica del justificante (ADR-0049).
 *
 * El aplicativo no firma: firma quien presenta, con su certificado. Esto solo
 * genera el PDF del justificante con jsPDF, a partir de los datos que pinta el
 * servidor en `data-prc-firma`, y o bien lo descarga para firmarlo fuera, o
 * bien se lo pasa a AutoFirma con AutoScript y manda lo firmado por el mismo
 * formulario de la subida a mano.
 *
 * Sin jsPDF no hay PDF y los botones se quedan escondidos; sin AutoScript, o
 * en un móvil sin servidor intermedio, se queda solo la descarga. La subida a
 * mano es un formulario normal y no depende de esto.
 */
( function () {
	'use strict';

	/*
	 * El PDF tiene que salir igual cada vez: la fecha de creación y el
	 * identificador del fichero los da el servidor, y nada depende de la hora
	 * ni de la zona de quien lo genera. El texto va sin comprimir: el servidor
	 * busca en él el número y la huella.
	 */
	function generar( r ) {
		var doc = new window.jspdf.jsPDF( { unit: 'pt', format: 'a4', compress: false } );
		doc.setCreationDate( r.created );
		doc.setFileId( r.file_id );
		doc.setDocumentProperties( { title: 'Justificante de presentación', subject: 'Solicitud ' + r.number } );

		doc.setFont( 'helvetica', 'bold' );
		doc.setFontSize( 16 );
		doc.text( 'Justificante de presentación', 56, 72 );

		// Lo que comprueba el servidor, en una línea cada cosa y en ASCII,
		// arriba: una coordinación larga no lo saca de la página.
		doc.setFont( 'courier', 'normal' );
		doc.setFontSize( 8 );
		doc.text( 'Solicitud: ' + r.number, 56, 92 );
		doc.text( 'Huella SHA-256: ' + r.fingerprint, 56, 104 );

		var y = 136;
		// En el orden de la huella; la coordinación, con su DNI o NIE, solo
		// si la hay.
		var filas = [
			[ 'N.º de solicitud', r.number ],
			[ 'Procedimiento', r.procedure ],
			[ 'Centro', r.centre ],
			[ 'Fecha y hora de presentación', r.submitted ]
		];
		if ( r.coordination ) {
			filas.push( [ 'Coordinación', r.coordination ] );
		}
		filas.forEach( function ( fila ) {
			if ( y > 720 ) {
				doc.addPage();
				y = 72;
			}
			doc.setFont( 'helvetica', 'bold' );
			doc.setFontSize( 10 );
			doc.text( fila[ 0 ], 56, y );
			doc.setFont( 'helvetica', 'normal' );
			doc.splitTextToSize( String( fila[ 1 ] || '' ), 330 ).forEach( function ( linea ) {
				// La firma visible ocupa abajo de 742 a 802 pt desde arriba:
				// el texto sigue en otra página antes de llegar a ella.
				if ( y > 720 ) {
					doc.addPage();
					y = 72;
				}
				doc.text( linea, 220, y );
				y += 16;
			} );
			y += 6;
		} );
		return doc;
	}

	/*
	 * La firma visible, abajo en la primera página, que el texto no pisa.
	 * `alwaysCreateRevision`: AutoFirma añade una revisión en vez de
	 * reescribir el fichero, y el texto queda como lo escribió jsPDF.
	 */
	var PARAMETROS = [
		'signaturePage=1',
		'signaturePositionOnPageLowerLeftX=56',
		'signaturePositionOnPageLowerLeftY=40',
		'signaturePositionOnPageUpperRightX=320',
		'signaturePositionOnPageUpperRightY=100',
		'layer2Text=Firmado por $$SUBJECTCN$$ el $$SIGNDATE=dd/MM/yyyy$$',
		'alwaysCreateRevision=true'
	].join( '\n' );

	function firmar( b64, servlets ) {
		return new Promise( function ( resolver, rechazar ) {
			var api = window.AutoScript;
			if ( servlets && servlets.storage && servlets.retrieve && api.setServlets ) {
				api.setServlets( servlets.storage, servlets.retrieve );
			}
			if ( api.cargarAppAfirma ) {
				api.cargarAppAfirma();
			}
			api.sign( b64, 'SHA256withRSA', 'PAdES', PARAMETROS, resolver, function ( tipo, mensaje ) {
				rechazar( new Error( mensaje || tipo || '' ) );
			} );
		} );
	}

	function preparar( seccion ) {
		var conf;
		try {
			conf = JSON.parse( seccion.getAttribute( 'data-prc-firma' ) );
		} catch ( e ) {
			return;
		}
		if ( ! conf || ! conf.receipt || ! window.jspdf || ! window.jspdf.jsPDF ) {
			return;
		}
		var r = conf.receipt;
		var servlets = conf.servlets || {};
		var botones = seccion.querySelector( '[data-prc-firma-botones]' );
		var pdf = seccion.querySelector( '[data-prc-firma-pdf]' );
		var boton = seccion.querySelector( '[data-prc-firmar]' );
		var estado = seccion.querySelector( '[data-prc-firma-estado]' );
		var form = seccion.querySelector( '[data-prc-firma-form]' );
		var resultado = seccion.querySelector( '[data-prc-firma-resultado]' );

		botones.hidden = false;
		pdf.addEventListener( 'click', function () {
			generar( r ).save( r.filename );
		} );

		// En el móvil no hay WebSocket con AutoFirma: sin servidor intermedio
		// el botón solo llevaría a un error.
		var conServlets = servlets.storage && servlets.retrieve;
		var api = window.AutoScript;
		if ( ! api || 'function' !== typeof api.sign || ( ! conServlets && /Android|iPhone|iPad/i.test( navigator.userAgent ) ) ) {
			return;
		}
		boton.hidden = false;
		boton.addEventListener( 'click', function () {
			boton.disabled = true;
			estado.textContent = 'AutoFirma se está abriendo. Elija su certificado en la ventana que aparece y no cierre esta página.';
			firmar( btoa( generar( r ).output() ), servlets )
				.then( function ( firmado ) {
					estado.textContent = 'Guardando el justificante firmado…';
					resultado.value = firmado;
					form.submit();
				} )
				.catch( function ( error ) {
					estado.textContent = 'No se pudo firmar' + ( error.message ? ': ' + error.message : '' ) + '. Compruebe que AutoFirma está instalada y vuelva a intentarlo, o descargue el justificante, fírmelo con otra aplicación y súbalo.';
					boton.disabled = false;
				} );
		} );
	}

	function iniciar() {
		Array.prototype.forEach.call( document.querySelectorAll( '[data-prc-firma]' ), preparar );
	}

	// Las librerías se encolan detrás de este guion: se espera a que estén.
	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', iniciar );
	} else {
		iniciar();
	}
}() );
