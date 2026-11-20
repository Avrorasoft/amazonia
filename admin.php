<?php
// admin.php - Panel CMS Avanzado Blindado con Carga Directa de Imágenes y Reglas de Integridad JSON
session_start();

define('ADMIN_PASSWORD', '2026');

$mensaje = '';
$error = '';

if (isset($_POST['login_pass'])) {
    if ($_POST['login_pass'] === ADMIN_PASSWORD) {
        $_SESSION['admin_logged'] = true;
        header("Location: admin.php");
        exit;
    } else {
        $error = "Clave de acceso incorrecta.";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}

$is_admin = isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true;
$archivo_json = __DIR__ . '/articulos.json';

function guardarArticulosSeguro($ruta_json, $lista_articulos) {
    $json_codificado = json_encode(array_values($lista_articulos), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json_codificado === false) {
        return false;
    }
    return file_put_contents($ruta_json, $json_codificado, LOCK_EX) !== false;
}

// Guardar / Crear / Editar Noticia
if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $articulos = [];
    if (file_exists($archivo_json)) {
        $articulos = json_decode(file_get_contents($archivo_json), true) ?: [];
    }

    $id_enviado = trim($_POST['id'] ?? '');
    $id = ($id_enviado !== '') ? $id_enviado : strval(time());

    // Mantener la imagen actual por defecto si no se sube una nueva
    $imagen_final = trim($_POST['imagen_actual'] ?? 'images/default.jpg');
    if ($imagen_final === '') {
        $imagen_final = 'images/default.jpg';
    }

    // Procesar la subida de la nueva imagen si se seleccionó un archivo
    if (isset($_FILES['imagen_archivo']) && $_FILES['imagen_archivo']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['imagen_archivo']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['imagen_archivo']['tmp_name'];
            $file_name = $_FILES['imagen_archivo']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'webp'];
            $info_imagen = @getimagesize($file_tmp);
            
            if (in_array($file_ext, $extensiones_permitidas, true) && $info_imagen !== false) {
                $carpeta_destino = __DIR__ . '/imagenes_articulos/';
                if (!is_dir($carpeta_destino)) {
                    mkdir($carpeta_destino, 0755, true);
                }

                $nuevo_nombre_img = 'img_' . time() . '_' . mt_rand(100, 999) . '.' . $file_ext;
                $ruta_absoluta = $carpeta_destino . $nuevo_nombre_img;

                if (move_uploaded_file($file_tmp, $ruta_absoluta)) {
                    $imagen_final = 'imagenes_articulos/' . $nuevo_nombre_img;
                } else {
                    $error = "Hubo un error al mover la imagen subida al servidor.";
                }
            } else {
                $error = "Archivo inválido. Solo se aceptan imágenes reales en formato JPG, PNG o WEBP.";
            }
        } else {
            $error = "Error al subir el archivo (código: " . intval($_FILES['imagen_archivo']['error']) . ").";
        }
    }

    if (!$error) {
        $categoria_limpia = mb_strtoupper(trim($_POST['categoria'] ?? 'NOTICIA'), 'UTF-8');
        if ($categoria_limpia === '') {
            $categoria_limpia = 'NOTICIA';
        }

        $formatos_validos = ['', 'span-h-izq', 'span-h-der', 'span-v', 'span-big'];
        $formato_enviado = trim($_POST['formato'] ?? '');
        if (!in_array($formato_enviado, $formatos_validos, true)) {
            $formato_enviado = '';
        }

        $nuevo_articulo = [
            'id' => $id,
            'fecha' => trim($_POST['fecha'] ?? date('Y-m-d')),
            'categoria' => $categoria_limpia,
            'titulo' => trim($_POST['titulo'] ?? ''),
            'fuente' => trim($_POST['fuente'] ?? ''),
            'resumen' => trim($_POST['resumen'] ?? ''),
            'contenido_completo' => trim($_POST['contenido_completo'] ?? ''),
            'imagen' => $imagen_final,
            'formato' => $formato_enviado,
            'eliminado' => false
        ];

        if ($nuevo_articulo['titulo'] === '') {
            $error = "El título del artículo es obligatorio.";
        } else {
            $encontrado = false;
            foreach ($articulos as &$art) {
                if (strval($art['id']) === strval($id)) {
                    $nuevo_articulo['likes'] = intval($art['likes'] ?? 0);
                    $nuevo_articulo['sads'] = intval($art['sads'] ?? 0);
                    $nuevo_articulo['comentarios'] = is_array($art['comentarios'] ?? null) ? $art['comentarios'] : [];
                    $art = array_merge($art, $nuevo_articulo);
                    $encontrado = true;
                    break;
                }
            }
            unset($art);

            if (!$encontrado) {
                $nuevo_articulo['likes'] = 0;
                $nuevo_articulo['sads'] = 0;
                $nuevo_articulo['comentarios'] = [];
                array_unshift($articulos, $nuevo_articulo);
            }

            if (guardarArticulosSeguro($archivo_json, $articulos)) {
                $mensaje = $encontrado
                    ? "¡Artículo actualizado exitosamente conservando sus reacciones y comentarios!"
                    : "¡Nuevo artículo y fotografía publicados exitosamente!";
            } else {
                $error = "Error al guardar el archivo articulos.json. Verifica los permisos de escritura.";
            }
        }
    }
}

// Borrado lógico seguro
if ($is_admin && isset($_GET['borrar'])) {
    $id_borrar = trim($_GET['borrar']);
    $articulos = [];
    if (file_exists($archivo_json)) {
        $articulos = json_decode(file_get_contents($archivo_json), true) ?: [];
    }
    foreach ($articulos as &$art) {
        if (strval($art['id']) === strval($id_borrar)) {
            $art['eliminado'] = true;
            break;
        }
    }
    unset($art);
    guardarArticulosSeguro($archivo_json, $articulos);
    header("Location: admin.php");
    exit;
}

$articulos = [];
if (file_exists($archivo_json)) {
    $articulos = json_decode(file_get_contents($archivo_json), true) ?: [];
}
$articulos = array_values(array_filter($articulos, function($art) {
    return !isset($art['eliminado']) || $art['eliminado'] !== true;
}));

$etiquetas_formato = [
    '' => 'Normal (1x1)',
    'span-h-izq' => 'Horizontal Izq',
    'span-h-der' => 'Horizontal Der',
    'span-v' => 'Vertical (1x2)',
    'span-big' => 'Destacado (2x2)'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel CMS Avanzado | Portal Amazonía</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --vibrant-green: #1D9A4C;
            --accent-gold: #FFB81C;
            --dark-bg: #121212;
            --panel-bg: #1e1e1e;
            --text-main: #E0E0E0;
        }
        body { font-family: 'Segoe UI', Roboto, sans-serif; background-color: var(--dark-bg); color: var(--text-main); margin: 0; padding: 20px; }
        .container { max-width: 980px; margin: 0 auto; background: var(--panel-bg); padding: 35px; border-radius: 8px; border: 1px solid #333; box-shadow: 0 10px 30px rgba(0,0,0,0.8); }
        h2 { color: var(--accent-gold); margin-top: 0; }
        .admin-label { display: block; font-size: 11px; color: var(--accent-gold); font-weight: bold; text-transform: uppercase; margin-top: 15px; margin-bottom: 5px; letter-spacing: 0.5px; }
        .admin-input { width: 100%; padding: 12px; border: 1px solid #444; background: #111; color: white; border-radius: 6px; font-size: 14px; box-sizing: border-box; font-family: inherit; }
        .admin-input:focus { outline: none; border-color: var(--vibrant-green); }
        .btn { background: var(--vibrant-green); color: white; border: none; padding: 14px 25px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 15px; margin-top: 20px; width: 100%; transition: background 0.3s; }
        .btn:hover { background: var(--accent-gold); color: black; }
        .btn-danger { background: #ff4444; color: white; width: auto; padding: 6px 12px; font-size: 12px; }
        .btn-danger:hover { background: #cc0000; color: white; }
        .btn-secondary { background: #333; color: #ddd; text-decoration: none; display: inline-block; padding: 8px 15px; border-radius: 6px; font-size: 13px; }
        .btn-secondary:hover { background: #444; color: #fff; }
        .alert-success { background: rgba(29,154,76,0.2); border: 1px solid var(--vibrant-green); color: #fff; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; }
        .alert-error { background: rgba(255,68,68,0.2); border: 1px solid #ff4444; color: #fff; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; }
        .badge-tabla { background: #2a2a2a; color: var(--accent-gold); padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; display: inline-block; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #333; font-size: 14px; vertical-align: middle; }
        th { color: var(--accent-gold); }
    </style>
</head>
<body>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #333; padding-bottom: 15px; margin-bottom: 25px; flex-wrap: wrap; gap: 10px;">
        <h2>Panel CMS (Portal Amazonía)</h2>
        <div>
            <a href="index.php" class="btn-secondary">← Ver Portal</a>
            <?php if ($is_admin): ?>
                <a href="admin.php?logout=1" class="btn-secondary" style="background:#400; margin-left:10px;">Cerrar Sesión</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert-success"><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if (!$is_admin): ?>
        <form method="POST" style="max-width: 400px; margin: 40px auto; text-align: center;">
            <p style="color: #aaa; margin-bottom: 20px;">Ingrese su clave de seguridad para administrar el portal</p>
            <input type="password" name="login_pass" class="admin-input" placeholder="••••••••" required style="text-align: center; font-size: 18px; margin-bottom: 15px;">
            <button type="submit" class="btn">VERIFICAR ACCESO</button>
        </form>
    <?php else: ?>
        <?php 
            $edit_id = $_GET['editar'] ?? '';
            $articulo_a_editar = null;
            if ($edit_id !== '') {
                foreach ($articulos as $art) {
                    if (strval($art['id']) === strval($edit_id)) {
                        $articulo_a_editar = $art;
                        break;
                    }
                }
            }
        ?>
        <h3 style="color: white; border-left: 4px solid var(--accent-gold); padding-left: 10px; margin-top: 0;">
            <?php echo $articulo_a_editar ? "Editar Artículo (ID: " . htmlspecialchars($edit_id, ENT_QUOTES, 'UTF-8') . ")" : "Crear Nueva Noticia"; ?>
        </h3>

        <!-- IMPORTANTE: enctype="multipart/form-data" para permitir subida de archivos -->
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="accion" value="guardar">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($articulo_a_editar['id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="imagen_actual" value="<?php echo htmlspecialchars($articulo_a_editar['imagen'] ?? 'images/default.jpg', ENT_QUOTES, 'UTF-8'); ?>">

            <label class="admin-label">Tamaño y Formato de Tarjeta en el Mosaico</label>
            <select name="formato" class="admin-input" style="border-color: var(--accent-gold); font-weight: bold;">
                <?php $fmt_actual = $articulo_a_editar['formato'] ?? ''; ?>
                <option value="" <?php echo $fmt_actual === '' ? 'selected' : ''; ?>>Cuadrado Normal (1x1)</option>
                <option value="span-h-izq" <?php echo $fmt_actual === 'span-h-izq' ? 'selected' : ''; ?>>Horizontal — Foto a la Izquierda</option>
                <option value="span-h-der" <?php echo $fmt_actual === 'span-h-der' ? 'selected' : ''; ?>>Horizontal — Foto a la Derecha</option>
                <option value="span-v" <?php echo $fmt_actual === 'span-v' ? 'selected' : ''; ?>>Vertical Largo (1x2)</option>
                <option value="span-big" <?php echo $fmt_actual === 'span-big' ? 'selected' : ''; ?>>Extra Grande Destacado (2x2)</option>
            </select>

            <label class="admin-label">Subir Fotografía desde tu Computadora</label>
            <input type="file" name="imagen_archivo" class="admin-input" accept="image/jpeg,image/png,image/webp" style="padding: 9px;">
            <?php if (!empty($articulo_a_editar['imagen'])): ?>
                <span style="font-size: 11px; color: #888; display: block; margin-top: 5px;">Imagen actual: <?php echo htmlspecialchars($articulo_a_editar['imagen'], ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <label class="admin-label">Fecha</label>
                    <input type="text" name="fecha" class="admin-input" value="<?php echo htmlspecialchars($articulo_a_editar['fecha'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div>
                    <label class="admin-label">Categoría</label>
                    <input type="text" name="categoria" class="admin-input" value="<?php echo htmlspecialchars($articulo_a_editar['categoria'] ?? 'NOTICIA', ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>

            <label class="admin-label">Título del Artículo</label>
            <input type="text" name="titulo" class="admin-input" value="<?php echo htmlspecialchars($articulo_a_editar['titulo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>

            <label class="admin-label">Fuente o Autor</label>
            <input type="text" name="fuente" class="admin-input" value="<?php echo htmlspecialchars($articulo_a_editar['fuente'] ?? 'Redacción Central', ENT_QUOTES, 'UTF-8'); ?>">

            <label class="admin-label">Resumen Corto (Mosaico)</label>
            <textarea name="resumen" class="admin-input" rows="3"><?php echo htmlspecialchars($articulo_a_editar['resumen'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>

            <label class="admin-label">Contenido Completo (Inmersión Total)</label>
            <textarea name="contenido_completo" class="admin-input" rows="7"><?php echo htmlspecialchars($articulo_a_editar['contenido_completo'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>

            <button type="submit" class="btn">💾 GUARDAR CAMBIOS Y SUBIR IMAGEN</button>
            <?php if ($articulo_a_editar): ?>
                <div style="text-align: center; margin-top: 10px;">
                    <a href="admin.php" class="btn-secondary">Cancelar Edición</a>
                </div>
            <?php endif; ?>
        </form>

        <hr style="border: 0; border-top: 1px solid #333; margin: 40px 0;">

        <h3 style="color: white;">Artículos Registrados en el Servidor (<?php echo count($articulos); ?>)</h3>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Categoría</th>
                        <th>Formato</th>
                        <th>Fecha</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($articulos)): ?>
                        <tr><td colspan="6" style="color: #888; text-align: center;">No hay artículos registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($articulos as $art): 
                            $cat_item = mb_strtoupper(trim($art['categoria'] ?? 'NOTICIA'), 'UTF-8');
                            $fmt_item = $art['formato'] ?? '';
                            $fmt_texto = $etiquetas_formato[$fmt_item] ?? 'Normal (1x1)';
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars(strval($art['id']), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($art['titulo'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><span class="badge-tabla"><?php echo htmlspecialchars($cat_item, ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td style="color: #aaa; font-size: 12px;"><?php echo htmlspecialchars($fmt_texto, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td style="white-space: nowrap;"><?php echo htmlspecialchars($art['fecha'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <a href="articulo.php?id=<?php echo rawurlencode(strval($art['id'])); ?>" target="_blank" class="btn-secondary" style="padding: 6px 10px; font-size: 12px;" title="Ver artículo">Ver</a>
                                    <a href="admin.php?editar=<?php echo rawurlencode(strval($art['id'])); ?>" class="btn-secondary" style="padding: 6px 12px; font-size: 12px;">Editar</a>
                                    <a href="admin.php?borrar=<?php echo rawurlencode(strval($art['id'])); ?>" class="btn-secondary btn-danger" style="padding: 6px 12px; font-size: 12px;" onclick="return confirm('¿Estás seguro de borrar este artículo?');">Borrar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

</body>
</html>