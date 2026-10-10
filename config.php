<?php
declare(strict_types=1);

/*
 * Configuración de base de datos.
 * En producción, es preferible definir DB_HOST, DB_NAME, DB_USER y DB_PASS
 * como variables de entorno en el panel de hosting.
 */
$dbHost = getenv('DB_HOST') ?: 'mysql-diboweb.alwaysdata.net';
$dbName = getenv('DB_NAME') ?: 'diboweb_enviosgit';
$dbUser = getenv('DB_USER') ?: 'diboweb';
$dbPass = getenv('DB_PASS') ?: 'Gol123456*';

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    // La tabla se crea automáticamente si no existe.
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS envios (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            destinatario VARCHAR(150) NOT NULL,
            direccion_envio VARCHAR(255) NOT NULL,
            descripcion TEXT NOT NULL,
            creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_destinatario (destinatario)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (PDOException $e) {
    error_log('Gestor de envíos - error DB: ' . $e->getMessage());
    http_response_code(500);
    exit(
        '<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">' .
        '<title>Error de conexión</title><body style="font:16px Arial;max-width:650px;margin:12vh auto;padding:24px;color:#19304d">' .
        '<h1>No se pudo conectar a la base de datos</h1><p>Revisa el host, las credenciales, los permisos y que la base de datos exista en tu hosting.</p></body></html>'
    );
}
