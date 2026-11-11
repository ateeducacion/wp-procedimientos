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















if ( ! class_exists( 'Prc\Meta\ProcedureMetaKeys', false ) ) {
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







	public const CENTRE_KINDS = 'prc_centre_kinds';




	public const CENTRE_TYPES = 'prc_centre_types';




	public const ISLANDS = 'prc_islands';





	public const ALLOWED_CENTRES = 'prc_allowed_centres';




	public const REQUIRES_COORDINATOR = 'prc_requires_coordinator';


	public const COORDINATOR_COUNT = 'prc_coordinator_count';


	public const COORDINATOR_COUNT_MAX = 10;


	public const CUSTOM_CSS = 'prc_custom_css';
	public const CUSTOM_JS  = 'prc_custom_js';




	public const QUESTIONS = 'prc_questions';





	public const QUESTIONS_SEQ = 'prc_questions_seq';




	public const COMMITMENTS = 'prc_commitments';








	public const ARCHIVED = 'prc_archived';




	public const APPLICATIONS_CLOSED = 'prc_applications_closed';







	public const NOTIFY = 'prc_notify';






	public const PEOPLE_SPEC = 'prc_people_spec';





	public const OBSERVABLE = 'prc_observable';







	public const OBSERVER_COLUMNS = 'prc_observer_columns';




	public const OBSERVER_FILES = 'prc_observer_files';




	public const OBSERVER_EXPORT = 'prc_observer_export';





	public const OBSERVER_NOTIFY = 'prc_observer_notify';

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

	public const KIND_SCHOOL      = 'school';
	public const KIND_RURAL_GROUP = 'rural_group';




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
			self::CENTRE_KINDS,
			self::CENTRE_TYPES,
			self::ISLANDS,
			self::ALLOWED_CENTRES,
			self::REQUIRES_COORDINATOR,
			self::COORDINATOR_COUNT,
			self::CUSTOM_CSS,
			self::CUSTOM_JS,
			self::QUESTIONS,
			self::QUESTIONS_SEQ,
			self::COMMITMENTS,
			self::ARCHIVED,
			self::APPLICATIONS_CLOSED,
			self::NOTIFY,
			self::PEOPLE_SPEC,
			self::OBSERVABLE,
			self::OBSERVER_COLUMNS,
			self::OBSERVER_FILES,
			self::OBSERVER_EXPORT,
			self::OBSERVER_NOTIFY,
		);
	}










	public static function notify_events(): array {
		return array(
			'application_submitted' => 'Acuse a quien presenta una solicitud nueva',
			'application_updated'   => 'Aviso a quien modifica su solicitud',
			'application_received'  => 'Aviso a los correos de contacto de cada solicitud recibida o modificada',
			'review_changed'        => 'Aviso a quien solicitó cuando cambia el estado de su revisión',
			'delivery_received'     => 'Aviso de cada documento entregado con plazo propio, a quien lo entrega y a los contactos',
			'people_changed'        => 'Aviso a los correos de contacto de cada alta, cambio o baja en la lista de personas de una solicitud',
			'application_withdrawn' => 'Aviso a los correos de contacto, y acuse a quien la retira, cuando un centro retira su solicitud',
			'application_extended'  => 'Aviso a quien presentó la solicitud cuando se le concede una prórroga',
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







	public static function ownership_name( string $ownership ): string {
		$nombres = array(
			self::OWNERSHIP_PUBLIC  => 'Pública',
			self::OWNERSHIP_PRIVATE => 'Privada',
		);
		return $nombres[ $ownership ] ?? '';
	}









	public static function centre_kinds(): array {
		return array(
			self::KIND_SCHOOL      => 'Cada centro educativo, con su código',
			self::KIND_RURAL_GROUP => 'Un CER, como colectivo y con su código',
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

}








namespace Prc\Meta;












if ( ! class_exists( 'Prc\Meta\ApplicationMetaKeys', false ) ) {
final class ApplicationMetaKeys {

	public const APPLICANT_TYPE = 'prc_applicant_type';
	public const TYPE_CENTRE    = 'centre';
	public const TYPE_TEACHER   = 'teacher';




	public const CENTRE_CODE = 'prc_centre_code';




	public const CENTRE_NAME = 'prc_centre_name';




	public const APPLICANT_POSITION = 'prc_applicant_position';




	public const COORDINATOR = 'prc_coordinator';




	public const ANSWERS = 'prc_answers';










	public const FILES = 'prc_files';




	public const REVIEW_STATE = 'prc_review_state';




	public const REVIEW_NOTE = 'prc_review_note';






	public const PEOPLE = 'prc_people';






	public const LOG = 'prc_log';






	public const LATE_UNTIL = 'prc_late_until';





	public const INTERNAL_NOTE = 'prc_internal_note';





	public const CREATED_BY = 'prc_created_by';

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
			self::APPLICANT_TYPE,
			self::CENTRE_CODE,
			self::CENTRE_NAME,
			self::APPLICANT_POSITION,
			self::COORDINATOR,
			self::ANSWERS,
			self::FILES,
			self::REVIEW_STATE,
			self::REVIEW_NOTE,
			self::PEOPLE,
			self::LOG,
			self::LATE_UNTIL,
			self::INTERNAL_NOTE,
			self::CREATED_BY,
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

}








namespace Prc\Domain;






















if ( ! class_exists( 'Prc\Domain\DateRange', false ) ) {
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

}








namespace Prc\Domain;

use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;













if ( ! class_exists( 'Prc\Domain\ProcedureState', false ) ) {
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
		if ( ! empty( $meta[ ProcedureMetaKeys::APPLICATIONS_CLOSED ] ) ) {
			return ProcedureMetaKeys::STATE_CLOSED;
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








	public static function label( string $state, bool $manually_closed = false ): string {
		if ( ProcedureMetaKeys::STATE_CLOSED === $state && $manually_closed ) {
			return 'Cerrado manualmente';
		}
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
			ProcedureMetaKeys::STATE_CLOSED    => 'Las solicitudes están cerradas: solo se pueden cambiar fechas y enlaces. '
				. 'Las entregas documentales conservan sus propios plazos.',
			ProcedureMetaKeys::STATE_AMENDMENT => 'La subsanación está en marcha: el procedimiento ya no se edita, para no mover el suelo a quien está subsanando. Las solicitudes se siguen gestionando.',
			ProcedureMetaKeys::STATE_RESOLVED  => 'El procedimiento está resuelto: solo se pueden cambiar los enlaces.',
		);
		return (string) ( $textos[ $state ] ?? '' );
	}











	public static function why_not_archivable( string $state ): string {
		if ( in_array( $state, array( ProcedureMetaKeys::STATE_CLOSED, ProcedureMetaKeys::STATE_RESOLVED ), true ) ) {
			return '';
		}
		return ProcedureMetaKeys::STATE_ARCHIVED === $state
			? 'Este procedimiento ya está marcado como histórico.'
			: 'Solo se marca como histórico un procedimiento cerrado o resuelto: este sigue vivo.';
	}










	public static function accepts_applications( string $state ): bool {
		return ProcedureMetaKeys::STATE_OPEN === $state;
	}















	public static function application_window( string $state, string $review_state, bool $manually_closed = false, bool $late = false ): string {
		if ( $late || self::accepts_applications( $state ) ) {
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
		if ( ProcedureMetaKeys::STATE_CLOSED === $state && $manually_closed ) {
			return 'La inscripción se ha cerrado manualmente, antes de la fecha prevista.';
		}
		return 'Las solicitudes están cerradas.';
	}















	public static function late_open( string $state, string $late_until, string $today ): bool {
		if ( in_array( $state, array( ProcedureMetaKeys::STATE_ARCHIVED, ProcedureMetaKeys::STATE_DRAFT ), true ) ) {
			return false;
		}
		$late_until = trim( $late_until );
		return 1 === preg_match( '/^\d{4}-\d{2}-\d{2}$/', $late_until ) && trim( $today ) <= $late_until;
	}








	private static function text( array $meta, string $key ): string {
		$value = $meta[ $key ] ?? '';
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}
}

}








namespace Prc\Domain;


















if ( ! class_exists( 'Prc\Domain\QuestionPoints', false ) ) {
final class QuestionPoints {














	public static function choices( string $type, array $raw ): array {
		$valor = $raw['choices'] ?? array();
		if ( ProcedureQuestions::TYPE_MULTIPLE !== $type ) {
			return array( 'choices' => ProcedureQuestions::choices( $valor ) );
		}

		$con    = isset( $raw['points'] ) && is_array( $raw['points'] );
		$textos = array();
		$valen  = array();
		foreach ( self::items( $valor, $con ? array_values( $raw['points'] ) : array() ) as $una ) {
			$con = $con || $una[2];
			if ( '' === $una[0] || in_array( $una[0], $textos, true ) || count( $textos ) >= ProcedureQuestions::MAX_CHOICES ) {
				continue;
			}
			$textos[] = $una[0];
			$valen[]  = $una[1];
		}
		if ( ! $con || array() === array_filter( $valen ) ) {
			return array( 'choices' => $textos );
		}
		return array(
			'choices' => $textos,
			'points'  => $valen,
		);
	}








	public static function answer( array $question, $value ): array {
		$opciones = (array) ( $question['choices'] ?? array() );
		$obliga   = ! empty( $question['required'] );
		if ( ProcedureQuestions::TYPE_SINGLE === ( $question['type'] ?? '' ) ) {
			$elegida = is_scalar( $value ) ? trim( (string) $value ) : '';
			$elegida = in_array( $elegida, $opciones, true ) ? $elegida : '';
			return array( $elegida, $obliga && '' === $elegida );
		}
		$limpias = array();
		foreach ( is_array( $value ) ? $value : array() as $una ) {
			$una = is_scalar( $una ) ? trim( (string) $una ) : '';
			if ( in_array( $una, $opciones, true ) && ! in_array( $una, $limpias, true ) ) {
				$limpias[] = $una;
			}
		}
		return array( $limpias, $obliga && array() === $limpias );
	}








	private static function items( $value, array $points ): array {
		$texto  = is_string( $value );
		$lineas = $texto ? preg_split( '/\r\n|\r|\n/', $value ) : $value;
		$out    = array();
		foreach ( is_array( $lineas ) ? array_values( $lineas ) : array() as $i => $linea ) {
			if ( ! is_scalar( $linea ) ) {
				continue;
			}
			$valor = $points[ $i ] ?? 0;
			$out[] = $texto ? self::split( trim( (string) $linea ) ) : array( trim( (string) $linea ), is_scalar( $valor ) ? (int) $valor : 0, false );
		}
		return $out;
	}







	private static function split( string $line ): array {
		if ( preg_match( '/^(.*?)\s*\|\s*(\d{1,6})$/u', $line, $partes ) && '' !== trim( $partes[1] ) ) {
			return array( trim( $partes[1] ), (int) $partes[2], true );
		}
		return array( $line, 0, false );
	}












	public static function lines( array $question ): array {
		$textos = array_map( 'strval', (array) ( $question['choices'] ?? array() ) );
		$puntos = isset( $question['points'] ) && is_array( $question['points'] ) ? $question['points'] : null;
		if ( null === $puntos ) {
			$barra = array_filter(
				$textos,
				function ( string $texto ): bool {
					return false !== strpos( $texto, '|' );
				}
			);
			if ( array() === $barra ) {
				return $textos;
			}
			$puntos = array();
		}
		$out = array();
		foreach ( $textos as $i => $texto ) {
			$out[] = $texto . ' | ' . (int) ( $puntos[ $i ] ?? 0 );
		}
		return $out;
	}







	public static function has_points( array $questions ): bool {
		foreach ( $questions as $pregunta ) {
			if ( ProcedureQuestions::TYPE_MULTIPLE === ( $pregunta['type'] ?? '' ) && isset( $pregunta['points'] ) ) {
				return true;
			}
		}
		return false;
	}










	public static function score( array $questions, array $answers ): ?int {
		if ( ! self::has_points( $questions ) ) {
			return null;
		}
		$total = 0;
		foreach ( $questions as $pregunta ) {
			$vale = self::values( $pregunta );
			foreach ( (array) ( $answers[ (string) ( $pregunta['key'] ?? '' ) ] ?? array() ) as $marcada ) {
				$total += is_scalar( $marcada ) ? (int) ( $vale[ (string) $marcada ] ?? 0 ) : 0;
			}
		}
		return $total;
	}







	public static function heading( array $questions ): array {
		return self::has_points( $questions ) ? array( 'score' => 'Puntos' ) : array();
	}








	public static function cell( array $questions, array $answers ): array {
		$puntos = self::score( $questions, $answers );
		return null === $puntos ? array() : array( 'score' => (string) $puntos );
	}







	private static function values( array $question ): array {
		$choices = array_map( 'strval', (array) ( $question['choices'] ?? array() ) );
		$points  = array_map( 'intval', (array) ( $question['points'] ?? array() ) );
		if ( ProcedureQuestions::TYPE_MULTIPLE !== ( $question['type'] ?? '' ) || count( $choices ) !== count( $points ) ) {
			return array();
		}
		return array_combine( $choices, $points );
	}
}

}








namespace Prc\Domain;















if ( ! class_exists( 'Prc\Domain\QuestionGrid', false ) ) {
final class QuestionGrid {







	public static function is_row_key( string $key ): bool {
		return (bool) preg_match( '/^r[1-9]\d{0,3}$/', $key );
	}











	public static function rows( $raw, int $seq ): array {
		if ( is_string( $raw ) ) {
			$raw = preg_split( '/\r\n|\r|\n/', $raw );
		}
		$filas = array();
		$mayor = max( 0, $seq );
		foreach ( is_array( $raw ) ? $raw : array() as $item ) {
			$fila = self::row( $item );
			if ( '' === $fila['label'] || in_array( $fila['label'], array_column( $filas, 'label' ), true ) || count( $filas ) >= ProcedureQuestions::MAX_ROWS ) {
				continue;
			}
			if ( in_array( $fila['key'], array_column( $filas, 'key' ), true ) ) {
				$fila['key'] = '';
			}
			$mayor   = max( $mayor, (int) substr( $fila['key'], 1 ) );
			$filas[] = $fila;
		}
		foreach ( $filas as $i => $fila ) {
			if ( '' === $fila['key'] ) {
				++$mayor;
				$filas[ $i ]['key'] = 'r' . $mayor;
			}
		}
		return array(
			'rows'    => $filas,
			'row_seq' => $mayor,
		);
	}







	private static function row( $item ): array {
		if ( ! is_array( $item ) ) {
			return array(
				'key'   => '',
				'label' => is_scalar( $item ) ? trim( (string) $item ) : '',
			);
		}
		$key = is_scalar( $item['key'] ?? null ) ? strtolower( trim( (string) $item['key'] ) ) : '';
		return array(
			'key'   => self::is_row_key( $key ) ? $key : '',
			'label' => is_scalar( $item['label'] ?? null ) ? trim( (string) $item['label'] ) : '',
		);
	}

















	public static function match_rows( string $text, array $previous ): array {
		$lineas = array_map( 'trim', (array) preg_split( '/\r\n|\r|\n/', $text ) );
		$antes  = array_values( array_filter( $previous, 'is_array' ) );
		$claves = self::same_text( $lineas, $antes );
		$usadas = array_flip( array_filter( $claves, 'strlen' ) );



		$sitio       = array_flip( array_column( $antes, 'key' ) );
		$en_su_sitio = count( $lineas ) >= count( $antes );
		foreach ( $claves as $i => $key ) {
			$en_su_sitio = $en_su_sitio && ( $sitio[ $key ] ?? -1 ) === $i;
		}
		$out = array();
		foreach ( $lineas as $i => $linea ) {
			if ( '' === $linea ) {
				continue;
			}
			$suya = (string) ( $antes[ $i ]['key'] ?? '' );
			if ( ! isset( $claves[ $i ] ) && $en_su_sitio && '' !== $suya && ! isset( $usadas[ $suya ] ) ) {
				$claves[ $i ]    = $suya;
				$usadas[ $suya ] = true;
			}
			$out[] = array(
				'key'   => $claves[ $i ] ?? '',
				'label' => $linea,
			);
		}
		return $out;
	}








	private static function same_text( array $lines, array $previous ): array {
		$libres = array_column( $previous, 'label', 'key' );
		$out    = array();
		foreach ( $lines as $i => $linea ) {
			$key = '' !== $linea ? array_search( $linea, $libres, true ) : false;
			if ( false !== $key ) {
				$out[ $i ] = (string) $key;
				unset( $libres[ $key ] );
			}
		}
		return $out;
	}











	public static function answer( array $question, $value ): array {
		$dadas  = is_array( $value ) ? $value : array();
		$mal    = ! is_array( $value ) && null !== $value && '' !== $value;
		$obliga = ! empty( $question['required'] );
		$out    = array();
		foreach ( (array) ( $question['rows'] ?? array() ) as $fila ) {
			$rk         = (string) $fila['key'];
			$dada       = $dadas[ $rk ] ?? null;
			$texto      = is_scalar( $dada ) ? trim( (string) $dada ) : '';
			$n          = ProcedureQuestions::integer( $texto );
			$bien       = null !== $n && $n >= 0;
			$out[ $rk ] = $bien ? $n : $texto;

			$mal = $mal || is_array( $dada ) || ( '' === $texto ? $obliga : ! $bien );
		}
		return array( $out, $mal );
	}








	public static function text( array $question, $answer ): string {
		$dadas = is_array( $answer ) ? $answer : array();
		$out   = array();
		foreach ( (array) ( $question['rows'] ?? array() ) as $fila ) {
			$valor = $dadas[ (string) $fila['key'] ] ?? '';
			if ( is_scalar( $valor ) && '' !== (string) $valor ) {
				$out[] = (string) $fila['label'] . ': ' . (string) $valor;
			}
		}
		return implode( '; ', $out );
	}







	public static function columns( array $question ): array {
		$out = array();
		if ( ProcedureQuestions::TYPE_GRID !== ( $question['type'] ?? '' ) ) {
			return $out;
		}
		foreach ( (array) ( $question['rows'] ?? array() ) as $fila ) {
			$out[ (string) $question['key'] . ':' . (string) $fila['key'] ] = (string) $question['label'] . ' — ' . (string) $fila['label'];
		}
		return $out;
	}












	public static function expand( array $columns, array $questions ): array {
		$rejillas = array();
		foreach ( $questions as $pregunta ) {
			$rejillas[ (string) ( $pregunta['key'] ?? '' ) ] = self::columns( $pregunta );
		}
		$out = array();
		foreach ( $columns as $clave => $rotulo ) {
			$out += array() !== ( $rejillas[ $clave ] ?? array() ) ? $rejillas[ $clave ] : array( $clave => $rotulo );
		}
		return $out;
	}








	public static function cells( array $question, $answer ): array {
		$dadas = is_array( $answer ) ? $answer : array();
		$out   = array();
		foreach ( (array) ( $question['rows'] ?? array() ) as $fila ) {
			$valor = $dadas[ (string) $fila['key'] ] ?? '';
			$out[ (string) $question['key'] . ':' . (string) $fila['key'] ] = is_scalar( $valor ) ? (string) $valor : '';
		}
		return $out;
	}
}

}








namespace Prc\Domain;



















if ( ! class_exists( 'Prc\Domain\ProcedureQuestions', false ) ) {
final class ProcedureQuestions {




	public const MAX = 40;




	public const MAX_ROWS = 30;




	public const MAX_CHOICES = 50;




	public const TEXT_MAX = 2000;

	public const TYPE_TEXT     = 'text';
	public const TYPE_SINGLE   = 'single';
	public const TYPE_MULTIPLE = 'multiple';
	public const TYPE_YESNO    = 'yesno';












	public const TYPE_FILE = 'file';




	public const TYPE_NUMBER = 'number';




	public const TYPE_DATE = 'date';




	public const TYPE_GRID = 'grid';




	private const INT_RE = '/^-?\d{1,9}$/';






	public static function types(): array {
		return array(
			self::TYPE_TEXT     => 'Respuesta libre',
			self::TYPE_SINGLE   => 'Una opción',
			self::TYPE_MULTIPLE => 'Varias opciones',
			self::TYPE_YESNO    => 'Sí o no',
			self::TYPE_FILE     => 'Archivo',
			self::TYPE_NUMBER   => 'Número',
			self::TYPE_DATE     => 'Fecha',
			self::TYPE_GRID     => 'Cantidades por fila',
		);
	}







	public static function has_choices( string $type ): bool {
		return self::TYPE_SINGLE === $type || self::TYPE_MULTIPLE === $type;
	}







	public static function is_key( string $key ): bool {
		return (bool) preg_match( '/^q[1-9]\d{0,3}$/', $key );
	}







	public static function highest_key( array $keys ): int {
		$mayor = 0;
		foreach ( array_filter( array_map( 'strval', $keys ), array( self::class, 'is_key' ) ) as $clave ) {
			$mayor = max( $mayor, (int) substr( $clave, 1 ) );
		}
		return $mayor;
	}










	public static function is_answer( $value ): bool {
		if ( is_array( $value ) ) {
			return array() !== array_filter( $value, array( self::class, 'is_answer' ) );
		}
		return '' !== $value && null !== $value;
	}






















	public static function sanitize( $raw, int &$seq = 0 ): array {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$out    = array();
		$vistas = array();
		$mayor  = max( 0, $seq );
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
		$seq = $mayor;
		return $out;
	}







	private static function one( array $raw ): array {
		$type = isset( $raw['type'] ) && is_scalar( $raw['type'] ) ? (string) $raw['type'] : '';
		if ( ! isset( self::types()[ $type ] ) ) {
			$type = self::TYPE_TEXT;
		}
		$key = isset( $raw['key'] ) && is_scalar( $raw['key'] ) ? strtolower( trim( (string) $raw['key'] ) ) : '';

		$question = array(
			'key'      => self::is_key( $key ) ? $key : '',
			'label'    => self::text( $raw, 'label' ),
			'help'     => self::text( $raw, 'help' ),
			'type'     => $type,
			'required' => ! empty( $raw['required'] ),
			'choices'  => array(),
		);
		if ( self::has_choices( $type ) ) {
			$question = array_merge( $question, QuestionPoints::choices( $type, $raw ) );
		}
		if ( self::TYPE_FILE === $type && ! empty( $raw['delivery'] ) ) {
			$question['delivery']  = true;
			$question['opens_at']  = self::text( $raw, 'opens_at' );
			$question['closes_at'] = self::text( $raw, 'closes_at' );
		}
		if ( self::TYPE_NUMBER === $type ) {
			$question['min'] = self::integer( $raw['min'] ?? null );
			$question['max'] = self::integer( $raw['max'] ?? null );
		}
		if ( self::TYPE_GRID === $type ) {
			$question += QuestionGrid::rows( $raw['rows'] ?? array(), (int) ( $raw['row_seq'] ?? 0 ) );
		}
		return $question;
	}







	public static function integer( $value ): ?int {
		if ( is_int( $value ) ) {
			return $value;
		}
		$texto = is_string( $value ) ? trim( $value ) : '';
		return preg_match( self::INT_RE, $texto ) ? (int) $texto : null;
	}







	public static function is_delivery( array $question ): bool {
		return self::TYPE_FILE === ( $question['type'] ?? '' ) && ! empty( $question['delivery'] );
	}







	public static function valid_delivery( array $question ): bool {
		$opens  = self::text( $question, 'opens_at' );
		$closes = self::text( $question, 'closes_at' );
		return self::is_delivery( $question ) && ProcedureInput::is_valid_date( $opens )
			&& ProcedureInput::is_valid_date( $closes ) && $opens <= $closes;
	}








	public static function delivery_is_open( array $question, string $today ): bool {
		return self::valid_delivery( $question ) && ProcedureInput::is_valid_date( $today )
			&& $question['opens_at'] <= $today && $today <= $question['closes_at'];
	}







	public static function application_questions( array $questions ): array {
		return array_values(
			array_filter(
				$questions,
				static function ( array $question ): bool {
					return ! self::is_delivery( $question );
				}
			)
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
			$key = (string) ( $pregunta['key'] ?? '' );





			if ( self::TYPE_FILE === ( $pregunta['type'] ?? '' ) ) {
				continue;
			}

			list( $datos[ $key ], $mal ) = self::answer( $pregunta, $answers[ $key ] ?? null );
			if ( $mal ) {
				$errores[] = $key;
			}
		}

		return array(
			'ok'     => array() === $errores,
			'errors' => $errores,
			'data'   => $datos,
		);
	}








	private static function answer( array $question, $value ): array {
		$tipo = (string) ( $question['type'] ?? self::TYPE_TEXT );
		if ( self::has_choices( $tipo ) ) {
			return QuestionPoints::answer( $question, $value );
		}
		if ( self::TYPE_NUMBER === $tipo ) {
			return self::number_answer( $question, $value );
		}
		if ( self::TYPE_GRID === $tipo ) {
			return QuestionGrid::answer( $question, $value );
		}
		$obliga = ! empty( $question['required'] );
		if ( self::TYPE_YESNO === $tipo ) {
			$si = self::yes( $value );
			return array( $si, $obliga && ! $si );
		}
		$texto = is_scalar( $value ) ? trim( (string) $value ) : '';
		if ( self::TYPE_DATE === $tipo ) {
			return array( $texto, '' === $texto ? $obliga : ! ProcedureInput::is_valid_date( $texto ) );
		}
		$texto = mb_substr( $texto, 0, self::TEXT_MAX );
		return array( $texto, $obliga && '' === $texto );
	}












	private static function number_answer( array $question, $value ): array {
		$texto = is_scalar( $value ) ? trim( (string) $value ) : '';
		if ( '' === $texto ) {
			return array( '', ! empty( $question['required'] ) );
		}
		$numero = self::integer( $texto );
		$minimo = self::integer( $question['min'] ?? null ) ?? 0;
		$maximo = self::integer( $question['max'] ?? null );
		if ( null === $numero || $numero < $minimo || ( null !== $maximo && $numero > $maximo ) ) {
			return array( $texto, true );
		}
		return array( $numero, false );
	}








	public static function as_text( array $question, $answer ): string {
		$tipo = $question['type'] ?? '';
		if ( self::TYPE_YESNO === $tipo ) {
			return self::yes( $answer ) ? 'Sí' : 'No';
		}
		if ( self::TYPE_GRID === $tipo ) {
			return QuestionGrid::text( $question, $answer );
		}
		if ( self::TYPE_DATE === $tipo && is_string( $answer ) && ProcedureInput::is_valid_date( $answer ) ) {
			return implode( '/', array_reverse( explode( '-', $answer ) ) );
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

}








namespace Prc\Domain;










if ( ! class_exists( 'Prc\Domain\QuestionChanges', false ) ) {
final class QuestionChanges {

	public const MOVE_UP   = 'up';
	public const MOVE_DOWN = 'down';











	public static function move( array $questions, string $key, string $direction ): array {
		$desde = array_search( $key, array_column( $questions, 'key' ), true );
		$paso  = array(
			self::MOVE_UP   => -1,
			self::MOVE_DOWN => 1,
		)[ $direction ] ?? 0;
		$lista = array_values( $questions );
		if ( false === $desde || 0 === $paso || ! isset( $lista[ $desde + $paso ] ) ) {
			return $questions;
		}
		$otra                    = $lista[ $desde + $paso ];
		$lista[ $desde + $paso ] = $lista[ $desde ];
		$lista[ $desde ]         = $otra;
		return $lista;
	}














	public static function refusal( array $before, array $after, array $answered ): string {
		$tipos     = array_column( $before, 'type', 'key' );
		$cambiadas = array();
		foreach ( $after as $pregunta ) {
			$key = (string) ( $pregunta['key'] ?? '' );

			if ( isset( $pregunta['max'] ) && ( $pregunta['min'] ?? 0 ) > $pregunta['max'] ) {
				return 'En «' . (string) $pregunta['label'] . '» el mínimo es mayor que el máximo. No se han guardado las preguntas.';
			}
			if ( isset( $tipos[ $key ] ) && $tipos[ $key ] !== $pregunta['type'] && ! empty( $answered[ $key ] ) ) {
				$cambiadas[] = '«' . (string) $pregunta['label'] . '»';
			}
		}
		if ( array() === $cambiadas ) {
			return '';
		}
		return 'No se puede cambiar el tipo de ' . implode( ', ', $cambiadas )
			. ': ya hay solicitudes que la han contestado. Si necesita otro tipo, cree una pregunta nueva. No se han guardado las preguntas.';
	}



















	public static function input_errors( array $raw, array $before = array() ): string {
		$guardadas = array_column( $before, null, 'key' );
		foreach ( $raw as $pregunta ) {
			if ( '' === trim( (string) ( $pregunta['label'] ?? '' ) ) ) {
				continue;
			}
			$rotulo = '«' . (string) ( $pregunta['label'] ?? '' ) . '»';
			$tipo   = (string) ( $pregunta['type'] ?? '' );
			$antes  = $guardadas[ (string) ( $pregunta['key'] ?? '' ) ] ?? array();
			$mal    = ProcedureQuestions::TYPE_MULTIPLE === $tipo ? self::bad_points( $pregunta['choices'] ?? '', self::known_lines( $antes ) ) : '';
			if ( '' !== $mal ) {
				return 'En ' . $rotulo . ' la opción «' . $mal . '» lleva unos puntos que no son un número entero de 0 en adelante. No se han guardado las preguntas.';
			}
			foreach ( array(
				'min' => 'mínimo',
				'max' => 'máximo',
			) as $limite => $nombre ) {
				$valor = is_scalar( $pregunta[ $limite ] ?? null ) ? trim( (string) $pregunta[ $limite ] ) : '';
				if ( ProcedureQuestions::TYPE_NUMBER === $tipo && '' !== $valor && null === ProcedureQuestions::integer( $valor ) ) {
					return 'En ' . $rotulo . ' el ' . $nombre . ' tiene que ser un número entero. No se han guardado las preguntas.';
				}
			}
		}
		return '';
	}







	private static function known_lines( array $question ): array {
		if ( array() === $question ) {
			return array();
		}
		return array_merge( array_map( 'strval', (array) ( $question['choices'] ?? array() ) ), QuestionPoints::lines( $question ) );
	}








	private static function bad_points( $choices, array $known ): string {
		foreach ( is_string( $choices ) ? (array) preg_split( '/\r\n|\r|\n/', $choices ) : array() as $linea ) {
			$linea = trim( (string) $linea );
			$cola  = trim( (string) substr( (string) strrchr( $linea, '|' ), 1 ) );
			if ( in_array( $linea, $known, true ) ) {
				continue;
			}
			if ( false !== strpos( $linea, '|' ) && preg_match( '/^[-+]?[\d.,]*\d[\d.,]*$/', $cola ) && ! preg_match( '/^\d{1,6}$/', $cola ) ) {
				return $linea;
			}
		}
		return '';
	}
}

}








namespace Prc\Domain;

use Prc\Meta\ProcedureMetaKeys;











if ( ! class_exists( 'Prc\Domain\ProcedureInput', false ) ) {
final class ProcedureInput {











	public static function validate( array $raw ): array {
		$errors                = array();
		$raw_coordinator_count = $raw['coordinator_count'] ?? ( ! empty( $raw['requires_coordinator'] ) ? 1 : 0 );
		$coordinator_count     = is_scalar( $raw_coordinator_count ) && false !== filter_var( $raw_coordinator_count, FILTER_VALIDATE_INT ) ? (int) $raw_coordinator_count : -1;
		if ( $coordinator_count < 0 || $coordinator_count > ProcedureMetaKeys::COORDINATOR_COUNT_MAX ) {
			$errors[]          = 'coordinator_count';
			$coordinator_count = 0;
		}

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
		$kinds        = ProcedureMetaKeys::AUDIENCE_SCHOOLS === $audience ? self::centre_kinds( $raw['centre_kinds'] ?? array() ) : array();
		$ownership    = ProcedureMetaKeys::AUDIENCE_SCHOOLS === $audience && in_array( ProcedureMetaKeys::KIND_SCHOOL, $kinds, true )
			? self::ownership( $raw['ownership'] ?? array() )
			: array();
		$urls         = array();
		$acotados     = CentreEligibility::input( $raw, ProcedureMetaKeys::AUDIENCE_SCHOOLS === $audience );
		$errors       = array_merge( $errors, $acotados['errors'] );

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
		if ( ProcedureMetaKeys::AUDIENCE_SCHOOLS === $audience && in_array( ProcedureMetaKeys::KIND_SCHOOL, $kinds, true ) && array() === $ownership ) {
			$errors[] = 'ownership';
		}
		if ( ProcedureMetaKeys::AUDIENCE_SCHOOLS === $audience && array() === $kinds ) {
			$errors[] = 'centre_kinds';
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
					'centre_kinds'         => $kinds,
					'coordinator_count'    => $coordinator_count,
					'requires_coordinator' => $coordinator_count > 0,
					'commitments'          => self::text( $raw, 'commitments' ),
				),
				$urls,
				$acotados['data']
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
			'ownership'            => 'a qué centros se dirige («Dirigido a»): públicos, privados y concertados, o los dos',
			'centre_kinds'         => 'quién puede presentar solicitud: los centros, un CER como colectivo o ambos',
			'allowed_centres'      => 'la lista cerrada de centros: cada código, de 3 a 20 letras o cifras, en su línea o separado por comas, y no más de 2000',
			'coordinator_count'    => 'cuántas personas coordinadoras se piden',
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



















	public static function not_ready( int $course, array $questions, string $opens_at ): array {
		return self::blocking(
			self::checklist(
				array(
					'course'    => $course,
					'questions' => $questions,
					'opens_at'  => $opens_at,
				)
			)
		);
	}







	public static function blocking( array $checklist ): array {
		$faltan = array();
		foreach ( $checklist as $punto ) {
			if ( ! empty( $punto['blocks'] ) && empty( $punto['ok'] ) ) {
				$faltan = array_merge( $faltan, (array) $punto['missing'] );
			}
		}
		return $faltan;
	}











	public static function checklist( array $facts ): array {
		$abre       = self::text( $facts, 'opens_at' );
		$abre       = self::is_valid_date( $abre ) ? $abre : '';
		$cierra     = self::text( $facts, 'closes_at' );
		$cierra     = self::is_valid_date( $cierra ) ? $cierra : $abre;
		$preguntas  = (array) ( $facts['questions'] ?? array() );
		$sin_curso  = 'Falta el curso: elíjalo en «Datos».';
		$sin_plazo  = 'Falta el plazo de solicitud: ponga al menos su fecha de inicio en «Datos».';
		$lista      = array(
			self::point( 'course', (int) ( $facts['course'] ?? 0 ) > 0, true, 'Curso elegido.', $sin_curso, array( $sin_curso ) ),
			self::point( 'window', '' !== $abre, true, sprintf( 'Plazo de solicitud: del %1$s al %2$s.', self::dmy( $abre ), self::dmy( $cierra ) ), $sin_plazo . ' Sin él, el procedimiento se quedaría en «Próximo» y nadie podría solicitar.', array( $sin_plazo ) ),
			self::point( 'questions', true, false, array() === $preguntas ? 'Sin preguntas propias: la solicitud pedirá solo los datos de siempre.' : sprintf( 'Preguntas propias: %d.', count( $preguntas ) ), '', array() ),
		);
		$sin_fechas = array();
		$entregas   = 0;
		foreach ( $preguntas as $pregunta ) {
			if ( ProcedureQuestions::is_delivery( $pregunta ) ) {
				++$entregas;
				if ( ! ProcedureQuestions::valid_delivery( $pregunta ) ) {
					$sin_fechas[] = sprintf( 'La entrega «%s» no tiene fechas de apertura y cierre: póngalas en «Preguntas».', (string) ( $pregunta['label'] ?? '' ) );
				}
			}
		}
		if ( $entregas > 0 ) {
			$lista[] = self::point( 'delivery', array() === $sin_fechas, true, 'Las entregas con plazo propio tienen sus fechas.', implode( ' ', $sin_fechas ), $sin_fechas );
		}
		$lista[] = self::point( 'resolution', '' !== self::text( $facts, 'resolution' ), false, 'Resolución enlazada.', 'Sin enlace a la resolución: puede publicar igual y añadirlo después en «Enlaces».', array() );
		return $lista;
	}












	private static function point( string $key, bool $ok, bool $blocks, string $done, string $missing, array $why ): array {
		return array(
			'key'     => $key,
			'ok'      => $ok,
			'blocks'  => $blocks,
			'text'    => $ok ? $done : $missing,
			'missing' => $ok ? array() : $why,
		);
	}







	private static function dmy( string $ymd ): string {
		return implode( '-', array_reverse( explode( '-', $ymd ) ) );
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
		return self::keys_of( $raw, ProcedureMetaKeys::ownerships() );
	}











	public static function stored_ownership( $stored ): array {
		$titular = self::ownership( $stored );
		return array() === $titular ? array_keys( ProcedureMetaKeys::ownerships() ) : $titular;
	}







	public static function centre_kinds( $raw ): array {
		$seleccionados = self::keys_of(
			$raw,
			array_merge( ProcedureMetaKeys::centre_kinds(), array( 'both' => '' ) )
		);
		return in_array( 'both', $seleccionados, true ) ? array_keys( ProcedureMetaKeys::centre_kinds() ) : $seleccionados;
	}








	private static function keys_of( $raw, array $vocabulary ): array {
		if ( is_string( $raw ) ) {
			$raw = explode( ',', $raw );
		}
		if ( ! is_array( $raw ) ) {
			return array();
		}
		$raw = array_map( 'trim', array_map( 'strval', array_filter( $raw, 'is_scalar' ) ) );
		return array_values( array_intersect( array_keys( $vocabulary ), $raw ) );
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














	public static function is_acceptable_link( string $url, string $stored ): bool {
		if ( '' === $url ) {
			return true;
		}
		if ( ! self::is_url( $url ) ) {
			return false;
		}
		$partes = (array) parse_url( $url );
		return $url === $stored || ( 'https' === strtolower( (string) ( $partes['scheme'] ?? '' ) ) && false !== strpos( (string) ( $partes['host'] ?? '' ), '.' ) );
	}







	public static function is_email( string $value ): bool {
		return (bool) preg_match( '/^[^@\s]+@[^@\s.]+(\.[^@\s.]+)+$/', $value );
	}








	private static function text( array $raw, string $key ): string {
		$value = $raw[ $key ] ?? '';
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}
}

}








namespace Prc\Domain;

use Prc\Meta\ApplicationMetaKeys;












if ( ! class_exists( 'Prc\Domain\ApplicationInput', false ) ) {
final class ApplicationInput {














	public static function validate( array $raw, array $procedure ): array {
		$errors  = array();
		$teacher = 'teacher' === ( $procedure['applicant_type'] ?? '' );

		$position = $teacher ? '' : self::text( $raw, 'position' );
		if ( ! $teacher && ! isset( ApplicationMetaKeys::positions()[ $position ] ) ) {
			$errors[] = 'position';
			$position = '';
		}

		$count        = $teacher ? 0 : (int) ( $procedure['coordinator_count'] ?? ( ! empty( $procedure['requires_coordinator'] ) ? 1 : 0 ) );
		$coordinacion = self::coordinators( $raw, $count );
		$errors       = array_merge( $errors, $coordinacion['errors'] );
		$coordinators = $coordinacion['data'];


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
			'fields' => array_values( array_merge( array_diff( $errors, $coordinacion['errors'] ), $coordinacion['fields'] ) ),
			'data'   => array(
				'position'    => $position,
				'coordinator' => $coordinators,
				'accept'      => $accepted,
				'answers'     => $respuesta['data'],
			),
		);
	}












	private static function coordinators( array $raw, int $count ): array {
		$filas = is_array( $raw['coordinators'] ?? null ) ? $raw['coordinators'] : array();
		if ( array() === $filas ) {
			$filas[] = array(
				'name'    => $raw['coordinator_name'] ?? '',
				'surname' => $raw['coordinator_surname'] ?? '',
				'dni'     => $raw['coordinator_dni'] ?? '',
				'email'   => $raw['coordinator_email'] ?? '',
			);
		}
		$errors = array();
		$fields = array();
		$data   = array();
		for ( $i = 0; $i < $count; ++$i ) {
			$fila        = is_array( $filas[ $i ] ?? null ) ? $filas[ $i ] : array();
			$coordinator = array(
				'name'    => self::text( $fila, 'name' ),
				'surname' => self::text( $fila, 'surname' ),
				'dni'     => self::dni( self::text( $fila, 'dni' ) ),
				'email'   => strtolower( self::text( $fila, 'email' ) ),
			);
			$checks      = array(
				'coordinator_name'    => '' !== $coordinator['name'],
				'coordinator_surname' => '' !== $coordinator['surname'],
				'coordinator_email'   => ProcedureInput::is_email( $coordinator['email'] ),
				'coordinator_dni'     => self::is_dni( $coordinator['dni'] ),
			);
			foreach ( $checks as $error => $ok ) {
				if ( ! $ok ) {
					$errors[] = $error;
					$fields[] = $error . ':' . $i;
				}
			}
			$data[] = $coordinator;
		}
		return array(
			'errors' => $errors,
			'fields' => $fields,
			'data'   => $data,
		);
	}








	public static function why( array $errors, array $questions = array() ): string {
		$textos = self::texts( $questions );

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












	public static function labels( array $fields, array $questions = array() ): array {
		$textos = self::texts( $questions );
		$out    = array();
		foreach ( $fields as $field ) {
			$partes = explode( ':', (string) $field, 2 );
			$fila   = 0 === strpos( $partes[0], 'coordinator_' ) && isset( $partes[1] ) ? (int) $partes[1] + 1 : 0;
			$texto  = (string) ( $textos[ $fila > 0 ? $partes[0] : (string) $field ] ?? '' );
			if ( '' !== $texto ) {
				$out[ (string) $field ] = $fila > 0 ? str_replace( 'cada persona coordinadora', 'la persona coordinadora ' . $fila, $texto ) : $texto;
			}
		}
		return $out;
	}







	private static function texts( array $questions ): array {
		$textos = array(
			'position'            => 'el cargo',
			'coordinator_name'    => 'el nombre de cada persona coordinadora',
			'coordinator_surname' => 'los apellidos de cada persona coordinadora',
			'coordinator_dni'     => 'el DNI o NIE de cada persona coordinadora',
			'coordinator_email'   => 'el correo de cada persona coordinadora',
			'accept'              => 'la aceptación de los compromisos',
		);
		foreach ( $questions as $pregunta ) {
			$textos[ 'answer:' . (string) ( $pregunta['key'] ?? '' ) ] = 'la respuesta a «' . (string) ( $pregunta['label'] ?? '' ) . '»';
		}
		return $textos;
	}








	private static function text( array $raw, string $key ): string {
		$value = $raw[ $key ] ?? '';
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}







	public static function is_dni( string $value ): bool {
		$value = self::dni( $value );
		if ( ! preg_match( '/^(?:[0-9]{8}|[XYZ][0-9]{7})[A-Z]$/', $value ) ) {
			return false;
		}
		$number = strtr(
			substr( $value, 0, -1 ),
			array(
				'X' => '0',
				'Y' => '1',
				'Z' => '2',
			)
		);
		return substr( 'TRWAGMYFPDXBNJZSQVHLCKE', (int) $number % 23, 1 ) === substr( $value, -1 );
	}







	private static function dni( string $value ): string {
		return strtoupper( (string) preg_replace( '/[ -]/', '', trim( $value ) ) );
	}
}

}








namespace Prc\Domain;

use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;





















if ( ! class_exists( 'Prc\Domain\PeopleSpec', false ) ) {
final class PeopleSpec {


	public const MAX_ROLES = 10;


	public const MAX_GROUPS = 20;


	public const MAX_PEOPLE = 200;




	public const GROUP_MARK = 'grupo';







	public static function sanitize( $raw ): array {
		$raw    = is_array( $raw ) ? $raw : array();
		$roles  = self::keyed( $raw['roles'] ?? array(), 'r', (int) ( $raw['role_seq'] ?? 0 ), self::MAX_ROLES );
		$groups = self::keyed( $raw['groups'] ?? array(), 'g', (int) ( $raw['group_seq'] ?? 0 ), self::MAX_GROUPS );
		$max    = self::number( $raw['max'] ?? null );
		$min    = self::number( $raw['min'] ?? null );
		return array(
			'enabled'          => ! empty( $raw['enabled'] ),
			'roles'            => array_map(
				static function ( array $role ): array {
					return array(
						'key'         => $role['key'],
						'label'       => $role['label'],
						'needs_group' => ! empty( $role['needs_group'] ),
					);
				},
				$roles['items']
			),
			'role_seq'         => $roles['seq'],
			'groups'           => array_map(
				static function ( array $group ): array {
					return array(
						'key'   => $group['key'],
						'label' => $group['label'],
					);
				},
				$groups['items']
			),
			'group_seq'        => $groups['seq'],
			'min'              => null === $min ? 0 : max( 0, min( $min, self::MAX_PEOPLE ) ),
			'max'              => null === $max || $max <= 0 ? self::MAX_PEOPLE : min( $max, self::MAX_PEOPLE ),
			'unique_per_group' => ! empty( $raw['unique_per_group'] ),
			'multi_role'       => ! empty( $raw['multi_role'] ),
			'opens_at'         => self::date( $raw['opens_at'] ?? '' ),
			'closes_at'        => self::date( $raw['closes_at'] ?? '' ),
			'admitted_only'    => ! empty( $raw['admitted_only'] ),
		);
	}













	private static function keyed( $raw, string $prefix, int $seq, int $max ): array {
		$items = array();
		$mayor = max( 0, $seq );
		foreach ( is_array( $raw ) ? $raw : array() as $item ) {
			$item  = is_array( $item ) ? $item : array( 'label' => $item );
			$label = is_scalar( $item['label'] ?? null ) ? trim( (string) $item['label'] ) : '';
			$key   = is_scalar( $item['key'] ?? null ) ? strtolower( trim( (string) $item['key'] ) ) : '';
			if ( '' === $label || in_array( $label, array_column( $items, 'label' ), true ) || count( $items ) >= $max ) {
				continue;
			}
			$key     = preg_match( '/^' . $prefix . '[1-9]\d{0,3}$/', $key ) && ! in_array( $key, array_column( $items, 'key' ), true ) ? $key : '';
			$mayor   = max( $mayor, (int) substr( $key, 1 ) );
			$items[] = array(
				'key'         => $key,
				'label'       => $label,
				'needs_group' => ! empty( $item['needs_group'] ),
			);
		}
		foreach ( $items as $i => $item ) {
			if ( '' === $item['key'] ) {
				++$mayor;
				$items[ $i ]['key'] = $prefix . $mayor;
			}
		}
		return array(
			'items' => $items,
			'seq'   => $mayor,
		);
	}














	public static function from_input( array $raw, array $previous ): array {
		$previous = self::sanitize( $previous );
		$roles    = self::typed_roles( self::text( $raw, 'roles' ), $previous['roles'] );
		$groups   = QuestionGrid::match_rows( self::text( $raw, 'groups' ), $previous['groups'] );
		$min      = self::text( $raw, 'min' );
		$max      = self::text( $raw, 'max' );
		$spec     = self::sanitize(
			array(
				'enabled'          => ! empty( $raw['enabled'] ),
				'roles'            => $roles,
				'role_seq'         => $previous['role_seq'],
				'groups'           => $groups,
				'group_seq'        => $previous['group_seq'],
				'min'              => $min,
				'max'              => $max,
				'unique_per_group' => ! empty( $raw['unique_per_group'] ),
				'multi_role'       => ! empty( $raw['multi_role'] ),
				'opens_at'         => self::text( $raw, 'opens_at' ),
				'closes_at'        => self::text( $raw, 'closes_at' ),
				'admitted_only'    => ! empty( $raw['admitted_only'] ),
			)
		);
		$error    = self::input_error( $raw, $spec, count( $roles ), count( $groups ) );


		return array(
			'spec'  => $spec,
			'error' => $error,
		);
	}








	private static function typed_roles( string $text, array $previous ): array {
		$marcas = array();
		$lineas = array();
		foreach ( (array) preg_split( '/\r\n|\r|\n/', $text ) as $linea ) {
			list( $rotulo, $marca ) = self::split_role( (string) $linea );
			$lineas[]               = $rotulo;
			if ( '' !== $rotulo ) {
				$marcas[] = $marca;
			}
		}
		$out = array();


		foreach ( QuestionGrid::match_rows( implode( "\n", $lineas ), $previous ) as $i => $role ) {
			$out[] = $role + array( 'needs_group' => ! empty( $marcas[ $i ] ) );
		}
		return $out;
	}







	private static function split_role( string $linea ): array {
		$partes = array_map( 'trim', explode( '|', $linea ) );
		$marca  = count( $partes ) > 1 && self::GROUP_MARK === strtolower( (string) end( $partes ) );
		if ( $marca ) {
			array_pop( $partes );
		}


		return array( trim( implode( ' | ', array_filter( $partes, 'strlen' ) ) ), $marca );
	}








	public static function role_lines( string $text ): array {
		$out = array();
		foreach ( (array) preg_split( '/\r\n|\r|\n/', $text ) as $linea ) {
			list( $rotulo, $marca ) = self::split_role( (string) $linea );
			if ( '' !== $rotulo ) {
				$out[] = array(
					'label'       => $rotulo,
					'needs_group' => $marca,
				);
			}
		}
		return $out;
	}















	public static function coordination_clash( array $before, array $after, int $count_before, int $count_after ): string {
		$ya_estaba = $count_before > 0 && self::collects_coordination( $before );
		if ( $ya_estaba || $count_after <= 0 || ! self::collects_coordination( $after ) ) {
			return '';
		}
		return $count_after !== $count_before
			? 'La coordinación ya se da de alta en la lista de la pestaña «Personas»: deje aquí «No se piden». No se ha guardado nada.'
			: 'En «Datos» se piden personas coordinadoras: para recogerlas en esta lista, deje antes «No se piden» en «Datos». No se ha guardado nada.';
	}










	private static function input_error( array $raw, array $spec, int $roles, int $groups ): string {
		$fechas = array( self::text( $raw, 'opens_at' ), self::text( $raw, 'closes_at' ) );
		$checks = array(
			'Caben como mucho ' . self::MAX_ROLES . ' papeles.' => $roles > self::MAX_ROLES,
			'Caben como mucho ' . self::MAX_GROUPS . ' grupos.' => $groups > self::MAX_GROUPS,
			'Escriba al menos un papel, uno por línea.' => $spec['enabled'] && array() === $spec['roles'],
			'Un papel exige grupo, pero no hay grupos: escriba los grupos o quite «| grupo».' => array() === $spec['groups'] && in_array( true, array_column( $spec['roles'], 'needs_group' ), true ),
			'El mínimo y el máximo de personas tienen que ser números enteros, sin decimales.' => ! self::is_count( self::text( $raw, 'min' ) ) || ! self::is_count( self::text( $raw, 'max' ) ),
			'El máximo de personas no puede pasar de ' . self::MAX_PEOPLE . '.' => (int) self::text( $raw, 'max' ) > self::MAX_PEOPLE,
			'El mínimo de personas no puede ser mayor que el máximo.' => $spec['min'] > $spec['max'],
			'Las fechas de la lista de personas tienen que ser días que existen.' => ( '' !== $fechas[0] && '' === $spec['opens_at'] ) || ( '' !== $fechas[1] && '' === $spec['closes_at'] ),
			'La lista de personas no puede cerrarse antes de abrirse.' => '' !== $spec['opens_at'] && '' !== $spec['closes_at'] && $spec['closes_at'] < $spec['opens_at'],
		);
		foreach ( $checks as $texto => $falla ) {
			if ( $falla ) {
				return $texto;
			}
		}
		return '';
	}













	public static function refusal( array $before, array $after, array $used ): string {
		foreach ( array(
			'roles'  => 'El papel',
			'groups' => 'El grupo',
		) as $lista => $nombre ) {
			$quedan = array_column( (array) ( $after[ $lista ] ?? array() ), 'key' );
			foreach ( (array) ( $before[ $lista ] ?? array() ) as $item ) {
				if ( ! in_array( $item['key'], $quedan, true ) && ! empty( $used[ $lista ][ $item['key'] ] ) ) {
					return $nombre . ' «' . $item['label'] . '» tiene personas dadas de alta en alguna solicitud: no se puede quitar. Puede cambiarle el nombre. No se ha guardado nada.';
				}
			}
		}
		return '';
	}







	public static function roles_text( array $spec ): string {
		$lineas = array();
		foreach ( (array) ( $spec['roles'] ?? array() ) as $role ) {
			$lineas[] = (string) $role['label'] . ( ! empty( $role['needs_group'] ) ? ' | ' . self::GROUP_MARK : '' );
		}
		return implode( "\n", $lineas );
	}







	public static function groups_text( array $spec ): string {
		return implode( "\n", array_column( (array) ( $spec['groups'] ?? array() ), 'label' ) );
	}









	public static function label( array $spec, string $which, string $key ): string {
		foreach ( (array) ( $spec[ $which ] ?? array() ) as $item ) {
			if ( $key === (string) $item['key'] ) {
				return (string) $item['label'];
			}
		}
		return '';
	}












	public static function collects_coordination( array $spec ): bool {
		$spec = self::sanitize( $spec );
		if ( true !== $spec['enabled'] || (int) $spec['min'] < 1 ) {
			return false;
		}
		foreach ( (array) $spec['roles'] as $papel ) {
			if ( false !== strpos( strtolower( (string) $papel['label'] ), 'coordina' ) ) {
				return true;
			}
		}
		return false;
	}














	public static function why_closed( array $spec, array $ctx ): string {
		$spec   = self::sanitize( $spec );
		$review = (string) ( $ctx['review'] ?? '' );
		$checks = array(
			'Este procedimiento no pide una lista de personas.' => ! $spec['enabled'] || ! empty( $ctx['teacher'] ),
			'El procedimiento está marcado como histórico: la lista de personas ya no se cambia.' => ! empty( $ctx['archived'] ),
			'La solicitud está excluida: su lista de personas ya no se cambia.' => ApplicationMetaKeys::REVIEW_EXCLUDED === $review,
			'La lista de personas se gestiona cuando la solicitud esté admitida, y todavía no lo está.' => $spec['admitted_only'] && ApplicationMetaKeys::REVIEW_ADMITTED !== $review,
		);
		foreach ( $checks as $texto => $cierra ) {
			if ( $cierra ) {
				return $texto;
			}
		}
		return self::window( $spec, (string) ( $ctx['today'] ?? '' ), (string) ( $ctx['state'] ?? '' ), $review, ! empty( $ctx['late'] ) );
	}











	private static function window( array $spec, string $today, string $state, string $review, bool $late = false ): string {
		$abre   = (string) $spec['opens_at'];
		$cierra = (string) $spec['closes_at'];
		if ( '' === $abre && '' === $cierra ) {
			return self::application_editable( $state, $review, $late )
				? ''
				: 'La lista de personas sigue el plazo de la solicitud, y no está abierto para la suya.';
		}
		if ( '' !== $abre && $today < $abre ) {
			return 'La lista de personas se abre el ' . self::day( $abre ) . '.';
		}
		if ( '' !== $cierra && $today > $cierra ) {
			return 'El plazo de la lista de personas terminó el ' . self::day( $cierra ) . '.';
		}
		return '';
	}















	public static function until( array $spec, string $closes_at, string $today ): string {
		$spec = self::sanitize( $spec );
		if ( true !== $spec['enabled'] ) {
			return '';
		}
		$propia = '' !== (string) $spec['opens_at'] || '' !== (string) $spec['closes_at'];
		$ultimo = $propia ? (string) $spec['closes_at'] : self::date( $closes_at );
		return '' !== $ultimo && $ultimo >= $today ? $ultimo : '';
	}














	public static function application_editable( string $state, string $review, bool $late = false ): bool {
		return '' === ProcedureState::application_window( $state, $review, false, $late );
	}







	private static function day( string $ymd ): string {
		return implode( '-', array_reverse( explode( '-', $ymd ) ) );
	}







	private static function date( $value ): string {
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';
		return ProcedureInput::is_valid_date( $value ) ? $value : '';
	}







	private static function number( $value ): ?int {
		if ( is_int( $value ) ) {
			return $value;
		}
		return is_scalar( $value ) ? ProcedureQuestions::integer( (string) $value ) : null;
	}







	private static function is_count( string $value ): bool {
		$n = ProcedureQuestions::integer( $value );
		return '' === $value || ( null !== $n && $n >= 0 );
	}








	private static function text( array $raw, string $key ): string {
		$value = $raw[ $key ] ?? '';
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}
}

}








namespace Prc\Domain;

use Prc\Meta\ProcedureMetaKeys;













if ( ! class_exists( 'Prc\Domain\ProcedureCopy', false ) ) {
final class ProcedureCopy {




	public const COPY = 'copy';





	public const TRANSFORM = 'transform';




	public const NEVER = 'never';




	public const SUFFIX = ' (copia)';






	public static function policy(): array {
		return array(

			ProcedureMetaKeys::OPENS_AT             => self::NEVER,
			ProcedureMetaKeys::CLOSES_AT            => self::NEVER,
			ProcedureMetaKeys::AMEND_OPENS_AT       => self::NEVER,
			ProcedureMetaKeys::AMEND_CLOSES_AT      => self::NEVER,
			ProcedureMetaKeys::RESOLUTION_URL       => self::NEVER,
			ProcedureMetaKeys::PROVISIONAL_LIST_URL => self::NEVER,
			ProcedureMetaKeys::FINAL_LIST_URL       => self::NEVER,

			ProcedureMetaKeys::ARCHIVED             => self::NEVER,
			ProcedureMetaKeys::APPLICATIONS_CLOSED  => self::NEVER,

			ProcedureMetaKeys::CONTACT_EMAILS       => self::COPY,
			ProcedureMetaKeys::HEADER_COLOR         => self::COPY,
			ProcedureMetaKeys::AUDIENCE             => self::COPY,
			ProcedureMetaKeys::OWNERSHIP            => self::COPY,
			ProcedureMetaKeys::CENTRE_KINDS         => self::COPY,
			ProcedureMetaKeys::CENTRE_TYPES         => self::COPY,
			ProcedureMetaKeys::ISLANDS              => self::COPY,
			ProcedureMetaKeys::ALLOWED_CENTRES      => self::COPY,
			ProcedureMetaKeys::REQUIRES_COORDINATOR => self::COPY,
			ProcedureMetaKeys::COORDINATOR_COUNT    => self::COPY,
			ProcedureMetaKeys::COMMITMENTS          => self::COPY,
			ProcedureMetaKeys::CUSTOM_CSS           => self::COPY,
			ProcedureMetaKeys::NOTIFY               => self::COPY,
			ProcedureMetaKeys::OBSERVABLE           => self::COPY,
			ProcedureMetaKeys::OBSERVER_FILES       => self::COPY,
			ProcedureMetaKeys::OBSERVER_EXPORT      => self::COPY,
			ProcedureMetaKeys::OBSERVER_NOTIFY      => self::COPY,

			ProcedureMetaKeys::QUESTIONS            => self::TRANSFORM,
			ProcedureMetaKeys::QUESTIONS_SEQ        => self::TRANSFORM,
			ProcedureMetaKeys::PEOPLE_SPEC          => self::TRANSFORM,
			ProcedureMetaKeys::OBSERVER_COLUMNS     => self::TRANSFORM,

			ProcedureMetaKeys::CUSTOM_JS            => self::TRANSFORM,
		);
	}







	public static function rule( string $key ): string {
		return self::policy()[ $key ] ?? '';
	}







	public static function title( string $title ): string {
		$title = trim( $title );
		$title = '' !== $title ? $title : 'Procedimiento sin título';

		return substr( $title, -strlen( self::SUFFIX ) ) === self::SUFFIX ? $title : $title . self::SUFFIX;
	}











	public static function meta( array $source, bool $with_js ): array {
		$meta = array();
		foreach ( self::policy() as $key => $rule ) {
			if ( self::COPY === $rule && array_key_exists( $key, $source ) ) {
				$meta[ $key ] = $source[ $key ];
			}
		}

		$preguntas = self::questions( $source[ ProcedureMetaKeys::QUESTIONS ] ?? array() );
		if ( array_key_exists( ProcedureMetaKeys::QUESTIONS, $source ) ) {
			$meta[ ProcedureMetaKeys::QUESTIONS ]     = $preguntas['questions'];
			$meta[ ProcedureMetaKeys::QUESTIONS_SEQ ] = $preguntas['seq'];
		}
		if ( array_key_exists( ProcedureMetaKeys::PEOPLE_SPEC, $source ) ) {
			$meta[ ProcedureMetaKeys::PEOPLE_SPEC ] = self::people_spec( $source[ ProcedureMetaKeys::PEOPLE_SPEC ] );
		}
		if ( array_key_exists( ProcedureMetaKeys::OBSERVER_COLUMNS, $source ) ) {
			$meta[ ProcedureMetaKeys::OBSERVER_COLUMNS ] = self::observer_columns( $source[ ProcedureMetaKeys::OBSERVER_COLUMNS ], $preguntas['map'] );
		}

		$js = $source[ ProcedureMetaKeys::CUSTOM_JS ] ?? '';
		$js = is_string( $js ) ? $js : '';
		if ( $with_js && '' !== $js ) {
			$meta[ ProcedureMetaKeys::CUSTOM_JS ] = $js;
		}

		return array(
			'meta'    => $meta,
			'js_left' => ! $with_js && '' !== trim( $js ),
		);
	}













	public static function questions( $raw ): array {
		$out = array();
		$map = array();
		foreach ( ProcedureQuestions::sanitize( $raw ) as $i => $pregunta ) {
			$antes           = (string) $pregunta['key'];
			$pregunta['key'] = 'q' . ( $i + 1 );
			$map[ $antes ]   = $pregunta['key'];
			if ( ProcedureQuestions::is_delivery( $pregunta ) ) {
				$pregunta['opens_at']  = '';
				$pregunta['closes_at'] = '';
			}
			if ( ProcedureQuestions::TYPE_GRID === $pregunta['type'] ) {
				$filas               = self::renumber( (array) $pregunta['rows'], 'r' );
				$pregunta['rows']    = $filas['items'];
				$pregunta['row_seq'] = count( $filas['items'] );
				foreach ( $filas['map'] as $fila => $nueva ) {
					$map[ $antes . ':' . $fila ] = $pregunta['key'] . ':' . $nueva;
				}
			}
			$out[] = $pregunta;
		}
		return array(
			'questions' => $out,
			'seq'       => count( $out ),
			'map'       => $map,
		);
	}










	public static function people_spec( $raw ): array {
		$spec   = PeopleSpec::sanitize( $raw );
		$roles  = self::renumber( $spec['roles'], 'r' );
		$groups = self::renumber( $spec['groups'], 'g' );

		$spec['roles']     = $roles['items'];
		$spec['role_seq']  = count( $roles['items'] );
		$spec['groups']    = $groups['items'];
		$spec['group_seq'] = count( $groups['items'] );
		$spec['opens_at']  = '';
		$spec['closes_at'] = '';
		return $spec;
	}











	public static function observer_columns( $raw, array $map ): array {
		$out = array();
		foreach ( is_array( $raw ) ? $raw : array() as $columna ) {
			$columna = is_string( $columna ) ? $columna : '';
			if ( preg_match( '/^q\d+(:r\d+)?$/', $columna ) ) {
				$columna = $map[ $columna ] ?? '';
			}
			if ( '' !== $columna && ! in_array( $columna, $out, true ) ) {
				$out[] = $columna;
			}
		}
		return $out;
	}








	private static function renumber( array $items, string $prefix ): array {
		$out = array();
		$map = array();
		foreach ( array_values( $items ) as $i => $item ) {
			$nueva                        = $prefix . ( $i + 1 );
			$map[ (string) $item['key'] ] = $nueva;
			$item['key']                  = $nueva;
			$out[]                        = $item;
		}
		return array(
			'items' => $out,
			'map'   => $map,
		);
	}
}

}








namespace Prc\Domain;

















if ( ! class_exists( 'Prc\Domain\ApplicationPeople', false ) ) {
final class ApplicationPeople {






	public const FIELDS = array( 'id', 'name', 'surname', 'dni', 'email', 'phone', 'role', 'group' );








	public const CLASHES = array( 'duplicate', 'same_person', 'unique_group' );







	public static function row( $raw ): array {
		$raw = is_array( $raw ) ? $raw : array();
		$out = array();
		foreach ( self::FIELDS as $campo ) {
			$valor         = $raw[ $campo ] ?? '';
			$out[ $campo ] = is_scalar( $valor ) ? trim( (string) preg_replace( '/\s+/u', ' ', (string) $valor ) ) : '';
		}
		$out['id']    = preg_match( '/^[a-z0-9]{1,32}$/', strtolower( $out['id'] ) ) ? strtolower( $out['id'] ) : '';
		$out['dni']   = strtoupper( (string) preg_replace( '/[ -]/', '', $out['dni'] ) );
		$out['email'] = strtolower( $out['email'] );
		$out['role']  = strtolower( $out['role'] );
		$out['group'] = strtolower( $out['group'] );
		return $out;
	}








	public static function rows( $raw ): array {
		$out = array();
		foreach ( is_array( $raw ) ? $raw : array() as $item ) {
			$fila = self::row( $item );
			if ( '' === $fila['id'] || isset( $out[ $fila['id'] ] ) ) {
				continue;
			}
			$out[ $fila['id'] ] = $fila;
		}
		return array_slice( array_values( $out ), 0, PeopleSpec::MAX_PEOPLE );
	}













	public static function validate( array $spec, array $people, array $row ): array {
		$spec   = PeopleSpec::sanitize( $spec );
		$row    = self::row( $row );
		$errors = self::fields( $spec, $row );
		if ( array() !== $errors ) {
			return $errors;
		}
		return self::against( $spec, self::rows( $people ), $row );
	}












	public static function clash( array $spec, array $people, array $row ): string {
		$choque = self::conflict( PeopleSpec::sanitize( $spec ), self::rows( $people ), self::row( $row ) );
		return null === $choque ? '' : (string) $choque['row']['id'];
	}








	private static function fields( array $spec, array $row ): array {
		$papel  = self::find( $spec['roles'], $row['role'] );
		$checks = array(
			'name'    => array( '' !== $row['name'], 'el nombre' ),
			'surname' => array( '' !== $row['surname'], 'los apellidos' ),
			'dni'     => array( ApplicationInput::is_dni( $row['dni'] ), 'el DNI o NIE, completo y con su letra' ),
			'email'   => array( ProcedureInput::is_email( $row['email'] ), 'el correo electrónico' ),
			'phone'   => array( '' === $row['phone'] || (bool) preg_match( '/^\+?[0-9][0-9 ]{5,19}$/', $row['phone'] ), 'el teléfono, solo con cifras, espacios y un «+» delante si hace falta' ),
			'role'    => array( array() !== $papel, 'el papel' ),
		);
		if ( array() !== $papel ) {
			$grupo           = '' === $row['group'] || array() !== self::find( $spec['groups'], $row['group'] );
			$checks['group'] = array( $grupo && ( '' !== $row['group'] || empty( $papel['needs_group'] ) ), 'el grupo, que el papel «' . $papel['label'] . '» exige (por ejemplo, el eje)' );
		}
		$out = array();
		foreach ( $checks as $clave => list( $bien, $texto ) ) {
			if ( ! $bien ) {
				$out[ $clave ] = $texto;
			}
		}
		return $out;
	}









	private static function against( array $spec, array $people, array $row ): array {
		$choque = self::conflict( $spec, $people, $row );
		if ( null !== $choque ) {
			$quien  = trim( $choque['row']['name'] . ' ' . $choque['row']['surname'] );
			$donde  = 'use «Sustituir o corregir» en su fila.';
			$textos = array(
				'duplicate'    => 'Esa persona ya está en la lista con el mismo papel y grupo.',
				'same_person'  => $quien . ' ya está en la lista como «' . self::place( $spec, $choque['row'] ) . '». Para cambiarle el papel o sustituirla, ' . $donde,
				'unique_group' => 'Ese papel ya lo tiene ' . $quien . ' en ese grupo, y solo cabe una persona por papel y grupo. Para sustituirla, ' . $donde,
			);
			return array( $choque['error'] => $textos[ $choque['error'] ] );
		}
		$otras = count( self::others( $people, $row ) );
		if ( '' === $row['id'] && $otras + 1 > (int) $spec['max'] ) {
			return array( 'max' => 'La lista ya tiene ' . $otras . ' personas, que es el máximo que admite este procedimiento.' );
		}
		return array();
	}















	private static function conflict( array $spec, array $people, array $row ): ?array {
		$ya_era = self::was_already( $people, $row );
		foreach ( self::others( $people, $row ) as $otra ) {
			$error = self::clash_kind( $spec, $otra, $row, $ya_era );
			if ( '' !== $error ) {
				return array(
					'error' => $error,
					'row'   => $otra,
				);
			}
		}
		return null;
	}










	private static function clash_kind( array $spec, array $otra, array $row, bool $ya_era ): string {
		$mismo_sitio = $otra['role'] === $row['role'] && $otra['group'] === $row['group'];
		$misma       = $otra['dni'] === $row['dni'];
		if ( $mismo_sitio && $misma ) {
			return 'duplicate';
		}
		if ( $misma && ! $spec['multi_role'] && ! $ya_era ) {
			return 'same_person';
		}
		return $mismo_sitio && $spec['unique_per_group'] && '' !== $row['group'] ? 'unique_group' : '';
	}








	private static function was_already( array $people, array $row ): bool {
		foreach ( $people as $fila ) {
			if ( '' !== $row['id'] && $fila['id'] === $row['id'] ) {
				return $fila['dni'] === $row['dni'] && $fila['role'] === $row['role'] && $fila['group'] === $row['group'];
			}
		}
		return false;
	}








	private static function others( array $people, array $row ): array {
		return array_values(
			array_filter(
				$people,
				static function ( array $otra ) use ( $row ): bool {
					return '' === $row['id'] || $otra['id'] !== $row['id'];
				}
			)
		);
	}








	private static function place( array $spec, array $row ): string {
		return implode( ' · ', array_filter( array( PeopleSpec::label( $spec, 'roles', (string) $row['role'] ), PeopleSpec::label( $spec, 'groups', (string) $row['group'] ) ), 'strlen' ) );
	}











	public static function is_complete( array $spec, array $people ): bool {
		$spec = PeopleSpec::sanitize( $spec );
		return ! $spec['enabled'] || count( self::rows( $people ) ) >= $spec['min'];
	}











	public static function fingerprint( array $row ): string {
		$row = self::row( $row );
		return substr( hash( 'sha256', implode( "\x1f", $row ) ), 0, 16 );
	}













	public static function describe( array $spec, array $row, ?array $fields = null ): string {
		$row    = self::row( $row );
		$partes = array(
			'name'    => $row['name'],
			'surname' => $row['surname'],
			'email'   => $row['email'],
			'phone'   => $row['phone'],
			'role'    => PeopleSpec::label( $spec, 'roles', $row['role'] ),
			'group'   => PeopleSpec::label( $spec, 'groups', $row['group'] ),
		);
		if ( null !== $fields ) {
			$partes = array_intersect_key( $partes, array_flip( $fields ) );
		}
		$nombre = trim( ( $partes['name'] ?? '' ) . ' ' . ( $partes['surname'] ?? '' ) );
		unset( $partes['name'], $partes['surname'] );
		return implode( ' · ', array_filter( array_merge( array( $nombre ), array_values( $partes ) ), 'strlen' ) );
	}







	public static function masked_dni( string $dni ): string {
		$dni = trim( $dni );
		return strlen( $dni ) <= 4 ? $dni : str_repeat( '•', strlen( $dni ) - 4 ) . substr( $dni, -4 );
	}








	private static function find( array $items, string $key ): array {
		foreach ( $items as $item ) {
			if ( '' !== $key && $key === (string) $item['key'] ) {
				return $item;
			}
		}
		return array();
	}
}

}








namespace Prc\Domain;

use Prc\Meta\ApplicationMetaKeys;











if ( ! class_exists( 'Prc\Domain\ApplicationFilter', false ) ) {
final class ApplicationFilter {


	public const ARG_REVIEW = 'revision';


	public const ARG_ISLAND = 'isla';


	public const ARG_ZONE = 'zona';


	public const ARG_INCOMPLETE = 'incompletas';


	public const ARG_SEARCH = 'buscar';


	public const ARG_PAGE = 'pagina';


	public const PER_PAGE = 50;


	public const MAX_SEARCH = 100;






	private const ARGS = array(
		'review'     => self::ARG_REVIEW,
		'island'     => self::ARG_ISLAND,
		'zone'       => self::ARG_ZONE,
		'incomplete' => self::ARG_INCOMPLETE,
		'search'     => self::ARG_SEARCH,
		'page'       => self::ARG_PAGE,
	);









	private const FOLD = array(
		'á' => 'a',
		'à' => 'a',
		'â' => 'a',
		'ä' => 'a',
		'é' => 'e',
		'è' => 'e',
		'ê' => 'e',
		'ë' => 'e',
		'í' => 'i',
		'ì' => 'i',
		'î' => 'i',
		'ï' => 'i',
		'ó' => 'o',
		'ò' => 'o',
		'ô' => 'o',
		'ö' => 'o',
		'ú' => 'u',
		'ù' => 'u',
		'û' => 'u',
		'ü' => 'u',
		'ñ' => 'n',
		'ç' => 'c',
	);






	public static function args(): array {
		return self::ARGS;
	}











	public static function sanitize( array $raw ): array {
		$estado = self::text( $raw, self::ARG_REVIEW, 32 );
		$pagina = self::text( $raw, self::ARG_PAGE, 9 );
		return array(
			'review'     => isset( ApplicationMetaKeys::review_states()[ $estado ] ) ? $estado : '',
			'island'     => self::text( $raw, self::ARG_ISLAND, 100 ),
			'zone'       => self::text( $raw, self::ARG_ZONE, 32 ),
			'incomplete' => '1' === self::text( $raw, self::ARG_INCOMPLETE, 1 ),
			'search'     => self::text( $raw, self::ARG_SEARCH, self::MAX_SEARCH ),
			'page'       => ctype_digit( $pagina ) && (int) $pagina > 0 ? (int) $pagina : 1,
		);
	}








	public static function against( array $filter, array $options ): array {
		foreach ( array(
			'island' => 'islands',
			'zone'   => 'zones',
		) as $clave => $lista ) {
			if ( '' !== $filter[ $clave ] && ! isset( $options[ $lista ][ $filter[ $clave ] ] ) ) {
				$filter[ $clave ] = '';
			}
		}
		return $filter;
	}







	public static function active( array $filter ): bool {
		return '' !== $filter['review'] || '' !== $filter['island'] || '' !== $filter['zone']
			|| true === $filter['incomplete'] || '' !== $filter['search'];
	}











	public static function query( array $filter, array $changes = array() ): array {
		if ( array() !== $changes && ! array_key_exists( 'page', $changes ) ) {
			$changes['page'] = 1;
		}
		$f   = array_merge( $filter, $changes );
		$out = array();
		foreach ( self::ARGS as $clave => $arg ) {
			$valor = $f[ $clave ] ?? '';
			if ( 'incomplete' === $clave ) {
				$valor = true === $valor ? '1' : '';
			} elseif ( 'page' === $clave ) {
				$valor = (int) $valor > 1 ? (string) (int) $valor : '';
			}
			if ( '' !== (string) $valor ) {
				$out[ $arg ] = (string) $valor;
			}
		}
		return $out;
	}












	public static function window( int $total, int $page, int $per_page = self::PER_PAGE ): array {
		$total  = max( 0, $total );
		$pages  = max( 1, (int) ceil( $total / max( 1, $per_page ) ) );
		$page   = min( max( 1, $page ), $pages );
		$offset = ( $page - 1 ) * $per_page;
		return array(
			'page'   => $page,
			'pages'  => $pages,
			'offset' => $offset,
			'from'   => 0 === $total ? 0 : $offset + 1,
			'to'     => min( $total, $offset + $per_page ),
		);
	}











	public static function matches( string $term, array $texts ): bool {
		$term = self::fold( $term );
		if ( '' === $term ) {
			return true;
		}
		foreach ( $texts as $texto ) {
			if ( false !== strpos( self::fold( (string) $texto ), $term ) ) {
				return true;
			}
		}
		return false;
	}







	private static function fold( string $text ): string {
		$text = function_exists( 'mb_strtolower' ) ? mb_strtolower( $text, 'UTF-8' ) : strtolower( $text );
		return strtr( trim( $text ), self::FOLD );
	}









	private static function text( array $raw, string $key, int $max ): string {
		$valor = $raw[ $key ] ?? '';
		if ( ! is_string( $valor ) && ! is_int( $valor ) ) {
			return '';
		}
		$valor = trim( (string) preg_replace( '/[\s\x00-\x1f]+/u', ' ', (string) $valor ) );
		return function_exists( 'mb_substr' ) ? mb_substr( $valor, 0, $max, 'UTF-8' ) : substr( $valor, 0, $max );
	}
}

}








namespace Prc\Domain;

use Prc\Meta\ApplicationMetaKeys;

















if ( ! class_exists( 'Prc\Domain\ApplicationCompleteness', false ) ) {
final class ApplicationCompleteness {













	public static function reasons( array $spec, $people, array $questions, $files, string $today, string $review, bool $pending = false ): array {
		if ( ApplicationMetaKeys::REVIEW_EXCLUDED === $review ) {
			return array();
		}
		$out  = $pending ? array( 'Pendiente de que el centro la complete' ) : array();
		$spec = PeopleSpec::sanitize( $spec );
		$hay  = ApplicationPeople::rows( $people );
		if ( ! ApplicationPeople::is_complete( $spec, $hay ) ) {
			$faltan = (int) $spec['min'] - count( $hay );
			$out[]  = 1 === $faltan ? 'Falta 1 persona' : sprintf( 'Faltan %d personas', $faltan );
		}
		$files = is_array( $files ) ? $files : array();
		foreach ( self::overdue( $questions, $today ) as $pregunta ) {
			$doc = $files[ (string) $pregunta['key'] ] ?? null;
			if ( ! is_array( $doc ) || ! isset( $doc['id'], $doc['stored'] ) ) {
				$out[] = 'Falta: ' . (string) $pregunta['label'];
			}
		}
		return $out;
	}








	public static function overdue( array $questions, string $today ): array {
		$out = array();
		foreach ( $questions as $pregunta ) {
			if ( ! empty( $pregunta['required'] ) && ProcedureQuestions::valid_delivery( $pregunta )
				&& ProcedureInput::is_valid_date( $today ) && (string) $pregunta['closes_at'] < $today ) {
				$out[] = $pregunta;
			}
		}
		return $out;
	}












	public static function may_be_incomplete( array $spec, array $questions, string $today ): bool {
		return true === PeopleSpec::sanitize( $spec )['enabled'] || array() !== self::overdue( $questions, $today );
	}
}

}








namespace Prc\Domain;

use Prc\Meta\ProcedureMetaKeys;
















if ( ! class_exists( 'Prc\Domain\CentreEligibility', false ) ) {
final class CentreEligibility {




	public const MAX_CODES = 2000;




	public const MAX_VALUES = 200;




	public const CONTACT_HINT = ' Si cree que es un error, escriba a los correos de contacto del procedimiento.';







	public static function fold( string $value ): string {
		return mb_strtolower( trim( (string) preg_replace( '/\s+/u', ' ', $value ) ), 'UTF-8' );
	}







	public static function values( $raw ): array {
		if ( is_string( $raw ) ) {
			$raw = preg_split( '/\R/u', $raw );
		}
		$out = array();
		foreach ( is_array( $raw ) ? $raw : array() as $valor ) {
			$valor = is_scalar( $valor ) ? trim( (string) preg_replace( '/\s+/u', ' ', (string) $valor ) ) : '';
			if ( '' !== $valor && ! isset( $out[ self::fold( $valor ) ] ) ) {
				$out[ self::fold( $valor ) ] = $valor;
			}
		}
		return array_slice( array_values( $out ), 0, self::MAX_VALUES );
	}











	public static function parse_codes( $raw ): array {
		$trozos = is_array( $raw ) ? $raw : preg_split( '/[\s,;]+/u', is_scalar( $raw ) ? (string) $raw : '' );
		$codes  = array();
		$malos  = array();
		foreach ( (array) $trozos as $trozo ) {
			$trozo = is_scalar( $trozo ) ? trim( (string) $trozo ) : '';
			if ( '' === $trozo ) {
				continue;
			}
			if ( self::is_code( $trozo ) ) {

				$codes[ strtoupper( $trozo ) ] = true;
			} else {
				$malos[ $trozo ] = true;
			}
		}
		return array(
			'codes'   => array_map( 'strval', array_keys( $codes ) ),
			'invalid' => array_map( 'strval', array_keys( $malos ) ),
		);
	}







	public static function codes( $raw ): array {
		return array_slice( self::parse_codes( $raw )['codes'], 0, self::MAX_CODES );
	}









	public static function is_code( string $code ): bool {
		return (bool) preg_match( '/^[A-Za-z0-9]{3,20}$/', $code );
	}










	public static function input( array $raw, bool $schools ): array {
		$alloweda = self::parse_codes( $raw['allowed_centres'] ?? '' );
		$errors   = array() !== $alloweda['invalid'] || count( $alloweda['codes'] ) > self::MAX_CODES ? array( 'allowed_centres' ) : array();
		return array(
			'data'   => array(
				'centre_types'    => $schools ? self::values( $raw['centre_types'] ?? array() ) : array(),
				'islands'         => $schools ? self::values( $raw['islands'] ?? array() ) : array(),
				'allowed_centres' => $schools ? array_slice( $alloweda['codes'], 0, self::MAX_CODES ) : array(),
			),
			'errors' => $schools ? $errors : array(),
		);
	}







	public static function rules( array $raw ): array {
		$titular = is_array( $raw['ownership'] ?? null ) ? array_map( 'strval', array_filter( $raw['ownership'], 'is_scalar' ) ) : array();
		return array(
			'ownership' => array_values( array_intersect( array_keys( ProcedureMetaKeys::ownerships() ), $titular ) ),
			'types'     => self::values( $raw['types'] ?? array() ),
			'islands'   => self::values( $raw['islands'] ?? array() ),
			'codes'     => self::codes( $raw['codes'] ?? array() ),
		);
	}







	public static function form_values( array $rules ): array {
		$rules = self::rules( $rules );
		return array(
			'centre_types'    => $rules['types'],
			'islands'         => $rules['islands'],
			'allowed_centres' => implode( "\n", $rules['codes'] ),
		);
	}









	public static function restricted( array $rules ): bool {
		$titular = (array) ( $rules['ownership'] ?? array() );
		return ( array() !== $titular && count( $titular ) < count( ProcedureMetaKeys::ownerships() ) )
			|| array() !== (array) ( $rules['types'] ?? array() )
			|| array() !== (array) ( $rules['islands'] ?? array() )
			|| array() !== (array) ( $rules['codes'] ?? array() );
	}
















	public static function why_not( array $rules, string $code, array $centre, bool $catalogue = true ): string {
		$rules = self::rules( $rules );
		if ( ! self::restricted( $rules ) ) {
			return '';
		}
		if ( array() !== $rules['codes'] && ! in_array( strtoupper( trim( $code ) ), $rules['codes'], true ) ) {
			return 'Este procedimiento es solo para una lista cerrada de centros, y el suyo no está en ella.';
		}
		$resto = self::without_list( $rules, $catalogue );
		if ( ! self::restricted( $resto ) ) {
			return '';
		}
		if ( array() === $centre ) {
			return 'Este procedimiento es solo para algunos centros, y el suyo no está en el catálogo de centros, así que no se puede comprobar si es uno de ellos.' . self::CONTACT_HINT;
		}
		return self::why_not_attributes( $resto, $centre );
	}












	public static function with_contact( string $why, string $contact ): string {
		$contact = trim( $contact );
		if ( '' === $why || '' === $contact ) {
			return $why;
		}
		$why = str_replace( self::CONTACT_HINT, '', $why );
		return $why . ' Si cree que es un error, escriba a ' . $contact . '.';
	}











	public static function describe( array $rules ): string {
		$rules   = self::rules( $rules );
		$titular = $rules['ownership'];
		if ( ! self::restricted( $rules ) ) {
			return 'Todos los centros';
		}
		$texto = 1 === count( $titular ) ? (string) ProcedureMetaKeys::ownerships()[ $titular[0] ] : 'Centros';
		if ( array() !== $rules['types'] ) {
			$texto .= ' de tipo ' . self::either( $rules['types'] );
		}
		if ( array() !== $rules['islands'] ) {
			$texto .= ' de ' . self::either( $rules['islands'] );
		}
		if ( array() === $rules['codes'] ) {
			return $texto;
		}
		return 'Centros' === $texto ? 'Solo los centros de una lista cerrada' : $texto . ', solo los de una lista cerrada';
	}








	private static function without_list( array $rules, bool $catalogue ): array {
		$rules['codes'] = array();
		if ( ! $catalogue ) {
			$rules['ownership'] = array();
		}
		return $rules;
	}








	private static function why_not_attributes( array $rules, array $centre ): string {
		$titular = $rules['ownership'];
		if ( array() !== $titular && ! in_array( (string) ( $centre['ownership'] ?? '' ), $titular, true ) ) {
			$rotulos = array_map( 'lcfirst', array_intersect_key( ProcedureMetaKeys::ownerships(), array_flip( $titular ) ) );
			return 'Este procedimiento es solo para ' . implode( ' y ', $rotulos ) . '.';
		}
		if ( ! self::takes( $rules['types'], (string) ( $centre['type'] ?? '' ) ) ) {
			return 'Este procedimiento es solo para centros de tipo ' . self::either( $rules['types'] ) . '.';
		}
		if ( ! self::takes( $rules['islands'], (string) ( $centre['island'] ?? '' ) ) ) {
			return 'Este procedimiento es solo para centros de ' . self::either( $rules['islands'] ) . '.';
		}
		return '';
	}













	public static function warnings( array $rules, bool $empty_catalogue, bool $no_private ): array {
		$rules   = self::rules( $rules );
		$out     = array();
		$titular = $rules['ownership'];
		if ( $no_private && 1 === count( $titular ) ) {
			$out['ownership'] = ProcedureMetaKeys::OWNERSHIP_PUBLIC === $titular[0]
				? 'No hay ningún tipo de centro marcado como privado en los ajustes: hoy todos los centros cuentan como públicos, salvo los que el catálogo marque expresamente, así que este límite no deja fuera a casi nadie.'
				: 'No hay ningún tipo de centro marcado como privado en los ajustes: hoy ningún centro cuenta como privado, salvo los que el catálogo marque expresamente, así que nadie o casi nadie puede solicitar.';
		}
		if ( $empty_catalogue && self::restricted( $rules ) ) {
			$resto = self::without_list( $rules, false );
			if ( self::restricted( $resto ) ) {
				$out['catalogue'] = 'El catálogo de centros está vacío: con los tipos o las islas que pide este procedimiento, ningún centro puede solicitar hasta que se cargue.';
			} elseif ( array() !== $rules['codes'] ) {
				$out['catalogue'] = 'El catálogo de centros está vacío: solo pueden solicitar los centros de la lista cerrada.';
			} else {
				$out['catalogue'] = 'El catálogo de centros está vacío, así que la titularidad no se puede comprobar: puede solicitar cualquier centro.';
			}
		}
		return $out;
	}








	private static function takes( array $allowed, string $value ): bool {
		return array() === $allowed || in_array( self::fold( $value ), array_map( array( self::class, 'fold' ), $allowed ), true );
	}







	private static function either( array $allowed ): string {
		$ultimo = (string) array_pop( $allowed );
		return array() === $allowed ? $ultimo : implode( ', ', $allowed ) . ' o ' . $ultimo;
	}
}

}








namespace Prc\Centre;

use Prc\Meta\ProcedureMetaKeys;








if ( ! class_exists( 'Prc\Centre\CentreCatalogue', false ) ) {
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
		$out = array();
		foreach ( self::all() as $code => $centre ) {
			if ( ! is_array( $centre ) ) {
				continue;
			}
			$fila = array(
				'code'   => (string) $code,
				'active' => ! isset( $centre['active'] ) || (bool) $centre['active'],
			);
			foreach ( array( 'name', 'island', 'municipality', 'type', 'zone' ) as $campo ) {
				$fila[ $campo ] = isset( $centre[ $campo ] ) && is_scalar( $centre[ $campo ] ) ? (string) $centre[ $campo ] : '';
			}
			$titular = isset( $centre['ownership'] ) && is_string( $centre['ownership'] ) ? $centre['ownership'] : '';
			if ( '' !== ProcedureMetaKeys::in_list( $titular, ProcedureMetaKeys::ownerships() ) ) {
				$fila['ownership'] = $titular;
			}
			$out[] = $fila;
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

}








namespace Prc\Centre;

use Prc\Meta\ProcedureMetaKeys;
use RuntimeException;








if ( ! class_exists( 'Prc\Centre\CentreCatalogueSync', false ) ) {
final class CentreCatalogueSync {

	public const OPTION_MANIFEST_URL = 'prc_centres_manifest_url';

	public const OPTION_CATALOGUE_URL = 'prc_centres_catalogue_url';

	public const OPTION_LOCK = 'prc_centres_sync_lock';

	public const LOCK_TTL = 300;

	public const CRON_HOOK = 'prc_centres_cron_sync';

	public const HTTP_TIMEOUT = 30;





	public const SOURCE_ZONE_FIELD = 'cep';















	public static function record( array $item, string $code, string $name, bool $is_active ): array {
		$texto   = static function ( string $campo ) use ( $item ): string {
			return isset( $item[ $campo ] ) && is_scalar( $item[ $campo ] ) ? trim( (string) $item[ $campo ] ) : '';
		};
		$zona    = $texto( self::SOURCE_ZONE_FIELD );
		$record  = array(
			'code'         => $code,
			'name'         => $name,
			'island'       => $texto( 'island' ),
			'municipality' => $texto( 'municipality' ),
			'type'         => $texto( 'type' ),
			'zone'         => 1 === preg_match( '/^\d{8}$/', $zona ) ? $zona : '',
			'active'       => $is_active,
		);
		$titular = strtolower( $texto( 'ownership' ) );
		if ( in_array( $titular, array( ProcedureMetaKeys::OWNERSHIP_PUBLIC, ProcedureMetaKeys::OWNERSHIP_PRIVATE ), true ) ) {
			$record['ownership'] = $titular;
		}
		return $record;
	}






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

				$indexed[ $code ] = self::record( $item, $code, $name, $is_active );
			}


			update_option( CentreCatalogue::OPTION_CATALOGUE, $indexed, false );

			\Prc\Domain\CentreCatalog::flush();

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

}








namespace Prc\Domain;

use Prc\Meta\ProcedureMetaKeys;






















if ( ! class_exists( 'Prc\Domain\CentreCatalog', false ) ) {
final class CentreCatalog {




	public const SEARCH_LIMIT = 20;





	public const OPTION_PRIVATE_TYPES = 'prc_private_centre_types';





	private const CACHE_GROUP = 'prc_centres';






	private static $memo = array(
		'key'   => null,
		'all'   => array(),
		'index' => null,
	);






	public static function register(): void {
		foreach ( array( 'added_option', 'updated_option', 'deleted_option' ) as $accion ) {
			add_action( $accion, array( self::class, 'on_option' ) );
		}
	}







	public static function on_option( $option ): void {
		if ( in_array( $option, array( \Prc\Centre\CentreCatalogue::OPTION_CATALOGUE, self::OPTION_PRIVATE_TYPES ), true ) ) {
			self::flush();
		}
	}






	public static function flush(): void {
		self::$memo = array(
			'key'   => null,
			'all'   => array(),
			'index' => null,
		);
		wp_cache_delete( 'last_changed', self::CACHE_GROUP );
	}











	public static function all(): array {








		$clave = self::memo_key();
		if ( $clave === self::$memo['key'] ) {
			return self::$memo['all'];
		}
		$default = class_exists( '\Prc\Centre\CentreCatalogue' ) ? \Prc\Centre\CentreCatalogue::for_domain() : array();
		if ( empty( $default ) ) {
			$default = self::from_uploads();
		}
		$raw = apply_filters( 'prc_centres', $default );
		$raw = is_array( $raw ) ? $raw : array();

		$privados = self::private_types();
		$out      = array();
		$nombres  = array();
		foreach ( $raw as $item ) {
			$centro = is_array( $item ) ? self::normalise( $item, $privados ) : null;
			if ( null === $centro || isset( $nombres[ $centro['code'] ] ) ) {
				continue;
			}
			$nombres[ $centro['code'] ] = $centro['name'];
			$out[]                      = $centro;
		}

		foreach ( $out as $i => $centro ) {
			$out[ $i ]['zone_name'] = '' !== $centro['zone'] ? (string) ( $nombres[ $centro['zone'] ] ?? '' ) : '';
		}
		self::$memo = array(
			'key'   => $clave,
			'all'   => $out,
			'index' => null,
		);
		return $out;
	}






	private static function memo_key(): string {
		global $wp_filter;
		$clave = (string) wp_cache_get_last_changed( self::CACHE_GROUP );
		foreach ( array( 'prc_centres', 'prc_private_centre_types' ) as $hook ) {
			$clave  .= '|' . $hook;
			$ganchos = isset( $wp_filter[ $hook ] ) && $wp_filter[ $hook ] instanceof \WP_Hook ? $wp_filter[ $hook ]->callbacks : array();
			foreach ( $ganchos as $prioridad => $funciones ) {
				$clave .= ':' . $prioridad . '=' . implode( ',', array_keys( $funciones ) );
			}
		}
		return $clave;
	}






	public static function index(): array {
		$todos = self::all();
		if ( null === self::$memo['index'] ) {
			self::$memo['index'] = array_column( $todos, null, 'code' );
		}
		return self::$memo['index'];
	}







	public static function find( string $code ): array {
		$code = trim( $code );
		if ( '' === $code ) {
			return array();
		}
		return self::index()[ $code ] ?? array();
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










	public static function values( string $field ): array {
		$out = array();
		foreach ( self::all() as $centro ) {
			$valor = (string) ( $centro[ $field ] ?? '' );
			if ( '' !== $valor && ! isset( $out[ CentreEligibility::fold( $valor ) ] ) ) {
				$out[ CentreEligibility::fold( $valor ) ] = $valor;
			}
		}
		$out = array_values( $out );
		usort( $out, 'strnatcasecmp' );
		return $out;
	}














	public static function choices( bool $wanted, array $types, array $islands ): array {
		if ( ! $wanted ) {
			return array(
				'types'   => array(),
				'islands' => array(),
			);
		}
		return array(
			'types'   => self::chosen_first( $types, self::values( 'type' ) ),
			'islands' => self::chosen_first( $islands, self::values( 'island' ) ),
		);
	}









	private static function chosen_first( array $chosen, array $catalogue ): array {
		$out = CentreEligibility::values( array_merge( $chosen, $catalogue ) );
		usort( $out, 'strnatcasecmp' );
		return $out;
	}









	public static function private_types(): array {
		$guardados = get_option( self::OPTION_PRIVATE_TYPES, array() );





		$tipos = apply_filters( 'prc_private_centre_types', is_array( $guardados ) ? $guardados : array() );
		return array_map( array( CentreEligibility::class, 'fold' ), CentreEligibility::values( $tipos ) );
	}








	private static function normalise( array $item, array $privados ): ?array {
		$code = self::text( $item, 'code' );
		if ( '' === $code ) {
			return null;
		}
		$tipo      = self::text( $item, 'type' );
		$explicita = ProcedureMetaKeys::in_list( $item['ownership'] ?? '', ProcedureMetaKeys::ownerships() );
		$derivada  = in_array( CentreEligibility::fold( $tipo ), $privados, true ) ? ProcedureMetaKeys::OWNERSHIP_PRIVATE : ProcedureMetaKeys::OWNERSHIP_PUBLIC;
		return array(
			'code'         => $code,
			'name'         => self::text( $item, 'name' ),
			'ownership'    => '' !== $explicita ? $explicita : $derivada,
			'kind'         => ProcedureMetaKeys::in_list( $item['kind'] ?? '', ProcedureMetaKeys::centre_kinds(), ProcedureMetaKeys::KIND_SCHOOL ),
			'island'       => self::text( $item, 'island' ),
			'municipality' => self::text( $item, 'municipality' ),
			'type'         => $tipo,
			'zone'         => self::text( $item, 'zone' ),
			'zone_name'    => '',
			'active'       => ! array_key_exists( 'active', $item ) || (bool) $item['active'],
		);
	}








	private static function text( array $item, string $field ): string {
		return isset( $item[ $field ] ) && is_scalar( $item[ $field ] ) ? trim( (string) $item[ $field ] ) : '';
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

}








namespace Prc\Access;

use Prc\Domain\CentreCatalog;
use Prc\Meta\ProcedureMetaKeys;










if ( ! class_exists( 'Prc\Access\CentreScope', false ) ) {
final class CentreScope {




	public const META_KEY = 'prc_centre_code';





	public const GROUP_META_KEY = 'prc_rural_group_code';






	public static function meta_key(): string {





		$key = apply_filters( 'prc_centre_code_meta_key', self::META_KEY );
		return is_string( $key ) ? trim( $key ) : '';
	}







	public static function code_for( int $user_id = 0 ): string {
		return self::read( $user_id, self::meta_key() );
	}






	public static function group_meta_key(): string {





		$key = apply_filters( 'prc_rural_group_code_meta_key', self::GROUP_META_KEY );
		return is_string( $key ) ? trim( $key ) : '';
	}







	public static function group_code_for( int $user_id = 0 ): string {
		return self::read( $user_id, self::group_meta_key() );
	}











	public static function codes_for( int $user_id = 0 ): array {
		$out  = array();
		$code = self::code_for( $user_id );
		if ( '' !== $code ) {
			$out[ $code ] = (string) ( CentreCatalog::find( $code )['kind'] ?? ProcedureMetaKeys::KIND_SCHOOL );
		}
		$group = self::group_code_for( $user_id );
		if ( '' !== $group && ! isset( $out[ $group ] ) ) {
			$out[ $group ] = ProcedureMetaKeys::KIND_RURAL_GROUP;
		}
		return $out;
	}








	private static function read( int $user_id, string $key ): string {
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
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

}









namespace Prc\Access;

use Prc\Domain\CentreCatalog;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ProcedurePostType;





















if ( ! class_exists( 'Prc\Access\ObserverScope', false ) ) {
final class ObserverScope {




	public const META_KEY = 'prc_zone';














	public const ALLOWED_COLUMNS = array(
		'date',
		'centre',
		'code',
		'island',
		'municipality',
		'centre_type',
		'ownership',
		'zone',
		'zone_code',
		'applicant',
		'email',
		'position',
		'coordinator',
		'coordinator_email',
		'person_name',
		'person_surname',
		'person_email',
		'person_phone',
		'person_role',
		'person_group',
		'state',
		'score',
	);






	public const DEFAULT_GROUPS = array( 'application', 'centre', 'coordination' );






	public static function meta_key(): string {





		$key = apply_filters( 'prc_zone_meta_key', self::META_KEY );
		return is_string( $key ) ? trim( $key ) : '';
	}







	public static function zones_for( int $user_id ): array {
		$key = self::meta_key();
		if ( $user_id <= 0 || '' === $key ) {
			return array();
		}
		return self::parse_zones( get_user_meta( $user_id, $key, true ) );
	}










	public static function is_observable( int $procedure_id ): bool {
		return $procedure_id > 0
			&& ProcedurePostType::POST_TYPE === get_post_type( $procedure_id )
			&& 'publish' === get_post_status( $procedure_id )
			&& (bool) get_post_meta( $procedure_id, ProcedureMetaKeys::OBSERVABLE, true );
	}











	public static function observers_of( string $code ): array {
		$zona = '' !== $code ? (string) ( CentreCatalog::find( $code )['zone'] ?? '' ) : '';
		$meta = self::meta_key();
		if ( '' === $zona || '' === $meta ) {
			return array();
		}


		$ids = get_users(
			array(
				'capability'   => ProcedureAccess::CAP_VIEW_APPLICATIONS,
				'fields'       => 'ID',
				'meta_key'     => $meta,
				'meta_compare' => 'EXISTS',
			)
		);
		$ids = array_map( 'intval', (array) $ids );
		if ( array() === $ids ) {
			return array();
		}
		global $wpdb;
		$huecos = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		$filas = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT user_id, meta_value FROM ' . $wpdb->usermeta . ' WHERE meta_key = %s AND user_id IN (' . $huecos . ')',
				array_merge( array( $meta ), $ids )
			)
		);

		$out = array();
		foreach ( (array) $filas as $fila ) {
			if ( in_array( $zona, self::parse_zones( maybe_unserialize( $fila->meta_value ) ), true ) ) {
				$out[ (int) $fila->user_id ] = (int) $fila->user_id;
			}
		}
		$out = array_values( $out );
		return $out;
	}











	public static function parse_zones( $raw ): array {
		if ( is_string( $raw ) ) {
			$raw = preg_split( '/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY );
		}
		$out = array();
		foreach ( is_array( $raw ) ? $raw : array() as $valor ) {
			$codigo = is_string( $valor ) ? trim( $valor ) : '';
			if ( CentreScope::is_code( $codigo ) ) {
				$out[ $codigo ] = $codigo;
			}
		}
		return array_values( $out );
	}








	public static function codes_in( array $zones, array $catalogue ): array {
		if ( array() === $zones ) {
			return array();
		}
		$buscadas = array_flip( $zones );
		$out      = array();
		foreach ( $catalogue as $codigo => $centro ) {
			$zona = is_array( $centro ) ? (string) ( $centro['zone'] ?? '' ) : '';
			if ( '' !== $zona && isset( $buscadas[ $zona ] ) ) {
				$out[] = (string) $codigo;
			}
		}
		return $out;
	}








	public static function is_allowed( string $column ): bool {
		return in_array( $column, self::ALLOWED_COLUMNS, true ) || (bool) preg_match( '/^q[1-9][0-9]*(:r[1-9][0-9]*)?$/', $column );
	}







	public static function allowed( array $groups ): array {
		$out = array();
		foreach ( $groups as $grupo => $columnas ) {
			$quedan = array();
			foreach ( (array) $columnas as $clave => $rotulo ) {
				if ( self::is_allowed( (string) $clave ) ) {
					$quedan[ (string) $clave ] = $rotulo;
				}
			}
			if ( array() !== $quedan ) {
				$out[ (string) $grupo ] = $quedan;
			}
		}
		return $out;
	}










	public static function columns( $chosen, array $groups ): array {
		$permitidas = self::allowed( $groups );
		$elegidas   = is_array( $chosen ) ? array_flip( array_filter( $chosen, 'is_string' ) ) : array();
		$out        = array();
		foreach ( $permitidas as $columnas ) {
			$out += array_intersect_key( $columnas, $elegidas );
		}
		if ( array() !== $out ) {
			return $out;
		}
		foreach ( self::DEFAULT_GROUPS as $grupo ) {
			$out += (array) ( $permitidas[ $grupo ] ?? array() );
		}
		return $out;
	}
}

}









namespace Prc\Access;

use Prc\Domain\CentreCatalog;
use Prc\Domain\CentreEligibility;
use Prc\Domain\PeopleSpec;
use Prc\Domain\ProcedureInput;
use Prc\Domain\ProcedureQuestions;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ApplicationPostType;
use Prc\PostType\ProcedurePostType;
use Prc\Taxonomy\ProcedureTaxonomies;


















if ( ! class_exists( 'Prc\Access\ProcedureAccess', false ) ) {
final class ProcedureAccess {




	public const USER_AREA_META = 'prc_area';




	public const CAP_APPLY            = 'prc_apply';
	public const CAP_APPLY_AS_TEACHER = 'prc_apply_as_teacher';




	public const CAP_MANAGE_PROCEDURES = 'prc_manage_procedures';




	public const CAP_REVIEW = 'prc_review_applications';




	public const CAP_ALL_AREAS = 'prc_manage_all_areas';




	public const CAP_MANAGE = 'prc_manage_app';






	public const CAP_EDIT_PEOPLE = 'prc_edit_people';





	public const CAP_VIEW_APPLICATIONS = 'prc_view_applications';





	public const VIEW_OWN      = 'own';
	public const VIEW_DOCUMENT = 'document';










	private const REASONS = array(
		'session'        => 'Debe iniciar sesión con su cuenta personal para presentar la solicitud.',
		'role_teachers'  => 'Su perfil no presenta solicitudes. Este procedimiento lo solicita cada docente con su cuenta personal: si ha entrado con otra, salga y entre con la suya.',
		'role'           => 'Su perfil no presenta solicitudes de centro. Si ha entrado con la cuenta genérica del centro, salga y entre con su cuenta personal; si ya es su cuenta personal, pida a quien administra el aplicativo que le asigne el cargo de dirección.',
		'teacher_closed' => 'El plazo está cerrado o este procedimiento no está disponible para su cuenta.',
		'teachers_only'  => 'Este procedimiento lo solicita cada docente a título personal, con su propia cuenta. La dirección no presenta solicitud en nombre del centro.',
		'heads_only'     => 'Este procedimiento lo presenta la dirección del centro, no cada docente.',
		'no_code'        => 'Su cuenta no tiene centro asignado. Si es la cuenta genérica del centro, salga y entre con su cuenta personal; si ya es la personal, pida a quien administra el aplicativo que le asigne el código de centro.',
		'foreign_code'   => 'Ese código de centro no es de su cuenta, así que no puede solicitar con él.',
		'unpublished'    => 'Ese procedimiento no existe o todavía no está publicado.',
		'kind'           => 'Este procedimiento solo admite solicitudes de %s, y su cuenta no tiene un código así.',
		'fallback'       => 'Su centro (%s) no cumple las condiciones de este procedimiento: consulte «Pueden solicitar» en el resumen de la ficha.',
	);









	private static $rest_checked = 0;






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




			if ( ProcedurePostType::POST_TYPE === $tipo ) {
				$por_rest           = self::$rest_checked === $post_id;
				self::$rest_checked = 0;
				if ( ! $por_rest && self::is_unready_publication( $post_id, (string) ( $data['post_status'] ?? '' ), $postarr['tax_input'][ ProcedureTaxonomies::COURSE ] ?? null ) ) {
					$data['post_status'] = 'draft';
				}
			}
		} elseif ( ProcedurePostType::POST_TYPE === $tipo ) {
			$data['post_parent'] = 0;
		}
		return $data;
	}
















	private static function is_unready_publication( int $post_id, string $status, $course, ?array &$why = null ): bool {
		$why        = array();
		$publicados = array( 'publish', 'future' );
		if ( ! in_array( $status, $publicados, true ) || ( $post_id > 0 && in_array( get_post_status( $post_id ), $publicados, true ) ) ) {
			return false;
		}
		if ( null === $course ) {
			$course = $post_id > 0 ? wp_get_post_terms( $post_id, ProcedureTaxonomies::COURSE, array( 'fields' => 'ids' ) ) : array();
		}
		$curso = 0;
		foreach ( is_array( $course ) ? $course : explode( ',', (string) $course ) as $valor ) {
			$term = is_numeric( $valor ) ? get_term( (int) $valor, ProcedureTaxonomies::COURSE ) : get_term_by( 'name', trim( (string) $valor ), ProcedureTaxonomies::COURSE );
			if ( $term instanceof \WP_Term && 0 === $curso ) {
				$curso = (int) $term->term_id;
			}
		}
		$preguntas = ProcedureQuestions::sanitize( $post_id > 0 ? get_post_meta( $post_id, ProcedureMetaKeys::QUESTIONS, true ) : array() );
		$abre      = $post_id > 0 ? (string) get_post_meta( $post_id, ProcedureMetaKeys::OPENS_AT, true ) : '';
		$why       = ProcedureInput::not_ready( $curso, $preguntas, $abre );
		return array() !== $why;
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
		$final   = null;
		if ( $request->has_param( ProcedureTaxonomies::AREA ) ) {
			$final = self::resolve_area_assignment( $post_id, (array) $request->get_param( ProcedureTaxonomies::AREA ) );
		} elseif ( 0 === $post_id && ! self::can_edit_all_areas() ) {
			$assigned = self::user_areas();
			$final    = array() === $assigned
				? new \WP_Error( 'prc_area_forbidden', self::scope_assignment_message( get_current_user_id() ), array( 'status' => 403 ) )
				: self::resolve_area_assignment( 0, $assigned );
		}
		if ( is_wp_error( $final ) ) {
			return $final;
		}
		if ( 0 === $post_id && ! self::can_edit_all_areas() && in_array( $request->get_param( 'status' ), array( 'publish', 'future', 'private' ), true ) ) {
			return new \WP_Error( 'prc_scope_draft_first', 'Cree el procedimiento como borrador antes de publicarlo, para que tenga ámbito desde el principio.', array( 'status' => 400 ) );
		}



		$faltan = array();
		if ( self::is_unready_publication( $post_id, (string) $request->get_param( 'status' ), $request->has_param( ProcedureTaxonomies::COURSE ) ? $request->get_param( ProcedureTaxonomies::COURSE ) : null, $faltan ) ) {
			return new \WP_Error( 'prc_not_ready', 'Todavía no se puede publicar. ' . implode( ' ', $faltan ), array( 'status' => 400 ) );
		}
		self::$rest_checked = $post_id;
		if ( null !== $final ) {
			$request->set_param( ProcedureTaxonomies::AREA, $final );
		}
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











	public static function can_edit_custom_js( int $user_id, int $post_id ): bool {
		return self::is_manager( $user_id ) && user_can( $user_id, 'unfiltered_html' ) && self::can_edit( $user_id, $post_id );
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













	public static function area_managers( int $procedure_id ): array {
		$theirs = $procedure_id > 0 ? self::post_areas( $procedure_id ) : array();
		if ( array() === $theirs ) {
			return array();
		}



		$candidatos = get_users(
			array(
				'capability'   => self::CAP_MANAGE_PROCEDURES,
				'fields'       => 'all_with_meta',
				'meta_key'     => self::USER_AREA_META,
				'meta_compare' => 'EXISTS',
			)
		);
		$out        = array();
		foreach ( (array) $candidatos as $user ) {
			if ( ! $user instanceof \WP_User || self::can_edit_all_areas( (int) $user->ID ) ) {
				continue;
			}
			if ( array() !== array_intersect( self::scope_areas( (int) $user->ID ), $theirs ) ) {
				$out[] = (int) $user->ID;
			}
		}
		return $out;
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
		return self::can_open( $user_id, $post_id ) && '' === ProcedureState::why_not_archivable( ProcedureState::of_post( $post_id ) );
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



















	public static function can_duplicate( int $user_id, int $post_id, ?array &$areas = null ): bool {
		$areas = null;

		if ( ProcedurePostType::POST_TYPE !== get_post_type( $post_id ) || 'trash' === get_post_status( $post_id ) || ! self::can_open( $user_id, $post_id ) || ! self::can_manage_procedures( $user_id ) ) {
			return false;
		}
		$suyos = self::post_areas( $post_id );
		$areas = self::can_edit_all_areas( $user_id ) ? $suyos : array_values( array_intersect( $suyos, self::scope_areas( $user_id ) ) );
		$areas = array() !== $areas ? $areas : self::user_areas( $user_id );
		return true;
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













	public static function can_apply( int $user_id, int $post_id = 0, string $code = '' ): bool {
		if ( $user_id <= 0 || ! user_can( $user_id, self::CAP_APPLY ) ) {
			return false;
		}
		if ( $post_id <= 0 ) {
			return user_can( $user_id, self::CAP_APPLY_AS_TEACHER ) || array() !== CentreScope::codes_for( $user_id );
		}
		if ( ProcedurePostType::POST_TYPE !== get_post_type( $post_id ) || 'publish' !== get_post_status( $post_id ) ) {
			return false;
		}
		if ( ProcedureMetaKeys::AUDIENCE_TEACHERS === get_post_meta( $post_id, ProcedureMetaKeys::AUDIENCE, true ) ) {
			return '' === $code && user_can( $user_id, self::CAP_APPLY_AS_TEACHER );
		}
		$codes = $post_id > 0 ? self::apply_codes( $user_id, $post_id ) : CentreScope::codes_for( $user_id );
		return '' === $code ? array() !== $codes : isset( $codes[ $code ] );
	}


















	public static function apply_codes( int $user_id, int $post_id ): array {
		$out = array();
		foreach ( self::kind_codes( $user_id, $post_id ) as $code => $kind ) {
			if ( '' === self::why_not_code( $post_id, (string) $code, $kind ) || self::has_application( $post_id, (string) $code ) ) {
				$out[ $code ] = $kind;
			}
		}
		return $out;
	}













	private static function has_application( int $post_id, string $code ): bool {
		$ids = get_posts(
			array(
				'post_type'        => ApplicationPostType::POST_TYPE,
				'post_parent'      => $post_id,
				'post_status'      => 'publish',
				'numberposts'      => 1,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'meta_key'         => ApplicationMetaKeys::CENTRE_CODE,
				'meta_value'       => $code,
				'suppress_filters' => true,
			)
		);
		return array() !== $ids;
	}








	private static function kind_codes( int $user_id, int $post_id ): array {
		if ( ProcedurePostType::POST_TYPE !== get_post_type( $post_id ) || 'publish' !== get_post_status( $post_id ) ) {
			return array();
		}
		if ( ProcedureMetaKeys::AUDIENCE_SCHOOLS !== ProcedureMetaKeys::in_list( get_post_meta( $post_id, ProcedureMetaKeys::AUDIENCE, true ), ProcedureMetaKeys::audiences(), ProcedureMetaKeys::AUDIENCE_SCHOOLS ) ) {
			return array();
		}
		return array_intersect( CentreScope::codes_for( $user_id ), self::centre_kinds( $post_id ) );
	}







	public static function eligibility( int $post_id ): array {
		$leer = static function ( string $clave ) use ( $post_id ): array {
			$valor = get_post_meta( $post_id, $clave, true );
			return is_array( $valor ) ? $valor : array();
		};
		return CentreEligibility::rules(
			array(
				'ownership' => $leer( ProcedureMetaKeys::OWNERSHIP ),
				'types'     => $leer( ProcedureMetaKeys::CENTRE_TYPES ),
				'islands'   => $leer( ProcedureMetaKeys::ISLANDS ),
				'codes'     => $leer( ProcedureMetaKeys::ALLOWED_CENTRES ),
			)
		);
	}













	private static function why_not_code( int $post_id, string $code, string $kind = ProcedureMetaKeys::KIND_SCHOOL ): string {
		$rules = self::eligibility( $post_id );
		if ( ProcedureMetaKeys::KIND_RURAL_GROUP === $kind ) {
			$rules['ownership'] = array();
		}
		return CentreEligibility::restricted( $rules )
			? CentreEligibility::why_not( $rules, $code, CentreCatalog::find( $code ), array() !== CentreCatalog::all() )
			: '';
	}





















	public static function why_not_apply( int $user_id, int $post_id, string $code = '' ): string {
		if ( self::can_apply( $user_id, $post_id, $code ) ) {
			return '';
		}
		$aplica    = user_can( $user_id, self::CAP_APPLY );
		$docente   = user_can( $user_id, self::CAP_APPLY_AS_TEACHER );
		$docentes  = ProcedureMetaKeys::AUDIENCE_TEACHERS === get_post_meta( $post_id, ProcedureMetaKeys::AUDIENCE, true );
		$suyos     = CentreScope::codes_for( $user_id );
		$admite    = self::centre_kinds( $post_id );
		$publicado = ProcedurePostType::POST_TYPE === get_post_type( $post_id ) && 'publish' === get_post_status( $post_id );
		$casos     = array(
			'session'        => $user_id <= 0,
			'role_teachers'  => ! $aplica && $docentes,
			'role'           => ! $aplica,
			'teacher_closed' => $docentes && $docente,
			'teachers_only'  => $docentes,
			'heads_only'     => array() === $suyos && $docente && $post_id > 0,
			'no_code'        => array() === $suyos || $post_id <= 0,
			'foreign_code'   => '' !== $code && ! isset( $suyos[ $code ] ),
			'unpublished'    => ! $publicado,
			'kind'           => array() === array_intersect( $suyos, $admite ),
		);
		$caso      = (string) array_search( true, $casos, true );
		if ( '' !== $caso ) {
			$rotulos = array_map( 'lcfirst', array_intersect_key( ProcedureMetaKeys::centre_kinds(), array_flip( $admite ) ) );
			return sprintf( self::REASONS[ $caso ], implode( ' y de ', $rotulos ) );
		}

		$codes  = array_intersect( '' !== $code ? array_intersect_key( $suyos, array( $code => true ) ) : $suyos, $admite );
		$codigo = (string) ( array_key_first( $codes ) ?? $code );
		$porque = self::why_not_code( $post_id, $codigo, (string) ( $codes[ $codigo ] ?? ProcedureMetaKeys::KIND_SCHOOL ) );
		$centro = (string) ( CentreCatalog::find( $codigo )['name'] ?? '' );
		return CentreEligibility::with_contact(
			'' !== $porque ? $porque : sprintf( self::REASONS['fallback'], trim( $centro . ', ' . $codigo, ', ' ) ),
			(string) ( ProcedureInput::emails( get_post_meta( $post_id, ProcedureMetaKeys::CONTACT_EMAILS, true ) )[0] ?? '' )
		);
	}







	public static function centre_kinds( int $post_id ): array {
		$kinds = ProcedureInput::centre_kinds( get_post_meta( $post_id, ProcedureMetaKeys::CENTRE_KINDS, true ) );
		return array() === $kinds ? array_keys( ProcedureMetaKeys::centre_kinds() ) : $kinds;
	}























	public static function can_view_application( int $user_id, int $application_id, string $purpose = '', string $question_key = '' ): bool {
		if ( $user_id <= 0 || ApplicationPostType::POST_TYPE !== get_post_type( $application_id ) ) {
			return false;
		}


		$propia = 'publish' === get_post_status( $application_id ) && self::is_own_application( $user_id, $application_id );
		if ( self::VIEW_OWN === $purpose ) {
			return $propia;
		}
		if ( $propia || self::can_review( $user_id, $application_id ) ) {
			return true;
		}
		if ( ! self::can_observe_application( $user_id, $application_id ) ) {
			return false;
		}
		if ( self::VIEW_DOCUMENT !== $purpose ) {
			return true;
		}
		$raiz = self::root_id( $application_id );
		return '' !== $question_key
			&& (bool) get_post_meta( $raiz, ProcedureMetaKeys::OBSERVER_FILES, true )
			&& in_array( $question_key, (array) get_post_meta( $raiz, ProcedureMetaKeys::OBSERVER_COLUMNS, true ), true );
	}








	private static function is_own_application( int $user_id, int $application_id ): bool {
		if ( ! user_can( $user_id, self::CAP_APPLY ) ) {
			return false;
		}
		if ( ApplicationMetaKeys::TYPE_TEACHER === get_post_meta( $application_id, ApplicationMetaKeys::APPLICANT_TYPE, true ) ) {
			return (int) get_post_field( 'post_author', $application_id ) === $user_id
				&& user_can( $user_id, self::CAP_APPLY_AS_TEACHER );
		}
		$owner = (string) get_post_meta( $application_id, ApplicationMetaKeys::CENTRE_CODE, true );
		return '' !== $owner && isset( CentreScope::codes_for( $user_id )[ $owner ] );
	}









	public static function can_deliver( int $user_id, int $application_id, array $question ): bool {
		if ( ! self::can_view_application( $user_id, $application_id ) || ! user_can( $user_id, self::CAP_APPLY ) ) {
			return false;
		}
		$procedure_id = (int) wp_get_post_parent_id( $application_id );
		$teacher      = ApplicationMetaKeys::TYPE_TEACHER === get_post_meta( $application_id, ApplicationMetaKeys::APPLICANT_TYPE, true );
		$code         = (string) get_post_meta( $application_id, ApplicationMetaKeys::CENTRE_CODE, true );
		if ( ( $teacher ? ( ProcedureMetaKeys::AUDIENCE_TEACHERS !== get_post_meta( $procedure_id, ProcedureMetaKeys::AUDIENCE, true ) || ! self::can_apply( $user_id, $procedure_id ) ) : ! isset( CentreScope::codes_for( $user_id )[ $code ] ) )
			|| 'publish' !== get_post_status( $application_id )
			|| ProcedurePostType::POST_TYPE !== get_post_type( $procedure_id )
			|| 'publish' !== get_post_status( $procedure_id ) || self::is_archived( $procedure_id ) ) {
			return false;
		}
		return ApplicationMetaKeys::REVIEW_EXCLUDED !== get_post_meta( $application_id, ApplicationMetaKeys::REVIEW_STATE, true )
			&& ProcedureQuestions::delivery_is_open( $question, ProcedureState::today() );
	}















	public static function can_edit_people( int $user_id, int $application_id ): bool {
		return '' === self::why_not_people( $user_id, $application_id );
	}








	public static function why_not_people( int $user_id, int $application_id ): string {
		$ajena = 'Solo gestiona las personas de una solicitud el equipo directivo de su centro.';
		if ( ! self::can_view_application( $user_id, $application_id ) || ! user_can( $user_id, self::CAP_APPLY ) || 'publish' !== get_post_status( $application_id ) ) {
			return $ajena;
		}
		$docente = ApplicationMetaKeys::TYPE_TEACHER === get_post_meta( $application_id, ApplicationMetaKeys::APPLICANT_TYPE, true );
		if ( ! $docente && ! isset( CentreScope::codes_for( $user_id )[ (string) get_post_meta( $application_id, ApplicationMetaKeys::CENTRE_CODE, true ) ] ) ) {
			return $ajena;
		}
		$procedure_id = (int) wp_get_post_parent_id( $application_id );
		if ( ProcedurePostType::POST_TYPE !== get_post_type( $procedure_id ) || 'publish' !== get_post_status( $procedure_id ) ) {
			return 'Ese procedimiento no existe o no está publicado.';
		}
		return PeopleSpec::why_closed(
			get_post_meta( $procedure_id, ProcedureMetaKeys::PEOPLE_SPEC, true ),
			array(
				'today'    => ProcedureState::today(),
				'archived' => self::is_archived( $procedure_id ),
				'teacher'  => $docente || ProcedureMetaKeys::AUDIENCE_TEACHERS === get_post_meta( $procedure_id, ProcedureMetaKeys::AUDIENCE, true ),
				'review'   => (string) get_post_meta( $application_id, ApplicationMetaKeys::REVIEW_STATE, true ),
				'state'    => ProcedureState::of_post( $procedure_id ),

				'late'     => ApplicationActions::is_late_open( $application_id ),
			)
		);
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









	public static function observer_zones( int $user_id ): array {
		return ObserverScope::zones_for( $user_id );
	}










	public static function observer_codes( int $user_id ): array {
		$zonas = self::observer_zones( $user_id );
		return array() === $zonas ? array() : ObserverScope::codes_in( $zonas, CentreCatalog::index() );
	}















	public static function can_observe( int $user_id, int $procedure_id = 0, ?array &$scope = null ): bool {
		$scope = 0 === $procedure_id ? null : self::observe_scope( $user_id, $procedure_id );
		if ( 0 !== $procedure_id ) {
			return null !== $scope;
		}
		return self::can_manage_procedures( $user_id )
			|| ( $user_id > 0 && user_can( $user_id, self::CAP_VIEW_APPLICATIONS ) && array() !== self::observer_codes( $user_id ) );
	}













	public static function can_observe_application( int $user_id, int $application_id ): bool {
		if ( ApplicationPostType::POST_TYPE !== get_post_type( $application_id ) || 'publish' !== get_post_status( $application_id ) ) {
			return false;
		}
		$ambito = self::observe_scope( $user_id, (int) wp_get_post_parent_id( $application_id ) );
		if ( null === $ambito ) {
			return false;
		}
		if ( ! isset( $ambito['codes'] ) ) {
			return true;
		}
		$codigo = (string) get_post_meta( $application_id, ApplicationMetaKeys::CENTRE_CODE, true );
		return '' !== $codigo && in_array( $codigo, $ambito['codes'], true );
	}








	private static function observe_scope( int $user_id, int $procedure_id ): ?array {
		if ( $user_id <= 0 || ! ObserverScope::is_observable( $procedure_id ) ) {
			return null;
		}
		if ( self::can_open( $user_id, $procedure_id ) ) {
			return array();
		}
		$codigos = user_can( $user_id, self::CAP_VIEW_APPLICATIONS ) ? self::observer_codes( $user_id ) : array();
		return array() === $codigos ? null : array( 'codes' => $codigos );
	}















	public static function map_meta_cap( array $caps, string $cap, int $user_id, array $args ): array {
		if ( 'assign_term' === $cap ) {
			return self::may_use_area( $user_id, isset( $args[0] ) ? (int) $args[0] : 0 )
				? $caps
				: array( 'do_not_allow' );
		}
		if ( self::CAP_EDIT_PEOPLE === $cap ) {
			return self::can_edit_people( $user_id, isset( $args[0] ) ? (int) $args[0] : 0 ) ? array( 'exist' ) : array( 'do_not_allow' );
		}



		if ( isset( ApplicationActions::CAPS[ $cap ] ) ) {
			return ApplicationActions::allows( $cap, $user_id, isset( $args[0] ) ? (int) $args[0] : 0 ) ? array( 'exist' ) : array( 'do_not_allow' );
		}
		if ( ! in_array( $cap, array( 'edit_post', 'delete_post', 'publish_post', 'read_post' ), true ) ) {
			return $caps;
		}
		$post_id = isset( $args[0] ) ? (int) $args[0] : 0;
		$tipo    = $post_id > 0 ? (string) get_post_type( $post_id ) : '';
		if ( ! in_array( $tipo, self::scoped_types(), true ) ) {
			return $caps;
		}

		if ( ApplicationPostType::POST_TYPE === $tipo && 'delete_post' === $cap ) {

			$permitido = ApplicationActions::may_delete( $user_id, $post_id );
		} elseif ( ApplicationPostType::POST_TYPE === $tipo ) {
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

















	private static function can_read( int $user_id, int $post_id ): bool {
		$estado = get_post_status_object( (string) get_post_status( $post_id ) );
		if ( null !== $estado && ! empty( $estado->public ) ) {
			return true;
		}
		return self::is_manager( $user_id ) || self::can_open( $user_id, $post_id );
	}

















	private static function may_use_area( int $user_id, int $term_id ): bool {
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

}









namespace Prc\Access;

use Prc\Domain\CentreCatalog;
use Prc\Domain\CentreEligibility;
use Prc\Domain\ProcedureInput;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ApplicationPostType;
use Prc\PostType\ProcedurePostType;
















if ( ! class_exists( 'Prc\Access\ApplicationActions', false ) ) {
final class ApplicationActions {




	public const CAP_WITHDRAW = 'prc_withdraw_application';




	public const CAP_RESTORE = 'prc_restore_application';




	public const CAP_EXTEND = 'prc_extend_application';





	public const CAP_SUBMIT_FOR = 'prc_submit_for_centre';






	public const CAPS = array(
		self::CAP_WITHDRAW   => 'why_not_withdraw',
		self::CAP_RESTORE    => 'why_not_restore',
		self::CAP_EXTEND     => 'why_not_extend',
		self::CAP_SUBMIT_FOR => 'why_not_submit_for',
	);






	public static function register(): void {
		add_filter( 'pre_untrash_post', array( self::class, 'guard_untrash' ), 10, 2 );
		add_filter( 'pre_delete_post', array( self::class, 'guard_delete' ), 10, 2 );
		add_filter( 'wp_untrash_post_status', array( self::class, 'untrash_status' ), 10, 3 );
	}











	public static function allows( string $cap, int $user_id, int $post_id ): bool {
		$regla = self::CAPS[ $cap ] ?? '';
		return '' !== $regla && '' === (string) call_user_func( array( self::class, $regla ), $user_id, $post_id );
	}










	public static function can_withdraw( int $user_id, int $application_id ): bool {
		return '' === self::why_not_withdraw( $user_id, $application_id );
	}














	public static function why_not_withdraw( int $user_id, int $application_id ): string {
		if ( ! self::is_live( $application_id ) || ! ProcedureAccess::can_view_application( $user_id, $application_id, ProcedureAccess::VIEW_OWN ) ) {
			return 'Solo retira una solicitud el equipo directivo de su centro, o la persona docente que la presentó.';
		}
		$procedure_id = (int) wp_get_post_parent_id( $application_id );
		if ( ProcedurePostType::POST_TYPE !== get_post_type( $procedure_id ) || 'publish' !== get_post_status( $procedure_id ) || ProcedureAccess::is_archived( $procedure_id ) ) {
			return 'Este procedimiento está cerrado: la solicitud ya no se puede retirar.';
		}
		$estado = ProcedureState::of_post( $procedure_id );
		if ( ProcedureState::accepts_applications( $estado ) || ProcedureState::late_open( $estado, self::late_until( $application_id ), ProcedureState::today() ) ) {
			return '';
		}
		return 'El plazo de solicitud está cerrado, así que la solicitud ya no se puede retirar. Si tiene que retirarla, pídalo a quien gestiona el procedimiento.';
	}









	public static function late_until( int $application_id ): string {
		if ( $application_id <= 0 ) {
			return '';
		}
		$valor = get_post_meta( $application_id, ApplicationMetaKeys::LATE_UNTIL, true );
		$valor = is_string( $valor ) ? trim( $valor ) : '';
		return ProcedureInput::is_valid_date( $valor ) ? $valor : '';
	}










	public static function is_late_open( int $application_id ): bool {
		if ( ! self::is_live( $application_id ) ) {
			return false;
		}
		$late = self::late_until( $application_id );
		return '' !== $late && ProcedureState::late_open( ProcedureState::of_post( (int) wp_get_post_parent_id( $application_id ) ), $late, ProcedureState::today() );
	}







	public static function late_notice( int $application_id ): string {
		if ( ! self::is_late_open( $application_id ) ) {
			return '';
		}
		return 'Esta solicitud tiene de plazo hasta el ' . self::day( self::late_until( $application_id ) ) . ', incluido, para completarla o modificarla, aunque el plazo general esté cerrado.';
	}








	public static function why_not_late_date( string $until, string $today ): string {
		if ( ! ProcedureInput::is_valid_date( $until ) ) {
			return 'Escriba el último día de la prórroga como una fecha válida.';
		}
		return $until < $today ? 'La prórroga no puede terminar antes de hoy.' : '';
	}











	public static function why_not_extend( int $user_id, int $application_id ): string {
		if ( ! self::is_live( $application_id ) ) {
			return 'Esa solicitud no existe o está retirada.';
		}
		return self::why_not_reviewer( $user_id, $application_id );
	}













	public static function why_not_retire( int $user_id, int $application_id ): string {
		return self::why_not_extend( $user_id, $application_id );
	}











	public static function why_not_restore( int $user_id, int $application_id ): string {
		if ( ApplicationPostType::POST_TYPE !== get_post_type( $application_id ) || 'trash' !== get_post_status( $application_id ) ) {
			return 'Esa solicitud no está retirada.';
		}
		$porque = self::why_not_reviewer( $user_id, $application_id );
		if ( '' !== $porque ) {
			return $porque;
		}
		return null !== self::live_twin( $application_id )
			? 'No se puede restaurar: el centro ya ha presentado otra solicitud a este procedimiento, y serían dos. Si hay que quedarse con esta, retire antes la otra.'
			: '';
	}











	public static function guard_untrash( $untrash, $post ) {
		if ( null !== $untrash || ! $post instanceof \WP_Post || ApplicationPostType::POST_TYPE !== $post->post_type ) {
			return $untrash;
		}


		return null === self::live_twin( (int) $post->ID ) && ! ProcedureAccess::is_archived( (int) $post->ID ) ? null : false;
	}

















	public static function may_delete( int $user_id, int $application_id ): bool {
		if ( ProcedureAccess::is_archived( $application_id ) ) {
			return false;
		}
		return 'trash' === get_post_status( $application_id )
			? ProcedureAccess::is_manager( $user_id )
			: ProcedureAccess::can_review( $user_id, $application_id );
	}














	public static function guard_delete( $delete, $post ) {
		if ( null !== $delete || ! $post instanceof \WP_Post || ApplicationPostType::POST_TYPE !== $post->post_type ) {
			return $delete;
		}
		if ( 'trash' !== $post->post_status ) {



			return self::trash_enabled() || ! self::inside_core_trash() ? $delete : false;
		}
		return ProcedureAccess::is_manager( get_current_user_id() ) ? null : false;
	}










	public static function trash_enabled(): bool {
		return (bool) apply_filters( 'prc_trash_enabled', (bool) EMPTY_TRASH_DAYS );
	}









	private static function inside_core_trash(): bool {
		$pila = wp_debug_backtrace_summary( null, 0, false );
		return is_array( $pila ) && in_array( 'wp_trash_post', $pila, true );
	}













	public static function untrash_status( $status, $post_id = 0, $previous_status = '' ): string {
		if ( ApplicationPostType::POST_TYPE === get_post_type( (int) $post_id ) && '' !== (string) $previous_status ) {
			return (string) $previous_status;
		}
		return (string) $status;
	}







	public static function live_twin( int $application_id ): ?int {
		$args = array(
			'post_type'        => ApplicationPostType::POST_TYPE,
			'post_parent'      => (int) wp_get_post_parent_id( $application_id ),
			'post_status'      => 'publish',
			'post__not_in'     => array( $application_id ),
			'numberposts'      => 1,
			'fields'           => 'ids',
			'no_found_rows'    => true,
			'suppress_filters' => true,
		);
		if ( ApplicationMetaKeys::TYPE_TEACHER === get_post_meta( $application_id, ApplicationMetaKeys::APPLICANT_TYPE, true ) ) {
			$args['author']     = (int) get_post_field( 'post_author', $application_id );
			$args['meta_key']   = ApplicationMetaKeys::APPLICANT_TYPE;
			$args['meta_value'] = ApplicationMetaKeys::TYPE_TEACHER;
		} else {
			$codigo = (string) get_post_meta( $application_id, ApplicationMetaKeys::CENTRE_CODE, true );
			if ( '' === $codigo ) {
				return null;
			}
			$args['meta_key']   = ApplicationMetaKeys::CENTRE_CODE;
			$args['meta_value'] = $codigo;
		}
		$ids = get_posts( $args );
		return is_array( $ids ) && array() !== $ids ? (int) $ids[0] : null;
	}





















	public static function why_not_submit_for( int $user_id, int $procedure_id, string $code = '', bool $override = false, ?string &$ineligible = null ): string {
		$ineligible = '';
		if ( ProcedurePostType::POST_TYPE !== get_post_type( $procedure_id ) || 'publish' !== get_post_status( $procedure_id ) ) {
			return 'Ese procedimiento no existe o no está publicado.';
		}
		$porque = self::why_not_reviewer( $user_id, $procedure_id );
		if ( '' !== $porque ) {
			return $porque;
		}
		if ( ProcedureMetaKeys::AUDIENCE_TEACHERS === get_post_meta( $procedure_id, ProcedureMetaKeys::AUDIENCE, true ) ) {
			return 'Este procedimiento es para el profesorado: cada solicitud es de una persona, y no se presenta en nombre de nadie.';
		}
		return '' === trim( $code ) ? '' : self::why_not_centre( $procedure_id, $code, $override, $ineligible );
	}







	public static function catalogue_centre( string $code ): array {
		$code   = trim( $code );
		$centro = CentreCatalog::find( $code );
		return array() !== $centro ? $centro : CentreCatalog::find( strtoupper( $code ) );
	}










	private static function why_not_centre( int $procedure_id, string $code, bool $override, ?string &$ineligible ): string {
		$centro = self::catalogue_centre( $code );
		if ( array() === $centro ) {
			return 'Ese código no está en el catálogo de centros. Compruébelo: solo se presenta en nombre de un centro del catálogo.';
		}
		if ( isset( $centro['active'] ) && false === $centro['active'] ) {
			return 'Ese centro figura como inactivo en el catálogo.';
		}
		$kind = (string) ( $centro['kind'] ?? '' );
		$kind = isset( ProcedureMetaKeys::centre_kinds()[ $kind ] ) ? $kind : ProcedureMetaKeys::KIND_SCHOOL;
		if ( ! in_array( $kind, ProcedureAccess::centre_kinds( $procedure_id ), true ) ) {
			return ProcedureMetaKeys::KIND_RURAL_GROUP === $kind
				? 'Este procedimiento no admite solicitudes de agrupaciones de escuelas rurales como colectivo.'
				: 'Este procedimiento solo admite solicitudes de agrupaciones de escuelas rurales como colectivo, y ese código es de un centro.';
		}
		$codigo = (string) $centro['code'];
		$antes  = self::existing( $procedure_id, $codigo );
		if ( 'publish' === $antes ) {
			return 'Ese centro ya tiene una solicitud en este procedimiento: ábrala desde la tabla.';
		}
		if ( 'trash' === $antes ) {
			return 'Ese centro tiene una solicitud retirada en este procedimiento: restáurela desde «Retiradas» en vez de crear otra.';
		}
		$ineligible = self::why_not_eligible( $procedure_id, $codigo, $kind );
		if ( '' !== $ineligible && ! $override ) {
			return $ineligible . ' Si aun así hay que presentarla, marque «Aunque no cumpla los requisitos».';
		}
		return '';
	}








	private static function existing( int $procedure_id, string $code ): string {
		$ids     = get_posts(
			array(
				'post_type'        => ApplicationPostType::POST_TYPE,
				'post_parent'      => $procedure_id,
				'post_status'      => array( 'publish', 'trash' ),
				'numberposts'      => -1,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'meta_key'         => ApplicationMetaKeys::CENTRE_CODE,
				'meta_value'       => $code,
				'suppress_filters' => true,
			)
		);
		$estados = array_map( 'get_post_status', is_array( $ids ) ? $ids : array() );
		if ( in_array( 'publish', $estados, true ) ) {
			return 'publish';
		}
		return in_array( 'trash', $estados, true ) ? 'trash' : '';
	}













	private static function why_not_eligible( int $procedure_id, string $code, string $kind ): string {
		$rules = ProcedureAccess::eligibility( $procedure_id );
		if ( ProcedureMetaKeys::KIND_RURAL_GROUP === $kind ) {
			$rules['ownership'] = array();
		}
		return CentreEligibility::restricted( $rules )
			? CentreEligibility::why_not( $rules, $code, CentreCatalog::find( $code ), array() !== CentreCatalog::all() )
			: '';
	}










	private static function why_not_reviewer( int $user_id, int $post_id ): string {
		if ( ! ProcedureAccess::can_review( $user_id, $post_id ) ) {
			return 'Su perfil no gestiona las solicitudes de este procedimiento.';
		}
		return ProcedureAccess::is_archived( $post_id )
			? 'Este procedimiento está marcado como histórico: sus solicitudes ya no se tocan.'
			: '';
	}







	private static function is_live( int $application_id ): bool {
		return $application_id > 0 && ApplicationPostType::POST_TYPE === get_post_type( $application_id ) && 'publish' === get_post_status( $application_id );
	}







	public static function day( string $ymd ): string {
		return '' === $ymd ? '' : implode( '-', array_reverse( explode( '-', $ymd ) ) );
	}
}

}








namespace Prc\Meta;

use Prc\Access\ObserverScope;
use Prc\Access\ProcedureAccess;
use Prc\Domain\CentreEligibility;
use Prc\Domain\PeopleSpec;
use Prc\Domain\ProcedureInput;
use Prc\Domain\ProcedureQuestions;
use Prc\PostType\ProcedurePostType;








if ( ! class_exists( 'Prc\Meta\ProcedureMetaRegistration', false ) ) {
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
			ProcedureMetaKeys::CENTRE_KINDS         => array(
				'type'     => 'array',
				'sanitize' => array( self::class, 'sanitize_centre_kinds' ),
			),
			ProcedureMetaKeys::CENTRE_TYPES         => array(
				'type'     => 'array',
				'sanitize' => array( self::class, 'sanitize_text_list' ),
			),
			ProcedureMetaKeys::ISLANDS              => array(
				'type'     => 'array',
				'sanitize' => array( self::class, 'sanitize_text_list' ),
			),
			ProcedureMetaKeys::ALLOWED_CENTRES      => array(
				'type'     => 'array',
				'sanitize' => array( CentreEligibility::class, 'codes' ),
			),
			ProcedureMetaKeys::REQUIRES_COORDINATOR => array(
				'type'     => 'boolean',
				'sanitize' => array( self::class, 'sanitize_bool' ),
			),
			ProcedureMetaKeys::COORDINATOR_COUNT    => array(
				'type'     => 'integer',
				'sanitize' => array( self::class, 'sanitize_coordinator_count' ),
			),
			ProcedureMetaKeys::CUSTOM_CSS           => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_custom_code' ),
			),
			ProcedureMetaKeys::CUSTOM_JS            => array(
				'type'     => 'string',
				'sanitize' => array( self::class, 'sanitize_custom_code' ),
				'auth'     => array( self::class, 'auth_custom_js' ),
			),
			ProcedureMetaKeys::QUESTIONS            => array(
				'type'     => 'array',
				'sanitize' => array( self::class, 'sanitize_questions' ),
			),
			ProcedureMetaKeys::QUESTIONS_SEQ        => array(
				'type'     => 'integer',
				'sanitize' => 'absint',
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
			ProcedureMetaKeys::APPLICATIONS_CLOSED  => array(
				'type'     => 'boolean',
				'sanitize' => array( self::class, 'sanitize_bool' ),
			),
			ProcedureMetaKeys::NOTIFY               => array(
				'type'     => 'array',
				'sanitize' => array( self::class, 'sanitize_notify' ),
			),
			ProcedureMetaKeys::PEOPLE_SPEC          => array(
				'type'     => 'array',
				'sanitize' => array( self::class, 'sanitize_people_spec' ),
			),
			ProcedureMetaKeys::OBSERVABLE           => array(
				'type'     => 'boolean',
				'sanitize' => array( self::class, 'sanitize_bool' ),
			),
			ProcedureMetaKeys::OBSERVER_COLUMNS     => array(
				'type'     => 'array',
				'sanitize' => array( self::class, 'sanitize_observer_columns' ),
			),
			ProcedureMetaKeys::OBSERVER_FILES       => array(
				'type'     => 'boolean',
				'sanitize' => array( self::class, 'sanitize_bool' ),
			),
			ProcedureMetaKeys::OBSERVER_EXPORT      => array(
				'type'     => 'boolean',
				'sanitize' => array( self::class, 'sanitize_bool' ),
			),
			ProcedureMetaKeys::OBSERVER_NOTIFY      => array(
				'type'     => 'boolean',
				'sanitize' => array( self::class, 'sanitize_bool' ),
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










	public static function auth_custom_js( bool $allowed, string $meta_key, int $post_id, int $user_id ): bool {
		unset( $allowed, $meta_key );
		return ProcedureAccess::can_edit_custom_js( $user_id, $post_id );
	}







	public static function sanitize_custom_code( $value ): string {
		return is_string( $value ) ? wp_check_invalid_utf8( $value ) : '';
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







	public static function sanitize_centre_kinds( $value ): array {
		return ProcedureInput::centre_kinds( $value );
	}







	public static function sanitize_text_list( $value ): array {
		return CentreEligibility::values( array_map( 'sanitize_text_field', CentreEligibility::values( $value ) ) );
	}











	public static function sanitize_observer_columns( $value ): array {
		$out = array();
		foreach ( is_array( $value ) ? $value : array() as $clave ) {
			$clave = is_string( $clave ) ? trim( $clave ) : '';
			if ( ObserverScope::is_allowed( $clave ) ) {
				$out[ $clave ] = $clave;
			}
		}
		return array_values( $out );
	}







	public static function sanitize_bool( $value ): bool {
		if ( is_string( $value ) ) {
			return in_array( strtolower( trim( $value ) ), array( '1', 'on', 'true', 'si', 'sí', 'yes' ), true );
		}
		return (bool) $value;
	}







	public static function sanitize_coordinator_count( $value ): int {
		$count = is_scalar( $value ) && false !== filter_var( $value, FILTER_VALIDATE_INT ) ? (int) $value : 0;
		return min( ProcedureMetaKeys::COORDINATOR_COUNT_MAX, max( 0, $count ) );
	}







	public static function sanitize_notify( $value ): array {
		$out = array();
		foreach ( is_array( $value ) ? $value : array( $value ) as $evento ) {
			$evento = ProcedureMetaKeys::in_list( $evento, ProcedureMetaKeys::notify_events() );
			if ( '' !== $evento && ! in_array( $evento, $out, true ) ) {
				$out[] = $evento;
			}
		}
		return $out;
	}







	public static function sanitize_questions( $value ): array {
		$preguntas = ProcedureQuestions::sanitize( $value );
		foreach ( $preguntas as $i => $pregunta ) {
			$preguntas[ $i ]['label']   = sanitize_text_field( (string) $pregunta['label'] );
			$preguntas[ $i ]['help']    = sanitize_text_field( (string) $pregunta['help'] );
			$preguntas[ $i ]['choices'] = array_map( 'sanitize_text_field', (array) $pregunta['choices'] );
			foreach ( (array) ( $pregunta['rows'] ?? array() ) as $j => $fila ) {
				$preguntas[ $i ]['rows'][ $j ]['label'] = sanitize_text_field( (string) $fila['label'] );
			}
		}
		return $preguntas;
	}







	public static function sanitize_people_spec( $value ): array {
		$spec = PeopleSpec::sanitize( $value );
		foreach ( array( 'roles', 'groups' ) as $lista ) {
			foreach ( $spec[ $lista ] as $i => $item ) {
				$spec[ $lista ][ $i ]['label'] = sanitize_text_field( (string) $item['label'] );
			}
		}
		return $spec;
	}
}

}








namespace Prc\Meta;

use Prc\Domain\ApplicationPeople;
use Prc\Domain\ProcedureInput;
use Prc\Domain\ProcedureQuestions;
use Prc\Domain\QuestionGrid;
use Prc\PostType\ApplicationPostType;
use Prc\PublicFront\ApplicationLog;









if ( ! class_exists( 'Prc\Meta\ApplicationMetaRegistration', false ) ) {
final class ApplicationMetaRegistration {






	public static function register(): void {
		add_action( 'init', array( self::class, 'register_meta' ), 12 );
	}






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
			ApplicationMetaKeys::PEOPLE             => array(
				'type'     => 'array',
				'sanitize' => array( self::class, 'sanitize_people' ),
			),
			ApplicationMetaKeys::LOG                => array(
				'type'     => 'array',
				'sanitize' => array( ApplicationLog::class, 'sanitize' ),
			),
			ApplicationMetaKeys::LATE_UNTIL         => array(
				'type'     => 'string',
				'sanitize' => array( ProcedureMetaRegistration::class, 'sanitize_date' ),
			),
			ApplicationMetaKeys::INTERNAL_NOTE      => array(
				'type'     => 'string',
				'sanitize' => 'sanitize_textarea_field',
			),
			ApplicationMetaKeys::CREATED_BY         => array(
				'type'     => 'integer',
				'sanitize' => 'absint',
			),
		);
	}







	public static function sanitize_applicant_type( $value ): string {
		return ApplicationMetaKeys::TYPE_TEACHER === $value ? ApplicationMetaKeys::TYPE_TEACHER : ApplicationMetaKeys::TYPE_CENTRE;
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







	public static function sanitize_people( $value ): array {
		$out = array();
		foreach ( ApplicationPeople::rows( $value ) as $fila ) {
			$out[] = array_map( 'sanitize_text_field', $fila );
		}
		return $out;
	}








	private static function text( array $person, string $key ): string {
		return sanitize_text_field( isset( $person[ $key ] ) && is_scalar( $person[ $key ] ) ? (string) $person[ $key ] : '' );
	}







	public static function full_name( array $coordinator ): string {
		return trim( (string) ( $coordinator['name'] ?? '' ) . ' ' . (string) ( $coordinator['surname'] ?? '' ) );
	}












	public static function split_name( string $full ): array {
		$partes = explode( ' ', trim( (string) preg_replace( '/\s+/u', ' ', $full ) ), 2 );
		return array( $partes[0], $partes[1] ?? '' );
	}










	public static function with_surname( array $coordinator ): array {
		if ( '' === (string) ( $coordinator['surname'] ?? '' ) && '' !== (string) ( $coordinator['name'] ?? '' ) ) {
			list( $coordinator['name'], $coordinator['surname'] ) = self::split_name( (string) $coordinator['name'] );
		}
		return $coordinator;
	}







	public static function full_names( array $coordinators ): string {
		return implode( ', ', array_filter( array_map( array( self::class, 'full_name' ), $coordinators ) ) );
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
			if ( is_bool( $respuesta ) || is_int( $respuesta ) ) {
				$out[ $key ] = $respuesta;
			} elseif ( is_array( $respuesta ) ) {
				$out[ $key ] = self::answer_list( $respuesta );
			} elseif ( is_scalar( $respuesta ) ) {
				$out[ $key ] = sanitize_textarea_field( (string) $respuesta );
			}
		}
		return $out;
	}








	private static function answer_list( array $values ): array {
		$out = array();
		foreach ( array_filter( $values, 'is_scalar' ) as $clave => $valor ) {
			$limpio = is_int( $valor ) ? $valor : sanitize_text_field( (string) $valor );
			if ( QuestionGrid::is_row_key( (string) $clave ) ) {
				$out[ (string) $clave ] = $limpio;
			} else {
				$out[] = $limpio;
			}
		}
		return $out;
	}
}

}








namespace Prc\Meta;





















if ( ! class_exists( 'Prc\Meta\MetaSwap', false ) ) {
final class MetaSwap {















	public static function change( int $post_id, string $key, callable $change, int $tries = 2 ): array {
		$res   = array();
		$veces = max( 1, $tries );
		for ( $i = 0; $i < $veces; ++$i ) {
			wp_cache_delete( $post_id, 'post_meta' );
			$leido = get_metadata_raw( 'post', $post_id, $key, true );
			$res   = (array) $change( $leido ) + array(
				'value' => null,
				'error' => '',
			);
			if ( '' !== (string) $res['error'] || ! empty( $res['skip'] ) ) {
				return array( 'ok' => '' === (string) $res['error'] ) + $res;
			}
			if ( self::swap( $post_id, $key, $leido, $res['value'] ) ) {
				wp_cache_delete( $post_id, 'post_meta' );
				return array( 'ok' => true ) + $res;
			}
		}
		return array(
			'ok'    => false,
			'error' => 'conflict',
		) + $res;
	}










	private static function swap( int $post_id, string $key, $read, $value ): bool {
		if ( null === $read ) {
			return array() === $value || false !== add_post_meta( $post_id, $key, wp_slash( $value ), true );
		}
		if ( empty( $read ) ) {



			delete_post_meta( $post_id, $key );
			return array() === $value || false !== add_post_meta( $post_id, $key, wp_slash( $value ), true );
		}
		if ( array() === $value ) {
			return false !== delete_post_meta( $post_id, $key, wp_slash( $read ) );
		}
		return false !== update_post_meta( $post_id, $key, wp_slash( $value ), $read );
	}
}

}








namespace Prc\PostType;








if ( ! class_exists( 'Prc\PostType\ProcedurePostType', false ) ) {
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

}








namespace Prc\PostType;













if ( ! class_exists( 'Prc\PostType\ApplicationPostType', false ) ) {
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

}








namespace Prc\Taxonomy;

use Prc\Access\ProcedureAccess;
use Prc\PostType\ProcedurePostType;











if ( ! class_exists( 'Prc\Taxonomy\ProcedureTaxonomies', false ) ) {
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

}








namespace Prc\PublicFront;














if ( ! class_exists( 'Prc\PublicFront\Assets', false ) ) {
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



















	public static function button_class( $variant = false ): string {
		$clases = array(
			'danger' => 'prc-btn prc-btn-borrar btn btn-outline-danger',
			'final'  => 'prc-btn prc-btn-primary prc-btn-final btn btn-primary btn-lg',
		);
		if ( is_string( $variant ) && isset( $clases[ $variant ] ) ) {
			return $clases[ $variant ];
		}
		return true === $variant
			? 'prc-btn prc-btn-primary btn btn-primary'
			: 'prc-btn prc-btn-sec btn btn-outline-secondary';
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

}








namespace Prc\PublicFront;






if ( ! class_exists( 'Prc\PublicFront\ExitSignal', false ) ) {
final class ExitSignal extends \RuntimeException {






	public $url = '';






	public function __construct( string $url = '' ) {
		parent::__construct( '' === $url ? 'Salida sin redirección' : 'Redirección a ' . $url );
		$this->url = $url;
	}
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















if ( ! class_exists( 'Prc\PublicFront\Shell', false ) ) {
final class Shell {






	public const SLUGS = array(
		'home'      => 'procedimientos',
		'workspace' => 'gestion-de-procedimientos',
		'editor'    => 'editar-procedimiento',
		'apply'     => 'solicitud',
		'mine'      => 'mi-centro',
		'observe'   => 'consulta-de-solicitudes',
		'help'      => 'ayuda',
	);











	public const SHORTCODES = array(
		'home'      => 'prc_home',
		'workspace' => 'prc_workspace',
		'editor'    => 'prc_editor',
		'apply'     => 'prc_apply',
		'mine'      => 'prc_mine',
		'observe'   => 'prc_observe',
		'help'      => 'prc_help',
	);




	public const SECTION_PROCEDURE = 'procedure';






	public const PUBLIC_SECTIONS = array( 'home', self::SECTION_PROCEDURE, 'help' );




	public const ARG_PROCEDURE = 'procedimiento';




	public const NOTICE_ANCHOR = 'prc-aviso';








	private const MINE_LABELS = array(
		'centre'  => 'Solicitudes de mi centro',
		'teacher' => 'Mis solicitudes',
	);






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
				'label' => self::mine_label( user_can( $user_id, ProcedureAccess::CAP_APPLY_AS_TEACHER ) ),
				'url'   => self::url( 'mine' ),
			);
		}


		if ( ProcedureAccess::can_observe( $user_id ) ) {
			$out['observe'] = array(
				'label' => 'Consulta de solicitudes',
				'url'   => self::url( 'observe' ),
			);
		}



		foreach ( $out as $clave => $seccion ) {
			if ( '' === $seccion['url'] ) {
				unset( $out[ $clave ] );
			}
		}

		return $out;
	}







	public static function mine_label( bool $teacher ): string {
		return self::MINE_LABELS[ $teacher ? 'teacher' : 'centre' ];
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
		if ( $user_id > 0 && user_can( $user_id, ProcedureAccess::CAP_VIEW_APPLICATIONS ) ) {
			return array(
				'role'  => 'Consulta de solicitudes',
				'scope' => self::zone_names( $user_id ),
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







	public static function zone_names( int $user_id ): string {
		$nombres = array();
		foreach ( ProcedureAccess::observer_zones( $user_id ) as $zona ) {
			$centro    = CentreCatalog::find( $zona );
			$nombres[] = array() !== $centro && '' !== (string) $centro['name'] ? $centro['name'] . ' (' . $zona . ')' : $zona;
		}
		return array() === $nombres ? 'Sin zona asignada' : implode( ' · ', $nombres );
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
			<?php echo $body;?>
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
		if ( $user_id <= 0 ) {
			$user_id = get_current_user_id();
		}
		$secciones = self::sections( $user_id );
		$activa    = self::current_section();
		$menu      = array();


		$inicio = isset( $secciones['home'] ) ? $secciones['home']['url'] : self::url( 'home' );
		if ( '' !== $inicio ) {
			$menu[] = self::entry( 'Inicio', $inicio, 'home' === $activa );
		}



		if ( isset( $secciones['mine'] ) ) {
			$menu[] = self::entry( $secciones['mine']['label'], $secciones['mine']['url'], 'mine' === $activa );
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

		$menu = array_merge( $menu, self::observe_entry( $secciones, $activa ), self::help_entry( $activa ) );



		foreach ( $menu as $i => $entrada ) {
			foreach ( $entrada['items'] as $item ) {
				$menu[ $i ]['active'] = $menu[ $i ]['active'] || $item['active'];
			}
		}

		return $menu;
	}











	private static function observe_entry( array $secciones, string $activa ): array {
		if ( ! isset( $secciones['observe'] ) ) {
			return array();
		}
		return array( self::entry( 'Consulta', $secciones['observe']['url'], 'observe' === $activa ) );
	}












	private static function help_entry( string $activa ): array {
		$guia   = self::url( 'help' );
		$dentro = 'help' === $activa;
		$ayuda  = self::entry( 'Ayuda', $guia, $dentro );
		foreach ( (array) ProcedureChrome::chrome()['footer_links'] as $enlace ) {
			if ( isset( $enlace['label'], $enlace['url'] ) ) {
				$ayuda['items'][] = self::entry( (string) $enlace['label'], (string) $enlace['url'] );
			}
		}
		if ( array() !== $ayuda['items'] && '' !== $guia ) {
			array_unshift( $ayuda['items'], self::entry( 'Guías y preguntas frecuentes', $guia, $dentro ) );
		}
		return '' === $guia && array() === $ayuda['items'] ? array() : array( $ayuda );
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









	public static function notice( string $type, string $text, bool $anchor = false ): string {
		if ( '' === $text ) {
			return '';
		}
		return self::notice_open( $type, $anchor ) . esc_html( $text ) . '</p>';
	}















	public static function notice_open( string $type, bool $anchor = false, string $tag = 'p' ): string {
		$tonos = array(
			'ok'    => 'success',
			'aviso' => 'warning',
			'error' => 'danger',
		);
		$tono  = $tonos[ $type ] ?? 'info';
		$papel = in_array( $tono, array( 'warning', 'danger' ), true ) ? 'alert' : 'status';
		$ancla = $anchor ? ' id="' . self::NOTICE_ANCHOR . '" tabindex="-1"' : '';
		return '<' . ( 'div' === $tag ? 'div' : 'p' ) . ' class="' . esc_attr( Assets::alert_class( $tono ) ) . '" role="' . $papel . '"' . $ancla . '>';
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
				<?php echo $cuerpo;?>
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
		$url = self::to_notice( $url );
		if ( apply_filters( 'prc_exit_throws', false, $url ) ) {
			throw new ExitSignal( $url );
		}
		if ( '' !== $url ) {
			wp_safe_redirect( $url );
		}
		exit;
	}










	private static function to_notice( string $url ): string {
		if ( '' === $url || false !== strpos( $url, '#' ) ) {
			return $url;
		}
		$args = wp_parse_args( (string) wp_parse_url( $url, PHP_URL_QUERY ) );
		return isset( $args['aviso'] ) ? $url . '#' . self::NOTICE_ANCHOR : $url;
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

}








namespace Prc\PublicFront;

use Prc\Access\ProcedureAccess;

























if ( ! class_exists( 'Prc\PublicFront\EditLock', false ) ) {
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

}








namespace Prc\PublicFront\View;

use Prc\PublicFront\Assets;
use Prc\PublicFront\Shell;










if ( ! class_exists( 'Prc\PublicFront\View\PanelParts', false ) ) {
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












	public static function help_link( string $url, string $lead = '¿Dudas?', string $label = 'Consulte la ayuda' ): string {
		if ( '' === $url ) {
			return '';
		}
		return '<p class="prc-ayuda-enlace">' . ( '' !== $lead ? esc_html( $lead ) . ' ' : '' )
			. '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></p>';
	}










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

}








namespace Prc\PublicFront\View;

use Prc\PublicFront\Shell;














if ( ! class_exists( 'Prc\PublicFront\View\FieldErrorView', false ) ) {
final class FieldErrorView {







	public static function anchor( string $code ): string {
		$partes = explode( ':', $code, 2 );
		if ( 'answer' === $partes[0] ) {
			return 'prc-q-' . ( $partes[1] ?? '' );
		}
		if ( 0 === strpos( $partes[0], 'coordinator_' ) ) {
			return 'prc-coordinator-' . substr( $partes[0], strlen( 'coordinator_' ) ) . '-' . (int) ( $partes[1] ?? 0 );
		}
		return 'prc-' . $partes[0];
	}









	public static function attrs( array $invalid, string $code, string $help_id = '' ): string {
		$falla  = isset( $invalid[ $code ] );
		$quien  = trim( $help_id . ( $falla ? ' ' . self::anchor( $code ) . '-error' : '' ) );
		$atribs = '' !== $quien ? ' aria-describedby="' . esc_attr( $quien ) . '"' : '';
		return $atribs . ( $falla ? ' aria-invalid="true"' : '' );
	}








	public static function css( array $invalid, string $code ): string {
		return isset( $invalid[ $code ] ) ? ' is-invalid' : '';
	}








	public static function feedback( array $invalid, string $code ): string {
		if ( ! isset( $invalid[ $code ] ) ) {
			return '';
		}
		return '<p class="invalid-feedback d-block" id="' . esc_attr( self::anchor( $code ) . '-error' ) . '">'
			. esc_html( 'Revise ' . $invalid[ $code ] . '.' ) . '</p>';
	}








	public static function summary( string $intro, array $invalid ): string {
		$items = '';
		foreach ( $invalid as $code => $rotulo ) {
			$items .= '<li><a href="#' . esc_attr( self::anchor( (string) $code ) ) . '">' . esc_html( (string) $rotulo ) . '</a></li>';
		}
		return Shell::notice_open( 'error', true, 'div' )
			. '<p>' . esc_html( $intro ) . '</p><ul class="prc-aviso__campos">' . $items . '</ul></div>';
	}
}

}









namespace Prc\PublicFront;

use Prc\Domain\CentreCatalog;
use Prc\Meta\ProcedureMetaKeys;












if ( ! class_exists( 'Prc\PublicFront\CentreColumns', false ) ) {
final class CentreColumns {






	public const KEYS = array( 'island', 'municipality', 'centre_type', 'ownership', 'zone', 'zone_code' );









	public static function extras(): array {
		return array_merge( self::KEYS, Applications::EXTRA_COLUMNS );
	}







	public static function columns( bool $wanted = true ): array {
		if ( ! $wanted ) {
			return array();
		}
		$zona = self::zone_label();
		return array(
			'island'       => 'Isla',
			'municipality' => 'Municipio',
			'centre_type'  => 'Tipo de centro',
			'ownership'    => 'Titularidad',
			'zone'         => $zona,
			'zone_code'    => $zona . ' (código)',
		);
	}









	public static function zone_label(): string {





		$rotulo = apply_filters( 'prc_zone_label', 'Zona' );
		$rotulo = is_string( $rotulo ) ? trim( wp_strip_all_tags( $rotulo ) ) : '';
		return '' !== $rotulo ? $rotulo : 'Zona';
	}












	public static function attributes( string $code, ?array $index = null ): array {
		$code   = trim( $code );
		$centro = array();
		if ( '' !== $code ) {
			$centro = null === $index ? CentreCatalog::find( $code ) : (array) ( $index[ $code ] ?? array() );
		}
		$out = array();
		foreach ( array( 'island', 'municipality', 'type', 'ownership', 'zone', 'zone_name' ) as $campo ) {
			$out[ $campo ] = (string) ( $centro[ $campo ] ?? '' );
		}
		return $out;
	}









	public static function index_for( array $applications, bool $wanted = true ): array {
		return $wanted && array() !== $applications ? CentreCatalog::index() : array();
	}








	public static function cells( string $code, ?array $index ): array {
		$centro = self::attributes( $code, $index );
		return array(
			'island'       => $centro['island'],
			'municipality' => $centro['municipality'],
			'centre_type'  => $centro['type'],
			'ownership'    => ProcedureMetaKeys::ownership_name( $centro['ownership'] ),
			'zone'         => $centro['zone_name'],
			'zone_code'    => $centro['zone'],
		);
	}














	public static function ordered( array $chosen, array $always, array $available ): array {
		$faltan = array_diff_key( $always, $chosen );
		if ( array() === $chosen || ( array() === $faltan && count( $chosen ) === count( $always ) ) ) {
			return $always;
		}
		return array() === $faltan ? array_intersect_key( $available, $chosen ) : $chosen;
	}











	public static function all_plus( array $ticked, array $always ): array {
		$extras = array_values( array_intersect( self::extras(), $ticked ) );
		return array() === $extras ? array() : array_merge( $always, $extras );
	}
}

}








namespace Prc\PublicFront;

use Prc\Access\CentreScope;
use Prc\Access\ProcedureAccess;
use Prc\Domain\CentreCatalog;
use Prc\Domain\PeopleSpec;
use Prc\Domain\ProcedureQuestions;
use Prc\Domain\QuestionGrid;
use Prc\Domain\QuestionPoints;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ApplicationMetaRegistration;
use Prc\Meta\ProcedureMetaRegistration;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ApplicationPostType;
use Prc\PostType\ProcedurePostType;

















if ( ! class_exists( 'Prc\PublicFront\Applications', false ) ) {
final class Applications {












	public const KEY_FILES = '_files';






	public const EXTRA_COLUMNS = array( 'internal_note' );











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








	public static function find_for_teacher( int $procedure_id, int $user_id ): ?int {
		if ( $procedure_id <= 0 || $user_id <= 0 ) {
			return null;
		}
		$ids = get_posts(
			array(
				'post_type'   => ApplicationPostType::POST_TYPE,
				'post_parent' => $procedure_id,
				'post_status' => 'publish',
				'author'      => $user_id,
				'numberposts' => 1,
				'fields'      => 'ids',
				'meta_key'    => ApplicationMetaKeys::APPLICANT_TYPE,
				'meta_value'  => ApplicationMetaKeys::TYPE_TEACHER,
			)
		);
		return is_array( $ids ) && array() !== $ids ? (int) $ids[0] : null;
	}







	public static function for_teacher( int $user_id ): array {
		if ( $user_id <= 0 ) {
			return array();
		}
		$posts = get_posts(
			array(
				'post_type'   => ApplicationPostType::POST_TYPE,
				'post_status' => 'publish',
				'author'      => $user_id,
				'numberposts' => -1,
				'orderby'     => 'date',
				'order'       => 'DESC',
				'meta_key'    => ApplicationMetaKeys::APPLICANT_TYPE,
				'meta_value'  => ApplicationMetaKeys::TYPE_TEACHER,
			)
		);
		return is_array( $posts ) ? $posts : array();
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










	public static function for_centre( $centre_code ): array {
		$centre_code = array_values( array_filter( array_map( 'trim', array_map( 'strval', (array) $centre_code ) ) ) );
		if ( array() === $centre_code ) {
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
				'meta_compare'     => 'IN',
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














	public static function answered_keys( int $procedure_id, bool $empty_too = false ): array {
		return array_fill_keys( array_keys( self::answer_counts( $procedure_id, $empty_too ) ), true );
	}












	public static function answer_counts( int $procedure_id, bool $empty_too = false ): array {
		$out = array();
		foreach ( self::for_procedure( $procedure_id ) as $solicitud ) {
			$respuestas = (array) self::meta( (int) $solicitud->ID )[ ApplicationMetaKeys::ANSWERS ];
			$respuestas = $empty_too ? $respuestas : array_filter( $respuestas, array( ProcedureQuestions::class, 'is_answer' ) );
			foreach ( array_keys( $respuestas + ApplicationFiles::descriptors( (int) $solicitud->ID ) ) as $clave ) {
				$out[ (string) $clave ] = ( $out[ (string) $clave ] ?? 0 ) + 1;
			}
		}
		return $out;
	}







	public static function spec( int $procedure_id ): array {
		$teacher = ProcedureMetaKeys::AUDIENCE_TEACHERS === get_post_meta( $procedure_id, ProcedureMetaKeys::AUDIENCE, true );
		$pide    = $teacher ? 0 : self::coordinator_count( $procedure_id );



		$en_lista = $pide > 0 && People::enabled( $procedure_id ) && PeopleSpec::collects_coordination( People::spec( $procedure_id ) );
		return array(
			'applicant_type'       => $teacher ? ApplicationMetaKeys::TYPE_TEACHER : ApplicationMetaKeys::TYPE_CENTRE,
			'coordinator_count'    => $en_lista ? 0 : $pide,
			'requires_coordinator' => ! $en_lista && $pide > 0,
			'coordinators_in_list' => $en_lista,
			'commitments'          => (string) get_post_meta( $procedure_id, ProcedureMetaKeys::COMMITMENTS, true ),
			'questions'            => ProcedureQuestions::application_questions( self::questions( $procedure_id ) ),
		);
	}







	public static function coordinator_count( int $procedure_id ): int {
		if ( $procedure_id <= 0 ) {
			return 0;
		}
		$count = metadata_exists( 'post', $procedure_id, ProcedureMetaKeys::COORDINATOR_COUNT )
			? get_post_meta( $procedure_id, ProcedureMetaKeys::COORDINATOR_COUNT, true )
			: ( ! empty( get_post_meta( $procedure_id, ProcedureMetaKeys::REQUIRES_COORDINATOR, true ) ) ? 1 : 0 );
		return ProcedureMetaRegistration::sanitize_coordinator_count( $count );
	}














	public static function centre_of( int $user_id = 0, string $code = '' ): array {
		if ( '' === $code ) {
			$code = CentreScope::code_for( $user_id );
		} elseif ( ! isset( CentreScope::codes_for( $user_id )[ $code ] ) ) {
			$code = '';
		}
		$centro = CentreCatalog::find( $code );
		return array(
			'code'      => $code,
			'name'      => (string) ( $centro['name'] ?? '' ),
			'ownership' => (string) ( $centro['ownership'] ?? '' ),
		);
	}



















	public static function save( int $procedure_id, int $user_id, array $data, string $code = '', array $files = array() ): int {
		if ( $procedure_id <= 0 || $user_id <= 0 || ProcedurePostType::POST_TYPE !== get_post_type( $procedure_id ) ) {
			return 0;
		}
		$centro = self::centre_of( $user_id, $code );
		ApplicationOperations::forget_refusal();
		if ( '' === $centro['code'] ) {
			return 0;
		}

		$created = null === self::find( $procedure_id, $centro['code'] );
		$id      = self::save_post( $procedure_id, $user_id, $centro, $files );
		if ( 0 === $id ) {
			return 0;
		}



		$data += array(
			'position'    => '',
			'coordinator' => array(),
			'answers'     => array(),
		);
		$antes = (string) get_post_meta( $id, ApplicationMetaKeys::REVIEW_STATE, true );
		$metas = array(
			ApplicationMetaKeys::APPLICANT_TYPE     => ApplicationMetaKeys::TYPE_CENTRE,
			ApplicationMetaKeys::CENTRE_CODE        => $centro['code'],
			ApplicationMetaKeys::CENTRE_NAME        => $centro['name'],
			ApplicationMetaKeys::APPLICANT_POSITION => (string) $data['position'],
			ApplicationMetaKeys::COORDINATOR        => (array) $data['coordinator'],
			ApplicationMetaKeys::ANSWERS            => (array) $data['answers'],
			ApplicationMetaKeys::REVIEW_STATE       => ApplicationMetaKeys::REVIEW_SUBMITTED,
		);


		if ( array() === (array) $data['coordinator'] && self::spec( $procedure_id )['coordinators_in_list'] ) {
			unset( $metas[ ApplicationMetaKeys::COORDINATOR ] );
		}
		foreach ( $metas as $clave => $valor ) {
			update_post_meta( $id, $clave, wp_slash( $valor ) );
		}


		if ( $created && ApplicationOperations::lost_race( $id, $procedure_id, $centro['code'] ) ) {
			return 0;
		}


		$created = ApplicationOperations::adopt( $id, $user_id ) || $created;
		self::saved( $id, $created, $user_id, $antes );
		return $id;
	}










	public static function save_teacher( int $procedure_id, int $user_id, array $data, array $files = array() ): int {
		$user = get_userdata( $user_id );
		if ( ! $user || ! ProcedureAccess::can_apply( $user_id, $procedure_id ) || ProcedureMetaKeys::AUDIENCE_TEACHERS !== get_post_meta( $procedure_id, ProcedureMetaKeys::AUDIENCE, true ) ) {
			return 0;
		}
		$created = null === self::find_for_teacher( $procedure_id, $user_id );
		$id      = self::save_post(
			$procedure_id,
			$user_id,
			array(
				'code'      => '',
				'name'      => '',
				'ownership' => '',
			),
			$files,
			ApplicationMetaKeys::TYPE_TEACHER,
			'Solicitud individual'
		);
		if ( 0 === $id ) {
			return 0;
		}
		$data += array( 'answers' => array() );
		$antes = (string) get_post_meta( $id, ApplicationMetaKeys::REVIEW_STATE, true );
		foreach (
			array(
				ApplicationMetaKeys::APPLICANT_TYPE     => ApplicationMetaKeys::TYPE_TEACHER,
				ApplicationMetaKeys::CENTRE_CODE        => '',
				ApplicationMetaKeys::CENTRE_NAME        => '',
				ApplicationMetaKeys::APPLICANT_POSITION => '',
				ApplicationMetaKeys::COORDINATOR        => array(),
				ApplicationMetaKeys::ANSWERS            => (array) $data['answers'],
				ApplicationMetaKeys::REVIEW_STATE       => ApplicationMetaKeys::REVIEW_SUBMITTED,
			) as $key => $value
		) {
			update_post_meta( $id, $key, wp_slash( $value ) );
		}
		self::saved( $id, $created, $user_id, $antes );
		return $id;
	}























	private static function saved( int $id, bool $created, int $user_id, string $previous = '' ): void {








		do_action( 'prc_application_saved', $id, $created, $user_id, $previous );
	}












	private static function save_post( int $procedure_id, int $user_id, array $centro, array $files, string $type = ApplicationMetaKeys::TYPE_CENTRE, string $applicant = '' ): int {
		$titulo = ApplicationMetaKeys::TYPE_TEACHER === $type ? $applicant . ' — ' . (string) get_post_field( 'post_title', $procedure_id ) : self::title( $centro, $procedure_id );
		$id     = ApplicationMetaKeys::TYPE_TEACHER === $type ? self::find_for_teacher( $procedure_id, $user_id ) : self::find( $procedure_id, $centro['code'] );
		$is_new = null === $id;
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
		}
		if ( is_wp_error( $id ) || ! $id ) {
			return 0;
		}
		$id = (int) $id;
		if ( ! ApplicationFiles::store_all( $id, $files ) ) {
			if ( $is_new ) {
				wp_delete_post( $id, true );
			}
			return 0;
		}
		if ( ! $is_new ) {
			$id = wp_update_post(
				array(
					'ID'         => $id,
					'post_title' => wp_slash( $titulo ),
				),
				true
			);
			if ( is_wp_error( $id ) || ! $id ) {
				return 0;
			}
		}

		return $id;
	}

















	public static function review( int $application_id, string $state, ?string $note ): array {
		if ( ApplicationPostType::POST_TYPE !== get_post_type( $application_id ) ) {
			return self::refused( 'application' );
		}
		if ( ! isset( ApplicationMetaKeys::review_states()[ $state ] ) ) {
			return self::refused( 'state' );
		}
		$previous_note = (string) get_post_meta( $application_id, ApplicationMetaKeys::REVIEW_NOTE, true );
		$note          = null === $note ? $previous_note : trim( $note );
		if ( '' === trim( $note ) && in_array( $state, ApplicationMetaKeys::states_needing_note(), true ) ) {
			return self::refused( 'note' );
		}

		$previous = (string) get_post_meta( $application_id, ApplicationMetaKeys::REVIEW_STATE, true );
		update_post_meta( $application_id, ApplicationMetaKeys::REVIEW_STATE, $state );
		update_post_meta( $application_id, ApplicationMetaKeys::REVIEW_NOTE, wp_slash( $note ) );














		do_action( 'prc_application_reviewed', $application_id, $state, $previous, $previous_note );

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




















	public static function columns( array $questions, bool $centre = false ): array {
		$out  = array(
			'centre' => 'Centro',
			'code'   => 'Código',
		);
		$out += CentreColumns::columns( $centre );
		$out += array(
			'date'              => 'Fecha',
			'applicant'         => 'Presentada por',
			'email'             => 'Correo',
			'position'          => 'Cargo',
			'coordinator'       => 'Coordinación',
			'coordinator_dni'   => 'DNI o NIE de coordinación',
			'coordinator_email' => 'Correo de coordinación',
			'state'             => 'Estado de revisión',
			'note'              => 'Nota',
		);
		$out += QuestionPoints::heading( $questions );


		$out += $centre ? array( 'internal_note' => 'Nota interna' ) : array();
		foreach ( $questions as $pregunta ) {
			$out[ (string) $pregunta['key'] ] = (string) $pregunta['label'];
		}
		return $out;
	}









	public static function rows( int $procedure_id, ?array $applications = null, bool $centre = false ): array {
		$preguntas   = self::questions( $procedure_id );
		$filas       = array();
		$solicitudes = $applications ?? self::for_procedure( $procedure_id );

		cache_users( array_unique( array_map( 'intval', wp_list_pluck( $solicitudes, 'post_author' ) ) ) );

		$catalogo = CentreColumns::index_for( $solicitudes, $centre );
		foreach ( $solicitudes as $solicitud ) {
			$filas[] = self::row( $solicitud, $preguntas, $catalogo );
		}
		return $filas;
	}












	public static function row( \WP_Post $application, array $questions, ?array $catalogue = null ): array {
		$meta   = self::meta( (int) $application->ID );
		$autor  = get_userdata( (int) $application->post_author );
		$coord  = ApplicationMetaRegistration::sanitize_coordinator( $meta[ ApplicationMetaKeys::COORDINATOR ] );
		$estado = (string) $meta[ ApplicationMetaKeys::REVIEW_STATE ];

		$fila  = array(
			'id'                => (string) $application->ID,
			'state_key'         => $estado,
			'centre'            => ApplicationMetaKeys::TYPE_TEACHER === ApplicationMetaRegistration::sanitize_applicant_type( $meta[ ApplicationMetaKeys::APPLICANT_TYPE ] ) ? 'Profesorado' : (string) $meta[ ApplicationMetaKeys::CENTRE_NAME ],
			'code'              => (string) $meta[ ApplicationMetaKeys::CENTRE_CODE ],



			'date'              => (string) get_the_date( 'Y-m-d H:i', $application ),
			'date_label'        => (string) get_the_date( 'd-m-Y', $application ),
			'applicant'         => false !== $autor ? (string) $autor->display_name : '',
			'email'             => false !== $autor ? (string) $autor->user_email : '',
			'position'          => (string) ( ApplicationMetaKeys::positions()[ $meta[ ApplicationMetaKeys::APPLICANT_POSITION ] ] ?? '' ),
			'coordinator'       => ApplicationMetaRegistration::full_names( $coord ),
			'coordinator_dni'   => implode( ', ', array_filter( array_column( $coord, 'dni' ) ) ),
			'coordinator_email' => implode( ', ', array_filter( array_column( $coord, 'email' ) ) ),
			'state'             => (string) ( ApplicationMetaKeys::review_states()[ $estado ] ?? '' ),
			'note'              => (string) $meta[ ApplicationMetaKeys::REVIEW_NOTE ],
			'internal_note'     => (string) $meta[ ApplicationMetaKeys::INTERNAL_NOTE ],
		);
		$fila += CentreColumns::cells( (string) $meta[ ApplicationMetaKeys::CENTRE_CODE ], $catalogue );


		$respuestas = (array) $meta[ ApplicationMetaKeys::ANSWERS ];
		$fila      += QuestionPoints::cell( $questions, $respuestas );
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
			$fila        += QuestionGrid::cells( $pregunta, $respuestas[ $key ] ?? null );
		}
		$fila[ self::KEY_FILES ] = $adjuntos;
		return $fila;
	}










	public static function csv( int $procedure_id ): string {
		return ApplicationExport::csv( $procedure_id );
	}



















	public static function csv_lines( array $columns, array $rows, array $verbatim = array() ): string {
		$lineas = array( self::csv_line( array_values( $columns ) ) );
		foreach ( $rows as $fila ) {
			$campos = array();
			$tal    = array();
			foreach ( array_keys( $columns ) as $clave ) {
				$campo    = (string) ( $fila[ $clave ] ?? '' );
				$campos[] = $campo;
				$tal[]    = isset( $verbatim[ $clave ] ) && (bool) preg_match( $verbatim[ $clave ], $campo );
			}
			$lineas[] = self::csv_line( $campos, $tal );
		}


		return "\xEF\xBB\xBF" . implode( "\r\n", $lineas ) . "\r\n";
	}







	public static function filename( string $title ): string {
		$base = sanitize_title( $title );
		return 'solicitudes-' . ( '' !== $base ? $base : 'procedimiento' ) . '-' . gmdate( 'Y-m-d' ) . '.csv';
	}








	private static function csv_line( array $campos, array $tal = array() ): string {
		$fuera = array();
		foreach ( $campos as $i => $campo ) {
			$fuera[] = '"' . str_replace( '"', '""', empty( $tal[ $i ] ) ? ApplicationExport::neutralize( $campo ) : $campo ) . '"';
		}
		return implode( ';', $fuera );
	}
}

}








namespace Prc\PublicFront;

use Prc\Access\ProcedureAccess;
use Prc\Domain\ProcedureQuestions;
use Prc\Meta\ApplicationMetaKeys;
use Prc\PostType\ApplicationPostType;

























if ( ! class_exists( 'Prc\PublicFront\ApplicationFiles', false ) ) {
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
		if ( ! update_post_meta( $application_id, ApplicationMetaKeys::FILES, wp_slash( array_merge( $antes, $nuevos ) ) ) ) {
			foreach ( $nuevos as $descriptor ) {
				self::erase( $descriptor );
			}
			return false;
		}



		foreach ( $nuevos as $key => $descriptor ) {
			if ( isset( $antes[ $key ] ) && $antes[ $key ]['stored'] !== $descriptor['stored'] ) {
				self::erase( $antes[ $key ] );
			}
		}
		return true;
	}














	public static function deliver( int $application_id, string $question_key, array $files, int $user_id ): bool {
		if ( ! isset( $files[ $question_key ] ) || ! self::store_all( $application_id, array( $question_key => $files[ $question_key ] ) ) ) {
			return false;
		}








		do_action( 'prc_delivery_saved', $application_id, $question_key, $user_id );
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
			|| ! ProcedureAccess::can_view_application( get_current_user_id(), $application_id, ProcedureAccess::VIEW_DOCUMENT, self::question_of( $application_id, $file_id ) ) ) {
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











	private static function question_of( int $application_id, string $file_id ): string {
		foreach ( self::descriptors( $application_id ) as $question_key => $descriptor ) {
			if ( hash_equals( (string) $descriptor['id'], $file_id ) ) {
				return (string) $question_key;
			}
		}
		return '';
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

}








namespace Prc\PublicFront;

use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\MetaSwap;
use Prc\PostType\ApplicationPostType;






















if ( ! class_exists( 'Prc\PublicFront\ApplicationLog', false ) ) {
final class ApplicationLog {


	public const MAX = 500;


	public const TRIMMED = 'log_trimmed';














	public static function add( int $application_id, string $event, array $data, int $user_id = 0 ): bool {
		if ( ApplicationPostType::POST_TYPE !== get_post_type( $application_id ) ) {
			return false;
		}
		$entrada = array(
			'at'    => gmdate( 'Y-m-d\TH:i:s\Z' ),
			'user'  => $user_id > 0 ? $user_id : get_current_user_id(),
			'event' => $event,
			'data'  => $data,
		);
		$res     = MetaSwap::change(
			$application_id,
			ApplicationMetaKeys::LOG,
			static function ( $leido ) use ( $entrada ): array {
				return array( 'value' => self::append( self::sanitize( $leido ), $entrada ) );
			},
			3
		);
		return (bool) $res['ok'];
	}












	public static function append( array $todo, array $entrada ): array {
		$idas = 0;
		if ( isset( $todo[0] ) && self::TRIMMED === $todo[0]['event'] ) {
			$idas = (int) ( $todo[0]['data']['dropped'] ?? 0 );
			array_shift( $todo );
		}
		$todo[] = $entrada;
		if ( count( $todo ) + ( $idas > 0 ? 1 : 0 ) <= self::MAX ) {
			return $idas > 0 ? array_merge( array( self::trimmed( $idas, $todo[0]['at'] ) ), $todo ) : $todo;
		}
		$quedan = array_slice( $todo, - ( self::MAX - 1 ) );
		$idas  += count( $todo ) - count( $quedan );
		return array_merge( array( self::trimmed( $idas, (string) $quedan[0]['at'] ) ), $quedan );
	}








	private static function trimmed( int $dropped, string $at ): array {
		return array(
			'at'    => $at,
			'user'  => 0,
			'event' => self::TRIMMED,
			'data'  => array( 'dropped' => $dropped ),
		);
	}







	public static function all( int $application_id ): array {
		return self::sanitize( get_post_meta( $application_id, ApplicationMetaKeys::LOG, true ) );
	}









	public static function sanitize( $value ): array {
		$out = array();
		foreach ( is_array( $value ) ? $value : array() as $entrada ) {
			$evento = is_array( $entrada ) && is_string( $entrada['event'] ?? null ) ? $entrada['event'] : '';
			if ( ! preg_match( '/^[a-z][a-z0-9_]{0,39}$/', $evento ) ) {
				continue;
			}
			$momento = is_string( $entrada['at'] ?? null ) ? $entrada['at'] : '';
			$out[]   = array(
				'at'    => preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $momento ) ? $momento : '',
				'user'  => absint( $entrada['user'] ?? 0 ),
				'event' => $evento,
				'data'  => self::data( is_array( $entrada['data'] ?? null ) ? $entrada['data'] : array(), 3 ),
			);
		}
		return array_slice( $out, -self::MAX );
	}








	private static function data( array $data, int $depth ): array {
		$out = array();
		foreach ( $data as $clave => $valor ) {
			$clave = is_int( $clave ) ? (string) $clave : sanitize_key( (string) $clave );
			if ( is_array( $valor ) ) {
				$out[ $clave ] = $depth > 1 ? self::data( $valor, $depth - 1 ) : array();
			} elseif ( is_int( $valor ) || is_bool( $valor ) ) {
				$out[ $clave ] = $valor;
			} elseif ( is_scalar( $valor ) ) {
				$out[ $clave ] = sanitize_text_field( (string) $valor );
			}
		}
		return $out;
	}
}

}









namespace Prc\PublicFront;

use Prc\Access\ApplicationActions;
use Prc\Meta\ApplicationMetaKeys;
use Prc\PostType\ApplicationPostType;
















if ( ! class_exists( 'Prc\PublicFront\ApplicationOperations', false ) ) {
final class ApplicationOperations {


	public const BY_APPLICANT = 'applicant';


	public const BY_REVIEWER = 'reviewer';


	public const BY_DESK = 'desk';









	private static $context = null;




	public const DUPLICATE = 'Ya hay una solicitud de este centro en este procedimiento, así que no se ha creado otra. Vuelva a abrir la solicitud para ver la que hay.';






	private static $refusal = '';






	public static function register(): void {
		add_action( 'trashed_post', array( self::class, 'on_trashed' ) );
		add_action( 'untrashed_post', array( self::class, 'on_untrashed' ) );
	}

















	public static function lost_race( int $id, int $procedure_id, string $code ): bool {
		$ids = get_posts(
			array(
				'post_type'        => ApplicationPostType::POST_TYPE,
				'post_parent'      => $procedure_id,
				'post_status'      => 'publish',
				'numberposts'      => 1,
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'meta_key'         => ApplicationMetaKeys::CENTRE_CODE,
				'meta_value'       => $code,
				'suppress_filters' => true,
			)
		);
		if ( ! is_array( $ids ) || array() === $ids || (int) $ids[0] === $id ) {
			return false;
		}
		wp_delete_post( $id, true );
		self::$refusal = self::DUPLICATE;
		return true;
	}







	public static function why_not_saved( string $generic ): string {
		return '' !== self::$refusal ? self::$refusal : $generic;
	}






	public static function forget_refusal(): void {
		self::$refusal = '';
	}












	public static function withdraw( int $application_id, int $user_id, string $by, string $reason = '' ): bool {
		if ( ApplicationPostType::POST_TYPE !== get_post_type( $application_id ) ) {
			return false;
		}
		self::$context = array(
			'user'   => $user_id,
			'by'     => $by,
			'reason' => $reason,
		);
		try {
			self::send_to_trash( $application_id );
		} finally {
			self::$context = null;
		}
		return 'trash' === get_post_status( $application_id );
	}













	private static function send_to_trash( int $application_id ): void {
		if ( ApplicationActions::trash_enabled() ) {
			wp_trash_post( $application_id );
			return;
		}
		$post = get_post( $application_id );
		if ( ! $post instanceof \WP_Post || 'trash' === $post->post_status ) {
			return;
		}
		$previous_status = $post->post_status;

		if ( null !== apply_filters( 'pre_trash_post', null, $post, $previous_status ) ) {
			return;
		}

		do_action( 'wp_trash_post', $application_id, $previous_status );
		add_post_meta( $application_id, '_wp_trash_meta_status', $previous_status );
		add_post_meta( $application_id, '_wp_trash_meta_time', time() );
		$updated = wp_update_post(
			array(
				'ID'          => $application_id,
				'post_status' => 'trash',
			)
		);
		if ( ! $updated ) {
			return;
		}
		wp_trash_post_comments( $application_id );

		do_action( 'trashed_post', $application_id, $previous_status );
	}







	public static function on_trashed( $post_id ): void {
		$post_id = (int) $post_id;
		if ( ApplicationPostType::POST_TYPE !== get_post_type( $post_id ) ) {
			return;
		}



		delete_post_meta( $post_id, '_wp_trash_meta_time' );
		$ctx = self::$context ?? array(
			'user'   => get_current_user_id(),
			'by'     => self::BY_DESK,
			'reason' => '',
		);









		do_action( 'prc_application_withdrawn', $post_id, (int) $ctx['user'], (string) $ctx['by'], (string) $ctx['reason'] );
	}










	public static function restore( int $application_id ): bool {
		if ( ApplicationPostType::POST_TYPE !== get_post_type( $application_id ) ) {
			return false;
		}
		wp_untrash_post( $application_id );
		return 'publish' === get_post_status( $application_id );
	}







	public static function on_untrashed( $post_id ): void {
		$post_id = (int) $post_id;
		if ( ApplicationPostType::POST_TYPE !== get_post_type( $post_id ) ) {
			return;
		}







		do_action( 'prc_application_restored', $post_id, get_current_user_id() );
	}







	public static function withdrawn( int $procedure_id ): array {
		if ( $procedure_id <= 0 ) {
			return array();
		}
		$posts = get_posts(
			array(
				'post_type'        => ApplicationPostType::POST_TYPE,
				'post_parent'      => $procedure_id,
				'post_status'      => 'trash',
				'numberposts'      => -1,
				'orderby'          => 'modified',
				'order'            => 'DESC',
				'suppress_filters' => true,
			)
		);
		return is_array( $posts ) ? $posts : array();
	}







	public static function withdrawn_count( int $procedure_id ): int {
		if ( $procedure_id <= 0 ) {
			return 0;
		}
		$ids = get_posts(
			array(
				'post_type'        => ApplicationPostType::POST_TYPE,
				'post_parent'      => $procedure_id,
				'post_status'      => 'trash',
				'numberposts'      => -1,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => true,
			)
		);
		return is_array( $ids ) ? count( $ids ) : 0;
	}













	public static function extend( int $application_id, int $user_id, string $until ): bool {
		$antes = ApplicationActions::late_until( $application_id );
		if ( $antes === $until ) {
			return false;
		}
		if ( '' === $until ) {
			delete_post_meta( $application_id, ApplicationMetaKeys::LATE_UNTIL );
		} else {
			update_post_meta( $application_id, ApplicationMetaKeys::LATE_UNTIL, $until );
		}









		do_action( 'prc_application_extended', $application_id, $until, $antes, $user_id );
		return true;
	}












	public static function set_internal_note( int $application_id, int $user_id, string $note ): bool {
		$note  = trim( $note );
		$antes = trim( (string) get_post_meta( $application_id, ApplicationMetaKeys::INTERNAL_NOTE, true ) );
		if ( $antes === $note ) {
			return false;
		}
		update_post_meta( $application_id, ApplicationMetaKeys::INTERNAL_NOTE, wp_slash( $note ) );







		do_action( 'prc_internal_note_changed', $application_id, $user_id );
		return true;
	}



















	public static function submit_for( int $procedure_id, int $user_id, string $code, string $until, string $ineligible = '' ): int {
		$centro = ApplicationActions::catalogue_centre( $code );
		if ( array() === $centro ) {
			return 0;
		}
		$codigo = (string) $centro['code'];
		$nombre = (string) ( $centro['name'] ?? '' );
		$id     = wp_insert_post(
			array(
				'post_type'   => ApplicationPostType::POST_TYPE,
				'post_parent' => $procedure_id,
				'post_author' => $user_id,
				'post_status' => 'publish',
				'post_title'  => wp_slash( ( '' !== $nombre ? $nombre : $codigo ) . ' — ' . (string) get_post_field( 'post_title', $procedure_id ) ),
			),
			true
		);
		if ( is_wp_error( $id ) || ! $id ) {
			return 0;
		}
		$id = (int) $id;
		foreach ( array(
			ApplicationMetaKeys::APPLICANT_TYPE => ApplicationMetaKeys::TYPE_CENTRE,
			ApplicationMetaKeys::CENTRE_CODE    => $codigo,
			ApplicationMetaKeys::CENTRE_NAME    => $nombre,
			ApplicationMetaKeys::REVIEW_STATE   => ApplicationMetaKeys::REVIEW_SUBMITTED,
			ApplicationMetaKeys::LATE_UNTIL     => $until,
			ApplicationMetaKeys::CREATED_BY     => $user_id,
		) as $clave => $valor ) {
			update_post_meta( $id, $clave, wp_slash( $valor ) );
		}

		if ( self::lost_race( $id, $procedure_id, $codigo ) ) {
			return 0;
		}








		do_action(
			'prc_application_created_for',
			$id,
			$user_id,
			array(
				'code'       => $codigo,
				'until'      => $until,
				'ineligible' => $ineligible,
			)
		);
		return $id;
	}







	public static function pending_for_school( int $application_id ): bool {
		$creada_por = (int) get_post_meta( $application_id, ApplicationMetaKeys::CREATED_BY, true );
		return $creada_por > 0 && (int) get_post_field( 'post_author', $application_id ) === $creada_por;
	}












	public static function adopt( int $application_id, int $user_id ): bool {
		if ( $user_id <= 0 || ! self::pending_for_school( $application_id ) ) {
			return false;
		}
		$hecho = wp_update_post(
			array(
				'ID'          => $application_id,
				'post_author' => $user_id,
			),
			true
		);
		return ! is_wp_error( $hecho ) && (int) $hecho > 0;
	}










	public static function accepted( array $application ): bool {
		return array() !== $application && ! self::pending_for_school( (int) ( $application['id'] ?? 0 ) );
	}
}

}








namespace Prc\PublicFront;

use Prc\Domain\ApplicationPeople;
use Prc\Meta\ApplicationMetaKeys;















if ( ! class_exists( 'Prc\PublicFront\ApplicationHistory', false ) ) {
final class ApplicationHistory {

	public const SUBMITTED     = 'submitted';
	public const UPDATED       = 'updated';
	public const REVIEWED      = 'review_changed';
	public const DELIVERED     = 'delivery';
	public const WITHDRAWN     = 'withdrawn';
	public const RESTORED      = 'restored';
	public const EXTENDED      = 'extended';
	public const CREATED_FOR   = 'created_for_centre';
	public const INTERNAL_NOTE = 'internal_note';






	public static function register(): void {
		add_action( 'prc_application_saved', array( self::class, 'on_saved' ), 5, 4 );
		add_action( 'prc_application_reviewed', array( self::class, 'on_reviewed' ), 5, 4 );
		add_action( 'prc_delivery_saved', array( self::class, 'on_delivery' ), 5, 3 );
		add_action( 'prc_people_changed', array( self::class, 'on_people' ), 5, 5 );
		add_action( 'prc_application_withdrawn', array( self::class, 'on_withdrawn' ), 5, 4 );
		add_action( 'prc_application_restored', array( self::class, 'on_restored' ), 5, 2 );
		add_action( 'prc_application_extended', array( self::class, 'on_extended' ), 5, 4 );
		add_action( 'prc_application_created_for', array( self::class, 'on_created_for' ), 5, 3 );
		add_action( 'prc_internal_note_changed', array( self::class, 'on_internal_note' ), 5, 2 );
	}
















	public static function on_saved( $application_id, $created = false, $user_id = 0, $previous = '' ): void {
		ApplicationLog::add( (int) $application_id, $created ? self::SUBMITTED : self::UPDATED, array(), (int) $user_id );
		$previous = (string) $previous;
		if ( $created || '' === $previous || ApplicationMetaKeys::REVIEW_SUBMITTED === $previous ) {
			return;
		}
		ApplicationLog::add(
			(int) $application_id,
			self::REVIEWED,
			array(
				'state'        => ApplicationMetaKeys::REVIEW_SUBMITTED,
				'previous'     => $previous,
				'note_changed' => false,
				'by'           => 'save',
			),
			(int) $user_id
		);
	}










	public static function on_reviewed( $application_id, $state = '', $previous = '', $previous_note = '' ): void {
		$nota   = trim( (string) get_post_meta( (int) $application_id, ApplicationMetaKeys::REVIEW_NOTE, true ) );
		$antes  = '' !== (string) $previous ? (string) $previous : ApplicationMetaKeys::REVIEW_SUBMITTED;
		$cambia = trim( (string) $previous_note ) !== $nota;
		if ( (string) $state === $antes && ! $cambia ) {
			return;
		}
		ApplicationLog::add(
			(int) $application_id,
			self::REVIEWED,
			array(
				'state'        => (string) $state,
				'previous'     => $antes,
				'note_changed' => $cambia,
			)
		);
	}









	public static function on_delivery( $application_id, $question_key = '', $user_id = 0 ): void {
		ApplicationLog::add( (int) $application_id, self::DELIVERED, array( 'question' => (string) $question_key ), (int) $user_id );
	}











	public static function on_people( $application_id, $change = '', $before = array(), $after = array(), $user_id = 0 ): void {
		ApplicationLog::add(
			(int) $application_id,
			'person_' . sanitize_key( (string) $change ),
			array(
				'before' => (array) $before,
				'after'  => (array) $after,
			),
			(int) $user_id
		);
	}










	public static function on_withdrawn( $application_id, $user_id = 0, $by = '', $reason = '' ): void {
		ApplicationLog::add(
			(int) $application_id,
			self::WITHDRAWN,
			array(
				'by'     => (string) $by,
				'reason' => (string) $reason,
			),
			(int) $user_id
		);
	}








	public static function on_restored( $application_id, $user_id = 0 ): void {
		ApplicationLog::add( (int) $application_id, self::RESTORED, array(), (int) $user_id );
	}










	public static function on_extended( $application_id, $until = '', $previous = '', $user_id = 0 ): void {
		ApplicationLog::add(
			(int) $application_id,
			self::EXTENDED,
			array(
				'until'    => (string) $until,
				'previous' => (string) $previous,
			),
			(int) $user_id
		);
	}









	public static function on_created_for( $application_id, $user_id = 0, $data = array() ): void {
		$data = (array) $data;
		ApplicationLog::add(
			(int) $application_id,
			self::CREATED_FOR,
			array(
				'until'      => (string) ( $data['until'] ?? '' ),
				'ineligible' => (string) ( $data['ineligible'] ?? '' ),
			),
			(int) $user_id
		);
	}








	public static function on_internal_note( $application_id, $user_id = 0 ): void {
		ApplicationLog::add( (int) $application_id, self::INTERNAL_NOTE, array(), (int) $user_id );
	}












	public static function entries( int $application_id ): array {
		$procedure_id = (int) wp_get_post_parent_id( $application_id );
		$spec         = People::spec( $procedure_id );
		$preguntas    = array_column( Applications::questions( $procedure_id ), null, 'key' );
		$entradas     = array_reverse( ApplicationLog::all( $application_id ) );
		cache_users( array_values( array_unique( array_filter( array_column( $entradas, 'user' ) ) ) ) );
		$out = array();
		foreach ( $entradas as $entrada ) {
			$linea = self::line( $entrada, $spec, $preguntas );
			if ( null === $linea ) {
				continue;
			}
			$cuenta = $entrada['user'] > 0 ? get_userdata( $entrada['user'] ) : false;
			$out[]  = $linea + array(
				'when' => '' !== $entrada['at'] ? (string) wp_date( 'd-m-Y H:i', (int) strtotime( $entrada['at'] ) ) : '',
				'who'  => $entrada['user'] <= 0 ? '' : ( false !== $cuenta ? (string) $cuenta->display_name : '(cuenta que ya no existe)' ),
			);
		}
		return $out;
	}









	private static function line( array $entrada, array $spec, array $preguntas ): ?array {
		$data   = (array) $entrada['data'];
		$fijos  = array(
			self::SUBMITTED         => 'Solicitud presentada',
			self::UPDATED           => 'Solicitud modificada',
			self::RESTORED          => 'Solicitud restaurada desde «Retiradas»',
			self::INTERNAL_NOTE     => 'Nota interna cambiada',
			ApplicationLog::TRIMMED => sprintf( 'Se han descartado las %d entradas más antiguas del historial.', (int) ( $data['dropped'] ?? 0 ) ),
		);
		$evento = (string) $entrada['event'];
		if ( isset( $fijos[ $evento ] ) ) {
			return self::words( $fijos[ $evento ] );
		}
		$personas = array(
			'person_added'   => 'Alta en la lista de personas',
			'person_changed' => 'Cambio en la lista de personas',
			'person_removed' => 'Baja en la lista de personas',
		);
		if ( isset( $personas[ $evento ] ) ) {
			return self::words( $personas[ $evento ], self::person( $spec, (array) ( $data['before'] ?? array() ) ), self::person( $spec, (array) ( $data['after'] ?? array() ) ) );
		}
		return self::described( $evento, $data, $preguntas );
	}









	private static function described( string $evento, array $data, array $preguntas ): ?array {
		$como = array(
			self::REVIEWED    => 'reviewed',
			self::WITHDRAWN   => 'withdrawn',
			self::EXTENDED    => 'extended',
			self::CREATED_FOR => 'created_for',
		);
		if ( self::DELIVERED === $evento ) {
			$clave = (string) ( $data['question'] ?? '' );
			return self::words( 'Documento entregado' . ( isset( $preguntas[ $clave ] ) ? ': ' . (string) $preguntas[ $clave ]['label'] : '' ) );
		}
		return isset( $como[ $evento ] ) ? call_user_func( array( self::class, $como[ $evento ] ), $data ) : null;
	}







	private static function reviewed( array $data ): array {
		$estados = ApplicationMetaKeys::review_states();
		$estado  = (string) ( $estados[ (string) ( $data['state'] ?? '' ) ] ?? '' );
		$antes   = (string) ( $estados[ (string) ( $data['previous'] ?? '' ) ] ?? '' );
		if ( $antes === $estado ) {
			return self::words( 'Revisión: nota cambiada' );
		}
		$porque = 'save' === ( $data['by'] ?? '' ) ? ', al modificarla quien la presentó' : ( empty( $data['note_changed'] ) ? '' : ', con nota nueva' );
		return self::words( 'Revisión: ' . $estado . $porque, $antes, $estado );
	}







	private static function withdrawn( array $data ): array {
		$quien  = array(
			ApplicationOperations::BY_APPLICANT => 'Retirada por el centro',
			ApplicationOperations::BY_REVIEWER  => 'Retirada por quien revisa',
		);
		$motivo = trim( (string) ( $data['reason'] ?? '' ) );
		return self::words( ( $quien[ (string) ( $data['by'] ?? '' ) ] ?? 'Enviada a la papelera desde el escritorio' ) . ( '' !== $motivo ? '. Motivo: ' . $motivo : '' ) );
	}







	private static function extended( array $data ): array {
		$hasta = (string) ( $data['until'] ?? '' );
		return self::words( '' === $hasta ? 'Prórroga quitada' : 'Prórroga hasta el ' . self::day( $hasta ), self::day( (string) ( $data['previous'] ?? '' ) ), self::day( $hasta ) );
	}







	private static function created_for( array $data ): array {
		$porque = trim( (string) ( $data['ineligible'] ?? '' ) );
		return self::words( 'Presentada en nombre del centro, con plazo para completarla hasta el ' . self::day( (string) ( $data['until'] ?? '' ) ) . ( '' !== $porque ? '. Aunque no cumple los requisitos: ' . $porque : '' ) );
	}









	private static function words( string $what, string $before = '', string $after = '' ): array {
		return array(
			'what'   => $what,
			'before' => $before,
			'after'  => $after,
		);
	}








	private static function person( array $spec, array $row ): string {
		if ( array() === $row ) {
			return '';
		}
		$dni = ApplicationPeople::masked_dni( (string) ( $row['dni'] ?? '' ) );
		return ApplicationPeople::describe( $spec, $row ) . ( '' !== $dni ? ' · DNI ' . $dni : '' );
	}







	private static function day( string $ymd ): string {
		return '' === $ymd ? '' : implode( '-', array_reverse( explode( '-', $ymd ) ) );
	}
}

}








namespace Prc\PublicFront;

use Prc\Access\ProcedureAccess;
use Prc\Domain\ApplicationPeople;
use Prc\Domain\PeopleSpec;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\MetaSwap;
use Prc\Meta\ProcedureMetaKeys;



















if ( ! class_exists( 'Prc\PublicFront\People', false ) ) {
final class People {

	public const ADDED   = 'added';
	public const CHANGED = 'changed';
	public const REMOVED = 'removed';







	public static function spec( int $procedure_id ): array {
		return PeopleSpec::sanitize( $procedure_id > 0 ? get_post_meta( $procedure_id, ProcedureMetaKeys::PEOPLE_SPEC, true ) : array() );
	}









	public static function enabled( int $procedure_id ): bool {
		return true === self::spec( $procedure_id )['enabled']
			&& ProcedureMetaKeys::AUDIENCE_TEACHERS !== get_post_meta( $procedure_id, ProcedureMetaKeys::AUDIENCE, true );
	}







	public static function of( int $application_id ): array {
		return ApplicationPeople::rows( get_post_meta( $application_id, ApplicationMetaKeys::PEOPLE, true ) );
	}









	public static function add( int $application_id, int $user_id, array $raw ): array {
		$raw['id'] = '';
		return self::apply( $application_id, $user_id, self::ADDED, '', '', $raw );
	}











	public static function change( int $application_id, int $user_id, string $id, string $huella, array $raw ): array {
		return self::apply( $application_id, $user_id, self::CHANGED, $id, $huella, $raw );
	}










	public static function remove( int $application_id, int $user_id, string $id, string $huella ): array {
		return self::apply( $application_id, $user_id, self::REMOVED, $id, $huella, array() );
	}



















	private static function apply( int $application_id, int $user_id, string $change, string $id, string $huella, array $raw ): array {
		if ( ! user_can( $user_id, ProcedureAccess::CAP_EDIT_PEOPLE, $application_id ) ) {
			return self::refused( ProcedureAccess::why_not_people( $user_id, $application_id ) );
		}
		$spec  = self::spec( (int) wp_get_post_parent_id( $application_id ) );
		$nueva = self::ADDED === $change ? bin2hex( random_bytes( 6 ) ) : $id;
		$res   = MetaSwap::change(
			$application_id,
			ApplicationMetaKeys::PEOPLE,
			static function ( $leido ) use ( $spec, $change, $id, $nueva, $huella, $raw ): array {
				return self::compute( $spec, ApplicationPeople::rows( $leido ), $change, $id, $nueva, $huella, $raw );
			}
		);
		if ( 'conflict' === $res['error'] ) {
			return self::refused( 'Otra cuenta de su centro estaba cambiando la lista a la vez y no se ha podido guardar sin pisar su cambio. Revise la lista tal como está ahora y vuelva a intentarlo.' );
		}
		if ( ! $res['ok'] || ! empty( $res['skip'] ) ) {
			return array(
				'ok'      => (bool) $res['ok'],
				'error'   => (string) $res['error'],
				'errors'  => (array) ( $res['errors'] ?? array() ),
				'changed' => false,
				'stale'   => ! empty( $res['stale'] ),
			);
		}
		return self::written( $application_id, $user_id, $change, (array) $res['before'], (array) $res['after'] );
	}













	private static function compute( array $spec, array $lista, string $change, string $id, string $nueva, string $huella, array $raw ): array {
		$sitio = self::position( $lista, $id );
		$stale = self::ADDED === $change ? '' : self::stale( $lista, $sitio, $huella );
		if ( '' !== $stale ) {
			return array(
				'error' => $stale,
				'stale' => true,
			);
		}
		$antes = null === $sitio ? array() : $lista[ $sitio ];
		if ( self::REMOVED === $change ) {
			array_splice( $lista, (int) $sitio, 1 );
			return array(
				'value'  => array_values( $lista ),
				'before' => $antes,
				'after'  => array(),
			);
		}
		$fila       = array( 'id' => $nueva ) + ApplicationPeople::row( $raw );
		$fila       = ApplicationPeople::row( $fila );
		$errores    = ApplicationPeople::validate( $spec, $lista, self::ADDED === $change ? array( 'id' => '' ) + $fila : $fila );
		$lista[]    = $fila;
		$sin_cambio = $antes === $fila;
		if ( null !== $sitio ) {
			array_pop( $lista );
			$lista[ $sitio ] = $fila;
		}
		return array(
			'value'  => array_values( $lista ),
			'error'  => array() !== $errores ? self::why( $errores ) : '',
			'errors' => $errores,

			'skip'   => array() === $errores && $sin_cambio,
			'before' => $antes,
			'after'  => $fila,
		);
	}









	private static function stale( array $rows, ?int $where, string $huella ): string {
		if ( null === $where ) {
			return 'Esa persona ya no está en la lista: otra cuenta de su centro la ha dado de baja mientras tanto. Revise la lista tal como está ahora.';
		}
		if ( ! hash_equals( ApplicationPeople::fingerprint( $rows[ $where ] ), $huella ) ) {
			return 'Otra cuenta de su centro ha cambiado esta persona mientras usted la tenía abierta, y no se ha guardado nada para no pisar su cambio. Abajo están sus datos actuales: revíselos y vuelva a intentarlo.';
		}
		return '';
	}











	private static function written( int $application_id, int $user_id, string $change, array $before, array $after ): array {













		do_action( 'prc_people_changed', $application_id, $change, $before, $after, $user_id );

		return array(
			'ok'      => true,
			'error'   => '',
			'errors'  => array(),
			'changed' => true,
			'stale'   => false,
		);
	}








	private static function position( array $rows, string $id ): ?int {
		foreach ( $rows as $i => $fila ) {
			if ( '' !== $id && $fila['id'] === $id ) {
				return (int) $i;
			}
		}
		return null;
	}









	public static function used_keys( int $procedure_id ): array {
		$out = array(
			'roles'  => array(),
			'groups' => array(),
		);
		foreach ( Applications::for_procedure( $procedure_id ) as $solicitud ) {
			foreach ( self::of( (int) $solicitud->ID ) as $fila ) {
				$out['roles'][ $fila['role'] ] = true;
				if ( '' !== $fila['group'] ) {
					$out['groups'][ $fila['group'] ] = true;
				}
			}
		}
		return $out;
	}







	public static function why( array $errors ): string {
		$campos = array_intersect_key( $errors, array_flip( array( 'name', 'surname', 'dni', 'email', 'phone', 'role', 'group' ) ) );
		if ( array() === $campos ) {
			return (string) reset( $errors );
		}
		$ultimo = array_pop( $campos );
		return 'Revise ' . ( array() === $campos ? '' : implode( ', ', $campos ) . ' y ' ) . $ultimo . '.';
	}







	private static function refused( string $why ): array {
		return array(
			'ok'      => false,
			'error'   => $why,
			'errors'  => array(),
			'changed' => false,
			'stale'   => false,
		);
	}
}

}








namespace Prc\PublicFront;

use Prc\Domain\ApplicationPeople;
use Prc\Domain\QuestionGrid;








if ( ! class_exists( 'Prc\PublicFront\PeopleExport', false ) ) {
final class PeopleExport {






	public const COLUMNS = array(
		'person_name'    => 'Nombre (persona)',
		'person_surname' => 'Apellidos (persona)',
		'person_dni'     => 'DNI o NIE (persona)',
		'person_email'   => 'Correo (persona)',
		'person_phone'   => 'Teléfono (persona)',
		'person_role'    => 'Papel (persona)',
		'person_group'   => 'Grupo (persona)',
	);








	public const VERBATIM = array( 'person_phone' => '/^\+?[0-9][0-9 ]*$/' );






	private const FIELDS = array(
		'person_name'    => 'name',
		'person_surname' => 'surname',
		'person_dni'     => 'dni',
		'person_email'   => 'email',
		'person_phone'   => 'phone',
		'person_role'    => 'role_label',
		'person_group'   => 'group_label',
	);













	public static function all_columns( int $procedure_id, bool $centre = false ): array {
		$preguntas = Applications::questions( $procedure_id );
		$columns   = QuestionGrid::expand( Applications::columns( $preguntas, $centre ), $preguntas );
		if ( ! People::enabled( $procedure_id ) ) {
			return $columns;
		}
		$out = array();
		foreach ( $columns as $clave => $rotulo ) {
			$out[ $clave ] = $rotulo;
			if ( 'coordinator_email' === $clave ) {
				$out += self::COLUMNS;
			}
		}
		return $out;
	}









	public static function cells( ?array $spec, array $rows ): array {
		if ( null === $spec ) {
			return array();
		}
		$filas = PeopleForm::rows( $spec, $rows );
		$out   = array();
		foreach ( self::FIELDS as $columna => $campo ) {

			$out[ $columna ] = implode( ' · ', array_column( $filas, $campo ) );
		}
		return $out;
	}











	public static function rows( ?array $spec, array $row, int $application_id, array $keys ): array {
		$out   = array();
		$salen = null !== $spec && array() !== array_intersect_key( $keys, self::COLUMNS );
		foreach ( $salen ? People::of( $application_id ) : array() as $persona ) {
			$out[] = array_merge(
				$row,
				array_fill_keys( array( 'coordinator', 'coordinator_dni', 'coordinator_email' ), '' ),
				self::cells( $spec, array( $persona ) )
			);
		}
		return $out;
	}







	public static function spec( int $procedure_id ): ?array {
		return People::enabled( $procedure_id ) ? People::spec( $procedure_id ) : null;
	}










	public static function offered( array $items, string $key, int $procedure_id ): array {
		if ( $procedure_id > 0 && ! People::enabled( $procedure_id ) ) {
			unset( $items[ $key ] );
		}
		return $items;
	}











	public static function users( int $application_id, $people = null ): array {
		$out = array();

		$lista = array();
		if ( People::enabled( (int) wp_get_post_parent_id( $application_id ) ) ) {
			$lista = null === $people ? People::of( $application_id ) : ApplicationPeople::rows( $people );
		}
		foreach ( $lista as $persona ) {
			$out[] = array(
				'source'         => ApplicationExport::WHO_PEOPLE,
				'application_id' => $application_id,
				'user_id'        => 0,
				'username'       => '',
				'firstname'      => $persona['name'],
				'lastname'       => $persona['surname'],
				'email'          => $persona['email'],
			);
		}
		return $out;
	}
}

}









namespace Prc\PublicFront;

use Prc\Domain\CentreCatalog;
use Prc\Domain\ProcedureInput;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ApplicationMetaRegistration;
use Prc\PostType\ApplicationPostType;
use Prc\PostType\ProcedurePostType;
















if ( ! class_exists( 'Prc\PublicFront\ApplicationExport', false ) ) {
final class ApplicationExport {


	public const SHAPE_APPLICATION = 'application';


	public const SHAPE_PERSON = 'person';

	public const FORMAT_CSV    = 'csv';
	public const FORMAT_MOODLE = 'moodle';


	public const WHO_COORDINATORS = 'coordinators';


	public const WHO_APPLICANT = 'applicant';


	public const WHO_PEOPLE = 'people';


	public const MOODLE_HEADER = 'username,firstname,lastname,email';




	public const CHOICE_META = 'prc_export_';


	private const BATCH = 100;






	private const GROUPS = array(
		'application'  => array( 'date' ),
		'centre'       => array( 'centre', 'code', 'island', 'municipality', 'centre_type', 'ownership', 'zone', 'zone_code' ),
		'applicant'    => array( 'applicant', 'email', 'position' ),
		'coordination' => array( 'coordinator', 'coordinator_dni', 'coordinator_email' ),
		'people'       => array( 'person_name', 'person_surname', 'person_dni', 'person_email', 'person_phone', 'person_role', 'person_group' ),
		'review'       => array( 'state', 'note', 'score', 'internal_note' ),
	);






	public static function register(): void {
		add_action( 'before_delete_post', array( self::class, 'on_delete' ), 10, 2 );
	}










	public static function on_delete( int $post_id, $post = null ): void {
		$tipo = $post instanceof \WP_Post ? (string) $post->post_type : (string) get_post_type( $post_id );
		if ( ProcedurePostType::POST_TYPE === $tipo ) {
			delete_metadata( 'user', 0, self::CHOICE_META . $post_id, '', true );
		}
	}












	public static function group_labels( int $procedure_id = 0 ): array {
		$out = array(
			'application'  => 'Solicitud',
			'centre'       => 'Centro',
			'applicant'    => 'Quien presenta',
			'coordination' => 'Coordinación',
			'people'       => 'Personas',
			'review'       => 'Revisión',
			'questions'    => 'Preguntas',
		);
		return PeopleExport::offered( $out, 'people', $procedure_id );
	}













	public static function groups( int $procedure_id ): array {
		$todas = PeopleExport::all_columns( $procedure_id, true );
		$out   = array();
		$fijas = array();
		foreach ( self::GROUPS as $grupo => $claves ) {
			$out[ $grupo ] = array_intersect_key( $todas, array_flip( $claves ) );
			$fijas        += $out[ $grupo ];
		}
		$out['questions'] = array_diff_key( $todas, $fijas );

		return PeopleExport::offered( $out, 'people', $procedure_id );
	}








	private static function valid_columns( int $procedure_id, array $keys ): array {
		$todas = PeopleExport::all_columns( $procedure_id, true );
		$out   = array();
		foreach ( $keys as $clave ) {
			if ( is_string( $clave ) && isset( $todas[ $clave ] ) ) {
				$out[ $clave ] = $todas[ $clave ];
			}
		}
		return $out;
	}




















	public static function csv( int $procedure_id, array $columns = array(), string $shape = self::SHAPE_APPLICATION, ?array $ids = null ): string {
		$preguntas = Applications::questions( $procedure_id );
		$columnas  = CentreColumns::ordered( self::valid_columns( $procedure_id, $columns ), PeopleExport::all_columns( $procedure_id ), PeopleExport::all_columns( $procedure_id, true ) );
		$personas  = PeopleExport::spec( $procedure_id );
		$claves    = array_flip( array_keys( $columnas ) );
		$filas     = array();

		$catalogo = array() !== array_intersect_key( $claves, array_flip( CentreColumns::KEYS ) ) ? CentreCatalog::index() : array();
		foreach ( self::batches( $procedure_id, $ids ) as $tanda ) {
			foreach ( $tanda as $solicitud ) {
				$fila = Applications::row( $solicitud, $preguntas, $catalogo ) + PeopleExport::cells( $personas, People::of( (int) $solicitud->ID ) );
				foreach ( self::SHAPE_PERSON === $shape ? self::per_person( $solicitud, $fila, $personas, $claves ) : array( $fila ) as $una ) {

					$filas[] = array_intersect_key( $una, $claves );
				}
			}
		}
		return Applications::csv_lines( $columnas, $filas, PeopleExport::VERBATIM );
	}



















	private static function per_person( \WP_Post $application, array $row, ?array $people, array $keys ): array {


		$coordinacion = array() !== array_intersect_key( $keys, array_flip( self::GROUPS['coordination'] ) );
		$out          = array();
		foreach ( $coordinacion ? self::coordinators( $application ) : array() as $persona ) {
			$out[] = array_merge(
				$row,
				PeopleExport::cells( $people, array() ),
				array(
					'coordinator'       => ApplicationMetaRegistration::full_name( $persona ),
					'coordinator_dni'   => $persona['dni'],
					'coordinator_email' => $persona['email'],
				)
			);
		}
		$out = array_merge( $out, PeopleExport::rows( $people, $row, (int) $application->ID, $keys ) );
		return array() === $out ? array( $row ) : $out;
	}













	public static function who( int $procedure_id = 0 ): array {
		$out = array(
			self::WHO_COORDINATORS => 'Coordinación',
			self::WHO_APPLICANT    => 'Quien presenta la solicitud',
			self::WHO_PEOPLE       => 'Personas de la solicitud',
		);

		return PeopleExport::offered( $out, self::WHO_PEOPLE, $procedure_id );
	}































	public static function people( int $procedure_id, array $who = array(), ?array $ids = null ): array {
		$cuenta = self::tally( self::batches( $procedure_id, $ids ), array( 'all' => self::sources( $who ) ) )['all'];
		return array(
			'people'   => array_values( $cuenta['people'] ),
			'left_out' => $cuenta['left_out'],
		);
	}
















	public static function who_counts( array $applications, array $who = array(), array $stored = array() ): array {
		$grupos = array();
		foreach ( self::sources( $who ) as $fuente ) {
			$grupos[ $fuente ] = array( $fuente );
		}
		$out = array();
		foreach ( self::tally( array( $applications ), $grupos, $stored ) as $fuente => $cuenta ) {
			$out[ $fuente ] = array( 'count' => count( $cuenta['people'] ) ) + $cuenta['left_out'];
		}
		return $out;
	}







	private static function sources( array $who ): array {
		$fuentes = array_values( array_intersect( array_keys( self::who() ), $who ) );
		return array() === $fuentes ? array_keys( self::who() ) : $fuentes;
	}









	private static function tally( iterable $batches, array $groups, array $stored = array() ): array {
		$out = array();
		foreach ( array_keys( $groups ) as $grupo ) {
			$out[ $grupo ] = array(
				'people'   => array(),
				'left_out' => array(
					'email'      => 0,
					'incomplete' => 0,
					'conflict'   => 0,
					'invalid'    => 0,
				),
			);
		}
		foreach ( $batches as $tanda ) {
			foreach ( $tanda as $solicitud ) {

				$de = array();
				foreach ( $groups as $grupo => $fuentes ) {
					foreach ( $fuentes as $fuente ) {
						$de[ $fuente ] = $de[ $fuente ] ?? self::from_source( $fuente, $solicitud, (array) ( $stored[ (int) $solicitud->ID ] ?? array() ) );
						foreach ( $de[ $fuente ] as $persona ) {
							self::add( $out[ $grupo ], $persona );
						}
					}
				}
			}
		}
		return $out;
	}








	private static function add( array &$tally, array $person ): void {
		if ( ! ProcedureInput::is_email( (string) $person['email'] ) ) {
			++$tally['left_out']['email'];
			return;
		}
		$person['username'] = self::username( $person );
		if ( '' === $person['username'] || '' === $person['firstname'] || '' === $person['lastname'] ) {
			++$tally['left_out']['incomplete'];
			return;
		}
		if ( ! self::is_safe_username( self::lower( $person['username'] ) ) || self::neutralize( (string) $person['email'] ) !== (string) $person['email'] ) {
			++$tally['left_out']['invalid'];
			return;
		}
		$clave = self::lower( $person['username'] );
		if ( ! isset( $tally['people'][ $clave ] ) ) {
			$tally['people'][ $clave ] = $person;
		} elseif ( self::lower( (string) $tally['people'][ $clave ]['email'] ) !== self::lower( (string) $person['email'] ) ) {
			++$tally['left_out']['conflict'];
		}
	}









	private static function is_safe_username( string $username ): bool {
		return 1 === preg_match( '/^[a-z0-9][a-z0-9_.@-]*$/', $username );
	}







	private static function lower( string $value ): string {
		return function_exists( 'mb_strtolower' ) ? mb_strtolower( $value ) : strtolower( $value );
	}








	public static function moodle( int $procedure_id, array $options = array() ): string {
		$who = is_array( $options['who'] ?? null ) ? $options['who'] : array();
		$ids = is_array( $options['ids'] ?? null ) ? $options['ids'] : null;
		return self::moodle_lines( self::people( $procedure_id, $who, $ids )['people'] );
	}
















	public static function moodle_lines( array $people ): string {
		$lineas = array( self::MOODLE_HEADER );
		foreach ( $people as $persona ) {
			$campos = array();
			foreach ( array( 'username', 'firstname', 'lastname', 'email' ) as $clave ) {
				$valor    = (string) ( $persona[ $clave ] ?? '' );
				$campos[] = self::moodle_cell( 'firstname' === $clave || 'lastname' === $clave ? self::neutralize( $valor ) : $valor );
			}
			$lineas[] = implode( ',', $campos );
		}
		return implode( "\n", $lineas );
	}







	private static function moodle_cell( string $value ): string {
		return false === strpbrk( $value, ",\"\r\n" ) ? $value : '"' . str_replace( '"', '""', $value ) . '"';
	}







	public static function neutralize( string $value ): string {
		return '' !== $value && false !== strpos( "=+-@\t\r", $value[0] ) ? "'" . $value : $value;
	}









	private static function from_source( string $source, \WP_Post $application, array $stored = array() ): array {
		switch ( $source ) {
			case self::WHO_APPLICANT:
				return self::applicant( $application );
			case self::WHO_COORDINATORS:
				$out = array();
				foreach ( self::coordinators( $application, $stored ) as $persona ) {

					if ( '' === $persona['name'] . $persona['surname'] . $persona['email'] ) {
						continue;
					}
					$persona = ApplicationMetaRegistration::with_surname( $persona );
					$out[]   = self::person( self::WHO_COORDINATORS, $application, 0, '', $persona['name'], $persona['surname'], $persona['email'] );
				}
				return $out;
			case self::WHO_PEOPLE:


				return PeopleExport::users( (int) $application->ID, array_key_exists( ApplicationMetaKeys::PEOPLE, $stored ) ? $stored[ ApplicationMetaKeys::PEOPLE ] : null );
		}
		return array();
	}











	private static function applicant( \WP_Post $application ): array {
		$cuenta = get_userdata( (int) $application->post_author );
		if ( false === $cuenta ) {
			return array();
		}
		$nombre    = trim( (string) $cuenta->first_name );
		$apellidos = trim( (string) $cuenta->last_name );


		$publico = (string) $cuenta->display_name;
		if ( ( '' === $nombre || '' === $apellidos ) && $publico !== (string) $cuenta->user_login ) {
			$partido   = ApplicationMetaRegistration::split_name( $publico );
			$nombre    = '' !== $nombre ? $nombre : $partido[0];
			$apellidos = '' !== $apellidos ? $apellidos : $partido[1];
		}
		return array( self::person( self::WHO_APPLICANT, $application, (int) $cuenta->ID, (string) $cuenta->user_login, $nombre, $apellidos, (string) $cuenta->user_email ) );
	}













	private static function person( string $source, \WP_Post $application, int $user_id, string $login, string $firstname, string $lastname, string $email ): array {
		return array(
			'source'         => $source,
			'application_id' => (int) $application->ID,
			'user_id'        => $user_id,
			'username'       => $login,
			'firstname'      => $firstname,
			'lastname'       => $lastname,
			'email'          => trim( $email ),
		);
	}











	private static function username( array $person ): string {
		$usuario = (string) $person['username'];
		if ( '' === $usuario ) {
			$arroba  = strrpos( (string) $person['email'], '@' );
			$usuario = false === $arroba ? '' : strtolower( substr( (string) $person['email'], 0, $arroba ) );
		}







		$usuario = apply_filters( 'prc_export_username', $usuario, $person );
		return is_string( $usuario ) ? trim( $usuario ) : '';
	}
















	public static function sanitize_choice( int $procedure_id, array $raw ): array {
		$columnas = is_array( $raw['columns'] ?? null ) ? $raw['columns'] : array();
		$quienes  = is_array( $raw['who'] ?? null ) ? array_filter( $raw['who'], 'is_string' ) : array();
		if ( ! empty( $raw['all'] ) ) {

			$columnas = CentreColumns::all_plus( $columnas, array_keys( PeopleExport::all_columns( $procedure_id ) ) );
		}
		return array(
			'columns' => array_keys( self::valid_columns( $procedure_id, $columnas ) ),
			'shape'   => self::SHAPE_PERSON === ( $raw['shape'] ?? '' ) ? self::SHAPE_PERSON : self::SHAPE_APPLICATION,
			'format'  => self::FORMAT_MOODLE === ( $raw['format'] ?? '' ) ? self::FORMAT_MOODLE : self::FORMAT_CSV,
			'who'     => array_values( array_intersect( array_keys( self::who( $procedure_id ) ), $quienes ) ),
		);
	}










	public static function choice( int $user_id, int $procedure_id ): array {
		$guardada = get_user_meta( $user_id, self::CHOICE_META . $procedure_id, true );
		return self::sanitize_choice( $procedure_id, is_array( $guardada ) ? $guardada : array() );
	}









	public static function remember( int $user_id, int $procedure_id, array $choice ): void {
		if ( $user_id > 0 && $procedure_id > 0 ) {
			update_user_meta( $user_id, self::CHOICE_META . $procedure_id, self::sanitize_choice( $procedure_id, $choice ) );
		}
	}








	public static function filename( string $title, string $format ): string {
		$nombre = Applications::filename( $title );
		return self::FORMAT_MOODLE === $format ? 'usuarios-' . substr( $nombre, strlen( 'solicitudes-' ) ) : $nombre;
	}










	private static function coordinators( \WP_Post $application, array $stored = array() ): array {
		$valor = array_key_exists( ApplicationMetaKeys::COORDINATOR, $stored ) ? $stored[ ApplicationMetaKeys::COORDINATOR ] : get_post_meta( (int) $application->ID, ApplicationMetaKeys::COORDINATOR, true );
		return ApplicationMetaRegistration::sanitize_coordinator( $valor );
	}















	private static function batches( int $procedure_id, ?array $ids = null ): \Generator {
		if ( $procedure_id <= 0 || array() === $ids ) {
			return;
		}
		$solo = null === $ids ? array() : array( 'post__in' => array_map( 'intval', $ids ) );
		for ( $pagina = 1; ; ++$pagina ) {
			$tanda = get_posts(
				$solo + array(
					'post_type'        => ApplicationPostType::POST_TYPE,
					'post_parent'      => $procedure_id,
					'post_status'      => 'publish',
					'posts_per_page'   => self::BATCH,
					'paged'            => $pagina,
					'no_found_rows'    => true,


					'orderby'          => array(
						'date' => 'ASC',
						'ID'   => 'ASC',
					),
					'suppress_filters' => false,
				)
			);
			if ( ! is_array( $tanda ) || array() === $tanda ) {
				return;
			}

			cache_users( array_unique( array_map( 'intval', wp_list_pluck( $tanda, 'post_author' ) ) ) );
			yield $tanda;
			if ( count( $tanda ) < self::BATCH ) {
				return;
			}
		}
	}
}

}









namespace Prc\PublicFront;

use Prc\Domain\ApplicationCompleteness;
use Prc\Domain\ApplicationFilter;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\PostType\ApplicationPostType;

























if ( ! class_exists( 'Prc\PublicFront\ApplicationQuery', false ) ) {
final class ApplicationQuery {






	private const CENSUS = array(
		ApplicationMetaKeys::REVIEW_STATE,
		ApplicationMetaKeys::CENTRE_CODE,
		ApplicationMetaKeys::CENTRE_NAME,
		ApplicationMetaKeys::APPLICANT_TYPE,
		ApplicationMetaKeys::COORDINATOR,
		ApplicationMetaKeys::CREATED_BY,
	);









	public static function run( int $procedure_id, array $filter, array $scope = array() ): array {
		$census = self::census( $procedure_id, $scope );

		$catalogo = CentreColumns::index_for( array_filter( array_column( $census['stored'], ApplicationMetaKeys::CENTRE_CODE ) ) );
		$opciones = self::options( $census['stored'], $catalogo );
		$filter   = ApplicationFilter::against( $filter, $opciones );

		$filter['incomplete'] = $census['check'] && true === $filter['incomplete'];
		$motivos              = $census['check'] ? self::reasons( $procedure_id, $census['stored'], $census['pending'] ) : null;
		$ids                  = self::narrow( $census, $filter, $catalogo, $motivos ?? array() );
		$ventana              = ApplicationFilter::window( count( $ids ), (int) $filter['page'] );

		$filter['page'] = $ventana['page'];
		$pagina         = array_slice( $ids, $ventana['offset'], ApplicationFilter::PER_PAGE );
		return array(
			'filter'            => $filter,
			'active'            => ApplicationFilter::active( $filter ),
			'ids'               => $ids,
			'all'               => count( $census['posts'] ),
			'total'             => count( $ids ),
			'counts'            => self::counts( $census['stored'] ),
			'incomplete'        => count( $motivos ?? array() ),
			'may_be_incomplete' => null !== $motivos,
			'options'           => $opciones,
			'posts'             => self::page( $pagina ),
			'reasons'           => array_intersect_key( $motivos ?? array(), array_flip( $pagina ) ),
			'stored'            => $census['stored'],
		) + $ventana;
	}











	public static function ids( int $procedure_id, array $filter, array $scope = array() ): array {
		return self::run( $procedure_id, $filter, $scope )['ids'];
	}








	public static function state_of( $state ): string {
		$state = is_string( $state ) ? $state : '';
		return isset( ApplicationMetaKeys::review_states()[ $state ] ) ? $state : ApplicationMetaKeys::REVIEW_SUBMITTED;
	}











	private static function census( int $procedure_id, array $scope ): array {
		$vacio = array(
			'posts'   => array(),
			'stored'  => array(),
			'check'   => false,
			'pending' => array(),
		);
		if ( $procedure_id <= 0 ) {
			return $vacio;
		}
		$posts = get_posts(
			array(
				'post_type'              => ApplicationPostType::POST_TYPE,
				'post_parent'            => $procedure_id,
				'post_status'            => 'publish',
				'numberposts'            => -1,

				'orderby'                => array(
					'date' => 'ASC',
					'ID'   => 'ASC',
				),

				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'suppress_filters'       => false,
			)
		);
		if ( ! is_array( $posts ) || array() === $posts ) {
			return $vacio;
		}
		$por_id = array();
		foreach ( $posts as $post ) {
			$por_id[ (int) $post->ID ] = $post;
		}
		$claves = self::CENSUS;
		$mirar  = self::can_be_incomplete( $procedure_id );
		if ( $mirar ) {
			$claves[] = ApplicationMetaKeys::PEOPLE;
			$claves[] = ApplicationMetaKeys::FILES;
		}
		$stored = self::stored( $procedure_id, $claves );
		foreach ( array_keys( $por_id ) as $id ) {
			$stored[ $id ] = ( $stored[ $id ] ?? array() ) + array_fill_keys( $claves, '' );
		}
		if ( array_key_exists( 'codes', $scope ) ) {
			$codigos = array_flip( array_map( 'strval', (array) $scope['codes'] ) );
			foreach ( $stored as $id => $meta ) {
				if ( ! isset( $codigos[ (string) $meta[ ApplicationMetaKeys::CENTRE_CODE ] ] ) ) {
					unset( $stored[ $id ], $por_id[ $id ] );
				}
			}
		}
		$pendientes = self::pending( $por_id, $stored );
		return array(
			'posts'   => $por_id,
			'stored'  => array_intersect_key( $stored, $por_id ),


			'check'   => $mirar || array() !== $pendientes,
			'pending' => $pendientes,
		);
	}








	private static function pending( array $posts, array $stored ): array {
		$out = array();
		foreach ( $posts as $id => $post ) {
			$creada_por = (int) ( $stored[ $id ][ ApplicationMetaKeys::CREATED_BY ] ?? 0 );
			if ( $creada_por > 0 && (int) $post->post_author === $creada_por ) {
				$out[ $id ] = true;
			}
		}
		return $out;
	}












	private static function stored( int $procedure_id, array $keys ): array {
		global $wpdb;
		$huecos = implode( ',', array_fill( 0, count( $keys ), '%s' ) );

		$filas = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT m.post_id, m.meta_key, m.meta_value FROM {$wpdb->postmeta} m INNER JOIN {$wpdb->posts} p ON p.ID = m.post_id WHERE p.post_type = %s AND p.post_parent = %d AND p.post_status = 'publish' AND m.meta_key IN ({$huecos}) ORDER BY m.meta_id ASC",
				array_merge( array( ApplicationPostType::POST_TYPE, $procedure_id ), $keys )
			)
		);

		$out = array();
		foreach ( (array) $filas as $fila ) {
			$id = (int) $fila->post_id;
			if ( ! isset( $out[ $id ][ $fila->meta_key ] ) ) {
				$out[ $id ][ (string) $fila->meta_key ] = maybe_unserialize( $fila->meta_value );
			}
		}
		return $out;
	}







	private static function can_be_incomplete( int $procedure_id ): bool {
		return ApplicationCompleteness::may_be_incomplete( self::people_spec( $procedure_id ), Applications::questions( $procedure_id ), ProcedureState::today() );
	}








	private static function people_spec( int $procedure_id ): array {
		return People::enabled( $procedure_id ) ? People::spec( $procedure_id ) : array( 'enabled' => false );
	}









	private static function counts( array $stored ): array {
		$out = array_fill_keys( array_keys( ApplicationMetaKeys::review_states() ), 0 );
		foreach ( $stored as $meta ) {
			++$out[ self::state_of( $meta[ ApplicationMetaKeys::REVIEW_STATE ] ) ];
		}
		return $out;
	}








	private static function options( array $stored, array $catalog ): array {
		$islas = array();
		$zonas = array();
		foreach ( $stored as $meta ) {
			$centro = CentreColumns::attributes( (string) $meta[ ApplicationMetaKeys::CENTRE_CODE ], $catalog );
			if ( '' !== $centro['island'] ) {
				$islas[ $centro['island'] ] = $centro['island'];
			}
			if ( '' !== $centro['zone'] ) {
				$zonas[ $centro['zone'] ] = '' !== $centro['zone_name'] ? $centro['zone_name'] : $centro['zone'];
			}
		}
		asort( $islas, SORT_NATURAL | SORT_FLAG_CASE );
		asort( $zonas, SORT_NATURAL | SORT_FLAG_CASE );
		return array(
			'islands' => $islas,
			'zones'   => $zonas,
		);
	}









	private static function reasons( int $procedure_id, array $stored, array $pending = array() ): array {
		$spec      = self::people_spec( $procedure_id );
		$preguntas = Applications::questions( $procedure_id );
		$hoy       = ProcedureState::today();
		$out       = array();
		foreach ( $stored as $id => $meta ) {
			$motivos = ApplicationCompleteness::reasons( $spec, $meta[ ApplicationMetaKeys::PEOPLE ] ?? '', $preguntas, $meta[ ApplicationMetaKeys::FILES ] ?? '', $hoy, self::state_of( $meta[ ApplicationMetaKeys::REVIEW_STATE ] ), isset( $pending[ $id ] ) );
			if ( array() !== $motivos ) {
				$out[ $id ] = $motivos;
			}
		}
		return $out;
	}










	private static function narrow( array $census, array $filter, array $catalog, array $incomplete ): array {
		if ( '' !== $filter['search'] ) {

			cache_users( array_unique( array_map( 'intval', wp_list_pluck( $census['posts'], 'post_author' ) ) ) );
		}
		$out = array();
		foreach ( $census['stored'] as $id => $meta ) {
			$centro = CentreColumns::attributes( (string) $meta[ ApplicationMetaKeys::CENTRE_CODE ], $catalog );
			if ( ( '' !== $filter['review'] && self::state_of( $meta[ ApplicationMetaKeys::REVIEW_STATE ] ) !== $filter['review'] )
				|| ( '' !== $filter['island'] && $centro['island'] !== $filter['island'] )
				|| ( '' !== $filter['zone'] && $centro['zone'] !== $filter['zone'] )
				|| ( true === $filter['incomplete'] && ! isset( $incomplete[ $id ] ) )
				|| ! ApplicationFilter::matches( (string) $filter['search'], self::searchable( $census['posts'][ $id ], $meta ) ) ) {
				continue;
			}
			$out[] = (int) $id;
		}
		return $out;
	}









	private static function searchable( \WP_Post $post, array $meta ): array {
		$textos = array( (string) $meta[ ApplicationMetaKeys::CENTRE_NAME ], (string) $meta[ ApplicationMetaKeys::CENTRE_CODE ] );
		if ( ApplicationMetaKeys::TYPE_TEACHER === $meta[ ApplicationMetaKeys::APPLICANT_TYPE ] ) {
			$cuenta = get_userdata( (int) $post->post_author );
			if ( false !== $cuenta ) {
				$textos[] = (string) $cuenta->display_name;
			}
		}
		return $textos;
	}












	private static function page( array $ids ): array {
		if ( array() === $ids ) {
			return array();
		}
		update_meta_cache( 'post', $ids );
		$posts = array_values( array_filter( array_map( 'get_post', $ids ) ) );
		cache_users( array_unique( array_map( 'intval', wp_list_pluck( $posts, 'post_author' ) ) ) );
		return $posts;
	}
}

}









namespace Prc\PublicFront;

use Prc\Domain\PeopleSpec;
use Prc\Meta\ProcedureMetaKeys;















if ( ! class_exists( 'Prc\PublicFront\PeopleSpecPanel', false ) ) {
final class PeopleSpecPanel {

	public const FIELD_ENABLED  = 'prc_people_enabled';
	public const FIELD_ROLES    = 'prc_people_roles';
	public const FIELD_GROUPS   = 'prc_people_groups';
	public const FIELD_MIN      = 'prc_people_min';
	public const FIELD_MAX      = 'prc_people_max';
	public const FIELD_UNIQUE   = 'prc_people_unique';
	public const FIELD_OPENS    = 'prc_people_opens_at';
	public const FIELD_CLOSES   = 'prc_people_closes_at';
	public const FIELD_ADMITTED = 'prc_people_admitted_only';


	public const FIELD_MULTI_ROLE = 'prc_people_multi_role';


	public const FIELD_ROLE_GROUP = 'prc_people_role_group';




	public const ARG_HISTORY = 'historial';






	private const FIELDS = array(
		'enabled'          => self::FIELD_ENABLED,
		'roles'            => self::FIELD_ROLES,
		'groups'           => self::FIELD_GROUPS,
		'min'              => self::FIELD_MIN,
		'max'              => self::FIELD_MAX,
		'unique_per_group' => self::FIELD_UNIQUE,
		'multi_role'       => self::FIELD_MULTI_ROLE,
		'opens_at'         => self::FIELD_OPENS,
		'closes_at'        => self::FIELD_CLOSES,
		'admitted_only'    => self::FIELD_ADMITTED,
	);







	public static function save( int $procedure_id ): string {
		if ( ProcedureMetaKeys::AUDIENCE_TEACHERS === get_post_meta( $procedure_id, ProcedureMetaKeys::AUDIENCE, true ) ) {
			return 'Los procedimientos para profesorado no tienen lista de personas: cada solicitud es de una sola persona.';
		}
		$antes = People::spec( $procedure_id );
		$res   = PeopleSpec::from_input( self::typed(), $antes );
		if ( '' !== $res['error'] ) {
			return $res['error'] . ' No se ha guardado nada.';
		}
		$porque = PeopleSpec::refusal( $antes, $res['spec'], People::used_keys( $procedure_id ) );
		if ( '' !== $porque ) {
			return $porque;
		}
		$coordina = Applications::coordinator_count( $procedure_id );
		$porque   = PeopleSpec::coordination_clash( $antes, $res['spec'], $coordina, $coordina );
		if ( '' !== $porque ) {
			return $porque;
		}
		update_post_meta( $procedure_id, ProcedureMetaKeys::PEOPLE_SPEC, wp_slash( $res['spec'] ) );
		return '';
	}










	private static function typed(): array {
		$out = array();
		foreach ( self::FIELDS as $clave => $campo ) {

			$valor         = isset( $_POST[ $campo ] ) && is_string( $_POST[ $campo ] ) ? wp_unslash( $_POST[ $campo ] ) : '';
			$out[ $clave ] = in_array( $clave, array( 'roles', 'groups' ), true ) ? sanitize_textarea_field( $valor ) : sanitize_text_field( $valor );
		}

		if ( isset( $_POST[ self::FIELD_ROLES ] ) && is_array( $_POST[ self::FIELD_ROLES ] ) ) {
			$filas  = wp_unslash( $_POST[ self::FIELD_ROLES ] );
			$grupos = (array) wp_unslash( $_POST[ self::FIELD_ROLE_GROUP ] ?? array() );
			$lineas = array();
			foreach ( $filas as $i => $rotulo ) {
				$rotulo   = is_string( $rotulo ) ? sanitize_text_field( $rotulo ) : '';
				$lineas[] = '' !== $rotulo && ! empty( $grupos[ $i ] ) ? $rotulo . ' | ' . PeopleSpec::GROUP_MARK : $rotulo;
			}
			$out['roles'] = implode( "\n", $lineas );
		}

		return $out;
	}









	public static function model( int $procedure_id, bool $rejected ): array {
		$spec    = People::spec( $procedure_id );
		$valores = array(
			'enabled'          => $spec['enabled'] ? '1' : '',
			'roles'            => PeopleSpec::roles_text( $spec ),
			'groups'           => PeopleSpec::groups_text( $spec ),
			'min'              => (string) $spec['min'],
			'max'              => (string) $spec['max'],


			'unique_per_group' => $spec['unique_per_group'] || ! metadata_exists( 'post', $procedure_id, ProcedureMetaKeys::PEOPLE_SPEC ) ? '1' : '',
			'multi_role'       => $spec['multi_role'] ? '1' : '',
			'opens_at'         => (string) $spec['opens_at'],
			'closes_at'        => (string) $spec['closes_at'],
			'admitted_only'    => $spec['admitted_only'] ? '1' : '',
		);


		$de_aqui = $rejected && isset( $_POST[ self::FIELD_ROLES ] );
		$valores = $de_aqui ? self::typed() : $valores;
		return array(
			'teachers'   => ProcedureMetaKeys::AUDIENCE_TEACHERS === get_post_meta( $procedure_id, ProcedureMetaKeys::AUDIENCE, true ),
			'values'     => $valores,
			'role_rows'  => PeopleSpec::role_lines( (string) $valores['roles'] ),
			'max_people' => PeopleSpec::MAX_PEOPLE,
			'max_roles'  => PeopleSpec::MAX_ROLES,
			'max_groups' => PeopleSpec::MAX_GROUPS,
		);
	}











	public static function reviews( array $applications, array $args = array() ): array {

		$pedido = absint( wp_unslash( $_GET[ self::ARG_HISTORY ] ?? 0 ) );
		$out    = array();
		foreach ( $applications as $solicitud ) {
			$out[ (int) $solicitud->ID ] = PeopleForm::review( (int) $solicitud->ID, (int) $solicitud->ID === $pedido, $args );
		}
		return $out;
	}
}

}









namespace Prc\PublicFront;

use Prc\Domain\ApplicationFilter;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ApplicationPostType;













if ( ! class_exists( 'Prc\PublicFront\ApplicationsPanel', false ) ) {
final class ApplicationsPanel {


	public const FIELD_ROWS = 'prc_rows';


	public const FIELD_PAGE = 'prc_rows_page';


	public const FIELD_PAGE_ROWS = 'prc_page_rows';


	public const FIELD_EXPORT_SCOPE = 'prc_export_scope';

	public const SCOPE_FILTER = 'filter';
	public const SCOPE_ALL    = 'all';







	public const BULK_MAX = 50;


	public const NOTICE_BULK = 'bloque';
	public const ARG_DONE    = 'revisadas';
	public const ARG_FAILED  = 'sin_cambiar';







	public static function filter( array $source ): array {
		$raw = array();
		foreach ( ApplicationFilter::args() as $arg ) {
			if ( isset( $source[ $arg ] ) && is_string( $source[ $arg ] ) ) {
				$raw[ $arg ] = sanitize_text_field( wp_unslash( $source[ $arg ] ) );
			}
		}
		return ApplicationFilter::sanitize( $raw );
	}













	public static function url( int $procedure_id, array $filter, array $extra = array(), array $changes = array() ): string {
		$args = array_map( 'rawurlencode', ApplicationFilter::query( $filter, $changes ) );
		return ProcedureEditor::url( $procedure_id, ProcedureEditor::PANEL_APPLICATIONS, $args + $extra );
	}












	public static function model( int $procedure_id, int $user_id, array $questions, array $request ): array {
		$res   = ApplicationQuery::run( $procedure_id, self::filter( $request ) );
		$filas = Applications::rows( $procedure_id, $res['posts'] );
		foreach ( $filas as $i => $fila ) {
			$filas[ $i ]['reasons'] = $res['reasons'][ (int) $fila['id'] ] ?? array();
		}
		$args = array_map( 'rawurlencode', ApplicationFilter::query( $res['filter'] ) );
		return array(
			'app_cols'     => Applications::columns( $questions ),
			'applications' => $filas,
			'export'       => self::export_model( $procedure_id, $user_id, $res ),
			'people'       => PeopleSpecPanel::reviews( $res['posts'], $args ),
			'table'        => self::table( $procedure_id, $res ),


			'actions'      => ApplicationActionsPanel::model( $procedure_id, $user_id, $request, $res['posts'], $args ),
		);
	}








	private static function table( int $procedure_id, array $res ): array {
		$filtro  = $res['filter'];
		$estados = array(
			array(
				'key'     => '',
				'label'   => 'Todas',
				'count'   => (int) $res['all'],
				'url'     => self::url( $procedure_id, ApplicationFilter::sanitize( array() ) ),
				'current' => '' === $filtro['review'],
			),
		);
		foreach ( ApplicationMetaKeys::review_states() as $clave => $rotulo ) {
			$estados[] = array(
				'key'     => $clave,
				'label'   => $rotulo,
				'count'   => (int) ( $res['counts'][ $clave ] ?? 0 ),

				'url'     => self::url( $procedure_id, ApplicationFilter::sanitize( array() ), array(), array( 'review' => $clave ) ),
				'current' => $clave === $filtro['review'],
			);
		}
		$base = ProcedureEditor::url( $procedure_id, ProcedureEditor::PANEL_APPLICATIONS );
		return array(
			'filter'     => $filtro,
			'active'     => $res['active'],
			'args'       => ApplicationFilter::query( $filtro ),
			'all'        => (int) $res['all'],
			'total'      => (int) $res['total'],
			'page'       => (int) $res['page'],
			'pages'      => (int) $res['pages'],
			'from'       => (int) $res['from'],
			'to'         => (int) $res['to'],
			'states'     => $estados,
			'incomplete' => array(
				'possible' => (bool) $res['may_be_incomplete'],
				'count'    => (int) $res['incomplete'],
			),
			'options'    => $res['options'],
			'zone_label' => CentreColumns::zone_label(),
			'teachers'   => ProcedureMetaKeys::AUDIENCE_TEACHERS === get_post_meta( $procedure_id, ProcedureMetaKeys::AUDIENCE, true ),
			'pager'      => self::pager(
				static function ( array $changes ) use ( $procedure_id, $filtro ): string {
					return self::url( $procedure_id, $filtro, array(), $changes );
				},
				$filtro,
				(int) $res['pages']
			),
			'action'     => strtok( $base, '?' ),
			'hidden'     => wp_parse_args( (string) wp_parse_url( $base, PHP_URL_QUERY ) ),
			'reset_url'  => $res['active'] ? $base : '',
			'page_ids'   => array_map( 'intval', wp_list_pluck( $res['posts'], 'ID' ) ),
			'bulk_max'   => self::BULK_MAX,
		);
	}












	public static function pager( callable $url, array $filter, int $pages ): array {
		$actual = (int) $filter['page'];
		$out    = array(
			'prev'  => $actual > 1 ? (string) $url( array( 'page' => $actual - 1 ) ) : '',
			'next'  => $actual < $pages ? (string) $url( array( 'page' => $actual + 1 ) ) : '',
			'pages' => array(),
		);
		$antes  = 0;
		for ( $n = 1; $n <= $pages; $n++ ) {
			if ( 1 !== $n && $pages !== $n && abs( $n - $actual ) > 2 ) {
				continue;
			}
			if ( $n - $antes > 1 ) {
				$out['pages'][] = array(
					'n'   => 0,
					'url' => '',
				);
			}
			$out['pages'][] = array(
				'n'   => $n,
				'url' => (string) $url( array( 'page' => $n ) ),
			);
			$antes          = $n;
		}
		return $out;
	}

















	private static function export_model( int $procedure_id, int $user_id, array $res ): array {
		$todas = array_values( array_filter( array_map( 'get_post', array_keys( $res['stored'] ) ) ) );
		cache_users( array_unique( array_map( 'intval', wp_list_pluck( $todas, 'post_author' ) ) ) );
		$fuentes = ApplicationExport::who( $procedure_id );
		$quienes = array();
		foreach ( ApplicationExport::who_counts( $todas, array_keys( $fuentes ), $res['stored'] ) as $clave => $cuenta ) {
			$quienes[ $clave ] = array( 'label' => $fuentes[ $clave ] ) + $cuenta;
		}
		if ( $res['active'] ) {
			$del_filtro = array_values( array_filter( array_map( 'get_post', $res['ids'] ) ) );
			foreach ( ApplicationExport::who_counts( $del_filtro, array_keys( $fuentes ), $res['stored'] ) as $clave => $cuenta ) {
				$quienes[ $clave ]['filter'] = $cuenta;
			}
		}
		return array(
			'groups'       => ApplicationExport::groups( $procedure_id ),
			'group_labels' => ApplicationExport::group_labels( $procedure_id ),
			'choice'       => ApplicationExport::choice( $user_id, $procedure_id ),
			'who'          => $quienes,
			'filtered'     => $res['active'],
			'count'        => $res['active'] ? count( $res['ids'] ) : (int) $res['all'],
			'all'          => (int) $res['all'],
			'args'         => ApplicationFilter::query( array( 'page' => 1 ) + $res['filter'] ),
		);
	}











	public static function export_ids( int $procedure_id, array $post ): ?array {
		$filtro = self::filter( $post );
		$ambito = isset( $post[ self::FIELD_EXPORT_SCOPE ] ) && is_string( $post[ self::FIELD_EXPORT_SCOPE ] ) ? sanitize_key( wp_unslash( $post[ self::FIELD_EXPORT_SCOPE ] ) ) : self::SCOPE_FILTER;
		if ( self::SCOPE_ALL === $ambito || ! ApplicationFilter::active( $filtro ) ) {
			return null;
		}
		return ApplicationQuery::ids( $procedure_id, $filtro );
	}










	public static function posted_ids( array $post ): array {
		$marcadas = isset( $post[ self::FIELD_ROWS ] ) && is_array( $post[ self::FIELD_ROWS ] ) ? $post[ self::FIELD_ROWS ] : array();
		if ( ! empty( $post[ self::FIELD_PAGE ] ) && isset( $post[ self::FIELD_PAGE_ROWS ] ) && is_string( $post[ self::FIELD_PAGE_ROWS ] ) ) {
			$marcadas = array_merge( $marcadas, explode( ',', wp_unslash( $post[ self::FIELD_PAGE_ROWS ] ) ) );
		}
		$ids = array();
		foreach ( $marcadas as $id ) {

			$id = is_scalar( $id ) && ctype_digit( trim( (string) $id ) ) ? (int) $id : 0;
			if ( $id > 0 ) {
				$ids[ $id ] = $id;
			}
		}
		return array_values( $ids );
	}



















	public static function bulk( int $procedure_id, array $ids, string $state, string $note ): array {
		$out = array(
			'error'  => '',
			'done'   => 0,
			'failed' => 0,
		);
		if ( array() === $ids ) {
			$out['error'] = 'Marque al menos una solicitud para cambiar su revisión.';
			return $out;
		}
		if ( count( $ids ) > self::BULK_MAX ) {
			$out['error'] = sprintf( 'Como mucho se cambian %1$d solicitudes a la vez, y ha marcado %2$d. No se ha cambiado ninguna.', self::BULK_MAX, count( $ids ) );
			return $out;
		}
		if ( ! isset( ApplicationMetaKeys::review_states()[ $state ] ) ) {
			$out['error'] = 'Ese estado de revisión no existe. No se ha cambiado ninguna solicitud.';
			return $out;
		}
		if ( '' === trim( $note ) && in_array( $state, ApplicationMetaKeys::states_needing_note(), true ) ) {
			$out['error'] = 'Para pedir subsanar o para excluir hay que escribir una nota: cada centro tiene que leer qué se le pide o por qué. No se ha cambiado ninguna solicitud.';
			return $out;
		}

		$nota = '' === trim( $note ) ? null : $note;
		foreach ( $ids as $id ) {
			$suya = ApplicationPostType::POST_TYPE === get_post_type( $id ) && 'publish' === get_post_status( $id ) && (int) wp_get_post_parent_id( $id ) === $procedure_id;
			if ( $suya && Applications::review( $id, $state, $nota )['ok'] ) {
				++$out['done'];
				continue;
			}
			++$out['failed'];
		}
		return $out;
	}







	public static function bulk_notice( array $request ): array {
		$hechas = isset( $request[ self::ARG_DONE ] ) && is_string( $request[ self::ARG_DONE ] ) ? absint( $request[ self::ARG_DONE ] ) : 0;
		$fallos = isset( $request[ self::ARG_FAILED ] ) && is_string( $request[ self::ARG_FAILED ] ) ? absint( $request[ self::ARG_FAILED ] ) : 0;
		$texto  = 1 === $hechas ? 'Se ha cambiado la revisión de 1 solicitud.' : sprintf( 'Se ha cambiado la revisión de %d solicitudes.', $hechas );
		if ( $fallos > 0 ) {
			$texto .= ' ' . ( 1 === $fallos
				? '1 no se ha podido cambiar (no es de este procedimiento o ya no está publicada).'
				: sprintf( '%d no se han podido cambiar (no son de este procedimiento o ya no están publicadas).', $fallos ) );
		}
		return array(
			'tipo'  => $fallos > 0 ? 'aviso' : 'ok',
			'texto' => $texto,
		);
	}
}

}










namespace Prc\PublicFront;

use Prc\Access\ApplicationActions;
use Prc\Domain\CentreCatalog;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;





















if ( ! class_exists( 'Prc\PublicFront\ApplicationActionsPanel', false ) ) {
final class ApplicationActionsPanel {

	public const OP_RETIRE     = 'retire_application';
	public const OP_RESTORE    = 'restore_application';
	public const OP_EXTEND     = 'extend_application';
	public const OP_SUBMIT_FOR = 'submit_for_centre';


	public const OPS = array( self::OP_RETIRE, self::OP_RESTORE, self::OP_EXTEND, self::OP_SUBMIT_FOR );


	public const ROW_OPS = array( self::OP_RETIRE, self::OP_RESTORE, self::OP_EXTEND );


	public const FIELD_REASON = 'prc_retire_reason';


	public const FIELD_LATE = 'prc_late_until';


	public const FIELD_LATE_REMOVE = 'prc_late_remove';


	public const FIELD_CODE = 'prc_submit_code';


	public const FIELD_OVERRIDE = 'prc_submit_override';


	public const FIELD_INTERNAL_NOTE = 'prc_internal_note';


	public const ARG_WITHDRAWN = 'retiradas';


	public const ARG_FIND = 'centro_buscar';


	public const ARG_PICK = 'centro_elegido';


	public const NOTICES = array(
		'retirada'         => 'Solicitud retirada: está en «Retiradas» y se puede restaurar desde allí mientras el centro no presente otra.',
		'restaurada'       => 'Solicitud restaurada: vuelve a la tabla como estaba.',
		'prorroga'         => 'Prórroga guardada: el centro puede editar esa solicitud entera hasta ese día, incluido.',
		'prorroga-quitada' => 'Prórroga quitada: la solicitud vuelve a seguir el plazo general.',
		'en-nombre'        => 'Solicitud presentada en nombre del centro, vacía y en «Presentada». El centro la ve en «Solicitudes de mi centro» y la completa con su formulario hasta el último día de la prórroga. El aplicativo no le avisa por correo: el catálogo no trae su dirección.',
	);




	private const ORIGINS = array(
		ApplicationOperations::BY_APPLICANT => 'Quien la presentó',
		ApplicationOperations::BY_REVIEWER  => 'Quien revisa',
		ApplicationOperations::BY_DESK      => 'Desde el escritorio',
	);






	private static $posted = array();












	public static function handle( string $op, int $procedure_id, int $user_id, int $row_id ): string {
		if ( self::OP_SUBMIT_FOR === $op ) {
			return self::submit_for( $procedure_id, $user_id );
		}
		if ( (int) wp_get_post_parent_id( $row_id ) !== $procedure_id ) {
			return 'Esa solicitud no es de este procedimiento.';
		}
		$ops = array(
			self::OP_RETIRE  => 'retire',
			self::OP_RESTORE => 'restore',
			self::OP_EXTEND  => 'extend',
		);
		return isset( $ops[ $op ] ) ? (string) call_user_func( array( self::class, $ops[ $op ] ), $procedure_id, $user_id, $row_id ) : '';
	}









	private static function retire( int $procedure_id, int $user_id, int $row_id ): string {
		$porque = ApplicationActions::why_not_retire( $user_id, $row_id );
		if ( '' !== $porque || ! current_user_can( 'delete_post', $row_id ) ) {
			return '' !== $porque ? $porque : 'Su perfil no puede retirar esta solicitud.';
		}
		$motivo = self::textarea( self::FIELD_REASON );
		if ( '' === $motivo ) {
			return 'Para retirar una solicitud hay que escribir el motivo: queda en su historial.';
		}
		if ( ! ApplicationOperations::withdraw( $row_id, $user_id, ApplicationOperations::BY_REVIEWER, $motivo ) ) {
			return 'No se ha podido retirar la solicitud. Vuelva a intentarlo.';
		}
		self::back( $procedure_id, 'retirada' );
		return '';
	}









	private static function restore( int $procedure_id, int $user_id, int $row_id ): string {
		if ( ! current_user_can( ApplicationActions::CAP_RESTORE, $row_id ) ) {
			return ApplicationActions::why_not_restore( $user_id, $row_id );
		}
		if ( ! ApplicationOperations::restore( $row_id ) ) {
			$porque = ApplicationActions::why_not_restore( $user_id, $row_id );
			return '' !== $porque ? $porque : 'No se ha podido restaurar la solicitud. Vuelva a intentarlo.';
		}
		self::back( $procedure_id, 'restaurada' );
		return '';
	}









	private static function extend( int $procedure_id, int $user_id, int $row_id ): string {
		if ( ! current_user_can( ApplicationActions::CAP_EXTEND, $row_id ) ) {
			return ApplicationActions::why_not_extend( $user_id, $row_id );
		}
		$quitar = '1' === self::field( self::FIELD_LATE_REMOVE );
		$hasta  = $quitar ? '' : self::field( self::FIELD_LATE );
		$porque = $quitar ? '' : ApplicationActions::why_not_late_date( $hasta, ProcedureState::today() );
		if ( '' !== $porque ) {
			return $porque;
		}
		ApplicationOperations::extend( $row_id, $user_id, $hasta );
		self::back( $procedure_id, $quitar ? 'prorroga-quitada' : 'prorroga' );
		return '';
	}








	private static function submit_for( int $procedure_id, int $user_id ): string {
		self::$posted = array(
			'code'     => self::field( self::FIELD_CODE ),
			'late'     => self::field( self::FIELD_LATE ),
			'override' => self::field( self::FIELD_OVERRIDE ),
		);
		if ( ! current_user_can( ApplicationActions::CAP_SUBMIT_FOR, $procedure_id ) ) {
			return ApplicationActions::why_not_submit_for( $user_id, $procedure_id );
		}
		if ( '' === self::$posted['code'] ) {
			return 'Escriba el código del centro en cuyo nombre presenta la solicitud.';
		}
		$fuera  = '';
		$porque = ApplicationActions::why_not_submit_for( $user_id, $procedure_id, self::$posted['code'], '1' === self::$posted['override'], $fuera );
		if ( '' === $porque ) {
			$porque = ApplicationActions::why_not_late_date( self::$posted['late'], ProcedureState::today() );
		}
		if ( '' !== $porque ) {
			return $porque;
		}
		if ( ApplicationOperations::submit_for( $procedure_id, $user_id, self::$posted['code'], self::$posted['late'], $fuera ) <= 0 ) {
			return ApplicationOperations::why_not_saved( 'No se ha podido crear la solicitud. Vuelva a intentarlo.' );
		}
		$codigo = (string) ( ApplicationActions::catalogue_centre( self::$posted['code'] )['code'] ?? '' );
		Shell::leave( ApplicationsPanel::url( $procedure_id, ApplicationsPanel::filter( array() ), array( ProcedureEditor::ARG_NOTICE => 'en-nombre' ), array( 'search' => $codigo ) ) );
		return '';
	}











	public static function save_internal_note( int $row_id, int $user_id ): void {

		if ( isset( $_POST[ self::FIELD_INTERNAL_NOTE ] ) && is_string( $_POST[ self::FIELD_INTERNAL_NOTE ] ) ) {
			ApplicationOperations::set_internal_note( $row_id, $user_id, self::textarea( self::FIELD_INTERNAL_NOTE ) );
		}
	}








	private static function back( int $procedure_id, string $notice ): void {
		$extra = array( ProcedureEditor::ARG_NOTICE => $notice );

		if ( ! empty( $_POST[ self::ARG_WITHDRAWN ] ) ) {
			$extra[ self::ARG_WITHDRAWN ] = 1;
		}

		Shell::leave( ApplicationsPanel::url( $procedure_id, ApplicationsPanel::filter( $_POST ), $extra ) );
	}







	private static function field( string $nombre ): string {

		return isset( $_POST[ $nombre ] ) && is_string( $_POST[ $nombre ] ) ? trim( sanitize_text_field( wp_unslash( $_POST[ $nombre ] ) ) ) : '';
	}







	private static function textarea( string $nombre ): string {

		return isset( $_POST[ $nombre ] ) && is_string( $_POST[ $nombre ] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST[ $nombre ] ) ) ) : '';
	}
















	public static function model( int $procedure_id, int $user_id, array $request, array $posts, array $args = array() ): array {
		$abiertas = self::get( $request, PeopleSpecPanel::ARG_HISTORY );
		$filas    = array();
		foreach ( $posts as $post ) {
			$id           = (int) $post->ID;
			$hasta        = ApplicationActions::late_until( $id );
			$filas[ $id ] = array(
				'late_until'    => $hasta,
				'late_label'    => ApplicationActions::day( $hasta ),
				'late_open'     => ApplicationActions::is_late_open( $id ),
				'can_extend'    => '' === ApplicationActions::why_not_extend( $user_id, $id ),
				'internal_note' => (string) get_post_meta( $id, ApplicationMetaKeys::INTERNAL_NOTE, true ),
				'pending'       => ApplicationOperations::pending_for_school( $id ),
				'review'        => (string) get_post_meta( $id, ApplicationMetaKeys::REVIEW_STATE, true ),
				'history'       => array(
					'open'    => (string) $id === $abiertas,
					'url'     => ProcedureEditor::url( $procedure_id, ProcedureEditor::PANEL_APPLICATIONS, array( PeopleSpecPanel::ARG_HISTORY => $id ) + $args ) . '#prc-historial-' . $id,
					'entries' => (string) $id === $abiertas ? ApplicationHistory::entries( $id ) : array(),
				),
			);
		}
		$abrir = '1' === self::get( $request, self::ARG_WITHDRAWN );
		return array(
			'rows'            => $filas,
			'today'           => ProcedureState::today(),
			'resolved'        => ProcedureMetaKeys::STATE_RESOLVED === ProcedureState::of_post( $procedure_id ),
			'withdrawn_count' => ApplicationOperations::withdrawn_count( $procedure_id ),
			'withdrawn_open'  => $abrir,
			'withdrawn_url'   => ProcedureEditor::url( $procedure_id, ProcedureEditor::PANEL_APPLICATIONS, array( self::ARG_WITHDRAWN => 1 ) ),
			'back_url'        => ProcedureEditor::url( $procedure_id, ProcedureEditor::PANEL_APPLICATIONS ),
			'withdrawn'       => $abrir ? self::withdrawn( $procedure_id, $user_id ) : array(),
			'submit_for'      => self::submit_for_model( $procedure_id, $user_id, $request ),
		);
	}








	private static function withdrawn( int $procedure_id, int $user_id ): array {
		$out   = array();
		$posts = ApplicationOperations::withdrawn( $procedure_id );
		cache_users( array_unique( array_map( 'intval', wp_list_pluck( $posts, 'post_author' ) ) ) );
		foreach ( $posts as $post ) {
			$id     = (int) $post->ID;
			$ultima = array();
			foreach ( ApplicationLog::all( $id ) as $entrada ) {
				$ultima = ApplicationHistory::WITHDRAWN === $entrada['event'] ? $entrada : $ultima;
			}
			$autor  = get_userdata( (int) $post->post_author );
			$nombre = (string) get_post_meta( $id, ApplicationMetaKeys::CENTRE_NAME, true );
			$codigo = (string) get_post_meta( $id, ApplicationMetaKeys::CENTRE_CODE, true );
			$porque = ApplicationActions::why_not_restore( $user_id, $id );
			$out[]  = array(
				'id'      => $id,
				'who'     => '' !== $codigo ? trim( ( '' !== $nombre ? $nombre : '(centro fuera del catálogo)' ) . ' (' . $codigo . ')' ) : ( false !== $autor ? (string) $autor->display_name : '' ),
				'when'    => '' !== (string) ( $ultima['at'] ?? '' ) ? (string) wp_date( 'd-m-Y H:i', (int) strtotime( (string) $ultima['at'] ) ) : (string) get_the_modified_date( 'd-m-Y H:i', $post ),
				'by'      => self::ORIGINS[ (string) ( $ultima['data']['by'] ?? '' ) ] ?? self::ORIGINS[ ApplicationOperations::BY_DESK ],
				'reason'  => (string) ( $ultima['data']['reason'] ?? '' ),
				'can'     => '' === $porque,
				'why'     => $porque,
				'history' => ApplicationHistory::entries( $id ),
			);
		}
		return $out;
	}













	private static function submit_for_model( int $procedure_id, int $user_id, array $request ): array {
		$porque = ApplicationActions::why_not_submit_for( $user_id, $procedure_id );
		$buscar = '' === $porque ? self::get( $request, self::ARG_FIND ) : '';
		return array(
			'can'      => '' === $porque,
			'open'     => array() !== self::$posted || '' !== $buscar || '' !== self::get( $request, self::ARG_PICK ),
			'search'   => $buscar,
			'results'  => '' !== $buscar ? CentreCatalog::search( $buscar ) : array(),
			'code'     => (string) ( self::$posted['code'] ?? self::get( $request, self::ARG_PICK ) ),
			'late'     => (string) ( self::$posted['late'] ?? '' ),
			'override' => '1' === ( self::$posted['override'] ?? '' ),
			'today'    => ProcedureState::today(),
			'action'   => strtok( ProcedureEditor::url( $procedure_id, ProcedureEditor::PANEL_APPLICATIONS ), '?' ),
			'hidden'   => wp_parse_args( (string) wp_parse_url( ProcedureEditor::url( $procedure_id, ProcedureEditor::PANEL_APPLICATIONS ), PHP_URL_QUERY ) ),
			'pick_url' => ProcedureEditor::url( $procedure_id, ProcedureEditor::PANEL_APPLICATIONS ),
		);
	}








	private static function get( array $request, string $clave ): string {
		return isset( $request[ $clave ] ) && is_scalar( $request[ $clave ] ) ? trim( sanitize_text_field( wp_unslash( (string) $request[ $clave ] ) ) ) : '';
	}
}

}








namespace Prc\PublicFront\View;

use Prc\PublicFront\Shell;












if ( ! class_exists( 'Prc\PublicFront\View\HomeView', false ) ) {
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

}








namespace Prc\PublicFront;

use Prc\Access\CentreScope;
use Prc\Domain\DateRange;
use Prc\Domain\ProcedureState;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ProcedurePostType;
use Prc\PublicFront\View\HomeView;
use Prc\Taxonomy\ProcedureTaxonomies;














if ( ! class_exists( 'Prc\PublicFront\Home', false ) ) {
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
		$codes = array_keys( CentreScope::codes_for( get_current_user_id() ) );
		if ( array() === $codes ) {
			return array();
		}

		$suyas = array();
		foreach ( Applications::for_centre( $codes ) as $solicitud ) {
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
			'state_label'  => ProcedureState::label(
				$state,
				(bool) get_post_meta( $id, ProcedureMetaKeys::APPLICATIONS_CLOSED, true )
			),
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

}








namespace Prc\PublicFront;



















if ( ! class_exists( 'Prc\PublicFront\HelpContent', false ) ) {
final class HelpContent {




	public const GUIDE_APPLY = 'solicitar';




	public const GUIDE_MANAGE = 'gestionar';




	public const GUIDE_FAQ = 'preguntas';






	public const GUIDES = array(
		self::GUIDE_APPLY  => array(
			'label' => 'Cómo presentar una solicitud',
			'intro' => 'Para la dirección de un centro educativo y para el profesorado. Lea primero «Antes de empezar»: la mayoría de los problemas empiezan ahí.',
		),
		self::GUIDE_MANAGE => array(
			'label' => 'Cómo dar de alta un procedimiento',
			'intro' => 'Para las personas que gestionan los procedimientos de un ámbito convocante.',
		),
		self::GUIDE_FAQ    => array(
			'label' => 'Preguntas frecuentes',
			'intro' => 'Las dudas que más se repiten. Lo que antes se llamaba «convocatoria» aquí se llama «procedimiento».',
		),
	);






	public const ALLOWED = array(
		'p'      => array(),
		'ul'     => array(),
		'ol'     => array(),
		'li'     => array(),
		'strong' => array(),
		'em'     => array(),
		'code'   => array(),
		'a'      => array( 'href' => true ),
		'table'  => array(),
		'thead'  => array(),
		'tbody'  => array(),
		'tr'     => array(),
		'th'     => array( 'scope' => true ),
		'td'     => array(),
	);







	public static function sections( string $guide ): array {
		$destinos = self::targets();
		$out      = array();
		foreach ( self::raw( $guide ) as $apartado ) {
			$html             = (string) preg_replace_callback(
				'/<a href="%([a-z]+)%(#[a-z0-9-]+)?">(.*?)<\/a>/u',
				static function ( array $m ) use ( $destinos ): string {
					$url = $destinos[ $m[1] ] ?? '';
					if ( '' === $url ) {
						return $m[3];
					}
					return '<a href="' . esc_url( $url . ( $m[2] ?? '' ) ) . '">' . $m[3] . '</a>';
				},
				$apartado['html']
			);
			$apartado['html'] = wp_kses( $html, self::ALLOWED );
			$out[]            = $apartado;
		}
		return $out;
	}







	public static function raw( string $guide ): array {
		return self::SECTIONS[ $guide ] ?? self::SECTIONS[ self::GUIDE_APPLY ];
	}






	private static function targets(): array {
		$out = array();
		foreach ( array( 'home', 'mine', 'workspace', 'editor' ) as $seccion ) {
			$out[ $seccion ] = Shell::url( $seccion );
		}
		foreach ( array_keys( self::GUIDES ) as $guia ) {
			$out[ $guia ] = Help::url( $guia );
		}
		return $out;
	}








	private const SECTIONS = array(
		'solicitar' => array(
			array(
				'id'    => 'antes-de-empezar',
				'level' => 2,
				'title' => 'Antes de empezar: entre con su cuenta personal',
				'html'  => '<ul><li>Pulse <strong>«Acceder»</strong>, arriba a la derecha, o <strong>«Entrar y solicitar»</strong> en la ficha del procedimiento. Hasta que no entre, la ficha solo se puede leer.</li><li>Use su <strong>cuenta personal</strong>, la suya y no la cuenta genérica del centro. La solicitud de un centro la presenta su equipo directivo con su propia cuenta.</li><li>Al entrar, arriba a la derecha verá su nombre, su perfil (por ejemplo, «Equipo directivo») y, debajo, <strong>el nombre y el código de su centro</strong>. Compruébelos antes de seguir.</li></ul>'
					. '<p>Si en lugar del centro ve <strong>«Sin centro asignado»</strong>, o al abrir una solicitud le aparece «Su cuenta no tiene centro asignado» o «Su perfil no presenta solicitudes de centro», no podrá solicitar hasta que se corrija su cuenta. Eso lo hace quien administra el aplicativo: vea las <a href="%preguntas%#no-veo-el-boton-solicitar">preguntas frecuentes</a>.</p>',
				'shots' => array(
					array(
						'caption' => 'cabecera con la sesión iniciada: nombre, «Equipo directivo» y el centro con su código',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'encontrar-el-procedimiento',
				'level' => 2,
				'title' => 'Encuentre el procedimiento',
				'html'  => '<ul><li>En <strong><a href="%home%">«Inicio»</a></strong> están los procedimientos publicados, cada uno con su estado: <strong>Próximo</strong> (aún no se puede solicitar), <strong>Abierto</strong>, <strong>En subsanación</strong>, <strong>Cerrado</strong> o <strong>Resuelto</strong>.</li><li>Pulse el título para abrir su <strong>ficha</strong>: arriba, el resumen (estado, quién puede solicitar, curso, plazo y correo de contacto) y la cuenta atrás del cierre; debajo, las pestañas con la información («Objetivos», «Dirigido a», «Calendario»…) y los enlaces a la resolución y a los listados.</li></ul>'
					. '<p>Fíjese en <strong>a quién se dirige</strong>: la fila <strong>«Pueden solicitar»</strong> del resumen y la tarjeta de acceso, que dice si lo solicita la dirección del centro o cada docente:</p>'
					. '<ul><li><strong>Centros educativos</strong>: solicita el equipo directivo, una solicitud por centro.</li><li><strong>Profesorado</strong>: cada docente solicita con su propia cuenta.</li><li>Algunos son solo para <strong>centros públicos</strong> o solo para <strong>centros privados y concertados</strong>, o solo para ciertos tipos de centro, ciertas islas o una lista cerrada de centros. Si su centro no entra, en el sitio del botón verá <strong>«No puede solicitar con esta cuenta»</strong> con el motivo y el correo de contacto del procedimiento.</li></ul>',
				'shots' => array(
					array(
						'caption' => 'portada con las tarjetas de procedimientos y sus estados',
						'image'   => '',
					),
					array(
						'caption' => 'ficha de un procedimiento abierto, con el resumen, la fila «Pueden solicitar», el bloque «Lo solicita la dirección del centro» y el botón para solicitar',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'rellenar-la-solicitud',
				'level' => 2,
				'title' => 'Rellene la solicitud',
				'html'  => '<p>Pulse <strong>«Solicitar»</strong>. Se abre la pantalla de la solicitud con el plazo arriba («Plazo de solicitud: …»).</p>'
					. '<p>Si el procedimiento pide una lista de personas o documentos con plazo propio, arriba verá <strong>los pasos</strong> —«Datos de la solicitud», «Personas que participan», «Documentos con plazo propio»—, cada uno con <strong>«Hecho»</strong> o <strong>«Falta»</strong>. Pulse un paso para ir a su bloque.</p>'
					. '<p>La pantalla tiene, por orden, estos bloques:</p>'
					. '<ol><li><strong>«Solicita en nombre de»</strong>: solo aparece si su cuenta tiene dos códigos (por ejemplo, el del centro y el de un CER) y el procedimiento admite los dos. Elija con cuál solicita: cada código tiene su propia solicitud.</li><li><strong>«Datos del centro educativo»</strong>: ya vienen con los de su cuenta y no se pueden cambiar.</li><li><strong>Sus datos</strong>: nombre y apellidos y, si solicita por un centro, su <strong>cargo en el centro</strong> (Dirección, Vicedirección, Jefatura de estudios, Secretaría u otro). El <strong>correo electrónico</strong> se lee de su cuenta: para cambiarlo, cambie el de su perfil.</li><li><strong>«Solicita»</strong>: si el procedimiento tiene compromisos, lea el texto y marque <strong>«El centro acepta estos compromisos»</strong> (o <strong>«Acepto estos compromisos»</strong> si solicita como docente). Sin esa casilla no se puede presentar.</li><li><strong>Preguntas del procedimiento</strong>: las que haya puesto quien lo gestiona. Las marcadas con asterisco (*) son obligatorias. Pueden ser de texto libre, de una opción, de varias opciones, de sí o no, un número, una fecha, una tabla de cantidades por fila o un archivo. En las de archivo, pulse para elegir el fichero en su equipo: el documento solo se ve en la solicitud, no es público.</li><li><strong>«Datos de la coordinación»</strong>: si el procedimiento la pide, el nombre, los apellidos, el DNI o NIE completo <strong>con la letra</strong> y el correo de cada persona coordinadora. Si el procedimiento tiene <strong>lista de personas</strong> con un papel de coordinación, este bloque no sale: las personas coordinadoras se dan de alta en la lista, después de presentar.</li></ol>'
					. '<p>Pulse <strong>«Presentar la solicitud»</strong>.</p>',
				'shots' => array(
					array(
						'caption' => 'pantalla de solicitud, parte superior: título, estado y plazo',
						'image'   => '',
					),
					array(
						'caption' => 'bloque de preguntas, con una obligatoria y una de archivo',
						'image'   => '',
					),
					array(
						'caption' => 'bloque «Solicita» con la casilla de los compromisos',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'comprobar-la-presentacion',
				'level' => 2,
				'title' => 'Compruebe que ha quedado presentada',
				'html'  => '<ul><li>Si no le falta nada, verá el aviso verde <strong>«Solicitud presentada el … a las …. Está completa: no tiene que hacer nada más.»</strong> Si le falta algo —casi siempre, la lista de personas—, el aviso es amarillo y dice qué falta y hasta cuándo, con un enlace para completarlo.</li><li>En la solicitud, la tarjeta de estado dice <strong>«Presentada el … a las …»</strong> y <strong>«Completa»</strong> o <strong>«Incompleta: …»</strong>.</li><li>Pulse <strong>«Ver e imprimir el justificante»</strong>: es la solicitud en una hoja, con su número, la fecha y la hora y lo presentado. Para guardarlo, use «Imprimir» en el menú del navegador y elija «Guardar como PDF».</li><li>Recibirá un correo de acuse, <strong>«Solicitud presentada n.º …: …»</strong>, en la dirección de su cuenta. Lleva el número de la solicitud, la fecha y la hora y si está completa: guárdelo, es su justificante.</li><li>En <strong><a href="%mine%">«Solicitudes de mi centro»</a></strong> (o <strong>«Mis solicitudes»</strong> si es docente) aparece la fila con el estado de revisión <strong>«Presentada»</strong>.</li></ul>'
					. '<p>Si ve un aviso rojo («No se ha podido guardar la solicitud…», «El formulario estuvo abierto demasiado tiempo…»), <strong>la solicitud no se ha presentado</strong>: vuelva a enviarla.</p>',
				'shots' => array(
					array(
						'caption' => 'aviso verde «Solicitud presentada»',
						'image'   => '',
					),
					array(
						'caption' => '«Solicitudes de mi centro» con la fila de la solicitud en estado «Presentada»',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'personas-participantes',
				'level' => 2,
				'title' => 'No olvide a las personas participantes',
				'html'  => '<p>Cuando el procedimiento pide una <strong>lista de personas</strong> (participantes, coordinación de cada eje o grupo…), esa lista <strong>se rellena después de presentar la solicitud</strong>, en la misma pantalla, en el bloque <strong>«Personas que participan»</strong>, que va antes de los documentos con plazo propio. Hasta entonces verá: «Cuando presente la solicitud podrá dar de alta aquí a las personas que participan».</p>'
					. '<ul><li>Pulse <strong>«Dar de alta a una persona»</strong>, escriba sus datos, elija su <strong>papel</strong> y, si el papel lo pide, su <strong>grupo</strong> (por ejemplo, el eje que coordina).</li><li>Si la lista pide un mínimo y no se llega, la solicitud queda marcada como <strong>incompleta</strong> («Incompleta: faltan … personas»). No se rechaza, pero quien revisa lo verá, y en <a href="%mine%">«Solicitudes de mi centro»</a> la columna «Personas» lo marca.</li><li>Para <strong>sustituir</strong> a alguien, use <strong>«Sustituir o corregir»</strong> en su fila; no dé de alta a otra persona nueva. Para quitarla, <strong>«Dar de baja a …»</strong>.</li><li>Lo primero del bloque, en negrita, es hasta cuándo: <strong>«Puede dar de alta, cambiar o sustituir personas hasta el …, incluido.»</strong> Puede ser distinto del plazo de solicitud.</li></ul>',
				'shots' => array(
					array(
						'caption' => 'bloque «Personas que participan» vacío, con «Dar de alta a una persona»',
						'image'   => '',
					),
					array(
						'caption' => 'lista con dos personas, papel y grupo, y los botones «Sustituir o corregir» y «Dar de baja»',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'modificar-la-solicitud',
				'level' => 2,
				'title' => 'Modifique la solicitud',
				'html'  => '<p>Mientras el plazo esté abierto, entre en <strong><a href="%mine%">«Solicitudes de mi centro»</a> → «Ver la solicitud»</strong>, cambie lo que necesite y pulse <strong>«Guardar los cambios»</strong>. Recibirá un correo de que la solicitud se ha modificado. Cualquier persona del equipo directivo del mismo centro ve y edita la misma solicitud.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'subsanar',
				'level' => 2,
				'title' => 'Subsane si se lo piden',
				'html'  => '<p>Si quien revisa le pide subsanar, la solicitud aparece como <strong>«A subsanar»</strong> con una nota que explica qué falta, y en «Solicitudes de mi centro» un aviso amarillo: «1 solicitud tiene la subsanación abierta hasta el …». Pulse <strong>«Ir a la solicitud»</strong>, corrija y guarde <strong>dentro del plazo de subsanación</strong>. Fuera de ese plazo ya no se puede editar.</p>',
				'shots' => array(
					array(
						'caption' => 'aviso de subsanación en «Solicitudes de mi centro» y la nota de revisión en la fila',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'entregas-con-plazo-propio',
				'level' => 2,
				'title' => 'Entregue los documentos con plazo propio',
				'html'  => '<p>Algunos documentos (una memoria, un proyecto, un anexo firmado) se entregan <strong>después</strong>, en su propio plazo. Aparecen en la solicitud con su apertura y su cierre. Elija el fichero y pulse <strong>«Entregar documento»</strong>. Si ya había entregado uno, el nuevo lo sustituye; la solicitud y su revisión se conservan.</p>',
				'shots' => array(
					array(
						'caption' => 'pregunta de entrega con plazo propio y el botón «Entregar documento»',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'retirar-la-solicitud',
				'level' => 2,
				'title' => 'Retire la solicitud si ya no quiere participar',
				'html'  => '<p>Mientras el plazo siga abierto, pulse <strong>«Retirar la solicitud»</strong>, al final de la solicitud, y confirme. Deja de contar y recibirá un correo de acuse. Mientras haya plazo, puede presentar otra nueva.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'despues-de-presentar',
				'level' => 2,
				'title' => 'Qué verá después',
				'html'  => '<ul><li>En <strong><a href="%mine%">«Solicitudes de mi centro»</a></strong>, la columna <strong>«Revisión»</strong>: Presentada, A subsanar, Admitida o Excluida, con la nota de quien revisa si la hay. Cada cambio le llega también por correo. Si el procedimiento pide lista de personas, la columna <strong>«Personas»</strong> dice cuántas hay, si falta alguna («Completar») y hasta cuándo se cambian.</li><li>En la ficha del procedimiento, los enlaces a los <strong>listados provisional y definitivo</strong> y a la resolución cuando se publiquen. Con el listado definitivo el procedimiento pasa a <strong>«Resuelto»</strong>.</li><li>Si le conceden una <strong>prórroga</strong>, recibirá un correo y verá en la solicitud hasta qué día puede editarla.</li></ul>',
				'shots' => array(),
			),
		),
		'gestionar' => array(
			array(
				'id'    => 'que-necesita',
				'level' => 2,
				'title' => 'Qué necesita',
				'html'  => '<ul><li>Una cuenta con perfil de <strong>gestión de procedimientos</strong> y un <strong>ámbito</strong> asignado. Lo ve arriba a la derecha: «Gestión de procedimientos» y, debajo, su ámbito.</li><li><strong>No necesita pedir permiso para cada procedimiento</strong>: con ese perfil y ese ámbito puede crear los que quiera. Si ve «Su perfil no puede crear procedimientos» o «No tiene ningún ámbito asignado», pídalo a quien administra el aplicativo.</li><li>Solo verá y editará los procedimientos de su ámbito y de los ámbitos que cuelgan de él.</li></ul>',
				'shots' => array(
					array(
						'caption' => 'cabecera con el perfil «Gestión de procedimientos» y el ámbito',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'su-lista',
				'level' => 2,
				'title' => 'Su lista: «Gestión de procedimientos»',
				'html'  => '<p>Menú <strong>«Administrar» → <a href="%workspace%">«Gestionar procedimientos»</a></strong>. Elija el curso arriba y verá una tabla con cada procedimiento, a quién se dirige, su estado, la fecha límite de solicitud y tres columnas de acciones: <strong>Detalles</strong> (abre el taller), <strong>Solicitudes</strong> y <strong>Duplicar</strong>. Al lado, la leyenda de los iconos de estado; el aviso de que <strong>las fechas no cuadran</strong> indica que hay que repasar los plazos.</p>',
				'shots' => array(
					array(
						'caption' => '«Gestión de procedimientos» con la tabla y la leyenda de estados',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'crear-o-duplicar',
				'level' => 2,
				'title' => 'Cree uno nuevo o duplique uno anterior',
				'html'  => '<p><strong>Nuevo</strong>: pulse <strong>«Nuevo procedimiento»</strong> en su lista, o <a href="%editor%">«Administrar» → «Crear procedimiento»</a>. El <strong>Formato Express</strong> pide solo lo mínimo:</p>'
					. '<ul><li><strong>Título del procedimiento</strong>, de 120 caracteres como mucho. El curso no se escribe en el título.</li><li><strong>Quién solicita</strong>: «Centros educativos» o «Profesorado».</li><li><strong>Dirigido a</strong>: «Centros públicos», «Centros privados y concertados» o los dos. Al crear no viene ninguna marcada: hay que elegir. Un centro de otra titularidad no podrá solicitar, y la ficha le dirá por qué. Lo que elija se ve en la ficha, en la fila «Pueden solicitar», y en «Mis procedimientos».</li><li><strong>Modalidad para centros CER</strong>, <strong>Ámbito convocante</strong>, <strong>Correo de contacto</strong> (sale en la ficha) y <strong>Color de cabecera</strong>.</li></ul>'
					. '<p>Pulse <strong>«Crear el procedimiento»</strong>. Nace en <strong>borrador</strong> (nadie lo ve) y se abre su taller. Los correos de contacto del procedimiento y quienes gestionan su ámbito reciben un aviso con lo que falta por completar.</p>'
					. '<p><strong>Duplicar</strong>: el icono «Duplicar» de su lista o el botón <strong>«Duplicar»</strong> del taller. Crea un borrador con los mismos datos, <strong>las mismas preguntas</strong> y la misma configuración, pero <strong>sin plazos, sin curso, sin los enlaces de la resolución y sin solicitudes</strong>. Es la forma de reutilizar el procedimiento del curso pasado.</p>',
				'shots' => array(
					array(
						'caption' => 'pantalla «Crear un procedimiento», Formato Express',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'el-taller',
				'level' => 2,
				'title' => 'El taller, pestaña a pestaña',
				'html'  => '<p>Arriba están el título, el estado (Borrador, Abierto…) y si está publicado; el ojo abre la ficha pública. Debajo, las pestañas.</p>',
				'shots' => array(
					array(
						'caption' => 'cabecera del taller con las pestañas',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'taller-datos',
				'level' => 3,
				'title' => 'Datos',
				'html'  => '<ul><li><strong>Ajustes generales</strong>: título, curso, quién solicita y <strong>Descripción</strong>, que es lo que se lee en la ficha; los apartados «Objetivos», «Dirigido a» o «Calendario» salen de sus encabezados.</li><li><strong>Promociona</strong>: el ámbito convocante. Puede marcar varios; todos editan.</li><li><strong>Más información</strong>: hasta tres correos de contacto.</li><li><strong>Plazos</strong>: inicio y fin del plazo de solicitud, e inicio y fin de la subsanación. <strong>El estado sale de estas fechas</strong>: no hay que «pasarlo a abierto» a mano. Sin fecha de inicio no se puede publicar: se quedaría como «Próximo» y nadie podría solicitar.</li><li><strong>La solicitud</strong>: si pide personas coordinadoras (ninguna, una o varias; para varios papeles o ejes, deje «No se piden» y use la pestaña «Personas») y el texto de los <strong>Compromisos del centro</strong>, que se aceptan con una casilla obligatoria. En blanco, no se pide nada.</li><li><strong>Qué centros pueden solicitar</strong>: tipos de centro, islas o una <strong>lista cerrada</strong> de códigos. Sin nada marcado, puede solicitar cualquier centro.</li></ul>'
					. '<p>Pulse <strong>«Guardar los datos»</strong>.</p>',
				'shots' => array(
					array(
						'caption' => 'pestaña «Datos», bloque de plazos',
						'image'   => '',
					),
					array(
						'caption' => 'pestaña «Datos», bloques de la solicitud y de qué centros pueden solicitar',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'taller-preguntas',
				'level' => 3,
				'title' => 'Preguntas',
				'html'  => '<p>Hasta 40 preguntas propias. Al final hay siempre una <strong>«Pregunta nueva»</strong> vacía y, en <strong>«Añadir otra pregunta»</strong>, dos más: rellene las que necesite y pulse <strong>«Guardar las preguntas»</strong>. Las que deje sin rótulo no se crean. Cada pregunta enseña solo los campos de su tipo.</p>'
					. '<p>Cada pregunta lleva <strong>Rótulo</strong>, <strong>Tipo</strong>, <strong>Indicaciones</strong> (una línea de ayuda) y si es <strong>obligatoria</strong>. Los tipos:</p>'
					. '<table><thead><tr><th scope="col">Tipo</th><th scope="col">Para qué</th><th scope="col">Qué más se rellena</th></tr></thead><tbody><tr><td>Respuesta libre</td><td>Un texto</td><td>Nada más</td></tr><tr><td>Una opción</td><td>Elegir una de una lista</td><td>«Opciones, una por línea»</td></tr><tr><td>Varias opciones</td><td>Marcar varias</td><td>Las opciones; una escrita «Texto &#124; 3» suma 3 puntos</td></tr><tr><td>Sí o no</td><td>Una casilla</td><td>Nada más</td></tr><tr><td>Número</td><td>Una cifra entera</td><td>Mínimo y máximo, si los hay</td></tr><tr><td>Fecha</td><td>Un día</td><td>Nada más</td></tr><tr><td>Cantidades por fila</td><td>Una tabla, por ejemplo el alumnado por nivel</td><td>«Filas, una por línea», hasta 30</td></tr><tr><td>Archivo</td><td>Un documento</td><td>Si quiere, «Entrega con plazo propio» y sus fechas</td></tr></tbody></table>'
					. '<ul><li><strong>«Subir»</strong> y <strong>«Bajar»</strong> cambian el orden. Guarde antes lo que haya escrito.</li><li>Para quitar una pregunta, marque <strong>«Quitar esta pregunta»</strong> y guarde; lo ya contestado no se pierde.</li><li><strong>Con solicitudes presentadas</strong> el tipo ya no se puede cambiar, y bajo el rótulo se dice cuántas la han contestado. El rótulo sí cambia, pero si le cambia el sentido, cambia lo que significan las respuestas ya dadas: si la pregunta es otra, cree una nueva.</li></ul>',
				'shots' => array(
					array(
						'caption' => 'pestaña «Preguntas» con una pregunta de «Una opción» y la «Pregunta nueva»',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'taller-enlaces',
				'level' => 3,
				'title' => 'Enlaces',
				'html'  => '<p><strong>Resolución</strong>, <strong>Listado provisional</strong> y <strong>Listado definitivo</strong>: pegue la <strong>dirección completa</strong> de donde ya están publicados, que empieza por https://; si no, no se guarda. Con el listado definitivo, el procedimiento pasa a <strong>«Resuelto»</strong> y los centros lo ven en su solicitud. Pulse <strong>«Guardar los enlaces»</strong>. Debajo de cada enlace guardado, <strong>«Abrir el enlace guardado ↗»</strong> lo abre en otra pestaña para comprobar que llega entero.</p>',
				'shots' => array(
					array(
						'caption' => 'pestaña «Enlaces»',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'taller-personas',
				'level' => 3,
				'title' => 'Personas',
				'html'  => '<p>Active <strong>«Pedir a cada centro la lista de personas que participan»</strong> si necesita saber quién participa. Defina:</p>'
					. '<ul><li>Los <strong>papeles</strong>, uno por fila (por ejemplo, «Coordinación general» y «Coordinación de eje»). Marque <strong>«Exige indicar el grupo»</strong> en el que lo necesite: el centro no podrá guardar a esa persona sin su grupo.</li><li>Los <strong>grupos</strong>, uno por línea (por ejemplo, «Eje 1», «Eje 2»).</li><li>El <strong>mínimo</strong> y el <strong>máximo</strong> de personas, y si hay <strong>«Una sola persona por papel y grupo»</strong>, que una lista nueva trae marcada.</li><li>Si <strong>«Una persona puede tener varios papeles»</strong>. Sin marcar, la misma persona (el mismo DNI o NIE) no entra dos veces en la lista, ni con otro papel.</li><li>Las fechas en que se abre y se cierra la lista, que pueden ir más allá del plazo de solicitud.</li></ul>'
					. '<p>Con la lista encendida, un papel cuyo rótulo nombre la coordinación (por ejemplo, «Coordinación») y un mínimo de al menos una persona, el formulario de la solicitud <strong>ya no pide las personas coordinadoras</strong> de «Datos»: se dan de alta en la lista, con su papel. Si falta alguna de las dos cosas, el formulario las sigue pidiendo. Las que ya estuvieran guardadas en solicitudes anteriores se conservan. Para no pedirla dos veces, en «Datos» deje «No se piden»: el taller no deja configurar la coordinación en los dos sitios.</p>',
				'shots' => array(
					array(
						'caption' => 'pestaña «Personas» con papeles y grupos',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'taller-avisos',
				'level' => 3,
				'title' => 'Avisos',
				'html'  => '<p>Los correos que manda el aplicativo para este procedimiento: el acuse al presentar, el aviso al modificar, el aviso a los contactos de cada solicitud, los cambios de revisión, las entregas, los cambios de personas, las retiradas y las prórrogas. Desmarque los que no quiera y pulse <strong>«Guardar los avisos»</strong>.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'taller-consulta',
				'level' => 3,
				'title' => 'Consulta',
				'html'  => '<p>Permite que otras personas vean <strong>en solo lectura</strong> las solicitudes de los centros de su zona. Marque qué columnas ven y si pueden descargar documentos o exportar. Nunca ven el DNI o NIE, la nota de revisión ni el historial.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'taller-codigo',
				'level' => 3,
				'title' => 'Código',
				'html'  => '<p>Estilos propios (CSS) para la ficha pública de este procedimiento. Los guiones (JavaScript) solo los añade quien administra el aplicativo.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'publicar',
				'level' => 2,
				'title' => 'Publique',
				'html'  => '<p>Pestaña <strong>«Publicación»</strong>. Mientras está en borrador, <strong>«Antes de publicar»</strong> marca cada punto con ✓, ✗ o !: el curso, el plazo de solicitud, las preguntas, las fechas de las entregas con plazo propio y la resolución. <strong>Sin curso no se puede publicar; sin fecha de inicio del plazo tampoco</strong>, porque se quedaría en «Próximo» y nadie podría solicitar nunca; si quiere anunciarlo antes, ponga fechas futuras y se publicará como «Próximo». La resolución solo avisa: puede enlazarla después.</p>'
					. '<p>Pulse <strong>«Publicar el procedimiento»</strong>: pasa a verse en la portada. Los centros podrán solicitar cuando llegue la fecha de inicio del plazo. Para retirarlo temporalmente, <strong>«Devolver a borrador»</strong>, que tiene el aspecto de las acciones delicadas y dice al lado lo que pasa: deja de verse en la portada.</p>'
					. '<p>Antes de publicar, repase también a quién se dirige, las preguntas obligatorias y los compromisos. Abra la ficha con el ojo para verla como la verá un centro.</p>',
				'shots' => array(
					array(
						'caption' => 'pestaña «Publicación» con la línea de estados y la etapa actual',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'revisar-solicitudes',
				'level' => 2,
				'title' => 'Revise las solicitudes',
				'html'  => '<p>Pestaña <strong>«Solicitudes»</strong>, o la columna «Solicitudes» de su lista.</p>'
					. '<ul><li>Filtros por estado (Presentada, A subsanar, Admitida, Excluida), por nombre o código del centro, por isla o zona y <strong>«Solo las incompletas»</strong>.</li><li><strong>«Revisar»</strong> en cada fila: elija el estado y escriba la <strong>nota para el centro</strong>, obligatoria al pedir subsanar y al excluir. El centro la lee en su solicitud y recibe un correo.</li><li><strong>«Revisar varias a la vez»</strong>: marque las filas y aplique a todas el mismo estado y la misma nota.</li><li>En la revisión de cada solicitud están el <strong>historial</strong>, la <strong>nota interna</strong> (solo la ve quien revisa), la <strong>prórroga</strong> («Guardar la prórroga» y «Quitar la prórroga») y <strong>«Retirar esta solicitud»</strong>, si se presentó por error, con su motivo. Las retiradas se ven en <strong>«Retiradas»</strong> y se pueden restaurar.</li><li><strong>«Presentar en nombre de un centro»</strong>: para un centro que no pudo entrar a tiempo. Se crea su solicitud vacía y el centro la completa hasta la fecha que usted ponga. Avise al centro: no recibe correo.</li></ul>',
				'shots' => array(
					array(
						'caption' => 'pestaña «Solicitudes» con filtros y la fila «Revisar»',
						'image'   => '',
					),
					array(
						'caption' => 'revisión de una solicitud con estado, nota y prórroga',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'exportar',
				'level' => 2,
				'title' => 'Exporte',
				'html'  => '<p>Botón <strong>«Exportar…»</strong>: elija <strong>todas</strong> o <strong>las del filtro</strong>, las <strong>columnas</strong> y si quiere <strong>una fila por solicitud</strong> o <strong>una por persona coordinadora</strong>. También puede sacar el <strong>fichero de alta de usuarios de Moodle</strong>, con el formato que admite su importación. Se descarga una hoja de cálculo (CSV).</p>',
				'shots' => array(
					array(
						'caption' => 'diálogo «Exportar las solicitudes» con las columnas',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'entregas-y-prorrogas',
				'level' => 2,
				'title' => 'Entregas y prórrogas',
				'html'  => '<ul><li>Pestaña <strong>«Entregas»</strong>: los plazos de las entregas con plazo propio. Puede <strong>ampliar el cierre</strong> sin reabrir la inscripción, con <strong>«Guardar los nuevos cierres»</strong>.</li><li>Prórroga <strong>a un solo centro</strong>: desde su solicitud, como se explica en «Revise las solicitudes». No afecta a los demás.</li></ul>',
				'shots' => array(),
			),
			array(
				'id'    => 'cerrar-y-archivar',
				'level' => 2,
				'title' => 'Cierre y archive',
				'html'  => '<ul><li>El plazo se cierra solo en la fecha de fin. Si necesita cerrarlo antes, pulse <strong>«Cerrar solicitudes ahora»</strong> en la pestaña «Publicación»; <strong>«Volver al calendario automático»</strong> lo deshace.</li><li>Cuando ya no quede nada que tocar (resuelto, listados enlazados, solicitudes revisadas), pulse <strong>«Marcar como histórico»</strong>, al final de la pestaña. Solo aparece con el procedimiento cerrado o resuelto: uno vivo no se puede marcar. Queda en solo lectura y se puede consultar y exportar. <strong>Para volver a abrirlo hay que pedírselo a quien administra el aplicativo.</strong></li></ul>',
				'shots' => array(
					array(
						'caption' => 'bloque «Dar el procedimiento por terminado»',
						'image'   => '',
					),
				),
			),
		),
		'preguntas' => array(
			array(
				'id'    => 'para-quien-solicita',
				'level' => 2,
				'title' => 'Para quien solicita',
				'html'  => '',
				'shots' => array(),
			),
			array(
				'id'    => 'no-veo-el-boton-solicitar',
				'level' => 3,
				'title' => 'No veo el botón «Solicitar». ¿Por qué?',
				'html'  => '<p>Con la sesión iniciada y el plazo abierto, la ficha ya le dice el motivo en el sitio del botón, bajo <strong>«No puede solicitar con esta cuenta»</strong>, con la cuenta con la que ha entrado y el enlace <strong>«Salir y entrar con otra cuenta»</strong>. Si no lo ve, compruebe, por este orden:</p><ol><li><strong>¿Ha pulsado «Acceder» o «Entrar y solicitar»?</strong> Sin iniciar sesión, la ficha solo se lee.</li><li><strong>¿Ha entrado con su cuenta personal?</strong> No con la cuenta genérica del centro. La solicitud de un centro la presenta el equipo directivo con su propia cuenta.</li><li><strong>¿Arriba a la derecha aparece su centro con su código?</strong> Si pone «Sin centro asignado», o la pantalla dice «Su perfil no presenta solicitudes de centro», su cuenta no tiene el perfil de equipo directivo o el centro. Pida que lo corrijan por el canal de ayuda técnica. Es frecuente con las direcciones recién nombradas.</li><li><strong>¿El procedimiento es para centros como el suyo?</strong> Mire «Pueden solicitar» en el resumen de la ficha: puede ser solo para públicos, solo para privados y concertados, solo para profesorado o solo para ciertos centros.</li><li><strong>¿Está abierto el plazo?</strong> Mire el estado y las fechas del resumen.</li></ol>',
				'shots' => array(),
			),
			array(
				'id'    => 'mi-perfil-no-tiene-centro',
				'level' => 3,
				'title' => 'Soy de un centro concertado y no puedo entrar, o mi perfil no tiene centro',
				'html'  => '<p>Su cuenta necesita tener asignado el código de su centro. No se puede cambiar desde la solicitud: pídalo por el canal de ayuda técnica indicando el nombre y el código del centro.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'centro-de-otra-titularidad',
				'level' => 3,
				'title' => 'Mi centro es público y el procedimiento es solo para concertados, o al revés',
				'html'  => '<p>No podrá solicitar: el aplicativo lo impide para que no se presente una solicitud que luego se excluiría. Si cree que su centro debería entrar, escriba al correo de contacto del procedimiento.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'como-se-que-esta-presentada',
				'level' => 3,
				'title' => '¿Cómo sé que mi solicitud ha quedado presentada?',
				'html'  => '<p>Tiene tres pruebas: la línea «Presentada el … a las …» en la propia solicitud, con su justificante («Ver e imprimir el justificante»); el correo «Solicitud presentada n.º …: …», que es su justificante; y la fila en «Solicitudes de mi centro» con el estado «Presentada». Si tiene las tres, está presentada. Si además pone «Incompleta», está presentada pero le falta algo: lo dice al lado.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'inscripcion-incompleta',
				'level' => 3,
				'title' => 'Me dicen que la inscripción no está completa, pero lo rellené todo',
				'html'  => '<p>Casi siempre falta la <strong>lista de personas</strong>, que se rellena después de presentar, en «Personas que participan», o un <strong>documento con plazo propio</strong> obligatorio. Abra la solicitud y mire si pone «Incompleta: …»; los pasos de arriba dicen cuál «Falta». La solicitud sigue presentada: complétela dentro del plazo de la lista.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'no-he-dado-de-alta-a-nadie',
				'level' => 3,
				'title' => 'He presentado la solicitud y no he dado de alta a nadie',
				'html'  => '<p>Es normal: la lista se rellena <strong>después</strong> de presentar. Vuelva a la solicitud («Solicitudes de mi centro» → «Ver la solicitud») y use «Dar de alta a una persona».</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'cambiar-personas-coordinadoras',
				'level' => 3,
				'title' => '¿Dónde añado o cambio a las personas después de presentar? ¿Hasta cuándo?',
				'html'  => '<p>En la misma solicitud, en el bloque «Personas que participan». Lo primero del bloque dice hasta cuándo («Puede dar de alta, cambiar o sustituir personas hasta el …, incluido»), y puede durar más que el plazo de solicitud; lo dice también el correo de acuse. Si el procedimiento tiene lista, las personas coordinadoras están en ella y en ningún otro sitio.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'coordino-un-eje',
				'level' => 3,
				'title' => 'Coordino un eje: ¿dónde digo cuál?',
				'html'  => '<p>Al dar de alta a la persona, elija su papel y, en el grupo, el eje. La etiqueta del grupo dice qué papeles lo exigen («Grupo (obligatorio para: …)»): con uno de ellos, no se puede guardar sin él.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'persona-dada-de-alta-dos-veces',
				'level' => 3,
				'title' => 'He dado de alta dos veces a la misma persona',
				'html'  => '<p>Dé de baja la fila que sobra con «Dar de baja». El aplicativo ya no deja dar de alta a la misma persona dos veces, salvo que el procedimiento admita varios papeles por persona: si lo intenta, le dice con qué papel está y le lleva a su fila para cambiarla.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'sustituir-a-una-persona',
				'level' => 3,
				'title' => 'Tengo que sustituir a una persona',
				'html'  => '<p>Pulse «Sustituir o corregir» en su fila y cambie sus datos. No dé de alta a otra persona nueva: quedarían las dos.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'cambiar-la-solicitud',
				'level' => 3,
				'title' => '¿Puedo cambiar la solicitud después de presentarla?',
				'html'  => '<p>Sí, mientras el plazo esté abierto. Después, solo si le piden subsanar, dentro del plazo de subsanación, o si le conceden una prórroga.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'me-equivoque-de-procedimiento',
				'level' => 3,
				'title' => 'Me equivoqué de procedimiento. ¿Puedo deshacerlo?',
				'html'  => '<p>Sí: pulse «Retirar la solicitud», al final de la solicitud, mientras el plazo siga abierto.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'para-quien-gestiona',
				'level' => 2,
				'title' => 'Para quien gestiona',
				'html'  => '',
				'shots' => array(),
			),
			array(
				'id'    => 'permiso-para-crear',
				'level' => 3,
				'title' => '¿Necesito permiso para crear un procedimiento?',
				'html'  => '<p>No para cada uno. Basta con tener el perfil de gestión y un ámbito asignado. Si no lo tiene, pídalo a quien administra el aplicativo.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'nadie-lo-ve',
				'level' => 3,
				'title' => 'He creado el procedimiento y nadie lo ve',
				'html'  => '<p>Nace en borrador. Pulse «Publicar el procedimiento» en la pestaña «Publicación».</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'sale-como-proximo',
				'level' => 3,
				'title' => 'Está publicado, pero sale como «Próximo» y nadie puede solicitar',
				'html'  => '<p>Todavía no ha llegado la fecha de inicio del plazo de solicitud, o, si se publicó antes de que se exigiera, no la tiene. El estado sale de las fechas: no hay que cambiarlo a mano. Revise los plazos en la pestaña «Datos». Hoy ya no se puede publicar sin esa fecha.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'varias-casillas',
				'level' => 3,
				'title' => '¿Cómo creo varias casillas o varias preguntas?',
				'html'  => '<p>Rellene la «Pregunta nueva» y, si necesita más, las dos de «Añadir otra pregunta», y pulse «Guardar las preguntas»; aparecerán otras tres vacías. Para una lista de casillas que marcar, use una sola pregunta de tipo «Varias opciones», con una opción por línea.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'mismas-preguntas',
				'level' => 3,
				'title' => 'Quiero las mismas preguntas que el curso pasado',
				'html'  => '<p>Abra el procedimiento del curso pasado y pulse «Duplicar». Después, ponga el curso nuevo y las fechas.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'campo-numerico',
				'level' => 3,
				'title' => 'Necesito un campo numérico',
				'html'  => '<p>Use el tipo «Número», con mínimo y máximo si los hay. Para varias cantidades, por ejemplo el alumnado por nivel, use «Cantidades por fila».</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'documento-firmado',
				'level' => 3,
				'title' => 'Necesito que el centro suba un documento de compromiso firmado',
				'html'  => '<p>Cree una pregunta de tipo «Archivo», obligatoria. Si se entrega más tarde, marque «Entrega con plazo propio» y ponga sus fechas. Si basta con que el centro acepte un texto, use «Compromisos del centro» en «Datos».</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'cambie-un-rotulo',
				'level' => 3,
				'title' => 'He cambiado el rótulo de una pregunta con solicitudes ya presentadas',
				'html'  => '<p>Las respuestas ya dadas se quedan como estaban, ahora bajo el rótulo nuevo. Si la pregunta significa otra cosa, vuelva al rótulo anterior y cree una pregunta nueva.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'enlaces-cortados',
				'level' => 3,
				'title' => 'Los enlaces de la resolución salen cortados o no abren',
				'html'  => '<p>Aquí no se sube el PDF: en «Enlaces» se pega la dirección completa donde está publicado. Copie la dirección entera desde la barra del navegador, empezando por https://, y compruébela con «Abrir el enlace guardado ↗», debajo del campo.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'exportar-mas-columnas',
				'level' => 3,
				'title' => 'Quiero exportar con más columnas',
				'html'  => '<p>En «Exportar…» elija las columnas. Además de las habituales hay otras, como los datos del catálogo del centro o la nota interna, que solo salen si se marcan.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'centro-fuera-de-plazo',
				'level' => 3,
				'title' => 'Un centro no pudo presentar a tiempo',
				'html'  => '<p>En «Solicitudes», use «Presentar en nombre de un centro» si no tiene solicitud, o dele una prórroga desde su solicitud si ya la tiene. Avise al centro.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'historico-por-error',
				'level' => 3,
				'title' => 'He marcado el procedimiento como histórico por error',
				'html'  => '<p>Pida a quien administra el aplicativo que lo vuelva a abrir.</p>',
				'shots' => array(),
			),
		),
	);
}

}








namespace Prc\PublicFront\View;

use Prc\PublicFront\HelpContent;
use Prc\PublicFront\Shell;











if ( ! class_exists( 'Prc\PublicFront\View\HelpView', false ) ) {
final class HelpView {







	public static function html( array $m ): string {
		$secciones = (array) $m['sections'];

		ob_start();
		?>
		<div class="prc-ayuda">
			<nav class="prc-ayuda__guias" aria-label="Guías de ayuda">
				<ul>
					<?php foreach ( (array) $m['tabs'] as $pestana ) : ?>
						<li><a href="<?php echo esc_url( (string) $pestana['url'] ); ?>"<?php echo empty( $pestana['active'] ) ? '' : ' class="is-activo" aria-current="page"'; ?>><?php echo esc_html( (string) $pestana['label'] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</nav>

			<article class="prc-ayuda__guia" aria-labelledby="prc-ayuda-titulo">
				<h2 class="prc-ayuda__titulo" id="prc-ayuda-titulo"><?php echo esc_html( (string) $m['label'] ); ?></h2>
				<p class="prc-intro"><?php echo esc_html( (string) $m['intro'] ); ?></p>
				<?php
				echo self::index( $secciones );
				foreach ( $secciones as $apartado ) {
					echo self::section( (array) $apartado );
				}
				?>
			</article>

			<?php echo self::support( (string) $m['support'] );?>
		</div>
		<?php
		return Shell::render( 'Ayuda', '', (string) ob_get_clean() );
	}







	private static function index( array $secciones ): string {
		$html    = '';
		$abierta = false;
		foreach ( $secciones as $apartado ) {
			$enlace = '<a href="#' . esc_attr( (string) $apartado['id'] ) . '">' . esc_html( (string) $apartado['title'] ) . '</a>';
			if ( 3 === (int) $apartado['level'] ) {
				$html   .= ( $abierta ? '' : '<ul>' ) . '<li>' . $enlace . '</li>';
				$abierta = true;
				continue;
			}
			$html   .= ( $abierta ? '</ul>' : '' ) . ( '' === $html ? '' : '</li>' ) . '<li>' . $enlace;
			$abierta = false;
		}
		if ( '' === $html ) {
			return '';
		}
		$html .= ( $abierta ? '</ul>' : '' ) . '</li>';
		return '<nav class="prc-ayuda__indice" aria-label="Índice de la guía"><h3 class="prc-ayuda__indice-titulo">En esta guía</h3><ol>' . $html . '</ol></nav>';
	}







	private static function section( array $apartado ): string {
		$id     = (string) $apartado['id'];
		$nivel  = 3 === (int) $apartado['level'] ? 'h4' : 'h3';
		$titulo = '<' . $nivel . ' class="prc-ayuda__apartado-titulo" id="' . esc_attr( $id . '-titulo' ) . '">' . esc_html( (string) $apartado['title'] ) . '</' . $nivel . '>';

		$capturas = '';
		foreach ( (array) $apartado['shots'] as $captura ) {
			$capturas .= self::shot( (array) $captura );
		}

		return '<section class="prc-ayuda__apartado prc-ayuda__apartado--' . esc_attr( $nivel ) . '" id="' . esc_attr( $id ) . '" aria-labelledby="' . esc_attr( $id . '-titulo' ) . '">'
			. $titulo
			. wp_kses( (string) $apartado['html'], HelpContent::ALLOWED )
			. $capturas
			. '</section>';
	}







	private static function shot( array $captura ): string {
		$texto  = (string) ( $captura['caption'] ?? '' );
		$imagen = (string) ( $captura['image'] ?? '' );
		if ( '' !== $imagen ) {
			return '<figure class="prc-ayuda__captura"><img src="' . esc_url( $imagen ) . '" alt="' . esc_attr( $texto ) . '" loading="lazy" />'
				. '<figcaption>' . esc_html( $texto ) . '</figcaption></figure>';
		}
		return '<p class="prc-ayuda__captura prc-ayuda__captura--pendiente">' . esc_html( 'Captura pendiente: ' . $texto ) . '</p>';
	}







	private static function support( string $configurada ): string {
		ob_start();
		?>
		<section class="prc-ayuda__soporte" aria-labelledby="prc-ayuda-soporte">
			<h2 class="prc-ayuda__titulo" id="prc-ayuda-soporte">¿Necesita ayuda técnica?</h2>
			<p>Para las dudas sobre un procedimiento —requisitos, plazos, quién puede participar—, escriba al área convocante, al <strong>correo de contacto</strong> que aparece en la ficha de ese procedimiento.</p>
			<?php echo wp_kses( $configurada, HelpContent::ALLOWED ); ?>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}

}








namespace Prc\PublicFront;

use Prc\PublicFront\View\HelpView;
use Prc\PublicFront\View\ProcedureChrome;














if ( ! class_exists( 'Prc\PublicFront\Help', false ) ) {
final class Help {

	public const SHORTCODE = 'prc_help';




	public const ARG_GUIDE = 'guia';






	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'render' ) );
	}






	public static function guide(): string {

		$crudo = isset( $_GET[ self::ARG_GUIDE ] ) && is_string( $_GET[ self::ARG_GUIDE ] ) ? sanitize_key( wp_unslash( $_GET[ self::ARG_GUIDE ] ) ) : '';
		return self::known( $crudo );
	}







	private static function known( string $guide ): string {
		return isset( HelpContent::GUIDES[ $guide ] ) ? $guide : HelpContent::GUIDE_APPLY;
	}











	public static function url( string $guide, string $anchor = '' ): string {
		$url = Shell::url( 'help', array( self::ARG_GUIDE => self::known( $guide ) ) );
		if ( '' === $url ) {
			return '';
		}
		$anchor = sanitize_title( $anchor );
		return '' === $anchor ? $url : $url . '#' . $anchor;
	}










	public static function support(): string {
		$texto = ProcedureChrome::chrome()['help_contact'] ?? '';
		return is_string( $texto ) ? trim( wp_kses( $texto, HelpContent::ALLOWED ) ) : '';
	}






	public static function model(): array {
		$guia     = self::guide();
		$pestanas = array();
		foreach ( HelpContent::GUIDES as $clave => $datos ) {
			$pestanas[ $clave ] = array(
				'label'  => $datos['label'],
				'url'    => self::url( $clave ),
				'active' => $clave === $guia,
			);
		}

		return array(
			'guide'    => $guia,
			'label'    => HelpContent::GUIDES[ $guia ]['label'],
			'intro'    => HelpContent::GUIDES[ $guia ]['intro'],
			'tabs'     => $pestanas,
			'sections' => HelpContent::sections( $guia ),
			'support'  => self::support(),
		);
	}







	public static function render( $atts = array() ): string {
		unset( $atts );
		Assets::enqueue();
		return HelpView::html( self::model() );
	}
}

}








namespace Prc\PublicFront\View;

use Prc\Domain\DateRange;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PublicFront\Assets;















if ( ! class_exists( 'Prc\PublicFront\View\ProcedureChrome', false ) ) {
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




			'help_contact'   => '',

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

			<?php if ( ! empty( $m['deliveries'] ) ) : ?>
				<section class="prc-seccion" aria-labelledby="prc-delivery-dates">
					<h2 id="prc-delivery-dates">Entregas de documentación</h2>
					<p>Los centros que hayan presentado solicitud pueden entregar estos documentos desde «Solicitudes de mi centro», dentro de cada plazo.</p>
					<ul>
						<?php foreach ( $m['deliveries'] as $delivery ) : ?>
							<li><strong><?php echo esc_html( (string) $delivery['label'] ); ?></strong>: <?php echo esc_html( DateRange::of( (string) $delivery['opens_at'], (string) $delivery['closes_at'] ) ); ?></li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>
			<hr class="prc-filete" />
			<div class="prc-ficha-pie">
				<?php if ( '' !== (string) $m['manage_url'] ) : ?>
					<a class="<?php echo esc_attr( Assets::button_class( true ) ); ?>" href="<?php echo esc_url( (string) $m['manage_url'] ); ?>">
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
			array( 'Pueden solicitar', (string) ( $m['who_can_apply'] ?? '' ), '' ),
			array( 'Curso', (string) $m['courses'], '' ),
			array( 'Inscripción', (string) $m['dates'], '' ),
			array( 'Subsanación', (string) $m['amend_dates'], '' ),
			array( 'Coordinación', (int) ( $m['coordinator_count'] ?? 0 ) < 1 ? '' : sprintf( 'Pide %d %s coordinadora%s', (int) $m['coordinator_count'], 1 === (int) $m['coordinator_count'] ? 'persona' : 'personas', 1 === (int) $m['coordinator_count'] ? '' : 's' ), '' ),
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
		$accion   = (array) $m['action'];
		$icono    = (string) ( $accion['icon'] ?? '' );
		$manda    = ! empty( $accion['primary'] );
		$clases   = Assets::button_class( $manda ) . ( $manda ? '' : ' prc-btn--sec' );
		$docentes = ProcedureMetaKeys::AUDIENCE_TEACHERS === (string) ( $m['audience_key'] ?? '' );

		ob_start();
		?>
		<div class="prc-accesos">
			<div class="prc-acceso">
				<h2 class="prc-acceso__rotulo"><?php echo esc_html( $docentes ? 'Lo solicita cada docente' : 'Lo solicita la dirección del centro' ); ?></h2>
				<p class="prc-acceso__texto">
					<?php if ( $docentes ) : ?>
						Cada docente entra con su <strong>cuenta personal</strong> y presenta su propia solicitud. No lo presenta el centro.
					<?php else : ?>
						Entre con su <strong>cuenta personal</strong> de la dirección, no con la cuenta genérica del centro.
					<?php endif; ?>
				</p>
				<?php if ( '' !== (string) ( $m['why_not'] ?? '' ) ) : ?>
					<?php echo self::why_not( $m );?>
				<?php elseif ( '' !== (string) $accion['url'] ) : ?>
					<p class="prc-acceso__accion">
						<a class="<?php echo esc_attr( $clases ); ?>" href="<?php echo esc_url( (string) $accion['url'] ); ?>">
							<?php if ( '' !== $icono ) : ?>
								<span class="material-symbols-outlined" aria-hidden="true"><?php echo esc_html( $icono ); ?></span>
							<?php endif; ?>
							<?php echo esc_html( (string) $accion['label'] ); ?>
						</a>
					</p>
				<?php endif; ?>
				<?php echo PanelParts::help_link( (string) ( $m['help_url'] ?? '' ), '', 'Cómo se solicita' );?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}












	private static function why_not( array $m ): string {
		$cuenta = (array) ( $m['account'] ?? array() );
		$nombre = (string) ( $cuenta['name'] ?? '' );
		$correo = (string) ( $cuenta['email'] ?? '' );

		ob_start();
		?>
		<div class="<?php echo esc_attr( Assets::alert_class( 'warning' ) ); ?> prc-acceso__motivo" role="status">
			<p class="prc-acceso__motivo-tit"><strong>No puede solicitar con esta cuenta</strong></p>
			<p><?php echo esc_html( (string) $m['why_not'] ); ?></p>
			<?php if ( '' !== $nombre || '' !== $correo ) : ?>
				<p>Ha entrado como <strong><?php echo esc_html( '' !== $nombre ? $nombre : $correo ); ?></strong><?php echo '' !== $nombre && '' !== $correo ? esc_html( ' (' . $correo . ')' ) : ''; ?>.</p>
			<?php endif; ?>
			<ul class="prc-acceso__salidas">
				<?php if ( '' !== (string) ( $m['switch_url'] ?? '' ) ) : ?>
					<li><a href="<?php echo esc_url( (string) $m['switch_url'] ); ?>">Salir y entrar con otra cuenta</a></li>
				<?php endif; ?>
				<?php if ( '' !== (string) ( $m['faq_url'] ?? '' ) ) : ?>
					<li><a href="<?php echo esc_url( (string) $m['faq_url'] ); ?>">¿Por qué no veo el botón «Solicitar»?</a></li>
				<?php endif; ?>
			</ul>
		</div>
		<?php
		return (string) ob_get_clean();
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











	public static function audience_label( string $audience, string $schools ): string {
		return ProcedureMetaKeys::AUDIENCE_TEACHERS === $audience
			? (string) ProcedureMetaKeys::audiences()[ ProcedureMetaKeys::AUDIENCE_TEACHERS ]
			: $schools;
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

}








namespace Prc\PublicFront;

use Prc\Access\CentreScope;
use Prc\Access\ProcedureAccess;
use Prc\Domain\CentreEligibility;
use Prc\Domain\DateRange;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ProcedurePostType;
use Prc\PublicFront\View\ProcedureChrome;
use Prc\Taxonomy\ProcedureTaxonomies;

















if ( ! class_exists( 'Prc\PublicFront\ProcedureView', false ) ) {
final class ProcedureView {




	public const PRIORITY = 20;






	public static function register(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_custom_code' ) );
		add_action( 'template_redirect', array( self::class, 'render' ), self::PRIORITY );
	}


	public static function enqueue_custom_code(): void {
		if ( ! is_singular( ProcedurePostType::POST_TYPE ) ) {
			return;
		}
		$post_id = (int) get_queried_object_id();
		if ( $post_id <= 0 || 'publish' !== get_post_status( $post_id ) ) {
			return;
		}
		$css = (string) get_post_meta( $post_id, ProcedureMetaKeys::CUSTOM_CSS, true );
		$js  = (string) get_post_meta( $post_id, ProcedureMetaKeys::CUSTOM_JS, true );
		if ( '' === $css && '' === $js ) {
			return;
		}
		Assets::enqueue();
		if ( '' !== $css ) {
			wp_add_inline_style( 'prc-app', str_replace( '<', '\\3C ', $css ) );
		}
		if ( '' !== $js ) {
			wp_add_inline_script( 'prc-app', str_ireplace( '</script', '<\\/script', $js ), 'after' );
		}
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
		$accion    = self::action( $post_id, $state, $user_id, $mia );
		$gestion   = self::manage_url( $post_id, $user_id );

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
				'state_label'          => ProcedureState::label(
					$state,
					(bool) $meta[ ProcedureMetaKeys::APPLICATIONS_CLOSED ]
				),
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
				'audience_key'         => $audiencia,
				'ownership'            => ProcedureChrome::ownership_label( $ownership ),
				'who_can_apply'        => self::who_can_apply( $post_id, $audiencia ),
				'coordinator_count'    => Applications::coordinator_count( $post_id ),
				'requires_coordinator' => Applications::coordinator_count( $post_id ) > 0,


				'contact_emails'       => $correos,
				'contact_email'        => (string) ( $correos[0] ?? '' ),
				'header_color'         => ProcedureMetaKeys::header_hex( $meta[ ProcedureMetaKeys::HEADER_COLOR ] ),
				'deliveries'           => array_values( array_filter( Applications::questions( $post_id ), array( \Prc\Domain\ProcedureQuestions::class, 'is_delivery' ) ) ),
				'links'                => self::links( $meta ),
				'resolution_url'       => trim( (string) $meta[ ProcedureMetaKeys::RESOLUTION_URL ] ),
				'image'                => $cartel > 0 ? (string) wp_get_attachment_image_url( $cartel, 'large' ) : '',
				'image_alt'            => $cartel > 0 ? (string) get_post_meta( $cartel, '_wp_attachment_image_alt', true ) : '',
				'mine'                 => $mia,
				'action'               => $accion,
				'manage_url'           => $gestion,
				'why_not'              => self::why_not( $post_id, $state, $user_id, array( $accion['url'], $gestion ) ),
				'account'              => self::account( $user_id ),
				'switch_url'           => self::switch_url( $post_id, $user_id ),
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
			'audience_key'         => '',
			'ownership'            => '',
			'who_can_apply'        => '',
			'requires_coordinator' => false,
			'coordinator_count'    => 0,
			'contact_emails'       => array(),
			'contact_email'        => '',
			'header_color'         => ProcedureMetaKeys::header_hex( '' ),
			'deliveries'           => array(),
			'links'                => array(),
			'resolution_url'       => '',
			'image'                => '',
			'image_alt'            => '',
			'mine'                 => array(),
			'action'               => self::no_action(),
			'manage_url'           => '',

			'why_not'              => '',
			'account'              => array(
				'name'  => '',
				'email' => '',
			),
			'switch_url'           => '',

			'help_url'             => Help::url( HelpContent::GUIDE_APPLY, 'antes-de-empezar' ),

			'faq_url'              => Help::url( HelpContent::GUIDE_FAQ, 'no-veo-el-boton-solicitar' ),
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
		if ( user_can( $user_id, ProcedureAccess::CAP_APPLY_AS_TEACHER ) && ProcedureMetaKeys::AUDIENCE_TEACHERS === get_post_meta( $post_id, ProcedureMetaKeys::AUDIENCE, true ) ) {
			$solicitud = Applications::find_for_teacher( $post_id, $user_id );
			$url       = ApplyForm::url( $post_id );
			$vacia     = array(
				'has'   => false,
				'state' => '',
				'label' => '',
				'date'  => '',
				'iso'   => '',
				'url'   => $url,
			);
			if ( null === $solicitud ) {
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


		$code = ApplyForm::chosen( $user_id, $post_id, '' );
		$code = '' !== $code ? $code : CentreScope::code_for( $user_id );
		if ( '' === $code ) {
			return array();
		}

		$url   = ApplyForm::url_for_code( $post_id, $code );
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
				'label'   => 'Entrar y solicitar',
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

















	private static function why_not( int $post_id, string $state, int $user_id, array $buttons ): string {
		if ( $user_id <= 0 || array() !== array_filter( $buttons ) || ! ProcedureState::accepts_applications( $state )
			|| user_can( $user_id, ProcedureAccess::CAP_MANAGE_PROCEDURES ) ) {
			return '';
		}
		if ( ! user_can( $user_id, ProcedureAccess::CAP_APPLY ) ) {
			foreach ( array( ProcedureAccess::CAP_REVIEW, ProcedureAccess::CAP_VIEW_APPLICATIONS, ProcedureAccess::CAP_MANAGE ) as $funcion ) {
				if ( user_can( $user_id, $funcion ) ) {
					return '';
				}
			}
		}
		return ProcedureAccess::why_not_apply( $user_id, $post_id );
	}










	private static function account( int $user_id ): array {
		$quien = $user_id > 0 ? get_userdata( $user_id ) : false;
		return array(
			'name'  => $quien instanceof \WP_User ? (string) $quien->display_name : '',
			'email' => $quien instanceof \WP_User ? (string) $quien->user_email : '',
		);
	}








	private static function switch_url( int $post_id, int $user_id ): string {
		return $user_id > 0 ? wp_logout_url( wp_login_url( (string) get_permalink( $post_id ) ) ) : '';
	}


















	public static function who_can_apply( int $post_id, string $audience ): string {
		if ( ProcedureMetaKeys::AUDIENCE_TEACHERS === $audience ) {
			return 'Profesorado: cada docente con su cuenta';
		}
		$rules = ProcedureAccess::eligibility( $post_id );
		if ( in_array( ProcedureMetaKeys::KIND_SCHOOL, ProcedureAccess::centre_kinds( $post_id ), true ) ) {
			return CentreEligibility::describe( $rules );
		}
		$cer                = 'Agrupaciones de escuelas rurales (CER), como colectivo';
		$rules['ownership'] = array();
		return CentreEligibility::restricted( $rules ) ? $cer . ': ' . lcfirst( CentreEligibility::describe( $rules ) ) : $cer;
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

}








namespace Prc\PublicFront\View;

use Prc\Meta\ProcedureMetaKeys;
use Prc\PublicFront\Assets;
use Prc\PublicFront\ProcedureEditor;
use Prc\PublicFront\Shell;
use Prc\PublicFront\Workspace;









if ( ! class_exists( 'Prc\PublicFront\View\WorkspaceView', false ) ) {
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
			<?php echo Shell::notice( (string) $m['notice']['type'], (string) $m['notice']['text'], true );?>
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
							<?php echo Shell::icon_plus();?>
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
		$cursos = (array) $m['courses'];
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
			'Mostrando <strong>%1$s</strong> %2$s%3$s',
			esc_html( (string) $n ),
			esc_html( 1 === $n ? 'procedimiento' : 'procedimientos' ),
			'' !== $curso
				? ' en <strong>' . esc_html( $curso ) . '</strong>'
				: ''
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
					<?php echo esc_html( '' !== $curso ? 'Procedimientos de ' . $curso : 'Procedimientos' ); ?>
				</caption>
				<thead>
					<tr class="prc-tabla__grupos">
						<th scope="colgroup" colspan="6" class="prc-tabla__grupo prc-tabla__grupo--1">Detalles del procedimiento</th>
						<th scope="colgroup" colspan="1" class="prc-tabla__grupo prc-tabla__grupo--2">Fecha límite</th>
						<th scope="colgroup" colspan="4" class="prc-tabla__grupo prc-tabla__grupo--3">Gestión</th>
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
						<th scope="col" class="prc-th--c prc-th--centro">Duplicar</th>
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
				<?php if ( '' !== (string) ( $row['ownership_label'] ?? '' ) ) : ?>
					<br /><span class="<?php echo esc_attr( Assets::state_class( 'audience' ) ); ?>"><?php echo esc_html( (string) $row['ownership_label'] ); ?></span>
				<?php endif; ?>
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
			<td class="prc-celda-accion" data-rotulo="Duplicar">
				<?php if ( ! empty( $row['can_duplicate'] ) ) : ?>
					<?php self::duplicate( $row ); ?>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}











	private static function duplicate( array $row ): void {
		$op = ProcedureEditor::OP_DUPLICATE;
		?>
		<form class="prc-acciones" method="post" action="<?php echo esc_url( (string) $row['url'] ); ?>"
			data-prc-confirm="<?php echo esc_attr( ProcedureEditorView::duplicate_question( (string) $row['title'] ) ); ?>"
			data-prc-confirm-ok="Duplicar" data-prc-once>
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::nonce_name( $op ) ); ?>" value="<?php echo esc_attr( wp_create_nonce( ProcedureEditor::nonce_action( $op ) ) ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) $row['id'] ); ?>" />
			<button type="submit" class="prc-accion" title="Duplicar este procedimiento">
				<span class="material-symbols-outlined" aria-hidden="true">content_copy</span>
				<span class="screen-reader-text">Duplicar este procedimiento</span>
			</button>
		</form>
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















if ( ! class_exists( 'Prc\PublicFront\Workspace', false ) ) {
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



		$crea = ProcedureAccess::can_manage_procedures( $user_id );
		foreach ( self::scope( $user_id ) as $post ) {
			if ( ProcedureAccess::can_open( $user_id, (int) $post->ID ) ) {
				$fila                  = self::row( $post );
				$fila['can_duplicate'] = $crea && 'trash' !== $post->post_status;
				$rows[]                = $fila;
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


			'ownership_label'  => ProcedureMetaKeys::AUDIENCE_SCHOOLS === $publico ? ProcedureView::who_can_apply( $id, $publico ) : '',
			'state'            => $state,
			'state_label'      => ProcedureState::label(
				$state,
				(bool) get_post_meta( $id, ProcedureMetaKeys::APPLICATIONS_CLOSED, true )
			),
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

		return array() === $cursos ? 0 : (int) array_key_first( $cursos );
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

}








namespace Prc\PublicFront\View;

use Prc\PublicFront\ApplicationExport;
use Prc\PublicFront\ApplicationsPanel;
use Prc\PublicFront\Assets;
use Prc\PublicFront\CentreColumns;
use Prc\PublicFront\ProcedureEditor;







if ( ! class_exists( 'Prc\PublicFront\View\ExportDialogView', false ) ) {
final class ExportDialogView {

















	public static function html( array $m ): string {
		$op       = ProcedureEditor::OP_EXPORT;
		$export   = (array) ( $m['export'] ?? array() );
		$eleccion = (array) ( $export['choice'] ?? array() );
		$columnas = (array) ( $eleccion['columns'] ?? array() );
		$quienes  = (array) ( $eleccion['who'] ?? array() );
		$formato  = (string) ( $eleccion['format'] ?? ApplicationExport::FORMAT_CSV );
		$forma    = (string) ( $eleccion['shape'] ?? ApplicationExport::SHAPE_APPLICATION );

		$radios_formato = self::radios(
			ProcedureEditor::FIELD_EXPORT_FORMAT,
			array(
				ApplicationExport::FORMAT_CSV    => 'Hoja de cálculo (CSV)',
				ApplicationExport::FORMAT_MOODLE => 'Fichero de alta de usuarios de Moodle',
			),
			$formato,
			'data-prc-export-format'
		);
		$radios_forma   = self::radios(
			ProcedureEditor::FIELD_EXPORT_SHAPE,
			array(
				ApplicationExport::SHAPE_APPLICATION => 'Solicitud',
				ApplicationExport::SHAPE_PERSON      => 'Persona coordinadora: la solicitud se repite en cada una',
			),
			$forma
		);

		ob_start();
		?>
		<details class="prc-exportar" data-prc-export>
			<summary class="<?php echo esc_attr( Assets::button_class() ); ?> prc-exportar__abrir">Exportar…</summary>
			<form class="prc-form prc-form--taller prc-exportar__form" method="post" action="" aria-labelledby="prc-exportar-titulo">
				<h2 class="prc-exportar__titulo" id="prc-exportar-titulo">Exportar las solicitudes</h2>
				<?php wp_nonce_field( ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op ), false ); ?>
				<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
				<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
				<?php echo self::scope( $export );?>

				<fieldset class="prc-exportar__grupo">
					<legend>Formato</legend>
					<?php echo $radios_formato;?>
				</fieldset>

				<div class="prc-exportar__formato" data-prc-export-for="<?php echo esc_attr( ApplicationExport::FORMAT_CSV ); ?>">
					<fieldset class="prc-exportar__grupo">
						<legend>Una fila por</legend>
						<?php echo $radios_forma;?>
					</fieldset>
					<fieldset class="prc-exportar__grupo">
						<legend>Columnas</legend>
						<label class="prc-casilla">
							<input type="checkbox" name="<?php echo esc_attr( ProcedureEditor::FIELD_EXPORT_ALL ); ?>" value="1" data-prc-export-all <?php checked( array() === $columnas ); ?> />
							Todas las columnas
						</label>
						<p class="prc-campo__ayuda">Marcada, se exportan todas las de siempre y no se miran las de abajo, salvo las del catálogo del centro (isla, municipio, tipo, titularidad y <?php echo esc_html( mb_strtolower( CentreColumns::zone_label() ) ); ?>), que solo salen si se marcan.</p>
						<?php foreach ( (array) ( $export['groups'] ?? array() ) as $grupo => $de_grupo ) : ?>
							<?php
							if ( array() === $de_grupo ) {
								continue;
							}
							?>
							<fieldset class="prc-exportar__columnas">
								<legend><?php echo esc_html( (string) ( $export['group_labels'][ $grupo ] ?? $grupo ) ); ?></legend>
								<?php foreach ( (array) $de_grupo as $clave => $rotulo ) : ?>
									<?php



									$extra = in_array( (string) $clave, CentreColumns::extras(), true );
									?>
									<label class="prc-casilla">
										<input type="checkbox" name="<?php echo esc_attr( ProcedureEditor::FIELD_EXPORT_COLUMNS ); ?>[]" value="<?php echo esc_attr( (string) $clave ); ?>" <?php echo $extra ? 'data-prc-export-extra' : 'data-prc-export-col'; ?> <?php checked( ( ! $extra && array() === $columnas ) || in_array( (string) $clave, $columnas, true ) ); ?> />
										<?php echo esc_html( (string) $rotulo ); ?>
									</label>
								<?php endforeach; ?>
							</fieldset>
						<?php endforeach; ?>
					</fieldset>
				</div>

				<fieldset class="prc-exportar__grupo prc-exportar__formato" data-prc-export-for="<?php echo esc_attr( ApplicationExport::FORMAT_MOODLE ); ?>">
					<legend>Quiénes salen en el fichero de usuarios</legend>
					<?php foreach ( (array) ( $export['who'] ?? array() ) as $clave => $quien ) : ?>
						<label class="prc-casilla">
							<input type="checkbox" name="<?php echo esc_attr( ProcedureEditor::FIELD_EXPORT_WHO ); ?>[]" value="<?php echo esc_attr( (string) $clave ); ?>" <?php checked( array() === $quienes || in_array( (string) $clave, $quienes, true ) ); ?> />
							<?php echo self::who_counts( (array) $quien, ! empty( $export['filtered'] ) );?>
						</label>
					<?php endforeach; ?>
					<p class="prc-campo__ayuda">Columnas <code><?php echo esc_html( ApplicationExport::MOODLE_HEADER ); ?></code>, una fila por persona y sin repetir usuario. No salen: quien no tiene un correo válido; las incompletas, sin nombre, apellidos o usuario; y quien comparte usuario con otra persona de distinto correo, que se queda con la primera.</p>
				</fieldset>

				<div class="prc-acciones">
					<button class="<?php echo esc_attr( Assets::button_class( true ) ); ?>" type="submit">Descargar el fichero</button>
				</div>
			</form>
		</details>
		<?php
		return (string) ob_get_clean();
	}









	private static function scope( array $export ): string {
		if ( empty( $export['filtered'] ) ) {
			return '';
		}
		$cuenta = (int) ( $export['count'] ?? 0 );
		$todas  = (int) ( $export['all'] ?? 0 );
		return ApplicationsPanelView::carry( (array) ( $export['args'] ?? array() ) )
			. '<fieldset class="prc-exportar__grupo prc-exportar__cuales">'
			. '<legend>Qué solicitudes</legend>'
			. '<p class="prc-campo__ayuda">' . esc_html( sprintf( 1 === $cuenta ? 'Se exportará %d solicitud (filtro activo).' : 'Se exportarán %d solicitudes (filtro activo).', $cuenta ) ) . ' Los recuentos de quién sale en el fichero de usuarios se dan para las dos opciones.</p>'
			. self::radios(
				ApplicationsPanel::FIELD_EXPORT_SCOPE,
				array(
					ApplicationsPanel::SCOPE_FILTER => sprintf( 'Las del filtro (%d)', $cuenta ),
					ApplicationsPanel::SCOPE_ALL    => sprintf( 'Todas (%d)', $todas ),
				),
				ApplicationsPanel::SCOPE_FILTER,
				'data-prc-export-scope'
			)
			. '</fieldset>';
	}










	private static function radios( string $name, array $options, string $selected, string $marca = '' ): string {
		$out = '';
		foreach ( $options as $valor => $rotulo ) {
			$out .= sprintf(
				'<label class="prc-casilla"><input type="radio" name="%1$s" value="%2$s"%3$s%4$s /> %5$s</label>',
				esc_attr( $name ),
				esc_attr( (string) $valor ),
				'' !== $marca ? ' ' . esc_attr( $marca ) : '',
				checked( (string) $valor, $selected, false ),
				esc_html( $rotulo )
			);
		}
		return $out;
	}












	private static function who_counts( array $quien, bool $filtered ): string {
		if ( ! $filtered ) {
			return esc_html( self::who_label( $quien ) );
		}
		$out = esc_html( (string) ( $quien['label'] ?? '' ) );
		foreach ( array(
			ApplicationsPanel::SCOPE_FILTER => array( 'las del filtro', (array) ( $quien['filter'] ?? array() ) ),
			ApplicationsPanel::SCOPE_ALL    => array( 'todas', $quien ),
		) as $ambito => list( $rotulo, $cuenta ) ) {
			$out .= ' <span data-prc-export-scope-count="' . esc_attr( $ambito ) . '">'
				. esc_html( self::who_label( array( 'label' => $rotulo . ':' ) + $cuenta ) ) . '</span>';
		}
		return $out;
	}












	private static function who_label( array $quien ): string {
		$cuenta  = (int) ( $quien['count'] ?? 0 );
		$motivos = array(
			'email'      => array( '%d sin correo válido', '%d sin correo válido' ),
			'incomplete' => array( '%d incompleta', '%d incompletas' ),
			'conflict'   => array( '%d con el usuario en conflicto', '%d con el usuario en conflicto' ),
			'invalid'    => array( '%d con un usuario no válido para la plataforma', '%d con un usuario no válido para la plataforma' ),
		);
		$fuera   = array();
		foreach ( $motivos as $clave => list( $una, $varias ) ) {
			$n = (int) ( $quien[ $clave ] ?? 0 );
			if ( $n > 0 ) {
				$fuera[] = sprintf( 1 === $n ? $una : $varias, $n );
			}
		}
		$texto = sprintf( 1 === $cuenta ? '%d persona' : '%d personas', $cuenta );
		if ( array() !== $fuera ) {
			$texto .= '. No saldrán: ' . implode( ', ', $fuera );
		}
		$rotulo = (string) ( $quien['label'] ?? '' );

		return ':' === substr( $rotulo, -1 ) ? '(' . $rotulo . ' ' . $texto . ')' : $rotulo . ' (' . $texto . ')';
	}
}

}









namespace Prc\PublicFront\View;

use Prc\Meta\ApplicationMetaKeys;
use Prc\PublicFront\ApplicationActionsPanel;
use Prc\PublicFront\Assets;
use Prc\PublicFront\ProcedureEditor;
use Prc\PublicFront\Shell;
use Prc\PublicFront\WithdrawForm;









if ( ! class_exists( 'Prc\PublicFront\View\ApplicationActionsView', false ) ) {
final class ApplicationActionsView {









	public static function withdraw( array $w ): string {
		if ( empty( $w['show'] ) || ( empty( $w['can'] ) && '' === (string) ( $w['rejected'] ?? '' ) ) ) {
			return Shell::notice( 'error', (string) ( $w['rejected'] ?? '' ) );
		}
		ob_start();
		?>
		<section class="prc-seccion" id="<?php echo esc_attr( WithdrawForm::ANCHOR ); ?>">
			<h3 class="prc-seccion__titulo">Retirar la solicitud</h3>
			<?php echo Shell::notice( 'error', (string) $w['rejected'] );?>
			<?php if ( ! empty( $w['can'] ) ) : ?>
				<p>Si ya no quiere participar en este procedimiento, puede retirar la solicitud mientras el plazo siga abierto. Dejará de contar y, mientras haya plazo, podrá presentar otra nueva.</p>
				<form class="prc-form prc-acciones" method="post" action="<?php echo esc_url( (string) $w['action'] ); ?>"
					data-prc-confirm="¿Retirar su solicitud? Dejará de contar para este procedimiento. Mientras el plazo siga abierto podrá presentar otra, pero tendrá que rellenarla de nuevo."
					data-prc-confirm-ok="Retirar la solicitud" data-prc-once>
					<?php wp_nonce_field( (string) $w['nonce_action'], WithdrawForm::NONCE_FIELD, false ); ?>
					<input type="hidden" name="<?php echo esc_attr( WithdrawForm::FIELD_OP ); ?>" value="<?php echo esc_attr( WithdrawForm::OP_WITHDRAW ); ?>" />
					<input type="hidden" name="<?php echo esc_attr( WithdrawForm::FIELD_APPLICATION ); ?>" value="<?php echo esc_attr( (string) (int) $w['application'] ); ?>" />
					<button class="<?php echo esc_attr( Assets::button_class( 'danger' ) ); ?>" type="submit">Retirar la solicitud</button>
				</form>
			<?php else : ?>
				<p><?php echo esc_html( (string) $w['why'] ); ?></p>
			<?php endif; ?>
		</section>
		<?php
		return (string) ob_get_clean();
	}









	public static function bar( array $m ): string {
		$a = (array) ( $m['actions'] ?? array() );
		if ( array() === $a || true !== ( $m['can_review'] ?? false ) ) {
			return '';
		}
		$out  = '<p class="prc-retiradas-enlace">';
		$out .= ! empty( $a['withdrawn_open'] )
			? '<a href="' . esc_url( (string) $a['back_url'] ) . '">Volver a las solicitudes</a>'
			: '<a href="' . esc_url( (string) $a['withdrawn_url'] ) . '">' . esc_html( sprintf( 'Retiradas (%d)', (int) $a['withdrawn_count'] ) ) . '</a>';
		return $out . '</p>' . self::submit_for( (int) $m['procedure_id'], (array) $a['submit_for'] );
	}








	private static function submit_for( int $procedure_id, array $s ): string {
		if ( empty( $s['can'] ) ) {
			return '';
		}
		$op = ApplicationActionsPanel::OP_SUBMIT_FOR;
		ob_start();
		?>
		<details class="prc-en-nombre"<?php echo empty( $s['open'] ) ? '' : ' open'; ?>>
			<summary class="prc-revision__abrir">Presentar en nombre de un centro</summary>
			<p class="prc-campo__ayuda">Para un centro que no pudo entrar a tiempo: se crea su solicitud vacía, en «Presentada», y el centro la completa con su formulario de siempre hasta el día que ponga aquí. El centro no recibe ningún correo: avísele por su cuenta.</p>
			<form class="prc-filtros" method="get" action="<?php echo esc_url( (string) $s['action'] ); ?>" role="search" aria-label="Buscar un centro en el catálogo">
				<?php echo ApplicationsPanelView::carry( (array) $s['hidden'] );?>
				<div class="prc-filtros__campo">
					<label class="prc-campo__etiqueta" for="prc-buscar-centro">Buscar un centro por código o nombre</label>
					<input class="prc-control" type="search" id="prc-buscar-centro" name="<?php echo esc_attr( ApplicationActionsPanel::ARG_FIND ); ?>" value="<?php echo esc_attr( (string) $s['search'] ); ?>" maxlength="100" />
				</div>
				<div class="prc-filtros__acciones"><button class="<?php echo esc_attr( Assets::button_class() ); ?>" type="submit">Buscar</button></div>
			</form>
			<?php if ( '' !== (string) $s['search'] ) : ?>
				<?php if ( array() === (array) $s['results'] ) : ?>
					<p class="prc-vacio">Ningún centro del catálogo tiene ese código o ese nombre.</p>
				<?php else : ?>
					<ul class="prc-centros-encontrados">
						<?php foreach ( (array) $s['results'] as $centro ) : ?>
							<li><a href="<?php echo esc_url( add_query_arg( ApplicationActionsPanel::ARG_PICK, rawurlencode( (string) $centro['code'] ), (string) $s['pick_url'] ) ); ?>"><?php echo esc_html( (string) $centro['name'] . ' (' . (string) $centro['code'] . ')' ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			<?php endif; ?>
			<form class="prc-form prc-form--taller" method="post" action="" data-prc-once>
				<?php wp_nonce_field( ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op ), false ); ?>
				<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
				<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) $procedure_id ); ?>" />
				<div class="prc-campo">
					<label class="prc-campo__etiqueta" for="prc-en-nombre-codigo">Código del centro</label>
					<input class="prc-control" type="text" id="prc-en-nombre-codigo" name="<?php echo esc_attr( ApplicationActionsPanel::FIELD_CODE ); ?>" value="<?php echo esc_attr( (string) $s['code'] ); ?>" required maxlength="20" autocomplete="off" />
				</div>
				<div class="prc-campo">
					<label class="prc-campo__etiqueta" for="prc-en-nombre-hasta">Puede completarla hasta el</label>
					<input class="prc-control" type="date" id="prc-en-nombre-hasta" name="<?php echo esc_attr( ApplicationActionsPanel::FIELD_LATE ); ?>" value="<?php echo esc_attr( (string) $s['late'] ); ?>" min="<?php echo esc_attr( (string) $s['today'] ); ?>" required />
				</div>
				<label class="prc-casilla">
					<input type="checkbox" name="<?php echo esc_attr( ApplicationActionsPanel::FIELD_OVERRIDE ); ?>" value="1" <?php checked( ! empty( $s['override'] ) ); ?> />
					Aunque no cumpla los requisitos del procedimiento (queda anotado en su historial)
				</label>
				<?php echo PanelParts::submit( 'Presentar en su nombre' );?>
			</form>
		</details>
		<?php
		return (string) ob_get_clean();
	}







	public static function withdrawn( array $m ): string {
		$a     = (array) ( $m['actions'] ?? array() );
		$filas = (array) ( $a['withdrawn'] ?? array() );
		if ( array() === $filas ) {
			return '<section class="prc-seccion-campos"><h2 class="prc-seccion__titulo">Retiradas</h2><p class="prc-vacio">No hay solicitudes retiradas en este procedimiento.</p></section>';
		}
		$op = ApplicationActionsPanel::OP_RESTORE;
		ob_start();
		?>
		<section class="prc-seccion-campos">
			<h2 class="prc-seccion__titulo">Retiradas</h2>
			<p class="prc-campo__ayuda">Solicitudes en la papelera: ya no cuentan, no salen en la consulta ni en las exportaciones. Se restauran como estaban, salvo que el centro haya presentado otra entretanto.</p>
			<div class="prc-tabla-caja">
				<table class="prc-tabla prc-tabla--densa table">
					<caption class="screen-reader-text">Solicitudes retiradas de este procedimiento</caption>
					<thead><tr><th class="prc-th" scope="col">Solicitud</th><th class="prc-th" scope="col">Retirada</th><th class="prc-th" scope="col">Por</th><th class="prc-th" scope="col">Motivo</th><th class="prc-th" scope="col">Acciones</th></tr></thead>
					<tbody>
						<?php foreach ( $filas as $fila ) : ?>
							<tr>
								<td class="prc-celda" data-rotulo="Solicitud"><?php echo esc_html( (string) $fila['who'] ); ?></td>
								<td class="prc-celda" data-rotulo="Retirada"><?php echo esc_html( (string) $fila['when'] ); ?></td>
								<td class="prc-celda" data-rotulo="Por"><?php echo esc_html( (string) $fila['by'] ); ?></td>
								<td class="prc-celda" data-rotulo="Motivo"><?php echo esc_html( '' !== (string) $fila['reason'] ? (string) $fila['reason'] : '—' ); ?></td>
								<td class="prc-celda" data-rotulo="Acciones">
									<?php if ( ! empty( $fila['can'] ) ) : ?>
										<form class="prc-acciones" method="post" action="">
											<?php wp_nonce_field( ProcedureEditor::nonce_action( $op, (int) $fila['id'] ), ProcedureEditor::nonce_name( $op, (int) $fila['id'] ), false ); ?>
											<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
											<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
											<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_ROW ); ?>" value="<?php echo esc_attr( (string) (int) $fila['id'] ); ?>" />
											<input type="hidden" name="<?php echo esc_attr( ApplicationActionsPanel::ARG_WITHDRAWN ); ?>" value="1" />
											<button class="<?php echo esc_attr( Assets::button_class() ); ?>" type="submit">Restaurar</button>
										</form>
									<?php else : ?>
										<span class="prc-campo__ayuda"><?php echo esc_html( (string) $fila['why'] ); ?></span>
									<?php endif; ?>
									<?php echo self::entries( (array) $fila['history'], (int) $fila['id'] );?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}










	public static function internal_note( array $m, int $id ): string {
		$fila = (array) ( $m['actions']['rows'][ $id ] ?? array() );
		return '<div class="prc-campo">'
			. '<label class="prc-campo__etiqueta" for="prc-nota-interna-' . esc_attr( (string) $id ) . '">Nota interna</label>'
			. '<textarea class="prc-control" id="prc-nota-interna-' . esc_attr( (string) $id ) . '" name="' . esc_attr( ApplicationActionsPanel::FIELD_INTERNAL_NOTE ) . '" rows="2" aria-describedby="prc-nota-interna-ayuda-' . esc_attr( (string) $id ) . '">'
			. esc_textarea( (string) ( $fila['internal_note'] ?? '' ) ) . '</textarea>'
			. '<p class="prc-campo__ayuda" id="prc-nota-interna-ayuda-' . esc_attr( (string) $id ) . '">Solo la ven quienes revisan: ni el centro, ni quien consulta, ni ningún correo. No sale en la exportación salvo que se elija su columna.</p>'
			. '</div>';
	}








	public static function tools( array $m, array $fila ): string {
		$id = (int) $fila['id'];
		$a  = (array) ( $m['actions']['rows'][ $id ] ?? array() );
		if ( array() === $a ) {
			return '';
		}
		$quien = '' !== (string) ( $fila['centre'] ?? '' ) ? (string) $fila['centre'] : (string) ( $fila['applicant'] ?? '' );
		$out   = ! empty( $a['pending'] ) ? '<p class="prc-campo__ayuda">Presentada en nombre del centro: todavía no la ha completado.</p>' : '';
		if ( ! empty( $a['can_extend'] ) ) {
			$out .= self::extend_form( $m, $id, $a ) . self::retire_form( $m, $id, $quien );
		}
		return $out . self::history( (array) $a['history'], $id );
	}









	private static function extend_form( array $m, int $id, array $a ): string {
		$op    = ApplicationActionsPanel::OP_EXTEND;
		$hasta = (string) $a['late_until'];
		ob_start();
		?>
		<form class="prc-form prc-form--taller prc-prorroga" method="post" action="">
			<?php echo self::hidden( $m, $op, $id );?>
			<p class="prc-campo__ayuda">
				<?php
				echo esc_html(
					'' === $hasta
						? 'Prórroga: deja a este centro editar su solicitud entera hasta el día que ponga, incluido, aunque el plazo general haya cerrado. No afecta a las demás.'
						: ( ! empty( $a['late_open'] ) ? 'Tiene prórroga hasta el ' . (string) $a['late_label'] . ', incluido.' : 'Su prórroga venció el ' . (string) $a['late_label'] . '.' )
				);
				?>
			</p>
			<?php foreach ( self::extend_warnings( $m, $a ) as $aviso ) : ?>
				<p class="prc-campo__ayuda prc-aviso-prorroga"><?php echo esc_html( $aviso ); ?></p>
			<?php endforeach; ?>
			<div class="prc-campo">
				<label class="prc-campo__etiqueta" for="prc-prorroga-<?php echo esc_attr( (string) $id ); ?>">Puede editarla hasta el</label>
				<input class="prc-control" type="date" id="prc-prorroga-<?php echo esc_attr( (string) $id ); ?>" name="<?php echo esc_attr( ApplicationActionsPanel::FIELD_LATE ); ?>" value="<?php echo esc_attr( $hasta ); ?>" min="<?php echo esc_attr( (string) ( $m['actions']['today'] ?? '' ) ); ?>" required />
			</div>
			<p class="prc-acciones">
				<button class="<?php echo esc_attr( Assets::button_class() ); ?>" type="submit">Guardar la prórroga</button>
				<?php if ( '' !== $hasta ) : ?>
					<button class="<?php echo esc_attr( Assets::button_class() ); ?>" type="submit" name="<?php echo esc_attr( ApplicationActionsPanel::FIELD_LATE_REMOVE ); ?>" value="1" formnovalidate>Quitar la prórroga</button>
				<?php endif; ?>
			</p>
		</form>
		<?php
		return (string) ob_get_clean();
	}











	private static function extend_warnings( array $m, array $a ): array {
		$out = array();
		if ( in_array( (string) ( $a['review'] ?? '' ), array( ApplicationMetaKeys::REVIEW_ADMITTED, ApplicationMetaKeys::REVIEW_EXCLUDED ), true ) ) {
			$out[] = 'Esta solicitud ya está revisada: si el centro la modifica con la prórroga, volverá a «Presentada» y habrá que revisarla de nuevo.';
		}
		if ( ! empty( $m['actions']['resolved'] ) ) {
			$out[] = 'Este procedimiento ya está resuelto: una prórroga deja cambiar una solicitud después de la resolución.';
		}
		return $out;
	}









	private static function retire_form( array $m, int $id, string $quien ): string {
		$op       = ApplicationActionsPanel::OP_RETIRE;
		$pregunta = '¿Retirar la solicitud de ' . $quien . '? Pasará a «Retiradas»: dejará de contar, de salir en la consulta y en las exportaciones. Se puede restaurar mientras el centro no presente otra.';
		ob_start();
		?>
		<details class="prc-retirar">
			<summary class="prc-revision__abrir">Retirar esta solicitud</summary>
			<form class="prc-form prc-form--taller" method="post" action=""
				data-prc-confirm="<?php echo esc_attr( $pregunta ); ?>" data-prc-confirm-ok="Retirar la solicitud" data-prc-once>
				<?php echo self::hidden( $m, $op, $id );?>
				<div class="prc-campo">
					<label class="prc-campo__etiqueta" for="prc-motivo-<?php echo esc_attr( (string) $id ); ?>">Motivo</label>
					<textarea class="prc-control" id="prc-motivo-<?php echo esc_attr( (string) $id ); ?>" name="<?php echo esc_attr( ApplicationActionsPanel::FIELD_REASON ); ?>" rows="2" required></textarea>
					<p class="prc-campo__ayuda">Obligatorio: queda en el historial de la solicitud. Úselo para lo que se presentó por error; para decir que no se admite, está «Excluida».</p>
				</div>
				<p class="prc-acciones"><button class="<?php echo esc_attr( Assets::button_class( 'danger' ) ); ?>" type="submit">Retirar la solicitud</button></p>
			</form>
		</details>
		<?php
		return (string) ob_get_clean();
	}









	private static function hidden( array $m, string $op, int $id ): string {
		return wp_nonce_field( ProcedureEditor::nonce_action( $op, $id ), ProcedureEditor::nonce_name( $op, $id ), false, false )
			. '<input type="hidden" name="' . esc_attr( ProcedureEditor::FIELD_DO ) . '" value="' . esc_attr( $op ) . '" />'
			. '<input type="hidden" name="' . esc_attr( ProcedureEditor::FIELD_PROCEDURE ) . '" value="' . esc_attr( (string) (int) $m['procedure_id'] ) . '" />'
			. '<input type="hidden" name="' . esc_attr( ProcedureEditor::FIELD_ROW ) . '" value="' . esc_attr( (string) $id ) . '" />'
			. ApplicationsPanelView::carry( (array) ( $m['table']['args'] ?? array() ) );
	}








	public static function history( array $h, int $id ): string {
		if ( empty( $h['open'] ) ) {
			return '<p class="prc-historial-enlace"><a href="' . esc_url( (string) ( $h['url'] ?? '' ) ) . '">Ver el historial</a></p>';
		}
		return self::entries( (array) ( $h['entries'] ?? array() ), $id );
	}








	private static function entries( array $entries, int $id ): string {
		$out = '<details class="prc-historial-caja" id="prc-historial-' . esc_attr( (string) $id ) . '" open><summary class="prc-revision__abrir">Historial</summary>';
		if ( array() === $entries ) {
			return $out . '<p class="prc-vacio">Esta solicitud todavía no tiene historial.</p></details>';
		}
		$out .= '<ol class="prc-historial">';
		foreach ( $entries as $entrada ) {
			$out .= '<li>' . esc_html( implode( ' · ', array_filter( array( $entrada['when'], $entrada['who'], $entrada['what'] ), 'strlen' ) ) );
			$out .= '' !== $entrada['before'] ? '<br />' . esc_html( 'Antes: ' . $entrada['before'] ) : '';
			$out .= '' !== $entrada['after'] ? '<br />' . esc_html( 'Después: ' . $entrada['after'] ) : '';
			$out .= '</li>';
		}
		return $out . '</ol></details>';
	}
}

}









namespace Prc\PublicFront\View;

use Prc\Domain\ApplicationFilter;
use Prc\Domain\ProcedureQuestions;
use Prc\PublicFront\ApplicationFiles;
use Prc\PublicFront\Applications;
use Prc\PublicFront\ApplicationsPanel;
use Prc\PublicFront\Assets;
use Prc\PublicFront\ProcedureEditor;










if ( ! class_exists( 'Prc\PublicFront\View\ApplicationsPanelView', false ) ) {
final class ApplicationsPanelView {


	public const BULK_FORM = 'prc-bloque';







	public static function html( array $m ): string {
		$t       = (array) ( $m['table'] ?? array() );
		$docente = ! empty( $t['teachers'] );


		$arriba = ApplicationActionsView::bar( $m );
		if ( ! empty( $m['actions']['withdrawn_open'] ) ) {
			return $arriba . ApplicationActionsView::withdrawn( $m );
		}
		if ( 0 === (int) ( $t['all'] ?? 0 ) ) {
			return $arriba . '<section class="prc-seccion-campos"><h2 class="prc-seccion__titulo">Todavía no hay solicitudes</h2>'
				. '<p class="prc-vacio">Cuando ' . ( $docente ? 'un docente' : 'un centro' ) . ' solicite saldrá en esta tabla, y desde ella podrá revisar la solicitud y exportar la lista a CSV.</p></section>';
		}
		$cerrado = true !== $m['can_review'];

		ob_start();
		echo $arriba;
		?>
		<div class="prc-banda-tabla">
			<p class="prc-recuento"><?php echo esc_html( self::count_text( $t ) ); ?></p>
			<?php echo $cerrado ? '<fieldset class="prc-solo-lectura" disabled><legend class="screen-reader-text">Solo lectura</legend>' : ''; ?>
			<?php echo ExportDialogView::html( $m );?>
			<?php echo $cerrado ? '</fieldset>' : ''; ?>
		</div>
		<?php
		echo self::states( $t );
		echo self::filters( $m, $t );
		if ( 0 === (int) $t['total'] ) {
			echo '<p class="prc-vacio">Ninguna solicitud cumple el filtro.';
			echo '' !== (string) $t['reset_url'] ? ' <a href="' . esc_url( (string) $t['reset_url'] ) . '">Quitar los filtros</a>' : '';
			echo '</p>';
			return (string) ob_get_clean();
		}
		echo $cerrado ? '<fieldset class="prc-solo-lectura" disabled><legend class="screen-reader-text">Solo lectura</legend>' : '';
		echo self::bulk_form( $m, $t );
		echo self::table( $m, $docente );
		echo $cerrado ? '</fieldset>' : '';
		echo self::pager( $t );
		return (string) ob_get_clean();
	}







	public static function carry( array $args ): string {
		$out = '';
		foreach ( $args as $nombre => $valor ) {
			$out .= '<input type="hidden" name="' . esc_attr( (string) $nombre ) . '" value="' . esc_attr( (string) $valor ) . '" />';
		}
		return $out;
	}







	private static function count_text( array $t ): string {
		$total = (int) $t['total'];
		if ( empty( $t['active'] ) && 1 === (int) $t['pages'] ) {
			return sprintf( 1 === $total ? 'Mostrando %d solicitud.' : 'Mostrando %d solicitudes.', $total )
				. ' Cada una se revisa desde su fila, o varias a la vez marcándolas; al exportar se eligen las columnas, una fila por solicitud o por persona, o el fichero de alta de usuarios.';
		}
		if ( 0 === $total ) {
			return sprintf( 'Ninguna de las %d solicitudes cumple el filtro.', (int) $t['all'] );
		}
		$texto = sprintf( 'Mostrando %1$d–%2$d de %3$d solicitudes', (int) $t['from'], (int) $t['to'], $total );
		return $texto . ( empty( $t['active'] ) ? '.' : sprintf( ' que cumplen el filtro, de %d en total.', (int) $t['all'] ) );
	}









	public static function states( array $t ): string {
		$out = '<ul class="prc-contadores" aria-label="Solicitudes por estado de revisión">';
		foreach ( (array) $t['states'] as $estado ) {
			$out .= sprintf(
				'<li><a class="prc-contadores__enlace" href="%1$s"%2$s>%3$s <span class="prc-contadores__cifra">%4$d</span></a></li>',
				esc_url( (string) $estado['url'] ),
				$estado['current'] ? ' aria-current="true"' : '',
				esc_html( (string) $estado['label'] ),
				(int) $estado['count']
			);
		}
		return $out . '</ul>';
	}








	private static function filters( array $m, array $t ): string {
		$f        = (array) $t['filter'];
		$opciones = (array) $t['options'];
		$estados  = array( '' => 'Todos los estados' ) + (array) $m['review_states'];
		ob_start();
		?>
		<form class="prc-filtros" method="get" action="<?php echo esc_url( (string) $t['action'] ); ?>" role="search" aria-label="Filtrar las solicitudes">
			<?php echo self::carry( (array) $t['hidden'] );?>
			<div class="prc-filtros__campo">
				<label class="prc-campo__etiqueta" for="prc-filtro-buscar"><?php echo esc_html( empty( $t['teachers'] ) ? 'Centro o código' : 'Nombre de la persona' ); ?></label>
				<input class="prc-control" type="search" id="prc-filtro-buscar" name="<?php echo esc_attr( ApplicationFilter::ARG_SEARCH ); ?>" value="<?php echo esc_attr( (string) $f['search'] ); ?>" maxlength="<?php echo esc_attr( (string) ApplicationFilter::MAX_SEARCH ); ?>" />
			</div>
			<?php echo self::select( 'prc-filtro-estado', ApplicationFilter::ARG_REVIEW, 'Estado de revisión', $estados, (string) $f['review'] );?>
			<?php
			if ( array() !== (array) $opciones['islands'] ) {
				echo self::select( 'prc-filtro-isla', ApplicationFilter::ARG_ISLAND, 'Isla', array( '' => 'Todas las islas' ) + (array) $opciones['islands'], (string) $f['island'] );
			}
			if ( array() !== (array) $opciones['zones'] ) {
				echo self::select( 'prc-filtro-zona', ApplicationFilter::ARG_ZONE, (string) $t['zone_label'], array( '' => 'Todas' ) + (array) $opciones['zones'], (string) $f['zone'] );
			}
			?>
			<?php if ( ! empty( $t['incomplete']['possible'] ) ) : ?>
				<label class="prc-casilla prc-filtros__casilla">
					<input type="checkbox" name="<?php echo esc_attr( ApplicationFilter::ARG_INCOMPLETE ); ?>" value="1" <?php checked( true === $f['incomplete'] ); ?> />
					<?php echo esc_html( sprintf( 'Solo las incompletas (%d)', (int) $t['incomplete']['count'] ) ); ?>
				</label>
			<?php endif; ?>
			<div class="prc-filtros__acciones">
				<button class="<?php echo esc_attr( Assets::button_class() ); ?>" type="submit">Filtrar</button>
				<?php if ( '' !== (string) $t['reset_url'] ) : ?>
					<a class="prc-filtros__quitar" href="<?php echo esc_url( (string) $t['reset_url'] ); ?>">Quitar los filtros</a>
				<?php endif; ?>
			</div>
		</form>
		<?php
		return (string) ob_get_clean();
	}











	public static function select( string $id, string $name, string $label, array $options, string $selected ): string {
		$out = '<div class="prc-filtros__campo"><label class="prc-campo__etiqueta" for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label>'
			. '<select class="prc-control" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
		foreach ( $options as $valor => $rotulo ) {
			$out .= '<option value="' . esc_attr( (string) $valor ) . '"' . selected( (string) $valor, $selected, false ) . '>' . esc_html( (string) $rotulo ) . '</option>';
		}
		return $out . '</select></div>';
	}













	private static function bulk_form( array $m, array $t ): string {
		$op     = ProcedureEditor::OP_BULK_REVIEW;
		$pagina = array_map( 'intval', (array) $t['page_ids'] );
		ob_start();
		?>
		<details class="prc-bloque">
			<summary class="prc-revision__abrir">Revisar varias a la vez</summary>
			<form class="prc-form prc-form--taller prc-bloque__form" id="<?php echo esc_attr( self::BULK_FORM ); ?>" method="post" action=""
				data-prc-confirm="¿Cambiar la revisión de las solicitudes marcadas? Cada una recibe el mismo estado y la misma nota, y a cada centro le llega su aviso por correo."
				data-prc-confirm-ok="Cambiar la revisión">
				<?php wp_nonce_field( ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op ), false ); ?>
				<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
				<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
				<input type="hidden" name="<?php echo esc_attr( ApplicationsPanel::FIELD_PAGE_ROWS ); ?>" value="<?php echo esc_attr( implode( ',', $pagina ) ); ?>" />
				<?php echo self::carry( (array) $t['args'] );?>
				<p class="prc-campo__ayuda">Marque las solicitudes en la primera columna de la tabla, o todas las de esta página. Como mucho <?php echo esc_html( (string) (int) $t['bulk_max'] ); ?> a la vez: a cada centro le sale su correo en el mismo envío, así que con muchas tarda un poco.</p>
				<label class="prc-casilla">
					<input type="checkbox" name="<?php echo esc_attr( ApplicationsPanel::FIELD_PAGE ); ?>" value="1" data-prc-bulk-page />
					<?php echo esc_html( sprintf( 'Marcar las de esta página (%d)', count( $pagina ) ) ); ?>
				</label>
				<div class="prc-campo">
					<label class="prc-campo__etiqueta" for="prc-bloque-estado">Estado</label>
					<select class="prc-control" id="prc-bloque-estado" name="<?php echo esc_attr( ProcedureEditor::FIELD_STATE ); ?>">
						<?php foreach ( (array) $m['review_states'] as $clave => $rotulo ) : ?>
							<option value="<?php echo esc_attr( (string) $clave ); ?>"><?php echo esc_html( (string) $rotulo ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="prc-campo">
					<label class="prc-campo__etiqueta" for="prc-bloque-nota">Nota para los centros</label>
					<textarea class="prc-control" id="prc-bloque-nota" name="<?php echo esc_attr( ProcedureEditor::FIELD_NOTE ); ?>" rows="3" aria-describedby="prc-bloque-nota-ayuda"></textarea>
					<p class="prc-campo__ayuda" id="prc-bloque-nota-ayuda">La nota se aplica a todas las marcadas y sustituye la que tuvieran. En blanco, cada una conserva la suya; al pedir subsanar y al excluir es obligatoria. Cada centro la lee en su solicitud.</p>
				</div>
				<?php echo PanelParts::submit( 'Cambiar la revisión de las marcadas' );?>
			</form>
		</details>
		<?php
		return (string) ob_get_clean();
	}








	private static function table( array $m, bool $docente ): string {
		ob_start();
		?>
		<div class="prc-tabla-caja prc-panel-solicitudes">
			<table class="prc-tabla prc-tabla--densa table">
				<caption class="screen-reader-text">Solicitudes presentadas a este procedimiento</caption>
				<thead>
					<tr>
						<th class="prc-th prc-th--marca" scope="col"><span class="screen-reader-text">Marcar para revisar en bloque</span></th>
						<?php if ( $docente ) : ?>
							<th class="prc-th" scope="col">Docente</th>
							<th class="prc-th" scope="col">Correo</th>
						<?php else : ?>
							<th class="prc-th" scope="col">Centro</th>
							<th class="prc-th" scope="col">Código</th>
						<?php endif; ?>
						<th class="prc-th" scope="col">Fecha</th>
						<?php if ( ! $docente ) : ?>
							<th class="prc-th" scope="col">Cargo</th>
							<th class="prc-th" scope="col">Coordinación</th>
						<?php endif; ?>
						<?php echo self::score_cell( $m );?>
						<th class="prc-th" scope="col">Revisión</th>
						<th class="prc-th" scope="col">Acciones</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( (array) $m['applications'] as $fila ) : ?>
						<?php $quien = $docente ? (string) $fila['applicant'] : ( '' !== (string) $fila['centre'] ? (string) $fila['centre'] : (string) $fila['code'] ); ?>
						<tr data-estado="<?php echo esc_attr( (string) $fila['state_key'] ); ?>"<?php echo array() !== (array) ( $fila['reasons'] ?? array() ) ? ' data-incompleta' : ''; ?>>
							<td class="prc-celda prc-celda--marca" data-rotulo="Marcar">
								<input type="checkbox" form="<?php echo esc_attr( self::BULK_FORM ); ?>" name="<?php echo esc_attr( ApplicationsPanel::FIELD_ROWS ); ?>[]" value="<?php echo esc_attr( (string) (int) $fila['id'] ); ?>" data-prc-bulk-row
									aria-label="<?php echo esc_attr( 'Marcar la solicitud de ' . $quien ); ?>" />
							</td>
							<?php if ( $docente ) : ?>
								<td class="prc-celda" data-rotulo="Docente"><?php echo esc_html( (string) $fila['applicant'] ); ?></td>
								<td class="prc-celda" data-rotulo="Correo"><?php echo esc_html( (string) $fila['email'] ); ?></td>
							<?php else : ?>
								<td class="prc-celda" data-rotulo="Centro"><?php echo esc_html( '' !== (string) $fila['centre'] ? (string) $fila['centre'] : '(centro fuera del catálogo)' ); ?></td>
								<td class="prc-celda" data-rotulo="Código"><?php echo esc_html( (string) $fila['code'] ); ?></td>
							<?php endif; ?>
							<td class="prc-celda" data-rotulo="Fecha"><?php echo esc_html( (string) $fila['date_label'] ); ?></td>
							<?php if ( ! $docente ) : ?>
								<td class="prc-celda" data-rotulo="Cargo"><?php echo esc_html( (string) $fila['position'] ); ?></td>
								<td class="prc-celda" data-rotulo="Coordinación"><?php echo self::dash( trim( (string) $fila['coordinator'] . ' ' . (string) ( $fila['coordinator_dni'] ?? '' ) . ' ' . (string) $fila['coordinator_email'] ) );?></td>
							<?php endif; ?>
							<?php echo self::score_cell( $m, $fila );?>
							<td class="prc-celda" data-rotulo="Revisión">
								<?php echo ProcedureEditorView::badge( (string) $fila['state_key'], (string) $fila['state'] );?>
								<?php echo self::reasons( (array) ( $fila['reasons'] ?? array() ) );?>
							</td>
							<td class="prc-celda" data-rotulo="Acciones"><?php echo self::review_form( $m, $fila );?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		return (string) ob_get_clean();
	}







	private static function reasons( array $reasons ): string {
		if ( array() === $reasons ) {
			return '';
		}
		$out = '<div class="prc-incompleta"><span class="prc-incompleta__titulo">Incompleta</span><ul class="prc-incompleta__motivos">';
		foreach ( $reasons as $motivo ) {
			$out .= '<li>' . esc_html( (string) $motivo ) . '</li>';
		}
		return $out . '</ul></div>';
	}







	public static function pager( array $t ): string {
		if ( (int) $t['pages'] <= 1 ) {
			return '';
		}
		$p   = (array) $t['pager'];
		$out = '<nav class="prc-paginacion" aria-label="Páginas de solicitudes"><ul class="prc-paginacion__lista">';
		if ( '' !== (string) $p['prev'] ) {
			$out .= '<li><a class="prc-paginacion__enlace" href="' . esc_url( (string) $p['prev'] ) . '" rel="prev">Anterior</a></li>';
		}
		foreach ( (array) $p['pages'] as $una ) {
			if ( 0 === (int) $una['n'] ) {
				$out .= '<li class="prc-paginacion__hueco" aria-hidden="true">…</li>';
			} elseif ( (int) $una['n'] === (int) $t['page'] ) {
				$out .= '<li><span class="prc-paginacion__actual" aria-current="page">' . (int) $una['n'] . '</span></li>';
			} else {
				$out .= '<li><a class="prc-paginacion__enlace" href="' . esc_url( (string) $una['url'] ) . '">' . (int) $una['n'] . '</a></li>';
			}
		}
		if ( '' !== (string) $p['next'] ) {
			$out .= '<li><a class="prc-paginacion__enlace" href="' . esc_url( (string) $p['next'] ) . '" rel="next">Siguiente</a></li>';
		}
		return $out . '</ul></nav>';
	}











	private static function score_cell( array $m, ?array $fila = null ): string {
		if ( ! isset( $m['app_cols']['score'] ) ) {
			return '';
		}
		if ( null === $fila ) {
			return '<th class="prc-th" scope="col">Puntos</th>';
		}
		return '<td class="prc-celda" data-rotulo="Puntos">' . esc_html( (string) ( $fila['score'] ?? '' ) ) . '</td>';
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
		<details class="prc-revision"<?php echo empty( $m['actions']['rows'][ $id ]['history']['open'] ) ? '' : ' open'; ?>>
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
					<dd><?php echo $respuesta;?></dd>
				<?php endforeach; ?>
			</dl>
			<?php echo PeopleView::review( (array) ( $m['people'][ $id ] ?? array() ) );?>
			<form class="prc-form prc-form--taller prc-revision__form" method="post" action="">
				<?php wp_nonce_field( ProcedureEditor::nonce_action( $op, $id ), ProcedureEditor::nonce_name( $op, $id ), false ); ?>
				<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
				<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
				<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_ROW ); ?>" value="<?php echo esc_attr( (string) $id ); ?>" />
				<?php echo self::carry( (array) ( $m['table']['args'] ?? array() ) );?>
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
				<?php echo ApplicationActionsView::internal_note( $m, $id );?>
				<?php echo PanelParts::submit( 'Guardar la revisión' );?>
			</form>
			<?php echo ApplicationActionsView::tools( $m, $fila );?>
		</details>
		<?php
		return (string) ob_get_clean();
	}
}

}








namespace Prc\PublicFront\View;

use Prc\Domain\CentreEligibility;
use Prc\Domain\ProcedureQuestions;
use Prc\Domain\QuestionChanges;
use Prc\Domain\QuestionPoints;
use Prc\Domain\ProcedureState;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PublicFront\Assets;
use Prc\PublicFront\ProcedureEditor;
use Prc\PublicFront\Shell;







if ( ! class_exists( 'Prc\PublicFront\View\ProcedureEditorView', false ) ) {
final class ProcedureEditorView {




	private const NEW_QUESTIONS = 3;







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

		echo Shell::notice( 'aviso', implode( ' ', (array) $m['not_ready'] ) );




		$cerrado = self::read_only( $m, $panel );
		if ( $cerrado ) {
			echo '<fieldset class="prc-solo-lectura" disabled><legend class="screen-reader-text">Procedimiento en solo lectura</legend>';
		}

		if ( ProcedureEditor::PANEL_QUESTIONS === $panel ) {
			echo self::questions_panel( $m );
		} elseif ( ProcedureEditor::PANEL_LINKS === $panel ) {
			echo self::links_panel( $m );
		} elseif ( ProcedureEditor::PANEL_APPLICATIONS === $panel ) {
			echo ApplicationsPanelView::html( $m );
		} elseif ( ProcedureEditor::PANEL_DELIVERIES === $panel ) {

			echo self::deliveries_panel( $m );
		} elseif ( ProcedureEditor::PANEL_PUBLISH === $panel ) {
			echo self::publish_panel( $m );
		} elseif ( ProcedureEditor::PANEL_NOTICES === $panel ) {
			echo self::notices_panel( $m );
		} elseif ( ProcedureEditor::PANEL_PEOPLE === $panel ) {
			echo PeopleView::spec_panel( $m );
		} elseif ( ProcedureEditor::PANEL_OBSERVE === $panel ) {
			echo ObserveView::settings( $m );
		} elseif ( ProcedureEditor::PANEL_CODE === $panel ) {

			echo self::custom_code_panel( $m );
		} else {
			echo self::data_panel( $m );
		}

		if ( $cerrado ) {
			echo '</fieldset>';
		}



		if ( ProcedureEditor::PANEL_PUBLISH === $panel ) {
			echo self::archive_switch( $m );
		}
		return Shell::render( '', '', (string) ob_get_clean() );
	}













	private static function read_only( array $m, string $panel ): bool {
		if ( ProcedureEditor::PANEL_APPLICATIONS === $panel ) {


			return false;
		}
		if ( ProcedureEditor::PANEL_DELIVERIES === $panel ) {
			return true !== $m['can_edit'];
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







	private static function deliveries_panel( array $m ): string {
		ob_start();
		?>
		<section aria-labelledby="prc-deliveries-title">
			<h2 id="prc-deliveries-title">Plazos de entregas documentales</h2>
			<p>Puede ampliar el cierre sin reabrir la inscripción. El inicio y las preguntas se conservan; el nuevo cierre no puede ser anterior al actual.</p>
			<?php if ( empty( $m['deliveries'] ) ) : ?>
				<p>Este procedimiento no tiene entregas con plazo propio.</p>
			<?php else : ?>
				<form class="prc-form" method="post" action="">
					<?php wp_nonce_field( ProcedureEditor::nonce_action( ProcedureEditor::OP_DELIVERY_DATES ), ProcedureEditor::nonce_name( ProcedureEditor::OP_DELIVERY_DATES ), false ); ?>
					<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( ProcedureEditor::OP_DELIVERY_DATES ); ?>" />
					<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
					<?php foreach ( $m['deliveries'] as $delivery ) : ?>
						<div class="prc-campo">
							<label class="prc-campo__etiqueta" for="prc-delivery-close-<?php echo esc_attr( (string) $delivery['key'] ); ?>"><?php echo esc_html( (string) $delivery['label'] ); ?> — cierre actual: <?php echo esc_html( (string) $delivery['closes_at'] ); ?></label>
							<input class="prc-control" type="date" id="prc-delivery-close-<?php echo esc_attr( (string) $delivery['key'] ); ?>" name="<?php echo esc_attr( ProcedureEditor::FIELD_DELIVERY_CLOSES . '[' . $delivery['key'] . ']' ); ?>" min="<?php echo esc_attr( (string) $delivery['closes_at'] ); ?>" value="<?php echo esc_attr( (string) $delivery['closes_at'] ); ?>" required />
						</div>
					<?php endforeach; ?>
					<?php echo PanelParts::submit( 'Guardar los nuevos cierres' );?>
				</form>
			<?php endif; ?>
		</section>
		<?php
		return (string) ob_get_clean();
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
		echo PanelParts::help_link( (string) ( $m['help_new_url'] ?? '' ) );
		echo self::flash( $m );
		echo self::data_panel( $m );
		return Shell::render( '', '', (string) ob_get_clean() );
	}







	private static function flash( array $m ): string {
		$flash = (array) $m['flash'];
		return Shell::notice( (string) $flash['tipo'], (string) $flash['texto'], true );
	}







	private static function head( array $m ): string {
		ob_start();
		?>
		<p class="prc-sub"><a href="<?php echo esc_url( (string) $m['workspace_url'] ); ?>">&larr; Mis procedimientos</a></p>
		<div class="prc-h1-fila">
			<h1 class="prc-h1"><?php echo esc_html( '' !== (string) $m['title'] ? (string) $m['title'] : 'Procedimiento sin título' ); ?></h1>
			<?php echo self::badge( (string) $m['state'], (string) $m['state_label'] );?>
			<?php echo self::badge( (string) $m['status'], (string) $m['status_label'] );?>
			<?php echo self::badge( 'audience', (string) ( $m['audience_label'] ?? '' ) );?>
			<span class="prc-acciones">
				<?php echo PanelParts::icon_link( (string) $m['view_url'], 'ojo', 'Ver la ficha pública' );?>
				<?php echo self::duplicate_form( $m );?>
			</span>
		</div>
		<p class="prc-sub"><?php echo esc_html( 'Ámbito: ' . (string) $m['area_names'] ); ?></p>
		<?php
		return (string) ob_get_clean();
	}










	private static function duplicate_form( array $m ): string {
		if ( true !== $m['can_duplicate'] ) {
			return '';
		}
		$op = ProcedureEditor::OP_DUPLICATE;
		ob_start();
		?>
		<form class="prc-acciones" method="post" action=""
			data-prc-confirm="<?php echo esc_attr( self::duplicate_question( (string) $m['title'] ) ); ?>"
			data-prc-confirm-ok="Duplicar" data-prc-once>
			<?php wp_nonce_field( ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
			<button type="submit" class="<?php echo esc_attr( Assets::button_class() . ' prc-mini' ); ?>">Duplicar</button>
		</form>
		<?php
		return (string) ob_get_clean();
	}







	public static function duplicate_question( string $title ): string {
		return sprintf(
			'¿Duplicar «%s»? Se creará un borrador nuevo con sus datos, sus preguntas y su configuración, sin plazos, sin curso y sin solicitudes.',
			'' !== $title ? $title : 'Procedimiento sin título'
		);
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
			$secciones .= PanelParts::section( 'Qué centros pueden solicitar', self::eligibility_fields( $m, $v ), $apagar, $motivo );
		}

		ob_start();
		?>
		<form class="prc-form prc-form--taller<?php echo $nuevo ? ' prc-form--alta' : ''; ?>" method="post" action="">
			<?php wp_nonce_field( ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
			<?php echo $secciones;?>
			<?php echo PanelParts::submit( $nuevo ? 'Crear el procedimiento' : 'Guardar los datos' );?>
		</form>
		<?php
		return (string) ob_get_clean();
	}










	private static function general_fields( array $m, array $v, array $listas, bool $nuevo ): string {
		$clases      = (array) $v[ ProcedureMetaKeys::CENTRE_KINDS ];
		$modo        = in_array( ProcedureMetaKeys::KIND_SCHOOL, $clases, true ) && in_array( ProcedureMetaKeys::KIND_RURAL_GROUP, $clases, true )
			? 'both'
			: ( in_array( ProcedureMetaKeys::KIND_RURAL_GROUP, $clases, true ) ? ProcedureMetaKeys::KIND_RURAL_GROUP : ProcedureMetaKeys::KIND_SCHOOL );
		$modalidades = array(
			ProcedureMetaKeys::KIND_SCHOOL      => 'Cada centro, con su código',
			ProcedureMetaKeys::KIND_RURAL_GROUP => 'El CER como colectivo, con su código',
			'both'                              => 'Ambos',
		);



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
				<label class="prc-campo__etiqueta" for="prc-title">Título del procedimiento <?php echo self::obl();?></label>
				<input class="prc-control" type="text" id="prc-title" name="<?php echo esc_attr( ProcedureEditor::FIELD_TITLE ); ?>"
					maxlength="120" required aria-describedby="prc-title-ayuda"
					value="<?php echo esc_attr( (string) $v[ ProcedureEditor::FIELD_TITLE ] ); ?>" />
				<p class="prc-campo__ayuda" id="prc-title-ayuda">Máximo 120 caracteres. El curso no se escribe aquí: se elige al lado.</p>
			</div>
			<div class="prc-campo prc-campo--cuarto">
				<?php echo $sel_course;?>
			</div>
			<div class="prc-campo prc-campo--cuarto">
				<label class="prc-campo__etiqueta" for="prc-audience">Quién solicita <?php echo self::obl();?></label>
				<select class="prc-control" id="prc-audience" name="<?php echo esc_attr( ProcedureMetaKeys::AUDIENCE ); ?>" required aria-describedby="prc-audience-ayuda">
					<?php foreach ( (array) $m['audiences'] as $clave => $rotulo ) : ?>
						<option value="<?php echo esc_attr( (string) $clave ); ?>" <?php selected( (string) $clave, (string) $v[ ProcedureMetaKeys::AUDIENCE ] ); ?>><?php echo esc_html( (string) $rotulo ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="prc-campo__ayuda" id="prc-audience-ayuda">
					<span data-prc-schools-only <?php echo ProcedureMetaKeys::AUDIENCE_SCHOOLS === $v[ ProcedureMetaKeys::AUDIENCE ] ? '' : 'hidden'; ?>>Solicita la dirección del centro con su cuenta personal, no con la cuenta genérica del centro, y con el cargo de dirección en su perfil.</span>
					<span class="prc-solo-docentes">Cada docente presenta su propia solicitud con su cuenta personal.</span>
				</p>
			</div>
			<div class="prc-campo prc-campo--medio" data-prc-schools-only <?php echo ProcedureMetaKeys::AUDIENCE_SCHOOLS === $v[ ProcedureMetaKeys::AUDIENCE ] ? '' : 'hidden'; ?>>
				<fieldset>
					<legend class="prc-campo__etiqueta">Dirigido a <?php echo self::obl();?></legend>
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
					<p class="prc-campo__ayuda">Marque al menos una. Un centro de otra titularidad no podrá solicitar, y la ficha le dirá por qué.</p>
					<?php echo self::centre_warning( $m, 'ownership' );?>
				</fieldset>
			</div>
			<div class="prc-campo prc-campo--medio" data-prc-schools-only <?php echo ProcedureMetaKeys::AUDIENCE_SCHOOLS === $v[ ProcedureMetaKeys::AUDIENCE ] ? '' : 'hidden'; ?>>
				<fieldset aria-describedby="prc-centre-kind-mode-help">
					<legend class="prc-campo__etiqueta">Modalidad para centros CER <?php echo self::obl();?></legend>
					<div class="prc-opciones prc-opciones--vertical">
						<?php foreach ( $modalidades as $clave => $rotulo ) : ?>
							<div class="prc-opcion">
								<input type="radio" id="prc-centre-kind-<?php echo esc_attr( $clave ); ?>"
									name="<?php echo esc_attr( ProcedureMetaKeys::CENTRE_KINDS ); ?>" value="<?php echo esc_attr( $clave ); ?>"
									required <?php checked( $modo, $clave ); ?> />
								<label for="prc-centre-kind-<?php echo esc_attr( $clave ); ?>"><?php echo esc_html( $rotulo ); ?></label>
							</div>
						<?php endforeach; ?>
					</div>
					<p class="prc-campo__ayuda" id="prc-centre-kind-mode-help">Si elige «Ambos», quien dirige un centro y coordina un CER podrá solicitar como centro, como CER o de las dos formas. Cada solicitud se presenta por separado.</p>
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
				<legend class="prc-campo__etiqueta">Ámbito convocante <?php echo self::obl();?></legend>
				<?php if ( array() === $arbol ) : ?>
					<p class="prc-vacio">No tiene ningún ámbito asignado. Pídalo a quien administre el aplicativo.</p>
				<?php else : ?>
					<ul class="prc-arbol">
						<?php foreach ( $arbol as $nodo ) : ?>
							<?php echo self::area_node( (array) $nodo, $elegidos );?>
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
						<?php echo self::area_node( (array) $hijo, $elegidos );?>
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
				<legend class="prc-campo__etiqueta">Color de cabecera <?php echo self::obl();?></legend>
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
		$numero = (int) $v[ ProcedureMetaKeys::COORDINATOR_COUNT ];
		$tipo   = 0 === $numero ? 'none' : ( 1 === $numero ? 'one' : 'several' );
		$varias = max( 2, $numero );

		ob_start();
		?>
		<fieldset class="prc-campo">
			<legend class="prc-campo__etiqueta">¿Pide la solicitud personas coordinadoras?</legend>
			<div class="prc-opciones prc-opciones--vertical">
				<div class="prc-opcion">
					<label>
						<input type="radio" name="<?php echo esc_attr( ProcedureEditor::FIELD_COORDINATOR_KIND ); ?>" value="none" <?php checked( 'none', $tipo ); ?> />
						No se piden
					</label>
				</div>
				<div class="prc-opcion">
					<label>
						<input type="radio" name="<?php echo esc_attr( ProcedureEditor::FIELD_COORDINATOR_KIND ); ?>" value="one" <?php checked( 'one', $tipo ); ?> />
						Sí, una persona
					</label>
				</div>
				<div class="prc-opcion prc-opcion--varias">
					<label>
						<input type="radio" name="<?php echo esc_attr( ProcedureEditor::FIELD_COORDINATOR_KIND ); ?>" value="several" <?php checked( 'several', $tipo ); ?> />
						Sí, varias personas:
					</label>
					<select class="prc-control" id="prc-coordinator-count" name="<?php echo esc_attr( ProcedureMetaKeys::COORDINATOR_COUNT ); ?>" aria-label="Número de personas coordinadoras">
						<?php for ( $count = 2; $count <= ProcedureMetaKeys::COORDINATOR_COUNT_MAX; ++$count ) : ?>
							<option value="<?php echo esc_attr( (string) $count ); ?>" <?php selected( $count, $varias ); ?>><?php echo esc_html( (string) $count ); ?></option>
						<?php endfor; ?>
					</select>
				</div>
			</div>
			<p class="prc-campo__ayuda">Lo habitual es pedir una persona coordinadora. El centro indicará su nombre y apellidos, DNI o NIE y correo. Si necesita varios papeles o ejes (coordinación general, coordinación de eje…), deje aquí «No se piden» y configúrelos en la pestaña «Personas».</p>
		</fieldset>
		<div class="prc-campo">
			<label class="prc-campo__etiqueta" for="prc-commitments">Compromisos del centro</label>
			<textarea class="prc-control" id="prc-commitments" name="<?php echo esc_attr( ProcedureMetaKeys::COMMITMENTS ); ?>" rows="5"
				aria-describedby="prc-commitments-ayuda"><?php echo esc_textarea( (string) $v[ ProcedureMetaKeys::COMMITMENTS ] ); ?></textarea>
			<p class="prc-campo__ayuda" id="prc-commitments-ayuda">Lo que el centro acepta al solicitar, con una casilla obligatoria. En blanco, no se pide nada.</p>
		</div>
		<?php
		return (string) ob_get_clean();
	}












	private static function eligibility_fields( array $m, array $v ): string {
		$opciones = (array) ( $m['centre_options'] ?? array() );
		$listas   = array(
			ProcedureMetaKeys::CENTRE_TYPES => array( 'Tipos de centro', (array) ( $opciones['types'] ?? array() ), 'Ninguno marcado: todos los tipos.' ),
			ProcedureMetaKeys::ISLANDS      => array( 'Islas', (array) ( $opciones['islands'] ?? array() ), 'Ninguna marcada: todas.' ),
		);

		$elegidos = array();
		foreach ( array_keys( $listas ) as $nombre ) {
			$elegidos[ $nombre ] = array_map( array( CentreEligibility::class, 'fold' ), array_map( 'strval', (array) $v[ $nombre ] ) );
		}

		ob_start();
		?>
		<div class="prc-seccion__cuerpo" data-prc-schools-only <?php echo ProcedureMetaKeys::AUDIENCE_SCHOOLS === $v[ ProcedureMetaKeys::AUDIENCE ] ? '' : 'hidden'; ?>>
			<div class="prc-campo prc-campo--completo">
				<p class="prc-campo__ayuda">Sin nada marcado ni escrito, puede solicitar cualquier centro, también uno que todavía no esté en el catálogo. Con tipos o islas, solo los centros del catálogo que los cumplan; con la lista cerrada sola, los de la lista, estén o no en el catálogo. Los límites se miran al presentar una solicitud nueva: un centro que ya la presentó la sigue editando o subsanando cuando el plazo lo permita, aunque el procedimiento se acote después.</p>
				<?php echo self::centre_warning( $m, 'catalogue' );?>
			</div>
			<?php foreach ( $listas as $nombre => $lista ) : ?>
				<div class="prc-campo prc-campo--medio">
					<fieldset>
						<legend class="prc-campo__etiqueta"><?php echo esc_html( $lista[0] ); ?></legend>
						<?php if ( array() === $lista[1] ) : ?>
							<p class="prc-campo__ayuda">El catálogo de centros no trae ninguno.</p>
						<?php endif; ?>
						<div class="prc-opciones prc-opciones--vertical">
							<?php foreach ( $lista[1] as $i => $valor ) : ?>
								<div class="prc-opcion">
									<input type="checkbox" id="<?php echo esc_attr( $nombre . '-' . $i ); ?>"
										name="<?php echo esc_attr( $nombre ); ?>[]" value="<?php echo esc_attr( (string) $valor ); ?>"
										<?php checked( in_array( CentreEligibility::fold( (string) $valor ), $elegidos[ $nombre ], true ) ); ?> />
									<label for="<?php echo esc_attr( $nombre . '-' . $i ); ?>"><?php echo esc_html( (string) $valor ); ?></label>
								</div>
							<?php endforeach; ?>
						</div>
						<p class="prc-campo__ayuda"><?php echo esc_html( $lista[2] ); ?></p>
					</fieldset>
				</div>
			<?php endforeach; ?>
			<div class="prc-campo prc-campo--completo">
				<label class="prc-campo__etiqueta" for="prc-allowed-centres">Lista cerrada de centros</label>
				<textarea class="prc-control" id="prc-allowed-centres" name="<?php echo esc_attr( ProcedureMetaKeys::ALLOWED_CENTRES ); ?>" rows="4"
					aria-describedby="prc-allowed-centres-ayuda"><?php echo esc_textarea( (string) $v[ ProcedureMetaKeys::ALLOWED_CENTRES ] ); ?></textarea>
				<p class="prc-campo__ayuda" id="prc-allowed-centres-ayuda">Códigos de centro, uno por línea o separados por comas, hasta <?php echo esc_html( (string) CentreEligibility::MAX_CODES ); ?>. En blanco, sin lista.</p>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}








	private static function centre_warning( array $m, string $key ): string {
		$texto = (string) ( $m['centre_warnings'][ $key ] ?? '' );
		return '' === $texto ? '' : '<p class="prc-aviso prc-aviso-warning prc-aviso-centros" role="status">' . esc_html( $texto ) . '</p>';
	}






	private static function obl(): string {
		return '<abbr class="prc-campo__obligatorio" title="obligatorio">*</abbr>';
	}












	private static function term_select( string $id, string $nombre, string $rotulo, array $terminos, int $elegido, string $ayuda ): string {
		ob_start();
		?>
		<label class="prc-campo__etiqueta" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $rotulo ); ?> <?php echo self::obl();?></label>
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
		$op        = ProcedureEditor::PANEL_QUESTIONS;
		$guardas   = (array) $m['questions'];
		$total     = count( $guardas );
		$nuevas    = max( 0, min( self::NEW_QUESTIONS, (int) $m['q_max'] - $total ) );
		$en_blanco = array(
			'key'      => '',
			'label'    => '',
			'help'     => '',
			'type'     => ProcedureQuestions::TYPE_TEXT,
			'required' => false,
			'choices'  => array(),
		);

		ob_start();
		?>
		<form class="prc-form prc-form--taller" method="post" action="">
			<?php wp_nonce_field( ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />

			<p class="prc-intro">
				Las opciones propias de este procedimiento, hasta <?php echo esc_html( (string) (int) $m['q_max'] ); ?>. El resto de la
				solicitud —centro, cargo, coordinación y compromisos— es siempre el mismo y se ajusta en «Datos».
				«Subir» y «Bajar» cambian el orden al momento: guarde antes lo que haya escrito.
			</p>
			<p class="prc-intro">¿Las mismas preguntas que el curso pasado? Abra aquel procedimiento y pulse «Duplicar»: se copia con sus preguntas, sin plazos ni solicitudes.</p>
			<?php echo PanelParts::help_link( (string) ( $m['help_questions_url'] ?? '' ) );?>

			<?php foreach ( $guardas as $i => $pregunta ) : ?>
				<?php echo self::question_row( (int) $i, (array) $pregunta, $m, $total );?>
			<?php endforeach; ?>
			<?php for ( $n = 0; $n < $nuevas; $n++ ) : ?>
				<?php if ( 1 === $n ) : ?>
					<details class="prc-preguntas-mas">
						<summary>Añadir otra pregunta</summary>
				<?php endif; ?>
				<?php echo self::question_row( $total + $n, $en_blanco, $m, $total );?>
			<?php endfor; ?>
			<?php if ( $nuevas > 1 ) : ?>
				</details>
			<?php endif; ?>

			<?php echo PanelParts::submit( 'Guardar las preguntas' );?>
		</form>
		<form id="prc-q-mover" method="post" action="">
			<?php wp_nonce_field( ProcedureEditor::nonce_action( ProcedureEditor::OP_MOVE_QUESTION ), ProcedureEditor::nonce_name( ProcedureEditor::OP_MOVE_QUESTION ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( ProcedureEditor::OP_MOVE_QUESTION ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
		</form>
		<?php
		return (string) ob_get_clean();
	}











	private static function move_buttons( int $i, array $p, int $total ): string {
		$botones = array(
			QuestionChanges::MOVE_UP   => array( 'Subir', 0 === $i ),
			QuestionChanges::MOVE_DOWN => array( 'Bajar', $i >= $total - 1 ),
		);
		$out     = '';
		foreach ( $botones as $hacia => $boton ) {
			$out .= sprintf(
				'<button type="submit" class="%1$s" form="prc-q-mover" name="%2$s" value="%3$s" aria-label="%4$s"%5$s>%6$s</button> ',
				esc_attr( Assets::button_class() ),
				esc_attr( ProcedureEditor::FIELD_Q_MOVE ),
				esc_attr( (string) $p['key'] . ':' . $hacia ),
				esc_attr( $boton[0] . ' la pregunta ' . ( $i + 1 ) ),
				$boton[1] ? ' disabled' : '',
				esc_html( $boton[0] )
			);
		}
		$quitar = '<span class="prc-opcion prc-pregunta__quitar">'
			. '<input type="checkbox" id="prc-q-x-' . esc_attr( (string) $i ) . '" name="' . esc_attr( ProcedureEditor::FIELD_Q . 'remove[' . $i . ']' ) . '" value="1" />'
			. '<label for="prc-q-x-' . esc_attr( (string) $i ) . '">Quitar esta pregunta al guardar (lo ya contestado se conserva)</label></span>';
		return '<div class="prc-campo prc-pregunta__orden">' . $out . $quitar . '</div>';
	}








	private static function type_fields( int $i, array $p ): string {
		$campo = ProcedureEditor::FIELD_Q;
		$filas = implode( "\n", array_map( 'strval', wp_list_pluck( (array) ( $p['rows'] ?? array() ), 'label' ) ) );

		ob_start();
		?>
		<div data-prc-q-for="number">
			<div class="prc-seccion__cuerpo">
				<?php
				foreach ( array(
					'min' => 'Mínimo',
					'max' => 'Máximo',
				) as $limite => $rotulo ) :
					?>
					<div class="prc-campo prc-campo--medio">
						<label class="prc-campo__etiqueta" for="prc-q-<?php echo esc_attr( $limite . '-' . $i ); ?>"><?php echo esc_html( $rotulo ); ?></label>
						<input class="prc-control" type="number" step="1" id="prc-q-<?php echo esc_attr( $limite . '-' . $i ); ?>" name="<?php echo esc_attr( $campo . $limite ); ?>[<?php echo esc_attr( (string) $i ); ?>]"
							value="<?php echo esc_attr( (string) ( $p[ $limite ] ?? '' ) ); ?>" aria-describedby="prc-q-n-help-<?php echo esc_attr( (string) $i ); ?>" />
					</div>
				<?php endforeach; ?>
			</div>
			<p class="prc-campo__ayuda" id="prc-q-n-help-<?php echo esc_attr( (string) $i ); ?>">Solo para «Número»: un número entero. Sin mínimo, se admite desde 0; sin máximo, no hay tope.</p>
		</div>
		<div class="prc-campo" data-prc-q-for="grid">
			<label class="prc-campo__etiqueta" for="prc-q-rows-<?php echo esc_attr( (string) $i ); ?>">Filas, una por línea</label>
			<textarea class="prc-control" id="prc-q-rows-<?php echo esc_attr( (string) $i ); ?>" name="<?php echo esc_attr( $campo . 'rows' ); ?>[<?php echo esc_attr( (string) $i ); ?>]" rows="3"
				aria-describedby="prc-q-rows-help-<?php echo esc_attr( (string) $i ); ?>"><?php echo esc_textarea( $filas ); ?></textarea>
			<p class="prc-campo__ayuda" id="prc-q-rows-help-<?php echo esc_attr( (string) $i ); ?>">Solo para «Cantidades por fila»: hasta <?php echo esc_html( (string) ProcedureQuestions::MAX_ROWS ); ?> filas, y en cada una se pide una cantidad. Para cambiar el nombre de una fila, escríbalo en su misma línea; para quitarla, deje su línea en blanco.</p>
		</div>
		<?php
		return (string) ob_get_clean();
	}








	private static function delivery_fields( int $i, array $p ): string {
		$campo = ProcedureEditor::FIELD_Q;
		$ayuda = 'prc-q-d-help-' . $i;
		$out   = '<div data-prc-q-for="file"><div class="prc-campo prc-opcion">'
			. '<input type="checkbox" id="prc-q-d-' . esc_attr( (string) $i ) . '" name="' . esc_attr( $campo . 'delivery[' . $i . ']' ) . '" value="1"' . checked( ProcedureQuestions::is_delivery( $p ), true, false ) . ' aria-describedby="' . esc_attr( $ayuda ) . '" />'
			. '<label for="prc-q-d-' . esc_attr( (string) $i ) . '">Entrega con plazo propio (solo para «Archivo»)</label>'
			. '<p class="prc-campo__ayuda" id="' . esc_attr( $ayuda ) . '">Se entrega por separado después de presentar la solicitud. Puede seguir abierta tras la inscripción o el listado definitivo. Configure sus fechas antes de cerrar las preguntas.</p></div>';
		foreach ( array(
			'opens_at'  => 'Apertura de la entrega',
			'closes_at' => 'Cierre de la entrega (inclusive)',
		) as $date_key => $date_label ) {
			$id   = 'prc-q-' . $date_key . '-' . $i;
			$out .= '<div class="prc-campo"><label for="' . esc_attr( $id ) . '">' . esc_html( $date_label ) . '</label>'
				. '<input class="prc-control" type="date" id="' . esc_attr( $id ) . '" name="' . esc_attr( $campo . $date_key . '[' . $i . ']' ) . '" value="' . esc_attr( (string) ( $p[ $date_key ] ?? '' ) ) . '" aria-describedby="' . esc_attr( $ayuda ) . '" /></div>';
		}
		return $out . '</div>';
	}










	private static function question_row( int $i, array $p, array $m, int $total ): string {
		$nueva     = '' === (string) $p['key'];
		$campo     = ProcedureEditor::FIELD_Q;
		$rotulo    = $nueva ? 'Pregunta nueva' : 'Pregunta ' . ( $i + 1 );
		$contestan = $nueva ? 0 : (int) ( ( (array) ( $m['answers'] ?? array() ) )[ (string) $p['key'] ] ?? 0 );
		$aviso     = 'prc-q-l-aviso-' . $i;

		ob_start();
		?>
		<fieldset class="prc-seccion-campos prc-pregunta">
			<legend class="prc-seccion__titulo"><?php echo esc_html( $rotulo ); ?></legend>
			<?php echo $nueva ? '' : self::move_buttons( $i, $p, $total );?>
			<input type="hidden" name="<?php echo esc_attr( $campo . 'key' ); ?>[<?php echo esc_attr( (string) $i ); ?>]" value="<?php echo esc_attr( (string) $p['key'] ); ?>" />
			<div class="prc-seccion__cuerpo">
				<div class="prc-campo prc-campo--medio">
					<label class="prc-campo__etiqueta" for="prc-q-l-<?php echo esc_attr( (string) $i ); ?>">Rótulo</label>
					<input class="prc-control" type="text" id="prc-q-l-<?php echo esc_attr( (string) $i ); ?>" name="<?php echo esc_attr( $campo . 'label' ); ?>[<?php echo esc_attr( (string) $i ); ?>]"
						maxlength="200" value="<?php echo esc_attr( (string) $p['label'] ); ?>"<?php echo $contestan > 0 ? ' aria-describedby="' . esc_attr( $aviso ) . '"' : ''; ?> />
					<?php if ( $contestan > 0 ) : ?>
						<p class="prc-aviso prc-aviso-warning prc-pregunta__contestada" id="<?php echo esc_attr( $aviso ); ?>"><?php echo esc_html( sprintf( 'Ya la han contestado %1$d %2$s. Corrija la redacción, no el sentido: si va a preguntar otra cosa, cree una pregunta nueva.', $contestan, 1 === $contestan ? 'solicitud' : 'solicitudes' ) ); ?></p>
					<?php endif; ?>
				</div>
				<div class="prc-campo prc-campo--medio">
					<label class="prc-campo__etiqueta" for="prc-q-t-<?php echo esc_attr( (string) $i ); ?>">Tipo</label>
					<select class="prc-control prc-pregunta__tipo" id="prc-q-t-<?php echo esc_attr( (string) $i ); ?>" name="<?php echo esc_attr( $campo . 'type' ); ?>[<?php echo esc_attr( (string) $i ); ?>]">
						<?php foreach ( (array) $m['q_types'] as $valor => $nombre ) : ?>
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
			<div class="prc-campo" data-prc-q-for="single multiple">
				<label class="prc-campo__etiqueta" for="prc-q-c-<?php echo esc_attr( (string) $i ); ?>">Opciones, una por línea</label>
				<textarea class="prc-control" id="prc-q-c-<?php echo esc_attr( (string) $i ); ?>" name="<?php echo esc_attr( $campo . 'choices' ); ?>[<?php echo esc_attr( (string) $i ); ?>]" rows="3"
					aria-describedby="prc-q-c-help-<?php echo esc_attr( (string) $i ); ?>"><?php echo esc_textarea( implode( "\n", QuestionPoints::lines( $p ) ) ); ?></textarea>
				<p class="prc-campo__ayuda" id="prc-q-c-help-<?php echo esc_attr( (string) $i ); ?>">Solo para «Una opción» y «Varias opciones». En «Varias opciones», una opción escrita «Texto | 3» vale 3 puntos y quien solicita solo ve «Texto»; la solicitud suma los puntos de lo que marque.</p>
			</div>
			<?php echo self::type_fields( $i, $p );?>
			<?php echo self::delivery_fields( $i, $p );?>
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
				<p class="prc-intro">Son enlaces a donde ya están publicados: aquí no se guarda ninguna copia. Pegue la dirección completa, que empieza por https://.</p>
				<?php foreach ( $campos as $clave => $texto ) : ?>
					<div class="prc-campo">
						<label class="prc-campo__etiqueta" for="<?php echo esc_attr( $clave ); ?>"><?php echo esc_html( $texto[0] ); ?></label>
						<input class="prc-control" type="url" id="<?php echo esc_attr( $clave ); ?>" name="<?php echo esc_attr( $clave ); ?>" placeholder="https://"
							value="<?php echo esc_attr( (string) $v[ $clave ] ); ?>" />
						<p class="prc-campo__ayuda"><?php echo esc_html( $texto[1] ); ?></p>
						<?php $guardado = (string) ( $v['saved_links'][ $clave ] ?? '' ); ?>
						<?php if ( '' !== $guardado ) : ?>
							<p class="prc-campo__ayuda"><a href="<?php echo esc_url( $guardado ); ?>" target="_blank" rel="noopener noreferrer">Abrir el enlace guardado ↗<span class="screen-reader-text"> (se abre en otra pestaña)</span></a></p>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</fieldset>
			<?php echo PanelParts::submit( 'Guardar los enlaces' );?>
		</form>
		<?php
		return (string) ob_get_clean();
	}
















	private static function publish_panel( array $m ): string {
		ob_start();
		?>
		<section class="prc-seccion-campos">
			<h2 class="prc-seccion__titulo">Estado</h2>
			<?php echo self::publish_timeline( $m );?>
			<p class="prc-intro">
				<?php echo self::badge( (string) $m['state'], (string) $m['state_label'] );?>
				El estado sale de las fechas y los enlaces; no hay que marcarlo a mano. Con el listado definitivo enlazado pasa a «Resuelto».
				Si necesita cerrar las solicitudes antes, puede hacerlo aquí sin cambiar esos datos ni los plazos de entrega documental.
			</p>
			<?php echo self::checklist( $m );?>
			<?php if ( true === $m['can_publish'] ) : ?>
				<?php $op = ProcedureEditor::OP_PUBLISH; ?>
				<p class="prc-intro">En borrador no se ve fuera y ningún centro puede solicitar. Al publicarlo sale en la portada,
					y los centros solicitan cuando abra el plazo.</p>
				<form class="prc-acciones" method="post" action=""
					data-prc-confirm="<?php echo esc_attr( sprintf( '¿Publicar «%s»? Pasará a verse en la portada.', (string) $m['title'] ) ); ?>"
					data-prc-confirm-ok="Publicar">
					<?php echo self::op_fields( $m, $op );?>
					<button class="<?php echo esc_attr( Assets::button_class( true ) . ' prc-envio' ); ?>" type="submit">Publicar el procedimiento</button>
				</form>
			<?php elseif ( 'publish' === (string) $m['status'] ) : ?>
				<p class="prc-intro">Publicado: se ve en la portada y en su ficha. Si necesita retirarlo temporalmente,
					puede devolverlo a borrador.</p>
			<?php endif; ?>
			<?php echo self::toggle_form( $m );?>
			<?php echo self::unpublish_form( $m );?>
		</section>
		<?php
		return (string) ob_get_clean();
	}








	private static function op_fields( array $m, string $op ): string {
		return wp_nonce_field( ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op ), false, false )
			. '<input type="hidden" name="' . esc_attr( ProcedureEditor::FIELD_DO ) . '" value="' . esc_attr( $op ) . '" />'
			. '<input type="hidden" name="' . esc_attr( ProcedureEditor::FIELD_PROCEDURE ) . '" value="' . esc_attr( (string) (int) $m['procedure_id'] ) . '" />';
	}







	private static function publish_timeline( array $m ): string {
		$etapas = array(
			ProcedureMetaKeys::STATE_DRAFT    => 0,
			ProcedureMetaKeys::STATE_ARCHIVED => 4,
			ProcedureMetaKeys::STATE_RESOLVED => 3,
			ProcedureMetaKeys::STATE_CLOSED   => empty( $m['pending_documentation'] ) ? 3 : 2,
		);
		$stage  = $etapas[ (string) $m['state'] ] ?? 1;
		$steps  = array(
			array( 'Borrador', 'Solo quienes gestionan el procedimiento pueden verlo; los centros aún no pueden solicitar.' ),
			array( 'Publicado', 'La ficha se muestra públicamente. Los centros pueden solicitar cuando se abre el plazo.' ),
			array( 'Pendiente de documentación', 'Las solicitudes están cerradas y los centros ya inscritos aún pueden entregar documentos dentro de su plazo.' ),
			array( 'Cerrado', 'No se admiten solicitudes. Cuando ya no quede nada que gestionar, puede marcar el procedimiento como histórico.' ),
			array( 'Histórico', 'Se conserva para consulta y exportación; el procedimiento queda en solo lectura.' ),
		);
		$out    = '<ol class="prc-ciclo-publicacion" aria-label="Etapas del procedimiento">';
		foreach ( $steps as $index => $step ) {
			$clase  = $index < $stage ? ' prc-ciclo-publicacion__paso--completado' : ( $index === $stage ? ' prc-ciclo-publicacion__paso--actual' : '' );
			$actual = $index === $stage;
			$out   .= '<li class="prc-ciclo-publicacion__paso' . $clase . '"' . ( $actual ? ' aria-current="step"' : '' ) . '>'
				. '<h3>' . esc_html( $step[0] ) . ( $actual ? ' <span class="prc-ciclo-publicacion__actual">Etapa actual</span>' : '' ) . '</h3>'
				. '<p>' . esc_html( $step[1] ) . '</p></li>';
		}
		return $out . '</ol>';
	}










	private static function checklist( array $m ): string {
		$puntos = (array) ( $m['checklist'] ?? array() );
		if ( array() === $puntos ) {
			return '';
		}
		$out = '<div class="prc-antes-de-publicar"><h3 class="prc-antes-de-publicar__titulo">Antes de publicar</h3><ul class="prc-comprobaciones">';
		foreach ( $puntos as $punto ) {
			$tipo = ! empty( $punto['ok'] ) ? array( 'ok', '✓', 'Hecho:' ) : ( ! empty( $punto['blocks'] ) ? array( 'falta', '✗', 'Falta:' ) : array( 'aviso', '!', 'Aviso:' ) );
			$out .= '<li class="prc-comprobacion prc-comprobacion--' . esc_attr( $tipo[0] ) . '">'
				. '<span class="prc-comprobacion__marca" aria-hidden="true">' . esc_html( $tipo[1] ) . '</span> '
				. '<span class="screen-reader-text">' . esc_html( $tipo[2] ) . '</span> '
				. esc_html( (string) $punto['text'] ) . '</li>';
		}
		return $out . '</ul>' . PanelParts::help_link( (string) ( $m['help_publish_url'] ?? '' ) ) . '</div>';
	}










	private static function toggle_form( array $m ): string {
		if ( true !== $m['can_toggle_applications'] || 'publish' !== (string) $m['status'] ) {
			return '';
		}
		$cerrada = true === $m['applications_closed'];
		if ( $cerrada ) {
			$texto = 'La inscripción está cerrada manualmente. Se mantienen los plazos de subsanación y de entrega de documentos ya solicitados.';
		} else {
			$texto = true === $m['pending_documentation']
				? 'Hay documentos pendientes de entrega. Cerrar solicitudes no cerrará esos plazos.'
				: 'Las solicitudes se abren y cierran según las fechas configuradas.';
		}
		$pregunta = sprintf(
			'¿Cerrar la inscripción de «%s» ahora? No se aceptarán nuevas solicitudes; se mantienen los plazos de subsanación y entrega documental que ya estén abiertos.',
			(string) $m['title']
		);
		$confirma = $cerrada ? '' : ' data-prc-confirm="' . esc_attr( $pregunta ) . '" data-prc-confirm-ok="Cerrar solicitudes"';
		return '<p class="prc-intro">' . esc_html( $texto ) . '</p>'
			. '<form class="prc-acciones" method="post" action=""' . $confirma . '>'
			. self::op_fields( $m, ProcedureEditor::OP_TOGGLE_APPLICATIONS )
			. '<button class="' . esc_attr( Assets::button_class( $cerrada ? false : 'danger' ) ) . '" type="submit">'
			. ( $cerrada ? 'Volver al calendario automático' : 'Cerrar solicitudes ahora' ) . '</button>'
			. ( $cerrada ? '' : ' <span class="prc-consecuencia">Los centros dejan de poder solicitar.</span>' )
			. '</form>';
	}







	private static function unpublish_form( array $m ): string {
		if ( true !== $m['can_unpublish'] ) {
			return '';
		}
		$pregunta = sprintf(
			'¿Devolver «%s» a borrador? Dejará de verse públicamente y no se podrá solicitar hasta que vuelva a publicarse.',
			(string) $m['title']
		);
		return '<form class="prc-acciones" method="post" action="" data-prc-confirm="' . esc_attr( $pregunta ) . '" data-prc-confirm-ok="Devolver a borrador">'
			. self::op_fields( $m, ProcedureEditor::OP_UNPUBLISH )
			. '<button class="' . esc_attr( Assets::button_class( 'danger' ) ) . '" type="submit">Devolver a borrador</button>'
			. ' <span class="prc-consecuencia">Deja de verse en la portada y nadie puede solicitar.</span>'
			. '</form>';
	}










	private static function notices_panel( array $m ): string {
		$op       = ProcedureEditor::OP_NOTIFY;
		$apagados = (array) ( $m['values'][ ProcedureMetaKeys::NOTIFY ] ?? array() );
		ob_start();
		?>
		<form class="prc-form prc-form--taller" method="post" action="">
			<?php wp_nonce_field( ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
			<fieldset class="prc-seccion-campos">
				<legend class="prc-seccion__titulo">Avisos por correo</legend>
				<p class="prc-intro">Los correos que manda el aplicativo para este procedimiento. Desmarque los que no quiera enviar; el resto sale solo, en texto plano y desde el remitente que configure administración.</p>
				<div class="prc-opciones prc-opciones--vertical">
					<?php foreach ( (array) $m['notify_events'] as $evento => $rotulo ) : ?>
						<div class="prc-opcion">
							<input type="checkbox" id="prc-notify-<?php echo esc_attr( (string) $evento ); ?>"
								name="<?php echo esc_attr( ProcedureEditor::FIELD_NOTIFY ); ?>[]" value="<?php echo esc_attr( (string) $evento ); ?>"
								<?php checked( ! in_array( (string) $evento, $apagados, true ) ); ?> />
							<label for="prc-notify-<?php echo esc_attr( (string) $evento ); ?>"><?php echo esc_html( (string) $rotulo ); ?></label>
						</div>
					<?php endforeach; ?>
				</div>
			</fieldset>
			<?php echo PanelParts::submit( 'Guardar los avisos' );?>
		</form>
		<?php
		return (string) ob_get_clean();
	}







	private static function custom_code_panel( array $m ): string {
		$op = ProcedureEditor::OP_CUSTOM_CODE;
		ob_start();
		?>
		<section class="prc-seccion-campos">
			<h2 class="prc-seccion__titulo">Código adicional</h2>
			<p class="prc-intro">El CSS se aplica solo a la ficha pública de este procedimiento.</p>
			<form class="prc-form prc-form--taller" method="post" action="">
				<?php wp_nonce_field( ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op ), false ); ?>
				<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
				<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
				<?php echo self::code_editor_field( 'prc-custom-css', 'prc_custom_css', 'CSS', (string) ( $m['values'][ ProcedureMetaKeys::CUSTOM_CSS ] ?? '' ), empty( $m['can_edit'] ) );?>
				<?php if ( ! empty( $m['can_edit_custom_js'] ) ) : ?>
					<div class="prc-aviso prc-aviso-warning prc-codigo-admin">
						<h3 id="prc-codigo-admin-titulo">Solo para administración</h3>
						<p>Este JavaScript se ejecuta en la ficha pública del procedimiento. Revíselo antes de guardarlo.</p>
					</div>
					<?php echo self::code_editor_field( 'prc-custom-js', 'prc_custom_js', 'JavaScript', (string) ( $m['values'][ ProcedureMetaKeys::CUSTOM_JS ] ?? '' ), false );?>
				<?php endif; ?>
				<button class="<?php echo esc_attr( Assets::button_class( true ) . ' prc-envio' ); ?>" type="submit"<?php disabled( empty( $m['can_edit'] ) ); ?>>Guardar el código</button>
			</form>
		</section>
		<?php
		return (string) ob_get_clean();
	}











	private static function code_editor_field( string $id, string $name, string $label, string $value, bool $disabled ): string {
		ob_start();
		?>
		<div class="prc-code">
			<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
			<textarea
				class="prc-control"
				id="<?php echo esc_attr( $id ); ?>"
				name="<?php echo esc_attr( $name ); ?>"
				rows="14"
				spellcheck="false"
				autocapitalize="off"
				autocomplete="off"
				autocorrect="off"
				<?php disabled( $disabled ); ?>
				><?php echo esc_textarea( $value ); ?></textarea>
		</div>
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
			<?php echo self::archive_form( $m, true );?>
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
			<button type="submit" class="<?php echo esc_attr( Assets::button_class( $marcar ? 'danger' : false ) ); ?>"><?php echo esc_html( $rotulo ); ?></button>
			<?php if ( $marcar ) : ?>
				<span class="prc-consecuencia">Queda en solo lectura; solo administración lo vuelve a abrir.</span>
			<?php endif; ?>
		</form>
		<?php
		return (string) ob_get_clean();
	}










	public static function badge( string $slug, string $label ): string {
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

}









namespace Prc\PublicFront;

use Prc\Access\ProcedureAccess;
use Prc\Domain\CentreCatalog;
use Prc\Domain\CentreEligibility;
use Prc\Domain\PeopleSpec;
use Prc\Domain\ProcedureInput;
use Prc\Domain\ProcedureQuestions;
use Prc\Domain\QuestionChanges;
use Prc\Domain\QuestionGrid;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\Meta\ProcedureMetaRegistration;
use Prc\PostType\ProcedurePostType;
use Prc\PublicFront\View\ProcedureChrome;
use Prc\PublicFront\View\ProcedureEditorView;
use Prc\Taxonomy\ProcedureTaxonomies;
















if ( ! class_exists( 'Prc\PublicFront\ProcedureEditor', false ) ) {
final class ProcedureEditor {

	public const SHORTCODE = 'prc_editor';




	public const ARG_PROCEDURE = Shell::ARG_PROCEDURE;




	public const ARG_PANEL = 'panel';




	public const ARG_NOTICE = 'aviso';

	public const PANEL_DATA         = 'datos';
	public const PANEL_QUESTIONS    = 'preguntas';
	public const PANEL_LINKS        = 'enlaces';
	public const PANEL_APPLICATIONS = 'solicitudes';
	public const PANEL_DELIVERIES   = 'entregas';
	public const PANEL_PUBLISH      = 'publicacion';
	public const PANEL_NOTICES      = 'avisos';
	public const PANEL_CODE         = 'codigo';
	public const PANEL_PEOPLE       = 'personas';
	public const PANEL_OBSERVE      = 'consulta';

	public const OP_REVIEW              = 'review';
	public const OP_BULK_REVIEW         = 'bulk_review';
	public const OP_EXPORT              = 'export';
	public const OP_PUBLISH             = 'publish';
	public const OP_UNPUBLISH           = 'unpublish';
	public const OP_TOGGLE_APPLICATIONS = 'toggle_applications';
	public const OP_ARCHIVE             = 'archive';
	public const OP_UNARCHIVE           = 'unarchive';
	public const OP_DELIVERY_DATES      = 'delivery_dates';
	public const OP_CUSTOM_CODE         = 'custom_code';
	public const OP_NOTIFY              = 'notify';
	public const OP_MOVE_QUESTION       = 'move_question';
	public const OP_PEOPLE_SPEC         = 'people_spec';
	public const OP_OBSERVE             = 'observe';
	public const OP_DUPLICATE           = 'duplicate';
	public const FIELD_DELIVERY_CLOSES  = 'prc_delivery_closes';




	public const FIELD_DO = 'prc_do';




	public const FIELD_PROCEDURE = 'prc_procedure';




	public const FIELD_ROW = 'prc_row';




	public const FIELD_STATE = 'prc_state';




	public const FIELD_NOTE = 'prc_note';

	public const FIELD_TITLE       = 'prc_title';
	public const FIELD_DESCRIPTION = 'prc_description';
	public const FIELD_AREA        = 'prc_area_term';
	public const FIELD_COURSE      = 'prc_course_term';






	public const FIELD_COORDINATOR_KIND = 'prc_coordinator_kind';




	public const FIELD_Q = 'prc_q_';




	public const FIELD_Q_MOVE = 'prc_q_move';







	public const FIELD_NOTIFY = 'prc_notify_on';








	public const FIELD_EXPORT_ALL     = 'prc_export_all';
	public const FIELD_EXPORT_COLUMNS = 'prc_export_columns';
	public const FIELD_EXPORT_SHAPE   = 'prc_export_shape';
	public const FIELD_EXPORT_FORMAT  = 'prc_export_format';
	public const FIELD_EXPORT_WHO     = 'prc_export_who';






	private const OPS = array(
		self::PANEL_DATA,
		self::PANEL_QUESTIONS,
		self::OP_MOVE_QUESTION,
		self::OP_DELIVERY_DATES,
		self::OP_CUSTOM_CODE,
		self::OP_NOTIFY,
		self::OP_PEOPLE_SPEC,
		self::OP_OBSERVE,
		self::PANEL_LINKS,
		self::OP_REVIEW,
		self::OP_BULK_REVIEW,
		self::OP_EXPORT,
		self::OP_PUBLISH,
		self::OP_UNPUBLISH,
		self::OP_TOGGLE_APPLICATIONS,
		self::OP_ARCHIVE,
		self::OP_UNARCHIVE,
		self::OP_DUPLICATE,
		ApplicationActionsPanel::OP_RETIRE,
		ApplicationActionsPanel::OP_RESTORE,
		ApplicationActionsPanel::OP_EXTEND,
		ApplicationActionsPanel::OP_SUBMIT_FOR,
	);






	private const ROW_OPS = array(
		self::OP_REVIEW,
		ApplicationActionsPanel::OP_RETIRE,
		ApplicationActionsPanel::OP_RESTORE,
		ApplicationActionsPanel::OP_EXTEND,
	);






	private const DONE = array(
		'creado'       => 'Procedimiento creado, en borrador: no se ve fuera hasta que lo publique. Añada sus preguntas y sus enlaces, y publíquelo cuando esté listo.',
		'datos'        => 'Datos del procedimiento guardados.',
		'preguntas'    => 'Preguntas guardadas.',
		'ordenadas'    => 'Orden de las preguntas guardado.',
		'entregas'     => 'Plazos de entrega actualizados.',
		'enlaces'      => 'Enlaces guardados.',
		'codigo'       => 'Código adicional guardado.',
		'avisos'       => 'Avisos por correo guardados.',
		'personas'     => 'Lista de personas guardada.',
		'consulta'     => 'Consulta de solicitudes guardada.',
		'revisada'     => 'Solicitud revisada.',
		'publicado'    => 'Procedimiento publicado: ya se ve en la portada y los centros pueden solicitar cuando abra el plazo.',
		'borrador'     => 'Procedimiento devuelto a borrador: deja de verse públicamente y no admite solicitudes.',
		'cerradas'     => 'Inscripción cerrada. Los plazos de subsanación y de entrega documental '
			. 'se mantienen como estaban.',
		'reabiertas'   => 'La inscripción vuelve a regirse por las fechas configuradas.',
		'archivado'    => 'Procedimiento marcado como histórico. Ya no se edita ni se gestionan sus solicitudes; la ficha pública se sigue viendo igual. Para volver a abrirlo hay que pedírselo a quien administre el aplicativo.',
		'desarchivado' => 'Procedimiento desmarcado: su ámbito vuelve a poder editarlo.',
	);










	private const CANNOT_CREATE = 'Su perfil no puede crear procedimientos: hace falta gestionar procedimientos y tener un ámbito asignado. Pídalo a quien administre el aplicativo.';









	private static $rejected = '';






	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_code_editor' ), 20 );


		add_action( 'init', array( self::class, 'handle' ), 20 );
	}


	public static function enqueue_code_editor(): void {

		if ( 'editor' !== Shell::current_section() || self::PANEL_CODE !== sanitize_key( wp_unslash( (string) ( $_GET[ self::ARG_PANEL ] ?? '' ) ) ) ) {
			return;
		}
		$procedure_id = absint( wp_unslash( $_GET[ self::ARG_PROCEDURE ] ?? 0 ) );

		$user_id = get_current_user_id();
		if ( ProcedureAccess::can_edit( $user_id, $procedure_id ) ) {
			$settings = wp_enqueue_code_editor( array( 'type' => 'text/css' ) );
			if ( is_array( $settings ) ) {
				wp_add_inline_script( 'code-editor', sprintf( 'jQuery(function(){wp.codeEditor.initialize("prc-custom-css",%s);});', wp_json_encode( $settings, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES ) ), 'after' );
			}
		}
		if ( ProcedureAccess::can_edit_custom_js( $user_id, $procedure_id ) ) {
			$settings = wp_enqueue_code_editor( array( 'type' => 'text/javascript' ) );
			if ( is_array( $settings ) ) {
				wp_add_inline_script( 'code-editor', sprintf( 'jQuery(function(){wp.codeEditor.initialize("prc-custom-js",%s);});', wp_json_encode( $settings, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES ) ), 'after' );
			}
		}
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
			self::PANEL_DELIVERIES   => 'Entregas',
			self::PANEL_PEOPLE       => 'Personas',
			self::PANEL_PUBLISH      => 'Publicación',
			self::PANEL_NOTICES      => 'Avisos',
			self::PANEL_OBSERVE      => 'Consulta',
			self::PANEL_CODE         => 'Código',
		);
		$cuentas = array(
			self::PANEL_QUESTIONS    => count( Applications::questions( $procedure_id ) ),
			self::PANEL_APPLICATIONS => Applications::count( $procedure_id ),
			self::PANEL_DELIVERIES   => count( array_filter( Applications::questions( $procedure_id ), array( ProcedureQuestions::class, 'is_delivery' ) ) ),
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












	public static function nonce_action( string $op, int $row = 0 ): string {
		return 'prc_ed_' . $op . ( $row > 0 ? '_' . $row : '' );
	}















	public static function handle(): void {

		$op           = sanitize_key( wp_unslash( (string) ( $_POST[ self::FIELD_DO ] ?? '' ) ) );
		$procedure_id = absint( wp_unslash( $_POST[ self::FIELD_PROCEDURE ] ?? 0 ) );
		$row_id       = absint( wp_unslash( $_POST[ self::FIELD_ROW ] ?? 0 ) );

		if ( ! in_array( $op, self::OPS, true ) || ! is_user_logged_in() ) {
			return;
		}
		if ( ! self::verify( $op, in_array( $op, self::ROW_OPS, true ) ? $row_id : 0 ) ) {
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


		if ( self::OP_DUPLICATE === $op ) {
			self::$rejected = ProcedureDuplicate::handle( $procedure_id, $user_id );
			return;
		}


		if ( in_array( $op, ApplicationActionsPanel::OPS, true ) ) {
			self::$rejected = ApplicationActionsPanel::handle( $op, $procedure_id, $user_id, $row_id );
			return;
		}
		if ( self::OP_CUSTOM_CODE === $op ) {
			EditLock::require_available( $procedure_id );
			if ( ! ProcedureAccess::can_edit( $user_id, $procedure_id ) ) {
				self::$rejected = ProcedureAccess::why_not_editable( $user_id, $procedure_id );
				return;
			}


			$css = isset( $_POST['prc_custom_css'] ) && is_string( $_POST['prc_custom_css'] ) ? ProcedureMetaRegistration::sanitize_custom_code( wp_unslash( $_POST['prc_custom_css'] ) ) : null;
			if ( ! is_string( $css ) ) {
				self::$rejected = 'El CSS no tiene un formato válido.';
				return;
			}
			update_post_meta( $procedure_id, ProcedureMetaKeys::CUSTOM_CSS, $css );


			$js = isset( $_POST['prc_custom_js'] ) && is_string( $_POST['prc_custom_js'] ) ? ProcedureMetaRegistration::sanitize_custom_code( wp_unslash( $_POST['prc_custom_js'] ) ) : null;

			if ( is_string( $js ) && ProcedureAccess::can_edit_custom_js( $user_id, $procedure_id ) ) {
				update_post_meta( $procedure_id, ProcedureMetaKeys::CUSTOM_JS, $js );
			}
			Shell::leave( self::url( $procedure_id, self::PANEL_CODE, array( self::ARG_NOTICE => 'codigo' ) ) );
			return;
		}




		$sueltas = array(
			self::OP_NOTIFY         => array( self::class, 'save_notify' ),
			self::OP_DELIVERY_DATES => array( self::class, 'save_delivery_dates' ),
			self::OP_PEOPLE_SPEC    => array( self::class, 'save_people_spec' ),
			self::OP_OBSERVE        => array( ObserveSettings::class, 'handle' ),
		);
		if ( isset( $sueltas[ $op ] ) ) {
			EditLock::require_available( $procedure_id );
			if ( ! ProcedureAccess::can_edit( $user_id, $procedure_id ) ) {
				self::$rejected = ProcedureAccess::why_not_editable( $user_id, $procedure_id );
				return;
			}
			call_user_func( $sueltas[ $op ], $procedure_id );
			return;
		}

		if ( in_array( $op, array( self::OP_EXPORT, self::OP_REVIEW, self::OP_BULK_REVIEW ), true ) ) {


			if ( ! ProcedureAccess::can_review( $user_id, $procedure_id ) ) {
				self::$rejected = 'Su perfil no gestiona las solicitudes de este procedimiento.';
				return;
			}
			$solicitudes = array(
				self::OP_EXPORT      => 'export',
				self::OP_REVIEW      => 'review',
				self::OP_BULK_REVIEW => 'bulk_review',
			);
			call_user_func( array( self::class, $solicitudes[ $op ] ), $procedure_id, self::OP_REVIEW === $op ? $row_id : $user_id );
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
		if ( self::OP_UNPUBLISH === $op ) {
			self::unpublish( $procedure_id, $user_id );
			return;
		}
		if ( self::OP_TOGGLE_APPLICATIONS === $op ) {
			self::toggle_applications( $procedure_id, $user_id );
			return;
		}
		if ( self::PANEL_DATA === $op ) {
			self::save_data( $procedure_id, $user_id );
			return;
		}
		if ( self::PANEL_QUESTIONS === $op || self::OP_MOVE_QUESTION === $op ) {
			if ( ! self::allows_group( $user_id, $procedure_id, ProcedureState::GROUP_QUESTIONS ) ) {
				return;
			}
			self::PANEL_QUESTIONS === $op ? self::save_questions( $procedure_id ) : self::move_question( $procedure_id );
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
		return false !== wp_verify_nonce( $valor, self::nonce_action( $op, $row ) );
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










		do_action( 'prc_procedure_created', (int) $id );
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


		$lista          = People::spec( $procedure_id );
		self::$rejected = PeopleSpec::coordination_clash( $lista, $lista, Applications::coordinator_count( $procedure_id ), (int) $revisado['data']['coordinator_count'] );
		if ( '' !== self::$rejected ) {
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










	private static function centre_kinds_value( int $procedure_id ): array {
		return $procedure_id > 0 ? ProcedureAccess::centre_kinds( $procedure_id ) : array( ProcedureMetaKeys::KIND_SCHOOL );
	}







	private static function submitted_data( int $procedure_id = 0 ): array {

		$descripcion = wp_kses_post( wp_unslash( (string) ( $_POST[ self::FIELD_DESCRIPTION ] ?? '' ) ) );

		$titularidad = (array) wp_unslash( $_POST[ ProcedureMetaKeys::OWNERSHIP ] ?? array() );

		$clases = (array) wp_unslash( $_POST[ ProcedureMetaKeys::CENTRE_KINDS ] ?? array() );
		$tipo   = isset( $_POST[ self::FIELD_COORDINATOR_KIND ] )
			? sanitize_key( wp_unslash( (string) $_POST[ self::FIELD_COORDINATOR_KIND ] ) )
			: '';
		if ( in_array( $tipo, array( 'none', 'one', 'several' ), true ) ) {
			if ( 'none' === $tipo ) {
				$coordina = '0';
			} elseif ( 'one' === $tipo ) {
				$coordina = '1';
			} else {
				$coordina = (string) max( 2, min( ProcedureMetaKeys::COORDINATOR_COUNT_MAX, (int) self::field( ProcedureMetaKeys::COORDINATOR_COUNT ) ) );
			}
		} else {
			$coordina = isset( $_POST[ ProcedureMetaKeys::COORDINATOR_COUNT ] )
				? self::field( ProcedureMetaKeys::COORDINATOR_COUNT )
				: ( $procedure_id > 0 ? (string) Applications::coordinator_count( $procedure_id ) : '0' );
		}

		$ambitos = (array) wp_unslash( $_POST[ self::FIELD_AREA ] ?? array() );

		$correos = (array) wp_unslash( $_POST[ ProcedureMetaKeys::CONTACT_EMAILS ] ?? array() );

		$tipos = (array) wp_unslash( $_POST[ ProcedureMetaKeys::CENTRE_TYPES ] ?? array() );

		$islas = (array) wp_unslash( $_POST[ ProcedureMetaKeys::ISLANDS ] ?? array() );


		$datos                      = array(
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
			'centre_kinds'         => $clases,
			'requires_coordinator' => '' !== $coordina && '0' !== $coordina,
			'commitments'          => self::textarea( ProcedureMetaKeys::COMMITMENTS ),
			'centre_types'         => $tipos,
			'islands'              => $islas,
			'allowed_centres'      => self::textarea( ProcedureMetaKeys::ALLOWED_CENTRES ),
		);
		$datos['coordinator_count'] = $coordina;
		return $datos;
	}














	private static function gated_data( int $procedure_id, bool $datos, bool $fechas ): array {
		$crudo = self::submitted_data( $procedure_id );
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


			$post                       = get_post( $procedure_id );
			$crudo                      = array_merge(
				$crudo,
				array(
					'title'                => $post instanceof \WP_Post ? $post->post_title : '',
					'description'          => $post instanceof \WP_Post ? $post->post_content : '',
					'areas'                => self::terms_of( $procedure_id, ProcedureTaxonomies::AREA ),
					'course'               => self::first_term( $procedure_id, ProcedureTaxonomies::COURSE ),
					'contact_emails'       => get_post_meta( $procedure_id, ProcedureMetaKeys::CONTACT_EMAILS, true ),
					'header_color'         => self::meta( $procedure_id, ProcedureMetaKeys::HEADER_COLOR ),
					'audience'             => self::meta( $procedure_id, ProcedureMetaKeys::AUDIENCE ),
					'ownership'            => ProcedureInput::stored_ownership( get_post_meta( $procedure_id, ProcedureMetaKeys::OWNERSHIP, true ) ),
					'centre_kinds'         => ProcedureAccess::centre_kinds( $procedure_id ),
					'requires_coordinator' => (bool) get_post_meta( $procedure_id, ProcedureMetaKeys::REQUIRES_COORDINATOR, true ),
					'commitments'          => self::meta( $procedure_id, ProcedureMetaKeys::COMMITMENTS ),
				),
				CentreEligibility::form_values( ProcedureAccess::eligibility( $procedure_id ) )
			);
			$crudo['coordinator_count'] = Applications::coordinator_count( $procedure_id );
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
			ProcedureMetaKeys::CENTRE_KINDS         => $limpio['centre_kinds'],
			ProcedureMetaKeys::REQUIRES_COORDINATOR => $limpio['requires_coordinator'],
			ProcedureMetaKeys::COMMITMENTS          => $limpio['commitments'],
			ProcedureMetaKeys::CENTRE_TYPES         => $limpio['centre_types'],
			ProcedureMetaKeys::ISLANDS              => $limpio['islands'],
			ProcedureMetaKeys::ALLOWED_CENTRES      => $limpio['allowed_centres'],
		);
		$meta[ ProcedureMetaKeys::COORDINATOR_COUNT ] = $limpio['coordinator_count'];
		foreach ( $meta as $clave => $valor ) {
			update_post_meta( $procedure_id, $clave, wp_slash( $valor ) );
		}
		wp_set_object_terms( $procedure_id, ProcedureInput::term_ids( $limpio['areas'] ), ProcedureTaxonomies::AREA, false );
		wp_set_object_terms( $procedure_id, array( (int) $limpio['course'] ), ProcedureTaxonomies::COURSE, false );
	}



















	private static function save_questions( int $procedure_id ): void {

		$claves     = (array) wp_unslash( $_POST[ self::FIELD_Q . 'key' ] ?? array() );
		$rotulos    = (array) wp_unslash( $_POST[ self::FIELD_Q . 'label' ] ?? array() );
		$ayudas     = (array) wp_unslash( $_POST[ self::FIELD_Q . 'help' ] ?? array() );
		$tipos      = (array) wp_unslash( $_POST[ self::FIELD_Q . 'type' ] ?? array() );
		$opciones   = (array) wp_unslash( $_POST[ self::FIELD_Q . 'choices' ] ?? array() );
		$obligadas  = (array) wp_unslash( $_POST[ self::FIELD_Q . 'required' ] ?? array() );
		$deliveries = (array) wp_unslash( $_POST[ self::FIELD_Q . 'delivery' ] ?? array() );
		$opens      = (array) wp_unslash( $_POST[ self::FIELD_Q . 'opens_at' ] ?? array() );
		$closes     = (array) wp_unslash( $_POST[ self::FIELD_Q . 'closes_at' ] ?? array() );
		$minimos    = (array) wp_unslash( $_POST[ self::FIELD_Q . 'min' ] ?? array() );
		$maximos    = (array) wp_unslash( $_POST[ self::FIELD_Q . 'max' ] ?? array() );
		$filas      = (array) wp_unslash( $_POST[ self::FIELD_Q . 'rows' ] ?? array() );
		$quitadas   = (array) wp_unslash( $_POST[ self::FIELD_Q . 'remove' ] ?? array() );


		$rotulos = array_replace( $rotulos, array_fill_keys( array_keys( array_filter( $quitadas ) ), '' ) );

		$antes   = Applications::questions( $procedure_id );
		$previas = array_column( $antes, null, 'key' );
		$crudas  = array();
		foreach ( $rotulos as $i => $rotulo ) {
			$key      = isset( $claves[ $i ] ) ? sanitize_key( (string) $claves[ $i ] ) : '';
			$crudas[] = array(
				'key'       => $key,
				'label'     => sanitize_text_field( (string) $rotulo ),
				'help'      => sanitize_text_field( (string) ( $ayudas[ $i ] ?? '' ) ),
				'type'      => isset( $tipos[ $i ] ) ? sanitize_key( (string) $tipos[ $i ] ) : ProcedureQuestions::TYPE_TEXT,
				'choices'   => sanitize_textarea_field( (string) ( $opciones[ $i ] ?? '' ) ),
				'required'  => ! empty( $obligadas[ $i ] ),
				'delivery'  => ! empty( $deliveries[ $i ] ),
				'opens_at'  => isset( $opens[ $i ] ) && is_string( $opens[ $i ] ) ? sanitize_text_field( $opens[ $i ] ) : '',
				'closes_at' => isset( $closes[ $i ] ) && is_string( $closes[ $i ] ) ? sanitize_text_field( $closes[ $i ] ) : '',
				'min'       => sanitize_text_field( (string) ( $minimos[ $i ] ?? '' ) ),
				'max'       => sanitize_text_field( (string) ( $maximos[ $i ] ?? '' ) ),


				'rows'      => QuestionGrid::match_rows(
					implode( "\n", array_map( 'sanitize_text_field', (array) preg_split( '/\r\n|\r|\n/', (string) ( $filas[ $i ] ?? '' ) ) ) ),
					(array) ( $previas[ $key ]['rows'] ?? array() )
				),
				'row_seq'   => (int) ( $previas[ $key ]['row_seq'] ?? 0 ),
			);
		}

		self::$rejected = QuestionChanges::input_errors( $crudas, $antes );
		if ( '' !== self::$rejected ) {
			return;
		}
		$seq       = self::question_seq( $procedure_id, $antes );
		$questions = ProcedureQuestions::sanitize( $crudas, $seq );
		foreach ( $questions as $question ) {
			if ( ProcedureQuestions::is_delivery( $question ) && ! ProcedureQuestions::valid_delivery( $question ) ) {
				self::$rejected = 'Cada entrega con plazo propio necesita fechas válidas de apertura y cierre, en ese orden. No se han guardado las preguntas.';
				return;
			}
		}
		self::$rejected = QuestionChanges::refusal( $antes, $questions, Applications::answered_keys( $procedure_id ) );
		if ( '' !== self::$rejected ) {
			return;
		}
		update_post_meta( $procedure_id, ProcedureMetaKeys::QUESTIONS_SEQ, $seq );
		update_post_meta( $procedure_id, ProcedureMetaKeys::QUESTIONS, wp_slash( $questions ) );
		Shell::leave( self::url( $procedure_id, self::PANEL_QUESTIONS, array( self::ARG_NOTICE => 'preguntas' ) ) );
	}













	private static function question_seq( int $procedure_id, array $questions ): int {
		$claves = array_column( $questions, 'key' );
		if ( ! metadata_exists( 'post', $procedure_id, ProcedureMetaKeys::QUESTIONS_SEQ ) ) {
			$claves = array_merge( $claves, array_keys( Applications::answered_keys( $procedure_id, true ) ) );
		}
		return max( (int) get_post_meta( $procedure_id, ProcedureMetaKeys::QUESTIONS_SEQ, true ), ProcedureQuestions::highest_key( $claves ) );
	}











	private static function move_question( int $procedure_id ): void {
		$pedido  = explode( ':', self::field( self::FIELD_Q_MOVE ) . ':' );
		$antes   = Applications::questions( $procedure_id );
		$despues = QuestionChanges::move( $antes, $pedido[0], $pedido[1] );
		if ( $despues === $antes ) {
			Shell::leave( self::url( $procedure_id, self::PANEL_QUESTIONS ) );
			return;
		}
		update_post_meta( $procedure_id, ProcedureMetaKeys::QUESTIONS, wp_slash( $despues ) );
		Shell::leave( self::url( $procedure_id, self::PANEL_QUESTIONS, array( self::ARG_NOTICE => 'ordenadas' ) ) );
	}








	private static function save_delivery_dates( int $procedure_id ): void {

		$closes = wp_unslash( $_POST[ self::FIELD_DELIVERY_CLOSES ] ?? array() );

		if ( ! is_array( $closes ) ) {
			self::$rejected = 'No se han podido validar los nuevos plazos. No se ha cambiado nada.';
			return;
		}

		$questions = Applications::questions( $procedure_id );
		$updated   = 0;
		foreach ( $questions as &$question ) {
			if ( ! ProcedureQuestions::is_delivery( $question ) || ! array_key_exists( $question['key'], $closes ) ) {
				continue;
			}
			$new_close = is_string( $closes[ $question['key'] ] ) ? sanitize_text_field( $closes[ $question['key'] ] ) : '';
			++$updated;
			if ( ! ProcedureInput::is_valid_date( $new_close ) || $new_close < $question['closes_at'] ) {
				self::$rejected = 'El nuevo cierre debe ser una fecha válida igual o posterior al actual. No se ha cambiado ningún plazo.';
				return;
			}
			$question['closes_at'] = $new_close;
		}
		unset( $question );

		if ( 0 === $updated ) {
			self::$rejected = 'No hay entregas documentales cuyos plazos se puedan ampliar.';
			return;
		}
		update_post_meta( $procedure_id, ProcedureMetaKeys::QUESTIONS, wp_slash( $questions ) );
		Shell::leave( self::url( $procedure_id, self::PANEL_DELIVERIES, array( self::ARG_NOTICE => 'entregas' ) ) );
	}








	private static function save_notify( int $procedure_id ): void {

		$encendidos = (array) wp_unslash( $_POST[ self::FIELD_NOTIFY ] ?? array() );
		$encendidos = array_map( 'sanitize_key', array_filter( $encendidos, 'is_string' ) );
		$apagados   = array_values( array_diff( array_keys( ProcedureMetaKeys::notify_events() ), $encendidos ) );
		update_post_meta( $procedure_id, ProcedureMetaKeys::NOTIFY, $apagados );
		Shell::leave( self::url( $procedure_id, self::PANEL_NOTICES, array( self::ARG_NOTICE => 'avisos' ) ) );
	}







	private static function save_people_spec( int $procedure_id ): void {
		self::$rejected = PeopleSpecPanel::save( $procedure_id );
		if ( '' === self::$rejected ) {
			Shell::leave( self::url( $procedure_id, self::PANEL_PEOPLE, array( self::ARG_NOTICE => 'personas' ) ) );
		}
	}







	private static function save_links( int $procedure_id ): void {
		$claves = array( ProcedureMetaKeys::RESOLUTION_URL, ProcedureMetaKeys::PROVISIONAL_LIST_URL, ProcedureMetaKeys::FINAL_LIST_URL );
		$urls   = array();
		foreach ( $claves as $clave ) {
			$url = self::field( $clave );
			if ( ! ProcedureInput::is_acceptable_link( $url, self::meta( $procedure_id, $clave ) ) ) {
				self::$rejected = ProcedureInput::why( array( substr( $clave, 4 ) ) ) . ' Pegue la dirección completa, que empieza por https://: cópiela entera desde la barra del navegador.';
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


		$faltan = ProcedureInput::not_ready( self::first_term( $procedure_id, ProcedureTaxonomies::COURSE ), Applications::questions( $procedure_id ), self::meta( $procedure_id, ProcedureMetaKeys::OPENS_AT ) );
		if ( array() !== $faltan ) {
			self::$rejected = 'Todavía no se puede publicar. ' . implode( ' ', $faltan );
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








	private static function unpublish( int $procedure_id, int $user_id ): void {
		if ( ! ProcedureAccess::can_publish( $user_id, $procedure_id ) ) {
			self::$rejected = 'Su perfil no puede devolver este procedimiento a borrador.';
			return;
		}
		$result = wp_update_post(
			array(
				'ID'          => $procedure_id,
				'post_status' => 'draft',
			),
			true
		);
		if ( is_wp_error( $result ) ) {
			self::$rejected = 'No se pudo devolver el procedimiento a borrador. No se ha cambiado.';
			return;
		}
		Shell::leave( self::url( $procedure_id, self::PANEL_PUBLISH, array( self::ARG_NOTICE => 'borrador' ) ) );
	}








	private static function toggle_applications( int $procedure_id, int $user_id ): void {
		if ( 'publish' !== get_post_status( $procedure_id ) ) {
			self::$rejected = 'Solo se puede cerrar la inscripción de un procedimiento publicado.';
			return;
		}
		if ( ! ProcedureAccess::can_edit( $user_id, $procedure_id ) ) {
			self::$rejected = ProcedureAccess::why_not_editable( $user_id, $procedure_id );
			return;
		}
		$closed = ! (bool) get_post_meta( $procedure_id, ProcedureMetaKeys::APPLICATIONS_CLOSED, true );
		update_post_meta( $procedure_id, ProcedureMetaKeys::APPLICATIONS_CLOSED, $closed );
		if ( $closed xor (bool) get_post_meta( $procedure_id, ProcedureMetaKeys::APPLICATIONS_CLOSED, true ) ) {
			self::$rejected = 'No se pudo cambiar el cierre de solicitudes. No se ha aplicado el cambio.';
			return;
		}
		Shell::leave(
			self::url( $procedure_id, self::PANEL_PUBLISH, array( self::ARG_NOTICE => $closed ? 'cerradas' : 'reabiertas' ) )
		);
	}












	private static function save_archived( string $op, int $procedure_id, int $user_id ): void {
		$marcar = self::OP_ARCHIVE === $op;
		$puede  = $marcar
			? ProcedureAccess::can_archive( $user_id, $procedure_id )
			: ProcedureAccess::can_unarchive( $user_id );
		if ( ! $puede ) {
			$no_es_suyo     = 'Este procedimiento no es suyo: solo lo marca como histórico el ámbito que lo convoca.';
			self::$rejected = $marcar
				? ( ProcedureAccess::can_open( $user_id, $procedure_id ) ? ProcedureState::why_not_archivable( ProcedureState::of_post( $procedure_id ) ) : $no_es_suyo )
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

		if ( 'publish' !== get_post_status( $row_id ) ) {
			self::$rejected = 'Esa solicitud está retirada: restáurela antes de revisarla.';
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

		ApplicationActionsPanel::save_internal_note( $row_id, get_current_user_id() );


		Shell::leave( ApplicationsPanel::url( $procedure_id, ApplicationsPanel::filter( $_POST ), array( self::ARG_NOTICE => 'revisada' ) ) );
	}











	private static function bulk_review( int $procedure_id ): void {

		$ids    = ApplicationsPanel::posted_ids( $_POST );
		$filtro = ApplicationsPanel::filter( $_POST );

		$res = ApplicationsPanel::bulk( $procedure_id, $ids, self::field( self::FIELD_STATE ), self::textarea( self::FIELD_NOTE ) );
		if ( '' !== $res['error'] ) {
			self::$rejected = $res['error'];
			return;
		}
		Shell::leave(
			ApplicationsPanel::url(
				$procedure_id,
				$filtro,
				array(
					self::ARG_NOTICE              => ApplicationsPanel::NOTICE_BULK,
					ApplicationsPanel::ARG_DONE   => $res['done'],
					ApplicationsPanel::ARG_FAILED => $res['failed'],
				)
			)
		);
	}

















	private static function export( int $procedure_id, int $user_id ): void {
		$eleccion = ApplicationExport::sanitize_choice( $procedure_id, self::export_request() );
		ApplicationExport::remember( $user_id, $procedure_id, $eleccion );


		$ids = ApplicationsPanel::export_ids( $procedure_id, $_POST );

		$cuerpo = ApplicationExport::FORMAT_MOODLE === $eleccion['format']
			? ApplicationExport::moodle(
				$procedure_id,
				array(
					'who' => $eleccion['who'],
					'ids' => $ids,
				)
			)
			: ApplicationExport::csv( $procedure_id, $eleccion['columns'], $eleccion['shape'], $ids );
		$nombre = ApplicationExport::filename( (string) get_post_field( 'post_title', $procedure_id ), $eleccion['format'] );

		Shell::send_header( 'Content-Type: text/csv; charset=utf-8' );
		Shell::send_header( 'Content-Disposition: attachment; filename="' . $nombre . '"' );
		Shell::send_header( 'Content-Length: ' . strlen( $cuerpo ) );
		Shell::send_header( 'Cache-Control: no-store, no-cache, must-revalidate' );
		echo $cuerpo;
		Shell::leave();
	}






	private static function export_request(): array {

		$listas = array();



		foreach ( array(
			self::FIELD_EXPORT_COLUMNS => 'sanitize_text_field',
			self::FIELD_EXPORT_WHO     => 'sanitize_key',
		) as $campo => $limpia ) {
			$valor            = isset( $_POST[ $campo ] ) ? wp_unslash( $_POST[ $campo ] ) : array();
			$listas[ $campo ] = is_array( $valor ) ? array_map( $limpia, array_filter( $valor, 'is_string' ) ) : array();
		}
		$todas = ! empty( $_POST[ self::FIELD_EXPORT_ALL ] );

		return array(
			'all'     => $todas,
			'columns' => $listas[ self::FIELD_EXPORT_COLUMNS ],
			'shape'   => sanitize_key( self::field( self::FIELD_EXPORT_SHAPE ) ),
			'format'  => sanitize_key( self::field( self::FIELD_EXPORT_FORMAT ) ),
			'who'     => $listas[ self::FIELD_EXPORT_WHO ],
		);
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

		$m = self::fill( $m, $procedimiento, $user_id, $pedido, $hecho );



		$m['checklist'] = 'publish' === $procedimiento->post_status ? array() : ProcedureInput::checklist(
			array(
				'course'     => self::first_term( $procedure_id, ProcedureTaxonomies::COURSE ),
				'questions'  => Applications::questions( $procedure_id ),
				'opens_at'   => self::meta( $procedure_id, ProcedureMetaKeys::OPENS_AT ),
				'closes_at'  => self::meta( $procedure_id, ProcedureMetaKeys::CLOSES_AT ),
				'resolution' => self::meta( $procedure_id, ProcedureMetaKeys::RESOLUTION_URL ),
			)
		);
		$m['not_ready'] = ProcedureInput::blocking( $m['checklist'] );


		$m['answers']       = self::PANEL_QUESTIONS === $m['panel'] ? Applications::answer_counts( $procedure_id ) : array();
		$m['can_publish']   = $m['can_publish'] && array() === $m['not_ready'];
		$m['can_duplicate'] = ProcedureAccess::can_duplicate( $user_id, $procedure_id );

		$m['centre_options'] = CentreCatalog::choices( self::PANEL_DATA === $m['panel'], (array) $m['values'][ ProcedureMetaKeys::CENTRE_TYPES ], (array) $m['values'][ ProcedureMetaKeys::ISLANDS ] );


		$m['centre_warnings'] = CentreEligibility::warnings( ProcedureAccess::eligibility( $procedure_id ), array() === CentreCatalog::all(), array() === CentreCatalog::private_types() );
		return $m;
	}






	private static function blank(): array {
		return array(
			'aviso'                   => '',
			'aviso_tipo'              => 'aviso',
			'nuevo'                   => false,
			'procedure_id'            => 0,
			'title'                   => '',
			'panel'                   => self::PANEL_DATA,
			'panels'                  => array(),
			'flash'                   => array(
				'tipo'  => '',
				'texto' => '',
			),
			'can_edit'                => false,
			'groups'                  => array(),
			'state_notice'            => '',
			'can_review'              => false,
			'can_edit_custom_js'      => false,
			'can_publish'             => false,
			'can_duplicate'           => false,
			'not_ready'               => array(),
			'checklist'               => array(),
			'answers'                 => array(),
			'can_unpublish'           => false,
			'can_toggle_applications' => false,
			'applications_closed'     => false,
			'pending_documentation'   => false,
			'can_archive'             => false,
			'can_unarchive'           => false,
			'archived'                => false,
			'lock'                    => EditLock::none(),
			'state'                   => '',
			'state_label'             => '',
			'status'                  => '',
			'status_label'            => '',

			'audience_label'          => '',
			'area_names'              => '',
			'foreign_areas'           => array(),
			'view_url'                => '',
			'workspace_url'           => Shell::url( 'workspace' ),

			'help_new_url'            => Help::url( HelpContent::GUIDE_MANAGE, 'crear-o-duplicar' ),
			'help_questions_url'      => Help::url( HelpContent::GUIDE_MANAGE, 'taller-preguntas' ),
			'help_publish_url'        => Help::url( HelpContent::GUIDE_MANAGE, 'publicar' ),
			'values'                  => array(),
			'terms'                   => array(
				'area'   => array(),
				'course' => array(),
			),
			'ownerships'              => ProcedureMetaKeys::ownerships(),
			'centre_warnings'         => array(),
			'centre_options'          => array(
				'types'   => array(),
				'islands' => array(),
			),
			'centre_kinds'            => ProcedureMetaKeys::centre_kinds(),
			'audiences'               => ProcedureMetaKeys::audiences(),
			'header_colors'           => ProcedureMetaKeys::header_colors(),
			'questions'               => array(),
			'q_types'                 => ProcedureQuestions::types(),
			'q_max'                   => ProcedureQuestions::MAX,
			'applications'            => array(),
			'app_cols'                => array(),
			'export'                  => array(),
			'table'                   => array(),
			'review_states'           => ApplicationMetaKeys::review_states(),
			'notify_events'           => ProcedureMetaKeys::notify_events(),
			'people_spec'             => array(),
			'people'                  => array(),
			'observe'                 => array(),
			'actions'                 => array(),
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

		$m['procedure_id']       = $procedure_id;
		$m['title']              = (string) $procedimiento->post_title;
		$m['panels']             = $paneles;
		$m['panel']              = isset( $paneles[ $pedido ] ) ? $pedido : self::PANEL_DATA;
		$m['flash']              = self::flash( $hecho );
		$m['can_edit']           = ProcedureAccess::can_edit( $user_id, $procedure_id );
		$m['can_edit_custom_js'] = ProcedureAccess::can_edit_custom_js( $user_id, $procedure_id );
		$m['can_review']         = ProcedureAccess::can_review( $user_id, $procedure_id );
		$m['archived']           = ProcedureAccess::is_archived( $procedure_id );



		$m['lock'] = EditLock::status( $procedure_id, (bool) $m['can_edit'] );
		if ( (int) $m['lock']['owner'] > 0 ) {
			$m['can_edit'] = false;
		}
		$libre                        = 0 === (int) $m['lock']['owner'];
		$m['can_archive']             = $libre && ! $m['archived'] && ProcedureAccess::can_archive( $user_id, $procedure_id );
		$m['can_unarchive']           = $libre && $m['archived'] && ProcedureAccess::can_unarchive( $user_id );
		$m['can_publish']             = $m['can_edit'] && 'publish' !== $procedimiento->post_status && ProcedureAccess::can_publish( $user_id, $procedure_id );
		$m['can_unpublish']           = $m['can_edit']
			&& 'publish' === $procedimiento->post_status
			&& ProcedureAccess::can_publish( $user_id, $procedure_id );
		$m['can_toggle_applications'] = $libre && $m['can_edit'] && 'publish' === $procedimiento->post_status;
		$m['applications_closed']     = (bool) get_post_meta( $procedure_id, ProcedureMetaKeys::APPLICATIONS_CLOSED, true );
		$m['pending_documentation']   = false;
		if ( 0 < (int) $paneles[ self::PANEL_APPLICATIONS ]['count'] ) {
			foreach ( Applications::questions( $procedure_id ) as $question ) {
				if ( ! ProcedureQuestions::is_delivery( $question ) ) {
					continue;
				}
				if ( ProcedureQuestions::delivery_is_open( $question, ProcedureState::today() ) ) {
					$m['pending_documentation'] = true;
					break;
				}
			}
		}
		$m['state']       = ProcedureState::of_post( $procedure_id );
		$m['state_label'] = ProcedureState::label( (string) $m['state'], $m['applications_closed'] );



		foreach ( ProcedureState::groups() as $grupo ) {
			$m['groups'][ $grupo ] = true === $m['can_edit'] && ProcedureAccess::can_edit_group( $user_id, $procedure_id, $grupo );
		}
		$m['state_notice']   = true === $m['can_edit'] ? ProcedureState::why_locked( (string) $m['state'] ) : '';
		$m['status']         = (string) $procedimiento->post_status;
		$m['status_label']   = self::status_label( (string) $procedimiento->post_status );
		$m['area_names']     = self::area_names( ProcedureAccess::post_areas( $procedure_id ) );
		$audiencia           = (string) get_post_meta( $procedure_id, ProcedureMetaKeys::AUDIENCE, true );
		$m['audience_label'] = ProcedureChrome::audience_label( $audiencia, ProcedureView::who_can_apply( $procedure_id, $audiencia ) );
		$foreign_ids         = ProcedureAccess::can_edit_all_areas( $user_id ) ? array() : array_diff( ProcedureAccess::post_areas( $procedure_id ), ProcedureAccess::scope_areas( $user_id ) );
		$all_labels          = ProcedureTaxonomies::area_options( 0, true );
		$m['foreign_areas']  = array_values( array_intersect_key( $all_labels, array_flip( $foreign_ids ) ) );
		$m['view_url']       = (string) get_permalink( $procedimiento );
		$m['values']         = self::values( $procedure_id );
		$m['terms']          = self::term_lists( $user_id );
		$m['questions']      = Applications::questions( $procedure_id );
		$m['deliveries']     = array_values( array_filter( $m['questions'], array( ProcedureQuestions::class, 'is_delivery' ) ) );
		$m['people_spec']    = PeopleSpecPanel::model( $procedure_id, '' !== self::$rejected );
		$m['observe']        = ObserveSettings::model( $procedure_id );

		if ( self::PANEL_APPLICATIONS === $m['panel'] ) {



			$m = array_merge( $m, ApplicationsPanel::model( $procedure_id, $user_id, (array) $m['questions'], $_GET ) );
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
		if ( ProcedureDuplicate::NOTICE === $hecho ) {

			return ProcedureDuplicate::notice( $_GET );
		}
		if ( isset( ApplicationActionsPanel::NOTICES[ $hecho ] ) ) {
			return array(
				'tipo'  => 'ok',
				'texto' => ApplicationActionsPanel::NOTICES[ $hecho ],
			);
		}
		if ( ApplicationsPanel::NOTICE_BULK === $hecho ) {

			return ApplicationsPanel::bulk_notice( $_GET );
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
				? ProcedureInput::stored_ownership( get_post_meta( $procedure_id, ProcedureMetaKeys::OWNERSHIP, true ) )
				: array(),
			ProcedureMetaKeys::CENTRE_KINDS         => self::centre_kinds_value( $procedure_id ),
			ProcedureMetaKeys::REQUIRES_COORDINATOR => Applications::coordinator_count( $procedure_id ) > 0 ? '1' : '',
			ProcedureMetaKeys::COMMITMENTS          => self::meta( $procedure_id, ProcedureMetaKeys::COMMITMENTS ),
			ProcedureMetaKeys::CUSTOM_CSS           => self::meta( $procedure_id, ProcedureMetaKeys::CUSTOM_CSS ),
			ProcedureMetaKeys::CUSTOM_JS            => self::meta( $procedure_id, ProcedureMetaKeys::CUSTOM_JS ),
			ProcedureMetaKeys::RESOLUTION_URL       => self::meta( $procedure_id, ProcedureMetaKeys::RESOLUTION_URL ),
			ProcedureMetaKeys::PROVISIONAL_LIST_URL => self::meta( $procedure_id, ProcedureMetaKeys::PROVISIONAL_LIST_URL ),
			ProcedureMetaKeys::FINAL_LIST_URL       => self::meta( $procedure_id, ProcedureMetaKeys::FINAL_LIST_URL ),
			ProcedureMetaKeys::NOTIFY               => $procedure_id > 0 ? (array) get_post_meta( $procedure_id, ProcedureMetaKeys::NOTIFY, true ) : array(),
		);
		$guardado[ ProcedureMetaKeys::COORDINATOR_COUNT ] = (string) Applications::coordinator_count( $procedure_id );


		$guardado['saved_links']                        = array_intersect_key( $guardado, array_flip( array( ProcedureMetaKeys::RESOLUTION_URL, ProcedureMetaKeys::PROVISIONAL_LIST_URL, ProcedureMetaKeys::FINAL_LIST_URL ) ) );
		$acotados                                       = CentreEligibility::form_values( ProcedureAccess::eligibility( $procedure_id ) );
		$guardado[ ProcedureMetaKeys::CENTRE_TYPES ]    = $acotados['centre_types'];
		$guardado[ ProcedureMetaKeys::ISLANDS ]         = $acotados['islands'];
		$guardado[ ProcedureMetaKeys::ALLOWED_CENTRES ] = $acotados['allowed_centres'];
		if ( '' === self::$rejected ) {
			return $guardado;
		}



		$tecleado = self::submitted_data( $procedure_id );
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
			ProcedureMetaKeys::CENTRE_KINDS    => 'centre_kinds',
			ProcedureMetaKeys::COMMITMENTS     => 'commitments',
			ProcedureMetaKeys::CENTRE_TYPES    => 'centre_types',
			ProcedureMetaKeys::ISLANDS         => 'islands',
			ProcedureMetaKeys::ALLOWED_CENTRES => 'allowed_centres',
		);

		$enviados                 = array_keys( (array) $_POST );
		$tecleado['ownership']    = ProcedureInput::ownership( $tecleado['ownership'] );
		$tecleado['centre_kinds'] = ProcedureInput::centre_kinds( $tecleado['centre_kinds'] );
		foreach ( $mapa as $campo => $clave ) {
			if ( in_array( $campo, $enviados, true ) ) {
				$valor              = $tecleado[ $clave ];
				$guardado[ $campo ] = is_array( $valor ) ? $valor : (string) $valor;
			}
		}
		if ( in_array( ProcedureMetaKeys::COORDINATOR_COUNT, $enviados, true ) ) {
			$guardado[ ProcedureMetaKeys::COORDINATOR_COUNT ] = (string) $tecleado['coordinator_count'];
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

}








namespace Prc\PublicFront;

use Prc\Access\ProcedureAccess;
use Prc\Domain\ProcedureCopy;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ProcedurePostType;
use Prc\Taxonomy\ProcedureTaxonomies;










if ( ! class_exists( 'Prc\PublicFront\ProcedureDuplicate', false ) ) {
final class ProcedureDuplicate {




	public const NOTICE = 'duplicado';




	public const ARG_SOURCE = 'origen';




	public const ARG_NO_JS = 'sin_js';




	public const ARG_NO_CONTACTS = 'sin_contactos';








	public static function handle( int $procedure_id, int $user_id ): string {
		$copia = self::duplicate( $procedure_id, $user_id );
		if ( is_wp_error( $copia ) ) {
			return $copia->get_error_message();
		}
		$args = array(
			ProcedureEditor::ARG_NOTICE => self::NOTICE,
			self::ARG_SOURCE            => $procedure_id,
		);
		if ( $copia['js_left'] ) {
			$args[ self::ARG_NO_JS ] = 1;
		}
		if ( $copia['contacts_left'] ) {
			$args[ self::ARG_NO_CONTACTS ] = 1;
		}



		EditLock::release( $procedure_id );
		Shell::leave( ProcedureEditor::url( $copia['id'], ProcedureEditor::PANEL_DATA, $args ) );
		return '';
	}








	public static function duplicate( int $procedure_id, int $user_id ) {
		$areas = null;
		if ( ! ProcedureAccess::can_duplicate( $user_id, $procedure_id, $areas ) ) {
			return new \WP_Error(
				'prc_duplicate_forbidden',
				ProcedureAccess::can_open( $user_id, $procedure_id )
					? 'Su perfil no puede crear procedimientos: hace falta gestionar procedimientos y tener un ámbito asignado.'
					: ProcedureAccess::why_not_editable( $user_id, $procedure_id )
			);
		}

		if ( array() === $areas ) {
			return new \WP_Error( 'prc_duplicate_area', 'El duplicado necesita al menos un ámbito, y ni el original ni su perfil tienen uno que pueda usar.' );
		}

		$original = get_post( $procedure_id );
		$id       = wp_insert_post(
			array(
				'post_type'    => ProcedurePostType::POST_TYPE,
				'post_title'   => wp_slash( ProcedureCopy::title( (string) $original->post_title ) ),
				'post_content' => wp_slash( (string) $original->post_content ),
				'post_excerpt' => wp_slash( (string) $original->post_excerpt ),
				'post_status'  => 'draft',
				'post_author'  => $user_id,
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			return new \WP_Error( 'prc_duplicate_failed', 'No se ha podido duplicar el procedimiento: ' . $id->get_error_message() );
		}
		$id = (int) $id;


		wp_set_object_terms( $id, $areas, ProcedureTaxonomies::AREA, false );
		$copia = ProcedureCopy::meta( self::source( $procedure_id ), ProcedureAccess::can_edit_custom_js( $user_id, $procedure_id ) );


		$sin_contactos = array() !== array_diff( ProcedureAccess::post_areas( $procedure_id ), $areas )
			&& isset( $copia['meta'][ ProcedureMetaKeys::CONTACT_EMAILS ] );
		if ( $sin_contactos ) {
			unset( $copia['meta'][ ProcedureMetaKeys::CONTACT_EMAILS ] );
		}
		foreach ( $copia['meta'] as $clave => $valor ) {
			update_post_meta( $id, $clave, wp_slash( $valor ) );
		}
		$imagen = (int) get_post_thumbnail_id( $procedure_id );
		if ( $imagen > 0 ) {
			update_post_meta( $id, '_thumbnail_id', $imagen );
		}


		do_action( 'prc_procedure_created', $id );

		return array(
			'id'            => $id,
			'js_left'       => $copia['js_left'],
			'contacts_left' => $sin_contactos,
		);
	}







	private static function source( int $procedure_id ): array {
		$out = array();
		foreach ( array_keys( ProcedureCopy::policy() ) as $clave ) {
			if ( metadata_exists( 'post', $procedure_id, $clave ) ) {
				$out[ $clave ] = get_post_meta( $procedure_id, $clave, true );
			}
		}
		return $out;
	}









	public static function notice( array $request ): array {
		$origen = absint( $request[ self::ARG_SOURCE ] ?? 0 );
		$texto  = ProcedureAccess::can_open( get_current_user_id(), $origen )
			? 'Ha duplicado «' . get_post_field( 'post_title', $origen, 'raw' ) . '».'
			: 'Ha duplicado el procedimiento.';
		$texto .= ' Revise las fechas y el curso antes de publicarlo.'
			. ' No se copian los plazos, el curso, los enlaces de la resolución y de los listados, ni las solicitudes.';
		if ( ! empty( $request[ self::ARG_NO_CONTACTS ] ) ) {
			$texto .= ' Los correos de contacto no se han copiado, porque el duplicado no tiene todos los ámbitos del original: indique los correos de contacto en «Datos».';
		}
		if ( ! empty( $request[ self::ARG_NO_JS ] ) ) {
			$texto .= ' El JavaScript propio del original no se ha copiado: no tiene permiso para el código JavaScript de este procedimiento.';
		}
		return array(
			'tipo'  => 'ok',
			'texto' => $texto,
		);
	}
}

}








namespace Prc\PublicFront\View;

use Prc\Domain\ProcedureQuestions;
use Prc\PublicFront\ApplyForm;







if ( ! class_exists( 'Prc\PublicFront\View\MeasuredQuestionView', false ) ) {
final class MeasuredQuestionView {















	public static function html( array $p, $valor, array $invalid = array() ): string {
		$key     = (string) $p['key'];
		$id      = 'prc-q-' . $key;
		$nombre  = ApplyForm::FIELD_ANSWERS . '[' . $key . ']';
		$obliga  = ! empty( $p['required'] );
		$ayuda   = (string) $p['help'];
		$codigo  = 'answer:' . $key;
		$pie     = FieldErrorView::attrs( $invalid, $codigo, '' !== $ayuda ? $id . '-ayuda' : '' );
		$rejilla = ProcedureQuestions::TYPE_GRID === (string) $p['type'];

		$control = '';
		if ( $rejilla ) {
			$dadas = is_array( $valor ) ? $valor : array();
			foreach ( (array) ( $p['rows'] ?? array() ) as $fila ) {
				$rk       = (string) $fila['key'];
				$control .= '<div class="prc-campo prc-campo--cuarto">'
					. '<label class="prc-campo__etiqueta" for="' . esc_attr( $id . '-' . $rk ) . '">' . esc_html( (string) $fila['label'] ) . '</label>'
					. self::number_input( $id . '-' . $rk, $nombre . '[' . $rk . ']', $dadas[ $rk ] ?? '', 0, null, $obliga )
					. '</div>';
			}
			$control = '<div class="prc-opciones prc-rejilla" role="group" id="' . esc_attr( $id ) . '" aria-labelledby="' . esc_attr( $id . '-rotulo' ) . '"' . $pie . '>' . $control . '</div>';
		} elseif ( ProcedureQuestions::TYPE_NUMBER === (string) $p['type'] ) {
			$control = self::number_input( $id, $nombre, $valor, ProcedureQuestions::integer( $p['min'] ?? null ) ?? 0, ProcedureQuestions::integer( $p['max'] ?? null ), $obliga, $pie );
		} else {
			$control = '<input type="date" class="prc-control" id="' . esc_attr( $id ) . '" name="' . esc_attr( $nombre ) . '" value="' . esc_attr( is_scalar( $valor ) ? (string) $valor : '' ) . '"'
				. ( $obliga ? ' required' : '' ) . $pie . ' />';
		}

		return '<div class="prc-campo prc-pregunta ' . esc_attr( $rejilla ? 'prc-campo--completo' : 'prc-campo--medio' ) . '">'
			. self::label( $rejilla ? $id . '-rotulo' : $id, (string) $p['label'], $obliga, $rejilla )
			. $control
			. FieldErrorView::feedback( $invalid, $codigo )
			. ( '' !== $ayuda ? '<div class="prc-campo__ayuda" id="' . esc_attr( $id . '-ayuda' ) . '">' . esc_html( $ayuda ) . '</div>' : '' )
			. '</div>';
	}













	private static function number_input( string $id, string $nombre, $valor, int $minimo, ?int $maximo, bool $obliga, string $extra = '' ): string {
		return '<input type="number" class="prc-control" inputmode="numeric" step="1" id="' . esc_attr( $id ) . '" name="' . esc_attr( $nombre ) . '"'
			. ' value="' . esc_attr( is_scalar( $valor ) ? (string) $valor : '' ) . '" min="' . esc_attr( (string) $minimo ) . '"'
			. ( null !== $maximo ? ' max="' . esc_attr( (string) $maximo ) . '"' : '' )
			. ( $obliga ? ' required' : '' ) . $extra . ' />';
	}










	private static function label( string $id, string $rotulo, bool $obligatorio, bool $grupo ): string {
		$atributo = $grupo ? ' id="' . esc_attr( $id ) . '"' : ' for="' . esc_attr( $id ) . '"';
		$etiqueta = $grupo ? 'p' : 'label';
		return '<' . $etiqueta . ' class="prc-campo__etiqueta"' . $atributo . '>'
			. esc_html( $rotulo )
			. ( $obligatorio ? ' <span class="prc-campo__obligatorio" aria-hidden="true">*</span>' : '' )
			. '</' . $etiqueta . '>';
	}
}

}








namespace Prc\PublicFront\View;

use Prc\PublicFront\Assets;
use Prc\PublicFront\PeopleForm;
use Prc\PublicFront\PeopleSpecPanel;
use Prc\PublicFront\ProcedureEditor;
use Prc\PublicFront\Shell;










if ( ! class_exists( 'Prc\PublicFront\View\PeopleView', false ) ) {
final class PeopleView {







	public static function section( array $p ): string {
		if ( empty( $p['show'] ) ) {
			return '';
		}
		$flash = (array) ( $p['flash'] ?? array() );
		$filas = (array) $p['rows'];
		$lleno = count( $filas ) >= (int) $p['max'];

		ob_start();
		?>
		<section class="prc-seccion prc-personas" id="<?php echo esc_attr( PeopleForm::ANCHOR ); ?>" aria-labelledby="prc-personas-titulo">
			<h3 class="prc-seccion__titulo" id="prc-personas-titulo">PERSONAS QUE PARTICIPAN</h3>
			<div class="prc-seccion__cuerpo">
				<?php echo Shell::notice( (string) ( $flash['tipo'] ?? '' ), (string) ( $flash['texto'] ?? '' ) );?>
				<?php if ( '' !== (string) ( $p['clash_row'] ?? '' ) ) : ?>
					<p class="prc-campo--completo"><a href="<?php echo esc_attr( '#' . self::row_anchor( (string) $p['clash_row'] ) ); ?>">Ir a su fila</a></p>
				<?php endif; ?>
				<?php echo self::until( $p );?>
				<p class="prc-campo__ayuda prc-campo--completo">
					<?php echo esc_html( (string) $p['window'] . ' ' . self::limits( (int) $p['min'], (int) $p['max'], count( $filas ) ) ); ?>
				</p>
				<?php if ( (int) $p['application_id'] > 0 && empty( $p['complete'] ) ) : ?>
					<?php echo Shell::notice( 'aviso', sprintf( 'La lista tiene %1$d %2$s y el procedimiento pide al menos %3$d: la solicitud queda incompleta hasta que la complete.', count( $filas ), 1 === count( $filas ) ? 'persona' : 'personas', (int) $p['min'] ) );?>
				<?php endif; ?>
				<?php echo Shell::notice( 'aviso', (string) $p['why'] );?>
				<?php echo self::table( $p, $filas, ! empty( $p['can_edit'] ) );?>
				<?php if ( ! empty( $p['can_edit'] ) && $lleno ) : ?>
					<p class="prc-campo--completo">La lista ha llegado al máximo de personas que admite este procedimiento. Para añadir a alguien, dé antes de baja a otra persona o cámbiela.</p>
				<?php elseif ( ! empty( $p['can_edit'] ) ) : ?>
					<?php if ( array() !== $filas ) : ?>
						<p class="prc-campo__ayuda prc-campo--completo">¿Viene a sustituir a alguien de la lista? Use «Sustituir o corregir» en su fila: así queda en el historial como sustitución y no se duplica.</p>
					<?php endif; ?>
					<details class="prc-personas__alta prc-campo--completo"<?php echo PeopleForm::NEW_ROW === (string) $p['typed_row'] ? ' open' : ''; ?>>
						<summary>Dar de alta a una persona</summary>
						<?php echo self::form( $p, PeopleForm::OP_ADD, PeopleForm::NEW_ROW === (string) $p['typed_row'] ? (array) $p['typed'] : array() );?>
					</details>
				<?php endif; ?>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}










	private static function until( array $p ): string {
		$dia = (string) ( $p['until'] ?? '' );
		if ( '' === $dia || ( empty( $p['can_edit'] ) && (int) $p['application_id'] > 0 ) ) {
			return '';
		}
		return '<p class="prc-personas__plazo prc-campo--completo"><strong>'
			. esc_html( 'Puede dar de alta, cambiar o sustituir personas hasta el ' . $dia . ', incluido.' )
			. '</strong></p>';
	}









	private static function limits( int $min, int $max, int $count ): string {
		$pide = $min > 0 ? sprintf( 'Pide entre %1$d y %2$d personas.', $min, $max ) : sprintf( 'Admite hasta %d personas.', $max );
		return $pide . ' ' . sprintf( 'Ahora hay %d.', $count );
	}









	private static function table( array $p, array $filas, bool $editable ): string {
		if ( array() === $filas ) {
			return '<p class="prc-vacio prc-campo--completo">Todavía no hay nadie en la lista.</p>';
		}
		ob_start();
		?>
		<div class="prc-tabla-caja prc-campo--completo">
			<table class="prc-tabla prc-tabla--densa">
				<caption class="screen-reader-text">Personas de la solicitud</caption>
				<thead>
					<tr>
						<th class="prc-th" scope="col">Nombre y apellidos</th>
						<th class="prc-th" scope="col">DNI o NIE</th>
						<th class="prc-th" scope="col">Correo</th>
						<th class="prc-th" scope="col">Teléfono</th>
						<th class="prc-th" scope="col">Papel</th>
						<th class="prc-th" scope="col">Grupo</th>
						<?php if ( $editable ) : ?>
							<th class="prc-th" scope="col">Acciones</th>
						<?php endif; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $filas as $fila ) : ?>
						<tr id="<?php echo esc_attr( self::row_anchor( (string) $fila['id'] ) ); ?>">
							<?php echo self::cells( $fila );?>
							<?php if ( $editable ) : ?>
								<td class="prc-celda" data-rotulo="Acciones">
									<?php $tecleado = (string) $p['typed_row'] === $fila['id'] ? (array) $p['typed'] : array(); ?>
									<details class="prc-personas__cambio"<?php echo in_array( $fila['id'], array( (string) $p['typed_row'], (string) ( $p['clash_row'] ?? '' ) ), true ) ? ' open' : ''; ?>>
										<summary class="<?php echo esc_attr( Assets::button_class() . ' prc-mini' ); ?>">Sustituir o corregir</summary>
										<?php echo self::form( $p, PeopleForm::OP_CHANGE, array() !== $tecleado ? $tecleado : $fila, $fila );?>
									</details>
									<?php echo self::remove_form( $p, $fila );?>
								</td>
							<?php endif; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		return (string) ob_get_clean();
	}







	private static function row_anchor( string $id ): string {
		return 'prc-persona-' . $id;
	}







	private static function cells( array $fila ): string {
		$celdas = array(
			'Nombre y apellidos' => trim( $fila['name'] . ' ' . $fila['surname'] ),
			'DNI o NIE'          => $fila['dni'],
			'Correo'             => $fila['email'],
			'Teléfono'           => $fila['phone'],
			'Papel'              => $fila['role_label'],
			'Grupo'              => $fila['group_label'],
		);
		$out    = '';
		foreach ( $celdas as $rotulo => $valor ) {
			$out .= '<td class="prc-celda" data-rotulo="' . esc_attr( $rotulo ) . '">'
				. ( '' !== (string) $valor ? esc_html( (string) $valor ) : '<span class="prc-sindato" title="Sin datos">&mdash;</span>' )
				. '</td>';
		}
		return $out;
	}









	private static function hidden( array $p, string $op, array $fila ): string {
		$campos = array(
			PeopleForm::FIELD_OP          => $op,
			PeopleForm::FIELD_APPLICATION => (string) (int) $p['application_id'],
		);
		if ( array() !== $fila ) {
			$campos[ PeopleForm::FIELD_ROW ]   = (string) $fila['id'];
			$campos[ PeopleForm::FIELD_PRINT ] = (string) $fila['print'];
		}
		$out = wp_nonce_field( PeopleForm::nonce_action( $op, (int) $p['application_id'] ), PeopleForm::NONCE_FIELD, false, false );
		foreach ( $campos as $nombre => $valor ) {
			$out .= '<input type="hidden" name="' . esc_attr( $nombre ) . '" value="' . esc_attr( $valor ) . '" />';
		}
		return $out;
	}










	private static function form( array $p, string $op, array $valores, array $fila = array() ): string {
		$base   = 'prc-person-' . ( array() !== $fila ? (string) $fila['id'] : PeopleForm::NEW_ROW ) . '-';
		$campos = array(
			'name'    => array( 'Nombre', 'text', true, 'given-name' ),
			'surname' => array( 'Apellidos', 'text', true, 'family-name' ),
			'dni'     => array( 'DNI o NIE', 'text', true, 'off' ),
			'email'   => array( 'Correo electrónico', 'email', true, 'email' ),
			'phone'   => array( 'Teléfono', 'tel', false, 'tel' ),
		);

		ob_start();
		?>
		<form class="prc-form prc-personas__form" method="post" action="<?php echo esc_url( '#' . PeopleForm::ANCHOR ); ?>">
			<?php echo self::hidden( $p, $op, $fila );?>
			<?php foreach ( $campos as $campo => list( $rotulo, $tipo, $obliga, $auto ) ) : ?>
				<div class="prc-campo prc-campo--cuarto">
					<label class="prc-campo__etiqueta" for="<?php echo esc_attr( $base . $campo ); ?>"><?php echo esc_html( $rotulo ); ?><?php echo $obliga ? ' <span class="prc-campo__obligatorio" aria-hidden="true">*</span>' : ''; ?></label>
					<input class="prc-control" type="<?php echo esc_attr( $tipo ); ?>" id="<?php echo esc_attr( $base . $campo ); ?>"
						name="<?php echo esc_attr( PeopleForm::FIELD_PERSON . '[' . $campo . ']' ); ?>" autocomplete="<?php echo esc_attr( $auto ); ?>"
						value="<?php echo esc_attr( (string) ( $valores[ $campo ] ?? '' ) ); ?>"<?php echo $obliga ? ' required' : ''; ?> />
				</div>
			<?php endforeach; ?>
			<?php echo self::select( $base . 'role', 'role', 'Papel', (array) $p['roles'], (string) ( $valores['role'] ?? '' ), true );?>
			<?php if ( array() !== (array) $p['groups'] ) : ?>
				<?php $exigen = (array) ( $p['group_roles'] ?? array() ); ?>
				<?php echo self::select( $base . 'group', 'group', array() === $exigen ? 'Grupo' : 'Grupo (obligatorio para: ' . implode( ', ', $exigen ) . ')', array( '' => '— Sin grupo —' ) + (array) $p['groups'], (string) ( $valores['group'] ?? '' ), false );?>
			<?php endif; ?>
			<div class="prc-campo prc-campo--completo">
				<button type="submit" class="<?php echo esc_attr( Assets::button_class() ); ?>"><?php echo esc_html( PeopleForm::OP_ADD === $op ? 'Dar de alta' : 'Guardar los cambios de la persona' ); ?></button>
			</div>
		</form>
		<?php
		return (string) ob_get_clean();
	}












	private static function select( string $id, string $campo, string $rotulo, array $opciones, string $elegida, bool $obliga ): string {
		$out = '<div class="prc-campo prc-campo--cuarto"><label class="prc-campo__etiqueta" for="' . esc_attr( $id ) . '">' . esc_html( $rotulo )
			. ( $obliga ? ' <span class="prc-campo__obligatorio" aria-hidden="true">*</span>' : '' ) . '</label>'
			. '<select class="prc-control" id="' . esc_attr( $id ) . '" name="' . esc_attr( PeopleForm::FIELD_PERSON . '[' . $campo . ']' ) . '"' . ( $obliga ? ' required' : '' ) . '>'
			. ( $obliga ? '<option value="">— Elija —</option>' : '' );
		foreach ( $opciones as $clave => $texto ) {
			$out .= '<option value="' . esc_attr( (string) $clave ) . '"' . selected( (string) $clave, $elegida, false ) . '>' . esc_html( (string) $texto ) . '</option>';
		}
		return $out . '</select></div>';
	}








	private static function remove_form( array $p, array $fila ): string {
		$nombre   = trim( $fila['name'] . ' ' . $fila['surname'] );
		$pregunta = sprintf( '¿Dar de baja a %s de la lista de personas? Quedará anotado en el historial de la solicitud.', $nombre );
		return '<form class="prc-acciones" method="post" action="' . esc_url( '#' . PeopleForm::ANCHOR ) . '"'
			. ' data-prc-confirm="' . esc_attr( $pregunta ) . '" data-prc-confirm-ok="Dar de baja">'
			. self::hidden( $p, PeopleForm::OP_REMOVE, $fila )
			. '<button type="submit" class="' . esc_attr( Assets::button_class( 'danger' ) ) . '" title="' . esc_attr( 'Dar de baja a ' . $nombre ) . '">Dar de baja</button>'
			. '</form>';
	}










	public static function review( array $r ): string {
		if ( empty( $r['show'] ) ) {
			return '';
		}
		$filas = (array) $r['rows'];
		ob_start();
		?>
		<div class="prc-personas-revision">
			<p><strong>Personas</strong>
				<?php echo esc_html( sprintf( '(%d)', count( $filas ) ) ); ?>
				<?php if ( empty( $r['complete'] ) ) : ?>
					<span class="<?php echo esc_attr( Assets::state_class( 'amend' ) ); ?>"><?php echo esc_html( sprintf( 'Incompleta: pide al menos %d', (int) $r['min'] ) ); ?></span>
				<?php endif; ?>
			</p>
			<?php echo self::table( array(), $filas, false );?>
		</div>
		<?php
		return (string) ob_get_clean();
	}








	public static function spec_panel( array $m ): string {
		$p  = (array) ( $m['people_spec'] ?? array() );
		$v  = (array) ( $p['values'] ?? array() );
		$op = ProcedureEditor::OP_PEOPLE_SPEC;
		if ( ! empty( $p['teachers'] ) ) {
			return '<section class="prc-seccion-campos"><h2 class="prc-seccion__titulo">Personas de la solicitud</h2>'
				. '<p class="prc-intro">Este procedimiento es para profesorado: cada solicitud es de una sola persona y no tiene lista de personas.</p></section>';
		}

		ob_start();
		?>
		<form class="prc-form prc-form--taller" method="post" action="">
			<?php wp_nonce_field( ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
			<fieldset class="prc-seccion-campos">
				<legend class="prc-seccion__titulo">Personas de la solicitud</legend>
				<p class="prc-intro">Con la lista encendida, cada centro da de alta, cambia o da de baja a las personas que participan, con su papel y, si hace falta, su grupo, sin pedir que se reabra la solicitud. Cada cambio queda en el historial de la solicitud y se avisa a los correos de contacto.</p>
				<?php echo self::check( PeopleSpecPanel::FIELD_ENABLED, 'Pedir a cada centro la lista de personas que participan', $v, 'enabled' );?>
				<?php echo self::role_rows( $p );?>
				<div class="prc-campo prc-campo--medio">
					<label class="prc-campo__etiqueta" for="prc-people-groups">Grupos <span class="prc-campo__opcional">(opcional)</span></label>
					<textarea class="prc-control" id="prc-people-groups" name="<?php echo esc_attr( PeopleSpecPanel::FIELD_GROUPS ); ?>" rows="5" aria-describedby="prc-people-groups-ayuda"><?php echo esc_textarea( (string) ( $v['groups'] ?? '' ) ); ?></textarea>
					<p class="prc-campo__ayuda" id="prc-people-groups-ayuda"><?php echo esc_html( sprintf( 'Uno por línea, hasta %d; por ejemplo «Eje 1». Para quitar uno, deje su línea en blanco.', (int) ( $p['max_groups'] ?? 0 ) ) ); ?></p>
				</div>
				<?php echo self::number( PeopleSpecPanel::FIELD_MIN, 'Mínimo de personas', $v, 'min', 'Por debajo, la solicitud se marca incompleta; no se rechaza.', (int) ( $p['max_people'] ?? 0 ) );?>
				<?php echo self::number( PeopleSpecPanel::FIELD_MAX, 'Máximo de personas', $v, 'max', sprintf( 'Hasta %d.', (int) ( $p['max_people'] ?? 0 ) ), (int) ( $p['max_people'] ?? 0 ) );?>
				<?php echo self::day_field( PeopleSpecPanel::FIELD_OPENS, 'La lista se abre el', $v, 'opens_at' );?>
				<?php echo self::day_field( PeopleSpecPanel::FIELD_CLOSES, 'Y se cierra el', $v, 'closes_at' );?>
				<p class="prc-campo__ayuda prc-campo--completo">Los dos días cuentan. Sin fechas, la lista se gestiona mientras se pueda modificar la solicitud; con ellas, aunque el plazo de solicitud esté cerrado.</p>
				<?php echo self::check( PeopleSpecPanel::FIELD_UNIQUE, 'Una sola persona por papel y grupo', $v, 'unique_per_group' );?>
				<?php echo self::check( PeopleSpecPanel::FIELD_MULTI_ROLE, 'Una persona puede tener varios papeles (sin marcar, la misma persona no entra dos veces en la lista)', $v, 'multi_role' );?>
				<?php echo self::check( PeopleSpecPanel::FIELD_ADMITTED, 'Solo las solicitudes admitidas gestionan su lista', $v, 'admitted_only' );?>
			</fieldset>
			<?php echo PanelParts::submit( 'Guardar la lista de personas' );?>
		</form>
		<?php
		return (string) ob_get_clean();
	}








	private static function role_rows( array $p ): string {
		$max   = (int) ( $p['max_roles'] ?? 0 );
		$filas = array_slice( (array) ( $p['role_rows'] ?? array() ), 0, $max );
		$vacia = array(
			'label'       => '',
			'needs_group' => false,
		);
		$filas = array_merge( $filas, array_fill( 0, max( 0, min( 2, $max - count( $filas ) ) ), $vacia ) );
		$out   = '<fieldset class="prc-campo prc-campo--completo prc-papeles"><legend class="prc-campo__etiqueta">Papeles</legend>'
			. '<p class="prc-campo__ayuda" id="prc-people-roles-ayuda">' . esc_html( sprintf( 'Uno por fila, hasta %d; por ejemplo «Coordinación general» y, en otra fila, «Coordinación de eje» con «Exige indicar el grupo» marcada. Para quitar uno, deje su rótulo en blanco; cambiarle el nombre no cambia a nadie de papel.', $max ) ) . '</p>';
		foreach ( $filas as $i => $fila ) {
			$id   = 'prc-people-role-' . $i;
			$out .= '<div class="prc-papel">'
				. '<label class="screen-reader-text" for="' . esc_attr( $id ) . '">' . esc_html( 'Papel ' . ( $i + 1 ) ) . '</label>'
				. '<input class="prc-control" type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( PeopleSpecPanel::FIELD_ROLES . '[' . $i . ']' ) . '" value="' . esc_attr( (string) $fila['label'] ) . '" aria-describedby="prc-people-roles-ayuda" />'
				. '<span class="prc-opcion"><input type="checkbox" id="' . esc_attr( $id . '-grupo' ) . '" name="' . esc_attr( PeopleSpecPanel::FIELD_ROLE_GROUP . '[' . $i . ']' ) . '" value="1"' . checked( ! empty( $fila['needs_group'] ), true, false ) . ' />'

				. '<label for="' . esc_attr( $id . '-grupo' ) . '"><span class="screen-reader-text">' . esc_html( 'Papel ' . ( $i + 1 ) . ': ' ) . '</span>Exige indicar el grupo</label></span>'
				. '</div>';
		}
		return $out . '</fieldset>';
	}










	private static function check( string $name, string $rotulo, array $v, string $clave ): string {
		return '<div class="prc-campo prc-campo--completo"><div class="prc-opciones prc-opciones--vertical"><div class="prc-opcion">'
			. '<input type="checkbox" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="1"' . checked( '' !== (string) ( $v[ $clave ] ?? '' ), true, false ) . ' />'
			. '<label for="' . esc_attr( $name ) . '">' . esc_html( $rotulo ) . '</label></div></div></div>';
	}












	private static function number( string $name, string $rotulo, array $v, string $clave, string $ayuda, int $max ): string {
		return '<div class="prc-campo prc-campo--cuarto"><label class="prc-campo__etiqueta" for="' . esc_attr( $name ) . '">' . esc_html( $rotulo ) . '</label>'
			. '<input class="prc-control" type="number" min="0" max="' . esc_attr( (string) $max ) . '" step="1" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '"'
			. ' value="' . esc_attr( (string) ( $v[ $clave ] ?? '' ) ) . '" aria-describedby="' . esc_attr( $name . '-ayuda' ) . '" />'
			. '<p class="prc-campo__ayuda" id="' . esc_attr( $name . '-ayuda' ) . '">' . esc_html( $ayuda ) . '</p></div>';
	}










	private static function day_field( string $name, string $rotulo, array $v, string $clave ): string {
		return '<div class="prc-campo prc-campo--cuarto"><label class="prc-campo__etiqueta" for="' . esc_attr( $name ) . '">' . esc_html( $rotulo ) . '</label>'
			. '<input class="prc-control" type="date" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) ( $v[ $clave ] ?? '' ) ) . '" /></div>';
	}
}

}








namespace Prc\PublicFront\View;

use Prc\Meta\ApplicationMetaKeys;
use Prc\PublicFront\Assets;
use Prc\PublicFront\PeopleForm;
use Prc\PublicFront\Shell;









if ( ! class_exists( 'Prc\PublicFront\View\ApplicationProgressView', false ) ) {
final class ApplicationProgressView {






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







	public static function steps( array $p ): string {
		$pasos = (array) ( $p['steps'] ?? array() );
		if ( array() === $pasos ) {
			return '';
		}
		$actual = false;
		$out    = '<nav class="prc-pasos-caja" aria-label="Pasos de la solicitud"><ol class="prc-pasos">';
		foreach ( $pasos as $paso ) {
			$hecho  = ! empty( $paso['done'] );
			$marca  = ! $hecho && ! $actual;
			$actual = $actual || $marca;
			$out   .= '<li class="prc-paso prc-paso--' . ( $hecho ? 'hecho' : 'falta' ) . '"' . ( $marca ? ' aria-current="step"' : '' ) . '>'
				. '<a href="' . esc_url( '#' . (string) $paso['anchor'] ) . '">' . esc_html( (string) $paso['label'] ) . '</a> '
				. '<span class="prc-paso__estado">' . esc_html( $hecho ? 'Hecho' : 'Falta' ) . '</span></li>';
		}
		return $out . '</ol></nav>';
	}











	public static function notice( array $flash, array $invalid = array() ): string {
		if ( 'error' === (string) ( $flash['tipo'] ?? '' ) && array() !== $invalid ) {
			return FieldErrorView::summary( 'La solicitud no se ha guardado. Revise:', $invalid );
		}
		$texto = (string) ( $flash['texto'] ?? '' );
		$link  = (string) ( $flash['link'] ?? '' );
		if ( '' === $texto || '' === $link ) {
			return Shell::notice( (string) ( $flash['tipo'] ?? '' ), $texto, true );
		}
		return Shell::notice_open( (string) ( $flash['tipo'] ?? '' ), true )
			. esc_html( $texto ) . ' <a href="' . esc_url( $link ) . '">' . esc_html( (string) ( $flash['link_text'] ?? '' ) ) . '</a></p>';
	}











	public static function card( array $m ): string {
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
				<?php if ( array() !== (array) ( $m['progress'] ?? array() ) ) : ?>
					<?php echo self::status( (array) $m['progress'] );?>
				<?php else : ?>
					<span class="prc-solicitud-estado__fecha"><?php echo esc_html( 'Presentada el ' . (string) $s['date'] . '.' ); ?></span>
				<?php endif; ?>
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








	public static function submit( array $m, bool $nueva ): string {
		$personas = (array) ( $m['people'] ?? array() );
		$despues  = (string) ( $personas['after_submit'] ?? '' );
		$texto    = '' === $despues
			? sprintf( 'Después de presentar, en esta misma pantalla tendrá que dar de alta a las personas que participan (al menos %d). Hasta entonces, la solicitud queda incompleta.', (int) ( $personas['min'] ?? 0 ) )
			: sprintf( 'Después de presentar tendrá que dar de alta a las personas que participan (al menos %d), pero no enseguida: %s Hasta entonces, la solicitud queda incompleta.', (int) ( $personas['min'] ?? 0 ), lcfirst( $despues ) );
		$aviso    = $nueva && ! empty( $personas['show'] )
			? '<p class="prc-form__siguiente">' . esc_html( $texto ) . '</p>'
			: '';
		return '<div class="prc-form__envio">' . $aviso
			. '<button class="' . esc_attr( Assets::button_class( 'final' ) ) . '" type="submit"><span class="material-symbols-outlined" aria-hidden="true">send</span> '
			. esc_html( $nueva ? 'Presentar la solicitud' : 'Guardar los cambios de la solicitud' ) . '</button></div>';
	}







	public static function status( array $p ): string {
		$out = '<span class="prc-solicitud-estado__fecha">' . esc_html( 'Presentada el ' . (string) ( $p['submitted'] ?? '' ) . '.' ) . '</span>';
		if ( ! empty( $p['show_complete'] ) ) {
			$out .= '<span class="prc-solicitud-estado__completa">' . esc_html( self::completeness( $p ) ) . '</span>';
		}
		$enlaces = array();
		if ( ! empty( $p['people_missing'] ) ) {
			$enlaces[] = '<a href="' . esc_url( '#' . PeopleForm::ANCHOR ) . '">Ir a la lista de personas</a>';
		}
		if ( '' !== (string) ( $p['receipt_url'] ?? '' ) ) {
			$enlaces[] = '<a href="' . esc_url( (string) $p['receipt_url'] ) . '">Ver e imprimir el justificante</a>';
		}
		if ( array() !== $enlaces ) {
			$out .= '<span class="prc-solicitud-estado__enlace">' . implode( ' · ', $enlaces ) . '</span>';
		}
		return $out;
	}







	private static function completeness( array $p ): string {
		$falta = (array) ( $p['missing'] ?? array() );
		return array() === $falta ? 'Completa: no le falta nada.' : 'Incompleta: ' . lcfirst( implode( '; ', $falta ) ) . '.';
	}











	public static function receipt( array $m ): string {
		$p       = (array) $m['progress'];
		$s       = (array) $m['application'];
		$centro  = (array) $m['centre'];
		$docente = ApplicationMetaKeys::TYPE_TEACHER === (string) $m['applicant_type'];
		$quien   = (string) $p['author'] . ( '' !== (string) ( $s['position'] ?? '' ) ? ' (' . (string) $s['position'] . ')' : '' );
		$datos   = array(
			'N.º de solicitud'    => (string) (int) $p['application'],
			'Procedimiento'       => (string) $m['title'],
			'Centro'              => $docente ? '' : (string) $centro['name'] . ' (' . (string) $centro['code'] . ')',
			'Presentada'          => 'El ' . (string) $p['submitted'] . ( '' !== trim( $quien ) ? ', por ' . $quien : '' ) . '.',
			'Última modificación' => (string) $p['modified'],
			'Estado de revisión'  => (string) ( $s['state_label'] ?? '' ),
			'Estado'              => ! empty( $p['show_complete'] ) ? self::completeness( $p ) : '',
		);

		ob_start();
		?>
		<div class="prc-justificante">
			<p class="prc-sub prc-no-imprimir"><a href="<?php echo esc_url( (string) $p['apply_url'] ); ?>">&larr; Volver a la solicitud</a></p>
			<h1 class="prc-h1">Justificante de presentación</h1>
			<dl class="prc-respuestas prc-justificante__datos">
				<?php foreach ( $datos as $rotulo => $valor ) : ?>
					<?php if ( '' !== $valor ) : ?>
						<dt><?php echo esc_html( $rotulo ); ?></dt>
						<dd><?php echo esc_html( $valor ); ?></dd>
					<?php endif; ?>
				<?php endforeach; ?>
			</dl>
			<?php echo ApplyFormView::summary( $m );?>
			<?php echo PeopleView::review( (array) ( $m['people'] ?? array() ) );?>
			<p class="prc-campo__ayuda prc-no-imprimir">Para imprimirlo o guardarlo, use «Imprimir» en el menú de su navegador y, si lo quiere en un fichero, elija «Guardar como PDF».</p>
		</div>
		<?php
		return Shell::render( '', '', (string) ob_get_clean() );
	}
}

}








namespace Prc\PublicFront\View;

use Prc\Domain\DateRange;
use Prc\Domain\ProcedureQuestions;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ApplicationMetaRegistration;
use Prc\PublicFront\ApplicationFiles;
use Prc\PublicFront\ApplicationProgress;
use Prc\PublicFront\ApplyForm;
use Prc\PublicFront\Assets;
use Prc\PublicFront\Shell;


















if ( ! class_exists( 'Prc\PublicFront\View\ApplyFormView', false ) ) {
final class ApplyFormView {






	private const ANCHO = array(
		3  => 'prc-campo--cuarto',
		4  => 'prc-campo--tercio',
		6  => 'prc-campo--medio',
		12 => 'prc-campo--completo',
	);







	public static function html( array $m ): string {
		if ( '' !== (string) $m['aviso'] ) {
			return Shell::render( 'Solicitud', '', Shell::notice( 'aviso', (string) $m['aviso'] ) . self::next_steps( $m ) . self::mine_link( $m ) );
		}

		$progreso = (array) ( $m['progress'] ?? array() );
		if ( ! empty( $progreso['receipt'] ) ) {
			return ApplicationProgressView::receipt( $m );
		}

		ob_start();
		echo self::head( $m );
		echo ApplicationProgressView::notice( (array) ( $progreso['flash'] ?? $m['flash'] ), (array) ( $m['invalid'] ?? array() ) );
		echo self::choices( $m );
		echo ApplicationProgressView::card( $m );

		echo Shell::notice( 'info', (string) ( $m['withdraw']['late'] ?? '' ) );
		echo ApplicationProgressView::steps( $progreso );

		if ( true === $m['can_submit'] ) {
			echo self::form( $m );
		} else {
			echo Shell::notice( 'aviso', (string) $m['why'] );
			echo array() === (array) $m['application'] ? self::next_steps( $m ) : '';
			echo self::summary( $m );
		}



		echo PeopleView::section( (array) ( $m['people'] ?? array() ) );
		if ( array() !== (array) ( $m['deliveries'] ?? array() ) ) {
			echo '<div id="' . esc_attr( ApplicationProgress::ANCHOR_DELIVERIES ) . '">';
			self::deliveries( $m );
			echo '</div>';
		}

		echo ApplicationActionsView::withdraw( (array) ( $m['withdraw'] ?? array() ) );
		return Shell::render( '', '', (string) ob_get_clean() );
	}







	private static function deliveries( array $m ): void {
		foreach ( (array) ( $m['deliveries'] ?? array() ) as $question ) {
			$key   = (string) $question['key'];
			$dates = DateRange::of( (string) $question['opens_at'], (string) $question['closes_at'] );
			?>
			<section class="prc-seccion">
				<h3 class="prc-seccion__titulo"><?php echo esc_html( (string) $question['label'] ); ?></h3>
				<p><?php echo esc_html( '' !== $dates ? 'Plazo de entrega: ' . lcfirst( $dates ) . '.' : 'Plazo de entrega pendiente de configurar.' ); ?></p>
				<p>Documento presentado: <?php echo wp_kses_post( self::attached( (array) $m['application'], $key ) ); ?></p>
				<?php if ( ! empty( $question['can_submit'] ) ) : ?>
					<form class="prc-form" method="post" action="<?php echo esc_url( ApplyForm::url_for_code( (int) $m['procedure_id'], (string) $m['centre']['code'] ) ); ?>" enctype="multipart/form-data">
						<?php wp_nonce_field( ApplyForm::NONCE_ACTION, ApplyForm::NONCE_FIELD, false ); ?>
						<input type="hidden" name="<?php echo esc_attr( ApplyForm::FIELD_OP ); ?>" value="<?php echo esc_attr( ApplyForm::OP_DELIVER ); ?>" />
						<input type="hidden" name="<?php echo esc_attr( ApplyForm::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) $m['procedure_id'] ); ?>" />
						<input type="hidden" name="<?php echo esc_attr( ApplyForm::FIELD_CENTRE ); ?>" value="<?php echo esc_attr( (string) $m['centre']['code'] ); ?>" />
						<input type="hidden" name="<?php echo esc_attr( ApplyForm::FIELD_DELIVERY ); ?>" value="<?php echo esc_attr( $key ); ?>" />
						<?php
						$question['required'] = true;

						echo self::question( $question, array() );
						?>
						<p>Si ya entregó un documento, esta entrega lo sustituirá. La solicitud y su revisión se conservan.</p>
						<button type="submit" class="<?php echo esc_attr( Assets::button_class() ); ?>">Entregar documento</button>
					</form>
				<?php else : ?>
					<p>No se admiten entregas: hace falta una solicitud vigente, un plazo abierto y un procedimiento publicado sin archivar.</p>
				<?php endif; ?>
			</section>
			<?php
		}
	}







	private static function head( array $m ): string {
		$plazo       = DateRange::of( (string) $m['opens_at'], (string) $m['closes_at'] );
		$subsanacion = DateRange::of( (string) $m['amend_opens_at'], (string) $m['amend_closes_at'] );

		ob_start();
		?>
		<p class="prc-sub"><a href="<?php echo esc_url( (string) $m['mine_url'] ); ?>">&larr; <?php echo esc_html( Shell::mine_label( ApplicationMetaKeys::TYPE_TEACHER === (string) $m['applicant_type'] ) ); ?></a></p>
		<div class="prc-h1-fila">
			<h1 class="prc-h1"><?php echo esc_html( (string) $m['title'] ); ?></h1>
			<span class="<?php echo esc_attr( Assets::state_class( (string) $m['state'] ) ); ?>"><?php echo esc_html( (string) $m['state_label'] ); ?></span>
			<span class="prc-acciones">
				<?php echo PanelParts::icon_link( (string) $m['view_url'], 'ojo', 'Ver la ficha pública' );?>
			</span>
		</div>
		<?php if ( '' !== $plazo ) : ?>
			<p class="prc-sub"><?php echo esc_html( 'Plazo de solicitud: ' . lcfirst( $plazo ) ); ?></p>
		<?php endif; ?>
		<?php if ( '' !== $subsanacion ) : ?>
			<p class="prc-sub"><?php echo esc_html( 'Plazo de subsanación: ' . lcfirst( $subsanacion ) ); ?></p>
		<?php endif; ?>
		<?php echo PanelParts::help_link( (string) ( $m['help_url'] ?? '' ), '¿Dudas al rellenar?' );?>
		<?php
		return (string) ob_get_clean();
	}











	private static function next_steps( array $m ): string {
		$correo = (string) ( $m['contact_email'] ?? '' );
		$motivo = (string) $m['why'] . ' ' . (string) $m['aviso'];
		$volver = (string) ( $m['view_url'] ?? '' );
		$cambio = (string) ( $m['switch_url'] ?? '' );

		ob_start();
		?>
		<div class="prc-siguiente">
			<?php if ( '' !== $correo && false === strpos( $motivo, $correo ) ) : ?>
				<p>¿Cree que es un error? Escriba a <a href="<?php echo esc_url( 'mailto:' . $correo ); ?>"><?php echo esc_html( $correo ); ?></a>.</p>
			<?php endif; ?>
			<?php if ( '' !== $volver || '' !== $cambio ) : ?>
				<p class="prc-acciones">
					<?php if ( '' !== $volver ) : ?>
						<a class="<?php echo esc_attr( Assets::button_class() ); ?>" href="<?php echo esc_url( $volver ); ?>">Volver a la ficha del procedimiento</a>
					<?php endif; ?>
					<?php if ( '' !== $cambio ) : ?>
						<a class="<?php echo esc_attr( Assets::button_class() ); ?>" href="<?php echo esc_url( $cambio ); ?>">Salir y entrar con otra cuenta</a>
					<?php endif; ?>
				</p>
			<?php endif; ?>
			<?php echo PanelParts::help_link( (string) ( $m['start_help_url'] ?? '' ), '', 'Ayuda: cómo presentar una solicitud' );?>
		</div>
		<?php
		return (string) ob_get_clean();
	}











	private static function choices( array $m ): string {
		$choices = (array) ( $m['choices'] ?? array() );
		if ( count( $choices ) < 2 ) {
			return '';
		}
		$actual = (string) $m['centre']['code'];

		ob_start();
		?>
		<nav class="prc-seccion" aria-labelledby="prc-apply-centre">
			<h3 class="prc-seccion__titulo" id="prc-apply-centre">Solicita en nombre de</h3>
			<div class="prc-seccion__cuerpo">
				<p class="prc-campo__ayuda">Su cuenta tiene más de un código y este procedimiento admite los dos. Cada uno tiene su propia solicitud: elija con cuál quiere verla o presentarla.</p>
				<ul class="prc-opciones prc-opciones--vertical">
					<?php foreach ( $choices as $code => $rotulo ) : ?>
						<li class="prc-opcion">
							<?php if ( (string) $code === $actual ) : ?>
								<strong aria-current="page"><?php echo esc_html( (string) $rotulo ); ?></strong>
							<?php else : ?>
								<a href="<?php echo esc_url( ApplyForm::url_for_code( (int) $m['procedure_id'], (string) $code ) ); ?>"><?php echo esc_html( (string) $rotulo ); ?></a>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</nav>
		<?php
		return (string) ob_get_clean();
	}







	private static function centre_help( array $m ): string {
		return count( (array) ( $m['choices'] ?? array() ) ) > 1
			? 'Es uno de los códigos de su cuenta: para solicitar con el otro, elíjalo arriba.'
			: 'El centro es el de su cuenta: no se puede cambiar desde aquí.';
	}











	private static function privacy(): string {
		$texto = trim( (string) ( ProcedureChrome::chrome()['privacy_notice'] ?? '' ) );
		if ( '' === $texto ) {
			return '';
		}
		return '<div class="prc-aviso-legal">' . wp_kses_post( $texto ) . '</div>';
	}







	private static function form( array $m ): string {
		$v       = (array) $m['values'];
		$spec    = (array) $m['spec'];
		$centro  = (array) $m['centre'];
		$quien   = (array) ( $m['applicant'] ?? array() );
		$nueva   = array() === (array) $m['application'];
		$docente = ApplicationMetaKeys::TYPE_TEACHER === (string) $m['applicant_type'];
		$falla   = (array) ( $m['invalid'] ?? array() );



		$del_centro = self::locked(
			'prc-centre-code',
			'Código',
			(string) $centro['code'],
			3,
			self::centre_help( $m )
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
		<form class="prc-form prc-form--solicitud" id="<?php echo esc_attr( ApplicationProgress::ANCHOR_FORM ); ?>" method="post" action="" enctype="multipart/form-data">
			<?php wp_nonce_field( ApplyForm::NONCE_ACTION, ApplyForm::NONCE_FIELD, false ); ?>
			<input type="hidden" name="<?php echo esc_attr( ApplyForm::FIELD_OP ); ?>" value="<?php echo esc_attr( ApplyForm::OP_APPLY ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( ApplyForm::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( ApplyForm::FIELD_CENTRE ); ?>" value="<?php echo esc_attr( (string) $centro['code'] ); ?>" />

			<?php echo self::privacy();?>

			<?php if ( ! $docente ) : ?>
				<section class="prc-seccion">
					<h3 class="prc-seccion__titulo">DATOS DEL CENTRO EDUCATIVO</h3>
					<div class="prc-seccion__cuerpo">
						<?php echo $del_centro;?>
					</div>
				</section>
			<?php endif; ?>

			<section class="prc-seccion">
				<h3 class="prc-seccion__titulo"><?php echo esc_html( $docente ? 'DATOS DE LA PERSONA PARTICIPANTE' : 'DATOS PERSONALES DE LA DIRECCIÓN DEL CENTRO' ); ?></h3>
				<div class="prc-seccion__cuerpo">
					<?php echo $de_quien;?>
					<?php if ( ! $docente ) : ?>
						<div class="prc-campo prc-campo--cuarto prc-campo--abre-fila">
							<?php echo self::label( 'prc-position', 'Cargo en el centro', true );?>
							<select class="prc-control<?php echo esc_attr( FieldErrorView::css( $falla, 'position' ) ); ?>" id="prc-position" name="<?php echo esc_attr( ApplyForm::FIELD_POSITION ); ?>" required<?php echo FieldErrorView::attrs( $falla, 'position' );?>>
								<option value="">— Elija —</option>
								<?php foreach ( (array) $m['positions'] as $clave => $rotulo ) : ?>
									<option value="<?php echo esc_attr( (string) $clave ); ?>" <?php selected( (string) $clave, (string) $v['position'] ); ?>><?php echo esc_html( (string) $rotulo ); ?></option>
								<?php endforeach; ?>
							</select>
							<?php echo FieldErrorView::feedback( $falla, 'position' );?>
						</div>
					<?php endif; ?>
				</div>
			</section>

			<?php if ( '' !== trim( (string) $spec['commitments'] ) ) : ?>
				<section class="prc-seccion">
					<h3 class="prc-seccion__titulo">SOLICITA</h3>
					<div class="prc-seccion__cuerpo">
						<div class="prc-nota prc-campo--completo">
							<p><?php echo esc_html( $docente ? 'Su participación en la presente convocatoria, comprometiéndose a cumplir los siguientes compromisos:' : 'La participación de su centro en la presente convocatoria, comprometiéndose a cumplir los siguientes compromisos:' ); ?></p>
							<p><?php echo nl2br( esc_html( (string) $spec['commitments'] ) );?></p>
						</div>
						<div class="prc-campo prc-campo--completo">
							<div class="prc-opciones prc-opciones--vertical">
								<div class="prc-opcion">
									<label for="prc-accept">
										<input type="checkbox" class="<?php echo esc_attr( trim( FieldErrorView::css( $falla, 'accept' ) ) ); ?>" id="prc-accept" name="<?php echo esc_attr( ApplyForm::FIELD_ACCEPT ); ?>" value="1" required <?php checked( (bool) $v['accept'] ); ?><?php echo FieldErrorView::attrs( $falla, 'accept' );?> />
										<?php echo esc_html( $docente ? 'Acepto estos compromisos' : 'El centro acepta estos compromisos' ); ?>
									</label>
								</div>
							</div>
							<?php echo FieldErrorView::feedback( $falla, 'accept' );?>
						</div>
					</div>
				</section>
			<?php endif; ?>

			<?php if ( array() !== (array) $spec['questions'] ) : ?>
				<section class="prc-seccion prc-seccion--sin-titulo" aria-labelledby="prc-preguntas">
					<h3 class="screen-reader-text" id="prc-preguntas">Preguntas del procedimiento</h3>
					<div class="prc-seccion__cuerpo">
						<?php foreach ( (array) $spec['questions'] as $pregunta ) : ?>
							<?php echo self::question( (array) $pregunta, (array) $v['answers'], $subidos, $falla );?>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>

			<?php echo self::coordination( $m, (array) $v['coordinators'] );?>

			<?php echo ApplicationProgressView::submit( $m, $nueva );?>
		</form>
		<?php
		return (string) ob_get_clean();
	}








	private static function coordination( array $m, array $coordinators ): string {
		$spec = (array) $m['spec'];
		if ( ! empty( $spec['coordinators_in_list'] ) ) {
			$hasta   = (string) ( $m['people']['until'] ?? '' );
			$despues = (string) ( $m['people']['after_submit'] ?? '' );
			$cuando  = '' !== $hasta ? ' Allí podrá cambiarlas hasta el ' . $hasta . ', incluido.' : '';
			return '<div class="prc-nota prc-campo--completo"><p>'
				. esc_html( 'Las personas coordinadoras se dan de alta en la lista de personas, después de presentar la solicitud.' . ( '' !== $despues ? ' ' . $despues : $cuando ) )
				. '</p></div>';
		}
		$cuantas = (int) ( $spec['coordinator_count'] ?? 0 );
		if ( $cuantas < 1 ) {
			return '';
		}
		$campos = '';
		for ( $index = 0; $index < $cuantas; ++$index ) {
			$campos .= self::coordinator_fields( $index, (array) ( $coordinators[ $index ] ?? array() ), (array) ( $m['invalid'] ?? array() ) );
		}
		return '<section class="prc-seccion"><h3 class="prc-seccion__titulo">DATOS DE LA COORDINACIÓN</h3><div class="prc-seccion__cuerpo">'
			. '<div class="prc-nota prc-campo--completo"><p>Las personas coordinadoras aceptan el compromiso de colaborar en el desarrollo del programa, proyecto o red tal como se expresa en la convocatoria.</p></div>'
			. $campos . '</div></section>';
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















	private static function question( array $p, array $answers, array $files = array(), array $invalid = array() ): string {
		if ( in_array( (string) $p['type'], array( ProcedureQuestions::TYPE_NUMBER, ProcedureQuestions::TYPE_DATE, ProcedureQuestions::TYPE_GRID ), true ) ) {
			return MeasuredQuestionView::html( $p, $answers[ (string) $p['key'] ] ?? '', $invalid );
		}
		$key    = (string) $p['key'];
		$subido = isset( $files[ $key ] ) && is_array( $files[ $key ] ) ? $files[ $key ] : array();
		$nombre = ApplyForm::FIELD_ANSWERS . '[' . $key . ']';
		$valor  = $answers[ $key ] ?? '';
		$tipo   = (string) $p['type'];
		$obliga = ! empty( $p['required'] );
		$ayuda  = (string) $p['help'];
		$id     = 'prc-q-' . $key;
		$codigo = 'answer:' . $key;
		$pie    = FieldErrorView::attrs( $invalid, $codigo, '' !== $ayuda ? $id . '-ayuda' : '' );
		$clase  = 'prc-control' . FieldErrorView::css( $invalid, $codigo );
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
				<textarea id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $nombre ); ?>" class="<?php echo esc_attr( $clase ); ?>" rows="3"
					placeholder="Escriba aquí"
					maxlength="<?php echo esc_attr( (string) ProcedureQuestions::TEXT_MAX ); ?>" <?php echo $obliga ? 'required' : ''; ?>
					<?php echo $pie;?>><?php echo esc_textarea( is_scalar( $valor ) ? (string) $valor : '' ); ?></textarea>
			<?php elseif ( ProcedureQuestions::TYPE_FILE === $tipo ) : ?>
				<?php echo self::label( $id, (string) $p['label'], $obliga );?>
				<?php if ( array() !== $subido ) : ?>
					<p class="prc-campo__adjunto">
						Ya adjuntó
						<a href="<?php echo esc_url( ApplicationFiles::url( (int) ( $subido['application'] ?? 0 ), (string) ( $subido['id'] ?? '' ) ) ); ?>" download><?php echo esc_html( (string) ( $subido['name'] ?? '' ) ); ?></a>.
						Adjunte otro solo si quiere sustituirlo.
					</p>
				<?php endif; ?>
				<input type="file" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( ApplicationFiles::FIELD . '[' . $key . ']' ); ?>"
					class="<?php echo esc_attr( $clase ); ?>" accept="<?php echo esc_attr( implode( ',', array_values( ApplicationFiles::mimes() ) ) ); ?>"
					<?php echo $obliga && array() === $subido ? 'required' : ''; ?>
					<?php echo $pie;?> />
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
								<?php echo $pie;?> />
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
				<div class="prc-opciones prc-opciones--vertical" role="<?php echo $varias ? 'group' : 'radiogroup'; ?>" id="<?php echo esc_attr( $id ); ?>"
					aria-labelledby="<?php echo esc_attr( $id . '-rotulo' ); ?>"
					<?php echo $pie;?>>
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
			<?php echo FieldErrorView::feedback( $invalid, $codigo );?>
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












	private static function coordinator_fields( int $index, array $coordinator, array $invalid = array() ): string {
		$campos = array(
			'name'    => array( 'Nombre', 'text', 'prc-campo--cuarto prc-campo--abre-fila', '' ),
			'surname' => array( 'Apellidos', 'text', 'prc-campo--cuarto', '' ),
			'dni'     => array( 'DNI o NIE', 'text', 'prc-campo--cuarto', 'Escriba el DNI o NIE completo, con la letra.' ),
			'email'   => array( 'Correo electrónico', 'email', 'prc-campo--cuarto', '' ),
		);

		ob_start();
		?>
		<fieldset class="prc-seccion-campos">
			<legend><?php echo esc_html( sprintf( 'Persona coordinadora %d', $index + 1 ) ); ?></legend>
			<?php foreach ( $campos as $campo => list( $rotulo, $tipo, $clases, $ayuda ) ) : ?>
				<?php
				$id     = 'prc-coordinator-' . $campo . '-' . $index;
				$codigo = 'coordinator_' . $campo . ':' . $index;
				?>
				<div class="prc-campo <?php echo esc_attr( $clases ); ?>">
					<?php echo self::label( $id, $rotulo, true );?>
					<input class="prc-control<?php echo esc_attr( FieldErrorView::css( $invalid, $codigo ) ); ?>" type="<?php echo esc_attr( $tipo ); ?>" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( ApplyForm::FIELD_COORDINATORS . '[' . $index . '][' . $campo . ']' ); ?>" required
						<?php if ( 'dni' === $campo ) : ?>
							autocomplete="off"
						<?php endif; ?>
						<?php echo FieldErrorView::attrs( $invalid, $codigo, '' !== $ayuda ? 'prc-coordinator-dni-ayuda-' . $index : '' );?>
						value="<?php echo esc_attr( (string) ( $coordinator[ $campo ] ?? '' ) ); ?>" />
					<?php echo FieldErrorView::feedback( $invalid, $codigo );?>
					<?php if ( '' !== $ayuda ) : ?>
						<p class="prc-campo__ayuda" id="<?php echo esc_attr( 'prc-coordinator-dni-ayuda-' . $index ); ?>"><?php echo esc_html( $ayuda ); ?></p>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</fieldset>
		<?php
		return (string) ob_get_clean();
	}







	public static function summary( array $m ): string {
		$s = (array) $m['application'];
		if ( array() === $s ) {
			return '';
		}
		$coord     = ApplicationMetaRegistration::sanitize_coordinator( $s['coordinator'] );
		$preguntas = (array) ( $m['spec']['questions'] ?? array() );

		ob_start();
		?>
		<section class="prc-seccion prc-solicitud-resumen" id="<?php echo esc_attr( ApplicationProgress::ANCHOR_FORM ); ?>">
			<h3 class="prc-seccion__titulo">LO QUE PRESENTÓ</h3>
			<dl class="prc-respuestas">
				<?php if ( '' !== (string) $s['position'] ) : ?>
					<dt>Cargo</dt>
					<dd><?php echo esc_html( (string) $s['position'] ); ?></dd>
				<?php endif; ?>
				<?php foreach ( $coord as $index => $persona ) : ?>
					<?php if ( '' !== (string) $persona['name'] ) : ?>
						<dt><?php echo esc_html( sprintf( 1 === count( $coord ) ? 'Persona coordinadora' : 'Persona coordinadora %d', $index + 1 ) ); ?></dt>
						<dd><?php echo esc_html( implode( ' · ', array_filter( array( ApplicationMetaRegistration::full_name( $persona ), $persona['dni'], $persona['email'] ) ) ) ); ?></dd>
					<?php endif; ?>
				<?php endforeach; ?>
				<?php foreach ( $preguntas as $pregunta ) : ?>
					<dt><?php echo esc_html( (string) $pregunta['label'] ); ?></dt>
					<?php


					$respuesta = ProcedureQuestions::TYPE_FILE === (string) $pregunta['type']
						? self::attached( $s, (string) $pregunta['key'] )
						: esc_html( ProcedureQuestions::as_text( (array) $pregunta, $s['answers'][ $pregunta['key'] ] ?? null ) );
					?>
					<dd><?php echo $respuesta;?></dd>
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
		$rotulo = Shell::mine_label( ApplicationMetaKeys::TYPE_TEACHER === (string) ( $m['applicant_type'] ?? '' ) );
		return '<p><a class="' . esc_attr( Assets::button_class( true ) ) . '" href="' . esc_url( $url ) . '">' . esc_html( $rotulo ) . '</a></p>';
	}
}

}








namespace Prc\PublicFront;

use Prc\Access\ApplicationActions;
use Prc\Access\CentreScope;
use Prc\Access\ProcedureAccess;
use Prc\Domain\ApplicationInput;
use Prc\Domain\ProcedureInput;
use Prc\Domain\ProcedureQuestions;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ApplicationMetaRegistration;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ProcedurePostType;
use Prc\PublicFront\View\ApplyFormView;



















if ( ! class_exists( 'Prc\PublicFront\ApplyForm', false ) ) {
final class ApplyForm {

	public const SHORTCODE = 'prc_apply';




	public const ARG_PROCEDURE = Shell::ARG_PROCEDURE;




	public const ARG_NOTICE = 'aviso';




	public const ARG_CENTRE = 'centro';




	public const FIELD_OP = 'prc_apply_op';

	public const OP_APPLY = 'apply';

	public const OP_DELIVER     = 'deliver';
	public const FIELD_DELIVERY = 'prc_delivery';

	public const FIELD_PROCEDURE     = 'prc_apply_procedure';
	public const FIELD_CENTRE        = 'prc_apply_centre';
	public const FIELD_POSITION      = 'prc_position';
	public const FIELD_COORD_NAME    = 'prc_coordinator_name';
	public const FIELD_COORD_SURNAME = 'prc_coordinator_surname';
	public const FIELD_COORD_DNI     = 'prc_coordinator_dni';
	public const FIELD_COORD_EMAIL   = 'prc_coordinator_email';
	public const FIELD_COORDINATORS  = 'prc_coordinators';
	public const FIELD_ACCEPT        = 'prc_accept';
	public const FIELD_ANSWERS       = 'prc_q';

	public const NONCE_ACTION = 'prc_apply';
	public const NONCE_FIELD  = '_prc_apply_nonce';






	private const DONE = array(
		'presentada' => 'Solicitud presentada. Puede volver a esta pantalla para consultarla, y editarla mientras el plazo siga abierto.',
		'guardada'   => 'Solicitud guardada.',
		'entregado'  => 'Documento entregado. La solicitud y su revisión se conservan.',
		'retirada'   => 'Solicitud retirada: ya no cuenta para este procedimiento. Mientras el plazo siga abierto, puede volver a presentar una nueva.',
	);









	private static $rejected = '';






	private static $invalid = array();






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












	public static function url_for_code( int $procedure_id, string $code, array $args = array() ): string {
		if ( '' !== $code && CentreScope::code_for() !== $code ) {
			$args[ self::ARG_CENTRE ] = $code;
		}
		return self::url( $procedure_id, $args );
	}












	public static function chosen( int $user_id, int $procedure_id, string $wanted ): string {
		$codes = ProcedureAccess::apply_codes( $user_id, $procedure_id );
		if ( '' !== $wanted && isset( $codes[ $wanted ] ) ) {
			return $wanted;
		}
		return array() === $codes ? '' : (string) array_key_first( $codes );
	}















	public static function window( string $state, string $review_state, bool $manually_closed = false, bool $late = false ): string {
		return ProcedureState::application_window( $state, $review_state, $manually_closed, $late );
	}






	public static function handle(): void {

		$op    = isset( $_POST[ self::FIELD_OP ] ) && is_string( $_POST[ self::FIELD_OP ] ) ? sanitize_key( wp_unslash( $_POST[ self::FIELD_OP ] ) ) : '';
		$nonce = isset( $_POST[ self::NONCE_FIELD ] ) && is_string( $_POST[ self::NONCE_FIELD ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ) : '';

		if ( ! in_array( $op, array( self::OP_APPLY, self::OP_DELIVER ), true ) || ! is_user_logged_in() ) {
			return;
		}
		if ( false === wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			self::$rejected = 'El formulario estuvo abierto demasiado tiempo y el envío caducó. Vuelva a enviarlo.';
			return;
		}


		$procedure_id = absint( wp_unslash( $_POST[ self::FIELD_PROCEDURE ] ?? 0 ) );
		$user_id      = get_current_user_id();



		$docente = user_can( $user_id, ProcedureAccess::CAP_APPLY_AS_TEACHER )
			&& ProcedureMetaKeys::AUDIENCE_TEACHERS === get_post_meta( $procedure_id, ProcedureMetaKeys::AUDIENCE, true );
		$pedido  = $docente ? '' : self::field( self::FIELD_CENTRE );
		if ( self::OP_DELIVER === $op ) {
			self::deliver( $procedure_id, $user_id, $pedido );
			return;
		}
		if ( ! ProcedureAccess::can_apply( $user_id, $procedure_id, $pedido ) ) {
			self::$rejected = ProcedureAccess::why_not_apply( $user_id, $procedure_id, $pedido );
			return;
		}
		$code      = $docente ? '' : ( '' !== $pedido ? $pedido : self::chosen( $user_id, $procedure_id, '' ) );
		$existente = $docente ? Applications::find_for_teacher( $procedure_id, $user_id ) : Applications::find( $procedure_id, $code );
		$cerrado   = self::window(
			ProcedureState::of_post( $procedure_id ),
			null !== $existente ? (string) get_post_meta( $existente, ApplicationMetaKeys::REVIEW_STATE, true ) : '',
			(bool) get_post_meta( $procedure_id, ProcedureMetaKeys::APPLICATIONS_CLOSED, true ),

			ApplicationActions::is_late_open( (int) $existente )
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
			self::$invalid  = ApplicationInput::labels( $revisado['fields'], (array) $spec['questions'] );
			return;
		}

		$id = $docente
			? Applications::save_teacher( $procedure_id, $user_id, $revisado['data'], $ficheros['files'] )
			: Applications::save( $procedure_id, $user_id, $revisado['data'], $code, $ficheros['files'] );
		if ( $id <= 0 ) {
			self::$rejected = ApplicationOperations::why_not_saved( 'No se ha podido guardar la solicitud o su documento, así que la solicitud no se ha presentado. Vuelva a intentarlo.' );
			return;
		}

		Shell::leave( self::url_for_code( $procedure_id, $code, array( self::ARG_NOTICE => null === $existente ? 'presentada' : 'guardada' ) ) );
	}






	private static function submitted(): array {


		$respuestas   = (array) wp_unslash( $_POST[ self::FIELD_ANSWERS ] ?? array() );
		$acepta       = ! empty( $_POST[ self::FIELD_ACCEPT ] );
		$coordinators = wp_unslash( $_POST[ self::FIELD_COORDINATORS ] ?? array() );
		$coordinators = is_array( $coordinators ) ? self::clean_coordinators( $coordinators ) : array();

		return array(
			'position'            => self::field( self::FIELD_POSITION ),
			'coordinators'        => is_array( $coordinators ) ? $coordinators : array(),
			'coordinator_name'    => self::field( self::FIELD_COORD_NAME ),
			'coordinator_surname' => self::field( self::FIELD_COORD_SURNAME ),
			'coordinator_dni'     => self::field( self::FIELD_COORD_DNI ),
			'coordinator_email'   => self::field( self::FIELD_COORD_EMAIL ),
			'accept'              => $acepta,
			'answers'             => self::clean_answers( $respuestas ),
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







	private static function clean_coordinators( array $raw ): array {
		$out = array();
		foreach ( array_slice( $raw, 0, ProcedureMetaKeys::COORDINATOR_COUNT_MAX ) as $row ) {
			$row  = is_array( $row ) ? $row : array();
			$fila = array();
			foreach ( array( 'name', 'surname', 'dni', 'email' ) as $campo ) {
				$fila[ $campo ] = is_scalar( $row[ $campo ] ?? null ) ? sanitize_text_field( (string) $row[ $campo ] ) : '';
			}
			$out[] = $fila;
		}
		return $out;
	}







	private static function field( string $nombre ): string {
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) || ! is_string( $_POST[ self::NONCE_FIELD ] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) ) {
			return '';
		}
		return isset( $_POST[ $nombre ] ) && is_string( $_POST[ $nombre ] )
			? trim( sanitize_text_field( wp_unslash( $_POST[ $nombre ] ) ) ) : '';
	}











	private static function exits(): array {

		$procedure_id = absint( wp_unslash( $_GET[ self::ARG_PROCEDURE ] ?? 0 ) );
		$user_id      = get_current_user_id();



		$publicado = ProcedurePostType::POST_TYPE === get_post_type( $procedure_id ) && 'publish' === get_post_status( $procedure_id );


		$cuenta = $user_id > 0 && $publicado && ! ProcedureAccess::can_apply( $user_id, $procedure_id );
		return array(
			'contact_email'  => $publicado ? (string) ( ProcedureInput::emails( get_post_meta( $procedure_id, ProcedureMetaKeys::CONTACT_EMAILS, true ) )[0] ?? '' ) : '',
			'start_help_url' => Help::url( HelpContent::GUIDE_APPLY, 'antes-de-empezar' ),
			'switch_url'     => $cuenta ? wp_logout_url( wp_login_url( self::url( $procedure_id ) ) ) : '',
		);
	}






	public static function model(): array {
		$m = self::exits() + array(
			'aviso'           => '',
			'flash'           => array(
				'tipo'  => '',
				'texto' => '',
			),
			'procedure_id'    => 0,
			'title'           => '',
			'view_url'        => '',
			'mine_url'        => Shell::url( 'mine' ),
			'help_url'        => Help::url( HelpContent::GUIDE_APPLY, 'rellenar-la-solicitud' ),
			'applicant_type'  => '',
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


			'choices'         => array(),


			'applicant'       => array(
				'name'  => '',
				'email' => '',
			),
			'application'     => array(),
			'max_file_size'   => ApplicationFiles::max_bytes(),
			'file_mimes'      => ApplicationFiles::mimes(),
			'deliveries'      => array(),
			'can_submit'      => false,
			'why'             => '',
			'spec'            => array(
				'coordinator_count' => 0,
				'commitments'       => '',
				'questions'         => array(),
			),
			'positions'       => ApplicationMetaKeys::positions(),
			'review_states'   => ApplicationMetaKeys::review_states(),
			'values'          => array(),

			'invalid'         => self::$invalid,
		);

		if ( ! is_user_logged_in() ) {
			$m['aviso'] = 'Debe iniciar sesión con su usuario para presentar la solicitud.';
			return $m;
		}
		$user_id = get_current_user_id();
		if ( ! user_can( $user_id, ProcedureAccess::CAP_APPLY ) ) {
			$m['aviso'] = ProcedureAccess::why_not_apply( $user_id, 0 );
			return $m;
		}
		$quien               = wp_get_current_user();
		$m['applicant']      = array(
			'name'  => (string) $quien->display_name,
			'email' => (string) $quien->user_email,
		);
		$es_docente          = user_can( $user_id, ProcedureAccess::CAP_APPLY_AS_TEACHER );
		$m['applicant_type'] = $es_docente ? ApplicationMetaKeys::TYPE_TEACHER : ApplicationMetaKeys::TYPE_CENTRE;
		$m['centre']         = Applications::centre_of( $user_id );


		$procedure_id = absint( wp_unslash( $_GET[ self::ARG_PROCEDURE ] ?? 0 ) );
		$hecho        = sanitize_key( wp_unslash( (string) ( $_GET[ self::ARG_NOTICE ] ?? '' ) ) );


		if ( ProcedurePostType::POST_TYPE !== get_post_type( $procedure_id ) || 'publish' !== get_post_status( $procedure_id ) ) {
			$m['aviso'] = $procedure_id > 0
				? 'Ese procedimiento no existe o todavía no está publicado.'
				: 'Abra la solicitud desde la ficha del procedimiento, o desde sus solicitudes.';
			return $m;
		}

		$m['procedure_id']    = $procedure_id;
		$m['title']           = (string) get_post_field( 'post_title', $procedure_id );
		$m['view_url']        = (string) get_permalink( $procedure_id );
		$m['state']           = ProcedureState::of_post( $procedure_id );
		$m['state_label']     = ProcedureState::label(
			(string) $m['state'],
			(bool) get_post_meta( $procedure_id, ProcedureMetaKeys::APPLICATIONS_CLOSED, true )
		);
		$m['opens_at']        = (string) get_post_meta( $procedure_id, ProcedureMetaKeys::OPENS_AT, true );
		$m['closes_at']       = (string) get_post_meta( $procedure_id, ProcedureMetaKeys::CLOSES_AT, true );
		$m['amend_opens_at']  = (string) get_post_meta( $procedure_id, ProcedureMetaKeys::AMEND_OPENS_AT, true );
		$m['amend_closes_at'] = (string) get_post_meta( $procedure_id, ProcedureMetaKeys::AMEND_CLOSES_AT, true );
		$m['final_list_url']  = (string) get_post_meta( $procedure_id, ProcedureMetaKeys::FINAL_LIST_URL, true );
		$m['spec']            = Applications::spec( $procedure_id );
		$m['applicant_type']  = (string) $m['spec']['applicant_type'];
		$es_docente           = ApplicationMetaKeys::TYPE_TEACHER === $m['applicant_type'] && user_can( $user_id, ProcedureAccess::CAP_APPLY_AS_TEACHER );
		if ( ! $es_docente && array() === CentreScope::codes_for( $user_id ) ) {
			$m['aviso'] = ProcedureAccess::why_not_apply( $user_id, $procedure_id );
			return $m;
		}

		$code = $es_docente ? '' : self::chosen( $user_id, $procedure_id, self::wanted() );
		if ( ! $es_docente ) {
			$m['centre']  = Applications::centre_of( $user_id, $code );
			$m['choices'] = self::choices( $user_id, $procedure_id );
		}

		$existente = $es_docente ? Applications::find_for_teacher( $procedure_id, $user_id ) : Applications::find( $procedure_id, $m['centre']['code'] );
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
				'coordinator' => ApplicationMetaRegistration::sanitize_coordinator( $meta[ ApplicationMetaKeys::COORDINATOR ] ),
				'answers'     => (array) $meta[ ApplicationMetaKeys::ANSWERS ],
				'files'       => ApplicationFiles::descriptors( $existente ),
			);
		}

		foreach ( Applications::questions( $procedure_id ) as $question ) {
			if ( ProcedureQuestions::is_delivery( $question ) ) {
				$question['can_submit'] = ProcedureAccess::can_deliver( $user_id, (int) $existente, $question );
				$m['deliveries'][]      = $question;
			}
		}



		if ( ! ProcedureAccess::can_apply( $user_id, $procedure_id, $code ) ) {
			$m['why'] = ProcedureAccess::why_not_apply( $user_id, $procedure_id, $code );
		} else {
			$m['why'] = self::window(
				(string) $m['state'],
				(string) ( $m['application']['state'] ?? '' ),
				(bool) get_post_meta( $procedure_id, ProcedureMetaKeys::APPLICATIONS_CLOSED, true ),
				ApplicationActions::is_late_open( (int) ( $m['application']['id'] ?? 0 ) )
			);
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









	private static function deliver( int $procedure_id, int $user_id, string $code ): void {
		$key = self::field( self::FIELD_DELIVERY );
		$id  = ProcedureMetaKeys::AUDIENCE_TEACHERS === get_post_meta( $procedure_id, ProcedureMetaKeys::AUDIENCE, true )
			? Applications::find_for_teacher( $procedure_id, $user_id )
			: Applications::find( $procedure_id, $code );
		foreach ( Applications::questions( $procedure_id ) as $question ) {
			if ( $key !== $question['key'] || ! ProcedureAccess::can_deliver( $user_id, (int) $id, $question ) ) {
				continue;
			}
			$question['required'] = true;
			$files                = ApplicationFiles::submitted( array( $question ) );
			if ( ! $files['ok'] ) {
				self::$rejected = ApplicationFiles::why( $files['errors'] );
				return;
			}
			if ( ! ApplicationFiles::deliver( (int) $id, $key, $files['files'], $user_id ) ) {
				self::$rejected = 'No se ha podido guardar el documento. Se conserva la entrega anterior; vuelva a intentarlo.';
				return;
			}
			Shell::leave( self::url_for_code( $procedure_id, $code, array( self::ARG_NOTICE => 'entregado' ) ) );
		}
		self::$rejected = 'No puede entregar este documento: compruebe el centro, su solicitud y el plazo de entrega.';
	}






	private static function wanted(): string {
		if ( '' !== self::$rejected ) {
			return self::field( self::FIELD_CENTRE );
		}

		return sanitize_text_field( wp_unslash( (string) ( $_GET[ self::ARG_CENTRE ] ?? '' ) ) );
	}








	private static function choices( int $user_id, int $procedure_id ): array {
		$out = array();
		foreach ( ProcedureAccess::apply_codes( $user_id, $procedure_id ) as $code => $kind ) {
			$centro       = Applications::centre_of( $user_id, $code );
			$out[ $code ] = ( '' !== $centro['name'] ? $centro['name'] . ' (' . $code . ')' : $code )
				. ( ProcedureMetaKeys::KIND_RURAL_GROUP === $kind ? ', como agrupación de escuelas rurales' : '' );
		}
		return $out;
	}







	private static function values( array $application ): array {
		if ( '' !== self::$rejected ) {
			return self::submitted();
		}


		$coordinators = array_map(
			array( ApplicationMetaRegistration::class, 'with_surname' ),
			ApplicationMetaRegistration::sanitize_coordinator( $application['coordinator'] ?? array() )
		);
		$meta         = array() !== $application ? Applications::meta( (int) $application['id'] ) : array();
		return array(
			'position'          => (string) ( $meta[ ApplicationMetaKeys::APPLICANT_POSITION ] ?? '' ),
			'coordinators'      => $coordinators,
			'coordinator_name'  => (string) ( $coordinators[0]['name'] ?? '' ),
			'coordinator_dni'   => (string) ( $coordinators[0]['dni'] ?? '' ),
			'coordinator_email' => (string) ( $coordinators[0]['email'] ?? '' ),


			'accept'            => ApplicationOperations::accepted( $application ),
			'answers'           => (array) ( $application['answers'] ?? array() ),
		);
	}







	public static function render( $atts = array() ): string {
		unset( $atts );
		Assets::enqueue();
		$m = self::model();

		$m['people'] = PeopleForm::model( $m );

		$m['withdraw'] = WithdrawForm::model( $m );

		$m['progress'] = ApplicationProgress::model( $m );
		return ApplyFormView::html( $m );
	}
}

}








namespace Prc\PublicFront;

use Prc\Access\ApplicationActions;
use Prc\Meta\ApplicationMetaKeys;













if ( ! class_exists( 'Prc\PublicFront\WithdrawForm', false ) ) {
final class WithdrawForm {


	public const FIELD_OP = 'prc_withdraw_op';

	public const OP_WITHDRAW = 'withdraw';


	public const FIELD_APPLICATION = 'prc_withdraw_application';


	public const NONCE_FIELD = '_prc_withdraw_nonce';


	public const ANCHOR = 'prc-retirar';


	public const NOTICE = 'retirada';






	private static $rejected = '';






	public static function register(): void {
		add_action( 'init', array( self::class, 'handle' ), 20 );
	}







	public static function nonce_action( int $application_id ): string {
		return 'prc_withdraw_' . $application_id;
	}






	public static function handle(): void {

		$op    = isset( $_POST[ self::FIELD_OP ] ) && is_string( $_POST[ self::FIELD_OP ] ) ? sanitize_key( wp_unslash( $_POST[ self::FIELD_OP ] ) ) : '';
		$id    = isset( $_POST[ self::FIELD_APPLICATION ] ) && is_string( $_POST[ self::FIELD_APPLICATION ] ) ? absint( wp_unslash( $_POST[ self::FIELD_APPLICATION ] ) ) : 0;
		$nonce = isset( $_POST[ self::NONCE_FIELD ] ) && is_string( $_POST[ self::NONCE_FIELD ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ) : '';

		if ( self::OP_WITHDRAW !== $op || ! is_user_logged_in() ) {
			return;
		}
		if ( false === wp_verify_nonce( $nonce, self::nonce_action( $id ) ) ) {
			self::$rejected = 'El formulario estuvo abierto demasiado tiempo y el envío caducó. Vuelva a intentarlo.';
			return;
		}
		$user_id = get_current_user_id();

		if ( ! current_user_can( ApplicationActions::CAP_WITHDRAW, $id ) ) {
			self::$rejected = ApplicationActions::why_not_withdraw( $user_id, $id );
			return;
		}
		$procedure_id = (int) wp_get_post_parent_id( $id );
		$codigo       = (string) get_post_meta( $id, ApplicationMetaKeys::CENTRE_CODE, true );
		if ( ! ApplicationOperations::withdraw( $id, $user_id, ApplicationOperations::BY_APPLICANT ) ) {
			self::$rejected = 'No se ha podido retirar la solicitud. Vuelva a intentarlo.';
			return;
		}
		Shell::leave( ApplyForm::url_for_code( $procedure_id, $codigo, array( ApplyForm::ARG_NOTICE => self::NOTICE ) ) );
	}







	public static function model( array $m ): array {
		$id = (int) ( $m['application']['id'] ?? 0 );
		if ( $id <= 0 ) {
			return array(
				'show'         => false,
				'can'          => false,
				'why'          => '',
				'late'         => '',
				'rejected'     => self::$rejected,
				'application'  => 0,
				'action'       => '',
				'nonce_action' => '',
			);
		}
		$user_id = get_current_user_id();
		$porque  = ApplicationActions::why_not_withdraw( $user_id, $id );
		return array(
			'show'         => true,
			'can'          => '' === $porque,
			'why'          => $porque,
			'late'         => ApplicationActions::late_notice( $id ),
			'rejected'     => self::$rejected,
			'application'  => $id,
			'action'       => ApplyForm::url_for_code( (int) ( $m['procedure_id'] ?? 0 ), (string) ( $m['centre']['code'] ?? '' ) ),
			'nonce_action' => self::nonce_action( $id ),
		);
	}
}

}








namespace Prc\PublicFront;

use Prc\Access\ProcedureAccess;
use Prc\Domain\ApplicationPeople;
use Prc\Domain\DateRange;
use Prc\Domain\PeopleSpec;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\PostType\ApplicationPostType;















if ( ! class_exists( 'Prc\PublicFront\PeopleForm', false ) ) {
final class PeopleForm {

	public const FIELD_OP = 'prc_people_op';

	public const OP_ADD    = 'add';
	public const OP_CHANGE = 'change';
	public const OP_REMOVE = 'remove';

	public const FIELD_APPLICATION = 'prc_people_application';
	public const FIELD_ROW         = 'prc_people_row';
	public const FIELD_PRINT       = 'prc_people_print';
	public const FIELD_PERSON      = 'prc_person';





	public const NONCE_ACTION = 'prc_people';
	public const NONCE_FIELD  = '_prc_people_nonce';




	public const ANCHOR = 'prc-personas';




	public const NEW_ROW = 'nueva';






	private const DONE = array(
		'persona-alta'   => 'Persona dada de alta en la lista.',
		'persona-cambio' => 'Cambios guardados en la persona.',
		'persona-baja'   => 'Persona dada de baja de la lista.',
	);






	private const NEUTRAL = array(
		'persona-sin-cambios' => 'Esa persona ya tenía esos datos: no había nada que cambiar.',
	);






	private const NOTICE = array(
		self::OP_ADD    => 'persona-alta',
		self::OP_CHANGE => 'persona-cambio',
		self::OP_REMOVE => 'persona-baja',
	);






	private static $rejected = '';






	private static $rejected_row = '';






	private static $stale = false;






	private static $clash = false;






	public static function register(): void {
		add_action( 'init', array( self::class, 'handle' ), 20 );
	}








	public static function url( int $application_id, array $args = array() ): string {
		$base = ApplyForm::url_for_code(
			(int) wp_get_post_parent_id( $application_id ),
			(string) get_post_meta( $application_id, ApplicationMetaKeys::CENTRE_CODE, true ),
			$args
		);
		return '' === $base ? '' : $base . '#' . self::ANCHOR;
	}









	public static function nonce_action( string $op, int $application_id ): string {
		return self::NONCE_ACTION . '_' . $op . '_' . $application_id;
	}






	public static function handle(): void {
		$op = self::posted_op();
		if ( ! isset( self::NOTICE[ $op ] ) || ! is_user_logged_in() ) {
			return;
		}
		$fila               = self::field( self::FIELD_ROW );
		self::$rejected_row = self::OP_ADD === $op ? self::NEW_ROW : $fila;
		if ( ! self::nonce_ok() ) {
			self::$rejected = 'El formulario estuvo abierto demasiado tiempo y el envío caducó. Vuelva a enviarlo.';
			return;
		}

		$solicitud = self::posted_application();
		$user_id   = get_current_user_id();
		$huella    = self::field( self::FIELD_PRINT );
		if ( self::OP_ADD === $op ) {
			$res = People::add( $solicitud, $user_id, self::person() );
		} elseif ( self::OP_CHANGE === $op ) {
			$res = People::change( $solicitud, $user_id, $fila, $huella, self::person() );
		} else {
			$res = People::remove( $solicitud, $user_id, $fila, $huella );
		}
		if ( ! $res['ok'] ) {
			self::$rejected = $res['error'];
			self::$stale    = $res['stale'];
			self::$clash    = array() !== array_intersect_key( $res['errors'], array_flip( ApplicationPeople::CLASHES ) );
			return;
		}
		Shell::leave( self::url( $solicitud, array( ApplyForm::ARG_NOTICE => $res['changed'] ? self::NOTICE[ $op ] : 'persona-sin-cambios' ) ) );
	}






	private static function person(): array {
		if ( ! self::nonce_ok() ) {
			return array();
		}

		$crudo = isset( $_POST[ self::FIELD_PERSON ] ) ? wp_unslash( $_POST[ self::FIELD_PERSON ] ) : array();
		$out   = array();
		foreach ( array_diff( ApplicationPeople::FIELDS, array( 'id' ) ) as $campo ) {
			$valor         = is_array( $crudo ) ? ( $crudo[ $campo ] ?? '' ) : '';
			$out[ $campo ] = is_string( $valor ) ? sanitize_text_field( $valor ) : '';
		}
		return $out;
	}







	private static function field( string $nombre ): string {
		if ( ! self::nonce_ok() ) {
			return '';
		}

		return isset( $_POST[ $nombre ] ) && is_string( $_POST[ $nombre ] )
			? trim( sanitize_text_field( wp_unslash( $_POST[ $nombre ] ) ) ) : '';

	}






	private static function nonce_ok(): bool {
		return isset( $_POST[ self::NONCE_FIELD ] ) && is_string( $_POST[ self::NONCE_FIELD ] )
			&& false !== wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::nonce_action( self::posted_op(), self::posted_application() ) );
	}







	private static function posted_op(): string {

		return isset( $_POST[ self::FIELD_OP ] ) && is_string( $_POST[ self::FIELD_OP ] ) ? sanitize_key( wp_unslash( $_POST[ self::FIELD_OP ] ) ) : '';
	}






	private static function posted_application(): int {

		return isset( $_POST[ self::FIELD_APPLICATION ] ) && is_string( $_POST[ self::FIELD_APPLICATION ] ) ? absint( wp_unslash( $_POST[ self::FIELD_APPLICATION ] ) ) : 0;
	}








	public static function model( array $apply ): array {
		$procedure_id = (int) ( $apply['procedure_id'] ?? 0 );
		if ( $procedure_id <= 0 || '' !== (string) ( $apply['aviso'] ?? '' ) || ! People::enabled( $procedure_id )
			|| ApplicationMetaKeys::TYPE_TEACHER === (string) ( $apply['applicant_type'] ?? '' ) ) {
			return array( 'show' => false );
		}
		$spec      = People::spec( $procedure_id );
		$solicitud = (int) ( $apply['application']['id'] ?? 0 );
		$user_id   = get_current_user_id();
		$filas     = $solicitud > 0 ? self::rows( $spec, People::of( $solicitud ) ) : array();
		$puede     = $solicitud > 0 && ProcedureAccess::can_edit_people( $user_id, $solicitud );
		$despues   = $solicitud > 0 || empty( $apply['can_submit'] ) ? '' : self::after_submit( $spec, $procedure_id );


		$abierta  = $solicitud > 0 ? $puede : '' === $despues;
		$tecleado = '' !== self::$rejected && ! self::$stale ? self::person() : array();
		return array(
			'show'           => true,
			'application_id' => $solicitud,
			'roles'          => array_column( $spec['roles'], 'label', 'key' ),
			'groups'         => array_column( $spec['groups'], 'label', 'key' ),

			'group_roles'    => array_column( wp_list_filter( $spec['roles'], array( 'needs_group' => true ) ), 'label' ),
			'min'            => (int) $spec['min'],
			'max'            => (int) $spec['max'],
			'window'         => self::window( $spec ),

			'until'          => $abierta ? self::day( PeopleSpec::until( $spec, (string) ( $apply['closes_at'] ?? '' ), ProcedureState::today() ) ) : '',
			'after_submit'   => $despues,
			'rows'           => $filas,
			'complete'       => ApplicationPeople::is_complete( $spec, $filas ),
			'can_edit'       => $puede,
			'why'            => '' !== $despues ? $despues : self::why( $solicitud, $user_id, $puede ),
			'flash'          => self::flash(),



			'typed'          => $tecleado,
			'typed_row'      => '' !== self::$rejected ? self::$rejected_row : '',
			'clash_row'      => self::clash_row( $spec, $filas, $tecleado ),
		);
	}














	private static function clash_row( array $spec, array $filas, array $tecleado ): string {
		if ( array() === $tecleado || ! self::$clash ) {
			return '';
		}
		$id = self::NEW_ROW === self::$rejected_row ? '' : self::$rejected_row;
		return ApplicationPeople::clash( $spec, $filas, array( 'id' => $id ) + $tecleado );
	}








	public static function rows( array $spec, array $rows ): array {
		$out = array();
		foreach ( $rows as $fila ) {
			$out[] = $fila + array(
				'role_label'  => PeopleSpec::label( $spec, 'roles', $fila['role'] ),
				'group_label' => PeopleSpec::label( $spec, 'groups', $fila['group'] ),
				'print'       => ApplicationPeople::fingerprint( $fila ),
			);
		}
		return $out;
	}







	private static function window( array $spec ): string {
		$fechas = DateRange::of( (string) $spec['opens_at'], (string) $spec['closes_at'] );
		if ( '' !== (string) $spec['opens_at'] && '' !== (string) $spec['closes_at'] && '' !== $fechas ) {
			return 'Plazo para gestionar la lista: ' . lcfirst( $fechas ) . '.';
		}
		if ( '' === (string) $spec['opens_at'] && '' === (string) $spec['closes_at'] ) {
			return 'La lista se gestiona mientras se pueda modificar la solicitud.';
		}
		return '' !== (string) $spec['opens_at']
			? 'La lista se puede gestionar desde el ' . self::day( (string) $spec['opens_at'] ) . '.'
			: 'La lista se puede gestionar hasta el ' . self::day( (string) $spec['closes_at'] ) . '.';
	}













	private static function after_submit( array $spec, int $procedure_id ): string {
		return PeopleSpec::why_closed(
			$spec,
			array(
				'today'    => ProcedureState::today(),
				'archived' => ProcedureAccess::is_archived( $procedure_id ),
				'review'   => ApplicationMetaKeys::REVIEW_SUBMITTED,
				'state'    => ProcedureState::of_post( $procedure_id ),
			)
		);
	}









	private static function why( int $application_id, int $user_id, bool $puede ): string {
		if ( $application_id <= 0 ) {
			return 'Cuando presente la solicitud podrá dar de alta aquí a las personas que participan.';
		}
		return $puede ? '' : ProcedureAccess::why_not_people( $user_id, $application_id );
	}






	private static function flash(): array {
		if ( '' !== self::$rejected ) {
			return array(
				'tipo'  => 'error',
				'texto' => self::$rejected,
			);
		}

		$hecho = sanitize_key( wp_unslash( (string) ( $_GET[ ApplyForm::ARG_NOTICE ] ?? '' ) ) );
		if ( isset( self::NEUTRAL[ $hecho ] ) ) {
			return array(
				'tipo'  => 'aviso',
				'texto' => self::NEUTRAL[ $hecho ],
			);
		}
		return array(
			'tipo'  => isset( self::DONE[ $hecho ] ) ? 'ok' : '',
			'texto' => (string) ( self::DONE[ $hecho ] ?? '' ),
		);
	}














	public static function review( int $application_id, bool $history = false, array $args = array() ): array {
		unset( $history, $args );
		$procedure_id = (int) wp_get_post_parent_id( $application_id );
		if ( ApplicationPostType::POST_TYPE !== get_post_type( $application_id ) || ! People::enabled( $procedure_id ) ) {
			return array( 'show' => false );
		}
		$spec  = People::spec( $procedure_id );
		$filas = self::rows( $spec, People::of( $application_id ) );
		return array(
			'show'     => true,
			'rows'     => $filas,
			'min'      => (int) $spec['min'],
			'complete' => ApplicationPeople::is_complete( $spec, $filas ),
		);
	}







	private static function day( string $ymd ): string {
		return implode( '-', array_reverse( explode( '-', $ymd ) ) );
	}
}

}








namespace Prc\PublicFront;

use Prc\Domain\ApplicationCompleteness;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;



















if ( ! class_exists( 'Prc\PublicFront\ApplicationProgress', false ) ) {
final class ApplicationProgress {




	public const ARG_RECEIPT = 'justificante';




	public const ANCHOR_FORM = 'prc-solicitud-datos';




	public const ANCHOR_DELIVERIES = 'prc-entregas';






	private const VERB = array(
		'presentada' => 'presentada',
		'guardada'   => 'guardada',
	);







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

			'receipt'        => $id > 0 && '1' === sanitize_key( wp_unslash( (string) ( $_GET[ self::ARG_RECEIPT ] ?? '' ) ) ),
		);
		$out['flash'] = self::flash( (array) ( $m['flash'] ?? array() ), $out );
		return $out;
	}







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








	private static function flash( array $flash, array $p ): array {
		$flash += array(
			'tipo'  => '',
			'texto' => '',
		);

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







	public static function when( int $timestamp ): string {
		return $timestamp > 0 ? (string) wp_date( 'd-m-Y', $timestamp ) . ' a las ' . (string) wp_date( 'H:i', $timestamp ) : '';
	}
}

}








namespace Prc\PublicFront\View;

use Prc\PublicFront\Assets;
use Prc\PublicFront\Shell;
use Prc\Meta\ApplicationMetaKeys;









if ( ! class_exists( 'Prc\PublicFront\View\MyCentreView', false ) ) {
final class MyCentreView {







	public static function html( array $m ): string {
		$docente = ApplicationMetaKeys::TYPE_TEACHER === (string) ( $m['applicant_type'] ?? '' );

		$titulo = Shell::mine_label( $docente );
		if ( '' !== (string) $m['aviso'] ) {
			return Shell::render( $titulo, '', Shell::notice( 'aviso', (string) $m['aviso'] ) );
		}

		$centro = (array) $m['centre'];
		$filas  = (array) $m['rows'];
		$nombre = '' !== (string) $centro['name'] ? (string) $centro['name'] : 'Centro ' . (string) $centro['code'];
		$ambito = $docente ? 'Solicitudes presentadas por ' . (string) ( $m['applicant'] ?? '' ) : 'Procedimientos para ' . $nombre . ' (' . (string) $centro['code'] . ')';

		if ( array() === $filas ) {
			return Shell::render(
				$titulo,
				$ambito,
				'<div class="prc-mi-centro">' . self::count( 0, $docente )
					. '<p class="prc-vacio">' . esc_html( $docente ? 'Todavía no ha presentado ninguna solicitud. Puede hacerlo desde la ficha de un procedimiento dirigido al profesorado, mientras el plazo esté abierto.' : 'Su centro todavía no ha presentado ninguna solicitud. Se presentan desde la ficha de cada procedimiento, mientras su plazo esté abierto.' ) . '</p></div>'
			);
		}

		ob_start();
		?>
		<div class="prc-mi-centro">
			<p class="prc-ayuda-orden">Pulse una cabecera para ordenar la tabla; otra vez, al revés.</p>
			<?php
			echo self::count( count( $filas ), $docente );
			echo self::amendment( (array) $m['amend'] );
			echo self::incomplete( (array) ( $m['incomplete'] ?? array() ) );
			?>
			<div class="prc-tabla-caja">
				<table class="prc-tabla prc-tabla--densa prc-mi-tabla">
					<caption class="screen-reader-text"><?php echo esc_html( $ambito ); ?></caption>
					<thead>
						<tr>
							<?php foreach ( (array) $m['headers'] as $clave => $columna ) : ?>
								<?php
								if ( ( $docente && 'coordinador' === (string) $clave ) || ( empty( $m['has_people'] ) && 'personas' === (string) $clave ) ) {
									continue; }
								?>
								<?php echo self::heading( (array) $columna );?>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $filas as $fila ) : ?>
							<?php echo self::row( (array) $fila, $docente, ! empty( $m['has_people'] ) );?>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
		return Shell::render( $titulo, $ambito, (string) ob_get_clean() );
	}








	private static function count( int $total, bool $docente = false ): string {
		$nombre = $docente ? ( 1 === $total ? 'solicitud' : 'solicitudes' ) : ( 1 === $total ? 'procedimiento' : 'procedimientos' );
		return '<p class="prc-recuento">Mostrando <strong>' . (int) $total . '</strong> ' . $nombre . '.</p>';
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









	private static function incomplete( array $incomplete ): string {
		$cuantas = (int) ( $incomplete['count'] ?? 0 );
		if ( $cuantas < 1 ) {
			return '';
		}
		$texto = 1 === $cuantas
			? '<strong>1 solicitud</strong> está incompleta: le faltan personas en la lista.'
			: '<strong>' . $cuantas . ' solicitudes</strong> están incompletas: les faltan personas en la lista.';
		if ( '' !== (string) ( $incomplete['id'] ?? '' ) ) {
			$texto .= ' <a href="#fila-' . esc_attr( (string) $incomplete['id'] ) . '">' . ( 1 === $cuantas ? 'Ir a la solicitud' : 'Ir a la primera' ) . '</a>';
		}
		return '<p class="' . esc_attr( Assets::alert_class( 'warning' ) ) . '" role="status">' . $texto . '</p>';
	}







	private static function people_cell( array $fila ): string {
		if ( empty( $fila['people'] ) ) {
			return '<td class="prc-celda" data-rotulo="Personas">' . self::value( '' ) . '</td>';
		}
		$hay = (int) $fila['people_count'];
		$min = (int) $fila['people_min'];
		$max = (int) $fila['people_max'];
		if ( empty( $fila['people_complete'] ) ) {
			$faltan = $min - $hay;
			$dato   = '<span class="' . esc_attr( Assets::state_class( 'amend' ) ) . '">' . esc_html( 1 === $faltan ? 'Falta 1 persona' : sprintf( 'Faltan %d personas', $faltan ) ) . '</span>';
		} else {
			$dato = esc_html( $min > 0 ? sprintf( $min === $max ? '%1$d de %3$d' : '%1$d de %2$d a %3$d', $hay, $min, $max ) : sprintf( '%1$d, hasta %2$d', $hay, $max ) );
		}
		$out = $dato;
		if ( ! empty( $fila['people_open'] ) ) {
			$out .= ' <a class="prc-ver-solicitud" href="' . esc_url( (string) $fila['people_url'] ) . '">' . ( empty( $fila['people_complete'] ) ? 'Completar' : 'Cambiar' ) . '</a>';
			if ( '' !== (string) ( $fila['people_until'] ?? '' ) ) {
				$out .= '<span class="prc-nota-revision">' . esc_html( 'Hasta el ' . (string) $fila['people_until'] . ', incluido.' ) . '</span>';
			}
		} else {
			$porque = (string) ( $fila['people_why'] ?? '' );
			$out   .= '<span class="prc-nota-revision">' . esc_html( '' !== $porque ? $porque : 'La lista ya no se puede cambiar.' ) . '</span>';
		}
		return '<td class="prc-celda" data-rotulo="Personas">' . $out . '</td>';
	}










	private static function heading( array $columna ): string {
		$rotulo = esc_html( (string) $columna['label'] );
		if ( '' === (string) $columna['url'] ) {
			return '<th scope="col" class="prc-th">' . $rotulo . '</th>';
		}
		return '<th scope="col" class="prc-th prc-th--ordenable" aria-sort="' . esc_attr( (string) $columna['sort'] ) . '">'
			. '<a class="prc-th-enlace" href="' . esc_url( (string) $columna['url'] ) . '">' . $rotulo . '</a></th>';
	}









	private static function row( array $fila, bool $docente = false, bool $personas = false ): string {
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
			<td class="prc-celda" data-rotulo="Solicitante (cargo)"><?php echo self::value( $quien );?></td>
			<?php if ( ! $docente ) : ?>
				<td class="prc-celda" data-rotulo="Coordinador/a"><?php echo self::value( (string) $fila['coordinator'] );?></td>
			<?php endif; ?>
			<td class="prc-celda" data-rotulo="Opciones"><?php echo self::value( implode( '<br>', $opciones ), false );?></td>
			<td class="prc-celda prc-celda--revision" data-rotulo="Revisión">
				<span class="<?php echo esc_attr( Assets::state_class( (string) $fila['review'] ) ); ?>"><?php echo esc_html( (string) $fila['review_label'] ); ?></span>
				<?php if ( '' !== (string) $fila['note'] ) : ?>
					<span class="prc-nota-revision"><?php echo esc_html( (string) $fila['note'] ); ?></span>
				<?php endif; ?>
				<?php if ( '' !== (string) $fila['url'] ) : ?>
					<a class="prc-ver-solicitud" href="<?php echo esc_url( (string) $fila['url'] ); ?>">Ver la solicitud</a>
				<?php endif; ?>
				<?php if ( '' !== (string) ( $fila['late'] ?? '' ) ) : ?>
					<span class="prc-nota-revision"><?php echo esc_html( (string) $fila['late'] ); ?></span>
				<?php endif; ?>
			</td>
			<?php echo $personas ? self::people_cell( $fila ) : '';?>
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

}








namespace Prc\PublicFront;

use Prc\Access\ApplicationActions;
use Prc\Access\CentreScope;
use Prc\Access\ProcedureAccess;
use Prc\Domain\ApplicationPeople;
use Prc\Domain\PeopleSpec;
use Prc\Domain\ProcedureQuestions;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ApplicationMetaRegistration;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PublicFront\View\MyCentreView;













if ( ! class_exists( 'Prc\PublicFront\MyCentre', false ) ) {
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

			'personas'      => array(
				'label' => 'Personas',
				'field' => 'people_sort',
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
			'aviso'          => '',
			'applicant_type' => ApplicationMetaKeys::TYPE_CENTRE,
			'applicant'      => '',
			'centre'         => array(
				'code'      => '',
				'name'      => '',
				'ownership' => '',
			),
			'rows'           => array(),
			'sort'           => $orden,
			'headers'        => self::headers( $orden ),
			'amend'          => array(
				'count'  => 0,
				'closes' => '',
				'id'     => '',
			),
			'has_people'     => false,
			'incomplete'     => array(
				'count' => 0,
				'id'    => '',
			),
		);

		if ( ! is_user_logged_in() ) {
			$m['aviso'] = 'Debe iniciar sesión con su usuario para ver sus solicitudes.';
			return $m;
		}
		$user_id = get_current_user_id();
		if ( ! user_can( $user_id, ProcedureAccess::CAP_APPLY ) ) {
			$m['aviso'] = 'Su perfil no presenta solicitudes de centro. Si forma parte del equipo directivo de un centro, pídalo a quien administre el aplicativo.';
			return $m;
		}
		if ( user_can( $user_id, ProcedureAccess::CAP_APPLY_AS_TEACHER ) ) {
			$m['applicant_type'] = ApplicationMetaKeys::TYPE_TEACHER;
			$m['applicant']      = (string) wp_get_current_user()->display_name;
			$solicitudes         = Applications::for_teacher( $user_id );
			_prime_post_caches( array_unique( array_map( 'intval', wp_list_pluck( $solicitudes, 'post_parent' ) ) ), false, true );
			foreach ( $solicitudes as $solicitud ) {
				$m['rows'][] = self::row( $solicitud );
			}
			$m['rows']  = self::ordered( $m['rows'], $orden );
			$m['amend'] = self::amendment( $m['rows'] );
			return self::with_people( $m );
		}
		$m['centre'] = Applications::centre_of( $user_id );
		if ( '' === $m['centre']['code'] ) {
			$m['aviso'] = 'Su cuenta no tiene centro asignado, así que no puede presentar ni consultar solicitudes. El código de centro lo pone quien administra el aplicativo.';
			return $m;
		}


		$solicitudes = Applications::for_centre( array_keys( CentreScope::codes_for( $user_id ) ) );


		_prime_post_caches( array_unique( array_map( 'intval', wp_list_pluck( $solicitudes, 'post_parent' ) ) ), false, true );
		foreach ( $solicitudes as $solicitud ) {
			$m['rows'][] = self::row( $solicitud );
		}
		$m['rows']  = self::ordered( $m['rows'], $orden );
		$m['amend'] = self::amendment( $m['rows'] );
		return self::with_people( $m );
	}











	private static function with_people( array $m ): array {
		$cortas = array();
		foreach ( (array) $m['rows'] as $fila ) {
			$m['has_people'] = $m['has_people'] || ! empty( $fila['people'] );
			if ( empty( $fila['people_complete'] ) && ! empty( $fila['people_open'] ) ) {
				$cortas[] = (string) $fila['procedure_id'];
			}
		}
		$m['incomplete'] = array(
			'count' => count( $cortas ),
			'id'    => (string) ( $cortas[0] ?? '' ),
		);
		return $m;
	}













	private static function people( int $application_id, int $procedure_id, bool $personas ): array {
		if ( ! $personas ) {
			return array(
				'people'          => false,
				'people_url'      => '',
				'people_count'    => 0,
				'people_complete' => true,
				'people_sort'     => '',
			);
		}
		$spec    = People::spec( $procedure_id );
		$filas   = People::of( $application_id );
		$porque  = ProcedureAccess::why_not_people( get_current_user_id(), $application_id );
		$abierta = '' === $porque;
		return array(
			'people'          => true,
			'people_url'      => PeopleForm::url( $application_id ),
			'people_count'    => count( $filas ),
			'people_min'      => (int) $spec['min'],
			'people_max'      => (int) $spec['max'],
			'people_complete' => ApplicationPeople::is_complete( $spec, $filas ),
			'people_open'     => $abierta,
			'people_why'      => $porque,
			'people_until'    => $abierta ? self::day( PeopleSpec::until( $spec, (string) get_post_meta( $procedure_id, ProcedureMetaKeys::CLOSES_AT, true ), ProcedureState::today() ) ) : '',
			'people_sort'     => (string) count( $filas ),
		);
	}







	private static function row( \WP_Post $application ): array {
		$procedure_id = (int) $application->post_parent;
		$meta         = Applications::meta( (int) $application->ID );
		$autor        = get_userdata( (int) $application->post_author );
		$coord        = ApplicationMetaRegistration::sanitize_coordinator( $meta[ ApplicationMetaKeys::COORDINATOR ] );
		$revision     = (string) $meta[ ApplicationMetaKeys::REVIEW_STATE ];
		$opciones     = self::options( $procedure_id, (array) $meta[ ApplicationMetaKeys::ANSWERS ] );
		$cierre       = (string) get_post_meta( $procedure_id, ProcedureMetaKeys::AMEND_CLOSES_AT, true );
		$estado       = ProcedureState::of_post( $procedure_id );
		$personas     = ApplicationMetaKeys::TYPE_TEACHER !== ApplicationMetaRegistration::sanitize_applicant_type( $meta[ ApplicationMetaKeys::APPLICANT_TYPE ] )
			&& People::enabled( $procedure_id );



		$subsanar = ApplicationMetaKeys::REVIEW_AMEND === $revision
			&& ( '' === $cierre || ProcedureState::today() <= $cierre );

		$url = ApplicationMetaKeys::TYPE_TEACHER === ApplicationMetaRegistration::sanitize_applicant_type( $meta[ ApplicationMetaKeys::APPLICANT_TYPE ] )
			? ApplyForm::url( $procedure_id )
			: ApplyForm::url_for_code( $procedure_id, (string) $meta[ ApplicationMetaKeys::CENTRE_CODE ] );

		return self::people( (int) $application->ID, $procedure_id, $personas ) + array(
			'procedure_id' => (string) $procedure_id,
			'title'        => (string) get_post_field( 'post_title', $procedure_id ),
			'url'          => $url,
			'sheet'        => (string) get_permalink( $procedure_id ),
			'date'         => (string) get_the_date( 'Y-m-d H:i', $application ),
			'date_label'   => (string) get_the_date( 'd-m-Y', $application ),
			'applicant'    => false !== $autor ? (string) $autor->display_name : '',
			'position'     => (string) ( ApplicationMetaKeys::positions()[ $meta[ ApplicationMetaKeys::APPLICANT_POSITION ] ] ?? '' ),
			'coordinator'  => ApplicationMetaRegistration::full_names( $coord ),
			'options'      => $opciones,
			'options_text' => implode( ' ', $opciones ),
			'state'        => $estado,
			'state_label'  => ProcedureState::label(
				$estado,
				(bool) get_post_meta( $procedure_id, ProcedureMetaKeys::APPLICATIONS_CLOSED, true )
			),
			'review'       => $revision,
			'review_label' => (string) ( ApplicationMetaKeys::review_states()[ $revision ] ?? '' ),
			'note'         => (string) $meta[ ApplicationMetaKeys::REVIEW_NOTE ],
			'amend'        => $subsanar,
			'amend_closes' => $cierre,



			'late'         => ApplicationActions::late_notice( (int) $application->ID ),
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

}









namespace Prc\PublicFront;

use Prc\Access\ObserverScope;
use Prc\Meta\ProcedureMetaKeys;












if ( ! class_exists( 'Prc\PublicFront\ObserveSettings', false ) ) {
final class ObserveSettings {


	public const FIELD_OBSERVABLE = 'prc_observable';


	public const FIELD_COLUMNS = 'prc_observer_columns';


	public const FIELD_FILES = 'prc_observer_files';


	public const FIELD_EXPORT = 'prc_observer_export';


	public const FIELD_NOTIFY = 'prc_observer_notify';







	public static function offered( int $procedure_id ): array {
		return ObserverScope::allowed( ApplicationExport::groups( $procedure_id ) );
	}











	public static function columns( int $procedure_id ): array {
		return ObserverScope::columns( get_post_meta( $procedure_id, ProcedureMetaKeys::OBSERVER_COLUMNS, true ), ApplicationExport::groups( $procedure_id ) );
	}







	public static function model( int $procedure_id ): array {
		$leer = static function ( string $clave ) use ( $procedure_id ): bool {
			return (bool) get_post_meta( $procedure_id, $clave, true );
		};
		return array(
			'observable'   => $leer( ProcedureMetaKeys::OBSERVABLE ),
			'columns'      => array_keys( self::columns( $procedure_id ) ),
			'groups'       => self::offered( $procedure_id ),
			'group_labels' => ApplicationExport::group_labels( $procedure_id ),
			'files'        => $leer( ProcedureMetaKeys::OBSERVER_FILES ),
			'export'       => $leer( ProcedureMetaKeys::OBSERVER_EXPORT ),
			'notify'       => $leer( ProcedureMetaKeys::OBSERVER_NOTIFY ),
			'url'          => Observe::url( $procedure_id ),
		);
	}










	public static function handle( int $procedure_id ): void {

		self::save( $procedure_id, $_POST );
		Shell::leave( ProcedureEditor::url( $procedure_id, ProcedureEditor::PANEL_OBSERVE, array( ProcedureEditor::ARG_NOTICE => 'consulta' ) ) );
	}











	public static function save( int $procedure_id, array $post ): void {
		$ofrecidas = array();
		foreach ( self::offered( $procedure_id ) as $columnas ) {
			$ofrecidas += $columnas;
		}
		$pedidas  = isset( $post[ self::FIELD_COLUMNS ] ) && is_array( $post[ self::FIELD_COLUMNS ] ) ? wp_unslash( $post[ self::FIELD_COLUMNS ] ) : array();
		$columnas = array();
		foreach ( $pedidas as $clave ) {
			$clave = is_string( $clave ) ? sanitize_text_field( $clave ) : '';
			if ( isset( $ofrecidas[ $clave ] ) ) {
				$columnas[] = $clave;
			}
		}
		update_post_meta( $procedure_id, ProcedureMetaKeys::OBSERVER_COLUMNS, $columnas );
		foreach ( array(
			self::FIELD_OBSERVABLE => ProcedureMetaKeys::OBSERVABLE,
			self::FIELD_FILES      => ProcedureMetaKeys::OBSERVER_FILES,
			self::FIELD_EXPORT     => ProcedureMetaKeys::OBSERVER_EXPORT,
			self::FIELD_NOTIFY     => ProcedureMetaKeys::OBSERVER_NOTIFY,
		) as $campo => $clave ) {
			update_post_meta( $procedure_id, $clave, ! empty( $post[ $campo ] ) );
		}
	}
}

}









namespace Prc\PublicFront\View;

use Prc\Domain\ApplicationFilter;
use Prc\PublicFront\ApplicationFiles;
use Prc\PublicFront\Assets;
use Prc\PublicFront\Observe;
use Prc\PublicFront\ObserveSettings;
use Prc\PublicFront\ProcedureEditor;
use Prc\PublicFront\Shell;










if ( ! class_exists( 'Prc\PublicFront\View\ObserveView', false ) ) {
final class ObserveView {


	private const TITLE = 'Consulta de solicitudes';







	public static function html( array $m ): string {
		if ( 'table' === (string) $m['mode'] ) {
			return self::procedure( $m );
		}
		if ( '' !== (string) $m['aviso'] ) {
			$volver = 'error' === (string) $m['aviso_tipo'] && '' !== (string) $m['list_url']
				? '<p><a href="' . esc_url( (string) $m['list_url'] ) . '">Volver a la consulta</a></p>'
				: '';
			return Shell::render( self::TITLE, '', Shell::notice( (string) $m['aviso_tipo'], (string) $m['aviso'] ) . $volver );
		}
		return Shell::render( self::TITLE, self::subtitle( $m ), self::listing( (array) $m['procedures'] ) );
	}







	private static function subtitle( array $m ): string {
		return '' !== (string) $m['zones']
			? 'Solicitudes de los centros de: ' . (string) $m['zones']
			: 'Solicitudes de todas las zonas de sus procedimientos';
	}







	private static function listing( array $procedures ): string {
		if ( array() === $procedures ) {
			return '<p class="prc-vacio">Todavía no hay ningún procedimiento con solicitudes que pueda consultar. Saldrán aquí en cuanto un procedimiento admita la consulta y algún centro de su zona solicite.</p>';
		}
		ob_start();
		?>
		<div class="prc-tabla-caja prc-consulta">
			<table class="prc-tabla prc-tabla--densa table">
				<caption class="screen-reader-text">Procedimientos que puede consultar</caption>
				<thead>
					<tr>
						<th class="prc-th" scope="col">Procedimiento</th>
						<th class="prc-th" scope="col">Estado</th>
						<th class="prc-th" scope="col">Solicitudes</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $procedures as $fila ) : ?>
						<tr>
							<td class="prc-celda" data-rotulo="Procedimiento"><a href="<?php echo esc_url( (string) $fila['url'] ); ?>"><?php echo esc_html( (string) $fila['title'] ); ?></a></td>
							<td class="prc-celda" data-rotulo="Estado"><?php echo esc_html( (string) $fila['state'] ); ?></td>
							<td class="prc-celda" data-rotulo="Solicitudes"><?php echo esc_html( (string) (int) $fila['count'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
		return (string) ob_get_clean();
	}







	private static function procedure( array $m ): string {
		$t = (array) $m['table'];
		ob_start();
		echo Shell::notice( (string) $m['aviso_tipo'], (string) $m['aviso'] );
		echo '<p><a href="' . esc_url( (string) $m['list_url'] ) . '">Volver a la consulta</a></p>';
		if ( 0 === (int) $t['all'] ) {
			echo '<p class="prc-vacio">Todavía no hay solicitudes que pueda consultar en este procedimiento.</p>';
			return Shell::render( (string) $m['title'], self::subtitle( $m ), (string) ob_get_clean() );
		}
		echo '<div class="prc-banda-tabla"><p class="prc-recuento">' . esc_html( self::count_text( $t ) ) . '</p>';
		echo self::export( $m, $t );
		echo '</div>';
		if ( array() !== (array) $t['states'] ) {
			echo ApplicationsPanelView::states( $t );
		}
		echo self::filters( $t );
		if ( 0 === (int) $t['total'] ) {
			echo '<p class="prc-vacio">Ninguna solicitud cumple el filtro.';
			echo '' !== (string) $t['reset_url'] ? ' <a href="' . esc_url( (string) $t['reset_url'] ) . '">Quitar los filtros</a>' : '';
			echo '</p>';
		} else {
			echo self::table( (array) $m['columns'], (array) $m['rows'] );
			echo ApplicationsPanelView::pager( $t );
		}
		return Shell::render( (string) $m['title'], self::subtitle( $m ), (string) ob_get_clean() );
	}







	private static function count_text( array $t ): string {
		$total = (int) $t['total'];
		if ( empty( $t['active'] ) && 1 === (int) $t['pages'] ) {
			return sprintf( 1 === $total ? 'Mostrando %d solicitud. Solo lectura.' : 'Mostrando %d solicitudes. Solo lectura.', $total );
		}
		if ( 0 === $total ) {
			return sprintf( 'Ninguna de las %d solicitudes cumple el filtro.', (int) $t['all'] );
		}
		$texto = sprintf( 'Mostrando %1$d–%2$d de %3$d solicitudes', (int) $t['from'], (int) $t['to'], $total );
		return $texto . ( empty( $t['active'] ) ? '.' : sprintf( ' que cumplen el filtro, de %d en total.', (int) $t['all'] ) );
	}











	private static function export( array $m, array $t ): string {
		if ( true !== $m['can_export'] ) {
			return '';
		}
		$id = (int) $m['procedure_id'];
		return '<form class="prc-form prc-exportar" method="post" action="">'
			. wp_nonce_field( (string) $m['nonce'], Observe::NONCE_NAME, false, false )
			. '<input type="hidden" name="' . esc_attr( Observe::FIELD_DO ) . '" value="' . esc_attr( Observe::OP_EXPORT ) . '" />'
			. '<input type="hidden" name="' . esc_attr( Observe::FIELD_ID ) . '" value="' . esc_attr( (string) $id ) . '" />'
			. ApplicationsPanelView::carry( (array) $t['args'] )
			. '<button class="' . esc_attr( Assets::button_class() ) . '" type="submit">' . esc_html( empty( $t['active'] ) ? 'Exportar a CSV' : 'Exportar las del filtro a CSV' ) . '</button>'
			. '</form>';
	}







	private static function filters( array $t ): string {
		$f        = (array) $t['filter'];
		$opciones = (array) $t['options'];
		$out      = '<form class="prc-filtros" method="get" action="' . esc_url( (string) $t['action'] ) . '" role="search" aria-label="Filtrar las solicitudes">'
			. ApplicationsPanelView::carry( (array) $t['hidden'] )
			. '<div class="prc-filtros__campo"><label class="prc-campo__etiqueta" for="prc-filtro-buscar">Centro o código</label>'
			. '<input class="prc-control" type="search" id="prc-filtro-buscar" name="' . esc_attr( ApplicationFilter::ARG_SEARCH ) . '" value="' . esc_attr( (string) $f['search'] ) . '" maxlength="' . esc_attr( (string) ApplicationFilter::MAX_SEARCH ) . '" /></div>';
		if ( array() !== (array) $t['states'] ) {
			$estados = array();
			foreach ( (array) $t['states'] as $estado ) {
				$estados[ (string) $estado['key'] ] = '' === (string) $estado['key'] ? 'Todos los estados' : (string) $estado['label'];
			}
			$out .= ApplicationsPanelView::select( 'prc-filtro-estado', ApplicationFilter::ARG_REVIEW, 'Estado de revisión', $estados, (string) $f['review'] );
		}
		if ( array() !== (array) $opciones['islands'] ) {
			$out .= ApplicationsPanelView::select( 'prc-filtro-isla', ApplicationFilter::ARG_ISLAND, 'Isla', array( '' => 'Todas las islas' ) + (array) $opciones['islands'], (string) $f['island'] );
		}

		if ( array() !== (array) $opciones['zones'] ) {
			$out .= ApplicationsPanelView::select( 'prc-filtro-zona', ApplicationFilter::ARG_ZONE, (string) $t['zone_label'], array( '' => 'Todas' ) + (array) $opciones['zones'], (string) $f['zone'] );
		}
		$out .= '<div class="prc-filtros__acciones"><button class="' . esc_attr( Assets::button_class() ) . '" type="submit">Filtrar</button>';
		if ( '' !== (string) $t['reset_url'] ) {
			$out .= '<a class="prc-filtros__quitar" href="' . esc_url( (string) $t['reset_url'] ) . '">Quitar los filtros</a>';
		}
		return $out . '</div></form>';
	}








	private static function table( array $columns, array $rows ): string {
		$out = '<div class="prc-tabla-caja prc-consulta"><table class="prc-tabla prc-tabla--densa table">'
			. '<caption class="screen-reader-text">Solicitudes que puede consultar, en solo lectura</caption><thead><tr>';
		foreach ( $columns as $rotulo ) {
			$out .= '<th class="prc-th" scope="col">' . esc_html( (string) $rotulo ) . '</th>';
		}
		$out .= '</tr></thead><tbody>';
		foreach ( $rows as $fila ) {
			$out .= '<tr>';
			foreach ( $columns as $clave => $rotulo ) {
				$out .= '<td class="prc-celda" data-rotulo="' . esc_attr( (string) $rotulo ) . '">' . self::cell( $fila, (string) $clave ) . '</td>';
			}
			$out .= '</tr>';
		}
		return $out . '</tbody></table></div>';
	}








	private static function cell( array $fila, string $clave ): string {
		$doc = (array) ( $fila['files'][ $clave ] ?? array() );
		if ( '' !== (string) ( $doc['id'] ?? '' ) ) {
			return sprintf(
				'<a class="prc-descarga" href="%1$s" download>%2$s</a>',
				esc_url( ApplicationFiles::url( (int) $doc['application'], (string) $doc['id'] ) ),
				esc_html( '' !== (string) ( $doc['name'] ?? '' ) ? (string) $doc['name'] : 'Descargar' )
			);
		}
		$texto = (string) ( $fila['cells'][ $clave ] ?? '' );
		return '' !== $texto ? esc_html( $texto ) : '<span class="prc-sindato" title="Sin datos">&mdash;</span>';
	}









	public static function settings( array $m ): string {
		$c  = (array) $m['observe'];
		$op = ProcedureEditor::OP_OBSERVE;
		ob_start();
		?>
		<form class="prc-form prc-form--taller" method="post" action="">
			<?php wp_nonce_field( ProcedureEditor::nonce_action( $op ), ProcedureEditor::nonce_name( $op ), false ); ?>
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_DO ); ?>" value="<?php echo esc_attr( $op ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( ProcedureEditor::FIELD_PROCEDURE ); ?>" value="<?php echo esc_attr( (string) (int) $m['procedure_id'] ); ?>" />
			<fieldset class="prc-seccion-campos">
				<legend class="prc-seccion__titulo">Consulta de solo lectura</legend>
				<p class="prc-intro">Quien consulta una zona ve, sin poder cambiar nada, las solicitudes de los centros de su zona. Lo que ve lo decide este procedimiento. El DNI o NIE, la nota de revisión y el historial no se enseñan nunca.</p>
				<?php echo self::check( ObserveSettings::FIELD_OBSERVABLE, 'Admitir la consulta de solicitudes por zona', true === $c['observable'] );?>
				<?php if ( true === $c['observable'] && '' !== (string) $c['url'] ) : ?>
					<p class="prc-campo__ayuda">Se consulta en <a href="<?php echo esc_url( (string) $c['url'] ); ?>">la consulta de este procedimiento</a>.</p>
				<?php endif; ?>
			</fieldset>
			<fieldset class="prc-seccion-campos">
				<legend class="prc-seccion__titulo">Columnas que se enseñan</legend>
				<p class="prc-intro">Sin ninguna marcada, se enseñan las de la solicitud, el centro y la coordinación.</p>
				<?php foreach ( (array) $c['groups'] as $grupo => $columnas ) : ?>
					<p class="prc-campo__etiqueta"><?php echo esc_html( (string) ( $c['group_labels'][ $grupo ] ?? $grupo ) ); ?></p>
					<div class="prc-opciones">
						<?php foreach ( (array) $columnas as $clave => $rotulo ) : ?>
							<div class="prc-opcion">
								<input type="checkbox" id="<?php echo esc_attr( 'prc-observe-' . sanitize_html_class( str_replace( ':', '-', (string) $clave ) ) ); ?>" name="<?php echo esc_attr( ObserveSettings::FIELD_COLUMNS ); ?>[]" value="<?php echo esc_attr( (string) $clave ); ?>" <?php checked( in_array( (string) $clave, (array) $c['columns'], true ) ); ?> />
								<label for="<?php echo esc_attr( 'prc-observe-' . sanitize_html_class( str_replace( ':', '-', (string) $clave ) ) ); ?>"><?php echo esc_html( (string) $rotulo ); ?></label>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
			</fieldset>
			<fieldset class="prc-seccion-campos">
				<legend class="prc-seccion__titulo">Qué más puede hacer quien consulta</legend>
				<?php echo self::check( ObserveSettings::FIELD_FILES, 'Pueden descargar los documentos de las solicitudes de su zona', true === $c['files'] );?>
				<?php echo self::check( ObserveSettings::FIELD_EXPORT, 'Pueden exportar a CSV lo que ven', true === $c['export'] );?>
				<?php echo self::check( ObserveSettings::FIELD_NOTIFY, 'Avisar también a quienes consultan la zona de cada alta, cambio o baja de personas', true === $c['notify'] );?>
			</fieldset>
			<?php echo PanelParts::submit( 'Guardar la consulta' );?>
		</form>
		<?php
		return (string) ob_get_clean();
	}









	private static function check( string $name, string $label, bool $checked ): string {
		return '<div class="prc-opcion"><input type="checkbox" id="' . esc_attr( 'prc-' . $name ) . '" name="' . esc_attr( $name ) . '" value="1"' . ( $checked ? ' checked="checked"' : '' ) . ' />'
			. '<label for="' . esc_attr( 'prc-' . $name ) . '">' . esc_html( $label ) . '</label></div>';
	}
}

}









namespace Prc\PublicFront;

use Prc\Access\ProcedureAccess;
use Prc\Domain\ApplicationFilter;
use Prc\Domain\ProcedureQuestions;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ApplicationPostType;
use Prc\PostType\ProcedurePostType;
use Prc\PublicFront\View\ObserveView;





















if ( ! class_exists( 'Prc\PublicFront\Observe', false ) ) {
final class Observe {

	public const SHORTCODE = 'prc_observe';


	public const ARG_PROCEDURE = Shell::ARG_PROCEDURE;


	public const FIELD_DO   = 'prc_observe_do';
	public const OP_EXPORT  = 'export';
	public const FIELD_ID   = 'prc_observe_procedure';
	public const NONCE_NAME = 'prc_observe_nonce';






	public const NOT_FOUND = 'Ese procedimiento no existe o no tiene consulta de solicitudes. Elija uno de la lista.';






	private static $rejected = '';






	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'render' ) );
		add_action( 'init', array( self::class, 'handle' ), 20 );
	}








	public static function url( int $procedure_id = 0, array $args = array() ): string {
		if ( $procedure_id > 0 ) {
			$args = array( self::ARG_PROCEDURE => $procedure_id ) + $args;
		}
		return Shell::url( 'observe', $args );
	}







	public static function nonce_action( int $procedure_id ): string {
		return 'prc_observe_export_' . $procedure_id;
	}













	public static function filter( array $source, array $columns ): array {
		$filtro               = ApplicationsPanel::filter( $source );
		$filtro['incomplete'] = false;
		if ( ! isset( $columns['state'] ) ) {
			$filtro['review'] = '';
		}
		return $filtro;
	}









	public static function model( array $request ): array {
		$m       = array(
			'aviso'      => '',
			'aviso_tipo' => 'aviso',
			'mode'       => 'list',
			'list_url'   => self::url(),
			'zones'      => '',
			'procedures' => array(),
		);
		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			$m['aviso'] = 'Debe iniciar sesión con su usuario para consultar solicitudes.';
			return $m;
		}
		if ( ! ProcedureAccess::can_observe( $user_id ) ) {
			$m['aviso'] = self::why_not( $user_id );
			return $m;
		}
		$m['zones']   = ProcedureAccess::can_manage_procedures( $user_id ) ? '' : Shell::zone_names( $user_id );
		$procedure_id = isset( $request[ self::ARG_PROCEDURE ] ) && is_string( $request[ self::ARG_PROCEDURE ] ) ? absint( $request[ self::ARG_PROCEDURE ] ) : 0;
		if ( $procedure_id <= 0 ) {
			$m['procedures'] = self::procedures( $user_id );
			return $m;
		}
		$ambito = null;
		if ( ! ProcedureAccess::can_observe( $user_id, $procedure_id, $ambito ) ) {
			$m['aviso']      = self::NOT_FOUND;
			$m['aviso_tipo'] = 'error';
			return $m;
		}
		return array_merge( $m, self::table( $user_id, $procedure_id, $ambito, $request ) );
	}







	private static function why_not( int $user_id ): string {
		if ( ! user_can( $user_id, ProcedureAccess::CAP_VIEW_APPLICATIONS ) ) {
			return 'Su perfil no consulta solicitudes. Si debería hacerlo, pídalo a quien administre el aplicativo.';
		}
		return array() === ProcedureAccess::observer_zones( $user_id )
			? 'Su cuenta no tiene ninguna zona asignada, así que no hay solicitudes que consultar. La zona la pone quien administra el aplicativo.'
			: 'Ninguna de las zonas de su cuenta tiene centros en el catálogo, así que no hay solicitudes que consultar. Revíselo con quien administre el aplicativo.';
	}










	private static function procedures( int $user_id ): array {
		$ids = get_posts(
			array(
				'post_type'        => ProcedurePostType::POST_TYPE,
				'post_status'      => 'publish',
				'numberposts'      => -1,
				'fields'           => 'ids',
				'orderby'          => 'title',
				'order'            => 'ASC',
				'meta_key'         => ProcedureMetaKeys::OBSERVABLE,
				'meta_value'       => '1',
				'suppress_filters' => true,
			)
		);
		$ids = array_map( 'intval', is_array( $ids ) ? $ids : array() );
		if ( array() === $ids ) {
			return array();
		}
		$codigos = self::codes_by_procedure( $ids );
		$out     = array();
		foreach ( $ids as $id ) {
			$ambito = null;
			if ( ! ProcedureAccess::can_observe( $user_id, $id, $ambito ) ) {
				continue;
			}
			$suyas = (array) ( $codigos[ $id ] ?? array() );
			if ( isset( $ambito['codes'] ) ) {
				$suyas = array_intersect( $suyas, $ambito['codes'] );
				if ( array() === $suyas ) {
					continue;
				}
			}
			$out[] = array(
				'id'    => $id,
				'title' => (string) get_post_field( 'post_title', $id ),
				'state' => ProcedureState::label( ProcedureState::of_post( $id ) ),
				'count' => count( $suyas ),
				'url'   => self::url( $id ),
			);
		}
		return $out;
	}







	private static function codes_by_procedure( array $procedure_ids ): array {
		global $wpdb;
		$huecos = implode( ',', array_fill( 0, count( $procedure_ids ), '%d' ) );

		$filas = $wpdb->get_results(
			$wpdb->prepare(



				'SELECT p.post_parent, COALESCE(m.meta_value, \'\') AS code FROM ' . $wpdb->posts . ' p LEFT JOIN ' . $wpdb->postmeta . ' m ON m.post_id = p.ID AND m.meta_key = %s WHERE p.post_type = %s AND p.post_status = \'publish\' AND p.post_parent IN (' . $huecos . ')',
				array_merge( array( ApplicationMetaKeys::CENTRE_CODE, ApplicationPostType::POST_TYPE ), $procedure_ids )
			)
		);

		$out = array();
		foreach ( (array) $filas as $fila ) {
			$out[ (int) $fila->post_parent ][] = (string) $fila->code;
		}
		return $out;
	}










	private static function table( int $user_id, int $procedure_id, array $scope, array $request ): array {
		$columnas = ObserveSettings::columns( $procedure_id );
		$res      = ApplicationQuery::run( $procedure_id, self::filter( $request, $columnas ), $scope );
		$filtro   = $res['filter'];
		$url      = static function ( array $changes = array() ) use ( $procedure_id, $filtro ): string {
			return self::url( $procedure_id, array_map( 'rawurlencode', ApplicationFilter::query( $filtro, $changes ) ) );
		};
		$base     = self::url( $procedure_id );
		return array(
			'mode'         => 'table',
			'aviso'        => self::$rejected,
			'aviso_tipo'   => 'error',
			'procedure_id' => $procedure_id,
			'title'        => (string) get_post_field( 'post_title', $procedure_id ),
			'columns'      => $columnas,


			'rows'         => self::rows( $procedure_id, $res['posts'], $columnas, (bool) get_post_meta( $procedure_id, ProcedureMetaKeys::OBSERVER_FILES, true ) ),
			'can_export'   => self::may_export( $procedure_id ),
			'nonce'        => self::nonce_action( $procedure_id ),
			'table'        => array(
				'filter'     => $filtro,
				'active'     => $res['active'],
				'args'       => ApplicationFilter::query( array( 'page' => 1 ) + $filtro ),
				'all'        => (int) $res['all'],
				'total'      => (int) $res['total'],
				'page'       => (int) $res['page'],
				'pages'      => (int) $res['pages'],
				'from'       => (int) $res['from'],
				'to'         => (int) $res['to'],
				'states'     => isset( $columnas['state'] ) ? self::states( $res, $procedure_id ) : array(),
				'options'    => $res['options'],
				'zone_label' => CentreColumns::zone_label(),
				'teachers'   => ProcedureMetaKeys::AUDIENCE_TEACHERS === get_post_meta( $procedure_id, ProcedureMetaKeys::AUDIENCE, true ),
				'pager'      => ApplicationsPanel::pager( $url, $filtro, (int) $res['pages'] ),
				'action'     => strtok( $base, '?' ),
				'hidden'     => wp_parse_args( (string) wp_parse_url( $base, PHP_URL_QUERY ) ),
				'reset_url'  => $res['active'] ? $base : '',
			),
		);
	}








	private static function states( array $res, int $procedure_id ): array {
		$vacio = ApplicationFilter::sanitize( array() );
		$out   = array(
			array(
				'key'     => '',
				'label'   => 'Todas',
				'count'   => (int) $res['all'],
				'url'     => self::url( $procedure_id ),
				'current' => '' === $res['filter']['review'],
			),
		);
		foreach ( ApplicationMetaKeys::review_states() as $clave => $rotulo ) {
			$out[] = array(
				'key'     => $clave,
				'label'   => $rotulo,
				'count'   => (int) ( $res['counts'][ $clave ] ?? 0 ),
				'url'     => self::url( $procedure_id, array_map( 'rawurlencode', ApplicationFilter::query( $vacio, array( 'review' => $clave ) ) ) ),
				'current' => $clave === $res['filter']['review'],
			);
		}
		return $out;
	}















	private static function rows( int $procedure_id, array $posts, array $columns, bool $files ): array {
		$catalogo  = array() !== array_intersect_key( $columns, array_flip( CentreColumns::KEYS ) );
		$personas  = array() !== array_intersect_key( $columns, PeopleExport::COLUMNS ) ? PeopleExport::spec( $procedure_id ) : null;
		$ficheros  = array();
		$preguntas = Applications::questions( $procedure_id );
		foreach ( $preguntas as $pregunta ) {
			if ( ProcedureQuestions::TYPE_FILE === (string) ( $pregunta['type'] ?? '' ) && isset( $columns[ (string) $pregunta['key'] ] ) ) {
				$ficheros[ (string) $pregunta['key'] ] = true;
			}
		}
		$out = array();
		foreach ( Applications::rows( $procedure_id, $posts, $catalogo ) as $fila ) {
			if ( null !== $personas ) {
				$fila += PeopleExport::cells( $personas, People::of( (int) $fila['id'] ) );
			}
			$celdas = array();
			foreach ( array_keys( $columns ) as $clave ) {
				$celdas[ $clave ] = (string) ( $fila[ $clave ] ?? '' );
			}
			$out[] = array(
				'id'    => (int) $fila['id'],
				'cells' => $celdas,
				'files' => $files ? array_intersect_key( (array) $fila[ Applications::KEY_FILES ], $ficheros ) : array(),
			);
		}
		return $out;
	}













	public static function handle(): void {

		if ( self::OP_EXPORT !== sanitize_key( wp_unslash( (string) ( $_POST[ self::FIELD_DO ] ?? '' ) ) ) || ! is_user_logged_in() ) {
			return;
		}
		$procedure_id = absint( wp_unslash( $_POST[ self::FIELD_ID ] ?? 0 ) );
		$nonce        = sanitize_text_field( wp_unslash( (string) ( $_POST[ self::NONCE_NAME ] ?? '' ) ) );
		$post         = $_POST;

		if ( false === wp_verify_nonce( $nonce, self::nonce_action( $procedure_id ) ) ) {
			return;
		}
		$cuerpo = self::csv( get_current_user_id(), $procedure_id, $post );
		if ( null === $cuerpo ) {
			self::$rejected = 'Este procedimiento no permite exportar su consulta.';
			return;
		}
		$nombre = ApplicationExport::filename( (string) get_post_field( 'post_title', $procedure_id ), ApplicationExport::FORMAT_CSV );
		Shell::send_header( 'Content-Type: text/csv; charset=utf-8' );
		Shell::send_header( 'Content-Disposition: attachment; filename="' . $nombre . '"' );
		Shell::send_header( 'Content-Length: ' . strlen( $cuerpo ) );
		Shell::send_header( 'Cache-Control: no-store, no-cache, must-revalidate' );
		echo $cuerpo;
		Shell::leave();
	}










	private static function may_export( int $procedure_id ): bool {
		return (bool) get_post_meta( $procedure_id, ProcedureMetaKeys::OBSERVER_EXPORT, true );
	}









	public static function csv( int $user_id, int $procedure_id, array $source ): ?string {
		$ambito = null;
		if ( ! ProcedureAccess::can_observe( $user_id, $procedure_id, $ambito ) || ! self::may_export( $procedure_id ) ) {
			return null;
		}
		$columnas = ObserveSettings::columns( $procedure_id );

		if ( array() === $columnas ) {
			return null;
		}
		$ids = ApplicationQuery::ids( $procedure_id, self::filter( $source, $columnas ), $ambito );
		return ApplicationExport::csv( $procedure_id, array_keys( $columnas ), ApplicationExport::SHAPE_APPLICATION, $ids );
	}







	public static function render( $atts = array() ): string {
		unset( $atts );
		Assets::enqueue();

		return ObserveView::html( self::model( $_GET ) );
	}
}

}








namespace Prc\Notifications;






























if ( ! class_exists( 'Prc\Notifications\Messages', false ) ) {
final class Messages {








	public static function render( string $event, array $ctx ): array {
		$partes = self::parts( $event, $ctx );
		if ( null === $partes ) {
			return array(
				'subject' => '',
				'message' => '',
			);
		}
		return array(
			'subject' => self::subject( $partes[0] ),
			'message' => self::join( $partes[1] ),
		);
	}








	private static function parts( string $event, array $ctx ): ?array {
		$titulo = self::text( $ctx, 'procedure' );
		$quien  = '' !== self::text( $ctx, 'centre' ) ? self::text( $ctx, 'centre' ) : self::text( $ctx, 'applicant' );

		switch ( $event ) {
			case Mailer::EVENT_APPLICATION_SUBMITTED:
				return self::submitted( $titulo, $ctx );

			case Mailer::EVENT_APPLICATION_UPDATED:
				return array(
					'Solicitud modificada: ' . $titulo,
					array_merge(
						array( 'Se han guardado cambios en la solicitud al procedimiento «' . $titulo . '». Así queda.', '' ),
						self::summary( $ctx ),
						self::link( 'Puede consultarla en:', $ctx, 'url' ),
						array( '', 'Este correo es un aviso automático: no hace falta responderlo.' )
					),
				);

			case Mailer::EVENT_APPLICATION_RECEIVED:
				$nueva = ! empty( $ctx['created'] );
				return array(
					( $nueva ? 'Nueva solicitud' : 'Solicitud modificada' ) . ' — ' . $titulo . ( '' !== $quien ? ' — ' . $quien : '' ),
					array_merge(
						array(
							$nueva
								? 'Ha llegado una solicitud nueva al procedimiento «' . $titulo . '».'
								: 'Se ha modificado una solicitud del procedimiento «' . $titulo . '». Vuelve a estar «presentada».',
							'',
						),
						self::summary( $ctx ),
						self::link( 'Se revisa en el taller del procedimiento:', $ctx, 'url' )
					),
				);

			case Mailer::EVENT_REVIEW_CHANGED:
				$nota = self::text( $ctx, 'note' );
				return array(
					'Revisión de su solicitud: ' . $titulo,
					array_merge(
						array( 'La solicitud al procedimiento «' . $titulo . '» ha pasado a «' . self::text( $ctx, 'state' ) . '».' ),
						'' !== $quien ? array( 'Solicitud de: ' . $quien ) : array(),
						'' !== $nota ? array( '', 'Nota de quien revisa:', $nota ) : array(),
						self::link( 'Puede consultarla en:', $ctx, 'url' ),
						array( '', 'Este correo es un aviso automático: no hace falta responderlo.' )
					),
				);

			case Mailer::EVENT_DELIVERY_RECEIVED:
				return array(
					'Documento entregado: ' . self::text( $ctx, 'document' ) . ' — ' . $titulo,
					array_merge(
						array( 'Se ha entregado un documento del procedimiento «' . $titulo . '».', '' ),
						self::field( 'Centro', self::text( $ctx, 'centre' ) ),
						self::field( 'Presentada por', self::text( $ctx, 'applicant' ) ),
						self::field( 'Documento', self::text( $ctx, 'document' ) ),
						self::field( 'Fichero', self::text( $ctx, 'file' ) ),
						self::field( 'Fecha', self::text( $ctx, 'date' ) ),
						self::link( 'Puede consultarla en:', $ctx, 'url' )
					),
				);

			case Mailer::EVENT_PROCEDURE_CREATED:
				return array( 'Procedimiento creado en borrador: ' . $titulo, self::created( $titulo, $ctx ) );

			case Mailer::EVENT_PEOPLE_CHANGED:
				return self::people( $titulo, $quien, $ctx );

			case Mailer::EVENT_APPLICATION_WITHDRAWN:
				return self::withdrawn( $titulo, $quien, $ctx );

			case Mailer::EVENT_APPLICATION_EXTENDED:
				return array(
					'Prórroga de su solicitud: ' . $titulo,
					array_merge(
						array( 'Se ha concedido una prórroga a la solicitud al procedimiento «' . $titulo . '»: puede completarla o modificarla hasta el ' . self::text( $ctx, 'until' ) . ', incluido, aunque el plazo general esté cerrado.' ),
						'' !== $quien ? array( 'Solicitud de: ' . $quien ) : array(),
						self::link( 'Puede hacerlo en:', $ctx, 'url' ),
						array( '', 'Este correo es un aviso automático: no hace falta responderlo.' )
					),
				);
		}
		return null;
	}













	private static function submitted( string $titulo, array $ctx ): array {
		$numero = self::text( $ctx, 'number' );
		if ( '' === $numero ) {
			$cabeza = array( 'Se ha presentado la solicitud al procedimiento «' . $titulo . '». Este es el resumen de lo presentado.' );
		} else {
			$quien  = self::text( $ctx, 'applicant' );
			$centro = self::text( $ctx, 'centre' );
			$falta  = self::lines( $ctx, 'missing' );
			$cabeza = array(
				'Se ha presentado la solicitud n.º ' . $numero . ' al procedimiento «' . $titulo . '» el ' . self::text( $ctx, 'submitted' )
					. ( '' !== $quien ? ', por ' . $quien : '' ) . ( '' !== $centro ? ', para ' . $centro : '' ) . '. Guarde este correo: es su justificante.',
				'',
				array() === $falta ? 'Estado: completa.' : 'Estado: presentada, pero incompleta: ' . lcfirst( implode( '; ', $falta ) ) . '.',
			);
			$hasta  = self::text( $ctx, 'people_until' );
			$porque = self::text( $ctx, 'people_why' );
			if ( '' !== $porque ) {
				$cabeza[] = $porque;
			} elseif ( '' !== $hasta ) {
				$cabeza = array_merge( $cabeza, self::link( 'Puede dar de alta, cambiar o sustituir a las personas que participan hasta el ' . $hasta . ', incluido, en:', $ctx, 'people_url' ) );
			}
		}
		return array(
			'Solicitud presentada' . ( '' !== $numero ? ' n.º ' . $numero : '' ) . ': ' . $titulo,
			array_merge(
				$cabeza,
				array( '' ),
				self::summary( $ctx ),
				self::link( 'Puede consultarla, y modificarla mientras el plazo siga abierto, en:', $ctx, 'url' ),
				self::link( 'Justificante para imprimir o guardar en PDF:', $ctx, 'receipt_url' ),
				array( '', 'Este correo es un acuse automático: no hace falta responderlo.' )
			),
		);
	}








	private static function created( string $titulo, array $ctx ): array {
		$lineas = array(
			'Se ha creado el procedimiento «' . $titulo . '». Está en borrador: no se ve fuera ni admite solicitudes hasta que se publique.',
		);
		$falta  = self::lines( $ctx, 'missing' );
		if ( array() !== $falta ) {
			$lineas[] = '';
			$lineas[] = 'Qué falta por completar:';
			foreach ( $falta as $cosa ) {
				$lineas[] = '- ' . $cosa;
			}
		}
		$lineas = array_merge(
			$lineas,
			array(
				'',
				'Los pasos:',
				'1. Complete los datos del procedimiento: la descripción, los plazos, las preguntas y los correos de contacto.',
				'2. El día de la publicación, ponga las fechas definitivas, enlace la resolución y publíquelo.',
			),
			self::link( 'Taller del procedimiento:', $ctx, 'editor_url' ),
			self::link( 'Gestión de procedimientos:', $ctx, 'workspace_url' )
		);
		$extra  = trim( self::text( $ctx, 'extra' ) );
		if ( '' !== $extra ) {
			$lineas[] = '';
			$lineas[] = $extra;
		}
		return $lineas;
	}









	private static function people( string $titulo, string $quien, array $ctx ): ?array {
		$cambios = array(
			'added'   => array( 'alta', 'Se ha dado de alta a una persona en' ),
			'changed' => array( 'cambio', 'Se ha cambiado una persona de' ),
			'removed' => array( 'baja', 'Se ha dado de baja a una persona de' ),
		);
		$cambio  = $cambios[ self::text( $ctx, 'change' ) ] ?? null;
		if ( null === $cambio ) {
			return null;
		}
		return array(
			'Personas de la solicitud: ' . $cambio[0] . ' — ' . $titulo . ( '' !== $quien ? ' — ' . $quien : '' ),
			array_merge(
				array( $cambio[1] . ' la lista de personas de una solicitud del procedimiento «' . $titulo . '».', '' ),
				self::field( 'Procedimiento', $titulo ),
				self::field( 'Centro', self::text( $ctx, 'centre' ) ),
				self::field( 'Hecho por', self::text( $ctx, 'applicant' ) ),
				self::field( 'Fecha', self::text( $ctx, 'date' ) ),
				array( '' ),
				self::field( 'Antes', self::text( $ctx, 'before' ) ),
				self::field( 'Después', self::text( $ctx, 'after' ) ),
				self::link( '' !== self::text( $ctx, 'url_label' ) ? self::text( $ctx, 'url_label' ) : 'Se consulta en el taller del procedimiento:', $ctx, 'url' )
			),
		);
	}









	private static function withdrawn( string $titulo, string $quien, array $ctx ): array {
		$datos = array_merge(
			self::field( 'Procedimiento', $titulo ),
			self::field( 'Centro', self::text( $ctx, 'centre' ) ),
			self::field( 'Retirada por', self::text( $ctx, 'applicant' ) ),
			self::field( 'Fecha', self::text( $ctx, 'date' ) )
		);
		if ( ! empty( $ctx['ack'] ) ) {
			return array(
				'Solicitud retirada: ' . $titulo,
				array_merge(
					array( 'Se ha retirado la solicitud al procedimiento «' . $titulo . '». Ya no cuenta.', '' ),
					$datos,
					self::link( 'Mientras el plazo siga abierto, puede presentar una nueva en:', $ctx, 'url' ),
					array( '', 'Este correo es un acuse automático: no hace falta responderlo.' )
				),
			);
		}
		return array(
			'Solicitud retirada — ' . $titulo . ( '' !== $quien ? ' — ' . $quien : '' ),
			array_merge(
				array( 'Se ha retirado una solicitud del procedimiento «' . $titulo . '». Está en «Retiradas» del panel de solicitudes, y se puede restaurar mientras el centro no presente otra.', '' ),
				$datos,
				self::link( 'Se consulta en el taller del procedimiento:', $ctx, 'url' )
			),
		);
	}







	private static function summary( array $ctx ): array {
		$lineas     = array_merge(
			self::field( 'Procedimiento', self::text( $ctx, 'procedure' ) ),
			self::field( 'Centro', self::text( $ctx, 'centre' ) ),
			self::field( 'Presentada por', self::text( $ctx, 'applicant' ) ),
			self::field( 'Cargo', self::text( $ctx, 'position' ) ),
			self::field( 'Coordinación', self::text( $ctx, 'coordinator' ) ),
			self::field( 'Fecha', self::text( $ctx, 'date' ) )
		);
		$respuestas = isset( $ctx['answers'] ) && is_array( $ctx['answers'] ) ? $ctx['answers'] : array();
		if ( array() !== $respuestas ) {
			$lineas[] = '';
			$lineas[] = 'Respuestas:';
			foreach ( $respuestas as $respuesta ) {
				$texto    = trim( (string) ( $respuesta['text'] ?? '' ) );
				$lineas[] = '- ' . self::one_line( (string) ( $respuesta['label'] ?? '' ) ) . ': ' . ( '' !== $texto ? $texto : '(sin respuesta)' );
			}
		}
		return $lineas;
	}








	private static function field( string $rotulo, string $valor ): array {
		return '' === $valor ? array() : array( $rotulo . ': ' . $valor );
	}









	private static function link( string $frase, array $ctx, string $clave ): array {
		$url = self::text( $ctx, $clave );
		return '' === $url ? array() : array( '', $frase, $url );
	}







	private static function subject( string $asunto ): string {





		$prefijo = trim( (string) apply_filters( 'prc_mail_subject_prefix', '[Procedimientos]' ) );
		return self::one_line( ( '' !== $prefijo ? $prefijo . ' ' : '' ) . $asunto );
	}







	private static function one_line( string $texto ): string {
		return trim( (string) preg_replace( '/\s+/u', ' ', $texto ) );
	}








	private static function text( array $ctx, string $clave ): string {
		return isset( $ctx[ $clave ] ) && is_scalar( $ctx[ $clave ] ) ? trim( (string) $ctx[ $clave ] ) : '';
	}








	private static function lines( array $ctx, string $clave ): array {
		$lista = isset( $ctx[ $clave ] ) && is_array( $ctx[ $clave ] ) ? $ctx[ $clave ] : array();
		return array_values( array_filter( array_map( 'strval', array_filter( $lista, 'is_scalar' ) ), 'strlen' ) );
	}







	private static function join( array $lineas ): string {
		return implode( "\n", $lineas ) . "\n";
	}
}

}








namespace Prc\Notifications;

use Prc\Access\CentreScope;
use Prc\Access\ObserverScope;
use Prc\Access\ProcedureAccess;
use Prc\Domain\ApplicationPeople;
use Prc\Domain\PeopleSpec;
use Prc\Domain\ProcedureInput;
use Prc\Domain\ProcedureQuestions;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ApplicationMetaRegistration;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ApplicationPostType;
use Prc\PostType\ProcedurePostType;
use Prc\PublicFront\ApplicationFiles;
use Prc\PublicFront\ApplicationOperations;
use Prc\PublicFront\ApplicationProgress;
use Prc\PublicFront\Applications;
use Prc\PublicFront\ApplyForm;
use Prc\PublicFront\Observe;
use Prc\PublicFront\ObserveSettings;
use Prc\PublicFront\People;
use Prc\PublicFront\PeopleForm;
use Prc\PublicFront\PeopleExport;
use Prc\PublicFront\ProcedureEditor;
use Prc\PublicFront\Shell;
use Prc\Taxonomy\ProcedureTaxonomies;




















if ( ! class_exists( 'Prc\Notifications\Mailer', false ) ) {
final class Mailer {

	public const EVENT_APPLICATION_SUBMITTED = 'application_submitted';
	public const EVENT_APPLICATION_UPDATED   = 'application_updated';
	public const EVENT_APPLICATION_RECEIVED  = 'application_received';
	public const EVENT_REVIEW_CHANGED        = 'review_changed';
	public const EVENT_DELIVERY_RECEIVED     = 'delivery_received';
	public const EVENT_PROCEDURE_CREATED     = 'procedure_created';
	public const EVENT_PEOPLE_CHANGED        = 'people_changed';
	public const EVENT_APPLICATION_WITHDRAWN = 'application_withdrawn';
	public const EVENT_APPLICATION_EXTENDED  = 'application_extended';




	public const OPTION_FROM_NAME = 'prc_mail_from_name';




	public const OPTION_FROM = 'prc_mail_from';




	public const OPTION_REPLY_TO = 'prc_mail_reply_to';




	public const OPTION_COPY = 'prc_mail_copy';




	public const OPTION_CREATED_EXTRA = 'prc_mail_created_extra';






	public static function register(): void {
		add_action( 'prc_procedure_created', array( self::class, 'on_procedure_created' ) );
		add_action( 'prc_application_saved', array( self::class, 'on_application_saved' ), 10, 3 );
		add_action( 'prc_application_reviewed', array( self::class, 'on_application_reviewed' ), 10, 4 );
		add_action( 'prc_delivery_saved', array( self::class, 'on_delivery_saved' ), 10, 3 );
		add_action( 'prc_people_changed', array( self::class, 'on_people_changed' ), 10, 5 );
		add_action( 'prc_application_withdrawn', array( self::class, 'on_application_withdrawn' ), 10, 4 );
		add_action( 'prc_application_extended', array( self::class, 'on_application_extended' ), 10, 4 );
	}






	public static function events(): array {
		return array(
			self::EVENT_APPLICATION_SUBMITTED,
			self::EVENT_APPLICATION_UPDATED,
			self::EVENT_APPLICATION_RECEIVED,
			self::EVENT_REVIEW_CHANGED,
			self::EVENT_DELIVERY_RECEIVED,
			self::EVENT_PROCEDURE_CREATED,
			self::EVENT_PEOPLE_CHANGED,
			self::EVENT_APPLICATION_WITHDRAWN,
			self::EVENT_APPLICATION_EXTENDED,
		);
	}






	public static function options(): array {
		return array(
			self::OPTION_FROM_NAME,
			self::OPTION_FROM,
			self::OPTION_REPLY_TO,
			self::OPTION_COPY,
			self::OPTION_CREATED_EXTRA,
		);
	}

















	public static function mail( string $event, string $to, string $subject, string $message, array $ctx = array() ): bool {
		$to = self::sanitize_address( $to );
		if ( '' === $to || '' === trim( $message ) ) {
			return false;
		}
		$procedure_id = isset( $ctx['procedure_id'] ) ? (int) $ctx['procedure_id'] : 0;
		if ( $procedure_id > 0 && ! self::enabled( $procedure_id, $event ) ) {
			return false;
		}

		try {







			$correo = apply_filters(
				'prc_mail',
				array(
					'to'      => $to,
					'subject' => $subject,
					'message' => $message,
					'headers' => self::headers(),
				),
				$event,
				$ctx
			);
			if ( ! is_array( $correo ) ) {
				return false;
			}
			return (bool) wp_mail(
				(string) ( $correo['to'] ?? '' ),
				(string) ( $correo['subject'] ?? '' ),
				(string) ( $correo['message'] ?? '' ),
				(array) ( $correo['headers'] ?? array() )
			);
		} catch ( \Throwable $e ) {


			unset( $e );
			return false;
		}
	}









	public static function send( string $event, array $addresses, array $ctx ): int {
		$correo = Messages::render( $event, $ctx );
		if ( '' === $correo['message'] ) {
			return 0;
		}
		$hechos = 0;
		foreach ( self::unique( $addresses ) as $to ) {
			if ( self::mail( $event, $to, $correo['subject'], $correo['message'], $ctx ) ) {
				++$hechos;
			}
		}
		return $hechos;
	}








	public static function enabled( int $procedure_id, string $event ): bool {
		$apagados = get_post_meta( $procedure_id, ProcedureMetaKeys::NOTIFY, true );
		return ! is_array( $apagados ) || ! in_array( $event, $apagados, true );
	}









	public static function sanitize_address( $value ): string {
		$email = is_scalar( $value ) ? strtolower( trim( (string) $value ) ) : '';
		return is_email( $email ) ? $email : '';
	}






	private static function headers(): array {
		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
		$from    = self::sanitize_address( get_option( self::OPTION_FROM, '' ) );
		if ( '' !== $from ) {


			$nombre    = trim( (string) preg_replace( '/[\r\n"<>]+/', ' ', (string) get_option( self::OPTION_FROM_NAME, '' ) ) );
			$headers[] = 'From: ' . ( '' !== $nombre ? $nombre . ' <' . $from . '>' : $from );
		}
		$reply = self::sanitize_address( get_option( self::OPTION_REPLY_TO, '' ) );
		if ( '' !== $reply ) {
			$headers[] = 'Reply-To: ' . $reply;
		}
		return $headers;
	}







	private static function unique( array $addresses ): array {
		$out = array();
		foreach ( $addresses as $address ) {
			$email = self::sanitize_address( $address );
			if ( '' !== $email ) {
				$out[ $email ] = $email;
			}
		}
		return array_values( $out );
	}











	public static function on_procedure_created( $procedure_id ): void {
		self::safely(
			static function () use ( $procedure_id ): void {
				$procedure_id = (int) $procedure_id;
				if ( ProcedurePostType::POST_TYPE !== get_post_type( $procedure_id ) ) {
					return;
				}
				$destinos = self::contacts( $procedure_id );
				foreach ( ProcedureAccess::area_managers( $procedure_id ) as $user_id ) {
					$destinos[] = self::email_of( $user_id );
				}
				$destinos[] = (string) get_option( self::OPTION_COPY, '' );

				self::send(
					self::EVENT_PROCEDURE_CREATED,
					$destinos,
					array(
						'procedure_id'  => $procedure_id,
						'procedure'     => self::title( $procedure_id ),
						'missing'       => self::missing( $procedure_id ),
						'editor_url'    => ProcedureEditor::url( $procedure_id ),
						'workspace_url' => Shell::url( 'workspace' ),
						'extra'         => sanitize_textarea_field( (string) get_option( self::OPTION_CREATED_EXTRA, '' ) ),
					)
				);
			}
		);
	}













	public static function on_application_saved( $application_id, $created = false, $user_id = 0 ): void {
		self::safely(
			static function () use ( $application_id, $created, $user_id ): void {
				$ctx = self::application_context( (int) $application_id, (int) $user_id );
				if ( array() === $ctx ) {
					return;
				}
				$ctx['created'] = (bool) $created;
				if ( $created ) {
					$ctx = self::receipt( (int) $application_id, $ctx, (int) $user_id );
				}

				self::send(
					$created ? self::EVENT_APPLICATION_SUBMITTED : self::EVENT_APPLICATION_UPDATED,
					array( self::email_of( (int) $user_id ) ),
					$ctx
				);

				$destinos   = self::contacts( (int) $ctx['procedure_id'] );
				$destinos[] = (string) get_option( self::OPTION_COPY, '' );
				$ctx['url'] = ProcedureEditor::url( (int) $ctx['procedure_id'], ProcedureEditor::PANEL_APPLICATIONS );
				self::send( self::EVENT_APPLICATION_RECEIVED, $destinos, $ctx );
			}
		);
	}
















	public static function on_application_reviewed( $application_id, $state = '', $previous = '', $previous_note = '' ): void {
		$nota = (string) get_post_meta( (int) $application_id, ApplicationMetaKeys::REVIEW_NOTE, true );
		if ( ! self::review_news( (string) $state, (string) $previous, $nota, (string) $previous_note ) ) {
			return;
		}
		$autor = (int) get_post_field( 'post_author', (int) $application_id );


		if ( ! ProcedureAccess::can_view_application( $autor, (int) $application_id, ProcedureAccess::VIEW_OWN ) ) {
			return;
		}
		self::safely(
			static function () use ( $application_id, $state, $autor ): void {
				$ctx = self::application_context( (int) $application_id, $autor );
				if ( array() === $ctx ) {
					return;
				}
				$ctx['state'] = (string) ( ApplicationMetaKeys::review_states()[ $state ] ?? '' );
				$ctx['note']  = in_array( $state, ApplicationMetaKeys::states_needing_note(), true )
					? (string) get_post_meta( (int) $application_id, ApplicationMetaKeys::REVIEW_NOTE, true )
					: '';
				self::send( self::EVENT_REVIEW_CHANGED, array( self::email_of( $autor ) ), $ctx );
			}
		);
	}














	public static function review_news( string $state, string $previous, string $note, string $previous_note ): bool {
		if ( '' === $previous ) {
			$previous = ApplicationMetaKeys::REVIEW_SUBMITTED;
		}
		if ( $state !== $previous ) {
			return true;
		}
		return in_array( $state, ApplicationMetaKeys::states_needing_note(), true ) && trim( $note ) !== trim( $previous_note );
	}












	public static function on_delivery_saved( $application_id, $question_key = '', $user_id = 0 ): void {
		self::safely(
			static function () use ( $application_id, $question_key, $user_id ): void {
				$ctx = self::application_context( (int) $application_id, (int) $user_id );
				if ( array() === $ctx ) {
					return;
				}
				foreach ( Applications::questions( (int) $ctx['procedure_id'] ) as $pregunta ) {
					if ( (string) $question_key === (string) $pregunta['key'] ) {
						$ctx['document'] = (string) $pregunta['label'];
					}
				}
				$ctx['file'] = (string) ( ApplicationFiles::descriptors( (int) $application_id )[ (string) $question_key ]['name'] ?? '' );
				$ctx['date'] = (string) wp_date( 'd-m-Y H:i' );
				unset( $ctx['answers'] );

				$destinos   = self::contacts( (int) $ctx['procedure_id'] );
				$destinos[] = self::email_of( (int) $user_id );
				self::send( self::EVENT_DELIVERY_RECEIVED, $destinos, $ctx );
			}
		);
	}

















	public static function on_people_changed( $application_id, $change = '', $before = array(), $after = array(), $user_id = 0 ): void {
		self::safely(
			static function () use ( $application_id, $change, $before, $after, $user_id ): void {
				$ctx = self::application_context( (int) $application_id, (int) $user_id );
				if ( array() === $ctx ) {
					return;
				}
				$spec          = People::spec( (int) $ctx['procedure_id'] );
				$ctx['change'] = (string) $change;
				$ctx['before'] = array() !== (array) $before ? ApplicationPeople::describe( $spec, (array) $before ) : '';
				$ctx['after']  = array() !== (array) $after ? ApplicationPeople::describe( $spec, (array) $after ) : '';
				$ctx['date']   = (string) wp_date( 'd-m-Y H:i' );
				$ctx['url']    = ProcedureEditor::url( (int) $ctx['procedure_id'], ProcedureEditor::PANEL_APPLICATIONS );
				unset( $ctx['answers'] );
				self::send( self::EVENT_PEOPLE_CHANGED, self::contacts( (int) $ctx['procedure_id'] ), $ctx );
				self::send_to_zone( (int) $application_id, $ctx, (array) $before, (array) $after );
			}
		);
	}
















	public static function on_application_withdrawn( $application_id, $user_id = 0, $by = '', $reason = '' ): void {
		unset( $reason );
		if ( ApplicationOperations::BY_APPLICANT !== (string) $by ) {
			return;
		}
		self::safely(
			static function () use ( $application_id, $user_id ): void {
				$ctx = self::application_context( (int) $application_id, (int) $user_id );
				if ( array() === $ctx ) {
					return;
				}
				unset( $ctx['answers'] );
				$ctx['date'] = (string) wp_date( 'd-m-Y H:i' );
				$propia      = (string) $ctx['url'];
				$ctx['url']  = ProcedureEditor::url( (int) $ctx['procedure_id'], ProcedureEditor::PANEL_APPLICATIONS );
				self::send( self::EVENT_APPLICATION_WITHDRAWN, self::contacts( (int) $ctx['procedure_id'] ), $ctx );
				$ctx['ack'] = true;
				$ctx['url'] = $propia;
				self::send( self::EVENT_APPLICATION_WITHDRAWN, array( self::email_of( (int) $user_id ) ), $ctx );
			}
		);
	}















	public static function on_application_extended( $application_id, $until = '', $previous = '', $user_id = 0 ): void {
		unset( $previous, $user_id );
		$autor = (int) get_post_field( 'post_author', (int) $application_id );
		if ( '' === (string) $until || ! ProcedureAccess::can_view_application( $autor, (int) $application_id, ProcedureAccess::VIEW_OWN ) ) {
			return;
		}
		self::safely(
			static function () use ( $application_id, $until, $autor ): void {
				$ctx = self::application_context( (int) $application_id, $autor );
				if ( array() === $ctx ) {
					return;
				}
				unset( $ctx['answers'] );
				$ctx['until'] = implode( '-', array_reverse( explode( '-', (string) $until ) ) );
				self::send( self::EVENT_APPLICATION_EXTENDED, array( self::email_of( $autor ) ), $ctx );
			}
		);
	}


















	private static function send_to_zone( int $application_id, array $ctx, array $before, array $after ): void {
		$procedure_id = (int) $ctx['procedure_id'];
		if ( ! ObserverScope::is_observable( $procedure_id ) || ! get_post_meta( $procedure_id, ProcedureMetaKeys::OBSERVER_NOTIFY, true ) ) {
			return;
		}
		$codigo   = (string) get_post_meta( $application_id, ApplicationMetaKeys::CENTRE_CODE, true );
		$destinos = array_filter( array_map( array( self::class, 'email_of' ), ObserverScope::observers_of( $codigo ) ) );
		if ( array() === $destinos ) {
			return;
		}
		$columnas = ObserveSettings::columns( $procedure_id );
		$campos   = array();
		foreach ( array_keys( array_intersect_key( $columnas, PeopleExport::COLUMNS ) ) as $columna ) {
			$campos[] = substr( (string) $columna, strlen( 'person_' ) );
		}
		$spec = People::spec( $procedure_id );


		$zona = array(
			'procedure'    => $ctx['procedure'] ?? '',
			'procedure_id' => $procedure_id,
			'centre'       => $ctx['centre'] ?? '',
			'change'       => $ctx['change'] ?? '',
			'date'         => $ctx['date'] ?? '',
			'before'       => array() !== $before && array() !== $campos ? ApplicationPeople::describe( $spec, $before, $campos ) : '',
			'after'        => array() !== $after && array() !== $campos ? ApplicationPeople::describe( $spec, $after, $campos ) : '',
			'url'          => Observe::url( $procedure_id ),
			'url_label'    => 'Se consulta en la consulta de solicitudes:',
		);
		if ( isset( $columnas['applicant'] ) ) {
			$zona['applicant'] = $ctx['applicant'] ?? '';
		}
		self::send( self::EVENT_PEOPLE_CHANGED, $destinos, $zona );
	}



















	private static function application_context( int $application_id, int $user_id ): array {
		if ( ApplicationPostType::POST_TYPE !== get_post_type( $application_id ) ) {
			return array();
		}
		$procedure_id = (int) get_post_field( 'post_parent', $application_id );
		$meta         = Applications::meta( $application_id );
		$autor        = $user_id > 0 ? get_userdata( $user_id ) : false;
		$docente      = ApplicationMetaKeys::TYPE_TEACHER === ApplicationMetaRegistration::sanitize_applicant_type( $meta[ ApplicationMetaKeys::APPLICANT_TYPE ] );
		$codigo       = (string) $meta[ ApplicationMetaKeys::CENTRE_CODE ];
		$nombre       = (string) $meta[ ApplicationMetaKeys::CENTRE_NAME ];
		$coordinacion = ApplicationMetaRegistration::sanitize_coordinator( $meta[ ApplicationMetaKeys::COORDINATOR ] );

		$args = array();
		if ( '' !== $codigo && false !== $autor && CentreScope::code_for( (int) $autor->ID ) !== $codigo ) {
			$args[ ApplyForm::ARG_CENTRE ] = $codigo;
		}

		return array(
			'procedure_id' => $procedure_id,
			'procedure'    => self::title( $procedure_id ),
			'centre'       => $docente || '' === $codigo ? '' : ( '' !== $nombre ? $nombre . ' (' . $codigo . ')' : $codigo ),
			'applicant'    => false !== $autor ? (string) $autor->display_name : '',
			'position'     => (string) ( ApplicationMetaKeys::positions()[ $meta[ ApplicationMetaKeys::APPLICANT_POSITION ] ] ?? '' ),
			'coordinator'  => ApplicationMetaRegistration::full_names( $coordinacion ),
			'date'         => (string) wp_date( 'd-m-Y H:i', (int) get_post_timestamp( $application_id, 'modified' ) ),
			'answers'      => self::answers( $procedure_id, $application_id, (array) $meta[ ApplicationMetaKeys::ANSWERS ] ),
			'url'          => ApplyForm::url( $procedure_id, $args ),
		);
	}
















	private static function receipt( int $application_id, array $ctx, int $user_id ): array {
		$procedure_id = (int) $ctx['procedure_id'];
		$lista        = People::enabled( $procedure_id )
			&& ApplicationMetaKeys::TYPE_TEACHER !== get_post_meta( $application_id, ApplicationMetaKeys::APPLICANT_TYPE, true );
		$porque       = $lista ? ProcedureAccess::why_not_people( $user_id, $application_id ) : '';
		$hasta        = $lista && '' === $porque
			? PeopleSpec::until( People::spec( $procedure_id ), (string) get_post_meta( $procedure_id, ProcedureMetaKeys::CLOSES_AT, true ), ProcedureState::today() )
			: '';
		return array(
			'number'       => (string) $application_id,
			'submitted'    => ApplicationProgress::when( (int) get_post_timestamp( $application_id ) ),
			'missing'      => ApplicationProgress::missing( $application_id ),
			'people_until' => '' !== $hasta ? implode( '-', array_reverse( explode( '-', $hasta ) ) ) : '',
			'people_url'   => (string) $ctx['url'] . '#' . PeopleForm::ANCHOR,
			'people_why'   => $porque,
			'receipt_url'  => add_query_arg( ApplicationProgress::ARG_RECEIPT, '1', (string) $ctx['url'] ),
		) + $ctx;
	}









	private static function answers( int $procedure_id, int $application_id, array $respuestas ): array {
		$documentos = ApplicationFiles::descriptors( $application_id );
		$out        = array();
		foreach ( ProcedureQuestions::application_questions( Applications::questions( $procedure_id ) ) as $pregunta ) {
			$key   = (string) $pregunta['key'];
			$out[] = array(
				'label' => (string) $pregunta['label'],
				'text'  => ProcedureQuestions::TYPE_FILE === (string) ( $pregunta['type'] ?? '' )
					? (string) ( $documentos[ $key ]['name'] ?? '' )
					: ProcedureQuestions::as_text( $pregunta, $respuestas[ $key ] ?? null ),
			);
		}
		return $out;
	}







	private static function missing( int $procedure_id ): array {
		$falta = array();
		if ( '' === trim( (string) get_post_field( 'post_content', $procedure_id ) ) ) {
			$falta[] = 'Descripción';
		}
		if ( '' === (string) get_post_meta( $procedure_id, ProcedureMetaKeys::OPENS_AT, true ) || '' === (string) get_post_meta( $procedure_id, ProcedureMetaKeys::CLOSES_AT, true ) ) {
			$falta[] = 'Plazo de solicitud: fechas de apertura y cierre';
		}
		if ( array() === Applications::questions( $procedure_id ) ) {
			$falta[] = 'Preguntas de la solicitud, si las necesita';
		}
		if ( '' === (string) get_post_meta( $procedure_id, ProcedureMetaKeys::RESOLUTION_URL, true ) ) {
			$falta[] = 'Enlace a la resolución';
		}



		$curso = wp_get_post_terms( $procedure_id, ProcedureTaxonomies::COURSE, array( 'fields' => 'ids' ) );
		return array_merge( $falta, ProcedureInput::not_ready( is_array( $curso ) ? (int) ( $curso[0] ?? 0 ) : 0, Applications::questions( $procedure_id ), ProcedureState::today() ) );
	}







	private static function contacts( int $procedure_id ): array {
		$correos = get_post_meta( $procedure_id, ProcedureMetaKeys::CONTACT_EMAILS, true );
		return is_array( $correos ) ? array_map( 'strval', $correos ) : array();
	}







	private static function email_of( int $user_id ): string {
		$user = $user_id > 0 ? get_userdata( $user_id ) : false;
		return false !== $user ? (string) $user->user_email : '';
	}







	private static function title( int $post_id ): string {
		return html_entity_decode( wp_strip_all_tags( (string) get_post_field( 'post_title', $post_id ) ), ENT_QUOTES, 'UTF-8' );
	}







	private static function safely( callable $listener ): void {
		try {
			$listener();
		} catch ( \Throwable $e ) {

			unset( $e );
		}
	}
}

}








namespace Prc\Admin;

use Prc\Access\ProcedureAccess;
use Prc\Domain\ProcedureState;
use Prc\Meta\ApplicationMetaKeys;
use Prc\Meta\ProcedureMetaKeys;
use Prc\PostType\ApplicationPostType;
use Prc\PostType\ProcedurePostType;
use Prc\Taxonomy\ProcedureTaxonomies;









if ( ! class_exists( 'Prc\Admin\ProcedureAdmin', false ) ) {
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
			echo esc_html(
				ProcedureState::label(
					ProcedureState::of_post( $post_id ),
					(bool) get_post_meta( $post_id, ProcedureMetaKeys::APPLICATIONS_CLOSED, true )
				)
			);
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

}








namespace Prc\Admin;

use Prc\Access\CentreScope;
use Prc\Access\ProcedureAccess;
use Prc\Centre\CentreCatalogue;
use Prc\Centre\CentreCatalogueSync;
use Prc\Domain\CentreCatalog;
use Prc\Domain\CentreEligibility;
use Prc\Meta\ProcedureMetaKeys;
use Prc\Notifications\Mailer;
use Prc\PostType\ApplicationPostType;
use Prc\PostType\ProcedurePostType;
use Prc\PublicFront\ExitSignal;
use Prc\Taxonomy\ProcedureTaxonomies;
use RuntimeException;










if ( ! class_exists( 'Prc\Admin\Settings', false ) ) {
final class Settings {




	public const PAGE = 'prc-settings';




	public const NONCE_SYNC_CENTRES = 'prc_centres_manual_sync';




	public const MAIL_GROUP = 'prc_mail';




	public const CENTRES_GROUP = 'prc_centres';






	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'menu' ) );
		add_action( 'admin_init', array( self::class, 'handle_actions' ) );
		add_action( 'admin_init', array( self::class, 'register_settings' ) );


		add_filter( 'option_page_capability_' . self::MAIL_GROUP, array( self::class, 'capability' ) );
		add_filter( 'option_page_capability_' . self::CENTRES_GROUP, array( self::class, 'capability' ) );
	}






	public static function register_settings(): void {
		$saneado = array(
			Mailer::OPTION_FROM_NAME     => 'sanitize_text_field',
			Mailer::OPTION_FROM          => array( Mailer::class, 'sanitize_address' ),
			Mailer::OPTION_REPLY_TO      => array( Mailer::class, 'sanitize_address' ),
			Mailer::OPTION_COPY          => array( Mailer::class, 'sanitize_address' ),
			Mailer::OPTION_CREATED_EXTRA => 'sanitize_textarea_field',
		);
		foreach ( $saneado as $opcion => $callback ) {
			register_setting(
				self::MAIL_GROUP,
				$opcion,
				array(
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => $callback,
					'show_in_rest'      => false,
				)
			);
		}
		register_setting(
			self::CENTRES_GROUP,
			CentreCatalog::OPTION_PRIVATE_TYPES,
			array(
				'type'              => 'array',
				'default'           => array(),
				'sanitize_callback' => array( self::class, 'sanitize_private_types' ),
				'show_in_rest'      => false,
			)
		);
	}







	public static function sanitize_private_types( $value ): array {
		return CentreEligibility::values( array_map( 'sanitize_text_field', CentreEligibility::values( $value ) ) );
	}






	public static function capability(): string {
		return ProcedureAccess::CAP_MANAGE;
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











	private static function catalogue_dependencies(): void {
		$centros  = count( CentreCatalog::all() );
		$privados = CentreCatalog::private_types();
		$dependen = self::dependents();
		$lineas   = array( sprintf( 'Centros que sirve el aplicativo: %d.', $centros ) );
		if ( 0 === $centros ) {
			$lineas[] = sprintf( '%d procedimientos publicados dependen del catálogo: acotan qué centros solicitan y, con el catálogo vacío, no se pueden comprobar.', $dependen['catalogue'] );
		}
		if ( array() === $privados ) {
			$lineas[] = sprintf( '%d procedimientos publicados dependen de los tipos privados: se dirigen a una sola titularidad y no hay ningún tipo de centro marcado como privado.', $dependen['private'] );
		}
		echo '<p style="margin-top:0.75rem;max-width:46rem">' . implode( '<br />', array_map( 'esc_html', $lineas ) ) . '</p>';
	}







	public static function dependents(): array {
		$out = array(
			'catalogue' => 0,
			'private'   => 0,
		);
		$ids = get_posts(
			array(
				'post_type'      => ProcedurePostType::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		foreach ( $ids as $id ) {
			if ( ProcedureMetaKeys::AUDIENCE_TEACHERS === get_post_meta( (int) $id, ProcedureMetaKeys::AUDIENCE, true ) ) {
				continue;
			}
			$reglas            = ProcedureAccess::eligibility( (int) $id );
			$out['catalogue'] += CentreEligibility::restricted( $reglas ) ? 1 : 0;
			$out['private']   += 1 === count( $reglas['ownership'] ) ? 1 : 0;
		}
		return $out;
	}










	public static function scope_diagnostics(): array {
		$rows = array();
		foreach ( get_users() as $user ) {
			if ( ! ( $user instanceof \WP_User ) || ProcedureAccess::can_edit_all_areas( $user->ID ) ) {
				continue;
			}
			if ( ! user_can( $user, ProcedureAccess::CAP_MANAGE_PROCEDURES ) && ! array_intersect( array( 'editor', 'prc_manager' ), $user->roles ) ) {
				continue;
			}
			$rows[] = array_merge(
				array(
					'user_id' => $user->ID,
					'login'   => $user->user_login,
				),
				ProcedureAccess::scope_assignment_state( $user->ID )
			);
		}
		return $rows;
	}









	private static function private_types_form(): void {
		$tipos = get_option( CentreCatalog::OPTION_PRIVATE_TYPES, array() );
		?>
		<h3>Titularidad de los centros</h3>
		<p style="max-width:46rem">
			El catálogo no dice si un centro es público o privado: se deduce de su tipo. Los
			tipos que escriba aquí, uno por línea y como los trae el catálogo, son privados;
			los demás, públicos. En blanco, todos los centros son públicos.
		</p>
		<form method="post" action="options.php">
			<?php settings_fields( self::CENTRES_GROUP ); ?>
			<label class="screen-reader-text" for="<?php echo esc_attr( CentreCatalog::OPTION_PRIVATE_TYPES ); ?>">Tipos de centro privados</label>
			<textarea class="large-text" rows="4" style="max-width:46rem" id="<?php echo esc_attr( CentreCatalog::OPTION_PRIVATE_TYPES ); ?>" name="<?php echo esc_attr( CentreCatalog::OPTION_PRIVATE_TYPES ); ?>"><?php echo esc_textarea( implode( "\n", is_array( $tipos ) ? array_map( 'strval', $tipos ) : array() ) ); ?></textarea>
			<?php submit_button( 'Guardar los tipos privados' ); ?>
		</form>
		<?php
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
			<?php


			settings_errors();
			?>
			<p class="description" style="max-width:46rem">
				Esta pantalla cuenta lo que el aplicativo tiene montado en este sitio. Si algo
				sale «sin registrar», es que el snippet correspondiente no está activo. Lo único
				que guarda es la configuración del correo y qué tipos de centro son privados.
			</p>

			<h2>Correo</h2>
			<p style="max-width:46rem">
				Vacíos, los correos salen con el remitente de WordPress y sin copia. Cada
				procedimiento decide, en su pestaña «Avisos», cuáles de sus correos se envían.
			</p>
			<form method="post" action="options.php">
				<?php settings_fields( self::MAIL_GROUP ); ?>
				<table class="form-table" role="presentation" style="max-width:46rem">
					<tbody>
						<tr>
							<th scope="row"><label for="<?php echo esc_attr( Mailer::OPTION_FROM_NAME ); ?>">Nombre del remitente</label></th>
							<td><input class="regular-text" type="text" id="<?php echo esc_attr( Mailer::OPTION_FROM_NAME ); ?>" name="<?php echo esc_attr( Mailer::OPTION_FROM_NAME ); ?>" value="<?php echo esc_attr( (string) get_option( Mailer::OPTION_FROM_NAME, '' ) ); ?>" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="<?php echo esc_attr( Mailer::OPTION_FROM ); ?>">Dirección del remitente</label></th>
							<td>
								<input class="regular-text" type="email" id="<?php echo esc_attr( Mailer::OPTION_FROM ); ?>" name="<?php echo esc_attr( Mailer::OPTION_FROM ); ?>" value="<?php echo esc_attr( (string) get_option( Mailer::OPTION_FROM, '' ) ); ?>" />
								<p class="description">Una dirección que el servidor de correo de este sitio pueda usar como remitente.</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="<?php echo esc_attr( Mailer::OPTION_REPLY_TO ); ?>">Responder a</label></th>
							<td><input class="regular-text" type="email" id="<?php echo esc_attr( Mailer::OPTION_REPLY_TO ); ?>" name="<?php echo esc_attr( Mailer::OPTION_REPLY_TO ); ?>" value="<?php echo esc_attr( (string) get_option( Mailer::OPTION_REPLY_TO, '' ) ); ?>" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="<?php echo esc_attr( Mailer::OPTION_COPY ); ?>">Copia</label></th>
							<td>
								<input class="regular-text" type="email" id="<?php echo esc_attr( Mailer::OPTION_COPY ); ?>" name="<?php echo esc_attr( Mailer::OPTION_COPY ); ?>" value="<?php echo esc_attr( (string) get_option( Mailer::OPTION_COPY, '' ) ); ?>" />
								<p class="description">Recibe copia de cada procedimiento creado y de cada solicitud recibida.</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="<?php echo esc_attr( Mailer::OPTION_CREATED_EXTRA ); ?>">Texto del correo de alta</label></th>
							<td>
								<textarea class="large-text" rows="4" id="<?php echo esc_attr( Mailer::OPTION_CREATED_EXTRA ); ?>" name="<?php echo esc_attr( Mailer::OPTION_CREATED_EXTRA ); ?>"><?php echo esc_textarea( (string) get_option( Mailer::OPTION_CREATED_EXTRA, '' ) ); ?></textarea>
								<p class="description">Se añade al final del correo que avisa de un procedimiento recién creado: las instrucciones propias de esta instalación.</p>
							</td>
						</tr>
					</tbody>
				</table>
				<?php submit_button( 'Guardar la configuración del correo' ); ?>
			</form>

			<?php if ( isset( $_GET['updated'] ) && 'synced' === $_GET['updated'] && ! empty( $_GET['msg'] ) ) :?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( sanitize_text_field( wp_unslash( (string) $_GET['msg'] ) ) );?></p></div>
			<?php elseif ( isset( $_GET['error'] ) && ! empty( $_GET['error'] ) ) :?>
				<div class="notice notice-error is-dismissible"><p><?php echo esc_html( sanitize_text_field( wp_unslash( (string) $_GET['error'] ) ) );?></p></div>
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
				<?php foreach ( self::scope_diagnostics() as $row ) : ?>
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

			$is_ready   = array() !== CentreCatalog::all();
			$has_source = '' !== CentreCatalogueSync::manifest_url();
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
			<?php self::catalogue_dependencies(); ?>
			<?php self::private_types_form(); ?>
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

}








namespace Prc;

use Prc\Access\ApplicationActions;
use Prc\Access\ProcedureAccess;
use Prc\Admin\ProcedureAdmin;
use Prc\Admin\Settings;
use Prc\Centre\CentreCatalogueSync;
use Prc\Domain\CentreCatalog;
use Prc\Meta\ApplicationMetaRegistration;
use Prc\Meta\ProcedureMetaRegistration;
use Prc\Notifications\Mailer;
use Prc\PostType\ApplicationPostType;
use Prc\PostType\ProcedurePostType;
use Prc\PublicFront\ApplicationExport;
use Prc\PublicFront\ApplicationHistory;
use Prc\PublicFront\ApplicationOperations;
use Prc\PublicFront\Applications;
use Prc\PublicFront\ApplicationFiles;
use Prc\PublicFront\ApplyForm;
use Prc\PublicFront\Assets;
use Prc\PublicFront\EditLock;
use Prc\PublicFront\Help;
use Prc\PublicFront\Home;
use Prc\PublicFront\MyCentre;
use Prc\PublicFront\Observe;
use Prc\PublicFront\PeopleForm;
use Prc\PublicFront\ProcedureEditor;
use Prc\PublicFront\ProcedureView;
use Prc\PublicFront\Shell;
use Prc\PublicFront\WithdrawForm;
use Prc\PublicFront\Workspace;
use Prc\Taxonomy\ProcedureTaxonomies;









if ( ! class_exists( 'Prc\App', false ) ) {
final class App {








	public const PAGES_PARENT = 'prc_pages_parent';












	private const FRONT = array(
		Assets::class,
		Shell::class,
		EditLock::class,
		Applications::class,



		ApplicationFiles::class,


		ApplicationExport::class,
		ApplyForm::class,
		PeopleForm::class,


		ApplicationOperations::class,
		ApplicationHistory::class,
		WithdrawForm::class,
		MyCentre::class,

		Observe::class,
		Workspace::class,
		ProcedureEditor::class,
		Home::class,

		Help::class,
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
		ApplicationActions::register();
		CentreCatalog::register();



		add_filter( 'prc_page_slug', array( self::class, 'page_slug' ), 10, 2 );



		foreach ( self::FRONT as $modulo ) {
			if ( class_exists( $modulo ) && method_exists( $modulo, 'register' ) ) {
				call_user_func( array( $modulo, 'register' ) );
			}
		}



		Mailer::register();

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

.prc-form.prc-form--taller [data-prc-schools-only][hidden] { display: none !important; }

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

/* «Sí, varias personas: [5]»: el número va en la misma línea que su radio. */
.prc-form.prc-form--taller .prc-opcion--varias {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}

.prc-form.prc-form--taller .prc-opcion--varias .prc-control {
  width: auto;
  min-width: 4.5rem;
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

/* La barra de la tabla de solicitudes: contadores por estado, que son atajos
   de filtro; el filtro, un formulario GET; las páginas, enlaces (PLAN-0003,
   PR 6). Todo funciona sin guion. */
.prc-contadores { display: flex; flex-wrap: wrap; gap: 6px; margin: 0 0 10px; padding: 0; list-style: none; }
.prc-contadores__enlace {
  display: inline-block;
  padding: 2px 10px;
  font-size: var(--prc-t-tabla);
  color: var(--prc-c-tabla);
  background: var(--prc-c-fondo-accion);
  border: 1px solid var(--prc-c-linea);
  border-radius: 999px;
  text-decoration: none;
}
.prc-contadores__enlace[aria-current] { font-weight: 700; border-color: var(--prc-c-azul); }
.prc-contadores__cifra { font-weight: 700; }

.prc-filtros {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  gap: 8px 12px;
  margin: 0 0 12px;
  padding: 10px 12px;
  background: var(--prc-c-fondo-barra);
  border-radius: var(--prc-radio);
}
.prc-filtros__campo { display: flex; flex-direction: column; min-width: 10rem; max-width: 100%; }
.prc-filtros__campo .prc-control { width: auto; }
.prc-filtros__casilla { align-self: center; }
.prc-filtros__acciones { display: flex; align-items: center; gap: 10px; }
.prc-filtros__quitar { font-size: var(--prc-t-menudo); }

.prc-bloque { margin: 0 0 10px; }
.prc-bloque[open] > .prc-bloque__form {
  margin-top: 8px;
  padding: 12px;
  max-width: 40rem;
  border: 1px solid var(--prc-c-linea);
  border-radius: var(--prc-radio);
}
.prc-th--marca,
.prc-celda--marca { width: 1%; text-align: center; }

/* Actuar sobre una solicitud (PLAN-0003, PR 9). */
.prc-retiradas-enlace { margin: 0 0 8px; font-size: var(--prc-t-menudo); }
.prc-en-nombre { margin: 0 0 10px; }
.prc-en-nombre[open] {
  padding: 12px;
  max-width: 40rem;
  border: 1px solid var(--prc-c-linea);
  border-radius: var(--prc-radio);
}
.prc-centros-encontrados { margin: 0 0 10px; padding-left: 1.2em; }
.prc-prorroga,
.prc-retirar,
.prc-historial-caja { margin-top: 10px; padding-top: 8px; border-top: 1px solid var(--prc-c-linea); }
.prc-historial-enlace { margin: 8px 0 0; font-size: var(--prc-t-menudo); }

.prc-incompleta { margin: 4px 0 0; font-size: var(--prc-t-menudo); color: var(--prc-c-tabla); }
.prc-incompleta__titulo { font-weight: 700; }
.prc-incompleta__motivos { margin: 0; padding-left: 1.1em; }

.prc-paginacion__lista { display: flex; flex-wrap: wrap; gap: 4px; margin: 12px 0 0; padding: 0; list-style: none; }
.prc-paginacion__enlace,
.prc-paginacion__actual,
.prc-paginacion__hueco { display: inline-block; min-width: 2em; padding: 2px 8px; text-align: center; }
.prc-paginacion__enlace { border: 1px solid var(--prc-c-linea); border-radius: var(--prc-radio); }
.prc-paginacion__actual { font-weight: 700; border: 1px solid var(--prc-c-azul); border-radius: var(--prc-radio); }

/* El diálogo de exportar (ADR-0039). Sin guion es un desplegable con el
   formulario dentro; con guion, `prc-app.js` mete el formulario en un
   `.prc-dialogo` y estas reglas valen igual en los dos sitios. */
.prc-exportar { margin: 0; }
.prc-exportar > summary { list-style: none; cursor: pointer; }
.prc-exportar > summary::-webkit-details-marker { display: none; }
.prc-exportar[open] > .prc-exportar__form {
  margin-top: 10px;
  padding: 16px;
  border: 1px solid var(--prc-linea);
  border-radius: var(--prc-r);
  background: var(--prc-sup);
}
.prc-exportar__titulo { margin: 0 0 12px; font-size: 18px; }
.prc-exportar__grupo,
.prc-exportar__columnas { margin: 0 0 12px; padding: 0; border: 0; min-width: 0; }
.prc-exportar__grupo > legend,
.prc-exportar__columnas > legend { margin-bottom: 4px; font-weight: 700; font-size: 14px; }
.prc-exportar__columnas { display: flex; flex-wrap: wrap; gap: 2px 16px; }
.prc-exportar__columnas > legend { flex-basis: 100%; font-weight: 600; color: var(--prc-c-medio); }
.prc-exportar .prc-casilla { display: flex; gap: 6px; align-items: baseline; margin: 2px 0; font-size: 14px; }
.prc-dialogo--exportar[open] { width: min(720px, calc(100vw - 32px)); }
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

   Cuatro entradas en mayúsculas grises —Inicio, Solicitudes de mi centro, Administrar
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
/* A quién se dirige: no es un estado, así que va sin relleno y con borde. */
.prc-state-audience { background: transparent; color: var(--prc-gris-texto); box-shadow: inset 0 0 0 1px currentColor; white-space: normal; }

/* La ayuda de «Quién solicita»: la de centros va con el interruptor de
   `data-prc-schools-only`, y la de profesorado se calla mientras aquella se
   vea. Sin guion se ve la que toca a lo guardado; sin `~`, las dos. */
[data-prc-schools-only]:not([hidden]) ~ .prc-solo-docentes { display: none; }

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
.prc-aviso:focus { outline: 2px solid currentColor; outline-offset: 2px; }
.prc-aviso p { margin: 0; }
.prc-aviso__campos { margin: 4px 0 0; padding-left: 20px; }
.prc-aviso__campos a { color: inherit; text-decoration: underline; }

/* El campo que falla, con Bootstrap y sin él: borde rojo y su línea debajo. */
.prc-control.is-invalid,
.prc-opciones.is-invalid,
.prc-opciones[aria-invalid="true"] { border-color: var(--prc-mal-texto); }
.prc-opciones[aria-invalid="true"] { border-left: 3px solid var(--prc-mal-texto); padding-left: 8px; }
.invalid-feedback.d-block { display: block; margin: 4px 0 0; color: var(--prc-mal-texto); font-size: .875rem; }

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

/* La secundaria de `Assets::button_class()`: blanca con borde gris, que no
   compite con la principal ni se confunde con un campo. */
.prc-btn-sec { color: #495057; background: #fff; border-color: #6c757d; }
.prc-btn-sec:hover { color: #fff; background: #6c757d; filter: none; }

/* La acción final de una pantalla —presentar la solicitud—: la principal, más grande. */
.prc-btn-final { padding: 10px 20px; font-size: 18px; font-weight: 600; }

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
body.prc-app .btn-outline-secondary {
  --bs-btn-color: #495057;
  --bs-btn-border-color: #6c757d;
  --bs-btn-hover-bg: #6c757d;
  --bs-btn-hover-border-color: #6c757d;
}
body.prc-app .btn-light {
  --bs-btn-bg: var(--prc-c-fondo-accion);
  --bs-btn-border-color: var(--prc-c-fondo-accion);
  --bs-btn-color: var(--prc-c-tabla);
}
/* La acción destructiva o difícil de deshacer (`Assets::button_class( \'danger\' )`)
   se distingue sin gritar: tonal, con el borde del mismo rojo, no roja rellena.
   Al pasar por encima se rellena, para que se vea qué se va a pulsar. */
body.prc-app .prc-btn-borrar {
  --bs-btn-bg: var(--prc-mal-cont);
  --bs-btn-border-color: var(--prc-mal);
  --bs-btn-color: var(--prc-mal);
  --bs-btn-hover-bg: var(--prc-mal);
  --bs-btn-hover-border-color: var(--prc-mal);
  --bs-btn-hover-color: #fff;
  --bs-btn-active-bg: var(--prc-mal);
  --bs-btn-active-border-color: var(--prc-mal);
  --bs-btn-active-color: #fff;
}
.prc-sin-bootstrap .prc-btn-borrar { background: var(--prc-mal-cont); color: var(--prc-mal); border-color: var(--prc-mal); }
.prc-sin-bootstrap .prc-btn-borrar:hover { background: var(--prc-mal); color: #fff; filter: none; }

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
  'css/prc-ayuda.css' => '/* La ayuda: dos guías y las preguntas frecuentes (ADR-0045).
   ==========================================================================

   Lo propio de esta pantalla y la línea «¿Dudas? Consulte la ayuda» que
   llevan otras. Todo cuelga de `.prc-ayuda` salvo esa línea, para que
   ninguna regla de aquí se escape a las demás pantallas. Sin diseño nuevo:
   los tokens del armazón y nada más; la mejora visual irá aparte. */

/* --- las tres guías ------------------------------------------------------ */

/* Enlaces de verdad, uno por guía: cada uno es una dirección. */
.prc-ayuda__guias ul {
  display: flex;
  flex-wrap: wrap;
  gap: 0;
  list-style: none;
  margin: 0 0 24px;
  padding: 0;
  border-bottom: 1px solid var(--prc-c-sindato);
}

.prc-ayuda__guias a {
  display: block;
  padding: 8px 16px;
  margin-bottom: -1px;
  border: 1px solid transparent;
  color: var(--prc-c-texto);
  font: var(--prc-p-base) var(--prc-t-base)/var(--prc-i-base) var(--prc-fuente);
  text-decoration: none;
}

.prc-ayuda__guias a:hover { color: var(--prc-c-titulo); }

.prc-ayuda__guias a.is-activo {
  border-color: var(--prc-c-sindato) var(--prc-c-sindato) #fff;
  background: #fff;
  color: var(--prc-c-titulo);
  font-weight: var(--prc-p-fuerte);
}

.prc-ayuda__guias a:focus-visible { outline: 2px solid var(--prc-c-azul-rotulo); outline-offset: -2px; }

/* --- la guía -------------------------------------------------------------- */

.prc-ayuda__titulo {
  margin: 0 0 8px;
  color: var(--prc-c-titulo);
  font: var(--prc-p-h2) var(--prc-t-h2)/var(--prc-i-h2) var(--prc-fuente);
}

.prc-ayuda__indice {
  margin: 16px 0 28px;
  padding: 12px 18px;
  background: var(--prc-info-fondo);
  border: 1px solid var(--prc-info-borde);
}

.prc-ayuda__indice-titulo {
  margin: 0 0 6px;
  color: var(--prc-info-texto);
  font: var(--prc-p-fuerte) var(--prc-t-base)/var(--prc-i-base) var(--prc-fuente);
}

.prc-ayuda__indice ol,
.prc-ayuda__indice ul { margin: 0; padding-left: 22px; }

.prc-ayuda__apartado {
  margin: 0 0 24px;
  scroll-margin-top: 16px;
  color: var(--prc-c-texto);
  font: var(--prc-p-base) var(--prc-t-base)/var(--prc-i-base) var(--prc-fuente);
}

.prc-ayuda__apartado-titulo {
  margin: 0 0 8px;
  color: var(--prc-c-titulo);
  font: var(--prc-p-h3) var(--prc-t-h3)/var(--prc-i-h3) var(--prc-fuente);
}

.prc-ayuda__apartado--h4 .prc-ayuda__apartado-titulo {
  font: var(--prc-p-h5) var(--prc-t-h5)/var(--prc-i-h5) var(--prc-fuente);
  font-weight: var(--prc-p-fuerte);
}

.prc-ayuda__apartado table {
  width: 100%;
  margin: 8px 0 12px;
  border-collapse: collapse;
}

.prc-ayuda__apartado th,
.prc-ayuda__apartado td {
  padding: 4px 8px;
  border: 1px solid var(--prc-c-sindato);
  text-align: left;
  vertical-align: top;
}

/* El hueco de una captura que todavía no hay: se ve, se lee y no estorba.
   Cuando llegue la imagen, el mismo sitio lleva una figura. */
.prc-ayuda__captura { margin: 8px 0 16px; }
.prc-ayuda__captura img { max-width: 100%; height: auto; border: 1px solid var(--prc-c-sindato); }

.prc-ayuda__captura--pendiente {
  padding: 10px 14px;
  border: 1px dashed var(--prc-c-tenue);
  color: var(--prc-c-medio);
  font-size: var(--prc-t-menudo);
  font-style: italic;
}

.prc-ayuda__soporte {
  margin: 32px 0 0;
  padding: 16px 18px;
  border-top: 2px solid var(--prc-c-azul);
  color: var(--prc-c-texto);
  font: var(--prc-p-base) var(--prc-t-base)/var(--prc-i-base) var(--prc-fuente);
}

/* --- «¿Dudas? Consulte la ayuda», en las demás pantallas ----------------- */

.prc-ayuda-enlace {
  margin: 4px 0 12px;
  color: var(--prc-c-texto);
  font-size: var(--prc-t-menudo);
  line-height: var(--prc-i-menudo);
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

.prc-acceso__texto { margin: 0; padding: 0 0 16px; }
.prc-acceso__accion { margin: 0; padding: 0; }

/* Por qué no hay botón, en su sitio: el motivo, la cuenta con la que se ha
   entrado y las dos salidas. Texto a 14 px como mínimo: es lo que se lee. */
.prc-acceso__motivo { margin: 0 0 16px; font-size: 14px; }
.prc-acceso__motivo p { margin: 0 0 8px; }
.prc-acceso__salidas { margin: 0; padding-left: 1.2em; }
.prc-acceso__salidas li + li { margin-top: 4px; }

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
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}

/* --- estrecho -------------------------------------------------------------- */

@media (max-width: 640px) {
  .prc-titulo-proc { font-size: 24px; line-height: 26px; }
  /* Las pestañas de ancho fijo no caben: pasan a repartirse la fila. */
  .prc-tabs-ficha__barra a { width: auto; flex: 1 1 auto; padding: 0 12px; }
  .prc-tabs-ficha__panel { padding: 16px; }
  .prc-ficha-pie { justify-content: flex-start; }
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
   Es el botón de siempre (`Assets::button_class( \'final\' )`), con aire
   arriba y abajo: la acción con la que termina la pantalla. */
.prc-form--solicitud .prc-form__envio .prc-btn-final { margin: 30px 0; }

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

.prc-solicitud-estado__completa { display: block; margin-top: 4px; font-weight: var(--prc-p-fuerte); }

/* --- los pasos de la solicitud -------------------------------------------
   Datos, personas y documentos, cada uno con «Hecho» o «Falta» escrito: el
   color acompaña al texto, nunca lo sustituye. El primero que falta lleva
   `aria-current="step"` y un filete. */
.prc-pasos-caja { margin: 0 0 20px; }

.prc-pasos {
  display: flex;
  flex-wrap: wrap;
  gap: 8px 24px;
  padding: 0;
  margin: 0;
  list-style: none;
  counter-reset: prc-paso;
}

.prc-paso {
  counter-increment: prc-paso;
  padding: 6px 10px;
  font: var(--prc-p-base) var(--prc-t-base) / var(--prc-i-base) var(--prc-fuente);
  color: var(--prc-c-titulo);
  border: 1px solid var(--prc-c-linea);
  border-radius: var(--prc-radio-caja);
}

.prc-paso::before { content: counter(prc-paso) ". "; font-weight: var(--prc-p-fuerte); }
.prc-paso[aria-current="step"] { box-shadow: inset 3px 0 0 var(--prc-avi-texto); }

.prc-paso__estado {
  display: inline-block;
  padding: 0 6px;
  margin-left: 4px;
  font-weight: var(--prc-p-fuerte);
  border-radius: var(--prc-radio-caja);
}

.prc-paso--hecho .prc-paso__estado { color: var(--prc-ok-texto); background: var(--prc-ok-fondo); }
.prc-paso--falta .prc-paso__estado { color: var(--prc-avi-texto); background: var(--prc-avi-fondo); }

/* El paso 2 anunciado encima del botón que cierra el paso 1. */
.prc-form__siguiente {
  margin: 20px 0 0;
  font: var(--prc-p-base) var(--prc-t-base) / var(--prc-i-base) var(--prc-fuente);
  color: var(--prc-c-titulo);
}

.prc-personas__plazo { margin: 0 0 8px; color: var(--prc-c-titulo); }

/* --- el justificante -----------------------------------------------------
   Se imprime con el navegador: sin cabecera, sin menú, sin pie y sin lo que
   solo sirve en pantalla. Sin JavaScript. */
.prc-justificante__datos { margin-bottom: 20px; }

@media print {
  body:has(.prc-justificante) .prc-top,
  body:has(.prc-justificante) .prc-tabs,
  body:has(.prc-justificante) .prc-pie,
  body:has(.prc-justificante) #wpadminbar,
  .prc-justificante .prc-no-imprimir { display: none !important; }

  .prc-justificante { color: #000; }
}
',
  'css/prc-mi-centro.css' => '/* «Solicitudes de mi centro» (y «Mis solicitudes» del profesorado).
   ==========================================================================

   Lo propio de esta pantalla y nada más: la ayuda de ordenación, el
   recuento, la columna «Personas», las cabeceras ordenables con su flecha y el convenio de celda
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

/* La ayuda de ordenación es una sola línea: lo que importa es la tabla. */
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
  font-size: 14px;
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

/* El estado del procedimiento se presenta como etapas ordenadas, con texto además de color. */
.prc-ciclo-publicacion { list-style: none; margin: 0 0 24px; padding: 0 0 0 12px; }
.prc-ciclo-publicacion__paso { position: relative; margin: 0; padding: 0 0 18px 24px; border-left: 2px solid var(--prc-c-linea); }
.prc-ciclo-publicacion__paso::before { position: absolute; top: 3px; left: -7px; width: 10px; height: 10px; border: 2px solid var(--prc-c-linea); border-radius: 50%; background: var(--prc-c-fondo); content: ""; }
.prc-ciclo-publicacion__paso--completado { border-color: var(--prc-ok-texto); }
.prc-ciclo-publicacion__paso--completado::before { border-color: var(--prc-ok-texto); background: var(--prc-ok-texto); }
.prc-ciclo-publicacion__paso--actual::before { border-color: var(--prc-c-azul-borde); background: var(--prc-c-fondo); box-shadow: 0 0 0 3px var(--prc-info-borde); }
.prc-ciclo-publicacion__paso:last-child { border-left-color: transparent; padding-bottom: 0; }
.prc-ciclo-publicacion__paso h3 { margin: 0; font-size: var(--prc-t-base); }
.prc-ciclo-publicacion__paso p { margin: 4px 0 0; }
.prc-ciclo-publicacion__actual { display: inline-block; margin-left: 6px; font-size: var(--prc-t-menudo); font-weight: 400; }

/* «Antes de publicar»: una línea por punto, con su marca (✓, ✗, !) en su
   color y la palabra para el lector de pantalla. El color nunca va solo. */
.prc-antes-de-publicar { margin: 0 0 24px; padding: 12px 16px; border: 1px solid var(--prc-c-linea); border-radius: var(--prc-radio); }
.prc-antes-de-publicar__titulo { margin: 0 0 8px; font-size: var(--prc-t-base); }
.prc-comprobaciones { list-style: none; margin: 0 0 8px; padding: 0; }
.prc-comprobacion { margin: 0 0 4px; }
.prc-comprobacion__marca { display: inline-block; width: 1.25em; font-weight: 700; text-align: center; }
.prc-comprobacion--ok .prc-comprobacion__marca { color: var(--prc-ok); }
.prc-comprobacion--falta { color: var(--prc-mal); }
.prc-comprobacion--aviso .prc-comprobacion__marca { color: var(--prc-esp); }

/* La consecuencia de una acción de peligro, al lado del botón. */
.prc-consecuencia { align-self: center; margin-left: 8px; font-size: .875rem; color: var(--prc-c-texto); }

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

/* CodeMirror es una mejora progresiva del textarea nativo. */
.prc-code { margin-bottom: 16px; }
.prc-code > label { display: block; margin-bottom: 4px; font-weight: 600; }
.prc-code textarea {
  width: 100%;
  min-height: 12rem;
  padding: 10px 12px;
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  font-size: 13px;
  line-height: 1.55;
  tab-size: 2;
  white-space: pre;
  overflow-x: auto;
}
.prc-code .CodeMirror {
  height: 20rem;
  border: 1px solid var(--prc-c-linea);
  border-radius: var(--prc-radio);
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  font-size: 13px;
  line-height: 1.55;
}
.prc-code .CodeMirror-focused { outline: 2px solid var(--prc-pri); outline-offset: 2px; }
.prc-codigo-admin h3 { margin: 0 0 4px; font-size: 1rem; }
.prc-codigo-admin p { margin: 0; }

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

/* --- «Preguntas» ---------------------------------------------------------- */

/* Cada pregunta enseña solo los campos de su tipo. Cada grupo dice para qué
   tipos es (`data-prc-q-for`) y aquí se esconde el resto según la opción
   elegida en «Tipo», sin guion. Un navegador sin `:has()` no entiende la
   regla y lo enseña todo, como antes: es el fallo bueno. Lo escondido se
   sigue enviando, como cuando se veía. */
.prc-pregunta:has(.prc-pregunta__tipo option[value="text"]:checked) [data-prc-q-for]:not([data-prc-q-for~="text"]),
.prc-pregunta:has(.prc-pregunta__tipo option[value="single"]:checked) [data-prc-q-for]:not([data-prc-q-for~="single"]),
.prc-pregunta:has(.prc-pregunta__tipo option[value="multiple"]:checked) [data-prc-q-for]:not([data-prc-q-for~="multiple"]),
.prc-pregunta:has(.prc-pregunta__tipo option[value="yesno"]:checked) [data-prc-q-for]:not([data-prc-q-for~="yesno"]),
.prc-pregunta:has(.prc-pregunta__tipo option[value="file"]:checked) [data-prc-q-for]:not([data-prc-q-for~="file"]),
.prc-pregunta:has(.prc-pregunta__tipo option[value="number"]:checked) [data-prc-q-for]:not([data-prc-q-for~="number"]),
.prc-pregunta:has(.prc-pregunta__tipo option[value="date"]:checked) [data-prc-q-for]:not([data-prc-q-for~="date"]),
.prc-pregunta:has(.prc-pregunta__tipo option[value="grid"]:checked) [data-prc-q-for]:not([data-prc-q-for~="grid"]) { display: none; }

/* Las preguntas nuevas de más, plegadas: la primera va a la vista. */
.prc-preguntas-mas { margin: 0 0 16px; }
.prc-preguntas-mas > summary { cursor: pointer; font-weight: 700; color: var(--prc-c-azul); padding: 8px 0; }
.prc-pregunta__orden { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
.prc-pregunta__quitar { margin-left: auto; }
.prc-pregunta__contestada { margin: 6px 0 0; font-size: .875rem; }

/* --- «Personas» ----------------------------------------------------------- */

/* Un papel por fila: el rótulo y, al lado, si exige indicar el grupo. */
.prc-papel { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 16px; margin: 0 0 8px; }
.prc-papel .prc-control { flex: 1 1 18rem; max-width: 32rem; }
',
  'js/prc-app.js' => '/*
 * Lo mínimo que las pantallas del aplicativo no pueden hacer sin guion.
 *
 * Todo es mejora sobre algo que ya funciona sin ellas: si el guion no llega,
 * el formulario se envía igual, el aviso del bloqueo ya viene pintado desde
 * el servidor, el `title` del botón sigue ahí, el botón de publicar hace lo
 * mismo que el interruptor y el diálogo de exportar es un desplegable. Nada aquí valida ni autoriza:
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
				cancelButton: \'prc-btn prc-btn-sec btn btn-outline-secondary\'
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

	/* --- 5. Ajustes exclusivos para solicitudes de centros ---------------- */

	function mostrarCamposDeCentro() {
		var audiencia = document.getElementById( \'prc-audience\' );
		if ( ! audiencia ) {
			return;
		}
		var soloCentros = \'schools\' === audiencia.value;
		Array.prototype.forEach.call( document.querySelectorAll( \'[data-prc-schools-only]\' ), function ( caja ) {
			caja.hidden = ! soloCentros;
			Array.prototype.forEach.call( caja.querySelectorAll( \'input\' ), function ( input ) {
				input.disabled = ! soloCentros;
			} );
		} );
	}
	document.addEventListener( \'change\', function ( e ) {
		if ( e.target && \'prc-audience\' === e.target.id ) {
			mostrarCamposDeCentro();
		}
	} );
	document.addEventListener( \'DOMContentLoaded\', mostrarCamposDeCentro );

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

	/* --- 6. El diálogo de exportar --------------------------------------- */

	/*
	 * `data-prc-export` en un `<details>` con el formulario dentro: sin guion
	 * se despliega y se envía igual, y «Todas las columnas» es un campo que
	 * lee el servidor. Con guion, el formulario pasa a un `<dialog>` modal y
	 * el `<summary>` se cambia por un `<button>` con `aria-haspopup="dialog"`:
	 * un desplegable que abre un diálogo se anunciaría como lo que no es.
	 * «Todas» marca y desmarca las casillas, y solo se ve lo del formato elegido. Nada se desactiva: lo escondido viaja igual y
	 * el servidor recuerda la elección entera (ADR-0039).
	 */
	function abrirDialogo( dialogo ) {
		if ( dialogo.showModal ) {
			dialogo.showModal();
		} else {
			dialogo.setAttribute( \'open\', \'\' );
		}
	}

	function cerrarDialogo( dialogo ) {
		if ( dialogo.close ) {
			dialogo.close();
		} else {
			dialogo.removeAttribute( \'open\' );
		}
	}

	function formatoElegido( form ) {
		var marcado = form.querySelector( \'[data-prc-export-format]:checked\' );
		Array.prototype.forEach.call( form.querySelectorAll( \'[data-prc-export-for]\' ), function ( caja ) {
			caja.hidden = !! marcado && caja.getAttribute( \'data-prc-export-for\' ) !== marcado.value;
		} );
	}

	/*
	 * Con un filtro en la tabla, el diálogo cuenta quién sale en el fichero
	 * de usuarios para «las del filtro» y para «todas»; sin guion se leen las
	 * dos, con su rótulo. Con guion, solo la de la opción elegida.
	 */
	function ambitoElegido( form ) {
		var marcado = form.querySelector( \'[data-prc-export-scope]:checked\' );
		Array.prototype.forEach.call( form.querySelectorAll( \'[data-prc-export-scope-count]\' ), function ( cifra ) {
			cifra.hidden = !! marcado && cifra.getAttribute( \'data-prc-export-scope-count\' ) !== marcado.value;
		} );
	}

	function prepararExportar( caja ) {
		var form = caja.querySelector( \'form\' );
		var resumen = caja.querySelector( \'summary\' );
		if ( ! form || ! resumen || caja.dataset.prcDialog ) {
			return;
		}
		var dialogo = document.createElement( \'dialog\' );
		dialogo.className = \'prc-dialogo prc-dialogo--exportar\';
		dialogo.id = \'prc-exportar-dialogo-\' + document.querySelectorAll( \'.prc-dialogo--exportar\' ).length;
		var titulo = form.getAttribute( \'aria-labelledby\' );
		if ( titulo ) {
			dialogo.setAttribute( \'aria-labelledby\', titulo );
		}
		var cancelar = document.createElement( \'button\' );
		cancelar.type = \'button\';
		cancelar.className = \'prc-btn prc-btn-sec btn btn-outline-secondary\';
		cancelar.textContent = \'Cancelar\';
		cancelar.setAttribute( \'data-prc-export-close\', \'\' );
		var acciones = form.querySelector( \'.prc-acciones\' ) || form;
		acciones.appendChild( cancelar );

		var abrir = document.createElement( \'button\' );
		abrir.type = \'button\';
		abrir.className = resumen.className;
		abrir.textContent = resumen.textContent;
		abrir.setAttribute( \'aria-haspopup\', \'dialog\' );
		abrir.setAttribute( \'aria-controls\', dialogo.id );
		abrir.setAttribute( \'data-prc-export-open\', dialogo.id );

		dialogo.appendChild( form );
		caja.parentNode.insertBefore( abrir, caja );
		caja.parentNode.insertBefore( dialogo, caja.nextSibling );
		caja.open = false;
		caja.hidden = true;
		caja.dataset.prcDialog = dialogo.id;
		formatoElegido( form );
		ambitoElegido( form );
	}

	document.addEventListener( \'DOMContentLoaded\', function () {
		Array.prototype.forEach.call( document.querySelectorAll( \'[data-prc-export]\' ), prepararExportar );
	} );

	document.addEventListener( \'click\', function ( e ) {
		var abrir = e.target.closest ? e.target.closest( \'[data-prc-export-open]\' ) : null;
		var dialogo = abrir ? document.getElementById( abrir.getAttribute( \'data-prc-export-open\' ) ) : null;
		if ( dialogo ) {
			abrirDialogo( dialogo );
			return;
		}
		var cerrar = e.target.closest ? e.target.closest( \'[data-prc-export-close]\' ) : null;
		if ( cerrar && cerrar.closest( \'dialog\' ) ) {
			cerrarDialogo( cerrar.closest( \'dialog\' ) );
		}
	} );

	document.addEventListener( \'change\', function ( e ) {
		var form = e.target.closest ? e.target.closest( \'.prc-exportar__form\' ) : null;
		if ( ! form ) {
			return;
		}
		var columnas = form.querySelectorAll( \'[data-prc-export-col]\' );
		var todas = form.querySelector( \'[data-prc-export-all]\' );
		if ( e.target.hasAttribute( \'data-prc-export-all\' ) ) {
			Array.prototype.forEach.call( columnas, function ( casilla ) {
				casilla.checked = e.target.checked;
			} );
		} else if ( e.target.hasAttribute( \'data-prc-export-col\' ) && todas ) {
			todas.checked = Array.prototype.every.call( columnas, function ( casilla ) {
				return casilla.checked;
			} );
		} else if ( e.target.hasAttribute( \'data-prc-export-format\' ) ) {
			formatoElegido( form );
		} else if ( e.target.hasAttribute( \'data-prc-export-scope\' ) ) {
			ambitoElegido( form );
		}
	} );

	// La descarga es la respuesta del envío: la página no cambia, así que el
	// diálogo se cierra a mano, después de que el envío haya salido.
	document.addEventListener( \'submit\', function ( e ) {
		var dialogo = e.target.closest ? e.target.closest( \'.prc-dialogo--exportar\' ) : null;
		if ( dialogo && ! e.defaultPrevented ) {
			window.setTimeout( function () {
				cerrarDialogo( dialogo );
			}, 0 );
		}
	} );

	/* --- 7. Revisar en bloque: marcar las de esta página ---------------- */

	/*
	 * `data-prc-bulk-page` es la casilla «Marcar las de esta página» y
	 * `data-prc-bulk-row`, la de cada fila, que apunta al formulario del bloque
	 * con el atributo `form`. Sin guion, la casilla de la página es un campo que
	 * lee el servidor y revisa la página entera. Con guion, además, marca y
	 * desmarca las de las filas, y se desmarca sola en cuanto falta alguna: así
	 * lo que se ve marcado es lo que se envía.
	 */
	document.addEventListener( \'change\', function ( e ) {
		var casilla = e.target;
		if ( ! casilla || ! casilla.hasAttribute ) {
			return;
		}
		var filas = document.querySelectorAll( \'[data-prc-bulk-row]\' );
		if ( casilla.hasAttribute( \'data-prc-bulk-page\' ) ) {
			Array.prototype.forEach.call( filas, function ( fila ) {
				fila.checked = casilla.checked;
			} );
		} else if ( casilla.hasAttribute( \'data-prc-bulk-row\' ) ) {
			var pagina = document.querySelector( \'[data-prc-bulk-page]\' );
			if ( pagina ) {
				pagina.checked = filas.length > 0 && Array.prototype.every.call( filas, function ( fila ) {
					return fila.checked;
				} );
			}
		}
	} );

	/* --- 8. Un solo envío ------------------------------------------------ */

	/*
	 * `data-prc-once` en el formulario: en cuanto el envío sale —ya
	 * confirmado— se apagan sus botones y un segundo envío no sale. Es para lo
	 * que, repetido, hace dos veces lo mismo: duplicar, por ejemplo. En
	 * `window` y no en `document` para ir detrás de la confirmación, que para
	 * el primer envío mientras pregunta. Sin guion se acepta el doble envío.
	 * Al volver con «Atrás» de la caché del navegador se encienden otra vez.
	 */
	function botones( form, apagados ) {
		Array.prototype.forEach.call( form.querySelectorAll( \'button[type="submit"], input[type="submit"]\' ), function ( boton ) {
			boton.disabled = apagados;
		} );
	}

	window.addEventListener( \'submit\', function ( e ) {
		var form = e.target;
		// Una vez por envío, aunque el guion se haya cargado dos veces.
		if ( e.prcUnaVez || ! ( form instanceof HTMLFormElement ) || ! form.hasAttribute( \'data-prc-once\' ) ) {
			return;
		}
		e.prcUnaVez = true;
		if ( \'1\' === form.dataset.prcEnviado ) {
			e.preventDefault();
			return;
		}
		if ( e.defaultPrevented ) {
			return;
		}
		form.dataset.prcEnviado = \'1\';
		botones( form, true );
	} );

	window.addEventListener( \'pageshow\', function ( e ) {
		if ( ! e.persisted ) {
			return;
		}
		Array.prototype.forEach.call( document.querySelectorAll( \'form[data-prc-once]\' ), function ( form ) {
			delete form.dataset.prcEnviado;
			botones( form, false );
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
