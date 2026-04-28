<?php

use App\Models\Product;
use Illuminate\Contracts\Console\Kernel;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

foreach (Product::orderByDesc('updated_at')->take(5)->get() as $p) {
    echo $p->id.': '.$p->image_url."\n";
}
