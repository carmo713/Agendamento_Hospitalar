<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            ['name' => 'admin', 'description' => 'Administrador do sistema', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'doctor', 'description' => 'Médico', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'patient', 'description' => 'Paciente', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ];

        DB::table('roles')->insert($roles);
    }
}