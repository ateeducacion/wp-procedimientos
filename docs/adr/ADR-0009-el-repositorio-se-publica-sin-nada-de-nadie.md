---
id: ADR-0009
title: "El repositorio se publica como software libre y no lleva dentro nada de nadie"
status: Propuesta
date: 2026-09-15
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0001, ADR-0010, ADR-0016, ADR-0017, ADR-0022]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-fable-5-1"
---

# ADR-0009: El repositorio se publica sin nada de nadie

## Estado

Propuesta (2026-09-15). Heredada del aplicativo de eventos
([ADR-0010](ADR-0010-se-parte-del-esqueleto-del-aplicativo-de-eventos.md)),
con una diferencia: allí la regla llegó después de escribir dando por hecho lo
contrario, y hubo que limpiar; aquí está puesta **desde el primer fichero**.
`scripts/check-public.mjs` corre en cada `make check` y, a fecha de hoy, no
encuentra ninguna aparición. Esta ADR añade una regla que la de eventos no
tenía: **el catálogo de centros y el nombre de las metas del sitio de destino
tampoco se versionan.**

## Contexto

Este repositorio va a publicarse en abierto, como software libre, igual que el
aplicativo de eventos del que parte. Lo que no puede llevar dentro son cuatro
cosas distintas, y conviene separarlas porque no tienen la misma gravedad ni
el mismo remedio.

**Uno: infraestructura de un despliegue concreto.** Las direcciones del
servidor de analítica, del aviso de cookies y de los servicios de una
organización, con sus identificadores. Publicarlas es publicar a qué servidor
van las visitas de la gente.

**Dos: la marca de esa organización.** Cabecera, pie, escudo y enlaces
legales. Un aplicativo libre que lleva dentro el escudo de alguien no lo puede
usar nadie más sin borrarlo.

**Tres: el mapa del sistema que se sustituye.** Aquí pesa más que en eventos,
porque el sistema de hoy es más grande: un formulario de alta de más de cien
campos, otro de solicitud de casi cien, centenares de vistas del gestor de
formularios y más de un centenar de fragmentos de código del sitio. La
investigación que lo documenta —inventarios, números de campo, de vista y de
fragmento, cuentas por rol, fallos de seguridad encontrados— es lo que hizo
posible diseñar el reemplazo, y es exactamente lo que no se enseña de una
instalación ajena. Vive en `.local/` y no se versiona.

**Cuatro, y es lo nuevo: datos del despliegue que parecen datos del
aplicativo.** Dos cosas que en eventos no existían:

- **El catálogo de centros.** Más de mil seiscientos centros con su código,
  su nombre y su titularidad. Son datos abiertos de una administración, pero
  versionarlos aquí dice dónde se despliega el aplicativo, convierte este
  repositorio en el sitio donde se mantiene un catálogo que no es suyo, y
  obliga a quien lo instale en otro sitio a borrar mil seiscientas filas.
- **El nombre de las metas de usuario del sitio de destino.** Hoy el código
  de centro de la persona lo escribe, al iniciar sesión, un fragmento del
  sitio que consulta el directorio corporativo, en una meta con un nombre
  que eligió quien lo escribió. Nombrar esa clave en el código es describir
  cómo está integrado un sitio con su directorio. Lo mismo vale para el
  vocabulario de ámbitos: el árbol real es el organigrama de alguien.

## Problema

¿Qué puede estar en un repositorio que se publica, y qué no?

## Factores de decisión

- **Lo que se publica no se puede despublicar.** Un repositorio en abierto se
  clona, se indexa y se archiva; borrar algo después no lo retira.
- **El aplicativo tiene que servirle a otro.** Si para instalarlo hay que
  borrar la marca, el catálogo o el organigrama de alguien, no es software
  libre: es el software de alguien con la licencia puesta encima.
- **La razón de una decisión vale más que el dato que la sostiene.** Se puede
  decir *por qué* se hizo algo sin enseñar el inventario de quien lo sufría.
- **Los datos de un despliegue cambian a su ritmo, no al del código.** Un
  centro se abre o se cierra sin que nadie despliegue nada; el catálogo no
  puede depender de un `make bundle`.
- **Una regla que no se comprueba se rompe.** Esto se arregla y se vigila.

## Decisión

### 1. El código no lleva dentro nada de ninguna organización

Todo lo que identifica un despliegue —dueño del sitio, rótulo, enlaces
legales, aviso de cookies, analítica con su identificador— sale de **un solo
sitio configurable**, `View/ProcedureChrome::chrome()`, y **está vacío por
defecto**. Lo que no se configura, no se pinta: una instalación recién hecha
no enseña el nombre de nadie, no carga ningún aviso y no envía ni una visita a
ningún servidor. Quien despliega lo rellena desde fuera con el filtro
`prc_chrome`, en un fragmento suelto que no está en este repositorio. En
desarrollo lo rellena el mu-plugin de `scripts/mu-plugins/`, con valores de
`example.org` y sin analítica.

### 2. Dónde se despliega no se versiona

El sitio y el subsitio de destino viven en el `.env`, que no se sube.
`.env.dist` los trae **vacíos**, no de ejemplo: un valor de ejemplo que es el
real acaba copiado.

### 3. La investigación del sistema anterior vive en `.local/`

`.local/` está en el `.gitignore`. Ahí va **todo lo que describe la
instalación que se sustituye**: su arquitectura, sus inventarios y el detalle
de sus formularios, vistas, campos y fragmentos de código. Las decisiones no
se pierden: lo que se queda en el repositorio es el porqué, y lo que se va es
el mapa. Donde una ADR se apoya en una medición, se cita como lo que es —algo
comprobado sobre material que no se publica— y la cifra se da redondeada.

### 4. Los datos del despliegue los contesta un filtro, no un fichero del repo

- **El catálogo de centros** lo devuelve el filtro `prc_centres`
  (`Domain/CentreCatalog::all()`); por defecto se lee de
  `wp-content/uploads/prc/centres.json`, que **no está en el repositorio**, y
  si no existe, la lista está vacía. El mu-plugin de desarrollo contesta con
  una docena de centros **inventados**
  ([ADR-0017](ADR-0017-el-catalogo-de-centros-no-se-versiona.md)).
- **La clave de la meta del código de centro** la devuelve el filtro
  `prc_centre_code_meta_key`, con un valor por defecto propio,
  `prc_centre_code`. El nombre que usa el sitio de destino se escribe en el
  fragmento privado que contesta el filtro, nunca aquí
  ([ADR-0016](ADR-0016-el-centro-de-la-persona-es-una-meta-configurable.md)).
- **El vocabulario de ámbitos y cursos** que siembra `scripts/setup-vocabulary.php`
  es de demostración, con nombres inventados. El árbol real lo crea cada
  instalación desde el escritorio.

### 5. Se comprueba en cada `make check`

`scripts/check-public.mjs` recorre **lo que git versionaría** —se lo pregunta
a git, así que respeta el `.gitignore`— y falla si encuentra infraestructura
de un despliegue, la marca de una organización, el nombre de la red de
destino, detalle interno del sistema anterior, nombres de otros repositorios
del equipo, cifras de plantilla o rutas locales de quien desarrolla. Cada
regla dice qué hacer, no solo que está mal.

La lista de lo que busca no puede estar en el propio script: escribir el
nombre de la organización o de un repositorio privado para buscarlo es
publicarlo. Por eso el script solo trae las reglas que no delatan nada, y las
demás las lee de `.check-public-rules` —en el `.gitignore`— o, en CI, del
secret `PRC_PUBLIC_RULES`. Secret y no variable: las variables de Actions no
se enmascaran en los registros, que en un repositorio público son públicos, y
un PR desde un fork no recibe los secrets. El precio es que ese PR solo pasa
las reglas genéricas, y que quien no tenga el fichero ve un aviso en vez de la
comprobación completa.

## Alternativas consideradas

### Opción 1: publicar tal cual y confiar en que nadie lo lea

- Contras: convierte una investigación interna —que aquí incluye fallos de
  seguridad de un sitio en marcha— en un mapa público. Descartada sin
  matices.

### Opción 2: limpiar una vez, antes del primer commit

- Pros: es el trabajo mínimo y el momento adecuado; este repositorio está
  justo ahí.
- Contras: **no se sostiene sola.** La siguiente ADR volverá a citar el
  sistema anterior, porque es de donde sale el problema; y el primer
  `centres.json` real que alguien deje en la raíz para probar acabará en un
  commit. Sin comprobación automática, la limpieza dura hasta el siguiente
  fichero.

### Opción 3: versionar el catálogo de centros, que es dato público

- Pros: `make up` levanta el sitio con centros reales y la búsqueda se prueba
  contra nombres de verdad.
- Contras: dice dónde se despliega; hace del repositorio el sitio donde se
  mantiene un catálogo ajeno, con un commit por cada centro que se abre; y
  obliga a quien instale en otro sitio a borrarlo. Que un dato sea público no
  lo convierte en parte del aplicativo. Descartada.

### Opción 4: limpiar, sacar los datos del despliegue a filtros y comprobar en cada `make check` — ELEGIDA

- Pros: la regla se mantiene sola y quien la rompe se entera antes de subir
  nada, con el motivo escrito. El aplicativo funciona en cualquier sitio con
  un fragmento de configuración y un fichero JSON.
- Contras: una lista de expresiones es aproximada; habrá falsos positivos y
  se escapará lo que no esté en la lista. Es una red, no una garantía.

## Consecuencias

### Positivas

- **El aplicativo se instala en otro sitio** sin borrar nada de nadie: un
  filtro para el armazón, otro para el catálogo, otro para la clave de la
  meta, y un vocabulario que cada instalación crea.
- **Una instalación nueva no envía datos a ningún sitio** sin que alguien lo
  configure a propósito.
- La documentación mejora al dejar de apoyarse en identificadores internos:
  obliga a escribir el argumento en vez de señalar el número de un campo.
- El catálogo se actualiza a su ritmo —sustituir un JSON— sin desplegar el
  bundle.

### Negativas

- **Se pierde trazabilidad.** Quien no tenga `.local/` tendrá que fiarse de
  que la medición se hizo. Es el precio de publicar.
- **La red aprieta desde el primer día.** Escribir sobre lo que se sustituye
  obliga a redactar en genérico —«el gestor de formularios», «un fragmento
  del sitio»— y eso cuesta más que pegar un identificador.
- **El entorno local no se parece al real en los datos.** Una docena de
  centros inventados no ejercen la búsqueda como mil seiscientos reales, y el
  árbol de ámbitos de demostración no tiene la profundidad del verdadero. Lo
  que falle por volumen o por profundidad se verá en el sitio de destino, no
  en `make up`.
- **Hay dos ficheros de configuración privados que mantener fuera** —el
  fragmento del `prc_chrome` y el del `prc_centre_code_meta_key`, más el
  `centres.json`— y ninguno está bajo el control de este repositorio ni de su
  CI.

### Neutras

- La comprobación es de expresiones regulares y se le escapará lo que no
  esté en la lista. Cada vez que se encuentre algo nuevo, se añade una regla.
- El mu-plugin de desarrollo rellena el armazón y el catálogo para que el
  wp-env se vea completo. Nunca se despliega, y sus valores son inventados.
