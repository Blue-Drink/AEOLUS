<?php
session_start();

// 1. Configuración de conexión para Aeolus Cloud
$host = "localhost"; 
$usuario = "admin"; 
$contrasena = "123456"; 
$base_datos = "Aeolus_Cloud";

$conexion = new mysqli($host, $usuario, $contrasena, $base_datos);

// Si hay error de conexión, que nos lo diga
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$u = $_POST['usuario'];
$c = $_POST['clave'];

// 2. Consulta a la tabla 'usuario' (en singular)
$stmt = $conexion->prepare("SELECT clave FROM usuario WHERE usuario = ?");
$stmt->bind_param("s", $u);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 1) {
    $fila = $resultado->fetch_assoc();
    // 3. Verificar el Hash de la contraseña
    if (password_verify($c, $fila['clave'])) {
        $_SESSION['usuario'] = $u;
        // Redirigimos al listado tras el éxito
        header("Location: listado.php"); 
        exit();
    }
}

// Si llega aquí es que ha fallado el login
echo "<script>alert('Usuario o clave incorrectos'); window.location='index.html';</script>";
$conexion->close();
?>
