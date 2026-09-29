---
id: ADR-0004
title: "Integración continua y política de pruebas"
status: Propuesta
date: 2026-09-15
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0001, ADR-0002, ADR-0009, ADR-0010]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-fable-5-1"
---

# ADR-0004: Integración continua y política de pruebas

## Estado

Propuesta (2026-09-15). Los workflows y `codecov.yml` ya están en el
repositorio, copiados del aplicativo de eventos con los identificadores
renombrados; lo que aquí se fija es la política.

## Contexto

El repositorio nace con la mecánica de CI ya escrita:

| Fichero | Qué es |
|---|---|
| `.github/workflows/ci.yml` | Los dos jobs que tumban un PR: `lint` y `test` |
| `.github/workflows/phpmd.yml` | Análisis de complejidad a Code Scanning, semanal y por PR |
| `.github/workflows/cancelar-al-cerrar.yml` | Cancela lo que quede en marcha cuando el PR se cierra |
| `.github/workflows/capturas.yml` | Recorre las pantallas y deja la galería en la descripción del PR |
| `.github/workflows/playground-preview.yml` | Pega en el PR un enlace que lo abre en WordPress Playground |
| `.github/workflows/update-agent-skills.yml` | Actualiza `.agents/skills` los lunes y abre PR |
| `.github/dependabot.yml` | Actualizaciones semanales de acciones, Composer y npm |
| `codecov.yml` | Estados de cobertura de proyecto y de parche |

El aplicativo de eventos empezó con un suelo de cobertura del 10 % y lo subió
al 90 % en los dos ejes cuando tuvo pantallas; su `codecov.yml` llegó aquí ya
en el 90 %. Y dos condicionantes son los mismos:

1. **El bundle no se puede cargar en los tests.** `tests/bootstrap.php` carga
   los módulos de `src/Prc/` y se salta `*.bundle.php`: cargar las dos copias
   sería «Cannot redeclare class», y por eso `declare(strict_types=1)` está
   prohibido.
2. **Los tests necesitan un WordPress vivo** (wp-env sobre Docker), así que el
   job de test no es barato.

Lo que el sistema actual tiene de pruebas: unas pocas comprobaciones sobre
tres de sus fragmentos, con WordPress simulado. Nada sobre las vistas, que es
donde está la lógica.

## Problema

¿Qué corre en cada pull request, con qué listón de cobertura, dónde corre y
qué queda deliberadamente sin probar?

## Factores de decisión

- Un listón que no se pueda cumplir se salta o se falsea; un listón que no
  exija nada no defiende nada.
- Un PR en rojo por algo que no es un fallo del código enseña a ignorar el
  rojo.
- Dónde corren los jobs puede cambiar por motivos ajenos al proyecto, y
  cambiarlo no debería ser un PR.
- El artefacto que llega a producción es el bundle, y es justo lo que los
  tests no cargan.
- Este repositorio no empieza de cero: hereda de eventos las fixtures, los
  patrones de test y un dominio con lógica pura desde el primer día
  (`ProcedureState`, `ProcedureQuestions`, `ProcedureInput`, `ApplicationInput`).

## Alternativas consideradas

### El suelo de cobertura del parche

#### Opción A: 90 % desde el primer commit

- Pros: la regla es la misma desde el principio; nadie tiene que acordarse de
  subirla —y en eventos casi nadie se acordó hasta que hubo un motivo—.
- Contras: los primeros PR de andamiaje pueden no llegar sin tests de adorno.

#### Opción C: 10 % que sube cuando la casa esté en orden

Lo que hizo eventos.

- Pros: no obliga a inventar tests sobre declaraciones.
- Contras: durante meses el listón no filtra, y subirlo depende de que
  alguien se acuerde. Eventos ya recorrió ese camino y enseñó dónde acaba:
  fixtures que dejaban el mundo a medias y tests que describían un mundo que
  en producción no existía, descubiertos al subir el listón.

### Dónde corren los jobs

#### Opción A: `runs-on: ubuntu-latest` fijo

- Contras: si la facturación se bloquea hay que tocar seis YAML.

#### Opción B: `runs-on: self-hosted` fijo

- Contras: lo mismo al revés, y el repositorio deja de correr en un fork.

#### Opción C: una variable de repositorio

`runs-on: ${{ vars.PRC_RUNNER || 'ubuntu-latest' }}`.

- Pros: cambiar de sitio es cambiar una variable, sin PR. Sin la variable se
  usan los runners de GitHub, que es lo que quiere un fork.
- Contras: los pasos de PHP van duplicados con
  `if: runner.environment == 'github-hosted'`.

## Decisión

**Cobertura: opción A. Runners: opción C.** En el segundo eje es la misma
decisión que eventos; en el primero es su punto de llegada, no su punto de
partida.

### Qué corre en cada pull request

| Job | Qué comprueba | ¿Tumba el PR? |
|---|---|---|
| `ci / lint` | `composer validate`; PHPCS con WPCS (`composer lint`); `make check-provision`; `php -l` de todo `*.php` fuera de `vendor/`; `jq empty` de todo el JSON versionado | **Sí** |
| `ci / test` | `npm run test:js` (Vitest sobre los guiones de `assets/js`, antes de arrancar nada) y, con wp-env arrancado, `make provision`, `make check-plugin` y `make coverage`: PHPUnit sobre un WordPress vivo | **Sí** |
| Codecov, estado `patch` | 90 % sobre las líneas que toca el parche | **Sí** |
| Codecov, estado `project` | 90 % del total, con 1 % de holgura | **Sí** |
| `PHPMD` | Complejidad y código sospechoso, en SARIF a Code Scanning | No |
| `Capturas` y `Playground` | Galería y enlace de prueba en la descripción del PR | No |
| `Cancelar al cerrar` | Cancela ejecuciones huérfanas | No |

Los dos jobs de `ci.yml` van con `timeout-minutes` (10 y 20) y el grupo de
`concurrency` es el número de PR. Los cambios que solo tocan `**.md`,
`docs/**` o `.github/*.md` no disparan CI: esta ADR no arranca ningún runner.

### El suelo de cobertura

**90 % en los dos ejes desde el primer commit**, con `threshold: 1%` en el del
proyecto. Cuenta PHP y JavaScript juntos: el job sube dos informes con
su flag —`php` de PHPUnit, `js` de Vitest— y Codecov los suma; el de
JavaScript no sustituye al de PHP. Lo que se toca va con sus tests **y** no se compensa tocando poco.
Si un PR de andamiaje no llega, se baja en un PR propio que diga por qué, no
se falsea con tests de adorno. `src/Prc/App.php` está fuera de la medición:
corre en el arranque, antes de que PHPUnit mida, y lo que hace se prueba en
`test-load-order.php`.

### Lo que enseñó eventos y aquí se aplica de entrada

- La guarda del paso de Codecov lee `env.CODECOV_TOKEN`, no `secrets`: el
  contexto `secrets` no se puede leer desde un `if` y GitHub rechaza el
  fichero entero sin decir qué línea. Y Dependabot no es un fork pero tampoco
  recibe los secretos del repositorio. `fail_ci_if_error: true` se queda.
- Las fixtures reponen las metas al reponer los tipos de contenido:
  `reset_post_types()` de `WP_UnitTestCase` se lleva las `register_post_meta()`
  y, sin reponerlas, `sanitize_callback` y `auth_callback` desaparecen a partir
  del segundo test de cada clase.

### Qué NO se prueba

- **El bundle.** La red de seguridad no es un test: es el `php -l` que
  `build/pack-snippet.php` ejecuta sobre el fichero recién escrito, más
  `make snippet-check` con wp-env arrancado. Eso comprueba **sintaxis, no
  comportamiento**.
- **El gestor de formularios ni el constructor de páginas.** No se instalan en
  el entorno de test; el aplicativo no depende de ellos.
- **El navegador, en CI.** `make test-browser` existe y comprueba con
  Playwright los tres escalones de la confirmación (SweetAlert2, `confirm()`,
  sin JavaScript), pero corre a mano: `ci.yml` no lo llama.
- **Producción.** Ningún workflow habla con el sitio de destino.

## Consecuencias

### Positivas

- Cada PR pasa exactamente el mismo lint que la máquina de quien lo escribió:
  CI llama a `composer lint` y a los targets del Makefile.
- El listón alto desde el principio evita la deuda que eventos tuvo que pagar
  al subirlo: los tests que miden mal se ven en el primer PR, no en el
  vigésimo.
- Mover los jobs entre runners es una variable, no un PR.

### Negativas

- **El 90 % en un repositorio vacío es exigente de verdad.** El primer PR que
  registre los dos tipos de contenido y las dos taxonomías tiene que traer sus
  tests de registro, y eso es trabajo que en eventos se difirió. Se acepta
  porque el SDD ya pide un fichero de test por módulo.
- **La CI está escrita antes que lo que ejecuta.** A 2026-09-15 no hay ningún
  test en `tests/unit/`: `make coverage` mediría cero. El primer PR tiene que
  traerlos o la CI está en rojo desde el primer día.
- El job `test` necesita Docker, wp-env y una provisión completa. En un runner
  propio hay que pararlo al acabar (paso «Parar wp-env», con `always()`) o los
  contenedores se quedan ocupando los puertos 8698 y 8699.
- **PHPMD no tumba nada.** Es un informe que se puede ignorar indefinidamente.
- Un PR desde un fork no tiene estado de cobertura.

### Neutras

- `make check-public` ([ADR-0009](ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md))
  está en `make check`, que es lo que corre en local, pero **`ci.yml` no lo
  llama**: un PR puede publicar lo que no debe y pasar en verde. Añadirlo al
  job `lint` es una línea y conviene que sea la primera del repositorio.
