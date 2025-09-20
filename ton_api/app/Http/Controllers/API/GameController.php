<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\GameSession;
use App\Models\Score;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GameController extends Controller
{
    /**
     * Start a new game session.
     */
    public function startGame(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'game_type' => 'required|string|in:spin,drop',
            'game_data' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $gameSession = GameSession::start($user->id, $request->game_type, $request->game_data ?? []);

        return response()->json([
            'success' => true,
            'message' => 'Game session started',
            'data' => [
                'session_id' => $gameSession->id,
                'game_type' => $gameSession->game_type,
                'started_at' => $gameSession->started_at,
            ]
        ]);
    }

    /**
     * Complete a game session and update scores.
     */
    public function completeGame(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|exists:game_sessions,id',
            'score' => 'required|integer|min:0',
            'rewards' => 'sometimes|array',
            'rewards.ton' => 'sometimes|numeric|min:0',
            'rewards.gems' => 'sometimes|integer|min:0',
            'rewards.diamonds' => 'sometimes|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $gameSession = GameSession::where('id', $request->session_id)
                                 ->where('user_id', $user->id)
                                 ->first();

        if (!$gameSession) {
            return response()->json([
                'success' => false,
                'message' => 'Game session not found'
            ], 404);
        }

        if ($gameSession->status === 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'Game session already completed'
            ], 400);
        }

        $rewards = $request->rewards ?? [];
        $gameSession->complete($request->score, $rewards);

        // Refresh user data to get updated wallet
        $user->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Game completed successfully',
            'data' => [
                'session_id' => $gameSession->id,
                'score' => $gameSession->score,
                'total_score' => $gameSession->score,
                'rewards' => [
                    'ton' => $gameSession->ton_earned,
                    'gems' => $gameSession->gems_earned,
                    'diamonds' => $gameSession->diamonds_earned,
                ],
                'wallet' => [
                    'balance' => $user->wallet->balance,
                    'gems' => $user->wallet->gems,
                    'diamonds' => $user->wallet->diamonds,
                ],
                'completed_at' => $gameSession->completed_at,
            ]
        ]);
    }

    /**
     * Get user's game statistics.
     */
    public function getStats(Request $request)
    {
        $user = $request->user();
        $gameType = $request->query('game_type', 'all');

        $query = $user->scores();
        if ($gameType !== 'all') {
            $query->where('game_type', $gameType);
        }

        $stats = $query->get()->map(function ($score) {
            return [
                'game_type' => $score->game_type,
                'total_score' => $score->total_score,
                'games_played' => $score->games_played,
                'wins' => $score->wins,
                'losses' => $score->losses,
                'win_rate' => $score->games_played > 0 ? round(($score->wins / $score->games_played) * 100, 2) : 0,
                'last_played_at' => $score->last_played_at,
                'achievements' => $score->achievements ?? [],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'total_games' => $stats->sum('games_played'),
                'total_score' => $stats->sum('total_score'),
            ]
        ]);
    }

    /**
     * Get user's recent game sessions.
     */
    public function getRecentSessions(Request $request)
    {
        $user = $request->user();
        $limit = $request->query('limit', 10);
        $gameType = $request->query('game_type');

        $query = $user->gameSessions()->completed();
        
        if ($gameType) {
            $query->where('game_type', $gameType);
        }

        $sessions = $query->recent($limit)->get()->map(function ($session) {
            return [
                'id' => $session->id,
                'game_type' => $session->game_type,
                'score' => $session->score,
                'duration' => $session->duration,
                'rewards' => [
                    'ton' => $session->ton_earned,
                    'gems' => $session->gems_earned,
                    'diamonds' => $session->diamonds_earned,
                ],
                'completed_at' => $session->completed_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $sessions
        ]);
    }

    /**
     * Get game configuration for a specific game type.
     */
    public function getGameConfig(Request $request)
    {
        $gameType = $request->query('game_type', 'spin');

        $configs = [
            'spin' => [
                'name' => 'TON Wheel',
                'description' => 'Spin the wheel to win TON, gems, and diamonds!',
                'cost' => [
                    'gems' => 10,
                    'diamonds' => 0,
                ],
                'max_spins_per_day' => 50,
                'prizes' => [
                    'ton' => [0.001, 0.005, 0.01, 0.05, 0.1],
                    'gems' => [10, 25, 50, 100, 200],
                    'diamonds' => [1, 2, 5, 10, 20],
                ],
            ],
            'drop' => [
                'name' => 'TON Drop',
                'description' => 'Catch falling TON coins and avoid obstacles!',
                'cost' => [
                    'gems' => 5,
                    'diamonds' => 0,
                ],
                'max_plays_per_day' => 100,
                'difficulty_levels' => ['easy', 'medium', 'hard'],
                'score_multipliers' => [
                    'easy' => 1,
                    'medium' => 1.5,
                    'hard' => 2,
                ],
            ],
        ];

        $config = $configs[$gameType] ?? null;

        if (!$config) {
            return response()->json([
                'success' => false,
                'message' => 'Game type not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $config
        ]);
    }
}
