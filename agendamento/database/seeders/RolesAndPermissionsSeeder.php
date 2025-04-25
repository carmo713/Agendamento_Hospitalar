<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Reset cache de permissões
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    
        // Criar permissões
        $permissions = [
            'view dashboard',
            'edit patients',
            'manage users',
            // Adicione outras permissões conforme necessário
        ];
    
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }
    
        // Criar roles e atribuir permissões
        $admin = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $admin->givePermissionTo(Permission::all());
    
        $doctor = Role::create(['name' => 'doctor', 'guard_name' => 'web']);
        $doctor->givePermissionTo(['view dashboard', 'edit patients']);
    
        $patient = Role::create(['name' => 'patient', 'guard_name' => 'web']);
        $patient->givePermissionTo('view dashboard');
    }
}
