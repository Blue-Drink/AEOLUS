<?php
session_start();
if (!isset($_SESSION['usuario'])) { header("Location: index.html"); exit(); }

// --- FUNCIÓN BÁSCULA AÑADIDA PARA ISSUE #1 ---
function calcularTamañoDirectorio($ruta) {
    $tamañoTotal = 0;
    if (!file_exists($ruta)) return 0;
    $archivos = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($ruta, FilesystemIterator::SKIP_DOTS));
    foreach ($archivos as $archivo) {
        $tamañoTotal += $archivo->getSize();
    }
    return $tamañoTotal;
}
// ----------------------------------------------

$base_path = "uploads/" . $_SESSION['usuario'] . "/";
// Seguridad en rutas
$req = isset($_GET['dir']) ? trim(str_replace('..', '', $_GET['dir']), '/\\') : '';
$current_path = $base_path . $req;

if (!file_exists($base_path)) mkdir($base_path, 0777, true);
if (!file_exists($current_path)) $current_path = $base_path;

// --- CÁLCULOS DE LA BARRA DE PROGRESO ---
$bytes_usados = calcularTamañoDirectorio("uploads/" . $_SESSION['usuario']);
$megas_usados = round($bytes_usados / 1048576, 2);
$porcentaje = round(($bytes_usados / 1073741824) * 100, 1);
$ancho_barra = min($porcentaje, 100); // Para que no se rompa el diseño si se pasa del 100% por un bug
// ----------------------------------------

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
    <style>
        /* Estilos mínimos para drag & drop */
        .drag-over { background-color: #ecfeff !important; }
        .dragging { opacity: 0.6; }
        .drop-hint { font-size: 0.85em; color: #0ea5a4; margin-left: 8px; }
        tr.item-row { cursor: default; }
    </style>
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

<div style="margin-bottom: 20px; padding: 15px; background: #f3f4f6; border-radius: 8px; box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);">
    <p style="margin: 0 0 5px 0; font-size: 14px; color: #4b5563;">Almacenamiento ocupado: <strong><?php echo $porcentaje; ?>%</strong></p>
    <div style="width: 100%; background-color: #e5e7eb; border-radius: 4px; overflow: hidden;">
        <div style="width: <?php echo $ancho_barra; ?>%; background-color: #4F46E5; height: 10px;"></div>
    </div>
    <p style="margin: 5px 0 0 0; font-size: 12px; color: #6b7280; text-align: right;">
        <?php echo $megas_usados; ?> MB / 1024 MB
    </p>
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
        // Botón Volver (ahora también acepta drop para mover al padre)
        if ($req !== '') {
            $parent = dirname($req);
            $back = ($parent == '.' || $parent == '/') ? '' : $parent;
            $backEsc = htmlspecialchars($back, ENT_QUOTES);
            echo "<tr class='drop-target' data-destdir='$backEsc' data-destname='' style='background:#f3f4f6'>
                    <td colspan='3'>
                        <a href='leer.php?dir=".urlencode($back)."'>⬅️ Volver</a>
                    </td>
                  </tr>";
        }

        // --- Obtener la lista de archivos que compartidos para ponerles la etiqueta ---
        $mis_archivos_compartidos = [];
        require_once 'vendor/autoload.php';
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->safeLoad();
        $conexion_chivato = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASS'], $_ENV['DB_NAME']);
        
        if (!$conexion_chivato->connect_error) {
            $stmt_chivato = $conexion_chivato->prepare("SELECT ruta_relativa FROM archivos_compartidos WHERE id_propietario = (SELECT id_usuario FROM usuario WHERE usuario = ?)");
            $stmt_chivato->bind_param("s", $_SESSION['usuario']);
            $stmt_chivato->execute();
            $res_chivato = $stmt_chivato->get_result();
            while($row = $res_chivato->fetch_assoc()) {
                $mis_archivos_compartidos[] = $row['ruta_relativa'];
            }
            $conexion_chivato->close();
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

            // Código de Isaac para el Drag & Drop
            $safeNameAttr = htmlspecialchars($f, ENT_QUOTES);
            $isDirAttr = $isDir ? 1 : 0;
            // data-destdir / data-destname: si es carpeta, indican la carpeta destino (actual)
            $dataDestDir = htmlspecialchars($req, ENT_QUOTES);
            $dataDestName = $isDir ? $safeNameAttr : '';
            echo "<tr class='item-row' draggable='true' data-name='$safeNameAttr' data-isdir='$isDirAttr' data-destdir='$dataDestDir' data-destname='$dataDestName'>";
            
            
            // --- Comprobar si lleva la pegatina de compartido ---
            // 1. Calculamos la ruta exacta de este archivo (ej: Hola/DNI.pdf)
            $ruta_relativa_completa = ($req ? $req.'/' : '') . $f;
            $etiqueta_compartido = ""; // Por defecto, no hay pegatina
            
            // 2. Si no es una carpeta Y además está en nuestra lista de compartidos, creamos la pegatina
            if (!$isDir && in_array($ruta_relativa_completa, $mis_archivos_compartidos)) {
                $etiqueta_compartido = "<span style='font-size: 0.7em; background-color: #10b981; color: white; padding: 2px 6px; border-radius: 10px; margin-left: 8px; vertical-align: middle;'>🔗 Compartido</span>";
            }

            // Columna Nombre + Renombrar + Etiqueta
            echo "<td>
                    <a href='$link'$target style='font-weight:bold; font-size:1.1em; color: #1f2937; text-decoration: none;'>$icon $f</a>
                    $etiqueta_compartido
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

                // NUEVO: Botón de compartir. Le pasamos el nombre del archivo y la ruta completa (relativa a uploads/)
                $ruta_relativa_completa = ($req ? $req.'/' : '') . $f;
                echo "<button onclick=\"abrirModalCompartir('$f', '$ruta_relativa_completa')\" class='btn-accion btn-descargar' style='background-color:#4F46E5; border:none; color:white; cursor:pointer;'>🤝 Compartir</button> ";
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

<br>
<h3 style="color: #4F46E5; border-bottom: 2px solid #4F46E5; padding-bottom: 5px; margin-top: 30px;">🤝 Compartidos conmigo</h3>
<table class="tabla-leer">
    <thead>
        <tr>
            <th>Nombre del Archivo</th>
            <th>Propietario</th>
            <th>Fecha</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // 1. Conectarnos a la base de datos (usamos require_once por si ya estuviera cargado)
        require_once 'vendor/autoload.php';
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->safeLoad();
        
        $conexion = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASS'], $_ENV['DB_NAME']);
        
        if (!$conexion->connect_error) {
            // 2. Buscar archivos donde tú eres el receptor
            $mi_usuario = $_SESSION['usuario'];
            
            // Hacemos un JOIN para sacar el nombre del propietario en lugar de su ID
            $sql = "SELECT ac.nombre_archivo, ac.ruta_relativa, ac.fecha_compartido, prop.usuario AS nombre_propietario 
                    FROM archivos_compartidos ac 
                    JOIN usuario rec ON ac.id_receptor = rec.id_usuario 
                    JOIN usuario prop ON ac.id_propietario = prop.id_usuario 
                    WHERE rec.usuario = ?";
                    
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("s", $mi_usuario);
            $stmt->execute();
            $compartidos = $stmt->get_result();
            
            if ($compartidos->num_rows > 0) {
                while ($fila = $compartidos->fetch_assoc()) {
                    // Reconstruimos la ruta física: uploads/Propietario/ruta_archivo
                    $ruta_fisica = "uploads/" . $fila['nombre_propietario'] . "/" . $fila['ruta_relativa'];
                    $nombre_amigable = htmlspecialchars($fila['nombre_archivo']);
                    $propietario = htmlspecialchars($fila['nombre_propietario']);
                    
                    // Formatear la fecha
                    $fecha = date('d/m/Y H:i', strtotime($fila['fecha_compartido']));
                    
                    echo "<tr style='background-color: #f8fafc;'>";
                    echo "<td><span style='font-weight:bold; color: #1f2937;'>📄 $nombre_amigable</span></td>";
                    echo "<td>👤 $propietario</td>";
                    echo "<td>$fecha</td>";
                    echo "<td>";
                    
                    // Comprobar que el dueño no haya borrado el archivo físico
                    if (file_exists($ruta_fisica)) {
                        // 1. Botón de Previsualizar (Abre en pestaña nueva gracias a target='_blank')
                        echo "<a href='$ruta_fisica' target='_blank' class='btn-accion btn-descargar' style='background-color: #3b82f6; margin-right: 5px; text-decoration: none;'>👁️ Ver</a>";
                        
                        // 2. Botón de Descargar de siempre
                        echo "<a href='$ruta_fisica' download class='btn-accion btn-descargar' style='background-color: #059669;'>⬇️ Descargar</a>";
                    } else {
                        echo "<span style='color: #dc3545; font-size: 0.85em;'>El propietario eliminó el archivo</span>";
                    }
                    
                    echo "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='4' style='text-align:center; color: #6b7280;'>Nadie ha compartido archivos contigo aún.</td></tr>";
            }
            $conexion->close();
        }
        ?>
    </tbody>
</table>

    <div id="modalCompartir" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); justify-content: center; align-items: center; z-index: 1000;">
        <div style="background: white; padding: 30px; border-radius: 8px; max-width: 450px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
            
            <h2 style="color: #4F46E5; margin-top: 0;">🤝 Compartir Archivo</h2>
            <p style="margin-bottom: 20px;">Vas a compartir el archivo <strong id="nombreArchivoVisible"></strong>.</p>
            
            <form action="compartir_archivo.php" method="POST">
                <input type="hidden" id="inputRutaArchivo" name="ruta_archivo">
                <input type="hidden" id="inputNombreArchivo" name="nombre_archivo">
                
                <p style="text-align: left; font-size: 0.9em; margin-bottom: 5px;">Correo del usuario receptor:</p>
                <input type="email" name="email_receptor" placeholder="ejemplo@correo.com" required style="width: 100%; padding: 10px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
                
                <div style="display: flex; justify-content: space-between;">
                    <button type="button" onclick="cerrarModalCompartir()" style="padding: 10px 20px; border: none; background: #6c757d; color: white; border-radius: 4px; cursor: pointer;">
                        Cancelar
                    </button>
                    
                    <button type="submit" style="padding: 10px 20px; border: none; background: #4F46E5; color: white; border-radius: 4px; cursor: pointer;">
                        Compartir
                    </button>
                </div>
            </form>
        </div>
    </div>

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

        // --- Funciones para el Modal de Compartir ---
        function abrirModalCompartir(nombre, ruta) {
            document.getElementById('modalCompartir').style.display = 'flex';
            document.getElementById('nombreArchivoVisible').innerText = nombre;
            document.getElementById('inputNombreArchivo').value = nombre;
            document.getElementById('inputRutaArchivo').value = ruta;
        }

        function cerrarModalCompartir() {
            document.getElementById('modalCompartir').style.display = 'none';
        }

        // --- Drag & Drop: mover archivos/carpetas ---
        (function(){
            const currentDir = <?php echo json_encode($req); ?>;

            function setupDnD(){
                // Draggables: filas con class item-row
                const draggables = document.querySelectorAll('tr.item-row[draggable="true"]');
                draggables.forEach(row => {
                    row.addEventListener('dragstart', (e) => {
                        row.classList.add('dragging');
                        const payload = { name: row.dataset.name, isDir: row.dataset.isdir, dir: currentDir };
                        e.dataTransfer.setData('text/plain', JSON.stringify(payload));
                        e.dataTransfer.effectAllowed = 'move';
                    });
                    row.addEventListener('dragend', () => row.classList.remove('dragging'));
                });

                // Drop targets: carpetas y fila 'Volver' (class drop-target)
                const dropTargets = document.querySelectorAll('tr.drop-target, tr.item-row[data-isdir="1"]');
                dropTargets.forEach(row => {
                    row.addEventListener('dragover', (e) => {
                        // Sólo permitir si viene un payload
                        if (e.dataTransfer.types.includes('text/plain')) {
                            e.preventDefault();
                            e.dataTransfer.dropEffect = 'move';
                            row.classList.add('drag-over');
                        }
                    });
                    row.addEventListener('dragleave', () => row.classList.remove('drag-over'));

                    row.addEventListener('drop', async (e) => {
                        e.preventDefault();
                        row.classList.remove('drag-over');
                        try {
                            const data = JSON.parse(e.dataTransfer.getData('text/plain'));
                            if (!data || !data.name) return;

                            // Destino determinado por data-destdir / data-destname
                            const destDir = row.dataset.destdir !== undefined ? row.dataset.destdir : currentDir;
                            const destName = row.dataset.destname !== undefined ? row.dataset.destname : (row.dataset.name || '');

                            // Evitar mover dentro de sí mismo (cuando destino es la propia carpeta)
                            if (data.name === destName && data.dir === destDir) {
                                alert('No se puede mover dentro del mismo elemento.');
                                return;
                            }

                            const body = { srcName: data.name, srcDir: data.dir, destName: destName, destDir: destDir };

                            // Confirmación antes de mover
                            const destLabel = destName || destDir || '/';
                            const ok = confirm(`¿Mover "${data.name}" a "${destLabel}"?`);
                            if (!ok) return;

                            const res = await fetch('mover.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify(body)
                            });

                            const result = await res.json();
                            if (result.success) window.location.reload();
                            else alert('Error al mover: ' + (result.error || 'error desconocido'));
                        } catch (err) {
                            console.error(err);
                            alert('Error al procesar el movimiento.');
                        }
                    });
                });
            }

            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', setupDnD);
            else setupDnD();
        })();
    </script>

</body>
</html>
