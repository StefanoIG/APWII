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

        $permisos = [
            'Puede registrar usuarios',
            'Aprobar demo',
            'Negar demo',
            'Puede actualizar usuarios',
            'Puede borrar usuarios',
            'Puede crear sitios',
            'Puede actualizar sitios',
            'Puede borrar sitios',
            'Puede crear productos',
            'Puede actualizar productos',
            'Puede borrar productos',
            'Puede crear lotes',
            'Puede actualizar lotes',
            'Puede borrar lotes',
            'Puede crear comprobantes',
            'Puede descargar comprobantes',
            'Puede crear etiquetas',
            'Puede actualizar etiquetas',
            'Puede borrar etiquetas',
            'Puede asignar etiquetas',
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
            'Puede crear proveedores',
            'Puede actualizar proveedores',
            'Puede borrar proveedores',
            'Puede gestionar roles y permisos',
            'Puede ver informacion de todos los usuarios',
            'Puede actualizar informacion de todos los usuarios',
            'Puede ver informacion usuarios de un solo sitio',
            'Puede ver solo su informacion',
            'Puede actualizar solo su informcion',
            'Puede actualizar empleados de sus sitios',
            'Puede eliminar empleados de sus sitios',
        ];

        // Crear los permisos
        foreach ($permisos as $permisoNombre) {
            Permiso::firstOrCreate(['nombre' => $permisoNombre]);
        }

        // Crear los roles y asignar permisos
        foreach ($roles as $rolData) {
            $rol = Rol::firstOrCreate(['nombre' => $rolData['nombre']], ['descripcion' => $rolData['descripcion']]);

            switch ($rolData['nombre']) {
                case 'Empleado':
                    $rol->permisos()->sync(Permiso::whereIn('nombre', [
                        'Puede actualizar solo su informcion',
                        'Puede ver solo su informacion',
                        'Puede crear productos',
                        'Puede actualizar productos',
                        'Puede borrar productos',
                        'Puede crear lotes',
                        'Puede actualizar lotes',
                        'Puede borrar lotes',
                        'Puede crear comprobantes',
                        'Puede descargar comprobantes',
                    ])->pluck('id'));
                    break;

                case 'Owner':
                    $rol->permisos()->sync(Permiso::whereIn('nombre', [
                        'Puede registrar usuarios (empleados)',
                        'Puede actualizar empleados de sus sitios',
                        'Puede eliminar empleados de sus sitios',
                        'Puede crear sitios',
                        'Puede actualizar sitios',
                        'Puede borrar sitios',
                        'Puede crear productos',
                        'Puede actualizar productos',
                        'Puede borrar productos',
                        'Puede crear lotes',
                        'Puede actualizar lotes',
                        'Puede borrar lotes',
                        'Puede crear comprobantes',
                        'Puede descargar comprobantes',
                        'Puede asignar etiquetas',
                        'Puede crear proveedores',
                        'Puede actualizar proveedores',
                        'Puede borrar proveedores',
                    ])->pluck('id'));
                    break;

                case 'Admin':
                    $rol->permisos()->sync(Permiso::whereIn('nombre', [
                        'Puede registrar usuarios (empleados)',
                        'Puede actualizar empleados',
                        'Puede eliminar empleados',
                        'Puede crear sitios',
                        'Puede actualizar sitios',
                        'Puede borrar sitios',
                        'Puede crear productos',
                        'Puede actualizar productos',
                        'Puede borrar productos',
                        'Puede crear lotes',
                        'Puede actualizar lotes',
                        'Puede borrar lotes',
                        'Puede crear comprobantes',
                        'Puede descargar comprobantes',
                        'Puede crear etiquetas',
                        'Puede actualizar etiquetas',
                        'Puede borrar etiquetas',
                        'Puede asignar etiquetas',
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
                        'Puede crear proveedores',
                        'Puede actualizar proveedores',
                        'Puede borrar proveedores',
                        'Puede gestionar roles y permisos',
                        'Aprobar demo',
                        'Negar demo',
                    ])->pluck('id'));
                    break;

                case 'Demo':
                    // Los roles Demo no tienen permisos completos.
                    $rol->permisos()->sync(Permiso::whereIn('nombre', [
                        'Puede crear productos',
                        'Puede actualizar productos',
                        'Puede descargar comprobantes',
                        'Puede crear Lotes',
                        'Puede actualizar lotes',
                        'Puede borrar lotes',
                        'Puede crear comprobantes',
                    ])->pluck('id'));
                    break;
            }
        }
    }
}
