<?php
declare(strict_types=1);

/**
 * Núcleo del bot — independiente del canal (WhatsApp, voz, web).
 * Recibe mensaje + instrucciones + historial y devuelve respuesta de Gemini.
 */

function cargarModelo(string $apiKey): string
{
    $list_url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $apiKey;
    $ch = curl_init($list_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $list_response = curl_exec($ch);
    $models_data = json_decode($list_response, true);
    curl_close($ch);

    $model_priority = [
        'gemini-2.5-flash',
        'gemini-2.5-pro',
        'gemini-2.0-flash',
        'gemini-1.5-flash',
        'gemini-pro',
    ];

    if (isset($models_data['models'])) {
        foreach ($model_priority as $preferred) {
            foreach ($models_data['models'] as $m) {
                if (strpos($m['name'], $preferred) !== false
                    && in_array("generateContent", $m['supportedGenerationMethods'])) {
                    return $m['name'];
                }
            }
        }
    }
    return "models/gemini-2.0-flash";
}

function procesarConGemini(
    string $mensaje,
    string $instrucciones,
    array $historial,
    string $apiKey,
    string $canal = 'whatsapp'
): string {
    $modelo = cargarModelo($apiKey);
    $url = "https://generativelanguage.googleapis.com/v1beta/{$modelo}:generateContent?key={$apiKey}";

    $dias = ["Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado"];
    $hoy = $dias[date('w')] . " " . date('d-m-Y');
    $contexto = "\n\nFECHA ACTUAL: {$hoy}\nCANAL: {$canal}";

    // Canal WhatsApp: no sugerir llamar ni escribir por WhatsApp
    if ($canal === 'whatsapp') {
        $contexto .= "\nIMPORTANTE: Estás en WhatsApp. NO sugieras llamar ni escribir por WhatsApp.";
    }

    $contents = [];
    foreach (array_slice($historial, -10) as $turn) {
        $role = ($turn['role'] === 'model') ? 'model' : 'user';
        $text = trim($turn['parts'][0]['text'] ?? '');
        if ($text !== '') {
            $contents[] = ["role" => $role, "parts" => [["text" => $text]]];
        }
    }
    $contents[] = ["role" => "user", "parts" => [["text" => $mensaje]]];

    $data = [
        "systemInstruction" => [
            "parts" => [["text" => $instrucciones . $contexto]]
        ],
        "contents" => $contents,
        "generationConfig" => ["temperature" => 0.4, "maxOutputTokens" => 2500],
        "safetySettings" => [
            ["category" => "HARM_CATEGORY_HARASSMENT",        "threshold" => "BLOCK_MEDIUM_AND_ABOVE"],
            ["category" => "HARM_CATEGORY_HATE_SPEECH",       "threshold" => "BLOCK_MEDIUM_AND_ABOVE"],
            ["category" => "HARM_CATEGORY_SEXUALLY_EXPLICIT", "threshold" => "BLOCK_MEDIUM_AND_ABOVE"],
            ["category" => "HARM_CATEGORY_DANGEROUS_CONTENT", "threshold" => "BLOCK_MEDIUM_AND_ABOVE"],
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 45);
    $response = curl_exec($ch);
    $result = json_decode($response, true);
    curl_close($ch);

    return $result['candidates'][0]['content']['parts'][0]['text']
        ?? "Lo siento, tengo problemas para conectar. Por favor, inténtalo de nuevo.";
}
