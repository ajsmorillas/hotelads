<?php
declare(strict_types=1);

@ini_set('display_errors', '0');
error_reporting(0);
ob_start();

/**
 * Envía una plantilla WhatsApp aprobada por Meta. Multi-tenant: cualquier
 * chatbot de la red Hotelads (hotelesarrecife.es, etc.) llama a este
 * endpoint pasando su propio phone_number_id; el WA_ACCESS_TOKEN es el de la
 * WABA de Hotelads.
 *
 * Dos plantillas soportadas (parámetro opcional 'template', por defecto
 * "info_hotel" para no romper llamadas existentes):
 * - "info_hotel": disponibilidad/precio para el CLIENTE (comportamiento
 *   histórico, sin cambios).
 * - "aviso_de_cliente": aviso al EQUIPO del hotel de una petición de
 *   contacto humano (hotelesarrecife.es §3 ESCALADO A HUMANO) — requiere
 *   además 'customer_phone', el teléfono del cliente para que el equipo
 *   pueda devolverle la llamada.
 */

$allowed_origins = ['https://hotelesarrecife.es', 'https://hotelads.es'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: {$origin}");
}
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    ob_end_clean();
    http_response_code(200);
    exit;
}

// Cargar .env (mismo patrón que chat-server.php)
$_envFile = dirname(dirname(__DIR__)) . '/.env';
if (file_exists($_envFile)) {
    foreach (file($_envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $_line) {
        if ($_line === '' || $_line[0] === '#' || strpos($_line, '=') === false) continue;
        [$_k, $_v] = explode('=', $_line, 2);
        putenv(trim($_k) . '=' . trim($_v));
    }
}
unset($_envFile, $_line, $_k, $_v);

// ── Autenticación entre proyectos de la red Hotelads ────────────────────
// Solo chatbots conocidos (hotelesarrecife.es, etc.) deben poder disparar
// envíos reales de WhatsApp a través de este endpoint compartido.
$sharedToken = getenv('INTERNAL_API_TOKEN') ?: '';
$givenToken  = (string) ($_SERVER['HTTP_X_INTERNAL_TOKEN'] ?? '');
if ($sharedToken === '' || $givenToken === '' || !hash_equals($sharedToken, $givenToken)) {
    ob_end_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

function sw_log(string $mensaje): void
{
    $log_dir = dirname(__DIR__) . '/chat-log';
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0750, true);
        @file_put_contents($log_dir . '/.htaccess', "Require all denied\n");
    }
    @file_put_contents(
        $log_dir . '/send-whatsapp.txt',
        '[' . date('Y-m-d H:i:s') . '] ' . $mensaje . "\n",
        FILE_APPEND | LOCK_EX
    );
}

$input = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($input)) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Petición inválida']);
    exit;
}

$phone           = preg_replace('/\D/', '', trim((string) ($input['phone'] ?? '')));
$nombre          = trim((string) ($input['nombre'] ?? ''));
$hotel           = trim((string) ($input['hotel'] ?? ''));
$info            = trim((string) ($input['info'] ?? ''));
$phone_number_id = trim((string) ($input['phone_number_id'] ?? ''));
$template        = trim((string) ($input['template'] ?? 'info_hotel'));
$customer_phone  = trim((string) ($input['customer_phone'] ?? ''));

$allowed_templates = ['info_hotel', 'aviso_de_cliente'];
if (!in_array($template, $allowed_templates, true)) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Plantilla no soportada']);
    sw_log("ERROR | Plantilla no soportada | template='{$template}'");
    exit;
}

if ($phone === '' || $nombre === '' || $hotel === '' || $info === '' || $phone_number_id === '') {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Faltan datos obligatorios']);
    sw_log("ERROR | Datos incompletos | phone='{$phone}' nombre='{$nombre}' hotel='{$hotel}' phone_number_id='{$phone_number_id}'");
    exit;
}

if ($template === 'aviso_de_cliente' && $customer_phone === '') {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Falta customer_phone para aviso_de_cliente']);
    sw_log("ERROR | Falta customer_phone | template='{$template}' phone='{$phone}'");
    exit;
}

// Whitelist de phone_number_id conocidos de la red Hotelads — no confiar en
// cualquier ID que envíe el llamante, aunque ya esté autenticado por token.
$allowed_phone_number_ids = [
    '1109730118900166', // hotelesarrecife.es
];
if (!in_array($phone_number_id, $allowed_phone_number_ids, true)) {
    ob_end_clean();
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'phone_number_id no autorizado']);
    sw_log("ERROR | phone_number_id no autorizado | '{$phone_number_id}'");
    exit;
}

$accessToken = getenv('WA_ACCESS_TOKEN') ?: '';
if ($accessToken === '') {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Configuración incompleta del servidor']);
    sw_log('ERROR | Falta WA_ACCESS_TOKEN en el .env');
    exit;
}

// Meta rechaza algunos caracteres especiales en plantillas
$toAscii = static function (string $text): string {
    $out = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    if ($out === false) {
        $map = ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
                'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
                'ñ' => 'n', 'Ñ' => 'N', 'ü' => 'u', 'Ü' => 'U'];
        $out = strtr($text, $map);
    }
    return $out;
};

$param1 = mb_substr($toAscii($nombre), 0, 60);
$param2 = mb_substr($toAscii($hotel), 0, 60);
$param3 = mb_substr($toAscii($info), 0, 600);

if ($template === 'aviso_de_cliente') {
    $param_phone = mb_substr($toAscii($customer_phone), 0, 30);
    sw_log("SENDING | template=aviso_de_cliente nombre='{$param1}' customer_phone='{$param_phone}' target='{$param2}' phone='{$phone}' resumen='" . substr($param3, 0, 80) . "'");
    $template_components = [
        [
            'type'       => 'body',
            'parameters' => [
                ['type' => 'text', 'parameter_name' => 'customer_name',    'text' => $param1],
                ['type' => 'text', 'parameter_name' => 'customer_phone',   'text' => $param_phone],
                ['type' => 'text', 'parameter_name' => 'target_label',     'text' => $param2],
                ['type' => 'text', 'parameter_name' => 'request_summary',  'text' => $param3],
            ],
        ],
    ];
} else {
    sw_log("SENDING | template=info_hotel nombre='{$param1}' hotel='{$param2}' phone='{$phone}' info='" . substr($param3, 0, 80) . "'");
    $template_components = [
        [
            'type'       => 'body',
            'parameters' => [
                ['type' => 'text', 'parameter_name' => 'customer_name', 'text' => $param1],
                ['type' => 'text', 'parameter_name' => 'hotel_name',    'text' => $param2],
                ['type' => 'text', 'parameter_name' => 'booking_info',  'text' => $param3],
            ],
        ],
    ];
}

$payload = json_encode([
    'messaging_product' => 'whatsapp',
    'to'                => $phone,
    'type'              => 'template',
    'template'          => [
        'name'       => $template,
        'language'   => ['code' => 'es'],
        'components' => $template_components,
    ],
], JSON_UNESCAPED_UNICODE);

$ch = curl_init("https://graph.facebook.com/v19.0/{$phone_number_id}/messages");
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken,
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT        => 20,
]);
$raw  = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err  = curl_error($ch);
curl_close($ch);

ob_end_clean();

if ($raw === false || $code < 200 || $code >= 300) {
    sw_log("ERROR | Envío fallido (HTTP {$code}) a {$phone} vía {$phone_number_id} | " . ($err ?: $raw));
    http_response_code(502);
    echo json_encode(['success' => false, 'error' => 'No se pudo enviar el mensaje']);
    exit;
}

sw_log("OK | template={$template} | {$nombre} ({$phone}) <- {$hotel} vía {$phone_number_id}");

echo json_encode(['success' => true]);
