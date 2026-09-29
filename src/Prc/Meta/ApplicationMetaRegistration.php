<?php
/**
 * Register the meta keys of an application.
 *
 * @package Prc
 */

namespace Prc\Meta;

use Prc\Domain\ProcedureInput;
use Prc\Domain\ProcedureQuestions;
use Prc\PostType\ApplicationPostType;

/**
 * Lo mismo que {@see ProcedureMetaRegistration}, para la solicitud.
 *
 * La diferencia está en el `auth_callback`, y no es un detalle: **devuelve
 * `false` siempre**. Una solicitud no la edita nadie a mano desde el
 * escritorio ni por la REST: la escribe el aplicativo cuando un centro
 * solicita y cuando quien gestiona la revisa (ADR-0018).
 */
final class ApplicationMetaRegistration {

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'register_meta' ), 12 );
	}

	/**
	 * Application meta keys with their type and sanitiser.
	 *
	 * @return array<string, array{type:string, sanitize:callable}>
	 */
	public static function schema(): array {
		return array(
			ApplicationMetaKeys::CENTRE_CODE        => array(
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field',
			),
			ApplicationMetaKeys::CENTRE_NAME        => array(
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field',
			),
			ApplicationMetaKeys::APPLICANT_POSITION => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_position' ),
			),
			ApplicationMetaKeys::COORDINATOR        => array(
				'type'     => 'array',
				'sanitize' => array( self::class, 'sanitize_coordinator' ),
			),
			ApplicationMetaKeys::ANSWERS            => array(
				'type'     => 'array',
				'sanitize' => array( self::class, 'sanitize_answers' ),
			),
			ApplicationMetaKeys::FILES              => array(
				'type'     => 'array',
				'sanitize' => array( self::class, 'sanitize_files' ),
			),
			ApplicationMetaKeys::REVIEW_STATE       => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_review_state' ),
			),
			ApplicationMetaKeys::REVIEW_NOTE        => array(
				'type'     => 'string',
				'sanitize' => 'sanitize_textarea_field',
			),
		);
	}

	/**
	 * Register every application meta key.
	 *
	 * @return void
	 */
	public static function register_meta(): void {
		foreach ( self::schema() as $key => $spec ) {
			register_post_meta(
				ApplicationPostType::POST_TYPE,
				$key,
				array(
					'type'              => $spec['type'],
					'single'            => true,
					'default'           => ProcedureMetaRegistration::defaults()[ $spec['type'] ],
					'sanitize_callback' => $spec['sanitize'],
					'auth_callback'     => '__return_false',
					'show_in_rest'      => false,
				)
			);
		}
	}

	/**
	 * One of the positions; «other» when it is not one of ours.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_position( $value ): string {
		return ProcedureMetaKeys::in_list( $value, ApplicationMetaKeys::positions(), ApplicationMetaKeys::POSITION_OTHER );
	}

	/**
	 * One of the review states; «submitted» when it is not one of ours.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_review_state( $value ): string {
		return ProcedureMetaKeys::in_list( $value, ApplicationMetaKeys::review_states(), ApplicationMetaKeys::REVIEW_SUBMITTED );
	}

	/**
	 * The coordinator, as `{name, email}` and nothing else.
	 *
	 * @param mixed $value Raw value.
	 * @return array{name:string, email:string}
	 */
	public static function sanitize_coordinator( $value ): array {
		$value = is_array( $value ) ? $value : array();
		$email = sanitize_email( isset( $value['email'] ) && is_scalar( $value['email'] ) ? (string) $value['email'] : '' );
		return array(
			'name'  => sanitize_text_field( isset( $value['name'] ) && is_scalar( $value['name'] ) ? (string) $value['name'] : '' ),
			'email' => ProcedureInput::is_email( $email ) ? strtolower( $email ) : '',
		);
	}

	/**
	 * Private file descriptors keyed by question key, and nothing else.
	 *
	 * Lo que entra tiene la forma de un descriptor de
	 * {@see \Prc\PublicFront\ApplicationFiles} o no entra. En particular
	 * `stored` es una **ruta relativa** a la raíz privada, con la forma que
	 * compone el aplicativo: ni ruta absoluta, ni URL, ni identificador de
	 * adjunto (ADR-0028).
	 *
	 * @param mixed $value Raw value.
	 * @return array<string, array<string, mixed>>
	 */
	public static function sanitize_files( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$out = array();
		foreach ( $value as $key => $descriptor ) {
			$key = (string) $key;
			if ( ! ProcedureQuestions::is_key( $key ) || ! is_array( $descriptor ) ) {
				continue;
			}
			$opaco  = isset( $descriptor['id'] ) ? strtolower( (string) $descriptor['id'] ) : '';
			$stored = isset( $descriptor['stored'] ) ? (string) $descriptor['stored'] : '';
			$sha    = isset( $descriptor['sha256'] ) ? strtolower( (string) $descriptor['sha256'] ) : '';
			if ( ! preg_match( '/^[a-f0-9]{32}$/', $opaco )
				|| ! preg_match( '#^[a-f0-9]{2}/[a-f0-9]{2}/[a-f0-9]{32}\.[a-z0-9]{1,8}$#', $stored )
				|| ! preg_match( '/^[a-f0-9]{64}$/', $sha ) ) {
				continue;
			}

			$out[ $key ] = array(
				'id'     => $opaco,
				'name'   => sanitize_file_name( (string) ( $descriptor['name'] ?? '' ) ),
				'mime'   => sanitize_mime_type( (string) ( $descriptor['mime'] ?? '' ) ),
				'size'   => max( 0, (int) ( $descriptor['size'] ?? 0 ) ),
				'sha256' => $sha,
				'stored' => $stored,
			);
		}
		return $out;
	}

	/**
	 * Answers keyed by question key, and nothing that is not an answer.
	 *
	 * @param mixed $value Raw value.
	 * @return array<string, mixed>
	 */
	public static function sanitize_answers( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$out = array();
		foreach ( $value as $key => $respuesta ) {
			$key = (string) $key;
			if ( ! ProcedureQuestions::is_key( $key ) ) {
				continue;
			}
			if ( is_bool( $respuesta ) ) {
				$out[ $key ] = $respuesta;
			} elseif ( is_array( $respuesta ) ) {
				$out[ $key ] = array_values( array_map( 'sanitize_text_field', array_filter( $respuesta, 'is_scalar' ) ) );
			} elseif ( is_scalar( $respuesta ) ) {
				$out[ $key ] = sanitize_textarea_field( (string) $respuesta );
			}
		}
		return $out;
	}
}
