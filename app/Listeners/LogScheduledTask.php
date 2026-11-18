<?php

namespace App\Listeners;

use App\Models\JobLog;
use Illuminate\Console\Application as ConsoleApplication;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Registra in job_logs le esecuzioni dello scheduler (php artisan schedule:run).
 *
 * Senza questo listener la vista system/jobs/cron mostrava solo le esecuzioni
 * avviate manualmente dalla UI (JobAdminController::runCronCommand), perché
 * erano le uniche a creare record job_logs con job_type = 'cron'.
 */
class LogScheduledTask
{
    /**
     * Byte massimi letti dalla coda del log file del task.
     */
    private const OUTPUT_TAIL_BYTES = 50 * 1024;

    /**
     * JobLog "running" indicizzati per istanza del task schedulato.
     *
     * @var array<int, array{log_id: int, output_path: string, output_offset: int}>
     */
    private static array $running = [];

    public function handle(ScheduledTaskStarting|ScheduledTaskFinished|ScheduledTaskFailed $event): void
    {
        try {
            if ($event instanceof ScheduledTaskStarting) {
                $this->onStarting($event);
            } elseif ($event instanceof ScheduledTaskFinished) {
                $this->onFinished($event);
            } else {
                $this->onFailed($event);
            }
        } catch (Throwable $e) {
            // Il logging non deve mai bloccare l'esecuzione del task schedulato
            Log::warning('LogScheduledTask: impossibile registrare la run schedulata', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function onStarting(ScheduledTaskStarting $event): void
    {
        $jobName = $this->commandName($event->task);

        if ($jobName === null) {
            // Task non-artisan (exec() shell, call() callback): non presenti in vista cron
            return;
        }

        $outputPath = $this->outputPath($event->task);

        $log = JobLog::create([
            'job_name' => $jobName,
            'job_type' => 'cron',
            'status' => 'running',
            'started_at' => now(),
            'payload' => [
                'scheduled_run' => true,
                'expression' => $event->task->expression,
                'timezone' => $event->task->timezone instanceof \DateTimeZone
                    ? $event->task->timezone->getName()
                    : $event->task->timezone,
            ],
        ]);

        self::$running[spl_object_id($event->task)] = [
            'log_id' => $log->id,
            'output_path' => $outputPath,
            'output_offset' => is_file($outputPath) ? (int) filesize($outputPath) : 0,
        ];
    }

    private function onFinished(ScheduledTaskFinished $event): void
    {
        [$log, $meta] = $this->resolve($event->task);

        if (! $log) {
            return;
        }

        $exitCode = $event->task->exitCode;

        // exitCode null: task saltato (es. withoutOverlapping) o eseguito in background
        $status = $exitCode === null
            ? 'skipped'
            : ($exitCode === 0 ? 'success' : 'failed');

        $log->update([
            'status' => $status,
            'finished_at' => now(),
            'duration' => $event->runtime,
            'output' => $this->readNewOutput($event->task, $meta),
            'error_message' => match ($status) {
                'failed' => "Command failed with exit code: {$exitCode}",
                'skipped' => 'Skipped (overlapping run prevented or background task)',
                default => null,
            },
        ]);
    }

    private function onFailed(ScheduledTaskFailed $event): void
    {
        [$log, $meta] = $this->resolve($event->task);

        if (! $log) {
            return;
        }

        $log->update([
            'status' => 'failed',
            'finished_at' => now(),
            'output' => $this->readNewOutput($event->task, $meta),
            'error_message' => $event->exception->getMessage(),
        ]);
    }

    /**
     * Recupera il JobLog "running" associato al task e lo rimuove dalla mappa.
     *
     * @return array{0: JobLog|null, 1: array|null}
     */
    private function resolve(Event $task): array
    {
        $key = spl_object_id($task);
        $meta = self::$running[$key] ?? null;
        unset(self::$running[$key]);

        if ($meta) {
            return [JobLog::find($meta['log_id']), $meta];
        }

        // Fallback: ultimo log 'running' con lo stesso nome (es. listener
        // registrato dopo il dispatch di Starting)
        $jobName = $this->commandName($task);

        $log = $jobName === null
            ? null
            : JobLog::cronJobs()->running()->where('job_name', $jobName)->latest()->first();

        return [$log, null];
    }

    /**
     * Estrae il nome comando (es. "app:commesse" o "app:foo --bar") da
     * task->command, che ha formato "'/path/php' 'artisan' app:foo --bar".
     * Deve coincidere con il nome parsato da Kernel.php in cronJobs().
     */
    private function commandName(Event $task): ?string
    {
        $command = (string) ($task->command ?? '');

        $prefix = ConsoleApplication::phpBinary().' '.ConsoleApplication::artisanBinary().' ';

        if (! str_starts_with($command, $prefix)) {
            return null;
        }

        return trim(substr($command, strlen($prefix)));
    }

    /**
     * Replica Event::ensureOutputIsBeingCaptured(): l'output di default viene
     * accodato in storage/logs/schedule-{sha1(mutex)}.log
     */
    private function outputPath(Event $task): string
    {
        $output = $task->output ?? null;

        if (is_string($output) && ! in_array($output, ['/dev/null', 'NUL'], true)) {
            return $output;
        }

        return storage_path('logs/schedule-'.sha1($task->mutexName()).'.log');
    }

    /**
     * Legge solo i byte accodati al log file durante questa esecuzione.
     */
    private function readNewOutput(Event $task, ?array $meta): ?string
    {
        $path = $meta['output_path'] ?? null;
        $offset = $meta['output_offset'] ?? 0;

        // Il path reale può differire da quello previsto se il task usa sendOutputTo()
        $actual = $task->output ?? null;
        if (is_string($actual) && ! in_array($actual, ['/dev/null', 'NUL'], true) && $actual !== $path) {
            $path = $actual;
            $offset = 0;
        }

        if (! $path || ! is_file($path)) {
            return null;
        }

        $size = (int) filesize($path);
        $offset = min($offset, $size);

        // Senza offset affidabile, leggi solo la coda del file
        if ($size - $offset > self::OUTPUT_TAIL_BYTES) {
            $offset = $size - self::OUTPUT_TAIL_BYTES;
        }

        $handle = fopen($path, 'rb');
        if (! $handle) {
            return null;
        }

        fseek($handle, $offset);
        $chunk = stream_get_contents($handle) ?: '';
        fclose($handle);

        $chunk = trim($chunk);

        return $chunk === '' ? null : $chunk;
    }
}
