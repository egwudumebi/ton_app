<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bonus extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'description',
        'ton_amount',
        'gems_amount',
        'diamonds_amount',
        'is_claimed',
        'claimed_at',
        'expires_at',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'ton_amount' => 'decimal:9',
        'gems_amount' => 'integer',
        'diamonds_amount' => 'integer',
        'is_claimed' => 'boolean',
        'is_active' => 'boolean',
        'claimed_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the bonus.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Claim the bonus.
     */
    public function claim()
    {
        if (!$this->is_claimed && (!$this->expires_at || $this->expires_at->isFuture())) {
            $this->is_claimed = true;
            $this->claimed_at = now();
            $this->save();

            // Add rewards to user's wallet
            if ($this->user && $this->user->wallet) {
                if ($this->ton_amount > 0) {
                    $this->user->wallet->addTon($this->ton_amount);
                }
                if ($this->gems_amount > 0) {
                    $this->user->wallet->addGems($this->gems_amount);
                }
                if ($this->diamonds_amount > 0) {
                    $this->user->wallet->addDiamonds($this->diamonds_amount);
                }
            }

            return true;
        }
        return false;
    }

    /**
     * Check if bonus is expired.
     */
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Scope for available bonuses.
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_claimed', false)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * Scope for claimed bonuses.
     */
    public function scopeClaimed($query)
    {
        return $query->where('is_claimed', true);
    }
}
