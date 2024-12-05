<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TableSheet implements FromCollection, WithTitle, WithStyles, WithHeadings
{
    protected $tenantDatabase;
    protected $table;
    protected $columns;

    public function __construct($tenantDatabase, $table)
    {
        $this->tenantDatabase = $tenantDatabase;
        $this->table = $table;
        $this->columns = $this->getFormattedColumns();
    }

    /**
     * Obtener los datos de la tabla
     */
    public function collection()
    {
        return DB::connection('tenant')->table($this->table)->get();
    }

    /**
     * Formatear nombres de columnas para una mejor presentación
     */
    protected function getFormattedColumns()
    {
        $columns = DB::connection('tenant')->getSchemaBuilder()->getColumnListing($this->table);

        $formattedColumns = [];
        foreach ($columns as $column) {
            // Convertir snake_case a Title Case
            $formattedName = Str::of($column)
                ->snake()
                ->replace('_', ' ')
                ->title();

            $formattedColumns[$column] = $formattedName;
        }

        return $formattedColumns;
    }

    /**
     * Título de la hoja (nombre de la tabla)
     */
    public function title(): string
    {
        return Str::of($this->table)->title();
    }

    /**
     * Encabezados de la hoja
     */
    public function headings(): array
    {
        return array_values($this->columns);
    }

    /**
     * Aplicar estilos avanzados a la hoja de Excel
     */
    public function styles(Worksheet $sheet)
    {
        // Obtener el rango máximo de columnas
        $maxColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($this->columns));
        $headerRange = "A1:{$maxColumn}1";

        // Estilos para el encabezado
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '000000'],
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E0E0E0'], // Light gray background
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Auto-size columns
        foreach (range('A', $maxColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Estilos generales para las celdas de datos
        $dataRange = "A2:{$maxColumn}1000";
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '888888'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        return [];
    }
}
