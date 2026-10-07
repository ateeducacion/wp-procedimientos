# Procedimientos — entorno de desarrollo

[![CI](https://github.com/ateeducacion/wp-procedimientos/actions/workflows/ci.yml/badge.svg)](https://github.com/ateeducacion/wp-procedimientos/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/ateeducacion/wp-procedimientos/graph/badge.svg)](https://codecov.io/gh/ateeducacion/wp-procedimientos)
[![Probar en WordPress Playground](https://img.shields.io/badge/Probar%20en%20WordPress%20Playground-3858E9?logo=wordpress&logoColor=white)](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/ateeducacion/wp-procedimientos/main/blueprint.json)

Entorno de desarrollo del aplicativo de **procedimientos** (convocatorias
—proyectos, programas, redes, concursos— a las que un centro educativo se
inscribe a través de su equipo directivo): WordPress + **Code Snippets** +
**WPFront User Role Editor**, con el dominio en dos tipos de
contenido —`prc_procedure`, `prc_application`— y el código modular de
`src/Prc/` empaquetado en un único snippet con `make bundle`.

Dónde se despliega **no está en el repositorio**: va en el `.env`, que no se
sube ([ADR-0009](docs/adr/ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md)).

**[Pruébalo sin instalar nada](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/ateeducacion/wp-procedimientos/main/blueprint.json)**: el botón de arriba abre
[`blueprint.json`](blueprint.json) en WordPress Playground —WordPress entero
compilado a WebAssembly, corriendo en la pestaña del navegador—. Levanta el
sitio con los tres plugins, sincroniza los snippets, crea los roles, el
vocabulario, las páginas y los datos de demostración, y **aterriza en el
aplicativo**, no en el escritorio: en «Gestión de procedimientos», que es lo
que se viene a ver.

Lleva también el mu-plugin de desarrollo, así que desde la barra superior se
puede **cambiar de cuenta** —`gestion`, `gestion2`, `direccion`, `direccion2`—
y ver el aplicativo desde cada lado: quien convoca y quien solicita, cada uno
acotado a su ámbito o a su centro, que es la mitad de lo que hay que probar
aquí. Volver a la propia cuenta se hace desde la misma barra.

Tarda un par de minutos la primera vez y **no toca nada de tu máquina**: al
cerrar la pestaña no queda rastro. En cada PR sale además su propio enlace, con
el código de esa rama.

> **Este repositorio NO es un plugin de WordPress.** No lleva cabecera de
> plugin, ni `readme.txt`, ni `register_activation_hook()`, ni rutas de plugin.
> Versiona, prueba y sincroniza el material que en producción se activa como
> Code Snippet(s). El artefacto de producción es un fichero PHP que acaba en
> Code Snippets —lo empuja `npm run snippets`—, porque es lo único que ese
> WordPress permite desplegar.

## Qué viene a sustituir

Conviene decirlo sin adornos, porque explica casi todas las decisiones de
diseño. Hoy una convocatoria es una entrada de un **formulario de más de un
centenar de campos** cuya acción crea una página del constructor de páginas del
sitio; la solicitud de un centro es una entrada de otro formulario de casi
cien campos, con la clave «convocatoria · centro» escrita en un campo de
texto; las opciones que pide cada convocatoria van en un tercer formulario, y
el catálogo de centros es un cuarto. Cada pantalla la pinta una de **cerca de
trescientas vistas** del gestor de formularios —plantillas HTML con
condicionales dentro de campos de texto, sin control de versiones, sin tests y
sin forma de revisar un cambio—, y **más de un centenar de fragmentos de
código** parchean lo que el resto no alcanza.

El estado de una convocatoria es un desplegable que alguien tiene que
acordarse de cambiar, así que hay iconos para «abierta con el plazo
finalizado» y «cerrada con el plazo abierto». El ámbito que convoca es un
árbol de varias decenas de términos filtrado por una meta de usuario con dos
fragmentos distintos, uno en el servidor y otro en el navegador. Una
convocatoria con una necesidad nueva acaba clonando un formulario y tres
vistas. Y el volumen no es pequeño: unas trescientas convocatorias en cuatro
cursos, cerca de dieciocho mil solicitudes de centros, unos mil setecientos
centros en el catálogo y más de mil trescientas cuentas de dirección.

Este repositorio baja ese dominio a WordPress nativo, con el mismo esqueleto
que el aplicativo de eventos
([ADR-0010](docs/adr/ADR-0010-se-parte-del-esqueleto-del-aplicativo-de-eventos.md)):
el procedimiento es un tipo de contenido y la solicitud otro que cuelga de él,
**una por centro**; el ámbito y el curso son dos taxonomías; el estado **se
deriva de las fechas** y no puede contradecirse; los permisos los decide un
único guardián acotado por ámbito (quien convoca) y por centro (quien
solicita); las opciones son un núcleo fijo más preguntas configurables por
procedimiento; y la resolución y los listados son **enlaces**, no copias.

Lo que **no** se lleva, y con una ADR que lo justifica: las solicitudes y los
documentos ya presentados **no se migran en la fase 1**
([ADR-0024](docs/adr/ADR-0024-las-solicitudes-de-hoy-no-se-migran.md)). Se
quedan donde están.

## Fuente de verdad

| Ruta | Contenido |
|------|-----------|
| `src/Prc/` | Aplicativo modular (editar aquí) |
| `src/Prc/load-order.php` | Orden de carga: lista única, la usan bootstrap y el bundler |
| `snippets/` | Snippets sueltos + `prc-procedimientos-app.bundle.php` generado (`make bundle`) |
| `docs/` | REQ / SDD / ADR |

## Modelo de datos (fase 1)

| Tipo de contenido | Qué es | Hoy |
|---|---|---|
| `prc_procedure` | La convocatoria: descripción, plazos de solicitud y de subsanación, enlaces a la resolución y a los listados, preguntas propias | Una entrada de un formulario más una página del constructor |
| `prc_application` | La solicitud de un centro: cuelga del procedimiento (`post_parent`), una por centro, con su estado de revisión | Una entrada de otro formulario con la clave compuesta a mano |

| Taxonomía | Eje | Notas |
|---|---|---|
| `prc_area` | Ámbito convocante. **Jerárquica** (servicio → área). **Es el eje de permisos** | Términos de ejemplo en local; cada instalación crea los suyos |
| `prc_course` | Curso escolar | `2026-2027` |

El estado (`draft`, `upcoming`, `open`, `closed`, `amendment`, `resolved`,
`archived`) **no es un término**: lo calcula `Domain/ProcedureState` a partir
de las fechas y de los enlaces
([ADR-0013](docs/adr/ADR-0013-el-estado-del-procedimiento-se-deriva-de-las-fechas.md)).
El catálogo de centros **no se versiona**: lo contesta el filtro `prc_centres`
([ADR-0017](docs/adr/ADR-0017-el-catalogo-de-centros-no-se-versiona.md)); en
local lo sirve el mu-plugin con una docena de centros inventados.

## Roles

Son **tres**, y solo dos los crea el aplicativo:

| Rol | Etiqueta | Quién |
|---|---|---|
| `editor` | Editor | Actor recomendado: gestiona procedimientos de su ámbito y descendientes, incluidos los compartidos con otras ramas |
| `prc_manager` | Gestión de procedimientos | Rol anterior conservado por compatibilidad, sujeto al mismo acotado |
| `prc_school_head` | Dirección de centro | Equipo directivo: presenta y edita la solicitud **de su centro** |
| `administrator` | Administrador | Todo, en todos los ámbitos. Y lo único reservado: los ajustes del aplicativo, el diagnóstico y **desarchivar** |

El acotado es **fail-closed** por los dos lados
([ADR-0014](docs/adr/ADR-0014-el-ambito-es-un-alcance-no-un-rol.md),
[ADR-0016](docs/adr/ADR-0016-el-centro-de-la-persona-es-una-meta-configurable.md)):
quien gestiona lleva un único ámbito en la meta de usuario `prc_area` (ese
término incluye a sus descendientes) y sin ámbito no edita
nada; quien solicita lleva el código de su centro en una meta cuya clave
devuelve el filtro `prc_centre_code_meta_key`, y sin código no solicita nada.
Los dos roles los crea el snippet suelto `snippets/roles-and-profiles.php`,
que también añade a la ficha de usuario los campos «Ámbito» y «Código de
centro», editables solo por administración; el aplicativo solo comprueba
capacidades
([ADR-0015](docs/adr/ADR-0015-el-aplicativo-comprueba-capacidades-no-roles.md)).

En REST, un Editor crea primero el procedimiento en borrador y después lo
publica; así los hooks de publicación ya encuentran su ámbito. Al crear sin
`prc_area` se usa el único término directo del perfil. Al actualizar sin ese
parámetro se conservan los organizadores existentes. Administración puede
crear contenido huérfano para repararlo.

## Pantallas

Páginas de WordPress con un shortcode cada una, pintadas enteras por código
([ADR-0022](docs/adr/ADR-0022-las-pantallas-son-paginas-pintadas-enteras.md)):

| Página | Quién | Qué |
|---|---|---|
| `/procedimientos/` | Público | Listado por curso: abiertos, en subsanación, próximos, cerrados y resueltos; filtro por ámbito |
| `/procedimiento/<slug>/` | Público | La ficha: descripción, fechas, estado, contacto, enlaces; botón «Solicitar», «Mi solicitud» o «Gestionar» según quién mira |
| `/gestion-de-procedimientos/` | `prc_manager` | Mis procedimientos por curso, con estado y número de solicitudes |
| `/editar-procedimiento/` | `prc_manager` | El taller: paneles **Datos**, **Preguntas**, **Enlaces**, **Solicitudes** y **Publicación** |
| `/solicitud/` | `prc_school_head` | El formulario de solicitud del centro, o la solicitud presentada con su estado y la nota de revisión |
| `/mi-centro/` | `prc_school_head` | Las solicitudes del centro |

## Requisitos

- **Docker** (wp-env)
- **Node.js 22** (ver `.nvmrc`)
- **PHP 8.1+** y **Composer** (lint / tests en el host)

El entorno de desarrollo y pruebas usa **PHP 8.3**, fijado en `.wp-env.json` y
en los workflows de CI; el mínimo de compatibilidad de Composer sigue en 8.1.

## Inicio rápido

> **¿Es tu primera vez y no vienes de desarrollo?** Empieza por
> [Empezar a trabajar en este proyecto](docs/desarrollo-para-empezar.md):
> qué instalar en macOS y en Windows, cómo levantar el entorno y cómo se
> propone un cambio (rama → `make check` → PR → revisión del equipo).

```bash
git clone https://github.com/ateeducacion/wp-procedimientos.git
cd wp-procedimientos
make install
make up
```

- Sitio: <http://localhost:8698> (`admin` / `password`)
- Aplicativo: <http://localhost:8698/gestion-de-procedimientos/>
- Procedimientos: <http://localhost:8698/wp-admin/edit.php?post_type=prc_procedure>
- Solicitudes: <http://localhost:8698/wp-admin/edit.php?post_type=prc_application>

Los puertos son **8698** (desarrollo) y **8699** (tests), fuera de los que usan
por defecto otros entornos de la casa a propósito: así pueden estar varios
arriba a la vez.

### Usuarios de prueba

Los crea `scripts/seed-demo.php` (que es la fuente de verdad de esta tabla) en
`make provision`, `make up` y en los dos blueprints de Playground. La
contraseña es `password` en todos.

| Usuario | Rol | Ámbito o centro en el perfil |
|---------|-----|------------------------------|
| `admin` | administrator | — (ve todo) |
| `gestion` | `prc_manager` | Un ámbito del árbol de ejemplo |
| `gestion2` | `prc_manager` | Otro ámbito (sirve para ver el acotado) |
| `editor-ambito1` | `editor` | Ámbito 1 y descendientes |
| `editor-ambito2` | `editor` | Ámbito 2 y descendientes |
| `direccion` | `prc_school_head` | Un centro del catálogo inventado |
| `direccion2` | `prc_school_head` | Otro centro |

Flujo sugerido: entra como `editor-ambito1` y `editor-ambito2` y comprueba que
ambos pueden editar el procedimiento compartido, pero cada uno solo cambia
los ámbitos organizadores de su rama. Un procedimiento exclusivo de la otra
rama no aparece en el listado ni se abre por enlace directo. Después entra
como `direccion`, presenta una solicitud y vuelve a «Solicitar»: te reabre la
misma. `gestion` y `gestion2` sirven para comprobar la compatibilidad del rol
anterior.

Para cambiar de usuario sin cerrar sesión, **WPFront User Role Editor** (menú
«Switch To» en Usuarios), igual que en producción.

## Comandos

| Comando | Qué hace |
|---------|----------|
| `make help` | Lista los targets |
| `make install` | Composer + npm |
| `make up` | Arranca wp-env y lo provisiona |
| `make down` / `make destroy` | Para / destruye el entorno |
| `make clean` | Resetea desarrollo y tests y vuelve a provisionar |
| `make logs` / `make shell` | Logs del entorno / shell en el contenedor CLI |
| `make provision` | Bundle + snippets + roles + vocabulario + páginas + datos de demostración |
| `make bundle` | Regenera `snippets/prc-procedimientos-app.bundle.php` desde `src/Prc/` |
| `make sync-snippets` | Sincroniza `snippets/*.php` → Code Snippets |
| `make snippet-check` | Comprueba que los snippets sobreviven al guardado (doble eval) |
| `make test` | PHPUnit (admite `FILE=…` y `FILTER=…`) |
| `make skills-sync` | Copia `.agents/skills/` sobre `.claude/skills/` |
| `make capturas` | Recorre las pantallas y deja `capturas/informe.html` (admite `ONLY=desktop` / `ONLY=mobile`) |
| `make check-plugin` | Pasa WordPress Plugin Check sobre el código de los snippets |
| `make test-browser` | Los tres escalones de la confirmación en un navegador real |
| `make coverage` | Cobertura de `src/Prc` (reinicia wp-env con Xdebug) |
| `make lint` / `make fix` | PHPCS / PHPCBF |
| `make phpmd` | PHP Mess Detector |
| `make check-public` | Comprueba que no se filtra nada que no pueda ir a un repositorio público |
| `make check-provision` | Comprueba que la provisión propaga los fallos |
| `make check` | `lint` + `phpmd` + `check-public` + `check-provision` + `check-skills` + `test` |
| `make release` | Etiqueta la versión del CHANGELOG y publica la release |
| `make playground` | WordPress Playground local, sin Docker |

### Flujo de desarrollo del aplicativo

```bash
# 1. Editar solo src/Prc/
# 2. Empaquetar y recargar en WordPress
make bundle && make sync-snippets
```

No edites a mano `snippets/*.bundle.php`: se regenera.

## Mapa del repositorio

| Ruta | Contenido |
|------|-----------|
| `src/Prc/` | Aplicativo: CPT, taxonomías, meta, acceso, pantallas y escritorio |
| `build/pack-snippet.php` | Empaquetador `src/Prc/` → snippet único |
| `snippets/` | Snippets sueltos (roles) y el bundle generado |
| `scripts/` | Provisión idempotente (`wp eval-file` y Playground) |
| `scripts/mu-plugins/` | mu-plugin **solo de desarrollo** |
| `tests/` | PHPUnit sobre un WordPress vivo |
| `docs/adr/`, `docs/sdd/` | Decisiones y diseño |
| `.agents/skills/` | Skills de agentes (`.claude/skills/` las copia) |
| `CHANGELOG.md` | Versión y cambios; `make bundle` lee de ahí el `@version` |
| `blueprint.json` / `blueprint-local.json` | Playground remoto / local |
| `.wp-env.json` / `Makefile` | Docker / comandos |
| `.env.dist` | Plantilla de configuración de despliegue (copiar a `.env`) |
| `.local/` | Material de investigación del sistema anterior. **No versionado**: lleva datos personales |

## Plugins del entorno

| Plugin | Uso |
|--------|-----|
| Code Snippets | Ejecuta el bundle y los snippets auxiliares |
| WPFront User Role Editor | Ver `prc_manager`, `prc_school_head` y las capacidades `prc_*`, y cambiar de usuario para probar |
| SQL Buddy | Inspección de datos en local |

**El gestor de formularios no se instala en el entorno.** En producción
convive con el aplicativo mientras las solicitudes antiguas sigan ahí, pero
nada de `src/Prc/` depende de él y los tests no lo cargan.

## Cobertura

El suelo es el **90 %**, y bloquea en los dos ejes: el del parche —lo que se
toca en un PR va con sus tests— y el del proyecto —no se compensa tocando
poco—. Está en [`codecov.yml`](codecov.yml) y la decisión, con su porqué, en
la [ADR-0004](docs/adr/ADR-0004-ci-y-politica-de-pruebas.md).

En local se mide con `make coverage`, que reinicia wp-env con Xdebug.

[![Mapa de cobertura](https://codecov.io/gh/ateeducacion/wp-procedimientos/graphs/tree.svg)](https://codecov.io/gh/ateeducacion/wp-procedimientos)

Cada rectángulo es un fichero de `src/Prc` y su tamaño son sus líneas; el color
va de rojo a verde según lo cubierto. Sirve para lo que un porcentaje no dice:
**dónde** está lo que no se prueba. `src/Prc/App.php` no sale porque está
excluido de la medición —su cuerpo corre en el arranque, antes de que PHPUnit
empiece a medir, y lo que hace se comprueba en `test-load-order.php`—.

## Documentación

- [`docs/desarrollo-para-empezar.md`](docs/desarrollo-para-empezar.md) — guía
  paso a paso para quien no viene de desarrollo
- [`docs/sdd/SDD-0001-arquitectura-del-aplicativo-de-procedimientos.md`](docs/sdd/SDD-0001-arquitectura-del-aplicativo-de-procedimientos.md) — el diseño entero
- `docs/adr/registro.md` — índice de decisiones
- `docs/sdd/registro.md` — índice de diseño
- [`AGENTS.md`](AGENTS.md) — instrucciones para agentes de código y convenciones
