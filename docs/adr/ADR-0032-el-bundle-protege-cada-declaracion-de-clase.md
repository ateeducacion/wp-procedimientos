---
id: ADR-0032
title: "El bundle protege cada declaración de clase"
status: Propuesta
date: 2026-11-06
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0002]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Codex"
  model: "GPT-6"
---

# ADR-0032: El bundle protege cada declaración de clase

## Contexto y problema

La guarda `PRC_BUNDLE_LOADED` evita ejecutar dos veces el aplicativo, pero el
validador estático de Code Snippets comprueba declaraciones antes de ejecutar
esa guarda. `scripts/snippet-check.php` reproduce el guardado de un snippet
activo y detecta el rechazo de una clase ya cargada. Una ejecución correcta
no basta si el editor rechaza el siguiente guardado.

## Decisión

El empaquetador conserva la guarda global y añade un `class_exists()` con
nombre completo y sin autoload alrededor de cada clase generada. No modifica
las fuentes modulares. Comprueba que cada unidad tenga namespace y clase final;
si no reconoce la unidad, falla en vez de producir un artefacto incompleto.

La guarda global sigue evitando repetir el arranque. Las guardas individuales
impiden redeclarar clases cuando las fuentes ya estaban cargadas y permiten
al validador reconocer esa protección. Es el mismo contrato en PHP y en el
análisis estático, sin desactivar la validación del plugin.

## Alternativas y consecuencias

- Mantener solo la constante deja abierto el rechazo estático.
- Añadir guardas manuales a todas las fuentes duplica una regla de empaquetado.
- El artefacto crece dos líneas por clase y queda limitado al formato de unidad
  ya usado por `src/Prc/load-order.php`: una clase final por fichero.

## Comprobación

`tests/unit/test-bundle.php::test_the_bundle_can_be_evaluated_twice_after_the_sources`
comprueba la evaluación repetida después de cargar las fuentes. `make bundle`
valida la sintaxis y `make snippet-check` comprueba el validador real y los
efectos del primer arranque. Las tres comprobaciones son necesarias.
