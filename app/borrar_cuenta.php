<?php
// Borrado de archivos físicos del usuario y su registro en la base de datos.
session_start();

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: index.html");
    exit();
}

// 1. Obtener datos de la sesión
$u = $_SESSION['usuario'];

// 2. Borrado Físico (Misma ruta que en leer.php y registro.php)
$ruta_personal = "uploads/" . $u;

if (file_exists($ruta_personal) && is_dir($ruta_personal)) {
    eliminarDirectorio($ruta_personal);
}

// 3. Cargar el archivo .env para las credenciales de la BD
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// 4. Borrado Lógico (¡Usando las variables seguras!)
$host = $_ENV['DB_HOST'];
$usuario_db = $_ENV['DB_USER'];
$contrasena_db = $_ENV['DB_PASS'];
$base_datos = $_ENV['DB_NAME'];

$conexion = new mysqli($host, $usuario_db, $contrasena_db, $base_datos);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Eliminar el registro de la tabla 'usuario'
$stmt = $conexion->prepare("DELETE FROM usuario WHERE usuario = ?");
$stmt->bind_param("s", $u);

// Evaluamos si falla y obligamos a que nos muestre el error
if (!$stmt->execute()) {
    die("🚨 Error crítico de MySQL al intentar borrar: " . $stmt->error);
}

$stmt->close();
$conexion->close();

// 5. Finalizar sesión y redirigir
session_unset();
session_destroy();

header("Location: index.html?msg=cuenta_eliminada");
exit();

/**
 * Función recursiva para eliminar el árbol de directorios (Misma que en borrar.php)
 */
function eliminarDirectorio($dir) {
    if (!is_dir($dir)) return false;

    $items = scandir($dir);

    foreach ($items as $item) {
        if ($item == '.' || $item == '..') continue;

        $path = $dir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($path)) {
            eliminarDirectorio($path);
        } else {
            unlink($path);
        }
    }
    return @rmdir($dir);
}
?>
