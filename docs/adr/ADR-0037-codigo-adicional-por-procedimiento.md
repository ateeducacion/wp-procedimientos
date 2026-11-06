---
id: ADR-0037
title: "El código adicional se limita a la ficha pública del procedimiento"
status: Propuesta
date: 2026-11-06
related:
  issues: []
  prs: [42]
  sdds: [SDD-0001]
  adrs: [ADR-0009, ADR-0014, ADR-0023]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Codex"
  model: "GPT-6"
---

# ADR-0037: El código adicional se limita a la ficha pública del procedimiento

## Estado

Propuesta

## Contexto

Los procedimientos comparten una ficha pública, pero pueden necesitar ajustes
visuales puntuales. Quien gestiona un procedimiento tiene permisos acotados por
ámbito (`Access/ProcedureAccess.php`); quien administra el aplicativo puede
tener permisos adicionales. El repositorio se publica en abierto y no debe
contener código, configuración ni identidad de una instalación concreta
(ADR-0009).

## Problema

¿Cómo permitir ajustes específicos por procedimiento sin ampliar los permisos
del resto de gestores ni cargar código en otras pantallas?

## Factores de decisión

- El CSS debe quedar al alcance de quien puede editar ese procedimiento.
- El JavaScript puede ejecutar acciones en el navegador de cualquier visitante
  y requiere una capacidad de WordPress reservada a administración.
- El código solo debe cargarse en la ficha pública del procedimiento.
- La edición debe conservar la protección POST, nonce, bloqueo y alcance del
  taller existente.

## Alternativas consideradas

### Guardar código global del aplicativo

Evita duplicar código, pero no permite cambios aislados y amplía el efecto de
cualquier error a todas las fichas.

### Guardar código por procedimiento y limitar su ejecución

Permite estilos por ficha. El JavaScript queda restringido a quienes tienen
`unfiltered_html` y permiso administrativo; en multisitio WordPress puede
retirar esa capacidad a administradores de sitio.

## Decisión

Guardaremos CSS y JavaScript como metas del procedimiento. El CSS lo puede
editar quien tenga acceso de edición al procedimiento. El JavaScript requiere
además administración del aplicativo y `unfiltered_html`. Ambos se encolan solo
en la ficha pública publicada; el `<` del CSS se escapa para impedir que el
contenido cierre el elemento `style` del documento.

## Consecuencias

### Positivas

- Un ajuste de una ficha no altera las demás.
- Las capacidades actuales por ámbito siguen siendo la puerta para el CSS.
- La ejecución de JavaScript tiene una barrera adicional y explícita.

### Negativas

- Los administradores de sitio en multisitio sin `unfiltered_html` no podrán
  editar JavaScript desde el taller.
- Quien guarde JavaScript puede cambiar la experiencia pública de esa ficha;
  el acceso queda reservado a administración.

### Neutras

- Cada persona administradora puede revisar el código desde la pestaña
  «Código» del taller.
