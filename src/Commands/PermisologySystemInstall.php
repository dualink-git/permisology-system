<?php

namespace PermisologySystem\PermisologySystem\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Artisan;
use PermisologySystem\PermisologySystem\Models\PermisologySystem\Permission;
use PermisologySystem\PermisologySystem\Models\PermisologySystem\UserPermisologySystem;
use PermisologySystem\PermisologySystem\Models\PermisologySystem\AccessFirewallSettings;

class PermisologySystemInstall extends Command
{
    protected $signature = 'permisology-system:install';
    protected $description = 'Install the Permisology System with necessary configurations';

    public function handle()
    {
        $this->info('Starting Permisology System installation...');

        // 1. Verificar conexión a la base de datos
        if (!$this->checkDatabaseConnection()) {
            $this->error('Database connection is not properly configured. Please check your .env file.');
            return Command::FAILURE;
        }

        // 2. Ejecutar migraciones iniciales
        $this->info('Running initial migrations...');
        Artisan::call('migrate');
        $this->info(Artisan::output());

        // 3. Ejecutar composer install
        $this->info('Running composer install...');
        shell_exec('composer install');
        $this->info('Composer install completed.');

        // 4. Instalar Filament si falta
        if (!$this->isPackageInstalled('filament/filament')) {
            $this->info('Installing Filament...');
            shell_exec('composer require filament/filament:^3.2');
        }

        // 5. Configurar Filament
        $this->info('Setting up Filament panels...');
        $this->installFilamentPanles();

        // 6. Ejecutar migraciones nuevamente
        $this->info('Running migrations again...');
        Artisan::call('migrate');
        $this->info(Artisan::output());

        // 7. Publicar configuración de Filament
        $this->info('Publishing Filament config...');
        Artisan::call('vendor:publish', ['--tag' => 'filament-config']);
        $this->info(Artisan::output());

        $this->info('Registering middleware in Filament...');
        $this->registerFilamentMiddleware();

        // 8. Instalar Laravel Permission
        if (!$this->isPackageInstalled('spatie/laravel-permission')) {
            $this->info('Installing Laravel Permission...');
            shell_exec('composer require spatie/laravel-permission');
        }

        // 9. Publicar configuraciones de Laravel Permission
        $this->info('Publishing Laravel Permission migrations...');
        Artisan::call('vendor:publish', ['--provider' => 'Spatie\Permission\PermissionServiceProvider']);
        $this->info(Artisan::output());

        // 10. Ejecutar migraciones para Laravel Permission
        $this->info('Running migrations for Laravel Permission...');
        Artisan::call('migrate');
        $this->info(Artisan::output());

        // 11. Publicar migraciones del sistema Permisology
        $this->info('Publishing Permisology System migrations...');
        Artisan::call('vendor:publish', ['--tag' => 'permisology-system-migrations']);
        $this->info(Artisan::output());

        // 12. Ejecutar migraciones del sistema Permisology
        $this->info('Running migrations for Permisology System...');
        Artisan::call('migrate');
        $this->info(Artisan::output());

        // 13. Añadir campo "active" a la tabla users si es necesario
        $this->info('Adding "active" field to users table if needed...');
        $this->addActiveFieldIfNeeded();

        // 14. Configurar roles y permisos
        $this->info('Setting up roles and permissions...');
        $this->setupRolesAndPermissions();

        // 15. Actualizar el modelo en auth.php
        $this->info('Updating auth.php configuration...');
        $this->updateAuthConfig();

        // 16. Copiar archivos de Filament
        $this->info('Copying Filament resources...');
        $this->copyFilamentResources();

        // 17. Actualizar AdminPanelProvider
        $this->info('Updating AdminPanelProvider...');
        $this->updateAdminPanelProvider();

        // 18. Publicar vista
        Artisan::call('vendor:publish', ['--tag' => 'permisology-system-views']);
        $this->info('The view access-firewall-settings has been published successfully.');

        // 19. Limpiar y optimizar la aplicación
        $this->info('Performing final cleanup and optimization...');
        $this->cleanAndOptimize();

        $this->info('Permisology System installation completed successfully!');
        return Command::SUCCESS;
    }

    private function checkDatabaseConnection()
    {
        try {
            DB::connection()->getPdo();
            $this->info('Database connection verified successfully.');
            return true;
        } catch (\Exception $e) {
            $this->error('Database connection failed: ' . $e->getMessage());
            return false;
        }
    }

    private function isPackageInstalled($package)
    {
        $composerFile = base_path('composer.json');
        if (file_exists($composerFile)) {
            $composerContent = json_decode(file_get_contents($composerFile), true);
            return isset($composerContent['require'][$package]) || isset($composerContent['require-dev'][$package]);
        }
        return false;
    }

    private function installFilamentPanles()
    {
        $process = new Process([
            'php',
            'artisan',
            'filament:install',
            '--panels',
        ]);

        // Configurar el proceso para responder automáticamente
        $process->setInput("admin\nno\n"); // Responde "admin" al ID y "no" al GitHub star
        //$process->setTimeout(null); // Evita tiempos de espera que terminen el proceso

        // Ejecutar el proceso
        $process->run(function ($type, $buffer) {
            echo $buffer;
        });

        // Verificar si se ejecutó correctamente
        if ($process->isSuccessful()) {
            $this->info('Filament panels have been set up successfully!');
        } else {
            $this->error('There was an issue setting up Filament panels.');
        }
    }

    private function updateAdminPanelProvider()
    {
        $filePath = app_path('Providers/Filament/AdminPanelProvider.php');

        // Verificar si el archivo existe
        if (!file_exists($filePath)) {
            $this->error('AdminPanelProvider.php file not found.');
            return;
        }

        // Leer el contenido del archivo
        $fileContents = file_get_contents($filePath);

        // Modificar el contenido
        $updatedContents = str_replace(
            [
                "->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\\\Filament\\\\Admin\\\\Resources')",
                "->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\\\Filament\\\\Admin\\\\Pages')",
                "->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\\\Filament\\\\Admin\\\\Widgets')",
            ],
            [
                "->discoverResources(in: app_path('Filament/Resources'), for: 'App\\\\Filament\\\\Resources')",
                "->discoverPages(in: app_path('Filament/Pages'), for: 'App\\\\Filament\\\\Pages')",
                "->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\\\Filament\\\\Widgets')",
            ],
            $fileContents
        );

        // Agregar DynamicPermissionCheck al middleware
        $updatedContents = str_replace(
            "])
            ->authMiddleware([",
            "\\PermisologySystem\\PermisologySystem\\Http\\Middleware\\DynamicPermissionCheck::class,\n])
            ->authMiddleware([",
            $updatedContents
        );

        // Agregar cambios en el panel
        // $updatedContents = str_replace(
        //     "->path('admin')",
        //     "->path('admin')\n            ->login()",
        //     $updatedContents
        // );

        // Guardar el archivo modificado
        file_put_contents($filePath, $updatedContents);

        $this->info('AdminPanelProvider.php has been updated successfully.');
    }


    private function addActiveFieldIfNeeded()
    {
        if (!Schema::hasColumn('users', 'active')) {
            Schema::table('users', function ($table) {
                $table->boolean('active')->default(true)->after('password');
            });
            $this->info('Added "active" column to "users" table with default value as true.');
        } else {
            $this->info('The "active" column already exists in the "users" table.');
        }
    }

    private function setupRolesAndPermissions()
    {
        $adminRoute = config('filament.path', 'admin');
        $permission = Permission::firstOrCreate([
            'name' => 'Super Admin Permissions',
            'guard_name' => 'web',
            'manual_routes' => ["$adminRoute/*", '*'],
        ]);

        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdminRole->givePermissionTo($permission);

        $this->assignSuperAdminRole($superAdminRole);
    }

    private function assignSuperAdminRole(Role $superAdminRole)
    {
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
                if (!$user) {
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

    private function registerFilamentMiddleware()
    {
        $configPath = config_path('filament.php');

        // Verificar si el archivo de configuración de Filament existe
        if (!file_exists($configPath)) {
            $this->error('Filament config file not found. Middleware could not be registered.');
            return;
        }

        // Agregar el middleware al grupo base de Filament
        $config = include $configPath;
        $middleware = \PermisologySystem\PermisologySystem\Http\Middleware\DynamicPermissionCheck::class;

        if (!in_array($middleware, $config['middleware']['base'] ?? [])) {
            $config['middleware']['base'][] = $middleware;

            // Guardar el archivo modificado
            file_put_contents($configPath, "<?php\n\nreturn " . var_export($config, true) . ';');
            $this->info("Middleware {$middleware} has been registered in Filament.");
        } else {
            $this->info("Middleware {$middleware} is already registered in Filament.");
        }
    }

    private function updateAuthConfig()
    {
        $authConfigPath = config_path('auth.php');

        if (!file_exists($authConfigPath)) {
            $this->error('The auth.php configuration file was not found.');
            return;
        }

        $fileContents = file_get_contents($authConfigPath);

        // Buscar el modelo predeterminado y reemplazarlo
        $updatedContents = preg_replace(
            "/('model' => )(.*?),/",
            "'model' => \\PermisologySystem\\PermisologySystem\\Models\\PermisologySystem\\UserPermisologySystem::class,",
            $fileContents
        );

        if ($updatedContents === null) {
            $this->error('Failed to update the auth.php configuration file.');
            return;
        }

        // Guardar los cambios en el archivo
        file_put_contents($authConfigPath, $updatedContents);
        $this->info('The auth.php configuration file has been updated successfully.');
    }

    private function copyFilamentResources()
    {
        $sourcePath = base_path('vendor/dualink/permisology-system/src/Filament');
        $destinationPath = app_path('Filament');

        if (!file_exists($sourcePath)) {
            $this->error("Source path '{$sourcePath}' does not exist. Unable to copy Filament resources.");
            return;
        }

        // Crear el directorio destino si no existe
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        // Procesar archivos .stub y copiarlos como .php
        $this->processStubFiles($sourcePath, $destinationPath);

        $this->info('Filament resources have been copied and converted to .php successfully in app/Filament.');
    }

    private function processStubFiles($source, $destination)
    {
        $directory = opendir($source);
        @mkdir($destination);

        while (($file = readdir($directory)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $sourceFile = $source . DIRECTORY_SEPARATOR . $file;
            $destinationFile = $destination . DIRECTORY_SEPARATOR . str_replace('.php.stub', '.php', $file);

            if (is_dir($sourceFile)) {
                // Procesar subdirectorios recursivamente
                $this->processStubFiles($sourceFile, $destinationFile);
            } else {
                if (pathinfo($sourceFile, PATHINFO_EXTENSION) === 'stub') {
                    // Leer el contenido del archivo .stub
                    $stubContent = file_get_contents($sourceFile);

                    // Reemplazar placeholders dinámicos si es necesario
                    $phpContent = str_replace(
                        ['{{ namespace }}'],
                        ['App\\Filament\\Resources'],
                        $stubContent
                    );

                    // Guardar como .php
                    file_put_contents($destinationFile, $phpContent);
                }
            }
        }

        closedir($directory);
    }


    private function cleanAndOptimize()
    {
        $this->info('Clearing and optimizing the application...');

        try {

            // Reconstruir autoload de Composer
            shell_exec('composer dump-autoload');
            shell_exec('composer install');
            $this->info('Composer dump-autoload executed successfully.');
        } catch (\Exception $e) {
            $this->error('An error occurred during cleaning and optimization: ' . $e->getMessage());
        }
    }
}
