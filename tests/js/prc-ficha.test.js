/**
 * Unit tests for assets/js/prc-ficha.js: the countdown and the content tabs of
 * a procedure's page.
 */
import { afterEach, describe, expect, it, vi } from 'vitest';

/**
 * Load the script. It only listens for DOMContentLoaded, so this has no effect
 * on the page until ready() is called.
 *
 * @return {Promise<void>}
 */
async function load() {
	vi.resetModules();
	await import( '../../assets/js/prc-ficha.js' );
}

/**
 * Fire DOMContentLoaded, as the page does once the markup is there.
 */
function ready() {
	document.dispatchEvent( new Event( 'DOMContentLoaded' ) );
}

afterEach( () => {
	vi.useRealTimers();
	window.history.replaceState( null, '', window.location.pathname );
} );

describe( 'countdown', () => {
	const NOW = new Date( '2026-10-01T10:00:00Z' );

	/**
	 * Markup of the counter as the server paints it.
	 *
	 * @param {string} end   Value of data-fin.
	 * @param {Array}  cells Which cells to paint.
	 * @return {string} HTML.
	 */
	function counter( end, cells = [ 'dias', 'horas', 'minutos', 'segundos' ] ) {
		return `<div class="prc-cuenta" data-fin="${ end }">${ cells
			.map( ( cell ) => `<span data-prc-cuenta="${ cell }">99</span>` )
			.join( '' ) }</div>`;
	}

	/**
	 * The four cells, in order.
	 *
	 * @return {string[]} Their text.
	 */
	function cells() {
		return [ 'dias', 'horas', 'minutos', 'segundos' ].map(
			( cell ) => document.querySelector( `[data-prc-cuenta="${ cell }"]` )?.textContent
		);
	}

	/**
	 * Load the script, then freeze the clock and start the page.
	 *
	 * @param {string} html Markup.
	 */
	async function start( html ) {
		document.body.innerHTML = html;
		await load();
		vi.useFakeTimers();
		vi.setSystemTime( NOW );
		ready();
	}

	it( 'paints the time left, padded, and ticks every second', async () => {
		await start( counter( '2026-10-02T12:03:04Z' ) );
		expect( cells() ).toEqual( [ '001', '02', '03', '04' ] );

		vi.advanceTimersByTime( 1000 );
		expect( cells() ).toEqual( [ '001', '02', '03', '03' ] );
	} );

	it( 'counts to the end instant in the site zone, not the viewer’s', async () => {
		// Las 23:59:00 de un sitio en UTC+1 son las 22:59:00 UTC.
		await start( counter( '2026-10-01T23:59:00+01:00' ) );
		expect( cells() ).toEqual( [ '000', '12', '59', '00' ] );
	} );

	it( 'stops at zero', async () => {
		await start( counter( '2026-10-01T10:00:02Z' ) );

		vi.advanceTimersByTime( 5000 );

		expect( cells() ).toEqual( [ '000', '00', '00', '00' ] );
		expect( vi.getTimerCount() ).toBe( 0 );
	} );

	it( 'shows zero and does not tick when the deadline has passed', async () => {
		await start( counter( '2026-09-30T23:59:00Z' ) );

		expect( cells() ).toEqual( [ '000', '00', '00', '00' ] );
		expect( vi.getTimerCount() ).toBe( 0 );
	} );

	it( 'leaves the server figures alone when the end date cannot be read', async () => {
		await start( counter( 'mañana' ) );

		expect( cells() ).toEqual( [ '99', '99', '99', '99' ] );
		expect( vi.getTimerCount() ).toBe( 0 );
	} );

	it( 'paints only the cells the counter has', async () => {
		await start( counter( '2026-10-02T12:03:04Z', [ 'dias', 'horas' ] ) );

		expect( cells() ).toEqual( [ '001', '02', undefined, undefined ] );
	} );
} );

describe( 'content tabs', () => {
	const TABS = `
		<div class="prc-tabs-ficha">
			<ul class="prc-tabs-ficha__barra">
				<li><a id="tab-a" href="#panel-a">Requisitos</a></li>
				<li><a id="tab-b" href="#panel-b">Plazos</a></li>
				<li><a id="tab-c" href="#panel-c">Documentos</a></li>
			</ul>
			<section id="panel-a">A</section>
			<section id="panel-b">B</section>
			<section id="panel-c">C</section>
		</div>`;

	/**
	 * Mount the tabs and start the page.
	 *
	 * @param {string} html Markup.
	 */
	async function mount( html = TABS ) {
		document.body.innerHTML = html;
		await load();
		ready();
	}

	/**
	 * Which tab is selected and which panels are visible.
	 *
	 * @return {Object} The state of the widget.
	 */
	function state() {
		const tabs = Array.from( document.querySelectorAll( '.prc-tabs-ficha__barra a' ) );
		return {
			selected: tabs.filter( ( tab ) => 'true' === tab.getAttribute( 'aria-selected' ) ).map( ( tab ) => tab.id ),
			tabbable: tabs.filter( ( tab ) => '0' === tab.getAttribute( 'tabindex' ) ).map( ( tab ) => tab.id ),
			visible: Array.from( document.querySelectorAll( 'section' ) )
				.filter( ( panel ) => ! panel.hidden )
				.map( ( panel ) => panel.id ),
		};
	}

	/**
	 * Press a key on a tab.
	 *
	 * @param {string} id  Tab id.
	 * @param {string} key Key name.
	 * @return {KeyboardEvent} The dispatched event.
	 */
	function press( id, key ) {
		const event = new KeyboardEvent( 'keydown', { key, bubbles: true, cancelable: true } );
		document.getElementById( id ).dispatchEvent( event );
		return event;
	}

	it( 'turns the anchor list into a tab list and shows only the first panel', async () => {
		await mount();

		const bar = document.querySelector( '.prc-tabs-ficha__barra' );
		expect( bar.getAttribute( 'role' ) ).toBe( 'tablist' );
		expect( bar.querySelector( 'li' ).getAttribute( 'role' ) ).toBe( 'presentation' );
		const tab = document.getElementById( 'tab-b' );
		expect( tab.getAttribute( 'role' ) ).toBe( 'tab' );
		expect( tab.getAttribute( 'aria-controls' ) ).toBe( 'panel-b' );
		const panel = document.getElementById( 'panel-b' );
		expect( panel.getAttribute( 'role' ) ).toBe( 'tabpanel' );
		expect( panel.getAttribute( 'aria-labelledby' ) ).toBe( 'tab-b' );
		expect( panel.getAttribute( 'tabindex' ) ).toBe( '0' );
		expect( document.querySelector( '.prc-tabs-ficha' ).classList.contains( 'prc-tabs-ficha--js' ) ).toBe( true );
		expect( state() ).toEqual( { selected: [ 'tab-a' ], tabbable: [ 'tab-a' ], visible: [ 'panel-a' ] } );
	} );

	it( 'opens the panel a link points at', async () => {
		window.history.replaceState( null, '', '#panel-c' );
		await mount();

		expect( state().visible ).toEqual( [ 'panel-c' ] );
	} );

	it( 'switches panel on click without following the anchor', async () => {
		await mount();

		const click = new MouseEvent( 'click', { bubbles: true, cancelable: true } );
		document.getElementById( 'tab-b' ).dispatchEvent( click );

		expect( click.defaultPrevented ).toBe( true );
		expect( state() ).toEqual( { selected: [ 'tab-b' ], tabbable: [ 'tab-b' ], visible: [ 'panel-b' ] } );
		expect( document.activeElement.id ).toBe( 'tab-b' );
	} );

	it( 'moves with the arrows, wrapping around, and jumps with Home and End', async () => {
		await mount();

		press( 'tab-a', 'ArrowLeft' );
		expect( state().selected ).toEqual( [ 'tab-c' ] );
		press( 'tab-c', 'ArrowRight' );
		expect( state().selected ).toEqual( [ 'tab-a' ] );
		press( 'tab-a', 'End' );
		expect( state().selected ).toEqual( [ 'tab-c' ] );
		press( 'tab-c', 'Home' );
		expect( state().selected ).toEqual( [ 'tab-a' ] );
		expect( document.activeElement.id ).toBe( 'tab-a' );
	} );

	it( 'leaves other keys to the browser', async () => {
		await mount();

		expect( press( 'tab-a', 'Tab' ).defaultPrevented ).toBe( false );
		expect( state().selected ).toEqual( [ 'tab-a' ] );
	} );

	it.each( [
		[ 'a tab points at a missing panel', TABS.replace( '#panel-c', '#nada' ) ],
		[ 'the bar has no tabs', '<div class="prc-tabs-ficha"><ul class="prc-tabs-ficha__barra"></ul></div>' ],
		[ 'there is no bar', '<div class="prc-tabs-ficha"><section id="panel-a">A</section></div>' ],
	] )( 'keeps the plain anchors when %s', async ( _label, html ) => {
		await mount( html );

		expect( document.querySelector( '.prc-tabs-ficha' ).classList.contains( 'prc-tabs-ficha--js' ) ).toBe( false );
		expect( document.querySelector( '[role]' ) ).toBeNull();
		expect( Array.from( document.querySelectorAll( 'section' ) ).every( ( panel ) => ! panel.hidden ) ).toBe( true );
	} );
} );
