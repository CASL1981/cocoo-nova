<?php

namespace App\Models;

use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    protected $fillable = ['name', 'guard_name'];

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, fn (Builder $q) => $q->where(function (Builder $q) use ($term) {
            $q->where('name', 'like', "%{$term}%");
        }));
    }

    /**
     * Usuarios cuyo rol primario (users.role_id) es este rol.
     *
     * @return HasMany<User, $this>
     */
    public function assignedUsers(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }
}
