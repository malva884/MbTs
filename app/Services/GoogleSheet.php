<?php

namespace App\Services;

use Google\Service;
use Google_Service_Sheets_Spreadsheet;
use Revolution\Google\Sheets\Facades\Sheets;

class GoogleSheet
{
    public Service $service;

    function __construct()
    {

    }

    public static function createSheet($nome, $path)
    {
        $spreadsheet = new Google_Service_Sheets_Spreadsheet([
            'properties' => [
                'title' => $nome,
                'locale' => 'it_IT',
            ]
        ]);

        $service = Sheets::getService();

        $spreadsheet = $service->spreadsheets->create($spreadsheet, [
            'fields' => 'spreadsheetId',
        ]);

        GoogleDrive::move($spreadsheet->spreadsheetId, $path);

        return $spreadsheet->spreadsheetId;
    }

}
