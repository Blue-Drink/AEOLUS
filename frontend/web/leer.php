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
// Formatea bytes a unidades legibles (B, KB, MB, GB...)
function human_filesize($bytes, $decimals = 2) {
    if ($bytes <= 0) return '0 B';
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), $decimals) . ' ' . $units[$i];
}
// ----------------------------------------------

$base_path = "../../backend/uploads/" . $_SESSION['usuario'] . "/";
// Seguridad en rutas
$req = isset($_GET['dir']) ? trim(str_replace('..', '', $_GET['dir']), '/\\') : '';
$current_path = $base_path . $req;

if (!file_exists($base_path)) mkdir($base_path, 0777, true);
if (!file_exists($current_path)) $current_path = $base_path;

// --- CÁLCULOS DE LA BARRA DE PROGRESO ---
$bytes_usados = calcularTamañoDirectorio("../../backend/uploads/" . $_SESSION['usuario']);
$used_display = human_filesize($bytes_usados, 2);
$quota_bytes = 1073741824; // 1 GB
$quota_display = human_filesize($quota_bytes, 2);
$porcentaje = $quota_bytes > 0 ? round(($bytes_usados / $quota_bytes) * 100, 1) : 0;
$ancho_barra = min($porcentaje, 100); // Para que no se rompa el diseño si se pasa del 100% por un bug
// ----------------------------------------

// --- LÓGICA DE RENOMBRAR---
if (isset($_POST['nuevo_nombre']) && isset($_POST['archivo_original'])) {
    $orig = $current_path . '/' . basename($_POST['archivo_original']);
    $dest = $current_path . '/' . basename($_POST['nuevo_nombre']);
    if (file_exists($orig)) rename($orig, $dest);

    $_SESSION['mensaje'] = "Archivo renombrado con éxito."; 
    header("Location: leer.php?dir=" . urlencode($req));
    exit;
}

// --- LÓGICA CREAR CARPETA ---
if (isset($_POST['nueva_carpeta'])) {
    $d = trim(basename($_POST['nueva_carpeta']));

    if (empty($d)) {
        $_SESSION['error'] = "Introduzca un nombre para la carpeta.";
    } else {
        $ruta_destino = $current_path . '/' . $d; 

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="AEOLUS.png">
    <title>AEOLUS | Mi Almacenamiento</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        /* --- ESTILOS DEL INTERFAZ DE EXPLORADOR PROFESIONAL --- */
        :root {
            --gradient-sidebar: linear-gradient(135deg, #2563eb 0%, #4f46e5 30%, #7c3aed 65%, #db2777 100%);
            --bg-main: #f8fafc;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --primary: #4F46E5;
            --primary-light: #e0e7ff;
            --danger: #ef4444;
            --danger-light: #fee2e2;
            --success: #10b981;
            --border: #e2e8f0;
        }

        * { box-sizing: border-box; }
        
        body {
            font-family: 'Inter', system-ui, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--bg-main);
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        /* --- Estilos Drag & Drop --- */
        .drag-over { background-color: #e0f2fe !important; border: 2px dashed #0284c7 !important; }
        .dragging { opacity: 0.5; }

        /* --- 1. BARRA LATERAL (SIDEBAR FINA Y FIJA) --- */
        .sidebar {
            width: 280px;
            background: var(--gradient-sidebar);
            color: white;
            padding: 30px 24px;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            box-shadow: 4px 0 25px rgba(0,0,0,0.1);
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 2.5rem;
        }

        .sidebar-logo img {
            width: 42px;
            height: 42px;
            background: white;
            padding: 4px;
            border-radius: 10px;
        }

        .sidebar-logo h1 {
            font-size: 1.3rem;
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .sidebar-logo span { font-weight: 300; opacity: 0.8; }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .menu-item a {
            display: flex;
            align-items: center;
            gap: 12px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            padding: 12px 16px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .menu-item.active a, .menu-item a:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }

        /* Widget de Cuota en la Sidebar */
        .quota-widget {
            margin-top: auto;
            background: rgba(0, 0, 0, 0.2);
            padding: 16px;
            border-radius: 12px;
            font-size: 0.85rem;
        }

        .quota-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            color: rgba(255,255,255,0.9);
        }

        .quota-bar-bg {
            width: 100%;
            background: rgba(255,255,255,0.2);
            height: 6px;
            border-radius: 4px;
            overflow: hidden;
        }

        .quota-bar-fill {
            background: #ffffff;
            height: 100%;
            border-radius: 4px;
        }

        /* --- 2. ESPACIO DE TRABAJO PRINCIPAL --- */
        .main-content {
            flex: 1;
            margin-left: 280px; /* Separación de la sidebar */
            padding: 40px;
        }

        /* Barra superior de usuario */
        .top-navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 16px 32px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02), 0 2px 4px -1px rgba(0,0,0,0.01);
            border: 1px solid var(--border);
        }

        .user-greeting h2 { margin: 0; font-size: 1.4rem; font-weight: 700; }
        .user-greeting .breadcrumb { font-size: 0.85rem; color: var(--text-muted); margin-top: 4px; font-weight: 500; }

        .nav-actions { display: flex; gap: 12px; }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
        }

        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: #4338ca; }
        .btn-outline-danger { background: white; color: var(--danger); border: 1px solid var(--danger); }
        .btn-outline-danger:hover { background: var(--danger-light); }
        .btn-danger { background: var(--danger); color: white; }
        .btn-danger:hover { background: #dc2626; }

        /* Barra de Herramientas (Crear carpeta / Subir) */
        .toolbar-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            gap: 20px;
        }

        .folder-form { display: flex; gap: 10px; width: 100%; max-width: 400px; }
        .folder-form input[type="text"] {
            flex: 1;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.9rem;
        }
        .folder-form input[type="text"]:focus { outline: none; border-color: var(--primary); }

        /* --- 3. TABLA EXPOSITIVA ESTILO EXPLORADOR --- */
        .explorer-card {
            background: white;
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
            overflow: hidden;
            margin-bottom: 35px;
        }

        .section-title {
            font-size: 1.15rem;
            font-weight: 700;
            padding: 24px 32px 12px 32px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-explorer {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .table-explorer th {
            background: #f8fafc;
            padding: 14px 32px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table-explorer td {
            padding: 16px 32px;
            border-bottom: 1px solid var(--border);
            font-size: 0.95rem;
            vertical-align: middle;
        }

        .table-explorer tr:last-child td { border-bottom: none; }
        .table-explorer tr.item-row:hover { background-color: #f1f5f9; }

        /* Iconos de Ficheros Especiales */
        .item-name-wrapper { display: flex; align-items: center; gap: 12px; }
        .item-link { text-decoration: none; color: var(--text-main); font-weight: 600; transition: color 0.2s; }
        .item-link:hover { color: var(--primary); }
        
        .icon-folder { color: #f59e0b; font-size: 1.2rem; }
        .icon-file { color: #3b82f6; font-size: 1.2rem; }

        /* Micro-formulario de renombrar camuflado */
        .rename-form { display: inline-flex; align-items: center; gap: 4px; margin-left: 12px; opacity: 0; transition: opacity 0.2s; }
        tr.item-row:hover .rename-form { opacity: 0.7; }
        .rename-form:hover { opacity: 1 !important; }
        .rename-input { border: 1px solid var(--border); padding: 3px 6px; font-size: 0.8rem; border-radius: 4px; width: 100px; }
        .rename-btn { background: none; border: none; cursor: pointer; font-size: 0.85rem; padding: 2px; }

        /* Botones de acción compactos */
        .actions-cell { display: flex; gap: 6px; justify-content: flex-end; }
        .action-icon-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 6px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        .btn-view { background: #eff6ff; color: #2563eb; }
        .btn-view:hover { background: #dbeafe; }
        .btn-download { background: #ecfdf5; color: #059669; }
        .btn-download:hover { background: #d1fae5; }
        .btn-share { background: #e0e7ff; color: #4f46e5; }
        .btn-share:hover { background: #c7d2fe; }
        .btn-delete { background: #fef2f2; color: var(--danger); }
        .btn-delete:hover { background: var(--danger-light); }

        /* Alertas */
        .alert-banner {
            padding: 16px 24px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-weight: 500;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .alert-success { background-color: #d1fae5; color: #065f46; border-left: 5px solid #10b981; }
        .alert-error { background-color: #fee2e2; color: #991b1b; border-left: 5px solid #ef4444; }

        /* Pegatina Compartido */
        .badge-shared {
            font-size: 0.75rem;
            background-color: var(--success);
            color: white;
            padding: 3px 8px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-logo">
            <img src="AEOLUS.png" alt="Logo">
            <h1>AEOLUS <span>Cloud</span></h1>
        </div>
        
        <ul class="sidebar-menu">
            <li class="menu-item active">
                <a href="leer.php"><i class="fas fa-folder-open"></i> Mis Archivos</a>
            </li>
            <li class="menu-item">
                <a href="#seccion-compartidos"><i class="fas fa-user-friends"></i> Compartidos</a>
            </li>
        </ul>

        <div class="quota-widget">
            <div class="quota-info">
                <span>Espacio Usado</span>
                <strong><?php echo $porcentaje; ?>%</strong>
            </div>
            <div class="quota-bar-bg">
                <div class="quota-bar-fill" style="width: <?php echo $ancho_barra; ?>%;"></div>
            </div>
            <div class="quota-info" style="margin-top: 8px; font-size: 0.75rem; opacity: 0.8;">
                <span></span>
                <span><?php echo $used_display; ?> / <?php echo $quota_display; ?></span>
            </div>
        </div>
    </div>

    <div class="main-content">
        
        <div class="top-navbar">
            <div class="user-greeting">
                <h2>Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario']); ?></h2>
                <div class="breadcrumb"><i class="fas fa-hdd"></i> root / <?php echo htmlspecialchars($req); ?></div>
            </div>
            <div class="nav-actions">
                <button onclick="abrirModalBorrado()" class="btn btn-outline-danger">
                    <i class="fas fa-user-slash"></i> Eliminar Cuenta
                </button>
                <a href="logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert-banner alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['mensaje']; ?>
            </div>
            <?php unset($_SESSION['mensaje']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-banner alert-error">
                <i class="fas fa-times-circle"></i> <?php echo $_SESSION['error']; ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="toolbar-container">
            <form action="" method="post" class="folder-form">
                <input type="text" name="nueva_carpeta" placeholder="Nueva Carpeta..." required>
                <button type="submit" class="btn btn-primary" style="padding: 10px 16px;"><i class="fas fa-plus"></i></button>
            </form>

            <a href="subir.html?dir=<?php echo urlencode($req); ?>" class="btn btn-primary" style="background: var(--gradient-sidebar);">
                <i class="fas fa-cloud-upload-alt"></i> Subir un Fichero
            </a>
        </div>

        <div class="explorer-card">
            <h3 class="section-title"><i class="fas fa-folder-open" style="color:var(--primary)"></i> Unidad de Almacenamiento</h3>
            <table class="table-explorer">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th style="width: 150px;">Tamaño</th>
                        <th style="width: 200px; text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Botón Volver dinámico para DND
                    if ($req !== '') {
                        $parent = dirname($req);
                        $back = ($parent == '.' || $parent == '/') ? '' : $parent;
                        $backEsc = htmlspecialchars($back, ENT_QUOTES);
                        echo "<tr class='drop-target' data-destdir='$backEsc' data-destname='' style='background:#f8fafc'>
                                <td colspan='3' style='padding: 12px 32px;'>
                                    <a href='leer.php?dir=".urlencode($back)."' style='text-decoration:none; font-weight:600; color:var(--primary); font-size:0.9rem;'>
                                        <i class='fas fa-arrow-left'></i> ... Volver al directorio anterior
                                    </a>
                                </td>
                              </tr>";
                    }

                    // Cargar lista de compartidos para chivato
                    $mis_archivos_compartidos = [];
		    require_once '../../backend/vendor/autoload.php';
		    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../backend');
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

                        $link = $isDir ? "leer.php?dir=" . urlencode(($req ? $req.'/' : '') . $f) : "abrir_archivos.php?file=" . urlencode($f) . "&dir=" . urlencode($req);
                        $target = $isDir ? "" : " target='_blank'";
                        
                        // Iconos sutiles de FontAwesome según tipo
                        $iconClass = $isDir ? "fas fa-folder icon-folder" : "fas fa-file-alt icon-file";

                        $size = $isDir ? "--" : human_filesize(filesize($full), 2);

                        // Atributos de Isaac para Drag & Drop
                        $safeNameAttr = htmlspecialchars($f, ENT_QUOTES);
                        $isDirAttr = $isDir ? 1 : 0;
                        $dataDestDir = htmlspecialchars($req, ENT_QUOTES);
                        $dataDestName = $isDir ? $safeNameAttr : '';
                        
                        echo "<tr class='item-row' draggable='true' data-name='$safeNameAttr' data-isdir='$isDirAttr' data-destdir='$dataDestDir' data-destname='$dataDestName'>";
                        
                        // Pegatina de Compartido refinada
                        $ruta_relativa_completa = ($req ? $req.'/' : '') . $f;
                        $etiqueta_compartido = ""; 
                        if (!$isDir && in_array($ruta_relativa_completa, $mis_archivos_compartidos)) {
                            $etiqueta_compartido = "<span class='badge-shared'><i class='fas fa-link'></i> Compartido</span>";
                        }

                        // Columna Nombre
                        echo "<td>
                                <div class='item-name-wrapper'>
                                    <i class='$iconClass'></i>
                                    <a href='$link'$target class='item-link'>$f</a>
                                    $etiqueta_compartido
                                    
                                    <form method='post' class='rename-form'>
                                        <input type='hidden' name='archivo_original' value='$f'>
                                        <input type='text' name='nuevo_nombre' placeholder='Renombrar' class='rename-input' required>
                                        <button type='submit' class='rename-btn' title='Renombrar'>✏️</button>
                                    </form>
                                </div>
                              </td>";

                        // Columna Tamaño
                        echo "<td style='color: var(--text-muted); font-weight:500;'>$size</td>";

                        // Columna Acciones
                        echo "<td><div class='actions-cell'>";
                        if (!$isDir) {
                            // Ver / Previsualizar
                            echo "<a href='$link' target='_blank' class='action-icon-btn btn-view' title='Previsualizar Fichero'><i class='fas fa-eye'></i></a>";
                            // Descargar
                            echo "<a href='$full' download class='action-icon-btn btn-download' title='Descargar'><i class='fas fa-download'></i></a>";
                            // Compartir
                            $ruta_relativa_completa = ($req ? $req.'/' : '') . $f;
                            echo "<button onclick=\"abrirModalCompartir('$f', '$ruta_relativa_completa')\" class='action-icon-btn btn-share' title='Compartir con un compañero'><i class='fas fa-user-plus'></i></button>";
                        }
                        // Borrar (Valido para archivos y carpetas vacías)
                        echo "<a href='borrar.php?eliminar=".urlencode($f)."&dir=".urlencode($req)."' class='action-icon-btn btn-delete' title='Eliminar de la unidad' onclick=\"return confirm('¿Seguro que quieres borrar $f?');\"><i class='fas fa-trash-alt'></i></a>";
                        echo "</div></td>";
                        echo "</tr>";
                    }
                    
                    // --- NUEVO: ZONA DE ARRASTRE PERMANENTE AL FINAL DE LA TABLA ---
                    echo "<tr>
                            <td colspan='3' style='text-align:center; color:var(--text-muted); padding: 35px; background-color: #f8fafc; border-top: 2px dashed #cbd5e1;'>
                                <i class='fas fa-cloud-upload-alt' style='font-size: 1.8rem; color: var(--primary); margin-bottom: 12px; display: block; opacity: 0.8;'></i>
                                <span style='font-weight: 500; font-size: 0.95rem;'>Arrastra aquí tus archivos para subirlos a esta carpeta</span>
                            </td>
                          </tr>";
                    ?>
                </tbody>
            </table>
        </div>

        <div class="explorer-card" id="seccion-compartidos">
            <h3 class="section-title" style="color: var(--success);"><i class="fas fa-user-friends"></i> Compartidos conmigo</h3>
            <table class="table-explorer">
                <thead>
                    <tr>
                        <th>Nombre del Archivo</th>
                        <th>Enviado por</th>
                        <th>Fecha de recepción</th>
                        <th style="width: 150px; text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $conexion = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASS'], $_ENV['DB_NAME']);
                    if (!$conexion->connect_error) {
                        $mi_usuario = $_SESSION['usuario'];
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
				$ruta_fisica = "../../backend/uploads/" . $fila['nombre_propietario'] . "/" . $fila['ruta_relativa'];
                                $nombre_amigable = htmlspecialchars($fila['nombre_archivo']);
                                $propietario = htmlspecialchars($fila['nombre_propietario']);
                                $fecha = date('d/m/Y H:i', strtotime($fila['fecha_compartido']));
                                
                                echo "<tr>";
                                echo "<td><div class='item-name-wrapper'><i class='fas fa-file-signature' style='color:var(--success)'></i> <span style='font-weight:600;'>$nombre_amigable</span></div></td>";
                                echo "<td><span style='font-weight:500; color:var(--text-muted)'><i class='fas fa-user-circle'></i> $propietario</span></td>";
                                echo "<td style='color: var(--text-muted); font-size:0.9rem;'>$fecha</td>";
                                echo "<td><div class='actions-cell'>";
                                
                                if (file_exists($ruta_fisica)) {
                                    echo "<a href='$ruta_fisica' target='_blank' class='action-icon-btn btn-view' title='Ver'><i class='fas fa-eye'></i></a>";
                                    echo "<a href='$ruta_fisica' download class='action-icon-btn btn-download' style='background-color:#d1fae5; color:#059669;' title='Descargar'><i class='fas fa-download'></i></a>";
                                } else {
                                    echo "<span style='color: var(--danger); font-size: 0.8em; font-weight:600;'>Archivo eliminado en origen</span>";
                                }
                                
                                echo "</div></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' style='text-align:center; color: var(--text-muted); padding:30px;'>Ningún compañero ha compartido elementos contigo todavía.</td></tr>";
                        }
                        $conexion->close();
                    }
                    ?>
                </tbody>
            </table>
        </div>

    </div>

    <div id="modalCompartir" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.6); justify-content: center; align-items: center; z-index: 1000; backdrop-filter: blur(4px);">
        <div style="background: white; padding: 35px; border-radius: 16px; max-width: 450px; width:100%; text-align: center; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); border:1px solid var(--border);">
            <div style="width: 56px; height: 56px; background: var(--primary-light); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem auto; font-size: 1.5rem;">
                <i class="fas fa-share-alt"></i>
            </div>
            <h2 style="margin-top: 0; font-weight:700;">Compartir Archivo</h2>
            <p style="color: var(--text-muted); font-size:0.95rem; margin-bottom: 20px;">Vas a conceder permisos de lectura para: <br><strong id="nombreArchivoVisible" style="color:var(--text-main)"></strong></p>
            
            <form action="compartir_archivo.php" method="POST">
                <input type="hidden" id="inputRutaArchivo" name="ruta_archivo">
                <input type="hidden" id="inputNombreArchivo" name="nombre_archivo">
                
                <div style="text-align: left; margin-bottom: 20px;">
                    <label style="font-size: 0.85rem; font-weight: 600; color: #475569; display:block; margin-bottom:6px;">Correo electrónico del destinatario:</label>
                    <input type="email" name="email_receptor" placeholder="ejemplo@correo.com" required style="width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px; font-family:inherit;">
                </div>
                
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" onclick="cerrarModalCompartir()" class="btn" style="background:#e2e8f0; color:#475569;">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Otorgar Acceso</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalBorrado" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.6); justify-content: center; align-items: center; z-index: 1000; backdrop-filter: blur(4px);">
        <div style="background: white; padding: 35px; border-radius: 16px; max-width: 450px; width:100%; text-align: center; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); border:1px solid var(--border);">
            <div style="width: 56px; height: 56px; background: var(--danger-light); color: var(--danger); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem auto; font-size: 1.5rem;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2 style="margin-top: 0; font-weight:700; color: var(--danger);">Acción Crítica</h2>
            <p style="color: var(--text-muted); font-size:0.95rem; line-height:1.5;">Se eliminarán tus credenciales de acceso y todos tus ficheros de forma permanente en el servidor.</p>
            <p style="font-size:0.9rem; margin-bottom: 20px;">Confirma escribiendo tu usuario exacto (<strong><?php echo $_SESSION['usuario']; ?></strong>):</p>

            <form action="borrar_cuenta.php" method="POST">
                <input type="text" id="inputConfirmacion" placeholder="Tu nombre de usuario" onkeyup="validarBorrado('<?php echo $_SESSION['usuario']; ?>')" style="width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px; font-family:inherit; margin-bottom:20px; text-align:center;">

                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" onclick="cerrarModalBorrado()" class="btn" style="background:#e2e8f0; color:#475569;">Cancelar Operación</button>
                    <button type="submit" id="btnBorrarReal" disabled class="btn" style="background: var(--danger); color:white; opacity:0.5; cursor:not-allowed;">Eliminar Definitivamente</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModalBorrado() {
            document.getElementById('modalBorrado').style.display = 'flex';
        }

        function cerrarModalBorrado() {
            document.getElementById('modalBorrado').style.display = 'none';
            document.getElementById('inputConfirmacion').value = '';
            validarBorrado('<?php echo $_SESSION['usuario']; ?>');
        }

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

        function abrirModalCompartir(nombre, ruta) {
            document.getElementById('modalCompartir').style.display = 'flex';
            document.getElementById('nombreArchivoVisible').innerText = nombre;
            document.getElementById('inputNombreArchivo').value = nombre;
            document.getElementById('inputRutaArchivo').value = ruta;
        }

        function cerrarModalCompartir() {
            document.getElementById('modalCompartir').style.display = 'none';
        }

        // Drag & Drop Original de Isaac preservado al 100%
        (function(){
            const currentDir = <?php echo json_encode($req); ?>;

            function setupDnD(){
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

                const dropTargets = document.querySelectorAll('tr.drop-target, tr.item-row[data-isdir="1"]');

                const transferHasFiles = (dataTransfer) => {
                    const types = Array.from(dataTransfer.types || []);
                    return types.includes('Files') || types.includes('application/x-moz-file');
                };

                dropTargets.forEach(row => {
                    row.addEventListener('dragover', (e) => {
                        const types = Array.from(e.dataTransfer.types || []);
                        if (types.includes('text/plain') || transferHasFiles(e.dataTransfer)) {
                            e.preventDefault(); // ¡ESTO ES VITAL! Bloquea que el navegador abra el archivo en una pestaña nueva
                            
                            // Si es un archivo de Windows mostramos el icono de "Copiar", si es interno el de "Mover"
                            e.dataTransfer.dropEffect = transferHasFiles(e.dataTransfer) ? 'copy' : 'move';
                            row.classList.add('drag-over');
                        }
                    });
                    row.addEventListener('dragleave', () => row.classList.remove('drag-over'));

                    row.addEventListener('drop', async (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        row.classList.remove('drag-over');

                        // --- DETECTOR DE ARCHIVOS EXTERNOS (DESDE EL ORDENADOR) ---
                        if (e.dataTransfer.files.length > 0) {
                            const archivosEscritorio = e.dataTransfer.files;
                            const destDir = row.dataset.destdir !== undefined ? row.dataset.destdir : currentDir;
                            const destName = row.dataset.destname !== undefined ? row.dataset.destname : '';
                            
                            // Si se suelta sobre una carpeta, el destino es esa carpeta. Si no, la carpeta actual.
                            const rutaFinalSubida = destName ? (destDir ? destDir + '/' : '') + destName : currentDir;
                            
                            const formData = new FormData();
                            formData.append('archivo', archivosEscritorio[0]); // El archivo físico
                            formData.append('directorio_destino', rutaFinalSubida); // La ruta actual
                            
                            try {
                                // Enviamos el archivo por detrás a vuestro subir.php original
                                await fetch('subir.php', { method: 'POST', body: formData });
                                // Recargamos la interfaz en la misma carpeta para ver el archivo subido
                                window.location.href = 'leer.php?dir=' + encodeURIComponent(currentDir);
                            } catch (err) {
                                alert('Error al subir el archivo desde el ordenador.');
                            }
                            return; // Cortamos la ejecución aquí
                        }

                        // --- RESTO DEL CÓDIGO PARA MOVER INTERNAMENTE ---
                        try {
                            const data = JSON.parse(e.dataTransfer.getData('text/plain'));
                            if (!data || !data.name) return;

                            const destDir = row.dataset.destdir !== undefined ? row.dataset.destdir : currentDir;
                            const destName = row.dataset.destname !== undefined ? row.dataset.destname : (row.dataset.name || '');

                            if (data.name === destName && data.dir === destDir) {
                                alert('No se puede mover dentro del mismo elemento.');
                                return;
                            }

                            const body = { srcName: data.name, srcDir: data.dir, destName: destName, destDir: destDir };

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

                document.body.addEventListener('dragover', (e) => {
                    if (transferHasFiles(e.dataTransfer)) {
                        e.preventDefault();
                        e.dataTransfer.dropEffect = 'copy';
                    }
                });

                document.body.addEventListener('drop', async (e) => {
                    if (!transferHasFiles(e.dataTransfer) || e.dataTransfer.files.length === 0) return;
                    e.preventDefault();

                    const archivosEscritorio = e.dataTransfer.files;
                    const rutaFinalSubida = currentDir;
                    const formData = new FormData();
                    formData.append('archivo', archivosEscritorio[0]);
                    formData.append('directorio_destino', rutaFinalSubida);

                    try {
                        await fetch('subir.php', { method: 'POST', body: formData });
                        window.location.href = 'leer.php?dir=' + encodeURIComponent(currentDir);
                    } catch (err) {
                        alert('Error al subir el archivo desde el ordenador.');
                    }
                });
            }

            if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', setupDnD);
            else setupDnD();
        })();
    </script>
</body>
</html>
