---
id: ADR-0050
title: "Todo se edita hasta histórico"
status: Propuesta
date: 2026-11-23
related:
  issues: [119]
  prs: [120]
  sdds: [SDD-0001]
  adrs: [ADR-0013, ADR-0019, ADR-0021, ADR-0023, ADR-0033, ADR-0034, ADR-0040, ADR-0048]
supersedes: [ADR-0034]
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5-5"
---

# ADR-0050: Todo se edita hasta histórico

## Estado

Propuesta (2026-11-23).

## Contexto

Hasta ahora el taller cerraba partes del procedimiento según su estado. La
tabla «Lo que cada estado permite» de la SDD-0001, columna «Quien gestiona»,
estaba en código en `Domain/ProcedureState::editable_fields()` y la cruzaba
`Access/ProcedureAccess::can_edit_group()`: borrador, próximo y abierto se
editaban enteros; cerrado, solo fechas y enlaces; resuelto, solo enlaces; en
subsanación, nada; histórico, nada (ADR-0023). La pantalla pintaba apagado lo
que el POST rechazaba, con la frase de `ProcedureState::why_locked()`.

En la práctica eso obligaba a pedir a administración cambios que son de quien
gestiona: corregir una errata en la descripción después del cierre, mover una
fecha de subsanación, añadir un campo que se olvidó. Y obligó a un panel
aparte, «Plazos de entregas documentales» (ADR-0034), solo para ampliar el
cierre de una entrega cuando «Campos adicionales» ya estaba bloqueado.

Lo que de verdad protege las respuestas ya presentadas no es el estado, sino
`Domain/QuestionChanges`: el tipo de un campo contestado no cambia
(`QuestionChanges::refusal()`, ADR-0040), y las respuestas a un campo que se
quita se quedan bajo su clave (ADR-0019). Eso valía ya con el procedimiento
abierto y solicitudes presentadas.

## Problema

¿Qué puede editar quien gestiona un procedimiento según su estado, sin perder
lo que protege las solicitudes ya presentadas?

## Factores de decisión

- Quien gestiona tiene que poder corregir su procedimiento sin pedírselo a
  administración mientras siga vivo.
- Las respuestas presentadas no se descolocan: lo que lo garantiza tiene que
  seguir igual de duro.
- Histórico sigue siendo el final (ADR-0023): solo lectura para el ámbito, y
  solo administración lo reabre.
- Menos código: una tabla, un grupo por bloque, avisos por bloque y un panel
  aparte que existen solo para cerrar cosas.

## Alternativas consideradas

### Opción 1: dejar la tabla por estado

Lo conocido. Pero sigue obligando a pedir a administración cambios legítimos y
a mantener un panel especial para cada excepción («Plazos de entregas
documentales» fue la primera). Y no protege nada que no proteja ya
`QuestionChanges`, porque con el plazo abierto y solicitudes presentadas todo
estaba igual de abierto.

### Opción 2: abrir más grupos en algunos estados

Por ejemplo, datos y preguntas también en cerrado. Reduce el problema pero
conserva el mecanismo entero —grupos, apagados, motivos— para una diferencia
cada vez más pequeña.

### Opción 3: todo se edita hasta histórico (elegida)

Cada estado salvo histórico edita todo, fechas incluidas y aunque haya
solicitudes presentadas. Quedan las protecciones de `QuestionChanges`.

## Decisión

- **El estado ya no cierra nada salvo histórico.** `ProcedureAccess::can_edit()`
  es la única regla: ámbito, bloqueo de edición y histórico (ADR-0023). Se
  quitan `ProcedureState::GROUP_*`, `groups()`, `editable_fields()`,
  `why_locked()`, `ProcedureAccess::can_edit_group()`, el parámetro de grupo de
  `why_not_editable()`, `ProcedureEditor::allows_group()` y `gated_data()`, el
  aviso del estado y los bloques apagados del taller (`PanelParts::section()`
  pierde su `apagado` y su `motivo`).
- **Se mantienen las protecciones de los campos contestados**
  (`QuestionChanges`): su tipo no cambia, con el mismo mensaje. El texto, la
  ayuda, las fechas de una entrega y los campos nuevos se editan.
- **«Enlaces» deja de ser pestaña**: es la última sección de «Datos», con su
  propio formulario, su operación (`ProcedureEditor::OP_LINKS`, el mismo valor
  `enlaces` de antes) y su botón; la copia privada del PDF sigue igual
  (ADR-0048). `?panel=enlaces` lleva a «Datos», a `#prc-enlaces`.
- **Se quita «Plazos de entregas documentales»** de «Solicitudes» y su
  operación (`delivery_dates`): las fechas de una entrega se editan en su
  campo, en «Campos adicionales». `?panel=entregas` lleva ahí. Sustituye a la
  ADR-0034.
- **Un fin de plazo cambiado a un día ya pasado pide confirmación** al
  guardar «Datos», con el diálogo de siempre (`prc-app.js`: SweetAlert2,
  `confirm()` y, sin JavaScript, se guarda). Con el plazo abierto, guardar lo
  cierra en ese momento: ya era posible y se mantiene, pero ya no por sorpresa.
  El día de hoy lo pone el servidor (`ProcedureState::today()`), no el reloj del
  navegador; un fin de plazo pasado que ya estaba guardado no vuelve a
  preguntar.

## Consecuencias

### Positivas

- Quien gestiona corrige su procedimiento sin intermediarios hasta que lo da
  por terminado.
- Desaparecen una tabla, cuatro constantes, un panel y los textos que
  explicaban qué estaba cerrado y por qué.
- Las fechas de una entrega se cambian en un solo sitio y en los dos
  sentidos: ADR-0034 solo dejaba ampliarlas.

### Negativas

- Se puede cambiar un procedimiento con solicitudes presentadas en más
  momentos: el título, la descripción, los compromisos o el texto de un campo
  pueden no ser ya los que leyó el centro al presentar. Era así con el plazo
  abierto; ahora lo es también después. El justificante guarda lo que se
  presentó.
- Quitar un campo contestado sigue siendo posible (ADR-0019): sus respuestas
  se conservan bajo su clave pero dejan de verse en la tabla y la exportación.
  Antes el estado lo impedía después del cierre; ahora no. Si hiciera falta
  impedirlo, iría en `QuestionChanges`, no en el estado.
- Mover fechas puede cambiar el estado derivado (ADR-0013), por ejemplo
  reabrir el plazo o pasar a subsanación. Es lo que se pide, pero el único
  aviso es el del fin de plazo pasado.

## Referencias

- `src/Prc/Access/ProcedureAccess.php`, `can_edit()` y `why_not_editable()`.
- `src/Prc/Domain/QuestionChanges.php`, `refusal()`.
- `src/Prc/PublicFront/ProcedureEditor.php`, `OLD_PANELS` y `OP_LINKS`.
- `assets/js/prc-app.js`, `preguntaDe()`.
- `tests/unit/test-procedure-access.php`, `tests/unit/test-procedure-editor.php`,
  `tests/js/prc-app.test.js`, `tests/browser/confirmacion.js`.
- Issue [#119](https://github.com/ateeducacion/wp-procedimientos/issues/119).
