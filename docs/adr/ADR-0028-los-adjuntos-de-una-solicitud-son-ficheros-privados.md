---
id: ADR-0028
title: "Los adjuntos de una solicitud son ficheros privados, no medios de WordPress"
status: Propuesta
date: 2026-09-19
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0007, ADR-0016, ADR-0018, ADR-0019, ADR-0024]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0028: Los adjuntos de una solicitud son ficheros privados, no medios de WordPress

## Estado

Propuesta

## Contexto

La [ADR-0019](ADR-0019-la-solicitud-es-nucleo-fijo-mas-preguntas.md) fijó que
la solicitud de un centro es un núcleo fijo más hasta veinte preguntas de
cuatro tipos: `text`, `single`, `multiple` y `yesno`
([`Domain/ProcedureQuestions.php`](../../src/Prc/Domain/ProcedureQuestions.php),
`types()`).

Esa lista deja fuera lo que una convocatoria pide de verdad casi siempre:
**un documento**. El acta del claustro, el certificado de aprobación del
consejo escolar, la autorización firmada. Sin un tipo para eso, la única
salida es sacar al centro del formulario y pedírselo por correo, que es lo que
este aplicativo viene a quitar.

Las piezas que ya hay:

- La [ADR-0018](ADR-0018-la-solicitud-es-un-contenido-del-procedimiento.md)
  registra `prc_application` colgando de su procedimiento, **una por centro**,
  con todas sus metas en `auth_callback` a `false` y `show_in_rest` a `false`
  ([`Meta/ApplicationMetaRegistration.php`](../../src/Prc/Meta/ApplicationMetaRegistration.php)).
- `Access/ProcedureAccess::can_view_application()` ya responde exactamente a
  la pregunta que hace falta: el centro que la presentó ve la suya, y quien
  revisa ese procedimiento ve las de su ámbito
  ([`Access/ProcedureAccess.php`](../../src/Prc/Access/ProcedureAccess.php)).
- La [ADR-0024](ADR-0024-las-solicitudes-de-hoy-no-se-migran.md) deja fuera de
  la fase 1 las solicitudes y los documentos ya presentados, así que esta
  decisión solo alcanza a lo que se presente aquí.

El problema aparece al decidir dónde vive el fichero. Un `wp_insert_attachment()`
no crea «un fichero»: crea una entrada de `wp_posts` de tipo `attachment`, y
con ella, de serie y sin pedirlo:

| Lo que abre un adjunto | Dónde |
|---|---|
| La biblioteca de medios | `upload.php`, y el selector de medios de cualquier pantalla |
| Una página propia del adjunto | `is_attachment()`, con su permalink |
| La colección REST | `wp/v2/media` |
| El AJAX de medios | `query-attachments`, `get-attachment` |
| XML-RPC | `wp.getMediaLibrary`, `wp.getMediaItem` |
| Una URL física predecible | `wp_get_attachment_url()`, y el fichero servido directo por el servidor web |

Son seis superficies genéricas que el dominio **no necesita** para un acta con
nombres y firmas dentro, y cada una habría que guardarla, probar que está
guardada y volver a probarlo cada vez que cambie WordPress o un plugin.

## Problema

¿Dónde se guarda un documento que aporta un centro con su solicitud, y quién
decide si alguien puede leerlo?

## Factores de decisión

- **Superficie.** Cuantas menos puertas se abran, menos hay que guardar. Una
  puerta que no existe no se olvida de cerrar.
- **Coherencia con lo que ya hay.** `prc_application` está cerrada a propósito;
  sus documentos no pueden estar más abiertos que sus metas.
- **Autorización sobre el objeto del dominio.** La regla es sobre **esta**
  solicitud, con la política que ya existe, y no una capacidad genérica del
  estilo `current_user_can( 'edit_posts' )`. No se inventa un sistema de roles.
- **La solicitud se edita.** Mientras el plazo siga abierto, un centro vuelve y
  cambia lo que puso (ADR-0019), así que sustituir un documento tiene que ser
  una operación segura y volver a enviar el formulario sin adjuntar no puede
  vaciar lo presentado.
- **Tamaño del cambio.** Este repositorio no tiene capa de servicios ni
  contenedor de inyección; la solución tiene que caber en una clase.

## Alternativas consideradas

### Opción 1: adjunto normal de WordPress

Guardar el documento con `media_handle_upload()` y quedarse el ID de adjunto en
una meta de la solicitud.

- **A favor:** no hay que escribir nada; la subida, la validación de tipo y el
  nombre único los hace WordPress.
- **En contra:** el documento aparece en la biblioteca de medios, tiene página
  propia, sale en `wp/v2/media` y su URL física funciona para quien la tenga.
  Un acta de claustro con nombres y firmas quedaría a un enlace de distancia
  del resto del sitio, y quien solicita es alguien de un centro, no del
  equipo que gestiona. Descartada.

### Opción 2: adjunto, y esconderlo después con filtros y guardas

Crear el adjunto y añadir filtros: `ajax_query_attachments_args`,
`rest_attachment_query`, `rest_prepare_attachment`, algo para XML-RPC,
`template_redirect` para la página de adjunto y una regla del servidor para la
URL física.

- **A favor:** reutiliza la mecánica de subida de WordPress.
- **En contra:** son **seis o más guardas distintas**, cada una en una API
  distinta, para un fichero que nunca quisimos publicar. Falla en abierto: la
  puerta está abierta y se tapa; si un filtro deja de aplicarse —otro plugin
  con más prioridad, un cambio de WordPress, una ruta nueva— el documento se
  publica solo y nadie se entera. Y va contra la regla de la casa, que dice que
  esconder no es proteger: lo que protege es la comprobación, no el filtro de
  listado. Descartada.

### Opción 3: fichero privado sin adjunto — **elegida**

El fichero se guarda en un subdirectorio propio de `uploads/`, con nombre
aleatorio, y **no se crea ninguna entrada en `wp_posts`**. La solicitud guarda
un descriptor y la descarga la sirve el aplicativo tras preguntar a
`ProcedureAccess::can_view_application()`.

- **A favor:** falla en cerrado. No hay biblioteca, no hay página de adjunto,
  no hay REST, no hay XML-RPC, no hay URL física que el dominio necesite —
  porque no existe el adjunto—. La autorización queda en **un solo sitio** y es
  la que ya decidía quién ve una solicitud.
- **En contra:** hay almacenamiento propio que limpiar, una descarga propia que
  mantener, y el fichero no se puede reutilizar desde la biblioteca de medios.
  Lo último es deliberado.

### Opción 4: los bytes en la base de datos

Guardar el contenido en una meta, en Base64 o en binario.

- **A favor:** la copia de seguridad de la base de datos lo lleva todo; no hay
  directorio que proteger.
- **En contra:** una meta de `wp_postmeta` con diez mebibytes en Base64 —un
  tercio más de tamaño que el original— se carga entera en memoria cada vez que
  se lee la solicitud, y una convocatoria con varios centenares de centros
  convierte esa tabla en un almacén de ficheros. El volcado de la base de datos
  deja de caber en una revisión. Descartada.

### Opción 5: confiar solo en un nombre aleatorio

Guardar el fichero en `uploads/` con un nombre imposible de adivinar y servirlo
por su URL directa.

- **A favor:** no hace falta ni manejador de descarga ni permisos especiales.
- **En contra:** un nombre aleatorio evita que el nombre cuente algo; **no es
  una autorización**. La dirección viaja en el historial del navegador, en el
  `Referer`, en los registros del servidor y en cualquier sitio donde se pegue,
  y una vez fuera vale para siempre y para cualquiera. Descartada como única
  defensa; el nombre aleatorio se usa igualmente, pero **encima** de la
  protección, no en su lugar.

## Decisión

Se añade `file` como **quinto** tipo de pregunta, con rótulo «Archivo», y se
separa el almacenamiento en dos caminos que no se mezclan:

```text
CONTENIDO PÚBLICO DEL SITIO     DOCUMENTO DE UNA SOLICITUD
→ adjunto de WordPress          → almacén privado del aplicativo
→ biblioteca de medios          → sin adjunto, sin biblioteca
→ URL pública                   → sin REST ni XML-RPC de medios
                                → sin página de adjunto
                                → descarga autorizada por el aplicativo
```

Lo concreto, en [`PublicFront/ApplicationFiles.php`](../../src/Prc/PublicFront/ApplicationFiles.php):

1. **Una pregunta `file` no configura nada.** Tiene clave, rótulo,
   indicaciones, tipo y si es obligatoria, y nada más. Ni tamaño, ni tipos, ni
   varios ficheros por pregunta: eso convertiría la lista de preguntas en el
   constructor de formularios del que la ADR-0019 se sale. **Un fichero por
   pregunta**, y una solicitud lleva tantos documentos como preguntas de ese
   tipo tenga su procedimiento.
2. **La política es del aplicativo**, en un solo sitio: lista cerrada de PDF,
   JPEG, PNG, DOCX y ODT, y un tope de `min( 10 MiB, wp_max_upload_size() )`.
   No se admite nada que un navegador pueda ejecutar o interpretar —PHP, HTML,
   SVG, JavaScript— ni nada empaquetado. Del `type` que manda el navegador no
   se fía nadie: decide `wp_check_filetype_and_ext()`, que mira el contenido y
   lo cruza con la extensión, y por encima la lista propia. Si un procedimiento
   concreto necesitara otro formato, esa ampliación se decide a propósito.
3. **El nombre físico es opaco:** 32 dígitos hexadecimales aleatorios más la
   extensión que salga del tipo ya validado, en `uploads/prc-private/ab/cd/`.
   No lleva el nombre del centro, ni su código, ni el de quien solicita, ni el
   del procedimiento, ni el nombre original del fichero.
4. **El fichero queda en modo `0200`:** se puede escribir, no leer. Para leerlo
   hay que abrirlo a propósito, y se vuelve a cerrar en el `finally`. Se
   escribe además un `.htaccess` de denegación, que es un cinturón y no el
   pantalón: **nginx no lo lee**, y por eso la protección real es el modo del
   fichero y que la descarga pase por el aplicativo.
5. **Lo que se guarda en la solicitud es un descriptor**, en su propia meta
   `prc_files` —no dentro de `prc_answers`, que sigue siendo solo respuestas—:
   `id` opaco, `name` original saneado, `mime` validado, `size`, `sha256` y
   `stored`, que es una ruta **relativa** a la raíz privada. Ni ruta absoluta,
   ni URL, ni identificador de adjunto. La meta se registra como las demás de
   la solicitud: `show_in_rest` a `false` y `auth_callback` a `false`.
6. **No queda estado a medias.** Se validan los documentos antes de guardar
   nada; si falta uno obligatorio o uno no pasa la política, la solicitud no se
   guarda. Si guardar un documento falla, una solicitud recién creada se borra
   entera y una que ya existía se queda **exactamente como estaba**: ni un
   descriptor sin fichero ni un fichero sin descriptor.
7. **Sustituir va siempre en el mismo orden:** se guarda el nuevo, se actualiza
   el descriptor y **solo entonces** se borra el viejo. Nunca al revés. Y
   reenviar el formulario sin volver a adjuntar no vacía lo presentado ni falla
   por obligatorio.
8. **La descarga la sirve el aplicativo**, nunca el servidor web, y autoriza
   con `ProcedureAccess::can_view_application()` sobre **esta** solicitud. El
   identificador del documento no autoriza nada por sí solo. La respuesta va
   con `Content-Disposition: attachment`, el tipo validado,
   `X-Content-Type-Options: nosniff` y `Cache-Control: private, no-store`.
9. **Borrar definitivamente una solicitud se lleva sus ficheros; la papelera,
   no.** Borrar es enviar a la papelera
   ([ADR-0007](ADR-0007-borrar-es-enviar-a-la-papelera.md)), y restaurar una
   solicitud sin sus documentos es restaurar otra cosa. Lo mismo vale para una
   convocatoria cerrada o archivada: se sigue consultando, así que sus
   documentos se quedan.

Y lo que **no** cambia: el contenido público del sitio sigue por el camino
normal de WordPress, con su biblioteca de medios y su URL. **No se instala ni
un filtro global sobre la biblioteca de medios, la REST de medios o las páginas
de adjunto.** La privacidad la decide quién creó el fichero y para qué, no
volver privada la biblioteca entera.

## Autorización

La decide el guardián de solicitudes y no WordPress Media:

| Quién | Qué pasa |
|---|---|
| El centro que presentó la solicitud | Descarga la suya |
| Otro centro | Denegado |
| Quien revisa ese procedimiento, en su ámbito | Descarga |
| Quien gestiona otro ámbito | Denegado |
| Cualquier otra cuenta con sesión | Denegado |
| Anónimo | Denegado |

## Consecuencias

### Positivas

- La invariante se puede comprobar de una sola manera, y se comprueba: después
  de guardar un documento de un centro, en `wp_posts` no hay ni un adjunto más
  ([`tests/unit/test-application-files.php`](../../tests/unit/test-application-files.php)).
  No sale en la biblioteca, ni en `wp/v2/media`, ni en una página de adjunto,
  **sin haber escrito un solo filtro para conseguirlo**.
- La autorización vive en un único sitio y es la que ya existía: no hay un
  segundo sistema de permisos que mantener al día.
- El descriptor no contiene ninguna ruta absoluta ni ninguna URL, así que un
  volcado de la base de datos no dice dónde está nada.
- El camino público no se toca, y hay un test que lo sujeta.

### Negativas

- **Hay almacenamiento propio que limpiar.** La limpieza cuelga de
  `before_delete_post`; una solicitud borrada con SQL a pelo dejaría sus
  ficheros huérfanos.
- **Hay una descarga propia que mantener.** Cada cabecera de esa respuesta es
  responsabilidad de este repositorio y no de WordPress.
- **Las copias de seguridad tienen que incluir `uploads/prc-private/`.** Está
  dentro de `uploads/`, así que una copia normal del directorio lo lleva, pero
  conviene decirlo porque un fichero en modo `0200` es fácil de perder con una
  herramienta que copie solo lo legible.
- **Estos documentos no se pueden reutilizar desde la biblioteca de medios**, y
  es deliberado.
- El modo `0200` **no protege si el proceso corre como `root`** o si el usuario
  del servidor web es el propietario y el sistema ignora el modo. Es una capa,
  no un muro.

### Neutras

- **No se decide aquí una política de retención.** Hoy no existe ninguna: los
  documentos viven mientras viva su solicitud. Cuándo caduca una solicitud
  —cuánto se guarda un expediente ya resuelto— es una decisión que todavía no
  se ha tomado, y cuando se tome decidirá también cuánto se guardan sus
  documentos. Será otra ADR.
- No se diseña cifrado en reposo ni almacenamiento en objeto (S3 y parientes).
  Si alguna vez hiciera falta, el sitio donde entra es
  `ApplicationFiles::store()` y `::read()`, que son las dos únicas funciones
  que tocan el disco.
- La concurrencia no se aborda: dos lecturas simultáneas del mismo documento
  pueden solaparse al abrir y cerrar el modo. El peor caso es que una de las
  dos vea el fichero abierto unos milisegundos de más, no que alguien no
  autorizado lo lea, porque la autorización va antes y es independiente del
  modo.
- El filtro `prc_private_files_dir` permite mover la raíz, y
  `prc_private_file_mimes` ampliar la lista de tipos. Los dos se ven escritos
  en el repositorio y se auditan; ninguno de los dos relaja la autorización.
- Esta decisión no alcanza a los documentos ya presentados en el sistema
  anterior: la [ADR-0024](ADR-0024-las-solicitudes-de-hoy-no-se-migran.md) los
  deja donde están.
