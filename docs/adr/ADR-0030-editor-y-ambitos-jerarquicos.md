---
id: ADR-0030
title: "Editor y ámbitos organizativos jerárquicos"
status: Propuesta
date: 2026-09-20
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0012, ADR-0014, ADR-0015, ADR-0025]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Codex"
  model: "unknown"
---

# ADR-0030: Editor y ámbitos organizativos jerárquicos

## Contexto

El aplicativo versionado gestiona procedimientos como `prc_procedure` y ámbitos como `prc_area` (`src/Prc/PostType/ProcedurePostType.php`, `src/Prc/Taxonomy/ProcedureTaxonomies.php`). La integración con el gestor de formularios anterior no forma parte de este repositorio: el aplicativo nuevo gestiona el contenido por CPT. La ADR-0014 ya distingue rol y ámbito; la ADR-0015 asignaba la gestión a `prc_manager`. El requisito nuevo pide al rol nativo `editor`.

## Decisión

Se conserva `prc_area` jerárquica y la clave `prc_area` del usuario. Cada Editor tiene un único término asignado que concede ese nodo y todos sus descendientes. La meta conserva el formato de lista por compatibilidad; un perfil histórico con varios términos válidos queda sin acceso hasta que administración elija uno. Un ID inválido, un error al obtener descendientes o la ausencia de ámbito concede cero términos. Administración, con `prc_manage_app` o `manage_options`, conserva acceso global. La autorización por objeto sigue en `ProcedureAccess::map_meta_cap`; las consultas del escritorio y el taller usan el ámbito efectivo. Las solicitudes heredan el ámbito de su procedimiento mediante `root_id()`.

Un procedimiento puede conservar **varios** ámbitos convocantes según ADR-0025. Cualquiera de ellos puede editar el contenido compartido. Un Editor solo puede añadir o retirar términos de su subárbol; los términos de otras ramas se conservan al guardar. El formulario de perfil es un selector único, mientras el taller del procedimiento mantiene el árbol de casillas multivalor.

El rol `editor` recibe `prc_manage_procedures`, `prc_review_applications` y las capacidades del CPT que ya recibe `prc_manager`. No recibe `prc_manage_app`, `prc_manage_all_areas`, `manage_options` ni administración de términos. `prc_manager` permanece para compatibilidad; no se retiran roles ni capacidades en cada petición. Las decisiones operativas siguen basadas en capacidades y ámbito. Solo administración ve el selector de `prc_area` al editar un Editor; el guardado exige `manage_options`, `edit_user` y nonce. Otros datos de perfil, como el centro, conservan su tratamiento.

Una persona Editor no puede asignar a un procedimiento términos ajenos a su subárbol: el taller, REST y el editor clásico validan antes de guardar. El procedimiento nuevo recibe de inmediato el único ámbito directo de su autor si no se indicó otro permitido; un procedimiento sin ámbito no se abre por autoría cuando ya es un borrador. La creación REST acotada exige borrador antes de publicar, porque la transición de estado ocurre antes de asignar términos REST; administración puede crear y reparar contenido huérfano. Las metas nuevas `prc_scope_email` y `prc_scope_image_id` guardan respectivamente un correo saneado y un ID de adjunto de imagen; la edición exige administración y nonce. No se añade ACF ni se crea una taxonomía duplicada. La imagen y el correo del sistema histórico requieren inventario de datos antes de una migración real; no se conocen sus claves almacenadas desde este repositorio.

## Consecuencias

Un Editor de un servicio edita procedimientos del servicio y descendientes, pero no padres, hermanos, otras ramas ni procedimientos sin ámbito. Un Editor sin ámbito no edita ninguno. Las cuentas `prc_manager` existentes conservan el funcionamiento con la misma regla del árbol. Ningún cambio de este repositorio local se publica automáticamente.

## Adenda — 2026-09-20

El perfil conserva literalmente valores históricos ambiguos o inválidos al guardar otros campos. Una cadena escalar con un ID válido se reconoce y se normaliza a una lista unitaria al guardar el perfil; varias asignaciones válidas, o una válida junto a otra inexistente, bloquean acceso hasta que administración seleccione explícitamente un único ámbito o «Sin ámbito». Ajustes y diagnóstico enumera las cuentas editoriales con ese estado. No hay usuarios reales que requieran una migración masiva.

`ProcedureAccess::resolve_area_assignment()` calcula una sola vez la asignación final: términos solicitados dentro del subárbol del Editor más los términos ajenos que ya organizaban el procedimiento. Retirar el último término propio se permite si queda un organizador ajeno; se rechaza si el contenido quedaría sin ámbito. Los organizadores ajenos se muestran en solo lectura. Administración mantiene la capacidad de reparar o publicar un procedimiento huérfano, por lo que no se declara una prohibición global para ese estado.
