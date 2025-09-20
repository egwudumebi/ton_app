<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'game_type',
        'score',
        'duration',
        'game_data',
        'ton_earned',
        'gems_earned',
        'diamonds_earned',
        'status',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'score' => 'integer',
        'duration' => 'integer',
        'game_data' => 'array',
        'ton_earned' => 'decimal:9',
        'gems_earned' => 'integer',
        'diamonds_earned' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the game session.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Start a new game session.
     */
    public static function start($userId, $gameType, $gameData = [])
    {
        return self::create([
            'user_id' => $userId,
            'game_type' => $gameType,
            'game_data' => $gameData,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    /**
     * Complete the game session.
     */
    public function complete($score, $rewards = [])
    {
        $this->score = $score;
        $this->ton_earned = $rewards['ton'] ?? 0;
        $this->gems_earned = $rewards['gems'] ?? 0;
        $this->diamonds_earned = $rewards['diamonds'] ?? 0;
        $this->status = 'completed';
        $this->completed_at = now();
        
        if ($this->started_at) {
            $this->duration = $this->started_at->diffInSeconds($this->completed_at);
        }

        $this->save();

        // Update user's wallet
        if ($this->user && $this->user->wallet) {
            if ($this->ton_earned > 0) {
                $this->user->wallet->addTon($this->ton_earned);
            }
            if ($this->gems_earned > 0) {
                $this->user->wallet->addGems($this->gems_earned);
            }
            if ($this->diamonds_earned > 0) {
                $this->user->wallet->addDiamonds($this->diamonds_earned);
            }
        }

        // Update user's score
        $userScore = Score::firstOrCreate(
            ['user_id' => $this->user_id, 'game_type' => $this->game_type],
            [
                'score' => 0,
                'total_score' => 0,
                'games_played' => 0,
                'wins' => 0,
                'losses' => 0,
            ]
        );
        $userScore->updateScore($score);

        return $this;
    }

    /**
     * Scope for recent sessions.
     */
    public function scopeRecent($query, $limit = 10)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Scope for completed sessions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
