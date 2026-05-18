<?php
session_start();

// 1. Comprobar que el usuario está logueado usando la variable correcta de vuestro sistema
if (!isset($_SESSION['usuario'])) {
    die("Acceso denegado. No se encontró la sesión.");
}

// Cargar variables de entorno y conexión
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$conexion = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASS'], $_ENV['DB_NAME']);
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// 2. Obtener el ID numérico del propietario (tú) a partir de tu nombre de usuario
$nombre_propietario = $_SESSION['usuario'];
$stmt_prop = $conexion->prepare("SELECT id_usuario FROM usuario WHERE usuario = ?");
$stmt_prop->bind_param("s", $nombre_propietario);
$stmt_prop->execute();
$resultado_prop = $stmt_prop->get_result();

if ($resultado_prop->num_rows === 0) {
    die("Error: Usuario propietario no encontrado en la base de datos.");
}

$fila_prop = $resultado_prop->fetch_assoc();
$id_propietario = $fila_prop['id_usuario'];

// 3. Recoger los datos del formulario modal
$email_receptor = filter_var(trim($_POST['email_receptor']), FILTER_SANITIZE_EMAIL);
$ruta_archivo = $_POST['ruta_archivo'];
$nombre_archivo = $_POST['nombre_archivo'];

if (empty($email_receptor) || empty($ruta_archivo)) {
    echo "<script>alert('Faltan datos.'); window.history.back();</script>";
    exit();
}

// 4. Buscar si el correo del receptor existe en nuestra plataforma
$stmt = $conexion->prepare("SELECT id_usuario FROM usuario WHERE email = ? AND verificado = 1");
$stmt->bind_param("s", $email_receptor);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    echo "<script>alert('El usuario con ese correo no existe o no está verificado en Aeolus.'); window.history.back();</script>";
    exit();
}

$fila_receptor = $resultado->fetch_assoc();
$id_receptor = $fila_receptor['id_usuario'];

// Evitar que te compartas un archivo a ti mismo
if ($id_propietario === $id_receptor) {
    echo "<script>alert('No puedes compartirte un archivo a ti mismo.'); window.history.back();</script>";
    exit();
}

// 5. Comprobar que no se lo hayas compartido ya antes (evitar duplicados)
$check_duplicado = $conexion->prepare("SELECT id_compartido FROM archivos_compartidos WHERE id_propietario = ? AND id_receptor = ? AND ruta_relativa = ?");
$check_duplicado->bind_param("iis", $id_propietario, $id_receptor, $ruta_archivo);
$check_duplicado->execute();

if ($check_duplicado->get_result()->num_rows > 0) {
    echo "<script>alert('Este archivo ya está compartido con ese usuario.'); window.history.back();</script>";
    exit();
}

// 6. ¡Todo correcto! Insertar en la base de datos
$insert = $conexion->prepare("INSERT INTO archivos_compartidos (id_propietario, id_receptor, nombre_archivo, ruta_relativa) VALUES (?, ?, ?, ?)");
$insert->bind_param("iiss", $id_propietario, $id_receptor, $nombre_archivo, $ruta_archivo);

if ($insert->execute()) {
    echo "<script>alert('¡Archivo compartido con éxito!'); window.history.back();</script>";
} else {
    echo "<script>alert('Error al compartir el archivo.'); window.history.back();</script>";
}

$conexion->close();
?>