---
id: PLAN-0002
title: "Continuar el esqueleto: dónde está el trabajo y cómo seguir"
status: Propuesta
date: 2026-09-16
related:
  issues: []
  prs: []
  adrs: [ADR-0009, ADR-0010]
  sdds: [SDD-0001]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-fable-5-1"
---

# PLAN-0002 — Continuar el esqueleto: dónde está el trabajo y cómo seguir

**Estado:** documento de continuación, no una fase. Se escribió el 2026-09-15
mientras se construía la fase 0 de
[PLAN-0001](PLAN-0001-implantacion-por-fases.md), para que quien retome el
trabajo —persona o agente, con esta herramienta o con otra— sepa qué hay, qué
está a medias y qué necesita a una persona. Se actualiza en cada hito; la
fecha de la cabecera dice cuándo fue el último.

Lo que aquí no está, está en `.local/`, que no se versiona
([ADR-0009](../adr/ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md)):
la investigación del sistema anterior y las notas privadas de continuación.

## 1. Qué es este repositorio, en tres líneas

Entorno de desarrollo (no plugin) del aplicativo de procedimientos: convocatorias
a las que los equipos directivos inscriben a su centro. Clon estructural del
aplicativo de eventos ([ADR-0010](../adr/ADR-0010-se-parte-del-esqueleto-del-aplicativo-de-eventos.md)).
El contrato de todo lo que se construye es
[SDD-0001](../sdd/SDD-0001-arquitectura-del-aplicativo-de-procedimientos.md).

## 2. Dónde está cada cosa

| Qué | Dónde | Estado a 2026-09-15 |
|---|---|---|
| Diseño | `docs/sdd/SDD-0001-…md` | Escrito; Propuesta |
| Decisiones | `docs/adr/ADR-0001…0024`, índice en `registro.md` | Escritas; Propuesta |
| Requisitos y plan | `docs/requisitos/REQ-0001-…md`, `docs/plan/PLAN-0001-…md` | Escritos |
| Guías | `README.md`, `AGENTS.md`, `docs/desarrollo-para-empezar.md`, `docs/roles-y-permisos.md` | Escritas |
| Infraestructura | `Makefile`, `.wp-env.json` (8698/8699), `.github/`, `build/`, `scripts/`, `codecov.yml`, `.phpcs.xml.dist` | Copiada del aplicativo de eventos y renombrada; revisada |
| Skills de agentes | `.agents/skills/` y su copia `.claude/skills/` | Copiadas; `make check-skills` |
| Lista de carga | `src/Prc/load-order.php` | Escrita: es la lista de ficheros que la fase 0 tiene que dejar existiendo |
| Núcleo | `src/Prc/{bootstrap,App}.php`, `Meta/`, `Domain/`, `Access/`, `PostType/`, `Taxonomy/`, `Admin/` | Escrito, pasa `php -l` |
| Pruebas | `tests/unit/` | 20 escritas; faltan las de las pantallas del frontal |
| Frontal | `src/Prc/PublicFront/` y `View/`, `assets/css/prc-app.css`, `assets/js/prc-app.js` | Escrito, pasa `php -l` |
| Solicitudes y taller | `PublicFront/{Applications,ProcedureEditor,ApplyForm,MyCentre}.php` | Escrito, pasa `php -l` |
| Roles y provisión | `snippets/roles-and-profiles.php`, `scripts/{provision-roles,setup-vocabulary,setup-pages,seed-demo}.php`, `scripts/mu-plugins/prc-dev-tools.php`, `blueprint*.json` | Escrito |
| Bundle | `snippets/prc-procedimientos-app.bundle.php` | Se genera con `make bundle`; no existe aún |
| Verificación | `make install && make up && make check` | `make install` hecho; el resto **sin ejecutar todavía** |

## 3. Cómo seguir desde aquí

1. Leer entera la SDD-0001 y `src/Prc/load-order.php`: todo fichero de esa
   lista tiene que existir para que `tests/bootstrap.php` y `make bundle`
   funcionen. Los que falten se escriben tomando como modelo el fichero
   equivalente del aplicativo de eventos (misma ruta, con su prefijo), adaptado al
   dominio de la SDD, y usando exactamente los nombres del núcleo ya escrito
   (`ProcedureMetaKeys`, `ApplicationMetaKeys`, `ProcedureState::of()`,
   `ProcedureAccess::can_*()`, `CentreScope::code_for()`,
   `CentreCatalog::find()`, `ProcedureQuestions::sanitize()/validate_answers()`).
2. `make install && make up` (Docker; la primera vez tarda varios minutos).
   La provisión encadena bundle → sync-snippets → roles → vocabulario →
   páginas → demo y se cae con `RuntimeException` si algo falta.
3. `make test`, `make lint`, `make check-public`, `make snippet-check`, y al
   final `make check`. Un test que falle porque el código no cumple la SDD se
   arregla en el código; un test solo se cambia si el test está mal, y se dice.
4. Comprobar en el navegador con las cuentas de demostración (`gestion`,
   `gestion2`, `direccion`, `direccion2`; cambio de cuenta desde la barra
   superior) los criterios de aceptación de la SDD.
5. Actualizar este documento, `CHANGELOG.md` («Sin publicar») y, si una
   decisión cambió al implementar, la ADR correspondiente con una adenda.

**No se hace commit ni push hasta que la persona responsable lo pida.** El
repositorio todavía no tiene historia: mientras tanto, ADR y SDD se corrigen
en su propio texto.

## 4. Lo que necesita a una persona

Las decisiones de la SDD se tomaron con la investigación delante y sin
poder preguntar. Antes de dar la fase 0 por cerrada, hay que confirmarlas:

1. **Subsanación.** La SDD la entiende como una sola ventana de fechas del
   procedimiento durante la cual el centro edita su solicitud **solo si** se le
   ha pedido (estado de revisión «a subsanar» con nota). ¿Es eso, o son dos
   cosas distintas (reclamar contra el listado provisional / corregir la
   solicitud)?
2. **Resolución y listados.** Tres enlaces (resolución que convoca, listado
   provisional, listado definitivo) y «resuelto» = hay listado definitivo.
3. **Centros privados y concertados.** No entran por el mismo camino que los
   públicos y no traen código de centro; la SDD lo resuelve con el campo de
   la ficha de usuario que rellena administración. ¿Basta para la fase 1?
4. **Una persona con más de un centro.** La SDD asume un centro por cuenta.
5. **Persona coordinadora.** Un bloque fijo (nombre y correo) cuando el
   procedimiento lo pide; hoy hay convocatorias que piden hasta cuatro
   docentes con más datos.
6. **Documentación adjunta** (aceptación firmada, proyecto, memoria): fase 2.
   ¿Es imprescindible para el primer despliegue?
7. **Roles.** El aplicativo comprueba capacidades; en el sitio de destino se
   cuelgan de los roles que ya existen. ¿De acuerdo?
8. **URL públicas.** El aplicativo arranca vacío y con su propio slug; las
   direcciones de las convocatorias de hoy no se conservan en la fase 1.
9. **Nombre público del repositorio**: los badges, el blueprint y los
   workflows asumen `ateeducacion/wp-procedimientos`.
10. **Correos** al solicitar y al cambiar el estado de revisión: fase 1,
    después del esqueleto. ¿Alguno hace falta desde el primer día?

## 5. Historial de hitos

| Fecha | Hito |
|---|---|
| 2026-09-15 | Investigación del sistema anterior y de los dos repositorios hermanos (`.local/`) |
| 2026-09-15 | Infraestructura copiada y renombrada; SDD-0001; índice de 24 ADR; lista de carga |
| 2026-09-15 | ADR, REQ, PLAN-0001, README, AGENTS y guías escritos; núcleo de `src/Prc/` escrito |
| 2026-09-15 | Frontal, solicitudes, taller, roles y provisión escritos |
| 2026-09-16 | Dependencias instaladas; pendientes las pruebas de las pantallas, la verificación completa y la revisión crítica |
