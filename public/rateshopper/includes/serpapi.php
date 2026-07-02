<?php

/**
 * Lanza una consulta a SerpApi (engine=google_hotels) para un hotel y fechas dadas.
 * Nunca lanza excepción: cualquier fallo se devuelve en la clave 'error' para
 * no interrumpir el resto de combinaciones hotel x fecha de la ejecución.
 *
 * `q` es Required por la API aunque se use property_token; se envía el nombre
 * del hotel solo para satisfacer el requisito, property_token sigue fijando el hotel exacto.
 */
function rateshopper_consultar_serpapi(
    string $apiKey,
    string $hotelNombre,
    string $propertyToken,
    string $checkIn,
    string $checkOut,
    int $adults
): array {
    $resultado = [
        'precio_noche'           => null,
        'precio_noche_sin_tasas' => null,
        'precio_total'           => null,
        'moneda'                 => 'EUR',
        'fuente_precio'          => null,
        'habitacion_nombre'      => null,
        'desayuno_incluido'      => null,
        'tarifa_inclusiones'     => null,
        'error'                  => null,
        'raw_json'               => null,
    ];

    $params = http_build_query([
        'engine'          => 'google_hotels',
        'q'               => $hotelNombre, // Required por la API incluso con property_token; este último sigue fijando el hotel exacto.
        'property_token'  => $propertyToken,
        'check_in_date'   => $checkIn,
        'check_out_date'  => $checkOut,
        'adults'          => $adults,
        'currency'        => 'EUR',
        'gl'              => 'es',
        'hl'              => 'es',
        'api_key'         => $apiKey,
    ]);

    $url = 'https://serpapi.com/search.json?' . $params;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        $resultado['error'] = 'Error de conexión con SerpApi: ' . $curlError;
        return $resultado;
    }

    $resultado['raw_json'] = $response;

    $data = json_decode($response, true);
    if (!is_array($data)) {
        $resultado['error'] = 'Respuesta de SerpApi no es JSON válido (HTTP ' . $httpCode . ')';
        return $resultado;
    }

    if (isset($data['error'])) {
        $resultado['error'] = 'SerpApi: ' . $data['error'];
        return $resultado;
    }

    if ($httpCode !== 200) {
        $resultado['error'] = 'SerpApi devolvió HTTP ' . $httpCode;
        return $resultado;
    }

    $ratePerNight = $data['rate_per_night'] ?? null;
    $totalRate    = $data['total_rate'] ?? null;

    if (!$ratePerNight && !$totalRate) {
        $resultado['error'] = 'Sin resultados de precio para este hotel/fecha';
        return $resultado;
    }

    $resultado['precio_noche']           = $ratePerNight['extracted_lowest'] ?? null;
    $resultado['precio_noche_sin_tasas'] = $ratePerNight['extracted_before_taxes_fees'] ?? null;
    $resultado['precio_total']           = $totalRate['extracted_lowest'] ?? null;

    $prices = $data['prices'] ?? [];
    $resultado['fuente_precio'] = $prices[0]['source'] ?? null;

    $detalle = rateshopper_extraer_detalle_habitacion($data, $resultado['precio_noche']);
    $resultado['habitacion_nombre']  = $detalle['habitacion_nombre'];
    $resultado['desayuno_incluido']  = $detalle['desayuno_incluido'];
    $resultado['tarifa_inclusiones'] = $detalle['tarifa_inclusiones'];

    return $resultado;
}

/**
 * Busca, dentro de featured_prices[], la fuente cuyo precio coincide con el que ya
 * mostramos (rate_per_night.extracted_lowest de la raíz), y de ahí extrae la
 * habitación y tarifa más baratas para obtener nombre de habitación, desayuno
 * incluido e inclusiones. No todos los hoteles/fuentes traen este nivel de detalle
 * (depende de si Google tiene datos granulares de habitación para esa propiedad),
 * así que cualquier nivel ausente devuelve null sin lanzar error.
 */
function rateshopper_extraer_detalle_habitacion(array $data, $precioRaiz): array
{
    $detalle = [
        'habitacion_nombre'  => null,
        'desayuno_incluido'  => null,
        'tarifa_inclusiones' => null,
    ];

    $featuredPrices = $data['featured_prices'] ?? null;
    if (!is_array($featuredPrices) || empty($featuredPrices) || $precioRaiz === null) {
        return $detalle;
    }

    // 1. Fuente cuyo rate_per_night.extracted_lowest está más cerca del precio ya mostrado.
    $fuente = null;
    $mejorDiferencia = null;
    foreach ($featuredPrices as $entry) {
        $precioFuente = $entry['rate_per_night']['extracted_lowest'] ?? null;
        if ($precioFuente === null) {
            continue;
        }
        $diferencia = abs($precioFuente - $precioRaiz);
        if ($mejorDiferencia === null || $diferencia < $mejorDiferencia) {
            $mejorDiferencia = $diferencia;
            $fuente = $entry;
        }
    }
    if ($fuente === null) {
        return $detalle;
    }

    // 2. Habitación más barata dentro de esa fuente.
    $rooms = $fuente['rooms'] ?? null;
    if (!is_array($rooms) || empty($rooms)) {
        return $detalle;
    }
    $room = null;
    $mejorPrecioRoom = null;
    foreach ($rooms as $r) {
        $precioRoom = $r['rate_per_night']['extracted_lowest'] ?? null;
        if ($precioRoom === null) {
            continue;
        }
        if ($mejorPrecioRoom === null || $precioRoom < $mejorPrecioRoom) {
            $mejorPrecioRoom = $precioRoom;
            $room = $r;
        }
    }
    if ($room === null) {
        return $detalle;
    }

    $detalle['habitacion_nombre'] = $room['name'] ?? null;

    // 3. Tarifa más barata dentro de esa habitación.
    $rates = $room['rates'] ?? null;
    if (!is_array($rates) || empty($rates)) {
        return $detalle;
    }
    $rate = null;
    $mejorPrecioRate = null;
    foreach ($rates as $rt) {
        $precioRate = $rt['rate_per_night']['extracted_lowest'] ?? null;
        if ($precioRate === null) {
            continue;
        }
        if ($mejorPrecioRate === null || $precioRate < $mejorPrecioRate) {
            $mejorPrecioRate = $precioRate;
            $rate = $rt;
        }
    }
    if ($rate === null) {
        return $detalle;
    }

    if (isset($rate['breakfast_included'])) {
        $detalle['desayuno_incluido'] = $rate['breakfast_included'] ? 1 : 0;
    }

    $inclusions = $rate['inclusions'] ?? null;
    if (is_array($inclusions) && !empty($inclusions)) {
        $detalle['tarifa_inclusiones'] = implode(', ', $inclusions);
    }

    return $detalle;
}
