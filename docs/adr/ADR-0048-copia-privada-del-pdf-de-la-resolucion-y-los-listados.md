---
id: ADR-0048
title: "El aplicativo guarda una copia privada del PDF de la resolución y los listados"
status: Propuesta
date: 2026-11-18
related:
  issues: [93]
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0007, ADR-0021, ADR-0028]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5-5"
---

# ADR-0048: El aplicativo guarda una copia privada del PDF de la resolución y los listados

## Estado

Propuesta (2026-11-18). Matiza la
[ADR-0021](ADR-0021-la-resolucion-y-los-listados-son-enlaces.md) sin
sustituirla: en público sigue habiendo solo enlaces.

## Contexto

Las tres metas de «Enlaces» —`prc_resolution_url`, `prc_provisional_list_url`
y `prc_final_list_url` (`src/Prc/Meta/ProcedureMetaKeys.php`)— apuntan a la
publicación oficial. La ADR-0021 ya apuntaba su contra más dura: «los enlaces
caducan» y «el aplicativo no se entera». Hay procedimientos que duran dos
cursos, y cuando la dirección oficial se despublica o cambia de sitio no
queda copia en ningún sitio que controle quien gestiona
([#93](https://github.com/ateeducacion/wp-procedimientos/issues/93)).

El aplicativo ya tiene un almacén privado para ficheros: el de los
documentos de las solicitudes, `PublicFront/ApplicationFiles`
([ADR-0028](ADR-0028-los-adjuntos-de-una-solicitud-son-ficheros-privados.md)), con
nombre físico opaco, modo `0200` en reposo y descarga que autoriza
`ProcedureAccess`.

## Problema

Cuando muere el enlace oficial de la resolución o de un listado, ¿de dónde
sale el documento, y quién puede llegar a él?

## Factores de decisión

- **La fuente de verdad es la publicación oficial.** En público se enlaza
  esa dirección; no se le quita tráfico ni se duplica el documento.
- **Quien gestiona no tiene que hacer nada más.** Ni bajarse el PDF ni
  subirlo: basta con guardar el enlace.
- **Descargar algo de una URL que escribe una persona es una petición del
  servidor hacia fuera** (SSRF): no puede llegar a direcciones internas, ni
  colgarse, ni traerse un fichero sin límite.
- **Un fichero es responsabilidad**: dónde vive, quién lo lee, qué pasa al
  borrar el procedimiento y al duplicarlo.

## Alternativas consideradas

### Opción 1: no guardar copia (lo de la ADR-0021)

| Pros | Contras |
|------|---------|
| Cero ficheros, cero código. | Cuando muere el enlace, el documento se pierde para el aplicativo. |

### Opción 2: adjunto en la biblioteca de medios

| Pros | Contras |
|------|---------|
| La resolución es contenido público: la biblioteca es su casa natural. | Crea una **segunda URL pública** del mismo documento, que compite con la oficial y la sobrevive con su versión vieja. |
| Previsualización y gestión de WordPress. | `wp_insert_attachment()` y la biblioteca: justo lo que la ADR-0028 deja fuera del aplicativo; el adjunto se vería, se editaría y se borraría desde «Medios», fuera de las reglas del procedimiento. |

### Opción 3: copia privada en el almacén de los documentos (elegida)

| Pros | Contras |
|------|---------|
| Un único almacén, con sus reglas ya probadas: nombre opaco, modo cerrado, `.htaccess` y descarga por el aplicativo. | Si la dirección oficial muere, el público no ve la copia hasta que quien gestiona la descarga y la vuelve a publicar donde corresponda. |
| La copia no compite con la publicación oficial: no tiene URL pública. | Ocupa disco en `uploads/prc-private/`, que hay que incluir en las copias de seguridad (ya lo está, con los documentos). |

## Decisión

Al guardar el panel «Enlaces», `PublicFront/LinkCopies` descarga el PDF de
cada enlace **cuya dirección cambió o que todavía no tiene copia**, y lo
guarda con `ApplicationFiles::put()` en el almacén privado. La referencia vive
en la meta `prc_link_copies`: por cada enlace, la URL de origen, el nombre
opaco (`stored`, con la forma de `ApplicationFiles::STORED_SHAPE`), la fecha y
el tamaño.

- **La petición** es `wp_safe_remote_get()`: rechaza direcciones internas y
  vuelve a comprobar cada redirección (hasta tres). Quince segundos como
  mucho por enlace y veinte entre los tres —por debajo de los 30 s por
  defecto de `max_execution_time`—; lo que no cabe se avisa y se reintenta.
  `limit_response_size` de 20 MB más un byte: lo que llega a 20 MB más uno
  se descarta como demasiado grande.
- **Es un PDF si lleva la cabecera `%PDF-` en su primer kilobyte**, que es
  donde la busca un lector; ni la extensión ni el `Content-Type`, que los
  pone el servidor remoto. Un código distinto de 200
  tampoco es una copia.
- **Si falla, el enlace se guarda igual** y el aviso dice «No se pudo guardar
  una copia de … : …». Se reintenta la próxima vez que se guarden los
  enlaces; si la dirección nueva falla, **se conserva la copia anterior**
  —puede ser la única que queda— y el panel dice que es «de un enlace
  anterior».
- **La meta se escribe antes de borrar la copia anterior**: si algo se corta
  entre medias sobra un fichero, pero la meta nunca apunta a uno borrado. Si
  la meta apunta a un fichero que ya no está, guardar la misma dirección lo
  vuelve a descargar.
- **Quitar el enlace quita su copia**; borrar el procedimiento para siempre
  se lleva las suyas. Mandarlo a la papelera no
  ([ADR-0007](ADR-0007-borrar-es-enviar-a-la-papelera.md)).
- **El duplicado no se lleva copias**: `prc_link_copies` es `NEVER` en
  `Domain/ProcedureCopy::policy()`, como los enlaces.
- **La descarga** es `?prc_link_procedure=<id>&prc_link_copy=<clave>`, que
  autoriza `ProcedureAccess::can_download_link_copy()`: quien abre el
  procedimiento en su taller, **histórico incluido**, que es cuando más
  probable es que el enlace haya muerto. Ni centros, ni quien consulta, ni
  sin sesión. Se sirve como descarga, con `nosniff` y `no-store`, con las
  mismas cabeceras que los documentos.
- En «Enlaces», junto a cada URL: «Copia guardada el dd/mm/aaaa ·
  Descargar».
- **Sin cron.** No hay comprobación periódica de enlaces: la copia se toma al
  guardar, y ya está.

## Consecuencias

### Positivas

- Cuando el enlace oficial muere, el documento sigue a mano de quien
  gestiona, sin que nadie haya tenido que bajárselo.
- En público no cambia nada: una sola dirección, la oficial.
- No hay un segundo almacén ni un segundo modelo de permisos.

### Negativas

- **El guardado de «Enlaces» espera a la red**: hasta veinte segundos en el
  peor caso.
- **Dos guardados a la vez** pueden dejar un fichero sin referencia en el
  almacén: no se borra nunca, pero tampoco se sirve.
- **La copia es la del día en que se guardó.** Si el portal oficial sustituye
  el PDF en la misma dirección, la copia no se entera; hay que borrar y
  volver a pegar el enlace.
- **Un portal que sirve el PDF detrás de una página intermedia** (un visor,
  un aviso de cookies) no deja copia: lo que llega no es un PDF.
- Las copias ocupan disco, hasta 60 MB por procedimiento.

### Neutras

- Las copias de los procedimientos de antes de esta ADR no existen hasta que
  alguien vuelva a guardar sus enlaces.
- En local, `wp_safe_remote_get()` rechaza `localhost`: para probarlo hace
  falta una dirección pública o simular la respuesta.
