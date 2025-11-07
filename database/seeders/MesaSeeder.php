<?php

namespace Database\Seeders;

use App\Models\Mesa;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MesaSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Crear 8 mesas para el restaurante
        $mesas = [
            ['num_mesa' => 1, 'estado' => 'D'],
            ['num_mesa' => 2, 'estado' => 'D'],
            ['num_mesa' => 3, 'estado' => 'O'], // Una mesa ocupada para ejemplo
            ['num_mesa' => 4, 'estado' => 'D'],
            ['num_mesa' => 5, 'estado' => 'D'],
            ['num_mesa' => 6, 'estado' => 'D'],
            ['num_mesa' => 7, 'estado' => 'O'], // Otra mesa ocupada
            ['num_mesa' => 8, 'estado' => 'D'],
        ];

        foreach ($mesas as $mesa) {
            Mesa::create($mesa);
        }

        $this->command->info('✅ Mesas creadas exitosamente');
    }
}
