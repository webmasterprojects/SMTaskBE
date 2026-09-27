<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_quote_assets', function (Blueprint $table) {
            $table->string('line_type')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('service_quote_assets', function (Blueprint $table) {
            $table->dropColumn('line_type');
        });
    }
};
