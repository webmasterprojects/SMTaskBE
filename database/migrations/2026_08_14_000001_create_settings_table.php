<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group');          // section|sub_section|severity|remark_template|resolution_template
            $table->string('key')->nullable(); // parent reference (e.g. section name for sub_sections)
            $table->string('value');           // display name / text
            $table->json('meta')->nullable();  // { warning_color, text_color, sort_order, default_resolution }
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['group', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
