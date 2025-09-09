<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\NewChunksController;
use App\Http\Controllers\Api\QuranController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\ReviewPlanController;
use App\Http\Controllers\Api\StudentMemorizationController;
use App\Http\Controllers\Api\ReviewPlanExportController;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:sanctum')->group(function () {

    Route::get('/memorization', [StudentMemorizationController::class, 'index']);
    Route::post('/memorization', [StudentMemorizationController::class, 'store']);
    Route::get('/student-memorization', [StudentMemorizationController::class, 'fetchForReview']);
    Route::get('/review-chunks', [StudentMemorizationController::class, 'reviewChunks']);
    Route::get('/student-memorization/jozo-count', [StudentMemorizationController::class, 'memorizedJozosCount']);
    Route::get('/student-memorization/chunks', [StudentMemorizationController::class, 'getMemorizationChunks']);
    Route::post('/student-memorization/update', [StudentMemorizationController::class, 'updateStudentMemorization']);
    Route::get('/student-memorization/jozos', [StudentMemorizationController::class, 'listJozos']);
    Route::get('/student-memorization/jozo/{jozo}/hizbs', [StudentMemorizationController::class, 'getHizbsInJozo']);
    Route::get('/student-memorization/hizb/{hizb}/qrtrs', [StudentMemorizationController::class, 'getQrtrsInHizb']);



    Route::post('/review-plans', [ReviewPlanController::class, 'store']);
    Route::get('/review-plans/view', [ReviewPlanController::class, 'view']);

    Route::get('/new-chunks', [NewChunksController::class, 'index']);

    Route::get('/unmemorized-surahs', [NewChunksController::class, 'listUnmemorizedSurahs']);
    Route::get('/unmemorized-surahs/{sora}/pages', [NewChunksController::class, 'getSurahPages']);

    Route::get('/chunk-verses', [QuranController::class, 'versesInRange']);
    Route::get('/verses/qrtr/{qrtr}', [QuranController::class, 'getByQuarter']);
    Route::get('/verses/surah/{sora}', [QuranController::class, 'getBySurah']);
    Route::get('/verses/hizb/{hizb}', [QuranController::class, 'getByHizb']);

    Route::delete('/review-plans', [ReviewPlanController::class, 'destroy']);

});

    Route::get('/review-plans/export/excel', [ReviewPlanExportController::class, 'exportExcel']);
    Route::get('/review-plans/export/pdf', [ReviewPlanExportController::class, 'exportPdf']);

    Route::post('/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->get('/user', [AuthController::class, 'me']);

