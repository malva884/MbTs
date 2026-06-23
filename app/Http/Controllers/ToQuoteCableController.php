<?php

namespace App\Http\Controllers;

use App\Models\ToCable;
use App\Models\ToQuoteCable;
use App\Models\ToQuoteCableStructure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ToQuoteCableController extends Controller
{
    public function list(Request $request, $id)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');

        if (empty($sortByName)) {
            $sortByName = 'posizione';
            $orderBy = 'asc';
        }

        $objs = ToQuoteCable::where('preventivo_id',$id)
            ->orderBy($sortByName, $orderBy)
            ->paginate(100);



        return response()->json($objs);
    }

    public function view($id)
    {
        $obj = ToQuoteCable::where('id', $id)->first();
        $obj->preventivo_obj;
        $obj->preventivo_obj->cliente_obj;

        return response()->json($obj);
    }

	public function stored(Request $request, $id)
	{
		// Avviamo la transazione: ogni operazione sul DB è temporanea fino al commit
		DB::beginTransaction();

		try {
			// 1. Controllo preventivo sull'esistenza del cavo base
			$cavo = ToCable::find($request->codice);
			if (!$cavo) {
				throw new \Exception("Cavo base con ID {$request->codice} non trovato.");
			}

			// AGGIORNATA LA SELECT: prendiamo anche gli identificativi delle tabelle in join per il controllo d'esistenza
			$struttura = DB::table('to_cable_structures')
				->select(
					'to_cable_structures.*', 
					'to_center_costs.costo as costoCentro', 
					'to_center_costs.centro as centro_verificato', // Campo di controllo macchina
					'to_materials.costo as costo_materia',
					'to_materials.materiale as materiale_verificato' // Campo di controllo materiale
				)
				->leftJoin('to_center_costs', 'to_center_costs.centro', '=', 'to_cable_structures.centro')
				->leftJoin('to_materials', 'to_materials.materiale', '=', 'to_cable_structures.materiale')
				->where('cavo_id', '=', $request->codice)
				->orderBy('posizione')
				->get();

			// 2. Creazione dell'oggetto Cavo nel Preventivo
			$obj = new ToQuoteCable();
			$obj->preventivo_id = $request->preventivo['id'] ?? null;
			$obj->cavo_id = $request->codice;
			$obj->codice = $cavo->codice;
			$obj->descrizione = $request->descrizione;
			$obj->scarto = $request->scarto;
			$obj->metri = $request->metri;
			$obj->diametro = $request->diametro;
			$obj->pezzatura = $request->pezzatura;
			
			$obj->bobina_id = $request->bobina['id'] ?? null;
			$obj->bobina = $request->bobina['bobina'] ?? null;
			
			// Evitiamo divisioni per zero sulla pezzatura
			$obj->bobina_numero = (!empty($obj->pezzatura)) ? ceil($obj->metri / $obj->pezzatura) : 0;
			
			$obj->peso = $request->bobina['peso'] ?? 0;
			$obj->m3 = $request->bobina['m3'] ?? 0;
			$obj->m3_totale = round(($request->m3 ?? 0) * $obj->bobina_numero, 2);
			$obj->totale_costo_bobine = round(($request->bobina['costo'] ?? 0) * $obj->bobina_numero, 4);
			$obj->costo_bobina = $request->bobina['costo'] ?? 0;
			$obj->posizione = $request->posizione;
			
			// Primo salvataggio (protetto da transazione)
			$obj->save();
			
			// 3. Riordinamento delle posizioni
			$check_posizione = true;
			if ($check_posizione) {
				$rows = ToQuoteCable::where('preventivo_id', $request->preventivo['id'])
					->orderBy('posizione', 'asc')
					->orderBy('created_at', 'desc')
					->get();
				
				$i = 0;
				foreach ($rows as $row) {
					$i++;
					if ($row->id != $obj->id && $row->posizione != $obj->posizione) {
						$row->posizione = $i;
						$row->save();
					} elseif ($row->id != $obj->id && $row->posizione == $obj->posizione) {
						$row->posizione = $i;
						$row->save();
					} else {
						$row->posizione = $i;
						$row->save();
						$i++;                    
					}
				}
			}

			// 4. Generazione della struttura del cavo nel preventivo
			foreach ($struttura as $row) {
				$obj_struct = new ToQuoteCableStructure();
				$obj_struct->cavo_id = $obj->id;
				$obj_struct->posizione = $row->posizione;
				$obj_struct->centro = $row->centro;
				$obj_struct->materiale = $row->materiale;
				$obj_struct->descrizione = $row->descrizione;
				$obj_struct->diametro = $row->diametro;
				$obj_struct->peso = $row->peso;
				$obj_struct->nota = $row->nota;
				$obj_struct->ordinata = $row->ordinata;
				$obj_struct->elementi = $row->elementi;
				
				// CONTROLLO ESISTENZA MACCHINA: Se c'è un centro nella struttura, ma la LEFT JOIN ha restituito NULL
				if (!empty($row->centro) && is_null($row->centro_verificato)) {
					throw new \Exception("La macchina/centro '{$row->centro}' non esiste più nell'anagrafica to_center_costs.");
				}

				// CONTROLLO ESISTENZA MATERIALE: Se c'è un materiale nella struttura, ma la LEFT JOIN ha restituito NULL
				if (!empty($row->materiale) && is_null($row->materiale_verificato)) {
					throw new \Exception("Il materiale '{$row->materiale}' non esiste più nell'anagrafica to_materials.");
				}

				// Assegnazione costi (se la macchina esiste ma costa 0, ora viene accettato correttamente)
				$obj_struct->costo_centro = $row->costoCentro ?? 0.00;
				$obj_struct->costo = $row->costo_materia ?? 0.00;
				
				if ($obj_struct->peso) {
					$obj_struct->costo_materia_prima = round(($obj_struct->peso * $obj_struct->costo) / 1000, 4);
				}
				
				// CONTROLLO VINCOLANTE SUI DATI DI PRODUZIONE
				// Se la macchina è presente nella riga (anche se a costo zero), ordinata ed elementi devono essere valorizzati
				if (!empty($row->centro)) {
					if (empty($row->ordinata) || empty($row->elementi)) {
						throw new \Exception("Dati incompleti per il centro {$row->centro}: 'ordinata' o 'elementi' mancanti a fronte di una macchina presente.");
					}

					// Calcoli protetti
					$obj_struct->ore_macchina = round((($obj->metri / $row->ordinata) * $obj_struct->elementi) / 1000, 2);
					$obj_struct->costo_lavorazione = round((($obj_struct->costo_centro / $row->ordinata) * $obj_struct->elementi) / 1000, 4);
				} else {
					$obj_struct->ore_macchina = 0;
					$obj_struct->costo_lavorazione = 0;
				}
				
				$obj_struct->save();
			}

			// 5. Calcolo dei totali finali
			$obj->calcola_totali();

			// Tutto è andato a buon fine: confermiamo le modifiche sul DB
			DB::commit();

			return response()->json([
				'success' => true,
				'message' => 'Messaggi.Cavo-Aggiunto',
				'color'   => 'success',
				'obj'     => $obj
			]);

		} catch (\Exception $e) {
			// Se scatta un qualsiasi errore (incluso il nostro throw), il DB torna allo stato iniziale
			DB::rollBack();

			// Logghiamo il motivo esatto del fallimento per controllo interno
			Log::error("Errore aggiunta cavo preventivo: " . $e->getMessage());

			// Frontend riceve il messaggio bloccante richiesto
			return response()->json([
				'success' => false,
				'message' => 'Messaggi.Cavo-Non-Aggiunto',
				'color'   => 'error',
				'details' => $e->getMessage()
			], 200);
		}
	}

	public function update(Request $request, $id, $cid)
    {
        DB::beginTransaction();

        try {
            $obj = ToQuoteCable::find($cid);

            $bobina = $request->bobina ?? [];
            $metriChanged = ($obj->metri != $request->metri);

            $obj->descrizione = $request->descrizione;
            $obj->scarto = $request->scarto;
            $obj->metri = $request->metri;
            $obj->diametro = $request->diametro;
            $obj->pezzatura = $request->pezzatura;
            $obj->bobina_id = $bobina['id'] ?? null;
            $obj->bobina = $bobina['bobina'] ?? null;
            $obj->bobina_numero = (!empty($obj->pezzatura) && $obj->pezzatura != 0)
                ? ceil($obj->metri / $obj->pezzatura)
                : 0;
            $obj->peso = $bobina['peso'] ?? 0;
            $obj->m3 = $bobina['m3'] ?? 0;
            $obj->m3_totale = round($obj->m3 * $obj->bobina_numero, 2);
            $obj->totale_costo_bobine = round(($bobina['costo'] ?? 0) * $obj->bobina_numero, 4);
            $obj->costo_bobina = $bobina['costo'] ?? 0;

            $check_posizione = false;
            if ($obj->posizione != $request->posizione) {
                $check_posizione = true;
            }

            $obj->posizione = $request->posizione;
            $obj->save();

            if ($check_posizione) {
                $rows = ToQuoteCable::where('preventivo_id', $id)
                    ->orderBy('posizione', 'asc')
                    ->orderBy('created_at', 'desc')
                    ->get();

                $currentIndex = $rows->search(function ($row) use ($cid) {
                    return $row->id == $cid;
                });

                $currentRow = $rows->pull($currentIndex);
                $newRows = $rows->values();
                $newRows->splice((int) $request->posizione - 1, 0, [$currentRow]);

                $i = 1;
                foreach ($newRows as $row) {
                    $row->posizione = $i;
                    $row->save();
                    $i++;
                }
            }

            if ($metriChanged) {
                $structures = ToQuoteCableStructure::where('cavo_id', $cid)->get();
                foreach ($structures as $struct) {
                    if (!empty($struct->centro) && !empty($struct->ordinata) && $struct->ordinata != 0 && !empty($struct->elementi)) {
                        $struct->ore_macchina = round((($obj->metri / $struct->ordinata) * $struct->elementi) / 1000, 2);
                        $struct->save();
                    }
                }
            }

            $obj->calcola_totali();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Messaggi.Cavo-Modificato',
                'color' => 'success',
                'obj' => $obj
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Errore aggiornamento cavo preventivo: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Messaggi.Cavo-Non-Modificato',
                'color' => 'error',
                'details' => $e->getMessage()
            ], 200);
        }
    }
	
	public function deleted(Request $request, $pid, $cid)
    {
        $obj = ToQuoteCable::find($cid);
        $obj->delete();

        $objs = ToQuoteCable::where('preventivo_id', $pid)->orderby('posizione', 'asc')->get();

        $i = 1;
        foreach ($objs as $obj) {
            if ($obj->posizione == $request->posizione) {
                $i++;
                $obj->posizione = $i;
            } else {
                $obj->posizione = $i;
            }
            $obj->save();
            $i++;
        }

        $message = 'Messaggi.Cavo-Eliminato';
        $color = 'success';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => $color,
            ]);
    }
}
