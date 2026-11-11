<?php
/**
 * The list of people of an application, painted: for the school and for whoever reviews.
 *
 * @package Prc
 */

namespace Prc\PublicFront\View;

use Prc\PublicFront\Assets;
use Prc\PublicFront\PeopleForm;
use Prc\PublicFront\PeopleSpecPanel;
use Prc\PublicFront\ProcedureEditor;
use Prc\PublicFront\Shell;

/**
 * Solo pinta: no lee la petición, no consulta, no decide (ADR-0041).
 *
 * Para el centro, la sección «Personas» de su solicitud: la lista, un
 * formulario de alta y, por fila, cambiar y dar de baja. Para quien revisa,
 * la lista y su historial, en solo lectura. Cada formulario es un POST con su
 * nonce; la baja pide confirmación con los tres escalones de siempre
 * (`data-prc-confirm`: SweetAlert2, `confirm()` y, sin guion, el envío).
 */
final class PeopleView {

	/**
	 * The «Personas» section of the application screen.
	 *
	 * @param array<string, mixed> $p What PeopleForm::model() returned.
	 * @return string Empty when the procedure has no list.
	 */
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
				<?php echo Shell::notice( (string) ( $flash['tipo'] ?? '' ), (string) ( $flash['texto'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				<?php echo self::until( $p ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				<p class="prc-campo__ayuda prc-campo--completo">
					<?php echo esc_html( (string) $p['window'] . ' ' . self::limits( (int) $p['min'], (int) $p['max'], count( $filas ) ) ); ?>
				</p>
				<?php if ( (int) $p['application_id'] > 0 && empty( $p['complete'] ) ) : ?>
					<?php echo Shell::notice( 'aviso', sprintf( 'La lista tiene %1$d %2$s y el procedimiento pide al menos %3$d: la solicitud queda incompleta hasta que la complete.', count( $filas ), 1 === count( $filas ) ? 'persona' : 'personas', (int) $p['min'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				<?php endif; ?>
				<?php echo Shell::notice( 'aviso', (string) $p['why'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				<?php echo self::table( $p, $filas, ! empty( $p['can_edit'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				<?php if ( ! empty( $p['can_edit'] ) && $lleno ) : ?>
					<p class="prc-campo--completo">La lista ha llegado al máximo de personas que admite este procedimiento. Para añadir a alguien, dé antes de baja a otra persona o cámbiela.</p>
				<?php elseif ( ! empty( $p['can_edit'] ) ) : ?>
					<details class="prc-personas__alta prc-campo--completo"<?php echo PeopleForm::NEW_ROW === (string) $p['typed_row'] ? ' open' : ''; ?>>
						<summary>Dar de alta a una persona</summary>
						<?php echo self::form( $p, PeopleForm::OP_ADD, PeopleForm::NEW_ROW === (string) $p['typed_row'] ? (array) $p['typed'] : array() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
					</details>
				<?php endif; ?>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Until when the people can be changed: the first thing, in bold.
	 *
	 * Solo cuando se puede, o cuando todavía no hay solicitud: con la lista
	 * cerrada, lo que se dice es el motivo.
	 *
	 * @param array<string, mixed> $p Model.
	 * @return string
	 */
	private static function until( array $p ): string {
		$dia = (string) ( $p['until'] ?? '' );
		if ( '' === $dia || ( empty( $p['can_edit'] ) && (int) $p['application_id'] > 0 ) ) {
			return '';
		}
		return '<p class="prc-personas__plazo prc-campo--completo"><strong>'
			. esc_html( 'Puede dar de alta, cambiar o sustituir personas hasta el ' . $dia . ', incluido.' )
			. '</strong></p>';
	}

	/**
	 * How many people the procedure asks for, and how many there are.
	 *
	 * @param int $min   Minimum.
	 * @param int $max   Maximum.
	 * @param int $count How many there are.
	 * @return string
	 */
	private static function limits( int $min, int $max, int $count ): string {
		$pide = $min > 0 ? sprintf( 'Pide entre %1$d y %2$d personas.', $min, $max ) : sprintf( 'Admite hasta %d personas.', $max );
		return $pide . ' ' . sprintf( 'Ahora hay %d.', $count );
	}

	/**
	 * The table of people, with the forms of each row when they can be written.
	 *
	 * @param array<string, mixed>              $p        Model.
	 * @param array<int, array<string, string>> $filas    Rows.
	 * @param bool                              $editable Whether the forms are painted.
	 * @return string
	 */
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
						<tr>
							<?php echo self::cells( $fila ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
							<?php if ( $editable ) : ?>
								<td class="prc-celda" data-rotulo="Acciones">
									<?php $tecleado = (string) $p['typed_row'] === $fila['id'] ? (array) $p['typed'] : array(); ?>
									<details class="prc-personas__cambio"<?php echo (string) $p['typed_row'] === $fila['id'] ? ' open' : ''; ?>>
										<summary>Cambiar o sustituir</summary>
										<?php echo self::form( $p, PeopleForm::OP_CHANGE, array() !== $tecleado ? $tecleado : $fila, $fila ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
									</details>
									<?php echo self::remove_form( $p, $fila ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
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

	/**
	 * The read-only cells of one person.
	 *
	 * @param array<string, string> $fila Row, with its labels.
	 * @return string
	 */
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

	/**
	 * The hidden fields every form of the section carries.
	 *
	 * @param array<string, mixed>  $p    Model.
	 * @param string                $op   Operation.
	 * @param array<string, string> $fila Row it acts on; empty to add.
	 * @return string
	 */
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

	/**
	 * The form to add a person, or to change one.
	 *
	 * @param array<string, mixed>  $p       Model.
	 * @param string                $op      Add or change.
	 * @param array<string, mixed>  $valores What the fields show.
	 * @param array<string, string> $fila    Row it changes; empty to add.
	 * @return string
	 */
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
			<?php echo self::hidden( $p, $op, $fila ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			<?php foreach ( $campos as $campo => list( $rotulo, $tipo, $obliga, $auto ) ) : ?>
				<div class="prc-campo prc-campo--cuarto">
					<label class="prc-campo__etiqueta" for="<?php echo esc_attr( $base . $campo ); ?>"><?php echo esc_html( $rotulo ); ?><?php echo $obliga ? ' <span class="prc-campo__obligatorio" aria-hidden="true">*</span>' : ''; ?></label>
					<input class="prc-control" type="<?php echo esc_attr( $tipo ); ?>" id="<?php echo esc_attr( $base . $campo ); ?>"
						name="<?php echo esc_attr( PeopleForm::FIELD_PERSON . '[' . $campo . ']' ); ?>" autocomplete="<?php echo esc_attr( $auto ); ?>"
						value="<?php echo esc_attr( (string) ( $valores[ $campo ] ?? '' ) ); ?>"<?php echo $obliga ? ' required' : ''; ?> />
				</div>
			<?php endforeach; ?>
			<?php echo self::select( $base . 'role', 'role', 'Papel', (array) $p['roles'], (string) ( $valores['role'] ?? '' ), true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			<?php if ( array() !== (array) $p['groups'] ) : ?>
				<?php echo self::select( $base . 'group', 'group', 'Grupo', array( '' => '— Sin grupo —' ) + (array) $p['groups'], (string) ( $valores['group'] ?? '' ), false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			<?php endif; ?>
			<div class="prc-campo prc-campo--completo">
				<button type="submit" class="<?php echo esc_attr( Assets::button_class() ); ?>"><?php echo esc_html( PeopleForm::OP_ADD === $op ? 'Dar de alta' : 'Guardar los cambios de esta persona' ); ?></button>
			</div>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * A select of the list of roles or groups.
	 *
	 * @param string                $id       Control id.
	 * @param string                $campo    Field of the person.
	 * @param string                $rotulo   Label.
	 * @param array<string, string> $opciones Key => label.
	 * @param string                $elegida  Selected key.
	 * @param bool                  $obliga   Whether it is required.
	 * @return string
	 */
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

	/**
	 * The form that removes one person, with its confirmation.
	 *
	 * @param array<string, mixed>  $p    Model.
	 * @param array<string, string> $fila Row.
	 * @return string
	 */
	private static function remove_form( array $p, array $fila ): string {
		$nombre   = trim( $fila['name'] . ' ' . $fila['surname'] );
		$pregunta = sprintf( '¿Dar de baja a %s de la lista de personas? Quedará anotado en el historial de la solicitud.', $nombre );
		return '<form class="prc-acciones" method="post" action="' . esc_url( '#' . PeopleForm::ANCHOR ) . '"'
			. ' data-prc-confirm="' . esc_attr( $pregunta ) . '" data-prc-confirm-ok="Dar de baja">'
			. self::hidden( $p, PeopleForm::OP_REMOVE, $fila )
			. '<button type="submit" class="' . esc_attr( Assets::button_class( 'danger' ) ) . '" title="' . esc_attr( 'Dar de baja a ' . $nombre ) . '">Dar de baja</button>'
			. '</form>';
	}

	/**
	 * The people of one application, read-only, for whoever reviews.
	 *
	 * Su historial es el de la solicitud entera: lo pinta
	 * {@see ApplicationActionsView::history()}.
	 *
	 * @param array<string, mixed> $r What PeopleForm::review() returned.
	 * @return string Empty when the procedure has no list.
	 */
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
			<?php echo self::table( array(), $filas, false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The «Personas» panel of the workshop: what the procedure asks about the
	 * people of each application.
	 *
	 * @param array<string, mixed> $m What ProcedureEditor::model() returned.
	 * @return string
	 */
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
				<?php echo self::check( PeopleSpecPanel::FIELD_ENABLED, 'Pedir a cada centro la lista de personas que participan', $v, 'enabled' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				<div class="prc-campo prc-campo--medio">
					<label class="prc-campo__etiqueta" for="prc-people-roles">Papeles</label>
					<textarea class="prc-control" id="prc-people-roles" name="<?php echo esc_attr( PeopleSpecPanel::FIELD_ROLES ); ?>" rows="5" aria-describedby="prc-people-roles-ayuda"><?php echo esc_textarea( (string) ( $v['roles'] ?? '' ) ); ?></textarea>
					<p class="prc-campo__ayuda" id="prc-people-roles-ayuda"><?php echo esc_html( sprintf( 'Uno por línea, hasta %d; por ejemplo «Coordinación». Escriba «| grupo» detrás del que exija grupo: «Participante | grupo». Para quitar uno, deje su línea en blanco; cambiarle el nombre no cambia a nadie de papel.', (int) ( $p['max_roles'] ?? 0 ) ) ); ?></p>
				</div>
				<div class="prc-campo prc-campo--medio">
					<label class="prc-campo__etiqueta" for="prc-people-groups">Grupos <span class="prc-campo__opcional">(opcional)</span></label>
					<textarea class="prc-control" id="prc-people-groups" name="<?php echo esc_attr( PeopleSpecPanel::FIELD_GROUPS ); ?>" rows="5" aria-describedby="prc-people-groups-ayuda"><?php echo esc_textarea( (string) ( $v['groups'] ?? '' ) ); ?></textarea>
					<p class="prc-campo__ayuda" id="prc-people-groups-ayuda"><?php echo esc_html( sprintf( 'Uno por línea, hasta %d; por ejemplo «Eje 1». Para quitar uno, deje su línea en blanco.', (int) ( $p['max_groups'] ?? 0 ) ) ); ?></p>
				</div>
				<?php echo self::number( PeopleSpecPanel::FIELD_MIN, 'Mínimo de personas', $v, 'min', 'Por debajo, la solicitud se marca incompleta; no se rechaza.', (int) ( $p['max_people'] ?? 0 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				<?php echo self::number( PeopleSpecPanel::FIELD_MAX, 'Máximo de personas', $v, 'max', sprintf( 'Hasta %d.', (int) ( $p['max_people'] ?? 0 ) ), (int) ( $p['max_people'] ?? 0 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				<?php echo self::day_field( PeopleSpecPanel::FIELD_OPENS, 'La lista se abre el', $v, 'opens_at' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				<?php echo self::day_field( PeopleSpecPanel::FIELD_CLOSES, 'Y se cierra el', $v, 'closes_at' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				<p class="prc-campo__ayuda prc-campo--completo">Los dos días cuentan. Sin fechas, la lista se gestiona mientras se pueda modificar la solicitud; con ellas, aunque el plazo de solicitud esté cerrado.</p>
				<?php echo self::check( PeopleSpecPanel::FIELD_UNIQUE, 'Una sola persona por papel y grupo', $v, 'unique_per_group' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
				<?php echo self::check( PeopleSpecPanel::FIELD_ADMITTED, 'Solo las solicitudes admitidas gestionan su lista', $v, 'admitted_only' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
			</fieldset>
			<?php echo PanelParts::submit( 'Guardar la lista de personas' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- construido escapado. ?>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * A checkbox of the workshop panel.
	 *
	 * @param string               $name   Field name.
	 * @param string               $rotulo Label.
	 * @param array<string, mixed> $v      Values.
	 * @param string               $clave  Key in the values.
	 * @return string
	 */
	private static function check( string $name, string $rotulo, array $v, string $clave ): string {
		return '<div class="prc-campo prc-campo--completo"><div class="prc-opciones prc-opciones--vertical"><div class="prc-opcion">'
			. '<input type="checkbox" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="1"' . checked( '' !== (string) ( $v[ $clave ] ?? '' ), true, false ) . ' />'
			. '<label for="' . esc_attr( $name ) . '">' . esc_html( $rotulo ) . '</label></div></div></div>';
	}

	/**
	 * A whole number field of the workshop panel.
	 *
	 * @param string               $name   Field name.
	 * @param string               $rotulo Label.
	 * @param array<string, mixed> $v      Values.
	 * @param string               $clave  Key in the values.
	 * @param string               $ayuda  Help.
	 * @param int                  $max    Highest value.
	 * @return string
	 */
	private static function number( string $name, string $rotulo, array $v, string $clave, string $ayuda, int $max ): string {
		return '<div class="prc-campo prc-campo--cuarto"><label class="prc-campo__etiqueta" for="' . esc_attr( $name ) . '">' . esc_html( $rotulo ) . '</label>'
			. '<input class="prc-control" type="number" min="0" max="' . esc_attr( (string) $max ) . '" step="1" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '"'
			. ' value="' . esc_attr( (string) ( $v[ $clave ] ?? '' ) ) . '" aria-describedby="' . esc_attr( $name . '-ayuda' ) . '" />'
			. '<p class="prc-campo__ayuda" id="' . esc_attr( $name . '-ayuda' ) . '">' . esc_html( $ayuda ) . '</p></div>';
	}

	/**
	 * A day field of the workshop panel.
	 *
	 * @param string               $name   Field name.
	 * @param string               $rotulo Label.
	 * @param array<string, mixed> $v      Values.
	 * @param string               $clave  Key in the values.
	 * @return string
	 */
	private static function day_field( string $name, string $rotulo, array $v, string $clave ): string {
		return '<div class="prc-campo prc-campo--cuarto"><label class="prc-campo__etiqueta" for="' . esc_attr( $name ) . '">' . esc_html( $rotulo ) . '</label>'
			. '<input class="prc-control" type="date" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( (string) ( $v[ $clave ] ?? '' ) ) . '" /></div>';
	}
}
