<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->restrictOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('name');
            $table->enum('billing_type', ['fixed', 'do_and_charge'])->default('fixed');
            $table->string('recurrence')->nullable();
            $table->date('contract_start_date')->nullable();
            $table->date('contract_finish_date')->nullable();
            $table->date('first_invoice_date')->nullable();
            $table->date('next_invoice_date')->nullable();
            $table->date('review_date')->nullable();
            $table->string('price_increase_policy')->nullable();
            $table->enum('status', ['draft', 'active', 'expired', 'cancelled'])->default('draft');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
