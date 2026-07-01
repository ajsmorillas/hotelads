<?php
declare(strict_types=1);

@ini_set('display_errors', '0');
error_reporting(0);
ob_start();

// CORS
$allowed_origins = ['https://hotelads.es', 'https://imorillas.com'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: {$origin}");
}
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/core/bot-core.php';

// Cargar .env
$_envFile = dirname(dirname(__DIR__)) . '/.env';
if (file_exists($_envFile)) {
    foreach (file($_envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $_line) {
        if ($_line === '' || $_line[0] === '#' || strpos($_line, '=') === false) continue;
        [$_k, $_v] = explode('=', $_line, 2);
        putenv(trim($_k) . '=' . trim($_v));
    }
}
unset($_envFile, $_line, $_k, $_v);

// Rate limiting
$ip      = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateKey = sys_get_temp_dir() . '/rl_hotelads_' . md5($ip) . '.json';
$now     = time();
$rateRaw = file_exists($rateKey) ? @json_decode(@file_get_contents($rateKey), true) : null;
$rateData = (is_array($rateRaw) && isset($rateRaw['count'], $rateRaw['start']))
    ? $rateRaw : ['count' => 0, 'start' => $now];
if ($now - (int) $rateData['start'] > 60) {
    $rateData = ['count' => 0, 'start' => $now];
}
$rateData['count']++;
@file_put_contents($rateKey, json_encode($rateData));
if ($rateData['count'] > 20) {
    http_response_code(429);
    echo json_encode(['response' => 'Demasiadas peticiones. Por favor espera un momento.']);
    exit;
}

// Cargar configuración
$hoteles = require __DIR__ . '/config/hoteles.php';

// Leer entrada
$input      = json_decode(file_get_contents('php://input'), true);
$mensaje    = trim($input['message'] ?? '');
$phone_id   = trim($input['phone_id'] ?? '');
$canal      = trim($input['canal'] ?? 'whatsapp');
$historial  = array_slice($input['history'] ?? [], -10);

if (empty($mensaje)) {
    ob_end_clean();
    echo json_encode(['response' => '¿En qué puedo ayudarte?']);
    exit;
}

if (mb_strlen($mensaje) > 500) {
    $mensaje = mb_substr($mensaje, 0, 500);
}

// Resolver hotel por phone_id
$hotel = $hoteles[$phone_id] ?? null;

if (!$hotel) {
    ob_end_clean();
    echo json_encode(['response' => 'Servicio no disponible para este número.']);
    exit;
}

// Cargar instrucciones del hotel
$instrucciones = file_exists($hotel['instrucciones'])
    ? file_get_contents($hotel['instrucciones'])
    : "Asistente de hotel.";

// API Key
$api_key = getenv('GOOGLE_API_KEY') ?: '';
if ($api_key === '') {
    ob_end_clean();
    echo json_encode(['response' => 'Servicio temporalmente no disponible.']);
    exit;
}

// Procesar con Gemini
$respuesta = procesarConGemini($mensaje, $instrucciones, $historial, $api_key, $canal);

// Log
$log_dir = dirname(__DIR__) . '/chat-log';
if (!is_dir($log_dir)) {
    @mkdir($log_dir, 0750, true);
    @file_put_contents($log_dir . '/.htaccess', "Require all denied\n");
}
$log_file = $log_dir . '/historial_chat.txt';
if (file_exists($log_file) && filesize($log_file) > 1048576) {
    rename($log_file, $log_dir . '/historial_' . date('Y-m') . '.txt');
}
$log_entry = "[" . date('Y-m-d H:i:s') . "] canal:{$canal} phone_id:{$phone_id} hotel:{$hotel['nombre']} IP:{$ip}\n"
           . "  → {$mensaje}\n"
           . "  ← {$respuesta}\n\n";
@file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);

ob_end_clean();
echo json_encode(['response' => $respuesta]);
