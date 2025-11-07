<?php

namespace Database\Seeders;

use App\Models\Mesa;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class PedidoSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Obtener mesas ocupadas y algunos productos
        $mesasOcupadas = Mesa::where('estado', 'O')->get();
        $productos = Product::take(6)->get();

        if ($mesasOcupadas->isEmpty() || $productos->isEmpty()) {
            $this->command->info('⚠️ No hay mesas ocupadas o productos disponibles para crear pedidos');
            return;
        }

        foreach ($mesasOcupadas as $mesa) {
            // Crear pedido para la mesa
            $pedido = Pedido::create([
                'mesa_id' => $mesa->id,
                'comensales' => rand(1, 6),
                'estado' => 'A',
                'fecha_apertura' => Carbon::now()->subMinutes(rand(10, 120)),
                'total' => 0.00
            ]);

            // Agregar algunos items al pedido
            $numItems = rand(2, 5);
            $total = 0;

            for ($i = 0; $i < $numItems; $i++) {
                $producto = $productos->random();
                $cantidad = rand(1, 3);
                $precioUnitario = $producto->price;

                PedidoItem::create([
                    'pedido_id' => $pedido->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precioUnitario
                ]);

                $total += $cantidad * $precioUnitario;
            }

            // Actualizar el total del pedido
            $pedido->update(['total' => $total]);

            $this->command->info("✅ Pedido creado para Mesa {$mesa->num_mesa} - Total: S/ {$total}");
        }

        $this->command->info('✅ Pedidos de ejemplo creados exitosamente');
    }
}
