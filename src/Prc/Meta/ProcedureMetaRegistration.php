<?php
/**
 * Formal registration of prc_procedure post meta (types, sanitisation, auth).
 *
 * @package Prc
 */

namespace Prc\Meta;

use Prc\Access\ProcedureAccess;
use Prc\Domain\ProcedureInput;
use Prc\Domain\ProcedureQuestions;
use Prc\PostType\ProcedurePostType;

/**
 * Registers every procedure meta key with register_post_meta().
 *
 * Los sanitize callbacks corren en cada update_post_meta() (sanitize_meta),
 * así que da igual por dónde entre el dato —formulario, escritorio, WP-CLI—:
 * siempre se normaliza en el mismo sitio.
 */
final class ProcedureMetaRegistration {

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'register_meta' ), 12 );
	}

	/**
	 * Every meta key with its storage type, its sanitiser and, when it needs
	 * one of its own, its authorisation callback.
	 *
	 * Sin `auth` la clave la escribe quien pueda editar el procedimiento
	 * ({@see auth_edit_procedure()}); la marca de histórico pide su regla.
	 *
	 * @return array<string, array{type:string, sanitize:callable, auth?:callable}>
	 */
	public static function schema(): array {
		return array(
			ProcedureMetaKeys::OPENS_AT             => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_date' ),
			),
			ProcedureMetaKeys::CLOSES_AT            => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_date' ),
			),
			ProcedureMetaKeys::AMEND_OPENS_AT       => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_date' ),
			),
			ProcedureMetaKeys::AMEND_CLOSES_AT      => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_date' ),
			),
			ProcedureMetaKeys::RESOLUTION_URL       => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_url' ),
			),
			ProcedureMetaKeys::PROVISIONAL_LIST_URL => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_url' ),
			),
			ProcedureMetaKeys::FINAL_LIST_URL       => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_url' ),
			),
			ProcedureMetaKeys::CONTACT_EMAILS       => array(
				'type'     => 'array',
				'sanitize' => array( self::class, 'sanitize_emails' ),
			),
			ProcedureMetaKeys::HEADER_COLOR         => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_header_color' ),
			),
			ProcedureMetaKeys::AUDIENCE             => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_audience' ),
			),
			ProcedureMetaKeys::OWNERSHIP            => array(
				'type'     => 'array',
				'sanitize' => array( self::class, 'sanitize_ownership' ),
			),
			ProcedureMetaKeys::REQUIRES_COORDINATOR => array(
				'type'     => 'boolean',
				'sanitize' => array( self::class, 'sanitize_bool' ),
			),
			ProcedureMetaKeys::QUESTIONS            => array(
				'type'     => 'array',
				'sanitize' => array( self::class, 'sanitize_questions' ),
			),
			ProcedureMetaKeys::COMMITMENTS          => array(
				'type'     => 'string',
				'sanitize' => 'sanitize_textarea_field',
			),
			ProcedureMetaKeys::ARCHIVED             => array(
				'type'     => 'boolean',
				'sanitize' => array( self::class, 'sanitize_bool' ),
				'auth'     => array( self::class, 'auth_archived' ),
			),
		);
	}

	/**
	 * Register all procedure meta keys.
	 *
	 * @return void
	 */
	public static function register_meta(): void {
		foreach ( self::schema() as $key => $spec ) {
			register_post_meta(
				ProcedurePostType::POST_TYPE,
				$key,
				array(
					'type'              => $spec['type'],
					'single'            => true,
					'default'           => self::defaults()[ $spec['type'] ],
					'sanitize_callback' => $spec['sanitize'],
					'auth_callback'     => $spec['auth'] ?? array( self::class, 'auth_edit_procedure' ),
					'show_in_rest'      => false,
				)
			);
		}
	}

	/**
	 * The empty value of each storage type.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'string'  => '',
			'boolean' => false,
			'integer' => 0,
			'array'   => array(),
		);
	}

	/**
	 * Only users who may edit the procedure can write its meta directly.
	 *
	 * @param bool   $allowed  Current permission.
	 * @param string $meta_key Meta key.
	 * @param int    $post_id  Post ID.
	 * @param int    $user_id  User ID.
	 * @return bool
	 */
	public static function auth_edit_procedure( bool $allowed, string $meta_key, int $post_id, int $user_id ): bool {
		unset( $allowed, $meta_key );
		return user_can( $user_id, 'edit_post', $post_id );
	}

	/**
	 * The «histórico» mark follows the same asymmetry by every route.
	 *
	 * Con el procedimiento abierto la marca la pone su ámbito; cerrado, solo
	 * la quita administración (ADR-0023). Sin esto, un ámbito que ya no puede
	 * editar se lo desmarcaría con un `update_post_meta()` desde otro sitio.
	 *
	 * @param bool   $allowed  Current permission.
	 * @param string $meta_key Meta key.
	 * @param int    $post_id  Post ID.
	 * @param int    $user_id  User ID.
	 * @return bool
	 */
	public static function auth_archived( bool $allowed, string $meta_key, int $post_id, int $user_id ): bool {
		unset( $allowed, $meta_key );
		return ProcedureAccess::can_toggle_archived( $user_id, $post_id );
	}

	/**
	 * Keep only a valid Y-m-d calendar date.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_date( $value ): string {
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
			return '';
		}
		$parts = array_map( 'intval', explode( '-', $value ) );
		return checkdate( $parts[1], $parts[2], $parts[0] ) ? $value : '';
	}

	/**
	 * An http(s) address, or nothing.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_url( $value ): string {
		$url = esc_url_raw( is_scalar( $value ) ? trim( (string) $value ) : '', array( 'http', 'https' ) );
		return is_string( $url ) ? $url : '';
	}

	/**
	 * An email address, or nothing.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_email( $value ): string {
		$email = sanitize_email( is_scalar( $value ) ? (string) $value : '' );
		return is_string( $email ) ? strtolower( $email ) : '';
	}

	/**
	 * Up to three addresses, each one an address or nothing.
	 *
	 * @param mixed $value Raw value: a list, or one address.
	 * @return string[]
	 */
	public static function sanitize_emails( $value ): array {
		$crudos = is_array( $value ) ? $value : array( $value );
		$out    = array();
		foreach ( $crudos as $crudo ) {
			$email = self::sanitize_email( $crudo );
			if ( '' !== $email && ! in_array( $email, $out, true ) ) {
				$out[] = $email;
			}
		}
		return array_slice( $out, 0, ProcedureMetaKeys::CONTACT_EMAILS_MAX );
	}

	/**
	 * One of the palette colours; the neutral one when it is not one of ours.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_header_color( $value ): string {
		return ProcedureMetaKeys::in_list( $value, ProcedureMetaKeys::header_colors(), ProcedureMetaKeys::HEADER_COLOR_NEUTRAL );
	}

	/**
	 * One of the audiences; schools when it is not one of ours.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_audience( $value ): string {
		return ProcedureMetaKeys::in_list( $value, ProcedureMetaKeys::audiences(), ProcedureMetaKeys::AUDIENCE_SCHOOLS );
	}

	/**
	 * The ownerships of the closed list, and none other.
	 *
	 * @param mixed $value Raw value.
	 * @return string[]
	 */
	public static function sanitize_ownership( $value ): array {
		return ProcedureInput::ownership( $value );
	}

	/**
	 * A checkbox: stored as a real boolean.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	public static function sanitize_bool( $value ): bool {
		if ( is_string( $value ) ) {
			return in_array( strtolower( trim( $value ) ), array( '1', 'on', 'true', 'si', 'sí', 'yes' ), true );
		}
		return (bool) $value;
	}

	/**
	 * Store the question list normalised, with its texts cleaned.
	 *
	 * @param mixed $value Raw value.
	 * @return array<int, array<string, mixed>>
	 */
	public static function sanitize_questions( $value ): array {
		$preguntas = ProcedureQuestions::sanitize( $value );
		foreach ( $preguntas as $i => $pregunta ) {
			$preguntas[ $i ]['label']   = sanitize_text_field( (string) $pregunta['label'] );
			$preguntas[ $i ]['help']    = sanitize_text_field( (string) $pregunta['help'] );
			$preguntas[ $i ]['choices'] = array_map( 'sanitize_text_field', (array) $pregunta['choices'] );
		}
		return $preguntas;
	}
}
