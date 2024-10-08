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
    protected $signature = 'app:build {--module=*} {--module-path=modules} {--build-path= : leave empty for `base_path()`} {--realpath}';

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
        $modulePath = $this->option('realpath') ? realpath($this->option('module-path')) : base_path($this->option('module-path'));

        $fromBase = $this->option('build-path') ? join_paths(base_path(), $this->option('build-path')) : base_path();

        $buildPath = $this->option('realpath') ? realpath($this->option('build-path') ?: '.') : $fromBase;

        $finder = new Finder;
        $finder->files()->in($modulePath);

        foreach ($this->option('module') as $module) {
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
