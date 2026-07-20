<?php

namespace App\Http\Controllers\Api;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Http\Requests\Region\StoreRegionRequest;
use App\Http\Requests\Region\UpdateRegionRequest;
use App\Http\Resources\RegionResource;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegionController extends Controller
{
    /**
     * Lista Regions.
     */
    public function index(Request $request)
    {
        $Regions = Region::query()
            ->when($request->name, fn ($query, $name) => $query->where('name', 'like', "%{$name}%"))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Regions encontrados.',
            'data' => RegionResource::collection($Regions),
        ]);
    }

    /**
     * Consulta un Region por su id.
     */
    public function show($id)
    {
        $Region = Region::find($id);

        if (! $Region) {
            return response()->json(['success' => false, 'message' => 'Region no encontrado.', 'data' => null], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Region encontrado.',
            'data' => new RegionResource($Region),
        ]);
    }

    /**
     * Registra un Region nuevo.
     */
    public function store(StoreRegionRequest $request)
    {
        try {
            DB::beginTransaction();

            $Region = Region::create([
                'name' => $request->name,
                'description' => $request->description,
                'status' => Status::Active->value,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Error interno al registrar el Region.', 'data' => $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Region registrado correctamente.',
            'data' => new RegionResource($Region),
        ], 201);
    }

    /**
     * Edita los datos de un Region existente
     */
    public function update(UpdateRegionRequest $request, $id)
    {
        $Region = Region::find($id);

        if (! $Region) {
            return response()->json(['success' => false, 'message' => 'Registro no existe', 'data' => null], 404);
        }

        try {
            DB::beginTransaction();

            $Region->update($request->only(['name', 'description', 'status']));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Error interno al editar el Region.', 'data' => $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Region actualizado correctamente.',
            'data' => new RegionResource($Region),
        ]);
    }

    /**
     * Elimina un Region
     */
    public function destroy($id)
    {
        $Region = Region::find($id);

        if (! $Region) {
            return response()->json(['success' => false, 'message' => 'Registro no existe', 'data' => null], 404);
        }

        if ($Region->communes()->exists()) {
            return response()->json(['success' => false, 'message' => 'No se puede eliminar el Region porque tiene communes asociadas.', 'data' => null], 422);
        }

        try {
            $Region->delete();
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error interno al eliminar el Region.', 'data' => $e->getMessage()], 500);
        }

        return response()->json(['success' => true, 'message' => 'Region eliminado correctamente.', 'data' => null]);
    }
}
