---
id: ADR-0016
title: "El centro de la persona es una meta de usuario con clave configurable"
status: Propuesta
date: 2026-09-15
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0009, ADR-0014, ADR-0015, ADR-0017, ADR-0018]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-fable-5-1"
---

# ADR-0016: El centro de la persona es una meta de usuario con clave configurable

## Estado

Propuesta (2026-09-15). `Access/CentreScope::code_for()`; filtro
`prc_centre_code_meta_key`; campo «Código de centro» en la ficha de usuario
del snippet de roles.

## Contexto

Quien presenta una solicitud lo hace **en nombre de un centro**, y el centro
no lo elige: lo tiene. En el sitio de hoy, leído sobre el material de
`.local/` (que no se publica,
[ADR-0009](ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md)):

1. **El código de centro lo escribe un fragmento del sitio al iniciar
   sesión.** Consulta el directorio corporativo, deduce el centro de los
   nombres de los grupos a los que pertenece la persona, y lo guarda en una
   meta de usuario con una clave que eligió quien escribió el fragmento. Se
   sobrescribe en cada inicio de sesión. El mismo fragmento pone o quita el
   rol de dirección ([ADR-0015](ADR-0015-el-aplicativo-comprueba-capacidades-no-roles.md)).
2. **El formulario de solicitud lo lee como valor por defecto de un campo
   oculto.** Un shortcode del gestor de formularios inyecta la meta en el
   HTML, y el resto de los datos del centro —nombre, etapa, municipio— llegan por
   consultas al catálogo desde ese campo. Es decir: **el centro de la
   solicitud viaja en el POST**, y quien edite el HTML solicita por otro
   centro.
3. **La unicidad se apoya en ese mismo valor**: la clave compuesta «ID de
   convocatoria · código» se construye concatenando el campo oculto.
4. **Hay un segundo código** para el caso de quien dirige un centro y además
   coordina una agrupación de centros rurales: solicita por la agrupación con
   el código de la agrupación. Y hay una lista de excepciones en los ajustes
   del sitio para cuentas que siempre deben ser dirección.

Lo primero —que el directorio ponga el código— es lo correcto y no es de este
aplicativo. Lo segundo es el problema: el aplicativo no puede fiarse de un
dato que le llega del navegador, y no puede escribir en su código el nombre
de una meta que decidió otro sistema.

## Problema

¿De dónde saca el aplicativo el centro de la persona que solicita, de forma
que no venga del navegador, que no nombre la meta del sitio de destino, y que
falle en cerrado cuando no exista?

## Factores de decisión

- **Frontera de confianza.** El centro es el dato que decide *por quién* se
  solicita; no puede ser un campo del formulario.
- **Publicable.** La clave de la meta del sitio de destino es un detalle del
  despliegue ([ADR-0009](ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md)).
- **No tocar el fragmento de inicio de sesión.** Escribe donde escribe; el
  aplicativo se adapta, no al revés.
- **Local sin directorio.** `make up` tiene que poder entrar como dirección
  de un centro inventado.
- **Fail-closed.** Sin código no se solicita; y se dice por qué.
- **Administración tiene que poder corregir** un código sin esperar al
  siguiente inicio de sesión ni tocar el directorio.

## Alternativas consideradas

### Opción 1: el código viaja en el formulario (statu quo)

- A favor: nada que configurar; el shortcode ya lo pone.
- En contra: es un dato de autorización en un campo oculto. Descartada sin
  matices.

### Opción 2: clave fija `prc_centre_code` y que el sitio la rellene

El aplicativo lee siempre su meta y el sitio de destino modifica su fragmento
de inicio de sesión para escribir también ahí.

- A favor: una sola clave, sin filtro.
- En contra: obliga a tocar el fragmento del sitio, que no es nuestro, y a
  mantener dos metas con el mismo valor; el día que el fragmento cambie y no
  escriba la nuestra, todo el mundo se queda sin centro. Descartada.

### Opción 3: consultar el directorio en cada petición

- A favor: siempre al día.
- En contra: el directorio no es de este aplicativo, exige credenciales que
  no pueden vivir aquí, y una consulta al directorio por petición es lo que hoy ya
  hace el fragmento una vez por sesión y ya es demasiado. Descartada.

### Opción 4: un filtro sobre el valor, `prc_centre_code`

Que el sitio conteste el código directamente para un `user_id`.

- A favor: máxima flexibilidad: el sitio puede calcularlo como quiera.
- En contra: el dato deja de ser consultable —no hay `meta_query` sobre un
  filtro— y el campo de la ficha no tiene dónde escribir. Para el caso que
  hay, que es «la meta se llama de otra forma», sobra flexibilidad.
  Descartada; se deja anotada por si algún día el centro no es una meta.

### Opción 5: meta de usuario con clave configurable por filtro (elegida)

El aplicativo lee una meta de usuario cuya **clave** la devuelve un filtro,
con un valor por defecto propio. En producción, un fragmento contesta con la
clave que ya escribe el inicio de sesión.

## Decisión

**El centro de la persona es una meta de usuario. Su clave la devuelve el
filtro `prc_centre_code_meta_key`, por defecto `prc_centre_code`. Se lee en
un solo sitio, `Access/CentreScope::code_for( $user_id )`, nunca del
navegador, y sin código válido `prc_apply` no sirve para nada.**

- **Lectura única.** `CentreScope::code_for()` hace
  `get_user_meta( $user_id, apply_filters( 'prc_centre_code_meta_key', 'prc_centre_code' ), true )`,
  lo sanea como código —cadena de dígitos, sin espacios— y devuelve `''` si
  no hay nada válido. `ApplyForm`, `MyCentre`, `Applications::find()` y el
  guardián preguntan ahí y a nada más.
- **Nunca del POST.** El formulario de solicitud pinta el centro **de solo
  lectura**, resuelto desde la persona en el momento de pintar y otra vez en
  el momento de guardar. Un POST con otro código se ignora: el centro de la
  solicitud es el que devuelve `code_for()` de quien está firmado.
- **Fail-closed.** Con `prc_apply` y sin código, la pantalla `apply` dice
  «Su cuenta no tiene centro asignado» y no ofrece el formulario; `mine` no
  lista nada; el botón «Solicitar» de la ficha pública no aparece.
- **Foto, no referencia.** Al presentar, la solicitud guarda `prc_centre_code`
  y `prc_centre_name` como estaban ese día
  ([ADR-0018](ADR-0018-la-solicitud-es-un-contenido-del-procedimiento.md));
  el nombre se resuelve contra el catálogo
  ([ADR-0017](ADR-0017-el-catalogo-de-centros-no-se-versiona.md)) y, si el
  código no está en él, se guarda con nombre vacío y quien gestiona lo ve
  marcado. No se bloquea: el catálogo puede ir por detrás de la realidad.
- **Override de administración.** El snippet de roles añade a la ficha de
  usuario el campo «Código de centro», editable solo por `prc_manage_app`, que
  **escribe en la misma clave que devuelve el filtro**: administración y el
  inicio de sesión comparten un dato, no dos.
- **En producción**, un fragmento privado del sitio hace
  `add_filter( 'prc_centre_code_meta_key', fn() => '<la clave del sitio>' )`.
  Esa clave no se escribe en este repositorio. En desarrollo no hay filtro:
  `scripts/seed-demo.php` escribe `prc_centre_code` en las cuentas
  `direccion` y `direccion2` con códigos del catálogo inventado.
- **Un centro por persona en la fase 1.** El caso de quien dirige un centro
  y coordina una agrupación rural no se modela: `code_for()` devuelve un
  código. Queda anotado como pendiente; la escotilla es que el filtro
  devuelva otra clave o que `code_for()` devuelva una lista, y ninguna de las
  dos obliga a rehacer nada.

## Consecuencias

### Positivas

- **El centro de la solicitud no lo elige el navegador.** Desaparece el
  campo oculto y, con él, la posibilidad de solicitar por otro centro
  editando el HTML.
- **El fragmento de inicio de sesión no se toca** y el aplicativo lo
  aprovecha entero: quien entra con cargo de dirección tiene su centro puesto
  antes de ver la primera pantalla.
- **El código es publicable y portable**: nombra su clave por defecto y un
  filtro.
- **Administración corrige un código desde la ficha**, sin esperar al
  directorio y sin tocar la base de datos.
- **La unicidad se apoya en un dato de confianza**
  ([ADR-0018](ADR-0018-la-solicitud-es-un-contenido-del-procedimiento.md)).

### Negativas

- **El override dura hasta el siguiente inicio de sesión.** El fragmento del
  sitio sobrescribe la meta cada vez; un código corregido a mano se pierde
  cuando la persona vuelve a entrar, salvo que el fragmento lo respete. Esto
  no lo puede resolver este aplicativo, y hay que decírselo a quien
  administre antes de que lo use.
- **Un paso de configuración fuera del repositorio.** Sin el filtro en
  producción, `code_for()` lee `prc_centre_code`, que nadie escribe, y nadie
  puede solicitar. El diagnóstico de `Admin/Settings` enseña qué clave está
  en uso y cuántas cuentas con `prc_apply` la tienen vacía.
- **Un solo centro por persona.** Quien hoy solicita por dos códigos tendrá
  que hacerlo con dos cuentas o esperar a que se modele; es una regresión
  para un caso que existe y hay que contarla.
- **Fail-closed parece un fallo.** Una cuenta con el rol correcto y sin
  código ve una pantalla que le dice que no tiene centro; si el mensaje no
  dice a quién acudir, la incidencia llega igual.

### Neutras

- El aplicativo no valida que el código exista en el catálogo para
  autorizar; solo para resolver el nombre. Autorizar es tener código.
- La meta con la clave por defecto se registra con `register_meta` para que
  tenga `sanitize_callback` en local; en producción, con otra clave, quien
  la escribe es el fragmento del sitio y el saneado lo hace `code_for()` al
  leer.
- El ámbito de quien gestiona es otro eje con otra meta, `prc_area`, cuya
  clave **no** es configurable: la escribe administración en la ficha, no un
  sistema externo ([ADR-0014](ADR-0014-el-ambito-es-un-alcance-no-un-rol.md)).
