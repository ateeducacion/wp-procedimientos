---
id: ADR-0012
title: "Una taxonomía por dimensión: ámbito y curso; el estado no es un término"
status: Propuesta
date: 2026-09-15
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0003, ADR-0011, ADR-0013, ADR-0014, ADR-0017]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-fable-5-1"
---

# ADR-0012: Una taxonomía por dimensión: ámbito y curso; el estado no es un término

## Estado

Propuesta (2026-09-15). Registro en `Taxonomy/ProcedureTaxonomies`; listas
cerradas en `Meta/ProcedureMetaKeys`.

## Contexto

El sitio que se sustituye clasifica una convocatoria por **cinco ejes en
cuatro mecanismos distintos**, leídos sobre el material de `.local/` (que no
se publica, [ADR-0009](ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md)):

| Eje | Dónde vive hoy | Cómo |
|---|---|---|
| Ámbito convocante | Una taxonomía jerárquica registrada por un fragmento del sitio sobre el tipo de contenido del constructor, **y además** una casilla multivalor de la entrada del formulario, serializada | Árbol de 32 términos: dirección → servicio → área. Con metas de término: correo del ámbito e imagen |
| Curso escolar | Otra taxonomía registrada por el mismo fragmento, **y además** un desplegable de la entrada | Un término por curso; un fragmento fuerza el sufijo «(curso XXXX-XXXX)» en el título |
| Estado | Un desplegable de la entrada que escribe en la **categoría del constructor de páginas** | Siete valores: abierta, borrador, cerrada, histórica, pendiente de documentación, periodo de reclamaciones, pilotaje |
| Dirigido a | Dos casillas de la entrada | Centros públicos / centros privados y concertados |
| Tipología | Un desplegable de la entrada | Solicitud de centros / solicitud de profesorado |

Tres observaciones que no son interpretación:

1. **El ámbito y el curso están modelados dos veces**, en la entrada y en la
   taxonomía, y solo la taxonomía se consulta. La casilla serializada la
   deserializa cada consumidor por su cuenta: hay tres fragmentos con su
   propio `maybe_unserialize` + `explode(',')`.
2. **El estado es una categoría del constructor** porque era la taxonomía que
   el constructor ofrecía, no porque signifique categoría. El propio sitio
   sabe que miente: la pantalla de gestión tiene una leyenda de **ocho
   iconos** que cruzan el estado con la fecha límite, y dos de ellos son
   «abierta con plazo finalizado» y «cerrada con plazo abierto».
3. **«Dirigido a» y «tipología» son listas de dos valores que cambian el
   comportamiento**: la tipología decide qué formulario de solicitud se usa
   y bajo qué padre cuelga el contenido; «dirigido a» decide qué centros
   pueden solicitar. No son clasificación: son configuración.

## Problema

¿Cómo se modelan el ámbito, el curso, el estado, la audiencia y la
titularidad de un procedimiento, de forma que cada eje viva en un solo sitio,
que el ámbito sirva de eje de permisos y que ninguno pueda contradecir a
otro?

## Factores de decisión

- **Un dato, un sitio.** Ni ámbito ni curso pueden estar en una meta y en una
  taxonomía a la vez.
- **El ámbito es el eje de permisos**
  ([ADR-0014](ADR-0014-el-ambito-es-un-alcance-no-un-rol.md)): tiene que ser
  consultable con `tax_query`, jerárquico de verdad —el acotado incluye
  descendientes— y asignable a personas.
- **Quién mantiene el vocabulario.** Ámbitos y cursos los crea la
  organización desde el escritorio; audiencia y titularidad las decide el
  código, porque cada valor lleva lógica detrás.
- **Lo que se puede derivar, no se guarda.** El estado se calcula
  ([ADR-0013](ADR-0013-el-estado-del-procedimiento-se-deriva-de-las-fechas.md)).
- **Las capacidades declaradas deben existir.** Las taxonomías se registran
  con capacidades del aplicativo, no con nombres que nadie concede.

## Alternativas consideradas

### Opción 1: tres taxonomías, incluida la de estado (el modelo de hoy, limpio)

`prc_area`, `prc_course` y `prc_status`, sin la duplicación en la entrada.

| Pros | Contras |
|---|---|
| Se filtra por estado con `tax_query`. | El estado sigue siendo algo que alguien tiene que acordarse de cambiar: los ocho iconos volverían. |
| Es lo que el sitio ya conoce. | Un término de estado puede contradecir a las fechas, y el aplicativo tendría que elegir a cuál creer. |

### Opción 2: todo como taxonomía, también audiencia y titularidad

| Pros | Contras |
|---|---|
| Un solo mecanismo para clasificar. | Añadir un término de audiencia desde el escritorio no añade el comportamiento que va con él: el código tendría que reconocer el slug. Un vocabulario que el código tiene que conocer no es un vocabulario. |
| Columnas y filtros gratis. | Cinco cajas de términos en la pantalla de edición para dos ejes que tienen dos valores. |

### Opción 3: todo como meta con listas cerradas en código

| Pros | Contras |
|---|---|
| Vocabulario en Git, revisable. | Añadir un ámbito exige desplegar el bundle. El ámbito lo crea la organización, no quien desarrolla. |
| Sin tablas de términos. | Se pierde la jerarquía, que aquí es real y es lo que acota los permisos. |

### Opción 4: dos taxonomías, dos listas cerradas y el estado derivado (elegida)

| Pros | Contras |
|---|---|
| Cada eje en el mecanismo que le corresponde. | Dos mecanismos que explicar: «esto es un término, esto es una meta». |
| El ámbito acota permisos con `tax_query` y jerarquía. | El estado no se filtra con `tax_query`: se filtra por fechas. |
| Audiencia y titularidad viven donde vive su lógica. | |

## Decisión

**Haremos la opción 4.**

| Dimensión | Dónde va | Por qué |
|---|---|---|
| Ámbito convocante | `prc_area`, **jerárquica** | Es el eje de permisos; el árbol servicio → área es real; lo crea la organización |
| Curso escolar | `prc_course`, no jerárquica | Un término por curso, p. ej. `2026-2027`; se añade uno al año |
| Estado | **Ninguna**: se deriva | Es la única dimensión que se puede calcular ([ADR-0013](ADR-0013-el-estado-del-procedimiento-se-deriva-de-las-fechas.md)) |
| Dirigido a | Meta `prc_ownership`, array sobre la lista cerrada `public` / `private` | Dos valores estables que se cruzan con la titularidad del catálogo de centros ([ADR-0017](ADR-0017-el-catalogo-de-centros-no-se-versiona.md)) |
| Tipología | Meta `prc_audience`, lista cerrada `schools` / `teachers` | Dos valores que cambian el formulario de solicitud; la fase 1 solo implementa `schools` |

- Las dos taxonomías se registran sobre `prc_procedure` en
  `Taxonomy/ProcedureTaxonomies`, con `show_admin_column` y con capacidades
  que existen: `prc_manage_app` para administrar términos y
  `prc_manage_procedures` para asignarlos.
- `prc_area` es jerárquica de verdad, no solo para presentarse como casillas:
  el acotado por ámbito incluye a los descendientes del término de la persona
  ([ADR-0014](ADR-0014-el-ambito-es-un-alcance-no-un-rol.md)).
- `prc_course` no es jerárquica y se presenta igualmente como lista cerrada
  en el taller: un curso nuevo no se crea por una errata al teclear.
- Las listas cerradas viven en `Meta/ProcedureMetaKeys::audiences()` y
  `::ownerships()` y las valida `sanitize_callback` al guardar; un valor
  fuera de la lista no se escribe.
- **Los términos son datos**: sus nombres van en castellano y los crea cada
  instalación; el vocabulario de `scripts/setup-vocabulary.php` es de
  demostración ([ADR-0009](ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md)).
- **El correo del ámbito no es una meta de término.** Hoy lo es, y un
  fragmento lo copia a los correos de contacto de la convocatoria al crearla.
  Aquí es `prc_contact_email` del procedimiento, y quien lo crea lo escribe:
  es un dato del procedimiento, no del organigrama.

## Consecuencias

### Positivas

- Ámbito y curso viven en un sitio y se consultan con `tax_query`; la
  columna sale sola en el escritorio.
- «Los procedimientos de mi ámbito, incluidos los de mis áreas» es una
  consulta con `include_children`, sin deserializar nada.
- El estado deja de poder mentir: no existe «cerrada con plazo abierto».
- Añadir un ámbito o un curso es añadir un término desde el escritorio;
  añadir una audiencia es una decisión de código, como debe ser.
- El sufijo «(curso XXXX-XXXX)» del título desaparece: el curso es un
  término y se pinta al lado.

### Negativas

- **Dos mecanismos donde había uno visible.** Quien edite tiene que saber que
  el ámbito es una caja de términos y «dirigido a» son dos casillas de meta.
- **`prc_course` crece un término al año** y nadie lo recuerda hasta que
  falta. Trabajo manual recurrente.
- **No se filtra por estado con `tax_query`.** Toda consulta por estado es
  una `meta_query` sobre fechas, más larga y más lenta.
- **La imagen del ámbito se pierde.** Hoy cada término lleva una imagen que
  la portada usa como icono; aquí no hay meta de término. Si se quiere, es un
  requisito nuevo.
- **`teachers` está en la lista y no hace nada.** Está para que el hueco
  exista sin renombrar la meta después; hasta la fase 3 es un valor que el
  taller no ofrece.

### Neutras

- Los términos de hoy no se migran en esta fase (ADR-0024); el árbol de
  ámbitos real se recrea desde el escritorio en el sitio de destino.
- Los siete valores del estado de hoy se corresponden con los seis derivados
  más el borrador, salvo dos que no son estado: «pendiente de documentación»
  es un paso de la solicitud (fase 2) y «pilotaje» es visibilidad, que aquí
  es no publicar ([ADR-0013](ADR-0013-el-estado-del-procedimiento-se-deriva-de-las-fechas.md)).
