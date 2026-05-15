<?php
// Mostrar errores (útil para diagnosticar)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Cargar la librería PHPMailer y Dotenv
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

// Inicializar la caja fuerte (.env)
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Recibir datos del formulario
$u = $_POST['usuario'];
$email = $_POST['email']; 
$n = $_POST['nombre'];
$c = $_POST['clave'];

// Encriptar clave y generar token seguro de 64 caracteres
$clave_segura = password_hash($c, PASSWORD_DEFAULT);
$token = bin2hex(random_bytes(32)); 

// Conexión a Aeolus Cloud usando variables seguras (.env)
$host = $_ENV['DB_HOST'];
$usuario = $_ENV['DB_USER']; 
$contrasena = $_ENV['DB_PASS']; 
$base_datos = $_ENV['DB_NAME'];

$conexion = new mysqli($host, $usuario, $contrasena, $base_datos);
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// 1. Verificar si el usuario o el email ya existen
$check = $conexion->prepare("SELECT usuario FROM usuario WHERE usuario=? OR email=?"); 
$check->bind_param("ss", $u, $email); 
$check->execute();

if($check->get_result()->num_rows > 0){
    echo "<script>alert('Ese usuario o correo ya están registrados. Prueba con otro.'); window.history.back();</script>";
    exit();
}

// 2. Insertar el nuevo usuario con verificado = 0
$stmt = $conexion->prepare("INSERT INTO usuario (usuario, email, clave, nombre, verificado, token) VALUES (?, ?, ?, ?, 0, ?)"); 
$stmt->bind_param("sssss", $u, $email, $clave_segura, $n, $token); 

if ($stmt->execute()) {

    // 3. Crear su carpeta personal
    $ruta = "uploads/" . $u;
    if (!file_exists($ruta)) {
        mkdir($ruta, 0775, true);
    }

    // 4. Configurar y enviar el correo con PHPMailer
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

        $mail->setFrom($_ENV['SMTP_USER'], 'Aeolus Cloud'); 
        $mail->addAddress($email, $n); 

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8'; 
        $mail->Subject = 'Verifica tu cuenta en Aeolus Cloud';
        
        // --- ⚠️ ATENCIÓN SYSADMIN ⚠️ ---
        // Enlace para XAMPP local. ¡Cambiar a 10.10.20.62 antes de subir a GitHub!
        $enlace = "https://debian-aeolus.taildaa0bc.ts.net/AEOLUS/app/verificar.php?token=" . $token;
        
        $mail->Body = "
            <h2>¡Bienvenido a Aeolus Cloud, $n!</h2>
            <p>Gracias por registrarte. Para poder iniciar sesión, necesitas verificar tu correo electrónico haciendo clic en el siguiente enlace:</p>
            <br>
            <a href='$enlace' style='background-color: #4F46E5; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Verificar mi cuenta</a>
            <br><br>
            <p>Si el botón no funciona, copia y pega esto en tu navegador: <br> $enlace</p>
        ";

        $mail->send();
        
        // --- INICIO DE LA NUEVA PANTALLA DE ÉXITO PREMIUM ---
        echo "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <title>Registro Exitoso - Aeolus Cloud</title>
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
                <div class='icono'>✉️✅</div>
                <h2>¡Casi listo, $n!</h2>
                <p>Tu cuenta ha sido creada con éxito en Aeolus Cloud.</p>
                <p>Para proteger tu seguridad, te hemos enviado un enlace de verificación a <strong>$email</strong>. Por favor, revisa tu bandeja de entrada (o la carpeta de Spam) para activarla.</p>
                <a href='index.html' class='boton'>Volver al inicio</a>
            </div>
        </body>
        </html>";
        // --- FIN DE LA NUEVA PANTALLA DE ÉXITO PREMIUM ---

    } catch (Exception $e) {
        echo "Error al enviar el correo: {$mail->ErrorInfo}";
    }

} else {
    echo "Hubo un error al registrarse.";
}

$stmt->close();
$conexion->close(); 
?>
