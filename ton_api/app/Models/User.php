<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'phone',
        'avatar',
        'is_admin',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the user's wallet.
     */
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * Get the user's scores.
     */
    public function scores()
    {
        return $this->hasMany(Score::class);
    }

    /**
     * Get the user's bonuses.
     */
    public function bonuses()
    {
        return $this->hasMany(Bonus::class);
    }

    /**
     * Get the user's game sessions.
     */
    public function gameSessions()
    {
        return $this->hasMany(GameSession::class);
    }

    /**
     * Get the user's total score for a specific game type.
     */
    public function getTotalScore($gameType)
    {
        return $this->scores()->where('game_type', $gameType)->value('total_score') ?? 0;
    }

    /**
     * Get the user's current balance.
     */
    public function getBalance()
    {
        return $this->wallet?->balance ?? 0;
    }

    /**
     * Get the user's current gems.
     */
    public function getGems()
    {
        return $this->wallet?->gems ?? 0;
    }

    /**
     * Get the user's current diamonds.
     */
    public function getDiamonds()
    {
        return $this->wallet?->diamonds ?? 0;
    }
}
