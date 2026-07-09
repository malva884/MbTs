<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    protected SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * Get all settings grouped by group
     */
    public function index(Request $request)
    {
        $group = $request->query('group');

        if ($group) {
            return response()->json([
                'data' => $this->settingService->getByGroup($group),
            ]);
        }

        return response()->json([
            'data' => $this->settingService->all(),
        ]);
    }

    /**
     * Get a specific setting by key
     */
    public function show(string $key)
    {
        $value = $this->settingService->get($key);

        if ($value === null) {
            return response()->json([
                'message' => 'Setting not found',
            ], 404);
        }

        return response()->json([
            'key' => $key,
            'value' => $value,
        ]);
    }

    /**
     * Create a new setting
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string|unique:settings,key',
            'value' => 'required',
            'type' => 'required|in:string,boolean,integer,json,float',
            'group' => 'required|string',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $setting = $this->settingService->set(
            $request->key,
            $request->value,
            $request->type,
            $request->group,
            $request->description
        );

        return response()->json([
            'message' => 'Setting created successfully',
            'data' => $setting,
        ], 201);
    }

    /**
     * Update an existing setting
     */
    public function update(Request $request, string $key)
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required',
            'type' => 'sometimes|in:string,boolean,integer,json,float',
            'group' => 'sometimes|string',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $setting = Setting::where('key', $key)->first();

        if (!$setting) {
            return response()->json([
                'message' => 'Setting not found',
            ], 404);
        }

        $setting = $this->settingService->set(
            $key,
            $request->value,
            $request->type ?? $setting->type,
            $request->group ?? $setting->group,
            $request->description ?? $setting->description
        );

        // Special handling for session_lifetime - update config file
        if ($key === 'session_lifetime') {
            $this->updateSessionConfig($request->value);
        }

        return response()->json([
            'message' => 'Setting updated successfully',
            'data' => $setting,
        ]);
    }

    /**
     * Update session configuration file
     */
    protected function updateSessionConfig($lifetime)
    {
        $configPath = config_path('session.php');
        $configContent = file_get_contents($configPath);
        
        // Replace the SESSION_LIFETIME_FROM_DB line
        $configContent = preg_replace(
            "/env\('SESSION_LIFETIME_FROM_DB', env\('SESSION_LIFETIME', \d+\)\)/",
            "env('SESSION_LIFETIME_FROM_DB', {$lifetime})",
            $configContent
        );
        
        file_put_contents($configPath, $configContent);
        
        // Clear config cache
        \Illuminate\Support\Facades\Artisan::call('config:clear');
    }

    /**
     * Delete a setting
     */
    public function destroy(string $key)
    {
        $setting = Setting::where('key', $key)->first();

        if (!$setting) {
            return response()->json([
                'message' => 'Setting not found',
            ], 404);
        }

        $setting->delete();
        $this->settingService->clearCache($key);

        return response()->json([
            'message' => 'Setting deleted successfully',
        ]);
    }

    /**
     * Get all settings with metadata (for admin panel)
     */
    public function indexWithMetadata(Request $request)
    {
        $group = $request->query('group');

        $query = Setting::query();

        if ($group) {
            $query->where('group', $group);
        }

        $settings = $query->orderBy('group')->orderBy('key')->get();

        return response()->json([
            'data' => $settings,
        ]);
    }

    /**
     * Clear all settings cache
     */
    public function clearCache()
    {
        $this->settingService->clearAllCache();

        return response()->json([
            'message' => 'Settings cache cleared successfully',
        ]);
    }
}
