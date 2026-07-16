<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Google Drive Folder IDs
            [
                'key' => 'google_drive_fai_folder_id',
                'value' => env('ID_GOOGLE_DRIVE_FAI', '1hnh89VGGEHOVd5fx-aPnTJIDtsADnefG'),
                'type' => 'string',
                'group' => 'google',
                'description' => 'Google Drive Folder ID for FAI',
            ],
            [
                'key' => 'google_drive_nc_giornaliere_folder_id',
                'value' => env('ID_GOOGLE_NC_GIORNALIENRE', '10ao7bg4H5_BIWfDD_EDwE2SzVOTjckHO'),
                'type' => 'string',
                'group' => 'google',
                'description' => 'Google Drive Folder ID for NC Giornaliere',
            ],
            [
                'key' => 'google_drive_magazzini_folder_id',
                'value' => env('ID_GOOGLE_MAGAZZINI', '0AJw5ImqlBEwwUk9PVA'),
                'type' => 'string',
                'group' => 'google',
                'description' => 'Google Drive Folder ID for Magazzini',
            ],
            [
                'key' => 'google_drive_dipendenti_folder_id',
                'value' => env('ID_GOOGLE_DIPENDENTI', '1LQ8Pw4zkaRqfHbEdb98IJOQ_ndj6x9hl'),
                'type' => 'string',
                'group' => 'google',
                'description' => 'Google Drive Folder ID for Dipendenti',
            ],
            [
                'key' => 'google_drive_fornitori_folder_id',
                'value' => env('ID_GOOGLE_FORNITORI', '0APoNRbX0s4H6Uk9PVA'),
                'type' => 'string',
                'group' => 'google',
                'description' => 'Google Drive Folder ID for Fornitori',
            ],
            [
                'key' => 'google_drive_product_codes_folder_id',
                'value' => env('ID_GOOGLE_PRODUCT_CODES', '1ahSiHd_pQBdFp2r5Xe0TJFWaeNO571L'),
                'type' => 'string',
                'group' => 'google',
                'description' => 'Google Drive Folder ID for Product Codes',
            ],
            [
                'key' => 'google_drive_task_folder_id',
                'value' => env('ID_GOOGLE_TASK', '1wVcMrBwOrvIs6v7fUGsklubyEb3VfGww'),
                'type' => 'string',
                'group' => 'google',
                'description' => 'Google Drive Folder ID for Tasks',
            ],
            [
                'key' => 'google_drive_ddt_folder_id',
                'value' => env('ID_GOOGLE_DDT', '1RqUBeM-1--mqEJ9HSHZ_34XyIeg6sE3i'),
                'type' => 'string',
                'group' => 'google',
                'description' => 'Google Drive Folder ID for DDT',
            ],
            [
                'key' => 'google_drive_ddc_folder_id',
                'value' => env('ID_GOOGLE_DDC', '1Vf92X8ycEC4jt1gvgauYrO3fDqjDRGI6'),
                'type' => 'string',
                'group' => 'google',
                'description' => 'Google Drive Folder ID for DDC',
            ],
            [
                'key' => 'google_drive_commesse_folder_id',
                'value' => env('ID_GOOGLE_COMMESSE', ''),
                'type' => 'string',
                'group' => 'google',
                'description' => 'Google Drive Folder ID for Commesse',
            ],
            [
                'key' => 'google_drive_documenti_folder_id',
                'value' => env('ID_GOOGLE_DOCUMENTI', ''),
                'type' => 'string',
                'group' => 'google',
                'description' => 'Google Drive Folder ID for Documenti',
            ],
            [
                'key' => 'google_drive_team_drive_id',
                'value' => env('GOOGLE_DRIVE_TEAM_DRIVE_ID', '0ACAKB3BTsJr1Uk9PVA'),
                'type' => 'string',
                'group' => 'google',
                'description' => 'Google Drive Team Drive ID',
            ],

            // Google Service Settings
            [
                'key' => 'google_sheet_service_enabled',
                'value' => env('GOOGLE_SHEET_SERVICE_ENABLED', 'true'),
                'type' => 'boolean',
                'group' => 'google',
                'description' => 'Enable Google Sheet Service',
            ],
            [
                'key' => 'google_redirect_uri',
                'value' => env('GOOGLE_REDIRECT_URI', 'https://portale.metallurgicabresciana.it/api/auth2/google/callback'),
                'type' => 'string',
                'group' => 'google',
                'description' => 'Google OAuth Redirect URI',
            ],
            [
                'key' => 'google_drive_verify_ssl',
                'value' => env('GOOGLE_DRIVE_VERIFY_SSL', 'true'),
                'type' => 'boolean',
                'group' => 'google',
                'description' => 'Verify SSL for Google Drive requests',
            ],

            // System Settings
            [
                'key' => 'session_lifetime',
                'value' => env('SESSION_LIFETIME', '120'),
                'type' => 'integer',
                'group' => 'system',
                'description' => 'Session lifetime in minutes',
            ],
            [
                'key' => 'log_level',
                'value' => env('LOG_LEVEL', 'error'),
                'type' => 'string',
                'group' => 'system',
                'description' => 'Application log level',
            ],
            [
                'key' => 'app_version',
                'value' => env('APP_MAJOR', '1.4.0'),
                'type' => 'string',
                'group' => 'system',
                'description' => 'Application version',
            ],
            [
                'key' => 'mail_from_address',
                'value' => env('MAIL_FROM_ADDRESS', 'portale.metallurgica@stl.tech'),
                'type' => 'string',
                'group' => 'mail',
                'description' => 'Default email from address',
            ],
            [
                'key' => 'mail_from_name',
                'value' => env('MAIL_FROM_NAME', 'Portale'),
                'type' => 'string',
                'group' => 'mail',
                'description' => 'Default email from name',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('Settings seeded successfully.');
    }
}
