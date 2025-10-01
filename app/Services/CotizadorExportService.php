<?php

namespace App\Services;

use App\Models\Cotizador;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class CotizadorExportService
{
    public function exportToHtml(Cotizador $cotizador): string
    {
        // Convertir los datos del cotizador al formato JavaScript
        $config = $this->convertToJsConfig($cotizador);
        
        // Leer la plantilla base
        $template = $this->getHtmlTemplate();
        
        // Reemplazar el placeholder con la configuración
        $html = str_replace(
            '/* {{CONFIG_PLACEHOLDER}} */',
            $config,
            $template
        );
        
        // Guardar el archivo
        $filename = "cotizador-{$cotizador->slug}.html";
        $directory = "cotizadores";
        
        // Asegurar que el directorio existe
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }
        
        Storage::disk('public')->put("{$directory}/{$filename}", $html);
        
        // Actualizar el registro con la fecha de última exportación
        $cotizador->update([
            'last_export_at' => now(),
            'export_filename' => $filename,
        ]);
        
        return storage_path("app/public/{$directory}/{$filename}");
    }

    public function exportToJson(Cotizador $cotizador): string
    {
        // Convertir los datos del cotizador al formato JavaScript (reutilizamos el método)
        $config = $this->buildConfigArray($cotizador);
        
        // Convertir a JSON
        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        // Guardar el archivo
        $filename = "cotizador-{$cotizador->slug}.json";
        $directory = "cotizadores/json";
        
        // Asegurar que el directorio existe
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }
        
        Storage::disk('public')->put("{$directory}/{$filename}", $json);
        
        // Actualizar el registro
        $cotizador->update([
            'last_export_at' => now(),
            'export_filename' => $filename,
        ]);
        
        return storage_path("app/public/{$directory}/{$filename}");
    }

    private function buildConfigArray(Cotizador $cotizador): array
    {
        // Convertir planes al formato correcto
        $plans = [];
        foreach ($cotizador->plans as $plan) {
            $key = $plan['key'];
            $plans[$key] = [
                'name' => $plan['name'],
                'minPeople' => (int) $plan['minPeople'],
                'prices' => [
                    'sencilla' => $this->getPrice($plan, 'sencilla'),
                    'doble' => $this->getPrice($plan, 'doble'),
                    'triple' => $this->getPrice($plan, 'triple'),
                    'cuadruple' => $this->getPrice($plan, 'cuadruple'),
                    'nino' => $this->getPrice($plan, 'nino'),
                ],
                'nino_desc' => $plan['nino_desc'] ?? '',
            ];
        }
        
        // Convertir servicios adicionales
        $adicionales = [
            'noche' => [
                'sencilla' => $this->getPriceFromPath($cotizador->adicionales, ['noche', 'sencilla']),
                'doble' => $this->getPriceFromPath($cotizador->adicionales, ['noche', 'doble']),
                'triple' => $this->getPriceFromPath($cotizador->adicionales, ['noche', 'triple']),
                'cuadruple' => $this->getPriceFromPath($cotizador->adicionales, ['noche', 'cuadruple']),
                'nino' => $this->getPriceFromPath($cotizador->adicionales, ['noche', 'nino']),
            ],
            'comida' => $this->getPriceFromPath($cotizador->adicionales, ['comida']),
        ];
        
        // Convertir secciones de información
        $infoSections = [];
        if (!empty($cotizador->info_sections)) {
            foreach ($cotizador->info_sections as $section) {
                if (!empty($section['title']) && !empty($section['content'])) {
                    $infoSections[] = [
                        'title' => $section['title'],
                        'content' => $section['content'],
                    ];
                }
            }
        }
        
        return [
            'hotelName' => $cotizador->hotel_name,
            'hotelSlogan' => $cotizador->hotel_slogan,
            'priceValidity' => $cotizador->price_validity ?? '',
            'whatsappNumber' => $cotizador->whatsapp_number,
            'operatorInfo' => $cotizador->operator_info,
            'plans' => $plans,
            'adicionales' => $adicionales,
            'infoSections' => $infoSections,
        ];
    }

    private function convertToJsConfig(Cotizador $cotizador): string
    {
        $config = $this->buildConfigArray($cotizador);
        
        // Convertir a JSON
        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        // Reemplazar null con Infinity para precios no disponibles
        $json = preg_replace('/"(sencilla|doble|triple|cuadruple|nino|comida)":\s*null/m', '"$1": Infinity', $json);
        
        return "const config = {$json};";
    }

    private function getPrice(array $plan, string $type): ?int
    {
        $price = $plan['prices'][$type] ?? null;
        return !empty($price) ? (int) $price : null;
    }
    
    private function getPriceFromPath(array $data, array $path): ?int
    {
        $value = $data;
        foreach ($path as $key) {
            if (!isset($value[$key])) {
                return null;
            }
            $value = $value[$key];
        }
        return !empty($value) ? (int) $value : null;
    }
    
    private function getHtmlTemplate(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Cotizador de Viajes</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <style>
      body {
        font-family: 'Inter', sans-serif;
      }
      .plan-radio:checked + label,
      .acomodacion-radio:checked + label {
        border-color: #2563eb;
        background-color: #eff6ff;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
      }
    </style>
  </head>
  <body class="bg-gray-100 text-gray-800">
    <div class="container mx-auto p-4 md:p-8 max-w-6xl">
      <header class="text-center mb-8">
        <h1
          id="header-title"
          class="text-3xl md:text-4xl font-bold text-gray-900"
        ></h1>
        <p id="header-slogan" class="text-lg text-gray-600 mt-2"></p>
      </header>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-6">
          <div class="bg-white p-6 rounded-xl shadow-md">
            <h2 class="text-xl font-semibold mb-4">1. Selecciona tu Plan</h2>
            <div
              id="plan-options"
              class="grid grid-cols-1 md:grid-cols-2 gap-4"
            >
            </div>
          </div>

          <div class="bg-white p-6 rounded-xl shadow-md">
            <h2 class="text-xl font-semibold mb-1">
              2. Ingresa el número de viajeros
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
              <div>
                <label
                  for="numAdultos"
                  class="block text-sm font-medium text-gray-700"
                  >Número de Adultos</label
                >
                <input
                  type="number"
                  id="numAdultos"
                  min="1"
                  value="1"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2"
                />
              </div>
              <div>
                <label
                  for="numNinos"
                  class="block text-sm font-medium text-gray-700"
                  >Número de Niños</label
                >
                <input
                  type="number"
                  id="numNinos"
                  min="0"
                  value="0"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2"
                />
              </div>
            </div>
            <p class="text-sm text-gray-500 mt-2" id="nino-description"></p>
          </div>

          <div class="bg-white p-6 rounded-xl shadow-md">
            <h2 class="text-xl font-semibold mb-4">
              3. Elige la mejor acomodación
            </h2>
            <div id="accommodation-options" class="space-y-4">
              <p class="text-gray-500 text-center">
                Ingresa el número de personas para ver las opciones disponibles.
              </p>
            </div>
          </div>

          <div class="bg-white p-6 rounded-xl shadow-md">
            <h2 class="text-xl font-semibold mb-1">
              4. Agrega Servicios Adicionales (Opcional)
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
              <div>
                <label
                  for="numNoches"
                  class="block text-sm font-medium text-gray-700"
                  >Noches Adicionales</label
                >
                <input
                  type="number"
                  id="numNoches"
                  min="0"
                  value="0"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2"
                />
              </div>
              <div>
                <label
                  for="numAlmuerzos"
                  class="block text-sm font-medium text-gray-700"
                  >Almuerzos Adicionales</label
                >
                <input
                  type="number"
                  id="numAlmuerzos"
                  min="0"
                  value="0"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2"
                />
              </div>
              <div>
                <label
                  for="numCenas"
                  class="block text-sm font-medium text-gray-700"
                  >Cenas Adicionales</label
                >
                <input
                  type="number"
                  id="numCenas"
                  min="0"
                  value="0"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2"
                />
              </div>
            </div>
          </div>
        </div>

        <div class="lg:sticky lg:top-8 h-fit">
          <div class="bg-white p-6 rounded-xl shadow-lg">
            <h2 class="text-2xl font-bold border-b pb-4 mb-4">
              Resumen de la Cotización
            </h2>

            <div id="summary-details" class="space-y-3 text-gray-600">
              <div class="flex justify-between items-center">
                <span>Plan:</span>
                <span id="summary-plan" class="font-semibold text-right"
                  >No seleccionado</span
                >
              </div>
              <div class="flex justify-between items-center">
                <span>Viajeros:</span>
                <span id="summary-people" class="font-semibold text-right"
                  >0</span
                >
              </div>
              <div class="flex justify-between items-center">
                <span>Acomodación:</span>
                <span id="summary-acomodacion" class="font-semibold text-right"
                  >No seleccionada</span
                >
              </div>
              <div
                id="summary-price-breakdown"
                class="hidden pl-4 border-l-2 border-gray-200 ml-2 space-y-2"
              >
                <div class="flex justify-between items-center text-sm">
                  <span>Subtotal Adultos:</span>
                  <span
                    id="summary-adult-price"
                    class="font-semibold text-right"
                    >$ 0</span
                  >
                </div>
                <div class="flex justify-between items-center text-sm">
                  <span>Subtotal Niños:</span>
                  <span
                    id="summary-child-price"
                    class="font-semibold text-right"
                    >$ 0</span
                  >
                </div>
              </div>
              <div
                id="summary-adicionales-breakdown"
                class="hidden pl-4 border-l-2 border-gray-200 ml-2 space-y-2 pt-2 mt-2 border-t"
              >
                <div class="flex justify-between items-center text-sm">
                  <span>Noches Adicionales:</span>
                  <span
                    id="summary-noches-price"
                    class="font-semibold text-right"
                    >$ 0</span
                  >
                </div>
                <div class="flex justify-between items-center text-sm">
                  <span>Comidas Adicionales:</span>
                  <span
                    id="summary-comidas-price"
                    class="font-semibold text-right"
                    >$ 0</span
                  >
                </div>
              </div>
            </div>

            <div
              id="error-message"
              class="hidden mt-4 text-center bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg"
              role="alert"
            >
              <strong class="font-bold">¡Atención!</strong>
              <span class="block sm:inline" id="error-text"></span>
            </div>

            <div class="mt-6 pt-4 border-t">
              <p class="text-lg text-gray-600">Total Estimado:</p>
              <p id="total-price" class="text-4xl font-bold text-gray-900 my-2">
                $ 0
              </p>
            </div>

            <button
              id="whatsapp-button"
              class="mt-6 w-full bg-green-500 text-white font-bold py-3 px-4 rounded-lg hover:bg-green-600 transition-colors flex items-center justify-center space-x-2 text-lg disabled:bg-gray-400 disabled:cursor-not-allowed"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="currentColor"
              >
                <path
                  d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.894 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.447-4.435-9.884-9.888-9.884-5.448 0-9.886 4.434-9.889 9.884-.001 2.225.651 4.315 1.847 6.062l-1.082 3.945 4.045-1.056zM15.03 12.036c-.112-.057-1.157-.574-1.338-.639-.181-.065-.315-.099-.449.099-.133.197-.506.639-.62.774-.114.133-.228.15-.41.093-.182-.057-.767-.282-1.46-.905-.542-.472-.9-.838-1.009-.973-.112-.133-.012-.204.045-.259.053-.053.112-.133.169-.199.057-.065.076-.112.114-.188.038-.076.019-.134-.009-.191s-.449-1.082-.614-1.479c-.161-.389-.328-.335-.449-.342-.112-.009-.246-.009-.38-.009s-.351.057-.533.246c-.182.197-.691.677-.691 1.654s.71 1.915.81 2.05c.098.133 1.392 2.132 3.37 2.96.471.197.837.315 1.12.402.32.093.606.076.829-.045.25-.133.767-.838.875-1.01.108-.172.108-.315.076-.371z"
                />
              </svg>
              <span>Cotizar por WhatsApp</span>
            </button>
            <p
              id="operator-info"
              class="text-xs text-center text-gray-500 mt-2"
            ></p>
          </div>
        </div>
      </div>

      <div id="info-sections" class="mt-10 space-y-4">
      </div>

      <div class="mt-10 bg-white p-8 rounded-xl shadow-md">
        <h2 class="text-2xl font-bold text-center text-gray-900 mb-2">
          ¡No te pierdas nuestras promociones!
        </h2>
        <p class="text-center text-gray-600 mb-6">
          Regístrate para recibir ofertas especiales y novedades directamente en
          tu correo.
        </p>
        <form action="#" method="POST" class="max-w-xl mx-auto">
          <div class="grid grid-cols-1 gap-y-6 sm:grid-cols-2 sm:gap-x-8">
            <div>
              <label
                for="full-name"
                class="block text-sm font-medium text-gray-700"
                >Nombre completo</label
              >
              <div class="mt-1">
                <input
                  type="text"
                  name="full-name"
                  id="full-name"
                  autocomplete="name"
                  class="py-3 px-4 block w-full shadow-sm focus:ring-blue-500 focus:border-blue-500 border-gray-300 rounded-md"
                />
              </div>
            </div>
            <div>
              <label for="email" class="block text-sm font-medium text-gray-700"
                >Email</label
              >
              <div class="mt-1">
                <input
                  id="email"
                  name="email"
                  type="email"
                  autocomplete="email"
                  class="py-3 px-4 block w-full shadow-sm focus:ring-blue-500 focus:border-blue-500 border-gray-300 rounded-md"
                />
              </div>
            </div>
            <div class="sm:col-span-2">
              <label
                for="phone-number"
                class="block text-sm font-medium text-gray-700"
                >Celular</label
              >
              <div class="mt-1">
                <input
                  type="text"
                  name="phone-number"
                  id="phone-number"
                  autocomplete="tel"
                  class="py-3 px-4 block w-full shadow-sm focus:ring-blue-500 focus:border-blue-500 border-gray-300 rounded-md"
                />
              </div>
            </div>
            <div class="sm:col-span-2">
              <div class="flex items-start">
                <div class="flex-shrink-0">
                  <input
                    id="privacy-policy"
                    name="privacy-policy"
                    type="checkbox"
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                  />
                </div>
                <div class="ml-3">
                  <p class="text-sm text-gray-600">
                    Acepto la
                    <a
                      href="#"
                      class="font-medium text-blue-600 hover:text-blue-500"
                      >política de tratamiento de datos</a
                    >.
                  </p>
                </div>
              </div>
            </div>
            <div class="sm:col-span-2">
              <button
                type="submit"
                class="w-full inline-flex items-center justify-center px-6 py-3 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                Registrarme
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <script>
      /* {{CONFIG_PLACEHOLDER}} */

      document.addEventListener('DOMContentLoaded', () => {
        const data = config;

        const dom = {
          header: {
            title: document.getElementById('header-title'),
            slogan: document.getElementById('header-slogan'),
          },
          planOptions: document.getElementById('plan-options'),
          numAdultos: document.getElementById('numAdultos'),
          numNinos: document.getElementById('numNinos'),
          numNoches: document.getElementById('numNoches'),
          numAlmuerzos: document.getElementById('numAlmuerzos'),
          numCenas: document.getElementById('numCenas'),
          ninoDescription: document.getElementById('nino-description'),
          accommodationOptions: document.getElementById('accommodation-options'),
          infoSections: document.getElementById('info-sections'),
          summary: {
            plan: document.getElementById('summary-plan'),
            people: document.getElementById('summary-people'),
            acomodacion: document.getElementById('summary-acomodacion'),
            priceBreakdown: document.getElementById('summary-price-breakdown'),
            adultPrice: document.getElementById('summary-adult-price'),
            childPrice: document.getElementById('summary-child-price'),
            adicionalesBreakdown: document.getElementById('summary-adicionales-breakdown'),
            nochesPrice: document.getElementById('summary-noches-price'),
            comidasPrice: document.getElementById('summary-comidas-price'),
            totalPrice: document.getElementById('total-price'),
            operatorInfo: document.getElementById('operator-info'),
          },
          error: {
            container: document.getElementById('error-message'),
            text: document.getElementById('error-text'),
          },
          whatsappButton: document.getElementById('whatsapp-button'),
        };

        let state = {
          basePrice: 0,
          selectedCombination: null,
        };

        const formatCurrency = (value) =>
          new Intl.NumberFormat('es-CO', {
            style: 'currency',
            currency: 'COP',
            minimumFractionDigits: 0,
          }).format(value);

        const roomTypes = {
          4: 'Cuádruple',
          3: 'Triple',
          2: 'Doble',
          1: 'Sencilla',
        };

        function findCombinations(target, start = 4, combination = {}) {
          if (target === 0) return [combination];
          if (target < 0 || start === 0) return [];
          let results = [];
          for (let i = Math.min(start, target); i >= 1; i--) {
            const newCombination = { ...combination };
            newCombination[i] = (newCombination[i] || 0) + 1;
            const subResults = findCombinations(target - i, i, newCombination);
            results = results.concat(subResults);
          }
          return results;
        }

        function combinationToString(combination) {
          return Object.entries(combination)
            .sort((a, b) => b[0] - a[0])
            .map(([roomSize, count]) => `${count} ${roomTypes[roomSize]}`)
            .join(' + ');
        }

        function calculateCombinationPrice(combination, plan, numNinos) {
          let priceSlots = [];
          const roomPrices = {
            1: 'sencilla',
            2: 'doble',
            3: 'triple',
            4: 'cuadruple',
          };

          for (const [roomSize, count] of Object.entries(combination)) {
            const roomKey = roomPrices[roomSize];
            const pricePerPerson = plan.prices[roomKey];
            if (!isFinite(pricePerPerson))
              return { total: Infinity, adultTotal: 0, childTotal: 0 };
            for (let i = 0; i < count * parseInt(roomSize); i++) {
              priceSlots.push(pricePerPerson);
            }
          }
          priceSlots.sort((a, b) => a - b);
          let adultTotal = 0;
          let childTotal = 0;
          for (let i = 0; i < priceSlots.length; i++) {
            if (i < numNinos) {
              childTotal += plan.prices.nino;
            } else {
              adultTotal += priceSlots[i];
            }
          }
          const total = adultTotal + childTotal;
          return { total, adultTotal, childTotal };
        }

        function calculateAdicionalesPrice() {
          if (!state.selectedCombination)
            return { nochesTotal: 0, comidasTotal: 0 };

          const numNinos = parseInt(dom.numNinos.value) || 0;
          const numAdultos = parseInt(dom.numAdultos.value) || 0;
          const totalPersonas = numAdultos + numNinos;

          const numNoches = parseInt(dom.numNoches.value) || 0;
          const numAlmuerzos = parseInt(dom.numAlmuerzos.value) || 0;
          const numCenas = parseInt(dom.numCenas.value) || 0;

          const comidasTotal =
            (numAlmuerzos + numCenas) * totalPersonas * (data.adicionales.comida || 0);

          let nocheUnitariaTotal = 0;
          if (numNoches > 0) {
            const roomPrices = {
              1: 'sencilla',
              2: 'doble',
              3: 'triple',
              4: 'cuadruple',
            };
            let ninosToDistribute = numNinos;

            for (const [roomSize, count] of Object.entries(state.selectedCombination)) {
              for (let i = 0; i < count; i++) {
                const roomKey = roomPrices[roomSize];
                const pricePerPerson = data.adicionales.noche[roomKey] || 0;
                for (let j = 0; j < roomSize; j++) {
                  if (ninosToDistribute > 0) {
                    nocheUnitariaTotal += data.adicionales.noche.nino || 0;
                    ninosToDistribute--;
                  } else {
                    nocheUnitariaTotal += pricePerPerson;
                  }
                }
              }
            }
          }
          const nochesTotal = nocheUnitariaTotal * numNoches;

          return { nochesTotal, comidasTotal };
        }

        function updateTotals() {
          const adicionales = calculateAdicionalesPrice();
          const totalAdicional = adicionales.nochesTotal + adicionales.comidasTotal;

          dom.summary.nochesPrice.textContent = formatCurrency(adicionales.nochesTotal);
          dom.summary.comidasPrice.textContent = formatCurrency(adicionales.comidasTotal);

          dom.summary.adicionalesBreakdown.classList.toggle('hidden', totalAdicional <= 0);

          dom.summary.totalPrice.textContent = formatCurrency(state.basePrice + totalAdicional);
        }

        function updateUI() {
          const selectedPlanKey = document.querySelector('input[name="plan"]:checked')?.value;
          if (!selectedPlanKey) return;

          const plan = data.plans[selectedPlanKey];
          const numAdultos = parseInt(dom.numAdultos.value) || 0;
          const numNinos = parseInt(dom.numNinos.value) || 0;
          const totalPersonas = numAdultos + numNinos;
          let error = null;

          if (totalPersonas < 1) error = 'Ingresa al menos 1 viajero.';
          else if (numAdultos < 1 && numNinos > 0)
            error = 'Debe haber al menos un adulto.';
          else if (totalPersonas < plan.minPeople)
            error = `Este plan requiere un mínimo de ${plan.minPeople} personas.`;

          dom.ninoDescription.textContent = plan.nino_desc || '';
          dom.summary.plan.textContent = plan.name;
          dom.summary.people.textContent = `${totalPersonas} (${numAdultos} Adultos, ${numNinos} Niños)`;

          dom.error.container.classList.toggle('hidden', !error);
          if (error) {
            dom.error.text.textContent = error;
            dom.accommodationOptions.innerHTML = `<p class="text-gray-500 text-center">${error}</p>`;
            resetSummary();
            return;
          }

          const combinations = findCombinations(totalPersonas);

          const pricedCombinations = combinations
            .map((combo, index) => {
              const prices = calculateCombinationPrice(combo, plan, numNinos);
              return {
                id: `combo-${index}`,
                text: combinationToString(combo),
                combination: JSON.stringify(combo),
                prices: prices,
              };
            })
            .filter((c) => isFinite(c.prices.total))
            .sort((a, b) => a.prices.total - b.prices.total);

          if (pricedCombinations.length > 0) {
            dom.accommodationOptions.innerHTML = pricedCombinations
              .map(
                (combo) => `
                        <div class="relative">
                           <input type="radio" name="acomodacion" id="${combo.id}" 
                                  value="${combo.prices.total}" 
                                  data-text="${combo.text}" 
                                  data-combination='${combo.combination}'
                                  data-price-adult="${combo.prices.adultTotal}"
                                  data-price-child="${combo.prices.childTotal}"
                                  class="hidden acomodacion-radio">
                           <label for="${combo.id}" class="flex justify-between items-center p-4 border-2 rounded-lg cursor-pointer transition-all duration-200 hover:border-blue-300">
                               <span class="font-medium text-gray-700">${combo.text}</span>
                               <span class="font-bold text-lg text-blue-600">${formatCurrency(combo.prices.total)}</span>
                           </label>
                        </div>
                    `
              )
              .join('');
          } else {
            dom.accommodationOptions.innerHTML = `<p class="text-gray-500 text-center">No hay acomodaciones válidas para este plan y número de personas.</p>`;
          }

          resetSummary();
        }

        function resetSummary() {
          dom.summary.acomodacion.textContent = 'No seleccionada';
          dom.summary.totalPrice.textContent = '$ 0';
          dom.summary.priceBreakdown.classList.add('hidden');
          dom.summary.adicionalesBreakdown.classList.add('hidden');
          dom.summary.adultPrice.textContent = '$ 0';
          dom.summary.childPrice.textContent = '$ 0';
          dom.numNoches.value = 0;
          dom.numAlmuerzos.value = 0;
          dom.numCenas.value = 0;
          state.basePrice = 0;
          state.selectedCombination = null;
          dom.whatsappButton.disabled = true;
        }

        function initializeApp() {
          dom.header.title.textContent = data.hotelName;
          document.title = `Cotizador - ${data.hotelName}`;
          dom.header.slogan.textContent = data.hotelSlogan;
          dom.summary.operatorInfo.textContent = data.operatorInfo;

          dom.planOptions.innerHTML = Object.keys(data.plans)
            .map(
              (key) => `
                    <div class="relative">
                        <input type="radio" id="${key}" name="plan" value="${key}" class="hidden plan-radio">
                        <label for="${key}" class="flex flex-col p-4 border-2 rounded-lg cursor-pointer transition-all duration-200 hover:border-blue-400">
                            <span class="font-semibold text-gray-800">${data.plans[key].name}</span>
                            ${
                              data.plans[key].minPeople > 1
                                ? `<span class="text-xs text-gray-500">Mínimo ${data.plans[key].minPeople} personas</span>`
                                : ''
                            }
                        </label>
                    </div>`
            )
            .join('');

          dom.infoSections.innerHTML = data.infoSections
            .map(
              (section) => `
                    <div class="bg-white rounded-xl shadow-md">
                        <details class="group p-6">
                            <summary class="flex items-center justify-between cursor-pointer list-none">
                                <h3 class="text-lg font-semibold text-gray-800">${section.title}</h3>
                                <span class="transition group-open:rotate-180">
                                    <svg fill="none" height="24" shape-rendering="geometricPrecision" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24" width="24"><path d="M6 9l6 6 6-6"></path></svg>
                                </span>
                            </summary>
                            <div class="mt-4 text-gray-600">${section.content}</div>
                        </details>
                    </div>
                `
            )
            .join('');

          document.querySelector('input[name="plan"]').checked = true;
          updateUI();
        }

        ['change', 'input'].forEach((evt) => {
          dom.planOptions.addEventListener(evt, updateUI);
          dom.numAdultos.addEventListener(evt, updateUI);
          dom.numNinos.addEventListener(evt, updateUI);
          dom.numNoches.addEventListener(evt, updateTotals);
          dom.numAlmuerzos.addEventListener(evt, updateTotals);
          dom.numCenas.addEventListener(evt, updateTotals);
        });

        dom.accommodationOptions.addEventListener('change', (e) => {
          if (e.target.name === 'acomodacion') {
            const selectedOption = e.target;
            const numNinos = parseInt(dom.numNinos.value) || 0;

            state.basePrice = parseFloat(selectedOption.value);
            state.selectedCombination = JSON.parse(selectedOption.dataset.combination);

            dom.summary.acomodacion.textContent = selectedOption.dataset.text;
            dom.summary.adultPrice.textContent = formatCurrency(
              parseFloat(selectedOption.dataset.priceAdult)
            );
            dom.summary.childPrice.textContent = formatCurrency(
              parseFloat(selectedOption.dataset.priceChild)
            );
            dom.summary.priceBreakdown.classList.toggle('hidden', numNinos <= 0);

            updateTotals();
            dom.whatsappButton.disabled = false;
          }
        });

        dom.whatsappButton.addEventListener('click', () => {
          const selectedPlanKey = document.querySelector('input[name="plan"]:checked')?.value;
          const selectedAcomodacion = document.querySelector('input[name="acomodacion"]:checked');
          if (!selectedPlanKey || !selectedAcomodacion) return;

          const plan = data.plans[selectedPlanKey];
          const numAdultos = parseInt(dom.numAdultos.value) || 0;
          const numNinos = parseInt(dom.numNinos.value) || 0;
          const totalPersonas = numAdultos + numNinos;

          const numNoches = parseInt(dom.numNoches.value) || 0;
          const numAlmuerzos = parseInt(dom.numAlmuerzos.value) || 0;
          const numCenas = parseInt(dom.numCenas.value) || 0;

          let priceBreakdownMessage = '';
          if (numNinos > 0) {
            priceBreakdownMessage =
              `\n*Subtotal Adultos:* ${dom.summary.adultPrice.textContent}\n` +
              `*Subtotal Niños:* ${dom.summary.childPrice.textContent}\n`;
          }

          let adicionalesMessage = '';
          if (numNoches > 0 || numAlmuerzos > 0 || numCenas > 0) {
            adicionalesMessage += '\n*Servicios Adicionales:*\n';
            if (numNoches > 0)
              adicionalesMessage += `- ${numNoches} Noche(s) Adicional(es): ${dom.summary.nochesPrice.textContent}\n`;
            if (numAlmuerzos > 0 || numCenas > 0)
              adicionalesMessage += `- Comidas Adicionales: ${dom.summary.comidasPrice.textContent}\n`;
          }

          let message =
            `¡Hola! 👋 Me gustaría solicitar una cotización con los siguientes detalles:\n\n` +
            `*Hotel/Tour:* ${data.hotelName}\n` +
            `*Plan:* ${plan.name}\n` +
            `*Total Viajeros:* ${totalPersonas} (${numAdultos} Adultos, ${numNinos} Niños)\n` +
            `*Acomodación:* ${selectedAcomodacion.dataset.text}\n` +
            `${priceBreakdownMessage}` +
            `${adicionalesMessage}\n` +
            `*TOTAL COTIZADO:* ${dom.summary.totalPrice.textContent}\n\n` +
            `Quedo a la espera de su respuesta. ¡Gracias!`;

          const encodedMessage = encodeURIComponent(message);
          window.open(`https://wa.me/${data.whatsappNumber}?text=${encodedMessage}`, '_blank');
        });

        initializeApp();
      });
    </script>
  </body>
</html>
HTML;
    }
}