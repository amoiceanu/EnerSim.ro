<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment_components', function (Blueprint $table) {
            $table->id();
            $table->string('type', 24)->index();
            $table->string('slug')->unique();
            $table->string('brand');
            $table->string('name');
            $table->string('model')->nullable();
            $table->string('sku')->nullable();
            $table->json('tech_data');
            $table->decimal('price_lei', 12, 2)->nullable();
            $table->string('stock_status', 32)->nullable();
            $table->text('source_url');
            $table->date('source_checked_at');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_components');
    }
};
