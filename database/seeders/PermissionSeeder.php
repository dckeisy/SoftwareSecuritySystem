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
        $permissions = [
            [
                'name' => 'Crear'
            ],
            [
                'name' => 'Editar'
            ],
            [
                'name' => 'Borrar',
            ],
            [
                'name' => 'Ver Reportes'
            ]
        ];

        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission['name'],
                'slug' => Str::slug($permission['name'])
            ]);
        }
    }
}
