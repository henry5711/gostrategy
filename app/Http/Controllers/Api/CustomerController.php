<?php

namespace App\Http\Controllers\Api;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    use ApiResponseTrait;

    /**
     * Consulta un customer activo por dni o email.
     */
    public function index(Request $request)
    {
        // Nunca debe devolver customers desactivados (I) ni eliminados (trash).
        $customer = Customer::active()->filter($request)->with(['region', 'commune'])->first();

        if (! $customer) {
            return $this->error('Customer no encontrado.', 404);
        }

        return $this->success([
            'name' => $customer->name,
            'last_name' => $customer->last_name,
            'address' => $customer->address,
            'region' => $customer->region->name,
            'commune' => $customer->commune->name,
        ], 'Customer encontrado.');
    }

    /**
     * Registra un customer nuevo. region_id/commune_id y dni/email únicos ya se validan
     * en el middleware validate.customer.store antes de llegar aquí.
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $customer = Customer::create([
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

            return $this->error('Error interno al registrar el customer.', 500, $e->getMessage());
        }

        return $this->success($customer->load(['region', 'commune']), 'Customer registrado correctamente.', 201);
    }

    /**
     * Actualiza los datos de un customer existente identificado por su id.
     */
    public function update(Request $request, $id)
    {
        $customer = Customer::find($id);

        if (! $customer || $customer->status === Status::Trash) {
            return $this->error('Registro no existe', 404);
        }

        try {
            DB::beginTransaction();

            $customer->update($request->only(['email', 'name', 'last_name', 'address', 'region_id', 'commune_id', 'status']));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->error('Error interno al actualizar el customer.', 500, $e->getMessage());
        }

        return $this->success($customer->load(['region', 'commune']), 'Customer actualizado correctamente.');
    }

    /**
     * Elimina lógicamente un customer identificado por su id (lo pasa a status trash).
     */
    public function destroy($id)
    {
        $customer = Customer::find($id);

        if (! $customer || $customer->status === Status::Trash) {
            return $this->error('Registro no existe', 404);
        }

        try {
            DB::beginTransaction();

            $customer->status = Status::Trash;
            $customer->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->error('Error interno al eliminar el customer.', 500, $e->getMessage());
        }

        return $this->success(null, 'Customer eliminado correctamente.');
    }

    /**
     * Cambia la contraseña de un customer identificado por su id, validando
     * previamente que la contraseña actual coincida.
     */
    public function changePassword(Request $request, $id)
    {
        $customer = Customer::find($id);

        if (! $customer || $customer->status === Status::Trash) {
            return $this->error('Registro no existe', 404);
        }

        if (! Hash::check($request->current_password, $customer->password)) {
            return $this->error('La contraseña actual no es correcta.', 422);
        }

        try {
            DB::beginTransaction();

            $customer->password = $request->password;
            $customer->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->error('Error interno al cambiar la contraseña.', 500, $e->getMessage());
        }

        return $this->success(null, 'Contraseña actualizada correctamente.');
    }

    /**
     * Recupera (reactiva) un customer identificado por su id que estaba en trash,
     * es decir, lo saca del trash y lo vuelve a dejar como activo (A).
     */
    public function restore($id)
    {
        $customer = Customer::find($id);

        if (! $customer) {
            return $this->error('Registro no existe', 404);
        }

        // Solo tiene sentido "recuperar" un customer que efectivamente está en trash.
        if ($customer->status !== Status::Trash) {
            return $this->error('El customer no está eliminado, no es necesario recuperarlo.', 422);
        }

        try {
            DB::beginTransaction();

            $customer->status = Status::Active;
            $customer->save();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->error('Error interno al recuperar el customer.', 500, $e->getMessage());
        }

        return $this->success($customer->load(['region', 'commune']), 'Customer recuperado correctamente.');
    }
}
