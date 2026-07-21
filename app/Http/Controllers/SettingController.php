<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\SettingService;
use App\Print\ZplPrinter;
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
            'type' => 'required|in:string,boolean,integer,json,float,printer',
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
            'type' => 'sometimes|in:string,boolean,integer,json,float,printer',
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

        // Special handling for session_lifetime - no longer needed with CheckSessionLifetime middleware
        // The middleware reads directly from SettingService

        return response()->json([
            'message' => 'Setting updated successfully',
            'data' => $setting,
        ]);
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

    /**
     * Test printer connection
     */
    public function testPrinter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'host' => 'required|string',
            'port' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $printer = new ZplPrinter($request->host, $request->port);

            // Invia stampa di prova con testo STAMPA in grassetto al centro
            $zpl = "^XA\n^FO200,200^A0N,50,50^FDSTAMPA^FS\n^XZ";
            $printer->send($zpl);

            return response()->json([
                'message' => 'Connessione alla stampante riuscita e stampa di prova inviata',
                'success' => true,
            ]);
        }
        catch (\Exception $e) {
            return response()->json([
                'message' => 'Impossibile connettersi alla stampante: ' . $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }
}
