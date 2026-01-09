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
        Schema::create('reservations', function (Blueprint $table) {
           $table->id();
           $table->foreignId('user_id')->constrained()->onDelete('cascade');
           $table->foreignId('table_id')->constrained()->onDelete('cascade');
           $table->date('date');
           $table->time('time');
           $table->integer('guests_count');
           $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
           $table->text('special_requests')->nullable();
           $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
