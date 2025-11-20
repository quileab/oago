<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    public function images()
    {
        $disk = Storage::disk('public');
        $jsonPath = 'slider/slider.json';

        if (!$disk->exists($jsonPath)) {
            return collect();
        }

        $imagePaths = json_decode($disk->get($jsonPath), true);

        return collect($imagePaths)->map(fn($file) => asset('storage/' . $file));
    }

    public function with(): array
    {
        return [
            'images' => $this->images(),
        ];
    }
}; ?>

<div>
    <div id='slider' class="z-0 transition-all duration-500" style="height: 0px;">
        @foreach($images as $image)
            <img src="{{ $image }}" loading="lazy" />
        @endforeach
    </div>
</div>
