<?php
/**
 * Meta keys and closed vocabularies for the prc_application CPT.
 *
 * @package Prc
 */

namespace Prc\Meta;

/**
 * Las claves de una solicitud de centro.
 *
 * La solicitud es un núcleo fijo más las respuestas a las preguntas del
 * procedimiento (ADR-0019). Lo que no está aquí no se copia: el nombre, los
 * apellidos y el correo de quien solicita son su cuenta (`post_author`), y
 * el procedimiento es `post_parent` (ADR-0018).
 *
 * El `auth_callback` de todas devuelve `false`: no hay ninguna vía por la que
 * se escriban desde fuera del aplicativo.
 */
final class ApplicationMetaKeys {

	/**
	 * Código del centro que solicita. Viene de la persona (ADR-0016), nunca del formulario.
	 */
	public const CENTRE_CODE = 'prc_centre_code';

	/**
	 * Nombre del centro en el momento de solicitar: una foto, no una referencia.
	 */
	public const CENTRE_NAME = 'prc_centre_name';

	/**
	 * Cargo de quien presenta la solicitud: lista de {@see positions()}.
	 */
	public const APPLICANT_POSITION = 'prc_applicant_position';

	/**
	 * Persona coordinadora, `{name, email}`, si el procedimiento la pide.
	 */
	public const COORDINATOR = 'prc_coordinator';

	/**
	 * Respuestas a las preguntas del procedimiento, indexadas por su `key`.
	 */
	public const ANSWERS = 'prc_answers';

	/**
	 * Documentos privados aportados, indexados por la `key` de su pregunta.
	 *
	 * **Ni una ruta absoluta, ni una URL, ni un identificador de adjunto**: un
	 * descriptor con lo que hace falta para enseñarlo y para encontrarlo
	 * (ADR-0028). Va aparte de {@see ANSWERS} a propósito: ahí viven las
	 * respuestas a las preguntas de lista cerrada, y un almacén de ficheros
	 * metido dentro las convertiría en otra cosa.
	 */
	public const FILES = 'prc_files';

	/**
	 * Estado de revisión: lista de {@see review_states()}.
	 */
	public const REVIEW_STATE = 'prc_review_state';

	/**
	 * Nota de quien gestiona. Obligatoria al pedir subsanar o al excluir.
	 */
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

	/**
	 * Every meta key that hangs off an application.
	 *
	 * @return string[]
	 */
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

	/**
	 * Closed vocabulary of applicant positions within the leadership team.
	 *
	 * @return array<string, string> slug => etiqueta.
	 */
	public static function positions(): array {
		return array(
			self::POSITION_HEAD            => 'Dirección',
			self::POSITION_DEPUTY_HEAD     => 'Vicedirección',
			self::POSITION_HEAD_OF_STUDIES => 'Jefatura de estudios',
			self::POSITION_SECRETARY       => 'Secretaría',
			self::POSITION_OTHER           => 'Otro',
		);
	}

	/**
	 * Closed vocabulary of review states (ADR-0020).
	 *
	 * @return array<string, string> slug => etiqueta.
	 */
	public static function review_states(): array {
		return array(
			self::REVIEW_SUBMITTED => 'Presentada',
			self::REVIEW_AMEND     => 'A subsanar',
			self::REVIEW_ADMITTED  => 'Admitida',
			self::REVIEW_EXCLUDED  => 'Excluida',
		);
	}

	/**
	 * The review states that need a note from whoever reviews.
	 *
	 * @return string[]
	 */
	public static function states_needing_note(): array {
		return array( self::REVIEW_AMEND, self::REVIEW_EXCLUDED );
	}
}
