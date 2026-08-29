<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_assets', function (Blueprint $table) {
            $table->string('status')->nullable()->after('asset_id');   // PASS | FAIL | NO_TEST | null
            $table->text('remarks')->nullable()->after('status');
            $table->timestamp('tested_at')->nullable()->after('remarks');
            $table->foreignId('tested_by')->nullable()->after('tested_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('task_assets', function (Blueprint $table) {
            $table->dropForeign(['tested_by']);
            $table->dropColumn(['status', 'remarks', 'tested_at', 'tested_by']);
        });
    }
};
