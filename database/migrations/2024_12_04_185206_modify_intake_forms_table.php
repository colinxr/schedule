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
        Schema::table('intake_forms', function (Blueprint $table) {
            // Drop columns
            $table->dropColumn([
                'tattoo_style',
                'has_previous_tattoos',
                'medical_conditions',
                'additional_notes'
            ]);

            // Rename column
            $table->renameColumn('tattoo_description', 'description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intake_forms', function (Blueprint $table) {
            // Add back columns
            $table->string('tattoo_style');
            $table->boolean('has_previous_tattoos')->default(false);
            $table->text('medical_conditions')->nullable();
            $table->text('additional_notes')->nullable();

            // Rename back
            $table->renameColumn('description', 'tattoo_description');
        });
    }
};
