---
id: ADR-0011
title: "El procedimiento es un tipo de contenido, no una entrada de formulario más una página del constructor"
status: Propuesta
date: 2026-09-15
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0001, ADR-0009, ADR-0010, ADR-0012, ADR-0013, ADR-0018, ADR-0019, ADR-0022]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-fable-5-1"
---

# ADR-0011: El procedimiento es un tipo de contenido, no una entrada de formulario más una página

## Estado

Propuesta (2026-09-15). Es la decisión raíz del modelo: `prc_procedure` como
tipo de contenido con metas y taxonomías, en `src/Prc/`, empaquetado en un
único Code Snippet.

## Contexto

Hoy una convocatoria no es un contenido de WordPress: es **dos cosas
sincronizadas por un fragmento de código**, y su clasificación vive en tres
sitios. El camino, eslabón a eslabón, leído sobre el material de `.local/`
(que no se publica, [ADR-0009](ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md)):

| # | Pieza | Qué hace |
|---|---|---|
| 1 | El formulario de alta | Una entrada del gestor de formularios con **123 campos** en secciones plegables: ajustes, resolución, corrección, diseño, gestión, calendario, especificaciones, opciones de acceso, campos personalizados, formulario anexo, documentación, modelos, listados, administración |
| 2 | La acción «crear entrada» | Crea un contenido del **constructor de páginas** con el título, el curso, el ámbito y una categoría del constructor sacada del desplegable de estado |
| 3 | Un fragmento del sitio | Copia el ID del contenido creado de vuelta a un campo de la entrada, con SQL interpolado sin `prepare` |
| 4 | Otro fragmento | Corrige el `post_parent` según la tipología, leyendo `$_POST` |
| 5 | Una vista del gestor de formularios | Pinta la página pública de la convocatoria: 24 KB de plantilla con parámetros de la URL |
| 6 | Un tercer fragmento | Unas doscientas líneas de JavaScript interceptando `keydown`, `select`, `mouseup` y `blur` para que quien edita no borre el sufijo «(curso XXXX-XXXX)» del título |
| 7 | Un cuarto | Al marcar una casilla, clona un formulario plantilla y tres vistas por convocatoria, reescribiendo IDs de campo con expresiones regulares sobre el HTML de la vista |

Y estas son las medidas, redondeadas, tomadas sobre la misma foto del
2026-09-15:

| Qué | Medida |
|---|---|
| Formulario de alta | 123 campos |
| Formulario de solicitud del centro | 94 campos, con una clave compuesta «ID de convocatoria · código de centro» escrita en un campo de texto por concatenación de cadenas |
| Vistas | 279, entre ellas la tabla de gestión y la de solicitudes de convocatorias concretas, duplicadas físicamente |
| Fragmentos de código | Más de un centenar en el sitio; una decena son «un fragmento por convocatoria y curso», con IDs de campo del año escritos dentro |
| Volumen | Unas 300 convocatorias en cuatro cursos; cerca de 18.000 solicitudes de centros |

De la tabla se leen tres cosas sin interpretar nada:

1. **El dato vive por duplicado.** El título está en la entrada y en
   `post_title`; el estado está en un desplegable de la entrada y en una
   categoría del constructor; el ámbito está en una casilla multivalor
   serializada de la entrada y en una taxonomía del contenido. Cada consumidor
   reimplementa el `unserialize`.
2. **No hay modelo, hay tecleo.** La clave compuesta se construyó a
   posteriori con un fragmento de un solo uso, y se consulta con `LIKE`. Nada
   en la base de datos impide dos solicitudes del mismo centro.
3. **Lo que no cabe en el formulario se resuelve clonando.** Cada convocatoria
   con una necesidad propia tiene un formulario anexo, tres vistas y, a
   veces, un fragmento de JavaScript con sus IDs.

## Problema

¿Cuál debe ser la capa de persistencia del dominio «procedimiento»: seguir
siendo una entrada del gestor de formularios más un contenido del constructor,
un tipo de contenido de WordPress con metas, o páginas con bloques?

## Factores de decisión

- **Una sola fuente de verdad por dato.** Estado, ámbito, curso y título en
  un sitio, no en tres.
- **Que se pueda leer y probar.** Hoy no hay diff posible de un campo
  `longtext`, ni un test.
- **Los permisos tienen dónde apoyarse.** Un contenido tiene autor,
  taxonomías y capacidades; una entrada de formulario, no.
- **El crecimiento no puede ser lineal en convocatorias.** Una convocatoria
  nueva no puede ser un formulario y tres vistas nuevas.
- **Lo que se despliega es un Code Snippet** ([ADR-0001](ADR-0001-repo-entorno-desarrollo-no-plugin.md)):
  la solución tiene que caber en PHP que se pega en el escritorio.
- **Se pierde el constructor visual**, y conviene decirlo antes de decidir.

## Alternativas consideradas

### Opción 1: seguir con el gestor de formularios, ordenado

Mantener la entrada como fuente de verdad, limpiar el formulario, eliminar la
doble representación y meter la lógica de los fragmentos en menos fragmentos.

| Pros | Contras |
|---|---|
| Coste inicial cero; el equipo lo conoce. | La lógica sigue en campos de texto y en plantillas HTML sin versión ni test. |
| Añadir un campo es arrastrarlo. | Sigue sin haber unicidad, ni permisos por contenido, ni estado calculado. |
| | Los fragmentos «por convocatoria» no desaparecen: no hay dónde meter una pregunta configurable. |

### Opción 2: tipo de contenido con metas y taxonomías (elegida)

`prc_procedure`, metas registradas con `register_post_meta`, `prc_area` y
`prc_course`, estado derivado, y la solicitud como segundo tipo de contenido
colgado del procedimiento.

| Pros | Contras |
|---|---|
| Un dato, un sitio: `post_title`, metas y términos. | Todo hay que escribirlo: alta, edición, listados, pantallas. |
| Lo da el escritorio gratis: listados, columnas, filtros, papelera, revisiones, REST. | Cambiar un campo es editar `src/Prc/`, empaquetar y sincronizar. |
| Autor, taxonomía y capacidades para el acotado por ámbito y por centro. | Hace falta alguien con PHP para lo que hoy se hace arrastrando. |
| Las preguntas configurables son una meta con esquema, no un formulario clonado. | |
| Se prueba con PHPUnit sobre WordPress vivo. | |

### Opción 3: páginas del editor de bloques con bloques propios

Cada procedimiento como una página con un bloque «procedimiento» cuyos
atributos son los datos, y un bloque «formulario de solicitud».

| Pros | Contras |
|---|---|
| Edición visual dentro de WordPress, sin gestor de formularios. | Los datos viven en atributos de bloque dentro de `post_content`: no se consultan con `meta_query` ni `tax_query`, no se acotan con `map_meta_cap` por campo. |
| El público ya lo conoce. | El sitio de destino usa un constructor de terceros, no el editor de bloques; cambiar de constructor no es una decisión de este aplicativo. |
| | Un bloque con formulario que escribe solicitudes es el mismo trabajo que la opción 2 más el bloque. |

## Decisión

**Haremos la opción 2.** El procedimiento es el tipo de contenido
`prc_procedure` y la solicitud es `prc_application`, ambos registrados por
`PostType/ProcedurePostType` y `PostType/ApplicationPostType` en `src/Prc/`,
empaquetados por `make bundle` en un único Code Snippet.

- **El título es el título.** El curso es un término de `prc_course`
  ([ADR-0012](ADR-0012-una-taxonomia-por-dimension.md)) y se compone al
  pintar, no se fuerza con JavaScript dentro del campo.
- **El estado se calcula** a partir de las fechas
  ([ADR-0013](ADR-0013-el-estado-del-procedimiento-se-deriva-de-las-fechas.md));
  no hay desplegable ni categoría.
- **Cada dato es una meta con tipo, `sanitize_callback` y `auth_callback`**,
  con las claves en `Meta/ProcedureMetaKeys`: fechas en `Y-m-d`, enlaces como
  URL, listas cerradas en código para audiencia y titularidad. No hay campos
  de texto con lógica dentro.
- **Lo que hoy es un formulario anexo clonado es una meta con esquema**,
  `prc_questions`, validada por una función pura
  ([ADR-0019](ADR-0019-la-solicitud-es-nucleo-fijo-mas-preguntas.md)).
- **La solicitud cuelga del procedimiento** por `post_parent`, con el centro
  en una meta y unicidad comprobada antes de crear
  ([ADR-0018](ADR-0018-la-solicitud-es-un-contenido-del-procedimiento.md)).
- **La página pública es el `single` del tipo de contenido**, pintada entera
  por código ([ADR-0022](ADR-0022-las-pantallas-son-paginas-pintadas-enteras.md)).
- **El sistema anterior no se toca.** Sigue vivo con sus convocatorias y sus
  solicitudes; no se migra nada en la fase 1 (ADR-0024).

### Lo que se pierde, dicho sin adornos

- **El constructor visual.** Hoy añadir un campo al alta es arrastrarlo sin
  desplegar nada. Con el tipo de contenido es editar `src/Prc/`, `make
  bundle`, sincronizar y, en producción, pegar el bundle.
- **La inmediatez del diseño.** Color de cabecera, cartel y CSS por
  convocatoria son hoy campos del formulario; pasan a ser el cartel
  (`thumbnail`) y nada más.
- **Años de conocimiento** del gestor de formularios y sus vistas. En la
  parte de código se empieza de cero.

### Criterios de aceptación de esta decisión

1. Un procedimiento se crea, edita, publica y archiva sin tocar el sistema
   anterior.
2. Título, estado, ámbito y curso se leen de un solo sitio cada uno.
3. Añadir una pregunta a un procedimiento no clona ningún formulario ni vista.
4. `make check` pasa: PHPCS, PHPMD, PHPUnit, provisión y publicación.

## Consecuencias

### Positivas

- Un solo modelo mental: «un procedimiento es un contenido, y sus solicitudes
  son sus hijos».
- El dominio queda cubierto por tests y por CI; hoy no hay ni una prueba.
- El escritorio da gratis listados, columnas, filtros, búsqueda, papelera y
  revisiones.
- El acotado por ámbito y por centro tiene dónde apoyarse.
- Desaparecen la doble representación, la clave compuesta, el sufijo forzado
  por JavaScript y los fragmentos «por convocatoria».

### Negativas

- Trabajo inicial considerable: cinco pantallas, dos tipos de contenido, dos
  taxonomías y el guardián hay que escribirlos.
- Disciplina permanente: editar en `src/Prc/`, nunca en el bundle.
- Convivencia: dos sistemas vivos, y dos sitios donde mirar cuando algo no
  cuadra, hasta que se decida la migración.
- Dependencia de una persona con PHP para cualquier cambio de campo.

### Neutras

- Los fragmentos de código del sitio no desaparecen con esta decisión: los
  que sostienen las convocatorias dejan de hacer falta cuando se apague el
  sistema anterior; los de inicio de sesión y directorio siguen, porque no
  son de este aplicativo ([ADR-0016](ADR-0016-el-centro-de-la-persona-es-una-meta-configurable.md)).
- Las vistas del sistema anterior se conservan en `.local/` como
  documentación del comportamiento actual, no como código a portar.
