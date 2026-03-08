<?php
session_start();
if (!isset($_SESSION['usuario'])) { header("Location: index.html"); exit(); }

$base_path = "uploads/" . $_SESSION['usuario'] . "/";
// Seguridad en rutas
$req = isset($_GET['dir']) ? trim(str_replace('..', '', $_GET['dir']), '/\\') : '';
$current_path = $base_path . $req;

if (!file_exists($base_path)) mkdir($base_path, 0777, true);
if (!file_exists($current_path)) $current_path = $base_path;

// --- LÓGICA DE RENOMBRAR---
if (isset($_POST['nuevo_nombre']) && isset($_POST['archivo_original'])) {
    $orig = $current_path . '/' . basename($_POST['archivo_original']);
    $dest = $current_path . '/' . basename($_POST['nuevo_nombre']);
    if (file_exists($orig)) rename($orig, $dest);
    header("Location: leer.php?dir=" . urlencode($req));
    exit;
}

// --- LÓGICA CREAR CARPETA ---
if (isset($_POST['nueva_carpeta'])) {
    $d = basename($_POST['nueva_carpeta']);
    if(!empty($d)) @mkdir($current_path . '/' . $d, 0777);
    header("Location: leer.php?dir=" . urlencode($req));
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Archivos</title>
    <link rel="stylesheet" href="estilos.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="body-leer">

<div class="header-container">
    <div>
        <h2>Hola, <?php echo htmlspecialchars($_SESSION['usuario']); ?></h2>
        <small>Ruta: /<?php echo htmlspecialchars($req); ?></small>
    </div>
    <a href="logout.php" class="logout-btn">Cerrar Sesión</a>
</div>

<div style="display:flex; justify-content:space-between; margin-bottom:20px;">
    <form action="" method="post" style="display:flex; gap:10px;">
        <input type="text" name="nueva_carpeta" placeholder="Nueva Carpeta..." style="margin:0; padding:8px;">
        <input type="submit" value="Crear" style="width:auto; padding:8px 15px;">
    </form>

    <a href="subir.html?dir=<?php echo urlencode($req); ?>" class="logout-btn" style="background-color: var(--primary); text-decoration:none;">
        <i class="fas fa-cloud-upload-alt"></i> Subir Archivo
    </a>
</div>

<table class="tabla-leer">
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Tamaño</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Botón Atrás
        if ($req !== '') {
            $parent = dirname($req);
            $back = ($parent == '.' || $parent == '/') ? '' : $parent;
            echo "<tr style='background:#f3f4f6'>
                    <td colspan='3'>
                        <a href='leer.php?dir=".urlencode($back)."'>⬅️ Volver atrás</a>
                    </td>
                  </tr>";
        }

        $files = array_diff(scandir($current_path), ['.', '..']);
        
        foreach ($files as $f) {
            $full = $current_path . '/' . $f;
            $isDir = is_dir($full);
            // Enlace para abrir carpeta o #
            $link = $isDir ? "leer.php?dir=" . urlencode(($req ? $req.'/' : '') . $f) : "#";
            $icon = $isDir ? "📁" : "📄";
            $size = $isDir ? "-" : round(filesize($full)/1024, 2) . " KB";

            echo "<tr>";
            
            // Columna Nombre + Renombrar
            echo "<td>
                    <a href='$link' style='font-weight:bold; font-size:1.1em;'>$icon $f</a>
                    <form method='post' style='display:inline-block; margin-left:15px; opacity:0.6;'>
                        <input type='hidden' name='archivo_original' value='$f'>
                        <input type='text' name='nuevo_nombre' placeholder='Renombrar' style='padding:2px; font-size:12px; width:80px; margin:0;'>
                        <button type='submit' style='cursor:pointer; border:none; background:none;'>✏️</button>
                    </form>
                  </td>";
            
            // Columna Tamaño
            echo "<td>$size</td>";

            // Columna Acciones (Descargar / Borrar)
            echo "<td>";
            if (!$isDir) {
                // Descarga directa 
                echo "<a href='$full' download class='btn-accion btn-descargar'>⬇️ Descargar</a> ";
            }
            // Enlace a borrar.php
            echo "<a href='borrar.php?eliminar=".urlencode($f)."&dir=".urlencode($req)."' 
                     class='btn-accion btn-borrar' 
                     onclick=\"return confirm('¿Borrar $f?');\">🗑️ Borrar</a>";
            echo "</td>";
            echo "</tr>";
        }
        ?>
    </tbody>
</table>

</body>
</html>