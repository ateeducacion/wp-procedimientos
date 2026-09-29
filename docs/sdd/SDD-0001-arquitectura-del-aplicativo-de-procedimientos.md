---
id: SDD-0001
title: "Arquitectura del aplicativo de procedimientos"
status: Propuesta
date: 2026-09-15
related:
  issues: []
  prs: []
  adrs: [ADR-0001, ADR-0002, ADR-0003, ADR-0004, ADR-0005, ADR-0006, ADR-0007, ADR-0008, ADR-0009, ADR-0010, ADR-0011, ADR-0012, ADR-0013, ADR-0014, ADR-0015, ADR-0016, ADR-0017, ADR-0018, ADR-0019, ADR-0020, ADR-0021, ADR-0022, ADR-0023, ADR-0024, ADR-0025, ADR-0026]
  sdds: []
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-fable-5-1"
---

# SDD-0001: Arquitectura del aplicativo de procedimientos

## Estado

**Propuesta**, escrita el 2026-09-15 sobre la foto del sistema que se
sustituye, tomada ese mismo día. El material de esa investigación —formularios,
vistas, exportaciones y fragmentos de código de la instalación de hoy— vive en
`.local/`, que no se versiona ([ADR-0009](../adr/ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md)).

Nada de lo que aquí se describe está desplegado. El sitio de destino sigue
funcionando exactamente como lo describe esa investigación y no se ha tocado.

## Resumen

Un **procedimiento** es una convocatoria (proyecto, programa, red, concurso…)
a la que un **centro educativo** se inscribe a través de su **equipo
directivo**: tiene un plazo de solicitud, un plazo de subsanación, una
resolución y unos listados de admisión, y pide al centro unas **opciones**
(preguntas) propias de cada convocatoria. Quien lo convoca —el personal de un
**ámbito**— lo da de alta, lo publica y gestiona las solicitudes que llegan.

Hoy eso lo hace un gestor de formularios con un formulario de alta de **123
campos**, otro de solicitud de **94 campos**, un tercero para las opciones, un
cuarto que es el catálogo de centros, y **279 vistas** —plantillas HTML con
condicionales dentro de campos de texto— que pintan cada pantalla; encima, un
constructor de páginas que convierte cada convocatoria en una página, y **más
de un centenar de fragmentos de código** que parchean lo que el resto no
alcanza. El estado de una convocatoria es un desplegable que alguien tiene que
acordarse de cambiar, así que hay iconos para «abierta con el plazo
finalizado» y «cerrada con el plazo abierto». La cifra medida está en
`.local/`.

Este diseño baja el dominio a WordPress nativo, con el mismo esqueleto que el
aplicativo de eventos ([ADR-0010](../adr/ADR-0010-se-parte-del-esqueleto-del-aplicativo-de-eventos.md)):
dos tipos de contenido (`prc_procedure`, `prc_application`), dos taxonomías
(`prc_area`, `prc_course`), un estado **derivado de las fechas**, un único
guardián de permisos acotado por ámbito y por centro, y cinco pantallas
pintadas enteras por código versionado y con tests. Todo se empaqueta con
`make bundle` en un Code Snippet más un snippet suelto de roles.

## Contexto

Lo que hay hoy, en una línea cada cosa (detalle y cifras en `.local/`):

| Concepto | Hoy |
|---|---|
| Convocatoria | Una entrada de un formulario de 123 campos que, por una acción del gestor de formularios, crea una página del constructor; el título lleva el curso entre paréntesis por obligación de un fragmento de JavaScript |
| Estado | Un desplegable de siete valores que escribe en una taxonomía del constructor de páginas; se cruza a mano con la fecha límite para pintar ocho iconos |
| Ámbito y curso | Dos taxonomías registradas por un fragmento de código; el ámbito es un árbol de 32 términos y se filtra por una meta de usuario con dos fragmentos distintos (uno en servidor, otro en el navegador) |
| Solicitud de un centro | Una entrada de un formulario de 94 campos con una clave compuesta «id de convocatoria · código de centro» escrita en un campo de texto; hasta cuatro docentes coordinadores con condiciones cableadas convocatoria a convocatoria |
| Centro de la persona | Una meta de usuario (código de centro) que escribe al iniciar sesión un fragmento que consulta el directorio corporativo, y un rol de dirección asignado por el mismo fragmento |
| Opciones que pide la convocatoria | Cuatro pares «título / indicaciones» con respuesta libre, más un subformulario de opciones (casillas o lista), más —si no basta— un formulario anexo clonado de una plantilla por convocatoria |
| Subsanación | Dos fechas («inicio / fin de reclamaciones»), un valor del desplegable de estado y una sección del formulario de solicitud con ficheros y descripción |
| Resolución y listados | Ficheros PDF subidos al gestor de formularios: resolución, corrección, listado provisional, definitivo y «otro» |
| Gestión de solicitudes | Vistas con tabla por convocatoria filtradas por parámetros de la URL y exportación a CSV con un complemento |
| Volumen | Unas 300 convocatorias en cuatro cursos, unas 17.700 solicitudes de centros, 1.676 centros en el catálogo y unas 1.350 cuentas de dirección |

Lo que pide el encargo (recogido el 2026-09-15): «un entorno donde dar de alta
procedimientos para que los centros educativos se apunten por parte de los
equipos directivos, y puedan gestionar la gente que se inscribe, las opciones
que se piden en el procedimiento, las fechas en las que se activa el
procedimiento y el estado, los plazos de subsanación y demás, la URL de la
resolución —mejor que mantener copia—. Ahora mismo es harto complejo y seguro
que se puede simplificar».

## Problema

Quien convoca no puede razonar sobre el estado ni sobre los permisos; quien
desarrolla no puede revisar un cambio porque la lógica vive en campos de
texto; y cada convocatoria con una necesidad nueva acaba clonando un
formulario y tres vistas. Hay que sustituirlo por algo con modelo de datos,
permisos y pantallas que se lean en código y se prueben.

## Objetivos

1. Dar de alta, editar, publicar y archivar un procedimiento desde una sola
   pantalla, con permisos acotados por ámbito.
2. Que el estado del procedimiento se calcule a partir de sus fechas y no
   pueda contradecirse.
3. Que un centro presente **una** solicitud por procedimiento, la edite
   mientras el plazo esté abierto o durante la subsanación si se le pide, y
   vea en qué estado está.
4. Que quien gestiona el procedimiento vea las solicitudes, las admita,
   excluya o pida subsanar con una nota, y las exporte a CSV.
5. Que las opciones que pide un procedimiento sean configurables sin clonar
   nada: un núcleo fijo más preguntas por procedimiento.
6. Que la resolución y los listados sean enlaces.
7. Que todo pase `make check` y se despliegue como Code Snippet.

## No objetivos

- Migrar las solicitudes ni los documentos ya presentados (ADR-0024).
- Subida de documentación por parte del centro (proyecto, memoria, aceptación
  firmada): fase 2, con su propia ADR.
- Convocatorias dirigidas al profesorado individual o al alumnado: el modelo
  deja el hueco (`prc_audience`) pero la fase 1 solo implementa centros.
- Certificaciones del profesorado participante.
- Correos y recordatorios automáticos: fase 1, después del esqueleto.
- Conservar las URL públicas de las convocatorias de hoy: decisión pendiente,
  ligada a la migración del histórico.

## Especificación / Diseño propuesto

### Esqueleto del repositorio

Idéntico al aplicativo de eventos, prefijo `prc_` / `PRC_`, namespace `Prc`,
snippets «PRC — …», montaje en `wp-content/prc-dev`, puertos `8698`/`8699`.
Fuente de verdad `src/Prc/`; `make bundle` genera
`snippets/prc-procedimientos-app.bundle.php`; `snippets/roles-and-profiles.php`
va suelto. `src/Prc/load-order.php` es la lista única de carga.

### Tipos de contenido

**`prc_procedure` — Procedimiento.** `public`, no jerárquico, `show_in_rest`,
rewrite `procedimiento` (filtrable con `prc_procedure_rewrite_slug`), soporta
`title`, `editor` (descripción, objetivos, a quién va dirigido), `excerpt`,
`thumbnail` (cartel). La página pública es el `single` del CPT, pintado entero
por `PublicFront/ProcedureView` (ADR-0022). Metas, todas registradas con
`register_post_meta` (tipo, `sanitize_callback`, `auth_callback` que delega en
`Access\ProcedureAccess`), claves en `Meta/ProcedureMetaKeys`:

| Clave | Tipo | Qué es |
|---|---|---|
| `prc_opens_at` | string `Y-m-d` | Inicio del plazo de solicitud |
| `prc_closes_at` | string `Y-m-d` | Fin del plazo de solicitud (inclusive) |
| `prc_amend_opens_at` | string `Y-m-d` | Inicio del plazo de subsanación |
| `prc_amend_closes_at` | string `Y-m-d` | Fin del plazo de subsanación (inclusive) |
| `prc_resolution_url` | string URL | La resolución que aprueba la convocatoria |
| `prc_provisional_list_url` | string URL | Listado provisional de admitidos |
| `prc_final_list_url` | string URL | Listado definitivo de admitidos |
| `prc_contact_email` | string email | Correo de contacto del ámbito convocante |
| `prc_audience` | string | `schools` (fase 1) o `teachers`; lista cerrada `ProcedureMetaKeys::audiences()` |
| `prc_ownership` | array de string | A quién se dirige: `public`, `private` (concertados y privados); lista cerrada |
| `prc_requires_coordinator` | boolean | La solicitud pide una persona coordinadora |
| `prc_questions` | array | Preguntas del procedimiento (ver más abajo) |
| `prc_commitments` | string | Texto de compromiso que el centro acepta al solicitar |
| `prc_archived` | boolean | Histórico: cierra la edición (ADR-0023) |

**`prc_application` — Solicitud.** `public => false`, `show_ui => true` (solo
para diagnóstico en el escritorio), `post_parent` = el procedimiento,
`post_author` = quien la presenta, `post_status` `publish` al presentarla.
Título generado: «{nombre del centro} — {título del procedimiento}». Metas
en `Meta/ApplicationMetaKeys`:

| Clave | Tipo | Qué es |
|---|---|---|
| `prc_centre_code` | string | Código del centro que solicita (viene de la persona, ADR-0016) |
| `prc_centre_name` | string | Nombre del centro en el momento de solicitar (foto, no referencia) |
| `prc_applicant_position` | string | Cargo: `head`, `deputy_head`, `head_of_studies`, `secretary`, `other`; lista cerrada `ApplicationMetaKeys::positions()` |
| `prc_coordinator` | array `{name, email}` | Persona coordinadora, si el procedimiento la pide |
| `prc_answers` | array | Respuestas, indexadas por la `key` de cada pregunta; **sin ficheros** |
| `prc_files` | array | Descriptores de los documentos privados, indexados por la `key` de su pregunta: `{id, name, mime, size, sha256, stored}`, con `stored` **relativo** a la raíz privada. Ni ruta absoluta, ni URL, ni identificador de adjunto (ADR-0028) |
| `prc_review_state` | string | `submitted` (presentada), `amend` (a subsanar), `admitted`, `excluded`; lista cerrada `ApplicationMetaKeys::review_states()` |
| `prc_review_note` | string | Nota de quien gestiona; obligatoria al pedir subsanar o excluir |

**Una solicitud por centro y procedimiento** (ADR-0018): antes de crear,
`PublicFront/Applications::find( $procedure_id, $centre_code )`; si existe, se
edita esa.

### Taxonomías (`Taxonomy/ProcedureTaxonomies`)

| Taxonomía | Eje | Notas |
|---|---|---|
| `prc_area` | Ámbito convocante | **Jerárquica** (servicio → área). Es el eje de permisos (ADR-0014). Términos = datos, en castellano |
| `prc_course` | Curso escolar | No jerárquica. Un término por curso, p. ej. `2026-2027` |

El estado **no** es un término (ADR-0012, ADR-0013). «Dirigido a» y
«tipología» son listas cerradas en código.

### Estado derivado (`Domain/ProcedureState`)

Función pura `ProcedureState::of( array $meta, string $today, bool $published )`
que devuelve una de las constantes de `ProcedureMetaKeys::states()`, en este
orden de prioridad:

| Estado | Etiqueta | Condición |
|---|---|---|
| `draft` | Borrador | `post_status !== 'publish'` |
| `archived` | Histórico | `prc_archived` |
| `resolved` | Resuelto | `prc_final_list_url` no vacía |
| `amendment` | En subsanación | `prc_amend_opens_at <= hoy <= prc_amend_closes_at` |
| `open` | Abierto | `prc_opens_at <= hoy <= prc_closes_at` |
| `upcoming` | Próximo | `hoy < prc_opens_at` o sin fecha de apertura |
| `closed` | Cerrado | El resto (plazo pasado, sin subsanación en curso ni listado definitivo) |

Normalizaciones al guardar (`Domain/ProcedureInput`): si falta `prc_closes_at`
se copia `prc_opens_at`; si `prc_amend_closes_at` falta se copia
`prc_amend_opens_at`; un plazo de subsanación anterior al cierre del de
solicitud es un error de validación. La comparación es por día (`Y-m-d`), en
la zona horaria del sitio.

Lo que cada estado permite:

| Estado | El centro | Quien gestiona |
|---|---|---|
| `draft` | No lo ve | Edita todo |
| `upcoming` | Lo ve, no solicita | Edita todo |
| `open` | Presenta o edita su solicitud | Edita todo; ve solicitudes |
| `closed` | Ve su solicitud | Edita enlaces y fechas; gestiona solicitudes |
| `amendment` | Edita su solicitud **solo si** está `amend` | Gestiona solicitudes |
| `resolved` | Ve su solicitud y el listado | Edita enlaces; gestiona solicitudes |
| `archived` | Ve | Nada, salvo desarchivar (administración) |

### Preguntas del procedimiento (`Domain/ProcedureQuestions`, ADR-0019)

`prc_questions` es una lista de hasta 20 preguntas:

```
{ key: 'q1', label: 'Modalidad', help: '', type: 'single', required: true,
  choices: ['Modalidad A', 'Modalidad B'] }
```

`type` ∈ `text` (respuesta libre, hasta 2.000 caracteres), `single` (una
opción), `multiple` (varias), `yesno`, `file` (un documento, ADR-0028). `key`
la genera el aplicativo (`q` + número) y no cambia al reordenar; las respuestas
se guardan por `key`. `ProcedureQuestions::sanitize()` y
`::validate_answers( $questions, $answers )` son puras: sin WordPress, y por
eso una pregunta `file` **no pasa por ellas** —el fichero es del borde de la
aplicación, y su descriptor va a `prc_files`, no a `prc_answers`—.

### Núcleo fijo de la solicitud (`Domain/ApplicationInput`)

Siempre: centro (código y nombre, **de solo lectura**, resueltos desde la
persona), cargo, aceptación de los compromisos (casilla obligatoria si
`prc_commitments` no está vacío); persona coordinadora si
`prc_requires_coordinator`; y las respuestas a las preguntas. Nombre, correo y
apellidos de quien solicita **no se copian**: son `post_author`, y se leen de
la cuenta cuando hacen falta.

### Permisos (`Access/ProcedureAccess`, único guardián)

Capacidades del aplicativo:

| Capacidad | Quién la tiene en local | Permite |
|---|---|---|
| `prc_apply` | `prc_school_head` | Presentar y editar la solicitud **de su centro** |
| `prc_manage_procedures` | `prc_manager` | Crear, editar, publicar y archivar procedimientos **de sus ámbitos** |
| `prc_review_applications` | `prc_manager` | Ver, admitir, excluir, pedir subsanar y exportar solicitudes de esos procedimientos |
| `prc_manage_all_areas` | `administrator` | Lo anterior en todos los ámbitos |
| `prc_manage_app` | `administrator` | Ajustes, diagnóstico y desarchivar |

Acotados, ambos **fail-closed**:

- **Por ámbito**: la meta de usuario `prc_area` (uno o varios IDs de término
  de `prc_area`; un término incluye sus descendientes). Sin ámbito y sin
  `prc_manage_all_areas`, no se edita ni se ve nada en el taller.
- **Por centro**: `Access\CentreScope::code_for( $user_id )` lee la meta cuya
  clave devuelve el filtro `prc_centre_code_meta_key` (por defecto
  `prc_centre_code`). Sin código válido, `prc_apply` no sirve para nada
  (ADR-0016).

La capa que protege es `map_meta_cap` sobre `edit_post`, `delete_post`,
`publish_post` y `read_post` de ambos CPT; el listado del escritorio se acota
además con `pre_get_posts`. Roles: los crea el snippet suelto
`snippets/roles-and-profiles.php`; el aplicativo solo comprueba capacidades
(ADR-0015). Ese mismo snippet añade a la ficha de usuario del escritorio los
campos «Ámbitos» (para quien gestiona) y «Código de centro» (para quien
solicita), editables solo por administración.

### Catálogo de centros (`Domain/CentreCatalog`, ADR-0017)

`CentreCatalog::all()` devuelve `[{code, name, ownership}]` a través del
filtro `prc_centres`; por defecto lee `wp-content/uploads/prc/centres.json`
si existe, y si no, una lista vacía. `find( $code )` y `search( $q )` trabajan
sobre eso. El mu-plugin de desarrollo contesta el filtro con un catálogo
**inventado** de una docena de centros; el catálogo real no se versiona.
`ownership` ∈ `public` | `private` y se cruza con `prc_ownership` del
procedimiento para decidir si el centro puede solicitar.

### Pantallas (`PublicFront/`)

Páginas de WordPress con un shortcode cada una, creadas por
`scripts/setup-pages.php` colgando de la página madre de la opción
`prc_pages_parent`; slugs en `Shell::SLUGS`, shortcodes en `Shell::SHORTCODES`,
pintadas enteras en `template_redirect` como en el aplicativo de eventos
(Bootstrap 5 y Bootstrap Icons desde CDN con SRI, SweetAlert2 para
confirmaciones, con degradación a `confirm()` y a formulario sin JavaScript).

| Clave | Slug | Shortcode | Quién | Qué |
|---|---|---|---|---|
| `home` | `procedimientos` | `[prc_home]` | Público | Listado por curso: abiertos, en subsanación, próximos, cerrados y resueltos; filtro por ámbito; tarjeta con estado, fechas y ámbito |
| `procedure` | (single de `prc_procedure`) | — | Público | Ficha: descripción, fechas, estado, ámbito, contacto, enlaces a resolución y listados; botón «Solicitar» / «Mi solicitud» / «Gestionar» según quién mira |
| `workspace` | `gestion-de-procedimientos` | `[prc_workspace]` | `prc_manage_procedures` | Mis procedimientos por curso, con estado y nº de solicitudes; «Nuevo procedimiento» |
| `editor` | `editar-procedimiento` | `[prc_editor]` | `prc_manage_procedures` | El taller del procedimiento, una pantalla con paneles: **Datos** (título, descripción, ámbito, curso, dirigido a, fechas, contacto, compromisos, coordinación), **Preguntas** (constructor), **Enlaces** (resolución, listados), **Solicitudes** (tabla con centro, código, fecha, cargo, coordinación, estado de revisión; acciones admitir / subsanar / excluir con nota; exportar CSV), **Publicación** (publicar, archivar) |
| `apply` | `solicitud` | `[prc_apply]` | `prc_apply` | Formulario de solicitud del centro para `?procedimiento=ID`, o la solicitud presentada con su estado y la nota de revisión |
| `mine` | `mi-centro` | `[prc_mine]` | `prc_apply` | Las solicitudes del centro: procedimiento, fecha, estado del procedimiento, estado de revisión |

Toda escritura va por POST con nonce y comprobación de capacidad, patrón
«POST → redirect → GET» con avisos en la sesión de la URL (`?aviso=`). Borrar
es enviar a la papelera (ADR-0007). La edición de un procedimiento usa el
bloqueo de edición de WordPress (ADR-0008).

### Ficheros de `src/Prc/` (fase 0)

```
load-order.php, bootstrap.php, App.php
Meta/ProcedureMetaKeys.php          claves, listas cerradas (estados, audiencias, titularidad)
Meta/ProcedureMetaRegistration.php  register_post_meta con sanitize y auth
Meta/ApplicationMetaKeys.php        claves, cargos, estados de revisión
Meta/ApplicationMetaRegistration.php
Domain/DateRange.php                (del aplicativo de eventos)
Domain/ProcedureState.php           estado derivado, puro
Domain/ProcedureQuestions.php       preguntas y validación de respuestas, puro
Domain/ProcedureInput.php           valida el alta/edición del procedimiento, puro
Domain/ApplicationInput.php         valida la solicitud, puro
Domain/CentreCatalog.php            filtro prc_centres, find/search
Access/CentreScope.php              código de centro de la persona (fail-closed)
Access/ProcedureAccess.php          único guardián: caps, ámbito, map_meta_cap, pre_get_posts
PostType/ProcedurePostType.php
PostType/ApplicationPostType.php
Taxonomy/ProcedureTaxonomies.php
PublicFront/Assets.php, ExitSignal.php, Shell.php, EditLock.php   (del aplicativo de eventos)
PublicFront/Home.php + View/HomeView.php
PublicFront/ProcedureView.php + View/ProcedureChrome.php
PublicFront/Workspace.php + View/WorkspaceView.php
PublicFront/ProcedureEditor.php + View/ProcedureEditorView.php + View/PanelParts.php
PublicFront/Applications.php        crear/editar/buscar solicitudes, revisión, CSV
PublicFront/ApplicationFiles.php    documentos privados: política, almacén, descarga autorizada (ADR-0028)
PublicFront/ApplyForm.php + View/ApplyFormView.php
PublicFront/MyCentre.php + View/MyCentreView.php
Admin/ProcedureAdmin.php            columnas, filtro por ámbito, acotado del listado
Admin/Settings.php                  ajustes y diagnóstico (prc_manage_app)
```

Todo `final class` con métodos `static`, sin contenedor, sin interfaces, sin
`declare(strict_types=1)`. Los módulos `View/*` solo pintan: reciben un array
y devuelven HTML escapado.

### Aprovisionamiento

- `scripts/provision-roles.php`: llama a las funciones del snippet de roles.
- `scripts/setup-vocabulary.php`: árbol de ámbitos y cursos **de
  demostración** (nombres inventados, sin organización real).
- `scripts/setup-pages.php`: las páginas de `Shell::SLUGS`.
- `scripts/seed-demo.php`: cuentas `gestion`, `gestion2` (gestores de dos
  ámbitos), `direccion`, `direccion2` (dirección de dos centros del catálogo
  inventado); procedimientos en cada estado; solicitudes en cada estado de
  revisión.
- `scripts/mu-plugins/prc-dev-tools.php`: cambio de cuenta desde la barra
  superior, catálogo inventado por `prc_centres`, reescritura de CDN a
  `node_modules`.
- `blueprint.json` aterriza en `/gestion-de-procedimientos/`.

## Comportamiento ante errores y casos límite

- **Procedimiento sin fechas**: `upcoming`; se publica igual, pero nadie puede
  solicitar hasta que tenga plazo.
- **Persona con `prc_apply` sin código de centro**: la pantalla `apply` lo
  dice («Su cuenta no tiene centro asignado») y no ofrece el formulario.
- **Código de centro que no está en el catálogo**: se solicita igual con el
  código y nombre vacío; quien gestiona lo ve marcado. No se bloquea, porque
  el catálogo puede ir por detrás de la realidad.
- **Centro privado en un procedimiento solo para públicos**: el botón
  «Solicitar» no aparece y el POST se rechaza con aviso.
- **Segunda solicitud del mismo centro**: se redirige a editar la existente.
- **Editar fuera de plazo**: el POST se rechaza; la pantalla lo explica.
- **Pregunta borrada después de que haya respuestas**: la respuesta se
  conserva en `prc_answers` bajo su `key` y deja de pintarse. Si era de tipo
  `file`, el descriptor se conserva igual en `prc_files` y el documento sigue
  en el almacén privado hasta que se borre la solicitud entera.
- **Editar la solicitud sin volver a adjuntar**: el documento ya presentado se
  conserva y no se vuelve a pedir aunque la pregunta sea obligatoria. Adjuntar
  otro lo sustituye, y el anterior no se borra hasta que el nuevo está guardado
  (ADR-0028).
- **Documento que no pasa la política** (tipo no admitido o demasiado grande):
  la solicitud no se guarda, y una que ya existía se queda exactamente como
  estaba.
- **Ámbito borrado**: los procedimientos quedan sin ámbito y solo los ve
  `prc_manage_all_areas`.
- **Fallo del snippet**: el sitio no se rompe; `App::boot()` es idempotente y
  todo va detrás de `function_exists`/`class_exists` donde toca.

## Estrategia de pruebas

PHPUnit sobre WordPress vivo (`make test`), un fichero por módulo en
`tests/unit/`: estado derivado (todos los estados y los límites de día),
preguntas y respuestas, validación de procedimiento y solicitud, acceso por
ámbito y por centro (fail-closed, `map_meta_cap`, `pre_get_posts`), unicidad
de solicitud, registro de CPT/taxonomías/metas, roles del snippet, pantallas
(cada shortcode pinta para quien puede y rechaza a quien no), CSV, lista de
carga, bundle, índices de ADR y planes. `make lint`, `make phpmd`,
`make check-public`, `make check-plugin` como en el aplicativo de eventos.

## Criterios de aceptación

- [ ] `make install && make up` levanta el sitio con roles, vocabulario,
      páginas y datos de demostración; `make check` en verde.
- [ ] Con la cuenta `gestion` se crea, publica y archiva un procedimiento de
      su ámbito y no se ve ninguno de otro ámbito.
- [ ] Con la cuenta `direccion` se presenta una solicitud a un procedimiento
      abierto, se edita, y una segunda solicitud del mismo centro reabre la
      primera.
- [ ] `gestion` pide subsanar con nota; `direccion` ve la nota y edita solo
      durante el plazo de subsanación; `gestion` admite; el CSV incluye la
      solicitud.
- [ ] El estado de cada procedimiento coincide con la tabla de esta SDD para
      cualquier día que se pase a `ProcedureState::of()`.
- [ ] `make bundle` pasa `php -l` y `make snippet-check`.

## ADR requeridas o referenciadas

| Decisión | ADR | Estado |
|----------|-----|--------|
| Repositorio de desarrollo, no plugin | ADR-0001 | Propuesta |
| Sincronización de snippets con `eval-file` | ADR-0002 | Propuesta |
| Identificadores en inglés, lo humano en castellano | ADR-0003 | Propuesta |
| CI y política de pruebas | ADR-0004 | Propuesta |
| Política de edición y auditoría | ADR-0005 | Propuesta |
| Librerías de terceros desde CDN con SRI | ADR-0006 | Propuesta |
| Borrar es enviar a la papelera | ADR-0007 | Propuesta |
| Bloqueo de edición con el de WordPress | ADR-0008 | Propuesta |
| El repositorio se publica sin nada de nadie | ADR-0009 | Propuesta |
| Se parte del esqueleto del aplicativo de eventos | ADR-0010 | Propuesta |
| El procedimiento es un tipo de contenido | ADR-0011 | Propuesta |
| Una taxonomía por dimensión; el estado no es un término | ADR-0012 | Propuesta |
| El estado del procedimiento se deriva de las fechas | ADR-0013 | Propuesta |
| El ámbito es un alcance de la persona, no un rol | ADR-0014 | Propuesta |
| El aplicativo comprueba capacidades; los roles del sitio no se nombran | ADR-0015 | Propuesta |
| El centro de la persona es una meta con clave configurable | ADR-0016 | Propuesta |
| El catálogo de centros no se versiona: lo contesta un filtro | ADR-0017 | Propuesta |
| La solicitud es un contenido del procedimiento, una por centro | ADR-0018 | Propuesta |
| La solicitud es un núcleo fijo más preguntas configurables | ADR-0019 | Propuesta |
| La subsanación es un plazo del procedimiento y un estado de la solicitud | ADR-0020 | Propuesta |
| La resolución y los listados son enlaces, no copias | ADR-0021 | Propuesta |
| Las pantallas son páginas de WordPress pintadas enteras por código | ADR-0022 | Propuesta |
| El estado histórico cierra la edición | ADR-0023 | Propuesta |
| Las solicitudes y documentos de hoy no se migran en la fase 1 | ADR-0024 | Propuesta |

## Pendientes y trabajo futuro

- [ ] Correos: al presentar (a quien solicita y al contacto del ámbito) y al
      cambiar el estado de revisión (a quien solicita). Fase 1.
- [ ] Documentación adjunta del centro (aceptación firmada, proyecto,
      memoria) con ficheros privados fuera del alcance del servidor web. Fase 2.
- [ ] Audiencia `teachers`. Fase 3.
- [ ] Migración del histórico y conservación de las URL. Fase 4, con ADR.
- [ ] Recordatorios de cierre de plazo.
- [ ] Estadísticas de participación por curso y ámbito.

## Referencias

- Investigación del sistema anterior: `.local/` (no se versiona).
- Encargo del 2026-09-15 (literal en REQ-0001).
- [`docs/adr/registro.md`](../adr/registro.md).

## Adenda — 2026-09-16

Lo de arriba no se reescribe: esto lo corrige y lo completa.

### El taller se cierra por estado, no solo por histórico

La tabla «Lo que cada estado permite» es ahora también código.
`Domain/ProcedureState::editable_fields( $state )` dice qué **grupos** de datos
admite cada estado —`data` (lo que el procedimiento es), `dates`, `links` y
`questions`— y `Access/ProcedureAccess::can_edit_group( $user, $id, $group )`
lo cruza con `can_edit()`. La comprobación va en el POST, antes de escribir, y
la pantalla pinta apagado lo mismo que el POST rechaza, con la frase que lo
explica (`ProcedureState::why_locked()`).

| Estado | Grupos que se editan |
|---|---|
| `draft`, `upcoming`, `open` | `data`, `dates`, `links`, `questions` |
| `closed` | `dates`, `links` |
| `amendment` | ninguno |
| `resolved` | `links` |
| `archived` | lo decide `can_edit()`, no esta tabla |

El panel «Datos» escribe dos grupos, así que en `closed` un envío se acepta a
medias: entran las fechas y el resto se reescribe con lo que ya había, en vez
de rechazar el formulario entero. Lo que de verdad protege esto son las
preguntas: borrar una de un procedimiento con solicitudes presentadas descoloca
su tabla y su CSV, y por eso ningún estado posterior al plazo las deja tocar.

`archived` no lo juzga la tabla: ya lo decidió `can_edit()` (ADR-0023), cerrado
para el ámbito que convocó y abierto para administración, que es quien tiene
que poder rematarlo y reabrirlo. Gestionar solicitudes sigue siendo cosa de
`can_review()` y no cambia: se gestionan en `closed`, `amendment` y `resolved`.
Publicar y archivar tampoco cambian: `can_publish()` y `can_toggle_archived()`.

### La subsanación empieza al día siguiente del cierre

Donde arriba se lee «un plazo de subsanación anterior al cierre del de
solicitud es un error», léase **anterior o el mismo día**: `Domain/ProcedureInput`
rechaza también que empiece el día del cierre, y se queda como está. El plazo
de solicitud es inclusive y `amendment` tiene prioridad sobre `open`, así que
una subsanación que abre ese día pone el procedimiento «En subsanación»
mientras a los centros les queda todavía ese día para presentar.

### La portada agrupa seis estados, no cinco

`PublicFront/Home` agrupa además los históricos, el último, después de los
resueltos. Un procedimiento marcado como histórico conserva su ficha pública
(ADR-0023); si el listado no lo agrupara, solo se llegaría a él por enlace
directo. El grupo de borradores no existe: un borrador no se ve fuera.

### Los ámbitos de un procedimiento son varios

`prc_area` admite **varios términos** por procedimiento, y al menos uno
([ADR-0025](../adr/ADR-0025-un-procedimiento-puede-tener-varios-ambitos.md)).
Donde arriba se lee «el ámbito del procedimiento», léase «sus ámbitos».

- `Domain/ProcedureInput` recibe `areas` —lista de identificadores de término—
  en vez de `area`, y rechaza la lista vacía con el código de error `areas`.
- `PublicFront/ProcedureEditor::may_set_area( $user_id, array $area_ids )`
  comprueba **todos** los marcados contra `ProcedureAccess::scope_areas()`;
  basta uno ajeno para rechazar el envío entero. Se salta la comprobación quien
  tenga `prc_manage_all_areas`.
- La tabla de permisos no cambia: `ProcedureAccess::post_areas()` ya devolvía
  una lista y se cruza con `scope_areas()` por intersección, así que **basta
  pertenecer a uno** de los ámbitos convocantes para abrir el taller, editar,
  publicar, archivar y revisar las solicitudes. No hay permiso parcial: la
  unidad de permiso sigue siendo el procedimiento entero.
- El listado y la ficha pintan los ámbitos separados, y el filtro por ámbito de
  la portada saca el procedimiento en todos los suyos.

### Tres correos de contacto y un color de cabecera

Dos metas de la tabla de arriba cambian, por el calco de la pantalla de alta:

| Clave | Tipo | Qué es |
|---|---|---|
| ~~`prc_contact_email`~~ → `prc_contact_emails` | array de string email | Hasta tres correos de contacto del ámbito convocante. El primero es obligatorio; el segundo y el tercero, opcionales. Los tres se validan: un campo escrito y mal rechaza el envío. Se guardan en minúsculas, sin repetidos, y el cuarto no cabe |
| `prc_header_color` | string | Color de la banda de cabecera: una clave de la paleta cerrada `ProcedureMetaKeys::header_colors()` ([ADR-0026](../adr/ADR-0026-el-aspecto-del-procedimiento-es-un-dato-con-paleta-cerrada.md)). Sin color elegido, el neutro |

La paleta son diez colores con nombre de color y un criterio escrito en el
docblock de `header_colors()`: todos alcanzan 4,5:1 de contraste con el blanco
(WCAG 2.1 AA) y no con el negro, así que el rótulo de encima es siempre blanco.
`ProcedureMetaKeys::header_hex( $slug )` traduce la clave al hexadecimal y
devuelve el neutro para lo que no esté en la lista. Un test recalcula el
contraste de toda la paleta, para que añadir un color no sea una decisión a
ojo.

La marca del despliegue —escudo, pie, enlaces legales— **no** entra aquí: sigue
siendo configuración del filtro `prc_chrome`
([ADR-0009](../adr/ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md)).

Mientras el taller siga pintando un solo campo de correo, `ProcedureEditor`
acepta también el nombre anterior, `prc_contact_email`, y lo mete como el
primero de la lista; `ProcedureView::model()` sigue ofreciendo `contact_email`
con el primero de los tres. Las dos costuras se van con la pantalla del calco.
