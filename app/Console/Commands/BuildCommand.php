<?php

namespace Inmanturbo\ModuleCompiler\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

class BuildCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:build';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compile modules into their respective files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $finder = new Finder();
        $finder->files()->in(base_path('modules'));

        if (!$finder->hasResults()) {
            $this->warn('No module files found in the modules directory.');
            return;
        }

        foreach ($finder as $file) {
            $content = $file->getContents();

            // Regular expression to match blocks between // BEGIN_FILE and // END_FILE
            // Captures the file path and the content within the block
            preg_match_all('/\/\/\s*BEGIN_FILE:\s*\((.*?)\)\s*(.*?)\/\/\s*END_FILE/s', $content, $matches, PREG_SET_ORDER);

            if (empty($matches)) {
                $this->warn("No file blocks found in module: {$file->getRelativePathname()}");
                continue;
            }

            foreach ($matches as $match) {
                $relativePath = trim($match[1]);
                $fileContent = trim($match[2]);

                $fileContent = "<?php\n\n" . $fileContent;

                $fullPath = base_path($relativePath);

                $directory = dirname($fullPath);
                if (!File::exists($directory)) {
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

        $this->info("All modules have been built successfully.");
    }
}
