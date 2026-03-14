<?php
session_start();

$host = "localhost"; 
$usuario = "admin"; 
$contrasena = "123456"; 
$base_datos = "Aeolus_Cloud";

$conexion = new mysqli($host, $usuario, $contrasena, $base_datos);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
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
$conexion->close();
?>