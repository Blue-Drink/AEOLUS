<?php
// Mostrar errores (útil para diagnosticar)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Cargar la librería Dotenv (Caja fuerte)
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Conexión a Aeolus Cloud usando variables seguras (.env)
$host = $_ENV['DB_HOST'];
$usuario = $_ENV['DB_USER']; 
$contrasena = $_ENV['DB_PASS']; 
$base_datos = $_ENV['DB_NAME'];

$conexion = new mysqli($host, $usuario, $contrasena, $base_datos);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $stmt = $conexion->prepare("SELECT id_usuario FROM usuario WHERE token = ? AND verificado = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $actualizar = $conexion->prepare("UPDATE usuario SET verificado = 1, token = NULL WHERE token = ?");
        $actualizar->bind_param("s", $token);
        if ($actualizar->execute()) {
            
            echo "<!DOCTYPE html>
            <html lang='es'>
            <head>
                <meta charset='UTF-8'>
                <title>Cuenta Verificada - Aeolus Cloud</title>
                <style>
                    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f3f4f6; margin: 0; }
                    .tarjeta { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center; max-width: 450px; }
                    .icono { font-size: 70px; margin-bottom: 10px; }
                    h1 { color: #1f2937; margin-bottom: 10px; font-size: 24px; }
                    p { color: #4b5563; line-height: 1.5; margin-bottom: 25px; }
                    .boton { display: inline-block; padding: 12px 24px; background-color: #4F46E5; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; transition: background 0.3s; }
                    .boton:hover { background-color: #4338ca; }
                </style>
            </head>
            <body>
                <div class='tarjeta'>
                    <div class='icono'>✅☁️</div>
                    <h1>Cuenta verificada con éxito</h1>
                    <p>Tu identidad ha sido confirmada. Ya puedes acceder a tu espacio seguro en Aeolus Cloud.</p>
                    <a href='index.html' class='boton'>Ir al Login</a>
                </div>
            </body>
            </html>";
        }
    } else {
        echo "<!DOCTYPE html>
            <html lang='es'>
            <head>
                <meta charset='UTF-8'>
                <title>Error de Verificación</title>
                <style>
                    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f3f4f6; margin: 0; }
                    .tarjeta { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center; max-width: 450px; border-top: 5px solid #ef4444; }
                    .icono { font-size: 60px; margin-bottom: 10px; }
                    h1 { color: #991b1b; margin-bottom: 10px; font-size: 22px; }
                    p { color: #4b5563; line-height: 1.5; margin-bottom: 25px; }
                </style>
            </head>
            <body>
                <div class='tarjeta'>
                    <div class='icono'>❌</div>
                    <h1>Enlace no válido o caducado</h1>
                    <p>Es posible que esta cuenta ya haya sido verificada anteriormente o que el enlace de tu correo esté incompleto.</p>
                    <a href='index.html' style='color: #4F46E5; text-decoration: none; font-weight: bold;'>Volver al inicio</a>
                </div>
            </body>
            </html>";
    }
}
$conexion->close();
?>
