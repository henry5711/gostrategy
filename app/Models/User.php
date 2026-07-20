<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Status;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['dni', 'region_id', 'commune_id', 'name', 'last_name', 'email', 'address', 'date_reg', 'status', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, PasskeyAuthenticatable, SoftDeletes, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'date_reg' => 'datetime',
            'status' => Status::class,
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


    public function scopeFilter($query, $request)
    {
        return $query
            ->when($request->user, function ($query) use ($request) {
                $query->where(function ($query) use ($request) {
                    $query
                        ->where('dni', $request->user)
                        ->orWhere('email', $request->user);
                });
            })
            ->when($request->dni, fn ($query, $dni) => $query->where('dni', $dni))
            ->when($request->email, fn ($query, $email) => $query->where('email', $email))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->when($request->region_id, fn ($query, $regionId) => $query->where('region_id', $regionId))
            ->when($request->commune_id, fn ($query, $communeId) => $query->where('commune_id', $communeId))
            ->when($request->name, fn ($query, $name) => $query->where('name', $name))
            ->when($request->last_name, fn ($query, $lastName) => $query->where('last_name', $lastName));
    }
}
