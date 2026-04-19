<?php
//$conexion = new mysqli("localhost", "admin", "123456", "Aeolus_Cloud"); //conexion clase
$conexion = new mysqli("localhost", "root", "", "Aeolus_Cloud"); //conexion  desde casa
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
            echo "<div style='text-align:center; margin-top:50px; font-family:sans-serif;'>";
            echo "<h1>✅ Cuenta verificada con éxito</h1>";
            echo "<p>Ya puedes iniciar sesión en tu nube.</p>";
            echo "<a href='index.html' style='background:#4F46E5; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Ir al Login</a>";
            echo "</div>";
        }
    } else {
        echo "<h1 style='text-align:center; color:red; margin-top:50px; font-family:sans-serif;'>❌ Error: El enlace no es válido o la cuenta ya fue verificada.</h1>";
    }
}
$conexion->close();
?>