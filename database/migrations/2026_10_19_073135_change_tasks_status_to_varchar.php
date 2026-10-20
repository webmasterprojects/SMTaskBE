<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE tasks MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'ready'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE tasks MODIFY COLUMN status ENUM('ready','scheduled','in_progress','on_hold','completed','cancelled','archived','office_review','revisit') NOT NULL DEFAULT 'ready'");
    }
};
