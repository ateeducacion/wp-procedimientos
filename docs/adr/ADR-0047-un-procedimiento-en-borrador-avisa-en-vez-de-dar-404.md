---
id: ADR-0047
title: "Un procedimiento en borrador avisa en vez de dar 404"
status: Propuesta
date: 2026-11-17
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0007, ADR-0009, ADR-0022]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5-5"
---

# ADR-0047: Un procedimiento en borrador avisa en vez de dar 404

## Estado

Propuesta (2026-11-17).

## Contexto

La ficha pública de un procedimiento es el `single` de `prc_procedure`,
pintado entero por `PublicFront/ProcedureView` en `template_redirect`
([ADR-0022](ADR-0022-las-pantallas-son-paginas-pintadas-enteras.md)). Solo se
pinta si WordPress encuentra la entrada: `ProcedureView::takes_over()` pide
`is_singular()` a través de `Shell::current_section()`
(`src/Prc/PublicFront/Shell.php`, `current_section()`).

Cuando el procedimiento **no está publicado** —borrador, pendiente de
revisión, programado o privado—, `WP_Query` solo lo devuelve a quien puede
editarlo o leer lo privado; a cualquier otra persona le da un 404
(`wp-includes/class-wp-query.php`, `get_posts()`, la comprobación de
`read_private_posts` y `edit_post` sobre los estados no públicos). El tema
pinta entonces su «No se encontró la página».

Y el enlace de una convocatoria que se está preparando circula antes de
tiempo: se pasa para revisarlo, se apunta para el día de la publicación, o
alguien lo encuentra. Quien llega por él lee que la página no existe —y sí
existe—, y quien la prepara, si no ha iniciado sesión, no tiene por dónde
entrar. El sistema que se sustituye respondía con un aviso de que la
convocatoria estaba en borrador y un enlace de acceso para responsables, y
quien lo usa lo espera.

## Problema

¿Qué contesta el sitio a quien abre la dirección de un procedimiento que
existe pero que todavía no puede ver?

## Factores de decisión

- No contar nada de un borrador: el título o la descripción pueden llevar lo
  que todavía no se puede anunciar.
- Distinguir «no he iniciado sesión» de «he entrado con una cuenta que no
  tiene permiso», sin que haga falta preguntar a nadie.
- No cambiar nada para quien sí puede ver el borrador: sigue viendo la ficha.
- Que no lo indexe ningún buscador ni lo guarde ninguna caché.
- Nada de ninguna organización en el código
  ([ADR-0009](ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md)).
- Lo de la papelera es lo borrado ([ADR-0007](ADR-0007-borrar-es-enviar-a-la-papelera.md)):
  no se anuncia.

## Alternativas consideradas

### Opción 1: dejar el 404 del tema

No hay código que mantener. Pero es justo lo que se quiere arreglar: dice
algo falso y deja fuera a quien prepara el procedimiento. Descartada.

### Opción 2: mandar a iniciar sesión

Un `wp_login_url()` en vez del 404. Resuelve a quien prepara, pero a
cualquier otra persona la manda a un formulario de acceso que no le sirve y le
confirma que ahí hay algo sin decirle qué. Y quien ya ha entrado sin permiso
volvería al mismo 404. Descartada.

### Opción 3: plantilla `404.php` propia o filtro sobre la del tema

Depende del tema, y el aplicativo no trae tema: se despliega con Code
Snippets. Descartada.

### Opción 4: aviso propio en `template_redirect` (elegida)

`PublicFront/DraftNotice` engancha `template_redirect` en la prioridad 1
(`src/Prc/PublicFront/DraftNotice.php:50`), antes de `redirect_canonical()` y
`wp_old_slug_redirect()`, que van en la 10 (`wp-includes/default-filters.php`)
y podrían mandar a otra página parecida. Si la petición es un 404 y lo pedido
—por número, `p`, o por su ruta, `prc_procedure` / `name`— es un
`prc_procedure` en `draft`, `pending`, `future` o `private`, sirve una página
corta y sale por `Shell::leave()` (`src/Prc/PublicFront/Shell.php:963`), la
costura que en los tests lanza `ExitSignal` en vez de terminar.

## Decisión

Haremos la opción 4. La página:

- responde **404**, con `nocache_headers()`, `X-Robots-Tag: noindex` y
  `<meta name="robots" content="noindex">`;
- es un documento propio, pequeño, con el CSS dentro y legible en el móvil; no
  carga el tema, ni la analítica, ni el aviso de cookies;
- dice que el procedimiento **no se ha publicado y está en modo borrador**,
  que la participación de los centros todavía no se ha publicado y que se
  informará por los canales habituales y en la portada;
- **sin sesión**, ofrece «Acceso para responsables», que es
  `wp_login_url()` con vuelta al propio procedimiento;
- **con sesión y sin permiso**, dice con qué cuenta se ha entrado, que esa no
  puede ver el borrador, y ofrece «Entrar con otra cuenta»
  (`wp_logout_url()` hacia el mismo acceso);
- **no** imprime el título ni nada del procedimiento;
- lleva logo solo si quien despliega lo configura en el filtro `prc_chrome`,
  con las claves nuevas `brand_logo` y `brand_alt`, vacías por defecto
  (`src/Prc/PublicFront/View/ProcedureChrome.php:66`).

Quien puede ver el borrador no pasa por aquí: no hay 404 y la ficha la pinta
`ProcedureView` como siempre. Lo que está en la papelera sigue dando el 404.

## Consecuencias

### Positivas

- Quien llega por un enlace adelantado entiende qué pasa, y quien prepara el
  procedimiento entra desde ahí y vuelve a él.
- La diferencia entre «sin sesión» y «sin permiso» la ve la propia persona.
- Probado: `tests/unit/test-draft-notice.php` cubre sin sesión, con sesión sin
  permiso, administración, publicado, papelera, la ruta bonita y el logo.

### Negativas

- Confirma que en esa dirección hay un procedimiento sin publicar, aunque no
  diga cuál. Es lo que hacía el sistema anterior y lo que se pide; quien no
  quiera ni eso tiene que no compartir el enlace.
- Un borrador nuevo no tiene nombre hasta que se publica, así que su enlace es
  por número (`?post_type=prc_procedure&p=N`); el aviso cubre las dos formas,
  pero solo esas dos.
- Una plantilla más fuera del armazón: su aspecto no sigue los cambios de
  `Shell::render()`.

### Neutras

- `prc_chrome` gana dos claves vacías por defecto; sin configurar, nada cambia.
