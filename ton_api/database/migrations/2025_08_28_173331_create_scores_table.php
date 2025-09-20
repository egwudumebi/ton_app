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
        Schema::create('scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('game_type'); // 'spin', 'drop', etc.
            $table->bigInteger('score')->default(0);
            $table->bigInteger('total_score')->default(0); // Cumulative score
            $table->integer('games_played')->default(0);
            $table->integer('wins')->default(0);
            $table->integer('losses')->default(0);
            $table->json('achievements')->nullable(); // Store achievements as JSON
            $table->timestamp('last_played_at')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'game_type']);
            $table->index(['game_type', 'total_score']);
            $table->index(['user_id', 'last_played_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scores');
    }
};
