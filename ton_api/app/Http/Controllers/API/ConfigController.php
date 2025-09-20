<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ConfigController extends Controller
{
    /**
     * Get app configuration
     */
    public function getConfig()
    {
        $config = Cache::remember('app_config', 3600, function () {
            return [
                'spinWheelCooldown' => env('SPIN_WHEEL_COOLDOWN', 300), // 5 minutes default
                'maxSpinsPerDay' => env('MAX_SPINS_PER_DAY', 10),
                'dropGameDuration' => env('DROP_GAME_DURATION', 30),
                'hourlyClaimAmount' => env('HOURLY_CLAIM_AMOUNT', 100),
                'bonusClaimAmount' => env('BONUS_CLAIM_AMOUNT', 250),
                'minPayoutAmount' => env('MIN_PAYOUT_AMOUNT', 100000),
                'payoutDay' => env('PAYOUT_DAY', 'Tuesday'),
                'showOfferwall' => env('SHOW_OFFERWALL', false),
                'showTelegramMiniApps' => env('SHOW_TELEGRAM_MINI_APPS', true),
                'showBonusClaim' => env('SHOW_BONUS_CLAIM', true),
                'appVersion' => '1.0.0',
                'lastUpdated' => now()->toISOString(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $config
        ]);
    }

    /**
     * Update app configuration (Admin only)
     */
    public function updateConfig(Request $request)
    {
        $request->validate([
            'spinWheelCooldown' => 'sometimes|integer|min:60|max:86400',
            'maxSpinsPerDay' => 'sometimes|integer|min:1|max:100',
            'dropGameDuration' => 'sometimes|integer|min:10|max:300',
            'hourlyClaimAmount' => 'sometimes|integer|min:1|max:10000',
            'bonusClaimAmount' => 'sometimes|integer|min:1|max:10000',
            'minPayoutAmount' => 'sometimes|integer|min:1000',
            'payoutDay' => 'sometimes|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'showOfferwall' => 'sometimes|boolean',
            'showTelegramMiniApps' => 'sometimes|boolean',
            'showBonusClaim' => 'sometimes|boolean',
        ]);

        // Store in cache and optionally in database
        $config = $request->only([
            'spinWheelCooldown',
            'maxSpinsPerDay', 
            'dropGameDuration',
            'hourlyClaimAmount',
            'bonusClaimAmount',
            'minPayoutAmount',
            'payoutDay',
            'showOfferwall',
            'showTelegramMiniApps',
            'showBonusClaim'
        ]);

        Cache::put('app_config', array_merge(Cache::get('app_config', []), $config), 3600);

        return response()->json([
            'success' => true,
            'message' => 'Configuration updated successfully',
            'data' => $config
        ]);
    }
}
