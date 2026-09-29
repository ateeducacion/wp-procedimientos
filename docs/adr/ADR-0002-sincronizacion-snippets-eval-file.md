---
id: ADR-0002
title: "Sincronización de snippets con wp eval-file y la API de Code Snippets"
status: Propuesta
date: 2026-09-15
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0001, ADR-0004, ADR-0010]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-fable-5-1"
---

# ADR-0002: Sincronización de snippets con `wp eval-file` y la API de Code Snippets

## Estado

Propuesta (2026-09-15). La librería ya está en el repositorio, copiada del
aplicativo de eventos con los identificadores renombrados
(`scripts/lib/snippet-sync.php`, `scripts/sync-snippets.php`,
`scripts/snippet-check.php`).

## Contexto

Según [ADR-0001](ADR-0001-repo-entorno-desarrollo-no-plugin.md), el código
vive en `src/Prc/` y en `snippets/*.php`, pero Code Snippets lo almacena y lo
ejecuta desde su propia tabla en la base de datos. Hace falta un mecanismo que
lleve los ficheros del repositorio a esa tabla de forma reproducible, tanto en
wp-env como en WordPress Playground.

El destino lleva Code Snippets en su **versión gratuita**, que **no ofrece
comandos WP-CLI** para crear o actualizar snippets, pero sí una API PHP
pública en el espacio de nombres `\Code_Snippets`: `get_snippets()`,
`save_snippet()`, `activate_snippet()` y una clase modelo `Snippet`.

Dos particularidades que condicionan la solución:

1. **Ya existe un cliente para el despliegue**: `@erseco/code-snippets-client`,
   un paquete de npm que habla por HTTP con el escritorio de WordPress —con
   contraseña de aplicación o con el inicio de sesión único del sitio—, lista,
   descarga y empuja snippets. Es lo que usa hoy el equipo para el centenar
   largo de fragmentos del sitio actual, y entra en `package.json` como
   dependencia de desarrollo.
2. **Code Snippets vuelve a evaluar un snippet activo al guardarlo.** Un bundle
   que declara clases muere en el segundo `eval()` («Cannot redeclare class») o
   se desactiva en silencio. De ahí la guarda `PRC_BUNDLE_LOADED` que inyecta
   `build/pack-snippet.php` y la comprobación de `scripts/snippet-check.php`.

## Problema

¿Cómo sincronizar `snippets/*.php` con la tabla de Code Snippets de forma
idempotente y automatizable, sin depender de funcionalidad de pago, sin SQL
directo como camino principal, y funcionando igual bajo `wp eval-file` y bajo
Playground?

## Factores de decisión

- No hay comandos WP-CLI de Code Snippets en la versión gratuita.
- Idempotencia: reejecutar la sincronización no puede duplicar snippets.
- El mismo código debe correr bajo `wp eval-file` (Docker) y bajo
  `runPHP`/`require` (Playground), así que no puede depender de `WP_CLI`.
- Ciclo de iteración rápido: editar `src/Prc/`, empaquetar, ver el resultado.
- Evitar SQL directo contra tablas de un plugin de terceros.

## Alternativas consideradas

### Opción 1: comandos WP-CLI de Code Snippets

- Contras: no existen en la versión gratuita, que es la de producción.
  Descartada.

### Opción 2: SQL directo contra la tabla del plugin

- Pros: sin dependencia de la API del plugin.
- Contras: acoplado al esquema interno, se salta la caché y la validación del
  plugin. Descartada como camino principal.

### Opción 3: importar y exportar a mano desde el escritorio

- Contras: manual, no automatizable en el aprovisionamiento ni en CI, y no
  idempotente. Descartada para desarrollo. Sigue siendo, por
  [ADR-0001](ADR-0001-repo-entorno-desarrollo-no-plugin.md), el canal de
  producción.

### Opción 4: script PHP sobre la API pública del plugin, ejecutado con `wp eval-file`

- Pros: usa el camino oficial del plugin, es idempotente y el mismo código
  sirve para Docker y Playground.
- Contras: depende de una API interna que se mueve entre versiones.

### Opción 5: usar también en desarrollo la herramienta de despliegue

- Pros: una sola herramienta para local y producción.
- Contras: pide credenciales del despliegue y habla por HTTP contra el
  escritorio, así que no corre dentro de wp-env ni de Playground y no sirve en
  CI. Descartada para desarrollo, **conservada para el despliegue**.

## Decisión

Se adopta la misma decisión que el aplicativo de eventos, y por las mismas
razones: la opción 4 para desarrollo y CI, y la opción 5 como canal de
producción.

- `scripts/lib/snippet-sync.php` define
  `prc_sync_snippets_from_dir( string $dir ): array`, que parsea la cabecera de
  cada `snippets/*.php` (`prc_parse_snippet_header()`), quita las etiquetas
  PHP (`prc_strip_php_tags()`), hace **match por nombre exacto** contra los
  snippets existentes y crea, actualiza y activa con la API del plugin.
- La disponibilidad del plugin se comprueba antes de nada
  (`prc_code_snippets_is_active()`), y el nombre de la clase modelo se resuelve
  probando `Code_Snippets\Model\Snippet` y luego `Code_Snippets\Snippet`
  (`prc_code_snippets_model_class()`), porque cambió de espacio de nombres en
  la versión 3.10 del plugin.
- `scripts/sync-snippets.php` es el envoltorio que ejecuta `wp eval-file` y
  lanza `RuntimeException` cuando algo falla, para que la provisión se detenga
  en lugar de seguir a ciegas.
- `scripts/snippet-check.php` reproduce la validación de guardado del propio
  plugin sobre cada snippet PRC: es la red que atrapa el bundle sin guarda.
- El bundle se sincroniza como un snippet más: `make bundle` lo regenera y
  `make sync-snippets` lo sube. Las prioridades son deliberadas:
  `PRC — Roles y perfiles` con `Priority: 5`, `PRC — Aplicativo de
  procedimientos (CPT)` con `15`, y los dos cargadores de librerías con `20`,
  de modo que los roles existen antes de que el aplicativo compruebe
  capacidades y las librerías se encolan cuando ya se sabe qué pantalla es.
- `wp eval-file` se invoca con `--url=` donde haga falta caer en un sitio
  concreto. Los scripts no lo añaden por su cuenta.

## Consecuencias

### Positivas

- Sincronización idempotente y automatizable —provisión, CI, blueprints de
  Playground— sin SQL directo como camino principal.
- El fallo se propaga: la provisión se para si Code Snippets no está activo o
  si no hay nada que sincronizar, en vez de terminar en verde sin haber hecho
  nada.
- `snippet-check.php` detecta antes de desplegar el fallo más caro de este
  modelo: el snippet que se desactiva solo al guardarse.

### Negativas

- El match por nombre exige no renombrar snippets a la ligera: renombrar crea
  uno nuevo y deja el viejo, que hay que borrar a mano en el escritorio.
- Dependencia de una API interna que ya ha cambiado una vez. El entorno local
  instala la última estable del plugin y el destino lleva la que lleve: se
  desarrolla contra una versión y se despliega contra otra.
- Queda una vía de SQL directo: `prc_force_activate_snippet()` escribe en
  `$wpdb->prefix . 'snippets'` cuando el validador del plugin rechaza un código
  que PHP sí acepta. Es desarrollo únicamente, pero es una excepción a «nada
  de SQL directo» y conviene que se vea.
- Nada de esto valida el despliegue real: al destino llega por el cliente HTTP,
  con sus credenciales y contra un WordPress vivo. La sincronización por
  `eval-file` solo garantiza el entorno local.

### Neutras

- Los snippets se guardan sin la etiqueta `<?php` inicial, tal y como espera
  Code Snippets; la librería la quita antes de guardar.
- El sitio actual convivirá con el aplicativo nuevo mientras dure la
  transición: sus fragmentos y los snippets PRC están en la misma tabla, con
  prioridades que no se pisan. Retirar los viejos no es cosa de este
  mecanismo, es cosa del plan de implantación.
