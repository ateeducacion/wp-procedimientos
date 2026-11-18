---
id: ADR-0038
title: "Una sola salida de correo, en texto plano, que escucha acciones"
status: Propuesta
date: 2026-11-10
related:
  issues: []
  prs: []
  sdds: [SDD-0001]
  adrs: [ADR-0009, ADR-0016, ADR-0018, ADR-0020, ADR-0028, ADR-0033]
supersedes: []
superseded_by: []
ai_assistance:
  tool: "Claude Code"
  model: "claude-opus-5-5"
---

# ADR-0038: Una sola salida de correo, en texto plano, que escucha acciones

## Estado

Propuesta

## Contexto

El aplicativo no mandaba ningún correo. La SDD-0001 lo deja anotado como
pendiente de la fase 1 («Correos: al presentar… y al cambiar el estado de
revisión»), y el
[PLAN-0003](../plan/PLAN-0003-lo-que-falta-para-sustituir-al-sistema-anterior.md)
lo pone como primer PR de la pila porque otros lo necesitan: el PR 4 avisa de
los cambios de personas y el PR 9 de las retiradas. Hoy, en el sistema que se
sustituye, cada formulario lleva sus propias notificaciones configuradas a
mano; el material está en `.local/` (ADR-0009).

Las restricciones que ya había:

- el repositorio se publica y no puede llevar ni remitentes, ni direcciones,
  ni nombres de una organización (ADR-0009);
- la solicitud se guarda en `Applications::save()` / `save_teacher()`
  (`src/Prc/PublicFront/Applications.php`), la revisión en
  `Applications::review()`, la entrega con plazo propio desde `ApplyForm`
  (ADR-0033) y el alta del procedimiento en `ProcedureEditor::create()`;
- todo son clases `final` con métodos `static` concatenadas en un snippet: no
  hay contenedor ni servicios que inyectar.

## Problema

¿Cómo manda correo el aplicativo de forma que un servidor de correo caído no
impida presentar, que cada instalación ponga su remitente sin tocar código, y
que las pantallas no acaben cada una con su `wp_mail()`?

## Factores de decisión

- Presentar no puede depender del correo: es un aviso, no parte de la solicitud.
- Un único sitio donde mirar qué sale, a quién y con qué cabeceras.
- Nada de ninguna organización en el código (ADR-0009).
- Que lo que se lee en el correo sea comprobable sin mandar nada.
- Que otros PR puedan añadir eventos sin tocar las pantallas existentes.

## Alternativas consideradas

### Opción 1: llamar a `wp_mail()` desde cada pantalla

Lo más corto: tres o cuatro llamadas en los sitios donde ya se guarda.

- A favor: no hay pieza nueva.
- En contra: cada pantalla tendría que saber de destinatarios, de cabeceras y
  de qué hacer si falla; el remitente se configuraría en varios sitios; y
  apagar un correo por procedimiento sería repetir la misma comprobación en
  cada llamada. Una pantalla más (PR 4, PR 9) es otra copia.

### Opción 2: un módulo de correo al que las pantallas llaman directamente

Una clase con `Mailer::application_saved( $id )` y similares, llamada desde
`Applications`, `ApplicationFiles` y `ProcedureEditor`.

- A favor: una sola salida; se lee qué se manda.
- En contra: las pantallas dependen del correo —en el orden de carga y en las
  pruebas— y quien despliega no puede engancharse a «se presentó una
  solicitud» para otra cosa sin tocar el código.

### Opción 3: las pantallas emiten acciones y el correo las escucha

`Applications` emite `prc_application_saved` y `prc_application_reviewed`,
`ApplicationFiles::deliver()` emite `prc_delivery_saved` y el alta del taller
`prc_procedure_created`. `Notifications/Mailer` las escucha desde su
`register()` y es lo único que llama a `wp_mail()`.

- A favor: el correo no sabe de pantallas ni las pantallas de correo; las
  acciones son una API que otros PR y quien despliega pueden escuchar; las
  pruebas del correo disparan la acción sin montar la pantalla.
- En contra: el camino de «guardo → llega un correo» ya no se lee en un solo
  fichero: hay que saber que la acción tiene un oyente. Una acción mal
  nombrada falla en silencio, por eso la prueba de cableado comprueba que
  `register()` engancha las cuatro.

## Decisión

Haremos la opción 3, con estas reglas:

1. **Una sola salida**: `Mailer::mail( $event, $to, $subject, $message, $ctx )`
   es lo único que llama a `wp_mail()`. Antes de salir pasa por el filtro
   `prc_mail` (recibe `to`, `subject`, `message`, `headers`; devolver `false`
   lo descarta) y mira si el procedimiento tiene el evento apagado en la meta
   `prc_notify`. `Mailer::send()` reparte un evento entre varias direcciones
   quitando duplicados sin distinguir mayúsculas.
2. **Texto plano**: `Messages::render()` compone asunto y cuerpo desde un
   contexto ya resuelto a texto —rótulos, no claves—, con el prefijo
   `[Procedimientos]` en el asunto (filtro `prc_mail_subject_prefix`). Sin
   HTML no hay plantilla que escapar, ni imágenes remotas, ni una versión que
   se vea distinta en cada cliente. El único identificador que viaja es el
   que va dentro de un enlace.
3. **El fallo no sube**: `mail()` y cada oyente envuelven su trabajo en un
   `try`/`catch` de `\Throwable`; un `wp_mail()` que devuelve `false` se queda
   en `false`. La solicitud ya está guardada cuando se emite la acción y la
   pantalla dice «presentada» igual
   (`tests/unit/test-mailer.php`, `test_a_failing_wp_mail_keeps_the_success_notice`).
4. **El remitente es configuración**: cinco opciones de los ajustes, vacías
   por defecto —`prc_mail_from_name`, `prc_mail_from`, `prc_mail_reply_to`,
   `prc_mail_copy` y `prc_mail_created_extra`—. Con `prc_mail_from` vacío no
   se añade `From` y manda el remitente de WordPress.
5. **Cada procedimiento apaga lo suyo**: la meta `prc_notify` guarda los
   eventos **apagados**; vacía significa todos encendidos, que es lo que
   tiene cualquier procedimiento anterior a esta decisión. El panel «Avisos»
   del taller pinta una casilla por evento. El correo del alta no está en esa
   lista: sale antes de que el procedimiento pueda apagar nada.

Las acciones, con su firma:

- `prc_procedure_created( int $id )`
- `prc_application_saved( int $id, bool $created, int $user_id )`
- `prc_application_reviewed( int $id, string $state, string $previous, string $previous_note )`
- `prc_delivery_saved( int $id, string $question_key, int $user_id )`

**La solicitud de un centro es del centro, no de quien la presentó**
(ADR-0016, ADR-0018): otra persona del mismo equipo directivo puede
modificarla o entregar un documento, y quien la presentó puede haber dejado
el centro. Por eso la cuenta que actúa viaja en la acción —quien emite la
conoce; el correo no la adivina con `get_current_user_id()`— y los avisos de
lo que alguien acaba de hacer van a esa cuenta. La revisión no tiene a nadie
que actúe del lado del centro: va a la cuenta autora solo mientras
`ProcedureAccess::can_view_application()` siga diciendo que sí, y si no, a
nadie.

`review_changed` sale cuando hay algo nuevo que contar
(`Mailer::review_news()`): un estado distinto del anterior, o el mismo estado
con una nota distinta si es de los que la exigen (`states_needing_note()`).
Un estado de antes vacío —una solicitud anterior a la revisión— cuenta como
«presentada», así que marcarla «presentada» no avisa de nada.

Los eventos de este PR y a quién van:

| Evento | Cuándo | A quién |
|---|---|---|
| `application_submitted` | se crea la solicitud | la cuenta que la presenta |
| `application_updated` | se guarda una ya existente | la cuenta que la guarda |
| `application_received` | las dos anteriores | `prc_contact_emails` y `prc_mail_copy` |
| `review_changed` | cambia el estado de revisión, o la nota de un estado que la exige | la cuenta autora, si todavía puede ver la solicitud; la nota, solo al pedir subsanar o al excluir |
| `delivery_received` | se entrega un documento con plazo propio | la cuenta que lo entrega y los contactos |
| `procedure_created` | el taller crea un borrador | contactos, quienes gestionan sus ámbitos (`ProcedureAccess::area_managers()`) y `prc_mail_copy` |

`ProcedureAccess::area_managers()` es la regla del acotado leída al revés:
cuentas con `prc_manage_procedures` cuyo ámbito alcanza a alguno de los del
procedimiento. Quien trabaja sobre todos los ámbitos no entra: alcanzarlos
todos no es gestionar este.

## Consecuencias

### Positivas

- Un servidor de correo caído no impide presentar ni cambia el aviso.
- Ninguna dirección ni nombre de nadie en el repositorio; cada instalación
  pone el suyo en los ajustes.
- Los PR siguientes añaden un evento con una acción y un caso en
  `Messages`, sin tocar las pantallas que ya mandan.
- Lo que dice cada correo se prueba sin mandar nada
  (`tests/unit/test-messages.php`).

### Negativas

- Un correo que no sale no deja rastro: no hay registro de envíos ni
  reintento. Si hace falta, se engancha al filtro `prc_mail` o a la acción
  `wp_mail_failed` de WordPress; aquí no se decide.
- Texto plano es texto plano: sin enlaces con nombre ni formato. Un correo
  largo, como el del alta, se lee peor que en HTML.
- `prc_application_reviewed` se emite en cada revisión, cambie algo o no;
  quien escuche la acción para otra cosa tiene que comparar lo de antes y lo
  de ahora.
- **Solo avisa lo que pasa por las pantallas del aplicativo.** Crear un
  procedimiento o cambiar una revisión desde el escritorio de WordPress o por
  la REST no emite estas acciones y no manda nada: los correos se enganchan a
  las operaciones del aplicativo, no a `save_post` ni a `updated_post_meta`,
  que se disparan también al importar, al migrar o al guardar un borrador
  automático.
- **El envío es síncrono, dentro de la petición.** Presentar espera a que
  `wp_mail()` termine con cada destinatario; un servidor de correo lento
  alarga la respuesta aunque no la tumbe. No hay cola ni WP-Cron de por
  medio.
- El alta busca a quién gestiona los ámbitos con una consulta de cuentas
  —con sus metas ya en caché, no una por cuenta—, que crece con el número de
  cuentas editoras.

### Neutras

- La pantalla de ajustes, que hasta ahora no guardaba nada, guarda la
  configuración del correo por la API de ajustes de WordPress, con la
  capacidad `prc_manage_app`.
- El orden de carga gana `Notifications/Messages.php` y
  `Notifications/Mailer.php`. **`Notifications/` es un módulo más, no una
  capa nueva** (AGENTS.md, «Ni una capa más»): una capa sería algo por lo que
  todo tiene que pasar —un servicio que las pantallas llamen, una interfaz
  que implementen—, y aquí no pasa nada por él. Son dos clases `final` con
  métodos estáticos, como `PublicFront/` o `Access/`; nadie depende de ellas
  y se pueden quitar del orden de carga sin que nada más se rompa: las
  acciones se emitirían sin nadie escuchando.
