<?php
// index.php - Portada con Mosaico Asimétrico de Espaciado Uniforme (Masonry), Categorías, Reacciones Instantáneas (0ms) y Compartido con Foto Real Adjunta
$archivo_json = __DIR__ . '/articulos.json';
$articulos = [];

if (file_exists($archivo_json)) {
    $contenido = file_get_contents($archivo_json);
    $articulos = json_decode($contenido, true) ?: [];
}

// API AJAX para procesar reacciones con opción de cambio o anulación
if (isset($_GET['ajax_reaccionar']) && isset($_GET['id']) && isset($_GET['tipo'])) {
    header('Content-Type: application/json');
    $tipo = $_GET['tipo']; // 'like' o 'sad'
    $previo = $_GET['previo'] ?? ''; // 'like', 'sad' o ''
    $id_art = $_GET['id'];
    $encontrado = false;
    $likes = 0;
    $sads = 0;
    $nuevo_estado = '';

    foreach ($articulos as &$a) {
        if (strval($a['id']) === strval($id_art)) {
            if ($previo === $tipo) {
                if ($tipo === 'like') {
                    $a['likes'] = max(0, intval($a['likes'] ?? 0) - 1);
                } elseif ($tipo === 'sad') {
                    $a['sads'] = max(0, intval($a['sads'] ?? 0) - 1);
                }
                $nuevo_estado = '';
            } else {
                if ($previo === 'like') {
                    $a['likes'] = max(0, intval($a['likes'] ?? 0) - 1);
                } elseif ($previo === 'sad') {
                    $a['sads'] = max(0, intval($a['sads'] ?? 0) - 1);
                }

                if ($tipo === 'like') {
                    $a['likes'] = intval($a['likes'] ?? 0) + 1;
                } elseif ($tipo === 'sad') {
                    $a['sads'] = intval($a['sads'] ?? 0) + 1;
                }
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
        file_put_contents($archivo_json, json_encode($articulos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo json_encode([
            'status' => 'success', 
            'likes' => $likes, 
            'sads' => $sads, 
            'nuevo_estado' => $nuevo_estado
        ]);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit;
}

// Filtrar artículos eliminados
$articulos = array_filter($articulos, function($art) {
    return !isset($art['eliminado']) || $art['eliminado'] !== true;
});

// Extraer categorías únicas y contar artículos por categoría
$conteo_categorias = [];
foreach ($articulos as $art) {
    $cat = strtoupper(trim($art['categoria'] ?? 'NOTICIA'));
    if ($cat === '') $cat = 'NOTICIA';
    if (!isset($conteo_categorias[$cat])) {
        $conteo_categorias[$cat] = 0;
    }
    $conteo_categorias[$cat]++;
}
ksort($conteo_categorias);

// Detección estricta de protocolo y dominio actual para construir enlaces absolutos
$es_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) 
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

$protocolo = $es_https ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
$base_dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$url_base_portal = $protocolo . $host . ($base_dir !== '' ? $base_dir : '');
?>
<!DOCTYPE html>
<html lang="es" prefix="og: https://ogp.me/ns#">
<head>
    <meta charset="UTF-8">
    <title>Portal Amazonía | Vanguardia y Territorio</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta property="og:site_name" content="Portal Amazonía">
    <meta property="og:title" content="Portal Amazonía | Vanguardia y Territorio">
    <meta property="og:description" content="Periodismo e investigación desde la Amazonía boliviana.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo htmlspecialchars($url_base_portal . '/index.php', ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($url_base_portal . '/images/default.jpg', ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:card" content="summary_large_image">
    <style>
        :root {
            --vibrant-green: #1D9A4C;
            --accent-gold: #FFB81C;
            --dark-bg: #121212;
            --panel-bg: #1e1e1e;
            --text-main: #E0E0E0;
            --grid-gap: 25px;
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
        a, button {
            touch-action: manipulation;
        }
        
        /* CABECERA Y NAVEGACIÓN */
        nav {
            position: sticky;
            top: 0;
            background: rgba(10, 10, 10, 0.96);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 1000;
            border-bottom: 1px solid #2a2a2a;
        }
        .nav-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 20px;
            max-width: 1400px;
            margin: 0 auto;
            flex-wrap: wrap;
            gap: 10px;
        }
        .nav-brand {
            color: white;
            text-decoration: none;
            font-weight: 900;
            font-size: clamp(16px, 4vw, 20px);
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .nav-brand span {
            color: var(--accent-gold);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .nav-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-mapa-nav {
            background: rgba(255, 184, 28, 0.12);
            color: var(--accent-gold);
            text-decoration: none;
            font-size: 12px;
            font-weight: bold;
            border: 1px solid var(--accent-gold);
            padding: 7px 12px;
            border-radius: 4px;
            transition: 0.15s;
        }
        .btn-mapa-nav:hover {
            background: var(--accent-gold);
            color: black;
        }
        .btn-admin-nav {
            color: var(--accent-gold);
            text-decoration: none;
            font-size: 12px;
            font-weight: bold;
            border: 1px solid var(--accent-gold);
            padding: 7px 14px;
            border-radius: 4px;
            transition: 0.15s;
        }
        .btn-admin-nav:hover {
            background: var(--accent-gold);
            color: black;
        }

        /* BARRA DE FILTRADO POR CATEGORÍAS */
        .categorias-wrapper {
            border-top: 1px solid #1f1f1f;
            background: #0d0d0d;
            padding: 10px 20px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .categorias-bar {
            display: flex;
            gap: 10px;
            max-width: 1400px;
            margin: 0 auto;
            align-items: center;
        }
        .btn-cat {
            background: #1a1a1a;
            color: #bbb;
            border: 1px solid #333;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.15s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-cat span {
            background: #2a2a2a;
            color: #999;
            padding: 1px 6px;
            border-radius: 10px;
            font-size: 10px;
        }
        .btn-cat:hover {
            border-color: var(--accent-gold);
            color: white;
        }
        .btn-cat.activo {
            background: var(--accent-gold);
            color: black;
            border-color: var(--accent-gold);
        }
        .btn-cat.activo span {
            background: rgba(0, 0, 0, 0.2);
            color: black;
        }

        /* CUADRÍCULA DE MOSAICO CON ESPACIADO UNIFORME EXACTO (25px) */
        .mosaico-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(min(100%, 300px), 1fr));
            grid-auto-rows: 1px;
            grid-auto-flow: dense;
            column-gap: var(--grid-gap);
            row-gap: 0px;
            max-width: 1400px;
            margin: 25px auto;
            padding: 0 16px;
            align-items: start;
        }

        .articulo-mosaico {
            position: relative;
            border-radius: 8px;
            background: #111111;
            border: 1px solid #2e2e2e;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            box-sizing: border-box;
            margin-bottom: var(--grid-gap);
            transition: border-color 0.2s;
        }
        .articulo-mosaico:hover {
            border-color: #444;
        }
        .articulo-mosaico.oculto {
            display: none !important;
        }

        @media (max-width: 680px) {
            .mosaico-container {
                display: flex;
                flex-direction: column;
                gap: var(--grid-gap);
            }
            .articulo-mosaico {
                margin-bottom: 0;
                width: 100%;
                padding: 16px;
            }
        }

        /* CONTENEDOR DE IMAGEN Y AJUSTE DE RECORTE (OBJECT-FIT) */
        .mosaico-img-container {
            width: 100%;
            background: #080808;
            border-radius: 6px;
            overflow: hidden;
            aspect-ratio: 16 / 9;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        .mosaico-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center 20%;
            transition: transform 0.3s ease;
        }
        .articulo-mosaico:hover .mosaico-img {
            transform: scale(1.03);
        }

        .contenido-columna {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .badge {
            background: var(--accent-gold);
            color: black;
            font-weight: 900;
            font-size: 10px;
            padding: 4px 9px;
            border-radius: 3px;
            text-transform: uppercase;
            align-self: flex-start;
            letter-spacing: 0.5px;
        }
        .fecha-tarjeta {
            font-size: 11px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: block;
            margin-bottom: 5px;
        }
        .texto-container h3 {
            margin: 4px 0 10px 0;
            font-size: 18px;
            color: white;
            line-height: 1.3;
        }
        .texto-container p {
            margin: 0;
            font-size: 14px;
            line-height: 1.6;
            color: #ccc;
            text-align: justify;
            hyphens: auto;
            -webkit-hyphens: auto;
        }

        /* REGLAS DE FORMATOS ASIMÉTRICOS EN ESCRITORIO */
        @media (min-width: 900px) {
            .articulo-mosaico.span-h-izq {
                grid-column: span 2;
                display: grid;
                grid-template-columns: 48% 1fr;
                gap: 22px;
                align-items: start;
            }
            .articulo-mosaico.span-h-izq .mosaico-img-container {
                aspect-ratio: 16 / 10;
                height: 100%;
                min-height: 220px;
            }

            .articulo-mosaico.span-h-der {
                grid-column: span 2;
                display: grid;
                grid-template-columns: 1fr 48%;
                gap: 22px;
                align-items: start;
            }
            .articulo-mosaico.span-h-der .mosaico-img-container {
                order: 2;
                aspect-ratio: 16 / 10;
                height: 100%;
                min-height: 220px;
            }
            .articulo-mosaico.span-h-der .contenido-columna {
                order: 1;
            }

            .articulo-mosaico.span-v {
                grid-column: span 1;
            }
            .articulo-mosaico.span-v .mosaico-img-container {
                aspect-ratio: 4 / 3;
            }
            .articulo-mosaico.span-v .texto-container h3 {
                font-size: 21px;
            }

            .articulo-mosaico.span-big {
                grid-column: span 2;
                border-color: rgba(255, 184, 28, 0.4);
            }
            .articulo-mosaico.span-big .mosaico-img-container {
                aspect-ratio: 16 / 9;
                max-height: 390px;
            }
            .articulo-mosaico.span-big .texto-container h3 {
                font-size: 26px;
                line-height: 1.25;
            }
            .articulo-mosaico.span-big .texto-container p {
                font-size: 15.5px;
            }
        }

        /* BOTONES DE ACCIÓN Y REACCIÓN PEGADOS AL TEXTO */
        .tarjeta-footer {
            margin-top: 12px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding-top: 14px;
            border-top: 1px solid #222;
        }
        .acciones-fila {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .btn-leer {
            padding: 10px 15px;
            background: var(--accent-gold);
            color: black;
            border: none;
            font-weight: 900;
            font-size: 11px;
            cursor: pointer;
            border-radius: 4px;
            text-transform: uppercase;
            text-decoration: none;
            text-align: center;
            flex-grow: 1;
            transition: transform 0.1s, background 0.15s;
        }
        .btn-leer:active {
            transform: scale(0.97);
        }
        .btn-leer:hover {
            background: white;
        }
        .btn-compartir-tarjeta {
            background: #222;
            color: white;
            border: 1px solid #444;
            padding: 8px 14px;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            transition: transform 0.1s, background 0.15s;
        }
        .btn-compartir-tarjeta:active {
            transform: scale(0.92);
        }
        .btn-compartir-tarjeta:hover {
            background: var(--accent-gold);
            color: black;
            border-color: var(--accent-gold);
        }

        .reacciones-bar {
            display: flex;
            gap: 10px;
        }
        .btn-reaccion {
            background: #1a1a1a;
            border: 1px solid #3a3a3a;
            color: #ccc;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            flex-grow: 1;
            justify-content: center;
            transition: transform 0.08s, background 0.12s, color 0.12s;
            user-select: none;
        }
        .btn-reaccion:active {
            transform: scale(0.93);
        }
        .btn-reaccion:hover {
            border-color: var(--accent-gold);
            color: white;
            background: rgba(255, 184, 28, 0.1);
        }
        .btn-reaccion.seleccionado {
            background: var(--accent-gold);
            color: black;
            border-color: var(--accent-gold);
        }

        /* NOTIFICACIÓN FLOTANTE INSTANTÁNEA (TOAST SIN RETARDO) */
        #toast-notificacion {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: var(--accent-gold);
            color: #000;
            font-weight: 800;
            font-size: 13px;
            padding: 12px 22px;
            border-radius: 30px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.8);
            z-index: 9999;
            opacity: 0;
            pointer-events: none;
            transition: transform 0.22s ease, opacity 0.22s ease;
            text-align: center;
            max-width: 90vw;
        }
        #toast-notificacion.mostrar {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }
    </style>
</head>
<body>

<nav>
    <div class="nav-top">
        <a href="index.php" class="nav-brand">
            PORTAL AMAZONÍA <span>Vanguardia y Territorio</span>
        </a>
        <div class="nav-actions">
            <a href="mapa.php" class="btn-mapa-nav">🗺️ Mapa Indígena</a>
            <a href="admin.php" class="btn-admin-nav">CMS Admin</a>
        </div>
    </div>

    <?php if (!empty($conteo_categorias)): ?>
    <div class="categorias-wrapper">
        <div class="categorias-bar">
            <button class="btn-cat activo" onclick="filtrarCategoria('TODAS', this)">
                Todas <span><?php echo count($articulos); ?></span>
            </button>
            <?php foreach ($conteo_categorias as $cat_nombre => $cantidad): ?>
                <button class="btn-cat" onclick="filtrarCategoria('<?php echo htmlspecialchars($cat_nombre, ENT_QUOTES, 'UTF-8'); ?>', this)">
                    <?php echo htmlspecialchars($cat_nombre, ENT_QUOTES, 'UTF-8'); ?>
                    <span><?php echo $cantidad; ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</nav>

<div class="mosaico-container" id="contenedorMosaico">
    <?php if (empty($articulos)): ?>
        <p style="text-align: center; color: #888; grid-column: 1 / -1;">No hay artículos publicados todavía. Utiliza <a href="admin.php" style="color:var(--accent-gold);">el panel CMS</a> para crear el primero.</p>
    <?php else: ?>
        <?php foreach ($articulos as $art): 
            $id = $art['id'] ?? '';
            $titulo_raw = trim($art['titulo'] ?? 'Sin título');
            $resumen_raw = trim($art['resumen'] ?? '');
            $titulo = htmlspecialchars($titulo_raw, ENT_QUOTES, 'UTF-8');
            $resumen = htmlspecialchars($resumen_raw, ENT_QUOTES, 'UTF-8');
            $categoriaRaw = strtoupper(trim($art['categoria'] ?? 'NOTICIA'));
            if ($categoriaRaw === '') $categoriaRaw = 'NOTICIA';
            $categoria = htmlspecialchars($categoriaRaw, ENT_QUOTES, 'UTF-8');
            $fecha = htmlspecialchars($art['fecha'] ?? '', ENT_QUOTES, 'UTF-8');
            $fuente = htmlspecialchars($art['fuente'] ?? '', ENT_QUOTES, 'UTF-8');
            $formato = htmlspecialchars($art['formato'] ?? '', ENT_QUOTES, 'UTF-8');
            
            $img_path = trim($art['imagen'] ?? '');
            if ($img_path !== '' && file_exists(__DIR__ . '/' . $img_path)) {
                $imagen = htmlspecialchars($img_path, ENT_QUOTES, 'UTF-8');
            } else {
                $imagen = 'images/default.jpg';
            }

            $likes = intval($art['likes'] ?? 0);
            $sads = intval($art['sads'] ?? 0);
            $url_articulo = $url_base_portal . "/articulo.php?id=" . rawurlencode($id);
        ?>
            <article class="articulo-mosaico <?php echo $formato; ?>" data-categoria="<?php echo $categoria; ?>">
                <div class="mosaico-img-container">
                    <img src="<?php echo $imagen; ?>" id="img-art-<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>" class="mosaico-img" alt="<?php echo $titulo; ?>" decoding="async" onload="ajustarMosaico()" onerror="this.onerror=null; this.src='images/default.jpg'; ajustarMosaico();">
                </div>

                <div class="contenido-columna">
                    <span class="badge"><?php echo $categoria; ?></span>
                    <div class="texto-container">
                        <span class="fecha-tarjeta"><?php echo $fecha; ?><?php echo $fuente ? " | $fuente" : ""; ?></span>
                        <h3><?php echo $titulo; ?></h3>
                        <p><?php echo $resumen; ?></p>
                    </div>
                    <div class="tarjeta-footer">
                        <div class="acciones-fila">
                            <a href="articulo.php?id=<?php echo rawurlencode($id); ?>" class="btn-leer">Leer Artículo (Inmersión)</a>
                            <button class="btn-compartir-tarjeta" 
                                    data-id="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-url="<?php echo htmlspecialchars($url_articulo, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-titulo="<?php echo $titulo; ?>"
                                    data-resumen="<?php echo htmlspecialchars(mb_substr($resumen_raw, 0, 220), ENT_QUOTES, 'UTF-8'); ?>"
                                    onclick="compartirConFotoReal(this)" 
                                    title="Compartir con foto adjunta, resumen y enlace">🔗</button>
                        </div>
                        <div class="reacciones-bar">
                            <button class="btn-reaccion" id="btn-like-<?php echo $id; ?>" onclick="reaccionar('<?php echo $id; ?>', 'like')">👍 <span id="likes-<?php echo $id; ?>"><?php echo $likes; ?></span></button>
                            <button class="btn-reaccion" id="btn-sad-<?php echo $id; ?>" onclick="reaccionar('<?php echo $id; ?>', 'sad')">😢 <span id="sads-<?php echo $id; ?>"><?php echo $sads; ?></span></button>
                        </div>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div id="toast-notificacion"></div>

<script>
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

    // Motor Masonry blindado contra bucles en iPhone SE
    let rafMosaico = null;
    function ajustarTarjeta(tarjeta) {
        if (window.innerWidth <= 680 || tarjeta.classList.contains('oculto')) {
            if (tarjeta.style.gridRowEnd !== 'auto') {
                tarjeta.style.gridRowEnd = 'auto';
            }
            return;
        }
        const gapVertical = 25;
        const alturaContenido = tarjeta.getBoundingClientRect().height;
        const filasTotales = Math.ceil(alturaContenido + gapVertical);
        const nuevoValor = `span ${filasTotales}`;
        if (tarjeta.style.gridRowEnd !== nuevoValor) {
            tarjeta.style.gridRowEnd = nuevoValor;
        }
    }

    function ajustarMosaico() {
        if (rafMosaico) cancelAnimationFrame(rafMosaico);
        rafMosaico = requestAnimationFrame(() => {
            const tarjetas = document.querySelectorAll('.articulo-mosaico');
            tarjetas.forEach(t => ajustarTarjeta(t));
        });
    }

    document.addEventListener("DOMContentLoaded", function() {
        <?php foreach ($articulos as $art): ?>
            (function() {
                let idArt = <?php echo json_encode(strval($art['id'])); ?>;
                let estado = localStorage.getItem('reaccion_' + idArt);
                if (estado) {
                    let btn = document.getElementById('btn-' + estado + '-' + idArt);
                    if (btn) btn.classList.add('seleccionado');
                }
            })();
        <?php endforeach; ?>

        ajustarMosaico();

        if ('ResizeObserver' in window) {
            const observer = new ResizeObserver(entries => {
                if (window.innerWidth <= 680) return;
                requestAnimationFrame(() => {
                    entries.forEach(entry => ajustarTarjeta(entry.target));
                });
            });
            document.querySelectorAll('.articulo-mosaico').forEach(t => observer.observe(t));
        }
    });

    window.addEventListener('load', ajustarMosaico);
    window.addEventListener('resize', ajustarMosaico);

    function filtrarCategoria(categoriaElegida, btnClickeado) {
        const botones = document.querySelectorAll('.btn-cat');
        botones.forEach(b => b.classList.remove('activo'));
        btnClickeado.classList.add('activo');

        const tarjetas = document.querySelectorAll('.articulo-mosaico');
        tarjetas.forEach(tarjeta => {
            const catTarjeta = tarjeta.getAttribute('data-categoria');
            if (categoriaElegida === 'TODAS' || catTarjeta === categoriaElegida) {
                tarjeta.classList.remove('oculto');
            } else {
                tarjeta.classList.add('oculto');
            }
        });
        ajustarMosaico();
    }

    // REACCIONES INSTANTÁNEAS EN 0 MILISEGUNDOS (OPTIMISTIC UI)
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

        // 1. Actualizar pantalla en 0ms sin esperar al servidor
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

        // 2. Guardar silenciosamente en segundo plano
        fetch(`index.php?ajax_reaccionar=1&id=${encodeURIComponent(id)}&tipo=${encodeURIComponent(tipo)}&previo=${encodeURIComponent(previo)}`, {
            cache: 'no-store'
        }).catch(() => {});
    }

    // Convierte la imagen ya cargada en el DOM a un archivo JPG comprimido en milisegundos
    function obtenerArchivoDesdeImgDOM(imgElement) {
        return new Promise((resolve) => {
            try {
                if (!imgElement || !imgElement.complete || imgElement.naturalWidth === 0) {
                    resolve(null);
                    return;
                }
                const canvas = document.createElement('canvas');
                const maxDim = 1200;
                let w = imgElement.naturalWidth;
                let h = imgElement.naturalHeight;
                if (w > maxDim || h > maxDim) {
                    if (w > h) {
                        h = Math.round((h * maxDim) / w);
                        w = maxDim;
                    } else {
                        w = Math.round((w * maxDim) / h);
                        h = maxDim;
                    }
                }
                canvas.width = w;
                canvas.height = h;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(imgElement, 0, 0, w, h);
                canvas.toBlob((blob) => {
                    if (blob) {
                        const archivo = new File([blob], 'noticia_amazonia.jpg', { type: 'image/jpeg' });
                        resolve(archivo);
                    } else {
                        resolve(null);
                    }
                }, 'image/jpeg', 0.85);
            } catch (e) {
                resolve(null);
            }
        });
    }

    // COMPARTIR CON FOTO FÍSICA ADJUNTA + TÍTULO + RESUMEN + ENLACE (IGUAL QUE PORTAL-IA-WEB.WEB.APP)
    async function compartirConFotoReal(btn) {
        const id = btn.getAttribute('data-id');
        const url = btn.getAttribute('data-url');
        const titulo = btn.getAttribute('data-titulo');
        const resumen = btn.getAttribute('data-resumen');
        const textoFormateado = `*${titulo}*\n\n${resumen}\n\n${url}`;

        const imgEl = document.getElementById(`img-art-${id}`);

        if (navigator.share) {
            try {
                const archivoFoto = await obtenerArchivoDesdeImgDOM(imgEl);
                if (archivoFoto && navigator.canShare && navigator.canShare({ files: [archivoFoto] })) {
                    await navigator.share({
                        files: [archivoFoto],
                        title: titulo,
                        text: textoFormateado
                    });
                    return;
                }
                // Si el dispositivo no soporta archivos adjuntos, comparte texto + enlace
                await navigator.share({
                    title: titulo,
                    text: `${titulo} — ${resumen}`,
                    url: url
                });
                return;
            } catch (err) {
                if (err.name === 'AbortError') return;
            }
        }

        // Respaldo instantáneo en portapapeles para PC de escritorio
        if (navigator.clipboard) {
            navigator.clipboard.writeText(textoFormateado).then(() => {
                mostrarToast("📋 ¡Copiado con título, resumen y enlace!");
            });
        }
    }
</script>

</body>
</html>