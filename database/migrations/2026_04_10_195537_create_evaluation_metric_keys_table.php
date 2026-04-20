<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_metric_keys', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category');
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['category', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_metric_keys');
    }
};
