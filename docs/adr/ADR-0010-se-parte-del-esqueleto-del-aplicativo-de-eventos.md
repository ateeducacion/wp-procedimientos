---
id: ADR-0010
title: "Se parte del esqueleto del aplicativo de eventos"
status: Propuesta
date: 2026-09-15
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0001, ADR-0002, ADR-0003, ADR-0004, ADR-0009, ADR-0011, ADR-0014, ADR-0022]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-fable-5-1"
---

# ADR-0010: Se parte del esqueleto del aplicativo de eventos

## Estado

Propuesta (2026-09-15). El esqueleto está copiado y renombrado; el dominio
está por escribir.

## Contexto

El equipo ya tiene un aplicativo con la misma forma que este necesita: el de
eventos, público, que se desarrolla en un repositorio con wp-env y se
despliega como un único Code Snippet sobre un WordPress que no se controla
([ADR-0001](ADR-0001-repo-entorno-desarrollo-no-plugin.md),
[ADR-0002](ADR-0002-sincronizacion-snippets-eval-file.md)). Ese aplicativo
sustituyó un sistema montado sobre el mismo gestor de formularios y el mismo
constructor de páginas que hoy sostienen las convocatorias, con los mismos
síntomas: campos de texto como plantillas, fragmentos de código pegados a mano
y un estado que alguien tiene que acordarse de cambiar.

Las dos aplicaciones comparten casi todo lo que no es dominio:

| Pieza | Qué es | ¿Depende del dominio? |
|---|---|---|
| `Makefile`, `composer.json`, `package.json`, `.wp-env.json`, `.env.dist` | El entorno: levantar, probar, lint, empaquetar | No: solo el prefijo y los puertos |
| `build/pack-snippet.php` | El empaquetador que inlinea `src/` en un snippet en el orden de `load-order.php` | No |
| `.github/` | CI: lint, PHPMD, PHPUnit, comprobaciones de provisión y de publicación | No |
| `scripts/check-public.mjs`, `check-provision.mjs`, `snippet-check.php`, `sync-snippets.php` | Comprobaciones y sincronización | No |
| `.agents/skills`, `.claude/skills` | Skills de desarrollo (WP-CLI, seguridad, Playground…) | No |
| `docs/*/README.md`, plantillas de ADR y SDD | La política documental | No |
| `PublicFront/Assets`, `ExitSignal`, `Shell`, `EditLock` | El armazón del frontal: Bootstrap con SRI, páginas por shortcode pintadas en `template_redirect`, bloqueo de edición | No |
| `Domain/DateRange` | Rango de fechas por día, puro | No |
| El guardián de permisos | Una sola clase, `map_meta_cap` + `pre_get_posts`, acotado por término de una taxonomía en una meta de usuario, fail-closed | El **patrón** no; los nombres sí |
| El snippet de roles y perfiles | Aditivo, nunca quita capacidades, campos de perfil editables solo por administración | El patrón no; los roles sí |
| El mu-plugin de desarrollo | Cambio de cuenta desde la barra, reescritura de CDN a `node_modules`, armazón de ejemplo | Casi no |
| La política de idiomas | Identificadores en inglés, lo que lee una persona en castellano ([ADR-0003](ADR-0003-identificadores-internos-en-ingles.md)) | No |

Y no comparten el dominio. Lo que en eventos es un evento con páginas
satélite jerárquicas, ponentes, actividades, talleres con aforo, CSS a
medida por evento e inscripciones con elección de taller, aquí es un
procedimiento **plano** con fechas, un plazo de subsanación, enlaces a la
resolución y a los listados, preguntas configurables, y una solicitud por
centro que pasa por cuatro estados de revisión. Ninguna de las decisiones de
modelo de eventos —secciones, ponentes, aforo, copia de ponentes, duplicado de
eventos, tipo de página al crear— tiene equivalente aquí.

## Problema

¿De dónde se parte: de cero, de una librería compartida con eventos, o de una
copia del esqueleto de eventos con el dominio vaciado?

## Factores de decisión

- **Lo que ya funciona no hay que volver a decidirlo.** Las ocho primeras ADR
  de eventos —repositorio, sincronización, idiomas, CI, edición, CDN,
  papelera, bloqueo— valen aquí palabra por palabra.
- **Un solo Code Snippet por aplicativo.** Es la restricción de despliegue
  ([ADR-0002](ADR-0002-sincronizacion-snippets-eval-file.md)): lo que se
  comparta tiene que acabar inlinado en cada bundle, no cargado desde fuera.
- **Los dos aplicativos viven en sitios distintos y evolucionan a ritmos
  distintos.** Un cambio en el armazón de eventos no tiene por qué llegar
  aquí el mismo día, ni al revés.
- **Lo compartible es infraestructura, no dominio.** Un `Shell` o un guardián
  genéricos, parametrizados para dos dominios, son una abstracción que nadie
  ha pedido.
- **El coste de copiar es un renombrado; el coste de una librería es un
  tercer repositorio.**

## Alternativas consideradas

### Opción 1: empezar de cero

- Pros: nada heredado que no se entienda; el repositorio solo tiene lo que
  necesita.
- Contras: se reescriben el empaquetador, la CI, las comprobaciones y el
  armazón del frontal, y se vuelven a tomar ocho decisiones ya tomadas, con
  el riesgo de tomarlas distinto sin motivo. Descartada.

### Opción 2: una librería compartida (paquete de Composer o tercer repositorio)

El armazón, el guardián y las comprobaciones en un paquete que ambos
aplicativos requieren.

- Pros: un arreglo en el armazón llega a los dos.
- Contras: **el despliegue es un snippet**, así que la librería habría que
  inlinarla en cada bundle igualmente; hace falta versionarla y publicarla; y
  generalizar `Shell`, `EventAccess` y el snippet de roles para dos dominios
  que solo comparten el patrón añade parámetros, interfaces y un tercer sitio
  donde mirar. Los dos aplicativos tendrían que actualizar a la vez o
  mantener dos versiones. Descartada: el ahorro es menor que el coste.

### Opción 3: un monorepositorio con los dos aplicativos

- Pros: un solo `Makefile`, una CI, un armazón.
- Contras: dos sitios de destino, dos bundles, dos ciclos de publicación y
  dos historias mezcladas en un repositorio público; el aplicativo de eventos
  ya está publicado con su URL y su historia. Descartada.

### Opción 4: clonar el esqueleto y renombrar (elegida)

Se copian los ficheros de la tabla del contexto con los identificadores
renombrados —el prefijo, el namespace, las constantes, los puertos y el nombre del
repositorio— y se escribe el dominio de nuevo.

- Pros: el primer día hay entorno, CI, comprobaciones y frontal. Lo copiado
  es exactamente lo que no depende del dominio.
- Contras: dos copias del mismo armazón que divergen desde el primer commit.

## Decisión

**Se parte del esqueleto del aplicativo de eventos, copiado y renombrado, y
el dominio se escribe de nuevo. No se comparte código entre los dos
repositorios.**

Qué se copia tal cual (con el renombrado): todo lo de la tabla del contexto.
Qué se copia como **patrón** y se reescribe con los nombres de este dominio:
el guardián de permisos (`Access/ProcedureAccess`, con la meta de usuario
`prc_area` y las capacidades `prc_*`), el snippet de roles y perfiles, la
registración de tipos de contenido, taxonomías y metas, y las pantallas
pintadas enteras
([ADR-0022](ADR-0022-las-pantallas-son-paginas-pintadas-enteras.md)).

Qué **no** se trae, porque no existe aquí: páginas satélite y tipos de
sección, ponentes, actividades, talleres y aforo, CSS y JavaScript a medida
por evento, la regla de no duplicar eventos, la de no copiar ponentes y la
migración de eventos históricos.

Qué se decide de nuevo, y lleva ADR propia: todo lo que va de
[ADR-0011](ADR-0011-el-procedimiento-es-un-tipo-de-contenido.md) a
ADR-0024. Las ocho primeras ADR de este repositorio reproducen las decisiones
de infraestructura de eventos con su propio número: **los números de ADR de
los dos repositorios no coinciden** y no se citan cruzados.

Cómo se mantiene la copia: cuando eventos corrija algo del esqueleto, se
porta a mano, con un diff de los ficheros renombrados. No hay automatismo, y
no se pretende que lo haya.

## Consecuencias

### Positivas

- El repositorio nace con `make check` en verde, con CI, con la comprobación
  de publicación ([ADR-0009](ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md))
  y con un frontal que ya se sabe cómo se ve.
- Quien conoce el aplicativo de eventos conoce este: mismo `Makefile`, misma
  anatomía de `src/`, mismo empaquetador, mismas convenciones.
- El dominio se escribe sin arrastrar nada de eventos: no hay ponentes que
  esconder ni secciones que ignorar.

### Negativas

- **Las dos copias divergen.** Un arreglo en `Shell` o en el empaquetador de
  eventos no llega aquí hasta que alguien lo porte, y nadie tiene esa tarea
  asignada. Con el tiempo, los dos armazones serán parecidos pero no iguales.
- **El renombrado es ruido en la historia.** Ficheros enteros cuya única
  diferencia con eventos son tres letras, sin que el diff lo diga.
- **Se hereda lo que eventos hizo mal** sin volver a mirarlo: lo que en su
  armazón sea discutible viene aquí igual, y se corregirá en dos sitios.
- **La numeración de ADR confunde a quien lea los dos repositorios.** «La
  ADR-0006» es el ámbito en eventos y el CDN aquí. Cada repositorio cita solo
  las suyas.

### Neutras

- La política de idiomas, la de edición y la de pruebas son las mismas y no
  se vuelven a discutir: sus ADR aquí son las de eventos con el prefijo
  cambiado.
- Los skills de `.agents/skills/` son los mismos ficheros. Si eventos
  actualiza uno, se copia.
- Nada de esto obliga a que los dos aplicativos se parezcan en pantalla más
  allá de compartir Bootstrap y el armazón; cada dominio pinta lo suyo.
