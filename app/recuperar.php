<?php
// Mostrar errores (útil para diagnosticar)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Cargar la librería PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

// Recibir datos del formulario
$email = $_POST['email'];

// Conexión a Aeolus Cloud (Credenciales locales de XAMPP)
$host = "localhost";
$usuario = "root"; 
$contrasena = ""; 
$base_datos = "Aeolus_Cloud";

$conexion = new mysqli($host, $usuario, $contrasena, $base_datos);
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Verificar si el email existe
$check = $conexion->prepare("SELECT id_usuario, nombre FROM usuario WHERE email=?");
$check->bind_param("s", $email);
$check->execute();
$result = $check->get_result();

if ($result->num_rows == 0) {
    // Si no existe, no damos pistas a los hackers, simplemente decimos que si existe se envió.
    // Pero por ahora en desarrollo, dejamos la alerta visual.
    echo "<script>alert('El correo electrónico no está registrado.'); window.history.back();</script>";
    exit();
}

$row = $result->fetch_assoc();
$id_usuario = $row['id_usuario'];
$nombre = $row['nombre'];

// Generar token seguro de 64 caracteres para reset
$token = bin2hex(random_bytes(32));

// Actualizar el token en la base de datos
$stmt = $conexion->prepare("UPDATE usuario SET token=? WHERE id_usuario=?");
$stmt->bind_param("si", $token, $id_usuario);

if (!$stmt->execute()) {
    echo "<script>alert('Error al generar el token. Inténtalo de nuevo.'); window.history.back();</script>";
    exit();
}

// Enviar email con el enlace de reset
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'pablojoseporras23@gmail.com'; //  credenciales reales
    $mail->Password   = 'ojgmlkondkpjwgba'; //  contraseña de aplicación
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('pablojoseporras23@gmail.com', 'Aeolus Cloud');
    $mail->addAddress($email, $nombre);

    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = 'Recuperación de contraseña - Aeolus Cloud';
    
    
    // Enlace para XAMPP local. ¡Cambiar a 10.10.20.62 antes de subir a GitHub!
    $reset_link = "http://localhost:8080/AEOLUS/app/reset.php?token=" . $token; 
    
    // Diseño del correo Premium
    $mail->Body = "
        <h2>Hola, $nombre.</h2>
        <p>Has solicitado restablecer tu contraseña en Aeolus Cloud.</p>
        <p>Haz clic en el siguiente botón para crear una nueva contraseña:</p>
        <br>
        <a href='$reset_link' style='background-color: #4F46E5; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Restablecer mi contraseña</a>
        <br><br>
        <p>Si no has sido tú o has recordado tu contraseña, simplemente ignora este correo.</p>
    ";

    $mail->send();
    
    // Pantalla de Éxito Premium (Sin alerts)
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <title>Recuperación - Aeolus Cloud</title>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f3f4f6; margin: 0; }
            .tarjeta { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center; max-width: 450px; }
            .icono { font-size: 70px; margin-bottom: 10px; }
            h2 { color: #1f2937; margin-bottom: 10px; }
            p { color: #4b5563; line-height: 1.5; }
            .boton { display: inline-block; margin-top: 25px; padding: 12px 24px; background-color: #4F46E5; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; transition: background 0.3s; }
            .boton:hover { background-color: #4338ca; }
        </style>
    </head>
    <body>
        <div class='tarjeta'>
            <div class='icono'>📩🔑</div>
            <h2>Correo enviado</h2>
            <p>Si el correo electrónico coincide con una cuenta existente, te hemos enviado un enlace para restablecer tu contraseña.</p>
            <p>Por favor, revisa tu bandeja de entrada.</p>
            <a href='index.html' class='boton'>Volver al inicio</a>
        </div>
    </body>
    </html>";

} catch (Exception $e) {
    echo "Error al enviar el correo: {$mail->ErrorInfo}";
}

$conexion->close();
?>