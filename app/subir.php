<?php
session_start();
if (!isset($_SESSION['usuario'])) { header("Location: index.html"); exit(); }

// --- FUNCIÓN BÁSCULA AÑADIDA---
function calcularTamañoDirectorio($ruta) {
    $tamañoTotal = 0;
    if (!file_exists($ruta)) return 0;
    $archivos = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($ruta, FilesystemIterator::SKIP_DOTS));
    foreach ($archivos as $archivo) {
        $tamañoTotal += $archivo->getSize();
    }
    return $tamañoTotal;
}
// ----------------------------------------------

$base = "uploads/" . $_SESSION['usuario'] . "/";
$dir_req = isset($_POST['directorio_destino']) ? trim(str_replace('..', '', $_POST['directorio_destino']), '/\\') : '';
$target_dir = $base . $dir_req;

if (!file_exists($target_dir)) $target_dir = $base;
else $target_dir .= "/";

if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] == 0) {

    // Límite de tamaño individual (20 MB máximo)
    $max_size = 200 * 1024 * 1024;
    if ($_FILES['archivo']['size'] > $max_size) {
        echo "<script>alert('Error: El archivo es demasiado grande (Máximo 200MB).'); window.history.back();</script>";
        exit();
    }

    // --- BARRERA DE CUOTA DE 1GB ---
    $limite_bytes = 1073741824; // 1GB
    $ruta_carpeta_usuario = "uploads/" . $_SESSION['usuario'];
    $tamaño_actual_bytes = calcularTamañoDirectorio($ruta_carpeta_usuario);
    $peso_archivo_nuevo = $_FILES['archivo']['size'];

    if (($tamaño_actual_bytes + $peso_archivo_nuevo) > $limite_bytes) {
        echo "<script>alert('❌ Error: Espacio insuficiente. Superarías tu cuota máxima de 1GB.'); window.history.back();</script>";
        exit();
    }
    // -------------------------------------------------------

    // MEJORA: Sanitización estricta del nombre y control de extensiones
    $nombre_original = basename($_FILES['archivo']['name']);
    // Se extrae la extensión (la última) y el nombre por separado
    $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
    $nombre_sin_ext = pathinfo($nombre_original, PATHINFO_FILENAME);
    // Se limpia el nombre de CUALQUIER carácter raro, dejando solo letras, números y guiones
    $nombre_limpio = preg_replace("/[^a-zA-Z0-9_-]/", "", $nombre_sin_ext);
    // Se reconstruye el nombre (Se evita el truco de la doble extensión archivo.php.jpg)
    $nombre_seguro = $nombre_limpio . "." . $extension;

    // --- INICIO DEL FILTRO DE SEGURIDAD RECUPERADO ---
    $tmp_name = $_FILES['archivo']['tmp_name'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_real = finfo_file($finfo, $tmp_name);
    finfo_close($finfo);

    // ==============================================================================
    // Contracomprobación Cruzada Mime/Extensión

    // Mapa estricto que relaciona la identidad real (MIME) con sus extensiones seguras
    $mapa_extensiones_seguras = [
        // --- Imágenes ---
        'image/jpeg'      => ['jpg', 'jpeg'],
        'image/png'       => ['png'],
        'image/gif'       => ['gif'],
        'image/webp'      => ['webp'],
        'image/bmp'       => ['bmp'],

        // --- Vídeo ---
        'video/mp4'       => ['mp4'],
        'video/webm'      => ['webm'],
        'video/x-matroska'=> ['mkv'],
        'video/x-msvideo' => ['avi'],
        'video/quicktime' => ['mov'],

        // --- Audio ---
        'audio/mpeg'      => ['mp3'],
        'audio/wav'       => ['wav'],
        'audio/ogg'       => ['ogg'],
        'audio/flac'      => ['flac'],
        'audio/mp4'       => ['m4a'],

        // --- Documentos de Texto y Lectura ---
        'application/pdf' => ['pdf'],
        'application/x-pdf' => ['pdf'],
        'text/plain'      => ['txt', 'csv'],
        'text/csv'        => ['csv'],

        // --- Documentos de Office (Word, Excel, PowerPoint) ---
        'application/msword'                                                        => ['doc'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => ['docx'],
        'application/vnd.ms-excel'                                                  => ['xls'],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => ['xlsx'],
        'application/vnd.ms-powerpoint'                                             => ['ppt'],
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => ['pptx'],

        // --- Archivos Comprimidos ---
        'application/zip'              => ['zip'],
        'application/x-rar-compressed' => ['rar'],
        'application/x-7z-compressed'  => ['7z']
    ];

    // 1. Comprobamos si el interior del archivo está en nuestra lista segura
    if (!array_key_exists($mime_real, $mapa_extensiones_seguras)) {
        echo "<script>alert('Error de seguridad: Formato de archivo no permitido ($mime_real).'); window.history.back();</script>";
        exit();
    }

    // 2. CONTRACCOMPROBACIÓN: ¿La extensión declarada es válida para ese tipo de archivo?
    if (!in_array($extension, $mapa_extensiones_seguras[$mime_real])) {
        echo "<script>alert('Error de seguridad (Incoherencia): Detectado como $mime_real pero la extensión es $extension. Archivo bloqueado.'); window.history.back();</script>";
        exit();
    }
    // ==============================================================================

    // --- FIN DEL FILTRO ---

    //GUARDADO DEL ARCHIVO
    if (move_uploaded_file($tmp_name, $target_dir . $nombre_seguro)) {
        header("Location: leer.php?dir=" . urlencode($dir_req));
        exit();
    } else {
        echo "<script>alert('Error al mover el archivo al disco.'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('Error en la subida del archivo o archivo dañado.'); window.history.back();</script>";
}
?>
