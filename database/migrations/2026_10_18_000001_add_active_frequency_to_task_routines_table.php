<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_routines', function (Blueprint $table) {
            $table->string('active_frequency')->nullable()->after('routine_id');
        });
    }

    public function down(): void
    {
        Schema::table('task_routines', function (Blueprint $table) {
            $table->dropColumn('active_frequency');
        });
    }
};
