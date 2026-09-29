/**
 * Remove, after each test, the listeners a script added to `window` and
 * `document`.
 *
 * The scripts delegate on those two objects, and jsdom keeps them for the whole
 * file: without this, every test that loads a script again would stack one
 * more copy of each handler, and a handler from an earlier test would keep
 * reacting with the state it closed over.
 */
import { afterEach, beforeEach } from 'vitest';

let added = [];
let originals = [];

beforeEach( () => {
	[ window, document ].forEach( ( target ) => {
		const add = target.addEventListener;
		originals.push( [ target, add ] );
		target.addEventListener = function ( type, listener, options ) {
			added.push( [ target, type, listener, options ] );
			return add.call( this, type, listener, options );
		};
	} );
} );

afterEach( () => {
	added.forEach( ( [ target, type, listener, options ] ) => {
		target.removeEventListener( type, listener, options );
	} );
	added = [];
	originals.forEach( ( [ target, add ] ) => {
		target.addEventListener = add;
	} );
	originals = [];
	document.body.innerHTML = '';
} );
