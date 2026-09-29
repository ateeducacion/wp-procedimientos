<?php
/**
 * Snippet Name: PRC — Aplicativo de procedimientos (CPT)
 * Description: CPT prc_procedure (el procedimiento y su convocatoria) y prc_application (la solicitud de un centro), taxonomías prc_area / prc_course, el acotado por ámbito y por centro y las pantallas propias del aplicativo (portada pública, ficha del procedimiento, mis procedimientos, taller del procedimiento, formulario de solicitud y mi centro). Código generado desde src/Prc — no editar a mano; ejecutar php build/pack-snippet.php.
 * Scope: global
 * Priority: 15
 *
 * @package Prc
 * @version 0.1.0
 */

// phpcs:disable








namespace Prc\Meta;





defined( 'ABSPATH' ) || exit;




if ( \defined( 'PRC_BUNDLE_LOADED' ) ) {
	return;
}
\define( 'PRC_BUNDLE_LOADED', true );














final class ProcedureMetaKeys {




	public const OPENS_AT = 'prc_opens_at';




	public const CLOSES_AT = 'prc_closes_at';




	public const AMEND_OPENS_AT = 'prc_amend_opens_at';




	public const AMEND_CLOSES_AT = 'prc_amend_closes_at';




	public const RESOLUTION_URL = 'prc_resolution_url';




	public const PROVISIONAL_LIST_URL = 'prc_provisional_list_url';




	public const FINAL_LIST_URL = 'prc_final_list_url';




	public const CONTACT_EMAILS = 'prc_contact_emails';








	public const CONTACT_EMAIL = 'prc_contact_email';




	public const HEADER_COLOR = 'prc_header_color';




	public const AUDIENCE = 'prc_audience';




	public const OWNERSHIP = 'prc_ownership';




	public const REQUIRES_COORDINATOR = 'prc_requires_coordinator';




	public const QUESTIONS = 'prc_questions';




	public const COMMITMENTS = 'prc_commitments';








	public const ARCHIVED = 'prc_archived';

	public const STATE_DRAFT     = 'draft';
	public const STATE_ARCHIVED  = 'archived';
	public const STATE_RESOLVED  = 'resolved';
	public const STATE_AMENDMENT = 'amendment';
	public const STATE_OPEN      = 'open';
	public const STATE_UPCOMING  = 'upcoming';
	public const STATE_CLOSED    = 'closed';

	public const AUDIENCE_SCHOOLS  = 'schools';
	public const AUDIENCE_TEACHERS = 'teachers';

	public const OWNERSHIP_PUBLIC  = 'public';
	public const OWNERSHIP_PRIVATE = 'private';




	public const HEADER_COLOR_NEUTRAL = 'pizarra';




	public const CONTACT_EMAILS_MAX = 3;






	public static function all(): array {
		return array(
			self::OPENS_AT,
			self::CLOSES_AT,
			self::AMEND_OPENS_AT,
			self::AMEND_CLOSES_AT,
			self::RESOLUTION_URL,
			self::PROVISIONAL_LIST_URL,
			self::FINAL_LIST_URL,
			self::CONTACT_EMAILS,
			self::HEADER_COLOR,
			self::AUDIENCE,
			self::OWNERSHIP,
			self::REQUIRES_COORDINATOR,
			self::QUESTIONS,
			self::COMMITMENTS,
			self::ARCHIVED,
		);
	}









	public static function states(): array {
		return array(
			self::STATE_DRAFT     => 'Borrador',
			self::STATE_ARCHIVED  => 'Histórico',
			self::STATE_RESOLVED  => 'Resuelto',
			self::STATE_AMENDMENT => 'En subsanación',
			self::STATE_OPEN      => 'Abierto',
			self::STATE_UPCOMING  => 'Próximo',
			self::STATE_CLOSED    => 'Cerrado',
		);
	}






	public static function audiences(): array {
		return array(
			self::AUDIENCE_SCHOOLS  => 'Centros educativos',
			self::AUDIENCE_TEACHERS => 'Profesorado',
		);
	}









	public static function ownerships(): array {
		return array(
			self::OWNERSHIP_PUBLIC  => 'Centros públicos',
			self::OWNERSHIP_PRIVATE => 'Centros privados y concertados',
		);
	}



















	public static function header_colors(): array {
		return array(
			self::HEADER_COLOR_NEUTRAL => array(
				'label' => 'Pizarra',
				'hex'   => '#3f4a55',
			),
			'azul'                     => array(
				'label' => 'Azul',
				'hex'   => '#12507e',
			),
			'indigo'                   => array(
				'label' => 'Índigo',
				'hex'   => '#3c3d8f',
			),
			'turquesa'                 => array(
				'label' => 'Turquesa',
				'hex'   => '#0f6168',
			),
			'verde'                    => array(
				'label' => 'Verde',
				'hex'   => '#1f6b3a',
			),
			'oliva'                    => array(
				'label' => 'Oliva',
				'hex'   => '#4d5c16',
			),
			'ocre'                     => array(
				'label' => 'Ocre',
				'hex'   => '#8a5a00',
			),
			'teja'                     => array(
				'label' => 'Teja',
				'hex'   => '#a34118',
			),
			'granate'                  => array(
				'label' => 'Granate',
				'hex'   => '#8c2230',
			),
			'morado'                   => array(
				'label' => 'Morado',
				'hex'   => '#6a2c91',
			),
		);
	}







	public static function header_hex( $slug ): string {
		$colores = self::header_colors();
		$clave   = self::in_list( $slug, $colores, self::HEADER_COLOR_NEUTRAL );
		return (string) $colores[ $clave ]['hex'];
	}









	public static function in_list( $value, array $allowed, string $fallback = '' ): string {
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';
		return isset( $allowed[ $value ] ) ? $value : $fallback;
	}
}








namespace Prc\Meta;












final class ApplicationMetaKeys {




	public const CENTRE_CODE = 'prc_centre_code';




	public const CENTRE_NAME = 'prc_centre_name';




	public const APPLICANT_POSITION = 'prc_applicant_position';




	public const COORDINATOR = 'prc_coordinator';




	public const ANSWERS = 'prc_answers';










	public const FILES = 'prc_files';




	public const REVIEW_STATE = 'prc_review_state';




	public const REVIEW_NOTE = 'prc_review_note';

	public const POSITION_HEAD            = 'head';
	public const POSITION_DEPUTY_HEAD     = 'deputy_head';
	public const POSITION_HEAD_OF_STUDIES = 'head_of_studies';
	public const POSITION_SECRETARY       = 'secretary';
	public const POSITION_OTHER           = 'other';

	public const REVIEW_SUBMITTED = 'submitted';
	public const REVIEW_AMEND     = 'amend';
	public const REVIEW_ADMITTED  = 'admitted';
	public const REVIEW_EXCLUDED  = 'excluded';






	public static function all(): array {
		return array(
			self::CENTRE_CODE,
			self::CENTRE_NAME,
			self::APPLICANT_POSITION,
			self::COORDINATOR,
			self::ANSWERS,
			self::FILES,
			self::REVIEW_STATE,
			self::REVIEW_NOTE,
		);
	}






	public static function positions(): array {
		return array(
			self::POSITION_HEAD            => 'Dirección',
			self::POSITION_DEPUTY_HEAD     => 'Vicedirección',
			self::POSITION_HEAD_OF_STUDIES => 'Jefatura de estudios',
			self::POSITION_SECRETARY       => 'Secretaría',
			self::POSITION_OTHER           => 'Otro',
		);
	}






	public static function review_states(): array {
		return array(
			self::REVIEW_SUBMITTED => 'Presentada',
			self::REVIEW_AMEND     => 'A subsanar',
			self::REVIEW_ADMITTED  => 'Admitida',
			self::REVIEW_EXCLUDED  => 'Excluida',
		);
	}






	public static function states_needing_note(): array {
		return array( self::REVIEW_AMEND, self::REVIEW_EXCLUDED );
	}
}








namespace Prc\Domain;






















final class DateRange {








	public static function of( string $start, string $end = '' ): string {
		$desde = self::parts( $start );
		if ( array() === $desde ) {
			return '';
		}

		$hasta = self::parts( $end );
		if ( array() === $hasta ) {
			return 'Desde el ' . self::day( $desde );
		}



		if ( $hasta['n'] <= $desde['n'] ) {
			return self::day( $desde );
		}
		if ( $hasta['y'] !== $desde['y'] ) {
			return sprintf( 'Del %s al %s', self::day( $desde ), self::day( $hasta ) );
		}
		if ( $hasta['m'] !== $desde['m'] ) {
			return sprintf( 'Del %d de %s al %s', $desde['d'], self::month( $desde['m'] ), self::day( $hasta ) );
		}
		return sprintf( 'Del %d al %s', $desde['d'], self::day( $hasta ) );
	}







	private static function day( array $day ): string {
		return sprintf( '%d de %s de %d', $day['d'], self::month( $day['m'] ), $day['y'] );
	}











	private static function parts( string $ymd ): array {
		if ( 1 !== preg_match( '/^(\d{4})-(\d{1,2})-(\d{1,2})$/', trim( $ymd ), $trozos ) ) {
			return array();
		}

		$anio = (int) $trozos[1];
		$mes  = (int) $trozos[2];
		$dia  = (int) $trozos[3];
		if ( ! checkdate( $mes, $dia, $anio ) ) {
			return array();
		}

		return array(
			'n' => $anio * 10000 + $mes * 100 + $dia,
			'y' => $anio,
			'm' => $mes,
			'd' => $dia,
		);
	}











	private static function month( int $month ): string {
		global $wp_locale;
		return mb_strtolower( (string) $wp_locale->get_month( $month ), 'UTF-8' );
	}
}








namespace Prc\Domain;

use Prc\Meta\ProcedureMetaKeys;












final class ProcedureState {





	public const GROUP_DATA = 'data';




	public const GROUP_DATES = 'dates';




	public const GROUP_LINKS = 'links';




	public const GROUP_QUESTIONS = 'questions';













	public static function of( array $meta, string $today, bool $published ): string {
		if ( ! $published ) {
			return ProcedureMetaKeys::STATE_DRAFT;
		}
		if ( ! empty( $meta[ ProcedureMetaKeys::ARCHIVED ] ) ) {
			return ProcedureMetaKeys::STATE_ARCHIVED;
		}
		if ( '' !== self::text( $meta, ProcedureMetaKeys::FINAL_LIST_URL ) ) {
			return ProcedureMetaKeys::STATE_RESOLVED;
		}

		$today = trim( $today );

		$amend_opens  = self::text( $meta, ProcedureMetaKeys::AMEND_OPENS_AT );
		$amend_closes = self::text( $meta, ProcedureMetaKeys::AMEND_CLOSES_AT );
		if ( '' === $amend_closes ) {
			$amend_closes = $amend_opens;
		}
		if ( '' !== $amend_opens && $amend_opens <= $today && $today <= $amend_closes ) {
			return ProcedureMetaKeys::STATE_AMENDMENT;
		}

		$opens  = self::text( $meta, ProcedureMetaKeys::OPENS_AT );
		$closes = self::text( $meta, ProcedureMetaKeys::CLOSES_AT );
		if ( '' === $closes ) {
			$closes = $opens;
		}


		if ( '' === $opens || $today < $opens ) {
			return ProcedureMetaKeys::STATE_UPCOMING;
		}
		if ( $today <= $closes ) {
			return ProcedureMetaKeys::STATE_OPEN;
		}
		return ProcedureMetaKeys::STATE_CLOSED;
	}








	public static function of_post( int $post_id, string $today = '' ): string {
		$meta = array();
		foreach ( ProcedureMetaKeys::all() as $key ) {
			$meta[ $key ] = get_post_meta( $post_id, $key, true );
		}


		$dia       = '' !== trim( $today ) ? $today : self::today();
		$publicado = 'publish' === get_post_status( $post_id );
		return self::of( $meta, $dia, $publicado );
	}






	public static function today(): string {
		return (string) current_time( 'Y-m-d' );
	}







	public static function label( string $state ): string {
		return (string) ( ProcedureMetaKeys::states()[ $state ] ?? '' );
	}






	public static function groups(): array {
		return array( self::GROUP_DATA, self::GROUP_DATES, self::GROUP_LINKS, self::GROUP_QUESTIONS );
	}



















	public static function editable_fields( string $state ): array {
		$todo  = self::groups();
		$tabla = array(
			ProcedureMetaKeys::STATE_DRAFT    => $todo,
			ProcedureMetaKeys::STATE_UPCOMING => $todo,
			ProcedureMetaKeys::STATE_OPEN     => $todo,
			ProcedureMetaKeys::STATE_CLOSED   => array( self::GROUP_DATES, self::GROUP_LINKS ),
			ProcedureMetaKeys::STATE_RESOLVED => array( self::GROUP_LINKS ),
		);
		return $tabla[ $state ] ?? array();
	}











	public static function why_locked( string $state ): string {
		$textos = array(
			ProcedureMetaKeys::STATE_CLOSED    => 'El plazo de solicitud está cerrado: solo se pueden cambiar las fechas y los enlaces. Las solicitudes se siguen gestionando.',
			ProcedureMetaKeys::STATE_AMENDMENT => 'La subsanación está en marcha: el procedimiento ya no se edita, para no mover el suelo a quien está subsanando. Las solicitudes se siguen gestionando.',
			ProcedureMetaKeys::STATE_RESOLVED  => 'El procedimiento está resuelto: solo se pueden cambiar los enlaces.',
		);
		return (string) ( $textos[ $state ] ?? '' );
	}










	public static function accepts_applications( string $state ): bool {
		return ProcedureMetaKeys::STATE_OPEN === $state;
	}








	private static function text( array $meta, string $key ): string {
		$value = $meta[ $key ] ?? '';
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}
}








namespace Prc\Domain;

















final class ProcedureQuestions {




	public const MAX = 20;




	public const MAX_CHOICES = 50;




	public const TEXT_MAX = 2000;

	public const TYPE_TEXT     = 'text';
	public const TYPE_SINGLE   = 'single';
	public const TYPE_MULTIPLE = 'multiple';
	public const TYPE_YESNO    = 'yesno';











	public const TYPE_FILE = 'file';






	public static function types(): array {
		return array(
			self::TYPE_TEXT     => 'Respuesta libre',
			self::TYPE_SINGLE   => 'Una opción',
			self::TYPE_MULTIPLE => 'Varias opciones',
			self::TYPE_YESNO    => 'Sí o no',
			self::TYPE_FILE     => 'Archivo',
		);
	}







	public static function has_choices( string $type ): bool {
		return self::TYPE_SINGLE === $type || self::TYPE_MULTIPLE === $type;
	}







	public static function is_key( string $key ): bool {
		return (bool) preg_match( '/^q[1-9]\d{0,3}$/', $key );
	}













	public static function sanitize( $raw ): array {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$out    = array();
		$vistas = array();
		$mayor  = 0;
		foreach ( $raw as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$pregunta = self::one( $item );
			if ( '' === $pregunta['label'] || isset( $vistas[ $pregunta['key'] ] ) ) {
				continue;
			}
			if ( '' !== $pregunta['key'] ) {
				$vistas[ $pregunta['key'] ] = true;
				$mayor                      = max( $mayor, (int) substr( $pregunta['key'], 1 ) );
			}
			$out[] = $pregunta;
			if ( count( $out ) >= self::MAX ) {
				break;
			}
		}

		foreach ( $out as $i => $pregunta ) {
			if ( '' === $pregunta['key'] ) {
				++$mayor;
				$out[ $i ]['key'] = 'q' . $mayor;
			}
		}
		return $out;
	}







	private static function one( array $raw ): array {
		$type = isset( $raw['type'] ) && is_scalar( $raw['type'] ) ? (string) $raw['type'] : '';
		if ( ! isset( self::types()[ $type ] ) ) {
			$type = self::TYPE_TEXT;
		}
		$key = isset( $raw['key'] ) && is_scalar( $raw['key'] ) ? strtolower( trim( (string) $raw['key'] ) ) : '';

		return array(
			'key'      => self::is_key( $key ) ? $key : '',
			'label'    => self::text( $raw, 'label' ),
			'help'     => self::text( $raw, 'help' ),
			'type'     => $type,
			'required' => ! empty( $raw['required'] ),
			'choices'  => self::has_choices( $type ) ? self::choices( $raw['choices'] ?? array() ) : array(),
		);
	}







	public static function choices( $raw ): array {
		if ( is_string( $raw ) ) {
			$raw = preg_split( '/\r\n|\r|\n/', $raw );
		}
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$out = array();
		foreach ( $raw as $opcion ) {
			if ( ! is_scalar( $opcion ) ) {
				continue;
			}
			$opcion = trim( (string) $opcion );
			if ( '' === $opcion || in_array( $opcion, $out, true ) ) {
				continue;
			}
			$out[] = $opcion;
			if ( count( $out ) >= self::MAX_CHOICES ) {
				break;
			}
		}
		return $out;
	}











	public static function validate_answers( array $questions, array $answers ): array {
		$errores = array();
		$datos   = array();

		foreach ( $questions as $pregunta ) {
			$key   = (string) ( $pregunta['key'] ?? '' );
			$valor = $answers[ $key ] ?? null;





			if ( self::TYPE_FILE === ( $pregunta['type'] ?? '' ) ) {
				continue;
			}

			switch ( $pregunta['type'] ?? self::TYPE_TEXT ) {
				case self::TYPE_YESNO:
					$datos[ $key ] = self::yes( $valor );
					if ( ! empty( $pregunta['required'] ) && ! $datos[ $key ] ) {
						$errores[] = $key;
					}
					break;

				case self::TYPE_SINGLE:
					$elegida       = is_scalar( $valor ) ? trim( (string) $valor ) : '';
					$datos[ $key ] = in_array( $elegida, (array) ( $pregunta['choices'] ?? array() ), true ) ? $elegida : '';
					if ( ! empty( $pregunta['required'] ) && '' === $datos[ $key ] ) {
						$errores[] = $key;
					}
					break;

				case self::TYPE_MULTIPLE:
					$limpias = array();
					foreach ( is_array( $valor ) ? $valor : array() as $una ) {
						$una = is_scalar( $una ) ? trim( (string) $una ) : '';
						if ( in_array( $una, (array) ( $pregunta['choices'] ?? array() ), true ) && ! in_array( $una, $limpias, true ) ) {
							$limpias[] = $una;
						}
					}
					$datos[ $key ] = $limpias;
					if ( ! empty( $pregunta['required'] ) && array() === $limpias ) {
						$errores[] = $key;
					}
					break;

				default:
					$texto         = is_scalar( $valor ) ? trim( (string) $valor ) : '';
					$datos[ $key ] = mb_substr( $texto, 0, self::TEXT_MAX );
					if ( ! empty( $pregunta['required'] ) && '' === $datos[ $key ] ) {
						$errores[] = $key;
					}
			}
		}

		return array(
			'ok'     => array() === $errores,
			'errors' => $errores,
			'data'   => $datos,
		);
	}








	public static function as_text( array $question, $answer ): string {
		if ( self::TYPE_YESNO === ( $question['type'] ?? '' ) ) {
			return self::yes( $answer ) ? 'Sí' : 'No';
		}
		if ( is_array( $answer ) ) {
			return implode( ', ', array_map( 'strval', $answer ) );
		}
		return is_scalar( $answer ) ? (string) $answer : '';
	}







	private static function yes( $value ): bool {
		if ( is_string( $value ) ) {
			return in_array( strtolower( trim( $value ) ), array( '1', 'on', 'true', 'si', 'sí', 'yes' ), true );
		}
		return (bool) $value;
	}








	private static function text( array $raw, string $key ): string {
		$value = $raw[ $key ] ?? '';
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}
}








namespace Prc\Domain;

use Prc\Meta\ProcedureMetaKeys;











final class ProcedureInput {











	public static function validate( array $raw ): array {
		$errors = array();

		$title        = self::text( $raw, 'title' );
		$description  = self::text( $raw, 'description' );
		$areas        = self::term_ids( $raw['areas'] ?? array() );
		$course       = isset( $raw['course'] ) ? (int) $raw['course'] : 0;
		$opens        = self::text( $raw, 'opens_at' );
		$closes       = self::text( $raw, 'closes_at' );
		$amend_opens  = self::text( $raw, 'amend_opens_at' );
		$amend_closes = self::text( $raw, 'amend_closes_at' );
		$emails       = self::emails( $raw['contact_emails'] ?? array() );
		$color        = self::text( $raw, 'header_color' );
		$audience     = ProcedureMetaKeys::in_list( $raw['audience'] ?? '', ProcedureMetaKeys::audiences(), ProcedureMetaKeys::AUDIENCE_SCHOOLS );
		$ownership    = self::ownership( $raw['ownership'] ?? array() );
		$urls         = array();

		if ( '' === $title ) {
			$errors[] = 'title';
		}
		if ( array() === $areas ) {
			$errors[] = 'areas';
		}
		if ( $course <= 0 ) {
			$errors[] = 'course';
		}

		foreach ( array( 'opens_at', 'closes_at', 'amend_opens_at', 'amend_closes_at' ) as $campo ) {
			$fecha = self::text( $raw, $campo );
			if ( '' !== $fecha && ! self::is_valid_date( $fecha ) ) {
				$errors[] = $campo;
			}
		}
		$opens        = self::is_valid_date( $opens ) ? $opens : '';
		$closes       = self::is_valid_date( $closes ) ? $closes : $opens;
		$amend_opens  = self::is_valid_date( $amend_opens ) ? $amend_opens : '';
		$amend_closes = self::is_valid_date( $amend_closes ) ? $amend_closes : $amend_opens;

		if ( '' !== $closes && '' === $opens ) {
			$errors[] = 'opens_at';
		}
		if ( '' !== $opens && $closes < $opens ) {
			$errors[] = 'date_order';
		}
		if ( '' !== $amend_opens && $amend_closes < $amend_opens ) {
			$errors[] = 'amend_order';
		}





		if ( '' !== $amend_opens && '' !== $closes && $amend_opens <= $closes ) {
			$errors[] = 'amend_order';
		}

		foreach ( array( 'resolution_url', 'provisional_list_url', 'final_list_url' ) as $campo ) {
			$url = self::text( $raw, $campo );
			if ( '' !== $url && ! self::is_url( $url ) ) {
				$errors[] = $campo;
				$url      = '';
			}
			$urls[ $campo ] = $url;
		}




		foreach ( self::listed( $raw['contact_emails'] ?? array() ) as $escrito ) {
			if ( ! self::is_email( strtolower( trim( (string) $escrito ) ) ) ) {
				$errors[] = 'contact_emails';
			}
		}
		if ( array() === $emails ) {
			$errors[] = 'contact_emails';
		}
		if ( '' !== $color && '' === ProcedureMetaKeys::in_list( $color, ProcedureMetaKeys::header_colors() ) ) {
			$errors[] = 'header_color';
			$color    = '';
		}
		$color = '' === $color ? ProcedureMetaKeys::HEADER_COLOR_NEUTRAL : $color;
		if ( array() === $ownership ) {
			$errors[] = 'ownership';
		}
		$errors = array_values( array_unique( $errors ) );

		return array(
			'ok'     => array() === $errors,
			'errors' => $errors,
			'data'   => array_merge(
				array(
					'title'                => $title,
					'description'          => $description,
					'areas'                => $areas,
					'course'               => max( 0, $course ),
					'opens_at'             => $opens,
					'closes_at'            => $closes,
					'amend_opens_at'       => $amend_opens,
					'amend_closes_at'      => $amend_closes,
					'contact_emails'       => $emails,
					'header_color'         => $color,
					'audience'             => $audience,
					'ownership'            => $ownership,
					'requires_coordinator' => ! empty( $raw['requires_coordinator'] ),
					'commitments'          => self::text( $raw, 'commitments' ),
				),
				$urls
			),
		);
	}









	public static function why( array $errors ): string {
		$textos = array(
			'title'                => 'el título',
			'areas'                => 'los ámbitos convocantes',
			'course'               => 'el curso',
			'opens_at'             => 'la fecha de inicio del plazo',
			'closes_at'            => 'la fecha de fin del plazo',
			'amend_opens_at'       => 'la fecha de inicio de la subsanación',
			'amend_closes_at'      => 'la fecha de fin de la subsanación',
			'date_order'           => 'el orden de las fechas del plazo',
			'amend_order'          => 'el orden de las fechas de subsanación, que va después del plazo de solicitud',
			'resolution_url'       => 'el enlace a la resolución',
			'provisional_list_url' => 'el enlace al listado provisional',
			'final_list_url'       => 'el enlace al listado definitivo',
			'contact_emails'       => 'los correos de contacto, que empiezan por uno obligatorio',
			'header_color'         => 'el color de la cabecera',
			'ownership'            => 'a qué centros se dirige',
		);

		$faltan = array();
		foreach ( $errors as $error ) {
			if ( isset( $textos[ $error ] ) && ! in_array( $textos[ $error ], $faltan, true ) ) {
				$faltan[] = $textos[ $error ];
			}
		}

		if ( array() === $faltan ) {
			return 'No se ha podido guardar el procedimiento.';
		}
		if ( 1 === count( $faltan ) ) {
			return 'Revise ' . $faltan[0] . '.';
		}

		$ultimo = array_pop( $faltan );
		return 'Revise ' . implode( ', ', $faltan ) . ' y ' . $ultimo . '.';
	}







	public static function term_ids( $raw ): array {
		$out = array();
		foreach ( self::listed( $raw ) as $valor ) {
			$id = (int) $valor;
			if ( $id > 0 && ! in_array( $id, $out, true ) ) {
				$out[] = $id;
			}
		}
		return $out;
	}










	public static function emails( $raw ): array {
		$out = array();
		foreach ( self::listed( $raw ) as $valor ) {
			$email = strtolower( trim( (string) $valor ) );
			if ( self::is_email( $email ) && ! in_array( $email, $out, true ) ) {
				$out[] = $email;
			}
		}
		return array_slice( $out, 0, ProcedureMetaKeys::CONTACT_EMAILS_MAX );
	}










	private static function listed( $raw ): array {
		if ( is_string( $raw ) ) {
			$raw = explode( ',', $raw );
		}
		if ( ! is_array( $raw ) ) {
			$raw = array( $raw );
		}
		$out = array();
		foreach ( $raw as $valor ) {
			if ( is_scalar( $valor ) && '' !== trim( (string) $valor ) ) {
				$out[] = $valor;
			}
		}
		return $out;
	}







	public static function ownership( $raw ): array {
		if ( is_string( $raw ) ) {
			$raw = explode( ',', $raw );
		}
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$raw = array_map( 'trim', array_map( 'strval', array_filter( $raw, 'is_scalar' ) ) );
		return array_values( array_intersect( array_keys( ProcedureMetaKeys::ownerships() ), $raw ) );
	}







	public static function is_valid_date( string $date ): bool {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return false;
		}
		$parts = array_map( 'intval', explode( '-', $date ) );
		return checkdate( $parts[1], $parts[2], $parts[0] );
	}










	public static function is_url( string $value ): bool {
		return (bool) preg_match( '#^https?://[^\s/$.?\#].[^\s]*$#i', $value );
	}







	public static function is_email( string $value ): bool {
		return (bool) preg_match( '/^[^@\s]+@[^@\s.]+(\.[^@\s.]+)+$/', $value );
	}








	private static function text( array $raw, string $key ): string {
		$value = $raw[ $key ] ?? '';
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}
}








namespace Prc\Domain;

use Prc\Meta\ApplicationMetaKeys;












final class ApplicationInput {










	public static function validate( array $raw, array $procedure ): array {
		$errors = array();

		$position = self::text( $raw, 'position' );
		if ( ! isset( ApplicationMetaKeys::positions()[ $position ] ) ) {
			$errors[] = 'position';
			$position = '';
		}

		$coordinator = array(
			'name'  => '',
			'email' => '',
		);
		if ( ! empty( $procedure['requires_coordinator'] ) ) {
			$coordinator['name']  = self::text( $raw, 'coordinator_name' );
			$coordinator['email'] = strtolower( self::text( $raw, 'coordinator_email' ) );
			if ( '' === $coordinator['name'] ) {
				$errors[] = 'coordinator_name';
			}
			if ( ! ProcedureInput::is_email( $coordinator['email'] ) ) {
				$errors[] = 'coordinator_email';
			}
		}


		$accepted = ! empty( $raw['accept'] );
		if ( '' !== trim( (string) ( $procedure['commitments'] ?? '' ) ) && ! $accepted ) {
			$errors[] = 'accept';
		}

		$answers   = is_array( $raw['answers'] ?? null ) ? $raw['answers'] : array();
		$respuesta = ProcedureQuestions::validate_answers( (array) ( $procedure['questions'] ?? array() ), $answers );
		foreach ( $respuesta['errors'] as $key ) {
			$errors[] = 'answer:' . $key;
		}

		return array(
			'ok'     => array() === $errors,
			'errors' => $errors,
			'data'   => array(
				'position'    => $position,
				'coordinator' => $coordinator,
				'accept'      => $accepted,
				'answers'     => $respuesta['data'],
			),
		);
	}








	public static function why( array $errors, array $questions = array() ): string {
		$textos = array(
			'position'          => 'el cargo',
			'coordinator_name'  => 'el nombre de la persona coordinadora',
			'coordinator_email' => 'el correo de la persona coordinadora',
			'accept'            => 'la aceptación de los compromisos',
		);
		foreach ( $questions as $pregunta ) {
			$textos[ 'answer:' . (string) ( $pregunta['key'] ?? '' ) ] = 'la respuesta a «' . (string) ( $pregunta['label'] ?? '' ) . '»';
		}

		$faltan = array();
		foreach ( $errors as $error ) {
			if ( isset( $textos[ $error ] ) && ! in_array( $textos[ $error ], $faltan, true ) ) {
				$faltan[] = $textos[ $error ];
			}
		}

		if ( array() === $faltan ) {
			return 'No se ha podido presentar la solicitud.';
		}
		if ( 1 === count( $faltan ) ) {
			return 'Revise ' . $faltan[0] . '.';
		}

		$ultimo = array_pop( $faltan );
		return 'Revise ' . implode( ', ', $faltan ) . ' y ' . $ultimo . '.';
	}








	private static function text( array $raw, string $key ): string {
		$value = $raw[ $key ] ?? '';
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}
}








namespace Prc\Centre;

use Prc\Meta\ProcedureMetaKeys;








final class CentreCatalogue {

	public const OPTION_CATALOGUE = 'prc_centres_catalogue';

	public const OPTION_STATUS = 'prc_centres_catalogue_status';






	public static function all(): array {
		$raw = get_option( self::OPTION_CATALOGUE, array() );
		return is_array( $raw ) ? $raw : array();
	}






	public static function all_active(): array {
		$all = self::all();
		$out = array();
		foreach ( $all as $code => $centre ) {
			if ( ! empty( $centre['active'] ) ) {
				$out[ $code ] = $centre;
			}
		}
		return $out;
	}






	public static function active_options(): array {
		$active = self::all_active();
		$out    = array();
		foreach ( $active as $code => $centre ) {
			$name = isset( $centre['name'] ) ? trim( (string) $centre['name'] ) : '';
			if ( '' !== $name ) {
				$out[ (string) $code ] = $name;
			}
		}

		uasort(
			$out,
			static function ( string $a, string $b ): int {
				return strcoll( $a, $b );
			}
		);

		return $out;
	}






	public static function for_domain(): array {
		$all = self::all();
		if ( empty( $all ) ) {
			return array();
		}

		$out = array();
		foreach ( $all as $code => $centre ) {
			$ownership = isset( $centre['ownership'] ) && is_string( $centre['ownership'] )
				? $centre['ownership']
				: ProcedureMetaKeys::OWNERSHIP_PUBLIC;

			$out[] = array(
				'code'      => (string) $code,
				'name'      => isset( $centre['name'] ) ? (string) $centre['name'] : '',
				'ownership' => ProcedureMetaKeys::in_list( $ownership, ProcedureMetaKeys::ownerships(), ProcedureMetaKeys::OWNERSHIP_PUBLIC ),
			);
		}

		return $out;
	}







	public static function find( string $code ): ?array {
		$code = trim( $code );
		if ( '' === $code ) {
			return null;
		}
		$all = self::all();
		return isset( $all[ $code ] ) && is_array( $all[ $code ] ) ? $all[ $code ] : null;
	}







	public static function is_active( string $code ): bool {
		$centre = self::find( $code );
		return null !== $centre && ! empty( $centre['active'] );
	}






	public static function status(): array {
		$defaults = array(
			'schema_version'       => 0,
			'sha256'               => '',
			'catalogue_updated_at' => '',
			'last_checked_at'      => '',
			'last_success_at'      => '',
			'last_error'           => '',
			'record_count'         => 0,
			'active_count'         => 0,
		);

		$raw = get_option( self::OPTION_STATUS, array() );
		if ( ! is_array( $raw ) ) {
			return $defaults;
		}

		return array_merge( $defaults, $raw );
	}






	public static function count(): int {
		return count( self::all() );
	}






	public static function active_count(): int {
		return count( self::all_active() );
	}
}








namespace Prc\Centre;

use RuntimeException;








final class CentreCatalogueSync {

	public const OPTION_MANIFEST_URL = 'prc_centres_manifest_url';

	public const OPTION_CATALOGUE_URL = 'prc_centres_catalogue_url';

	public const OPTION_LOCK = 'prc_centres_sync_lock';

	public const LOCK_TTL = 300;

	public const CRON_HOOK = 'prc_centres_cron_sync';

	public const HTTP_TIMEOUT = 30;






	public static function register_cron(): void {
		add_action( self::CRON_HOOK, array( self::class, 'cron_sync' ) );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + 3600, 'daily', self::CRON_HOOK );
		}
	}






	public static function cron_sync(): void {
		try {
			self::sync( false );
		} catch ( RuntimeException $e ) {

			unset( $e );
		}
	}







	public static function is_valid_https_url( string $url ): bool {
		$url = trim( $url );
		if ( '' === $url ) {
			return false;
		}
		$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
		$host   = wp_parse_url( $url, PHP_URL_HOST );
		return 'https' === strtolower( (string) $scheme ) && '' !== trim( (string) $host );
	}






	public static function manifest_url(): string {
		$url = '';
		if ( defined( 'PRC_CENTRES_MANIFEST_URL' ) ) {
			$url = (string) PRC_CENTRES_MANIFEST_URL;
		}
		if ( '' === $url ) {
			$url = (string) get_option( self::OPTION_MANIFEST_URL, '' );
		}





		$filtered = apply_filters( 'prc_centres_manifest_url', $url );
		return is_string( $filtered ) ? trim( $filtered ) : '';
	}







	public static function catalogue_url( string $manifest_url = '' ): string {
		$url = '';
		if ( defined( 'PRC_CENTRES_CATALOGUE_URL' ) ) {
			$url = (string) PRC_CENTRES_CATALOGUE_URL;
		}
		if ( '' === $url ) {
			$url = (string) get_option( self::OPTION_CATALOGUE_URL, '' );
		}
		if ( '' === $url && '' !== $manifest_url ) {
			$dir = dirname( $manifest_url );
			if ( 'http:' === $dir || 'https:' === $dir ) {
				$dir = $manifest_url;
			}
			$url = trailingslashit( $dir ) . 'centros.min.json';
		}





		$filtered = apply_filters( 'prc_centres_catalogue_url', $url );
		return is_string( $filtered ) ? trim( $filtered ) : '';
	}










	public static function acquire_lock() {
		$token   = wp_generate_password( 32, false );
		$payload = array(
			'token' => $token,
			'time'  => time(),
		);

		wp_cache_delete( self::OPTION_LOCK, 'options' );
		wp_cache_delete( 'notoptions', 'options' );

		if ( add_option( self::OPTION_LOCK, $payload, '', 'no' ) ) {
			return $token;
		}

		$current = get_option( self::OPTION_LOCK );
		if ( is_array( $current ) && isset( $current['time'] ) ) {
			$elapsed = time() - (int) $current['time'];
			if ( $elapsed > self::LOCK_TTL ) {
				global $wpdb;

				$deleted = $wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s",
						self::OPTION_LOCK,
						maybe_serialize( $current )
					)
				);

				if ( $deleted ) {
					wp_cache_delete( self::OPTION_LOCK, 'options' );
					wp_cache_delete( 'notoptions', 'options' );

					if ( add_option( self::OPTION_LOCK, $payload, '', 'no' ) ) {
						return $token;
					}
				}
			}
		}

		return false;
	}







	public static function release_lock( string $token ): bool {
		wp_cache_delete( self::OPTION_LOCK, 'options' );
		$current = get_option( self::OPTION_LOCK );
		if ( is_array( $current ) && isset( $current['token'] ) && hash_equals( (string) $current['token'], $token ) ) {
			global $wpdb;

			$deleted = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s",
					self::OPTION_LOCK,
					maybe_serialize( $current )
				)
			);
			if ( $deleted ) {
				wp_cache_delete( self::OPTION_LOCK, 'options' );
				wp_cache_delete( 'notoptions', 'options' );
				return true;
			}
		}
		return false;
	}








	public static function sync( bool $force = false ): array {
		$token = self::acquire_lock();
		if ( false === $token ) {
			throw new RuntimeException( 'Hay otra sincronización de centros en curso. Espere a que finalice.' );
		}

		$status = CentreCatalogue::status();

		try {
			$manifest_url = self::manifest_url();
			if ( ! self::is_valid_https_url( $manifest_url ) ) {
				throw new RuntimeException( 'URL de manifest de centros inválida o no utiliza HTTPS.' );
			}


			$manifest_response = wp_safe_remote_get(
				$manifest_url,
				array(
					'timeout'    => self::HTTP_TIMEOUT,
					'sslverify'  => true,
					'user-agent' => 'WordPress/Prc',
				)
			);

			if ( is_wp_error( $manifest_response ) ) {

				throw new RuntimeException( 'Error al descargar manifest.json: ' . $manifest_response->get_error_message() );
			}

			$code = wp_remote_retrieve_response_code( $manifest_response );
			if ( 200 !== $code ) {

				throw new RuntimeException( sprintf( 'El servidor devolvió HTTP %d al solicitar manifest.json.', $code ) );
			}

			$manifest_body = wp_remote_retrieve_body( $manifest_response );
			$manifest      = json_decode( $manifest_body, true );

			if ( ! is_array( $manifest ) ) {
				throw new RuntimeException( 'El archivo manifest.json no contiene un JSON válido.' );
			}


			if ( empty( $manifest['schema_version'] ) || 1 !== (int) $manifest['schema_version'] ) {
				throw new RuntimeException( 'Versión de esquema incompatible en manifest.json.' );
			}

			if ( empty( $manifest['files']['centros.min.json']['sha256'] ) ) {
				throw new RuntimeException( 'El manifest.json no declara la entrada de centros.min.json con su sha256.' );
			}

			$remote_sha256 = trim( (string) $manifest['files']['centros.min.json']['sha256'] );
			$remote_count  = isset( $manifest['files']['centros.min.json']['records'] )
				? (int) $manifest['files']['centros.min.json']['records']
				: 0;
			$updated_at    = isset( $manifest['catalogue_updated_at'] ) && is_scalar( $manifest['catalogue_updated_at'] )
				? trim( (string) $manifest['catalogue_updated_at'] )
				: '';


			if ( ! $force && $remote_sha256 === $status['sha256'] && ! empty( $status['sha256'] ) && CentreCatalogue::count() > 0 ) {
				$status['last_checked_at'] = current_time( 'mysql' );
				$status['last_error']      = '';
				update_option( CentreCatalogue::OPTION_STATUS, $status, false );

				return array(
					'status'  => 'unchanged',
					'sha256'  => $remote_sha256,
					'records' => $status['record_count'],
					'active'  => $status['active_count'],
				);
			}


			$catalogue_url = self::catalogue_url( $manifest_url );
			if ( ! self::is_valid_https_url( $catalogue_url ) ) {
				throw new RuntimeException( 'URL del catálogo de centros inválida o no utiliza HTTPS.' );
			}

			$cat_response = wp_safe_remote_get(
				$catalogue_url,
				array(
					'timeout'    => 45,
					'sslverify'  => true,
					'user-agent' => 'WordPress/Prc',
				)
			);

			if ( is_wp_error( $cat_response ) ) {

				throw new RuntimeException( 'Error al descargar centros.min.json: ' . $cat_response->get_error_message() );
			}

			$cat_code = wp_remote_retrieve_response_code( $cat_response );
			if ( 200 !== $cat_code ) {

				throw new RuntimeException( sprintf( 'El servidor devolvió HTTP %d al solicitar centros.min.json.', $cat_code ) );
			}

			$cat_body = wp_remote_retrieve_body( $cat_response );


			$computed_sha256 = hash( 'sha256', $cat_body );
			if ( ! hash_equals( $remote_sha256, $computed_sha256 ) ) {
				throw new RuntimeException( 'El hash SHA-256 del archivo descargado no coincide con el declarado en manifest.json.' );
			}


			$items = json_decode( $cat_body, true );
			if ( ! is_array( $items ) ) {
				throw new RuntimeException( 'El archivo centros.min.json no contiene un array JSON válido.' );
			}

			if ( $remote_count > 0 && count( $items ) !== $remote_count ) {
				$msg = sprintf( 'El número de registros (%d) no coincide con el declarado en el manifest (%d).', count( $items ), $remote_count );

				throw new RuntimeException( $msg );
			}

			$indexed      = array();
			$active_count = 0;

			foreach ( $items as $idx => $item ) {
				if ( ! is_array( $item ) ) {
					$msg = sprintf( 'Registro en posición %d no es un objeto válido.', $idx );

					throw new RuntimeException( $msg );
				}

				$code = isset( $item['code'] ) && is_scalar( $item['code'] ) ? trim( (string) $item['code'] ) : '';
				if ( 1 !== preg_match( '/^\d{8}$/', $code ) ) {
					$msg = sprintf( 'Código de centro inválido en registro %d (debe tener exactamente 8 dígitos): "%s".', $idx, $code );

					throw new RuntimeException( $msg );
				}

				if ( isset( $indexed[ $code ] ) ) {
					$msg = sprintf( 'Código oficial duplicado en centros.min.json: "%s".', $code );

					throw new RuntimeException( $msg );
				}

				$name = isset( $item['name'] ) && is_scalar( $item['name'] ) ? trim( (string) $item['name'] ) : '';
				if ( '' === $name ) {
					$msg = sprintf( 'Denominación vacía para el centro con código "%s".', $code );

					throw new RuntimeException( $msg );
				}

				if ( ! isset( $item['active'] ) || ! is_bool( $item['active'] ) ) {
					$msg = sprintf( 'El campo "active" debe ser booleano para el centro con código "%s".', $code );

					throw new RuntimeException( $msg );
				}

				$is_active = (bool) $item['active'];
				if ( $is_active ) {
					++$active_count;
				}

				$indexed[ $code ] = array(
					'code'         => $code,
					'name'         => $name,
					'island'       => isset( $item['island'] ) && is_scalar( $item['island'] ) ? trim( (string) $item['island'] ) : '',
					'municipality' => isset( $item['municipality'] ) && is_scalar( $item['municipality'] ) ? trim( (string) $item['municipality'] ) : '',
					'type'         => isset( $item['type'] ) && is_scalar( $item['type'] ) ? trim( (string) $item['type'] ) : '',
					'active'       => $is_active,
				);
			}


			update_option( CentreCatalogue::OPTION_CATALOGUE, $indexed, false );

			$status = array(
				'schema_version'       => 1,
				'sha256'               => $remote_sha256,
				'catalogue_updated_at' => $updated_at,
				'last_checked_at'      => current_time( 'mysql' ),
				'last_success_at'      => current_time( 'mysql' ),
				'last_error'           => '',
				'record_count'         => count( $indexed ),
				'active_count'         => $active_count,
			);
			update_option( CentreCatalogue::OPTION_STATUS, $status, false );

			return array(
				'status'  => 'updated',
				'sha256'  => $remote_sha256,
				'records' => count( $indexed ),
				'active'  => $active_count,
			);
		} catch ( RuntimeException $e ) {
			$status['last_checked_at'] = current_time( 'mysql' );
			$status['last_error']      = $e->getMessage();
			update_option( CentreCatalogue::OPTION_STATUS, $status, false );

			throw $e;
		} finally {
			self::release_lock( $token );
		}
	}
}








namespace Prc\Domain;

use Prc\Meta\ProcedureMetaKeys;










final class CentreCatalog {




	public const SEARCH_LIMIT = 20;






	public static function all(): array {








		$default = class_exists( '\Prc\Centre\CentreCatalogue' ) ? \Prc\Centre\CentreCatalogue::for_domain() : array();
		if ( empty( $default ) ) {
			$default = self::from_uploads();
		}
		$raw = apply_filters( 'prc_centres', $default );
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$out    = array();
		$vistos = array();
		foreach ( $raw as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$code = isset( $item['code'] ) && is_scalar( $item['code'] ) ? trim( (string) $item['code'] ) : '';
			if ( '' === $code || isset( $vistos[ $code ] ) ) {
				continue;
			}
			$vistos[ $code ] = true;
			$out[]           = array(
				'code'      => $code,
				'name'      => isset( $item['name'] ) && is_scalar( $item['name'] ) ? trim( (string) $item['name'] ) : '',
				'ownership' => ProcedureMetaKeys::in_list( $item['ownership'] ?? '', ProcedureMetaKeys::ownerships(), ProcedureMetaKeys::OWNERSHIP_PUBLIC ),
			);
		}
		return $out;
	}







	public static function find( string $code ): array {
		$code = trim( $code );
		if ( '' === $code ) {
			return array();
		}
		foreach ( self::all() as $centro ) {
			if ( $centro['code'] === $code ) {
				return $centro;
			}
		}
		return array();
	}







	public static function search( string $q ): array {
		$q = self::fold( $q );
		if ( '' === $q ) {
			return array();
		}
		$out = array();
		foreach ( self::all() as $centro ) {
			if ( 0 === strpos( self::fold( $centro['code'] ), $q ) || false !== strpos( self::fold( $centro['name'] ), $q ) ) {
				$out[] = $centro;
				if ( count( $out ) >= self::SEARCH_LIMIT ) {
					break;
				}
			}
		}
		return $out;
	}






	private static function from_uploads(): array {
		$file = (string) wp_upload_dir()['basedir'] . '/prc/centres.json';
		if ( ! is_readable( $file ) ) {
			return array();
		}

		$data = json_decode( (string) file_get_contents( $file ), true );
		return is_array( $data ) ? $data : array();
	}







	private static function fold( string $value ): string {
		return mb_strtolower( trim( remove_accents( $value ) ), 'UTF-8' );
	}
}








namespace Prc\Access;










final class CentreScope {




	public const META_KEY = 'prc_centre_code';






	public static function meta_key(): string {





		$key = apply_filters( 'prc_centre_code_meta_key', self::META_KEY );
		return is_string( $key ) ? trim( $key ) : '';
	}







	public static function code_for( int $user_id = 0 ): string {
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
		$key = self::meta_key();
		if ( $user_id <= 0 || '' === $key ) {
			return '';
		}
		$raw  = get_user_meta( $user_id, $key, true );
		$code = is_scalar( $raw ) ? trim( (string) $raw ) : '';
		return self::is_code( $code ) ? $code : '';
	}










	public static function is_code( string $code ): bool {
		return (bool) preg_match( '/^[A-Za-z0-9]{3,20}$/', $code );
	}
}









namespace Prc\Access;

use Prc\Domain\CentreCatalog;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ApplicationPostType;
use Prc\PostType\ProcedurePostType;
use Prc\Taxonomy\ProcedureTaxonomies;















final class ProcedureAccess {




	public const USER_AREA_META = 'prc_area';




	public const CAP_APPLY = 'prc_apply';




	public const CAP_MANAGE_PROCEDURES = 'prc_manage_procedures';




	public const CAP_REVIEW = 'prc_review_applications';




	public const CAP_ALL_AREAS = 'prc_manage_all_areas';




	public const CAP_MANAGE = 'prc_manage_app';






	public static function register(): void {
		add_filter( 'map_meta_cap', array( self::class, 'map_meta_cap' ), 10, 4 );
		add_filter( 'rest_pre_insert_prc_procedure', array( self::class, 'validate_rest_areas' ), 10, 2 );
		add_action( 'admin_init', array( self::class, 'validate_admin_areas' ) );
		add_filter( 'wp_insert_post_data', array( self::class, 'guard_parent' ), 10, 2 );
		add_action( 'save_post_' . ProcedurePostType::POST_TYPE, array( self::class, 'stamp_area' ), 20 );
	}





















	public static function guard_parent( array $data, array $postarr ): array {
		$tipo = (string) ( $data['post_type'] ?? '' );

		if ( get_current_user_id() <= 0 || ! in_array( $tipo, self::scoped_types(), true ) ) {
			return $data;
		}
		$post_id = absint( $postarr['ID'] ?? 0 );
		if ( $post_id > 0 ) {
			$data['post_parent'] = (int) get_post_field( 'post_parent', $post_id );
		} elseif ( ProcedurePostType::POST_TYPE === $tipo ) {
			$data['post_parent'] = 0;
		}
		return $data;
	}






	public static function stamp_area( int $post_id ): void {
		if ( array() !== self::post_areas( $post_id ) ) {
			return;
		}
		$areas = self::user_areas( (int) get_post_field( 'post_author', $post_id ) );
		if ( array() !== $areas ) {
			wp_set_object_terms( $post_id, $areas, ProcedureTaxonomies::AREA );
		}
	}








	public static function validate_rest_areas( $prepared, $request ) {
		if ( is_wp_error( $prepared ) ) {
			return $prepared;
		}
		$post_id = absint( $request->get_url_params()['id'] ?? 0 );
		if ( ! $request->has_param( ProcedureTaxonomies::AREA ) ) {
			if ( $post_id > 0 || self::can_edit_all_areas() ) {
				return $prepared;
			}
			$assigned = self::user_areas();
			if ( array() === $assigned ) {
				return new \WP_Error( 'prc_area_forbidden', self::scope_assignment_message( get_current_user_id() ), array( 'status' => 403 ) );
			}
			$requested = $assigned;
		} else {
			$requested = (array) $request->get_param( ProcedureTaxonomies::AREA );
		}
		$final = self::resolve_area_assignment( $post_id, $requested );
		if ( is_wp_error( $final ) ) {
			return $final;
		}
		if ( 0 === $post_id && ! self::can_edit_all_areas() && in_array( $request->get_param( 'status' ), array( 'publish', 'future', 'private' ), true ) ) {
			return new \WP_Error( 'prc_scope_draft_first', 'Cree el procedimiento como borrador antes de publicarlo, para que tenga ámbito desde el principio.', array( 'status' => 400 ) );
		}
		$request->set_param( ProcedureTaxonomies::AREA, $final );
		return $prepared;
	}









	public static function resolve_area_assignment( int $post_id, array $requested, int $user_id = 0 ) {
		$user_id = $user_id > 0 ? $user_id : get_current_user_id();
		$ids     = array();
		foreach ( $requested as $value ) {
			if ( ! is_scalar( $value ) ) {
				return new \WP_Error( 'prc_area_invalid', 'El ámbito solicitado no existe.', array( 'status' => 400 ) );
			}
			$value = trim( (string) $value );
			$id    = ctype_digit( $value ) ? absint( $value ) : 0;
			if ( $id <= 0 || ! ( get_term( $id, ProcedureTaxonomies::AREA ) instanceof \WP_Term ) ) {
				return new \WP_Error( 'prc_area_invalid', 'El ámbito solicitado no existe.', array( 'status' => 400 ) );
			}
			$ids[] = $id;
		}
		$ids = array_values( array_unique( $ids ) );
		if ( self::can_edit_all_areas( $user_id ) ) {
			return $ids;
		}
		$allowed = self::scope_areas( $user_id );
		if ( array() === $allowed ) {
			return new \WP_Error( 'prc_area_forbidden', self::scope_assignment_message( $user_id ), array( 'status' => 403 ) );
		}
		if ( array_diff( $ids, $allowed ) ) {
			return new \WP_Error( 'prc_area_forbidden', 'No puede asignar un ámbito de otra rama.', array( 'status' => 403 ) );
		}
		$foreign = $post_id > 0 ? array_diff( self::post_areas( $post_id ), $allowed ) : array();
		$final   = array_values( array_unique( array_merge( $ids, $foreign ) ) );
		if ( array() === $final ) {
			return new \WP_Error( 'prc_area_required', 'El contenido debe conservar al menos un ámbito organizador.', array( 'status' => 400 ) );
		}
		return $final;
	}








	public static function may_assign_areas( array $requested, int $user_id = 0 ): bool {
		if ( self::can_edit_all_areas( $user_id ) ) {
			return true;
		}
		if ( array() === $requested ) {
			return false;
		}
		$effective_user = $user_id > 0 ? $user_id : get_current_user_id();
		$allowed        = self::scope_areas( $effective_user );
		foreach ( $requested as $term_id ) {
			$id = absint( $term_id );
			if ( ! ( get_term( $id, ProcedureTaxonomies::AREA ) instanceof \WP_Term ) || ! in_array( $id, $allowed, true ) ) {
				return false;
			}
		}
		return true;
	}




	public static function validate_admin_areas(): void {
		global $pagenow;


		$action = isset( $_POST['action'] ) ? (string) $_POST['action'] : '';
		if ( ! ( 'post.php' === $pagenow && 'editpost' === $action ) && ! ( 'admin-ajax.php' === $pagenow && 'inline-save' === $action ) && ! ( 'edit.php' === $pagenow && in_array( $action, array( 'edit', 'bulk_edit' ), true ) ) ) {
			return;
		}
		$final = self::admin_area_assignment( wp_unslash( $_POST ) );
		if ( null === $final ) {
			return;
		}
		if ( is_wp_error( $final ) ) {
			wp_die( esc_html( $final->get_error_message() ), 'Ámbito no permitido', array( 'response' => absint( $final->get_error_data()['status'] ?? 403 ) ) );
		}
		$_POST['tax_input'][ ProcedureTaxonomies::AREA ] = $final;

	}







	public static function admin_area_assignment( array $request ) {
		if ( ProcedurePostType::POST_TYPE !== ( $request['post_type'] ?? '' ) || ( ! isset( $request['tax_input'][ ProcedureTaxonomies::AREA ] ) && empty( $request['prc_area_present'] ) ) ) {
			return null;
		}
		$requested = $request['tax_input'][ ProcedureTaxonomies::AREA ] ?? array();
		$requested = is_array( $requested ) ? $requested : explode( ',', (string) $requested );
		return self::resolve_area_assignment( absint( $request['post_ID'] ?? 0 ), $requested );
	}






	public static function scoped_types(): array {
		return array( ProcedurePostType::POST_TYPE, ApplicationPostType::POST_TYPE );
	}







	public static function is_manager( int $user_id = 0 ): bool {
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
		return user_can( $user_id, self::CAP_MANAGE ) || user_can( $user_id, 'manage_options' );
	}







	public static function can_edit_all_areas( int $user_id = 0 ): bool {
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
		return user_can( $user_id, self::CAP_ALL_AREAS ) || self::is_manager( $user_id );
	}









	public static function can_manage_procedures( int $user_id = 0 ): bool {
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
		if ( $user_id <= 0 || ! user_can( $user_id, self::CAP_MANAGE_PROCEDURES ) ) {
			return false;
		}
		return self::can_edit_all_areas( $user_id ) || array() !== self::user_areas( $user_id );
	}







	public static function user_areas( int $user_id = 0 ): array {
		$assignment = self::scope_assignment_state( $user_id );
		return 'resolved' === $assignment['state'] ? $assignment['ids'] : array();
	}







	public static function scope_assignment_state( int $user_id = 0 ): array {
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
		if ( $user_id <= 0 ) {
			return array(
				'state'   => 'empty',
				'ids'     => array(),
				'invalid' => array(),
				'legacy'  => false,
			);
		}
		$raw     = get_user_meta( $user_id, self::USER_AREA_META, true );
		$scalar  = is_scalar( $raw ) ? trim( (string) $raw ) : '';
		$legacy  = ! is_array( $raw ) && '' !== $scalar;
		$values  = is_array( $raw ) ? $raw : ( '' === $scalar ? array() : explode( ',', $scalar ) );
		$ids     = array();
		$invalid = array();
		foreach ( $values as $value ) {
			$value = is_scalar( $value ) ? trim( (string) $value ) : '';
			$id    = ctype_digit( $value ) ? absint( $value ) : 0;
			if ( $id > 0 && get_term( $id, ProcedureTaxonomies::AREA ) instanceof \WP_Term ) {
				$ids[] = $id;
			} else {
				$invalid[] = $value;
			}
		}
		$ids   = array_values( array_unique( $ids ) );
		$state = $invalid ? 'invalid' : ( count( $ids ) > 1 ? 'ambiguous' : ( $ids ? 'resolved' : 'empty' ) );
		return compact( 'state', 'ids', 'invalid', 'legacy' );
	}







	public static function scope_assignment_message( int $user_id ): string {
		$state = self::scope_assignment_state( $user_id )['state'];
		if ( 'ambiguous' === $state ) {
			return 'Su perfil conserva varios ámbitos y necesita que administración elija uno.';
		}
		if ( 'invalid' === $state ) {
			return 'Su perfil contiene un ámbito inválido y necesita que administración lo revise.';
		}
		return 'No tiene ningún ámbito asignado en su perfil. El ámbito lo pone quien administra el aplicativo.';
	}






	public static function scope_diagnostics(): array {
		$rows = array();
		foreach ( get_users() as $user ) {
			if ( ! ( $user instanceof \WP_User ) || self::can_edit_all_areas( $user->ID ) ) {
				continue;
			}
			if ( ! user_can( $user, self::CAP_MANAGE_PROCEDURES ) && ! array_intersect( array( 'editor', 'prc_manager' ), $user->roles ) ) {
				continue;
			}
			$rows[] = array_merge(
				array(
					'user_id' => $user->ID,
					'login'   => $user->user_login,
				),
				self::scope_assignment_state( $user->ID )
			);
		}
		return $rows;
	}










	public static function scope_areas( int $user_id = 0 ): array {
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
		static $cache = array();
		$assigned     = self::user_areas( $user_id );
		$key          = $user_id . ':' . implode( ',', $assigned ) . ':' . wp_cache_get_last_changed( 'terms' );
		if ( isset( $cache[ $key ] ) ) {
			return $cache[ $key ];
		}
		$areas = array();
		foreach ( $assigned as $term_id ) {
			$children = get_term_children( $term_id, ProcedureTaxonomies::AREA );
			if ( is_wp_error( $children ) ) {
				return array();
			}
			$areas = array_merge( $areas, array( $term_id ), $children );
		}
		$cache[ $key ] = self::clean_ids( $areas );
		return $cache[ $key ];
	}









	public static function post_areas( int $post_id ): array {
		$terms = get_the_terms( self::root_id( $post_id ), ProcedureTaxonomies::AREA );
		if ( ! is_array( $terms ) ) {
			return array();
		}
		return self::clean_ids( wp_list_pluck( $terms, 'term_id' ) );
	}







	public static function root_id( int $post_id ): int {
		$parent = (int) get_post_field( 'post_parent', $post_id );
		return $parent > 0 ? $parent : $post_id;
	}









	public static function is_archived( int $post_id ): bool {
		if ( $post_id <= 0 ) {
			return false;
		}
		return (bool) get_post_meta( self::root_id( $post_id ), ProcedureMetaKeys::ARCHIVED, true );
	}












	public static function can_archive( int $user_id, int $post_id ): bool {
		return self::can_open( $user_id, $post_id );
	}










	public static function can_unarchive( int $user_id = 0 ): bool {
		return self::is_manager( $user_id );
	}












	public static function can_toggle_archived( int $user_id, int $post_id ): bool {
		return self::is_archived( $post_id )
			? self::can_unarchive( $user_id )
			: self::can_archive( $user_id, $post_id );
	}











	public static function can_open( int $user_id, int $post_id ): bool {
		if ( $user_id <= 0 || ! user_can( $user_id, self::CAP_MANAGE_PROCEDURES ) ) {
			return false;
		}
		return self::in_scope( $user_id, $post_id );
	}













	public static function can_edit( int $user_id, int $post_id ): bool {
		if ( ! self::can_open( $user_id, $post_id ) ) {
			return false;
		}
		return ! self::is_archived( $post_id ) || self::is_manager( $user_id );
	}














	public static function can_edit_group( int $user_id, int $procedure_id, string $group ): bool {
		if ( ! self::can_edit( $user_id, $procedure_id ) ) {
			return false;
		}
		$estado = ProcedureState::of_post( $procedure_id );



		return ProcedureMetaKeys::STATE_ARCHIVED === $estado
			|| in_array( $group, ProcedureState::editable_fields( $estado ), true );
	}








	public static function can_publish( int $user_id, int $post_id = 0 ): bool {
		if ( $user_id <= 0 || ! user_can( $user_id, 'publish_prc_procedures' ) ) {
			return false;
		}
		if ( $post_id <= 0 ) {
			return true;
		}
		return self::can_edit( $user_id, $post_id );
	}











	public static function can_review( int $user_id, int $post_id ): bool {
		if ( $user_id <= 0 || ! user_can( $user_id, self::CAP_REVIEW ) || ! self::in_scope( $user_id, $post_id ) ) {
			return false;
		}
		return ! self::is_archived( $post_id ) || self::is_manager( $user_id );
	}














	public static function can_apply( int $user_id, int $post_id = 0 ): bool {
		if ( $user_id <= 0 || ! user_can( $user_id, self::CAP_APPLY ) ) {
			return false;
		}
		$code = CentreScope::code_for( $user_id );
		if ( '' === $code ) {
			return false;
		}
		if ( $post_id <= 0 ) {
			return true;
		}
		if ( ProcedurePostType::POST_TYPE !== get_post_type( $post_id ) || 'publish' !== get_post_status( $post_id ) ) {
			return false;
		}
		$centro = CentreCatalog::find( $code );
		if ( array() === $centro ) {
			return true;
		}
		$ownership = get_post_meta( $post_id, ProcedureMetaKeys::OWNERSHIP, true );
		$ownership = is_array( $ownership ) ? $ownership : array();
		return array() === $ownership || in_array( $centro['ownership'], $ownership, true );
	}








	public static function can_view_application( int $user_id, int $application_id ): bool {
		if ( $user_id <= 0 || ApplicationPostType::POST_TYPE !== get_post_type( $application_id ) ) {
			return false;
		}
		if ( self::can_review( $user_id, $application_id ) ) {
			return true;
		}
		if ( ! user_can( $user_id, self::CAP_APPLY ) ) {
			return false;
		}
		$code  = CentreScope::code_for( $user_id );
		$owner = (string) get_post_meta( $application_id, ApplicationMetaKeys::CENTRE_CODE, true );
		return '' !== $code && $code === $owner;
	}











	public static function why_not_editable( int $user_id, int $post_id, string $group = '' ): string {
		if ( self::can_edit( $user_id, $post_id ) ) {


			return '' === $group || self::can_edit_group( $user_id, $post_id, $group )
				? ''
				: ProcedureState::why_locked( ProcedureState::of_post( $post_id ) );
		}
		if ( self::can_open( $user_id, $post_id ) ) {
			return 'Este procedimiento está marcado como histórico: se puede consultar y exportar, pero ya no se edita. Para volver a abrirlo, pídalo a quien administre el aplicativo.';
		}
		if ( ! user_can( $user_id, self::CAP_MANAGE_PROCEDURES ) ) {
			return 'Su perfil no gestiona procedimientos. Si debería hacerlo, pídalo a quien administre el aplicativo.';
		}
		if ( array() === self::user_areas( $user_id ) ) {
			return self::scope_assignment_message( $user_id );
		}
		return 'Este procedimiento es de otro ámbito. Solo lo edita el ámbito que lo convoca o quien administra el aplicativo.';
	}















	public static function map_meta_cap( array $caps, string $cap, int $user_id, array $args ): array {
		if ( 'assign_term' === $cap ) {
			return self::may_use_area( $user_id, isset( $args[0] ) ? (int) $args[0] : 0 )
				? $caps
				: array( 'do_not_allow' );
		}
		if ( ! in_array( $cap, array( 'edit_post', 'delete_post', 'publish_post', 'read_post' ), true ) ) {
			return $caps;
		}
		$post_id = isset( $args[0] ) ? (int) $args[0] : 0;
		$tipo    = $post_id > 0 ? (string) get_post_type( $post_id ) : '';
		if ( ! in_array( $tipo, self::scoped_types(), true ) ) {
			return $caps;
		}

		if ( ApplicationPostType::POST_TYPE === $tipo ) {
			$permitido = 'read_post' === $cap
				? self::can_view_application( $user_id, $post_id )
				: self::can_review( $user_id, $post_id );
		} else {
			$permitido = 'read_post' === $cap
				? self::can_read( $user_id, $post_id )
				: self::can_edit( $user_id, $post_id );
		}

		return $permitido ? $caps : array( 'do_not_allow' );
	}

















	public static function can_read( int $user_id, int $post_id ): bool {
		$estado = get_post_status_object( (string) get_post_status( $post_id ) );
		if ( null !== $estado && ! empty( $estado->public ) ) {
			return true;
		}
		return self::is_manager( $user_id ) || self::can_open( $user_id, $post_id );
	}

















	public static function may_use_area( int $user_id, int $term_id ): bool {
		$term = $term_id > 0 ? get_term( $term_id ) : null;
		if ( $term instanceof \WP_Term && ProcedureTaxonomies::AREA !== $term->taxonomy ) {

			return true;
		}
		if ( self::can_edit_all_areas( $user_id ) ) {
			return true;
		}

		return in_array( $term_id, self::scope_areas( $user_id ), true );
	}








	private static function in_scope( int $user_id, int $post_id ): bool {
		if ( $post_id <= 0 || ! in_array( (string) get_post_type( $post_id ), self::scoped_types(), true ) ) {
			return false;
		}
		if ( self::can_edit_all_areas( $user_id ) ) {
			return true;
		}

		$mine = self::scope_areas( $user_id );
		if ( array() === $mine ) {
			return false;
		}

		$theirs = self::post_areas( $post_id );
		if ( array() === $theirs ) {


			return 'auto-draft' === get_post_status( self::root_id( $post_id ) )
				&& (int) get_post_field( 'post_author', self::root_id( $post_id ) ) === $user_id;
		}

		return array() !== array_intersect( $mine, $theirs );
	}







	private static function clean_ids( array $values ): array {
		$out = array();
		foreach ( $values as $value ) {
			$id = (int) $value;
			if ( $id > 0 ) {
				$out[] = $id;
			}
		}
		return array_values( array_unique( $out ) );
	}
}








namespace Prc\Meta;

use Prc\Access\ProcedureAccess;
use Prc\Domain\ProcedureInput;
use Prc\Domain\ProcedureQuestions;
use Prc\PostType\ProcedurePostType;








final class ProcedureMetaRegistration {






	public static function register(): void {
		add_action( 'init', array( self::class, 'register_meta' ), 12 );
	}










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






	public static function defaults(): array {
		return array(
			'string'  => '',
			'boolean' => false,
			'integer' => 0,
			'array'   => array(),
		);
	}










	public static function auth_edit_procedure( bool $allowed, string $meta_key, int $post_id, int $user_id ): bool {
		unset( $allowed, $meta_key );
		return user_can( $user_id, 'edit_post', $post_id );
	}














	public static function auth_archived( bool $allowed, string $meta_key, int $post_id, int $user_id ): bool {
		unset( $allowed, $meta_key );
		return ProcedureAccess::can_toggle_archived( $user_id, $post_id );
	}







	public static function sanitize_date( $value ): string {
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
			return '';
		}
		$parts = array_map( 'intval', explode( '-', $value ) );
		return checkdate( $parts[1], $parts[2], $parts[0] ) ? $value : '';
	}







	public static function sanitize_url( $value ): string {
		$url = esc_url_raw( is_scalar( $value ) ? trim( (string) $value ) : '', array( 'http', 'https' ) );
		return is_string( $url ) ? $url : '';
	}







	public static function sanitize_email( $value ): string {
		$email = sanitize_email( is_scalar( $value ) ? (string) $value : '' );
		return is_string( $email ) ? strtolower( $email ) : '';
	}







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







	public static function sanitize_header_color( $value ): string {
		return ProcedureMetaKeys::in_list( $value, ProcedureMetaKeys::header_colors(), ProcedureMetaKeys::HEADER_COLOR_NEUTRAL );
	}







	public static function sanitize_audience( $value ): string {
		return ProcedureMetaKeys::in_list( $value, ProcedureMetaKeys::audiences(), ProcedureMetaKeys::AUDIENCE_SCHOOLS );
	}







	public static function sanitize_ownership( $value ): array {
		return ProcedureInput::ownership( $value );
	}







	public static function sanitize_bool( $value ): bool {
		if ( is_string( $value ) ) {
			return in_array( strtolower( trim( $value ) ), array( '1', 'on', 'true', 'si', 'sí', 'yes' ), true );
		}
		return (bool) $value;
	}







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








namespace Prc\Meta;

use Prc\Domain\ProcedureInput;
use Prc\Domain\ProcedureQuestions;
use Prc\PostType\ApplicationPostType;









final class ApplicationMetaRegistration {






	public static function register(): void {
		add_action( 'init', array( self::class, 'register_meta' ), 12 );
	}






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







	public static function sanitize_position( $value ): string {
		return ProcedureMetaKeys::in_list( $value, ApplicationMetaKeys::positions(), ApplicationMetaKeys::POSITION_OTHER );
	}







	public static function sanitize_review_state( $value ): string {
		return ProcedureMetaKeys::in_list( $value, ApplicationMetaKeys::review_states(), ApplicationMetaKeys::REVIEW_SUBMITTED );
	}







	public static function sanitize_coordinator( $value ): array {
		$value = is_array( $value ) ? $value : array();
		$email = sanitize_email( isset( $value['email'] ) && is_scalar( $value['email'] ) ? (string) $value['email'] : '' );
		return array(
			'name'  => sanitize_text_field( isset( $value['name'] ) && is_scalar( $value['name'] ) ? (string) $value['name'] : '' ),
			'email' => ProcedureInput::is_email( $email ) ? strtolower( $email ) : '',
		);
	}













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








namespace Prc\PostType;








final class ProcedurePostType {

	public const POST_TYPE = 'prc_procedure';






	public static function register(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'               => 'Procedimientos',
					'singular_name'      => 'Procedimiento',
					'add_new'            => 'Añadir procedimiento',
					'add_new_item'       => 'Añadir procedimiento',
					'edit_item'          => 'Editar procedimiento',
					'new_item'           => 'Nuevo procedimiento',
					'view_item'          => 'Ver procedimiento',
					'search_items'       => 'Buscar procedimientos',
					'not_found'          => 'No se encontraron procedimientos',
					'not_found_in_trash' => 'No hay procedimientos en la papelera',
					'menu_name'          => 'Procedimientos',
				),
				'public'          => true,
				'hierarchical'    => false,
				'show_in_menu'    => true,
				'menu_position'   => 21,
				'menu_icon'       => 'dashicons-clipboard',
				'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
				'has_archive'     => false,
				'rewrite'         => array(








					'slug'       => (string) apply_filters( 'prc_procedure_rewrite_slug', 'procedimiento' ),
					'with_front' => false,
				),
				'capability_type' => array( 'prc_procedure', 'prc_procedures' ),
				'map_meta_cap'    => true,
				'capabilities'    => self::capabilities(),
				'show_in_rest'    => true,
			)
		);
	}










	public static function capabilities(): array {
		return self::cap_map( 'prc_procedure', 'prc_procedures' );
	}








	public static function cap_map( string $one, string $many ): array {
		return array(
			'edit_post'              => 'edit_' . $one,
			'read_post'              => 'read_' . $one,
			'delete_post'            => 'delete_' . $one,
			'edit_posts'             => 'edit_' . $many,
			'edit_others_posts'      => 'edit_others_' . $many,
			'publish_posts'          => 'publish_' . $many,
			'read_private_posts'     => 'read_private_' . $many,
			'delete_posts'           => 'delete_' . $many,
			'delete_private_posts'   => 'delete_private_' . $many,
			'delete_published_posts' => 'delete_published_' . $many,
			'delete_others_posts'    => 'delete_others_' . $many,
			'edit_private_posts'     => 'edit_private_' . $many,
			'edit_published_posts'   => 'edit_published_' . $many,
		);
	}












	public static function grant_caps_to_roles(): void {
		$maps = array(
			self::POST_TYPE                => self::capabilities(),
			ApplicationPostType::POST_TYPE => ApplicationPostType::capabilities(),
		);

		$every = array_keys( self::capabilities() );




		$manager_procedures = array(
			'edit_post',
			'read_post',
			'delete_post',
			'edit_posts',
			'edit_others_posts',
			'publish_posts',
			'read_private_posts',
			'delete_posts',
			'delete_published_posts',
			'edit_published_posts',
		);




		$manager_applications = array(
			'edit_post',
			'read_post',
			'delete_post',
			'edit_posts',
			'edit_others_posts',
			'read_private_posts',
			'delete_posts',
			'delete_others_posts',
			'delete_private_posts',
			'delete_published_posts',
			'edit_private_posts',
			'edit_published_posts',
		);

		$by_role = array(
			'editor'        => array(
				self::POST_TYPE                => $manager_procedures,
				ApplicationPostType::POST_TYPE => $manager_applications,
			),
			'prc_manager'   => array(
				self::POST_TYPE                => $manager_procedures,
				ApplicationPostType::POST_TYPE => $manager_applications,
			),
			'administrator' => array_fill_keys( array_keys( $maps ), $every ),
		);

		foreach ( $by_role as $slug => $por_tipo ) {
			$role = get_role( $slug );
			if ( ! $role ) {
				continue;
			}
			foreach ( $maps as $tipo => $map ) {
				foreach ( $por_tipo[ $tipo ] as $key ) {
					if ( isset( $map[ $key ] ) && ! $role->has_cap( $map[ $key ] ) ) {
						$role->add_cap( $map[ $key ] );
					}
				}
			}
		}
	}
}








namespace Prc\PostType;













final class ApplicationPostType {

	public const POST_TYPE = 'prc_application';






	public static function register(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'          => 'Solicitudes',
					'singular_name' => 'Solicitud',
					'edit_item'     => 'Solicitud',
					'search_items'  => 'Buscar solicitudes',
					'not_found'     => 'No se encontraron solicitudes',
					'menu_name'     => 'Solicitudes',
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'show_ui'             => true,
				'show_in_menu'        => 'edit.php?post_type=' . ProcedurePostType::POST_TYPE,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => false,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => array( 'title' ),
				'capability_type'     => array( 'prc_application', 'prc_applications' ),
				'map_meta_cap'        => true,
				'capabilities'        => self::capabilities(),
				'delete_with_user'    => false,
			)
		);
	}






	public static function capabilities(): array {
		return ProcedurePostType::cap_map( 'prc_application', 'prc_applications' );
	}
}








namespace Prc\Taxonomy;

use Prc\Access\ProcedureAccess;
use Prc\PostType\ProcedurePostType;











final class ProcedureTaxonomies {




	public const AREA = 'prc_area';




	public const COURSE = 'prc_course';


	public const EMAIL    = 'prc_scope_email';
	public const IMAGE_ID = 'prc_scope_image_id';






	public static function register(): void {
		$area_args                = self::args( 'Ámbitos', 'Ámbito', true );
		$area_args['meta_box_cb'] = array( self::class, 'area_meta_box' );
		register_taxonomy( self::AREA, ProcedurePostType::POST_TYPE, $area_args );
		register_taxonomy( self::COURSE, ProcedurePostType::POST_TYPE, self::args( 'Cursos escolares', 'Curso escolar', false ) );
		register_term_meta(
			self::AREA,
			self::EMAIL,
			array(
				'type'              => 'string',
				'single'            => true,
				'sanitize_callback' => 'sanitize_email',
				'auth_callback'     => array( self::class, 'can_edit_meta' ),
			)
		);
		register_term_meta(
			self::AREA,
			self::IMAGE_ID,
			array(
				'type'              => 'integer',
				'single'            => true,
				'sanitize_callback' => 'absint',
				'auth_callback'     => array( self::class, 'can_edit_meta' ),
			)
		);
		add_action( self::AREA . '_add_form_fields', array( self::class, 'add_fields' ) );
		add_action( self::AREA . '_edit_form_fields', array( self::class, 'edit_fields' ) );
		add_action( 'created_' . self::AREA, array( self::class, 'save_fields' ) );
		add_action( 'edited_' . self::AREA, array( self::class, 'save_fields' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_media' ) );
		add_action( 'admin_notices', array( self::class, 'scope_notice' ) );
		add_filter( 'rest_' . self::AREA . '_query', array( self::class, 'rest_area_query' ) );
	}







	public static function rest_area_query( array $args ): array {
		$user_id = get_current_user_id();
		if ( $user_id > 0 && user_can( $user_id, 'edit_prc_procedures' ) && ! ProcedureAccess::can_edit_all_areas( $user_id ) ) {
			$allowed         = ProcedureAccess::scope_areas( $user_id );
			$args['include'] = array() === $allowed ? array( 0 ) : $allowed;
		}
		return $args;
	}








	public static function area_options( int $user_id = 0, bool $all = false ): array {
		$user_id = $user_id > 0 ? $user_id : get_current_user_id();
		$allowed = $all || ProcedureAccess::can_edit_all_areas( $user_id ) ? null : ProcedureAccess::scope_areas( $user_id );
		$terms   = get_terms(
			array(
				'taxonomy'   => self::AREA,
				'hide_empty' => false,
			)
		);
		if ( ! is_array( $terms ) ) {
			return array();
		}
		$names   = wp_list_pluck( $terms, 'name', 'term_id' );
		$options = array();
		foreach ( $terms as $term ) {
			$id = (int) $term->term_id;
			if ( null !== $allowed && ! in_array( $id, $allowed, true ) ) {
				continue;
			}
			$path           = array_reverse( get_ancestors( $id, self::AREA, 'taxonomy' ) );
			$path[]         = $id;
			$options[ $id ] = implode(
				' › ',
				array_map(
					static function ( $part ) use ( $names ) {
						return $names[ $part ] ?? '';
					},
					$path
				)
			);
		}
		return $options;
	}







	public static function area_meta_box( $post ): void {
		$selected = $post instanceof \WP_Post ? ProcedureAccess::post_areas( $post->ID ) : array();
		$allowed  = ProcedureAccess::can_edit_all_areas() ? $selected : ProcedureAccess::scope_areas();
		echo '<div class="inside"><p>Seleccione los ámbitos que convoca su perfil.</p><input type="hidden" name="prc_area_present" value="1" />';
		foreach ( self::area_options() as $id => $label ) {
			printf( '<label><input type="checkbox" name="tax_input[%1$s][]" value="%2$d"%3$s /> %4$s</label><br />', esc_attr( self::AREA ), (int) $id, in_array( $id, $selected, true ) ? ' checked="checked"' : '', esc_html( $label ) );
		}
		$foreign = array_diff( $selected, $allowed );
		if ( $foreign ) {
			$labels = self::area_options( 0, true );
			echo '<p>Otros ámbitos organizadores (solo lectura):</p><ul>';
			foreach ( $foreign as $id ) {
				printf( '<li>%s</li>', esc_html( $labels[ $id ] ?? '' ) );
			}
			echo '</ul><p>Se conservarán al guardar. Solo administración o una persona de ese ámbito puede modificar su participación.</p>';
		}
		echo '</div>';
	}


	public static function can_edit_meta(): bool {
		return current_user_can( ProcedureAccess::CAP_MANAGE ) || current_user_can( 'manage_options' );
	}


	public static function add_fields(): void {
		wp_nonce_field( 'prc_scope_meta', 'prc_scope_meta_nonce' );
		echo '<div class="form-field"><label for="prc_scope_email">Correo del ámbito</label><input type="email" id="prc_scope_email" name="prc_scope_email" value="" /></div>';
		echo '<div class="form-field"><label for="prc_scope_image_id">Imagen del ámbito</label><input type="hidden" id="prc_scope_image_id" name="prc_scope_image_id" value="0" /><button type="button" class="button prc-scope-choose-image">Elegir imagen</button> <button type="button" class="button prc-scope-remove-image">Quitar imagen</button><span class="prc-scope-image-name"></span></div>';
	}






	public static function edit_fields( $term ): void {
		if ( ! ( $term instanceof \WP_Term ) || self::AREA !== $term->taxonomy ) {
			return;
		}
		wp_nonce_field( 'prc_scope_meta', 'prc_scope_meta_nonce' );
		printf( '<tr class="form-field"><th><label for="prc_scope_email">Correo del ámbito</label></th><td><input type="email" id="prc_scope_email" name="prc_scope_email" value="%s" /></td></tr>', esc_attr( (string) get_term_meta( $term->term_id, self::EMAIL, true ) ) );
		$image_id = absint( get_term_meta( $term->term_id, self::IMAGE_ID, true ) );
		printf( '<tr class="form-field"><th><label for="prc_scope_image_id">Imagen del ámbito</label></th><td><input type="hidden" id="prc_scope_image_id" name="prc_scope_image_id" value="%1$s" /><button type="button" class="button prc-scope-choose-image">Elegir imagen</button> <button type="button" class="button prc-scope-remove-image">Quitar imagen</button> <span class="prc-scope-image-name">%2$s</span></td></tr>', esc_attr( (string) $image_id ), $image_id ? esc_html( get_the_title( $image_id ) . ' (ID ' . $image_id . ')' ) : 'Sin imagen' );
	}


	public static function enqueue_media(): void {
		$screen = get_current_screen();
		if ( ! $screen || self::AREA !== $screen->taxonomy ) {
			return;
		}
		wp_enqueue_media();
		wp_add_inline_script( 'media-views', 'document.addEventListener("click", function (event) { const choose = event.target.closest(".prc-scope-choose-image"); const remove = event.target.closest(".prc-scope-remove-image"); if (!choose && !remove) return; const field = document.getElementById("prc_scope_image_id"); const name = document.querySelector(".prc-scope-image-name"); if (remove) { field.value = "0"; name.textContent = ""; return; } const frame = wp.media({ title: "Imagen del ámbito", library: { type: "image" }, multiple: false }); frame.on("select", function () { const attachment = frame.state().get("selection").first().toJSON(); field.value = attachment.id; name.textContent = attachment.filename; }); frame.open(); });' );
	}






	public static function save_fields( int $term_id ): void {
		$term = get_term( $term_id, self::AREA );
		if ( ! ( $term instanceof \WP_Term ) || ! self::can_edit_meta() || ! isset( $_POST['prc_scope_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['prc_scope_meta_nonce'] ) ), 'prc_scope_meta' ) ) {
			return;
		}
		$errors = array();
		if ( isset( $_POST[ self::EMAIL ] ) ) {
			$raw_email = trim( sanitize_text_field( wp_unslash( $_POST[ self::EMAIL ] ) ) );
			$email     = sanitize_email( $raw_email );
			if ( '' === $raw_email ) {
				delete_term_meta( $term_id, self::EMAIL );
			} elseif ( is_email( $email ) ) {
				update_term_meta( $term_id, self::EMAIL, $email );
			} else {
				$errors[] = 'El correo del ámbito no es válido; se ha conservado el anterior.';
			}
		}
		if ( isset( $_POST[ self::IMAGE_ID ] ) ) {
			$raw_image = sanitize_text_field( wp_unslash( $_POST[ self::IMAGE_ID ] ) );
			$image_id  = absint( $raw_image );
			if ( '0' === $raw_image ) {
				delete_term_meta( $term_id, self::IMAGE_ID );
			} elseif ( wp_attachment_is_image( $image_id ) ) {
				update_term_meta( $term_id, self::IMAGE_ID, $image_id );
			} else {
				$errors[] = 'La imagen del ámbito no es válida; se ha conservado la anterior.';
			}
		}
		if ( $errors ) {
			set_transient( 'prc_scope_error_' . get_current_user_id(), implode( ' ', $errors ), 60 );
		}
	}


	public static function scope_notice(): void {
		$screen = get_current_screen();
		if ( ! $screen || self::AREA !== $screen->taxonomy || ! self::can_edit_meta() ) {
			return;
		}
		$key     = 'prc_scope_error_' . get_current_user_id();
		$message = get_transient( $key );
		if ( ! is_string( $message ) ) {
			return;
		}
		delete_transient( $key );
		printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $message ) );
	}









	private static function args( string $plural, string $singular, bool $hierarchical ): array {
		return array(
			'labels'             => array(
				'name'          => $plural,
				'singular_name' => $singular,
				'search_items'  => 'Buscar en ' . $plural,
				'all_items'     => 'Todos: ' . $plural,
				'edit_item'     => 'Editar ' . $singular,
				'update_item'   => 'Actualizar ' . $singular,
				'add_new_item'  => 'Añadir ' . $singular,
				'new_item_name' => 'Nombre de ' . $singular,
				'not_found'     => 'No se encontró ninguna coincidencia',
				'menu_name'     => $plural,
			),
			'public'             => true,
			'hierarchical'       => $hierarchical,
			'show_admin_column'  => true,
			'show_in_rest'       => true,
			'show_in_quick_edit' => false,
			'capabilities'       => array(
				'manage_terms' => ProcedureAccess::CAP_MANAGE,
				'edit_terms'   => ProcedureAccess::CAP_MANAGE,
				'delete_terms' => ProcedureAccess::CAP_MANAGE,
				'assign_terms' => ProcedureAccess::CAP_MANAGE_PROCEDURES,
			),
		);
	}
}








namespace Prc\PublicFront;














final class Assets {



















	private const FONTS = array(
		'prc-fuentes' => 'https://fonts.googleapis.com/css2?family=Days+One&family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap',
		'prc-iconos'  => 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=block',
	);






	private static $inline = array();







	public static function set_inline( array $assets ): void {
		self::$inline = $assets;
	}






	public static function register(): void {

		add_action( 'init', array( self::class, 'register_assets' ), 20 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'register_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_app' ), 100 );
		add_filter( 'body_class', array( self::class, 'body_class' ) );
		add_filter( 'style_loader_tag', array( self::class, 'font_tag' ), 10, 2 );
	}











	public static function register_assets(): void {
		if ( wp_style_is( 'prc-app', 'registered' ) ) {
			return;
		}


		foreach ( self::fonts() as $handle => $url ) {
			wp_register_style( $handle, $url, array(), null );
		}



		wp_register_style( 'prc-app', false, array_keys( self::fonts() ), null );
		foreach ( self::files( 'css' ) as $rel ) {
			wp_add_inline_style( 'prc-app', self::contents( $rel ) );
		}

		wp_register_script( 'prc-app', false, array(), null, true );
		foreach ( self::files( 'js' ) as $rel ) {
			wp_add_inline_script( 'prc-app', self::contents( $rel ) );
		}

	}










	public static function fonts(): array {





		return array_filter( (array) apply_filters( 'prc_fonts', self::FONTS ) );
	}








	public static function font_tag( string $tag, string $handle ): string {
		if ( ! isset( self::fonts()[ $handle ] ) ) {
			return $tag;
		}
		return str_replace( ' href=', ' referrerpolicy="no-referrer" href=', $tag );
	}












	public static function files( string $tipo ): array {
		$rel = array();
		foreach ( array_keys( self::$inline ) as $clave ) {
			if ( 0 === strpos( $clave, $tipo . '/' ) ) {
				$rel[] = $clave;
			}
		}
		if ( array() === $rel ) {
			foreach ( (array) glob( dirname( __DIR__, 3 ) . '/assets/' . $tipo . '/*.' . $tipo ) as $ruta ) {
				$rel[] = $tipo . '/' . basename( (string) $ruta );
			}
		}
		sort( $rel );

		$armazon = $tipo . '/prc-app.' . $tipo;
		if ( in_array( $armazon, $rel, true ) ) {
			$rel = array_merge( array( $armazon ), array_values( array_diff( $rel, array( $armazon ) ) ) );
		}
		return $rel;
	}











	public static function enqueue_app(): void {
		if ( Shell::is_app_page() ) {
			self::enqueue();
		}
	}






	public static function enqueue(): void {
		self::register_assets();
		wp_enqueue_style( 'prc-app' );
		wp_enqueue_script( 'prc-app' );
	}












	public static function has_bootstrap(): bool {





		return (bool) apply_filters( 'prc_has_bootstrap', false );
	}







	public static function body_class( array $classes ): array {
		if ( ! self::has_bootstrap() ) {
			$classes[] = 'prc-sin-bootstrap';
		}
		return $classes;
	}







	public static function alert_class( string $tono = 'info' ): string {
		$tonos = array( 'info', 'warning', 'success', 'danger' );
		$tono  = in_array( $tono, $tonos, true ) ? $tono : 'info';
		return sprintf( 'prc-aviso prc-aviso-%1$s alert alert-%1$s', $tono );
	}











	public static function button_class( bool $primary = false ): string {
		return $primary
			? 'prc-btn prc-btn-primary btn btn-primary'
			: 'prc-btn btn btn-light';
	}







	public static function state_class( string $estado ): string {
		return sprintf(
			'prc-state prc-state-%s',
			sanitize_html_class( '' !== $estado ? $estado : 'na' )
		);
	}







	public static function contents( string $rel ): string {
		if ( isset( self::$inline[ $rel ] ) ) {
			return self::$inline[ $rel ];
		}

		$path = dirname( __DIR__, 3 ) . '/assets/' . $rel;
		if ( ! is_readable( $path ) ) {
			return '';
		}


		return (string) file_get_contents( $path );
	}
}








namespace Prc\PublicFront;






final class ExitSignal extends \RuntimeException {






	public $url = '';






	public function __construct( string $url = '' ) {
		parent::__construct( '' === $url ? 'Salida sin redirección' : 'Redirección a ' . $url );
		$this->url = $url;
	}
}








namespace Prc\PublicFront;

use Prc\Access\CentreScope;
use Prc\Access\ProcedureAccess;
use Prc\Admin\Settings;
use Prc\Domain\CentreCatalog;
use Prc\PostType\ProcedurePostType;
use Prc\PublicFront\View\ProcedureChrome;
use Prc\Taxonomy\ProcedureTaxonomies;















final class Shell {






	public const SLUGS = array(
		'home'      => 'procedimientos',
		'workspace' => 'gestion-de-procedimientos',
		'editor'    => 'editar-procedimiento',
		'apply'     => 'solicitud',
		'mine'      => 'mi-centro',
	);











	public const SHORTCODES = array(
		'home'      => 'prc_home',
		'workspace' => 'prc_workspace',
		'editor'    => 'prc_editor',
		'apply'     => 'prc_apply',
		'mine'      => 'prc_mine',
	);




	public const SECTION_PROCEDURE = 'procedure';






	public const PUBLIC_SECTIONS = array( 'home', self::SECTION_PROCEDURE );




	public const ARG_PROCEDURE = 'procedimiento';






	private const CORE_ASSETS = array(
		'wp-block-library',
		'wp-block-library-theme',
		'global-styles',
		'classic-theme-styles',
		'wp-emoji-styles',
	);






	public static function register(): void {
		add_filter( 'body_class', array( self::class, 'body_class' ) );
		add_filter( 'show_admin_bar', array( self::class, 'show_admin_bar' ), 100 );


		add_action( 'template_redirect', array( self::class, 'require_login' ) );


		add_action( 'template_redirect', array( self::class, 'render_standalone' ), 20 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'drop_theme_assets' ), 100 );


		add_filter( 'style_loader_tag', array( self::class, 'drop_theme_tag' ), 10, 3 );
		add_filter( 'script_loader_tag', array( self::class, 'drop_theme_tag' ), 10, 3 );
	}










	public static function require_login(): void {
		if ( is_user_logged_in() || ! self::is_app_page() || self::is_public( self::current_section() ) ) {
			return;
		}
		self::leave( wp_login_url( self::current_url() ) );
	}







	public static function is_public( string $section ): bool {
		return in_array( $section, self::PUBLIC_SECTIONS, true );
	}









	public static function render_standalone(): void {
		if ( ! self::is_standalone() || self::SECTION_PROCEDURE === self::current_section() ) {
			return;
		}

		$post = get_post();
		self::serve( (string) apply_filters( 'the_content', $post->post_content ) );
	}






	public static function is_standalone(): bool {





		return (bool) apply_filters( 'prc_standalone_page', true ) && self::is_app_page();
	}












	public static function serve( string $body ): void {
		status_header( 200 );
		nocache_headers();
		self::send_header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );
		echo self::document( $body ); 
		self::leave();
	}







	public static function document( string $body ): string {
		ob_start();
		?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
		<?php


		if ( ! current_theme_supports( 'title-tag' ) ) {
			echo '<title>' . esc_html( wp_get_document_title() ) . '</title>';
		}
		wp_head();


		echo ProcedureChrome::consent(); 
		?>
</head>
<body <?php body_class(); ?>>
		<?php
		wp_body_open();
		echo $body; 
		wp_footer();
		echo ProcedureChrome::analytics(); 
		?>
</body>
</html>
		<?php
		return (string) ob_get_clean();
	}









	public static function drop_theme_tag( string $tag, string $handle, string $src ): string {
		unset( $handle );
		if ( ! self::is_standalone() ) {
			return $tag;
		}
		return self::is_theme_asset( $src ) ? '' : $tag;
	}






	public static function drop_theme_assets(): void {
		if ( ! self::is_standalone() ) {
			return;
		}

		foreach ( array( wp_styles(), wp_scripts() ) as $cola ) {
			foreach ( (array) $cola->queue as $handle ) {
				$src = isset( $cola->registered[ $handle ] ) ? (string) $cola->registered[ $handle ]->src : '';
				if ( '' !== $src && self::is_theme_asset( $src ) ) {
					$cola->dequeue( $handle );
				}
			}
		}




		foreach ( self::CORE_ASSETS as $handle ) {
			wp_dequeue_style( $handle );
		}


		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
	}










	private static function is_theme_asset( string $src ): bool {
		return '' !== $src
			&& ( false !== strpos( $src, '/themes/' ) || false !== strpos( $src, '/et-cache/' ) );
	}
















	public static function show_admin_bar(): bool {
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		if ( ! is_user_logged_in() || ! class_exists( '\WPFront\URE\WPFront_User_Role_Editor_Utils' ) ) {
			return false;
		}

		$clave = 'wpfront_ure_user_switching_stack_' . COOKIEHASH;
		if ( ! isset( $_COOKIE[ $clave ] ) || ! is_string( $_COOKIE[ $clave ] ) ) {
			return false;
		}
		$cookie = sanitize_text_field( wp_unslash( $_COOKIE[ $clave ] ) );


		$sesion = explode( '-', (string) \WPFront\URE\WPFront_User_Role_Editor_Utils::decrypt( $cookie ), 4 );
		if ( count( $sesion ) < 3 || COOKIEHASH !== $sesion[0] || ! ctype_digit( $sesion[1] ) ) {
			return false;
		}
		$edad = time() - (int) $sesion[1];
		if ( $edad < 0 || $edad > 12 * HOUR_IN_SECONDS ) {
			return false;
		}

		$usuarios = explode( ',', $sesion[2] );
		return ctype_digit( $usuarios[0] ) && user_can( (int) $usuarios[0], 'manage_options' );
	}







	public static function body_class( array $classes ): array {
		if ( self::is_app_page() ) {
			$classes[] = 'prc-app';
		}
		return $classes;
	}






	public static function is_app_page(): bool {
		return '' !== self::current_section();
	}






	public static function current_section(): string {
		if ( is_admin() || ! is_singular() ) {
			return '';
		}
		if ( is_singular( ProcedurePostType::POST_TYPE ) ) {
			return self::SECTION_PROCEDURE;
		}
		$post = get_post();
		if ( ! $post instanceof \WP_Post ) {
			return '';
		}



		static $memo = array();
		$contenido   = (string) $post->post_content;
		$clave       = md5( $contenido );
		if ( ! isset( $memo[ $clave ] ) ) {
			$memo[ $clave ] = '';
			foreach ( self::SHORTCODES as $seccion => $codigo ) {
				if ( has_shortcode( $contenido, $codigo ) ) {
					$memo[ $clave ] = $seccion;
					break;
				}
			}
		}
		return $memo[ $clave ];
	}








	public static function url( string $section, array $args = array() ): string {
		$slug = self::SLUGS[ $section ] ?? '';
		if ( '' === $slug ) {
			return '';
		}











		$slug = (string) apply_filters( 'prc_page_slug', $slug, $section );

		$page = get_page_by_path( $slug );
		if ( ! $page ) {
			return '';
		}
		$url = (string) get_permalink( $page );
		return array() === $args ? $url : add_query_arg( $args, $url );
	}










	public static function sections( int $user_id = 0 ): array {
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
		if ( $user_id <= 0 ) {
			return array();
		}

		$out = array(
			'home' => array(
				'label' => 'Procedimientos',
				'url'   => self::url( 'home' ),
			),
		);
		if ( ProcedureAccess::can_manage_procedures( $user_id ) ) {
			$out['workspace'] = array(
				'label' => 'Gestión',
				'url'   => self::url( 'workspace' ),
			);
		}
		if ( ProcedureAccess::can_apply( $user_id ) ) {
			$out['mine'] = array(
				'label' => 'Mi centro',
				'url'   => self::url( 'mine' ),
			);
		}



		foreach ( $out as $clave => $seccion ) {
			if ( '' === $seccion['url'] ) {
				unset( $out[ $clave ] );
			}
		}

		return $out;
	}











	public static function profile( int $user_id = 0 ): array {
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
		if ( $user_id > 0 && ProcedureAccess::is_manager( $user_id ) ) {
			return array(
				'role'  => 'Administración',
				'scope' => 'Todos los ámbitos',
			);
		}
		if ( $user_id > 0 && user_can( $user_id, ProcedureAccess::CAP_MANAGE_PROCEDURES ) ) {
			return array(
				'role'  => 'Gestión de procedimientos',
				'scope' => self::area_names( $user_id ),
			);
		}
		if ( $user_id > 0 && user_can( $user_id, ProcedureAccess::CAP_APPLY ) ) {
			return array(
				'role'  => 'Equipo directivo',
				'scope' => self::centre_name( $user_id ),
			);
		}
		return array(
			'role'  => '',
			'scope' => '',
		);
	}







	private static function area_names( int $user_id ): string {
		$nombres = array();
		foreach ( ProcedureAccess::user_areas( $user_id ) as $term_id ) {
			$term = get_term( $term_id, ProcedureTaxonomies::AREA );
			if ( $term instanceof \WP_Term ) {
				$nombres[] = $term->name;
			}
		}
		return array() === $nombres ? 'Sin ámbito asignado' : implode( ' · ', $nombres );
	}







	public static function centre_name( int $user_id ): string {
		$code = CentreScope::code_for( $user_id );
		if ( '' === $code ) {
			return 'Sin centro asignado';
		}
		$centro = CentreCatalog::find( $code );
		return array() !== $centro && '' !== $centro['name'] ? $centro['name'] . ' (' . $code . ')' : $code;
	}











	private static function initials( string $nombre ): string {
		$partes = preg_split( '/[^\p{L}\p{N}]+/u', trim( $nombre ), -1, PREG_SPLIT_NO_EMPTY );
		$partes = is_array( $partes ) ? $partes : array();
		$letras = '';
		foreach ( array_slice( $partes, 0, 2 ) as $parte ) {
			$letras .= mb_strtoupper( mb_substr( $parte, 0, 1 ) );
		}
		return $letras;
	}
















	public static function render( string $title, string $subtitle, string $body, bool $hero = false ): string {





		if ( ! apply_filters( 'prc_show_chrome', true ) ) {
			return '<div class="prc-hoja">' . $body . '</div>';
		}

		ob_start();
		?>
		<div class="prc-hoja">
			<?php if ( '' !== $title ) : ?>
				<h1 class="<?php echo $hero ? 'prc-hero' : 'prc-h1'; ?>"><?php echo esc_html( $title ); ?></h1>
			<?php endif; ?>
			<?php if ( '' !== $subtitle ) : ?>
				<p class="prc-sub"><?php echo esc_html( $subtitle ); ?></p>
			<?php endif; ?>
			<?php echo $body; ?>
		</div>
		<?php
		return self::top() . self::tabs() . (string) ob_get_clean() . self::bottom();
	}










	private static function top(): string {
		$perfil  = self::profile();
		$usuario = wp_get_current_user();
		$inicio  = self::home_url();
		$chrome  = ProcedureChrome::chrome();
		$duenio  = (string) $chrome['owner'];
		$rotulo  = (string) $chrome['org'];

		ob_start();
		?>
		<div class="prc-top">
			<div class="prc-top-fila">
				<?php if ( '' !== $duenio ) : ?>
					<span class="prc-logo" role="img" aria-label="<?php echo esc_attr( $duenio ); ?>"></span>
				<?php endif; ?>
				<?php if ( '' !== $rotulo ) : ?>
					<span class="prc-marca">
						<small><?php echo esc_html( $rotulo ); ?></small>
					</span>
				<?php endif; ?>
				<a class="prc-marca-app" href="<?php echo esc_url( $inicio ); ?>">Procedimientos</a>
				<?php if ( '' !== $perfil['role'] ) : ?>
					<details class="prc-yo">
						<summary>
							<span class="prc-yo-ava"><?php echo esc_html( self::initials( $usuario->display_name ) ); ?></span>
							<span class="prc-yo-txt">
								<span class="prc-yo-n"><?php echo esc_html( $usuario->display_name ); ?> <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg></span>
								<span class="prc-yo-r"><?php echo esc_html( $perfil['role'] ); ?></span>
								<span class="prc-yo-r prc-yo-a"><?php echo esc_html( $perfil['scope'] ); ?></span>
							</span>
						</summary>
						<div class="prc-yo-menu">
							<?php if ( ProcedureAccess::is_manager() ) : ?>
								<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . ProcedurePostType::POST_TYPE . '&page=' . Settings::PAGE ) ); ?>">Ajustes del aplicativo</a>
							<?php endif; ?>
							<a href="<?php echo esc_url( wp_logout_url( $inicio ) ); ?>">Salir</a>
						</div>
					</details>
				<?php elseif ( ! is_user_logged_in() ) : ?>
					<a class="prc-entrar" href="<?php echo esc_url( wp_login_url( self::current_url() ) ); ?>">Acceder</a>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}










	private static function entry( string $label, string $url = '', bool $active = false, bool $group = false ): array {
		return array(
			'label'  => $label,
			'url'    => $url,
			'active' => $active,
			'group'  => $group,
			'items'  => array(),
		);
	}


















	private static function menu( int $user_id = 0 ): array {
		$secciones = self::sections( $user_id );
		if ( array() === $secciones ) {
			return array();
		}
		$activa = self::current_section();
		$menu   = array();

		if ( isset( $secciones['home'] ) ) {
			$menu[] = self::entry( 'Inicio', $secciones['home']['url'], 'home' === $activa );
		}



		if ( isset( $secciones['mine'] ) ) {
			$suyas            = self::entry( 'Sus solicitudes' );
			$suyas['items'][] = self::entry( 'De su centro', $secciones['mine']['url'], 'mine' === $activa );
			$menu[]           = $suyas;
		}




		if ( isset( $secciones['workspace'] ) ) {
			$admin  = self::entry( 'Administrar' );
			$crear  = self::url( 'editor' );
			$dentro = in_array( $activa, array( 'workspace', 'editor' ), true );

			$admin['items'][] = self::entry( 'Gestión de procedimientos', '', false, true );
			if ( '' !== $crear ) {
				$admin['items'][] = self::entry( 'Crear procedimiento', $crear );
			}
			$admin['items'][] = self::entry( 'Gestionar procedimientos', $secciones['workspace']['url'], $dentro );
			$menu[]           = $admin;
		}




		$ayuda = self::entry( 'Ayuda' );
		foreach ( (array) ProcedureChrome::chrome()['footer_links'] as $enlace ) {
			if ( isset( $enlace['label'], $enlace['url'] ) ) {
				$ayuda['items'][] = self::entry( (string) $enlace['label'], (string) $enlace['url'] );
			}
		}
		if ( array() !== $ayuda['items'] ) {
			$menu[] = $ayuda;
		}



		foreach ( $menu as $i => $entrada ) {
			foreach ( $entrada['items'] as $item ) {
				$menu[ $i ]['active'] = $menu[ $i ]['active'] || $item['active'];
			}
		}

		return $menu;
	}










	private static function tabs(): string {
		$menu = self::menu();
		if ( count( $menu ) < 2 ) {
			return '';
		}

		ob_start();
		?>
		<nav class="prc-tabs" aria-label="Secciones">
			<div class="prc-tabs-fila">
				<?php foreach ( $menu as $entrada ) : ?>
					<?php $marca = $entrada['active'] ? ' prc-tab-on' : ''; ?>
					<?php if ( array() === $entrada['items'] ) : ?>
						<a class="prc-tab<?php echo esc_attr( $marca ); ?>"<?php echo $entrada['active'] ? ' aria-current="page"' : ''; ?> href="<?php echo esc_url( $entrada['url'] ); ?>"><?php echo esc_html( $entrada['label'] ); ?></a>
					<?php else : ?>
						<details class="prc-tab-caja">
							<summary class="prc-tab<?php echo esc_attr( $marca ); ?>"><?php echo esc_html( $entrada['label'] ); ?></summary>
							<div class="prc-desplegable">
								<?php foreach ( $entrada['items'] as $item ) : ?>
									<?php if ( $item['group'] ) : ?>
										<p class="prc-desplegable-grupo"><?php echo esc_html( $item['label'] ); ?></p>
									<?php else : ?>
										<a<?php echo $item['active'] ? ' aria-current="page"' : ''; ?> href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a>
									<?php endif; ?>
								<?php endforeach; ?>
							</div>
						</details>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</nav>
		<?php
		return (string) ob_get_clean();
	}








	private static function bottom(): string {
		$chrome = ProcedureChrome::chrome();
		$duenio = (string) $chrome['owner'];
		$hecho  = (string) $chrome['credit'];

		ob_start();
		?>
		<div class="prc-pie"><div>
			<span class="prc-pie-quien">
				<?php if ( '' !== $duenio ) : ?>
					<a href="<?php echo esc_url( '' !== (string) $chrome['owner_url'] ? (string) $chrome['owner_url'] : self::home_url() ); ?>">&copy; <?php echo esc_html( $duenio ); ?></a>
				<?php endif; ?>
				<?php if ( '' !== $hecho ) : ?>
					<span class="prc-pie-credito"><?php echo esc_html( $hecho ); ?></span>
				<?php endif; ?>
			</span>
			<span class="prc-pie-enlaces">
				<?php foreach ( (array) $chrome['footer_links'] as $enlace ) : ?>
					<?php if ( isset( $enlace['label'], $enlace['url'] ) ) : ?>
						<a href="<?php echo esc_url( (string) $enlace['url'] ); ?>" rel="noopener"><?php echo esc_html( (string) $enlace['label'] ); ?></a>
					<?php endif; ?>
				<?php endforeach; ?>
			</span>
		</div></div>
		<?php
		return (string) ob_get_clean();
	}






	private static function home_url(): string {
		$inicio = self::url( 'home' );
		return '' !== $inicio ? $inicio : home_url( '/' );
	}









	private static function current_url(): string {
		$destino = is_singular() ? (string) get_permalink() : '';
		return '' !== $destino ? $destino : home_url( '/' );
	}








	public static function notice( string $type, string $text ): string {
		if ( '' === $text ) {
			return '';
		}
		$tonos = array(
			'ok'    => 'success',
			'aviso' => 'warning',
			'error' => 'danger',
		);
		$tono  = $tonos[ $type ] ?? 'info';
		return '<p class="' . esc_attr( Assets::alert_class( $tono ) ) . '">' . esc_html( $text ) . '</p>';
	}




	public const ADMIN_BOX_WHY = 'Este recuadro solo lo ve quien administra el aplicativo. Ningún otro perfil lo ve ni puede cambiar lo que hay dentro.';















	public static function admin_box( string $titulo, string $cuerpo, string $explicacion = '' ): string {
		$porque = '' !== $explicacion ? $explicacion : self::ADMIN_BOX_WHY;

		ob_start();
		?>
		<section class="prc-solo-admin">
			<p class="prc-solo-admin-marca">
				<svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 1 3 5v6c0 5 3.8 9.7 9 11 5.2-1.3 9-6 9-11V5l-9-4Zm0 6a2 2 0 0 1 2 2v1h.5a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-.5.5h-5a.5.5 0 0 1-.5-.5v-4a.5.5 0 0 1 .5-.5H10V9a2 2 0 0 1 2-2Zm0 1.2A.8.8 0 0 0 11.2 9v1h1.6V9a.8.8 0 0 0-.8-.8Z"/></svg>
				Solo administración
			</p>
			<?php if ( '' !== $titulo ) : ?>
				<h3 class="prc-solo-admin-titulo"><?php echo esc_html( $titulo ); ?></h3>
			<?php endif; ?>
			<p class="prc-solo-admin-porque"><?php echo esc_html( $porque ); ?></p>
			<div class="prc-solo-admin-cuerpo">
				<?php echo $cuerpo; ?>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}












	public static function back_url( string $section = 'workspace' ): string {
		$destino = wp_validate_redirect( (string) wp_get_raw_referer(), '' );
		if ( '' !== $destino ) {
			return $destino;
		}
		$url = self::url( $section );
		return '' !== $url ? $url : home_url( '/' );
	}











	public static function send_header( string $linea ): void {
		if ( ! headers_sent() ) {
			header( $linea );
		}
	}












	public static function leave( string $url = '' ): void {
		if ( apply_filters( 'prc_exit_throws', false, $url ) ) {
			throw new ExitSignal( $url ); 
		}
		if ( '' !== $url ) {
			wp_safe_redirect( $url );
		}
		exit;
	}















	public static function icon( string $nombre ): string {
		$caminos = array(

			'lapiz' => '<path fill="currentColor" d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8.4 17.6l-3.9.9.9-3.9L16.5 3.5Z"/>',

			'ojo'   => '<path fill="currentColor" d="M12 5c-5 0-9 4.5-9 7s4 7 9 7 9-4.5 9-7-4-7-9-7Zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8Zm0-2a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/>',
		);
		if ( ! isset( $caminos[ $nombre ] ) ) {
			return '';
		}
		return '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false">'
			. $caminos[ $nombre ] . '</svg>';
	}






	public static function icon_plus(): string {
		return '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false">'
			. '<path fill="currentColor" d="M12 4a1 1 0 0 1 1 1v6h6a1 1 0 1 1 0 2h-6v6a1 1 0 1 1-2 0v-6H5a1 1 0 1 1 0-2h6V5a1 1 0 0 1 1-1Z"/>'
			. '</svg>';
	}
}








namespace Prc\PublicFront;

use Prc\Access\ProcedureAccess;

























final class EditLock {




	public const FIELD_DO = 'prc_lock_do';




	public const FIELD_POST = 'prc_lock_post';




	public const NONCE_FIELD = 'prc_lock_nonce';




	public const OP_TAKEOVER = 'takeover';









	public static function register(): void {
		add_action( 'init', array( self::class, 'handle' ), 20 );
	}







	public static function nonce_action( int $procedure_id ): string {
		return 'prc_lock_takeover_' . $procedure_id;
	}










	public static function owner( int $post_id ): int {
		$procedure_id = ProcedureAccess::root_id( $post_id );
		if ( $procedure_id <= 0 ) {
			return 0;
		}
		require_once ABSPATH . 'wp-admin/includes/post.php';
		return (int) wp_check_post_lock( $procedure_id );
	}











	public static function require_available( int $post_id ): void {
		$owner = self::owner( $post_id );
		if ( $owner <= 0 ) {
			return;
		}
		wp_die(
			esc_html(
				sprintf(
					'%s está editando este procedimiento ahora mismo, aquí o en el escritorio de WordPress. Vuelva atrás y tome posesión antes de guardar: así no se pisa lo que la otra persona esté escribiendo.',
					self::name( $owner )
				)
			),
			'Edición bloqueada',
			array(
				'response'  => 409,
				'back_link' => true,
			)
		);
	}

















	public static function status( int $post_id, bool $can_edit ): array {
		$procedure_id = ProcedureAccess::root_id( $post_id );
		if ( $procedure_id <= 0 || ! $can_edit ) {
			return self::none();
		}
		$owner = self::owner( $procedure_id );

		return array(
			'procedure_id' => $procedure_id,
			'owner'        => $owner,
			'name'         => $owner > 0 ? self::name( $owner ) : '',


			'lock'         => '',
		);
	}







	public static function claim( array $lock ): array {
		if ( (int) $lock['procedure_id'] <= 0 || (int) $lock['owner'] > 0 ) {
			return $lock;
		}
		require_once ABSPATH . 'wp-admin/includes/post.php';
		$puesto       = wp_set_post_lock( (int) $lock['procedure_id'] );
		$lock['lock'] = is_array( $puesto ) ? implode( ':', $puesto ) : '';

		return $lock;
	}






	public static function none(): array {
		return array(
			'procedure_id' => 0,
			'owner'        => 0,
			'name'         => '',
			'lock'         => '',
		);
	}












	public static function release( int $post_id ): void {
		$procedure_id = ProcedureAccess::root_id( $post_id );
		if ( $procedure_id <= 0 ) {
			return;
		}
		$lock  = (string) get_post_meta( $procedure_id, '_edit_lock', true );
		$trozo = explode( ':', $lock );
		if ( isset( $trozo[1] ) && get_current_user_id() === (int) $trozo[1] ) {
			delete_post_meta( $procedure_id, '_edit_lock', $lock );
		}
	}











	public static function handle(): void {

		$op = sanitize_key( wp_unslash( (string) ( $_POST[ self::FIELD_DO ] ?? '' ) ) );
		if ( self::OP_TAKEOVER !== $op ) {
			return;
		}
		$post_id = absint( wp_unslash( $_POST[ self::FIELD_POST ] ?? 0 ) );
		$nonce   = sanitize_text_field( wp_unslash( (string) ( $_POST[ self::NONCE_FIELD ] ?? '' ) ) );


		$procedure_id = ProcedureAccess::root_id( $post_id );
		if ( $procedure_id <= 0 || false === wp_verify_nonce( $nonce, self::nonce_action( $procedure_id ) ) ) {
			return;
		}

		if ( ! ProcedureAccess::can_edit( get_current_user_id(), $procedure_id ) ) {
			wp_die(
				esc_html( ProcedureAccess::why_not_editable( get_current_user_id(), $procedure_id ) ),
				'Sin permiso',
				array(
					'response'  => 403,
					'back_link' => true,
				)
			);
		}

		require_once ABSPATH . 'wp-admin/includes/post.php';
		wp_set_post_lock( $procedure_id );


		Shell::leave( Shell::back_url( 'workspace' ) );
	}













	public static function render( array $lock ): string {
		$procedure_id = (int) $lock['procedure_id'];
		if ( $procedure_id <= 0 ) {
			return '';
		}
		$owner = (int) $lock['owner'];



		wp_enqueue_script( 'heartbeat' );

		ob_start();
		?>
		<div id="prc-edit-lock"
			data-post-id="<?php echo esc_attr( (string) $procedure_id ); ?>"
			data-lock="<?php echo esc_attr( (string) $lock['lock'] ); ?>"
			data-release-nonce="<?php echo esc_attr( wp_create_nonce( 'update-post_' . $procedure_id ) ); ?>"
			data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
			<dialog id="prc-lock-dialog" class="prc-dialogo prc-dialogo-bloqueo" aria-labelledby="prc-lock-title"<?php echo $owner > 0 ? ' open' : ''; ?>>
				<h2 id="prc-lock-title">Lo está editando otra persona</h2>
				<p>
					<strong id="prc-lock-owner"><?php echo esc_html( $owner > 0 ? (string) $lock['name'] : 'Otra persona' ); ?></strong>
					tiene abierto este procedimiento ahora mismo, aquí o en el escritorio de WordPress.
				</p>
				<p>
					Puede esperar a que termine y consultarlo mientras tanto, o tomar posesión y
					editarlo usted: la otra persona dejará de poder guardar y verá este mismo aviso.
				</p>
				<form class="prc-acciones" method="post" action="">
					<?php wp_nonce_field( self::nonce_action( $procedure_id ), self::NONCE_FIELD ); ?>
					<input type="hidden" name="<?php echo esc_attr( self::FIELD_DO ); ?>" value="<?php echo esc_attr( self::OP_TAKEOVER ); ?>" />
					<input type="hidden" name="<?php echo esc_attr( self::FIELD_POST ); ?>" value="<?php echo esc_attr( (string) $procedure_id ); ?>" />
					<a class="<?php echo esc_attr( Assets::button_class() ); ?>" href="<?php echo esc_url( self::workspace_url() ); ?>">Dejarlo y volver a mis procedimientos</a>
					<button type="submit" class="<?php echo esc_attr( Assets::button_class( true ) ); ?>">Tomar posesión</button>
				</form>
			</dialog>
		</div>
		<?php
		return (string) ob_get_clean();
	}






	private static function workspace_url(): string {
		$url = Shell::url( 'workspace' );
		return '' !== $url ? $url : home_url( '/' );
	}







	private static function name( int $user_id ): string {
		$user = get_userdata( $user_id );
		if ( false === $user || '' === (string) $user->display_name ) {
			return 'Otra persona';
		}
		return (string) $user->display_name;
	}
}








namespace Prc\PublicFront\View;

use Prc\PublicFront\Assets;
use Prc\PublicFront\Shell;










final class PanelParts {










	public const SVG = array(
		'svg'  => array(
			'viewbox'     => true,
			'width'       => true,
			'height'      => true,
			'aria-hidden' => true,
			'focusable'   => true,
		),
		'path' => array(
			'fill' => true,
			'd'    => true,
		),
	);










	public static function icon_link( string $url, string $icono, string $titulo, string $clases = '' ): string {
		if ( '' === $url ) {
			return '';
		}
		$clases = trim( Assets::button_class() . ' prc-mini prc-icono ' . $clases );

		return '<a class="' . esc_attr( $clases ) . '" href="' . esc_url( $url ) . '"'
			. ' title="' . esc_attr( $titulo ) . '" data-bs-toggle="tooltip">'
			. wp_kses( Shell::icon( $icono ), self::SVG )
			. '<span class="screen-reader-text">' . esc_html( $titulo ) . '</span></a>';
	}















	public static function section( string $rotulo, string $contenido, bool $apagado = false, string $motivo = '' ): string {
		$aviso = $apagado && '' !== $motivo
			? '<p class="prc-seccion__bloqueo">' . esc_html( $motivo ) . '</p>'
			: '';

		return '<fieldset class="prc-seccion-campos"' . ( $apagado ? ' disabled' : '' ) . '>'
			. '<legend class="prc-seccion__titulo">' . esc_html( $rotulo ) . '</legend>'
			. $aviso . $contenido
			. '</fieldset>';
	}







	public static function submit( string $rotulo ): string {
		return '<p class="prc-acciones prc-acciones--pie">'
			. '<button class="' . esc_attr( Assets::button_class( true ) . ' prc-envio' ) . '" type="submit">'
			. esc_html( $rotulo ) . '</button></p>';
	}







	public static function day( string $fecha ): string {
		if ( '' === $fecha ) {
			return 'Sin fecha';
		}
		$marca = strtotime( $fecha . ' 12:00:00' );
		if ( false === $marca ) {
			return $fecha;
		}
		return (string) wp_date( 'j \d\e F \d\e Y', $marca );
	}
}








namespace Prc\PublicFront;

use Prc\Access\CentreScope;
use Prc\Domain\CentreCatalog;
use Prc\Domain\ProcedureQuestions;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ApplicationPostType;
use Prc\PostType\ProcedurePostType;














final class Applications {












	public const KEY_FILES = '_files';










	public static function find( int $procedure_id, string $centre_code ): ?int {
		$centre_code = trim( $centre_code );
		if ( $procedure_id <= 0 || '' === $centre_code ) {
			return null;
		}
		$ids = get_posts(
			array(
				'post_type'        => ApplicationPostType::POST_TYPE,
				'post_parent'      => $procedure_id,
				'post_status'      => 'publish',
				'numberposts'      => 1,


				'orderby'          => 'date ID',
				'order'            => 'ASC',
				'fields'           => 'ids',
				'meta_key'         => ApplicationMetaKeys::CENTRE_CODE, 
				'meta_value'       => $centre_code, 
				'suppress_filters' => false,
			)
		);
		return is_array( $ids ) && array() !== $ids ? (int) $ids[0] : null;
	}







	public static function for_procedure( int $procedure_id ): array {
		if ( $procedure_id <= 0 ) {
			return array();
		}
		$posts = get_posts(
			array(
				'post_type'        => ApplicationPostType::POST_TYPE,
				'post_parent'      => $procedure_id,
				'post_status'      => 'publish',
				'numberposts'      => -1,
				'orderby'          => 'date',
				'order'            => 'ASC',
				'suppress_filters' => false,
			)
		);
		return is_array( $posts ) ? $posts : array();
	}







	public static function for_centre( string $centre_code ): array {
		$centre_code = trim( $centre_code );
		if ( '' === $centre_code ) {
			return array();
		}
		$posts = get_posts(
			array(
				'post_type'        => ApplicationPostType::POST_TYPE,
				'post_status'      => 'publish',
				'numberposts'      => -1,
				'orderby'          => 'date',
				'order'            => 'DESC',
				'meta_key'         => ApplicationMetaKeys::CENTRE_CODE, 
				'meta_value'       => $centre_code, 
				'suppress_filters' => false,
			)
		);
		return is_array( $posts ) ? $posts : array();
	}







	public static function count( int $procedure_id ): int {
		if ( $procedure_id <= 0 ) {
			return 0;
		}
		$ids = get_posts(
			array(
				'post_type'        => ApplicationPostType::POST_TYPE,
				'post_parent'      => $procedure_id,
				'post_status'      => 'publish',
				'numberposts'      => -1,
				'fields'           => 'ids',
				'suppress_filters' => false,
			)
		);
		return is_array( $ids ) ? count( $ids ) : 0;
	}







	public static function meta( int $application_id ): array {
		$out = array();
		foreach ( ApplicationMetaKeys::all() as $clave ) {
			$out[ $clave ] = get_post_meta( $application_id, $clave, true );
		}
		$out[ ApplicationMetaKeys::COORDINATOR ] = is_array( $out[ ApplicationMetaKeys::COORDINATOR ] )
			? $out[ ApplicationMetaKeys::COORDINATOR ]
			: array(
				'name'  => '',
				'email' => '',
			);
		$out[ ApplicationMetaKeys::ANSWERS ]     = is_array( $out[ ApplicationMetaKeys::ANSWERS ] ) ? $out[ ApplicationMetaKeys::ANSWERS ] : array();
		return $out;
	}







	public static function questions( int $procedure_id ): array {
		return ProcedureQuestions::sanitize( get_post_meta( $procedure_id, ProcedureMetaKeys::QUESTIONS, true ) );
	}







	public static function spec( int $procedure_id ): array {
		return array(
			'requires_coordinator' => (bool) get_post_meta( $procedure_id, ProcedureMetaKeys::REQUIRES_COORDINATOR, true ),
			'commitments'          => (string) get_post_meta( $procedure_id, ProcedureMetaKeys::COMMITMENTS, true ),
			'questions'            => self::questions( $procedure_id ),
		);
	}










	public static function centre_of( int $user_id = 0 ): array {
		$code   = CentreScope::code_for( $user_id );
		$centro = '' !== $code ? CentreCatalog::find( $code ) : array();
		return array(
			'code'      => $code,
			'name'      => (string) ( $centro['name'] ?? '' ),
			'ownership' => (string) ( $centro['ownership'] ?? '' ),
		);
	}

















	public static function save( int $procedure_id, int $user_id, array $data ): int {
		if ( $procedure_id <= 0 || $user_id <= 0 || ProcedurePostType::POST_TYPE !== get_post_type( $procedure_id ) ) {
			return 0;
		}
		$centro = self::centre_of( $user_id );
		if ( '' === $centro['code'] ) {
			return 0;
		}

		$titulo = self::title( $centro, $procedure_id );
		$id     = self::find( $procedure_id, $centro['code'] );
		if ( null === $id ) {
			$id = wp_insert_post(
				array(
					'post_type'   => ApplicationPostType::POST_TYPE,
					'post_parent' => $procedure_id,
					'post_author' => $user_id,
					'post_status' => 'publish',
					'post_title'  => wp_slash( $titulo ),
				),
				true
			);
		} else {
			$id = wp_update_post(
				array(
					'ID'         => $id,
					'post_title' => wp_slash( $titulo ),
				),
				true
			);
		}
		if ( is_wp_error( $id ) || ! $id ) {
			return 0;
		}
		$id = (int) $id;



		$metas = array(
			ApplicationMetaKeys::CENTRE_CODE        => $centro['code'],
			ApplicationMetaKeys::CENTRE_NAME        => $centro['name'],
			ApplicationMetaKeys::APPLICANT_POSITION => (string) ( $data['position'] ?? '' ),
			ApplicationMetaKeys::COORDINATOR        => (array) ( $data['coordinator'] ?? array() ),
			ApplicationMetaKeys::ANSWERS            => (array) ( $data['answers'] ?? array() ),
			ApplicationMetaKeys::REVIEW_STATE       => ApplicationMetaKeys::REVIEW_SUBMITTED,
		);
		foreach ( $metas as $clave => $valor ) {
			update_post_meta( $id, $clave, wp_slash( $valor ) );
		}
		return $id;
	}












	public static function review( int $application_id, string $state, string $note ): array {
		if ( ApplicationPostType::POST_TYPE !== get_post_type( $application_id ) ) {
			return self::refused( 'application' );
		}
		if ( ! isset( ApplicationMetaKeys::review_states()[ $state ] ) ) {
			return self::refused( 'state' );
		}
		$note = trim( $note );
		if ( '' === $note && in_array( $state, ApplicationMetaKeys::states_needing_note(), true ) ) {
			return self::refused( 'note' );
		}

		update_post_meta( $application_id, ApplicationMetaKeys::REVIEW_STATE, $state );
		update_post_meta( $application_id, ApplicationMetaKeys::REVIEW_NOTE, wp_slash( $note ) );

		return array(
			'ok'    => true,
			'error' => '',
		);
	}







	private static function refused( string $error ): array {
		return array(
			'ok'    => false,
			'error' => $error,
		);
	}








	private static function title( array $centro, int $procedure_id ): string {
		$nombre = '' !== $centro['name'] ? $centro['name'] : $centro['code'];
		return $nombre . ' — ' . (string) get_post_field( 'post_title', $procedure_id );
	}













	public static function columns( array $questions ): array {
		$out = array(
			'centre'            => 'Centro',
			'code'              => 'Código',
			'date'              => 'Fecha',
			'applicant'         => 'Presentada por',
			'email'             => 'Correo',
			'position'          => 'Cargo',
			'coordinator'       => 'Coordinación',
			'coordinator_email' => 'Correo de coordinación',
			'state'             => 'Estado de revisión',
			'note'              => 'Nota',
		);
		foreach ( $questions as $pregunta ) {
			$out[ (string) $pregunta['key'] ] = (string) $pregunta['label'];
		}
		return $out;
	}







	public static function rows( int $procedure_id ): array {
		$preguntas   = self::questions( $procedure_id );
		$filas       = array();
		$solicitudes = self::for_procedure( $procedure_id );

		cache_users( array_unique( array_map( 'intval', wp_list_pluck( $solicitudes, 'post_author' ) ) ) );
		foreach ( $solicitudes as $solicitud ) {
			$filas[] = self::row( $solicitud, $preguntas );
		}
		return $filas;
	}








	public static function row( \WP_Post $application, array $questions ): array {
		$meta   = self::meta( (int) $application->ID );
		$autor  = get_userdata( (int) $application->post_author );
		$coord  = (array) $meta[ ApplicationMetaKeys::COORDINATOR ];
		$estado = (string) $meta[ ApplicationMetaKeys::REVIEW_STATE ];

		$fila = array(
			'id'                => (string) $application->ID,
			'state_key'         => $estado,
			'centre'            => (string) $meta[ ApplicationMetaKeys::CENTRE_NAME ],
			'code'              => (string) $meta[ ApplicationMetaKeys::CENTRE_CODE ],



			'date'              => (string) get_the_date( 'Y-m-d H:i', $application ),
			'date_label'        => (string) get_the_date( 'd-m-Y', $application ),
			'applicant'         => false !== $autor ? (string) $autor->display_name : '',
			'email'             => false !== $autor ? (string) $autor->user_email : '',
			'position'          => (string) ( ApplicationMetaKeys::positions()[ $meta[ ApplicationMetaKeys::APPLICANT_POSITION ] ] ?? '' ),
			'coordinator'       => (string) ( $coord['name'] ?? '' ),
			'coordinator_email' => (string) ( $coord['email'] ?? '' ),
			'state'             => (string) ( ApplicationMetaKeys::review_states()[ $estado ] ?? '' ),
			'note'              => (string) $meta[ ApplicationMetaKeys::REVIEW_NOTE ],
		);


		$respuestas = (array) $meta[ ApplicationMetaKeys::ANSWERS ];
		$documentos = ApplicationFiles::descriptors( (int) $application->ID );
		$adjuntos   = array();
		foreach ( $questions as $pregunta ) {
			$key = (string) $pregunta['key'];




			if ( ProcedureQuestions::TYPE_FILE === (string) ( $pregunta['type'] ?? '' ) ) {
				$descriptor       = $documentos[ $key ] ?? array();
				$fila[ $key ]     = (string) ( $descriptor['name'] ?? '' );
				$adjuntos[ $key ] = array() === $descriptor
					? array()
					: array(
						'application' => (int) $application->ID,
						'id'          => (string) ( $descriptor['id'] ?? '' ),
						'name'        => (string) ( $descriptor['name'] ?? '' ),
					);
				continue;
			}
			$fila[ $key ] = ProcedureQuestions::as_text( $pregunta, $respuestas[ $key ] ?? null );
		}
		$fila[ self::KEY_FILES ] = $adjuntos;
		return $fila;
	}







	public static function csv( int $procedure_id ): string {
		return self::csv_lines( self::columns( self::questions( $procedure_id ) ), self::rows( $procedure_id ) );
	}















	public static function csv_lines( array $columns, array $rows ): string {
		$lineas = array( self::csv_line( array_values( $columns ) ) );
		foreach ( $rows as $fila ) {
			$campos = array();
			foreach ( array_keys( $columns ) as $clave ) {
				$campos[] = (string) ( $fila[ $clave ] ?? '' );
			}
			$lineas[] = self::csv_line( $campos );
		}


		return "\xEF\xBB\xBF" . implode( "\r\n", $lineas ) . "\r\n";
	}







	public static function filename( string $title ): string {
		$base = sanitize_title( $title );
		return 'solicitudes-' . ( '' !== $base ? $base : 'procedimiento' ) . '-' . gmdate( 'Y-m-d' ) . '.csv';
	}







	private static function csv_line( array $campos ): string {
		$fuera = array();
		foreach ( $campos as $campo ) {
			if ( '' !== $campo && false !== strpos( "=+-@\t\r", $campo[0] ) ) {
				$campo = "'" . $campo;
			}
			$fuera[] = '"' . str_replace( '"', '""', $campo ) . '"';
		}
		return implode( ';', $fuera );
	}
}








namespace Prc\PublicFront;

use Prc\Access\ProcedureAccess;
use Prc\Domain\ProcedureQuestions;
use Prc\Meta\ApplicationMetaKeys;
use Prc\PostType\ApplicationPostType;

























final class ApplicationFiles {




	public const FIELD = 'prc_qf';




	public const ARG_APPLICATION = 'prc_app';




	public const ARG_FILE = 'prc_file';




	public const DIR = 'prc-private';








	public const MAX_BYTES = 10 * MB_IN_BYTES;









	private const MODE_CLOSED = 0200;




	private const MODE_OPEN = 0400;









	private const STORED_SHAPE = '#^[a-f0-9]{2}/[a-f0-9]{2}/[a-f0-9]{32}\.[a-z0-9]{1,8}$#';






	public static function register(): void {



		add_action( 'init', array( self::class, 'handle' ), 20 );



		add_action( 'before_delete_post', array( self::class, 'on_delete' ), 10, 2 );
	}














	public static function mimes(): array {
		$mimes = array(
			'pdf'          => 'application/pdf',
			'jpg|jpeg|jpe' => 'image/jpeg',
			'png'          => 'image/png',
			'docx'         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'odt'          => 'application/vnd.oasis.opendocument.text',
		);










		$mimes = apply_filters( 'prc_private_file_mimes', $mimes );
		return is_array( $mimes ) ? $mimes : array();
	}






	public static function max_bytes(): int {
		$servidor = (int) wp_max_upload_size();
		return $servidor > 0 ? min( self::MAX_BYTES, $servidor ) : self::MAX_BYTES;
	}










	public static function root(): string {
		$uploads = wp_upload_dir();
		$base    = isset( $uploads['basedir'] ) && ! $uploads['error'] ? (string) $uploads['basedir'] : '';
		$raiz    = '' === $base ? '' : $base . '/' . self::DIR;






		return rtrim( (string) apply_filters( 'prc_private_files_dir', $raiz ), '/' );
	}




















	public static function submitted( array $questions, array $stored = array() ): array {
		$errores = array();
		$traidos = array();

		foreach ( $questions as $pregunta ) {
			if ( ProcedureQuestions::TYPE_FILE !== ( $pregunta['type'] ?? '' ) ) {
				continue;
			}
			$key     = (string) ( $pregunta['key'] ?? '' );
			$fichero = self::from_request( $key );

			if ( null === $fichero ) {
				if ( ! empty( $pregunta['required'] ) && ! isset( $stored[ $key ] ) ) {
					$errores[] = 'file_missing';
				}
				continue;
			}

			$porque = self::refuse( $fichero );
			if ( '' !== $porque ) {
				$errores[] = $porque;
				continue;
			}
			$traidos[ $key ] = $fichero;
		}

		return array(
			'ok'     => array() === $errores,
			'errors' => $errores,
			'files'  => $traidos,
		);
	}







	private static function from_request( string $question_key ): ?array {


		$campo = isset( $_FILES[ self::FIELD ] ) && is_array( $_FILES[ self::FIELD ] ) ? $_FILES[ self::FIELD ] : array();
		if ( ! isset( $campo['name'][ $question_key ] ) || ! is_scalar( $campo['name'][ $question_key ] ) ) {
			return null;
		}

		$nombre = sanitize_text_field( (string) $campo['name'][ $question_key ] );
		$tmp    = isset( $campo['tmp_name'][ $question_key ] ) ? (string) $campo['tmp_name'][ $question_key ] : '';
		$error  = isset( $campo['error'][ $question_key ] ) ? (int) $campo['error'][ $question_key ] : UPLOAD_ERR_NO_FILE;
		$tamano = isset( $campo['size'][ $question_key ] ) ? (int) $campo['size'][ $question_key ] : 0;


		if ( '' === $nombre && UPLOAD_ERR_NO_FILE === $error ) {
			return null;
		}










		return array(
			'name'     => basename( $nombre ),
			'tmp_name' => $tmp,
			'size'     => $tamano,
			'error'    => $error,
		);
	}












	public static function refuse( array $fichero ): string {
		if ( UPLOAD_ERR_INI_SIZE === $fichero['error'] || UPLOAD_ERR_FORM_SIZE === $fichero['error'] ) {
			return 'file_too_big';
		}
		if ( UPLOAD_ERR_OK !== $fichero['error'] || '' === $fichero['tmp_name'] ) {
			return 'file_broken';
		}
		if ( $fichero['size'] <= 0 || $fichero['size'] > self::max_bytes() ) {
			return 'file_too_big';
		}

		$mimes    = self::mimes();
		$revisado = wp_check_filetype_and_ext( $fichero['tmp_name'], $fichero['name'], $mimes );
		$tipo     = isset( $revisado['type'] ) && is_string( $revisado['type'] ) ? $revisado['type'] : '';
		$ext      = isset( $revisado['ext'] ) && is_string( $revisado['ext'] ) ? $revisado['ext'] : '';

		if ( '' === $tipo || '' === $ext || ! in_array( $tipo, array_values( $mimes ), true ) ) {
			return 'file_type';
		}
		return '';
	}



















	public static function store_all( int $application_id, array $files ): bool {
		if ( $application_id <= 0 ) {
			return false;
		}
		if ( array() === $files ) {
			return true;
		}

		$nuevos = array();
		foreach ( $files as $question_key => $fichero ) {
			$descriptor = self::store( $fichero );
			if ( null === $descriptor ) {
				foreach ( $nuevos as $hecho ) {
					self::erase( $hecho );
				}
				return false;
			}
			$nuevos[ (string) $question_key ] = $descriptor;
		}

		$antes = self::descriptors( $application_id );
		update_post_meta( $application_id, ApplicationMetaKeys::FILES, wp_slash( array_merge( $antes, $nuevos ) ) );



		foreach ( $nuevos as $key => $descriptor ) {
			if ( isset( $antes[ $key ] ) && $antes[ $key ]['stored'] !== $descriptor['stored'] ) {
				self::erase( $antes[ $key ] );
			}
		}
		return true;
	}












	private static function store( array $fichero ): ?array {
		if ( '' !== self::refuse( $fichero ) ) {
			return null;
		}

		$raiz = self::root();
		$fs   = self::filesystem();
		if ( '' === $raiz || null === $fs ) {
			return null;
		}

		$revisado = wp_check_filetype_and_ext( $fichero['tmp_name'], $fichero['name'], self::mimes() );
		$ext      = strtolower( (string) $revisado['ext'] );
		$mime     = (string) $revisado['type'];

		$bytes = $fs->get_contents( $fichero['tmp_name'] );
		if ( ! is_string( $bytes ) || '' === $bytes ) {
			return null;
		}

		$opaco  = bin2hex( random_bytes( 16 ) );
		$stored = substr( $opaco, 0, 2 ) . '/' . substr( $opaco, 2, 2 ) . '/' . $opaco . '.' . $ext;
		$camino = $raiz . '/' . $stored;

		if ( ! self::prepare_dir( dirname( $camino ) ) ) {
			return null;
		}
		if ( ! $fs->put_contents( $camino, $bytes, self::MODE_CLOSED ) ) {
			return null;
		}


		$fs->chmod( $camino, self::MODE_CLOSED );

		return array(
			'id'     => bin2hex( random_bytes( 16 ) ),
			'name'   => sanitize_file_name( $fichero['name'] ),
			'mime'   => $mime,
			'size'   => strlen( $bytes ),
			'sha256' => hash( 'sha256', $bytes ),
			'stored' => $stored,
		);
	}











	private static function prepare_dir( string $dir ): bool {
		$raiz = self::root();
		$fs   = self::filesystem();
		if ( '' === $raiz || null === $fs ) {
			return false;
		}

		if ( ! $fs->is_dir( $raiz ) && ! wp_mkdir_p( $raiz ) ) {
			return false;
		}
		if ( ! $fs->exists( $raiz . '/.htaccess' ) ) {
			$fs->put_contents(
				$raiz . '/.htaccess',
				"# Los documentos de las solicitudes no se sirven directamente (ADR-0028).\n"
					. "<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n"
					. "<IfModule !mod_authz_core.c>\n\tDeny from all\n</IfModule>\n",
				FS_CHMOD_FILE
			);
		}
		if ( ! $fs->exists( $raiz . '/index.php' ) ) {
			$fs->put_contents( $raiz . '/index.php', "<?php\n// Silence is golden.\n", FS_CHMOD_FILE );
		}

		return $fs->is_dir( $dir ) || wp_mkdir_p( $dir );
	}









	public static function descriptors( int $application_id ): array {
		if ( $application_id <= 0 ) {
			return array();
		}
		$datos = get_post_meta( $application_id, ApplicationMetaKeys::FILES, true );
		if ( ! is_array( $datos ) ) {
			return array();
		}

		$out = array();
		foreach ( $datos as $question_key => $descriptor ) {
			if ( is_array( $descriptor ) && isset( $descriptor['id'], $descriptor['stored'] ) ) {
				$out[ (string) $question_key ] = $descriptor;
			}
		}
		return $out;
	}








	public static function find( int $application_id, string $file_id ): ?array {
		if ( ! (bool) preg_match( '/^[a-f0-9]{32}$/', $file_id ) ) {
			return null;
		}
		foreach ( self::descriptors( $application_id ) as $descriptor ) {
			if ( hash_equals( (string) $descriptor['id'], $file_id ) ) {
				return $descriptor;
			}
		}
		return null;
	}










	public static function read( array $descriptor ): ?string {
		$camino = self::path( $descriptor );
		$fs     = self::filesystem();
		if ( '' === $camino || null === $fs || ! $fs->exists( $camino ) ) {
			return null;
		}

		try {
			$fs->chmod( $camino, self::MODE_OPEN );
			$bytes = $fs->get_contents( $camino );
		} finally {
			$fs->chmod( $camino, self::MODE_CLOSED );
		}
		return is_string( $bytes ) ? $bytes : null;
	}











	public static function path( array $descriptor ): string {
		$stored = isset( $descriptor['stored'] ) ? (string) $descriptor['stored'] : '';
		$raiz   = self::root();
		if ( '' === $raiz || ! (bool) preg_match( self::STORED_SHAPE, $stored ) ) {
			return '';
		}
		$camino = $raiz . '/' . $stored;
		return 0 === strpos( $camino, $raiz . '/' ) ? $camino : '';
	}










	public static function on_delete( int $post_id, $post = null ): void {
		$tipo = $post instanceof \WP_Post ? (string) $post->post_type : (string) get_post_type( $post_id );
		if ( ApplicationPostType::POST_TYPE !== $tipo ) {
			return;
		}
		self::delete_all( $post_id );
	}







	public static function delete_all( int $application_id ): void {
		foreach ( self::descriptors( $application_id ) as $descriptor ) {
			self::erase( $descriptor );
		}
		delete_post_meta( $application_id, ApplicationMetaKeys::FILES );
	}







	private static function erase( array $descriptor ): void {
		$camino = self::path( $descriptor );
		$fs     = self::filesystem();
		if ( '' !== $camino && null !== $fs && $fs->exists( $camino ) ) {
			$fs->delete( $camino );
		}
	}













	public static function url( int $application_id, string $file_id ): string {
		return add_query_arg(
			array(
				self::ARG_APPLICATION => $application_id,
				self::ARG_FILE        => $file_id,
			),
			home_url( '/' )
		);
	}













	public static function handle(): void {

		$file_id = isset( $_GET[ self::ARG_FILE ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::ARG_FILE ] ) ) : '';
		if ( '' === $file_id ) {
			return;
		}
		$application_id = isset( $_GET[ self::ARG_APPLICATION ] ) ? absint( wp_unslash( $_GET[ self::ARG_APPLICATION ] ) ) : 0;


		if ( $application_id <= 0
			|| ApplicationPostType::POST_TYPE !== (string) get_post_type( $application_id )
			|| ! ProcedureAccess::can_view_application( get_current_user_id(), $application_id ) ) {
			self::deny();
			return;
		}

		$descriptor = self::find( $application_id, $file_id );
		$bytes      = null === $descriptor ? null : self::read( $descriptor );
		if ( null === $descriptor || null === $bytes ) {
			self::deny( 404, 'Ese documento ya no está.' );
			return;
		}

		foreach ( self::headers( $descriptor, strlen( $bytes ) ) as $linea ) {
			Shell::send_header( $linea );
		}
		echo $bytes; 
		Shell::leave();
	}
















	public static function headers( array $descriptor, int $bytes ): array {
		$nombre = sanitize_file_name( (string) ( $descriptor['name'] ?? '' ) );
		if ( '' === $nombre ) {
			$nombre = 'documento';
		}

		return array(
			'Content-Type: ' . sanitize_mime_type( (string) ( $descriptor['mime'] ?? '' ) ),
			'Content-Disposition: attachment; filename="' . $nombre . '"',
			'Content-Length: ' . $bytes,
			'X-Content-Type-Options: nosniff',
			'Cache-Control: private, no-store',
		);
	}








	private static function deny( int $codigo = 403, string $texto = 'No puede descargar este documento.' ): void {
		status_header( $codigo );
		Shell::send_header( 'Content-Type: text/plain; charset=utf-8' );
		Shell::send_header( 'X-Content-Type-Options: nosniff' );
		Shell::send_header( 'Cache-Control: private, no-store' );
		echo esc_html( $texto );
		Shell::leave();
	}









	public static function why( array $errors ): string {
		$textos = array(
			'file_missing' => 'Falta un documento obligatorio.',
			'file_too_big' => 'El documento es demasiado grande: el máximo son ' . size_format( self::max_bytes() ) . '.',
			'file_type'    => 'Ese tipo de documento no se admite. Se aceptan PDF, JPG, PNG, DOCX y ODT.',
			'file_broken'  => 'El documento no ha llegado completo. Vuelva a adjuntarlo.',
		);

		foreach ( $errors as $error ) {
			if ( isset( $textos[ $error ] ) ) {
				return $textos[ $error ];
			}
		}
		return '';
	}






	private static function filesystem(): ?\WP_Filesystem_Base {
		global $wp_filesystem;
		if ( ! $wp_filesystem instanceof \WP_Filesystem_Base ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}
		return $wp_filesystem instanceof \WP_Filesystem_Base ? $wp_filesystem : null;
	}
}








namespace Prc\PublicFront\View;

use Prc\PublicFront\Shell;












final class HomeView {







	public static function html( array $m ): string {
		ob_start();
		?>
		<div class="prc-portada">
			<section class="prc-banda prc-portada__intro">
				<div class="prc-fila">
					<p class="prc-portada__entrada">Procedimientos a los que un centro educativo puede presentar su solicitud.</p>
					<?php self::filters( (array) $m['filters'] ); ?>
				</div>
			</section>

			<?php if ( array() === (array) $m['groups'] ) : ?>
				<div class="prc-fila">
					<p class="prc-vacio"><?php echo esc_html( (string) $m['empty_text'] ); ?>
						<?php if ( '' !== (string) $m['reset_url'] ) : ?>
							<a href="<?php echo esc_url( (string) $m['reset_url'] ); ?>">Ver todos los ámbitos</a>
						<?php endif; ?>
					</p>
				</div>
			<?php endif; ?>

			<?php foreach ( (array) $m['groups'] as $state => $grupo ) : ?>
				<?php self::group( (string) $state, (array) $grupo ); ?>
			<?php endforeach; ?>
		</div>
		<?php
		return Shell::render( 'Procedimientos para los centros educativos', '', (string) ob_get_clean(), true );
	}










	private static function filters( array $ejes ): void {
		if ( array() === $ejes ) {
			return;
		}
		?>
		<nav class="prc-filtros" aria-label="Filtros de la portada">
			<?php foreach ( $ejes as $eje ) : ?>
				<div class="prc-filtros__eje">
					<span class="prc-filtros__rotulo"><?php echo esc_html( (string) $eje['label'] ); ?></span>
					<ul>
						<?php foreach ( (array) $eje['items'] as $opcion ) : ?>
							<li><a href="<?php echo esc_url( (string) $opcion['url'] ); ?>"<?php echo empty( $opcion['active'] ) ? '' : ' class="is-activo" aria-current="page"'; ?>><?php echo esc_html( (string) $opcion['label'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endforeach; ?>
		</nav>
		<?php
	}








	private static function group( string $state, array $grupo ): void {
		$clase = sanitize_html_class( $state );
		?>
		<section class="prc-banda prc-bloque prc-bloque--<?php echo esc_attr( $clase ); ?> prc-grupo-<?php echo esc_attr( $clase ); ?>">
			<div class="prc-fila">
				<h2 class="prc-bloque__titulo">
					Procedimientos en estado
					<strong class="prc-bloque__rotulo"><?php echo esc_html( (string) $grupo['label'] ); ?></strong>
				</h2>
				<ul class="prc-rejilla">
					<?php foreach ( (array) $grupo['rows'] as $row ) : ?>
						<?php self::card( (array) $row ); ?>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
		<?php
	}










	private static function card( array $row ): void {
		?>
		<li class="prc-proc">
			<a class="prc-proc__enlace" href="<?php echo esc_url( (string) $row['url'] ); ?>">
				<span class="prc-proc__marco">
					<span class="prc-proc__banda" style="--prc-banda: <?php echo esc_attr( (string) $row['header_color'] ); ?>"></span>
				</span>
				<span class="prc-proc__titulo"><?php echo esc_html( (string) $row['title'] ); ?></span>
			</a>
			<?php if ( '' !== (string) $row['state_label'] ) : ?>
				<p class="prc-proc__estado"><?php echo esc_html( (string) $row['state_label'] ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== (string) $row['deadline'] ) : ?>
				<p class="prc-proc__plazo<?php echo empty( $row['urgent'] ) ? '' : ' prc-proc__plazo--urgente'; ?>">
					<span class="material-symbols-outlined" aria-hidden="true">schedule</span>
					<?php echo esc_html( (string) $row['deadline'] ); ?>
				</p>
			<?php endif; ?>
			<?php if ( ! empty( $row['applied'] ) ) : ?>
				<p class="prc-proc__marca">
					<span class="material-symbols-outlined" aria-hidden="true">task_alt</span>
					Su centro ya ha solicitado
				</p>
			<?php endif; ?>
		</li>
		<?php
	}
}








namespace Prc\PublicFront;

use Prc\Domain\DateRange;
use Prc\Domain\ProcedureState;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ProcedurePostType;
use Prc\PublicFront\View\HomeView;
use Prc\Taxonomy\ProcedureTaxonomies;














final class Home {

	public const SHORTCODE = 'prc_home';








	public const VAR_COURSE = 'prc_filter_course';




	public const VAR_AREA = 'prc_filter_area';








	public const VAR_STATE = 'prc_filter_state';






	public const ORDER = array(
		ProcedureMetaKeys::STATE_OPEN,
		ProcedureMetaKeys::STATE_AMENDMENT,
		ProcedureMetaKeys::STATE_UPCOMING,
		ProcedureMetaKeys::STATE_CLOSED,
		ProcedureMetaKeys::STATE_RESOLVED,
		ProcedureMetaKeys::STATE_ARCHIVED,
	);






	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'render' ) );
	}







	public static function input( string $key ): int {

		return isset( $_GET[ $key ] ) && is_string( $_GET[ $key ] ) ? max( 0, (int) sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) ) : 0;
	}






	public static function state(): string {

		$crudo = isset( $_GET[ self::VAR_STATE ] ) && is_string( $_GET[ self::VAR_STATE ] ) ? sanitize_key( wp_unslash( $_GET[ self::VAR_STATE ] ) ) : '';
		return in_array( $crudo, self::ORDER, true ) ? $crudo : '';
	}









	public static function url( int $course, int $area = 0, string $state = '' ): string {
		$args = array();
		if ( $course > 0 ) {
			$args[ self::VAR_COURSE ] = (string) $course;
		}
		if ( $area > 0 ) {
			$args[ self::VAR_AREA ] = (string) $area;
		}
		if ( in_array( $state, self::ORDER, true ) ) {
			$args[ self::VAR_STATE ] = $state;
		}
		return Shell::url( 'home', $args );
	}






	public static function model(): array {
		$courses = self::courses();
		$course  = self::input( self::VAR_COURSE );
		if ( ! isset( $courses[ $course ] ) ) {

			$course = array() === $courses ? 0 : (int) array_key_first( $courses );
		}

		$rows  = self::collect( $course );
		$areas = self::options( $rows );
		$area  = self::input( self::VAR_AREA );
		if ( ! isset( $areas[ $area ] ) ) {
			$area = 0;
		}
		if ( $area > 0 ) {
			$rows = array_values(
				array_filter(
					$rows,
					static function ( array $row ) use ( $area ): bool {
						return isset( $row['areas'][ $area ] );
					}
				)
			);
		}

		$groups = array();
		foreach ( self::ORDER as $state ) {
			$del_estado = array_values(
				array_filter(
					$rows,
					static function ( array $row ) use ( $state ): bool {
						return $state === $row['state'];
					}
				)
			);
			if ( array() !== $del_estado ) {
				$groups[ $state ] = array(
					'label' => ProcedureState::label( $state ),
					'rows'  => $del_estado,
				);
			}
		}



		$states = array();
		foreach ( $groups as $slug => $grupo ) {
			$states[ $slug ] = (string) $grupo['label'];
		}
		$state = self::state();
		if ( ! isset( $states[ $state ] ) ) {
			$state = '';
		}
		if ( '' !== $state ) {
			$groups = array( $state => $groups[ $state ] );
		}

		return array(
			'courses'    => $courses,
			'course'     => $course,
			'areas'      => $areas,
			'area'       => $area,
			'states'     => $states,
			'state'      => $state,
			'filters'    => self::filters( $courses, $course, $areas, $area, $states, $state ),
			'groups'     => $groups,
			'total'      => count( $rows ),
			'empty_text' => $area > 0
				? 'Ningún procedimiento de este ámbito en este curso. Pruebe con otro ámbito o con otro curso.'
				: 'Todavía no hay ningún procedimiento publicado en este curso.',
			'reset_url'  => $area > 0 ? self::url( $course ) : '',
		);
	}
















	private static function filters( array $courses, int $course, array $areas, int $area, array $states, string $state ): array {
		if ( '' === Shell::url( 'home' ) ) {

			return array();
		}

		$ejes = array();

		$items = array();
		foreach ( $courses as $term_id => $nombre ) {
			$items[] = array(
				'label'  => $nombre,
				'url'    => self::url( (int) $term_id, $area, $state ),
				'active' => (int) $term_id === $course,
			);
		}
		if ( count( $items ) > 1 ) {
			$ejes[] = array(
				'label' => 'Curso escolar',
				'items' => $items,
			);
		}



		if ( array() !== $states ) {
			$items = array(
				array(
					'label'  => 'Todos',
					'url'    => self::url( $course, $area ),
					'active' => '' === $state,
				),
			);
			foreach ( $states as $slug => $rotulo ) {
				$items[] = array(
					'label'  => $rotulo,
					'url'    => self::url( $course, $area, $slug ),
					'active' => $slug === $state,
				);
			}
			$ejes[] = array(
				'label' => 'Estado',
				'items' => $items,
			);
		}

		$items = array(
			array(
				'label'  => 'Todos los ámbitos',
				'url'    => self::url( $course, 0, $state ),
				'active' => 0 === $area,
			),
		);
		foreach ( $areas as $term_id => $nombre ) {
			$items[] = array(
				'label'  => $nombre,
				'url'    => self::url( $course, (int) $term_id, $state ),
				'active' => (int) $term_id === $area,
			);
		}
		if ( count( $items ) > 2 ) {
			$ejes[] = array(
				'label' => 'Ámbito',
				'items' => $items,
			);
		}

		return $ejes;
	}







	public static function html( array $model ): string {
		return HomeView::html( $model );
	}







	public static function render( $atts = array() ): string {
		unset( $atts );
		Assets::enqueue();
		return self::html( self::model() );
	}






	private static function courses(): array {
		$terms = get_terms(
			array(
				'taxonomy'   => ProcedureTaxonomies::COURSE,
				'hide_empty' => true,
			)
		);
		if ( ! is_array( $terms ) ) {
			return array();
		}
		$out = wp_list_pluck( $terms, 'name', 'term_id' );
		arsort( $out, SORT_NATURAL );
		return $out;
	}







	private static function collect( int $course ): array {
		$args = array(
			'post_type'           => ProcedurePostType::POST_TYPE,
			'post_status'         => 'publish',



			'posts_per_page'      => 500, 
			'orderby'             => 'title',
			'order'               => 'ASC',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		);
		if ( $course > 0 ) {
			$args['tax_query'] = array( 
				array(
					'taxonomy' => ProcedureTaxonomies::COURSE,
					'field'    => 'term_id',
					'terms'    => array( $course ),
				),
			);
		}

		$solicitados = self::applied_by_centre();

		$rows = array();
		foreach ( get_posts( $args ) as $post ) {
			$rows[] = self::row( $post, $solicitados );
		}




		usort(
			$rows,
			static function ( array $a, array $b ): int {
				$uno = '' !== (string) $a['closes_at'] ? (string) $a['closes_at'] : '9999-12-31';
				$dos = '' !== (string) $b['closes_at'] ? (string) $b['closes_at'] : '9999-12-31';
				return $uno === $dos
					? strnatcasecmp( (string) $a['title'], (string) $b['title'] )
					: strcmp( $uno, $dos );
			}
		);
		return $rows;
	}










	private static function applied_by_centre(): array {
		if ( ! is_user_logged_in() ) {
			return array();
		}
		$centro = Applications::centre_of( get_current_user_id() );
		if ( '' === (string) $centro['code'] ) {
			return array();
		}

		$suyas = array();
		foreach ( Applications::for_centre( (string) $centro['code'] ) as $solicitud ) {
			$suyas[ (int) $solicitud->post_parent ] = true;
		}
		return $suyas;
	}







	private static function days_until( string $ymd ): ?int {
		$ymd = trim( $ymd );
		if ( 1 !== preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $ymd, $trozos ) || ! checkdate( (int) $trozos[2], (int) $trozos[3], (int) $trozos[1] ) ) {
			return null;
		}
		$hoy  = new \DateTimeImmutable( ProcedureState::today() );
		$fin  = new \DateTimeImmutable( $ymd );
		$dias = $hoy->diff( $fin )->format( '%r%a' );
		return (int) $dias;
	}








	private static function row( \WP_Post $post, array $solicitados = array() ): array {
		$id     = (int) $post->ID;
		$state  = ProcedureState::of_post( $id );
		$titulo = trim( (string) $post->post_title );
		$cierra = (string) get_post_meta( $id, ProcedureMetaKeys::CLOSES_AT, true );
		$fin    = ProcedureMetaKeys::STATE_AMENDMENT === $state
			? (string) get_post_meta( $id, ProcedureMetaKeys::AMEND_CLOSES_AT, true )
			: $cierra;
		$quedan = in_array( $state, array( ProcedureMetaKeys::STATE_OPEN, ProcedureMetaKeys::STATE_AMENDMENT ), true )
			? self::days_until( $fin )
			: null;

		return array(
			'id'           => $id,
			'title'        => '' !== $titulo ? $titulo : 'Procedimiento sin título',
			'url'          => (string) get_permalink( $id ),
			'excerpt'      => trim( wp_strip_all_tags( (string) $post->post_excerpt ) ),
			'areas'        => self::terms( $id ),
			'state'        => $state,
			'state_label'  => ProcedureState::label( $state ),
			'closes_at'    => $cierra,
			'days_left'    => $quedan,
			'deadline'     => self::deadline( $quedan ),
			'urgent'       => null !== $quedan && $quedan <= 3,
			'applied'      => isset( $solicitados[ $id ] ),
			'header_color' => ProcedureMetaKeys::header_hex( get_post_meta( $id, ProcedureMetaKeys::HEADER_COLOR, true ) ),
			'dates'        => DateRange::of(
				(string) get_post_meta( $id, ProcedureMetaKeys::OPENS_AT, true ),
				(string) get_post_meta( $id, ProcedureMetaKeys::CLOSES_AT, true )
			),
			'amend_dates'  => DateRange::of(
				(string) get_post_meta( $id, ProcedureMetaKeys::AMEND_OPENS_AT, true ),
				(string) get_post_meta( $id, ProcedureMetaKeys::AMEND_CLOSES_AT, true )
			),
		);
	}







	private static function deadline( ?int $days ): string {
		if ( null === $days || $days < 0 ) {
			return '';
		}
		if ( 0 === $days ) {
			return 'Último día';
		}
		if ( 1 === $days ) {
			return 'Cierra mañana';
		}
		return sprintf( 'Cierra en %d días', $days );
	}







	private static function terms( int $post_id ): array {
		$terms = get_the_terms( $post_id, ProcedureTaxonomies::AREA );
		if ( ! is_array( $terms ) ) {
			return array();
		}
		return wp_list_pluck( $terms, 'name', 'term_id' );
	}









	private static function options( array $rows ): array {
		$out = array();
		foreach ( $rows as $row ) {
			foreach ( $row['areas'] as $term_id => $nombre ) {
				$out[ $term_id ] = $nombre;
			}
		}
		asort( $out );
		return $out;
	}
}








namespace Prc\PublicFront\View;

use Prc\Meta\ProcedureMetaKeys;
use Prc\PublicFront\Assets;















final class ProcedureChrome {









	public const HOOK = 'prc_chrome';







	public const CONSENT_COOKIE = 'cookieconsent_status';










	public static function chrome(): array {
		$defecto = array(

			'owner'          => '',
			'owner_url'      => '',
			'org'            => '',
			'credit'         => '',
			'footer_links'   => array(),


			'privacy_notice' => '',

			'consent_css'    => '',
			'consent_js'     => '',
			'consent_init'   => '',
			'consent_cookie' => self::CONSENT_COOKIE,

			'matomo_api'     => '',
			'matomo_js'      => '',
			'matomo_site'    => 0,
		);






		$puesto = apply_filters( self::HOOK, $defecto );

		return is_array( $puesto ) ? array_merge( $defecto, $puesto ) : $defecto;
	}













	public static function html( array $m ): string {
		ob_start();
		?>
		<div class="prc-ficha">
			<div class="prc-ficha-cab">
				<div class="prc-ficha-izq">
					<h1 class="prc-titulo-proc"><?php echo esc_html( (string) $m['title'] ); ?></h1>
					<?php
					echo self::resolution( $m ); 
					?>
					<?php if ( '' !== (string) $m['image'] ) : ?>
						<img class="prc-ficha-cartel" src="<?php echo esc_url( (string) $m['image'] ); ?>" alt="<?php echo esc_attr( (string) $m['image_alt'] ); ?>" />
					<?php endif; ?>
					<?php if ( '' !== (string) $m['areas'] ) : ?>
						<p class="prc-promotor"><em>Promovido por <?php echo esc_html( (string) $m['areas'] ); ?></em></p>
					<?php endif; ?>
				</div>
				<div class="prc-ficha-der">
					<?php
					echo self::countdown( $m );  
					echo self::summary( $m );    
					?>
				</div>
			</div>

			<?php
			echo self::tabs( $m );   
			echo self::access( $m ); 
			?>

			<hr class="prc-filete" />
			<div class="prc-ficha-pie">
				<p class="prc-ficha-meta">
					<?php foreach ( self::footer_meta( $m ) as $linea ) : ?>
						<?php echo esc_html( $linea ); ?><br />
					<?php endforeach; ?>
				</p>
				<?php if ( '' !== (string) $m['manage_url'] ) : ?>
					<a class="<?php echo esc_attr( Assets::button_class() ); ?>" href="<?php echo esc_url( (string) $m['manage_url'] ); ?>">
						<span class="material-symbols-outlined" aria-hidden="true">edit_note</span>
						Editar procedimiento</a>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}












	private static function resolution( array $m ): string {
		$texto  = (string) $m['excerpt'];
		$enlace = (string) $m['resolution_url'];
		if ( '' === $texto && '' === $enlace ) {
			return '';
		}

		ob_start();
		?>
		<div class="prc-caja prc-resolucion">
			<?php if ( '' !== $texto ) : ?>
				<p class="prc-resolucion__texto"><?php echo esc_html( $texto ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $enlace ) : ?>
				<p class="prc-resolucion__doc">
					<a href="<?php echo esc_url( $enlace ); ?>" title="Acceder al documento de la resolución">
						<span class="material-symbols-outlined" aria-hidden="true">quick_reference_all</span>
						<span class="prc-resolucion__archivo">Resolución del procedimiento</span>
					</a>
				</p>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}
















	private static function countdown( array $m ): string {
		$cuenta = (array) $m['countdown'];
		$plazo  = (string) $m['deadline_label'];
		$fecha  = (string) $m['deadline_text'];

		if ( array() === $cuenta ) {
			if ( empty( $m['expired'] ) || '' === $fecha ) {
				return '';
			}
			ob_start();
			?>
			<p class="prc-aviso prc-aviso-warning prc-cuenta-vencida">
				<span class="material-symbols-outlined" aria-hidden="true">error</span>
				El plazo de <?php echo esc_html( $plazo ); ?> terminó el <?php echo esc_html( $fecha ); ?>
			</p>
			<?php
			return (string) ob_get_clean();
		}

		$bloques = array(
			array( 'dias', 'Día', 'días' ),
			array( 'horas', 'Hrs', 'horas' ),
			array( 'minutos', 'Min', 'minutos' ),
			array( 'segundos', 'Seg', 'segundos' ),
		);

		ob_start();
		?>
		<div class="prc-caja prc-cuenta-caja">
			<p class="prc-cuenta__pie">Quedan para el cierre de <strong><?php echo esc_html( $plazo ); ?></strong></p>
			<div class="prc-cuenta" data-fin="<?php echo esc_attr( (string) $m['deadline_iso'] ); ?>" aria-live="off">
				<?php foreach ( $bloques as $i => $bloque ) : ?>
					<?php if ( $i > 0 ) : ?>
						<div class="prc-cuenta__sep" aria-hidden="true"><p>:</p></div>
					<?php endif; ?>
					<div class="prc-cuenta__bloque">
						<p class="prc-cuenta__valor" data-prc-cuenta="<?php echo esc_attr( $bloque[0] ); ?>"><?php echo esc_html( (string) $cuenta[ $bloque[0] ] ); ?></p>
						<p class="prc-cuenta__rotulo"><span aria-hidden="true"><?php echo esc_html( $bloque[1] ); ?></span><span class="screen-reader-text"><?php echo esc_html( $bloque[2] ); ?></span></p>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}














	private static function summary( array $m ): string {
		$filas = self::summary_rows( $m );
		ob_start();
		?>
		<aside class="prc-aviso prc-aviso-info prc-resumen" aria-labelledby="prc-resumen-tit">
			<h2 id="prc-resumen-tit" class="prc-resumen__tit">Resumen</h2>

			<?php if ( '' !== (string) $m['state_label'] ) : ?>
				<p class="prc-resumen__estado prc-resumen__estado--<?php echo esc_attr( sanitize_html_class( (string) $m['state'] ) ); ?>">
					<?php if ( '' !== (string) $m['state_icon'] ) : ?>
						<span class="material-symbols-outlined" aria-hidden="true"><?php echo esc_html( (string) $m['state_icon'] ); ?></span>
					<?php endif; ?>
					<?php echo esc_html( (string) $m['state_label'] ); ?>
				</p>
			<?php endif; ?>

			<?php if ( array() !== $filas ) : ?>
				<dl class="prc-resumen__datos">
					<?php foreach ( $filas as $fila ) : ?>
						<div>
							<dt><?php echo esc_html( $fila['dt'] ); ?></dt>
							<dd>
								<?php if ( '' !== $fila['mailto'] ) : ?>
									<a href="<?php echo esc_url( 'mailto:' . $fila['mailto'] ); ?>"><?php echo esc_html( $fila['dd'] ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $fila['dd'] ); ?>
								<?php endif; ?>
							</dd>
						</div>
					<?php endforeach; ?>
				</dl>
			<?php endif; ?>

			<?php if ( array() !== (array) $m['links'] ) : ?>
				<ul class="prc-resumen__enlaces">
					<?php foreach ( (array) $m['links'] as $enlace ) : ?>
						<li>
							<a href="<?php echo esc_url( (string) $enlace['url'] ); ?>">
								<span class="material-symbols-outlined" aria-hidden="true"><?php echo esc_html( (string) $enlace['icon'] ); ?></span>
								<?php echo esc_html( (string) $enlace['label'] ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php
			echo self::summary_mine( (array) $m['mine'] ); 
			?>
		</aside>
		<?php
		return (string) ob_get_clean();
	}










	private static function summary_rows( array $m ): array {
		$crudas = array(
			array( 'Curso', (string) $m['courses'], '' ),
			array( 'Inscripción', (string) $m['dates'], '' ),
			array( 'Subsanación', (string) $m['amend_dates'], '' ),
			array( 'Coordinación', empty( $m['requires_coordinator'] ) ? '' : 'Pide una persona coordinadora', '' ),
			array( 'Contacto', (string) $m['contact_email'], (string) $m['contact_email'] ),
		);

		$filas = array();
		foreach ( $crudas as $fila ) {
			if ( '' !== $fila[1] ) {
				$filas[] = array(
					'dt'     => $fila[0],
					'dd'     => $fila[1],
					'mailto' => $fila[2],
				);
			}
		}
		return $filas;
	}











	private static function summary_mine( array $mia ): string {
		if ( array() === $mia ) {
			return '';
		}
		$tiene = ! empty( $mia['has'] );

		ob_start();
		?>
		<p class="prc-resumen__mia">
			<span class="material-symbols-outlined" aria-hidden="true"><?php echo $tiene ? 'task_alt' : 'error'; ?></span>
			<?php if ( $tiene ) : ?>
				Su centro ya ha solicitado
				<?php if ( '' !== (string) $mia['date'] ) : ?>
					el <time datetime="<?php echo esc_attr( (string) $mia['iso'] ); ?>"><?php echo esc_html( (string) $mia['date'] ); ?></time>
				<?php endif; ?>
				<?php if ( '' !== (string) $mia['label'] ) : ?>
					· <strong><?php echo esc_html( (string) $mia['label'] ); ?></strong>
				<?php endif; ?>
				<a href="<?php echo esc_url( (string) $mia['url'] ); ?>">Ver mi solicitud</a>
			<?php else : ?>
				Su centro aún no ha solicitado
			<?php endif; ?>
		</p>
		<?php
		return (string) ob_get_clean();
	}













	private static function tabs( array $m ): string {
		$paneles = (array) $m['tabs'];
		if ( array() === $paneles ) {
			return '';
		}
		$barra = count( $paneles ) > 1;

		ob_start();
		?>
		<div class="prc-tabs-ficha">
			<?php if ( $barra ) : ?>
				<ul class="prc-tabs-ficha__barra">
					<?php foreach ( $paneles as $panel ) : ?>
						<li>
							<a id="tab-<?php echo esc_attr( (string) $panel['id'] ); ?>" href="#<?php echo esc_attr( (string) $panel['id'] ); ?>"><?php echo esc_html( (string) $panel['label'] ); ?></a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<div class="prc-tabs-ficha__paneles">
				<?php foreach ( $paneles as $panel ) : ?>
					<section class="prc-tabs-ficha__panel" id="<?php echo esc_attr( (string) $panel['id'] ); ?>" aria-labelledby="titulo-<?php echo esc_attr( (string) $panel['id'] ); ?>">
						<h2 class="prc-tabs-ficha__titulo" id="titulo-<?php echo esc_attr( (string) $panel['id'] ); ?>"><?php echo esc_html( (string) $panel['label'] ); ?></h2>
						<?php echo wp_kses_post( (string) $panel['html'] ); ?>
					</section>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}












	private static function access( array $m ): string {
		$accion = (array) $m['action'];
		$icono  = (string) ( $accion['icon'] ?? '' );
		$manda  = ! empty( $accion['primary'] );
		$clases = Assets::button_class( $manda ) . ( $manda ? '' : ' prc-btn--sec' );

		ob_start();
		?>
		<div class="prc-accesos">
			<div class="prc-acceso">
				<h2 class="prc-acceso__rotulo">Acceso con la cuenta del equipo directivo</h2>
				<?php if ( '' !== (string) $m['ownership'] ) : ?>
					<p class="prc-acceso__cifra"><?php echo esc_html( (string) $m['ownership'] ); ?></p>
				<?php endif; ?>
				<p class="prc-acceso__texto">Solicita la dirección del centro, validándose con su
					<strong>cuenta personal</strong> en el enlace de acceso de la cabecera.</p>
				<?php if ( '' !== (string) $accion['url'] ) : ?>
					<p class="prc-acceso__accion">
						<a class="<?php echo esc_attr( $clases ); ?>" href="<?php echo esc_url( (string) $accion['url'] ); ?>">
							<?php if ( '' !== $icono ) : ?>
								<span class="material-symbols-outlined" aria-hidden="true"><?php echo esc_html( $icono ); ?></span>
							<?php endif; ?>
							<?php echo esc_html( (string) $accion['label'] ); ?>
						</a>
					</p>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}







	private static function footer_meta( array $m ): array {
		$lineas = array(
			(string) $m['audience'],
			(string) $m['ownership'],
			(string) $m['state_label'],
		);
		return array_values( array_filter( $lineas, static fn( string $l ): bool => '' !== $l ) );
	}














	public static function consent(): string {
		$chrome = self::chrome();
		$html   = '';


		if ( '' !== (string) $chrome['consent_css'] ) {
			$html .= '<link rel="stylesheet" href="' . esc_url( (string) $chrome['consent_css'] ) . '" />' . "\n";
		}
		if ( '' !== (string) $chrome['consent_js'] ) {
			$html .= '<script src="' . esc_url( (string) $chrome['consent_js'] ) . '" defer></script>' . "\n";
		}
		if ( '' !== (string) $chrome['consent_init'] ) {
			$html .= '<script src="' . esc_url( (string) $chrome['consent_init'] ) . '" defer></script>' . "\n";
		}


		return $html;
	}












	public static function analytics(): string {
		$chrome = self::chrome();
		$site   = (int) $chrome['matomo_site'];
		$api    = (string) $chrome['matomo_api'];
		$js_url = (string) $chrome['matomo_js'];






		$encendida = (bool) apply_filters( 'prc_analytics_enabled', 'production' === wp_get_environment_type() );

		if ( ! $encendida || $site <= 0 || '' === $api || '' === $js_url ) {
			return '';
		}

		$cookie = (string) $chrome['consent_cookie'];
		$js     = 'var _paq=window._paq=window._paq||[];'
			. '_paq.push(["requireCookieConsent"]);'
			. ( '' !== $cookie
				? 'if(/(^|;\s*)' . $cookie . '=(allow|dismiss)(;|$)/.test(document.cookie)){_paq.push(["setCookieConsentGiven"]);}'
				: '' )
			. '_paq.push(["setTrackerUrl",' . wp_json_encode( $api, JSON_UNESCAPED_SLASHES ) . ']);'
			. '_paq.push(["setSiteId",' . $site . ']);'
			. '_paq.push(["trackPageView"]);'
			. '_paq.push(["enableLinkTracking"]);';


		$html = '<script id="prc-matomo">' . $js . '</script>' . "\n"
			. '<script src="' . esc_url( $js_url ) . '" async defer></script>' . "\n";

		return $html;
	}







	public static function ownership_label( array $ownership ): string {
		$lista  = ProcedureMetaKeys::ownerships();
		$nombre = array();
		foreach ( $ownership as $clave ) {
			if ( isset( $lista[ $clave ] ) ) {
				$nombre[] = $lista[ $clave ];
			}
		}

		return array() === $nombre || count( $nombre ) === count( $lista ) ? 'Todos los centros' : implode( ' y ', $nombre );
	}
}








namespace Prc\PublicFront;

use Prc\Access\CentreScope;
use Prc\Access\ProcedureAccess;
use Prc\Domain\DateRange;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ProcedurePostType;
use Prc\PublicFront\View\ProcedureChrome;
use Prc\Taxonomy\ProcedureTaxonomies;

















final class ProcedureView {




	public const PRIORITY = 20;






	public static function register(): void {
		add_action( 'template_redirect', array( self::class, 'render' ), self::PRIORITY );
	}






	public static function takes_over(): bool {
		return Shell::is_standalone() && Shell::SECTION_PROCEDURE === Shell::current_section();
	}






	public static function render(): void {
		if ( ! self::takes_over() ) {
			return;
		}
		$m = self::model();
		Assets::enqueue();



		Shell::serve( Shell::render( '', '', ProcedureChrome::html( $m ) ) );
	}







	public static function model( int $post_id = 0 ): array {
		$post_id = $post_id > 0 ? $post_id : (int) get_queried_object_id();
		$m       = self::empty_model();
		if ( $post_id <= 0 || ProcedurePostType::POST_TYPE !== get_post_type( $post_id ) ) {
			return $m;
		}

		$meta = array();
		foreach ( ProcedureMetaKeys::all() as $key ) {
			$meta[ $key ] = get_post_meta( $post_id, $key, true );
		}
		$state     = ProcedureState::of_post( $post_id );
		$user_id   = get_current_user_id();
		$ownership = is_array( $meta[ ProcedureMetaKeys::OWNERSHIP ] ) ? $meta[ ProcedureMetaKeys::OWNERSHIP ] : array();
		$cartel    = (int) get_post_thumbnail_id( $post_id );
		$correos   = is_array( $meta[ ProcedureMetaKeys::CONTACT_EMAILS ] ) ? $meta[ ProcedureMetaKeys::CONTACT_EMAILS ] : array();
		$plazo     = self::deadline( $meta, $state );
		$cuenta    = $plazo['running'] ? self::countdown( $plazo['ymd'] ) : array();
		$mia       = self::mine( $post_id, $user_id );
		$audiencia = ProcedureMetaKeys::in_list( $meta[ ProcedureMetaKeys::AUDIENCE ], ProcedureMetaKeys::audiences(), ProcedureMetaKeys::AUDIENCE_SCHOOLS );
		$contenido = (string) apply_filters( 'the_content', (string) get_post_field( 'post_content', $post_id ) );

		return array_merge(
			$m,
			array(
				'id'                   => $post_id,
				'title'                => (string) get_the_title( $post_id ),
				'url'                  => (string) get_permalink( $post_id ),
				'content'              => $contenido,
				'tabs'                 => self::tabs( $contenido ),
				'excerpt'              => trim( wp_strip_all_tags( (string) get_post_field( 'post_excerpt', $post_id ) ) ),
				'state'                => $state,
				'state_label'          => ProcedureState::label( $state ),
				'state_icon'           => self::state_icon( $state ),
				'dates'                => DateRange::of( (string) $meta[ ProcedureMetaKeys::OPENS_AT ], (string) $meta[ ProcedureMetaKeys::CLOSES_AT ] ),
				'amend_dates'          => DateRange::of( (string) $meta[ ProcedureMetaKeys::AMEND_OPENS_AT ], (string) $meta[ ProcedureMetaKeys::AMEND_CLOSES_AT ] ),



				'deadline_label'       => $plazo['label'],




				'deadline_text'        => DateRange::of( $plazo['ymd'], $plazo['ymd'] ),
				'deadline_iso'         => (string) ( $cuenta['iso'] ?? '' ),
				'countdown'            => $cuenta,
				'expired'              => ! $plazo['running'] && '' !== $plazo['ymd'],
				'areas'                => self::terms( $post_id, ProcedureTaxonomies::AREA ),
				'courses'              => self::terms( $post_id, ProcedureTaxonomies::COURSE ),
				'audience'             => (string) ( ProcedureMetaKeys::audiences()[ $audiencia ] ?? '' ),
				'ownership'            => ProcedureChrome::ownership_label( $ownership ),
				'requires_coordinator' => (bool) $meta[ ProcedureMetaKeys::REQUIRES_COORDINATOR ],


				'contact_emails'       => $correos,
				'contact_email'        => (string) ( $correos[0] ?? '' ),
				'header_color'         => ProcedureMetaKeys::header_hex( $meta[ ProcedureMetaKeys::HEADER_COLOR ] ),
				'links'                => self::links( $meta ),
				'resolution_url'       => trim( (string) $meta[ ProcedureMetaKeys::RESOLUTION_URL ] ),
				'image'                => $cartel > 0 ? (string) wp_get_attachment_image_url( $cartel, 'large' ) : '',
				'image_alt'            => $cartel > 0 ? (string) get_post_meta( $cartel, '_wp_attachment_image_alt', true ) : '',
				'mine'                 => $mia,
				'action'               => self::action( $post_id, $state, $user_id, $mia ),
				'manage_url'           => self::manage_url( $post_id, $user_id ),
			)
		);
	}






	private static function empty_model(): array {
		return array(
			'id'                   => 0,
			'title'                => '',
			'url'                  => '',
			'content'              => '',
			'tabs'                 => array(),
			'excerpt'              => '',
			'state'                => '',
			'state_label'          => '',
			'state_icon'           => '',
			'dates'                => '',
			'amend_dates'          => '',
			'deadline_label'       => '',
			'deadline_text'        => '',
			'deadline_iso'         => '',
			'countdown'            => array(),
			'expired'              => false,
			'areas'                => '',
			'courses'              => '',
			'audience'             => '',
			'ownership'            => '',
			'requires_coordinator' => false,
			'contact_emails'       => array(),
			'contact_email'        => '',
			'header_color'         => ProcedureMetaKeys::header_hex( '' ),
			'links'                => array(),
			'resolution_url'       => '',
			'image'                => '',
			'image_alt'            => '',
			'mine'                 => array(),
			'action'               => self::no_action(),
			'manage_url'           => '',
		);
	}












	private static function state_icon( string $state ): string {
		$iconos = array(
			ProcedureMetaKeys::STATE_OPEN      => 'task_alt',
			ProcedureMetaKeys::STATE_CLOSED    => 'cancel',
			ProcedureMetaKeys::STATE_AMENDMENT => 'contact_support',
			ProcedureMetaKeys::STATE_RESOLVED  => 'view_list',
			ProcedureMetaKeys::STATE_UPCOMING  => 'schedule',
			ProcedureMetaKeys::STATE_DRAFT     => 'reset_brightness',
			ProcedureMetaKeys::STATE_ARCHIVED  => 'tonality',
		);
		return (string) ( $iconos[ $state ] ?? '' );
	}














	private static function deadline( array $meta, string $state ): array {
		$ultimo = static function ( $fin, $principio ): string {
			$fin = trim( (string) $fin );
			return '' !== $fin ? $fin : trim( (string) $principio );
		};

		if ( ProcedureMetaKeys::STATE_AMENDMENT === $state ) {
			return array(
				'ymd'     => $ultimo( $meta[ ProcedureMetaKeys::AMEND_CLOSES_AT ], $meta[ ProcedureMetaKeys::AMEND_OPENS_AT ] ),
				'label'   => 'subsanación',
				'running' => true,
			);
		}
		if ( ProcedureMetaKeys::STATE_OPEN === $state || ProcedureMetaKeys::STATE_CLOSED === $state ) {
			return array(
				'ymd'     => $ultimo( $meta[ ProcedureMetaKeys::CLOSES_AT ], $meta[ ProcedureMetaKeys::OPENS_AT ] ),
				'label'   => 'inscripción',
				'running' => ProcedureMetaKeys::STATE_OPEN === $state,
			);
		}
		return array(
			'ymd'     => '',
			'label'   => '',
			'running' => false,
		);
	}















	private static function countdown( string $ymd ): array {
		$ymd = trim( $ymd );
		if ( 1 !== preg_match( '/^\d{4}-\d{1,2}-\d{1,2}$/', $ymd ) ) {
			return array();
		}
		$fin = \DateTimeImmutable::createFromFormat( 'Y-n-j H:i:s', $ymd . ' 23:59:00', wp_timezone() );
		if ( false === $fin ) {
			return array();
		}

		$queda = max( 0, $fin->getTimestamp() - time() );
		return array(
			'iso'      => $fin->format( 'c' ),

			'dias'     => sprintf( '%03d', intdiv( $queda, DAY_IN_SECONDS ) ),
			'horas'    => sprintf( '%02d', intdiv( $queda % DAY_IN_SECONDS, HOUR_IN_SECONDS ) ),
			'minutos'  => sprintf( '%02d', intdiv( $queda % HOUR_IN_SECONDS, MINUTE_IN_SECONDS ) ),
			'segundos' => sprintf( '%02d', $queda % MINUTE_IN_SECONDS ),
		);
	}












	private static function mine( int $post_id, int $user_id ): array {
		if ( $user_id <= 0 || ! user_can( $user_id, ProcedureAccess::CAP_APPLY ) || ! class_exists( Applications::class ) ) {
			return array();
		}
		$code = CentreScope::code_for( $user_id );
		if ( '' === $code ) {
			return array();
		}

		$url   = Shell::url( 'apply', array( Shell::ARG_PROCEDURE => $post_id ) );
		$vacia = array(
			'has'   => false,
			'state' => '',
			'label' => '',
			'date'  => '',
			'iso'   => '',
			'url'   => $url,
		);

		$solicitud = (int) Applications::find( $post_id, $code );
		if ( $solicitud <= 0 ) {
			return $vacia;
		}

		$estado = (string) get_post_meta( $solicitud, ApplicationMetaKeys::REVIEW_STATE, true );
		return array_merge(
			$vacia,
			array(
				'has'   => true,
				'state' => $estado,
				'label' => (string) ( ApplicationMetaKeys::review_states()[ $estado ] ?? '' ),
				'date'  => (string) get_the_date( 'd-m-Y', $solicitud ),
				'iso'   => (string) get_post_time( 'Y-m-d', false, $solicitud ),
			)
		);
	}
















	private static function tabs( string $html ): array {
		$trozos = preg_split( '#<h2\b[^>]*>(.*?)</h2>#is', trim( $html ), -1, PREG_SPLIT_DELIM_CAPTURE );
		if ( ! is_array( $trozos ) || array() === $trozos ) {
			return array();
		}

		$paneles = array();
		$intro   = trim( (string) array_shift( $trozos ) );
		if ( '' !== trim( wp_strip_all_tags( $intro ) ) ) {
			$paneles[] = array(
				'label' => 'Objetivos:',
				'html'  => $intro,
			);
		}
		while ( array() !== $trozos ) {
			$rotulo = trim( wp_strip_all_tags( (string) array_shift( $trozos ) ) );
			$cuerpo = trim( (string) array_shift( $trozos ) );
			if ( '' === $rotulo || '' === trim( wp_strip_all_tags( $cuerpo ) ) ) {
				continue;
			}
			$paneles[] = array(
				'label' => $rotulo,
				'html'  => $cuerpo,
			);
		}

		foreach ( array_keys( $paneles ) as $i ) {
			$paneles[ $i ]['id'] = 'prc-panel-' . ( $i + 1 );
		}
		return $paneles;
	}






	private static function no_action(): array {
		return array(
			'label'   => '',
			'url'     => '',
			'icon'    => '',
			'primary' => false,
		);
	}

















	private static function action( int $post_id, string $state, int $user_id, array $mine ): array {
		$vacio = self::no_action();
		$apply = Shell::url( 'apply', array( Shell::ARG_PROCEDURE => $post_id ) );
		if ( '' === $apply ) {
			return $vacio;
		}
		$abierto = ProcedureState::accepts_applications( $state );

		if ( $user_id <= 0 ) {
			return $abierto ? array(
				'label'   => 'Solicitar',
				'url'     => wp_login_url( $apply ),
				'icon'    => 'edit_note',
				'primary' => true,
			) : $vacio;
		}
		if ( ! user_can( $user_id, ProcedureAccess::CAP_APPLY ) ) {
			return $vacio;
		}

		if ( ! empty( $mine['has'] ) ) {
			return $abierto ? array(
				'label'   => 'Editar mi solicitud',
				'url'     => $apply,
				'icon'    => 'edit_note',
				'primary' => true,
			) : array(
				'label'   => 'Ver mi solicitud',
				'url'     => $apply,
				'icon'    => 'visibility',
				'primary' => false,
			);
		}
		if ( $abierto && ProcedureAccess::can_apply( $user_id, $post_id ) ) {
			return array(
				'label'   => 'Solicitar',
				'url'     => $apply,
				'icon'    => 'edit_note',
				'primary' => true,
			);
		}
		return $vacio;
	}












	private static function manage_url( int $post_id, int $user_id ): string {
		if ( ! ProcedureAccess::can_open( $user_id, $post_id ) ) {
			return '';
		}
		return Shell::url( 'editor', array( Shell::ARG_PROCEDURE => $post_id ) );
	}









	private static function links( array $meta ): array {
		$rotulos = array(
			ProcedureMetaKeys::RESOLUTION_URL       => array( 'Resolución del procedimiento', 'quick_reference_all' ),
			ProcedureMetaKeys::PROVISIONAL_LIST_URL => array( 'Listado provisional de admitidos', 'view_list' ),
			ProcedureMetaKeys::FINAL_LIST_URL       => array( 'Listado definitivo de admitidos', 'view_list' ),
		);
		$out     = array();
		foreach ( $rotulos as $key => $rotulo ) {
			$url = trim( (string) $meta[ $key ] );
			if ( '' !== $url ) {
				$out[] = array(
					'label' => $rotulo[0],
					'url'   => $url,
					'icon'  => $rotulo[1],
				);
			}
		}
		return $out;
	}








	private static function terms( int $post_id, string $taxonomy ): string {
		$terms = get_the_terms( $post_id, $taxonomy );
		if ( ! is_array( $terms ) ) {
			return '';
		}
		return implode( ' · ', wp_list_pluck( $terms, 'name' ) );
	}
}








namespace Prc\PublicFront\View;

use Prc\Meta\ProcedureMetaKeys;
use Prc\PublicFront\Assets;
use Prc\PublicFront\Shell;
use Prc\PublicFront\Workspace;









final class WorkspaceView {












	private const LEGEND = array(
		ProcedureMetaKeys::STATE_OPEN      => 'Procedimiento <strong>abierto</strong>. Plazo de solicitud <strong>abierto</strong>.',
		ProcedureMetaKeys::STATE_AMENDMENT => 'Procedimiento en <strong>subsanación</strong>. Plazo de solicitud <strong>finalizado</strong>.',
		ProcedureMetaKeys::STATE_UPCOMING  => 'Procedimiento <strong>próximo</strong>. El plazo de solicitud todavía no ha empezado.',
		ProcedureMetaKeys::STATE_CLOSED    => 'Procedimiento <strong>cerrado</strong>. Plazo de solicitud <strong>finalizado</strong>.',
		ProcedureMetaKeys::STATE_RESOLVED  => 'Procedimiento <strong>resuelto</strong>, con su listado definitivo publicado.',
		ProcedureMetaKeys::STATE_DRAFT     => 'Procedimiento <strong>en borrador</strong>: todavía no se ve en la portada.',
		ProcedureMetaKeys::STATE_ARCHIVED  => 'Procedimiento <strong>histórico</strong>: se consulta y se exporta, pero ya no se edita.',
	);




	private const LEGEND_WARNING = 'Las <strong>fechas no cuadran</strong> y hay que repasarlas: el aviso dice qué.';







	public static function html( array $m ): string {
		if ( empty( $m['can_use'] ) ) {
			return Shell::render( 'Gestión de procedimientos', '', Shell::notice( 'aviso', (string) $m['reason'] ) );
		}

		ob_start();
		?>
		<div class="prc-taller">
			<?php echo Shell::notice( (string) $m['notice']['type'], (string) $m['notice']['text'] ); ?>
			<?php self::band( $m ); ?>
			<?php self::courses( $m ); ?>
			<?php self::count( $m ); ?>
			<?php self::table( $m ); ?>
		</div>
		<?php


		return Shell::render( 'Gestión de procedimientos', '', (string) ob_get_clean(), true );
	}







	private static function band( array $m ): void {
		?>
		<div class="prc-taller__banda">
			<div class="prc-taller__izq">
				<?php self::search( $m ); ?>
				<p class="prc-taller__nota"><?php echo esc_html( (string) $m['subtitle'] ); ?></p>
				<?php if ( ! empty( $m['can_create'] ) ) : ?>
					<p class="prc-acciones">
						<a class="<?php echo esc_attr( Assets::button_class( true ) ); ?>" href="<?php echo esc_url( (string) $m['create_url'] ); ?>">
							<?php echo Shell::icon_plus(); ?>
							Nuevo procedimiento
						</a>
					</p>
				<?php endif; ?>
			</div>
			<div class="prc-taller__der">
				<?php self::legend(); ?>
			</div>
		</div>
		<?php
	}











	private static function search( array $m ): void {
		$s = $m['selection'];
		?>
		<form class="prc-buscador" method="get" action="" role="search">
			<?php if ( (int) $m['page_id'] > 0 ) : ?>
				<input type="hidden" name="page_id" value="<?php echo esc_attr( (string) $m['page_id'] ); ?>" />
			<?php endif; ?>
			<?php if ( (int) $s['course'] > 0 ) : ?>
				<input type="hidden" name="<?php echo esc_attr( Workspace::VAR_COURSE ); ?>" value="<?php echo esc_attr( (string) $s['course'] ); ?>" />
			<?php endif; ?>
			<label class="screen-reader-text" for="prc-buscar">Buscar en la tabla</label>
			<input type="search" id="prc-buscar" class="prc-buscador__campo" size="20"
				name="<?php echo esc_attr( Workspace::VAR_SEARCH ); ?>"
				value="<?php echo esc_attr( (string) $s['q'] ); ?>" />
			<label class="screen-reader-text" for="prc-estado">Estado</label>
			<select class="prc-buscador__estado" id="prc-estado" name="<?php echo esc_attr( Workspace::VAR_STATE ); ?>">
				<?php foreach ( Workspace::state_filters() as $clave => $rotulo ) : ?>
					<option value="<?php echo esc_attr( $clave ); ?>" <?php selected( (string) $s['state'], $clave ); ?>>
						<?php echo esc_html( sprintf( '%s (%d)', $rotulo, (int) ( $m['counts'][ $clave ] ?? 0 ) ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<button class="prc-buscador__boton" type="submit">Buscar en la tabla</button>
			<?php if ( '' !== (string) $m['reset_url'] ) : ?>
				<a class="prc-buscador__quitar" href="<?php echo esc_url( (string) $m['reset_url'] ); ?>">Quitar los filtros</a>
			<?php endif; ?>
		</form>
		<?php
	}






	private static function legend(): void {
		$iconos = Workspace::state_icons();
		?>
		<div class="prc-leyenda">
			<ul class="prc-leyenda__lista">
				<?php foreach ( self::LEGEND as $estado => $frase ) : ?>
					<li class="prc-leyenda__item">
						<?php self::icon( (string) ( $iconos[ $estado ] ?? '' ), $estado ); ?>
						<span><?php echo wp_kses( $frase, array( 'strong' => array() ) ); ?></span>
					</li>
				<?php endforeach; ?>
				<li class="prc-leyenda__item">
					<?php self::icon( 'error', 'aviso' ); ?>
					<span><?php echo wp_kses( self::LEGEND_WARNING, array( 'strong' => array() ) ); ?></span>
				</li>
			</ul>
		</div>
		<?php
	}













	private static function icon( string $glifo, string $estado, string $rotulo = '' ): void {
		if ( '' === $glifo ) {
			return;
		}
		$clases = 'material-symbols-outlined prc-icono-estado prc-icono-estado--' . sanitize_html_class( $estado );
		if ( '' === $rotulo ) {
			printf( '<span class="%s" aria-hidden="true">%s</span>', esc_attr( $clases ), esc_html( $glifo ) );
			return;
		}
		printf(
			'<span class="%s" role="img" aria-label="%s" title="%s">%s</span>',
			esc_attr( $clases ),
			esc_attr( $rotulo ),
			esc_attr( $rotulo ),
			esc_html( $glifo )
		);
	}










	private static function courses( array $m ): void {
		if ( array() === (array) $m['courses'] ) {
			return;
		}
		$s      = $m['selection'];
		$cursos = (array) $m['courses'] + array( 0 => 'Todos los cursos' );
		?>
		<nav class="prc-cursos" aria-label="Curso escolar">
			<ul class="prc-cursos__lista">
				<?php foreach ( $cursos as $term_id => $nombre ) : ?>
					<?php $activa = (int) $s['course'] === (int) $term_id; ?>
					<li>
						<a class="prc-cursos__tab<?php echo $activa ? ' prc-cursos__tab--on' : ''; ?>"
							href="<?php echo esc_url( Workspace::url( $s, array( 'course' => (int) $term_id ) ) ); ?>"
							<?php echo $activa ? 'aria-current="page"' : ''; ?>>
							<?php echo esc_html( (string) $nombre ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>
		<?php
	}







	private static function count( array $m ): void {
		$n     = (int) $m['total'];
		$curso = (string) $m['course_label'];
		$q     = (string) $m['selection']['q'];



		$frase = sprintf(
			'Mostrando <strong>%1$s</strong> %2$s %3$s',
			esc_html( (string) $n ),
			esc_html( 1 === $n ? 'procedimiento' : 'procedimientos' ),
			'' !== $curso
				? 'en <strong>' . esc_html( $curso ) . '</strong>'
				: 'de <strong>todos los cursos</strong>'
		);
		if ( '' !== $q ) {
			$frase .= sprintf(
				' %s «<strong>%s</strong>»',
				1 === $n ? 'que contiene' : 'que contienen',
				esc_html( $q )
			);
		}
		?>
		<p class="prc-recuento"><?php echo wp_kses( $frase . ':', array( 'strong' => array() ) ); ?></p>
		<?php
	}







	private static function table( array $m ): void {
		if ( array() === $m['rows'] ) {
			?>
			<p class="prc-vacio"><?php echo esc_html( (string) $m['empty_text'] ); ?></p>
			<?php
			return;
		}
		$curso = (string) $m['course_label'];
		?>
		<div class="prc-tabla-caja">
			<table class="prc-tabla prc-tabla--densa prc-tabla--taller">
				<caption class="screen-reader-text">
					<?php echo esc_html( '' !== $curso ? 'Procedimientos de ' . $curso : 'Procedimientos de todos los cursos' ); ?>
				</caption>
				<thead>
					<tr class="prc-tabla__grupos">
						<th scope="colgroup" colspan="6" class="prc-tabla__grupo prc-tabla__grupo--1">Detalles del procedimiento</th>
						<th scope="colgroup" colspan="1" class="prc-tabla__grupo prc-tabla__grupo--2">Fecha límite</th>
						<th scope="colgroup" colspan="3" class="prc-tabla__grupo prc-tabla__grupo--3">Gestión</th>
					</tr>
					<tr>
						<th scope="col" class="prc-th--a prc-th--centro prc-th--color">
							<span class="material-symbols-outlined" aria-hidden="true">image</span>
							<span class="screen-reader-text">Color de cabecera</span>
						</th>
						<th scope="col" class="prc-th--a prc-th--centro">#</th>
						<th scope="col" class="prc-th--a">Título</th>
						<th scope="col" class="prc-th--a prc-th--centro">Dirigido a</th>
						<th scope="col" class="prc-th--a prc-th--centro">Estado</th>
						<th scope="col" class="prc-th--a">Creado</th>
						<th scope="col" class="prc-th--b">Límite<br />solicitud</th>
						<th scope="col" class="prc-th--c prc-th--gestiona">Gestiona</th>
						<th scope="col" class="prc-th--c prc-th--centro">Detalles</th>
						<th scope="col" class="prc-th--c prc-th--centro">Solicitudes</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $m['rows'] as $row ) : ?>
						<?php self::row( $row ); ?>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}







	private static function row( array $row ): void {
		$estado = (string) $row['state'];
		$aviso  = (string) $row['warning'];
		$mirar  = 'publish' === (string) $row['status']
			? 'Ver la ficha pública'
			: 'Previsualizar la ficha, que está en borrador';
		?>
		<tr data-estado="<?php echo esc_attr( $estado ); ?>" data-procedimiento="<?php echo esc_attr( (string) $row['id'] ); ?>">
			<td class="prc-celda-color" data-rotulo="Color">
				<span class="prc-mini-color" style="background-color:<?php echo esc_attr( (string) $row['color'] ); ?>"></span>
			</td>
			<th scope="row" class="prc-celda-num" data-rotulo="Número">
				<?php self::icon( (string) ( Workspace::state_icons()[ $estado ] ?? '' ), $estado, (string) $row['state_label'] ); ?>
				<br /><?php echo esc_html( (string) $row['id'] ); ?>
			</th>
			<td class="prc-celda-titulo" data-rotulo="Título">
				<?php if ( '' !== (string) $row['url'] ) : ?>
					<a href="<?php echo esc_url( (string) $row['url'] ); ?>" title="Abrir el taller de este procedimiento">
						<?php echo esc_html( (string) $row['title'] ); ?>
					</a>
				<?php else : ?>
					<?php echo esc_html( (string) $row['title'] ); ?>
				<?php endif; ?>
			</td>
			<td class="prc-celda-destinatario" data-rotulo="Dirigido a">
				<span class="material-symbols-outlined prc-icono-azul" aria-hidden="true"><?php echo esc_html( ProcedureMetaKeys::AUDIENCE_TEACHERS === (string) $row['audience'] ? 'group' : 'home_work' ); ?></span>
				<br /><?php echo esc_html( (string) $row['audience_label'] ); ?>
			</td>
			<td class="prc-celda-estado" data-rotulo="Estado">
				<span class="<?php echo esc_attr( Assets::state_class( $estado ) ); ?>"><?php echo esc_html( (string) $row['state_label'] ); ?></span>
				<?php if ( ! empty( $row['archived'] ) && ProcedureMetaKeys::STATE_ARCHIVED !== $estado ) : ?>
					<span class="<?php echo esc_attr( Assets::state_class( ProcedureMetaKeys::STATE_ARCHIVED ) ); ?>"
						title="Cerrado a edición: se consulta y se exporta, pero no se cambia.">Histórico</span>
				<?php endif; ?>
				<?php if ( '' !== $aviso ) : ?>
					<?php self::icon( 'error', 'aviso', $aviso ); ?>
				<?php endif; ?>
				<?php if ( array() !== (array) $row['courses'] ) : ?>
					<br /><em class="prc-state__curso"><?php echo esc_html( implode( ' · ', (array) $row['courses'] ) ); ?></em>
				<?php endif; ?>
			</td>
			<td class="prc-celda-fecha" data-rotulo="Creado"><?php echo esc_html( (string) $row['created'] ); ?></td>
			<td class="prc-celda-fecha" data-rotulo="Límite de solicitud"><?php echo esc_html( (string) $row['closes'] ); ?></td>
			<td class="prc-celda-ambitos" data-rotulo="Gestiona">
				<?php if ( array() !== (array) $row['areas'] ) : ?>
					<ol class="prc-ambitos">
						<?php foreach ( (array) $row['areas'] as $nombre ) : ?>
							<li><?php echo esc_html( (string) $nombre ); ?></li>
						<?php endforeach; ?>
					</ol>
				<?php endif; ?>
			</td>
			<td class="prc-celda-accion" data-rotulo="Detalles">
				<?php self::action( (string) $row['view_url'], 'visibility', $mirar ); ?>
			</td>
			<td class="prc-celda-accion" data-rotulo="Solicitudes">
				<?php self::action( (string) $row['applications_url'], 'view_list', 'Ver solicitudes' ); ?>
				<span class="prc-accion__contador"><?php echo esc_html( (string) $row['applications'] ); ?></span>
			</td>
		</tr>
		<?php
	}









	private static function action( string $url, string $glifo, string $rotulo ): void {
		if ( '' === $url ) {
			return;
		}
		?>
		<a class="prc-accion" href="<?php echo esc_url( $url ); ?>" title="<?php echo esc_attr( $rotulo ); ?>">
			<span class="material-symbols-outlined" aria-hidden="true"><?php echo esc_html( $glifo ); ?></span>
			<span class="screen-reader-text"><?php echo esc_html( $rotulo ); ?></span>
		</a>
		<?php
	}
}








namespace Prc\PublicFront;

use Prc\Access\ProcedureAccess;
use Prc\Domain\DateRange;
use Prc\Domain\ProcedureState;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ApplicationPostType;
use Prc\PostType\ProcedurePostType;
use Prc\PublicFront\View\WorkspaceView;
use Prc\Taxonomy\ProcedureTaxonomies;















final class Workspace {

	public const SHORTCODE = 'prc_workspace';








	public const VAR_COURSE = 'prc_filter_course';




	public const VAR_STATE = 'prc_filter_state';







	public const VAR_SEARCH = 'prc_q';




	public const VAR_NOTICE = 'aviso';






	private const STATUSES = array( 'publish', 'future', 'draft', 'pending', 'private' );






	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'render' ) );
	}






	public static function state_filters(): array {
		$estados = ProcedureMetaKeys::states();
		$orden   = array(
			ProcedureMetaKeys::STATE_OPEN,
			ProcedureMetaKeys::STATE_AMENDMENT,
			ProcedureMetaKeys::STATE_UPCOMING,
			ProcedureMetaKeys::STATE_CLOSED,
			ProcedureMetaKeys::STATE_RESOLVED,
			ProcedureMetaKeys::STATE_DRAFT,
			ProcedureMetaKeys::STATE_ARCHIVED,
		);
		$out     = array( 'all' => 'Todos' );
		foreach ( $orden as $estado ) {
			$out[ $estado ] = $estados[ $estado ];
		}
		return $out;
	}












	public static function state_icons(): array {
		return array(
			ProcedureMetaKeys::STATE_OPEN      => 'task_alt',
			ProcedureMetaKeys::STATE_AMENDMENT => 'contact_support',
			ProcedureMetaKeys::STATE_UPCOMING  => 'schedule',
			ProcedureMetaKeys::STATE_CLOSED    => 'cancel',
			ProcedureMetaKeys::STATE_RESOLVED  => 'view_list',
			ProcedureMetaKeys::STATE_DRAFT     => 'reset_brightness',
			ProcedureMetaKeys::STATE_ARCHIVED  => 'tonality',
		);
	}








	public static function input( string $key, string $fallback = '' ): string {

		return isset( $_GET[ $key ] ) && is_string( $_GET[ $key ] )
			? sanitize_text_field( wp_unslash( $_GET[ $key ] ) )
			: $fallback;

	}






	public static function selection(): array {
		$estado = self::input( self::VAR_STATE, 'all' );

		return array(
			'course' => max( 0, (int) self::input( self::VAR_COURSE ) ),
			'state'  => isset( self::state_filters()[ $estado ] ) ? $estado : 'all',
			'q'      => self::input( self::VAR_SEARCH ),
		);
	}








	public static function url( array $selection, array $changes = array() ): string {
		$s    = array_merge( $selection, $changes );
		$args = array();
		if ( (int) $s['course'] > 0 ) {
			$args[ self::VAR_COURSE ] = (string) $s['course'];
		}
		if ( 'all' !== (string) $s['state'] ) {
			$args[ self::VAR_STATE ] = (string) $s['state'];
		}
		if ( '' !== (string) ( $s['q'] ?? '' ) ) {
			$args[ self::VAR_SEARCH ] = (string) $s['q'];
		}
		return Shell::url( 'workspace', $args );
	}






	public static function model(): array {
		$m           = self::blank();
		$m['reason'] = self::why_nothing();
		if ( '' !== $m['reason'] ) {
			return $m;
		}

		$user_id = get_current_user_id();
		$todas   = ProcedureAccess::can_edit_all_areas( $user_id );
		$crear   = Shell::url( 'editor' );
		$m       = array_merge(
			$m,
			array(
				'can_use'    => true,
				'notice'     => self::flash(),
				'page_id'    => '' === (string) get_option( 'permalink_structure' ) ? (int) get_queried_object_id() : 0,
				'scoped'     => ! $todas,
				'subtitle'   => $todas
					? 'Todos los procedimientos, de todos los ámbitos.'
					: 'Solo los procedimientos de su ámbito: los que convocan otros ámbitos no salen aquí.',
				'can_create' => '' !== $crear,
				'create_url' => $crear,
			)
		);

		$rows         = self::collect( $user_id );
		$m['courses'] = self::courses( $rows );
		$s            = self::selection();
		$en_url       = $s['course'] > 0;
		$s['course']  = self::course_shown( $s['course'], $m['courses'] );
		$rows         = self::narrow( $rows, $s );



		foreach ( $rows as $row ) {
			++$m['counts']['all'];
			++$m['counts'][ $row['state'] ];
		}
		$rows = self::only_state( $rows, (string) $s['state'] );
		usort( $rows, array( self::class, 'compare' ) );



		$m['rows']         = self::with_applications( $rows );
		$m['total']        = count( $rows );
		$m['selection']    = $s;
		$m['course_label'] = (string) ( $m['courses'][ $s['course'] ] ?? '' );

		if ( array() === $rows ) {
			$filtrando       = 'all' !== $s['state'] || $en_url || '' !== $s['q'];
			$m['empty_text'] = self::empty_text( $filtrando, $todas );
			$m['reset_url']  = $filtrando
				? self::url(
					$s,
					array(
						'course' => 0,
						'state'  => 'all',
						'q'      => '',
					)
				)
				: '';
		}

		return $m;
	}






	private static function blank(): array {
		return array(
			'can_use'      => false,
			'reason'       => '',
			'notice'       => array(
				'type' => '',
				'text' => '',
			),
			'subtitle'     => '',
			'selection'    => self::selection(),
			'courses'      => array(),
			'scoped'       => false,
			'counts'       => array_fill_keys( array_keys( self::state_filters() ), 0 ),
			'total'        => 0,
			'rows'         => array(),
			'can_create'   => false,
			'create_url'   => '',
			'empty_text'   => '',
			'reset_url'    => '',
			'page_id'      => 0,
			'course_label' => '',
		);
	}






	private static function why_nothing(): string {
		if ( ! is_user_logged_in() ) {
			return 'Debe iniciar sesión con su usuario para gestionar procedimientos.';
		}
		$user_id = get_current_user_id();
		if ( ProcedureAccess::can_manage_procedures( $user_id ) ) {
			return '';
		}


		return user_can( $user_id, ProcedureAccess::CAP_MANAGE_PROCEDURES )
			? ProcedureAccess::scope_assignment_message( $user_id )
			: 'Su usuario todavía no gestiona procedimientos. Pídalo a quien administre el aplicativo.';
	}












	private static function collect( int $user_id ): array {
		$rows = array();
		foreach ( self::scope( $user_id ) as $post ) {
			if ( ProcedureAccess::can_open( $user_id, (int) $post->ID ) ) {
				$rows[] = self::row( $post );
			}
		}
		return $rows;
	}







	private static function scope( int $user_id ): array {
		$base = array(
			'post_type'           => ProcedurePostType::POST_TYPE,
			'post_status'         => self::STATUSES,



			'posts_per_page'      => 500, 
			'orderby'             => 'date',
			'order'               => 'DESC',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		);

		if ( ProcedureAccess::can_edit_all_areas( $user_id ) ) {
			return get_posts( $base );
		}

		$areas = ProcedureAccess::scope_areas( $user_id );
		if ( array() === $areas ) {
			return array();
		}

		return get_posts(
			array_merge(
				$base,
				array(
					'tax_query' => array( 
						array(
							'taxonomy'     => ProcedureTaxonomies::AREA,
							'field'        => 'term_id',
							'terms'        => $areas,
						'include_children' => false,
						),
					),
				)
			)
		);
	}







	private static function row( \WP_Post $post ): array {
		$id       = (int) $post->ID;
		$titulo   = trim( (string) $post->post_title );
		$state    = ProcedureState::of_post( $id );
		$opens    = (string) get_post_meta( $id, ProcedureMetaKeys::OPENS_AT, true );
		$closes   = (string) get_post_meta( $id, ProcedureMetaKeys::CLOSES_AT, true );
		$publicos = ProcedureMetaKeys::audiences();
		$publico  = ProcedureMetaKeys::in_list(
			get_post_meta( $id, ProcedureMetaKeys::AUDIENCE, true ),
			$publicos,
			ProcedureMetaKeys::AUDIENCE_SCHOOLS
		);

		return array(
			'id'               => $id,
			'title'            => '' !== $titulo ? $titulo : 'Procedimiento sin título',
			'areas'            => self::terms( $id, ProcedureTaxonomies::AREA ),
			'courses'          => self::terms( $id, ProcedureTaxonomies::COURSE ),
			'opens'            => $opens,
			'dates'            => DateRange::of( $opens, $closes ),

			'created'          => self::dmy( substr( (string) $post->post_date, 0, 10 ) ),
			'closes'           => self::dmy( $closes ),
			'color'            => ProcedureMetaKeys::header_hex( get_post_meta( $id, ProcedureMetaKeys::HEADER_COLOR, true ) ),
			'audience'         => $publico,
			'audience_label'   => (string) $publicos[ $publico ],
			'state'            => $state,
			'state_label'      => ProcedureState::label( $state ),
			'warning'          => self::mismatch( $id, $opens, $closes ),
			'archived'         => ProcedureAccess::is_archived( $id ),
			'status'           => (string) $post->post_status,
			'applications'     => 0,
			'url'              => Shell::url( 'editor', array( Shell::ARG_PROCEDURE => $id ) ),
			'applications_url' => ProcedureEditor::url( $id, ProcedureEditor::PANEL_APPLICATIONS ),


			'view_url'         => 'publish' === $post->post_status ? (string) get_permalink( $id ) : (string) get_preview_post_link( $id ),
		);
	}








	private static function course_shown( int $pedido, array $cursos ): int {
		if ( isset( $cursos[ $pedido ] ) ) {
			return $pedido;
		}


		return $pedido > 0 || array() === $cursos ? 0 : (int) array_key_first( $cursos );
	}








	private static function narrow( array $rows, array $s ): array {
		$curso = (int) $s['course'];
		if ( $curso > 0 ) {
			$rows = array_values(
				array_filter(
					$rows,
					static function ( array $row ) use ( $curso ): bool {
						return isset( $row['courses'][ $curso ] );
					}
				)
			);
		}
		return '' !== (string) $s['q'] ? self::matching( $rows, (string) $s['q'] ) : $rows;
	}








	private static function only_state( array $rows, string $estado ): array {
		if ( 'all' === $estado ) {
			return $rows;
		}
		return array_values(
			array_filter(
				$rows,
				static function ( array $row ) use ( $estado ): bool {
					return $estado === $row['state'];
				}
			)
		);
	}













	private static function matching( array $rows, string $texto ): array {
		return array_values(
			array_filter(
				$rows,
				static function ( array $row ) use ( $texto ): bool {
					$heno = (string) $row['title'] . ' ' . (string) $row['id'] . ' ' . implode( ' ', (array) $row['areas'] );
					return false !== stripos( $heno, $texto );
				}
			)
		);
	}















	private static function mismatch( int $post_id, string $opens, string $closes ): string {
		if ( '' !== $opens && '' !== $closes && $closes < $opens ) {
			return 'El plazo de solicitud cierra antes de abrirse: repase las fechas.';
		}

		$abre   = (string) get_post_meta( $post_id, ProcedureMetaKeys::AMEND_OPENS_AT, true );
		$cierra = (string) get_post_meta( $post_id, ProcedureMetaKeys::AMEND_CLOSES_AT, true );
		if ( '' !== $abre && '' !== $cierra && $cierra < $abre ) {
			return 'La subsanación cierra antes de abrirse: repase las fechas.';
		}
		if ( '' !== $abre && '' !== $closes && $abre <= $closes ) {
			return 'La subsanación empieza antes de que cierre el plazo de solicitud: repase las fechas.';
		}
		return '';
	}







	private static function dmy( string $fecha ): string {
		return 1 === preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $fecha, $trozos )
			? $trozos[3] . '-' . $trozos[2] . '-' . $trozos[1]
			: '';
	}








	private static function terms( int $post_id, string $taxonomy ): array {
		$terms = get_the_terms( $post_id, $taxonomy );
		if ( ! is_array( $terms ) ) {
			return array();
		}
		return wp_list_pluck( $terms, 'name', 'term_id' );
	}







	private static function courses( array $rows ): array {
		$out = array();
		foreach ( $rows as $row ) {
			foreach ( $row['courses'] as $term_id => $nombre ) {
				$out[ $term_id ] = $nombre;
			}
		}
		arsort( $out, SORT_NATURAL );
		return $out;
	}







	private static function with_applications( array $rows ): array {
		$ids = array_map( 'intval', array_column( $rows, 'id' ) );
		if ( array() === $ids ) {
			return $rows;
		}
		global $wpdb;
		$huecos = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		$filas = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_parent, COUNT(*) AS n FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish' AND post_parent IN ({$huecos}) GROUP BY post_parent",
				array_merge( array( ApplicationPostType::POST_TYPE ), $ids )
			)
		);

		$cuenta = array();
		foreach ( (array) $filas as $fila ) {
			$cuenta[ (int) $fila->post_parent ] = (int) $fila->n;
		}
		foreach ( $rows as $i => $row ) {
			$rows[ $i ]['applications'] = $cuenta[ $row['id'] ] ?? 0;
		}
		return $rows;
	}








	private static function compare( array $a, array $b ): int {
		$ka = '' !== $a['opens'] ? $a['opens'] : '9999-12-31';
		$kb = '' !== $b['opens'] ? $b['opens'] : '9999-12-31';
		return $ka === $kb ? strnatcasecmp( $a['title'], $b['title'] ) : strcmp( $kb, $ka );
	}








	private static function empty_text( bool $filtered, bool $all_areas ): string {
		if ( $filtered ) {
			return 'Ningún procedimiento coincide con lo que ha pedido. Pruebe a quitar algún filtro.';
		}
		return $all_areas
			? 'Todavía no hay ningún procedimiento. Cree el primero con «Nuevo procedimiento».'
			: 'Todavía no hay ningún procedimiento de su ámbito. Aquí solo salen los que convoca su ámbito; cree el primero con «Nuevo procedimiento».';
	}






	private static function flash(): array {
		$avisos = array(
			'creado'       => array( 'ok', 'Procedimiento creado. Ya puede completarlo y publicarlo.' ),
			'guardado'     => array( 'ok', 'Cambios guardados.' ),
			'retirado'     => array( 'ok', 'El procedimiento se ha guardado. Su ámbito ya no lo organiza y dejará de tener acceso a su edición.' ),
			'borrado'      => array( 'ok', 'Procedimiento enviado a la papelera. Nada se ha perdido: se restaura desde el escritorio de WordPress.' ),
			'publicado'    => array( 'ok', 'Procedimiento publicado: ya se ve en la portada.' ),
			'despublicado' => array( 'ok', 'Procedimiento devuelto a borrador: deja de verse en la portada y no se pierde nada.' ),
			'archivado'    => array( 'ok', 'Procedimiento marcado como histórico: se consulta y se exporta, pero ya no se edita.' ),
			'permiso'      => array( 'error', 'Ese procedimiento es de otro ámbito: solo lo edita el ámbito que lo convoca o quien administra el aplicativo.' ),
		);

		$aviso = $avisos[ self::input( self::VAR_NOTICE ) ] ?? array( '', '' );

		return array(
			'type' => $aviso[0],
			'text' => $aviso[1],
		);
	}







	public static function html( array $model ): string {
		return WorkspaceView::html( $model );
	}







	public static function render( $atts = array() ): string {
		unset( $atts );
		Assets::enqueue();
		return self::html( self::model() );
	}
}








namespace Prc\PublicFront\View;

use Prc\Domain\ProcedureQuestions;
use Prc\Domain\ProcedureState;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PublicFront\ApplicationFiles;
use Prc\PublicFront\Applications;
use Prc\PublicFront\Assets;
use Prc\PublicFront\ProcedureEditor;
use Prc\PublicFront\Shell;








final class ProcedureEditorView {







	public static function html( array $m ): string {
		if ( '' !== (string) $m['aviso'] ) {
			return Shell::render(
				'Taller del procedimiento',
				'',
				Shell::notice( (string) $m['aviso_tipo'], (string) $m['aviso'] ) . self::back_link( $m )
			);
		}
		if ( true === $m['nuevo'] ) {
			return self::new_procedure( $m );
		}

		$panel = (string) $m['panel'];

		ob_start();
		echo self::head( $m ); 
		echo self::tabs( $m ); 
		echo self::flash( $m ); 
		echo self::archived_notice( $m ); 

		echo Shell::notice( 'aviso', (string) $m['state_notice'] ); 



		if ( ProcedureEditor::PANEL_PUBLISH === $panel ) {
			echo self::archive_switch( $m ); 
		}




		$cerrado = self::read_only( $m, $panel );
		if ( $cerrado ) {
			echo '<fieldset class="prc-solo-lectura" disabled><legend class="screen-reader-text">Procedimiento en solo lectura</legend>';
		}

		if ( ProcedureEditor::PANEL_QUESTIONS === $panel ) {
			echo self::questions_panel( $m ); 
		} elseif ( ProcedureEditor::PANEL_LINKS === $panel ) {
			echo self::links_panel( $m ); 
		} elseif ( ProcedureEditor::PANEL_APPLICATIONS === $panel ) {
			echo self::applications_panel( $m ); 
		} elseif ( ProcedureEditor::PANEL_PUBLISH === $panel ) {
			echo self::publish_panel( $m ); 
		} else {
			echo self::data_panel( $m ); 
		}

		if ( $cerrado ) {
			echo '</fieldset>';
		}
		return Shell::render( '', '', (string) ob_get_clean() );
	}













	private static function read_only( array $m, string $panel ): bool {
		if ( ProcedureEditor::PANEL_APPLICATIONS === $panel ) {
			return true !== $m['can_review'];
		}
		if ( ProcedureEditor::PANEL_QUESTIONS === $panel ) {
			return ! self::open_group( $m, ProcedureState::GROUP_QUESTIONS );
		}
		if ( ProcedureEditor::PANEL_LINKS === $panel ) {
			return ! self::open_group( $m, ProcedureState::GROUP_LINKS );
		}
		if ( ProcedureEditor::PANEL_DATA === $panel ) {
			return ! self::open_group( $m, ProcedureState::GROUP_DATA ) && ! self::open_group( $m, ProcedureState::GROUP_DATES );
		}
		return true !== $m['can_edit'];
	}








	private static function open_group( array $m, string $group ): bool {
		return true === ( ( (array) $m['groups'] )[ $group ] ?? false );
	}













	private static function new_procedure( array $m ): string {
		ob_start();
		?>
		<p class="prc-sub"><a href="<?php echo esc_url( (string) $m['workspace_url'] ); ?>">&larr; Mis procedimientos</a></p>
		<div class="prc-h1-fila">
			<h1 class="prc-h1">Crear un procedimiento</h1>
		</div>
		<h2 class="prc-sub"><strong>Formato Express</strong></h2>
		<p class="prc-intro">
			En este formulario <strong>solo aparecen los campos mínimos</strong> para crear el
			procedimiento. Nace en borrador y no se ve fuera hasta que lo publique. Una vez creado
			se abre su taller, y desde ahí se añaden <strong>los plazos, las preguntas y los
			enlaces</strong>.
		</p>
		<?php
		echo self::flash( $m ); 
		echo self::data_panel( $m ); 
		return Shell::render( '', '', (string) ob_get_clean() );
	}







	private static function flash( array $m ): string {
		$flash = (array) $m['flash'];
		return Shell::notice( (string) $flash['tipo'], (string) $flash['texto'] );
	}







	private static function head( array $m ): string {
		ob_start();
		?>
		<p class="prc-sub"><a href="<?php echo esc_url( (string) $m['workspace_url'] ); ?>">&larr; Mis procedimientos</a></p>
		<div class="prc-h1-fila">
			<h1 class="prc-h1"><?php echo esc_html( '' !== (string) $m['title'] ? (string) $m['title'] : 'Procedimiento sin título' ); ?></h1>
			<?php echo self::badge( (string) $m['state'], (string) $m['state_label'] ); ?>
			<?php echo self::badge( (string) $m['status'], (string) $m['status_label'] ); ?>
			<span class="prc-acciones">
				<?php echo PanelParts::icon_link( (string) $m['view_url'], 'ojo', 'Ver la ficha pública' ); ?>
			</span>
		</div>
		<p class="prc-sub"><?php echo esc_html( 'Ámbito: ' . (string) $m['area_names'] ); ?></p>
		<?php
		return (string) ob_get_clean();
	}







	private static function tabs( array $m ): string {
		$activa = (string) $m['panel'];

		ob_start();
		?>
		<nav class="prc-tabs" aria-label="Paneles del procedimiento">
			<div class="prc-tabs-fila">
				<?php foreach ( (array) $m['panels'] as $clave => $panel ) : ?>
					<a class="prc-tab<?php echo $clave === $activa ? ' prc-tab-on' : ''; ?>"
						<?php echo $clave === $activa ? ' aria-current="page"' : ''; ?>
						href="<?php echo esc_url( (string) $panel['url'] ); ?>"><?php echo esc_html( (string) $panel['label'] ); ?>
						<?php if ( null !== ( $panel['count'] ?? null ) ) : ?>
							<span class="prc-tab-n badge"><?php echo esc_html( (string) (int) $panel['count'] ); ?></span>
						<?php endif; ?></a>
				<?php endforeach; ?>
			</div>
		</nav>
		<?php
		return (string) ob_get_clean();
	}







	private static function archived_notice( array $m ): string {
		if ( true !== $m['archived'] ) {
			return '';
		}
		return Shell::notice(
			'aviso',
			true === $m['can_edit']
				? 'Este procedimiento está marcado como histórico: su ámbito ya no puede editarlo. Usted sí, porque administra el aplicativo.'
				: 'Este procedimiento está marcado como histórico: se puede consultar y exportar, pero ya no se edita. Para volver a abrirlo, pídalo a quien administre el aplicativo.'
		);
	}













	private static function data_panel( array $m ): string {
		$v      = (array) $m['values'];
		$listas = (array) $m['terms'];
		$op     = ProcedureEditor::PANEL_DATA;
		$nuevo  = true === $m['nuevo'];


		$apagar = ! self::open_group( $m, ProcedureState::GROUP_DATA );
		$plazos = ! self::open_group( $m, ProcedureState::GROUP_DATES );



		$motivo = '' !== ProcedureState::why_locked( (string) $m['state'] )
			? 'Cerrado por el estado del procedimiento.'
			: '';



		$secciones  = PanelParts::section( 'Ajustes generales', self::general_fields( $m, $v, $listas, $nuevo ), $apagar, $motivo );
		$secciones .= '<div class="prc-form__mitades">'
			. PanelParts::section( 'Promociona', self::area_field( $v, (array) ( $listas['area'] ?? array() ), (array) ( $m['foreign_areas'] ?? array() ) ), $apagar, $motivo )
			. PanelParts::section( 'Más información', self::email_fields( $v ), $apagar, $motivo )
			. '</div>';
		$secciones .= PanelParts::section( 'Aspectos de diseño', self::palette_field( $m, $v ), $apagar, $motivo );
		if ( ! $nuevo ) {
			$secciones .= PanelParts::section( 'Plazos', self::date_fields( $v ), $plazos, $motivo );
			$secciones .= PanelParts::section( 'La solicitud', self::request_fields( $v ), $apagar, $motivo );
		}

		ob_start();
		?>
		<form class="prc-form prc-form--taller<?php echo $nuevo ? ' prc-form--alta' : ''; ?>" method="post" action="">
			<?php wp_nonce_field( ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
			<?php echo $secciones; ?>
			<?php echo PanelParts::submit( $nuevo ? 'Crear el procedimiento' : 'Guardar los datos' ); ?>
		</form>
		<?php
		return (string) ob_get_clean();
	}










	private static function general_fields( array $m, array $v, array $listas, bool $nuevo ): string {



		$sel_course = self::term_select(
			'prc-course',
			ProcedureEditor::FIELD_COURSE,
			'Curso',
			(array) ( $listas['course'] ?? array() ),
			(int) $v[ ProcedureEditor::FIELD_COURSE ],
			'En la forma 2026-2027.'
		);

		ob_start();
		?>
		<div class="prc-seccion__cuerpo">
			<div class="prc-campo prc-campo--medio">
				<label class="prc-campo__etiqueta" for="prc-title">Título del procedimiento <?php echo self::obl(); ?></label>
				<input class="prc-control" type="text" id="prc-title" name="<?php echo esc_attr( ProcedureEditor::FIELD_TITLE ); ?>"
					maxlength="120" required aria-describedby="prc-title-ayuda"
					value="<?php echo esc_attr( (string) $v[ ProcedureEditor::FIELD_TITLE ] ); ?>" />
				<p class="prc-campo__ayuda" id="prc-title-ayuda">Máximo 120 caracteres. El curso no se escribe aquí: se elige al lado.</p>
			</div>
			<div class="prc-campo prc-campo--cuarto">
				<?php echo $sel_course; ?>
			</div>
			<div class="prc-campo prc-campo--cuarto">
				<label class="prc-campo__etiqueta" for="prc-audience">Quién solicita <?php echo self::obl(); ?></label>
				<select class="prc-control" id="prc-audience" name="<?php echo esc_attr( ProcedureMetaKeys::AUDIENCE ); ?>" required aria-describedby="prc-audience-ayuda">
					<?php foreach ( (array) $m['audiences'] as $clave => $rotulo ) : ?>
						<option value="<?php echo esc_attr( (string) $clave ); ?>" <?php selected( (string) $clave, (string) $v[ ProcedureMetaKeys::AUDIENCE ] ); ?>><?php echo esc_html( (string) $rotulo ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="prc-campo__ayuda" id="prc-audience-ayuda">Casi siempre, el centro educativo.</p>
			</div>
			<div class="prc-campo prc-campo--medio">
				<fieldset>
					<legend class="prc-campo__etiqueta">Dirigido a <?php echo self::obl(); ?></legend>
					<div class="prc-opciones prc-opciones--vertical">
						<?php foreach ( (array) $m['ownerships'] as $clave => $rotulo ) : ?>
							<div class="prc-opcion">
								<input type="checkbox" id="prc-ownership-<?php echo esc_attr( (string) $clave ); ?>"
									name="<?php echo esc_attr( ProcedureMetaKeys::OWNERSHIP ); ?>[]" value="<?php echo esc_attr( (string) $clave ); ?>"
									<?php checked( in_array( $clave, (array) $v[ ProcedureMetaKeys::OWNERSHIP ], true ) ); ?> />
								<label for="prc-ownership-<?php echo esc_attr( (string) $clave ); ?>"><?php echo esc_html( (string) $rotulo ); ?></label>
							</div>
						<?php endforeach; ?>
					</div>
					<p class="prc-campo__ayuda">Un centro de otra titularidad no ve el botón de solicitar.</p>
				</fieldset>
			</div>
			<?php if ( ! $nuevo ) : ?>
				<div class="prc-campo prc-campo--completo">
					<label class="prc-campo__etiqueta" for="prc-description">Descripción</label>
					<textarea class="prc-control" id="prc-description" name="<?php echo esc_attr( ProcedureEditor::FIELD_DESCRIPTION ); ?>" rows="8"
						aria-describedby="prc-description-ayuda"><?php echo esc_textarea( (string) $v[ ProcedureEditor::FIELD_DESCRIPTION ] ); ?></textarea>
					<p class="prc-campo__ayuda" id="prc-description-ayuda">De qué va y a quién se dirige. Es lo que se lee en la ficha, debajo del título.</p>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}









	private static function area_field( array $v, array $arbol, array $foreign ): string {
		$elegidos = array_map( 'intval', (array) $v[ ProcedureEditor::FIELD_AREA ] );

		ob_start();
		?>
		<div class="prc-campo">
			<fieldset>
				<legend class="prc-campo__etiqueta">Ámbito convocante <?php echo self::obl(); ?></legend>
				<?php if ( array() === $arbol ) : ?>
					<p class="prc-vacio">No tiene ningún ámbito asignado. Pídalo a quien administre el aplicativo.</p>
				<?php else : ?>
					<ul class="prc-arbol">
						<?php foreach ( $arbol as $nodo ) : ?>
							<?php echo self::area_node( (array) $nodo, $elegidos ); ?>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<?php if ( $foreign ) : ?>
					<p>Otros ámbitos organizadores (solo lectura):</p>
					<ul>
					<?php
					foreach ( $foreign as $label ) :
						?>
						<li><?php echo esc_html( $label ); ?></li><?php endforeach; ?></ul>
					<p>Se conservarán al guardar. Solo administración o una persona de ese ámbito puede modificar su participación.</p>
				<?php endif; ?>
				<p class="prc-campo__ayuda">
					Puede marcar varios: todos ellos editan el procedimiento y gestionan sus
					solicitudes. Solo salen los suyos; para pasarlo a otro, pídalo a quien
					administra el aplicativo.
				</p>
			</fieldset>
		</div>
		<?php
		return (string) ob_get_clean();
	}













	private static function area_node( array $nodo, array $elegidos ): string {
		$id      = (int) $nodo['id'];
		$hijos   = (array) $nodo['children'];
		$rama    = array() !== $hijos;
		$abierta = self::area_selected( $nodo, $elegidos );

		ob_start();
		?>
		<li class="prc-arbol__nodo<?php echo $rama ? ' prc-arbol__nodo--rama' : ''; ?>">
			<div class="prc-arbol__fila prc-opcion">
				<?php if ( $rama ) : ?>
					<input class="prc-arbol__pliegue" type="checkbox" id="prc-rama-<?php echo esc_attr( (string) $id ); ?>" <?php checked( $abierta ); ?> />
					<label class="prc-arbol__tri" for="prc-rama-<?php echo esc_attr( (string) $id ); ?>">
						<span class="screen-reader-text"><?php echo esc_html( 'Desplegar o plegar «' . (string) $nodo['name'] . '»' ); ?></span>
					</label>
				<?php else : ?>
					<span class="prc-arbol__hueco" aria-hidden="true"></span>
				<?php endif; ?>
				<input class="prc-arbol__casilla" type="checkbox" id="prc-area-<?php echo esc_attr( (string) $id ); ?>"
					name="<?php echo esc_attr( ProcedureEditor::FIELD_AREA ); ?>[]" value="<?php echo esc_attr( (string) $id ); ?>"
					<?php checked( in_array( $id, $elegidos, true ) ); ?> />
				<label for="prc-area-<?php echo esc_attr( (string) $id ); ?>"><?php echo esc_html( (string) $nodo['name'] ); ?></label>
			</div>
			<?php if ( $rama ) : ?>
				<ul class="prc-arbol prc-arbol--rama">
					<?php foreach ( $hijos as $hijo ) : ?>
						<?php echo self::area_node( (array) $hijo, $elegidos ); ?>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</li>
		<?php
		return (string) ob_get_clean();
	}








	private static function area_selected( array $nodo, array $elegidos ): bool {
		if ( in_array( (int) $nodo['id'], $elegidos, true ) ) {
			return true;
		}
		foreach ( (array) $nodo['children'] as $hijo ) {
			if ( self::area_selected( (array) $hijo, $elegidos ) ) {
				return true;
			}
		}
		return false;
	}







	private static function email_fields( array $v ): string {
		$correos = array_pad( array_values( (array) $v[ ProcedureMetaKeys::CONTACT_EMAILS ] ), ProcedureMetaKeys::CONTACT_EMAILS_MAX, '' );
		$rotulos = array( 'Correo de contacto', '2.º correo', '3.er correo' );

		ob_start();
		?>
		<?php foreach ( $rotulos as $i => $rotulo ) : ?>
			<div class="prc-campo">
				<label class="prc-campo__etiqueta" for="prc-contact-<?php echo esc_attr( (string) $i ); ?>">
					<?php echo esc_html( $rotulo ); ?>
					<?php
					if ( 0 === $i ) {
						echo self::obl(); 
					}
					?>
				</label>
				<input class="prc-control" type="email" id="prc-contact-<?php echo esc_attr( (string) $i ); ?>"
					name="<?php echo esc_attr( ProcedureMetaKeys::CONTACT_EMAILS ); ?>[]"
					<?php echo 0 === $i ? 'required' : ''; ?>
					value="<?php echo esc_attr( (string) $correos[ $i ] ); ?>" />
			</div>
		<?php endforeach; ?>
		<p class="prc-campo__ayuda">
			Para las dudas de los centros sobre el procedimiento, no sobre el aplicativo. El
			primero sale en la ficha pública; los otros dos son opcionales.
		</p>
		<?php
		return (string) ob_get_clean();
	}












	private static function palette_field( array $m, array $v ): string {
		$elegido = (string) $v[ ProcedureMetaKeys::HEADER_COLOR ];
		$elegido = '' !== $elegido ? $elegido : ProcedureMetaKeys::HEADER_COLOR_NEUTRAL;

		ob_start();
		?>
		<div class="prc-campo">
			<fieldset>
				<legend class="prc-campo__etiqueta">Color de cabecera <?php echo self::obl(); ?></legend>
				<div class="prc-paleta">
					<?php foreach ( (array) $m['header_colors'] as $clave => $color ) : ?>
						<label class="prc-muestra" style="--prc-muestra: <?php echo esc_attr( (string) $color['hex'] ); ?>">
							<input type="radio" name="<?php echo esc_attr( ProcedureMetaKeys::HEADER_COLOR ); ?>"
								value="<?php echo esc_attr( (string) $clave ); ?>" <?php checked( (string) $clave, $elegido ); ?> />
							<span class="prc-muestra__nombre"><?php echo esc_html( (string) $color['label'] ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
				<p class="prc-campo__ayuda">Es la banda de color de la tarjeta de la portada y de la ficha.</p>
			</fieldset>
		</div>
		<?php
		return (string) ob_get_clean();
	}







	private static function date_fields( array $v ): string {
		$fechas = array(
			ProcedureMetaKeys::OPENS_AT        => array( 'prc-opens', 'Inicio del plazo de solicitud', 'Sin fecha, el procedimiento se publica como próximo y nadie puede solicitar.' ),
			ProcedureMetaKeys::CLOSES_AT       => array( 'prc-closes', 'Fin del plazo de solicitud', 'Inclusive. En blanco, el plazo dura el día de inicio.' ),
			ProcedureMetaKeys::AMEND_OPENS_AT  => array( 'prc-amend-opens', 'Inicio de la subsanación', 'Después del plazo de solicitud. Solo editan los centros a los que se pida subsanar.' ),
			ProcedureMetaKeys::AMEND_CLOSES_AT => array( 'prc-amend-closes', 'Fin de la subsanación', 'Inclusive. En blanco, dura el día de inicio.' ),
		);

		ob_start();
		?>
		<p class="prc-campo__ayuda">De estas fechas sale el estado del procedimiento —próximo, abierto, cerrado, en subsanación—, así que no hay que marcarlo a mano en ningún sitio.</p>
		<div class="prc-seccion__cuerpo">
			<?php foreach ( $fechas as $clave => $texto ) : ?>
				<div class="prc-campo prc-campo--medio">
					<label class="prc-campo__etiqueta" for="<?php echo esc_attr( $texto[0] ); ?>"><?php echo esc_html( $texto[1] ); ?></label>
					<input class="prc-control" type="date" id="<?php echo esc_attr( $texto[0] ); ?>" name="<?php echo esc_attr( (string) $clave ); ?>"
						value="<?php echo esc_attr( (string) $v[ $clave ] ); ?>" />
					<p class="prc-campo__ayuda"><?php echo esc_html( $texto[2] ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}







	private static function request_fields( array $v ): string {
		ob_start();
		?>
		<div class="prc-campo">
			<div class="prc-opcion">
				<input type="checkbox" id="prc-coordinator"
					name="<?php echo esc_attr( ProcedureMetaKeys::REQUIRES_COORDINATOR ); ?>" value="1"
					<?php checked( '' !== (string) $v[ ProcedureMetaKeys::REQUIRES_COORDINATOR ] ); ?> />
				<label for="prc-coordinator">La solicitud pide una persona coordinadora</label>
			</div>
			<p class="prc-campo__ayuda">Nombre y correo de quien coordina en el centro.</p>
		</div>
		<div class="prc-campo">
			<label class="prc-campo__etiqueta" for="prc-commitments">Compromisos del centro</label>
			<textarea class="prc-control" id="prc-commitments" name="<?php echo esc_attr( ProcedureMetaKeys::COMMITMENTS ); ?>" rows="5"
				aria-describedby="prc-commitments-ayuda"><?php echo esc_textarea( (string) $v[ ProcedureMetaKeys::COMMITMENTS ] ); ?></textarea>
			<p class="prc-campo__ayuda" id="prc-commitments-ayuda">Lo que el centro acepta al solicitar, con una casilla obligatoria. En blanco, no se pide nada.</p>
		</div>
		<?php
		return (string) ob_get_clean();
	}






	private static function obl(): string {
		return '<abbr class="prc-campo__obligatorio" title="obligatorio">*</abbr>';
	}












	private static function term_select( string $id, string $nombre, string $rotulo, array $terminos, int $elegido, string $ayuda ): string {
		ob_start();
		?>
		<label class="prc-campo__etiqueta" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $rotulo ); ?> <?php echo self::obl(); ?></label>
		<select class="prc-control" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $nombre ); ?>" required aria-describedby="<?php echo esc_attr( $id . '-ayuda' ); ?>">
			<option value="0">— Elija —</option>
			<?php foreach ( $terminos as $term_id => $texto ) : ?>
				<option value="<?php echo esc_attr( (string) (int) $term_id ); ?>" <?php selected( (int) $term_id, $elegido ); ?>><?php echo esc_html( (string) $texto ); ?></option>
			<?php endforeach; ?>
		</select>
		<p class="prc-campo__ayuda" id="<?php echo esc_attr( $id . '-ayuda' ); ?>"><?php echo esc_html( $ayuda ); ?></p>
		<?php
		return (string) ob_get_clean();
	}














	private static function questions_panel( array $m ): string {
		$op    = ProcedureEditor::PANEL_QUESTIONS;
		$filas = (array) $m['questions'];
		if ( count( $filas ) < (int) $m['q_max'] ) {
			$filas[] = array(
				'key'      => '',
				'label'    => '',
				'help'     => '',
				'type'     => ProcedureQuestions::TYPE_TEXT,
				'required' => false,
				'choices'  => array(),
			);
		}

		ob_start();
		?>
		<form class="prc-form prc-form--taller" method="post" action="">
			<?php wp_nonce_field( ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />

			<p class="prc-intro">
				Las opciones propias de este procedimiento, hasta <?php echo esc_html( (string) (int) $m['q_max'] ); ?>. El resto de la
				solicitud —centro, cargo, coordinación y compromisos— es siempre el mismo y se ajusta en «Datos».
				Para quitar una pregunta, borre su rótulo y guarde: lo que ya hubiera contestado un centro no se pierde.
			</p>

			<?php foreach ( $filas as $i => $pregunta ) : ?>
				<?php echo self::question_row( (int) $i, (array) $pregunta, (array) $m['q_types'] ); ?>
			<?php endforeach; ?>

			<?php echo PanelParts::submit( 'Guardar las preguntas' ); ?>
		</form>
		<?php
		return (string) ob_get_clean();
	}









	private static function question_row( int $i, array $p, array $tipos ): string {
		$nueva  = '' === (string) $p['key'];
		$campo  = ProcedureEditor::FIELD_Q;
		$rotulo = $nueva ? 'Pregunta nueva' : 'Pregunta ' . ( $i + 1 );

		ob_start();
		?>
		<fieldset class="prc-seccion-campos prc-pregunta">
			<legend class="prc-seccion__titulo"><?php echo esc_html( $rotulo ); ?></legend>
			<input type="hidden" name="<?php echo esc_attr( $campo . 'key' ); ?>[<?php echo esc_attr( (string) $i ); ?>]" value="<?php echo esc_attr( (string) $p['key'] ); ?>" />
			<div class="prc-seccion__cuerpo">
				<div class="prc-campo prc-campo--medio">
					<label class="prc-campo__etiqueta" for="prc-q-l-<?php echo esc_attr( (string) $i ); ?>">Rótulo</label>
					<input class="prc-control" type="text" id="prc-q-l-<?php echo esc_attr( (string) $i ); ?>" name="<?php echo esc_attr( $campo . 'label' ); ?>[<?php echo esc_attr( (string) $i ); ?>]"
						maxlength="200" value="<?php echo esc_attr( (string) $p['label'] ); ?>" />
				</div>
				<div class="prc-campo prc-campo--medio">
					<label class="prc-campo__etiqueta" for="prc-q-t-<?php echo esc_attr( (string) $i ); ?>">Tipo</label>
					<select class="prc-control" id="prc-q-t-<?php echo esc_attr( (string) $i ); ?>" name="<?php echo esc_attr( $campo . 'type' ); ?>[<?php echo esc_attr( (string) $i ); ?>]">
						<?php foreach ( $tipos as $valor => $nombre ) : ?>
							<option value="<?php echo esc_attr( (string) $valor ); ?>" <?php selected( (string) $valor, (string) $p['type'] ); ?>><?php echo esc_html( (string) $nombre ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
			<div class="prc-campo">
				<label class="prc-campo__etiqueta" for="prc-q-h-<?php echo esc_attr( (string) $i ); ?>">Indicaciones</label>
				<input class="prc-control" type="text" id="prc-q-h-<?php echo esc_attr( (string) $i ); ?>" name="<?php echo esc_attr( $campo . 'help' ); ?>[<?php echo esc_attr( (string) $i ); ?>]"
					value="<?php echo esc_attr( (string) $p['help'] ); ?>" />
				<p class="prc-campo__ayuda">Una línea bajo la pregunta que ayude a contestarla. Puede dejarse en blanco.</p>
			</div>
			<div class="prc-campo">
				<label class="prc-campo__etiqueta" for="prc-q-c-<?php echo esc_attr( (string) $i ); ?>">Opciones, una por línea</label>
				<textarea class="prc-control" id="prc-q-c-<?php echo esc_attr( (string) $i ); ?>" name="<?php echo esc_attr( $campo . 'choices' ); ?>[<?php echo esc_attr( (string) $i ); ?>]" rows="3"><?php echo esc_textarea( implode( "\n", (array) $p['choices'] ) ); ?></textarea>
				<p class="prc-campo__ayuda">Solo para «Una opción» y «Varias opciones».</p>
			</div>
			<div class="prc-campo prc-opcion">
				<input type="checkbox" id="prc-q-r-<?php echo esc_attr( (string) $i ); ?>" name="<?php echo esc_attr( $campo . 'required' ); ?>[<?php echo esc_attr( (string) $i ); ?>]" value="1"
					<?php checked( (bool) $p['required'] ); ?> />
				<label for="prc-q-r-<?php echo esc_attr( (string) $i ); ?>">Obligatoria</label>
			</div>
		</fieldset>
		<?php
		return (string) ob_get_clean();
	}









	private static function links_panel( array $m ): string {
		$v      = (array) $m['values'];
		$op     = ProcedureEditor::PANEL_LINKS;
		$campos = array(
			ProcedureMetaKeys::RESOLUTION_URL       => array( 'Resolución', 'La resolución que aprueba el procedimiento, donde esté publicada.' ),
			ProcedureMetaKeys::PROVISIONAL_LIST_URL => array( 'Listado provisional', 'Los centros admitidos provisionalmente.' ),
			ProcedureMetaKeys::FINAL_LIST_URL       => array( 'Listado definitivo', 'Con este enlace el procedimiento pasa a «Resuelto» y los centros lo ven en su solicitud.' ),
		);

		ob_start();
		?>
		<form class="prc-form prc-form--taller" method="post" action="">
			<?php wp_nonce_field( ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
			<fieldset class="prc-seccion-campos">
				<legend class="prc-seccion__titulo">Resolución y listados</legend>
				<p class="prc-intro">Son enlaces a donde ya están publicados: aquí no se guarda ninguna copia.</p>
				<?php foreach ( $campos as $clave => $texto ) : ?>
					<div class="prc-campo">
						<label class="prc-campo__etiqueta" for="<?php echo esc_attr( $clave ); ?>"><?php echo esc_html( $texto[0] ); ?></label>
						<input class="prc-control" type="url" id="<?php echo esc_attr( $clave ); ?>" name="<?php echo esc_attr( $clave ); ?>" placeholder="https://"
							value="<?php echo esc_attr( (string) $v[ $clave ] ); ?>" />
						<p class="prc-campo__ayuda"><?php echo esc_html( $texto[1] ); ?></p>
					</div>
				<?php endforeach; ?>
			</fieldset>
			<?php echo PanelParts::submit( 'Guardar los enlaces' ); ?>
		</form>
		<?php
		return (string) ob_get_clean();
	}









	private static function applications_panel( array $m ): string {
		$filas = (array) $m['applications'];
		if ( array() === $filas ) {
			return '<section class="prc-seccion-campos"><h2 class="prc-seccion__titulo">Todavía no hay solicitudes</h2>'
				. '<p class="prc-vacio">Cuando un centro solicite saldrá en esta tabla, y desde ella podrá admitirlo, pedirle subsanar o excluirlo, y exportar la lista a CSV.</p></section>';
		}
		$cuenta = count( $filas );

		ob_start();
		?>
		<div class="prc-banda-tabla">
			<p class="prc-recuento">
				<?php echo esc_html( sprintf( 1 === $cuenta ? 'Mostrando %d solicitud.' : 'Mostrando %d solicitudes.', $cuenta ) ); ?>
				Cada una se revisa desde su fila; la exportación lleva todas las columnas, respuestas incluidas.
			</p>
			<?php echo self::export_form( $m ); ?>
		</div>
		<div class="prc-tabla-caja prc-panel-solicitudes">
			<table class="prc-tabla prc-tabla--densa table">
				<caption class="screen-reader-text">Solicitudes presentadas a este procedimiento</caption>
				<thead>
					<tr>
						<th class="prc-th" scope="col">Centro</th>
						<th class="prc-th" scope="col">Código</th>
						<th class="prc-th" scope="col">Fecha</th>
						<th class="prc-th" scope="col">Cargo</th>
						<th class="prc-th" scope="col">Coordinación</th>
						<th class="prc-th" scope="col">Revisión</th>
						<th class="prc-th" scope="col">Acciones</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $filas as $fila ) : ?>
						<tr data-estado="<?php echo esc_attr( (string) $fila['state_key'] ); ?>">
							<td class="prc-celda" data-rotulo="Centro"><?php echo esc_html( '' !== (string) $fila['centre'] ? (string) $fila['centre'] : '(centro fuera del catálogo)' ); ?></td>
							<td class="prc-celda" data-rotulo="Código"><?php echo esc_html( (string) $fila['code'] ); ?></td>
							<td class="prc-celda" data-rotulo="Fecha"><?php echo esc_html( (string) $fila['date_label'] ); ?></td>
							<td class="prc-celda" data-rotulo="Cargo"><?php echo esc_html( (string) $fila['position'] ); ?></td>
							<td class="prc-celda" data-rotulo="Coordinación"><?php echo self::dash( trim( (string) $fila['coordinator'] . ' ' . (string) $fila['coordinator_email'] ) ); ?></td>
							<td class="prc-celda" data-rotulo="Revisión"><?php echo self::badge( (string) $fila['state_key'], (string) $fila['state'] ); ?></td>
							<td class="prc-celda" data-rotulo="Acciones"><?php echo self::review_form( $m, $fila ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		return (string) ob_get_clean();
	}







	private static function dash( string $texto ): string {
		return '' !== $texto ? esc_html( $texto ) : '<span class="prc-sindato" title="Sin datos">&mdash;</span>';
	}













	private static function download( array $fila, string $key ): string {
		$adjuntos = isset( $fila[ Applications::KEY_FILES ] ) && is_array( $fila[ Applications::KEY_FILES ] )
			? $fila[ Applications::KEY_FILES ]
			: array();
		$doc      = isset( $adjuntos[ $key ] ) && is_array( $adjuntos[ $key ] ) ? $adjuntos[ $key ] : array();
		if ( array() === $doc || '' === (string) ( $doc['id'] ?? '' ) ) {
			return '<span class="prc-sindato" title="Sin documento">&mdash;</span>';
		}

		return sprintf(
			'<a class="prc-descarga" href="%1$s" download>%2$s</a>',
			esc_url( ApplicationFiles::url( (int) ( $doc['application'] ?? 0 ), (string) $doc['id'] ) ),
			esc_html( '' !== (string) ( $doc['name'] ?? '' ) ? (string) $doc['name'] : 'Descargar' )
		);
	}








	private static function review_form( array $m, array $fila ): string {
		$op = ProcedureEditor::OP_REVIEW;
		$id = (int) $fila['id'];

		ob_start();
		?>
		<details class="prc-revision">
			<summary class="prc-revision__abrir">Revisar</summary>
			<dl class="prc-respuestas">
				<dt>Presentada por</dt>
				<dd><?php echo esc_html( trim( (string) $fila['applicant'] . ' ' . (string) $fila['email'] ) ); ?></dd>
				<?php foreach ( (array) $m['questions'] as $pregunta ) : ?>
					<dt><?php echo esc_html( (string) $pregunta['label'] ); ?></dt>
					<?php


					$respuesta = ProcedureQuestions::TYPE_FILE === (string) ( $pregunta['type'] ?? '' )
						? self::download( $fila, (string) $pregunta['key'] )
						: esc_html( '' !== (string) ( $fila[ $pregunta['key'] ] ?? '' ) ? (string) $fila[ $pregunta['key'] ] : '—' );
					?>
					<dd><?php echo $respuesta; ?></dd>
				<?php endforeach; ?>
			</dl>
			<form class="prc-form prc-form--taller prc-revision__form" method="post" action="">
				<?php wp_nonce_field( ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op, $id ), false ); ?>
				<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
				<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
				<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_ROW ); ?>" value="<?php echo esc_attr( (string) $id ); ?>" />
				<div class="prc-campo">
					<label class="prc-campo__etiqueta" for="prc-state-<?php echo esc_attr( (string) $id ); ?>">Estado</label>
					<select class="prc-control" id="prc-state-<?php echo esc_attr( (string) $id ); ?>" name="<?php echo esc_attr( ProcedureEditor::FIELD_STATE ); ?>">
						<?php foreach ( (array) $m['review_states'] as $clave => $rotulo ) : ?>
							<option value="<?php echo esc_attr( (string) $clave ); ?>" <?php selected( (string) $clave, (string) $fila['state_key'] ); ?>><?php echo esc_html( (string) $rotulo ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="prc-campo">
					<label class="prc-campo__etiqueta" for="prc-note-<?php echo esc_attr( (string) $id ); ?>">Nota para el centro</label>
					<textarea class="prc-control" id="prc-note-<?php echo esc_attr( (string) $id ); ?>" name="<?php echo esc_attr( ProcedureEditor::FIELD_NOTE ); ?>" rows="3"
						aria-describedby="prc-note-ayuda-<?php echo esc_attr( (string) $id ); ?>"><?php echo esc_textarea( (string) $fila['note'] ); ?></textarea>
					<p class="prc-campo__ayuda" id="prc-note-ayuda-<?php echo esc_attr( (string) $id ); ?>">Obligatoria al pedir subsanar y al excluir: el centro la lee en su solicitud.</p>
				</div>
				<?php echo PanelParts::submit( 'Guardar la revisión' ); ?>
			</form>
		</details>
		<?php
		return (string) ob_get_clean();
	}







	private static function export_form( array $m ): string {
		$op = ProcedureEditor::OP_EXPORT;

		ob_start();
		?>
		<form class="prc-acciones" method="post" action="">
			<?php wp_nonce_field( ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
			<button class="<?php echo esc_attr( Assets::button_class() ); ?>" type="submit">Exportar a CSV</button>
		</form>
		<?php
		return (string) ob_get_clean();
	}









	private static function publish_panel( array $m ): string {
		$op = ProcedureEditor::OP_PUBLISH;

		ob_start();
		?>
		<section class="prc-seccion-campos">
			<h2 class="prc-seccion__titulo">Estado</h2>
			<p class="prc-intro">
				<?php echo self::badge( (string) $m['state'], (string) $m['state_label'] ); ?>
				El estado sale de las fechas y de los enlaces: no hay nada que marcar a mano. Con el listado definitivo enlazado pasa a «Resuelto».
			</p>
			<?php if ( true === $m['can_publish'] ) : ?>
				<p class="prc-intro">En borrador no se ve fuera y ningún centro puede solicitar. Al publicarlo sale en la portada, y los centros solicitan cuando abra el plazo.</p>
				<form class="prc-acciones" method="post" action=""
					data-prc-confirm="<?php echo esc_attr( sprintf( '¿Publicar «%s»? Pasará a verse en la portada.', (string) $m['title'] ) ); ?>"
					data-prc-confirm-ok="Publicar">
					<?php wp_nonce_field( ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op ), false ); ?>
					<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
					<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
					<button class="<?php echo esc_attr( Assets::button_class( true ) . ' prc-envio' ); ?>" type="submit">Publicar el procedimiento</button>
				</form>
			<?php elseif ( 'publish' === (string) $m['status'] ) : ?>
				<p class="prc-intro">Publicado: se ve en la portada y en su ficha.</p>
			<?php endif; ?>
		</section>
		<?php
		return (string) ob_get_clean();
	}










	private static function archive_switch( array $m ): string {
		if ( true === $m['can_unarchive'] ) {
			return Shell::admin_box(
				'Volver a abrir el procedimiento',
				self::archive_form( $m, false ),
				'Cerrar un procedimiento lo hace su ámbito; volver a abrirlo, solo quien administra el aplicativo.'
			);
		}
		if ( true !== $m['can_archive'] ) {
			return '';
		}

		ob_start();
		?>
		<section class="prc-seccion-campos">
			<h2 class="prc-seccion__titulo">Dar el procedimiento por terminado</h2>
			<p class="prc-intro">Cuando ya no quede nada que tocar —resuelto, con los listados enlazados y las solicitudes revisadas—, márquelo como histórico y quedará cerrado tal y como está. Seguirá entrando a consultarlo y a exportarlo, y la ficha pública se verá igual; lo que ya no podrá es cambiar nada. <strong>Para volver a abrirlo tendrá que pedírselo a quien administre el aplicativo.</strong></p>
			<?php echo self::archive_form( $m, true ); ?>
		</section>
		<?php
		return (string) ob_get_clean();
	}








	private static function archive_form( array $m, bool $marcar ): string {
		$op       = $marcar ? ProcedureEditor::OP_ARCHIVE : ProcedureEditor::OP_UNARCHIVE;
		$rotulo   = $marcar ? 'Marcar como histórico' : 'Volver a abrir el procedimiento';
		$pregunta = $marcar
			? sprintf( '¿Marcar «%s» como histórico? Dejará de poder editarlo y de gestionar sus solicitudes, y no hay vuelta atrás: solo quien administre el aplicativo puede volver a abrirlo.', (string) $m['title'] )
			: '¿Volver a abrir este procedimiento? Su ámbito podrá editarlo otra vez.';

		ob_start();
		?>
		<form class="prc-acciones" method="post" action=""
			data-prc-confirm="<?php echo esc_attr( $pregunta ); ?>"
			data-prc-confirm-ok="<?php echo esc_attr( $rotulo ); ?>">
			<?php wp_nonce_field( ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
			<button type="submit" class="<?php echo esc_attr( Assets::button_class() ); ?>"><?php echo esc_html( $rotulo ); ?></button>
		</form>
		<?php
		return (string) ob_get_clean();
	}










	private static function badge( string $slug, string $label ): string {
		if ( '' === $label ) {
			return '';
		}
		return '<span class="' . esc_attr( Assets::state_class( $slug ) ) . '">' . esc_html( $label ) . '</span>';
	}







	private static function back_link( array $m ): string {
		$url = (string) $m['workspace_url'];
		if ( '' === $url ) {
			return '';
		}
		return '<p><a class="' . esc_attr( Assets::button_class( true ) ) . '" href="' . esc_url( $url ) . '">Ver mis procedimientos</a></p>';
	}
}









namespace Prc\PublicFront;

use Prc\Access\ProcedureAccess;
use Prc\Domain\ProcedureInput;
use Prc\Domain\ProcedureQuestions;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ProcedurePostType;
use Prc\PublicFront\View\ProcedureEditorView;
use Prc\Taxonomy\ProcedureTaxonomies;
















final class ProcedureEditor {

	public const SHORTCODE = 'prc_editor';




	public const ARG_PROCEDURE = Shell::ARG_PROCEDURE;




	public const ARG_PANEL = 'panel';




	public const ARG_NOTICE = 'aviso';

	public const PANEL_DATA         = 'datos';
	public const PANEL_QUESTIONS    = 'preguntas';
	public const PANEL_LINKS        = 'enlaces';
	public const PANEL_APPLICATIONS = 'solicitudes';
	public const PANEL_PUBLISH      = 'publicacion';

	public const OP_REVIEW    = 'review';
	public const OP_EXPORT    = 'export';
	public const OP_PUBLISH   = 'publish';
	public const OP_ARCHIVE   = 'archive';
	public const OP_UNARCHIVE = 'unarchive';




	public const FIELD_DO = 'prc_do';




	public const FIELD_PROCEDURE = 'prc_procedure';




	public const FIELD_ROW = 'prc_row';




	public const FIELD_STATE = 'prc_state';




	public const FIELD_NOTE = 'prc_note';

	public const FIELD_TITLE       = 'prc_title';
	public const FIELD_DESCRIPTION = 'prc_description';
	public const FIELD_AREA        = 'prc_area_term';
	public const FIELD_COURSE      = 'prc_course_term';




	public const FIELD_Q = 'prc_q_';






	private const OPS = array(
		self::PANEL_DATA,
		self::PANEL_QUESTIONS,
		self::PANEL_LINKS,
		self::OP_REVIEW,
		self::OP_EXPORT,
		self::OP_PUBLISH,
		self::OP_ARCHIVE,
		self::OP_UNARCHIVE,
	);






	private const DONE = array(
		'creado'       => 'Procedimiento creado, en borrador: no se ve fuera hasta que lo publique. Añada sus preguntas y sus enlaces, y publíquelo cuando esté listo.',
		'datos'        => 'Datos del procedimiento guardados.',
		'preguntas'    => 'Preguntas guardadas.',
		'enlaces'      => 'Enlaces guardados.',
		'revisada'     => 'Solicitud revisada.',
		'publicado'    => 'Procedimiento publicado: ya se ve en la portada y los centros pueden solicitar cuando abra el plazo.',
		'archivado'    => 'Procedimiento marcado como histórico. Ya no se edita ni se gestionan sus solicitudes; la ficha pública se sigue viendo igual. Para volver a abrirlo hay que pedírselo a quien administre el aplicativo.',
		'desarchivado' => 'Procedimiento desmarcado: su ámbito vuelve a poder editarlo.',
	);










	private const CANNOT_CREATE = 'Su perfil no puede crear procedimientos: hace falta gestionar procedimientos y tener un ámbito asignado. Pídalo a quien administre el aplicativo.';









	private static $rejected = '';






	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'render' ) );


		add_action( 'init', array( self::class, 'handle' ), 20 );
	}















	public static function url( int $procedure_id, string $panel = '', array $args = array() ): string {
		if ( $procedure_id > 0 ) {
			$args[ self::ARG_PROCEDURE ] = $procedure_id;
		}
		if ( '' !== $panel ) {
			$args[ self::ARG_PANEL ] = $panel;
		}
		return Shell::url( 'editor', $args );
	}







	public static function panels( int $procedure_id ): array {
		$rotulos = array(
			self::PANEL_DATA         => 'Datos',
			self::PANEL_QUESTIONS    => 'Preguntas',
			self::PANEL_LINKS        => 'Enlaces',
			self::PANEL_APPLICATIONS => 'Solicitudes',
			self::PANEL_PUBLISH      => 'Publicación',
		);
		$cuentas = array(
			self::PANEL_QUESTIONS    => count( Applications::questions( $procedure_id ) ),
			self::PANEL_APPLICATIONS => Applications::count( $procedure_id ),
		);

		$out = array();
		foreach ( $rotulos as $slug => $rotulo ) {
			$out[ $slug ] = array(
				'label' => $rotulo,
				'url'   => self::url( $procedure_id, $slug ),
				'count' => $cuentas[ $slug ] ?? null,
			);
		}
		return $out;
	}











	public static function nonce_name( string $op, int $row = 0 ): string {
		return 'prc_nonce_' . $op . '_' . $row;
	}







	public static function nonce_action( string $op ): string {
		return 'prc_ed_' . $op;
	}















	public static function handle(): void {

		$op           = sanitize_key( wp_unslash( (string) ( $_POST[ self::FIELD_DO ] ?? '' ) ) );
		$procedure_id = absint( wp_unslash( $_POST[ self::FIELD_PROCEDURE ] ?? 0 ) );
		$row_id       = absint( wp_unslash( $_POST[ self::FIELD_ROW ] ?? 0 ) );

		if ( ! in_array( $op, self::OPS, true ) || ! is_user_logged_in() ) {
			return;
		}
		if ( ! self::verify( $op, self::OP_REVIEW === $op ? $row_id : 0 ) ) {
			return;
		}

		$user_id = get_current_user_id();



		if ( self::PANEL_DATA === $op && $procedure_id <= 0 ) {
			self::create( $user_id );
			return;
		}
		if ( ProcedurePostType::POST_TYPE !== get_post_type( $procedure_id ) ) {
			self::$rejected = 'Ese procedimiento ya no existe.';
			return;
		}

		if ( self::OP_EXPORT === $op || self::OP_REVIEW === $op ) {


			if ( ! ProcedureAccess::can_review( $user_id, $procedure_id ) ) {
				self::$rejected = 'Su perfil no gestiona las solicitudes de este procedimiento.';
				return;
			}
			if ( self::OP_EXPORT === $op ) {
				self::export( $procedure_id );
				return;
			}
			self::review( $procedure_id, $row_id );
			return;
		}




		EditLock::require_available( $procedure_id );



		if ( self::OP_ARCHIVE === $op || self::OP_UNARCHIVE === $op ) {
			self::save_archived( $op, $procedure_id, $user_id );
			return;
		}

		if ( ! ProcedureAccess::can_edit( $user_id, $procedure_id ) ) {
			self::$rejected = ProcedureAccess::why_not_editable( $user_id, $procedure_id );
			return;
		}

		if ( self::OP_PUBLISH === $op ) {
			self::publish( $procedure_id, $user_id );
			return;
		}
		if ( self::PANEL_DATA === $op ) {
			self::save_data( $procedure_id, $user_id );
			return;
		}
		if ( self::PANEL_QUESTIONS === $op ) {
			if ( ! self::allows_group( $user_id, $procedure_id, ProcedureState::GROUP_QUESTIONS ) ) {
				return;
			}
			self::save_questions( $procedure_id );
			return;
		}
		if ( ! self::allows_group( $user_id, $procedure_id, ProcedureState::GROUP_LINKS ) ) {
			return;
		}
		self::save_links( $procedure_id );
	}












	private static function allows_group( int $user_id, int $procedure_id, string $group ): bool {
		if ( ProcedureAccess::can_edit_group( $user_id, $procedure_id, $group ) ) {
			return true;
		}
		self::$rejected = ProcedureAccess::why_not_editable( $user_id, $procedure_id, $group );
		return false;
	}








	private static function verify( string $op, int $row ): bool {

		$valor = sanitize_text_field( wp_unslash( (string) ( $_POST[ self::nonce_name( $op, $row ) ] ?? '' ) ) );
		return false !== wp_verify_nonce( $valor, self::nonce_action( $op ) );
	}











	private static function create( int $user_id ): void {
		if ( ! ProcedureAccess::can_manage_procedures( $user_id ) ) {
			self::$rejected = self::CANNOT_CREATE;
			return;
		}
		$revisado = self::validate_data( $user_id, self::submitted_data() );
		if ( ! $revisado['ok'] ) {
			self::$rejected = $revisado['area_error'] ?? ProcedureInput::why( $revisado['errors'] );
			return;
		}

		$id = wp_insert_post(
			array(
				'post_type'    => ProcedurePostType::POST_TYPE,
				'post_title'   => wp_slash( (string) $revisado['data']['title'] ),
				'post_content' => wp_slash( (string) $revisado['data']['description'] ),
				'post_status'  => 'draft',
				'post_author'  => $user_id,
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			self::$rejected = 'No se ha podido crear el procedimiento: ' . $id->get_error_message();
			return;
		}
		self::write_data( (int) $id, $revisado['data'] );
		Shell::leave( self::url( (int) $id, self::PANEL_DATA, array( self::ARG_NOTICE => 'creado' ) ) );
	}












	private static function save_data( int $procedure_id, int $user_id ): void {
		$datos  = ProcedureAccess::can_edit_group( $user_id, $procedure_id, ProcedureState::GROUP_DATA );
		$fechas = ProcedureAccess::can_edit_group( $user_id, $procedure_id, ProcedureState::GROUP_DATES );
		if ( ! $datos && ! $fechas && ! self::allows_group( $user_id, $procedure_id, ProcedureState::GROUP_DATA ) ) {
			return;
		}

		$crudo = self::gated_data( $procedure_id, $datos, $fechas );
		if ( ! $datos && ! ProcedureAccess::can_edit_all_areas( $user_id ) ) {
			$crudo['areas'] = array_intersect( (array) $crudo['areas'], ProcedureAccess::scope_areas( $user_id ) );
		}
		$revisado = self::validate_data( $user_id, $crudo, $procedure_id );
		if ( ! $revisado['ok'] ) {
			self::$rejected = $revisado['area_error'] ?? ProcedureInput::why( $revisado['errors'] );
			return;
		}
		wp_update_post(
			array(
				'ID'           => $procedure_id,
				'post_title'   => wp_slash( (string) $revisado['data']['title'] ),
				'post_content' => wp_slash( (string) $revisado['data']['description'] ),
			)
		);
		$loses_access = ! ProcedureAccess::can_edit_all_areas( $user_id ) && ! array_intersect( (array) $revisado['data']['areas'], ProcedureAccess::scope_areas( $user_id ) );
		self::write_data( $procedure_id, $revisado['data'] );
		if ( $loses_access ) {
			Shell::leave( add_query_arg( Workspace::VAR_NOTICE, 'retirado', Shell::url( 'workspace' ) ) );
			return;
		}
		Shell::leave( self::url( $procedure_id, self::PANEL_DATA, array( self::ARG_NOTICE => 'datos' ) ) );
	}














	private static function validate_data( int $user_id, array $crudo, int $post_id = 0 ): array {
		$areas = ProcedureAccess::resolve_area_assignment( $post_id, (array) ( $crudo['areas'] ?? array() ), $user_id );
		if ( is_wp_error( $areas ) ) {
			$crudo['areas']         = array();
			$revisado               = ProcedureInput::validate( $crudo );
			$revisado['ok']         = false;
			$revisado['errors'][]   = 'areas';
			$revisado['area_error'] = $areas->get_error_message();
			return $revisado;
		}
		$crudo['areas'] = $areas;
		$revisado       = ProcedureInput::validate( $crudo );
		return $revisado;
	}













	public static function may_set_area( int $user_id, array $area_ids ): bool {
		return ProcedureAccess::may_assign_areas( ProcedureInput::term_ids( $area_ids ), $user_id );
	}






	private static function submitted_data(): array {

		$descripcion = wp_kses_post( wp_unslash( (string) ( $_POST[ self::FIELD_DESCRIPTION ] ?? '' ) ) );

		$titularidad = (array) wp_unslash( $_POST[ ProcedureMetaKeys::OWNERSHIP ] ?? array() );
		$coordina    = ! empty( $_POST[ ProcedureMetaKeys::REQUIRES_COORDINATOR ] );

		$ambitos = (array) wp_unslash( $_POST[ self::FIELD_AREA ] ?? array() );

		$correos = (array) wp_unslash( $_POST[ ProcedureMetaKeys::CONTACT_EMAILS ] ?? array() );


		return array(
			'title'                => self::field( self::FIELD_TITLE ),
			'description'          => $descripcion,
			'areas'                => $ambitos,
			'course'               => (int) self::field( self::FIELD_COURSE ),
			'opens_at'             => self::field( ProcedureMetaKeys::OPENS_AT ),
			'closes_at'            => self::field( ProcedureMetaKeys::CLOSES_AT ),
			'amend_opens_at'       => self::field( ProcedureMetaKeys::AMEND_OPENS_AT ),
			'amend_closes_at'      => self::field( ProcedureMetaKeys::AMEND_CLOSES_AT ),
			'contact_emails'       => $correos,
			'header_color'         => self::field( ProcedureMetaKeys::HEADER_COLOR ),
			'audience'             => self::field( ProcedureMetaKeys::AUDIENCE ),
			'ownership'            => $titularidad,
			'requires_coordinator' => $coordina,
			'commitments'          => self::textarea( ProcedureMetaKeys::COMMITMENTS ),
		);
	}














	private static function gated_data( int $procedure_id, bool $datos, bool $fechas ): array {
		$crudo = self::submitted_data();
		if ( ! $fechas ) {
			$plazos = array(
				'opens_at'        => ProcedureMetaKeys::OPENS_AT,
				'closes_at'       => ProcedureMetaKeys::CLOSES_AT,
				'amend_opens_at'  => ProcedureMetaKeys::AMEND_OPENS_AT,
				'amend_closes_at' => ProcedureMetaKeys::AMEND_CLOSES_AT,
			);
			foreach ( $plazos as $campo => $clave ) {
				$crudo[ $campo ] = self::meta( $procedure_id, $clave );
			}
		}
		if ( ! $datos ) {


			$post  = get_post( $procedure_id );
			$crudo = array_merge(
				$crudo,
				array(
					'title'                => $post instanceof \WP_Post ? $post->post_title : '',
					'description'          => $post instanceof \WP_Post ? $post->post_content : '',
					'areas'                => self::terms_of( $procedure_id, ProcedureTaxonomies::AREA ),
					'course'               => self::first_term( $procedure_id, ProcedureTaxonomies::COURSE ),
					'contact_emails'       => get_post_meta( $procedure_id, ProcedureMetaKeys::CONTACT_EMAILS, true ),
					'header_color'         => self::meta( $procedure_id, ProcedureMetaKeys::HEADER_COLOR ),
					'audience'             => self::meta( $procedure_id, ProcedureMetaKeys::AUDIENCE ),
					'ownership'            => get_post_meta( $procedure_id, ProcedureMetaKeys::OWNERSHIP, true ),
					'requires_coordinator' => (bool) get_post_meta( $procedure_id, ProcedureMetaKeys::REQUIRES_COORDINATOR, true ),
					'commitments'          => self::meta( $procedure_id, ProcedureMetaKeys::COMMITMENTS ),
				)
			);
		}
		return $crudo;
	}











	private static function write_data( int $procedure_id, array $limpio ): void {
		$meta = array(
			ProcedureMetaKeys::OPENS_AT             => $limpio['opens_at'],
			ProcedureMetaKeys::CLOSES_AT            => $limpio['closes_at'],
			ProcedureMetaKeys::AMEND_OPENS_AT       => $limpio['amend_opens_at'],
			ProcedureMetaKeys::AMEND_CLOSES_AT      => $limpio['amend_closes_at'],
			ProcedureMetaKeys::CONTACT_EMAILS       => $limpio['contact_emails'],
			ProcedureMetaKeys::HEADER_COLOR         => $limpio['header_color'],
			ProcedureMetaKeys::AUDIENCE             => $limpio['audience'],
			ProcedureMetaKeys::OWNERSHIP            => $limpio['ownership'],
			ProcedureMetaKeys::REQUIRES_COORDINATOR => $limpio['requires_coordinator'],
			ProcedureMetaKeys::COMMITMENTS          => $limpio['commitments'],
		);
		foreach ( $meta as $clave => $valor ) {
			update_post_meta( $procedure_id, $clave, wp_slash( $valor ) );
		}
		wp_set_object_terms( $procedure_id, ProcedureInput::term_ids( $limpio['areas'] ), ProcedureTaxonomies::AREA, false );
		wp_set_object_terms( $procedure_id, array( (int) $limpio['course'] ), ProcedureTaxonomies::COURSE, false );
	}













	private static function save_questions( int $procedure_id ): void {

		$claves    = (array) wp_unslash( $_POST[ self::FIELD_Q . 'key' ] ?? array() );
		$rotulos   = (array) wp_unslash( $_POST[ self::FIELD_Q . 'label' ] ?? array() );
		$ayudas    = (array) wp_unslash( $_POST[ self::FIELD_Q . 'help' ] ?? array() );
		$tipos     = (array) wp_unslash( $_POST[ self::FIELD_Q . 'type' ] ?? array() );
		$opciones  = (array) wp_unslash( $_POST[ self::FIELD_Q . 'choices' ] ?? array() );
		$obligadas = (array) wp_unslash( $_POST[ self::FIELD_Q . 'required' ] ?? array() );


		$crudas = array();
		foreach ( $rotulos as $i => $rotulo ) {
			$crudas[] = array(
				'key'      => isset( $claves[ $i ] ) ? sanitize_key( (string) $claves[ $i ] ) : '',
				'label'    => sanitize_text_field( (string) $rotulo ),
				'help'     => sanitize_text_field( (string) ( $ayudas[ $i ] ?? '' ) ),
				'type'     => isset( $tipos[ $i ] ) ? sanitize_key( (string) $tipos[ $i ] ) : ProcedureQuestions::TYPE_TEXT,
				'choices'  => sanitize_textarea_field( (string) ( $opciones[ $i ] ?? '' ) ),
				'required' => ! empty( $obligadas[ $i ] ),
			);
		}

		update_post_meta( $procedure_id, ProcedureMetaKeys::QUESTIONS, wp_slash( ProcedureQuestions::sanitize( $crudas ) ) );
		Shell::leave( self::url( $procedure_id, self::PANEL_QUESTIONS, array( self::ARG_NOTICE => 'preguntas' ) ) );
	}







	private static function save_links( int $procedure_id ): void {
		$claves = array( ProcedureMetaKeys::RESOLUTION_URL, ProcedureMetaKeys::PROVISIONAL_LIST_URL, ProcedureMetaKeys::FINAL_LIST_URL );
		$urls   = array();
		foreach ( $claves as $clave ) {
			$url = self::field( $clave );
			if ( '' !== $url && ! ProcedureInput::is_url( $url ) ) {
				self::$rejected = ProcedureInput::why( array( substr( $clave, 4 ) ) );
				return;
			}
			$urls[ $clave ] = $url;
		}
		foreach ( $urls as $clave => $url ) {
			update_post_meta( $procedure_id, $clave, $url );
		}
		Shell::leave( self::url( $procedure_id, self::PANEL_LINKS, array( self::ARG_NOTICE => 'enlaces' ) ) );
	}








	private static function publish( int $procedure_id, int $user_id ): void {
		if ( ! ProcedureAccess::can_publish( $user_id, $procedure_id ) ) {
			self::$rejected = 'Su perfil no publica procedimientos: puede editarlo, pero no publicarlo.';
			return;
		}
		wp_update_post(
			array(
				'ID'          => $procedure_id,
				'post_status' => 'publish',
			)
		);
		Shell::leave( self::url( $procedure_id, self::PANEL_PUBLISH, array( self::ARG_NOTICE => 'publicado' ) ) );
	}












	private static function save_archived( string $op, int $procedure_id, int $user_id ): void {
		$marcar = self::OP_ARCHIVE === $op;
		$puede  = $marcar
			? ProcedureAccess::can_archive( $user_id, $procedure_id )
			: ProcedureAccess::can_unarchive( $user_id );
		if ( ! $puede ) {
			self::$rejected = $marcar
				? 'Este procedimiento no es suyo: solo lo marca como histórico el ámbito que lo convoca.'
				: 'Volver a abrir un procedimiento histórico solo lo hace quien administra el aplicativo.';
			return;
		}
		update_post_meta( $procedure_id, ProcedureMetaKeys::ARCHIVED, $marcar );


		if ( $marcar ) {
			EditLock::release( $procedure_id );
		}
		Shell::leave( self::url( $procedure_id, self::PANEL_PUBLISH, array( self::ARG_NOTICE => $marcar ? 'archivado' : 'desarchivado' ) ) );
	}








	private static function review( int $procedure_id, int $row_id ): void {
		if ( (int) get_post_field( 'post_parent', $row_id ) !== $procedure_id ) {
			self::$rejected = 'Esa solicitud no es de este procedimiento.';
			return;
		}
		$res = Applications::review( $row_id, self::field( self::FIELD_STATE ), self::textarea( self::FIELD_NOTE ) );
		if ( ! $res['ok'] ) {
			$textos         = array(
				'note'  => 'Para pedir subsanar o para excluir hay que escribir una nota: el centro tiene que leer qué se le pide o por qué.',
				'state' => 'Ese estado de revisión no existe.',
			);
			self::$rejected = $textos[ $res['error'] ] ?? 'No se ha podido revisar la solicitud.';
			return;
		}
		Shell::leave( self::url( $procedure_id, self::PANEL_APPLICATIONS, array( self::ARG_NOTICE => 'revisada' ) ) );
	}











	private static function export( int $procedure_id ): void {
		$cuerpo = Applications::csv( $procedure_id );
		$nombre = Applications::filename( (string) get_post_field( 'post_title', $procedure_id ) );

		Shell::send_header( 'Content-Type: text/csv; charset=utf-8' );
		Shell::send_header( 'Content-Disposition: attachment; filename="' . $nombre . '"' );
		Shell::send_header( 'Content-Length: ' . strlen( $cuerpo ) );
		Shell::send_header( 'Cache-Control: no-store, no-cache, must-revalidate' );
		echo $cuerpo; 
		Shell::leave();
	}







	private static function field( string $nombre ): string {

		return trim( sanitize_text_field( wp_unslash( (string) ( $_POST[ $nombre ] ?? '' ) ) ) );
	}







	private static function textarea( string $nombre ): string {

		return trim( sanitize_textarea_field( wp_unslash( (string) ( $_POST[ $nombre ] ?? '' ) ) ) );
	}












	public static function model(): array {
		$m = self::blank();

		if ( ! is_user_logged_in() ) {
			$m['aviso'] = 'Debe iniciar sesión con su usuario para gestionar procedimientos.';
			return $m;
		}


		$procedure_id = absint( wp_unslash( $_GET[ self::ARG_PROCEDURE ] ?? 0 ) );
		$pedido       = sanitize_key( wp_unslash( (string) ( $_GET[ self::ARG_PANEL ] ?? '' ) ) );
		$hecho        = sanitize_key( wp_unslash( (string) ( $_GET[ self::ARG_NOTICE ] ?? '' ) ) );


		$user_id = get_current_user_id();
		if ( $procedure_id <= 0 ) {
			return self::blank_procedure( $m, $user_id );
		}

		$procedimiento = get_post( $procedure_id );
		if ( ! $procedimiento instanceof \WP_Post || ProcedurePostType::POST_TYPE !== $procedimiento->post_type ) {
			$m['aviso'] = 'Ese procedimiento ya no existe. Elija uno en la lista para abrir su taller.';
			return $m;
		}



		if ( ! ProcedureAccess::can_open( $user_id, $procedure_id ) ) {
			$m['aviso']      = ProcedureAccess::why_not_editable( $user_id, $procedure_id );
			$m['aviso_tipo'] = 'error';
			return $m;
		}

		return self::fill( $m, $procedimiento, $user_id, $pedido, $hecho );
	}






	private static function blank(): array {
		return array(
			'aviso'         => '',
			'aviso_tipo'    => 'aviso',
			'nuevo'         => false,
			'procedure_id'  => 0,
			'title'         => '',
			'panel'         => self::PANEL_DATA,
			'panels'        => array(),
			'flash'         => array(
				'tipo'  => '',
				'texto' => '',
			),
			'can_edit'      => false,
			'groups'        => array(),
			'state_notice'  => '',
			'can_review'    => false,
			'can_publish'   => false,
			'can_archive'   => false,
			'can_unarchive' => false,
			'archived'      => false,
			'lock'          => EditLock::none(),
			'state'         => '',
			'state_label'   => '',
			'status'        => '',
			'status_label'  => '',
			'area_names'    => '',
			'foreign_areas' => array(),
			'view_url'      => '',
			'workspace_url' => Shell::url( 'workspace' ),
			'values'        => array(),
			'terms'         => array(
				'area'   => array(),
				'course' => array(),
			),
			'ownerships'    => ProcedureMetaKeys::ownerships(),
			'audiences'     => ProcedureMetaKeys::audiences(),
			'header_colors' => ProcedureMetaKeys::header_colors(),
			'questions'     => array(),
			'q_types'       => ProcedureQuestions::types(),
			'q_max'         => ProcedureQuestions::MAX,
			'applications'  => array(),
			'app_cols'      => array(),
			'review_states' => ApplicationMetaKeys::review_states(),
		);
	}








	private static function blank_procedure( array $m, int $user_id ): array {
		if ( ! ProcedureAccess::can_manage_procedures( $user_id ) ) {
			$m['aviso']      = self::CANNOT_CREATE;
			$m['aviso_tipo'] = 'error';
			return $m;
		}
		$m['nuevo']    = true;
		$m['can_edit'] = true;

		$m['groups']       = array_fill_keys( ProcedureState::editable_fields( ProcedureMetaKeys::STATE_DRAFT ), true );
		$m['flash']        = self::flash( '' );
		$m['status']       = 'draft';
		$m['status_label'] = self::status_label( 'draft' );
		$m['values']       = self::values( 0 );
		$m['terms']        = self::term_lists( $user_id );





		if ( '' === self::$rejected ) {
			$suyos = ProcedureAccess::scope_areas( $user_id );
			if ( 1 === count( $suyos ) ) {
				$m['values'][ self::FIELD_AREA ] = $suyos;
			}
			$quien = get_userdata( $user_id );
			if ( false !== $quien && '' !== (string) $quien->user_email ) {
				$m['values'][ ProcedureMetaKeys::CONTACT_EMAILS ] = array( (string) $quien->user_email );
			}
		}
		return $m;
	}











	private static function fill( array $m, \WP_Post $procedimiento, int $user_id, string $pedido, string $hecho ): array {
		$procedure_id = (int) $procedimiento->ID;
		$paneles      = self::panels( $procedure_id );

		$m['procedure_id'] = $procedure_id;
		$m['title']        = (string) $procedimiento->post_title;
		$m['panels']       = $paneles;
		$m['panel']        = isset( $paneles[ $pedido ] ) ? $pedido : self::PANEL_DATA;
		$m['flash']        = self::flash( $hecho );
		$m['can_edit']     = ProcedureAccess::can_edit( $user_id, $procedure_id );
		$m['can_review']   = ProcedureAccess::can_review( $user_id, $procedure_id );
		$m['archived']     = ProcedureAccess::is_archived( $procedure_id );



		$m['lock'] = EditLock::status( $procedure_id, (bool) $m['can_edit'] );
		if ( (int) $m['lock']['owner'] > 0 ) {
			$m['can_edit'] = false;
		}
		$libre              = 0 === (int) $m['lock']['owner'];
		$m['can_archive']   = $libre && ! $m['archived'] && ProcedureAccess::can_archive( $user_id, $procedure_id );
		$m['can_unarchive'] = $libre && $m['archived'] && ProcedureAccess::can_unarchive( $user_id );
		$m['can_publish']   = $m['can_edit'] && 'publish' !== $procedimiento->post_status && ProcedureAccess::can_publish( $user_id, $procedure_id );
		$m['state']         = ProcedureState::of_post( $procedure_id );
		$m['state_label']   = ProcedureState::label( (string) $m['state'] );



		foreach ( ProcedureState::groups() as $grupo ) {
			$m['groups'][ $grupo ] = true === $m['can_edit'] && ProcedureAccess::can_edit_group( $user_id, $procedure_id, $grupo );
		}
		$m['state_notice']  = true === $m['can_edit'] ? ProcedureState::why_locked( (string) $m['state'] ) : '';
		$m['status']        = (string) $procedimiento->post_status;
		$m['status_label']  = self::status_label( (string) $procedimiento->post_status );
		$m['area_names']    = self::area_names( ProcedureAccess::post_areas( $procedure_id ) );
		$foreign_ids        = ProcedureAccess::can_edit_all_areas( $user_id ) ? array() : array_diff( ProcedureAccess::post_areas( $procedure_id ), ProcedureAccess::scope_areas( $user_id ) );
		$all_labels         = ProcedureTaxonomies::area_options( 0, true );
		$m['foreign_areas'] = array_values( array_intersect_key( $all_labels, array_flip( $foreign_ids ) ) );
		$m['view_url']      = (string) get_permalink( $procedimiento );
		$m['values']        = self::values( $procedure_id );
		$m['terms']         = self::term_lists( $user_id );
		$m['questions']     = Applications::questions( $procedure_id );

		if ( self::PANEL_APPLICATIONS === $m['panel'] ) {
			$m['app_cols']     = Applications::columns( (array) $m['questions'] );
			$m['applications'] = Applications::rows( $procedure_id );
		}
		return $m;
	}








	private static function flash( string $hecho ): array {
		if ( '' !== self::$rejected ) {
			return array(
				'tipo'  => 'error',
				'texto' => self::$rejected,
			);
		}
		return array(
			'tipo'  => isset( self::DONE[ $hecho ] ) ? 'ok' : '',
			'texto' => (string) ( self::DONE[ $hecho ] ?? '' ),
		);
	}







	private static function values( int $procedure_id ): array {
		$guardado = array(
			self::FIELD_TITLE                       => $procedure_id > 0 ? (string) get_post_field( 'post_title', $procedure_id ) : '',
			self::FIELD_DESCRIPTION                 => $procedure_id > 0 ? (string) get_post_field( 'post_content', $procedure_id ) : '',
			self::FIELD_AREA                        => self::terms_of( $procedure_id, ProcedureTaxonomies::AREA ),
			self::FIELD_COURSE                      => (string) self::first_term( $procedure_id, ProcedureTaxonomies::COURSE ),
			ProcedureMetaKeys::OPENS_AT             => self::meta( $procedure_id, ProcedureMetaKeys::OPENS_AT ),
			ProcedureMetaKeys::CLOSES_AT            => self::meta( $procedure_id, ProcedureMetaKeys::CLOSES_AT ),
			ProcedureMetaKeys::AMEND_OPENS_AT       => self::meta( $procedure_id, ProcedureMetaKeys::AMEND_OPENS_AT ),
			ProcedureMetaKeys::AMEND_CLOSES_AT      => self::meta( $procedure_id, ProcedureMetaKeys::AMEND_CLOSES_AT ),
			ProcedureMetaKeys::CONTACT_EMAILS       => ProcedureInput::emails( get_post_meta( $procedure_id, ProcedureMetaKeys::CONTACT_EMAILS, true ) ),
			ProcedureMetaKeys::HEADER_COLOR         => self::meta( $procedure_id, ProcedureMetaKeys::HEADER_COLOR ),
			ProcedureMetaKeys::AUDIENCE             => $procedure_id > 0
				? ProcedureMetaKeys::in_list( self::meta( $procedure_id, ProcedureMetaKeys::AUDIENCE ), ProcedureMetaKeys::audiences(), ProcedureMetaKeys::AUDIENCE_SCHOOLS )
				: ProcedureMetaKeys::AUDIENCE_SCHOOLS,
			ProcedureMetaKeys::OWNERSHIP            => $procedure_id > 0
				? ProcedureInput::ownership( get_post_meta( $procedure_id, ProcedureMetaKeys::OWNERSHIP, true ) )
				: array_keys( ProcedureMetaKeys::ownerships() ),
			ProcedureMetaKeys::REQUIRES_COORDINATOR => get_post_meta( $procedure_id, ProcedureMetaKeys::REQUIRES_COORDINATOR, true ) ? '1' : '',
			ProcedureMetaKeys::COMMITMENTS          => self::meta( $procedure_id, ProcedureMetaKeys::COMMITMENTS ),
			ProcedureMetaKeys::RESOLUTION_URL       => self::meta( $procedure_id, ProcedureMetaKeys::RESOLUTION_URL ),
			ProcedureMetaKeys::PROVISIONAL_LIST_URL => self::meta( $procedure_id, ProcedureMetaKeys::PROVISIONAL_LIST_URL ),
			ProcedureMetaKeys::FINAL_LIST_URL       => self::meta( $procedure_id, ProcedureMetaKeys::FINAL_LIST_URL ),
		);
		if ( '' === self::$rejected ) {
			return $guardado;
		}



		$tecleado = self::submitted_data();
		$mapa     = array(
			self::FIELD_TITLE                  => 'title',
			self::FIELD_DESCRIPTION            => 'description',
			self::FIELD_AREA                   => 'areas',
			self::FIELD_COURSE                 => 'course',
			ProcedureMetaKeys::OPENS_AT        => 'opens_at',
			ProcedureMetaKeys::CLOSES_AT       => 'closes_at',
			ProcedureMetaKeys::AMEND_OPENS_AT  => 'amend_opens_at',
			ProcedureMetaKeys::AMEND_CLOSES_AT => 'amend_closes_at',
			ProcedureMetaKeys::CONTACT_EMAILS  => 'contact_emails',
			ProcedureMetaKeys::HEADER_COLOR    => 'header_color',
			ProcedureMetaKeys::AUDIENCE        => 'audience',
			ProcedureMetaKeys::OWNERSHIP       => 'ownership',
			ProcedureMetaKeys::COMMITMENTS     => 'commitments',
		);

		$enviados = array_keys( (array) $_POST );
		foreach ( $mapa as $campo => $clave ) {
			if ( in_array( $campo, $enviados, true ) ) {
				$valor              = 'ownership' === $clave ? ProcedureInput::ownership( $tecleado[ $clave ] ) : $tecleado[ $clave ];
				$guardado[ $campo ] = is_array( $valor ) ? $valor : (string) $valor;
			}
		}
		if ( in_array( self::FIELD_TITLE, $enviados, true ) ) {
			$guardado[ ProcedureMetaKeys::REQUIRES_COORDINATOR ] = $tecleado['requires_coordinator'] ? '1' : '';
		}
		foreach ( array( ProcedureMetaKeys::RESOLUTION_URL, ProcedureMetaKeys::PROVISIONAL_LIST_URL, ProcedureMetaKeys::FINAL_LIST_URL ) as $clave ) {
			if ( in_array( $clave, $enviados, true ) ) {
				$guardado[ $clave ] = self::field( $clave );
			}
		}
		return $guardado;
	}







	private static function term_lists( int $user_id ): array {
		$solo = ProcedureAccess::can_edit_all_areas( $user_id )
			? array()
			: ProcedureAccess::scope_areas( $user_id );
		return array(
			'area'   => self::area_tree( $solo ),
			'course' => self::term_options( ProcedureTaxonomies::COURSE ),
		);
	}













	private static function area_tree( array $solo = array() ): array {
		$terms = get_terms(
			array(
				'taxonomy'   => ProcedureTaxonomies::AREA,
				'hide_empty' => false,
			)
		);
		if ( ! is_array( $terms ) ) {
			return array();
		}

		$nombres = array();
		foreach ( $terms as $term ) {
			if ( $term instanceof \WP_Term && ( array() === $solo || in_array( (int) $term->term_id, $solo, true ) ) ) {
				$nombres[ (int) $term->term_id ] = array( (int) $term->parent, (string) $term->name );
			}
		}

		$hijos = array();
		foreach ( $nombres as $id => $dato ) {
			$padre             = isset( $nombres[ $dato[0] ] ) ? (int) $dato[0] : 0;
			$hijos[ $padre ][] = (int) $id;
		}
		return self::branch( $hijos, $nombres, 0 );
	}









	private static function branch( array $hijos, array $nombres, int $padre ): array {
		$out = array();
		foreach ( (array) ( $hijos[ $padre ] ?? array() ) as $id ) {
			$out[] = array(
				'id'       => (int) $id,
				'name'     => (string) $nombres[ $id ][1],
				'children' => self::branch( $hijos, $nombres, (int) $id ),
			);
		}
		return $out;
	}








	private static function term_options( string $taxonomy, array $solo = array() ): array {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'fields'     => 'id=>name',
			)
		);
		if ( ! is_array( $terms ) ) {
			return array();
		}
		return array() === $solo ? $terms : array_intersect_key( $terms, array_flip( $solo ) );
	}








	private static function terms_of( int $procedure_id, string $taxonomy ): array {
		if ( $procedure_id <= 0 ) {
			return array();
		}
		return ProcedureInput::term_ids( wp_get_post_terms( $procedure_id, $taxonomy, array( 'fields' => 'ids' ) ) );
	}








	private static function first_term( int $procedure_id, string $taxonomy ): int {
		if ( $procedure_id <= 0 ) {
			return 0;
		}
		$ids = wp_get_post_terms( $procedure_id, $taxonomy, array( 'fields' => 'ids' ) );
		return is_array( $ids ) ? (int) ( $ids[0] ?? 0 ) : 0;
	}







	private static function area_names( array $ids ): string {
		$nombres = array();
		foreach ( $ids as $term_id ) {
			$term = get_term( (int) $term_id, ProcedureTaxonomies::AREA );
			if ( $term instanceof \WP_Term ) {
				$nombres[] = $term->name;
			}
		}
		return array() === $nombres ? 'Sin ámbito' : implode( ' · ', $nombres );
	}







	private static function status_label( string $status ): string {
		$objeto = get_post_status_object( $status );
		return null !== $objeto ? (string) $objeto->label : $status;
	}








	private static function meta( int $post_id, string $clave ): string {
		return $post_id > 0 ? (string) get_post_meta( $post_id, $clave, true ) : '';
	}







	public static function render( $atts = array() ): string {
		unset( $atts );
		Assets::enqueue();
		$m = self::model();


		return EditLock::render( EditLock::claim( (array) $m['lock'] ) ) . ProcedureEditorView::html( $m );
	}
}








namespace Prc\PublicFront\View;

use Prc\Domain\DateRange;
use Prc\Domain\ProcedureQuestions;
use Prc\Meta\ApplicationMetaKeys;
use Prc\PublicFront\ApplicationFiles;
use Prc\PublicFront\ApplyForm;
use Prc\PublicFront\Assets;
use Prc\PublicFront\Shell;


















final class ApplyFormView {






	private const ANCHO = array(
		3  => 'prc-campo--cuarto',
		4  => 'prc-campo--tercio',
		6  => 'prc-campo--medio',
		12 => 'prc-campo--completo',
	);






	private const REVISION_TONO = array(
		ApplicationMetaKeys::REVIEW_SUBMITTED => 'info',
		ApplicationMetaKeys::REVIEW_AMEND     => 'warning',
		ApplicationMetaKeys::REVIEW_ADMITTED  => 'success',
		ApplicationMetaKeys::REVIEW_EXCLUDED  => 'danger',
	);






	private const REVISION_ICONO = array(
		ApplicationMetaKeys::REVIEW_SUBMITTED => 'description',
		ApplicationMetaKeys::REVIEW_AMEND     => 'edit_note',
		ApplicationMetaKeys::REVIEW_ADMITTED  => 'task_alt',
		ApplicationMetaKeys::REVIEW_EXCLUDED  => 'error',
	);







	public static function html( array $m ): string {
		if ( '' !== (string) $m['aviso'] ) {
			return Shell::render( 'Solicitud', '', Shell::notice( 'aviso', (string) $m['aviso'] ) . self::mine_link( $m ) );
		}

		$flash = (array) $m['flash'];

		ob_start();
		echo self::head( $m ); 
		echo Shell::notice( (string) $flash['tipo'], (string) $flash['texto'] ); 
		echo self::status_card( $m ); 

		if ( true === $m['can_submit'] ) {
			echo self::form( $m ); 
		} else {
			echo Shell::notice( 'aviso', (string) $m['why'] ); 
			echo self::summary( $m ); 
		}
		return Shell::render( '', '', (string) ob_get_clean() );
	}







	private static function head( array $m ): string {
		$plazo       = DateRange::of( (string) $m['opens_at'], (string) $m['closes_at'] );
		$subsanacion = DateRange::of( (string) $m['amend_opens_at'], (string) $m['amend_closes_at'] );

		ob_start();
		?>
		<p class="prc-sub"><a href="<?php echo esc_url( (string) $m['mine_url'] ); ?>">&larr; Mi centro</a></p>
		<div class="prc-h1-fila">
			<h1 class="prc-h1"><?php echo esc_html( (string) $m['title'] ); ?></h1>
			<span class="<?php echo esc_attr( Assets::state_class( (string) $m['state'] ) ); ?>"><?php echo esc_html( (string) $m['state_label'] ); ?></span>
			<span class="prc-acciones">
				<?php echo PanelParts::icon_link( (string) $m['view_url'], 'ojo', 'Ver la ficha pública' ); ?>
			</span>
		</div>
		<?php if ( '' !== $plazo ) : ?>
			<p class="prc-sub"><?php echo esc_html( 'Plazo de solicitud: ' . lcfirst( $plazo ) ); ?></p>
		<?php endif; ?>
		<?php if ( '' !== $subsanacion ) : ?>
			<p class="prc-sub"><?php echo esc_html( 'Plazo de subsanación: ' . lcfirst( $subsanacion ) ); ?></p>
		<?php endif; ?>
		<?php
		return (string) ob_get_clean();
	}











	private static function status_card( array $m ): string {
		$s = (array) $m['application'];
		if ( array() === $s ) {
			return '';
		}

		$estado = (string) $s['state'];
		$tono   = self::REVISION_TONO[ $estado ] ?? 'info';
		$icono  = self::REVISION_ICONO[ $estado ] ?? 'description';
		$rotulo = (string) $s['state_label'];

		if ( ApplicationMetaKeys::REVIEW_AMEND === $estado && '' !== (string) $m['amend_closes_at'] ) {
			$rotulo .= ' · hasta el ' . PanelParts::day( (string) $m['amend_closes_at'] );
		}

		ob_start();
		?>
		<div class="prc-solicitud-estado <?php echo esc_attr( Assets::alert_class( $tono ) ); ?>" role="status">
			<span class="material-symbols-outlined" aria-hidden="true"><?php echo esc_html( $icono ); ?></span>
			<span class="prc-solicitud-estado__cuerpo">
				<span class="prc-solicitud-estado__estado">Su solicitud: <?php echo esc_html( $rotulo ); ?></span>
				<span class="prc-solicitud-estado__fecha"><?php echo esc_html( 'Presentada el ' . (string) $s['date'] . '.' ); ?></span>
				<?php if ( '' !== (string) $s['note'] ) : ?>
					<span class="prc-solicitud-estado__nota"><?php echo esc_html( (string) $s['note'] ); ?></span>
				<?php endif; ?>
				<?php if ( '' !== (string) $m['final_list_url'] ) : ?>
					<span class="prc-solicitud-estado__enlace"><a href="<?php echo esc_url( (string) $m['final_list_url'] ); ?>" rel="noopener">Listado definitivo de admitidos</a></span>
				<?php endif; ?>
			</span>
		</div>
		<?php
		return (string) ob_get_clean();
	}











	private static function privacy(): string {
		$texto = trim( (string) ( ProcedureChrome::chrome()['privacy_notice'] ?? '' ) );
		if ( '' === $texto ) {
			return '';
		}
		return '<div class="prc-aviso-legal">' . wp_kses_post( $texto ) . '</div>';
	}







	private static function form( array $m ): string {
		$v      = (array) $m['values'];
		$spec   = (array) $m['spec'];
		$centro = (array) $m['centre'];
		$quien  = (array) ( $m['applicant'] ?? array() );
		$nueva  = array() === (array) $m['application'];



		$del_centro = self::locked(
			'prc-centre-code',
			'Código',
			(string) $centro['code'],
			3,
			'El centro es el de su cuenta: no se puede cambiar desde aquí.'
		) . self::locked(
			'prc-centre',
			'Denominación',
			(string) $centro['name'],
			6,
			'' === (string) $centro['name'] ? 'Su código no está en el catálogo; se solicita igual.' : ''
		);
		$de_quien   = self::locked(
			'prc-applicant',
			'Nombre y apellidos',
			(string) ( $quien['name'] ?? '' ),
			6
		) . self::locked(
			'prc-applicant-email',
			'Correo electrónico',
			(string) ( $quien['email'] ?? '' ),
			6,
			'Se lee de su cuenta: para cambiarlo, cambie el correo de su perfil.'
		);



		$subidos = array();
		foreach ( (array) ( $m['application']['files'] ?? array() ) as $clave => $descriptor ) {
			if ( is_array( $descriptor ) ) {
				$descriptor['application']  = (int) ( $m['application']['id'] ?? 0 );
				$subidos[ (string) $clave ] = $descriptor;
			}
		}

		ob_start();
		?>
		<?php ?>
		<form class="prc-form prc-form--solicitud" method="post" action="" enctype="multipart/form-data">
			<?php wp_nonce_field( ApplyForm::NONCE_ACTION, ApplyForm::NONCE_FIELD, false ); ?>
			<input type="hidden" name="<?php echo esc_attr( ApplyForm::FIELD_OP ); ?>" value="<?php echo esc_attr( ApplyForm::OP_APPLY ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( ApplyForm::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />

			<?php echo self::privacy(); ?>

			<section class="prc-seccion">
				<h3 class="prc-seccion__titulo">DATOS DEL CENTRO EDUCATIVO</h3>
				<div class="prc-seccion__cuerpo">
					<?php echo $del_centro; ?>
				</div>
			</section>

			<section class="prc-seccion">
				<h3 class="prc-seccion__titulo">DATOS PERSONALES DE LA DIRECCIÓN DEL CENTRO</h3>
				<div class="prc-seccion__cuerpo">
					<?php echo $de_quien; ?>
					<div class="prc-campo prc-campo--cuarto prc-campo--abre-fila">
						<?php echo self::label( 'prc-position', 'Cargo en el centro', true ); ?>
						<select class="prc-control" id="prc-position" name="<?php echo esc_attr( ApplyForm::FIELD_POSITION ); ?>" required>
							<option value="">— Elija —</option>
							<?php foreach ( (array) $m['positions'] as $clave => $rotulo ) : ?>
								<option value="<?php echo esc_attr( (string) $clave ); ?>" <?php selected( (string) $clave, (string) $v['position'] ); ?>><?php echo esc_html( (string) $rotulo ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
			</section>

			<?php if ( '' !== trim( (string) $spec['commitments'] ) ) : ?>
				<section class="prc-seccion">
					<h3 class="prc-seccion__titulo">SOLICITA</h3>
					<div class="prc-seccion__cuerpo">
						<div class="prc-nota prc-campo--completo">
							<p>La participación de su centro en la <em>presente convocatoria</em>, comprometiéndose a cumplir los siguientes compromisos:</p>
							<p><?php echo nl2br( esc_html( (string) $spec['commitments'] ) ); ?></p>
						</div>
						<div class="prc-campo prc-campo--completo">
							<div class="prc-opciones prc-opciones--vertical">
								<div class="prc-opcion">
									<label for="prc-accept">
										<input type="checkbox" id="prc-accept" name="<?php echo esc_attr( ApplyForm::FIELD_ACCEPT ); ?>" value="1" required <?php checked( (bool) $v['accept'] ); ?> />
										El centro acepta estos compromisos
									</label>
								</div>
							</div>
						</div>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( array() !== (array) $spec['questions'] ) : ?>
				<section class="prc-seccion prc-seccion--sin-titulo" aria-labelledby="prc-preguntas">
					<h3 class="screen-reader-text" id="prc-preguntas">Preguntas del procedimiento</h3>
					<div class="prc-seccion__cuerpo">
						<?php foreach ( (array) $spec['questions'] as $pregunta ) : ?>
							<?php echo self::question( (array) $pregunta, (array) $v['answers'], $subidos ); ?>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( ! empty( $spec['requires_coordinator'] ) ) : ?>
				<section class="prc-seccion">
					<h3 class="prc-seccion__titulo">DATOS DE LA COORDINACIÓN</h3>
					<div class="prc-seccion__cuerpo">
						<div class="prc-nota prc-campo--completo">
							<p>La persona coordinadora acepta el compromiso de colaborar en el desarrollo del programa, proyecto o red tal como se expresa en la convocatoria del mismo.</p>
							<p><strong>La persona coordinadora es:</strong></p>
						</div>
						<div class="prc-campo prc-campo--medio prc-campo--abre-fila">
							<?php echo self::label( 'prc-coordinator-name', 'Nombre y apellidos', true ); ?>
							<input class="prc-control" type="text" id="prc-coordinator-name" name="<?php echo esc_attr( ApplyForm::FIELD_COORD_NAME ); ?>" required
								value="<?php echo esc_attr( (string) $v['coordinator_name'] ); ?>" />
						</div>
						<div class="prc-campo prc-campo--cuarto">
							<?php echo self::label( 'prc-coordinator-email', 'Correo electrónico', true ); ?>
							<input class="prc-control" type="email" id="prc-coordinator-email" name="<?php echo esc_attr( ApplyForm::FIELD_COORD_EMAIL ); ?>" required
								value="<?php echo esc_attr( (string) $v['coordinator_email'] ); ?>" />
						</div>
					</div>
				</section>
			<?php endif; ?>

			<div class="prc-form__envio">
				<button class="prc-boton-enviar" type="submit">
					<span class="material-symbols-outlined" aria-hidden="true">send</span>
					<?php echo esc_html( $nueva ? 'Presentar la solicitud' : 'Guardar los cambios' ); ?>
				</button>
			</div>
		</form>
		<?php
		return (string) ob_get_clean();
	}













	private static function label( string $id, string $rotulo, bool $obligatorio, string $etiqueta = 'label' ): string {
		$atributo = 'label' === $etiqueta ? ' for="' . esc_attr( $id ) . '"' : ' id="' . esc_attr( $id ) . '"';

		return '<' . $etiqueta . ' class="prc-campo__etiqueta"' . $atributo . '>'
			. esc_html( $rotulo )
			. ( $obligatorio ? ' <span class="prc-campo__obligatorio" aria-hidden="true">*</span>' : '' )
			. '</' . $etiqueta . '>';
	}
















	private static function locked( string $id, string $rotulo, string $valor, int $columnas, string $ayuda = '' ): string {
		$ancho  = self::ANCHO[ $columnas ] ?? self::ANCHO[12];
		$ayudas = '' !== $ayuda ? ' aria-describedby="' . esc_attr( $id . '-ayuda' ) . '"' : '';

		return '<div class="prc-campo ' . esc_attr( $ancho ) . '">'
			. self::label( $id, $rotulo, false )
			. '<input class="prc-control prc-control--bloqueado" type="text" id="' . esc_attr( $id ) . '"'
			. ' value="' . esc_attr( '' !== $valor ? $valor : '—' ) . '" readonly' . $ayudas . ' />'
			. ( '' !== $ayuda
				? '<div class="prc-campo__ayuda" id="' . esc_attr( $id . '-ayuda' ) . '">' . esc_html( $ayuda ) . '</div>'
				: '' )
			. '</div>';
	}














	private static function question( array $p, array $answers, array $files = array() ): string {
		$key    = (string) $p['key'];
		$subido = isset( $files[ $key ] ) && is_array( $files[ $key ] ) ? $files[ $key ] : array();
		$nombre = ApplyForm::FIELD_ANSWERS . '[' . $key . ']';
		$valor  = $answers[ $key ] ?? '';
		$tipo   = (string) $p['type'];
		$obliga = ! empty( $p['required'] );
		$ayuda  = (string) $p['help'];
		$id     = 'prc-q-' . $key;
		$pie    = '' !== $ayuda ? ' aria-describedby="' . esc_attr( $id . '-ayuda' ) . '"' : '';
		$ancho  = in_array( $tipo, array( ProcedureQuestions::TYPE_TEXT, ProcedureQuestions::TYPE_FILE ), true )
			? self::ANCHO[12]
			: self::ANCHO[6];

		ob_start();
		?>
		<div class="prc-campo prc-pregunta <?php echo esc_attr( $ancho ); ?>">
			<?php if ( ProcedureQuestions::TYPE_TEXT === $tipo ) : ?>
				<label class="prc-campo__etiqueta prc-campo__etiqueta--enunciado" for="<?php echo esc_attr( $id ); ?>">
					<strong><?php echo esc_html( (string) $p['label'] ); ?></strong>
					<?php if ( $obliga ) : ?>
						<span class="prc-campo__obligatorio" aria-hidden="true">*</span>
					<?php endif; ?>
				</label>
				<textarea id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $nombre ); ?>" class="prc-control" rows="3"
					placeholder="Escriba aquí"
					maxlength="<?php echo esc_attr( (string) ProcedureQuestions::TEXT_MAX ); ?>" <?php echo $obliga ? 'required' : ''; ?>
					<?php echo $pie; ?>><?php echo esc_textarea( is_scalar( $valor ) ? (string) $valor : '' ); ?></textarea>
			<?php elseif ( ProcedureQuestions::TYPE_FILE === $tipo ) : ?>
				<?php echo self::label( $id, (string) $p['label'], $obliga ); ?>
				<?php if ( array() !== $subido ) : ?>
					<p class="prc-campo__adjunto">
						Ya adjuntó
						<a href="<?php echo esc_url( ApplicationFiles::url( (int) ( $subido['application'] ?? 0 ), (string) ( $subido['id'] ?? '' ) ) ); ?>" download><?php echo esc_html( (string) ( $subido['name'] ?? '' ) ); ?></a>.
						Adjunte otro solo si quiere sustituirlo.
					</p>
				<?php endif; ?>
				<input type="file" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( ApplicationFiles::FIELD . '[' . $key . ']' ); ?>"
					class="prc-control" accept="<?php echo esc_attr( implode( ',', array_values( ApplicationFiles::mimes() ) ) ); ?>"
					<?php echo $obliga && array() === $subido ? 'required' : ''; ?>
					<?php echo $pie; ?> />
				<div class="prc-campo__ayuda">
					Un solo documento, de hasta <?php echo esc_html( size_format( ApplicationFiles::max_bytes() ) ); ?>.
					Se admiten PDF, JPG, PNG, DOCX y ODT.
				</div>
			<?php elseif ( ProcedureQuestions::TYPE_YESNO === $tipo ) : ?>
				<div class="prc-opciones prc-opciones--vertical">
					<div class="prc-opcion">
						<label for="<?php echo esc_attr( $id ); ?>">
							<input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $nombre ); ?>" value="1"
								<?php checked( (bool) $valor ); ?> <?php echo $obliga ? 'required' : ''; ?>
								<?php echo $pie; ?> />
							<?php echo esc_html( (string) $p['label'] ); ?>
						</label>
					</div>
				</div>
			<?php else : ?>
				<?php
				$varias = ProcedureQuestions::TYPE_MULTIPLE === $tipo;
				$unica  = is_scalar( $valor ) ? (string) $valor : '';
				echo self::label( $id . '-rotulo', (string) $p['label'], $obliga, 'p' ); 
				?>
				<div class="prc-opciones prc-opciones--vertical" role="<?php echo $varias ? 'group' : 'radiogroup'; ?>"
					aria-labelledby="<?php echo esc_attr( $id . '-rotulo' ); ?>"
					<?php echo $pie; ?>>
					<?php foreach ( (array) $p['choices'] as $i => $opcion ) : ?>
						<?php
						$marcada = $varias
							? in_array( (string) $opcion, array_map( 'strval', (array) $valor ), true )
							: $unica === (string) $opcion;
						?>
						<div class="prc-opcion">
							<label for="<?php echo esc_attr( $id . '-' . (string) $i ); ?>">
								<input type="<?php echo $varias ? 'checkbox' : 'radio'; ?>" id="<?php echo esc_attr( $id . '-' . (string) $i ); ?>"
									name="<?php echo esc_attr( $nombre . ( $varias ? '[]' : '' ) ); ?>"
									value="<?php echo esc_attr( (string) $opcion ); ?>" <?php checked( $marcada ); ?>
									<?php echo $obliga && ! $varias ? 'required' : ''; ?> />
								<?php echo esc_html( (string) $opcion ); ?>
							</label>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<?php if ( '' !== $ayuda ) : ?>
				<div class="prc-campo__ayuda" id="<?php echo esc_attr( $id . '-ayuda' ); ?>"><?php echo esc_html( $ayuda ); ?></div>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}











	private static function attached( array $application, string $key ): string {
		$ficheros = (array) ( $application['files'] ?? array() );
		$doc      = isset( $ficheros[ $key ] ) && is_array( $ficheros[ $key ] ) ? $ficheros[ $key ] : array();
		if ( array() === $doc ) {
			return '&mdash;';
		}
		return sprintf(
			'<a href="%1$s" download>%2$s</a>',
			esc_url( ApplicationFiles::url( (int) ( $application['id'] ?? 0 ), (string) ( $doc['id'] ?? '' ) ) ),
			esc_html( (string) ( $doc['name'] ?? '' ) )
		);
	}







	private static function summary( array $m ): string {
		$s = (array) $m['application'];
		if ( array() === $s ) {
			return '';
		}
		$coord     = (array) $s['coordinator'];
		$preguntas = (array) ( $m['spec']['questions'] ?? array() );

		ob_start();
		?>
		<section class="prc-seccion prc-solicitud-resumen">
			<h3 class="prc-seccion__titulo">LO QUE PRESENTÓ</h3>
			<dl class="prc-respuestas">
				<dt>Cargo</dt>
				<dd><?php echo esc_html( (string) $s['position'] ); ?></dd>
				<?php if ( '' !== (string) ( $coord['name'] ?? '' ) ) : ?>
					<dt>Persona coordinadora</dt>
					<dd><?php echo esc_html( trim( (string) $coord['name'] . ' ' . (string) ( $coord['email'] ?? '' ) ) ); ?></dd>
				<?php endif; ?>
				<?php foreach ( $preguntas as $pregunta ) : ?>
					<dt><?php echo esc_html( (string) $pregunta['label'] ); ?></dt>
					<?php


					$respuesta = ProcedureQuestions::TYPE_FILE === (string) $pregunta['type']
						? self::attached( $s, (string) $pregunta['key'] )
						: esc_html( ProcedureQuestions::as_text( (array) $pregunta, $s['answers'][ $pregunta['key'] ] ?? null ) );
					?>
					<dd><?php echo $respuesta; ?></dd>
				<?php endforeach; ?>
			</dl>
		</section>
		<?php
		return (string) ob_get_clean();
	}







	private static function mine_link( array $m ): string {
		$url = (string) $m['mine_url'];
		if ( '' === $url ) {
			return '';
		}
		return '<p><a class="' . esc_attr( Assets::button_class( true ) ) . '" href="' . esc_url( $url ) . '">Las solicitudes de mi centro</a></p>';
	}
}








namespace Prc\PublicFront;

use Prc\Access\ProcedureAccess;
use Prc\Domain\ApplicationInput;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ProcedurePostType;
use Prc\PublicFront\View\ApplyFormView;













final class ApplyForm {

	public const SHORTCODE = 'prc_apply';




	public const ARG_PROCEDURE = Shell::ARG_PROCEDURE;




	public const ARG_NOTICE = 'aviso';




	public const FIELD_OP = 'prc_apply_op';

	public const OP_APPLY = 'apply';

	public const FIELD_PROCEDURE   = 'prc_apply_procedure';
	public const FIELD_POSITION    = 'prc_position';
	public const FIELD_COORD_NAME  = 'prc_coordinator_name';
	public const FIELD_COORD_EMAIL = 'prc_coordinator_email';
	public const FIELD_ACCEPT      = 'prc_accept';
	public const FIELD_ANSWERS     = 'prc_q';

	public const NONCE_ACTION = 'prc_apply';
	public const NONCE_FIELD  = '_prc_apply_nonce';






	private const DONE = array(
		'presentada' => 'Solicitud presentada. Puede volver a esta pantalla para consultarla, y editarla mientras el plazo siga abierto.',
		'guardada'   => 'Solicitud guardada.',
	);









	private static $rejected = '';






	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'render' ) );
		add_action( 'init', array( self::class, 'handle' ), 20 );
	}








	public static function url( int $procedure_id, array $args = array() ): string {
		if ( $procedure_id > 0 ) {
			$args[ self::ARG_PROCEDURE ] = $procedure_id;
		}
		return Shell::url( 'apply', $args );
	}













	public static function window( string $state, string $review_state ): string {
		if ( ProcedureState::accepts_applications( $state ) ) {
			return '';
		}
		if ( ProcedureMetaKeys::STATE_AMENDMENT === $state ) {
			return ApplicationMetaKeys::REVIEW_AMEND === $review_state
				? ''
				: 'El procedimiento está en plazo de subsanación: solo pueden editar su solicitud los centros a los que se ha pedido subsanar.';
		}
		if ( ProcedureMetaKeys::STATE_UPCOMING === $state ) {
			return 'El plazo de solicitud todavía no está abierto.';
		}
		return 'El plazo de solicitud está cerrado.';
	}






	public static function handle(): void {

		$op    = sanitize_key( wp_unslash( (string) ( $_POST[ self::FIELD_OP ] ?? '' ) ) );
		$nonce = sanitize_text_field( wp_unslash( (string) ( $_POST[ self::NONCE_FIELD ] ?? '' ) ) );

		if ( self::OP_APPLY !== $op || ! is_user_logged_in() ) {
			return;
		}
		if ( false === wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			self::$rejected = 'El formulario estuvo abierto demasiado tiempo y el envío caducó. Vuelva a enviarlo.';
			return;
		}


		$procedure_id = absint( wp_unslash( $_POST[ self::FIELD_PROCEDURE ] ?? 0 ) );
		$user_id      = get_current_user_id();

		if ( ! ProcedureAccess::can_apply( $user_id, $procedure_id ) ) {
			self::$rejected = self::why_not_allowed( $user_id, $procedure_id );
			return;
		}
		$centro    = Applications::centre_of( $user_id );
		$existente = Applications::find( $procedure_id, $centro['code'] );
		$cerrado   = self::window(
			ProcedureState::of_post( $procedure_id ),
			null !== $existente ? (string) get_post_meta( $existente, ApplicationMetaKeys::REVIEW_STATE, true ) : ''
		);
		if ( '' !== $cerrado ) {
			self::$rejected = $cerrado;
			return;
		}

		$spec     = Applications::spec( $procedure_id );
		$revisado = ApplicationInput::validate( self::submitted(), $spec );




		$ficheros = ApplicationFiles::submitted(
			(array) $spec['questions'],
			null !== $existente ? ApplicationFiles::descriptors( $existente ) : array()
		);

		if ( ! $revisado['ok'] || ! $ficheros['ok'] ) {
			$porque         = ApplicationFiles::why( $ficheros['errors'] );
			self::$rejected = '' !== $porque && $revisado['ok']
				? $porque
				: ApplicationInput::why( $revisado['errors'], (array) $spec['questions'] );
			return;
		}

		$id = Applications::save( $procedure_id, $user_id, $revisado['data'] );
		if ( $id <= 0 ) {
			self::$rejected = 'No se ha podido guardar la solicitud. Vuelva a intentarlo.';
			return;
		}





		if ( ! ApplicationFiles::store_all( $id, $ficheros['files'] ) ) {
			if ( null === $existente ) {
				wp_delete_post( $id, true );
			}
			self::$rejected = 'No se ha podido guardar el documento que adjuntó, así que la solicitud no se ha presentado. Vuelva a intentarlo.';
			return;
		}
		Shell::leave( self::url( $procedure_id, array( self::ARG_NOTICE => null === $existente ? 'presentada' : 'guardada' ) ) );
	}






	private static function submitted(): array {


		$respuestas = (array) wp_unslash( $_POST[ self::FIELD_ANSWERS ] ?? array() );
		$acepta     = ! empty( $_POST[ self::FIELD_ACCEPT ] );


		return array(
			'position'          => self::field( self::FIELD_POSITION ),
			'coordinator_name'  => self::field( self::FIELD_COORD_NAME ),
			'coordinator_email' => self::field( self::FIELD_COORD_EMAIL ),
			'accept'            => $acepta,
			'answers'           => self::clean_answers( $respuestas ),
		);
	}







	private static function clean_answers( array $raw ): array {
		$out = array();
		foreach ( $raw as $key => $valor ) {
			if ( is_array( $valor ) ) {
				$out[ (string) $key ] = array_map( 'sanitize_text_field', array_filter( $valor, 'is_scalar' ) );
			} elseif ( is_scalar( $valor ) ) {
				$out[ (string) $key ] = sanitize_textarea_field( (string) $valor );
			}
		}
		return $out;
	}







	private static function field( string $nombre ): string {

		return trim( sanitize_text_field( wp_unslash( (string) ( $_POST[ $nombre ] ?? '' ) ) ) );
	}








	private static function why_not_allowed( int $user_id, int $procedure_id ): string {
		if ( ! user_can( $user_id, ProcedureAccess::CAP_APPLY ) ) {
			return 'Su perfil no presenta solicitudes de centro. Si forma parte del equipo directivo de un centro, pídalo a quien administre el aplicativo.';
		}
		if ( '' === Applications::centre_of( $user_id )['code'] ) {
			return 'Su cuenta no tiene centro asignado, así que no puede presentar solicitudes. El código de centro lo pone quien administra el aplicativo.';
		}
		if ( ProcedurePostType::POST_TYPE !== get_post_type( $procedure_id ) || 'publish' !== get_post_status( $procedure_id ) ) {
			return 'Ese procedimiento no existe o todavía no está publicado.';
		}
		return 'Este procedimiento no se dirige a centros de la titularidad del suyo.';
	}






	public static function model(): array {
		$m = array(
			'aviso'           => '',
			'flash'           => array(
				'tipo'  => '',
				'texto' => '',
			),
			'procedure_id'    => 0,
			'title'           => '',
			'view_url'        => '',
			'mine_url'        => Shell::url( 'mine' ),
			'state'           => '',
			'state_label'     => '',
			'opens_at'        => '',
			'closes_at'       => '',
			'amend_opens_at'  => '',
			'amend_closes_at' => '',
			'final_list_url'  => '',
			'centre'          => array(
				'code'      => '',
				'name'      => '',
				'ownership' => '',
			),


			'applicant'       => array(
				'name'  => '',
				'email' => '',
			),
			'application'     => array(),
			'max_file_size'   => ApplicationFiles::max_bytes(),
			'file_mimes'      => ApplicationFiles::mimes(),
			'can_submit'      => false,
			'why'             => '',
			'spec'            => array(
				'requires_coordinator' => false,
				'commitments'          => '',
				'questions'            => array(),
			),
			'positions'       => ApplicationMetaKeys::positions(),
			'review_states'   => ApplicationMetaKeys::review_states(),
			'values'          => array(),
		);

		if ( ! is_user_logged_in() ) {
			$m['aviso'] = 'Debe iniciar sesión con su usuario para presentar la solicitud de su centro.';
			return $m;
		}
		$user_id = get_current_user_id();
		if ( ! user_can( $user_id, ProcedureAccess::CAP_APPLY ) ) {
			$m['aviso'] = self::why_not_allowed( $user_id, 0 );
			return $m;
		}
		$quien          = wp_get_current_user();
		$m['applicant'] = array(
			'name'  => (string) $quien->display_name,
			'email' => (string) $quien->user_email,
		);
		$m['centre']    = Applications::centre_of( $user_id );
		if ( '' === $m['centre']['code'] ) {
			$m['aviso'] = self::why_not_allowed( $user_id, 0 );
			return $m;
		}


		$procedure_id = absint( wp_unslash( $_GET[ self::ARG_PROCEDURE ] ?? 0 ) );
		$hecho        = sanitize_key( wp_unslash( (string) ( $_GET[ self::ARG_NOTICE ] ?? '' ) ) );


		if ( ProcedurePostType::POST_TYPE !== get_post_type( $procedure_id ) || 'publish' !== get_post_status( $procedure_id ) ) {
			$m['aviso'] = $procedure_id > 0
				? 'Ese procedimiento no existe o todavía no está publicado.'
				: 'Abra la solicitud desde la ficha del procedimiento, o desde las solicitudes de su centro.';
			return $m;
		}

		$m['procedure_id']    = $procedure_id;
		$m['title']           = (string) get_post_field( 'post_title', $procedure_id );
		$m['view_url']        = (string) get_permalink( $procedure_id );
		$m['state']           = ProcedureState::of_post( $procedure_id );
		$m['state_label']     = ProcedureState::label( (string) $m['state'] );
		$m['opens_at']        = (string) get_post_meta( $procedure_id, ProcedureMetaKeys::OPENS_AT, true );
		$m['closes_at']       = (string) get_post_meta( $procedure_id, ProcedureMetaKeys::CLOSES_AT, true );
		$m['amend_opens_at']  = (string) get_post_meta( $procedure_id, ProcedureMetaKeys::AMEND_OPENS_AT, true );
		$m['amend_closes_at'] = (string) get_post_meta( $procedure_id, ProcedureMetaKeys::AMEND_CLOSES_AT, true );
		$m['final_list_url']  = (string) get_post_meta( $procedure_id, ProcedureMetaKeys::FINAL_LIST_URL, true );
		$m['spec']            = Applications::spec( $procedure_id );

		$existente = Applications::find( $procedure_id, $m['centre']['code'] );
		if ( null !== $existente ) {
			$meta             = Applications::meta( $existente );
			$estado           = (string) $meta[ ApplicationMetaKeys::REVIEW_STATE ];
			$m['application'] = array(
				'id'          => $existente,
				'date'        => (string) get_the_date( 'd-m-Y', $existente ),
				'state'       => $estado,
				'state_label' => (string) ( ApplicationMetaKeys::review_states()[ $estado ] ?? '' ),
				'note'        => (string) $meta[ ApplicationMetaKeys::REVIEW_NOTE ],
				'position'    => (string) ( ApplicationMetaKeys::positions()[ $meta[ ApplicationMetaKeys::APPLICANT_POSITION ] ] ?? '' ),
				'coordinator' => (array) $meta[ ApplicationMetaKeys::COORDINATOR ],
				'answers'     => (array) $meta[ ApplicationMetaKeys::ANSWERS ],
				'files'       => ApplicationFiles::descriptors( $existente ),
			);
		}



		if ( ! ProcedureAccess::can_apply( $user_id, $procedure_id ) ) {
			$m['why'] = self::why_not_allowed( $user_id, $procedure_id );
		} else {
			$m['why'] = self::window( (string) $m['state'], (string) ( $m['application']['state'] ?? '' ) );
		}
		$m['can_submit'] = '' === $m['why'];
		$m['values']     = self::values( (array) $m['application'] );
		$m['flash']      = '' !== self::$rejected
			? array(
				'tipo'  => 'error',
				'texto' => self::$rejected,
			)
			: array(
				'tipo'  => isset( self::DONE[ $hecho ] ) ? 'ok' : '',
				'texto' => (string) ( self::DONE[ $hecho ] ?? '' ),
			);

		return $m;
	}







	private static function values( array $application ): array {
		if ( '' !== self::$rejected ) {
			return self::submitted();
		}
		$coord = (array) ( $application['coordinator'] ?? array() );
		$meta  = array() !== $application ? Applications::meta( (int) $application['id'] ) : array();
		return array(
			'position'          => (string) ( $meta[ ApplicationMetaKeys::APPLICANT_POSITION ] ?? '' ),
			'coordinator_name'  => (string) ( $coord['name'] ?? '' ),
			'coordinator_email' => (string) ( $coord['email'] ?? '' ),

			'accept'            => array() !== $application,
			'answers'           => (array) ( $application['answers'] ?? array() ),
		);
	}







	public static function render( $atts = array() ): string {
		unset( $atts );
		Assets::enqueue();
		return ApplyFormView::html( self::model() );
	}
}








namespace Prc\PublicFront\View;

use Prc\PublicFront\Assets;
use Prc\PublicFront\Shell;









final class MyCentreView {




	private const TITLE = 'Solicitudes de participación en procedimientos de su centro educativo';







	public static function html( array $m ): string {
		if ( '' !== (string) $m['aviso'] ) {
			return Shell::render( self::TITLE, '', Shell::notice( 'aviso', (string) $m['aviso'] ) );
		}

		$centro = (array) $m['centre'];
		$filas  = (array) $m['rows'];
		$nombre = '' !== (string) $centro['name'] ? (string) $centro['name'] : 'Centro ' . (string) $centro['code'];
		$ambito = 'Procedimientos para ' . $nombre . ' (' . (string) $centro['code'] . ')';

		if ( array() === $filas ) {
			return Shell::render(
				self::TITLE,
				$ambito,
				'<div class="prc-mi-centro">' . self::count( 0 )
					. '<p class="prc-vacio">Su centro todavía no ha presentado ninguna solicitud. Se presentan desde la ficha de cada procedimiento, mientras su plazo esté abierto.</p></div>'
			);
		}

		ob_start();
		?>
		<div class="prc-mi-centro">
			<p class="prc-ayuda-orden">
				<strong>Pulse en las cabeceras</strong> de las columnas de la tabla <strong>para ordenarla</strong>:<br>
				la primera vez ordenará de menor a mayor (de la A a la Z, o de la más antigua a la más reciente);<br>
				la segunda vez, al revés.
			</p>
			<?php
			echo self::count( count( $filas ) ); 
			echo self::amendment( (array) $m['amend'] ); 
			?>
			<div class="prc-tabla-caja">
				<table class="prc-tabla prc-tabla--densa prc-mi-tabla">
					<caption class="screen-reader-text"><?php echo esc_html( $ambito ); ?></caption>
					<thead>
						<tr>
							<?php foreach ( (array) $m['headers'] as $columna ) : ?>
								<?php echo self::heading( (array) $columna ); ?>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $filas as $fila ) : ?>
							<?php echo self::row( (array) $fila ); ?>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
		return Shell::render( self::TITLE, $ambito, (string) ob_get_clean() );
	}







	private static function count( int $total ): string {
		return '<p class="prc-recuento">Mostrando <strong>' . (int) $total . '</strong> '
			. ( 1 === $total ? 'procedimiento' : 'procedimientos' ) . '.</p>';
	}










	private static function amendment( array $amend ): string {
		$cuantas = (int) $amend['count'];
		if ( $cuantas < 1 ) {
			return '';
		}
		$una   = 1 === $cuantas;
		$texto = '<strong>' . $cuantas . ( $una ? ' solicitud</strong> tiene' : ' solicitudes</strong> tienen' )
			. ' la subsanación abierta';
		if ( '' !== (string) $amend['closes'] ) {
			$texto .= ( $una ? ' hasta el <strong>' : ', la más próxima hasta el <strong>' )
				. esc_html( (string) $amend['closes'] ) . '</strong>';
		}
		$texto .= '.';
		if ( '' !== (string) $amend['id'] ) {
			$texto .= ' <a href="#fila-' . esc_attr( (string) $amend['id'] ) . '">Ir a la solicitud</a>';
		}
		return '<p class="' . esc_attr( Assets::alert_class( 'warning' ) ) . '" role="status">' . $texto . '</p>';
	}










	private static function heading( array $columna ): string {
		$rotulo = esc_html( (string) $columna['label'] );
		if ( '' === (string) $columna['url'] ) {
			return '<th scope="col" class="prc-th">' . $rotulo . '</th>';
		}
		return '<th scope="col" class="prc-th prc-th--ordenable" aria-sort="' . esc_attr( (string) $columna['sort'] ) . '">'
			. '<a class="prc-th-enlace" href="' . esc_url( (string) $columna['url'] ) . '">' . $rotulo . '</a></th>';
	}







	private static function row( array $fila ): string {
		$cargo    = (string) $fila['position'];
		$quien    = (string) $fila['applicant'];
		$quien    = '' !== $quien && '' !== $cargo ? $quien . ' (' . $cargo . ')' : $quien;
		$opciones = array_map( 'esc_html', (array) $fila['options'] );

		ob_start();
		?>
		<tr id="fila-<?php echo esc_attr( (string) $fila['procedure_id'] ); ?>"<?php echo empty( $fila['amend'] ) ? '' : ' class="prc-subsanar"'; ?>>
			<th scope="row" class="prc-celda prc-celda--titulo" data-rotulo="Procedimiento">
				<?php if ( '' !== (string) $fila['sheet'] ) : ?>
					<a href="<?php echo esc_url( (string) $fila['sheet'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( (string) $fila['title'] ); ?></a>
				<?php else : ?>
					<?php echo esc_html( (string) $fila['title'] ); ?>
				<?php endif; ?>
				<span class="<?php echo esc_attr( Assets::state_class( (string) $fila['state'] ) ); ?>"><?php echo esc_html( (string) $fila['state_label'] ); ?></span>
			</th>
			<td class="prc-celda prc-celda--fecha" data-rotulo="Fecha"><?php echo esc_html( (string) $fila['date_label'] ); ?></td>
			<td class="prc-celda" data-rotulo="Solicitante (cargo)"><?php echo self::value( $quien ); ?></td>
			<td class="prc-celda" data-rotulo="Coordinador/a"><?php echo self::value( (string) $fila['coordinator'] ); ?></td>
			<td class="prc-celda" data-rotulo="Opciones"><?php echo self::value( implode( '<br>', $opciones ), false ); ?></td>
			<td class="prc-celda prc-celda--revision" data-rotulo="Revisión">
				<span class="<?php echo esc_attr( Assets::state_class( (string) $fila['review'] ) ); ?>"><?php echo esc_html( (string) $fila['review_label'] ); ?></span>
				<?php if ( '' !== (string) $fila['note'] ) : ?>
					<span class="prc-nota-revision"><?php echo esc_html( (string) $fila['note'] ); ?></span>
				<?php endif; ?>
				<?php if ( '' !== (string) $fila['url'] ) : ?>
					<a class="prc-ver-solicitud" href="<?php echo esc_url( (string) $fila['url'] ); ?>">Ver la solicitud</a>
				<?php endif; ?>
			</td>
		</tr>
		<?php
		return (string) ob_get_clean();
	}








	private static function value( string $valor, bool $escape = true ): string {
		if ( '' === trim( $valor ) ) {
			return '<span class="prc-sindato" title="Sin datos">¿?</span>';
		}
		return $escape ? esc_html( $valor ) : $valor;
	}
}








namespace Prc\PublicFront;

use Prc\Access\ProcedureAccess;
use Prc\Domain\ProcedureQuestions;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PublicFront\View\MyCentreView;













final class MyCentre {

	public const SHORTCODE = 'prc_mine';




	public const VAR_SORT = 'orden';




	public const VAR_DIR = 'dir';





	public const SORT_DEFAULT = 'fecha';






	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'render' ) );
	}










	public static function columns(): array {
		return array(
			'procedimiento' => array(
				'label' => 'Procedimiento',
				'field' => 'title',
			),
			'fecha'         => array(
				'label' => 'Fecha',
				'field' => 'date',
			),
			'solicitante'   => array(
				'label' => 'Solicitante (cargo)',
				'field' => 'applicant',
			),
			'coordinador'   => array(
				'label' => 'Coordinador/a',
				'field' => 'coordinator',
			),
			'opciones'      => array(
				'label' => 'Opciones',
				'field' => 'options_text',
			),
			'revision'      => array(
				'label' => 'Revisión',
				'field' => 'review_label',
			),
		);
	}









	public static function sort(): array {
		$col = Workspace::input( self::VAR_SORT );
		$dir = Workspace::input( self::VAR_DIR );
		if ( ! isset( self::columns()[ $col ] ) ) {
			$col = self::SORT_DEFAULT;
			$dir = 'desc';
		}
		return array(
			'col' => $col,
			'dir' => 'desc' === $dir ? 'desc' : 'asc',
		);
	}






	public static function model(): array {
		$orden = self::sort();
		$m     = array(
			'aviso'   => '',
			'centre'  => array(
				'code'      => '',
				'name'      => '',
				'ownership' => '',
			),
			'rows'    => array(),
			'sort'    => $orden,
			'headers' => self::headers( $orden ),
			'amend'   => array(
				'count'  => 0,
				'closes' => '',
				'id'     => '',
			),
		);

		if ( ! is_user_logged_in() ) {
			$m['aviso'] = 'Debe iniciar sesión con su usuario para ver las solicitudes de su centro.';
			return $m;
		}
		$user_id = get_current_user_id();
		if ( ! user_can( $user_id, ProcedureAccess::CAP_APPLY ) ) {
			$m['aviso'] = 'Su perfil no presenta solicitudes de centro. Si forma parte del equipo directivo de un centro, pídalo a quien administre el aplicativo.';
			return $m;
		}
		$m['centre'] = Applications::centre_of( $user_id );
		if ( '' === $m['centre']['code'] ) {
			$m['aviso'] = 'Su cuenta no tiene centro asignado, así que no puede presentar ni consultar solicitudes. El código de centro lo pone quien administra el aplicativo.';
			return $m;
		}

		$solicitudes = Applications::for_centre( $m['centre']['code'] );


		_prime_post_caches( array_unique( array_map( 'intval', wp_list_pluck( $solicitudes, 'post_parent' ) ) ), false, true );
		foreach ( $solicitudes as $solicitud ) {
			$m['rows'][] = self::row( $solicitud );
		}
		$m['rows']  = self::ordered( $m['rows'], $orden );
		$m['amend'] = self::amendment( $m['rows'] );
		return $m;
	}







	private static function row( \WP_Post $application ): array {
		$procedure_id = (int) $application->post_parent;
		$meta         = Applications::meta( (int) $application->ID );
		$autor        = get_userdata( (int) $application->post_author );
		$coord        = (array) $meta[ ApplicationMetaKeys::COORDINATOR ];
		$revision     = (string) $meta[ ApplicationMetaKeys::REVIEW_STATE ];
		$opciones     = self::options( $procedure_id, (array) $meta[ ApplicationMetaKeys::ANSWERS ] );
		$cierre       = (string) get_post_meta( $procedure_id, ProcedureMetaKeys::AMEND_CLOSES_AT, true );
		$estado       = ProcedureState::of_post( $procedure_id );



		$subsanar = ApplicationMetaKeys::REVIEW_AMEND === $revision
			&& ( '' === $cierre || ProcedureState::today() <= $cierre );

		return array(
			'procedure_id' => (string) $procedure_id,
			'title'        => (string) get_post_field( 'post_title', $procedure_id ),
			'url'          => ApplyForm::url( $procedure_id ),
			'sheet'        => (string) get_permalink( $procedure_id ),
			'date'         => (string) get_the_date( 'Y-m-d H:i', $application ),
			'date_label'   => (string) get_the_date( 'd-m-Y', $application ),
			'applicant'    => false !== $autor ? (string) $autor->display_name : '',
			'position'     => (string) ( ApplicationMetaKeys::positions()[ $meta[ ApplicationMetaKeys::APPLICANT_POSITION ] ] ?? '' ),
			'coordinator'  => (string) ( $coord['name'] ?? '' ),
			'options'      => $opciones,
			'options_text' => implode( ' ', $opciones ),
			'state'        => $estado,
			'state_label'  => ProcedureState::label( $estado ),
			'review'       => $revision,
			'review_label' => (string) ( ApplicationMetaKeys::review_states()[ $revision ] ?? '' ),
			'note'         => (string) $meta[ ApplicationMetaKeys::REVIEW_NOTE ],
			'amend'        => $subsanar,
			'amend_closes' => $cierre,
		);
	}












	private static function options( int $procedure_id, array $answers ): array {
		$out = array();
		foreach ( Applications::questions( $procedure_id ) as $pregunta ) {
			if ( ! ProcedureQuestions::has_choices( (string) $pregunta['type'] ) ) {
				continue;
			}
			$valor = $answers[ (string) $pregunta['key'] ] ?? '';
			foreach ( is_array( $valor ) ? $valor : array( $valor ) as $opcion ) {
				$opcion = trim( (string) $opcion );
				if ( '' !== $opcion ) {
					$out[] = $opcion;
				}
			}
		}
		return $out;
	}











	private static function ordered( array $rows, array $sort ): array {
		$campo = (string) self::columns()[ $sort['col'] ]['field'];
		$signo = 'desc' === $sort['dir'] ? -1 : 1;
		usort(
			$rows,
			static function ( array $a, array $b ) use ( $campo, $signo ): int {
				$x = (string) $a[ $campo ];
				$y = (string) $b[ $campo ];
				if ( ( '' === $x ) !== ( '' === $y ) ) {
					return '' === $x ? 1 : -1;
				}
				return $signo * strnatcasecmp( remove_accents( $x ), remove_accents( $y ) );
			}
		);
		return $rows;
	}










	private static function headers( array $sort ): array {
		$base = Shell::url( 'mine' );
		$out  = array();
		foreach ( self::columns() as $clave => $columna ) {
			$activa        = $clave === $sort['col'];
			$vuelta        = $activa && 'asc' === $sort['dir'] ? 'desc' : 'asc';
			$out[ $clave ] = array(
				'label' => (string) $columna['label'],
				'sort'  => $activa ? ( 'asc' === $sort['dir'] ? 'ascending' : 'descending' ) : 'none',
				'url'   => '' === $base ? '' : add_query_arg(
					array(
						self::VAR_SORT => $clave,
						self::VAR_DIR  => $vuelta,
					),
					$base
				),
			);
		}
		return $out;
	}







	private static function amendment( array $rows ): array {
		$abiertas = array();
		foreach ( $rows as $fila ) {
			if ( ! empty( $fila['amend'] ) ) {
				$abiertas[] = $fila;
			}
		}


		$fechas = array_filter( wp_list_pluck( $abiertas, 'amend_closes' ) );
		sort( $fechas );
		return array(
			'count'  => count( $abiertas ),
			'closes' => self::day( (string) ( $fechas[0] ?? '' ) ),
			'id'     => 1 === count( $abiertas ) ? (string) $abiertas[0]['procedure_id'] : '',
		);
	}







	private static function day( string $ymd ): string {
		return '' === trim( $ymd ) ? '' : (string) mysql2date( 'd-m-Y', $ymd );
	}







	public static function render( $atts = array() ): string {
		unset( $atts );
		Assets::enqueue();
		return MyCentreView::html( self::model() );
	}
}








namespace Prc\Admin;

use Prc\Access\ProcedureAccess;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\PostType\ApplicationPostType;
use Prc\PostType\ProcedurePostType;
use Prc\Taxonomy\ProcedureTaxonomies;









final class ProcedureAdmin {






	public static function register(): void {
		add_action( 'pre_get_posts', array( self::class, 'scope_admin_query' ) );
		add_filter( 'manage_' . ProcedurePostType::POST_TYPE . '_posts_columns', array( self::class, 'columns' ) );
		add_action( 'manage_' . ProcedurePostType::POST_TYPE . '_posts_custom_column', array( self::class, 'column_content' ), 10, 2 );
		add_filter( 'manage_' . ApplicationPostType::POST_TYPE . '_posts_columns', array( self::class, 'application_columns' ) );
		add_action( 'manage_' . ApplicationPostType::POST_TYPE . '_posts_custom_column', array( self::class, 'application_column_content' ), 10, 2 );
	}







	public static function scope_admin_query( $query ): void {
		if ( ! is_admin() || ! ( $query instanceof \WP_Query ) || ! $query->is_main_query() ) {
			return;
		}
		$tipo = (string) $query->get( 'post_type' );
		if ( ! in_array( $tipo, ProcedureAccess::scoped_types(), true ) ) {
			return;
		}
		if ( ProcedureAccess::can_edit_all_areas() ) {
			return;
		}

		$areas = ProcedureAccess::scope_areas();
		if ( array() === $areas ) {

			$query->set( 'post__in', array( 0 ) );
			return;
		}

		if ( ProcedurePostType::POST_TYPE === $tipo ) {
			$tax_query   = (array) $query->get( 'tax_query' );
			$tax_query[] = array(
				'taxonomy'         => ProcedureTaxonomies::AREA,
				'field'            => 'term_id',
				'terms'            => $areas,
				'include_children' => false,
			);
			$query->set( 'tax_query', $tax_query );
			return;
		}


		$procedures = get_posts(
			array(
				'post_type'      => ProcedurePostType::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'tax_query'      => array( 
					array(
						'taxonomy'         => ProcedureTaxonomies::AREA,
						'field'            => 'term_id',
						'terms'            => $areas,
						'include_children' => false,
					),
				),
			)
		);
		$query->set( 'post_parent__in', array() === $procedures ? array( 0 ) : array_map( 'intval', $procedures ) );
	}







	public static function columns( array $columns ): array {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['prc_area']  = 'Ámbito';
				$new['prc_state'] = 'Estado';
			}
		}
		return $new;
	}








	public static function column_content( string $column, int $post_id ): void {
		if ( 'prc_area' === $column ) {
			$terms = get_the_terms( $post_id, ProcedureTaxonomies::AREA );
			echo is_array( $terms ) && array() !== $terms
				? esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) )
				: '—';
			return;
		}
		if ( 'prc_state' === $column ) {
			echo esc_html( ProcedureState::label( ProcedureState::of_post( $post_id ) ) );
		}
	}







	public static function application_columns( array $columns ): array {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['prc_procedure'] = 'Procedimiento';
				$new['prc_centre']    = 'Centro';
				$new['prc_review']    = 'Revisión';
			}
		}
		return $new;
	}








	public static function application_column_content( string $column, int $post_id ): void {
		if ( 'prc_procedure' === $column ) {
			$parent = (int) get_post_field( 'post_parent', $post_id );
			echo $parent > 0 ? esc_html( (string) get_the_title( $parent ) ) : '—';
			return;
		}
		if ( 'prc_centre' === $column ) {
			$code = (string) get_post_meta( $post_id, ApplicationMetaKeys::CENTRE_CODE, true );
			$name = (string) get_post_meta( $post_id, ApplicationMetaKeys::CENTRE_NAME, true );
			echo esc_html( trim( $name . ' (' . $code . ')' ) );
			return;
		}
		if ( 'prc_review' === $column ) {
			$state = (string) get_post_meta( $post_id, ApplicationMetaKeys::REVIEW_STATE, true );
			echo esc_html( (string) ( ApplicationMetaKeys::review_states()[ $state ] ?? '—' ) );
		}
	}
}








namespace Prc\Admin;

use Prc\Access\CentreScope;
use Prc\Access\ProcedureAccess;
use Prc\Centre\CentreCatalogue;
use Prc\Centre\CentreCatalogueSync;
use Prc\PostType\ApplicationPostType;
use Prc\PostType\ProcedurePostType;
use Prc\PublicFront\ExitSignal;
use Prc\Taxonomy\ProcedureTaxonomies;
use RuntimeException;







final class Settings {




	public const PAGE = 'prc-settings';




	public const NONCE_SYNC_CENTRES = 'prc_centres_manual_sync';






	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'menu' ) );
		add_action( 'admin_init', array( self::class, 'handle_actions' ) );
	}






	public static function menu(): void {
		add_submenu_page(
			'edit.php?post_type=' . ProcedurePostType::POST_TYPE,
			'Ajustes y diagnóstico de procedimientos',
			'Ajustes',
			ProcedureAccess::CAP_MANAGE,
			self::PAGE,
			array( self::class, 'render' )
		);
	}






	public static function handle_actions(): void {
		if ( ! is_admin() || ! ProcedureAccess::is_manager() ) {
			return;
		}

		if (
			isset( $_POST['prc_action'] ) &&
			'sync_centres' === $_POST['prc_action'] &&
			check_admin_referer( self::NONCE_SYNC_CENTRES, '_prc_centres_nonce' )
		) {
			$redirect_args = array(
				'post_type' => ProcedurePostType::POST_TYPE,
				'page'      => self::PAGE,
			);

			try {
				$result = CentreCatalogueSync::sync( true );
				$msg    = sprintf(
					'Catálogo actualizado correctamente (%d centros, %d activos). SHA-256: %s',
					$result['records'],
					$result['active'],
					substr( $result['sha256'], 0, 12 ) . '…'
				);

				$redirect_args['updated'] = 'synced';
				$redirect_args['msg']     = rawurlencode( $msg );
			} catch ( RuntimeException $e ) {
				$redirect_args['error'] = rawurlencode( $e->getMessage() );
			}

			self::leave( add_query_arg( $redirect_args, admin_url( 'edit.php' ) ) );
		}
	}








	private static function leave( string $url ): void {
		if ( apply_filters( 'prc_exit_throws', false, $url ) ) {

			throw new ExitSignal( $url );
		}
		wp_safe_redirect( $url );
		exit;
	}






	public static function render(): void {
		if ( ! ProcedureAccess::is_manager() ) {
			wp_die( esc_html( 'No tiene permiso para ver los ajustes del aplicativo de procedimientos.' ), '', array( 'response' => 403 ) );
		}

		$post_types = array(
			ProcedurePostType::POST_TYPE   => 'Procedimientos',
			ApplicationPostType::POST_TYPE => 'Solicitudes',
		);
		$taxonomies = array(
			ProcedureTaxonomies::AREA   => 'Ámbito',
			ProcedureTaxonomies::COURSE => 'Curso escolar',
		);
		?>
		<div class="wrap">
			<h1>Ajustes y diagnóstico de procedimientos</h1>
			<p class="description" style="max-width:46rem">
				Esta pantalla no guarda nada todavía: cuenta lo que el aplicativo tiene montado
				en este sitio. Si algo sale «sin registrar», es que el snippet correspondiente
				no está activo.
			</p>

			<?php if ( isset( $_GET['updated'] ) && 'synced' === $_GET['updated'] && ! empty( $_GET['msg'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( sanitize_text_field( wp_unslash( (string) $_GET['msg'] ) ) ); ?></p></div>
			<?php elseif ( isset( $_GET['error'] ) && ! empty( $_GET['error'] ) ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php echo esc_html( sanitize_text_field( wp_unslash( (string) $_GET['error'] ) ) ); ?></p></div>
			<?php endif; ?>

			<h2>Tipos de contenido</h2>
			<table class="widefat striped" style="max-width:46rem">
				<tbody>
				<?php foreach ( $post_types as $slug => $label ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $label ); ?></strong><br /><code><?php echo esc_html( $slug ); ?></code></td>
						<td><?php echo post_type_exists( $slug ) ? 'Registrado' : 'Sin registrar'; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<h2>Taxonomías</h2>
			<table class="widefat striped" style="max-width:46rem">
				<tbody>
				<?php foreach ( $taxonomies as $slug => $label ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $label ); ?></strong><br /><code><?php echo esc_html( $slug ); ?></code></td>
						<td>
							<?php
							if ( ! taxonomy_exists( $slug ) ) {
								echo 'Sin registrar';
							} else {
								$total = wp_count_terms(
									array(
										'taxonomy'   => $slug,
										'hide_empty' => false,
									)
								);
								echo esc_html( sprintf( 'Registrada, %d términos', is_wp_error( $total ) ? 0 : (int) $total ) );
							}
							?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<h2>Roles del aplicativo y Editor nativo</h2>
			<?php if ( ! function_exists( 'prc_roles_status' ) ) : ?>
				<p>El snippet <code>PRC — Roles y perfiles</code> no está activo, así que no hay roles que revisar.</p>
			<?php else : ?>
				<table class="widefat striped" style="max-width:46rem">
					<tbody>
					<?php foreach ( prc_roles_status() as $slug => $estado ) : ?>
						<tr>
							<td><strong><?php echo esc_html( (string) $estado['label'] ); ?></strong><br /><code><?php echo esc_html( $slug ); ?></code></td>
							<td>
								<?php
								if ( empty( $estado['exists'] ) ) {
									echo 'Falta el rol';
								} elseif ( ! empty( $estado['forbidden'] ) ) {
									echo esc_html( 'Capacidades indebidas: ' . implode( ', ', (array) $estado['forbidden'] ) );
								} elseif ( ! empty( $estado['missing'] ) ) {
									echo esc_html( 'Sin ' . implode( ', ', (array) $estado['missing'] ) );
								} else {
									echo 'Correcto';
								}
								?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<h2>Ámbitos de los perfiles editores</h2>
			<p>Diagnóstico de solo lectura. Los perfiles ambiguos o inválidos no reciben acceso hasta que administración elija un ámbito en su perfil.</p>
			<table class="widefat striped" style="max-width:46rem">
				<thead><tr><th>Usuario</th><th>Estado</th><th>IDs válidos</th><th>IDs inválidos</th><th>Formato anterior</th></tr></thead>
				<tbody>
				<?php foreach ( ProcedureAccess::scope_diagnostics() as $row ) : ?>
					<tr>
						<td><a href="<?php echo esc_url( get_edit_user_link( $row['user_id'] ) ); ?>"><?php echo esc_html( $row['login'] ); ?></a></td>
						<td><?php echo esc_html( $row['state'] ); ?></td>
						<td><?php echo esc_html( implode( ', ', $row['ids'] ) ); ?></td>
						<td><?php echo esc_html( implode( ', ', $row['invalid'] ) ); ?></td>
						<td><?php echo $row['legacy'] ? 'Sí' : 'No'; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<h2>Catálogo de centros educativos</h2>
			<?php
			$status       = CentreCatalogue::status();
			$total_count  = CentreCatalogue::count();
			$active_count = CentreCatalogue::active_count();
			$is_ready     = $total_count > 0;
			$has_source   = '' !== CentreCatalogueSync::manifest_url();
			?>
			<table class="widefat striped" style="max-width:46rem">
				<tbody>
					<tr>
						<th style="width:40%;">Estado</th>
						<td>
							<?php if ( $is_ready ) : ?>
								<span class="dashicons dashicons-yes-alt" style="color:#46b450;" aria-hidden="true"></span> Disponible
							<?php else : ?>
								<span class="dashicons dashicons-warning" style="color:#dc3232;" aria-hidden="true"></span> No disponible (catálogo vacío)
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th>Fuente configurada</th>
						<td><?php echo $has_source ? 'Sí' : 'No'; ?></td>
					</tr>
					<tr>
						<th>Registros totales</th>
						<td><strong><?php echo esc_html( (string) $total_count ); ?></strong></td>
					</tr>
					<tr>
						<th>Registros activos</th>
						<td><strong style="color:#46b450;"><?php echo esc_html( (string) $active_count ); ?></strong></td>
					</tr>
					<tr>
						<th>Registros inactivos</th>
						<td><?php echo esc_html( (string) ( $total_count - $active_count ) ); ?></td>
					</tr>
					<tr>
						<th>Fecha del catálogo</th>
						<td><?php echo esc_html( '' !== $status['catalogue_updated_at'] ? $status['catalogue_updated_at'] : '—' ); ?></td>
					</tr>
					<tr>
						<th>Última comprobación</th>
						<td><?php echo esc_html( '' !== $status['last_checked_at'] ? $status['last_checked_at'] : '—' ); ?></td>
					</tr>
					<tr>
						<th>Última actualización correcta</th>
						<td><?php echo esc_html( '' !== $status['last_success_at'] ? $status['last_success_at'] : '—' ); ?></td>
					</tr>
					<tr>
						<th>SHA-256 actual</th>
						<td>
							<?php if ( '' !== $status['sha256'] ) : ?>
								<code style="font-size:0.85em;"><?php echo esc_html( $status['sha256'] ); ?></code>
							<?php else : ?>
								<em>Ninguno</em>
							<?php endif; ?>
						</td>
					</tr>
					<?php if ( '' !== $status['last_error'] ) : ?>
						<tr>
							<th style="color:#dc3232;">Último error</th>
							<td style="color:#dc3232;"><code><?php echo esc_html( $status['last_error'] ); ?></code></td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>

			<div style="margin-top:1rem;">
				<form method="post" action="">
					<?php wp_nonce_field( self::NONCE_SYNC_CENTRES, '_prc_centres_nonce' ); ?>
					<input type="hidden" name="prc_action" value="sync_centres" />
					<button type="submit" class="button button-secondary">
						Actualizar catálogo ahora
					</button>
				</form>
			</div>
			<p style="margin-top:0.75rem;">
				La clave de usuario con el código de centro es <code><?php echo esc_html( CentreScope::meta_key() ); ?></code>.
			</p>

			<h2>Su acotado</h2>
			<?php
			$areas = ProcedureAccess::user_areas();
			$names = array();
			foreach ( $areas as $term_id ) {
				$term = get_term( $term_id, ProcedureTaxonomies::AREA );
				if ( $term instanceof \WP_Term ) {
					$names[] = $term->name;
				}
			}
			$code = CentreScope::code_for();
			?>
			<p>
				<?php if ( ProcedureAccess::can_edit_all_areas() ) : ?>
					Ve y edita los procedimientos de todos los ámbitos.
				<?php elseif ( array() === $names ) : ?>
					No tiene ningún ámbito asignado en su perfil, así que no ve ni edita ningún procedimiento.
				<?php else : ?>
					<?php echo esc_html( 'Acotado a: ' . implode( ', ', $names ) ); ?>
				<?php endif; ?>
				<?php echo esc_html( '' === $code ? 'Sin código de centro.' : 'Código de centro: ' . $code . '.' ); ?>
			</p>
		</div>
		<?php
	}
}








namespace Prc;

use Prc\Access\ProcedureAccess;
use Prc\Admin\ProcedureAdmin;
use Prc\Admin\Settings;
use Prc\Centre\CentreCatalogueSync;
use Prc\Meta\ApplicationMetaRegistration;
use Prc\Meta\ProcedureMetaRegistration;
use Prc\PostType\ApplicationPostType;
use Prc\PostType\ProcedurePostType;
use Prc\PublicFront\Applications;
use Prc\PublicFront\ApplicationFiles;
use Prc\PublicFront\ApplyForm;
use Prc\PublicFront\Assets;
use Prc\PublicFront\EditLock;
use Prc\PublicFront\Home;
use Prc\PublicFront\MyCentre;
use Prc\PublicFront\ProcedureEditor;
use Prc\PublicFront\ProcedureView;
use Prc\PublicFront\Shell;
use Prc\PublicFront\Workspace;
use Prc\Taxonomy\ProcedureTaxonomies;









final class App {








	public const PAGES_PARENT = 'prc_pages_parent';












	private const FRONT = array(
		Assets::class,
		Shell::class,
		EditLock::class,
		Applications::class,



		ApplicationFiles::class,
		ApplyForm::class,
		MyCentre::class,
		Workspace::class,
		ProcedureEditor::class,
		Home::class,
		ProcedureView::class,
	);






	public static function boot(): void {
		static $booted = false;
		if ( $booted ) {
			return;
		}
		$booted = true;

		add_action( 'init', array( ProcedureTaxonomies::class, 'register' ), 9 );
		add_action( 'init', array( ProcedurePostType::class, 'register' ), 10 );
		add_action( 'init', array( ApplicationPostType::class, 'register' ), 10 );
		add_action( 'init', array( ProcedurePostType::class, 'grant_caps_to_roles' ), 11 );

		ProcedureMetaRegistration::register();
		ApplicationMetaRegistration::register();
		ProcedureAccess::register();



		add_filter( 'prc_page_slug', array( self::class, 'page_slug' ), 10, 2 );



		foreach ( self::FRONT as $modulo ) {
			if ( class_exists( $modulo ) && method_exists( $modulo, 'register' ) ) {
				call_user_func( array( $modulo, 'register' ) );
			}
		}

		ProcedureAdmin::register();
		Settings::register();
		CentreCatalogueSync::register_cron();
	}








	public static function page_slug( string $slug, string $section = '' ): string {
		unset( $section );
		$padre = trim( (string) get_option( self::PAGES_PARENT, '' ), '/' );
		if ( '' === $padre || '' === $slug || 0 === strpos( $slug, $padre . '/' ) ) {
			return $slug;
		}
		return $padre . '/' . $slug;
	}
}


\Prc\PublicFront\Assets::set_inline( array (
  'css/prc-alta.css' => '/* El alta de un procedimiento y los paneles del taller.

   Dos pantallas con la misma piel: la de crear —que en el sitio que se
   sustituye es un formulario de ciento dieciocho campos con ciento diez
   escondidos— y los cinco paneles del taller. De aquel formulario se copia lo
   que la gente reconoce: la banda beige de cada sección, la retícula de doce
   columnas con canal del 2 %, el árbol de casillas del ámbito, los tres
   correos y la paleta de color de cabecera. No se copian ni el campo de
   identificador interno ni el desplegable de estado: el primero es el `ID` del
   post y el segundo, un campo obligatorio con un solo valor válido, que aquí
   es lo que las fechas digan (ADR-0013).

   Los tokens y los componentes comunes están en `prc-app.css`, que carga
   delante. Los que esta hoja necesita y allí todavía no están se piden con su
   valor medido como respaldo (`var(--token, valor)`): el día que el armazón
   los declare, mandan ellos y aquí no hay que tocar nada. */

/* --- la piel de formulario del taller -----------------------------------
   Etiqueta de 15px sobre control de 14px, que es la segunda de las dos pieles
   de formulario del sitio original (la otra, la de la solicitud, la viste
   `prc-formulario.css`). */

.prc-form.prc-form--taller .prc-campo {
  margin: 0 0 20px;
  padding: 0;
  text-align: left;
}

.prc-form.prc-form--taller .prc-campo__etiqueta {
  display: block;
  margin: 0;
  padding: 0 0 3px;
  font: 400 15px/23.8px var(--prc-fuente);
  color: var(--prc-c-etiqueta, #3f4b5b);
}

/* El asterisco: `#b94a48` y no el rojo puro del original, que es el que peor
   contrasta con el texto que lleva al lado. */
.prc-campo__obligatorio {
  font-weight: 700;
  color: var(--prc-c-obligatorio);
  text-decoration: none;
  border: 0;
  cursor: help;
}

/* La ayuda del campo va visible y referenciada con `aria-describedby`: en el
   original vive en un `<span>` que solo se abre con el ratón, y el nodo al que
   apunta `aria-describedby` está en `display:none`. */
.prc-form.prc-form--taller .prc-campo__ayuda {
  margin: 6px 0 0;
  padding: 0;
  font: 400 12px/1.5 var(--prc-fuente);
  color: var(--prc-c-texto);
  max-width: 60ch;
}

/* Dos clases y no una: el armazón todavía trae un bloque antiguo
   (`.prc-form input[type="text"]`, de 44px y radio 8px) que por el selector de
   atributo pesa más que un modificador suelto. Cuando ese bloque se retire,
   aquí sobra la primera clase. */
.prc-form.prc-form--taller .prc-control {
  box-sizing: border-box;
  width: 100%;
  min-height: var(--prc-alto-campo-alta, 39px);
  padding: 10px;
  font: 400 14px/18.2px var(--prc-fuente);
  color: var(--prc-c-medio);
  background: var(--prc-c-fondo);
  border: 1px solid var(--prc-c-campo-borde, #bfc3c8);
  border-radius: var(--prc-radio-campo);
  box-shadow: none;
}

.prc-form.prc-form--taller select.prc-control {
  min-height: 35px;
  padding: 6px 10px;
  border-radius: var(--prc-radio);
}

.prc-form.prc-form--taller textarea.prc-control {
  min-height: 0;
  line-height: 1.5;
}

.prc-form.prc-form--taller .prc-control:focus {
  border-color: var(--prc-c-foco-campo, #66afe9);
  box-shadow: var(--prc-sombra-foco);
  outline: none;
}

/* Un campo que el estado cierra se distingue solo por el color: mismo alto,
   mismo borde, mismo radio, fondo blanco. Ni candado ni fondo gris. */
.prc-form.prc-form--taller .prc-control[readonly],
.prc-form.prc-form--taller .prc-control:disabled {
  color: var(--prc-c-bloq-texto, #a1a1a1);
  background: var(--prc-c-fondo);
  border-color: var(--prc-c-bloq-borde, #e5e5e5);
}

.prc-form.prc-form--taller .prc-control::placeholder {
  color: var(--prc-c-bloq-texto, #a1a1a1);
  font-style: normal;
  opacity: 1;
}

/* Las casillas son las de la lista de opciones reutilizable de
   `prc-formulario.css` (`.prc-opcion`): aquí solo cambia el rótulo, que en el
   taller es de 13px sobre #444 y no de 16px sobre el gris del cuerpo. */
.prc-form.prc-form--taller .prc-opcion label {
  font: 400 13px/16.9px var(--prc-fuente);
  color: var(--prc-c-fuerte);
}

/* --- la banda de sección ------------------------------------------------
   Beige macizo, 18px en negrita y en mayúsculas por CSS (escritas, un lector
   de pantalla las deletrea), con filete de 2px solo arriba. */

.prc-seccion-campos {
  min-width: 0;
  margin: 0;
  padding: 0;
  border: 0;
}

.prc-seccion-campos > .prc-seccion__titulo {
  display: block;
  float: none;
  width: 100%;
  box-sizing: border-box;
  height: auto;
  margin: 30px 0;
  padding: 10px;
  font: 700 18px/18px var(--prc-fuente);
  text-transform: uppercase;
  color: var(--prc-c-tenue);
  background: var(--prc-c-fondo-banda);
  border: 0;
  border-top: 2px solid var(--prc-c-linea-seccion);
  border-radius: 0;
}

/* Por qué este bloque está apagado, junto al bloque y no solo arriba. */
.prc-seccion__bloqueo {
  margin: -18px 0 18px;
  padding: 0;
  font: 400 13px/20px var(--prc-fuente);
  color: var(--prc-c-tenue);
}

.prc-intro {
  margin: 0 0 18px;
  padding: 0;
  max-width: 72ch;
  font: var(--prc-p-base) var(--prc-t-base)/var(--prc-i-base) var(--prc-fuente);
  color: var(--prc-c-texto);
}

/* La retícula de doce columnas con canal del 2 % y el punto de ruptura de
   600px están en la piel reutilizable de `prc-formulario.css`
   (`.prc-seccion__cuerpo`, `.prc-campo--cuarto|tercio|medio|completo`): aquí
   solo va lo que el alta añade encima.

   Las dos secciones del medio comparten fila, y la banda de cada una ocupa
   solo su mitad, como en el original. */
.prc-form__mitades {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0 var(--prc-form-hueco);
}

@media (min-width: 992px) {
  .prc-form__mitades { grid-template-columns: 1fr 1fr; }
}

/* --- el árbol de ámbitos ------------------------------------------------
   Sangría de 20px por nivel, guía vertical de 1px y el triángulo de plegar a
   la izquierda de la casilla, como el original. Lo que cambia es el mecanismo:
   allí es JavaScript sobre `display:none`; aquí, una casilla sin `name` —no se
   envía— y una regla de `:has()`. Sin JavaScript se pliega igual, y donde
   `:has()` no llegue el árbol se queda abierto, que es el fallo bueno. */

.prc-arbol {
  list-style: none;
  margin: 0;
  padding: 0;
}

.prc-arbol--rama {
  margin-left: 20px;
  padding-left: 0;
  border-left: 1px solid var(--prc-c-linea-tabla);
}

.prc-arbol__nodo { margin-bottom: 5px; }

.prc-arbol__fila {
  display: flex;
  align-items: center;
  gap: 5px;
}

/* El interruptor de plegado: invisible pero enfocable y con nombre. */
.prc-arbol__pliegue {
  position: absolute;
  width: 1px;
  height: 1px;
  margin: 0;
  padding: 0;
  opacity: 0;
  pointer-events: none;
}

.prc-arbol__tri,
.prc-arbol__hueco {
  flex: 0 0 15px;
  width: 15px;
  text-align: center;
  font-size: 12px;
  line-height: 1;
  color: var(--prc-c-texto);
}

.prc-arbol__tri { cursor: pointer; }
.prc-arbol__tri::before { content: "\\25B6"; }
.prc-arbol__pliegue:checked + .prc-arbol__tri::before { content: "\\25BC"; }

.prc-arbol__pliegue:focus-visible + .prc-arbol__tri {
  outline: 2px solid var(--prc-c-azul);
  outline-offset: 2px;
}

.prc-arbol__nodo:has(> .prc-arbol__fila > .prc-arbol__pliegue:not(:checked)) > .prc-arbol--rama {
  display: none;
}

/* --- la paleta de color de cabecera -------------------------------------
   Diez muestras de lista cerrada en vez de las cuarenta y nueve del original
   —seis de ellas fotografías—, y la elegida se ve elegida: allí el marcado
   servido no distingue ninguna. */

.prc-paleta {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(34px, 34px));
  gap: 8px;
  max-width: 440px;
  margin: 0 0 4px;
}

.prc-muestra {
  position: relative;
  display: block;
  width: 34px;
  height: 34px;
  margin: 0;
  border: 1px solid var(--prc-c-linea-acceso);
  border-radius: var(--prc-radio);
  background: var(--prc-muestra, var(--prc-c-fondo-bloque));
  cursor: pointer;
}

/* El radio se estira sobre la muestra entera: el objetivo de clic es el
   cuadrado de color, no un punto de 16px al lado. Transparente, no escondido:
   sigue recibiendo el foco y lo dibuja el `outline` de la muestra. */
.prc-paleta .prc-muestra input {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  margin: 0;
  opacity: 0;
  cursor: pointer;
}

.prc-muestra:has(input:checked) {
  outline: 3px solid var(--prc-c-titulo);
  outline-offset: 2px;
}

.prc-muestra:has(input:focus-visible) {
  outline: 3px solid var(--prc-c-azul);
  outline-offset: 2px;
}

/* El nombre del color, para quien no lo ve. */
.prc-muestra__nombre {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip-path: inset(50%);
  white-space: nowrap;
}

/* --- el botón de enviar -------------------------------------------------
   42,5px de alto con 30px de aire arriba y abajo. Lo que no se copia es el
   `outline: none` del foco: allí el botón de crear no se ve al enfocarlo. */

.prc-acciones--pie { margin: 0; padding: 0; }

.prc-btn.prc-envio {
  display: inline-block;
  min-height: var(--prc-alto-envio, 42.5px);
  margin: 30px 0;
  padding: 10px 20px;
  font: 400 15px/normal var(--prc-fuente);
  text-align: center;
  color: #fff;
  background: var(--prc-c-azul-alta, #006ece);
  border: 1px solid var(--prc-c-azul-alta-bd, #579af6);
  border-radius: var(--prc-radio);
  box-shadow: var(--prc-sombra-boton);
}

.prc-btn.prc-envio:hover,
.prc-btn.prc-envio:active {
  color: var(--prc-c-fuerte);
  background: #efefef;
  border-color: var(--prc-c-borde-campo);
}

.prc-btn.prc-envio:disabled {
  color: var(--prc-c-bloq-texto, #a1a1a1);
  background: var(--prc-c-fondo-accion);
  border-color: var(--prc-c-bloq-borde, #e5e5e5);
  cursor: not-allowed;
}

/* --- los paneles del taller ---------------------------------------------- */

.prc-pregunta > .prc-seccion__titulo { margin-top: 20px; }

.prc-banda-tabla {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
  margin: 0 0 10px;
}

/* La tabla densa y su recuento vienen de `prc-app.css` y de `prc-taller.css`.
   Lo único que cambia aquí es el relleno: con 13px sobre 15,6px y filas de dos
   líneas, los 3px medidos pegan los renglones de filas contiguas. */
.prc-panel-solicitudes .prc-th,
.prc-panel-solicitudes .prc-celda { padding: 6px 8px; vertical-align: top; }

.prc-panel-solicitudes .prc-th { font-weight: 700; color: var(--prc-c-medio); vertical-align: bottom; }

.prc-revision__abrir {
  display: inline-block;
  padding: 2px 6px;
  font-size: var(--prc-t-tabla);
  font-weight: 700;
  color: var(--prc-c-tabla);
  background: var(--prc-c-fondo-accion);
  border-radius: var(--prc-radio);
  cursor: pointer;
}

.prc-revision[open] .prc-revision__abrir { margin-bottom: 8px; }

.prc-revision__form { min-width: 16rem; }
.prc-revision__form .prc-btn.prc-envio { margin: 10px 0 0; }

.prc-respuestas {
  margin: 0 0 8px;
  font-size: var(--prc-t-tabla);
  line-height: var(--prc-i-tabla);
}

.prc-respuestas dt {
  margin-top: 6px;
  font-weight: 700;
  color: var(--prc-c-medio);
}

.prc-respuestas dd { margin: 0; }
',
  'css/prc-app.css' => '/* Aplicativo de procedimientos: **los tokens, el armazón y los componentes
   comunes**. Cabecera, menú, lienzo, títulos, avisos, botones, pastillas de
   estado, tabla base y estado vacío: lo que aparece en más de una pantalla.
   Lo que solo aparece en una va en la hoja de esa pantalla (`prc-portada.css`,
   `prc-ficha.css`, `prc-taller.css`, `prc-mi-centro.css`, `prc-formulario.css`),
   y todas se cargan juntas, esta primero.

   Se monta encima de Bootstrap 5, que lo carga un snippet aparte
   (`snippets/bootstrap5.php`). El aspecto lo pone esta hoja con Bootstrap y
   sin él: el botón, el aviso y la pastilla del original son los de su
   Bootstrap, que era otro, así que aquí se escriben enteros y `prc-sin-bootstrap`
   solo queda para lo que la librería aporta de más. */

:root {
  /* Los tokens del sistema visual, medidos sobre el sitio que se sustituye.
     Los nombres cortos de siempre —`--prc-pri`, `--prc-texto`, `--prc-r`—
     siguen valiendo y están abajo como alias: lo que cambia es su valor.
     Renombrarlos en toda la hoja y en las siete vistas no compraba nada. */

  /* --- tipografía ------------------------------------------------------- */
  --prc-fuente: "Open Sans", Arial, sans-serif;
  --prc-fuente-hero: "Open Sans", Helvetica, Arial, sans-serif;
  --prc-fuente-cifras: "Days One", "Open Sans", Arial, sans-serif;
  --prc-fuente-iconos: "Material Symbols Outlined";

  --prc-t-hero: 50px;
  --prc-i-hero: 50px;
  --prc-p-hero: 800;
  --prc-t-h1: 30px;
  --prc-i-h1: 30px;
  --prc-p-h1: 500;
  --prc-t-h2: 26px;
  --prc-i-h2: 26px;
  --prc-p-h2: 500;
  --prc-t-h3: 22px;
  --prc-i-h3: 22px;
  --prc-p-h3: 500;
  --prc-t-h5: 16px;
  --prc-i-h5: 23.8px;
  --prc-p-h5: 500;
  --prc-t-base: 14px;
  --prc-i-base: 23.8px;
  --prc-p-base: 500;
  --prc-t-tarjeta: 18px;
  --prc-i-tarjeta: 18px;
  --prc-t-tabla: 13px;
  --prc-i-tabla: 15.6px;
  --prc-t-cifra: 30px;
  --prc-i-cifra: 30px;
  --prc-t-menudo: 12px;
  --prc-i-menudo: 17px;
  --prc-t-micro: 12.6px;
  --prc-t-meta: 9px;
  --prc-p-fuerte: 700;

  /* --- color de texto --------------------------------------------------- */
  --prc-c-texto: #666;          /* cuerpo, leyenda, pestañas, menú */
  --prc-c-titulo: #333;         /* títulos de pantalla y pestaña activa */
  --prc-c-fuerte: #444;         /* título del procedimiento y cifras */
  --prc-c-hero: #474747;        /* el título grande de portada y taller */
  --prc-c-medio: #555;          /* cabeceras de tabla y valores escritos */
  --prc-c-tabla: #212529;       /* celdas */
  --prc-c-tenue: #888;          /* texto auxiliar */
  --prc-c-sindato: #ccc;        /* la celda sin dato */

  /* --- los azules -------------------------------------------------------
     Son cuatro azules distintos y los cuatro se copian con nombre propio: no
     hay «el azul del sitio», y unificarlos se notaría. */
  --prc-c-enlace: #2ea3f2;      /* todo enlace */
  --prc-c-azul: #007bff;        /* botón, filete, títulos de sección */
  --prc-c-azul-hover: #0069d9;
  --prc-c-azul-borde: #0062cc;
  --prc-c-azul-tabla: #007acc;  /* iconos y enlaces dentro de la tabla */
  --prc-c-azul-rotulo: #0c71c3; /* el rótulo «abierta» de la portada */

  /* --- los cuatro tonos de aviso ----------------------------------------
     Los del Bootstrap del sitio original, que NO son los de Bootstrap 5: la
     5 cambió el informativo y el primario. Sin pisarlos no hay calco. */
  --prc-info-fondo: #d1ecf1;
  --prc-info-borde: #bee5eb;
  --prc-info-texto: #0c5460;
  --prc-ok-fondo: #d4edda;
  --prc-ok-borde: #c3e6cb;
  --prc-ok-texto: #155724;
  --prc-avi-fondo: #fff3cd;
  --prc-avi-borde: #ffeeba;
  --prc-avi-texto: #856404;
  --prc-mal-fondo: #f8d7da;
  --prc-mal-borde: #f5c6cb;
  --prc-mal-texto: #721c24;
  --prc-gris-fondo: #e2e3e5;
  --prc-gris-borde: #d6d8db;
  --prc-gris-texto: #383d41;

  /* --- fondos, líneas y bordes ------------------------------------------ */
  --prc-c-fondo: #fff;
  --prc-c-fondo-intro: #f6f6f6;   /* la banda de introducción de la portada */
  --prc-c-fondo-bloque: #dbdbdb;  /* los bloques por estado de la portada */
  --prc-c-fondo-barra: #f4f4f4;   /* barra de pestañas, buscador, hover */
  --prc-c-fondo-leyenda: #f7f7f7;
  --prc-c-fondo-banda: #f5f5dc;   /* la cabecera de sección del formulario */
  --prc-c-fondo-accion: #f8f9fa;  /* botón de icono y botón secundario */
  --prc-c-cebra: rgba(0, 0, 0, .05);
  --prc-c-linea: #d9d9d9;
  --prc-c-linea-tabla: #eee;      /* borde exterior de la tabla */
  --prc-c-linea-celda: #dee2e6;
  --prc-c-linea-th: #dad9c7;
  --prc-c-linea-seccion: #e8e8e8;
  --prc-c-linea-acceso: rgba(0, 0, 0, .125);
  --prc-c-borde-campo: #ccc;
  --prc-c-foco: #0cc;
  --prc-c-obligatorio: #b94a48;

  /* --- radios ------------------------------------------------------------
     No hay un radio del sistema: hay cuatro, uno por familia de componente. */
  --prc-radio: 4px;          /* botón, aviso, pastilla */
  --prc-radio-caja: 0;       /* caja de pestañas, tabla, controles */
  --prc-radio-campo: 5px;    /* buscador y campo de texto */
  --prc-radio-casilla: 2px;

  /* --- sombras -----------------------------------------------------------
     Medido: las tarjetas y las tablas van planas. El efecto de tarjeta lo da
     el blanco contra el gris del bloque, no una sombra. */
  --prc-sombra: none;
  --prc-sombra-boton: 0 1px 1px 0 #eee;
  --prc-sombra-menu: 0 2px 8px rgba(0, 0, 0, .12);
  --prc-sombra-foco: 0 0 5px 0 rgba(102, 175, 233, .6);

  /* --- retícula ----------------------------------------------------------
     Una sola fila, del 80 % del ancho con un máximo de 1200px: a 1470px de
     ventana da los 1176px medidos. Todas las retículas interiores se expresan
     en porcentaje de esa fila, con hueco del 5,5 %. */
  --prc-fila-ancho: 80%;
  --prc-fila-max: 1200px;
  --prc-fila-relleno: 13px 0 7px;
  --prc-seccion-relleno: 0 0 6px;
  --prc-hueco: 5.5%;
  --prc-hueco-px: 64.68px;
  --prc-media: 555.66px;
  --prc-cuarto: 262.5px;
  --prc-proc-ancho: 20.875%;
  --prc-ficha-izq: 57.8%;
  --prc-ficha-der: 36.7%;
  --prc-form-hueco: 2%;
  --prc-ruptura-tema: 980px;
  --prc-ruptura-form: 600px;

  /* --- alturas y medidas de pieza ---------------------------------------- */
  --prc-alto-campo: 46px;
  --prc-alto-boton: 38px;
  --prc-alto-pestana: 32px;
  --prc-ancho-pestana: 129px;
  --prc-banda-tarjeta: 50px;
  --prc-cuenta-bloque: 58px;
  --prc-cuenta-sep: 16px;
  --prc-cuenta-ancho: 280px;
  --prc-icono: 24px;

  /* --- el color de cada estado ------------------------------------------
     Los de los iconos de la leyenda, tal cual están medidos. Como color de
     texto de una pastilla no valen —#aaa, #ffcc00 y #ff0000 no llegan al
     4,5:1—, así que la pastilla usa los tonos de aviso de arriba, que son de
     la misma familia y sí están medidos contra su fondo. */
  --prc-e-abierta: #008000;
  --prc-e-cerrada: #00f;
  --prc-e-reclamacion: #00f;
  --prc-e-pendiente: #00f;
  --prc-e-borrador: #666;
  --prc-e-historica: #aaa;
  --prc-e-incoh-abierta: #f00;   /* abierta con el plazo vencido */
  --prc-e-incoh-cerrada: #fc0;   /* cerrada con el plazo vivo */
  --prc-e-etiqueta: #1a1a8c;     /* la etiqueta bajo la tarjeta de la portada */

  /* --- el recuadro amarillo de «Solo administración» ---------------------
     Los contrastes están medidos, no supuestos (fórmula de luminancia
     relativa de la WCAG 2.1):

       texto  #4a3a05 sobre fondo #fdf6dd → 10,22:1   (pide 4,5:1) ✔
       marca  #ffffff sobre fondo #6b5200 →  7,42:1   (pide 4,5:1) ✔
       borde  #a67c00 sobre la caja       →  3,52:1   (pide 3:1)   ✔
       borde  #a67c00 sobre la página     →  3,59:1   (pide 3:1)   ✔

     El amarillo no es el único indicador: la etiqueta «Solo administración»
     va escrita dentro del recuadro. Es una convención del aplicativo nuevo,
     no tiene equivalente en el original y no se revalora. */
  --prc-adm-fondo: #fdf6dd;
  --prc-adm-borde: #a67c00;
  --prc-adm-texto: #4a3a05;
  --prc-adm-marca: #6b5200;

  /* --- los nombres de siempre, con el valor nuevo ------------------------ */
  --prc-pri: var(--prc-c-azul);
  --prc-pri-cont: var(--prc-info-fondo);
  --prc-sup: var(--prc-c-fondo);
  --prc-sup-2: var(--prc-c-fondo-barra);
  --prc-fondo: var(--prc-c-fondo);
  --prc-texto: var(--prc-c-texto);
  --prc-texto-2: var(--prc-c-tenue);
  --prc-pie-fondo: #05395c;
  --prc-linea: var(--prc-c-linea);
  --prc-ok: var(--prc-ok-texto);
  --prc-ok-cont: var(--prc-ok-fondo);
  --prc-esp: var(--prc-avi-texto);
  --prc-esp-cont: var(--prc-avi-fondo);
  --prc-mal: var(--prc-mal-texto);
  --prc-mal-cont: var(--prc-mal-fondo);
  --prc-inf: var(--prc-info-texto);
  --prc-inf-cont: var(--prc-info-fondo);
  --prc-e1: var(--prc-sombra);
  --prc-e2: var(--prc-sombra-menu);
  --prc-r: var(--prc-radio);
  --prc-ancho: var(--prc-fila-max);
}

/* --- base ----------------------------------------------------------------
   Las reglas del original, tal cual: cuerpo gris medio sobre blanco, enlaces
   que no se subrayan nunca y 14px de aire bajo cada párrafo. Van dentro de
   `:where()` para pesar lo mismo que un selector de elemento: así cualquier
   clase del sistema les gana sin tener que subir la especificidad. */

:where(.prc-app) a { color: var(--prc-c-enlace); text-decoration: none; }
:where(.prc-app) a:hover { text-decoration: none; }
:where(.prc-app) p { margin: 0; padding-bottom: 1em; }
:where(.prc-app) p:last-of-type { padding-bottom: 0; }
:where(.prc-app) strong { font-weight: var(--prc-p-fuerte); }

/* El icono es un glifo con ligadura: sin la familia cargada se lee el nombre
   del icono, así que todo icono que sea la única información lleva su
   `aria-label` al lado. */
.prc-app .material-symbols-outlined {
  font-family: var(--prc-fuente-iconos);
  font-size: var(--prc-icono);
  line-height: var(--prc-icono);
  display: inline-block;
  width: var(--prc-icono);
  font-weight: 400;
  font-variation-settings: "opsz" 24, "wght" 400, "FILL" 0, "GRAD" 0;
}

/* La fila: la única retícula de la que cuelga todo. */
.prc-fila {
  width: var(--prc-fila-ancho);
  max-width: var(--prc-fila-max);
  margin-inline: auto;
  padding: var(--prc-fila-relleno);
}
.prc-seccion { padding: var(--prc-seccion-relleno); }

/* Por debajo del ancho del tema, la fila deja de medirse en porcentaje: un
   80 % de 400px son márgenes de 40px que no le sobran a nadie. */
@media (max-width: 980px) {
  .prc-fila { width: auto; margin-inline: 16px; }
}

/* --- página plana -------------------------------------------------------- */

/* La cabecera y el pie los pintamos nosotros, así que los del tema sobran, y
   también el título de la entrada: lo repite nuestro `h1`. Solo en nuestras
   páginas (`body.prc-app`, que pone Shell) y por selector, no por
   `!important`: si un tema no trae estos elementos, no pasa nada.

   Algunos temas los llaman `#main-header` y `#main-footer`, y con su maquetador
   son plantillas propias (`.et-l--header`, `.et-l--footer`). Los temas de
   bloques los sacan en `wp-block-template-part`, que NO cuelga de `body`, así
   que aquí no vale el combinador `>`. */
body.prc-app #main-header,
body.prc-app #top-header,
body.prc-app .et-l--header,
body.prc-app .et-l--footer,
body.prc-app #main-footer,
body.prc-app .site-header,
body.prc-app .site-footer,
body.prc-app header.wp-block-template-part,
body.prc-app footer.wp-block-template-part,
body.prc-app .et_pb_title_container,
body.prc-app .entry-title,
body.prc-app .page-title,
body.prc-app .wp-block-post-title {
  display: none;
}

body.prc-app {
  font: var(--prc-p-base) var(--prc-t-base)/var(--prc-i-base) var(--prc-fuente);
  color: var(--prc-c-texto);
  background: var(--prc-c-fondo);
  overflow-x: clip;
}
body.prc-app #page-container { padding-top: 0; }

/* Hay temas que reservan la columna de la barra lateral aunque no haya ninguna
   (`et_right_sidebar`): eso descentra el contenido, y con él la cabecera y las
   pestañas de ancho completo, que se calculan contra su contenedor. */
body.prc-app #main-content .container,
body.prc-app #main-content #content-area,
body.prc-app #main-content #left-area {
  width: 100%;
  max-width: none;
  padding: 0;
}
body.prc-app #main-content #sidebar { display: none; }
/* Y la raya vertical que la separaba, que algunos temas dibujan con un
   pseudoelemento absoluto y sobrevive a esconder la columna. */
body.prc-app #main-content .container::before { display: none; }

/* El tema mete el contenido en una columna estrecha con su propio relleno. La
   cabecera, las pestañas y el pie son de ancho completo, así que rompen esa
   columna y vuelven a centrarse por dentro (`--prc-ancho`).

   `left: 50%` + `translateX(-50%)` y no márgenes negativos: los márgenes se
   calculan contra el contenedor y fallan si el tema no lo tiene centrado. */
body.prc-app .prc-top,
body.prc-app .prc-tabs,
body.prc-app .prc-hoja,
body.prc-app .prc-pie {
  position: relative;
  left: 50%;
  transform: translateX(-50%);
  width: 100vw;
  max-width: 100vw;
}

/* El pie, abajo del todo: `sticky` con `top: 100vh` lo deja pegado al borde
   inferior mientras sobre sitio, y baja con la página cuando el contenido es
   largo. Con `sticky`, `left` ya no desplaza —marca el umbral de pegado—, así
   que el ancho completo lo dan los márgenes negativos de siempre. */
body.prc-app .prc-pie {
  position: sticky;
  top: 100vh;
  left: auto;
  transform: none;
  width: auto;
  margin-left: calc(50% - 50vw);
  margin-right: calc(50% - 50vw);
}

/* Sin la cabecera del tema, su hueco superior sobra. */
body.prc-app .wp-site-blocks,
body.prc-app .wp-site-blocks > main,
body.prc-app main.wp-block-group,
body.prc-app .site-main,
body.prc-app #content,
body.prc-app #main-content,
body.prc-app .wp-site-blocks > *,
body.prc-app .entry-content,
body.prc-app .wp-block-post-content {
  padding-top: 0;
  padding-bottom: 0;
  margin-top: 0;
  margin-bottom: 0;
}

body.prc-app .et_pb_row,
body.prc-app .et_pb_section,
body.prc-app .site-content,
body.prc-app #et-main-area {
  max-width: none;
  padding: 0;
  margin: 0;
}

/* El único `!important` del armazón, y con motivo: los temas de bloques le
   escriben al `<main>` un `style="margin-top: …"` en la propia plantilla, y
   contra un estilo en línea no gana ningún selector. */
body.prc-app main { margin-top: 0 !important; margin-bottom: 0 !important; }
body.prc-app main > .wp-block-group { padding-top: 0 !important; padding-bottom: 0 !important; }

/* Una tabla más ancha que la pantalla —aunque vaya en su caja con scroll—
   hacía crecer el viewport en el móvil. Va en <html>: en <body> no basta. */
html:has(> body.prc-app) { overflow-x: clip; }

/* --- cabecera ------------------------------------------------------------ */

/* El `transform` de ancho completo crea una capa en la cabecera, en el menú y
   en la hoja, así que entre ellas manda el orden del documento y no el
   `z-index` de lo que llevan dentro: sin estas tres líneas, la hoja —que va
   después— se pinta encima de los desplegables del menú, y el título de la
   pantalla tapa «Gestión de procedimientos». Se ordenan a mano: la cabecera
   por encima del menú, el menú por encima de la hoja. */
.prc-top { z-index: 3; background: var(--prc-c-fondo); border-bottom: 1px solid #e5e5e5; }
.prc-tabs { z-index: 2; }
.prc-hoja { z-index: 1; }

/* La cabecera, el menú y el pie son de ancho completo y se vuelven a centrar
   por dentro sobre la misma fila que el contenido: 80 % con máximo, no un
   `max-width` con relleno lateral, que dejaba el contenido 48px más estrecho
   que la fila medida. */
.prc-top-fila,
.prc-tabs-fila,
.prc-pie div {
  width: var(--prc-fila-ancho);
  max-width: var(--prc-fila-max);
  margin-inline: auto;
}

.prc-top-fila {
  padding: 16px 0;
  display: flex;
  align-items: center;
  gap: 14px;
  flex-wrap: wrap;
}

/* El escudo de quien despliega. **Aquí no hay ninguno** (ADR-0009): el hueco
   solo se pinta cuando el filtro `prc_chrome` trae un `owner`, y la imagen la
   pone quien despliega desde su propio CSS con `--prc-logo: url(…)`. */
.prc-logo {
  display: block;
  width: 104px;
  height: 60px;
  flex: 0 0 auto;
  background: var(--prc-logo, none) center / contain no-repeat;
}

.prc-marca { line-height: 1.3; }
.prc-marca small {
  display: block;
  font-size: 11.5px;
  color: var(--prc-texto-2);
  max-width: 30ch;
  border-left: 1px solid var(--prc-linea);
  padding-left: 14px;
}

.prc-marca-app {
  margin-left: 12px;
  padding-left: 14px;
  border-left: 1px solid var(--prc-linea);
  font-size: 18px;
  font-weight: 700;
  letter-spacing: -.02em;
  color: var(--prc-pri);
  text-decoration: none;
}

/* «Acceder», para quien mira la portada sin sesión. */
.prc-entrar { margin-left: auto; font-size: 14px; font-weight: 600; color: var(--prc-pri); }

/* --- quién eres y con qué ámbito o centro -------------------------------- */

/* Tres líneas —nombre, rol y ámbito o centro— y no una píldora: en un
   aplicativo acotado por ámbito y por centro, saber con cuál se está mirando
   es media respuesta a «¿por qué no veo este procedimiento?». El menú es un
   `details` nativo: sin guion, y se cierra solo al navegar. */
.prc-yo { margin-left: auto; position: relative; }
.prc-yo > summary {
  list-style: none;
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  border-radius: 12px;
  padding: 4px 6px;
}
.prc-yo > summary::-webkit-details-marker { display: none; }
.prc-yo > summary:hover, .prc-yo[open] > summary { background: var(--prc-sup-2); }
.prc-yo-ava {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  font-size: 13px;
  font-weight: 700;
  background: var(--prc-sup-2);
  color: var(--prc-texto);
}
.prc-yo-txt { display: flex; flex-direction: column; line-height: 1.25; text-align: left; }
.prc-yo-n { display: flex; align-items: center; gap: 6px; font-size: 14px; font-weight: 600; color: var(--prc-texto); }
.prc-yo-r { font-size: 12.5px; color: var(--prc-texto-2); }
.prc-yo-a { font-weight: 600; color: var(--prc-pri); }
.prc-yo-a::before { content: ""; display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: currentColor; margin-right: 6px; vertical-align: 1px; }
.prc-yo-menu {
  position: absolute;
  right: 0;
  top: calc(100% + 6px);
  min-width: 12rem;
  padding: 6px;
  background: var(--prc-sup);
  border: 1px solid var(--prc-linea);
  border-radius: 12px;
  box-shadow: var(--prc-e2);
  z-index: 30;
  display: grid;
}
.prc-yo-menu a { padding: 10px 12px; border-radius: 8px; color: var(--prc-texto); text-decoration: none; font-size: 14px; }
.prc-yo-menu a:hover { background: var(--prc-sup-2); color: var(--prc-pri); }

/* --- el menú -------------------------------------------------------------

   Cuatro entradas en mayúsculas grises —Inicio, Sus solicitudes, Administrar
   y Ayuda— y las que tienen hijos los despliegan con un `details` nativo: sin
   JavaScript (ADR-0022), con teclado y con un solo nivel, que es justo lo que
   los tres niveles anidados del menú original no tenían. */

.prc-tabs { background: var(--prc-c-fondo); border-bottom: 1px solid var(--prc-c-linea); }

.prc-tabs-fila {
  display: flex;
  flex-wrap: wrap;
  align-items: stretch;
  padding: 0;
}

.prc-tab {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 14px 0;
  margin-right: 28px;
  font: 600 var(--prc-t-base)/1 var(--prc-fuente);
  color: var(--prc-c-texto);
  text-transform: uppercase;
  letter-spacing: .04em;
  white-space: nowrap;
  text-decoration: none;
  cursor: pointer;
}

.prc-tab:hover { color: var(--prc-c-enlace); text-decoration: none; }
.prc-tab-on { color: var(--prc-c-titulo); box-shadow: inset 0 -3px 0 var(--prc-c-enlace); }

/* La entrada que despliega: el triángulo lo pinta el propio `summary`, que
   por defecto trae un marcador que no se parece al del original. */
.prc-tab-caja { position: relative; }
.prc-tab-caja > summary { list-style: none; }
.prc-tab-caja > summary::-webkit-details-marker { display: none; }
.prc-tab-caja > summary::after {
  content: "";
  width: 0;
  height: 0;
  border: 4px solid transparent;
  border-top-color: currentColor;
  margin-top: 4px;
}
.prc-tab-caja[open] > summary::after { margin: 0 0 4px; border-top-color: transparent; border-bottom-color: currentColor; }

.prc-desplegable {
  position: absolute;
  left: 0;
  top: 100%;
  z-index: 30;
  display: grid;
  min-width: 260px;
  padding: 8px 0;
  background: var(--prc-c-fondo);
  border: 1px solid var(--prc-c-linea);
  border-radius: var(--prc-radio-caja);
  box-shadow: var(--prc-sombra-menu);
}
.prc-desplegable a {
  padding: 8px 20px;
  font: 500 var(--prc-t-base)/20px var(--prc-fuente);
  color: var(--prc-c-texto);
  text-decoration: none;
}
.prc-desplegable a:hover,
.prc-desplegable a:focus { background: var(--prc-c-fondo-barra); color: var(--prc-c-titulo); }
.prc-desplegable a[aria-current="page"] { background: var(--prc-c-fondo-barra); color: var(--prc-c-titulo); font-weight: 700; }

/* El rótulo de grupo del desplegable: no es un enlace y no se puede pulsar. */
.prc-desplegable-grupo {
  margin: 0;
  padding: 10px 20px 4px;
  font: 700 var(--prc-t-menudo)/16px var(--prc-fuente);
  text-transform: uppercase;
  letter-spacing: .06em;
  color: var(--prc-c-tenue);
}

.prc-tab-n {
  padding: .05em .5em;
  border-radius: 999px;
  background: var(--prc-esp-cont);
  color: var(--prc-esp);
  font-size: var(--prc-t-menudo);
  font-weight: 700;
}

/* --- lienzo -------------------------------------------------------------- */

.prc-hoja {
  width: var(--prc-fila-ancho);
  max-width: var(--prc-fila-max);
  margin-inline: auto;
  padding: 24px 0 44px;
}

/* Al romper la columna del tema, la hoja pasa a ser de ancho completo: el
   centrado lo hace este relleno lateral, y el 10 % es la otra mitad del 80 %
   de la fila. A 1470px de ventana deja los 1176px medidos. */
body.prc-app .prc-hoja {
  padding-left: max(16px, 10%, calc(50% - var(--prc-fila-max) / 2));
  padding-right: max(16px, 10%, calc(50% - var(--prc-fila-max) / 2));
}

.prc-hoja .prc-tabs { margin: 0 0 28px; }

/* Dos títulos, no un `h1` con variantes: el héroe de la portada y del taller
   —grande, en mayúsculas y centrado— y el título corriente de las pantallas
   de dentro, que va alineado a la izquierda y en caja normal. */
.prc-hero {
  font-family: var(--prc-fuente-hero);
  font-size: var(--prc-t-hero);
  line-height: var(--prc-i-hero);
  font-weight: var(--prc-p-hero);
  color: var(--prc-c-hero);
  text-transform: uppercase;
  text-align: center;
  margin: 0;
  padding: 0 0 10px;
}

.prc-h1 {
  font-size: var(--prc-t-h1);
  line-height: var(--prc-i-h1);
  font-weight: var(--prc-p-h1);
  color: var(--prc-c-titulo);
  letter-spacing: normal;
  text-align: left;
  text-transform: none;
  margin: 0 0 20px;
  padding: 0 0 10px;
}

.prc-sub {
  font-size: var(--prc-t-h2);
  line-height: var(--prc-i-h2);
  font-weight: var(--prc-p-h2);
  color: var(--prc-c-titulo);
  margin: 0;
  padding: 0 0 10px;
  max-width: none;
}

/* La fila del título con su acción principal a la derecha. */
.prc-h1-fila { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; margin: 0 0 4px; }
.prc-h1-fila .prc-h1 { margin: 0; }
.prc-h1-fila .prc-acciones { margin-left: auto; }

.prc-acciones { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

/* Dentro de una tabla, los botones de acción no se parten en varias líneas:
   la columna es estrecha y dos iconos apilados se leen como dos filas. Que la
   tabla se desplace en horizontal es lo que ya hace `.prc-tabla-caja`. */
.prc-tabla td .prc-acciones { flex-wrap: nowrap; }
.prc-tabla td .prc-acciones > * { flex: 0 0 auto; }

/* --- pie ----------------------------------------------------------------- */

.prc-pie { position: sticky; top: 100vh; background: var(--prc-pie-fondo); color: #fff; }

.prc-pie div {
  padding: 18px 0;
  display: flex;
  gap: 8px 24px;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: baseline;
  font-size: 13px;
}

.prc-pie-quien { display: flex; gap: 8px 18px; flex-wrap: wrap; align-items: baseline; }
.prc-pie-credito { color: rgba(255, 255, 255, .72); }
.prc-pie-enlaces { display: flex; gap: 18px; flex-wrap: wrap; }
.prc-pie a { color: #fff; text-decoration: underline; }
.prc-pie a:hover { color: #fff; text-decoration: none; }

/* --- el cartel de la ficha ------------------------------------------------ */

/* La imagen que anuncia el procedimiento; `prc-ficha.css` le da su sitio. */
.prc-ficha-cartel { display: block; max-width: 100%; height: auto; margin: 0 0 18px; border-radius: var(--prc-r); }

/* --- el formulario -------------------------------------------------------- */

.prc-form { max-width: 100%; }
.prc-form label { display: block; margin-bottom: 4px; font-weight: 600; font-size: 14px; }
.prc-form input[type="text"],
.prc-form input[type="url"],
.prc-form input[type="email"],
.prc-form input[type="date"],
.prc-form input[type="number"],
.prc-form input[type="search"],
.prc-form select,
.prc-form textarea {
  width: 100%;
  min-height: 44px;
  padding: 8px 12px;
  font: inherit;
  color: var(--prc-texto);
  background: var(--prc-sup);
  border: 1px solid #c3cad2;
  border-radius: 8px;
}
.prc-form textarea { min-height: 8rem; line-height: 1.5; }
.prc-form input::placeholder, .prc-form textarea::placeholder { color: #767676; font-style: italic; opacity: 1; }
.prc-form small, .prc-form .prc-nota { display: block; margin-top: 4px; font-size: 12.5px; color: var(--prc-texto-2); max-width: 60ch; }

/* --- la botonera ---------------------------------------------------------
   Botones de icono: cuadrados, con el icono centrado y el texto solo para
   lectores de pantalla. El bocadillo lo pone Bootstrap si está; si no, queda
   el `title` del navegador. El tamaño no baja de 36 px porque es el objetivo
   táctil mínimo que se puede acertar con el dedo. */
.prc-icono {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 36px;
  min-height: 36px;
  padding: 6px;
  line-height: 0;
}
.prc-icono svg { display: block; }

/* Un procedimiento cerrado se abre entero, pero sin poder guardar. El
   `fieldset` `disabled` ya apaga los controles; esto lo hace visible, porque
   un botón apagado sin señal se lee como una avería. El borde no es el único
   indicador: arriba hay un aviso escrito que dice por qué. */
.prc-solo-lectura { margin: 0; padding: 0; border: 0; opacity: .72; }
.prc-solo-lectura button,
.prc-solo-lectura input,
.prc-solo-lectura select,
.prc-solo-lectura textarea { cursor: not-allowed; }

/* Un anillo de foco visible en todo lo que se puede pulsar o escribir: sin él
   no se sabe dónde está el cursor al navegar con el tabulador. */
.prc-app a:focus-visible,
.prc-app button:focus-visible,
.prc-app input:focus-visible,
.prc-app select:focus-visible,
.prc-app textarea:focus-visible,
.prc-app summary:focus-visible {
  outline: 2px solid var(--prc-pri);
  outline-offset: 2px;
  border-radius: 4px;
}

/* --- tablas -------------------------------------------------------------- */

/* Una sola tabla con dos pieles: la densa —la que se ve en el taller y en la
   pantalla del centro— y la holgada. Sin pieles es la de hoy, que es la que
   usan las cinco pantallas hasta que cada una estrene la suya.

   Las reglas van por elemento y no por clase (`.prc-celda`, `.prc-th`) para
   que valgan igual con el marcado de hoy y con el que traiga cada pantalla:
   la clase se añade, no sustituye. */
.prc-tabla-caja { overflow-x: auto; background: var(--prc-c-fondo); border: 0; box-shadow: none; }
.prc-tabla {
  width: 100%;
  border-collapse: collapse;
  margin: 0 0 15px;
  font-size: var(--prc-t-base);
  color: var(--prc-c-tabla);
  background: var(--prc-c-fondo);
  border: 1px solid var(--prc-c-linea-tabla);
  border-radius: var(--prc-radio-caja);
  box-shadow: none;
}
.prc-tabla th, .prc-tabla td { padding: 6px 10px; text-align: left; }
.prc-tabla th {
  font-weight: 700;
  color: var(--prc-c-medio);
  background: transparent;
  border: 1px solid var(--prc-c-linea-th);
  vertical-align: bottom;
  white-space: nowrap;
}
.prc-tabla td {
  font-weight: 400;
  color: var(--prc-c-tabla);
  border: 1px solid var(--prc-c-linea-celda);
  vertical-align: top;
}
.prc-tabla tbody tr:nth-of-type(odd) > * { background: var(--prc-c-cebra); }
.prc-tabla tbody tr:nth-of-type(even) > * { background: transparent; }

.prc-tabla--densa { font-family: var(--prc-fuente); }
.prc-tabla--densa th,
.prc-tabla--densa td { font-size: var(--prc-t-tabla); line-height: var(--prc-i-tabla); padding: 3px; }
.prc-tabla .prc-acciones { justify-content: flex-start; }
.prc-tabla [hidden] { display: none !important; }

/* En pantalla estrecha la tabla pasa a tarjetas: cada fila, un bloque, y cada
   celda rotulada con el `data-rotulo` que pinta la pantalla. */
@media (max-width: 720px) {
  .prc-tabla-caja { border: 0; background: none; box-shadow: none; }
  .prc-tabla, .prc-tabla tbody, .prc-tabla tr, .prc-tabla td { display: block; width: auto; }
  .prc-tabla thead { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; }
  .prc-tabla tr {
    margin-bottom: 12px;
    padding: 6px 14px;
    background: var(--prc-sup);
    border: 1px solid var(--prc-linea);
    border-radius: var(--prc-r);
    box-shadow: var(--prc-e1);
  }
  .prc-tabla td { border-bottom: 0; padding: 6px 0; display: flex; gap: 12px; }
  .prc-tabla td::before {
    content: attr(data-rotulo);
    flex: 0 0 8rem;
    font-size: 12.5px;
    font-weight: 700;
    color: var(--prc-texto-2);
  }
  .prc-tabla td:empty { display: none; }
}

/* --- pastillas de estado ------------------------------------------------- */

/* Los estados del procedimiento (`ProcedureMetaKeys::states()`), los de
   revisión de una solicitud (`ApplicationMetaKeys::review_states()`) y el de
   publicación comparten la pastilla: el color dice de qué va cada uno, y el
   texto lo dice siempre. */
.prc-state {
  display: inline-block;
  padding: .3em .72em;
  border-radius: 999px;
  font-size: var(--prc-t-menudo);
  font-weight: 600;
  line-height: 1.4;
  text-transform: uppercase;
  white-space: nowrap;
  background: var(--prc-gris-fondo);
  color: var(--prc-gris-texto);
}
/* El curso, debajo y en cursiva, como en el original. */
.prc-state__curso { font-style: italic; font-weight: 400; text-transform: none; }
.prc-state-open { background: var(--prc-ok-cont); color: var(--prc-ok); }
.prc-state-amendment { background: var(--prc-esp-cont); color: var(--prc-esp); }
.prc-state-upcoming { background: var(--prc-inf-cont); color: var(--prc-inf); }
.prc-state-closed { background: var(--prc-gris-fondo); color: var(--prc-gris-texto); }
.prc-state-resolved { background: var(--prc-pri-cont); color: var(--prc-pri); }
.prc-state-draft { background: var(--prc-gris-fondo); color: var(--prc-gris-texto); }
.prc-state-archived { background: var(--prc-gris-fondo); color: var(--prc-gris-texto); }
.prc-state-submitted { background: var(--prc-inf-cont); color: var(--prc-inf); }
.prc-state-amend { background: var(--prc-esp-cont); color: var(--prc-esp); }
.prc-state-admitted { background: var(--prc-ok-cont); color: var(--prc-ok); }
.prc-state-excluded { background: var(--prc-mal-cont); color: var(--prc-mal); }
.prc-state-publish { background: var(--prc-ok-cont); color: var(--prc-ok); }

/* --- avisos -------------------------------------------------------------- */

/* Geometría única y color por tono, con Bootstrap y sin él: los cuatro tonos
   son los del original y no los de Bootstrap 5, que cambió el informativo.
   `Assets::alert_class()` ya emite `prc-aviso prc-aviso-{tono}`. */
.prc-aviso {
  padding: 12px 20px;
  margin: 0 0 16px;
  border: 1px solid transparent;
  border-radius: var(--prc-radio);
  font-size: var(--prc-t-base);
  line-height: var(--prc-i-base);
}
.prc-aviso-info { background: var(--prc-info-fondo); border-color: var(--prc-info-borde); color: var(--prc-info-texto); }
.prc-aviso-success { background: var(--prc-ok-fondo); border-color: var(--prc-ok-borde); color: var(--prc-ok-texto); }
.prc-aviso-warning { background: var(--prc-avi-fondo); border-color: var(--prc-avi-borde); color: var(--prc-avi-texto); }
.prc-aviso-danger { background: var(--prc-mal-fondo); border-color: var(--prc-mal-borde); color: var(--prc-mal-texto); }

/* La misma geometría sin color: la caja neutra que envuelve un bloque. */
.prc-caja {
  padding: 12px 20px;
  margin: 0 0 16px;
  border: 1px solid transparent;
  border-radius: var(--prc-radio);
}

/* Lo que se dice cuando no hay nada que enseñar, y la celda sin dato. */
.prc-vacio {
  font: 500 var(--prc-t-base)/var(--prc-i-base) var(--prc-fuente);
  color: var(--prc-c-texto);
  padding: 0 0 14px;
  margin: 0;
}
.prc-sindato { color: var(--prc-c-sindato); font-weight: 700; font-size: inherit; }

/* --- botones ------------------------------------------------------------- */

/* El aspecto lo ponemos nosotros, con Bootstrap y sin él: el botón del
   original es el de su Bootstrap, no el de la 5, y `Assets::button_class()`
   emite los dos vocabularios a la vez. Alto 38px = 24 de línea + 12 de
   relleno + 2 de borde. */
.prc-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 6px 12px;
  font-size: 16px;
  line-height: 24px;
  font-weight: 400;
  color: var(--prc-c-tabla);
  background: var(--prc-c-fondo-accion);
  border: 1px solid var(--prc-c-fondo-accion);
  border-radius: var(--prc-radio);
  text-decoration: none;
  cursor: pointer;
}
.prc-btn:hover { text-decoration: none; filter: brightness(.95); }
.prc-btn:focus-visible { outline: 2px solid var(--prc-c-azul-borde); outline-offset: 2px; }
.prc-btn .material-symbols-outlined { font-size: 24px; line-height: 24px; }

.prc-btn-primary { color: #fff; background: var(--prc-c-azul); border-color: var(--prc-c-azul); }
.prc-btn-primary:hover { color: #fff; background: var(--prc-c-azul-hover); border-color: var(--prc-c-azul-borde); filter: none; }

/* El secundario: el mismo botón en blanco. No está en el original —allí la
   acción secundaria era un enlace suelto— y se añade porque una pantalla con
   dos botones azules no dice cuál es la acción principal. */
.prc-btn--sec { color: var(--prc-c-azul); background: #fff; border-color: var(--prc-c-azul); }
.prc-btn--sec:hover { color: #fff; background: var(--prc-c-azul); filter: none; }

/* El botón de icono de una fila de tabla: en el original es una etiqueta
   clara con el icono dentro, y el número va fuera, pegado. */
.prc-accion {
  display: inline-block;
  padding: 2.4375px 3.9px;
  margin: 0;
  font: 700 9.75px/9.75px var(--prc-fuente);
  text-align: center;
  color: var(--prc-c-tabla);
  background: var(--prc-c-fondo-accion);
  border: 0;
  border-radius: var(--prc-radio);
  box-shadow: none;
  text-decoration: none;
}
.prc-accion .material-symbols-outlined { font-size: 24px; line-height: 24px; width: 24px; }
.prc-accion__contador { font-size: var(--prc-t-tabla); }

/* Con Bootstrap: solo sus variables, y solo bajo `body.prc-app`. Fuera de
   estas páginas Bootstrap sigue siendo el de siempre. `.btn-primary` no lee
   `--bs-primary` —la 5.3 le escribe el color en `--bs-btn-bg`—, así que hay
   que tocar las del botón. */
body.prc-app {
  --bs-primary: #007bff;
  --bs-primary-rgb: 0, 123, 255;
  --bs-link-color: #2ea3f2;
  --bs-link-hover-color: #0c71c3;
}
body.prc-app .btn { border-radius: var(--prc-radio); min-height: var(--prc-alto-boton); font-weight: 400; }
body.prc-app .btn-primary {
  --bs-btn-bg: var(--prc-c-azul);
  --bs-btn-border-color: var(--prc-c-azul);
  --bs-btn-hover-bg: var(--prc-c-azul-hover);
  --bs-btn-hover-border-color: var(--prc-c-azul-borde);
  --bs-btn-active-bg: var(--prc-c-azul-hover);
  --bs-btn-active-border-color: var(--prc-c-azul-borde);
}
body.prc-app .btn-light {
  --bs-btn-bg: var(--prc-c-fondo-accion);
  --bs-btn-border-color: var(--prc-c-fondo-accion);
  --bs-btn-color: var(--prc-c-tabla);
}
/* La acción destructiva se distingue sin gritar: tonal, no roja rellena. */
body.prc-app .prc-btn-borrar { --bs-btn-bg: var(--prc-mal-cont); --bs-btn-border-color: var(--prc-mal-cont); --bs-btn-color: var(--prc-mal); }
.prc-sin-bootstrap .prc-btn-borrar { background: var(--prc-mal-cont); color: var(--prc-mal); }

/* El diálogo de confirmación (SweetAlert2) se pinta al final del `body`, así
   que sus clases —`prc-app`, `prc-sin-bootstrap`— siguen valiendo y los
   botones del diálogo son exactamente los mismos que los de la página. Con
   `buttonsStyling: false` la librería no pone los suyos, y aquí solo queda
   devolverles la separación que ella daba y darle al cuadro nuestro radio.
   Si SweetAlert2 no llega, estas reglas no aplican a nada. */
.swal2-popup { border-radius: var(--prc-r); font: inherit; }
.swal2-popup .swal2-title { font-size: 20px; color: var(--prc-texto); }
.swal2-popup .swal2-html-container { font-size: 15px; color: var(--prc-texto-2); }
.swal2-popup .swal2-actions { gap: 8px; }

/* Los botoncitos de una fila de tabla: abrir, ver, revisar. */
.prc-mini { min-height: 36px; padding: 0 12px; font-size: 13px; }

/* --- lo que solo ve la administración ------------------------------------ */

/* Una convención del aplicativo, no un adorno de una pantalla: todo lo que
   solo ve quien administra —desarchivar, por ejemplo (ADR-0023)— se pinta aquí
   dentro y se reconoce sin leerlo. Lo pinta `Shell::admin_box()`, en un sitio,
   para que cualquier pantalla futura lo reutilice. Los contrastes de estos
   cuatro colores están calculados arriba, en la paleta. */
.prc-solo-admin {
  margin: 0 0 18px;
  padding: 16px 18px 4px;
  background: var(--prc-adm-fondo);
  border: 1px solid var(--prc-adm-borde);
  border-left-width: 5px;
  border-radius: var(--prc-r);
  color: var(--prc-adm-texto);
}
.prc-solo-admin + .prc-solo-admin { margin-top: 18px; }

/* La etiqueta en texto, para quien no distingue el color. */
.prc-solo-admin-marca {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin: 0 0 10px;
  padding: 3px 10px;
  border-radius: 999px;
  background: var(--prc-adm-marca);
  color: #fff;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: .02em;
  text-transform: uppercase;
}
.prc-solo-admin-marca svg { flex: 0 0 auto; }

.prc-solo-admin-titulo { margin: 0 0 4px; font-size: 17px; font-weight: 700; color: var(--prc-adm-texto); }
.prc-solo-admin-porque { margin: 0 0 14px; font-size: 13.5px; max-width: 70ch; color: var(--prc-adm-texto); }

/* Dentro del recuadro el texto sigue siendo el oscuro sobre amarillo: si un
   rótulo o una ayuda heredara el gris de fuera, se caería del 4,5:1.

   La etiqueta «Solo administración» queda FUERA de esta regla: es un `<p>`,
   así que `.prc-solo-admin p` (0,2,0) le ganaría a `.prc-solo-admin-marca`
   (0,1,0) y la pintaría del marrón oscuro sobre su propio fondo marrón. Con el
   `:not()` no la toca y se queda con su blanco sobre `--prc-adm-marca`. */
.prc-solo-admin label,
.prc-solo-admin small,
.prc-solo-admin p:not(.prc-solo-admin-marca),
.prc-solo-admin legend { color: var(--prc-adm-texto); }

/* --- el aviso de que lo está editando otra persona ------------------------ */

/* El aviso del bloqueo (`EditLock::render()`) sale de dos maneras y tiene que
   verse igual en las dos: el servidor lo pinta como `<dialog open>` —sin
   JavaScript, que es justo el motivo de haber elegido un `<dialog>` y no
   SweetAlert2— y el Heartbeat lo abre con `showModal()` cuando el bloqueo se
   pierde sin recargar. Sin estilo, un `<dialog open>` es un bloque
   `position: absolute` sin capa ni sombreado, y la pantalla se le pinta encima:
   el aviso está en el HTML y no se lee. */
.prc-dialogo[open] {
  position: fixed;
  inset: 0;
  z-index: 100;
  width: min(560px, calc(100vw - 32px));
  max-height: calc(100vh - 32px);
  margin: auto;
  padding: 24px 26px;
  overflow: auto;
  border: 1px solid var(--prc-linea);
  border-radius: var(--prc-r);
  background: var(--prc-sup);
  color: var(--prc-texto);
  box-shadow: 0 18px 50px rgba(0, 0, 0, .28);
}

/* El sombreado del fondo lo pone el navegador en `::backdrop`, pero solo
   cuando el diálogo se abrió con `showModal()`. El que llega abierto desde el
   servidor no lo tiene, así que se le pinta a mano; `:not(:modal)` es
   exactamente ese caso y evita oscurecer dos veces. */
.prc-dialogo[open]:not(:modal) {
  box-shadow: 0 18px 50px rgba(0, 0, 0, .28), 0 0 0 100vmax rgba(15, 23, 42, .55);
}
.prc-dialogo::backdrop { background: rgba(15, 23, 42, .55); }
.prc-dialogo h2 { margin: 0 0 14px; font-size: 20px; line-height: 1.3; }
.prc-dialogo p { margin: 0 0 12px; font-size: 14.5px; line-height: 1.55; }
.prc-dialogo .prc-acciones { gap: 10px; margin: 18px 0 0; }

/* --- móvil --------------------------------------------------------------- */

/* A 400 px de ancho tiene que seguir siendo usable: es la pantalla desde la
   que un equipo directivo mira si su solicitud ha sido admitida. */
@media (max-width: 640px) {
  .prc-top-fila,
  .prc-tabs-fila,
  .prc-pie div { width: auto; margin-inline: 16px; }
  .prc-top-fila { padding: 11px 0; gap: 10px; flex-wrap: nowrap; }
  .prc-marca { display: none; }
  .prc-marca-app { margin-left: 0; padding-left: 0; border-left: 0; font-size: 16px; }
  .prc-yo-txt { display: none; }
  .prc-yo > summary { padding: 0; }
  .prc-yo-ava { width: 40px; height: 40px; }
  .prc-logo { width: 78px; height: 45px; }
  .prc-tab { margin-right: 18px; padding: 10px 0; }
  /* El desplegable, en columna: en una pantalla estrecha una capa flotante
     tapa justo lo que se acaba de tocar. */
  .prc-desplegable { position: static; border: 0; box-shadow: none; padding: 0 0 8px; min-width: 0; }
  body.prc-app .prc-hoja { padding: 18px 16px 40px; }
  .prc-hero { font-size: 32px; line-height: 34px; }
  .prc-h1 { font-size: 24px; line-height: 26px; }
  .prc-sub { font-size: 20px; line-height: 22px; }
  .prc-h1-fila .prc-acciones { margin-left: 0; width: 100%; }
  .prc-solo-admin { padding: 14px 14px 2px; }
  /* En el móvil el pie no cabe: la pantalla es para el trabajo, y lo legal
     está a un toque en cualquier otra página del sitio. */
  .prc-pie { display: none; }
}

/* Etiqueta solo para lectores de pantalla. La trae WordPress y casi todos los
   temas, pero el aplicativo no puede darla por hecha: sin ella los rótulos de
   las casillas salen escritos en pantalla. */
.prc-app .screen-reader-text {
  position: absolute;
  width: 1px;
  height: 1px;
  margin: -1px;
  padding: 0;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

/* Quien pide menos movimiento, menos movimiento. */
@media (prefers-reduced-motion: reduce) {
  .prc-app * { transition-duration: .01ms !important; animation-duration: .01ms !important; }
}
',
  'css/prc-ficha.css' => '/* La ficha de un procedimiento: **solo lo que aparece en esta pantalla**.

   Los tokens, la fila, la caja neutra, los cuatro tonos de aviso, el botón y
   las pastillas de estado están en `prc-app.css` y aquí no se tocan: lo que
   esta hoja hace es montar encima la cabecera de dos columnas, el contador,
   la caja resumen, las pestañas de contenido, la tarjeta de acceso y el pie.

   Todas las medidas salen del sistema visual medido sobre el sitio que se
   sustituye: la fila de 1176px, la cabecera repartida en 57,8 % / 5,5 % /
   36,7 %, el contador de 280px de ancho con bloques de 58px y las pestañas de
   129px. Lo que no estaba medido va dicho en su comentario. */

/* --- la cabecera de dos columnas ----------------------------------------- */

/* El orden del DOM es el que hace falta al colapsar: primero el título y la
   resolución, después el contador. Así, en una pantalla estrecha, el contador
   queda debajo del título y no encima. */
.prc-ficha-cab {
  display: grid;
  grid-template-columns: var(--prc-ficha-izq) var(--prc-ficha-der);
  column-gap: var(--prc-hueco);
  align-items: start;
  margin: 0 0 24px;
}

@media (max-width: 980px) {
  .prc-ficha-cab { grid-template-columns: 1fr; row-gap: 24px; }
}

/* El título del procedimiento NO es el título de la pantalla: es 30px de peso
   800 en mayúsculas y centrado, y el dato se guarda en caja normal. No se
   trunca: de una a seis líneas, las que haga falta. */
.prc-titulo-proc {
  font-family: var(--prc-fuente);
  font-size: var(--prc-t-h1);
  line-height: var(--prc-i-h1);
  font-weight: var(--prc-p-hero);
  color: var(--prc-c-fuerte);
  letter-spacing: normal;
  text-transform: uppercase;
  text-align: center;
  margin: 0;
  padding: 0 0 10px;
}

.prc-ficha-cartel {
  display: block;
  max-width: 100%;
  height: auto;
  border-radius: var(--prc-radio);
  margin: 0 0 16px;
}

/* --- la resolución -------------------------------------------------------- */

/* Hereda la geometría de `.prc-caja` y añade las mayúsculas, que afectan al
   bloque entero. El cuerpo es 0.9em del de la página. */
.prc-resolucion { text-transform: uppercase; }

.prc-resolucion__texto,
.prc-resolucion__doc {
  font-size: var(--prc-t-micro);
  line-height: normal;
  margin: 0;
  padding: 0;
}

.prc-resolucion__doc { margin-top: var(--prc-t-micro); }

.prc-resolucion__doc a {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  color: var(--prc-c-enlace);
  text-decoration: none;
}

/* Aquí sí se subraya al pasar: es un enlace a un documento dentro de un bloque
   de texto, y sin subrayado no se distingue del resto del párrafo. */
.prc-resolucion__doc a:hover,
.prc-resolucion__doc a:focus-visible { text-decoration: underline; }

.prc-promotor {
  font-size: var(--prc-t-h5);
  line-height: var(--prc-i-h5);
  font-style: italic;
  color: var(--prc-c-medio);
  margin: 0;
  padding: 0;
}

/* --- el contador regresivo ------------------------------------------------ */

/* Cuatro bloques valor/rótulo separados por tres «:», centrados en la columna
   derecha. Ancho total del grupo 280px; paso entre centros de bloque 73,9px,
   de ahí los 58 + 16. El hueco es el mismo con contador, con aviso de plazo
   vencido y sin nada: la página no da saltos. */
.prc-cuenta-caja { text-align: center; }

/* Encima de las cifras, y no debajo: cuatro números sin rótulo no dicen a qué
   plazo cuentan, y con fase de subsanación eso importa. */
.prc-cuenta__pie {
  font-size: var(--prc-t-menudo);
  line-height: var(--prc-i-menudo);
  color: var(--prc-c-texto);
  margin: 0 0 6px;
  padding: 0;
}

.prc-cuenta {
  display: flex;
  justify-content: center;
  align-items: flex-start;
}

.prc-cuenta__bloque { width: var(--prc-cuenta-bloque); text-align: center; }
.prc-cuenta__sep { width: var(--prc-cuenta-sep); text-align: center; }

.prc-cuenta__valor,
.prc-cuenta__sep p {
  font-family: var(--prc-fuente-cifras);
  font-size: var(--prc-t-cifra);
  line-height: var(--prc-i-cifra);
  font-weight: 400;
  color: var(--prc-c-fuerte);
  font-variant-numeric: tabular-nums;
  margin: 0;
  padding: 0;
}

.prc-cuenta__rotulo {
  font-family: var(--prc-fuente);
  font-size: var(--prc-t-menudo);
  line-height: var(--prc-i-menudo);
  font-weight: 500;
  color: var(--prc-c-texto);
  margin: 2px 0 0;
  padding: 0;
}

/* El plazo vencido ocupa el hueco del contador con la misma caja: en el
   original el contador se quedaba en `000 : 00 : 00 : 00`, que no dice nada. */
.prc-cuenta-vencida {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-weight: 600;
  margin: 0 0 16px;
  padding: 12px 20px;
}

/* --- la caja resumen ------------------------------------------------------ */

/* Es `alert-info`, la misma caja informativa del original: no cambia el peso
   visual de la pantalla, solo lo que cuenta. */
.prc-resumen {
  display: block;
  padding: 16px 20px;
  margin: 0;
}

.prc-resumen__tit {
  font-size: var(--prc-t-menudo);
  line-height: 16px;
  font-weight: var(--prc-p-fuerte);
  text-transform: uppercase;
  letter-spacing: .04em;
  color: var(--prc-info-texto);
  opacity: .75;
  margin: 0 0 8px;
  padding: 0;
}

/* El estado, con su color y su icono. El color no es el único indicador: el
   estado va escrito al lado, y el icono lleva `aria-hidden`. */
.prc-resumen__estado {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: var(--prc-t-base);
  line-height: 20px;
  font-weight: 600;
  border: 1px solid;
  border-radius: var(--prc-radio);
  margin: 0 0 12px;
  padding: 6px 10px;
}

.prc-resumen__estado .material-symbols-outlined {
  font-size: 20px;
  line-height: 20px;
  width: 20px;
}

/* Los siete estados derivados (ADR-0013). Son siete y no los ocho de la
   leyenda del original porque dos de aquellos eran contradicciones entre la
   etiqueta escrita a mano y las fechas, y aquí el estado sale de las fechas.
   Los tonos son los de aviso, que están medidos contra su fondo; los colores
   crudos de los iconos del original (#aaa, #fc0, #f00) no llegan a 4,5:1 como
   color de texto. */
.prc-resumen__estado--open { background: var(--prc-ok-fondo); border-color: var(--prc-ok-borde); color: var(--prc-ok-texto); }
.prc-resumen__estado--amendment { background: var(--prc-avi-fondo); border-color: var(--prc-avi-borde); color: var(--prc-avi-texto); }
.prc-resumen__estado--upcoming,
.prc-resumen__estado--resolved { background: var(--prc-info-fondo); border-color: var(--prc-info-borde); color: var(--prc-info-texto); }
.prc-resumen__estado--closed,
.prc-resumen__estado--draft,
.prc-resumen__estado--archived { background: var(--prc-gris-fondo); border-color: var(--prc-gris-borde); color: var(--prc-gris-texto); }

/* La fila sin dato no se pinta, así que la lista nunca dice «Subsanación: —».
   `display: contents` en el `div` para que la pareja caiga en las dos columnas
   de la retícula sin perder el agrupamiento del marcado. */
.prc-resumen__datos {
  display: grid;
  grid-template-columns: max-content 1fr;
  column-gap: 12px;
  row-gap: 4px;
  margin: 0 0 12px;
}

.prc-resumen__datos > div { display: contents; }
.prc-resumen__datos dt { font-weight: var(--prc-p-fuerte); }
.prc-resumen__datos dd { margin: 0; }
.prc-resumen__datos a { color: var(--prc-info-texto); text-decoration: underline; }

.prc-resumen__enlaces {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin: 0 0 12px;
  padding: 0;
}

.prc-resumen__enlaces a {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: var(--prc-info-texto);
  text-decoration: underline;
}

.prc-resumen__enlaces .material-symbols-outlined,
.prc-resumen__mia .material-symbols-outlined {
  font-size: 20px;
  line-height: 20px;
  width: 20px;
  flex: 0 0 20px;
}

/* Lo que ahorra el viaje «ficha → mis solicitudes → volver». Solo lo ve quien
   ha entrado con un centro detrás. */
.prc-resumen__mia {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 6px;
  font-size: 13px;
  line-height: 20px;
  border-top: 1px solid var(--prc-info-borde);
  margin: 0;
  padding: 12px 0 0;
}

.prc-resumen__mia a { color: var(--prc-info-texto); text-decoration: underline; }

/* --- las pestañas de contenido -------------------------------------------- */

/* Caja de ancho completo con barra gris arriba y panel blanco. Las pestañas
   son todas del mismo ancho aunque el rótulo mida distinto, la barra no se
   reparte el ancho de la caja —sobra gris a la derecha— y la línea de debajo
   cruza también por debajo de la pestaña activa. Todo eso está medido. */
.prc-tabs-ficha {
  background: var(--prc-c-fondo);
  border: 1px solid var(--prc-c-linea);
  border-radius: var(--prc-radio-caja);
  margin: 0 0 24px;
}

.prc-tabs-ficha__barra {
  display: flex;
  flex-wrap: wrap;
  list-style: none;
  background: var(--prc-c-fondo-barra);
  border-bottom: 1px solid var(--prc-c-linea);
  margin: 0;
  padding: 0;
}

.prc-tabs-ficha__barra a {
  display: block;
  width: var(--prc-ancho-pestana);
  height: var(--prc-alto-pestana);
  line-height: var(--prc-alto-pestana);
  font-size: var(--prc-t-base);
  font-weight: 600;
  color: var(--prc-c-texto);
  text-align: center;
  text-decoration: none;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.prc-tabs-ficha__barra a[aria-selected="true"] {
  background: var(--prc-c-fondo);
  color: var(--prc-c-titulo);
}

.prc-tabs-ficha__panel { padding: 24px; }
.prc-tabs-ficha__panel + .prc-tabs-ficha__panel { border-top: 1px solid var(--prc-c-linea); }

.prc-tabs-ficha__titulo {
  font-size: var(--prc-t-h3);
  line-height: var(--prc-i-h3);
  font-weight: var(--prc-p-h3);
  color: var(--prc-c-azul);
  margin: 0;
  padding: 0 0 10px;
}

/* Con el guion cargado, el rótulo del panel lo dice ya la pestaña: se queda
   para quien navega con lector de pantalla. Se esconde DESDE el guion —la
   clase la pone él— para que sin guion siga viéndose y los paneles se lean
   como secciones seguidas. */
.prc-tabs-ficha--js .prc-tabs-ficha__titulo {
  position: absolute;
  width: 1px;
  height: 1px;
  margin: -1px;
  padding: 0;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

.prc-tabs-ficha--js .prc-tabs-ficha__panel + .prc-tabs-ficha__panel { border-top: 0; }

/* --- la tarjeta de acceso -------------------------------------------------- */

/* Tres columnas iguales de las que hoy se rellena una: es la retícula del
   original, y la tarjeta mide 362px de ancho a 1176px de fila. */
.prc-accesos {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
  margin: 0 0 24px;
}

@media (max-width: 991.98px) {
  .prc-accesos { grid-template-columns: 1fr; }
}

.prc-acceso {
  background: var(--prc-c-fondo);
  border: 1px solid var(--prc-c-linea-acceso);
  border-radius: var(--prc-radio);
  box-shadow: var(--prc-sombra);
  padding: 20px;
}

/* El rótulo de la tarjeta es una caja informativa, no un titular suelto. */
.prc-acceso__rotulo {
  font-size: var(--prc-t-h5);
  line-height: var(--prc-i-h5);
  font-weight: var(--prc-p-h5);
  background: var(--prc-info-fondo);
  border: 1px solid var(--prc-info-borde);
  border-radius: var(--prc-radio);
  color: var(--prc-info-texto);
  margin: 0 0 16px;
  padding: 12px 20px;
}

/* A quién se dirige, en grande y centrado: en el original es la línea que
   distingue una tarjeta de acceso de la de al lado. */
.prc-acceso__cifra {
  font-family: var(--prc-fuente-cifras);
  font-size: var(--prc-t-h2);
  line-height: var(--prc-i-h2);
  font-weight: var(--prc-p-fuerte);
  color: var(--prc-c-azul);
  text-align: center;
  margin: 0;
  padding: 0 0 10px;
}

.prc-acceso__texto { margin: 0; padding: 0 0 16px; }
.prc-acceso__accion { margin: 0; padding: 0; }

/* El botón de consulta —«Ver mi solicitud», cuando el plazo ya cerró— va en
   blanco con el contorno azul: una pantalla con el botón principal relleno y
   este también relleno no dice cuál es la acción que se espera.

   Va con el contenedor delante a propósito: la hoja del armazón se imprime
   ANTES que Bootstrap, así que un `.prc-btn--sec` a secas pierde contra el
   `.btn` de la librería, que pinta el fondo desde sus propias variables. */
.prc-acceso__accion .prc-btn--sec {
  color: var(--prc-c-azul);
  background: var(--prc-c-fondo);
  border-color: var(--prc-c-azul);
}

.prc-acceso__accion .prc-btn--sec:hover,
.prc-acceso__accion .prc-btn--sec:focus-visible {
  color: #fff;
  background: var(--prc-c-azul);
  border-color: var(--prc-c-azul-borde);
}

/* --- el filete y el pie ---------------------------------------------------- */

/* El original escribe `border: 1px dashed`, que dibuja un rectángulo punteado
   de 2px en vez de una línea. Aquí es `border-top`, y hay que anular además el
   `opacity: .25` que Bootstrap 5 le pone al `<hr>`. */
.prc-filete {
  border: 0;
  border-top: 1px dashed var(--prc-c-azul);
  opacity: 1;
  margin: 0 0 12px;
}

.prc-ficha-pie {
  display: flex;
  align-items: flex-start;
  justify-content: flex-end;
  gap: 16px;
  flex-wrap: wrap;
}

.prc-ficha-meta {
  font-size: var(--prc-t-meta);
  line-height: normal;
  text-align: right;
  margin: 0;
  padding: 0;
}

/* --- estrecho -------------------------------------------------------------- */

@media (max-width: 640px) {
  .prc-titulo-proc { font-size: 24px; line-height: 26px; }
  /* Las pestañas de ancho fijo no caben: pasan a repartirse la fila. */
  .prc-tabs-ficha__barra a { width: auto; flex: 1 1 auto; padding: 0 12px; }
  .prc-tabs-ficha__panel { padding: 16px; }
  .prc-ficha-pie { justify-content: flex-start; }
  .prc-ficha-meta { text-align: left; }
}
',
  'css/prc-formulario.css' => '/* Aplicativo de procedimientos: **la piel de formulario de la solicitud**.

   Dos partes, y la frontera está marcada:

     1. La retícula y las listas de opciones, que no saben qué se está
        rellenando y valen para cualquier formulario del aplicativo. Van con
        **una sola clase** a propósito: así cualquier piel las reajusta con su
        modificador delante y gana sin pelear con el orden de carga.
     2. La piel de la solicitud —etiqueta de 18px sobre control de 46px—, que
        es una de las dos pieles de formulario del sitio que se sustituye y va
        entera bajo `.prc-form--solicitud`. La otra, la del taller, la viste
        `prc-alta.css` bajo `.prc-form--taller`: **cada piel se queda dentro de
        su modificador** y así las dos conviven sin pisarse, cargue la hoja que
        cargue primero.

   Los tokens son los del sistema y viven en `prc-app.css`. Los que esta hoja
   necesita y allí todavía no están se piden con su valor medido de respaldo
   (`var(--token, valor)`): el día que el armazón los declare, mandan ellos y
   aquí no hay que tocar nada. */

/* ══ piel de formulario, reutilizable ═══════════════════════════════════ */

/* --- retícula: doce columnas con hueco del 2 % --------------------------
   Es la del original: una rejilla por bloque de campos, no una fila por par
   de campos, así que un campo de tres columnas y otro de seis encajan sin
   envolturas intermedias. */
.prc-seccion__cuerpo {
  display: grid;
  grid-template-columns: repeat(12, 1fr);
  grid-auto-rows: max-content;
  gap: 0 var(--prc-form-hueco);
}

.prc-seccion__cuerpo > * { grid-column: span 12 / span 12; }

.prc-campo--cuarto { grid-column: span 3 / span 3; }
.prc-campo--tercio { grid-column: span 4 / span 4; }
.prc-campo--medio { grid-column: span 6 / span 6; }
.prc-campo--completo { grid-column: span 12 / span 12; }
/* Detrás de los anchos: solo mueve el principio, el ancho se lo queda. */
.prc-campo--abre-fila { grid-column-start: 1; }

/* --- casillas y listas de opciones -------------------------------------- */
.prc-opciones--vertical .prc-opcion { display: block; margin-bottom: 10px; }
.prc-opciones--horizontal .prc-opcion { display: inline-block; padding-left: 0; margin-right: 12px; }

.prc-opcion label {
  display: inline-block;
  font-size: 16px;
  font-weight: 200;
  line-height: 1.3;
  color: var(--prc-c-texto);
  text-align: left;
  white-space: normal;
  vertical-align: middle;
  cursor: pointer;
}

.prc-opcion input[type="checkbox"],
.prc-opcion input[type="radio"] {
  width: 16px;
  min-width: 16px;
  height: 16px;
  padding: 0;
  margin: 0 4px 0 0;
  vertical-align: middle;
  background: var(--prc-c-fondo);
  border: 1px solid var(--prc-c-borde-campo);
  appearance: none;
}

.prc-opcion input[type="radio"] { border-radius: 50%; }
.prc-opcion input[type="checkbox"] { border-radius: var(--prc-radio-caja); }
.prc-opcion input:checked { border-color: var(--prc-c-foco); }

.prc-opcion input[type="checkbox"]:checked {
  background-color: var(--prc-c-foco);
  background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 12 9\'%3E%3Cpath fill=\'none\' stroke=\'%23fff\' stroke-width=\'2\' d=\'M1 4.5 4.5 8 11 1.5\'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: center;
  background-size: 9px;
}

.prc-opcion input[type="radio"]:checked::before {
  display: block;
  width: 8px;
  height: 8px;
  margin: 3px;
  content: "";
  background: var(--prc-c-foco);
  border-radius: 50%;
}

.prc-opcion input[disabled]:checked { background-color: var(--prc-c-borde-campo); border-color: var(--prc-c-borde-campo); }
.prc-opcion input:focus-visible { outline: 2px solid var(--prc-c-foco); outline-offset: 2px; }

/* Dibujar la casilla a mano la borra en alto contraste: ahí manda la del
   sistema, que es la que el modo sabe pintar. */
@media (forced-colors: active) {
  .prc-opcion input[type="checkbox"],
  .prc-opcion input[type="radio"] { appearance: auto; }
}

/* --- estrecho -----------------------------------------------------------
   El único punto de ruptura del formulario: por debajo, todo a ancho
   completo. Va al final del bloque para pisar los anchos de arriba sin
   `!important`. */
@media only screen and (max-width: 600px) {
  .prc-seccion__cuerpo > * { grid-column: 1 / span 12; }
}

/* ══ fin de la piel reutilizable ════════════════════════════════════════ */

/* --- la caja de cada campo ----------------------------------------------
   5px de relleno y ningún margen inferior: entre dos campos contiguos quedan
   los 10px medidos. */
.prc-form--solicitud .prc-campo {
  min-width: 0;
  padding: 5px;
  margin: 0 5px;
  text-align: left;
}

/* --- banda de sección ---------------------------------------------------
   El filete de 2px encima del rótulo es lo que separa un bloque del
   siguiente: no hay cajas, ni bordes, ni sombras. La banda beige maciza es la
   del taller, no la de aquí. */
.prc-form--solicitud .prc-seccion,
.prc-solicitud-resumen {
  margin: 15px 0 0;
  padding: 0;
  border: 0;
}

.prc-form--solicitud .prc-seccion__titulo,
.prc-solicitud-resumen > .prc-seccion__titulo {
  display: block;
  width: auto;
  height: auto;
  padding: 12px 0 8px;
  margin: 0 0 15px;
  font: var(--prc-p-h3) var(--prc-t-h3) / var(--prc-i-h3) var(--prc-fuente);
  color: var(--prc-c-fuerte);
  text-transform: none;   /* los rótulos ya vienen en mayúsculas en el texto */
  background: transparent;
  border: none;
  border-top: 2px solid var(--prc-c-linea-seccion);
  border-radius: 0;
}

/* Sección sin rótulo visible: ni filete ni el aire de encima. */
.prc-form--solicitud .prc-seccion--sin-titulo { margin-top: 0; }
.prc-form--solicitud .prc-seccion--sin-linea > .prc-seccion__titulo { border-top-width: 0; }

/* --- etiqueta, ayuda y error -------------------------------------------- */
.prc-form--solicitud .prc-campo__etiqueta {
  display: block;
  width: auto;
  max-width: 100%;
  padding: 0 0 5px;
  margin: 0;
  font: 500 18px / 23.8px var(--prc-fuente);
  color: var(--prc-c-texto);
  text-align: left;
}

.prc-form--solicitud .prc-campo__etiqueta--enunciado strong { font-weight: var(--prc-p-fuerte); }
.prc-form--solicitud .prc-campo__obligatorio { font-weight: 300; color: var(--prc-c-obligatorio); }

.prc-form--solicitud .prc-campo__ayuda {
  max-width: 100%;
  padding: 0;
  margin-top: 6px;
  font: 300 var(--prc-t-base) / var(--prc-i-base) var(--prc-fuente);
  color: var(--prc-c-texto);
  text-align: left;
}

.prc-form--solicitud .prc-campo__error {
  padding: 0;
  margin-top: 6px;
  font: 500 var(--prc-t-base) / var(--prc-i-base) var(--prc-fuente);
  color: var(--prc-c-obligatorio);
  text-align: left;
}

.prc-form.prc-form--solicitud .prc-campo--error .prc-control {
  color: var(--prc-c-fuerte);
  background: var(--prc-c-fondo);
  border: 1px solid var(--prc-c-obligatorio);
}

/* --- el control --------------------------------------------------------- */
/* Las dos clases del formulario delante, como en `prc-alta.css`: el armazón
   pinta `.prc-form input[type="text"]`, y un atributo pesa lo que una clase,
   así que con una sola clase la caja del original —46px de alto, sin radio y
   con 10px de relleno— se la comía la del esqueleto. */
.prc-form.prc-form--solicitud .prc-control {
  box-sizing: border-box;
  width: 100%;
  max-width: 100%;
  min-height: var(--prc-alto-campo);
  padding: 10px;
  font-family: inherit;
  font-size: 18px;
  font-weight: 200;
  line-height: 1.3;
  color: var(--prc-c-medio);
  background: var(--prc-c-fondo);
  border: 1px solid var(--prc-c-borde-campo);
  border-radius: var(--prc-radio-caja);
  outline: none;
  box-shadow: none;
}

.prc-form.prc-form--solicitud textarea.prc-control { line-height: var(--prc-i-base); }
.prc-form.prc-form--solicitud .prc-control::placeholder { color: var(--prc-c-bloq-texto, #a1a1a1); font-style: normal; opacity: 1; }

/* Al enfocar, el original solo cambia el borde. Con el teclado eso es poco
   —un borde de 1px que pasa de gris a turquesa—, así que ahí va además el
   contorno de siempre. */
.prc-form.prc-form--solicitud .prc-control:focus {
  color: var(--prc-c-medio);
  background: var(--prc-c-fondo);
  border-color: var(--prc-c-foco);
  outline: none;
  box-shadow: none;
}

.prc-form.prc-form--solicitud .prc-control:focus-visible { outline: 2px solid var(--prc-c-foco); outline-offset: 1px; }

/* Bloqueado: **solo el color**. Mismo alto, mismo borde, mismo radio y el
   fondo sigue blanco; lo que se aclara es el texto y el borde. Sin candado y
   sin fondo gris, que es como está medido.

   `#a1a1a1` sobre blanco da 2,5:1: quien despliegue lo sube redefiniendo
   `--prc-c-bloq-texto` sin tocar esta hoja. */
.prc-form.prc-form--solicitud .prc-control--bloqueado,
.prc-form.prc-form--solicitud .prc-control[readonly],
.prc-form.prc-form--solicitud .prc-control[disabled] {
  color: var(--prc-c-bloq-texto, #a1a1a1);
  background: var(--prc-c-fondo);
  border-color: var(--prc-c-bloq-borde, #e5e5e5);
}

/* --- bloque de texto fijo entre campos ---------------------------------- */
.prc-form--solicitud .prc-nota {
  display: block;
  max-width: none;
  padding: 5px;
  margin: 0 5px;
  font-size: 18px;
  line-height: var(--prc-i-base);
  color: var(--prc-c-texto);
  overflow-wrap: break-word;
}

.prc-form--solicitud .prc-nota p { padding-bottom: 1em; margin: 0; }
.prc-form--solicitud .prc-nota p:last-child { padding-bottom: 0; }

/* --- botón de envío -----------------------------------------------------
   El color medido es el del despliegue que se sustituye y queda como variable
   (`--prc-envio-fondo`), no como token del sistema: blanco sobre `#5f9ea0` da
   3,05:1, que llega para texto grande y no para texto normal. Quien despliegue
   lo sube sin tocar esta hoja. */
.prc-form--solicitud .prc-boton-enviar {
  display: inline-flex;
  gap: 6px;
  align-items: center;
  width: auto;
  height: auto;
  padding: 12px 20px;
  margin: 30px 0;
  font-family: inherit;
  font-size: 18px;
  font-weight: 600;
  line-height: normal;
  color: #fff;
  text-align: center;
  text-shadow: none;
  vertical-align: middle;
  cursor: pointer;
  background: var(--prc-envio-fondo, #5f9ea0);
  border: 1px solid var(--prc-envio-fondo, #5f9ea0);
  border-radius: var(--prc-radio-caja);
  box-shadow: var(--prc-sombra-boton);
}

.prc-form--solicitud .prc-boton-enviar:hover,
.prc-form--solicitud .prc-boton-enviar:active {
  color: #fff;
  background: var(--prc-envio-hover, #099);
  border-color: var(--prc-envio-hover, #099);
}

.prc-form--solicitud .prc-boton-enviar:focus-visible { outline: 2px solid var(--prc-c-azul-borde); outline-offset: 2px; }
.prc-form--solicitud .prc-boton-enviar[disabled] { cursor: not-allowed; opacity: .5; }

/* --- aviso de protección de datos ---------------------------------------
   La plantilla pinta la caja; **el texto es del despliegue** y llega por el
   filtro del armazón. Aquí no hay ni una línea de texto legal (ADR-0009). */
.prc-aviso-legal {
  margin: 0 0 15px;
  font: var(--prc-p-base) var(--prc-t-base) / var(--prc-i-base) var(--prc-fuente);
  color: var(--prc-c-texto);
}

.prc-aviso-legal p { padding: 0; margin: 0 0 1em; }
.prc-aviso-legal p:last-child { margin-bottom: 0; }
.prc-aviso-legal ul,
.prc-aviso-legal ol { padding-left: 1em; margin: 0 0 1em; }
.prc-aviso-legal strong { font-weight: var(--prc-p-fuerte); }
.prc-aviso-legal em { font-style: italic; }

/* --- la tira de estado de la solicitud ----------------------------------
   El estado de la solicitud y, cuando toca subsanar, el motivo y hasta
   cuándo: encima del formulario que hay que corregir, y no en un correo. El
   color y la caja son los del aviso del sistema; aquí solo se pone el icono
   al lado del texto. */
.prc-solicitud-estado { display: flex; gap: 10px; align-items: flex-start; }
.prc-solicitud-estado .material-symbols-outlined { flex: 0 0 var(--prc-icono); }
.prc-solicitud-estado__cuerpo { display: block; }
.prc-solicitud-estado__estado { display: block; font-weight: var(--prc-p-fuerte); }

.prc-solicitud-estado__fecha,
.prc-solicitud-estado__nota,
.prc-solicitud-estado__enlace { display: block; margin-top: 4px; }
',
  'css/prc-mi-centro.css' => '/* «Mis solicitudes» del centro.
   ==========================================================================

   Lo propio de esta pantalla y nada más: la ayuda de ordenación, el
   recuento, las cabeceras ordenables con su flecha y el convenio de celda
   sin dato. La tabla, la cebra, los bordes y la piel densa vienen de
   `prc-app.css` y no se tocan.

   Todo cuelga de `.prc-mi-centro` para que ninguna regla de aquí se escape a
   las demás pantallas mientras cada una estrena la suya. */

.prc-mi-centro {

  /* Falta en `prc-app.css`: el azul de enlace que alcanza AA sobre la fila
     tintada. El `--prc-c-enlace` del sistema (#2ea3f2) se queda en 2,7 : 1 y
     en la tabla es el único punto de entrada. Cuando el armazón declare un
     `--prc-c-enlace-fuerte`, esta línea sobra. */
  --prc-enlace-tabla: #0b6fb8;
}

/* --- la ayuda de ordenación y el recuento -------------------------------- */

/* Las tres líneas van con `<br>`, no en párrafos: el hueco entre ellas es el
   del interlineado, no el de párrafo. */
.prc-mi-centro .prc-ayuda-orden,
.prc-mi-centro .prc-recuento {
  margin: 0;
  padding: 0 0 14px;
  font: var(--prc-p-base) var(--prc-t-base)/var(--prc-i-base) var(--prc-fuente);
  color: var(--prc-c-texto);
}

.prc-mi-centro .prc-ayuda-orden strong,
.prc-mi-centro .prc-recuento strong { font-weight: var(--prc-p-fuerte); color: inherit; }

/* --- las cabeceras ordenables -------------------------------------------- */

/* El rótulo no es azul: es la cabecera de su columna. La flecha va en
   `::after`, con caracteres y no con una imagen, y la elige `aria-sort`, que
   es el mismo dato que lee quien navega con lector de pantalla. */
.prc-mi-centro .prc-th--ordenable { cursor: pointer; }

.prc-mi-centro .prc-th-enlace {
  position: relative;
  display: block;
  padding-right: 14px;
  color: inherit;
  text-decoration: none;
}

.prc-mi-centro .prc-th-enlace::after {
  content: "\\2195";
  position: absolute;
  top: 50%;
  right: 0;
  transform: translateY(-50%);
  font-size: 11px;
  line-height: 1;
  color: var(--prc-c-tenue);
}

.prc-mi-centro .prc-th[aria-sort="ascending"] .prc-th-enlace::after { content: "\\25B2"; color: var(--prc-c-medio); }
.prc-mi-centro .prc-th[aria-sort="descending"] .prc-th-enlace::after { content: "\\25BC"; color: var(--prc-c-medio); }
.prc-mi-centro .prc-th-enlace:hover { color: var(--prc-c-titulo); text-decoration: none; }

/* --- las celdas ---------------------------------------------------------- */

/* La primera celda de cada fila es un `th`: el nombre del procedimiento es
   lo que identifica la fila, y así se lee antes que el dato de cada columna. */
.prc-mi-centro .prc-celda--titulo {
  font-weight: var(--prc-p-fuerte);
  text-align: left;
  vertical-align: top;
  white-space: normal;
  background: transparent;
  border: 1px solid var(--prc-c-linea-celda);
}

.prc-mi-centro .prc-celda--titulo a {
  font-weight: var(--prc-p-fuerte);
  color: var(--prc-enlace-tabla);
  text-decoration: none;
}

.prc-mi-centro .prc-celda--titulo a:hover,
.prc-mi-centro .prc-celda--titulo a:focus-visible { text-decoration: underline; }

.prc-mi-centro .prc-celda--titulo .prc-state { display: inline-block; margin: 3px 0 0; }
.prc-mi-centro .prc-celda--fecha { white-space: nowrap; }

/* Sin dato: el «¿?» de siempre. `.prc-sindato` ya lo viste el armazón. */
.prc-mi-centro .prc-sindato { white-space: nowrap; }

.prc-mi-centro .prc-celda--revision .prc-state { display: inline-block; }

.prc-mi-centro .prc-nota-revision {
  display: block;
  margin: 3px 0 0;
  font-style: italic;
  color: var(--prc-c-texto);
}

.prc-mi-centro .prc-ver-solicitud {
  display: inline-block;
  margin: 3px 0 0;
  color: var(--prc-enlace-tabla);
  text-decoration: underline;
}

/* --- la fila con la subsanación abierta ---------------------------------- */

/* El plazo de subsanación es lo que más caro sale perder. Arriba va el aviso
   y aquí el filete, del mismo tono: color y posición, nunca solo color. */
.prc-mi-centro .prc-subsanar > :first-child { box-shadow: inset 3px 0 0 var(--prc-avi-texto); }

/* --- en estrecho --------------------------------------------------------- */

/* Por debajo de 720px el armazón pasa la tabla a tarjetas, pero solo conoce
   los `td`. La primera celda es un `th` y necesita el mismo trato: bloque, y
   de cabecera de la tarjeta. */
@media (max-width: 720px) {

  .prc-mi-centro .prc-tabla tbody th { display: block; width: auto; padding: 6px 0; border: 0; }

  /* En tarjetas la fila deja de tener un borde izquierdo común: el filete
     vuelve a ir celda a celda, que es lo que dibuja el costado de la tarjeta. */
  .prc-mi-centro .prc-subsanar > * { box-shadow: inset 3px 0 0 var(--prc-avi-texto); }

  .prc-mi-centro .prc-celda--titulo { font-size: var(--prc-t-base); }
  .prc-mi-centro .prc-ayuda-orden { display: none; }
}
',
  'css/prc-portada.css' => '/* La portada pública (`[prc_home]`): héroe, entradilla con los filtros y un
   bloque gris por estado con su rejilla de tarjetas.

   Los tokens y los componentes comunes están en `prc-app.css` y aquí no se
   tocan: esta hoja solo trae lo que no aparece en ninguna otra pantalla. */

.prc-portada {
  /* Los cuatro colores de rótulo medidos sobre el sitio que se sustituye, y
     dos más para los dos estados que allí no tenían bloque propio. El amarillo
     del original (#ff0 sobre gris claro, 1,1:1) no se copia: es ilegible. Los
     seis están medidos contra el gris #dbdbdb del bloque y ninguno baja de
     3:1, que es lo que pide un rótulo de 22px en negrita:

       #0c71c3 → 3,64:1   #8a6800 → 3,73:1   #555555 → 5,38:1
       #378407 → 3,40:1   #155724 → 6,27:1   #6b6b6b → 3,85:1  */
  --prc-rot-open: var(--prc-c-azul-rotulo);
  --prc-rot-amendment: #8a6800;
  --prc-rot-upcoming: var(--prc-c-medio);
  --prc-rot-closed: #378407;
  --prc-rot-resolved: var(--prc-ok-texto);
  --prc-rot-archived: #6b6b6b;
}

/* --- las bandas de ancho completo ---------------------------------------- */

/* La banda ocupa la ventana entera y su contenido se vuelve a centrar sobre la
   fila de siempre. El margen negativo cancela justo el relleno lateral del
   lienzo, tanto cuando el aplicativo se pinta solo como dentro del tema. */
.prc-banda { margin-inline: calc(50% - 50vw); }

/* Relleno vertical [PROPUESTO]: el volcado solo midió el ancho. */
.prc-banda > .prc-fila { padding: 18px 0 26px; }

/* --- la entradilla y los filtros ----------------------------------------- */

.prc-portada__intro { background: var(--prc-c-fondo-intro); }

.prc-portada__entrada {
  max-width: 62ch;
  font-size: var(--prc-t-base);
  line-height: var(--prc-i-base);
  color: var(--prc-c-texto);
}

/* Pestañas de verdad: cajas blancas contiguas, sin hueco ni radio, sobre el
   fondo de la banda. Son enlaces, así que filtran sin JavaScript. */
.prc-filtros { display: flex; flex-wrap: wrap; gap: 8px 28px; margin: 6px 0 0; }
.prc-filtros__eje { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

.prc-filtros__rotulo {
  font-size: var(--prc-t-menudo);
  line-height: var(--prc-i-menudo);
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .04em;
  color: var(--prc-c-texto);
}

.prc-filtros ul { display: flex; flex-wrap: wrap; list-style: none; margin: 0; padding: 0; }

.prc-filtros a {
  display: block;
  padding: 4px 14px;
  background: var(--prc-c-fondo);
  color: var(--prc-c-texto);
  font-size: var(--prc-t-base);
  line-height: var(--prc-i-base);
}

.prc-filtros li + li a { border-left: 1px solid var(--prc-c-fondo-intro); }
.prc-filtros a:hover { color: var(--prc-c-titulo); }
.prc-filtros a.is-activo { color: var(--prc-c-titulo); font-weight: 600; }
.prc-filtros a:focus-visible { outline: 2px solid var(--prc-c-azul-rotulo); outline-offset: -2px; }

/* --- un bloque por estado ------------------------------------------------ */

/* Los bloques van pegados y comparten fondo: lo que los separa es su título,
   como en el sitio que se sustituye. */
.prc-bloque { background: var(--prc-c-fondo-bloque); }

.prc-bloque__titulo {
  margin: 0;
  padding: 0 0 18px;
  font-size: var(--prc-t-h3);
  line-height: var(--prc-i-h3);
  font-weight: var(--prc-p-h3);
  color: var(--prc-c-titulo);
}

.prc-bloque__rotulo { font-weight: var(--prc-p-fuerte); text-transform: uppercase; }

.prc-bloque--open .prc-bloque__rotulo { color: var(--prc-rot-open); }
.prc-bloque--amendment .prc-bloque__rotulo { color: var(--prc-rot-amendment); }
.prc-bloque--upcoming .prc-bloque__rotulo { color: var(--prc-rot-upcoming); }
.prc-bloque--closed .prc-bloque__rotulo { color: var(--prc-rot-closed); }
.prc-bloque--resolved .prc-bloque__rotulo { color: var(--prc-rot-resolved); }
.prc-bloque--archived .prc-bloque__rotulo { color: var(--prc-rot-archived); }

/* --- la rejilla de tarjetas ---------------------------------------------- */

/* Cuatro columnas con hueco del 5,5 % en las dos direcciones: a 1176px de fila
   son los 245,5px de tarjeta y los 64,7px de hueco medidos. El `auto-fill` las
   va quitando solo al estrechar, sin un punto de ruptura por cada anchura. */
.prc-rejilla {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
  column-gap: var(--prc-hueco);
  row-gap: var(--prc-hueco-px);
  align-items: start;
}

/* La tarjeta no es una caja: ni borde, ni sombra, ni fondo propio. El efecto
   de tarjeta lo da el marco blanco contra el gris del bloque. */
.prc-proc {
  display: block;
  width: auto;
  margin: 0;
  padding: 0;
  background: none;
  border: 0;
  border-radius: 0;
  box-shadow: none;
}

.prc-proc p { margin: 0; padding-bottom: 0; }
.prc-proc__enlace { display: block; color: inherit; text-decoration: none; }
.prc-proc__enlace:hover .prc-proc__titulo { color: var(--prc-c-azul-rotulo); }

.prc-proc__enlace:focus-visible {
  outline: 2px solid var(--prc-c-azul-rotulo);
  outline-offset: 2px;
}

.prc-proc__marco { display: block; padding: 10px; background: var(--prc-c-fondo); }

.prc-proc__banda {
  display: block;
  height: var(--prc-banda-tarjeta);
  background: var(--prc-banda, #c9c9c9);
}

/* El título no se trunca: de una a seis líneas, como esté escrito. */
.prc-proc__titulo {
  display: block;
  margin: 10px 0 0;
  font-size: var(--prc-t-tarjeta);
  line-height: var(--prc-i-tarjeta);
  font-weight: 500;
  color: var(--prc-c-titulo);
}

.prc-proc__estado {
  font-size: var(--prc-t-menudo);
  line-height: var(--prc-i-menudo);
  font-variant: small-caps;
  font-weight: 600;
  color: var(--prc-e-etiqueta);
}

/* Los días que quedan y la marca del centro: esto el original no lo tiene, y
   es lo que ahorra el viaje «portada → ficha → ver si ya lo pedí». */
.prc-proc__plazo,
.prc-proc__marca {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: var(--prc-t-menudo);
  line-height: var(--prc-i-menudo);
  color: var(--prc-c-medio);
}

/* El rojo medido del original (#e02b20) da 3,35:1 contra el gris del bloque y
   esto es texto de 12px: se oscurece hasta 5,61:1 sin cambiar de tono. */
.prc-proc__plazo--urgente { color: #a31910; font-weight: 600; }
.prc-proc__marca { color: var(--prc-ok-texto); font-weight: 600; }

.prc-app .prc-proc__plazo .material-symbols-outlined,
.prc-app .prc-proc__marca .material-symbols-outlined {
  font-size: 18px;
  line-height: 18px;
  width: 18px;
  flex: 0 0 18px;
}

/* En estrecho la fila deja de medirse en porcentaje y el hueco de la rejilla,
   también: un 5,5 % de 340px no separa nada. */
@media (max-width: 980px) {
  .prc-rejilla { column-gap: 24px; row-gap: 36px; }
  .prc-banda > .prc-fila { padding: 16px 0 22px; }
}
',
  'css/prc-taller.css' => '/* El taller del gestor: la pantalla «Gestión de procedimientos».

   Calco de la pantalla que se sustituye: título grande, banda de dos mitades
   con el buscador a la izquierda y la leyenda de estados a la derecha, una
   pestaña por curso, la línea de recuento y la tabla de dos niveles con su
   paleta granate. Los tokens y los componentes comunes —fila, héroe, tabla
   base, pastilla de estado, botón de icono, estado vacío— están en
   `prc-app.css` y aquí no se repiten: esto es solo lo que esta pantalla
   tiene y ninguna otra.

   La paleta de la cabecera de dos niveles se declara aquí, en el ámbito de la
   pantalla, porque hoy no la usa nadie más. Si el editor acaba pintando la
   misma cabecera, sube a `prc-app.css` sin cambiar de nombre. */

.prc-taller {
  --prc-g1: #9a031e;      /* grupo «Detalles del procedimiento» */
  --prc-g2: #5f0f40;      /* grupo «Fecha límite» */
  --prc-g3: #321325;      /* grupo «Gestión» */
  --prc-g1-sub: #daa3ad;  /* segunda fila de cabecera, bajo cada grupo */
  --prc-g2-sub: #c4a7b9;
  --prc-g3-sub: #b4a9af;
  --prc-g-texto: #fff;
}

/* --- la banda de cabecera ------------------------------------------------ */

/* Dos mitades con el hueco del 5,5 % de la fila. La ilustración del original
   no se calca: es una imagen del sitio que se sustituye, no diseño. */
.prc-taller__banda {
  display: grid;
  grid-template-columns: 1fr 1fr;
  column-gap: var(--prc-hueco-px);
  align-items: start;
  padding: var(--prc-fila-relleno);
}

.prc-taller__izq { text-align: center; }
.prc-taller__izq .prc-acciones { justify-content: center; }

.prc-taller__nota {
  font-size: var(--prc-t-base);
  line-height: var(--prc-i-base);
  color: var(--prc-c-texto);
  margin: 0;
  padding: 10px 0;
}

@media (max-width: 991.98px) {
  .prc-taller__banda { grid-template-columns: 1fr; row-gap: 16px; }
}

/* --- el buscador de la tabla --------------------------------------------- */

/* Los controles van en bloque y centrados, como en el original: el campo con
   su ancho de siempre y el botón debajo. Filtra el servidor. */
.prc-buscador { margin: 0; }

.prc-buscador__campo,
.prc-buscador__estado {
  display: block;
  margin: 0 auto 6px;
  width: 177px;
  max-width: 100%;
  padding: 6px 10px;
  font: 400 var(--prc-t-base)/normal var(--prc-fuente);
  color: var(--prc-c-texto);
  background: var(--prc-c-fondo);
  border: 1px solid var(--prc-c-linea);
  border-radius: var(--prc-radio-campo);
}

.prc-buscador__boton {
  display: block;
  margin: 0 auto;
  padding: 6px 12px;
  font: 400 var(--prc-t-base)/1.4 var(--prc-fuente);
  color: var(--prc-c-fuerte);
  background: #e6e6e6;
  border: 0;
  border-radius: var(--prc-radio);
  cursor: pointer;
}

.prc-buscador__boton:hover { filter: brightness(.95); }
.prc-buscador__campo:focus-visible,
.prc-buscador__estado:focus-visible,
.prc-buscador__boton:focus-visible { outline: 2px solid var(--prc-c-azul-borde); outline-offset: 2px; }
.prc-buscador__quitar { display: inline-block; margin: 8px 0 0; font-size: var(--prc-t-menudo); }

/* --- la leyenda de estados ----------------------------------------------- */

.prc-leyenda {
  border: 1px solid var(--prc-c-linea);
  background: var(--prc-c-fondo-leyenda);
  padding: 10px 14px;
}

.prc-leyenda__lista { list-style: none; margin: 0; padding: 0; }

.prc-leyenda__item {
  display: flex;
  align-items: center;
  gap: 6px;
  font: var(--prc-p-base) var(--prc-t-base)/var(--prc-i-base) var(--prc-fuente);
  color: var(--prc-c-texto);
}

.prc-leyenda__item strong { font-weight: var(--prc-p-fuerte); }

/* El color de cada estado, el mismo en la leyenda y en la tabla. Son siete y
   no ocho: el estado se deriva de las fechas, así que «abierto con el plazo
   vencido» y «cerrado con el plazo vivo» no pueden darse. Lo que queda de
   aquellas dos entradas es el aviso de datos que no cuadran. */
.prc-icono-estado { flex: 0 0 var(--prc-icono); color: var(--prc-c-tabla); }
.prc-icono-estado--open { color: var(--prc-e-abierta); }
.prc-icono-estado--closed { color: var(--prc-e-cerrada); }
.prc-icono-estado--amendment { color: var(--prc-e-reclamacion); }
.prc-icono-estado--resolved { color: var(--prc-e-pendiente); }
.prc-icono-estado--upcoming { color: var(--prc-e-pendiente); }
.prc-icono-estado--draft { color: var(--prc-e-borrador); }
.prc-icono-estado--archived { color: var(--prc-e-historica); }
.prc-icono-estado--aviso { color: var(--prc-avi-texto); }
.prc-icono-azul { color: var(--prc-c-azul-tabla); }

/* --- las pestañas por curso ---------------------------------------------- */

.prc-cursos { margin: 6px 0 0; }

.prc-cursos__lista {
  display: flex;
  flex-wrap: wrap;
  list-style: none;
  margin: 0;
  padding: 0;
  border-bottom: 1px solid var(--prc-c-linea-celda);
}

.prc-cursos__tab {
  display: block;
  padding: 8px 16px;
  font-size: var(--prc-t-base);
  line-height: var(--prc-i-base);
  font-weight: 400;
  color: #495057;
  text-align: center;
  text-decoration: none;
  border: 1px solid transparent;
  border-radius: var(--prc-radio) var(--prc-radio) 0 0;
  margin-bottom: -1px;
}

.prc-cursos__tab:hover { border-color: var(--prc-c-fondo-barra) var(--prc-c-fondo-barra) var(--prc-c-linea-celda); }
.prc-cursos__tab:focus-visible { outline: 2px solid var(--prc-c-azul-borde); outline-offset: -2px; }

.prc-cursos__tab--on {
  background: var(--prc-c-fondo);
  color: var(--prc-c-titulo);
  border-color: var(--prc-c-linea-celda) var(--prc-c-linea-celda) var(--prc-c-fondo);
}

/* --- la línea de recuento ------------------------------------------------ */

.prc-recuento {
  font-size: var(--prc-t-base);
  line-height: var(--prc-i-base);
  color: var(--prc-c-texto);
  margin: 0;
  padding: 10px 0;
}

.prc-recuento strong { font-weight: var(--prc-p-fuerte); }

/* --- la tabla de dos niveles --------------------------------------------- */

/* Quince columnas apretadas no caben en un móvil: por debajo de 1000px la
   caja se desplaza en horizontal, y por debajo de 720px `prc-app.css` la
   convierte en tarjetas. */
.prc-tabla--taller { min-width: 1000px; table-layout: fixed; }

.prc-tabla--taller thead th {
  font-size: var(--prc-t-tabla);
  line-height: var(--prc-i-tabla);
  font-weight: var(--prc-p-fuerte);
  padding: 3px;
  vertical-align: bottom;
  white-space: normal;
  border-color: rgba(255, 255, 255, .25);
}

/* Las seis van acotadas a la tabla del taller, y no sueltas por su clase, por
   una razón de peso: el armazón pinta `.prc-tabla th { background: transparent }`,
   que con su elemento pesa más que una clase suelta y dejaba la cabecera en
   blanco. Con la tabla delante pesan más que él y la banda granate sale. */
.prc-tabla--taller .prc-tabla__grupo { color: var(--prc-g-texto); }
.prc-tabla--taller .prc-tabla__grupo--1 { background: var(--prc-g1); }
.prc-tabla--taller .prc-tabla__grupo--2 { background: var(--prc-g2); }
.prc-tabla--taller .prc-tabla__grupo--3 { background: var(--prc-g3); }

/* La segunda fila lleva el texto en el granate oscuro del tercer grupo y no
   en blanco: sobre el rosa del original, el blanco da 1,99:1 y este 8,39:1,
   sin tocar la paleta que la gente reconoce. */
.prc-tabla--taller .prc-th--a { background: var(--prc-g1-sub); color: var(--prc-g3); }
.prc-tabla--taller .prc-th--b { background: var(--prc-g2-sub); color: var(--prc-g3); }
.prc-tabla--taller .prc-th--c { background: var(--prc-g3-sub); color: var(--prc-g3); }
.prc-th--centro { text-align: center; }
.prc-th--color { width: 64px; }
.prc-th--gestiona { width: 200px; }

.prc-tabla--taller tbody th,
.prc-tabla--taller tbody td {
  font-size: var(--prc-t-tabla);
  line-height: var(--prc-i-tabla);
  font-weight: 400;
  color: var(--prc-c-tabla);
  border: 1px solid var(--prc-c-linea-celda);
  /* 6px de alto en vez de los 3px del original: con filas de dos renglones,
     3px hace que los de filas contiguas se toquen. */
  padding: 6px 4px;
  vertical-align: middle;
  text-align: left;
  white-space: normal;
}

.prc-tabla--taller .prc-celda-color,
.prc-tabla--taller .prc-celda-num,
.prc-tabla--taller .prc-celda-destinatario,
.prc-tabla--taller .prc-celda-estado,
.prc-tabla--taller .prc-celda-accion { text-align: center; }

.prc-mini-color { display: block; width: 40px; height: 40px; margin: 0 auto; }

/* Acotadas igual que las de la cabecera, y por lo mismo: sueltas las pisan
   las reglas por elemento de `.prc-tabla--taller tbody td`, que van ahí
   arriba y pesan más. */
.prc-tabla--taller .prc-celda-num { font-weight: 500; }
.prc-celda-titulo a { color: var(--prc-c-azul-tabla); }
.prc-celda-estado .prc-state { white-space: normal; }
.prc-celda-estado .prc-state__curso { display: inline-block; padding-top: 2px; }
.prc-tabla--taller .prc-celda-fecha { white-space: nowrap; }

.prc-ambitos { margin: 0; padding-left: 1.1em; }
.prc-ambitos li { line-height: var(--prc-i-tabla); }

/* En tarjetas, la fila deja de ser una rejilla: la cabecera está escondida y
   cada celda se rotula sola. La primera celda es un `th`, y `prc-app.css`
   solo trata los `td`. */
@media (max-width: 720px) {
  .prc-tabla--taller { min-width: 0; table-layout: auto; }
  .prc-tabla--taller tbody th { display: flex; gap: 12px; padding: 6px 0; border: 0; text-align: left; }
  .prc-tabla--taller tbody th::before {
    content: attr(data-rotulo);
    flex: 0 0 8rem;
    font-size: var(--prc-t-menudo);
    font-weight: var(--prc-p-fuerte);
    color: var(--prc-c-tenue);
  }
  .prc-tabla--taller tbody td { border: 0; text-align: left; }
  .prc-tabla--taller .prc-celda-color,
  .prc-tabla--taller .prc-celda-num,
  .prc-tabla--taller .prc-celda-destinatario,
  .prc-tabla--taller .prc-celda-estado,
  .prc-tabla--taller .prc-celda-accion { text-align: left; }
  .prc-mini-color { width: 100%; height: 10px; }
}
',
  'js/prc-app.js' => '/*
 * Lo mínimo que las pantallas del aplicativo no pueden hacer sin guion.
 *
 * Cuatro cosas, y las cuatro son mejora sobre algo que ya funciona sin ellas:
 * si el guion no llega, el formulario se envía igual, el aviso del bloqueo ya
 * viene pintado desde el servidor, el `title` del botón sigue ahí y el botón
 * de publicar hace lo mismo que el interruptor. Nada aquí valida ni autoriza:
 * eso está en el servidor, con su nonce y su comprobación de ProcedureAccess.
 *
 * Delegado en `document`: las pantallas repintan trozos y un `addEventListener`
 * por nodo se quedaría atrás.
 */
( function () {
	\'use strict\';

	/* --- 1. Confirmar antes de lo que no tiene vuelta atrás -------------- */

	/*
	 * `data-prc-confirm="¿Seguro que…?"` en el formulario. Se pregunta al
	 * enviar, que es el único momento en que se pierde algo.
	 *
	 * Tres escalones, y los tres hacen la acción:
	 *   1. Con SweetAlert2 (`snippets/sweetalert.php`), un diálogo en
	 *      castellano cuyo botón dice el verbo —«Marcar como histórico»— y no
	 *      «OK». Atrapa el foco y se cierra con Escape; lo hace la librería.
	 *   2. Sin ella —el CDN no contesta, el SRI no cuadra—, el `confirm()` del
	 *      navegador de siempre.
	 *   3. Sin guion, el botón envía el formulario y la acción se hace.
	 * Nunca se pierde una acción porque una librería no llegara.
	 *
	 * El verbo sale de `data-prc-confirm-ok` si el formulario lo pone y, si no,
	 * del `title` del botón, que ya es la acción escrita porque es lo que lee
	 * quien navega con lector de pantalla.
	 */
	function verbo( form, boton ) {
		return form.getAttribute( \'data-prc-confirm-ok\' ) ||
			( boton && boton.getAttribute( \'title\' ) ) ||
			\'Sí, continuar\';
	}

	document.addEventListener( \'submit\', function ( e ) {
		var form = e.target;
		// La marca la pone el envío que sale del diálogo: se deja pasar.
		if ( ! ( form instanceof HTMLFormElement ) || \'1\' === form.dataset.prcConfirmado ) {
			return;
		}
		var pregunta = form.getAttribute( \'data-prc-confirm\' );
		if ( ! pregunta ) {
			return;
		}

		if ( ! window.Swal ) {
			if ( ! window.confirm( pregunta ) ) {
				e.preventDefault();
			}
			return;
		}

		// SweetAlert2 contesta con una promesa, así que el envío se para y se
		// repite luego; el `confirm()` de arriba, no, y por eso va aparte.
		e.preventDefault();
		var boton = e.submitter || form.querySelector( \'[type="submit"]\' );
		// «¿Marcar «X» como histórico? No hay vuelta atrás.» → titular y detalle.
		var corte = pregunta.indexOf( \'? \' );
		window.Swal.fire( {
			title: -1 === corte ? pregunta : pregunta.slice( 0, corte + 1 ),
			text: -1 === corte ? \'\' : pregunta.slice( corte + 2 ),
			icon: \'warning\',
			showCancelButton: true,
			confirmButtonText: verbo( form, boton ),
			cancelButtonText: \'Cancelar\',
			// Lo que no tiene vuelta atrás no se confirma sin querer.
			focusCancel: true,
			reverseButtons: true,
			// Sin tocar el alto del `html`: la barra de pestañas es pegajosa.
			heightAuto: false,
			// Los botones son los de la página, no los de la librería.
			buttonsStyling: false,
			customClass: {
				confirmButton: \'prc-btn prc-btn-borrar btn\',
				cancelButton: \'prc-btn btn btn-light\'
			}
		} ).then( function ( respuesta ) {
			if ( ! respuesta.isConfirmed ) {
				return;
			}
			form.dataset.prcConfirmado = \'1\';
			if ( form.requestSubmit ) {
				// Con el mismo botón: si algún día lleva `name`, sigue viajando.
				form.requestSubmit( boton || undefined );
			} else {
				form.submit();
			}
		} );
	} );

	/* --- 2. El bloqueo de edición, con el Heartbeat de WordPress --------- */

	/*
	 * `EditLock::render()` pinta un `#prc-edit-lock` con el procedimiento, el
	 * bloqueo que se tiene ahora (`data-lock`) y el nonce para soltarlo. Todo
	 * lo de aquí es el mecanismo NATIVO del escritorio, sin inventar nada
	 * (ADR-0008):
	 *
	 *   - `wp-refresh-post-lock` viaja en cada latido del Heartbeat: renueva el
	 *     bloqueo propio y, si otra persona tomó posesión, contesta con su
	 *     nombre. Es la misma llamada que hace el editor de WordPress, así que
	 *     los dos sitios se enteran el uno del otro.
	 *   - `wp-remove-post-lock` suelta el bloqueo al cerrar la pestaña.
	 *
	 * Sin guion no se pierde nada: el aviso ya viene pintado como
	 * `<dialog open>` desde el servidor cuando el procedimiento está cogido, y
	 * el servidor vuelve a comprobarlo con un 409 antes de escribir. Esto solo
	 * evita el susto de estar media hora escribiendo algo que ya no se puede
	 * guardar.
	 *
	 * En `DOMContentLoaded` porque el guion del aplicativo se imprime en el pie
	 * sin depender de jQuery ni del Heartbeat, y a esas alturas los dos ya
	 * están cargados.
	 */
	document.addEventListener( \'DOMContentLoaded\', function () {
		var caja = document.getElementById( \'prc-edit-lock\' );
		// Sin `data-lock` el bloqueo lo tiene otra persona: no hay nada que
		// renovar, y pedirlo sería pedir la renovación de un bloqueo ajeno.
		if ( ! caja || ! caja.dataset.lock || ! window.jQuery || ! window.wp || ! window.wp.heartbeat ) {
			return;
		}

		var $ = window.jQuery;
		var dialogo = document.getElementById( \'prc-lock-dialog\' );
		var cerradura = caja.dataset.lock;
		var perdido = false;
		var enviando = false;

		// Todo lo de la pantalla menos el propio aviso, que es lo único que
		// tiene que seguir funcionando cuando el bloqueo se pierde.
		function fuera( nodo ) {
			return ! caja.contains( nodo );
		}

		$( document ).on( \'heartbeat-send.prcLock\', function ( e, data ) {
			if ( ! perdido ) {
				data[\'wp-refresh-post-lock\'] = {
					post_id: Number( caja.dataset.postId ),
					lock: cerradura
				};
			}
		} );

		$( document ).on( \'heartbeat-tick.prcLock\', function ( e, data ) {
			var respuesta = data[\'wp-refresh-post-lock\'];
			if ( ! respuesta || perdido ) {
				return;
			}
			if ( respuesta.lock_error ) {
				perdido = true;
				// Congelado, no borrado: lo escrito sigue en pantalla para
				// poder copiarlo antes de tomar posesión o de irse.
				Array.prototype.forEach.call( document.querySelectorAll( \'form\' ), function ( form ) {
					if ( fuera( form ) ) {
						form.inert = true;
					}
				} );
				document.getElementById( \'prc-lock-owner\' ).textContent = respuesta.lock_error.name;
				if ( dialogo.showModal ) {
					dialogo.showModal();
				} else {
					dialogo.setAttribute( \'open\', \'\' );
				}
			} else if ( respuesta.new_lock ) {
				cerradura = respuesta.new_lock;
			}
		} );

		// El aviso no se cierra con Escape: no es una confirmación, es que no
		// se puede editar, y cerrarlo devolvería una pantalla que miente.
		dialogo.addEventListener( \'cancel\', function ( e ) {
			e.preventDefault();
		} );

		// En captura, antes que la confirmación de SweetAlert2: si el bloqueo
		// ya se perdió, el envío no sale ni siquiera al servidor.
		document.addEventListener( \'submit\', function ( e ) {
			if ( perdido && fuera( e.target ) ) {
				e.preventDefault();
				e.stopImmediatePropagation();
			}
		}, true );

		// Como el editor clásico: al enviar NO se suelta el bloqueo, que a
		// partir de ahí es del servidor. Soltarlo aquí volvería a crear con la
		// baliza el bloqueo que el propio guardado acaba de borrar.
		window.addEventListener( \'submit\', function ( e ) {
			if ( fuera( e.target ) && ! e.defaultPrevented ) {
				enviando = true;
			}
		} );

		window.addEventListener( \'pagehide\', function () {
			if ( perdido || enviando || ! navigator.sendBeacon || ! caja.dataset.ajaxUrl ) {
				return;
			}
			var datos = new FormData();
			datos.append( \'action\', \'wp-remove-post-lock\' );
			datos.append( \'_wpnonce\', caja.dataset.releaseNonce );
			datos.append( \'post_ID\', caja.dataset.postId );
			datos.append( \'active_post_lock\', cerradura );
			navigator.sendBeacon( caja.dataset.ajaxUrl, datos );
		} );

		// Una página restaurada del historial trae campos viejos y un bloqueo
		// que ya puede ser de otra persona: se vuelve a preguntar al servidor.
		window.addEventListener( \'pageshow\', function ( e ) {
			if ( e.persisted ) {
				window.location.reload();
			}
		} );

		window.wp.heartbeat.interval( 15 );
	} );

	/* --- 3. Bocadillos en los botones de icono --------------------------- */

	/*
	 * Un botón de icono no dice qué hace. Lo dice su `title`, y el navegador ya
	 * lo enseña al posarse encima: **esto es mejora, no requisito**. Con
	 * Bootstrap cargado se cambia por su bocadillo, que sale antes y se lee
	 * mejor; sin Bootstrap —o sin guion— queda el `title` de siempre.
	 *
	 * El texto de verdad para quien navega con lector de pantalla no es el
	 * `title` sino el `.screen-reader-text` que va dentro del botón.
	 */
	function bocadillos( raiz ) {
		if ( ! window.bootstrap || ! window.bootstrap.Tooltip ) {
			return;
		}
		var nodos = ( raiz || document ).querySelectorAll( \'[data-bs-toggle="tooltip"]\' );
		Array.prototype.forEach.call( nodos, function ( nodo ) {
			if ( ! window.bootstrap.Tooltip.getInstance( nodo ) ) {
				new window.bootstrap.Tooltip( nodo );
			}
		} );
	}
	document.addEventListener( \'DOMContentLoaded\', function () {
		bocadillos( document );
	} );

	/* --- 4. El interruptor de publicación -------------------------------- */

	/*
	 * `data-prc-switch` en la casilla. Al cambiarla se envía su formulario, que
	 * es lo que se espera de un interruptor: se toca y pasa algo.
	 *
	 * El botón de al lado hace lo mismo y es el que queda sin guion; con guion
	 * se esconde, porque teniendo el interruptor sobra. Se esconde **desde
	 * aquí** y no en el CSS a propósito: si el guion no llega, el botón se ve.
	 */
	document.addEventListener( \'change\', function ( e ) {
		var casilla = e.target.closest ? e.target.closest( \'[data-prc-switch]\' ) : null;
		if ( ! casilla ) {
			return;
		}
		var form = casilla.form;
		if ( form ) {
			casilla.disabled = true;
			form.submit();
		}
	} );
	document.addEventListener( \'DOMContentLoaded\', function () {
		var botones = document.querySelectorAll( \'.prc-switch-boton\' );
		Array.prototype.forEach.call( botones, function ( boton ) {
			boton.hidden = true;
		} );
	} );
}() );
',
  'js/prc-ficha.js' => '/*
 * Lo que la ficha de un procedimiento no puede hacer sin guion, y solo eso.
 *
 * Las dos cosas son mejora sobre algo que ya funciona sin ellas: el servidor
 * pinta las cuatro cifras del contador con el tiempo que quedaba al generar la
 * página —sin guion se ve bien, solo se queda quieto— y los paneles de
 * contenido salen todos visibles, cada uno con su rótulo, con la barra como
 * una lista de anclas de verdad. Aquí la barra se asciende a pestañas.
 */
( function () {
	\'use strict\';

	/* --- 1. El contador regresivo ---------------------------------------- */

	/*
	 * El instante final llega en `data-fin` como ISO 8601 con zona: el último
	 * día del plazo a las 23:59:00 donde esté el sitio, no donde esté quien
	 * mira. Al llegar a cero se para el reloj; el estado del procedimiento no
	 * lo decide esto, que se deriva en el servidor y por día.
	 */
	function cuenta( caja ) {
		var fin = Date.parse( caja.getAttribute( \'data-fin\' ) );
		if ( isNaN( fin ) ) {
			return;
		}
		var celdas = {};
		Array.prototype.forEach.call( caja.querySelectorAll( \'[data-prc-cuenta]\' ), function ( nodo ) {
			celdas[ nodo.getAttribute( \'data-prc-cuenta\' ) ] = nodo;
		} );

		function escribe( clave, valor, digitos ) {
			if ( celdas[ clave ] ) {
				celdas[ clave ].textContent = String( valor ).padStart( digitos, \'0\' );
			}
		}

		function pinta() {
			var queda = Math.max( 0, Math.floor( ( fin - Date.now() ) / 1000 ) );
			escribe( \'dias\', Math.floor( queda / 86400 ), 3 );
			escribe( \'horas\', Math.floor( queda / 3600 ) % 24, 2 );
			escribe( \'minutos\', Math.floor( queda / 60 ) % 60, 2 );
			escribe( \'segundos\', queda % 60, 2 );
			if ( 0 === queda ) {
				clearInterval( reloj );
			}
		}

		var reloj = setInterval( pinta, 1000 );
		pinta();
	}

	/* --- 2. Las pestañas de contenido ------------------------------------ */

	/*
	 * Sin guion los paneles son secciones seguidas y la barra salta a cada una,
	 * que es marcado correcto y se lee. Con guion pasan a ser pestañas de
	 * verdad: roles, `aria-selected`, un solo panel visible y navegación por
	 * flechas (Inicio y Fin a los extremos), como manda el patrón.
	 *
	 * Los roles se ponen AQUÍ y no en el servidor a propósito: un `role="tab"`
	 * sobre una lista de anclas que no esconde nada le mentiría a quien navega
	 * con lector de pantalla cuando el guion no llega.
	 */
	function pestanas( caja ) {
		var barra = caja.querySelector( \'.prc-tabs-ficha__barra\' );
		if ( ! barra ) {
			return;
		}
		var tabs = Array.prototype.slice.call( barra.querySelectorAll( \'a\' ) );
		var paneles = tabs.map( function ( tab ) {
			return document.getElementById( ( tab.getAttribute( \'href\' ) || \'\' ).slice( 1 ) );
		} );
		if ( ! tabs.length || paneles.indexOf( null ) !== -1 ) {
			return;
		}

		function muestra( activa, foco ) {
			tabs.forEach( function ( tab, i ) {
				tab.setAttribute( \'aria-selected\', i === activa ? \'true\' : \'false\' );
				tab.setAttribute( \'tabindex\', i === activa ? \'0\' : \'-1\' );
				paneles[ i ].hidden = i !== activa;
			} );
			if ( foco ) {
				tabs[ activa ].focus();
			}
		}

		barra.setAttribute( \'role\', \'tablist\' );
		Array.prototype.forEach.call( barra.querySelectorAll( \'li\' ), function ( li ) {
			li.setAttribute( \'role\', \'presentation\' );
		} );

		tabs.forEach( function ( tab, i ) {
			tab.setAttribute( \'role\', \'tab\' );
			tab.setAttribute( \'aria-controls\', paneles[ i ].id );
			paneles[ i ].setAttribute( \'role\', \'tabpanel\' );
			paneles[ i ].setAttribute( \'aria-labelledby\', tab.id );
			// El panel no siempre tiene dentro algo que reciba el foco: sin
			// esto, con teclado no se llega a leerlo.
			paneles[ i ].setAttribute( \'tabindex\', \'0\' );

			tab.addEventListener( \'click\', function ( e ) {
				e.preventDefault();
				muestra( i, true );
			} );

			tab.addEventListener( \'keydown\', function ( e ) {
				var saltos = {
					ArrowLeft: -1,
					ArrowRight: 1,
					Home: -i,
					End: tabs.length - 1 - i
				};
				if ( ! ( e.key in saltos ) ) {
					return;
				}
				e.preventDefault();
				muestra( ( i + saltos[ e.key ] + tabs.length ) % tabs.length, true );
			} );
		} );

		caja.classList.add( \'prc-tabs-ficha--js\' );
		// Con un enlace a un panel concreto se abre ese, no el primero.
		var pedida = paneles.map( function ( panel ) {
			return \'#\' + panel.id;
		} ).indexOf( window.location.hash );
		muestra( pedida === -1 ? 0 : pedida, false );
	}

	document.addEventListener( \'DOMContentLoaded\', function () {
		Array.prototype.forEach.call( document.querySelectorAll( \'.prc-cuenta[data-fin]\' ), cuenta );
		Array.prototype.forEach.call( document.querySelectorAll( \'.prc-tabs-ficha\' ), pestanas );
	} );
}() );
',
) );

if ( \class_exists( \Prc\App::class ) ) {
	\Prc\App::boot();
}

// phpcs:enable
