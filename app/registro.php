<?php
// Mostrar errores (útil para diagnosticar)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Cargar la librería PHPMailer que acabamos de instalar
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

// Recibir datos del formulario
$u = $_POST['usuario'];
$email = $_POST['email'];
$n = $_POST['nombre'];
$c = $_POST['clave'];

// Encriptar clave y generar token seguro de 64 caracteres
$clave_segura = password_hash($c, PASSWORD_DEFAULT);
$token = bin2hex(random_bytes(32)); 

// Conexión a Aeolus Cloud
$host = "localhost";
$usuario = "admin";
$contrasena = "123456";
$base_datos = "Aeolus_Cloud";

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
        $mail->Username   = 'pablojoseporras23@gmail.com'; // <--- CAMBIA ESTO
        $mail->Password   = 'ojgmlkondkpjwgba'; // <--- CAMBIA ESTO
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('pablojoseporras23@gmail.com', 'Aeolus Cloud'); // <--- CAMBIA ESTO
        $mail->addAddress($email, $n);

        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8'; // Para que funcionen las tildes
        $mail->Subject = 'Verifica tu cuenta en Aeolus Cloud';
        
        // El enlace que se enviará al usuario (usando tu IP de Debian)
        $enlace = "http://10.10.20.62/verificar.php?token=" . $token;
        
        $mail->Body = "
            <h2>¡Bienvenido a Aeolus Cloud, $n!</h2>
            <p>Gracias por registrarte. Para poder iniciar sesión, necesitas verificar tu correo electrónico haciendo clic en el siguiente enlace:</p>
            <br>
            <a href='$enlace' style='background-color: #4F46E5; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Verificar mi cuenta</a>
            <br><br>
            <p>Si el botón no funciona, copia y pega esto en tu navegador: <br> $enlace</p>
        ";

        $mail->send();
        echo "<script>alert('¡Registro exitoso! Revisa tu correo electrónico para verificar la cuenta.'); window.location='index.html';</script>";
    } catch (Exception $e) {
        echo "Error al enviar el correo: {$mail->ErrorInfo}";
    }

} else {
    echo "Hubo un error al registrarse.";
}

$stmt->close();
$conexion->close();
?>
