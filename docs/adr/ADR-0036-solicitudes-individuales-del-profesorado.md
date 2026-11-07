---
id: ADR-0036
title: "Las solicitudes del profesorado son individuales y usan su cuenta"
status: Propuesta
date: 2026-11-07
related:
  issues: []
  prs: [47]
  sdds: [SDD-0001]
  adrs: [ADR-0015, ADR-0018, ADR-0019, ADR-0028]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Codex"
  model: "GPT-6"
---

# ADR-0036: Las solicitudes del profesorado son individuales y usan su cuenta

## Estado

Propuesta

## Contexto

El modelo ya distingue procedimientos para centros y para profesorado mediante
`prc_audience`, pero el flujo de solicitud se había reservado para centros.
El tipo de contenido `prc_application`, las preguntas configurables, los
compromisos y el almacén privado de documentos ya cubren las piezas comunes.

## Problema

¿Cómo permitir que una persona docente solicite por sí misma sin pedirle datos
de centro ni permitir que elija o suplante a otra persona?

## Factores de decisión

- La persona solicitante debe identificarse por una cuenta autenticada.
- Una cuenta solo debe consultar y editar sus propias solicitudes individuales.
- Las solicitudes de centro existentes deben seguir leyéndose como tales.
- La capacidad de docente no debe permitir solicitar procedimientos para
  centros.
- Preguntas, revisión y documentos deben reutilizar el flujo existente.

## Alternativas consideradas

### Un tipo de contenido distinto para solicitudes docentes

Separaría los dos flujos en el escritorio, pero duplicaría revisión, preguntas,
documentos, listados y permisos.

### Reutilizar `prc_application` con tipo de solicitante

Mantiene las herramientas comunes. La identidad individual queda en
`post_author`; no se guarda una copia del nombre o correo de la cuenta.

## Decisión

Reutilizaremos `prc_application`. Las solicitudes individuales guardan el tipo
`teacher`, el autor como identidad, respuestas y documentos privados. Solo
quien tenga `prc_apply` y `prc_apply_as_teacher` podrá solicitarlas, y solo en
procedimientos publicados con audiencia `teachers`. Cada persona tendrá una
solicitud por procedimiento. El formulario no mostrará código de centro, cargo
ni coordinación. La pantalla «Mis solicitudes» mostrará las solicitudes del
autor. Las solicitudes antiguas sin tipo se interpretan como solicitudes de
centro.

## Consecuencias

### Positivas

- Docentes y centros pueden usar el mismo esquema de preguntas, revisión y
  archivos privados.
- La identidad docente no depende de datos escritos en el formulario.
- El control de acceso sigue comprobando capacidades y propiedad.

### Negativas

- El rol docente necesita asignarse a las cuentas autorizadas por cada
  instalación.
- Este cambio no migra solicitudes ni certificaciones existentes.

### Neutras

- La administración y las personas gestoras mantienen la revisión por ámbito.
- Los perfiles de ejemplo incluyen una cuenta y una solicitud docente.
