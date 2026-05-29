<?php
// Mostrar errores solo en desarrollo
if ($_SERVER['SERVER_NAME'] === 'localhost') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

// Cargar la librería PHPMailer y Dotenv
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../../backend/vendor/autoload.php';
// Inicializar la caja fuerte (.env)
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../backend');
$dotenv->load();

// Sanitizar y validar email
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "<script>alert('Correo electrónico inválido.'); window.history.back();</script>";
    exit();
}

// Conexión a Aeolus Cloud usando variables seguras (.env)
$host = $_ENV['DB_HOST'];
$usuario = $_ENV['DB_USER'];
$contrasena = $_ENV['DB_PASS'];
$base_datos = $_ENV['DB_NAME'];

$conexion = new mysqli($host, $usuario, $contrasena, $base_datos);
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Verificar si el email existe y obtener datos
$check = $conexion->prepare("SELECT id_usuario, nombre FROM usuario WHERE email=? AND verificado=1");
$check->bind_param("s", $email);
$check->execute();
$result = $check->get_result();

if ($result->num_rows == 0) {
    // No dar pistas sobre si existe o no (seguridad)
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
            <p>Si el correo electrónico coincide con una cuenta existente y verificada, te hemos enviado un enlace para restablecer tu contraseña.</p>
            <p>Por favor, revisa tu bandeja de entrada y la carpeta de spam.</p>
            <a href='index.html' class='boton'>Volver al inicio</a>
        </div>
    </body>
    </html>";
    exit();
}

$row = $result->fetch_assoc();
$id_usuario = $row['id_usuario'];
$nombre = $row['nombre'];

// Generar token seguro y expiración (1 hora)
$token = bin2hex(random_bytes(32));
$expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));

// Actualizar el token y expiración en la base de datos
$stmt = $conexion->prepare("UPDATE usuario SET token=?, token_expiracion=? WHERE id_usuario=?");
$stmt->bind_param("ssi", $token, $expiracion, $id_usuario);

if (!$stmt->execute()) {
    error_log("Error al actualizar token para usuario $id_usuario: " . $conexion->error);
    echo "<script>alert('Error interno. Inténtalo de nuevo.'); window.history.back();</script>";
    exit();
}

// Enviar email con el enlace de reset
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    
    // Credenciales inyectadas desde la caja fuerte (.env)
    $mail->Username   = $_ENV['SMTP_USER'];
    $mail->Password   = $_ENV['SMTP_PASS'];
    
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom($_ENV['SMTP_USER'], 'Aeolus Cloud'); // También usamos la variable aquí por limpieza
    $mail->addAddress($email, $nombre);

    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = 'Recuperación de contraseña - Aeolus Cloud';
    
    // Enlace para XAMPP local. ¡Cambiar a 10.10.20.62 antes de subir a GitHub!
    $reset_link = "http://debian.taildaa0bc.ts.net/reset.php?token=" . $token;    
    // Diseño del correo Premium
    $mail->Body = "
        <h2>Hola, $nombre.</h2>
        <p>Has solicitado restablecer tu contraseña en Aeolus Cloud.</p>
        <p>Haz clic en el siguiente botón para crear una nueva contraseña:</p>
        <br>
        <a href='$reset_link' style='background-color: #4F46E5; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Restablecer mi contraseña</a>
        <br><br>
        <p>Este enlace expirará en 1 hora por seguridad.</p>
        <p>Si no has sido tú o has recordado tu contraseña, simplemente ignora este correo.</p>
    ";

    $mail->send();
    
    // Log exitoso
    error_log("Email de recuperación enviado a $email para usuario $id_usuario");
    
    // Pantalla de Éxito Premium
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
            <p>Si el correo electrónico coincide con una cuenta existente y verificada, te hemos enviado un enlace para restablecer tu contraseña.</p>
            <p>Por favor, revisa tu bandeja de entrada y la carpeta de spam.</p>
            <a href='index.html' class='boton'>Volver al inicio</a>
        </div>
    </body>
    </html>";

} catch (Exception $e) {
    error_log("Error al enviar email de recuperación a $email: " . $mail->ErrorInfo);
    echo "<script>alert('Error al enviar el correo. Inténtalo de nuevo más tarde.'); window.history.back();</script>";
}

$conexion->close();
?>
