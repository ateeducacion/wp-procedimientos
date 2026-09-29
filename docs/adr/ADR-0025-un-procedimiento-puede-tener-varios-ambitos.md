---
id: ADR-0025
title: "Un procedimiento puede tener varios ámbitos convocantes"
status: Propuesta
date: 2026-09-16
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0012, ADR-0014, ADR-0015]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0025: Un procedimiento puede tener varios ámbitos convocantes

## Estado

Propuesta (2026-09-16). Corrige el modelo escrito el día anterior en la
[SDD-0001](../sdd/SDD-0001-arquitectura-del-aplicativo-de-procedimientos.md),
que guardaba un ámbito por procedimiento.

## Contexto

La [ADR-0012](ADR-0012-una-taxonomia-por-dimension.md) hizo del ámbito una
taxonomía, y la [ADR-0014](ADR-0014-el-ambito-es-un-alcance-no-un-rol.md), el
alcance de quien gestiona. Una taxonomía admite varios términos por entrada, y
el guardián ya estaba escrito para varios: `Access\ProcedureAccess::post_areas()`
devuelve una **lista** y se cruza con `scope_areas()` por intersección. Lo que
guardaba uno solo era la capa de encima: `Domain\ProcedureInput` recibía un
`area` entero y `PublicFront\ProcedureEditor` hacía
`wp_set_object_terms( …, array( $area ) )`.

Al calcar la interfaz del sistema que se sustituye —foto del 2026-09-15, en
`.local/`, que no se versiona ([ADR-0009](ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md))—
aparecen dos hechos:

1. Hay convocatorias vivas con **dos y con tres** ámbitos convocantes.
2. La pantalla de alta de ese sistema no pinta un desplegable, sino un **árbol
   de casillas**: el marcado dice, por sí solo, que la respuesta es una lista.

Un procedimiento convocado por dos servicios no es un caso raro que se pueda
mandar a «pídalo a quien administre»: es cómo se convocan los programas que
comparten dos áreas.

## Problema

¿Un procedimiento pertenece a un ámbito o a varios, y qué significa eso para
quién puede editarlo y revisar sus solicitudes?

## Factores de decisión

- **Fidelidad al calco**: la persona responsable pidió que la visualización
  fuese calcada, y la pantalla de alta es una de las seis nombradas.
- **El ámbito es el permiso** ([ADR-0014](ADR-0014-el-ambito-es-un-alcance-no-un-rol.md)):
  lo que se decida aquí no es una etiqueta, es quién abre el taller.
- **Coste**: el guardián ya trabaja con listas; el cambio es de la capa de
  entrada.
- **Migración**: las convocatorias de hoy no se traen ([ADR-0024](ADR-0024-las-solicitudes-de-hoy-no-se-migran.md)),
  así que no hay datos que convertir.

## Alternativas consideradas

### Opción 1: Un ámbito, y el resto se apaña

Se conserva `area` como entero. Un programa de dos servicios se da de alta bajo
uno de ellos y el otro pide acceso a quien administra, o se le añade ese ámbito
al perfil.

| | |
|---|---|
| A favor | Nada que cambiar. La pregunta «¿de quién es esto?» tiene una sola respuesta. |
| En contra | Divergencia visible del calco en una de las seis pantallas pedidas. Hincha los perfiles: para que el segundo servicio entre en un procedimiento hay que darle **todo** el ámbito del primero, que es mucho más de lo que necesitaba. Y la ficha pública miente sobre quién convoca. |

### Opción 2: Un ámbito «principal» más una lista de acompañantes

Se guarda un término principal —el que manda en permisos— y una meta con los
demás, que solo se pintan.

| | |
|---|---|
| A favor | Conserva «un dueño» para las decisiones difíciles. |
| En contra | Dos sitios donde vive el mismo dato, y el de la meta se sale de la taxonomía: deja de poder filtrarse, contarse y acotarse con lo que WordPress ya trae. La distinción entre convocar y acompañar no está en el sistema que se calca ni nadie la ha pedido: es una jerarquía inventada por el modelo. |

### Opción 3: Varios ámbitos, todos iguales

`prc_area` admite varios términos por procedimiento. El permiso es la
intersección que el guardián ya calcula.

| | |
|---|---|
| A favor | Es lo que la taxonomía sabe hacer y lo que el guardián ya hacía. Un único sitio donde vive el dato. El calco sale sin divergencias. |
| En contra | Estrena una regla de permisos que antes no se podía dar: **basta uno** de los ámbitos para gestionarlo entero. Y quien edita puede añadir ámbitos, así que el control de qué se puede marcar deja de ser cosmético. |

## Decisión

Haremos la opción 3: **un procedimiento se convoca desde uno o varios
ámbitos**, todos del mismo rango.

- `Domain\ProcedureInput` recibe `areas`, una lista de identificadores de
  término, y exige al menos uno.
- `PublicFront\ProcedureEditor::may_set_area()` comprueba **todos** los
  marcados contra `ProcedureAccess::scope_areas()`, y basta uno ajeno para
  rechazar el envío entero. Se salta la comprobación quien tenga
  `prc_manage_all_areas`.
- El permiso queda como estaba: `ProcedureAccess` cruza los ámbitos del
  procedimiento con los de la persona por intersección, así que **pertenecer a
  uno basta** para editarlo, publicarlo, archivarlo y revisar sus solicitudes.

## Consecuencias

### Positivas

- Dos servicios convocan juntos sin que nadie toque perfiles ni reparta
  capacidades: cada uno entra por su ámbito y ninguno tiene que ver lo del
  otro.
- La pantalla de alta se calca tal cual, con sus casillas.
- El listado y la ficha dicen quién convoca de verdad, en vez de la mitad.

### Negativas

- **El permiso es más ancho de lo que parece.** Quien esté en cualquiera de
  los ámbitos edita el procedimiento entero, borra sus preguntas, cambia sus
  fechas y ve todas sus solicitudes, incluidas las que responden a la parte del
  otro ámbito. No hay permiso parcial y no se pretende que lo haya: la unidad
  de permiso es el procedimiento.
- **Marcar un ámbito es dar acceso**, y eso ocurre desde la pantalla de un
  procedimiento, no desde la administración de usuarios. La comprobación de
  `may_set_area()` es lo único que separa «me equivoqué de casilla» de «acabo
  de meter a otro servicio en mis solicitudes»; si esa comprobación se cae, no
  hay una segunda barrera detrás.
- Quien gestiona todos los ámbitos **sí** puede repartir un procedimiento entre
  ámbitos ajenos, y a partir de ahí no puede deshacerlo sin quitar el ámbito, lo
  que a su vez puede dejar sin acceso a quien ya estaba trabajando en él.
- Un procedimiento sin ningún ámbito deja de ser representable en el alta, así
  que la regla de cortesía de `ProcedureAccess::in_scope()` —el recién creado y
  todavía sin ámbito lo edita quien lo creó— solo se alcanza por rutas que no
  son la pantalla.

### Neutras

- No hay datos que migrar: las convocatorias de hoy no se traen
  ([ADR-0024](ADR-0024-las-solicitudes-de-hoy-no-se-migran.md)).
- El filtro por ámbito de la portada no cambia: un procedimiento de dos
  ámbitos sale en los dos filtros, que es lo que se espera de él.
