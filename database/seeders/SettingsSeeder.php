<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Default severities
        $severities = [
            ['value' => 'Critical',  'meta' => ['warning_color' => '#dc2626', 'text_color' => '#ffffff'], 'sort_order' => 1],
            ['value' => 'Major',     'meta' => ['warning_color' => '#ea580c', 'text_color' => '#ffffff'], 'sort_order' => 2],
            ['value' => 'Minor',     'meta' => ['warning_color' => '#ca8a04', 'text_color' => '#ffffff'], 'sort_order' => 3],
            ['value' => 'Advisory',  'meta' => ['warning_color' => '#2563eb', 'text_color' => '#ffffff'], 'sort_order' => 4],
        ];

        foreach ($severities as $s) {
            DB::table('settings')->insertOrIgnore([
                'group'      => 'severity',
                'key'        => null,
                'value'      => $s['value'],
                'meta'       => json_encode($s['meta']),
                'is_active'  => true,
                'sort_order' => $s['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Default sections
        $sections = ['Safety - Compliance', 'Property Services'];
        foreach ($sections as $i => $s) {
            DB::table('settings')->insertOrIgnore([
                'group'      => 'section',
                'key'        => null,
                'value'      => $s,
                'meta'       => null,
                'is_active'  => true,
                'sort_order' => $i,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Default sub-sections
        $subSections = [
            ['key' => 'Safety - Compliance', 'value' => 'Fire Protection'],
            ['key' => 'Safety - Compliance', 'value' => 'Security Systems'],
            ['key' => 'Property Services',   'value' => 'Plumbing'],
            ['key' => 'Property Services',   'value' => 'Electrical'],
        ];
        foreach ($subSections as $i => $s) {
            DB::table('settings')->insertOrIgnore([
                'group'      => 'sub_section',
                'key'        => $s['key'],
                'value'      => $s['value'],
                'meta'       => null,
                'is_active'  => true,
                'sort_order' => $i,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Access Schedule options
        $schedules = [
            ['key' => 'all_hours',        'value' => 'All Hours',          'sort_order' => 1],
            ['key' => 'business_hours',   'value' => 'Business Hours',     'sort_order' => 2],
            ['key' => 'restricted_hours', 'value' => 'Restricted Hours',   'sort_order' => 3],
            ['key' => 'make_appointment', 'value' => 'Make Appointment',   'sort_order' => 4],
            ['key' => 'pick_up_key',      'value' => 'Pick Up Key',        'sort_order' => 5],
        ];
        foreach ($schedules as $s) {
            DB::table('settings')->insertOrIgnore([
                'group'      => 'access_schedule',
                'key'        => $s['key'],
                'value'      => $s['value'],
                'meta'       => null,
                'is_active'  => true,
                'sort_order' => $s['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Access Procedure options
        $procedures = [
            ['key' => 'identity_card', 'value' => 'Identity Card', 'sort_order' => 1],
            ['key' => 'use_pin_code',  'value' => 'Use PIN Code',  'sort_order' => 2],
        ];
        foreach ($procedures as $s) {
            DB::table('settings')->insertOrIgnore([
                'group'      => 'access_procedure',
                'key'        => $s['key'],
                'value'      => $s['value'],
                'meta'       => null,
                'is_active'  => true,
                'sort_order' => $s['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
