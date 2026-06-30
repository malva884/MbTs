<?php

namespace App\Http\Controllers;

use App\Models\ItNetworkDevice;
use Illuminate\Http\Request;

class ItNetworkDeviceController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_id' => 'required|uuid|exists:it_assets,id',
            'ip_address' => 'nullable|string|max:45',
            'mac_address' => 'nullable|string|max:17',
            'device_type' => 'required|in:Router,Switch,Access Point,Server,Firewall,Other',
            'location' => 'nullable|string|max:255',
            'rack_position' => 'nullable|string|max:50',
            'vlan' => 'nullable|string|max:50',
            'subnet' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'disabled' => 'boolean',
            'monitor_enabled' => 'boolean',
        ]);

        $networkDevice = ItNetworkDevice::create($validated);

        return response()->json($networkDevice, 201);
    }

    public function update(Request $request, $id)
    {
        $networkDevice = ItNetworkDevice::findOrFail($id);

        $validated = $request->validate([
            'ip_address' => 'nullable|string|max:45',
            'mac_address' => 'nullable|string|max:17',
            'device_type' => 'required|in:Router,Switch,Access Point,Server,Firewall,Other',
            'location' => 'nullable|string|max:255',
            'rack_position' => 'nullable|string|max:50',
            'vlan' => 'nullable|string|max:50',
            'subnet' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'disabled' => 'boolean',
            'monitor_enabled' => 'boolean',
        ]);

        $networkDevice->update($validated);

        return response()->json($networkDevice);
    }

    public function destroy($id)
    {
        $networkDevice = ItNetworkDevice::findOrFail($id);
        $networkDevice->delete();

        return response()->json(null, 204);
    }

    public function index()
    {
        $devices = ItNetworkDevice::with('asset')
            ->where('monitor_enabled', true)
            ->whereNotNull('ip_address')
            ->where('disabled', false)
            ->get();

        return response()->json($devices);
    }

    public function logs($id)
    {
        $logs = ItNetworkDeviceLog::where('network_device_id', $id)
            ->orderBy('checked_at', 'desc')
            ->limit(100)
            ->get();

        return response()->json($logs);
    }
}
