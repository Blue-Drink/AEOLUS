<?php
session_start();
if (!isset($_SESSION['usuario'])) exit("No autorizado");

$base = "uploads/" . $_SESSION['usuario'] . "/";
// Recuperar directorio actual para volver ahí
$dir_actual = isset($_GET['dir']) ? $_GET['dir'] : '';

if (isset($_GET['eliminar'])) {
    // Seguridad: asegurar que solo borra dentro de su carpeta
    $archivo = basename($_GET['eliminar']); 
    $ruta_completa = $base . ($dir_actual ? $dir_actual . '/' : '') . $archivo;

    if (file_exists($ruta_completa)) {
        if (is_dir($ruta_completa)) {
            @rmdir($ruta_completa); // Borra carpeta si está vacía
        } else {
            unlink($ruta_completa); // Borra archivo
        }
    }
}

// Redirigir de vuelta a leer.php manteniendo la ruta
header("Location: leer.php?dir=" . urlencode($dir_actual));
exit();
?>