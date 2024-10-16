<?php

namespace Inmanturbo\ModuleCompiler\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class AppendCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'append {make-command} {--module=app.php} {--module-path=modules} {--keep}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'example usage: php artisan get-generated-file-path "make:model Team"';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Artisan::call($this->argument('make-command'));

        $output = Artisan::output();

        if (preg_match_all('/\[(.*\.php)\]/', $output, $matches)) {

            $this->info($output);

            $this->call('combine', [
                'files' => $matches[1],
                '--module' => $this->option('module'),
                '--module-path' => $this->option('module-path'),
            ]);

            if($this->option('keep')) {
                return 0;
            }

            foreach($matches[1] as $stub) {
                unlink($stub);
            }

            return 0;
        }
        
        $this->error($output);
        return 1;
    }
}
