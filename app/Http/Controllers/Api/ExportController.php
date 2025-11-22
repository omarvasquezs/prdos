<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReporteIngreso;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Response;

class ExportController extends Controller
{
    public function export(Request $request)
    {
        try {
            $format = $request->query('format', 'csv');
            $dateRangeType = $request->query('dateRangeType', 'today');
            $startDate = $request->query('startDate');
            $endDate = $request->query('endDate');

            // 1. Filter Data
            $query = ReporteIngreso::with(['metodoPago', 'comprobante.user', 'comprobante.pedido'])
                ->orderByDesc('fecha');

            $this->applyDateFilter($query, $dateRangeType, $startDate, $endDate);

            $movimientos = $query->get();

            if ($movimientos->isEmpty()) {
                return response()->json(['error' => 'No hay datos para exportar en el rango seleccionado.'], 404);
            }

            $filename = 'reporte_ventas_' . now()->format('Y-m-d_His');

            // 2. Generate File based on format
            switch ($format) {
                case 'csv':
                    return $this->exportCsv($movimientos, $filename);
                case 'xlsx':
                    // Simple HTML-to-Excel workaround for now to avoid dependencies
                    return $this->exportExcelWorkaround($movimientos, $filename);
                case 'pdf':
                    return $this->exportPdf($movimientos, $filename);
                default:
                    return response()->json(['error' => 'Formato no soportado'], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al exportar datos',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function applyDateFilter($query, $type, $start, $end)
    {
        $now = Carbon::now();

        switch ($type) {
            case 'today':
                $query->whereDate('fecha', $now->toDateString());
                break;
            case 'week':
                $query->whereBetween('fecha', [$now->startOfWeek()->toDateString(), $now->endOfWeek()->toDateString()]);
                break;
            case 'month':
                $query->whereMonth('fecha', $now->month)->whereYear('fecha', $now->year);
                break;
            case 'year':
                $query->whereYear('fecha', $now->year);
                break;
            case 'custom':
                if ($start && $end) {
                    $query->whereBetween('fecha', [$start, $end]);
                }
                break;
        }
    }

    private function getTipoAtencionNombre($letra)
    {
        $map = [
            'P' => 'PRESENCIAL',
            'D' => 'DELIVERY',
            'R' => 'RECOJO'
        ];
        return $map[$letra] ?? 'MESA'; // Default to MESA/PRESENCIAL if unknown, or keep original
    }

    private function exportCsv($data, $filename)
    {
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename.csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for Excel compatibility with UTF-8
            fputs($file, "\xEF\xBB\xBF");

            // Headers
            fputcsv($file, ['Fecha', 'Comprobante', 'Tipo', 'Metodo Pago', 'Monto', 'Usuario', 'Atencion']);

            foreach ($data as $row) {
                $atencion = $row->comprobante?->pedido?->tipo_atencion ?? 'P';
                
                fputcsv($file, [
                    $row->fecha,
                    $row->cod_comprobante,
                    $row->comprobante?->tipo_comprobante_name ?? 'Nota de Venta',
                    $row->metodoPago?->nom_metodo_pago ?? 'No especificado',
                    $row->costo_total,
                    $row->comprobante?->user?->name ?? 'Sistema',
                    $this->getTipoAtencionNombre($atencion)
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportExcelWorkaround($data, $filename)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers
        $headers = ['Fecha', 'Comprobante', 'Tipo', 'Metodo Pago', 'Monto', 'Usuario', 'Atencion'];
        $sheet->fromArray($headers, null, 'A1');

        // Style Headers
        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $sheet->getStyle('A1:G1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFEEEEEE');

        // Data
        $rowNum = 2;
        foreach ($data as $row) {
            $atencion = $row->comprobante?->pedido?->tipo_atencion ?? 'P';
            $atencionNombre = $this->getTipoAtencionNombre($atencion);

            $sheet->setCellValue('A' . $rowNum, $row->fecha);
            $sheet->setCellValue('B' . $rowNum, $row->cod_comprobante);
            $sheet->setCellValue('C' . $rowNum, $row->comprobante?->tipo_comprobante_name ?? 'Nota de Venta');
            $sheet->setCellValue('D' . $rowNum, $row->metodoPago?->nom_metodo_pago ?? 'No especificado');
            $sheet->setCellValue('E' . $rowNum, $row->costo_total);
            $sheet->setCellValue('F' . $rowNum, $row->comprobante?->user?->name ?? 'Sistema');
            $sheet->setCellValue('G' . $rowNum, $atencionNombre);
            
            // Format Currency
            $sheet->getStyle('E' . $rowNum)->getNumberFormat()->setFormatCode('"S/" #,##0.00');
            
            $rowNum++;
        }

        // Auto-size columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        $callback = function () use ($writer) {
            $writer->save('php://output');
        };

        $headers = [
            "Content-Type" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            "Content-Disposition" => "attachment; filename=$filename.xlsx",
            "Cache-Control" => "max-age=0",
        ];

        return response()->stream($callback, 200, $headers);
    }

    private function exportPdf($data, $filename)
    {
        $pdf = Pdf::loadView('exports.movimientos_pdf', [
            'movimientos' => $data,
            'fecha_generacion' => now(),
            'total' => $data->sum('costo_total')
        ]);

        return $pdf->download("$filename.pdf");
    }
}
