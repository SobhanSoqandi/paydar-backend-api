<?php

// use App\Http\Controllers\Customer\CustomerController;
// use App\Http\Controllers\Owner\OwnerController;
// use App\Http\Controllers\Salon\SalonController;
// use App\Http\Controllers\Service\ServiceController;
// use App\Http\Controllers\Appointment\AppointmentController;
// use App\Http\Controllers\AppointmentService\AppointmentServiceController;
// use App\Http\Controllers\Auth\AuthController;
// use App\Http\Controllers\Discount\DiscountController;
// use App\Http\Controllers\Role\RoleController;
// // use App\Http\Controllers\Discount\DiscountController;
// // use App\Http\Controllers\DiscountUsage\DiscountUsageController;
// use App\Http\Controllers\Wallet\WalletController;
// use App\Http\Controllers\WalletTransaction\WalletTransactionController;
// use App\Http\Controllers\User\UserController;
// use Illuminate\Support\Facades\Route;


use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\Owner\OwnerController;
use App\Http\Controllers\Salon\SalonController;
use App\Http\Controllers\Service\ServiceController;
use App\Http\Controllers\Appointment\AppointmentController;
use App\Http\Controllers\AppointmentService\AppointmentServiceController;
use App\Http\Controllers\Auth\AuthController;
// use App\Http\Controllers\RoleController;
// use App\Http\Controllers\Discount\DiscountController;
use App\Http\Controllers\Role\RoleController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Wallet\WalletController;
use App\Http\Controllers\WalletTransaction\WalletTransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {

    Route::post('/login', [AuthController::class, 'login']);

    Route::post('/logout', [AuthController::class, 'logout'])
        ->middleware('auth.api');

    Route::post('/refresh', [AuthController::class, 'refresh']);

});

Route::prefix('roles')->group(function () {
    Route::post('', [RoleController::class, 'create']);
    Route::get('', [RoleController::class, 'index']);
    Route::get('/{role_id}', [RoleController::class, 'show']);
    Route::put('/{role_id}', [RoleController::class, 'update']);
    Route::delete('/{role_id}', [RoleController::class, 'delete']);
});

// Route::prefix('user')->group(function () {
//     Route::post('', [UserController::class, 'create']);
//     Route::get('', [UserController::class, 'index']);
//     Route::get('/me', [UserController::class, 'me'])->middleware('auth.api');
//     Route::put('', [UserController::class, 'update'])->middleware('auth.api');
//     Route::delete('', [UserController::class, 'delete'])->middleware('auth.api');
//     Route::get('/{user_id}', [UserController::class, 'show']);
// });

Route::prefix('user')->group(function () {
    Route::post('', [UserController::class, 'create']);
    Route::get('', [UserController::class, 'index']);

    Route::get('/me', [UserController::class, 'me'])
        ->middleware('auth.api');

    Route::patch('/update/me', [UserController::class, 'updateMe'])
        ->middleware('auth.api');

    Route::put('', [UserController::class, 'update'])
        ->middleware('auth.api');

    Route::delete('', [UserController::class, 'delete'])
        ->middleware('auth.api');

    Route::get('/{user_id}', [UserController::class, 'show']);
});

Route::prefix('customer')->group(function () {

    Route::post('', [CustomerController::class, 'create']);

    Route::get('', [CustomerController::class, 'index']);

    Route::get('/me', [CustomerController::class, 'me'])
        ->middleware('auth.api');

    Route::get('/appointments', [CustomerController::class, 'appointments'])
        ->middleware(['auth.api', 'role:owner']);

    Route::put('', [CustomerController::class, 'update'])
        ->middleware('auth.api');

    Route::delete('', [CustomerController::class, 'delete'])
        ->middleware('auth.api');

    Route::get('/{customer_id}', [CustomerController::class, 'show']);
});

Route::prefix('owner')->group(function () {
    Route::post('', [OwnerController::class, 'create']);
    Route::get('', [OwnerController::class, 'index']);
    Route::get('/{owner_id}', [OwnerController::class, 'show']);
    Route::put('/{owner_id}', [OwnerController::class, 'update']);
    Route::delete('/{owner_id}', [OwnerController::class, 'delete']);
});

Route::prefix('salon')->group(function () {

    Route::post('', [SalonController::class, 'create'])
        ->middleware(['auth.api', 'role:owner']);

    Route::get('/search', [SalonController::class, 'search'])
        ->middleware(['auth.api', 'role:owner']);

    Route::put('', [SalonController::class, 'update'])
        ->middleware(['auth.api', 'role:owner']);

    Route::get('/user_id', [SalonController::class, 'getByUserId'])
        ->middleware(['auth.api', 'role:owner']);

    Route::get('/{salon_id}/services', [SalonController::class, 'services']);
});

Route::prefix('services')->group(function () {

    Route::post('', [ServiceController::class, 'create'])
        ->middleware(['auth.api', 'role:owner']);

    Route::get('', [ServiceController::class, 'index']);

    Route::get('/{service_id}', [ServiceController::class, 'show']);

    Route::put('/{service_id}', [ServiceController::class, 'update']);

    Route::delete('/{service_id}', [ServiceController::class, 'delete']);
});

Route::prefix('appointments')->group(function () {

    Route::post('', [AppointmentController::class, 'create']);

    Route::get('', [AppointmentController::class, 'index']);

    Route::get('/me', [AppointmentController::class, 'me'])
        ->middleware('auth.api');

    Route::get('/search', [AppointmentController::class, 'search']);

    Route::put('/services', [AppointmentController::class, 'updateServices']);

    Route::post('/pay', [AppointmentController::class, 'pay']);

    Route::get('/{appointment_id}', [AppointmentController::class, 'show']);

    Route::put('/{appointment_id}', [AppointmentController::class, 'update']);

    Route::delete('/{appointment_id}', [AppointmentController::class, 'delete']);
});


Route::prefix('appointment_service')->group(function () {

    Route::post('', [AppointmentServiceController::class, 'create']);

    Route::get('', [AppointmentServiceController::class, 'index']);

    Route::get('/{id}', [AppointmentServiceController::class, 'show']);

    Route::put('/{id}', [AppointmentServiceController::class, 'update']);

    Route::delete('/{id}', [AppointmentServiceController::class, 'delete']);
});

// Route::prefix('discount')->group(function () {
//     Route::post('', [DiscountController::class, 'create']);
//     Route::get('', [DiscountController::class, 'index']);
//     Route::get('/me', [DiscountController::class, 'me']);
//     Route::get('/{discount_id}', [DiscountController::class, 'show']);
//     Route::put('/{discount_id}', [DiscountController::class, 'update']);
//     Route::delete('/{discount_id}', [DiscountController::class, 'delete']);
// });

// Route::prefix('discount-usage')->group(function () {
//     Route::post('', [DiscountUsageController::class, 'create']);
// });


Route::prefix('wallets')->group(function () {
    Route::post('', [WalletController::class, 'create']);
    Route::get('/customer/{customer_id}', [WalletController::class, 'customer']);
    Route::get('/{wallet_id}', [WalletController::class, 'show']);
    Route::put('/{wallet_id}', [WalletController::class, 'update']);
    Route::delete('/{wallet_id}', [WalletController::class, 'delete']);
});

Route::prefix('wallet-transactions')->group(function () {
    Route::post('', [WalletTransactionController::class, 'create']);
    Route::get('/customer', [WalletTransactionController::class, 'customer'])
        ->middleware(['auth.api', 'role:customer']);
});

