<?php

use Livewire\Volt\Volt;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
// route to web /
Route::get('/', function () {
  return view('index');
});

Route::get('/ordersuccess', function () {
  return view('ordersuccess');
})->name('ordersuccess');


Volt::route('/users', 'users.index');
Volt::route('/products', 'products.index');
Volt::route('/orders', 'orders');
// Users will be redirected to this route if not logged in
Volt::route('/login', 'login')->name('login');
Volt::route('/register', 'register'); 

Volt::route('/order/{orderId}/edit', 'orderitems');

// Route::get('/artisan/{command}', function ($command) {
//     return Artisan::call($command);
// });

Route::get('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
});

Route::get('/clear/{option?}', function ($option = null) {
    
    $logs = [];
    $maintenance = ($option == "cache") ? [
        'Flush' => 'cache:flush',
    ] : [
        //'DebugBar'=>'debugbar:clear',
        'Storage Link'=>'storage:link',
        'Config' => 'config:clear',
        'Optimize Clear' => 'optimize:clear',
        //'Optimize'=>'optimize',
        'Route Clear' => 'route:clear',
        'Cache' => 'cache:clear',
    ];

    foreach ($maintenance as $key => $value) {
        try {
            Artisan::call($value);
            $logs[$key]='✔️';
        } catch (\Exception $e) {
            $logs[$key]='❌'.$e->getMessage();
        }
    }
    return "<pre>".print_r($logs,true)."</pre><hr />";
    //    return var_dump($maintenance,true);
    //.Artisan::output();
});
