<?php
session_start();

// 1. Verificar sesión (Mejora: Nos manda al login en vez de dejarte en una página en blanco)
if (!isset($_SESSION['usuario'])) {
    header("Location: index.html");
    exit();
}

$base = "uploads/" . $_SESSION['usuario'] . "/";
// Recuperar directorio actual para volver ahí
$dir_actual = isset($_GET['dir']) ? $_GET['dir'] : '';

if (isset($_GET['eliminar']) && !empty($_GET['eliminar'])) {
    // Seguridad: asegurar que solo borra dentro de su carpeta
    $archivo = basename($_GET['eliminar']); 
    $ruta_completa = $base . ($dir_actual ? $dir_actual . '/' : '') . $archivo;

    if (file_exists($ruta_completa)) {
        
        // Defensa extra: Evitar borrado accidental de directorios de navegación de Linux
        if ($archivo == "." || $archivo == "..") {
            $_SESSION['error'] = "Operación de borrado no permitida.";
            header("Location: leer.php?dir=" . urlencode($dir_actual));
            exit();
        }

        $exito = false;

        if (is_dir($ruta_completa)) {
            // Usa la función de Isaac para fulminar la carpeta aunque tenga cosas dentro
            $exito = eliminarDirectorio($ruta_completa); 
        } else {
            // Borra archivo normal
            $exito = @unlink($ruta_completa); 
        }

        // Aquí enviamos el mensaje que leer.php atrapará y pintará de verde o rojo
        if ($exito) {
            $_SESSION['mensaje'] = "Elemento eliminado correctamente.";
        } else {
            $_SESSION['error'] = "No se pudo eliminar. Verifica que no esté en uso.";
        }
    } else {
        $_SESSION['error'] = "El archivo o carpeta ya no existe.";
    }
}

// Redirigir de vuelta a leer.php manteniendo la ruta
header("Location: leer.php?dir=" . urlencode($dir_actual));
exit();

/**
 * FUNCIÓN AUXILIAR (Aporte de Isaac): Borra carpetas y su contenido interno
 */
function eliminarDirectorio($dir) {
    if (!is_dir($dir)) return false;

    $items = scandir($dir);
    
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') continue;

        $path = $dir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($path)) {
            eliminarDirectorio($path); // Recursividad
        } else {
            unlink($path); // Borra el archivo interior
        }
    }
    // Finalmente, borra la carpeta ya vacía
    return @rmdir($dir);
}
?>