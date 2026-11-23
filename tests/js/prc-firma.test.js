/**
 * Unit tests for assets/js/prc-firma.js: the receipt PDF with jsPDF and the
 * signing with AutoFirma (ADR-0049). AutoScript is a stand-in: the real
 * AutoFirma cannot run here.
 */
import { afterEach, describe, expect, it, vi } from 'vitest';
import { jsPDF } from 'jspdf';

const RECEIPT = {
	number: '57',
	procedure: 'Red de prueba',
	centre: 'CEIP Uno (C0001)',
	submitted: '10-11-2026 a las 12:34',
	coordination: 'Ana Perez (DNI o NIE 12345678Z)',
	fingerprint: 'a'.repeat( 64 ),
	created: "D:20261110113400+00'00'",
	file_id: 'b'.repeat( 32 ),
	filename: 'justificante-solicitud-57.pdf',
};

/**
 * The section as the server paints it.
 *
 * @param {Object} servlets Intermediate server.
 * @return {string} HTML.
 */
function section( servlets = { storage: '', retrieve: '' } ) {
	const conf = JSON.stringify( { receipt: RECEIPT, servlets } ).replace( /"/g, '&quot;' );
	return `<section id="prc-firma" data-prc-firma="${ conf }">
		<p data-prc-firma-botones hidden>
			<button type="button" data-prc-firmar hidden>Firmar justificante con AutoFirma</button>
			<button type="button" data-prc-firma-pdf>Descargar justificante en PDF</button>
		</p>
		<p data-prc-firma-estado></p>
		<form data-prc-firma-form method="post"><input type="hidden" name="prc_signed_b64" value="" data-prc-firma-resultado /></form>
	</section>`;
}

/**
 * Paint, then load the script once the libraries are in place.
 *
 * @param {string} html Markup.
 * @return {Promise<void>}
 */
async function load( html ) {
	document.body.innerHTML = html;
	vi.resetModules();
	await import( '../../assets/js/prc-firma.js' );
}

const $ = ( selector ) => document.querySelector( selector );

afterEach( () => {
	delete window.jspdf;
	delete window.AutoScript;
	vi.unstubAllGlobals();
} );

describe( 'receipt PDF', () => {
	it( 'is the same bytes every time, with the number and the fingerprint in clear text', async () => {
		const docs = [];
		window.jspdf = {
			jsPDF: function ( options ) {
				const doc = new jsPDF( options );
				doc.save = vi.fn();
				docs.push( doc );
				return doc;
			},
		};
		await load( section() );
		$( '[data-prc-firma-pdf]' ).click();
		$( '[data-prc-firma-pdf]' ).click();
		expect( docs ).toHaveLength( 2 );
		expect( docs[ 0 ].save ).toHaveBeenCalledWith( RECEIPT.filename );
		const [ uno, otro ] = docs.map( ( doc ) => doc.output() );
		expect( uno ).toBe( otro );
		expect( uno ).toContain( '(Solicitud: 57) Tj' );
		expect( uno ).toContain( '(Huella SHA-256: ' + RECEIPT.fingerprint + ') Tj' );
		expect( uno ).toContain( "/CreationDate (D:20261110113400+00'00')" );
		expect( uno ).toContain( '/ID [ <' + RECEIPT.file_id.toUpperCase() + '>' );
	} );

	it( 'carries the coordination with its ID only when there is one', async () => {
		const docs = [];
		window.jspdf = {
			jsPDF: function ( options ) {
				const doc = new jsPDF( options );
				doc.save = vi.fn();
				docs.push( doc );
				return doc;
			},
		};
		await load( section() );
		$( '[data-prc-firma-pdf]' ).click();
		expect( docs[ 0 ].output() ).toContain( '(Ana Perez \\(DNI o NIE 12345678Z\\)) Tj' );

		const original = RECEIPT.coordination;
		RECEIPT.coordination = '';
		try {
			await load( section() );
			$( '[data-prc-firma-pdf]' ).click();
		} finally {
			RECEIPT.coordination = original;
		}
		expect( docs[ 1 ].output() ).not.toContain( 'DNI o NIE' );
		expect( docs[ 1 ].output() ).not.toContain( 'Coordinaci' );
	} );

	it( 'keeps a long coordination off the signature box, on more pages', async () => {
		const docs = [];
		window.jspdf = {
			jsPDF: function ( options ) {
				const doc = new jsPDF( options );
				doc.save = vi.fn();
				docs.push( doc );
				return doc;
			},
		};
		const original = RECEIPT.coordination;
		RECEIPT.coordination = Array.from( { length: 80 }, ( _, i ) => 'Persona ' + i + ' (DNI o NIE 12345678Z)' ).join( '; ' );
		try {
			await load( section() );
			$( '[data-prc-firma-pdf]' ).click();
		} finally {
			RECEIPT.coordination = original;
		}
		expect( docs[ 0 ].getNumberOfPages() ).toBeGreaterThan( 1 );
		expect( docs[ 0 ].output() ).toContain( 'Huella SHA-256: ' + RECEIPT.fingerprint );
	} );

	it( 'leaves the buttons hidden without jsPDF', async () => {
		await load( section() );
		expect( $( '[data-prc-firma-botones]' ).hidden ).toBe( true );
	} );
} );

describe( 'AutoFirma', () => {
	/**
	 * A stand-in for AutoScript that answers with what it is given.
	 *
	 * @param {Function} answer Called with the success and error callbacks.
	 * @return {Object} The stand-in.
	 */
	function autoscript( answer ) {
		return {
			sign: vi.fn( ( data, algorithm, format, params, ok, ko ) => answer( ok, ko ) ),
			setServlets: vi.fn(),
			cargarAppAfirma: vi.fn(),
		};
	}

	it( 'signs PAdES with a new revision and sends what comes back by the upload form', async () => {
		window.jspdf = { jsPDF };
		window.AutoScript = autoscript( ( ok ) => ok( 'RklSTUFETw==' ) );
		const submit = vi.spyOn( HTMLFormElement.prototype, 'submit' ).mockImplementation( () => {} );
		await load( section() );
		const boton = $( '[data-prc-firmar]' );
		expect( boton.hidden ).toBe( false );
		boton.click();
		await vi.waitFor( () => expect( submit ).toHaveBeenCalled() );
		const [ data, algorithm, format, params ] = window.AutoScript.sign.mock.calls[ 0 ];
		expect( atob( data ) ).toContain( '(Solicitud: 57) Tj' );
		expect( algorithm ).toBe( 'SHA256withRSA' );
		expect( format ).toBe( 'PAdES' );
		expect( params ).toContain( 'alwaysCreateRevision=true' );
		expect( $( '[data-prc-firma-resultado]' ).value ).toBe( 'RklSTUFETw==' );
		expect( window.AutoScript.setServlets ).not.toHaveBeenCalled();
	} );

	it( 'says why and lets one try again when AutoFirma fails', async () => {
		window.jspdf = { jsPDF };
		window.AutoScript = autoscript( ( ok, ko ) => ko( 'java.lang.Exception', 'Operación cancelada' ) );
		const submit = vi.spyOn( HTMLFormElement.prototype, 'submit' ).mockImplementation( () => {} );
		await load( section() );
		$( '[data-prc-firmar]' ).click();
		await vi.waitFor( () => expect( $( '[data-prc-firma-estado]' ).textContent ).toContain( 'Operación cancelada' ) );
		expect( $( '[data-prc-firmar]' ).disabled ).toBe( false );
		expect( submit ).not.toHaveBeenCalled();
	} );

	it( 'is not offered on a phone without an intermediate server', async () => {
		window.jspdf = { jsPDF };
		window.AutoScript = autoscript( () => {} );
		vi.stubGlobal( 'navigator', { userAgent: 'Mozilla/5.0 (iPhone)' } );
		await load( section() );
		expect( $( '[data-prc-firmar]' ).hidden ).toBe( true );
		expect( $( '[data-prc-firma-botones]' ).hidden ).toBe( false, 'la descarga sí' );
	} );

	it( 'uses the intermediate server when there is one', async () => {
		window.jspdf = { jsPDF };
		window.AutoScript = autoscript( ( ok ) => ok( 'eA==' ) );
		vi.spyOn( HTMLFormElement.prototype, 'submit' ).mockImplementation( () => {} );
		vi.stubGlobal( 'navigator', { userAgent: 'Mozilla/5.0 (Android)' } );
		await load( section( { storage: 'https://i.example/storage', retrieve: 'https://i.example/retrieve' } ) );
		$( '[data-prc-firmar]' ).click();
		await vi.waitFor( () => expect( window.AutoScript.setServlets ).toHaveBeenCalledWith( 'https://i.example/storage', 'https://i.example/retrieve' ) );
	} );
} );
