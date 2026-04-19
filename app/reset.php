<?php
// Mostrar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Conexión a DB
$host = "localhost";
$usuario = "root";
$contrasena = "";
$base_datos = "Aeolus_Cloud";

$conexion = new mysqli($host, $usuario, $contrasena, $base_datos);
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['token'])) {
    $token = $_GET['token'];

    // Verificar token
    $stmt = $conexion->prepare("SELECT id_usuario FROM usuario WHERE token=?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $message = "Token inválido o expirado.";
    } else {
        // Mostrar formulario
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Restablecer Contraseña</title>
            <link rel="stylesheet" href="estilos.css">
        </head>
        <body class="body-login">
            <table id="banner">
                <tr>
                    <td>Restablecer contraseña</td>
                </tr>
            </table>

            <div class="logo-container">
                <img src="AEOLUS.png" alt="Logo Aeolus">
            </div>

            <form action="reset.php" method="post" class="form-login">
                <h2 style="text-align:center; color:#333; margin-top:0;">Nueva Contraseña</h2>

                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <label style="display:block; margin-bottom:5px; color:#666;">Nueva Contraseña</label>
                <input type="password" name="clave" required placeholder="Ingrese nueva contraseña" style="width:100%; margin-bottom:15px; box-sizing:border-box;">

                <label style="display:block; margin-bottom:5px; color:#666;">Confirmar Contraseña</label>
                <input type="password" name="confirmar" required placeholder="Confirme la contraseña" style="width:100%; margin-bottom:15px; box-sizing:border-box;">

                <input type="submit" value="Restablecer">

                <div style="text-align:center; margin-top:15px;">
                    <a href="./index.html" style="font-size:0.9em;">Volver al inicio de sesión</a>
                </div>
            </form>

            <div class="footer-login">
                Proyecto hecho por Pablo Porras, Isaac Rios y Patricia Ortiz © 2026
            </div>
        </body>
        </html>
        <?php
        exit();
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['token'])) {
    $token = $_POST['token'];
    $clave = $_POST['clave'];
    $confirmar = $_POST['confirmar'];

    if ($clave !== $confirmar) {
        $message = "Las contraseñas no coinciden.";
    } else {
        // Verificar token y actualizar
        $stmt = $conexion->prepare("SELECT id_usuario FROM usuario WHERE token=?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $id_usuario = $row['id_usuario'];

            $clave_segura = password_hash($clave, PASSWORD_DEFAULT);

            $update = $conexion->prepare("UPDATE usuario SET clave=?, token=NULL WHERE id_usuario=?");
            $update->bind_param("si", $clave_segura, $id_usuario);

            if ($update->execute()) {
                $message = "Contraseña restablecida exitosamente. <a href='index.html'>Iniciar sesión</a>";
            } else {
                $message = "Error al restablecer la contraseña.";
            }
        } else {
            $message = "Token inválido.";
        }
    }
} else {
    $message = "Acceso no válido.";
}

$conexion->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer Contraseña</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body class="body-login">
    <div style="text-align:center; margin-top:50px;">
        <p><?php echo $message; ?></p>
    </div>
</body>
</html>