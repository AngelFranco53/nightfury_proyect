<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Clase que representa un rol en el sistema.
 * Los roles son utilizados para agrupar permisos y asignarlos a usuarios.
 *
 * @property string $name Nombre único del rol
 * @property string $description Descripción del propósito del rol
 * @property string $guard_name Nombre del guard de autenticación (por defecto 'web')
 */
class Role extends Model
{
    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'description',
        'guard_name'
    ];

    /**
     * Obtiene los permisos asociados a este rol.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_has_permissions');
    }

    /**
     * Obtiene los usuarios que tienen asignado este rol.
     * Utiliza una relación polimórfica para permitir asignar roles a diferentes tipos de modelos.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function users(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'model', 'model_has_roles');
    }

    /**
     * Verifica si el rol tiene un permiso específico.
     *
     * @param string|Permission $permission Nombre del permiso o instancia de Permission
     * @return bool True si tiene el permiso, false en caso contrario
     */
    public function hasPermissionTo(string|Permission $permission): bool
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->first();
        }

        return $this->permissions()->where('id', $permission->id)->exists();
    }

    /**
     * Asigna uno o más permisos al rol.
     * Si el permiso ya está asignado, no se duplicará.
     *
     * @param string|Permission ...$permissions Nombres de permisos o instancias de Permission
     * @return self
     */
    public function givePermissionTo(string|Permission ...$permissions): self
    {
        $permissionIds = collect($permissions)->map(function ($permission) {
            if (is_string($permission)) {
                return Permission::where('name', $permission)->first()->id;
            }
            return $permission->id;
        });

        $this->permissions()->syncWithoutDetaching($permissionIds);

        return $this;
    }

    public function revokePermissionTo(string|Permission ...$permissions): self
    {
        $permissionIds = collect($permissions)->map(function ($permission) {
            if (is_string($permission)) {
                return Permission::where('name', $permission)->first()->id;
            }
            return $permission->id;
        });

        $this->permissions()->detach($permissionIds);

        return $this;
    }
}