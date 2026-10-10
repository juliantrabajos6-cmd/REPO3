<?php
declare(strict_types=1);

/*
 * Configuración de conexión MySQL.
 * Recomendación: cuando publiques el proyecto, mueve estas credenciales
 * a variables de entorno y no compartas este archivo públicamente.
 */
const DB_HOST = 'mysql-julianapi.alwaysdata.net';
const DB_NAME = 'julianapi_enviosgit';
const DB_USER = 'julianapi';
const DB_PASS = 'clase12345';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Crear la tabla automáticamente si todavía no existe.
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS envios (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            destinatario VARCHAR(150) NOT NULL,
            direccion VARCHAR(255) NOT NULL,
            descripcion TEXT NOT NULL,
            creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    return $pdo;
}
