/**
 * Screenshot script for the procedures application.
 *
 * Recorre las pantallas con Playwright y deja un informe HTML con las capturas
 * comentadas. Sirve para ver de un vistazo cómo queda todo después de un
 * cambio, y para que un PR enseñe lo que toca sin que nadie tenga que levantar
 * el entorno.
 *
 * **Cada pantalla se captura donde se usa, y algunas se usan en los dos
 * sitios.** El taller del procedimiento y el escritorio se usan sentado
 * delante de un ordenador, así que van solo en horizontal. La **portada**, la
 * **ficha pública** y la **solicitud** van en los dos: el enlace a una
 * convocatoria llega por correo y se abre en el móvil de quien dirige el
 * centro, y las dos formas tienen que aguantar.
 *
 * El guion es la constante SCENES. Cada escena dice quién entra, a dónde va,
 * qué se está enseñando y en qué tamaños: añadir una pantalla al informe es
 * añadir una escena, no tocar el motor.
 *
 * Uso:  make capturas                (todo)
 *       make capturas ONLY=mobile    (solo las de móvil)
 *       make capturas ONLY=desktop   (solo las de ordenador)
 */

import { chromium, devices } from 'playwright';
import { mkdir, writeFile, rm } from 'node:fs/promises';
import path from 'node:path';

const BASE = process.env.PRC_URL || 'http://localhost:8698';
const OUT = process.env.PRC_SCREENSHOTS || 'capturas';
const ONLY = process.env.ONLY || '';

/** Quién entra en cada escena del aplicativo. */
const USERS = {
	gestion: { user: 'gestion', pass: 'password' },
	direccion: { user: 'direccion', pass: 'password' },
	admin: { user: 'admin', pass: 'password' },
};

const SCREENS = {
	desktop: { viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1 },
	mobile: devices[ 'Pixel 7' ],
};

/**
 * El guion.
 *
 * `who` vacío significa **sin entrar**, que es como se ve una página pública y
 * como tiene que verse. `screens` dice en qué tamaños se captura esa escena.
 * `{ID}` es el procedimiento de demostración que se enseña: se pregunta al
 * sitio una vez y vale para el taller, la ficha y la solicitud.
 */
const SCENES = [
	// ── Lo público: los dos tamaños ───────────────────────────────────────
	{
		chapter: 'Lo público',
		title: 'Portada',
		note: 'Los procedimientos por curso —abiertos, en subsanación, próximos, cerrados y resueltos— con su estado derivado de las fechas y el filtro por ámbito.',
		who: '',
		go: '/procedimientos/',
		screens: [ 'desktop', 'mobile' ],
	},
	{
		chapter: 'Lo público',
		title: 'Ficha del procedimiento',
		note: 'Descripción, fechas, estado, ámbito, contacto y los enlaces a la resolución y a los listados. Sin entrar, el botón «Solicitar» lleva a iniciar sesión.',
		who: '',
		go: '/?post_type=prc_procedure&p={ID}',
		screens: [ 'desktop', 'mobile' ],
	},

	// ── Quien gestiona: solo ordenador ────────────────────────────────────
	{
		chapter: 'Quien gestiona',
		title: 'Mis procedimientos',
		note: 'El listado con el que se abre: los procedimientos de mis ámbitos por curso, con su estado y cuántas solicitudes tienen; «Nuevo procedimiento».',
		who: 'gestion',
		go: '/gestion-de-procedimientos/',
		screens: [ 'desktop' ],
	},
	{
		chapter: 'Quien gestiona',
		title: 'El menú desplegado',
		note: 'Las entradas que ve quien gestiona. La captura falla si el desplegable se queda por debajo del contenido, que es lo que pasa cuando se descuida el orden de las tres capas de ancho completo.',
		who: 'gestion',
		go: '/gestion-de-procedimientos/',
		unfold: 'Administrar',
		screens: [ 'desktop' ],
	},
	{
		chapter: 'Quien gestiona',
		title: 'Nuevo procedimiento',
		note: 'El alta: los campos mínimos para crear un procedimiento —título, curso, a quién va dirigido, ámbito y contacto—. Lo demás se edita después en el taller.',
		who: 'gestion',
		go: '/editar-procedimiento/',
		screens: [ 'desktop' ],
	},
	{
		chapter: 'El taller del procedimiento',
		title: 'Datos',
		note: 'Título, descripción, ámbito, curso, a quién va dirigido, los dos plazos, contacto, compromisos y si pide persona coordinadora.',
		who: 'gestion',
		go: '/editar-procedimiento/?procedimiento={ID}&panel=datos',
		screens: [ 'desktop' ],
	},
	{
		chapter: 'El taller del procedimiento',
		title: 'Preguntas',
		note: 'El constructor: hasta veinte preguntas de texto, de una opción, de varias o sí/no, con su clave fija aunque se reordenen.',
		who: 'gestion',
		go: '/editar-procedimiento/?procedimiento={ID}&panel=preguntas',
		screens: [ 'desktop' ],
	},
	{
		chapter: 'El taller del procedimiento',
		title: 'Enlaces',
		note: 'La resolución y los listados provisional y definitivo: enlaces, no copias. El definitivo es lo que pasa el procedimiento a resuelto.',
		who: 'gestion',
		go: '/editar-procedimiento/?procedimiento={ID}&panel=enlaces',
		screens: [ 'desktop' ],
	},
	{
		chapter: 'El taller del procedimiento',
		title: 'Solicitudes',
		note: 'La tabla de centros con cargo, coordinación y estado de revisión; admitir, pedir subsanar o excluir con nota, y exportar a CSV por POST.',
		who: 'gestion',
		go: '/editar-procedimiento/?procedimiento={ID}&panel=solicitudes',
		screens: [ 'desktop' ],
	},
	{
		chapter: 'El taller del procedimiento',
		title: 'Publicación',
		note: 'Publicar y archivar. Archivado cierra la edición; desarchivar es cosa de administración.',
		who: 'gestion',
		go: '/editar-procedimiento/?procedimiento={ID}&panel=publicacion',
		screens: [ 'desktop' ],
	},

	// ── Quien solicita: la solicitud también en el móvil ──────────────────
	{
		chapter: 'Quien solicita',
		title: 'Solicitud',
		note: 'El formulario del centro: código y nombre de solo lectura, cargo, compromisos, persona coordinadora si se pide y las preguntas del procedimiento. Si ya hay una, se edita esa.',
		who: 'direccion',
		go: '/solicitud/?procedimiento={ID}',
		screens: [ 'desktop', 'mobile' ],
	},
	{
		chapter: 'Quien solicita',
		title: 'Mi centro',
		note: 'Las solicitudes del centro: procedimiento, fecha, estado del procedimiento y estado de revisión con su nota.',
		who: 'direccion',
		go: '/mi-centro/',
		screens: [ 'desktop' ],
	},

	// ── El escritorio: solo ordenador ─────────────────────────────────────
	{
		chapter: 'El escritorio',
		title: 'Listado de procedimientos',
		note: 'Las columnas propias, Ámbito y Estado, justo detrás del título, y el filtro por ámbito.',
		who: 'admin',
		go: '/wp-admin/edit.php?post_type=prc_procedure',
		screens: [ 'desktop' ],
	},
	{
		chapter: 'El escritorio',
		title: 'Listado de solicitudes',
		note: 'Solo para diagnóstico: cada solicitud con su procedimiento y su centro.',
		who: 'admin',
		go: '/wp-admin/edit.php?post_type=prc_application',
		screens: [ 'desktop' ],
	},
	{
		chapter: 'El escritorio',
		title: 'Ajustes y diagnóstico',
		note: 'Qué hay montado en este sitio: tipos, taxonomías, roles y catálogo de centros. No guarda nada.',
		who: 'admin',
		go: '/wp-admin/edit.php?post_type=prc_procedure&page=prc-settings',
		screens: [ 'desktop' ],
	},
];

/**
 * Log in with one of the demo accounts, or log out when there is nobody.
 *
 * @param {import('playwright').Page} page The tab.
 * @param {string}                    who  Key of USERS; '' to stay logged out.
 * @return {Promise<void>}
 */
async function logIn( page, who ) {
	if ( '' === who ) {
		return;
	}
	const { user, pass } = USERS[ who ];
	await page.goto( `${ BASE }/wp-login.php`, { waitUntil: 'domcontentloaded' } );
	await page.fill( '#user_login', user );
	await page.fill( '#user_pass', pass );
	await Promise.all( [
		page.waitForNavigation( { waitUntil: 'domcontentloaded' } ),
		page.click( '#wp-submit' ),
	] );
}

/**
 * The ID of the demo procedure, which the workshop, apply and single URLs need.
 *
 * Se pregunta al sitio en vez de clavarlo: el número cambia con cada
 * reprovisión y una captura contra un procedimiento que no existe no avisa,
 * sale en blanco.
 *
 * **Se busca uno abierto**, no el primero de la tabla: la ficha y la solicitud
 * se capturan desde fuera, y un borrador —que es lo que suele encabezar el
 * listado— contesta 404 a quien no ha entrado.
 *
 * @param {import('playwright').Page} page The tab, already logged in.
 * @return {Promise<number>}
 */
async function procedureId( page ) {
	await page.goto( `${ BASE }/gestion-de-procedimientos/`, { waitUntil: 'domcontentloaded' } );
	const id = Number(
		( await page.getAttribute( 'tr[data-estado="open"]', 'data-procedimiento' ) ) || 0
	);
	if ( ! id ) {
		throw new Error(
			'No encuentro ningún procedimiento abierto en «Mis procedimientos»: ¿está provisionado el entorno?'
		);
	}
	return id;
}

/**
 * A file-name-safe slug.
 *
 * @param {string} text Any text.
 * @return {string}
 */
function slug( text ) {
	return text
		.toLowerCase()
		.normalize( 'NFD' )
		.replace( /[̀-ͯ]/g, '' )
		.replace( /[^a-z0-9]+/g, '-' )
		.replace( /^-|-$/g, '' );
}

/**
 * Take one screenshot of one scene at one screen size.
 *
 * @param {import('playwright').Browser} browser The browser.
 * @param {object}                       scene   The scene.
 * @param {string}                       screen  'desktop' or 'mobile'.
 * @param {number}                       id      Demo procedure ID.
 * @param {number}                       n       Scene number, for the file name.
 * @return {Promise<object>} The scene with its result.
 */
async function capture( browser, scene, screen, id, n ) {
	const context = await browser.newContext( { ...SCREENS[ screen ], locale: 'es-ES' } );
	const page = await context.newPage();
	// Ruta relativa a la raíz del informe, no solo el nombre: es la que usan
	// el HTML y el comentario del PR, y con una sola cadena no hay dos sitios
	// donde olvidarse del directorio.
	const img = `img/${ String( n ).padStart( 2, '0' ) }-${ screen }-${ slug( scene.title ) }.png`;
	const shot = { ...scene, screen, img, ok: true, error: '' };
	delete shot.screens;

	try {
		await logIn( page, scene.who );
		const target = scene.go.replace( '{ID}', String( id ) );
		const response = await page.goto( `${ BASE }${ target }`, { waitUntil: 'networkidle' } );
		if ( response && response.status() >= 400 ) {
			throw new Error( `la página respondió ${ response.status() }` );
		}
		// La barra de administración tapa la cabecera y no es del aplicativo.
		await page.addStyleTag( {
			content: '#wpadminbar { display: none !important; } html { margin-top: 0 !important; }',
		} );
		// Escena con el menú desplegado: la cabecera, el menú y la hoja son tres
		// capas distintas —cada una con su `transform` de ancho completo—, así
		// que entre ellas manda el orden del documento. Si alguien toca ese
		// orden, el título de la pantalla vuelve a pintarse encima del
		// desplegable y aquí se ve. `unfold` dice qué entrada se abre.
		if ( scene.unfold ) {
			const caja = page.locator( `.prc-tab-caja:has-text("${ scene.unfold }")` ).first();
			await caja.locator( 'summary' ).click();
			await page.waitForTimeout( 250 );
			const tapado = await page.evaluate( () => {
				const menu = document.querySelector( '.prc-desplegable' );
				if ( ! menu ) {
					return 'el menú no se abrió';
				}
				const caja = menu.getBoundingClientRect();
				const encima = document.elementFromPoint(
					Math.round( caja.left + caja.width / 2 ),
					Math.round( caja.top + caja.height / 2 )
				);
				return encima && ( encima === menu || menu.contains( encima ) )
					? ''
					: `el desplegable queda debajo de «${ encima ? encima.className || encima.tagName : '?' }»`;
			} );
			if ( '' !== tapado ) {
				throw new Error( tapado );
			}
		}
		await page.screenshot( { path: path.join( OUT, img ), fullPage: true } );
	} catch ( error ) {
		shot.ok = false;
		shot.error = error.message;
		// Una captura de lo que haya sirve más que ninguna: enseña dónde murió.
		await page.screenshot( { path: path.join( OUT, img ) } ).catch( () => {} );
	} finally {
		await context.close();
	}

	return shot;
}

/**
 * The HTML report.
 *
 * @param {object[]} shots What was captured.
 * @return {string}
 */
function report( shots ) {
	const esc = ( s ) =>
		String( s ).replace( /[&<>"]/g, ( c ) => ( { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[ c ] ) );
	let html = '';
	let chapter = '';

	for ( const s of shots ) {
		if ( s.chapter !== chapter ) {
			chapter = s.chapter;
			html += `<h2>${ esc( chapter ) }</h2>\n`;
		}
		html += `<figure class="${ s.screen }${ s.ok ? '' : ' mal' }">
	<figcaption><strong>${ esc( s.title ) }</strong> <small>${ esc( s.screen === 'mobile' ? 'móvil' : 'ordenador' ) }</small><span>${ esc(
			s.note
		) }</span>${ s.ok ? '' : `<em>✗ ${ esc( s.error ) }</em>` }</figcaption>
	<a href="${ s.img }"><img src="${ s.img }" alt="${ esc( s.title ) }"></a>
</figure>\n`;
	}

	const failed = shots.filter( ( s ) => ! s.ok ).length;

	return `<!doctype html>
<html lang="es">
<meta charset="utf-8">
<title>Capturas del aplicativo de procedimientos</title>
<style>
	:root { color-scheme: light dark; }
	body { font: 16px/1.5 system-ui, sans-serif; max-width: 1100px; margin: 2rem auto; padding: 0 1rem; }
	h1 { margin-bottom: .2rem; }
	.resumen { color: #666; margin-top: 0; }
	h2 { margin-top: 2.5rem; border-bottom: 1px solid #ccc; padding-bottom: .3rem; }
	figure { margin: 1.5rem 0; }
	figcaption { display: flex; flex-direction: column; gap: .15rem; margin-bottom: .5rem; }
	figcaption small { color: #888; font-weight: normal; }
	figcaption span { color: #666; font-size: .9em; }
	figcaption em { color: #b00; font-style: normal; }
	img { max-width: 100%; border: 1px solid #ccc; border-radius: 4px; }
	figure.mobile img { max-width: 380px; }
	figure.mal img { border-color: #b00; }
</style>
<h1>Capturas del aplicativo de procedimientos</h1>
<p class="resumen">${ shots.length } capturas · ${
		failed ? `<strong>${ failed } sin completar</strong>` : 'todas completadas'
	} · ${ new Date().toISOString().slice( 0, 16 ).replace( 'T', ' ' ) }</p>
${ html }`;
}

// Cada escena se despliega en una toma por tamaño, y el filtro se aplica sobre
// las tomas: ONLY=mobile deja las del móvil de una escena que tenga las dos.
const shots = SCENES.flatMap( ( scene ) =>
	scene.screens
		.filter( ( screen ) => '' === ONLY || screen === ONLY )
		.map( ( screen ) => ( { scene, screen } ) )
);

if ( 0 === shots.length ) {
	console.error( `ONLY=${ ONLY } no deja ninguna toma. Use «desktop» o «mobile».` );
	process.exit( 1 );
}

await rm( OUT, { recursive: true, force: true } );
await mkdir( path.join( OUT, 'img' ), { recursive: true } );

const browser = await chromium.launch();
const taken = [];

try {
	// El ID se pregunta una vez, con una sesión aparte: las tomas públicas no
	// entran en el aplicativo y no podrían averiguarlo.
	const context = await browser.newContext( SCREENS.desktop );
	const page = await context.newPage();
	await logIn( page, 'gestion' );
	const id = await procedureId( page );
	await context.close();

	for ( const [ i, { scene, screen } ] of shots.entries() ) {
		const shot = await capture( browser, scene, screen, id, i + 1 );
		taken.push( shot );
		console.log(
			`${ shot.ok ? '✓' : '✗' } ${ String( i + 1 ).padStart( 2, '0' ) } ${ screen.padEnd( 8 ) } ${ shot.title }${
				shot.ok ? '' : ` — ${ shot.error }`
			}`
		);
	}
} finally {
	await browser.close();
}

await writeFile( path.join( OUT, 'index.json' ), JSON.stringify( taken, null, 2 ) );
await writeFile( path.join( OUT, 'informe.html' ), report( taken ) );

const failed = taken.filter( ( s ) => ! s.ok );
console.log( `\n${ taken.length } capturas en ${ OUT }/informe.html` );
if ( failed.length ) {
	console.error( `${ failed.length } toma(s) sin completar.` );
	process.exit( 1 );
}
