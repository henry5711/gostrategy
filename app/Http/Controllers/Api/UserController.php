<?php

namespace App\Http\Controllers\Api;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class UserController extends Controller
{
    /**
     * Lista Users.
     */
    public function index(Request $request)
    {
        $Users = User::filter($request)->with(['region', 'commune'])->get();

        return response()->json([
            'success' => true,
            'message' => 'Users encontrados.',
            'data' => UserResource::collection($Users),
        ]);
    }

    /**
     * Consulta un User por su id.
     */
    public function show($id)
    {
        $User = User::with(['region', 'commune'])->find($id);

        if (! $User) {
            return response()->json(['success' => false, 'message' => 'User no encontrado.', 'data' => null], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'User encontrado.',
            'data' => new UserResource($User),
        ]);
    }

    /**
     * Registra un User nuevo.
     */
    public function store(StoreUserRequest $request)
    {
        try {
            DB::beginTransaction();

            $User = User::create([
                'dni' => $request->dni,
                'region_id' => $request->region_id,
                'commune_id' => $request->commune_id,
                'email' => $request->email,
                'password' => $request->password,
                'name' => $request->name,
                'last_name' => $request->last_name,
                'address' => $request->address,
                'date_reg' => now(),
                'status' => Status::Active->value,
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Error interno al registrar el User.', 'data' => $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'User registrado correctamente.',
            'data' => new UserResource($User->load(['region', 'commune'])),
        ], 201);
    }

    /**
     * Edita los datos de un User existente
     */
    public function update(UpdateUserRequest $request, $id)
    {
        $User = User::withTrashed()->find($id);

        if (! $User || $User->trashed()) {
            return response()->json(['success' => false, 'message' => 'Registro no existe', 'data' => null], 404);
        }

        try {
            DB::beginTransaction();

            $User->update($request->only(['email', 'name', 'last_name', 'address', 'region_id', 'commune_id','status']));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Error interno al editar el User.', 'data' => $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'User actualizado correctamente.',
            'data' => new UserResource($User->load(['region', 'commune'])),
        ]);
    }

    /**
     * Elimina lógicamente un User
     */
    public function destroy($id)
    {
        $User = User::withTrashed()->find($id);

        if (! $User || $User->trashed()) {
            return response()->json(['success' => false, 'message' => 'Registro no existe', 'data' => null], 404);
        }

        try {
            $User->delete();
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error interno al eliminar el User.', 'data' => $e->getMessage()], 500);
        }

        return response()->json(['success' => true, 'message' => 'User eliminado correctamente.', 'data' => null]);
    }

    /**
     * Recupera un User eliminado.
     */
    public function restore($id)
    {
        $User = User::withTrashed()->find($id);

        if (!$User) {
            return response()->json(['success' => false, 'message' => 'Registro no existe', 'data' => null], 404);
        }

        // Solo tiene sentido "recuperar" un User que efectivamente está en trash.
        if (! $User->trashed()) {
            return response()->json(['success' => false, 'message' => 'El User no está eliminado, no es necesario recuperarlo.', 'data' => null], 422);
        }

        try {
            $User->restore();
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error interno al recuperar el User.', 'data' => $e->getMessage()], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'User recuperado correctamente.',
            'data' => new UserResource($User->load(['region', 'commune'])),
        ]);
    }
}
