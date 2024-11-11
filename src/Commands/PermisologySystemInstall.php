<?php

namespace PermisologySystem\Commands;

use App\Models\PermisologySystem\AccessFirewallSettings;
use App\Models\PermisologySystem\Permission;
use App\Models\PermisologySystem\UserPermisologySystem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class PermisologySystemInstall extends Command
{
    protected $signature = 'permisology-system:install';

    protected $description = 'Install the Permisology System with necessary configurations';

    public function handle()
    {
        $this->info('Starting Permisology System installation...');

        // 1. Verificar y instalar dependencias
        $this->installDependencies();

        // 1. Verificar que Filament y los paneles estén instalados
        if (! $this->isFilamentInstalled()) {
            $this->error('Filament is not installed. Please install Filament using "composer require filament/filament" before running this command.');

            return Command::FAILURE;
        }

        // 3. Ejecutar migraciones
        $this->info('Running migrations...');
        Artisan::call('migrate');

        $this->info('Add active field in users if it does not exist...');
        $this->addActiveFieldIfNeeded();

        // 2. Crear rol y permisos necesarios
        $this->info('Setting up roles and permissions...');
        $this->setupRolesAndPermissions();

        $this->info('Registering middleware...');
        $this->registerMiddleware();

        $this->info('Permisology System installation completed successfully!');

        return Command::SUCCESS;
    }

    private function installDependencies()
    {
        // Opción para instalar Filament en require o require-dev
        if (! class_exists('Filament\FilamentServiceProvider')) {
            $filamentInstallInDev = $this->confirm('Do you want to install Filament only for development?', true);
            $filamentCommand = $filamentInstallInDev
                ? 'composer require filament/filament:"^3.2" --dev -W'
                : 'composer require filament/filament:"^3.2" -W';
            $this->info('Installing Filament...');
            shell_exec($filamentCommand);
        }

        // Opción para instalar Ignition en require o require-dev
        if (! class_exists('Spatie\LaravelIgnition\LaravelIgnitionServiceProvider')) {
            $ignitionInstallInDev = $this->confirm('Do you want to install Spatie Laravel Ignition only for development?', true);
            $ignitionCommand = $ignitionInstallInDev
                ? 'composer require spatie/laravel-ignition:"^2.0" --dev'
                : 'composer require spatie/laravel-ignition:"^2.0"';
            $this->info('Installing Spatie Laravel Ignition...');
            shell_exec($ignitionCommand);
        }
    }

    private function isFilamentInstalled()
    {
        // Verificar si las clases de Filament y Paneles están disponibles
        return class_exists('Filament\FilamentServiceProvider') && class_exists('Filament\Panel');
    }

    private function setupRolesAndPermissions()
    {
        $adminRoute = config('filament.path', 'admin');
        $permission = Permission::firstOrCreate([
            'name' => 'Super Admin Permissions',
            'guard_name' => 'web',
            'manual_routes' => ["$adminRoute/*", '*'],
        ]);

        // Crear rol "Super Admin"
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdminRole->givePermissionTo($permission);

        // Asignar el rol Super Admin a un usuario
        $this->assignSuperAdminRole($superAdminRole);
    }

    private function assignSuperAdminRole(Role $superAdminRole)
    {
        // Verificar usuarios existentes
        $users = UserPermisologySystem::all();
        $user = null;
        if ($users->isEmpty()) {
            $this->info('No users found. Creating a new user for Super Admin role.');
            $name = $this->ask('Enter name for the Super Admin user');
            $email = $this->ask('Enter email for the Super Admin user');
            $password = $this->secret('Enter password for the Super Admin user');

            $user = UserPermisologySystem::create([
                'name' => $name,
                'email' => $email,
                'password' => bcrypt($password),
                'active' => true,
            ]);

            $this->info("User {$user->name} created.");
        } else {
            $this->info('Available users:');
            foreach ($users as $user) {
                $this->line("ID: {$user->id} | Name: {$user->name} | Email: {$user->email}");
            }

            $userId = $this->ask('Enter the ID of the user to assign the Super Admin role, or press Enter to create a new user');

            if ($userId) {
                $user = UserPermisologySystem::find($userId);
                if (! $user) {
                    $this->error("User with ID {$userId} not found.");

                    return;
                }
            } else {
                $name = $this->ask('Enter name for the Super Admin user');
                $email = $this->ask('Enter email for the Super Admin user');
                $password = $this->secret('Enter password for the Super Admin user');

                $user = UserPermisologySystem::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => bcrypt($password),
                    'active' => true,
                ]);

                $this->info("User {$user->name} created.");
            }
        }

        $user->assignRole($superAdminRole);

        AccessFirewallSettings::updateOrCreate(
            ['id' => 1],
            ['super_main_administrator_id' => $user->id]
        );

        $this->info("Super Admin role assigned to {$user->name}.");
    }

    private function addActiveFieldIfNeeded()
    {
        if (! Schema::hasColumn('users', 'active')) {
            // Crear una migración temporal para agregar el campo `active`
            Schema::table('users', function ($table) {
                $table->boolean('active')->default(true)->after('password');
            });
            $this->info('Added "active" column to "users" table with default value as true.');
        } else {
            $this->info('The "active" column already exists in the "users" table.');
        }
    }

    private function registerMiddleware()
    {
        // Aquí puedes agregar la lógica para registrar middleware
        // Dependiendo de tu aplicación, podrías añadirlo al kernel o hacer ajustes adicionales
        $this->info('Middleware registration logic goes here.');
    }
}
