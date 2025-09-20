<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Bonus;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    /**
     * Credit user's wallet (admin only).
     */
    public function creditUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'ton_amount' => 'sometimes|numeric|min:0',
            'gems_amount' => 'sometimes|integer|min:0',
            'diamonds_amount' => 'sometimes|integer|min:0',
            'reason' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::findOrFail($request->user_id);
        $wallet = $user->wallet;

        $credits = [];
        if ($request->ton_amount > 0) {
            $wallet->addTon($request->ton_amount);
            $credits['ton'] = $request->ton_amount;
        }
        if ($request->gems_amount > 0) {
            $wallet->addGems($request->gems_amount);
            $credits['gems'] = $request->gems_amount;
        }
        if ($request->diamonds_amount > 0) {
            $wallet->addDiamonds($request->diamonds_amount);
            $credits['diamonds'] = $request->diamonds_amount;
        }

        // Create admin bonus record
        Bonus::create([
            'user_id' => $user->id,
            'type' => 'admin',
            'title' => 'Admin Credit',
            'description' => $request->reason,
            'ton_amount' => $request->ton_amount ?? 0,
            'gems_amount' => $request->gems_amount ?? 0,
            'diamonds_amount' => $request->diamonds_amount ?? 0,
            'is_claimed' => true,
            'claimed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User credited successfully',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                ],
                'credits' => $credits,
                'new_balance' => [
                    'ton' => $wallet->balance,
                    'gems' => $wallet->gems,
                    'diamonds' => $wallet->diamonds,
                ],
            ]
        ]);
    }

    /**
     * Create a bonus for users.
     */
    public function createBonus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:daily,weekly,achievement,admin',
            'title' => 'required|string|max:255',
            'description' => 'sometimes|string',
            'ton_amount' => 'sometimes|numeric|min:0',
            'gems_amount' => 'sometimes|integer|min:0',
            'diamonds_amount' => 'sometimes|integer|min:0',
            'user_ids' => 'sometimes|array',
            'user_ids.*' => 'exists:users,id',
            'expires_at' => 'sometimes|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $userIds = $request->user_ids ?? User::pluck('id')->toArray();
        $bonuses = [];

        foreach ($userIds as $userId) {
            $bonus = Bonus::create([
                'user_id' => $userId,
                'type' => $request->type,
                'title' => $request->title,
                'description' => $request->description,
                'ton_amount' => $request->ton_amount ?? 0,
                'gems_amount' => $request->gems_amount ?? 0,
                'diamonds_amount' => $request->diamonds_amount ?? 0,
                'expires_at' => $request->expires_at,
            ]);
            $bonuses[] = $bonus;
        }

        return response()->json([
            'success' => true,
            'message' => 'Bonus created successfully',
            'data' => [
                'bonus_type' => $request->type,
                'title' => $request->title,
                'users_count' => count($userIds),
                'bonuses_created' => count($bonuses),
            ]
        ]);
    }

    /**
     * Get user statistics (admin view).
     */
    public function getUserStats(Request $request)
    {
        $userId = $request->query('user_id');
        $limit = min($request->query('limit', 20), 100);

        if ($userId) {
            $user = User::with(['wallet', 'scores', 'gameSessions', 'bonuses'])->findOrFail($userId);
            
            $stats = [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'created_at' => $user->created_at,
                ],
                'wallet' => [
                    'balance' => $user->wallet->balance,
                    'gems' => $user->wallet->gems,
                    'diamonds' => $user->wallet->diamonds,
                    'ton_address' => $user->wallet->ton_address,
                ],
                'scores' => $user->scores->map(function ($score) {
                    return [
                        'game_type' => $score->game_type,
                        'total_score' => $score->total_score,
                        'games_played' => $score->games_played,
                        'wins' => $score->wins,
                        'losses' => $score->losses,
                        'win_rate' => $score->games_played > 0 ? round(($score->wins / $score->games_played) * 100, 2) : 0,
                        'last_played_at' => $score->last_played_at,
                    ];
                }),
                'recent_sessions' => $user->gameSessions()
                                        ->completed()
                                        ->recent(10)
                                        ->get()
                                        ->map(function ($session) {
                                            return [
                                                'id' => $session->id,
                                                'game_type' => $session->game_type,
                                                'score' => $session->score,
                                                'rewards' => [
                                                    'ton' => $session->ton_earned,
                                                    'gems' => $session->gems_earned,
                                                    'diamonds' => $session->diamonds_earned,
                                                ],
                                                'completed_at' => $session->completed_at,
                                            ];
                                        }),
                'bonuses' => [
                    'total' => $user->bonuses()->count(),
                    'claimed' => $user->bonuses()->claimed()->count(),
                    'available' => $user->bonuses()->available()->count(),
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        }

        // Get all users with basic stats
        $users = User::with(['wallet', 'scores'])
                    ->withCount(['gameSessions', 'bonuses'])
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get()
                    ->map(function ($user) {
                        $totalScore = $user->scores->sum('total_score');
                        $totalGames = $user->scores->sum('games_played');
                        
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'username' => $user->username,
                            'email' => $user->email,
                            'created_at' => $user->created_at,
                            'wallet' => [
                                'balance' => $user->wallet->balance,
                                'gems' => $user->wallet->gems,
                                'diamonds' => $user->wallet->diamonds,
                            ],
                            'stats' => [
                                'total_score' => $totalScore,
                                'total_games' => $totalGames,
                                'game_sessions_count' => $user->game_sessions_count,
                                'bonuses_count' => $user->bonuses_count,
                            ],
                        ];
                    });

        return response()->json([
            'success' => true,
            'data' => [
                'users' => $users,
                'total_users' => User::count(),
            ]
        ]);
    }

    /**
     * Get system statistics.
     */
    public function getSystemStats(Request $request)
    {
        $stats = [
            'users' => [
                'total' => User::count(),
                'active_today' => User::whereHas('gameSessions', function ($q) {
                    $q->where('completed_at', '>=', now()->startOfDay());
                })->count(),
                'active_week' => User::whereHas('gameSessions', function ($q) {
                    $q->where('completed_at', '>=', now()->subWeek());
                })->count(),
            ],
            'games' => [
                'total_sessions' => \App\Models\GameSession::count(),
                'completed_sessions' => \App\Models\GameSession::completed()->count(),
                'total_score' => \App\Models\Score::sum('total_score'),
                'total_games_played' => \App\Models\Score::sum('games_played'),
            ],
            'wallet' => [
                'total_ton' => Wallet::sum('balance'),
                'total_gems' => Wallet::sum('gems'),
                'total_diamonds' => Wallet::sum('diamonds'),
            ],
            'bonuses' => [
                'total' => Bonus::count(),
                'claimed' => Bonus::claimed()->count(),
                'available' => Bonus::available()->count(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Search users.
     */
    public function searchUsers(Request $request)
    {
        $query = $request->query('q');
        $limit = min($request->query('limit', 20), 50);

        if (!$query) {
            return response()->json([
                'success' => false,
                'message' => 'Search query is required'
            ], 400);
        }

        $users = User::where('name', 'like', "%{$query}%")
                    ->orWhere('username', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->with(['wallet'])
                    ->limit($limit)
                    ->get()
                    ->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'username' => $user->username,
                            'email' => $user->email,
                            'wallet' => [
                                'balance' => $user->wallet->balance,
                                'gems' => $user->wallet->gems,
                                'diamonds' => $user->wallet->diamonds,
                            ],
                        ];
                    });

        return response()->json([
            'success' => true,
            'data' => [
                'users' => $users,
                'query' => $query,
                'count' => $users->count(),
            ]
        ]);
    }
}
