<?php

namespace Inmanturbo\ModuleCompiler\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Spatie\Watcher\Watch;

use function Illuminate\Filesystem\join_paths;
use function Laravel\Prompts\note;

class WatchCommand extends Command
{
    public $processedFiles = [];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'watch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Event::listen('built.file', function ($relativePath): void {
            $this->processedFiles[$relativePath] = $relativePath;
        });
        Event::listen('combined.file', function ($modulePath): void {
            $this->processedFiles[$modulePath] = $modulePath;
        });

        Watch::path(join_paths(base_path()))
            ->onAnyChange(function (string $type, string $path): void {

                if ($type !== Watch::EVENT_TYPE_FILE_CREATED && $type !== Watch::EVENT_TYPE_FILE_UPDATED) {
                    return;
                }

                $path = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);

                if (in_array($path, $this->processedFiles)) {
                    note('Skipping '.$path.' to avoid infinite loop!');
                    unset($this->processedFiles[$path]);

                    return;
                }

                if (str_starts_with($path, 'modules')) {
                    $this->handleModuleModified($path);

                    return;
                }

                if (Str::contains($path, [
                    'vendor',
                    'storage',
                    '.env',
                    'cache',
                    'modules',
                    '.git'.DIRECTORY_SEPARATOR,
                ])) {
                    return;
                }
                $this->handleFileModified($path);
            })
            ->start();
    }

    protected function handleFileModified($path)
    {
        $this->call('combine', [
            'files' => [$path],
            '--preserve-path' => true,
            '--namespace' => true,
        ]);
    }

    protected function handleModuleModified($path)
    {
        $this->call('build');
    }
}
