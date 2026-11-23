/*
 * Los tres escalones de la confirmación, en un navegador de verdad.
 *
 *   npm run test:browser
 *
 * La degradación es una promesa que solo se puede comprobar aquí: PHP ve el
 * atributo en el formulario, pero que Escape cierre el diálogo, que el foco no
 * se escape de él y que la acción se haga igual cuando la librería no llegó
 * son cosas del navegador. Así que:
 *
 *   1. con SweetAlert2 —el de `node_modules`, la misma versión que el CDN—;
 *   2. sin él, con el `confirm()` de siempre;
 *   3. sin JavaScript en absoluto.
 *
 * No toca wp-env ni la red: monta la página a mano —el mismo `prc-app.js`, la
 * misma hoja y el mismo HTML que pinta `ProcedureEditorView`— y sirve tanto la
 * pantalla como el destino del envío desde el propio Playwright.
 */
const RAIZ = require( 'path' ).resolve( __dirname, '../..' );
const { chromium } = require( 'playwright' );
const fs = require( 'fs' );

const APP = fs.readFileSync( RAIZ + '/assets/js/prc-app.js', 'utf8' );
const CSS = fs.readFileSync( RAIZ + '/assets/css/prc-app.css', 'utf8' );
const SWAL = fs.readFileSync( RAIZ + '/node_modules/sweetalert2/dist/sweetalert2.all.min.js', 'utf8' );

const FORM = `<!doctype html><meta charset="utf-8">
<body class="prc-app">
<form id="f" class="prc-acciones" method="post" action="/enviado"
      data-prc-confirm="¿Enviar «Red de ejemplo» a la papelera? Dejará de verse en Mis procedimientos.">
  <input type="hidden" name="prc_do" value="delete" />
  <button id="b" type="submit" class="prc-btn prc-mini prc-btn-borrar" title="Enviar a la papelera">
    <span aria-hidden="true">Borrar</span>
    <span class="screen-reader-text">Enviar a la papelera</span>
  </button>
</form>
<a id="fuera" href="#">un enlace fuera del diálogo</a>
</body>`;

let fallos = 0;
function ok( cond, texto ) {
	console.log( ( cond ? '  OK   ' : '  FALLA' ) + '  ' + texto );
	if ( ! cond ) { fallos++; }
}

function html( conSwal ) {
	return '<style>' + CSS + '</style>' + FORM +
		( conSwal ? '<script>' + SWAL + '</scr' + 'ipt>' : '' ) +
		'<script>' + APP + '</scr' + 'ipt>';
}

async function abrir( ctx, conSwal ) {
	const page = await ctx.newPage();
	await page.route( 'https://prc.test/**', ( route ) => {
		const url = route.request().url();
		route.fulfill( {
			status: 200,
			contentType: 'text/html; charset=utf-8',
			body: url.includes( '/enviado' ) ? '<!doctype html><h1>ENVIADO</h1>' : html( conSwal ),
		} );
	} );
	await page.goto( 'https://prc.test/panel' );
	return page;
}

( async () => {
	const browser = await chromium.launch();
	const ctx = await browser.newContext();

	// --- 1. Sin SweetAlert: el confirm() de siempre --------------------------
	{
		const page = await abrir( ctx, false );
		let preguntado = null;
		page.on( 'dialog', async ( d ) => { preguntado = d.message(); await d.dismiss(); } );
		await page.click( '#b' );
		await page.waitForTimeout( 300 );
		ok( null !== preguntado, 'sin SweetAlert se pregunta con confirm()' );
		ok( '¿Enviar «Red de ejemplo» a la papelera? Dejará de verse en Mis procedimientos.' === preguntado,
			'y con el texto del atributo: ' + JSON.stringify( preguntado ) );
		ok( ! page.url().includes( 'enviado' ), 'al cancelar no se envía' );

		page.removeAllListeners( 'dialog' );
		page.on( 'dialog', ( d ) => d.accept() );
		await Promise.all( [ page.waitForURL( '**/enviado' ), page.click( '#b' ) ] );
		ok( page.url().includes( 'enviado' ), 'al aceptar el confirm() sí se envía' );
		await page.close();
	}

	// --- 2. Sin JavaScript ---------------------------------------------------
	{
		const sinJs = await browser.newContext( { javaScriptEnabled: false } );
		const page = await abrir( sinJs, true );
		await Promise.all( [ page.waitForURL( '**/enviado' ), page.click( '#b' ) ] );
		ok( page.url().includes( 'enviado' ), 'sin JavaScript el botón envía y la acción se hace' );
		await sinJs.close();
	}

	// --- 3. Con SweetAlert ---------------------------------------------------
	{
		const page = await abrir( ctx, true );
		page.on( 'dialog', async ( d ) => { ok( false, 'no debería salir el confirm() del navegador' ); await d.dismiss(); } );

		await page.click( '#b' );
		await page.waitForSelector( '.swal2-popup', { state: 'visible' } );
		ok( true, 'sale el diálogo de SweetAlert2' );
		const titulo = ( await page.textContent( '.swal2-title' ) ).trim();
		ok( '¿Enviar «Red de ejemplo» a la papelera?' === titulo, 'titular: ' + titulo );
		const detalle = ( await page.textContent( '.swal2-html-container' ) ).trim();
		ok( 'Dejará de verse en Mis procedimientos.' === detalle, 'detalle: ' + detalle );
		const confirmar = ( await page.textContent( '.swal2-confirm' ) ).trim();
		ok( 'Enviar a la papelera' === confirmar, 'el botón dice el verbo: ' + confirmar );
		ok( 'Cancelar' === ( await page.textContent( '.swal2-cancel' ) ).trim(), 'y hay botón de cancelar' );
		ok( await page.evaluate( () => document.activeElement.classList.contains( 'swal2-cancel' ) ),
			'el foco arranca en Cancelar, no en la acción destructiva' );

		let dentro = true;
		for ( let i = 0; i < 12; i++ ) {
			await page.keyboard.press( 'Tab' );
			dentro = dentro && await page.evaluate( () => null !== document.activeElement.closest( '.swal2-container' ) );
		}
		ok( dentro, 'el foco queda atrapado dentro del diálogo tras 12 tabuladores' );

		await page.keyboard.press( 'Escape' );
		await page.waitForSelector( '.swal2-popup', { state: 'hidden' } );
		ok( ! page.url().includes( 'enviado' ), 'Escape cierra el diálogo y no envía' );

		await page.click( '#b' );
		await page.waitForSelector( '.swal2-popup', { state: 'visible' } );
		await page.click( '.swal2-cancel' );
		await page.waitForSelector( '.swal2-popup', { state: 'hidden' } );
		ok( ! page.url().includes( 'enviado' ), 'Cancelar cierra el diálogo y no envía' );

		await page.click( '#b' );
		await page.waitForSelector( '.swal2-popup', { state: 'visible' } );
		await Promise.all( [ page.waitForURL( '**/enviado' ), page.click( '.swal2-confirm' ) ] );
		ok( page.url().includes( 'enviado' ), 'confirmar envía el formulario' );
		await page.close();
	}

	// --- 4. La revisión en bloque: casillas fuera del formulario ------------
	//
	// Las casillas de las filas están en la tabla y apuntan al formulario con
	// el atributo `form`; «Marcar las de esta página» es un campo que lee el
	// servidor. Se comprueba qué viaja en el envío en los tres escalones.
	{
		const BLOQUE = `<!doctype html><meta charset="utf-8">
<body class="prc-app">
<form id="prc-bloque" class="prc-bloque__form" method="post" action="/enviado"
      data-prc-confirm="¿Cambiar la revisión de las solicitudes marcadas? Cada una recibe el mismo estado y la misma nota, y a cada centro le llega su aviso por correo."
      data-prc-confirm-ok="Cambiar la revisión">
  <input type="hidden" name="prc_do" value="bulk_review" />
  <input type="hidden" name="prc_page_rows" value="11,12" />
  <input type="hidden" name="revision" value="submitted" />
  <label><input type="checkbox" id="pagina" name="prc_rows_page" value="1" data-prc-bulk-page /> Marcar las de esta página (2)</label>
  <select name="prc_state"><option value="admitted">Admitida</option></select>
  <button id="enviar" type="submit">Cambiar la revisión de las marcadas</button>
</form>
<table><tbody>
  <tr><td><input type="checkbox" id="f11" form="prc-bloque" name="prc_rows[]" value="11" data-prc-bulk-row /></td></tr>
  <tr><td><input type="checkbox" id="f12" form="prc-bloque" name="prc_rows[]" value="12" data-prc-bulk-row /></td></tr>
</tbody></table>
</body>`;
		const abrirBloque = async ( contexto, conSwal ) => {
			const page = await contexto.newPage();
			const envio = { cuerpo: null };
			await page.route( 'https://prc.test/**', ( route ) => {
				const url = route.request().url();
				if ( url.includes( '/enviado' ) ) {
					envio.cuerpo = decodeURIComponent( ( route.request().postData() || '' ).replace( /\+/g, ' ' ) );
				}
				route.fulfill( {
					status: 200,
					contentType: 'text/html; charset=utf-8',
					body: url.includes( '/enviado' ) ? '<!doctype html><h1>ENVIADO</h1>' :
						'<style>' + CSS + '</style>' + BLOQUE + ( conSwal ? '<script>' + SWAL + '</scr' + 'ipt>' : '' ) + '<script>' + APP + '</scr' + 'ipt>',
				} );
			} );
			await page.goto( 'https://prc.test/panel' );
			return { page, envio };
		};

		// Sin JavaScript: la casilla de la página viaja y el servidor revisa la página.
		const sinJs = await browser.newContext( { javaScriptEnabled: false } );
		let { page, envio } = await abrirBloque( sinJs, true );
		await page.check( '#pagina' );
		ok( ! ( await page.isChecked( '#f11' ) ), 'sin guion, marcar la página no toca las filas' );
		await Promise.all( [ page.waitForURL( '**/enviado' ), page.click( '#enviar' ) ] );
		ok( /prc_rows_page=1/.test( envio.cuerpo ) && /prc_page_rows=11,12/.test( envio.cuerpo ),
			'sin guion se envía la casilla de la página con sus solicitudes: ' + envio.cuerpo );
		ok( /revision=submitted/.test( envio.cuerpo ), 'y el filtro vuelve con el envío' );
		await sinJs.close();

		// Con el confirm() del navegador: el guion marca las filas.
		( { page, envio } = await abrirBloque( ctx, false ) );
		await page.check( '#pagina' );
		ok( await page.isChecked( '#f11' ) && await page.isChecked( '#f12' ), 'con guion, la casilla de la página marca las filas' );
		await page.uncheck( '#f12' );
		ok( ! ( await page.isChecked( '#pagina' ) ), 'y se desmarca sola en cuanto falta una' );
		let pregunta = null;
		page.on( 'dialog', async ( d ) => { pregunta = d.message(); await d.dismiss(); } );
		await page.click( '#enviar' );
		await page.waitForTimeout( 300 );
		ok( null !== pregunta && pregunta.startsWith( '¿Cambiar la revisión' ), 'sin SweetAlert se pregunta con confirm()' );
		ok( null === envio.cuerpo, 'al cancelar no se envía' );
		page.removeAllListeners( 'dialog' );
		page.on( 'dialog', ( d ) => d.accept() );
		await Promise.all( [ page.waitForURL( '**/enviado' ), page.click( '#enviar' ) ] );
		ok( /prc_rows\[\]=11/.test( envio.cuerpo ) && ! /prc_rows\[\]=12/.test( envio.cuerpo ) && ! /prc_rows_page/.test( envio.cuerpo ),
			'al aceptar viaja solo la fila marcada: ' + envio.cuerpo );
		await page.close();

		// Con SweetAlert2.
		( { page, envio } = await abrirBloque( ctx, true ) );
		page.on( 'dialog', async ( d ) => { ok( false, 'no debería salir el confirm() del navegador' ); await d.dismiss(); } );
		await page.check( '#f12' );
		await page.click( '#enviar' );
		await page.waitForSelector( '.swal2-popup', { state: 'visible' } );
		const verbo = ( await page.textContent( '.swal2-confirm' ) ).trim();
		ok( 'Cambiar la revisión' === verbo, 'el botón del diálogo dice el verbo: ' + verbo );
		await page.click( '.swal2-cancel' );
		await page.waitForSelector( '.swal2-popup', { state: 'hidden' } );
		ok( null === envio.cuerpo, 'Cancelar no envía' );
		await page.click( '#enviar' );
		await page.waitForSelector( '.swal2-popup', { state: 'visible' } );
		await Promise.all( [ page.waitForURL( '**/enviado' ), page.click( '.swal2-confirm' ) ] );
		ok( /prc_rows\[\]=12/.test( envio.cuerpo ), 'confirmar envía la fila marcada fuera del formulario: ' + envio.cuerpo );
		await page.close();
	}

	// --- 5. Duplicar, desde el menú «⋮» del taller ---------------------------
	//
	// El mismo HTML que pinta `ProcedureEditorView::menu()`: un `details` con
	// sus tres puntos, y dentro el formulario de duplicar con su confirmación.
	// Sin guion, el `summary` lo abre igual y el botón envía.
	{
		const DUPLICAR = `<!doctype html><meta charset="utf-8">
<body class="prc-app">
<details class="prc-mas" data-prc-menu>
  <summary id="mas" class="prc-btn prc-btn-sec btn btn-outline-secondary prc-mini prc-mas__boton" title="Más acciones para este procedimiento">
    <span aria-hidden="true">&#8942;</span><span class="screen-reader-text">Más acciones para este procedimiento</span>
  </summary>
  <div class="prc-mas__menu">
    <a class="prc-mas__opcion" href="/ficha">Vista previa</a>
    <form class="prc-mas__form" method="post" action="/enviado"
          data-prc-confirm="¿Duplicar «Red de ejemplo»? Se creará un borrador nuevo con sus datos, sus campos adicionales y su configuración, sin plazos, sin curso y sin solicitudes."
          data-prc-confirm-ok="Duplicar" data-prc-once>
      <input type="hidden" name="prc_nonce_duplicate_0" value="abc123" />
      <input type="hidden" name="prc_do" value="duplicate" />
      <input type="hidden" name="prc_procedure" value="42" />
      <button id="dup" type="submit" class="prc-mas__opcion">Duplicar</button>
    </form>
    <a class="prc-mas__opcion prc-mas__opcion--borrar" href="/borrar">Borrar</a>
  </div>
</details>
</body>`;
		const abrirDuplicar = async ( contexto, conSwal ) => {
			const page = await contexto.newPage();
			const envio = { cuerpo: null };
			await page.route( 'https://prc.test/**', ( route ) => {
				const url = route.request().url();
				if ( url.includes( '/enviado' ) ) {
					envio.cuerpo = decodeURIComponent( ( route.request().postData() || '' ).replace( /\+/g, ' ' ) );
				}
				route.fulfill( {
					status: 200,
					contentType: 'text/html; charset=utf-8',
					body: url.includes( '/enviado' ) ? '<!doctype html><h1>ENVIADO</h1>' :
						'<style>' + CSS + '</style>' + DUPLICAR + ( conSwal ? '<script>' + SWAL + '</scr' + 'ipt>' : '' ) + '<script>' + APP + '</scr' + 'ipt>',
				} );
			} );
			await page.goto( 'https://prc.test/taller' );
			return { page, envio };
		};
		// El botón está dentro del menú: primero se abre.
		const pulsar = async ( page ) => {
			if ( ! ( await page.evaluate( () => document.querySelector( 'details.prc-mas' ).open ) ) ) {
				await page.click( '#mas' );
			}
			await page.click( '#dup' );
		};
		const lleva = ( cuerpo ) => /prc_do=duplicate/.test( cuerpo ) && /prc_procedure=42/.test( cuerpo ) && /prc_nonce_duplicate_0=abc123/.test( cuerpo );

		// Sin JavaScript: el botón envía y la acción se hace.
		const sinJs = await browser.newContext( { javaScriptEnabled: false } );
		let { page, envio } = await abrirDuplicar( sinJs, true );
		await Promise.all( [ page.waitForURL( '**/enviado' ), pulsar( page ) ] );
		ok( lleva( envio.cuerpo ), 'duplicar sin JavaScript envía la operación, el procedimiento y el nonce: ' + envio.cuerpo );
		await sinJs.close();

		// Con el confirm() del navegador.
		( { page, envio } = await abrirDuplicar( ctx, false ) );
		let pregunta = null;
		page.on( 'dialog', async ( d ) => { pregunta = d.message(); await d.dismiss(); } );
		await pulsar( page );
		await page.waitForTimeout( 300 );
		ok( null !== pregunta && pregunta.startsWith( '¿Duplicar «Red de ejemplo»?' ), 'duplicar sin SweetAlert pregunta con confirm(): ' + JSON.stringify( pregunta ) );
		ok( null === envio.cuerpo, 'al cancelar no se duplica' );
		page.removeAllListeners( 'dialog' );
		page.on( 'dialog', ( d ) => d.accept() );
		await Promise.all( [ page.waitForURL( '**/enviado' ), pulsar( page ) ] );
		ok( lleva( envio.cuerpo ), 'al aceptar el confirm() se duplica' );
		await page.close();


		// Con SweetAlert2.
		( { page, envio } = await abrirDuplicar( ctx, true ) );
		page.on( 'dialog', async ( d ) => { ok( false, 'no debería salir el confirm() del navegador' ); await d.dismiss(); } );
		await pulsar( page );
		await page.waitForSelector( '.swal2-popup', { state: 'visible' } );
		const titular = ( await page.textContent( '.swal2-title' ) ).trim();
		ok( '¿Duplicar «Red de ejemplo»?' === titular, 'titular: ' + titular );
		const verbo = ( await page.textContent( '.swal2-confirm' ) ).trim();
		ok( 'Duplicar' === verbo, 'el botón del diálogo dice el verbo: ' + verbo );
		await page.keyboard.press( 'Escape' );
		await page.waitForSelector( '.swal2-popup', { state: 'hidden' } );
		ok( null === envio.cuerpo, 'Escape no duplica' );
		await pulsar( page );
		await page.waitForSelector( '.swal2-popup', { state: 'visible' } );
		await Promise.all( [ page.waitForURL( '**/enviado' ), page.click( '.swal2-confirm' ) ] );
		ok( lleva( envio.cuerpo ), 'confirmar duplica' );
		await page.close();
	}

	// --- 6. Retirar una solicitud: el centro, y quien revisa con su motivo ---
	//
	// El mismo HTML que pintan `ApplicationActionsView::withdraw()` y
	// `retire_form()`: el centro retira sin más; quien revisa escribe el
	// motivo, que es obligatorio y tiene que viajar con el envío.
	{
		const RETIRAR = `<!doctype html><meta charset="utf-8">
<body class="prc-app">
<form id="centro" class="prc-form prc-acciones" method="post" action="/enviado"
      data-prc-confirm="¿Retirar su solicitud? Dejará de contar para este procedimiento. Mientras el plazo siga abierto podrá presentar otra, pero tendrá que rellenarla de nuevo."
      data-prc-confirm-ok="Retirar la solicitud" data-prc-once>
  <input type="hidden" name="_prc_withdraw_nonce" value="abc123" />
  <input type="hidden" name="prc_withdraw_op" value="withdraw" />
  <input type="hidden" name="prc_withdraw_application" value="77" />
  <button id="retirar" type="submit">Retirar la solicitud</button>
</form>
<form id="revision" class="prc-form" method="post" action="/enviado"
      data-prc-confirm="¿Retirar la solicitud de Centro de ejemplo? Pasará a «Retiradas»: dejará de contar, de salir en la consulta y en las exportaciones. Se puede restaurar mientras el centro no presente otra."
      data-prc-confirm-ok="Retirar la solicitud" data-prc-once>
  <input type="hidden" name="prc_do" value="retire_application" />
  <input type="hidden" name="prc_row" value="78" />
  <textarea id="motivo" name="prc_retire_reason" required></textarea>
  <button id="quitar" type="submit">Retirar la solicitud</button>
</form>
</body>`;
		const abrirRetirar = async ( contexto, conSwal ) => {
			const page = await contexto.newPage();
			const envio = { cuerpo: null };
			await page.route( 'https://prc.test/**', ( route ) => {
				const url = route.request().url();
				if ( url.includes( '/enviado' ) ) {
					envio.cuerpo = decodeURIComponent( ( route.request().postData() || '' ).replace( /\+/g, ' ' ) );
				}
				route.fulfill( {
					status: 200,
					contentType: 'text/html; charset=utf-8',
					body: url.includes( '/enviado' ) ? '<!doctype html><h1>ENVIADO</h1>' :
						'<style>' + CSS + '</style>' + RETIRAR + ( conSwal ? '<script>' + SWAL + '</scr' + 'ipt>' : '' ) + '<script>' + APP + '</scr' + 'ipt>',
				} );
			} );
			await page.goto( 'https://prc.test/solicitud' );
			return { page, envio };
		};
		const delCentro = ( cuerpo ) => /prc_withdraw_op=withdraw/.test( cuerpo ) && /prc_withdraw_application=77/.test( cuerpo ) && /_prc_withdraw_nonce=abc123/.test( cuerpo );
		const conMotivo = ( cuerpo ) => /prc_do=retire_application/.test( cuerpo ) && /prc_row=78/.test( cuerpo ) && /prc_retire_reason=Por error/.test( cuerpo );

		// Sin JavaScript: el botón envía, y sin motivo el navegador no deja.
		const sinJs = await browser.newContext( { javaScriptEnabled: false } );
		let { page, envio } = await abrirRetirar( sinJs, true );
		await Promise.all( [ page.waitForURL( '**/enviado' ), page.click( '#retirar' ) ] );
		ok( delCentro( envio.cuerpo ), 'retirar sin JavaScript envía la solicitud y el nonce: ' + envio.cuerpo );
		await page.close();
		( { page, envio } = await abrirRetirar( sinJs, true ) );
		await page.click( '#quitar' );
		await page.waitForTimeout( 300 );
		ok( null === envio.cuerpo, 'sin motivo, quien revisa no retira' );
		await page.fill( '#motivo', 'Por error' );
		await Promise.all( [ page.waitForURL( '**/enviado' ), page.click( '#quitar' ) ] );
		ok( conMotivo( envio.cuerpo ), 'con motivo, el motivo viaja: ' + envio.cuerpo );
		await sinJs.close();

		// Con el confirm() del navegador.
		( { page, envio } = await abrirRetirar( ctx, false ) );
		let pregunta = null;
		page.on( 'dialog', async ( d ) => { pregunta = d.message(); await d.dismiss(); } );
		await page.click( '#retirar' );
		await page.waitForTimeout( 300 );
		ok( null !== pregunta && pregunta.startsWith( '¿Retirar su solicitud?' ), 'retirar sin SweetAlert pregunta con confirm(): ' + JSON.stringify( pregunta ) );
		ok( null === envio.cuerpo, 'al cancelar no se retira' );
		page.removeAllListeners( 'dialog' );
		page.on( 'dialog', ( d ) => d.accept() );
		await Promise.all( [ page.waitForURL( '**/enviado' ), page.click( '#retirar' ) ] );
		ok( delCentro( envio.cuerpo ), 'al aceptar el confirm() se retira' );
		await page.close();

		// Con SweetAlert2: el centro, y quien revisa con su motivo.
		( { page, envio } = await abrirRetirar( ctx, true ) );
		page.on( 'dialog', async ( d ) => { ok( false, 'no debería salir el confirm() del navegador' ); await d.dismiss(); } );
		await page.click( '#retirar' );
		await page.waitForSelector( '.swal2-popup', { state: 'visible' } );
		ok( '¿Retirar su solicitud?' === ( await page.textContent( '.swal2-title' ) ).trim(), 'titular del centro' );
		ok( 'Retirar la solicitud' === ( await page.textContent( '.swal2-confirm' ) ).trim(), 'el botón dice el verbo' );
		await page.keyboard.press( 'Escape' );
		await page.waitForSelector( '.swal2-popup', { state: 'hidden' } );
		ok( null === envio.cuerpo, 'Escape no retira' );
		await page.fill( '#motivo', 'Por error' );
		await page.click( '#quitar' );
		await page.waitForSelector( '.swal2-popup', { state: 'visible' } );
		ok( ( await page.textContent( '.swal2-title' ) ).trim().startsWith( '¿Retirar la solicitud de Centro de ejemplo?' ), 'titular de quien revisa' );
		await Promise.all( [ page.waitForURL( '**/enviado' ), page.click( '.swal2-confirm' ) ] );
		ok( conMotivo( envio.cuerpo ), 'confirmar retira con el motivo: ' + envio.cuerpo );
		await page.close();
	}

	// --- 7. El menú «⋮»: cerrarlo como un menú, en ordenador y en el móvil ---
	//
	// La cabecera del taller tal como la pinta `ProcedureEditorView::head()`,
	// con un título largo para que en el móvil la fila se parta. Sin guion el
	// `details` se abre y se cierra con su botón; con guion, además, Escape lo
	// cierra devolviendo el foco y pulsar fuera también.
	{
		const CABECERA = `<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<body class="prc-app" style="margin:0;padding:0 16px">
<div class="prc-h1-fila">
  <h1 class="prc-h1">Jornadas de Ciencia en Familia para todos los centros</h1>
  <span class="prc-state">Borrador</span>
  <details class="prc-mas" data-prc-menu>
    <summary id="mas" class="prc-btn prc-btn-sec btn btn-outline-secondary prc-mini prc-mas__boton" title="Más acciones para este procedimiento">
      <span aria-hidden="true">&#8942;</span><span class="screen-reader-text">Más acciones para este procedimiento</span>
    </summary>
    <div class="prc-mas__menu" id="capa">
      <a class="prc-mas__opcion" href="/ficha">Vista previa</a>
      <form class="prc-mas__form" method="post" action="/enviado"><button type="submit" class="prc-mas__opcion">Duplicar</button></form>
      <a class="prc-mas__opcion prc-mas__opcion--borrar" href="/borrar">Borrar</a>
    </div>
  </details>
</div>
<p id="fuera">Contenido del taller.</p>
</body>`;
		const abrirCabecera = async ( contexto ) => {
			const page = await contexto.newPage();
			await page.route( 'https://prc.test/**', ( route ) => route.fulfill( {
				status: 200,
				contentType: 'text/html; charset=utf-8',
				body: '<style>' + CSS + '</style>' + CABECERA + '<script>' + APP + '</scr' + 'ipt>',
			} ) );
			await page.goto( 'https://prc.test/taller' );
			return page;
		};
		const abierto = ( page ) => page.evaluate( () => document.querySelector( 'details.prc-mas' ).open );

		// Sin JavaScript: el botón abre y cierra, y las opciones se pulsan.
		const sinJs = await browser.newContext( { javaScriptEnabled: false } );
		let page = await abrirCabecera( sinJs );
		await page.click( '#mas' );
		ok( await abierto( page ), 'sin guion, los tres puntos abren el menú' );
		ok( await page.isVisible( 'text=Borrar' ), 'y se ven sus opciones' );
		await page.click( '#mas' );
		ok( ! ( await abierto( page ) ), 'y lo vuelven a cerrar' );
		await sinJs.close();

		// Con guion: Escape devuelve el foco, pulsar fuera cierra.
		page = await abrirCabecera( ctx );
		await page.click( '#mas' );
		ok( await abierto( page ), 'con guion, el menú se abre' );
		await page.focus( '.prc-mas__opcion--borrar' );
		await page.keyboard.press( 'Escape' );
		ok( ! ( await abierto( page ) ), 'Escape lo cierra' );
		ok( await page.evaluate( () => 'mas' === document.activeElement.id ), 'y el foco vuelve a los tres puntos' );
		await page.click( '#mas' );
		await page.click( '#fuera' );
		ok( ! ( await abierto( page ) ), 'pulsar fuera lo cierra' );
		await page.click( '#mas' );
		await page.click( '#capa', { position: { x: 2, y: 2 } } );
		ok( await abierto( page ), 'pulsar dentro no lo cierra' );
		const alto = await page.evaluate( () => Math.min( ...Array.from(
			document.querySelectorAll( '#mas, .prc-mas__opcion' ),
			( el ) => el.getBoundingClientRect().height
		) ) );
		ok( alto >= 44, 'cada objetivo mide al menos 44 px de alto: ' + alto );
		await page.close();

		// En el móvil, la capa no se sale de la pantalla.
		const movil = await browser.newContext( { viewport: { width: 360, height: 740 } } );
		page = await abrirCabecera( movil );
		await page.click( '#mas' );
		const caja = await page.evaluate( () => {
			const r = document.getElementById( 'capa' ).getBoundingClientRect();
			return { izquierda: r.left, derecha: r.right, ancho: document.documentElement.clientWidth, scroll: document.documentElement.scrollWidth };
		} );
		ok( caja.izquierda >= 0 && caja.derecha <= caja.ancho, 'en el móvil la capa cabe en la pantalla: ' + JSON.stringify( caja ) );
		ok( caja.scroll <= caja.ancho, 'y no hay desplazamiento horizontal' );
		await movil.close();
	}

	// --- 8. Borrar: escribir BORRAR, también sin JavaScript -------------------
	//
	// La confirmación de `ProcedureEditorView::delete_confirmation()`: un campo
	// obligatorio que el servidor vuelve a comprobar. Aquí se mira que sin
	// escribir nada el navegador no envía y que lo escrito viaja.
	{
		const BORRAR = `<!doctype html><meta charset="utf-8">
<body class="prc-app">
<form class="prc-form" method="post" action="/enviado" data-prc-once>
  <input type="hidden" name="prc_nonce_delete_42" value="abc123" />
  <input type="hidden" name="prc_do" value="delete" />
  <input type="hidden" name="prc_procedure" value="42" />
  <label for="prc-confirm-word">Para confirmarlo, escriba <strong>BORRAR</strong></label>
  <input type="text" id="prc-confirm-word" name="prc_confirm_word" required autocomplete="off" />
  <button id="papelera" type="submit">Enviar a la papelera</button>
</form>
</body>`;
		const abrirBorrar = async ( contexto, conJs ) => {
			const pagina = await contexto.newPage();
			const envio = { cuerpo: null };
			await pagina.route( 'https://prc.test/**', ( route ) => {
				const url = route.request().url();
				if ( url.includes( '/enviado' ) ) {
					envio.cuerpo = decodeURIComponent( ( route.request().postData() || '' ).replace( /\+/g, ' ' ) );
				}
				route.fulfill( {
					status: 200,
					contentType: 'text/html; charset=utf-8',
					body: url.includes( '/enviado' ) ? '<!doctype html><h1>ENVIADO</h1>' :
						'<style>' + CSS + '</style>' + BORRAR + ( conJs ? '<script>' + SWAL + '</scr' + 'ipt><script>' + APP + '</scr' + 'ipt>' : '' ),
				} );
			} );
			await pagina.goto( 'https://prc.test/taller?borrar=1' );
			return { pagina, envio };
		};
		for ( const conJs of [ false, true ] ) {
			const contexto = await browser.newContext( { javaScriptEnabled: conJs } );
			const { pagina, envio } = await abrirBorrar( contexto, conJs );
			const como = conJs ? 'con guion' : 'sin guion';
			await pagina.click( '#papelera' );
			await pagina.waitForTimeout( 300 );
			ok( null === envio.cuerpo, como + ', con el campo vacío no se envía' );
			await pagina.fill( '#prc-confirm-word', ' borrar ' );
			await Promise.all( [ pagina.waitForURL( '**/enviado' ), pagina.click( '#papelera' ) ] );
			ok( /prc_do=delete/.test( envio.cuerpo ) && /prc_nonce_delete_42=abc123/.test( envio.cuerpo ) && /prc_confirm_word= borrar /.test( envio.cuerpo ),
				como + ', lo escrito viaja tal cual y lo compara el servidor: ' + envio.cuerpo );
			await contexto.close();
		}
	}

	// --- 9. Cambios sin guardar en el taller --------------------------------
	//
	// Un panel con su formulario marcado `data-prc-dirty-guard` y la barra de
	// pestañas. Escrito algo, la pestaña pregunta antes de irse —SweetAlert2 o
	// `confirm()`—, cerrar la página da el aviso del navegador y guardar no
	// pregunta nada. Sin guion, la pestaña se sigue sin más.
	{
		const SUCIO = `<!doctype html><meta charset="utf-8">
<body class="prc-app">
<nav class="prc-tabs"><a id="pestana" href="/otra">Campos adicionales</a></nav>
<form class="prc-form prc-form--taller" method="post" action="/enviado" data-prc-dirty-guard>
  <input type="text" id="titulo" name="prc_title" value="Red de ejemplo" />
  <button id="guardar" type="submit">Guardar los datos</button>
</form>
</body>`;
		const abrirSucio = async ( contexto, conSwal ) => {
			const pagina = await contexto.newPage();
			await pagina.route( 'https://prc.test/**', ( route ) => {
				const url = route.request().url();
				route.fulfill( {
					status: 200,
					contentType: 'text/html; charset=utf-8',
					body: url.includes( '/enviado' ) ? '<!doctype html><h1>ENVIADO</h1>' :
						url.includes( '/otra' ) ? '<!doctype html><h1>OTRA</h1>' :
						'<style>' + CSS + '</style>' + SUCIO + ( conSwal ? '<script>' + SWAL + '</scr' + 'ipt>' : '' ) + '<script>' + APP + '</scr' + 'ipt>',
				} );
			} );
			await pagina.goto( 'https://prc.test/taller' );
			return pagina;
		};
		const PREGUNTA = 'Hay cambios sin guardar. ¿Salir sin guardar?';

		// Sin JavaScript: la pestaña se sigue.
		const sinJs = await browser.newContext( { javaScriptEnabled: false } );
		let page = await abrirSucio( sinJs, true );
		await page.fill( '#titulo', 'Otro título' );
		await Promise.all( [ page.waitForURL( '**/otra' ), page.click( '#pestana' ) ] );
		ok( page.url().includes( 'otra' ), 'sin guion, la pestaña se sigue sin preguntar' );
		await sinJs.close();

		// Sin cambios no se pregunta nada.
		page = await abrirSucio( ctx, false );
		page.on( 'dialog', async ( d ) => { ok( false, 'sin cambios no debería preguntar: ' + d.message() ); await d.dismiss(); } );
		await Promise.all( [ page.waitForURL( '**/otra' ), page.click( '#pestana' ) ] );
		ok( page.url().includes( 'otra' ), 'sin cambios, la pestaña se sigue sin preguntar' );
		await page.close();

		// Con el confirm() del navegador.
		page = await abrirSucio( ctx, false );
		let pregunta = null;
		page.on( 'dialog', async ( d ) => { pregunta = d.message(); await d.dismiss(); } );
		await page.fill( '#titulo', 'Otro título' );
		await page.click( '#pestana' );
		await page.waitForTimeout( 300 );
		ok( PREGUNTA === pregunta, 'con cambios, la pestaña pregunta con confirm(): ' + JSON.stringify( pregunta ) );
		ok( ! page.url().includes( 'otra' ), 'al cancelar se queda, con lo escrito' );
		ok( 'Otro título' === await page.inputValue( '#titulo' ), 'y lo escrito sigue ahí' );
		page.removeAllListeners( 'dialog' );
		let preguntas = 0;
		page.on( 'dialog', ( d ) => { preguntas++; d.accept(); } );
		await Promise.all( [ page.waitForURL( '**/otra' ), page.click( '#pestana' ) ] );
		ok( page.url().includes( 'otra' ) && 1 === preguntas, 'al aceptar se va, y el navegador no vuelve a preguntar: ' + preguntas );
		await page.close();

		// Cerrar la página: el aviso del propio navegador.
		page = await abrirSucio( ctx, false );
		let aviso = null;
		page.on( 'dialog', async ( d ) => { aviso = d.type(); await d.dismiss(); } );
		await page.fill( '#titulo', 'Otro título' );
		await page.close( { runBeforeUnload: true } );
		await new Promise( ( r ) => setTimeout( r, 300 ) );
		ok( 'beforeunload' === aviso, 'con cambios, cerrar la página da el aviso del navegador: ' + aviso );

		// Guardar el propio formulario no pregunta.
		page = await abrirSucio( ctx, false );
		page.on( 'dialog', async ( d ) => { ok( false, 'guardar no debería preguntar: ' + d.type() ); await d.dismiss(); } );
		await page.fill( '#titulo', 'Otro título' );
		await Promise.all( [ page.waitForURL( '**/enviado' ), page.click( '#guardar' ) ] );
		ok( page.url().includes( 'enviado' ), 'guardar envía sin preguntar' );
		await page.close();

		// Con SweetAlert2.
		page = await abrirSucio( ctx, true );
		page.on( 'dialog', async ( d ) => { ok( false, 'no debería salir el diálogo del navegador: ' + d.type() ); await d.dismiss(); } );
		await page.fill( '#titulo', 'Otro título' );
		await page.click( '#pestana' );
		await page.waitForSelector( '.swal2-popup', { state: 'visible' } );
		ok( PREGUNTA === ( await page.textContent( '.swal2-title' ) ).trim(), 'sale SweetAlert2 con la pregunta' );
		ok( 'Salir sin guardar' === ( await page.textContent( '.swal2-confirm' ) ).trim(), 'el botón dice «Salir sin guardar»' );
		ok( await page.evaluate( () => document.activeElement.classList.contains( 'swal2-cancel' ) ), 'el foco arranca en Cancelar' );
		await page.keyboard.press( 'Escape' );
		await page.waitForSelector( '.swal2-popup', { state: 'hidden' } );
		ok( ! page.url().includes( 'otra' ), 'Escape se queda' );
		await page.click( '#pestana' );
		await page.waitForSelector( '.swal2-popup', { state: 'visible' } );
		await Promise.all( [ page.waitForURL( '**/otra' ), page.click( '.swal2-confirm' ) ] );
		ok( page.url().includes( 'otra' ), 'confirmar sigue la pestaña' );
		await page.close();
	}

	// --- 10. Un fin de plazo ya pasado pide confirmación (ADR-0050) --------
	//
	// Solo si la fecha se cambió y queda antes de hoy, que pone el servidor.
	// Sin JavaScript se guarda sin preguntar, como cualquier otra fecha.
	{
		const DATOS = `<!doctype html><meta charset="utf-8">
<body class="prc-app">
<form id="datos" method="post" action="/enviado">
  <input type="hidden" name="prc_do" value="datos" />
  <input type="date" id="cierre" name="prc_closes_at" value="2026-12-01"
         data-prc-confirm-before="2026-11-23"
         data-prc-confirm-text="¿Guardar con un fin de plazo que ya ha pasado? Si el plazo de solicitud está abierto, se cierra en cuanto guarde." />
  <button id="guardar" type="submit">Guardar los datos</button>
</form>
</body>`;
		const abrirDatos = async ( contexto, conSwal ) => {
			const page = await contexto.newPage();
			await page.route( 'https://prc.test/**', ( route ) => {
				const url = route.request().url();
				route.fulfill( {
					status: 200,
					contentType: 'text/html; charset=utf-8',
					body: url.includes( '/enviado' ) ? '<!doctype html><h1>ENVIADO</h1>' :
						'<style>' + CSS + '</style>' + DATOS + ( conSwal ? '<script>' + SWAL + '</scr' + 'ipt>' : '' ) + '<script>' + APP + '</scr' + 'ipt>',
				} );
			} );
			await page.goto( 'https://prc.test/panel' );
			return page;
		};

		// Una fecha futura no pregunta.
		let page = await abrirDatos( ctx, true );
		await page.fill( '#cierre', '2026-12-15' );
		await Promise.all( [ page.waitForURL( '**/enviado' ), page.click( '#guardar' ) ] );
		ok( page.url().includes( 'enviado' ), 'un fin de plazo futuro se guarda sin preguntar' );
		await page.close();

		// Con SweetAlert2: el diálogo, y cancelar no guarda.
		page = await abrirDatos( ctx, true );
		await page.fill( '#cierre', '2026-11-20' );
		await page.click( '#guardar' );
		await page.waitForSelector( '.swal2-popup', { state: 'visible' } );
		ok( '¿Guardar con un fin de plazo que ya ha pasado?' === ( await page.textContent( '.swal2-title' ) ).trim(), 'un fin de plazo pasado pregunta con SweetAlert2' );
		await page.click( '.swal2-cancel' );
		await page.waitForSelector( '.swal2-popup', { state: 'hidden' } );
		ok( ! page.url().includes( 'enviado' ), 'cancelar no guarda' );
		await page.click( '#guardar' );
		await page.waitForSelector( '.swal2-popup', { state: 'visible' } );
		await Promise.all( [ page.waitForURL( '**/enviado' ), page.click( '.swal2-confirm' ) ] );
		ok( page.url().includes( 'enviado' ), 'confirmar guarda' );
		await page.close();

		// Sin SweetAlert2: el confirm() del navegador.
		page = await abrirDatos( ctx, false );
		await page.fill( '#cierre', '2026-11-20' );
		let pregunta = null;
		page.on( 'dialog', async ( d ) => { pregunta = d.message(); await d.accept(); } );
		await Promise.all( [ page.waitForURL( '**/enviado' ), page.click( '#guardar' ) ] );
		ok( null !== pregunta && pregunta.startsWith( '¿Guardar con un fin de plazo que ya ha pasado?' ), 'sin SweetAlert2 pregunta con confirm() y guarda al aceptar' );
		await page.close();

		// Sin JavaScript: se guarda sin preguntar.
		const sinJs = await browser.newContext( { javaScriptEnabled: false } );
		page = await abrirDatos( sinJs, true );
		await page.fill( '#cierre', '2026-11-20' );
		await Promise.all( [ page.waitForURL( '**/enviado' ), page.click( '#guardar' ) ] );
		ok( page.url().includes( 'enviado' ), 'sin JavaScript el fin de plazo pasado se guarda igual' );
		await sinJs.close();
	}

	// --- La ayuda en un diálogo (#86): abrir, cerrar y, sin JavaScript, navegar
	{
		const AYUDA = `<!doctype html><meta charset="utf-8"><body class="prc-app">
<form data-prc-dirty-guard><input id="t" name="prc_title" /></form>
<p class="prc-ayuda-enlace"><a id="ayuda" href="/ayuda/?guia=solicitar#antes-de-empezar"
   data-prc-help="prc-ayuda-solicitar-antes-de-empezar" aria-haspopup="dialog">Cómo se solicita</a></p>
<dialog class="prc-dialogo prc-ayuda-dialogo" id="prc-ayuda-solicitar-antes-de-empezar" aria-labelledby="antes-de-empezar-titulo">
  <section class="prc-ayuda__apartado" id="antes-de-empezar"><h3 id="antes-de-empezar-titulo">Antes de empezar</h3><p>Texto.</p></section>
  <form method="dialog" class="prc-acciones"><a href="/ayuda/?guia=solicitar#antes-de-empezar">Ver toda la ayuda</a>
  <button id="cerrar" class="prc-btn">Cerrar</button></form>
</dialog></body>`;
		const servir = async ( contexto ) => {
			const page = await contexto.newPage();
			await page.route( 'https://prc.test/**', ( route ) => route.fulfill( {
				status: 200,
				contentType: 'text/html; charset=utf-8',
				body: route.request().url().includes( '/ayuda/' ) ? '<!doctype html><h1>AYUDA</h1>' :
					'<style>' + CSS + '</style>' + AYUDA + '<script>' + APP + '</scr' + 'ipt>',
			} ) );
			await page.goto( 'https://prc.test/solicitud' );
			return page;
		};

		const conJs = await browser.newContext();
		const page = await servir( conJs );
		let preguntado = null;
		page.on( 'dialog', async ( d ) => { preguntado = d.message(); await d.dismiss(); } );
		await page.fill( '#t', 'algo escrito' );
		await page.click( '#ayuda' );
		const abierto = () => page.evaluate( () => document.getElementById( 'prc-ayuda-solicitar-antes-de-empezar' ).matches( ':modal' ) );
		ok( await abierto(), 'ayuda: el enlace abre el apartado en un diálogo modal' );
		ok( page.url().endsWith( '/solicitud' ), 'ayuda: sin salir de la página' );
		ok( null === preguntado, 'ayuda: con cambios sin guardar, abrirla no pregunta nada' );
		ok( 'antes-de-empezar-titulo' === await page.evaluate( () => document.activeElement.id ), 'ayuda: el foco va al título del apartado' );
		await page.keyboard.press( 'Escape' );
		ok( ! await abierto(), 'ayuda: Escape la cierra' );
		ok( 'ayuda' === await page.evaluate( () => document.activeElement.id ), 'ayuda: y el foco vuelve al enlace' );
		await page.click( '#ayuda' );
		await page.click( '#cerrar' );
		ok( ! await abierto(), 'ayuda: «Cerrar» la cierra' );
		ok( 'ayuda' === await page.evaluate( () => document.activeElement.id ), 'ayuda: y el foco vuelve al enlace' );
		await conJs.close();

		const sinJs = await browser.newContext( { javaScriptEnabled: false } );
		const plana = await servir( sinJs );
		await Promise.all( [ plana.waitForURL( '**/ayuda/**' ), plana.click( '#ayuda' ) ] );
		ok( plana.url().includes( '/ayuda/?guia=solicitar#antes-de-empezar' ), 'ayuda: sin JavaScript el enlace lleva a la ayuda, en su apartado' );
		await sinJs.close();
	}

	await browser.close();
	console.log( fallos ? '\n' + fallos + ' FALLOS' : '\nTodo verde' );
	process.exit( fallos ? 1 : 0 );
} )();
