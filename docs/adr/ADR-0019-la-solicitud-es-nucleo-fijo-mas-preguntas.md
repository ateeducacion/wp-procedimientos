---
id: ADR-0019
title: "La solicitud es un núcleo fijo más preguntas configurables"
status: Propuesta
date: 2026-09-15
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0010, ADR-0016, ADR-0017, ADR-0018, ADR-0022]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-fable-5-1"
---

# ADR-0019: La solicitud es un núcleo fijo más preguntas configurables

## Estado

Propuesta (2026-09-15). Nada implementado: fija `prc_questions`,
`Domain/ProcedureQuestions` y `Domain/ApplicationInput` antes de escribirlos.

## Contexto

Lo que un procedimiento pide a un centro cambia de un procedimiento a otro, y
hoy eso se resuelve con **tres mecanismos apilados**, medidos sobre la foto
del 2026-09-15 (material en `.local/`):

1. **Cuatro pares «título / indicaciones».** El formulario de alta tiene un
   contador «¿desea añadir campos personalizados?» y cuatro bloques de título
   más indicaciones que se muestran si el contador llega a 1, 2, 3 o 4. El
   formulario de solicitud los copia por búsqueda y ofrece, para cada uno, una
   **respuesta libre**. Cuatro es el máximo y texto es el único tipo.
2. **Un subformulario de opciones.** Si el alta marca «casillas» o «lista
   desplegable», quien gestiona crea **una entrada por opción** en otro
   formulario, enlazada al identificador de la convocatoria, y la solicitud las
   recoge como casillas o como una única elección. Unas 500 opciones en el
   sitio.
3. **Un formulario anexo clonado por convocatoria.** Si lo anterior no
   basta, una casilla del alta hace que un fragmento de código **duplique un
   formulario plantilla y sus tres vistas**, los renombre con el slug de la
   convocatoria, reescriba los identificadores de campo emparejándolos por
   nombre y guarde los cuatro identificadores resultantes en la convocatoria.
   Hay más de veinte anexos así, uno por programa y curso, con entre 18 y 209
   campos.

Y por debajo, **condiciones cableadas por convocatoria** dentro del propio
formulario de solicitud: hasta cuatro docentes coordinadores cuya visibilidad
es una lista literal de identificadores de convocatoria, un teléfono que solo
aparece para dos, una etapa y una especialidad que solo aparecen para una.
Cada curso alguien edita esas listas.

El núcleo real, el que aparece en todas, es corto: **el centro** (por código),
**el cargo** de quien firma, **los compromisos** que acepta y, cuando el
procedimiento lo pide, **una persona coordinadora**.

## Problema

¿Cómo se define lo que pide un procedimiento sin clonar un formulario por
convocatoria y sin cablear condiciones por identificador, y dónde está la
raya entre lo que va en código y lo que redacta quien gestiona?

## Factores de decisión

- **La parte fija es de verdad fija** y se puede validar y probar sin
  WordPress.
- **La parte variable es corta pero impredecible**: modalidad, ejes, número
  de grupos, sí o no a una condición. No se anticipa en código.
- **Quien gestiona no despliega.** Un procedimiento nuevo no puede esperar a
  una versión.
- **Un constructor de formularios es el sistema del que se sale.** Clonar
  plantillas y reescribir identificadores es el origen del problema.
- **El CSV necesita columnas estables** para el núcleo y una por pregunta.
- **Una respuesta tiene que seguir unida a su pregunta** aunque se reordene o
  se reescriba el rótulo.
- El aplicativo de eventos tomó la misma decisión para su inscripción
  ([ADR-0010](ADR-0010-se-parte-del-esqueleto-del-aplicativo-de-eventos.md));
  aquí cambia el núcleo y se añaden dos tipos.

## Alternativas consideradas

### Opción 1: todos los campos fijos en código

| Pros | Contras |
|------|---------|
| Todo se valida y se prueba. | No cabe lo imprevisible, que es justo lo que hay: cada programa pide algo distinto. |
| Columnas estables por definición. | Lo que no cabe se cuela por un «Observaciones» libre, sin estructura. |

Descartada.

### Opción 2: un constructor de formularios completo

Tipos de campo, lógica condicional, validación por campo, repetidores.

| Pros | Contras |
|------|---------|
| No hay caso que no quepa. | Es rehacer el formulario anexo clonado, en PHP propio. |
| | Se acaban las columnas estables y se multiplica la superficie a mantener y asegurar. |

Descartada. Es la respuesta completa a una pregunta que no se ha hecho.

### Opción 3: un catálogo cerrado de extras que se encienden por procedimiento

| Pros | Contras |
|------|---------|
| Todo en código. | Falla en el primer programa que pida algo fuera del catálogo, y ese llega siempre. |

Descartada.

### Opción 4: núcleo fijo más una lista corta de preguntas de forma cerrada (elegida)

| Pros | Contras |
|------|---------|
| Lo estable se prueba; lo variable no espera despliegue. | La raya la decide quien escribe el código. |
| Cuatro tipos y ninguna regla: no se convierte en constructor. | Sin lógica condicional se ven preguntas que no aplican. |

## Decisión

**La solicitud es un núcleo fijo, en código, más una lista de hasta veinte
preguntas de forma cerrada que quien gestiona redacta en el taller.**

### El núcleo (`Domain/ApplicationInput`)

| Dato | Regla |
|---|---|
| Centro | Código y nombre **de solo lectura**, resueltos en el servidor desde la persona ([ADR-0016](ADR-0016-el-centro-de-la-persona-es-una-meta-configurable.md), [ADR-0017](ADR-0017-el-catalogo-de-centros-no-se-versiona.md)); nunca del formulario |
| Cargo | `prc_applicant_position`, uno de `head`, `deputy_head`, `head_of_studies`, `secretary`, `other` |
| Compromisos | Casilla obligatoria si `prc_commitments` del procedimiento no está vacío |
| Persona coordinadora | `prc_coordinator` `{ name, email }`, obligatoria si `prc_requires_coordinator` |
| Respuestas | `prc_answers`, indexadas por la `key` de cada pregunta |

Quien solicita no teclea su nombre ni su correo: son `post_author`
([ADR-0018](ADR-0018-la-solicitud-es-un-contenido-del-procedimiento.md)). Una
sola persona coordinadora, con nombre y correo: los «hasta cuatro docentes»
de hoy son una condición cableada de dos programas, no un núcleo.

### Las preguntas (`prc_questions`, `Domain/ProcedureQuestions`)

Una pregunta tiene exactamente esto:

```
{ key: 'q1', label: 'Modalidad', help: '', type: 'single', required: true,
  choices: ['Modalidad A', 'Modalidad B'] }
```

| Parte | Regla |
|---|---|
| `key` | La genera el aplicativo (`q` + número correlativo) al crear la pregunta y **no cambia** al reordenar ni al reescribir el rótulo |
| `label`, `help` | Texto; `help` puede estar vacío |
| `type` | `text` (hasta 2.000 caracteres), `single` (una opción), `multiple` (varias), `yesno` |
| `required` | Sí o no |
| `choices` | Solo para `single` y `multiple`; lista de textos |

Máximo veinte. `ProcedureQuestions::sanitize( $raw )` y
`::validate_answers( $questions, $answers )` son **puras**: sin WordPress, y
con test propio.

**Lo que una pregunta no tiene:** lógica condicional —ninguna aparece según
otra— ni validación propia más allá del tipo. Esas dos ausencias son lo que
separa esto de la opción 2.

### Las respuestas viven en la solicitud, por `key`

`prc_answers` guarda `{ q1: 'Modalidad A', q2: ['Eje 1', 'Eje 3'], q3: true,
q4: '…' }`. Reescribir el rótulo o reordenar no desconecta nada. Una pregunta
**borrada** no borra su respuesta: se conserva bajo su `key`, deja de
pintarse y deja de exportarse; recuperar la pregunta con la misma `key` la
devuelve.

### El CSV

Columnas del núcleo, siempre en el mismo orden, más **una columna por
pregunta** con el rótulo por cabecera; `multiple` se exporta separando las
opciones con `;`.

## Consecuencias

### Positivas

- Un procedimiento nuevo **no clona nada**: se redactan sus preguntas y se
  publica. Se acaban los anexos por curso y las listas de identificadores
  cableadas.
- El núcleo se valida una vez y se prueba sin WordPress.
- `single`, `multiple` y `yesno` cubren lo que hoy hacen el subformulario de
  opciones y la mayoría de los anexos; `text` cubre los cuatro pares.
- Las columnas del núcleo son estables; el CSV tiene forma.

### Negativas

- **Sin lógica condicional se ven preguntas que no aplican.** Se acepta a
  sabiendas.
- **`text` es texto.** Una pregunta «Número de grupos» de tipo `text` admite
  «tres». La validación de verdad solo existe en el núcleo.
- **Cambiar el tipo o quitar una opción con respuestas ya guardadas deja
  respuestas que no encajan.** Esta ADR **no bloquea** la edición de
  preguntas con el plazo abierto: la respuesta se conserva tal cual y se pinta
  como texto si ya no coincide con ninguna opción. Si eso se convierte en un
  problema real, se añade el bloqueo entonces, con su ADR.
- **Los anexos grandes no caben.** Un anexo de 200 campos con repetidores y
  ficheros no es veinte preguntas de forma cerrada. Esos programas tendrán que
  reducir lo que piden en la solicitud, o esperar a la fase de documentación
  adjunta. No es un defecto de la decisión: es la decisión.
- **Comparar entre procedimientos no se puede**: la misma pregunta escrita de
  dos maneras son dos columnas.
- **Veinte es un límite, no un criterio.** Nada impide que un procedimiento
  use las veinte para reconstruir a mano el formulario del que se salía.

### Neutras

- Dónde se guardan las respuestas es de la
  [ADR-0018](ADR-0018-la-solicitud-es-un-contenido-del-procedimiento.md); qué
  pantalla las pinta, de la
  [ADR-0022](ADR-0022-las-pantallas-son-paginas-pintadas-enteras.md).
- Subir ficheros no es un tipo de pregunta. La documentación adjunta es la
  fase 2, con su ADR.
- Ampliar el núcleo es una versión del aplicativo, como cualquier cambio de
  código.
