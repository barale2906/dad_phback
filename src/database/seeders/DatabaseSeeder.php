<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Alexander Barajas',
            'email' => 'alexanderbarajas@gmail.com',
            'rol' => 'SUPER_ADMIN',
            'tipo_usuario' => 'ADMINISTRATIVO',
            'documento' => '1000000001',
            'telefono' => '3002172663',
            'activo' => true,
        ]);
    }
}
