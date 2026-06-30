<?php

namespace App\Http\Controllers;

use App\Models\HrAccessType;
use App\Models\HrAccessResource;
use App\Models\HrEmployeeAccess;
use App\Models\HrEmployee;
use App\Services\GoogleDrive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class HrAccessController extends Controller
{
    // Access Types
    public function getAccessTypes()
    {
        $types = HrAccessType::with('resources')->get();
        return response()->json($types);
    }

    public function storeAccessType(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:hr_access_types',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'disabled' => 'nullable|boolean',
        ]);

        $type = HrAccessType::create($validated);
        return response()->json($type, 201);
    }

    public function updateAccessType(Request $request, $id)
    {
        $type = HrAccessType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:hr_access_types,name,' . $id,
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'disabled' => 'boolean',
        ]);

        $type->update($validated);
        return response()->json($type);
    }

    public function deleteAccessType($id)
    {
        $type = HrAccessType::findOrFail($id);
        $type->delete();
        return response()->json(null, 204);
    }

    // Access Resources
    public function getAccessResources()
    {
        $resources = HrAccessResource::with('accessType')->get();
        return response()->json($resources);
    }

    public function storeAccessResource(Request $request)
    {
        $validated = $request->validate([
            'access_type_id' => 'required|uuid|exists:hr_access_types,id',
            'name' => 'required|string|max:255',
            'path' => 'nullable|string|max:500',
            'drive_file_id' => 'nullable|string|max:255',
            'server_ip' => 'nullable|string|max:50',
            'import_method' => 'nullable|string|in:manual,google_drive,file_server',
            'description' => 'nullable|string',
            'disabled' => 'boolean',
        ]);

        $resource = HrAccessResource::create($validated);
        return response()->json($resource, 201);
    }

    public function updateAccessResource(Request $request, $id)
    {
        $resource = HrAccessResource::findOrFail($id);

        $validated = $request->validate([
            'access_type_id' => 'required|uuid|exists:hr_access_types,id',
            'name' => 'required|string|max:255',
            'path' => 'nullable|string|max:500',
            'drive_file_id' => 'nullable|string|max:255',
            'server_ip' => 'nullable|string|max:50',
            'import_method' => 'nullable|string|in:manual,google_drive,file_server',
            'description' => 'nullable|string',
            'disabled' => 'boolean',
        ]);

        $resource->update($validated);
        return response()->json($resource);
    }

    public function deleteAccessResource($id)
    {
        $resource = HrAccessResource::findOrFail($id);
        $resource->delete();
        return response()->json(null, 204);
    }

    // Employee Accesses
    public function getEmployeeAccesses($employeeId)
    {
        $accesses = HrEmployeeAccess::with(['accessResource.accessType'])
            ->where('employee_id', $employeeId)
            ->get();
        return response()->json($accesses);
    }

    public function storeEmployeeAccess(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|uuid|exists:hr_employees,id',
            'access_resource_id' => 'required|uuid|exists:hr_access_resources,id',
            'permission' => 'nullable|in:read,write,admin',
            'notes' => 'nullable|string',
        ]);

        $resource = HrAccessResource::find($validated['access_resource_id']);

        // Use resource's default_permission if not provided
        if (empty($validated['permission'])) {
            $validated['permission'] = $resource->default_permission ?? 'read';
        }

        $access = HrEmployeeAccess::create($validated);
        $access->load('accessResource.accessType');

        // Integrate Google Drive permissions
        $employee = HrEmployee::find($validated['employee_id']);

        if ($resource && $resource->drive_file_id && $employee && $employee->email) {
            $googleRole = $this->mapPermissionToGoogleRole($validated['permission']);
            GoogleDrive::set_role($resource->drive_file_id, $employee->email, $googleRole, 'create');
        }

        return response()->json($access, 201);
    }

    public function updateEmployeeAccess(Request $request, $id)
    {
        $access = HrEmployeeAccess::findOrFail($id);

        $validated = $request->validate([
            'permission' => 'required|in:read,write,admin',
            'notes' => 'nullable|string',
        ]);

        $access->update($validated);
        $access->load('accessResource.accessType');

        // Integrate Google Drive permissions
        $resource = HrAccessResource::find($access->access_resource_id);
        $employee = HrEmployee::find($access->employee_id);

        if ($resource && $resource->drive_file_id && $employee && $employee->email) {
            $googleRole = $this->mapPermissionToGoogleRole($validated['permission']);

            // First remove existing permission, then create new one
            GoogleDrive::removeShared($resource->drive_file_id, $employee->email);
            GoogleDrive::set_role($resource->drive_file_id, $employee->email, $googleRole, 'create');
        }

        return response()->json($access);
    }

    public function deleteEmployeeAccess($id)
    {
        $access = HrEmployeeAccess::findOrFail($id);

        // Integrate Google Drive permissions removal
        $resource = HrAccessResource::find($access->access_resource_id);
        $employee = HrEmployee::find($access->employee_id);

        if ($resource && $resource->drive_file_id && $employee && $employee->email) {
            GoogleDrive::removeShared($resource->drive_file_id, $employee->email);
        }

        $access->delete();
        return response()->json(null, 204);
    }

    public function getEmployeeAccessesByResource($resourceId)
    {
        $accesses = HrEmployeeAccess::with(['employee'])
            ->where('access_resource_id', $resourceId)
            ->get();
        return response()->json($accesses);
    }

    private function mapPermissionToGoogleRole($permission)
    {
        $mapping = [
            'read' => 'reader',
            'write' => 'writer',
            'admin' => 'fileOrganizer',
        ];

        return $mapping[$permission] ?? 'reader';
    }

    public function importGoogleDriveFolders(Request $request)
    {
        try {
            $validated = $request->validate([
                'access_type_id' => 'nullable|uuid|exists:hr_access_types,id',
                'parent_folder_id' => 'nullable|string',
                'shared_drive_only' => 'nullable|boolean',
                'drive_id' => 'nullable|string',
                'folder_name' => 'nullable|string',
            ]);

            Log::info('Google Drive import request: ' . json_encode($validated));
            Log::info('Google Drive shared_drive_only value: ' . var_export($validated['shared_drive_only'] ?? null, true));

            $service = Storage::disk('google')->getAdapter()->getService();
            $folders = [];
            $sharedDriveOnly = !empty($validated['shared_drive_only']);
            $driveId = $validated['drive_id'] ?? null;
            $folderName = trim($validated['folder_name'] ?? '');

            $buildNameFilter = function() use ($folderName) {
                if (empty($folderName))
                    return '';
                $escapedName = str_replace("'", "\\'", $folderName);
                return " and name contains '{$escapedName}'";
            };

            if (!empty($validated['parent_folder_id'])) {
                // Get folders from specific parent
                $parameters = [
                    'q' => "'{$validated['parent_folder_id']}' in parents and mimeType = 'application/vnd.google-apps.folder' and trashed=false" . $buildNameFilter(),
                    'fields' => 'files(id,name,parents)',
                    'supportsAllDrives' => true,
                    'includeItemsFromAllDrives' => true,
                ];

                if (!empty($driveId)) {
                    $parameters['driveId'] = $driveId;
                    $parameters['corpora'] = 'drive';
                }

                Log::info('Google Drive parent query: ' . json_encode($parameters));
                $results = $service->files->listFiles($parameters);
                $folders = $results->getFiles();
                Log::info('Google Drive parent folders found: ' . count($folders));
                Log::info('Google Drive parent folder names: ' . json_encode(array_map(fn($f) => $f->name, $folders)));
            }
            elseif ($sharedDriveOnly) {
                // Get all shared drives as first level
                $drives = $service->drives->listDrives([
                    'fields' => 'drives(id,name)',
                    'pageSize' => 100,
                ]);

                Log::info('Google Drive shared drives found: ' . count($drives->getDrives()));

                foreach ($drives->getDrives() as $drive) {
                    $driveFolder = new \stdClass();
                    $driveFolder->id = $drive->id;
                    $driveFolder->name = $drive->name;
                    $driveFolder->parents = [];
                    $driveFolder->isSharedDrive = true;
                    $folders[] = $driveFolder;
                }
            }
            else {
                // Get all folders from root
                $parameters = [
                    'q' => "mimeType = 'application/vnd.google-apps.folder' and trashed=false" . $buildNameFilter(),
                    'fields' => 'files(id,name,parents)',
                    'supportsAllDrives' => true,
                    'includeItemsFromAllDrives' => true,
                ];
                Log::info('Google Drive root query: all folders');
                $results = $service->files->listFiles($parameters);
                $folders = $results->getFiles();
            }

            Log::info('Google Drive folders found: ' . count($folders));

            return response()->json($folders);
        }
        catch (\Exception $e) {
            Log::error('Google Drive import error: ' . $e->getMessage());
            Log::error('Google Drive import trace: ' . $e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function importFileServerFolders(Request $request)
    {
        try {
            $validated = $request->validate([
                'server_ip' => 'nullable|string|max:50',
                'share_name' => 'required|string|max:255',
                'username' => 'nullable|string',
                'password' => 'nullable|string',
                'parent_path' => 'nullable|string|max:500',
                'folder_name' => 'nullable|string|max:255',
            ]);

            // Use .env values as defaults if not provided
            $serverIp = $validated['server_ip'] ?? env('FILE_SERVER_IP');
            $username = $validated['username'] ?? env('FILE_SERVER_USERNAME');
            $password = $validated['password'] ?? env('FILE_SERVER_PASSWORD');

            if (empty($serverIp)) {
                return response()->json(['error' => 'Server IP is required (provide in form or set FILE_SERVER_IP in .env)'], 400);
            }

            $folders = [];
            $serverPath = "\\\\{$serverIp}\\{$validated['share_name']}";

            // If parent_path is provided, use it as base
            if (!empty($validated['parent_path'])) {
                $serverPath = $validated['parent_path'];
            }

            // Use SMB authentication if credentials are provided
            if (!empty($username) && !empty($password)) {
                $folders = $this->scanSmbDirectories(
                    $serverPath,
                    $username,
                    $password,
                    $validated['folder_name'] ?? null
                );
            }
            else {
                // Fallback to local filesystem scan (no authentication)
                if (!is_dir($serverPath)) {
                    return response()->json(['error' => 'Server path not accessible'], 400);
                }

                $directories = scandir($serverPath);
                foreach ($directories as $dir) {
                    if ($dir !== '.' && $dir !== '..' && is_dir($serverPath . '\\' . $dir)) {
                        if (!empty($validated['folder_name'])) {
                            if (stripos($dir, $validated['folder_name']) === false) {
                                continue;
                            }
                        }

                        $folders[] = [
                            'name' => $dir,
                            'path' => $serverPath . '\\' . $dir,
                        ];
                    }
                }
            }

            return response()->json($folders);
        }
        catch (\Exception $e) {
            Log::channel('stderr')->error('File Server import error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function scanSmbDirectories($serverPath, $username, $password, $filterName = null)
    {
        $folders = [];
        $isWindows = PHP_OS_FAMILY === 'Windows';

        if ($isWindows) {
            // Windows: Use net use or PowerShell
            $driveLetter = 'Z:';
            $escapedPassword = escapeshellarg($password);
            $escapedUsername = escapeshellarg($username);
            $escapedServerPath = escapeshellarg($serverPath);

            // Map network drive
            exec("net use {$driveLetter} {$escapedServerPath} /user:{$escapedUsername} {$escapedPassword} 2>&1", $output, $returnCode);

            if ($returnCode !== 0) {
                // Try with different syntax
                exec("net use {$driveLetter} /delete 2>&1", $deleteOutput);
                exec("net use {$driveLetter} {$escapedServerPath} {$escapedPassword} /user:{$escapedUsername} 2>&1", $output, $returnCode);
            }

            if ($returnCode === 0) {
                $directories = scandir($driveLetter . '\\');
                foreach ($directories as $dir) {
                    if ($dir !== '.' && $dir !== '..' && is_dir($driveLetter . '\\' . $dir)) {
                        if ($filterName && stripos($dir, $filterName) === false) {
                            continue;
                        }

                        $folders[] = [
                            'name' => $dir,
                            'path' => $serverPath . '\\' . $dir,
                        ];
                    }
                }

                // Disconnect network drive
                exec("net use {$driveLetter} /delete 2>&1");
            }
        }
        else {
            // Linux: Use smbclient
            $escapedServerPath = str_replace('\\', '/', $serverPath);
            $escapedUsername = escapeshellarg($username);
            $escapedPassword = escapeshellarg($password);

            $command = "smbclient '{$escapedServerPath}' -U {$escapedUsername}%{$escapedPassword} -c 'ls'";
            exec($command, $output, $returnCode);

            if ($returnCode === 0) {
                foreach ($output as $line) {
                    if (preg_match('/^\s*(D)\s+([^\s]+)\s+/', $line, $matches)) {
                        $dirName = $matches[2];
                        if ($filterName && stripos($dirName, $filterName) === false) {
                            continue;
                        }

                        $folders[] = [
                            'name' => $dirName,
                            'path' => $serverPath . '/' . $dirName,
                        ];
                    }
                }
            }
        }

        return $folders;
    }

    public function bulkImportResources(Request $request)
    {
        try {
            $validated = $request->validate([
                'access_type_id' => 'required|uuid|exists:hr_access_types,id',
                'default_permission' => 'nullable|in:read,write,admin',
                'employee_id' => 'nullable|uuid|exists:hr_employees,id',
                'assign_to_employee' => 'nullable|boolean',
                'resources' => 'required|array',
                'resources.*.name' => 'required|string|max:255',
                'resources.*.path' => 'nullable|string|max:500',
                'resources.*.drive_file_id' => 'nullable|string|max:255',
                'resources.*.import_method' => 'required|string|in:manual,google_drive,file_server',
            ]);

            $imported = [];
            foreach ($validated['resources'] as $resourceData) {
                $resourceData['access_type_id'] = $validated['access_type_id'];
                $resourceData['default_permission'] = $validated['default_permission'] ?? 'read';
                $resource = HrAccessResource::create($resourceData);
                $imported[] = $resource;
            }

            Log::info('Bulk imported resources with default_permission: ' . ($validated['default_permission'] ?? 'read'));

            // Auto-assign to employee if requested
            $accessesCreated = [];
            if (!empty($validated['assign_to_employee']) && !empty($validated['employee_id'])) {
                $employee = HrEmployee::find($validated['employee_id']);
                $permission = $validated['default_permission'] ?? 'read';

                foreach ($imported as $resource) {
                    if ($resource->drive_file_id && $employee && $employee->email) {
                        $access = HrEmployeeAccess::create([
                            'employee_id' => $validated['employee_id'],
                            'access_resource_id' => $resource->id,
                            'permission' => $permission,
                            'notes' => null,
                        ]);

                        // Set Google Drive permission
                        $googleRole = $this->mapPermissionToGoogleRole($permission);
                        GoogleDrive::set_role($resource->drive_file_id, $employee->email, $googleRole, 'create');

                        $accessesCreated[] = $access;
                    }
                }
                Log::info('Auto-assigned ' . count($accessesCreated) . ' resources to employee with permission: ' . $permission);
            }

            return response()->json([
                'resources' => $imported,
                'accesses' => $accessesCreated,
            ], 201);
        }
        catch (\Exception $e) {
            Log::channel('stderr')->error('Bulk import error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
