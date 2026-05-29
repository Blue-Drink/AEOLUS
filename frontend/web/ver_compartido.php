<?php
session_start();
if (!isset($_SESSION['usuario']) || empty($_GET['f'])) {
    die("Acceso denegado.");
}

$ruta_fisica = $_GET['f'];

if (file_exists($ruta_fisica)) {
    // Detectar el tipo de archivo (pdf, txt, jpg...)
    $mime = mime_content_type($ruta_fisica);
    header('Content-Type: ' . $mime);
    
    // Si viene por el botón de descargar, forzamos descarga
    if (isset($_GET['descargar'])) {
        header('Content-Disposition: attachment; filename="' . basename($ruta_fisica) . '"');
    } else {
        header('Content-Disposition: inline; filename="' . basename($ruta_fisica) . '"');
    }
    
    // Leer y mostrar el archivo
    readfile($ruta_fisica);
} else {
    echo "<h1>Error 404: El archivo no existe físicamente en el servidor.</h1>";
}
?>
