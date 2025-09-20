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
        Schema::create('bonuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // 'daily', 'weekly', 'achievement', 'admin'
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('ton_amount', 18, 9)->default(0);
            $table->integer('gems_amount')->default(0);
            $table->integer('diamonds_amount')->default(0);
            $table->boolean('is_claimed')->default(false);
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable(); // Additional bonus data
            $table->timestamps();
            
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'is_claimed']);
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bonuses');
    }
};
