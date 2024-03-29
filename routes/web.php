<?php

use App\Http\Controllers\SandboxController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::resource('supplier', SupplierController::class);
Route::get('supplier/{supplier}/pull', [SupplierController::class, 'pull']);
Route::get('sandbox', [SandboxController::class, 'index']);
