<?php

namespace App\Http\Controllers;

use App\Services\GoogleDrive;
use App\Services\GoogleCalendar;
use App\Services\SettingService;
use App\Services\GeminiAiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ServiceHealthController extends Controller
{
    /**
     * Get health status of all services
     */
    public function index()
    {
        try {
            $services = array_merge(
                $this->checkAllDatabases(),
                [
                    $this->checkCache(),
                    $this->checkStorage(),
                    $this->checkGoogleDrive(),
                    $this->checkGoogleCalendar(),
                    $this->checkGemini(),
                    $this->checkSettings(),
                    $this->checkSystemLoad(),
                ]
            );

            return response()->json([
                'services' => $services,
                'overall_status' => $this->getOverallStatus($services),
                'checked_at' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'services' => [],
                'overall_status' => 'error',
                'checked_at' => now()->toIso8601String(),
            ], 500);
        }
    }

    /**
     * Check all database connections
     */
    protected function checkAllDatabases()
    {
        $connections = [
            //'mysql' => 'MySQL',
            'mysql_old' => 'MySQL (Legacy)',
            'sqlsrv' => 'SQL Server (Primary)',
            'sqlsrv_fornitori' => 'SQL Server (Fornitori)',
            'sqlsrv_gp' => 'SQL Server (GP)',
            'sqlsrv_root_gp' => 'SQL Server (Root GP)',

        ];

        $results = [];

        foreach ($connections as $connection => $name) {
            $results[] = $this->checkDatabaseConnection($connection, $name);
        }

        return $results;
    }

    /**
     * Check specific database connection
     */
    protected function checkDatabaseConnection($connection, $name)
    {
        try {
            $config = config("database.connections.{$connection}");
            $host = $config['host'] ?? 'unknown';
            $port = $config['port'] ?? 'default';
            $database = $config['database'] ?? 'unknown';

            $responseTime = $this->measureResponseTime(function () use ($connection) {
                DB::connection($connection)->getPdo();
            });

            return [
                'name' => "Database - {$name}",
                'status' => 'healthy',
                'message' => "Connection successful ({$connection})",
                'response_time' => $responseTime,
                'host' => $host,
                'port' => $port,
                'database' => $database,
            ];
        } catch (\Exception $e) {
            $config = config("database.connections.{$connection}");
            $host = $config['host'] ?? 'unknown';
            $port = $config['port'] ?? 'default';
            $database = $config['database'] ?? 'unknown';

            return [
                'name' => "Database - {$name}",
                'status' => 'unhealthy',
                'message' => "Connection failed ({$connection}): " . $e->getMessage(),
                'response_time' => null,
                'host' => $host,
                'port' => $port,
                'database' => $database,
            ];
        }
    }

    /**
     * Check cache connection
     */
    protected function checkCache()
    {
        try {
            $testKey = 'health_check_test';
            Cache::put($testKey, 'test', 60);
            $value = Cache::get($testKey);
            Cache::forget($testKey);

            if ($value === 'test') {
                return [
                    'name' => 'Cache',
                    'status' => 'healthy',
                    'message' => 'Cache read/write successful',
                    'response_time' => $this->measureResponseTime(function () {
                        Cache::put('health_check_test', 'test', 60);
                        Cache::get('health_check_test');
                        Cache::forget('health_check_test');
                    }),
                ];
            }

            return [
                'name' => 'Cache',
                'status' => 'unhealthy',
                'message' => 'Cache value mismatch',
                'response_time' => null,
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Cache',
                'status' => 'unhealthy',
                'message' => $e->getMessage(),
                'response_time' => null,
            ];
        }
    }

    /**
     * Check storage connection
     */
    protected function checkStorage()
    {
        try {
            $testFile = 'health_check_test.txt';
            Storage::put($testFile, 'test');
            $exists = Storage::exists($testFile);
            Storage::delete($testFile);

            if ($exists) {
                return [
                    'name' => 'Storage',
                    'status' => 'healthy',
                    'message' => 'Storage read/write successful',
                    'response_time' => $this->measureResponseTime(function () {
                        Storage::put('health_check_test.txt', 'test');
                        Storage::exists('health_check_test.txt');
                        Storage::delete('health_check_test.txt');
                    }),
                ];
            }

            return [
                'name' => 'Storage',
                'status' => 'unhealthy',
                'message' => 'Storage file not found',
                'response_time' => null,
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Storage',
                'status' => 'unhealthy',
                'message' => $e->getMessage(),
                'response_time' => null,
            ];
        }
    }

    /**
     * Check Google Drive service
     */
    protected function checkGoogleDrive()
    {
        try {
            $drive = new GoogleDrive();
            $storageUsed = 0;
            $storageLimit = 0;
            $storageUsagePercent = 0;

            $responseTime = $this->measureResponseTime(function () use ($drive, &$storageUsed, &$storageLimit, &$storageUsagePercent) {
                // Try to access the service
                if (empty($drive->service)) {
                    throw new \Exception('Google Drive service not initialized');
                }

                // Get storage usage information
                try {
                    $about = $drive->service->about->get(['fields' => 'storageQuota']);
                    $storageQuota = $about->getStorageQuota();

                    if ($storageQuota) {
                        $storageUsed = (int) ($storageQuota->getUsage() ?? 0);
                        $storageLimit = (int) ($storageQuota->getLimit() ?? 0);

                        if ($storageLimit > 0) {
                            $storageUsagePercent = round(($storageUsed / $storageLimit) * 100, 2);
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Could not fetch Google Drive storage quota: ' . $e->getMessage());
                }
            });

            $message = "Google Drive service accessible";
            if ($storageLimit > 0) {
                $message .= " - Storage: {$storageUsagePercent}% used";
            }

            // Standard Google Drive API quota limits (per day)
            // Note: These are standard limits. Actual quota depends on Google Cloud project configuration
            $apiQuotaLimit = 1000000; // 1M queries/day for public files, 1K for private files
            $apiQuotaUsed = null; // Would need Google Cloud Platform API monitoring to get actual usage

            return [
                'name' => 'Google Drive',
                'status' => 'healthy',
                'message' => $message,
                'response_time' => $responseTime,
                'storage_used' => $storageUsed,
                'storage_limit' => $storageLimit,
                'storage_usage_percent' => $storageUsagePercent,
                'api_quota_limit' => $apiQuotaLimit,
                'api_quota_used' => $apiQuotaUsed,
                'api_quota_percent' => null,
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Google Drive',
                'status' => 'unhealthy',
                'message' => $e->getMessage(),
                'response_time' => null,
                'storage_used' => null,
                'storage_limit' => null,
                'storage_usage_percent' => null,
                'api_quota_limit' => null,
                'api_quota_used' => null,
                'api_quota_percent' => null,
            ];
        }
    }

    /**
     * Check Google Calendar service
     */
    protected function checkGoogleCalendar()
    {
        try {
            $responseTime = $this->measureResponseTime(function () {
                $client = GoogleCalendar::getClient();
                if (empty($client)) {
                    throw new \Exception('Google Calendar client not initialized');
                }
            });

            return [
                'name' => 'Google Calendar',
                'status' => 'healthy',
                'message' => 'Google Calendar service accessible',
                'response_time' => $responseTime,
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Google Calendar',
                'status' => 'unhealthy',
                'message' => $e->getMessage(),
                'response_time' => null,
            ];
        }
    }

    /**
     * Check Gemini AI service - tests multiple models
     */
    protected function checkGemini()
    {
        try {
            $settingService = new SettingService();
            $apiKey = $settingService->get('gemini_api_key');
            
            if (empty($apiKey)) {
                return [
                    'name' => 'Gemini AI',
                    'status' => 'unhealthy',
                    'message' => 'gemini_api_key not configured in database',
                    'response_time' => null,
                    'models' => [],
                ];
            }

            // Test multiple models
            $models = [
                //'gemini-3.6-flash',
                //'gemini-3.1-flash-lite',
            ];

            $modelResults = [];
            $healthyCount = 0;
            $totalResponseTime = 0;

            foreach ($models as $model) {
                try {
                    $startTime = microtime(true);
                    $geminiService = new GeminiAiService();
                    $result = $geminiService->analizzaTesto('Respond with "OK" only', $model);
                    $responseTime = round((microtime(true) - $startTime) * 1000, 2);
                    
                    if ($result !== null && !empty(trim($result))) {
                        $modelResults[] = [
                            'model' => $model,
                            'status' => 'healthy',
                            'response_time' => $responseTime,
                            'message' => 'Model accessible',
                        ];
                        $healthyCount++;
                        $totalResponseTime += $responseTime;
                    } else {
                        $modelResults[] = [
                            'model' => $model,
                            'status' => 'unhealthy',
                            'response_time' => $responseTime,
                            'message' => 'Model returned null or empty response',
                        ];
                    }
                } catch (\Exception $e) {
                    $errorMessage = $e->getMessage();
                    $quotaInfo = 'Unknown';
                    
                    // Extract quota information from error message
                    if (str_contains($errorMessage, 'quota') || str_contains($errorMessage, 'Quota')) {
                        $quotaInfo = 'Quota exceeded';
                        if (str_contains($errorMessage, 'free_tier')) {
                            $quotaInfo = 'Free tier quota exceeded';
                        }
                    }
                    
                    $modelResults[] = [
                        'model' => $model,
                        'status' => 'unhealthy',
                        'response_time' => null,
                        'message' => $errorMessage,
                        'quota_info' => $quotaInfo,
                    ];
                }
            }

            // Determine overall status
            $overallStatus = 'unhealthy';
            if ($healthyCount === count($models)) {
                $overallStatus = 'healthy';
            } elseif ($healthyCount > 0) {
                $overallStatus = 'degraded';
            }

            $avgResponseTime = $healthyCount > 0 ? round($totalResponseTime / $healthyCount, 2) : null;

            return [
                'name' => 'Gemini AI',
                'status' => $overallStatus,
                'message' => "{$healthyCount}/" . count($models) . " models accessible",
                'response_time' => $avgResponseTime,
                'models' => $modelResults,
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Gemini AI',
                'status' => 'unhealthy',
                'message' => $e->getMessage(),
                'response_time' => null,
                'models' => [],
            ];
        }
    }

    /**
     * Check Settings service
     */
    protected function checkSettings()
    {
        try {
            $responseTime = $this->measureResponseTime(function () {
                $settingService = new SettingService();
                $settingService->get('app_version');
            });

            return [
                'name' => 'Settings',
                'status' => 'healthy',
                'message' => 'Settings service accessible',
                'response_time' => $responseTime,
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'Settings',
                'status' => 'unhealthy',
                'message' => $e->getMessage(),
                'response_time' => null,
            ];
        }
    }

    /**
     * Check system load (CPU, Memory, Disk)
     */
    protected function checkSystemLoad()
    {
        try {
            $cpuUsage = $this->getCpuUsage();
            $memoryUsage = $this->getMemoryUsage();
            $diskUsage = $this->getDiskUsage();
            $loadAverage = $this->getLoadAverage();

            // Determine overall status based on thresholds
            $status = 'healthy';
            $messages = [];

            if ($cpuUsage > 80) {
                $status = 'degraded';
                $messages[] = "High CPU usage: {$cpuUsage}%";
            }
            if ($memoryUsage > 80) {
                $status = 'degraded';
                $messages[] = "High memory usage: {$memoryUsage}%";
            }
            if ($diskUsage > 90) {
                $status = $status === 'degraded' ? 'unhealthy' : 'degraded';
                $messages[] = "High disk usage: {$diskUsage}%";
            }

            // Check load average (Linux only, typically > number of CPUs is high)
            if ($loadAverage && isset($loadAverage['1min']) && PHP_OS_FAMILY !== 'Windows') {
                $cpuCount = $this->getCpuCount();
                if ($loadAverage['1min'] > $cpuCount * 2) {
                    $status = 'degraded';
                    $messages[] = "High load average: {$loadAverage['1min']}";
                }
            }

            if ($status === 'healthy') {
                $message = "CPU: {$cpuUsage}%, Memory: {$memoryUsage}%, Disk: {$diskUsage}%";
            } else {
                $message = implode(', ', $messages);
            }

            return [
                'name' => 'System Load',
                'status' => $status,
                'message' => $message,
                'response_time' => null,
                'cpu_usage' => $cpuUsage,
                'memory_usage' => $memoryUsage,
                'disk_usage' => $diskUsage,
                'load_average' => $loadAverage,
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'System Load',
                'status' => 'unhealthy',
                'message' => $e->getMessage(),
                'response_time' => null,
                'cpu_usage' => null,
                'memory_usage' => null,
                'disk_usage' => null,
                'load_average' => null,
            ];
        }
    }

    /**
     * Get CPU count
     */
    protected function getCpuCount()
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // Windows: use WMIC
            $output = shell_exec('wmic cpu get NumberOfCores /value');
            if (preg_match('/NumberOfCores=(\d+)/', $output, $matches)) {
                return (int) $matches[1];
            }
        } else {
            // Linux: use /proc/cpuinfo
            if (file_exists('/proc/cpuinfo')) {
                $cpuinfo = file_get_contents('/proc/cpuinfo');
                preg_match_all('/^processor\s+:\s+\d+/m', $cpuinfo, $matches);
                return count($matches[0]);
            }
        }
        
        // Fallback: assume 1 CPU
        return 1;
    }

    /**
     * Get CPU usage percentage
     */
    protected function getCpuUsage()
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // Windows: use WMIC
            $output = shell_exec('wmic cpu get loadpercentage /value');
            if (preg_match('/LoadPercentage=(\d+)/', $output, $matches)) {
                return (int) $matches[1];
            }
        } else {
            // Linux: use /proc/stat
            $stat1 = $this->getCpuStat();
            sleep(1);
            $stat2 = $this->getCpuStat();
            
            if ($stat1 && $stat2) {
                $total1 = array_sum($stat1);
                $idle1 = $stat1['idle'];
                $total2 = array_sum($stat2);
                $idle2 = $stat2['idle'];
                
                $totalDiff = $total2 - $total1;
                $idleDiff = $idle2 - $idle1;
                
                if ($totalDiff > 0) {
                    return round(100 - (($idleDiff / $totalDiff) * 100), 1);
                }
            }
        }
        
        return null;
    }

    /**
     * Get CPU stat from /proc/stat (Linux)
     */
    protected function getCpuStat()
    {
        if (file_exists('/proc/stat')) {
            $lines = file('/proc/stat');
            $line = preg_replace('/\s+/', ' ', trim($lines[0]));
            $parts = explode(' ', $line);
            
            return [
                'user' => (int) $parts[1],
                'nice' => (int) $parts[2],
                'system' => (int) $parts[3],
                'idle' => (int) $parts[4],
                'iowait' => (int) $parts[5],
            ];
        }
        
        return null;
    }

    /**
     * Get memory usage percentage
     */
    protected function getMemoryUsage()
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // Windows: use WMIC
            $output = shell_exec('wmic OS get TotalVisibleMemorySize,FreePhysicalMemory /value');
            preg_match('/TotalVisibleMemorySize=(\d+)/', $output, $totalMatches);
            preg_match('/FreePhysicalMemory=(\d+)/', $output, $freeMatches);
            
            if (isset($totalMatches[1]) && isset($freeMatches[1])) {
                $total = (int) $totalMatches[1];
                $free = (int) $freeMatches[1];
                $used = $total - $free;
                
                return round(($used / $total) * 100, 1);
            }
        } else {
            // Linux: use /proc/meminfo
            if (file_exists('/proc/meminfo')) {
                $meminfo = file_get_contents('/proc/meminfo');
                preg_match('/MemTotal:\s+(\d+)/', $meminfo, $totalMatches);
                preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $availMatches);
                
                if (isset($totalMatches[1]) && isset($availMatches[1])) {
                    $total = (int) $totalMatches[1];
                    $available = (int) $availMatches[1];
                    $used = $total - $available;
                    
                    return round(($used / $total) * 100, 1);
                }
            }
        }
        
        return null;
    }

    /**
     * Get disk usage percentage
     */
    protected function getDiskUsage()
    {
        $total = disk_total_space(base_path());
        $free = disk_free_space(base_path());
        
        if ($total && $free) {
            $used = $total - $free;
            return round(($used / $total) * 100, 1);
        }
        
        return null;
    }

    /**
     * Get load average (Linux: 1/5/15 min, Windows: process queue length)
     */
    protected function getLoadAverage()
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // Windows: use system_getloadaverage if available (PHP 8.0+ on Windows)
            if (function_exists('sys_getloadavg')) {
                $load = sys_getloadavg();
                return [
                    '1min' => $load[0] ?? null,
                    '5min' => $load[1] ?? null,
                    '15min' => $load[2] ?? null,
                ];
            }
            
            // Fallback: use PowerShell to get process queue length
            $output = shell_exec('powershell -Command "Get-WmiObject Win32_PerfFormattedData_PerfOS_System | Select-Object ProcessorQueueLength | ForEach-Object { $_.ProcessorQueueLength }"');
            $queueLength = trim($output);
            
            if (is_numeric($queueLength)) {
                return [
                    '1min' => (float) $queueLength,
                    '5min' => null,
                    '15min' => null,
                    'type' => 'queue_length',
                ];
            }
            
            return null;
        } else {
            // Linux: use sys_getloadavg
            $load = sys_getloadavg();
            
            return [
                '1min' => $load[0] ?? null,
                '5min' => $load[1] ?? null,
                '15min' => $load[2] ?? null,
            ];
        }
    }

    /**
     * Measure response time of a function
     */
    protected function measureResponseTime(callable $callback)
    {
        $start = microtime(true);
        $callback();
        $end = microtime(true);

        return round(($end - $start) * 1000, 2); // Return in milliseconds
    }

    /**
     * Get overall status based on individual service statuses
     */
    protected function getOverallStatus(array $services)
    {
        $unhealthyCount = collect($services)->where('status', 'unhealthy')->count();

        if ($unhealthyCount === 0) {
            return 'healthy';
        } elseif ($unhealthyCount < count($services)) {
            return 'degraded';
        } else {
            return 'unhealthy';
        }
    }
}
