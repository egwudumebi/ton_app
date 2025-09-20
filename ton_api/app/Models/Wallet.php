<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ton_address',
        'balance',
        'gems',
        'diamonds',
    ];

    protected $casts = [
        'balance' => 'decimal:9',
        'gems' => 'integer',
        'diamonds' => 'integer',
    ];

    /**
     * Get the user that owns the wallet.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Add TON to the wallet.
     */
    public function addTon($amount)
    {
        $this->increment('balance', $amount);
        return $this;
    }

    /**
     * Subtract TON from the wallet.
     */
    public function subtractTon($amount)
    {
        if ($this->balance >= $amount) {
            $this->decrement('balance', $amount);
            return true;
        }
        return false;
    }

    /**
     * Add gems to the wallet.
     */
    public function addGems($amount)
    {
        $this->increment('gems', $amount);
        return $this;
    }

    /**
     * Subtract gems from the wallet.
     */
    public function subtractGems($amount)
    {
        if ($this->gems >= $amount) {
            $this->decrement('gems', $amount);
            return true;
        }
        return false;
    }

    /**
     * Add diamonds to the wallet.
     */
    public function addDiamonds($amount)
    {
        $this->increment('diamonds', $amount);
        return $this;
    }

    /**
     * Subtract diamonds from the wallet.
     */
    public function subtractDiamonds($amount)
    {
        if ($this->diamonds >= $amount) {
            $this->decrement('diamonds', $amount);
            return true;
        }
        return false;
    }
}
