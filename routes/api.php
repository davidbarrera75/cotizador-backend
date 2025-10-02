<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CotizacionController;

Route::post('/cotizaciones', [CotizacionController::class, 'store']);