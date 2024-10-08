<?php

namespace Inmanturbo\ModuleCompiler\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

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

        $modulePath = join_paths($modulePath, $moduleName);

        if (!File::exists($modulePath)) {
            File::put($modulePath, "<?php\n");
            $this->info("Created build module: {$moduleName}");
        } else {
            $this->info("Appending to existing build module: {$moduleName}");
        }

        foreach ($this->argument('files') as $filePath) {
            $fullPath = join_paths($buildPath, $filePath);

            if (!File::exists($fullPath)) {
                $this->error("File does not exist: {$filePath}");
                continue;
            }

            if (!File::isReadable($fullPath)) {
                $this->error("File is not readable: {$filePath}");
                continue;
            }

            $content = File::get($fullPath);

            $content = preg_replace('/<\?php\s*/', '', $content);
            $content = trim($content);

            $block = "\n// BEGIN_FILE: ({$filePath})\n\n{$content}\n// END_FILE\n";

            File::append($modulePath, $block);

            $this->info("Appended file: {$filePath} to module: {$moduleName}");
        }

        $this->info("All specified files have been combined into {$moduleName}.");

        return 0; // Exit with success code
    }
}
