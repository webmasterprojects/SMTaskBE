<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('property_id')->nullable()->constrained('properties')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->foreignId('billing_id')->nullable()->constrained('billings')->nullOnDelete();
            $table->boolean('is_defect_quote')->default(false);
            $table->text('description')->nullable();
            $table->date('review_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sales_person_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('tags')->nullable();
            $table->text('scope_of_work')->nullable();
            $table->text('terms_and_condition')->nullable();
            $table->text('internal_note')->nullable();
            $table->enum('status', ['draft', 'sent', 'approved', 'declined', 'cancelled'])->default('draft');
            $table->decimal('total_cost_price', 12, 2)->default(0);
            $table->decimal('total_markup', 12, 2)->default(0);
            $table->decimal('sub_total', 12, 2)->default(0);
            $table->decimal('total_sales_price', 12, 2)->default(0);
            $table->decimal('total_gst', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('total_profit', 12, 2)->default(0);
            $table->integer('total_quantity')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_quotes');
    }
};
