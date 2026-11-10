---
id: ADR-0045
title: "La ayuda es una pantalla del aplicativo, con el contenido en código y la ayuda técnica en configuración"
status: Propuesta
date: 2026-11-10
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0009, ADR-0022]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5-5"
---

# ADR-0045: La ayuda es una pantalla del aplicativo, con el contenido en código y la ayuda técnica en configuración

## Estado

Propuesta

## Contexto

El aplicativo no tenía ayuda propia. La entrada «Ayuda» del menú
(`Shell::menu()`) solo aparecía si quien despliega configuraba enlaces en la
clave `footer_links` del filtro `prc_chrome`: sin configurar, no había entrada
ni ningún sitio donde explicar cómo se presenta una solicitud o cómo se da de
alta un procedimiento. No existía slug ni shortcode de ayuda en
`Shell::SLUGS` / `Shell::SHORTCODES`.

La ayuda del sistema que se sustituye no es una guía de uso, sino una página
para comunicar incidencias, y el material de gestión estaba detrás del acceso;
la comparación medida vive en `.local/` (ADR-0009). Las dudas que más se
repiten son de dos tipos: las de quien solicita —no ve el botón, no sabe si su
solicitud ha quedado presentada, no da de alta a las personas porque esa lista
se rellena después de presentar— y las de quien gestiona —el procedimiento sale
como «Próximo» porque faltan las fechas, las preguntas se crean de una en una—.
Los guiones de las dos guías y de las preguntas frecuentes están escritos y
revisados contra los rótulos reales de las pantallas.

Restricciones que aplican:

- El repositorio se publica en abierto: ni el nombre de una organización, ni
  sus buzones, ni sus direcciones pueden estar en el código
  ([ADR-0009](ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md)).
- En producción solo se despliega código pegado en Code Snippets: no hay
  ficheros que servir aparte del bundle, ni un analizador de Markdown cargado.
- Las pantallas son páginas de WordPress con un shortcode, pintadas enteras
  por código ([ADR-0022](ADR-0022-las-pantallas-son-paginas-pintadas-enteras.md)).
- Aún no hay capturas de pantalla: la mejora de diseño de las pantallas va en
  otros cambios y las capturas se harán sobre el resultado.

## Problema

¿Dónde vive la ayuda de uso del aplicativo, en qué forma se escribe su
contenido y de dónde sale la información de soporte técnico, que es distinta
en cada instalación?

## Factores de decisión

- Quien más necesita la ayuda todavía no ha entrado: tiene que leerse sin
  sesión.
- Otras pantallas tienen que poder enlazar a un apartado concreto, con una
  dirección estable.
- El contenido tiene que viajar con el bundle, versionado y revisado como el
  código, y nombrar los botones tal como se llaman hoy.
- Lo que se pinta tiene que estar escapado o filtrado, sin excepciones.
- Nada de ninguna instalación concreta en el repositorio.
- Las capturas llegan después y no deben obligar a reescribir la pantalla.

## Alternativas consideradas

### Opción 1: una página de WordPress escrita a mano en cada instalación

Quien despliega crea una página «Ayuda» con el editor y el menú la enlaza.

- A favor: cero código; cada instalación escribe lo suyo.
- En contra: el texto no está versionado ni revisado con el código, y se queda
  viejo en cuanto cambia un rótulo; cada instalación repite el trabajo; no hay
  anclas estables a las que enlazar desde las pantallas, y la página se pinta
  con el tema, no con el marco del aplicativo.

### Opción 2: guiones en Markdown dentro del repositorio, interpretados al servirse

- A favor: se escriben cómodos y se leen bien en el repositorio.
- En contra: hace falta un analizador de Markdown en producción, que el bundle
  tendría que llevar dentro o que no hay; y lo que produce habría que filtrarlo
  igual. Los ficheros sueltos no viajan en Code Snippets.

### Opción 3: una pantalla del aplicativo con el contenido como datos en código

Slug `ayuda`, shortcode `[prc_help]`, clases `PublicFront/Help`
(modelo y enrutado), `PublicFront/HelpContent` (el texto) y
`PublicFront/View/HelpView` (solo pinta). Cada guía es una lista de apartados
`{id, level, title, html, shots}` con un HTML corto y una lista cerrada de
etiquetas; los enlaces internos se escriben con un marcador que se resuelve con
`Shell::url()`. La ayuda técnica sale de la clave `help_contact` de
`prc_chrome`, vacía por defecto.

- A favor: viaja en el bundle; se revisa con el código y una prueba comprueba
  que ninguna etiqueta se sale de la lista; cada apartado tiene un ancla
  estable; se pinta con el marco del aplicativo; no depende de ninguna
  librería.
- En contra: el texto se edita en un fichero PHP, no en un editor visual, y
  cambiarlo pide un despliegue; una frase larga en una cadena PHP se lee peor
  que en Markdown.

## Decisión

Haremos la opción 3:

- **La ayuda es una pantalla más del aplicativo**, pública como la portada: slug
  `ayuda`, shortcode `[prc_help]`, sección `help` en `Shell::SLUGS`,
  `Shell::SHORTCODES` y `Shell::PUBLIC_SECTIONS`. La crea
  `scripts/setup-pages.php` como las demás.
- **Tres pestañas que son enlaces**: «Cómo presentar una solicitud»
  (`?guia=solicitar`, por defecto), «Cómo dar de alta un procedimiento»
  (`?guia=gestionar`) y «Preguntas frecuentes» (`?guia=preguntas`). Un valor
  desconocido cae en la primera. Sin JavaScript funcionan igual, porque cada
  guía es una dirección; llevan `aria-current` y no los roles de `tablist`, que
  prometerían un manejo con flechas que no hay.
- **El contenido vive en `HelpContent` como datos**, con un índice al principio
  de cada guía y un `id` estable por apartado, en castellano sin tildes. La
  vista lo pasa por `wp_kses()` con `HelpContent::ALLOWED`: párrafos, listas,
  `strong`, `em`, `code`, una tabla y enlaces con `href`.
- **`Help::url( $guia, $ancla )`** es la dirección de un apartado; con ella, la
  ficha pública (junto al acceso), la cabecera de la solicitud, el alta de un
  procedimiento y el panel «Preguntas» del taller llevan una línea discreta
  «¿Dudas? Consulte la ayuda».
- **La ayuda técnica sale de configuración**: la clave `help_contact` del
  filtro `prc_chrome`, un texto libre que se filtra con la misma lista. Sin
  configurar, el bloque «¿Necesita ayuda técnica?» solo remite al área
  convocante por el correo de contacto de cada procedimiento.
- **«Ayuda» está siempre en el menú**, también sin sesión. Si hay enlaces en
  `footer_links`, la entrada se vuelve un desplegable con la guía primero y esos
  enlaces detrás.
- **Las capturas vienen después**: cada una es un hueco con su texto
  («Captura pendiente: …») y un campo `image` vacío; cuando llegue la imagen,
  basta con poner su dirección y el hueco se pinta como figura.

## Consecuencias

### Positivas

- La ayuda existe en todas las instalaciones desde el primer despliegue, sin que
  nadie tenga que escribirla.
- Un cambio de rótulo y su ayuda pueden ir en el mismo cambio y revisarse
  juntos.
- Las pantallas que más dudas levantan llevan al apartado que las explica.
- Ningún buzón ni dirección real entra en el repositorio.

### Negativas

- Cambiar una coma de la ayuda pide editar PHP, regenerar el bundle y
  desplegarlo.
- El texto tiene que mantenerse a mano al día con los rótulos: no hay una prueba
  que compare cada nombre de botón citado con el que pinta su pantalla.
- Los huecos de las capturas se ven hasta que lleguen las imágenes.
- Con la entrada «Ayuda» siempre presente, quien entra sin sesión o sin perfil
  ve ahora un menú de dos entradas donde antes no había menú.

### Neutras

- El texto de soporte de cada instalación es HTML filtrado, no un campo de los
  ajustes: quien despliega ya configura el resto del marco con `prc_chrome`.
