---
id: REQ-0001
title: "Requisitos del aplicativo de procedimientos"
status: Propuesta
date: 2026-09-15
related:
  issues: []
  prs: []
  adrs: [ADR-0009, ADR-0011, ADR-0012, ADR-0013, ADR-0014, ADR-0015, ADR-0016, ADR-0017, ADR-0018, ADR-0019, ADR-0020, ADR-0021, ADR-0022, ADR-0023, ADR-0024]
  sdds: [SDD-0001]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-fable-5-1"
---

# REQ-0001 — Requisitos: aplicativo de procedimientos

**Estado:** propuesta. Los requisitos marcados **F1** son el alcance que fija
[SDD-0001](../sdd/SDD-0001-arquitectura-del-aplicativo-de-procedimientos.md)
y que desarrollan las veinticuatro ADR del [registro](../adr/registro.md):
el esqueleto que se está construyendo (fase 0 de
[PLAN-0001](../plan/PLAN-0001-implantacion-por-fases.md)) más las pantallas
completas, los correos y el CSV (fase 1). Los marcados **F2**, **F3** y **F4**
se escriben aquí para que el alcance de F1 se entienda por lo que deja fuera,
y se cerrarán con su propia ADR cuando les toque. Ninguno está entregado: a
esta fecha no hay nada desplegado en el sitio de destino.
**Fecha:** 2026-09-15
**Fuentes:**

- Encargo de la persona usuaria, recogido literal en §2.
- Foto del sistema que se sustituye, tomada el 2026-09-15. El método, el
  inventario y las cifras están en `.local/`, que no se versiona
  ([ADR-0009](../adr/ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md)).
- [SDD-0001](../sdd/SDD-0001-arquitectura-del-aplicativo-de-procedimientos.md),
  que es el contrato de diseño de la fase 0.
- Real Decreto 1112/2018, de accesibilidad de los sitios web del sector
  público, que remite a la norma EN 301 549 (WCAG 2.1 nivel AA).

---

## 1. Propósito

Sustituir el aplicativo de procedimientos actual —un formulario de alta de
**más de un centenar de campos**, otro de solicitud de **casi un centenar**,
**cerca de trescientas** plantillas HTML con condicionales dentro de campos de
texto y **más de un centenar de fragmentos de código** que parchean lo que el
resto no alcanza— por un aplicativo en el que **cada ámbito da de alta,
publica y gestiona sus procedimientos** y **cada centro presenta una solicitud
desde su equipo directivo**, con un estado que se calcula solo, permisos que
se pueden razonar y código que se puede revisar.

Lo que hoy duele, y que este documento traduce a requisitos:

| Síntoma que se cuenta | Lo que hay detrás, medido |
|---|---|
| «Es harto complejo» | Más de un centenar de campos en secciones plegables para editar una convocatoria; una solicitud de casi un centenar de campos, la mitad ocultos y rellenos por búsquedas cruzadas entre cuatro formularios |
| «El estado no se corresponde con las fechas» | El estado es un desplegable de siete valores que alguien tiene que acordarse de cambiar; la leyenda de la pantalla de gestión tiene **ocho** iconos, entre ellos «abierta con el plazo finalizado» y «cerrada con el plazo abierto» |
| «Cada convocatoria con una necesidad nueva cuesta lo mismo que la anterior» | Cuando las cuatro preguntas libres y el subformulario de opciones no bastan, se clona un formulario anexo de una plantilla y tres vistas más, convocatoria a convocatoria |
| «No se puede saber qué puede hacer cada persona» | El ámbito de quien gestiona se filtra por una meta de usuario con dos fragmentos distintos —uno en servidor, otro en el navegador— y el centro de quien solicita lo escribe otro fragmento al iniciar sesión; nada de eso está en un solo sitio ni tiene test |
| «La resolución hay que subirla y mantenerla» | Resolución, corrección y listados son PDF subidos al gestor de formularios, copias de documentos que ya están publicados en otro sitio |

## 2. El encargo, literal, y lo que se deriva de él

> «un entorno donde dar de alta procedimientos para que los centros
> educativos se apunten por parte de los equipos directivos, y puedan
> gestionar la gente que se inscribe, las opciones que se piden en el
> procedimiento, las fechas en las que se activa el procedimiento y el estado,
> los plazos de subsanación y demás, la URL de la resolución —mejor que
> mantener copia—. Ahora mismo es harto complejo y seguro que se puede
> simplificar»

Ocho piezas, y cada una obliga a algo distinto:

| Fragmento | Qué exige | Requisitos |
|---|---|---|
| «dar de alta procedimientos» | El procedimiento es un contenido con modelo de datos, no una entrada de formulario que genera una página ([ADR-0011](../adr/ADR-0011-el-procedimiento-es-un-tipo-de-contenido.md)), y se edita desde una sola pantalla ([ADR-0022](../adr/ADR-0022-las-pantallas-son-paginas-pintadas-enteras.md)) | RF-GES-01 … RF-GES-04 |
| «los centros educativos se apunten» | El sujeto de la solicitud es el **centro**: una por centro y procedimiento ([ADR-0018](../adr/ADR-0018-la-solicitud-es-un-contenido-del-procedimiento.md)) | RF-DIR-01 … RF-DIR-04 |
| «por parte de los equipos directivos» | Quien solicita es una persona con centro asignado; el centro sale de su cuenta, no se teclea ([ADR-0016](../adr/ADR-0016-el-centro-de-la-persona-es-una-meta-configurable.md)) | RF-DIR-05, RF-DIR-06 |
| «gestionar la gente que se inscribe» | Ver, admitir, excluir, pedir subsanar con nota y exportar, desde el propio procedimiento | RF-GES-10 … RF-GES-14 |
| «las opciones que se piden en el procedimiento» | Preguntas configurables por procedimiento sin clonar nada: núcleo fijo más preguntas ([ADR-0019](../adr/ADR-0019-la-solicitud-es-nucleo-fijo-mas-preguntas.md)) | RF-GES-06, RF-GES-07 |
| «las fechas en las que se activa el procedimiento y el estado» | El estado se deriva de las fechas y no puede contradecirse ([ADR-0013](../adr/ADR-0013-el-estado-del-procedimiento-se-deriva-de-las-fechas.md)) | RF-GES-05, RF-VIS-03 |
| «los plazos de subsanación» | Un plazo del procedimiento y un estado de la solicitud ([ADR-0020](../adr/ADR-0020-la-subsanacion-es-un-plazo-y-un-estado.md)) | RF-GES-12, RF-DIR-07 |
| «la URL de la resolución —mejor que mantener copia—» | Resolución y listados son enlaces ([ADR-0021](../adr/ADR-0021-la-resolucion-y-los-listados-son-enlaces.md)) | RF-GES-08 |

Y la frase final —«seguro que se puede simplificar»— es un requisito no
funcional en sí misma: RNF-REN-02 y RNF-OPE-01.

## 3. Actores

| Actor | Slug local | Quién es | De dónde sale su ámbito |
|---|---|---|---|
| Gestión de procedimientos | `prc_manager` | Personal de un ámbito (servicio o área) que convoca, publica y gestiona las solicitudes de sus procedimientos | User meta `prc_area`, uno o varios `term_id` de `prc_area`; un término incluye sus descendientes |
| Dirección de centro | `prc_school_head` | Miembro del equipo directivo de un centro educativo que presenta la solicitud de su centro | La meta de usuario con el código de centro, cuya clave devuelve el filtro `prc_centre_code_meta_key` ([ADR-0016](../adr/ADR-0016-el-centro-de-la-persona-es-una-meta-configurable.md)) |
| Administración del aplicativo | `administrator` | Quien administra el sitio; llega a todos los ámbitos, ajusta, diagnostica y desarchiva | Capacidades `prc_manage_all_areas` y `prc_manage_app` |
| Persona visitante | — | Cualquiera que llega al sitio | — |
| Profesorado participante | — | Docente que se inscribe a título individual | **F3**: el modelo deja el hueco (`prc_audience = teachers`) y no se implementa antes |

Los slugs son los de los dos roles que crea el snippet suelto
`snippets/roles-and-profiles.php` **en el entorno local**. El aplicativo solo
comprueba capacidades, nunca nombres de rol
([ADR-0015](../adr/ADR-0015-el-aplicativo-comprueba-capacidades-no-roles.md)):
en el sitio de destino, las capacidades se cuelgan de los roles que ya tenga
quien despliega, y esos roles no se nombran en este repositorio. El reparto
capacidad a capacidad está en [roles y permisos](../roles-y-permisos.md).

## 4. Qué es un procedimiento, pieza a pieza

Un procedimiento es una convocatoria a la que un centro se inscribe. Esto es
lo que hay hoy y adónde va cada pieza.

| Pieza | Hoy | En el aplicativo | Fase |
|---|---|---|---|
| La convocatoria | Una entrada de un formulario de más de cien campos que, por una acción del gestor de formularios, crea una página del constructor de páginas | `prc_procedure`, con la descripción en `post_content` y el resto en metas registradas | F1 |
| Ámbito y curso | Dos taxonomías registradas por un fragmento de código; el ámbito es un árbol de unas treinta ramas | `prc_area` (jerárquica, eje de permisos) y `prc_course` ([ADR-0012](../adr/ADR-0012-una-taxonomia-por-dimension.md)) | F1 |
| Estado | Un desplegable que escribe en una taxonomía del constructor de páginas, cruzado a mano con la fecha límite | Derivado de las fechas: `draft`, `upcoming`, `open`, `closed`, `amendment`, `resolved`, `archived` ([ADR-0013](../adr/ADR-0013-el-estado-del-procedimiento-se-deriva-de-las-fechas.md)) | F1 |
| Plazo de solicitud | Dos fechas y el estado manual | `prc_opens_at` / `prc_closes_at` | F1 |
| Plazo de subsanación | Dos fechas, un valor del desplegable y una sección del formulario de solicitud | `prc_amend_opens_at` / `prc_amend_closes_at` y el estado `amend` de la solicitud ([ADR-0020](../adr/ADR-0020-la-subsanacion-es-un-plazo-y-un-estado.md)) | F1 |
| Resolución y listados | PDF subidos al gestor de formularios: resolución, corrección, provisional, definitivo y «otro» | Tres enlaces: `prc_resolution_url`, `prc_provisional_list_url`, `prc_final_list_url` ([ADR-0021](../adr/ADR-0021-la-resolucion-y-los-listados-son-enlaces.md)) | F1 |
| Lo que se pregunta al centro | Cuatro pares «título / indicaciones» con respuesta libre, un subformulario de opciones y, si no basta, un formulario anexo clonado | `prc_questions`: hasta 20 preguntas de cuatro tipos ([ADR-0019](../adr/ADR-0019-la-solicitud-es-nucleo-fijo-mas-preguntas.md)) | F1 |
| La solicitud del centro | Una entrada de un formulario de casi cien campos con clave «id de convocatoria · código de centro» escrita en un campo de texto | `prc_application` con `post_parent` = procedimiento, una por centro ([ADR-0018](../adr/ADR-0018-la-solicitud-es-un-contenido-del-procedimiento.md)) | F1 |
| El centro de quien solicita | Meta de usuario escrita al iniciar sesión por un fragmento que consulta el directorio corporativo | Meta de usuario con clave configurable, de solo lectura en la solicitud ([ADR-0016](../adr/ADR-0016-el-centro-de-la-persona-es-una-meta-configurable.md)) | F1 |
| El catálogo de centros | Un cuarto formulario con los centros | Filtro `prc_centres`; por defecto un JSON fuera del repositorio ([ADR-0017](../adr/ADR-0017-el-catalogo-de-centros-no-se-versiona.md)) | F1 |
| Documentación del centro | Subidas al formulario de solicitud: aceptación firmada, proyecto, memoria, otros | Ficheros privados fuera del alcance del servidor web, con su ADR | **F2** |
| Solicitudes del profesorado | Un segundo formulario de solicitud, individual | Audiencia `teachers`, con su ADR | **F3** |
| Lo ya presentado | Miles de entradas y ficheros | No se migra en la fase 1 ([ADR-0024](../adr/ADR-0024-las-solicitudes-de-hoy-no-se-migran.md)) | **F4** |

**Fase**: F1 el alcance de SDD-0001 (fases 0 y 1 del plan) · F2
documentación adjunta · F3 profesorado · F4 migración del histórico y URL. El
calendario y sus criterios de salida están en
[PLAN-0001](../plan/PLAN-0001-implantacion-por-fases.md).

---

## 5. Requisitos por actor

Cada requisito lleva su fase y **cómo se comprueba que está hecho**. Un
requisito sin forma de verificarlo es una intención, no un requisito.

### 5.1 Gestión de procedimientos (`prc_manage_procedures`, `prc_review_applications`)

| ID | Requisito | Fase | Cómo se verifica |
|---|---|---|---|
| RF-GES-01 | Crea, edita, publica y archiva un procedimiento **desde una sola pantalla** con paneles (Datos, Preguntas, Enlaces, Solicitudes, Publicación), sin pasar por el escritorio de WordPress ni por un formulario del sistema anterior | F1 | La página `editar-procedimiento` (`[prc_editor]`) contiene los cinco paneles y el alta termina en un `prc_procedure` en borrador o publicado |
| RF-GES-02 | El alta obligatoria se limita a título, ámbito y curso escolar; **como mucho cinco campos**. Fechas, contacto, preguntas y enlaces se completan después | F1 | Contar los campos obligatorios de la pantalla: **≤ 5**, frente a los más de cien del formulario de edición anterior. Validación en `Domain/ProcedureInput` con su test |
| RF-GES-03 | Ve y edita únicamente los procedimientos cuyo ámbito está entre los de su perfil, o bajo uno de ellos en el árbol. Lo haya creado quien lo haya creado | F1 | Con dos ámbitos y las cuentas `gestion` y `gestion2`, cada una ve solo lo suyo en el taller, en el escritorio, por enlace directo y por REST (`ProcedureAccess`, `map_meta_cap` y `pre_get_posts`) |
| RF-GES-04 | Sin ningún ámbito en el perfil, no ve ni edita nada. El fallo es en cerrado, nunca en abierto | F1 | Cuenta con `prc_manage_procedures` y sin `prc_area`: cero filas y `edit_post` falso |
| RF-GES-05 | **No elige el estado**: escribe las fechas y el estado se calcula. Un procedimiento sin fechas es «próximo» y no admite solicitudes | F1 | No existe ningún campo de estado en el taller; `ProcedureState::of()` cubre los siete estados y los límites de día, con test |
| RF-GES-06 | Define las preguntas del procedimiento en un constructor: hasta 20, de tipo texto, opción única, opción múltiple o sí/no, con ayuda y obligatoriedad, reordenables | F1 | `ProcedureQuestions::sanitize()` rechaza lo que no esté en la lista cerrada; reordenar no cambia la `key` de ninguna pregunta |
| RF-GES-07 | Una necesidad nueva de un procedimiento **no exige clonar nada**: ni formulario, ni vista, ni fragmento de código | F1 | Dar de alta un procedimiento con seis preguntas de los cuatro tipos sin tocar código |
| RF-GES-08 | Enlaza la resolución, el listado provisional y el definitivo como URL. No sube ficheros | F1 | Las tres metas son de tipo URL y el taller no tiene ningún campo de subida |
| RF-GES-09 | Sigue editando enlaces y fechas después del cierre del plazo. Lo que cierra la edición es marcar el procedimiento como **histórico**, a mano, y eso solo lo reabre la administración | F1 | Tabla de estados de SDD-0001; `prc_archived` ([ADR-0023](../adr/ADR-0023-el-estado-historico-cierra-la-edicion.md)) |
| RF-GES-10 | Ve las solicitudes de su procedimiento en una tabla: centro, código, fecha, cargo, coordinación y estado de revisión | F1 | Panel «Solicitudes» del taller con las solicitudes de demostración |
| RF-GES-11 | Admite o excluye una solicitud; excluir exige una nota | F1 | El POST sin nota se rechaza con aviso; `prc_review_state` y `prc_review_note` quedan escritos |
| RF-GES-12 | Pide subsanar con una nota; el centro la ve y solo puede editar durante el plazo de subsanación | F1 | Criterio de aceptación de SDD-0001: `gestion` pide subsanar, `direccion` edita en plazo y no fuera de él |
| RF-GES-13 | Exporta las solicitudes de un procedimiento a CSV, con el núcleo fijo y una columna por pregunta | F1 | La descarga exige `prc_review_applications` sobre ese procedimiento y contiene la solicitud de prueba |
| RF-GES-14 | Ve el número de solicitudes de cada procedimiento en su listado, por curso | F1 | Página `gestion-de-procedimientos` (`[prc_workspace]`) |
| RF-GES-15 | Recibe un correo cuando llega una solicitud a un procedimiento suyo, en el contacto del ámbito | F1 | los `prc_contact_emails` reciben el aviso al presentar; capturado en local |
| RF-GES-16 | Configura qué documentación tiene que aportar el centro (aceptación firmada, proyecto, memoria) y hasta cuándo | F2 | Con su ADR |
| RF-GES-17 | Dirige un procedimiento al profesorado individual en lugar de a los centros | F3 | `prc_audience = teachers`, con su ADR |

### 5.2 Dirección de centro (`prc_apply`)

| ID | Requisito | Fase | Cómo se verifica |
|---|---|---|---|
| RF-DIR-01 | Presenta **una** solicitud por procedimiento en nombre de su centro, mientras el plazo está abierto | F1 | Página `solicitud?procedimiento=ID` (`[prc_apply]`); el POST fuera de plazo se rechaza y la pantalla lo explica |
| RF-DIR-02 | Una segunda solicitud del mismo centro reabre la primera; no hay duplicados | F1 | `Applications::find( $procedure_id, $centre_code )` antes de crear; test de unicidad |
| RF-DIR-03 | Edita su solicitud mientras el plazo esté abierto, y durante la subsanación **solo si** se le ha pedido | F1 | Tabla de estados de SDD-0001, con test por estado |
| RF-DIR-04 | Ve todas las solicitudes de su centro con el estado del procedimiento y el de revisión, y la nota de quien gestiona | F1 | Página `mi-centro` (`[prc_mine]`) |
| RF-DIR-05 | El centro (código y nombre) sale de su cuenta y es **de solo lectura** en la solicitud. No lo teclea ni lo cambia | F1 | El formulario no tiene campo editable de centro; `CentreScope::code_for()` con test |
| RF-DIR-06 | Sin código de centro en su cuenta no puede solicitar, y la pantalla dice por qué («Su cuenta no tiene centro asignado») | F1 | Cuenta con `prc_apply` y sin meta: sin formulario y con el aviso; fallo en cerrado |
| RF-DIR-07 | La solicitud pide solo el núcleo fijo —cargo, aceptación de los compromisos si los hay, persona coordinadora si se pide— y las respuestas a las preguntas del procedimiento | F1 | `Domain/ApplicationInput` con su test; nombre, apellidos y correo de quien solicita **no** se copian: son `post_author` |
| RF-DIR-08 | Un centro de titularidad privada no ve el botón «Solicitar» en un procedimiento solo para públicos, y el POST se rechaza | F1 | `prc_ownership` del procedimiento cruzado con `ownership` del catálogo, con test |
| RF-DIR-09 | Recibe un correo al presentar y al cambiar el estado de revisión | F1 | Capturado en local para los cuatro estados |
| RF-DIR-10 | Sube la documentación que pide el procedimiento, y solo ella y quien gestiona pueden descargarla | F2 | Con su ADR; RNF-PDP-05 |

### 5.3 Administración del aplicativo (`prc_manage_all_areas`, `prc_manage_app`)

| ID | Requisito | Fase | Cómo se verifica |
|---|---|---|---|
| RF-ADM-01 | Hace todo lo de la gestión en **cualquier** ámbito, y ve los procedimientos que se han quedado sin ámbito | F1 | `prc_manage_all_areas` cortocircuita el acotado |
| RF-ADM-02 | Asigna a cada persona sus ámbitos (con la jerarquía visible) y su código de centro desde la ficha de usuario. Nadie más edita esos campos, ni en su propio perfil | F1 | Campos «Ámbitos» y «Código de centro» de `snippets/roles-and-profiles.php`, editables solo por administración |
| RF-ADM-03 | Mantiene el vocabulario de `prc_area` y `prc_course`: el ámbito nuevo cuando se crea un servicio, el curso nuevo cada año | F1 | Alta de término exige `prc_manage_app` |
| RF-ADM-04 | Desarchiva un procedimiento histórico | F1 | Solo `prc_manage_app` ([ADR-0023](../adr/ADR-0023-el-estado-historico-cierra-la-edicion.md)) |
| RF-ADM-05 | Tiene una pantalla de diagnóstico que dice si las capacidades están concedidas, si el catálogo de centros responde y qué versión del bundle está cargada | F1 | `Admin/Settings` |
| RF-ADM-06 | Consulta las solicitudes en el escritorio de WordPress para diagnóstico, sin editarlas desde ahí | F1 | `prc_application` con `show_ui => true` y `public => false` |

### 5.4 Persona visitante

| ID | Requisito | Fase | Cómo se verifica |
|---|---|---|---|
| RF-VIS-01 | Ve el listado de procedimientos por curso —abiertos, en subsanación, próximos, cerrados y resueltos— y lo filtra por ámbito | F1 | Página `procedimientos` (`[prc_home]`) con **una** `WP_Query` |
| RF-VIS-02 | Ve la ficha de un procedimiento: descripción, fechas, estado, ámbito, contacto y enlaces a resolución y listados | F1 | `single` de `prc_procedure` pintado por `ProcedureView` |
| RF-VIS-03 | El estado que se anuncia es correcto sin que nadie lo actualice a mano | F1 | Un procedimiento cuyo plazo terminó ayer aparece como cerrado hoy, sin tocar nada |
| RF-VIS-04 | El botón de la ficha dice lo que puede hacer quien mira: «Solicitar», «Mi solicitud» o «Gestionar», y nada si no puede nada | F1 | Cuatro cuentas, cuatro fichas |
| RF-VIS-05 | Las direcciones publicadas de las convocatorias de hoy siguen respondiendo, o redirigen | F4 | Decisión pendiente, ligada a la migración (RNF-URL-01) |

---

## 6. Requisitos no funcionales

### 6.1 Rendimiento y simplicidad

**Aviso de honestidad, antes de la tabla.** La queja de partida es «harto
complejo», no «lento», y lo que está medido es el **volumen de trabajo**: más
de un centenar de campos para editar una convocatoria, casi un centenar para
solicitar, y un formulario anexo clonado con tres vistas cada vez que las
preguntas de serie no bastan. Nadie ha medido un tiempo de respuesta. Por eso
la simplicidad se mide en campos y pantallas, y el rendimiento empieza por
tomar una línea base.

| ID | Requisito | Fase | Cómo se verifica |
|---|---|---|---|
| RNF-REN-01 | Antes de sustituir nada se toma una **línea base**: campos y pantallas que recorre quien da de alta una convocatoria y quien la solicita, y tiempo de respuesta de la portada y de una convocatoria | F1 | Informe fechado en `docs/verificacion/RENDIMIENTO-AAAA-MM-DD-linea-base.md` |
| RNF-REN-02 | Dar de alta un procedimiento exige **como mucho 5 campos obligatorios** y una pantalla; solicitar, el núcleo fijo más las preguntas | F1 | Recuento directo sobre las dos pantallas |
| RNF-REN-03 | Ninguna pantalla se genera interpretando una plantilla guardada en un campo de texto: todo el HTML lo emite código versionado con test | F1 | Los módulos `View/*` y sus tests |
| RNF-REN-04 | La portada, el listado del taller y la tabla de solicitudes de un procedimiento resuelven con **una** `WP_Query`, sin consultas dentro del bucle | F1 | Query Monitor con los datos de demostración |
| RNF-REN-05 | Las librerías de terceros se cargan desde CDN **con SRI**, y en local desde `node_modules` ([ADR-0006](../adr/ADR-0006-librerias-de-terceros-desde-cdn-con-sri.md)) | F1 | Toda etiqueta `<script>`/`<link>` externa lleva `integrity` |
| RNF-REN-06 | Con unas 300 convocatorias y unas 17.700 solicitudes cargadas, la portada se sirve en menos de **500 ms** sin caché y la tabla de solicitudes de un procedimiento en menos de **1 s**. **Cifras provisionales** hasta que exista la línea base | F4 | Medición repetible con el método de RNF-REN-01 |

### 6.2 Accesibilidad

El sitio es de una administración pública. El **Real Decreto 1112/2018**
obliga a cumplir la norma **EN 301 549**, que remite a **WCAG 2.1 nivel AA**.
No es una buena práctica opcional: es el marco legal que aplica al sitio.

| ID | Requisito | Fase | Cómo se verifica |
|---|---|---|---|
| RNF-ACC-01 | Las cinco pantallas y la ficha del procedimiento cumplen WCAG 2.1 AA | F1 | Auditoría automática (axe-core o equivalente) sin incidencias de nivel A o AA, más el repaso manual de RNF-ACC-02 y RNF-ACC-03 |
| RNF-ACC-02 | Todo el aplicativo se maneja **solo con teclado**, con el foco visible y en el orden de la pantalla; el constructor de preguntas también | F1 | Recorrido manual documentado con capturas en `docs/verificacion/` |
| RNF-ACC-03 | Cada campo tiene su `<label>`; los errores se dicen **en texto**, junto al campo y enlazados con `aria-describedby`; el color nunca es el único portador de información, tampoco en el estado del procedimiento ni en el de revisión | F1 | Revisión del marcado y prueba con el color desactivado |
| RNF-ACC-04 | Contraste mínimo 4,5:1 en texto normal y 3:1 en texto grande y en los distintivos de estado | F1 | Medición sobre la paleta usada |
| RNF-ACC-05 | La ficha pública tiene un solo `<h1>`, jerarquía de encabezados correcta y alternativa textual en el cartel | F1 | Auditoría sobre un procedimiento de demostración |
| RNF-ACC-06 | **Las fechas, el plazo y el estado existen como texto**, no solo en una cuenta atrás ni dentro del cartel | F1 | Un lector de pantalla llega al plazo de cualquier procedimiento |
| RNF-ACC-07 | Las confirmaciones (SweetAlert2) y todo POST funcionan sin JavaScript, con `confirm()` o con el formulario a secas | F1 | Recorrido con JavaScript desactivado |
| RNF-ACC-08 | Lo que **no** se hace: no se auditan los PDF enlazados desde la resolución y los listados, que son de quien los publica, ni las páginas del sistema anterior | — | Exclusión declarada de alcance |

### 6.3 Protección de datos

| ID | Requisito | Fase | Cómo se verifica |
|---|---|---|---|
| RNF-PDP-01 | La fase 1 **no migra ni un solo dato personal**: ni solicitudes ni documentos ya presentados ([ADR-0024](../adr/ADR-0024-las-solicitudes-de-hoy-no-se-migran.md)) | F1 | El bundle no lee ninguna tabla del gestor de formularios |
| RNF-PDP-02 | La solicitud guarda **lo mínimo**: quien solicita es `post_author` y no se copian su nombre, apellidos ni correo; de la persona coordinadora, solo nombre y correo, y solo si el procedimiento la pide | F1 | Revisión de `ApplicationMetaKeys`: ninguna meta de datos personales fuera de `prc_coordinator` |
| RNF-PDP-03 | Una solicitud la lee su autoría, quien gestiona el ámbito del procedimiento y la administración. Nadie más, tampoco por REST ni por enlace directo | F1 | `map_meta_cap` sobre `read_post` de `prc_application`, con test por cada camino |
| RNF-PDP-04 | El repositorio no contiene datos personales ni el catálogo real de centros. El material crudo vive en `.local/`, con doble candado | F0 | `git ls-files .local` vacío; `make check-public` en verde |
| RNF-PDP-05 | Los documentos del centro se guardan **fuera del alcance del servidor web** y se sirven previa comprobación de capacidad; nunca por URL adivinable | F2 | Descargar un documento sin capacidad devuelve 403 |
| RNF-PDP-06 | Antes de migrar una sola solicitud hace falta el análisis del tratamiento: base jurídica, finalidad, plazo de conservación y quién accede | F4 | Documento previo, referenciado desde la ADR de la fase 4 |
| RNF-PDP-07 | Una petición de acceso o de supresión se resuelve con una consulta por `post_author` | F1 | Prueba con una persona con solicitudes en dos procedimientos |

### 6.4 Direcciones

| ID | Requisito | Fase | Cómo se verifica |
|---|---|---|---|
| RNF-URL-01 | Se decide qué pasa con las direcciones públicas de las convocatorias de hoy: se conservan, se redirigen o caducan. **Está pendiente** y ligado a la migración del histórico | F4 | ADR de la fase 4 |
| RNF-URL-02 | Un procedimiento nuevo se sirve en `/procedimiento/<slug>/`, y el segmento es configurable con el filtro `prc_procedure_rewrite_slug` | F1 | Un procedimiento de demostración responde en esa dirección |
| RNF-URL-03 | Despublicar no libera ni cambia el slug: al republicar, la URL es la de antes | F1 | Publicar → despublicar → republicar deja `post_name` e `ID` intactos |
| RNF-URL-04 | Las URL nuevas se escriben en castellano; los identificadores, en inglés ([ADR-0003](../adr/ADR-0003-identificadores-internos-en-ingles.md)) | F1 | `Shell::SLUGS` |

### 6.5 Coste de operación

| ID | Requisito | Fase | Cómo se verifica |
|---|---|---|---|
| RNF-OPE-01 | Un procedimiento nuevo **no cuesta código**: ni formulario, ni vista, ni fragmento. Hoy cuesta eso cada vez que las preguntas de serie no bastan | F1 | RF-GES-07 |
| RNF-OPE-02 | Toda la lógica vive en un único fichero desplegable, generado desde el repositorio y con su versión visible, más el snippet suelto de roles | F0 | `snippets/prc-procedimientos-app.bundle.php` y `make bundle` |
| RNF-OPE-03 | El catálogo de centros y el código de centro de cada persona los aporta quien despliega, por filtro o por fichero fuera del repositorio; el aplicativo no los conoce | F1 | `prc_centres` y `prc_centre_code_meta_key` ([ADR-0017](../adr/ADR-0017-el-catalogo-de-centros-no-se-versiona.md)) |

### 6.6 Seguridad

| ID | Requisito | Fase | Cómo se verifica |
|---|---|---|---|
| RNF-SEG-01 | La autorización se decide en **un solo sitio** (`Access/ProcedureAccess`) y sobre `map_meta_cap`, no escondiendo filas: el taller, el escritorio, un enlace directo y la REST API dan el mismo resultado | F1 | Test por cada camino y por cada capacidad |
| RNF-SEG-02 | Los dos acotados —ámbito y centro— fallan en cerrado | F1 | RF-GES-04 y RF-DIR-06 |
| RNF-SEG-03 | Toda escritura va por POST con nonce y comprobación de capacidad; ninguna por GET | F1 | Revisión de los manejadores de `PublicFront/*` |
| RNF-SEG-04 | El aplicativo **no concede `unfiltered_html`** ni lo menciona | F1 | Búsqueda en `src/Prc/` y en `snippets/roles-and-profiles.php`: cero coincidencias |
| RNF-SEG-05 | El fallo del snippet no rompe el sitio: `App::boot()` es idempotente y todo va detrás de `function_exists`/`class_exists` donde toca | F0 | Activar el bundle dos veces no duplica nada |

---

## 7. Fuera de alcance

Lo que este aplicativo **no** hace, dicho para que no se dé por supuesto:

| Qué | Por qué |
|---|---|
| Certificaciones del profesorado participante | Es otro flujo, con su propio formulario y decenas de miles de entradas. Si se trae, entra después de F3 y con su ADR |
| Impresión de la solicitud en PDF | Hoy es un paso obligado para poder subir la aceptación firmada; con F2 se decide si sigue haciendo falta |
| Registro de incidencias y tutoriales | Páginas de ayuda del sitio; no son del aplicativo |
| Estadísticas de participación | Pendiente de SDD-0001; no tiene fase |
| Recordatorios de cierre de plazo | Ídem |
| Petición de un procedimiento nuevo por parte de un ámbito | Hoy es un formulario de contacto; sigue siendo un correo |
| Retirar el gestor de formularios | Mientras haya solicitudes y documentos de hoy sin migrar, se queda (F4) |

## 8. Criterios de aceptación de F1

Ninguno está cumplido a 2026-09-15. La casilla se marca cuando hay evidencia,
no cuando hay código. Son los de SDD-0001 más los de las pantallas completas:

- [ ] `make install && make up` levanta el sitio con roles, vocabulario,
      páginas y datos de demostración; `make check` en verde.
- [ ] Con la cuenta `gestion` se crea, publica y archiva un procedimiento de
      su ámbito y no se ve ninguno de otro ámbito, ni por el taller, ni por el
      escritorio, ni por enlace directo, ni por la REST API.
- [ ] Con la cuenta `direccion` se presenta una solicitud a un procedimiento
      abierto, se edita, y una segunda solicitud del mismo centro reabre la
      primera.
- [ ] `gestion` pide subsanar con nota; `direccion` ve la nota y edita solo
      durante el plazo de subsanación; `gestion` admite; el CSV incluye la
      solicitud con una columna por pregunta.
- [ ] El estado de cada procedimiento coincide con la tabla de SDD-0001 para
      cualquier día que se pase a `ProcedureState::of()`.
- [ ] Al presentar y al cambiar el estado de revisión salen los correos, y
      llegan a quien toca.
- [ ] Sin ámbito en el perfil no se ve nada; sin código de centro no se
      solicita nada; las dos pantallas dicen por qué.
- [ ] La auditoría de accesibilidad de las pantallas no tiene incidencias A
      ni AA, y todo funciona sin JavaScript.
- [ ] Existe la línea base (RNF-REN-01).
- [ ] `make bundle` pasa `php -l` y `make snippet-check`; `make check-public`
      en verde.

## 9. Lo que no se sabe

Se escribe aquí para que no se pierda, y porque un requisito construido sobre
una suposición es una avería aplazada.

| # | Qué falta por saber | A qué requisito afecta | Cómo se cierra |
|---|---|---|---|
| 1 | Cómo llega el **catálogo real de centros** al sitio de destino: un JSON en `uploads/prc/` que alguien mantiene, o un filtro que consulta otro sistema. Y cada cuánto se refresca | RNF-OPE-03, RF-DIR-08 | Decisión de quien despliega, anotada en el runbook |
| 2 | Cómo se rellena el **código de centro** de cada persona en el sitio de destino. Hoy lo escribe un fragmento al iniciar sesión, en una meta con otra clave; el filtro `prc_centre_code_meta_key` permite leer esa misma meta sin migrar nada, pero **no se ha probado** | RF-DIR-05, RF-DIR-06 | Prueba en el sitio de destino con una cuenta real |
| 3 | Qué se hace con las **direcciones públicas** de las convocatorias de hoy | RF-VIS-05, RNF-URL-01 | ADR de la fase 4 |
| 4 | Si **17.700 solicitudes** como `prc_application` con `post_parent` y metas rinden bien en las consultas del taller y del CSV | RNF-REN-06 | Medición con datos sintéticos de ese volumen antes de la fase 4 |
| 5 | Quién envía los **correos** en el sitio de destino y con qué remitente; el entorno local no tiene servidor de correo | RF-GES-15, RF-DIR-09 | Comprobación en el runbook de despliegue |
| 6 | Si los **ocho cargos** de hoy se reducen a los cinco de `ApplicationMetaKeys::positions()` sin que nadie se quede fuera; «otro» recoge el resto | RF-DIR-07 | Revisión con quien gestiona antes de la fase 1 |
| 7 | Cuántos de los procedimientos de hoy necesitan **más de 20 preguntas** o un tipo que no esté en la lista | RF-GES-06 | Recuento sobre el material de `.local/` |

## 10. Trazabilidad

| Bloque de requisitos | Decisión que lo sostiene |
|---|---|
| RF-GES-01, RF-GES-02 | [ADR-0011](../adr/ADR-0011-el-procedimiento-es-un-tipo-de-contenido.md), [ADR-0022](../adr/ADR-0022-las-pantallas-son-paginas-pintadas-enteras.md) |
| RF-ADM-03, RF-VIS-01 | [ADR-0012](../adr/ADR-0012-una-taxonomia-por-dimension.md) |
| RF-GES-05, RF-VIS-03 | [ADR-0013](../adr/ADR-0013-el-estado-del-procedimiento-se-deriva-de-las-fechas.md) |
| RF-GES-03, RF-GES-04, RF-ADM-01, RF-ADM-02 | [ADR-0014](../adr/ADR-0014-el-ambito-es-un-alcance-no-un-rol.md) |
| §3 Actores | [ADR-0015](../adr/ADR-0015-el-aplicativo-comprueba-capacidades-no-roles.md) |
| RF-DIR-05, RF-DIR-06 | [ADR-0016](../adr/ADR-0016-el-centro-de-la-persona-es-una-meta-configurable.md) |
| RF-DIR-08, RNF-OPE-03 | [ADR-0017](../adr/ADR-0017-el-catalogo-de-centros-no-se-versiona.md) |
| RF-DIR-01 … RF-DIR-04, RNF-PDP-03 | [ADR-0018](../adr/ADR-0018-la-solicitud-es-un-contenido-del-procedimiento.md) |
| RF-GES-06, RF-GES-07, RF-DIR-07 | [ADR-0019](../adr/ADR-0019-la-solicitud-es-nucleo-fijo-mas-preguntas.md) |
| RF-GES-12, RF-DIR-03 | [ADR-0020](../adr/ADR-0020-la-subsanacion-es-un-plazo-y-un-estado.md) |
| RF-GES-08 | [ADR-0021](../adr/ADR-0021-la-resolucion-y-los-listados-son-enlaces.md) |
| RF-GES-09, RF-ADM-04 | [ADR-0023](../adr/ADR-0023-el-estado-historico-cierra-la-edicion.md) |
| RNF-PDP-01, RNF-URL-01 | [ADR-0024](../adr/ADR-0024-las-solicitudes-de-hoy-no-se-migran.md) |
| RNF-PDP-04 | [ADR-0009](../adr/ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md) |

El orden en que se construye todo esto, con sus criterios de salida, está en
[PLAN-0001](../plan/PLAN-0001-implantacion-por-fases.md).
