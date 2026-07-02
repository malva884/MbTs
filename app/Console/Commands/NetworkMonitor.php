<?php

namespace App\Console\Commands;

use App\Models\ItNetworkDevice;
use App\Models\ItNetworkDeviceLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class NetworkMonitor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'network:monitor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor network devices and update their status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting network device monitoring...');

        $devices = ItNetworkDevice::where('monitor_enabled', true)
            ->whereNotNull('ip_address')
            ->where('disabled', false)
            ->get();

        $this->info("Found {$devices->count()} devices to monitor");

        foreach ($devices as $device) {
            $this->monitorDevice($device);
        }

        $this->info('Network monitoring completed.');
    }

    private function monitorDevice(ItNetworkDevice $device)
    {
        $this->info("Checking device: {$device->ip_address}");

        $startTime = microtime(true);
        $status = $this->pingDevice($device->ip_address);
        $responseTime = round((microtime(true) - $startTime) * 1000);

        $previousStatus = $device->status;
        $device->status = $status;
        $device->last_check_at = now();
        $device->response_time_ms = $responseTime;

        if ($status === 'online') {
            $device->last_online_at = now();
        }

        // Calculate uptime percentage
        $totalChecks = ItNetworkDeviceLog::where('network_device_id', $device->id)->count();
        $onlineChecks = ItNetworkDeviceLog::where('network_device_id', $device->id)
            ->where('status', 'online')
            ->count();

        if ($totalChecks > 0) {
            $device->uptime_percentage = round(($onlineChecks / $totalChecks) * 100, 2);
        }

        $device->save();

        // Log the check
        ItNetworkDeviceLog::create([
            'network_device_id' => $device->id,
            'status' => $status,
            'response_time_ms' => $responseTime,
            'checked_at' => now(),
        ]);

        // Log status change
        if ($previousStatus !== $status) {
            Log::info("Device {$device->ip_address} status changed from {$previousStatus} to {$status}");
            $this->warn("Device {$device->ip_address} status changed: {$previousStatus} -> {$status}");
        }

        $this->info("Device {$device->ip_address}: {$status} ({$responseTime}ms)");
    }

    private function pingDevice($ip)
    {
        // Use PHP's exec to ping the device
        // On Windows: ping -n 1 IP
        // On Linux/Mac: ping -c 1 IP

        $os = PHP_OS_FAMILY;
        $command = '';

        if ($os === 'Windows') {
            $command = "ping -n 1 -w 2000 {$ip}";
        } else {
            $command = "ping -c 1 -W 2 {$ip}";
        }

        exec($command, $output, $returnCode);

        // Return code 0 means success (device is reachable)
        return $returnCode === 0 ? 'online' : 'offline';
    }
}
