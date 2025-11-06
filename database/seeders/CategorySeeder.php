<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener el primer usuario (admin) como creador
        $adminUser = User::where('username', 'admin')->first();
        
        if (!$adminUser) {
            $this->command->error('Usuario admin no encontrado. Ejecuta primero UserSeeder.');
            return;
        }

        $categories = [
            [
                'name' => 'SOLO POLLOS',
                'description' => 'Pollos a la brasa en diferentes porciones',
                'order' => 1,
                'is_active' => true,
                'created_by' => $adminUser->id,
            ],
            [
                'name' => 'OFERTAS ESPECIALES',
                'description' => 'Combos y ofertas especiales de la casa',
                'order' => 2,
                'is_active' => true,
                'created_by' => $adminUser->id,
            ],
            [
                'name' => 'OTROS PLATOS',
                'description' => 'Chaufa, mostrito, salchipapas y más',
                'order' => 3,
                'is_active' => true,
                'created_by' => $adminUser->id,
            ],
            [
                'name' => 'BEBIDAS',
                'description' => 'Gaseosas, jugos e infusiones',
                'order' => 4,
                'is_active' => true,
                'created_by' => $adminUser->id,
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        $this->command->info('Categorías creadas exitosamente.');
    }
}
