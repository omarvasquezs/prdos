<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
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

        // Obtener categorías
        $soloPollos = Category::where('name', 'SOLO POLLOS')->first();
        $ofertas = Category::where('name', 'OFERTAS ESPECIALES')->first();
        $otros = Category::where('name', 'OTROS PLATOS')->first();
        $bebidas = Category::where('name', 'BEBIDAS')->first();

        $products = [
            // SOLO POLLOS
            [
                'name' => '1/4 DE POLLO',
                'description' => 'Papas fritas + Ensalada + Cremas',
                'price' => 15.00,
                'category_id' => $soloPollos->id,
                'order' => 1,
                'is_available' => true,
                'created_by' => $adminUser->id,
            ],
            [
                'name' => '1/2 DE POLLO',
                'description' => 'Papas fritas + Ensalada + Cremas',
                'price' => 28.00,
                'category_id' => $soloPollos->id,
                'order' => 2,
                'is_available' => true,
                'created_by' => $adminUser->id,
            ],
            [
                'name' => 'POLLO ENTERO',
                'description' => 'Papas fritas + Ensalada + Cremas',
                'price' => 55.00,
                'category_id' => $soloPollos->id,
                'order' => 3,
                'is_available' => true,
                'created_by' => $adminUser->id,
            ],

            // OFERTAS ESPECIALES
            [
                'name' => 'OFERTA 1',
                'description' => '01 Pollo entero + Papas fritas + Ensalada + Cremas + 1/4 de Pollo (Parie Pierna adicional)',
                'price' => 65.00,
                'category_id' => $ofertas->id,
                'order' => 1,
                'is_available' => true,
                'created_by' => $adminUser->id,
            ],
            [
                'name' => 'OFERTA 2',
                'description' => '01 Pollo entero + Papas fritas + Ensalada + Cremas + 1/4 de Pollo (Parte Pierna adicional) + 1 Gaseosa 1.5 Lt.',
                'price' => 70.00,
                'category_id' => $ofertas->id,
                'order' => 2,
                'is_available' => true,
                'created_by' => $adminUser->id,
            ],

            // OTROS PLATOS
            [
                'name' => 'CHAUFA',
                'description' => 'Plato de Chaufa de pollo + Cremas',
                'price' => 18.00,
                'category_id' => $otros->id,
                'order' => 1,
                'is_available' => true,
                'created_by' => $adminUser->id,
            ],
            [
                'name' => 'MOSTRITO',
                'description' => '1/8 de Pollo + Chaufa + Papas fritas + Ensalada + Cremas',
                'price' => 22.00,
                'category_id' => $otros->id,
                'order' => 2,
                'is_available' => true,
                'created_by' => $adminUser->id,
            ],
            [
                'name' => 'MOSTRO',
                'description' => '1/4 de Pollo + Papas fritas + Chaufa + Ensalada + Cremas',
                'price' => 25.00,
                'category_id' => $otros->id,
                'order' => 3,
                'is_available' => true,
                'created_by' => $adminUser->id,
            ],
            [
                'name' => 'SALCHIPAPA',
                'description' => 'Hot Dog + Papa fritas + Ensalada + Cremas',
                'price' => 12.00,
                'category_id' => $otros->id,
                'order' => 4,
                'is_available' => true,
                'created_by' => $adminUser->id,
            ],
            [
                'name' => 'SALCHIPOLLO',
                'description' => 'Hot Dog + Papa fritas + 1/8 de pollo + Ensalada + Cremas',
                'price' => 18.00,
                'category_id' => $otros->id,
                'order' => 5,
                'is_available' => true,
                'created_by' => $adminUser->id,
            ],
            [
                'name' => 'SALCHIROYAL',
                'description' => 'Hot Dog + Papa fritas + Huevo + Ensalada + Cremas',
                'price' => 15.00,
                'category_id' => $otros->id,
                'order' => 6,
                'is_available' => true,
                'created_by' => $adminUser->id,
            ],

            // BEBIDAS
            [
                'name' => 'GASEOSA PERSONAL',
                'description' => 'Inca Kola, Coca Cola, Sprite 500ml',
                'price' => 4.00,
                'category_id' => $bebidas->id,
                'order' => 1,
                'is_available' => true,
                'created_by' => $adminUser->id,
            ],
            [
                'name' => 'GASEOSA FAMILIAR',
                'description' => 'Inca Kola, Coca Cola, Sprite 1.5Lt',
                'price' => 8.00,
                'category_id' => $bebidas->id,
                'order' => 2,
                'is_available' => true,
                'created_by' => $adminUser->id,
            ],
            [
                'name' => 'CHICHA MORADA',
                'description' => 'Chicha morada natural (Jarra 1Lt)',
                'price' => 6.00,
                'category_id' => $bebidas->id,
                'order' => 3,
                'is_available' => true,
                'created_by' => $adminUser->id,
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }

        $this->command->info('Productos creados exitosamente.');
    }
}
