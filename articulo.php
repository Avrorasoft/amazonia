<?php
// articulo.php - Vista completa del artículo + Open Graph en Una Sola Tarjeta + Audio TTS + Reacciones 0ms + Comentarios
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

$id_actual = isset($_GET['id']) ? trim($_GET['id']) : (isset($_POST['id']) ? trim($_POST['id']) : '');

// Endpoint asíncrono para reacciones en 0ms desde articulo.php
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

// Guardar nuevo comentario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'comentar') {
    $autor_com = trim($_POST['nombre'] ?? 'Lector Amazónico');
    $texto_com = trim($_POST['comentario'] ?? '');
    if ($autor_com === '') $autor_com = 'Lector Amazónico';

    if ($id_actual !== '' && $texto_com !== '') {
        foreach ($articulos as &$art) {
            if ((string)$art['id'] === (string)$id_actual) {
                if (!isset($art['comentarios']) || !is_array($art['comentarios'])) {
                    $art['comentarios'] = [];
                }
                $art['comentarios'][] = [
                    'nombre' => mb_substr($autor_com, 0, 60, 'UTF-8'),
                    'texto'  => mb_substr($texto_com, 0, 1000, 'UTF-8'),
                    'fecha'  => date('Y-m-d H:i')
                ];
                file_put_contents($archivo_json, json_encode($articulos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
                break;
            }
        }
        header('Location: articulo.php?id=' . urlencode($id_actual) . '#comentarios');
        exit;
    }
}

// Localizar artículo solicitado
$articulo = null;
foreach ($articulos as $art) {
    if ((string)($art['id'] ?? '') === (string)$id_actual) {
        $articulo = $art;
        break;
    }
}

if (!$articulo && !empty($articulos)) {
    $articulo = $articulos[0];
    $id_actual = (string)$articulo['id'];
}

// Construir URL base con HTTPS para Open Graph
$host = $_SERVER['HTTP_HOST'] ?? 'amazonia.alwaysdata.net';
$ruta_base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$url_base = 'https://' . $host . ($ruta_base !== '' ? $ruta_base : '');

function obtenerUrlImagenAbsolutaArt($img, $url_base) {
    $img = trim((string)$img);
    if ($img === '') {
        return $url_base . '/Mapa_indigena.jpg';
    }
    if (preg_match('/^https?:\/\//i', $img)) {
        return $img;
    }
    return $url_base . '/' . ltrim($img, '/');
}

function formatearFechaArt($fecha_str) {
    if (!$fecha_str) return '';
    $ts = strtotime($fecha_str);
    if (!$ts) return htmlspecialchars($fecha_str);
    $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    return date('d', $ts) . ' de ' . $meses[(int)date('n', $ts) - 1] . ' de ' . date('Y', $ts);
}

$titulo = $articulo ? trim($articulo['titulo'] ?? 'Artículo Portal Amazonía') : 'Artículo no encontrado';
$categoria = $articulo ? trim($articulo['categoria'] ?? 'Amazonía') : 'Amazonía';
$autor = $articulo ? trim($articulo['autor'] ?? 'Redacción Amazonía') : '';
$fecha_fmt = $articulo ? formatearFechaArt($articulo['fecha'] ?? '') : '';
$contenido_raw = $articulo ? trim($articulo['contenido'] ?? '') : '';
$resumen_og = $articulo ? trim($articulo['resumen'] ?? '') : '';
if ($resumen_og === '') {
    $resumen_og = mb_substr( trim(preg_replace('/\s+/', ' ', strip_tags($contenido_raw))), 0, 180, 'UTF-8' );
}
$imagen_rel = $articulo ? trim($articulo['imagen'] ?? '') : '';
$imagen_abs = obtenerUrlImagenAbsolutaArt($imagen_rel, $url_base);
$url_canonica = $url_base . '/articulo.php?id=' . urlencode($id_actual);

$reacciones = ($articulo && isset($articulo['reacciones']) && is_array($articulo['reacciones'])) ? $articulo['reacciones'] : [];
$c_like = (int)($reacciones['like'] ?? 0);
$c_love = (int)($reacciones['love'] ?? 0);
$c_wow  = (int)($reacciones['wow'] ?? 0);
$c_sad  = (int)($reacciones['sad'] ?? 0);

$comentarios = ($articulo && isset($articulo['comentarios']) && is_array($articulo['comentarios'])) ? $articulo['comentarios'] : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title><?php echo htmlspecialchars($titulo); ?> — Portal Amazonía</title>
    <meta name="description" content="<?php echo htmlspecialchars($resumen_og); ?>">

    <!-- Etiquetas Open Graph para que WhatsApp, Facebook y Telegram armen UNA SOLA TARJETA con Foto + Texto -->
    <meta property="og:type" content="article">
    <meta property="og:site_name" content="Portal Amazonía">
    <meta property="og:title" content="<?php echo htmlspecialchars($titulo); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($resumen_og); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($url_canonica); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($imagen_abs); ?>">
    <meta property="og:image:secure_url" content="<?php echo htmlspecialchars($imagen_abs); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($titulo); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($resumen_og); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($imagen_abs); ?>">

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
            line-height: 1.65;
            -webkit-font-smoothing: antialiased;
        }

        .top-header {
            background: linear-gradient(135deg, var(--verde-oscuro) 0%, var(--verde-selva) 100%);
            color: var(--blanco);
            padding: 14px 20px;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.16);
        }

        .header-inner {
            max-width: 960px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-volver {
            color: var(--blanco);
            text-decoration: none;
            font-weight: 700;
            font-size: 0.92rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background-color: rgba(255,255,255,0.14);
            padding: 7px 14px;
            border-radius: 999px;
        }

        .btn-volver:hover {
            background-color: rgba(255,255,255,0.24);
        }

        .btn-mapa {
            background-color: var(--dorado-sol);
            color: var(--verde-oscuro);
            text-decoration: none;
            padding: 7px 14px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .article-wrapper {
            max-width: 860px;
            margin: 24px auto 50px;
            padding: 0 16px;
        }

        .article-card {
            background-color: var(--blanco);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(11, 46, 29, 0.09);
            border: 1px solid var(--borde-suave);
        }

        .article-hero img {
            width: 100%;
            max-height: 480px;
            object-fit: cover;
            display: block;
        }

        .article-content-box {
            padding: 28px 26px;
        }

        @media (max-width: 600px) {
            .article-content-box {
                padding: 20px 16px;
            }
        }

        .article-badge {
            display: inline-block;
            background-color: var(--verde-selva);
            color: var(--dorado-sol);
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .article-title {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--verde-oscuro);
            line-height: 1.25;
            margin-bottom: 12px;
        }

        @media (max-width: 600px) {
            .article-title {
                font-size: 1.38rem;
            }
        }

        .article-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            font-size: 0.85rem;
            color: var(--texto-secundario);
            padding-bottom: 16px;
            border-bottom: 1px solid var(--borde-suave);
            margin-bottom: 18px;
        }

        /* Barra de herramientas: Lector de Voz + Compartir en una sola tarjeta */
        .toolbar-article {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            justify-content: space-between;
            background-color: #f3f7f4;
            padding: 12px 14px;
            border-radius: 12px;
            margin-bottom: 22px;
            border: 1px solid var(--borde-suave);
        }

        .tts-controls {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-tts {
            background-color: var(--verde-selva);
            color: var(--blanco);
            border: none;
            padding: 8px 14px;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-tts.stop {
            background-color: #9e2a2b;
            display: none;
        }

        .btn-share-main {
            background-color: var(--dorado-sol);
            color: var(--verde-oscuro);
            border: none;
            padding: 8px 16px;
            border-radius: 999px;
            font-size: 0.86rem;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        /* Cuerpo del artículo justificado */
        .article-body-text {
            font-size: 1.04rem;
            color: var(--texto-principal);
            text-align: justify;
            text-justify: inter-word;
            hyphens: auto;
            -webkit-hyphens: auto;
            line-height: 1.75;
        }

        .article-body-text p {
            margin-bottom: 16px;
        }

        /* Reacciones 0ms */
        .reactions-section {
            margin-top: 26px;
            padding-top: 18px;
            border-top: 1px solid var(--borde-suave);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .reactions-bar {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .react-btn {
            background: var(--blanco);
            border: 1px solid var(--borde-suave);
            border-radius: 999px;
            padding: 7px 13px;
            font-size: 0.9rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-weight: 700;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }

        .react-btn.voted {
            background-color: #e6f4ec;
            border-color: var(--verde-vivo);
            color: var(--verde-oscuro);
        }

        /* Sección de comentarios */
        .comments-box {
            margin-top: 28px;
            background-color: var(--blanco);
            border-radius: 16px;
            padding: 24px;
            border: 1px solid var(--borde-suave);
        }

        .comments-box h3 {
            color: var(--verde-oscuro);
            margin-bottom: 14px;
            font-size: 1.2rem;
        }

        .comment-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 22px;
        }

        .comment-form input, .comment-form textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--borde-suave);
            border-radius: 10px;
            font-family: inherit;
            font-size: 0.92rem;
        }

        .comment-form button {
            align-self: flex-start;
            background-color: var(--verde-selva);
            color: var(--blanco);
            border: none;
            padding: 9px 18px;
            border-radius: 999px;
            font-weight: 700;
            cursor: pointer;
        }

        .comment-item {
            padding: 12px 0;
            border-bottom: 1px solid var(--borde-suave);
        }

        .comment-item:last-child {
            border-bottom: none;
        }

        .comment-head {
            font-size: 0.84rem;
            color: var(--texto-secundario);
            margin-bottom: 4px;
        }

        .comment-head strong {
            color: var(--verde-oscuro);
        }
    </style>
</head>
<body>

<header class="top-header">
    <div class="header-inner">
        <a href="index.php" class="btn-volver">← Volver a Portada</a>
        <a href="mapa.php" class="btn-mapa">🗺️ Mapa Indígena</a>
    </div>
</header>

<main class="article-wrapper">
    <?php if (!$articulo): ?>
        <div class="article-card" style="padding: 36px; text-align: center;">
            <h2>Artículo no encontrado</h2>
            <p style="margin-top: 10px;"><a href="index.php">Regresar al inicio</a></p>
        </div>
    <?php else: ?>
        <article class="article-card">
            <?php if ($imagen_rel !== ''): ?>
                <div class="article-hero">
                    <img src="<?php echo htmlspecialchars($imagen_rel); ?>" alt="<?php echo htmlspecialchars($titulo); ?>">
                </div>
            <?php endif; ?>

            <div class="article-content-box">
                <span class="article-badge"><?php echo htmlspecialchars($categoria); ?></span>
                <h1 class="article-title" id="tituloArticulo"><?php echo htmlspecialchars($titulo); ?></h1>

                <div class="article-meta">
                    <span>✍️ Por <strong><?php echo htmlspecialchars($autor); ?></strong></span>
                    <?php if ($fecha_fmt !== ''): ?>
                        <span>📅 <?php echo $fecha_fmt; ?></span>
                    <?php endif; ?>
                </div>

                <div class="toolbar-article">
                    <div class="tts-controls">
                        <button type="button" class="btn-tts" id="btnPlayTTS" onclick="alternarLecturaVoz()">
                            ▶ Escuchar artículo
                        </button>
                        <button type="button" class="btn-tts stop" id="btnStopTTS" onclick="detenerLecturaVoz()">
                            ⏹ Detener
                        </button>
                    </div>

                    <button type="button"
                            class="btn-share-main"
                            data-titulo="<?php echo htmlspecialchars($titulo, ENT_QUOTES); ?>"
                            data-resumen="<?php echo htmlspecialchars($resumen_og, ENT_QUOTES); ?>"
                            data-url="<?php echo htmlspecialchars($url_canonica, ENT_QUOTES); ?>"
                            onclick="compartirConFotoReal(this)">
                        🔗 Compartir artículo
                    </button>
                </div>

                <div class="article-body-text" id="cuerpoArticulo">
                    <?php
                    $parrafos = preg_split('/\n\s*\n/', $contenido_raw);
                    foreach ($parrafos as $p) {
                        $p = trim($p);
                        if ($p !== '') {
                            echo '<p>' . nl2br(htmlspecialchars($p)) . '</p>';
                        }
                    }
                    ?>
                </div>

                <div class="reactions-section">
                    <div class="reactions-bar">
                        <button type="button" class="react-btn" data-id="<?php echo htmlspecialchars($id_actual); ?>" data-tipo="like" onclick="reaccionarInstantaneo(this)">
                            👍 <span class="count"><?php echo $c_like; ?></span>
                        </button>
                        <button type="button" class="react-btn" data-id="<?php echo htmlspecialchars($id_actual); ?>" data-tipo="love" onclick="reaccionarInstantaneo(this)">
                            ❤️ <span class="count"><?php echo $c_love; ?></span>
                        </button>
                        <button type="button" class="react-btn" data-id="<?php echo htmlspecialchars($id_actual); ?>" data-tipo="wow" onclick="reaccionarInstantaneo(this)">
                            😮 <span class="count"><?php echo $c_wow; ?></span>
                        </button>
                        <button type="button" class="react-btn" data-id="<?php echo htmlspecialchars($id_actual); ?>" data-tipo="sad" onclick="reaccionarInstantaneo(this)">
                            😢 <span class="count"><?php echo $c_sad; ?></span>
                        </button>
                    </div>
                </div>
            </div>
        </article>

        <section class="comments-box" id="comentarios">
            <h3>💬 Comentarios (<?php echo count($comentarios); ?>)</h3>
            <form class="comment-form" method="POST" action="articulo.php?id=<?php echo urlencode($id_actual); ?>">
                <input type="hidden" name="accion" value="comentar">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($id_actual); ?>">
                <input type="text" name="nombre" placeholder="Tu nombre (opcional)" maxlength="60">
                <textarea name="comentario" rows="3" placeholder="Escribe tu opinión o aporte sobre este tema..." required></textarea>
                <button type="submit">Publicar comentario</button>
            </form>

            <?php if (empty($comentarios)): ?>
                <p style="color: var(--texto-secundario); font-size: 0.9rem;">Sé el primero en comentar este artículo.</p>
            <?php else: ?>
                <?php foreach (array_reverse($comentarios) as $com): ?>
                    <div class="comment-item">
                        <div class="comment-head">
                            <strong><?php echo htmlspecialchars($com['nombre'] ?? 'Lector'); ?></strong>
                            • <?php echo htmlspecialchars($com['fecha'] ?? ''); ?>
                        </div>
                        <div><?php echo nl2br(htmlspecialchars($com['texto'] ?? '')); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>

<script>
// 1. Reacciones Instantáneas en 0 ms
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.react-btn').forEach(btn => {
        const id = btn.getAttribute('data-id');
        const tipo = btn.getAttribute('data-tipo');
        if (localStorage.getItem('reaccion_' + id + '_' + tipo) === '1') {
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

    let actual = parseInt(countSpan.textContent, 10) || 0;
    countSpan.textContent = Math.max(0, actual + delta);

    if (yaVoto) {
        localStorage.removeItem(key);
        btn.classList.remove('voted');
    } else {
        localStorage.setItem(key, '1');
        btn.classList.add('voted');
    }

    const formData = new FormData();
    formData.append('accion', 'reaccionar');
    formData.append('id', id);
    formData.append('tipo', tipo);
    formData.append('delta', delta);

    fetch('articulo.php', {
        method: 'POST',
        body: formData
    }).catch(() => {});
}

// 2. Compartir en UNA SOLA TARJETA (Foto Open Graph + Título + Resumen + Enlace integrados)
async function compartirConFotoReal(btn) {
    const titulo = btn.getAttribute('data-titulo') || document.title;
    const resumen = btn.getAttribute('data-resumen') || '';
    const url = btn.getAttribute('data-url') || window.location.href;

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

    const textoCompleto = `*${titulo}*\n\n${resumen}\n\n${url}`;
    if (/Android|iPhone|iPad|iPod/i.test(navigator.userAgent)) {
        window.open('https://api.whatsapp.com/send?text=' + encodeURIComponent(textoCompleto), '_blank');
    } else if (navigator.clipboard) {
        navigator.clipboard.writeText(textoCompleto);
        alert('Artículo y enlace copiados al portapapeles');
    }
}

// 3. Sintetizador de Voz (Web Speech API)
let leyendo = false;
function alternarLecturaVoz() {
    if (!('speechSynthesis' in window)) {
        alert('Tu navegador no soporta lectura por voz.');
        return;
    }
    const synth = window.speechSynthesis;
    const btnPlay = document.getElementById('btnPlayTTS');
    const btnStop = document.getElementById('btnStopTTS');

    if (synth.speaking) {
        if (synth.paused) {
            synth.resume();
            btnPlay.textContent = '⏸ Pausar voz';
        } else {
            synth.pause();
            btnPlay.textContent = '▶ Continuar voz';
        }
        return;
    }

    const titulo = document.getElementById('tituloArticulo')?.innerText || '';
    const cuerpo = document.getElementById('cuerpoArticulo')?.innerText || '';
    const utter = new SpeechSynthesisUtterance(titulo + '. ' + cuerpo);
    utter.lang = 'es-ES';
    utter.rate = 1.0;

    utter.onend = function() {
        btnPlay.textContent = '▶ Escuchar artículo';
        btnStop.style.display = 'none';
    };

    btnPlay.textContent = '⏸ Pausar voz';
    btnStop.style.display = 'inline-flex';
    synth.cancel();
    synth.speak(utter);
}

function detenerLecturaVoz() {
    if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel();
    }
    document.getElementById('btnPlayTTS').textContent = '▶ Escuchar artículo';
    document.getElementById('btnStopTTS').style.display = 'none';
}
window.addEventListener('beforeunload', detenerLecturaVoz);
</script>
</body>
</html>