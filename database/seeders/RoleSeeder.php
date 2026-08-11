<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Administrator',
                'slug' => Role::ADMIN,
                'description' => 'Full access to every part of the platform.',
            ],
            [
                'name' => 'Editor',
                'slug' => Role::EDITOR,
                'description' => 'Manages and publishes all content, but not users or site settings.',
            ],
            [
                'name' => 'Author',
                'slug' => Role::AUTHOR,
                'description' => 'Writes and manages only their own posts.',
            ],
            [
                'name' => 'User',
                'slug' => Role::USER,
                'description' => 'Registered reader. No admin panel access.',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
