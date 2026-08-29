<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignId('property_id')->nullable()->constrained('properties')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('name');
            $table->string('reference')->nullable();
            $table->string('abn_number')->nullable();
            $table->string('accounting_organisation')->nullable();
            $table->string('attention')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('bh_phone_number')->nullable();
            $table->string('ah_phone_number')->nullable();
            $table->string('fax_number')->nullable();
            $table->text('postal_address')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billings');
    }
};
