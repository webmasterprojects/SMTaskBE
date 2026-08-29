<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_routines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('routine_id')->constrained('routines')->restrictOnDelete();
            $table->unique(['task_id', 'routine_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_routines');
    }
};
