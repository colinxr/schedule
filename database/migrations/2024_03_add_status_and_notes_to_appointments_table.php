<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Add new status column after google_event_id
            $table->string('status')
                ->after('google_event_id')
                ->default('scheduled');

            // Add new notes column after status
            $table->text('notes')
                ->after('status')
                ->nullable();

            // Add index for status to improve query performance
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['status', 'notes']);
            $table->dropIndex(['status']);
        });
    }
}; 