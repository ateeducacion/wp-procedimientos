---
id: ADR-0027
title: "Sincronización de snippets por contenido"
status: Propuesta
date: 2026-09-19
related:
  issues: []
  prs: []
  sdds: []
  adrs: [ADR-0002, ADR-0006, ADR-0010]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-sonnet-5"
---

# ADR-0027: Sincronización de snippets por contenido

## Estado

Propuesta

## Contexto

Según [ADR-0002](ADR-0002-sincronizacion-snippets-eval-file.md), el
aplicativo se despliega como snippets de Code Snippets y
`scripts/lib/snippet-sync.php` los sincroniza desde `snippets/*.php` con la
API pública del plugin (`save_snippet()`, `activate_snippet()`,
`get_snippets()`), ejecutada por `scripts/sync-snippets.php` bajo `wp
eval-file`. Hasta ahora, `prc_sync_snippets_from_dir()` construye un objeto
`Snippet` para cada fichero y llama a `Code_Snippets\save_snippet()` **siempre**
que el snippet ya existe, sin comparar su contenido con lo que hay en la
tabla, y a continuación llama a `activate_snippet()` sobre el resultado.

`Code_Snippets\save_snippet()` no es una operación neutra: en cada llamada
actualiza `modified`, reevalúa el código si el snippet está activo —lo que lo
**ejecuta**— y limpia la caché de snippets del sitio. Guardar un snippet que
no ha cambiado deja, por tanto, una revisión y una marca de tiempo
artificiales, y repite una ejecución del código que no tenía motivo para
repetirse.

Este repositorio parte del esqueleto del aplicativo de eventos
([ADR-0010](ADR-0010-se-parte-del-esqueleto-del-aplicativo-de-eventos.md)),
que resuelve el mismo problema con este mismo mecanismo desde su propia
`ADR-0035-sincronizacion-de-snippets-por-contenido.md`. Según la propia
ADR-0010, «cuando eventos corrija algo del esqueleto, se porta a mano, con un
diff de los ficheros renombrados»: esta ADR y su implementación son
exactamente ese porte, con los identificadores de este repositorio
(`prc_*`, `@package Prc`).

El bundle principal ya elimina los comentarios PHP correctamente
(`build/pack-snippet.php`); esta decisión no toca ese mecanismo.

Las librerías de terceros —Bootstrap 5, Bootstrap Icons, SweetAlert2— siguen
una arquitectura distinta y ya resuelta: se cargan desde jsDelivr con versión
exacta y *Subresource Integrity*, y en desarrollo y en los tests se sirven de
`node_modules`
([ADR-0006](ADR-0006-librerias-de-terceros-desde-cdn-con-sri.md)). No entran
en el bundle PHP ni en la tabla de Code Snippets como código propio, y su
identidad de versión ya es el trío `package.json` + URL versionada + SRI,
comprobado en `tests/unit/test-bootstrap5.php` y
`tests/unit/test-sweetalert.php`.

## Problema

¿Cómo determinar si un snippet propio necesita realmente actualizarse, sin
usar la versión global del aplicativo PRC como sustituto del contenido, y sin
volver a guardar —ni por tanto revalidar, reejecutar ni marcar como
modificado— un snippet cuyo contenido no ha cambiado?

## Factores de decisión

- Guardar un snippet activo lo reejecuta: un guardado que no representa un
  cambio real es una ejecución de código sin motivo.
- `modified` y la revisión del snippet deben significar «esto cambió», no
  «esto se sincronizó».
- La comparación tiene que ser determinista y fácil de probar sin depender de
  un WordPress vivo para la parte de normalización.
- No se puede confundir la versión del aplicativo (`CHANGELOG.md`, que sigue
  fijando el `@version` del bundle) con si un snippet concreto cambió: son
  preguntas distintas.
- La normalización de código para comparar tiene que ser la misma que la que
  ya se aplica antes de guardar (`prc_strip_php_tags()`), o la comparación
  detecta diferencias que no son reales.
- No se introduce una segunda forma de fijar versión de librerías de
  terceros: la de la [ADR-0006](ADR-0006-librerias-de-terceros-desde-cdn-con-sri.md)
  ya resuelve ese caso y no comparte nada con este.

## Alternativas consideradas

### Opción 1: guardar siempre todos los snippets

Es el comportamiento actual.

- Pros: ninguno más allá de la sencillez de no comparar nada.
- Contras: reejecuta código, revalida y actualiza `modified` en cada
  sincronización, también cuando nada cambió. Descartada.

### Opción 2: comparar únicamente la versión del aplicativo PRC

Sincronizar solo si el `@version` del `CHANGELOG.md` cambió desde la última
sincronización.

- Pros: una sola comparación, sin tocar el contenido de cada snippet.
- Contras: la versión PRC es del **aplicativo**, no de cada snippet
  independiente; `snippets/bootstrap5.php`, `snippets/sweetalert.php` y
  `snippets/roles-and-profiles.php` no llevan versión propia y cambian sin
  que cambie el `CHANGELOG`. Y al revés: subir la versión PRC por un cambio
  en un solo fichero forzaría a revalidar y reejecutar los demás sin que
  hayan cambiado. Descartada explícitamente por el problema que plantea esta
  ADR.

### Opción 3: comparar únicamente el código

Comparar solo el cuerpo del snippet, ignorando `desc`, `scope`, `priority` y
`tags`.

- Pros: cubre el caso más común.
- Contras: un cambio de ámbito o de prioridad —que cambia **cuándo y dónde**
  se ejecuta el snippet— pasaría desapercibido y el snippet desplegado se
  quedaría con el ámbito o la prioridad viejos. Descartada: el estado
  gestionado por el repositorio es más que el código.

### Opción 4: comparar todo el estado gestionado directamente

Comparar código, `desc`, `scope`, `priority` y `tags` campo a campo, sin
fingerprint.

- Pros: no necesita una función de hash; el resultado es el mismo que la
  opción elegida.
- Contras: cada punto de comparación repite la misma normalización a mano, y
  es fácil que dos comparaciones diverjan en un detalle —por ejemplo, que una
  compare el código ya normalizado y la otra no— sin que ningún test lo note.
  Descartada por no dejar un único sitio donde vive la normalización.

### Opción 5: fingerprint SHA-256 determinista del estado gestionado — ELEGIDA

Construir un array con claves fijas —`code`, `desc`, `scope`, `priority`,
`tags`— a partir de los mismos valores ya normalizados, codificarlo con
`wp_json_encode()` de forma determinista y calcular su SHA-256. Comparar
fingerprints con `hash_equals()`.

- Pros: una sola función de normalización (`prc_snippet_managed_state()`) y
  una sola de hash (`prc_snippet_fingerprint()`), reutilizables desde la
  sincronización y desde los tests; el fingerprint es una cadena corta y
  fácil de volcar en un mensaje de diagnóstico si hace falta; no depende de
  comparar objetos `Snippet` completos, que llevan campos que el repositorio
  no gestiona (`id`, `modified`, `revision`, `locked`...).
- Contras: una capa más de indirección sobre la opción 4; hay que mantener la
  lista de campos gestionados si algún día se gestiona alguno más.

## Decisión

Se adopta la opción 5, portada del esqueleto de eventos.

- `prc_snippet_managed_state( string $code, string $desc, string $scope, int $priority, array $tags ): array`
  (`scripts/lib/snippet-sync.php`) construye el estado gestionado con claves
  fijas. El código pasa por `prc_strip_php_tags()` —la misma normalización
  que ya se aplicaba antes de guardar, reutilizada y no duplicada— y las
  etiquetas se ordenan, así que ni la etiqueta `<?php`, ni el cierre `?>`, ni
  un salto de línea final, ni el orden de las etiquetas cambian el estado
  gestionado.
- `prc_snippet_fingerprint( array $managed_state ): string` codifica ese
  array con `wp_json_encode( $managed_state, JSON_UNESCAPED_SLASHES |
  JSON_UNESCAPED_UNICODE )` y calcula su SHA-256. Dos estados iguales
  producen siempre el mismo fingerprint; cualquier campo gestionado que
  cambie produce uno distinto.
- `prc_sync_snippets_from_dir()` calcula el fingerprint del snippet tal y
  como quedaría (el fichero) y el del snippet tal y como está (la fila
  existente, si la hay) y los compara con `hash_equals()`:
  - **No existe** → se crea y se activa. Resultado `created`.
  - **Existe y el fingerprint difiere** → se **clona** el objeto `Snippet` ya
    cargado de la tabla, se le aplican solo los campos gestionados y se
    guarda el clon (`save_snippet()`). Del objeto existente, y no de uno
    nuevo, porque uno nuevo dejaría `active`, `locked`, `condition_id`,
    `revision` y `cloud_id` en los valores por defecto de la clase, que
    `save_snippet()` escribiría encima de la fila. Y de un clon, y no del
    objeto mismo, porque `get_snippets()` deja sus objetos en la caché del
    plugin y `get_snippet()` devuelve esa misma instancia sin releer la
    tabla: modificarla le cambiaría a `save_snippet()` el «estado anterior»
    que él mismo relee y pasa al gancho `code_snippets/update_snippet`.
    Resultado `updated`.
  - **Existe, está bloqueado y su código difiere** → no se guarda nada.
    Resultado `error`, con mensaje en castellano. Ver más abajo.
  - **Existe, el fingerprint coincide y está activo** → no se llama a
    `save_snippet()` ni a `activate_snippet()`. Resultado `unchanged`.
  - **Existe, el fingerprint coincide y está inactivo** → no se guarda; se
    llama solo a `Code_Snippets\activate_snippet()` (con la misma
    degradación a `prc_force_activate_snippet()` que ya existía para el
    validador del plugin). Resultado `unchanged`, con `reactivated` a `true`
    solo si esa reactivación tuvo éxito.
- La versión del aplicativo PRC (`CHANGELOG.md`, `@version` del bundle) sigue
  existiendo y sigue siendo la fuente de verdad de **esa** versión —el bundle
  principal no cambia su política—, pero no interviene en absoluto en si un
  snippet independiente se sincroniza: eso lo decide únicamente el
  fingerprint de su propio estado gestionado.
- **Después de guardar se activa solo lo que no quedó activo en la tabla.** La
  autoridad es la **fila persistida, releída** en `prc_settle_activation()`, y
  no el estado que tenía el snippet antes ni el objeto que devuelve
  `save_snippet()`. Ese objeto no sirve: el plugin lo construye con un
  `get_snippet()` **anterior** a su `clean_snippets_cache()` final, y
  `get_snippet()` devuelve la instancia cacheada sin releer la tabla, así que
  puede ser la de antes del guardado, con el `active` de antes. Cuando corre
  `prc_settle_activation()` la caché ya está limpia y su `get_snippet()` sí va
  a la tabla:
  - **La fila está activa** → no se llama a `activate_snippet()`. Activar es un
    `UPDATE ... SET active = 1` y el plugin trata como fallo la llamada que no
    cambia ninguna fila (`if ( ! $result ) { return __( 'Could not activate
    snippet.' ) }`, `snippet-ops.php`), así que sobre un snippet ya activo
    devolvía siempre ese error, la degradación a
    `prc_force_activate_snippet()` lo «arreglaba» con otro `UPDATE` de cero
    filas y salía por pantalla un aviso que no correspondía a ningún
    problema.
  - **La fila está inactiva** → se conserva entera la recuperación con
    `prc_activate_snippet_with_fallback()`, que sigue haciendo falta: un
    snippet activo cuyo código cambia puede salir inactivo del guardado,
    porque `save_snippet()` llama a `test_snippet_code()` cuando el objeto
    llega activo, y esa función no se queda en el análisis léxico —si el
    `Validator` no encuentra nada, llega a `execute_snippet( $snippet->code,
    $snippet->id, true )`—, de modo que un `code_error` escribe la fila con
    `active = 0`.

  Es el mismo comportamiento condicional que implementa el cliente de
  despliegue `@erseco/code-snippets-client`, que lee el estado remoto,
  preserva `active`, actualiza y solo intenta restaurar la activación si el
  snippet estaba activo y el resultado vuelve inactivo. La publicación remota
  usa `@erseco/code-snippets-client@0.1.7` (versión exacta en `package.json`),
  que además protege las actualizaciones sobre snippets bloqueados
  —comprueba `code` y `name` antes de escribir, verifica después de escribir,
  y solo envía `locked` cuando quien llama lo pide—. Su lógica REST no se
  duplica aquí: `make sync-snippets` sincroniza el wp-env local con esta
  librería PHP y `npm run snippets` publica en el sitio de destino con el
  cliente.
- **`locked` se preserva, pero no significa «inmutable».** `save_snippet()`
  protege el código y el nombre de un snippet bloqueado restaurándolos desde
  la fila (`if ( $old_snippet->locked && $snippet->locked ) { $snippet->code =
  $old_snippet->code; … }`). Como los campos no gestionados se conservan, esa
  rama se activa, y guardar un snippet bloqueado cuyo código difiere habría
  informado `updated` dejando el código viejo en la tabla: un «actualizado»
  falso que la siguiente sincronización volvería a encontrar. Por eso:
  - **Bloqueado y el código difiere** → `error`, y no se toca nada: ni el
    candado, ni el código, ni la metadatos gestionada, que si no quedaría
    aplicada a medias. El sincronizador **nunca** quita el candado por su
    cuenta.
  - **Bloqueado y el código coincide** → se actualiza con normalidad la
    metadatos que el candado no protege (descripción, ámbito, prioridad,
    etiquetas), y el snippet sigue bloqueado.

  Con los ficheros de `snippets/`, esa segunda divergencia solo puede nacer en
  la base de datos —de quien edite la descripción desde el escritorio de Code
  Snippets—, porque la descripción, el ámbito y la prioridad se declaran en la
  cabecera del propio fichero: cambiarlas en el repositorio cambia también el
  código, que es justo lo que el candado no deja tocar.
- `scripts/sync-snippets.php` distingue las cuatro salidas en su resumen y en
  la línea de cada snippet (`sin cambios` / `sin cambios, reactivado` /
  `actualizado` / `creado`), y cuenta un `sin cambios` aparte de los
  `actualizado(s)`. Un estado inesperado cuenta como error, no como «sin
  cambios».
- Las librerías de terceros no entran en este mecanismo. Siguen la
  [ADR-0006](ADR-0006-librerias-de-terceros-desde-cdn-con-sri.md) sin
  cambios: versión exacta en `package.json`, URL versionada de jsDelivr y
  SRI. No se crea un bundle PHP de Bootstrap ni de SweetAlert2, ni un
  `Bundle Hash:` ni un segundo SHA-256 que duplique el SRI. El fingerprint de
  esta ADR identifica **código propio versionado en `snippets/*.php`**; el
  SRI identifica **un fichero de un tercero servido desde un CDN**. Son dos
  preguntas distintas y cada una tiene ya su respuesta.

## Consecuencias

### Positivas

- Menos escrituras en la tabla de snippets: una sincronización sin cambios
  reales no toca la base de datos salvo, como mucho, para reactivar.
- Menos revisiones y marcas de tiempo artificiales: `modified` vuelve a
  significar que el contenido cambió.
- Menos invalidaciones de caché de Code Snippets por sincronizaciones que no
  tenían nada que sincronizar.
- Menos revalidaciones y reejecuciones de código sin motivo: un snippet
  activo e inalterado no se vuelve a ejecutar por el mero hecho de
  sincronizar.
- `updated` vuelve a significar «esto cambió de verdad», tanto en la salida
  de `make sync-snippets` como en los resultados que consume cualquier otro
  script.
- Sincronizaciones más fáciles de auditar: el resumen distingue creado,
  actualizado, sin cambios y error, en vez de tratar «existe» y «cambió»
  como lo mismo.

### Negativas

- Hay una capa más de normalización y de fingerprint que mantener
  (`prc_snippet_managed_state()`, `prc_snippet_fingerprint()`).
- Hay que recordar qué campos forman el estado gestionado si algún día se
  gestiona alguno más (por ejemplo, si `snippets/*.php` empezara a declarar
  sus propias etiquetas en la cabecera): un campo que se use y no entre en el
  estado gestionado no se detectaría como cambio.
- Requiere tests específicos de la sincronización
  (`tests/unit/test-snippet-sync.php`), que no existían: hasta esta ADR
  ningún test de la suite ejercitaba la API de Code Snippets, y activarla
  para PHPUnit (el wp-env de tests instala el plugin pero no lo activa) fue
  parte del trabajo, acotado a esa única clase de test.

### Neutras

- No cambia la arquitectura de Code Snippets como destino de despliegue
  ([ADR-0002](ADR-0002-sincronizacion-snippets-eval-file.md)).
- No convierte el repositorio en un plugin de producción.
- No modifica la política de librerías de terceros
  ([ADR-0006](ADR-0006-librerias-de-terceros-desde-cdn-con-sri.md)): siguen
  con versión exacta y SRI, sin fingerprint propio.
- No despliega nada en producción por sí misma.
- No cambia ninguna versión de dependencia.
- Es un porte a mano de
  `ADR-0035-sincronizacion-de-snippets-por-contenido.md` del aplicativo de
  eventos, tal y como prevé la
  [ADR-0010](ADR-0010-se-parte-del-esqueleto-del-aplicativo-de-eventos.md);
  no hay automatismo entre los dos repositorios y no lo pretende haber.
