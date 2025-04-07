<?php

namespace App\Traits;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Trait que proporciona funcionalidad de roles y permisos a los modelos.
 * Este trait permite a cualquier modelo (típicamente User) tener roles y permisos asignados.
 *
 * Proporciona métodos para:
 * - Asignar y remover roles
 * - Asignar y revocar permisos directos
 * - Verificar si tiene roles o permisos específicos
 * - Gestionar las relaciones polimórficas con roles y permisos
 */
trait HasRolesAndPermissions
{
    /**
     * Obtiene los roles asignados al modelo.
     * Utiliza una relación polimórfica para permitir que cualquier modelo pueda tener roles.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function roles(): MorphToMany
    {
        return $this->morphToMany(Role::class, 'model', 'model_has_roles');
    }

    /**
     * Obtiene los permisos directamente asignados al modelo.
     * Estos son permisos adicionales a los que el modelo pueda tener a través de sus roles.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function permissions(): MorphToMany
    {
        return $this->morphToMany(Permission::class, 'model', 'model_has_permissions');
    }

    /**
     * Asigna uno o más roles al modelo.
     * Si el rol ya está asignado, no se duplicará.
     *
     * @param string|Role ...$roles Nombres de roles o instancias de Role
     * @return self
     */
    public function assignRole(string|Role ...$roles): self
    {
        $roleIds = collect($roles)->map(function ($role) {
            if (is_string($role)) {
                return Role::where('name', $role)->first()->id;
            }
            return $role->id;
        });

        $this->roles()->syncWithoutDetaching($roleIds);

        return $this;
    }

    /**
     * Remueve uno o más roles del modelo.
     *
     * @param string|Role ...$roles Nombres de roles o instancias de Role
     * @return self
     */
    public function removeRole(string|Role ...$roles): self
    {
        $roleIds = collect($roles)->map(function ($role) {
            if (is_string($role)) {
                return Role::where('name', $role)->first()->id;
            }
            return $role->id;
        });

        $this->roles()->detach($roleIds);

        return $this;
    }

    /**
     * Verifica si el modelo tiene al menos uno de los roles especificados.
     *
     * @param string|Role ...$roles Nombres de roles o instancias de Role
     * @return bool True si tiene al menos uno de los roles, false en caso contrario
     */
    public function hasRole(string|Role ...$roles): bool
    {
        foreach ($roles as $role) {
            if (is_string($role)) {
                $exists = $this->roles()->where('name', $role)->exists();
            } else {
                $exists = $this->roles()->where('id', $role->id)->exists();
            }

            if ($exists) {
                return true;
            }
        }

        return false;
    }

    /**
     * Asigna uno o más permisos directamente al modelo.
     * Estos permisos son adicionales a los que el modelo pueda tener a través de sus roles.
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

    /**
     * Revoca uno o más permisos directamente asignados al modelo.
     *
     * @param string|Permission ...$permissions Nombres de permisos o instancias de Permission
     * @return self
     */
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

    /**
     * Verifica si el modelo tiene un permiso específico.
     * El permiso puede ser otorgado directamente o a través de sus roles.
     *
     * @param string|Permission $permission Nombre del permiso o instancia de Permission
     * @return bool True si tiene el permiso, false en caso contrario
     */
    public function hasPermissionTo(string|Permission $permission): bool
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->first();
        }

        if ($this->permissions()->where('id', $permission->id)->exists()) {
            return true;
        }

        return $this->hasPermissionThroughRole($permission);
    }

    protected function hasPermissionThroughRole(Permission $permission): bool
    {
        foreach ($this->roles as $role) {
            if ($role->hasPermissionTo($permission)) {
                return true;
            }
        }

        return false;
    }
}