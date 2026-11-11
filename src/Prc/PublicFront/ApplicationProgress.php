<?php
/**
 * How far an application has got: its steps, what it lacks and its receipt.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

use Prc\Domain\ApplicationCompleteness;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;

/**
 * La solicitud se termina y se demuestra.
 *
 * Lee y decide; no pinta nada, que es de {@see View\ApplicationProgressView}.
 * Va aparte de {@see ApplyForm}, como la lista de personas y la retirada,
 * porque su modelo se arma **después** del de las personas: los pasos, el
 * aviso de vuelta y la línea «Completa» o «Incompleta» dependen de si la
 * lista llega al mínimo.
 *
 * Lo que falta es lo mismo que ve quien revisa
 * ({@see ApplicationCompleteness::reasons()}): no hay dos reglas de
 * «incompleta», una para el centro y otra para el taller.
 *
 * El justificante es la misma pantalla con `justificante=1`: solo lectura,
 * sin un formulario y sin JavaScript, que se imprime o se guarda en PDF con
 * el navegador. No abre nada que la pantalla no enseñe ya: sale solo cuando
 * el modelo encontró la solicitud de quien mira.
 */
final class ApplicationProgress {

	/**
	 * Query arg that asks for the printable receipt.
	 */
	public const ARG_RECEIPT = 'justificante';

	/**
	 * Anchor of the application form, or of what was submitted.
	 */
	public const ANCHOR_FORM = 'prc-solicitud-datos';

	/**
	 * Anchor of the deliveries with their own deadline.
	 */
	public const ANCHOR_DELIVERIES = 'prc-entregas';

	/**
	 * The notices of the application screen this one rewrites, with their verb.
	 *
	 * @var array<string, string>
	 */
	private const VERB = array(
		'presentada' => 'presentada',
		'guardada'   => 'guardada',
	);

	/**
	 * What the screen paints about how far the application has got.
	 *
	 * @param array<string, mixed> $m What ApplyForm::model() returned, with `people`.
	 * @return array<string, mixed>
	 */
	public static function model( array $m ): array {
		$app      = (array) ( $m['application'] ?? array() );
		$id       = (int) ( $app['id'] ?? 0 );
		$people   = (array) ( $m['people'] ?? array() );
		$excluida = ApplicationMetaKeys::REVIEW_EXCLUDED === (string) ( $app['state'] ?? '' );
		$falta    = $id > 0 ? self::missing( $id ) : array();
		$autor    = $id > 0 ? get_userdata( (int) get_post_field( 'post_author', $id ) ) : false;
		$out      = array(
			'steps'          => self::steps( $m, $people ),
			'application'    => $id,
			'missing'        => $falta,
			'show_complete'  => $id > 0 && ! $excluida,
			'people_missing' => $id > 0 && ! empty( $people['show'] ) && empty( $people['complete'] ),
			'people_min'     => (int) ( $people['min'] ?? 0 ),
			'people_until'   => (string) ( $people['until'] ?? '' ),
			'people_open'    => ! empty( $people['can_edit'] ),
			'people_why'     => (string) ( $people['why'] ?? '' ),
			'submitted'      => $id > 0 ? self::when( (int) get_post_timestamp( $id ) ) : '',
			'modified'       => $id > 0 ? self::when( (int) get_post_timestamp( $id, 'modified' ) ) : '',
			'author'         => false !== $autor ? (string) $autor->display_name : '',
			'receipt_url'    => $id > 0 ? ApplyForm::url_for_code( (int) $m['procedure_id'], (string) ( $m['centre']['code'] ?? '' ), array( self::ARG_RECEIPT => '1' ) ) : '',
			'apply_url'      => $id > 0 ? ApplyForm::url_for_code( (int) $m['procedure_id'], (string) ( $m['centre']['code'] ?? '' ) ) : '',
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo elige pintar en solo lectura lo que la pantalla ya enseña.
			'receipt'        => $id > 0 && '1' === sanitize_key( wp_unslash( (string) ( $_GET[ self::ARG_RECEIPT ] ?? '' ) ) ),
		);
		$out['flash'] = self::flash( (array) ( $m['flash'] ?? array() ), $out );
		return $out;
	}

	/**
	 * What one stored application lacks, in words: what whoever reviews sees.
	 *
	 * @param int $application_id Application ID.
	 * @return string[] Empty when nothing is missing.
	 */
	public static function missing( int $application_id ): array {
		$procedure_id = (int) wp_get_post_parent_id( $application_id );
		return ApplicationCompleteness::reasons(
			People::enabled( $procedure_id ) ? People::spec( $procedure_id ) : array( 'enabled' => false ),
			get_post_meta( $application_id, ApplicationMetaKeys::PEOPLE, true ),
			Applications::questions( $procedure_id ),
			get_post_meta( $application_id, ApplicationMetaKeys::FILES, true ),
			ProcedureState::today(),
			(string) get_post_meta( $application_id, ApplicationMetaKeys::REVIEW_STATE, true ),
			ApplicationOperations::pending_for_school( $application_id )
		);
	}

	/**
	 * The steps of the screen, each «done» or not; none when there is only one.
	 *
	 * Los datos, las personas si el procedimiento pide la lista y los
	 * documentos con plazo propio si los tiene. Un documento opcional no
	 * hace que falte nada. Sin solicitud y sin poder presentarla, no hay
	 * camino que enseñar.
	 *
	 * @param array<string, mixed> $m      Model of the screen.
	 * @param array<string, mixed> $people Model of the list of people.
	 * @return array<int, array{label:string, anchor:string, done:bool}>
	 */
	private static function steps( array $m, array $people ): array {
		$app = (array) ( $m['application'] ?? array() );
		$hay = array() !== $app;
		if ( ! $hay && empty( $m['can_submit'] ) ) {
			return array();
		}
		$pasos = array(
			array(
				'label'  => 'Datos de la solicitud',
				'anchor' => self::ANCHOR_FORM,
				'done'   => $hay,
			),
		);
		if ( ! empty( $people['show'] ) ) {
			$pasos[] = array(
				'label'  => 'Personas que participan',
				'anchor' => PeopleForm::ANCHOR,
				'done'   => $hay && ! empty( $people['complete'] ),
			);
		}
		$entregas = (array) ( $m['deliveries'] ?? array() );
		if ( array() !== $entregas ) {
			$ficheros = (array) ( $app['files'] ?? array() );
			$hechas   = true;
			foreach ( $entregas as $pregunta ) {
				$hechas = $hechas && ( empty( $pregunta['required'] ) || isset( $ficheros[ (string) $pregunta['key'] ] ) );
			}
			$pasos[] = array(
				'label'  => 'Documentos con plazo propio',
				'anchor' => self::ANCHOR_DELIVERIES,
				'done'   => $hay && $hechas,
			);
		}
		return count( $pasos ) > 1 ? $pasos : array();
	}

	/**
	 * The notice after submitting or saving: green only when nothing is missing.
	 *
	 * @param array<string, string> $flash What the application screen would say.
	 * @param array<string, mixed>  $p     What model() worked out.
	 * @return array{tipo:string, texto:string, link:string, link_text:string}
	 */
	private static function flash( array $flash, array $p ): array {
		$flash += array(
			'tipo'  => '',
			'texto' => '',
		);
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- solo elige qué aviso fijo se pinta.
		$hecho = sanitize_key( wp_unslash( (string) ( $_GET[ ApplyForm::ARG_NOTICE ] ?? '' ) ) );
		$out   = array(
			'tipo'      => (string) $flash['tipo'],
			'texto'     => (string) $flash['texto'],
			'link'      => '',
			'link_text' => '',
		);
		if ( 'ok' !== $out['tipo'] || ! isset( self::VERB[ $hecho ] ) || ! $p['show_complete'] ) {
			return $out;
		}
		$verbo = 'Solicitud ' . self::VERB[ $hecho ];
		if ( array() === (array) $p['missing'] ) {
			$out['texto'] = ( 'presentada' === $hecho ? $verbo . ' el ' . (string) $p['submitted'] : $verbo )
				. '. Está completa: no tiene que hacer nada más. Puede volver a esta pantalla para consultarla, y editarla mientras el plazo siga abierto.';
			return $out;
		}
		$out['tipo'] = 'aviso';
		if ( $p['people_missing'] ) {
			$falta = $verbo . ', pero todavía no está completa: falta dar de alta a las personas que participan (el procedimiento pide al menos ' . (int) $p['people_min'] . ').';
			// Con la lista cerrada para esta solicitud —solo admitidas, una
			// ventana que todavía no se abre— no se manda a darla de alta: se
			// dice por qué todavía no, lo mismo que la sección de abajo.
			if ( ! $p['people_open'] ) {
				$out['texto'] = $falta . ( '' !== (string) $p['people_why'] ? ' ' . (string) $p['people_why'] : '' );
				return $out;
			}
			$hasta            = (string) $p['people_until'];
			$out['texto']     = $falta . ' Hágalo abajo, en «Personas que participan»'
				. ( '' !== $hasta ? ', hasta el ' . $hasta . ', incluido.' : '.' );
			$out['link']      = '#' . PeopleForm::ANCHOR;
			$out['link_text'] = 'Ir a la lista de personas';
			return $out;
		}
		$out['texto'] = $verbo . ', pero todavía no está completa. Le falta: ' . lcfirst( implode( '; ', (array) $p['missing'] ) ) . '.';
		return $out;
	}

	/**
	 * A moment as the screen and the mail say it: «10-11-2026 a las 12:34».
	 *
	 * @param int $timestamp Unix time.
	 * @return string
	 */
	public static function when( int $timestamp ): string {
		return $timestamp > 0 ? (string) wp_date( 'd-m-Y', $timestamp ) . ' a las ' . (string) wp_date( 'H:i', $timestamp ) : '';
	}
}
