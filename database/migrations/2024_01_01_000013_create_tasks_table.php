<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->enum('type', ['routine', 'on_demand'])->default('routine');
            $table->string('label')->nullable();
            $table->json('tags')->nullable();
            $table->text('technician_note')->nullable();
            $table->text('invoice_note')->nullable();
            $table->json('logbook')->nullable();
            $table->enum('status', [
                'ready', 'scheduled', 'in_progress',
                'on_hold', 'completed', 'cancelled', 'archived',
            ])->default('ready');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
