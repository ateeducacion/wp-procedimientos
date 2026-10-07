#!/usr/bin/env node
/*
 * Comprueba que no se filtra información que no puede salir del repositorio.
 *
 * Este repositorio se publica como software libre. Eso significa que **nada de
 * lo que se versiona** puede decir de quién es el despliegue, dónde está, qué
 * infraestructura usa ni cómo era el sistema que se sustituye por dentro. Todo
 * eso vive en `.local/`, que está en el `.gitignore` (ADR-0009).
 *
 * No es una comprobación de estilo: es la que evita publicar la dirección del
 * servidor de analítica de una organización, el identificador de su sitio, o
 * el mapa de una instalación ajena.
 *
 * Node y sin dependencias, como el resto de las comprobaciones: el stack de
 * este repositorio es PHP y JavaScript, y un tercer lenguaje es un requisito
 * más que instalar en cada máquina y en CI.
 *
 *   node scripts/check-public.mjs           comprueba
 *   node scripts/check-public.mjs --list    además enseña cada línea
 */

import { execFileSync } from 'node:child_process';
import { readFileSync, statSync } from 'node:fs';
import { dirname, extname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = resolve( dirname( fileURLToPath( import.meta.url ) ), '..' );

/*
 * Qué no puede aparecer, y por qué. El consejo se le enseña a quien lo rompa,
 * así que dice qué hacer, no solo que está mal.
 *
 * Aquí solo están las reglas que no delatan nada. Las que nombran lo que no
 * puede salir —la organización, la red de destino, otros repositorios, cifras—
 * no pueden vivir en un fichero público: escribirlas para buscarlas sería
 * publicarlas. Se leen de `.check-public-rules` (en el `.gitignore`) o, en CI,
 * del secret `PRC_PUBLIC_RULES`. Ver ADR-0009.
 */
const RULES = [
	{
		name: 'rutas locales de quien desarrolla',
		// `/home/user/` es el marcador genérico de los ejemplos (lo trae alguna
		// skill de terceros, que va verbatim): no dice de quién es el portátil.
		pattern: /\/Users\/|\/home\/(?!user\/)[a-z]/,
		advice: 'Una ruta absoluta de un portátil no le sirve a nadie más y dice quién eres.',
	},
];

const PRIVATE_FILE = '.check-public-rules';
const PRIVATE_ADVICE =
	'Término privado (ADR-0009): se describe en genérico o se deja en `.local/`. ' +
	`La regla está en \`${ PRIVATE_FILE }\`, que no se versiona.`;

/**
 * Reglas privadas: una línea «# nombre» abre una regla y cada línea siguiente
 * es una expresión regular.
 *
 * @return {object[]} Reglas con la misma forma que RULES.
 */
function privateRules() {
	let text = process.env.PRC_PUBLIC_RULES || '';
	if ( ! text ) {
		try {
			text = readFileSync( join( ROOT, PRIVATE_FILE ), 'utf8' );
		} catch {
			console.warn(
				`Sin \`${ PRIVATE_FILE }\` ni PRC_PUBLIC_RULES: solo se comprueban las reglas genéricas.`
			);
			return [];
		}
	}
	const rules = [];
	let name = 'término privado';
	for ( const raw of text.split( '\n' ) ) {
		const line = raw.trim();
		if ( line.startsWith( '#' ) ) {
			name = line.replace( /^#+\s*/, '' ) || name;
		} else if ( line ) {
			rules.push( { name, pattern: new RegExp( line, 'i' ), advice: PRIVATE_ADVICE } );
		}
	}
	return rules;
}

RULES.push( ...privateRules() );

// Lo generado y lo que es, por definición, material de investigación.
const SKIP = [
	'.local/',
	'node_modules/',
	'vendor/',
	'snippets/prc-procedimientos-app.bundle.php',
];
const EXTENSIONS = new Set( [ '.php', '.md', '.js', '.mjs', '.css', '.html', '.json', '.yml', '.yaml', '.xml', '.dist', '.txt', '.py', '.cjs', '.sh' ] );
const NO_EXTENSION = new Set( [ 'Makefile', 'Dockerfile' ] );

/**
 * Skills de terceros: van tal cual las trae `gh skill`, que les deja
 * `github-repo:` en el frontmatter. No se pueden corregir sin divergir de
 * upstream, y sus ejemplos (`/home/user/…`) no son de nadie. Las propias no
 * llevan esa marca y se siguen revisando.
 *
 * @param {string} f Ruta relativa a la raíz.
 * @return {boolean} Si cae dentro de una skill de terceros.
 */
function isThirdPartySkill( f ) {
	const m = f.match( /^\.(?:agents|claude)\/skills\/[^/]+\// );
	if ( ! m ) {
		return false;
	}
	try {
		return /^\s*github-repo:/m.test( readFileSync( join( ROOT, m[ 0 ], 'SKILL.md' ), 'utf8' ) );
	} catch {
		return false;
	}
}

/**
 * Lo que git incluiría: se le pregunta a él, que ya conoce el `.gitignore`.
 *
 * @return {string[]} Rutas relativas a la raíz del repositorio.
 */
function tracked() {
	let out = '';
	try {
		out = execFileSync(
			'git',
			[ 'ls-files', '--cached', '--others', '--exclude-standard' ],
			{ cwd: ROOT, encoding: 'utf8', maxBuffer: 64 * 1024 * 1024 }
		);
	} catch {
		// Sin git no hay lista fiable de lo que se publicaría, y adivinarla
		// sería peor que decirlo: se para.
		console.error( 'No se ha podido preguntar a git qué ficheros se versionan.' );
		process.exit( 2 );
	}

	return out
		.split( '\n' )
		.filter( ( f ) => f && ! SKIP.some( ( x ) => f.includes( x ) ) )
		.filter( ( f ) => ! isThirdPartySkill( f ) )
		.filter( ( f ) => {
			const name = f.split( '/' ).pop();
			if ( ! EXTENSIONS.has( extname( f ) ) && ! NO_EXTENSION.has( name ) ) {
				return false;
			}
			try {
				return statSync( join( ROOT, f ) ).isFile();
			} catch {
				return false;
			}
		} );
}

const verbose = process.argv.includes( '--list' );
const found = new Map(); // nombre de la regla → apariciones

for ( const file of tracked() ) {
	let text;
	try {
		text = readFileSync( join( ROOT, file ), 'utf8' );
	} catch {
		continue;
	}
	const lines = text.split( '\n' );
	for ( let i = 0; i < lines.length; i++ ) {
		for ( const rule of RULES ) {
			if ( rule.pattern.test( lines[ i ] ) ) {
				if ( ! found.has( rule.name ) ) {
					found.set( rule.name, [] );
				}
				found.get( rule.name ).push( {
					file,
					line: i + 1,
					text: lines[ i ].trim().slice( 0, 120 ),
				} );
			}
		}
	}
}

if ( found.size === 0 ) {
	console.log( 'Publicación: nada que no pueda salir del repositorio.' );
	process.exit( 0 );
}

const total = [ ...found.values() ].reduce( ( n, v ) => n + v.length, 0 );
console.log( `Publicación: ${ total } aparición(es) que no pueden ir a un repositorio público.\n` );

for ( const rule of RULES ) {
	const hits = found.get( rule.name );
	if ( ! hits ) {
		continue;
	}
	const files = [ ...new Set( hits.map( ( c ) => c.file ) ) ].sort();
	console.log( `  ${ rule.name }: ${ hits.length } en ${ files.length } fichero(s)` );
	console.log( `    → ${ rule.advice }` );
	for ( const f of files.slice( 0, 10 ) ) {
		const count = hits.filter( ( c ) => c.file === f ).length;
		console.log( `      ${ String( count ).padStart( 4 ) }  ${ f }` );
	}
	if ( files.length > 10 ) {
		console.log( `      … y ${ files.length - 10 } fichero(s) más` );
	}
	if ( verbose ) {
		for ( const c of hits ) {
			console.log( `        ${ c.file }:${ c.line }: ${ c.text }` );
		}
	}
	console.log();
}

console.log( 'Con `--list` se ve cada línea. Lo que sea material de investigación va a `.local/`.' );
process.exit( 1 );
