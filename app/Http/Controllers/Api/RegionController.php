<?php

namespace App\Http\Controllers\Api;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegionController extends Controller
{
    use ApiResponseTrait;

    /**
     * Lista las regiones, opcionalmente filtradas por name/status.
     */
    public function index(Request $request)
    {
        $regions = Region::filter($request)->get();

        return $this->success($regions, 'Regiones encontradas.');
    }

    /**
     * Consulta una región por su id.
     */
    public function show($id)
    {
        $region = Region::find($id);

        if (! $region || $region->status === Status::Trash) {
            return $this->error('Región no existe', 404);
        }

        return $this->success($region, 'Región encontrada.');
    }

    /**
     * Registra una región nueva. El nombre único ya se valida en el
     * middleware validate.region.store antes de llegar aquí.
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $region = Region::create([
                'name' => $request->name,
                'description' => $request->description,
                'status' => Status::Active->value,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->error('Error interno al registrar la región.', 500, $e->getMessage());
        }

        return $this->success($region, 'Región registrada correctamente.', 201);
    }

    /**
     * Actualiza los datos de una región existente identificada por su id.
     */
    public function update(Request $request, $id)
    {
        $region = Region::find($id);

        if (! $region || $region->status === Status::Trash) {
            return $this->error('Región no existe', 404);
        }

        try {
            DB::beginTransaction();

            $region->update($request->only(['name', 'description', 'status']));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->error('Error interno al actualizar la región.', 500, $e->getMessage());
        }

        return $this->success($region, 'Región actualizada correctamente.');
    }

    /**
     * Elimina lógicamente una región identificada por su id (la pasa a status trash).
     */
    public function destroy($id)
    {
        $region = Region::find($id);

        if (! $region || $region->status === Status::Trash) {
            return $this->error('Región no existe', 404);
        }

        try {
            DB::beginTransaction();

            $region->status = Status::Trash;
            $region->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->error('Error interno al eliminar la región.', 500, $e->getMessage());
        }

        return $this->success(null, 'Región eliminada correctamente.');
    }

    /**
     * Recupera (reactiva) una región identificada por su id que estaba en trash.
     */
    public function restore($id)
    {
        $region = Region::find($id);

        if (! $region) {
            return $this->error('Región no existe', 404);
        }

        if ($region->status !== Status::Trash) {
            return $this->error('La región no está eliminada, no es necesario recuperarla.', 422);
        }

        try {
            DB::beginTransaction();

            $region->status = Status::Active;
            $region->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->error('Error interno al recuperar la región.', 500, $e->getMessage());
        }

        return $this->success($region, 'Región recuperada correctamente.');
    }
}
