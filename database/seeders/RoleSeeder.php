<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdminRole = Role::create([
            'name' => 'SuperAdmin',
            'slug' => 'superadmin',
            'description' => 'Administrador con acceso completo al sistema'
        ]);
        
        $auditorRole = Role::create([
            'name' => 'Auditor',
            'slug' => 'auditor',
            'description' => 'Solo puede ver la lista de usuarios y productos'
        ]);
        
        $registradorRole = Role::create([
            'name' => 'Registrador',
            'slug' => 'registrador',
            'description' => 'Puede gestionar productos y ver usuarios'
        ]);

        $superAdminRole->permissions()->attach(Permission::all());
        
        $auditorRole->permissions()->attach(
            Permission::where('slug', 'ver-reportes')->first()
        );
        
        $registradorRole->permissions()->attach([
            Permission::where('slug', 'crear')->first()->id,
            Permission::where('slug', 'editar')->first()->id,
            Permission::where('slug', 'borrar')->first()->id,
            Permission::where('slug', 'ver-reportes')->first()->id
        ]);
    }
}
