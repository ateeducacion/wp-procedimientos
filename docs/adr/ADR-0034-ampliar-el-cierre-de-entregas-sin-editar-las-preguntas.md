---
id: ADR-0034
title: "Ampliar el cierre de entregas sin editar las preguntas"
status: Propuesta
date: 2026-11-06
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0019, ADR-0023, ADR-0033]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Codex"
  model: "GPT-6"
---

# ADR-0034: Ampliar el cierre de entregas sin editar las preguntas

## Estado

Propuesta

## Contexto

La ADR-0033 permite que una pregunta de archivo tenga su propio periodo de
entrega. El taller bloquea el panel «Preguntas» cuando cambia el estado del
procedimiento, porque alterar preguntas después de una solicitud puede
desconectar respuestas existentes (`ProcedureState::editable_fields()` y
`ProcedureAccess::can_edit_group()`).

## Problema

¿Cómo se amplía una entrega documental que sigue pendiente cuando ya se cerró
la edición de preguntas, sin reabrir la inscripción ni tocar otras partes de
la solicitud?

## Factores de decisión

- No cambiar la apertura ni la identidad de una pregunta ya usada.
- No reducir por accidente el plazo vigente.
- Mantener las comprobaciones de permiso, ámbito, bloqueo y nonce de cada
  escritura.
- Poder consultar y gestionar el cambio aunque el panel «Preguntas» sea de
  solo lectura.

## Alternativas consideradas

### Opción 1: permitir editar de nuevo todo el panel «Preguntas»

Evita una pantalla nueva, pero también reabre cambios de rótulos, tipos,
obligatoriedad y eliminación de preguntas que pueden tener respuestas.

### Opción 2: panel independiente para ampliar el cierre

Solo presenta preguntas que ya son entregas, conserva su apertura y acepta
cierres iguales o posteriores al guardado. Las preguntas siguen protegidas por
el bloqueo actual.

## Decisión

Añadiremos un panel «Entregas» accesible a quien puede editar el procedimiento.
Su única escritura será ampliar o mantener el cierre de entregas existentes.
La petición se valida en servidor y actualiza la meta de preguntas sin
reescribir sus demás campos. Un procedimiento histórico sigue sujeto a la
excepción de administración de la ADR-0023.

## Consecuencias

### Positivas

- Se puede prorrogar una entrega tras cerrar la edición de preguntas sin
  reabrir la inscripción.
- La apertura, las preguntas, los documentos aportados y las revisiones no
  cambian.

### Negativas

- Esta acción solo amplía el cierre; para corregir una fecha por error hacia
  atrás hará falta una operación administrativa distinta, deliberada y
  revisada.
- El cambio no conserva un historial de valores anteriores.

### Neutras

- La fecha ampliada sigue siendo visible en la ficha pública mediante el
  calendario previsto por la ADR-0033.

## Evidencia

- `src/Prc/Domain/ProcedureState.php`: el estado cerrado solo deja editar fechas
  y enlaces; el estado resuelto deja editar enlaces.
- `src/Prc/PublicFront/ProcedureEditor.php` y
  `src/Prc/Access/ProcedureAccess.php`: los POST del taller comprueban nonce,
  bloqueo, capacidad y ámbito.
- Pruebas en `tests/unit/test-procedure-editor.php`.
