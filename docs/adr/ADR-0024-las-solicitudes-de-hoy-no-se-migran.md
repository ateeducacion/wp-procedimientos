---
id: ADR-0024
title: "Las solicitudes y los documentos de hoy no se migran en la fase 1"
status: Propuesta
date: 2026-09-15
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0009, ADR-0010, ADR-0011, ADR-0013, ADR-0018, ADR-0021, ADR-0023]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-fable-5-1"
---

# ADR-0024: Las solicitudes y los documentos de hoy no se migran en la fase 1

## Estado

Propuesta (2026-09-15). Es una decisión de alcance: dice lo que la fase 1 **no
hace** y deja escrito qué queda pendiente y cuándo se decide.

## Contexto

El día que `prc_procedure` entre en servicio, el sitio de destino ya tiene
dentro lo siguiente, medido sobre la foto del 2026-09-15 (cifras redondeadas;
el detalle está en `.local/`):

| Qué | Cuánto | Dónde vive |
|---|---|---|
| Convocatorias | Unas 300, en cuatro cursos | Una entrada del gestor de formularios más una página del constructor, con dirección pública `/convocatoria/<slug>/` |
| Solicitudes de centros | Unas 17.700 | Entradas de un formulario de 94 campos |
| Solicitudes de profesorado | Unas 3.400 | Otro formulario, fuera del alcance de la fase 1 |
| Ficheros por solicitud | Hasta una decena | Aceptación firmada, proyecto, memoria, otros documentos, hoja de profesorado, reclamaciones; servidos por el gestor con protección de ficheros |
| Documentos oficiales | Uno a cinco por convocatoria | Resolución, corrección y listados en PDF, en la carpeta del gestor |
| Certificaciones | Decenas de miles de filas | Otro formulario, fuera del alcance |

Y tres restricciones que mandan:

- **Son datos personales.** Cada solicitud lleva identificador fiscal, nombre y
  correo de la dirección y de la persona coordinadora, y ficheros firmados.
  Moverlos a otro almacén es un tratamiento nuevo, con su análisis, su base
  jurídica y su plazo de conservación.
- **Las direcciones públicas están enlazadas desde fuera.** Las resoluciones
  oficiales citan la página de la convocatoria; las circulares también. No se
  pueden romper a la ligera.
- **El modelo nuevo no es el de hoy.** El aplicativo no copia el centro entero
  ni la persona
  ([ADR-0018](ADR-0018-la-solicitud-es-un-contenido-del-procedimiento.md)), no
  tiene ficheros en la fase 1, guarda enlaces en vez de PDF
  ([ADR-0021](ADR-0021-la-resolucion-y-los-listados-son-enlaces.md)) y deriva
  el estado de fechas que hoy no siempre están rellenas
  ([ADR-0013](ADR-0013-el-estado-del-procedimiento-se-deriva-de-las-fechas.md)).
  Una migración no es una copia: es una traducción con pérdida.

El aplicativo de eventos tomó dos decisiones seguidas sobre esto: dejar las
inscripciones donde estaban en su fase 1 y migrar el contenedor de las páginas
históricas congelando su contenido
([ADR-0010](ADR-0010-se-parte-del-esqueleto-del-aplicativo-de-eventos.md)). La
primera se sustituyó después, cuando el aplicativo tuvo formulario propio; la
segunda arrastró un bloqueante —la reescritura de direcciones— que no estaba
resuelto al decidirla. Aquí se toma la primera y **no** se toma todavía la
segunda.

## Problema

¿Qué hace la fase 1 con las convocatorias, las solicitudes y los documentos
que ya existen: los migra, los lee, o los deja donde están? ¿Y quién decide lo
que quede sin decidir?

## Factores de decisión

- **Lo que duele es el alta, el estado y la gestión de lo nuevo.** Las
  solicitudes ya presentadas están presentadas: nadie las va a volver a
  gestionar con otra herramienta.
- **Volumen y naturaleza del dato.** 17.700 registros con datos personales y
  ficheros firmados no se mueven en una fase de arquitectura.
- **Una fase 1 que lo abarque todo no se entrega.**
- **Coste cero de no tocarlo.** El gestor de formularios y el constructor
  siguen instalados y sirviendo lo suyo.
- **No cerrar puertas.** Lo que no se decide hoy tiene que poder decidirse
  mañana sin deshacer nada.

## Alternativas consideradas

### Opción 1: migrar todo en la fase 1

Convocatorias a `prc_procedure`, solicitudes a `prc_application`, ficheros a
un almacén propio.

| Pros | Contras |
|------|---------|
| Un solo sistema desde el primer día. | Multiplica el alcance: exige el almacén de ficheros de la fase 2 antes de la fase 1. |
| | Obliga a un análisis de protección de datos antes del primer despliegue. |
| | Traducción con pérdida: hasta cuatro docentes coordinadores, campos por convocatoria, anexos de 200 campos no caben en el modelo nuevo. |

Descartada.

### Opción 2: migrar solo el contenedor de las convocatorias y congelar el contenido

Como hizo el aplicativo de eventos con sus páginas: cambiar el tipo de
contenido conservando ID y slug, marcar como congelado, seguir pintando con
el sistema anterior.

| Pros | Contras |
|------|---------|
| Un solo listado y una sola consulta en la portada. | Aquí la convocatoria es **dos cosas**: una entrada del gestor y una página del constructor, sincronizadas por un fragmento. Migrar la página deja la entrada huérfana, y la página no tiene nada dentro sin la entrada. |
| Conserva las direcciones si el CPT reescribe en la raíz. | La reescritura en la raíz no está resuelta ni probada; el aplicativo de eventos lo dejó como bloqueante. |
| | Exige una segunda marca de «contenido congelado» además de `prc_archived` ([ADR-0023](ADR-0023-el-estado-historico-cierra-la-edicion.md)), y un segundo camino en el editor y en la ficha. |

**No se descarta: se aplaza.** Es la candidata natural para el histórico,
pero necesita un inventario que hoy no existe (qué páginas son convocatorias,
qué plantillas usan, qué consulta «¿es una página?») y una prueba de la
reescritura de direcciones.

### Opción 3: puente de lectura

El aplicativo consulta el gestor de formularios por su API para enseñar las
solicitudes antiguas junto a las nuevas.

Descartada: acopla el aplicativo a los identificadores de campo de cada
formulario y anexo, que son distintos por convocatoria, justo cuando el
objetivo es poder retirar el gestor.

### Opción 4: no migrar nada en la fase 1 y decidir el histórico aparte (elegida)

| Pros | Contras |
|------|---------|
| La fase 1 se despliega sin mover un solo dato personal. | Dos sistemas conviven, sin integridad entre ellos. |
| Lo pendiente queda escrito, con disparadores. | La portada nueva no enseña lo viejo. |

## Decisión

**En la fase 1 el aplicativo no migra ni lee nada del sistema anterior.**
Punto por punto:

1. **`prc_procedure` arranca vacío.** Los procedimientos nuevos se crean con
   el aplicativo; las convocatorias anteriores siguen siendo entradas del
   gestor y páginas del constructor, y se siguen viendo como hoy.
2. **`prc_application` arranca vacío.** Las 17.700 solicitudes se quedan
   donde están, con sus ficheros. No se leen, no se cuentan, no se enlazan.
3. **El gestor de formularios y el constructor siguen instalados** y
   actualizados. No son deuda que se pueda desinstalar: son lo que sirve el
   histórico.
4. **No se registra ninguna meta de contenido migrado.** Hay una marca,
   `prc_archived`, y significa «cerrado a edición»
   ([ADR-0023](ADR-0023-el-estado-historico-cierra-la-edicion.md)). Una
   segunda marca la creará, si hace falta, la ADR de la migración.
5. **No se cierra la puerta a las direcciones.** El slug de `prc_procedure`
   es `procedimiento` y es filtrable (`prc_procedure_rewrite_slug`,
   [ADR-0011](ADR-0011-el-procedimiento-es-un-tipo-de-contenido.md)); la
   decisión sobre conservar `/convocatoria/<slug>/` queda para la ADR de la
   migración, con la reescritura probada antes de decidirla.
6. **Los datos del sistema anterior no entran en el repositorio** ni como
   fixtures ni como ejemplos
   ([ADR-0009](ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md)). Los
   datos de demostración son inventados.
7. **Cuando el camino nuevo funcione, se desactiva el alta por el camino
   viejo**: la acción del gestor que crea la página del constructor. Se
   desactiva, no se borra; es reversible.

### Lo que queda pendiente, y cuándo se decide

| Pendiente | Se decide en | Disparador |
|---|---|---|
| Histórico de convocatorias en la portada nueva | ADR de la migración (fase 4 de la SDD) | Alguien pide ver «todas las convocatorias de un ámbito» en un sitio |
| Conservar `/convocatoria/<slug>/` | La misma ADR, con la reescritura probada en el entorno local | Antes de retirar el constructor de páginas |
| Solicitudes históricas | ADR propia, con análisis de protección de datos | Petición de acceso o supresión; o retirada del gestor de formularios |
| Ficheros de solicitudes | La ADR de documentación adjunta (fase 2) | Ídem |
| Solicitudes de profesorado y certificaciones | Fase 3 (`prc_audience = teachers`) | Un procedimiento dirigido a profesorado que no quepa en el gestor |

## Consecuencias

### Positivas

- La fase 1 cabe y se despliega **sin mover un solo dato personal** y sin
  esperar a un análisis de protección de datos.
- Lo ya presentado no corre riesgo: no se toca.
- Quien gestiona solicitudes antiguas no nota ningún cambio.
- Nada de lo decidido aquí hay que deshacer para migrar después: el slug es
  filtrable, la marca es una, y `prc_procedure` no tiene nada que tropiece con
  lo viejo.

### Negativas

- **Dos sistemas conviven** sin integridad referencial: un procedimiento
  nuevo vive en `wp_posts`; una convocatoria vieja, en el gestor. Buscar
  «todo lo de un ámbito» son dos búsquedas.
- **La portada nueva no enseña lo viejo.** Durante el primer curso el listado
  de `procedimientos` tendrá solo lo creado con el aplicativo, y la portada
  del sistema anterior seguirá existiendo al lado. Hay que decidir cuál
  enlaza el menú.
- **La superficie de protección de datos se queda donde está**, con sus
  ficheros servidos por el gestor.
- **El coste por convocatoria antigua no baja**: una corrección en una
  convocatoria del curso pasado se hace en el formulario de 123 campos.
- **Mientras haya histórico en el gestor hay que mantenerlo instalado.** A
  partir de la fase 4, el histórico será el único motivo, y ese día habrá que
  pagar la opción 2 o asumir el archivo estático.
- **El inventario para la opción 2 no está hecho** y nadie lo hará hasta que
  llegue el disparador. Cuanto más tarde, más convocatorias nuevas conviven
  con las viejas.

### Neutras

- Esta ADR no dice nada de las convocatorias **en curso** el día del
  despliegue. Lo razonable es que terminen por el camino viejo y que lo nuevo
  empiece con el curso; es una decisión de calendario, no de arquitectura.
- La primera vez que se migre algo, esta ADR se sustituye.
