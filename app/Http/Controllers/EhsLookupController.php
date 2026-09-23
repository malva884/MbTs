<?php

namespace App\Http\Controllers;

use App\Models\EhsAnatomical;
use App\Models\EhsCause;
use App\Models\EhsInjury;
use App\Models\EhsSite;
use App\Models\EhsTypeEvent;
use Illuminate\Http\Request;

/**
 * CRUD generico per le tabelle di supporto del modulo EHS:
 * sedi, cause, lesioni, sedi anatomiche, tipi evento.
 */
class EhsLookupController extends Controller
{
    /**
     * Mappa slug -> [modello, colonna nome].
     */
    private const TYPES = [
        'sedi' => [EhsSite::class, 'site'],
        'cause' => [EhsCause::class, 'causa'],
        'lesioni' => [EhsInjury::class, 'injurie'],
        'anatomiche' => [EhsAnatomical::class, 'anatomical'],
        'eventi' => [EhsTypeEvent::class, 'event'],
    ];

    private function resolve(string $type): array
    {
        abort_unless(isset(self::TYPES[$type]), 404, 'Tipo non valido');
        return self::TYPES[$type];
    }

    /**
     * Lista paginata con filtri.
     */
    public function list(Request $request, string $type)
    {
        [$model, $nameColumn] = $this->resolve($type);

        $sortByName = $request->get('sortBy') ?: $nameColumn;
        $orderBy = $request->get('orderBy') ?: 'asc';
        $nome = $request->get('nome');
        $disattivo = $request->get('disattivo');

        $query = $model::query()
            ->where(function ($q) use ($nome, $nameColumn) {
                if ($nome)
                    $q->where($nameColumn, 'like', '%' . $nome . '%');
            });

        if ($disattivo !== null && $disattivo !== '') {
            $query->where('disattivo', $disattivo == 1);
        }

        $objs = $query->orderBy($sortByName, $orderBy)
            ->paginate($request->itemsPerPage ?? 10, ['*'], 'page', $request->page ?? 1);

        return response()->json($objs);
    }

    /**
     * Lista completa (solo attivi) per le select dei form.
     */
    public function getList(string $type)
    {
        [$model, $nameColumn] = $this->resolve($type);

        $objs = $model::where('disattivo', false)
            ->orderBy($nameColumn)
            ->get(['id', $nameColumn]);

        return response()->json($objs);
    }

    public function store(Request $request, string $type)
    {
        [$model, $nameColumn] = $this->resolve($type);

        $request->validate(['nome' => 'required|string|max:255']);

        $obj = new $model();
        $obj->$nameColumn = ucwords(strtolower($request->nome));
        $obj->disattivo = (bool) $request->disattivo;
        $obj->save();

        return response()->json([
            'success' => true,
            'message' => 'Messaggi.Elemento-Salvato',
            'color' => 'success',
            'obj' => $obj,
        ]);
    }

    public function update(Request $request, string $type, $id)
    {
        [$model, $nameColumn] = $this->resolve($type);

        $request->validate(['nome' => 'required|string|max:255']);

        $obj = $model::findOrFail($id);
        $obj->$nameColumn = ucwords(strtolower($request->nome));
        $obj->disattivo = (bool) $request->disattivo;
        $obj->save();

        return response()->json([
            'success' => true,
            'message' => 'Messaggi.Elemento-Modificato',
            'color' => 'success',
            'obj' => $obj,
        ]);
    }

    /**
     * Soft-delete: marca come disattivo.
     */
    public function destroy(string $type, $id)
    {
        [$model] = $this->resolve($type);

        $obj = $model::find($id);
        if ($obj) {
            $obj->disattivo = true;
            $obj->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Messaggi.Elemento-Disattivato',
            'color' => 'success',
        ]);
    }
}
