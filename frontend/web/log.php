<?php
// Mostrar errores para diagnosticar más fácil si algo falla
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// 1. Abrir la caja fuerte (.env) para conseguir las contraseñas de esta máquina
require '../../backend/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../backend');
$dotenv->load();

$host = $_ENV['DB_HOST'];
$usuario_db = $_ENV['DB_USER'];
$contrasena_db = $_ENV['DB_PASS'];
$base_datos = $_ENV['DB_NAME'];

// 2. Intentar la conexión de forma segura (Atrapando el error para evitar el 500)
try {
    $conexion = new mysqli($host, $usuario_db, $contrasena_db, $base_datos);
} catch (Exception $e) {
    die("🚨 Error crítico: No me puedo conectar a la base de datos. Verifica el archivo .env.");
}

$u = $_POST['usuario'];
$c = $_POST['clave'];

// RECUPERADO: Añadimos 'verificado' a la consulta SQL
$stmt = $conexion->prepare("SELECT clave, verificado FROM usuario WHERE usuario = ?");
$stmt->bind_param("s", $u);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 1) {
    $fila = $resultado->fetch_assoc();

    if (password_verify($c, $fila['clave'])) {

        // RECUPERADO: El muro de seguridad para usuarios no verificados
        if ($fila['verificado'] == 0) {
            echo "<script>alert('Debes verificar tu correo electrónico antes de entrar.'); window.location='index.html';</script>";
            exit();
        }

        $_SESSION['usuario'] = $u;
        header("Location: listado.php");
        exit();
    }
}

echo "<script>alert('Usuario o clave incorrectos'); window.location='index.html';</script>";

$stmt->close();
$conexion->close();
?>
