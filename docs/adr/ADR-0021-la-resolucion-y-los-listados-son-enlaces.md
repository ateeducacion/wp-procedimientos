---
id: ADR-0021
title: "La resolución y los listados son enlaces, no copias"
status: Propuesta
date: 2026-09-15
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0006, ADR-0013, ADR-0020, ADR-0022, ADR-0024]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-fable-5-1"
---

# ADR-0021: La resolución y los listados son enlaces, no copias

## Estado

Propuesta (2026-09-15). Nada implementado: fija las tres metas de URL antes de
registrarlas.

## Contexto

El encargo lo dice con sus palabras: «la URL de la resolución —mejor que
mantener copia—».

Hoy se mantiene copia. Medido sobre la foto del 2026-09-15 (material en
`.local/`), el formulario de alta tiene **cinco campos de fichero** para
documentos oficiales: la resolución (PDF, hasta 10 MB) con su título, la
corrección de la resolución con el suyo, el listado provisional de admitidos,
el listado definitivo y «otro tipo de listado» con un texto para rotularlo.
Los ficheros se suben a la carpeta del gestor de formularios, se sirven desde
ahí y la página de la convocatoria los enlaza con el nombre del fichero; la
portada del sitio enseña los siete últimos listados publicados leyendo esos
mismos campos.

Lo que eso produce, visto en el sitio:

- **La resolución ya está publicada en otro sitio.** La firma un órgano y se
  publica en el portal oficial de quien convoca, con su dirección y, a veces,
  con su código de verificación. La copia del gestor es una segunda copia de
  un documento que ya tiene casa.
- **Cuando hay corrección, hay dos ficheros y un párrafo.** El título de la
  corrección y su PDF van en campos aparte; la página enseña los dos y el
  visitante decide cuál manda.
- **Los listados cambian de versión.** Un listado provisional con una errata
  se vuelve a subir; el anterior sigue en la carpeta con otro nombre.
- **El estado depende de alguien.** Que exista un listado definitivo no
  cambia el desplegable de estado: hay que acordarse.

## Problema

¿El procedimiento guarda los documentos oficiales —resolución y listados— o
guarda su dirección?

## Factores de decisión

- **El encargo pide enlaces.** Es la única petición literal de la SDD sobre
  esto.
- **Una copia diverge.** Dos sitios con el mismo PDF acaban con dos versiones.
- **Un enlace caduca.** Portales que reorganizan, direcciones con
  identificadores de sesión, documentos que se retiran.
- **El estado `resolved` sale de un dato**
  ([ADR-0013](ADR-0013-el-estado-del-procedimiento-se-deriva-de-las-fechas.md)):
  cuanto más simple el dato, más fiable el estado.
- **Ficheros son responsabilidad.** Espacio, tipos permitidos, tamaño, quién
  los sirve, qué pasa al borrar el procedimiento.

## Alternativas consideradas

### Opción 1: adjuntos de WordPress en la biblioteca de medios

| Pros | Contras |
|------|---------|
| Todo dentro de WordPress, con `post_parent` al procedimiento. | Sigue siendo copia; la biblioteca es pública por diseño y no distingue versión. |
| Se puede previsualizar. | Cinco campos de subida, tipos y tamaños que validar, y la papelera no borra adjuntos. |

Descartada. Cambia de carpeta el mismo problema.

### Opción 2: un almacén privado propio

Ficheros fuera del alcance del servidor web, servidos por el aplicativo con
comprobación de capacidad.

Descartada **para esto**: la resolución y los listados son documentos
públicos; un almacén privado es la respuesta a la documentación adjunta del
centro (fase 2), no a esto.

### Opción 3: tres direcciones (elegida)

| Pros | Contras |
|------|---------|
| Cero ficheros, cero espacio, cero versión desactualizada. | Un enlace que caduca deja la ficha con un enlace roto, y nadie avisa. |
| El documento se lee donde se publicó, con su verificación. | No hay vista previa ni copia de respaldo. |
| `resolved` es «hay listado definitivo», sin desplegable. | Quien gestiona tiene que tener la URL antes de poder ponerla. |

### Opción 4: dirección más copia de respaldo

Lo anterior y, además, descargar y guardar el fichero.

Descartada: es hacer las dos cosas y cargar con los contras de ambas. Si un
día hace falta respaldo, es una ADR nueva.

## Decisión

**La resolución y los listados son tres URL del procedimiento.**

| Meta | Qué es |
|---|---|
| `prc_resolution_url` | La resolución que aprueba la convocatoria |
| `prc_provisional_list_url` | El listado provisional de admitidos |
| `prc_final_list_url` | El listado definitivo de admitidos |

### Reglas

1. **Se registran como `string` con `sanitize_callback` `esc_url_raw`** y
   `Domain/ProcedureInput` rechaza lo que no sea una URL absoluta con esquema
   `http` o `https`. Vacío es válido: significa «todavía no».
2. **No hay campo de corrección ni de «otro listado».** Una corrección
   sustituye la URL de la resolución o se enlaza desde la descripción del
   procedimiento (`post_content`), que admite enlaces. «Otro tipo de listado»
   va por el mismo sitio. Menos campos, menos ambigüedad sobre cuál manda.
3. **`prc_final_list_url` no vacía ⇒ estado `resolved`**
   ([ADR-0013](ADR-0013-el-estado-del-procedimiento-se-deriva-de-las-fechas.md)),
   por encima de `amendment`. Poner el listado definitivo es resolver; no hay
   otro interruptor.
4. **Se pintan como enlaces**, en la ficha pública y en el panel «Enlaces» del
   taller, con `rel="noopener noreferrer"` y en pestaña nueva; el texto del
   enlace es fijo («Resolución», «Listado provisional», «Listado definitivo»),
   no el nombre del fichero.
5. **Se editan en `closed` y `resolved`**, que es cuando llegan: la tabla de
   estados de la SDD lo permite y el panel «Enlaces» es el único editable en
   esos estados junto con las fechas.

### Lo que el aplicativo no hace

No comprueba que la URL responda, ni al guardar ni después. No descarga nada.
No distingue un PDF de una página. Es un dato que quien gestiona pone y
mantiene.

## Consecuencias

### Positivas

- Se acaban cinco campos de fichero, sus límites de tamaño y su carpeta.
- No hay dos versiones del mismo documento: hay una, donde se publicó.
- `resolved` no depende de que alguien se acuerde de cambiar un desplegable.
- La papelera y el borrado no dejan ficheros huérfanos.

### Negativas

- **Los enlaces caducan.** Un portal que reorganiza sus direcciones deja las
  fichas de tres cursos con enlaces rotos, y el aplicativo no se entera. No
  hay comprobación periódica; añadirla es trabajo futuro, si el problema
  aparece.
- **Sin corrección propia, la historia se pierde.** Sustituir la URL de la
  resolución borra la anterior; si importa conservar las dos, quien gestiona
  las enlaza en la descripción, a mano.
- **Sin vista previa.** El visitante sale del sitio para leer el documento.
- **La portada de hoy enseña «los últimos listados publicados».** Con tres URL
  se puede seguir haciendo —`prc_final_list_url` no vacía, orden por fecha de
  modificación—, pero no está en las cinco pantallas de la SDD.
- **Quien gestiona necesita la URL antes.** Si el documento se publica en el
  portal oficial después de abrir el plazo, la ficha va sin resolución hasta
  entonces. Hoy pasa lo mismo con el PDF.

### Neutras

- Los PDF que hoy están en la carpeta del gestor de formularios no se mueven
  ni se enlazan: son del sistema anterior
  ([ADR-0024](ADR-0024-las-solicitudes-de-hoy-no-se-migran.md)).
- La documentación que aporta el centro —proyecto, memoria, aceptación
  firmada— no es de esta ADR. Es la fase 2, con almacén privado y decisión
  propia.
