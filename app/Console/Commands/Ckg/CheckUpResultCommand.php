<?php

namespace App\Console\Commands\Ckg;

use Illuminate\Console\Command;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class CheckUpResultCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ckg:get-cur';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get Checkup Result from CKG Sekolah Sehat Indonesiaku';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $screenings = \App\Models\Screening::with('patient')->get();

        $screenings->each(function ($screening) {

            $process = new Process(['node', base_path('\node-app\ckg\get-report.js'), '--token_report=' . $screening->token_report]);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $result = $process->getOutput();
            dump(json_decode($result, true)['data']);   

            $this->info("➡️ " . $screening->patient->full_name);

            $this->newLine(1);
            die;
        });
    }
}
