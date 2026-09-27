<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('report_email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
            $table->string('to');
            $table->string('subject');
            $table->string('sent_by')->nullable();
            $table->timestamp('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_email_logs');
    }
};
