---
id: PLAN-0003
title: "Lo que falta para sustituir al sistema anterior: nueve PR apilados"
status: Propuesta
date: 2026-11-10
related:
  issues: []
  prs: []
  adrs: [ADR-0005, ADR-0007, ADR-0018, ADR-0019, ADR-0021, ADR-0023, ADR-0029]
  sdds: [SDD-0001]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-fable-5-2"
---

# PLAN-0003 — Lo que falta para sustituir al sistema anterior: nueve PR apilados

> **Para quien lo ejecute con agentes:** un subagente por PR, en orden, con
> `superpowers:subagent-driven-development`. Los pasos llevan casilla
> (`- [ ]`) para seguir el avance. Cómo se reparte, en la §3.

**Objetivo:** cerrar las carencias que impiden retirar el gestor de
formularios anterior: correos, exportación a medida, tipos de pregunta,
personas de la solicitud, atributos del centro, tabla de solicitudes, consulta
de solo lectura, duplicado y actuaciones sobre una solicitud.

**Arquitectura:** nueve PR pequeños, cada uno apilado sobre el anterior y el
primero sobre la rama que reúne la pila anterior, pendiente de fusionar en
`main`. Ninguno añade una capa: son
clases `final` con métodos `static` en `src/Prc/`, dadas de alta en
`load-order.php`. Los módulos se hablan por acciones de WordPress
(`do_action`), de modo que el correo no sabe de pantallas ni las pantallas de
correo.

**Pila técnica:** PHP sobre WordPress, PHPUnit (`make test`), Vitest y
Playwright donde se toque JavaScript, bundle para Code Snippets (`make bundle`).

**Diseño de referencia:**
[SDD-0001](../sdd/SDD-0001-arquitectura-del-aplicativo-de-procedimientos.md) y
[REQ-0001](../requisitos/REQ-0001-aplicativo-de-procedimientos.md). De dónde
sale la lista: una revisión de las peticiones recibidas durante un trimestre
de uso del sistema anterior; el material está en `.local/` (ADR-0009).

## 1. Restricciones que valen para todos los PR

- Código solo en `src/Prc/`; fichero nuevo, línea nueva en `load-order.php`.
- Nada de `declare(strict_types=1)`; WPCS; identificadores en inglés y textos
  en castellano (AGENTS.md, «Idiomas»).
- Toda escritura por POST con nonce y capacidad, «POST → redirect → GET» con
  `?aviso=`. Borrar es enviar a la papelera (ADR-0007).
- Todo «puede o no puede» pasa por `Access/ProcedureAccess`, y se protege en
  `map_meta_cap`, no solo escondiendo.
- Nada de ninguna organización concreta: ni nombres de programas, ni de las
  plataformas de una instalación, ni direcciones (`make check-public`).
- Cada PR: prueba primero, `make check` en verde, `make bundle` y
  `make snippet-check`, CHANGELOG, y ADR nueva si la tabla de la §2 la pide.
- Los PR no se fusionan desde aquí: los revisa otra persona.

## 2. La pila

| # | Rama | Qué entrega | ADR |
|---|------|-------------|-----|
| 1 | `feature/app-mailer` | Correos genéricos del aplicativo | 0038 |
| 2 | `feature/export-columns-moodle` | Exportar eligiendo columnas (CSV) y fichero de alta de usuarios | 0039 |
| 3 | `feature/question-types` | Preguntas de número, fecha, rejilla y opciones con puntos | 0040 |
| 4 | `feature/application-people` | Personas de la solicitud, gestionadas por quien solicita | 0041 |
| 5 | `feature/centre-attributes` | Titularidad y zona en el catálogo; quién puede solicitar | 0042 |
| 6 | `feature/applications-table` | Filtros, paginación, revisión en bloque e incompletas | — |
| 7 | `feature/observers` | Consulta de solo lectura acotada por zona | 0043 |
| 8 | `feature/duplicate-procedure` | Duplicar un procedimiento | — |
| 9 | `feature/application-actions` | Retirar, presentar en nombre de un centro, admitir fuera de plazo, historial | 0044 |

El orden lo fijan las dependencias: 1 y 2 no dependen entre sí, pero 4
dispara correos de 1 y añade filas a 2; 6 y 7 filtran por los atributos de 5;
9 escribe en el historial que 4 inaugura.

### PR 1 — Correos genéricos (`feature/app-mailer`)

**Ficheros:**
- Crear `src/Prc/Notifications/Mailer.php`, `src/Prc/Notifications/Messages.php`.
- Modificar `PublicFront/Applications.php` (emitir acciones),
  `PublicFront/ApplicationFiles.php` (entrega), `Meta/ProcedureMetaKeys.php`,
  `PublicFront/ProcedureEditor.php` y su vista (panel «Avisos»),
  `Admin/Settings.php` (remitente), `App.php`, `load-order.php`.
- Pruebas: `tests/unit/test-mailer.php`, `tests/unit/test-messages.php`.

**Interfaces que produce:**
- `Mailer::mail( string $event, string $to, string $subject, string $message, array $ctx = array() ): bool`
  — única salida de correo. Pasa por el filtro `prc_mail` (recibe
  `to`, `subject`, `message`, `headers`; devolver `false` lo descarta).
- `Mailer::send( string $event, array $addresses, array $ctx ): int` — quita
  duplicados sin distinguir mayúsculas y devuelve los envíos correctos.
- `Messages::render( string $event, array $ctx ): array{subject:string, message:string}`
  — texto plano, asunto con prefijo `[Procedimientos]` (filtro
  `prc_mail_subject_prefix`).
- Acciones: `prc_procedure_created( int $id )`,
  `prc_application_saved( int $id, bool $created, int $user_id )`,
  `prc_application_reviewed( int $id, string $state, string $previous, string $previous_note )`,
  `prc_delivery_saved( int $id, string $question_key, int $user_id )`.
  `$user_id` es la cuenta que actúa: la solicitud de un centro es del centro,
  y el aviso va a quien guarda o entrega, no a quien la presentó.
- Meta `prc_notify` del procedimiento: lista de eventos **apagados**; vacía
  significa todos encendidos.

**Eventos y destinatarios (los correos genéricos):**

| Evento | Cuándo | A quién |
|---|---|---|
| `application_submitted` | se crea la solicitud | quien la presenta: acuse con el resumen de lo presentado y el enlace |
| `application_updated` | se guarda una ya existente | la cuenta que la guarda |
| `application_received` | las dos anteriores | correos de contacto del procedimiento (`prc_contact_emails`) |
| `review_changed` | cambia el estado de revisión, o la nota de un estado que la exige | la cuenta autora, si todavía puede ver la solicitud; con la nota si es «a subsanar» o «excluida» |
| `delivery_received` | se entrega un documento con plazo propio | la cuenta que lo entrega y contactos |
| `procedure_created` | se crea un procedimiento en borrador | contactos del procedimiento y quienes gestionan sus ámbitos: qué falta por completar, los dos pasos (completar los datos; el día de la publicación, poner fechas y enlazar la resolución) y los enlaces al taller y a «Gestión» |

**Remitente y copias: son configuración, no código** (ADR-0009). En
`Admin/Settings` cuatro opciones, vacías por defecto: `prc_mail_from_name`,
`prc_mail_from`, `prc_mail_reply_to` y `prc_mail_copy` (dirección que recibe
copia de todo `procedure_created` y `application_received`). Con
`prc_mail_from` vacío no se toca el remitente de WordPress. Una quinta,
`prc_mail_created_extra`, es un párrafo libre que se añade al correo de
`procedure_created`: ahí van las instrucciones propias de cada instalación.

- [ ] Prueba: `application_submitted` lleva título del procedimiento, centro,
      fecha y una línea por respuesta con `ProcedureQuestions::as_text()`;
      **no** lleva claves internas ni identificadores numéricos.
- [ ] Prueba: un evento listado en `prc_notify` no envía; el filtro `prc_mail`
      devolviendo `false` tampoco; `wp_mail()` fallando devuelve `false` y la
      solicitud queda guardada igual.
- [ ] Prueba: `review_changed` no se envía si el estado no cambia.
- [ ] Prueba: dos contactos con la misma dirección en distinta caja reciben uno.
- [ ] Prueba: `procedure_created` sale una vez al crear el borrador y no al
      guardarlo después; incluye `prc_mail_created_extra` si no está vacío.
- [ ] Prueba: con `prc_mail_from` y `prc_mail_reply_to` puestos, las cabeceras
      `From` y `Reply-To` los llevan; vacíos, no se añade ninguna.
- [ ] Implementar, panel «Avisos» (una casilla por evento) y remitente en ajustes.
- [ ] ADR-0038: una sola salida de correo, texto plano, por qué acciones y no
      llamadas directas. Marca el pendiente «Correos» de la SDD.

### PR 2 — Exportar a medida (`feature/export-columns-moodle`)

**Ficheros:**
- Crear `src/Prc/PublicFront/ApplicationExport.php`.
- Modificar `Applications.php` (el CSV pasa a delegarse), `ProcedureEditor.php`
  (`OP_EXPORT` acepta columnas y formato), `View/ProcedureEditorView.php`
  (diálogo de exportación), `Domain/ApplicationInput.php` y
  `View/ApplyFormView.php` (apellidos de la coordinación).
- Pruebas: `tests/unit/test-application-export.php`; ampliar
  `test-applications-csv.php` y `test-application-input.php`.

**Interfaces que produce:**
- `ApplicationExport::groups( int $procedure_id ): array<string, array<string,string>>`
  — columnas disponibles agrupadas: solicitud, centro, quien presenta,
  coordinación, revisión, preguntas.
- `ApplicationExport::csv( int $procedure_id, array $columns, string $shape ): string`
  — `$shape` es `application` (una fila por solicitud) o `person` (una por
  persona). Mismo formato de hoy: `;`, BOM, CRLF, celdas neutralizadas.
- `ApplicationExport::moodle( int $procedure_id, array $options ): string`
  — `$options['who']`: claves de `who()`; vacío, todas.
- `ApplicationExport::who(): array<string,string>` — fuentes de personas del
  fichero de usuarios (`coordinators`, `applicant`). **Punto de extensión del
  PR 4**: sus personas entran aquí y en el `switch` privado `from_source()`.
- `ApplicationExport::people( int $procedure_id, array $who = array() ): array{people:array, left_out:array{email:int, incomplete:int, conflict:int}}`
  — las personas sin repetir `username` y, aparte, cuántas se quedan fuera
  por no tener correo válido, por estar incompletas (sin nombre, apellidos o
  usuario) o por compartir usuario con otra de distinto correo.
- `ApplicationExport::who_counts( array $applications, array $who = array() ): array<string, array{count:int, email:int, incomplete:int, conflict:int}>`
  — lo mismo por fuente y en una sola pasada sobre las solicitudes ya
  cargadas: es lo que el diálogo cuenta antes de descargar.
  `Applications::rows( int $procedure_id, ?array $applications = null )`
  acepta las solicitudes ya leídas para no leerlas dos veces.
- `ApplicationExport::sanitize_choice()`, `choice()` y `remember()` — la
  elección del diálogo, validada, en la meta de usuario `prc_export_{id}`
  (`{columns, shape, format, who}`; `columns` vacío es «todas»).
- Coordinación con `surname` además de `name` (obligatorio en el formulario;
  `ApplicationMetaRegistration::full_name()` los junta para leer). Lo ya
  guardado, sin `surname`, se parte por el primer espacio al exportar y al
  abrirlo en el formulario (`ApplicationMetaRegistration::split_name()` y
  `with_surname()`).
- `prc_export_{id}` se borra de todas las personas al borrar el
  procedimiento para siempre (`ApplicationExport::on_delete()`).
- Campos del diálogo en `ProcedureEditor`: `FIELD_EXPORT_ALL`,
  `FIELD_EXPORT_COLUMNS`, `FIELD_EXPORT_SHAPE`, `FIELD_EXPORT_FORMAT` y
  `FIELD_EXPORT_WHO`.

**Decisiones:**
- El botón «Exportar» abre un diálogo: casillas por grupo, «marcar todo»,
  forma y formato. La última elección se recuerda por persona y procedimiento
  (meta de usuario `prc_export_{id}`). Sin JavaScript, el diálogo es un
  desplegable con el mismo formulario, y «Todas las columnas» es un campo que
  lee el servidor.
- Sin columnas elegidas se exportan todas: el comportamiento de hoy no cambia.
- Formato de alta de usuarios: exactamente la cabecera
  `username,firstname,lastname,email`, coma, UTF-8 sin BOM, LF y sin salto
  tras la última fila. Una fila por persona, sin duplicar `username`. El
  diálogo solo pregunta quiénes: coordinación, quien presenta o las personas
  del PR 4.
- `username` es el usuario corporativo: el `user_login` de quien presenta y,
  para quien se teclea en un formulario, la parte local de su correo en
  minúsculas. Filtro `prc_export_username( string $username, array $person )`.

- [ ] Prueba: columnas `code` y `q3` devuelven solo esas dos, en ese orden.
- [ ] Prueba: una clave que no existe se ignora; ninguna válida exporta todas.
- [ ] Prueba: forma `person` con tres coordinadoras da tres filas que repiten
      las columnas de la solicitud.
- [ ] Prueba: formato de usuarios con la misma persona en dos solicitudes da
      una fila; una persona sin correo válido no sale y se cuenta en el aviso.
- [ ] Prueba: un nombre que empieza por `=` sale neutralizado en los dos formatos.
- [ ] Implementar y ADR-0039 (por qué el formato vive aquí y no en un snippet).

### PR 3 — Tipos de pregunta (`feature/question-types`)

**Ficheros:**
- Crear `Domain/QuestionGrid.php`, `Domain/QuestionPoints.php`,
  `Domain/QuestionChanges.php` (puros) y `View/MeasuredQuestionView.php`:
  sin ellos `ProcedureQuestions` y `ApplyFormView` pasan del umbral de
  complejidad de PHPMD.
- Modificar `Domain/ProcedureQuestions.php`, `View/ProcedureEditorView.php`,
  `ProcedureEditor.php`, `View/ApplyFormView.php`, `Applications.php`,
  `ApplicationExport.php` y los registros de meta.
- Pruebas en `test-procedure-questions.php`, `test-apply-form.php`,
  `test-procedure-editor.php`, `test-application-export.php`,
  `test-mailer.php` y `test-meta-registration.php`.

**Interfaces que produce:**
- `ProcedureQuestions::TYPE_NUMBER`, `TYPE_DATE`, `TYPE_GRID`, `MAX = 40`,
  `MAX_ROWS = 30`, `ProcedureQuestions::integer( $value ): ?int`,
  `::highest_key( array $keys ): int` y `::is_answer( $value ): bool`.
- `ProcedureQuestions::sanitize( $raw, int &$seq = 0 ): array` — `$seq` es el
  contador de preguntas del procedimiento: las claves nuevas salen detrás y
  se devuelve actualizado. Meta `prc_questions_seq` (entero, sin REST); el
  taller la lee o, en un procedimiento de antes, la deduce de sus preguntas
  y de las claves de sus respuestas.
- Pregunta `number`: `min`, `max` (`int|null`); `grid`: `rows`
  (`{key:'r1', label}`) y `row_seq`; `multiple` con puntos: `points`
  (`int[]`, uno por opción). Respuestas: `number` entero o `''`; `date`
  `'Y-m-d'` o `''`; `grid` `{ r1: 3, r2: '' }`.
- `QuestionGrid::match_rows( string $text, array $previous ): array`,
  `::columns( array $question ): array` (`q4:r1` => «Pregunta — Fila»),
  `::cells( array $question, $answer ): array` y
  `::expand( array $columns, array $questions ): array`, que abre cada
  rejilla de `Applications::columns()` en sus filas para la exportación.
- `QuestionPoints::score( array $questions, array $answers ): ?int` —
  `null` sin puntos; `::has_points()`, `::lines()`, `::heading()`,
  `::cell()`. Los puntos solo se leen en el texto del taller; una lista
  guardada sin `points` se toma tal cual.
- `QuestionChanges::move( array $questions, string $key, string $direction ): array`,
  `::refusal( array $before, array $after, array $answered ): string` y
  `::input_errors( array $raw ): string` (puntos o rango mal escritos).
- `Applications::answered_keys( int $procedure_id, bool $empty_too = false ): array<string,bool>`;
  columna calculada `score` en `Applications::columns()` y `row()`.
- Operación `ProcedureEditor::OP_MOVE_QUESTION` con el campo
  `FIELD_Q_MOVE` (`q3:up`, `q3:down`); aviso `ordenadas`.

**Decisiones:**
- Tipos nuevos: `number` (entero, con `min` y `max` opcionales), `date`
  (`Y-m-d`) y `grid` (una cantidad entera por fila; las filas, una por línea,
  hasta 30).
- `multiple` admite puntos: una opción escrita `Texto | 3` vale 3. La
  solicitud gana la columna calculada `score`; no se guarda.
- Reordenar con botones «subir» y «bajar»; la `key` no cambia.
- `MAX` sube de 20 a 40.
- Cambiar el tipo de una pregunta con respuestas se rechaza con aviso.

- [ ] Prueba: `number` rechaza `1,5`, `abc` y fuera de rango; acepta `0`.
- [ ] Prueba: `grid` obligatoria exige todas las filas; cada fila sale como
      columna propia `q4:fila` en la exportación.
- [ ] Prueba: `score` suma solo las opciones marcadas; una opción sin puntos vale 0.
- [ ] Prueba: reordenar no altera las respuestas guardadas.
- [ ] Implementar y ADR-0040 (adenda de alcance a la ADR-0019).

### PR 4 — Personas de la solicitud (`feature/application-people`)

**Ficheros:**
- Crear `Domain/PeopleSpec.php` y `Domain/ApplicationPeople.php` (puros),
  `PublicFront/ApplicationLog.php`, `PublicFront/People.php` (la lista,
  escrita de fila en fila), `PublicFront/PeopleForm.php` (la sección de la
  solicitud y su POST), `PublicFront/PeopleSpecPanel.php` (el panel del
  taller), `PublicFront/PeopleExport.php` (columnas y filas de la
  exportación) y `PublicFront/View/PeopleView.php`: sin ellos `ApplyForm`,
  `ProcedureEditor`, `ApplicationExport` y sus vistas pasan más del umbral
  de PHPMD.
- Modificar `Meta/*MetaKeys.php` y registros, `ApplyForm.php` y su vista,
  `ProcedureEditor.php` y su vista (panel «Personas» y revisión),
  `MyCentre.php` y su vista, `ProcedureAccess.php`, `ApplicationExport.php`,
  `Notifications/Mailer.php` y `Messages.php`, `App.php`, `load-order.php`.
- Pruebas: `test-application-people.php` (dominio), `test-application-log.php`,
  `test-people-list.php` (guardián, ventana, concurrencia, historial y
  correo), `test-people-form.php`, `test-people-panel.php`; ampliar
  `test-application-export.php` y `test-procedure-editor.php`.

**Interfaces que produce:**
- Meta `prc_people_spec` del procedimiento (`ProcedureMetaKeys::PEOPLE_SPEC`,
  sin REST): `{enabled, roles:[{key:'r1', label, needs_group}], role_seq,
  groups:[{key:'g1', label}], group_seq, min, max, unique_per_group, opens_at,
  closes_at, admitted_only}`; `PeopleSpec::MAX_ROLES = 10`, `MAX_GROUPS = 20`,
  `MAX_PEOPLE = 200` (`max` 0 o vacío es 200).
- Meta `prc_people` de la solicitud (`ApplicationMetaKeys::PEOPLE`, sin REST,
  `auth_callback` falso): filas `{id, name, surname, dni, email, phone, role,
  group}`; `id` de 12 caracteres hexadecimales, estable.
- Meta `prc_log` de la solicitud (`ApplicationMetaKeys::LOG`): entradas
  `{at:'Y-m-d\TH:i:s\Z', user:int, event, data}`, tope `ApplicationLog::MAX = 500`.
- `PeopleSpec::sanitize( $raw ): array`,
  `::from_input( array $raw, array $previous ): array{spec, error}`,
  `::refusal( array $before, array $after, array $used ): string`,
  `::why_closed( array $spec, array $ctx ): string` (`$ctx`: `today`,
  `archived`, `teacher`, `review`, `state`),
  `::application_editable( string $state, string $review ): bool`,
  `::label( array $spec, string $which, string $key ): string`,
  `::roles_text()`, `::groups_text()`.
- `ApplicationPeople::validate( array $spec, array $people, array $row ): array<string,string>`
  — errores por clave (`name`, `surname`, `dni`, `email`, `phone`, `role`,
  `group`, `duplicate`, `unique_group`, `max`); `::is_complete( array $spec, array $people ): bool`;
  `::row()`, `::rows()`, `::fingerprint( array $row ): string`,
  `::describe( array $spec, array $row ): string` (sin DNI) y `::masked_dni()`.
- `ProcedureAccess::can_edit_people( int $user_id, int $application_id ): bool`,
  `::why_not_people( int $user_id, int $application_id ): string` y la meta
  capacidad `ProcedureAccess::CAP_EDIT_PEOPLE` (`prc_edit_people`), resuelta en
  `map_meta_cap`.
- `MetaSwap::change( int $post_id, string $key, callable $change, int $tries = 2 ): array`
  — comparar y cambiar sobre una meta (`update_post_meta()` con el valor
  leído, `add_post_meta( …, true )` sin meta); la usan la lista y el historial.
- `People::add( int $application_id, int $user_id, array $raw ): array{ok, error, errors, changed, stale}`,
  `::change( …, string $id, string $huella, array $raw )`,
  `::remove( …, string $id, string $huella )`, `::of( int $application_id ): array`,
  `::spec( int $procedure_id ): array`, `::enabled( int $procedure_id ): bool`,
  `::used_keys( int $procedure_id ): array{roles, groups}`. Constantes
  `People::ADDED`, `CHANGED`, `REMOVED`.
- Acción `prc_people_changed( int $id, string $change, array $before, array $after, int $user_id )`
  y evento de correo `Mailer::EVENT_PEOPLE_CHANGED` (`people_changed`), solo a
  los contactos, apagable en «Avisos».
- `ApplicationLog::add( int $application_id, string $event, array $data, int $user_id = 0 ): bool`
  y `ApplicationLog::all( int $application_id ): array` — quién, cuándo y qué.
  Al pasar del tope, la primera entrada es `ApplicationLog::TRIMMED`
  (`log_trimmed`, `data.dropped`); `ApplicationLog::append()` es puro.
  Eventos de las personas: `person_added`, `person_changed`, `person_removed`
  con `{before, after}`.
- `PeopleForm::model( array $apply ): array`, `::review( int $application_id, bool $history = false ): array`,
  `::nonce_action( string $op, int $application_id ): string` (`prc_people_{op}_{id}`),
  `::url( int $application_id, array $args = array() ): string` (con el ancla
  `#prc-personas`); campos `FIELD_OP`, `FIELD_APPLICATION`, `FIELD_ROW`,
  `FIELD_PRINT`, `FIELD_PERSON`; aviso `persona-sin-cambios` para un cambio
  que no cambia nada.
- `ProcedureEditor::PANEL_PEOPLE` (`personas`) y `OP_PEOPLE_SPEC`; aviso
  `personas`; `PeopleSpecPanel::save( int $procedure_id ): string`,
  `::model()`, `::reviews( array $applications ): array` y
  `PeopleSpecPanel::ARG_HISTORY` (`historial`): el historial solo se lee para
  la solicitud que lo pide.
- Exportación: `ApplicationExport::WHO_PEOPLE` (`people`), columnas
  `person_name`, `person_surname`, `person_dni`, `person_email`,
  `person_phone`, `person_role`, `person_group` en el grupo `people`;
  `ApplicationExport::who( int $procedure_id = 0 )` y
  `::group_labels( int $procedure_id = 0 )` dejan fuera la lista cuando el
  procedimiento no la pide. Por persona, cada grupo da filas solo si se
  exporta alguna de sus columnas. `Applications::csv_lines( $columns, $rows, $verbatim = array() )`
  deja sin neutralizar las columnas de `PeopleExport::VERBATIM` (el teléfono)
  cuando casan con su patrón. «Mi centro» gana `people_url` y `people_count`.

**Decisiones:**
- La ventana de personas es propia: se puede dar de alta, cambiar o dar de
  baja con el plazo de solicitud cerrado. Es lo que hoy obliga a reabrir.
- Tocar personas **no** devuelve la solicitud a «presentada».
- Sustituir es editar la fila; el historial guarda el antes y el después.
- Con `groups`, una fila con rol que lo exige no se guarda sin grupo; con
  `unique_per_group`, dos personas no comparten rol y grupo.
- La coordinación de hoy (`prc_coordinator`) se queda como está: los
  procedimientos sencillos no necesitan la lista.
- Sin fechas, la ventana es la de la solicitud. Histórico, excluida o
  profesorado: nunca.
- Dos cuentas del mismo centro: cada operación relee la lista al escribir, y
  un cambio o una baja sobre una fila que cambió desde que se pintó se
  rechaza con aviso (huella en un campo oculto).
- Quitar un papel o un grupo que alguna solicitud usa se rechaza; renombrarlo,
  no, porque la clave no cambia.
- El correo `people_changed` va solo a los contactos y sin DNI; el historial
  enseña el DNI tapado.

- [x] Prueba: misma persona (DNI) dos veces con el mismo rol y grupo, rechazada.
- [x] Prueba: fuera de la ventana no se escribe, tampoco por POST directo.
- [x] Prueba: con `admitted_only`, una solicitud «presentada» no puede.
- [x] Prueba: alta, cambio y baja dejan tres entradas en el historial y un
      correo cada una.
- [x] Prueba: por debajo de `min` la solicitud se marca incompleta, no se rechaza.
- [x] Implementar y ADR-0041 (por qué no es un tipo de contenido y qué cambia
      respecto a las ADR-0019 y ADR-0023).

### PR 5 — Atributos del centro y elegibilidad (`feature/centre-attributes`)

**Ficheros:**
- Crear `Domain/CentreEligibility.php` (puro): los acotados y su motivo.
- Modificar `Centre/CentreCatalogue.php`, `Centre/CentreCatalogueSync.php`,
  `Domain/CentreCatalog.php`, `Domain/ProcedureInput.php`,
  `Access/ProcedureAccess.php`, `Meta/ProcedureMetaKeys.php` y registro,
  `ProcedureEditor.php` y su vista (sección «Qué centros pueden solicitar» y
  diálogo de exportación), `ApplyForm.php` (el motivo), `Applications.php`,
  `PublicFront/CentreColumns.php` (nuevo: las columnas del catálogo, aparte
  para que `Applications` y `ApplicationExport` no pasen del umbral de PHPMD),
  `ApplicationExport.php`, `PeopleExport.php`, `Admin/Settings.php`,
  `scripts/mu-plugins/prc-dev-tools.php`, `load-order.php`.
- Pruebas: `test-centre-eligibility.php` (dominio); ampliar
  `test-centre-catalog.php`, `test-centres.php`, `test-procedure-access.php`,
  `test-rural-groups.php`, `test-apply-form.php`, `test-procedure-editor.php`,
  `test-application-export.php`, `test-meta-registration.php` y
  `test-rest-authorization.php`.

**Interfaces que produce:**
- Catálogo: `CentreCatalog::all()` / `find()` sirven `{code, name, ownership,
  kind, island, municipality, type, zone, zone_name, active}`; `zone` es el
  código de otro centro del catálogo y `zone_name` su nombre, vacío si no
  está. `CentreCatalog::index(): array<string, array>` (por código, una
  pasada) y `CentreCatalog::values( string $field ): string[]` (valores
  distintos de `type`, `island`…), `CentreCatalog::choices( bool $wanted, array $types, array $islands )`
  (lo que ofrece el taller). `CentreCatalog::private_types(): string[]`
  y la opción `CentreCatalog::OPTION_PRIVATE_TYPES` (`prc_private_centre_types`).
- La sincronización guarda `zone` (campo `CentreCatalogueSync::SOURCE_ZONE_FIELD`
  de la fuente, 8 cifras o vacío) y `ownership` si viene;
  `CentreCatalogueSync::record( array $item, string $code, string $name, bool $is_active ): array`.
- Filtros: `prc_private_centre_types` (lista de tipos privados, sin distinguir
  mayúsculas; por defecto la opción, vacía) y `prc_zone_label` (rótulo de la
  zona, «Zona» por defecto; `CentreColumns::zone_label()`).
- Metas del procedimiento: `ProcedureMetaKeys::CENTRE_TYPES`
  (`prc_centre_types`), `ISLANDS` (`prc_islands`) y `ALLOWED_CENTRES`
  (`prc_allowed_centres`), arrays sin REST; vacías, sin acotado.
- `CentreEligibility::rules( array $raw ): array{ownership, types, islands, codes}`,
  `::restricted( array $rules ): bool`, `::why_not( array $rules, string $code, array $centre ): string`,
  `::parse_codes( $raw ): array{codes, invalid}`, `::codes( $raw )`,
  `::values( $raw )`, `::input( array $raw, bool $schools )`, `MAX_CODES = 2000`.
- `CentreEligibility::why_not( array $rules, string $code, array $centre, bool $catalogue = true )`
  y `CentreEligibility::warnings( array $rules, bool $empty_catalogue, bool $no_private ): array{ownership?, catalogue?}`;
  `CentreCatalog::flush()`, `CentreCatalog::register()`;
  `Applications::rows( int $procedure_id, ?array $applications = null, bool $centre = false )`
  (sin `$centre` no lee el catálogo); `CentreColumns::index_for( array $applications, bool $wanted = true )`.
- Guardián: `ProcedureAccess::eligibility( int $post_id ): array`,
  `::why_not_code( int $post_id, string $code, string $kind = 'school' ): string` y
  `::why_not_eligible( int $user_id, int $post_id, string $code = '' ): string`;
  `apply_codes()` / `can_apply()` los aplican.
- **Leer los atributos de una solicitud** (para los PR 6 y 7):
  `CentreColumns::attributes( string $code, ?array $index = null ): array{island, municipality, type, ownership, zone, zone_name}`
  con el `prc_centre_code` de la solicitud; todo vacío si el código no está
  en el catálogo o es de profesorado. Para muchas a la vez, pasar
  `CentreCatalog::index()` una vez. `Applications::row()` ya trae las celdas
  `island`, `municipality`, `centre_type`, `ownership` (rótulo), `zone`
  (nombre) y `zone_code`, y `Applications::rows()` lee el catálogo una sola vez.
- Exportación: `CentreColumns::KEYS`, `CentreColumns::columns()`,
  `CentreColumns::cells( string $code, ?array $index )`, `CentreColumns::ordered()`,
  `CentreColumns::all_plus()`, `CentreColumns::index_for( array $applications )`,
  `Applications::columns( array $questions, bool $centre = false )` y
  `PeopleExport::all_columns( int $procedure_id, bool $centre = false )`: las
  seis van en el grupo `centre` de `groups()` pero fuera del CSV de siempre.
- `CentreEligibility::form_values( array $rules )`: los acotados como los
  pinta el taller (la lista cerrada, un código por línea).
- `Settings::CENTRES_GROUP` y `Settings::sanitize_private_types()`.

**Decisiones:**
- La titularidad se deriva del tipo; una explícita manda. Sin configurar, todo
  es público.
- Un centro que no está en el catálogo no crea solicitud en un procedimiento
  acotado por titularidad (distinta de «todas»), tipos o islas; sin acotados,
  sí. Con la lista cerrada como único acotado, estar en ella basta aunque no
  esté en el catálogo; con lista y otros acotados, hace falta el catálogo. Con
  el catálogo entero vacío, la titularidad sola no cierra. La lista no
  distingue mayúsculas. Una agrupación rural se mide por su propio código, y
  la titularidad no la filtra.
- La elegibilidad se mira al crear: quien ya presentó su solicitud la sigue
  editando o subsanando aunque el procedimiento se acote después
  (`apply_codes()` admite el código que ya tiene solicitud).
- El taller avisa del catálogo vacío y de una sola titularidad sin tipos
  privados (`CentreEligibility::warnings()`); los ajustes cuentan los centros
  que sirve el dominio y los procedimientos publicados que dependen de lo que
  falta (`Settings::dependents()`).
- `CentreCatalog::all()`, `find()` e `index()` se construyen una vez por
  petición; `CentreCatalog::flush()` lo vacía, y se llama al sincronizar y al
  cambiar la opción del catálogo o la de los tipos privados.
- Las seis columnas solo salen si se eligen: «Todas las columnas» y el CSV sin
  columnas son el de siempre, byte a byte; con «Todas» marcada, las del
  catálogo que se marquen se suman. En el diálogo van con
  `data-prc-export-extra`, fuera del «marcar todo» del guion.

- [x] **Comprobar antes de tocar**: la prueba que sincroniza un centro privado
      y lee su titularidad desde el dominio **falló**: salía «pública». Era un
      defecto y se arregla aquí.
- [x] El catálogo guarda y sirve `zone`: el código que la fuente trae en el
      campo `cep` de cada centro, que es a su vez un centro del catálogo y de
      ahí sale su nombre. El rótulo de «zona» sale del filtro `prc_zone_label`.
- [x] La fuente no trae titularidad: se deriva del tipo de centro con una
      lista de tipos privados que contesta el filtro `prc_private_centre_types`.
- [x] El procedimiento puede acotar por tipo de centro, por isla y por lista
      cerrada de códigos (`prc_allowed_centres`). Un centro que no está en el
      catálogo **no** crea solicitud en un procedimiento acotado por
      titularidad, tipo o isla (hoy sí); con la lista sola, basta estar en
      ella.
- [x] La exportación gana las columnas isla, municipio, tipo, titularidad y zona.
- [x] ADR-0042.

### PR 6 — Tabla de solicitudes (`feature/applications-table`)

**Ficheros:**
- Crear `Domain/ApplicationFilter.php` y `Domain/ApplicationCompleteness.php`
  (puros), `PublicFront/ApplicationQuery.php` (la consulta),
  `PublicFront/ApplicationsPanel.php` (el modelo del panel y la revisión en
  bloque), `PublicFront/View/ApplicationsPanelView.php` y
  `PublicFront/View/ExportDialogView.php`: el panel sale de
  `ProcedureEditor` y de `ProcedureEditorView`, que ya estaban en el umbral
  de PHPMD.
- Modificar `ProcedureEditor.php` (`OP_BULK_REVIEW`, el filtro de vuelta y la
  exportación acotada), `ApplicationExport.php` y `PeopleExport.php` (`ids` y
  metas ya leídas), `PeopleSpecPanel.php` y `PeopleForm.php` (el enlace al
  historial conserva el filtro), `assets/js/prc-app.js` y
  `assets/css/prc-alta.css`.
- Pruebas: `test-application-filter.php`, `test-application-completeness.php`
  (dominio), `test-applications-table.php` (consulta, panel, bloque,
  exportación y número de consultas), `tests/js/prc-app.test.js` y
  `tests/browser/confirmacion.js`.

**Interfaces que produce** (el PR 7 reutiliza filtro, páginas y exportación
acotada para la consulta de solo lectura):
- Parámetros GET del panel: `ApplicationFilter::ARG_REVIEW` (`revision`),
  `ARG_ISLAND` (`isla`), `ARG_ZONE` (`zona`, el código de la zona),
  `ARG_INCOMPLETE` (`incompletas=1`), `ARG_SEARCH` (`buscar`) y `ARG_PAGE`
  (`pagina`); `ApplicationFilter::PER_PAGE = 50`, `MAX_SEARCH = 100`. Lo que
  no es un valor del filtro se ignora; la isla y la zona, además, si no son de
  ninguna solicitud del procedimiento.
- `ApplicationFilter::sanitize( array $raw ): array{review, island, zone, incomplete, search, page}`,
  `::against( array $filter, array $options )`, `::active( array $filter ): bool`,
  `::query( array $filter, array $changes = array() ): array` (los argumentos
  de la URL, sin codificar; cambiar algo que no es la página vuelve a la 1),
  `::window( int $total, int $page, int $per_page = 50 ): array{page, pages, offset, from, to}`
  (una página fuera de rango cae en la última), `::matches( string $term, array $texts ): bool`
  (sin mayúsculas ni tildes; `%` y `_` son letras) y `::args()`.
- `ApplicationCompleteness::reasons( array $spec, $people, array $questions, $files, string $today, string $review ): string[]`
  («Faltan 2 personas», «Falta: Memoria final»; vacío para una excluida),
  `::overdue( array $questions, string $today )` y
  `::may_be_incomplete( array $spec, array $questions, string $today ): bool`.
- `ApplicationQuery::run( int $procedure_id, array $filter, array $scope = array() ): array{filter, active, ids, all, total, counts, incomplete, may_be_incomplete, options:{islands, zones}, posts, reasons, stored, page, pages, offset, from, to}`
  — `$scope['codes']` es el acotado adicional por códigos de centro (vacío no
  deja ver nada); contadores, opciones, filtro, página y exportación salen ya
  acotados. `ApplicationQuery::ids( int $procedure_id, array $filter, array $scope = array() ): int[]`
  y `::state_of( $state )` (vacío es «presentada»).
- `ApplicationsPanel::filter( array $source )` (de `$_GET` o de los campos
  ocultos de un POST), `::url( int $procedure_id, array $filter, array $extra = array(), array $changes = array() )`,
  `::model( int $procedure_id, int $user_id, array $questions, array $request )`,
  `::export_ids( int $procedure_id, array $post ): ?int[]`,
  `::posted_ids( array $post ): int[]`,
  `::bulk( int $procedure_id, array $ids, string $state, string $note ): array{error, done, failed}`
  y `::bulk_notice( array $request )`; `BULK_MAX = 50`.
- `Applications::review( int $application_id, string $state, ?string $note )`:
  `null` es «sin nota nueva» y conserva la que tenga (que cuenta para la
  obligatoria); la cadena vacía la vacía, como en la revisión de una en una.
- Campos POST: `ProcedureEditor::OP_BULK_REVIEW` (`bulk_review`, nonce propio),
  `ApplicationsPanel::FIELD_ROWS` (`prc_rows[]`), `FIELD_PAGE`
  (`prc_rows_page`, «marcar las de esta página»), `FIELD_PAGE_ROWS`
  (`prc_page_rows`, los ID de la página), los de siempre `FIELD_STATE` y
  `FIELD_NOTE`, y en el diálogo de exportar `FIELD_EXPORT_SCOPE`
  (`prc_export_scope`: `filter` o `all`). Cada formulario del panel lleva el
  filtro en campos ocultos con los nombres de GET. Aviso `bloque` con
  `revisadas` y `sin_cambiar`.
- `ApplicationExport::csv( …, ?array $ids = null )`, `::moodle( …, array{who, ids} )`,
  `::people( …, ?array $ids = null )`, `::who_counts( array $applications, array $who = array(), array $stored = array() )`
  (el diálogo la llama dos veces con filtro: todas y las del filtro),
  `PeopleExport::users( int $application_id, $people = null )`,
  `PeopleSpecPanel::reviews( array $applications, array $args = array() )` y
  `PeopleForm::review( int $application_id, bool $history = false, array $args = array() )`.

**Decisiones:**
- Todo el filtro por GET, sin guion. Los contadores por estado son del total
  sin filtrar y sirven de atajo; con una página y sin filtro, el recuento dice
  lo de siempre.
- Leer en dos tiempos: un censo con las solicitudes sin sus metas y, en otra
  consulta, solo las metas que deciden el filtro; luego las metas enteras de
  la página. El estado y la búsqueda se resuelven en el censo y no con
  `meta_query`: el censo ya hace falta entero para los contadores y las
  opciones, y así un `%` no es un comodín y las tildes no cuentan. Medido:
  pintar el panel con una página de 50 cuesta las mismas consultas sobre 100
  solicitudes que sobre 300 (`test-applications-table.php`).
- En bloque, las mismas reglas que de una en una; un estado que no existe, la
  nota que falta, nada marcado o más de 50 —una página— rechazan el envío
  entero. Un ID que no es una solicitud publicada de este procedimiento no se
  toca y se cuenta, sin frenar a las demás. Cada una pasa por
  `Applications::review()`: emite `prc_application_reviewed` y su correo sale
  **dentro de la misma petición**, sin cola ni cron; por eso el tope es una
  página. La nota común, escrita, sustituye la de todas; en blanco, con un
  estado que no la exige, cada una conserva la suya.
- Exportar con un filtro ofrece «las del filtro» (lo marcado) o «todas». Los
  recuentos de quién se queda fuera del fichero de usuarios se calculan para
  las dos; con guion se ve la de la opción elegida y sin guion las dos, cada
  una con su rótulo.
- Los contadores por estado son atajos sobre el total: su enlace lleva solo
  el estado. `incompletas=1` en un procedimiento donde ninguna puede estarlo
  se ignora, como una isla que no está.
- Quien no gestiona las solicitudes filtra y pasa de página; revisar, revisar
  en bloque y exportar quedan apagados.

- [x] Filtros por estado de revisión, isla, zona e «incompletas», y búsqueda
      por centro o código; todo por GET.
- [x] Paginación de 50 y contadores por estado.
- [x] Revisión en bloque: mismo estado y misma nota para las marcadas; cada
      una emite `prc_application_reviewed`.
- [x] «Incompleta» es: personas por debajo del mínimo o una entrega obligatoria
      vencida sin documento.
- [x] La exportación respeta el filtro activo.

### PR 7 — Consulta de solo lectura (`feature/observers`)

**Ficheros:**
- Crear `Access/ObserverScope.php` (la zona de la persona, sus centros del
  catálogo, si un procedimiento admite consulta y lo que nunca se enseña),
  `PublicFront/Observe.php`, `PublicFront/ObserveSettings.php` (el panel
  «Consulta» del taller, aparte para que `ProcedureEditor` no pase del umbral
  de PHPMD) y `PublicFront/View/ObserveView.php`.
- Modificar `ProcedureAccess.php`, `Shell.php` (slug `consulta-de-solicitudes`,
  shortcode `[prc_observe]`, menú y perfil), `Meta/ProcedureMetaKeys.php` y
  registro, `ProcedureEditor.php` y su vista (panel «Consulta»),
  `ApplicationsPanel.php` y su vista (paginador y piezas reutilizables),
  `ApplicationFiles.php` (descarga), `Notifications/Mailer.php` y
  `Messages.php`, `App.php`, `load-order.php`, `snippets/roles-and-profiles.php`,
  `scripts/setup-pages.php`, `scripts/seed-demo.php` y el mu-plugin.
- Pruebas: `test-observer-access.php` (guardián), `test-observe.php`
  (pantalla, exportación, documentos, panel, correo y menú); ampliar
  `test-rest-authorization.php`, `test-roles-and-profiles.php`,
  `test-provision.php`, `test-shell.php` y `test-procedure-editor.php`.

**Interfaces que produce:**
- Capacidad `ProcedureAccess::CAP_VIEW_APPLICATIONS` (`prc_view_applications`)
  y rol `prc_observer` («Consulta de solicitudes»: `read` y esa capacidad).
- Meta de usuario de la zona: `ObserverScope::META_KEY` (`prc_zone`), clave
  por el filtro `prc_zone_meta_key`; una lista o un texto con comas, varias
  zonas. Campo «Zonas de consulta» del perfil, solo administración
  (`prc_zone_meta_key()` y `prc_profile_zones()` en el snippet de roles).
- `ProcedureAccess::observer_zones( int $user_id ): string[]`,
  `::observer_codes( int $user_id ): string[]` (los centros de sus zonas,
  del catálogo de hoy),
  `::can_observe( int $user_id, int $procedure_id = 0, ?array &$scope = null ): bool`
  (con procedimiento, deja en `$scope` lo que ve: `array()` es todo y
  `array( 'codes' => … )`, esos centros, listo para `ApplicationQuery::run()`;
  sin procedimiento, si tiene alguna consulta),
  `::can_observe_application( int $user_id, int $application_id ): bool` y
  `::can_view_application( int $user_id, int $application_id, string $purpose = '', string $question_key = '' ): bool`
  (`VIEW_OWN`: solo si es suya, lo que pregunta el correo de revisión;
  `VIEW_DOCUMENT` con la pregunta: la descarga, que a quien consulta solo se
  la da `prc_observer_files` y si la pregunta está entre sus columnas). `can_read()`, `why_not_code()` y `may_use_area()`
  pasan a privados.
- `ObserverScope::zones_for( int $user_id )`, `::parse_zones( $raw )`,
  `::codes_in( array $zones, array $catalogue )`, `::is_observable( int $procedure_id )`,
  `::observers_of( string $code ): int[]`, `::allowed( array $groups )`,
  `::columns( $chosen, array $groups )`, `::is_allowed( string $column )`,
  `ALLOWED_COLUMNS` (lista cerrada: `date`, `centre`, `code`, `island`,
  `municipality`, `centre_type`, `ownership`, `zone`, `zone_code`,
  `applicant`, `email`, `position`, `coordinator`, `coordinator_email`,
  `person_name`, `person_surname`, `person_email`, `person_phone`,
  `person_role`, `person_group`, `state`, `score`, más `q\d+` y
  `q\d+:r\d+`) y `DEFAULT_GROUPS`. `observers_of()` lee solo ID y la meta de
  zona.
- Metas del procedimiento (`ProcedureMetaKeys`): `OBSERVABLE`
  (`prc_observable`), `OBSERVER_COLUMNS` (`prc_observer_columns`, saneada por
  `ProcedureMetaRegistration::sanitize_observer_columns()`), `OBSERVER_FILES`,
  `OBSERVER_EXPORT` y `OBSERVER_NOTIFY`; todas sin REST y apagadas.
- Pantalla: `Shell::SLUGS['observe']`, `Shell::SHORTCODES['observe']`,
  `Shell::zone_names( int $user_id )`; `Observe::url( int $procedure_id = 0, array $args = array() )`,
  `::model( array $request )`, `::filter( array $source, array $columns )`,
  `::csv( int $user_id, int $procedure_id, array $source ): ?string`,
  `::handle()` (POST `FIELD_DO` = `OP_EXPORT`, `FIELD_ID`, nonce
  `NONCE_NAME` con `nonce_action( $procedure_id )`) y `NOT_FOUND`.
- Taller: `ProcedureEditor::PANEL_OBSERVE` (`consulta`), `OP_OBSERVE`, aviso
  `consulta`; `ObserveSettings::model()`, `::save()`, `::handle()`,
  `::columns()`, `::offered()` y sus campos `FIELD_*`.
- `ApplicationsPanel::pager( callable $url, array $filter, int $pages )`
  público; `ApplicationsPanelView::states()`, `::select()` y `::pager()`
  públicos.
- Correo: `people_changed` se copia a `ObserverScope::observers_of()` del
  centro si el procedimiento es observable y tiene `prc_observer_notify`, con
  el enlace a la consulta (`url_label` en el contexto) y la persona descrita
  solo con las columnas `person_*` que la consulta enseña
  (`ApplicationPeople::describe( $spec, $row, ?array $fields = null )`); sin
  ninguna, sin datos de la persona. El correo de revisión va a la cuenta
  autora solo con `VIEW_OWN`.
- Guardar el perfil normaliza la meta de zona como lista.

**Decisiones:**
- Capacidad `prc_view_applications` y rol `prc_observer`. La zona de la
  persona es la meta de usuario `prc_zone`; sin zona válida, no ve nada.
- El procedimiento decide si admite consulta (`prc_observable`) y qué columnas
  y documentos enseña.
- Ve las solicitudes de los centros de su zona, sus personas y, si se
  permite, descarga sus documentos y exporta lo que ve. No edita nada.
- `people_changed` puede copiarse a quienes consultan esa zona.
- Quien gestiona el ámbito del procedimiento, y quien tiene todos, ve la
  consulta entera. Sin la columna del estado no hay contadores ni filtro por
  estado; las incompletas no se enseñan.

- [x] Prueba: una solicitud de otra zona no se ve ni por enlace directo, ni
      por REST, ni por el escritorio, ni en la exportación, ni por la
      descarga de un documento.
- [x] Prueba: sin `prc_observable` la pantalla responde como si no existiera.
- [x] Prueba: el DNI y la nota no salen ni en el HTML ni en el CSV.
- [x] Prueba: el conjunto exacto de columnas ofrecidas, y una columna nueva
      que no se ofrece ni se guarda.
- [x] Prueba: el aviso a la zona sin columnas de personas no lleva datos de
      la persona; la nota de revisión no llega a quien solo consulta.
- [x] Implementar y ADR-0043.

### PR 8 — Duplicar (`feature/duplicate-procedure`)

**Ficheros:**
- Crear `Domain/ProcedureCopy.php` (puro: qué se lleva el duplicado) y
  `PublicFront/ProcedureDuplicate.php` (la operación y su aviso): fuera de
  `ProcedureEditor`, que ya estaba en el tope de métodos de PHPMD.
- Modificar `Access/ProcedureAccess.php`, `Domain/ProcedureInput.php`,
  `ProcedureEditor.php` y su vista (botón en la cabecera, aviso de lo que
  falta para publicar), `View/WorkspaceView.php` (columna «Duplicar»),
  `Admin/Settings.php` y `load-order.php`.
- Pruebas: `test-procedure-copy.php` (dominio y clasificación de metas),
  `test-duplicate-procedure.php`; ampliar `test-procedure-editor.php` y
  `tests/browser/confirmacion.js`.

**Interfaces que produce:**
- `ProcedureCopy::policy(): array<string, string>` — cada meta del
  procedimiento con `ProcedureCopy::COPY`, `TRANSFORM` o `NEVER`;
  `::rule( string $key ): string` (`''` sin clasificar). La prueba
  `test_every_registered_procedure_meta_is_classified` recorre las metas
  registradas: **una meta nueva del procedimiento se clasifica aquí**.
- `ProcedureCopy::meta( array $source, bool $with_js ): array{meta, js_left, undated}`,
  `::questions( $raw ): array{questions, seq, map, undated}` (claves desde
  `q1`, filas desde `r1`; `map` es clave vieja => nueva, filas como
  `q4:r1`), `::people_spec( $raw )` (papeles y grupos renumerados, sin
  ventana), `::observer_columns( $raw, array $map )` y
  `::title( string $title )` (sufijo `ProcedureCopy::SUFFIX`, « (copia)»).
- `ProcedureAccess::can_duplicate( int $user_id, int $post_id, ?array &$areas = null ): bool`
  — abre el original y puede crear procedimientos; en `$areas`, los ámbitos
  del duplicado (los del original que puede poner o, si ninguno, el suyo).
- `ProcedureInput::not_ready( int $course, array $questions ): string[]` —
  lo que impide publicar: el curso y las entregas sin fechas válidas. El
  modelo del taller lo da en `not_ready` y apaga `can_publish`; el POST de
  publicar lo rechaza.
- `ProcedureEditor::OP_DUPLICATE` (`duplicate`, nonce propio
  `nonce_name( 'duplicate' )`), modelo `can_duplicate`;
  `ProcedureDuplicate::duplicate( int $procedure_id, int $user_id ): array{id, js_left}|\WP_Error`,
  `::handle()`, `::notice( array $request )`, `NOTICE` (`duplicado`),
  `ARG_SOURCE` (`origen`) y `ARG_NO_JS` (`sin_js`).
- `ProcedureEditorView::duplicate_question( string $title )`: el texto de la
  confirmación, el mismo en el taller y en «Gestión».
- `Settings::scope_diagnostics()` (antes en `ProcedureAccess`, que estaba en
  el tope de 45 públicos de PHPMD).

**Decisiones:**
- Se duplica en cualquier estado, también histórico o con el bloqueo de
  otra persona: el original no se escribe. Emite `prc_procedure_created`.
- El duplicado: borrador de quien duplica, «<título> (copia)», sin curso,
  en «Datos» con el aviso. Las entregas con plazo propio quedan sin
  fechas: el borrador se guarda, y no se publica hasta ponerlas.
- El JavaScript propio solo se copia con `can_edit_custom_js` sobre el
  original; si se queda atrás, el aviso lo dice.
- Sin ADR: no hay decisión de arquitectura nueva; la regla de clasificar
  cada meta vive en `ProcedureCopy`, en su prueba y en `AGENTS.md`.

- [x] Operación `duplicate` en el taller y en «Gestión»: copia datos,
      preguntas (con claves nuevas), compromisos, especificación de personas
      y avisos a un borrador.
- [x] No copia fechas, enlaces de resolución, solicitudes ni archivado; el
      curso queda vacío para que se elija.
- [x] Prueba: el duplicado no comparte ninguna respuesta ni documento.

### PR 9 — Actuar sobre una solicitud (`feature/application-actions`)

- [ ] Quien solicita puede retirar su solicitud mientras el plazo esté
      abierto: va a la papelera y se avisa a los contactos.
- [ ] Quien revisa puede presentar en nombre de un centro del catálogo, y
      conceder a una solicitud una prórroga con fecha (`prc_late_until`).
- [ ] Nota interna, que quien solicita no ve (`prc_internal_note`).
- [ ] Todo queda en `ApplicationLog`, con pestaña «Historial» en la revisión.
- [ ] Prueba: retirar fuera de plazo se rechaza; la prórroga vencida no deja editar.
- [ ] ADR-0044, que desarrolla lo que la ADR-0005 dejó por escribir.

## 3. Cómo se reparte

- Un subagente **por PR**, en serie: comparten un solo entorno de pruebas y
  cada rama nace de la anterior. Modelo `opus` para quien implementa y para
  quien revisa.
- Todos trabajan en el árbol principal: es el que el entorno de pruebas
  tiene montado, y un árbol aparte no se probaría.
- Ciclo de cada PR: crear la rama desde la anterior → implementar con prueba
  primero → `make check`, `make bundle`, `make snippet-check` → revisión por
  otro subagente contra este plan → corregir → `git push` y
  `gh pr create --base <rama anterior>`.
- A cada subagente se le pasa su sección, la §1, las «Interfaces que produce»
  de los PR anteriores y `AGENTS.md`. Nada más.
- Si un PR anterior cambia tras la revisión humana, los posteriores se
  rebasan en orden.

## 4. Lo que hay que mirar en la revisión

Lo más probable que falle y que ninguna prueba de arriba cubre por sí sola:

1. Un servidor de correo caído no debe impedir presentar: el aviso al usuario
   dice que se presentó aunque el acuse no saliera.
2. Un procedimiento con 300 solicitudes y 40 preguntas: la exportación y la
   tabla no cargan todo en memoria de una vez.
3. Dos personas del mismo centro editando la lista de personas a la vez: la
   segunda no pisa a la primera en silencio.
4. Nombres con comas, comillas, tildes y eñes en el fichero de usuarios.
5. Una pregunta borrada o cambiada de sitio con solicitudes ya presentadas:
   ni la exportación a medida ni el acuse se descolocan.

## 5. Decisiones tomadas

| # | Qué | Decisión (2026-11-10) |
|---|-----|-----------------------|
| D-1 | Fichero de usuarios | Cuatro columnas: `username,firstname,lastname,email`; `username` es el usuario corporativo |
| D-2 | De dónde sale la zona de cada centro | Del campo `cep` del catálogo externo (ADR-0029) |
| D-3 | Remitente de los correos (P-05 del PLAN-0001) | Opciones de ajustes; cada instalación pone el suyo |
| D-4 | Base de la pila | El primero sobre la rama que reúne la pila anterior, pendiente de fusionar en `main` |
| D-5 | Certificación de participantes | Fuera: sigue necesitando ADR propia |
