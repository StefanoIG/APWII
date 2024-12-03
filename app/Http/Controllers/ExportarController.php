<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AllTablesExport;

class ExportarController extends Controller
{
    /**
     * Exportar la base de datos a Excel, excluyendo algunas tablas.
     */
    public function exportarExcel(Request $request)
    {
        $tenantDatabase = $request->header('X-Tenant');

        if (!$tenantDatabase) {
            return response()->json(['error' => 'El encabezado X-Tenant es obligatorio.'], 400);
        }

        // Lista de tablas a excluir
        $excludedTables = ['migrations', 'sqlite_sequence', 'cache_locks', 'job_batches', 'planes', 'password_resets', 'cache', 'failed_jobs', 'jobs', 'sessions', 'personal_access_tokens', 'roles', 'permisos', 'usuario_rol', 'rol_permiso', 'usuario_permiso'];  // Agrega aquí las tablas que deseas excluir
        $fileName = $tenantDatabase . '.xlsx';

        // Establecer la conexión a la base de datos del tenant
        $this->setTenantConnection($tenantDatabase);

        try {
            // Exportar las tablas a Excel y descargar directamente
            return Excel::download(new AllTablesExport($tenantDatabase, $excludedTables), $fileName);
        } catch (\Exception $e) {
            Log::error('Error al exportar la base de datos a Excel: ' . $e->getMessage());
            return response()->json(['error' => 'No se pudo exportar la base de datos a Excel.'], 500);
        }
    }

    /**
     * Exportar la base de datos a un archivo .sql, excluyendo algunas tablas.
     */
    public function exportarSQL(Request $request)
    {
        $tenantDatabase = $request->header('X-Tenant');

        if (!$tenantDatabase) {
            return response()->json(['error' => 'El encabezado X-Tenant es obligatorio.'], 400);
        }

        // Lista de tablas a excluir
        $excludedTables = ['migrations', 'sqlite_sequence', 'cache_locks', 'job_batches', 'planes', 'password_resets', 'cache', 'failed_jobs', 'jobs', 'sessions', 'personal_access_tokens', 'roles', 'permisos', 'usuario_rol', 'rol_permiso', 'usuario_permiso'];  // Agrega aquí las tablas que deseas excluir
        $fileName = $tenantDatabase . '.sql';

        // Establecer la conexión a la base de datos del tenant
        $this->setTenantConnection($tenantDatabase);

        try {
            // Generar el contenido SQL en memoria
            $sqlContent = $this->generateSQLDump($excludedTables);

            // Devolver el archivo SQL como respuesta para descargar
            return response($sqlContent, 200, [
                'Content-Type' => 'application/sql',
                'Content-Disposition' => "attachment; filename=\"$fileName\"",
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
     * Generar el volcado SQL de todas las tablas, excluyendo las especificadas.
     */
    protected function generateSQLDump($excludedTables = [])
    {
        $tables = DB::connection('tenant')->select('SELECT name FROM sqlite_master WHERE type="table"');
        $sqlContent = '';

        foreach ($tables as $table) {
            $tableName = $table->name;

            // Excluir las tablas que están en la lista de exclusión
            if (in_array($tableName, $excludedTables)) {
                continue;
            }

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
