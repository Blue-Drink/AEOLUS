<?php
//Comprueba si hay sesión activa
session_start();
if (!isset($_SESSION['usuario'])) { 
    header("Location: index.html"); 
    exit(); 
}

// Le dice al navegador que no guarde esta página (Anti Caché)
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

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
    
    $_SESSION['mensaje'] = "Archivo renombrado con éxito."; // Añadimos mensaje aquí también
    header("Location: leer.php?dir=" . urlencode($req));
    exit;
}

// --- LÓGICA CREAR CARPETA ---
if (isset($_POST['nueva_carpeta'])) {
    $d = trim(basename($_POST['nueva_carpeta']));
    
    if (empty($d)) {
        $_SESSION['error'] = "Introduzca un nombre para la carpeta.";
    } else {
        $ruta_destino = $current_path . '/' . $d; // Ojo: asegúrate de que usas tu variable real de la ruta actual
        
        if (!file_exists($ruta_destino)) {
            @mkdir($ruta_destino, 0777);
            $_SESSION['mensaje'] = "Carpeta '$d' creada.";
        } else {
            $_SESSION['error'] = "Ya existe una carpeta con el nombre '$d'.";
        }
    }
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
    
    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
        <a href="logout.php" class="logout-btn" style="width: 145px; 
           background-color: #dc3545; color: white; padding: 6px 0; 
           border-radius: 4px; border: 1px solid #dc3545; text-decoration: none; 
           text-align: center; font-size: 0.9em; box-sizing: border-box;">
        Cerrar Sesión
        </a>
        <button onclick="abrirModalBorrado()" style="width: 145px; background-color: white; 
                color: #dc3545; border: 1px solid #dc3545; padding: 6px 0; border-radius: 4px; 
                cursor: pointer; font-size: 0.85em; font-weight: bold; text-align: center; 
                box-sizing: border-box; transition: all 0.3s ease;">
            ¿Eliminar cuenta?
        </button>
    </div>
</div>

<?php if (isset($_SESSION['mensaje'])): ?>
    <div style="background-color: #d1fae5; color: #065f46; padding: 15px; margin-bottom: 20px; border-radius: 6px; border-left: 5px solid #10b981;">
        ✅ <?php echo $_SESSION['mensaje']; ?>
    </div>
    <?php unset($_SESSION['mensaje']); // Lo borramos para que no salga siempre ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div style="background-color: #fee2e2; color: #991b1b; padding: 15px; margin-bottom: 20px; border-radius: 6px; border-left: 5px solid #ef4444;">
        ❌ <?php echo $_SESSION['error']; ?>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>
<div style="display:flex; justify-content:space-between; margin-bottom:20px;">
    <form action="" method="post" style="display:flex; gap:10px;">
        <input type="text" name="nueva_carpeta" placeholder="Nueva Carpeta..." style="margin:0; padding:8px;" required>
        <input type="submit" value="Crear" style="width:auto; padding:8px 15px;">
    </form>

    <a href="subir_formulario.php?dir=<?php echo urlencode($req); ?>" class="logout-btn" style="background-color: var(--primary); text-decoration:none;">
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

            // Si es carpeta, recarga leer.php. Si es archivo, lo envía al portero.
            $link = $isDir ? "leer.php?dir=" . urlencode(($req ? $req.'/' : '') . $f) : "abrir_archivos.php?file=" . urlencode($f) . "&dir=" . urlencode($req);
            
            // Si es un archivo, queremos que intente abrirse en una pestaña nueva de tu navegador (_blank)
            $target = $isDir ? "" : " target='_blank'";
            
            $icon = $isDir ? "📁" : "📄";

            $size = $isDir ? "-" : round(filesize($full)/1024, 2) . " KB";

            echo "<tr>";
            
            // Columna Nombre + Renombrar
            echo "<td>
                    <a href='$link'$target style='font-weight:bold; font-size:1.1em; color: #1f2937; text-decoration: none;'>$icon $f</a>
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
                     onclick=\"return confirm('¿Seguro que quieres borrar $f?');\">🗑️ Borrar</a>";
            echo "</td>";
            echo "</tr>";
        }
        ?>
    </tbody>
</table>

    <div id="modalBorrado" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); justify-content: center; align-items: center; z-index: 1000;">
        <div style="background: white; padding: 30px; border-radius: 8px; max-width: 450px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
            
            <h2 style="color: #dc3545; margin-top: 0;">🚨 Acción Irreversible</h2>
            <p>Estás a punto de eliminar tu cuenta y <strong>TODOS</strong> tus archivos físicos de la nube. Esta acción no se puede deshacer.</p>
            
            <p style="margin-bottom: 20px;">Para confirmar, escribe tu nombre de usuario (<strong><?php echo $_SESSION['usuario']; ?></strong>) a continuación:</p>
            
            <form action="borrar_cuenta.php" method="POST">
                <input type="text" id="inputConfirmacion" placeholder="Escribe tu usuario aquí" onkeyup="validarBorrado('<?php echo $_SESSION['usuario']; ?>')" style="width: 100%; padding: 10px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
                
                <div style="display: flex; justify-content: space-between;">
                    <button type="button" onclick="cerrarModalBorrado()" style="padding: 10px 20px; border: none; background: #6c757d; color: white; border-radius: 4px; cursor: pointer;">
                        Cancelar
                    </button>
                    
                    <button type="submit" id="btnBorrarReal" disabled style="padding: 10px 20px; border: none; background: #dc3545; color: white; border-radius: 4px; cursor: not-allowed; opacity: 0.5;">
                        Eliminar Definitivamente
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Esta es la función que llama al botón de Eliminar Cuenta. 
        // Cambia el 'display' a 'flex' para que se vea.
        function abrirModalBorrado() {
            document.getElementById('modalBorrado').style.display = 'flex';
        }

        // Esta función cierra la ventana y borra lo que hubieras escrito
        function cerrarModalBorrado() {
            document.getElementById('modalBorrado').style.display = 'none';
            document.getElementById('inputConfirmacion').value = ''; 
            validarBorrado('<?php echo $_SESSION['usuario']; ?>'); 
        }

        // Esta función comprueba que escribas bien tu usuario para desbloquear el botón rojo
        function validarBorrado(usuarioCorrecto) {
            const inputTexto = document.getElementById('inputConfirmacion').value;
            const botonBorrar = document.getElementById('btnBorrarReal');
            
            if (inputTexto === usuarioCorrecto) {
                botonBorrar.disabled = false;
                botonBorrar.style.cursor = 'pointer';
                botonBorrar.style.opacity = '1';
            } else {
                botonBorrar.disabled = true;
                botonBorrar.style.cursor = 'not-allowed';
                botonBorrar.style.opacity = '0.5';
            }
        }
    </script>

</body>
</html>