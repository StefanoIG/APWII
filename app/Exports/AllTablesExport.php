<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AllTablesExport implements WithMultipleSheets
{
    protected $tenantDatabase;
    protected $excludedTables;

    public function __construct($tenantDatabase, $excludedTables = [])
    {
        $this->tenantDatabase = $tenantDatabase;
        $this->excludedTables = $excludedTables;
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