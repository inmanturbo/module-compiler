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
     *   php artisan app:combine path/to/File1.php path/to/File2.php --module=moduleName.php
     *
     * @var string
     */
    protected $signature = 'app:combine {files* : Path to or more PHP files to combine, relative to build-path}
                                        {--module-path=modules : The name of the build modules directory}
                                        {--build-path= : The base path to find the files under (leave empty for `base_path()`}
                                        {--realpath : indicates the given module directory is absolute}
                                        {--module=app.php : The name of the build module file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Combine one or more PHP files into a single module file under modules/';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $moduleName = $this->option('module');
        $modulePath = $this->option('realpath') ? realpath($this->option('module-path')) : base_path($this->option('module-path'));
        $buildPath = $this->option('build-path') ? $this->option('build-path') : base_path();

        if (!File::exists($modulePath)) {
            File::makeDirectory($modulePath, 0755, true);
            $this->info("Created modules directory at: {$modulePath}");
        }

        $buildModule = join_paths($modulePath, $moduleName);

        if (!File::exists($buildModule)) {
            File::put($buildModule, "<?php\n");
            $this->info("Created build module: {$moduleName}");
        } else {
            $this->info("Appending to existing build module: {$moduleName}");
        }

        foreach ($this->argument('files') as $filePath) {
            $fullPath = join_paths($buildPath, $filePath);

            if (!File::isDirectory($fullPath)) {
                $this->extractFile($buildPath, $filePath, $buildModule);
                continue;
            }

            $files = (new Finder)->in($fullPath)->name('*.php')->files();

            foreach ($files as $file) {
                $filePath = str_replace($buildPath, '', $file->getPathname());

                $filePath = ltrim($filePath, DIRECTORY_SEPARATOR);

                $this->extractFile($buildPath, $filePath, $buildModule);
            }
        }

        $this->info("All specified files have been combined into {$buildModule}.");

        return 0;
    }

    protected function extractFile(string $buildPath, string $filePath, string $buildModule)
    {
        $fullPath = join_paths($buildPath, $filePath);

        if (!File::exists($fullPath)) {
            $this->error("File does not exist: {$filePath}");
            return;
        }

        if (!File::isReadable($fullPath)) {
            $this->error("File is not readable: {$filePath}");
            return;
        }

        $content = File::get($fullPath);

        $content = preg_replace('/<\?php\s*/', '', $content);
        $content = trim($content);

        $__php_eol = PHP_EOL;

        $block = "{$__php_eol}// BEGIN_FILE: ({$filePath}){$__php_eol}{$content}{$__php_eol}// END_FILE{$__php_eol}";

        File::append($buildModule, $block);

        $this->info("Appended file: {$filePath} to module: {$buildModule}");
    }
}
