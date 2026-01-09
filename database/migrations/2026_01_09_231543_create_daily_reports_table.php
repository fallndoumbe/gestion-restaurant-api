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
        Schema::create('daily_reports', function (Blueprint $table) {
           $table->id();
           $table->date('date')->unique();
           $table->integer('total_orders')->default(0);
           $table->decimal('total_revenue', 12, 2)->default(0);
           $table->integer('total_customers')->default(0);
           $table->foreignId('best_seller_id')->nullable()->constrained('menu_items')->onDelete('set null');
           $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_reports');
    }
};
