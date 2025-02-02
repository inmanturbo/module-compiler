<?php

namespace Inmanturbo\ModuleCompiler\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

use function Illuminate\Filesystem\join_paths;

class CombineCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Usage:
     *   php artisan combine path/to/File1.php path/to/File2.php --module=module_name.php
     *
     * @var string
     */
    protected $signature = 'combine {files* : Path to one or more files to combine, relative to build-path}
                                        {--name= : Match file names with the given pattern}
                                        {--module-path=modules : The name of the build modules directory}
                                        {--build-path= : The base path to find the files under (leave empty for `base_path()`}
                                        {--realpath : Indicates indicates provided paths will be absolute}
                                        {--module=app.php : The name of the build module file}
                                        {--preserve-path : Use the file path as the build module}
                                        {--namespace : insert namespaces between fileblocks}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Combine one or more PHP files into a single module file';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $moduleName = $this->option('module');
        $modulePath = $this->option('module-path');
        $buildPath = $this->option('build-path') ?: base_path();

        if ($this->option('realpath')) {
            $buildPath = realpath($buildPath);
            $modulePath = realpath($modulePath);
        }

        if (! File::exists($modulePath)) {
            File::makeDirectory($modulePath, 0755, true);
            $this->info("Created modules directory at: {$modulePath}");
        }

        $buildModule = join_paths($modulePath, $moduleName);

        if (! $this->option('preserve-path')) {
            if (! File::exists($buildModule)) {
                File::put($buildModule, '<?php'.PHP_EOL);
                $this->info("Created build module: {$moduleName}");
            } else {
                $this->info("Appending to existing build module: {$moduleName}");
            }
        }

        foreach ($this->argument('files') as $filePath) {
            $fullPath = join_paths($buildPath, $filePath);

            if (! File::isDirectory($fullPath)) {

                if ($this->option('preserve-path')) {
                    $buildModule = join_paths($this->option('module-path'), $filePath);
                }

                if (! File::exists($directory = dirname($buildModule))) {
                    File::makeDirectory($directory, 0755, true);
                    $this->info("Created directory: {$directory}");
                }

                if ($this->option('preserve-path')) {

                    if (! File::exists($buildModule)) {
                        $fullExtension = implode('.', array_slice(explode('.', basename($buildModule)), 1));

                        $shebang = match ($fullExtension) {
                            'php' => '<?php'.PHP_EOL.PHP_EOL,
                            'sh' => '#!/bin/bash'.PHP_EOL.PHP_EOL,
                            default => '',
                        };

                        File::put($buildModule, $shebang);
                        $this->info("Created build module: {$buildModule}");
                    } else {
                        $this->info("Appending to existing build module: {$buildModule}");
                    }
                }

                $this->extractFile($buildPath, $filePath, $buildModule);

                continue;
            }

            $files = (new Finder)->in($fullPath)->name($this->option('name') ? $this->option('name') : '*.php')->files();

            foreach ($files as $file) {
                $filePath = str_replace($buildPath, '', $file->getPathname());

                $filePath = ltrim($filePath, DIRECTORY_SEPARATOR);

                if ($this->option('preserve-path')) {
                    $buildModule = join_paths($this->option('module-path'), $filePath);
                }

                if (! File::exists($directory = dirname($buildModule))) {
                    File::makeDirectory($directory, 0755, true);
                    $this->info("Created directory: {$directory}");
                }

                if ($this->option('preserve-path')) {

                    if (! File::exists($buildModule)) {
                        $fullExtension = implode('.', array_slice(explode('.', basename($buildModule)), 1));

                        $shebang = match ($fullExtension) {
                            'php' => '<?php'.PHP_EOL.PHP_EOL,
                            'sh' => '#!/bin/bash'.PHP_EOL.PHP_EOL,
                            default => '',
                        };

                        File::put($buildModule, $shebang);
                        $this->info("Created build module: {$buildModule}");
                    } else {
                        $this->info("Appending to existing build module: {$buildModule}");
                    }
                }

                $this->extractFile($buildPath, $filePath, $buildModule);
            }
        }

        $this->info("All specified files have been combined into {$buildModule}.");

        return 0;
    }

    protected function extractFile(string $buildPath, string $filePath, string $buildModule)
    {
        $fullPath = join_paths($buildPath, $filePath);

        if (! File::exists($fullPath)) {
            $this->error("File does not exist: {$filePath}");

            return;
        }

        if (! File::isReadable($fullPath)) {
            $this->error("File is not readable: {$filePath}");

            return;
        }

        $content = File::get($fullPath);

        $content = preg_replace('/<\?php\s*/', '', $content, 1);
        $content = trim((string) $content);

        $__php_eol = PHP_EOL;

        $fullExtension = implode('.', array_slice(explode('.', basename($buildModule)), 1));

        $namespace = match ($fullExtension) {
            'php' => 'namespace i'.str()->of(str()->ulid())->lower()->__toString().';',
            default => '',
        };

        if ($this->option('namespace')) {
            $block = "{$__php_eol}{$namespace}{$__php_eol}{$__php_eol}// BEGIN_FILE: ({$filePath}){$__php_eol}{$content}{$__php_eol}// END_FILE{$__php_eol}";
        } else {
            $block = "{$__php_eol}// BEGIN_FILE: ({$filePath}){$__php_eol}{$content}{$__php_eol}// END_FILE{$__php_eol}";
        }

        File::append($buildModule, $block);

        event('combined.file', [$buildModule]);

        $this->info("Appended file: {$filePath} to module: {$buildModule}");
    }
}
