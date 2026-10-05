---
name: profiler
description: Mide el rendimiento del aplicativo con PHP-SPX y compara dos versiones para encontrar dónde mejorar. Usar cuando se pida «perfilar», «medir rendimiento», «qué tarda», «/profiler», o comparar una rama con main.
metadata:
  author: ateeducacion
  version: "1.0.0"
---

# Perfilar el aplicativo con SPX

`make profile` reinicia wp-env con `--spx`, rehace el bundle, lo sincroniza y
pide cada pantalla de `SCENES` (`scripts/profile.mjs`) una vez en frío y `RUNS`
veces con SPX encendido. Guarda `artifacts/profile/<etiqueta>.json` e imprime:
tiempo, memoria y llamadas por pantalla; las funciones `Prc\…` por tiempo
inclusivo; y lo que más se lleva por sí mismo, sea de quien sea.

## Comparar una rama con main

```bash
git switch main     && make profile LABEL=main
git switch mi-rama  && make profile LABEL=mi-rama
make profile-compare A=main B=mi-rama
```

`make profile` sin `LABEL` usa el nombre de la rama.

## Cómo leerlo

- **Las llamadas son la señal fiable; el tiempo no.** La misma rama, dos
  pasadas seguidas, da hasta un ±50 % en tiempo —sobre todo si la primera va
  justo después de arrancar el contenedor— y un 0 % en llamadas. Un
  cambio de tiempo sin cambio de llamadas es ruido: repite con `RUNS=15`
  antes de creértelo.
- **Los tiempos van inflados por SPX.** Sirven para comparar entre sí, no como
  lo que tarda producción.
- **Busca primero en `Prc\…` inclusivo**: es lo que se puede tocar. Si una
  función propia se lleva mucho inclusivo y poco en sus hijas propias, abre
  su gráfico de llama para ver en qué núcleo de WordPress se va.
- **Lo exclusivo de WordPress** (`wpdb::_do_query`, `apply_filters`…) dice
  *qué tipo* de coste es: si sube `wpdb::_do_query` en llamadas, hay consultas
  de más (en un bucle, casi siempre).

## Ver el gráfico de llama

Los informes completos se quedan en el contenedor. Ábrelos en
<http://localhost:8698/?SPX_KEY=dev&SPX_UI_URI=/> (flame graph, árbol de
llamadas y línea de tiempo). Desde esa interfaz también se puede encender SPX
y perfilar a mano una acción que no esté en `SCENES`, como enviar una solicitud.

## Añadir una pantalla

Una línea en `SCENES` de `scripts/profile.mjs`: `who` vacío es sin entrar,
`{ID}` es un procedimiento abierto de demostración. Tiene que acabar en un 200: las
redirecciones se siguen (se mide la página de destino), pero acabar en el login
falla a propósito, porque perfilaría otra cosa.

## Quitar SPX

Se deja puesto para que la interfaz siga abierta. `npx @wordpress/env start`
arranca sin él.
