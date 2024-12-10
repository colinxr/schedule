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
        Schema::dropIfExists('intake_forms');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('intake_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->text('description');
            $table->string('placement');
            $table->string('size');
            $table->text('reference_images')->nullable();
            $table->string('budget_range');
            $table->string('email');
            $table->timestamps();
        });
    }
};
