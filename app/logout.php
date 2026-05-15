<?php
// 1. Iniciamos la sesión para poder destruirla
session_start();

// 2. Limpiamos todas las variables de sesión en memoria
$_SESSION = array();

// 3. Destruimos la cookie de sesión en el navegador del cliente
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000, // Ponemos la fecha de expiración en el pasado
        $params["path"], 
        $params["domain"], 
        $params["secure"], 
        $params["httponly"]
    );
}

// 4. Destruimos la sesión en el servidor
session_destroy();

// 5. Redirigimos al inicio y evitamos que se ejecute más código
header("Location: index.html");
exit();
?>

