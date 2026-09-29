---
id: ADR-0017
title: "El catálogo de centros no se versiona: lo contesta un filtro"
status: Propuesta
date: 2026-09-15
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0001, ADR-0009, ADR-0010, ADR-0016, ADR-0018, ADR-0019]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-fable-5-1"
---

# ADR-0017: El catálogo de centros no se versiona: lo contesta un filtro

## Estado

Propuesta (2026-09-15). Nada implementado: fija el contrato de
`Domain/CentreCatalog` antes de escribirlo.

## Contexto

Una solicitud la presenta un **centro**, y el centro no se teclea: viene del
código que la persona lleva en su cuenta
([ADR-0016](ADR-0016-el-centro-de-la-persona-es-una-meta-configurable.md)).
Para pasar de ese código a un nombre, y para saber si el centro es público o
privado —que es lo que cruza con `prc_ownership` del procedimiento—, hace
falta un catálogo.

Hoy ese catálogo es **un formulario más del gestor de formularios**, con unos
1.700 centros y cerca de cuarenta columnas por centro: código, denominación,
etapa, dirección, localidad, correos, titularidad, coordenadas y varias
columnas locales añadidas a mano. Lo importa un fragmento de código del sitio
desde un portal de datos abiertos, con un botón en los ajustes y un comando de
consola, y se ha reparado a mano al menos diez veces con otros tantos
fragmentos de un solo uso: una sincronización vació una columna y hubo que
reponerla centro a centro. El formulario de solicitud lo consulta con
búsquedas encadenadas —código → etapa, denominación, localidad, correo del
centro— en cada carga.

Dos cosas de ese catálogo no pueden entrar en este repositorio:

1. **Son nombres y lugares de una organización concreta.** Un fichero con
   1.700 centros y sus direcciones dice de quién es el despliegue con más
   precisión que cualquier logotipo
   ([ADR-0009](ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md)).
2. **Va por detrás de la realidad.** Se abren centros, se cierran, cambian de
   nombre; el catálogo se refresca cuando alguien se acuerda. Versionarlo es
   congelar una foto que caduca sin que el repositorio se entere.

El aplicativo de eventos ya resolvió una dependencia parecida —de dónde salen
los participantes mientras no hay formulario— con un filtro que en desarrollo
contesta un mu-plugin con datos inventados
([ADR-0010](ADR-0010-se-parte-del-esqueleto-del-aplicativo-de-eventos.md)). Es
el mismo problema con otro dato.

## Problema

¿De dónde saca el aplicativo la lista de centros —código, nombre y
titularidad— sin que el catálogo real entre en el repositorio, sin que el
aplicativo dependa de un servicio externo en cada petición y sin que el
entorno de desarrollo se quede sin centros?

## Factores de decisión

- **Repositorio público.** Nada del catálogo real se versiona
  ([ADR-0009](ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md)).
- **Se despliega pegando código.** No hay activación ni tabla propia sin
  escribir antes quién la crea y qué pasa si falla
  ([ADR-0001](ADR-0001-repo-entorno-desarrollo-no-plugin.md)).
- **Tres datos bastan.** El aplicativo necesita `code`, `name` y `ownership`.
  Etapa, localidad y correo del centro son del catálogo, no de la solicitud: se
  consultan por código cuando hagan falta.
- **El catálogo puede faltar o ir atrasado.** Un centro recién creado no está;
  eso no puede impedirle solicitar.
- **Desarrollo y tests necesitan centros** sin conexión y sin datos reales.
- **Quien despliega decide de dónde vienen** los suyos: un fichero, otro
  plugin, una consulta a su directorio.

## Alternativas consideradas

### Opción 1: versionar el catálogo en el repositorio

Un JSON en `src/` o en `assets/` con los 1.700 centros.

| Pros | Contras |
|------|---------|
| Cero configuración al desplegar. | **Publica el mapa de una organización.** Descartada por la ADR-0009 antes de discutir nada más. |
| Los tests tienen datos reales. | Cada cambio en un centro es un commit y un despliegue. |

Descartada.

### Opción 2: consultar el portal de datos abiertos en cada petición

`wp_remote_get()` al origen, con caché en un transient.

| Pros | Contras |
|------|---------|
| Siempre al día. | El origen es de quien despliega: la URL y las columnas van en el código de alguien. |
| Nada que subir. | Sin red no hay centros, ni en desarrollo ni en producción; el formato del CSV remoto no lo controla nadie de aquí (ya vació una columna una vez). |

Descartada.

### Opción 3: una tabla propia o un tipo de contenido `prc_centre`

| Pros | Contras |
|------|---------|
| Búsqueda y filtrado con SQL. | 1.700 filas que alguien tiene que importar, actualizar y depurar, con un importador propio: es reescribir el fragmento de importación que hoy da problemas. |
| Se edita desde el escritorio. | Una tabla necesita quien la cree; un CPT llena `wp_posts` de contenido que no es contenido. |

Descartada. Es la opción que más código exige para un dato que el aplicativo
solo lee.

### Opción 4: un filtro, con un fichero fuera del repositorio por defecto (elegida)

`CentreCatalog::all()` pregunta al filtro `prc_centres`; por defecto lee un
JSON en `uploads/` que sube quien despliega; en desarrollo lo contesta el
mu-plugin con centros inventados.

| Pros | Contras |
|------|---------|
| El catálogo real no entra en el repositorio. | Hay que subir un fichero al desplegar, y acordarse de refrescarlo. |
| Quien despliega lo sustituye por lo que tenga sin tocar el aplicativo. | `search()` es un recorrido en memoria sobre un array. |
| Desarrollo y tests funcionan sin red y sin datos reales. | Un JSON malformado deja el catálogo vacío en silencio. |

## Decisión

**El catálogo de centros lo contesta el filtro `prc_centres` y no se
versiona.**

### El contrato

`Domain/CentreCatalog` expone tres funciones y nada más:

| Función | Devuelve |
|---|---|
| `all()` | `array` de `{ code: string, name: string, ownership: 'public'\|'private' }`, resultado de `apply_filters( 'prc_centres', $default )` |
| `find( $code )` | La fila cuyo `code` coincide, o `null` |
| `search( $q )` | Las filas cuyo `code` o `name` contienen `$q`, para el buscador del escritorio |

`ownership` es una lista cerrada de dos valores y es el único dato del centro
que decide algo: se cruza con `prc_ownership` del procedimiento para saber si
el centro puede solicitar. Lo que no esté en esos tres campos no lo lee el
aplicativo.

### El valor por defecto

Si nadie contesta el filtro, `all()` lee
`wp-content/uploads/prc/centres.json` —un array JSON con esa misma forma— y,
si el fichero no existe o no se puede decodificar, devuelve **una lista
vacía**. El fichero lo sube quien despliega, fuera del bundle, y lo refresca
cuando su origen cambie. El repositorio lleva la forma del JSON documentada y
un ejemplo de dos centros inventados; no lleva el catálogo.

### En desarrollo

`scripts/mu-plugins/prc-dev-tools.php` contesta `prc_centres` con **una
docena de centros inventados**, públicos y privados, con nombres que no
existen. `seed-demo.php` da a `direccion` y `direccion2` dos de esos códigos.
Los tests usan ese mismo catálogo o uno propio inyectado por el filtro.

### Lo que no bloquea

Un código de centro que **no está** en el catálogo solicita igual: la
solicitud guarda el código y un nombre vacío, y quien gestiona lo ve marcado.
El catálogo puede ir por detrás de la realidad y el aplicativo no convierte
ese retraso en un centro que no puede participar. La foto del nombre en la
solicitud es de la
[ADR-0018](ADR-0018-la-solicitud-es-un-contenido-del-procedimiento.md).

## Consecuencias

### Positivas

- El repositorio se publica sin un solo nombre de centro real.
- Quien despliega elige la fuente: un fichero, un plugin, su directorio. El
  aplicativo no cambia.
- El entorno de desarrollo y los tests no dependen de la red ni de datos
  personales de nadie.
- El aplicativo lee tres campos, así que el catálogo puede tener cuarenta
  columnas o tres sin que importe.

### Negativas

- **Subir el fichero es un paso manual del despliegue**, y refrescarlo,
  otro. Nadie avisa de que el catálogo tiene un año. El aplicativo tampoco:
  no comprueba fechas ni cuenta filas.
- **Un JSON roto es un catálogo vacío en silencio.** Con la lista vacía todo
  centro es «desconocido» y toda solicitud se guarda sin nombre. La pantalla
  de diagnóstico de `Admin/Settings` debe enseñar cuántos centros hay
  cargados; sin eso, el fallo se descubre por una solicitud sin nombre.
- **`search()` recorre el array entero.** Con 1.700 filas no se nota; no está
  medido con más, y no hay índice.
- **El aplicativo no valida el catálogo.** Un `ownership` fuera de la lista
  cerrada se trata como desconocido y el centro no puede solicitar en ningún
  procedimiento con `prc_ownership` restringido.

### Neutras

- Cómo se identifica a la persona con su centro no cambia: es la meta de la
  [ADR-0016](ADR-0016-el-centro-de-la-persona-es-una-meta-configurable.md).
- Esta ADR no dice de dónde saca quien despliega su catálogo ni con qué
  periodicidad lo refresca. Es suyo.
