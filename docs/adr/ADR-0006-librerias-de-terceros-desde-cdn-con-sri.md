---
id: ADR-0006
title: "Las librerías de terceros se cargan desde jsDelivr con SRI; en desarrollo, desde node_modules"
status: Propuesta
date: 2026-09-15
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0001, ADR-0002, ADR-0004, ADR-0007, ADR-0022]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-fable-5-1"
---

# ADR-0006: Las librerías de terceros se cargan desde jsDelivr con SRI; en desarrollo, desde `node_modules`

## Estado

Propuesta (2026-09-15). Los dos snippets que estrenan la política ya están
en el repositorio (`snippets/bootstrap5.php`, `snippets/sweetalert.php`) y
las versiones en `package.json`; el mu-plugin de desarrollo lo trae la fase 0.

## Contexto

Este repositorio **no es un plugin**
([ADR-0001](ADR-0001-repo-entorno-desarrollo-no-plugin.md)). Lo que se
despliega es un snippet: un único fichero PHP que concatena `src/Prc/`. En
producción **no hay directorio en disco desde el que servir un `.css` o un
`.js`**, no hay `plugins_url()` y no hay forma de encolar un fichero propio.
El CSS y el JavaScript del aplicativo viajan en línea dentro del bundle
(`PublicFront/Assets`).

Una librería de terceros, por tanto, solo tiene dos caminos: meterla dentro
del bundle o pedirla a una URL. Y la disyuntiva se resuelve sola en cuanto se
mira el tamaño: Bootstrap 5 minificado son unos 300 KB de código ajeno dentro
de un snippet que revisa una persona en un cuadro de texto.

### Lo que hay en el sitio de destino

Está inventariado en `.local/`, y es el caso que obliga a escribir esto:

- **Un fragmento activo, de ámbito global, carga Bootstrap 4 en todas las
  páginas del sitio**, sin `integrity` ni versión en el encolado, desde un
  CDN de hace años; su nombre dice una versión y carga otra.
- Otro fragmento, inactivo, carga Bootstrap 5 e iconos —y lleva la clave de
  API de un servicio de mapas escrita en claro—.
- Un tercero retira el 4 y pone el 5 **solo en las pantallas de otro
  aplicativo** del mismo sitio, con el mismo patrón que aquí se adopta.
- Y una fuente de iconos se pide a un servicio de Google en cada página.

El aplicativo se dibuja con Bootstrap 5 y pregunta si está para ponerle al
`body` la clase `prc-sin-bootstrap` cuando no lo está. Con el fragmento
global registrando el handle `bootstrap-css`, una comprobación por nombre de
handle diría «sí» y el aplicativo se dibujaría sobre Bootstrap 4. Las dos
versiones no conviven en la misma página, y no se puede cambiar el aspecto
del resto del sitio para acomodar nuestras pantallas.

## Problema

¿De dónde salen las librerías de terceros del aplicativo —en producción, en
desarrollo y en la CI—, y cómo se ancla su versión para que las tres estén
mirando el mismo fichero?

## Factores de decisión

- **No hay dónde servirlas**: el artefacto de producción es un snippet.
- **El bundle no debe engordar con código ajeno** que nadie revisa y del que
  nadie avisa cuando tiene una vulnerabilidad.
- **La CI no puede depender del DNS** ([ADR-0004](ADR-0004-ci-y-politica-de-pruebas.md)):
  eventos pagó una tarde por un parpadeo de red disfrazado de fallo de maqueta.
- **Integridad comprobable**: `integrity` y `crossorigin`, sin excepción.
- **Una sola versión, en todos los sitios.**
- **La pantalla tiene que seguir siendo utilizable si el CDN no contesta.**
  Un centro tiene que poder presentar su solicitud el último día del plazo
  aunque falte una hoja de estilos.
- **Una sola política.**

## Alternativas consideradas

### Opción 1: jsDelivr con SRI en producción y `node_modules` en desarrollo — ELEGIDA

- Pros: cero bytes en el bundle; la integridad la comprueba el navegador; la
  versión queda en la URL, así que subirla es un diff legible; en desarrollo
  y CI no se sale a la red.
- Contras: la misma versión hay que mantenerla en tres sitios; el navegador de
  quien visita pide un recurso a un tercero; en producción sigue habiendo una
  dependencia de red en tiempo de ejecución, que se cubre con la degradación.

### Opción 2: inlinear la librería en el bundle

- Contras: cientos de KB de código ajeno en un fichero que se pega en un
  cuadro de texto; actualizarla es copiarla a mano y nada avisa de un parche
  de seguridad. Descartada.

### Opción 3: servir las librerías desde `uploads` del sitio

- Contras: el fichero deja de estar en el repositorio: ni se versiona, ni se
  revisa, ni se despliega con `make sync-snippets`
  ([ADR-0002](ADR-0002-sincronizacion-snippets-eval-file.md)). Descartada.

### Opción 4: no usar librerías de terceros

- Pros: cero dependencias, cero peticiones.
- Contras: es la opción por defecto para las piezas pequeñas, y esta ADR no
  la descarta: la acota. Un sistema de rejilla y componentes no se reimplementa;
  un ordenador de tablas, sí. **Primero mirar si no hace falta; si hace
  falta, esta ADR dice de dónde sale.**

## Decisión

Se adopta la misma decisión que el aplicativo de eventos, y por las mismas
razones.

### 1. En producción: jsDelivr, versión exacta, `integrity` y `crossorigin`

Desde `https://cdn.jsdelivr.net/npm/…` con la versión **clavada en la URL**,
`integrity` con el hash calculado contra el mismo CDN —jsDelivr minifica al
vuelo, así que el hash del paquete no tiene por qué coincidir— y
`crossorigin="anonymous"`. El comando que genera el hash va en un comentario
junto al hash.

Dos snippets sueltos, fuera del bundle, con `Priority: 20`:

| Snippet | Qué carga | Dónde |
|---|---|---|
| `snippets/bootstrap5.php` | Bootstrap 5.3.8 y bootstrap-icons 1.13.1 | solo en las páginas de `Shell::SLUGS` y en el `single` de `prc_procedure`; antes retira los handles `bootstrap-css`, `bootstrap-js` y `popper-js` que deja el fragmento global |
| `snippets/sweetalert.php` | SweetAlert2 11.26.25 (`all`, estilos dentro: un fichero, un hash) | las mismas pantallas, para las confirmaciones ([ADR-0007](ADR-0007-borrar-es-enviar-a-la-papelera.md)) |

El resto del sitio se queda con su Bootstrap 4 y a nadie se le cambia el
aspecto.

### 2. En desarrollo y en los tests: `node_modules`

Las mismas versiones se instalan como `devDependencies` con `--save-exact`, y
el mu-plugin `scripts/mu-plugins/prc-dev-tools.php` reescribe en
`script_loader_src` y `style_loader_src` cualquier URL
`cdn.jsdelivr.net/npm/<paquete>@<versión>/<fichero>` a la copia local, con
tres condiciones: el paquete está instalado, su versión es **exactamente** la
de la URL, y el fichero existe (si jsDelivr minificó al vuelo, vale el
original). Si alguna falla, se deja la URL del CDN. El mu-plugin no se
despliega nunca.

### 3. La versión coincide en tres sitios

`package.json`, la URL del CDN y el `$ver` del encolado. Si no coinciden, o el
SRI no cuadra o el reescritor no encuentra el fichero y la prueba vuelve a
salir a la red sin que nadie se entere.

### 4. La degradación no es opcional

Si el CDN no contesta, los datos siguen leyéndose, los formularios siguen
enviándose y la navegación sigue funcionando. La hoja propia del aplicativo
pinta el aspecto base y `prc-sin-bootstrap` en el `body` lo activa. Bootstrap
**mejora** la pantalla; no la sostiene. SweetAlert2 degrada a `confirm()` y,
sin JavaScript, el botón envía el formulario igual.

### 5. `has_bootstrap()` no adivina la versión

Que un handle se llame `bootstrap-css` no dice qué versión trae: el fragmento
global es la prueba. La verdad la pone quien la conoce: `snippets/bootstrap5.php`
contesta `true` al filtro `prc_has_bootstrap` y nadie más.

## Consecuencias

### Positivas

- **El bundle no crece** ni contiene una línea de código de terceros ni una
  URL externa: quien las pone es un snippet suelto.
- **La integridad la comprueba el navegador.** El fragmento de hoy, sin SRI,
  no tiene esa garantía.
- **La CI deja de depender del DNS**, y se puede trabajar sin conexión.
- **Subir de versión es un diff** de tres líneas en tres ficheros.
- **El aplicativo sabe qué Bootstrap tiene** porque lo carga él, y solo donde
  le toca.

### Negativas

- **Una versión en tres sitios.** Se convierte en un fallo con nombre solo si
  hay una comprobación que lo mire, y hay que escribirla.
- **Hay una petición a un tercero en producción**, con SRI y degradación, pero
  es una superficie que antes no se declaraba y ahora sí.
- **`npm install` pasa a ser un paso previo a los tests.**
- **Dos snippets sueltos más que mantener y sincronizar**, cada uno con su
  hash.
- **Convivencia frágil con el fragmento global**: si alguien renombra sus
  handles, dejamos de retirarlos y las dos versiones se pisan. Lo correcto a
  medio plazo es retirar ese fragmento, no convivir con él.

### Neutras

- La regla no obliga a usar librerías: primero se mira si no hace falta.
- El sitio de destino podrá cargar además la fuente de iconos que quiera en
  el resto de sus páginas; las pantallas del aplicativo usan bootstrap-icons
  y no la piden.
