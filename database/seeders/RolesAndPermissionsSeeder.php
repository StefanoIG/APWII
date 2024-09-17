<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rol;
use App\Models\Permiso;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Lista de roles y sus descripciones
        $roles = [
            ['nombre' => 'Empleado', 'descripcion' => 'Usuario con permisos limitados para gestionar productos y lotes'],
            ['nombre' => 'Owner', 'descripcion' => 'Propietario con acceso completo a sitios, empleados y productos'],
            ['nombre' => 'Admin', 'descripcion' => 'Administrador del sistema con acceso completo a todas las funciones'],
            ['nombre' => 'Demo', 'descripcion' => 'Usuario de demostración con acceso limitado'],
        ];

        // Lista de todos los permisos disponibles
        $permisos = [
            'Puede registrar usuarios',
            'Aprobar demo',
            'Negar demo',
            'Puede borrar usuarios',
            'Puede crear planes',
            'Puede actualizar planes',
            'Puede borrar planes',
            'Puede crear preguntas chatbot',
            'Puede actualizar preguntas chatbot',
            'Puede borrar preguntas chatbot',
            'Puede crear facturas',
            'Puede actualizar facturas',
            'Puede borrar facturas',
            'Puede gestionar detalles de pago',
            'Puede gestionar detalles de factura',
            'Puede gestionar roles y permisos',
            'Puede ver informacion de todos los usuarios',
            'Puede actualizar informacion de todos los usuarios',
            'Puede ver informacion usuarios de un solo sitio',
            'Puede actualizar empleados de sus sitios',
            'Puede eliminar empleados de sus sitios',
        ];

        // Crear los permisos
        foreach ($permisos as $permisoNombre) {
            Permiso::firstOrCreate(['nombre' => $permisoNombre]);
        }

        // Permisos globales que se repiten entre roles
        $permisosGlobales = [
            'Puede crear productos',
            'Puede actualizar productos',
            'Puede borrar productos',
            'Puede crear lotes',
            'Puede actualizar lotes',
            'Puede borrar lotes',
            'Puede crear comprobantes',
            'Puede descargar comprobantes',
            'Puede ver etiquetas',
            'Puede asignar etiquetas',
            'Puede crear sitios',
            'Puede actualizar sitios',
            'Puede borrar sitios',
            'Puede crear proveedores',
            'Puede actualizar proveedores',
            'Puede borrar proveedores',
            'Puede ver solo su informacion',
            'Puede actualizar solo su informcion',
        ];

        // Crear los roles y asignar permisos
        foreach ($roles as $rolData) {
            $rol = Rol::firstOrCreate(['nombre' => $rolData['nombre']], ['descripcion' => $rolData['descripcion']]);

            switch ($rolData['nombre']) {
                case 'Empleado':
                    $this->asignarPermisos($rol, array_merge($permisosGlobales, [
                        'Puede actualizar solo su informcion',
                        'Puede ver solo su informacion',
                        'Puede crear etiquetas',
                    ]));
                    break;

                case 'Owner':
                    $this->asignarPermisos($rol, array_merge($permisosGlobales, [
                        'Puede registrar usuarios',
                        'Puede actualizar empleados de sus sitios',
                        'Puede eliminar empleados de sus sitios',
                        'Puede crear proveedores',
                    ]));
                    break;

                case 'Admin':
                    $this->asignarPermisos($rol, array_merge($permisosGlobales, [
                        'Puede registrar usuarios',
                        'Puede actualizar empleados',
                        'Puede eliminar empleados',
                        'Puede crear etiquetas',
                        'Puede actualizar etiquetas',
                        'Puede borrar etiquetas',
                        'Puede crear planes',
                        'Puede actualizar planes',
                        'Puede borrar planes',
                        'Puede crear preguntas chatbot',
                        'Puede actualizar preguntas chatbot',
                        'Puede borrar preguntas chatbot',
                        'Puede crear facturas',
                        'Puede actualizar facturas',
                        'Puede borrar facturas',
                        'Puede gestionar detalles de pago',
                        'Puede gestionar detalles de factura',
                        'Puede gestionar roles y permisos',
                        'Aprobar demo',
                        'Negar demo',
                    ]));
                    break;

                case 'Demo':
                    $this->asignarPermisos($rol, array_merge($permisosGlobales, [
                        'Puede crear Lotes',
                    ]));
                    break;
            }
        }
    }

    /**
     * Asignar permisos a un rol específico.
     *
     * @param Rol $rol
     * @param array $permisos
     */
    private function asignarPermisos(Rol $rol, array $permisos)
    {
        $permisoIds = Permiso::whereIn('nombre', $permisos)->pluck('id');
        $rol->permisos()->sync($permisoIds);
    }
}
