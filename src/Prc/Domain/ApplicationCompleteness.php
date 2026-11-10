<?php
/**
 * Whether an application is incomplete, and why, in words.
 *
 * @package Prc
 */

namespace Prc\Domain;

use Prc\Meta\ApplicationMetaKeys;

/**
 * Cuándo a una solicitud le falta algo. Puro: no lee nada.
 *
 * Incompleta es una de dos cosas, y nada más:
 *
 * - la lista de personas encendida y por debajo del mínimo
 *   ({@see ApplicationPeople::is_complete()}, ADR-0041): no se rechaza al
 *   guardar, se marca aquí;
 * - una pregunta de entrega con plazo propio, **obligatoria**, cuyo plazo
 *   ya ha vencido sin que haya documento. Una entrega abierta todavía puede
 *   llegar; una opcional no falta nunca.
 *
 * Una solicitud excluida no está incompleta: no hay nada que completar.
 * Los motivos se escriben para quien revisa: «Faltan 2 personas», «Falta:
 * Memoria final».
 */
final class ApplicationCompleteness {

	/**
	 * What is missing in one application.
	 *
	 * @param array<string, mixed>             $spec      People specification of the procedure; disabled for teachers.
	 * @param mixed                            $people    Stored list of people of the application.
	 * @param array<int, array<string, mixed>> $questions Normalised questions of the procedure.
	 * @param mixed                            $files     Stored documents of the application, by question key.
	 * @param string                           $today     Today, `Y-m-d`.
	 * @param string                           $review    Review state of the application.
	 * @param bool                             $pending   Whether it was submitted on behalf of the school and the school has not completed it yet (ADR-0044).
	 * @return string[] Reasons, in words; empty when nothing is missing.
	 */
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

	/**
	 * The required deliveries whose own deadline is already over.
	 *
	 * @param array<int, array<string, mixed>> $questions Normalised questions.
	 * @param string                           $today     Today, `Y-m-d`.
	 * @return array<int, array<string, mixed>>
	 */
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

	/**
	 * Whether any application of the procedure can be incomplete at all.
	 *
	 * Sin lista de personas ni entregas vencidas no hace falta leer nada de
	 * ninguna solicitud: ninguna lo está.
	 *
	 * @param array<string, mixed>             $spec      People specification; disabled for teachers.
	 * @param array<int, array<string, mixed>> $questions Normalised questions.
	 * @param string                           $today     Today, `Y-m-d`.
	 * @return bool
	 */
	public static function may_be_incomplete( array $spec, array $questions, string $today ): bool {
		return true === PeopleSpec::sanitize( $spec )['enabled'] || array() !== self::overdue( $questions, $today );
	}
}
