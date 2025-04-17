<?php

namespace Database\Seeders;

use App\Models\Entity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EntitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $entities = [
            [
                'name' => 'Usuarios',
                'slug' => 'usuarios'
            ],
            [
                'name' => 'Roles',
                'slug' => 'roles'
            ],
            [
                'name' => 'Productos',
                'slug' => 'productos'
            ]
        ];

        foreach ($entities as $entity) {
            Entity::create($entity);
        }
    }
}
