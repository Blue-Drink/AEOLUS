<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Petición inválida']);
    exit;
}

$srcName = isset($data['srcName']) ? basename($data['srcName']) : '';
$srcDir  = isset($data['srcDir']) ? trim(str_replace('..', '', $data['srcDir']), '/\\') : '';
$destName = isset($data['destName']) ? basename($data['destName']) : '';
$destDir  = isset($data['destDir']) ? trim(str_replace('..', '', $data['destDir']), '/\\') : '';

if ($srcName === '') {
    echo json_encode(['success' => false, 'error' => 'Faltan parámetros: origen']);
    exit;
}

$base = realpath(__DIR__ . '/backend/uploads/' . $_SESSION['usuario']);
if ($base === false) {
    echo json_encode(['success' => false, 'error' => 'Directorio de usuario no encontrado']);
    exit;
}

$srcPath = $base . DIRECTORY_SEPARATOR . ($srcDir ? $srcDir . DIRECTORY_SEPARATOR : '') . $srcName;
// Si destName está vacío significa que queremos mover al directorio indicado por destDir (sin añadir un subnombre)
$destFolder = $base . DIRECTORY_SEPARATOR . ($destDir ? $destDir . DIRECTORY_SEPARATOR : '');
if ($destName !== '') {
    $destFolder .= $destName . DIRECTORY_SEPARATOR;
}
$targetPath = rtrim($destFolder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $srcName;

// Normalizar rutas reales
$realSrc = realpath($srcPath);
// realpath del folder destino existente
$realDestFolder = realpath(rtrim($destFolder, DIRECTORY_SEPARATOR));

// Validaciones básicas
if ($realSrc === false || !file_exists($realSrc)) {
    echo json_encode(['success' => false, 'error' => 'Origen no existe']);
    exit;
}
if ($realDestFolder === false || !is_dir($realDestFolder)) {
    echo json_encode(['success' => false, 'error' => 'Carpeta destino no existe']);
    exit;
}

// Comprobar que tanto origen como destino están dentro del base
if (strpos($realSrc, $base) !== 0 || strpos($realDestFolder, $base) !== 0) {
    echo json_encode(['success' => false, 'error' => 'Ruta fuera de permisos']);
    exit;
}

// Evitar mover una carpeta dentro de sí misma o su subárbol
if (is_dir($realSrc)) {
    $normalizedTarget = $realDestFolder . DIRECTORY_SEPARATOR . basename($realSrc);
    $realNormalizedTarget = realpath($normalizedTarget);
    if ($realNormalizedTarget !== false && strpos($realNormalizedTarget, $realSrc) === 0) {
        echo json_encode(['success' => false, 'error' => 'No se puede mover una carpeta dentro de su propia jerarquía']);
        exit;
    }
}

// Evitar sobrescribir
if (file_exists($targetPath)) {
    echo json_encode(['success' => false, 'error' => 'Destino ya existe']);
    exit;
}

// Intentar mover/renombrar
if (@rename($realSrc, $targetPath)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al mover en el servidor']);
}

exit;

?>
