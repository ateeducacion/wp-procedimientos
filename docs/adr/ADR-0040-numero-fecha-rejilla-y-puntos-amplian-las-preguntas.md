---
id: ADR-0040
title: "Número, fecha, rejilla y opciones con puntos amplían las preguntas"
status: Propuesta
date: 2026-11-10
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0019, ADR-0023, ADR-0028, ADR-0033, ADR-0038, ADR-0039]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5-5"
---

# ADR-0040: Número, fecha, rejilla y opciones con puntos amplían las preguntas

## Estado

Propuesta. Amplía el alcance de la
[ADR-0019](ADR-0019-la-solicitud-es-nucleo-fijo-mas-preguntas.md), que no se
reescribe: su decisión —núcleo fijo más una lista corta de preguntas de forma
cerrada, sin lógica condicional ni reglas propias— sigue en pie. Esta ADR
cambia tres cosas que la ADR-0019 fijaba: los tipos, el máximo y la edición de
una pregunta con respuestas.

## Contexto

La ADR-0019 dejó cuatro tipos —`text`, `single`, `multiple`, `yesno`— y la
[ADR-0028](ADR-0028-los-adjuntos-de-una-solicitud-son-ficheros-privados.md)
añadió `file`. La propia ADR-0019 apuntaba sus límites en «Negativas»:

- «**`text` es texto.** Una pregunta “Número de grupos” de tipo `text`
  admite “tres”». La validación de verdad solo existía en el núcleo.
- «**Veinte es un límite, no un criterio.**»
- «Cambiar el tipo o quitar una opción con respuestas ya guardadas deja
  respuestas que no encajan. […] Si eso se convierte en un problema real, se
  añade el bloqueo entonces, con su ADR.»

Las peticiones recibidas durante un trimestre de uso del sistema anterior
—revisadas para el
[PLAN-0003](../plan/PLAN-0003-lo-que-falta-para-sustituir-al-sistema-anterior.md);
el material está en `.local/` ([ADR-0009](ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md))—
repiten cuatro cosas que hoy se resuelven con `text` y una hoja de cálculo
aparte:

1. **Cantidades**: número de grupos, de alumnado, de docentes participantes.
   Con `text` llegan «tres», «3 o 4» o «unos 20», y no se pueden sumar.
2. **Fechas**: la del claustro o la del consejo escolar que aprueba la
   participación. Con `text`, cada centro escribe una forma distinta.
3. **Cantidades por fila**: alumnado por etapa, grupos por nivel. Hoy son
   varias preguntas `text` seguidas, una por fila, o un anexo.
4. **Baremo**: méritos que suman puntos para ordenar las solicitudes. Hoy se
   exporta el CSV y se puntúa a mano.

Y procedimientos que con estas preguntas se quedan cortos en veinte.

Lo que ya estaba decidido y no cambia: las respuestas se guardan por la `key`
de la pregunta, que genera el aplicativo y no cambia al reordenar
(ADR-0019); el estado del procedimiento decide si se editan las preguntas
(ADR-0023); el acuse lista cada respuesta con `ProcedureQuestions::as_text()`
([ADR-0038](ADR-0038-una-sola-salida-de-correo-en-texto-plano.md)); y la
exportación a medida ofrece las columnas por su clave
([ADR-0039](ADR-0039-la-exportacion-a-medida-y-el-fichero-de-usuarios-viven-en-el-aplicativo.md)).

## Problema

¿Cómo se piden cantidades, fechas, cantidades por fila y un baremo sin
convertir las preguntas en el constructor de formularios que la ADR-0019
descartó, y sin que una respuesta ya presentada se descoloque al editar?

## Factores de decisión

- **La raya de la ADR-0019**: tipo, rótulo, indicaciones y si es
  obligatoria. Ni condiciones, ni fórmulas, ni validación propia más allá
  del tipo.
- **Una respuesta sigue unida a su pregunta** aunque se reordene o se
  renombre; también una fila de una rejilla.
- **Lo que ya se presentó no cambia**: ni el CSV por defecto ni el acuse de
  un procedimiento que no use nada nuevo.
- **Pura y probada**: el dominio sigue sin llamar a WordPress.
- **Sin JavaScript obligatorio**, como el resto del taller.

## Alternativas consideradas

### Opción 1: seguir con `text` y validar en la hoja de cálculo

- **Pro:** nada nuevo.
- **Contra:** es lo que se pide quitar. «Tres» no se suma, «01/02» no se
  ordena, y el baremo se repite a mano en cada exportación.

### Opción 2: una regla de validación por pregunta

Un patrón, un rango o una fórmula que quien gestiona escribe.

- **Pro:** cabe cualquier caso.
- **Contra:** es la opción 2 de la ADR-0019 por otra puerta: validación
  propia por pregunta, superficie que mantener y asegurar, y quien gestiona
  escribiendo expresiones.

### Opción 3: más tipos cerrados y puntos en las opciones (elegida)

Tres tipos nuevos, cada uno con su validación fija, y un número opcional
detrás de cada opción de «varias opciones».

- **Pro:** el tipo sigue siendo toda la validación; se prueba sin WordPress.
- **Pro:** las respuestas ya presentadas siguen casando: lo que se guarda de
  una opción es su texto, no sus puntos.
- **Contra:** ocho tipos son más que cinco para quien gestiona, y más ramas
  en la pantalla de solicitud.
- **Contra:** la puntuación es una suma, nada más: ni pesos por pregunta, ni
  topes, ni desempates.

## Decisión

Haremos la opción 3.

### Tipos nuevos

| Tipo | Qué es | Respuesta guardada |
|---|---|---|
| `number` | Un entero. Sin mínimo, desde 0; `min` y `max` opcionales, enteros. Se aceptan espacios alrededor y ceros a la izquierda (`007` es 7); no el signo `+`, decimales ni separadores de miles. **Cero es una respuesta** y es distinta de «sin responder» | `int`, o `''` sin responder |
| `date` | Un día del calendario que existe, `Y-m-d` (`2026-02-30` no). Se pide con `<input type="date">` y se lee como `d/m/Y` | `'Y-m-d'`, o `''` |
| `grid` | Una cantidad entera de 0 en adelante por fila. Las filas se escriben una por línea, hasta 30. Obligatoria, todas las filas llevan cantidad; opcional, una fila vacía se guarda vacía, **no como 0** | `{ r1: 3, r2: '' }` |

Lo que no encaja es un error aunque la pregunta sea opcional: «abc» no se
guarda como vacío.

Una pregunta normalizada lleva, detrás de las seis claves de la ADR-0019, lo
de su tipo:

```
{ key: 'q2', label: 'Grupos', help: '', type: 'number', required: false,
  choices: [], min: 1, max: 12 }

{ key: 'q4', label: 'Alumnado', help: '', type: 'grid', required: true,
  choices: [], rows: [ { key: 'r1', label: 'Infantil' },
                       { key: 'r2', label: 'Primaria' } ], row_seq: 2 }
```

Cada fila lleva una clave que genera el aplicativo (`r` + número), como las
preguntas. `row_seq` es la mayor que ha tenido la rejilla y **nunca baja**:
una fila nueva no hereda la clave —ni las cantidades— de una borrada.

Las filas se escriben como texto, así que el taller deduce la clave de cada
línea ([`QuestionGrid::match_rows()`](../../src/Prc/Domain/QuestionGrid.php)):
una línea igual que una fila de antes es esa fila; una línea cambiada es la
fila que ocupaba su misma línea —renombrar—, **solo si no se ha quitado
ninguna línea**. Con una menos no se sabe cuál se fue, y la renombrada pasa
por nueva antes que heredar las cantidades de otra. Por eso el taller pide
dejar en blanco la línea de la fila que se quita.

### Puntos en «varias opciones»

Una opción escrita `Texto | 3` vale 3 puntos (entero, puede ser 0); sin `|`
vale 0. Solo cuenta el último `| entero`: «A | B | 2» se llama «A | B» y vale
2. Quien solicita ve y marca solo «Texto», y **lo que se guarda es el texto**:
las respuestas presentadas antes de poner los puntos siguen casando, y
cambiar los puntos después no toca ninguna. La pregunta normalizada guarda
los textos en `choices` y los puntos aparte, en `points`, un entero por
opción; sin ninguna opción con puntos, no hay `points` y la pregunta es la de
siempre. Una sola opción (`single`) no lleva puntos.

Si alguna pregunta del procedimiento da puntos, la solicitud gana la columna
**calculada** `score` («Puntos»): la suma de los puntos de las opciones
marcadas que siguen en la lista. **No se guarda**: se calcula al leer, con
los puntos de hoy. Sale en `Applications::columns()` tras la nota, en la
tabla del taller y en la exportación, dentro del grupo «Revisión». Sin
puntos, la columna no existe.

### La rejilla en la tabla, el acuse y la exportación

En la tabla del taller, en «Lo que presentó» y en el acuse de correo, una
rejilla es una sola línea legible: «Infantil: 3; Primaria: 0», solo las filas
con cantidad. En la exportación es **una columna por fila**, con clave
`q4:r1` y rótulo «Alumnado — Infantil», dentro del grupo «Preguntas» de
`ApplicationExport::groups()`.

### Orden, máximo y cambios con respuestas

- `ProcedureQuestions::MAX` pasa de 20 a **40**.
- Las preguntas se reordenan con «Subir» y «Bajar»: botones de un formulario
  aparte, por POST con su nonce y sin JavaScript, que solo cambian el orden.
  La `key` va con la pregunta y las respuestas no se tocan. En los extremos
  no hacen nada, y sin error.
- **Cambiar el tipo de una pregunta que ya tiene respuestas se rechaza**: el
  envío entero no se guarda y el aviso nombra la pregunta. Es el bloqueo que
  la ADR-0019 dejaba para «si se convierte en un problema real»: con número,
  fecha y rejilla una respuesta de otro tipo no se puede leer —«tres» no es
  un número—. Una respuesta vacía no cuenta como respuesta; un «no», sí; un
  documento aportado, también. Cambiar el rótulo, la ayuda, las opciones o
  el rango sí se puede, mientras el estado del procedimiento deje editar las
  preguntas (ADR-0023).
- Un número con el mínimo por encima del máximo no se guarda.

### Dónde vive

Todo puro, en `src/Prc/Domain/`, sin capa nueva: `ProcedureQuestions` sigue
siendo la entrada (`sanitize()`, `validate_answers()`, `as_text()`) y se
apoya en tres clases `final` con métodos `static`, para que ninguna pase del
umbral de complejidad de PHPMD (`phpmd.xml`): `QuestionGrid` (filas, claves,
columnas de la exportación), `QuestionPoints` (opciones, puntos y puntuación) y `QuestionChanges` (subir,
bajar y qué cambios se rechazan). La pantalla de solicitud pinta los tres
tipos nuevos en `View/MeasuredQuestionView`.

## Consecuencias

### Positivas

- Las cantidades se suman y las fechas se ordenan en la hoja de cálculo sin
  limpiarlas; cada fila de una rejilla es su columna.
- El baremo sale hecho en la tabla y en la exportación, y se recalcula solo
  si se cambian los puntos.
- Un procedimiento que no usa nada nuevo exporta el mismo CSV y manda el
  mismo acuse, letra a letra (`test-application-export.php`,
  `test-mailer.php`).
- Ninguna respuesta presentada se lee con otro tipo: el cambio se para antes
  de guardar.

### Negativas

- **Cambiar el tipo con respuestas ya no se puede.** Quien se equivocó de
  tipo con el plazo abierto tiene que crear otra pregunta y borrar la vieja
  —su respuesta se conserva bajo su clave (ADR-0019)—.
- **Renombrar una fila y quitar otra borrando su línea, en el mismo
  guardado, da la renombrada por nueva**: sus cantidades dejan de verse,
  aunque siguen guardadas. Es el precio de no adivinar; el taller lo explica.
- **La puntuación es una suma.** Ni pesos, ni topes, ni empates: si un
  procedimiento los necesita, es otra ADR.
- **Cuarenta sigue siendo un límite, no un criterio**, como decía la
  ADR-0019 de veinte.
- **Ocho tipos, más ramas** en la pantalla de solicitud y en la exportación.

### Neutras

- Las respuestas se siguen guardando en `prc_answers` por `key`; la meta
  conserva ahora los enteros como enteros y la rejilla por clave de fila.
- La clave de una pregunta borrada todavía puede volver a darse a una nueva
  si era la mayor; las filas de una rejilla ya no tienen ese problema
  (`row_seq`). Corregirlo en las preguntas queda fuera de esta ADR.
- El navegador ayuda con `type="number"` y `type="date"`, pero quien decide
  es el servidor: sin ellos se teclea igual.
