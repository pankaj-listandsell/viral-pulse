<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Credentials come from the environment, never from source. If no password
     * is configured a strong one is generated and printed once - there is no
     * default password to forget to change.
     */
    public function run(): void
    {
        $email = config('site.admin.email');
        $password = config('site.admin.password');
        $generated = false;

        if (blank($password)) {
            $password = Str::password(20);
            $generated = true;
        }

        $admin = User::withTrashed()->firstWhere('email', $email);

        if ($admin) {
            $this->command?->warn("Admin user {$email} already exists - left unchanged.");

            return;
        }

        User::create([
            'role_id' => Role::where('slug', Role::ADMIN)->value('id'),
            'name' => config('site.admin.name'),
            'username' => 'admin',
            'email' => $email,
            'password' => $password,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $this->command?->info("Admin user created: {$email}");

        if ($generated) {
            $this->command?->warn("Generated password (shown once): {$password}");
            $this->command?->warn('Set ADMIN_PASSWORD in .env to control this yourself.');
        }
    }
}
