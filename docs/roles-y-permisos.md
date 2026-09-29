---
id: ROLES-Y-PERMISOS
title: "Roles y permisos del aplicativo de procedimientos"
status: Propuesta
date: 2026-09-15
related:
  issues: []
  prs: []
  adrs: [ADR-0014, ADR-0015, ADR-0016, ADR-0017, ADR-0018, ADR-0020, ADR-0023]
  sdds: [SDD-0001]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-fable-5-1"
---

# Roles y permisos del aplicativo de procedimientos

## Modelo vigente desde ADR-0030

El rol nativo `editor` es el actor recomendado; `prc_manager` permanece por
compatibilidad. Administración asigna **un** término `prc_area` por persona.
Ese término concede su nodo y todos los descendientes. Una meta histórica con
varios términos válidos no concede acceso hasta que administración la resuelva.
Un `prc_procedure` sí puede pertenecer a **varios** ámbitos convocantes, y
cualquiera de ellos puede editarlo. El taller conserva los ámbitos de otras
ramas al guardar. Solo administración ve y modifica el selector del perfil.

| Capacidad | `editor` | `prc_manager` | `administrator` |
|---|:---:|:---:|:---:|
| `prc_manage_procedures`, edición/publicación de CPT | ✓ acotado | ✓ acotado | ✓ |
| `prc_review_applications` | ✓ acotado | ✓ acotado | ✓ |
| `prc_manage_app`, `prc_manage_all_areas` | — | — | ✓ |

Las secciones siguientes conservan el análisis de la etapa anterior en que
`prc_manager` era el único actor editorial explícito. Donde difieran, rige
el modelo vigente de ADR-0030.

Qué capacidad necesita cada persona, qué abre cada capacidad, qué campo de la
ficha de usuario hace falta para que vea y edite lo que le toca, y cómo
reproducirlo en el editor de roles del sitio donde se despliega. La fuente de
verdad en código está repartida en dos sitios, y conviene saberlo antes de
buscar:

| Qué | Dónde |
|---|---|
| Los dos roles del entorno local y sus capacidades | `prc_register_roles()`, en `snippets/roles-and-profiles.php` |
| Los campos «Ámbitos» y «Código de centro» de la ficha de usuario | El mismo snippet |
| Quién puede hacer qué con cada procedimiento y cada solicitud | `ProcedureAccess`, en `src/Prc/Access/ProcedureAccess.php` |
| El código de centro de la persona | `CentreScope::code_for()`, en `src/Prc/Access/CentreScope.php` |

Están separados a propósito: el snippet de roles es un fichero suelto que se
activa antes que nada (`init` prioridad 5) y el aplicativo **solo comprueba
capacidades**, nunca nombres de rol
([ADR-0015](adr/ADR-0015-el-aplicativo-comprueba-capacidades-no-roles.md)).
Los roles del sitio de destino no aparecen en este repositorio: quien
despliega cuelga las cinco capacidades de los roles que ya tiene, o activa el
snippet y usa los dos que crea.

**El snippet repone capacidades en cada carga.** `prc_register_roles()` crea
el rol si falta y le añade la capacidad si le falta. Nunca llama a
`remove_cap()` ni a `remove_role()`. Las consecuencias, en las dos direcciones:

- **Quitar** una capacidad de esta lista a un rol hay que hacerlo **en el
  código**. Si se quita solo en el editor de roles, vuelve en la siguiente
  carga y nadie se entera de por qué.
- **Añadir** capacidades desde el editor de roles **sí se respeta**: no hay
  revocación genérica que las borre. Es la vía para colgar las capacidades de
  un rol que ya existe en el destino.

---

## Parte A — De dónde se parte (2026-09-15)

Esta parte no describe el aplicativo: describe el sitio que se sustituye tal
y como estaba el día de la foto. El inventario, perfil a perfil, está en
`.local/`, que no se versiona.

Lo que se encontró, en tres líneas:

1. **Quien gestiona una convocatoria tiene un rol y una meta de usuario de
   ámbito**, y el acotado lo hacen **dos fragmentos de código distintos**:
   uno filtra en el servidor la tabla de gestión y otro esconde en el
   navegador lo que no toca. Ni el rol ni la meta se comprueban en un solo
   sitio ni tienen test.
2. **Quien solicita tiene un rol de dirección y una meta de usuario con el
   código de su centro**, y las dos cosas las escribe **otro fragmento al
   iniciar sesión**, consultando el directorio corporativo. Más de un millar
   de cuentas dependen de ese fragmento.
3. **Los formularios se cierran por rol**, no por dato: un formulario de
   solicitud se abre a quien tenga el rol de dirección, y la unicidad «un
   centro, una solicitud» es una clave compuesta escrita en un campo de
   texto que nada garantiza.

Es el motivo de [ADR-0014](adr/ADR-0014-el-ambito-es-un-alcance-no-un-rol.md)
(el ámbito es un alcance de la persona), de
[ADR-0016](adr/ADR-0016-el-centro-de-la-persona-es-una-meta-configurable.md)
(el centro es una meta con clave configurable, para poder leer la de hoy sin
migrar nada) y de
[ADR-0018](adr/ADR-0018-la-solicitud-es-un-contenido-del-procedimiento.md)
(una solicitud por centro, garantizada por código).

---

## Parte B — El modelo del aplicativo

### Roles: dos en local, y ninguno obligatorio en el destino

| Rol (slug) | Etiqueta | Quién | Qué ve | Campo de la ficha imprescindible |
|---|---|---|---|---|
| `prc_manager` | Gestión de procedimientos | Personal de un ámbito que convoca y gestiona las solicitudes | `gestion-de-procedimientos`, `editar-procedimiento`, y el escritorio acotado a sus ámbitos | **Ámbitos** (`prc_area`): uno o varios. Sin ninguno, no ve ni edita nada |
| `prc_school_head` | Dirección de centro | Miembro del equipo directivo de un centro | `solicitud`, `mi-centro`, y el botón «Solicitar» en las fichas | **Código de centro**. Sin él, no solicita nada |
| `administrator` | Administrador | Administración del sitio | Todo, en todos los ámbitos, más «Ajustes» del aplicativo | — |

Los dos primeros los crea `snippets/roles-and-profiles.php` **para el
entorno local y para quien quiera usarlos**. En el sitio de destino no hace
falta crearlos: basta con colgar `prc_apply` del rol que hoy tiene el equipo
directivo y `prc_manage_procedures` y `prc_review_applications` del rol que
hoy tiene quien gestiona. Las pantallas preguntan siempre por capacidad, nunca
por nombre de rol, y por eso ese reparto no cuesta ni una línea de
`ProcedureAccess`.

**`administrator` no se crea**: es el rol de siempre de WordPress y el
aplicativo se limita a colgarle sus dos capacidades propias.

**Y no hay un rol de «administración del aplicativo» ni de «todos los
ámbitos».** Un rol que se distingue de otro solo por saltarse el ámbito no
está nombrando una función: está nombrando *no tener ámbito*, que es lo que
en WordPress ya se llama `administrator`. Si hace falta una persona que llegue
a todos los ámbitos sin administrar el sitio, la vía es concederle
`prc_manage_all_areas` desde el editor de roles a esa cuenta concreta: el
snippet nunca revoca, así que la concesión se respeta y queda visible en el
diagnóstico.

### Capacidades por rol

Son **cinco**, todas del aplicativo. Las columnas se leen «tiene la
capacidad», no «puede hacerlo con cualquier procedimiento»: los dos acotados
son una segunda comprobación y van en la sección siguiente.

| Capacidad | `prc_school_head` | `prc_manager` | `administrator` | Qué abre |
|---|:-:|:-:|:-:|---|
| `prc_apply` | ✓ | | | Presentar y editar la solicitud **de su centro**; las páginas `solicitud` y `mi-centro`; el botón «Solicitar» / «Mi solicitud» |
| `prc_manage_procedures` | | ✓ | ✓ | Crear, editar, publicar y archivar procedimientos **de sus ámbitos**; la página `gestion-de-procedimientos`; los paneles Datos, Preguntas, Enlaces y Publicación del taller; el botón «Gestionar» |
| `prc_review_applications` | | ✓ | ✓ | El panel Solicitudes del taller: ver, admitir, excluir, pedir subsanar con nota y exportar el CSV de esos procedimientos |
| `prc_manage_all_areas` | | | ✓ | Saltarse el acotado por ámbito, y ver los procedimientos que se han quedado sin ámbito |
| `prc_manage_app` | | | ✓ | «Ajustes y diagnóstico»; crear y editar términos de `prc_area` y `prc_course`; los campos «Ámbitos» y «Código de centro» de cualquier ficha de usuario; **desarchivar** un procedimiento histórico |

Y `read`, que llevan los dos roles del snippet.

Las capacidades primitivas de los dos tipos de contenido —`edit_post`,
`delete_post`, `publish_post` y `read_post` sobre `prc_procedure` y
`prc_application`— **no se marcan en ningún rol**: las resuelve
`ProcedureAccess::map_meta_cap()` a partir de estas cinco y de los dos
acotados. En el editor de roles solo hay que marcar las cinco.

Tres precisiones sobre WordPress, que es donde se falla:

- **`prc_manager` no lleva `prc_manage_app`, ni puede llevarla.** Quien
  gestiona **usa** los ámbitos y los cursos que hay; crearlos es administrar el
  aplicativo. El diagnóstico (`prc_roles_status()`, pintado en «Ajustes») avisa
  si la encuentra en un rol que no es el de administración; el snippet nunca la
  quita, así que avisa y quien administra decide.
- **`administrator` no lleva `prc_apply`.** Solicitar es cosa de un centro, y
  la administración no tiene centro. Para probar la solicitud se entra con una
  cuenta de dirección.
- **Despublicar no es una capacidad.** Es un `edit_post` sobre un procedimiento
  publicado, y lo resuelve el mismo `map_meta_cap()` con las mismas reglas que
  editar.

### Qué puede hacer cada cual con cada cosa

Es la tabla que hay que poder repetir sin mirarla. «Su ámbito» es la
intersección entre los ámbitos de la persona (con sus descendientes) y el
ámbito del procedimiento; «su centro», que el código de centro de la persona
coincide con `prc_centre_code` de la solicitud.

| Acción | Quién | Y además |
|---|---|---|
| Ver un procedimiento publicado | Cualquiera | — |
| Ver un procedimiento en borrador | `prc_manage_procedures` en su ámbito, o `prc_manage_all_areas` | — |
| Crear, editar, publicar, despublicar, enviar a la papelera | `prc_manage_procedures` en su ámbito, o `prc_manage_all_areas` | Que **no** esté archivado |
| Marcar como histórico | Los mismos | Con confirmación: no hay vuelta atrás sin administración |
| Desarchivar | Solo `prc_manage_app` | — |
| Presentar una solicitud | `prc_apply` con código de centro | Procedimiento `open`; titularidad del centro admitida; sin solicitud previa del centro (si la hay, se edita esa) |
| Editar su solicitud | `prc_apply` de **ese** centro | Procedimiento `open`; o `amendment` **y** la solicitud en `amend` |
| Ver su solicitud y la nota de revisión | `prc_apply` de ese centro | Siempre |
| Ver las solicitudes de un procedimiento | `prc_review_applications` en su ámbito, o `prc_manage_all_areas` | Que el procedimiento no esté archivado |
| Admitir, excluir, pedir subsanar | Los mismos | Excluir y pedir subsanar exigen nota |
| Exportar el CSV | Los mismos | — |
| Ver una solicitud en el escritorio | Los mismos, más `prc_manage_app` | Solo diagnóstico: `prc_application` no es público |

Lo que cada **estado** del procedimiento permite está en la tabla de
[SDD-0001](sdd/SDD-0001-arquitectura-del-aplicativo-de-procedimientos.md):
esta tabla dice quién; aquella, cuándo.

### El acotado por ámbito

El ámbito convocante es un **término de la taxonomía `prc_area`** puesto en
el procedimiento, y la pertenencia de la persona es la **meta de usuario
`prc_area`** con uno o varios `term_id`. No es un rol:
[ADR-0014](adr/ADR-0014-el-ambito-es-un-alcance-no-un-rol.md).

| Pieza | Dónde |
|---|---|
| Ámbito del procedimiento | término de `prc_area` en el `prc_procedure` |
| Ámbito de la solicitud | el de su procedimiento (`post_parent`); la solicitud no lleva término |
| Ámbitos de la persona | meta de usuario `prc_area`, array de `term_id` |
| Quién escribe ese campo | solo `prc_manage_app` o `edit_users` |
| Quién decide | `ProcedureAccess`, y solo ella |

Cuatro reglas, todas comprobables en el código:

1. **Un término incluye sus descendientes.** `prc_area` es jerárquica
   (servicio → área): quien tiene el servicio en su ficha llega a todas sus
   áreas. Quien tiene un área llega solo a esa.
2. **Falla en cerrado.** Quien tiene `prc_manage_procedures` y **no** tiene
   ningún ámbito en su ficha no crea, no ve y no edita nada, y su listado del
   escritorio sale vacío. Abrir cuando falta el dato es exactamente cómo un
   ámbito acaba tocando los procedimientos de otro.
3. **Un procedimiento sin ámbito solo lo ve `prc_manage_all_areas`.** Pasa
   cuando se borra un término. No queda huérfano: queda en administración.
4. **El campo de la ficha es de administración.** WordPress deja a cualquiera
   editar su propio perfil; si el campo fuera editable, autoasignarse un ámbito
   sería colarse en otro. A quien no puede editarlo se le enseña el valor como
   texto, no como desplegable.

**Dónde se aplica de verdad.** Hay dos capas y hacen cosas distintas:

| Capa | Qué hace | Dónde |
|---|---|---|
| `pre_get_posts` | **Esconde** filas del listado del escritorio | `Admin/ProcedureAdmin.php` |
| `map_meta_cap` | **Deniega** `edit_post`, `delete_post`, `publish_post` y `read_post` en los dos tipos | `Access/ProcedureAccess.php` |

La segunda es la que protege. La primera solo filtra la consulta principal:
el enlace directo a `post.php?post=N`, la edición rápida, la REST y las
acciones en bloque no pasan por ella. La escritura de las metas cuelga también
de la segunda, porque el `auth_callback` de `register_post_meta()` delega en
`ProcedureAccess`.

### El acotado por centro

El centro de la persona es una **meta de usuario** cuya clave devuelve el
filtro `prc_centre_code_meta_key` —por defecto `prc_centre_code`—, y el
aplicativo la lee con `CentreScope::code_for( $user_id )`
([ADR-0016](adr/ADR-0016-el-centro-de-la-persona-es-una-meta-configurable.md)).
La clave es configurable para que el sitio de destino pueda **seguir usando
la meta que hoy escribe su inicio de sesión**, sin migrar nada.

| Pieza | Dónde |
|---|---|
| Código de centro de la persona | meta de usuario, clave por `prc_centre_code_meta_key` |
| Nombre y titularidad del centro | `CentreCatalog::find( $code )`, por el filtro `prc_centres` ([ADR-0017](adr/ADR-0017-el-catalogo-de-centros-no-se-versiona.md)) |
| Centro de la solicitud | metas `prc_centre_code` y `prc_centre_name` (foto en el momento de solicitar) |
| Quién escribe el campo de la ficha | solo `prc_manage_app` o `edit_users` |

Cuatro reglas:

1. **Falla en cerrado.** `prc_apply` sin código de centro no sirve para nada:
   la pantalla dice «Su cuenta no tiene centro asignado» y no ofrece el
   formulario.
2. **El centro no se teclea.** En la solicitud, código y nombre son de solo
   lectura; vienen de la cuenta.
3. **Un centro que no está en el catálogo solicita igual**, con el código y el
   nombre vacío, y quien gestiona lo ve marcado. No se bloquea, porque el
   catálogo puede ir por detrás de la realidad.
4. **La solicitud es del centro, no de la persona.** Dos cuentas con el mismo
   código de centro editan la misma solicitud; quien la presentó es
   `post_author` y sirve para los correos y para las peticiones de acceso.

### El estado «histórico»: el cierre que va por encima del ámbito

Un procedimiento marcado como **histórico** no lo edita su ámbito, ni nadie
con `prc_manage_all_areas`: solo administración
([ADR-0023](adr/ADR-0023-el-estado-historico-cierra-la-edicion.md)). Es la
única regla de este documento que se pone **por encima** del acotado por
ámbito.

| Acción | Quién | Por qué |
|---|---|---|
| **Marcar** como histórico | `prc_manage_procedures` en su ámbito | Es su procedimiento y es quien sabe cuándo ya no queda nada que hacer |
| **Desmarcar** | Solo `prc_manage_app` | Un candado que abre quien lo cerró no es un candado |

Antes de marcar, el taller avisa de que **no hay vuelta atrás sin
administración**. Y mientras está marcado tampoco se gestionan sus
solicitudes: el panel se abre en solo lectura.

**Y no hay ningún cierre por fechas.** Un procedimiento cerrado o resuelto se
sigue editando —enlaces y fechas— y sus solicitudes se siguen gestionando.
`ProcedureState::of()` se usa para pintar y para decidir qué puede hacer el
centro; nunca para negar la edición a quien gestiona.

### Cómo reproducirlo en el editor de roles del destino

1. **Elegir**: activar el snippet `PRC — Roles y perfiles`, que crea
   `prc_manager` y `prc_school_head` con sus capacidades en `init` prioridad 5,
   **o** colgar las cinco capacidades de los roles que ya existen. Las dos
   vías valen; la segunda es la que evita reasignar más de un millar de
   cuentas ([ADR-0015](adr/ADR-0015-el-aplicativo-comprueba-capacidades-no-roles.md)).
2. Marcar las capacidades de la tabla de arriba. Las `prc_*` aparecen en el
   grupo de capacidades personalizadas **una vez que el snippet y el bundle han
   cargado al menos una vez**; si no salen, la opción de añadir una capacidad a
   mano permite escribirlas.
3. Rellenar en la ficha de cada persona su **ámbito** (quien gestiona) o su
   **código de centro** (quien solicita), o comprobar que
   `prc_centre_code_meta_key` apunta a la meta que ya tiene el sitio. Sin eso,
   la capacidad no abre nada: no es un fallo, es la regla del fallo en cerrado.

Quien tenga `prc_manage_app` ve el estado en **Ajustes**: si cada capacidad
está concedida a algún rol, si el catálogo de centros responde y qué versión
del bundle está cargada.

### Cambiar de usuario para probar

En local lo hace el mu-plugin de desarrollo desde la barra superior
(`scripts/mu-plugins/prc-dev-tools.php`). En el destino, el editor de roles
que ya esté instalado suele traer un «cambiar a» equivalente; no se añade
nada propio para esto.

Las cuentas de demostración, todas con contraseña `password`:

| Cuenta | Rol | Ficha |
|---|---|---|
| `admin` | `administrator` | — (ve todo) |
| `gestion` | `prc_manager` | Un ámbito del vocabulario de demostración |
| `gestion2` | `prc_manager` | Otro ámbito: sirve para ver el acotado |
| `direccion` | `prc_school_head` | Un centro del catálogo inventado |
| `direccion2` | `prc_school_head` | Otro centro: sirve para ver que no se ven las solicitudes entre centros |

Para probar el fallo en cerrado hacen falta además una cuenta con
`prc_manage_procedures` **sin** ámbito y otra con `prc_apply` **sin** código
de centro; se crean a mano y son las que más fácil se rompen al tocar
`ProcedureAccess`.

### Qué pasa con los roles de hoy

Nada, y a propósito. El aplicativo no los nombra, no los crea y no los toca
([ADR-0015](adr/ADR-0015-el-aplicativo-comprueba-capacidades-no-roles.md)).
Dos cosas sí quedan pendientes para el despliegue, y están en
[PLAN-0001](plan/PLAN-0001-implantacion-por-fases.md) §9:

- **La meta de centro de hoy** se puede leer tal cual con
  `prc_centre_code_meta_key`, pero no se ha probado en el destino (P-03).
- **La meta de ámbito de hoy** guarda términos de otra taxonomía: no se puede
  leer tal cual. Quien despliega rellena «Ámbitos» con los términos de
  `prc_area`, a mano o con un guion del runbook.
