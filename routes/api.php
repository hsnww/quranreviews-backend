<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\NewChunksController;
use App\Http\Controllers\Api\QuranController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\ReviewPlanController;
use App\Http\Controllers\Api\StudentMemorizationController;
use App\Http\Controllers\Api\ReviewPlanExportController;
use App\Http\Controllers\Api\QuizSessionController;
use App\Http\Controllers\Api\RecitationPlannerController;
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

    Route::get('/quiz-sessions', [QuizSessionController::class, 'index']);
    Route::post('/quiz-sessions', [QuizSessionController::class, 'store'])
        ->middleware('throttle:quiz-session-create');
    Route::get('/quiz-sessions/{quiz_session}', [QuizSessionController::class, 'show']);
    Route::patch('/quiz-sessions/{quiz_session}/cards/{quiz_session_card}', [QuizSessionController::class, 'updateCard']);
    Route::post('/quiz-sessions/{quiz_session}/complete', [QuizSessionController::class, 'complete']);
    Route::delete('/quiz-sessions/{quiz_session}', [QuizSessionController::class, 'destroy']);

    Route::get('/recitation/plans', [RecitationPlannerController::class, 'indexPlans']);
    Route::post('/recitation/plans', [RecitationPlannerController::class, 'storePlan']);
    Route::get('/recitation/plans/{plan}', [RecitationPlannerController::class, 'showPlan']);
    Route::delete('/recitation/plans/{plan}', [RecitationPlannerController::class, 'destroyPlan']);
    Route::post('/recitation/plans/{plan}/sessions', [RecitationPlannerController::class, 'upsertSessions']);
    Route::post('/recitation/sessions/{session}/segments', [RecitationPlannerController::class, 'storeSegment']);
    Route::patch('/recitation/segments/{segment}', [RecitationPlannerController::class, 'updateSegment']);
    Route::delete('/recitation/segments/{segment}', [RecitationPlannerController::class, 'destroySegment']);
    Route::post('/recitation/sessions/{session}/complete', [RecitationPlannerController::class, 'completeSession']);
    Route::get('/recitation/archive', [RecitationPlannerController::class, 'archive']);
    Route::get('/recitation/ayah-status', [RecitationPlannerController::class, 'ayahStatus']);

});

    Route::get('/review-plans/export/excel', [ReviewPlanExportController::class, 'exportExcel']);
    Route::get('/review-plans/export/pdf', [ReviewPlanExportController::class, 'exportPdf']);

    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::middleware('auth:sanctum')->get('/user', [AuthController::class, 'me']);
    Route::middleware('auth:sanctum')->put('/user', [AuthController::class, 'updateUser']);
    Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

