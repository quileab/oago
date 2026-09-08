<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class SliderService
{
    public function getSlides(): array
    {
        $disk = Storage::disk('slider_public');
        if (! $disk->exists('slider.json')) {
            $fallback = public_path('storage/slider/slider.json');
            if (! file_exists($fallback)) {
                return [];
            }
            $data = json_decode(file_get_contents($fallback), true) ?? [];
            $items = $data['slides'] ?? $data;
            if (! is_array($items)) {
                return [];
            }

            return collect($items)->map(fn ($item) => $this->mapItem($item, 'storage'))->toArray();
        }

        $data = json_decode($disk->get('slider.json'), true) ?? [];
        $items = $data['slides'] ?? $data;
        if (! is_array($items)) {
            return [];
        }

        return collect($items)->map(fn ($item) => $this->mapItem($item, 'slider_public'))->toArray();
    }

    private function mapItem(mixed $item, string $source): array
    {
        $path = is_array($item) ? $item['id'] : $item;
        $cleanPath = ltrim($path, '/');

        if ($source === 'storage') {
            if (str_starts_with($cleanPath, 'slider/')) {
                $imageUrl = asset('storage/'.$cleanPath);
            } else {
                $imageUrl = asset('storage/slider/'.$cleanPath);
            }
        } else {
            $imageUrl = asset('imgs/slider/'.$path);
        }

        return [
            'image' => $imageUrl,
            'title' => is_array($item) ? ($item['title'] ?? '') : '',
            'description' => is_array($item) ? ($item['description'] ?? '') : '',
            'url' => is_array($item) ? ($item['url'] ?? '') : '',
            'urlText' => is_array($item) ? ($item['urlText'] ?? '') : '',
        ];
    }
}
