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
        Schema::create('game_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('game_type'); // 'spin', 'drop', etc.
            $table->bigInteger('score')->default(0);
            $table->integer('duration')->nullable(); // Session duration in seconds
            $table->json('game_data')->nullable(); // Store game-specific data
            $table->decimal('ton_earned', 18, 9)->default(0);
            $table->integer('gems_earned')->default(0);
            $table->integer('diamonds_earned')->default(0);
            $table->string('status')->default('completed'); // 'completed', 'abandoned', 'error'
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'game_type']);
            $table->index(['user_id', 'created_at']);
            $table->index(['game_type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_sessions');
    }
};
