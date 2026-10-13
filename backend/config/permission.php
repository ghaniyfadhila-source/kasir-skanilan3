<?php

return [

    'models' => [

        'permission' => Spatie\Permission\Models\Permission::class,
        'role' => Spatie\Permission\Models\Role::class,

    ],

    'table_names' => [
        'roles' => 'roles',
        'permissions' => 'permissions',
        'model_has_permissions' => 'model_has_permissions',
        'model_has_roles' => 'model_has_roles',
        'role_has_permissions' => 'role_has_permissions',
    ],

    'column_names' => [
        'role_pivot_key' => 'role_id',
        'permission_pivot_key' => 'permission_id',
        'model_morph_key' => 'model_id',
        'team_foreign_key' => 'team_id',
    ],

    'register_permission_check_method' => function ($model) {
        return $model->hasPermissionTo(...);
    },

    'teams' => false,

    'cache' => [
        'expiration' => env('PERMISSION_CACHE_EXPIRATION', 86400),
        'key' => env('PERMISSION_CACHE_KEY', 'spatie.permission.cache'),
        'model' => env('PERMISSION_CACHE_MODEL', 'Spatie\Permission\PermissionRegistrar'),
    ],

];