<?php

namespace App\Config;

use Illuminate\Database\Capsule\Manager as Capsule;

class Database
{
    public static function boot(): void
    {
        $capsule = new Capsule();

        $capsule->addConnection(self::conexion(Env::get('DB_DATABASE', '')));
        $capsule->addConnection(self::conexion(Env::get('DB_AUTH_DATABASE', 'restaurante_auth')), 'auth');

        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

    private static function conexion(string $database): array
    {
        return [
            'driver' => Env::get('DB_DRIVER', 'mysql'),
            'host' => Env::get('DB_HOST', '127.0.0.1'),
            'port' => Env::get('DB_PORT', '3306'),
            'database' => $database,
            'username' => Env::get('DB_USERNAME', 'root'),
            'password' => Env::get('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ];
    }
}
