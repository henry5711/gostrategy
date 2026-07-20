<?php

namespace App\Http\Controllers\Api;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Commune;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommuneController extends Controller
{
    use ApiResponseTrait;

    /**
     * Lista las communes, opcionalmente filtradas por name/region_id/status.
     */
    public function index(Request $request)
    {
        $communes = Commune::filter($request)->with('region')->get();

        return $this->success($communes, 'Communes encontradas.');
    }

    /**
     * Consulta una commune por su id.
     */
    public function show($id)
    {
        $commune = Commune::find($id);

        if (! $commune || $commune->status === Status::Trash) {
            return $this->error('Commune no existe', 404);
        }

        return $this->success($commune->load('region'), 'Commune encontrada.');
    }

    /**
     * Registra una commune nueva. region_id y el nombre único dentro de la
     * región ya se validan en el middleware validate.commune.store.
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $commune = Commune::create([
                'region_id' => $request->region_id,
                'name' => $request->name,
                'description' => $request->description,
                'status' => Status::Active->value,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->error('Error interno al registrar la commune.', 500, $e->getMessage());
        }

        return $this->success($commune->load('region'), 'Commune registrada correctamente.', 201);
    }

    /**
     * Actualiza los datos de una commune existente identificada por su id.
     */
    public function update(Request $request, $id)
    {
        $commune = Commune::find($id);

        if (! $commune || $commune->status === Status::Trash) {
            return $this->error('Commune no existe', 404);
        }

        try {
            DB::beginTransaction();

            $commune->update($request->only(['region_id', 'name', 'description', 'status']));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->error('Error interno al actualizar la commune.', 500, $e->getMessage());
        }

        return $this->success($commune->load('region'), 'Commune actualizada correctamente.');
    }

    /**
     * Elimina lógicamente una commune identificada por su id (la pasa a status trash).
     */
    public function destroy($id)
    {
        $commune = Commune::find($id);

        if (! $commune || $commune->status === Status::Trash) {
            return $this->error('Commune no existe', 404);
        }

        try {
            DB::beginTransaction();

            $commune->status = Status::Trash;
            $commune->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->error('Error interno al eliminar la commune.', 500, $e->getMessage());
        }

        return $this->success(null, 'Commune eliminada correctamente.');
    }

    /**
     * Recupera (reactiva) una commune identificada por su id que estaba en trash.
     */
    public function restore($id)
    {
        $commune = Commune::find($id);

        if (! $commune) {
            return $this->error('Commune no existe', 404);
        }

        if ($commune->status !== Status::Trash) {
            return $this->error('La commune no está eliminada, no es necesario recuperarla.', 422);
        }

        try {
            DB::beginTransaction();

            $commune->status = Status::Active;
            $commune->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->error('Error interno al recuperar la commune.', 500, $e->getMessage());
        }

        return $this->success($commune->load('region'), 'Commune recuperada correctamente.');
    }
}
