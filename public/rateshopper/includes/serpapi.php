<?php

/**
 * Lanza una consulta a SerpApi (engine=google_hotels) para un hotel y fechas dadas.
 * Nunca lanza excepción: cualquier fallo se devuelve en la clave 'error' para
 * no interrumpir el resto de combinaciones hotel x fecha de la ejecución.
 */
function rateshopper_consultar_serpapi(
    string $apiKey,
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
        'error'                  => null,
        'raw_json'               => null,
    ];

    $params = http_build_query([
        'engine'          => 'google_hotels',
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

    return $resultado;
}
