<?php
// articulo.php - Vista de Inmersión Total (Foto Fija a la Izquierda en PC / Scroll Natural en Móvil, Voz, Reacciones 0ms, Comentarios y Compartido Unificado)
$archivo_json = __DIR__ . '/articulos.json';
$articulos = [];

if (file_exists($archivo_json)) {
    $contenido = file_get_contents($archivo_json);
    $articulos = json_decode($contenido, true) ?: [];
}

$id_actual = $_GET['id'] ?? '';

// API AJAX para reacciones instantáneas (0ms en cliente, persistencia en servidor)
if (isset($_GET['ajax_reaccionar']) && isset($_GET['id']) && isset($_GET['tipo'])) {
    header('Content-Type: application/json');
    $tipo = $_GET['tipo'];
    $previo = $_GET['previo'] ?? '';
    $id_art = $_GET['id'];
    $encontrado = false;
    $likes = 0;
    $sads = 0;
    $nuevo_estado = '';

    foreach ($articulos as &$a) {
        if (strval($a['id']) === strval($id_art)) {
            if ($previo === $tipo) {
                if ($tipo === 'like') $a['likes'] = max(0, intval($a['likes'] ?? 0) - 1);
                if ($tipo === 'sad') $a['sads'] = max(0, intval($a['sads'] ?? 0) - 1);
                $nuevo_estado = '';
            } else {
                if ($previo === 'like') $a['likes'] = max(0, intval($a['likes'] ?? 0) - 1);
                if ($previo === 'sad') $a['sads'] = max(0, intval($a['sads'] ?? 0) - 1);

                if ($tipo === 'like') $a['likes'] = intval($a['likes'] ?? 0) + 1;
                if ($tipo === 'sad') $a['sads'] = intval($a['sads'] ?? 0) + 1;
                $nuevo_estado = $tipo;
            }
            $likes = intval($a['likes'] ?? 0);
            $sads = intval($a['sads'] ?? 0);
            $encontrado = true;
            break;
        }
    }
    unset($a);

    if ($encontrado) {
        file_put_contents($archivo_json, json_encode($articulos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        echo json_encode(['status' => 'success', 'likes' => $likes, 'sads' => $sads, 'nuevo_estado' => $nuevo_estado]);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit;
}

// API AJAX para agregar comentarios con bloqueo seguro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_comentar']) && isset($_POST['id'])) {
    header('Content-Type: application/json');
    $id_art = $_POST['id'];
    $autor = trim($_POST['autor'] ?? 'Lector');
    $texto = trim($_POST['comentario'] ?? '');

    if ($autor === '') $autor = 'Lector';
    if ($texto === '') {
        echo json_encode(['status' => 'error', 'msg' => 'El comentario no puede estar vacío.']);
        exit;
    }

    $encontrado = false;
    $nuevo_comentario = [
        'autor' => mb_substr($autor, 0, 60, 'UTF-8'),
        'texto' => mb_substr($texto, 0, 1000, 'UTF-8'),
        'fecha' => date('Y-m-d H:i')
    ];

    foreach ($articulos as &$a) {
        if (strval($a['id']) === strval($id_art)) {
            if (!isset($a['comentarios']) || !is_array($a['comentarios'])) {
                $a['comentarios'] = [];
            }
            $a['comentarios'][] = $nuevo_comentario;
            $encontrado = true;
            break;
        }
    }
    unset($a);

    if ($encontrado) {
        file_put_contents($archivo_json, json_encode($articulos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        echo json_encode(['status' => 'success', 'comentario' => $nuevo_comentario]);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Artículo no encontrado.']);
    }
    exit;
}

// Buscar artículo activo
$articulo = null;
foreach ($articulos as $art) {
    if ((!isset($art['eliminado']) || $art['eliminado'] !== true) && strval($art['id']) === strval($id_actual)) {
        $articulo = $art;
        break;
    }
}

if (!$articulo) {
    header("Location: index.php");
    exit;
}

$titulo_raw = trim($articulo['titulo'] ?? 'Artículo');
$subtitulo_raw = trim($articulo['subtitulo'] ?? '');
$resumen_raw = trim($articulo['resumen'] ?? '');
$contenido_raw = trim($articulo['contenido_completo'] ?? '');
$texto_lectura = ($contenido_raw !== '') ? $contenido_raw : $resumen_raw;

// Limpieza de descripción para meta description y og:description
$desc_fuente = ($resumen_raw !== '') ? $resumen_raw : $texto_lectura;
$desc_limpia = trim(preg_replace('/\s+/u', ' ', $desc_fuente));
$meta_desc_180 = mb_substr($desc_limpia, 0, 180, 'UTF-8');
$og_desc_200   = mb_substr($desc_limpia, 0, 200, 'UTF-8');

$categoria_raw = mb_strtoupper(trim($articulo['categoria'] ?? 'NOTICIA'), 'UTF-8');
if ($categoria_raw === '') $categoria_raw = 'NOTICIA';

$fecha_raw = trim($articulo['fecha'] ?? '');
$fuente_raw = trim($articulo['fuente'] ?? '');

$img_rel = trim($articulo['imagen'] ?? '');
if ($img_rel === '' || !file_exists(__DIR__ . '/' . $img_rel)) {
    $img_rel = 'images/default.jpg';
}

$img_mime = 'image/jpeg';
$ruta_fisica_img = __DIR__ . '/' . $img_rel;
if (file_exists($ruta_fisica_img)) {
    $info_img = @getimagesize($ruta_fisica_img);
    if ($info_img !== false) {
        $img_mime = $info_img['mime'] ?? 'image/jpeg';
    }
}

$es_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) 
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

$protocolo = $es_https ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
$base_dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$url_base_portal = $protocolo . $host . ($base_dir !== '' ? $base_dir : '');
$url_canonica = $url_base_portal . '/articulo.php?id=' . rawurlencode(strval($articulo['id']));
$url_imagen_og = $url_base_portal . '/' . ltrim($img_rel, '/');

$likes = intval($articulo['likes'] ?? 0);
$sads = intval($articulo['sads'] ?? 0);
$comentarios = is_array($articulo['comentarios'] ?? null) ? $articulo['comentarios'] : [];
?>
<!DOCTYPE html>
<html lang="es" prefix="og: https://ogp.me/ns#">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($titulo_raw, ENT_QUOTES, 'UTF-8'); ?> | Portal Amazonía</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($meta_desc_180, ENT_QUOTES, 'UTF-8'); ?>">

    <!-- Metatags Open Graph compactos (300x200 + summary) para tarjeta unificada de foto + texto -->
    <meta property="og:site_name" content="Portal Amazonía">
    <meta property="og:title" content="<?php echo htmlspecialchars($titulo_raw, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($og_desc_200, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?php echo htmlspecialchars($url_canonica, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($url_imagen_og, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image:type" content="<?php echo htmlspecialchars($img_mime, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image:width" content="300">
    <meta property="og:image:height" content="200">
    <meta name="twitter:card" content="summary">

    <style>
        :root {
            --vibrant-green: #1D9A4C;
            --accent-gold: #FFB81C;
            --dark-bg: #111111;
            --panel-bg: #1a1a1a;
            --text-main: #E6E6E6;
        }
        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }
        body {
            font-family: 'Segoe UI', Roboto, sans-serif;
            background-color: var(--dark-bg);
            color: var(--text-main);
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            -webkit-text-size-adjust: 100%;
        }

        /* BARRA DE NAVEGACIÓN SUPERIOR */
        nav {
            position: sticky;
            top: 0;
            background: rgba(10, 10, 10, 0.96);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 1000;
            border-bottom: 1px solid #282828;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.65);
        }
        .nav-inner {
            max-width: 1320px;
            margin: 0 auto;
            padding: 11px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }
        .btn-volver-portal {
            color: var(--accent-gold);
            text-decoration: none;
            font-weight: 800;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border: 1px solid rgba(255, 184, 28, 0.45);
            border-radius: 5px;
            transition: background 0.15s, color 0.15s;
        }
        .btn-volver-portal:hover {
            background: var(--accent-gold);
            color: #000;
        }
        .nav-marca {
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 1.5px;
            color: #aaa;
            text-transform: uppercase;
        }

        /* CONTENEDOR PRINCIPAL DE DOS COLUMNAS EN PC (FOTO IZQUIERDA / TEXTO DERECHA) */
        .articulo-layout {
            max-width: 1320px;
            margin: 0 auto;
            padding: 24px 20px 60px 20px;
            display: flex;
            flex-direction: row;
            align-items: flex-start;
            gap: 32px;
        }

        /* COLUMNA IZQUIERDA EN PC: FOTO FIJA (STICKY) VISIBLE DURANTE TODA LA LECTURA */
        .columna-foto-izquierda {
            flex: 0 0 40%;
            max-width: 480px;
            position: sticky;
            top: 68px;
            z-index: 20;
        }
        .hero-foto-marco {
            width: 100%;
            border-radius: 10px;
            overflow: hidden;
            background: #080808;
            border: 1px solid #2e2e2e;
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.8);
        }
        .hero-foto-img {
            width: 100%;
            max-height: calc(100vh - 110px);
            object-fit: cover;
            object-position: center 20%;
            display: block;
        }
        .pie-foto-izquierda {
            margin-top: 10px;
            font-size: 12px;
            color: #999;
            line-height: 1.45;
            padding: 0 2px;
        }

        /* COLUMNA DERECHA EN PC: LECTURA LIMPIA */
        .columna-texto-derecha {
            flex: 1 1 60%;
            min-width: 0;
        }

        /* EN TELÉFONO: UNA SOLA COLUMNA Y LA FOTO VA CON EL SCROLL NATURAL */
        @media (max-width: 768px) {
            .articulo-layout {
                flex-direction: column;
                padding: 14px 14px 48px 14px;
                gap: 16px;
            }
            .columna-foto-izquierda {
                position: static;
                flex: none;
                width: 100%;
                max-width: 100%;
            }
            .hero-foto-img {
                max-height: 300px;
            }
            .columna-texto-derecha {
                width: 100%;
            }
        }

        /* CABECERA Y CUERPO DE LECTURA */
        .meta-fila {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 12px;
        }
        .badge-cat {
            background: var(--accent-gold);
            color: #000;
            font-weight: 900;
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        .meta-texto {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        h1.titulo-articulo {
            margin: 0 0 10px 0;
            font-size: clamp(24px, 3.6vw, 38px);
            line-height: 1.2;
            color: #ffffff;
            font-weight: 900;
        }
        .subtitulo-articulo {
            margin: 0 0 16px 0;
            font-size: clamp(16px, 2.2vw, 20px);
            color: var(--accent-gold);
            font-weight: 600;
            line-height: 1.4;
        }

        /* BARRA DE HERRAMIENTAS: VOZ Y UN SOLO BOTÓN COMPARTIR */
        .herramientas-lectura {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            margin-bottom: 20px;
            border-top: 1px solid #262626;
            border-bottom: 1px solid #262626;
        }
        .btn-voz {
            background: #1b1b1b;
            color: var(--accent-gold);
            border: 1px solid var(--accent-gold);
            padding: 9px 16px;
            border-radius: 20px;
            font-size: 13.5px;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.15s, color 0.15s;
        }
        .btn-voz:hover {
            background: var(--accent-gold);
            color: #000;
        }
        .btn-compartir-unico {
            background: var(--vibrant-green);
            color: #fff;
            border: none;
            padding: 9px 18px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 7px;
            transition: background 0.15s;
        }
        .btn-compartir-unico:hover {
            background: #17803e;
        }

        /* TEXTO JUSTIFICADO DE ALTA LEGIBILIDAD */
        .cuerpo-lectura {
            font-size: 18px;
            line-height: 1.85;
            color: #E2E2E2;
            text-align: justify;
            hyphens: auto;
            -webkit-hyphens: auto;
        }
        .cuerpo-lectura p {
            margin: 0 0 22px 0;
        }
        .resumen-destacado {
            background: #171717;
            border-left: 4px solid var(--accent-gold);
            padding: 14px 18px;
            border-radius: 0 6px 6px 0;
            margin-bottom: 24px;
            font-size: 16.5px;
            line-height: 1.65;
            color: #d6d6d6;
            text-align: justify;
        }

        /* REACCIONES INSTANTÁNEAS MÁS GRANDES Y VISIBLES */
        .seccion-reacciones {
            margin-top: 32px;
            padding: 18px;
            background: #171717;
            border: 1px solid #2b2b2b;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }
        .seccion-reacciones span.label-reac {
            font-weight: 800;
            font-size: 15px;
            color: #e6e6e6;
        }
        .botones-reaccion-grupo {
            display: flex;
            gap: 12px;
        }
        .btn-reaccion {
            background: #222;
            border: 1px solid #484848;
            color: #efefef;
            border-radius: 8px;
            padding: 11px 20px;
            font-size: 17px;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.08s, background 0.15s, color 0.15s;
        }
        .btn-reaccion .emoji-reac {
            font-size: 21px;
            line-height: 1;
        }
        .btn-reaccion:active {
            transform: scale(0.95);
        }
        .btn-reaccion.seleccionado {
            background: var(--accent-gold);
            color: #000;
            border-color: var(--accent-gold);
        }

        /* SECCIÓN DE COMENTARIOS */
        .seccion-comentarios {
            margin-top: 34px;
            padding-top: 24px;
            border-top: 1px solid #2b2b2b;
        }
        .seccion-comentarios h3 {
            margin: 0 0 16px 0;
            font-size: 19px;
            color: var(--accent-gold);
        }
        .form-comentario {
            display: flex;
            flex-direction: column;
            gap: 10px;
            background: #171717;
            padding: 16px;
            border-radius: 8px;
            border: 1px solid #2b2b2b;
            margin-bottom: 22px;
        }
        .input-com {
            width: 100%;
            background: #101010;
            border: 1px solid #353535;
            color: #fff;
            padding: 10px 12px;
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
        }
        .input-com:focus {
            outline: none;
            border-color: var(--accent-gold);
        }
        .btn-enviar-com {
            align-self: flex-start;
            background: var(--accent-gold);
            color: #000;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            font-weight: 900;
            font-size: 13px;
            cursor: pointer;
            text-transform: uppercase;
        }
        .btn-enviar-com:hover {
            background: #fff;
        }
        .lista-comentarios {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .item-comentario {
            background: #161616;
            border: 1px solid #282828;
            border-radius: 6px;
            padding: 12px 14px;
        }
        .item-comentario-head {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: var(--accent-gold);
            font-weight: 700;
            margin-bottom: 6px;
        }
        .item-comentario-head span.fecha-com {
            color: #777;
            font-weight: normal;
        }
        .item-comentario p {
            margin: 0;
            font-size: 14.5px;
            line-height: 1.55;
            color: #ddd;
        }

        /* MODAL UNIFICADO DE COMPARTIR */
        .modal-compartir-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.88);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            z-index: 5000;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .modal-compartir-overlay.abierto {
            display: flex;
        }
        .modal-compartir-caja {
            background: #161616;
            border: 1px solid #353535;
            border-radius: 12px;
            width: 100%;
            max-width: 470px;
            padding: 18px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.95);
        }
        .modal-compartir-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        .modal-compartir-header h4 {
            margin: 0;
            color: var(--accent-gold);
            font-size: 15px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        .btn-cerrar-compartir {
            background: #262626;
            color: #fff;
            border: 1px solid #444;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-cerrar-compartir:hover {
            background: var(--accent-gold);
            color: #000;
        }
        .tarjeta-previa-compartir {
            display: flex;
            gap: 12px;
            background: #0e0e0e;
            border: 1px solid #2e2e2e;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 14px;
            align-items: center;
        }
        .tarjeta-previa-compartir img {
            width: 96px;
            height: 96px;
            object-fit: cover;
            border-radius: 6px;
            flex-shrink: 0;
            background: #000;
        }
        .tarjeta-previa-info {
            min-width: 0;
            flex: 1;
        }
        .tarjeta-previa-info strong {
            display: block;
            color: #fff;
            font-size: 13.5px;
            line-height: 1.3;
            margin-bottom: 4px;
        }
        .tarjeta-previa-info p {
            margin: 0;
            font-size: 12px;
            color: #bbb;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .grid-redes-compartir {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }
        .btn-red-opcion {
            background: #212121;
            color: #fff;
            border: 1px solid #383838;
            border-radius: 6px;
            padding: 10px 12px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            transition: background 0.15s, border-color 0.15s;
        }
        .btn-red-opcion:hover {
            border-color: var(--accent-gold);
            background: #2b2b2b;
        }
        .btn-red-opcion.wa { border-left: 4px solid #25D366; }
        .btn-red-opcion.fb { border-left: 4px solid #1877F2; }
        .btn-red-opcion.ig { border-left: 4px solid #E1306C; }
        .btn-red-opcion.tg { border-left: 4px solid #26A5E4; }
        .btn-red-opcion.xx { border-left: 4px solid #ffffff; }
        .btn-red-opcion.tk { border-left: 4px solid #FE2C55; }
        .btn-red-opcion.li { border-left: 4px solid #0A66C2; }
        .btn-red-opcion.em { border-left: 4px solid #FFB81C; }
        .btn-red-opcion.nativo {
            grid-column: span 2;
            background: var(--vibrant-green);
            border-color: var(--vibrant-green);
            color: #fff;
        }
        .btn-red-opcion.copiar {
            grid-column: span 2;
            background: var(--accent-gold);
            border-color: var(--accent-gold);
            color: #000;
            font-weight: 900;
        }

        /* TOAST FLOTANTE */
        #toast-notificacion {
            position: fixed;
            bottom: 22px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: var(--accent-gold);
            color: #000;
            font-weight: 800;
            font-size: 13px;
            padding: 11px 20px;
            border-radius: 30px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.85);
            z-index: 9999;
            opacity: 0;
            pointer-events: none;
            transition: transform 0.22s ease, opacity 0.22s ease;
        }
        #toast-notificacion.mostrar {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }

        @media (max-width: 768px) {
            .cuerpo-lectura {
                font-size: 16.5px;
                line-height: 1.7;
            }
        }
    </style>
</head>
<body>

<nav>
    <div class="nav-inner">
        <a href="index.php" class="btn-volver-portal">← Volver al Portal</a>
        <span class="nav-marca">PORTAL AMAZONÍA</span>
    </div>
</nav>

<main class="articulo-layout">
    <!-- COSTADO IZQUIERDO EN PC (STICKY SIEMPRE VISIBLE) / ARRIBA CON SCROLL EN TELÉFONO -->
    <aside class="columna-foto-izquierda">
        <div class="hero-foto-marco">
            <img src="<?php echo htmlspecialchars($img_rel, ENT_QUOTES, 'UTF-8'); ?>"
                 id="imgPrincipalArticulo"
                 class="hero-foto-img"
                 alt="<?php echo htmlspecialchars($titulo_raw, ENT_QUOTES, 'UTF-8'); ?>"
                 onerror="this.onerror=null; this.src='images/default.jpg';">
        </div>
        <?php if ($fuente_raw !== '' || $fecha_raw !== ''): ?>
            <div class="pie-foto-izquierda">
                <?php echo htmlspecialchars($titulo_raw, ENT_QUOTES, 'UTF-8'); ?>
                <?php echo $fuente_raw !== '' ? ' — ' . htmlspecialchars($fuente_raw, ENT_QUOTES, 'UTF-8') : ''; ?>
            </div>
        <?php endif; ?>
    </aside>

    <!-- COSTADO DERECHO EN PC: LECTURA LIMPIA, HERRAMIENTAS, REACCIONES Y COMENTARIOS -->
    <div class="columna-texto-derecha">
        <article>
            <div class="meta-fila">
                <span class="badge-cat"><?php echo htmlspecialchars($categoria_raw, ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="meta-texto">
                    <?php echo htmlspecialchars($fecha_raw, ENT_QUOTES, 'UTF-8'); ?>
                    <?php echo $fuente_raw !== '' ? ' | ' . htmlspecialchars($fuente_raw, ENT_QUOTES, 'UTF-8') : ''; ?>
                </span>
            </div>

            <h1 class="titulo-articulo" id="tituloArticulo"><?php echo htmlspecialchars($titulo_raw, ENT_QUOTES, 'UTF-8'); ?></h1>

            <?php if ($subtitulo_raw !== ''): ?>
                <p class="subtitulo-articulo"><?php echo htmlspecialchars($subtitulo_raw, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <div class="herramientas-lectura">
                <button type="button" class="btn-voz" id="btnVozArticulo" onclick="toggleVozArticulo()">▶ Escuchar artículo</button>
                <button type="button" class="btn-compartir-unico" onclick="abrirModalCompartirArticulo()">🔗 Compartir</button>
            </div>

            <?php if ($resumen_raw !== '' && $contenido_raw !== ''): ?>
                <div class="resumen-destacado">
                    <?php echo htmlspecialchars($resumen_raw, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <div class="cuerpo-lectura" id="cuerpoLectura">
                <?php
                $parrafos = preg_split('/\r?\n+/', $texto_lectura);
                foreach ($parrafos as $p) {
                    $p_limpio = trim($p);
                    if ($p_limpio !== '') {
                        echo '<p>' . htmlspecialchars($p_limpio, ENT_QUOTES, 'UTF-8') . '</p>' . "\n";
                    }
                }
                ?>
            </div>
        </article>

        <section class="seccion-reacciones">
            <span class="label-reac">¿Qué te pareció esta publicación?</span>
            <div class="botones-reaccion-grupo">
                <button type="button" class="btn-reaccion" id="btn-like-<?php echo htmlspecialchars(strval($articulo['id']), ENT_QUOTES, 'UTF-8'); ?>" onclick="reaccionar('<?php echo htmlspecialchars(strval($articulo['id']), ENT_QUOTES, 'UTF-8'); ?>', 'like')">
                    <span class="emoji-reac">👍</span> <span id="likes-<?php echo htmlspecialchars(strval($articulo['id']), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $likes; ?></span>
                </button>
                <button type="button" class="btn-reaccion" id="btn-sad-<?php echo htmlspecialchars(strval($articulo['id']), ENT_QUOTES, 'UTF-8'); ?>" onclick="reaccionar('<?php echo htmlspecialchars(strval($articulo['id']), ENT_QUOTES, 'UTF-8'); ?>', 'sad')">
                    <span class="emoji-reac">😢</span> <span id="sads-<?php echo htmlspecialchars(strval($articulo['id']), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $sads; ?></span>
                </button>
            </div>
        </section>

        <section class="seccion-comentarios">
            <h3>Comentarios (<span id="contadorComentarios"><?php echo count($comentarios); ?></span>)</h3>

            <form class="form-comentario" onsubmit="enviarComentario(event)">
                <input type="text" id="autorComentario" class="input-com" placeholder="Tu nombre (opcional)" maxlength="60">
                <textarea id="textoComentario" class="input-com" rows="3" placeholder="Escribe tu comentario sobre esta noticia..." maxlength="1000" required></textarea>
                <button type="submit" class="btn-enviar-com">Publicar comentario</button>
            </form>

            <div class="lista-comentarios" id="listaComentarios">
                <?php if (empty($comentarios)): ?>
                    <p id="sinComentariosMsg" style="color: #888; font-size: 14px;">Sé el primero en comentar este artículo.</p>
                <?php else: ?>
                    <?php foreach (array_reverse($comentarios) as $com): ?>
                        <div class="item-comentario">
                            <div class="item-comentario-head">
                                <span><?php echo htmlspecialchars($com['autor'] ?? 'Lector', ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="fecha-com"><?php echo htmlspecialchars($com['fecha'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <p><?php echo htmlspecialchars($com['texto'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>

<!-- MODAL UNIFICADO DE COMPARTIR -->
<div id="modalCompartirArticulo" class="modal-compartir-overlay" onclick="if(event.target===this) cerrarModalCompartirArticulo()">
    <div class="modal-compartir-caja">
        <div class="modal-compartir-header">
            <h4>Compartir Artículo</h4>
            <button type="button" class="btn-cerrar-compartir" onclick="cerrarModalCompartirArticulo()">×</button>
        </div>

        <div class="tarjeta-previa-compartir">
            <img src="<?php echo htmlspecialchars($img_rel, ENT_QUOTES, 'UTF-8'); ?>" alt="Vista previa" onerror="this.onerror=null; this.src='images/default.jpg';">
            <div class="tarjeta-previa-info">
                <strong><?php echo htmlspecialchars($titulo_raw, ENT_QUOTES, 'UTF-8'); ?></strong>
                <p><?php echo htmlspecialchars($og_desc_200, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>

        <div class="grid-redes-compartir">
            <a id="artShareWA" href="#" target="_blank" rel="noopener" class="btn-red-opcion wa">WhatsApp</a>
            <a id="artShareFB" href="#" target="_blank" rel="noopener" class="btn-red-opcion fb">Facebook</a>
            <button type="button" onclick="compartirInstagramArt()" class="btn-red-opcion ig">Instagram</button>
            <a id="artShareTG" href="#" target="_blank" rel="noopener" class="btn-red-opcion tg">Telegram</a>
            <a id="artShareX" href="#" target="_blank" rel="noopener" class="btn-red-opcion xx">X (Twitter)</a>
            <button type="button" onclick="compartirTikTokArt()" class="btn-red-opcion tk">TikTok</button>
            <a id="artShareLI" href="#" target="_blank" rel="noopener" class="btn-red-opcion li">LinkedIn</a>
            <a id="artShareEmail" href="#" class="btn-red-opcion em">Correo</a>
            <button type="button" onclick="compartirPaginaConFoto()" class="btn-red-opcion nativo">📲 Compartir desde el dispositivo (foto miniatura + texto)</button>
            <button type="button" onclick="copiarArticuloCompleto()" class="btn-red-opcion copiar">📋 Copiar título, resumen y enlace</button>
        </div>
    </div>
</div>

<div id="toast-notificacion"></div>

<script>
    const ARTICULO_ID = <?php echo json_encode(strval($articulo['id'])); ?>;
    const ARTICULO_URL = <?php echo json_encode($url_canonica); ?>;
    const ARTICULO_TITULO = <?php echo json_encode($titulo_raw); ?>;
    const ARTICULO_RESUMEN = <?php echo json_encode(mb_substr($og_desc_200, 0, 200, 'UTF-8')); ?>;

    let toastTimer = null;
    function mostrarToast(mensaje) {
        const toast = document.getElementById('toast-notificacion');
        toast.textContent = mensaje;
        toast.classList.add('mostrar');
        if (toastTimer) clearTimeout(toastTimer);
        toastTimer = setTimeout(() => {
            toast.classList.remove('mostrar');
        }, 2800);
    }

    document.addEventListener('DOMContentLoaded', () => {
        const estado = localStorage.getItem('reaccion_' + ARTICULO_ID);
        if (estado) {
            const btn = document.getElementById('btn-' + estado + '-' + ARTICULO_ID);
            if (btn) btn.classList.add('seleccionado');
        }

        const textoWA = `*${ARTICULO_TITULO}*\n${ARTICULO_RESUMEN}\n${ARTICULO_URL}`;
        document.getElementById('artShareWA').href = `https://api.whatsapp.com/send?text=${encodeURIComponent(textoWA)}`;
        document.getElementById('artShareFB').href = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(ARTICULO_URL)}`;
        document.getElementById('artShareTG').href = `https://t.me/share/url?url=${encodeURIComponent(ARTICULO_URL)}&text=${encodeURIComponent(ARTICULO_TITULO + ' — ' + ARTICULO_RESUMEN)}`;
        document.getElementById('artShareX').href = `https://twitter.com/intent/tweet?url=${encodeURIComponent(ARTICULO_URL)}&text=${encodeURIComponent(ARTICULO_TITULO)}`;
        document.getElementById('artShareLI').href = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(ARTICULO_URL)}`;
        document.getElementById('artShareEmail').href = `mailto:?subject=${encodeURIComponent(ARTICULO_TITULO)}&body=${encodeURIComponent(textoWA)}`;
    });

    // Reacciones instantáneas 0ms
    function reaccionar(id, tipo) {
        const claveLocal = `reaccion_${id}`;
        const previo = localStorage.getItem(claveLocal) || '';

        const spanLikes = document.getElementById(`likes-${id}`);
        const spanSads = document.getElementById(`sads-${id}`);
        const btnLike = document.getElementById(`btn-like-${id}`);
        const btnSad = document.getElementById(`btn-sad-${id}`);

        let likesActuales = parseInt(spanLikes.textContent, 10) || 0;
        let sadsActuales = parseInt(spanSads.textContent, 10) || 0;
        let nuevoEstado = '';

        if (previo === tipo) {
            if (tipo === 'like') likesActuales = Math.max(0, likesActuales - 1);
            if (tipo === 'sad') sadsActuales = Math.max(0, sadsActuales - 1);
            nuevoEstado = '';
        } else {
            if (previo === 'like') likesActuales = Math.max(0, likesActuales - 1);
            if (previo === 'sad') sadsActuales = Math.max(0, sadsActuales - 1);
            if (tipo === 'like') likesActuales++;
            if (tipo === 'sad') sadsActuales++;
            nuevoEstado = tipo;
        }

        spanLikes.textContent = likesActuales;
        spanSads.textContent = sadsActuales;
        if (btnLike) btnLike.classList.remove('seleccionado');
        if (btnSad) btnSad.classList.remove('seleccionado');

        if (nuevoEstado !== '') {
            const btnActivo = document.getElementById(`btn-${nuevoEstado}-${id}`);
            if (btnActivo) btnActivo.classList.add('seleccionado');
            localStorage.setItem(claveLocal, nuevoEstado);
        } else {
            localStorage.removeItem(claveLocal);
        }

        fetch(`articulo.php?ajax_reaccionar=1&id=${encodeURIComponent(id)}&tipo=${encodeURIComponent(tipo)}&previo=${encodeURIComponent(previo)}`, {
            cache: 'no-store'
        }).catch(() => {});
    }

    // Lectura en voz alta
    let synth = window.speechSynthesis;
    let leyendoVoz = false;

    function toggleVozArticulo() {
        if (!synth) {
            mostrarToast("Tu navegador no soporta lectura de voz.");
            return;
        }
        const btn = document.getElementById('btnVozArticulo');
        if (leyendoVoz) {
            synth.cancel();
            leyendoVoz = false;
            btn.textContent = "▶ Escuchar artículo";
            return;
        }
        const titulo = document.getElementById('tituloArticulo').innerText;
        const cuerpo = document.getElementById('cuerpoLectura').innerText;
        const utter = new SpeechSynthesisUtterance(titulo + ". " + cuerpo);
        utter.lang = 'es-ES';
        utter.rate = 1.0;
        utter.onend = () => {
            leyendoVoz = false;
            btn.textContent = "▶ Escuchar artículo";
        };
        synth.speak(utter);
        leyendoVoz = true;
        btn.textContent = "❚❚ Detener lectura";
    }

    window.addEventListener('beforeunload', () => {
        if (synth && leyendoVoz) synth.cancel();
    });

    // Comentarios AJAX
    function escaparHTML(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function enviarComentario(e) {
        e.preventDefault();
        const autorInput = document.getElementById('autorComentario');
        const textoInput = document.getElementById('textoComentario');
        const texto = textoInput.value.trim();
        if (!texto) return;

        const formData = new URLSearchParams();
        formData.append('ajax_comentar', '1');
        formData.append('id', ARTICULO_ID);
        formData.append('autor', autorInput.value.trim() || 'Lector');
        formData.append('comentario', texto);

        fetch('articulo.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData.toString()
        })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success' && data.comentario) {
                const sinMsg = document.getElementById('sinComentariosMsg');
                if (sinMsg) sinMsg.remove();

                const lista = document.getElementById('listaComentarios');
                const item = document.createElement('div');
                item.className = 'item-comentario';
                item.innerHTML = `
                    <div class="item-comentario-head">
                        <span>${escaparHTML(data.comentario.autor)}</span>
                        <span class="fecha-com">${escaparHTML(data.comentario.fecha)}</span>
                    </div>
                    <p>${escaparHTML(data.comentario.texto)}</p>
                `;
                lista.prepend(item);

                const contador = document.getElementById('contadorComentarios');
                contador.textContent = (parseInt(contador.textContent, 10) || 0) + 1;
                textoInput.value = '';
                mostrarToast("💬 Comentario publicado.");
            }
        })
        .catch(() => mostrarToast("No se pudo publicar el comentario."));
    }

    function abrirModalCompartirArticulo() {
        document.getElementById('modalCompartirArticulo').classList.add('abierto');
    }

    function cerrarModalCompartirArticulo() {
        document.getElementById('modalCompartirArticulo').classList.remove('abierto');
    }

    function copiarArticuloCompleto() {
        const textoFormateado = `*${ARTICULO_TITULO}*\n\n${ARTICULO_RESUMEN}\n\n${ARTICULO_URL}`;
        if (navigator.clipboard) {
            navigator.clipboard.writeText(textoFormateado).then(() => {
                mostrarToast("📋 ¡Tarjeta de texto y enlace copiada!");
            });
        }
    }

    function compartirInstagramArt() {
        copiarArticuloCompleto();
        window.open('https://www.instagram.com/', '_blank', 'noopener');
    }

    function compartirTikTokArt() {
        copiarArticuloCompleto();
        window.open('https://www.tiktok.com/', '_blank', 'noopener');
    }

    // Miniatura ultra compacta (180px) para que las redes mantengan foto y texto juntos
    function obtenerArchivoDesdeImgDOM(imgElement) {
        return new Promise((resolve) => {
            try {
                if (!imgElement || !imgElement.complete || imgElement.naturalWidth === 0) {
                    resolve(null);
                    return;
                }
                const canvas = document.createElement('canvas');
                const maxDim = 180;
                let w = imgElement.naturalWidth;
                let h = imgElement.naturalHeight;
                if (w > maxDim || h > maxDim) {
                    if (w > h) {
                        h = Math.max(1, Math.round((h * maxDim) / w));
                        w = maxDim;
                    } else {
                        w = Math.max(1, Math.round((w * maxDim) / h));
                        h = maxDim;
                    }
                }
                canvas.width = w;
                canvas.height = h;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(imgElement, 0, 0, w, h);
                canvas.toBlob((blob) => {
                    if (blob) {
                        resolve(new File([blob], 'noticia_amazonia.jpg', { type: 'image/jpeg' }));
                    } else {
                        resolve(null);
                    }
                }, 'image/jpeg', 0.62);
            } catch (e) {
                resolve(null);
            }
        });
    }

    async function compartirPaginaConFoto() {
        const textoFormateado = `*${ARTICULO_TITULO}*\n\n${ARTICULO_RESUMEN}\n\n${ARTICULO_URL}`;
        const imgEl = document.getElementById('imgPrincipalArticulo');

        if (navigator.share) {
            try {
                const archivoFoto = await obtenerArchivoDesdeImgDOM(imgEl);
                if (archivoFoto && navigator.canShare && navigator.canShare({ files: [archivoFoto] })) {
                    await navigator.share({
                        files: [archivoFoto],
                        title: ARTICULO_TITULO,
                        text: textoFormateado
                    });
                    cerrarModalCompartirArticulo();
                    return;
                }
            } catch (err) {
                if (err.name === 'AbortError') return;
            }

            try {
                await navigator.share({
                    title: ARTICULO_TITULO,
                    text: `${ARTICULO_TITULO} — ${ARTICULO_RESUMEN}`,
                    url: ARTICULO_URL
                });
                cerrarModalCompartirArticulo();
                return;
            } catch (err) {
                if (err.name === 'AbortError') return;
            }
        }

        copiarArticuloCompleto();
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') cerrarModalCompartirArticulo();
    });
</script>

</body>
</html>