---
id: ADR-0039
title: "La exportación a medida y el fichero de alta de usuarios viven en el aplicativo"
status: Propuesta
date: 2026-11-10
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0009, ADR-0018, ADR-0019, ADR-0023, ADR-0028, ADR-0038]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5-5"
---

# ADR-0039: La exportación a medida y el fichero de alta de usuarios viven en el aplicativo

## Estado

Propuesta

## Contexto

El panel «Solicitudes» del taller exportaba un único CSV con todas las
columnas y una fila por solicitud (`Applications::csv()`,
`src/Prc/PublicFront/Applications.php`), por POST con nonce y con
`ProcedureAccess::can_review()` (`ProcedureEditor::handle()`). Las peticiones
recibidas durante un trimestre de uso del sistema anterior —el material está
en `.local/` (ADR-0009)— repiten dos cosas que ese CSV no da:

- elegir columnas, y sacar una fila por persona coordinadora y no por
  solicitud, para cruzar listados sin limpiar la hoja a mano;
- un fichero para dar de alta en un aula virtual a quienes coordinan, que
  hoy se compone a mano a partir del CSV.

El aula virtual de destino es Moodle. Su herramienta de subida de usuarios
(«Upload users», <https://docs.moodle.org/en/Upload_users>) lee un CSV cuya
primera línea nombra los campos; `username`, `firstname`, `lastname` y
`email` son los que necesita para crear una cuenta. El formato es público y
no dice nada de ninguna instalación.

Lo que ya estaba decidido y no cambia: una solicitud cuelga de su
procedimiento, una por centro (ADR-0018); las respuestas se guardan por la
`key` de la pregunta (ADR-0019); un documento privado sale por su nombre y
nunca por su ruta (ADR-0028); un procedimiento histórico solo lo exporta
administración (ADR-0023). La coordinación guardaba `name`, `dni` y `email`;
`name` era «Nombre y apellidos» en un solo campo.

## Problema

¿Dónde vive el formato de la exportación a medida y del fichero de usuarios,
qué datos personales salen en cada uno y quién los puede descargar?

## Factores de decisión

- Quien gestiona no debería tener que limpiar el fichero para usarlo.
- Los datos personales salen solo hacia quien ya los ve en pantalla.
- El CSV de siempre no cambia: quien ya lo usa no nota nada.
- Una pregunta borrada o reordenada con solicitudes presentadas no puede
  descolocar ninguna columna.
- Nada de ninguna organización en el código (ADR-0009): ni el nombre del
  aula virtual, ni su dirección, ni cómo se forma el usuario corporativo en
  una instalación concreta.
- El bundle es un snippet: lo que necesite el aplicativo para funcionar va
  dentro, y lo que sea de una instalación va en un filtro.

## Alternativas consideradas

### Opción 1: el formato en un snippet suelto

Un `snippets/*.php` propio que lea las solicitudes y componga el fichero de
usuarios, como `roles-and-profiles.php`.

- **Pro:** el bundle no sabe nada de Moodle.
- **Contra:** el snippet tendría que volver a leer las solicitudes, las
  preguntas y la coordinación, y comprobar otra vez quién puede descargar:
  una segunda puerta a los mismos datos personales, fuera de
  `ProcedureAccess` y sin sus pruebas.
- **Contra:** el diálogo del taller tendría que saber si el snippet está
  desplegado; con `function_exists()` la opción aparecería y desaparecería
  según la instalación, sin que quien gestiona sepa por qué.

### Opción 2: que cada cual lo componga en la hoja de cálculo

Ampliar solo el CSV y dejar el fichero de usuarios a mano.

- **Pro:** nada nuevo que mantener.
- **Contra:** es lo que se hace hoy y lo que se pide quitar: partir nombres,
  quitar duplicados y guardar con coma, sin BOM y con saltos LF es justo
  donde se equivoca una persona, y Moodle rechaza o duplica cuentas por ello.

### Opción 3: el formato en el aplicativo, detrás de la misma puerta

Una clase `PublicFront/ApplicationExport` con los dos formatos, llamada desde
la misma operación `export` del taller, con el mismo nonce y el mismo
`can_review()`.

- **Pro:** una sola puerta para los datos personales de las solicitudes, con
  sus pruebas.
- **Pro:** las columnas se validan contra las preguntas que el procedimiento
  tiene hoy, por su `key`: una borrada se ignora y no corre las demás.
- **Contra:** el bundle crece y sabe de un formato de un tercero. Es un
  formato estable, de cuatro columnas, y lo que es de cada instalación —cómo
  se forma el usuario— sale por un filtro.

## Decisión

Haremos la opción 3.

- `ApplicationExport::groups()` ofrece las columnas de la tabla agrupadas
  (solicitud, centro, quien presenta, coordinación, revisión, preguntas);
  `csv()` exporta las elegidas, en el orden pedido, con una fila por
  solicitud (`application`) o por persona coordinadora (`person`). Una clave
  que no existe se ignora; sin ninguna válida, o con todas elegidas aunque
  sea en otro orden, salen todas en el orden de la tabla: el CSV de siempre,
  con el mismo formato (`;`, BOM, CRLF, todo entrecomillado y las fórmulas
  neutralizadas). `Applications::csv()` delega aquí.
- `ApplicationExport::moodle()` escribe exactamente la cabecera
  `username,firstname,lastname,email`, separado por comas, UTF-8 sin BOM,
  saltos LF y sin salto tras la última fila. Entrecomilla solo lo que lo
  exige. El nombre y los apellidos que empiecen por `=`, `+`, `-` o `@`
  llevan una comilla simple delante, porque ese fichero también se abre en
  una hoja de cálculo; el usuario y el correo salen tal cual, porque ya están
  validados y una comilla delante los convertiría en otro usuario y otro
  correo. Una fila por persona, sin repetir `username` sin distinguir
  mayúsculas.
- Lo que no puede crear una cuenta no sale, y se cuenta aparte por motivo
  (`people()` devuelve `left_out`):
  - **sin correo válido**;
  - **incompleta**: sin nombre, sin apellidos o sin usuario. Es una cuenta sin
    nombre ni apellidos cuyo nombre público es el propio usuario —que no se
    toma por nombre—, una coordinación antigua de una sola palabra o una
    persona a la que el filtro deja sin usuario;
  - **usuario en conflicto**: el mismo `username` con **otro** correo. No se
    funde en silencio: se queda la primera y la otra se cuenta. Con el mismo
    usuario y el mismo correo es la misma persona, y no cuenta como omitida.

  Un hueco de coordinación vacío no es una persona y no cuenta.
- `username` es el `user_login` de la cuenta de quien presenta y, para quien
  se tecleó en un formulario, la parte local de su correo en minúsculas. Cada
  instalación lo cambia con el filtro
  `prc_export_username( string $username, array $person )`; devolver la
  cadena vacía deja a la persona fuera.
- La coordinación gana `surname`, obligatorio en el formulario. Lo guardado
  antes se lee con `surname` vacío y el nombre entero; se parte por el
  primer espacio (`ApplicationMetaRegistration::split_name()`) al exportar y
  al abrir esa solicitud en el formulario, para que guardarla no repita los
  apellidos dentro del nombre. Para quien presenta salen el nombre y los
  apellidos de su cuenta y, si falta alguno, su nombre público partido igual.
- El diálogo pregunta, para el fichero de usuarios, solo **quiénes**:
  coordinación y quien presenta. `ApplicationExport::who()` es la lista de
  fuentes; las personas de la solicitud del PR 4 entran ahí.
- Como la descarga es la respuesta del envío y no puede volver con un aviso,
  el diálogo cuenta **antes de descargar**, por fuente, cuántas personas
  saldrían y cuántas se quedarían fuera por cada motivo, con su rótulo.
  Los recuentos de todas las fuentes salen de una sola pasada
  (`who_counts()`) sobre las solicitudes que el panel ya ha cargado para la
  tabla: añadir una fuente no añade consultas.
- La última elección se recuerda por persona y procedimiento en la meta de
  usuario `prc_export_{id}`, y se vuelve a validar al leerla. Al borrar el
  procedimiento para siempre (`before_delete_post`) se borran las de todas
  las personas; la papelera no las toca.
- Sin JavaScript, el diálogo es un desplegable con el mismo formulario;
  «Todas las columnas» es un campo que lee el servidor. Con JavaScript el
  `<summary>` se cambia por un `<button aria-haspopup="dialog">` que abre un
  diálogo modal: un desplegable que abre un diálogo se anunciaría como lo
  que no es.

### Qué datos personales salen y a quién

| Formato | Datos de personas | Quién lo descarga |
|---|---|---|
| CSV | Nombre y correo de la cuenta que presenta; nombre, apellidos, DNI o NIE y correo de cada persona coordinadora; la nota de revisión; las respuestas y el **nombre** de cada documento (ADR-0028) | Quien revisa el procedimiento: `can_review()`, por ámbito; histórico, solo administración (ADR-0023) |
| Fichero de usuarios | Usuario, nombre, apellidos y correo de las fuentes elegidas. **Ni DNI ni teléfono** | Los mismos |

Son los mismos datos que esa persona ya ve en el panel «Solicitudes». La
descarga no se registra en ningún sitio; la elección guardada en la meta de
usuario es una lista de claves de columnas, no datos de nadie.

## Consecuencias

### Positivas

- Quien gestiona elige columnas y forma sin limpiar la hoja, y obtiene el
  fichero de alta listo para subir.
- Una sola puerta para exportar datos personales, con pruebas: columnas,
  forma, duplicados, correos inválidos, fórmulas, comas, comillas, tildes y
  eñes.
- El CSV de siempre sigue saliendo igual, también para un envío sin los
  campos del diálogo.

### Negativas

- El bundle sabe del formato de un tercero. Si Moodle cambiara su cabecera,
  hay que tocar el aplicativo.
- Partir por el primer espacio se equivoca con los nombres compuestos que se
  guardaron antes de los apellidos («María José Pérez» sale como «María» y
  «José Pérez»). Lo nuevo ya llega separado.
- Pedir `surname` obliga a quien edite una solicitud antigua a escribir los
  apellidos de la coordinación antes de poder guardarla.
- El recuento de quien se queda fuera recorre en memoria todas las
  solicitudes al pintar el panel, aunque sin consultas nuevas. La descarga
  las lee por tandas de cien, pero la caché de objetos de la petición las
  conserva.

### Neutras

- El fichero de usuarios lleva todas las solicitudes del procedimiento, sea
  cual sea su estado de revisión. Filtrar por estado es del PR 6, que hará
  que la exportación respete el filtro activo.
- Una cuenta de quien presenta sin nombre ni apellidos deja de salir en el
  fichero de usuarios hasta que alguien se los ponga: es preferible a crear
  en Moodle una cuenta llamada como su usuario.
