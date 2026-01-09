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
        Schema::create('menu_ingredients', function (Blueprint $table) {
           $table->id();
           $table->foreignId('menu_item_id')->constrained()->onDelete('cascade');
           $table->foreignId('ingredient_id')->constrained()->onDelete('cascade');
           $table->decimal('quantity_needed', 10, 3);
           $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_ingredients');
    }
};
