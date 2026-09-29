---
id: ADR-0013
title: "El estado del procedimiento se deriva de las fechas"
status: Propuesta
date: 2026-09-15
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0011, ADR-0012, ADR-0020, ADR-0021, ADR-0023]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-fable-5-1"
---

# ADR-0013: El estado del procedimiento se deriva de las fechas

## Estado

Propuesta (2026-09-15). Función pura `Domain/ProcedureState::of()`, con test
por estado y por límite de día.

## Contexto

Hoy el estado de una convocatoria es **un desplegable de siete valores** de la
entrada del formulario —borrador, abierta, cerrada, histórica, pendiente de
documentación, periodo de reclamaciones, pilotaje— que una acción escribe en
una categoría del constructor de páginas. Nadie lo cambia solo: la guía de
alta dice que toda convocatoria nueva «debe crearse en borrador» y pasar a
abierta «cuando se publique», y el paso de abierta a cerrada lo hace una
persona el día que se acuerda.

**El sitio ya sabe que el dato miente.** La pantalla de gestión lleva una
leyenda de **ocho iconos**, y no siete, porque cruza el estado con la fecha
límite de solicitud: además de los esperables hay uno para «abierta con
plazo finalizado» y otro para «cerrada con plazo abierto». Es decir, la
contradicción entre el estado y el calendario es tan frecuente que tiene
icono propio. Todo esto está medido sobre el material de `.local/`, que no se
publica ([ADR-0009](ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md)).

Y, a diferencia del aplicativo de eventos, **las fechas ya existen como
fechas**: la sección «calendario» del formulario tiene inicio y fin del plazo
de solicitud, inicio y fin del periodo de reclamaciones, y fechas límite de
documentación. Lo que falta no es el dato: es que el estado se lea de él.

Dos observaciones más:

- **El borrador ya tiene su sitio en WordPress.** El formulario lleva además
  un campo «estado de la publicación» que alimenta `post_status`. «Borrador»
  en el desplegable es un segundo vocabulario para lo mismo.
- **Dos de los siete valores no son estados del procedimiento.** «Pendiente
  de documentación» describe lo que le falta a una solicitud, no a la
  convocatoria; «pilotaje» es «se ve pero no cuenta», que es exactamente no
  publicar.

## Problema

¿El estado de un procedimiento es un dato que alguien mantiene, o un cálculo
sobre sus fechas, su listado definitivo y su marca de histórico?

## Factores de decisión

- **Un estado que hay que acordarse de cambiar se queda obsoleto.** Los dos
  iconos de contradicción son la prueba.
- **El dato de partida ya se pide y ya es fecha.** No hay migración de
  formato que hacer.
- **El borrador es `post_status`**, y el histórico es una decisión de quien
  gestiona, no del calendario
  ([ADR-0023](ADR-0023-el-estado-historico-cierra-la-edicion.md)).
- **La subsanación es un plazo**, con fechas propias, y durante ese plazo el
  centro edita solo si se le ha pedido
  ([ADR-0020](ADR-0020-la-subsanacion-es-un-plazo-y-un-estado.md)).
- **«Resuelto» no es una fecha: es que exista el listado definitivo**
  ([ADR-0021](ADR-0021-la-resolucion-y-los-listados-son-enlaces.md)).
- **Consultabilidad.** Un valor calculado no se filtra con `tax_query`; hay
  que decir con qué se sustituye.

## Alternativas consideradas

### Opción 1: mantener el estado como dato que alguien cambia (statu quo)

| Pros | Contras |
|---|---|
| Se filtra por término sin escribir nada. | Es lo que ya se probó y produjo ocho iconos. |
| Permite un estado que las fechas no expresan. | Obliga a una tarea recurrente que nadie tiene asignada: hoy la hace quien se acuerda. |

### Opción 2: estado guardado en meta, recalculado por un cron diario

| Pros | Contras |
|---|---|
| Consultable con `meta_query` por una sola clave. | Dos fuentes de verdad para el mismo hecho. |
| No depende de que nadie se acuerde. | WP-Cron se dispara con las visitas: el estado cambia cuando alguien entra, no a medianoche. |
| | Una pieza más que registrar, vigilar y explicar cuando no coincide. |

### Opción 3: derivarlo, sin guardarlo (elegida)

Una función pura que recibe las metas, el día de hoy y si está publicado.

| Pros | Contras |
|---|---|
| No se puede quedar obsoleto: no hay nada que actualizar. | Se filtra por fechas, no por estado. |
| Una sola fuente de verdad: las fechas que ya se publican. | No puede expresar «cancelado» ni «aplazado». |
| Se prueba con PHPUnit sin WordPress, pasándole `$today`. | |

### Opción 4: derivado más un estado editable que lo sobrescribe

Para «cancelado» o «suspendido». Es la respuesta el día que alguien lo pida;
hoy no está entre los siete valores del desplegable y nadie lo ha pedido. Se
deja anotada: la escotilla es una meta y una rama, no rehacer nada.

## Decisión

**El estado del procedimiento se calcula. No se guarda, no es un término y
no hay desplegable que rellenar.**

`Prc\Domain\ProcedureState::of( array $meta, string $today, bool $published )`
devuelve una de las constantes de `ProcedureMetaKeys::states()`, evaluando
**en este orden** y quedándose con la primera que se cumple:

| Orden | Estado | Etiqueta | Condición |
|---|---|---|---|
| 1 | `draft` | Borrador | `$published` es falso (`post_status !== 'publish'`) |
| 2 | `archived` | Histórico | `prc_archived` |
| 3 | `resolved` | Resuelto | `prc_final_list_url` no vacía |
| 4 | `amendment` | En subsanación | `prc_amend_opens_at <= hoy <= prc_amend_closes_at` |
| 5 | `open` | Abierto | `prc_opens_at <= hoy <= prc_closes_at` |
| 6 | `upcoming` | Próximo | `hoy < prc_opens_at`, o sin fecha de apertura |
| 7 | `closed` | Cerrado | El resto: plazo pasado, sin subsanación en curso ni listado definitivo |

Son **seis estados calculados más el borrador**, que es `post_status` y
solo entra en la función para que la pantalla tenga una única palabra que
pintar.

El orden importa y está elegido:

- **Histórico gana a todo lo demás**, porque es una decisión de quien
  gestiona que cierra la edición ([ADR-0023](ADR-0023-el-estado-historico-cierra-la-edicion.md)).
- **Resuelto gana a los plazos**: publicado el listado definitivo, da igual
  que el calendario diga otra cosa.
- **Subsanación gana a abierto**: si por error se solapan los dos plazos, el
  centro edita bajo la regla más restrictiva (solo si está `amend`).

Normalizaciones al guardar, en `Domain/ProcedureInput`, aplicadas **al dato
guardado y no solo en la función** para que consulta y pantalla digan lo
mismo: si falta `prc_closes_at` se copia `prc_opens_at`; si falta
`prc_amend_closes_at` se copia `prc_amend_opens_at`; un plazo de subsanación
que empieza antes del cierre del de solicitud es un **error de validación**,
no una normalización. La comparación es por día, en `Y-m-d`, con el «hoy»
tomado en la **zona horaria del sitio** (`wp_date( 'Y-m-d' )`): un plazo que
acaba el 25 acaba el 25 a las 23:59 de quien solicita, no en UTC.

Un procedimiento **sin fecha de apertura es «próximo»**: se publica igual y
nadie puede solicitar hasta que tenga plazo.

Filtrar por estado es filtrar por fechas. Para quien escriba una consulta:

| Estado | `meta_query` (sobre publicados y no archivados) |
|---|---|
| Resuelto | `prc_final_list_url != ''` |
| En subsanación | `prc_amend_opens_at <= hoy` **y** `prc_amend_closes_at >= hoy` |
| Abierto | `prc_opens_at <= hoy` **y** `prc_closes_at >= hoy` (y no lo anterior) |
| Próximo | `prc_opens_at > hoy` o vacía |
| Cerrado | `prc_closes_at < hoy` (y no lo anterior) |

Correspondencia con los siete valores de hoy, para quien venga del sistema
anterior: borrador → `draft`; abierta → `open`; cerrada → `closed`; periodo
de reclamaciones → `amendment`; histórica → `archived`; **pendiente de
documentación → no es estado del procedimiento** (es un paso de la
solicitud, fase 2); **pilotaje → no publicar**.

## Consecuencias

### Positivas

- El estado no puede quedarse obsoleto ni contradecir al calendario: los
  dos iconos de contradicción desaparecen porque su combinación no existe.
- Desaparecen un desplegable, una categoría del constructor y la tarea de
  acordarse.
- `ProcedureState` es pura: se prueba cada estado y cada límite de día
  pasándole `$today`, sin levantar WordPress.
- Lo que cada estado permite —solicitar, editar, gestionar— se lee de una
  tabla en la SDD y de una función, no de un cruce de icono y fecha.

### Negativas

- **No hay `tax_query` por estado.** Cada listado por estado es una
  `meta_query` sobre dos o tres claves, más larga y más lenta, y obliga a que
  las fechas de cierre existan siempre (de ahí la normalización al guardar).
- **No hay «cancelado» ni «suspendido».** Un procedimiento que se retira hay
  que despublicarlo o archivarlo; ambas cosas pierden matiz. Es la opción 4,
  no implementada.
- **Un plazo mal tecleado cambia el estado en silencio.** Hoy, un error en
  la fecha no cambia nada hasta que alguien toca el desplegable; aquí cierra
  o abre el procedimiento ese día. El taller tiene que enseñar el estado
  resultante al guardar.
- **El estado de un día pasado no queda registrado**, así que no se puede
  decir «qué estado tenía el día 12» más que recalculándolo con las fechas
  de hoy. Si las fechas se editan después, el pasado cambia.

### Neutras

- El vocabulario de pantalla se conserva casi entero —abierto, cerrado, en
  subsanación, histórico— y se añaden «próximo» y «resuelto», que hoy se
  deducen a ojo.
- «Pendiente de documentación» no se pierde: reaparece como estado de la
  solicitud cuando exista la subida de documentos (fase 2).
- El «hoy» se inyecta, así que el entorno de demostración puede sembrar
  procedimientos en cada estado con fechas relativas al día en que se
  ejecuta `scripts/seed-demo.php`.
