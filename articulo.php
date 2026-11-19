<?php
// articulo.php - Inmersión Total con Envío de Fotografía Adjunta Real en Redes Sociales, Reacciones Instantáneas (0ms), Audio, Pin y Comentarios
$id = isset($_GET['id']) ? $_GET['id'] : '';

$archivo_json = __DIR__ . '/articulos.json';
$articulos = [];

if (file_exists($archivo_json)) {
    $contenido = file_get_contents($archivo_json);
    $articulos = json_decode($contenido, true) ?: [];
}

// API AJAX interna para procesar reacciones sin recargar en la inmersión total
if (isset($_GET['ajax_reaccionar']) && isset($_GET['id']) && isset($_GET['tipo'])) {
    header('Content-Type: application/json');
    $tipo = $_GET['tipo']; // 'like' o 'sad'
    $previo = $_GET['previo'] ?? ''; 
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

// Manejo de envío de comentarios locales por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_comentario'])) {
    $autor_comentario = trim($_POST['autor'] ?? 'Anónimo');
    $texto_comentario = trim($_POST['comentario'] ?? '');

    if ($texto_comentario !== '') {
        foreach ($articulos as &$art) {
            if (strval($art['id']) === strval($id)) {
                if (!isset($art['comentarios'])) {
                    $art['comentarios'] = [];
                }
                array_unshift($art['comentarios'], [
                    'autor' => ($autor_comentario !== '' ? $autor_comentario : 'Anónimo'),
                    'fecha' => date('Y-m-d H:i'),
                    'texto' => $texto_comentario
                ]);
                break;
            }
        }
        unset($art);
        file_put_contents($archivo_json, json_encode($articulos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        header("Location: articulo.php?id=" . urlencode($id));
        exit;
    }
}

$articulo = null;
foreach ($articulos as $art) {
    if (strval($art['id']) === strval($id)) {
        $articulo = $art;
        break;
    }
}

if (!$articulo || (isset($articulo['eliminado']) && $articulo['eliminado'] === true)) {
    header("Location: index.php");
    exit;
}

// Datos limpios del artículo
$titulo_raw = trim($articulo['titulo'] ?? 'Artículo');
$resumen_raw = trim($articulo['resumen'] ?? '');
if ($resumen_raw === '') {
    $resumen_raw = mb_substr(strip_tags($articulo['contenido_completo'] ?? ''), 0, 200) . '...';
}

$titulo = htmlspecialchars($titulo_raw, ENT_QUOTES, 'UTF-8');
$resumen_meta = htmlspecialchars(mb_substr($resumen_raw, 0, 220), ENT_QUOTES, 'UTF-8');
$categoria = htmlspecialchars($articulo['categoria'] ?? 'NOTICIA', ENT_QUOTES, 'UTF-8');

// Validar imagen física y obtener sus dimensiones reales para forzar vista previa gigante
$img_path = ltrim(trim($articulo['imagen'] ?? ''), '/');
$ruta_fisica_img = __DIR__ . '/' . $img_path;

if ($img_path !== '' && file_exists($ruta_fisica_img)) {
    $imagen_relativa = $img_path;
} else {
    $imagen_relativa = 'images/default.jpg';
    $ruta_fisica_img = __DIR__ . '/images/default.jpg';
}

$imagen = htmlspecialchars($imagen_relativa, ENT_QUOTES, 'UTF-8');

// Detectar dimensiones y tipo MIME para WhatsApp, Facebook y Telegram
$img_width = 1200;
$img_height = 630;
$img_mime = 'image/jpeg';

if (file_exists($ruta_fisica_img)) {
    $info_img = @getimagesize($ruta_fisica_img);
    if ($info_img !== false) {
        $img_width = max(1200, intval($info_img[0]));
        $img_height = max(630, intval($info_img[1]));
        if (!empty($info_img['mime'])) {
            $img_mime = $info_img['mime'];
        }
    }
}

// Detección estricta de protocolo y host compatible con túneles y servidores de producción
$es_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) 
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

$protocolo = $es_https ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
$base_dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');

$segmentos_img = explode('/', $imagen_relativa);
$segmentos_codificados = array_map('rawurlencode', $segmentos_img);
$ruta_img_codificada = implode('/', $segmentos_codificados);

$url_imagen_absoluta = $protocolo . $host . ($base_dir !== '' ? $base_dir : '') . '/' . $ruta_img_codificada;
$url_actual = $protocolo . $host . ($base_dir !== '' ? $base_dir : '') . '/articulo.php?id=' . rawurlencode($id);

$texto_plano_voz = strip_tags($articulo['contenido_completo'] ?? $articulo['resumen'] ?? '');
$contenido_completo = nl2br(htmlspecialchars($articulo['contenido_completo'] ?? $articulo['resumen'] ?? '', ENT_QUOTES, 'UTF-8'));
$fecha = htmlspecialchars($articulo['fecha'] ?? '', ENT_QUOTES, 'UTF-8');
$fuente = htmlspecialchars($articulo['fuente'] ?? '', ENT_QUOTES, 'UTF-8');
$likes = intval($articulo['likes'] ?? 0);
$sads = intval($articulo['sads'] ?? 0);
$comentarios = $articulo['comentarios'] ?? [];
?>
<!DOCTYPE html>
<html lang="es" prefix="og: https://ogp.me/ns#">
<head>
    <meta charset="UTF-8">
    <title><?php echo $titulo; ?> | Portal Amazonía</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo $resumen_meta; ?>">

    <!-- OPEN GRAPH EXTENDIDO PARA WHATSAPP, FACEBOOK, INSTAGRAM Y TELEGRAM (FOTO GIGANTE) -->
    <meta property="og:site_name" content="Portal Amazonía | Vanguardia y Territorio">
    <meta property="og:locale" content="es_BO">
    <meta property="og:type" content="article">
    <meta property="og:title" content="<?php echo $titulo; ?>">
    <meta property="og:description" content="<?php echo $resumen_meta; ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($url_actual, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($url_imagen_absoluta, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image:secure_url" content="<?php echo htmlspecialchars($url_imagen_absoluta, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image:type" content="<?php echo htmlspecialchars($img_mime, ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image:width" content="<?php echo $img_width; ?>">
    <meta property="og:image:height" content="<?php echo $img_height; ?>">
    <meta property="og:image:alt" content="<?php echo $titulo; ?>">
    <meta property="article:section" content="<?php echo $categoria; ?>">

    <!-- TWITTER / X / TELEGRAM LARGE SUMMARY CARD -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo $titulo; ?>">
    <meta name="twitter:description" content="<?php echo $resumen_meta; ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($url_imagen_absoluta, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:image:alt" content="<?php echo $titulo; ?>">

    <link rel="image_src" href="<?php echo htmlspecialchars($url_imagen_absoluta, ENT_QUOTES, 'UTF-8'); ?>">

    <style>
        :root { --vibrant-green: #1D9A4C; --accent-gold: #FFB81C; --dark-bg: #121212; --panel-bg: #1e1e1e; --text-main: #E0E0E0; }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { font-family: 'Segoe UI', Roboto, sans-serif; background-color: var(--dark-bg); color: var(--text-main); margin: 0; padding: 0; -webkit-text-size-adjust: 100%; }
        a, button { touch-action: manipulation; }
        
        .lectura-container { max-width: 1100px; margin: 20px auto; background: #111111; border: 1px solid #333; border-radius: 8px; box-shadow: 0 10px 40px rgba(0,0,0,0.9); }
        .lectura-header { padding: 15px 25px; border-bottom: 1px solid #222; display: flex; justify-content: space-between; align-items: center; background: #0c0c0c; border-top-left-radius: 8px; border-top-right-radius: 8px; }
        .btn-volver { color: var(--accent-gold); text-decoration: none; font-weight: bold; font-size: 13px; padding: 6px 0; }
        .btn-volver:hover { color: white; }

        .lectura-layout { display: flex; flex-direction: column; position: relative; align-items: flex-start; }
        
        @media (min-width: 900px) {
            .lectura-layout { display: grid; grid-template-columns: 45% 55%; }
            .lectura-hero-container { padding: 30px; background: #111111; width: 100%; box-sizing: border-box; }
            .lectura-hero-container.pinned { position: sticky; top: 20px; align-self: start; }
        }

        .lectura-hero-container { padding: 20px; background: #080808; text-align: center; position: relative; width: 100%; }
        .lectura-hero { width: 100%; max-height: 75vh; object-fit: contain; border-radius: 6px; box-shadow: 0 4px 20px rgba(0,0,0,0.8); background: #000; border: 1px solid rgba(255,255,255,0.1); }
        
        .btn-pin { position: absolute; top: 35px; right: 35px; background: rgba(0,0,0,0.7); color: #aaa; border: 1px solid #555; border-radius: 50%; width: 40px; height: 40px; font-size: 16px; cursor: pointer; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px); transition: all 0.2s; z-index: 10; }
        .btn-pin:hover { background: rgba(0,0,0,0.9); color: white; }
        .btn-pin.activo { background: var(--accent-gold); color: black; border-color: var(--accent-gold); }

        .lectura-body { padding: 25px; width: 100%; box-sizing: border-box; }
        .lectura-body h1 { color: white; font-size: clamp(22px, 3vw, 34px); font-weight: 900; line-height: 1.25; margin-top: 0; margin-bottom: 15px; }
        .lectura-meta { font-size: 12px; color: var(--accent-gold); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; display: block; }
        
        .reproductor-box { display: flex; align-items: center; gap: 12px; background: #1a1a1a; border: 1px solid #333; padding: 10px 16px; border-radius: 6px; margin-bottom: 25px; }
        .btn-play { background: var(--vibrant-green); color: white; border: none; border-radius: 50%; width: 38px; height: 38px; display: flex; align-items: center; justify-content: center; font-size: 16px; cursor: pointer; transition: 0.15s; flex-shrink: 0; }
        .btn-play:hover { background: var(--accent-gold); color: black; }
        .reproductor-info { font-size: 13px; color: #aaa; font-weight: bold; }

        .lectura-texto { font-size: 17px; line-height: 1.8; color: #ddd; text-align: justify; hyphens: auto; -webkit-hyphens: auto; }

        .acciones-superiores { display: flex; justify-content: flex-start; align-items: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #222; flex-wrap: wrap; gap: 8px; }
        .btn-compartir { background: var(--accent-gold); color: black; border: none; padding: 10px 16px; border-radius: 6px; font-weight: 900; cursor: pointer; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; transition: transform 0.1s, background 0.15s; }
        .btn-compartir:active { transform: scale(0.97); }
        .btn-compartir:hover { background: white; }

        .btn-red-social { background: #222; color: white; border: 1px solid #444; padding: 10px 14px; border-radius: 6px; font-weight: bold; font-size: 12px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; transition: 0.15s; }
        .btn-red-social.fb:hover { background: #1877F2; border-color: #1877F2; }
        .btn-red-social.x:hover { background: #000; border-color: #000; }
        .btn-red-social.tiktok:hover { background: #FE2C55; border-color: #FE2C55; }
        
        .reacciones-footer { display: flex; gap: 10px; width: 100%; margin-top: 15px; }
        .btn-reaccion { background: #1e1e1e; border: 1px solid #444; color: #ccc; border-radius: 6px; padding: 10px 15px; font-size: 14px; font-weight: bold; cursor: pointer; display: flex; align-items: center; gap: 8px; flex-grow: 1; justify-content: center; transition: transform 0.08s, background 0.12s; user-select: none; }
        .btn-reaccion:active { transform: scale(0.94); }
        .btn-reaccion:hover { border-color: var(--accent-gold); color: white; background: rgba(255,184,28,0.1); }
        .btn-reaccion.seleccionado { background: var(--accent-gold); color: black; border-color: var(--accent-gold); }

        .seccion-comentarios { margin-top: 30px; border-top: 1px solid #333; padding: 25px; grid-column: 1 / -1; }
        .seccion-comentarios h3 { color: var(--accent-gold); font-size: 20px; margin-bottom: 20px; }
        .form-comentario { display: flex; flex-direction: column; gap: 12px; margin-bottom: 30px; }
        .input-comentario { background: var(--panel-bg); border: 1px solid #444; color: white; padding: 12px; border-radius: 6px; font-family: inherit; font-size: 14px; box-sizing: border-box; width: 100%; }
        .input-comentario:focus { outline: none; border-color: var(--vibrant-green); }
        .btn-enviar { align-self: flex-end; background: var(--vibrant-green); color: white; border: none; padding: 10px 25px; font-weight: bold; border-radius: 6px; cursor: pointer; }
        .btn-enviar:hover { background: #147037; }
        .comentario-item { background: #1a1a1a; padding: 15px; border-radius: 6px; margin-bottom: 15px; border-left: 3px solid var(--accent-gold); }
        .comentario-autor { font-weight: bold; color: white; font-size: 14px; margin-bottom: 4px; }
        .comentario-fecha { font-size: 11px; color: #888; margin-bottom: 8px; }
        .comentario-texto { color: #ccc; font-size: 15px; margin: 0; line-height: 1.5; text-align: justify; }

        /* NOTIFICACIÓN FLOTANTE INSTANTÁNEA (TOAST) */
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

<div class="lectura-container">
    <div class="lectura-header">
        <a href="index.php" class="btn-volver">← Volver al Mosaico</a>
        <span style="font-size:11px; color:#888; text-transform:uppercase;">Inmersión Total</span>
    </div>

    <div class="lectura-layout">
        <!-- FOTOGRAFÍA CON PIN -->
        <div class="lectura-hero-container" id="heroContainer">
            <button class="btn-pin" id="btnPin" onclick="togglePin()" title="Clavar o desclavar foto">📌</button>
            <img src="<?php echo $imagen; ?>" id="heroImagenArticulo" class="lectura-hero" alt="<?php echo $titulo; ?>" decoding="async" onerror="this.onerror=null; this.src='images/default.jpg';">
        </div>

        <!-- CONTENIDO Y AUDIO -->
        <div class="lectura-body">
            <span class="lectura-meta"><?php echo $fecha; ?><?php echo $fuente ? " | Fuente: $fuente" : ""; ?></span>
            <h1><?php echo $titulo; ?></h1>
            
            <div class="reproductor-box">
                <button class="btn-play" id="btnPlay" onclick="toggleAudio()">▶</button>
                <div class="reproductor-info" id="infoAudio">Escuchar artículo completo (Audio)</div>
            </div>

            <div class="lectura-texto">
                <?php echo $contenido_completo; ?>
            </div>

            <div class="acciones-superiores">
                <button class="btn-compartir" onclick="compartirPaginaConFoto()">🔗 Compartir</button>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($url_actual); ?>" target="_blank" rel="noopener" class="btn-red-social fb">Facebook</a>
                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($url_actual); ?>&text=<?php echo urlencode($titulo_raw); ?>" target="_blank" rel="noopener" class="btn-red-social x">X</a>
                <a href="https://www.tiktok.com/" target="_blank" rel="noopener" class="btn-red-social tiktok" title="Abrir TikTok">TikTok</a>
            </div>

            <!-- REACCIONES INSTANTÁNEAS (0ms) -->
            <div class="reacciones-footer">
                <button class="btn-reaccion" id="btn-like-<?php echo $id; ?>" onclick="reaccionar('<?php echo $id; ?>', 'like')">👍 <span id="likes-<?php echo $id; ?>"><?php echo $likes; ?></span></button>
                <button class="btn-reaccion" id="btn-sad-<?php echo $id; ?>" onclick="reaccionar('<?php echo $id; ?>', 'sad')">😢 <span id="sads-<?php echo $id; ?>"><?php echo $sads; ?></span></button>
            </div>
        </div>
    </div>

    <div class="seccion-comentarios">
        <h3>Comentarios de la Comunidad</h3>
        
        <form method="POST" class="form-comentario">
            <input type="hidden" name="nuevo_comentario" value="1">
            <input type="text" name="autor" class="input-comentario" placeholder="Tu Nombre o Seudónimo" required>
            <textarea name="comentario" class="input-comentario" rows="3" placeholder="Escribe tu comentario u opinión..." required></textarea>
            <button type="submit" class="btn-enviar">Publicar Comentario</button>
        </form>

        <?php if (empty($comentarios)): ?>
            <p style="color: #777; font-style: italic;">Sé el primero en comentar este artículo.</p>
        <?php else: ?>
            <?php foreach ($comentarios as $com): ?>
                <div class="comentario-item">
                    <div class="comentario-autor"><?php echo htmlspecialchars($com['autor']); ?></div>
                    <div class="comentario-fecha"><?php echo htmlspecialchars($com['fecha']); ?></div>
                    <p class="comentario-texto"><?php echo nl2br(htmlspecialchars($com['texto'])); ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div id="toast-notificacion"></div>

<script>
    const datosCompartir = {
        titulo: <?php echo json_encode($titulo_raw, JSON_UNESCAPED_UNICODE); ?>,
        resumen: <?php echo json_encode(mb_substr($resumen_raw, 0, 220), JSON_UNESCAPED_UNICODE); ?>,
        url: <?php echo json_encode($url_actual, JSON_UNESCAPED_UNICODE); ?>
    };

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

    document.addEventListener("DOMContentLoaded", function() {
        let idArt = <?php echo json_encode(strval($id)); ?>;
        let estado = localStorage.getItem(`reaccion_${idArt}`);
        if (estado) {
            let btn = document.getElementById(`btn-${estado}-${idArt}`);
            if (btn) btn.classList.add('seleccionado');
        }
    });

    function togglePin() {
        const container = document.getElementById('heroContainer');
        const btn = document.getElementById('btnPin');
        container.classList.toggle('pinned');
        btn.classList.toggle('activo');
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

        fetch(`articulo.php?id=${encodeURIComponent(id)}&ajax_reaccionar=1&tipo=${encodeURIComponent(tipo)}&previo=${encodeURIComponent(previo)}`, {
            cache: 'no-store'
        }).catch(() => {});
    }

    const textoParaAudio = <?php echo json_encode(strip_tags($titulo_raw . ". " . $texto_plano_voz), JSON_UNESCAPED_UNICODE); ?>;
    let synth = window.speechSynthesis;
    let utterance = null;
    let reproduciendo = false;

    function toggleAudio() {
        const btnPlay = document.getElementById('btnPlay');
        const infoAudio = document.getElementById('infoAudio');

        if (!synth) {
            mostrarToast("Tu navegador no soporta reproducción de voz.");
            return;
        }

        if (reproduciendo) {
            synth.cancel();
            reproduciendo = false;
            btnPlay.textContent = "▶";
            infoAudio.textContent = "Escuchar artículo completo (Audio)";
        } else {
            utterance = new SpeechSynthesisUtterance(textoParaAudio);
            utterance.lang = 'es-ES';
            utterance.rate = 1.0;

            utterance.onend = function() {
                reproduciendo = false;
                btnPlay.textContent = "▶";
                infoAudio.textContent = "Escuchar artículo completo (Audio)";
            };

            synth.speak(utterance);
            reproduciendo = true;
            btnPlay.textContent = "❚❚";
            infoAudio.textContent = "Reproduciendo audio...";
        }
    }

    // Convierte la foto ya cargada en pantalla a un archivo JPG listo para adjuntar en 0ms
    function obtenerArchivoDesdeHero() {
        return new Promise((resolve) => {
            try {
                const imgElement = document.getElementById('heroImagenArticulo');
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

    // COMPARTIR ADJUNTANDO LA FOTOGRAFÍA FÍSICA + TÍTULO + RESUMEN + ENLACE
    async function compartirPaginaConFoto() {
        const textoFormateado = `*${datosCompartir.titulo}*\n\n${datosCompartir.resumen}\n\n${datosCompartir.url}`;

        if (navigator.share) {
            try {
                const archivoFoto = await obtenerArchivoDesdeHero();
                if (archivoFoto && navigator.canShare && navigator.canShare({ files: [archivoFoto] })) {
                    await navigator.share({
                        files: [archivoFoto],
                        title: datosCompartir.titulo,
                        text: textoFormateado
                    });
                    return;
                }
                await navigator.share({
                    title: datosCompartir.titulo,
                    text: `${datosCompartir.titulo} — ${datosCompartir.resumen}`,
                    url: datosCompartir.url
                });
                return;
            } catch (err) {
                if (err.name === 'AbortError') return;
            }
        }

        if (navigator.clipboard) {
            navigator.clipboard.writeText(textoFormateado).then(function() {
                mostrarToast("📋 ¡Copiado con título, resumen y enlace!");
            });
        }
    }
</script>

</body>
</html>