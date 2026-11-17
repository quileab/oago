<?php

namespace App\Services;

class ThemeCatalogComponentRegistry
{
    /**
     * Catalog component targets editable from Studio.
     *
     * @return array<string, array{key: string, label: string, short_label: string, description: string, target_view: string}>
     */
    public function components(): array
    {
        return [
            'product_card' => [
                'key' => 'product_card',
                'label' => 'Tarjeta de Producto (Card)',
                'short_label' => 'Card de Producto',
                'description' => 'Diseño, colores, orden de bloques, textos y distribución de botones en grilla y carruseles.',
                'target_view' => 'livewire/web-product-card.blade.php',
            ],
            'product_detail' => [
                'key' => 'product_detail',
                'label' => 'Vista Única de Producto (Detalle)',
                'short_label' => 'Vista Única',
                'description' => 'Diseño de galería, orden de bloques informativos, precios, botones de compra y relacionados.',
                'target_view' => 'livewire/web-product-detail.blade.php',
            ],
        ];
    }

    /**
     * Sub-block schema definitions for a catalog component.
     *
     * @return array<string, array{
     *     type: string,
     *     name: string,
     *     description: string,
     *     fixed?: bool,
     *     defaults: array<string, mixed>,
     *     fields: array<int, array<string, mixed>>
     * }>
     */
    public function definitions(string $component): array
    {
        return match ($component) {
            'product_detail' => $this->productDetailDefinitions(),
            default => $this->productCardDefinitions(),
        };
    }

    /**
     * Retrieve a single sub-block definition for the specified component.
     *
     * @return array<string, mixed>|null
     */
    public function getDefinition(string $component, string $type): ?array
    {
        return $this->definitions($component)[$type] ?? null;
    }

    /**
     * Return the default ordered stack of sub-blocks for a catalog component.
     *
     * @return array<int, array{id: string, type: string, enabled: bool, props: array<string, mixed>}>
     */
    public function defaultBlocks(string $component): array
    {
        $blocks = [];

        foreach ($this->definitions($component) as $type => $def) {
            $blocks[] = [
                'id' => 'cat_'.$type,
                'type' => $type,
                'enabled' => $type === 'card_excerpt' ? false : true,
                'props' => $def['defaults'],
            ];
        }

        return $blocks;
    }

    /**
     * Ready-to-use layout presets for catalog components.
     *
     * @return array<string, array{key: string, name: string, description: string, blocks: array<int, array{id: string, type: string, enabled: bool, props: array<string, mixed>}>}>
     */
    public function presets(string $component): array
    {
        if ($component === 'product_detail') {
            return [
                'classic_light' => [
                    'key' => 'classic_light',
                    'name' => 'Clásico Claro (B2B)',
                    'description' => 'Fondo blanco luminoso con galería a la izquierda, precios destacados y botones apilados.',
                    'blocks' => $this->buildPresetBlocks('product_detail', []),
                ],
                'technical_checklist' => [
                    'key' => 'technical_checklist',
                    'name' => 'Ficha Técnica + Checklist',
                    'description' => 'Lista de atributos con íconos arriba del precio, proporción 45/55 y selector en línea.',
                    'blocks' => $this->buildPresetBlocks('product_detail', [
                        'detail_style' => [
                            'surface_style' => 'soft',
                            'column_ratio' => '45-55',
                            'primary_color' => '#1b365d',
                            'price_color' => '#0f172a',
                        ],
                        'detail_description' => [
                            'show_html_description' => true,
                            'show_tags_checklist' => true,
                        ],
                        'detail_actions' => [
                            'buttons_layout' => 'inline',
                            'show_bulk_step_buttons' => false,
                        ],
                    ], ['detail_style', 'detail_tags', 'detail_header', 'detail_stock', 'detail_description', 'detail_price', 'detail_actions', 'detail_related']),
                ],
                'dark_glass' => [
                    'key' => 'dark_glass',
                    'name' => 'Oscuro Industrial (Glass)',
                    'description' => 'Superficie oscura con alto contraste, precio esmeralda y acentos rojos.',
                    'blocks' => $this->buildPresetBlocks('product_detail', [
                        'detail_style' => [
                            'surface_style' => 'dark',
                            'column_ratio' => '50-50',
                            'primary_color' => '#dc2626',
                            'price_color' => '#34d399',
                            'accent_color' => '#dc2626',
                            'tag_bg_color' => '#d97706',
                        ],
                        'detail_actions' => [
                            'buttons_layout' => 'stacked',
                            'show_bulk_step_buttons' => true,
                        ],
                    ]),
                ],
            ];
        }

        return [
            'horizontal_b2b' => [
                'key' => 'horizontal_b2b',
                'name' => 'Horizontal Clásico (B2B)',
                'description' => 'Imagen izquierda 45% + info derecha 55%, toggle Bulto/Unidad y Similares + Agregar lado a lado.',
                'blocks' => $this->buildPresetBlocks('product_card', [
                    'card_style' => [
                        'layout' => 'horizontal',
                        'rounded' => 'rounded-xl',
                        'primary_color' => '#1b365d',
                        'secondary_color' => '#f97316',
                        'price_color' => '#15803d',
                    ],
                    'card_excerpt' => ['lines' => '2'],
                ], ['card_style', 'card_image', 'card_tags', 'card_title', 'card_meta', 'card_price', 'card_excerpt', 'card_stock', 'card_actions'], [
                    'card_excerpt' => true,
                ]),
            ],
            'vertical_vitrina' => [
                'key' => 'vertical_vitrina',
                'name' => 'Vitrina Vertical Moderna',
                'description' => 'Imagen superior 1:1 con badges flotantes, título limpio abajo y botón principal a ancho completo.',
                'blocks' => $this->buildPresetBlocks('product_card', [
                    'card_style' => [
                        'layout' => 'vertical',
                        'rounded' => 'rounded-2xl',
                        'image_bg' => '#f8fafc',
                        'primary_color' => '#cc0000',
                        'price_color' => '#111827',
                    ],
                    'card_tags' => ['position' => 'overlay', 'max_tags' => '2'],
                    'card_image' => ['show_favorite_icon' => true],
                    'card_price' => ['align' => 'left', 'show_unit_price' => false],
                    'card_actions' => [
                        'show_bulk_toggle' => false,
                        'buttons_layout' => 'add_only',
                        'add_button_label' => 'AGREGAR AL CARRITO',
                        'guest_actions_style' => 'register_and_login',
                    ],
                ], ['card_style', 'card_image', 'card_tags', 'card_meta', 'card_title', 'card_price', 'card_stock', 'card_actions', 'card_excerpt'], [
                    'card_excerpt' => false,
                ]),
            ],
            'compact_mayorista' => [
                'key' => 'compact_mayorista',
                'name' => 'Compacta Pedido Rápido',
                'description' => 'Diseño compacto para reponer rápido con selector bulto/unidad y botones Agregar + Similares apilados.',
                'blocks' => $this->buildPresetBlocks('product_card', [
                    'card_style' => [
                        'layout' => 'compact',
                        'rounded' => 'rounded-lg',
                        'primary_color' => '#0f172a',
                        'secondary_color' => '#2563eb',
                        'price_color' => '#15803d',
                    ],
                    'card_title' => ['lines' => '2', 'font_size' => 'xs', 'show_divider' => false],
                    'card_price' => ['align' => 'left', 'show_unit_price' => true],
                    'card_actions' => [
                        'qty_height' => 'sm',
                        'buttons_layout' => 'add_similar_row',
                    ],
                ], null, ['card_excerpt' => false, 'card_stock' => true]),
            ],
        ];
    }

    /**
     * Build sample view payload for live preview inside Studio under different buyer states.
     *
     * @return array<string, mixed>
     */
    public function mockPreviewData(string $state = 'offer'): array
    {
        $isGuest = $state === 'guest';
        $isOutOfStock = $state === 'out_of_stock';
        $hasOffer = $state === 'offer';

        $basePrice = 48500.00;
        $promoPrice = $hasOffer ? 39900.00 : 0.0;

        $product = (object) [
            'id' => 10428,
            'brand' => 'DOC BROWN LABS',
            'model' => 'OUTATIME-88',
            'description' => 'Condensador de Flujo 1.21 Gigawatts DeLorean DMC-12',
            'description_html' => '<p>Viajes temporales precisos a 88 mph, carcasa hermética de acero inoxidable y núcleo energético de fusión.</p>',
            'image_url' => '/imgs/fallback.webp',
            'featured' => true,
            'qtty_package' => 6,
            'qtty_unit' => 2,
            'stock' => $isOutOfStock ? 0 : 34,
            'in_stock' => ! $isOutOfStock,
            'deduct_stock' => true,
            'allow_reservation' => false,
            'tags_array' => ['MARVEL', 'NUEVO', 'EDICIÓN 1985'],
            'commercial_tags_array' => ['NUEVO', 'EDICIÓN 1985'],
            'division' => [
                'slug' => 'marvel',
                'store_key' => 'marvel',
                'name' => 'MARVEL',
                'color' => '#1b365d',
                'fallback_image' => 'fallback-marvel.webp',
            ],
            'media' => [
                ['type' => 'image', 'url' => '/imgs/fallback.webp', 'thumb' => '/imgs/fallback.webp', 'isFallback' => false],
                ['type' => 'image', 'url' => '/imgs/fallback.webp', 'thumb' => '/imgs/fallback.webp', 'isFallback' => false],
            ],
        ];

        $stockBadge = $isOutOfStock
            ? ['label' => 'Agotado', 'icon' => 'o-x-circle', 'class' => 'bg-red-50 text-red-700 border-red-200']
            : ['label' => 'En Stock', 'icon' => 'o-check-circle', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'];

        return [
            'product' => $product,
            'display_price' => $basePrice,
            'display_offer' => $promoPrice,
            'cart' => $isGuest ? [] : [10428 => ['quantity' => 6]],
            'isGuest' => $isGuest,
            'showPrices' => ! $isGuest,
            'guestMessage' => 'Regístrese para ver precios',
            'isOutOfStock' => $isOutOfStock,
            'hasAvailableStock' => ! $isOutOfStock,
            'stockBadge' => $stockBadge,
            'cartPayload' => [
                'id' => 10428,
                'description' => $product->description,
                'user_price' => $promoPrice > 0 ? $promoPrice : $basePrice,
                'qtty_package' => 6,
            ],
            'relatedProducts' => [
                (object) [
                    'id' => 10429,
                    'brand' => 'ASGARD FORGE',
                    'description' => 'Martillo de Thor Mjölnir Forjado en Uru Encantado',
                    'description_html' => '<p>Acero estelar equilibrado con correa de cuero ceremonial.</p>',
                    'image_url' => '/imgs/fallback.webp',
                    'featured' => false,
                    'qtty_package' => 4,
                    'qtty_unit' => 1,
                    'tags_array' => ['MARVEL'],
                    'commercial_tags_array' => [],
                    'display_price' => 27400.00,
                    'display_offer' => 0.0,
                ],
                (object) [
                    'id' => 10430,
                    'brand' => 'WAYNE TECH',
                    'description' => 'Batarang Táctico Supersónico Plegable de Carbono',
                    'description_html' => '<p>Aerodinámica de precisión con retorno silencioso.</p>',
                    'image_url' => '/imgs/fallback.webp',
                    'featured' => true,
                    'qtty_package' => 1,
                    'qtty_unit' => 1,
                    'tags_array' => ['DC', 'OFERTA'],
                    'commercial_tags_array' => ['OFERTA'],
                    'display_price' => 92000.00,
                    'display_offer' => 79500.00,
                ],
                (object) [
                    'id' => 10431,
                    'brand' => 'STARK INDUSTRIES',
                    'description' => 'Reactor Arc Mark LXXXV Nanotecnología Portátil',
                    'description_html' => '<p>Salida energética limpia con enfriamiento automático.</p>',
                    'image_url' => '/imgs/fallback.webp',
                    'featured' => false,
                    'qtty_package' => 10,
                    'qtty_unit' => 4,
                    'tags_array' => ['MARVEL', 'NUEVO'],
                    'commercial_tags_array' => ['NUEVO'],
                    'display_price' => 31800.00,
                    'display_offer' => 0.0,
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{type: string, name: string, description: string, fixed?: bool, defaults: array<string, mixed>, fields: array<int, array<string, mixed>>}>
     */
    private function productCardDefinitions(): array
    {
        return [
            'card_style' => [
                'type' => 'card_style',
                'name' => 'Diseño General y Colores',
                'description' => 'Disposición vertical/horizontal/compacta, paleta de colores, bordes y color por división.',
                'fixed' => true,
                'defaults' => [
                    'layout' => 'horizontal',
                    'rounded' => 'rounded-xl',
                    'shadow' => 'shadow-md',
                    'card_bg' => '#ffffff',
                    'image_bg' => '#f8fafc',
                    'text_color' => '#111827',
                    'primary_color' => '#1b365d',
                    'secondary_color' => '#f97316',
                    'price_color' => '#15803d',
                    'offer_badge_color' => '#dc2626',
                    'tag_bg_color' => '#d97706',
                    'enable_division_colors' => false,
                ],
                'fields' => [
                    ['key' => 'layout', 'label' => 'Disposición general de la tarjeta', 'type' => 'select', 'options' => [
                        ['id' => 'horizontal', 'name' => 'Horizontal (Imagen 45% + Info 55%)'],
                        ['id' => 'vertical', 'name' => 'Vertical (Imagen arriba + Info abajo)'],
                        ['id' => 'compact', 'name' => 'Compacta (Catálogo denso B2B)'],
                    ]],
                    ['key' => 'rounded', 'label' => 'Redondeo de bordes', 'type' => 'select', 'options' => [
                        ['id' => 'rounded-none', 'name' => 'Cuadrado (0px)'],
                        ['id' => 'rounded-lg', 'name' => 'Suave (8px)'],
                        ['id' => 'rounded-xl', 'name' => 'Moderno (12px)'],
                        ['id' => 'rounded-2xl', 'name' => 'Amplio (16px)'],
                    ]],
                    ['key' => 'shadow', 'label' => 'Sombra', 'type' => 'select', 'options' => [
                        ['id' => 'shadow-sm', 'name' => 'Sutil'],
                        ['id' => 'shadow-md', 'name' => 'Media'],
                        ['id' => 'shadow-lg', 'name' => 'Pronunciada'],
                    ]],
                    ['key' => 'card_bg', 'label' => 'Color de fondo de la tarjeta', 'type' => 'color'],
                    ['key' => 'image_bg', 'label' => 'Color de fondo del área de imagen', 'type' => 'color'],
                    ['key' => 'text_color', 'label' => 'Color de texto principal', 'type' => 'color'],
                    ['key' => 'primary_color', 'label' => 'Color botón principal (Agregar)', 'type' => 'color'],
                    ['key' => 'secondary_color', 'label' => 'Color botón secundario (Similares)', 'type' => 'color'],
                    ['key' => 'price_color', 'label' => 'Color del precio destacado', 'type' => 'color'],
                    ['key' => 'offer_badge_color', 'label' => 'Color de distintivo Oferta / Destacado', 'type' => 'color'],
                    ['key' => 'tag_bg_color', 'label' => 'Color de etiquetas (Tags)', 'type' => 'color'],
                    ['key' => 'enable_division_colors', 'label' => 'Colores dinámicos según el Tag de División (Ej: Marvel vs DC)', 'type' => 'toggle'],
                ],
            ],
            'card_image' => [
                'type' => 'card_image',
                'name' => 'Imagen y Badges',
                'description' => 'Proporción de imagen, zoom al pasar el mouse, sello de agotado, destacado e ícono de favorito.',
                'defaults' => [
                    'aspect_ratio' => '1-1',
                    'hover_zoom' => true,
                    'show_featured_badge' => true,
                    'featured_badge_text' => '⭐',
                    'offer_badge_text' => 'OFERTA',
                    'out_of_stock_text' => 'Agotado',
                    'show_favorite_icon' => false,
                ],
                'fields' => [
                    ['key' => 'aspect_ratio', 'label' => 'Proporción del recuadro de imagen', 'type' => 'select', 'options' => [
                        ['id' => '1-1', 'name' => 'Cuadrada (1:1)'],
                        ['id' => '4-3', 'name' => 'Clásica (4:3)'],
                        ['id' => 'auto', 'name' => 'Libre / Adaptable'],
                    ]],
                    ['key' => 'hover_zoom', 'label' => 'Efecto zoom suave al pasar el cursor', 'type' => 'toggle'],
                    ['key' => 'show_featured_badge', 'label' => 'Mostrar badge de Oferta / Destacado sobre la foto', 'type' => 'toggle'],
                    ['key' => 'featured_badge_text', 'label' => 'Texto badge Destacado', 'type' => 'text'],
                    ['key' => 'offer_badge_text', 'label' => 'Texto badge Oferta', 'type' => 'text'],
                    ['key' => 'out_of_stock_text', 'label' => 'Texto sello Agotado', 'type' => 'text'],
                    ['key' => 'show_favorite_icon', 'label' => 'Mostrar ícono de corazón decorativo', 'type' => 'toggle'],
                ],
            ],
            'card_tags' => [
                'type' => 'card_tags',
                'name' => 'Etiquetas (Tags)',
                'description' => 'Muestra las etiquetas del producto sobre la imagen o en el cuerpo de información.',
                'defaults' => [
                    'max_tags' => '3',
                    'position' => 'body',
                ],
                'fields' => [
                    ['key' => 'position', 'label' => 'Ubicación de etiquetas', 'type' => 'select', 'options' => [
                        ['id' => 'body', 'name' => 'En la zona de información'],
                        ['id' => 'overlay', 'name' => 'Superpuestas sobre la imagen'],
                    ]],
                    ['key' => 'max_tags', 'label' => 'Cantidad máxima a mostrar', 'type' => 'select', 'options' => [
                        ['id' => '2', 'name' => 'Hasta 2 etiquetas'],
                        ['id' => '3', 'name' => 'Hasta 3 etiquetas'],
                        ['id' => 'all', 'name' => 'Todas las etiquetas'],
                    ]],
                ],
            ],
            'card_meta' => [
                'type' => 'card_meta',
                'name' => 'Marca y Código #ID',
                'description' => 'Chip de marca comercial y código identificador del producto.',
                'defaults' => [
                    'show_brand' => true,
                    'show_id' => true,
                    'id_prefix' => '#',
                    'align' => 'left',
                ],
                'fields' => [
                    ['key' => 'show_brand', 'label' => 'Mostrar marca', 'type' => 'toggle'],
                    ['key' => 'show_id', 'label' => 'Mostrar código/ID de producto', 'type' => 'toggle'],
                    ['key' => 'id_prefix', 'label' => 'Prefijo del código', 'type' => 'text'],
                    ['key' => 'align', 'label' => 'Alineación', 'type' => 'select', 'options' => [
                        ['id' => 'left', 'name' => 'Izquierda'],
                        ['id' => 'center', 'name' => 'Centrado'],
                        ['id' => 'between', 'name' => 'Extremos (Marca izq. / ID der.)'],
                    ]],
                ],
            ],
            'card_title' => [
                'type' => 'card_title',
                'name' => 'Título del Producto',
                'description' => 'Cantidad de líneas máximas, tamaño de tipografía, mayúsculas y divisor inferior.',
                'defaults' => [
                    'lines' => '2',
                    'uppercase' => false,
                    'font_size' => 'sm',
                    'show_divider' => false,
                ],
                'fields' => [
                    ['key' => 'lines', 'label' => 'Líneas máximas visibles', 'type' => 'select', 'options' => [
                        ['id' => '1', 'name' => '1 línea'],
                        ['id' => '2', 'name' => '2 líneas'],
                        ['id' => '3', 'name' => '3 líneas'],
                    ]],
                    ['key' => 'font_size', 'label' => 'Tamaño de letra', 'type' => 'select', 'options' => [
                        ['id' => 'xs', 'name' => 'Compacto (xs)'],
                        ['id' => 'sm', 'name' => 'Estándar (sm)'],
                        ['id' => 'base', 'name' => 'Grande (base)'],
                    ]],
                    ['key' => 'uppercase', 'label' => 'Transformar título a MAYÚSCULAS', 'type' => 'toggle'],
                    ['key' => 'show_divider', 'label' => 'Mostrar línea divisoria inferior', 'type' => 'toggle'],
                ],
            ],
            'card_price' => [
                'type' => 'card_price',
                'name' => 'Bloque de Precios',
                'description' => 'Alineación del precio, oferta tachada, precio por unidad y textos para invitados o sin precio.',
                'defaults' => [
                    'align' => 'right',
                    'show_unit_price' => true,
                    'unit_price_suffix' => 'x un.',
                    'coming_soon_text' => 'Muy pronto',
                    'guest_price_text' => 'Precios solo para usuarios registrados',
                ],
                'fields' => [
                    ['key' => 'align', 'label' => 'Alineación del bloque de precio', 'type' => 'select', 'options' => [
                        ['id' => 'left', 'name' => 'Izquierda'],
                        ['id' => 'center', 'name' => 'Centrado'],
                        ['id' => 'right', 'name' => 'Derecha'],
                    ]],
                    ['key' => 'show_unit_price', 'label' => 'Mostrar precio unitario cuando qtty_unit > 1', 'type' => 'toggle'],
                    ['key' => 'unit_price_suffix', 'label' => 'Sufijo precio por unidad', 'type' => 'text'],
                    ['key' => 'coming_soon_text', 'label' => 'Texto cuando el precio es 0', 'type' => 'text'],
                    ['key' => 'guest_price_text', 'label' => 'Texto cuando los precios están ocultos a invitados', 'type' => 'text'],
                ],
            ],
            'card_excerpt' => [
                'type' => 'card_excerpt',
                'name' => 'Resumen / Descripción Corta',
                'description' => 'Extracto de texto HTML del producto dentro de la card.',
                'defaults' => [
                    'lines' => '2',
                ],
                'fields' => [
                    ['key' => 'lines', 'label' => 'Líneas máximas del resumen', 'type' => 'select', 'options' => [
                        ['id' => '2', 'name' => '2 líneas'],
                        ['id' => '3', 'name' => '3 líneas'],
                    ]],
                ],
            ],
            'card_stock' => [
                'type' => 'card_stock',
                'name' => 'Stock y En Carrito',
                'description' => 'Badge de disponibilidad y contador en tiempo real de unidades ya agregadas al carrito.',
                'defaults' => [
                    'show_stock_badge' => true,
                    'show_in_cart_badge' => true,
                    'in_cart_label' => 'en carrito:',
                ],
                'fields' => [
                    ['key' => 'show_stock_badge', 'label' => 'Mostrar badge de stock', 'type' => 'toggle'],
                    ['key' => 'show_in_cart_badge', 'label' => 'Mostrar indicador "en carrito"', 'type' => 'toggle'],
                    ['key' => 'in_cart_label', 'label' => 'Texto indicador carrito', 'type' => 'text'],
                ],
            ],
            'card_actions' => [
                'type' => 'card_actions',
                'name' => 'Botones y Selector de Cantidad',
                'description' => 'Redistribuye los botones Agregar y Similares, selector Bulto/Unidad y acciones para invitados.',
                'defaults' => [
                    'show_bulk_toggle' => true,
                    'bulk_label' => 'BULTO',
                    'unit_label' => 'UNIDAD',
                    'show_qty_selector' => true,
                    'qty_height' => 'md',
                    'buttons_layout' => 'similar_add_row',
                    'add_button_label' => 'AGREGAR',
                    'similar_button_label' => 'Similares',
                    'guest_actions_style' => 'message_only',
                    'guest_register_label' => 'REGISTRARSE',
                    'guest_login_label' => 'INGRESAR',
                ],
                'fields' => [
                    ['key' => 'buttons_layout', 'label' => 'Distribución de botones', 'type' => 'select', 'options' => [
                        ['id' => 'similar_add_row', 'name' => 'Fila 2 cols: [Similares] + [Agregar]'],
                        ['id' => 'add_similar_row', 'name' => 'Fila 2 cols: [Agregar] + [Similares]'],
                        ['id' => 'add_only', 'name' => 'Solo [Agregar al Carrito] ancho completo'],
                        ['id' => 'add_similar_stack', 'name' => 'Apilados: [Agregar] arriba + [Similares] abajo'],
                        ['id' => 'similar_add_stack', 'name' => 'Apilados: [Similares] arriba + [Agregar] abajo'],
                    ]],
                    ['key' => 'add_button_label', 'label' => 'Texto botón Agregar', 'type' => 'text'],
                    ['key' => 'similar_button_label', 'label' => 'Texto botón Similares', 'type' => 'text'],
                    ['key' => 'show_bulk_toggle', 'label' => 'Mostrar selector BULTO / UNIDAD', 'type' => 'toggle'],
                    ['key' => 'bulk_label', 'label' => 'Etiqueta Bulto', 'type' => 'text'],
                    ['key' => 'unit_label', 'label' => 'Etiqueta Unidad', 'type' => 'text'],
                    ['key' => 'show_qty_selector', 'label' => 'Mostrar selector numérico (- / cantidad / +)', 'type' => 'toggle'],
                    ['key' => 'qty_height', 'label' => 'Altura del selector de cantidad', 'type' => 'select', 'options' => [
                        ['id' => 'sm', 'name' => 'Compacto (32px)'],
                        ['id' => 'md', 'name' => 'Estándar (44px)'],
                    ]],
                    ['key' => 'guest_actions_style', 'label' => 'Pie para invitados (sin sesión)', 'type' => 'select', 'options' => [
                        ['id' => 'message_only', 'name' => 'Solo aviso de registro con candado'],
                        ['id' => 'register_and_login', 'name' => 'Botones [Registrarse] + [Ingresar]'],
                        ['id' => 'register_and_disabled', 'name' => 'Botón [Registrarse] + [Agregar] deshabilitado'],
                    ]],
                    ['key' => 'guest_register_label', 'label' => 'Texto botón Registrarse (invitados)', 'type' => 'text'],
                    ['key' => 'guest_login_label', 'label' => 'Texto botón Ingresar (invitados)', 'type' => 'text'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{type: string, name: string, description: string, fixed?: bool, defaults: array<string, mixed>, fields: array<int, array<string, mixed>>}>
     */
    private function productDetailDefinitions(): array
    {
        return [
            'detail_style' => [
                'type' => 'detail_style',
                'name' => 'Diseño General, Galería y Colores',
                'description' => 'Ubicación y proporción de la galería, tema claro/oscuro, paleta y sección de relacionados.',
                'fixed' => true,
                'defaults' => [
                    'gallery_position' => 'left',
                    'column_ratio' => '50-50',
                    'surface_style' => 'light',
                    'primary_color' => '#1b365d',
                    'price_color' => '#15803d',
                    'accent_color' => '#dc2626',
                    'tag_bg_color' => '#d97706',
                    'back_button_label' => 'Volver al catálogo',
                    'featured_badge_text' => 'PRODUCTO DESTACADO ⭐',
                    'out_of_stock_text' => 'Agotado',
                ],
                'fields' => [
                    ['key' => 'gallery_position', 'label' => 'Posición de la galería de imágenes/video', 'type' => 'select', 'options' => [
                        ['id' => 'left', 'name' => 'Galería a la izquierda'],
                        ['id' => 'right', 'name' => 'Galería a la derecha'],
                    ]],
                    ['key' => 'column_ratio', 'label' => 'Proporción de columnas en escritorio', 'type' => 'select', 'options' => [
                        ['id' => '50-50', 'name' => 'Mitad y mitad (50% / 50%)'],
                        ['id' => '45-55', 'name' => 'Galería 45% / Información 55%'],
                        ['id' => '40-60', 'name' => 'Galería compacta 40% / Información 60%'],
                    ]],
                    ['key' => 'surface_style', 'label' => 'Estilo de superficie', 'type' => 'select', 'options' => [
                        ['id' => 'light', 'name' => 'Claro (Fondo blanco limpio)'],
                        ['id' => 'soft', 'name' => 'Suave (Tarjeta blanca + detalles pizarra)'],
                        ['id' => 'dark', 'name' => 'Oscuro Industrial (Alto Contraste)'],
                    ]],
                    ['key' => 'primary_color', 'label' => 'Color principal (Botón compra / acentos)', 'type' => 'color'],
                    ['key' => 'price_color', 'label' => 'Color del precio destacado', 'type' => 'color'],
                    ['key' => 'accent_color', 'label' => 'Color de acento / Destacado', 'type' => 'color'],
                    ['key' => 'tag_bg_color', 'label' => 'Color de etiquetas (Tags)', 'type' => 'color'],
                    ['key' => 'back_button_label', 'label' => 'Texto botón volver', 'type' => 'text'],
                    ['key' => 'featured_badge_text', 'label' => 'Texto badge producto destacado', 'type' => 'text'],
                    ['key' => 'out_of_stock_text', 'label' => 'Texto sello producto agotado', 'type' => 'text'],
                ],
            ],
            'detail_tags' => [
                'type' => 'detail_tags',
                'name' => 'Etiquetas Superiores',
                'description' => 'Chips de etiquetas asignadas al producto.',
                'defaults' => [
                    'show_tags' => true,
                ],
                'fields' => [
                    ['key' => 'show_tags', 'label' => 'Mostrar etiquetas sobre el título', 'type' => 'toggle'],
                ],
            ],
            'detail_header' => [
                'type' => 'detail_header',
                'name' => 'Título, Marca y REF',
                'description' => 'Encabezado principal con descripción comercial, marca y código de referencia.',
                'defaults' => [
                    'title_size' => '3xl',
                    'show_brand' => true,
                    'show_ref' => true,
                    'ref_prefix' => 'REF: ',
                ],
                'fields' => [
                    ['key' => 'title_size', 'label' => 'Tamaño del título', 'type' => 'select', 'options' => [
                        ['id' => '2xl', 'name' => 'Mediano (2xl)'],
                        ['id' => '3xl', 'name' => 'Grande (3xl)'],
                        ['id' => '4xl', 'name' => 'Extra grande (4xl)'],
                    ]],
                    ['key' => 'show_brand', 'label' => 'Mostrar badge de Marca', 'type' => 'toggle'],
                    ['key' => 'show_ref', 'label' => 'Mostrar código de referencia', 'type' => 'toggle'],
                    ['key' => 'ref_prefix', 'label' => 'Prefijo código de referencia', 'type' => 'text'],
                ],
            ],
            'detail_description' => [
                'type' => 'detail_description',
                'name' => 'Descripción y Características',
                'description' => 'Texto enriquecido HTML del producto y lista visual opcional de viñetas con ícono.',
                'defaults' => [
                    'show_html_description' => true,
                    'show_tags_checklist' => false,
                ],
                'fields' => [
                    ['key' => 'show_html_description', 'label' => 'Mostrar descripción HTML completa', 'type' => 'toggle'],
                    ['key' => 'show_tags_checklist', 'label' => 'Mostrar lista con checks de los tags principales', 'type' => 'toggle'],
                ],
            ],
            'detail_price' => [
                'type' => 'detail_price',
                'name' => 'Caja de Precios y Oferta',
                'description' => 'Precio regular tachado, precio promocional, badge oferta y precio unitario.',
                'defaults' => [
                    'regular_price_prefix' => 'Precio regular:',
                    'offer_badge_label' => 'Oferta',
                    'show_unit_price' => true,
                    'unit_price_prefix' => 'Precio por unidad:',
                    'coming_soon_text' => 'Muy pronto',
                ],
                'fields' => [
                    ['key' => 'regular_price_prefix', 'label' => 'Prefijo precio regular tachado', 'type' => 'text'],
                    ['key' => 'offer_badge_label', 'label' => 'Etiqueta de Oferta', 'type' => 'text'],
                    ['key' => 'show_unit_price', 'label' => 'Mostrar precio por unidad (cuando qtty_unit > 1)', 'type' => 'toggle'],
                    ['key' => 'unit_price_prefix', 'label' => 'Prefijo precio por unidad', 'type' => 'text'],
                    ['key' => 'coming_soon_text', 'label' => 'Texto cuando precio es 0', 'type' => 'text'],
                ],
            ],
            'detail_stock' => [
                'type' => 'detail_stock',
                'name' => 'Stock y Venta por Bulto',
                'description' => 'Indicadores de disponibilidad en depósito y cantidad por bulto.',
                'defaults' => [
                    'show_stock_badge' => true,
                    'show_package_badge' => true,
                    'package_prefix' => 'Venta por bulto:',
                ],
                'fields' => [
                    ['key' => 'show_stock_badge', 'label' => 'Mostrar badge de disponibilidad', 'type' => 'toggle'],
                    ['key' => 'show_package_badge', 'label' => 'Mostrar badge de bulto', 'type' => 'toggle'],
                    ['key' => 'package_prefix', 'label' => 'Prefijo venta por bulto', 'type' => 'text'],
                ],
            ],
            'detail_actions' => [
                'type' => 'detail_actions',
                'name' => 'Selector de Cantidad y Compra',
                'description' => 'Botones de suma/resta rápida por unidad y bulto, botón comprar y aviso a invitados.',
                'defaults' => [
                    'show_bulk_step_buttons' => true,
                    'buttons_layout' => 'stacked',
                    'buy_button_label' => 'AGREGAR AL CARRITO',
                    'button_size' => 'lg',
                    'guest_message' => 'Inicie sesión para ver precios y comprar',
                    'guest_button_label' => 'Ingresar ahora',
                ],
                'fields' => [
                    ['key' => 'buttons_layout', 'label' => 'Distribución de cantidad y compra', 'type' => 'select', 'options' => [
                        ['id' => 'stacked', 'name' => 'Selector arriba + Botón compra ancho completo'],
                        ['id' => 'inline', 'name' => 'Selector y Botón compra en la misma fila'],
                    ]],
                    ['key' => 'show_bulk_step_buttons', 'label' => 'Mostrar botones rápidos (-Bulto / +Bulto)', 'type' => 'toggle'],
                    ['key' => 'buy_button_label', 'label' => 'Texto botón principal', 'type' => 'text'],
                    ['key' => 'button_size', 'label' => 'Altura de controles', 'type' => 'select', 'options' => [
                        ['id' => 'md', 'name' => 'Estándar (48px)'],
                        ['id' => 'lg', 'name' => 'Grande (56px)'],
                    ]],
                    ['key' => 'guest_message', 'label' => 'Mensaje para visitantes sin sesión', 'type' => 'text'],
                    ['key' => 'guest_button_label', 'label' => 'Texto botón login para visitantes', 'type' => 'text'],
                ],
            ],
            'detail_related' => [
                'type' => 'detail_related',
                'name' => 'Productos Relacionados',
                'description' => 'Sección inferior de artículos sugeridos con título y grilla configurables.',
                'pinned' => true,
                'defaults' => [
                    'related_title' => 'PRODUCTOS RELACIONADOS',
                    'related_columns' => '4',
                ],
                'fields' => [
                    ['key' => 'related_title', 'label' => 'Título de Productos Relacionados', 'type' => 'text'],
                    ['key' => 'related_columns', 'label' => 'Columnas en escritorio', 'type' => 'select', 'options' => [
                        ['id' => '3', 'name' => '3 columnas'],
                        ['id' => '4', 'name' => '4 columnas'],
                    ]],
                ],
            ],
        ];
    }

    /**
     * Build an ordered block list applying prop and visibility overrides on top of defaults.
     *
     * @param  array<string, array<string, mixed>>  $propOverrides
     * @param  array<int, string>|null  $order
     * @param  array<string, bool>  $enabledOverrides
     * @return array<int, array{id: string, type: string, enabled: bool, props: array<string, mixed>}>
     */
    private function buildPresetBlocks(
        string $component,
        array $propOverrides,
        ?array $order = null,
        array $enabledOverrides = []
    ): array {
        $defaultsByType = [];
        foreach ($this->defaultBlocks($component) as $block) {
            $defaultsByType[$block['type']] = $block;
        }

        $orderedTypes = $order ?? array_keys($defaultsByType);
        $blocks = [];

        foreach ($orderedTypes as $type) {
            if (! isset($defaultsByType[$type])) {
                continue;
            }

            $block = $defaultsByType[$type];
            if (isset($propOverrides[$type])) {
                $block['props'] = array_replace($block['props'], $propOverrides[$type]);
            }
            if (array_key_exists($type, $enabledOverrides)) {
                $block['enabled'] = (bool) $enabledOverrides[$type];
            }

            $blocks[] = $block;
        }

        foreach ($defaultsByType as $type => $block) {
            if (! in_array($type, $orderedTypes, true)) {
                $blocks[] = $block;
            }
        }

        return $blocks;
    }
}
