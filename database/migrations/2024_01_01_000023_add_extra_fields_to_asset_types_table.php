<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_types', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('name');
            $table->json('tags')->nullable()->after('is_active');
            $table->string('category')->nullable()->after('tags');
            $table->string('sub_category')->nullable()->after('category');
            $table->string('classification')->nullable()->after('sub_category');
        });
    }

    public function down(): void
    {
        Schema::table('asset_types', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'tags', 'category', 'sub_category', 'classification']);
        });
    }
};
