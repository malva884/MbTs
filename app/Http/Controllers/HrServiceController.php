<?php

namespace App\Http\Controllers;

use App\Models\HrServiceType;
use App\Models\HrService;
use App\Models\HrEmployeeService;
use App\Models\HrEmployee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HrServiceController extends Controller
{
    // Service Types
    public function getServiceTypes()
    {
        $types = HrServiceType::with('services')->get();
        return response()->json($types);
    }

    public function storeServiceType(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:hr_service_types',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'disabled' => 'nullable|boolean',
        ]);

        $type = HrServiceType::create($validated);
        return response()->json($type, 201);
    }

    public function updateServiceType(Request $request, $id)
    {
        $type = HrServiceType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:hr_service_types,name,' . $id,
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'disabled' => 'boolean',
        ]);

        $type->update($validated);
        return response()->json($type);
    }

    public function deleteServiceType($id)
    {
        $type = HrServiceType::findOrFail($id);
        $type->delete();
        return response()->json(null, 204);
    }

    // Services
    public function getServices()
    {
        $services = HrService::with('serviceType:id,name,icon')
            ->select('id', 'service_type_id', 'name', 'description', 'provider', 'disabled')
            ->where('disabled', false)
            ->get();
        return response()->json($services);
    }

    public function storeService(Request $request)
    {
        $validated = $request->validate([
            'service_type_id' => 'required|uuid|exists:hr_service_types,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'provider' => 'nullable|string|max:255',
            'server_url' => 'nullable|url|max:500',
            'domain' => 'nullable|string|max:255',
            'disabled' => 'boolean',
        ]);

        $service = HrService::create($validated);
        return response()->json($service, 201);
    }

    public function updateService(Request $request, $id)
    {
        $service = HrService::findOrFail($id);

        $validated = $request->validate([
            'service_type_id' => 'required|uuid|exists:hr_service_types,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'provider' => 'nullable|string|max:255',
            'server_url' => 'nullable|url|max:500',
            'domain' => 'nullable|string|max:255',
            'disabled' => 'boolean',
        ]);

        $service->update($validated);
        return response()->json($service);
    }

    public function deleteService($id)
    {
        $service = HrService::findOrFail($id);
        $service->delete();
        return response()->json(null, 204);
    }

    // Employee Services
    public function getEmployeeServices($employeeId)
    {
        $services = HrEmployeeService::with(['service:id,name,service_type_id', 'service.serviceType:id,name,icon', 'assignedBy:id,full_name'])
            ->where('employee_id', $employeeId)
            ->select('id', 'employee_id', 'service_id', 'username', 'email', 'phone', 'status', 'activated_at', 'expires_at', 'notes', 'assigned_by', 'created_at')
            ->get();
        return response()->json($services);
    }

    public function storeEmployeeService(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|uuid|exists:hr_employees,id',
            'service_id' => 'required|uuid|exists:hr_services,id',
            'username' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'status' => 'nullable|in:Active,Suspended,Revoked',
            'activated_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $validated['assigned_by'] = Auth::id();
        $validated['status'] = $validated['status'] ?? 'Active';

        $service = HrEmployeeService::create($validated);
        $service->load('service.serviceType', 'assignedBy');

        return response()->json($service, 201);
    }

    public function updateEmployeeService(Request $request, $id)
    {
        $service = HrEmployeeService::findOrFail($id);

        $validated = $request->validate([
            'username' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'status' => 'required|in:Active,Suspended,Revoked',
            'activated_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $service->update($validated);
        $service->load('service.serviceType', 'assignedBy');

        return response()->json($service);
    }

    public function deleteEmployeeService($id)
    {
        $service = HrEmployeeService::findOrFail($id);
        $service->delete();
        return response()->json(null, 204);
    }
}
