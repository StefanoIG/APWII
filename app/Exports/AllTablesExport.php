<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AllTablesExport implements FromCollection, WithMultipleSheets, WithHeadings, WithStyles
{
    protected $tenantDatabase;
    protected $excludedTables;

    public function __construct($tenantDatabase, $excludedTables = [])
    {
        $this->tenantDatabase = $tenantDatabase;
        $this->excludedTables = $excludedTables;
    }

    public function collection()
    {
        // Ejemplo: selecciona los datos de una tabla, debes adaptarlo para múltiples tablas
        return DB::connection('tenant')->table('users')->get();
    }

    public function headings(): array
    {
        // Retorna los nombres de las columnas como encabezados
        return DB::connection('tenant')->getSchemaBuilder()->getColumnListing('users');
    }

    public function styles(Worksheet $sheet)
    {
        // Aplica estilos generales
        $sheet->getStyle('A1:Z1')->getFont()->setBold(true); // Encabezados en negrita
        $sheet->getStyle('A1:Z1')->getBorders()->getAllBorders()->setBorderStyle('thin'); // Bordes finos
        $sheet->getStyle('A1:Z1')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFEEEEEE'); // Fondo gris claro

        // Aplica estilos específicos
        return [
            // Rango específico, por ejemplo, para los encabezados
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function sheets(): array
    {
        $tables = DB::connection('tenant')->select('SELECT name FROM sqlite_master WHERE type="table"');
        $sheets = [];

        foreach ($tables as $table) {
            if (!in_array($table->name, $this->excludedTables)) {
                $sheets[] = new TableSheet($this->tenantDatabase, $table->name);
            }
        }

        return $sheets;
    }
}
