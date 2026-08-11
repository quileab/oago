<?php

namespace App\Http\Requests\Api;

use App\Enums\Role;
use App\Models\Tag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

/**
 * @property-read int|null $id
 * @property-read string|null $barcode
 * @property-read string|null $sku
 * @property-read string|null $product_type
 * @property-read string|null $brand
 * @property-read string|null $model
 * @property-read string|null $category
 * @property-read string $description
 * @property-read string|null $description_html
 * @property-read bool $published
 * @property-read bool $featured
 * @property-read string $visibility
 * @property-read string|null $offer_start
 * @property-read string|null $offer_end
 * @property-read string $tax_status
 * @property-read bool $in_stock
 * @property-read int $stock
 * @property-read bool $allow_reservation
 * @property-read int $qtty_package
 * @property-read int $qtty_unit
 * @property-read bool|null $by_bulk
 * @property-read float|null $weight
 * @property-read float|null $lenght
 * @property-read float|null $width
 * @property-read float|null $height
 * @property-read float|null $price
 * @property-read float|null $offer_price
 * @property-read string|null $tags
 * @property-read string|null $image_url
 */
class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role->value === Role::ADMIN->value;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('qtty_package') && (int) ($this->input('qtty_package') ?? 0) < 1) {
            $this->merge(['qtty_package' => 1]);
            Log::channel('api')->info('qtty_package corregido a 1 (SKU: '.($this->input('sku') ?? 'N/A').')');
        }

        if ($this->has('price') && is_null($this->input('price'))) {
            $this->merge(['price' => 0]);
        }

        $tags = $this->input('tags');

        if ($tags === null || $tags === '') {
            $this->merge(['tags' => '']);
        } elseif (is_array($tags)) {
            $this->merge(['tags' => $this->normalizeTagArray($tags)]);
        } elseif (is_string($tags)) {
            $this->merge(['tags' => $this->normalizeTagString($tags)]);
        }
    }

    public function rules(): array
    {
        return [
            'id' => 'nullable|integer',
            'barcode' => 'nullable|string|max:50',
            'sku' => 'nullable|string|max:50',
            'product_type' => 'nullable|string|max:30',
            'brand' => 'nullable|string|max:30',
            'model' => 'nullable|string|max:130',
            'category' => 'nullable|string|max:50',
            'description' => 'required|string|max:100',
            'description_html' => 'nullable|string|max:250',
            'published' => 'required|boolean',
            'featured' => 'required|boolean',
            'visibility' => 'required|string|max:10',
            'offer_start' => 'nullable|date',
            'offer_end' => 'nullable|date',
            'tax_status' => 'required|string|max:10',
            'in_stock' => 'required|boolean',
            'stock' => 'required|integer|min:0',
            'allow_reservation' => 'required|boolean',
            'qtty_package' => 'required|integer|min:1',
            'qtty_unit' => 'required|integer|min:1',
            'by_bulk' => 'boolean',
            'weight' => 'nullable|numeric|min:0',
            'lenght' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'price' => 'nullable|numeric|min:0',
            'offer_price' => 'nullable|numeric|min:0',
            'tags' => 'nullable|string',
            'image_url' => 'nullable|string|max:250',
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => 'La descripción es obligatoria.',
            'published.required' => 'El estado de publicación es obligatorio.',
            'featured.required' => 'El estado destacado es obligatorio.',
            'visibility.required' => 'La visibilidad es obligatoria.',
            'tax_status.required' => 'El estado de impuesto es obligatorio.',
            'in_stock.required' => 'El estado de stock es obligatorio.',
            'stock.required' => 'La cantidad en stock es obligatoria.',
            'allow_reservation.required' => 'Debe indicar si se permite reserva.',
            'qtty_package.required' => 'La cantidad por paquete es obligatoria.',
            'qtty_unit.required' => 'La cantidad unitaria es obligatoria.',
        ];
    }

    private function normalizeTagArray(array $tags): string
    {
        $names = [];
        foreach ($tags as $value) {
            if (is_numeric($value)) {
                $tag = Tag::find($value);
                if ($tag) {
                    $names[] = $tag->name;
                }
            } else {
                $names[] = (string) $value;
            }
        }

        return $this->buildTagsString($names);
    }

    private function normalizeTagString(string $tags): string
    {
        $delimiter = str_contains($tags, '|') ? '|' : ',';
        $tagNames = array_values(array_filter(array_map('trim', explode($delimiter, $tags))));

        return $this->buildTagsString($tagNames);
    }

    private function buildTagsString(array $names): string
    {
        $names = array_map('strtoupper', $names);
        $names = array_values(array_unique($names));
        sort($names);

        return implode('|', $names);
    }

    public static function getTagSlugs(): array
    {
        return Tag::allCached()->pluck('slug')->all();
    }
}
