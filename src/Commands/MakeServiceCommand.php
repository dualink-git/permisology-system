<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MakeServiceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:service {path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Service';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pathInput = $this->argument('path');
        $path = str_replace('/', '\\', $pathInput);
        $parts = explode('\\', $path);
        $name = array_pop($parts);
        $namespace = 'App\\Services' . (empty($parts) ? '' : '\\' . implode('\\', $parts));

        $template = $this->getTemplate($name, $namespace);

        $path = app_path("/Services/{$name}.php");

        $directoryPath = app_path("Services/" . implode('/', $parts));
        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true); // Asegúrate de que existe el directorio
        }

        $filePath = $directoryPath . "/{$name}.php";
        if (file_exists($filePath)) {
            $this->error("The service {$name} already exists.");
            return;
        }

        file_put_contents($filePath, $template);

        $this->info("Service {$name} created successfully.");
    }

    protected function getTemplate($name, $namespace)
    {
        return <<<EOT
        <?php

        namespace {$namespace};

        use App\Services\BaseService;

        class {$name} extends BaseService
        {

        }
        EOT;
    }
}
