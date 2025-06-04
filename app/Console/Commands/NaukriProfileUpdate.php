<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class NaukriProfileUpdate extends Command
{
    protected $signature = 'naukri:profile-update';
    protected $description = 'Update Naukri profile with latest headline.';

    public function handle()
    {
        // Storage::put('naukri_bot_pid.txt', getmypid());

        $this->info('Starting Naukri profile update process...');

        try {
            $scriptPath = base_path('automation/naukriProfileBot.js');

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
                $this->info('Naukri profile update process completed successfully.');
                return Command::SUCCESS;
            } else {
                $this->error('Naukri profile update error: '.$process->getErrorOutput());
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('Error while updating profile: '.$e->getMessage());
            return Command::FAILURE;
        }
    }
}
