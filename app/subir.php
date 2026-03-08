<?php
//comienza la sesion 
session_start();
if (!isset($_SESSION['usuario'])) { header("Location: index.html"); exit(); }

//Lee el subdirectorio donde se quiere guardar el archivo
$base = "uploads/" . $_SESSION['usuario'] . "/";
$dir_req = isset($_POST['directorio_destino']) ? trim(str_replace('..', '', $_POST['directorio_destino']), '/\\') : '';
//Contruye la ruta  final
$target_dir = $base . $dir_req;

// Asegurar carpeta
if (!file_exists($target_dir)) $target_dir = $base;
else $target_dir .= "/";
// Verifica si se ha subido un archivo sin errores
if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] == 0) {
    $nombre = basename($_FILES['archivo']['name']);
    // Limpiar nombre para evitar problemas
    $nombre = preg_replace("/[^a-zA-Z0-9\._-]/", "_", $nombre);
    
    if (move_uploaded_file($_FILES['archivo']['tmp_name'], $target_dir . $nombre)) {
        // Éxito: volvemos a leer.php
        header("Location: leer.php?dir=" . urlencode($dir_req));
        exit();
    } else {
        echo "Error al mover el archivo.";
    }
} else {
    echo "Error en la subida.";
}
?>