<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use App\Models\Cotizador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CotizacionController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cotizador_slug' => 'required|string|exists:cotizadores,slug',
            'plan_selected' => 'required|string',
            'num_adultos' => 'required|integer|min:0',
            'num_ninos' => 'required|integer|min:0',
            'acomodacion' => 'nullable|string',
            'precio_base' => 'required|numeric|min:0',
            'precio_adicionales' => 'required|numeric|min:0',
            'precio_total' => 'required|numeric|min:0',
            'noches_adicionales' => 'nullable|integer|min:0',
            'almuerzos_adicionales' => 'nullable|integer|min:0',
            'cenas_adicionales' => 'nullable|integer|min:0',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'customer_email' => 'nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $cotizador = Cotizador::where('slug', $request->cotizador_slug)->firstOrFail();

        $cotizacion = Cotizacion::create([
            'cotizador_id' => $cotizador->id,
            'agency_id' => $cotizador->agency_id,
            'plan_selected' => $request->plan_selected,
            'num_adultos' => $request->num_adultos,
            'num_ninos' => $request->num_ninos,
            'acomodacion' => $request->acomodacion,
            'precio_base' => $request->precio_base,
            'precio_adicionales' => $request->precio_adicionales,
            'precio_total' => $request->precio_total,
            'noches_adicionales' => $request->noches_adicionales ?? 0,
            'almuerzos_adicionales' => $request->almuerzos_adicionales ?? 0,
            'cenas_adicionales' => $request->cenas_adicionales ?? 0,
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'customer_email' => $request->customer_email,
            'source' => 'whatsapp_click',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cotización registrada correctamente',
            'data' => [
                'id' => $cotizacion->id,
            ]
        ], 201);
    }
}