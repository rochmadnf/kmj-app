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
        Schema::create('screenings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('register_id', 26);
            $table->date('register_date');

            $table->foreignUuid('patient_id')->constrained('patients')->onDelete('cascade');

            $table->char('ticket_number', 7);
            $table->string('token_report');

            $table->string('klaster_code', 10);
            $table->string('klaster_name', 100);
            
            $table->string('school_name');
            $table->string('school_code', 15);
            $table->string('school_category', 20);

            $table->string('class_name', 8);
            $table->string('class_code', 15);



            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('screenings');
    }
};
