<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    protected $fillable = [
        'name',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => Status::class,
        ];
    }

    public function communes(): HasMany
    {
        return $this->hasMany(Commune::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', Status::Active->value);
    }

    public function scopeFilter($query, $request)
    {
        return $query
            ->when($request->name, function ($query, $name) {
                return $query->where('name', 'like', "%{$name}%");
            })
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            });
    }
}
