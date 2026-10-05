/**
 * Performance profile of the application, with PHP-SPX.
 *
 * Pide una lista fija de pantallas al wp-env arrancado con `--spx`, con el
 * perfilador encendido, y resume cada una: tiempo de pared, memoria, llamadas
 * y las funciones que más tiempo se llevan —las propias, `Prc\…`, aparte—. El
 * resumen se guarda con una etiqueta (por defecto, la rama) para compararlo
 * con otro: así se ve si un cambio mejora o empeora, y dónde.
 *
 * Cada pantalla se calienta una vez sin perfilar y luego se mide RUNS veces;
 * el tiempo que cuenta es la mediana, que es lo que aguanta el ruido de
 * Docker. Los informes completos se quedan en /tmp/spx del contenedor, así que
 * se pueden abrir también en la interfaz de SPX con su gráfico de llama.
 *
 * Uso:  node scripts/profile.mjs [etiqueta]          (make profile)
 *       node scripts/profile.mjs compare <a> <b>     (make profile-compare)
 */

import { execFileSync } from 'node:child_process';
import { mkdir, readFile, readdir, rm, writeFile } from 'node:fs/promises';
import { createReadStream } from 'node:fs';
import { createGunzip } from 'node:zlib';
import { createInterface } from 'node:readline';
import path from 'node:path';

const BASE = process.env.PRC_URL || 'http://localhost:8698';
const RUNS = Number( process.env.RUNS || 5 );
const OUT = 'artifacts/profile';
const TOP = 15;

const USERS = {
	gestion: { user: 'gestion', pass: 'password' },
	direccion: { user: 'direccion', pass: 'password' },
	admin: { user: 'admin', pass: 'password' },
};

/** The screens measured. `who` empty means logged out. */
const SCENES = [
	{ name: 'Catálogo público', who: '', go: '/procedimientos/' },
	{ name: 'Ficha pública', who: '', go: '/?post_type=prc_procedure&p={ID}' },
	{ name: 'Mis procedimientos', who: 'gestion', go: '/gestion-de-procedimientos/' },
	{ name: 'Edición: datos', who: 'gestion', go: '/editar-procedimiento/?procedimiento={ID}&panel=datos' },
	{ name: 'Edición: preguntas', who: 'gestion', go: '/editar-procedimiento/?procedimiento={ID}&panel=preguntas' },
	{ name: 'Edición: solicitudes', who: 'gestion', go: '/editar-procedimiento/?procedimiento={ID}&panel=solicitudes' },
	{ name: 'Solicitud de un centro', who: 'direccion', go: '/solicitud/?procedimiento={ID}' },
	{ name: 'Mi centro', who: 'direccion', go: '/mi-centro/' },
	{ name: 'Listado del escritorio', who: 'admin', go: '/wp-admin/edit.php?post_type=prc_procedure' },
	{ name: 'Solicitudes del escritorio', who: 'admin', go: '/wp-admin/edit.php?post_type=prc_application' },
];

const SPX_COOKIES = 'SPX_ENABLED=1; SPX_KEY=dev; SPX_AUTO_START=1; SPX_REPORT=full';

/**
 * Run a shell command inside the wp-env web container (where SPX lives).
 *
 * @param {string} command Shell command.
 * @return {string}
 */
function inWeb( command ) {
	return execFileSync( 'npx', [ '@wordpress/env', 'run', 'wordpress', 'sh', '-c', command ], {
		encoding: 'utf8',
		stdio: [ 'ignore', 'pipe', 'pipe' ],
	} );
}

/**
 * Log in and return the cookie header.
 *
 * @param {{user: string, pass: string}} who Credentials.
 * @return {Promise<string>}
 */
async function login( who ) {
	const res = await fetch( `${ BASE }/wp-login.php`, {
		method: 'POST',
		redirect: 'manual',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded',
			Cookie: 'wordpress_test_cookie=WP%20Cookie%20check',
		},
		body: new URLSearchParams( { log: who.user, pwd: who.pass, testcookie: '1' } ),
	} );
	const cookies = res.headers.getSetCookie().map( ( c ) => c.split( ';' )[ 0 ] );
	if ( ! cookies.some( ( c ) => c.startsWith( 'wordpress_logged_in_' ) ) ) {
		throw new Error( `No se pudo entrar como ${ who.user }: ¿está provisionado el entorno?` );
	}
	return cookies.join( '; ' );
}

/**
 * Request a page and fail loudly if it does not answer 200.
 *
 * @param {string} url    Absolute URL.
 * @param {string} cookie Cookie header.
 * @return {Promise<string>}
 */
async function get( url, cookie ) {
	const res = await fetch( url, { headers: { Cookie: cookie }, redirect: 'manual' } );
	if ( 200 !== res.status ) {
		throw new Error( `${ url } respondió ${ res.status }` );
	}
	return res.text();
}

/**
 * Warm a page up, following redirects, and return the path that answered.
 *
 * La ficha pública se pide por su ID y redirige a su enlace permanente: se
 * mide la página de destino. Acabar en el login es otra cosa y falla.
 *
 * @param {string} url    Absolute URL.
 * @param {string} cookie Cookie header.
 * @return {Promise<string>}
 */
async function warm( url, cookie ) {
	const res = await fetch( url, { headers: { Cookie: cookie } } );
	const final = new URL( res.url );
	if ( 200 !== res.status || final.pathname.includes( 'wp-login' ) ) {
		throw new Error( `${ url } respondió ${ res.status } en ${ final.pathname }` );
	}
	return final.pathname + final.search;
}

/**
 * Flat profile of one SPX full report: per function, calls and wall time.
 *
 * Las líneas de `[events]` son «función inicio|fin tiempo_ns memoria». El
 * tiempo exclusivo es el de la llamada menos el de sus hijas; el inclusivo
 * solo se suma en la llamada más externa, para que la recursión no lo cuente
 * dos veces.
 *
 * @param {string} file Path of the .txt.gz report.
 * @return {Promise<Map<string, {calls: number, inc: number, exc: number}>>}
 */
async function flatProfile( file ) {
	const lines = createInterface( { input: createReadStream( file ).pipe( createGunzip() ) } );
	const events = [];
	const names = [];
	let section = '';
	for await ( const line of lines ) {
		if ( line.startsWith( '[' ) ) {
			section = line;
		} else if ( '[events]' === section && line ) {
			events.push( line );
		} else if ( '[functions]' === section ) {
			names.push( line );
		}
	}

	const stats = names.map( () => ( { calls: 0, inc: 0, exc: 0 } ) );
	const depth = new Array( names.length ).fill( 0 );
	const stack = [];
	for ( const line of events ) {
		const [ idx, start, wt ] = line.split( ' ' ).map( Number );
		if ( 1 === start ) {
			stack.push( { idx, wt, children: 0 } );
			stats[ idx ].calls++;
			depth[ idx ]++;
			continue;
		}
		const frame = stack.pop();
		const spent = wt - frame.wt;
		stats[ idx ].exc += spent - frame.children;
		if ( 0 === --depth[ idx ] ) {
			stats[ idx ].inc += spent;
		}
		if ( stack.length ) {
			stack[ stack.length - 1 ].children += spent;
		}
	}
	return new Map( names.map( ( n, i ) => [ n, stats[ i ] ] ) );
}

const median = ( list ) => [ ...list ].sort( ( a, b ) => a - b )[ Math.floor( list.length / 2 ) ];
const ms = ( ns ) => Math.round( ns / 1e4 ) / 100;

/**
 * Average the flat profiles of several runs and keep the interesting rows.
 *
 * @param {Array<Map<string, object>>} profiles One per run.
 * @return {{top: object[], own: object[]}}
 */
function summarise( profiles ) {
	const sum = new Map();
	for ( const profile of profiles ) {
		for ( const [ fn, s ] of profile ) {
			const acc = sum.get( fn ) || { fn, calls: 0, inc: 0, exc: 0 };
			acc.calls += s.calls;
			acc.inc += s.inc;
			acc.exc += s.exc;
			sum.set( fn, acc );
		}
	}
	const rows = [ ...sum.values() ].map( ( r ) => ( {
		fn: r.fn,
		calls: Math.round( r.calls / profiles.length ),
		inc_ms: ms( r.inc / profiles.length ),
		exc_ms: ms( r.exc / profiles.length ),
	} ) );
	return {
		top: [ ...rows ].sort( ( a, b ) => b.exc_ms - a.exc_ms ).slice( 0, TOP ),
		own: rows
			.filter( ( r ) => r.fn.startsWith( 'Prc\\' ) )
			.sort( ( a, b ) => b.inc_ms - a.inc_ms )
	};
}

/**
 * Measure every scene and save the summary.
 *
 * @param {string} label Name of the summary file.
 */
async function measure( label ) {
	try {
		inWeb( 'php -m | grep -qi spx' );
	} catch {
		throw new Error( 'SPX no está cargado. Arranque el entorno con: make profile (o npx @wordpress/env start --spx)' );
	}

	const cookies = { '': '' };
	for ( const [ who, cred ] of Object.entries( USERS ) ) {
		cookies[ who ] = await login( cred );
	}
	// Uno abierto, no el primero: un borrador contesta 404 a quien no ha entrado.
	const id = ( await get( `${ BASE }/gestion-de-procedimientos/`, cookies.gestion ) ).match(
		/data-estado="open"[^>]*data-procedimiento="(\d+)"|data-procedimiento="(\d+)"[^>]*data-estado="open"/
	)?.slice( 1 ).find( Boolean );
	if ( ! id ) {
		throw new Error( 'No encuentro ningún procedimiento abierto en «Mis procedimientos»: ¿está provisionado el entorno?' );
	}

	// Cada informe se reconoce por su URI, así que se vacía el directorio antes.
	inWeb( 'rm -f /tmp/spx/spx-full-*' );
	const uris = [];
	for ( const scene of SCENES ) {
		const uri = await warm( BASE + scene.go.replace( '{ID}', id ), cookies[ scene.who ] );
		const url = BASE + uri;
		uris.push( uri );
		for ( let i = 0; i < RUNS; i++ ) {
			await get( url, [ cookies[ scene.who ], SPX_COOKIES ].filter( Boolean ).join( '; ' ) );
		}
		process.stdout.write( '.' );
	}
	console.log( '' );

	const raw = path.join( OUT, 'raw' );
	await rm( raw, { recursive: true, force: true } );
	await mkdir( raw, { recursive: true } );
	inWeb( `cp /tmp/spx/spx-full-* /var/www/html/wp-content/prc-dev/${ raw }/` );

	const reports = new Map();
	for ( const file of ( await readdir( raw ) ).filter( ( f ) => f.endsWith( '.json' ) ) ) {
		const meta = JSON.parse( await readFile( path.join( raw, file ), 'utf8' ) );
		const list = reports.get( meta.http_request_uri ) || [];
		list.push( { meta, trace: path.join( raw, file.replace( /\.json$/, '.txt.gz' ) ) } );
		reports.set( meta.http_request_uri, list );
	}

	const result = { label, date: new Date().toISOString(), runs: RUNS, scenes: [] };
	for ( const [ i, scene ] of SCENES.entries() ) {
		const uri = uris[ i ];
		const runs = reports.get( uri ) || [];
		if ( ! runs.length ) {
			throw new Error( `SPX no ha dejado informe de ${ uri }` );
		}
		const profiles = await Promise.all( runs.map( ( r ) => flatProfile( r.trace ) ) );
		result.scenes.push( {
			name: scene.name,
			uri: scene.go,
			// SPX lo llama «_ms», pero son microsegundos.
			wall_ms: Math.round( median( runs.map( ( r ) => r.meta.wall_time_ms ) ) / 10 ) / 100,
			memory_mb: Math.round( median( runs.map( ( r ) => r.meta.peak_memory_usage ) ) / 1e4 ) / 100,
			calls: median( runs.map( ( r ) => r.meta.call_count ) ),
			...summarise( profiles ),
		} );
	}

	const file = path.join( OUT, `${ label }.json` );
	await writeFile( file, JSON.stringify( result, null, '\t' ) );
	report( result );
	console.log( `\nResumen: ${ file }  ·  Gráficos de llama: ${ BASE }/?SPX_KEY=dev&SPX_UI_URI=/` );
}

/**
 * Print a summary to the terminal.
 *
 * @param {object} result Output of measure().
 */
function report( result ) {
	console.log( `\nPerfil «${ result.label }» — mediana de ${ result.runs } peticiones por pantalla\n` );
	console.table( result.scenes.map( ( s ) => ( { pantalla: s.name, ms: s.wall_ms, MB: s.memory_mb, llamadas: s.calls } ) ) );
	for ( const s of result.scenes ) {
		console.log( `\n■ ${ s.name } — funciones propias, por tiempo inclusivo (ms)` );
		console.table( s.own.slice( 0, 8 ).map( ( r ) => ( { función: r.fn, ms: r.inc_ms, llamadas: r.calls } ) ) );
		console.log( `  …y lo que más tiempo se lleva por sí mismo, sea de quien sea:` );
		console.table( s.top.slice( 0, 8 ).map( ( r ) => ( { función: r.fn, ms: r.exc_ms, llamadas: r.calls } ) ) );
	}
}

/**
 * Compare two saved summaries: per screen, and per own function.
 *
 * @param {string} a Label of the baseline.
 * @param {string} b Label of the candidate.
 */
async function compare( a, b ) {
	const load = async ( l ) => JSON.parse( await readFile( path.join( OUT, `${ l }.json` ), 'utf8' ) );
	const [ before, after ] = await Promise.all( [ load( a ), load( b ) ] );
	const pct = ( x, y ) => ( x ? `${ y >= x ? '+' : '' }${ Math.round( ( ( y - x ) / x ) * 100 ) }%` : '—' );

	// Medido: la misma rama, dos veces seguidas, da hasta un 50 % de diferencia
	// en tiempo si la primera va justo después de arrancar el contenedor, y 0 %
	// en llamadas. Las llamadas son la señal fiable.
	console.log( `\n«${ a }» → «${ b }»  (el tiempo baila hasta ±50 % entre dos pasadas iguales; las llamadas no bailan)\n` );
	console.table( after.scenes.map( ( s ) => {
		const old = before.scenes.find( ( o ) => o.uri === s.uri ) || {};
		return { pantalla: s.name, [ a ]: old.wall_ms, [ b ]: s.wall_ms, Δms: pct( old.wall_ms, s.wall_ms ), llamadas: pct( old.calls, s.calls ) };
	} ) );

	for ( const s of after.scenes ) {
		const old = before.scenes.find( ( o ) => o.uri === s.uri );
		if ( ! old ) {
			continue;
		}
		const rows = new Map();
		old.own.forEach( ( r ) => rows.set( r.fn, { función: r.fn, [ a ]: r.inc_ms, [ b ]: 0 } ) );
		s.own.forEach( ( r ) => rows.set( r.fn, { función: r.fn, [ a ]: 0, ...rows.get( r.fn ), [ b ]: r.inc_ms } ) );
		const changed = [ ...rows.values() ]
			.map( ( r ) => ( { ...r, Δms: Math.round( ( r[ b ] - r[ a ] ) * 100 ) / 100 } ) )
			.filter( ( r ) => Math.abs( r.Δms ) >= 0.5 )
			.sort( ( x, y ) => Math.abs( y.Δms ) - Math.abs( x.Δms ) );
		if ( changed.length ) {
			console.log( `\n■ ${ s.name } — funciones propias que cambian ≥ 0,5 ms` );
			console.table( changed.slice( 0, 10 ) );
		}
	}
}

const [ command, ...args ] = process.argv.slice( 2 );
try {
	if ( 'compare' === command ) {
		if ( 2 !== args.length ) {
			throw new Error( 'Uso: node scripts/profile.mjs compare <etiqueta-a> <etiqueta-b>' );
		}
		await compare( ...args );
	} else {
		const branch = execFileSync( 'git', [ 'rev-parse', '--abbrev-ref', 'HEAD' ], { encoding: 'utf8' } ).trim();
		await measure( ( command || branch ).replace( /[^\w.-]+/g, '-' ) );
	}
} catch ( error ) {
	console.error( error.message );
	process.exit( 1 );
}
