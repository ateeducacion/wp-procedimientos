---
id: ADR-0014
title: "El ámbito es un alcance asignado a la persona, no un rol"
status: Propuesta
date: 2026-09-15
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0003, ADR-0009, ADR-0012, ADR-0015, ADR-0016]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-fable-5-1"
---

# ADR-0014: El ámbito es un alcance asignado a la persona, no un rol

## Estado

Propuesta (2026-09-15). Único guardián en `Access/ProcedureAccess`; meta de
usuario `prc_area`; campo «Ámbitos» en la ficha de usuario del snippet de
roles.

## Contexto

En el sitio de hoy quien gestiona una convocatoria es una persona con un rol
de «responsable» —unas cien cuentas— y **una meta de usuario con su ámbito**,
que apunta a uno o varios términos de la taxonomía de ámbitos. La forma es la
correcta: el ámbito ya es un dato de la persona y no un rol. Lo que falla es
todo lo que hay alrededor, leído sobre el material de `.local/` (que no se
publica, [ADR-0009](ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md)):

1. **El acotado se hace en el navegador.** El listado de gestión pinta
   **todas** las filas de todas las convocatorias y un fragmento de
   JavaScript oculta las que no son del ámbito de quien mira. El fragmento
   que lo hacía en el servidor está desactivado. Cualquiera con el rol ve el
   listado completo en el HTML, y una caché de página lo sirve igual para
   todos los usuarios con sesión.
2. **La meta se lee de tres formas en cascada** —un shortcode del gestor de
   formularios, un campo del plugin de campos, `get_user_meta`— porque su
   formato no es uno: a veces es un ID, a veces un slug, a veces un nombre, a
   veces una lista separada por comas. El mismo normalizador está copiado en
   dos fragmentos con sufijo de versión para no chocar.
3. **Los descendientes se calculan a mano.** El árbol servicio → área es real
   y una persona de un servicio debe ver las áreas de debajo; hoy eso lo
   hace el fragmento de JavaScript recorriendo `data-ambitos` fila a fila.
4. **Nada acota la escritura.** La entrada del formulario se edita por su ID
   en la URL; que el ámbito de quien edita coincida con el de la convocatoria
   no lo comprueba nadie en el servidor.

Mientras tanto, la creación sí usa el dato: al dar de alta, otro fragmento
preselecciona el ámbito de la persona en la casilla y copia el correo del
término a los correos de contacto.

## Problema

¿Cómo se expresa «los procedimientos de mi ámbito» de forma que acote lo que
se ve **y lo que se edita**, en el servidor, incluyendo a los descendientes,
y que falle en cerrado cuando falte el dato?

## Factores de decisión

- **Pertenencia frente a permiso.** La pregunta es «¿este procedimiento es de
  mi ámbito?», y un rol no la responde
  ([ADR-0015](ADR-0015-el-aplicativo-comprueba-capacidades-no-roles.md)).
- **Jerarquía real.** Servicio → área existe y el acotado tiene que
  respetarla sin que nadie recorra filas.
- **Cardinalidad.** Hay 32 términos hoy y personas en más de uno; un rol por
  ámbito es una lista que solo crece.
- **Fail-closed.** Editar el procedimiento de otro ámbito tiene que ser
  imposible por defecto, no por olvido; y quien no tiene ámbito no edita
  nada.
- **Un solo sitio que decide.** Listado del escritorio, taller, página
  pública y exportación tienen que preguntar lo mismo al mismo código.
- **Quién mantiene el dato.** Hoy, nadie: se rellena a mano en la ficha. Eso
  no cambia con esta decisión y hay que decirlo.

## Alternativas consideradas

### Opción 1: un rol por ámbito

`prc_area_<slug>` por cada término, con las capacidades del CPT repartidas
por rol.

- A favor: el ámbito se ve en la columna «Rol» de la lista de usuarios.
- En contra: **sigue sin expresar de quién es el procedimiento**; habría que
  comparar el rol con el término, que es lo que hace la opción elegida con un
  vocabulario duplicado y decenas de roles de más; cada reorganización sería
  un despliegue. Descartada.

### Opción 2: reutilizar la meta de usuario que ya tiene el sitio

Leer la meta de ámbito de hoy, con su clave y su formato, como hace
[ADR-0016](ADR-0016-el-centro-de-la-persona-es-una-meta-configurable.md) con
el código de centro.

- A favor: el día del despliegue las personas ya tienen su ámbito puesto.
- En contra: **apunta a términos de otra taxonomía**, la del constructor de
  páginas, que este aplicativo no registra; su formato son cuatro formatos; y
  a diferencia del código de centro, **no la escribe ningún fragmento al
  iniciar sesión**: la rellena administración a mano. Reutilizarla ahorra
  una tarde de rellenar fichas a cambio de heredar un dato sin forma que
  apunta a un vocabulario que se retira. Descartada; el ámbito se vuelve a
  asignar sobre `prc_area`.

### Opción 3: una capacidad por ámbito

`prc_manage_area_<term_id>` concedida a cada persona.

- A favor: el guardián solo pregunta `current_user_can`.
- En contra: una capacidad por término es un rol por término con otro
  nombre, sin jerarquía y con IDs numéricos en los slugs. Descartada.

### Opción 4: taxonomía `prc_area` + meta de usuario `prc_area` + un guardián (elegida)

El ámbito del procedimiento es un término; la pertenencia de la persona es
un dato de su ficha; y una sola clase decide, en el servidor, con
descendientes y en cerrado.

## Decisión

**El ámbito es un alcance: un término de `prc_area` en el procedimiento y una
meta de usuario `prc_area` en la persona. Lo comprueba un único guardián,
`Access/ProcedureAccess`, y falla en cerrado.**

- **El ámbito del procedimiento** es un término de la taxonomía jerárquica
  `prc_area` ([ADR-0012](ADR-0012-una-taxonomia-por-dimension.md)); un
  procedimiento puede llevar más de uno.
- **La pertenencia de la persona** es la meta `prc_area`
  (`ProcedureAccess::USER_AREA_META`), con uno o varios `term_id` de esa
  misma taxonomía. **Un término incluye a sus descendientes**: quien tiene el
  servicio ve y edita las áreas de debajo, calculado con `get_term_children`
  en el servidor, no fila a fila en el navegador.
- **Se edita en la ficha de usuario**, con los términos sangrados por
  jerarquía, y **solo la puede tocar quien administra**: WordPress deja a
  cualquiera editar su propio perfil, y un campo de ámbito editable por su
  dueño es una puerta para asignarse el ámbito de otro. El campo lo pinta y
  guarda `snippets/roles-and-profiles.php`
  ([ADR-0015](ADR-0015-el-aplicativo-comprueba-capacidades-no-roles.md)).
- **Un solo guardián.** `ProcedureAccess` engancha `map_meta_cap` sobre
  `edit_post`, `delete_post`, `publish_post` y `read_post` de `prc_procedure`
  y `prc_application`, y `pre_get_posts` para acotar el listado del
  escritorio. El taller, la página pública, la exportación y los
  `auth_callback` de las metas preguntan a esa clase y a nada más.
- **Fail-closed.** Sin ámbito en la ficha y sin `prc_manage_all_areas`, no se
  edita ni se ve nada en el taller. Un procedimiento sin ámbito solo lo ve
  `prc_manage_all_areas`; la única excepción, escrita y acotada, es el
  procedimiento **recién creado**, que edita quien lo creó hasta que le pone
  el ámbito. Un ámbito borrado deja sus procedimientos en ese mismo caso.
- **`prc_manage_all_areas` salta el acotado.** Es la capacidad de
  administración, no un rol intermedio: lo que está por encima del ámbito ya
  tiene nombre en WordPress.
- **Los permisos se preguntan por capacidad, nunca por rol.** Por eso el
  acotado no cambia aunque cambie quién lleva qué rol en el sitio de destino.
- **La solicitud hereda el ámbito de su procedimiento**: `prc_application` no
  lleva término propio; el guardián sube a `post_parent`. Quien revisa
  solicitudes es quien gestiona el procedimiento del que cuelgan.

## Consecuencias

### Positivas

- **«Los procedimientos de mi ámbito» es una `tax_query`** con
  `include_children`, y es la misma que acota el escritorio, el taller, la
  portada del taller y el CSV. Deja de haber filas ocultas con JavaScript.
- **Nadie ve en el HTML lo que no es suyo**, y la caché no puede servirlo por
  error, porque no llega a pintarse.
- **La escritura está acotada.** Editar el procedimiento de otro ámbito
  devuelve 403 en `map_meta_cap`, no depende de que el enlace no esté.
- **Un ámbito nuevo es un término**, creado en diez segundos desde el
  escritorio; una reorganización es mover un término en el árbol.
- **Una persona en dos ámbitos** marca dos casillas.
- **La meta tiene un solo formato**: IDs de término de `prc_area`.

### Negativas

- **El día del despliegue nadie gestiona nada hasta que se rellenen las
  fichas.** Fail-closed es eso. Hay que repasar la ficha de cada persona con
  rol de responsable y ponerle su término de `prc_area`; la meta de hoy no se
  copia porque apunta a otro vocabulario. Es una tarea manual de puesta en
  marcha, y si no se hace, el aplicativo parece roto.
- **La meta no la mantiene nadie automáticamente.** Quien cambia de servicio
  conserva el ámbito viejo hasta que alguien lo corrija; quien se va conserva
  permiso sobre los procedimientos de su antiguo ámbito hasta que se le
  retire la cuenta o la capacidad.
- **El ámbito no se ve en la lista de usuarios** sin abrir la ficha o añadir
  una columna, que no está escrita.
- **Un procedimiento puede quedarse sin ámbito** si quien lo crea no lo
  marca; la regla del recién creado limita el daño, pero no aparece en el
  listado de nadie más. Hace falta un aviso en el taller y una comprobación
  al publicar.

### Neutras

- Administración queda exenta del acotado por `prc_manage_all_areas` y
  `prc_manage_app`. Es deliberado y es lo que permite arreglar un
  procedimiento mal asignado.
- El correo de contacto deja de copiarse del término al crear: es un dato
  del procedimiento que quien lo crea escribe
  ([ADR-0012](ADR-0012-una-taxonomia-por-dimension.md)).
- El acotado **por centro** de quien solicita es otro eje, con su propia meta
  y su propia decisión
  ([ADR-0016](ADR-0016-el-centro-de-la-persona-es-una-meta-configurable.md));
  el guardián es el mismo.
