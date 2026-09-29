---
id: GUIA-ESBOZOS
title: "Guía de los esbozos de referencia"
status: Aceptada
date: 2026-09-15
related:
  issues: []
  prs: []
  adrs: []
  sdds: []
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-fable-5-1"
---

# Esbozos de referencia

Ficheros aportados como **referencia de diseño**, no como fuente de verdad
automática del entorno: maquetas de pantalla, diagramas de flujo y capturas
anotadas que se consultan al implementar.

| Fichero | Descripción |
|---------|-------------|
| — | Todavía no hay ninguno. |

El directorio está vacío a 2026-09-15 y se deja creado a propósito, para que
el primer esbozo tenga sitio y esta guía se lea antes de dejarlo caer.

## Uso

- Consultar maquetación, campos y flujos al implementar `src/Prc/` y las
  cinco pantallas del aplicativo.
- **No** es material de producción. La exportación del sitio de origen, los
  formularios, sus vistas y los fragmentos de código del sistema anterior
  viven en `.local/`, que **no se versiona** porque lleva datos personales y
  describe una instalación ajena
  ([ADR-0009](../adr/ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md)).
- **No** importar nada de aquí a ciegas en un entorno de desarrollo: los
  esbozos dependen de identificadores y catálogos del sitio de origen que aquí
  no existen o no coinciden.
- Cada subcarpeta con más de un fichero lleva su propio `README.md` que
  explique qué representa y de dónde salió.
