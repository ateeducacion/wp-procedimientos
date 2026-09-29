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

	await browser.close();
	console.log( fallos ? '\n' + fallos + ' FALLOS' : '\nTodo verde' );
	process.exit( fallos ? 1 : 0 );
} )();
