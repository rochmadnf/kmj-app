<?php

namespace App\Console\Commands\Ckg;

use App\Models\Ckg\CheckUpResult;
use App\Models\Ckg\ListCheckUp;
use Illuminate\Console\Command;

class SetListResultCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ckg:set-cl';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set Check List from Results Checkup';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cur = CheckUpResult::with('screening', 'screening.patient')
            ->get();


        $cur->each(function ($c) {
            foreach ($c->results as $result) {
                if (is_null($result)) {
                    $this->info('Skip.');
                } else {
                    foreach ($result['list_parameter'] as $list) {
                        $this->info("{$list['label']} - {$c->screening->school_category}");

                        if (ListCheckUp::where('code', $list['code'])->where('school_category', $c->screening->school_category)->exists()) {
                            $this->warn('⚠️ Pemeriksaan Telah Tersedia.');
                        } else {
                            ListCheckUp::create([
                                'group_name' => $result['pemeriksaan_label'],
                                'group_code' => $result['pemeriksaan_code'],
                                'label' => $list['label'],
                                'code' => $list['code'],
                                'school_category' => $c->screening->school_category,
                            ]);
                            $this->info('✅ Pemeriksaan Berhasil Disimpan.');
                        }
                    }
                }
                $this->newLine(2);
            }
        });
    }
}
