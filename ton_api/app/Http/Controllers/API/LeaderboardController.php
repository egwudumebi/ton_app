<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Score;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    /**
     * Get leaderboard for a specific game type.
     */
    public function getLeaderboard(Request $request)
    {
        $gameType = $request->query('game_type', 'spin');
        $limit = min($request->query('limit', 50), 100); // Max 100 results
        $offset = $request->query('offset', 0);
        $period = $request->query('period', 'all'); // all, week, month

        $cacheKey = "leaderboard_{$gameType}_{$period}_{$limit}_{$offset}";
        
        return Cache::remember($cacheKey, 300, function () use ($gameType, $limit, $offset, $period) {
            $query = Score::with('user')
                         ->where('game_type', $gameType)
                         ->where('total_score', '>', 0);

            // Apply time period filter
            if ($period === 'week') {
                $query->where('last_played_at', '>=', now()->subWeek());
            } elseif ($period === 'month') {
                $query->where('last_played_at', '>=', now()->subMonth());
            }

            $leaderboard = $query->orderBy('total_score', 'desc')
                                ->offset($offset)
                                ->limit($limit)
                                ->get()
                                ->map(function ($score, $index) use ($offset) {
                                    return [
                                        'rank' => $offset + $index + 1,
                                        'user' => [
                                            'id' => $score->user->id,
                                            'name' => $score->user->name,
                                            'username' => $score->user->username,
                                        ],
                                        'score' => [
                                            'total' => $score->total_score,
                                            'current' => $score->score,
                                            'games_played' => $score->games_played,
                                            'wins' => $score->wins,
                                            'losses' => $score->losses,
                                            'win_rate' => $score->games_played > 0 ? round(($score->wins / $score->games_played) * 100, 2) : 0,
                                        ],
                                        'last_played' => $score->last_played_at,
                                    ];
                                });

            return response()->json([
                'success' => true,
                'data' => [
                    'game_type' => $gameType,
                    'period' => $period,
                    'leaderboard' => $leaderboard,
                    'pagination' => [
                        'limit' => $limit,
                        'offset' => $offset,
                        'total' => $query->count(),
                    ],
                ]
            ]);
        });
    }

    /**
     * Get user's position in leaderboard.
     */
    public function getUserPosition(Request $request)
    {
        $user = $request->user();
        $gameType = $request->query('game_type', 'spin');
        $period = $request->query('period', 'all');

        $cacheKey = "user_position_{$user->id}_{$gameType}_{$period}";
        
        return Cache::remember($cacheKey, 300, function () use ($user, $gameType, $period) {
            $query = Score::where('game_type', $gameType)
                         ->where('total_score', '>', 0);

            // Apply time period filter
            if ($period === 'week') {
                $query->where('last_played_at', '>=', now()->subWeek());
            } elseif ($period === 'month') {
                $query->where('last_played_at', '>=', now()->subMonth());
            }

            $userScore = $user->scores()->where('game_type', $gameType)->first();
            
            if (!$userScore || $userScore->total_score <= 0) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'position' => null,
                        'total_players' => $query->count(),
                        'user_score' => $userScore ? $userScore->total_score : 0,
                    ]
                ]);
            }

            $position = $query->where('total_score', '>', $userScore->total_score)->count() + 1;

            return response()->json([
                'success' => true,
                'data' => [
                    'position' => $position,
                    'total_players' => $query->count(),
                    'user_score' => $userScore->total_score,
                    'score_details' => [
                        'current_score' => $userScore->score,
                        'games_played' => $userScore->games_played,
                        'wins' => $userScore->wins,
                        'losses' => $userScore->losses,
                        'win_rate' => $userScore->games_played > 0 ? round(($userScore->wins / $userScore->games_played) * 100, 2) : 0,
                    ],
                ]
            ]);
        });
    }

    /**
     * Get global statistics.
     */
    public function getGlobalStats(Request $request)
    {
        $gameType = $request->query('game_type', 'all');
        $period = $request->query('period', 'all');

        $cacheKey = "global_stats_{$gameType}_{$period}";
        
        return Cache::remember($cacheKey, 600, function () use ($gameType, $period) {
            $query = Score::query();

            if ($gameType !== 'all') {
                $query->where('game_type', $gameType);
            }

            // Apply time period filter
            if ($period === 'week') {
                $query->where('last_played_at', '>=', now()->subWeek());
            } elseif ($period === 'month') {
                $query->where('last_played_at', '>=', now()->subMonth());
            }

            $stats = $query->select([
                'game_type',
                DB::raw('COUNT(DISTINCT user_id) as total_players'),
                DB::raw('SUM(total_score) as total_score'),
                DB::raw('SUM(games_played) as total_games'),
                DB::raw('SUM(wins) as total_wins'),
                DB::raw('SUM(losses) as total_losses'),
                DB::raw('AVG(total_score) as avg_score'),
                DB::raw('MAX(total_score) as max_score'),
            ])
            ->groupBy('game_type')
            ->get();

            $totalStats = [
                'total_players' => $stats->sum('total_players'),
                'total_score' => $stats->sum('total_score'),
                'total_games' => $stats->sum('total_games'),
                'total_wins' => $stats->sum('total_wins'),
                'total_losses' => $stats->sum('total_losses'),
                'avg_score' => $stats->sum('total_score') > 0 ? round($stats->sum('total_score') / $stats->sum('total_players'), 2) : 0,
                'max_score' => $stats->max('max_score'),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => $period,
                    'game_type' => $gameType,
                    'total_stats' => $totalStats,
                    'game_stats' => $stats,
                ]
            ]);
        });
    }

    /**
     * Get top players across all games.
     */
    public function getTopPlayers(Request $request)
    {
        $limit = min($request->query('limit', 10), 50);
        $period = $request->query('period', 'all');

        $cacheKey = "top_players_{$period}_{$limit}";
        
        return Cache::remember($cacheKey, 300, function () use ($limit, $period) {
            $query = User::with(['scores' => function ($q) use ($period) {
                if ($period === 'week') {
                    $q->where('last_played_at', '>=', now()->subWeek());
                } elseif ($period === 'month') {
                    $q->where('last_played_at', '>=', now()->subMonth());
                }
            }])
            ->whereHas('scores', function ($q) use ($period) {
                if ($period === 'week') {
                    $q->where('last_played_at', '>=', now()->subWeek());
                } elseif ($period === 'month') {
                    $q->where('last_played_at', '>=', now()->subMonth());
                }
            });

            $topPlayers = $query->get()
                               ->map(function ($user) {
                                   $totalScore = $user->scores->sum('total_score');
                                   $totalGames = $user->scores->sum('games_played');
                                   $totalWins = $user->scores->sum('wins');
                                   
                                   return [
                                       'user' => [
                                           'id' => $user->id,
                                           'name' => $user->name,
                                           'username' => $user->username,
                                       ],
                                       'stats' => [
                                           'total_score' => $totalScore,
                                           'total_games' => $totalGames,
                                           'total_wins' => $totalWins,
                                           'win_rate' => $totalGames > 0 ? round(($totalWins / $totalGames) * 100, 2) : 0,
                                       ],
                                   ];
                               })
                               ->sortByDesc('stats.total_score')
                               ->take($limit)
                               ->values()
                               ->map(function ($player, $index) {
                                   $player['rank'] = $index + 1;
                                   return $player;
                               });

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => $period,
                    'top_players' => $topPlayers,
                ]
            ]);
        });
    }
}
