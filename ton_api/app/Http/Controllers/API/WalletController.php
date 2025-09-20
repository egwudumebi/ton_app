<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    /**
     * Get user's wallet information.
     */
    public function getWallet(Request $request)
    {
        $user = $request->user();
        $wallet = $user->wallet;

        return response()->json([
            'success' => true,
            'data' => [
                'balance' => $wallet->balance,
                'gems' => $wallet->gems,
                'diamonds' => $wallet->diamonds,
                'ton_address' => $wallet->ton_address,
                'updated_at' => $wallet->updated_at,
            ]
        ]);
    }

    /**
     * Update TON wallet address.
     */
    public function updateTonAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ton_address' => 'required|string|max:255|regex:/^[0-9a-zA-Z_-]{48}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $wallet = $user->wallet;

        // Check if address is already taken by another user
        $existingWallet = Wallet::where('ton_address', $request->ton_address)
                               ->where('user_id', '!=', $user->id)
                               ->first();

        if ($existingWallet) {
            return response()->json([
                'success' => false,
                'message' => 'This TON address is already registered by another user'
            ], 422);
        }

        $wallet->update(['ton_address' => $request->ton_address]);

        return response()->json([
            'success' => true,
            'message' => 'TON address updated successfully',
            'data' => [
                'ton_address' => $wallet->ton_address,
            ]
        ]);
    }

    /**
     * Get wallet transaction history.
     */
    public function getTransactionHistory(Request $request)
    {
        $user = $request->user();
        $limit = $request->query('limit', 20);
        $offset = $request->query('offset', 0);

        $transactions = $user->gameSessions()
                            ->completed()
                            ->orderBy('completed_at', 'desc')
                            ->offset($offset)
                            ->limit($limit)
                            ->get()
                            ->map(function ($session) {
                                return [
                                    'id' => $session->id,
                                    'type' => 'game_reward',
                                    'game_type' => $session->game_type,
                                    'amount' => [
                                        'ton' => $session->ton_earned,
                                        'gems' => $session->gems_earned,
                                        'diamonds' => $session->diamonds_earned,
                                    ],
                                    'description' => ucfirst($session->game_type) . ' game reward',
                                    'timestamp' => $session->completed_at,
                                ];
                            });

        // Add bonus transactions
        $bonusTransactions = $user->bonuses()
                                 ->claimed()
                                 ->orderBy('claimed_at', 'desc')
                                 ->offset($offset)
                                 ->limit($limit)
                                 ->get()
                                 ->map(function ($bonus) {
                                     return [
                                         'id' => $bonus->id,
                                         'type' => 'bonus',
                                         'amount' => [
                                             'ton' => $bonus->ton_amount,
                                             'gems' => $bonus->gems_amount,
                                             'diamonds' => $bonus->diamonds_amount,
                                         ],
                                         'description' => $bonus->title,
                                         'timestamp' => $bonus->claimed_at,
                                     ];
                                 });

        // Merge and sort transactions
        $allTransactions = $transactions->concat($bonusTransactions)
                                       ->sortByDesc('timestamp')
                                       ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'transactions' => $allTransactions,
                'total' => $allTransactions->count(),
            ]
        ]);
    }

    /**
     * Get wallet statistics.
     */
    public function getWalletStats(Request $request)
    {
        $user = $request->user();
        $period = $request->query('period', 'all'); // all, week, month

        $query = $user->gameSessions()->completed();

        if ($period === 'week') {
            $query->where('completed_at', '>=', now()->subWeek());
        } elseif ($period === 'month') {
            $query->where('completed_at', '>=', now()->subMonth());
        }

        $stats = $query->get();

        $totalEarned = [
            'ton' => $stats->sum('ton_earned'),
            'gems' => $stats->sum('gems_earned'),
            'diamonds' => $stats->sum('diamonds_earned'),
        ];

        $gamesPlayed = $stats->count();
        $totalScore = $stats->sum('score');

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $period,
                'total_earned' => $totalEarned,
                'games_played' => $gamesPlayed,
                'total_score' => $totalScore,
                'average_score' => $gamesPlayed > 0 ? round($totalScore / $gamesPlayed, 2) : 0,
            ]
        ]);
    }

    /**
     * Get available bonuses.
     */
    public function getAvailableBonuses(Request $request)
    {
        $user = $request->user();
        
        $bonuses = $user->bonuses()
                       ->available()
                       ->orderBy('created_at', 'desc')
                       ->get()
                       ->map(function ($bonus) {
                           return [
                               'id' => $bonus->id,
                               'type' => $bonus->type,
                               'title' => $bonus->title,
                               'description' => $bonus->description,
                               'amount' => [
                                   'ton' => $bonus->ton_amount,
                                   'gems' => $bonus->gems_amount,
                                   'diamonds' => $bonus->diamonds_amount,
                               ],
                               'expires_at' => $bonus->expires_at,
                               'created_at' => $bonus->created_at,
                           ];
                       });

        return response()->json([
            'success' => true,
            'data' => $bonuses
        ]);
    }

    /**
     * Claim a bonus.
     */
    public function claimBonus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bonus_id' => 'required|exists:bonuses,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $bonus = $user->bonuses()->find($request->bonus_id);

        if (!$bonus) {
            return response()->json([
                'success' => false,
                'message' => 'Bonus not found'
            ], 404);
        }

        if ($bonus->is_claimed) {
            return response()->json([
                'success' => false,
                'message' => 'Bonus already claimed'
            ], 400);
        }

        if ($bonus->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'Bonus has expired'
            ], 400);
        }

        $claimed = $bonus->claim();

        if (!$claimed) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to claim bonus'
            ], 400);
        }

        // Refresh user data to get updated wallet
        $user->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Bonus claimed successfully',
            'data' => [
                'bonus' => [
                    'id' => $bonus->id,
                    'title' => $bonus->title,
                    'amount' => [
                        'ton' => $bonus->ton_amount,
                        'gems' => $bonus->gems_amount,
                        'diamonds' => $bonus->diamonds_amount,
                    ],
                ],
                'wallet' => [
                    'balance' => $user->wallet->balance,
                    'gems' => $user->wallet->gems,
                    'diamonds' => $user->wallet->diamonds,
                ],
            ]
        ]);
    }
}
