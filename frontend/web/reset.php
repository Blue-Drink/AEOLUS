<?php
// Mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Cargar la librería Dotenv para leer el .env
require '../../backend/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../backend');
$dotenv->load();

// Conexión a DB usando variables seguras
$host = $_ENV['DB_HOST'];
$usuario = $_ENV['DB_USER'];
$contrasena = $_ENV['DB_PASS'];
$base_datos = $_ENV['DB_NAME'];

try {
    $conexion = new mysqli($host, $usuario, $contrasena, $base_datos);
} catch (Exception $e) {
    die("🚨 Error crítico: No me puedo conectar a la base de datos. Verifica el archivo .env.");
}

// Variables de estado para controlar qué muestra el HTML
$mensaje = '';
$mostrar_formulario = false;
$token_seguro = '';

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['token'])) {
    $token = trim($_GET['token']);

    // Verificar token y expiración
    $ahora = date('Y-m-d H:i:s');
    $stmt = $conexion->prepare("SELECT id_usuario FROM usuario WHERE token=? AND token_expiracion > ?");
    $stmt->bind_param("ss", $token, $ahora);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $mensaje = "El enlace es inválido, ha expirado o ya ha sido utilizado.";
    } else {
        $mostrar_formulario = true;
        $token_seguro = htmlspecialchars($token);
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['token'])) {
    $token = trim($_POST['token']);
    $clave = $_POST['clave'];
    $confirmar = $_POST['confirmar'];

    if (strlen($clave) < 8) {
        $mensaje = "La contraseña debe tener al menos 8 caracteres.";
    } elseif ($clave !== $confirmar) {
        $mensaje = "Las contraseñas no coinciden. Inténtalo de nuevo.";
    } else {
        // Verificar token de nuevo antes de actualizar
        $ahora = date('Y-m-d H:i:s');
        $stmt = $conexion->prepare("SELECT id_usuario FROM usuario WHERE token=? AND token_expiracion > ?");
        $stmt->bind_param("ss", $token, $ahora);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $id_usuario = $row['id_usuario'];

            $clave_segura = password_hash($clave, PASSWORD_DEFAULT);

            $update = $conexion->prepare("UPDATE usuario SET clave=?, token=NULL, token_expiracion=NULL WHERE id_usuario=?");
            $update->bind_param("si", $clave_segura, $id_usuario);

            if ($update->execute()) {
                error_log("Contraseña restablecida para usuario $id_usuario");
                $mensaje = "¡Tu contraseña se ha actualizado correctamente!";
            } else {
                error_log("Error al restablecer contraseña para usuario $id_usuario: " . $conexion->error);
                $mensaje = "Ha ocurrido un error al actualizar la base de datos.";
            }
        } else {
            $mensaje = "El enlace es inválido, ha expirado o ya ha sido utilizado.";
        }
    }
} else {
    $mensaje = "Acceso no válido.";
}

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="AEOLUS.png">
    <title>AEOLUS Cloud | Restablecer Contraseña</title>
    <link rel="stylesheet" href="estilos.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="body-split">

    <div class="split-container">
        
        <div class="panel-visual">
            <div class="visual-content">
                <div class="logo-wrapper">
                    <img src="AEOLUS.png" alt="Logo Aeolus">
                </div>
                <h1 class="brand-title">AEOLUS <span>Cloud</span></h1>
                <p class="brand-subtitle">Almacenamiento seguro, privado<br>y de alto rendimiento.</p>
            </div>
            <div class="footer-login" style="text-align: center; line-height: 1.6;">
                AEOLUS Project © 2026 | Desarrollo y Sistemas<br>
                <span style="font-size: 0.9em; opacity: 0.85;">Por: Patricia Ortiz, Pablo Porras e Isaac Rios</span>
            </div>
        </div>

        <div class="panel-formulario">
            <div class="form-wrapper">
                
                <?php if ($mostrar_formulario): ?>
                    <form action="reset.php" method="post" class="form-login">
                        <h2>Nueva Contraseña</h2>
                        <p class="form-intro">Introduce tu nueva credencial de acceso. Asegúrate de que sea segura.</p>

                        <input type="hidden" name="token" value="<?php echo $token_seguro; ?>">

                        <div class="input-group">
                            <label for="clave">Nueva Contraseña</label>
                            <div class="password-wrapper">
                                <input type="password" id="clave" name="clave" placeholder="Mínimo 8 caracteres" required minlength="8">
                                <span id="togglePassword" class="toggle-icon">👁️</span>
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="confirmar">Confirmar Contraseña</label>
                            <div class="password-wrapper">
                                <input type="password" id="confirmar" name="confirmar" placeholder="Repite tu contraseña" required minlength="8">
                            </div>
                        </div>

                        <input type="submit" value="Guardar contraseña" class="btn-submit">
                        
                        <div class="form-links" style="margin-top: 20px; justify-content: center;">
                            <a href="./index.html" class="link-secondary">Volver al inicio de sesión</a>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="form-login" style="text-align: center;">
                        <h2>Aviso del Sistema</h2>
                        <p class="form-intro" style="margin-bottom: 30px; font-size: 1.1em; color: #4b5563;">
                            <?php echo htmlspecialchars($mensaje); ?>
                        </p>
                        <a href="index.html" class="btn-submit" style="display: inline-block; text-decoration: none; text-align: center; box-sizing: border-box;">Ir a Iniciar Sesión</a>
                    </div>
                <?php endif; ?>

            </div>
        </div>

    </div>

    <script src="togglePassword.js" defer></script>
</body>
</html>