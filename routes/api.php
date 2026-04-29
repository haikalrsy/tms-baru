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
use App\Http\Controllers\WMS\SalesOrderController;
use App\Http\Controllers\WMS\TransferStockController;
use App\Http\Controllers\WMS\DriverController;

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
        Route::post('accounts/approve-all',       [AccountController::class, 'approveAll']);
        Route::post('accounts/{user}/approve',    [AccountController::class, 'approve']);
        Route::post('accounts/{user}/reject',     [AccountController::class, 'reject']);
        Route::post('accounts/{user}/suspend',    [AccountController::class, 'suspend']);
        Route::post('accounts/{user}/reactivate', [AccountController::class, 'reactivate']);

        Route::get('drivers/online', [DriverController::class, 'onlineDrivers']);
    });

    /*
    |--------------------------------------------------------------------------
    | TMS
    |--------------------------------------------------------------------------
    */
    Route::prefix('tms')->group(function () {

        Route::get('delivery-orders',                              [DeliveryOrderController::class, 'index']);
        Route::post('delivery-orders',                             [DeliveryOrderController::class, 'store'])->middleware('role:admin');
        Route::get('delivery-orders/{deliveryOrder}',              [DeliveryOrderController::class, 'show']);
        Route::post('delivery-orders/{deliveryOrder}/assign',      [DeliveryOrderController::class, 'assign'])->middleware('role:admin');
        Route::patch('delivery-orders/{deliveryOrder}/status',     [DeliveryOrderController::class, 'updateStatus']);
        Route::get('delivery-orders/{deliveryOrder}/pod',          [DeliveryOrderController::class, 'getPod']);

        Route::post('delivery-orders/{deliveryOrder}/tracking',    [DeliveryTrackingController::class, 'update'])->middleware('role:driver');
        Route::get('delivery-orders/{deliveryOrder}/tracking',     [DeliveryTrackingController::class, 'history']);

        Route::post('delivery-orders/{deliveryOrder}/pod',         [ProofOfDeliveryController::class, 'store'])->middleware('role:driver');
        Route::patch('delivery-orders/{deliveryOrder}/pod/verify', [ProofOfDeliveryController::class, 'verify'])->middleware('role:admin');

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

        Route::get('warehouses',                    [WarehouseController::class, 'index']);
        Route::post('warehouses',                   [WarehouseController::class, 'store'])->middleware('role:admin');
        Route::get('warehouses/{warehouse}',        [WarehouseController::class, 'show']);
        Route::put('warehouses/{warehouse}',        [WarehouseController::class, 'update'])->middleware('role:admin');
        Route::delete('warehouses/{warehouse}',     [WarehouseController::class, 'destroy'])->middleware('role:admin');
        Route::get('warehouses/{warehouse}/zones',  [WarehouseController::class, 'zones']);
        Route::get('warehouses/{warehouse}/stocks', [WarehouseController::class, 'stocks']);

        Route::get('goods-receipts',                         [GoodsReceiptController::class, 'index']);
        Route::post('goods-receipts',                        [GoodsReceiptController::class, 'store']);
        Route::get('goods-receipts/{goodsReceipt}',          [GoodsReceiptController::class, 'show']);
        Route::post('goods-receipts/{goodsReceipt}/receive', [GoodsReceiptController::class, 'receive']);
        Route::post('goods-receipts/{goodsReceipt}/putaway', [GoodsReceiptController::class, 'putaway']);

        Route::get('stocks',           [StockController::class, 'index']);
        Route::get('stocks/summary',   [StockController::class, 'summary']);
        Route::get('stocks/low-stock', [StockController::class, 'lowStock']);
        Route::get('stocks/movements', [StockController::class, 'movements']);

        Route::put('stocks/{stock}',          [StockController::class, 'update'])->middleware('role:admin');
        Route::patch('stocks/{stock}/adjust', [StockController::class, 'adjust'])->middleware('role:admin');
        Route::delete('stocks/{stock}',       [StockController::class, 'destroy'])->middleware('role:admin');

        Route::get('warehouses/{warehouse}/stocks',  [StockController::class, 'byWarehouse']);
        Route::post('warehouses/{warehouse}/stocks', [StockController::class, 'store'])->middleware('role:admin');

        Route::get('pick-lists',                      [PickListController::class, 'index']);
        Route::post('pick-lists',                     [PickListController::class, 'store']);
        Route::get('pick-lists/{pickList}',           [PickListController::class, 'show']);
        Route::post('pick-lists/{pickList}/complete', [PickListController::class, 'complete']);

        Route::get('sales-orders',                       [SalesOrderController::class, 'index']);
        Route::post('sales-orders',                      [SalesOrderController::class, 'store'])->middleware('role:admin');
        Route::get('sales-orders/{salesOrder}',          [SalesOrderController::class, 'show']);
        Route::put('sales-orders/{salesOrder}',          [SalesOrderController::class, 'update'])->middleware('role:admin');
        Route::delete('sales-orders/{salesOrder}',       [SalesOrderController::class, 'destroy'])->middleware('role:admin');
        Route::post('sales-orders/{salesOrder}/confirm', [SalesOrderController::class, 'confirm'])->middleware('role:admin');

        Route::get('transfer-stocks',                          [TransferStockController::class, 'index']);
        Route::post('transfer-stocks',                         [TransferStockController::class, 'store'])->middleware('role:admin');
        Route::get('transfer-stocks/{transferStock}',          [TransferStockController::class, 'show']);
        Route::patch('transfer-stocks/{transferStock}/status', [TransferStockController::class, 'updateStatus']);

        // BARU: Admin approve put away
        Route::post('transfer-stocks/{transferStock}/approve-putaway', [TransferStockController::class, 'approvePutAway'])->middleware('role:admin');

        Route::get('map/data', [TransferStockController::class, 'mapData']);
    });

    /*
    |--------------------------------------------------------------------------
    | DRIVER (self-service routes)
    |--------------------------------------------------------------------------
    */
    Route::prefix('driver')->middleware('role:driver')->group(function () {
        Route::get('status',   [DriverController::class, 'status']);
        Route::put('status',   [DriverController::class, 'updateStatus']);
        Route::put('location', [DriverController::class, 'updateLocation']);

        Route::get('transfers/pending',       [DriverController::class, 'pendingTransfers']);
        Route::get('transfers',               [DriverController::class, 'transfers']);
        Route::get('transfers/{id}',          [DriverController::class, 'showTransfer']);
        Route::post('transfers/{id}/confirm', [DriverController::class, 'confirmTransfer']);
        Route::post('transfers/{id}/reject',  [DriverController::class, 'rejectTransfer']);
        Route::post('transfers/{id}/deliver', [DriverController::class, 'deliverTransfer']);

        // BARU: Driver advance status (picking → packing → on_the_way → put_away)
        Route::post('transfers/{id}/status',  [DriverController::class, 'updateTransferStatus']);
    });
});