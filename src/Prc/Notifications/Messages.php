<?php
/**
 * The plain-text subject and body of each mail of the application.
 *
 * @package Prc
 */

namespace Prc\Notifications;

/**
 * Lo que dice cada correo, en texto plano.
 *
 * Recibe el contexto ya resuelto por {@see Mailer} —títulos, nombres, fechas,
 * respuestas como texto y enlaces— y no consulta nada: así se lee y se prueba
 * sin base de datos, y lo que llega a la persona es exactamente lo que se ve
 * aquí. **No se vuelcan claves internas ni identificadores**: el contexto ya
 * viene con rótulos, y el único número de la base de datos que viaja es el
 * que va dentro de un enlace (ADR-0038).
 *
 * Claves del contexto que se usan, todas opcionales:
 *
 * - `procedure`, `centre`, `applicant`, `date`, `url`: textos;
 * - `answers`: lista de `{label, text}`, una línea por respuesta;
 * - `created`: si la solicitud es nueva (para el aviso a los contactos);
 * - `state`, `note`: rótulo del estado de revisión y la nota que se manda;
 * - `document`, `file`: rótulo de la entrega y nombre del documento;
 * - `missing`, `editor_url`, `workspace_url`, `extra`: el correo del alta;
 * - `change`, `before`, `after`: el cambio en la lista de personas y la
 *   persona antes y después, ya escrita en una línea y sin DNI;
 * - `ack`: en una retirada, si es el acuse a quien la retira;
 * - `until`: el último día de una prórroga, ya escrito como se lee.
 */
final class Messages {

	/**
	 * Subject and body of one event.
	 *
	 * @param string               $event One of {@see Mailer::events()}.
	 * @param array<string, mixed> $ctx   Resolved context.
	 * @return array{subject:string, message:string} Both empty for an unknown event.
	 */
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

	/**
	 * Subject without prefix and lines of the body, per event.
	 *
	 * @param string               $event Event.
	 * @param array<string, mixed> $ctx   Context.
	 * @return array{0:string, 1:array<int, string>}|null
	 */
	private static function parts( string $event, array $ctx ): ?array {
		$titulo = self::text( $ctx, 'procedure' );
		$quien  = '' !== self::text( $ctx, 'centre' ) ? self::text( $ctx, 'centre' ) : self::text( $ctx, 'applicant' );

		switch ( $event ) {
			case Mailer::EVENT_APPLICATION_SUBMITTED:
				return array(
					'Solicitud presentada: ' . $titulo,
					array_merge(
						array( 'Se ha presentado la solicitud al procedimiento «' . $titulo . '». Este es el resumen de lo presentado.', '' ),
						self::summary( $ctx ),
						self::link( 'Puede consultarla, y modificarla mientras el plazo siga abierto, en:', $ctx, 'url' ),
						array( '', 'Este correo es un acuse automático: no hace falta responderlo.' )
					),
				);

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

	/**
	 * Body of the «procedure created» mail.
	 *
	 * @param string               $titulo Procedure title.
	 * @param array<string, mixed> $ctx    Context.
	 * @return array<int, string>
	 */
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

	/**
	 * Subject and body of a change in the list of people of an application.
	 *
	 * @param string               $titulo Procedure title.
	 * @param string               $quien  The school, or who applied.
	 * @param array<string, mixed> $ctx    Context.
	 * @return array{0:string, 1:array<int, string>}|null Null for an unknown change.
	 */
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

	/**
	 * Subject and body of a withdrawn application: the acknowledgement or the notice to the contacts.
	 *
	 * @param string               $titulo Procedure title.
	 * @param string               $quien  The school, or who applied.
	 * @param array<string, mixed> $ctx    Context; `ack` for the acknowledgement.
	 * @return array{0:string, 1:array<int, string>}
	 */
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

	/**
	 * The summary of an application: who, when and one line per answer.
	 *
	 * @param array<string, mixed> $ctx Context.
	 * @return array<int, string>
	 */
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

	/**
	 * «Rótulo: valor», or nothing when there is no value.
	 *
	 * @param string $rotulo Label.
	 * @param string $valor  Value.
	 * @return array<int, string>
	 */
	private static function field( string $rotulo, string $valor ): array {
		return '' === $valor ? array() : array( $rotulo . ': ' . $valor );
	}

	/**
	 * A blank line, a sentence and a link on its own line; nothing without link.
	 *
	 * @param string               $frase Sentence before the link.
	 * @param array<string, mixed> $ctx   Context.
	 * @param string               $clave Context key with the URL.
	 * @return array<int, string>
	 */
	private static function link( string $frase, array $ctx, string $clave ): array {
		$url = self::text( $ctx, $clave );
		return '' === $url ? array() : array( '', $frase, $url );
	}

	/**
	 * The subject, with its prefix, on one line.
	 *
	 * @param string $asunto Subject without prefix.
	 * @return string
	 */
	private static function subject( string $asunto ): string {
		/**
		 * Filter the prefix of every subject the application sends.
		 *
		 * @param string $prefijo Prefix, `[Procedimientos]` by default; empty for none.
		 */
		$prefijo = trim( (string) apply_filters( 'prc_mail_subject_prefix', '[Procedimientos]' ) );
		return self::one_line( ( '' !== $prefijo ? $prefijo . ' ' : '' ) . $asunto );
	}

	/**
	 * A text with its line breaks turned into spaces.
	 *
	 * @param string $texto Text.
	 * @return string
	 */
	private static function one_line( string $texto ): string {
		return trim( (string) preg_replace( '/\s+/u', ' ', $texto ) );
	}

	/**
	 * One string of the context.
	 *
	 * @param array<string, mixed> $ctx   Context.
	 * @param string               $clave Key.
	 * @return string
	 */
	private static function text( array $ctx, string $clave ): string {
		return isset( $ctx[ $clave ] ) && is_scalar( $ctx[ $clave ] ) ? trim( (string) $ctx[ $clave ] ) : '';
	}

	/**
	 * One list of strings of the context, without empty ones.
	 *
	 * @param array<string, mixed> $ctx   Context.
	 * @param string               $clave Key.
	 * @return array<int, string>
	 */
	private static function lines( array $ctx, string $clave ): array {
		$lista = isset( $ctx[ $clave ] ) && is_array( $ctx[ $clave ] ) ? $ctx[ $clave ] : array();
		return array_values( array_filter( array_map( 'strval', array_filter( $lista, 'is_scalar' ) ), 'strlen' ) );
	}

	/**
	 * The lines as one body, ending in a line break.
	 *
	 * @param array<int, string> $lineas Lines.
	 * @return string
	 */
	private static function join( array $lineas ): string {
		return implode( "\n", $lineas ) . "\n";
	}
}
