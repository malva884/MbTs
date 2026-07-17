<?php

namespace App\Http\Controllers;

use App\Models\JobLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ReflectionClass;

class JobAdminController extends Controller
{
    /**
     * Get dashboard data
     */
    public function dashboard()
    {
        $stats = [
            'total_jobs' => JobLog::count(),
            'running_jobs' => JobLog::running()->count(),
            'failed_jobs' => JobLog::failed()->count(),
            'success_jobs' => JobLog::success()->count(),
            'queue_jobs' => JobLog::queueJobs()->count(),
            'cron_jobs' => JobLog::cronJobs()->count(),
        ];

        $recentJobs = JobLog::latest()->take(10)->get();
        $failedJobs = JobLog::failed()->latest()->take(5)->get();

        return response()->json([
            'stats' => $stats,
            'recent_jobs' => $recentJobs,
            'failed_jobs' => $failedJobs,
        ]);
    }

    /**
     * Get all queue jobs
     */
    public function queueJobs()
    {
        $jobsPath = app_path('Jobs');
        $jobs = [];

        if (File::exists($jobsPath)) {
            $files = File::files($jobsPath);
            foreach ($files as $file) {
                $className = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                $fullClassName = "App\\Jobs\\{$className}";

                if (class_exists($fullClassName)) {
                    $reflection = new ReflectionClass($fullClassName);
                    $jobs[] = [
                        'name' => $className,
                        'full_class' => $fullClassName,
                        'path' => $file->getPathname(),
                        'last_run' => JobLog::where('job_name', $className)
                            ->where('job_type', 'queue')
                            ->latest()
                            ->first(),
                    ];
                }
            }
        }

        return response()->json($jobs);
    }

    /**
     * Get all cron jobs from Kernel.php
     */
    public function cronJobs()
    {
        $kernelPath = app_path('Console/Kernel.php');
        $cronJobs = [];

        if (File::exists($kernelPath)) {
            $content = File::get($kernelPath);
            
            // Parse schedule commands from Kernel.php
            preg_match_all('/\$schedule->command\([\'"]([^\'"]+)[\'"]\)([^;]*)/s', $content, $matches);
            
            if (isset($matches[1]) && isset($matches[2])) {
                foreach ($matches[1] as $index => $command) {
                    $options = $matches[2][$index];
                    
                    // Extract schedule info
                    $scheduleInfo = $this->parseScheduleOptions($options);
                    
                    $cronJobs[] = [
                        'command' => $command,
                        'schedule_info' => $scheduleInfo,
                        'last_run' => JobLog::where('job_name', $command)
                            ->where('job_type', 'cron')
                            ->latest()
                            ->first(),
                    ];
                }
            }
        }

        return response()->json($cronJobs);
    }

    /**
     * Get job logs
     */
    public function jobLogs(Request $request)
    {
        $query = JobLog::query();

        if ($request->has('job_type')) {
            $query->where('job_type', $request->job_type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('job_name')) {
            $query->where('job_name', 'like', '%' . $request->job_name . '%');
        }

        $logs = $query->latest()->get()->toArray();

        return response()->json($logs);
    }

    /**
     * Get job log details
     */
    public function jobLogDetail($id)
    {
        $log = JobLog::findOrFail($id);
        return response()->json($log);
    }

    /**
     * Run a queue job manually
     */
    public function runQueueJob(Request $request)
    {
        $request->validate([
            'job_class' => 'required|string',
        ]);

        $jobClass = $request->job_class;
        
        if (!class_exists($jobClass)) {
            return response()->json(['error' => 'Job class not found'], 404);
        }

        try {
            $jobLog = JobLog::create([
                'job_name' => class_basename($jobClass),
                'job_type' => 'queue',
                'status' => 'running',
                'started_at' => now(),
                'payload' => ['manual_run' => true],
            ]);

            dispatch(new $jobClass());

            $jobLog->update([
                'status' => 'success',
                'finished_at' => now(),
                'output' => 'Job dispatched successfully',
            ]);

            return response()->json([
                'message' => 'Job dispatched successfully',
                'log' => $jobLog,
            ]);
        } catch (\Exception $e) {
            if (isset($jobLog)) {
                $jobLog->update([
                    'status' => 'failed',
                    'finished_at' => now(),
                    'error_message' => $e->getMessage(),
                ]);
            }

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Run a cron command manually
     */
    public function runCronCommand(Request $request)
    {
        $request->validate([
            'command' => 'required|string',
        ]);

        $command = $request->command;

        try {
            $jobLog = JobLog::create([
                'job_name' => $command,
                'job_type' => 'cron',
                'status' => 'running',
                'started_at' => now(),
                'payload' => ['manual_run' => true],
            ]);

            $exitCode = Artisan::call($command);
            $output = Artisan::output();

            $jobLog->update([
                'status' => $exitCode === 0 ? 'success' : 'failed',
                'finished_at' => now(),
                'output' => $output,
                'error_message' => $exitCode !== 0 ? 'Command failed with exit code: ' . $exitCode : null,
            ]);

            return response()->json([
                'message' => 'Command executed',
                'exit_code' => $exitCode,
                'output' => $output,
                'log' => $jobLog,
            ]);
        } catch (\Exception $e) {
            if (isset($jobLog)) {
                $jobLog->update([
                    'status' => 'failed',
                    'finished_at' => now(),
                    'error_message' => $e->getMessage(),
                ]);
            }

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update cron schedule
     */
    public function updateCronSchedule(Request $request)
    {
        $request->validate([
            'command' => 'required|string',
            'new_schedule' => 'required|string',
        ]);

        $command = $request->command;
        $newSchedule = $request->new_schedule;
        $kernelPath = app_path('Console/Kernel.php');

        if (!File::exists($kernelPath)) {
            return response()->json(['error' => 'Kernel.php not found'], 404);
        }

        try {
            $content = File::get($kernelPath);
            
            // Find and replace the schedule for the specific command
            $pattern = '/\$schedule->command\([\'"]' . preg_quote($command, '/') . '[\'"]\)([^;]*);/s';
            
            if (preg_match($pattern, $content, $matches)) {
                $newLine = "\$schedule->command('{$command}'){$newSchedule};";
                $content = preg_replace($pattern, $newLine, $content);
                
                File::put($kernelPath, $content);
                
                return response()->json([
                    'message' => 'Schedule updated successfully',
                    'new_schedule' => $newSchedule,
                ]);
            }

            return response()->json(['error' => 'Command not found in Kernel.php'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get failed jobs from Laravel's failed_jobs table
     */
    public function failedJobs()
    {
        $failedJobs = DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->get()
            ->map(function ($job) {
                $payload = json_decode($job->payload, true);
                return [
                    'id' => $job->id,
                    'uuid' => $job->uuid,
                    'connection' => $job->connection,
                    'queue' => $job->queue,
                    'job_name' => $payload['displayName'] ?? $payload['job'] ?? 'Unknown',
                    'exception' => $job->exception,
                    'failed_at' => $job->failed_at,
                    'payload' => $payload,
                ];
            });

        return response()->json($failedJobs);
    }

    /**
     * Retry a failed job
     */
    public function retryFailedJob(Request $request)
    {
        $request->validate([
            'id' => 'required',
        ]);

        $id = $request->id;

        try {
            $exitCode = Artisan::call('queue:retry', ['id' => $id]);
            $output = Artisan::output();

            if ($exitCode === 0) {
                return response()->json([
                    'message' => 'Job retried successfully',
                    'output' => $output,
                ]);
            } else {
                return response()->json([
                    'error' => 'Failed to retry job',
                    'output' => $output,
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete old job logs
     */
    public function cleanupLogs(Request $request)
    {
        $request->validate([
            'days' => 'required|integer|min:1',
        ]);

        $days = $request->days;
        $deleted = JobLog::where('created_at', '<', now()->subDays($days))->delete();

        return response()->json([
            'message' => "Deleted {$deleted} old logs",
            'deleted_count' => $deleted,
        ]);
    }

    /**
     * Parse schedule options from Kernel.php
     */
    private function parseScheduleOptions($options)
    {
        $info = [];

        // Extract timezone
        if (preg_match('/->timezone\([\'"]([^\'"]+)[\'"]\)/', $options, $match)) {
            $info['timezone'] = $match[1];
        }

        // Extract schedule methods
        $scheduleMethods = [
            'daily', 'hourly', 'weekly', 'monthly', 'yearly',
            'dailyAt', 'hourlyAt', 'weeklyOn', 'monthlyOn',
            'everyMinute', 'everyFiveMinutes', 'everyTenMinutes',
            'everyFifteenMinutes', 'everyThirtyMinutes', 'everyTwoMinutes',
            'everyThreeMinutes', 'everyFourMinutes', 'everySixMinutes',
            'everyTwoHours', 'everyThreeHours', 'everyFourHours', 'everySixHours',
        ];

        foreach ($scheduleMethods as $method) {
            if (preg_match("/->$method\(([^)]*)\)/", $options, $match)) {
                $info['method'] = $method;
                $info['parameters'] = $match[1];
                break;
            }
        }

        return $info;
    }
}
