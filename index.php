<?php
// index.php - Portal Amazonía (Almacenamiento JSON Plano + Open Graph en Una Sola Tarjeta + Reacciones 0ms)
session_start();

$archivo_json = __DIR__ . '/articulos.json';
$articulos = [];

if (file_exists($archivo_json)) {
    $contenido = file_get_contents($archivo_json);
    $articulos = json_decode($contenido, true);
    if (!is_array($articulos)) {
        $articulos = [];
    }
}

// Endpoint asíncrono para guardar reacciones en tiempo real (0ms Optimistic UI en cliente)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'reaccionar') {
    header('Content-Type: application/json; charset=utf-8');
    $id = isset($_POST['id']) ? trim($_POST['id']) : '';
    $tipo = isset($_POST['tipo']) ? trim($_POST['tipo']) : '';
    $delta = isset($_POST['delta']) ? (int)$_POST['delta'] : 1;

    $tipos_validos = ['like', 'love', 'haha', 'wow', 'sad', 'angry'];
    if ($id !== '' && in_array($tipo, $tipos_validos, true)) {
        foreach ($articulos as &$art) {
            if ((string)$art['id'] === (string)$id) {
                if (!isset($art['reacciones']) || !is_array($art['reacciones'])) {
                    $art['reacciones'] = ['like' => 0, 'love' => 0, 'haha' => 0, 'wow' => 0, 'sad' => 0, 'angry' => 0];
                }
                if (!isset($art['reacciones'][$tipo])) {
                    $art['reacciones'][$tipo] = 0;
                }
                $art['reacciones'][$tipo] = max(0, (int)$art['reacciones'][$tipo] + $delta);
                file_put_contents($archivo_json, json_encode($articulos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
                echo json_encode(['ok' => true, 'reacciones' => $art['reacciones']]);
                exit;
            }
        }
    }
    echo json_encode(['ok' => false]);
    exit;
}

// Ordenar artículos del más reciente al más antiguo
usort($articulos, function ($a, $b) {
    $fa = isset($a['fecha']) ? strtotime($a['fecha']) : 0;
    $fb = isset($b['fecha']) ? strtotime($b['fecha']) : 0;
    if ($fa === $fb) {
        return strcmp((string)($b['id'] ?? ''), (string)($a['id'] ?? ''));
    }
    return $fb - $fa;
});

// Filtrado por categoría o búsqueda
$categoria_actual = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';
$busqueda = isset($_GET['q']) ? trim($_GET['q']) : '';

$articulos_filtrados = array_filter($articulos, function ($art) use ($categoria_actual, $busqueda) {
    if ($categoria_actual !== '' && strcasecmp($art['categoria'] ?? '', $categoria_actual) !== 0) {
        return false;
    }
    if ($busqueda !== '') {
        $texto = mb_strtolower(($art['titulo'] ?? '') . ' ' . ($art['resumen'] ?? '') . ' ' . ($art['contenido'] ?? '') . ' ' . ($art['autor'] ?? ''), 'UTF-8');
        if (mb_strpos($texto, mb_strtolower($busqueda, 'UTF-8')) === false) {
            return false;
        }
    }
    return true;
});

// Extraer categorías únicas
$categorias = [];
foreach ($articulos as $art) {
    $cat = trim($art['categoria'] ?? '');
    if ($cat !== '' && !in_array($cat, $categorias, true)) {
        $categorias[] = $cat;
    }
}
sort($categorias);

// Construir URL base absoluta con HTTPS para Open Graph
$protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https://' : 'https://';
$host = $_SERVER['HTTP_HOST'] ?? 'amazonia.alwaysdata.net';
$ruta_base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$url_base = $protocolo . $host . ($ruta_base !== '' ? $ruta_base : '');

function obtenerUrlImagenAbsoluta($img, $url_base) {
    $img = trim((string)$img);
    if ($img === '') {
        return $url_base . '/images/logo_amazonia.png';
    }
    if (preg_match('/^https?:\/\//i', $img)) {
        return $img;
    }
    return $url_base . '/' . ltrim($img, '/');
}

function formatearFechaEspanol($fecha_str) {
    if (!$fecha_str) return '';
    $ts = strtotime($fecha_str);
    if (!$ts) return htmlspecialchars($fecha_str);
    $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    $dia = date('d', $ts);
    $mes = $meses[(int)date('n', $ts) - 1];
    $anio = date('Y', $ts);
    return "$dia $mes $anio";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Portal Amazonía - Saberes, Cultura y Biodiversidad</title>
    <meta name="description" content="Portal informativo y cultural de la Amazonía boliviana. Noticias, investigaciones, pueblos indígenas y biodiversidad.">

    <!-- Open Graph / WhatsApp / Facebook en una sola tarjeta -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Portal Amazonía">
    <meta property="og:title" content="Portal Amazonía - Saberes, Cultura y Biodiversidad">
    <meta property="og:description" content="Portal informativo y cultural de la Amazonía boliviana. Noticias, investigaciones, pueblos indígenas y biodiversidad.">
    <meta property="og:url" content="<?php echo htmlspecialchars($url_base . '/index.php'); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($url_base . '/Mapa_indigena.jpg'); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">

    <style>
        :root {
            --verde-oscuro: #0b2e1d;
            --verde-selva: #145334;
            --verde-vivo: #1f7a4c;
            --dorado-sol: #e6a817;
            --crema-fondo: #f4f6f0;
            --blanco: #ffffff;
            --texto-principal: #1e2923;
            --texto-secundario: #4f6257;
            --borde-suave: #dce3d8;
            --sombra-tarjeta: 0 6px 18px rgba(11, 46, 29, 0.08);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--crema-fondo);
            color: var(--texto-principal);
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        /* Cabecera Superior */
        .top-header {
            background: linear-gradient(135deg, var(--verde-oscuro) 0%, var(--verde-selva) 100%);
            color: var(--blanco);
            padding: 16px 20px;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.18);
        }

        .header-inner {
            max-width: 1280px;
            margin: 0 auto;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .brand-box {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--blanco);
        }

        .brand-icon {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: var(--dorado-sol);
            color: var(--verde-oscuro);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.3rem;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.25);
        }

        .brand-titles h1 {
            font-size: 1.35rem;
            font-weight: 800;
            letter-spacing: 0.3px;
            line-height: 1.15;
        }

        .brand-titles span {
            font-size: 0.78rem;
            color: #c9e2d3;
            display: block;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-mapa {
            background-color: var(--dorado-sol);
            color: var(--verde-oscuro);
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.88rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: transform 0.15s ease, background-color 0.15s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .btn-mapa:hover {
            background-color: #f4b623;
            transform: translateY(-1px);
        }

        .btn-admin {
            background-color: rgba(255, 255, 255, 0.14);
            color: var(--blanco);
            text-decoration: none;
            padding: 8px 13px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 600;
            transition: background-color 0.15s ease;
        }

        .btn-admin:hover {
            background-color: rgba(255, 255, 255, 0.25);
        }

        /* Barra de búsqueda y filtros */
        .filters-bar {
            max-width: 1280px;
            margin: 18px auto 10px;
            padding: 0 16px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            justify-content: space-between;
        }

        .categories-scroll {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 4px;
            scrollbar-width: thin;
            max-width: 100%;
        }

        .cat-chip {
            text-decoration: none;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 0.84rem;
            font-weight: 600;
            white-space: nowrap;
            background-color: var(--blanco);
            color: var(--verde-selva);
            border: 1px solid var(--borde-suave);
            transition: all 0.15s ease;
        }

        .cat-chip.active, .cat-chip:hover {
            background-color: var(--verde-selva);
            color: var(--blanco);
            border-color: var(--verde-selva);
        }

        .search-form {
            display: flex;
            gap: 6px;
            flex: 1 1 240px;
            max-width: 360px;
        }

        .search-form input {
            width: 100%;
            padding: 8px 14px;
            border-radius: 999px;
            border: 1px solid var(--borde-suave);
            font-size: 0.88rem;
            outline: none;
        }

        .search-form button {
            background-color: var(--verde-selva);
            color: var(--blanco);
            border: none;
            padding: 8px 15px;
            border-radius: 999px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.85rem;
        }

        /* Contenedor Principal Masonry / Grid Responsivo */
        .main-container {
            max-width: 1280px;
            margin: 12px auto 40px;
            padding: 0 16px;
        }

        .masonry-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(310px, 1fr));
            grid-auto-rows: 8px;
            gap: 18px;
            align-items: start;
        }

        /* En móviles compactos (ej. iPhone SE 320px-480px) usamos flujo natural de 1 columna sin bucle Masonry */
        @media (max-width: 640px) {
            .masonry-grid {
                display: flex;
                flex-direction: column;
                gap: 16px;
            }
            .top-header {
                padding: 12px 14px;
            }
            .brand-titles h1 {
                font-size: 1.15rem;
            }
            .main-container {
                padding: 0 12px;
            }
        }

        .news-card {
            background-color: var(--blanco);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: var(--sombra-tarjeta);
            border: 1px solid var(--borde-suave);
            display: flex;
            flex-direction: column;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .news-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(11, 46, 29, 0.13);
        }

        .card-image-link {
            display: block;
            position: relative;
            width: 100%;
            background-color: #e9efe6;
            overflow: hidden;
        }

        .card-image-link img {
            width: 100%;
            height: auto;
            max-height: 360px;
            object-fit: cover;
            display: block;
            transition: transform 0.3s ease;
        }

        .news-card:hover .card-image-link img {
            transform: scale(1.02);
        }

        .badge-categoria {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: rgba(11, 46, 29, 0.88);
            color: var(--dorado-sol);
            padding: 4px 11px;
            border-radius: 999px;
            font-size: 0.74rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .card-body {
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .card-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.78rem;
            color: var(--texto-secundario);
            gap: 8px;
            flex-wrap: wrap;
        }

        .card-title {
            font-size: 1.15rem;
            font-weight: 800;
            line-height: 1.3;
            color: var(--verde-oscuro);
            text-decoration: none;
        }

        .card-title:hover {
            color: var(--verde-vivo);
        }

        /* Texto del resumen justificado con espaciado limpio */
        .card-summary {
            font-size: 0.93rem;
            color: var(--texto-principal);
            text-align: justify;
            text-justify: inter-word;
            hyphens: auto;
            -webkit-hyphens: auto;
            line-height: 1.55;
            word-spacing: normal;
        }

        .card-footer {
            padding: 12px 16px;
            border-top: 1px solid var(--borde-suave);
            background-color: #fafbf9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
        }

        /* Botones de reacción instantánea (0ms) */
        .reactions-bar {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .react-btn {
            background: var(--blanco);
            border: 1px solid var(--borde-suave);
            border-radius: 999px;
            padding: 5px 10px;
            font-size: 0.82rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            color: var(--texto-principal);
            font-weight: 600;
            transition: transform 0.1s ease, background-color 0.15s ease, border-color 0.15s ease;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }

        .react-btn:active {
            transform: scale(1.18);
        }

        .react-btn.voted {
            background-color: #e6f4ec;
            border-color: var(--verde-vivo);
            color: var(--verde-oscuro);
        }

        .card-actions-right {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: auto;
        }

        .btn-share-card {
            background-color: #e8f0ec;
            color: var(--verde-oscuro);
            border: 1px solid #c8dbd0;
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: background-color 0.15s ease;
        }

        .btn-share-card:hover {
            background-color: #d3e5dc;
        }

        .btn-leer-mas {
            background-color: var(--verde-selva);
            color: var(--blanco);
            text-decoration: none;
            padding: 6px 13px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 700;
            transition: background-color 0.15s ease;
        }

        .btn-leer-mas:hover {
            background-color: var(--verde-vivo);
        }

        .empty-state {
            background: var(--blanco);
            padding: 40px 20px;
            border-radius: 14px;
            text-align: center;
            color: var(--texto-secundario);
            border: 1px solid var(--borde-suave);
        }

        /* Toast de notificación al copiar enlace */
        .toast-notif {
            position: fixed;
            bottom: 22px;
            left: 50%;
            transform: translateX(-50%) translateY(80px);
            background-color: var(--verde-oscuro);
            color: var(--blanco);
            padding: 10px 20px;
            border-radius: 999px;
            font-size: 0.88rem;
            font-weight: 600;
            box-shadow: 0 6px 18px rgba(0,0,0,0.28);
            transition: transform 0.25s ease, opacity 0.25s ease;
            opacity: 0;
            z-index: 9999;
            pointer-events: none;
        }

        .toast-notif.visible {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }

        footer {
            background-color: var(--verde-oscuro);
            color: #c9e2d3;
            text-align: center;
            padding: 24px 16px;
            font-size: 0.85rem;
            margin-top: 40px;
        }
    </style>
</head>
<body>

<header class="top-header">
    <div class="header-inner">
        <a href="index.php" class="brand-box">
            <div class="brand-icon">A</div>
            <div class="brand-titles">
                <h1>PORTAL AMAZONÍA</h1>
                <span>Saberes, Pueblos Indígenas y Biodiversidad</span>
            </div>
        </a>
        <nav class="nav-actions">
            <a href="mapa.php" class="btn-mapa">🗺️ Mapa Indígena (36 Naciones)</a>
            <a href="admin.php" class="btn-admin">⚙️ Panel CMS</a>
        </nav>
    </div>
</header>

<section class="filters-bar">
    <div class="categories-scroll">
        <a href="index.php" class="cat-chip <?php echo $categoria_actual === '' ? 'active' : ''; ?>">Todas</a>
        <?php foreach ($categorias as $cat): ?>
            <a href="index.php?categoria=<?php echo urlencode($cat); ?>"
               class="cat-chip <?php echo strcasecmp($categoria_actual, $cat) === 0 ? 'active' : ''; ?>">
                <?php echo htmlspecialchars($cat); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <form class="search-form" method="GET" action="index.php">
        <?php if ($categoria_actual !== ''): ?>
            <input type="hidden" name="categoria" value="<?php echo htmlspecialchars($categoria_actual); ?>">
        <?php endif; ?>
        <input type="text" name="q" placeholder="Buscar artículo o pueblo..." value="<?php echo htmlspecialchars($busqueda); ?>">
        <button type="submit">Buscar</button>
    </form>
</section>

<main class="main-container">
    <?php if (empty($articulos_filtrados)): ?>
        <div class="empty-state">
            <h3>No se encontraron artículos</h3>
            <p style="margin-top: 8px;">Prueba con otra categoría o término de búsqueda.</p>
        </div>
    <?php else: ?>
        <div class="masonry-grid" id="masonryGrid">
            <?php foreach ($articulos_filtrados as $art):
                $id = (string)($art['id'] ?? '');
                $titulo = trim($art['titulo'] ?? 'Sin título');
                $categoria = trim($art['categoria'] ?? 'Amazonía');
                $autor = trim($art['autor'] ?? 'Redacción Amazonía');
                $fecha_fmt = formatearFechaEspanol($art['fecha'] ?? '');
                $imagen_rel = trim($art['imagen'] ?? '');
                $imagen_abs = obtenerUrlImagenAbsoluta($imagen_rel, $url_base);

                // Resumen limpio
                $resumen_raw = trim($art['resumen'] ?? '');
                if ($resumen_raw === '') {
                    $resumen_raw = mb_substr(strip_tags($art['contenido'] ?? ''), 0, 220, 'UTF-8') . '...';
                }

                $url_articulo = $url_base . '/articulo.php?id=' . urlencode($id);

                $reacciones = isset($art['reacciones']) && is_array($art['reacciones']) ? $art['reacciones'] : [];
                $c_like = (int)($reacciones['like'] ?? 0);
                $c_love = (int)($reacciones['love'] ?? 0);
                $c_wow  = (int)($reacciones['wow'] ?? 0);
                $c_sad  = (int)($reacciones['sad'] ?? 0);
            ?>
                <article class="news-card" data-id="<?php echo htmlspecialchars($id); ?>">
                    <?php if ($imagen_rel !== ''): ?>
                        <a href="articulo.php?id=<?php echo urlencode($id); ?>" class="card-image-link">
                            <span class="badge-categoria"><?php echo htmlspecialchars($categoria); ?></span>
                            <img src="<?php echo htmlspecialchars($imagen_rel); ?>"
                                 alt="<?php echo htmlspecialchars($titulo); ?>"
                                 loading="lazy"
                                 onload="ajustarTarjetaMasonry(this.closest('.news-card'))">
                        </a>
                    <?php endif; ?>

                    <div class="card-body">
                        <div class="card-meta">
                            <span>✍️ <?php echo htmlspecialchars($autor); ?></span>
                            <?php if ($fecha_fmt !== ''): ?>
                                <span>📅 <?php echo $fecha_fmt; ?></span>
                            <?php endif; ?>
                        </div>

                        <a href="articulo.php?id=<?php echo urlencode($id); ?>" class="card-title">
                            <?php echo htmlspecialchars($titulo); ?>
                        </a>

                        <p class="card-summary"><?php echo nl2br(htmlspecialchars($resumen_raw)); ?></p>
                    </div>

                    <div class="card-footer">
                        <div class="reactions-bar">
                            <button type="button" class="react-btn" data-id="<?php echo htmlspecialchars($id); ?>" data-tipo="like" onclick="reaccionarInstantaneo(this)">
                                👍 <span class="count"><?php echo $c_like; ?></span>
                            </button>
                            <button type="button" class="react-btn" data-id="<?php echo htmlspecialchars($id); ?>" data-tipo="love" onclick="reaccionarInstantaneo(this)">
                                ❤️ <span class="count"><?php echo $c_love; ?></span>
                            </button>
                            <button type="button" class="react-btn" data-id="<?php echo htmlspecialchars($id); ?>" data-tipo="wow" onclick="reaccionarInstantaneo(this)">
                                😮 <span class="count"><?php echo $c_wow; ?></span>
                            </button>
                            <button type="button" class="react-btn" data-id="<?php echo htmlspecialchars($id); ?>" data-tipo="sad" onclick="reaccionarInstantaneo(this)">
                                😢 <span class="count"><?php echo $c_sad; ?></span>
                            </button>
                        </div>

                        <div class="card-actions-right">
                            <button type="button"
                                    class="btn-share-card"
                                    data-titulo="<?php echo htmlspecialchars($titulo, ENT_QUOTES); ?>"
                                    data-resumen="<?php echo htmlspecialchars(mb_substr($resumen_raw, 0, 150, 'UTF-8'), ENT_QUOTES); ?>"
                                    data-url="<?php echo htmlspecialchars($url_articulo, ENT_QUOTES); ?>"
                                    data-imagen="<?php echo htmlspecialchars($imagen_abs, ENT_QUOTES); ?>"
                                    onclick="compartirConFotoReal(this)">
                                🔗 Compartir
                            </button>
                            <a href="articulo.php?id=<?php echo urlencode($id); ?>" class="btn-leer-mas">Leer →</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<div id="toastNotif" class="toast-notif">Enlace copiado al portapapeles</div>

<footer>
    <p>&copy; <?php echo date('Y'); ?> Portal Amazonía — Cultura, Territorio y Biodiversidad Amazónica</p>
</footer>

<script>
// 1. Motor Masonry protegido contra bucles en pantallas móviles (iPhone SE <= 640px)
function ajustarTarjetaMasonry(card) {
    if (!card || window.innerWidth <= 640) {
        if (card) card.style.gridRowEnd = 'auto';
        return;
    }
    const grid = document.getElementById('masonryGrid');
    if (!grid) return;
    const rowHeight = 8;
    const rowGap = 18;
    const contentHeight = card.getBoundingClientRect().height;
    const rowSpan = Math.ceil((contentHeight + rowGap) / (rowHeight + rowGap));
    card.style.gridRowEnd = 'span ' + rowSpan;
}

function recalcularTodoMasonry() {
    const cards = document.querySelectorAll('.news-card');
    if (window.innerWidth <= 640) {
        cards.forEach(c => c.style.gridRowEnd = 'auto');
        return;
    }
    cards.forEach(c => ajustarTarjetaMasonry(c));
}

window.addEventListener('load', recalcularTodoMasonry);
let resizeTimer = null;
window.addEventListener('resize', function() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(recalcularTodoMasonry, 120);
});

// 2. Reacciones Instantáneas en 0 ms (Optimistic UI + persistencia en localStorage y servidor)
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.react-btn').forEach(btn => {
        const id = btn.getAttribute('data-id');
        const tipo = btn.getAttribute('data-tipo');
        const key = 'reaccion_' + id + '_' + tipo;
        if (localStorage.getItem(key) === '1') {
            btn.classList.add('voted');
        }
    });
});

function reaccionarInstantaneo(btn) {
    const id = btn.getAttribute('data-id');
    const tipo = btn.getAttribute('data-tipo');
    const countSpan = btn.querySelector('.count');
    if (!id || !tipo || !countSpan) return;

    const key = 'reaccion_' + id + '_' + tipo;
    const yaVoto = localStorage.getItem(key) === '1';
    const delta = yaVoto ? -1 : 1;

    // Cambio visual en 0 milisegundos
    let actual = parseInt(countSpan.textContent, 10) || 0;
    let nuevo = Math.max(0, actual + delta);
    countSpan.textContent = nuevo;

    if (yaVoto) {
        localStorage.removeItem(key);
        btn.classList.remove('voted');
    } else {
        localStorage.setItem(key, '1');
        btn.classList.add('voted');
    }

    // Envío asíncrono en segundo plano sin bloquear la interfaz
    const formData = new FormData();
    formData.append('accion', 'reaccionar');
    formData.append('id', id);
    formData.append('tipo', tipo);
    formData.append('delta', delta);

    fetch('index.php', {
        method: 'POST',
        body: formData
    }).catch(() => {});
}

// 3. Compartir en UNA SOLA TARJETA unificada (Open Graph nativo con foto + título + resumen + enlace)
function mostrarToast(mensaje) {
    const toast = document.getElementById('toastNotif');
    if (!toast) return;
    toast.textContent = mensaje;
    toast.classList.add('visible');
    setTimeout(() => toast.classList.remove('visible'), 2600);
}

async function compartirConFotoReal(btn) {
    const titulo = btn.getAttribute('data-titulo') || 'Portal Amazonía';
    const resumen = btn.getAttribute('data-resumen') || '';
    const url = btn.getAttribute('data-url') || window.location.href;

    // Se envía texto y URL sin archivo binario suelto para que WhatsApp arme UNA SOLA TARJETA con la foto Open Graph
    const textoCompartir = `*${titulo}*\n\n${resumen}`;

    if (navigator.share) {
        try {
            await navigator.share({
                title: titulo,
                text: textoCompartir,
                url: url
            });
            return;
        } catch (err) {
            if (err && err.name === 'AbortError') return;
        }
    }

    // Respaldo directo a WhatsApp o portapapeles en escritorio
    const textoCompleto = `*${titulo}*\n\n${resumen}\n\n${url}`;
    if (/Android|iPhone|iPad|iPod/i.test(navigator.userAgent)) {
        window.open('https://api.whatsapp.com/send?text=' + encodeURIComponent(textoCompleto), '_blank');
    } else if (navigator.clipboard) {
        navigator.clipboard.writeText(textoCompleto).then(() => {
            mostrarToast('Artículo y enlace copiados para compartir');
        });
    }
}
</script>
</body>
</html>