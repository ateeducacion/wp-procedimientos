---
id: REGISTRO-SDD
title: "Índice de estados de los SDD"
status: Aceptada
date: 2026-09-15
related:
  issues: []
  prs: []
  adrs: []
  sdds: [SDD-0001]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-fable-5-1"
---

# Índice de SDD

La tabla es el registro único de estados; no se duplican listas por estado.
Ver la [guía](README.md) y la [plantilla](plantilla.md).

Hay un solo SDD y describe el aplicativo que va a sustituir al sistema de
hoy. Cómo funciona ese sistema no es un SDD: es material de investigación
—formularios, vistas, exportaciones y fragmentos de código de la instalación
actual— y vive en `.local/`, que no se versiona
([ADR-0009](../adr/ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md)).

Las decisiones que sostienen el diseño están en el
[índice de ADR](../adr/registro.md).

| ID | Contenido | Estado | Sustituido por |
|----|-----------|--------|----------------|
| [SDD-0001](SDD-0001-arquitectura-del-aplicativo-de-procedimientos.md) | El aplicativo de procedimientos: `prc_procedure` y `prc_application`, dos taxonomías, estado derivado de las fechas, acotado por ámbito y por centro, cinco pantallas | Propuesta (2026-09-15) | — |
