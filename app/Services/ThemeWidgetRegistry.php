<?php

namespace App\Services;

class ThemeWidgetRegistry
{
    /**
     * All available visual builder widget definitions keyed by widget type.
     *
     * @return array<string, array{
     *     type: string,
     *     name: string,
     *     description: string,
     *     category: string,
     *     icon: string,
     *     defaults: array<string, mixed>,
     *     fields: array<int, array<string, mixed>>
     * }>
     */
    public function all(): array
    {
        return [
            'hero_banner' => [
                'type' => 'hero_banner',
                'name' => 'Hero Banner Principal',
                'description' => 'Portada de alto impacto con titular, subtítulo, botones CTA, imagen de fondo y métricas clave.',
                'category' => 'Portada y Destacados',
                'icon' => 'o-sparkles',
                'defaults' => [
                    'badge' => 'Catálogo {{ current_year }}',
                    'title' => 'Bienvenidos a {{ company.name }}',
                    'subtitle' => 'Soluciones comerciales, atención personalizada y disponibilidad inmediata para tu negocio.',
                    'background_image' => '',
                    'background_color' => '#1b365d',
                    'accent_color' => '#a6282e',
                    'text_color' => '#ffffff',
                    'overlay_opacity' => '75',
                    'align' => 'left',
                    'buttons' => [
                        ['label' => 'Explorar Catálogo', 'url' => '/', 'style' => 'primary'],
                        ['label' => 'Nuestras Sucursales', 'url' => '/page/locales', 'style' => 'outline'],
                    ],
                    'stats' => [
                        ['value' => '+15', 'label' => 'Años de trayectoria'],
                        ['value' => '24 hs', 'label' => 'Despacho ágil'],
                        ['value' => '100%', 'label' => 'Asesoramiento directo'],
                    ],
                ],
                'fields' => [
                    ['key' => 'badge', 'label' => 'Etiqueta superior (Badge)', 'type' => 'text', 'supports_vars' => true],
                    ['key' => 'title', 'label' => 'Título principal', 'type' => 'text', 'supports_vars' => true],
                    ['key' => 'subtitle', 'label' => 'Subtítulo descriptivo', 'type' => 'textarea', 'supports_vars' => true],
                    ['key' => 'background_image', 'label' => 'Imagen de fondo', 'type' => 'image', 'supports_vars' => true],
                    ['key' => 'background_color', 'label' => 'Color base de fondo', 'type' => 'color'],
                    ['key' => 'accent_color', 'label' => 'Color de acento', 'type' => 'color'],
                    ['key' => 'text_color', 'label' => 'Color de texto', 'type' => 'color'],
                    ['key' => 'overlay_opacity', 'label' => 'Opacidad del velo oscuro (%)', 'type' => 'select', 'options' => [
                        ['id' => '0', 'name' => 'Sin velo (0%)'],
                        ['id' => '45', 'name' => 'Suave (45%)'],
                        ['id' => '75', 'name' => 'Equilibrado (75%)'],
                        ['id' => '90', 'name' => 'Intenso (90%)'],
                    ]],
                    ['key' => 'align', 'label' => 'Alineación del contenido', 'type' => 'select', 'options' => [
                        ['id' => 'left', 'name' => 'Izquierda'],
                        ['id' => 'center', 'name' => 'Centrado'],
                    ]],
                    [
                        'key' => 'buttons',
                        'label' => 'Botones de acción',
                        'type' => 'repeater',
                        'item_fields' => [
                            ['key' => 'label', 'label' => 'Texto del botón', 'type' => 'text', 'supports_vars' => true],
                            ['key' => 'url', 'label' => 'URL de destino', 'type' => 'text'],
                            ['key' => 'style', 'label' => 'Estilo visual', 'type' => 'select', 'options' => [
                                ['id' => 'primary', 'name' => 'Principal (Relleno)'],
                                ['id' => 'outline', 'name' => 'Secundario (Contorno)'],
                            ]],
                        ],
                    ],
                    [
                        'key' => 'stats',
                        'label' => 'Métricas destacadas',
                        'type' => 'repeater',
                        'item_fields' => [
                            ['key' => 'value', 'label' => 'Valor / Cifra', 'type' => 'text', 'supports_vars' => true],
                            ['key' => 'label', 'label' => 'Descripción breve', 'type' => 'text', 'supports_vars' => true],
                        ],
                    ],
                ],
            ],
            'logo_strip' => [
                'type' => 'logo_strip',
                'name' => 'Franja de Logos / Medios de Pago',
                'description' => 'Tira horizontal de marcas representadas, certificaciones o medios de pago habilitados.',
                'category' => 'Portada y Destacados',
                'icon' => 'o-squares-2x2',
                'defaults' => [
                    'title' => 'Marcas y Medios de Pago',
                    'subtitle' => '',
                    'background_color' => '#ffffff',
                    'accent_color' => '#1b365d',
                    'item_height' => 'md',
                    'logos' => [
                        ['name' => 'Marca Asociada', 'image' => '', 'url' => ''],
                    ],
                ],
                'fields' => [
                    ['key' => 'title', 'label' => 'Título de la franja', 'type' => 'text', 'supports_vars' => true],
                    ['key' => 'subtitle', 'label' => 'Subtítulo opcional', 'type' => 'text', 'supports_vars' => true],
                    ['key' => 'background_color', 'label' => 'Color de fondo', 'type' => 'color'],
                    ['key' => 'accent_color', 'label' => 'Color de acento / texto', 'type' => 'color'],
                    ['key' => 'item_height', 'label' => 'Tamaño de logos', 'type' => 'select', 'options' => [
                        ['id' => 'sm', 'name' => 'Compacto'],
                        ['id' => 'md', 'name' => 'Mediano'],
                        ['id' => 'lg', 'name' => 'Grande'],
                    ]],
                    [
                        'key' => 'logos',
                        'label' => 'Logos',
                        'type' => 'repeater',
                        'item_fields' => [
                            ['key' => 'name', 'label' => 'Nombre alternativo (ALT)', 'type' => 'text'],
                            ['key' => 'image', 'label' => 'Imagen del logo', 'type' => 'image', 'supports_vars' => true],
                            ['key' => 'url', 'label' => 'Enlace opcional', 'type' => 'text'],
                        ],
                    ],
                ],
            ],
            'product_slider' => [
                'type' => 'product_slider',
                'name' => 'Carrusel Dinámico de Productos',
                'description' => 'Inserta el slider vivo de productos destacados o novedades conectado a tu catálogo en tiempo real.',
                'category' => 'Catálogo',
                'icon' => 'o-shopping-bag',
                'defaults' => [
                    'title' => 'Productos Destacados',
                    'slider_type' => 'featured',
                    'background_color' => '#ffffff',
                    'accent_color' => '#1b365d',
                ],
                'fields' => [
                    ['key' => 'title', 'label' => 'Título del carrusel', 'type' => 'text', 'supports_vars' => true],
                    ['key' => 'slider_type', 'label' => 'Colección a mostrar', 'type' => 'select', 'options' => [
                        ['id' => 'featured', 'name' => 'Productos Destacados'],
                        ['id' => 'new', 'name' => 'Últimos Ingresos / Novedades'],
                    ]],
                    ['key' => 'background_color', 'label' => 'Color de fondo del contenedor', 'type' => 'color'],
                    ['key' => 'accent_color', 'label' => 'Color de acento', 'type' => 'color'],
                ],
            ],
            'rich_text' => [
                'type' => 'rich_text',
                'name' => 'Bloque de Contenido Enriquecido',
                'description' => 'Editor WYSIWYG libre para textos institucionales, listas, enlaces y variables dinámicas.',
                'category' => 'Contenido',
                'icon' => 'o-document-text',
                'defaults' => [
                    'badge' => 'Información Institucional',
                    'title' => 'Conocé más sobre {{ company.name }}',
                    'html' => '<p>Escribí aquí el contenido de tu sección utilizando formato enriquecido e incorporando datos vivos como <strong>{{ company.name }}</strong> o <strong>{{ company.phone }}</strong>.</p>',
                    'max_width' => 'medium',
                    'background_color' => '#ffffff',
                    'text_color' => '#1e293b',
                    'accent_color' => '#a6282e',
                ],
                'fields' => [
                    ['key' => 'badge', 'label' => 'Etiqueta superior opcional', 'type' => 'text', 'supports_vars' => true],
                    ['key' => 'title', 'label' => 'Título de la sección', 'type' => 'text', 'supports_vars' => true],
                    ['key' => 'html', 'label' => 'Contenido enriquecido (HTML)', 'type' => 'richtext', 'supports_vars' => true],
                    ['key' => 'max_width', 'label' => 'Ancho de lectura', 'type' => 'select', 'options' => [
                        ['id' => 'narrow', 'name' => 'Estrecho (Artículo)'],
                        ['id' => 'medium', 'name' => 'Mediano (Estándar)'],
                        ['id' => 'wide', 'name' => 'Amplio (1280px)'],
                        ['id' => 'full', 'name' => 'Ancho completo'],
                    ]],
                    ['key' => 'background_color', 'label' => 'Color de fondo', 'type' => 'color'],
                    ['key' => 'text_color', 'label' => 'Color del texto', 'type' => 'color'],
                    ['key' => 'accent_color', 'label' => 'Color de acento', 'type' => 'color'],
                ],
            ],
            'story_timeline' => [
                'type' => 'story_timeline',
                'name' => 'Línea de Tiempo / Historia',
                'description' => 'Recorrido cronológico por hitos de la empresa con año, título, relato e imagen opcional.',
                'category' => 'Institucional',
                'icon' => 'o-clock',
                'defaults' => [
                    'badge' => 'Nuestra Historia',
                    'title' => 'Trayectoria de {{ company.name }}',
                    'subtitle' => 'Cada etapa consolidó nuestro compromiso con la calidad y el servicio.',
                    'accent_color' => '#a6282e',
                    'secondary_color' => '#1b365d',
                    'background_color' => '#f8fafc',
                    'timeline' => [
                        [
                            'year' => '2010',
                            'title' => 'Los primeros pasos',
                            'description' => 'Iniciamos operaciones enfocándonos en atención directa y stock permanente.',
                            'image' => '',
                        ],
                        [
                            'year' => '{{ current_year }}',
                            'title' => 'Catálogo B2B digitalizado',
                            'description' => 'Operamos pedidos en línea para clientes mayoristas y minoristas de toda la región.',
                            'image' => '',
                        ],
                    ],
                ],
                'fields' => [
                    ['key' => 'badge', 'label' => 'Etiqueta superior', 'type' => 'text', 'supports_vars' => true],
                    ['key' => 'title', 'label' => 'Título', 'type' => 'text', 'supports_vars' => true],
                    ['key' => 'subtitle', 'label' => 'Subtítulo', 'type' => 'textarea', 'supports_vars' => true],
                    ['key' => 'accent_color', 'label' => 'Color de acento principal', 'type' => 'color'],
                    ['key' => 'secondary_color', 'label' => 'Color secundario', 'type' => 'color'],
                    ['key' => 'background_color', 'label' => 'Color de fondo', 'type' => 'color'],
                    [
                        'key' => 'timeline',
                        'label' => 'Hitos cronológicos',
                        'type' => 'repeater',
                        'item_fields' => [
                            ['key' => 'year', 'label' => 'Año o etapa', 'type' => 'text', 'supports_vars' => true],
                            ['key' => 'title', 'label' => 'Título del hito', 'type' => 'text', 'supports_vars' => true],
                            ['key' => 'description', 'label' => 'Descripción', 'type' => 'textarea', 'supports_vars' => true],
                            ['key' => 'image', 'label' => 'Imagen opcional', 'type' => 'image', 'supports_vars' => true],
                        ],
                    ],
                ],
            ],
            'branch_cards' => [
                'type' => 'branch_cards',
                'name' => 'Tarjetas de Sucursales / Locales',
                'description' => 'Grilla de sucursales con fotografía, dirección, horarios, teléfonos, WhatsApp y enlace a Google Maps.',
                'category' => 'Institucional',
                'icon' => 'o-building-storefront',
                'defaults' => [
                    'badge' => 'Puntos de Atención',
                    'title' => 'Nuestras Sucursales',
                    'subtitle' => 'Visitá nuestros locales comerciales o contactate por teléfono y WhatsApp.',
                    'columns' => '2',
                    'accent_color' => '#a6282e',
                    'secondary_color' => '#1b365d',
                    'background_color' => '#f8fafc',
                    'branches' => [
                        [
                            'name' => 'Casa Central',
                            'badge' => 'Sede Principal',
                            'address' => '{{ company.address }}',
                            'city' => 'Atención comercial y depósito',
                            'phone' => '{{ company.phone }}',
                            'whatsapp' => '',
                            'email' => '{{ company.email }}',
                            'image' => '',
                            'map_url' => '',
                            'hours_text' => 'Lunes a Viernes de 8:00 a 12:30 y 15:30 a 19:30 hs',
                        ],
                    ],
                ],
                'fields' => [
                    ['key' => 'badge', 'label' => 'Etiqueta superior', 'type' => 'text', 'supports_vars' => true],
                    ['key' => 'title', 'label' => 'Título', 'type' => 'text', 'supports_vars' => true],
                    ['key' => 'subtitle', 'label' => 'Subtítulo', 'type' => 'textarea', 'supports_vars' => true],
                    ['key' => 'columns', 'label' => 'Columnas en escritorio', 'type' => 'select', 'options' => [
                        ['id' => '1', 'name' => '1 columna'],
                        ['id' => '2', 'name' => '2 columnas'],
                        ['id' => '3', 'name' => '3 columnas'],
                    ]],
                    ['key' => 'accent_color', 'label' => 'Color de acento', 'type' => 'color'],
                    ['key' => 'secondary_color', 'label' => 'Color secundario', 'type' => 'color'],
                    ['key' => 'background_color', 'label' => 'Color de fondo', 'type' => 'color'],
                    [
                        'key' => 'branches',
                        'label' => 'Sucursales',
                        'type' => 'repeater',
                        'item_fields' => [
                            ['key' => 'name', 'label' => 'Nombre de la sucursal', 'type' => 'text', 'supports_vars' => true],
                            ['key' => 'badge', 'label' => 'Distintivo (Ej: Casa Central)', 'type' => 'text', 'supports_vars' => true],
                            ['key' => 'address', 'label' => 'Dirección', 'type' => 'text', 'supports_vars' => true],
                            ['key' => 'city', 'label' => 'Localidad / Referencia', 'type' => 'text', 'supports_vars' => true],
                            ['key' => 'phone', 'label' => 'Teléfono', 'type' => 'text', 'supports_vars' => true],
                            ['key' => 'whatsapp', 'label' => 'WhatsApp (solo dígitos con código país)', 'type' => 'text'],
                            ['key' => 'email', 'label' => 'Email', 'type' => 'text', 'supports_vars' => true],
                            ['key' => 'hours_text', 'label' => 'Horarios de atención', 'type' => 'text', 'supports_vars' => true],
                            ['key' => 'image', 'label' => 'Foto del frente / salón', 'type' => 'image', 'supports_vars' => true],
                            ['key' => 'map_url', 'label' => 'URL de Google Maps', 'type' => 'text'],
                        ],
                    ],
                ],
            ],
            'feature_grid' => [
                'type' => 'feature_grid',
                'name' => 'Grilla de Pilares / Beneficios',
                'description' => 'Tarjetas con ícono, título y descripción para presentar valores, servicios o diferenciales.',
                'category' => 'Contenido',
                'icon' => 'o-rectangle-group',
                'defaults' => [
                    'badge' => 'Diferenciales',
                    'title' => '¿Por qué operar con {{ company.name }}?',
                    'subtitle' => 'Ventajas diseñadas para acompañar el abastecimiento de tu negocio.',
                    'columns' => '3',
                    'accent_color' => '#a6282e',
                    'secondary_color' => '#1b365d',
                    'background_color' => '#ffffff',
                    'values' => [
                        ['icon' => 'shield', 'title' => 'Garantía Oficial', 'description' => 'Trabajamos con marcas líderes y respaldo técnico comprobado.'],
                        ['icon' => 'truck', 'title' => 'Envíos Programados', 'description' => 'Logística ágil con flete bonificado a partir de ${{ company.free_freight }}.'],
                        ['icon' => 'users', 'title' => 'Asesoramiento B2B', 'description' => 'Atención comercial dedicada para optimizar cada reposición de stock.'],
                    ],
                ],
                'fields' => [
                    ['key' => 'badge', 'label' => 'Etiqueta superior', 'type' => 'text', 'supports_vars' => true],
                    ['key' => 'title', 'label' => 'Título', 'type' => 'text', 'supports_vars' => true],
                    ['key' => 'subtitle', 'label' => 'Subtítulo', 'type' => 'textarea', 'supports_vars' => true],
                    ['key' => 'columns', 'label' => 'Columnas en escritorio', 'type' => 'select', 'options' => [
                        ['id' => '2', 'name' => '2 columnas'],
                        ['id' => '3', 'name' => '3 columnas'],
                        ['id' => '4', 'name' => '4 columnas'],
                    ]],
                    ['key' => 'accent_color', 'label' => 'Color de acento', 'type' => 'color'],
                    ['key' => 'secondary_color', 'label' => 'Color secundario', 'type' => 'color'],
                    ['key' => 'background_color', 'label' => 'Color de fondo', 'type' => 'color'],
                    [
                        'key' => 'values',
                        'label' => 'Tarjetas de beneficio / pilar',
                        'type' => 'repeater',
                        'item_fields' => [
                            ['key' => 'icon', 'label' => 'Ícono', 'type' => 'select', 'options' => [
                                ['id' => 'shield', 'name' => 'Escudo / Garantía'],
                                ['id' => 'truck', 'name' => 'Logística / Envío'],
                                ['id' => 'users', 'name' => 'Equipo / Atención'],
                                ['id' => 'sparkles', 'name' => 'Calidad / Destacado'],
                                ['id' => 'clock', 'name' => 'Rapidez / Horarios'],
                                ['id' => 'bolt', 'name' => 'Energía / Potencia'],
                                ['id' => 'wrench', 'name' => 'Soporte Técnico'],
                                ['id' => 'check', 'name' => 'Verificado / Stock'],
                            ]],
                            ['key' => 'title', 'label' => 'Título', 'type' => 'text', 'supports_vars' => true],
                            ['key' => 'description', 'label' => 'Descripción', 'type' => 'textarea', 'supports_vars' => true],
                        ],
                    ],
                ],
            ],
            'image_banner' => [
                'type' => 'image_banner',
                'name' => 'Banner Visual con Enlace',
                'description' => 'Pieza gráfica ancha con imagen destacada, texto superpuesto y botón de llamada a la acción.',
                'category' => 'Portada y Destacados',
                'icon' => 'o-photo',
                'defaults' => [
                    'title' => 'Novedades de Temporada',
                    'caption' => 'Consultá condiciones especiales y disponibilidad en el catálogo completo.',
                    'image' => '',
                    'link_url' => '/',
                    'link_label' => 'Ver productos',
                    'height' => 'md',
                    'rounded' => true,
                    'overlay_color' => '#0f172a',
                ],
                'fields' => [
                    ['key' => 'title', 'label' => 'Título superpuesto', 'type' => 'text', 'supports_vars' => true],
                    ['key' => 'caption', 'label' => 'Descripción breve', 'type' => 'textarea', 'supports_vars' => true],
                    ['key' => 'image', 'label' => 'Imagen del banner', 'type' => 'image', 'supports_vars' => true],
                    ['key' => 'link_url', 'label' => 'URL de destino', 'type' => 'text'],
                    ['key' => 'link_label', 'label' => 'Texto del enlace / botón', 'type' => 'text', 'supports_vars' => true],
                    ['key' => 'height', 'label' => 'Altura del banner', 'type' => 'select', 'options' => [
                        ['id' => 'sm', 'name' => 'Compacto (190px)'],
                        ['id' => 'md', 'name' => 'Mediano (280px)'],
                        ['id' => 'lg', 'name' => 'Alto (380px)'],
                    ]],
                    ['key' => 'rounded', 'label' => 'Bordes redondeados y contenedor centrado', 'type' => 'toggle'],
                    ['key' => 'overlay_color', 'label' => 'Color base / velo', 'type' => 'color'],
                ],
            ],
            'cta_assistance' => [
                'type' => 'cta_assistance',
                'name' => 'Llamado a la Acción / Asesoramiento',
                'description' => 'Bloque oscuro o de marca con botones de contacto y chips de servicios comerciales.',
                'category' => 'Conversión',
                'icon' => 'o-chat-bubble-left-right',
                'defaults' => [
                    'badge' => 'Atención Personalizada',
                    'title' => '¿Necesitás asesoramiento para armar tu pedido?',
                    'subtitle' => 'El equipo de {{ company.name }} te ayuda a elegir productos y condiciones según tu lista {{ user.price_list }}.',
                    'background_color' => '#1b365d',
                    'accent_color' => '#a6282e',
                    'text_color' => '#ffffff',
                    'buttons' => [
                        ['label' => 'Volver al Catálogo', 'url' => '/', 'style' => 'primary'],
                        ['label' => 'Llamar ahora', 'url' => 'tel:{{ company.phone }}', 'style' => 'outline'],
                    ],
                    'services' => [
                        ['label' => 'Asesoramiento técnico'],
                        ['label' => 'Listas mayoristas'],
                        ['label' => 'Envíos coordinados'],
                    ],
                ],
                'fields' => [
                    ['key' => 'badge', 'label' => 'Etiqueta superior', 'type' => 'text', 'supports_vars' => true],
                    ['key' => 'title', 'label' => 'Título', 'type' => 'text', 'supports_vars' => true],
                    ['key' => 'subtitle', 'label' => 'Subtítulo', 'type' => 'textarea', 'supports_vars' => true],
                    ['key' => 'background_color', 'label' => 'Color de fondo', 'type' => 'color'],
                    ['key' => 'accent_color', 'label' => 'Color de acento', 'type' => 'color'],
                    ['key' => 'text_color', 'label' => 'Color de texto', 'type' => 'color'],
                    [
                        'key' => 'buttons',
                        'label' => 'Botones CTA',
                        'type' => 'repeater',
                        'item_fields' => [
                            ['key' => 'label', 'label' => 'Texto del botón', 'type' => 'text', 'supports_vars' => true],
                            ['key' => 'url', 'label' => 'URL / tel: / mailto:', 'type' => 'text', 'supports_vars' => true],
                            ['key' => 'style', 'label' => 'Estilo', 'type' => 'select', 'options' => [
                                ['id' => 'primary', 'name' => 'Acento relleno'],
                                ['id' => 'outline', 'name' => 'Borde blanco / transparente'],
                            ]],
                        ],
                    ],
                    [
                        'key' => 'services',
                        'label' => 'Chips de servicios',
                        'type' => 'repeater',
                        'item_fields' => [
                            ['key' => 'label', 'label' => 'Etiqueta de servicio', 'type' => 'text', 'supports_vars' => true],
                        ],
                    ],
                ],
            ],
            'contact_map' => [
                'type' => 'contact_map',
                'name' => 'Contacto, Horarios y Mapa',
                'description' => 'Sección de dos columnas con ficha de contacto de la empresa, horarios y mapa embebido.',
                'category' => 'Institucional',
                'icon' => 'o-map-pin',
                'defaults' => [
                    'badge' => 'Contacto Directo',
                    'title' => 'Comunicate con {{ company.name }}',
                    'subtitle' => 'Encontranos en nuestro local o escribinos por cualquiera de nuestros canales.',
                    'address' => '{{ company.address }}',
                    'phone' => '{{ company.phone }}',
                    'email' => '{{ company.email }}',
                    'map_embed_url' => '',
                    'map_link_url' => '',
                    'accent_color' => '#a6282e',
                    'secondary_color' => '#1b365d',
                    'background_color' => '#ffffff',
                    'hours' => [
                        ['day' => 'Lunes a Viernes', 'schedule' => '08:00 - 12:30 y 15:30 - 19:30'],
                        ['day' => 'Sábados', 'schedule' => '08:30 - 12:30'],
                    ],
                ],
                'fields' => [
                    ['key' => 'badge', 'label' => 'Etiqueta superior', 'type' => 'text', 'supports_vars' => true],
                    ['key' => 'title', 'label' => 'Título', 'type' => 'text', 'supports_vars' => true],
                    ['key' => 'subtitle', 'label' => 'Subtítulo', 'type' => 'textarea', 'supports_vars' => true],
                    ['key' => 'address', 'label' => 'Dirección', 'type' => 'text', 'supports_vars' => true],
                    ['key' => 'phone', 'label' => 'Teléfono', 'type' => 'text', 'supports_vars' => true],
                    ['key' => 'email', 'label' => 'Email', 'type' => 'text', 'supports_vars' => true],
                    ['key' => 'map_embed_url', 'label' => 'URL embed de Google Maps (https://...)', 'type' => 'text'],
                    ['key' => 'map_link_url', 'label' => 'Enlace botón "Cómo llegar"', 'type' => 'text'],
                    ['key' => 'accent_color', 'label' => 'Color de acento', 'type' => 'color'],
                    ['key' => 'secondary_color', 'label' => 'Color secundario', 'type' => 'color'],
                    ['key' => 'background_color', 'label' => 'Color de fondo', 'type' => 'color'],
                    [
                        'key' => 'hours',
                        'label' => 'Horarios de atención',
                        'type' => 'repeater',
                        'item_fields' => [
                            ['key' => 'day', 'label' => 'Días', 'type' => 'text'],
                            ['key' => 'schedule', 'label' => 'Franja horaria', 'type' => 'text'],
                        ],
                    ],
                ],
            ],
            'topbar_announcement' => [
                'type' => 'topbar_announcement',
                'name' => 'Barra Superior de Anuncio',
                'description' => 'Franja informativa ideal para la zona Topbar: alertas de flete bonificado, avisos o promociones.',
                'category' => 'Zonas de Layout',
                'icon' => 'o-megaphone',
                'defaults' => [
                    'badge' => 'IMPORTANTE',
                    'message' => 'Flete bonificado en {{ company.name }} para pedidos superiores a ${{ company.free_freight }}.',
                    'cta_label' => 'Ver Catálogo',
                    'cta_url' => '/',
                    'background_color' => '#a6282e',
                    'text_color' => '#ffffff',
                    'dismissible' => true,
                ],
                'fields' => [
                    ['key' => 'badge', 'label' => 'Etiqueta destacada', 'type' => 'text', 'supports_vars' => true],
                    ['key' => 'message', 'label' => 'Mensaje principal', 'type' => 'text', 'supports_vars' => true],
                    ['key' => 'cta_label', 'label' => 'Texto botón opcional', 'type' => 'text', 'supports_vars' => true],
                    ['key' => 'cta_url', 'label' => 'URL del botón', 'type' => 'text'],
                    ['key' => 'background_color', 'label' => 'Color de fondo', 'type' => 'color'],
                    ['key' => 'text_color', 'label' => 'Color de texto', 'type' => 'color'],
                    ['key' => 'dismissible', 'label' => 'Permitir cerrar al usuario', 'type' => 'toggle'],
                ],
            ],
            'multi_columns' => [
                'type' => 'multi_columns',
                'name' => 'Sección de Columnas',
                'description' => 'Grilla flexible de 2, 3, 4 o columnas asimétricas (1/3 + 2/3) con imagen, título, texto y botón en cada columna.',
                'category' => 'Estructura y Columnas',
                'icon' => 'o-view-columns',
                'defaults' => [
                    'badge' => '',
                    'title' => 'Líneas y Servicios Destacados',
                    'subtitle' => 'Organizá bloques visuales en múltiples columnas adaptables a cualquier pantalla.',
                    'layout' => '3',
                    'vertical_align' => 'stretch',
                    'style' => 'card',
                    'text_align' => 'left',
                    'background_color' => '#ffffff',
                    'accent_color' => '#a6282e',
                    'text_color' => '#1e293b',
                    'columns_items' => [
                        [
                            'image' => '',
                            'badge' => 'Columna 1',
                            'title' => 'Stock Permanente',
                            'content' => 'Amplia variedad de referencias con disponibilidad inmediata para tu comercio.',
                            'button_label' => 'Ver más',
                            'button_url' => '/',
                        ],
                        [
                            'image' => '',
                            'badge' => 'Columna 2',
                            'title' => 'Asesoramiento B2B',
                            'content' => 'Acompañamiento personalizado para elegir productos y optimizar cada compra.',
                            'button_label' => '',
                            'button_url' => '',
                        ],
                        [
                            'image' => '',
                            'badge' => 'Columna 3',
                            'title' => 'Entregas Coordinadas',
                            'content' => 'Despachos ágiles y seguimiento de envíos en todo el país.',
                            'button_label' => '',
                            'button_url' => '',
                        ],
                    ],
                ],
                'fields' => [
                    ['key' => 'badge', 'label' => 'Etiqueta superior opcional', 'type' => 'text', 'supports_vars' => true],
                    ['key' => 'title', 'label' => 'Título de la sección', 'type' => 'text', 'supports_vars' => true],
                    ['key' => 'subtitle', 'label' => 'Subtítulo descriptivo', 'type' => 'textarea', 'supports_vars' => true],
                    ['key' => 'layout', 'label' => 'Distribución de columnas', 'type' => 'select', 'options' => [
                        ['id' => '2', 'name' => '2 columnas iguales (50% / 50%)'],
                        ['id' => '3', 'name' => '3 columnas iguales (33% c/u)'],
                        ['id' => '4', 'name' => '4 columnas iguales (25% c/u)'],
                        ['id' => '1-2', 'name' => 'Asimétrica (33% / 67%)'],
                        ['id' => '2-1', 'name' => 'Asimétrica (67% / 33%)'],
                    ]],
                    ['key' => 'vertical_align', 'label' => 'Alineación vertical', 'type' => 'select', 'options' => [
                        ['id' => 'stretch', 'name' => 'Misma altura (Estirado)'],
                        ['id' => 'top', 'name' => 'Alineado arriba'],
                        ['id' => 'center', 'name' => 'Centrado vertical'],
                    ]],
                    ['key' => 'style', 'label' => 'Estilo visual de columnas', 'type' => 'select', 'options' => [
                        ['id' => 'card', 'name' => 'Tarjetas con borde y sombra'],
                        ['id' => 'soft', 'name' => 'Tarjetas suaves'],
                        ['id' => 'plain', 'name' => 'Limpio sin recuadro'],
                    ]],
                    ['key' => 'text_align', 'label' => 'Alineación del texto', 'type' => 'select', 'options' => [
                        ['id' => 'left', 'name' => 'Izquierda'],
                        ['id' => 'center', 'name' => 'Centrado'],
                    ]],
                    ['key' => 'background_color', 'label' => 'Color de fondo de sección', 'type' => 'color'],
                    ['key' => 'accent_color', 'label' => 'Color de acento', 'type' => 'color'],
                    ['key' => 'text_color', 'label' => 'Color de texto', 'type' => 'color'],
                    [
                        'key' => 'columns_items',
                        'label' => 'Columnas',
                        'type' => 'repeater',
                        'item_fields' => [
                            ['key' => 'image', 'label' => 'Imagen superior opcional', 'type' => 'image', 'supports_vars' => true],
                            ['key' => 'badge', 'label' => 'Etiqueta / Badge', 'type' => 'text', 'supports_vars' => true],
                            ['key' => 'title', 'label' => 'Título', 'type' => 'text', 'supports_vars' => true],
                            ['key' => 'content', 'label' => 'Contenido', 'type' => 'textarea', 'supports_vars' => true],
                            ['key' => 'button_label', 'label' => 'Texto botón opcional', 'type' => 'text', 'supports_vars' => true],
                            ['key' => 'button_url', 'label' => 'URL del botón', 'type' => 'text'],
                        ],
                    ],
                ],
            ],
            'split_image_text' => [
                'type' => 'split_image_text',
                'name' => 'Imagen + Texto (2 Columnas)',
                'description' => 'Diseño en dos columnas combinando fotografía destacada a izquierda o derecha con relato, viñetas y botón.',
                'category' => 'Estructura y Columnas',
                'icon' => 'o-arrows-right-left',
                'defaults' => [
                    'image_position' => 'left',
                    'column_ratio' => '50-50',
                    'image' => '',
                    'image_caption' => '',
                    'badge' => 'Destacado',
                    'title' => 'Calidad y respaldo de {{ company.name }}',
                    'content' => 'Presentá un producto estrella, tu local comercial o una propuesta de valor combinando imagen protagonista y texto claro.',
                    'bullets' => [
                        ['text' => 'Atención comercial personalizada para cada cliente'],
                        ['text' => 'Envíos coordinados a todo el país'],
                        ['text' => 'Catálogo online siempre actualizado'],
                    ],
                    'button_label' => 'Explorar Catálogo',
                    'button_url' => '/',
                    'rounded' => true,
                    'background_color' => '#ffffff',
                    'accent_color' => '#a6282e',
                    'text_color' => '#1e293b',
                ],
                'fields' => [
                    ['key' => 'image_position', 'label' => 'Posición de la imagen', 'type' => 'select', 'options' => [
                        ['id' => 'left', 'name' => 'Imagen a la izquierda'],
                        ['id' => 'right', 'name' => 'Imagen a la derecha'],
                    ]],
                    ['key' => 'column_ratio', 'label' => 'Proporción de columnas', 'type' => 'select', 'options' => [
                        ['id' => '50-50', 'name' => 'Equilibrada (50% / 50%)'],
                        ['id' => '40-60', 'name' => 'Imagen compacta (40% / 60%)'],
                        ['id' => '60-40', 'name' => 'Imagen protagonista (60% / 40%)'],
                    ]],
                    ['key' => 'image', 'label' => 'Imagen destacada', 'type' => 'image', 'supports_vars' => true],
                    ['key' => 'image_caption', 'label' => 'Epígrafe bajo la imagen (opcional)', 'type' => 'text', 'supports_vars' => true],
                    ['key' => 'badge', 'label' => 'Etiqueta superior', 'type' => 'text', 'supports_vars' => true],
                    ['key' => 'title', 'label' => 'Título', 'type' => 'text', 'supports_vars' => true],
                    ['key' => 'content', 'label' => 'Texto descriptivo', 'type' => 'textarea', 'supports_vars' => true],
                    [
                        'key' => 'bullets',
                        'label' => 'Puntos destacados (viñetas)',
                        'type' => 'repeater',
                        'item_fields' => [
                            ['key' => 'text', 'label' => 'Texto de la viñeta', 'type' => 'text', 'supports_vars' => true],
                        ],
                    ],
                    ['key' => 'button_label', 'label' => 'Texto botón opcional', 'type' => 'text', 'supports_vars' => true],
                    ['key' => 'button_url', 'label' => 'URL del botón', 'type' => 'text'],
                    ['key' => 'rounded', 'label' => 'Bordes redondeados en la imagen', 'type' => 'toggle'],
                    ['key' => 'background_color', 'label' => 'Color de fondo', 'type' => 'color'],
                    ['key' => 'accent_color', 'label' => 'Color de acento', 'type' => 'color'],
                    ['key' => 'text_color', 'label' => 'Color de texto', 'type' => 'color'],
                ],
            ],
            'single_image' => [
                'type' => 'single_image',
                'name' => 'Imagen Individual / Figura',
                'description' => 'Inserta una fotografía, flyer o gráfico con control de ancho, alineación, proporción y enlace opcional.',
                'category' => 'Imágenes y Multimedia',
                'icon' => 'o-photo',
                'defaults' => [
                    'image' => '',
                    'alt' => 'Imagen destacada de {{ company.name }}',
                    'title' => '',
                    'caption' => '',
                    'link_url' => '',
                    'max_width' => 'lg',
                    'align' => 'center',
                    'aspect_ratio' => 'auto',
                    'rounded' => true,
                    'shadow' => true,
                    'background_color' => '#ffffff',
                ],
                'fields' => [
                    ['key' => 'image', 'label' => 'Imagen', 'type' => 'image', 'supports_vars' => true],
                    ['key' => 'alt', 'label' => 'Texto alternativo (ALT)', 'type' => 'text', 'supports_vars' => true],
                    ['key' => 'title', 'label' => 'Título opcional', 'type' => 'text', 'supports_vars' => true],
                    ['key' => 'caption', 'label' => 'Epígrafe o pie de imagen', 'type' => 'textarea', 'supports_vars' => true],
                    ['key' => 'link_url', 'label' => 'URL de enlace al hacer clic (opcional)', 'type' => 'text'],
                    ['key' => 'max_width', 'label' => 'Ancho máximo', 'type' => 'select', 'options' => [
                        ['id' => 'sm', 'name' => 'Pequeño (max-w-md)'],
                        ['id' => 'md', 'name' => 'Mediano (max-w-2xl)'],
                        ['id' => 'lg', 'name' => 'Amplio (max-w-4xl)'],
                        ['id' => 'xl', 'name' => 'Contenedor tienda (max-w-7xl)'],
                        ['id' => 'full', 'name' => 'Ancho completo'],
                    ]],
                    ['key' => 'align', 'label' => 'Alineación horizontal', 'type' => 'select', 'options' => [
                        ['id' => 'left', 'name' => 'Izquierda'],
                        ['id' => 'center', 'name' => 'Centrado'],
                        ['id' => 'right', 'name' => 'Derecha'],
                    ]],
                    ['key' => 'aspect_ratio', 'label' => 'Proporción de recorte', 'type' => 'select', 'options' => [
                        ['id' => 'auto', 'name' => 'Proporción original'],
                        ['id' => '16-9', 'name' => 'Panorámica (16:9)'],
                        ['id' => '4-3', 'name' => 'Clásica (4:3)'],
                        ['id' => '1-1', 'name' => 'Cuadrada (1:1)'],
                        ['id' => '21-9', 'name' => 'Cinemática (21:9)'],
                    ]],
                    ['key' => 'rounded', 'label' => 'Bordes redondeados', 'type' => 'toggle'],
                    ['key' => 'shadow', 'label' => 'Sombra suave', 'type' => 'toggle'],
                    ['key' => 'background_color', 'label' => 'Color de fondo de sección', 'type' => 'color'],
                ],
            ],
            'image_gallery' => [
                'type' => 'image_gallery',
                'name' => 'Galería / Mosaico de Imágenes',
                'description' => 'Grilla adaptable de 2, 3 o 4 columnas para mostrar fotos de locales, exhibidores, eventos o lanzamientos.',
                'category' => 'Imágenes y Multimedia',
                'icon' => 'o-squares-plus',
                'defaults' => [
                    'badge' => '',
                    'title' => 'Galería de Imágenes',
                    'subtitle' => '',
                    'columns' => '3',
                    'aspect_ratio' => '4-3',
                    'gap' => 'md',
                    'rounded' => true,
                    'background_color' => '#ffffff',
                    'accent_color' => '#a6282e',
                    'images' => [
                        ['image' => '', 'title' => 'Imagen 1', 'caption' => 'Descripción breve', 'url' => ''],
                        ['image' => '', 'title' => 'Imagen 2', 'caption' => 'Descripción breve', 'url' => ''],
                        ['image' => '', 'title' => 'Imagen 3', 'caption' => 'Descripción breve', 'url' => ''],
                    ],
                ],
                'fields' => [
                    ['key' => 'badge', 'label' => 'Etiqueta superior opcional', 'type' => 'text', 'supports_vars' => true],
                    ['key' => 'title', 'label' => 'Título de la galería', 'type' => 'text', 'supports_vars' => true],
                    ['key' => 'subtitle', 'label' => 'Subtítulo opcional', 'type' => 'textarea', 'supports_vars' => true],
                    ['key' => 'columns', 'label' => 'Columnas en escritorio', 'type' => 'select', 'options' => [
                        ['id' => '2', 'name' => '2 columnas'],
                        ['id' => '3', 'name' => '3 columnas'],
                        ['id' => '4', 'name' => '4 columnas'],
                    ]],
                    ['key' => 'aspect_ratio', 'label' => 'Proporción de tarjetas', 'type' => 'select', 'options' => [
                        ['id' => '4-3', 'name' => 'Clásica (4:3)'],
                        ['id' => '1-1', 'name' => 'Cuadrada (1:1)'],
                        ['id' => '16-9', 'name' => 'Panorámica (16:9)'],
                        ['id' => 'auto', 'name' => 'Proporción original'],
                    ]],
                    ['key' => 'gap', 'label' => 'Separación entre imágenes', 'type' => 'select', 'options' => [
                        ['id' => 'sm', 'name' => 'Compacta'],
                        ['id' => 'md', 'name' => 'Estándar'],
                        ['id' => 'lg', 'name' => 'Amplia'],
                    ]],
                    ['key' => 'rounded', 'label' => 'Bordes redondeados', 'type' => 'toggle'],
                    ['key' => 'background_color', 'label' => 'Color de fondo', 'type' => 'color'],
                    ['key' => 'accent_color', 'label' => 'Color de acento', 'type' => 'color'],
                    [
                        'key' => 'images',
                        'label' => 'Imágenes de la galería',
                        'type' => 'repeater',
                        'item_fields' => [
                            ['key' => 'image', 'label' => 'Archivo / URL de imagen', 'type' => 'image', 'supports_vars' => true],
                            ['key' => 'title', 'label' => 'Título / ALT', 'type' => 'text', 'supports_vars' => true],
                            ['key' => 'caption', 'label' => 'Descripción corta', 'type' => 'text', 'supports_vars' => true],
                            ['key' => 'url', 'label' => 'Enlace opcional', 'type' => 'text'],
                        ],
                    ],
                ],
            ],
            'custom_blade' => [
                'type' => 'custom_blade',
                'name' => 'HTML / Tailwind Libre',
                'description' => 'Bloque avanzado para insertar maquetado HTML + Tailwind propio manteniendo interpolación segura de variables dinámicas.',
                'category' => 'Avanzado',
                'icon' => 'o-code-bracket',
                'defaults' => [
                    'title' => 'Bloque Personalizado',
                    'html' => '<div class="p-6 rounded-2xl border border-slate-200 bg-white shadow-sm"><h3 class="text-lg font-bold text-slate-900">{{ company.name }}</h3><p class="mt-2 text-sm text-slate-600">Bloque HTML libre listo para personalizar.</p></div>',
                    'container' => true,
                ],
                'fields' => [
                    ['key' => 'title', 'label' => 'Nombre interno del bloque', 'type' => 'text'],
                    ['key' => 'html', 'label' => 'Código HTML (admite clases Tailwind y variables {{ ... }})', 'type' => 'code', 'supports_vars' => true],
                    ['key' => 'container', 'label' => 'Centrar dentro de contenedor máximo (max-w-7xl)', 'type' => 'toggle'],
                ],
            ],
        ];
    }

    /**
     * Get a single widget definition by type.
     *
     * @return array<string, mixed>|null
     */
    public function get(string $type): ?array
    {
        return $this->all()[$type] ?? null;
    }

    /**
     * Create a new block structure initialized with default props for the given widget type.
     *
     * @return array{id: string, type: string, enabled: bool, props: array<string, mixed>}
     */
    public function defaultBlock(string $type): array
    {
        $definition = $this->get($type) ?? $this->get('rich_text');
        $resolvedType = $definition['type'];

        return [
            'id' => 'blk_'.bin2hex(random_bytes(4)),
            'type' => $resolvedType,
            'enabled' => true,
            'props' => $definition['defaults'],
        ];
    }

    /**
     * Editable layout and home zones supported by the visual builder.
     *
     * @return array<string, array{key: string, label: string, description: string, badge: string}>
     */
    public function zones(): array
    {
        return [
            'topbar' => [
                'key' => 'topbar',
                'label' => 'Barra Superior (Topbar)',
                'description' => 'Se renderiza sobre la barra de navegación en portada y páginas internas.',
                'badge' => 'Global superior',
            ],
            'home_before_catalog' => [
                'key' => 'home_before_catalog',
                'label' => 'Portada — Antes del Catálogo',
                'description' => 'Aparece entre el buscador/slider y la grilla principal de productos en la home.',
                'badge' => 'Home superior',
            ],
            'home_after_catalog' => [
                'key' => 'home_after_catalog',
                'label' => 'Portada — Después del Catálogo',
                'description' => 'Aparece debajo del listado de productos y antes del pie de página en la home.',
                'badge' => 'Home inferior',
            ],
            'footer' => [
                'key' => 'footer',
                'label' => 'Pie de Página (Sobre Footer)',
                'description' => 'Se inyecta en la cabecera superior del footer global en todas las vistas públicas.',
                'badge' => 'Footer global',
            ],
        ];
    }

    /**
     * Ready-to-use starter page templates that populate a coherent block stack in one click.
     *
     * @return array<string, array{
     *     key: string,
     *     name: string,
     *     description: string,
     *     blocks: array<int, array{id: string, type: string, enabled: bool, props: array<string, mixed>}>
     * }>
     */
    public function getStarterTemplates(): array
    {
        return [
            'nosotros' => [
                'key' => 'nosotros',
                'name' => 'Página Nosotros (Institucional)',
                'description' => 'Hero de marca + línea de tiempo histórica + grilla de pilares + CTA comercial.',
                'blocks' => [
                    $this->buildTemplateBlock('hero_banner', [
                        'badge' => 'Institucional • Desde {{ current_year }}',
                        'title' => 'Sobre {{ company.name }}',
                        'subtitle' => 'Construimos relaciones comerciales duraderas a través de stock permanente, logística eficiente y asesoramiento cercano.',
                        'align' => 'left',
                    ]),
                    $this->buildTemplateBlock('story_timeline', []),
                    $this->buildTemplateBlock('feature_grid', []),
                    $this->buildTemplateBlock('cta_assistance', []),
                ],
            ],
            'locales' => [
                'key' => 'locales',
                'name' => 'Página Sucursales / Locales',
                'description' => 'Portada + tarjetas detalladas de sucursales + llamado a la acción.',
                'blocks' => [
                    $this->buildTemplateBlock('hero_banner', [
                        'badge' => 'Atención Presencial y Digital',
                        'title' => 'Encontrá nuestros Locales y Depósitos',
                        'subtitle' => 'Visitá nuestros puntos de venta en {{ company.address }} o consultá stock por canal directo.',
                        'stats' => [
                            ['value' => 'Atención B2B', 'label' => 'Cuentas corrientes y mayoristas'],
                            ['value' => 'Retiro Express', 'label' => 'Coordiná tu pedido web en mostrador'],
                        ],
                    ]),
                    $this->buildTemplateBlock('branch_cards', []),
                    $this->buildTemplateBlock('cta_assistance', []),
                ],
            ],
            'contacto' => [
                'key' => 'contacto',
                'name' => 'Página de Contacto y Ubicación',
                'description' => 'Ficha completa de teléfono, dirección, horarios y mapa embebido.',
                'blocks' => [
                    $this->buildTemplateBlock('hero_banner', [
                        'badge' => 'Canales Oficiales',
                        'title' => 'Contactate con {{ company.name }}',
                        'subtitle' => 'Nuestro equipo comercial está disponible para responder tus consultas de presupuestos, entregas y catálogo.',
                        'stats' => [],
                    ]),
                    $this->buildTemplateBlock('contact_map', []),
                    $this->buildTemplateBlock('cta_assistance', []),
                ],
            ],
            'promo_home' => [
                'key' => 'promo_home',
                'name' => 'Franja Promocional de Portada',
                'description' => 'Anuncio destacado + carrusel de productos destacados + beneficios de compra.',
                'blocks' => [
                    $this->buildTemplateBlock('topbar_announcement', []),
                    $this->buildTemplateBlock('product_slider', [
                        'title' => 'Destacados de la Semana',
                        'slider_type' => 'featured',
                    ]),
                    $this->buildTemplateBlock('feature_grid', []),
                ],
            ],
        ];
    }

    /**
     * Build a starter block merging explicit overrides onto widget defaults.
     *
     * @param  array<string, mixed>  $overrides
     * @return array{id: string, type: string, enabled: bool, props: array<string, mixed>}
     */
    private function buildTemplateBlock(string $type, array $overrides): array
    {
        $block = $this->defaultBlock($type);
        $block['props'] = array_replace_recursive($block['props'], $overrides);

        if ($overrides !== []) {
            foreach ($overrides as $key => $value) {
                if (is_array($value)) {
                    $block['props'][$key] = $value;
                }
            }
        }

        return $block;
    }
}
