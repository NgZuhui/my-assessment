<?php

namespace Modules\Auth\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AuthDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrNew(['email' => 'admin@example.com']);
        $admin->name = 'Admin User';
        $admin->password = 'password'; // hashed by the model cast
        $admin->is_admin = true;
        $admin->email_verified_at = now();
        $admin->save();
    }
}
