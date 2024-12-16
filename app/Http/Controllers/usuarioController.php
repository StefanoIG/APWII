<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
//mails
use App\Mail\RecoveryPassword;
use App\Mail\WelcomeMail;
use App\Mail\OwnerMail;
use App\Mail\DemoMail;
use App\Mail\RequestDemoMail;
use App\Mail\ConfirmarPagoTransferencia;
use App\Mail\PagoPendienteTransferencia;
use App\Mail\ConfirmacionPagoMail;
use App\Mail\RechazoPagoMail;
//Modelos
use App\Models\Demo;
use App\Models\Rol;
use App\Mail\DemoRejectedMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\Planes;
use App\Models\Factura;
use App\Models\DetalleFactura;
use App\Models\Tenant;
use App\Models\Sitio;
use Illuminate\Support\Facades\File;
use Srmklive\PayPal\Services\PayPal as PayPalClient; // Importa el cliente PayPal

class UsuarioController extends Controller


{
    /**
     * Verifica si el usuario autenticado tiene un permiso específico.
     *
     * @param string $permisoNombre
     * @return bool
     */
    private function verificarPermiso($permisoNombre)
    {
        $user = Auth::user();

        // Obtener los roles asociados al usuario
        $roles = $user->roles; // Asumiendo que el modelo Usuario tiene una relación con roles

        // Iterar sobre cada rol del usuario
        foreach ($roles as $rol) {
            // Verificar si el rol tiene el permiso requerido
            if ($rol->permisos()->where('nombre', $permisoNombre)->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtener el ID del rol por nombre.
     */
    private function getRoleIdByName($roleName)
    {
        return Rol::where('nombre', $roleName)->first()->id;
    }


    /**
     * Establecer la conexión al tenant correspondiente usando el nombre de la base de datos.
     */
    protected function setTenantConnection($databaseName)
    {
        // Configurar la conexión a la base de datos del tenant
        config(['database.connections.tenant' => [
            'driver' => 'sqlite',
            'database' => database_path('tenants/' . $databaseName),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);

        // Purga la conexión anterior y reconecta con el tenant
        DB::purge('tenant');
        DB::reconnect('tenant');

        // Establecer el nombre de la conexión de forma predeterminada
        DB::setDefaultConnection('tenant');
    }



    /**
     * Mostrar la lista de usuarios.
     */
    public function index(Request $request)
    {
        // Obtener el usuario autenticado
        $user = Auth::user();

        // Verificar el rol del usuario
        if ($this->verificarRol('Owner')) {
            // Si es Owner, obtener usuarios paginados
            $usuarios = DB::connection('tenant')->table('usuarios')->paginate(10); // Cambia 10 por el número de resultados por página deseados
        } else {
            // Si es un empleado, solo puede ver su propia información
            $usuarios = DB::connection('tenant')->table('usuarios')->where('id', $user->id)->get();
        }

        return response()->json($usuarios);
    }

    //funcion index para admin
    public function indexAdmins(Request $request)
    {
        //autenticar usuario
        $user = Auth::user();

        
            //si es admin, obtener usuarios paginados
            $usuarios = Usuario::paginate(10);
        
        return response()->json($usuarios);
    }


    //Funcion de show
    public function show(Request $request, $id)
    {
        // Obtener el usuario autenticado
        $user = Auth::user();

        // Verificar si el usuario tiene permiso para ver el perfil solicitado
        if ($this->verificarRol('Owner')) {
            // Si es Owner, puede ver cualquier usuario del tenant
            $usuario = DB::connection('tenant')->table('usuarios')->find($id);
        } else {
            // Si no es Owner, solo puede ver su propia información
            if ($user->id != $id) {
                return response()->json(['error' => 'No tienes permiso para ver esta información'], 403);
            }

            $usuario = DB::connection('tenant')->table('usuarios')->find($id);
        }

        // Verificar si el usuario existe
        if (!$usuario) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        return response()->json($usuario);
    }



    // Función para verificar el rol del usuario (si es necesario)
    private function verificarRol($rol)
    {
        return Auth::user()->roles->contains('nombre', $rol);
    }



    /**
     * Crear un nuevo usuario de tipo owner
     */

    public function register(Request $request)
    {
        // Validación de datos
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'telefono' => 'required|string|max:20',
            'cedula' => 'required|string|max:10|unique:usuarios,cedula',
            'correo_electronico' => 'required|email|unique:usuarios,correo_electronico',
            'password' => 'required|string|min:6',
            'rol_id' => 'required|exists:roles,id', // Asegúrate de que se valida que el rol existe
            'sitio_id' => 'required_if:rol_id,' . $this->getRoleIdByName('Empleado') . '|exists:sitio,id_sitio',
            'id_plan' => 'required_if:rol_id,' . $this->getRoleIdByName('Owner') . '|exists:planes,id_plan',
            'metodo_pago' => 'required_if:rol_id,' . $this->getRoleIdByName('Owner') . '|in:1,2',
        ]);


        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // Crear el usuario
            $usuario = $this->createUser($request);

            // Asignar el rol al usuario (a través de la tabla usuario_rol)
            $usuario->roles()->attach($request->rol_id);

            // Obtener el rol asociado al usuario
            $rol = Rol::find($request->rol_id);
            // Ejecutar lógica basada en el rol
            if ($rol->nombre === 'Owner') {
                if ($request->metodo_pago == 2) { // Pago por PayPal

                    $paymentResult = $this->processPaypalPayment($request, $usuario);
                    // Detén la ejecución si se genera el enlace de redirección
                    if ($paymentResult->status() === 200) {
                        DB::commit(); // Commit temprano para garantizar persistencia
                        return $paymentResult;
                    }
                } elseif ($request->metodo_pago == 1) { // Pago por transferencia bancaria
                    $this->processBankTransfer($request, $usuario);
                }
            }
            DB::commit();

            return response()->json(['message' => 'Usuario creado exitosamente', 'usuario' => $usuario], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear usuario: ' . $e->getMessage());
            return response()->json(['errors' => 'Error al crear usuario'], 500);
        }
    }

    //Funcion de registro para administradores
    public function registerAdmins(Request $request)
    {
        // Validación de datos
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'telefono' => 'required|string|max:20',
            'cedula' => 'required|string|max:10|unique:usuarios,cedula',
            'correo_electronico' => 'required|email|unique:usuarios,correo_electronico',
            'password' => 'required|string|min:6',
            'rol_id' => 'required|exists:roles,id', // Asegúrate de que se valida que el rol existe
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // Crear el usuario
            $usuario = $this->createUser($request);

            // Asignar el rol al usuario (a través de la tabla usuario_rol)
            $usuario->roles()->attach($request->rol_id);

            DB::commit();

            return response()->json(['message' => 'Usuario creado exitosamente', 'usuario' => $usuario], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear usuario: ' . $e->getMessage());
            return response()->json(['errors' => 'Error al crear usuario'], 500);
        }
    }



    //Funcion para crear un usuario tipo empleado
    public function registerForEmployee(Request $request)
    {
        // Validar los datos de entrada
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'telefono' => 'required|string|max:20',
            'cedula' => 'required|string|max:10|unique:usuarios,cedula',
            'correo_electronico' => 'required|email|unique:usuarios,correo_electronico',
            'password' => 'required|string|min:6',
            'rol_id' => 'required|exists:roles,id', // Validar que el rol existe
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Iniciar la transacción en ambas bases de datos
        DB::beginTransaction();  // Esto es para la base de datos principal
        try {
            // Crear el usuario en la base de datos principal
            $usuario = $this->createUser($request);

            // Asignar el rol al usuario en la base de datos principal
            $usuario->roles()->attach($request->rol_id);

            // Obtener el valor del encabezado X-Tenant
            $tenantDatabase = $request->header('X-Tenant');
            if (!$tenantDatabase) {
                return response()->json(['error' => 'Nombre de la base de datos del tenant no proporcionado'], 400);
            }

            // Buscar el tenant en la tabla tenants
            $tenants = Tenant::all();
            $tenant = null;

            foreach ($tenants as $candidate) {
                // Acceder directamente a database_path
                if (isset($candidate->database_path) && str_ends_with($candidate->database_path, $tenantDatabase)) {
                    // Usar basename() para obtener solo el nombre del archivo
                    $tenantFileName = basename($candidate->database_path);
                    if ($tenantFileName === $tenantDatabase) {
                        $tenant = $candidate;
                        break;
                    }
                }
            }

            if (!$tenant) {
                return response()->json(['error' => 'Tenant no encontrado'], 404);
            }

            // Insertar en la tabla pivote tenant_usuario (base de datos principal)
            $tenantId = $tenant->id;
            DB::table('tenant_usuario')->insert([
                'usuario_id' => $usuario->id,
                'tenant_id' => $tenantId,
                'rol_id' => 1,
            ]);
            //Hacer commit
            DB::commit();

            // Conectar a la base de datos del tenant
            $this->setTenantConnection($tenantDatabase);

            // Verificar la conexión a la base de datos del tenant
            if (!DB::connection('tenant')->getDatabaseName()) {
                return response()->json(['error' => 'No se pudo conectar a la base de datos del tenant'], 500);
            }

            // Crear el usuario en la base de datos del tenant
            $usuarioTenantId = DB::connection('tenant')->table('usuarios')->insertGetId([
                'nombre' => $usuario->nombre,
                'apellido' => $usuario->apellido,
                'telefono' => $usuario->telefono,
                'cedula' => $usuario->cedula,
                'correo_electronico' => $usuario->correo_electronico,
                'password' => $usuario->password,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Asignar el rol en el tenant
            DB::connection('tenant')->table('usuario_rol')->insert([
                'usuario_id' => $usuarioTenantId,
                'rol_id' => $request->rol_id,
            ]);

            // Si todo ha ido bien en ambas bases de datos, finalizamos la transacción
            DB::commit();

            return response()->json(['message' => 'Usuario creado exitosamente'], 201);
        } catch (\Exception $e) {
            // Si algo falla, hacemos rollback en ambas bases de datos
            DB::rollBack();

            Log::error('Error al registrar usuario: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }


    //Funcion para crear usuario
    private function createUser(Request $request)
    {
        return Usuario::create([
            'nombre' => $request->nombre,
            'apellido' => $request->apellido,
            'telefono' => $request->telefono,
            'cedula' => $request->cedula,
            'correo_electronico' => $request->correo_electronico,
            'password' => $request->password,
        ]);
    }


    //Funcion encargada del registro de empleado
    protected function handleEmpleadoRegistration($request, $usuario)
    {
        // Lógica específica para el rol "Empleado"
        $sitio = Sitio::find($request->sitio_id);
        $usuario->sitio()->associate($sitio);
        $usuario->save();
        Mail::to($usuario->correo_electronico)->send(new WelcomeMail($usuario));
    }

    //Funcion para procesar el pago por paypal
    protected function processPaypalPayment($request, $usuario)
    {
        $paymentResult = $this->processPayment($request->id_plan, $usuario);
        if (!$paymentResult['status']) {
            throw new \Exception('Error en el proceso de pago.');
        }

        // Cambiar estado del usuario a "deshabilitado"
        $usuario->estado = 'deshabilitado';
        $usuario->save();

        return response()->json([
            'message' => 'Redirigir a PayPal para completar el pago.',
            'redirect_url' => $paymentResult['redirect_url'],
        ], 200);
    }

    //Funcion para procesar el pago por transferencia bancaria
    protected function processBankTransfer($request, $usuario)
    {
        // Crear la factura antes de notificar a los administradores
        $plan = Planes::find($request->id_plan);

        $paymentPreferences = json_decode($plan->payment_preferences, true);
        $setupFee = $paymentPreferences['setup_fee']['value'];

        // Crear la factura y el detalle de la factura
        $factura = $this->crearFacturaYDetalle($usuario, $plan, 2, "Pago suscripción por transferencia bancaria");


        // Notificar a los administradores

        $admins = Usuario::whereHas('roles', function ($query) {
            $query->where('nombre', 'Admin');
        })->get();

        // foreach ($admins as $admin) {
        //     Mail::to($admin->correo_electronico)->send(new ConfirmarPagoTransferencia($usuario));
        // }

        // Enviar mensaje al usuario indicando que el pago será confirmado
        // Mail::to($usuario->correo_electronico)->send(new PagoPendienteTransferencia($usuario));

        // Lógica específica para el rol "Owner"
        $this->createTenantForOwner($usuario);
    }

    //funcion para crear factura y detalle
    protected function crearFacturaYDetalle($usuario, $plan, $metodoPagoId, $descripcion)
    {
        $paymentPreferences = json_decode($plan->payment_preferences, true);
        $setupFee = $paymentPreferences['setup_fee']['value'];

        // Crear la factura
        $factura = Factura::create([
            'usuario_id' => $usuario->id,
            'metodo_pago_id' => $metodoPagoId,
            'total' => $setupFee,
            'estado' => 'pendiente',
        ]);

        // Crear el detalle de la factura
        $detalleFactura = DetalleFactura::create([
            'factura_id' => $factura->id,
            'descripcion' => $descripcion,
            'cantidad' => 1,
            'precio_unitario' => $setupFee,
            'subtotal' => $setupFee,
            'dia_facturacion' => Carbon::now()->day,  // Almacenar el día actual como entero
        ]);


        // Calcular las fechas de pago mediante la función
        $this->calcularFechasPago($factura);

        return $factura;
    }

    //Fucnion para crear tenants
    protected function createTenantForOwner($usuario)
    {
        DB::beginTransaction();
        try {
            // Generar un código único para el tenant
            $codigoUnico = substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', 6)), 0, 8);

            // Crear el nombre y la ruta de la base de datos del tenant
            $tenantName = $usuario->nombre . ' ' . $usuario->apellido;
            $tenantSlug = Str::slug($usuario->nombre . $usuario->apellido . '-' . $codigoUnico);
            $databasePath = database_path('tenants/' . $tenantSlug . '.sqlite');

            // Asegurarse de que la carpeta 'tenants' existe
            if (!File::exists(database_path('tenants'))) {
                File::makeDirectory(database_path('tenants'), 0755, true);
            }

            // Crear un archivo SQLite vacío para el tenant
            if (!File::exists($databasePath)) {
                File::put($databasePath, '');
            }

            Log::info('Base de datos SQLite creada en: ' . $databasePath);

            // Guardar el tenant en la base de datos principal (tabla `tenants`)
            $tenant = Tenant::create([
                'id' => Str::uuid(),
                'data' => [
                    'database_path' => $databasePath,
                    'name_slug' => $tenantSlug
                ],
                'name' => $tenantName,
                'name_slug' => $tenantSlug,
                'database_path' => $databasePath,
            ]);

            if (!$tenant) {
                throw new \Exception('Error al guardar el tenant en la base de datos.');
            }

            Log::info('Tenant creado: ' . $tenant->name);

            // Guardar la relación en la tabla pivote tenant_usuario
            DB::table('tenant_usuario')->insert([
                'usuario_id' => $usuario->id,
                'tenant_id' => $tenant->id,
                'rol_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('Relación guardada en la tabla pivote tenant_usuario.');

            // Configurar la conexión dinámica para este tenant
            config(['database.connections.tenant' => [
                'driver' => 'sqlite',
                'database' => $databasePath,
                'prefix' => '',
                'foreign_key_constraints' => true,
            ]]);

            // Cambiar la conexión al tenant
            DB::purge('tenant');
            DB::reconnect('tenant');
            Log::info('Conexión a la base de datos del tenant establecida.');

            // Excluir migraciones específicas y ejecutar las demás
            $excludedMigrations = [
                '2019_09_15_000020_create_domains_table',
                '2019_09_15_000010_create_tenants_table',
                '2024_09_15_031803_create_factura_table',
                '2024_09_15_031639_create_metodo_pago_table',
                '2024_09_12_220920_create__q_a_table',
                '2019_09_15_000010_create_tenants_table',
                '2024_09_23_014630_create_usuario_tenant_table',
                '2024_09_15_031824_create_detallef_actura_table',
                '2024_09_08_051844_create_demo_table',
                '2024_09_15_031656_create_promociones_table',
                '2024_09_15_062531_add_demo_expiry_date_to_demo_table',
                '2024_11_26_023950_add_columns_tenant',
                '2024_09_23_014630_create_usuario_tenant_table',
                '0001_01_01_000000_create_users_table'
                // Añade otras migraciones que no deban ejecutarse en los tenants
            ];

            $migrations = collect(File::allFiles(database_path('migrations')))
                ->map(fn($file) => pathinfo($file, PATHINFO_FILENAME))
                ->filter(fn($migration) => !in_array($migration, $excludedMigrations));

            foreach ($migrations as $migration) {
                Artisan::call('migrate', [
                    '--database' => 'tenant',
                    '--path' => 'database/migrations/' . $migration . '.php',
                    '--force' => true,
                ]);
                Log::info('Migración ejecutada: ' . $migration);
            }

            // Ejecutar el seeder en la base de datos del tenant
            Artisan::call('db:seed', [
                '--class' => 'RolesAndPermissionsSeeder',
                '--database' => 'tenant',
                '--force' => true,
            ]);

            Log::info('Seeder ejecutado en el tenant.');

            // Insertar el usuario en la base de datos del tenant
            $usuarioTenant = DB::connection('tenant')->table('usuarios')->insertGetId([
                'nombre' => $usuario->nombre,
                'apellido' => $usuario->apellido,
                'telefono' => $usuario->telefono,
                'cedula' => $usuario->cedula,
                'correo_electronico' => $usuario->correo_electronico,
                'password' => $usuario->password, // Asume que ya está encriptada
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (!$usuarioTenant) {
                throw new \Exception('Error al insertar el usuario en la base de datos del tenant.');
            }

            Log::info('Usuario insertado en la base de datos del tenant, ID: ' . $usuarioTenant);

            // Obtener el rol 'Owner' del tenant
            $adminRol = DB::connection('tenant')->table('roles')->where('nombre', 'Owner')->first();

            if ($adminRol) {
                // Asignar el rol de 'Owner' al usuario en la base de datos del tenant
                DB::connection('tenant')->table('usuario_rol')->insert([
                    'usuario_id' => $usuarioTenant,
                    'rol_id' => $adminRol->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                Log::info('Rol "Owner" asignado al usuario en el tenant.');
            } else {
                throw new \Exception('Rol "Owner" no encontrado en el tenant.');
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en la creación del tenant: ' . $e->getMessage());
            return response()->json(['error' => 'Error en la creación del tenant'], 500);
        }
    }


    public function confirmarPago($id)
    {
        // Iniciar una transacción en la base de datos principal
        DB::beginTransaction();
        try {
            // Buscar la factura correspondiente en la base de datos principal
            $factura = Factura::find($id);
            if (!$factura) {
                return response()->json(['error' => 'Factura no encontrada'], 404);
            }

            // Actualizar el estado de la factura a 'pagado' en la base de datos principal
            $factura->estado = 'pagado';
            $factura->save();

            // Obtener el usuario asociado en la base de datos principal
            $usuario = Usuario::find($factura->usuario_id);
            if (!$usuario) {
                return response()->json(['error' => 'Usuario no encontrado'], 404);
            }

            // Actualizar el estado del usuario a 'habilitado' en la base de datos principal
            $usuario->estado = 'habilitado';
            $usuario->save();

            //obtener el correo del usuario
            $correo = $usuario->correo_electronico;

            // Obtener todas las bases de datos de tenants
            $tenantDatabases = File::files(database_path('tenants'));

            foreach ($tenantDatabases as $tenantDatabase) {
                // Extraer el nombre de la base de datos
                $databasePath = $tenantDatabase->getPathname();

                try {
                    // Configurar la conexión para el tenant
                    config(['database.connections.tenant' => [
                        'driver' => 'sqlite',
                        'database' => $databasePath,
                        'prefix' => '',
                        'foreign_key_constraints' => true,
                    ]]);

                    // Establecer la conexión del tenant
                    DB::purge('tenant');
                    DB::reconnect('tenant');

                    Log::info('Conexión establecida a la base de datos del tenant: ' . $databasePath);

                    // Iniciar una transacción en la base de datos del tenant
                    DB::connection('tenant')->beginTransaction();

                    // Buscar el usuario en la base de datos del tenant por correo
                    $tenantUsuario = DB::connection('tenant')->table('usuarios')
                        ->where('correo_electronico', $correo)
                        ->first();

                    if ($tenantUsuario) {
                        // Actualizar el estado del usuario a 'habilitado' en la base de datos del tenant
                        DB::connection('tenant')->table('usuarios')
                            ->where('id', $tenantUsuario->id)
                            ->update(['estado' => 'habilitado']);

                        Log::info('Estado del usuario actualizado en el tenant: ' . $databasePath);

                        // Confirmar la transacción en la base de datos del tenant
                        DB::connection('tenant')->commit();
                    } else {
                        // Si no se encuentra el usuario, continuar con el siguiente tenant
                        Log::warning('Usuario no encontrado en el tenant: ' . $databasePath);
                        continue;
                    }
                } catch (\Exception $e) {
                    // Revertir la transacción en la base de datos del tenant
                    DB::connection('tenant')->rollBack();
                    Log::error('Error al actualizar el estado del usuario en el tenant: ' . $e->getMessage());

                    // Revertir la transacción en la base de datos principal
                    DB::rollBack();
                    Log::error('Error al confirmar el pago, deshaciendo todo: ' . $e->getMessage());

                    return response()->json(['error' => 'Error al confirmar el pago'], 500);
                }
            }

            // Confirmar la transacción en la base de datos principal
            DB::commit();

            // Enviar correos de confirmación y rechazo
            Mail::to($usuario->correo_electronico)->send(new ConfirmacionPagoMail($usuario));

            return response()->json(['message' => 'Pago confirmado y correos enviados'], 200);
        } catch (\Exception $e) {
            // Revertir la transacción en la base de datos principal
            DB::rollBack();
            Log::error('Error al confirmar el pago: ' . $e->getMessage());

            return response()->json(['error' => 'Error al confirmar el pago'], 500);
        }
    }


    public function rechazarPago($id)
    {
        // Buscar la factura correspondiente
        $factura = Factura::find($id);
        if (!$factura) {
            return response()->json(['error' => 'Factura no encontrada'], 404);
        }

        // Actualizar estado a rechazado
        $factura->estado = 'rechazado';
        $factura->save();

        // Obtener el usuario asociado
        $usuario = Usuario::find($factura->usuario_id);

        // Enviar correo de rechazo
        Mail::to($usuario->correo_electronico)->send(new RechazoPagoMail($usuario));

        return response()->json(['message' => 'Pago rechazado y correo enviado'], 200);
    }


    //Funcion para procesar pago por  paypal
    public function processPayment($planId, $usuario)
    {
        $plan = Planes::find($planId);

        if (!$plan) {
            Log::error('Plan no encontrado', ['plan_id' => $planId]);
            return ['status' => false, 'message' => 'Plan no encontrado'];
        }

        try {
            $provider = new PayPalClient;
            $provider->setApiCredentials(config('paypal'));
            $paypalToken = $provider->getAccessToken();

            // Crear suscripción en PayPal utilizando el id_paypal del plan
            $response = $provider->createSubscription([
                "plan_id" => $plan->id_paypal,  // ID del plan en PayPal
                "application_context" => [
                    "brand_name" => "InventoryPro",
                    "locale" => "es-ES",
                    "user_action" => "SUBSCRIBE_NOW",
                    "return_url" => route('paypal.payment.success', ['user_id' => $usuario->id]),
                    "cancel_url" => route('paypal.payment.cancel'),
                ],
                "subscriber" => [
                    "name" => [
                        "given_name" => $usuario->nombre,
                        "surname" => $usuario->apellido,
                    ],
                    "email_address" => $usuario->correo_electronico,
                ],
                "quantity" => "1", // Cantidad fija o ajustar según tu lógica
            ]);

            // Buscar el enlace de aprobación
            if (isset($response['id']) && $response['id'] != null) {
                $orderIdPayPal = $response['id']; // Capturamos el ID de la orden en PayPal

                // Extraer el valor de setup_fee['value'] de payment_preferences del plan
                $paymentPreferences = json_decode($plan->payment_preferences, true); // Asegúrate de que payment_preferences esté en formato JSON en la base de datos
                $setupFee = $paymentPreferences['setup_fee']['value']; // Obtener el valor de setup_fee

                // Crear la factura y el detalle de la factura
                $factura = $this->crearFacturaYDetalle($usuario, $plan, 1, "Pago suscripción por PayPal");
                $factura = $this->crearFacturaYDetalle($usuario, $plan, 1, "Pago suscripción por PayPal");

                // Guardar el `order_id_paypal` en la factura
                $factura->order_id_paypal = $orderIdPayPal;
                $factura->save();


                // Redirigir al usuario para aprobar el pago
                foreach ($response['links'] as $link) {
                    if ($link['rel'] == 'approve') {
                        Log::info('Orden de PayPal creada exitosamente', ['order_id' => $response['id']]);
                        return ['status' => true, 'redirect_url' => $link['href']];
                    }
                }
            }

            Log::error('Error al crear la orden de pago', ['response' => $response]);
            return ['status' => false, 'message' => 'Error al crear la orden de pago'];
        } catch (\Exception $e) {
            Log::error('Excepción durante el proceso de pago', ['exception' => $e->getMessage()]);
            return ['status' => false, 'message' => 'Error en el servidor'];
        }
    }

    //funcion para calcular las fechas de pago
    private function calcularFechasPago($factura)
    {
        // Iniciar una transacción
        DB::beginTransaction();
        try {
            // Definir el ciclo de facturación y los días de gracia
            $cicloFacturacionDias = 30; // Ejemplo: 30 días de ciclo
            $diasGracia = 7; // Ejemplo: 7 días de gracia

            // Calcular la próxima fecha de pago y la fecha de gracia
            $proximaFechaPago = Carbon::now()->addDays($cicloFacturacionDias);
            $fechaGracia = $proximaFechaPago->copy()->addDays($diasGracia);


            // Guardar las fechas calculadas en la base de datos
            $factura->update([
                'proxima_fecha_pago' => $proximaFechaPago,
                'fecha_gracia' => $fechaGracia
            ]);

            // Confirmar la transacción
            DB::commit();
            Log::info('Fechas de pago calculadas y guardadas correctamente', [
                'factura_id' => $factura->id,
                'proxima_fecha_pago' => $proximaFechaPago,
                'fecha_gracia' => $fechaGracia
            ]);
        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            DB::rollBack();
            Log::error('Error al calcular y guardar las fechas de pago', [
                'factura_id' => $factura->id,
                'error' => $e->getMessage()
            ]);
            throw $e; // Re-lanzar la excepción para manejarla en el contexto superior si es necesario
        }
    }

    //funcion si el pago por paypal fue exitoso
    public function paymentSuccess(Request $request)
    {
        $userId = $request->input('user_id');
        $usuario = Usuario::find($userId);

        if (!$usuario) {
            return response()->json(['status' => 'error', 'message' => 'Usuario no encontrado'], 404);
        }

        // Verifica el estado de la factura y realiza acciones
        $factura = Factura::where('usuario_id', $usuario->id)
            ->where('estado', 'pendiente')
            ->whereNotNull('order_id_paypal')
            ->first();

        if (!$factura) {
            return response()->json(['status' => 'error', 'message' => 'Factura no encontrada'], 404);
        }

        // Actualiza el estado del pago y crea el tenant
        DB::beginTransaction();
        try {
            $factura->estado = 'pagado';
            $factura->fecha_pago = now();
            $factura->save();

            // Cambia el estado del usuario
            $usuario->estado = 'habilitado';
            $usuario->save();

            // Lógica para crear el tenant después del pago exitoso
            $this->createTenantForOwner($usuario);

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Suscripción activada correctamente']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al procesar pago exitoso: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Error al procesar el pago'], 500);
        }
    }


    //Funcion para procesar pagos con paypal
    public function paymentCancel()
    {
        return response()->json(['status' => 'error', 'message' => 'Pago cancelado'], 400);
    }

    //Funcion para procesar pagos fallidos
    public function paymentFailure()
    {
        return response()->json(['status' => 'error', 'message' => 'Error en el pago. Inténtelo de nuevo.'], 500);
    }


    /**
     * Editar la información del usuario.
     */

    public function update(Request $request, $id)
    {
        // Obtener el usuario autenticado
        $user = Auth::user();

        // Validar los datos de entrada
        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|required|string',
            'apellido' => 'sometimes|required|string',
            'telefono' => 'sometimes|required|string',
            'cedula' => 'sometimes|required|string|max:10|unique:usuarios,cedula,' . $id,
            'correo_electronico' => 'sometimes|required|email|unique:usuarios,correo_electronico,' . $id,
            'contrasena' => 'sometimes|required|string|min:6',
            'rol_id' => 'sometimes|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verificar que la conexión al tenant esté configurada correctamente
        try {
            $databaseName = DB::connection('tenant')->getDatabaseName();
            if (!$databaseName) {
                return response()->json(['error' => 'No se pudo conectar a la base de datos del tenant'], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error al conectar con la base de datos del tenant: ' . $e->getMessage());
            return response()->json(['error' => 'No se pudo conectar a la base de datos del tenant'], 500);
        }

        // Buscar al usuario en la base de datos del tenant
        $usuario = DB::connection('tenant')->table('usuarios')->where('id', $id)->first();

        if (!$usuario) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        // // Verificar permisos para actualizar
        // if (!$this->verificarPermiso('Puede actualizar usuarios') && $user->id !== $usuario->id) {
        //     return response()->json(['error' => 'No tienes permisos para actualizar esta información'], 403);
        // }

        // Preparar datos para actualizar
        $requestData = $request->only(['nombre', 'apellido', 'telefono', 'cedula', 'correo_electronico']);
        if ($request->has('contrasena')) {
            $requestData['contrasena'] = bcrypt($request->contrasena);
        }

        // Actualizar la información en el tenant
        DB::connection('tenant')->beginTransaction();
        try {
            DB::connection('tenant')->table('usuarios')->where('id', $id)->update($requestData);

            DB::connection('tenant')->commit();
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            Log::error('Error al actualizar usuario en el tenant: ' . $e->getMessage());
            return response()->json(['error' => 'Error al actualizar usuario en el tenant'], 500);
        }

        // Desconectar del tenant y reconectar a la base de datos principal
        try {
            DB::purge('tenant'); // Limpiar conexión activa del tenant
            DB::reconnect('sqlite'); // Reconectar a la base de datos principal
        } catch (\Exception $e) {
            Log::error('Error al reconectar a la base de datos principal: ' . $e->getMessage());
            return response()->json(['error' => 'Error al reconectar a la base de datos principal'], 500);
        }

        // Actualizar la información en la base de datos principal
        DB::connection('sqlite')->beginTransaction();
        try {
            DB::connection('sqlite')->table('usuarios')->where('id', $id)->update($requestData);

            DB::connection('sqlite')->commit();
        } catch (\Exception $e) {
            DB::connection('sqlite')->rollBack();
            Log::error('Error al actualizar usuario en la base de datos principal: ' . $e->getMessage());
            return response()->json(['error' => 'Error al actualizar usuario en la base de datos principal'], 500);
        }

        return response()->json(['message' => 'Usuario actualizado exitosamente'], 200);
    }


    // recuperar contraseña
    public function recoveryPassword(Request $request)
    {
        // Validar que el email fue enviado en la solicitud
        $validator = Validator::make($request->all(), [
            'correo_electronico' => 'required|email|exists:usuarios,correo_electronico',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Buscar el usuario por su email
        $user = Usuario::where('correo_electronico', $request->correo_electronico)->first();

        if ($user) {
            // Generar un token único
            $token = Str::random(64);

            // Guardar el token en la tabla password_resets
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->correo_electronico],
                ['token' => $token, 'created_at' => Carbon::now()]
            );

            // Crear la URL con el token
            $resetUrl = url("http://localhost:3000/auth/recovery?token={$token}&correo_electronico={$request->correo_electronico}");

            try {
                // Enviar el correo con el enlace
                Mail::to($request->correo_electronico)->send(new RecoveryPassword($user, $resetUrl));
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Hubo un error al enviar el correo de recuperación. Inténtelo de nuevo más tarde.'
                ], 500);
            }

            return response()->json([
                'message' => 'Hemos enviado un correo electrónico con un enlace para restablecer su contraseña.'
            ]);
        } else {
            return response()->json([
                'message' => 'No hemos encontrado un usuario con ese correo electrónico.'
            ], 404);
        }
    }

    // Resetear la contraseña
    public function resetPassword(Request $request)
    {
        // Validar que el token, email, y contraseñas fueron enviados en la solicitud
        $validator = Validator::make($request->all(), [
            'token' => 'required|string|exists:password_reset_tokens,token',
            'correo_electronico' => 'required|email|exists:password_reset_tokens,email',
            'password' => 'required|string|min:8|confirmed', // El campo "password_confirmation" también debe ser enviado
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verificar que el token corresponde al email
        $passwordReset = DB::table('password_reset_tokens')
            ->where('token', $request->token)
            ->where('email', $request->correo_electronico)
            ->first();

        if (!$passwordReset) {
            return response()->json(['message' => 'Token inválido o correo no coincidente.'], 400);
        }

        DB::beginTransaction();

        try {
            // Actualizar la contraseña del usuario en la base de datos principal
            $user = Usuario::where('correo_electronico', $request->correo_electronico)->first();

            if (!$user) {
                return response()->json(['message' => 'No se ha encontrado el usuario.'], 404);
            }

            // Hashear la nueva contraseña
            $hashedPassword = Hash::make($request->password);
            $user->password = $hashedPassword;
            $user->save();

            // Obtener el tenant asociado al usuario
            $tenantRelation = DB::table('tenant_usuario')->where('usuario_id', $user->id)->first();

            if ($tenantRelation) {
                // Configurar la conexión al tenant
                $tenant = Tenant::find($tenantRelation->tenant_id);
                if (!$tenant) {
                    throw new \Exception('No se encontró el tenant asociado.');
                }

                $databasePath = $tenant->data['database_path'];
                config(['database.connections.tenant' => [
                    'driver' => 'sqlite',
                    'database' => $databasePath,
                    'prefix' => '',
                    'foreign_key_constraints' => true,
                ]]);

                // Cambiar la conexión al tenant
                DB::purge('tenant');
                DB::reconnect('tenant');

                // Actualizar la contraseña del usuario en la base de datos del tenant
                DB::connection('tenant')->table('usuarios')
                    ->where('correo_electronico', $request->correo_electronico)
                    ->update(['password' => $hashedPassword]);

                Log::info('Contraseña actualizada en el tenant: ' . $databasePath);
            }

            // Eliminar el token una vez la contraseña ha sido reseteada exitosamente
            DB::table('password_reset_tokens')->where('email', $request->correo_electronico)->delete();

            DB::commit();

            return response()->json(['message' => 'Su contraseña ha sido cambiada exitosamente.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al cambiar la contraseña: ' . $e->getMessage());
            return response()->json(['message' => 'Error al cambiar la contraseña. Por favor, inténtelo de nuevo.'], 500);
        }
    }

    public function requestDemo(Request $request)
    {
        // Validar que el email fue enviado en la solicitud
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:demo,email|unique:usuarios,correo_electronico',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            // Registrar el correo en la tabla demo
            $demoRequest = Demo::create([
                'email' => $request->email,
                // El usuario_id y isActive se manejarán al aprobar
            ]);

            // Obtener los correos de los administradores directamente desde la tabla usuario_rol
            $adminEmail = Usuario::whereHas('roles', function ($query) {
                $query->where('nombre', 'Admin');
            })->pluck('correo_electronico')->toArray();


            // Verificar que haya correos de administradores
            if (empty($adminEmail)) {
                throw new \Exception("No hay administradores disponibles para enviar la solicitud de demo.");
            }

            // Enviar un correo a los administradores notificando la nueva solicitud de demo
            Mail::to($adminEmail)->send(new RequestDemoMail($demoRequest));

            DB::commit();

            return response()->json(['message' => 'Solicitud de demo enviada correctamente.'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            //log de errores
            Log::error('Error al enviar la solicitud de demo: ' . $e->getMessage());
            return response()->json(['errors' => 'Hubo un error al enviar la solicitud de demo. Por favor, inténtelo de nuevo.'], 500);
        }
    }

    // Aprobación de demo
    public function approveDemo(Request $request)
    {
        // Verificar si el usuario tiene el permiso para aprobar demos
        if (!$this->verificarPermiso('Aprobar demo')) {
            return response()->json(['error' => 'No tienes permiso para aprobar demos'], 403);
        }

        // Validar el ID de la demo a aprobar
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:demo,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Contar la cantidad de demos activas
        $demoCount = Demo::where('isActive', true)->count();

        // Limitar la cantidad de demos activas
        $cantidadMaximaDemos = 5;
        if ($demoCount >= $cantidadMaximaDemos) {
            return response()->json(['message' => 'No se pueden aprobar más demos.'], 400);
        }

        DB::beginTransaction();

        try {
            // Pedir el ID de la demo a aprobar
            $demoRequest = Demo::findOrFail($request->id);

            // Verificar que la demo aún no ha sido aprobada
            if ($demoRequest->isActive) {
                return response()->json(['message' => 'La demo ya ha sido aprobada.'], 400);
            }

            // Generar datos predeterminados para el usuario demo
            $nombreDemo = 'Demo ' . Str::random(5);
            $apellidoDemo = 'User';
            $telefonoDemo = '0000000000';
            $cedulaDemo = Str::random(10);

            // Generar la contraseña en texto plano
            $passwordPlano = Str::random(8);

            // Crear el usuario demo en la tabla usuarios con la contraseña hasheada
            $usuarioDemo = Usuario::create([
                'nombre' => $nombreDemo,
                'apellido' => $apellidoDemo,
                'telefono' => $telefonoDemo,
                'cedula' => $cedulaDemo,
                'correo_electronico' => $demoRequest->email,
                'password' => $passwordPlano,
                'rol' => 'demo',
            ]);

            // Actualizar la tabla demo con el ID del usuario, marcar como activa y establecer la fecha de expiración
            $demoRequest->update([
                'usuario_id' => $usuarioDemo->id,
                'isActive' => true,
                'demo_expiry_date' => now()->addDays(5), // Establecer la fecha de expiración a 5 días a partir de ahora
            ]);

            // Enviar el correo con la contraseña en texto plano
            Mail::to($demoRequest->email)->send(new DemoMail($usuarioDemo, $passwordPlano));

            DB::commit();

            return response()->json(['message' => 'Demo aprobada exitosamente', 'usuario' => $usuarioDemo], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errors' => 'Hubo un error al aprobar la demo. Por favor, inténtelo de nuevo.'], 500);
        }
    }


    //Negar demo
    public function rejectDemo(Request $request)
    {
        // Verificar si el usuario tiene el permiso para negar demos
        if (!$this->verificarPermiso('Negar demo')) {
            return response()->json(['error' => 'No tienes permiso para rechazar demos'], 403);
        }

        // Validar el ID de la demo a rechazar
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:demo,id',
        ]);

        // Si falla la validación
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            $demoRequest = Demo::findOrFail($request->id);

            // Enviar un correo de rechazo con el motivo
            Mail::to($demoRequest->email)->send(new DemoRejectedMail($demoRequest));

            DB::commit();

            return response()->json(['message' => 'Demo rechazada y correo enviado.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errors' => 'Hubo un error al rechazar la demo. Por favor, inténtelo de nuevo.'], 500);
        }
    }

    //Eliminar usuario
    public function destroy(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'tenant_database' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $tenantDatabase = $request->tenant_database;

        $this->setTenantConnection($tenantDatabase);

        if (!DB::connection('tenant')->getDatabaseName()) {
            return response()->json(['error' => 'No se pudo conectar a la base de datos del tenant'], 500);
        }

        if (!$this->verificarPermiso('Puede borrar usuarios')) {
            return response()->json(['error' => 'No tienes permiso para eliminar usuarios'], 403);
        }

        $usuario = DB::connection('tenant')->table('usuarios')->find($id);

        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        try {
            DB::connection('tenant')->table('usuarios')->where('id', $id)->update(['deleted_at' => now()]);
            return response()->json(['message' => 'Usuario eliminado correctamente'], 200);
        } catch (\Exception $e) {
            Log::error('Error al eliminar usuario en el tenant: ' . $e->getMessage());
            return response()->json(['error' => 'Error al eliminar usuario'], 500);
        }
    }
}
