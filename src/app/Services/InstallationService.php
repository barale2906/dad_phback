<?php

namespace App\Services;

use App\Models\Ph;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

class InstallationService
{
    public function isInstalled(): bool
    {
        return Ph::query()->whereNotNull('installed_at')->exists();
    }

    public function checkRequirements(): array
    {
        $requiredExtensions = ['pdo', 'pdo_pgsql', 'mbstring', 'openssl', 'tokenizer', 'ctype', 'json'];

        $extensions = collect($requiredExtensions)->mapWithKeys(function (string $extension): array {
            return [$extension => extension_loaded($extension)];
        })->all();

        $databaseOk = true;
        $databaseError = null;

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $exception) {
            $databaseOk = false;
            $databaseError = $exception->getMessage();
        }

        return [
            'php_version' => PHP_VERSION,
            'php_minimum_ok' => version_compare(PHP_VERSION, '8.4.0', '>='),
            'extensions' => $extensions,
            'database_connection_ok' => $databaseOk,
            'database_error' => $databaseError,
        ];
    }

    public function run(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            if ($this->isInstalled()) {
                throw new HttpResponseException(response()->json([
                    'message' => 'El sistema ya fue instalado.',
                    'status' => 'already_installed',
                ], 409));
            }

            $admin = User::query()->create([
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => $data['admin_password'],
                'rol' => 'SUPER_ADMIN',
                'tipo_usuario' => 'ADMINISTRATIVO',
                'documento' => $data['admin_documento'] ?? null,
                'telefono' => $data['admin_telefono'] ?? null,
                'activo' => true,
            ]);

            $ph = Ph::query()->create([
                'nit' => $data['ph_nit'],
                'nombre' => $data['ph_nombre'],
                'logo' => null,
                'email' => $data['ph_email'] ?? null,
                'direccion' => $data['ph_direccion'] ?? null,
                'telefono' => $data['ph_telefono'] ?? null,
                'estado' => $data['ph_estado'] ?? 'activo',
                'installed_at' => now(),
            ]);

            return [
                'admin' => $admin,
                'ph' => $ph,
            ];
        });
    }
}
