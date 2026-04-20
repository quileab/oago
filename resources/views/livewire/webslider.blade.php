<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    public function slides()
    {
        $disk = Storage::disk('public');
        $jsonPath = 'slider/slider.json';

        if (!$disk->exists($jsonPath)) {
            return [];
        }

        $data = json_decode($disk->get($jsonPath), true);
        $items = isset($data['slides']) ? $data['slides'] : $data;

        return collect($items)->map(function($item) {
            $path = is_array($item) ? $item['id'] : $item;
            return [
                'image' => asset('storage/' . $path),
                'title' => is_array($item) ? ($item['title'] ?? '') : '',
                'description' => is_array($item) ? ($item['description'] ?? '') : '',
                'url' => is_array($item) ? ($item['url'] ?? '') : '',
                'urlText' => is_array($item) ? ($item['urlText'] ?? '') : '',
            ];
        })->toArray();
    }

    public function config()
    {
        $disk = Storage::disk('public');
        $jsonPath = 'slider/slider.json';
        $defaultConfig = [
            'autoplay' => true,
            'interval' => 5000,
            'withoutArrows' => false,
            'withoutIndicators' => false
        ];

        if (!$disk->exists($jsonPath)) {
            return $defaultConfig;
        }

        $data = json_decode($disk->get($jsonPath), true);
        return array_merge($defaultConfig, $data['config'] ?? []);
    }

    public function with(): array
    {
        return [
            'slides' => $this->slides(),
            'config' => $this->config(),
        ];
    }
}; ?>

<div class="mb-4">
    @if(count($slides) > 0)
        <x-carousel 
            :slides="$slides" 
            :autoplay="$config['autoplay']" 
            :interval="$config['interval']" 
            :without-arrows="$config['withoutArrows']"
            :without-indicators="$config['withoutIndicators']"
            class="h-64 sm:h-80 md:h-96 lg:h-[450px] shadow-lg" 
        />
    @endif
</div>
