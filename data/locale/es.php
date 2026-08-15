<?php
/**
 * Tabla de textos en espanol.
 * Las plantillas evitan depender del genero del nombre de la profesion:
 * "el trabajo de %s" en lugar de "un/una %s".
 */
declare(strict_types=1);

return [
    'site.tagline' => 'Veredictos por tarea sobre qué trabajos se lleva realmente la IA.',

    // --- veredictos ---
    'verdict.safe.label'        => 'A SALVO',
    'verdict.safe.blurb'        => 'El núcleo de este trabajo resiste estructuralmente. La IA se convierte en herramienta, no en sustituto.',
    'verdict.shrinking.label'   => 'SE REDUCE',
    'verdict.shrinking.blurb'   => 'Partes importantes se están automatizando. El puesto se estrecha y se desplaza — no desaparece.',
    'verdict.on-the-menu.label' => 'EN EL MENÚ',
    'verdict.on-the-menu.blurb' => 'Las tareas centrales se van, y lo que queda no dará de comer a la misma cantidad de gente. Se aplica un horizonte temporal.',

    // --- veredictos de tarea ---
    'task.gone.label'  => 'ya desapareció',
    'task.going.label' => 'está desapareciendo',
    'task.safe.label'  => 'resiste',

    // --- definiciones de las barreras ---
    'tag.physical-presence'  => 'Requiere manos y un cuerpo en el mundo físico.',
    'tag.legal-liability'    => 'Una persona debe responder legalmente por el resultado y firmarlo.',
    'tag.regulated'          => 'Hay una licencia, un permiso o un muro normativo de por medio.',
    'tag.trust-relationship' => 'El valor es una relación personal de confianza, no el producto.',
    'tag.human-judgment'     => 'Decisiones contextuales bajo incertidumbre que nadie quiere delegar.',
    'tag.creative-taste'     => 'Juicio estético: la IA puede generar, no puede elegir.',
    'tag.accountability'     => '"Quién responde si esto sale mal" exige una persona.',
    'tag.physical-context'   => 'Hay que estar en el sitio, en la sala, en ese momento.',
    'tag.emotional-labor'    => 'El trabajo emocional es el trabajo en sí.',

    // --- categorias ---
    'category.tech'      => 'Tecnología e Ingeniería',
    'category.finance'   => 'Finanzas y Contabilidad',
    'category.legal'     => 'Derecho',
    'category.health'    => 'Salud y Cuidados',
    'category.education' => 'Educación',
    'category.creative'  => 'Medios y Creatividad',
    'category.trades'    => 'Oficios y Trabajo de Campo',
    'category.service'   => 'Ventas y Servicios',
    'category.ops'       => 'Operaciones y Administración',
    'category.unknown'   => 'Sin clasificar',

    // --- meses ---
    'month.1'  => 'enero',   'month.2'  => 'febrero',   'month.3'  => 'marzo',
    'month.4'  => 'abril',   'month.5'  => 'mayo',      'month.6'  => 'junio',
    'month.7'  => 'julio',   'month.8'  => 'agosto',    'month.9'  => 'septiembre',
    'month.10' => 'octubre', 'month.11' => 'noviembre', 'month.12' => 'diciembre',
    'month.format' => '%s de %s',

    'list.and'    => 'y',
    'list.and.e'  => 'e',        // ante palabras que empiezan por i-/hi- (salvo hie-/hia-)

    // --- nota sobre la evidencia ---
    'evidence.draft.label' => 'Borrador de la comunidad',
    'evidence.draft.text'  => 'Todavía no hay evidencia adjunta a esta entrada. El argumento puede seguir siendo válido, pero nadie lo ha respaldado con una fuente. Adjuntar una es la contribución más útil que puedes hacer.',
    'evidence.thin.label'  => 'Evidencia escasa',
    'evidence.thin.text'   => 'Este veredicto se apoya en poca evidencia publicada. Es un argumento que defenderemos, pero merece más fuentes de las que tiene — si conoces mejores datos, abre un PR.',

    // --- parrafo GEO ---
    'geo.prefix'                  => 'En %s, %s',
    'geo.verdict.safe'            => 'la IA no está reemplazando el trabajo de %s.',
    'geo.verdict.onthemenu'       => 'las tareas centrales del trabajo de %s están pasando a ser automatizables%s.',
    'geo.verdict.onthemenu.until' => ', y se espera que el cambio se consolide hacia %s',
    'geo.verdict.shrinking'       => 'el trabajo de %s se reduce en lugar de desaparecer%s.',
    'geo.verdict.shrinking.until' => ', con un núcleo que se estrecha hasta aproximadamente %s',
    'geo.gone'                    => ' La IA ya ha absorbido lo siguiente: %s.',
    'geo.safe'                    => ' Lo que resiste: %s.',
    'geo.resistance'              => ' La razón estructural: %s.',
    'geo.fallbackDate'            => 'agosto de 2026',

    // --- FAQ ---
    'faq.replace.q'    => '¿La IA reemplazará el trabajo de %s?',
    'faq.howLong.q'    => '¿Cuánto tiempo está a salvo el trabajo de %s frente a la IA?',
    'faq.howLong.a'    => 'Nuestra estimación es hacia %s. Ese es el año en que se espera que las tareas centrales de este trabajo se hagan de forma rutinaria con máquinas en la práctica habitual — después de que llegue la capacidad, de que las empresas la adopten y de que los reguladores lo permitan. No es el año en que desaparece el nombre del puesto. Veredicto actual: %s.',
    'faq.whichTasks.q' => '¿Qué tareas del trabajo de %s hace ya la IA?',
    'faq.whichTasks.a' => '%s. Cada una se juzga por separado en lugar de reducir todo el trabajo a una sola respuesta.',
    'faq.whatSafe.q'   => '¿Qué parte del trabajo de %s está a salvo de la IA?',
    'faq.howUse.q'     => '¿Cómo debería %s usar la IA en lugar de competir con ella?',
    'faq.howUse.a'     => 'Úsala en las tareas ya marcadas como desaparecidas o en retirada, y quédate con el juicio. Hay un prompt listo para copiar escrito para este trabajo concreto en %s.',

    'share.safeUntil' => ' — a salvo hasta ~%s',
];
