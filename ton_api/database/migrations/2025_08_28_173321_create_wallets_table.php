<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('ton_address')->unique()->nullable();
            $table->decimal('balance', 18, 9)->default(0); // TON balance with 9 decimal places
            $table->integer('gems')->default(0); // In-game currency
            $table->integer('diamonds')->default(0); // Premium currency
            $table->timestamps();
            
            $table->index(['user_id', 'ton_address']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
