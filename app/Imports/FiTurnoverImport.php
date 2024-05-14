<?php

namespace App\Imports;

use App\Models\FiTurnoverRow;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;

class FiTurnoverImport implements ToModel, WithHeadingRow
{
    // WithHeadingRow
    private $head = null;
    public $result = ['targhet_cc' => 0, 'targhet_ofc' => 0, 'targhet_fkm' => 0, 'targhet_ckm' => 0,'targhet_ofc_ckm'=>0, 'check' => false];

    public function __construct($headId)
    {
        $this->head = $headId;

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

        if(!empty($row['document_date'])){
            // converto la data excel
            $unix_date = ($row['document_date'] - 25569) * 86400;
            $excel_date = 25569 + ($unix_date / 86400);
            $unix_date = ($excel_date - 25569) * 86400;
            $document_date = gmdate("Y-m-d", $unix_date);
            // converto la data excel
            $unix_date = ($row['posting_date'] - 25569) * 86400;
            $excel_date = 25569 + ($unix_date / 86400);
            $unix_date = ($excel_date - 25569) * 86400;
            $posting_date = gmdate("Y-m-d", $unix_date);

            //$value = str_replace("-", "", $row['amount_in_local_currency']);
            $value = str_replace(",", ".", $row['amount_in_local_currency']);

            //$quantita = str_replace("-", "", $row['quantity']);
            $quantita = str_replace(",", ".", $row['quantity']);
            $matariale = $row['assignment'];

            $value = number_format($value, 2, '.', '');
            if($quantita == 0)
                $quantita = 0.00;
            $ckm = 0;
            $fkm = 0;
            // se il cavo e rame
            if ($row['business_area'] == '5441') {
                // sommo il valore fatturato rame
                $this->result['targhet_cc'] += $value;
                // se l
                if ($row['base_unit_of_measure'] == 'M') {
                    $ckm = round($quantita / 1000, 3);
                    $this->result['targhet_ckm'] += $ckm;
                } elseif ($row['base_unit_of_measure'] == 'KM') {
                    $ckm = $quantita;
                    $this->result['targhet_ckm'] += $quantita;
                }
            } elseif ($row['business_area'] == '5420') {
                $this->result['targhet_ofc'] += $value;
                $numeroFibre = substr($matariale, 7, 4);
                if ($row['base_unit_of_measure'] == 'M') {
                    if($numeroFibre > 0)
                        $fkm = round(($quantita / 1000) * $numeroFibre, 3);
                    else
                        $fkm = round($quantita / 1000, 3);

                    $this->result['targhet_fkm'] += $fkm;
                    $ckm = round($quantita / 1000, 3);
                    $this->result['targhet_ofc_ckm'] += $ckm;
                } elseif ($row['base_unit_of_measure'] == 'KM') {
                    if(is_numeric($numeroFibre) && is_numeric($quantita))
                        $fkm = round($numeroFibre * $quantita, 3);
                    else
                        $fkm = $quantita;

                    $this->result['targhet_fkm'] += $fkm;
                    $ckm = $quantita;
                    $this->result['targhet_ofc_ckm'] += $quantita;
                }
            }
            $numeroDocuemto = substr( $row['document_number'], 0, 3);
            $country = '';
            switch ($numeroDocuemto) {
                case '191':
                case '194':
                    $country='ITA';
                    break;
                case '192':
                    $country='UE';
                    break;
                case '193':
                    $country='EX-UE';
                    break;
            }

            if(!$this->result['check'] && $quantita == 0.000 && !in_array($row['account'],['404000','452100','452000']) && !in_array($row['document_type'],['M8','M9','V8','V9']))
                $this->result['check'] = true;


            return new FiTurnoverRow([
                'head' => $this->head,
                'account' => $row['account'],
                'data_documento' => $document_date,
                'quantita' => $quantita,
                'unit' => $row['base_unit_of_measure'],
                'materiale' => $matariale,
                'importo_valuta_locale' => $value,
                'documento_numero' => $row['document_number'],
                'documento_tipo' => $row['document_type'],
                'cliente' => $row['name_of_offsetting_account'],
                'tipologia_cavo' => $row['business_area'],
                'data_publicazione' => $posting_date,
                'chiave_publicazione' => $row['posting_key'],
                'valuta_locale' => $row['local_currency'],
                'tax_code' => $row['tax_code'],
                'account_tipo' => $row['offsettaccount_type'],
                'codice_cliente' => $row['offsetting_acct_no'],
                'paese' => $country,
                'ckm' => $ckm,
                'fkm' => $fkm
            ]);
        }
    }

}
