<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">Cambiar Contexto de Agencia</h3>
                @if($selectedAgency)
                    <x-filament::badge color="success">
                        Agencia Activa
                    </x-filament::badge>
                @else
                    <x-filament::badge color="warning">
                        Vista Global
                    </x-filament::badge>
                @endif
            </div>

            <form wire:submit.prevent="save">
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Selecciona una agencia para ver sus datos:
                        </label>
                        <select
                            wire:model.live="selectedAgency"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700"
                        >
                            <option value="">Vista Global (Sin Agencia)</option>
                            @foreach($this->getAgencies() as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if($selectedAgency)
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700 dark:text-blue-300">
                                        Estás viendo los datos como si fueras parte de esta agencia. Solo verás cotizadores, cotizaciones y usuarios de esta agencia.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="bg-gray-50 dark:bg-gray-900/20 border border-gray-200 dark:border-gray-800 rounded-lg p-4">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        Estás en vista global. Puedes ver todos los datos de todas las agencias.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </form>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
