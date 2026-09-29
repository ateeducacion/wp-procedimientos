---
id: PLAN-0001
title: "Implantación por fases del aplicativo de procedimientos"
status: Propuesta
date: 2026-09-15
related:
  issues: []
  prs: []
  adrs: [ADR-0001, ADR-0004, ADR-0009, ADR-0010, ADR-0013, ADR-0016, ADR-0017, ADR-0018, ADR-0019, ADR-0020, ADR-0024]
  sdds: [SDD-0001]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-fable-5-1"
---

# PLAN-0001 — Plan de implantación por fases

**Estado:** propuesta. La **fase 0 está en curso**: es el esqueleto que fija
[SDD-0001](../sdd/SDD-0001-arquitectura-del-aplicativo-de-procedimientos.md)
y se está escribiendo a la vez que este plan. Las fases 1 a 4 son el orden
propuesto, no una lista de tareas en marcha: a 2026-09-15 no hay nada
desplegado en el sitio de destino y no se ha tocado nada allí.
**Fecha:** 2026-09-15
**Basado en:** [REQ-0001](../requisitos/REQ-0001-aplicativo-de-procedimientos.md),
`.local/` (investigación del sistema anterior),
[SDD-0001](../sdd/SDD-0001-arquitectura-del-aplicativo-de-procedimientos.md)
y las veinticuatro ADR del [registro](../adr/registro.md).

---

## 1. Objetivo del plan

Decir **en qué orden** se sustituye el aplicativo de procedimientos, qué
entrega cada fase, **con qué evidencia se da por terminada** y qué puede salir
mal en cada una. Lo que hay que hacer está en REQ-0001; por qué se hace así,
en las ADR; el diseño, en SDD-0001. Aquí solo va la secuencia.

El plan tiene un eje: **cada fase deja el sitio funcionando**. El sistema
anterior sigue en pie hasta la fase 4 y ninguna fase lo toca.

## 2. Principios

1. **Nada se toca en producción desde este repositorio.**
   [ADR-0001](../adr/ADR-0001-repo-entorno-desarrollo-no-plugin.md). Lo que
   aquí se prepara se despliega activando dos snippets, con su ventana y su
   vuelta atrás.
2. **Una fase = un problema.** La fase que abarca dos no se entrega.
3. **Primero lo que duele.** El alta del procedimiento, el estado derivado y
   la solicitud del centro. La documentación adjunta, el profesorado y el
   histórico funcionan hoy donde están
   ([ADR-0024](../adr/ADR-0024-las-solicitudes-de-hoy-no-se-migran.md)).
4. **Reversible antes que rápido.** Ningún paso borra nada hasta que el
   siguiente esté en pie.
5. **Sin medida no hay mejora.** La línea base se toma antes de sustituir
   nada (REQ-0001 RNF-REN-01).
6. Cada entrega de código pasa `make check` y regenera el bundle sin
   diferencias ([ADR-0004](../adr/ADR-0004-ci-y-politica-de-pruebas.md)).
7. **Nada de nadie en el repositorio.** Cada PR pasa `make check-public`
   ([ADR-0009](../adr/ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md)).

## 3. Las fases de un vistazo

| Fase | Qué entrega | Estado | Depende de |
|---|---|---|---|
| **0** | Esqueleto: modelo de datos, estado derivado, permisos, las cinco pantallas funcionando en su versión mínima, entorno y tests | **En curso** (2026-09-15) | — |
| **1** | Pantallas completas, correos, CSV completo, línea base y auditoría de accesibilidad; runbook de despliegue | Propuesta | Fase 0 |
| **2** | Documentación adjunta del centro: ficheros privados con descarga por capacidad | Propuesta | Fase 1, y su ADR |
| **3** | Procedimientos dirigidos al profesorado individual (`prc_audience = teachers`) | Propuesta | Fase 1, y su ADR |
| **4** | Migración del histórico —convocatorias, solicitudes y documentos— y decisión sobre las URL de hoy; retirada del camino viejo | Propuesta | Fases 2 y 3, el análisis de protección de datos y su ADR |

```text
Fase 0 — esqueleto  ◐ en curso
   └─► Fase 1 — pantallas completas, correos, CSV
         ├─► Fase 2 — documentación adjunta
         │        └─► Fase 4 — migración y retirada
         └─► Fase 3 — profesorado
                  └─► Fase 4 — migración y retirada
```

### 3.1 Nota sobre la numeración

**Esta tabla es la numeración canónica.** Ningún otro documento puede usar
otra. Las ADR y SDD-0001 hablan de «fase 1» como el conjunto de las fases 0
y 1 de aquí —el alcance que fija SDD-0001—, y de «fase 2», «fase 3» y
«fase 4» con el mismo significado que esta tabla. REQ-0001 etiqueta sus
requisitos igual: **F1** cubre las fases 0 y 1.

---

## 4. Fase 0 — Esqueleto · **EN CURSO**

**Objetivo.** Que exista todo lo que SDD-0001 describe, funcione con datos de
demostración y tenga test, aunque cada pantalla esté en su versión mínima. Es
la fase en la que se decide la forma; la fase 1 la rellena.

### Entregables

| # | Entregable | Dónde |
|---|---|---|
| 0.1 | Entorno: wp-env en los puertos **8698** (desarrollo) y **8699** (tests), PHP 8.3, `es_ES`, Code Snippets; `make up` provisiona sin pasos manuales | `.wp-env.json`, `Makefile`, `scripts/` |
| 0.2 | Empaquetado: `src/Prc/` → un único Code Snippet con la guarda `PRC_BUNDLE_LOADED`; el snippet suelto de roles | `build/pack-snippet.php`, `snippets/` |
| 0.3 | Modelo de datos: `prc_procedure`, `prc_application`, `prc_area`, `prc_course`, metas registradas con `sanitize_callback` y `auth_callback` | `src/Prc/PostType/`, `Taxonomy/`, `Meta/` |
| 0.4 | Dominio puro con test: estado derivado, preguntas y respuestas, validación de procedimiento y solicitud, catálogo de centros | `src/Prc/Domain/` |
| 0.5 | Permisos: `ProcedureAccess` como guardián único, acotado por ámbito y por centro, fallo en cerrado, `map_meta_cap` y `pre_get_posts` | `src/Prc/Access/` |
| 0.6 | Las cinco pantallas y la ficha, pintadas enteras: `home`, `workspace`, `editor` con sus cinco paneles, `apply`, `mine`, `single` | `src/Prc/PublicFront/` |
| 0.7 | Escritorio: columnas, filtro por ámbito, acotado del listado; ajustes y diagnóstico | `src/Prc/Admin/` |
| 0.8 | Provisión: roles, vocabulario de demostración, páginas, cuentas `gestion`, `gestion2`, `direccion`, `direccion2`, procedimientos en cada estado y solicitudes en cada estado de revisión; catálogo inventado por `prc_centres` | `scripts/*.php`, `scripts/mu-plugins/prc-dev-tools.php` |
| 0.9 | Pruebas: un fichero por módulo en `tests/unit/`; CI con `make check` | `tests/`, `.github/workflows/` |
| 0.10 | Documentación: 24 ADR, SDD-0001, REQ-0001, este plan, roles y permisos, guía para empezar | `docs/` |

### Criterios de salida

Son los criterios de aceptación de SDD-0001:

- [ ] `make install && make up` levanta el sitio con roles, vocabulario,
      páginas y datos de demostración; `make check` en verde.
- [ ] Con `gestion` se crea, publica y archiva un procedimiento de su ámbito
      y no se ve ninguno de otro ámbito.
- [ ] Con `direccion` se presenta una solicitud a un procedimiento abierto,
      se edita, y una segunda solicitud del mismo centro reabre la primera.
- [ ] `gestion` pide subsanar con nota; `direccion` ve la nota y edita solo
      durante el plazo de subsanación; `gestion` admite; el CSV incluye la
      solicitud.
- [ ] El estado de cada procedimiento coincide con la tabla de SDD-0001 para
      cualquier día que se pase a `ProcedureState::of()`.
- [ ] `make bundle` pasa `php -l` y `make snippet-check`; `make check-public`
      en verde.

### Lo que deja sin resolver, a propósito

| # | Qué falta | Consecuencia |
|---|---|---|
| 1 | Correos: ni al presentar ni al cambiar el estado de revisión | Fase 1 |
| 2 | El constructor de preguntas y la tabla de solicitudes existen en su forma mínima: sin reordenar con el ratón, sin filtros, sin paginación | Fase 1 |
| 3 | El CSV lleva el núcleo fijo; las columnas por pregunta y el acotado por ámbito se comprueban en la fase 1 | Fase 1 |
| 4 | No hay línea base ni auditoría de accesibilidad | Fase 1 |
| 5 | El catálogo de centros de local es inventado; el real no existe en ningún sitio del repositorio ([ADR-0017](../adr/ADR-0017-el-catalogo-de-centros-no-se-versiona.md)) | Quien despliega lo aporta; P-02 |
| 6 | El entorno local es de un solo sitio, sin el gestor de formularios ni el inicio de sesión corporativo del destino | No se puede ensayar en local ni la convivencia ni el código de centro real; P-03, P-04 |

---

## 5. Fase 1 — Pantallas completas, correos y CSV

**Objetivo.** Que una persona de un ámbito lleve un procedimiento de principio
a fin —alta, publicación, solicitudes, subsanación, resolución, histórico— y
un centro lo solicite, sin abrir el sistema anterior, y que a cada paso llegue
el correo que toca. Es la fase que responde al encargo literal de REQ-0001 §2.

### Entregables

Cada entregable es un PR, o varios pequeños; ninguno mezcla dos filas.

| # | PR | Requisitos que cierra |
|---|---|---|
| 1.1 | **Correos**: al presentar (a quien solicita y a los `prc_contact_emails`) y al cambiar el estado de revisión (a quien solicita), con plantillas de texto plano y test de que se envían a quien toca | RF-GES-15, RF-DIR-09 |
| 1.2 | **Constructor de preguntas completo**: reordenar, ayuda por pregunta, previsualización de cómo lo ve el centro; `key` estable al reordenar | RF-GES-06 |
| 1.3 | **Tabla de solicitudes completa**: filtro por estado de revisión, búsqueda por centro, paginación, marca de «centro fuera del catálogo» | RF-GES-10 |
| 1.4 | **CSV completo**: una columna por pregunta, coordinación, nota de revisión; acotado por ámbito y con test de que quien no gestiona el procedimiento no descarga | RF-GES-13, RNF-SEG-01 |
| 1.5 | **Avisos y motivos en castellano** en cada rechazo: fuera de plazo, sin centro, centro privado, segunda solicitud, procedimiento histórico; con test por motivo | RF-DIR-01, RF-DIR-06, RF-DIR-08 |
| 1.6 | **Bloqueo de edición** del procedimiento con el de WordPress ([ADR-0008](../adr/ADR-0008-bloqueo-de-edicion-con-el-de-wordpress.md)) y aviso en el taller | — |
| 1.7 | **Portada completa**: filtro por ámbito con la jerarquía, pestañas por curso, tarjeta con estado, fechas y ámbito; una `WP_Query` | RF-VIS-01, RNF-REN-04 |
| 1.8 | **Sin JavaScript**: recorrido de todas las pantallas con JavaScript desactivado y corrección de lo que falle | RNF-ACC-07 |
| 1.9 | **Línea base** del sistema actual y **auditoría de accesibilidad** de las pantallas nuevas, ambas fechadas en `docs/verificacion/` | RNF-REN-01, RNF-ACC-01 … RNF-ACC-06 |
| 1.10 | `docs/despliegue-procedimientos.md`: el runbook de despliegue al sitio de destino, con la ventana, la copia previa, cómo se cuelgan las capacidades de los roles existentes, cómo llega el catálogo de centros y la vuelta atrás | P-02, P-03, P-05 |
| 1.11 | Petición de acceso y supresión: consulta por `post_author` documentada y probada | RNF-PDP-07 |

### Criterios de salida

Son los criterios de aceptación de
[REQ-0001 §8](../requisitos/REQ-0001-aplicativo-de-procedimientos.md), y
además:

- [ ] Un procedimiento con seis preguntas de los cuatro tipos recibe tres
      solicitudes y el CSV las devuelve con una columna por pregunta.
- [ ] Los cuatro correos —presentar, subsanar, admitir, excluir— salen y
      llegan a quien toca, capturados en local.
- [ ] Las cuatro cuentas de demostración no se ven entre ellas: `gestion` y
      `gestion2` por ámbito, `direccion` y `direccion2` por centro; ni por el
      taller, ni por enlace directo, ni por la REST API.
- [ ] Todo funciona con JavaScript desactivado.
- [ ] `make check` en verde, bundle regenerado sin diferencias y el
      porcentaje de cobertura del parche por encima del suelo.

### Riesgos

| Riesgo | Por qué es real | Mitigación |
|---|---|---|
| El taller crece hasta parecerse al formulario que sustituye | Son cinco paneles y la tentación de añadir «un campo más» por cada convocatoria que lo pide | El alta obligatoria son ≤ 5 campos (RF-GES-02); cada campo nuevo pasa por REQ-0001 y, si es duradero, por ADR |
| Los correos no salen en el destino | El entorno local no tiene servidor de correo y el destino tiene el suyo | Test de que se llama a `wp_mail()` con los destinatarios correctos; comprobación real en el runbook (1.10) |
| El código de centro del destino no está donde el aplicativo lo lee | Hoy lo escribe un fragmento en una meta con otra clave | `prc_centre_code_meta_key` está para eso; se prueba en el destino antes de nada (1.10) |
| Reimplementar la vista de hoy | Son 24 KB de plantilla con cuenta atrás, pestañas y condicionales | La ficha pinta lo **estructural**: descripción, fechas, estado, enlaces y el botón que toca |
| El constructor de preguntas se vuelve un gestor de formularios | Es la puerta por la que volverían los formularios anexos | Cuatro tipos y veinte preguntas, cerrados en `ProcedureQuestions`; lo que no quepa es una ADR, no un tipo más |

---

## 6. Fase 2 — Documentación adjunta del centro

**Objetivo.** Que el centro suba lo que el procedimiento le pida —aceptación
firmada, proyecto, memoria— y que quien gestiona lo descargue, sin que ningún
fichero se sirva por una URL adivinable. Es lo que SDD-0001 deja fuera a
propósito y lo que hoy hace la mitad de las páginas del sistema anterior.

**No empieza sin su ADR.** Decide dónde se guardan los ficheros (fuera del
alcance del servidor web), cómo se sirven (previa comprobación de capacidad),
qué tipos de documento existen y si son por procedimiento o fijos.

### Entregables

| # | PR | Detalle |
|---|---|---|
| 2.1 | **ADR de documentación adjunta**: almacenamiento, servicio, tipos, límites de tamaño y formato, plazo de conservación | Sin ella no se escribe código |
| 2.2 | Metas del procedimiento: qué documentos se piden, con instrucciones y fecha límite cada uno | RF-GES-16 |
| 2.3 | Subida desde `apply` y desde `mine`, con validación de tipo y tamaño y sin JavaScript | RF-DIR-10 |
| 2.4 | Descarga por capacidad: autoría, gestión del ámbito y administración; nadie más | RNF-PDP-05 |
| 2.5 | Estado de la solicitud «pendiente de documentación» **si** la ADR lo decide; si no, una columna en la tabla | — |
| 2.6 | Decidir si la impresión de la solicitud sigue haciendo falta, ahora que la aceptación firmada se sube al mismo sitio | REQ-0001 §7 |

### Criterios de salida

- [ ] Ningún fichero de una solicitud se descarga sin comprobar capacidad;
      probado con las cuatro cuentas y con una sesión anónima.
- [ ] Los ficheros no están bajo `wp-content/uploads/` ni en ninguna ruta que
      sirva el servidor web.
- [ ] Un procedimiento sin documentación pedida no enseña el panel de subida.
- [ ] `make check` en verde.

### Riesgos

| Riesgo | Por qué es real | Mitigación |
|---|---|---|
| Servir ficheros por PHP es lento y ocupa memoria | Un PDF de proyecto puede pesar decenas de MB | `X-Sendfile`/`X-Accel-Redirect` si el destino lo permite, decidido en la ADR; tope de tamaño |
| Reproducir el patrón de hoy | Hoy los ficheros se sirven desde una ruta pública con el nombre codificado | Es el requisito RNF-PDP-05, y el criterio de salida lo comprueba |
| Datos personales en ficheros | Una aceptación firmada lleva nombre y firma | Plazo de conservación en la ADR y borrado con la solicitud |

---

## 7. Fase 3 — Profesorado

**Objetivo.** Llevar al aplicativo los procedimientos dirigidos al
profesorado individual, que hoy tienen su propio formulario y sus propias
vistas. El modelo deja el hueco (`prc_audience = teachers`); esta fase lo
rellena.

**No empieza sin su ADR.** Lo que cambia no es poco: el sujeto de la solicitud
deja de ser el centro, la unicidad pasa a ser por persona, y el acotado por
centro no aplica.

### Entregables

| # | PR | Detalle |
|---|---|---|
| 3.1 | **ADR de audiencia profesorado**: quién solicita, con qué capacidad, unicidad, qué núcleo fijo lleva | Sin ella no se escribe código |
| 3.2 | `prc_audience = teachers` en el taller y en el estado; `apply` y `mine` para la persona docente | RF-GES-17 |
| 3.3 | Unicidad por `post_author` y procedimiento, en lugar de por centro | — |
| 3.4 | Tabla de solicitudes y CSV con el núcleo fijo de la persona docente | — |

### Criterios de salida

- [ ] Un procedimiento `teachers` no enseña «Solicitar» a una cuenta de
      dirección sin capacidad de docente, ni al revés.
- [ ] Una segunda solicitud de la misma persona reabre la primera.
- [ ] `make check` en verde.

### Riesgos

| Riesgo | Por qué es real | Mitigación |
|---|---|---|
| Duplicar `ApplyForm` y `Applications` para la segunda audiencia | Es lo fácil | Una sola pantalla con el núcleo fijo elegido por `prc_audience`; la ADR fija el reparto |
| Traer las certificaciones «ya que estamos» | Van pegadas al profesorado en el sistema de hoy | Fuera de alcance (REQ-0001 §7); si se piden, ADR aparte |

---

## 8. Fase 4 — Migración del histórico y retirada del camino viejo

**Objetivo.** Que las convocatorias, solicitudes y documentos de hoy vivan en
el aplicativo y que el sistema anterior se pueda apagar. Es la fase que
[ADR-0024](../adr/ADR-0024-las-solicitudes-de-hoy-no-se-migran.md) aplaza a
propósito y **esta fase la paga**. Al terminar, ADR-0024 queda sustituida
por la ADR nueva de esta fase.

**No arranca sin el análisis de protección de datos.** Son miles de
solicitudes con nombre, correo, cargo y ficheros firmados, y moverlas es un
tratamiento nuevo (REQ-0001 RNF-PDP-06).

**Nada se borra: primero se desactiva, y se borra pasado el plazo.**

### Entregables

| # | PR | Detalle |
|---|---|---|
| 4.1 | **Análisis del tratamiento**: base jurídica, finalidad, plazo de conservación y quién accede | Documento previo, referenciado desde la ADR |
| 4.2 | **ADR de migración**, que sustituye a ADR-0024: qué se migra, a qué se mapea cada pieza y **qué pasa con las URL de hoy** (conservar, redirigir o caducar) | RNF-URL-01, RF-VIS-05 |
| 4.3 | **Mapa de las dos taxonomías** de hoy a `prc_area` y `prc_course`, y del estado manual **a ninguna**: el estado se deriva de las fechas | Con las convocatorias que tienen fechas incoherentes con su estado manual anotadas una a una |
| 4.4 | **Guion de migración idempotente** con `--dry-run` por defecto: convocatorias → `prc_procedure`, solicitudes → `prc_application`, opciones → `prc_questions` y `prc_answers`, documentos → el almacén de la fase 2 | No toca el sistema anterior; escribe solo en lo nuevo |
| 4.5 | **Copia previa**: exportación del sitio y volcado de la base de datos, con su fecha y su ubicación anotadas en el runbook | Sin copia, no se ejecuta |
| 4.6 | **Ensayo en seco completo sobre una copia del sitio**, con su informe fechado en `docs/verificacion/` y la medición de RNF-REN-06 | El ensayo cuadra solicitud a solicitud |
| 4.7 | Vuelta atrás probada: borrar lo migrado deja el sistema anterior como estaba | La reversibilidad solo cuenta si se ha ejecutado una vez |
| 4.8 | **Desactivar** el alta y la solicitud del sistema anterior; sus páginas pasan a enlazar al aplicativo | Se desactiva, no se borra |
| 4.9 | Apagar las vistas y los fragmentos que ya no pinte nadie, de uno en uno y con anotación de dónde se comprobó | Cerca de trescientas vistas y más de un centenar de fragmentos: es trabajo de meses |
| 4.10 | Decidir qué se hace con los roles de hoy y con la meta de ámbito y la de centro que escriben sus fragmentos | El snippet de roles es aditivo y nunca los toca |

### Criterios de salida

- [ ] Las solicitudes migradas cuadran una a una con las del sistema
      anterior, y el diff del ensayo en seco es vacío.
- [ ] Ejecutar el guion dos veces seguidas no cambia nada la segunda vez.
- [ ] Ningún procedimiento de hace años aparece como «próximo» en la portada.
- [ ] Cada URL de hoy hace lo que la ADR decidió, comprobado sobre la lista
      completa.
- [ ] Han pasado 60 días desde la migración sin que nadie haya creado una
      convocatoria ni una solicitud en el sistema anterior.
- [ ] La vuelta atrás se ha ejecutado una vez sobre la copia.
- [ ] Existe el análisis de protección de datos, firmado y fechado.

### Riesgos

| Riesgo | Por qué es real | Mitigación |
|---|---|---|
| **Las fechas de hoy contradicen el estado manual** | Hay iconos para «abierta con el plazo finalizado» porque pasa | Entregable 4.3 las lista; la ADR decide si manda la fecha o se corrige a mano |
| Las respuestas de hoy no caben en `prc_answers` | Formularios anexos clonados, con campos que nadie catalogó | Inventario de los anexos en el ensayo en seco; lo que no quepa se guarda como texto bajo una `key` reservada |
| Datos personales en movimiento | Miles de solicitudes y ficheros firmados | Ensayo en seco, copia previa y el análisis de 4.1 antes de tocar nada |
| Corte con plazos abiertos | Siempre hay alguna convocatoria con el plazo vivo | Migrar curso a curso, empezando por los cerrados |
| La retirada nunca se hace | Es el patrón habitual: la fase que no entrega nada visible se aplaza | Los criterios de salida tienen fecha (60 días) y dueño en el runbook |

---

## 9. Pendientes conocidos, hoy abiertos

Ordenados por riesgo, el mayor primero. Un pendiente sale de esta tabla
cuando hay evidencia de que está cerrado, no cuando alguien cree que sí. Los
identificadores no se reutilizan.

| # | Pendiente | Riesgo | Bloquea | Cómo se cierra |
|---|---|---|---|---|
| P-01 | **Qué pasa con las URL de las convocatorias de hoy.** Están enlazadas desde resoluciones y circulares publicadas | **Alto** | Fase 4 | ADR de la fase 4 (4.2) |
| P-02 | **Cómo llega el catálogo real de centros al destino** y cada cuánto se refresca. El aplicativo solo ofrece el filtro `prc_centres` y un JSON por defecto | **Alto** | Solicitar de verdad | Runbook (1.10); decisión de quien despliega |
| P-03 | **Cómo se rellena el código de centro de cada persona en el destino.** Hoy lo escribe un fragmento al iniciar sesión, en otra meta; `prc_centre_code_meta_key` permite leerla, pero **no se ha probado** | **Alto** | Solicitar de verdad | Prueba en el destino con una cuenta real (1.10) |
| P-04 | **El entorno local no reproduce el destino**: un solo sitio, sin el gestor de formularios ni el inicio de sesión corporativo | Medio | Ensayar la convivencia | Declarar por escrito qué no se puede probar en local |
| P-05 | **Quién envía los correos en el destino** y con qué remitente | Medio | Fase 1 | Runbook (1.10) |
| P-06 | **No hay línea base.** La queja de partida —«harto complejo»— no está medida | Medio | Poder demostrar la mejora | Entregable 1.9 |
| P-07 | **Si 17.700 solicitudes como `prc_application` rinden** en las consultas del taller y del CSV | Medio | Fase 4 | Medición con datos sintéticos de ese volumen (4.6) |
| P-08 | **La titularidad del centro sale del catálogo**, que puede ir por detrás de la realidad; un centro mal catalogado no puede solicitar | Bajo-medio | RF-DIR-08 | Aviso en la pantalla y contacto del ámbito; corrección en el catálogo |
| P-09 | **Si los cinco cargos de `positions()` cubren los ocho de hoy** sin que nadie se quede fuera | Bajo | RF-DIR-07 | Revisión con quien gestiona antes de la fase 1 |
| P-10 | **Cuántas convocatorias de hoy necesitan más de 20 preguntas** o un tipo que no esté en la lista | Bajo | RF-GES-06 | Recuento sobre `.local/` |

## 10. Definición de hecho

Del plan completo, no de una fase:

1. Un ámbito da de alta, publica, gestiona y archiva sus procedimientos sin
   abrir el sistema anterior y sin pedirle nada a nadie.
2. Un centro solicita, subsana y consulta sus solicitudes desde su cuenta,
   con el centro puesto solo.
3. El estado de cada procedimiento lo dicen sus fechas, y no hay dos que se
   contradigan.
4. Un procedimiento nuevo con preguntas propias no cuesta ni un formulario,
   ni una vista, ni un fragmento de código.
5. Ningún fichero de una solicitud se descarga sin comprobar capacidad.
6. Las solicitudes de hoy viven en el aplicativo y el sistema anterior está
   apagado.
7. La mejora está medida contra la línea base, no contada.
8. Cada pendiente de §9 está cerrado con evidencia o convertido en una ADR
   que dice por qué se acepta.

## 11. Próximo paso inmediato

1. **Terminar la fase 0** y dejar sus criterios de salida marcados con
   evidencia, no con código.
2. En paralelo, **tomar la línea base** (P-06): se puede hacer hoy, sobre el
   sitio tal y como está, y su valor caduca en cuanto se despliegue nada.
3. **Preguntar** por P-02 y P-03 a quien despliega: son las dos cosas que el
   repositorio no puede resolver solo, y sin ellas no solicita nadie.
