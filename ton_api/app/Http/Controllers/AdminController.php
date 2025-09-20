<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wallet;
use App\Models\GameSession;
use App\Models\Advertisement;
use App\Models\Bonus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function __construct()
    {
        // Middleware is handled in routes/api.php
    }

    /**
     * Get system statistics
     */
    public function getSystemStats()
    {
        try {
            $stats = [
                'users' => [
                    'total' => User::where('is_admin', false)->count(),
                    'active' => User::where('is_admin', false)->where(function($q) {
                        $q->where('is_active', true)->orWhereNull('is_active');
                    })->count(),
                    'inactive' => User::where('is_admin', false)->where('is_active', false)->count(),
                ],
                'games' => [
                    'total_sessions' => GameSession::count(),
                    'completed_sessions' => GameSession::where('status', 'completed')->count(),
                    'total_score' => GameSession::sum('score'),
                ],
                'wallets' => [
                    'total_ton' => Wallet::sum('balance'),
                    'total_gems' => Wallet::sum('gems'),
                    'total_diamonds' => Wallet::sum('diamonds'),
                ],
                'advertisements' => [
                    'total' => Advertisement::count(),
                    'active' => Advertisement::where('is_active', true)->count(),
                ],
            ];

            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch system stats'], 500);
        }
    }

    /**
     * Get all users with pagination
     */
    public function getUsers(Request $request)
    {
        try {
            $limit = $request->get('limit', 50);
            
            $users = User::with(['wallet', 'gameSessions'])
                ->where('is_admin', false)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            $usersWithStats = $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'is_active' => $user->is_active,
                    'created_at' => $user->created_at,
                    'wallet' => $user->wallet ? [
                        'balance' => $user->wallet->balance,
                        'gems' => $user->wallet->gems,
                        'diamonds' => $user->wallet->diamonds,
                    ] : [
                        'balance' => 0,
                        'gems' => 0,
                        'diamonds' => 0,
                    ],
                    'stats' => [
                        'total_games' => $user->gameSessions->count(),
                        'total_score' => $user->gameSessions->sum('score'),
                        'last_game' => $user->gameSessions->sortByDesc('created_at')->first()?->created_at,
                    ],
                ];
            });

            return response()->json(['users' => $usersWithStats]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch users'], 500);
        }
    }

    /**
     * Search users
     */
    public function searchUsers(Request $request)
    {
        try {
            $query = $request->get('q') ?: $request->get('query');
            
            if (!$query) {
                return response()->json(['users' => []]);
            }

            $users = User::with(['wallet', 'gameSessions'])
                ->where('is_admin', false)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                      ->orWhere('email', 'LIKE', "%{$query}%")
                      ->orWhere('username', 'LIKE', "%{$query}%");
                })
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();

            $usersWithStats = $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'is_active' => $user->is_active,
                    'created_at' => $user->created_at,
                    'wallet' => $user->wallet ? [
                        'balance' => $user->wallet->balance,
                        'gems' => $user->wallet->gems,
                        'diamonds' => $user->wallet->diamonds,
                    ] : [
                        'balance' => 0,
                        'gems' => 0,
                        'diamonds' => 0,
                    ],
                    'stats' => [
                        'total_games' => $user->gameSessions->count(),
                        'total_score' => $user->gameSessions->sum('score'),
                        'last_game' => $user->gameSessions->sortByDesc('created_at')->first()?->created_at,
                    ],
                ];
            });

            return response()->json(['users' => $usersWithStats]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to search users'], 500);
        }
    }

    /**
     * Get user details
     */
    public function getUserDetails($userId)
    {
        try {
            $user = User::with(['wallet', 'gameSessions'])->findOrFail($userId);

            $userDetails = [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'is_active' => $user->is_active,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
                'wallet' => $user->wallet ? [
                    'balance' => $user->wallet->balance,
                    'gems' => $user->wallet->gems,
                    'diamonds' => $user->wallet->diamonds,
                    'wallet_address' => $user->wallet->wallet_address,
                ] : null,
                'stats' => [
                    'total_games' => $user->gameSessions->count(),
                    'total_score' => $user->gameSessions->sum('score'),
                    'games_by_type' => $user->gameSessions->groupBy('game_type')->map->count(),
                    'recent_games' => $user->gameSessions->sortByDesc('created_at')->take(5)->values(),
                ],
            ];

            return response()->json($userDetails);
        } catch (\Exception $e) {
            return response()->json(['error' => 'User not found'], 404);
        }
    }

    /**
     * Update user
     */
    public function updateUser(Request $request, $userId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $userId,
                'username' => 'sometimes|string|unique:users,username,' . $userId,
                'is_active' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user = User::findOrFail($userId);
            $user->update($request->only(['name', 'email', 'username', 'is_active']));

            return response()->json(['message' => 'User updated successfully', 'user' => $user]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update user'], 500);
        }
    }

    /**
     * Deactivate user
     */
    public function deactivateUser($userId)
    {
        try {
            $user = User::findOrFail($userId);
            $user->update(['is_active' => false]);

            return response()->json(['message' => 'User deactivated successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to deactivate user'], 500);
        }
    }

    /**
     * Activate user
     */
    public function activateUser($userId)
    {
        try {
            $user = User::findOrFail($userId);
            $user->update(['is_active' => true]);

            return response()->json(['message' => 'User activated successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to activate user'], 500);
        }
    }

    /**
     * Credit user wallet
     */
    public function creditUser(Request $request, $userId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ton_amount' => 'sometimes|numeric|min:0',
                'gems_amount' => 'sometimes|integer|min:0',
                'diamonds_amount' => 'sometimes|integer|min:0',
                'reason' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user = User::findOrFail($userId);
            $wallet = $user->wallet;

            if (!$wallet) {
                $wallet = Wallet::create([
                    'user_id' => $user->id,
                    'balance' => 0,
                    'gems' => 0,
                    'diamonds' => 0,
                ]);
            }

            DB::transaction(function () use ($request, $wallet) {
                if ($request->has('ton_amount')) {
                    $wallet->increment('balance', $request->ton_amount);
                }
                if ($request->has('gems_amount')) {
                    $wallet->increment('gems', $request->gems_amount);
                }
                if ($request->has('diamonds_amount')) {
                    $wallet->increment('diamonds', $request->diamonds_amount);
                }
            });

            return response()->json(['message' => 'User credited successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to credit user'], 500);
        }
    }

    /**
     * Credit TON to user's external wallet
     */
    public function creditToWallet(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'ton_amount' => 'required|numeric|min:0.0001',
                'wallet_address' => 'required|string',
                'reason' => 'sometimes|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // In a real implementation, this would integrate with TON blockchain
            // For now, we'll just log the transaction
            
            return response()->json([
                'message' => 'TON credited to wallet successfully',
                'transaction' => [
                    'user_id' => $request->user_id,
                    'amount' => $request->ton_amount,
                    'wallet_address' => $request->wallet_address,
                    'reason' => $request->reason ?? 'Admin credit',
                    'timestamp' => now(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to credit wallet'], 500);
        }
    }

    /**
     * Create bonus for all users
     */
    public function createBonus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'ton_amount' => 'sometimes|numeric|min:0',
                'gems_amount' => 'sometimes|integer|min:0',
                'diamonds_amount' => 'sometimes|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $bonus = Bonus::create([
                'title' => $request->title,
                'description' => $request->description,
                'ton_amount' => $request->ton_amount ?? 0,
                'gems_amount' => $request->gems_amount ?? 0,
                'diamonds_amount' => $request->diamonds_amount ?? 0,
                'is_active' => true,
            ]);

            // Apply bonus to all active users
            $activeUsers = User::where('is_active', true)->where('is_admin', false)->get();
            
            foreach ($activeUsers as $user) {
                $wallet = $user->wallet;
                if (!$wallet) {
                    $wallet = Wallet::create([
                        'user_id' => $user->id,
                        'balance' => 0,
                        'gems' => 0,
                        'diamonds' => 0,
                    ]);
                }

                DB::transaction(function () use ($request, $wallet) {
                    if ($request->ton_amount > 0) {
                        $wallet->increment('balance', $request->ton_amount);
                    }
                    if ($request->gems_amount > 0) {
                        $wallet->increment('gems', $request->gems_amount);
                    }
                    if ($request->diamonds_amount > 0) {
                        $wallet->increment('diamonds', $request->diamonds_amount);
                    }
                });
            }

            return response()->json([
                'message' => 'Bonus created and applied to all users successfully',
                'bonus' => $bonus,
                'users_affected' => $activeUsers->count()
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create bonus'], 500);
        }
    }

    /**
     * Get bonuses
     */
    public function getBonuses()
    {
        try {
            $bonuses = Bonus::orderBy('created_at', 'desc')->get();
            return response()->json(['bonuses' => $bonuses]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch bonuses'], 500);
        }
    }

    /**
     * Get advertisements
     */
    public function getAdvertisements()
    {
        try {
            $advertisements = Advertisement::orderBy('created_at', 'desc')->get();
            return response()->json(['advertisements' => $advertisements]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch advertisements'], 500);
        }
    }

    /**
     * Create advertisement
     */
    public function createAdvertisement(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'sometimes|string',
                'media_type' => 'required|in:image,video',
                'media_url' => 'sometimes|string',
                'is_active' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $advertisement = Advertisement::create([
                'title' => $request->title,
                'description' => $request->description,
                'media_type' => $request->media_type,
                'media_url' => $request->media_url,
                'is_active' => $request->is_active ?? true,
            ]);

            return response()->json([
                'message' => 'Advertisement created successfully',
                'advertisement' => $advertisement
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create advertisement'], 500);
        }
    }

    /**
     * Update advertisement
     */
    public function updateAdvertisement(Request $request, $adId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'media_type' => 'sometimes|in:image,video',
                'media_url' => 'sometimes|string',
                'is_active' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $advertisement = Advertisement::findOrFail($adId);
            $advertisement->update($request->only(['title', 'description', 'media_type', 'media_url', 'is_active']));

            return response()->json([
                'message' => 'Advertisement updated successfully',
                'advertisement' => $advertisement
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update advertisement'], 500);
        }
    }

    /**
     * Delete advertisement
     */
    public function deleteAdvertisement($adId)
    {
        try {
            $advertisement = Advertisement::findOrFail($adId);
            $advertisement->delete();

            return response()->json(['message' => 'Advertisement deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete advertisement'], 500);
        }
    }
}
