<?php

use App\Models\Product;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$p = Product::orderBy('id', 'desc')->first();
print_r($p->media);
