---
id: ADR-0042
title: "La titularidad se deriva del tipo, la zona es otro centro del catálogo y el procedimiento acota qué centros solicitan"
status: Propuesta
date: 2026-11-10
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0009, ADR-0016, ADR-0017, ADR-0018, ADR-0029, ADR-0031, ADR-0039]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5-5"
---

# ADR-0042: La titularidad se deriva del tipo, la zona es otro centro del catálogo y el procedimiento acota qué centros solicitan

## Estado

Propuesta. Cambia lo que decían la
[ADR-0016](ADR-0016-el-centro-de-la-persona-es-una-meta-configurable.md) y la
[ADR-0017](ADR-0017-el-catalogo-de-centros-no-se-versiona.md) sobre el centro
que no está en el catálogo, amplía lo que guarda la sincronización de la
[ADR-0029](ADR-0029-el-catalogo-de-centros-es-externo-y-se-cachea-localmente.md)
y matiza la [ADR-0031](ADR-0031-quien-coordina-una-agrupacion-rural-solicita-por-el-centro-o-por-la-agrupacion.md).
Ninguna se reescribe: llevan una adenda que remite aquí.

## Contexto

1. **La titularidad salía siempre «pública».** El procedimiento acota por
   titularidad desde el principio (`prc_ownership`, ADR-0017), y el catálogo
   sincronizado (ADR-0029) es la fuente por defecto. Pero la fuente no trae
   titularidad, la sincronización no la guardaba y
   `CentreCatalogue::for_domain()` la rellenaba con «público». Se comprobó con
   una prueba antes de tocar nada
   (`tests/unit/test-centres.php`, `test_a_synced_private_centre_reads_private_in_the_domain`):
   un centro privado sincronizado se leía público en el dominio. Con el
   catálogo sincronizado, un procedimiento «solo centros públicos» admitía a
   todos y uno «solo privados» no admitía a nadie.
2. **La fuente sí trae el tipo de centro** (`type`) y, en otro campo, el
   código de **otro** centro del mismo catálogo al que cada centro está
   adscrito: su zona. Los procedimientos que revisan esa adscripción —y la
   consulta de solo lectura que la usará (PLAN-0003, PR 7)— necesitan leerla.
   El campo de la fuente se llama `cep`; cómo llama cada instalación a esa
   zona no se escribe aquí (ADR-0009).
3. **Hay convocatorias que se dirigen a tipos de centro, a islas o a una
   lista cerrada de centros**, y hoy eso solo lo dice el texto: el aplicativo
   no lo comprueba. Es una de las carencias que recoge el
   [PLAN-0003](../plan/PLAN-0003-lo-que-falta-para-sustituir-al-sistema-anterior.md)
   (PR 5) a partir de la revisión de las peticiones del sistema anterior,
   cuyo material está en `.local/`.

## Problema

¿De dónde sale la titularidad de un centro cuando la fuente no la trae, cómo
se guarda la zona, y cómo acota un procedimiento qué centros pueden solicitar
sin dejar pasar en silencio a un centro del que no se sabe nada?

## Factores de decisión

- **Publicable** (ADR-0009): ni la lista de tipos privados de una instalación,
  ni el nombre de su zona, ni centros reales en el repositorio.
- **Un único guardián** (`ProcedureAccess`): lo que acota se decide en
  `can_apply()` / `apply_codes()`, que es lo que mira cada puerta.
- **No tocar lo ya presentado**: acotar después no borra ni cambia una
  solicitud.
- **Sin coste por fila**: el catálogo tiene del orden de dos mil centros; ni la
  zona ni la exportación pueden hacer una búsqueda por centro.
- **El CSV de siempre no cambia** (ADR-0039).

## Alternativas consideradas

### Titularidad

| Opción | A favor | En contra |
|---|---|---|
| Pedirla a la fuente | Es donde debería estar | No es nuestra; no se sabe cuándo, ni si |
| Una lista de códigos privados en los ajustes | Exacta | Cientos de códigos a mano, y caduca con cada centro nuevo |
| **Derivarla del tipo con una lista de tipos privados (elegida)** | Una docena de tipos, que cambian poco; un centro nuevo sale bien solo | Un tipo mixto obliga a corregir centro a centro con el filtro `prc_centres` |

### Zona

| Opción | A favor | En contra |
|---|---|---|
| Guardar también el nombre de la zona al sincronizar | Una lectura menos | Dos datos que pueden discrepar; el nombre ya está en el catálogo |
| **Guardar el código y resolver el nombre en memoria (elegida)** | Una sola verdad; una pasada | Si la zona no está en el catálogo, no hay nombre |

### Centro fuera del catálogo

| Opción | A favor | En contra |
|---|---|---|
| Que siga solicitando siempre (lo de antes) | El catálogo puede ir por detrás | Un acotado deja de acotar: basta con no estar en el catálogo |
| Que no solicite nunca | Simple | Un procedimiento sin acotar cierra la puerta por un retraso del catálogo |
| **Que no solicite solo si el procedimiento acota (elegida)** | Sin acotados, igual que antes; con ellos, no se cuela nadie que no se pueda comprobar | Un catálogo vacío o atrasado deja fuera de los acotados a quien no está |

## Decisión

### El catálogo

- La sincronización guarda `zone`: el campo `cep` de la fuente si tiene la
  forma de un código oficial de 8 cifras, y vacía si no viene o no la tiene;
  no tumba la sincronización por eso. Guarda también `ownership` si algún día
  la fuente la trae (`CentreCatalogueSync::record()`).
- `CentreCatalogue::for_domain()` deja de inventar la titularidad: la pasa
  solo si está guardada.
- `Domain/CentreCatalog::all()` y `find()` sirven `code`, `name`, `ownership`,
  `kind`, `island`, `municipality`, `type`, `zone`, `zone_name` y `active`.
  `zone_name` es el nombre del centro cuyo código es `zone`, resuelto en el
  mismo recorrido; vacío si no está. Un catálogo guardado antes de esta ADR
  no tiene zona: sale vacía. `CentreCatalog::index()` lo da indexado por
  código para quien mira muchos a la vez, y `values( $campo )` los valores
  distintos de un atributo.
- **La titularidad se deriva del tipo**: es privada si el `type` está en la
  lista que contesta el filtro `prc_private_centre_types`, sin distinguir
  mayúsculas. Por defecto la lista sale de la opción
  `prc_private_centre_types` de los ajustes, **vacía**: sin configurar, todo
  centro es público. Una `ownership` explícita —de la fuente o de quien
  conteste `prc_centres`— manda sobre la derivada.

### El procedimiento

Tres metas nuevas, registradas como las demás y sin REST, en la sección «Qué
centros pueden solicitar» del panel «Datos»:

| Meta | Qué | Vacía |
|---|---|---|
| `prc_centre_types` | Tipos admitidos, como los escribe el catálogo | todos |
| `prc_islands` | Islas admitidas | todas |
| `prc_allowed_centres` | Lista cerrada de códigos, uno por línea o separados por comas, hasta 2000 | sin lista |

Las casillas de tipos e islas salen de los valores presentes en el catálogo,
más los ya elegidos aunque el catálogo los haya perdido. Una lista cerrada con
un código mal formado o con más de 2000 no se guarda y se dice; los espacios y
los repetidos se van solos.

### El guardián

`ProcedureAccess::apply_codes()` mira, para cada código de la persona cuya
clase admite el procedimiento, `Domain/CentreEligibility::why_not()` con su
propia entrada del catálogo:

1. Sin ningún acotado —titularidad «todas» (o ninguna guardada), sin tipos,
   sin islas y sin lista—, pasa, esté o no en el catálogo: **como antes**.
2. Con alguno: si hay lista y el código no está, no; si el código no está en
   el catálogo, **no** (aunque esté en la lista: no se puede comprobar lo
   demás); después, titularidad, tipo e isla.
3. Una agrupación de escuelas rurales se mide **por su propio código**: su
   isla, su tipo, su sitio en la lista y en el catálogo. La titularidad no la
   filtra, como hasta ahora (ADR-0031).

`ProcedureAccess::why_not_eligible()` da el motivo en castellano —«Este
procedimiento es solo para centros de tipo CEIP.»— y es lo que pinta la
pantalla de solicitud. Un centro inactivo sigue en el catálogo y se mide igual.

### Lo ya presentado

Acotar después no toca ninguna solicitud: se sigue viendo y se sigue
revisando. Lo que se cierra es volver a enviarla, porque el envío pasa por
`can_apply()`. Las entregas de documentos con plazo propio y la lista de
personas no pasan por aquí: siguen su propia regla (ADR-0033, ADR-0041).

### Exportación

La isla, el municipio, el tipo, la titularidad y la zona **se leen del
catálogo al pintar o exportar**; no se copian a la solicitud, que solo guarda
código y nombre (ADR-0029). `PublicFront/CentreColumns::attributes( $code )` es la
lectura que usarán los filtros por isla y por zona. `Applications::columns()`
gana, con su segundo parámetro, `island` («Isla»), `municipality`
(«Municipio»), `centre_type` («Tipo de centro»), `ownership` («Titularidad»),
`zone` (rótulo del filtro `prc_zone_label`, «Zona» por defecto) y `zone_code`.
Van en el grupo «Centro» del diálogo, pero **no** en el CSV de siempre: sin
columnas elegidas, o con «Todas las columnas», el fichero no cambia ni un
byte; solo salen si se marcan.

## Consecuencias

### Positivas

- La titularidad deja de ser una mentira silenciosa: un procedimiento «solo
  públicos» acota de verdad con el catálogo sincronizado.
- Quien convoca dice a qué tipos, islas o centros se dirige una vez, y el
  centro que no entra lo lee en su pantalla en vez de descubrirlo en la
  resolución.
- La zona queda lista para la consulta por zona y para los filtros de la
  tabla sin una consulta más por centro.
- Ningún nombre de una instalación entra en el código: tipos privados y rótulo
  de la zona son configuración.

### Negativas

- **Cambio de comportamiento.** Un centro que no está en el catálogo ya no
  solicita a un procedimiento con cualquier acotado; antes sí. Todo
  procedimiento de centros guardado con una sola titularidad está acotado, así
  que con el catálogo vacío o sin sincronizar esos procedimientos se cierran
  para todos. El diagnóstico de los ajustes enseña cuántos centros hay; hay que
  mirarlo antes de desplegar.
- **Sin configurar los tipos privados, todos son públicos.** Un procedimiento
  «solo privados» no admite a nadie hasta que alguien escriba la lista en los
  ajustes. No hay aviso automático de que falte.
- **Un tipo que mezcla titularidades** no se puede derivar: hay que corregir
  esos centros uno a uno contestando `prc_centres` con su `ownership`.
- **La zona sin nombre**: si el centro de zona no está en el catálogo, la
  columna sale con el código y el nombre vacío.
- Las columnas del catálogo dicen lo que dice el catálogo **hoy**, no el día
  que se presentó la solicitud.

### Neutras

- `CentreScope::codes_for()` y la unicidad de la solicitud no cambian.
- El catálogo de desarrollo (`scripts/mu-plugins/prc-dev-tools.php`) gana
  tipo, isla, municipio y zona, todos inventados, y un tipo privado inventado.
