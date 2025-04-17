<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = [
            [
                'name' => 'SuperAdmin',
                'slug' => 'superadmin'
            ],
            [
                'name' => 'Auditor',
                'slug' => 'auditor'
            ],
            [
                'name' => 'Registrador',
                'slug' => 'registrador'
            ]
        ];

        foreach ($roles as $role) {
            Role::create([
                'name' => $role['name'],
                'slug' => $role['slug']
            ]);
        }
    }
}
