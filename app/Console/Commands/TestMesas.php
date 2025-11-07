<?php

namespace App\Console\Commands;

use App\Models\Mesa;
use Illuminate\Console\Command;

class TestMesas extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:mesas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test mesas API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $this->info('Testing Mesa model...');
            
            $mesas = Mesa::with('pedidoActivo')->get();
            
            $this->info('Found ' . $mesas->count() . ' mesas');
            
            foreach ($mesas as $mesa) {
                $this->line("Mesa {$mesa->num_mesa}: {$mesa->estado}");
                if ($mesa->pedidoActivo) {
                    $this->line("  - Pedido activo: {$mesa->pedidoActivo->id}");
                }
            }
            
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            $this->error('File: ' . $e->getFile() . ':' . $e->getLine());
        }
    }
}
