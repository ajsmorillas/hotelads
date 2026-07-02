-- Rate Shopper (hotelads.es) - esquema de la tabla de precios
-- Ejecutar una sola vez contra la BD de producción: rateshopper_

CREATE TABLE IF NOT EXISTS rateshopper_precios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hotel_key VARCHAR(50) NOT NULL,
    hotel_nombre VARCHAR(150) NOT NULL,
    propio TINYINT(1) NOT NULL DEFAULT 0,
    property_token VARCHAR(150) NOT NULL,
    fecha_checkin DATE NOT NULL,
    fecha_checkout DATE NOT NULL,
    noches INT NOT NULL,
    adultos INT NOT NULL DEFAULT 2,
    precio_noche DECIMAL(10,2) NULL,
    precio_noche_sin_tasas DECIMAL(10,2) NULL,
    precio_total DECIMAL(10,2) NULL,
    moneda VARCHAR(10) NOT NULL DEFAULT 'EUR',
    fuente_precio VARCHAR(100) NULL,
    fecha_consulta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    error VARCHAR(255) NULL,
    raw_json MEDIUMTEXT NULL,
    INDEX idx_hotel_checkin (hotel_key, fecha_checkin),
    INDEX idx_fecha_consulta (fecha_consulta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
