<?php

use Illuminate\Support\Facades\Route;
use App\Models\Cotizador;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/preview/{cotizador}', function (Cotizador $cotizador) {
    $exporter = new \App\Services\CotizadorExportService();
    $html = $exporter->generateHtmlPreview($cotizador);
    return response($html)->header('Content-Type', 'text/html');
})->name('cotizador.preview');