<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained('training_schedules')->nullOnDelete();
            $table->date('session_date');
            $table->time('start_time');
            $table->unsignedSmallInteger('duration_minutes');
            $table->string('location')->nullable();
            $table->string('status')->default('scheduled');
            $table->text('notes')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'category_id']);
            $table->index(['tenant_id', 'session_date']);
            $table->unique(['schedule_id', 'session_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_sessions');
    }
};
