/**
 * Unit tests for assets/js/prc-app.js.
 *
 * Each test builds the markup the server paints, loads the real script and
 * drives it with DOM events. The confirmation dialogs themselves (focus,
 * Escape, the no-JavaScript step) are checked in a real browser by
 * `npm run test:browser`; here only the decisions the script takes.
 */
import { afterEach, describe, expect, it, vi } from 'vitest';

/**
 * Load the script and fire DOMContentLoaded, as the page does.
 *
 * @return {Promise<void>}
 */
async function load() {
	vi.resetModules();
	await import( '../../assets/js/prc-app.js' );
	document.dispatchEvent( new Event( 'DOMContentLoaded' ) );
}

/**
 * Submit a form the way the browser does, with the button that sent it.
 *
 * @param {HTMLFormElement}   form      The form.
 * @param {HTMLElement|null} submitter The button, if any.
 * @return {Event} The dispatched event.
 */
function submit( form, submitter = null ) {
	const event = new window.SubmitEvent( 'submit', {
		bubbles: true,
		cancelable: true,
		submitter,
	} );
	form.dispatchEvent( event );
	return event;
}

/**
 * Let the promise chain of Swal.fire() settle.
 *
 * @return {Promise<void>}
 */
async function flush() {
	await Promise.resolve();
	await Promise.resolve();
}

afterEach( () => {
	delete window.Swal;
	delete window.jQuery;
	delete window.wp;
	delete window.bootstrap;
	delete navigator.sendBeacon;
} );

describe( 'confirmation before an irreversible action', () => {
	const FORM = `
		<form id="f" data-prc-confirm="¿Marcar «Becas» como histórico? No hay vuelta atrás.">
			<button type="submit" title="Marcar como histórico">Histórico</button>
		</form>`;

	it( 'lets a form without a question through', async () => {
		document.body.innerHTML = '<form id="f"><button type="submit">Guardar</button></form>';
		window.confirm = vi.fn();
		await load();

		expect( submit( document.getElementById( 'f' ) ).defaultPrevented ).toBe( false );
		expect( window.confirm ).not.toHaveBeenCalled();
	} );

	it( 'falls back to confirm() when SweetAlert2 did not load', async () => {
		document.body.innerHTML = FORM;
		window.confirm = vi.fn( () => false );
		await load();
		const form = document.getElementById( 'f' );

		expect( submit( form ).defaultPrevented ).toBe( true );
		expect( window.confirm ).toHaveBeenCalledWith(
			'¿Marcar «Becas» como histórico? No hay vuelta atrás.'
		);

		window.confirm = vi.fn( () => true );
		expect( submit( form ).defaultPrevented ).toBe( false );
	} );

	it( 'splits the question into title and detail and names the button after the action', async () => {
		document.body.innerHTML = FORM;
		window.Swal = { fire: vi.fn( () => Promise.resolve( { isConfirmed: false } ) ) };
		await load();
		const form = document.getElementById( 'f' );

		expect( submit( form, form.querySelector( 'button' ) ).defaultPrevented ).toBe( true );
		const options = window.Swal.fire.mock.calls[ 0 ][ 0 ];
		expect( options.title ).toBe( '¿Marcar «Becas» como histórico?' );
		expect( options.text ).toBe( 'No hay vuelta atrás.' );
		expect( options.confirmButtonText ).toBe( 'Marcar como histórico' );
		expect( options.cancelButtonText ).toBe( 'Cancelar' );
		expect( options.focusCancel ).toBe( true );
	} );

	it( 'prefers data-prc-confirm-ok and keeps a question without detail whole', async () => {
		document.body.innerHTML = `
			<form id="f" data-prc-confirm="¿Borrar?" data-prc-confirm-ok="Enviar a la papelera">
				<button type="submit" title="Borrar">x</button>
			</form>`;
		window.Swal = { fire: vi.fn( () => Promise.resolve( { isConfirmed: false } ) ) };
		await load();

		submit( document.getElementById( 'f' ) );
		const options = window.Swal.fire.mock.calls[ 0 ][ 0 ];
		expect( options.title ).toBe( '¿Borrar?' );
		expect( options.text ).toBe( '' );
		expect( options.confirmButtonText ).toBe( 'Enviar a la papelera' );
	} );

	it( 'uses a generic verb when the button says nothing', async () => {
		document.body.innerHTML = '<form id="f" data-prc-confirm="¿Seguro?"></form>';
		window.Swal = { fire: vi.fn( () => Promise.resolve( { isConfirmed: false } ) ) };
		await load();

		submit( document.getElementById( 'f' ) );
		expect( window.Swal.fire.mock.calls[ 0 ][ 0 ].confirmButtonText ).toBe( 'Sí, continuar' );
	} );

	it( 'does nothing when the dialog is cancelled', async () => {
		document.body.innerHTML = FORM;
		window.Swal = { fire: vi.fn( () => Promise.resolve( { isConfirmed: false } ) ) };
		await load();
		const form = document.getElementById( 'f' );
		form.requestSubmit = vi.fn();

		submit( form );
		await flush();

		expect( form.requestSubmit ).not.toHaveBeenCalled();
		expect( form.dataset.prcConfirmado ).toBeUndefined();
	} );

	it( 'resubmits with the same button once confirmed, and lets that submission through', async () => {
		document.body.innerHTML = FORM;
		window.Swal = { fire: vi.fn( () => Promise.resolve( { isConfirmed: true } ) ) };
		await load();
		const form = document.getElementById( 'f' );
		const button = form.querySelector( 'button' );
		form.requestSubmit = vi.fn();

		submit( form, button );
		await flush();

		expect( form.requestSubmit ).toHaveBeenCalledWith( button );
		expect( form.dataset.prcConfirmado ).toBe( '1' );
		// El envío que sale del diálogo no vuelve a preguntar.
		expect( submit( form, button ).defaultPrevented ).toBe( false );
		expect( window.Swal.fire ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'falls back to the first submit button and to submit() without requestSubmit', async () => {
		document.body.innerHTML = FORM;
		window.Swal = { fire: vi.fn( () => Promise.resolve( { isConfirmed: true } ) ) };
		await load();
		const form = document.getElementById( 'f' );
		form.requestSubmit = undefined;
		form.submit = vi.fn();

		submit( form );
		await flush();

		expect( window.Swal.fire.mock.calls[ 0 ][ 0 ].confirmButtonText ).toBe( 'Marcar como histórico' );
		expect( form.submit ).toHaveBeenCalledTimes( 1 );
	} );
} );

describe( 'edit lock over the WordPress Heartbeat', () => {
	const LOCK = `
		<div id="prc-edit-lock" data-post-id="42" data-lock="100:7" data-release-nonce="nonce"
			data-ajax-url="/wp-admin/admin-ajax.php">
			<dialog id="prc-lock-dialog">
				<span id="prc-lock-owner"></span>
				<form id="dentro"><button type="submit">Tomar posesión</button></form>
			</dialog>
		</div>
		<form id="fuera"><input name="t" value="Lo escrito"><button type="submit">Guardar</button></form>`;

	/**
	 * A stand-in for the part of jQuery the script uses: namespaced `on()` on
	 * the document, and a way to fire the Heartbeat events at it.
	 *
	 * @return {Function} The fake jQuery, with a `fire( name, data )` helper.
	 */
	function fakeJQuery() {
		const handlers = {};
		const $ = () => ( {
			on( event, handler ) {
				const name = event.split( '.' )[ 0 ];
				( handlers[ name ] = handlers[ name ] || [] ).push( handler );
				return this;
			},
		} );
		$.fire = ( name, data ) => ( handlers[ name ] || [] ).forEach( ( handler ) => handler( {}, data ) );
		return $;
	}

	/**
	 * Mount the lock box, WordPress' globals and the script.
	 *
	 * @param {string} html Markup to mount.
	 * @return {Promise<Function>} The fake jQuery.
	 */
	async function mount( html = LOCK ) {
		document.body.innerHTML = html;
		const $ = fakeJQuery();
		window.jQuery = $;
		window.wp = { heartbeat: { interval: vi.fn() } };
		navigator.sendBeacon = vi.fn();
		await load();
		return $;
	}

	/**
	 * Ask the script what it sends on the next beat.
	 *
	 * @param {Function} $ The fake jQuery.
	 * @return {Object} The Heartbeat payload.
	 */
	function beat( $ ) {
		const data = {};
		$.fire( 'heartbeat-send', data );
		return data;
	}

	it( 'renews its own lock on every beat, every 15 seconds', async () => {
		const $ = await mount();

		expect( window.wp.heartbeat.interval ).toHaveBeenCalledWith( 15 );
		expect( beat( $ )[ 'wp-refresh-post-lock' ] ).toEqual( { post_id: 42, lock: '100:7' } );
	} );

	it( 'carries the renewed lock and releases it when the tab closes', async () => {
		const $ = await mount();

		$.fire( 'heartbeat-tick', {} );
		$.fire( 'heartbeat-tick', { 'wp-refresh-post-lock': { new_lock: '200:7' } } );
		expect( beat( $ )[ 'wp-refresh-post-lock' ].lock ).toBe( '200:7' );

		window.dispatchEvent( new Event( 'pagehide' ) );
		const [ url, data ] = navigator.sendBeacon.mock.calls[ 0 ];
		expect( url ).toBe( '/wp-admin/admin-ajax.php' );
		expect( Object.fromEntries( data ) ).toEqual( {
			action: 'wp-remove-post-lock',
			_wpnonce: 'nonce',
			post_ID: '42',
			active_post_lock: '200:7',
		} );
	} );

	it( 'freezes the screen without losing what was written when someone else takes over', async () => {
		const $ = await mount();
		const dialog = document.getElementById( 'prc-lock-dialog' );
		dialog.showModal = vi.fn();

		$.fire( 'heartbeat-tick', { 'wp-refresh-post-lock': { lock_error: { name: 'Ana' } } } );

		expect( document.getElementById( 'fuera' ).inert ).toBe( true );
		expect( document.getElementById( 'dentro' ).inert ).not.toBe( true );
		expect( document.querySelector( '[name="t"]' ).value ).toBe( 'Lo escrito' );
		expect( document.getElementById( 'prc-lock-owner' ).textContent ).toBe( 'Ana' );
		expect( dialog.showModal ).toHaveBeenCalledTimes( 1 );

		// Perdido el bloqueo, ni se renueva, ni cambia, ni se suelta.
		expect( beat( $ ) ).toEqual( {} );
		$.fire( 'heartbeat-tick', { 'wp-refresh-post-lock': { new_lock: '300:7' } } );
		window.dispatchEvent( new Event( 'pagehide' ) );
		expect( navigator.sendBeacon ).not.toHaveBeenCalled();
	} );

	it( 'opens the notice with the attribute when showModal() is missing', async () => {
		const $ = await mount();
		const dialog = document.getElementById( 'prc-lock-dialog' );
		dialog.showModal = undefined;

		$.fire( 'heartbeat-tick', { 'wp-refresh-post-lock': { lock_error: { name: 'Ana' } } } );

		expect( dialog.hasAttribute( 'open' ) ).toBe( true );
	} );

	it( 'keeps the notice open on Escape and blocks submissions outside it', async () => {
		const $ = await mount();
		document.getElementById( 'prc-lock-dialog' ).showModal = vi.fn();
		document.getElementById( 'fuera' ).setAttribute( 'data-prc-confirm', '¿Guardar?' );
		window.confirm = vi.fn( () => true );
		$.fire( 'heartbeat-tick', { 'wp-refresh-post-lock': { lock_error: { name: 'Ana' } } } );

		const cancel = new Event( 'cancel', { cancelable: true } );
		document.getElementById( 'prc-lock-dialog' ).dispatchEvent( cancel );
		expect( cancel.defaultPrevented ).toBe( true );

		expect( submit( document.getElementById( 'fuera' ) ).defaultPrevented ).toBe( true );
		// Ni siquiera llega a preguntar: el envío se para en captura.
		expect( window.confirm ).not.toHaveBeenCalled();
		expect( submit( document.getElementById( 'dentro' ) ).defaultPrevented ).toBe( false );
	} );

	it( 'does not release the lock after a save, which hands it to the server', async () => {
		await mount();

		submit( document.getElementById( 'fuera' ) );
		window.dispatchEvent( new Event( 'pagehide' ) );

		expect( navigator.sendBeacon ).not.toHaveBeenCalled();
	} );

	it( 'still releases the lock when the save was stopped', async () => {
		await mount();
		document.getElementById( 'fuera' ).setAttribute( 'data-prc-confirm', '¿Guardar?' );
		window.confirm = vi.fn( () => false );

		submit( document.getElementById( 'fuera' ) );
		window.dispatchEvent( new Event( 'pagehide' ) );

		expect( navigator.sendBeacon ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'leaves the release to WordPress without sendBeacon or an AJAX URL', async () => {
		await mount( LOCK.replace( 'data-ajax-url="/wp-admin/admin-ajax.php"', '' ) );
		window.dispatchEvent( new Event( 'pagehide' ) );
		expect( navigator.sendBeacon ).not.toHaveBeenCalled();

		await mount();
		delete navigator.sendBeacon;
		expect( () => window.dispatchEvent( new Event( 'pagehide' ) ) ).not.toThrow();
	} );

	it.each( [
		[ 'there is no lock box', '<form></form>' ],
		[ 'the lock belongs to someone else', '<div id="prc-edit-lock" data-lock=""></div>' ],
	] )( 'does not renew anything when %s', async ( _label, html ) => {
		await mount( html );
		expect( window.wp.heartbeat.interval ).not.toHaveBeenCalled();
	} );

	it( 'does nothing without the Heartbeat', async () => {
		document.body.innerHTML = LOCK;
		window.jQuery = fakeJQuery();
		window.wp = {};
		await expect( load() ).resolves.toBeUndefined();
	} );
} );

describe( 'tooltips on icon buttons', () => {
	const BUTTONS = `
		<button data-bs-toggle="tooltip" title="Editar"></button>
		<button data-bs-toggle="tooltip" title="Borrar"></button>`;

	it( 'swaps the title for the Bootstrap tooltip once per button', async () => {
		document.body.innerHTML = BUTTONS;
		const created = [];
		const instances = new Map();
		function Tooltip( node ) {
			created.push( node );
			instances.set( node, this );
		}
		Tooltip.getInstance = ( node ) => instances.get( node ) || null;
		const first = document.querySelector( 'button' );
		instances.set( first, {} );
		window.bootstrap = { Tooltip };

		await load();

		expect( created ).toEqual( [ document.querySelectorAll( 'button' )[ 1 ] ] );
	} );

	it( 'keeps the plain title without Bootstrap', async () => {
		document.body.innerHTML = BUTTONS;
		await expect( load() ).resolves.toBeUndefined();
		expect( document.querySelector( 'button' ).title ).toBe( 'Editar' );
	} );
} );

describe( 'publication switch', () => {
	const SWITCH = `
		<form id="p">
			<input type="checkbox" data-prc-switch>
			<button class="prc-switch-boton" type="submit">Publicar</button>
		</form>
		<input type="checkbox" id="suelta" data-prc-switch>
		<input type="checkbox" id="otra">`;

	it( 'hides the button the switch replaces', async () => {
		document.body.innerHTML = SWITCH;
		await load();
		expect( document.querySelector( '.prc-switch-boton' ).hidden ).toBe( true );
	} );

	it( 'submits its form on change and locks itself meanwhile', async () => {
		document.body.innerHTML = SWITCH;
		await load();
		const form = document.getElementById( 'p' );
		form.submit = vi.fn();
		const box = form.querySelector( '[data-prc-switch]' );

		box.dispatchEvent( new Event( 'change', { bubbles: true } ) );

		expect( box.disabled ).toBe( true );
		expect( form.submit ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'ignores other checkboxes and a switch outside a form', async () => {
		document.body.innerHTML = SWITCH;
		await load();
		const form = document.getElementById( 'p' );
		form.submit = vi.fn();

		document.getElementById( 'otra' ).dispatchEvent( new Event( 'change', { bubbles: true } ) );
		document.getElementById( 'suelta' ).dispatchEvent( new Event( 'change', { bubbles: true } ) );

		expect( form.submit ).not.toHaveBeenCalled();
		expect( document.getElementById( 'suelta' ).disabled ).toBe( false );
	} );
} );
