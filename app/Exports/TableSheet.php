<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;

class TableSheet implements FromCollection, WithTitle, WithStyles
{
    protected $tenantDatabase;
    protected $table;

    public function __construct($tenantDatabase, $table)
    {
        $this->tenantDatabase = $tenantDatabase;
        $this->table = $table;
    }

    /**
     * Obtener los datos de la tabla
     */
    public function collection()
    {
        return DB::connection('tenant')->table($this->table)->get();
    }

    /**
     * Título de la hoja (nombre de la tabla)
     */
    public function title(): string
    {
        return $this->table;
    }

    /**
     * Aplicar estilos a la hoja de Excel
     */
    public function styles(Worksheet $sheet)
    {
        // Estilos para el encabezado (la primera fila)
        $sheet->getStyle('A1:Z1')->getFont()->setBold(true); // Encabezados en negrita
        $sheet->getStyle('A1:Z1')->getBorders()->getAllBorders()->setBorderStyle('thin'); // Bordes finos
        $sheet->getStyle('A1:Z1')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFEEEEEE'); // Fondo gris claro

        // Estilo para todas las celdas con datos
        $sheet->getStyle('A2:Z1000')->getBorders()->getAllBorders()->setBorderStyle('thin'); // Bordes finos en las celdas

        // Retornar el estilo
        return [
            1 => ['font' => ['bold' => true]], // Encabezado en negrita
        ];
    }
}
