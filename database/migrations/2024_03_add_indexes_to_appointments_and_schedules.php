<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('work_schedules')) {
            Schema::table('work_schedules', function (Blueprint $table) {
                // Composite index for work schedule lookups
                $table->index(['user_id', 'day_of_week'], 'idx_user_day_of_week');
                
                // Index for time range queries
                $table->index(['start_time', 'end_time'], 'idx_work_hours');
            });
        }

        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                // Composite index for artist's appointments by date
                $table->index(['artist_id', 'starts_at', 'ends_at'], 'idx_artist_dates');
                
                // Index for status filtering
                $table->index(['status', 'starts_at'], 'idx_status_dates');
                
                // Index for client's appointments
                $table->index(['client_id', 'starts_at'], 'idx_client_dates');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropIndex('idx_artist_dates');
                $table->dropIndex('idx_status_dates');
                $table->dropIndex('idx_client_dates');
            });
        }

        if (Schema::hasTable('work_schedules')) {
            Schema::table('work_schedules', function (Blueprint $table) {
                $table->dropIndex('idx_user_day_of_week');
                $table->dropIndex('idx_work_hours');
            });
        }
    }
}; 