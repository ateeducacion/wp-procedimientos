---
id: ADR-0001
title: "El repositorio es un entorno de desarrollo, no un plugin"
status: Propuesta
date: 2026-09-15
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0002, ADR-0009, ADR-0010, ADR-0011]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-fable-5-1"
---

# ADR-0001: El repositorio es un entorno de desarrollo, no un plugin

## Estado

Propuesta (2026-09-15). Escrita antes del primer commit, sobre la foto del
sistema que se sustituye tomada ese mismo día.

## Contexto

El aplicativo de procedimientos —convocatorias a las que un centro educativo
se inscribe a través de su equipo directivo— vive en un WordPress gestionado
entero desde el panel de administración. Todo lo que hoy hace de aplicativo
está dentro de ese panel ([SDD-0001](../sdd/SDD-0001-arquitectura-del-aplicativo-de-procedimientos.md),
«Contexto»; la foto con identificadores vive en `.local/`, que no se versiona,
[ADR-0009](ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md)):

- un formulario de alta de convocatoria de **123 campos** y otro de solicitud
  de **94**, en el gestor de formularios;
- **279 vistas** de ese gestor —plantillas HTML con condicionales dentro de
  campos de texto— que pintan cada pantalla;
- una página del constructor de páginas por convocatoria, creada por una
  acción del formulario;
- **más de un centenar de fragmentos de código** en Code Snippets que
  parchean lo que el resto no alcanza: filtros del gestor de formularios,
  JavaScript inyectado en el pie y SQL directo contra sus tablas.

No existe despliegue de ficheros a ese WordPress. El canal real de puesta en
producción es pegar código en el formulario de Code Snippets del escritorio.
La herramienta que ya usa el equipo automatiza ese pegado por HTTP con una
sesión autenticada del panel —y hasta pasa `php -l` y unas pruebas antes de
empujar—, pero no cambia la naturaleza del canal: sigue siendo el admin, y
sigue sin subir ficheros al servidor.

Ese código necesita versionado, revisión, tests y un entorno local
reproducible. Hoy no tiene nada de eso: los fragmentos se copian a un
repositorio privado *después* de pegarlos, no antes.

## Problema

¿Cómo estructurar el repositorio para versionar, revisar y probar el código
de un aplicativo cuyo destino es un WordPress ajeno, sin acceso al sistema de
ficheros del servidor y sin más canal de despliegue que el panel de
administración?

## Factores de decisión

- Producción admite Code Snippets y el gestor de formularios desde el admin, y
  nada más. Esa restricción no está en nuestra mano cambiarla.
- Trazabilidad: el código debe poder revisarse en Git, con diffs, PR y CI.
- Reproducibilidad: cualquier persona debe levantar en local un entorno
  equivalente con un comando.
- No duplicar mecanismos que los plugins de producción ya proporcionan.
- El aplicativo de eventos ya resolvió esto mismo con el mismo destino
  ([ADR-0010](ADR-0010-se-parte-del-esqueleto-del-aplicativo-de-eventos.md)):
  cambiar de esquema sería tener dos formas de desplegar en la misma casa.

## Alternativas consideradas

### Opción 1: empaquetar el aplicativo como plugin propio

- Pros: estructura estándar, activación limpia, autoload de Composer,
  distribuible.
- Contras: **producción no instala plugins propios**. Duplicaría lo que Code
  Snippets ya hace y obligaría a montar un flujo de publicación que hoy no
  existe. Descartada.

### Opción 2: mu-plugin permanente

- Pros: siempre activo, sin pasos de activación, sin interfaz que tocar.
- Contras: requiere acceso al sistema de ficheros del servidor, que no existe.
  Y si el destino fuera algún día un subsitio de una red, `mu-plugins` es de
  la red entera: `prc_procedure` se registraría en sitios que no tienen nada
  que ver con esto. Descartada.

### Opción 3: repositorio-entorno con `src/Prc/` y `snippets/` como fuente de verdad

El repositorio no contiene un plugin: contiene el código del aplicativo en
`src/Prc/`, los snippets sueltos en `snippets/*.php`, un entorno local
(wp-env / WordPress Playground) con los mismos plugins que producción y
scripts idempotentes que sincronizan repositorio ↔ WordPress. El artefacto de
despliegue es **un único snippet** generado por `build/pack-snippet.php`.

- Pros: encaja con el modelo real de producción; todo es versionable,
  revisable y testeable; el entorno local se levanta con `make up`; es el
  esquema que ya funciona en el aplicativo de eventos.
- Contras: exige disciplina de sincronización en los dos sentidos, y el
  «despliegue» sigue siendo un copiar y pegar con supervisión humana.

## Decisión

Se adopta la misma decisión que el aplicativo de eventos, y por las mismas
razones: la opción 3. Este repositorio **es un entorno de desarrollo, no un
plugin**:

- `src/Prc/` es la fuente de verdad del aplicativo y `snippets/*.php` la de
  los snippets sueltos: `roles-and-profiles.php` (`Snippet Name: PRC — Roles y
  perfiles`, `Priority: 5`), `bootstrap5.php` y `sweetalert.php`
  (`Priority: 20`, [ADR-0006](ADR-0006-librerias-de-terceros-desde-cdn-con-sri.md)).
- `build/pack-snippet.php` genera `snippets/prc-procedimientos-app.bundle.php`
  con la cabecera `Snippet Name: PRC — Aplicativo de procedimientos (CPT)` y
  `Priority: 15`, en el orden de `src/Prc/load-order.php`, con la guarda
  `PRC_BUNDLE_LOADED` justo después del `namespace` y un `php -l` sobre el
  resultado. Ese fichero es lo que se pega en Code Snippets; no se edita a mano.
- wp-env monta el repositorio en `wp-content/prc-dev` (las `mappings` de
  `.wp-env.json`), en los puertos 8698 y 8699, con Code Snippets, Members,
  WPFront User Role Editor y SQL Buddy, **sin el gestor de formularios ni el
  constructor de páginas** (los `plugins` de `.wp-env.json`).
- La sincronización repositorio → Code Snippets se hace con `wp eval-file` y
  la API PHP del plugin ([ADR-0002](ADR-0002-sincronizacion-snippets-eval-file.md)).
- No hay cabecera de plugin en ningún fichero del repositorio, ni
  `readme.txt`, ni `register_activation_hook()`, ni `plugins_url()`.

Lo que cambia respecto a eventos es el destino: aquel es un subsitio de una
red; este es hoy un sitio único. Las reglas nacidas allí para el multisitio
—`--url=` en cada `wp eval-file`, nada que construya un nombre de tabla— se
heredan igual porque no cuestan nada y porque el destino puede cambiar de
naturaleza sin avisar a este repositorio.

## Consecuencias

### Positivas

- El código del aplicativo es revisable en PR, pasa PHPCS y PHPMD y tiene
  tests en CI. Hoy no lo tiene nada de lo que hay en producción: ni el
  centenar largo de fragmentos, ni los 123 campos del formulario, ni las 279
  vistas tienen control de versiones antes de estar desplegados.
- Un solo artefacto de despliegue, con su versión en la cabecera, en lugar de
  un fragmento por convocatoria y curso —que es lo que hay hoy para cada
  necesidad nueva—.
- Entorno local reproducible con los mismos plugins de gestión de código y
  roles que producción, y demo pública en WordPress Playground del mismo
  material (`blueprint.json`).

### Negativas

- Doble paso permanente: lo que se cambia en el repositorio hay que
  sincronizarlo a WordPress, y lo que alguien cambie en el admin hay que
  volcarlo al repositorio. El sistema actual ya sufre esa divergencia en el
  sentido contrario (se copia después de pegar) y no la ha resuelto nadie.
- El despliegue a producción es pegar el bundle en un `textarea` del
  escritorio. No hay firma ni checksum: la comprobación de que lo pegado es el
  bundle de un commit concreto es humana.
- Estructura de plugin sin las ventajas de un plugin: no hay autoload en
  producción, así que el orden de carga se mantiene a mano en
  `src/Prc/load-order.php`, y `declare(strict_types=1)` queda prohibido porque
  es legal por fichero y fatal al concatenar.
- El entorno local es un sitio único y el destino también, **hoy**. Nada en
  este repositorio prueba el caso multisitio; se heredan sus reglas, no su
  verificación.

### Neutras

- Si algún día el destino admitiera desplegar ficheros, el mismo `src/Prc/`
  se empaquetaría como plugin sin tocar el dominio: cambiaría el empaquetador,
  no el código.
- El gestor de formularios no se instala en el entorno local: el aplicativo
  nuevo no depende de él. Lo que se queda allí —las solicitudes y documentos
  ya presentados— lo decide la [ADR-0024](ADR-0024-las-solicitudes-de-hoy-no-se-migran.md).
