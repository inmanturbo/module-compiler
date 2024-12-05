<?php

namespace Inmanturbo\ModuleCompiler\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

use function Illuminate\Filesystem\join_paths;

class BuildCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'build {module?* : File containing code that should be split out into one or more files} 
                                    {--module-path=modules : Directory under which modules will be found} 
                                    {--build-path= : Leave empty for `base_path()`} 
                                    {--realpath : Use realpath for module and build path(s)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compile modules into their respective files';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $modulePath = $this->option('module-path');

        $buildPath = $this->option('build-path') ?: base_path();

        if ($this->option('realpath')) {
            $modulePath = realpath($modulePath);
            $buildPath = realpath($buildPath);
        }

        $finder = new Finder;
        $finder->files()->in($modulePath);

        foreach ($this->argument('module') as $module) {
            $finder->name($module);
        }

        if (! $finder->hasResults()) {
            $this->warn('No matching build modules found in module-path.');

            return;
        }

        foreach ($finder as $file) {
            $content = $file->getContents();

            // Regular expression to match blocks between // BEGIN_FILE and // END_FILE
            // Captures the file path and the content within the block
            preg_match_all('/\/\/\s*BEGIN_FILE:\s*\((.*?)\)\s*(.*?)\/\/\s*END_FILE/s', $content, $matches, PREG_SET_ORDER);

            if ($matches === []) {
                $this->warn("No file blocks found in module: {$file->getRelativePathname()}");

                continue;
            }

            $this->info("Building module: {$file->getRelativePathname()}");

            foreach ($matches as $match) {
                $relativePath = trim($match[1]);
                $fileContent = trim($match[2]);

                $fullPath = join_paths($buildPath, $relativePath);

                if ($fileContent === '// DELETE_FILE') {
                    if (File::exists($fullPath)) {
                        File::delete($fullPath);
                    }

                    continue;
                }

                $fullExtension = implode('.', array_slice(explode('.', basename($fullPath)), 1));

                $shebang = match ($fullExtension) {
                    'php' => '<?php'.PHP_EOL.PHP_EOL,
                    'sh' => '#!/bin/bash'.PHP_EOL.PHP_EOL,
                    default => '',
                };

                $fileContent = $shebang.$fileContent.PHP_EOL;

                $directory = dirname($fullPath);
                if (! File::exists($directory)) {
                    File::makeDirectory($directory, 0755, true);
                    $this->info("Created directory: {$directory}");
                }

                try {
                    File::put($fullPath, $fileContent);
                    $this->info("Built file: {$relativePath}");
                } catch (\Exception $e) {
                    $this->error("Failed to write file: {$relativePath}. Error: {$e->getMessage()}");
                }
            }
        }

        $this->info('All modules have been built successfully.');
    }
}
