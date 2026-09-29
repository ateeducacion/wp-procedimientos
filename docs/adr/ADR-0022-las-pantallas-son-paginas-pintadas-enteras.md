---
id: ADR-0022
title: "Las pantallas son páginas de WordPress pintadas enteras por código"
status: Propuesta
date: 2026-09-15
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0001, ADR-0006, ADR-0007, ADR-0008, ADR-0009, ADR-0010, ADR-0011, ADR-0014, ADR-0019, ADR-0020, ADR-0023]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-fable-5-1"
---

# ADR-0022: Las pantallas son páginas de WordPress pintadas enteras por código

## Estado

Propuesta (2026-09-15). Nada implementado ni dibujado: la forma del taller es
una decisión del equipo de desarrollo a partir de lo que hay, **no** de una
sesión de diseño con la persona usuaria. Es revisable en cuanto se enseñe.

## Contexto

Hoy cada pantalla del aplicativo es **una página del constructor de páginas
con un shortcode del gestor de formularios dentro**, y ese shortcode ejecuta
una «vista»: una plantilla HTML con condicionales escrita en un campo de texto
del gestor. Medido sobre la foto del 2026-09-15 (material en `.local/`):

- **279 vistas** en el sitio; la de la convocatoria pública tiene 24 KB de
  HTML, shortcodes del maquetador y condicionales anidados, y decide qué
  enseñar comparando fechas con «ahora» dentro de la plantilla.
- **El alta y la edición** son el mismo formulario de 123 campos en
  dieciséis secciones plegables; el estado, las fechas, los enlaces, las
  preguntas, la subida de documentación y el CSS a medida están todos en la
  misma pantalla, y una segunda copia «exprés» del formulario enseña 18.
- **La gestión de solicitudes** es otra página con una tabla filtrada por
  parámetros de la URL y un complemento de exportación a CSV; para llegar
  desde la convocatoria hay que pasar por el listado de gestión.
- **Lo que las vistas no alcanzan** lo hacen fragmentos de JavaScript en el
  pie: filtrar filas por ámbito en el navegador, forzar el sufijo del curso en
  el título, ocultar botones hasta que aparezca un texto.

El aplicativo de eventos ya resolvió las dos mitades de esto
([ADR-0010](ADR-0010-se-parte-del-esqueleto-del-aplicativo-de-eventos.md)):
**cómo** se pinta —un documento entero en `template_redirect`, un shortcode
por pantalla, `Shell::render_standalone()`— y **qué forma** tiene el taller
—una sola pantalla con paneles, en vez de un formulario largo—. Esta ADR
adopta las dos y las adapta al dominio.

## Problema

¿Cómo se pintan las cinco pantallas del aplicativo y la ficha pública del
procedimiento, y cómo se organiza el taller de un procedimiento para que se
vea a la vez **qué procedimiento**, **en qué estado** y **qué le falta**?

## Factores de decisión

- **Código versionado y con tests**, no plantillas en campos de texto.
- **Independencia del tema.** El tema del sitio de destino no es el del
  entorno de desarrollo, y no controlamos ninguno.
- **Se despliega pegando código**: no se pueden añadir plantillas a un tema
  ([ADR-0001](ADR-0001-repo-entorno-desarrollo-no-plugin.md)).
- **Nada de nadie en el documento**: cabecera, pie y analítica son de quien
  despliega ([ADR-0009](ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md)).
- **Toda escritura por POST con nonce y capacidad**, patrón POST → redirect →
  GET.
- **Menos campos a la vez.** La referencia a batir son 123 campos en una
  pantalla.
- **Sin JavaScript obligatorio**: el formulario funciona sin él.

## Alternativas consideradas

### A. Cómo se pinta

#### Opción A1: dentro de `the_content`, con el tema alrededor

| Pros | Contras |
|------|---------|
| Cabecera, pie, cookies y analítica salen solos. | El aspecto depende de un tema que no controlamos; cualquier regla suya pisa las nuestras y la defensa es `!important`. |
| | Arrastra las hojas y guiones de todo lo instalado. |

Descartada.

#### Opción A2: plantilla de página del tema

Descartada: es una plantilla **por tema**, y un Code Snippet no puede añadir
ficheros a un tema ajeno.

#### Opción A3: pantallas del escritorio (`add_menu_page`)

| Pros | Contras |
|------|---------|
| Estilos y tablas de WordPress gratis. | Quien dirige un centro no tiene que ver el escritorio para presentar una solicitud; la ficha pública no puede vivir ahí. |
| | Dos mundos: uno para gestionar y otro para solicitar. |

Descartada.

#### Opción A4: documento entero en `template_redirect` (elegida)

Lo que ya hacen las pantallas del aplicativo de eventos. Un solo mecanismo
para las cinco pantallas y la ficha pública.

### B. La forma del taller

#### Opción B1: un formulario largo con secciones plegables

Es lo que hay. Descartada: es la cifra a batir.

#### Opción B2: una página por panel

`editar-procedimiento`, `preguntas-del-procedimiento`, `solicitudes`…

| Pros | Contras |
|------|---------|
| Cada pantalla es pequeña. | El contexto —qué procedimiento, en qué estado— se pierde en cada viaje. |
| | Cinco slugs más que crear, enlazar y proteger. |

Descartada.

#### Opción B3: una sola pantalla con paneles (elegida)

| Pros | Contras |
|------|---------|
| El procedimiento y su estado siempre a la vista; cada panel, un formulario corto. | Un `?panel=` más que validar; una pantalla que carga el recuento de solicitudes en cada visita. |

## Decisión

**Las pantallas son páginas de WordPress con un shortcode cada una, pintadas
enteras por código en `template_redirect`; el taller del procedimiento es una
sola pantalla con cinco paneles.**

### Cómo

1. `scripts/setup-pages.php` crea las páginas de `Shell::SLUGS` colgando de
   la página madre de la opción `prc_pages_parent`, cada una con su shortcode
   de `Shell::SHORTCODES`. El shortcode solo sirve para que `Shell` reconozca
   la página; no pinta nada por sí mismo.
2. En `template_redirect` (prioridad 20) `Shell` detecta la página o el
   `single` de `prc_procedure`, comprueba la capacidad, pinta desde
   `<!doctype html>` hasta `</html>` y sale por `Shell::leave()`, que en los
   tests lanza `ExitSignal`. `wp_head()` y `wp_footer()` se llaman: lo que
   quien despliega enganche ahí sigue saliendo.
3. Bootstrap 5, Bootstrap Icons y SweetAlert2 desde CDN con SRI
   ([ADR-0006](ADR-0006-librerias-de-terceros-desde-cdn-con-sri.md)), con
   degradación a `confirm()` y a formulario sin JavaScript.
4. Los módulos `View/*` **solo pintan**: reciben un array y devuelven HTML con
   toda la salida escapada. La lógica está en `PublicFront/*` y en `Domain/*`.
5. Toda escritura va por POST con nonce y `ProcedureAccess`
   ([ADR-0014](ADR-0014-el-ambito-es-un-alcance-no-un-rol.md)), y termina en
   un redirect con `?aviso=` para el mensaje. Nunca en GET.
6. Cabecera, pie y enlaces legales del documento son los que devuelva el
   filtro `prc_chrome`, vacío por defecto
   ([ADR-0009](ADR-0009-el-repositorio-se-publica-sin-nada-de-nadie.md)).

### Las pantallas

| Clave | Slug | Quién | Qué |
|---|---|---|---|
| `home` | `procedimientos` | Público | Listado por curso agrupado por estado derivado, filtro por ámbito |
| `procedure` | single de `prc_procedure` | Público | Ficha: descripción, fechas, estado, ámbito, contacto, enlaces; botón «Solicitar» / «Mi solicitud» / «Gestionar» según quién mira |
| `workspace` | `gestion-de-procedimientos` | `prc_manage_procedures` | Mis procedimientos por curso con estado y nº de solicitudes; «Nuevo procedimiento» |
| `editor` | `editar-procedimiento` | `prc_manage_procedures` | El taller |
| `apply` | `solicitud` | `prc_apply` | Formulario de solicitud para `?procedimiento=ID`, o la presentada con su estado y nota |
| `mine` | `mi-centro` | `prc_apply` | Las solicitudes del centro |

### El taller

`editar-procedimiento?id=N&panel=…`. **Arriba**, la cabecera del
procedimiento: título, estado derivado con su etiqueta, ámbito, curso, fechas
del plazo, «Ver la ficha». Nunca hay duda de qué se está tocando ni de si se
puede tocar. **Debajo**, cinco paneles, cada uno con su propio formulario y su
propio POST:

| Panel | Qué lleva |
|---|---|
| **Datos** | Título, descripción, ámbito, curso, dirigido a, titularidad, fechas de solicitud y de subsanación, contacto, compromisos, coordinación |
| **Preguntas** | El constructor de `prc_questions` ([ADR-0019](ADR-0019-la-solicitud-es-nucleo-fijo-mas-preguntas.md)): añadir, reordenar con «subir/bajar» por POST, borrar |
| **Enlaces** | Resolución, listado provisional, listado definitivo |
| **Solicitudes** | Tabla: centro, código, fecha, cargo, coordinación, estado de revisión; acciones admitir / subsanar / excluir con nota ([ADR-0020](ADR-0020-la-subsanacion-es-un-plazo-y-un-estado.md)); exportar CSV |
| **Publicación** | Publicar / volver a borrador; marcar como histórico ([ADR-0023](ADR-0023-el-estado-historico-cierra-la-edicion.md)); enviar a la papelera ([ADR-0007](ADR-0007-borrar-es-enviar-a-la-papelera.md)) |

Qué panel se puede editar en cada estado lo dice la tabla de la SDD: en
`closed` y `resolved` solo Enlaces, fechas y Solicitudes; en `archived`,
nada. El panel se pinta igual, en solo lectura, con el motivo arriba. El
bloqueo de edición es el de WordPress
([ADR-0008](ADR-0008-bloqueo-de-edicion-con-el-de-wordpress.md)).

## Consecuencias

### Positivas

- Las seis pantallas se prueban enteras: doctype, `<h1>` único, escape,
  quién puede y quién no.
- El aspecto no depende del tema del sitio de destino.
- 123 campos en una pantalla pasan a cinco paneles cortos con el estado
  siempre a la vista.
- El filtrado por ámbito ocurre en el servidor, en `WP_Query`, y no en el
  navegador ocultando filas.

### Negativas

- **Somos responsables del documento entero**: `<title>`, idioma,
  accesibilidad del esqueleto. Un fallo sale publicado.
- **Consentimiento de cookies y analítica** siguen si están en `wp_head` o
  `wp_footer`; si estaban en el `footer.php` del tema, desaparecen. No está
  comprobado contra producción.
- **Sin arrastrar y soltar.** Reordenar preguntas es «subir/bajar» por POST.
  Basta, y es un solo camino que probar; el arrastre llegará si alguien lo
  pide.
- **La forma del taller no se ha enseñado a nadie.** Lo que en el aplicativo
  de eventos salió de una sesión de diseño aquí sale del código. Es lo primero
  que hay que validar con quien gestiona, y puede cambiar.
- **El taller carga más**: el procedimiento, sus metas y el recuento de
  solicitudes en cada visita. No está medido.

### Neutras

- La barra de administración sigue saliendo para quien tenga sesión, porque
  la pinta `wp_footer()`.
- La lista del escritorio de `prc_procedure` y `prc_application` existe para
  diagnóstico; no es una pantalla del aplicativo.
