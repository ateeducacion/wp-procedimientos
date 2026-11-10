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

	// --- 5. Duplicar, desde una fila de «Gestión» -----------------------------
	//
	// El mismo HTML que pinta `WorkspaceView::duplicate()`: un botón de icono
	// dentro de una celda, con el nonce sin `id` y el procedimiento oculto.
	{
		const DUPLICAR = `<!doctype html><meta charset="utf-8">
<body class="prc-app">
<table><tbody><tr><td class="prc-celda-accion" data-rotulo="Duplicar">
<form class="prc-acciones" method="post" action="/enviado"
      data-prc-confirm="¿Duplicar «Red de ejemplo»? Se creará un borrador nuevo con sus datos, sus preguntas y su configuración, sin plazos, sin curso y sin solicitudes."
      data-prc-confirm-ok="Duplicar">
  <input type="hidden" name="prc_nonce_duplicate_0" value="abc123" />
  <input type="hidden" name="prc_do" value="duplicate" />
  <input type="hidden" name="prc_procedure" value="42" />
  <button id="dup" type="submit" class="prc-accion" title="Duplicar este procedimiento">
    <span class="material-symbols-outlined" aria-hidden="true">content_copy</span>
    <span class="screen-reader-text">Duplicar este procedimiento</span>
  </button>
</form>
</td></tr></tbody></table>
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
			await page.goto( 'https://prc.test/gestion' );
			return { page, envio };
		};
		const lleva = ( cuerpo ) => /prc_do=duplicate/.test( cuerpo ) && /prc_procedure=42/.test( cuerpo ) && /prc_nonce_duplicate_0=abc123/.test( cuerpo );

		// Sin JavaScript: el botón envía y la acción se hace.
		const sinJs = await browser.newContext( { javaScriptEnabled: false } );
		let { page, envio } = await abrirDuplicar( sinJs, true );
		await Promise.all( [ page.waitForURL( '**/enviado' ), page.click( '#dup' ) ] );
		ok( lleva( envio.cuerpo ), 'duplicar sin JavaScript envía la operación, el procedimiento y el nonce: ' + envio.cuerpo );
		await sinJs.close();

		// Con el confirm() del navegador.
		( { page, envio } = await abrirDuplicar( ctx, false ) );
		let pregunta = null;
		page.on( 'dialog', async ( d ) => { pregunta = d.message(); await d.dismiss(); } );
		await page.click( '#dup' );
		await page.waitForTimeout( 300 );
		ok( null !== pregunta && pregunta.startsWith( '¿Duplicar «Red de ejemplo»?' ), 'duplicar sin SweetAlert pregunta con confirm(): ' + JSON.stringify( pregunta ) );
		ok( null === envio.cuerpo, 'al cancelar no se duplica' );
		page.removeAllListeners( 'dialog' );
		page.on( 'dialog', ( d ) => d.accept() );
		await Promise.all( [ page.waitForURL( '**/enviado' ), page.click( '#dup' ) ] );
		ok( lleva( envio.cuerpo ), 'al aceptar el confirm() se duplica' );
		await page.close();

		// Con SweetAlert2.
		( { page, envio } = await abrirDuplicar( ctx, true ) );
		page.on( 'dialog', async ( d ) => { ok( false, 'no debería salir el confirm() del navegador' ); await d.dismiss(); } );
		await page.click( '#dup' );
		await page.waitForSelector( '.swal2-popup', { state: 'visible' } );
		const titular = ( await page.textContent( '.swal2-title' ) ).trim();
		ok( '¿Duplicar «Red de ejemplo»?' === titular, 'titular: ' + titular );
		const verbo = ( await page.textContent( '.swal2-confirm' ) ).trim();
		ok( 'Duplicar' === verbo, 'el botón del diálogo dice el verbo: ' + verbo );
		await page.keyboard.press( 'Escape' );
		await page.waitForSelector( '.swal2-popup', { state: 'hidden' } );
		ok( null === envio.cuerpo, 'Escape no duplica' );
		await page.click( '#dup' );
		await page.waitForSelector( '.swal2-popup', { state: 'visible' } );
		await Promise.all( [ page.waitForURL( '**/enviado' ), page.click( '.swal2-confirm' ) ] );
		ok( lleva( envio.cuerpo ), 'confirmar duplica' );
		await page.close();
	}

	await browser.close();
	console.log( fallos ? '\n' + fallos + ' FALLOS' : '\nTodo verde' );
	process.exit( fallos ? 1 : 0 );
} )();
