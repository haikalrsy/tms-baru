<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\AccountController;

use App\Http\Controllers\Dashboard\DashboardController;

use App\Http\Controllers\TMS\DeliveryOrderController;
use App\Http\Controllers\TMS\DeliveryTrackingController;
use App\Http\Controllers\TMS\ProofOfDeliveryController;
use App\Http\Controllers\TMS\DeliveryNoteController;

use App\Http\Controllers\WMS\WarehouseController;
use App\Http\Controllers\WMS\GoodsReceiptController;
use App\Http\Controllers\WMS\StockController;
use App\Http\Controllers\WMS\PickListController;

use App\Http\Controllers\Integration\SyncController;
use App\Http\Controllers\Integration\SyncSalesOrderController;
use App\Http\Controllers\Integration\SyncCustomerController;
use App\Http\Controllers\Integration\SyncItemController;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->middleware('throttle:5,1')->group(function () {
    Route::post('login',               [AuthController::class, 'login'])->middleware('brute.force');
    Route::post('register',            [AuthController::class, 'register']);
    Route::post('resend-verification', [AuthController::class, 'resendVerification']);
    Route::get('google',               [GoogleAuthController::class, 'redirect']);
});

/*
|--------------------------------------------------------------------------
| INTEGRATION ROUTES (API KEY)
|--------------------------------------------------------------------------
*/
Route::prefix('integration')->middleware('integration.auth')->group(function () {
    Route::post('sync/sales-orders', [SyncSalesOrderController::class, 'sync']);
    Route::post('sync/customers',    [SyncCustomerController::class, 'sync']);
    Route::post('sync/items',        [SyncItemController::class, 'sync']);

    // Alternative unified controller
    Route::post('sync2/sales-orders', [SyncController::class, 'syncSalesOrders']);
    Route::post('sync2/customers',    [SyncController::class, 'syncCustomers']);
    Route::post('sync2/items',        [SyncController::class, 'syncItems']);
});

/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware([
    'auth:sanctum',
    'account.status',
    'throttle:api'
])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | AUTH
    |--------------------------------------------------------------------------
    */
    Route::prefix('auth')->group(function () {
        Route::get('me',               [AuthController::class, 'me']);
        Route::post('logout',          [AuthController::class, 'logout']);
        Route::put('fcm-token',        [AuthController::class, 'updateFcmToken']);
        Route::post('google/complete', [GoogleAuthController::class, 'completeRegistration']);
    });

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD (ADMIN)
    |--------------------------------------------------------------------------
    */
    Route::prefix('dashboard')->middleware('role:admin')->group(function () {
        Route::get('summary',          [DashboardController::class, 'summary']);
        Route::get('driver-locations', [DashboardController::class, 'driverLocations']);
    });

    /*
    |--------------------------------------------------------------------------
    | ADMIN ACCOUNT MANAGEMENT
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('accounts',                    [AccountController::class, 'index']);
        Route::post('accounts',                   [AccountController::class, 'store']);
        Route::post('accounts/{user}/approve',    [AccountController::class, 'approve']);
        Route::post('accounts/{user}/reject',     [AccountController::class, 'reject']);
        Route::post('accounts/{user}/suspend',    [AccountController::class, 'suspend']);
        Route::post('accounts/{user}/reactivate', [AccountController::class, 'reactivate']);
    });

    /*
    |--------------------------------------------------------------------------
    | TMS
    |--------------------------------------------------------------------------
    */
    Route::prefix('tms')->group(function () {

        // Delivery Orders
        Route::get('delivery-orders',                              [DeliveryOrderController::class, 'index']);
        Route::post('delivery-orders',                             [DeliveryOrderController::class, 'store'])->middleware('role:admin');
        Route::get('delivery-orders/{deliveryOrder}',              [DeliveryOrderController::class, 'show']);
        Route::post('delivery-orders/{deliveryOrder}/assign',      [DeliveryOrderController::class, 'assign'])->middleware('role:admin');
        Route::patch('delivery-orders/{deliveryOrder}/status',     [DeliveryOrderController::class, 'updateStatus']);
        Route::get('delivery-orders/{deliveryOrder}/pod',          [DeliveryOrderController::class, 'getPod']);

        // Tracking
        Route::post('delivery-orders/{deliveryOrder}/tracking',    [DeliveryTrackingController::class, 'update'])->middleware('role:driver');
        Route::get('delivery-orders/{deliveryOrder}/tracking',     [DeliveryTrackingController::class, 'history']);

        // POD
        Route::post('delivery-orders/{deliveryOrder}/pod',         [ProofOfDeliveryController::class, 'store'])->middleware('role:driver');
        Route::patch('delivery-orders/{deliveryOrder}/pod/verify', [ProofOfDeliveryController::class, 'verify'])->middleware('role:admin');

        // Delivery Notes
        Route::get('delivery-notes',                       [DeliveryNoteController::class, 'index']);
        Route::post('delivery-notes',                      [DeliveryNoteController::class, 'store']);
        Route::get('delivery-notes/{deliveryNote}',        [DeliveryNoteController::class, 'show']);
        Route::put('delivery-notes/{deliveryNote}',        [DeliveryNoteController::class, 'update']);
        Route::delete('delivery-notes/{deliveryNote}',     [DeliveryNoteController::class, 'destroy']);
        Route::post('delivery-notes/{deliveryNote}/issue', [DeliveryNoteController::class, 'issue'])->middleware('role:admin');
        Route::get('delivery-notes/{deliveryNote}/print',  [DeliveryNoteController::class, 'print']);
    });

    /*
    |--------------------------------------------------------------------------
    | WMS
    |--------------------------------------------------------------------------
    */
    Route::prefix('wms')->group(function () {

        // Warehouses
        Route::get('warehouses',                    [WarehouseController::class, 'index']);
        Route::post('warehouses',                   [WarehouseController::class, 'store'])->middleware('role:admin');
        Route::get('warehouses/{warehouse}',        [WarehouseController::class, 'show']);
        Route::put('warehouses/{warehouse}',        [WarehouseController::class, 'update'])->middleware('role:admin');
        Route::delete('warehouses/{warehouse}',     [WarehouseController::class, 'destroy'])->middleware('role:admin');
        Route::get('warehouses/{warehouse}/zones',  [WarehouseController::class, 'zones']);
        Route::get('warehouses/{warehouse}/stocks', [WarehouseController::class, 'stocks']);

        // Goods Receipts
        Route::get('goods-receipts',                         [GoodsReceiptController::class, 'index']);
        Route::post('goods-receipts',                        [GoodsReceiptController::class, 'store']);
        Route::get('goods-receipts/{goodsReceipt}',          [GoodsReceiptController::class, 'show']);
        Route::post('goods-receipts/{goodsReceipt}/receive', [GoodsReceiptController::class, 'receive']);
        Route::post('goods-receipts/{goodsReceipt}/putaway', [GoodsReceiptController::class, 'putaway']);

        // Stocks
        Route::get('stocks',           [StockController::class, 'index']);
        Route::get('stocks/summary',   [StockController::class, 'summary']);
        Route::get('stocks/low-stock', [StockController::class, 'lowStock']);
        Route::get('stocks/movements', [StockController::class, 'movements']);
        Route::post('stocks/adjust',   [StockController::class, 'adjust'])->middleware('role:admin');

        // Pick Lists
        Route::get('pick-lists',                      [PickListController::class, 'index']);
        Route::post('pick-lists',                     [PickListController::class, 'store']);
        Route::get('pick-lists/{pickList}',           [PickListController::class, 'show']);
        Route::post('pick-lists/{pickList}/complete', [PickListController::class, 'complete']);
    });
});