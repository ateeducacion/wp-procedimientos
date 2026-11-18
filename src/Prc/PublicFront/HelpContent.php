<?php
/**
 * The text of the help screen: two guides and the frequently asked questions.
 *
 * @package Prc
 */

namespace Prc\PublicFront;

/**
 * Lo que dice la ayuda, escrito como datos (ADR-0045).
 *
 * No hay Markdown que interpretar al servirse ni un editor del que dependa:
 * cada guía es una lista de apartados con su identificador —estable, en
 * castellano sin tildes, para poder enlazar a uno desde otra pantalla—, su
 * título, un HTML corto que solo usa las etiquetas de {@see ALLOWED} y los
 * huecos de sus capturas, con la imagen vacía hasta que lleguen.
 *
 * Los enlaces internos se escriben con un marcador —`%home%`, `%mine%`,
 * `%workspace%`, `%editor%` o el de una guía— que {@see sections()} cambia
 * por la dirección real de esa instalación. Un marcador cuya página no
 * existe deja el texto del enlace y quita el enlace.
 *
 * Nada de aquí nombra a una organización, un sitio o una plataforma concreta
 * (ADR-0009): la ayuda técnica de cada instalación sale de la configuración
 * ({@see Help::support()}).
 */
final class HelpContent {

	/**
	 * Guía de quien solicita; la que se abre por defecto.
	 */
	public const GUIDE_APPLY = 'solicitar';

	/**
	 * Guía de quien da de alta y gestiona un procedimiento.
	 */
	public const GUIDE_MANAGE = 'gestionar';

	/**
	 * Las preguntas frecuentes.
	 */
	public const GUIDE_FAQ = 'preguntas';

	/**
	 * The guides, in the order of the tabs, with their tab label and their intro.
	 *
	 * @var array<string, array{label:string, intro:string}>
	 */
	public const GUIDES = array(
		self::GUIDE_APPLY  => array(
			'label' => 'Cómo presentar una solicitud',
			'intro' => 'Para la dirección de un centro educativo y para el profesorado. Lea primero «Antes de empezar»: la mayoría de los problemas empiezan ahí.',
		),
		self::GUIDE_MANAGE => array(
			'label' => 'Cómo dar de alta un procedimiento',
			'intro' => 'Para las personas que gestionan los procedimientos de un ámbito convocante.',
		),
		self::GUIDE_FAQ    => array(
			'label' => 'Preguntas frecuentes',
			'intro' => 'Las dudas que más se repiten. Lo que antes se llamaba «convocatoria» aquí se llama «procedimiento».',
		),
	);

	/**
	 * What the help text may carry: short prose, lists, one table and internal links.
	 *
	 * @var array<string, array<string, bool>>
	 */
	public const ALLOWED = array(
		'p'      => array(),
		'ul'     => array(),
		'ol'     => array(),
		'li'     => array(),
		'strong' => array(),
		'em'     => array(),
		'code'   => array(),
		'a'      => array( 'href' => true ),
		'table'  => array(),
		'thead'  => array(),
		'tbody'  => array(),
		'tr'     => array(),
		'th'     => array( 'scope' => true ),
		'td'     => array(),
	);

	/**
	 * The sections of one guide, with the links resolved and the markup filtered.
	 *
	 * @param string $guide Guide key.
	 * @return array<int, array{id:string, level:int, title:string, html:string, shots:array<int, array{caption:string, image:string}>}>
	 */
	public static function sections( string $guide ): array {
		$destinos = self::targets();
		$out      = array();
		foreach ( self::raw( $guide ) as $apartado ) {
			$html             = (string) preg_replace_callback(
				'/<a href="%([a-z]+)%(#[a-z0-9-]+)?">(.*?)<\/a>/u',
				static function ( array $m ) use ( $destinos ): string {
					$url = $destinos[ $m[1] ] ?? '';
					if ( '' === $url ) {
						return $m[3];
					}
					return '<a href="' . esc_url( $url . ( $m[2] ?? '' ) ) . '">' . $m[3] . '</a>';
				},
				$apartado['html']
			);
			$apartado['html'] = wp_kses( $html, self::ALLOWED );
			$out[]            = $apartado;
		}
		return $out;
	}

	/**
	 * The sections of one guide as written, with their link markers.
	 *
	 * @param string $guide Guide key; an unknown one is the default guide.
	 * @return array<int, array{id:string, level:int, title:string, html:string, shots:array<int, array{caption:string, image:string}>}>
	 */
	public static function raw( string $guide ): array {
		return self::SECTIONS[ $guide ] ?? self::SECTIONS[ self::GUIDE_APPLY ];
	}

	/**
	 * Where each link marker goes in this installation.
	 *
	 * @return array<string, string> Marker => URL, empty when the page does not exist.
	 */
	private static function targets(): array {
		$out = array();
		foreach ( array( 'home', 'mine', 'workspace', 'editor' ) as $seccion ) {
			$out[ $seccion ] = Shell::url( $seccion );
		}
		foreach ( array_keys( self::GUIDES ) as $guia ) {
			$out[ $guia ] = Help::url( $guia );
		}
		return $out;
	}

	/**
	 * Las guías, apartado a apartado. El nivel 2 es un apartado del índice;
	 * el 3, uno que cuelga del anterior (las pestañas del taller, las
	 * preguntas de cada grupo).
	 *
	 * @var array<string, array<int, array{id:string, level:int, title:string, html:string, shots:array<int, array{caption:string, image:string}>}>>
	 */
	private const SECTIONS = array(
		'solicitar' => array(
			array(
				'id'    => 'antes-de-empezar',
				'level' => 2,
				'title' => 'Antes de empezar: entre con su cuenta personal',
				'html'  => '<ul><li>Pulse <strong>«Acceder»</strong>, arriba a la derecha, o <strong>«Entrar y solicitar»</strong> en la ficha del procedimiento. Hasta que no entre, la ficha solo se puede leer.</li><li>Use su <strong>cuenta personal</strong>, la suya y no la cuenta genérica del centro. La solicitud de un centro la presenta su equipo directivo con su propia cuenta.</li><li>Al entrar, arriba a la derecha verá su nombre, su perfil (por ejemplo, «Equipo directivo») y, debajo, <strong>el nombre y el código de su centro</strong>. Compruébelos antes de seguir.</li></ul>'
					. '<p>Si en lugar del centro ve <strong>«Sin centro asignado»</strong>, o al abrir una solicitud le aparece «Su cuenta no tiene centro asignado» o «Su perfil no presenta solicitudes de centro», no podrá solicitar hasta que se corrija su cuenta. Eso lo hace quien administra el aplicativo: vea las <a href="%preguntas%#no-veo-el-boton-solicitar">preguntas frecuentes</a>.</p>',
				'shots' => array(
					array(
						'caption' => 'cabecera con la sesión iniciada: nombre, «Equipo directivo» y el centro con su código',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'encontrar-el-procedimiento',
				'level' => 2,
				'title' => 'Encuentre el procedimiento',
				'html'  => '<ul><li>En <strong><a href="%home%">«Inicio»</a></strong> están los procedimientos publicados, cada uno con su estado: <strong>Próximo</strong> (aún no se puede solicitar), <strong>Abierto</strong>, <strong>En subsanación</strong>, <strong>Cerrado</strong> o <strong>Resuelto</strong>.</li><li>Pulse el título para abrir su <strong>ficha</strong>: arriba, el resumen (estado, quién puede solicitar, curso, plazo y correo de contacto) y la cuenta atrás del cierre; debajo, las pestañas con la información («Objetivos», «Dirigido a», «Calendario»…) y los enlaces a la resolución y a los listados.</li></ul>'
					. '<p>Fíjese en <strong>a quién se dirige</strong>: la fila <strong>«Pueden solicitar»</strong> del resumen y la tarjeta de acceso, que dice si lo solicita la dirección del centro o cada docente:</p>'
					. '<ul><li><strong>Centros educativos</strong>: solicita el equipo directivo, una solicitud por centro.</li><li><strong>Profesorado</strong>: cada docente solicita con su propia cuenta.</li><li>Algunos son solo para <strong>centros públicos</strong> o solo para <strong>centros privados y concertados</strong>, o solo para ciertos tipos de centro, ciertas islas o una lista cerrada de centros. Si su centro no entra, en el sitio del botón verá <strong>«No puede solicitar con esta cuenta»</strong> con el motivo y el correo de contacto del procedimiento.</li></ul>',
				'shots' => array(
					array(
						'caption' => 'portada con las tarjetas de procedimientos y sus estados',
						'image'   => '',
					),
					array(
						'caption' => 'ficha de un procedimiento abierto, con el resumen, la fila «Pueden solicitar», el bloque «Lo solicita la dirección del centro» y el botón para solicitar',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'rellenar-la-solicitud',
				'level' => 2,
				'title' => 'Rellene la solicitud',
				'html'  => '<p>Pulse <strong>«Solicitar»</strong>. Se abre la pantalla de la solicitud con el plazo arriba («Plazo de solicitud: …»).</p>'
					. '<p>Si el procedimiento pide una lista de participantes o documentos con plazo propio, arriba verá <strong>los pasos</strong> —«Datos de la solicitud», «Participantes», «Documentos con plazo propio»—, cada uno con <strong>«Hecho»</strong> o <strong>«Falta»</strong>. Pulse un paso para ir a su bloque.</p>'
					. '<p>La pantalla tiene, por orden, estos bloques:</p>'
					. '<ol><li><strong>«Solicita en nombre de»</strong>: solo aparece si su cuenta tiene dos códigos (por ejemplo, el del centro y el de un CER) y el procedimiento admite los dos. Elija con cuál solicita: cada código tiene su propia solicitud.</li><li><strong>«Datos del centro educativo»</strong>: ya vienen con los de su cuenta y no se pueden cambiar.</li><li><strong>Sus datos</strong>: nombre y apellidos y, si solicita por un centro, su <strong>cargo en el centro</strong> (Dirección, Vicedirección, Jefatura de estudios, Secretaría u otro). El <strong>correo electrónico</strong> se lee de su cuenta: para cambiarlo, cambie el de su perfil.</li><li><strong>«Solicita»</strong>: si el procedimiento tiene compromisos, lea el texto y marque <strong>«El centro acepta estos compromisos»</strong> (o <strong>«Acepto estos compromisos»</strong> si solicita como docente). Sin esa casilla no se puede presentar.</li><li><strong>Campos adicionales del procedimiento</strong>: los que haya puesto quien lo gestiona. Los marcados con asterisco (*) son obligatorios. Pueden ser de texto libre, de una opción, de varias opciones, de sí o no, un número, una fecha, una tabla de cantidades por fila o un archivo. En los de archivo, pulse para elegir el fichero en su equipo: el documento solo se ve en la solicitud, no es público.</li><li><strong>«Datos de la coordinación»</strong>: si el procedimiento la pide, el nombre, los apellidos, el DNI o NIE completo <strong>con la letra</strong> y el correo de cada persona coordinadora. Si el procedimiento tiene <strong>lista de participantes</strong> con un papel de coordinación, este bloque no sale: las personas coordinadoras se dan de alta en la lista, después de presentar.</li></ol>'
					. '<p>Pulse <strong>«Presentar la solicitud»</strong>.</p>',
				'shots' => array(
					array(
						'caption' => 'pantalla de solicitud, parte superior: título, estado y plazo',
						'image'   => '',
					),
					array(
						'caption' => 'bloque de campos adicionales, con uno obligatorio y uno de archivo',
						'image'   => '',
					),
					array(
						'caption' => 'bloque «Solicita» con la casilla de los compromisos',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'comprobar-la-presentacion',
				'level' => 2,
				'title' => 'Compruebe que ha quedado presentada',
				'html'  => '<ul><li>Si no le falta nada, verá el aviso verde <strong>«Solicitud presentada el … a las …. Está completa: no tiene que hacer nada más.»</strong> Si le falta algo —casi siempre, la lista de participantes—, el aviso es amarillo y dice qué falta y hasta cuándo, con un enlace para completarlo.</li><li>En la solicitud, la tarjeta de estado dice <strong>«Presentada el … a las …»</strong> y <strong>«Completa»</strong> o <strong>«Incompleta: …»</strong>.</li><li>Pulse <strong>«Ver e imprimir el justificante»</strong>: es la solicitud en una hoja, con su número, la fecha y la hora y lo presentado. Para guardarlo, use «Imprimir» en el menú del navegador y elija «Guardar como PDF».</li><li>Recibirá un correo de acuse, <strong>«Solicitud presentada n.º …: …»</strong>, en la dirección de su cuenta. Lleva el número de la solicitud, la fecha y la hora y si está completa: guárdelo, es su justificante.</li><li>En <strong><a href="%mine%">«Solicitudes de mi centro»</a></strong> (o <strong>«Mis solicitudes»</strong> si es docente) aparece la fila con el estado de revisión <strong>«Presentada»</strong>.</li></ul>'
					. '<p>Si ve un aviso rojo («No se ha podido guardar la solicitud…», «El formulario estuvo abierto demasiado tiempo…»), <strong>la solicitud no se ha presentado</strong>: vuelva a enviarla.</p>',
				'shots' => array(
					array(
						'caption' => 'aviso verde «Solicitud presentada»',
						'image'   => '',
					),
					array(
						'caption' => '«Solicitudes de mi centro» con la fila de la solicitud en estado «Presentada»',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'personas-participantes',
				'level' => 2,
				'title' => 'No olvide a las personas participantes',
				'html'  => '<p>Cuando el procedimiento pide una <strong>lista de participantes</strong> (quienes participan, coordinación de cada eje o grupo…), esa lista <strong>se rellena después de presentar la solicitud</strong>, en la misma pantalla, en el bloque <strong>«Participantes»</strong>, que va antes de los documentos con plazo propio. Hasta entonces verá: «Cuando presente la solicitud podrá dar de alta aquí a las personas que participan».</p>'
					. '<ul><li>Pulse <strong>«Dar de alta a una persona»</strong>, escriba sus datos, elija su <strong>papel</strong> y, si el papel lo pide, su <strong>grupo</strong> (por ejemplo, el eje que coordina).</li><li>Si la lista pide un mínimo y no se llega, la solicitud queda marcada como <strong>incompleta</strong> («Incompleta: faltan … personas»). No se rechaza, pero quien revisa lo verá, y en <a href="%mine%">«Solicitudes de mi centro»</a> la columna «Participantes» lo marca.</li><li>Para <strong>sustituir</strong> a alguien, use <strong>«Sustituir o corregir»</strong> en su fila; no dé de alta a otra persona nueva. Para quitarla, <strong>«Dar de baja a …»</strong>.</li><li>Lo primero del bloque, en negrita, es hasta cuándo: <strong>«Puede dar de alta, cambiar o sustituir personas hasta el …, incluido.»</strong> Puede ser distinto del plazo de solicitud.</li></ul>',
				'shots' => array(
					array(
						'caption' => 'bloque «Participantes» vacío, con «Dar de alta a una persona»',
						'image'   => '',
					),
					array(
						'caption' => 'lista con dos personas, papel y grupo, y los botones «Sustituir o corregir» y «Dar de baja»',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'modificar-la-solicitud',
				'level' => 2,
				'title' => 'Modifique la solicitud',
				'html'  => '<p>Mientras el plazo esté abierto, entre en <strong><a href="%mine%">«Solicitudes de mi centro»</a> → «Ver la solicitud»</strong>, cambie lo que necesite y pulse <strong>«Guardar los cambios»</strong>. Recibirá un correo de que la solicitud se ha modificado. Cualquier persona del equipo directivo del mismo centro ve y edita la misma solicitud.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'subsanar',
				'level' => 2,
				'title' => 'Subsane si se lo piden',
				'html'  => '<p>Si quien revisa le pide subsanar, la solicitud aparece como <strong>«A subsanar»</strong> con una nota que explica qué falta, y en «Solicitudes de mi centro» un aviso amarillo: «1 solicitud tiene la subsanación abierta hasta el …». Pulse <strong>«Ir a la solicitud»</strong>, corrija y guarde <strong>dentro del plazo de subsanación</strong>. Fuera de ese plazo ya no se puede editar.</p>',
				'shots' => array(
					array(
						'caption' => 'aviso de subsanación en «Solicitudes de mi centro» y la nota de revisión en la fila',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'entregas-con-plazo-propio',
				'level' => 2,
				'title' => 'Entregue los documentos con plazo propio',
				'html'  => '<p>Algunos documentos (una memoria, un proyecto, un anexo firmado) se entregan <strong>después</strong>, en su propio plazo. Aparecen en la solicitud con su apertura y su cierre. Elija el fichero y pulse <strong>«Entregar documento»</strong>. Si ya había entregado uno, el nuevo lo sustituye; la solicitud y su revisión se conservan.</p>',
				'shots' => array(
					array(
						'caption' => 'campo de tipo Archivo con plazo propio y el botón «Entregar documento»',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'retirar-la-solicitud',
				'level' => 2,
				'title' => 'Retire la solicitud si ya no quiere participar',
				'html'  => '<p>Mientras el plazo siga abierto, pulse <strong>«Retirar la solicitud»</strong>, al final de la solicitud, y confirme. Deja de contar y recibirá un correo de acuse. Mientras haya plazo, puede presentar otra nueva.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'despues-de-presentar',
				'level' => 2,
				'title' => 'Qué verá después',
				'html'  => '<ul><li>En <strong><a href="%mine%">«Solicitudes de mi centro»</a></strong>, la columna <strong>«Revisión»</strong>: Presentada, A subsanar, Admitida o Excluida, con la nota de quien revisa si la hay. Cada cambio le llega también por correo. Si el procedimiento pide lista de participantes, la columna <strong>«Participantes»</strong> dice cuántas hay, si falta alguna («Completar») y hasta cuándo se cambian.</li><li>En la ficha del procedimiento, los enlaces a los <strong>listados provisional y definitivo</strong> y a la resolución cuando se publiquen. Con el listado definitivo el procedimiento pasa a <strong>«Resuelto»</strong>.</li><li>Si le conceden una <strong>prórroga</strong>, recibirá un correo y verá en la solicitud hasta qué día puede editarla.</li></ul>',
				'shots' => array(),
			),
		),
		'gestionar' => array(
			array(
				'id'    => 'que-necesita',
				'level' => 2,
				'title' => 'Qué necesita',
				'html'  => '<ul><li>Una cuenta con perfil de <strong>gestión de procedimientos</strong> y un <strong>ámbito</strong> asignado. Lo ve arriba a la derecha: «Gestión de procedimientos» y, debajo, su ámbito.</li><li><strong>No necesita pedir permiso para cada procedimiento</strong>: con ese perfil y ese ámbito puede crear los que quiera. Si ve «Su perfil no puede crear procedimientos» o «No tiene ningún ámbito asignado», pídalo a quien administra el aplicativo.</li><li>Solo verá y editará los procedimientos de su ámbito y de los ámbitos que cuelgan de él.</li></ul>',
				'shots' => array(
					array(
						'caption' => 'cabecera con el perfil «Gestión de procedimientos» y el ámbito',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'su-lista',
				'level' => 2,
				'title' => 'Su lista: «Gestión de procedimientos»',
				'html'  => '<p>Menú <strong>«Administrar» → <a href="%workspace%">«Gestionar procedimientos»</a></strong>. Elija el curso arriba y verá una tabla con cada procedimiento: su <strong>título</strong> (abre el taller, y debajo lleva su número y su ámbito), su <strong>estado</strong>, el <strong>plazo</strong> de solicitud, a quién va <strong>dirigido</strong>, cuántas <strong>solicitudes</strong> tiene (abre la lista) y, al final, tres iconos: el <strong>lápiz</strong> abre el taller, la <strong>lista</strong> sus solicitudes y el <strong>ojo</strong> la ficha —en un borrador, la vista previa—. Duplicar y borrar se hacen dentro del taller, desde el menú <strong>«⋮»</strong> de arriba a la derecha. Busque por título, número o ámbito en <strong>«Buscar por título, número o ámbito»</strong>. Si duda de lo que significa un estado, abra <strong>«¿Qué significa cada estado?»</strong>; el aviso de que <strong>las fechas no cuadran</strong> indica que hay que repasar los plazos.</p>',
				'shots' => array(
					array(
						'caption' => '«Gestión de procedimientos» con la tabla y la leyenda de estados',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'crear-o-duplicar',
				'level' => 2,
				'title' => 'Cree uno nuevo o duplique uno anterior',
				'html'  => '<p><strong>Nuevo</strong>: pulse <strong>«Nuevo procedimiento»</strong>, junto al título de su lista, o <a href="%editor%">«Administrar» → «Crear procedimiento»</a>. El alta pide solo lo mínimo:</p>'
					. '<ul><li><strong>Título del procedimiento</strong>, de 120 caracteres como mucho. El curso no se escribe en el título.</li><li><strong>Quién solicita</strong>: «Centros educativos» o «Profesorado».</li><li><strong>Dirigido a</strong>: «Centros públicos», «Centros privados y concertados» o los dos. Al crear no viene ninguna marcada: hay que elegir. Un centro de otra titularidad no podrá solicitar, y la ficha le dirá por qué. Lo que elija se ve en la ficha, en la fila «Pueden solicitar», y en «Gestión de procedimientos».</li><li><strong>Modalidad para centros CER</strong>, <strong>Ámbito convocante</strong>, <strong>Correo de contacto</strong> (sale en la ficha), <strong>Otros correos de contacto</strong>, si quiere, y <strong>Color de cabecera</strong>, que ya viene elegido.</li></ul>'
					. '<p>Pulse <strong>«Crear el procedimiento»</strong>. Nace en <strong>borrador</strong> (nadie lo ve) y se abre su taller. Los correos de contacto del procedimiento y quienes gestionan su ámbito reciben un aviso con lo que falta por completar.</p>'
					. '<p><strong>Duplicar</strong>: abra el procedimiento que quiere copiar, pulse el menú <strong>«⋮»</strong> de arriba a la derecha del taller y elija <strong>«Duplicar»</strong>. Crea un borrador con los mismos datos, <strong>los mismos campos adicionales</strong> y la misma configuración, pero <strong>sin plazos, sin curso, sin los enlaces de la resolución y sin solicitudes</strong>. Es la forma de reutilizar el procedimiento del curso pasado.</p>',
				'shots' => array(
					array(
						'caption' => 'pantalla «Crear un procedimiento»',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'el-taller',
				'level' => 2,
				'title' => 'El taller, pestaña a pestaña',
				'html'  => '<p>Arriba están el título, el estado (Borrador, Abierto…) y si está publicado. A la derecha, el <strong>ojo</strong> abre la ficha —en un borrador, la vista previa— y el menú <strong>«⋮»</strong> («Más acciones para este procedimiento») reúne <strong>«Ver la ficha»</strong> —en un borrador, <strong>«Vista previa»</strong>—, <strong>«Duplicar»</strong> y, en un borrador sin ninguna solicitud, <strong>«Borrar»</strong>. Debajo, las pestañas, en tres grupos y en el orden en que se trabaja: <strong>Preparar</strong> (Datos, Campos adicionales, Participantes, Enlaces y Publicación), <strong>Gestionar</strong> (Solicitudes y Entregas) y <strong>Avanzado</strong> (Código y, solo para quien administra el aplicativo, Acceso de consulta).</p>',
				'shots' => array(
					array(
						'caption' => 'cabecera del taller con las pestañas',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'taller-datos',
				'level' => 3,
				'title' => 'Datos',
				'html'  => '<ul><li><strong>Ajustes generales</strong>: título, curso, quién solicita y <strong>Descripción</strong>, que es lo que se lee en la ficha; los apartados «Objetivos», «Dirigido a» o «Calendario» salen de sus encabezados.</li><li><strong>Promociona</strong>: el ámbito convocante. Puede marcar varios; todos editan.</li><li><strong>Más información</strong>: hasta tres correos de contacto.</li><li><strong>Plazos</strong>: inicio y fin del plazo de solicitud, e inicio y fin de la subsanación. <strong>El estado sale de estas fechas</strong>: no hay que «pasarlo a abierto» a mano. Sin fecha de inicio no se puede publicar: se quedaría como «Próximo» y nadie podría solicitar.</li><li><strong>La solicitud</strong>: si pide personas coordinadoras (ninguna, una o varias; para varios papeles o ejes, deje «No se piden» y use la pestaña «Participantes») y el texto de los <strong>Compromisos del centro</strong>, que se aceptan con una casilla obligatoria. En blanco, no se pide nada.</li><li><strong>Qué centros pueden solicitar</strong>: tipos de centro, islas o una <strong>lista cerrada</strong> de códigos. Sin nada marcado, puede solicitar cualquier centro.</li></ul>'
					. '<p>Pulse <strong>«Guardar los datos»</strong>.</p>',
				'shots' => array(
					array(
						'caption' => 'pestaña «Datos», bloque de plazos',
						'image'   => '',
					),
					array(
						'caption' => 'pestaña «Datos», bloques de la solicitud y de qué centros pueden solicitar',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'taller-preguntas',
				'level' => 3,
				'title' => 'Campos adicionales',
				'html'  => '<p>Hasta 40 campos adicionales. Al final hay siempre un <strong>«Campo nuevo»</strong> vacío y, en <strong>«Añadir otro campo»</strong>, dos más: rellene los que necesite y pulse <strong>«Guardar los campos»</strong>. Los que deje sin título no se crean. Cada campo enseña solo las opciones de su tipo.</p>'
					. '<p>Cada campo lleva <strong>Título</strong>, <strong>Tipo</strong>, <strong>Indicaciones</strong> (una línea de ayuda) y si es <strong>obligatorio</strong>. Los tipos:</p>'
					. '<table><thead><tr><th scope="col">Tipo</th><th scope="col">Para qué</th><th scope="col">Qué más se rellena</th></tr></thead><tbody><tr><td>Respuesta libre</td><td>Un texto</td><td>Nada más</td></tr><tr><td>Una opción</td><td>Elegir una de una lista</td><td>«Opciones, una por línea»</td></tr><tr><td>Varias opciones</td><td>Marcar varias</td><td>Las opciones; una escrita «Texto &#124; 3» suma 3 puntos</td></tr><tr><td>Sí o no</td><td>Una casilla</td><td>Nada más</td></tr><tr><td>Número</td><td>Una cifra entera</td><td>Mínimo y máximo, si los hay</td></tr><tr><td>Fecha</td><td>Un día</td><td>Nada más</td></tr><tr><td>Cantidades por fila</td><td>Una tabla, por ejemplo el alumnado por nivel</td><td>«Filas, una por línea», hasta 30</td></tr><tr><td>Archivo</td><td>Un documento</td><td>Si quiere, «Entrega con plazo propio» y sus fechas</td></tr></tbody></table>'
					. '<ul><li><strong>«Subir»</strong> y <strong>«Bajar»</strong> cambian el orden. Guarde antes lo que haya escrito.</li><li>Para quitar un campo, marque <strong>«Quitar este campo»</strong> y guarde; lo ya contestado no se pierde.</li><li><strong>Con solicitudes presentadas</strong> el tipo ya no se puede cambiar, y bajo el título se dice cuántas lo han contestado. El título sí cambia, pero si le cambia el sentido, cambia lo que significan las respuestas ya dadas: si el campo es otro, cree uno nuevo.</li></ul>',
				'shots' => array(
					array(
						'caption' => 'pestaña «Campos adicionales» con un campo de «Una opción» y el «Campo nuevo»',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'taller-enlaces',
				'level' => 3,
				'title' => 'Enlaces',
				'html'  => '<p><strong>Resolución</strong>, <strong>Listado provisional</strong> y <strong>Listado definitivo</strong>: pegue la <strong>dirección completa</strong> de donde ya están publicados, que empieza por https://. Con el listado definitivo, el procedimiento pasa a <strong>«Resuelto»</strong> y los centros lo ven en su solicitud. Pulse <strong>«Guardar los enlaces»</strong>.</p>',
				'shots' => array(
					array(
						'caption' => 'pestaña «Enlaces»',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'taller-personas',
				'level' => 3,
				'title' => 'Participantes',
				'html'  => '<p>Active <strong>«Pedir a cada centro la lista de participantes»</strong> si necesita saber quién participa. Defina:</p>'
					. '<ul><li>Los <strong>papeles</strong>, uno por fila (por ejemplo, «Coordinación general» y «Coordinación de eje»). Marque <strong>«Exige indicar el grupo»</strong> en el que lo necesite: el centro no podrá guardar a esa persona sin su grupo.</li><li>Los <strong>grupos</strong>, uno por línea (por ejemplo, «Eje 1», «Eje 2»).</li><li>El <strong>mínimo</strong> y el <strong>máximo</strong> de participantes, y si hay <strong>«Una sola persona por papel y grupo»</strong>, que una lista nueva trae marcada.</li><li>Si <strong>«Una persona puede tener varios papeles»</strong>. Sin marcar, la misma persona (el mismo DNI o NIE) no entra dos veces en la lista, ni con otro papel.</li><li>Las fechas en que se abre y se cierra la lista, que pueden ir más allá del plazo de solicitud.</li></ul>'
					. '<p>Con la lista encendida, un papel cuyo nombre diga coordinación (por ejemplo, «Coordinación») y un mínimo de al menos una persona, el formulario de la solicitud <strong>ya no pide las personas coordinadoras</strong> de «Datos»: se dan de alta en la lista, con su papel. Si falta alguna de las dos cosas, el formulario las sigue pidiendo. Las que ya estuvieran guardadas en solicitudes anteriores se conservan. Para no pedirla dos veces, en «Datos» deje «No se piden»: el taller no deja configurar la coordinación en los dos sitios.</p>',
				'shots' => array(
					array(
						'caption' => 'pestaña «Participantes» con papeles y grupos',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'taller-correos',
				'level' => 3,
				'title' => 'Correos',
				'html'  => '<p>Los correos salen solos y no se apagan desde el taller. Al presentar, modificar o retirar una solicitud, el acuse llega a la cuenta con la que se hace —la del centro, en los procedimientos para centros; la de la persona, en los de profesorado— y el aviso, a los correos de contacto del procedimiento. También salen, como siempre, los cambios de revisión, las prórrogas, las entregas y los cambios en la lista de participantes. Si alguien necesita copia de estos correos en otra dirección, lo configura administración.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'taller-consulta',
				'level' => 3,
				'title' => 'Acceso de consulta',
				'html'  => '<p>Solo lo ve y lo guarda quien administra el aplicativo. Permite que otras personas vean <strong>en solo lectura</strong> las solicitudes de los centros de su zona. Marque qué columnas ven y si pueden descargar documentos o exportar. Nunca ven el DNI o NIE, la nota de revisión ni el historial.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'taller-codigo',
				'level' => 3,
				'title' => 'Código',
				'html'  => '<p>Estilos propios (CSS) para la ficha pública de este procedimiento. Los guiones (JavaScript) solo los añade quien administra el aplicativo.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'publicar',
				'level' => 2,
				'title' => 'Publique',
				'html'  => '<p>Pestaña <strong>«Publicación»</strong>. Mientras está en borrador, <strong>«Antes de publicar»</strong> marca cada punto con ✓, ✗ o !: el curso, el plazo de solicitud, los campos adicionales, las fechas de las entregas con plazo propio y la resolución. <strong>Sin curso no se puede publicar; sin fecha de inicio del plazo tampoco</strong>, porque se quedaría en «Próximo» y nadie podría solicitar nunca; si quiere anunciarlo antes, ponga fechas futuras y se publicará como «Próximo». La resolución solo avisa: puede enlazarla después.</p>'
					. '<p>Pulse <strong>«Publicar el procedimiento»</strong>: pasa a verse en la portada. Los centros podrán solicitar cuando llegue la fecha de inicio del plazo. Para retirarlo temporalmente, <strong>«Devolver a borrador»</strong>, que tiene el aspecto de las acciones delicadas y dice al lado lo que pasa: deja de verse en la portada.</p>'
					. '<p>Antes de publicar, repase también a quién se dirige, los campos obligatorios y los compromisos. Abra la ficha con el ojo para verla como la verá un centro.</p>',
				'shots' => array(
					array(
						'caption' => 'pestaña «Publicación» con la línea de estados y la etapa actual',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'revisar-solicitudes',
				'level' => 2,
				'title' => 'Revise las solicitudes',
				'html'  => '<p>Pestaña <strong>«Solicitudes»</strong>, o la columna «Solicitudes» de su lista.</p>'
					. '<ul><li>Filtros por estado (Presentada, A subsanar, Admitida, Excluida), por nombre o código del centro, por isla o zona y <strong>«Solo las incompletas»</strong>.</li><li><strong>«Revisar»</strong> en cada fila: elija el estado y escriba la <strong>nota para el centro</strong>, obligatoria al pedir subsanar y al excluir. El centro la lee en su solicitud y recibe un correo.</li><li><strong>«Revisar varias a la vez»</strong>: marque las filas y aplique a todas el mismo estado y la misma nota.</li><li>En la revisión de cada solicitud están el <strong>historial</strong>, la <strong>nota interna</strong> (solo la ve quien revisa), la <strong>prórroga</strong> («Guardar la prórroga» y «Quitar la prórroga») y <strong>«Retirar esta solicitud»</strong>, si se presentó por error, con su motivo. Las retiradas se ven en <strong>«Retiradas»</strong> y se pueden restaurar.</li><li><strong>«Presentar en nombre de un centro»</strong>: para un centro que no pudo entrar a tiempo. Se crea su solicitud vacía y el centro la completa hasta la fecha que usted ponga. Avise al centro: no recibe correo.</li></ul>',
				'shots' => array(
					array(
						'caption' => 'pestaña «Solicitudes» con filtros y la fila «Revisar»',
						'image'   => '',
					),
					array(
						'caption' => 'revisión de una solicitud con estado, nota y prórroga',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'exportar',
				'level' => 2,
				'title' => 'Exporte',
				'html'  => '<p>Botón <strong>«Exportar…»</strong>: elija <strong>todas</strong> o <strong>las del filtro</strong>, las <strong>columnas</strong> y si quiere <strong>una fila por solicitud</strong> o <strong>una por persona coordinadora</strong>. También puede sacar el <strong>fichero de alta de usuarios de Moodle</strong>, con el formato que admite su importación. Se descarga una hoja de cálculo (CSV).</p>',
				'shots' => array(
					array(
						'caption' => 'diálogo «Exportar las solicitudes» con las columnas',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'entregas-y-prorrogas',
				'level' => 2,
				'title' => 'Entregas y prórrogas',
				'html'  => '<ul><li>Pestaña <strong>«Entregas»</strong>: los plazos de las entregas con plazo propio. Puede <strong>ampliar el cierre</strong> sin reabrir la inscripción, con <strong>«Guardar los nuevos cierres»</strong>.</li><li>Prórroga <strong>a un solo centro</strong>: desde su solicitud, como se explica en «Revise las solicitudes». No afecta a los demás.</li></ul>',
				'shots' => array(),
			),
			array(
				'id'    => 'cerrar-y-archivar',
				'level' => 2,
				'title' => 'Cierre y archive',
				'html'  => '<ul><li>El plazo se cierra solo en la fecha de fin. Si necesita cerrarlo antes, pulse <strong>«Cerrar solicitudes ahora»</strong> en la pestaña «Publicación»; <strong>«Volver al calendario automático»</strong> lo deshace.</li><li>Cuando ya no quede nada que tocar (resuelto, listados enlazados, solicitudes revisadas), pulse <strong>«Marcar como histórico»</strong>, al final de la pestaña. Solo aparece con el procedimiento cerrado o resuelto: uno vivo no se puede marcar. Queda en solo lectura y se puede consultar y exportar. <strong>Para volver a abrirlo hay que pedírselo a quien administra el aplicativo.</strong></li></ul>',
				'shots' => array(
					array(
						'caption' => 'bloque «Dar el procedimiento por terminado»',
						'image'   => '',
					),
				),
			),
			array(
				'id'    => 'borrar',
				'level' => 2,
				'title' => 'Borre un procedimiento que no debió crearse',
				'html'  => '<p>Solo se borra un procedimiento en <strong>borrador</strong> que <strong>no tenga ninguna solicitud</strong>, tampoco retirada. Abra su taller, pulse el menú <strong>«⋮»</strong> de arriba a la derecha y elija <strong>«Borrar»</strong>; si no aparece, es que no se puede borrar. Para confirmarlo, escriba <strong>BORRAR</strong> y pulse <strong>«Enviar a la papelera»</strong>. Vuelve a su lista con el aviso de que se ha enviado a la papelera; <strong>quien administra el aplicativo puede recuperarlo</strong>.</p>'
					. '<p>Uno publicado no se borra: devuélvalo antes a borrador desde «Publicación». Uno que ya tiene solicitudes, o que terminó, tampoco: márquelo como histórico cuando esté cerrado o resuelto.</p>',
				'shots' => array(),
			),
		),
		'preguntas' => array(
			array(
				'id'    => 'para-quien-solicita',
				'level' => 2,
				'title' => 'Para quien solicita',
				'html'  => '',
				'shots' => array(),
			),
			array(
				'id'    => 'no-veo-el-boton-solicitar',
				'level' => 3,
				'title' => 'No veo el botón «Solicitar». ¿Por qué?',
				'html'  => '<p>Con la sesión iniciada y el plazo abierto, la ficha ya le dice el motivo en el sitio del botón, bajo <strong>«No puede solicitar con esta cuenta»</strong>, con la cuenta con la que ha entrado y el enlace <strong>«Salir y entrar con otra cuenta»</strong>. Si no lo ve, compruebe, por este orden:</p><ol><li><strong>¿Ha pulsado «Acceder» o «Entrar y solicitar»?</strong> Sin iniciar sesión, la ficha solo se lee.</li><li><strong>¿Ha entrado con su cuenta personal?</strong> No con la cuenta genérica del centro. La solicitud de un centro la presenta el equipo directivo con su propia cuenta.</li><li><strong>¿Arriba a la derecha aparece su centro con su código?</strong> Si pone «Sin centro asignado», o la pantalla dice «Su perfil no presenta solicitudes de centro», su cuenta no tiene el perfil de equipo directivo o el centro. Pida que lo corrijan por el canal de ayuda técnica. Es frecuente con las direcciones recién nombradas.</li><li><strong>¿El procedimiento es para centros como el suyo?</strong> Mire «Pueden solicitar» en el resumen de la ficha: puede ser solo para públicos, solo para privados y concertados, solo para profesorado o solo para ciertos centros.</li><li><strong>¿Está abierto el plazo?</strong> Mire el estado y las fechas del resumen.</li></ol>',
				'shots' => array(),
			),
			array(
				'id'    => 'mi-perfil-no-tiene-centro',
				'level' => 3,
				'title' => 'Soy de un centro concertado y no puedo entrar, o mi perfil no tiene centro',
				'html'  => '<p>Su cuenta necesita tener asignado el código de su centro. No se puede cambiar desde la solicitud: pídalo por el canal de ayuda técnica indicando el nombre y el código del centro.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'centro-de-otra-titularidad',
				'level' => 3,
				'title' => 'Mi centro es público y el procedimiento es solo para concertados, o al revés',
				'html'  => '<p>No podrá solicitar: el aplicativo lo impide para que no se presente una solicitud que luego se excluiría. Si cree que su centro debería entrar, escriba al correo de contacto del procedimiento.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'como-se-que-esta-presentada',
				'level' => 3,
				'title' => '¿Cómo sé que mi solicitud ha quedado presentada?',
				'html'  => '<p>Tiene tres pruebas: la línea «Presentada el … a las …» en la propia solicitud, con su justificante («Ver e imprimir el justificante»); el correo «Solicitud presentada n.º …: …», que es su justificante; y la fila en «Solicitudes de mi centro» con el estado «Presentada». Si tiene las tres, está presentada. Si además pone «Incompleta», está presentada pero le falta algo: lo dice al lado.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'inscripcion-incompleta',
				'level' => 3,
				'title' => 'Me dicen que la inscripción no está completa, pero lo rellené todo',
				'html'  => '<p>Casi siempre falta la <strong>lista de participantes</strong>, que se rellena después de presentar, en «Participantes», o un <strong>documento con plazo propio</strong> obligatorio. Abra la solicitud y mire si pone «Incompleta: …»; los pasos de arriba dicen cuál «Falta». La solicitud sigue presentada: complétela dentro del plazo de la lista.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'no-he-dado-de-alta-a-nadie',
				'level' => 3,
				'title' => 'He presentado la solicitud y no he dado de alta a nadie',
				'html'  => '<p>Es normal: la lista se rellena <strong>después</strong> de presentar. Vuelva a la solicitud («Solicitudes de mi centro» → «Ver la solicitud») y use «Dar de alta a una persona».</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'cambiar-personas-coordinadoras',
				'level' => 3,
				'title' => '¿Dónde añado o cambio a las personas después de presentar? ¿Hasta cuándo?',
				'html'  => '<p>En la misma solicitud, en el bloque «Participantes». Lo primero del bloque dice hasta cuándo («Puede dar de alta, cambiar o sustituir personas hasta el …, incluido»), y puede durar más que el plazo de solicitud; lo dice también el correo de acuse. Si el procedimiento tiene lista, las personas coordinadoras están en ella y en ningún otro sitio.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'coordino-un-eje',
				'level' => 3,
				'title' => 'Coordino un eje: ¿dónde digo cuál?',
				'html'  => '<p>Al dar de alta a la persona, elija su papel y, en el grupo, el eje. La etiqueta del grupo dice qué papeles lo exigen («Grupo (obligatorio para: …)»): con uno de ellos, no se puede guardar sin él.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'persona-dada-de-alta-dos-veces',
				'level' => 3,
				'title' => 'He dado de alta dos veces a la misma persona',
				'html'  => '<p>Dé de baja la fila que sobra con «Dar de baja». El aplicativo ya no deja dar de alta a la misma persona dos veces, salvo que el procedimiento admita varios papeles por persona: si lo intenta, le dice con qué papel está y le lleva a su fila para cambiarla.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'sustituir-a-una-persona',
				'level' => 3,
				'title' => 'Tengo que sustituir a una persona',
				'html'  => '<p>Pulse «Sustituir o corregir» en su fila y cambie sus datos. No dé de alta a otra persona nueva: quedarían las dos.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'cambiar-la-solicitud',
				'level' => 3,
				'title' => '¿Puedo cambiar la solicitud después de presentarla?',
				'html'  => '<p>Sí, mientras el plazo esté abierto. Después, solo si le piden subsanar, dentro del plazo de subsanación, o si le conceden una prórroga.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'me-equivoque-de-procedimiento',
				'level' => 3,
				'title' => 'Me equivoqué de procedimiento. ¿Puedo deshacerlo?',
				'html'  => '<p>Sí: pulse «Retirar la solicitud», al final de la solicitud, mientras el plazo siga abierto.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'para-quien-gestiona',
				'level' => 2,
				'title' => 'Para quien gestiona',
				'html'  => '',
				'shots' => array(),
			),
			array(
				'id'    => 'permiso-para-crear',
				'level' => 3,
				'title' => '¿Necesito permiso para crear un procedimiento?',
				'html'  => '<p>No para cada uno. Basta con tener el perfil de gestión y un ámbito asignado. Si no lo tiene, pídalo a quien administra el aplicativo.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'nadie-lo-ve',
				'level' => 3,
				'title' => 'He creado el procedimiento y nadie lo ve',
				'html'  => '<p>Nace en borrador. Pulse «Publicar el procedimiento» en la pestaña «Publicación».</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'sale-como-proximo',
				'level' => 3,
				'title' => 'Está publicado, pero sale como «Próximo» y nadie puede solicitar',
				'html'  => '<p>Todavía no ha llegado la fecha de inicio del plazo de solicitud, o, si se publicó antes de que se exigiera, no la tiene. El estado sale de las fechas: no hay que cambiarlo a mano. Revise los plazos en la pestaña «Datos». Hoy ya no se puede publicar sin esa fecha.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'varias-casillas',
				'level' => 3,
				'title' => '¿Cómo creo varias casillas o varios campos?',
				'html'  => '<p>Rellene el «Campo nuevo» y, si necesita más, los dos de «Añadir otro campo», y pulse «Guardar los campos»; aparecerán otros tres vacíos. Para una lista de casillas que marcar, use un solo campo de tipo «Varias opciones», con una opción por línea.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'mismas-preguntas',
				'level' => 3,
				'title' => 'Quiero los mismos campos que el curso pasado',
				'html'  => '<p>Abra el procedimiento del curso pasado, pulse el menú «⋮» de arriba a la derecha y elija «Duplicar». Después, ponga el curso nuevo y las fechas.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'campo-numerico',
				'level' => 3,
				'title' => 'Necesito un campo numérico',
				'html'  => '<p>Use el tipo «Número», con mínimo y máximo si los hay. Para varias cantidades, por ejemplo el alumnado por nivel, use «Cantidades por fila».</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'documento-firmado',
				'level' => 3,
				'title' => 'Necesito que el centro suba un documento de compromiso firmado',
				'html'  => '<p>Cree un campo de tipo «Archivo», obligatorio. Si se entrega más tarde, marque «Entrega con plazo propio» y ponga sus fechas. Si basta con que el centro acepte un texto, use «Compromisos del centro» en «Datos».</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'cambie-un-rotulo',
				'level' => 3,
				'title' => 'He cambiado el título de un campo con solicitudes ya presentadas',
				'html'  => '<p>Las respuestas ya dadas se quedan como estaban, ahora bajo el título nuevo. Si el campo significa otra cosa, vuelva al título anterior y cree un campo nuevo.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'enlaces-cortados',
				'level' => 3,
				'title' => 'Los enlaces de la resolución salen cortados o no abren',
				'html'  => '<p>Aquí no se sube el PDF: en «Enlaces» se pega la dirección completa donde está publicado. Copie la dirección entera desde la barra del navegador, empezando por https://, y compruébela abriendo la ficha pública con el ojo.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'exportar-mas-columnas',
				'level' => 3,
				'title' => 'Quiero exportar con más columnas',
				'html'  => '<p>En «Exportar…» elija las columnas. Además de las habituales hay otras, como los datos del catálogo del centro o la nota interna, que solo salen si se marcan.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'centro-fuera-de-plazo',
				'level' => 3,
				'title' => 'Un centro no pudo presentar a tiempo',
				'html'  => '<p>En «Solicitudes», use «Presentar en nombre de un centro» si no tiene solicitud, o dele una prórroga desde su solicitud si ya la tiene. Avise al centro.</p>',
				'shots' => array(),
			),
			array(
				'id'    => 'historico-por-error',
				'level' => 3,
				'title' => 'He marcado el procedimiento como histórico por error',
				'html'  => '<p>Pida a quien administra el aplicativo que lo vuelva a abrir.</p>',
				'shots' => array(),
			),
		),
	);
}
