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
use App\Mail\RecoveryPassword;
use App\Mail\WelcomeMail;
use App\Mail\DemoMail;
use App\Mail\RequestDemoMail;
use App\Models\Demo;
use App\Mail\DemoRejectedMail;
use Illuminate\Support\Facades\Mail;


class UsuarioController extends Controller

{
    /**
     * Mostrar la lista de usuarios.
     */
    public function index()
    {
        $user = Auth::user(); // Obtiene el usuario autenticado

        if ($user->rol === 'admin') {
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
            'rol' => 'in:empleado,admin,demo,owner',
            'demo',
            'owner',
            'admin',  // Validación del rol
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Creación del usuario
        $usuario = Usuario::create([
            'nombre' => $request->nombre,
            'apellido' => $request->apellido,
            'telefono' => $request->telefono,
            'cedula' => $request->cedula,
            'correo_electronico' => $request->correo_electronico,
            'password' => $request->password,
            'rol' => $request->rol ?? 'empleado',  // Rol por defecto
        ]);
        Mail::to($usuario->correo_electronico)->send(new WelcomeMail($usuario));
        return response()->json(['message' => 'Usuario creado exitosamente', 'usuario' => $usuario], 201);
    }

    /**
     * Editar la información del usuario.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user(); // Obtiene el usuario autenticado
        $usuario = Usuario::findOrFail($id);

        // Verificar permisos
        if ($user->rol === 'empleado' && $user->id !== $usuario->id) {
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
            'rol' => 'sometimes|in:empleado,admin,demo,owner',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Actualización del usuario
        if ($user->rol === 'empleado') {
            // Solo permitir actualización de ciertos campos para empleados
            $requestData = $request->only(['nombre', 'apellido', 'telefono', 'cedula', 'correo_electronico']);
            if ($request->has('contrasena')) {
                $requestData['contrasena'] = bcrypt($request->contrasena);
            }
            $usuario->update($requestData);
        } else {
            // Admin puede actualizar todos los campos
            if ($request->has('contrasena')) {
                $request->merge(['contrasena' => bcrypt($request->contrasena)]);
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
            $resetUrl = url("/reset-password?token={$token}&correo_electronico={$request->correo_electronico}");

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
        }
    }


    // Aprobación de demo
    public function approveDemo(Request $request)
    {
        // Validar el ID de la demo a aprobar
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:demo,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
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
                'password' => Hash::make($passwordPlano),
                'rol' => 'demo',
            ]);

            // Actualizar la tabla demo con el ID del usuario y marcar como activa
            $demoRequest->update([
                'usuario_id' => $usuarioDemo->id,
                'isActive' => true,
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
        // Find the user by ID
        $usuario = Usuario::find($id);

        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        // Soft delete the user
        $usuario->delete();

        return response()->json(['message' => 'Usuario eliminado correctamente'], 200);
    }
}
