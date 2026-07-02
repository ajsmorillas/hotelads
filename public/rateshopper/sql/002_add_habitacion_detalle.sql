-- Rate Shopper - migración para la tabla ya existente en producción
-- (no recrea la tabla, solo añade las columnas nuevas con datos por defecto NULL)

ALTER TABLE rateshopper_precios
    ADD COLUMN habitacion_nombre VARCHAR(200) NULL AFTER fuente_precio,
    ADD COLUMN desayuno_incluido TINYINT(1) NULL AFTER habitacion_nombre, -- NULL = dato no disponible, no "no incluido"
    ADD COLUMN tarifa_inclusiones VARCHAR(500) NULL AFTER desayuno_incluido;
