<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

#[Signature('project:status')]
#[Description('Verify the backend database and display content module totals')]
class ProjectStatus extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            DB::connection()->getPdo();
            $rows = [];
            $missing = [];

            foreach (config('content.modules') as $label => $modelClass) {
                $model = new $modelClass;
                $table = $model->getTable();

                if (! Schema::hasTable($table)) {
                    $missing[] = $table;

                    continue;
                }

                $rows[] = [$label, $table, $modelClass::count()];
            }

            if ($missing !== []) {
                $this->error('Missing tables: '.implode(', ', $missing).'. Run php artisan migrate.');

                return self::FAILURE;
            }

            $this->info('Backend database is ready ('.config('database.default').').');
            $this->table(['Module', 'Table', 'Records'], $rows);
            $this->line('Phase 6 public website and review submissions at /; administrator moderation at /admin/reviews.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Database check failed. Check your local .env connection settings and run migrations.');

            return self::FAILURE;
        }
    }
}
