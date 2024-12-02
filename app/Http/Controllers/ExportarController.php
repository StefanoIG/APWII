<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AllTablesExport;

class ExportarController extends Controller
{
    /**
     * Exportar la base de datos a Excel.
     */
    public function exportarExcel(Request $request)
    {
        $tenantDatabase = $request->header('X-Tenant');
        
        if (!$tenantDatabase) {
            return response()->json(['error' => 'El encabezado X-Tenant es obligatorio.'], 400);
        }

        $fileName = $tenantDatabase . '.xlsx';

        // Establecer la conexión a la base de datos del tenant
        $this->setTenantConnection($tenantDatabase);

        try {
            // Exportar las tablas a Excel usando el paquete maatwebsite/excel
            return Excel::download(new AllTablesExport($tenantDatabase), $fileName);
        } catch (\Exception $e) {
            Log::error('Error al exportar la base de datos a Excel: ' . $e->getMessage());
            return response()->json(['error' => 'No se pudo exportar la base de datos a Excel.'], 500);
        }
    }

    /**
     * Exportar la base de datos a un archivo .sql
     */
    public function exportarSQL(Request $request)
    {
        $tenantDatabase = $request->header('X-Tenant');
        
        if (!$tenantDatabase) {
            return response()->json(['error' => 'El encabezado X-Tenant es obligatorio.'], 400);
        }

        $fileName = $tenantDatabase . '.sql';

        // Establecer la conexión a la base de datos del tenant
        $this->setTenantConnection($tenantDatabase);

        try {
            // Generar el archivo SQL
            $sqlContent = $this->generateSQLDump();

            // Guardar el archivo SQL en el almacenamiento
            Storage::put('exports/' . $fileName, $sqlContent);

            return response()->json([
                'message' => 'Archivo SQL generado correctamente',
                'file_url' => Storage::url('exports/' . $fileName)
            ]);
        } catch (\Exception $e) {
            Log::error('Error al exportar la base de datos a SQL: ' . $e->getMessage());
            return response()->json(['error' => 'No se pudo exportar la base de datos a SQL.'], 500);
        }
    }

    /**
     * Establecer la conexión a la base de datos del tenant.
     */
    protected function setTenantConnection($tenantDatabase)
    {
        config(['database.connections.tenant' => [
            'driver' => 'sqlite',
            'database' => database_path('tenants/' . $tenantDatabase),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);

        DB::purge('tenant');
        DB::reconnect('tenant');
        DB::setDefaultConnection('tenant');
    }

    /**
     * Generar el volcado SQL de todas las tablas.
     */
    protected function generateSQLDump()
    {
        $tables = DB::connection('tenant')->select('SELECT name FROM sqlite_master WHERE type="table"');
        $sqlContent = '';

        foreach ($tables as $table) {
            $tableName = $table->name;

            // Obtener el esquema de la tabla
            $createTableQuery = DB::connection('tenant')->select("SELECT sql FROM sqlite_master WHERE type='table' AND name=?", [$tableName]);
            if (!empty($createTableQuery)) {
                $sqlContent .= $createTableQuery[0]->sql . ";\n\n";
            }

            // Obtener los datos de la tabla
            $rows = DB::connection('tenant')->table($tableName)->get();
            foreach ($rows as $row) {
                $columns = implode(", ", array_keys((array) $row));
                $values = implode(", ", array_map(function ($value) {
                    return "'" . addslashes($value) . "'";
                }, (array) $row));

                $sqlContent .= "INSERT INTO $tableName ($columns) VALUES ($values);\n";
            }

            $sqlContent .= "\n";
        }

        return $sqlContent;
    }
}
