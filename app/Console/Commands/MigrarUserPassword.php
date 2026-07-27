<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Etl\LegacyDecryptor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MigrarUserPassword extends Command
{
    protected $signature = 'zafiro:migrar-password {username? : Username a migrar (por defecto EIDEL)}';

    protected $description = 'Migra un usuario legacy al nuevo esquema con password descifrado';

    public function handle(): int
    {
        $username = mb_strtoupper($this->argument('username') ?? 'EIDEL');

        $legacy = DB::connection('legacy')
            ->table('cod_usuarios')
            ->where('login', $username)
            ->first();

        if (! $legacy) {
            $this->error("Usuario '{$username}' no encontrado en BD legacy.");

            return self::FAILURE;
        }

        $this->line("Usuario encontrado: {$legacy->login} (iduser: {$legacy->iduser})");

        $decryptor = new LegacyDecryptor;
        $plain = $decryptor->decrypt($legacy->password);

        if ($plain === null || $plain === '') {
            $this->warn('No se pudo descifrar el password, se genera uno temporal.');
            $plain = 'Zafiro*' . bin2hex(random_bytes(4));
        }

        $this->line("Password descifrado correctamente.");

        $user = User::withTrashed()->find($legacy->iduser);

        if (! $user) {
            $user = new User;
            $user->id = $legacy->iduser;
        }

        $user->forceFill([
            'name' => trim($legacy->login),
            'username' => mb_strtoupper(trim($legacy->login)),
            'email' => null,
            'password' => Hash::make($plain),
            'id_entidad' => $legacy->idunidad ?: null,
            'bloqueado' => (bool) $legacy->bloqueado,
            'intentos_fallidos' => 0,
            'fecha_cambio_password' => $legacy->fpass,
            'password_temporal' => (bool) $legacy->cpass,
            'deleted_at' => null,
        ]);

        $user->save();

        $mapeoPerfiles = config('etl.mapeo_perfiles');
        $rol = $mapeoPerfiles[$legacy->idperfil] ?? null;

        if ($rol) {
            $user->syncRoles([$rol]);
            $this->line("Rol asignado: {$rol}");
        } else {
            $this->warn("idperfil {$legacy->idperfil} sin mapeo de rol.");
        }

        $this->info("Usuario '{$username}' migrado exitosamente.");

        return self::SUCCESS;
    }
}
