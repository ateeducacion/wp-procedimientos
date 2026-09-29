---
id: ADR-0023
title: "El estado histórico cierra la edición"
status: Propuesta
date: 2026-09-15
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0005, ADR-0007, ADR-0010, ADR-0013, ADR-0014, ADR-0015, ADR-0018, ADR-0022, ADR-0024]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-fable-5-1"
---

# ADR-0023: El estado histórico cierra la edición

## Estado

Propuesta (2026-09-15). Nada implementado: fija `prc_archived` y lo que
`ProcedureAccess` hace con ella antes de escribirlo.

## Contexto

Hoy «Histórica» es **un valor más del desplegable manual de estado** de la
convocatoria, junto a «Abierta», «Cerrada», «Borrador», «Pendiente de
documentación», «Periodo de reclamaciones» y «Pilotaje». Medido sobre la foto
del 2026-09-15 (material en `.local/`): unas 300 convocatorias en cuatro
cursos, todas editables por quien tenga su ámbito, tengan el curso que tengan.
Marcar una como histórica cambia el icono del listado y la saca de la
portada; **no cambia quién puede editarla**. Una convocatoria de hace tres
cursos se edita igual que una abierta, y nadie sabe si alguien la ha tocado.

El estado del procedimiento ya se deriva de las fechas
([ADR-0013](ADR-0013-el-estado-del-procedimiento-se-deriva-de-las-fechas.md)),
y a propósito ninguna fecha cierra la edición: en `closed` y `resolved` quien
gestiona sigue editando enlaces y fechas y gestionando solicitudes, porque es
cuando llegan la resolución y los listados
([ADR-0005](ADR-0005-politica-de-edicion-y-auditoria.md)). Eso deja el hueco:
no hay forma de decir «esto ya está, no se toca más».

El aplicativo de eventos lo resolvió con una marca de archivado que pone el
área y quita administración, y tuvo que distinguirla de otra marca de
contenido migrado ([ADR-0010](ADR-0010-se-parte-del-esqueleto-del-aplicativo-de-eventos.md)).
Aquí no hay migración en la fase 1
([ADR-0024](ADR-0024-las-solicitudes-de-hoy-no-se-migran.md)), así que hay una
marca, no dos.

## Problema

¿Cómo se cierra un procedimiento a la edición de forma explícita, sin
despublicarlo ni esconderlo, y con vuelta atrás para quien administra?

## Factores de decisión

- **Alguien tiene que poder cerrar la puerta a mano.** Ninguna fecha distingue
  «resuelto la semana pasada, falta corregir un enlace» de «esto es de hace
  tres cursos».
- **Y quien la cierra es quien gestiona el ámbito.** Cerrar tu propio
  procedimiento no es un trámite que se pide.
- **Y alguien tiene que poder abrirla.** Cerrado para siempre no puede
  significar cerrado también para arreglar una errata.
- **La ficha pública no cambia.** Es un cierre de edición, no un
  despublicado: la dirección sigue respondiendo.
- **El cierre alcanza a las solicitudes**, que cuelgan del procedimiento
  ([ADR-0018](ADR-0018-la-solicitud-es-un-contenido-del-procedimiento.md)).
- **El centro sigue viendo su solicitud.** Cerrar la edición no es borrar el
  histórico del centro.

## Alternativas consideradas

### Opción 1: derivarlo del curso escolar

Un procedimiento cuyo término de `prc_course` no sea el curso actual se
cierra solo.

| Pros | Contras |
|------|---------|
| Cero decisiones que alguien pueda olvidar. | Un procedimiento resuelto en julio con el listado definitivo pendiente se cierra el 1 de septiembre, en mitad del trabajo. |
| | «Curso actual» es una regla de calendario que habría que escribir y defender; el término del curso es un dato, no una fecha. |

Descartada.

### Opción 2: despublicar

Descartada: cambia la ficha pública, que está enlazada desde resoluciones y
circulares. Cerrar la edición y quitar la página son dos decisiones distintas.

### Opción 3: enviar a la papelera

Descartada: la papelera se vacía sola a los treinta días
([ADR-0007](ADR-0007-borrar-es-enviar-a-la-papelera.md)). Lo que se quiere
conservar no vive donde WordPress borra.

### Opción 4: una meta `prc_archived` que marca quien gestiona y desmarca administración (elegida)

| Pros | Contras |
|------|---------|
| Explícito, reversible en un clic, sin tocar la ficha pública. | Una decisión que alguien tiene que tomar; un procedimiento que nadie marque no se cierra nunca. |
| El cierre baja a las solicitudes sin copiar nada. | Quien gestiona puede dejarse fuera de su propio procedimiento por error. |

## Decisión

### 1. La marca es `prc_archived` y es un estado derivado

`ProcedureMetaKeys::ARCHIVED`, booleana, registrada con `sanitize` y
`auth_callback`. A diferencia del aplicativo de eventos, **aquí el histórico
sí es uno de los estados** que devuelve `ProcedureState::of()`: `archived`,
por encima de todo salvo `draft`. Un procedimiento histórico se lista con esa
etiqueta y no como «resuelto» o «cerrado», porque para el centro y para el
público lo que importa es que ya no cambia.

### 2. La marca quien gestiona; la desmarca administración

| Acción | Quién |
|---|---|
| **Marcar** como histórico | Quien tiene `prc_manage_procedures` con el ámbito del procedimiento ([ADR-0014](ADR-0014-el-ambito-es-un-alcance-no-un-rol.md)) |
| **Desmarcar** | Solo `prc_manage_app` |
| **Editar** un procedimiento marcado | Nadie desde el taller; `prc_manage_app` desde el escritorio para corregir una errata |

**Un candado que abre quien lo cerró no es un candado.** La asimetría es el
punto: si quien gestiona pudiera desmarcar, el cierre sería una preferencia.
Antes de marcar, el taller lo pide con SweetAlert2 y dice con todas las
letras que **no hay vuelta atrás sin administración**; sin JavaScript, el
formulario lleva una casilla de confirmación.

### 3. Dónde se comprueba

| Camino | Qué comprueba |
|---|---|
| El POST del panel «Publicación», al marcar | `ProcedureAccess::can_archive()`: la regla del ámbito **sin** el cierre encima |
| El POST al desmarcar | `ProcedureAccess::can_unarchive()`: `prc_manage_app` |
| El `auth_callback` de la meta | `can_toggle_archived()`: con el procedimiento abierto, quien gestiona; cerrado, solo administración. Mira el estado de hoy, no el valor que llega, porque un `auth_callback` no recibe el valor |
| `can_edit()` de procedimiento **y de solicitud** | Con `prc_archived` en el procedimiento, `false` salvo `prc_manage_app`. Por ahí pasa `map_meta_cap`, así que alcanza al taller, al escritorio y a la REST |

Marcar pregunta por la regla del ámbito y no por `can_edit()`, porque lo
primero que hace la marca es quitar `can_edit()`.

### 4. Lo que sigue funcionando

- **La ficha pública** no cambia: mismo `post_status`, misma dirección, mismo
  HTML.
- **El taller se abre**, en solo lectura, con el motivo arriba
  (`ProcedureAccess::why_not_editable()`): «Este procedimiento está marcado
  como histórico: se puede consultar y exportar, pero ya no se edita. Para
  volver a abrirlo, pídalo a quien administre el aplicativo».
- **El CSV se exporta.** Consultar no es editar.
- **El centro ve su solicitud** en `mi-centro` y en `solicitud`, con su
  estado de revisión y su nota. No la edita, pero tampoco la editaría: un
  procedimiento histórico no está `open` ni en `amendment`.
- **Admitir, excluir y pedir subsanar** no se pueden: son escrituras sobre
  solicitudes de un procedimiento cerrado.

### 5. Convivencia con las fechas

No hay dos reglas: hay el acotado por ámbito y un cierre explícito por encima.
`can_edit()` evalúa primero el ámbito y después la marca; basta un «no».
`ProcedureState` sigue saliendo de las fechas para todo lo demás, y nunca
decide permisos.

## Consecuencias

### Positivas

- Se puede cerrar un procedimiento **y decirlo**, con un motivo que se lee en
  pantalla.
- El cierre no se rodea: vive en `can_edit()` y alcanza a todas las vías con
  una línea.
- Baja solo a las solicitudes, porque `can_edit()` de la solicitud pregunta
  por su padre.
- No se pierde nada de vista: se lista, se abre, se exporta.
- Reversible por administración sin tocar la base de datos.

### Negativas

- **Es una decisión que alguien tiene que tomar.** El aplicativo no avisa de
  que un procedimiento de hace tres cursos sigue abierto.
- **Quien gestiona puede dejarse fuera por error** y necesita a
  administración. Por eso el aviso no es opcional.
- **No queda registro de quién marcó ni cuándo.** La meta es un booleano. La
  auditoría de la [ADR-0005](ADR-0005-politica-de-edicion-y-auditoria.md), cuando
  exista, tiene que incluir marcar y desmarcar.
- **`prc_manage_all_areas` no exime del cierre.** Lo que exime es
  `prc_manage_app`, que es de quien administra el aplicativo, no de quien lo
  ve todo.

### Neutras

- La marca no cambia la publicación: un histórico puede estar publicado o en
  borrador, y eso lo decide `post_status`.
- Los roles no se nombran: se comprueban capacidades
  ([ADR-0015](ADR-0015-el-aplicativo-comprueba-capacidades-no-roles.md)).
- Si un día se migran las convocatorias del sistema anterior
  ([ADR-0024](ADR-0024-las-solicitudes-de-hoy-no-se-migran.md)), esa ADR
  decidirá si llegan marcadas y si hace falta una segunda marca para el
  contenido congelado. Esta no lo prejuzga.
