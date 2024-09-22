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

use App\Models\Demo;
use App\Models\Rol;
use App\Mail\DemoRejectedMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\Planes;
use App\Models\Factura;
use App\Models\DetalleFactura;

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
     * Verifica si el usuario autenticado tiene un rol específico.
     *
     * @param string $rolNombre
     * @return bool
     */
    private function verificarRol($rolNombre)
    {
        $user = Auth::user();

        // Obtener los roles asociados al usuario
        $roles = $user->roles; // Asumiendo que el modelo Usuario tiene una relación con roles

        // Verificar si alguno de los roles coincide con el nombre del rol requerido
        foreach ($roles as $rol) {
            if ($rol->nombre === $rolNombre) {
                return true;
            }
        }

        return false;
    }



    /**
     * Mostrar la lista de usuarios.
     */
    public function index()
    {
        $user = Auth::user(); // Obtiene el usuario autenticado

        if ($this->verificarRol('admin')) {
            // Admin puede ver todos los usuarios
            $usuarios = Usuario::all();
        } else {
            // Empleado solo puede ver su propia información
            $usuarios = Usuario::where('id', $user->id)->get();
        }

        return response()->json($usuarios);
    }



    /**
     * Crear un nuevo usuario.
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
            'rol_id' => 'required|exists:roles,id', // Validación para el campo rol_id
            'sitio_id' => 'required_if:rol_id,' . $this->getRoleIdByName('Empleado') . '|exists:sitio,id_sitio', // Rol empleado
            'id_plan' => 'required_if:rol_id,' . $this->getRoleIdByName('Owner') . '|exists:planes,id_plan', // Rol owner
            'metodo_pago' => 'required_if:rol_id,' . $this->getRoleIdByName('Owner') . '|in:1,2', // Validación para el método de pago para owner
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            // Buscar el rol por ID
            $rol = Rol::find($request->rol_id);

            // Crear el usuario
            $usuario = Usuario::create([
                'nombre' => $request->nombre,
                'apellido' => $request->apellido,
                'telefono' => $request->telefono,
                'cedula' => $request->cedula,
                'correo_electronico' => $request->correo_electronico,
                'password' => $request->password,
                'rol_id' => $request->rol_id,  // Relación con la tabla de roles
                'id_plan' => $request->id_plan ?? null,
                'suscripcion' => 'pendiente',  // Estado pendiente hasta confirmar el pago
            ]);

            if ($rol->nombre === 'Owner') {
                if ($request->metodo_pago == 1) {
                    // Opción 1: PayPal
                    $paymentResult = $this->processPayment($request->id_plan, $usuario);

                    if (!$paymentResult['status']) {
                        DB::rollBack();
                        return response()->json(['errors' => 'Error en el proceso de pago. Inténtelo de nuevo.'], 500);
                    }

                    DB::commit();
                    return response()->json(['redirect_url' => $paymentResult['redirect_url']]);
                } elseif ($request->metodo_pago == 2) {
                    // Opción 2: Transferencia Bancaria

                    // Crear la factura antes de notificar a los administradores
                    $plan = Planes::find($request->id_plan);
                    $paymentPreferences = json_decode($plan->payment_preferences, true);
                    $setupFee = $paymentPreferences['setup_fee']['value'];

                    $factura = Factura::create([
                        'usuario_id' => $usuario->id,
                        'metodo_pago_id' => 2, // ID para transferencia bancaria
                        'total' => $setupFee,
                        'estado' => 'pendiente',
                    ]);

                    DetalleFactura::create([
                        'factura_id' => $factura->id,
                        'descripcion' => "Pago suscripción",
                        'cantidad' => 1,
                        'precio_unitario' => $setupFee,
                        'subtotal' => $setupFee,
                    ]);

                    // Notificar a los administradores
                    $admins = Usuario::whereHas('rol', function ($query) {
                        $query->where('nombre', 'Admin');
                    })->get();

                    foreach ($admins as $admin) {
                        Mail::to($admin->correo_electronico)->send(new ConfirmarPagoTransferencia($usuario));
                    }

                    // Enviar mensaje al usuario indicando que el pago será confirmado
                    Mail::to($usuario->correo_electronico)->send(new PagoPendienteTransferencia($usuario));

                    DB::commit();
                    return response()->json(['message' => 'Usuario creado exitosamente. Se te notificará por correo cuando tu pago sea confirmado.'], 201);
                }
            } else {
                DB::commit();
                //funcion que asigna el rol automaticamente
                $usuario->roles()->attach($rol);
                // Enviar correo de bienvenida
                Mail::to($usuario->correo_electronico)->send(new WelcomeMail($usuario));
                return response()->json(['message' => 'Usuario creado exitosamente', 'usuario' => $usuario], 201);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear usuario: ' . $e->getMessage());
            return response()->json(['errors' => 'Error al crear usuario'], 500);
        }
    }

    /**
     * Obtener el ID del rol por nombre.
     */
    private function getRoleIdByName($rolNombre)
    {
        $rol = Rol::where('nombre', $rolNombre)->first();
        return $rol ? $rol->id : null;
    }




    public function confirmarPago($id)
    {
        // Buscar la factura correspondiente
        $factura = Factura::find($id);
        if (!$factura) {
            return response()->json(['error' => 'Factura no encontrada'], 404);
        }

        // Actualizar estado a confirmado
        $factura->estado = 'pagado';
        $factura->save();

        // Obtener el usuario asociado
        $usuario = Usuario::find($factura->usuario_id);

        // Enviar correo de confirmación
        Mail::to($usuario->correo_electronico)->send(new ConfirmacionPagoMail($usuario));

        return response()->json(['message' => 'Pago confirmado y correo enviado'], 200);
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


    //Funcion para procesar pago
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

                // Crear la factura antes de redirigir a PayPal
                $factura = Factura::create([
                    'usuario_id' => $usuario->id,  // Cambiado a usuario_id
                    'metodo_pago_id' => 1, // Cambiar si se necesita ajustar el ID correspondiente a PayPal
                    'order_id_paypal' => $orderIdPayPal,
                    'total' => $setupFee, // Usar setup_fee['value'] como el total del plan
                    'estado' => 'pendiente',
                ]);



                // Crear el detalle de la factura
                DetalleFactura::create([
                    'factura_id' => $factura->id,
                    'descripcion' => "Pago suscripcion", // Descripción del plan
                    'cantidad' => 1, // En este caso solo 1 suscripción
                    'precio_unitario' => $setupFee, // Usar el setup_fee
                    'subtotal' => $setupFee, // Total = cantidad * precio_unitario
                ]);

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



    //funcion si el pago fue exitoso
    public function paymentSuccess(Request $request)
    {
        $userId = $request->input('user_id');
        $usuario = Usuario::find($userId);

        if (!$usuario) {
            return response()->json(['status' => 'error', 'message' => 'Usuario no encontrado'], 404);
        }

        // Buscar la factura pendiente del usuario
        $factura = Factura::where('usuario_id', $usuario->id)
            ->where('estado', 'pendiente')
            ->whereNotNull('order_id_paypal')
            ->first();

        if (!$factura) {
            return response()->json(['status' => 'error', 'message' => 'Factura no encontrada'], 404);
        }

        // Actualizar el estado de la factura y la fecha de pago
        $factura->estado = 'pagado';
        $factura->fecha_pago = now();
        $factura->save();

        // Actualizar el estado del usuario a 'habilitado'
        $usuario->estado = 'habilitado';
        $usuario->save();

        // Enviar correo al owner
        Mail::to($usuario->correo_electronico)->send(new OwnerMail($usuario));

        return response()->json(['status' => 'success', 'message' => 'Suscripción activada correctamente']);
    }



    public function paymentCancel()
    {
        return response()->json(['status' => 'error', 'message' => 'Pago cancelado'], 400);
    }

    public function paymentFailure()
    {
        return response()->json(['status' => 'error', 'message' => 'Error en el pago. Inténtelo de nuevo.'], 500);
    }






    /**
     * Editar la información del usuario.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user(); // Obtiene el usuario autenticado
        $usuario = Usuario::findOrFail($id);

        // Verificar si el usuario tiene el permiso para actualizar otros usuarios
        if (!$this->verificarPermiso('Puede actualizar usuarios') && $user->id !== $usuario->id) {
            return response()->json(['error' => 'No tienes permisos para actualizar esta información'], 403);
        }

        // Validación de datos
        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|required|string',
            'apellido' => 'sometimes|required|string',
            'telefono' => 'sometimes|required|string',
            'cedula' => 'sometimes|required|string|max:10|unique:usuarios,cedula,' . $usuario->id,
            'correo_electronico' => 'sometimes|required|email|unique:usuarios,correo_electronico,' . $usuario->id,
            'contrasena' => 'sometimes|required|string|min:6',
            'rol_id' => 'sometimes|exists:roles,id', // Ahora se usa rol_id
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Actualización del usuario
        if ($this->verificarPermiso('Puede actualizar empleados')) {
            // Solo permitir actualización de ciertos campos para empleados
            $requestData = $request->only(['nombre', 'apellido', 'telefono', 'cedula', 'correo_electronico']);
            if ($request->has('contrasena')) {
                $requestData['contrasena'] = ($request->contrasena);
            }
            $usuario->update($requestData);
        } else {
            // Admin puede actualizar todos los campos
            if ($request->has('contrasena')) {
                $request->merge(['contrasena' => $request->password]);
            }
            $usuario->update($request->all());
        }

        return response()->json(['message' => 'Usuario actualizado exitosamente', 'usuario' => $usuario], 200);
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

        // Actualizar la contraseña del usuario
        $user = Usuario::where('correo_electronico', $request->correo_electronico)->first();

        if ($user) {
            // Hashear la nueva contraseña
            $user->password = Hash::make($request->password);
            $user->save();

            // Eliminar el token una vez la contraseña ha sido reseteada exitosamente
            DB::table('password_reset_tokens')->where('email', $request->correo_electronico)->delete();

            return response()->json(['message' => 'Su contraseña ha sido cambiada exitosamente.'], 200);
        } else {
            return response()->json(['message' => 'No se ha encontrado el usuario.'], 404);
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

            // Obtener los correos de los administradores
            $adminEmail = Usuario::where('rol', 'admin')->pluck('correo_electronico')->toArray();

            // Enviar un correo a los administradores notificando la nueva solicitud de demo
            Mail::to($adminEmail)->send(new RequestDemoMail($demoRequest));

            DB::commit();

            return response()->json(['message' => 'Solicitud de demo enviada correctamente.'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errors' => 'Hubo un error al enviar la solicitud de demo. Por favor, inténtelo de nuevo.'], 500);
            //log con los errores

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
    public function destroy($id)
    {
        // Verificar si el usuario tiene el permiso para borrar usuarios
        if (!$this->verificarPermiso('Puede borrar usuarios')) {
            return response()->json(['error' => 'No tienes permiso para eliminar usuarios'], 403);
        }

        // Buscar el usuario por ID
        $usuario = Usuario::find($id);

        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        // Borrado lógico del usuario (soft delete)
        $usuario->delete();

        return response()->json(['message' => 'Usuario eliminado correctamente'], 200);
    }



    public function paginatedIndex(Request $request)
    {
        // Verificar si el usuario está autenticado
        $authUser = Auth::user();
        if (!$authUser) {
            return response()->json(['errors' => 'No estás autenticado.'], 401);
        }

        // Verificar si el usuario tiene el rol de empleado y denegar acceso
        if ($this->verificarRol('Empleado')) {
            return response()->json(['errors' => 'No tienes permiso para acceder a esta información.'], 403);
        }

        // Validar la solicitud
        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|string|max:255',
            'apellido' => 'sometimes|string|max:255',
            'telefono' => 'sometimes|string|max:255',
            'cedula' => 'sometimes|string|max:10',
            'correo_electronico' => 'sometimes|string|max:255',
            'rol' => 'sometimes|in:Empleado,Admin,Demo,Owner',
            'deleted_at' => 'sometimes|boolean', // Usar booleano para filtrar activos/inactivos
            'per_page' => 'sometimes|integer|min:1|max:100', // Limitar el número de resultados por página
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Obtener los datos validados
        $validatedData = $validator->validated();

        // Construir la consulta con los filtros opcionales
        $query = Usuario::query();

        if (isset($validatedData['nombre'])) {
            $query->where('nombre', 'like', '%' . $validatedData['nombre'] . '%');
        }

        if (isset($validatedData['apellido'])) {
            $query->where('apellido', 'like', '%' . $validatedData['apellido'] . '%');
        }

        if (isset($validatedData['telefono'])) {
            $query->where('telefono', 'like', '%' . $validatedData['telefono'] . '%');
        }

        if (isset($validatedData['cedula'])) {
            $query->where('cedula', 'like', '%' . $validatedData['cedula'] . '%');
        }

        if (isset($validatedData['correo_electronico'])) {
            $query->where('correo_electronico', 'like', '%' . $validatedData['correo_electronico'] . '%');
        }

        if (isset($validatedData['rol'])) {
            $query->where('rol', $validatedData['rol']);
        }

        // Filtrar por deleted_at
        if (isset($validatedData['deleted_at'])) {
            if ($validatedData['deleted_at']) {
                $query->onlyTrashed(); // Solo usuarios eliminados
            } else {
                $query->withTrashed(); // Todos los usuarios, incluidos los eliminados
            }
        }

        // Limitar la información basada en el rol del usuario autenticado
        if ($this->verificarRol('owner')) {
            $query->whereHas('owners', function ($q) use ($authUser) {
                $q->where('owner_id', $authUser->id);
            });
        }

        // Obtener el número de resultados por página, por defecto 15
        $perPage = $validatedData['per_page'] ?? 15;

        // Obtener los resultados paginados
        $usuarios = $query->paginate($perPage);

        return response()->json($usuarios);
    }
}
