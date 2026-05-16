<?php

use App\View\Components\ImageProxy;
use Illuminate\Support\Facades\Blade;

it('renders the image proxy component correctly', function () {
    $url = 'https://example.com/test.jpg';
    $view = $this->component(ImageProxy::class, ['url' => $url]);

    $view->assertSee('proxy-image?url='.urlencode($url), false);
    $view->assertSee('<img', false);
});

it('renders the image proxy component via blade directive', function () {
    $url = 'https://example.com/blade-test.jpg';
    $html = Blade::render('<x-image-proxy :url="$url" />', ['url' => $url]);

    expect($html)->toContain('proxy-image?url='.urlencode($url));
});
