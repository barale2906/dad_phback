<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Menú del sistema UniPH
    |--------------------------------------------------------------------------
    | Estructura del menú para el manejo del sistema. Cada ítem puede tener
    | 'roles' (array): si está vacío o no se define, todos los autenticados ven el ítem.
    | 'route' es la ruta del frontend (ej. /dashboard, /reuniones).
    | 'api' es la ruta API asociada para referencia.
    */
    'items' => [
        [
            'key' => 'ph',
            'label' => 'Propiedad horizontal',
            'route' => '/ph',
            'icon' => 'building-2',
            'api' => '/api/ph',
            'roles' => ['SUPER_ADMIN', 'ADMIN_PH', 'LOGISTICA'],
            'order' => 10,
        ],
        [
            'key' => 'inmuebles',
            'label' => 'Inmuebles',
            'route' => '/inmuebles',
            'icon' => 'home',
            'api' => '/api/inmuebles',
            'parent' => 'ph',
            'roles' => ['SUPER_ADMIN', 'ADMIN_PH', 'LOGISTICA'],
            'order' => 11,
        ],
        [
            'key' => 'zonas-comunes',
            'label' => 'Zonas comunes',
            'route' => '/zonas-comunes',
            'icon' => 'tree-pine',
            'api' => '/api/zonas-comunes',
            'parent' => 'ph',
            'roles' => ['SUPER_ADMIN', 'ADMIN_PH', 'LOGISTICA'],
            'order' => 12,
        ],
        [
            'key' => 'reuniones',
            'label' => 'Reuniones',
            'route' => '/reuniones',
            'icon' => 'calendar',
            'api' => '/api/reuniones',
            'roles' => ['SUPER_ADMIN', 'ADMIN_PH', 'LOGISTICA', 'LECTURA'],
            'order' => 20,
        ],
        [
            'key' => 'codigos-barras',
            'label' => 'Códigos de barras',
            'route' => '/barcodes',
            'icon' => 'barcode',
            'api' => '/api/barcodes/print',
            'roles' => ['SUPER_ADMIN', 'ADMIN_PH', 'LOGISTICA'],
            'order' => 30,
        ],
        [
            'key' => 'configuracion',
            'label' => 'Configuración',
            'route' => '/configuracion',
            'icon' => 'settings',
            'roles' => ['SUPER_ADMIN'],
            'order' => 40,
        ],
        [
            'key' => 'config-usuarios',
            'label' => 'Usuarios',
            'route' => '/configuracion/usuarios',
            'icon' => 'users',
            'api' => '/api/users',
            'parent' => 'configuracion',
            'roles' => ['SUPER_ADMIN'],
            'order' => 41,
        ],
        [
            'key' => 'config-roles-permisos',
            'label' => 'Roles y permisos',
            'route' => '/configuracion/roles-permisos',
            'icon' => 'shield',
            'parent' => 'configuracion',
            'roles' => ['SUPER_ADMIN'],
            'order' => 42,
        ],
        [
            'key' => 'mi-perfil',
            'label' => 'Mi perfil',
            'route' => '/mi-perfil',
            'icon' => 'user',
            'api' => '/api/me',
            'roles' => [],
            'order' => 50,
        ],
    ],
];
