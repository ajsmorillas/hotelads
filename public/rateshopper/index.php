<?php
declare(strict_types=1);

// Nunca mostrar trazas: podrían llevar la api_key o la contraseña de BD como argumentos.
@ini_set('display_errors', '0');

// Sesión propia solo para el token CSRF. SameSite=Strict: un formulario de otro
// sitio no lleva la cookie, así que no puede gastar cuota aprovechando las
// credenciales Basic que el navegador ya tiene guardadas.
session_name('rateshopper');
session_set_cookie_params(['path' => '/rateshopper/', 'secure' => true, 'httponly' => true, 'samesite' => 'Strict']);
session_start();
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));

const RATESHOPPER_MAX_FECHAS = 31;

require_once __DIR__ . '/includes/config-loader.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/serpapi.php';

$config  = rateshopper_config();
$hoteles = $config['hoteles'];
$apiKey  = $config['serpapi']['api_key'];

$resultadosPorFecha = [];
$busquedasRealizadas = 0;
$erroresGenerales = [];
$cuenta = empty($apiKey) ? null : rateshopper_cuenta($apiKey);
$cuotaAgotada = null; // 'mes' | 'hora' si SerpApi corta a mitad de ejecución
$sinConsultar = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string) ($_POST['csrf'] ?? '');
    if ($csrf === '' || !hash_equals($_SESSION['csrf'], $csrf)) {
        http_response_code(403);
        exit('Sesión caducada. Recarga la página.');
    }
    $fechasInput = (array) ($_POST['fechas'] ?? []);
    $hotelesSeleccionados = (array) ($_POST['hoteles'] ?? []);
    $noches  = max(1, min(30, (int) ($_POST['noches'] ?? 1)));
    $adultos = max(1, min(10, (int) ($_POST['adultos'] ?? 1)));

    $fechas = [];
    foreach ($fechasInput as $f) {
        $f = trim((string) $f);
        if ($f !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f) && strtotime($f) !== false) {
            $fechas[] = $f;
        }
    }
    $fechas = array_values(array_unique($fechas));

    $hotelesValidos = array_intersect($hotelesSeleccionados, array_keys($hoteles));

    if (count($fechas) > RATESHOPPER_MAX_FECHAS) {
        $erroresGenerales[] = 'Como máximo ' . RATESHOPPER_MAX_FECHAS . ' fechas por consulta.';
    }
    if (empty($fechas)) {
        $erroresGenerales[] = 'Indica al menos una fecha de check-in válida.';
    }
    if (empty($hotelesValidos)) {
        $erroresGenerales[] = 'Selecciona al menos un hotel.';
    }
    if (empty($apiKey)) {
        $erroresGenerales[] = 'Falta configurar serpapi.api_key en config.php.';
    }

    $necesarias = count($fechas) * count($hotelesValidos);
    if ($cuenta !== null && $cuenta['quedan'] === null) {
        // Sin saber la cuota no se gasta: es compartida con /staff/rateshopper de hotelesarrecife.
        $erroresGenerales[] = 'No se pudo leer la cuota de SerpApi; no se ha lanzado ninguna búsqueda. Inténtalo en un rato.';
    } elseif ($cuenta !== null && $necesarias > 0) {
        if ($cuenta['quedan'] <= 0) {
            $erroresGenerales[] = 'La cuota de SerpApi está agotada: no se ha lanzado ninguna búsqueda.';
        } elseif ($necesarias > $cuenta['quedan']) {
            $erroresGenerales[] = "Esta consulta necesita {$necesarias} búsquedas y solo quedan {$cuenta['quedan']} este mes. Quita fechas u hoteles.";
        }
        if ($cuenta['limite_hora'] !== null && $cuenta['hora'] !== null
            && $cuenta['hora'] + $necesarias > $cuenta['limite_hora']) {
            $libres = max(0, $cuenta['limite_hora'] - $cuenta['hora']);
            $erroresGenerales[] = "El plan permite {$cuenta['limite_hora']} búsquedas por hora y ahora mismo quedan {$libres}; esta consulta necesita {$necesarias}. Divídela o espera un rato.";
        }
    }

    if (empty($erroresGenerales)) {
      try {
        foreach ($fechas as $checkIn) {
            $checkOut = date('Y-m-d', strtotime($checkIn . " +{$noches} days"));

            foreach ($hotelesValidos as $hotelKey) {
                if ($cuotaAgotada !== null) {
                    $sinConsultar++;
                    continue;
                }
                $hotel = $hoteles[$hotelKey];

                $r = rateshopper_consultar_serpapi(
                    $apiKey,
                    $hotel['nombre'],
                    $hotel['property_token'],
                    $checkIn,
                    $checkOut,
                    $adultos
                );
                if ($r['cuota'] !== null) {
                    $cuotaAgotada = $r['cuota'];
                    $sinConsultar++;
                    continue;
                }
                $busquedasRealizadas++;

                $fila = [
                    'hotel_key'              => $hotelKey,
                    'hotel_nombre'           => $hotel['nombre'],
                    'propio'                 => $hotel['propio'],
                    'property_token'         => $hotel['property_token'],
                    'fecha_checkin'          => $checkIn,
                    'fecha_checkout'         => $checkOut,
                    'noches'                 => $noches,
                    'adultos'                => $adultos,
                    'precio_noche'           => $r['precio_noche'],
                    'precio_noche_sin_tasas' => $r['precio_noche_sin_tasas'],
                    'precio_total'           => $r['precio_total'],
                    'moneda'                 => $r['moneda'],
                    'fuente_precio'          => $r['fuente_precio'],
                    'habitacion_nombre'      => $r['habitacion_nombre'],
                    'desayuno_incluido'      => $r['desayuno_incluido'],
                    'tarifa_inclusiones'     => $r['tarifa_inclusiones'],
                    'error'                  => $r['error'],
                    'raw_json'               => $r['raw_json'],
                ];

                rateshopper_guardar_precio($fila);

                $resultadosPorFecha[$checkIn][] = $fila;
            }
        }
      } catch (Throwable $e) {
        // Solo el mensaje: la traza podría incluir la api_key o la contraseña de BD.
        error_log('[rateshopper] ' . $e->getMessage());
        $erroresGenerales[] = 'Error interno al consultar o guardar precios. Las búsquedas ya hechas sí se han gastado.';
      }

        foreach ($resultadosPorFecha as $checkIn => &$filas) {
            usort($filas, function ($a, $b) {
                if ($a['error'] && !$b['error']) return 1;
                if (!$a['error'] && $b['error']) return -1;
                return ($a['precio_total'] ?? PHP_INT_MAX) <=> ($b['precio_total'] ?? PHP_INT_MAX);
            });
        }
        unset($filas);
        ksort($resultadosPorFecha);

        if ($busquedasRealizadas > 0 || $cuotaAgotada !== null) {
            $cuenta = rateshopper_cuenta($apiKey); // cifra ya descontada
        }
    }
}

$agotada = $cuotaAgotada === 'mes' || ($cuenta !== null && $cuenta['quedan'] !== null && $cuenta['quedan'] <= 0);
$pocas = !$agotada && $cuenta !== null && $cuenta['quedan'] !== null
    && $cuenta['quedan'] <= max(25, (int) (($cuenta['por_mes'] ?? 0) * 0.1));
$renovacionTxt = !empty($cuenta['renovacion'])
    ? ' Se renueva el ' . date('d/m/Y', strtotime($cuenta['renovacion'])) . '.'
    : '';

function e(?string $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Rate Shopper - Hoteles Arrecife</title>
<meta name="robots" content="noindex, nofollow">
<style>
    body { font-family: system-ui, sans-serif; max-width: 1000px; margin: 2rem auto; padding: 0 1rem; color: #1c1c1c; }
    h1 { font-size: 1.4rem; }
    fieldset { border: 1px solid #ccc; border-radius: 6px; margin-bottom: 1.5rem; padding: 1rem; }
    legend { font-weight: 600; padding: 0 0.5rem; }
    .fechas-row { display: flex; gap: 0.5rem; margin-bottom: 0.5rem; align-items: center; }
    .hoteles-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 0.4rem; }
    label.hotel { display: flex; align-items: center; gap: 0.4rem; }
    label.propio { font-weight: 700; color: #0a5d2c; }
    .inline-inputs { display: flex; gap: 1.5rem; margin-top: 1rem; }
    .inline-inputs label { display: flex; flex-direction: column; font-size: 0.85rem; }
    button { cursor: pointer; }
    button[type=submit] { background: #0a5d2c; color: #fff; border: none; padding: 0.6rem 1.2rem; border-radius: 6px; font-size: 1rem; margin-top: 1rem; }
    button.add-fecha { background: #eee; border: 1px solid #ccc; border-radius: 4px; padding: 0.2rem 0.6rem; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 2rem; }
    th, td { border: 1px solid #ddd; padding: 0.4rem 0.6rem; text-align: left; font-size: 0.9rem; }
    th { background: #f5f5f5; }
    tr.propio { background: #eaf6ee; font-weight: 700; }
    tr.error td { color: #b00020; font-style: italic; }
    .aviso-busquedas { background: #fff8e1; border: 1px solid #ffe082; padding: 0.6rem 1rem; border-radius: 6px; margin-bottom: 1.5rem; }
    .errores { background: #fdecea; border: 1px solid #f5c6cb; padding: 0.6rem 1rem; border-radius: 6px; margin-bottom: 1.5rem; color: #b00020; }
</style>
</head>
<body>

<h1>Rate Shopper &mdash; Hoteles Arrecife</h1>
<p>Consulta manual de precios (Google Hotels vía SerpApi) frente a la competencia de Cabo de Gata / San José / Las Negras.</p>

<?php if ($agotada): ?>
<div class="errores" role="alert">
    <strong>Cuota de SerpApi agotada.</strong> No se pueden consultar más precios este mes.<?= e($renovacionTxt) ?>
    <?= $sinConsultar ? 'Se quedaron sin consultar ' . $sinConsultar . ' combinaciones de esta ejecución.' : '' ?>
</div>
<?php elseif ($cuotaAgotada === 'hora'): ?>
<div class="aviso-busquedas" role="alert">
    <strong>Límite de búsquedas por hora alcanzado.</strong> Se quedaron sin consultar <?= $sinConsultar ?> combinaciones. Vuelve a intentarlo dentro de un rato.
</div>
<?php elseif ($pocas): ?>
<div class="aviso-busquedas" role="status">
    Quedan solo <strong><?= (int) $cuenta['quedan'] ?></strong> búsquedas de SerpApi este mes.<?= e($renovacionTxt) ?>
</div>
<?php endif; ?>

<?php if ($cuenta !== null && $cuenta['error'] !== null): ?>
<p class="errores">No se pudo leer la cuota de SerpApi (<?= e($cuenta['error']) ?>). No se lanzarán búsquedas hasta que se pueda comprobar.</p>
<?php elseif ($cuenta !== null && !$agotada): ?>
<p>Cuota: <strong><?= (int) $cuenta['quedan'] ?></strong> búsquedas disponibles<?= $cuenta['por_mes'] ? ' de ' . (int) $cuenta['por_mes'] . ' al mes' : '' ?><?= $cuenta['plan'] ? ' (plan ' . e($cuenta['plan']) . ')' : '' ?>.<?= e($renovacionTxt) ?></p>
<?php endif; ?>

<?php if (!empty($erroresGenerales)): ?>
<div class="errores">
    <ul>
    <?php foreach ($erroresGenerales as $e): ?>
        <li><?= e($e) ?></li>
    <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($erroresGenerales)): ?>
<div class="aviso-busquedas">
    Búsquedas gastadas en esta ejecución: <strong><?= $busquedasRealizadas ?></strong>
    (cada combinación hotel × fecha consume 1 búsqueda de la cuota de SerpApi).
</div>
<?php endif; ?>

<form method="post">
    <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
    <fieldset>
        <legend>Fechas de check-in</legend>
        <div id="fechas-container">
        <?php
            $fechasPrevias = (array) ($_POST['fechas'] ?? [date('Y-m-d')]);
            foreach ($fechasPrevias as $f):
        ?>
            <div class="fechas-row">
                <input type="date" name="fechas[]" value="<?= e((string) $f) ?>" required>
                <button type="button" class="quitar-fecha">✕</button>
            </div>
        <?php endforeach; ?>
        </div>
        <button type="button" class="add-fecha" id="add-fecha">+ Añadir fecha</button>
    </fieldset>

    <fieldset>
        <legend>Hoteles a consultar</legend>
        <div class="hoteles-grid">
        <?php
            $hotelesPrevios = (array) ($_POST['hoteles'] ?? array_keys($hoteles));
            foreach ($hoteles as $key => $hotel):
                $checked = in_array($key, $hotelesPrevios, true) ? 'checked' : '';
                $claseLabel = $hotel['propio'] ? 'hotel propio' : 'hotel';
        ?>
            <label class="<?= $claseLabel ?>">
                <input type="checkbox" name="hoteles[]" value="<?= e($key) ?>" <?= $checked ?>>
                <?= e($hotel['nombre']) ?><?= $hotel['propio'] ? ' (propio)' : '' ?>
            </label>
        <?php endforeach; ?>
        </div>

        <div class="inline-inputs">
            <label>Noches
                <input type="number" name="noches" min="1" value="<?= e((string) ($_POST['noches'] ?? 3)) ?>" required>
            </label>
            <label>Adultos
                <input type="number" name="adultos" min="1" value="<?= e((string) ($_POST['adultos'] ?? 2)) ?>" required>
            </label>
        </div>
    </fieldset>

    <button type="submit"<?= $agotada ? ' disabled style="background:#999;cursor:not-allowed"' : '' ?>>Consultar precios</button>
</form>

<?php foreach ($resultadosPorFecha as $checkIn => $filas): ?>
    <h2><?= e($checkIn) ?> &rarr; <?= e($filas[0]['fecha_checkout']) ?> (<?= (int) $filas[0]['noches'] ?> noches)</h2>
    <table>
        <thead>
            <tr>
                <th>Hotel</th>
                <th>Precio/noche</th>
                <th>Precio/noche sin tasas</th>
                <th>Precio total</th>
                <th>Fuente</th>
                <th>Habitación</th>
                <th>Desayuno</th>
                <th>Inclusiones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($filas as $fila): ?>
            <tr class="<?= $fila['error'] ? 'error' : ($fila['propio'] ? 'propio' : '') ?>">
                <td><?= e($fila['hotel_nombre']) ?></td>
                <?php if ($fila['error']): ?>
                    <td colspan="7">Error: <?= e($fila['error']) ?></td>
                <?php else: ?>
                    <td><?= $fila['precio_noche'] !== null ? number_format((float) $fila['precio_noche'], 2, ',', '.') . ' €' : '—' ?></td>
                    <td><?= $fila['precio_noche_sin_tasas'] !== null ? number_format((float) $fila['precio_noche_sin_tasas'], 2, ',', '.') . ' €' : '—' ?></td>
                    <td><?= $fila['precio_total'] !== null ? number_format((float) $fila['precio_total'], 2, ',', '.') . ' €' : '—' ?></td>
                    <td><?= e($fila['fuente_precio']) ?: '—' ?></td>
                    <td><?= e($fila['habitacion_nombre']) ?: '—' ?></td>
                    <td><?= $fila['desayuno_incluido'] === null ? '—' : ($fila['desayuno_incluido'] ? 'Sí' : 'No') ?></td>
                    <td><?= e($fila['tarifa_inclusiones']) ?: '—' ?></td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endforeach; ?>

<script>
document.getElementById('add-fecha').addEventListener('click', function () {
    var container = document.getElementById('fechas-container');
    var row = document.createElement('div');
    row.className = 'fechas-row';
    row.innerHTML = '<input type="date" name="fechas[]" required>' +
        '<button type="button" class="quitar-fecha">✕</button>';
    row.querySelector('.quitar-fecha').addEventListener('click', function () {
        row.remove();
    });
    container.appendChild(row);
});
document.querySelectorAll('.quitar-fecha').forEach(function (btn) {
    btn.addEventListener('click', function () {
        btn.parentElement.remove();
    });
});
</script>

</body>
</html>
