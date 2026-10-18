<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE service_quotes MODIFY COLUMN status ENUM('draft','sent','approved','declined','cancelled','invoice_done','repair_task_created') NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE service_quotes MODIFY COLUMN status ENUM('draft','sent','approved','declined','cancelled') NOT NULL DEFAULT 'draft'");
    }
};
