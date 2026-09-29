---
id: ADR-0020
title: "La subsanación es un plazo del procedimiento y un estado de la solicitud"
status: Propuesta
date: 2026-09-15
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0013, ADR-0014, ADR-0018, ADR-0021, ADR-0022]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-fable-5-1"
---

# ADR-0020: La subsanación es un plazo del procedimiento y un estado de la solicitud

## Estado

Propuesta (2026-09-15). Nada implementado: fija `prc_amend_opens_at`,
`prc_amend_closes_at`, `prc_review_state` y `prc_review_note` antes de
registrarlas.

## Contexto

El encargo pide, con estas palabras, gestionar «los plazos de subsanación y
demás». Hoy la subsanación se llama «reclamaciones» y son **tres piezas que no
se hablan entre sí**, medidas sobre la foto del 2026-09-15 (material en
`.local/`):

1. **Dos fechas de la convocatoria**, «inicio de reclamaciones» y «fin de
   reclamaciones». La página pública las compara con hoy y, si está dentro,
   pinta un aviso parpadeante «abierto el período de reclamaciones» con un
   botón.
2. **Un valor del desplegable manual de estado**, «En período de
   reclamaciones», que alguien tiene que poner y quitar a mano y que no mira
   las fechas. De ahí que el listado de gestión tenga un icono para «cerrada
   con el plazo abierto».
3. **Una sección del formulario de solicitud**, «Reclamaciones», con hasta
   cinco ficheros y un texto libre, que el centro rellena editando su propia
   solicitud. Es la misma pantalla de 94 campos con la que la presentó.

Lo que **no existe** es un estado por solicitud. Quien gestiona publica un
listado provisional en PDF y el centro tiene que buscarse en él para saber si
está admitido, excluido o si le falta algo; si le falta algo, lo averigua por
el PDF o por correo. Quien gestiona, a su vez, no tiene dónde anotar «a este
centro le falta la firma de la dirección»: lo escribe en un campo
«observaciones» solo para administración, que el centro no ve. Y durante el
plazo **cualquier centro** puede editar su solicitud entera, la hayan
reclamado o no.

El estado del procedimiento ya se deriva de las fechas
([ADR-0013](ADR-0013-el-estado-del-procedimiento-se-deriva-de-las-fechas.md)).
Falta decidir qué es la subsanación en ese modelo.

## Problema

¿Cómo se modela la subsanación para que el centro sepa **si tiene que hacer
algo y qué**, quien gestiona sepa **qué ha pedido y a quién**, y solo edite
quien debe, cuando debe?

## Factores de decisión

- **El plazo es del procedimiento.** Lo fija la resolución y es el mismo para
  todos los centros.
- **La petición es de la solicitud.** A un centro se le pide subsanar; a otro
  no. Eso no lo puede decir una fecha.
- **La nota tiene que llegar al centro.** Pedir subsanar sin decir qué es lo
  que hoy obliga a mirar un PDF.
- **Editar fuera de lo pedido es un riesgo.** Una solicitud admitida que se
  reescribe durante la subsanación deja de ser la que se admitió.
- **El estado derivado no puede contradecirse**
  ([ADR-0013](ADR-0013-el-estado-del-procedimiento-se-deriva-de-las-fechas.md)).
- **Sin ficheros en la fase 1.** La documentación adjunta es la fase 2.

## Alternativas consideradas

### Opción 1: solo el plazo, como hoy

Dos fechas; durante el plazo todo centro edita su solicitud.

| Pros | Contras |
|------|---------|
| Dos metas y nada más. | El centro sigue sin saber si le toca; quien gestiona sigue sin dónde decirlo. |
| | Una solicitud admitida se puede reescribir en el plazo. |

Descartada.

### Opción 2: solo un estado por solicitud, sin plazo

Quien gestiona pone `amend` y el centro edita hasta que se lo quiten.

| Pros | Contras |
|------|---------|
| Nada de fechas que cuadrar. | El plazo de subsanación está en la resolución: es un dato del procedimiento, no una decisión por solicitud. |
| | Un `amend` olvidado deja la solicitud editable para siempre. |

Descartada.

### Opción 3: estado por solicitud más ficheros de reclamación

Lo anterior y una subida de ficheros en la solicitud.

Descartada **para la fase 1**: subir ficheros exige decidir dónde viven, quién
los sirve y cómo se protegen. Es la ADR de la documentación adjunta, no esta.

### Opción 4: plazo del procedimiento más estado de la solicitud con nota obligatoria (elegida)

| Pros | Contras |
|------|---------|
| El centro ve en su solicitud «a subsanar» y la nota. | Dos condiciones que cumplir a la vez para editar: plazo y estado. |
| Solo edita quien lo tiene pedido, y solo en plazo. | Sin correo, la nota se ve al entrar; el aviso automático es pendiente. |
| El estado del procedimiento sigue saliendo de fechas. | |

## Decisión

**La subsanación es un plazo del procedimiento y un estado de la solicitud.
Las dos cosas hacen falta para editar.**

### El plazo, en el procedimiento

`prc_amend_opens_at` y `prc_amend_closes_at` (`Y-m-d`, fin inclusive). Con
hoy entre las dos, `ProcedureState::of()` devuelve `amendment` —por encima de
`open` y `closed`, por debajo de `resolved`— y la ficha pública lo dice.
Normalizaciones de `Domain/ProcedureInput`: si falta `prc_amend_closes_at` se
copia `prc_amend_opens_at`; un plazo de subsanación que empiece **antes** del
cierre del plazo de solicitud es un error de validación y no se guarda.

### El estado, en la solicitud

`prc_review_state`, lista cerrada `ApplicationMetaKeys::review_states()`:

| Valor | Etiqueta | Quién lo pone |
|---|---|---|
| `submitted` | Presentada | El aplicativo, al presentar |
| `amend` | A subsanar | Quien gestiona, **con nota obligatoria** |
| `admitted` | Admitida | Quien gestiona |
| `excluded` | Excluida | Quien gestiona, **con nota obligatoria** |

`prc_review_note` es el texto que el centro lee. Pedir subsanar o excluir sin
nota es un error de validación: el POST vuelve con aviso y no cambia nada.
Admitir puede llevar nota o no.

### Quién edita y cuándo

| Estado del procedimiento | Estado de la solicitud | ¿El centro edita? |
|---|---|---|
| `open` | cualquiera | Sí |
| `amendment` | `amend` | Sí |
| `amendment` | otro | No |
| cualquier otro | cualquiera | No |

La regla vive en `ProcedureAccess` y por ahí pasa `map_meta_cap`, así que
alcanza al POST de la pantalla `apply` y a cualquier otra vía. La pantalla
explica por qué no se puede: «El plazo de subsanación está abierto, pero a su
solicitud no se le ha pedido subsanar».

Cuando el centro guarda en subsanación, **la solicitud sigue en `amend`**
hasta que quien gestiona la admita o la excluya. La tabla del panel
«Solicitudes» enseña la fecha de la última modificación, que es como se ve
quién ha respondido. Un `amend` que llegue al fin del plazo sin respuesta se
queda en `amend`: lo resuelve quien gestiona, con `admitted` o `excluded`.

### Quien gestiona

Admitir, pedir subsanar y excluir son tres acciones del panel «Solicitudes»
del taller, con `prc_review_applications` acotado por ámbito
([ADR-0014](ADR-0014-el-ambito-es-un-alcance-no-un-rol.md)), POST con nonce y
confirmación. Se pueden hacer en cualquier estado del procedimiento salvo
`draft` y `archived`; el plazo de subsanación limita al centro, no a quien
revisa.

## Consecuencias

### Positivas

- El centro abre su solicitud y lee **si le toca y qué**: el estado y la nota
  están en la misma pantalla, no en un PDF.
- Quien gestiona tiene dónde escribir lo que hoy va en observaciones que el
  centro no ve, y el CSV lo lleva.
- Una solicitud admitida no se puede reescribir durante la subsanación.
- «Cerrada con el plazo abierto» deja de existir: el estado sale de las
  fechas.

### Negativas

- **La nota no viaja.** Sin correo, el centro se entera al entrar. El aviso
  automático al cambiar el estado de revisión está en los pendientes de la
  SDD, y hasta entonces quien gestiona avisa por su cuenta.
- **Dos condiciones para editar** son dos cosas que probar y dos que
  explicar. Un centro con `amend` fuera de plazo no edita, y tiene que
  entender por qué.
- **No hay ficheros.** Si lo que falta es un documento firmado, el centro no
  puede aportarlo aquí en la fase 1: solo corregir lo que la solicitud lleva.
  Es la limitación más visible frente a hoy y es deliberada.
- **`amend` no caduca solo.** Una solicitud a la que nadie vuelva se queda «a
  subsanar» para siempre en el listado del centro.
- **La nota es texto libre.** Nada impide que lleve datos que no deberían
  estar ahí.

### Neutras

- El listado provisional sigue existiendo como enlace
  ([ADR-0021](ADR-0021-la-resolucion-y-los-listados-son-enlaces.md)); el
  estado de revisión no lo sustituye, lo complementa.
- La subsanación no es un estado de la solicitud «pendiente de documentación»:
  eso llegará, si llega, con la documentación adjunta.
