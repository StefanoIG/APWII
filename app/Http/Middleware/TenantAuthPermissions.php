<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
use Stancl\Tenancy\Facades\Tenancy;

class TenantAuthPermissions
{
    public function handle(Request $request, Closure $next, $requiredPermissions = null)
    {
        // Autenticación JWT
        $token = $request->bearerToken();
        if (!$token || !JWTAuth::setToken($token)->check()) {
            return response()->json(['error' => 'No autorizado'], 401);
        }

        $user = Auth::user();

        // Verificar si el usuario es un administrador
        $esAdmin = $user->roles->contains('nombre', 'Admin');

        // Conectar al tenant solo si el usuario no es admin
        if (!$esAdmin) {
            // Obtener el tenant desde el header o el parámetro de consulta
            $tenantId = $request->header('X-Tenant') ?? $request->query('tenant');

            if ($tenantId) {
                $tenant = \App\Models\Tenant::find($tenantId);

                if ($tenant) {
                    Tenancy::initialize($tenant);
                } else {
                    return response()->json(['error' => 'Tenant no encontrado'], 404);
                }
            } else {
                return response()->json(['error' => 'Tenant no especificado'], 400);
            }
        }

        // Verificación de permisos
        if ($requiredPermissions) {
            $permissions = explode(',', $requiredPermissions);
            $hasPermission = $user->roles->some(fn($rol) => 
                $rol->permisos()->whereIn('nombre', $permissions)->exists()
            );

            if (!$hasPermission) {
                return response()->json(['error' => 'Permiso insuficiente'], 403);
            }
        }

        return $next($request);
    }
}

