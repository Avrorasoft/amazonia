<?php
// mapa.php - Mapa Indígena Inmersivo de la Amazonía y Tierras Bajas de Bolivia (Optimizado para iPhone SE, Móviles y Escritorio)
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
    <title>Portal Amazonía | Mapa Indígena Inmersivo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Mapa interactivo con radar satelital e historia de las 36 Naciones Originarias de Bolivia. Autor: Wigberto Rivero Pinto.">
    
    <!-- Open Graph HD para compartir el Mapa en WhatsApp y Facebook -->
    <meta property="og:site_name" content="Portal Amazonía">
    <meta property="og:title" content="Mapa Indígena Inmersivo | Naciones Originarias de Bolivia">
    <meta property="og:description" content="Explora con radar interactivo la ubicación e historia de los pueblos indígenas. Autor: Wigberto Rivero Pinto.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo htmlspecialchars($url_base_portal . '/mapa.php', ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($url_base_portal . '/Mapa_indigena.jpg', ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:card" content="summary_large_image">

    <style>
        /* =========================================
           VARIABLES Y DISEÑO BASE (MODO OSCURO)
           ========================================= */
        :root { 
            --deep-green: #0A3D0A;
            --vibrant-green: #1D9A4C;
            --accent-gold: #FFB81C;
            --dark-bg: #121212; 
            --panel-bg: #1e1e1e; 
            --text-main: #E0E0E0; 
        }

        * { box-sizing: border-box; }

        body { 
            font-family: 'Segoe UI', Roboto, sans-serif; 
            background-color: var(--dark-bg); 
            color: var(--text-main); 
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            height: 100vh;
            height: 100dvh; 
            overflow: hidden; 
            -webkit-text-size-adjust: 100%;
        }

        /* =========================================
           NAVEGACIÓN COMPACTA
           ========================================= */
        nav {
            flex-shrink: 0;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            z-index: 2000;
            box-shadow: 0 4px 20px rgba(0,0,0,0.6);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 15px;
            flex-wrap: wrap;
            border-bottom: 1px solid #222;
        }

        .nav-brand {
            color: white;
            text-decoration: none;
            font-weight: 900;
            font-size: 15px;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .nav-links-container {
            display: flex;
            gap: 6px;
        }
        .nav-links-container a {
            color: #ddd;
            text-decoration: none;
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 6px 10px;
            transition: all 0.3s;
        }
        .nav-links-container a:hover { color: var(--accent-gold); }

        /* =========================================
           CABECERA Y AUTOR
           ========================================= */
        header { 
            flex-shrink: 0;
            padding: 10px 15px; 
            border-bottom: 1px solid #333; 
            text-align: center;
            background: #080808;
            position: relative;
        }

        h1 { 
            text-transform: uppercase; 
            letter-spacing: 1.5px; 
            margin: 0; 
            font-size: clamp(16px, 3.5vw, 22px);
            color: #ffffff;
        }

        p.instrucciones {
            color: #aaaaaa;
            margin: 3px auto 0 auto;
            max-width: 700px;
            font-size: 12px;
            font-style: italic;
            text-align: center;
        }

        .autor-mapa {
            color: var(--accent-gold);
            font-size: 11px;
            margin-top: 3px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        #btn-volver {
            display: none;
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: #252525;
            color: white;
            border: 1px solid var(--accent-gold);
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            transition: all 0.3s;
            z-index: 100;
        }
        #btn-volver.visible {
            display: inline-block;
        }
        #btn-volver:hover {
            background: var(--accent-gold);
            color: black;
        }

        /* =========================================
           ÁREA PRINCIPAL (FLEXBOX)
           ========================================= */
        main {
            flex-grow: 1;
            display: flex;
            flex-direction: row; 
            padding: 10px;
            gap: 12px;
            overflow: hidden;
            min-height: 0;
            transition: all 0.4s ease;
        }

        /* =========================================
           PANEL DE BOTONES Y BUSCADOR RÁPIDO
           ========================================= */
        #columna-izquierda {
            flex: 0 0 330px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-height: 0;
            transition: all 0.4s ease;
        }

        #buscador-etnia {
            width: 100%;
            padding: 8px 12px;
            background: #141414;
            border: 1px solid #333;
            border-radius: 6px;
            color: white;
            font-size: 12px;
            outline: none;
            flex-shrink: 0;
        }
        #buscador-etnia:focus {
            border-color: var(--accent-gold);
        }

        #panel-botones {
            flex-grow: 1;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(135px, 1fr));
            grid-auto-rows: max-content;
            gap: 8px;
            overflow-y: auto;
            padding-right: 4px;
            -webkit-overflow-scrolling: touch;
        }

        #panel-botones::-webkit-scrollbar { width: 5px; }
        #panel-botones::-webkit-scrollbar-thumb { background: #333; border-radius: 4px; }

        .btn-etnia {
            background: #1a1a1a;
            color: #ddd;
            border: 1px solid #333;
            padding: 10px 8px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 12px;
            cursor: pointer;
            text-align: center;
            transition: all 0.25s;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            user-select: none;
        }

        .btn-etnia:hover {
            background: #2a2a2a;
            border-color: var(--accent-gold);
            color: white;
        }

        .btn-etnia.activo {
            background: var(--accent-gold);
            color: black;
            border-color: var(--accent-gold);
            grid-column: 1 / -1; 
            font-size: 16px;
            padding: 14px;
            box-shadow: 0 5px 20px rgba(255, 184, 28, 0.35);
            animation: pulse-btn 2s infinite;
        }

        .btn-etnia .clic-info {
            display: none;
            font-size: 11px;
            font-weight: bold;
            margin-top: 4px;
            background: rgba(0,0,0,0.15);
            padding: 3px 8px;
            border-radius: 4px;
            text-align: center;
        }

        .btn-etnia.activo .clic-info {
            display: block; 
        }

        @keyframes pulse-btn {
            0% { transform: scale(1); }
            50% { transform: scale(1.015); }
            100% { transform: scale(1); }
        }

        /* =========================================
           CONTENEDOR DEL MAPA (SINCRONIZADO AL MILÍMETRO)
           ========================================= */
        #map-wrapper {
            flex-grow: 1; 
            position: relative;
            background: #050505;
            border-radius: 8px;
            border: 1px solid #222;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            min-height: 0;
        }

        img#mapa_img {
            position: absolute;
            width: 100%;
            height: 100%;
            object-fit: contain; 
            z-index: 1;
        }

        svg#mapa_svg {
            position: absolute;
            width: 100%;
            height: 100%;
            object-fit: contain; 
            z-index: 2;
            pointer-events: none; 
        }

        .poligono-etnia {
            fill: transparent;
            stroke: transparent;
            stroke-width: 2;
            transition: all 0.3s;
            pointer-events: auto;
            cursor: pointer;
        }
        .poligono-etnia:hover {
            fill: rgba(255, 184, 28, 0.22);
            stroke: rgba(255, 184, 28, 0.6);
        }

        .poligono-etnia.parpadeo {
            fill: rgba(255, 184, 28, 0.45); 
            stroke: var(--accent-gold); 
            stroke-width: 2.5;
            animation: blink-map 1.5s ease-in-out infinite;
        }

        @keyframes blink-map {
            0% { opacity: 0.45; }
            50% { opacity: 1; filter: drop-shadow(0 0 12px var(--accent-gold)); }
            100% { opacity: 0.45; }
        }

        /* =========================================
           VENTANA DE LECTURA INMERSIVA (MODAL)
           ========================================= */
        .modal-inmersivo {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.96); 
            z-index: 4000;
            align-items: flex-start;
            justify-content: center;
            overflow-y: auto;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .lectura-box {
            background: #111111; 
            color: var(--text-main);
            width: 100%;
            max-width: 880px; 
            min-height: 100%; 
            position: relative;
            box-shadow: 0 0 50px rgba(0,0,0,1);
            display: flex;
            flex-direction: column;
            border-left: 1px solid #222;
            border-right: 1px solid #222;
        }
        
        .btn-cerrar-lectura {
            position: fixed;
            top: 16px; 
            right: 16px;
            background: var(--accent-gold); 
            color: #000;
            border: none;
            border-radius: 50%;
            width: 42px; height: 42px; 
            font-size: 22px;
            font-weight: bold;
            cursor: pointer;
            z-index: 4100;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-cerrar-lectura:hover { background: white; transform: scale(1.08); }

        .lectura-hero-container {
            padding: 35px 20px 20px 20px;
            background: #080808;
            text-align: center;
            border-bottom: 1px solid #222;
        }

        .lectura-hero {
            width: 100%;
            max-width: 420px;
            max-height: 320px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.9);
            border: 1px solid #333;
        }

        .lectura-body { 
            padding: 25px 45px 60px 45px; 
            flex-grow: 1;
        }

        .lectura-top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 12px;
        }

        .badge {
            background: var(--accent-gold);
            color: black;
            font-weight: 900;
            font-size: 11px;
            padding: 5px 12px;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .btn-audio-modal {
            background: #1a1a1a;
            color: var(--accent-gold);
            border: 1px solid var(--accent-gold);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-audio-modal:hover {
            background: var(--accent-gold);
            color: black;
        }

        .lectura-body h1 {
            color: white;
            font-weight: 900;
            margin-top: 0;
            margin-bottom: 22px;
            font-size: clamp(24px, 4.5vw, 38px);
            border-bottom: 1px solid #333;
            padding-bottom: 12px;
        }
        
        /* TEXTO ESTRICTAMENTE JUSTIFICADO EN EL MODAL */
        .lectura-texto { 
            font-size: 17px; 
            line-height: 1.8; 
            color: #E5E5E5; 
            text-align: justify;
            hyphens: auto;
            -webkit-hyphens: auto;
        }

        .lectura-texto p { margin-bottom: 20px; }

        /* =========================================
           RESPONSIVE MÓVIL (ESPECIAL PARA IPHONE SE)
           ========================================= */
        @media (max-width: 850px) {
            main { flex-direction: column; padding: 6px; gap: 6px; }
            
            #columna-izquierda { 
                flex: 0 0 36%; 
                width: 100%;
            }
            #panel-botones { 
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); 
                padding-right: 0; 
                gap: 6px;
            }
            #map-wrapper { flex: 1 1 64%; }

            /* En modo inmersión móvil ocultamos el buscador y compactamos al máximo el botón activo */
            main.modo-inmersion #columna-izquierda { 
                flex: 0 0 auto; 
            }
            main.modo-inmersion #buscador-etnia {
                display: none;
            }
            main.modo-inmersion #panel-botones {
                max-height: 75px;
                overflow: hidden;
            }
            main.modo-inmersion #map-wrapper { 
                flex-grow: 1; 
            }

            header { padding: 6px 10px; }
            h1 { font-size: 14px; margin-bottom: 1px; }
            .autor-mapa { font-size: 10px; margin-top: 1px; }
            p.instrucciones { font-size: 10px; }
            
            /* Solo se muestra cuando tiene la clase .visible */
            #btn-volver { 
                position: relative; 
                top: auto; 
                left: 0; 
                transform: none; 
                width: 100%; 
                text-align: center; 
                padding: 6px; 
                font-size: 10px;
                margin-bottom: 4px;
            }
            #btn-volver.visible {
                display: block;
            }
            
            .btn-etnia {
                padding: 8px 4px;
                font-size: 11px;
            }
            .btn-etnia.activo {
                font-size: 14px;
                padding: 8px 12px;
            }
            .btn-etnia.activo .clic-info {
                font-size: 10px;
                margin-top: 2px;
            }

            .lectura-body { padding: 18px 18px 40px 18px; }
            .lectura-texto { font-size: 15.5px; line-height: 1.65; }
            .btn-cerrar-lectura { top: 10px; right: 10px; width: 38px; height: 38px; }
        }
    </style>
</head>
<body>

    <!-- Navegación -->
    <nav>
        <a href="index.php" class="nav-brand">
            <img src="images/logo1.png" alt="Logo" style="height: 28px;" onerror="this.style.display='none'">
            PORTAL AMAZONÍA
        </a>
        <div class="nav-links-container">
            <a href="index.php">Inicio</a>
            <a href="index.php#contenedorMosaico">Noticias</a>
            <a href="mapa.php" style="color: var(--accent-gold);">Mapa Indígena</a>
        </div>
    </nav>

    <!-- Encabezado -->
    <header id="mapa-header">
        <button id="btn-volver" onclick="restaurarMapa()">← Volver al listado de 36 naciones</button>
        <h1 id="titulo-principal">Naciones Originarias de Bolivia</h1>
        <div class="autor-mapa">Autor: Wigberto Rivero Pinto</div>
        <p class="instrucciones" id="instrucciones-texto">Selecciona una etnia en el panel o toca directamente su territorio en el mapa para activar el radar.</p>
    </header>

    <!-- Área Principal Flex -->
    <main id="main-area">
        
        <!-- Columna de Búsqueda y Botones -->
        <div id="columna-izquierda">
            <input type="text" id="buscador-etnia" placeholder="🔍 Buscar pueblo o nación indígena..." oninput="filtrarBotones(this.value)">
            <div id="panel-botones"></div>
        </div>

        <!-- Contenedor del Mapa Elástico -->
        <div id="map-wrapper">
            <img src="Mapa_indigena.jpg" alt="Mapa Indígena de Bolivia" id="mapa_img">
            
            <!-- Capa vectorial superpuesta sincronizada con el lienzo 482x550 -->
            <svg id="mapa_svg" viewBox="0 0 482 550" preserveAspectRatio="xMidYMid meet">
                <defs>
                    <marker id="arrow" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                        <path d="M 0 0 L 10 5 L 0 10 z" fill="#FFB81C" />
                    </marker>
                </defs>
                <g id="capa-interactiva-base"></g>
                <g id="capa-radar-activa"></g>
            </svg>
        </div>

    </main>

    <!-- VENTANA INMERSIVA DE LECTURA -->
    <div id="modal-lectura" class="modal-inmersivo">
        <button class="btn-cerrar-lectura" onclick="cerrarLectura()" title="Cerrar">×</button>
        <div class="lectura-box">
            <div class="lectura-hero-container">
                <img id="lectura-img" src="images/default.jpg" class="lectura-hero" alt="Etnia" onerror="this.onerror=null; this.src='images/default.jpg';">
            </div>
            <div class="lectura-body">
                <div class="lectura-top-bar">
                    <span class="badge">Historia y Cultura</span>
                    <button class="btn-audio-modal" id="btnAudioModal" onclick="toggleAudioEtnia()">▶ Escuchar relato</button>
                </div>
                <h1 id="lectura-titulo">Título</h1>
                <div id="lectura-texto" class="lectura-texto"></div>
            </div>
        </div>
    </div>

    <script>
        const etniasData = {
            "Nahua": { imagen: "images_indigena/nahua.jpg", texto: "El pueblo Nahua, históricamente caracterizado como un grupo de contacto inicial o en aislamiento voluntario, habita en las profundidades de la vasta Amazonía, resguardando celosamente su cultura milenaria. Pertenecientes a la gran familia sociolingüística Pano, los nahuas han mantenido un estilo de vida de cazadores, pescadores y recolectores seminómadas, adaptando sus ciclos vitales a las épocas de lluvia y sequía del bosque tropical.\n\nDurante siglos, su existencia estuvo marcada por el profundo respeto hacia los espíritus del monte y la biodiversidad que los rodea. Sin embargo, la historia de los nahuas y otros pueblos amazónicos se vio severamente trastocada por las fiebres extractivistas, como el auge del caucho, que los obligó a replegarse hacia las cabeceras de los ríos más inaccesibles para proteger su integridad física y cultural.\n\nHoy en día, la comprensión sobre su organización social indica que se agrupan en clanes familiares altamente cohesivos, donde la transmisión oral del conocimiento botánico y de las técnicas de supervivencia resulta fundamental. Su situación actual refleja la constante lucha por preservar su territorio ancestral frente a las presiones de la modernidad y el avance de la frontera extractiva." },
            "Toromona": { imagen: "images_indigena/toromona.jpg", texto: "Los Toromona constituyen uno de los pueblos indígenas más enigmáticos y vulnerables de Bolivia, ya que se mantienen en situación de aislamiento voluntario en la inaccesible espesura del Parque Nacional Madidi y áreas adyacentes del norte paceño. Como miembros de la familia etnolingüística Tacana, su existencia ha estado documentada a través de relatos históricos y testimonios de pueblos vecinos, aunque han evitado sistemáticamente el contacto sostenido con la sociedad nacional.\n\nSu decisión de aislamiento no es producto del azar, sino una estrategia histórica de supervivencia adoptada tras los devastadores impactos de las expediciones misioneras y la brutal explotación durante la era de la goma a finales del siglo XIX y principios del XX. Los Toromona desarrollaron un profundo conocimiento de los ciclos de la selva, siendo expertos en la caza furtiva, la recolección de frutos silvestres y la agricultura de subsistencia a pequeña escala.\n\nEl Estado boliviano, reconociendo su extrema vulnerabilidad epidemiológica y territorial, ha establecido normativas estrictas para declarar zonas de protección intangible para salvaguardar la vida y el ecosistema de este pueblo, garantizando que su decisión de aislamiento y su invaluable patrimonio cultural sigan intactos." },
            "Tacana": { imagen: "images_indigena/tacana.jpg", texto: "La nación Tacana es uno de los pilares culturales y demográficos más prominentes del norte del departamento de La Paz y la región adyacente del Beni. Habitantes históricos del piedemonte andino y las llanuras amazónicas, han forjado su identidad en una profunda simbiosis con el entorno de los ríos Beni, Tuichi y Madidi. Su lengua pertenece a la extensa familia lingüística Tacana, que agrupa a diversos pueblos de la región.\n\nLa economía tradicional tacana es un complejo y equilibrado sistema que combina la agricultura de roza, tumba y quema —cultivando yuca, maíz, plátano y arroz— con la caza, la pesca y la recolección estacional de frutos del bosque como la castaña y el majo. Además, poseen un conocimiento botánico excepcional, utilizando cientos de especies vegetales tanto para la medicina tradicional como para la construcción de sus viviendas y la elaboración de herramientas y finas artesanías.\n\nA lo largo de la historia, desde la llegada de las misiones franciscanas hasta la explotación cauchera y maderera, los tacanas han demostrado una notable resiliencia. En las últimas décadas, se han organizado sólidamente en consejos indígenas, logrando la titulación de vastas Tierras Comunitarias de Origen (TCO) y liderando iniciativas de ecoturismo comunitario, como en el Parque Nacional Madidi, demostrando que es posible un desarrollo que respete la madre tierra." },
            "Ese Ejja": { imagen: "images_indigena/eseejja.jpg", texto: "Los Ese Ejja, cuyo nombre se traduce frecuentemente como 'gente verdadera' o 'personas', son un pueblo originario de la familia etnolingüística Tacana que habita principalmente en las riberas del río Madre de Dios y el río Beni, en los departamentos de Pando, Beni y el norte de La Paz, extendiéndose también hacia el territorio peruano.\n\nTradicionalmente, los Ese Ejja fueron nómadas y excelentes navegantes, estructurando toda su cosmovisión y economía alrededor del agua. La pesca no es solo su principal fuente de sustento, sino el eje central de su cultura, dictando sus ritmos de vida, sus asentamientos temporales y sus relatos míticos. Complementan esta actividad con la caza de monte, la recolección de huevos de tortuga y, más recientemente, la agricultura de subsistencia.\n\nEl choque con la sociedad occidental, especialmente durante el violento boom del caucho y la posterior llegada de misiones religiosas, forzó un proceso de sedentarización progresiva. A pesar de la pérdida de vastas extensiones de su territorio ancestral y los retos contemporáneos frente a industrias extractivas que amenazan la pureza de sus ríos, el pueblo Ese Ejja lucha incansablemente por revitalizar su lengua, mantener vivas sus ricas tradiciones orales y asegurar sus derechos territoriales en la Amazonía boliviana." },
            "Pacahuara": { imagen: "images_indigena/pacahuara.jpg", texto: "El pueblo Pacahuara es el testimonio viviente y melancólico de las drásticas transformaciones que sufrió la Amazonía durante los últimos siglos. Pertenecientes a la familia lingüística Pano, antiguamente conformaban una nación numerosa, guerrera y temida que dominaba extensas áreas de los bosques entre los ríos Acre, Abuná y Madre de Dios, en el actual departamento de Pando y norte del Beni.\n\nSu declive demográfico comenzó con las brutales incursiones de los 'seringueiros' durante la fiebre del caucho, quienes diezmaron sus poblaciones mediante masacres, esclavitud y la introducción de enfermedades frente a las cuales no tenían inmunidad. Para mediados del siglo XX, los sobrevivientes habían sido reducidos a pequeños grupos familiares que vivían en constante huida. Eventualmente, en las décadas de 1960 y 1970, el contacto forzado impulsado por misiones evangelizadoras terminó por desarraigar a las últimas familias de su territorio original, reubicándolas cerca de comunidades Chácobo.\n\nHoy en día, la identidad Pacahuara se encuentra en un estado crítico, con escasísimos hablantes fluidos de su lengua materna. A pesar de esta tragedia demográfica, los descendientes luchan por preservar la memoria de sus abuelos, sus mitos de creación, su destreza en el uso de los recursos del bosque y su característico adorno nasal, como un acto de resistencia para no desaparecer en el olvido de la historia amazónica." },
            "Araona": { imagen: "images_indigena/araona.jpg", texto: "El pueblo Araona, perteneciente a la extensa familia sociolingüística Tacana, habita en las recónditas selvas del noroeste boliviano, asentados principalmente a lo largo del río Manupare, en el departamento de Pando y el norte de La Paz. Antes del siglo XIX, los Araona eran uno de los grupos indígenas más grandes e influyentes de la región, con una compleja organización social, amplias redes comerciales y un profundo dominio territorial.\n\nLa llegada de las empresas caucheras fue catastrófica para esta nación. Miles de araonas fueron capturados y forzados a trabajar bajo condiciones de esclavitud en las barracas gomeras, lo que, sumado a las enfermedades introducidas, provocó un genocidio que redujo su población a unas cuantas familias que lograron escapar hacia las zonas más inaccesibles del bosque.\n\nEn la actualidad, los Araona han logrado estabilizar su crecimiento demográfico. Mantienen gran parte de su estilo de vida tradicional, basado en la agricultura itinerante, la caza, la pesca y la recolección de frutos amazónicos. Su organización social está fuertemente guiada por sus líderes espirituales o chamanes, y conservan celosamente su idioma y su cosmovisión, que concibe al bosque no como un recurso, sino como un entramado de espíritus protectores con los que se debe convivir en rigurosa armonía." },
            "Cavineño": { imagen: "images_indigena/cavineno.jpg", texto: "Los Cavineños, pueblo amazónico de raíz lingüística Tacana, se ubican históricamente en los municipios de Riberalta y Reyes, en las llanuras boscosas de la cuenca del río Beni, cerca de los ríos Biata, Geneshuaya y otros afluentes. Su historia de contacto se remonta al siglo XVIII, cuando fueron reducidos en misiones franciscanas, un proceso que alteró profundamente su organización sociopolítica y sus patrones de asentamiento.\n\nA pesar del fuerte impacto misional y la posterior incursión cauchera, los Cavineños lograron amalgamar sus creencias ancestrales con el catolicismo, creando una rica cultura sincrética. Son excepcionales artesanos, dominando el tejido en base a fibras vegetales y algodón, y expertos en la agricultura de subsistencia, la pesca y la recolección de la preciada castaña, que hoy representa una de sus principales fuentes de ingreso económico.\n\nSu territorio, rico en maderas preciosas y biodiversidad, ha enfrentado el acoso constante de taladores ilegales y ganaderos. Sin embargo, a través de una fuerte organización política a nivel de capitanías, los Cavineños han logrado consolidar legalmente sus Tierras Comunitarias de Origen (TCO), asegurando así el espacio vital necesario para la reproducción material y espiritual de las futuras generaciones." },
            "Chácobo": { imagen: "images_indigena/chacobo.jpg", texto: "El pueblo Chácobo, integrante de la familia lingüística Pano, habita principalmente en las cercanías del río Ivón, el río Benicito y el lago Rogaguado, en la provincia Vaca Díez del departamento del Beni. Al igual que muchos otros pueblos panos, los Chácobo vivían originalmente en grandes malocas comunales y se organizaban en grupos seminómadas altamente adaptados al bosque tropical.\n\nFueron uno de los pocos pueblos de la región que logró resistir con relativo éxito las incursiones durante el auge de la goma, retrocediendo hacia las zonas más aisladas. Fue a mediados del siglo XX cuando el contacto definitivo se dio a través de misiones protestantes del Instituto Lingüístico de Verano. Este contacto introdujo la sedentarización en pueblos unifamiliares, pero los Chácobo supieron preservar con firmeza su idioma nativo, el cual es hablado por la gran mayoría de la población, desde los ancianos hasta los niños.\n\nLa subsistencia Chácobo depende de un equilibrio entre la agricultura de roza y quema, la caza, la pesca y la importante recolección y comercialización de la nuez de Brasil (castaña) y palmito silvestre. Sus conocimientos etnobotánicos son vastos y detallados, utilizando innumerables plantas para usos medicinales, alimenticios, rituales y de construcción, destacando su profundo arraigo e identidad amazónica." },
            "Moré": { imagen: "images_indigena/more.jpg", texto: "La nación Moré (o Iténez) es un pueblo indígena ubicado en la estratégica confluencia de los inmensos ríos Mamoré y Guaporé (Iténez), en el límite fronterizo con Brasil, en el departamento del Beni. Pertenecientes a la familia lingüística Chapacura, fueron históricamente conocidos por su bravura y su notable destreza en la navegación de las difíciles aguas de estos ríos caudalosos.\n\nDurante siglos, mantuvieron una fiera independencia, rechazando los intentos de evangelización de los jesuitas en el siglo XVIII y resistiendo las expediciones extractivistas. Su forma de vida dependía casi por completo de la riqueza fluvial: la pesca, la recolección de huevos de tortuga acuática y la agricultura estacional en las vegas de los ríos tras las inundaciones anuales.\n\nEl contacto pacífico y la posterior reducción en misiones en el siglo XX trajeron consigo epidemias que mermaron severamente su población. Hoy, la comunidad de San Miguel del Mátire es el principal centro de la cultura Moré. Aunque su idioma materno se encuentra en grave riesgo de desaparición, los ancianos y líderes de la comunidad libran una batalla constante para revitalizar su lengua, sus danzas tradicionales y su identidad, reivindicando sus derechos territoriales en una de las zonas de mayor riqueza natural del país." },
            "Cayubaba": { imagen: "images_indigena/cayubaba.jpg", texto: "Ubicados históricamente en el municipio de Exaltación, en la provincia Yacuma del departamento del Beni, los Cayubaba conforman un pueblo indígena cuya lengua se considera aislada, sin afiliación probada con otras familias lingüísticas de la región. Fueron contactados inicialmente por las misiones jesuíticas a finales del siglo XVII, congregándose en la Misión de la Exaltación de la Santa Cruz.\n\nSu territorio, caracterizado por extensas pampas inundables y bosques de galería, dictó su economía de subsistencia. Los Cayubaba desarrollaron notables habilidades agrícolas adaptadas a los ciclos de inundación, además de ser destacados ganaderos, pescadores y expertos cazadores. Su cosmovisión está profundamente entrelazada con el agua y los espíritus dueños de la naturaleza, creencias que sobrevivieron bajo el manto del catolicismo impuesto por los misioneros.\n\nEn la actualidad, la organización sociopolítica Cayubaba, estructurada alrededor de los cabildos indigenales, juega un papel vital en la cohesión comunitaria y la administración de la justicia local. Frente a la pérdida progresiva de su lengua original, la nación Cayubaba enfoca sus esfuerzos en el resguardo de su Tierra Comunitaria de Origen, la transmisión de sus prácticas artesanales y el fortalecimiento de su autogobierno en medio de las presiones del desarrollo agrícola y ganadero moderno." },
            "Movima": { imagen: "images_indigena/movima.jpg", texto: "La nación Movima, dueña de una lengua considerada aislada por los lingüistas, habita en las extensas llanuras y sabanas del municipio de Santa Ana del Yacuma, en el corazón geográfico del departamento del Beni. Históricamente, fueron un pueblo de pescadores, agricultores y expertos navegantes que dominaron las intrincadas redes hídricas de los ríos Yacuma, Rapulo, Apere y Mamoré.\n\nEl encuentro con las misiones jesuíticas reconfiguró su modo de asentamiento y su economía, introduciendo la ganadería, que hoy en día, junto a la agricultura a pequeña escala y la pesca, constituye el sustento principal de las familias movimas. A pesar del fuerte proceso de asimilación cultural y mestizaje, la identidad Movima se mantiene vigorosa a través de su rica tradición oral, sus artesanías en hilo y fibra vegetal, y su emblemático Cabildo Indigenal, que conserva la autoridad moral sobre la comunidad.\n\nEn tiempos recientes, los Movimas han logrado importantes avances en el reconocimiento de sus derechos territoriales y políticos. La revitalización de su idioma, la protección de los recursos pesqueros y maderables de su TCO contra la explotación indiscriminada y la promoción de su cultura ancestral son los frentes de batalla actuales de este antiguo y orgulloso pueblo amazónico." },
            "Quechua": { imagen: "images_indigena/quechua.jpg", texto: "Aunque mundialmente reconocidos como la gran civilización de los Andes, la presencia del pueblo Quechua en las tierras bajas y la Amazonía boliviana es un fenómeno demográfico, histórico y cultural de gran importancia. A través de procesos de migración histórica y contemporánea impulsados por la búsqueda de tierras cultivables y mejores oportunidades, miles de quechuas se han asentado en regiones del trópico de Cochabamba, el norte de La Paz, Santa Cruz, Pando y Beni.\n\nEn estos nuevos ecosistemas tropicales y subtropicales, los migrantes quechuas han tenido que adaptar profundamente sus técnicas agrícolas ancestrales, pasando del cultivo de papa y quinua en terrazas andinas a la producción de arroz, plátano, cítricos, cacao y, fundamentalmente, la hoja de coca en el Chapare y los Yungas. Esta adaptación ha forjado una identidad 'quechua tropical', que fusiona el profundo sentido de comunidad (el ayni y la minka) con las dinámicas de las zonas de colonización.\n\nA pesar del drástico cambio de paisaje, conservan con fuerza su idioma milenario, sus complejas estructuras sindicales agrarias, su música, danzas y festividades religiosas, aportando una invaluable diversidad cultural a la Amazonía y los llanos orientales, y convirtiéndose en un motor productivo y político crucial en el Estado Plurinacional." },
            "Maropa": { imagen: "images_indigena/maropa.jpg", texto: "El pueblo Maropa, también conocido históricamente como Reyesano, pertenece a la familia lingüística Tacana y se ubica principalmente en el municipio de Reyes, en la provincia José Ballivián del departamento del Beni. Fueron reducidos por los jesuitas en la Misión de Los Santos Reyes a principios del siglo XVIII, un evento que marcó el inicio de un fuerte proceso de asimilación cultural y mestizaje.\n\nLos Maropa desarrollaron una economía mixta que combinó su conocimiento ancestral de la selva con las prácticas introducidas por los misioneros. Hoy en día, son hábiles agricultores, ganaderos de pequeña escala, pescadores y artesanos, destacándose en el trabajo de tallado en madera, talabartería y el tejido de hamacas. Su vida social y religiosa es un sincretismo entre sus mitos de los 'dueños del bosque' y las celebraciones católicas, siendo la fiesta de los Santos Reyes su principal manifestación cultural.\n\nFrente al avance de grandes haciendas ganaderas y la explotación maderera en su territorio tradicional, el pueblo Maropa ha fortalecido su organización para la defensa de su Tierra Comunitaria de Origen. En un esfuerzo por revertir el riesgo de extinción de su idioma, impulsan programas de educación bilingüe e intercultural, asegurando que las raíces de su identidad sigan vivas en las sabanas benianas." },
            "Leco": { imagen: "images_indigena/leco.jpg", texto: "Los Lecos son un pueblo originario de las estribaciones andinas y la Amazonía paceña, asentados principalmente en las provincias de Larecaja y Franz Tamayo, en los municipios de Guanay, Mapiri y Apolo. Tienen una lengua propia (el leko o lapalapa), considerada aislada, que hoy se encuentra en un estado crítico de preservación, con pocos hablantes de edad avanzada.\n\nHistóricamente, los Lecos fueron reconocidos por su increíble destreza para navegar los torrentosos ríos de la región (el Mapiri, Kaka, Tipuani) utilizando balsas construidas con pericia artesanal. Actuaron como los principales nexos comerciales entre las tierras altas andinas y la cuenca amazónica. Hoy en día, su economía se basa en la agricultura tropical (arroz, plátano, cacao), la pesca, la caza y la extracción aurífera a pequeña escala, una actividad tradicional que ahora enfrenta los retos ambientales de la minería mecanizada moderna.\n\nTras décadas de marginación y pérdida de identidad frente a la fuerte influencia de la cultura andina migrante, los Lecos han experimentado un vigoroso proceso de reconstitución cultural y organizativa desde finales del siglo XX. Han consolidado sus organizaciones originarias (como la PILCOL) y han asegurado legalmente sus territorios, demostrando que son los legítimos herederos y protectores de los frágiles ecosistemas del piedemonte andino-amazónico." },
            "Afro-Boliviano": { imagen: "images_indigena/afroboliviano.jpg", texto: "El pueblo Afroboliviano es una de las comunidades con mayor riqueza histórica y cultural de Bolivia, concentrado tradicionalmente en la región subtropical de los Yungas de La Paz (Coroico, Chulumani, Irupana). Son descendientes de africanos traídos en condición de esclavitud durante la época colonial para trabajar inicialmente en las minas de plata de Potosí y, posteriormente, trasladados a los Yungas para trabajar en las grandes haciendas cocaleras y agrícolas.\n\nA pesar del desarraigo y la opresión secular, los afrobolivianos supieron resistir, fusionando fragmentos de su memoria africana con la cultura andina para forjar una identidad única. Su aporte a la cultura boliviana es incalculable, siendo la danza y la música de la Saya —con el rítmico repique de los tambores y cantos de coplas— su máxima expresión y un símbolo de resistencia, rebeldía e identidad.\n\nHoy en día, dedicados en su mayoría al cultivo de la hoja de coca, cítricos y café, el pueblo afroboliviano ha logrado un reconocimiento histórico en la Constitución del Estado Plurinacional. Han pasado de la invisibilidad a tener representación política y protección legal de sus derechos, aunque continúan la lucha contra la discriminación racial y por la mejora integral de sus condiciones de vida y educación en sus comunidades." },
            "Mosetén": { imagen: "images_indigena/moseten.jpg", texto: "Los Mosetenes son un pueblo indígena de lengua no clasificada definitivamente, aunque emparentada con la lengua de los Chimanes, que habita en las ricas tierras de transición entre los Andes y la Amazonía, en los municipios de Palos Blancos (La Paz) y San Borja (Beni). Las comunidades más representativas, como Covendo y Santa Ana, fueron establecidas como misiones por los franciscanos en el siglo XIX.\n\nTradicionalmente, la economía mosetén era seminómada, dependiendo de la caza, la pesca en ríos pedregosos y la recolección. Tras la sedentarización, se convirtieron en hábiles agricultores de rozas, desarrollando complejos sistemas agroforestales donde cultivan yuca, maíz, plátano, cacao y una gran variedad de frutales, demostrando un uso exquisitamente sostenible de los suelos del bosque húmedo preandino. Las mujeres mosetenes son reconocidas por su habilidad en la tejeduría de algodón y la elaboración de prendas y accesorios tradicionales.\n\nEn las últimas décadas, el territorio mosetén ha estado sometido a inmensas presiones por la colonización andina, la explotación maderera y la prospección de hidrocarburos. Ante esto, cuentan con una sólida organización intercomunal (la OPIM) con la cual gestionan y defienden su Tierra Comunitaria de Origen, apostando por proyectos de desarrollo sustentable que protejan sus bosques y aseguren el bienestar de su población." },
            "Aymara": { imagen: "images_indigena/aymara.jpg", texto: "El pueblo Aymara es una de las civilizaciones precolombinas más antiguas y demográficamente significativas de Sudamérica, originaria del duro y elevado ecosistema del Altiplano andino. Sin embargo, al igual que los quechuas, las dinámicas de migración interna, la necesidad de nuevas tierras cultivables y los programas de colonización estatal han impulsado una notable presencia aymara en los llanos, valles subtropicales y la Amazonía boliviana.\n\nAl descender de los Andes hacia tierras bajas como el norte de La Paz, Pando y zonas cruceñas, los migrantes aymaras han llevado consigo su inquebrantable ética de trabajo, su profunda cohesión comunitaria y su capacidad de adaptación. Han transformado el paisaje amazónico integrándose a economías basadas en el cultivo de frutas tropicales, café, cacao, y la recolección de castaña en el extremo norte, fusionando su cosmovisión de respeto a la Pachamama con los nuevos espíritus de la selva.\n\nLa presencia aymara en la Amazonía es un crisol donde se teje la Bolivia contemporánea. Mantienen vivos su idioma, rituales agrarios, tejidos y festividades cívico-religiosas, organizándose en fuertes federaciones y sindicatos que defienden sus derechos a la tierra y al desarrollo productivo, siendo hoy actores fundamentales en la economía y la geopolítica de las tierras bajas." },
            "Chimán": { imagen: "images_indigena/chiman.jpg", texto: "El pueblo Chimán (o Tsimané), poseedor de una lengua aislada emparentada con la mosetén, es una de las culturas más fascinantes y demográficamente vitales de la Amazonía boliviana. Habitan un vasto territorio boscoso y fluvial en los municipios de San Borja, San Ignacio de Moxos y Rurrenabaque, en el departamento del Beni, extendiéndose a lo largo de la cuenca del río Maniqui.\n\nLos chimanes han mantenido una notable independencia cultural y un estilo de vida que privilegia la dispersión en pequeños asentamientos familiares a lo largo de los ríos. Son maestros del bosque: excelentes cazadores, pescadores con arco y flecha y técnicas vegetales (barbasco), y agricultores itinerantes de subsistencia. Tienen un conocimiento enciclopédico de la selva, del uso de plantas medicinales y de la fibra de jatata, con la cual tejen impresionantes paneles para techos que son altamente comercializados en la región.\n\nA pesar del creciente contacto con misiones evangélicas, madereros y colonizadores, su idioma se mantiene fuerte y vibrante. La organización sociopolítica del Gran Consejo Tsimané ha sido clave para lograr el reconocimiento de su Tierra Comunitaria de Origen, enfrentando los retos de proteger sus recursos naturales de la explotación ilegal, exigiendo respeto a su forma tradicional de vida y acceso a salud y educación intercultural." },
            "Joaquiniano": { imagen: "images_indigena/joaquiniano.jpg", texto: "El pueblo Joaquiniano, originario de las pampas y bosques del municipio de San Joaquín, en el departamento del Beni, está históricamente emparentado con la extensa familia lingüística Baure. Fueron concentrados por los misioneros jesuitas a principios del siglo XVIII, un proceso que consolidó su identidad actual, marcada por una profunda influencia religiosa sincrética.\n\nSu hábitat, surcado por los ríos Machupo y Blanco, define una economía basada fuertemente en el aprovechamiento de los recursos acuáticos. Son pescadores diestros, complementando su dieta con la caza de monte, la agricultura en pequeñas islas de bosque y la ganadería familiar. La cultura joaquiniana es rica en manifestaciones artísticas, destacando la elaboración de sombreros y artesanías de paja jipijapa, además de sus vibrantes fiestas patronales, donde las danzas tradicionales como los 'macheteros' joaquinianos despliegan todo su esplendor.\n\nTras el declive que supusieron las enfermedades y la explotación cauchera, los joaquinianos han protagonizado un firme resurgimiento. Organizados en la Central Indígena de la Región Amazónica de Bolivia (CIRABO), luchan por la preservación de su entorno natural frente a la deforestación y la contaminación de los ríos, y promueven la recuperación de su lengua materna y sus tradiciones para reafirmar su lugar en el abanico de las culturas amazónicas." },
            "Itonama": { imagen: "images_indigena/itonama.jpg", texto: "Los Itonamas, cuya lengua se considera aislada, son un pueblo indígena ancestral del departamento del Beni, con centro neurálgico en el municipio de Magdalena, a orillas del río Itonamas y cerca de la laguna La Bella. En la época precolombina, formaron parte de las grandes sociedades que construyeron asombrosos sistemas de lomas, terraplenes y canales agrícolas en los llanos de Moxos.\n\nFueron reunidos en las misiones jesuíticas a partir de 1704, lo que alteró radicalmente su cosmovisión y los introdujo en el sistema ganadero y textil colonial. En la actualidad, su economía descansa sobre la agricultura de autoconsumo (yuca, plátano, arroz), la pesca y el trabajo asalariado en las estancias ganaderas de la región. Son reconocidos por su habilidad en la música, siendo herederos de las orquestas barrocas misionales, y por sus ricas artesanías en algodón e hilos finos.\n\nSi bien la asimilación cultural y el mestizaje han sido intensos, provocando que su idioma originario esté en peligro inminente de extinción, el pueblo Itonama conserva un profundo sentido de pertenencia. Su Cabildo Indigenal sigue siendo la autoridad moral y organizativa fundamental que guía la defensa de sus derechos territoriales y la lucha por mantener vivas las raíces de su milenaria identidad frente a la homogeneización moderna." },
            "Baure": { imagen: "images_indigena/baure.jpg", texto: "El pueblo Baure, perteneciente a la gran familia sociolingüística Arawak, es uno de los grupos indígenas de mayor riqueza histórica de la Amazonía boliviana. Habitan en el municipio de Baures, provincia Iténez del Beni. Los investigadores los señalan como los herederos directos de las sofisticadas Culturas Hidráulicas prehispánicas de Moxos, habiendo construido inmensas redes de lomas artificiales, canales y terraplenes que asombran a la arqueología moderna.\n\nA la llegada de los jesuitas en el siglo XVII, los Baures constituían una sociedad compleja, con líderes reconocidos, cacicazgos estructurados y pueblos bien ordenados y amurallados. Su reducción en misiones introdujo el ganado, que hoy, junto con la pesca, la recolección del codiciado cacao silvestre (considerado de los mejores del mundo) y la agricultura, forman su base económica. Las mujeres baures destacan por el tejido de hamacas de algodón crudo de altísima calidad.\n\nHoy en día, aunque su población y su idioma nativo sufrieron mermas por la fiebre del caucho y la colonización posterior, la identidad Baure se reaviva con fuerza. Mediante su organización comunal, protegen sus ricos bosques del extractivismo maderero, fomentan el aprovechamiento sostenible de sus recursos silvestres y reclaman orgullosamente su legado como los grandes ingenieros y señores ancestrales de las pampas benianas." },
            "Canichana": { imagen: "images_indigena/canichana.jpg", texto: "El pueblo Canichana, portador de una lengua considerada aislada e independiente, tiene como centro histórico el municipio de San Pedro Nuevo, en la provincia Cercado del departamento del Beni. Antiguamente, los relatos históricos los describían como guerreros indomables y temibles navegantes del río Mamoré, lo que dificultó durante largo tiempo los intentos de contacto de los misioneros.\n\nFinalmente reducidos en la Misión de San Pedro a fines del siglo XVII, la historia de los Canichana estuvo marcada por una paulatina pérdida territorial debido al avance de los hacendados ganaderos tras la expulsión de los jesuitas. Actualmente, su supervivencia económica depende de la agricultura de roza y quema (cultivo de yuca, fréjol y plátano), la pesca ribereña y, en gran medida, del trabajo estacional remunerado en estancias ganaderas y aserraderos de la región.\n\nEl impacto del mestizaje y la desestructuración territorial ha colocado a la lengua y cultura Canichana en un alto nivel de vulnerabilidad. No obstante, en las últimas décadas, el pueblo ha iniciado un fuerte proceso de reconstitución organizativa mediante su Subcentral Indígena. Su lucha se enfoca en el saneamiento y titulación de sus tierras ancestrales, la recuperación de la memoria de sus ancianos y el desarrollo de alternativas de vida digna en su territorio de origen." },
            "Sirionó": { imagen: "images_indigena/siriono.jpg", texto: "Los Sirionó, autodenominados 'Mbia' (el pueblo, la gente), son una nación indígena de la familia lingüística Tupí-Guaraní que habita en las zonas boscosas de los municipios de San Javier y Trinidad, principalmente en la comunidad de Ibiato (El Cerro), en el departamento del Beni. Tradicionalmente fueron uno de los pueblos cazadores-recolectores nómadas más puros de la región, desarrollando habilidades excepcionales para sobrevivir y moverse en el denso bosque amazónico utilizando arcos y flechas de hasta dos metros de largo.\n\nEl brutal avance de la frontera agrícola, la explotación cauchera y las misiones evangelizadoras del siglo XX alteraron drásticamente su modo de vida, obligándolos a sedentarizarse y empujándolos al borde de la extinción. Hoy, aunque han adoptado la agricultura de subsistencia y la ganadería menor, mantienen un profundo lazo espiritual y material con el monte, siendo la caza y la recolección de miel silvestre actividades de gran valor identitario.\n\nEl pueblo Sirionó ha demostrado una inmensa capacidad de resiliencia. Han consolidado su Tierra Comunitaria de Origen tras históricas movilizaciones como la Marcha por el Territorio y la Dignidad de 1990. Sus líderes continúan peleando contra la invasión de madereros ilegales, buscando preservar su territorio, su idioma, que aún hablan las generaciones mayores, y su inigualable herencia como amos de la selva espesa." },
            "Guarasugwe": { imagen: "images_indigena/guarasugwe.jpg", texto: "La nación Guarasugwe (o Pauserna) representa uno de los capítulos más dramáticos de la historia amazónica boliviana, habiendo sido llevados al borde mismo de la extinción. Pertenecientes a la rama Tupí-Guaraní, históricamente ocuparon el extenso, remoto y tupido bosque de galería entre los ríos Iténez y Paraguá, en los departamentos de Santa Cruz y Beni, a lo largo de la frontera con Brasil.\n\nEran conocidos como un pueblo pacífico, profundamente espiritual, que basaba su vida en la caza con arcos perfeccionados, la pesca y la agricultura migratoria. Su colapso demográfico se debió al choque violento con frentes colonizadores, enfermedades introducidas, la despiadada época del caucho y su sistemático exterminio por parte de intereses extractivistas a principios y mediados del siglo XX.\n\nPor muchos años fueron considerados una etnia extinta. Sin embargo, pequeños grupos de descendientes que sobrevivieron camuflados entre otras poblaciones o en la profundidad del bosque han resurgido recientemente, reivindicando su identidad y su lengua. Hoy, el puñado de Guarasugwes sobrevivientes lucha contra el olvido, intentando reconstituir su comunidad, rescatando la memoria oral de sus ancestros y reclamando su derecho a existir en la tierra donde sus antepasados reinaron en perfecta armonía con la naturaleza." },
            "Moxeño": { imagen: "images_indigena/moxeno.jpg", texto: "El pueblo Moxeño (Mojeño), compuesto principalmente por las variantes Trinitario e Ignaciano, es la nación indígena más populosa e influyente de las llanuras del departamento del Beni. De matriz lingüística Arawak, son los herederos directos de las monumentales Culturas Hidráulicas prehispánicas, célebres por transformar el paisaje amazónico con cientos de miles de camellones agrícolas, lomas artificiales y canales para domesticar las inundaciones.\n\nLa llegada de las misiones jesuíticas a finales del siglo XVII fusionó su talento milenario con la ganadería, la música barroca y el catolicismo, forjando un sincretismo cultural de belleza incomparable. El Gran Cabildo Indigenal, la fastuosa danza de Los Macheteros, las habilidades excepcionales en la ebanistería y el tejido de algodón, además de las orquestas de bajones y violines, son el vivo reflejo de esta identidad vibrante. La agricultura, la ganadería a pequeña escala y el trabajo asalariado sustentan su economía actual.\n\nHistóricamente despojados de sus tierras tras la época misional, los Moxeños fueron los impulsores y líderes de las grandes marchas indígenas contemporáneas que transformaron la política del país (como la de 1990 y 2011). Hoy, desde bastiones como el Territorio Indígena Parque Nacional Isiboro Sécure (TIPNIS) y el Territorio Indígena Multiétnico (TIM), continúan su firme lucha por la autonomía, la defensa de la naturaleza frente a megaproyectos y la afirmación de su innegable señorío sobre el corazón de las pampas benianas." },
            "Yuracaré": { imagen: "images_indigena/yuracare.jpg", texto: "El pueblo Yuracaré, cuya lengua se considera aislada pero de gran riqueza sonora, habita en las estribaciones orientales de los Andes y la vasta llanura amazónica, abarcando los departamentos de Cochabamba y el Beni. Su territorio ancestral se sitúa principalmente en la cuenca del río Chapare, el río Securé y el Isiboro, compartiendo históricamente espacios con la nación Moxeña en el emblemático TIPNIS (Territorio Indígena Parque Nacional Isiboro Sécure).\n\nLos Yuracaré se han adaptado brillantemente al entorno ribereño y boscoso, siendo eximios cazadores, pescadores y navegantes. Su economía tradicional se complementa con la agricultura de subsistencia en las vegas de los ríos, donde cultivan yuca, maíz y plátano. Poseen una profunda cosmovisión animista, donde el bosque está habitado por seres tutelares con los que interactúan a través de sus conocimientos chamánicos y etnobotánicos.\n\nA lo largo de los años, su territorio ha sufrido la fuerte presión de la colonización agrícola, la expansión del cultivo de coca y la deforestación maderera. Ante esto, la nación Yuracaré se ha organizado vigorosamente en consejos indígenas para defender la integridad de sus tierras comunitarias, exigir el respeto a su forma de vida y garantizar que el desarrollo en la región no destruya el bosque que les da sustento material e identidad espiritual." },
            "Guarayo": { imagen: "images_indigena/guarayo.jpg", texto: "La nación Guaraya, integrante de la gran familia lingüística Tupí-Guaraní, habita en el corazón de la provincia Guarayos, en el departamento de Santa Cruz, con su epicentro cultural en el municipio de Ascensión de Guarayos. Según sus relatos míticos y la evidencia histórica, emigraron desde las llanuras del Chaco y Paraguay buscando la 'Tierra Sin Mal', asentándose en esta zona de transición entre la Chiquitanía y la Amazonía.\n\nFueron agrupados en reducciones por las misiones franciscanas en el siglo XIX, lo cual introdujo el catolicismo, pero no apagó su profunda herencia guaranítica ni su pasión musical. Hoy son célebres a nivel internacional por su extraordinario talento para la música barroca y renacentista, la interpretación de violines elaborados artesanalmente y la talla en madera de misiones. Su economía se basa en la agricultura (arroz, plátano, piña), la ganadería y el aprovechamiento forestal, destacando la extracción del aceite de la palmera de cusi.\n\nFrente al avance expansivo de la frontera agrícola mecanizada, los ganaderos y los asentamientos de colonizadores, el pueblo Guarayo libra una constante batalla legal y social para preservar sus Tierras Comunitarias de Origen. Su objetivo es asegurar que la riqueza de sus bosques, vital para su economía y espiritualidad, sea administrada por y para sus legítimos dueños ancestrales." },
            "Uru": { imagen: "images_indigena/uru.jpg", texto: "Los Urus (Uru-Chipayas y Uru-Muratos), conocidos ancestralmente como los 'hombres del agua' o 'qhas qut suñi', son una de las culturas más antiguas e impresionantes del continente, originarios del agreste Altiplano boliviano. Aunque tradicionalmente asociados a las cuencas de los lagos Poopó, Uru Uru y el lago Titicaca en Oruro y La Paz, algunos grupos buscaron históricamente refugios y adaptaciones a lo largo del territorio nacional huyendo del asedio de grandes imperios como el inca, el español y posteriormente, del avasallamiento de sus tierras.\n\nSu vida dependió durante milenios del ecosistema lacustre: la caza de aves acuáticas, la recolección de huevos, la pesca y, sobre todo, el magistral uso de la totora para construir balsas, viviendas y artesanías. Son los señores originarios de las aguas, poseedores de una lengua propia (el uru-chipaya) y una organización social pacífica y resistente.\n\nLa desecación de sus lagos tradicionales debido a los desvíos hídricos y el cambio climático han puesto a los Urus en una situación de emergencia extrema, forzando a muchos a migrar. Quienes llegan a zonas más cálidas o bajas llevan consigo la inquebrantable resiliencia de un pueblo que sobrevivió a los glaciares y a la historia, luchando hoy para que su milenaria voz, sus saberes hidrológicos y su identidad no desaparezcan frente a la catástrofe ambiental." },
            "Yuqui": { imagen: "images_indigena/yuqui.jpg", texto: "El pueblo Yuqui es uno de los últimos grupos indígenas en Bolivia en establecer contacto sostenido con la sociedad nacional. Pertenecientes a la familia lingüística Tupí-Guaraní, habitaron durante siglos en estado de aislamiento nómada en los densos y húmedos bosques del trópico de Cochabamba, moviéndose a lo largo de los ríos Chimoré e Ichilo.\n\nEran cazadores-recolectores de gran habilidad, utilizando arcos y flechas inusualmente grandes, y dependiendo totalmente de la provisión de la selva. El contacto definitivo y forzado, mediado por misiones evangelizadoras entre las décadas de 1960 y 1980, fue devastador. La sedentarización abrupta en campamentos, la pérdida de su territorio tradicional y la introducción de enfermedades como la tuberculosis y las afecciones respiratorias diezmaron drásticamente su población, colocándolos al borde de la desaparición física.\n\nHoy en día, confinados a su Tierra Comunitaria de Origen y rodeados por la inmensa presión de los cultivos de coca, madereros ilegales y colonizadores, el pueblo Yuqui atraviesa una situación de alta vulnerabilidad sanitaria y alimentaria. A pesar de esto, con el apoyo de organizaciones aliadas, luchan por superar los embates epidemiológicos, recuperar el equilibrio de sus recursos naturales y mantener el orgullo de ser los verdaderos hijos y sobrevivientes de la indomable selva del Chapare." },
            "Chiquitano": { imagen: "images_indigena/chiquitano.jpg", texto: "La nación Chiquitana (o Monkóx) es el pueblo indígena más numeroso y extenso del Oriente boliviano, abarcando las inmensas llanuras y serranías de las provincias Chiquitos, Velasco, Ñuflo de Chávez y Ángel Sandoval, en el departamento de Santa Cruz. Surgieron como una identidad cohesionada a partir del complejo amalgamiento de medio centenar de grupos étnicos distintos que fueron congregados en las misiones jesuíticas de Chiquitos desde finales del siglo XVII.\n\nEsta síntesis misional forjó una cultura asombrosamente rica, donde la agricultura tropical de roza y quema, la música barroca interpretada por luthieres y coros locales, la talla exquisita en madera y una profunda fe sincrética cristiana-animista definen su cotidianeidad. Su idioma (besiro), su vistosa vestimenta tradicional de tipoy y sus autoridades morales y religiosas (los Cabildos Indigenales) son el corazón de su identidad.\n\nA lo largo de la historia post-jesuítica, sufrieron el despojo territorial, la esclavitud en las barracas caucheras y la explotación hacendaria. Sin embargo, hoy son una fuerza política y social formidable en la región. Lideran la defensa de los frágiles ecosistemas del Bosque Seco Chiquitano contra los mega-incendios forestales y el avasallamiento de colonos y empresas agroindustriales, demostrando que son los genuinos guardianes del patrimonio natural y cultural del Oriente boliviano." },
            "Weenhayek": { imagen: "images_indigena/weenhayek.jpg", texto: "El pueblo Weenhayek (o Mataco), perteneciente a la familia lingüística Mataco-Noctenes, es un pilar fundamental de las culturas del Gran Chaco boliviano. Se asientan principalmente en los márgenes del río Pilcomayo, en la provincia Gran Chaco del departamento de Tarija, cerca de las poblaciones de Villamontes y Yacuiba.\n\nLa vida, la economía, la espiritualidad y la historia de los Weenhayek están indivisiblemente ligadas al río Pilcomayo. Tradicionalmente fueron nómadas cazadores-recolectores, pero la pesca del sábalo y el dorado durante las épocas de migración de los peces constituye la base de su subsistencia material y comercial. Son un pueblo profundamente reflexivo, cuyas mujeres destacan por su arte inigualable en el tejido de coloridas redes, bolsas y vestimentas a partir de la fibra vegetal del caraguatá.\n\nEn las últimas décadas, han enfrentado crisis existenciales debido a la drástica disminución del caudal y los peces del Pilcomayo, originada por la contaminación minera y represas en las cabeceras del río. Pese a las sequías, la expansión hidrocarburífera y la pobreza que los azota, la nación Weenhayek se mantiene firme, apelando a su sabiduría ancestral, defendiendo sus territorios a través de la ORCAWETA y alzando su voz para exigir la protección urgente de su río y su hábitat chaqueño." },
            "Guaraní": { imagen: "images_indigena/guarani.jpg", texto: "La inmensa y orgullosa nación Guaraní de Bolivia, de la familia lingüística Tupí-Guaraní, habita una vasta región que abarca los departamentos de Santa Cruz, Chuquisaca y Tarija, en la zona de transición entre el Chaco, los valles y los llanos orientales. Famosos históricamente por su bravura indomable, los 'Chiriguanos' resistieron militarmente los intentos de conquista del Imperio Inca y de la corona española durante más de tres siglos.\n\nLa base de su sociedad es profundamente igualitaria y solidaria, estructurada en comunidades al mando de Capitanes (Mburuvichas). Su economía histórica combinaba la guerra, la agricultura del maíz (esencial para la elaboración de su chicha ritual o 'kagüi') y la caza. La devastadora derrota militar en la Batalla de Kuruyuki (1892) marcó el inicio del despojo de sus tierras y un periodo de explotación bajo el sistema de semi-esclavitud del 'empatronamiento' en las grandes haciendas del sur del país.\n\nContra todo pronóstico, el pueblo Guaraní protagonizó un renacimiento político y cultural espectacular a partir de la creación de la Asamblea del Pueblo Guaraní (APG) en 1987. Han logrado la erradicación del empatronamiento, la titulación de enormes Tierras Comunitarias de Origen, la implementación pionera de la educación intercultural bilingüe y hoy son una voz poderosa frente a la explotación de reservas de gas y petróleo que se encuentran en el subsuelo de sus territorios sagrados." },
            "Ayoreo": { imagen: "images_indigena/ayoreo.jpg", texto: "El pueblo Ayoreo (Zamuco) es originario de los áridos e inmensos matorrales espinosos del Chaco Boreal, extendiéndose entre el sudeste de Bolivia (departamento de Santa Cruz) y el norte de Paraguay. Fueron uno de los últimos grupos indígenas en América del Sur en ser contactados y sedentarizados forzosamente por la sociedad moderna, un proceso impulsado principalmente por misiones fundamentalistas a partir de la década de 1940.\n\nAntes del contacto, vivían como nómadas cazadores-recolectores, perfectamente adaptados a un ambiente de extrema escasez de agua. Se organizaban en pequeños clanes familiares, guiados por el chamán y el jefe guerrero, donde el coraje y la resistencia física eran virtudes máximas. Las mujeres ayoreas son eximias artesanas, creando magníficas bolsas (pesas) tejidas con fibra de garabatá, teñidas con colores vibrantes extraídos de la naturaleza.\n\nEl brusco paso del nomadismo selvático a los asentamientos fijos (como Puesto Paz o la comunidad Degüi en Santa Cruz) provocó un choque cultural devastador, trayendo marginación, epidemias, discriminación urbana y pobreza. Pese a estas adversidades, los Ayoreos modernos luchan por visibilizarse con dignidad, exigiendo derechos de salud, educación y la protección de los grupos aislados no contactados que aún deambulan libres por el bosque chaqueño defendiendo su aislamiento voluntario." },
            "Tapiete": { imagen: "images_indigena/tapiete.jpg", texto: "El pueblo Tapiete es uno de los grupos originarios más pequeños y menos conocidos de Bolivia. Habitan en las llanuras áridas del Chaco tarijeño, específicamente en el municipio de Villamontes, a orillas del río Pilcomayo. Aunque comparten muchas características culturales y de subsistencia con los pueblos chaqueños vecinos, pertenecen lingüísticamente a la familia Tupí-Guaraní.\n\nHistóricamente, los Tapietes fueron guerreros y cazadores-recolectores seminómadas, que transitaban las fronteras naturales entre Bolivia, Paraguay y Argentina. La brutal Guerra del Chaco (1932-1935) fracturó su territorio y diezmó a su población, forzándolos a dispersarse y reubicarse en diversas misiones y estancias para sobrevivir. Hoy en día, su economía depende fuertemente de la pesca en el Pilcomayo, el trabajo asalariado en fundos agrícolas vecinos y una agricultura de supervivencia.\n\nEnfrentados a la dramática reducción del caudal del río Pilcomayo y las extremas sequías del Chaco, la existencia de los Tapietes es un acto diario de resistencia. A través de la Organización de Capitanías Tapietes (ORCAWETA), trabajan incansablemente para asegurar el título de sus reducidas tierras, preservar su lengua materna (severamente amenazada por el predominio del castellano y el guaraní) y mejorar el acceso a servicios básicos que garanticen el futuro de sus hijos en el seco, pero amado, monte chaqueño." },
            "Yaminahua": { imagen: "images_indigena/yaminahua.jpg", texto: "Los Yaminahua (la 'gente del hacha' en lengua pano) son un pueblo amazónico que habita las riberas del río Acre, en la frontera tripartita entre Bolivia, Brasil y Perú, asentándose en Bolivia principalmente en el departamento de Pando. Su historia pre-contacto estaba definida por continuas migraciones, la vida en grandes malocas y la subsistencia basada en la recolección, caza, pesca y agricultura de roza.\n\nLa brutal penetración de las empresas gomeras (caucheras) a finales del siglo XIX fue catastrófica. Fueron masacrados, desplazados y forzados a la esclavitud laboral, provocando una drástica disminución demográfica y la fragmentación de su estructura social. Posteriormente, la acción de los madereros, buscadores de oro y los misioneros consolidó su sedentarización en comunidades ribereñas a mediados del siglo XX.\n\nEn la actualidad, los Yaminahua son comunidades pequeñas pero resilientes. Mantienen vivas sus tradiciones chamánicas (el uso de la ayahuasca), sus complejos cantos curativos y sus diseños geométricos faciales y corporales. Su lucha diaria radica en el reconocimiento efectivo de sus territorios frente a la extracción ilegal de recursos, la preservación de su rica lengua pano en las nuevas generaciones y el derecho a un desarrollo que no destruya la selva de la que dependen material y espiritualmente." },
            "Machineri": { imagen: "images_indigena/machinery.jpg", texto: "El pueblo Machineri es una nación indígena de la familia lingüística Arawak que habita el extremo norte de Bolivia, fundamentalmente en el municipio de Bolpebra, departamento de Pando, así como en territorios aledaños de Brasil. Durante siglos, fueron prósperos navegantes y agricultores de las selvas surcadas por los ríos Acre y Yaco, destacando por sus complejos sistemas de creencias y rituales botánicos.\n\nAl igual que otros pueblos de la región, padecieron los embates desastrosos del auge del caucho, que desestructuró sus comunidades y los obligó a integrarse marginalmente a la economía capitalista bajo sistemas de enganche. Hoy, los Machineri son comunidades relativamente pequeñas que basan su economía en la agricultura de yuca, plátano y arroz, complementada con la caza y, primordialmente, la extracción y comercialización estacional de la castaña amazónica.\n\nEn un entorno transfronterizo donde frecuentemente interactúan con poblaciones peruanas y brasileñas, los Machineri afrontan el enorme desafío de preservar su identidad cultural Arawak frente a la fuerte asimilación de la cultura nacional dominante. Su organización comunitaria prioriza la protección del monte, el acceso equitativo a servicios de salud y educación intercultural, y la reafirmación del valor de sus abuelos como bibliotecas vivas de los secretos milenarios de la selva amazónica pandina." }
        };

        const mapCoords = {
            "Nahua": ["29,88,34,82,40,83,44,88,44,94,39,98,32,98,26,93"],
            "Toromona": ["50,115,58,113,63,119,63,125,59,131,50,130,48,123"],
            "Tacana": ["87,73,84,65,85,57,90,51,97,47,104,41,110,36,116,38,119,43,116,51,109,59,103,64,97,71", "62,174,57,179,59,188,63,196,71,203,77,207,84,213,91,217,99,212,96,202,99,195,100,187,95,175,88,183,82,189"],
            "Ese Ejja": ["109,85,110,76,117,68,125,64,134,61,143,57,152,59,154,65,151,72,145,79,139,84,131,89,121,89,114,88"],
            "Pacahuara": ["147,38,147,31,150,23,156,19,163,19,170,21,170,27,168,35,164,39,155,41"],
            "Araona": ["78,124,83,116,91,112,97,116,102,122,103,129,101,137,92,135,84,131"],
            "Cavineño": ["104,98,113,98,121,97,129,102,129,111,126,117,123,125,124,137,122,145,115,145,109,137,109,126,110,116,105,108"],
            "Chácobo": ["140,108,140,101,145,92,153,89,160,89,167,87,171,95,169,103,162,109,153,109,145,111"],
            "Moré": ["191,105,189,114,194,120,202,123,207,116,205,108,199,102"],
            "Cayubaba": ["148,139,144,147,148,155,158,160,165,162,169,157,171,151,163,145,157,138"],
            "Movima": ["143,160,152,163,161,165,167,168,174,174,181,179,182,190,177,199,169,208,153,212,132,207,138,196,156,191,137,166,141,162"],
            "Quechua": ["34,211,45,199,54,200,63,207,70,213,71,226,62,225,54,222,45,222,37,221", "113,336,117,325,119,313,115,298,117,288,126,292,131,296,137,303,146,313,155,319,163,313,171,304,177,299,191,291,206,286,219,284,223,292,217,296,208,308,201,315,193,325,192,338,195,350,199,356,203,364,211,369,212,381,204,382,193,368,188,360,181,350,162,339,159,346,165,355,171,365,165,366,136,352,123,350,114,348", "117,446,112,434,121,421,131,417,141,409,149,410,157,417,157,426,156,436,135,444", "170,443,172,420,177,403,173,386,177,377,185,384,190,392,198,401,203,414,205,429,205,445,195,451,182,448", "96,511,92,500,120,477,144,480,148,497,124,500,107,509", "164,496,159,484,166,472,176,466,187,467,193,477,191,484"],
            "Maropa": ["110,209,107,200,107,188,114,181,121,180,128,183,128,190,127,197,126,203,119,210"],
            "Leco": ["35,236,42,239,53,243,55,235,52,227,45,225,37,225,33,230"],
            "Afro-Boliviano": ["63,246,59,251,61,259,66,263,74,264,78,258,75,250,69,245"],
            "Mosetén": ["81,235,91,235,99,241,107,247,109,255,101,257,93,253,81,255,75,242"],
            "Aymara": ["23,242,32,242,45,251,55,263,64,274,76,280,88,287,97,297,99,307,100,314,102,323,99,330,91,331,79,328,69,324,59,319,51,320,35,327,23,325,22,312,34,298,43,290,42,276,26,253", "87,265,104,286,116,282,95,258", "79,402,84,387,81,373,74,358,73,340,77,331,87,336,93,347,97,359,103,369,111,380,122,385,123,399,114,398,103,394,95,402", "61,394,71,397,74,409,74,420,72,428,69,435,69,445,59,451,54,443"],
            "Chimán": ["91,233,93,219,105,215,113,217,127,219,132,226,132,232,127,241,119,245"],
            "Joaquiniano": ["183,173,183,162,192,159,199,161,203,168,202,175,194,177"],
            "Itonama": ["217,132,239,119,248,119,256,125,261,133,259,142,251,138,240,135,230,138,220,140"],
            "Baure": ["217,182,223,172,232,167,241,165,247,170,247,177,243,183,234,181,229,186,223,188"],
            "Canichana": ["190,214,185,207,190,199,198,199,205,203,209,210,206,216,199,214"],
            "Sirionó": ["209,203,211,194,224,198,223,208,215,210"],
            "Guarasugwe": ["312,197,309,187,315,181,325,178,335,178,341,183,346,193,343,199,331,192"],
            "Moxeño": ["148,273,138,260,145,250,154,243,165,232,177,223,187,218,196,221,204,226,213,232,221,239,225,250,216,255,208,253,196,248,165,265"],
            "Yuracaré": ["151,278,153,288,165,292,176,291,179,283,185,270,174,263,163,273"],
            "Guarayo": ["239,279,239,265,245,257,239,238,243,248,244,230,251,228,259,228,265,234,269,247,265,259,266,271,267,281"],
            "Uru": ["38,332,35,340,37,352,41,364,50,372,57,367,57,354,50,344,46,332"],
            "Yuqui": ["193,285,187,279,189,267,195,261,202,266,201,277,198,284"],
            "Chiquitano": ["276,299,268,269,274,246,284,252,296,261,309,270,326,279,349,287,370,290,387,291,402,292,417,288,431,294,438,305,439,326,449,342,458,353,461,368,461,384,459,395,449,400,407,335,391,318,364,313,341,331,329,319,315,308,293,307"],
            "Weenhayek": ["248,507,233,496,223,485,228,474,244,488,256,498,255,505"],
            "Guaraní": ["263,482,244,468,233,456,229,438,227,416,234,402,241,389,252,378,263,386,251,408,252,423,264,415,278,404,296,398,319,392,335,397,337,406,320,408,305,410,294,418,284,430,278,450,279,469,273,478"],
            "Ayoreo": ["341,370,357,370,371,372,391,375,417,378,415,389,406,392,388,397,374,396,356,392,344,386"],
            "Tapiete": ["272,505,280,499,283,491,273,487,267,495"],
            "Yaminahua": ["13,59,21,53,27,58,20,66"],
            "Machineri": ["26,63,31,57,41,64,33,72"]
        };

        let etniaActiva = null;
        let synth = window.speechSynthesis;
        let reproduciendoModal = false;

        function inicializarMapaYBotones() {
            const panel = document.getElementById('panel-botones');
            const capaBase = document.getElementById('capa-interactiva-base');
            const nombres = Object.keys(mapCoords).sort();

            nombres.forEach(nombre => {
                // 1. Crear botón en el panel lateral
                const btn = document.createElement('div');
                btn.className = 'btn-etnia';
                btn.id = `btn-${nombre.replace(/\s+/g, '-')}`;
                btn.setAttribute('data-nombre', nombre.toLowerCase());
                
                const txt = document.createElement('span');
                txt.textContent = nombre;
                btn.appendChild(txt);

                const info = document.createElement('span');
                info.className = 'clic-info';
                info.textContent = '📖 Toca aquí de nuevo para leer su historia completa';
                btn.appendChild(info);

                btn.onclick = () => procesarClic(nombre);
                panel.appendChild(btn);

                // 2. Crear polígonos interactivos permanentes sobre el mapa para poder tocarlos directamente
                mapCoords[nombre].forEach(coordsStr => {
                    const parts = coordsStr.split(',');
                    let points = "";
                    for(let i = 0; i < parts.length; i += 2) {
                        points += `${parts[i]},${parts[i+1]} `;
                    }
                    const pol = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
                    pol.setAttribute('points', points.trim());
                    pol.setAttribute('class', 'poligono-etnia');
                    pol.innerHTML = `<title>${nombre}</title>`;
                    pol.onclick = () => procesarClic(nombre);
                    capaBase.appendChild(pol);
                });
            });
        }

        function filtrarBotones(termino) {
            if (etniaActiva) return;
            const q = termino.trim().toLowerCase();
            document.querySelectorAll('.btn-etnia').forEach(btn => {
                const nombre = btn.getAttribute('data-nombre');
                btn.style.display = (q === '' || nombre.includes(q)) ? 'flex' : 'none';
            });
        }

        function procesarClic(nombre) {
            if (etniaActiva === nombre) {
                abrirLectura(nombre);
            } else {
                etniaActiva = nombre;
                activarInmersion(nombre);
            }
        }

        function activarInmersion(nombre) {
            const botones = document.querySelectorAll('.btn-etnia');
            botones.forEach(btn => {
                if(btn.id === `btn-${nombre.replace(/\s+/g, '-')}`) {
                    btn.style.display = 'flex';
                    btn.classList.add('activo');
                } else {
                    btn.style.display = 'none';
                }
            });

            document.getElementById('main-area').classList.add('modo-inmersion');
            document.getElementById('btn-volver').classList.add('visible');
            document.getElementById('instrucciones-texto').style.display = 'none';
            document.getElementById('titulo-principal').style.display = 'none';

            dibujarPoligonosYRadar(nombre);
        }

        function restaurarMapa() {
            etniaActiva = null;
            const buscador = document.getElementById('buscador-etnia');
            buscador.value = '';
            
            const botones = document.querySelectorAll('.btn-etnia');
            botones.forEach(btn => {
                btn.style.display = 'flex';
                btn.classList.remove('activo');
            });

            document.getElementById('main-area').classList.remove('modo-inmersion');
            document.getElementById('btn-volver').classList.remove('visible');
            document.getElementById('instrucciones-texto').style.display = 'block';
            document.getElementById('titulo-principal').style.display = 'block';
            
            document.getElementById('capa-radar-activa').innerHTML = ''; 
        }

        function dibujarPoligonosYRadar(nombre) {
            const capaRadar = document.getElementById('capa-radar-activa');
            capaRadar.innerHTML = ''; 

            const coordenadasArr = mapCoords[nombre];
            if (!coordenadasArr) return;

            let maxArea = 0;
            let mainCx = 0;
            let mainCy = 0;

            coordenadasArr.forEach(coordsStr => {
                const parts = coordsStr.split(',');
                let points = "";
                
                let minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity;

                for(let i = 0; i < parts.length; i += 2) {
                    let px = Number(parts[i]);
                    let py = Number(parts[i+1]);
                    points += `${px},${py} `;
                    
                    if(px < minX) minX = px;
                    if(px > maxX) maxX = px;
                    if(py < minY) minY = py;
                    if(py > maxY) maxY = py;
                }

                let cx = (minX + maxX) / 2;
                let cy = (minY + maxY) / 2;
                const areaEstimada = (maxX - minX) * (maxY - minY);

                // Calibración específica preservada de indigenas.html para centrar la flecha y el radar
                if (nombre === "Chiquitano") {
                    cy -= 23;
                }
                if (nombre === "Guaraní") {
                    cx -= 15;
                }

                if (areaEstimada > maxArea) {
                    maxArea = areaEstimada;
                    mainCx = cx;
                    mainCy = cy;
                }

                const pol = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
                pol.setAttribute('points', points.trim());
                pol.setAttribute('class', 'poligono-etnia parpadeo');
                pol.onclick = () => abrirLectura(nombre);
                capaRadar.appendChild(pol);

                const radarRed = crearCirculoRadar(cx, cy, '#ff3333', '0s');
                capaRadar.appendChild(radarRed);

                const radarGreen = crearCirculoRadar(cx, cy, '#33ff33', '1s');
                capaRadar.appendChild(radarGreen);
            });

            dibujarEtiquetaConFlecha(capaRadar, mainCx, mainCy, nombre);
        }

        function crearCirculoRadar(cx, cy, color, delay) {
            const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            circle.setAttribute('cx', cx);
            circle.setAttribute('cy', cy);
            circle.setAttribute('fill', 'none');
            circle.setAttribute('stroke', color);
            circle.setAttribute('stroke-width', '2');
            circle.setAttribute('style', `filter: drop-shadow(0 0 6px ${color}); pointer-events: none;`);

            const animR = document.createElementNS('http://www.w3.org/2000/svg', 'animate');
            animR.setAttribute('attributeName', 'r');
            animR.setAttribute('values', '0; 45');
            animR.setAttribute('dur', '2s');
            animR.setAttribute('begin', delay);
            animR.setAttribute('repeatCount', 'indefinite');

            const animOp = document.createElementNS('http://www.w3.org/2000/svg', 'animate');
            animOp.setAttribute('attributeName', 'opacity');
            animOp.setAttribute('values', '1; 0');
            animOp.setAttribute('dur', '2s');
            animOp.setAttribute('begin', delay);
            animOp.setAttribute('repeatCount', 'indefinite');

            circle.appendChild(animR);
            circle.appendChild(animOp);
            return circle;
        }

        function dibujarEtiquetaConFlecha(contenedorSvg, cx, cy, texto) {
            const textGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            textGroup.style.cursor = 'pointer';
            textGroup.style.pointerEvents = 'auto';
            textGroup.onclick = () => abrirLectura(texto);
            
            const width = texto.length * 7.5 + 26; 
            const height = 24;
            
            const boxX = 465 - width; 
            const boxY = 18; 
            
            const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            line.setAttribute('x1', cx);
            line.setAttribute('y1', cy);
            line.setAttribute('x2', boxX - 4); 
            line.setAttribute('y2', boxY + (height / 2));
            line.setAttribute('stroke', '#FFB81C');
            line.setAttribute('stroke-width', '1.6');
            line.setAttribute('marker-end', 'url(#arrow)');

            const bgRect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
            bgRect.setAttribute('x', boxX);
            bgRect.setAttribute('y', boxY);
            bgRect.setAttribute('width', width);
            bgRect.setAttribute('height', height);
            bgRect.setAttribute('fill', '#111111');
            bgRect.setAttribute('stroke', '#FFB81C');
            bgRect.setAttribute('stroke-width', '1.5');
            bgRect.setAttribute('rx', '4'); 

            const textNode = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            textNode.setAttribute('x', boxX + (width / 2));
            textNode.setAttribute('y', boxY + 16); 
            textNode.setAttribute('fill', '#FFFFFF');
            textNode.setAttribute('font-size', '12px');
            textNode.setAttribute('font-weight', 'bold');
            textNode.setAttribute('text-anchor', 'middle');
            textNode.textContent = texto;

            textGroup.appendChild(line);
            textGroup.appendChild(bgRect);
            textGroup.appendChild(textNode);
            contenedorSvg.appendChild(textGroup);
        }

        function abrirLectura(nombre) {
            const data = etniasData[nombre];
            if (!data) return;

            detenerAudioEtnia();

            document.getElementById('lectura-titulo').innerText = "Pueblo " + nombre;
            const imgEl = document.getElementById('lectura-img');
            imgEl.src = data.imagen || "images/default.jpg";
            
            let htmlContent = data.texto
                .split(/\r?\n+/) 
                .map(p => p.trim())
                .filter(p => p.length > 0)
                .map(p => `<p>${p}</p>`) 
                .join('');
                
            document.getElementById('lectura-texto').innerHTML = htmlContent;

            const modal = document.getElementById('modal-lectura');
            modal.style.display = 'flex';
            modal.scrollTop = 0;
        }

        function cerrarLectura() {
            detenerAudioEtnia();
            document.getElementById('modal-lectura').style.display = 'none';
        }

        function detenerAudioEtnia() {
            if (synth && reproduciendoModal) {
                synth.cancel();
            }
            reproduciendoModal = false;
            const btnAudio = document.getElementById('btnAudioModal');
            if (btnAudio) btnAudio.textContent = "▶ Escuchar relato";
        }

        function toggleAudioEtnia() {
            if (!synth) {
                alert("Tu navegador no soporta lectura de voz.");
                return;
            }
            const btnAudio = document.getElementById('btnAudioModal');
            if (reproduciendoModal) {
                detenerAudioEtnia();
            } else {
                const titulo = document.getElementById('lectura-titulo').innerText;
                const cuerpo = document.getElementById('lectura-texto').innerText;
                const utter = new SpeechSynthesisUtterance(titulo + ". " + cuerpo);
                utter.lang = 'es-ES';
                utter.rate = 1.0;
                utter.onend = () => detenerAudioEtnia();
                synth.speak(utter);
                reproduciendoModal = true;
                btnAudio.textContent = "❚❚ Detener audio";
            }
        }

        // Cerrar modal con tecla Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') cerrarLectura();
        });

        window.addEventListener('DOMContentLoaded', inicializarMapaYBotones);
    </script>
</body>
</html>