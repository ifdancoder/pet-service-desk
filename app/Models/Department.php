<?php

namespace App\Models;

use Database\Factories\DepartmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

#[Fillable(['name', 'slug'])]
class Department extends Model
{
    /** @use HasFactory<DepartmentFactory> */
    use HasFactory;

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function staffUsers(): Collection
    {
        $permissionNames = Permission::query()
            ->whereIn('name', ['ticket.view-team', 'ticket.view-all'])
            ->where('guard_name', config('auth.defaults.guard'))
            ->pluck('name')
            ->all();

        if ($permissionNames === []) {
            return User::query()->whereRaw('1 = 0')->get();
        }

        return $this->users()->permission($permissionNames)->get();
    }
}
