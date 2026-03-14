<?php
session_start();
if (!isset($_SESSION['usuario'])) { header("Location: index.html"); exit(); }

$base = "uploads/" . $_SESSION['usuario'] . "/";
$dir_req = isset($_POST['directorio_destino']) ? trim(str_replace('..', '', $_POST['directorio_destino']), '/\\') : '';
$target_dir = $base . $dir_req;

if (!file_exists($target_dir)) $target_dir = $base;
else $target_dir .= "/";

if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] == 0) {
    $nombre = basename($_FILES['archivo']['name']);
    $nombre = preg_replace("/[^a-zA-Z0-9\._-]/", "_", $nombre);
    
    // --- INICIO DEL FILTRO DE SEGURIDAD RECUPERADO ---
    $tmp_name = $_FILES['archivo']['tmp_name'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_real = finfo_file($finfo, $tmp_name);
    finfo_close($finfo);
    
    // Lista blanca de formatos (puedes añadir más si tu equipo lo necesita)
    $formatos_permitidos = [
        'image/jpeg', 'image/png', 'application/pdf', 
        'video/mp4', 'text/plain'
    ];
    
    if (!in_array($mime_real, $formatos_permitidos)) {
        echo "<script>alert('Error de seguridad: Formato de archivo no permitido ($mime_real).'); window.history.back();</script>";
        exit();
    }
    // --- FIN DEL FILTRO ---

    if (move_uploaded_file($tmp_name, $target_dir . $nombre)) {
        header("Location: leer.php?dir=" . urlencode($dir_req));
        exit();
    } else {
        echo "Error al mover el archivo.";
    }
} else {
    echo "Error en la subida.";
}
?>