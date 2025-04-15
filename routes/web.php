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

// Route::get('/artisan/{command}', function ($command) {
//     return Artisan::call($command);
// });
Volt::route('/login', 'login')->name('login');

Route::get('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    request()->session()->flush();
    return redirect('/');
});
// admin only routes
Route::middleware('auth')->group(function () {

    Volt::route('/orders', 'orders');
    Volt::route('/order/{orderId}/edit', 'orderitems');

    Volt::route('/users', 'users.index')->middleware('is_admin');
    Volt::route('/user/{id?}', 'users.crud')->middleware('is_admin');
    Volt::route('/products', 'products.index')->middleware('is_admin');
    Volt::route('/product/{id?}', 'products.crud')->middleware('is_admin');
    // Users will be redirected to this route if not logged in
    Volt::route('/register', 'register')->middleware('is_admin');
    Route::get('/clear/{option?}', function ($option = null) {
        $logs = [];
        // if option is 'prod' then run composer install --optimize-autoloader --no-dev
        if ($option == 'prod') {
            $logs['Composer Install for PROD'] = Artisan::call('composer install --optimize-autoloader --no-dev');
        }

        $maintenance = ($option == "cache") ? [
            'Flush' => 'cache:flush',
        ] : [
            //'DebugBar'=>'debugbar:clear',
            'Storage Link' => 'storage:link',
            'Config' => 'config:clear',
            'Optimize Clear' => 'optimize:clear',
            'Optimize' => 'optimize',
            'Route Clear' => 'route:clear',
            'Route Cache' => 'route:cache',
            'View Clear' => 'view:clear',
            'View Cache' => 'view:cache',
            'Cache Clear' => 'cache:clear',
            'Cache Config' => 'config:cache',
        ];

        foreach ($maintenance as $key => $value) {
            try {
                Artisan::call($value);
                $logs[$key] = '✔️';
            } catch (\Exception $e) {
                $logs[$key] = '❌' . $e->getMessage();
            }
        }
        return "<pre>" . print_r($logs, true) . "</pre><hr />";
    });

    // using Reports/ExportController -> exportProducts with associated ListPrices
    Route::get('/export/products', [\App\Http\Controllers\Reports\ExportController::class, 'exportProducts'])->middleware('is_admin');
});
