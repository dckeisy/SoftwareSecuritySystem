<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Los 4 permisos básicos definidos en UTN-05
        $permissions = [
            [
                'name' => 'Crear',
                'description' => 'Permite crear registros.'
            ],
            [
                'name' => 'Editar',
                'description' => 'Permite editar registros.'
            ],
            [
                'name' => 'Borrar',
                'description' => 'Permite borrar registros.'
            ],
            [
                'name' => 'Ver Reportes',
                'description' => 'Permite acceder a las listas.'
            ]
        ];

        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission['name'],
                'slug' => Str::slug($permission['name']),
                'description' => $permission['description']
            ]);
        }
    }
}
