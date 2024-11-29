<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FinancialRecordController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/financial-records', [FinancialRecordController::class, 'index']);
Route::post('/financial-records', [FinancialRecordController::class, 'store']);
Route::put('/financial-records/{id}', [FinancialRecordController::class, 'update']);
Route::delete('/financial-records/{id}', [FinancialRecordController::class, 'destroy']);
Route::post('/financial-records/delete-multiple', [FinancialRecordController::class, 'deleteMultiple']);
