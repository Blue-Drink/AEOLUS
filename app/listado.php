<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: index.html");
    exit();
}
// Redirige al gestor principal
header("Location: leer.php");
exit();
?>