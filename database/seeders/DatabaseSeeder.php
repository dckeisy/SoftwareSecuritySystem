<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear primero las entidades, permisos y roles
        $this->call([
            EntitySeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
        ]);
        
        // Crear usuarios sin roles asignados
        $users = User::factory(10)->create();
        
        // Obtener los roles creados por el seeder
        $superadmin = Role::where('slug', 'superadmin')->first();
        $auditor = Role::where('slug', 'auditor')->first();
        $registrador = Role::where('slug', 'registrador')->first();
        
        if ($superadmin) {
            // Asignar el primer usuario como superadmin
            $users[0]->update([
                'role_id' => $superadmin->id,
                'password' => bcrypt('Isw@2025admin')
            ]);
        }
        
        if ($auditor) {
            // Asignar los siguientes 3 usuarios como auditores
            foreach($users->slice(1, 3) as $user) {
                $user->update(['role_id' => $auditor->id]);
            }
        }
        
        if ($registrador) {
            // Asignar los usuarios restantes como registradores
            foreach($users->slice(4) as $user) {
                $user->update(['role_id' => $registrador->id]);
            }
        }
        
        // Ahora asignar los permisos a los roles
        $this->call([
            RoleEntityPermissionSeeder::class
        ]);
    }
}
