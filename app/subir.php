<?php
session_start();
if (!isset($_SESSION['usuario'])) { header("Location: index.html"); exit(); }

$base = "uploads/" . $_SESSION['usuario'] . "/";
$dir_req = isset($_POST['directorio_destino']) ? trim(str_replace('..', '', $_POST['directorio_destino']), '/\\') : '';
$target_dir = $base . $dir_req;

if (!file_exists($target_dir)) $target_dir = $base;
else $target_dir .= "/";

if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] == 0) {
    
    // Límite de tamaño (20 MB máximo)
    $max_size = 20 * 1024 * 1024;
    if ($_FILES['archivo']['size'] > $max_size) {
        echo "<script>alert('Error: El archivo es demasiado grande (Máximo 20MB).'); window.history.back();</script>";
        exit();
    }

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
    // No basta con que el MIME esté en la lista general. Debe coincidir con lo que dice el usuario.

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
        'text/plain'      => ['txt'],
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
        echo "<script>alert(\"Error de seguridad: El interior del archivo ($mime_real) no esta en la lista blanca de la nube Aeolus.\"); window.history.back();</script>";
        exit();
    }

    // 2. CONTRACCOMPROBACIÓN: ¿La extensión declarada es válida para ese tipo de archivo?
    // Tu prueba ('text/plain' vs '.jpg') morirá aquí.
    if (!in_array($extension, $mapa_extensiones_seguras[$mime_real])) {
        echo "<script>alert(\"Error de seguridad (Incoherencia): El archivo ha sido detectado internamente como [$mime_real] pero la extensión es [$extension]. Esto es sospechoso y ha sido bloqueado.\"); window.history.back();</script>";
        exit();
    }
    // ==============================================================================
    
    // Se comprueba si el interior del archivo coincide con nuestra lista blanca
    if (!in_array($mime_real, $formatos_permitidos)) {
        echo "<script>alert('Error de seguridad: Formato de archivo no permitido ($mime_real).'); window.history.back();</script>";
        exit();
    }
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