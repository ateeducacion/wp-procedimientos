---
id: ADR-0007
title: "Borrar es enviar a la papelera; el borrado definitivo se queda en el escritorio"
status: Propuesta
date: 2026-09-15
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0005, ADR-0006, ADR-0014, ADR-0018, ADR-0020, ADR-0023]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-fable-5-1"
---

# ADR-0007: Borrar es enviar a la papelera; el borrado definitivo se queda en el escritorio

## Estado

Propuesta (2026-09-15). Lo que aquí se afirma sobre `wp_delete_post()` y las
hijas está leído en el código del núcleo (`wp-includes/post.php`), no medido
en el wp-env de este repositorio, que a esta fecha no se ha levantado. Medirlo
es el primer test de esta ADR.

## Contexto

El aplicativo tiene pantallas propias en el frontal: el listado de
procedimientos del ámbito (`PublicFront/Workspace`) y el taller de un
procedimiento (`PublicFront/ProcedureEditor`). Quien pulsa «Borrar» ahí no es
administración: es personal de un ámbito con `prc_manage_procedures`, que
entra por una página del sitio y no por `wp-admin`.

Lo que hay detrás de un procedimiento no es una fila suelta. Por
[ADR-0018](ADR-0018-la-solicitud-es-un-contenido-del-procedimiento.md), cada
solicitud es un `prc_application` con `post_parent` = el procedimiento, y hay
procedimientos con cientos. Y una solicitud lleva datos de personas: el cargo
de quien la presenta y, si el procedimiento lo pide, el nombre y el correo de
la persona coordinadora.

En el sistema actual el peligro se conoce y se parchea: un fragmento de código
pinta en rojo las filas de las convocatorias en el listado de entradas del
gestor de formularios y cambia el texto de confirmación del enlace de borrar,
porque borrar la entrada de una convocatoria deja huérfanas todas sus
solicitudes. Otro quita el botón de «borrar todas las entradas». Eso esconde
el botón; no protege nada.

WordPress ya trae el mecanismo que hace falta: `wp_trash_post()` cambia el
`post_status` a `trash` y guarda el anterior en `_wp_trash_meta_status`;
`wp_untrash_post()` lo devuelve; la papelera se vacía sola a los
`EMPTY_TRASH_DAYS` días (30 por defecto), y el borrado definitivo tiene su
propia capacidad y su propia pantalla en el escritorio.

## Problema

Cuando alguien pulsa «Borrar» en un procedimiento, ¿qué pasa con él y con sus
solicitudes: desaparecen o se pueden recuperar? ¿Y quién puede borrar una
solicitud?

## Factores de decisión

- **El clic equivocado existe**, y el botón de borrar está a un centímetro
  del de editar.
- **Lo que se pierde no es recuperable de otra manera**: no hay copia de
  seguridad al alcance de quien gestiona un ámbito.
- **Las solicitudes cuelgan del procedimiento.** Un borrado definitivo del
  padre no las borra: las deja apuntando a un ID que ya no existe.
- **Las solicitudes llevan datos personales**, y una papelera no es un borrado.
- **Quien borra no es administración** ([ADR-0014](ADR-0014-el-ambito-es-un-alcance-no-un-rol.md)
  dice qué puede tocar cada persona, no cuánto daño hace de un clic).
- **El núcleo ya lo resuelve**, y este repositorio no reimplementa lo que
  WordPress hace.

## Lo que hace el núcleo, leído en su código

`wp_trash_post()` no toca a las hijas: ni `post_status`, ni `post_parent`, ni
metas. `wp_delete_post()` sí, pero **solo para tipos jerárquicos**: reengancha
las hijas al abuelo cuando `is_post_type_hierarchical()` es cierto. Ni
`prc_procedure` ni `prc_application` lo son, así que borrar definitivamente
un procedimiento **deja sus solicitudes con un `post_parent` que no existe**:
ni borradas, ni reenganchadas, ni visibles desde ninguna pantalla del
aplicativo. Y al mandarlo a la papelera, WordPress le añade `__trashed` al
slug para liberar el bueno; como la solicitud no tiene URL propia
(`public => false`), aquí eso no rompe ningún enlace.

## Alternativas consideradas

### Opción 1: `wp_trash_post()` en los dos tipos de contenido — ELEGIDA

- Pros: el clic equivocado se deshace en un clic, por la misma persona y en la
  misma pantalla; el contenido sigue con su ID, así que las solicitudes no
  quedan colgando; la purga la hace el núcleo; es lo que cualquiera que haya
  usado WordPress conoce.
- Contras: la papelera es estado que hay que mirar —toda consulta tiene que
  decir qué `post_status` quiere—; el slug queda ocupado con `__trashed`; y
  **el borrado no es inmediato**, lo que para un dato personal metido por
  error es un matiz que hay que saber.

### Opción 2: `wp_delete_post( $id, true )`, borrado definitivo — DESCARTADA

- Pros: sin estado intermedio; si lo borrado eran datos personales, dejan de
  estar en el acto.
- Contras: no hay vuelta atrás y quien pulsa no es administración; y con las
  solicitudes colgando del procedimiento, borrar el padre hace algo peor que
  borrar: deja cientos de solicitudes huérfanas, con datos de personas, que
  ninguna pantalla enseña y ninguna papelera recoge.

### Opción 3: papelera para el procedimiento, definitivo para la solicitud — DESCARTADA

- Contras: dos comportamientos para el mismo verbo en la misma pantalla, y la
  fila más fácil de borrar sin querer sería justamente la irreversible.

## Decisión

### 1. Borrar es `wp_trash_post()`, en los dos tipos de contenido

`wp_delete_post()` no se llama desde el aplicativo.

### 2. El procedimiento se borra desde el taller, y tiene papelera y «Restaurar»

El listado del ámbito enseña un filtro **«Papelera (N)»** solo cuando hay algo
dentro, con un botón de restaurar por fila. Restaurar exige lo mismo que
editar: quien no podría tocar el procedimiento tampoco lo saca de la papelera.
Lo restaurado **vuelve en borrador** (el valor por omisión de WordPress desde
5.6; no se instala `wp_untrash_post_set_previous_status()`): un procedimiento
borrado por error no reaparece publicado sin que nadie lo mire.

### 3. Una solicitud no se borra: se excluye

El aplicativo **no ofrece botón de borrar solicitudes**. Quien gestiona la
marca `excluded` con su nota ([ADR-0020](ADR-0020-la-subsanacion-es-un-plazo-y-un-estado.md)),
y así queda constancia de que existió y por qué no sigue. El centro tampoco
retira la suya: la edita mientras el plazo esté abierto. Mandar una solicitud
a la papelera —una presentada por error contra el procedimiento equivocado—
es cosa del escritorio (`show_ui => true` existe para eso) y de quien tenga
`delete_post` sobre ella por `map_meta_cap`.

### 4. Las solicitudes de un procedimiento en la papelera se quedan como están

No se arrastra la papelera hacia abajo ni se restaura hacia abajo. Mientras
el procedimiento esté en la papelera, sus solicitudes no se pintan en ninguna
pantalla —`Applications` consulta siempre por procedimiento vivo— y vuelven
solas al restaurarlo.

### 5. El borrado definitivo es del escritorio de WordPress

Y solo para quien tenga la capacidad. El aplicativo no ofrece ningún atajo.

### 6. La confirmación se pide, y se pide bien

Con SweetAlert2 y el verbo en el botón —«Enviar a la papelera», no
«Aceptar»—, degradando a `confirm()` y, sin JavaScript, enviando el
formulario igual ([ADR-0006](ADR-0006-librerias-de-terceros-desde-cdn-con-sri.md)).
El aviso de vuelta dice dónde ha ido lo borrado y que nada se ha perdido.

### 7. Papelera no es histórico

Archivar ([ADR-0023](ADR-0023-el-estado-historico-cierra-la-edicion.md)) es
lo que se hace con un procedimiento que terminó; la papelera es lo que se
hace con uno que no debió existir. Un procedimiento con solicitudes
presentadas que se manda a la papelera es casi siempre un error, y la
confirmación lo dice con el número de solicitudes delante.

## Consecuencias

### Positivas

- **El error se deshace donde se cometió**, por la misma persona, sin abrir un
  ticket y sin entrar al escritorio.
- **No hay huérfanos.** Al no llamar nunca a `wp_delete_post()`, ninguna
  solicitud se queda apuntando a un procedimiento que no existe.
- **Cero código de purga.** Los 30 días los cuenta el núcleo.
- **Excluir deja rastro; borrar no.**

### Negativas

- **Hay estado que mirar.** Toda consulta de procedimientos o solicitudes
  tiene que decir qué `post_status` quiere; la que no lo diga enseñará algo
  de la papelera. Es el tipo de detalle que se olvida en la consulta número
  doce.
- **Un procedimiento en la papelera sigue ocupando su slug** con `__trashed`;
  el que vuelva puede acabar con un número detrás.
- **Borrar no borra.** El nombre y el correo de una persona coordinadora
  metidos por error siguen en la base de datos hasta 30 días, o hasta que
  alguien vacíe la papelera desde el escritorio. Es una consecuencia real y
  hay que conocerla.
- **La papelera de la solicitud es invisible desde el aplicativo.** Una
  solicitud que administración mande a la papelera desde el escritorio no
  aparece en «Mi centro» ni en el panel de solicitudes, y el centro no sabe
  por qué; y si ese centro vuelve a solicitar, `Applications::find()` no la
  encuentra y crea otra. Hay que decidir si `find()` mira también la
  papelera, y esta ADR dice que **no**: lo que está en la papelera no existe.

### Neutras

- `EMPTY_TRASH_DAYS` no se toca: si se quisiera otra ventana, es una
  constante de `wp-config.php` del sitio, no código del aplicativo.
- Cuando la fase 2 traiga documentos adjuntos, su borrado —ficheros en disco,
  no filas— se decide en su propia ADR y no se hereda de esta.
