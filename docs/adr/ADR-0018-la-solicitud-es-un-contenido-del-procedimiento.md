---
id: ADR-0018
title: "La solicitud es un contenido que cuelga del procedimiento, una por centro"
status: Propuesta
date: 2026-09-15
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0001, ADR-0007, ADR-0010, ADR-0011, ADR-0014, ADR-0016, ADR-0017, ADR-0019, ADR-0020, ADR-0023]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-fable-5-1"
---

# ADR-0018: La solicitud es un contenido que cuelga del procedimiento, una por centro

## Estado

Propuesta (2026-09-15). Nada implementado: fija `prc_application` antes de
registrarlo.

## Contexto

Hoy una solicitud de centro es **una entrada de un formulario de 94 campos**
del gestor de formularios. Medido sobre la foto del 2026-09-15 (material en
`.local/`):

- **La clave es una cadena concatenada.** El vínculo con la convocatoria es un
  campo de búsqueda que copia el identificador de la convocatoria desde la
  URL, y la unicidad «un centro, una convocatoria» es un campo de texto que
  concatena ese identificador con el código de centro de la persona
  (`«identificador·código»`), marcado como único en el gestor. Esa clave se
  construyó **a posteriori** con un fragmento de un solo uso que recorrió las
  entradas cuyo campo empezaba por un prefijo de código y les antepuso el
  identificador. No hay unicidad en la base de datos: hay una comprobación
  del gestor sobre un texto.
- **El centro se copia entero en cada solicitud.** Código, etapa,
  denominación, localidad, municipio, correo del centro y zona, cada uno por
  una búsqueda encadenada al catálogo en cada carga del formulario, y todos
  guardados como texto en la entrada.
- **La persona también se copia.** Identificador fiscal, nombre, apellidos y
  correo de quien solicita van como campos de solo lectura rellenos desde la
  cuenta. Están en unas 17.700 entradas.
- **La solicitud no tiene estado.** Lo que hay son «pasos» —registrada,
  imprimir, subir la aceptación firmada— que se deducen de si un fichero está
  subido, y una sección de reclamaciones con ficheros y texto.

El aplicativo de eventos tomó para sus inscripciones la vía de colgarlas del
evento por `post_parent`, en un tipo de contenido cerrado
([ADR-0010](ADR-0010-se-parte-del-esqueleto-del-aplicativo-de-eventos.md)). De
ahí salen sin escribirlos el ámbito al que pertenece la solicitud, el guardián
que la acota
([ADR-0014](ADR-0014-el-ambito-es-un-alcance-no-un-rol.md)) y el cierre por
histórico ([ADR-0023](ADR-0023-el-estado-historico-cierra-la-edicion.md)).

## Problema

¿Dónde vive una solicitud, cómo se garantiza que un centro presenta **una**
por procedimiento, y qué se copia del centro y de la persona en el momento de
presentarla?

## Factores de decisión

- **Se despliega pegando código.** Sin activación no hay `dbDelta()`
  ([ADR-0001](ADR-0001-repo-entorno-desarrollo-no-plugin.md)).
- **Colgar del procedimiento resuelve tres cosas gratis**: ámbito, guardián y
  cierre por histórico.
- **Una por centro.** Hoy se cumple por un texto único; mañana tiene que
  cumplirse por una búsqueda antes de crear, y la segunda solicitud tiene que
  abrir la primera, no fallar.
- **El nombre del centro cambia; el de la solicitud, no.** Un listado de un
  curso anterior tiene que leerse como se presentó, aunque el centro se haya
  renombrado o ya no exista.
- **Los datos personales se minimizan.** Lo que ya está en la cuenta no se
  copia.
- **Diagnóstico.** Quien administra tiene que poder ver una solicitud sin
  pasar por las pantallas del aplicativo cuando algo falle.

## Alternativas consideradas

### Opción 1: una tabla propia

| Pros | Contras |
|------|---------|
| Unicidad con un índice `UNIQUE (procedure_id, centre_code)`. | Nadie la crea: sin activación hay que comprobar en cada carga y decidir qué pasa sin `CREATE TABLE`. |
| Contar y agrupar es SQL. | Papelera, autor, fechas, capacidades y exportación de datos personales dejan de venir de serie. |

Descartada. El índice único es la única ventaja real, y no compensa lo que
deja de venir hecho.

### Opción 2: las solicitudes en una meta del procedimiento

| Pros | Contras |
|------|---------|
| Cero tipos de contenido nuevos. | Dos centros solicitando a la vez se pisan: leer, añadir y escribir una meta no es atómico, y con el plazo abierto pasa. |
| Se lee de una vez. | Sin papelera ni autor por fila; unas 60 solicitudes por procedimiento son una meta serializada que se reescribe entera. |

Descartada.

### Opción 3: un tipo de contenido que cuelga del procedimiento (elegida)

`prc_application`, hijo por `post_parent`, con metas registradas.

| Pros | Contras |
|------|---------|
| Ámbito, guardián, papelera, autor y fecha vienen de serie. | Los datos personales viven en `wp_posts` y `wp_postmeta`. |
| La unicidad es una búsqueda; la segunda solicitud reabre la primera. | Esa búsqueda no es un índice: dos envíos simultáneos del mismo centro pueden crear dos. |

## Decisión

**Una solicitud es un `prc_application` que cuelga de su procedimiento por
`post_parent`.**

### Registro

`public => false`, `publicly_queryable => false`, `show_in_rest => false`,
`exclude_from_search => true`, `has_archive => false`, `rewrite => false`,
`capability_type => array( 'prc_application', 'prc_applications' )`,
`map_meta_cap => true`. **`show_ui => true`**, a diferencia del aplicativo de
eventos: la lista del escritorio existe **solo para diagnóstico** de quien
administra, acotada por `pre_get_posts` y guardada por `map_meta_cap`
([ADR-0014](ADR-0014-el-ambito-es-un-alcance-no-un-rol.md)). No hay pantalla
de edición útil ahí: las metas se editan desde el taller.

`post_author` es quien presenta; `post_status` pasa a `publish` al
presentar; `post_title` se genera: «{nombre del centro} — {título del
procedimiento}».

### Una por centro y procedimiento

Antes de crear, `PublicFront/Applications::find( $procedure_id,
$centre_code )` busca un `prc_application` con ese `post_parent` y esa
`prc_centre_code`. Si existe, **se edita esa**: la pantalla `apply` con
`?procedimiento=ID` abre siempre la solicitud del centro de la persona, y un
POST de alta con una ya existente se convierte en edición. La comprobación se
hace en el servidor, en el manejador del POST, no solo al pintar el botón.

### Lo que se copia del centro: código y nombre, y nada más

| Meta | Qué es |
|---|---|
| `prc_centre_code` | El código de la persona ([ADR-0016](ADR-0016-el-centro-de-la-persona-es-una-meta-configurable.md)), resuelto en el servidor; nunca del formulario |
| `prc_centre_name` | El nombre que el catálogo devolvía **ese día** ([ADR-0017](ADR-0017-el-catalogo-de-centros-no-se-versiona.md)): una **foto**, no una referencia |

El nombre se copia porque el catálogo cambia y la solicitud no debe cambiar
con él. Etapa, localidad y correo del centro **no se copian**: son del
catálogo y se consultan por código si hacen falta. Si el código no está en el
catálogo, el nombre se guarda vacío y quien gestiona lo ve marcado.

### Lo que no se copia de la persona

Nombre, apellidos y correo de quien solicita **son `post_author`** y se leen
de la cuenta cuando se pintan o se exportan. El identificador fiscal no se
guarda en ningún sitio. Lo único propio de la persona en la solicitud es el
cargo (`prc_applicant_position`, lista cerrada).

### El resto de metas

Las declara `Meta/ApplicationMetaKeys` y las registra
`ApplicationMetaRegistration` con `sanitize_callback` y `auth_callback` que
delega en `ProcedureAccess`: cargo, persona coordinadora, respuestas
([ADR-0019](ADR-0019-la-solicitud-es-nucleo-fijo-mas-preguntas.md)), estado
de revisión y nota
([ADR-0020](ADR-0020-la-subsanacion-es-un-plazo-y-un-estado.md)).

### Borrar

Enviar a la papelera ([ADR-0007](ADR-0007-borrar-es-enviar-a-la-papelera.md)).
Un procedimiento en la papelera se lleva sus solicitudes.

## Consecuencias

### Positivas

- Se acaba la clave concatenada en un campo de texto y el fragmento que la
  reconstruye: la unicidad es una búsqueda con dos criterios.
- Ámbito, guardián, cierre por histórico y papelera no se escriben.
- Un listado de hace tres cursos se lee como se presentó, con el nombre de
  entonces.
- Unas 17.700 copias del nombre y el identificador fiscal de la dirección
  dejan de crearse: lo que hay en la cuenta se lee de la cuenta.

### Negativas

- **La unicidad no es un índice.** Dos envíos del mismo centro en el mismo
  segundo pueden crear dos solicitudes; `find()` devolverá la primera y la
  segunda quedará huérfana en la lista del escritorio. No se protege con
  bloqueo; si ocurre, se resuelve a mano. Con unos 60 centros por
  procedimiento no está medido que ocurra.
- **Los datos personales siguen en `wp_posts`.** Menos que hoy, pero el
  cargo, la persona coordinadora y las respuestas siguen ahí, y cualquier
  plugin que recorra tipos de contenido los encuentra.
- **`show_ui => true` es una puerta más que guardar.** Se abre a sabiendas,
  para diagnóstico, y `map_meta_cap` y `pre_get_posts` tienen que cubrirla en
  los tests.
- **Si el centro cambia de nombre, la solicitud no se entera.** Es
  deliberado, y quien busque «todas las solicitudes del centro X» tiene que
  buscar por código.
- **No hay plazo de conservación.** Una solicitud dura lo que dure su
  procedimiento, y los procedimientos históricos no se borran. Quién borra qué
  y cuándo no lo decide esta ADR.

### Neutras

- Las solicitudes de profesorado o alumnado no existen en la fase 1; el hueco
  es `prc_audience` en el procedimiento
  ([ADR-0011](ADR-0011-el-procedimiento-es-un-tipo-de-contenido.md)).
- Las solicitudes del sistema anterior no se migran ni se leen
  ([ADR-0024](ADR-0024-las-solicitudes-de-hoy-no-se-migran.md)).
