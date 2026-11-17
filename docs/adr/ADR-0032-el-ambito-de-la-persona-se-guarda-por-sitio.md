---
id: ADR-0032
title: "El ámbito de la persona se guarda por sitio"
status: Propuesta
date: 2026-11-17
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0014, ADR-0030]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5-5"
---

# ADR-0032: El ámbito de la persona se guarda por sitio

## Estado

Propuesta (2026-11-17). Cambia **dónde** se guarda el ámbito de la persona que
decidieron la [ADR-0014](ADR-0014-el-ambito-es-un-alcance-no-un-rol.md) y la
[ADR-0030](ADR-0030-editor-y-ambitos-jerarquicos.md); no cambia qué es ni cómo
se decide.

## Contexto

El ámbito de quien gestiona se guarda en la meta de usuario `prc_area`
(`ProcedureAccess::USER_AREA_META`, `src/Prc/Access/ProcedureAccess.php`), con
uno o varios `term_id` de la taxonomía `prc_area`. La escribe la ficha de
usuario (`snippets/roles-and-profiles.php`, `prc_save_profile_fields()`) y la
provisión de demostración (`scripts/seed-demo.php`).

El destino en producción **puede ser un subsitio de un multisitio**
([AGENTS.md](../../AGENTS.md), «Referencia de herramientas»). En un multisitio:

- La tabla de metas de usuario es **una sola para toda la red**
  (`$wpdb->usermeta`); una meta de usuario vale igual en todos los sitios.
- Las taxonomías son **de cada sitio**: cada subsitio tiene sus propias tablas
  de términos, y el mismo `term_id` es un término distinto —o ninguno— en otro
  sitio.

## Problema

Con la meta global, si dos subsitios de la misma red tienen el aplicativo:

1. El `term_id` guardado en el perfil se interpreta en cada sitio contra **su**
   taxonomía: la persona gestiona en el otro sitio un ámbito que nadie le dio,
   o ninguno.
2. Cambiar el ámbito de alguien en un sitio **lo cambia en todos**.

¿Dónde se guarda el ámbito para que valga solo en el sitio donde se asignó,
sin romper las instalaciones que ya lo tienen guardado?

## Factores de decisión

- El acotado por ámbito falla en cerrado (ADR-0014): ningún cambio puede abrir
  acceso por un dato de otro sitio.
- Sin migración al desplegar: el despliegue es pegar código en Code Snippets,
  no hay paso de actualización de datos.
- Usar lo que WordPress ya ofrece antes que inventar un formato.
- Funcionar igual en un sitio único, que es el entorno local y el de los tests.

## Alternativas consideradas

### Opción 1: dejarla global

No cambia nada. Es correcta en un sitio único, pero en un multisitio da los
dos fallos del problema, y el primero es un fallo **abierto**: un `term_id`
de otro sitio puede coincidir con un término real de este. Descartada.

### Opción 2: prefijar la clave a mano

Guardar en `prc_area_{blog_id}` o parecido con `update_user_meta()`. Resuelve
el problema, pero reinventa lo que WordPress ya hace con las opciones de
usuario, con un formato propio que nadie más reconoce y sin lectura de lo que
ya hay guardado: habría que escribir la compatibilidad a mano.

### Opción 3: opción de usuario con `update_user_option()` (elegida)

`update_user_option( $user_id, 'prc_area', $value )` guarda la meta con el
prefijo del sitio, `{$wpdb->get_blog_prefix()}prc_area` (`wp_prc_area` en el
sitio principal, `wp_2_prc_area` en el subsitio 2), y `get_user_option()` la
lee y, **si no existe, cae a la clave sin prefijo**
(`wp-includes/user.php`, `get_user_option()` y `update_user_option()`;
<https://developer.wordpress.org/reference/functions/get_user_option/>). Es el
mecanismo con el que el núcleo guarda por sitio datos de la persona, como sus
capacidades (`wp_capabilities`).

## Decisión

El ámbito se guarda como **opción de usuario por sitio**:

- Se lee con `get_user_option( 'prc_area', $user_id )`, a través de
  `ProcedureAccess::raw_user_areas()`.
- Se escribe con `ProcedureAccess::set_user_areas()` —o, en el snippet suelto,
  que no depende del bundle, con las mismas dos llamadas—:
  `update_user_option()` y, a continuación, `delete_user_meta( $user_id,
  'prc_area' )`, que borra la copia global anterior. Así la persona queda
  migrada la primera vez que administración guarda su ámbito.
- Quien busque personas por ámbito (`WP_User_Query`, SQL) usa
  `ProcedureAccess::user_area_meta_key()`, la clave con el prefijo del sitio, y
  también la clave sin prefijo si tiene que ver a quien aún no se ha migrado.
  Hoy ningún código del aplicativo busca personas por esta meta.
- El diagnóstico de Ajustes marca, en la columna «Solo global», a quien todavía
  tiene el ámbito solo en la meta global
  (`ProcedureAccess::has_only_global_areas()`).

La constante `USER_AREA_META` sigue valiendo `prc_area`: es el nombre de la
opción, no la clave de la fila.

## Consecuencias

### Positivas

- Cada sitio de la red tiene su ámbito, interpretado contra su taxonomía; un
  cambio en un sitio no toca los demás.
- **Sin migración**: lo ya guardado en la meta global se sigue leyendo, y se
  pasa al sitio en cuanto se vuelve a guardar el perfil.
- La opción del sitio gana siempre a la global, también cuando está vacía:
  «Sin ámbito» en un sitio no hereda el de la red, y falla en cerrado.
- En un sitio único se comporta igual que antes, con otra clave en la fila.

### Negativas

- **Mientras no se vuelva a guardar el perfil, la copia global sigue
  valiendo** en todos los sitios de la red que no tengan la suya: el problema
  de partida sigue abierto para esas personas hasta entonces. El diagnóstico
  las señala para poder repasarlas.
- La copia global solo se borra al guardar el ámbito **en un sitio**. Si la
  misma persona tenía un ámbito pensado para otro sitio, ese otro sitio deja de
  leerlo y hay que asignárselo allí.
- **Limitación conocida, que esta ADR no resuelve:** en un multisitio,
  WordPress solo deja editar el perfil de otras personas a quien tiene
  `manage_network_users` (`map_meta_cap()` para `edit_user` y `edit_users`,
  `wp-includes/capabilities.php`), que en una red es de la
  superadministración. La administración
  de un subsitio puede necesitar a la superadministración para asignar
  ámbitos.

### Neutras

- Quien lea la base de datos verá la fila `wp_prc_area` (o
  `wp_N_prc_area`) en lugar de `prc_area`.
- El código de centro y las demás metas de usuario no cambian: no guardan
  identificadores de una taxonomía del sitio.
