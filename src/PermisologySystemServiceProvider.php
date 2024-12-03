<?php

namespace PermisologySystem\PermisologySystem;

use Filament\Support\Assets\Js;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Asset;
use Illuminate\Filesystem\Filesystem;
use Spatie\LaravelPackageTools\Package;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Facades\FilamentAsset;
use Livewire\Features\SupportTesting\Testable;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use PermisologySystem\PermisologySystem\Commands\PermisologySystemInstall;
use PermisologySystem\PermisologySystem\Commands\PermisologySystemCommand;

class PermisologySystemServiceProvider extends PackageServiceProvider
{
    public static string $name = 'permisology-system';

    public static string $viewNamespace = 'permisology-system';

    public function configurePackage(Package $package): void
    {
        $package
        ->name('permisology-system')
        ->hasConfigFile();

        $configFileName = $package->shortName();

        if (file_exists($package->basePath("/../config/{$configFileName}.php"))) {
            $package->hasConfigFile();
        }

        if (file_exists($package->basePath('/../database/migrations'))) {
            $package->hasMigrations($this->getMigrations());
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageBooted(): void
    {
        parent::packageBooted();

        if ($this->app->runningInConsole()) {
            $this->commands($this->getCommands());

            $this->publishes([
                __DIR__ . '/../resources/views/filament/pages/access-firewall-settings.blade.php' =>
                resource_path('views/filament/pages/access-firewall-settings.blade.php'),
            ], 'permisology-system-views');
        }

        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Icon Registration
        FilamentIcon::register($this->getIcons());

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'permisology-system');

        // Handle Stubs
        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/permisology-system/{$file->getFilename()}"),
                ], 'permisology-system-stubs');
            }
        }
    }

    protected function getAssetPackageName(): ?string
    {
        return 'dualink/permisology-system';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            // AlpineComponent::make('permisology-system', __DIR__ . '/../resources/dist/components/permisology-system.js'),
            //Css::make('permisology-system-styles', __DIR__ . '/../resources/dist/permisology-system.css'),
            //Js::make('permisology-system-scripts', __DIR__ . '/../resources/dist/permisology-system.js'),
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            PermisologySystemCommand::class,
            PermisologySystemInstall::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getRoutes(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            '1_allowed_routes_to_permissions_table',
            '2_create_access_firewall_settings_table',
            '3_create_black_location_lists_table',
            '4_create_public_location_access_end_point_lists_table',
            '5_create_user_location_access_end_point_lists_table',
        ];
    }
}
