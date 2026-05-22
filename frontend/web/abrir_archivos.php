<?php
session_start();

// 1. Barrera de seguridad
if (!isset($_SESSION['usuario'])) {
    header("Location: index.html"); 
    exit();
}

if (isset($_GET['file'])) {
    $archivo = basename($_GET['file']);
    $usuario = $_SESSION['usuario'];
    
    // 2. Recogemos en qué subcarpeta estamos (si estás en 'Hola', lo cogerá)
    $dir = isset($_GET['dir']) ? trim(str_replace('..', '', $_GET['dir']), '/\\') : '';
    
    // 3. RUTA ABSOLUTA MÁGICA: __DIR__ nos da la ruta exacta de la carpeta actual
    $ruta_base = __DIR__ . "/../../backend/uploads/" . $usuario . "/";
    $ruta_completa = $ruta_base . ($dir ? $dir . '/' : '') . $archivo; 

    // 4. Comprobamos si existe
    if (file_exists($ruta_completa) && is_file($ruta_completa)) {
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $ruta_completa);
        finfo_close($finfo);

        // --- CABECERAS PARA ABRIR EN EL NAVEGADOR ---
        header('Content-Type: ' . $mime_type);
        // Usamos INLINE para que el PDF/Imagen se abra en pestaña nueva
        header('Content-Disposition: inline; filename="' . $archivo . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($ruta_completa));

        readfile($ruta_completa);
        exit();
    } else {
        // Truco: Si falla, te dirá exactamente en qué ruta lo estaba buscando
        $_SESSION['error'] = "El archivo no existe en la ruta: " . $ruta_completa;
        header("Location: leer.php?dir=" . urlencode($dir));
        exit();
    }
} else {
    header("Location: leer.php");
    exit();
}
?>
