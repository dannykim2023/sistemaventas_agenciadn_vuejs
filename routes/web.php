<?php
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\{
    CustomerController,
    ProductController,
    QuotationController,
    SaleController,       // 👈 añade SaleController aquí, arriba
};
use App\Http\Controllers\DashboardController;


Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';

Route::middleware(['auth','verified'])->group(function () {
    Route::resource('customers', CustomerController::class)->only(['index','store','update','destroy']);
    Route::resource('products', ProductController::class)->only(['index','store','update','destroy']);
    Route::resource('quotations', QuotationController::class); // index, create, store, show, update, destroy

    // ✅ PON AQUÍ LAS RUTAS DE VENTAS PARA QUE REQUIERAN LOGIN
    Route::resource('sales', SaleController::class);
});

Route::get('/quotations/{quotation}/pdf', [QuotationController::class, 'pdf'])
    ->name('quotations.pdf');


Route::post('/sales/{sale}/payments', [PaymentController::class, 'store'])
    ->name('sales.payments.store');

Route::delete('/sales/{sale}/payments/{payment}', [PaymentController::class, 'destroy'])
    ->name('sales.payments.destroy');


Route::resource('payments', PaymentController::class)->except(['show']);



Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');


Route::get('/sales/{sale}/pdf', [SaleController::class, 'pdf'])
    ->name('sales.pdf');

    
Route::resource('sales', SaleController::class);
