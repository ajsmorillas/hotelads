<?php

require_once __DIR__ . '/config-loader.php';

function rateshopper_db(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $config = rateshopper_config();
    $db = $config['db'];

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $db['host'],
        $db['port'],
        $db['name']
    );

    $pdo = new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    return $pdo;
}

function rateshopper_guardar_precio(array $fila): void
{
    $sql = 'INSERT INTO rateshopper_precios
        (hotel_key, hotel_nombre, propio, property_token, fecha_checkin, fecha_checkout,
         noches, adultos, precio_noche, precio_noche_sin_tasas, precio_total, moneda,
         fuente_precio, error, raw_json)
        VALUES
        (:hotel_key, :hotel_nombre, :propio, :property_token, :fecha_checkin, :fecha_checkout,
         :noches, :adultos, :precio_noche, :precio_noche_sin_tasas, :precio_total, :moneda,
         :fuente_precio, :error, :raw_json)';

    $stmt = rateshopper_db()->prepare($sql);
    $stmt->execute([
        'hotel_key'              => $fila['hotel_key'],
        'hotel_nombre'           => $fila['hotel_nombre'],
        'propio'                 => $fila['propio'] ? 1 : 0,
        'property_token'         => $fila['property_token'],
        'fecha_checkin'          => $fila['fecha_checkin'],
        'fecha_checkout'         => $fila['fecha_checkout'],
        'noches'                 => $fila['noches'],
        'adultos'                => $fila['adultos'],
        'precio_noche'           => $fila['precio_noche'],
        'precio_noche_sin_tasas' => $fila['precio_noche_sin_tasas'],
        'precio_total'           => $fila['precio_total'],
        'moneda'                 => $fila['moneda'] ?? 'EUR',
        'fuente_precio'          => $fila['fuente_precio'],
        'error'                  => $fila['error'],
        'raw_json'               => $fila['raw_json'],
    ]);
}
