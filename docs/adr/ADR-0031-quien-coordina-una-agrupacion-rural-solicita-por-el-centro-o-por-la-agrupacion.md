---
id: ADR-0031
title: "Quien coordina una agrupación de escuelas rurales solicita por su centro o por la agrupación"
status: Propuesta
date: 2026-10-14
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0016, ADR-0017, ADR-0018]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5-5"
---

# ADR-0031: Quien coordina una agrupación de escuelas rurales solicita por su centro o por la agrupación

## Estado

Propuesta (2026-10-14). Resuelve el pendiente «un centro por persona en la
fase 1» de la [ADR-0016](ADR-0016-el-centro-de-la-persona-es-una-meta-configurable.md)
sin cambiar lo que decidió: el centro sigue saliendo de la cuenta y nunca del
navegador.

## Contexto

Hay personas que dirigen un centro y además coordinan una agrupación de
escuelas rurales. El directorio corporativo las pone en el equipo directivo de
los dos, y el fragmento de inicio de sesión del sitio guarda los dos códigos
en dos metas de usuario: el de su centro y el de la agrupación.

En el sitio de hoy, la mayoría de las convocatorias solo leen el código de
centro. Quien quiere inscribir a la agrupación no puede: inscribe a su centro
y escribe una incidencia pidiendo que se cambie a mano. Solo una convocatoria
que se arregló a medida lee el segundo código. Son pocas personas, pero el
caso se repite cada vez que se abre una convocatoria que también admite
agrupaciones.

Hay además un segundo caso: quien coordina una agrupación **a tiempo
completo** no dirige ningún centro, y el inicio de sesión le pone la
agrupación como código de centro, sin segunda meta.

Al aplicativo le faltaban dos cosas:

1. Saber que una persona tiene un segundo código y que es de una agrupación.
2. Saber si un procedimiento admite centros, agrupaciones como colectivo o las
   dos cosas. Hoy eso solo lo dice el texto de la convocatoria, y quien la
   escribe no siempre lo deja claro.

## Problema

¿Cómo solicita por la agrupación quien tiene los dos códigos, sin teclear el
código y sin que tenga que decidir cuando el procedimiento solo admite uno?

## Factores de decisión

- **Frontera de confianza** (ADR-0016): el código no puede venir del
  formulario. Elegir entre los códigos de la cuenta sí puede.
- **Lo mínimo para quien solicita**: si solo vale un código, no se pregunta.
- **Que quien crea el procedimiento lo diga claro**, y que lo diga una vez.
- **Una solicitud por código y procedimiento** (ADR-0018): el centro y la
  agrupación no comparten solicitud.
- **Publicable** (ADR-0009): la clave de la meta del sitio de destino no se
  escribe aquí.

## Alternativas consideradas

### Opción 1: dejar editable el código en el formulario

- A favor: nada que modelar.
- En contra: es justo lo que la ADR-0016 descartó. Quien edite el HTML
  solicita por cualquier centro. Descartada.

### Opción 2: que `code_for()` devuelva una lista

- A favor: es la escotilla que dejó escrita la ADR-0016.
- En contra: hay más de diez llamadas que esperan un código y que tendrían
  que cambiar todas, y la mayoría (ficha de usuario, diagnóstico, cabeceras)
  solo quiere el centro. Descartada.

### Opción 3: segunda meta y una lista cerrada en el procedimiento (elegida)

`code_for()` sigue igual. Al lado, una segunda meta con clave configurable y
`codes_for()`, que devuelve los dos códigos con lo que es cada uno. El
procedimiento guarda en `prc_centre_kinds` qué clases admite. Lo que antes
preguntaba «¿puede solicitar?» pregunta ahora «¿con qué códigos?».

## Decisión

**Una persona puede tener dos códigos, y el procedimiento dice con cuáles se
solicita.**

- **La persona.** `CentreScope::group_code_for()` lee la meta cuya clave
  devuelve el filtro `prc_rural_group_code_meta_key`, por defecto
  `prc_rural_group_code`. En producción, un fragmento privado contesta con la
  clave que ya escribe el inicio de sesión. `CentreScope::codes_for()` devuelve
  `código => clase`: primero el centro y luego la agrupación, sin repetir. El
  código de la segunda meta es siempre una agrupación. El del centro es lo
  que diga el catálogo en su campo `kind`, y si no dice nada es un centro;
  así entra quien coordina a tiempo completo.
- **El procedimiento.** Meta `prc_centre_kinds`, una lista cerrada de
  `ProcedureMetaKeys::centre_kinds()`: `school` (cada centro con su código) y
  `rural_group` (agrupaciones de escuelas rurales como colectivo). El taller
  la pide al lado de la titularidad y es obligatoria. **Un procedimiento nuevo
  empieza solo con centros**, porque admitir agrupaciones es una decisión y no
  un descuido. Uno sin la meta, anterior a esta ADR, las admite todas, como
  hasta ahora.
- **El guardián.** `ProcedureAccess::apply_codes()` devuelve los códigos de la
  persona que el procedimiento admite por clase y por titularidad.
  `can_apply( $user, $post, $code )` acepta un código solo si está en esa
  lista. `can_view_application()` deja ver la solicitud de cualquiera de los
  códigos de la persona.
- **La pantalla.** Con un solo código admitido se solicita con él sin
  preguntar. Con dos, la pantalla de solicitud pinta «Solicita en nombre de»
  con un enlace por código (`?centro=`) y se trabaja con el primero mientras
  no se elija otro. El POST lleva el código elegido en `prc_apply_centre`, y
  un código que no es de la cuenta o que el procedimiento no admite no guarda
  nada. Cada código tiene su propia solicitud. «Mi centro», la portada y la
  ficha cuentan las solicitudes de los dos códigos.

## Consecuencias

### Positivas

- Quien coordina una agrupación la inscribe sin pedir que nadie cambie nada a
  mano, y no ve ninguna pregunta si el procedimiento no le deja elegir.
- Quien crea el procedimiento tiene que decir a quién admite, con una casilla
  en vez de con una frase en la descripción.
- `code_for()` y las pantallas que solo quieren el centro no cambian.

### Negativas

- Que el código del centro sea una agrupación depende de que el catálogo
  traiga `kind`. Si no lo trae, quien coordina a tiempo completo cuenta como
  centro: no puede solicitar a un procedimiento que solo admite agrupaciones,
  y sí a uno que solo admite centros. La sincronización del catálogo
  (ADR-0029) todavía no guarda `kind`; hasta entonces lo tiene que añadir el
  fragmento que contesta `prc_centres`.
- La ficha de usuario todavía no tiene un campo para corregir a mano el código
  de la agrupación. Hoy lo corrige quien tenga acceso a las metas.
- La ficha pública no dice todavía si el procedimiento admite agrupaciones:
  eso solo se ve en el taller y, para quien tiene los dos códigos, en la
  pantalla de solicitud.

### Neutras

- `Applications::for_centre()` acepta uno o varios códigos (`meta_compare
  IN`), con la misma consulta de siempre.
