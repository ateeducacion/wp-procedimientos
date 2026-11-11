<?php
/**
 * Pure validation for procedure form input.
 *
 * @package Prc
 */

namespace Prc\Domain;

use Prc\Meta\ProcedureMetaKeys;

/**
 * Validates and normalises the raw fields of a procedure.
 *
 * Pura: ni una llamada a WordPress, para que se pueda probar sin cargarlo.
 * Los términos (ámbitos y curso) llegan como identificadores y aquí solo se
 * exige que los haya: que existan lo comprueba la pantalla, que es quien tiene
 * WordPress delante. Los ámbitos son varios (ADR-0025): al menos uno, y quien
 * edita solo puede poner los suyos, lo que ya no es asunto de esta clase pura
 * sino de {@see \Prc\PublicFront\ProcedureEditor::may_set_area()}.
 */
final class ProcedureInput {

	/**
	 * Validate a submitted procedure payload.
	 *
	 * Normalizaciones de la SDD: sin fecha de cierre se copia la de apertura;
	 * sin fin de subsanación se copia su inicio; un plazo de subsanación que
	 * empieza antes de que cierre el de solicitud es un error.
	 *
	 * @param array<string, mixed> $raw Raw fields.
	 * @return array{ok:bool, errors:string[], data:array<string, mixed>}
	 */
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
		// La subsanación viene después del plazo de solicitud, nunca dentro, y
		// el mismo día del cierre ya es dentro: el plazo de solicitud es
		// inclusive y `amendment` gana a `open`, así que una subsanación que
		// abre ese día deja el procedimiento «En subsanación» mientras a los
		// centros les queda todavía ese día para presentar (adenda 2026-09-16).
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

		// El primero es obligatorio y los otros dos, opcionales; lo que se
		// escriba tiene que ser un correo, así que un campo relleno y mal
		// tumba el envío en vez de caerse sin avisar.
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

	/**
	 * Why a procedure was refused, in Spanish.
	 *
	 * Vive aquí y no en cada pantalla para que todas lo cuenten igual.
	 *
	 * @param string[] $errors Error codes.
	 * @return string
	 */
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

	/**
	 * What still stops a procedure from being published, in Spanish.
	 *
	 * Guardar «Datos» ya pide el curso, y guardar «Preguntas», unas fechas
	 * válidas para cada entrega con plazo propio; pero un duplicado nace sin
	 * las dos cosas (PR 8 del PLAN-0003): se puede seguir completando en
	 * borrador, y no se publica hasta tenerlas.
	 *
	 * Sin plazo de solicitud tampoco: el estado se quedaría en «Próximo» para
	 * siempre y nadie podría solicitar. Quien quiera anunciarlo antes pone
	 * fechas futuras y se publica igual, como «Próximo». Solo se mira al
	 * publicar: lo ya publicado sin plazo se sigue guardando como estaba.
	 *
	 * @param int                              $course    Term ID of prc_course; 0 for none.
	 * @param array<int, array<string, mixed>> $questions Normalised questions.
	 * @param string                           $opens_at  First day of the application window, Y-m-d; '' for none.
	 * @return string[] Empty when it may be published.
	 */
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

	/**
	 * What of a checklist stops the publication, in Spanish.
	 *
	 * @param array<int, array<string, mixed>> $checklist What checklist() returned.
	 * @return string[] Empty when it may be published.
	 */
	public static function blocking( array $checklist ): array {
		$faltan = array();
		foreach ( $checklist as $punto ) {
			if ( ! empty( $punto['blocks'] ) && empty( $punto['ok'] ) ) {
				$faltan = array_merge( $faltan, (array) $punto['missing'] );
			}
		}
		return $faltan;
	}

	/**
	 * «Antes de publicar»: each thing the workshop checks, in the order it is filled in.
	 *
	 * Lo que bloquea —curso, plazo, fechas de las entregas— es lo mismo que
	 * {@see not_ready()}; las preguntas y la resolución solo informan: un
	 * procedimiento se puede publicar sin resolución enlazada todavía.
	 *
	 * @param array<string, mixed> $facts `course`, `questions`, `opens_at`, `closes_at`, `resolution`.
	 * @return array<int, array{key:string, ok:bool, blocks:bool, text:string, missing:string[]}>
	 */
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

	/**
	 * One point of the checklist.
	 *
	 * @param string   $key     Point key.
	 * @param bool     $ok      Whether it is done.
	 * @param bool     $blocks  Whether, missing, it stops the publication.
	 * @param string   $done    Text when it is done.
	 * @param string   $missing Text when it is not.
	 * @param string[] $why     What not_ready() says when it is not.
	 * @return array{key:string, ok:bool, blocks:bool, text:string, missing:string[]}
	 */
	private static function point( string $key, bool $ok, bool $blocks, string $done, string $missing, array $why ): array {
		return array(
			'key'     => $key,
			'ok'      => $ok,
			'blocks'  => $blocks,
			'text'    => $ok ? $done : $missing,
			'missing' => $ok ? array() : $why,
		);
	}

	/**
	 * A Y-m-d day as d-m-Y.
	 *
	 * @param string $ymd Day.
	 * @return string
	 */
	private static function dmy( string $ymd ): string {
		return implode( '-', array_reverse( explode( '-', $ymd ) ) );
	}

	/**
	 * Positive term IDs, without repeats, in the order they came.
	 *
	 * @param mixed $raw Raw value: a list, one ID, or a comma-separated string.
	 * @return int[]
	 */
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

	/**
	 * Up to three addresses, each one an address; what is not, drops.
	 *
	 * Quien mande cuatro se queda con los tres primeros, como la meta: el
	 * formulario solo pinta tres campos.
	 *
	 * @param mixed $raw Raw value: a list, or one address.
	 * @return string[]
	 */
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

	/**
	 * What came, as a list of scalars, with the blanks already gone.
	 *
	 * Un campo en blanco del formulario no es un error: es el segundo o el
	 * tercer correo que nadie escribió, o la casilla que nadie marcó.
	 *
	 * @param mixed $raw Raw value: a list, one scalar, or a comma-separated string.
	 * @return array<int, scalar>
	 */
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

	/**
	 * Keep only the ownerships of the closed list, in list order.
	 *
	 * @param mixed $raw Raw value: an array or a comma-separated string.
	 * @return string[]
	 */
	public static function ownership( $raw ): array {
		return self::keys_of( $raw, ProcedureMetaKeys::ownerships() );
	}

	/**
	 * The ownerships a stored procedure is really for: none stored is all.
	 *
	 * Uno de antes sin titularidad guardada se dirige a todos —es lo que hace
	 * `ProcedureAccess::can_apply()`—, y así lo enseña y lo vuelve a guardar
	 * el taller. Uno **nuevo** no pasa por aquí: empieza sin ninguna.
	 *
	 * @param mixed $stored Stored value of `prc_ownership`.
	 * @return string[]
	 */
	public static function stored_ownership( $stored ): array {
		$titular = self::ownership( $stored );
		return array() === $titular ? array_keys( ProcedureMetaKeys::ownerships() ) : $titular;
	}

	/**
	 * Keep only the centre kinds of the closed list, in list order.
	 *
	 * @param mixed $raw Raw value: an array or a comma-separated string.
	 * @return string[]
	 */
	public static function centre_kinds( $raw ): array {
		$seleccionados = self::keys_of(
			$raw,
			array_merge( ProcedureMetaKeys::centre_kinds(), array( 'both' => '' ) )
		);
		return in_array( 'both', $seleccionados, true ) ? array_keys( ProcedureMetaKeys::centre_kinds() ) : $seleccionados;
	}

	/**
	 * The keys of a closed list that the raw value names, in list order.
	 *
	 * @param mixed                 $raw  Raw value: an array or a comma-separated string.
	 * @param array<string, string> $vocabulary Closed list, slug => label.
	 * @return string[]
	 */
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

	/**
	 * Whether the string is a calendar date in Y-m-d form.
	 *
	 * @param string $date Date string.
	 * @return bool
	 */
	public static function is_valid_date( string $date ): bool {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return false;
		}
		$parts = array_map( 'intval', explode( '-', $date ) );
		return checkdate( $parts[1], $parts[2], $parts[0] );
	}

	/**
	 * Whether a string looks like an http(s) address.
	 *
	 * Sin `esc_url_raw()` para que la clase siga siendo pura: el saneado de
	 * verdad lo hace la meta al guardar.
	 *
	 * @param string $value Raw value.
	 * @return bool
	 */
	public static function is_url( string $value ): bool {
		return (bool) preg_match( '#^https?://[^\s/$.?\#].[^\s]*$#i', $value );
	}

	/**
	 * Whether a link of «Enlaces» may be stored.
	 *
	 * En blanco, sí. Uno nuevo o cambiado tiene que ser la dirección
	 * completa, con `https://` y su servidor: los PDF llegaban cortados. Uno
	 * guardado antes de la regla que no cambia se sigue guardando, para no
	 * impedir guardar los demás. Sin consultar la red: decir si existe lo
	 * hace «Abrir el enlace guardado».
	 *
	 * @param string $url    What was typed.
	 * @param string $stored What is stored now.
	 * @return bool
	 */
	public static function is_acceptable_link( string $url, string $stored ): bool {
		if ( '' === $url ) {
			return true;
		}
		if ( ! self::is_url( $url ) ) {
			return false;
		}
		$partes = (array) parse_url( $url ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- pura: sin WordPress.
		return $url === $stored || ( 'https' === strtolower( (string) ( $partes['scheme'] ?? '' ) ) && false !== strpos( (string) ( $partes['host'] ?? '' ), '.' ) );
	}

	/**
	 * Whether an address looks like an address.
	 *
	 * @param string $value Raw value.
	 * @return bool
	 */
	public static function is_email( string $value ): bool {
		return (bool) preg_match( '/^[^@\s]+@[^@\s.]+(\.[^@\s.]+)+$/', $value );
	}

	/**
	 * One trimmed field.
	 *
	 * @param array<string, mixed> $raw Raw fields.
	 * @param string               $key Field name.
	 * @return string
	 */
	private static function text( array $raw, string $key ): string {
		$value = $raw[ $key ] ?? '';
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}
}
