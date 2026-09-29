---
id: ADR-0015
title: "El aplicativo comprueba capacidades; los roles del sitio de destino no se nombran"
status: Propuesta
date: 2026-09-15
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0001, ADR-0009, ADR-0010, ADR-0014, ADR-0016]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-fable-5-1"
---

# ADR-0015: El aplicativo comprueba capacidades; los roles del sitio de destino no se nombran

## Estado

Propuesta (2026-09-15). Capacidades en `Access/ProcedureAccess`; roles de
conveniencia en `snippets/roles-and-profiles.php`, que va suelto y no entra
en el bundle.

## Contexto

En el sitio de destino **los roles ya existen y los pone otro**. Al iniciar
sesión, un fragmento del sitio consulta el directorio corporativo y, según los
grupos a los que pertenece la persona, le añade o le quita el rol de
**dirección de centro**; hay además un rol de **responsable** de convocatoria
que administración asigna a mano, y una decena más —asesorías, coordinaciones,
perfiles de otros aplicativos— que no son de este dominio. Hay unas 1.350
cuentas con el rol de dirección y unas cien con el de responsable (medido
sobre el material de `.local/`, que no se publica,
[ADR-0009](ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md)).

Esa integración es exactamente el caso en el que **reutilizar roles
funciona**: el rol expresa un cargo, lo mantiene una integración en cada
inicio de sesión, son un puñado y estables. Lo que este aplicativo no puede
hacer es **escribir esos nombres en su código**, por tres razones:

1. **Son un detalle del despliegue.** El slug de un rol de un sitio concreto
   dice cómo está integrado ese sitio con su directorio; publicarlo en un
   repositorio libre es publicar el organigrama de permisos de alguien, y
   quien instale el aplicativo en otro sitio tendrá otros roles o ninguno.
2. **Un rol no es una capacidad, aunque WordPress deje preguntarlo como
   tal.** Varios fragmentos del sitio hacen `current_user_can( 'nombre-de-rol' )`.
   Funciona porque el núcleo resuelve el rol como capacidad implícita, pero
   ata cada comprobación al nombre y no dice qué se está permitiendo.
3. **El aplicativo de eventos ya decidió preguntar por capacidad, nunca por
   rol** ([ADR-0010](ADR-0010-se-parte-del-esqueleto-del-aplicativo-de-eventos.md)),
   y aquí la razón es más fuerte: allí el aplicativo creaba su rol; aquí los
   roles que importan ni siquiera son suyos.

En el entorno local no hay directorio ni fragmento de inicio de sesión: hay
que poder entrar como dirección y como gestión desde el primer `make up`.

## Problema

¿Cómo decide el aplicativo quién puede solicitar, gestionar y revisar, sin
nombrar los roles del sitio de destino y sin obligar a ese sitio a cambiar
los que tiene?

## Factores de decisión

- **Publicable.** Ningún slug de rol ajeno en el código
  ([ADR-0009](ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md)).
- **No tocar lo que ya funciona.** El fragmento que asigna el rol de
  dirección al iniciar sesión no es de este aplicativo y no se modifica.
- **Un solo sitio donde se lee cada permiso**, con nombre que diga qué
  permite.
- **Local y producción con el mismo código.** Lo que cambia es la
  configuración: quién lleva puesta cada capacidad.
- **Aditivo.** Lo que el aplicativo provisione no puede quitar nada a nadie;
  retirar es una decisión de administración.

## Alternativas consideradas

### Opción 1: el código comprueba nombres de rol (el patrón de hoy)

- A favor: cero configuración en producción; los roles ya están puestos.
- En contra: nombra los roles del sitio de destino en un repositorio
  público; no sirve en otro sitio; y `current_user_can( 'rol' )` no dice qué
  se permite. Descartada.

### Opción 2: el aplicativo crea sus roles y el sitio migra a ellos

`prc_school_head` y `prc_manager` como únicos roles válidos; en producción se
reasigna a las 1.350 cuentas y se cambia el fragmento de inicio de sesión para
que ponga el rol nuevo.

- A favor: un solo mapa de roles, el del repositorio.
- En contra: obliga a tocar el fragmento de inicio de sesión, que no es
  nuestro, y a reasignar cuentas que otro sistema mantiene; el día que el
  fragmento vuelva a poner el rol viejo, el nuestro desaparece. Descartada.

### Opción 3: capacidades propias, roles de conveniencia y reparto en producción (elegida)

El aplicativo define capacidades `prc_*` y solo pregunta por ellas. Un snippet
suelto crea dos roles de conveniencia que las llevan. En producción,
administración cuelga las capacidades de los roles que ya hay.

- A favor: publicable; no toca la integración; el mismo código en local y en
  producción; retirar un permiso es quitar una capacidad de un rol en el
  editor de roles.
- En contra: un paso de configuración en producción que hay que documentar y
  comprobar.

### Opción 4: sin roles, solo un filtro `user_has_cap`

Conceder las capacidades al vuelo con un filtro que mire lo que haga falta.

- A favor: sin roles nuevos en la base de datos.
- En contra: el reparto vive en código, invisible en el editor de roles; en
  producción ese código tendría que nombrar los roles del sitio, que es la
  opción 1 con otra puerta. Descartada.

## Decisión

**El aplicativo define cinco capacidades y solo comprueba capacidades. Los
roles del sitio de destino no aparecen en el código. Un snippet suelto crea
dos roles de conveniencia; en producción, administración cuelga las
capacidades de los roles que ya existen.**

Las capacidades, en `Access/ProcedureAccess`:

| Capacidad | Permite | Quién la lleva en local |
|---|---|---|
| `prc_apply` | Presentar y editar la solicitud **de su centro** ([ADR-0016](ADR-0016-el-centro-de-la-persona-es-una-meta-configurable.md)) | `prc_school_head` |
| `prc_manage_procedures` | Crear, editar, publicar y archivar procedimientos **de sus ámbitos** ([ADR-0014](ADR-0014-el-ambito-es-un-alcance-no-un-rol.md)) | `prc_manager` |
| `prc_review_applications` | Ver, admitir, excluir, pedir subsanar y exportar las solicitudes de esos procedimientos | `prc_manager` |
| `prc_manage_all_areas` | Lo anterior en todos los ámbitos | `administrator` |
| `prc_manage_app` | Ajustes, diagnóstico, términos y desarchivar | `administrator` |

Cada una **acota además por dato**: `prc_apply` no sirve sin código de centro
y `prc_manage_procedures` no sirve sin ámbito. La capacidad dice *qué*; la
meta dice *sobre qué*. Ambas fallan en cerrado.

`snippets/roles-and-profiles.php`, suelto y fuera del bundle:

- Crea `prc_school_head` («Dirección de centro») con `read` y `prc_apply`, y
  `prc_manager` («Gestión de procedimientos») con `read`, `upload_files`,
  `prc_manage_procedures` y `prc_review_applications`. Concede
  `prc_manage_all_areas` y `prc_manage_app` a `administrator`.
- Es **aditivo**: nunca llama a `remove_cap()` ni a `remove_role()`. Lo que
  administración conceda a mano en el editor de roles se respeta.
- Añade a la ficha de usuario los campos «Ámbitos» y «Código de centro»,
  editables solo por quien tiene `prc_manage_app`.
- Las capacidades de los tipos de contenido (`edit_prc_procedures`, …) las
  reparte el aplicativo en `init`, no el snippet, para no tener el mismo mapa
  en dos sitios.

En producción, el snippet se instala igual —para los campos de la ficha y para
que `administrator` reciba lo suyo— y administración añade `prc_apply` al rol
de dirección que ya existe y `prc_manage_procedures` +
`prc_review_applications` al rol de responsable, desde el editor de roles del
sitio. Los dos roles de conveniencia quedan creados y vacíos; no hace falta
usarlos. **Ese reparto no se escribe en este repositorio.**

`Admin/Settings` enseña en el diagnóstico qué capacidades existen y qué roles
las llevan, leyendo `wp_roles()`, para que la puesta en marcha se compruebe
sin abrir la base de datos.

## Consecuencias

### Positivas

- **El código es publicable y portable**: nombra sus capacidades y nada más.
- **La integración con el directorio no se toca.** El rol de dirección lo
  sigue poniendo y quitando quien lo hacía, y el aplicativo lo hereda a
  través de una capacidad colgada de él.
- **Cada comprobación dice qué permite.** `prc_review_applications` se lee;
  `current_user_can( 'responsable' )` no.
- **Retirar un permiso es una casilla** en el editor de roles, sin
  desplegar nada.
- **Local y producción corren el mismo código** y solo difieren en qué rol
  lleva cada capacidad.

### Negativas

- **Un paso de puesta en marcha que no está en el código.** Si nadie cuelga
  `prc_apply` del rol de dirección, nadie puede solicitar, y el error no lo
  ve la CI: lo ve el diagnóstico, si alguien lo abre.
- **Dos roles vacíos en producción.** `prc_school_head` y `prc_manager`
  aparecerán en el editor de roles sin nadie dentro, y alguien preguntará
  para qué están.
- **El reparto real vive fuera del control de versiones**: en la base de
  datos del sitio, editado por el editor de roles. Un cambio ahí no deja
  rastro en este repositorio.
- **La capacidad no basta sola.** Una cuenta con `prc_apply` y sin código de
  centro, o con `prc_manage_procedures` y sin ámbito, no puede hacer nada y
  la pantalla tiene que decirle por qué; si no lo dice, parece un fallo.

### Neutras

- `administrator` sigue siendo el de WordPress; no hay rol intermedio entre
  el ámbito y la administración, por las mismas razones que en el aplicativo
  de eventos.
- Los roles de conveniencia llevan slug en inglés y etiqueta en castellano
  ([ADR-0003](ADR-0003-identificadores-internos-en-ingles.md)).
- `scripts/seed-demo.php` crea las cuentas `gestion`, `gestion2`,
  `direccion` y `direccion2` con los roles de conveniencia; son de
  demostración y no existen en producción.
