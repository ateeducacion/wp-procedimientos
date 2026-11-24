---
id: ADR-0051
title: "Lo contestado no se descoloca al editar los campos"
status: Propuesta
date: 2026-11-24
related:
  issues: [121]
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0019, ADR-0040, ADR-0050]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5-5"
---

# ADR-0051: Lo contestado no se descoloca al editar los campos

## Estado

Propuesta (2026-11-24). Modifica en parte la
[ADR-0019](ADR-0019-la-solicitud-es-nucleo-fijo-mas-preguntas.md) —quitar un
campo contestado— y la
[ADR-0040](ADR-0040-numero-fecha-rejilla-y-puntos-amplian-las-preguntas.md)
—quitar una fila o una opción, y los puntos—. El resto de las dos sigue igual:
la solicitud es núcleo fijo más preguntas, y las preguntas tienen clave.

## Contexto

Desde la [ADR-0050](ADR-0050-todo-se-edita-hasta-historico.md), el taller se
edita entero hasta histórico, aunque haya solicitudes presentadas. Lo único que
protegía esas respuestas era que el tipo de un campo contestado no cambia
(`QuestionChanges::refusal()`). Seguía siendo posible:

- quitar un campo contestado: sus respuestas se conservaban bajo su clave pero
  dejaban de verse en la tabla y en la exportación (ADR-0019);
- quitar, o reescribir, una opción ya elegida de «Una opción» o «Varias
  opciones»: las opciones no tienen clave, así que la respuesta guardada se
  queda con un texto que ya no está en la lista;
- quitar una fila de una rejilla con cantidades: dejan de verse;
- marcar o desmarcar «Entrega con plazo propio» con documentos ya entregados,
  que cambia dónde y cuándo se gestionan;
- cambiar los puntos de una opción elegida, que cambia en silencio la
  puntuación de quien la eligió, porque se calcula al leer (ADR-0040).

## Problema

¿Cómo se sigue editando el procedimiento hasta histórico sin perder ni alterar
en silencio lo que ya presentaron los centros?

## Factores de decisión

- La libertad de la ADR-0050 se mantiene: ningún panel vuelve a cerrarse por
  el estado.
- Ninguna respuesta presentada se pierde ni cambia de sentido sin que quien
  gestiona lo sepa.
- Las reglas, en un solo sitio y comprobadas en el servidor.
- Lo mínimo que cubra los cinco casos, sin cambiar el modelo de datos.

## Alternativas consideradas

### Opción 1: solo avisar

Un aviso antes de guardar y se guarda igual. No protege nada: el aviso se
acepta sin leerlo, y las respuestas se descolocan igual.

### Opción 2: retirar en vez de quitar

Un campo u opción «retirado» deja de pedirse pero sigue en la tabla y en la
exportación, y se puede reactivar. Es lo deseable a medio plazo, pero pide un
estado nuevo en cada pregunta y en cada opción, y claves persistentes para las
opciones, con su migración de respuestas. Queda para después.

### Opción 3: rechazar lo que descoloca, confirmar los puntos (elegida)

Se rechaza al guardar lo que deja una respuesta sin sitio, se pide una casilla
para cambiar los puntos elegidos, y todo lo demás se guarda.

## Decisión

Lo decide `Domain/QuestionChanges::refusal()`, sea cual sea el estado. Con
alguna solicitud que lo haya contestado:

- **Un campo no se quita**, ni con «Quitar este campo» ni dejando su título en
  blanco. El taller no ofrece la casilla y dice «Con respuestas: no se puede
  quitar». Esto cambia la ADR-0019, que lo permitía.
- **Su tipo no cambia** (ya era así, ADR-0040).
- **«Entrega con plazo propio» no cambia** en un campo «Archivo» con documentos
  entregados, ni para marcarla ni para desmarcarla. Las fechas, sí.
- **Una opción elegida por alguna solicitud no se quita ni cambia de texto.**
  Como las opciones se guardan por su texto, cambiar el texto es quitarla y
  crear otra: se rechaza igual. Una opción nueva, o quitar una que nadie
  eligió, sí.
- **Una fila de rejilla con cantidades no se quita.** Cambiar su nombre en su
  misma línea, sí: la fila conserva su clave (ADR-0040).
- **Cambiar los puntos de una opción elegida pide confirmarlo** con la casilla
  «Aplicar el cambio de puntos a las solicitudes presentadas», que solo sale
  cuando hay una «Varias opciones» contestada. Sin marcarla, se rechaza y se
  explica que cambia la puntuación de esas solicitudes. Los puntos de las
  opciones que nadie eligió cambian sin más. La puntuación se sigue calculando
  al leer: no se congela por solicitud, para que dos centros con las mismas
  respuestas tengan siempre los mismos puntos.

Qué se ha contestado lo dice `Applications::answered_keys()` (las respuestas y
los documentos), y qué opciones y filas se han usado,
`Applications::used_values()`, las dos sobre las solicitudes vivas, como ya
hacía la regla del tipo. Una respuesta vacía no cuenta.

El rechazo no guarda nada del panel y dice el motivo del primer problema que
encuentra.

## Consecuencias

### Positivas

- Nada presentado deja de verse ni cambia de sentido por una edición del
  taller.
- Quien gestiona sigue corrigiendo títulos, indicaciones, orden y fechas, y
  añadiendo campos, opciones y filas, hasta histórico.
- Las reglas viven en una función pura, con sus tests, y no dependen del estado.

### Negativas

- Corregir una errata en una opción ya elegida no es posible: las opciones no
  tienen clave. Hasta que la tengan, la salida es añadir la opción bien escrita
  y dejar la anterior.
- Un campo contestado que ya no interesa no se puede quitar: se queda. Hasta que
  exista «retirar», solo se le puede cambiar el título y dejarlo opcional.
- Una solicitud retirada (en la papelera, ADR-0044) no cuenta: si se
  restaura después de un cambio, puede volver con una respuesta sin sitio.
- El cambio de puntos confirmado no queda en el historial ni se guarda la
  puntuación usada en la resolución.

### Fuera de esta decisión

- Retirar campos y opciones (`retired`).
- Claves persistentes para las opciones, con migración de las respuestas.
- Versionar el baremo y congelar la puntuación de la resolución.
- Avisar al cambiar la obligatoriedad, al reducir el rango de un número o al
  añadir un campo obligatorio con solicitudes presentadas.

## Referencias

- `src/Prc/Domain/QuestionChanges.php`, `refusal()`.
- `src/Prc/PublicFront/Applications.php`, `answered_keys()` y `used_values()`.
- `src/Prc/PublicFront/ProcedureEditor.php`, `FIELD_Q_POINTS_OK`.
- `tests/unit/test-procedure-questions.php`, `test_what_was_answered_keeps_its_place()`.
- `tests/unit/test-procedure-editor.php`,
  `test_answered_questions_and_chosen_options_are_not_lost()` y
  `test_the_delivery_mode_of_a_file_question_with_documents_is_refused()`.
- Issue [#121](https://github.com/ateeducacion/wp-procedimientos/issues/121).
