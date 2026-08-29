<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin account
        User::firstOrCreate(
            ['email' => 'admin@fireinspect.local'],
            [
                'name'        => 'Admin User',
                'password'    => Hash::make('password'),
                'role'        => 'admin',
                'status'      => 'active',
                'permissions' => [],
            ]
        );

        // Regular user with all permissions
        User::firstOrCreate(
            ['email' => 'inspector@fireinspect.local'],
            [
                'name'        => 'Fire Inspector',
                'password'    => Hash::make('password'),
                'role'        => 'user',
                'status'      => 'active',
                'permissions' => [
                    'task.view', 'task.create', 'task.edit',
                    'client.view', 'property.view',
                    'asset.view', 'routine.view',
                    'task.time.start', 'task.time.end',
                    'report.time.view',
                ],
            ]
        );
    }
}
