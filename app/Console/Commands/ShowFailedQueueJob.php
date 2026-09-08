<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShowFailedQueueJob extends Command
{
    protected $signature = 'queue:failed:show {id? : Failed job id or UUID}';

    protected $description = 'Compatibility helper to show failed queue job details.';

    public function handle(): int
    {
        if (!DB::getSchemaBuilder()->hasTable('failed_jobs')) {
            $this->warn('No failed_jobs table exists.');

            return self::SUCCESS;
        }

        $id = $this->argument('id');

        if (!$id) {
            $this->call('queue:failed');

            return self::SUCCESS;
        }

        $job = DB::table('failed_jobs')
            ->when(Str::isUuid($id), fn ($query) => $query->where('uuid', $id), fn ($query) => $query->where('id', $id))
            ->first();

        if (!$job) {
            $this->warn('Failed job not found.');

            return self::SUCCESS;
        }

        $this->line('ID: ' . $job->id);
        $this->line('UUID: ' . ($job->uuid ?? 'n/a'));
        $this->line('Connection: ' . ($job->connection ?? 'n/a'));
        $this->line('Queue: ' . ($job->queue ?? 'n/a'));
        $this->line('Failed At: ' . ($job->failed_at ?? $job->created_at ?? 'n/a'));
        $this->newLine();
        $this->line((string) ($job->exception ?? ''));

        return self::SUCCESS;
    }
}
