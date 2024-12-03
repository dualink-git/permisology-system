<?php

namespace PermisologySystem\PermisologySystem;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Illuminate\Support\Facades\Schema;

class PermisologySystemPlugin implements Plugin
{
    public function getId(): string
    {
        return 'permisology-system';
    }

    public function register(Panel $panel): void
    {
        //
    }

    public function boot(Panel $panel): void
    {
        $requiredPackages = [
            'filament/filament' => '^3.2',
            'spatie/laravel-permission' => '^6.10',
        ];

        foreach ($requiredPackages as $package => $version) {
            if (!class_exists($package)) {
                throw new \Exception("The package {$package} with version {$version} is required to use PermisologySystem.");
            }
        }

        $requiredTables = ['permissions', 'roles', 'model_has_permissions', 'model_has_roles', 'role_has_permissions'];

        foreach ($requiredTables as $table) {
            if (!Schema::hasTable($table)) {
                throw new \Exception("The required table `{$table}` does not exist. Make sure to publish and run the migrations for Spatie Laravel Permission.");
            }
        }
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
