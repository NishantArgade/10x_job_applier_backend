<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class NaukriApplyJobs extends Command
{
    protected $signature = 'naukri:bot';
    protected $description = 'Apply for recommended jobs on Naukri';

    public function handle()
    {
        $this->info('Starting Naukri job application process...');
        
        try {
            $scriptPath = base_path('automation/naukriBot.js');
            
            $process = new Process(['node', $scriptPath]);
            $process->setTimeout(3600); 
            
            $process->run(function ($type, $buffer) {
                if (Process::ERR === $type) {
                    $this->error($buffer);
                } else {
                    $this->line($buffer);
                }
            });

            if ($process->isSuccessful()) {
                $this->info('Job application process completed successfully.');
                return Command::SUCCESS;
            } else {
                $this->error('Job application process failed: ' . $process->getErrorOutput());
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('Error applying for jobs: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
