<?php

namespace App\Imports;

use App\Models\PlAsset;
use App\Models\PlWarehouse;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PlWarehouseImport implements ToModel, WithHeadingRow
{
    // WithHeadingRow

    public function __construct()
    {

    }

    public function startRow(): int
    {
        return 2;
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        if(!empty($row['asset_code'])){
            $asset = PlWarehouse::where('pn_interno',$row['serial_number'])->first();

            if(empty($asset->id)) {
                return new PlAsset([
                    'codice_asset' => $row['asset_code'],
                    'nazione' => $row['store_location'],
                    'stato' => $row['status'],
                    'condizione_asset' => $row['asset_condition'],
                    'utilizzo' => $row['allocated_to'],
                    'emp_id' => $row['emp_id'],
                    'utente' => $row['user_name'],
                    'email' => $row['e_mail_id'],
                    'data_allocazione' => $row['allocated_date'],
                    'scopo' => $row['purpose'],
                    'tipo_allocazione' => $row['allocation_type'],
                    'data_dismesso' => $row['deactivation_date'],
                    'motivazione_dismesso' => $row['reason_for_deactivation'],
                    'hostName' => $row['host_name'],
                    'nome_utente_effetivo' => $row['actual_user_name'],
                    'tipo_asset' => $row['asset_type'],
                    'cpu' => $row['cpu'],
                    'cpu_numero' => $row['cpu_count'],
                    'hdd_capienza' => $row['hdd_sizegb'],
                    'hdd_numero' => $row['hdd_quantitynumber'],
                    'fattura_dt' => explode(" ", $row['invoice_dt'])[0],
                    'fattura_numero' => $row['invoice_number'],
                    'ip_address' => $row['ip_address'],
                    //'ultima_data_allocazione' => $row['last_deallocation_date'],
                    'marca' => ucfirst(strtolower($row['manufacturemake'])),
                    'modello' => $row['model_details'],
                    'mause' => $row['mouse'],
                    'tipo_rete' => $row['network_type'],
                    'sistema_operativo' => $row['os_details'],
                    'ram_numero' => $row['ram_quantitynumber'],
                    'ram_memoria' => $row['ram_sizegb'],
                    'sap_codice_asset' => $row['sap_asset_code'],
                    'numero_seriale' => $row['serial_number'],
                    'fine_garanzia' => explode(" ", $row['warranty_end_dt'])[0],
                    //'ultima_data_allocazione' => $row[35],
                ]);
            }
            else{
                $asset->stato = $row['status'];
                $asset->utilizzo = $row['allocated_to'];
                $asset->condizione_asset = $row['asset_condition'];
                $asset->utente = $row['user_name'];
                $asset->email = $row['e_mail_id'];
                $asset->tipo_allocazione = $row['allocation_type'];
                $asset->data_dismesso = $row['deactivation_date'];
                $asset->motivazione_dismesso = $row['reason_for_deactivation'];
                $asset->nome_utente_effetivo = $row['actual_user_name'];
                $asset->hostName = $row['host_name'];
                $asset->sistema_operativo = $row['os_details'];
                $asset->ram_numero = $row['ram_quantitynumber'];
                $asset->ram_memoria = $row['ram_sizegb'];
                $asset->marca = ucfirst(strtolower($row['manufacturemake']));
                $asset->modello = $row['model_details'];
                $asset->mause = $row['mouse'];
                $asset->ip_address = $row['ip_address'];
                $asset->save();
            }

        }
    }

}
