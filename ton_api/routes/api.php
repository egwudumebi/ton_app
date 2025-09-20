<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\GameController;
use App\Http\Controllers\API\WalletController;
use App\Http\Controllers\API\LeaderboardController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\API\ConfigController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Test route
Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is working!',
        'timestamp' => now()
    ]);
});

// Health check route
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is healthy',
        'status' => 'online',
        'timestamp' => now(),
        'version' => '1.0.0'
    ]);
});

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/config', [ConfigController::class, 'getConfig']);

// Public advertisements (Sponsored) endpoints
Route::get('/advertisements', function () {
    $ads = DB::table('advertisements')
        ->where('is_active', 1)
        ->orderByDesc('created_at')
        ->get(['id', 'title', 'description', 'media_url', 'is_active']);
    return response()->json(['advertisements' => $ads]);
});

// Optional: best-effort ad analytics endpoint
Route::post('/ads/events', function (\Illuminate\Http\Request $request) {
    // You can persist events later; for now, accept and return ok
    return response()->json(['status' => 'ok']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/profile', [AuthController::class, 'profile']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);

    // Game routes
    Route::post('/games/start', [GameController::class, 'startGame']);
    Route::post('/games/complete', [GameController::class, 'completeGame']);
    Route::get('/games/stats', [GameController::class, 'getStats']);
    Route::get('/games/sessions', [GameController::class, 'getRecentSessions']);
    Route::get('/games/config', [GameController::class, 'getGameConfig']);

    // Wallet routes
    Route::get('/wallet', [WalletController::class, 'getWallet']);
    Route::put('/wallet/update', [WalletController::class, 'updateWallet']);
    Route::put('/wallet/ton-address', [WalletController::class, 'updateTonAddress']);
    Route::get('/wallet/transactions', [WalletController::class, 'getTransactionHistory']);
    Route::get('/wallet/stats', [WalletController::class, 'getWalletStats']);
    Route::get('/wallet/bonuses', [WalletController::class, 'getAvailableBonuses']);
    Route::post('/wallet/bonuses/claim', [WalletController::class, 'claimBonus']);

    // Leaderboard routes
    Route::get('/leaderboard', [LeaderboardController::class, 'getLeaderboard']);
    Route::get('/leaderboard/position', [LeaderboardController::class, 'getUserPosition']);
    Route::get('/leaderboard/global-stats', [LeaderboardController::class, 'getGlobalStats']);
    Route::get('/leaderboard/top-players', [LeaderboardController::class, 'getTopPlayers']);

    // Admin routes (protected by auth middleware)
    Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
        Route::get('/users', [AdminController::class, 'getUsers']);
        Route::get('/users/search', [AdminController::class, 'searchUsers']);
        Route::get('/users/{id}', [AdminController::class, 'getUserDetails']);
        Route::put('/users/{id}', [AdminController::class, 'updateUser']);
        Route::post('/users/{id}/deactivate', [AdminController::class, 'deactivateUser']);
        Route::post('/users/{id}/activate', [AdminController::class, 'activateUser']);
        Route::post('/users/{id}/credit', [AdminController::class, 'creditUser']);
        Route::post('/wallet/credit', [AdminController::class, 'creditToWallet']);
        Route::post('/bonuses', [AdminController::class, 'createBonus']);
        Route::get('/bonuses', [AdminController::class, 'getBonuses']);
        Route::get('/advertisements', [AdminController::class, 'getAdvertisements']);
        Route::post('/advertisements', [AdminController::class, 'createAdvertisement']);
        Route::put('/advertisements/{id}', [AdminController::class, 'updateAdvertisement']);
        Route::delete('/advertisements/{id}', [AdminController::class, 'deleteAdvertisement']);
        Route::get('/stats', [AdminController::class, 'getSystemStats']);
    });
});
