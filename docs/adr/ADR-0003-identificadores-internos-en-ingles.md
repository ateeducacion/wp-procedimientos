---
id: ADR-0003
title: "Identificadores internos en inglés, lo que lee una persona en castellano"
status: Propuesta
date: 2026-09-15
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0004, ADR-0011, ADR-0012, ADR-0013, ADR-0024]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-fable-5-1"
---

# ADR-0003: Identificadores internos en inglés, lo que lee una persona en castellano

## Estado

Propuesta (2026-09-15). Fijada antes de escribir la primera línea que se
despliegue.

## Contexto

El vocabulario de este dominio es castellano: procedimiento, solicitud,
centro, ámbito, curso escolar, subsanación. El de WordPress y el de PHP no lo
es. En algún punto hay que decidir dónde acaba una lengua y empieza la otra,
porque si no se decide, se decide sola y mal.

Cómo queda cuando se decide sola está a la vista en el sistema que se
sustituye (foto en `.local/`, que no se versiona):

- Las dos taxonomías de la convocatoria tienen slugs en castellano y las
  registra un fragmento de código cuyas funciones tienen nombre inglés; otro
  fragmento las consulta con un slug distinto que no existe.
- La meta de usuario que guarda el código de centro es una palabra castellana
  **sin prefijo**, compartida con otro aplicativo del mismo sitio; la del
  ámbito, otra. Ninguna dice de quién es.
- El rol del equipo directivo tiene un slug en castellano cuyo rótulo se
  cambió después; el slug, ya repartido entre más de mil cuentas, no.
- El estado de la convocatoria es un desplegable de siete rótulos en
  castellano que escribe en una categoría del constructor de páginas, y la
  clave de unicidad de una solicitud es dos números concatenados en un campo
  de texto.

No es que el castellano sea el problema; el problema es que no hay regla, y un
identificador mal puesto es para siempre.

Aquí el coste de aplicar la regla es cero, y solo lo es hoy: el repositorio no
tiene ningún commit, no existe ninguna base de datos con un `prc_procedure`
dentro y nada del sistema anterior se migra en la fase 1
([ADR-0024](ADR-0024-las-solicitudes-de-hoy-no-se-migran.md)). Renombrar una
clave de meta es un `sed`. Renombrarla con 300 procedimientos cargados es un
script de migración sobre contenido publicado.

## Problema

¿Hasta dónde llega el inglés cuando el vocabulario del dominio es castellano
y la interfaz tiene que seguir siéndolo?

## Decisión

Se adopta la misma decisión que el aplicativo de eventos, y por las mismas
razones: **la raya se traza entre lo que lee una máquina y lo que lee una
persona**, no entre código y datos.

### Qué va en cada idioma

| Qué | Idioma | Ejemplo en este repositorio |
|---|---|---|
| Slugs de tipos de contenido | inglés | `prc_procedure`, `prc_application` |
| Slugs de taxonomía | inglés | `prc_area`, `prc_course` (`Taxonomy/ProcedureTaxonomies`) |
| Claves de meta del contenido | inglés | `prc_opens_at`, `prc_amend_closes_at`, `prc_review_state` (`Meta/ProcedureMetaKeys`, `Meta/ApplicationMetaKeys`) |
| **Valores** de las listas cerradas | inglés | estados `draft` … `archived`; revisión `submitted`, `amend`, `admitted`, `excluded`; cargos `head`, `secretary`…; audiencias `schools`, `teachers`; titularidad `public`, `private` |
| Claves de meta del perfil | inglés | `prc_area`, `prc_centre_code` |
| Capacidades | inglés | `prc_apply`, `prc_manage_procedures`, `prc_review_applications`, `prc_manage_all_areas`, `prc_manage_app` |
| Slugs de rol | inglés | `prc_manager`, `prc_school_head` (`snippets/roles-and-profiles.php`). `administrator` no lleva prefijo porque no es nuestro |
| Opciones, hooks, filtros, acciones de nonce y parámetros de petición | inglés | `prc_pages_parent`, `prc_centres`, `prc_centre_code_meta_key` |
| Clases, métodos, constantes, ficheros | inglés | `ProcedureState::of()`, `src/Prc/Domain/ProcedureInput.php` |
| Docblocks `/** */` | inglés | `@param`, `@return`, la frase de resumen |
| Etiquetas de tipos de contenido y taxonomías | **castellano** | «Procedimientos», «Solicitudes», «Ámbito convocante», «Curso escolar» |
| Cadenas de interfaz, avisos y mensajes de error | **castellano** | «Su cuenta no tiene centro asignado» |
| Comentarios de dominio dentro del código | **castellano** | los que explican por qué, no qué |
| **URL de las páginas** | **castellano** | `procedimiento`, `procedimientos`, `gestion-de-procedimientos`, `editar-procedimiento`, `solicitud`, `mi-centro` (`Shell::SLUGS`) y `?procedimiento=`, `?aviso=` |
| Términos de taxonomía | **castellano** (son datos) | los nombres de ámbitos y cursos que escribe una persona |
| Documentación, CHANGELOG y mensajes de commit | **castellano** | este fichero |

### Sin excepciones

El aplicativo de eventos dobló la regla en dos listas cerradas —tipos de
sección y estados— porque eran la clave ajena del sistema del que venía y se
migraban tal cual. Aquí no hay clave ajena que conservar: el estado de hoy es
un desplegable manual que el modelo nuevo no lee
([ADR-0013](ADR-0013-el-estado-del-procedimiento-se-deriva-de-las-fechas.md)),
y las solicitudes no se migran. Por eso `ProcedureMetaKeys::states()` devuelve
`open` y no `abierto`, y `ApplicationMetaKeys::review_states()` devuelve
`amend` y no `subsanar`. La etiqueta en pantalla —«Abierto», «A subsanar»— la
pone la vista.

### Las URL siguen en castellano

Una URL no la lee una máquina: se comparte por correo, va en una resolución,
se teclea. `/procedimiento/red-de-huertos-2026-2027/` es tan visible como un
rótulo. El `rewrite` de `prc_procedure` es `procedimiento`, filtrable con
`prc_procedure_rewrite_slug` para que el sitio de destino elija el suyo; se
elija el que se elija, será en castellano. Conservar las direcciones de las
convocatorias de hoy es una decisión pendiente ligada a la migración del
histórico ([ADR-0024](ADR-0024-las-solicitudes-de-hoy-no-se-migran.md)); esta
ADR no la resuelve.

### Un identificador es una dirección, no un resumen

**Un identificador publicado no se reutiliza ni se renombra.** Se cita desde el
código, desde otros documentos, desde enlaces ya escritos y desde la base de
datos de un sitio en marcha; cambiarlo para que el nombre vuelva a describir lo
que hay dentro rompe todas esas referencias a cambio de nada que no se arregle
con una línea de texto. Lo que se corrige es **el contenido**, y se dice dónde.

**La raya está en «publicado».** Mientras nada se haya publicado —ni un
commit, ni una URL compartida, ni una fila en una base de datos ajena— un
identificador no es todavía la dirección de nadie, y ahí sí se corrige en su
sitio. Después del primer commit, los números de ADR, los slugs y las claves de
meta se congelan.

### Lo que no se toca

- **Nombres propios**: los términos de `prc_area` y `prc_course` los escribe
  una persona. No se traducen ni se transliteran.
- **Las claves de los arrays internos** que viajan entre dos métodos de la
  misma petición y no se guardan.
- **Las `key` de las preguntas** (`q1`, `q2`…): las genera el aplicativo y son
  la dirección de una respuesta ([ADR-0019](ADR-0019-la-solicitud-es-nucleo-fijo-mas-preguntas.md)).
  No cambian al reordenar ni al reetiquetar.

## Consecuencias

### Positivas

- Una sola lengua por línea: `ProcedureAccess` lee `prc_area` y devuelve un
  mensaje en castellano, y se ve de un vistazo cuál es cuál.
- Sin excepciones que explicar: a diferencia de eventos, aquí no hay ninguna
  fila con dos lenguas.
- Quien lea `WHERE meta_key = 'prc_closes_at'` entiende qué está mirando sin
  saber castellano, y el prefijo dice de quién es la meta en un sitio donde
  conviven varios aplicativos con metas de usuario sin prefijo.

### Negativas

- **Nada lo comprueba automáticamente.** PHPCS valida estilo, no idioma
  ([ADR-0004](ADR-0004-ci-y-politica-de-pruebas.md)); la regla se sostiene en
  la revisión de cada PR.
- No hay dominio de traducción: el repositorio no es un plugin
  ([ADR-0001](ADR-0001-repo-entorno-desarrollo-no-plugin.md)) y las cadenas
  van literales, sin `__()`. Traducir la interfaz sería tocar todos los
  ficheros. Se acepta porque quien lo usa trabaja en castellano.
- Los valores en inglés se ven en la exportación CSV si la vista no los
  traduce. `Applications` tiene que exportar la etiqueta, no la clave, y eso
  es una línea que alguien tiene que acordarse de escribir.

### Neutras

- Es la misma regla que en el aplicativo de eventos, así que quien trabaje en
  los dos no cambia de hábito.
- La interfaz no cambia nada respecto a lo que el personal ve hoy: los rótulos
  siguen en castellano y las direcciones también.
