<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Planes;
use Srmklive\PayPal\Services\PayPal as PayPalClient; // Importa el cliente PayPal


class PaypalController extends Controller
{

    public function index()
    {
        return view('paypal');
    }

    


    // Procesar el pago y asociarlo al usuario, si es exitoso
    public function payment(Request $request)
    {
        $plan = Planes::find($request->plan_id);

        if (!$plan) {
            return redirect()->route('paypal')->with('error', 'El plan no existe.');
        }

        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $paypalToken = $provider->getAccessToken();

        $response = $provider->createOrder([
            "intent" => "CAPTURE",
            "application_context" => [
                "return_url" => route('paypal.payment.success'),
                "cancel_url" => route('paypal.payment.cancel'),
            ],
            "purchase_units" => [
                0 => [
                    "amount" => [
                        "currency_code" => "USD",
                        "value" => $plan->price // Cobrar el monto del plan seleccionado
                    ]
                ]
            ]
        ]);

        if (isset($response['id']) && $response['id'] != null) {
            foreach ($response['links'] as $links) {
                if ($links['rel'] == 'approve') {
                    return redirect()->away($links['href']);
                }
            }
            return redirect()->route('paypal.payment.cancel')->with('error', 'Algo salió mal.');
        } else {
            return redirect()->route('paypal.payment.cancel')->with('error', $response['message'] ?? 'Algo salió mal.');
        }
    }


     public function paymentCancel()
    {
        return redirect()
              ->route('paypal')
              ->with('error', $response['message'] ?? 'You have canceled the transaction.');
    }
  
    /**
     * Write code on Method 
     * Written by Appfinz Technologies
     * @return response()
     */
    public function paymentSuccess(Request $request)
    {
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $provider->getAccessToken();
        $response = $provider->capturePaymentOrder($request['token']);
  
        if (isset($response['status']) && $response['status'] == 'COMPLETED') {
            return redirect()
                ->route('paypal')
                ->with('success', 'Transaction complete.');
        } else {
            return redirect()
                ->route('paypal')
                ->with('error', $response['message'] ?? 'Something went wrong.');
        }
    }
}
