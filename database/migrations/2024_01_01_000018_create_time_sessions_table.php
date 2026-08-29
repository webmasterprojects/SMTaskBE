<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('technician_id')->constrained('technicians')->restrictOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->enum('type', ['travelling', 'working', 'break', 'other'])->default('working');
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->integer('duration_mins')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['task_id', 'technician_id']);
            $table->index(['start_time', 'end_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_sessions');
    }
};
