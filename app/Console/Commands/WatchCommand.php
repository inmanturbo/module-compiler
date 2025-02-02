<?php

namespace Inmanturbo\ModuleCompiler\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Spatie\Watcher\Watch;

use function Illuminate\Filesystem\join_paths;
use function Laravel\Prompts\info;

class WatchCommand extends Command
{

    public $builtFiles = [];
    public $combinedFiles = [];

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
    public function handle()
    {
        Event::listen('built.file', function ($relativePath) {
            $this->builtFiles[$relativePath] = $relativePath;
        });
        Event::listen('combined.file', function ($modulePath) {
            dump($modulePath);
            $this->combinedFiles[$modulePath] = $modulePath;
        });

        Watch::path(join_paths(base_path()))
            ->onAnyChange(function (string $type, string $path) {

                $path = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);


                if (str_starts_with($path, 'modules')) {

                    if (in_array($path, $this->combinedFiles)) {
                        info('Skipping '.$path);
                        unset($this->combinedFiles[$path]);
                        return;
                    }    

                    if ($type === Watch::EVENT_TYPE_FILE_CREATED || $type === Watch::EVENT_TYPE_FILE_UPDATED) {
                        $this->handleModuleModified($path);
                    }

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

                if (in_array($path, $this->builtFiles)) {
                    info('Skipping '.$path);
                    unset($this->builtFiles[$path]);
                    return;
                }

                if ($type === Watch::EVENT_TYPE_FILE_CREATED || $type === Watch::EVENT_TYPE_FILE_UPDATED) {
                    $this->handleFileModified($path);
                }
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
