---
id: ADR-0005
title: "Política de edición y auditoría"
status: Propuesta
date: 2026-09-15
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0001, ADR-0013, ADR-0014, ADR-0018, ADR-0019, ADR-0023]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-fable-5-1"
---

# ADR-0005: Política de edición y auditoría

## Estado

Propuesta (2026-09-15). Decidida antes de implementarla: no existe todavía
`Access/ProcedureAccess` ni ningún módulo de auditoría (ver «Consecuencias»).

## Contexto

Hoy no hay política de edición, hay un formulario con dos roles escritos en
sus opciones.

- **Quién puede crear o reabrir una convocatoria lo decide el propio
  formulario** del gestor de formularios: la lista de quién puede rellenarlo
  y la de quién puede editar un envío están en sus ajustes, y en las dos hay
  dos roles.
- **Quién ve qué convocatorias lo decide el navegador.** El listado de gestión
  pinta todas las filas y un fragmento de código oculta con JavaScript las
  que no son del ámbito de quien mira; la versión en servidor del mismo
  filtro está desactivada. Cualquiera con sesión ve todas en el HTML.
- **No hay límite temporal**: una convocatoria de hace cuatro cursos se puede
  reeditar hoy, y se edita con el mismo formulario de 123 campos que cuando
  estaba abierta —incluidas las preguntas que cientos de centros ya
  contestaron—.
- **No queda rastro de quién cambió qué.** Las revisiones de WordPress dicen
  que la plantilla se volvió a interpolar, no qué dato cambió.

El aplicativo nuevo sí reparte capacidades reales
([ADR-0014](ADR-0014-el-ambito-es-un-alcance-no-un-rol.md)), y eso obliga a
decidir hasta dónde llegan. Y tiene algo que eventos no tenía en la fase 1:
**las solicitudes están dentro** ([ADR-0018](ADR-0018-la-solicitud-es-un-contenido-del-procedimiento.md)),
y una solicitud es una respuesta a las preguntas concretas de un
procedimiento concreto.

## Problema

¿Quién puede tocar un procedimiento, qué parte de él y hasta cuándo, y qué
queda registrado de cada cambio?

## Factores de decisión

- **Integridad de lo publicado.** Un procedimiento publicado es un compromiso
  con los centros que solicitan: plazo, preguntas, a quién va dirigido. Con
  17.000 solicitudes acumuladas en cuatro cursos, cambiar una pregunta después
  de que la hayan contestado deja las respuestas sin sentido. El sistema
  actual lo sufre: un formulario compartido acumula columnas «solo para la
  convocatoria X» y hubo que limpiar a mano los registros que se colaron.
- **Autonomía del ámbito.** Que un ámbito publique lo suyo sin pedir turno es
  la razón de ser del aplicativo.
- **El trabajo no acaba con el plazo.** Después del cierre vienen la
  subsanación, la resolución y los listados, y los publica el ámbito.
- **Rendir cuentas.** Cuando un plazo cambia, tiene que poderse decir quién lo
  cambió y cuándo.
- **Una regla, no un motor de reglas.**

## Alternativas consideradas

### Opción 1: acotado por ámbito sin límite temporal, con un cierre explícito por encima

Lo que eligió eventos. `ProcedureAccess::can_edit()` compara los ámbitos del
perfil con el del procedimiento, y lo único que cierra la puerta es la marca
`prc_archived` ([ADR-0023](ADR-0023-el-estado-historico-cierra-la-edicion.md)).

- Pros: mínimo código, máxima autonomía.
- Contras: **aquí no basta.** En eventos lo que se edita después del acto es
  contenido —vídeos, fotos— y nadie ha contestado a nada. Aquí, con el plazo
  cerrado, hay solicitudes que dependen de las preguntas, de la audiencia y de
  si se pedía coordinación. Dejarlo todo abierto hasta que alguien pulse
  «archivar» es dejar abierta la puerta a que una convocatoria resuelta cambie
  lo que se preguntó.

### Opción 2: bloqueo al publicar

- Contras: las fechas de subsanación y los enlaces a la resolución se conocen
  **después** de publicar. Descartada.

### Opción 3: el ámbito manda mientras el plazo esté abierto; después, nada

- Contras: el corte cae justo donde empieza el trabajo: subsanación,
  resolución, listados, revisión. Descartada.

### Opción 4: opción 1 más un cierre **parcial** que dicta el estado derivado — ELEGIDA

El ámbito manda en su ámbito sin límite de calendario, pero **qué paneles
aceptan un POST** lo dice `ProcedureState::of()`
([ADR-0013](ADR-0013-el-estado-del-procedimiento-se-deriva-de-las-fechas.md)):
la sustancia que las solicitudes contestan se congela cuando el plazo cierra;
lo que viene después sigue abierto.

- Pros: es la tabla «Lo que cada estado permite» de la SDD, ni más ni menos;
  una regla por panel, sin opción que configurar; la integridad protege
  exactamente lo que las solicitudes referencian.
- Contras: hay dos guardianes con dos preguntas distintas —«¿puede esta
  persona tocar este procedimiento?» y «¿acepta este panel un cambio hoy?»—
  y quien lea el código tiene que saber cuál es cuál.

## Decisión

**Haremos la opción 4.** Quién puede tocar un procedimiento lo dice el
ámbito; qué parte, el estado; hasta cuándo, el botón de archivar.

### Quién

| Quién | Qué puede hacer |
|---|---|
| `prc_manage_procedures` con el ámbito del procedimiento —o un antecesor— en `prc_area` del perfil | crear, editar, publicar y archivar el procedimiento; con `prc_review_applications`, revisar y exportar sus solicitudes |
| `prc_manage_procedures` de otro ámbito | nada |
| `prc_manage_procedures` sin ámbito en el perfil | nada (fail-closed) |
| Autoría de un procedimiento que todavía no tiene ámbito | editarlo, para poder ponérselo |
| `prc_manage_all_areas` | lo anterior en todos los ámbitos |
| `prc_manage_app` | ajustes, diagnóstico y **desarchivar** |
| `prc_apply` con código de centro | presentar y editar **la solicitud de su centro**, en `open` siempre y en `amendment` solo si está `amend` ([ADR-0020](ADR-0020-la-subsanacion-es-un-plazo-y-un-estado.md)) |

La regla se aplica en **`Access/ProcedureAccess`, el único guardián**, sobre
`map_meta_cap` de `edit_post`, `delete_post`, `publish_post` y `read_post` de
los dos tipos de contenido, no sobre el listado del escritorio: esconder algo
con `pre_get_posts` deja abiertos `post.php?post=N`, la edición rápida, las
acciones en bloque y la REST API. El acotado del listado se hace también,
pero es comodidad, no protección. La denegación se explica en castellano
desde un solo sitio, `ProcedureAccess::why_not_editable()`.

### Qué parte

`ProcedureAccess::can_edit()` **no mira las fechas**: solo `prc_archived`.
Qué panel del taller acepta un POST lo decide `PublicFront/ProcedureEditor`
con el estado derivado:

| Estado | Datos y Preguntas | Fechas y Enlaces | Solicitudes | Publicación |
|---|---|---|---|---|
| `draft`, `upcoming`, `open` | sí | sí | sí (en `open`) | sí |
| `closed`, `amendment`, `resolved` | **no** | sí | sí | archivar |
| `archived` | no | no | no | desarchivar (`prc_manage_app`) |

Un POST a un panel cerrado se rechaza con aviso, aunque el nonce y la
capacidad sean válidos. Las fechas siguen abiertas en `amendment` y
`resolved` —la SDD las cierra ahí— porque un plazo de subsanación que hay que
alargar se alarga mientras corre, no después. Para corregir una errata del
título con el plazo cerrado, administración adelanta la fecha de cierre,
corrige y la repone: incómodo a propósito, porque es raro y deja rastro.

### Qué se audita

Toda mutación permitida sobre un `prc_procedure` —creación, edición, cambio
de estado de publicación, archivo, papelera— y todo cambio de
`prc_review_state` de una solicitud dejan registrado actor (`user_id`),
momento (UTC, ISO 8601) y qué cambió (clave, valor anterior, valor nuevo). Se
guarda en meta del procedimiento, no en una tabla propia: el repositorio no
es un plugin y no tiene dónde correr un `dbDelta`
([ADR-0001](ADR-0001-repo-entorno-desarrollo-no-plugin.md)). El registro tiene
tope por procedimiento; al llenarse se descartan las entradas más antiguas.

No se auditan las lecturas ni las revisiones de `post_content`, que WordPress
ya guarda.

## Consecuencias

### Positivas

- El ámbito no pide turno para su trabajo del día a día ni para lo que viene
  después del plazo, que es cuando más se toca el procedimiento.
- Una solicitud responde siempre a las preguntas que se le hicieron: no hay
  respuesta huérfana de su pregunta mientras el procedimiento no esté en
  borrador.
- Cuando un plazo o el estado de una solicitud cambian, hay a quién preguntar.
- El estado protege el filtro y el listado en servidor, no en el navegador.

### Negativas

- **Dos guardianes.** `ProcedureAccess` dice quién y `ProcedureEditor` dice
  qué panel; un panel nuevo que olvide preguntar al estado queda abierto para
  siempre. Es el tipo de detalle que se olvida en el panel número seis.
- **Un procedimiento que nadie archiva se sigue tocando** en fechas y enlaces
  indefinidamente. Lo compensa la auditoría, y la auditoría **no está en la
  lista de ficheros de la fase 0** de la SDD: es lo que falta por escribir.
- **La auditoría en meta tiene tope y no es un archivo histórico.** Si hace
  falta conservarlo todo, hay que cambiar de almacén y es otra ADR.
- **Corregir una errata con el plazo cerrado es un rodeo.** Si resulta
  frecuente, se abre el título —y solo el título— en una adenda.

### Neutras

- El estado derivado no toca `map_meta_cap`: WordPress sigue diciendo que
  quien puede editar, puede editar. Es el taller quien cierra paneles.
- La política de la solicitud —cuándo la edita el centro— no es de esta ADR:
  la fija la [ADR-0020](ADR-0020-la-subsanacion-es-un-plazo-y-un-estado.md).
