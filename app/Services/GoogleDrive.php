<?php

namespace App\Services;

use Google\Service;
use Google_Service_Drive_DriveFile;
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

    public static function search($path, $disk, $type, $name, $create = false)
    {
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
                return $fileId;

        } catch (\Exception $e) {

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

    public static function delated($path, $disk)
    {
        if (empty($disk))
            $disk = 'google';
        $service = Storage::disk($disk)->getAdapter()->getService();

        $service->files->delete($path, array("supportsAllDrives" => true));

    }

}
