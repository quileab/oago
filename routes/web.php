<?php

use App\Enums\Role;
use App\Http\Controllers\ImageProxyController;
use App\Http\Controllers\Reports\ExportController;
use App\Models\AltOrder;
use App\Models\AltUser;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// route to web /
Route::get('/', function () {
    return view('index');
});

Route::get('/ordersuccess', function (Request $request) {
    return view('ordersuccess', [
        'orderId' => $request->order,
        'orderStatus' => $request->status,
        'isAlt' => $request->is_alt ?? false,
    ]);
})->name('ordersuccess');

Route::get('/proxy-image', [ImageProxyController::class, 'show'])->name('proxy.image');

// Route::get('/artisan/{command}', function ($command) {
//     return Artisan::call($command);
// });
Volt::route('/login', 'login')->name('login');
Volt::route('/about', 'about')->name('about');
Volt::route('/contact', 'contact')->name('contact');

// Activación de cuenta para usuarios alternativos (PÚBLICA)
Route::get('/activate-account/{token}', function ($token) {
    Log::info('INICIO ACTIVACIÓN: Token [' . $token . ']');

    $user = AltUser::where('activation_token', $token)->first();

    if (! $user) {
        Log::warning('ACTIVACIÓN FALLIDA: Token no encontrado.');
        return redirect('/login')->with('error', 'El enlace de activación es inválido o ha expirado.');
    }

    Log::info('USUARIO ENCONTRADO: ' . $user->email . ' [ID: ' . $user->id . '] [Rol actual: ' . ($user->role->value ?? $user->role) . ']');

    // Activación mediante forceFill para asegurar la persistencia
    $user->forceFill([
        'role' => Role::CUSTOMER,
        'activation_token' => null,
    ])->save();

    // Recarga para confirmar el cambio en logs
    $freshUser = AltUser::find($user->id);
    Log::info('ACTIVACIÓN COMPLETADA: Nuevo rol -> ' . ($freshUser->role->value ?? $freshUser->role));

    return redirect('/login')->with('success', '¡Cuenta activada con éxito! Ya podés ingresar con tus credenciales.');
})->name('activate.account');

Route::get('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    request()->session()->flush();
    request()->session()->forget('is_alt_login'); // Eliminar la bandera de alternativo

    return redirect('/');
});

Route::get('/register', function () {
    // return "<pre>Registration is disabled. Please contact the administrator.</pre>";
    return view('registration');
})->name('register');
// admin only routes
Route::middleware(['auth', 'check_guest'])->group(function () {

    Volt::route('/user/profile', 'users.profile');

    Volt::route('/orders', 'orders')->middleware('is_role:admin,customer,sales');
    Volt::route('/alt-orders', 'alt-orders')->middleware('is_role:admin,sales,customer')->name('alt-orders');
    Volt::route('/alt-order/{orderId}/edit', 'alt-orderitems');
    Volt::route('/order/{orderId}/edit', 'orderitems');

    // Print Routes
    Route::get('/order/{id}/print', function ($id) {
        $order = Order::with(['user', 'items.product'])->findOrFail($id);

        return view('order-print', ['order' => $order, 'isAlt' => false]);
    })->name('order.print');

    Route::get('/alt-order/{id}/print', function ($id) {
        $order = AltOrder::with(['user', 'items.product'])->findOrFail($id);

        return view('order-print', ['order' => $order, 'isAlt' => true]);
    })->name('alt-order.print');
    Volt::route('/users', 'users.index')->middleware('is_admin');
    Volt::route('/users/{id}/sales-assign', 'users.sales-assign')->middleware('is_admin'); // New route
    Volt::route('/user/{id?}', 'users.crud')->middleware('is_admin');
    Volt::route('/my-sales-agents', 'users.sales-assigned')->middleware('auth'); // New route for customers
    Volt::route('/alts', 'users.alts.index')->middleware('is_admin');

    // Rutas de prueba para páginas de error
    Route::get('/test-error/{code}', function ($code) {
        abort($code);
    })->middleware('auth');
    Volt::route('/alt/{id?}', 'users.alts.crud')->middleware('is_admin');
    Volt::route('/alt-users/create', 'users.alts.crud')->middleware('is_admin'); // New route for adding alt user
    Volt::route('/products', 'products.index')->middleware('is_admin');
    Volt::route('/products/extras', 'products.extras')->middleware('is_admin');
    Volt::route('/product/{id?}', 'products.crud')->middleware('is_admin');
    Volt::route('/product/details/{id?}', 'web-product-detail');
    Volt::route('/slider', 'slider')->middleware('is_admin');
    Volt::route('/dashboard', 'dashboard')->name('dashboard')->middleware('is_admin');
    Volt::route('/achievements', 'achievements.index');
    Volt::route('/achievement/create', 'achievements.crud');
    Volt::route('/achievement/{achievement}/edit', 'achievements.crud');
    Volt::route('/assign-achievement', 'assign-achievement');
    Volt::route('/settings', 'settings.crud')->middleware('is_admin');
    Volt::route('/logs', 'admin.logs')->middleware('is_admin');

    // Users will be redirected to this route if not logged in
    // Volt::route('/register', 'register')->middleware('is_admin');

    Volt::route('/checkout', 'checkout')->name('checkout');
    Route::get('/clear/{option?}', function ($option = null) {
        $logs = [];
        // if option is 'prod' then run composer install --optimize-autoloader --no-dev
        if ($option == 'prod') {
            $logs['Composer Install for PROD'] = Artisan::call('composer install --optimize-autoloader --no-dev');
        }

        $maintenance = ($option == 'cache') ? [
            'Flush' => 'cache:flush',
        ] : [
            // 'DebugBar'=>'debugbar:clear',
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
            } catch (Exception $e) {
                $logs[$key] = '❌'.$e->getMessage();
            }
        }

        return '<pre>'.print_r($logs, true).'</pre><hr />';
    })->middleware('is_admin');

    // using Reports/ExportController -> exportProducts with associated ListPrices
    Route::get('/export/products', [ExportController::class, 'exportProducts'])->middleware('is_admin');
    Route::get('/export/customers-products', [ExportController::class, 'exportCustomersProducts'])->middleware('is_admin');
    Route::get('/export/users-order-stats', [ExportController::class, 'exportUsersOrderStats'])->middleware('is_admin');
});
