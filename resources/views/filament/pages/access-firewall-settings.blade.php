{{-- resources/views/filament/pages/access-firewall-settings.blade.php --}}
<x-filament-panels::page>
    <form wire:submit.prevent="save">
        <div class="shadow rounded-lg p-6">
            <div class="flex flex-col lg:flex-row gap-4">
                <!-- Sección de checkboxes, ocupa 2/3 del espacio -->
                <div class="flex flex-col gap-2 w-full lg:w-2/3">
                    <label class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            class="form-checkbox h-5 w-5 text-blue-600"
                            wire:model.defer="enable_ip_location"
                            @if($enable_ip_location) checked @endif
                        />
                        <span>Enable IP Location Control</span>
                    </label>

                    <label class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            class="form-checkbox h-5 w-5 text-blue-600"
                            wire:model.defer="enable_dns_location"
                            @if($enable_dns_location) checked @endif
                        />
                        <span>Enable DNS Location Control</span>
                    </label>

                    <label class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            class="form-checkbox h-5 w-5 text-blue-600"
                            wire:model.defer="enable_monitoring_control"
                            @if($enable_monitoring_control) checked @endif
                        />
                        <span>Enable Monitoring Control</span>
                    </label>

                    <label class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            class="form-checkbox h-5 w-5 text-blue-600"
                            wire:model.defer="enable_unknown_ip_alert"
                            @if($enable_unknown_ip_alert) checked @endif
                        />
                        <span>Enable Unknown IP Alert</span>
                    </label>
                </div>

                <!-- Sección del campo de entrada para la ruta base de la API, ocupa 1/3 del espacio -->
                <div class="flex flex-col gap-1 w-full lg:w-1/3">
                    <label for="api_base_path">API Base Path</label>
                    <input
                        type="text"
                        id="api_base_path"
                        class="form-input w-full px-3 py-2 border rounded-md text-sm"
                        wire:model.defer="api_base_path"
                        placeholder="e.g., api/"
                    />
                    <small class="text-gray-500">
                        Specify the base path for the API routes (e.g., "api/").
                    </small>
                </div>
            </div>

            <div class="mt-6 text-center">
                <x-filament::button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                    Save Settings
                </x-filament::button>
            </div>
        </div>
    </form>
</x-filament-panels::page>
