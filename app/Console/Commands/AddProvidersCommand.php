<?php

namespace Inmanturbo\ModuleCompiler\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AddProvidersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'providers:add {--paths=* --realpath=false}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add all providers from app/Providers/ to bootstrap/providers.php';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if (!count($this->option('paths')) {
            $this->writeProviders();
            return;
        }

        if ($this->option('realpath')) {
            foreach ($this->$this->option('paths') as $path) {
                if (!$path = realpath($path)) {
                    continue;
                }
                $this->writeProviders($path);
            }

            return;
        }
       
        foreach ($this->$this->option('paths') as $path) {
            $path = base_path($path);
            $this->writeProviders($path);
        }
    }

    protected function writeProviders($providersPath = null, $bootstrapProvidersFile = null)
    {
        $providersPath ??= app_path('Providers');
        $bootstrapProvidersFile ??= base_path('bootstrap/providers.php');

        if (! File::exists($providersPath)) {
            $this->error("The Providers directory does not exist at {$providersPath}");

            return;
        }

        if (! File::exists($bootstrapProvidersFile)) {
            $this->error('The bootstrap/providers.php file does not exist.');

            return;
        }

        $providers = collect(File::files($providersPath))
            ->filter(fn ($file): bool => $file->getExtension() === 'php')
            ->map(fn ($file): string => 'App\\Providers\\'.$file->getFilenameWithoutExtension())
            ->values()
            ->toArray();

        $providersArrayContent = "return [\n";
        foreach ($providers as $provider) {
            $providersArrayContent .= "    {$provider}::class,\n";
        }
        $providersArrayContent .= "];\n";

        File::put($bootstrapProvidersFile, "<?php\n\n".$providersArrayContent);

        $this->info("Providers have been successfully added to {$bootstrapProvidersFile}.");
    }
}
