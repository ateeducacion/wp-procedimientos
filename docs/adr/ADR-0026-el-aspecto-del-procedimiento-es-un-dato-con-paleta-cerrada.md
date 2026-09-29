---
id: ADR-0026
title: "El aspecto del procedimiento es un dato con paleta cerrada"
status: Propuesta
date: 2026-09-16
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0003, ADR-0006, ADR-0009, ADR-0022]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5"
---

# ADR-0026: El aspecto del procedimiento es un dato con paleta cerrada

## Estado

Propuesta (2026-09-16). Añade `prc_header_color` al modelo de la
[SDD-0001](../sdd/SDD-0001-arquitectura-del-aplicativo-de-procedimientos.md),
que no tenía ningún dato de aspecto.

## Contexto

En el sistema que se sustituye, cada convocatoria se ve en el listado con una
banda de color propia, y ese color lo elige quien la da de alta entre unas
muestras de la pantalla de alta. Es lo que distingue a ojo un programa de otro
en una rejilla de decenas de tarjetas, y la persona responsable pidió que la
visualización fuese **calcada**.

El color no es decoración añadida por la plantilla: viaja con la convocatoria.
Dos convocatorias del mismo ámbito tienen colores distintos, y la misma
convocatoria conserva el suyo de un curso a otro. El modelo escrito el
2026-09-15 no lo recogía en ninguna parte.

Hay una lección en cómo está guardado hoy: es **texto libre**. Sobre la foto
del 2026-09-15 (en `.local/`, que no se versiona) se leen valores escritos a
mano, con mayúsculas y minúsculas mezcladas, alguno vacío y alguno que no es un
color; y hay bandas claras con el rótulo en blanco encima, que no se leen. Es
la misma historia que las demás listas abiertas de ese sistema.

Esto no es lo mismo que la marca del despliegue. El escudo, el pie, los enlaces
legales y la hoja de estilo de la organización que despliega **no se calcan** y
no se versionan: son configuración, y entran por el filtro `prc_chrome`
([ADR-0009](ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md)). Lo que
se discute aquí es un dato de la convocatoria, como sus fechas.

## Problema

¿Dónde vive el color de la cabecera de un procedimiento, y quién decide qué
colores son posibles?

## Factores de decisión

- **El calco lo exige**: sin el color, ni el listado ni la ficha se parecen.
- **Accesibilidad**: encima de la banda va el título, y tiene que leerse. No es
  negociable ni se comprueba a ojo.
- **La lección de las listas abiertas**: lo que se puede escribir a mano acaba
  escrito de veinte maneras.
- **Sin dependencias nuevas** ([ADR-0006](ADR-0006-librerias-de-terceros-desde-cdn-con-sri.md)):
  ni una librería de color ni un selector de terceros.
- **Nada de la organización en el repositorio**
  ([ADR-0009](ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md)): los
  colores no pueden ser los de una marca concreta ni llamarse como sus áreas.

## Alternativas consideradas

### Opción 1: Un selector de color, texto libre

`<input type="color">` y la meta guarda el hexadecimal que salga.

| | |
|---|---|
| A favor | Nativo, cero código, y quien gestiona elige exactamente lo que quiere. |
| En contra | Reproduce el defecto que se viene a corregir: cualquiera puede elegir un amarillo claro y dejar el título ilegible, y nadie se entera hasta que alguien no puede leer la portada. Validar el contraste de un color arbitrario obliga a escribir la comprobación igualmente, y entonces la lista cerrada sale más barata. |

### Opción 2: El color es una taxonomía

Un término por color, editable desde el escritorio.

| | |
|---|---|
| A favor | Se cambia sin tocar código. |
| En contra | Una taxonomía es una dimensión de clasificación, y esto no clasifica nada ([ADR-0012](ADR-0012-una-taxonomia-por-dimension.md)): nadie va a filtrar «los procedimientos morados». Y devuelve la puerta de la opción 1 por otro sitio, porque el hexadecimal del término lo teclearía una persona. |

### Opción 3: Lista cerrada en código

Una meta `prc_header_color` que guarda una **clave** de una paleta declarada en
`Meta\ProcedureMetaKeys::header_colors()`, con su etiqueta en castellano y su
hexadecimal.

| | |
|---|---|
| A favor | El contraste se decide una vez, se documenta y se prueba. Lo que se guarda es una clave corta, no una cadena de color. Sin color elegido hay un neutro, no un hueco. |
| En contra | Cambiar la paleta es tocar código y volver a desplegar el snippet: quien gestiona no puede añadir un color el día que lo necesita. |

## Decisión

Haremos la opción 3. Meta nueva `prc_header_color`, de tipo cadena, registrada
con su `sanitize_callback` y su `auth_callback` como todas las demás, que
guarda una clave de `Meta\ProcedureMetaKeys::header_colors()`.

La paleta tiene diez colores con nombre de color —ni marcas, ni áreas, ni
programas ([ADR-0003](ADR-0003-identificadores-internos-en-ingles.md))— y un
criterio escrito en su docblock: **todos** alcanzan un contraste de 4,5:1 con
el blanco (WCAG 2.1 AA para texto normal) y no lo alcanzan con el negro, así
que el rótulo de encima es siempre blanco y no hay que decidirlo color a color.
El más flojo contra el blanco llega a 5,93:1. Un color nuevo entra en la lista
solo si pasa ese mismo umbral, y hay un test que lo calcula.

Quien no elija color se queda con el neutro: ni la validación ni el saneado
dejan que la meta guarde una clave que no esté en la lista.

## Consecuencias

### Positivas

- El listado y la ficha se calcan, con las bandas que la gente ya reconoce.
- Ninguna banda puede quedar ilegible: lo impide la lista, no la buena
  voluntad de quien da de alta.
- El dato es una clave corta y estable: se puede cambiar el hexadecimal de
  «verde» sin tocar ni una convocatoria.

### Negativas

- **Diez colores para un número indefinido de programas.** En una rejilla
  grande habrá dos tarjetas del mismo color, y la banda dejará de identificar
  unívocamente a nadie. Es una pista, no una etiqueta, y quien venga del
  sistema anterior con un color propio que no esté en la paleta se quedará sin
  él.
- **Añadir un color es un despliegue.** Quien gestiona no puede resolverlo
  solo, y lo normal es que lo pida justo el día del alta.
- El criterio «siempre blanco encima» cierra la puerta a los colores claros,
  que son la mitad del espectro: no habrá pasteles, y alguien echará de menos
  el suyo.
- Aparece un dato de aspecto en un modelo que hasta ahora solo tenía datos de
  procedimiento. La raya con la marca del despliegue —que sigue en `prc_chrome`
  y sigue sin versionarse— hay que explicarla cada vez que alguien proponga
  meter aquí el segundo.

### Neutras

- El color no participa en ninguna regla: ni en el estado, ni en los permisos,
  ni en los filtros. Solo se pinta.
- No hay nada que migrar ([ADR-0024](ADR-0024-las-solicitudes-de-hoy-no-se-migran.md)).
