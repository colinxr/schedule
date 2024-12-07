<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('day_of_week'); // 0 = Sunday, 1 = Monday, etc.
            $table->time('start_time');  // This ensures proper time format
            $table->time('end_time');    // This ensures proper time format
            $table->string('timezone')->default('America/New_York');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Prevent duplicate schedules for the same day
            $table->unique(['user_id', 'day_of_week']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('work_schedules');
    }
}; 