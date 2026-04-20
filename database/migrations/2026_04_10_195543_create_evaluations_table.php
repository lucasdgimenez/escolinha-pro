<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->date('evaluated_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'player_id']);
            $table->index(['tenant_id', 'evaluated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
