<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Clase que representa un permiso en el sistema.
 * Los permisos definen acciones específicas que pueden ser realizadas en el sistema.
 *
 * @property string $name Nombre único del permiso
 * @property string $description Descripción del propósito del permiso
 * @property string $guard_name Nombre del guard de autenticación (por defecto 'web')
 */
class Permission extends Model
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
     * Obtiene los roles que tienen este permiso.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_has_permissions');
    }

    /**
     * Obtiene los usuarios que tienen este permiso directamente asignado.
     * Utiliza una relación polimórfica para permitir asignar permisos a diferentes tipos de modelos.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function users(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'model', 'model_has_permissions');
    }

    /**
     * Asigna uno o más roles a este permiso.
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
     * Remueve uno o más roles de este permiso.
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
}