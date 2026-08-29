<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_type_failing_remarks', function (Blueprint $table) {
            $table->id();
            $table->string('uid')->unique();
            $table->foreignId('asset_type_id')->constrained('asset_types')->cascadeOnDelete();
            $table->text('remark');
            $table->string('severity')->nullable(); // Critical, Non-Critical, No-conformance, Informational, Recommendation
            $table->text('resolution')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('asset_work_history', function (Blueprint $table) {
            $table->string('severity')->nullable()->after('remarks');
            $table->text('resolution')->nullable()->after('severity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_type_failing_remarks');
        Schema::table('asset_work_history', function (Blueprint $table) {
            $table->dropColumn(['severity', 'resolution']);
        });
    }
};
