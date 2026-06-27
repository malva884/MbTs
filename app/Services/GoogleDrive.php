<?php

namespace App\Services;

use Exception;
use Google\Service;
use Google_Service_Drive_DriveFile;
use Google_Service_Drive_Permission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GoogleDrive
{
    public Service $service;

    function __construct($disk = null)
    {
        if (empty($disk))
            $disk = 'google';

        $this->service = Storage::disk($disk)->getAdapter()->getService();
    }

    public  static function slide($disk = null)
    {
        if (empty($disk))
            $disk = 'google';
        $driveService = Storage::disk($disk)->getAdapter()->getService();
    }

    public static function set_role($fileId,$userEmail,$role = 'commenter',$type = 'create')
    {
        try {
            if (empty($disk))
                $disk = 'google';

            $driveService = Storage::disk($disk)->getAdapter()->getService();
            $batch = $driveService->createBatch();
            $permission = new \Google_Service_Drive_Permission();

            if($type == 'create'){
                $driveService->getClient()->setUseBatch(true);
                $permission->setRole($role);
                $permission->setType('user');
                $permission->emailAddress = $userEmail;
                $request = $driveService->permissions->create(
                    $fileId, $permission, array(
                        'fields' => 'id',
                        "supportsAllDrives" => true,
                        "sendNotificationEmail" => false,
                    )
                );
                $batch->add($request, 'user');
                $batch->execute();
            }
            else{
                $id_permesso = null;
                $results = $driveService->permissions->listPermissions($fileId, array(
                    'fields' => '*',
                    "supportsAllDrives" => true,
                ));
                $permissions = $results->getPermissions();
                foreach ($permissions as $kk => $users){
                    if($users['emailAddress'] == $userEmail)
                        $id_permesso = $users['id'];
                }

                $driveService->permissions->delete($fileId, $id_permesso, array(
                    "supportsAllDrives" => true,
                ));
            }

        } catch (\Exception $e) {
            Log::channel('stderr')->error('Google Drive set_role error: ' . $e->getMessage());
            return false;
        }
    }

    public static function search($path, $disk, $type, $name = '', $create = false)
    {
        try{
            if (empty($disk))
                $disk = 'google';

            $service = Storage::disk($disk)->getAdapter()->getService();
            $return = null;
            switch ($type) {
                case "dir":
                    $parameters = [
                        "supportsAllDrives" => true,
                        "includeItemsFromAllDrives" => true,
                        "q" => "'$path' in parents and mimeType = 'application/vnd.google-apps.folder' and name = '$name' and trashed=false",
                    ];

                    $tmp = collect($service->files->listFiles($parameters))->whereNotNull('name')->first();
                    $return = (!empty($tmp->id) ? $tmp->id : null);

                    break;
                case 'file':
                    $parameters = [
                        "supportsAllDrives" => true,
                        "includeItemsFromAllDrives" => true,
                        "q" => "'$path' in parents and mimeType != 'application/vnd.google-apps.folder' and name = '$name' and trashed=false",
                    ];
                    $tmp = collect($service->files->listFiles($parameters))->whereNotNull('name')->first();

                    $return = (!empty($tmp->id) ? $tmp->id : null);

                    break;
                case 'files':
                    $parameters = [
                        "supportsAllDrives" => true,
                        "includeItemsFromAllDrives" => true,
                        "q" => "'$path' in parents and mimeType != 'application/vnd.google-apps.folder' and trashed=false",
                    ];

                    $return = collect($service->files->listFiles($parameters));

                    break;
                default:
                    $parameters = [
                        "supportsAllDrives" => true,
                        "includeItemsFromAllDrives" => true,
                        "q" => "'$path' in parents and trashed=false",
                    ];
                    $return = collect($service->files->listFiles($parameters));

            }

            if ($create && !$return)
                $return = self::add_folder($path, $name, $disk);

            return $return;

        } catch (\Exception $e) {
            Log::channel('stderr')->error('Google Drive search error: ' . $e->getMessage());
            return null;
        }

    }

    public static function add_folder($path, $name_folder, $disk = null, $search = false)
    {

        try {
            if (empty($disk))
                $disk = 'google';

            $service = Storage::disk($disk)->getAdapter()->getService();

            if ($search)
                $folderId = self::search(implode('/', $path), $disk, 'dir', $name_folder);

            if (!is_array($path))
                $path = array($path);

            if (empty($folderId)) {
                $fileMetadata = new \Google_Service_Drive_DriveFile(array(
                    'name' => $name_folder,
                    'parents' => $path,
                    'mimeType' => 'application/vnd.google-apps.folder'));

                $optParams = array('fields' => 'id', 'supportsTeamDrives' => true);
                $folderId = $service->files->create($fileMetadata, $optParams);

            }

            return (!empty($folderId['id']) ? $folderId['id'] : $folderId);

        } catch (\Exception $e) {
            Log::channel('stderr')->error('Google Drive add_folder error: ' . $e->getMessage());
            return false;
        }
    }

    public static function add_file($path, $name_file, $request = null, $returnId = false, $disk = null)
    {
        if (!empty($request->file)) {
            $filename = $name_file . '.' . $request->file->extension();
            $file = file_get_contents($request->file);
            $ext = $request->file->extension();
        } else {
            $filename = $name_file;
            $tmp = explode(".", $filename);
            $ext = end($tmp);
            $file = file_get_contents($request);
        }

        if (empty($disk))
            $disk = 'google';

        $service = Storage::disk($disk)->getAdapter()->getService();

        switch (strtolower($ext)) {
            case "xlsx":
                $mimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            case "xls":
                $mimeType = 'application/vnd.ms-excel';
                break;
            case "pdf":
                $mimeType = 'application/pdf';
                break;
            default:
                $mimeType = 'image/jpeg/png';
        }

        if (!is_array($path))
            $path = array($path);


        try {
            $fileMetadata = new \Google_Service_Drive_DriveFile(array(
                'name' => $filename,
                'parents' => $path
            ));

            $fileId = $service->files->create($fileMetadata, array(
                'data' => $file,
                'mimeType' => $mimeType,
                'uploadType' => 'multipart',
                'supportsTeamDrives' => true,
                'fields' => 'id'));


            if ($returnId)
                return $fileId['id'];

        } catch (\Exception $e) {
            Log::channel('stderr')->error('Google Drive add_file error: ' . $e->getMessage());
            return false;
        }
    }

    public static function rename_dir($path, $new_name, $disk = null)
    {
        if (empty($disk))
            $disk = 'google';
        $service = Storage::disk($disk)->getAdapter()->getService();
        $file = new Google_Service_Drive_DriveFile();
        $file->setName($new_name);
        $service->files->update($path, $file, array('fields' => 'name', "supportsAllDrives" => true));
    }

    public static function download($fileId, $disk = null){
        try {

            if (empty($disk))
                $disk = 'google';
            $service = Storage::disk($disk)->getAdapter()->getService();
            $response = $service->files->get($fileId, array(
                'alt' => 'media'));
            $content = $response->getBody()->getContents();
            return $content;

        } catch(Exception $e) {
            Log::channel('stderr')->error('Google Drive download error: ' . $e->getMessage());
        }

    }

    public static function shortcut($id_file, $path_destination, $name, $disk)
    {
        if (empty($disk))
            $disk = 'google';
        $service = Storage::disk($disk)->getAdapter()->getService();
        $file = new \Google_Service_Drive_DriveFile();
        $file->setName($name);
        $file->setParents([$path_destination]);
        $file->setMimeType('application/vnd.google-apps.shortcut');

        $shortcutDetails = new \Google_Service_Drive_DriveFileShortcutDetails();
        $shortcutDetails->setTargetId($id_file);
        $file->setShortcutDetails($shortcutDetails);

        $createdFile = $service->files->create($file);

        return $createdFile->id;

    }

    public static function move($fileId, $newParentId)
    {
        $service = Storage::disk('google')->getAdapter()->getService();
        $emptyFileMetadata = new \Google_Service_Drive_DriveFile();
        // Retrieve the existing parents to remove
        $file = $service->files->get($fileId, array('fields' => 'id, parents',"supportsAllDrives" => true));
        $previousParents = join(',', $file->parents);

        // Move the file to the new folder
        $file = $service->files->update($fileId, $emptyFileMetadata, array(
            'addParents' => $newParentId,
            'removeParents' => $previousParents,
            'fields' => 'id, parents', 'supportsAllDrives' => true));
    }

    public static function spredSheet()
    {
        if (empty($disk))
            $disk = 'google';
        $service = Storage::disk($disk)->getAdapter()->getService();

        $spreadsheet = new Google_Service_Sheets_Spreadsheet([
            'properties' => [
                'title' => 'Prova'
            ]
        ]);
        $spreadsheet = $service->spreadsheets->create($spreadsheet, [
            'fields' => 'spreadsheetId'
        ]);

        return $spreadsheet;
    }

    public static function shared($id, $email, $role = 'reader')
    {
        try {
            //owner	organizer	fileOrganizer	writer	commenter	reader
            $service = Storage::disk('google')->getAdapter()->getService();
            $service->permissions->delete();
            $service->permissions->create();
            $service->permissions->listPermissions();
            $userEmail = $email;
            $fileId = $id;

            $userPermission = new Google_Service_Drive_Permission(array(
                'type' => 'user',
                'role' => $role,
                'emailAddress' => $userEmail,

            ));

            $request = $service->permissions->create(
                $fileId, $userPermission, array('fields' => 'id', 'supportsAllDrives' => true, 'sendNotificationEmails' => false)
            );

            return $request;
        } catch (\Exception $e) {

        }
    }

    public static function removeShared($idFolder, $email)
    {
        try {
            $service = Storage::disk('google')->getAdapter()->getService();

            $idPermission = self::getIdPermission($idFolder, $email);

            if($idPermission)
                $service->permissions->delete($idFolder, $idPermission, array('supportsAllDrives' => true));
        } catch (\Exception $e) {

        }
    }

    private static function getIdPermission($idFolder, $email)
    {
        $idPermission = NULL;
        $service = Storage::disk('google')->getAdapter()->getService();
        $optParams = array(
            'fields' => '*',
            'supportsAllDrives' => true
        );
        $results = $service->permissions->listPermissions($idFolder, $optParams);

        foreach ($results->getPermissions() as $kk => $users){
            $tmp = (array)$users;
            if($tmp['emailAddress'] == $email)
                $idPermission = $tmp['id'];
        }

        return $idPermission;
    }

    public static function delated($path, $disk)
    {
        if (empty($disk))
            $disk = 'google';
        $service = Storage::disk($disk)->getAdapter()->getService();

        $service->files->delete($path, array("supportsTeamDrives" => true));
    }

}
