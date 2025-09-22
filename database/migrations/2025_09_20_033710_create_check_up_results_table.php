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
        Schema::create('check_up_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('screening_id')->constrained('screenings')->onDelete('cascade');
            $table->json('results');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('check_up_results');
    }
};
