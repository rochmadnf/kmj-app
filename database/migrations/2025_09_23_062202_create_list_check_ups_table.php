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
        Schema::create('ckg_list_check_ups', function (Blueprint $table) {
            $table->id();
            $table->string('group_name');
            $table->string('group_code');
            $table->string('label');
            $table->string('code');
            $table->string('school_category', 20);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ckg_list_check_ups');
    }
};
