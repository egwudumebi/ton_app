<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Score extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'game_type',
        'score',
        'total_score',
        'games_played',
        'wins',
        'losses',
        'achievements',
        'last_played_at',
    ];

    protected $casts = [
        'score' => 'integer',
        'total_score' => 'integer',
        'games_played' => 'integer',
        'wins' => 'integer',
        'losses' => 'integer',
        'achievements' => 'array',
        'last_played_at' => 'datetime',
    ];

    /**
     * Get the user that owns the score.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Update score after a game session.
     */
    public function updateScore($newScore, $isWin = null)
    {
        $this->score = $newScore;
        $this->total_score += $newScore;
        $this->games_played++;
        $this->last_played_at = now();

        if ($isWin !== null) {
            if ($isWin) {
                $this->wins++;
            } else {
                $this->losses++;
            }
        }

        $this->save();
        return $this;
    }

    /**
     * Add an achievement.
     */
    public function addAchievement($achievement)
    {
        $achievements = $this->achievements ?? [];
        if (!in_array($achievement, $achievements)) {
            $achievements[] = $achievement;
            $this->achievements = $achievements;
            $this->save();
        }
        return $this;
    }

    /**
     * Scope for leaderboard queries.
     */
    public function scopeLeaderboard($query, $gameType, $limit = 100)
    {
        return $query->where('game_type', $gameType)
                    ->orderBy('total_score', 'desc')
                    ->limit($limit);
    }
}
