<?php

namespace App\Services;

use App\Models\Comprobante;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NubefactService
{
    protected $url;
    protected $token;

    public function __construct()
    {
        $this->url = config('services.nubefact.url');
        $this->token = config('services.nubefact.token');
    }

    public function emitirComprobante(Comprobante $comprobante)
    {
        // Bypass for local environment removed as per user request to use test credentials

        if (!$this->url || !$this->token) {
            Log::warning('Nubefact credentials not configured');
            return [
                'success' => false,
                'error' => 'Nubefact credentials not configured'
            ];
        }

        $data = $this->prepararDatos($comprobante);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
            ])->post($this->url, $data);

            if ($response->successful()) {
                $body = $response->json();

                // Actualizar comprobante con respuesta de SUNAT
                $comprobante->update([
                    'sunat_success' => true,
                    'sunat_error' => null,
                    'enlace_pdf' => $body['enlace_del_pdf'] ?? null,
                    'enlace_xml' => $body['enlace_del_xml'] ?? null,
                    'enlace_cdr' => $body['enlace_del_cdr'] ?? null,
                    'sunat_description' => $body['sunat_description'] ?? null,
                ]);

                return [
                    'success' => true,
                    'data' => $body
                ];
            } else {
                $error = $response->body();
                Log::error('Nubefact Error: ' . $error);

                $comprobante->update([
                    'sunat_success' => false,
                    'sunat_error' => $error
                ]);

                return [
                    'success' => false,
                    'error' => $error
                ];
            }
        } catch (\Exception $e) {
            Log::error('Nubefact Exception: ' . $e->getMessage());

            $comprobante->update([
                'sunat_success' => false,
                'sunat_error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    protected function prepararDatos(Comprobante $comprobante)
    {
        $pedido = $comprobante->pedido;
        $cliente = $pedido->cliente_nombre ?? 'CLIENTE GENERICO';

        // Mapeo de tipo de comprobante
        $tipo_de_comprobante = match ($comprobante->tipo_comprobante) {
            'B' => 2, // BOLETA DE VENTA
            'F' => 1, // FACTURA
            'C' => 3, // NOTA DE CRÉDITO
            default => 2
        };

        // Serie y número
        $parts = explode('-', $comprobante->cod_comprobante);
        $serie = $parts[0] ?? 'B001';
        $numero = $parts[1] ?? 1;

        // Mapeo para pruebas (Nubefact Demo usa BBB1/FFF1)
        if ($serie === 'B001')
            $serie = 'BBB1';
        if ($serie === 'F001')
            $serie = 'FFF1';
        // Para Notas de Crédito en Demo
        if ($serie === 'BC01')
            $serie = 'BBB1'; // En demo suelen usar la misma serie o specíficas, ajustaremos si falla
        if ($serie === 'FC01')
            $serie = 'FFF1';

        // Additional fields for Credit Note
        $documento_modifica_tipo = "";
        $documento_modifica_serie = "";
        $documento_modifica_numero = "";
        $tipo_nota_credito = "";
        $sustento = "";

        if ($comprobante->tipo_comprobante === 'C' && $comprobante->relatedComprobante) {
            $related = $comprobante->relatedComprobante;
            $partsRelated = explode('-', $related->cod_comprobante);

            $documento_modifica_tipo = match ($related->tipo_comprobante) {
                'B' => 2,
                'F' => 1,
                default => 2
            };
            $documento_modifica_serie = $partsRelated[0] ?? '';
            $documento_modifica_numero = $partsRelated[1] ?? '';

            // Adjust series for Demo if needed
            if ($documento_modifica_serie === 'B001')
                $documento_modifica_serie = 'BBB1';
            if ($documento_modifica_serie === 'F001')
                $documento_modifica_serie = 'FFF1';

            $tipo_nota_credito = $comprobante->tipo_nota_credito ?? 1; // 1 = Anulación de la operación
            $sustento = $comprobante->sustento;
        }

        // Items
        $items = [];
        $isFactura = $comprobante->tipo_comprobante === 'F';

        foreach ($pedido->items as $item) {
            if ($isFactura) {
                // Para Factura: el precio del item se toma como valor unitario (base)
                // Se agrega IGV encima
                $valor_unitario = $item->precio_unitario;
                $igv = $valor_unitario * 0.105;
                $precio_unitario_con_igv = $valor_unitario + $igv;
            } else {
                // Para Boleta/Otros: el precio del item incluye IGV
                // Se desglosa hacia abajo
                $precio_unitario_con_igv = $item->precio_unitario;
                $valor_unitario = $precio_unitario_con_igv / 1.105;
                $igv = $precio_unitario_con_igv - $valor_unitario;
            }

            $items[] = [
                "unidad_de_medida" => "NIU",
                "codigo" => (string) $item->producto_id,
                "descripcion" => $item->producto->name,
                "cantidad" => $item->cantidad,
                "valor_unitario" => round($valor_unitario, 10),
                "precio_unitario" => round($precio_unitario_con_igv, 10),
                "descuento" => "",
                "subtotal" => round($valor_unitario * $item->cantidad, 10),
                "tipo_de_igv" => 1, // Gravado - Operación Onerosa
                "igv" => round($igv * $item->cantidad, 10),
                "total" => round($precio_unitario_con_igv * $item->cantidad, 10),
                "anticipo_regularizacion" => "false",
                "anticipo_documento_serie" => "",
                "anticipo_documento_numero" => ""
            ];
        }

        // Agregar delivery si existe
        if ($pedido->costo_delivery > 0) {
            if ($isFactura) {
                // Factura: Delivery is base, add IGV
                $valor_unitario = $pedido->costo_delivery;
                $igv = $valor_unitario * 0.105;
                $precio_unitario_con_igv = $valor_unitario + $igv;
            } else {
                // Other: Delivery includes IGV
                $precio_unitario_con_igv = $pedido->costo_delivery;
                $valor_unitario = $precio_unitario_con_igv / 1.105;
                $igv = $precio_unitario_con_igv - $valor_unitario;
            }

            $items[] = [
                "unidad_de_medida" => "ZZ",
                "codigo" => "DELIVERY",
                "descripcion" => "SERVICIO DE DELIVERY",
                "cantidad" => 1,
                "valor_unitario" => round($valor_unitario, 10),
                "precio_unitario" => round($precio_unitario_con_igv, 10),
                "descuento" => "",
                "subtotal" => round($valor_unitario, 10),
                "tipo_de_igv" => 1,
                "igv" => round($igv, 10),
                "total" => round($precio_unitario_con_igv, 10),
                "anticipo_regularizacion" => "false",
                "anticipo_documento_serie" => "",
                "anticipo_documento_numero" => ""
            ];
        }

        $total_gravada = 0;
        $total_igv = 0;

        foreach ($items as $it) {
            $total_gravada += $it['subtotal'];
            $total_igv += $it['igv'];
        }

        // Mapeo de medio de pago
        $medio_de_pago = "";

        return [
            "operacion" => "generar_comprobante",
            "tipo_de_comprobante" => $tipo_de_comprobante,
            "serie" => $serie,
            "numero" => (int) $numero,
            "sunat_transaction" => 1,
            "cliente_tipo_de_documento" => strlen($comprobante->num_ruc) == 11 ? 6 : 1, // 6: RUC, 1: DNI
            "cliente_numero_de_documento" => $comprobante->num_ruc ?: $comprobante->dni_ce_cliente ?: '00000000',
            "cliente_denominacion" => $comprobante->razon_social ?: $comprobante->nombre_cliente ?: 'CLIENTE VARIOS',
            "cliente_direccion" => $pedido->direccion_entrega ?? '',
            "cliente_email" => "",
            "cliente_email_1" => "",
            "cliente_email_2" => "",
            "fecha_de_emision" => $comprobante->fecha->format('d-m-Y'),
            "fecha_de_vencimiento" => "",
            "moneda" => 1, // SOLES
            "tipo_de_cambio" => "",
            "porcentaje_de_igv" => 10.50,
            "descuento_global" => "",
            "total_descuento" => "",
            "total_anticipo" => "",
            "total_gravada" => round($total_gravada, 2),
            "total_inafecta" => "",
            "total_exonerada" => "",
            "total_igv" => round($total_igv, 2),
            "total_gratuita" => "",
            "total_otros_cargos" => "",
            "total" => $comprobante->costo_total,
            "percepcion_tipo" => "",
            "percepcion_base_imponible" => "",
            "total_percepcion" => "",
            "total_incluido_percepcion" => "",
            "detraccion" => "false",
            "observaciones" => $comprobante->observaciones ?? '',
            "documento_que_se_modifica_tipo" => $documento_modifica_tipo,
            "documento_que_se_modifica_serie" => $documento_modifica_serie,
            "documento_que_se_modifica_numero" => (int) $documento_modifica_numero,
            "tipo_de_nota_de_credito" => $tipo_nota_credito, // 1 = ANULACION DE LA OPERACION
            "tipo_de_nota_de_debito" => "",
            "enviar_automaticamente_a_la_sunat" => "true",
            "enviar_automaticamente_al_cliente" => "false",
            "codigo_unico" => "",
            "condicion_de_pago" => "CONTADO",
            "medio_de_pago" => $medio_de_pago,
            "placa_vehiculo" => "",
            "orden_compra_servicio" => "",
            "tabla_personalizada_codigo" => "",
            "formato_de_pdf" => "",
            "items" => $items
        ];
    }
}
