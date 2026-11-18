---
name: manage-procedures
description: Lista y gestiona procedimientos educativos en el aplicativo de WordPress: buscar, crear borradores, completar datos y preguntas, editar enlaces y plazos, revisar solicitudes y publicar o cerrar cuando se pide. Actívala ante cualquier petición de gestión de convocatorias o procedimientos.
metadata:
  author: ateeducacion
  version: "1.0.0"
---

# Gestionar procedimientos

Usa esta skill para actuar sobre el aplicativo de procedimientos ya instalado en un sitio WordPress. No presupone un dominio, una cuenta ni que haya una API de IA instalada.

## Elegir cómo conectarse

1. Si hay una pestaña del sitio ya autenticada, úsala y conserva esa sesión. Si no, busca una herramienta o conexión REST autorizada disponible para esta tarea. No pidas ni guardes contraseñas, cookies o nonces.
2. Comprueba que la cuenta puede abrir «Mis procedimientos» y que el procedimiento está dentro de su ámbito. No deduzcas permisos por el nombre del rol: WordPress decide por capacidades y ámbito.
3. Usa el taller del aplicativo para altas y cambios completos. Sigue los enlaces y botones que muestra el sitio; los slugs de página se pueden configurar.
4. Puedes usar la REST API para leer o cambiar campos básicos solo después de descubrir la ruta en el índice REST del propio sitio y comprobar el permiso de la cuenta. `prc_procedure` está expuesto a REST, pero las metas propias no se exponen; `prc_application` no está expuesto. Por tanto, la REST API estándar no sirve para completar el procedimiento ni para gestionar solicitudes.
5. Si el índice REST anuncia `/wp-abilities/v1/abilities`, descubre sus descripciones, esquemas y permisos antes de usar una Ability; la API está en WordPress 6.9 o posterior, no en la versión mínima 6.5 de este aplicativo. Comprueba cada operación con la cuenta actual. Para usarla como herramienta MCP también hace falta que el sitio tenga configurado el MCP Adapter. La API por sí sola no crea una conexión MCP ni herramientas de navegador. No inventes nombres de abilities o rutas.

## Listar y encontrar

- Abre «Mis procedimientos» desde la navegación del sitio. Busca por título y acota por curso o estado desde sus filtros.
- Antes de editar, confirma que título y ámbito coinciden con lo pedido. Si hay varios resultados posibles, pregunta cuál es.
- Sigue el enlace «Gestionar» o el enlace equivalente del resultado para abrir su taller. No construyas a mano el identificador ni la URL.

## Crear

- Usa «Nuevo procedimiento» desde el listado. El alta crea un borrador; no lo publiques como parte automática de la creación.
- Rellena los datos proporcionados y los campos obligatorios que falten de la solicitud. No inventes fechas, contactos, ámbitos, cursos, compromisos ni condiciones.
- Después del alta, completa las pestañas necesarias del taller: Datos, Preguntas, Enlaces y, si corresponde, Entregas. Guarda cada panel y comprueba el aviso de resultado.
- Publica solo si la persona lo pide expresamente. Antes de publicar, revisa el título, ámbito, curso, fechas, preguntas y enlaces con lo solicitado.

## Editar y otras acciones

- En el taller, usa la pestaña correspondiente: Datos, Preguntas, Enlaces, Solicitudes, Entregas o Publicación. Abre «Código» solo si la persona lo pide y tiene permiso de administración.
- Cambia únicamente los campos que la persona indicó; conserva los demás. Si un campo obligatorio imprescindible no está especificado, pide ese dato antes de guardar.
- Para revisar solicitudes, trabaja solo en la pestaña del procedimiento elegido. No incluyas datos personales ni descargues documentos. Exporta CSV únicamente si lo piden.
- Publicar, marcar como histórico, desarchivar, borrar o cambiar decisiones de admisión son acciones explícitas: no las infieras de «crear», «actualizar» o «revisar».

## Guardar y confirmar

- Usa los controles normales del taller. El aplicativo guarda los cambios por formularios POST con nonce y vuelve a la pestaña con un aviso; no reproduzcas POST a mano ni eludas sus controles.
- Tras guardar, verifica el aviso y vuelve a leer los campos afectados. Si falla o el acceso se deniega, para y explica qué falta; no afirmes que el cambio se hizo.
- Resume el título, los cambios aplicados y el estado actual. No repitas datos personales de solicitudes.

## Límites de la API

El tipo de contenido de procedimientos tiene endpoint REST de WordPress, pero el código registra las metas del aplicativo con `show_in_rest` desactivado para proteger su edición directa y validación. Las solicitudes tienen `show_in_rest` desactivado en el propio tipo. Una integración de IA debe publicar operaciones deliberadas con esquemas y permisos por procedimiento a través de Abilities (WordPress 6.9+) o rutas propias; no debe abrir todas las metas ni sustituir `ProcedureAccess`. La ejecución REST de cada Ability requiere autenticación y respeta su `permission_callback`. Consulta la [referencia de Abilities](https://developer.wordpress.org/apis/abilities-api/) y sus [rutas REST](https://developer.wordpress.org/apis/abilities-api/rest-api-endpoints/). Para conectar un agente externo mediante MCP hace falta además un [MCP Adapter](https://github.com/WordPress/mcp-adapter).

El formato de esta skill sigue [Agent Skills](https://agentskills.io/specification). La fuente de permisos es `docs/roles-y-permisos.md`; ante cambios, vuelve a leer la implementación y esa documentación en vez de asumir que esta guía enumera todos los campos.
