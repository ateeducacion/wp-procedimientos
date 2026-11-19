---
id: ADR-0049
title: "El procedimiento puede pedir la firma electrónica del justificante, con AutoFirma o subiéndolo firmado"
status: Propuesta
date: 2026-11-19
related:
  issues: [112]
  prs: [113]
  sdds: [SDD-0001]
  adrs: [ADR-0006, ADR-0009, ADR-0028, ADR-0041, ADR-0044]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5-5"
---

# ADR-0049: El procedimiento puede pedir la firma electrónica del justificante, con AutoFirma o subiéndolo firmado

## Estado

Propuesta (2026-11-19).

## Contexto

Hay procedimientos —proyectos europeos y similares— que exigen que la
solicitud vaya firmada electrónicamente por quien la presenta
([#112](https://github.com/ateeducacion/wp-procedimientos/issues/112)). Hoy el
aplicativo da un justificante en HTML que se imprime o se guarda como PDF con
el navegador (`PublicFront/View/ApplicationProgressView::receipt()`), y nada
más: la firma se pide por correo y se cruza a mano.

La firma con certificado en la Administración se hace con **AutoFirma**, la
aplicación de escritorio del Cliente @firma. Una página web habla con ella
con **AutoScript**, que el paquete `@erseco/autofirma-client` publica en npm
(`vendor/autoscript.js`): en escritorio, por un WebSocket con la aplicación
local; en el móvil, solo a través de un **servidor intermedio** con dos
servlets, de guardado y de recuperación.

El aplicativo ya tiene un almacén privado de ficheros con descarga autorizada
(`PublicFront/ApplicationFiles`,
[ADR-0028](ADR-0028-los-adjuntos-de-una-solicitud-son-ficheros-privados.md)) y
un historial que escucha acciones
([ADR-0044](ADR-0044-actuar-sobre-una-solicitud-y-su-historial.md)).

El artefacto de producción es un Code Snippet: no hay ningún fichero servido
desde el que entregar una librería ni un PDF generado en disco, y no se puede
instalar una dependencia de PHP en el servidor.

## Problema

¿Qué se firma, quién lo genera, cómo llega al servidor y qué comprueba el
servidor de lo que recibe, sin una librería de PDF en PHP y sin poder validar
la firma?

## Factores de decisión

- **No bloquear la presentación.** Una solicitud sin firmar tiene que contar
  igual: la firma es un requisito del procedimiento, no del plazo.
- **Escritorio primero, y un camino que no dependa de AutoFirma**: quien
  firma con otra herramienta, o desde un equipo sin AutoFirma, tiene que
  poder hacerlo.
- **Nada de dependencias de PHP** (es un Code Snippet) y **las librerías de
  terceros por jsDelivr con SRI y versión exacta**
  ([ADR-0006](ADR-0006-librerias-de-terceros-desde-cdn-con-sri.md)).
- **Lo que llega es un fichero que sube una persona**: tamaño acotado, que
  sea un PDF y que sea *este* justificante.
- **El repositorio es público**
  ([ADR-0009](ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md)): ni el
  servidor intermedio de nadie ni su dirección.

## Alternativas consideradas

### A. Generar el PDF en el servidor con una librería de PHP (FPDF, TCPDF)

- Pros: el servidor sabe exactamente qué bytes sirvió y puede comprobar que
  el firmado los lleva como prefijo (revisión incremental); el PDF sale
  igual sin JavaScript.
- Contras: una librería de PHP de miles de líneas dentro del bundle o en un
  snippet aparte, que hay que mantener al día a mano; el bundle ya es grande
  y el editor de Code Snippets lo sufre. **Descartada.**

### B. Generar el PDF en el navegador con jsPDF a partir de datos del servidor

- Pros: la librería viene del CDN con SRI, como Bootstrap y SweetAlert2; nada
  nuevo en el bundle ni en el servidor.
- Contras: el servidor **no sirve el PDF**, así que no puede cotejar «el
  prefijo de lo servido»; sin JavaScript no hay PDF que firmar.
- **Elegida**, con el cotejo sustituido por las comprobaciones de la
  decisión.

### C. Firmar el justificante en HTML o un XML (XAdES)

- Contras: quien recibe la firma —la propia administración, un socio
  europeo— espera un PDF que se abra y se vea firmado. **Descartada.**

### D. Validar la firma en el servidor (cadena de certificados, OCSP, sellos)

- Contras: hace falta una pila criptográfica de PDF y las autoridades de
  confianza, o llamar a un servicio de validación externo; nada de eso cabe
  en un snippet. La validación de verdad la hace quien la necesita con
  VALIDe o con su lector de PDF. **Descartada.**

## Decisión

**Un interruptor por procedimiento.** En «Avanzado», sección «Firma
electrónica», con su formulario y su operación (`signature`): «Pedir la firma
electrónica del justificante (AutoFirma)». Lo guarda quien edita el
procedimiento (`ProcedureAccess::can_edit()`), en cualquier estado mientras se
pueda editar. Es la meta booleana `prc_signature_required`, que **se copia**
al duplicar (`Domain/ProcedureCopy::policy()`): describe el procedimiento, no
una convocatoria.

**Sin firmar, la solicitud se presenta igual** y queda **«Pendiente de
firma»**. Firmar no cambia el estado de revisión ni la completitud.

**El justificante en PDF lo genera el navegador** con jsPDF 4.2.1 (jsDelivr,
SRI, versión exacta en `package.json`) a partir de lo que pinta el servidor
en la página: número de solicitud, procedimiento, centro (o la persona, si es
docente), fecha y hora de presentación, y la **huella SHA-256** de esos cuatro
datos (`Domain/SignedReceipt::fingerprint()`). Es **determinista**: la fecha de
creación sale de la presentación en UTC y el identificador del fichero, de la
huella; el mismo navegador da los mismos bytes cada vez.

**Dos caminos para firmar**, en la pantalla de la solicitud presentada:

- «**Firmar justificante con AutoFirma**»: AutoScript de
  `@erseco/autofirma-client` **1.9.3** —la primera versión estable; hasta
  ella solo había *canary*— por jsDelivr con SRI. Firma PAdES con
  `alwaysCreateRevision=true`: AutoFirma añade una revisión y deja intactos
  los bytes generados. Lo firmado vuelve en base64 a un campo oculto del
  mismo formulario de subida y se envía.
- La **vía manual**: «Descargar justificante en PDF», firmarlo fuera y
  «Subir el justificante firmado» con un formulario normal por POST con su
  nonce por solicitud. **La subida funciona sin JavaScript**; generar el PDF,
  no.

El botón de AutoFirma solo aparece si AutoScript cargó y, en un móvil, solo
si hay servlets del servidor intermedio. **Los servlets son un filtro,
`prc_autofirma_servlets`, vacío por defecto** (`array( 'storage' => '',
'retrieve' => '' )`): este repositorio no sabe de ningún servidor intermedio
y no lo nombra. Sin ellos, en el móvil queda la vía manual.

**Lo que se comprueba al recibir el firmado**
(`Domain/SignedReceipt::refuse()`), en este orden:

1. Que no pase del tamaño máximo de un documento
   (`ApplicationFiles::max_bytes()`).
2. Que empiece por `%PDF`.
3. Que lleve un diccionario de firma (`/Type /Sig` o `/FT /Sig`, también
   dentro de un flujo de objetos comprimido).
4. Que tenga **un solo `/ByteRange`** —una sola firma— y que **cubra el
   fichero entero**: empieza en 0, el hueco es la cadena hexadecimal de
   `/Contents` y el segundo tramo acaba en el último byte. Lo que se añade
   después de firmar deja un `/ByteRange` que no llega al final.
5. Que el texto lleve `Solicitud: <número>` y `Huella SHA-256: <huella>`,
   recalculada en el servidor con los datos de hoy de esa solicitud.

**Sin validación criptográfica.** No se comprueba que la firma sea válida,
de quién es el certificado ni si está revocado (alternativa D).

**Se guarda** con `ApplicationFiles::put()` en el almacén privado, con
nombre opaco (ADR-0028), y la referencia en la meta de la solicitud
`prc_signed_receipt`: el descriptor del fichero, cuándo y quién, y por qué
camino (`autofirma` o `upload`). Se escribe con `add_post_meta( …, true )`:
si dos envíos llegan a la vez, el segundo no pisa al primero y su fichero se
borra. **Una vez firmado, no se vuelve a firmar.** La operación emite
`prc_receipt_signed` y `ApplicationHistory` la anota como «Justificante
firmado» (ADR-0044). Borrar la solicitud para siempre se lleva el fichero.

**Quién**: firma quien puede ver la solicitud como propia
(`ProcedureAccess::can_view_application( …, VIEW_OWN )`) en un procedimiento
publicado, no histórico, que pide la firma, mientras la solicitud no esté a
la espera de que el centro la complete. **Descarga el firmado** el centro y
quien revisa (`ProcedureAccess::can_review()`); quien consulta por zona, no
(ADR-0043: la consulta enseña una lista cerrada).

**Dónde se ve**: «Pendiente de firma» o «Firmado el … por …» en la tarjeta de
la solicitud, en el justificante, en la tabla de «Solicitudes» (con el filtro
«Solo las pendientes de firma»), en la revisión de cada una con su descarga, y
la columna «Firma del justificante» en la exportación. Solo en los
procedimientos que piden la firma.

## Consecuencias

### Positivas

- La firma se pide y se recoge dentro del aplicativo, con su rastro en el
  historial, sin correos.
- Nada nuevo en el servidor: ni librería de PDF, ni ruta REST, ni
  dependencia. Lo de terceros viene del CDN con SRI y en desarrollo de
  `node_modules`, como lo demás.
- El camino manual no depende de AutoFirma: vale cualquier herramienta que
  firme PAdES.

### Negativas

- **No hay cotejo con «lo servido».** El servidor no genera el PDF, así que
  no puede comprobar que lo firmado es exactamente lo que dio: alguien con
  la huella delante podría componer otro PDF que la lleve y firmarlo. Lo que
  se comprueba en su lugar —número, huella, una sola firma que lo cubre todo—
  ata el fichero a *esta* solicitud y detecta lo añadido después de firmar,
  pero no lo que se cambió antes.
- **Sin validación criptográfica.** Un PDF con un diccionario de firma bien
  formado y una firma inválida, caducada o de otra persona se acepta. Quien
  necesite garantías tiene que validarlo con VALIDe o su lector de PDF; el
  aplicativo dice «firmado», no «firma válida».
- **Sin JavaScript no hay PDF**: la subida funciona, pero el fichero que se
  firma lo genera el guion. El justificante en HTML guardado como PDF desde
  el navegador no sirve: su texto va en fuentes incrustadas y la huella no
  se encuentra.
- **Una herramienta que reescribe el PDF al firmarlo** (sin revisión
  incremental) puede codificar el texto de forma que no se encuentre. Se
  miran también los flujos comprimidos, pero un texto en hexadecimal o con
  otra codificación no se reconoce y se rechaza.
- **Si cambian los datos del justificante** —el título del procedimiento, el
  nombre del centro en la solicitud— después de generar el PDF, la huella ya
  no coincide y hay que volver a descargarlo.
- jsPDF con las fuentes estándar solo escribe Latin-1: un carácter de fuera
  de esa tabla en el título sale mal en el PDF (la huella, que es lo que se
  comprueba, no se ve afectada).
- **El móvil depende de un servidor intermedio** que este repositorio no
  trae; sin él, en el móvil solo hay vía manual.
- Dos librerías más que mantener al día: `jspdf` y `@erseco/autofirma-client`,
  con su SRI y su versión en tres sitios.

### Neutras

- Las solicitudes presentadas antes de encender el interruptor quedan
  «Pendiente de firma» y se pueden firmar después.
- Apagar el interruptor esconde el estado, pero no borra los firmados: se
  siguen descargando si se vuelve a encender.
- La prueba con AutoFirma de verdad no cabe en la integración continua: los
  tests usan un PDF firmado sintético (una revisión con su `/ByteRange`) y un
  AutoScript simulado.

## Adenda — 2026-11-19

Tras la revisión del código de [#113](https://github.com/ateeducacion/wp-procedimientos/pull/113):

- **Los flujos se descomprimen con tope.** Antes de inflar nada se miran en
  los bytes el `/ByteRange` y que cubra el fichero; lo descomprimido, todo
  junto, no pasa del tamaño máximo de un documento. Un PDF con un flujo que
  se infla sin fin no tumba la petición: se lee hasta el tope y, sin la
  huella dentro, se rechaza.
- **La subida a mano pasa por `ApplicationFiles::refuse()`**, la misma
  política que el resto del almacén privado: errores de subida, tamaño y
  tipo por contenido con `wp_check_filetype_and_ext()`.
- **`add_post_meta( …, true )` no es atómico**: mira y luego inserta, como
  `Meta/MetaSwap` cuando no hay meta. Dos envíos en el mismo instante aún
  pueden dejar dos filas; se lee la primera y sobra un fichero que nadie
  sirve. El que pierde cuando ya hay firma dice «ya está firmado», no
  «vuelva a intentarlo».
- La columna «Firma del justificante» es de las que solo salen si se marcan,
  también con «Todas las columnas», como la nota interna.

## Adenda — 2026-11-19: el DNI o NIE de coordinación entra en el justificante

Decidido después de la adenda anterior (#114): el justificante lleva la
coordinación con su DNI o NIE, junto a quien presenta y solo si lo hay.

- **En el justificante impreso**, una fila «Coordinación» con cada persona
  coordinadora como «Nombre Apellidos (DNI o NIE …)»; sin DNI, solo el
  nombre, y sin coordinación, sin fila
  (`ApplicationMetaRegistration::receipt_coordinators()`).
- **En el PDF y en la huella.** `SignedReceipt::FIELDS` pasa de cuatro datos
  a cinco: `number`, `procedure`, `centre`, `submitted` y `coordination`, en
  ese orden, y jsPDF escribe la fila «Coordinación» solo si no está vacía.
  Encaja en el diseño de la huella sin tocarlo: es otro dato de lo que se
  presentó, se calcula igual al pintar y al recibir, y si cambia —otra
  persona, otro DNI— el PDF ya descargado deja de valer y hay que
  descargarlo de nuevo, como con el título o el nombre del centro.
- **Contra:** las huellas calculadas con cuatro datos no coinciden con las de
  cinco. Un PDF descargado antes de este cambio y sin subir se rechaza con
  «sus datos han cambiado»; lo ya guardado como firmado no se vuelve a
  comprobar y sigue firmado. Como #113 aún no ha salido a producción, no
  hay justificantes firmados que migrar.
- **El DNI sigue sin salir de donde ya salía**: lo ven quien presenta y quien
  revisa, que ya lo veían en «Lo que presentó». No va en un correo —el acuse
  solo enlaza el justificante—, ni en la consulta por zona, ni en su CSV
  (`ObserverScope::ALLOWED_COLUMNS` no cambia; ADR-0043).

## Adenda — 2026-11-19: tras la revisión de #115

- **La coordinación sale de donde se recoge.** Si el procedimiento la
  recoge en la lista de personas (`coordinators_in_list`), el formulario ya
  no la pide y lo que quede en `prc_coordinator` es de antes: el
  justificante y la huella leen la lista. Si no, la meta. Lo decide
  `ApplicationProgress::coordination()`, que usan los dos.
- **Matiz a «el DNI sigue sin salir de donde ya salía».** Con
  `prc_autofirma_servlets` configurado para firmar desde el móvil, el PDF
  que se firma pasa por el servidor intermedio que elige la instalación, y
  ahora lleva el DNI o NIE de coordinación. Antes no llevaba ningún dato
  personal. Quien configure los servlets tiene que contar con ello.
- **Firmado no quiere decir al día.** Lo guardado como firmado no se vuelve
  a comprobar: si después se cambia la coordinación o su DNI dentro del
  plazo, el justificante impreso y el PDF firmado dicen cosas distintas y
  la solicitud sigue «Firmada». Ya pasaba con las respuestas, que el PDF no
  lleva; el PDF firmado prueba lo que había al firmar.

## Adenda — 2026-11-23

- **El trabajo de descomprimir también tiene tope.** El tope de 2026-11-19
  acotaba lo que se añade, no lo que se gasta: un flujo que se infla por
  encima de lo que queda falla sin restar nada, y cientos de bombas pequeñas
  hacían inflar cada una hasta el tope (medido: un PDF de 9,4 MB con 800,
  5,4 s de CPU por subida). Ahora cada intento cuenta, salga o no, hasta
  `SignedReceipt::MAX_INFLATES` (64); el flujo que no empieza como zlib ni se
  intenta, y sin salto antes de `endstream` no se repite. El mismo PDF, 0,2 s.
- **Quién firma y quién descarga lo decide `ProcedureAccess`**, como toda
  decisión de «puede o no puede»: `can_sign_receipt()` y
  `can_download_signed_receipt()`, que antes eran `can_sign()` y
  `can_download()` de `ReceiptSignature`. `ReceiptSignature` las pregunta,
  como `LinkCopies` pregunta `can_download_link_copy()` (ADR-0048).
