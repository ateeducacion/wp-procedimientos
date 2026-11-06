---
id: ADR-0033
title: "Los documentos pueden tener un plazo de entrega propio"
status: Propuesta
date: 2026-11-06
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0019, ADR-0020, ADR-0024, ADR-0028]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Codex"
  model: "GPT-6"
---

# ADR-0033: Los documentos pueden tener un plazo de entrega propio

## Contexto

Cerrar la inscripción o publicar el listado definitivo no termina necesariamente
las obligaciones documentales del centro: una memoria puede entregarse después.
El formulario actual usa `ApplyForm::window()` para cerrar toda la solicitud y
`Applications::save()` devuelve una solicitud editada a «Presentada». Esas reglas
no sirven para aportar una memoria sin modificar la solicitud admitida.

La ADR-0028 ya resuelve el almacenamiento privado, la sustitución segura y la
descarga autorizada. La pregunta `file` ya tiene identidad estable, rótulo e
indicaciones y aparece en la tabla de revisión y el CSV. Las fuentes verificables
son `src/Prc/Domain/ProcedureQuestions.php`, `src/Prc/PublicFront/ApplicationFiles.php`
y `src/Prc/PublicFront/Applications.php`.

## Decisión

Una pregunta `file` puede marcarse como **entrega con plazo propio**. Solo en ese
caso guarda `delivery: true`, `opens_at` y `closes_at`, ambos días reales en
`Y-m-d`, con apertura no posterior al cierre. Los días son inclusivos en la zona
del sitio, como los plazos existentes. Una configuración incompleta o inválida
no admite entregas. El taller rechaza el guardado completo y conserva las preguntas
anteriores cuando una entrega tiene fechas inválidas.

Las preguntas sin esa marca conservan exactamente su comportamiento. Una entrega
independiente no se exige ni se acepta desde el formulario inicial. Aparece en la
misma pantalla de la solicitud, pero con un POST por documento, nonce y permiso
propio en `ProcedureAccess::can_deliver()`. Se deriva la solicitud por procedimiento
y código de centro; el navegador no decide a qué solicitud ajena escribir.

Se admite una entrega si existe una solicitud publicada del centro de la cuenta,
no excluida, el procedimiento está publicado sin archivar y el plazo propio está
abierto. Puede estar cerrado o resuelto, o en subsanación: eso no modifica el plazo
propio. No se exige estar admitido, porque pueden pedirse documentos antes de
publicar el definitivo. Ser revisor de otro centro no concede permiso para entregar.

El POST modifica únicamente el descriptor y el fichero de esa pregunta. Conserva
respuestas, cargo, coordinación, fecha y autor de la solicitud, revisión y nota.
Un envío vacío se rechaza, incluso si ya existía un documento. La sustitución
conserva el anterior hasta confirmar el nuevo descriptor; no guarda versiones.

Las fechas se muestran en la ficha pública; nombres y descargas de documentos
siguen siendo privados. Tabla de revisión y CSV usan las preguntas completas;
el formulario inicial usa solo las que no son entregas independientes.

## Alternativas y límites

- Reabrir la solicitud completa permitiría cambiar respuestas y reiniciaría su
  revisión. Se descarta.
- Crear otro CPT o un constructor de formularios duplicaría identidad y almacén
  sin ser necesario para una entrega de un documento. Se descarta en esta pieza.
- Se conserva el máximo conjunto de veinte preguntas y un fichero por pregunta.
  No añade participantes, reclamaciones, importador, recibo firmado ni historial
  de versiones. No sustituye ni modifica el alcance histórico de ADR-0024.
- Los plazos se configuran en «Preguntas» mientras ese panel es editable; quedan
  sujetos a su bloqueo al cerrar la inscripción. La ampliación posterior de un
  plazo requiere una operación específica futura, sin reabrir la inscripción.
- La marca de obligatoriedad expresa qué se pide al centro, pero no introduce
  un cierre automático por cumplimiento ni cambia la admisión de la solicitud.

## Verificación

`tests/unit/test-procedure-questions.php` prueba límites inclusivos, fechas
imposibles, orden invertido y separación del formulario inicial.
`tests/unit/test-procedure-editor.php` prueba guardado y rechazo sin pérdida.
`tests/unit/test-application-files.php` prueba POST real tras el definitivo,
conservación del expediente, nonce, otro centro, pregunta ordinaria, plazos
cerrados o futuros, exclusión, archivo, ausencia de solicitud y fallo de almacén.
La demostración «Escuelas Promotoras de Salud» incluye una memoria con plazo
abierto en un procedimiento resuelto para verificar el recorrido en navegador.
