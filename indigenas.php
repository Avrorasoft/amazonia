<?php
// indigenas.php - Mapa Indígena Inmersivo de la Amazonía Boliviana (Optimizado y Responsivo)
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Portal Amazonía Hoy | Mapa Indígena Inmersivo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    
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
            min-height: 100vh;
            /* Soporte estricto para pantallas compactas como iPhone SE */
            height: 100vh;
            height: 100dvh;
            overflow: hidden; 
        }

        /* =========================================
           NAVEGACIÓN COMPACTA
           ========================================= */
        nav {
            flex-shrink: 0;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(10px);
            z-index: 2000;
            box-shadow: 0 4px 20px rgba(0,0,0,0.6);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 15px;
            flex-wrap: wrap;
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
            margin: 3px 0 0 0;
            font-size: 12px;
            font-style: italic;
            text-align: justify;
            text-align-last: center;
        }

        .autor-mapa {
            color: #888;
            font-size: 11px;
            margin-top: 3px;
            font-weight: bold;
        }

        #btn-volver {
            display: none;
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: #333;
            color: white;
            border: 1px solid #555;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            transition: all 0.3s;
            z-index: 100;
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
            gap: 10px;
            overflow: hidden;
            transition: all 0.5s ease;
        }

        /* =========================================
           PANEL DE BOTONES
           ========================================= */
        #panel-botones {
            flex: 0 0 320px; 
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            grid-auto-rows: max-content;
            gap: 8px;
            overflow-y: auto;
            padding-right: 5px;
            -webkit-overflow-scrolling: touch;
            transition: all 0.5s ease;
        }

        #panel-botones::-webkit-scrollbar { width: 5px; }
        #panel-botones::-webkit-scrollbar-thumb { background: #333; border-radius: 4px; }

        .btn-etnia {
            background: #1a1a1a;
            color: #ddd;
            border: 1px solid #333;
            padding: 10px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 12px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
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
            padding: 15px;
            box-shadow: 0 5px 20px rgba(255, 184, 28, 0.4);
            animation: pulse-btn 2s infinite;
        }

        .btn-etnia .clic-info {
            display: none;
            font-size: 10px;
            font-weight: normal;
            margin-top: 4px;
            opacity: 0.8;
            text-align: justify;
        }

        .btn-etnia.activo .clic-info {
            display: block; 
        }

        @keyframes pulse-btn {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }

        /* =========================================
           CONTENEDOR DEL MAPA (FLEXIBLE)
           ========================================= */
        #map-wrapper {
            flex-grow: 1; 
            position: relative;
            background: #080808;
            border-radius: 8px;
            border: 1px solid #222;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            transition: all 0.5s ease;
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
        }

        .poligono-etnia.parpadeo {
            fill: rgba(255, 184, 28, 0.4); 
            stroke: var(--accent-gold); 
            stroke-width: 2.5;
            animation: blink-map 1.5s ease-in-out infinite;
        }

        @keyframes blink-map {
            0% { opacity: 0.4; }
            50% { opacity: 1; filter: drop-shadow(0 0 12px var(--accent-gold)); }
            100% { opacity: 0.4; }
        }

        /* =========================================
           RESPONSIVE MÓVIL (ADAPTADO PARA IPHONE SE)
           ========================================= */
        @media (max-width: 850px) {
            body { height: 100dvh; }
            main { flex-direction: column; padding: 6px; gap: 6px; }
            
            #panel-botones { 
                flex: 0 0 32%; 
                grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); 
                padding-right: 0; 
            }
            #map-wrapper { flex: 0 0 68%; }

            /* Modo Inmersión Móvil compacto */
            main.modo-inmersion #panel-botones { flex: 0 0 auto; margin-bottom: 2px; max-height: 110px; }
            main.modo-inmersion #map-wrapper { flex-grow: 1; }

            header { padding: 8px 10px; }
            h1 { font-size: 14px; margin-bottom: 1px; }
            .autor-mapa { font-size: 9px; margin-top: 1px; }
            p.instrucciones { font-size: 10px; text-align: justify; }
            
            #btn-volver { 
                top: auto; 
                bottom: -4px; 
                position: relative; 
                display: inline-block; 
                margin-top: 4px; 
                left: 0; 
                transform: none; 
                width: 100%; 
                text-align: center; 
                padding: 4px; 
                font-size: 10px;
            }
            
            .btn-etnia.activo {
                font-size: 13px;
                padding: 8px;
            }
            .btn-etnia.activo .clic-info {
                font-size: 9px;
            }
        }
    </style>
</head>
<body>

    <!-- Navegación -->
    <nav>
        <a href="index.php" class="nav-brand">
            <img src="images/logo1.png" alt="Logo" style="height: 30px;" onerror="this.style.display='none'">
            AMAZONÍA
        </a>
        <div class="nav-links-container">
            <a href="index.php">Inicio</a>
            <a href="index.php#mosaico">Noticias</a>
            <a href="indigenas.php" style="color: var(--accent-gold);">Mapa Indígena</a>
        </div>
    </nav>

    <!-- Encabezado -->
    <header id="mapa-header">
        <button id="btn-volver" onclick="restaurarMapa()">← Volver al panel completo</button>
        <h1 id="titulo-principal">Naciones Originarias</h1>
        <div class="autor-mapa">Autor: Wigberto Rivero Pinto</div>
        <p class="instrucciones" id="instrucciones-texto">Selecciona una etnia para localizarla en el mapa interactivo con el sistema de radar y visualización satelital.</p>
    </header>

    <!-- Área Principal Flex -->
    <main id="main-area">
        
        <!-- Panel de Botones -->
        <div id="panel-botones"></div>

        <!-- Contenedor del Mapa Elástico -->
        <div id="map-wrapper">
            <img src="Mapa_indigena.jpg" alt="Mapa Base" id="mapa_img">
            
            <!-- Capa vectorial superpuesta para parpadeo, radar y flecha -->
            <svg id="mapa_svg" viewBox="0 0 482 550" preserveAspectRatio="xMidYMid meet">
                <defs>
                    <marker id="arrow" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                        <path d="M 0 0 L 10 5 L 0 10 z" fill="#FFB81C" />
                    </marker>
                </defs>
            </svg>
        </div>

    </main>

    <script>
        const etniasUrls = {
            "Nahua": "nahua.php",
            "Toromona": "toromona.php",
            "Tacana": "tacana.php",
            "Ese Ejja": "eseejja.php",
            "Pacahuara": "pacahuara.php",
            "Araona": "araona.php",
            "Cavineño": "cavineno.php",
            "Chácobo": "chacobo.php",
            "Moré": "more.php",
            "Cayubaba": "cayubaba.php",
            "Movima": "movima.php",
            "Quechua": "quechua.php",
            "Maropa": "maropa.php",
            "Leco": "leco.php",
            "Afro-Boliviano": "afroboliviano.php",
            "Mosetén": "moseten.php",
            "Aymara": "aymara.php",
            "Chimán": "chiman.php",
            "Joaquiniano": "joaquiniano.php",
            "Itonama": "itonama.php",
            "Baure": "baure.php",
            "Canichana": "canichana.php",
            "Sirionó": "siriono.php",
            "Guarasugwe": "guarasugwe.php",
            "Moxeño": "moxeno.php",
            "Yuracaré": "yuracare.php",
            "Guarayo": "guarayo.php",
            "Uru": "uru.php",
            "Yuqui": "yuqui.php",
            "Chiquitano": "chiquitano.php",
            "Weenhayek": "weenhayek.php",
            "Guaraní": "guarani.php",
            "Ayoreo": "ayoreo.php",
            "Tapiete": "tapiete.php",
            "Yaminahua": "yaminahua.php",
            "Machineri": "machinery.php"
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

        function inicializarBotones() {
            const panel = document.getElementById('panel-botones');
            const nombres = Object.keys(mapCoords).sort();

            nombres.forEach(nombre => {
                const btn = document.createElement('div');
                btn.className = 'btn-etnia';
                btn.id = `btn-${nombre.replace(/\s+/g, '-')}`;
                
                const txt = document.createElement('span');
                txt.textContent = nombre;
                btn.appendChild(txt);

                const info = document.createElement('span');
                info.className = 'clic-info';
                info.textContent = 'Toque de nuevo para Información Completa';
                btn.appendChild(info);

                btn.onclick = () => procesarClic(nombre);
                panel.appendChild(btn);
            });
        }

        function procesarClic(nombre) {
            if (etniaActiva === nombre) {
                const destino = etniasUrls[nombre];
                if(destino) {
                    window.location.href = destino;
                } else {
                    alert("La página para esta etnia está en construcción.");
                }
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
            document.getElementById('btn-volver').style.display = 'inline-block';
            document.getElementById('instrucciones-texto').style.display = 'none';
            document.getElementById('titulo-principal').style.display = 'none';

            dibujarPoligonosYRadar(nombre);
        }

        function restaurarMapa() {
            etniaActiva = null;
            
            const botones = document.querySelectorAll('.btn-etnia');
            botones.forEach(btn => {
                btn.style.display = 'flex';
                btn.classList.remove('activo');
            });

            document.getElementById('main-area').classList.remove('modo-inmersion');
            
            document.getElementById('btn-volver').style.display = 'none';
            document.getElementById('instrucciones-texto').style.display = 'block';
            document.getElementById('titulo-principal').style.display = 'block';
            
            const svg = document.getElementById('mapa_svg');
            const defs = svg.querySelector('defs').outerHTML;
            svg.innerHTML = defs; 
        }

        function dibujarPoligonosYRadar(nombre) {
            const svg = document.getElementById('mapa_svg');
            const defs = svg.querySelector('defs').outerHTML;
            svg.innerHTML = defs;

            const coordenadasArr = mapCoords[nombre];
            if (!coordenadasArr) return;

            let maxArea = 0;
            let mainCx = 0;
            let mainCy = 0;

            coordenadasArr.forEach(coordsStr => {
                const parts = coordsStr.split(',');
                let points = "";
                let minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity;

                for(let i=0; i < parts.length; i+=2) {
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

                if (nombre === "Chiquitano") { cy -= 23; }
                if (nombre === "Guaraní") { cx -= 15; }

                if (areaEstimada > maxArea) {
                    maxArea = areaEstimada;
                    mainCx = cx;
                    mainCy = cy;
                }

                const pol = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
                pol.setAttribute('points', points.trim());
                pol.setAttribute('class', 'poligono-etnia parpadeo');
                svg.appendChild(pol);

                const radarRed = crearCirculoRadar(cx, cy, '#ff3333', '0s');
                svg.appendChild(radarRed);

                const radarGreen = crearCirculoRadar(cx, cy, '#33ff33', '1s');
                svg.appendChild(radarGreen);
            });

            dibujarEtiquetaConFlecha(svg, mainCx, mainCy, nombre);
        }

        function crearCirculoRadar(cx, cy, color, delay) {
            const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            circle.setAttribute('cx', cx);
            circle.setAttribute('cy', cy);
            circle.setAttribute('fill', 'none');
            circle.setAttribute('stroke', color);
            circle.setAttribute('stroke-width', '2');
            circle.setAttribute('style', `filter: drop-shadow(0 0 6px ${color});`);

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

        function dibujarEtiquetaConFlecha(svg, cx, cy, texto) {
            const textGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            const width = texto.length * 7 + 24; 
            const height = 22;
            const boxX = 460 - width; 
            const boxY = 20; 
            
            const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            line.setAttribute('x1', cx);
            line.setAttribute('y1', cy);
            line.setAttribute('x2', boxX - 4); 
            line.setAttribute('y2', boxY + (height / 2));
            line.setAttribute('stroke', '#FFB81C');
            line.setAttribute('stroke-width', '1.5');
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
            textNode.setAttribute('y', boxY + 15); 
            textNode.setAttribute('fill', '#FFFFFF');
            textNode.setAttribute('font-size', '12px');
            textNode.setAttribute('font-weight', 'bold');
            textNode.setAttribute('text-anchor', 'middle');
            textNode.textContent = texto;

            textGroup.appendChild(line);
            textGroup.appendChild(bgRect);
            textGroup.appendChild(textNode);
            svg.appendChild(textGroup);
        }

        window.onload = inicializarBotones;
    </script>
</body>
</html>