---
id: ADR-0041
title: "Las personas de la solicitud son una lista que gestiona el centro, con su propia ventana"
status: Propuesta
date: 2026-11-10
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0005, ADR-0009, ADR-0018, ADR-0019, ADR-0020, ADR-0023, ADR-0037, ADR-0038, ADR-0039, ADR-0040]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5-5"
---

# ADR-0041: Las personas de la solicitud son una lista que gestiona el centro, con su propia ventana

## Estado

Propuesta. Amplía la
[ADR-0019](ADR-0019-la-solicitud-es-nucleo-fijo-mas-preguntas.md) y matiza la
[ADR-0020](ADR-0020-la-subsanacion-es-un-plazo-y-un-estado.md) y la
[ADR-0023](ADR-0023-el-estado-historico-cierra-la-edicion.md), que no se
reescriben: llevan una adenda que remite aquí.

## Contexto

La petición que más se repite de las áreas, en la revisión de un trimestre de
uso del sistema anterior que hizo el
[PLAN-0003](../plan/PLAN-0003-lo-que-falta-para-sustituir-al-sistema-anterior.md)
(material en `.local/`, ADR-0009): que **el centro gestione por sí mismo la
lista de personas que participan** —altas, cambios, bajas y sustituciones a
mitad de curso—, cada una con su papel y, en algunos procedimientos, su eje o
grupo. Hoy eso pasa por pedir que se reabra la solicitud, o por correo y una
hoja de cálculo aparte.

Lo que ya había:

- la solicitud es un núcleo fijo más preguntas (ADR-0019), y en el núcleo está
  la coordinación, `prc_coordinator`: de cero a diez personas con nombre,
  apellidos, DNI o NIE y correo, que se escriben **con el formulario de la
  solicitud** ([`ApplicationInput::validate()`](../../src/Prc/Domain/ApplicationInput.php));
- el centro solo edita su solicitud con el plazo abierto, o en subsanación si
  se le pidió (ADR-0020, [`ApplyForm::window()`](../../src/Prc/PublicFront/ApplyForm.php));
  y guardarla la devuelve a «presentada»
  ([`Applications::save()`](../../src/Prc/PublicFront/Applications.php));
- el histórico cierra toda escritura (ADR-0023);
- la ADR-0018 descartó guardar **las solicitudes** en una meta del
  procedimiento porque «dos centros solicitando a la vez se pisan: leer, añadir
  y escribir una meta no es atómico»;
- la ADR-0005 pide registrar quién cambió qué y cuándo, en meta, con tope, y
  no había ningún registro escrito todavía.

## Problema

¿Dónde vive la lista de personas de una solicitud, quién la escribe y cuándo,
para que el centro la mantenga a lo largo del curso sin reabrir la solicitud,
sin pisarse entre dos cuentas del mismo centro y dejando rastro de cada
cambio?

## Factores de decisión

- **La lista cambia cuando la solicitud ya no**: una sustitución llega en
  febrero, con el plazo cerrado hace meses.
- **Tocar la lista no es volver a solicitar**: no puede devolver la solicitud
  a «presentada» ni borrar la nota de quien revisa.
- **Dos cuentas del mismo centro** —dirección y jefatura de estudios— pueden
  trabajar a la vez.
- **Rendir cuentas**: quién dio de alta o de baja a quién, y cuándo (ADR-0005).
- **Datos personales**: el DNI de cada persona no debe ir más allá de quien
  puede ver la solicitud.
- **Se despliega pegando código**: nada de tablas propias (ADR-0001).
- **Lo sencillo sigue siendo sencillo**: un procedimiento que solo pide una
  persona coordinadora no tiene por qué cambiar.

## Alternativas consideradas

### Opción 1: ampliar `prc_coordinator`

Más campos y más personas en la coordinación de siempre.

- **Pro:** nada nuevo que aprender.
- **Contra:** la coordinación se escribe con el formulario de la solicitud,
  así que hereda su plazo y su vuelta a «presentada»: es justo lo que se pide
  quitar. Y guardar el formulario reescribe la lista entera: dos cuentas a la
  vez se pisan.

### Opción 2: un tipo de contenido por persona (`prc_person`), hijo de la solicitud

- **Pro:** una fila por persona en la base de datos; dos altas a la vez no se
  pisan, porque cada una es un `INSERT`; papelera, autor y fecha de serie.
- **Contra:** un tipo de contenido más que guardar por `map_meta_cap`,
  `pre_get_posts`, REST y escritorio (ADR-0018 ya contaba `show_ui` como «una
  puerta más que guardar»); hasta 200 entradas por solicitud y miles por
  procedimiento en `wp_posts`; el DNI de cada persona repartido en
  `wp_postmeta` de entradas que cualquier plugin que recorra tipos de
  contenido encuentra; y la exportación y la revisión pasan de leer una meta a
  una consulta por solicitud. Es el peso de la ADR-0018 para algo que nunca
  tiene página, ni URL, ni vida propia fuera de su solicitud.

### Opción 3: cuentas de usuario para cada persona

- **Contra:** quien participa no entra al aplicativo; crear cuentas es dar
  acceso. Descartada sin más.

### Opción 4: una meta de la solicitud, escrita de fila en fila (elegida)

`prc_people` en la solicitud: una lista de filas, cada una con un `id`
estable. Cada operación —alta, cambio, baja— actúa sobre **una** fila,
releyendo la meta en el momento de escribir.

- **Pro:** cuelga de la solicitud y hereda su guardián, su ámbito y su cierre;
  sin tipo de contenido nuevo; la revisión y la exportación la leen con las
  demás metas, ya en caché.
- **Pro:** no pasa por el formulario de la solicitud: ni su plazo ni su
  vuelta a «presentada».
- **Contra:** es la objeción de la ADR-0018 en pequeño. Releer justo antes de
  escribir estrecha la ventana a milisegundos, pero leer, añadir y escribir
  una meta **sigue sin ser atómico**: dos altas en el mismo instante pueden
  perder una. Se acepta con la escala real —un centro, dos o tres cuentas— y
  porque lo que sí se pisaría en silencio, cambiar la misma fila, se para con
  la huella (abajo).
- **Contra:** la meta es un array serializado que se reescribe entero en cada
  operación; con el tope de 200 filas, pocos kilobytes.

## Decisión

Haremos la opción 4.

### La especificación, en el procedimiento

`prc_people_spec`, que normaliza
[`Domain/PeopleSpec`](../../src/Prc/Domain/PeopleSpec.php) (pura):

```
{ enabled: true,
  roles:  [ { key: 'r1', label: 'Coordinación', needs_group: false },
            { key: 'r2', label: 'Participante', needs_group: true } ],
  role_seq: 2,
  groups: [ { key: 'g1', label: 'Eje 1' } ], group_seq: 1,
  min: 1, max: 20, unique_per_group: false,
  opens_at: '2026-11-01', closes_at: '2026-11-30', admitted_only: false }
```

Hasta 10 papeles y 20 grupos, `max` hasta 200. Las claves las pone el
aplicativo y salen de un contador que nunca baja, como las preguntas
(ADR-0040): renombrar un papel o un grupo no cambia a nadie de sitio, y uno
nuevo no hereda a las personas de uno borrado. En el panel «Personas» del
taller se escriben uno por línea; un papel que termina en «| grupo» exige
grupo, como «Texto | 3» da puntos a una opción. **Quitar un papel o un grupo
que alguna solicitud usa se rechaza** con su nombre, sin guardar nada
([`PeopleSpec::refusal()`](../../src/Prc/Domain/PeopleSpec.php)).

El panel se guarda en cualquier estado mientras el procedimiento se pueda
editar —es lo que permite abrir o alargar la ventana con el plazo de
solicitud cerrado—, y nunca en histórico. Un procedimiento para profesorado
no tiene lista: cada solicitud es de una persona (ADR-0037).

### La lista, en la solicitud

`prc_people`, filas de
[`Domain/ApplicationPeople`](../../src/Prc/Domain/ApplicationPeople.php)
(pura):

```
{ id: '4f1c2a9b0e7d', name: 'Ana', surname: 'Pérez Díaz',
  dni: '12345678Z', email: 'ana@example.org', phone: '',
  role: 'r2', group: 'g1' }
```

`id` lo genera el aplicativo al dar de alta y no cambia: **sustituir a alguien
es editar su fila**, y el historial guarda el antes y el después. El DNI se
valida como el de la coordinación (letra de control, ADR-0019); el correo es
obligatorio; el teléfono, no. `ApplicationPeople::validate( $spec, $people,
$row )` devuelve los errores por clave: la misma persona (DNI) con el mismo
papel y grupo; un papel que exige grupo sin grupo; con `unique_per_group`, dos
personas con el mismo papel y grupo; por encima de `max`. **Por debajo de
`min` no es un error**: la solicitud queda incompleta
(`ApplicationPeople::is_complete()`), y así lo ven el centro y quien revisa.

La coordinación de hoy (`prc_coordinator`) **se queda como está**: los
procedimientos sencillos no necesitan la lista.

### Quién y cuándo

`ProcedureAccess::can_edit_people( $user_id, $application_id )`, y por ella
`map_meta_cap` con la meta capacidad `prc_edit_people`, que no tiene ningún
rol:

- **quién**: el equipo directivo del centro de la solicitud —cualquier cuenta
  con su código, no solo la que la presentó—. Quien revisa **no** la escribe:
  la lista es del centro;
- **cuándo** ([`PeopleSpec::why_closed()`](../../src/Prc/Domain/PeopleSpec.php)):
  nunca con la lista apagada, en profesorado, en un procedimiento histórico
  (ADR-0023) ni en una solicitud excluida; con `admitted_only`, solo
  admitida. Y dentro de **su ventana**: con fechas, de `opens_at` a
  `closes_at`, los dos días incluidos, **aunque el plazo de solicitud esté
  cerrado**; sin fechas, mientras el centro pueda editar la solicitud
  (ADR-0020).

`PublicFront/People` lo vuelve a preguntar antes de escribir, así que un POST
armado a mano se para igual que la pantalla. La meta no se publica en la REST
y su `auth_callback` dice que no a todo el mundo, como el resto de las de la
solicitud (ADR-0018).

Tocar la lista **no** escribe nada más de la solicitud: ni el estado de
revisión, ni la nota, ni la fecha de presentación.

### Dos cuentas a la vez

Cada operación actúa sobre una fila por su `id`, relee la meta sin caché en el
momento de escribir y escribe la lista entera con ese cambio y nada más. El
cambio y la baja llevan en un campo oculto la **huella** de la fila tal como
se pintó (`ApplicationPeople::fingerprint()`, un SHA-256 recortado de sus
datos): si al escribir la fila ya no está, o su huella es otra, se rechaza con
un aviso que lo dice, y no se pisa lo que hizo la otra cuenta.

### El historial

[`PublicFront/ApplicationLog`](../../src/Prc/PublicFront/ApplicationLog.php):
`add( $application_id, $event, $data )` y `all( $application_id )`, en la meta
`prc_log` de la solicitud, con quién, cuándo (UTC, ISO 8601) y qué; en las
personas, `person_added`, `person_changed` y `person_removed` con la fila de
antes y la de después. Tope de 500 entradas por solicitud: al llenarse se van
las más antiguas, como pedía la ADR-0005. Es el primer registro de la ADR-0005
que se escribe, y el PR 9 lo usa para el resto de actuaciones.

### El aviso

La acción `prc_people_changed( $id, $change, $before, $after, $user_id )` y el
evento de correo `people_changed` (ADR-0038), **a los correos de contacto del
procedimiento y a nadie más**: ni la copia de administración (`prc_mail_copy`)
ni el centro, que es quien lo ha hecho. Dice procedimiento, centro, quién,
cuándo y el antes y el después con papel y grupo por su rótulo, **sin el
DNI**. Se apaga en el panel «Avisos», como los demás.

### Pantallas y exportación

- En la solicitud del centro, la sección «Personas», fuera del formulario de
  la solicitud: la lista, el alta y, por fila, cambiar o sustituir y dar de
  baja con confirmación (`data-prc-confirm`). Dice por qué no se puede cuando
  no se puede. «Mi centro» enlaza con ella.
- En el taller, el panel «Personas» y, en la revisión de cada solicitud, la
  lista y su historial en solo lectura.
- En la exportación (ADR-0039), con la lista encendida, el grupo de columnas
  «Personas»; la forma por persona da una fila por cada persona de la lista,
  además de las de la coordinación. El fichero de alta de usuarios gana la
  fuente `people`, con la parte local del correo como usuario. Sin lista, el
  diálogo y el CSV son los de siempre.

### Datos personales

El DNI de cada persona se guarda —en la lista y en el historial— porque es lo
que identifica a quien participa y lo que pide el procedimiento. Se enseña
entero donde ya se enseña el de la coordinación —la solicitud, la revisión y
la exportación autorizada—; en el historial, tapado salvo los cuatro últimos
caracteres, y **nunca** en un correo.

## Consecuencias

### Positivas

- El centro mantiene su lista todo el curso sin pedir que se le reabra nada,
  y quien revisa ve la lista y quién la cambió.
- Una sustitución no pierde a la persona de antes: está en el historial.
- Ni un tipo de contenido, ni una tabla, ni una puerta más que guardar.
- Un procedimiento que no la pide no cambia: ni pantalla, ni exportación, ni
  correo (`tests/unit/test-people-list.php`, `test-application-export.php`).

### Negativas

- **Dos altas en el mismo instante pueden perder una.** Releer antes de
  escribir lo hace improbable, no imposible; lo que se pisaría de verdad —el
  mismo cambio sobre la misma fila— sí se para. Si ocurre, la persona no sale
  en la lista ni en el historial y el centro la vuelve a dar de alta. No está
  medido que ocurra.
- **El DNI está en tres sitios** de la solicitud: la coordinación, la lista y
  el historial. El historial es interno, pero existe, y cualquier copia de
  seguridad de la base de datos lo lleva.
- **El historial tiene tope** y no es un archivo: una solicitud con más de 500
  cambios pierde los primeros.
- **Quitar un papel en uso no se puede**: hay que cambiar antes de papel a sus
  personas, solicitud por solicitud, o dejarlo con otro nombre.
- **La ventana propia es otra fecha que poner.** Sin ella, la lista se cierra
  con la solicitud, que es lo de antes.
- **Quien revisa no corrige la lista.** Si un centro se equivoca, se lo tiene
  que pedir; actuar en su nombre es el PR 9.

### Neutras

- `unique_per_group` solo mira las personas que tienen grupo.
- La lista no es parte del acuse de la solicitud: tiene su propio aviso.
- La certificación de participantes sigue fuera (PLAN-0003, D-5).
