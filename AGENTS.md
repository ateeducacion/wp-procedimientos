# AGENTS.md — Procedimientos: instrucciones para agentes

Este es el **fichero de instrucciones canónico** para todos los agentes de
código (GitHub Copilot, Claude Code, Gemini Code Assist, Codex, Aider y otros)
que trabajen en este repositorio. Los demás ficheros de agentes (`CLAUDE.md`)
apuntan aquí.

---

## Qué es este proyecto (y qué NO es)

**Procedimientos** es el repositorio de trabajo de un aplicativo que gestiona
convocatorias —proyectos, programas, redes, concursos— a las que un centro
educativo se inscribe a través de su equipo directivo. En producción se activa
con **Code Snippets**, **Members** y **WPFront User Role Editor**; dónde, lo
dice el `.env` y **no el repositorio**
([ADR-0009](docs/adr/ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md)).
El dominio son dos CPT —`prc_procedure`, `prc_application`— y dos taxonomías
—`prc_area`, `prc_course`—. El diseño entero está en la
[SDD-0001](docs/sdd/SDD-0001-arquitectura-del-aplicativo-de-procedimientos.md):
los nombres de ficheros, clases, metas, capacidades, estados, slugs y
shortcodes son los que dice ahí.

- **NO es un plugin de WordPress** instalable en producción. No crees fichero
  principal de plugin, ni `readme.txt`, ni `register_activation_hook()`, ni
  uses `plugin_dir_path()` / `plugin_dir_url()` / `plugins_url()`. Ese
  WordPress no admite despliegue de ficheros: solo pegar código en Code
  Snippets. Todo lo demás sale de ahí.
- Fuente de verdad:
  - `src/Prc/` → código del aplicativo (**editar aquí**);
  - `make bundle` → genera `snippets/prc-procedimientos-app.bundle.php`;
  - `snippets/*.php` → `make sync-snippets` los lleva al entorno local;
    `npm run snippets` (el cliente `@erseco/code-snippets-client`) los lleva al
    sitio de destino, con las credenciales del `.env`.
- El repo se monta en el contenedor en `wp-content/prc-dev`.
- `.local/` es material de investigación del sistema anterior, con datos
  personales: **no se versiona y no se toca**.

### Lo que sustituye, en una línea cada cosa

Hace falta saberlo para no reinventar los errores de la casa:

| Hoy en producción | Aquí |
|---|---|
| Una convocatoria es una entrada de un formulario de más de cien campos que, por una acción del gestor de formularios, crea una página del constructor de páginas | CPT `prc_procedure`: la página pública es su `single`, pintado entero por `PublicFront/ProcedureView` |
| El estado es un desplegable de siete valores que alguien tiene que acordarse de cambiar, cruzado a mano con la fecha límite para pintar ocho iconos | `Domain/ProcedureState` lo **deriva de las fechas** y de los enlaces; no es un término ni un campo |
| El ámbito es un árbol de términos filtrado por una meta de usuario con dos fragmentos distintos, uno en servidor y otro en navegador | `prc_area` jerárquica en el contenido y la meta `prc_area` en la persona; lo decide un único guardián, `Access/ProcedureAccess` |
| La solicitud de un centro es una entrada de otro formulario con la clave «convocatoria · centro» escrita en un campo de texto | CPT `prc_application` con `post_parent` = el procedimiento, **una por centro** (`Applications::find()` antes de crear) |
| El centro de la persona es una meta de usuario que escribe un fragmento al iniciar sesión | `Access/CentreScope` lee la meta cuya clave devuelve `prc_centre_code_meta_key`; sin código válido, fail-closed |
| Las opciones que pide la convocatoria: cuatro pares título/indicaciones, un subformulario y, si no basta, un formulario clonado | Núcleo fijo (`Domain/ApplicationInput`) más hasta veinte preguntas en `prc_questions` (`Domain/ProcedureQuestions`) |
| La resolución y los listados son PDF subidos al gestor de formularios | Tres metas con una URL cada una |
| El catálogo de centros es un formulario más | El filtro `prc_centres`; en local lo contesta el mu-plugin con centros inventados |

**Las solicitudes y los documentos ya presentados no se migran en la fase 1**
(ADR-0024). **No se lee nada del gestor de formularios anterior** ni se añade
dependencia de él.

---

## Mapa del repositorio

| Ruta | Contenido |
|------|-----------|
| `src/Prc/` | Aplicativo (fuente; `make bundle`) |
| `src/Prc/load-order.php` | Orden de carga: lista única, la usan bootstrap y el bundler |
| `src/Prc/bootstrap.php` | Define `PRC_SRC_DIR`, recorre la lista y llama `App::boot()` |
| `build/pack-snippet.php` | Empaquetador `src/Prc/` → bundle |
| `snippets/` | Snippets sueltos + bundle generado; sync a Code Snippets |
| `scripts/` | Aprovisionamiento (`wp eval-file` / Playground) |
| `scripts/lib/snippet-sync.php` | Librería de sincronización con Code Snippets |
| `scripts/mu-plugins/` | mu-plugin solo de desarrollo |
| `tests/` | PHPUnit sobre un WordPress vivo (`tests/unit/`), Vitest sobre los guiones (`tests/js/`) y la confirmación en un navegador (`tests/browser/`) |
| `docs/adr/`, `docs/sdd/` | ADR y SDD |
| `.agents/skills/` | Skills de agentes (`.claude/skills/` lleva una copia) |
| `CHANGELOG.md` | Versión y cambios; `make bundle` lee de ahí el `@version` |
| `.env.dist` | Plantilla de configuración de despliegue (copiar a `.env`); la lee `npm run snippets` |
| `blueprint.json` / `blueprint-local.json` | Playground remoto / local |
| `.wp-env.json` / `Makefile` | Docker / comandos |
| `.local/` | Investigación del sistema anterior. **Ignorado. No lo abras ni lo cites en un commit** |

### Anatomía de `src/Prc/`

Todo son `final class` con métodos `static`. Sin contenedor de inyección, sin
interfaces, sin factorías, sin capa de servicios: es lo que permite
concatenarlo en un fichero. Los módulos `View/*` solo pintan: reciben un array
y devuelven HTML escapado.

```
src/Prc/
├── load-order.php                          Lista única y ordenada
├── bootstrap.php                           PRC_SRC_DIR + require de la lista + App::boot()
├── Meta/ProcedureMetaKeys.php              Claves de meta y listas cerradas (estados, audiencias, titularidad)
├── Meta/ApplicationMetaKeys.php            Claves de meta, cargos y estados de revisión
├── Domain/DateRange.php                    Rangos de fechas (del aplicativo de eventos)
├── Domain/ProcedureState.php               Estado derivado de las fechas. Puro
├── Domain/ProcedureQuestions.php           Preguntas y validación de respuestas. Puro
├── Domain/ProcedureInput.php               Valida el alta/edición del procedimiento. Puro
├── Domain/ApplicationInput.php             Valida la solicitud. Puro
├── Domain/CentreCatalog.php                Filtro prc_centres, find/search
├── Access/CentreScope.php                  Código de centro de la persona (fail-closed)
├── Access/ProcedureAccess.php              Único guardián: caps, ámbito, map_meta_cap, pre_get_posts
├── Meta/ProcedureMetaRegistration.php      register_post_meta con tipo, sanitize y auth_callback
├── Meta/ApplicationMetaRegistration.php    Ídem para la solicitud
├── PostType/ProcedurePostType.php          CPT prc_procedure
├── PostType/ApplicationPostType.php        CPT prc_application
├── Taxonomy/ProcedureTaxonomies.php        prc_area (jerárquica), prc_course
├── PublicFront/Assets.php                  Bootstrap 5, iconos y SweetAlert2 desde CDN con SRI
├── PublicFront/ExitSignal.php              Salida limpia tras pintar una pantalla
├── PublicFront/Shell.php                   SLUGS, SHORTCODES y el marco de cada pantalla
├── PublicFront/EditLock.php                Bloqueo de edición de WordPress
├── PublicFront/View/PanelParts.php         Piezas comunes de los paneles
├── PublicFront/Applications.php            Crear/editar/buscar solicitudes, revisión, CSV
├── PublicFront/ApplicationFiles.php        Documentos privados de una solicitud: política, almacén y descarga (ADR-0028)
├── PublicFront/View/HomeView.php           Pinta la portada
├── PublicFront/Home.php                    [prc_home]
├── PublicFront/View/ProcedureChrome.php    Pinta la ficha pública
├── PublicFront/ProcedureView.php           El single de prc_procedure
├── PublicFront/View/WorkspaceView.php      Pinta «Mis procedimientos»
├── PublicFront/Workspace.php               [prc_workspace]
├── PublicFront/View/ProcedureEditorView.php Pinta el taller y sus paneles
├── PublicFront/ProcedureEditor.php         [prc_editor]
├── PublicFront/View/ApplyFormView.php      Pinta la solicitud
├── PublicFront/ApplyForm.php               [prc_apply]
├── PublicFront/View/MyCentreView.php       Pinta «Mi centro»
├── PublicFront/MyCentre.php                [prc_mine]
├── Admin/ProcedureAdmin.php                Columnas, filtro por ámbito y acotado del listado
├── Admin/Settings.php                      Ajustes y diagnóstico (capacidad prc_manage_app)
└── App.php                                 Idempotente. Registra CPT, taxonomías y el register() de cada módulo
```

Ni una capa más. Si crees que hace falta otra, escribe la ADR primero.

---

## Qué comando ejecutar

| Situación | Comando |
|-----------|---------|
| Primera vez | `make install && make up` |
| Cambio en `src/Prc/` | `make bundle && make sync-snippets` (+ `make lint` / `make test`) |
| Cambio en un snippet suelto | `make sync-snippets` |
| Tras `make bundle`, con wp-env arrancado | `make snippet-check` (los snippets sobreviven al guardado de Code Snippets) |
| Un test concreto | `make test FILE=tests/unit/test-procedure-access.php` o `make test FILTER=nombre_del_metodo` |
| Cambio en `assets/js/` | `make test-js` (Vitest y jsdom, sin wp-env; cobertura en `artifacts/coverage-js/`) |
| Cambio en una confirmación o en `assets/js/prc-app.js` | `make test-browser` (los tres escalones: SweetAlert2, `confirm()` y sin JavaScript) |
| Ver la cobertura | `make coverage` (reinicia wp-env con Xdebug) |
| Entorno raro | `make clean` |
| Empezar de cero | `make destroy && make up` |
| Probar sin Docker | `make playground` |
| Antes de commit/PR | `make check` |
| Comprobar el código como lo revisaría WordPress.org | `make check-plugin` (errores **y avisos**) |
| Ver cómo queda todo después de un cambio | `make capturas` — y en cada PR sale solo, comentado |
| Publicar una versión | `make release` (tras cerrar el bloque del CHANGELOG) |
| Llevar un snippet al sitio de destino | `npm run snippets -- push <id> --file snippets/… --dry-run` (sin `--yes` no escribe) |

Sitio local: <http://localhost:8698> (`admin` / `password`). Tests en el 8699.

---

## Cuando quien te dirige no viene de desarrollo

Parte del equipo trabaja en este repositorio **a través de un agente**, sin
experiencia previa en desarrollo. Para esa persona está
[`docs/desarrollo-para-empezar.md`](docs/desarrollo-para-empezar.md): instalación
en macOS y en Windows, `git pull` antes de empezar, `make install` / `make up`,
el ciclo rama → `make check` → `gh pr create` → **revisión de otra persona**, y
qué mirar cuando algo falla.

Si es tu caso, dos consecuencias para ti:

- **Explica en castellano llano qué has tocado y por qué**, y qué tiene que ver
  en pantalla para comprobarlo. Un resumen que solo entiende quien ya sabe no
  sirve de nada.
- **No propongas ni ejecutes nada que se salte la revisión**: ni `push` a
  `main`, ni fusionar el PR, ni desactivar una comprobación para que `make
  check` pase. Si `make check` está en rojo, se arregla; no se rodea.

---

## Añadir código al aplicativo

**¿Clase o función suelta?** Dominio de procedimientos → `src/Prc/` (acaba
inlineado en el bundle). Helpers que otros snippets puedan usar por su cuenta →
un `snippets/*.php` propio con su cabecero `Snippet Name: PRC — …`. El bundle
llama a los sueltos con `function_exists()`: si el snippet no está desplegado,
degrada en silencio, no peta. Los roles son el caso canónico:
`snippets/roles-and-profiles.php` **no entra en el bundle**.

**Fichero nuevo en `src/Prc/`** → añádelo a `src/Prc/load-order.php`, después
de todo lo que extienda o implemente. Es la única lista; `make bundle` falla si
un fichero de `src/Prc/` no está en ella. Sin esa guarda, el fichero
simplemente no llegaría a producción, en silencio.

**El primer fichero del load-order** tiene que abrir con `namespace`: ahí es
donde el bundler inyecta la guarda `PRC_BUNDLE_LOADED`, que evita que el
segundo `eval()` de Code Snippets muera con «Cannot redeclare class». Es una
constante y no un `class_exists()` a propósito: PHP resuelve pronto las clases
sin padre y `App` ya existiría en la primera pasada.

**El bundle sale sin comentarios**: el empaquetador se los quita con el
analizador léxico de PHP —no con expresiones regulares— para que el snippet sea
más pequeño y manejable en el editor de Code Snippets. Los comentarios se leen
en `src/Prc/`, que es donde están enteros. La **cabecera no se toca**: de ella
salen el nombre, el ámbito y la prioridad del snippet, y el `@version` que mira
`make release`.

**Al bundle solo lo miran sus propios tests** (`tests/unit/test-bundle.php`):
`tests/bootstrap.php` carga los módulos de `src/Prc/` y se salta `*.bundle.php`.
Por eso `make bundle` hace `php -l` del resultado — un `declare(strict_types=1)`
es legal por fichero y **fatal** al concatenar. Por eso también está prohibido.

**Permisos**: toda decisión de «puede o no puede» pasa por
`Access/ProcedureAccess`. Esconder algo en el listado con `pre_get_posts` **no
es protegerlo**: quedan abiertos el enlace directo a `post.php?post=N`, la
edición rápida, la REST API y las acciones en bloque. La capa que protege es
`map_meta_cap`. Se hacen las dos, en ese orden de importancia. Y son dos
acotados, los dos fail-closed: por **ámbito** (la meta `prc_area` de quien
gestiona) y por **centro** (`Access/CentreScope`, para quien solicita).

**Verlo funcionando:** `make bundle && make sync-snippets` y recarga
<http://localhost:8698/gestion-de-procedimientos/>.

---

## Convenciones

- **Idiomas:** ver [más abajo](#idiomas); en corto, identificadores en
  **inglés** y todo lo que lee una persona en **castellano**.
- **Prefijo:** `prc_` / `PRC_`; namespace `Prc`; snippets `PRC — …`.
- **Estilo:** WPCS (`wp-coding-standards/wpcs`, `<rule ref="WordPress">`),
  tabuladores, Yoda conditions, escape de salida. `make lint` / `make fix`.
  Cubre `src/`, `snippets/`, `scripts/`, `tests/` **y `build/`**; en `build/` se
  apagan solo las reglas que exigen `WP_Filesystem`, porque esos guiones corren
  desde la línea de órdenes y ahí no hay WordPress.
- **`declare(strict_types=1)` está prohibido** (ver arriba).
- **Aplicativo:** editar solo `src/Prc/`; no editar a mano
  `snippets/*.bundle.php` (regenerar con `make bundle`).
- **Librerías de terceros:** en producción desde **jsDelivr con SRI**
  (`cdn.jsdelivr.net/npm/<paquete>@<versión>/…`, con `integrity` y
  `crossorigin`); en desarrollo y en los tests desde **`node_modules`**, que el
  mu-plugin reescribe. Van en `package.json` con la versión **exacta**, y esa
  versión es la misma en los tres sitios: `package.json`, la URL y el `$ver` del
  encolado. **Nunca se inlinean en el bundle.** El CDN **no** es un problema en
  este proyecto: es el camino
  ([ADR-0006](docs/adr/ADR-0006-librerias-de-terceros-desde-cdn-con-sri.md)).
- **Workflows:** `make check` compila el JavaScript de `github-script`, que va
  dentro de una cadena YAML y que si no nadie mira hasta que el job corre. **No**
  valida las expresiones `${{ … }}`: un `if` que lea un contexto inexistente
  —`secrets`, por ejemplo— es YAML válido, pasa aquí, y GitHub rechaza el fichero
  entero al recibirlo: rojo a los cero segundos y sin un job que abrir. Eso solo
  lo dice GitHub.
- **Scripts de `scripts/`:** idempotentes, **sin `WP_CLI`** (corren también
  bajo Playground), raíz con `dirname( __DIR__ )`, y lanzan `RuntimeException`
  cuando algo falla, para que `make provision` se caiga en vez de seguir a
  medias.
- **Listas cerradas en código, no en taxonomía:** los estados, las audiencias
  (`schools`, `teachers`) y la titularidad (`public`, `private`) viven en
  `Meta/ProcedureMetaKeys.php`; los cargos y los estados de revisión
  (`submitted`, `amend`, `admitted`, `excluded`) en
  `Meta/ApplicationMetaKeys.php`, y los cinco tipos de pregunta —`text`,
  `single`, `multiple`, `yesno`, `file`— en `Domain/ProcedureQuestions.php`.
  Solo el ámbito y el curso son taxonomías
  ([ADR-0012](docs/adr/ADR-0012-una-taxonomia-por-dimension.md)).
- **Toda escritura va por POST** con nonce y comprobación de capacidad, patrón
  «POST → redirect → GET» con el aviso en la URL (`?aviso=`). Borrar es enviar
  a la papelera (ADR-0007).

### Idiomas

Qué va en cada idioma. Si algo no está en esta tabla, va en castellano: es lo
que lee una persona.

| Qué | Idioma |
|--|--|
| Identificadores que se llaman: clases, métodos, funciones, nombres de fichero | **inglés** |
| Variables y parámetros locales | **inglés** |
| Slugs, claves de meta y sus valores, roles, opciones y hooks | **inglés** |
| Nombres de test | **inglés** (son funciones) |
| Docblocks (`/** … */`) | **inglés** |
| Comentarios sueltos (`// …`) | **castellano** si explican una regla del dominio o una decisión; inglés si son puramente técnicos |
| Cadenas de la interfaz, etiquetas, mensajes de error y avisos | **castellano** |
| ADR, SDD, requisitos, planes, `CHANGELOG`, `README`, este fichero | **castellano** |
| Mensajes de commit, títulos y descripciones de PR | **castellano** |
| `Makefile`: `help` y comentarios | **castellano** |
| URL de las páginas | **castellano** |

**La raya está entre lo que lee una máquina y lo que lee una persona**: en
inglés los slugs de los tipos de contenido y de las taxonomías, las claves de
meta y sus valores (`prc_review_state`, `admitted`), los roles
(`prc_manager`), las opciones, los hooks y las clases que los reflejan. En
castellano las cadenas, las etiquetas, los rótulos de rol y las URL de las
páginas (`gestion-de-procedimientos`, `mi-centro`), que se comparten y se
teclean.

Los términos de `prc_area` y `prc_course` son **datos**, no identificadores:
sus nombres van en castellano y sus slugs, como los escriba quien los cree.

**Por qué los comentarios del dominio van en castellano:** quien los lee
—persona o agente— tiene que enlazarlos con el requisito y con la cadena que
sale en pantalla, y ambos están en castellano. Los docblocks no: describen la
API, sus etiquetas ya son inglesas y las revisa WPCS.

---

## Política ADR/SDD

Toda decisión no trivial de IA se registra en `docs/adr/` o `docs/sdd/` con
`ai_assistance` (tool, model). Plantillas e índices en esos directorios; el ID
es `max(existentes) + 1`, con ceros a la izquierda, y nunca se reutiliza.

Una ADR aceptada **no se reescribe**: se le añade una `## Adenda — AAAA-MM-DD`,
o se crea una ADR nueva con `supersedes` / `superseded_by` y se actualiza
`registro.md`.

La raya está en **publicado**. Mientras una ADR no haya salido del repositorio
—sin commit, sin enlace compartido— no es todavía la dirección de nadie y se
corrige en su propio texto; ahí es donde se consolida y se renumera. En cuanto
hay commit, el identificador y el texto se congelan y toda corrección es adenda
o ADR nueva.

Evidencia antes que preferencia: cada afirmación técnica lleva su fuente
verificable (ruta del repo con línea, documentación oficial, experimento
reproducible, PR o ADR previa). Los contras se escriben con la misma dureza
que los pros.

---

## Este repositorio se publica en abierto

Va a ser **software libre**, así que **nada de lo que se versiona puede decir
de quién es el despliegue, dónde está, qué infraestructura usa ni cómo era por
dentro el sistema que se sustituye**
([ADR-0009](docs/adr/ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md)).

Lo que **no** se escribe en `src/`, `docs/`, `scripts/`, `tests/`, `README.md`
ni en este fichero:

| Qué | Dónde va |
|---|---|
| URL de analítica, de avisos de cookies o de servicios de una organización, y sus identificadores | Configuración: el filtro `prc_chrome`, vacío por defecto |
| El nombre, el escudo, el rótulo o los enlaces legales de una organización | Lo mismo |
| El nombre de la red, del sitio o del subsitio de destino | El `.env`, que no se sube. `.env.dist` los trae **vacíos** |
| Cómo era la instalación anterior por dentro: sus formularios, vistas, campos, fragmentos de código y sus números | `.local/`, que está en el `.gitignore` |
| El nombre de las metas de usuario del sitio de destino (la del código de centro, la del ámbito) | El filtro `prc_centre_code_meta_key` y la configuración de cada instalación; aquí solo `prc_area` y `prc_centre_code` |
| El catálogo de centros real, o cualquier centro real | El filtro `prc_centres`; en local, centros **inventados** |
| Cuánta gente hay, en qué ámbitos y con qué rol | `.local/`, o se escribe la decisión con la cifra redondeada y sin nombres |
| El nombre de otro repositorio del equipo, sus rutas, sus opciones o sus prefijos | `.local/`. **Son privados**: citarlos publica código de otro |
| Rutas absolutas de un portátil | En ningún sitio |

**La razón de una decisión se queda; el dato que la sostenía puede irse.** Se
puede escribir por qué se eligió algo sin publicar el inventario de una
instalación ajena. Vale igual para el argumento de autoridad: «se hace así en
tal repositorio» no es una razón, es una cita —**y además privada**—; lo que
convence es el motivo, escrito entero aquí. Cuando una ADR se apoye en una
medición, se dice que está medida y se deja el material en `.local/`.

**Se comprueba**, no se confía: `make check-public` recorre lo que git
versionaría y falla con el motivo escrito. Las reglas genéricas están en
`scripts/check-public.mjs`; las que nombran lo que no puede salir, en
`.check-public-rules`, que no se versiona, y en CI en el secret
`PRC_PUBLIC_RULES`. Una regla nueva va a las dos:
`gh secret set PRC_PUBLIC_RULES < .check-public-rules`.

---

## Reglas duras

- **No convertir el repo en plugin de producción.** Ni cabecera, ni
  `readme.txt`, ni hooks de activación, ni rutas de plugin.
- **Código del aplicativo en `src/Prc/`** → bundle → Code Snippets. No dejar
  cambios solo en el admin de Snippets.
- **Nada de `declare(strict_types=1)`.**
- **No tocar nada del WordPress de producción.** Este repositorio es de solo
  escritura hacia dentro: lo que se escribe, se escribe aquí.
- **No versionar ni citar `.local/`**: son datos personales reales y el mapa
  del sistema anterior.
- **Nada de ninguna organización concreta en lo que se versiona** (ADR-0009):
  ni marca, ni infraestructura, ni destino de despliegue, ni catálogo de
  centros real. `make check-public`.
- `prc_application` **sí** se registra: una solicitud es un contenido que
  cuelga de su procedimiento, una por centro (ADR-0018). El centro **no** es
  un tipo de contenido ni una taxonomía: es un código en una meta de la
  persona y una entrada del catálogo que contesta un filtro (ADR-0016,
  ADR-0017).
- El estado **no se guarda**: se deriva (ADR-0013). Lo único que se escribe es
  `prc_archived`, y solo desarchiva administración (ADR-0023).
- **Un documento aportado en una solicitud NO es un adjunto de WordPress**
  (ADR-0028): no se llama a `wp_insert_attachment()`, `media_handle_upload()`
  ni `media_handle_sideload()`, no se guardan IDs de adjunto y **no se
  instalan filtros** para esconder la biblioteca de medios, la REST de medios
  ni las páginas de adjunto. Vive en `PublicFront/ApplicationFiles`, con
  nombre físico opaco y descarga autorizada por `ProcedureAccess`. El
  contenido público del sitio sigue por el camino normal de WordPress.
- El mu-plugin de `scripts/mu-plugins/` es solo desarrollo.
- Diffs pequeños y enfocados. No inventar ficheros que nadie ha pedido.

---

## Definición de hecho

1. `make lint` sin errores.
2. `make test` sin fallos, y `make check-plugin` sin errores de Plugin Check.
3. `make bundle` genera un bundle que pasa `php -l`, y `make snippet-check`
   pasa con el entorno arrancado.
4. `make up` / provisión OK (<http://localhost:8698>, `admin` / `password`).
5. Docs actualizadas si aplica; ADR/SDD con `ai_assistance` si hubo decisión.

---

## Referencia de herramientas

- `make help` y el `Makefile` son la referencia de targets.
- wp-env: **Code Snippets + Members + WPFront User Role Editor + SQL Buddy**
  (ningún gestor de formularios). Puertos `8698` / `8699`.
- El destino en producción **puede ser un subsitio de un multisitio**, a
  diferencia del entorno local, que es un sitio único. Cualquier cosa que
  construya el nombre de una tabla o invoque `wp eval-file` sin `--url=` merece
  una mirada antes de darla por buena.

---

## Skills (`.agents/skills/`)

Viven en:

- `.agents/skills/` — GitHub Copilot, Codex, Cursor y el resto de agentes que
  comparten esa ruta
- `.claude/skills/` — Claude Code

Las copias canónicas viven en `.agents/skills/`, y `.claude/skills/` lleva una
**copia** de cada una. Copia y no enlace porque **en Windows los enlaces
simbólicos no funcionan** sin habilitarlos a mano, y `gh skill` tampoco enlaza.
Las dos carpetas se igualan con `make skills-sync`, y `make check` falla si
difieren.

**Las que hay hoy** —todas de terceros y verbatim salvo una, con su índice y
su origen en [`.agents/skills/README.md`](.agents/skills/README.md)—:

| Para | Skills |
|---|---|
| Seguridad | `security-audit`, `wp-plugin-security`, `github-actions-hardening` |
| WordPress | `wp-plugin-development`, `wp-performance`, `wp-wpcli-and-ops`, `wp-project-triage`, `wp-playground`, `blueprint` |
| Pruebas | `playwright-cli` |
| Propia | `changelog` — el bloque de versión y `make release` |

Skills propias: créalas en `.agents/skills/<nombre>/` y ejecuta
`make skills-sync`. Las de terceros, instálalas solo para Copilot y sincroniza
igual:

```bash
gh skill add WordPress/agent-skills wp-performance --agent github-copilot
gh skill update --all
make skills-sync
```

`gh skill` mete la procedencia en el frontmatter del `SKILL.md`. No instales
`--agent claude-code` ni `--agent grok` en este repo: escribirían en
`.claude/skills/` por su cuenta y la canónica dejaría de ser la de `.agents/`.
Las de terceros van **verbatim**: no las reformatees, divergir de upstream
complica `gh skill update`.

### Compatibilidad de skills

Las skills genéricas de WordPress orientan la implementación, pero este
repositorio **no es un plugin distribuible**. La arquitectura del repositorio
siempre prevalece sobre las recomendaciones de una skill:

- No crear un fichero bootstrap de plugin ni cabeceras de plugin ni
  `readme.txt`.
- El código del aplicativo pertenece a `src/Prc/`.
- Los artefactos de producción son Code Snippets generados con `make bundle`.
- No introducir hooks de activación/desactivación de plugin.
- No asumir rutas o URL relativas a un plugin (`plugin_dir_path()`,
  `plugin_dir_url()`, `plugins_url()`, `register_activation_hook()`).

`wp-plugin-development` se usa para **patrones de desarrollo WordPress**
(hooks, CPT, admin, shortcodes, capabilities, enqueue), no para packaging ni
estructura de plugin.
