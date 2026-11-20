<?php
// index.php - Portada con Mosaico Asimétrico de Espaciado Uniforme (Masonry), Buscador en Vivo, Categorías, Reacciones Instantáneas (0ms) y Compartido Unificado
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
        file_put_contents($archivo_json, json_encode($articulos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
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

// Filtrar artículos eliminados y reindexar
$articulos = array_values(array_filter($articulos, function($art) {
    return !isset($art['eliminado']) || $art['eliminado'] !== true;
}));

// Extraer categorías únicas normalizadas y contar artículos por categoría
$conteo_categorias = [];
foreach ($articulos as $art) {
    $cat = mb_strtoupper(trim($art['categoria'] ?? 'NOTICIA'), 'UTF-8');
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
    <meta property="og:image:width" content="300">
    <meta property="og:image:height" content="200">
    <meta name="twitter:card" content="summary">
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
        a, button, input {
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
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.6);
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

        /* BARRA DE FILTRADO POR CATEGORÍAS Y BUSCADOR EN VIVO */
        .categorias-wrapper {
            border-top: 1px solid #1f1f1f;
            background: #0d0d0d;
            padding: 10px 20px;
        }
        .filtros-grid {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
            gap: 14px;
            flex-wrap: wrap;
        }
        .categorias-bar {
            display: flex;
            gap: 8px;
            align-items: center;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 2px;
            flex: 1 1 auto;
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

        /* BUSCADOR EN VIVO */
        .buscador-box {
            display: flex;
            align-items: center;
            background: #161616;
            border: 1px solid #333;
            border-radius: 20px;
            padding: 4px 12px;
            min-width: 240px;
            max-width: 340px;
            width: 100%;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .buscador-box:focus-within {
            border-color: var(--accent-gold);
            box-shadow: 0 0 0 2px rgba(255, 184, 28, 0.15);
        }
        .buscador-icono {
            font-size: 13px;
            color: #888;
            margin-right: 8px;
            user-select: none;
        }
        .buscador-input {
            background: transparent;
            border: none;
            color: white;
            font-family: inherit;
            font-size: 13px;
            width: 100%;
            outline: none;
            padding: 4px 0;
        }
        .buscador-input::placeholder {
            color: #777;
        }
        .btn-limpiar-buscador {
            background: transparent;
            border: none;
            color: #888;
            font-size: 14px;
            cursor: pointer;
            padding: 2px 4px;
            display: none;
            line-height: 1;
        }
        .btn-limpiar-buscador:hover {
            color: var(--accent-gold);
        }
        @media (max-width: 680px) {
            .buscador-box {
                max-width: 100%;
            }
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
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .articulo-mosaico:hover {
            border-color: #4a4a4a;
            box-shadow: 0 8px 26px rgba(0, 0, 0, 0.65);
        }
        .articulo-mosaico.destacada-portada {
            border-color: rgba(255, 184, 28, 0.45);
            background: linear-gradient(180deg, #161511 0%, #111111 100%);
        }
        .articulo-mosaico.oculto {
            display: none !important;
        }

        /* AVISO CUANDO NO HAY COINCIDENCIAS */
        .sin-resultados {
            grid-column: 1 / -1;
            background: #141414;
            border: 1px dashed #3a3a3a;
            border-radius: 8px;
            padding: 40px 20px;
            text-align: center;
            color: #bbb;
            margin-bottom: 25px;
        }
        .sin-resultados h4 {
            margin: 0 0 8px 0;
            color: white;
            font-size: 18px;
        }
        .sin-resultados p {
            margin: 0 0 18px 0;
            font-size: 14px;
            color: #888;
        }
        .btn-restablecer {
            background: var(--accent-gold);
            color: black;
            border: none;
            padding: 9px 18px;
            border-radius: 4px;
            font-weight: 800;
            font-size: 12px;
            text-transform: uppercase;
            cursor: pointer;
        }
        .btn-restablecer:hover {
            background: white;
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

        .badges-fila {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
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
        .badge-portada {
            background: var(--vibrant-green);
            color: white;
            font-weight: 900;
            font-size: 10px;
            padding: 4px 8px;
            border-radius: 3px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
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
        .texto-container h3 a {
            color: inherit;
            text-decoration: none;
            transition: color 0.15s;
        }
        .texto-container h3 a:hover {
            color: var(--accent-gold);
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
                border-color: rgba(255, 184, 28, 0.45);
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

        /* BOTONES DE ACCIÓN Y REACCIÓN PEGADOS AL TEXTO (MÁS GRANDES Y VISIBLES) */
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
            gap: 8px;
            align-items: center;
        }
        .btn-leer {
            padding: 11px 14px;
            background: var(--accent-gold);
            color: black;
            border: none;
            font-weight: 900;
            font-size: 13.5px;
            letter-spacing: 0.5px;
            cursor: pointer;
            border-radius: 5px;
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
        .btn-compartir-unico {
            background: var(--vibrant-green);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 11px 14px;
            border-radius: 5px;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            white-space: nowrap;
            transition: transform 0.1s, background 0.15s;
        }
        .btn-compartir-unico:hover {
            background: #17803e;
        }
        .btn-compartir-unico:active {
            transform: scale(0.97);
        }

        .reacciones-bar {
            display: flex;
            gap: 10px;
        }
        .btn-reaccion {
            background: #1c1c1c;
            border: 1px solid #444;
            color: #e8e8e8;
            border-radius: 6px;
            padding: 10px 14px;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-grow: 1;
            justify-content: center;
            transition: transform 0.08s, background 0.12s, color 0.12s;
            user-select: none;
        }
        .btn-reaccion .emoji-reac {
            font-size: 19px;
            line-height: 1;
        }
        .btn-reaccion:active {
            transform: scale(0.94);
        }
        .btn-reaccion:hover {
            border-color: var(--accent-gold);
            color: white;
            background: rgba(255, 184, 28, 0.14);
        }
        .btn-reaccion.seleccionado {
            background: var(--accent-gold);
            color: black;
            border-color: var(--accent-gold);
        }

        /* MODAL UNIFICADO DE COMPARTIR (TARJETA FOTO + TEXTO + SELECTOR DE REDES) */
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
            position: relative;
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

        /* TARJETA UNIFICADA FOTO + TEXTO */
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

    <div class="categorias-wrapper">
        <div class="filtros-grid">
            <?php if (!empty($conteo_categorias)): ?>
            <div class="categorias-bar">
                <button class="btn-cat activo" data-cat="TODAS" onclick="filtrarCategoria('TODAS', this)">
                    Todas <span><?php echo count($articulos); ?></span>
                </button>
                <?php foreach ($conteo_categorias as $cat_nombre => $cantidad): ?>
                    <button class="btn-cat" data-cat="<?php echo htmlspecialchars($cat_nombre, ENT_QUOTES, 'UTF-8'); ?>" onclick="filtrarCategoria('<?php echo htmlspecialchars($cat_nombre, ENT_QUOTES, 'UTF-8'); ?>', this)">
                        <?php echo htmlspecialchars($cat_nombre, ENT_QUOTES, 'UTF-8'); ?>
                        <span><?php echo $cantidad; ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="buscador-box">
                <span class="buscador-icono">🔍</span>
                <input type="search" id="buscadorVivo" class="buscador-input" placeholder="Buscar noticia, tema o fuente..." oninput="filtrarEnVivo()" autocomplete="off">
                <button type="button" id="btnLimpiarBuscador" class="btn-limpiar-buscador" onclick="limpiarBusqueda()" title="Limpiar búsqueda">✕</button>
            </div>
        </div>
    </div>
</nav>

<div class="mosaico-container" id="contenedorMosaico">
    <?php if (empty($articulos)): ?>
        <p style="text-align: center; color: #888; grid-column: 1 / -1;">No hay artículos publicados todavía. Utiliza <a href="admin.php" style="color:var(--accent-gold);">el panel CMS</a> para crear el primero.</p>
    <?php else: ?>
        <?php foreach ($articulos as $indice => $art): 
            $id = $art['id'] ?? '';
            $titulo_raw = trim($art['titulo'] ?? 'Sin título');
            $resumen_raw = trim($art['resumen'] ?? '');
            $resumen_limpio = trim(preg_replace('/\s+/u', ' ', $resumen_raw));
            $titulo = htmlspecialchars($titulo_raw, ENT_QUOTES, 'UTF-8');
            $resumen = htmlspecialchars($resumen_raw, ENT_QUOTES, 'UTF-8');
            $categoriaRaw = mb_strtoupper(trim($art['categoria'] ?? 'NOTICIA'), 'UTF-8');
            if ($categoriaRaw === '') $categoriaRaw = 'NOTICIA';
            $categoria = htmlspecialchars($categoriaRaw, ENT_QUOTES, 'UTF-8');
            $fecha = htmlspecialchars($art['fecha'] ?? '', ENT_QUOTES, 'UTF-8');
            $fuente = htmlspecialchars($art['fuente'] ?? '', ENT_QUOTES, 'UTF-8');
            $formato = htmlspecialchars($art['formato'] ?? '', ENT_QUOTES, 'UTF-8');
            $es_portada = ($indice === 0 || $formato === 'span-big');
            $texto_busqueda = htmlspecialchars(mb_strtolower($titulo_raw . ' ' . $resumen_raw . ' ' . $categoriaRaw . ' ' . ($art['fuente'] ?? ''), 'UTF-8'), ENT_QUOTES, 'UTF-8');
            
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
            <article class="articulo-mosaico <?php echo $formato; ?> <?php echo $es_portada ? 'destacada-portada' : ''; ?>" 
                     data-categoria="<?php echo $categoria; ?>" 
                     data-busqueda="<?php echo $texto_busqueda; ?>">
                <a href="articulo.php?id=<?php echo rawurlencode($id); ?>" class="mosaico-img-container">
                    <img src="<?php echo $imagen; ?>" id="img-art-<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>" class="mosaico-img" alt="<?php echo $titulo; ?>" decoding="async" onload="ajustarMosaico()" onerror="this.onerror=null; this.src='images/default.jpg'; ajustarMosaico();">
                </a>

                <div class="contenido-columna">
                    <div class="badges-fila">
                        <?php if ($indice === 0): ?>
                            <span class="badge-portada">PORTADA</span>
                        <?php endif; ?>
                        <span class="badge"><?php echo $categoria; ?></span>
                    </div>
                    <div class="texto-container">
                        <span class="fecha-tarjeta"><?php echo $fecha; ?><?php echo $fuente ? " | $fuente" : ""; ?></span>
                        <h3><a href="articulo.php?id=<?php echo rawurlencode($id); ?>"><?php echo $titulo; ?></a></h3>
                        <p><?php echo $resumen; ?></p>
                    </div>
                    <div class="tarjeta-footer">
                        <div class="acciones-fila">
                            <a href="articulo.php?id=<?php echo rawurlencode($id); ?>" class="btn-leer">Leer</a>
                            
                            <button type="button"
                                    class="btn-compartir-unico" 
                                    data-id="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-url="<?php echo htmlspecialchars($url_articulo, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-titulo="<?php echo $titulo; ?>"
                                    data-resumen="<?php echo htmlspecialchars(mb_substr($resumen_limpio, 0, 200), ENT_QUOTES, 'UTF-8'); ?>"
                                    data-img="<?php echo $imagen; ?>"
                                    onclick="abrirModalCompartir(this)" 
                                    title="Compartir en redes sociales">🔗 Compartir</button>
                        </div>
                        <div class="reacciones-bar">
                            <button class="btn-reaccion" id="btn-like-<?php echo $id; ?>" onclick="reaccionar('<?php echo $id; ?>', 'like')">
                                <span class="emoji-reac">👍</span> <span id="likes-<?php echo $id; ?>"><?php echo $likes; ?></span>
                            </button>
                            <button class="btn-reaccion" id="btn-sad-<?php echo $id; ?>" onclick="reaccionar('<?php echo $id; ?>', 'sad')">
                                <span class="emoji-reac">😢</span> <span id="sads-<?php echo $id; ?>"><?php echo $sads; ?></span>
                            </button>
                        </div>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>

        <div id="sinResultados" class="sin-resultados" style="display: none;">
            <h4>No se encontraron artículos</h4>
            <p>No hay publicaciones que coincidan con tu búsqueda o categoría seleccionada.</p>
            <button type="button" class="btn-restablecer" onclick="restablecerFiltros()">Mostrar todas las noticias</button>
        </div>
    <?php endif; ?>
</div>

<!-- MODAL DE COMPARTIR UNIFICADO -->
<div id="modalCompartir" class="modal-compartir-overlay" onclick="if(event.target===this) cerrarModalCompartir()">
    <div class="modal-compartir-caja">
        <div class="modal-compartir-header">
            <h4>Compartir Publicación</h4>
            <button type="button" class="btn-cerrar-compartir" onclick="cerrarModalCompartir()">×</button>
        </div>

        <div class="tarjeta-previa-compartir">
            <img id="previaShareImg" src="images/default.jpg" alt="Vista previa">
            <div class="tarjeta-previa-info">
                <strong id="previaShareTitulo">Título</strong>
                <p id="previaShareResumen">Resumen</p>
            </div>
        </div>

        <div class="grid-redes-compartir">
            <a id="linkShareWA" href="#" target="_blank" rel="noopener" class="btn-red-opcion wa">WhatsApp</a>
            <a id="linkShareFB" href="#" target="_blank" rel="noopener" class="btn-red-opcion fb">Facebook</a>
            <button type="button" onclick="compartirInstagram()" class="btn-red-opcion ig">Instagram</button>
            <a id="linkShareTG" href="#" target="_blank" rel="noopener" class="btn-red-opcion tg">Telegram</a>
            <a id="linkShareX" href="#" target="_blank" rel="noopener" class="btn-red-opcion xx">X (Twitter)</a>
            <button type="button" onclick="compartirTikTok()" class="btn-red-opcion tk">TikTok</button>
            <a id="linkShareLI" href="#" target="_blank" rel="noopener" class="btn-red-opcion li">LinkedIn</a>
            <a id="linkShareEmail" href="#" class="btn-red-opcion em">Correo</a>
            <button type="button" onclick="compartirNativoConMiniatura()" class="btn-red-opcion nativo">📲 Compartir desde el dispositivo (foto miniatura + texto)</button>
            <button type="button" onclick="copiarTarjetaCompleta()" class="btn-red-opcion copiar">📋 Copiar título, resumen y enlace</button>
        </div>
    </div>
</div>

<div id="toast-notificacion"></div>

<script>
    let categoriaActiva = 'TODAS';
    let shareActual = { id: '', url: '', titulo: '', resumen: '', img: '' };

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

    function normalizarTexto(texto) {
        return (texto || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }

    function aplicarFiltros() {
        const input = document.getElementById('buscadorVivo');
        const btnLimpiar = document.getElementById('btnLimpiarBuscador');
        const termino = normalizarTexto(input ? input.value : '');

        if (btnLimpiar) {
            btnLimpiar.style.display = termino.length > 0 ? 'inline-block' : 'none';
        }

        const tarjetas = document.querySelectorAll('.articulo-mosaico');
        let visibles = 0;

        tarjetas.forEach(tarjeta => {
            const catTarjeta = tarjeta.getAttribute('data-categoria');
            const textoTarjeta = normalizarTexto(tarjeta.getAttribute('data-busqueda'));
            const coincideCat = (categoriaActiva === 'TODAS' || catTarjeta === categoriaActiva);
            const coincideTexto = (termino === '' || textoTarjeta.includes(termino));

            if (coincideCat && coincideTexto) {
                tarjeta.classList.remove('oculto');
                visibles++;
            } else {
                tarjeta.classList.add('oculto');
            }
        });

        const avisoSinResultados = document.getElementById('sinResultados');
        if (avisoSinResultados) {
            avisoSinResultados.style.display = (visibles === 0) ? 'block' : 'none';
        }

        ajustarMosaico();
    }

    function filtrarCategoria(categoriaElegida, btnClickeado) {
        categoriaActiva = categoriaElegida;
        const botones = document.querySelectorAll('.btn-cat');
        botones.forEach(b => b.classList.remove('activo'));
        if (btnClickeado) btnClickeado.classList.add('activo');
        aplicarFiltros();
    }

    function filtrarEnVivo() {
        aplicarFiltros();
    }

    function limpiarBusqueda() {
        const input = document.getElementById('buscadorVivo');
        if (input) {
            input.value = '';
            input.focus();
        }
        aplicarFiltros();
    }

    function restablecerFiltros() {
        const input = document.getElementById('buscadorVivo');
        if (input) input.value = '';
        const btnTodas = document.querySelector('.btn-cat[data-cat="TODAS"]');
        filtrarCategoria('TODAS', btnTodas);
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

        fetch(`index.php?ajax_reaccionar=1&id=${encodeURIComponent(id)}&tipo=${encodeURIComponent(tipo)}&previo=${encodeURIComponent(previo)}`, {
            cache: 'no-store'
        }).catch(() => {});
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

    function abrirModalCompartir(btn) {
        shareActual = {
            id: btn.getAttribute('data-id') || '',
            url: btn.getAttribute('data-url') || window.location.href,
            titulo: btn.getAttribute('data-titulo') || 'Portal Amazonía',
            resumen: btn.getAttribute('data-resumen') || '',
            img: btn.getAttribute('data-img') || 'images/default.jpg'
        };

        document.getElementById('previaShareImg').src = shareActual.img;
        document.getElementById('previaShareTitulo').textContent = shareActual.titulo;
        document.getElementById('previaShareResumen').textContent = shareActual.resumen;

        const textoWA = `*${shareActual.titulo}*\n${shareActual.resumen}\n${shareActual.url}`;
        document.getElementById('linkShareWA').href = `https://api.whatsapp.com/send?text=${encodeURIComponent(textoWA)}`;
        document.getElementById('linkShareFB').href = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareActual.url)}`;
        document.getElementById('linkShareTG').href = `https://t.me/share/url?url=${encodeURIComponent(shareActual.url)}&text=${encodeURIComponent(shareActual.titulo + ' — ' + shareActual.resumen)}`;
        document.getElementById('linkShareX').href = `https://twitter.com/intent/tweet?url=${encodeURIComponent(shareActual.url)}&text=${encodeURIComponent(shareActual.titulo)}`;
        document.getElementById('linkShareLI').href = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(shareActual.url)}`;
        document.getElementById('linkShareEmail').href = `mailto:?subject=${encodeURIComponent(shareActual.titulo)}&body=${encodeURIComponent(textoWA)}`;

        document.getElementById('modalCompartir').classList.add('abierto');
    }

    function cerrarModalCompartir() {
        document.getElementById('modalCompartir').classList.remove('abierto');
    }

    function copiarTarjetaCompleta() {
        const textoFormateado = `*${shareActual.titulo}*\n\n${shareActual.resumen}\n\n${shareActual.url}`;
        if (navigator.clipboard) {
            navigator.clipboard.writeText(textoFormateado).then(() => {
                mostrarToast("📋 ¡Tarjeta de texto y enlace copiada!");
            });
        }
    }

    function compartirInstagram() {
        copiarTarjetaCompleta();
        window.open('https://www.instagram.com/', '_blank', 'noopener');
    }

    function compartirTikTok() {
        copiarTarjetaCompleta();
        window.open('https://www.tiktok.com/', '_blank', 'noopener');
    }

    async function compartirNativoConMiniatura() {
        const textoFormateado = `*${shareActual.titulo}*\n\n${shareActual.resumen}\n\n${shareActual.url}`;
        const imgEl = document.getElementById(`img-art-${shareActual.id}`);

        if (navigator.share) {
            try {
                const archivoFoto = await obtenerArchivoDesdeImgDOM(imgEl);
                if (archivoFoto && navigator.canShare && navigator.canShare({ files: [archivoFoto] })) {
                    await navigator.share({
                        files: [archivoFoto],
                        title: shareActual.titulo,
                        text: textoFormateado
                    });
                    cerrarModalCompartir();
                    return;
                }
            } catch (err) {
                if (err.name === 'AbortError') return;
            }

            try {
                await navigator.share({
                    title: shareActual.titulo,
                    text: `${shareActual.titulo} — ${shareActual.resumen}`,
                    url: shareActual.url
                });
                cerrarModalCompartir();
                return;
            } catch (err) {
                if (err.name === 'AbortError') return;
            }
        }

        copiarTarjetaCompleta();
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') cerrarModalCompartir();
    });
</script>

</body>
</html>