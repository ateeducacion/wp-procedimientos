---
id: ADR-0044
title: "Actuar sobre una solicitud —retirarla, restaurarla, prorrogarla o presentarla en nombre de un centro— y su historial"
status: Propuesta
date: 2026-11-10
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0005, ADR-0007, ADR-0018, ADR-0020, ADR-0023, ADR-0028, ADR-0037, ADR-0038, ADR-0041, ADR-0042, ADR-0043]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5-5"
---

# ADR-0044: Actuar sobre una solicitud —retirarla, restaurarla, prorrogarla o presentarla en nombre de un centro— y su historial

## Estado

Propuesta. Desarrolla lo que la
[ADR-0005](ADR-0005-politica-de-edicion-y-auditoria.md) dejó por escribir
sobre la auditoría de las solicitudes, y matiza la
[ADR-0007](ADR-0007-borrar-es-enviar-a-la-papelera.md), la
[ADR-0018](ADR-0018-la-solicitud-es-un-contenido-del-procedimiento.md), la
[ADR-0020](ADR-0020-la-subsanacion-es-un-plazo-y-un-estado.md) y la
[ADR-0023](ADR-0023-el-estado-historico-cierra-la-edicion.md). Ninguna se
reescribe: llevan una adenda que remite aquí.

## Contexto

Son las peticiones que más se repiten de los centros y de quienes revisan, en
la revisión de un trimestre de uso del sistema anterior que hizo el
[PLAN-0003](../plan/PLAN-0003-lo-que-falta-para-sustituir-al-sistema-anterior.md)
(material en `.local/`, ADR-0009):

1. «Quiero darme de baja de esta convocatoria.»
2. «Este centro se inscribió por error, bórrenlo.»
3. «El centro no pudo entrar a tiempo, inscríbanlo ustedes.»
4. «Déjenle unos días más.»
5. «¿Quién cambió esto y cuándo?»

Lo que había:

- la ADR-0007 decía que **una solicitud no se borra: se excluye**, y que
  mandarla a la papelera era cosa del escritorio; el centro no retiraba la
  suya, la editaba mientras el plazo estuviera abierto;
- la ventana de la solicitud era la tabla de la ADR-0020, igual para todos los
  centros (`ApplyForm::window()`), sin manera de abrirla a uno solo;
- una solicitud la crea su centro (ADR-0018); nadie más podía darla de alta;
- la ADR-0005 pedía registrar actor, momento y qué cambió de cada mutación, en
  meta y con tope, y lo dejaba «por escribir»; la ADR-0041 escribió el primer
  registro —`prc_log` en la solicitud, `PublicFront/ApplicationLog`— solo
  para las personas, llamado desde `People` en cada operación;
- quien revisa no tenía dónde anotar lo que el centro no debe leer: la nota de
  revisión de la ADR-0020 la lee el centro.

## Problema

¿Cómo retira una solicitud quien la presentó, cómo se deshace una inscripción
por error, cómo se abre la solicitud de **un** centro fuera de plazo o se le
crea una, y qué queda escrito de todo eso —y de lo de antes—, sin abrir una
puerta que hoy está cerrada ni enseñar a nadie lo que no le toca?

## Factores de decisión

- **Un solo guardián** (`ProcedureAccess`), cerrado en `map_meta_cap`, no solo
  escondiendo el botón (ADR-0014).
- **Nada se borra para siempre desde el aplicativo** (ADR-0007): los datos de
  una solicitud retirada tienen que poder volver.
- **Una por centro** (ADR-0018): restaurar o crear no puede dejar dos.
- **La regla es del dominio**: si la prórroga decide quién edita, lo decide
  donde ya se decide la ventana, no en cada pantalla.
- **Rendir cuentas** (ADR-0005), sin que cada pantalla nueva tenga que acordarse
  de anotar.
- **Mínimo de datos**: el historial no es un sitio para copiar textos largos;
  la nota interna no sale de quien revisa.
- **PHPMD**: `ProcedureAccess` estaba en el tope de métodos públicos;
  `ApplyForm`, `ProcedureEditor` y `ApplicationsPanel`, en el de complejidad.

## Alternativas consideradas

### Retirar: borrar, excluir o papelera

- **Borrar para siempre.** Descartada por la ADR-0007: no hay vuelta atrás y
  quien pulsa no es administración.
- **Un estado de revisión «retirada».** La solicitud seguiría viva: en la tabla,
  en las exportaciones, en la consulta y en `Applications::find()`, que la
  devolvería y no dejaría al centro volver a presentar. Todas las consultas
  tendrían que aprender a saltársela.
- **La papelera (`wp_trash_post()`) — elegida.** Ninguna consulta del
  aplicativo mira la papelera (`post_status => 'publish'` en `Applications`,
  `ApplicationQuery`, `ApplicationExport` y `ProcedureAccess::has_application()`),
  así que retirada deja de contar en todas a la vez y `find()` deja volver a
  presentar. Y se restaura con `wp_untrash_post()`.

### La prórroga: reabrir el procedimiento o una fecha en la solicitud

- **Mover el cierre del procedimiento.** Abre la puerta a todos los centros.
- **Una fecha en la solicitud (`prc_late_until`) — elegida.** Afecta a esa y a
  ninguna otra; vencida, no deja nada.

### Presentar en nombre de un centro: una cuenta del centro o una solicitud vacía

- **Que quien revisa presente con la cuenta del centro.** Suplantar no deja
  rastro de quién lo hizo, y quien revisa no tiene esa cuenta.
- **Rellenarla entera desde el taller.** Duplica el formulario de la solicitud
  —preguntas, documentos, compromisos— en otra pantalla.
- **Una solicitud vacía, con prórroga, que el centro completa con su formulario
  de siempre — elegida.**

### El historial: llamar desde cada pantalla o escuchar acciones

- **Llamar a `ApplicationLog::add()` en cada operación**, como hacía `People`.
  Cada pantalla nueva tiene que acordarse, y nada impide anotar dos veces.
- **Escuchar las acciones que ya emiten las operaciones — elegida.** Es el
  mismo patrón que el correo de la ADR-0038: `prc_application_saved`,
  `prc_application_reviewed`, `prc_delivery_saved`, `prc_people_changed` y las
  cinco acciones nuevas. Cada actuación emite su acción una vez, después de
  escribir, y el historial la anota una vez.

## Decisión

### Las reglas, en `Access/ApplicationActions`

Va aparte de `ProcedureAccess`, que no admite más métodos públicos sin pasar
del umbral de PHPMD, pero **la decisión sigue en el guardián**:
`ProcedureAccess::map_meta_cap()` resuelve cuatro meta capacidades
preguntando a `ApplicationActions::allows()`
([`src/Prc/Access/ProcedureAccess.php`](../../src/Prc/Access/ProcedureAccess.php)),
y ninguna está en ningún rol:

| Meta capacidad | Regla | Quién | Cuándo |
|---|---|---|---|
| `prc_withdraw_application` | `why_not_withdraw()` | quien puede editar esa solicitud: su centro (cualquier cuenta con su código) o la persona docente que la presentó | plazo de solicitud abierto (`open`) o prórroga viva; nunca histórico ni sin publicar |
| `prc_restore_application` | `why_not_restore()` | quien revisa ese procedimiento | si el centro —o la persona docente— no tiene otra viva; nunca histórico |
| `prc_extend_application` | `why_not_extend()` | quien revisa | solicitud viva; nunca histórico, tampoco para administración |
| `prc_submit_for_centre` (sobre el procedimiento) | `why_not_submit_for()` | quien revisa | publicado, no histórico y para centros |

Retirar por error desde la revisión usa `delete_post`, más `why_not_retire()`
(viva y no histórica). `delete_post` sobre una solicitud lo resuelve ahora
`ApplicationActions::may_delete()`: con el procedimiento histórico, nadie
—tampoco administración—; en la papelera —borrar para siempre, «Vaciar
papelera»—, solo `prc_manage_app`; publicada —enviarla a la papelera—, quien
revisa. Como el escritorio pregunta `delete_post` también para restaurar, quien
revisa restaura desde el aplicativo (`prc_restore_application`). Las
operaciones de una fila —revisar, retirar, restaurar, la prórroga— llevan un
nonce de su operación **y** de su solicitud
(`ProcedureEditor::nonce_action( $op, $row )`).
Quien solicita no restaura ni concede prórrogas; quien consulta no hace nada
de esto; un gestor de otro ámbito, tampoco
([`tests/unit/test-application-actions.php`](../../tests/unit/test-application-actions.php)).
La REST no publica `prc_application` (ADR-0018), y el escritorio pide
`edit_prc_applications`, que no tiene quien solicita.

Además, tres filtros de WordPress cierran el escritorio:
`pre_untrash_post` no restaura una solicitud que haría dos ni una de un
procedimiento histórico, entre por donde entre; `wp_untrash_post_status` la
devuelve con el estado que tenía (publicada), y no en borrador como un
procedimiento (ADR-0007); y `pre_delete_post` no deja borrar para siempre una
retirada sin administración delante.

**La papelera no se vacía sola para las solicitudes.** El núcleo borra a los
`EMPTY_TRASH_DAYS` días lo que tiene la meta `_wp_trash_meta_time`
(`wp_scheduled_delete()`), y una retirada perdería así sus documentos y su
historial. Al retirarla, `ApplicationOperations::on_trashed()` le quita esa
meta, así que el vaciado automático no la ve; y por si llega una con ella —una
retirada antes de esta regla—, `pre_delete_post` la para, porque el cron no
tiene a nadie de administración delante. Una retirada se queda hasta que
administración la borre a mano.

Una solicitud en la papelera **ya no es de quien la presentó**:
`can_view_application()` solo la da por propia publicada, así que no se ve en
«Mi centro», no se descarga su documento y no llega su correo de revisión.
Quien revisa sí la ve, en «Retiradas».

### Retirar (quien solicita)

En la pantalla de la solicitud, una sección «Retirar la solicitud» con
confirmación de tres escalones (`data-prc-confirm`); «Mi centro» la enlaza.
El POST lo atiende `PublicFront/WithdrawForm` —fuera de `ApplyForm`, que ya
pasaba del umbral de complejidad—, que pregunta a la meta capacidad. Va a la
papelera y el centro puede volver a presentar mientras haya plazo. Correo
`application_withdrawn` a los contactos y un acuse a quien retira (ADR-0038),
apagables en «Avisos».

### Retirar por error y restaurar (quien revisa)

En la revisión de cada solicitud, «Retirar esta solicitud» con **motivo
obligatorio**, que queda en el historial y no viaja en ningún correo. Las
retiradas se listan en «Retiradas (N)» del panel, con su motivo, quién y
cuándo —quien la presentó, quien revisa o «Desde el escritorio»—, y
«Restaurar». No hay borrado definitivo desde el aplicativo, y ni la revisión
de una en una ni la de bloque tocan una retirada: solo revisan solicitudes
publicadas.

### La prórroga

`prc_late_until` (`Y-m-d`, inclusive). La regla es pura:
`ProcedureState::late_open( $state, $late_until, $today )`, y la ventana de la
solicitud pasa a `ProcedureState::application_window()`, con la prórroga como
cuarto argumento; `ApplyForm::window()` delega en ella. Con prórroga viva, el
centro edita **el formulario entero** aunque el plazo general haya cerrado, y
puede retirarla; no afecta a ninguna otra solicitud. Vencida, nada. Nunca en
histórico ni sin publicar. Sin fechas propias, la lista de personas sigue la
ventana de la solicitud, y con ella su prórroga
(`PeopleSpec::application_editable()`); con ventana propia, manda la propia. La
pantalla del centro y «Mi centro» dicen «tiene de plazo hasta el …». Correo
`application_extended` a quien presentó, mientras siga siendo suya; quitarla no
avisa.

Guardar con prórroga sigue la regla general: la solicitud vuelve a
«presentada» para que se revise otra vez (ADR-0020). El formulario de la
prórroga lo avisa cuando la solicitud está admitida o excluida, y el historial
lo anota como un cambio de revisión hecho por el guardado, con el estado de
antes, sin correo de revisión. **Con el procedimiento resuelto**, la prórroga y
retirar se permiten —es una decisión expresa de quien revisa—, y el formulario
de la prórroga avisa de que ya está resuelto.

### Presentar en nombre de un centro

En el panel, «Presentar en nombre de un centro»: un campo de código que valida
el servidor y, aparte, un buscador por código o nombre que devuelve como mucho
veinte centros (`CentreCatalog::search()`); el catálogo no se carga en el HTML.
Se crea una solicitud **vacía**, «presentada», con `prc_late_until`
obligatorio, `prc_created_by` y `post_author` = quien revisa. Se rechaza un
código que no está en el catálogo o está inactivo, una clase de código que el
procedimiento no admite, un centro que ya tiene solicitud —si está retirada, se
pide restaurarla— y un procedimiento de profesorado. Respeta la elegibilidad
(ADR-0042) salvo que se marque «aunque no cumpla los requisitos», y el motivo
queda en el historial.

El centro la ve en «Mi centro» y la completa con su formulario dentro de la
prórroga; la primera vez que la guarda **pasa a ser quien la presenta**
(`post_author`), sale el acuse de presentada, y `prc_created_by` sigue diciendo
quién la creó. Los compromisos no se dan por aceptados hasta entonces, y
mientras tanto cuenta como **incompleta**, con el motivo «Pendiente de que el
centro la complete» (`ApplicationCompleteness::reasons()`), y sale en el
filtro de incompletas.

**Una por centro, también con dos envíos a la vez.** Después de crear una
solicitud —el alta normal del centro o en su nombre—,
`ApplicationOperations::lost_race()` vuelve a mirar: si hay otra publicada de ese centro
con un ID más bajo, la nueva se borra para siempre y se rechaza con «ya hay una
solicitud de este centro».

### La nota interna

`prc_internal_note`, un campo aparte en la revisión de cada solicitud que viaja
con el envío de la revisión. No la ven quien solicita ni quien consulta, no va
en ningún correo y no está en `ObserverScope::ALLOWED_COLUMNS`. Se ofrece como
columna del grupo «Revisión» de la exportación, **fuera** de «Todas las
columnas», como las del catálogo (`CentreColumns::extras()`).

### El historial: qué, dónde, quién lo ve, tope y límites

**Qué se anota** (`PublicFront/ApplicationHistory`, que escucha las acciones):

| Evento | Acción | `data` |
|---|---|---|
| `submitted` / `updated` | `prc_application_saved` | — |
| `review_changed` | `prc_application_reviewed`, solo si cambia el estado o la nota | `state`, `previous`, `note_changed` (sin la nota) |
| `review_changed` | `prc_application_saved` cuando guardar la devuelve a «presentada» desde otro estado (la acción lleva ahora el estado de antes) | `state`, `previous`, `by: save` |
| `delivery` | `prc_delivery_saved` | `question` |
| `person_added` / `person_changed` / `person_removed` | `prc_people_changed` | `before`, `after` (ADR-0041) |
| `withdrawn` | `prc_application_withdrawn` (de `trashed_post`) | `by` (`applicant`, `reviewer`, `desk`), `reason` |
| `restored` | `prc_application_restored` (de `untrashed_post`) | — |
| `extended` | `prc_application_extended` | `until`, `previous` |
| `created_for_centre` | `prc_application_created_for` | `until`, `ineligible` |
| `internal_note` | `prc_internal_note_changed` | — (nunca el texto) |

Cada entrada lleva quién (`user`) y cuándo (UTC, ISO 8601). Retirar y
restaurar salen de los ganchos del núcleo, así que también queda lo que
administración hace desde el escritorio.

**Dónde**: en la meta `prc_log` de la solicitud, como en la ADR-0041; tope de
500 entradas, que descarta las más antiguas y lo dice.

**Quién lo ve**: solo quien revisa, en la revisión de esa solicitud y solo al
abrirla (`?historial=ID`): un historial por solicitud, que sustituye al «de la
lista» de la ADR-0041. Quien solicita no lo ve; quien consulta, tampoco
(ADR-0043).

**Límites**: no es un archivo histórico (ADR-0005); no se anotan lecturas,
exportaciones ni descargas; tampoco los cambios del procedimiento, que la
ADR-0005 pedía en meta del procedimiento y siguen sin escribirse.

## Consecuencias

### Positivas

- Las cinco peticiones tienen una respuesta en el aplicativo, sin pedir a
  administración que entre al escritorio.
- Retirar no borra nada: lo hecho por error se deshace en un clic, y las
  consultas, exportaciones y la consulta de solo lectura no tienen que aprender
  nada nuevo.
- El historial no depende de que cada pantalla se acuerde: basta con emitir la
  acción.
- Abrir la solicitud de **un** centro ya no exige mover el plazo de todos.

### Negativas

- **Presentar en nombre de un centro no avisa al centro**: el catálogo no trae
  su dirección. Quien revisa tiene que avisarle por su cuenta, y la pantalla
  lo dice.
- **La retirada sigue ocupando sitio, sin fecha de caducidad**: datos
  personales en la papelera hasta que administración la borre a mano; la
  papelera del núcleo no la vacía sola. Sus documentos se borran entonces
  (`ApplicationFiles::on_delete()`, ADR-0028). Es a propósito —no perder
  documentos ni historial— y hay que saberlo.
- **Retirar no se apoya en la papelera del núcleo cuando está desactivada**:
  con `EMPTY_TRASH_DAYS` a 0, `wp_trash_post()` borra para siempre. El
  aplicativo hace entonces a mano lo mismo que el núcleo con la papelera
  activa —mismos filtros y acciones, así que el historial y el correo actúan
  igual—, y `pre_delete_post` impide que una solicitud viva se borre desde
  `wp_trash_post()`; administración la sigue pudiendo borrar a propósito. Para
  saber de dónde viene el borrado hay que mirar la pila de llamadas, porque el
  núcleo no deja enganche antes; y el filtro `prc_trash_enabled` decide si la
  papelera está activa.
- **El historial lo ve quien revisa, entero**, también el motivo de una
  retirada; no hay historial para el centro.
- **La prórroga es por días**, no por horas, como todos los plazos del
  aplicativo.
- **Retirar por el escritorio** no pide motivo: queda como «enviada a la
  papelera desde el escritorio».
- **La unicidad sigue sin ser un índice** (ADR-0018): dos altas a la vez del
  mismo centro se resuelven después de crear —se queda la de ID más bajo y la
  otra se borra—, no con un bloqueo. Quedan sin cerrar dos carreras: restaurar
  una retirada a la vez que el centro presenta otra (la restaurada no se
  vuelve a comprobar tras restaurar), y dos solicitudes individuales de la
  misma persona docente, que no pasan por `lost_race()`.

### Neutras

- `ProcedureAccess` no gana métodos públicos: las reglas están en
  `ApplicationActions` y la decisión, en su `map_meta_cap()`.
- La ventana de la solicitud se mueve de `ApplyForm::window()` a
  `ProcedureState::application_window()`, sin cambiar lo que dice.
