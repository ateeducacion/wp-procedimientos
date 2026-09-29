---
id: ADR-0029
title: "El catálogo de centros educativos es un dato maestro externo y se cachea localmente"
status: Aceptada
date: 2026-09-20
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0009, ADR-0016, ADR-0017, ADR-0018, ADR-0019]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Antigravity"
  model: "inherit"
---

# ADR-0029: El catálogo de centros educativos es externo y se cachea localmente

## Estado

Aceptada (2026-09-20). Complementa y perfecciona la [ADR-0017](ADR-0017-el-catalogo-de-centros-no-se-versiona.md).

## Contexto

Las solicitudes de los centros educativos requieren identificar con exactitud el centro de
procedencia de la persona solicitante ([ADR-0016](ADR-0016-el-centro-de-la-persona-es-una-meta-configurable.md)
y [ADR-0018](ADR-0018-la-solicitud-es-un-contenido-del-procedimiento.md)). La ADR-0017 definió
que el catálogo no se versiona y que `CentreCatalog::all()` pregunta al filtro `prc_centres`,
con valor por defecto en `uploads/prc/centres.json`.

Sin embargo, depender exclusivamente de la subida manual de un fichero JSON en `uploads/` presenta
limitaciones de mantenimiento: nadie avisa de cuándo caduca, su actualización es manual y no
hay validación de integridad criptográfica.

Existe un catálogo maestro externo versionado e independiente que consolida y publica el censo
oficial de centros mediante dos artefactos estáticos:
- `manifest.json`: metadatos de versión de esquema (`schema_version = 1`), recuento de registros,
  fecha de actualización y suma SHA-256 del fichero de datos.
- `centros.min.json`: array JSON con los registros normalizados (`code`, `name`, `island`,
  `municipality`, `type`, `active`).

## Decisión

1. **WordPress no replica el catálogo como CPT, taxonomía ni tabla SQL propia**:
   - No se crean posts ni tablas para almacenar ~1.500 centros.
   - El aplicativo de procedimientos no es propietario del catálogo, sino un consumidor de un
     dato maestro externo.

2. **Copia local cacheada en opción de WordPress (`autoload = false`)**:
   - Para garantizar cero peticiones HTTP remotas durante el renderizado de pantallas o validación
     de solicitudes, el catálogo se almacena en la opción `prc_centres_catalogue` con `autoload = false`.
   - Indexado por el código oficial de 8 dígitos:
     `[ '90000101' => [ 'code' => ..., 'name' => ..., 'island' => ..., 'municipality' => ..., 'type' => ..., 'active' => bool ] ]`.
   - Almacena centros activos e inactivos para resolver identidades históricas.

3. **Sincronización segura con verificación SHA-256 y reemplazo atómico**:
   - La sincronización descarga `manifest.json`. Si el hash SHA-256 no ha cambiado, no se descarga
     `centros.min.json`.
   - Si ha cambiado, descarga `centros.min.json`, valida que su SHA-256 coincide exactamente con
     el manifest y comprueba la integridad de cada registro (código de 8 dígitos, denominación no
     vacía, formato booleano de `active`, ausencia de duplicados).
   - El catálogo local solo se reemplaza si la validación es completa y exitosa (reemplazo atómico).
   - Ante cualquier fallo de red o formato, se mantiene intacto el catálogo local previo y se anota
     el error en `prc_centres_catalogue_status`.
   - Programación periódica diaria vía WP-Cron (`prc_centres_cron_sync`) y sincronización manual
     protegida por nonce y capacidad administrativa en **Ajustes → Centros educativos**.

4. **Integración con `CentreCatalog::all()`**:
   - `CentreCatalog::all()` utiliza `CentreCatalogue::for_domain()` como fuente por defecto antes
     de acudir a `from_uploads()`.
   - El filtro `prc_centres` sigue estando disponible para que otros fragmentos o pruebas puedan
     intervenir el catálogo.

5. **Persistencia en solicitudes**:
   - Cada solicitud almacena únicamente:
     - `prc_centre_code`: código oficial de 8 dígitos (identidad inmutable).
     - `prc_centre_name`: denominación oficial en el momento de solicitar (snapshot histórico).

## Consecuencias

### Positivas
- Cero peticiones de red al presentar o revisar solicitudes.
- Base de datos limpia sin miles de filas de CPT ni metadatos superfluos.
- Integridad garantizada mediante comprobación criptográfica SHA-256.
- Mantenimiento automatizado y resiliente ante caídas del origen externo.
- Snapshot histórico inmutable en cada solicitud.

### Negativas
- Requiere configurar la URL del manifest o disponer de conectividad para la primera sincronización.

## Adenda — 2026-09-20

Tras la revisión del diseño inicial se introducen las siguientes precisiones y simplificaciones:

1. **Código oficial de exactamente 8 dígitos**:
   - Se fija el contrato formal con la expresión regular `^\d{8}$` en la sincronización del catálogo (`CentreCatalogueSync`).
   - Se rechaza cualquier código que no tenga exactamente 8 dígitos (7 dígitos, 9 dígitos, letras o caracteres especiales), sin normalización con ceros a la izquierda.
   - En las solicitudes, el código del centro procede de la meta de usuario configurada (`CentreScope::code_for()`) y se almacena junto al snapshot textual del nombre en ese momento (`Applications::save()`).

2. **HTTPS obligatorio y prevención de SSRF**:
   - Las fuentes remotas del catálogo (`manifest.json` y `centros.min.json`) exigen obligatoriamente el esquema `https://`.
   - Se validan de forma estricta antes de realizar cualquier petición HTTP remota con `is_valid_https_url()`, rechazando `http://`, esquemas no seguros o URLs relativas.
   - Se elimina cualquier campo de texto editable en el escritorio para introducir URLs arbitrarias, reduciendo la superficie de ataque SSRF y evitando desconfiguraciones administrativas. Las URLs se configuran mediante constantes de entorno (`PRC_CENTRES_MANIFEST_URL`, `PRC_CENTRES_CATALOGUE_URL`), opciones programáticas o filtros de WordPress (`prc_centres_manifest_url`, `prc_centres_catalogue_url`).

3. **Candado de sincronización atómico con token**:
   - Se sustituye el mecanismo de transients (`get_transient`/`set_transient`) por un candado atómico basado en `add_option('prc_centres_sync_lock', ...)`.
   - Almacena un token único aleatorio y la marca temporal. La liberación (`release_lock()`) requiere verificar la titularidad del token con `hash_equals()`, evitando que un proceso borre accidentalmente el candado adquirido por otro.
   - Si un candado caduca tras 300 segundos (proceso muerto), el mecanismo lo retira y lo vuelve a reclamar de forma atómica.

4. **Integración en Ajustes y diagnóstico (`Prc\Admin\Settings`)**:
   - Se descarta la pantalla independiente `CentreSettings` bajo `manage_options` (esta adenda sustituye la ubicación inicial «Ajustes → Centros educativos» propuesta más arriba).
   - La información de diagnóstico del catálogo se integra en la pantalla existente del aplicativo (`Ajustes y diagnóstico de procedimientos`), respetando la capacidad propia del aplicativo (`ProcedureAccess::CAP_MANAGE`).
   - Se expone el estado, recuentos de registros, fechas, SHA-256 y un botón de actualización manual con nonce y control de acceso.

5. **Eliminación de WP-CLI**:
   - El entorno de producción no dispone de WP-CLI. Dado que el aplicativo ya cuenta con WP-Cron diario y sincronización manual desde la interfaz de diagnóstico, se elimina por completo `CentreCli` (`wp prc centres sync`) para no mantener código innecesario en el aplicativo.

6. **Preservación del snapshot histórico**:
   - Cada solicitud guarda `prc_centre_code` y `prc_centre_name`. Cambios posteriores de denominación o estado en el catálogo de centros no alteran las solicitudes ya registradas.

### Revisión final de validación, transporte y concurrencia

1. **Transporte seguro con `wp_safe_remote_get()` y HTTPS**:
   - Tanto `manifest.json` como `centros.min.json` se descargan exclusivamente mediante `wp_safe_remote_get()`, previniendo redirecciones o resoluciones a direcciones de bucle local o redes privadas internas (mitigación SSRF).
   - Se mantiene complementariamente la validación sintáctica estricta de `https://` y host no vacío (`is_valid_https_url()`).

2. **Recuperación segura de candado caducado (compare-and-delete)**:
   - La recuperación de un candado expirado (>300 s) y su posterior liberación ejecutan un compare-and-delete atómico en base de datos (`DELETE FROM {$wpdb->options} WHERE option_name = %s AND option_value = %s`).
   - Esto evita condiciones de carrera donde un proceso lento intente eliminar un candado antiguo y borre accidentalmente el candado nuevo recién adquirido por otro proceso contemporáneo.


