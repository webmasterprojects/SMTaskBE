<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_quote_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_quote_id')->constrained('service_quotes')->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->string('label')->nullable();
            $table->string('variant')->nullable();
            $table->string('type')->nullable();
            $table->string('serial')->nullable();
            $table->string('bar_code')->nullable();
            $table->string('make')->nullable();
            $table->string('size')->nullable();
            $table->string('model')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('cost_price', 10, 2)->default(0);
            $table->decimal('markup', 10, 2)->default(0);
            $table->decimal('sales_price', 10, 2)->default(0);
            $table->decimal('gst', 10, 2)->default(0);
            $table->string('status')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_quote_assets');
    }
};
