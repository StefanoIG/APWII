<?php

namespace App\Http\Controllers;

use App\Models\Planes;
use Illuminate\Http\Request;
//importar validator
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PlanController extends Controller
{

    private $clientId;
    private $clientSecret;
    private $paypalBaseUrl;

    // En el constructor, asegúrate de cargar correctamente las variables de entorno
    public function __construct()
    {
        $this->clientId = config('services.paypal.client_id');
        $this->clientSecret = config('services.paypal.secret');
        $this->paypalBaseUrl = config('services.paypal.base_url');
    }




    //Get de planes totales
    public function index()
    {
        $planes = Planes::all();
        return response()->json($planes);
    }

    //Get de un plan en especifico
    public function show($id)
    {
        $plan = Planes::find($id);
        return response()->json($plan);
    }

    public function store(Request $request)
    {
        // Validar request
        $validator = Validator::make($request->all(), [
            'product_id' => 'nullable|string|max:22',
            'name' => 'required|string|max:127',
            'description' => 'nullable|string|max:127',
            'status' => 'required|in:CREATED,ACTIVE',
            'billing_cycles' => 'required|json',
            'payment_preferences' => 'required|json',
            'taxes' => 'nullable|json',
            'quantity_supported' => 'boolean', // Esto valida que sea booleano
            'image_url' => 'nullable|url',
            'home_url' => 'nullable|url'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Obtener el token de PayPal
        $token = $this->obtenerTokenPayPal();
        if (!$token) {
            return response()->json(['error' => 'No se pudo obtener el token de PayPal'], 500);
        }

        // Crear el producto en PayPal
        $productoCreado = $this->crearProductoPayPal($token, $request);
        if (!$productoCreado) {
            return response()->json(['error' => 'No se pudo crear el producto en PayPal'], 500);
        }

        $productId = $productoCreado['id'] ?? null;

        if (!$productId) {
            return response()->json(['error' => 'No se pudo obtener la ID del producto'], 500);
        }

        // Crear el plan de suscripción en PayPal
        $paypalPlanId = $this->crearPlanSuscripcionPayPal($token, $request, $productId);
        if (!$paypalPlanId) {
            return response()->json(['error' => 'No se pudo crear el plan en PayPal'], 500);
        }

        // Crear el plan en la base de datos local, manejando 'quantity_supported'
        $plan = Planes::create([
            'product_id' => $productId,
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status,
            'billing_cycles' => $request->billing_cycles,
            'payment_preferences' => $request->payment_preferences,
            'taxes' => $request->taxes,
            'quantity_supported' => $request->has('quantity_supported') ? $request->quantity_supported : false, // Asignar valor por defecto
            'id_paypal' => $paypalPlanId,
        ]);

        return response()->json($plan, 201);
    }


    // Función para obtener el token de PayPal
    private function obtenerTokenPayPal()
    {
        // Asegurarse de que el cliente y el secreto no sean nulos
        if (empty($this->clientId) || empty($this->clientSecret)) {
            Log::error("PayPal Client ID o Secret están vacíos");
            return null;
        }

        try {
            // Codificar las credenciales en Base64
            $credentials = base64_encode($this->clientId . ':' . $this->clientSecret);

            // Realizar la solicitud para obtener el token
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $credentials,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->asForm()->post("{$this->paypalBaseUrl}/v1/oauth2/token", [
                'grant_type' => 'client_credentials',
            ]);

            // Comprobar si la solicitud fue exitosa
            if ($response->successful()) {
                //log imprimiendo el acces_token
                Log::info("Token de PayPal obtenido: " . $response->json()['access_token']);
                return $response->json()['access_token'];
            } else {
                // Loggear el error en caso de que la solicitud falle
                Log::error("Error al obtener el token de PayPal", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }
        } catch (\Exception $e) {
            // Capturar y loggear cualquier excepción
            Log::error("Excepción al obtener el token de PayPal: " . $e->getMessage());
            return null;
        }
    }

    // Función para crear un producto en PayPal
    private function crearProductoPayPal($token, Request $request)
    {
        $response = Http::withToken($token)
            ->post("{$this->paypalBaseUrl}/v1/catalogs/products", [
                'name' => $request->name,
                'description' => $request->description ?? 'Plan de suscripción para InventoryPro',
                'type' => 'SERVICE',
                'category' => 'SOFTWARE',
                'image_url' => $request->image_url ?? null,  // Opcional, si tienes una URL de imagen
                'home_url' => $request->home_url ?? null    // Opcional, si tienes una URL de inicio
            ]);

        if (!$response->successful()) {
            // Loggear el error en caso de que la solicitud falle
            Log::error("Error al crear el producto en PayPal", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        return $response->successful() ? $response->json() : null;
    }

    /// Función para crear el plan de suscripción en PayPal
    private function crearPlanSuscripcionPayPal($token, Request $request, $productId)
    {
        $taxes = [
            'percentage' => '15',
            'inclusive' => false
        ];

        $billingCycles = json_decode($request->billing_cycles, true);
        $correctedBillingCycles = [];
        $sequence = 1;

        foreach ($billingCycles as $cycle) {
            $cycle['sequence'] = $sequence++;
            $correctedBillingCycles[] = $cycle;
        }

        $data = [
            'product_id' => $productId,
            'name' => $request->name,
            'description' => $request->description ?? 'Descripción del plan',
            'billing_cycles' => $correctedBillingCycles,
            'payment_preferences' => json_decode($request->payment_preferences, true),
            'taxes' => $taxes,
        ];

        $response = Http::withToken($token)
            ->post("{$this->paypalBaseUrl}/v1/billing/plans", $data);

        Log::info('Respuesta de PayPal al crear el plan', [
            'status' => $response->status(),
            'body' => $response->json(),
        ]);

        if ($response->successful()) {
            $planData = $response->json();

            if (isset($planData['id'])) {
                return $planData['id']; // Devolver solo el ID de PayPal
            } else {
                Log::error('El campo id no está presente en la respuesta de PayPal', ['response' => $planData]);
                return null;
            }
        } else {
            Log::error("Error al crear el plan de suscripción en PayPal", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        }
    }




    //Actualizar un plan
    public function update(Request $request, $id)
    {
        //validar request
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|string|max:22',
            'name' => 'required|string|max:127',
            'description' => 'nullable|string|max:127',
            'status' => 'required|in:CREATED,ACTIVE',
            'billing_cycles' => 'required|json',
            'payment_preferences' => 'required|json',
            'taxes' => 'nullable|json',
            'quantity_supported' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $plan = Planes::findOrFail($id);
        $plan->update($request->all());
        return response()->json($plan, 200);
    }


    //eleminar un plan
    public function delete($id)
    {
        Planes::findOrFail($id)->delete();
        return response()->json(['message' => 'Plan deleted'], 200);
    }
}
