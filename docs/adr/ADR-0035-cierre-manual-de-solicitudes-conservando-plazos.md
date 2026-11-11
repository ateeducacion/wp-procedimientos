---
id: ADR-0035
title: "Cierre manual de solicitudes sin cambiar los plazos"
status: Propuesta
date: 2026-11-07
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0013, ADR-0023, ADR-0033, ADR-0034]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Codex"
  model: "GPT-6"
---

# ADR-0035: Cierre manual de solicitudes sin cambiar los plazos

## Estado

Propuesta (2026-11-07). El taller permite pausar solicitudes y volver al
cálculo de fechas; el cierre no altera los plazos propios de documentos.

## Contexto

`Domain/ProcedureState::of()` deriva el estado de la publicación, el histórico,
los enlaces y las fechas (ADR-0013). A la vez, las preguntas de archivo pueden
tener ventanas de entrega propias (ADR-0033), ampliables sin reabrir la
inscripción (ADR-0034). Las consultas operativas revisadas muestran que puede
hacer falta admitir documentos de centros ya inscritos sin aceptar centros
nuevos.

El taller publica el procedimiento, pero no tenía una acción para devolver uno
publicado a borrador ni para cerrar las solicitudes antes de la fecha prevista.

## Problema

¿Cómo detener nuevas solicitudes en cualquier momento sin cambiar las fechas
oficiales ni cerrar por accidente la entrega documental o el plazo separado de
subsanación?

## Factores de decisión

- El cierre debe ser inmediato y reversible por el ámbito que gestiona.
- Los plazos de documentos y de subsanación son dimensiones independientes.
- Las acciones de publicación requieren POST, nonce, capacidad y ámbito.
- El modo automático debe seguir siendo el comportamiento por defecto.

## Alternativas consideradas

### Opción 1: cambiar la fecha de cierre

Es simple de implementar, pero reemplaza la fecha prevista y hace más difícil
saber cuál era el plazo publicado. Reabrir solicitudes también exige volver a
editar fechas.

### Opción 2: marca booleana de cierre manual (elegida)

`prc_applications_closed` detiene nuevas solicitudes y la edición ordinaria de
las existentes. Al desactivarla, el estado vuelve a derivarse del calendario
guardado. El plazo de subsanación, que tiene prioridad propia, y los plazos de
entrega documental no cambian.

### Opción 3: estado manual completo

Permitir escoger cualquier estado duplicaría fechas, enlaces, publicación e
histórico y permitiría combinaciones contradictorias. No se adopta.

## Decisión

Se añade una acción «Cerrar solicitudes ahora» y su reversión «Volver al
calendario automático» en el panel «Publicación». El cierre se guarda
como `prc_applications_closed`; tiene prioridad sobre el cálculo de abierto,
próximo y cerrado, pero no sobre borrador, histórico, resuelto ni subsanación.
No afecta a las entregas de documentos ya solicitados. Para retirar la ficha
pública se usa «Devolver a borrador», que cambia `post_status` y conserva las
fechas y la marca de cierre.

El recuadro de etapas muestra «Pendiente de documentación» cuando las
solicitudes están cerradas, hay una solicitud registrada y sigue abierta una
ventana documental. La ventana se calcula de las preguntas existentes; no se
guarda un segundo estado. La ficha, el área de gestión y el formulario explican
cuando el cierre es manual y anterior a la fecha prevista.

## Consecuencias

### Positivas

- El área puede parar nuevas solicitudes sin editar ni perder el cierre
  previsto.
- Los centros ya inscritos conservan el acceso a sus documentos mientras sus
  plazos específicos sigan abiertos.
- Volver al modo automático es una acción explícita y mantiene las fechas.

### Negativas

- Hay una excepción manual al cálculo automático; quien gestiona debe
  reactivarlo para que vuelvan a mandar las fechas.
- Devolver a borrador oculta temporalmente la ficha pública y requiere volver
  a publicarla.

### Neutras

- Histórico sigue siendo irreversible para el ámbito convocante (ADR-0023).
- La subsanación sigue su calendario y sus reglas propias.
