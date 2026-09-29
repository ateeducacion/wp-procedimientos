---
id: REGISTRO-ADR
title: "Índice de estados de las ADR"
status: Aceptada
date: 2026-09-15
related:
  issues: []
  prs: []
  adrs: [ADR-0001, ADR-0002, ADR-0003, ADR-0004, ADR-0005, ADR-0006, ADR-0007, ADR-0008, ADR-0009, ADR-0010, ADR-0011, ADR-0012, ADR-0013, ADR-0014, ADR-0015, ADR-0016, ADR-0017, ADR-0018, ADR-0019, ADR-0020, ADR-0021, ADR-0022, ADR-0023, ADR-0024, ADR-0025, ADR-0026, ADR-0027, ADR-0028, ADR-0029, ADR-0030]
  sdds: [SDD-0001]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-fable-5-1"
---

# Índice de ADR

La tabla es el registro único de estados; no se duplican listas por estado.
Ver la [guía](README.md) y la [plantilla](plantilla.md).

Las veinticuatro primeras se redactaron el 2026-09-15, sobre la foto del sistema
anterior tomada ese mismo día —material de investigación que vive en `.local/`
y no se versiona— y sobre el esqueleto del aplicativo de eventos, del que este
repositorio parte ([ADR-0010](ADR-0010-se-parte-del-esqueleto-del-aplicativo-de-eventos.md)).
Están en `Propuesta` porque fijan la arquitectura **antes** de que haya nada
desplegado ni revisado por una persona: el sitio de destino sigue funcionando
exactamente como lo describe ese material. Mientras el repositorio no tenga
historia, se corrigen en su propio texto; después del primer commit, toda
corrección es adenda o ADR nueva.

La ADR-0025 y la ADR-0026 son del 2026-09-16 y salen del calco de la interfaz
del sistema que se sustituye: al reproducir sus pantallas aparecieron dos datos
que el modelo del día anterior no recogía.

La ADR-0027, del 2026-09-19, es un porte a mano de la corrección equivalente
en el aplicativo de eventos —tal y como prevé la
[ADR-0010](ADR-0010-se-parte-del-esqueleto-del-aplicativo-de-eventos.md)—: la
sincronización de snippets deja de guardar los que no han cambiado,
comparando un fingerprint SHA-256 de su estado gestionado en vez de la
versión global del aplicativo.

| ID | Título | Estado | Fecha |
|----|--------|--------|-------|
| [ADR-0001](ADR-0001-repo-entorno-desarrollo-no-plugin.md) | El repositorio es un entorno de desarrollo, no un plugin | Propuesta | 2026-09-15 |
| [ADR-0002](ADR-0002-sincronizacion-snippets-eval-file.md) | Sincronización de snippets con `wp eval-file` | Propuesta | 2026-09-15 |
| [ADR-0003](ADR-0003-identificadores-internos-en-ingles.md) | Identificadores internos en inglés, lo que lee una persona en castellano | Propuesta | 2026-09-15 |
| [ADR-0004](ADR-0004-ci-y-politica-de-pruebas.md) | CI y política de pruebas | Propuesta | 2026-09-15 |
| [ADR-0005](ADR-0005-politica-de-edicion-y-auditoria.md) | Política de edición y auditoría | Propuesta | 2026-09-15 |
| [ADR-0006](ADR-0006-librerias-de-terceros-desde-cdn-con-sri.md) | Librerías de terceros desde CDN con SRI | Propuesta | 2026-09-15 |
| [ADR-0007](ADR-0007-borrar-es-enviar-a-la-papelera.md) | Borrar es enviar a la papelera | Propuesta | 2026-09-15 |
| [ADR-0008](ADR-0008-bloqueo-de-edicion-con-el-de-wordpress.md) | Bloqueo de edición con el de WordPress | Propuesta | 2026-09-15 |
| [ADR-0009](ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md) | El repositorio se publica sin nada de nadie | Propuesta | 2026-09-15 |
| [ADR-0010](ADR-0010-se-parte-del-esqueleto-del-aplicativo-de-eventos.md) | Se parte del esqueleto del aplicativo de eventos | Propuesta | 2026-09-15 |
| [ADR-0011](ADR-0011-el-procedimiento-es-un-tipo-de-contenido.md) | El procedimiento es un tipo de contenido, no una entrada de formulario más una página | Propuesta | 2026-09-15 |
| [ADR-0012](ADR-0012-una-taxonomia-por-dimension.md) | Una taxonomía por dimensión: ámbito y curso; el estado no es un término | Propuesta | 2026-09-15 |
| [ADR-0013](ADR-0013-el-estado-del-procedimiento-se-deriva-de-las-fechas.md) | El estado del procedimiento se deriva de las fechas | Propuesta | 2026-09-15 |
| [ADR-0014](ADR-0014-el-ambito-es-un-alcance-no-un-rol.md) | El ámbito es un alcance asignado a la persona, no un rol | Propuesta | 2026-09-15 |
| [ADR-0015](ADR-0015-el-aplicativo-comprueba-capacidades-no-roles.md) | El aplicativo comprueba capacidades; los roles del sitio de destino no se nombran | Propuesta | 2026-09-15 |
| [ADR-0016](ADR-0016-el-centro-de-la-persona-es-una-meta-configurable.md) | El centro de la persona es una meta de usuario con clave configurable | Propuesta | 2026-09-15 |
| [ADR-0017](ADR-0017-el-catalogo-de-centros-no-se-versiona.md) | El catálogo de centros no se versiona: lo contesta un filtro | Propuesta | 2026-09-15 |
| [ADR-0018](ADR-0018-la-solicitud-es-un-contenido-del-procedimiento.md) | La solicitud es un contenido que cuelga del procedimiento, una por centro | Propuesta | 2026-09-15 |
| [ADR-0019](ADR-0019-la-solicitud-es-nucleo-fijo-mas-preguntas.md) | La solicitud es un núcleo fijo más preguntas configurables | Propuesta | 2026-09-15 |
| [ADR-0020](ADR-0020-la-subsanacion-es-un-plazo-y-un-estado.md) | La subsanación es un plazo del procedimiento y un estado de la solicitud | Propuesta | 2026-09-15 |
| [ADR-0021](ADR-0021-la-resolucion-y-los-listados-son-enlaces.md) | La resolución y los listados son enlaces, no copias | Propuesta | 2026-09-15 |
| [ADR-0022](ADR-0022-las-pantallas-son-paginas-pintadas-enteras.md) | Las pantallas son páginas de WordPress pintadas enteras por código | Propuesta | 2026-09-15 |
| [ADR-0023](ADR-0023-el-estado-historico-cierra-la-edicion.md) | El estado histórico cierra la edición | Propuesta | 2026-09-15 |
| [ADR-0024](ADR-0024-las-solicitudes-de-hoy-no-se-migran.md) | Las solicitudes y los documentos de hoy no se migran en la fase 1 | Propuesta | 2026-09-15 |
| [ADR-0025](ADR-0025-un-procedimiento-puede-tener-varios-ambitos.md) | Un procedimiento puede tener varios ámbitos convocantes | Propuesta | 2026-09-16 |
| [ADR-0026](ADR-0026-el-aspecto-del-procedimiento-es-un-dato-con-paleta-cerrada.md) | El aspecto del procedimiento es un dato con paleta cerrada | Propuesta | 2026-09-16 |
| [ADR-0027](ADR-0027-sincronizacion-de-snippets-por-contenido.md) | Sincronización de snippets por contenido | Propuesta | 2026-09-19 |
| [ADR-0028](ADR-0028-los-adjuntos-de-una-solicitud-son-ficheros-privados.md) | Los adjuntos de una solicitud son ficheros privados, no medios de WordPress | Propuesta | 2026-09-19 |
| [ADR-0029](ADR-0029-el-catalogo-de-centros-es-externo-y-se-cachea-localmente.md) | El catálogo de centros educativos es un dato maestro externo y se cachea localmente | Aceptada | 2026-09-20 |
| [ADR-0030](ADR-0030-editor-y-ambitos-jerarquicos.md) | Editor y ámbitos organizativos jerárquicos | Propuesta | 2026-09-20 |
