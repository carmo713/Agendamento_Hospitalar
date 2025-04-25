<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        // Criar usuário admin
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin3@example.com',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('patient');
    }
}