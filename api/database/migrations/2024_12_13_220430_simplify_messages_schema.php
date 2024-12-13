<?php

namespace Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('messages', function (Blueprint $table) {
            // First rename sender_id to user_id
            $table->renameColumn('sender_id', 'user_id');
            // Then drop sender_type column
            $table->dropColumn('sender_type');
        });
    }

    public function down()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('sender_type')->after('content');
            $table->renameColumn('user_id', 'sender_id');
        });
    }
};