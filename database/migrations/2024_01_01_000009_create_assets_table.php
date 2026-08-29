<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->restrictOnDelete();
            $table->foreignId('asset_type_id')->constrained('asset_types')->restrictOnDelete();
            $table->foreignId('asset_type_variant_id')->nullable()->constrained('asset_type_variants')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('label')->nullable();
            $table->string('field_id')->nullable();
            $table->string('level')->nullable();
            $table->string('internal_reference')->nullable();
            $table->string('serial')->nullable();
            $table->string('bar_code')->nullable();
            $table->string('make')->nullable();
            $table->string('size')->nullable();
            $table->string('model')->nullable();
            $table->integer('quantity')->default(1);
            $table->date('base_date')->nullable();
            $table->date('installation_date')->nullable();
            $table->text('internal_notes')->nullable();
            $table->json('tags')->nullable();
            $table->string('contractor')->nullable();
            $table->string('location')->nullable();
            $table->text('standard_of_maintenance')->nullable();
            $table->text('standard_of_installation')->nullable();
            $table->text('standard_of_performance')->nullable();
            $table->boolean('is_not_on_occupancy_permit')->default(false);
            $table->boolean('is_silent')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
