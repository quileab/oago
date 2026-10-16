<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    /**
     * Default product tags, ordered by sort_order.
     * These are only seeded on a fresh install; afterwards tags are managed
     * from /tags and live exclusively in the `taggables` pivot.
     */
    protected static array $defaultTags = [
        'NUEVO',
        'OFERTA',
        'REMATE',
        'IMPORTADOS',
        'DESTACADO',
        'LIQUIDACION',
        'ULTIMAS_UNIDADES',
    ];

    public function run(): void
    {
        foreach (self::$defaultTags as $index => $name) {
            Tag::firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'sort_order' => $index,
                ]
            );
        }
    }
}
