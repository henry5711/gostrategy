<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    protected $fillable = [
        'dni',
        'region_id',
        'commune_id',
        'email',
        'password',
        'name',
        'last_name',
        'address',
        'date_reg',
        'status',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'status' => Status::class,
            'date_reg' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', Status::Active->value);
    }

    public function scopeFilter($query, $request)
    {
        return $query->where(function ($query) use ($request) {
            $query
                ->when($request->dni, function ($query, $dni) {
                    return $query->where('dni', $dni);
                })
                ->when($request->email, function ($query, $email) {
                    return $query->Where('email', $email);
                })
                  ->when($request->region_id, function ($query, $region_id) {
                    return $query->Where('region_id', $region_id);
                })
                  ->when($request->commune_id, function ($query, $commune_id) {
                    return $query->Where('commune_id', $commune_id);
                });
        });
    }
}
