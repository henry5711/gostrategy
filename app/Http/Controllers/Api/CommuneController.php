<?php

namespace App\Http\Controllers\Api;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Http\Requests\Commune\StoreCommuneRequest;
use App\Http\Requests\Commune\UpdateCommuneRequest;
use App\Http\Resources\CommuneResource;
use App\Models\Commune;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommuneController extends Controller
{
    /**
     * Lista Communes.
     */
    public function index(Request $request)
    {
        $Communes = Commune::with(['region'])
            ->when($request->name, fn ($query, $name) => $query->where('name', 'like', "%{$name}%"))
            ->when($request->region_id, fn ($query, $regionId) => $query->where('region_id', $regionId))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Communes encontrados.',
            'data' => CommuneResource::collection($Communes),
        ]);
    }

    /**
     * Consulta un Commune por su id.
     */
    public function show($id)
    {
        $Commune = Commune::with(['region'])->find($id);

        if (! $Commune) {
            return response()->json(['success' => false, 'message' => 'Commune no encontrado.', 'data' => null], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Commune encontrado.',
            'data' => new CommuneResource($Commune),
        ]);
    }

    /**
     * Registra un Commune nuevo.
     */
    public function store(StoreCommuneRequest $request)
    {
        try {
            DB::beginTransaction();

            $Commune = Commune::create([
                'region_id' => $request->region_id,
                'name' => $request->name,
                'description' => $request->description,
                'status' => Status::Active->value,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Error interno al registrar el Commune.', 'data' => $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Commune registrado correctamente.',
            'data' => new CommuneResource($Commune->load('region')),
        ], 201);
    }

    /**
     * Edita los datos de un Commune existente
     */
    public function update(UpdateCommuneRequest $request, $id)
    {
        $Commune = Commune::find($id);

        if (! $Commune) {
            return response()->json(['success' => false, 'message' => 'Registro no existe', 'data' => null], 404);
        }

        try {
            DB::beginTransaction();

            $Commune->update($request->only(['region_id', 'name', 'description', 'status']));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Error interno al editar el Commune.', 'data' => $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Commune actualizado correctamente.',
            'data' => new CommuneResource($Commune->load('region')),
        ]);
    }

    /**
     * Elimina un Commune
     */
    public function destroy($id)
    {
        $Commune = Commune::find($id);

        if (! $Commune) {
            return response()->json(['success' => false, 'message' => 'Registro no existe', 'data' => null], 404);
        }

        if ($Commune->users()->withTrashed()->exists()) {
            return response()->json(['success' => false, 'message' => 'No se puede eliminar el Commune porque tiene usuarios asociados.', 'data' => null], 422);
        }

        try {
            $Commune->delete();
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error interno al eliminar el Commune.', 'data' => $e->getMessage()], 500);
        }

        return response()->json(['success' => true, 'message' => 'Commune eliminado correctamente.', 'data' => null]);
    }
}
