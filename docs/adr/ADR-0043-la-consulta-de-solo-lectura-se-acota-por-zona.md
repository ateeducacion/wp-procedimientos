---
id: ADR-0043
title: "La consulta de solo lectura se acota por zona con una capacidad y una meta, y la decide el procedimiento"
status: Propuesta
date: 2026-11-10
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0009, ADR-0014, ADR-0015, ADR-0016, ADR-0022, ADR-0028, ADR-0039, ADR-0041, ADR-0042]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5-5"
---

# ADR-0043: La consulta de solo lectura se acota por zona con una capacidad y una meta, y la decide el procedimiento

## Estado

Propuesta. Añade un tercer acotado a los dos de la
[ADR-0014](ADR-0014-el-ambito-es-un-alcance-no-un-rol.md) (ámbito) y la
[ADR-0016](ADR-0016-el-centro-de-la-persona-es-una-meta-configurable.md)
(centro), y una capacidad a las de la
[ADR-0015](ADR-0015-el-aplicativo-comprueba-capacidades-no-roles.md). Las
tres llevan una adenda que remite aquí; ninguna se reescribe.

## Contexto

1. **Hay personas que acompañan a los centros de una zona** y necesitan
   seguir sus solicitudes en un procedimiento: quién ha solicitado, con qué
   coordinación, en qué estado está. No revisan ni deciden nada. Hoy, para
   que puedan verlo, alguien monta a mano una hoja de consulta por
   procedimiento, la comparte y la mantiene al día. Es una de las carencias
   que recoge el
   [PLAN-0003](../plan/PLAN-0003-lo-que-falta-para-sustituir-al-sistema-anterior.md)
   (PR 7) a partir de la revisión de las peticiones del sistema anterior,
   cuyo material está en `.local/` ([ADR-0009](ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md)).
2. **La zona ya está en el catálogo.** Desde la
   [ADR-0042](ADR-0042-la-titularidad-se-deriva-del-tipo-y-el-procedimiento-acota-que-centros-solicitan.md)
   cada centro lleva `zone`, el código de otro centro del catálogo, y la
   tabla del taller ya filtra por ella (PLAN-0003, PR 6). Lo que falta es
   decir **qué zona tiene una persona** y qué puede ver con ella.
3. **Hay quien atiende más de una zona.** Un modelo de una zona por persona
   obligaría a tener dos cuentas.
4. **Lo que hay en una solicitud no es todo de la zona.** La solicitud lleva
   el DNI o NIE de la coordinación y de las personas de la lista
   ([ADR-0041](ADR-0041-las-personas-de-la-solicitud-son-una-lista-que-gestiona-el-centro.md)),
   la nota de revisión —la conversación entre quien gestiona y el centro— y
   el historial. Y cada procedimiento pide cosas distintas: en uno, que la
   zona vea los documentos tiene sentido; en otro, no.
5. **El aplicativo protege en `map_meta_cap`**, no escondiendo
   ([ADR-0014](ADR-0014-el-ambito-es-un-alcance-no-un-rol.md)): una vía de
   lectura nueva tiene que cerrarse ahí y en la descarga de documentos
   ([ADR-0028](ADR-0028-los-adjuntos-de-una-solicitud-son-ficheros-privados.md)),
   o queda abierta por el enlace directo.

## Problema

¿Cómo ve una persona, en solo lectura, las solicitudes de los centros de sus
zonas —y nada más—, sin que eso abra ninguna vía de escritura ni enseñe datos
que no son de la zona, y quién decide qué se enseña?

## Factores de decisión

- **Un solo guardián.** Quién ve qué se decide en `ProcedureAccess`, como el
  ámbito y el centro, y se cierra en `map_meta_cap`.
- **Fallo en cerrado.** Sin zona, nada; con una zona sin centros, nada.
- **Capacidades, no roles** ([ADR-0015](ADR-0015-el-aplicativo-comprueba-capacidades-no-roles.md)):
  el destino cuelga la capacidad del rol que ya tenga.
- **Publicable.** Ni el nombre con que una instalación llama a la zona ni el
  de la meta que ya use se escriben aquí
  ([ADR-0009](ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md)).
- **El dato de la zona no se copia.** Un centro que cambia de zona en el
  catálogo no puede seguir viéndose desde la antigua.
- **Mínimo de datos personales.** La zona acompaña al centro, no revisa a
  sus personas.
- **Reutilizar la tabla del taller**, su filtro, sus páginas y su
  exportación, en vez de una segunda.

## Alternativas consideradas

### Quién: un rol por zona

Un rol `prc_zone_<código>` por cada zona, o una capacidad por zona.

- A favor: la zona se ve en la columna «Rol».
- En contra: es la opción 1 de la
  [ADR-0014](ADR-0014-el-ambito-es-un-alcance-no-un-rol.md) con otro nombre:
  sigue sin decir de quién es cada solicitud —habría que comparar el rol con
  la zona del centro—, crece con cada zona, cada reorganización es un
  despliegue y el código acabaría nombrando zonas de una instalación.
  Descartada.

### Quién: reutilizar el ámbito

Que la zona sea un término de `prc_area`.

- A favor: no hay meta nueva.
- En contra: el ámbito es de quien **convoca** y la zona, del **centro**; un
  procedimiento de un ámbito recibe solicitudes de todas las zonas. Mezclarlo
  daría a quien consulta el taller del procedimiento. Descartada.

### Quién: capacidad más meta de usuario con clave configurable (elegida)

La capacidad `prc_view_applications` dice *qué* puede hacer —leer—; la meta
`prc_zone`, cuya clave devuelve el filtro `prc_zone_meta_key`, dice *sobre
qué*. Es el mismo patrón que el centro
([ADR-0016](ADR-0016-el-centro-de-la-persona-es-una-meta-configurable.md)),
con una diferencia: la meta admite **varias** zonas.

### Qué: lo decide el aplicativo, igual para todos los procedimientos

- A favor: nada que configurar.
- En contra: o se enseña demasiado en los procedimientos que no lo necesitan,
  o demasiado poco en los que sí. Descartada.

### Qué: lo decide cada procedimiento, con una raya que no cruza nadie (elegida)

El procedimiento enciende la consulta, elige columnas y decide si se
descargan documentos y si se exporta. Lo que no es de la zona —DNI, nota,
historial— no se ofrece nunca.

## Decisión

### Quién

- Capacidad **`prc_view_applications`** y rol de conveniencia
  **`prc_observer`** («Consulta de solicitudes»), con esa capacidad y `read`.
  No da escritorio más allá del perfil, ni el taller, ni crear o editar nada.
  El snippet de roles lo crea en local; en el destino basta con colgar la
  capacidad de un rol que ya exista.
- **La zona de la persona** es la meta de usuario `prc_zone`; su clave la
  devuelve `prc_zone_meta_key`. Admite una lista o un texto con códigos
  separados por comas o espacios, y se queda con lo que tiene forma de código
  (`Access/ObserverScope::parse_zones()`). La edita administración en el
  perfil («Zonas de consulta»), con el mismo patrón que el código de centro.
- **Los centros de sus zonas** se leen del catálogo **al decidir**
  (`ProcedureAccess::observer_codes()`): un centro que cambia de zona deja de
  verse desde la antigua sin tocar nada. Una solicitud de profesorado no tiene
  centro y un centro fuera del catálogo no tiene zona: por zona, no se ven.
- **Quien gestiona el ámbito del procedimiento** —y quien tiene todos— ve la
  consulta entera, igual que ve el taller. Quien gestiona otro ámbito, nada.

### Dónde se decide

En `Access/ProcedureAccess`, y en ningún otro sitio:

| Pieza | Qué responde |
|---|---|
| `observer_zones( $user_id )` | Las zonas del perfil |
| `observer_codes( $user_id )` | Los códigos de sus centros, del catálogo de hoy |
| `can_observe( $user_id, $procedure_id = 0, &$scope = null )` | Si abre la consulta de ese procedimiento —o alguna, sin procedimiento— y, en `$scope`, qué ve: vacío es todo, `codes` son esos centros |
| `can_observe_application( $user_id, $application_id )` | Si lee esa solicitud por la consulta |
| `can_view_application( $user_id, $application_id, $documents = false )` | La de siempre, que ahora suma la consulta; con `$documents`, la descarga, que a quien consulta solo se la da `prc_observer_files` |

`Access/ObserverScope` lee la meta, cruza con el catálogo, dice si un
procedimiento admite consulta (`is_observable()`: publicado y con
`prc_observable`), qué cuentas consultan la zona de un centro y qué columnas
no se enseñan nunca. No decide permisos.

`map_meta_cap` da `read_post` sobre las solicitudes de su zona y nada más:
`edit_post`, `delete_post` y `publish_post` siguen siendo de quien revisa.
La REST no publica `prc_application`, el escritorio pide
`edit_prc_applications`, que el rol no tiene, y la descarga pregunta a
`can_view_application( …, true )`: ni enlace directo, ni REST, ni escritorio,
ni exportación, ni URL de documento adivinada abren una solicitud de otra
zona. Las pruebas están en `tests/unit/test-observer-access.php`,
`test-observe.php` y `test-rest-authorization.php`.

### Qué

Metas del procedimiento, todas apagadas por defecto, en el panel «Consulta»
del taller:

| Meta | Qué decide |
|---|---|
| `prc_observable` | Si admite la consulta. Apagada, la pantalla responde lo mismo que a un ID que no existe: no confirma que exista |
| `prc_observer_columns` | Qué columnas de la exportación a medida ([ADR-0039](ADR-0039-la-exportacion-a-medida-y-el-fichero-de-usuarios-viven-en-el-aplicativo.md)) enseña. Vacía, las de solicitud, centro y coordinación |
| `prc_observer_files` | Si quien consulta descarga los documentos |
| `prc_observer_export` | Si exporta a CSV lo que ve |
| `prc_observer_notify` | Si el aviso de personas (`people_changed`) se copia a quienes consultan la zona del centro |

**Lo que no se enseña nunca**, se marque lo que se marque: el DNI o NIE de
la coordinación y de las personas (`coordinator_dni`, `person_dni`) y la nota
de revisión (`note`). No se ofrecen en el panel, el registro de la meta los
quita y la pantalla los vuelve a quitar al leer
(`ObserverScope::FORBIDDEN_COLUMNS`). El historial de la solicitud no está
en la consulta. Sin la columna del estado, tampoco los contadores ni el
filtro por estado; las «incompletas» son de quien revisa.

### La pantalla

`consulta-de-solicitudes`, shortcode `[prc_observe]`
([ADR-0022](ADR-0022-las-pantallas-son-paginas-pintadas-enteras.md)). Sin
procedimiento, los observables publicados en los que la persona tiene algo
que ver, con cuántas solicitudes de sus zonas hay. Con procedimiento, la
tabla con la consulta del taller (`ApplicationQuery::run()` con
`$scope['codes']`): búsqueda, filtros, páginas y, si el procedimiento lo deja,
exportar. El filtro de zona solo ofrece las suyas, porque las opciones salen
de las solicitudes ya acotadas. No hay ningún formulario que escriba; el de
exportar devuelve un fichero y no guarda nada. El fichero de alta de usuarios
no se ofrece.

## Consecuencias

### Positivas

- **Desaparece la hoja de consulta hecha a mano** por procedimiento, y con
  ella las copias de datos personales que nadie actualiza.
- **Un tercer acotado con la misma forma que los otros dos**: capacidad,
  meta, fallo en cerrado, decidido en un sitio y cerrado en `map_meta_cap`.
- **Varias zonas por persona** sin cuentas duplicadas.
- **El procedimiento decide**, y quien lo gestiona ve en el taller qué
  enseña.
- **La zona no se copia**: el catálogo manda, también cuando cambia.

### Negativas

- **Otro dato de perfil que alguien tiene que mantener.** Quien deja de
  atender una zona la conserva hasta que administración la quite; sin una
  fuente automática, es trabajo manual, como el ámbito.
- **Depende del catálogo.** Con el catálogo vacío o sin zonas, la consulta
  no enseña nada. Es fallar en cerrado, pero se lee como un fallo.
- **Las solicitudes de profesorado no se consultan por zona**: no tienen
  centro. Si algún día hace falta, es otra decisión.
- **`can_observe()` devuelve el alcance por referencia.** Es para no partir
  la decisión en dos métodos públicos más en una clase que ya está en el
  umbral de PHPMD; a cambio, la firma es menos corriente.
- **El aviso a la zona multiplica correos** si muchas cuentas consultan la
  misma zona; por eso está apagado por defecto.

### Neutras

- Quien dirige un centro y además consulta una zona tiene las dos cosas sin
  mezclarlas: lee las de su zona y solo escribe en lo de su centro
  (`can_edit_people()` y `can_deliver()` siguen mirando el código del centro).
- Los documentos que se descargan desde la consulta pasan por el mismo
  manejador y las mismas cabeceras que el resto
  ([ADR-0028](ADR-0028-los-adjuntos-de-una-solicitud-son-ficheros-privados.md)).
- `can_read()`, `why_not_code()` y `may_use_area()` pasan a privados: no los
  usaba nadie fuera del guardián, y la prueba de `may_use_area()` pregunta
  ahora por `assign_term`, que es la puerta de verdad.

## Adenda — 2026-11-10 (revisión del PR 7)

La revisión del PR cambió cinco detalles; la decisión no cambia.

- **Lista de permitidas, no de prohibidas.** `ObserverScope::FORBIDDEN_COLUMNS`
  no llega a publicarse: lo sustituye `ObserverScope::ALLOWED_COLUMNS`, una
  lista cerrada y escrita a mano de las columnas que la consulta puede
  enseñar —las que se ofrecían, sin el DNI de nadie ni la nota—, más las de
  las preguntas (`q4`, `q4:r1`; `ObserverScope::is_allowed()`). Una columna
  que otro cambio añada a la exportación no llega a la consulta hasta que
  alguien la escriba en esa lista. La raya «nunca el DNI ni la nota» sigue
  igual; lo que cambia es que fallar es no enseñar.
- **`can_view_application()` dice para qué se pregunta.** El cuarto
  argumento booleano pasa a ser `string $purpose` y `string $question_key`:
  `ProcedureAccess::VIEW_OWN` responde solo si la solicitud es **suya** —de su
  centro o su solicitud individual de profesorado—, y es lo que pregunta el
  correo con la nota de revisión: quien cambió de centro y consulta la zona
  del antiguo ya no la recibe. `ProcedureAccess::VIEW_DOCUMENT` con la
  pregunta del documento es lo que pregunta la descarga: quien consulta solo
  descarga, con `prc_observer_files`, los documentos de preguntas que están
  entre las columnas que la consulta enseña.
- **El aviso a la zona enseña lo mismo que la consulta.** La copia de
  `people_changed` describe a la persona solo con las columnas `person_*` que
  el procedimiento enseña en la consulta; sin ninguna, dice que hubo un alta,
  un cambio o una baja en la lista de ese centro, sin datos de la persona. El
  correo a los contactos no cambia.
- **Guardar el perfil normaliza la meta de zona como lista.** El campo
  «Zonas de consulta» escribe siempre un array de códigos, aunque la meta
  tuviera un texto con comas. Si `prc_zone_meta_key` apunta a una meta que
  escribe otro sistema y espera otro formato, guardar el perfil desde el
  escritorio la reescribe: hay que tenerlo en cuenta antes de apuntar el
  filtro a una meta ajena.
- `ObserverScope::observers_of()` lee solo los ID de las cuentas y su meta de
  zona, en una consulta, sin cargar el resto de sus metas.

Asistencia de IA: Claude Code, claude-opus-5-5.
