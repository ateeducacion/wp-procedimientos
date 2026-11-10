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
  `MAX_ROWS = 30` y `ProcedureQuestions::integer( $value ): ?int`.
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
  `null` sin puntos; `::has_points()`, `::lines()`.
- `QuestionChanges::move( array $questions, string $key, string $direction ): array`
  y `::refusal( array $before, array $after, array $answered ): string`.
- `Applications::answered_keys( int $procedure_id ): array<string,bool>`;
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
- Crear `src/Prc/Domain/ApplicationPeople.php` (puro),
  `src/Prc/PublicFront/ApplicationLog.php`.
- Modificar `Meta/*MetaKeys.php` y registros, `ApplyForm.php` y vista,
  `ProcedureEditor.php` y vista (panel «Personas»), `ProcedureAccess.php`,
  `ApplicationExport.php`, `Notifications/Messages.php`.
- Pruebas: `test-application-people.php`, `test-application-log.php`.

**Interfaces que produce:**
- Meta `prc_people_spec` del procedimiento: `enabled`, `roles` (hasta 10),
  `groups` (hasta 20, opcional), `min`, `max` (hasta 200),
  `unique_per_group`, `opens_at`, `closes_at`, `admitted_only`.
- Meta `prc_people` de la solicitud: filas `id`, `name`, `surname`, `dni`,
  `email`, `phone`, `role`, `group`.
- `ApplicationPeople::validate( array $spec, array $people, array $row ): array`
  — errores por clave.
- `ProcedureAccess::can_edit_people( int $user_id, int $application_id ): bool`.
- Acción `prc_people_changed( int $id, string $change, array $before, array $after )`
  y evento de correo `people_changed` a los contactos.
- `ApplicationLog::add( int $application_id, string $event, array $data ): void`
  y `ApplicationLog::all( int $application_id ): array` — quién, cuándo y qué.

**Decisiones:**
- La ventana de personas es propia: se puede dar de alta, cambiar o dar de
  baja con el plazo de solicitud cerrado. Es lo que hoy obliga a reabrir.
- Tocar personas **no** devuelve la solicitud a «presentada».
- Sustituir es editar la fila; el historial guarda el antes y el después.
- Con `groups`, una fila con rol que lo exige no se guarda sin grupo; con
  `unique_per_group`, dos personas no comparten rol y grupo.
- La coordinación de hoy (`prc_coordinator`) se queda como está: los
  procedimientos sencillos no necesitan la lista.

- [ ] Prueba: misma persona (DNI) dos veces con el mismo rol y grupo, rechazada.
- [ ] Prueba: fuera de la ventana no se escribe, tampoco por POST directo.
- [ ] Prueba: con `admitted_only`, una solicitud «presentada» no puede.
- [ ] Prueba: alta, cambio y baja dejan tres entradas en el historial y un
      correo cada una.
- [ ] Prueba: por debajo de `min` la solicitud se marca incompleta, no se rechaza.
- [ ] Implementar y ADR-0041 (por qué no es un tipo de contenido y qué cambia
      respecto a las ADR-0019 y ADR-0023).

### PR 5 — Atributos del centro y elegibilidad (`feature/centre-attributes`)

**Ficheros:** `Centre/CentreCatalogue.php`, `Centre/CentreCatalogueSync.php`,
`Domain/CentreCatalog.php`, `Access/ProcedureAccess.php`,
`Meta/ProcedureMetaKeys.php`, editor y vista; pruebas en
`test-centre-catalog.php`, `test-centres.php`, `test-procedure-access.php`.

- [ ] **Comprobar antes de tocar**: escribir la prueba que sincroniza un
      centro privado y lee su titularidad desde el dominio. Si falla, es un
      defecto y se arregla aquí; si pasa, se anota y se sigue.
- [ ] El catálogo guarda y sirve `zone`: el código que la fuente trae en el
      campo `cep` de cada centro, que es a su vez un centro del catálogo y de
      ahí sale su nombre. El rótulo de «zona» sale del filtro `prc_zone_label`.
- [ ] La fuente no trae titularidad: se deriva del tipo de centro con una
      lista de tipos privados que contesta el filtro `prc_private_centre_types`.
- [ ] El procedimiento puede acotar por tipo de centro, por isla y por lista
      cerrada de códigos (`prc_allowed_centres`). Un centro que no está en el
      catálogo **no** solicita en un procedimiento acotado (hoy sí).
- [ ] La exportación gana las columnas isla, municipio, tipo, titularidad y zona.
- [ ] ADR-0042.

### PR 6 — Tabla de solicitudes (`feature/applications-table`)

**Ficheros:** `Applications.php`, `ProcedureEditor.php`,
`View/ProcedureEditorView.php`; pruebas en `test-procedure-editor.php` y
`test-applications.php`.

- [ ] Filtros por estado de revisión, isla, zona e «incompletas», y búsqueda
      por centro o código; todo por GET.
- [ ] Paginación de 50 y contadores por estado.
- [ ] Revisión en bloque: mismo estado y misma nota para las marcadas; cada
      una emite `prc_application_reviewed`.
- [ ] «Incompleta» es: personas por debajo del mínimo o una entrega obligatoria
      vencida sin documento.
- [ ] La exportación respeta el filtro activo.

### PR 7 — Consulta de solo lectura (`feature/observers`)

**Ficheros:**
- Crear `PublicFront/Observe.php`, `PublicFront/View/ObserveView.php`.
- Modificar `Shell.php` (slug `consulta-de-solicitudes`, shortcode
  `[prc_observe]`), `ProcedureAccess.php`, `snippets/roles-and-profiles.php`,
  `scripts/setup-pages.php`, editor y vista.
- Pruebas: `test-observe.php`, `test-procedure-access.php`,
  `test-rest-authorization.php`.

**Decisiones:**
- Capacidad `prc_view_applications` y rol `prc_observer`. La zona de la
  persona es la meta de usuario `prc_zone`; sin zona válida, no ve nada.
- El procedimiento decide si admite consulta (`prc_observable`) y qué columnas
  y documentos enseña.
- Ve las solicitudes de los centros de su zona, sus personas y, si se
  permite, descarga sus documentos y exporta lo que ve. No edita nada.
- `people_changed` puede copiarse a quienes consultan esa zona.

- [ ] Prueba: una solicitud de otra zona no se ve ni por enlace directo, ni
      por REST, ni en la exportación.
- [ ] Prueba: sin `prc_observable` la pantalla responde como si no existiera.
- [ ] Implementar y ADR-0043.

### PR 8 — Duplicar (`feature/duplicate-procedure`)

- [ ] Operación `duplicate` en el taller: copia datos, preguntas (con claves
      nuevas), compromisos, especificación de personas y avisos a un borrador.
- [ ] No copia fechas, enlaces de resolución, solicitudes ni archivado; el
      curso queda vacío para que se elija.
- [ ] Prueba: el duplicado no comparte ninguna respuesta ni documento.

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
