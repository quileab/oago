<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    public function images()
    {
        $files = Storage::disk('public')->files('slider');

        // Sort files by their numerical index if they follow the 'slide (X).jpg' pattern
        usort($files, function ($a, $b) {
            preg_match('/slide \((\d+)\)\./', basename($a), $matchesA);
            preg_match('/slide \((\d+)\)\./', basename($b), $matchesB);

            $numA = isset($matchesA[1]) ? (int) $matchesA[1] : PHP_INT_MAX;
            $numB = isset($matchesB[1]) ? (int) $matchesB[1] : PHP_INT_MAX;

            return $numA <=> $numB;
        });

        return collect($files)->map(fn($file) => asset('storage/' . $file));
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