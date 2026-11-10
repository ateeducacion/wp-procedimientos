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
			ApplicationMetaKeys::APPLICANT_TYPE     => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_applicant_type' ),
			),
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
	 * One supported applicant type; old records default to a centre.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_applicant_type( $value ): string {
		return ApplicationMetaKeys::TYPE_TEACHER === $value ? ApplicationMetaKeys::TYPE_TEACHER : ApplicationMetaKeys::TYPE_CENTRE;
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
	 * Coordinators as a list of `{name, surname, dni, email}`; a saved single coordinator is upgraded on read.
	 *
	 * Los apellidos llegaron después que el nombre (ADR-0039): lo guardado
	 * antes los lee vacíos y el nombre se queda entero, como se tecleó. Solo
	 * el fichero de alta de usuarios lo parte, al exportar.
	 *
	 * @param mixed $value Raw value.
	 * @return array<int, array{name:string, surname:string, dni:string, email:string}>
	 */
	public static function sanitize_coordinator( $value ): array {
		$value = is_array( $value ) ? $value : array();
		if ( isset( $value['name'] ) || isset( $value['email'] ) ) {
			$value = array( $value );
		}
		$out = array();
		foreach ( array_slice( $value, 0, ProcedureMetaKeys::COORDINATOR_COUNT_MAX ) as $coordinator ) {
			if ( ! is_array( $coordinator ) ) {
				continue;
			}
			$email = sanitize_email( isset( $coordinator['email'] ) && is_scalar( $coordinator['email'] ) ? (string) $coordinator['email'] : '' );
			$out[] = array(
				'name'    => self::text( $coordinator, 'name' ),
				'surname' => self::text( $coordinator, 'surname' ),
				'dni'     => strtoupper( (string) preg_replace( '/[ -]/', '', self::text( $coordinator, 'dni' ) ) ),
				'email'   => ProcedureInput::is_email( $email ) ? strtolower( $email ) : '',
			);
		}
		return $out;
	}

	/**
	 * One field of a stored person, as plain text.
	 *
	 * @param array<string, mixed> $person Stored person.
	 * @param string               $key    Field.
	 * @return string
	 */
	private static function text( array $person, string $key ): string {
		return sanitize_text_field( isset( $person[ $key ] ) && is_scalar( $person[ $key ] ) ? (string) $person[ $key ] : '' );
	}

	/**
	 * Name and surname of a coordinator, as one reads them.
	 *
	 * @param array<string, mixed> $coordinator One stored coordinator.
	 * @return string
	 */
	public static function full_name( array $coordinator ): string {
		return trim( (string) ( $coordinator['name'] ?? '' ) . ' ' . (string) ( $coordinator['surname'] ?? '' ) );
	}

	/**
	 * Every coordinator's full name, joined for one cell.
	 *
	 * @param array<int, array<string, mixed>> $coordinators Stored coordinators.
	 * @return string
	 */
	public static function full_names( array $coordinators ): string {
		return implode( ', ', array_filter( array_map( array( self::class, 'full_name' ), $coordinators ) ) );
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
