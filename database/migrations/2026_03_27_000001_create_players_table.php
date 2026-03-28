<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->date('date_of_birth');
            $table->string('position');
            $table->string('dominant_foot');
            $table->string('photo_path')->nullable();
            $table->string('guardian_name');
            $table->string('guardian_email');
            $table->string('guardian_phone')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
