<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $statuses = [
            ['key' => 'scheduled',   'value' => 'Scheduled',   'color' => '#6c757d', 'show_on_load' => true],
            ['key' => 'ready',       'value' => 'Ready',       'color' => '#ffc107', 'show_on_load' => true],
            ['key' => 'in_progress', 'value' => 'In Progress', 'color' => '#0dcaf0', 'show_on_load' => true],
            ['key' => 'on_hold',     'value' => 'On Hold',     'color' => '#fd7e14', 'show_on_load' => true],
            ['key' => 'completed',   'value' => 'Completed',   'color' => '#198754', 'show_on_load' => false],
            ['key' => 'cancelled',   'value' => 'Cancelled',   'color' => '#dc3545', 'show_on_load' => false],
            ['key' => 'archived',    'value' => 'Archived',    'color' => '#adb5bd', 'show_on_load' => false],
        ];

        foreach ($statuses as $i => $s) {
            $exists = DB::table('settings')
                ->where('group', 'task_status')
                ->where('key', $s['key'])
                ->exists();

            if (!$exists) {
                DB::table('settings')->insert([
                    'group'      => 'task_status',
                    'key'        => $s['key'],
                    'value'      => $s['value'],
                    'is_active'  => true,
                    'sort_order' => $i,
                    'meta'       => json_encode([
                        'color'        => $s['color'],
                        'show_on_load' => $s['show_on_load'],
                        'is_system'    => true,
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('group', 'task_status')->delete();
    }
};
