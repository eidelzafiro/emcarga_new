<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Etl\EtlService;
use App\Services\Etl\LegacyDecryptor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use phpseclib3\Crypt\Rijndael;
use Tests\TestCase;

/**
 * Tests del ETL de la Fase 3: descifrado legacy (CI_Encrypt) y motor
 * de migración con doble conexión (default + legacy, ambas SQLite).
 */
class EtlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.legacy' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        $this->seed(); // roles/permisos para la asignación de perfiles
        $this->crearEsquemaLegacy();
    }

    private function crearEsquemaLegacy(): void
    {
        Schema::connection('legacy')->create('cod_usuarios', function ($table) {
            $table->increments('iduser');
            $table->integer('idunidad')->default(3);
            $table->string('login', 50);
            $table->string('password', 128);
            $table->integer('idperfil');
            $table->integer('idbolsa')->default(0);
            $table->integer('color')->default(0);
            $table->integer('matriz')->default(0);
            $table->date('foperaciones')->nullable();
            $table->date('fpass')->nullable();
            $table->integer('cpass')->default(0);
            $table->integer('bloqueado')->default(0);
        });

        Schema::connection('legacy')->create('cod_usuariosh', function ($table) {
            $table->increments('iduserh');
            $table->integer('iduser');
            $table->string('password', 250);
            $table->date('fcambio')->nullable();
        });

        Schema::connection('legacy')->create('com_clientes', function ($table) {
            $table->increments('idcliente');
            $table->string('codcliente', 10);
            $table->string('nombcliente', 200)->nullable();
            $table->string('nit', 11)->nullable();
            $table->string('dircliente', 150)->nullable();
            $table->string('email', 150)->nullable();
        });

        Schema::connection('legacy')->create('tec_tractivos', function ($table) {
            $table->increments('idtractivos');
            $table->string('codtractivo', 15)->nullable();
            $table->string('chapa', 25)->nullable();
            $table->string('chassis', 150)->nullable();
            $table->date('falta')->nullable();
            $table->decimal('kmsacum', 10, 2)->default(0);
        });
    }

    /**
     * Cifra con el algoritmo legacy de CI_Encrypt (para datos de prueba).
     */
    private function cifrarLegacy(string $plain, string $clave = 'PRUEBA'): string
    {
        $key = md5($clave);
        $iv = str_repeat("\1", 32); // IV fijo para test determinista

        $cipher = new Rijndael('cbc');
        $cipher->setBlockLength(256);
        $cipher->setKey($key);
        $cipher->setIV($iv);
        $cipher->disablePadding();

        // Relleno con \0 a múltiplo de 32 (comportamiento de mcrypt)
        $padded = $plain.str_repeat("\0", 32 - (strlen($plain) % 32));
        $data = $iv.$cipher->encrypt($padded);

        // _add_cipher_noise: suma byte a byte sha1($key) módulo 256
        $hash = sha1($key);
        $result = '';
        for ($i = 0, $j = 0, $ld = strlen($data); $i < $ld; $i++, $j++) {
            if ($j >= strlen($hash)) {
                $j = 0;
            }
            $result .= chr((ord($data[$i]) + ord($hash[$j])) % 256);
        }

        return base64_encode($result);
    }

    public function test_decryptor_roundtrip(): void
    {
        $decryptor = new LegacyDecryptor;

        foreach (['ZAFIRO', 'agneris', '15329', 'Clave*Segura2024'] as $plain) {
            $this->assertSame($plain, $decryptor->decrypt($this->cifrarLegacy($plain)));
        }
    }

    public function test_decryptor_con_valor_real_del_dump(): void
    {
        // Valor real de cod_usuarios del dump legacy (reset masivo conocido)
        $decryptor = new LegacyDecryptor;

        $this->assertSame('15329', $decryptor->decrypt(
            'vg2e4TmrpnE0HwC50HyPWvL0akuu1sPCYDrtHq9CQbb36Y9Hroz9P0BmG/RZ9AZuvVJjwAZC6axKVE6GXR5+Jg=='
        ));
    }

    public function test_decryptor_devuelve_null_con_basura(): void
    {
        $decryptor = new LegacyDecryptor;

        $this->assertNull($decryptor->decrypt('no-es-base64-valido!!!'));
        $this->assertNull($decryptor->decrypt(base64_encode('corto')));
    }

    public function test_migrar_usuarios_con_prehash_y_roles(): void
    {
        DB::connection('legacy')->table('cod_usuarios')->insert([
            ['iduser' => 1, 'login' => 'EIDEL', 'password' => $this->cifrarLegacy('MiClave*1'), 'idperfil' => 6, 'idunidad' => 7, 'bloqueado' => 0, 'cpass' => 0, 'fpass' => '2024-01-17'],
            ['iduser' => 2, 'login' => 'VENDEDORA', 'password' => $this->cifrarLegacy('ventas2024'), 'idperfil' => 3, 'idunidad' => 11, 'bloqueado' => 0, 'cpass' => 1, 'fpass' => null],
            ['iduser' => 3, 'login' => 'RRHHGTM', 'password' => $this->cifrarLegacy('rh2024'), 'idperfil' => 1, 'idunidad' => 16, 'bloqueado' => 1, 'cpass' => 0, 'fpass' => null],
        ]);

        $etl = app(EtlService::class);
        $etl->migrarUsuarios();

        // Usuario admin migrado con id preservado y password bcrypt verificable
        $eidel = User::find(1);
        $this->assertNotNull($eidel);
        $this->assertSame('EIDEL', $eidel->username);
        $this->assertTrue(Hash::check('MiClave*1', $eidel->password));
        $this->assertTrue($eidel->hasRole('ADMIN'));
        $this->assertFalse((bool) $eidel->password_temporal);

        // Mapeo de perfil COMERCIAL y flag cpass → password_temporal
        $vendedora = User::find(2);
        $this->assertTrue($vendedora->hasRole('COMERCIAL'));
        $this->assertTrue((bool) $vendedora->password_temporal);

        // RECHUM + bloqueado
        $rrhh = User::find(3);
        $this->assertTrue($rrhh->hasRole('RECHUM'));
        $this->assertTrue((bool) $rrhh->bloqueado);

        // Reporte coherente
        $reporte = $etl->getReporte();
        $this->assertSame(3, $reporte['users']['legacy']);
        $this->assertSame(3, $reporte['users']['nueva']);
    }

    public function test_migrar_usuarios_resuelve_logins_duplicados(): void
    {
        DB::connection('legacy')->table('cod_usuarios')->insert([
            ['iduser' => 5, 'login' => 'DAILEN', 'password' => $this->cifrarLegacy('vieja'), 'idperfil' => 7, 'idunidad' => 11, 'bloqueado' => 0, 'cpass' => 0],
            ['iduser' => 222, 'login' => 'DAILEN', 'password' => $this->cifrarLegacy('nueva'), 'idperfil' => 7, 'idunidad' => 11, 'bloqueado' => 0, 'cpass' => 0],
        ]);

        $etl = app(EtlService::class);
        $etl->migrarUsuarios();

        // Gana el iduser mayor; se reporta el conflicto
        $this->assertSame(1, User::where('username', 'DAILEN')->count());
        $this->assertTrue(Hash::check('nueva', User::where('username', 'DAILEN')->first()->password));
        $this->assertNotEmpty($etl->getReporte()['users']['avisos']);
    }

    public function test_migrar_historial_de_passwords(): void
    {
        DB::connection('legacy')->table('cod_usuarios')->insert([
            ['iduser' => 1, 'login' => 'EIDEL', 'password' => $this->cifrarLegacy('actual'), 'idperfil' => 6, 'idunidad' => 7, 'bloqueado' => 0, 'cpass' => 0],
        ]);
        DB::connection('legacy')->table('cod_usuariosh')->insert([
            ['iduserh' => 1, 'iduser' => 1, 'password' => $this->cifrarLegacy('anterior1'), 'fcambio' => '2023-05-01'],
        ]);

        app(EtlService::class)->migrarUsuarios();

        $historial = DB::table('password_histories')->where('user_id', 1)->first();
        $this->assertNotNull($historial);
        $this->assertTrue(Hash::check('anterior1', $historial->password));
    }

    public function test_migrar_tabla_generica_preserva_ids(): void
    {
        DB::connection('legacy')->table('com_clientes')->insert([
            ['idcliente' => 10, 'codcliente' => 'CLI-01', 'nombcliente' => 'Empresa Uno', 'nit' => '12345678901', 'dircliente' => 'Calle 1', 'email' => 'uno@empresa.cu'],
            ['idcliente' => 20, 'codcliente' => 'CLI-02', 'nombcliente' => 'Empresa Dos', 'nit' => null, 'dircliente' => null, 'email' => null],
        ]);

        $etl = app(EtlService::class);
        $etl->migrarTabla('clientes');

        $this->assertDatabaseHas('clientes', ['id' => 10, 'codigo' => 'CLI-01', 'nombre' => 'Empresa Uno', 'activo' => true]);
        $this->assertDatabaseHas('clientes', ['id' => 20, 'codigo' => 'CLI-02']);

        // Repetible: segunda corrida no duplica
        $etl->migrarTabla('clientes');
        $this->assertSame(2, DB::table('clientes')->count());
    }

    public function test_validar_reporta_conteos(): void
    {
        DB::connection('legacy')->table('cod_usuarios')->insert([
            ['iduser' => 1, 'login' => 'EIDEL', 'password' => $this->cifrarLegacy('x'), 'idperfil' => 6, 'idunidad' => 7, 'bloqueado' => 0, 'cpass' => 0],
        ]);

        $etl = app(EtlService::class);
        $etl->migrarUsuarios();

        $validacion = $etl->validar();
        $this->assertSame(1, $validacion['users']['legacy']);
        // Incluye el usuario ADMIN del seeder de la plataforma
        $this->assertSame(2, $validacion['users']['nueva']);
        $this->assertSame(0, $validacion['clientes']['nueva']);
    }
}
