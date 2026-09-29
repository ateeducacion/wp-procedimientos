---
id: ADR-0008
title: "Que dos personas no se pisen: se usa el bloqueo de edición nativo de WordPress, no uno propio"
status: Propuesta
date: 2026-09-15
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0005, ADR-0007, ADR-0010, ADR-0014, ADR-0018, ADR-0023]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-fable-5-1"
---

# ADR-0008: Que dos personas no se pisen: se usa el bloqueo de edición nativo de WordPress, no uno propio

## Estado

Propuesta (2026-09-15). El módulo llega del aplicativo de eventos
(`src/Prc/PublicFront/EditLock.php`: `owner()`, `require_available()`,
`status()`, `claim()`, `release()`, `handle()` y `render()`) con su parte de
navegador y sus pruebas ([ADR-0010](ADR-0010-se-parte-del-esqueleto-del-aplicativo-de-eventos.md)).
Lo que se decide aquí es dónde se engancha. El comportamiento medido en
eventos con dos sesiones simultáneas —el 409 antes de escribir, el aviso por
Heartbeat, la baliza al cerrar— no se ha vuelto a medir aquí; medirlo es la
primera prueba de esta ADR.

## Contexto

El aplicativo ya decide **quién** puede editar un procedimiento —el ámbito
([ADR-0014](ADR-0014-el-ambito-es-un-alcance-no-un-rol.md)) y el archivo
([ADR-0023](ADR-0023-el-estado-historico-cierra-la-edicion.md))— pero no dice
nada sobre **cuándo**. Y el taller es una pantalla larga: cinco paneles,
catorce metas, un constructor de hasta veinte preguntas, y se guarda un rato
después de abrirse.

Es el sitio donde dos personas se pisan, y aquí por dos motivos propios:

- **El ámbito es un árbol y los perfiles se solapan.** Quien tiene el servicio
  en `prc_area` ve y edita los procedimientos de todas sus áreas; quien tiene
  el área, los suyos. Dos personas con derecho sobre el mismo procedimiento
  es el caso normal, no el raro (`scripts/seed-demo.php` lo monta a propósito
  con `gestion` y `gestion2`).
- **Las preguntas son del procedimiento entero**
  ([ADR-0019](ADR-0019-la-solicitud-es-nucleo-fijo-mas-preguntas.md)): dos
  personas en dos paneles distintos del mismo taller no están trabajando en
  cosas separadas, porque el POST de un panel escribe sobre el mismo post.

Y hay un tercer camino que existe igual: **el escritorio de WordPress**. Un
procedimiento es un `prc_procedure` con `show_ui`, así que se puede abrir en
`post.php` como cualquier entrada.

## Problema

¿Cómo se evita que dos personas que pueden editar el mismo procedimiento se
sobrescriban el trabajo, contando con que una de las dos puede estar en el
escritorio de WordPress y no en el aplicativo?

## Factores de decisión

- **No puede haber dos verdades sobre quién está editando.** Si el aplicativo
  lleva su cuenta y `wp-admin` la suya, cada uno deja pasar lo que el otro
  bloquea.
- **La comprobación va antes de escribir, no después.** Quien perdió el turno
  tiene la pantalla de hace media hora delante; su envío trae catorce campos
  viejos y los escribiría todos.
- **Tiene que decir un nombre.** «Lo está editando otra persona» no sirve para
  avisar a nadie.
- **Tiene que haber salida.** Hace falta caducidad y poder tomar posesión.
- **Cuanto menos código, mejor.** Es un problema resuelto en WordPress desde
  2008.

## Alternativas consideradas

### Opción 1: el bloqueo nativo de WordPress (`_edit_lock`) — ELEGIDA

`wp_check_post_lock()`, `wp_set_post_lock()` y la meta `_edit_lock`, más su
renovación por Heartbeat (`wp-refresh-post-lock`) y su liberación por baliza
(`wp-remove-post-lock`).

- Pros: **se comparte con `wp-admin`**, y esa es la razón principal; cero
  mecanismo nuevo —caducidad, renovación, liberación y el nombre de quien lo
  tiene ya están escritos y probados en el núcleo—; el módulo ya existe y
  está probado en eventos.
- Contras: la caducidad y el intervalo son los de WordPress (150 s y 15 s), y
  la semántica del núcleo es «aviso fuerte», no «candado».

### Opción 2: un bloqueo propio, con su meta y su tabla — DESCARTADA

- Contras: no lo vería `wp-admin`, que es el caso que más duele; habría que
  escribir caducidad, renovación, liberación y aviso en caliente; no ahorra
  nada.

### Opción 3: no bloquear y resolver el choque al guardar — DESCARTADA

- Contras: se entera **después** de haber escrito media hora, y `post_modified`
  no se mueve cuando lo que cambia son las metas y los términos.

### Opción 4: bloqueo por panel y no por procedimiento — DESCARTADA

- Contras: los paneles escriben sobre el mismo post; un candado que
  tranquiliza y no protege es peor que ninguno.

## Decisión

Se adopta la misma decisión que el aplicativo de eventos, y por las mismas
razones.

### 1. El bloqueo es el nativo, y por eso se comparte con el escritorio

`EditLock::owner()` es `wp_check_post_lock()` y `EditLock::claim()` es
`wp_set_post_lock()`, los dos sobre la meta `_edit_lock` del núcleo. No hay
meta propia ni tabla propia.

### 2. Es del PROCEDIMIENTO, y solo del taller

`prc_procedure` no es jerárquico, así que no hay raíz que resolver: el
bloqueo es del post que se edita. Se toma al abrir `PublicFront/ProcedureEditor`
y se comprueba en todos sus paneles, incluido el de solicitudes: admitir,
excluir o pedir subsanar escribe en la solicitud, pero se hace desde el
taller del procedimiento y es una sola pantalla con un solo dueño.

**El formulario de solicitud del centro no toma bloqueo.** Es corto, lo
rellena una persona y el caso de dos cuentas del mismo centro editando la
misma solicitud a la vez existe pero no justifica un aviso más en la pantalla
que menos gente sabe usar. Si ocurre, gana la última que guarda; queda
anotado como decisión consciente y como límite.

### 3. La comprobación va antes de escribir, y responde 409

`EditLock::require_available()` es lo primero de `ProcedureEditor::handle()`:
antes de una sola meta, un solo término o un solo estado. Responde `409` con
`wp_die()`, con el nombre de quien lo tiene y un enlace de vuelta.

### 4. «Tomar posesión» va por POST y con su nonce, y además con `ProcedureAccess`

El nonce dice que el envío salió de nuestra pantalla; no dice que quien lo
manda pueda editar este procedimiento. Se comprueban las dos cosas
(`EditLock::handle()`), y sin la segunda se responde `403`.

### 5. Al soltar solo se suelta el propio

`EditLock::release()` le pasa el valor exacto a `delete_post_meta()`: si otra
persona tomó posesión mientras tanto, el bloqueo es suyo y no se le quita.

### 6. El aviso dice el nombre, y se ve sin JavaScript

Es un `<dialog>` nativo y no SweetAlert2: esto no es una confirmación
([ADR-0007](ADR-0007-borrar-es-enviar-a-la-papelera.md)), es el estado de la
pantalla. El servidor lo pinta con `open`, toda la hoja va dentro de un
`<fieldset disabled>`, y nunca dice «alguien»: dice el `display_name`.

### 7. El Heartbeat sí se porta, y avisa sin recargar

`wp-refresh-post-lock` viaja en cada latido, renueva el bloqueo propio y,
cuando otra persona toma posesión, abre el aviso con `showModal()` y deja
`inert` todos los formularios menos el del aviso. Lo escrito **no se borra**:
se congela en pantalla para poder copiarlo. Al cerrar la pestaña, una baliza
`wp-remove-post-lock` suelta el bloqueo.

## Consecuencias

### Positivas

- **Una sola verdad sobre quién está editando**, compartida con el escritorio.
- **Se avisa antes de perder el trabajo, no después.** Quien pierde el bloqueo
  lo sabe en 15 segundos y con lo escrito todavía en pantalla.
- **Poco código propio**, ninguna caducidad que mantener, y funciona sin
  JavaScript.

### Negativas

Y aquí conviene no adornar nada:

- **Un bloqueo caduca, y esto no es un candado.** A los 150 segundos sin
  latidos, el procedimiento queda libre aunque la otra persona siga con la
  pantalla abierta —basta con que se le duerma el portátil—. Quien entre
  después no verá aviso ninguno y guardará encima.
- **«Tomar posesión» no pide permiso a nadie, y se puede perder trabajo no
  guardado.** Cualquiera que pueda editar el procedimiento puede quitárselo
  a quien lo tenga. Lo no guardado se congela en pantalla para copiarlo a
  mano, y ahí se acaba la ayuda. Hay que contarlo al formar a la gente.
- **Si se cierra el navegador, depende de una baliza.** Sin JavaScript, sin
  batería o sin red, no sale, y el procedimiento sigue apareciendo ocupado
  hasta los 150 segundos. Nunca más de dos minutos y medio, pero no es cero.
- **La solicitud del centro no está protegida.** Dos cuentas del mismo centro
  —dirección y secretaría, por ejemplo— pueden pisarse. Es un límite
  elegido, y si aparece en la práctica se engancha `EditLock` en `ApplyForm`
  con las mismas cuatro líneas.

### Neutras

- **No hay registro de quién tomó posesión ni cuándo.** `_edit_lock` es una
  fecha y un usuario, y se sobrescribe. Entra en la auditoría que la
  [ADR-0005](ADR-0005-politica-de-edicion-y-auditoria.md) deja pendiente.
- **El bloqueo no sustituye a los permisos.** Quien no puede editar el
  procedimiento no ve ningún aviso ni cuenta para nada
  (`EditLock::status()` devuelve `none()` cuando `can_edit` es falso).
