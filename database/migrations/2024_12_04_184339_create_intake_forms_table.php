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
        Schema::create('intake_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('tattoo_style');
            $table->text('tattoo_description');
            $table->string('placement');
            $table->string('size');
            $table->text('reference_images')->nullable(); // JSON array of image URLs
            $table->string('budget_range');
            $table->text('additional_notes')->nullable();
            $table->boolean('has_previous_tattoos')->default(false);
            $table->text('medical_conditions')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intake_forms');
    }
};
