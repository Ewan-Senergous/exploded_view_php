<?php
if (!function_exists('addVueEclateeTab')) {
    define('WHITESPACE_PATTERN', '/\s+/');
    
    // Copier ici toutes les fonctions des autres fichiers
    function zoomVeFunction() {
        ob_start();
        
        // Récupérer le produit actuel
        $product = wc_get_product(get_the_ID()) ?? $GLOBALS['product'];
        
        // Récupérer l'attribut cross_ref
        $cross_ref = '';
        foreach ($product->get_attributes() as $attr) {
            if (is_object($attr) && wc_attribute_label($attr->get_name()) === 'cross_ref') {
                $cross_ref = implode(', ', $attr->get_options());
                break;
            }
        }

       
        // Décoder le JSON et récupérer l'URL du SVG
        $data = json_decode(preg_replace(WHITESPACE_PATTERN, ' ', $cross_ref), true);
        $svg_url = $data['svg_url'] ?? '';
        
        if (empty($svg_url)) {
            return 'SVG URL not found';
        }
        ?>
        <style>
            .zoom-container {
                position: relative;
                overflow: hidden;
                width: 100%; /* réduit la taille du conteneur */
                max-width: 1000px; /* limite la largeur maximum */
                cursor: grab;
                background: #f5f5f5;
            }
            .image-container {
                border: 1px solid #ddd;
                width: 100%;
            }
            .zoom-image {
                width: 100%;
                height: 100%; /* occupe toute la hauteur */
                object-fit: cover; /* remplit l'espace */
                transform-origin: 0 0;
                pointer-events: none;
                display: block;
            }
            .zoom-controls {
                position: relative;
                margin-top: 10px;
                background: rgba(0,0,0,0.7);
                padding: 10px;
                border-radius: 5px;
                text-align: center;
                width: 18rem;
                display: none; /* Par défaut, tous les contrôles sont cachés */
            }
            .zoom-controls.desktop {
                display: none; /* Cache complètement les contrôles desktop sous l'image */
            }
            .zoom-controls.mobile {
                display: none; /* Caché par défaut */
            }
            @media (max-width: 768px) {
                .zoom-controls.desktop {
                    display: none !important; /* Force le masquage en mobile */
                }
                .zoom-controls.mobile {
                    display: block !important; /* Affichage uniquement en mobile */
                    margin: 10px auto;
                    width: 100%;
                }
            }
            @media (min-width: 1900px) {
                .zoom-wrapper {
                    max-width: 1400px;
                    margin: 0 auto;
                }
            }
            .zoom-button {
                background: #fff;
                border: none;
                padding: 5px 10px;
                margin: 0 3px;
                cursor: pointer;
                border-radius: 3px;
                min-width: 30px;
            }
        </style>

        <div class="zoom-wrapper">
            <div class="zoom-container" id="zoomContainer">
                <div class="image-container">
                    <img src="<?php echo esc_url($svg_url); ?>" class="zoom-image" id="zoomImage" alt="Zoomable exploded view">
                </div>
            </div>
            <!-- Uniquement les contrôles mobiles -->
            <div class="zoom-controls mobile">
                <button class="zoom-button" onclick="resetZoom()">Reset</button>
                <button class="zoom-button" onclick="zoomIn()">-</button>
                <span id="zoomLevel-mobile" style="color: white; margin: 0 10px;">100%</span>
                <button class="zoom-button" onclick="zoomOut()">+</button>
            </div>
        </div>

        <script>
            let scale = 1;
            // Modification des étapes de zoom selon le device
            const ZOOM_STEPS = window.innerWidth <= 768 ? [300] : [100, 200];
            const MAX_ZOOM = window.innerWidth <= 768 ? 3 : 2;
            const MIN_ZOOM = window.innerWidth <= 768 ? 3 : 1; // Force le zoom minimum à 600% sur mobile
            
            const container = document.getElementById('zoomContainer');
            const image = document.getElementById('zoomImage');
            let isDragging = false;
            let startX, startY, translateX = 0, translateY = 0;
            let lastX, lastY;
            let momentum = { x: 0, y: 0 };

            function updateTransform() {
                image.style.transform = `translate(${translateX}px, ${translateY}px) scale(${scale})`;
                document.getElementById('zoomLevel').textContent = Math.round(scale * 100) + '%';
                document.getElementById('zoomLevel-mobile').textContent = Math.round(scale * 100) + '%';
                
                // Dispatch un événement personnalisé avec le niveau de zoom actuel
                const zoomEvent = new CustomEvent('zoomLevelChanged', {
                    detail: { zoom: scale }
                });
                window.dispatchEvent(zoomEvent);
            }

            // Exposer scale globalement de manière sécurisée
            Object.defineProperty(window, 'currentZoomLevel', {
                get: function() {
                    return scale;
                }
            });

            function findNextZoomStep(currentScale, increase) {
                // Sur mobile, on force le zoom à 600%
                if (window.innerWidth <= 768) {
                    return 3; // 600%
                }
                
                // Sur desktop, comportement normal
                const currentPercentage = currentScale * 100;
                if (increase) {
                    for (let step of ZOOM_STEPS) {
                        if (step > currentPercentage) return step / 100;
                    }
                    return MAX_ZOOM;
                } else {
                    for (let i = ZOOM_STEPS.length - 1; i >= 0; i--) {
                        if (ZOOM_STEPS[i] < currentPercentage) return ZOOM_STEPS[i] / 100;
                    }
                    return MIN_ZOOM;
                }
            }

            function zoomToPoint(x, y, increase) {
                const rect = container.getBoundingClientRect();
                const mouseX = x - rect.left;
                const mouseY = y - rect.top;

                const oldScale = scale;
                const newScale = findNextZoomStep(oldScale, increase);
                scale = newScale;

                const scaleChange = scale / oldScale;

                translateX = mouseX - (mouseX - translateX) * scaleChange;
                translateY = mouseY - (mouseY - translateY) * scaleChange;

                updateTransform();
            }

            container.addEventListener('wheel', function(e) {
                e.preventDefault();
                zoomToPoint(e.clientX, e.clientY, e.deltaY < 0);
            });

            container.addEventListener('mousedown', function(e) {
                isDragging = true;
                startX = e.clientX - translateX;
                startY = e.clientY - translateY;
                lastX = e.clientX;
                lastY = e.clientY;
                container.style.cursor = 'grabbing';
                e.preventDefault();
            });

            // Ajouter la gestion des événements tactiles
            let touchStartX, touchStartY;
            
            container.addEventListener('touchstart', function(e) {
                if (e.touches.length === 1) {
                    isDragging = true;
                    const touch = e.touches[0];
                    startX = touch.clientX - translateX;
                    startY = touch.clientY - translateY;
                    touchStartX = touch.clientX;
                    touchStartY = touch.clientY;
                    e.preventDefault();
                }
            });

            container.addEventListener('touchmove', function(e) {
                if (!isDragging) return;
                
                const touch = e.touches[0];
                translateX = touch.clientX - startX;
                translateY = touch.clientY - startY;
                
                updateTransform();
                e.preventDefault();
            });

            container.addEventListener('touchend', function(e) {
                isDragging = false;
                e.preventDefault();
            });

            container.addEventListener('touchcancel', function(e) {
                isDragging = false;
                e.preventDefault();
            });

            // Empêcher le défilement de la page pendant le déplacement
            container.addEventListener('touchmove', function(e) {
                e.preventDefault();
            }, { passive: false });

            window.addEventListener('mousemove', function(e) {
                if (!isDragging) return;
                
                translateX = e.clientX - startX;
                translateY = e.clientY - startY;
                
                lastX = e.clientX;
                lastY = e.clientY;
                
                updateTransform();
            });

            window.addEventListener('mouseup', function() {
                isDragging = false;
                container.style.cursor = 'grab';
            });

            // Empêcher le glisser-déposer par défaut de l'image
            container.addEventListener('dragstart', function(e) {
                e.preventDefault();
            });

            function zoomIn() {
                const rect = container.getBoundingClientRect();
                const centerX = rect.left + rect.width / 2;
                const centerY = rect.top + rect.height / 2;
                zoomToPoint(centerX, centerY, false);
            }

            function zoomOut() {
                const rect = container.getBoundingClientRect();
                const centerX = rect.left + rect.width / 2;
                const centerY = rect.top + rect.height / 2;
                zoomToPoint(centerX, centerY, true);
            }

            // Modifier la fonction resetZoom pour tenir compte du mobile
            function resetZoom() {
                scale = window.innerWidth <= 768 ? 3 : 1; // 600% sur mobile, 100% sur desktop
                translateX = 0;
                translateY = 0;
                updateTransform();
            }

            // Initialiser le zoom correct au chargement
            document.addEventListener('DOMContentLoaded', function() {
                if (window.innerWidth <= 768) {
                    resetZoom(); // Applique automatiquement le zoom 600% sur mobile
                }
            });
        </script>
        <?php
        return ob_get_clean();
    }

    function getSvgPositions($svg_url) {
        // Vérifie si l'URL est valide
        if (empty($svg_url)) {
            return ['positions' => [], 'width' => 0, 'height' => 0];
        }
    
        $svg_content = file_get_contents($svg_url);
        if (substr($svg_content, 0, 2) === "\x1f\x8b") {
            $svg_content = gzdecode($svg_content);
        }
    
        // Extraire width et height avec des expressions régulières
        preg_match('/width="([^"]*)"/', $svg_content, $width_matches);
        preg_match('/height="([^"]*)"/', $svg_content, $height_matches);
    
        // Récupérer les valeurs
        $svgWidth = isset($width_matches[1]) ? floatval($width_matches[1]) : 0;
        $svgHeight = isset($height_matches[1]) ? floatval($height_matches[1]) : 0;
    
        $positions = [];
        
        // Premier pattern: <text id="205" transform="matrix(1 0 0 1 635.637 403.6521)">
        $pattern1 = '/<text id="(\d+)" transform="matrix\(1 0 0 1 ([\d.-]+) ([\d.-]+)\)">/';
        if (preg_match_all($pattern1, $svg_content, $matches)) {
            foreach ($matches[1] as $i => $number) {
                $x = floatval($matches[2][$i]);
                $y = floatval($matches[3][$i]);
                // Conserver tous les doublons en stockant chaque position sans écrasement
                $positions[] = [
                    'id' => $number,
                    'x'  => $x,
                    'y'  => $y
                ];
            }
        }
        
        // Deuxième pattern: <text transform="matrix(1 0 0 1 200.6177 267.4883)" id="202">
        $pattern2 = '/<text transform="matrix\(1 0 0 1 ([\d.-]+) ([\d.-]+)\)" id="(\d+)">/';
        if (preg_match_all($pattern2, $svg_content, $matches)) {
            foreach ($matches[3] as $i => $number) {
                $x = floatval($matches[1][$i]);
                $y = floatval($matches[2][$i]);
                // Ajouter les positions correspondant au deuxième pattern
                $positions[] = [
                    'id' => $number,
                    'x'  => $x,
                    'y'  => $y
                ];
            }
        }
    
        // Tri des positions par 'id' pour conserver l'ordre
        usort($positions, function($a, $b) {
            return $a['id'] <=> $b['id'];
        });
        
        return ['positions' => $positions, 'width' => $svgWidth, 'height' => $svgHeight];
    }
    
    function tooltipVeFunction() {
        ob_start();

        // Récupérer le produit actuel
        $product = wc_get_product(get_the_ID()) ?? $GLOBALS['product'];

        // Récupérer l'attribut cross_ref
        $cross_ref = '';
        foreach ($product->get_attributes() as $attr) {
            if (is_object($attr) && wc_attribute_label($attr->get_name()) === 'cross_ref') {
                $cross_ref = implode(', ', $attr->get_options());
                break;
            }
        }

        // Décoder le JSON et récupérer l'URL du SVG
        $data = json_decode(preg_replace(WHITESPACE_PATTERN, ' ', $cross_ref), true);
        $svg_url = $data['svg_url'] ?? '';

        // Récupérer les positions depuis le SVG avec l'URL dynamique
        $svg_data = getSvgPositions($svg_url);
        $svg_positions = $svg_data['positions'];
        $svgWidth = $svg_data['width'];
        $svgHeight = $svg_data['height'];

        ?>
        <style>
        .piece-hover {
            position: absolute;
            cursor: pointer;
            width: 15px;
            height: 15px;
            border: 2px solid;
            transform-origin: center;
            pointer-events: all;
            z-index: 1000;
            margin-left: -3.5px;
            margin-top: -11.5px;
            background-color: rgba(255, 0, 0, 0.3);
            border-color: rgba(255, 0, 0, 0.5);
        }
        </style>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const zoomContainer = document.getElementById('zoomContainer');
            const zoomImage = document.getElementById('zoomImage');
            
            // Dimensions originales du SVG
            const SVG_WIDTH = <?php echo $svgWidth; ?>;
            const svgHeight$svgHeight = <?php echo $svgHeight; ?>;
            
            // Fonction modifiée pour calculer le facteur d'échelle et la translation
            function calculateScale() {
                const imageRect = zoomImage.getBoundingClientRect();
                const transform = window.getComputedStyle(zoomImage).transform;
                const matrix = new DOMMatrixReadOnly(transform);
                
                return {
                    scaleX: imageRect.width / SVG_WIDTH,
                    scaleY: imageRect.height / svgHeight$svgHeight,
                    translateX: matrix.e || 0,
                    translateY: matrix.f || 0,
                    zoom: window.scale || 1
                };
            }

            // Nouvelle fonction pour gérer la coloration des points
            function initializePointColors() {
                const validPositions = new Set();
                // Récupérer toutes les positions valides depuis les accordéons
                document.querySelectorAll('.accordion-header').forEach(header => {
                    const posText = header.querySelector('span').textContent;
                    const pos = parseInt(posText.match(/Position (\d+)/)[1]);
                    validPositions.add(pos);
                });

                // Appliquer les couleurs initiales et sauvegarder l'état
                document.querySelectorAll('.piece-hover').forEach(point => {
                    const position = parseInt(point.getAttribute('data-position'));
                    const exists = validPositions.has(position);
                    
                    // Sauvegarder l'état dans un attribut data
                    point.setAttribute('data-exists', exists.toString());
                    point.setAttribute('data-state', exists ? 'normal' : 'invalid');
                    
                    // Appliquer la couleur initiale
                    if (!exists) {
                        point.style.backgroundColor = 'rgba(255, 0, 0, 0.3)';
                        point.style.borderColor = 'rgba(255, 0, 0, 0.5)';
                    }
                });
            }

            // Fonction modifiée pour mettre à jour les positions des points
            function updatePointPositions() {
                const transforms = calculateScale();
                const currentZoom = window.currentZoomLevel || transforms.zoom;

                document.querySelectorAll('.piece-hover').forEach(point => {
                    const originalX = parseFloat(point.getAttribute('data-original-x'));
                    const originalY = parseFloat(point.getAttribute('data-original-y'));
                    const position = point.getAttribute('data-position');
                    
                    const scaledX = (originalX * transforms.scaleX * transforms.zoom) + transforms.translateX;
                    const scaledY = (originalY * transforms.scaleY * transforms.zoom) + transforms.translateY;
                    
                    // Vérifier si le point est sélectionné
                    const isSelected = point.getAttribute('data-selected') === 'true';
                    
                    // Je veux pour écran inéferieur à 768 px une nouvelle width, height, marginLeft et marginTop
                    if (window.innerWidth <= 768) {
                        // Appliquer les dimensions de zoom
                        if (position >= 10 && position < 100) {
                            point.style.width = '22px';
                            point.style.height = '20px';
                            point.style.marginLeft = '-1.5px';
                            point.style.marginTop = '-10.5px';
                        } else if (position >= 100) {
                            point.style.width = '28px';
                            point.style.height = '20px';
                            point.style.marginLeft = '-2.5px';
                            point.style.marginTop = '-10.5px';
                        } else {
                            point.style.width = '20px';
                            point.style.height = '20px';
                            point.style.marginLeft = '1.5px';
                            point.style.marginTop = '-7.5px';
                        }
                    } else {
                    if (currentZoom >= 2) { // 200%
                        // Appliquer les dimensions de zoom
                        if (position >= 10 && position < 100) {
                            point.style.width = '39px';
                            point.style.height = '32px';
                            point.style.marginLeft = '-7px';
                            point.style.marginTop = '-23px';
                        } else if (position >= 100) {
                            point.style.width = '49px';
                            point.style.height = '32px';
                            point.style.marginLeft = '-7px';
                            point.style.marginTop = '-23px';
                        } else {
                            point.style.width = '30px';
                            point.style.height = '32px';
                            point.style.marginLeft = '-7px';
                            point.style.marginTop = '-23px';
                        }
                    } else {
                        // Dimensions originales pour zoom normal
                        if (position >= 10 && position < 100) {
                            point.style.width = '20px';
                            point.style.height = '16px';
                            point.style.marginLeft = '-3.5px';
                            point.style.marginTop = '-11px';
                        } else if (position >= 100) {
                            point.style.width = '25px';
                            point.style.height = '16px';
                            point.style.marginLeft = '-3.5px';
                            point.style.marginTop = '-11px';
                        } else {
                            point.style.width = '15px';
                            point.style.height = '16px';
                            point.style.marginLeft = '-3.5px';
                            point.style.marginTop = '-11.5px';
                        }
                    }
                }

                    // Appliquer les couleurs en fonction de la sélection
                    const state = point.getAttribute('data-state');
                    switch(state) {
                        case 'invalid':
                            point.style.backgroundColor = 'rgba(255, 0, 0, 0.3)';
                            point.style.borderColor = 'rgba(255, 0, 0, 0.5)';
                            break;
                        case 'selected':
                            point.style.backgroundColor = 'rgba(0, 86, 179, 0.3)';
                            point.style.borderColor = 'rgba(0, 86, 179, 0.5)';
                            break;
                        default: // normal
                        point.style.backgroundColor = 'rgba(255, 0, 0, 0.3)';
                        point.style.borderColor = 'rgba(255, 0, 0, 0.5)';
                    }
                    
                    point.style.transform = `translate(${scaledX}px, ${scaledY}px) scale(${transforms.zoom})`;
                    point.style.left = '0';
                    point.style.top = '0';
                });
            }

            // Ajouter la fonction de vérification des positions existantes
            function positionExists(position) {
                const headers = document.querySelectorAll('.accordion-header');
                for (let header of headers) {
                    const posText = header.querySelector('span').textContent;
                    const pos = parseInt(posText.match(/Position (\d+)/)[1]);
                    if (pos === parseInt(position)) {
                        return true;
                    }
                }
                return false;
            }

            if (zoomContainer && zoomImage) {
                <?php
                // Afficher les cercles pour toutes les positions SVG trouvées, y compris les doublons
                if (!empty($svg_positions)) {
                    foreach ($svg_positions as $index => $position) {
                        ?>
                        const point<?php echo $index; ?> = document.createElement('div');
                        point<?php echo $index; ?>.className = 'piece-hover';
                        point<?php echo $index; ?>.setAttribute('data-position', '<?php echo $position['id']; ?>');
                        point<?php echo $index; ?>.setAttribute('data-original-x', '<?php echo $position['x']; ?>');
                        point<?php echo $index; ?>.setAttribute('data-original-y', '<?php echo $position['y']; ?>');

                        // Initialiser la couleur en fonction de l'existence de la position
                        if (!positionExists(<?php echo $position['id']; ?>)) {
                            point<?php echo $index; ?>.style.backgroundColor = 'rgba(255, 0, 0, 0.3)';
                            point<?php echo $index; ?>.style.borderColor = 'rgba(255, 0, 0, 0.5)';
                        } else {
                            point<?php echo $index; ?>.style.backgroundColor = 'rgba(255, 0, 0, 0.3)';
                            point<?php echo $index; ?>.style.borderColor = 'rgba(255, 0, 0, 0.5)';
                        }

                        // Ajuster la taille selon l'ID
                        if (<?php echo $position['id']; ?> >= 10 && <?php echo $position['id']; ?> < 100) {
                            point<?php echo $index; ?>.style.width = '21px';
                            point<?php echo $index; ?>.style.marginLeft = '-3.5px';
                            point<?php echo $index; ?>.style.marginTop = '-11px';
                        } else if (<?php echo $position['id']; ?> >= 100) {
                            point<?php echo $index; ?>.style.width = '27px';
                            point<?php echo $index; ?>.style.marginLeft = '-3.5px';
                            point<?php echo $index; ?>.style.marginTop = '-11px';
                        }
                        
                        zoomContainer.appendChild(point<?php echo $index; ?>);
                <?php
                    }
                }
                ?>

                // Initialisation et observateurs
                updatePointPositions();
                initializePointColors();
                
                const resizeObserver = new ResizeObserver(() => {
                    updatePointPositions();
                });
                resizeObserver.observe(zoomImage);

                // Gestionnaire de clic pour debug
                zoomContainer.addEventListener('click', function(e) {
                    const rect = zoomContainer.getBoundingClientRect();
                    const scale = calculateScale();
                    const x = (e.clientX - rect.left) / scale.scaleX;
                    const y = (e.clientY - rect.top) / scale.scaleY;
                    
                    console.log('Coordonnées du clic:', {
                        screen: {x: e.clientX, y: e.clientY},
                        relative: {x: e.clientX - rect.left, y: e.clientY - rect.top},
                        scaled: {x, y},
                        scale
                    });
                });

                // Ajouter un écouteur pour la mise à jour des points lors du zoom
                const originalUpdateTransform = window.updateTransform;
                window.updateTransform = function() {
                    if (originalUpdateTransform) originalUpdateTransform();
                    requestAnimationFrame(updatePointPositions);
                };

                // Observer les changements de style de l'image
                const observer = new MutationObserver(() => {
                    requestAnimationFrame(updatePointPositions);
                });

                observer.observe(zoomImage, {
                    attributes: true,
                    attributeFilter: ['style']
                });

                // Mettre à jour lors du redimensionnement de la fenêtre
                window.addEventListener('resize', () => {
                    requestAnimationFrame(updatePointPositions);
                });

                // Écouter l'événement de zoom
                window.addEventListener('zoomLevelChanged', (e) => {
                    requestAnimationFrame(updatePointPositions);
                });
            }
        });

        // Gestion des boutons de quantité
        jQuery(document).ready(function($) {
            $(document).on('click', '.plus-btn', function() {
                var input = $(this).siblings('.quantity-input');
                input.val(parseInt(input.val()) + 1);
            });

            $(document).on('click', '.minus-btn', function() {
                var input = $(this).siblings('.quantity-input');
                var value = parseInt(input.val());
                if (value > 1) {
                    input.val(value - 1);
                }
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    function clickShowProductsFunction() {
        ob_start();
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Constantes de couleurs pour éviter la répétition
            const COLORS = {
                default: {
                    bg: 'rgba(255, 0, 0, 0.3)',
                    border: 'rgba(255, 0, 0, 0.5)'
                },
                selected: {
                    bg: 'rgba(0, 86, 179, 0.3)',
                    border: 'rgba(0, 86, 179, 0.5)'
                }
            };

            function resetAllPoints() {
                document.querySelectorAll('.piece-hover').forEach(point => {
                    
                    const exists = point.getAttribute('data-exists') === 'true';
                    point.style.backgroundColor = COLORS.default.bg;
                    point.style.borderColor = COLORS.default.border;
                    point.setAttribute('data-selected', 'false');
                });
            }

           
            function findAccordionByPosition(position) {
                const headers = document.querySelectorAll('.accordion-header');
                for (let header of headers) {
                    const posText = header.querySelector('span').textContent;
                    // Ignorer si c'est un kit
                    if (posText.includes('Maintenance')) {
                        continue;
                    }
                    const matches = posText.match(/Position (\d+)/);
                    if (matches && parseInt(matches[1]) === parseInt(position)) {
                        return {
                            header: header,
                            content: header.nextElementSibling
                        };
                    }
                }
                return null;
            }

            function openAccordionForPosition(position, fromAccordion = false) {
                const accordion = findAccordionByPosition(position);
                if (!accordion) return;

                resetAllPoints();

                // Mise à jour des points
                document.querySelectorAll('.piece-hover').forEach(point => {
                    const isCurrentPosition = point.getAttribute('data-position') === position.toString();
                    point.setAttribute('data-state', isCurrentPosition ? 'selected' :
                        (point.getAttribute('data-exists') === 'true' ? 'normal' : 'invalid'));
                });

                // Ne pas modifier l'accordéon si le clic vient de l'accordéon lui-même
                if (!fromAccordion) {
                    // Fermer tous les accordéons d'abord
                    document.querySelectorAll('.accordion-content').forEach(content => {
                        content.style.display = 'none';
                        content.classList.remove('active');
                        content.previousElementSibling.querySelector('.arrow').innerHTML = '▼';
                    });

                    // Ouvrir l'accordéon sélectionné
                    accordion.content.style.display = 'block';
                    accordion.content.classList.add('active');
                    accordion.header.querySelector('.arrow').innerHTML = '▲';

                    // Scroll vers l'accordéon
                    const scrollContainer = document.querySelector('.scroll-container');
                    if (scrollContainer) {
                        const containerTop = scrollContainer.getBoundingClientRect().top;
                        const accordionTop = accordion.header.getBoundingClientRect().top;
                        scrollContainer.scrollTop += (accordionTop - containerTop);
                    }
                }

                // Mise à jour du point sélectionné
                document.querySelectorAll(`.piece-hover[data-position="${position}"]`).forEach(point => {
                    if (point.getAttribute('data-exists') === 'true') {
                        point.style.backgroundColor = COLORS.selected.bg;
                        point.style.borderColor = COLORS.selected.border;
                        point.setAttribute('data-selected', 'true');
                    }
                });
            }
                
            // Gestionnaire d'événements séparés pour les points et les accordéons
            document.querySelectorAll('.piece-hover').forEach(point => {
                ['click', 'touchstart'].forEach(eventType => {
                    point.addEventListener(eventType, (e) => {
                        e.preventDefault();
                        const position = point.getAttribute('data-position');
                        if (position) openAccordionForPosition(parseInt(position), false);
                    }, { passive: false });
                });
            });

            document.querySelectorAll('.accordion-header').forEach(header => {
                ['click', 'touchstart'].forEach(eventType => {
                    header.addEventListener(eventType, (e) => {
                        const posText = header.querySelector('span').textContent;
                        if (posText.includes('Maintenance')) return;
                        
                        const matches = posText.match(/Position (\d+)/);
                        if (matches) {
                            const position = parseInt(matches[1]);
                            openAccordionForPosition(position, true);
                        }
                    }, { passive: false });
                });
            });
        });
        </script>
         <?php
        return ob_get_clean();
    }
    
    function accordionVeFunction() {
        ob_start();
        ?>
        <style>
            .accordion {
                margin-bottom: 15px;
                border-radius: 8px;
                overflow: hidden;
                position: relative;
            }
            .accordion-header {
                color: white;
                padding: 15px 20px;
                cursor: pointer;
                display: flex;
                justify-content: space-between;
                align-items: center;
                font-size: 18px;
                background: #0056B3; /* Couleur par défaut */
            }
            .accordion-header.special {
                background: #00458F; /* Couleur pour les pièces spéciales */
            }
            .accordion-content {
                display: none;
                padding: 25px;
                background: white;
                border: 1px solid #e2e8f0;
                font-size: 18px;
            }
            .accordion-content.active {
                display: block;
            }
            .details-dropdown {
                border: 1px solid #e2e8f0;
                border-radius: 5px;
            }
            .dropdown-header {
                background: #f8fafc;
                padding: 10px 15px;
                cursor: pointer;
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-radius: 5px;
            }
            .dropdown-content {
                display: none;
                padding: 15px;
            }
            .dropdown-content.show {
                display: block;
            }
        </style>

        <script>
            function toggleAccordion(index, event) {
                event.preventDefault();

                const content = document.getElementById(`accordion-${index}`);
                const allContents = document.getElementsByClassName("accordion-content");
                const allHeaders = document.getElementsByClassName("accordion-header");
                const currentHeader = event.currentTarget;

                // Fermer tous les autres accordéons
                for(let i = 0; i < allContents.length; i++) {
                    const currentContent = allContents[i];
                    if(currentContent.id !== `accordion-${index}`) {
                        currentContent.style.display = "none";
                        currentContent.classList.remove("active");
                        allHeaders[i].querySelector(".arrow").textContent = "▼";
                    }
                }

                // Ouvrir/fermer l'accordéon cliqué
                if(content.classList.contains("active")) {
                    content.style.display = "none";
                    content.classList.remove("active");
                    currentHeader.querySelector(".arrow").textContent = "▼";
                } else {
                    content.style.display = "block";
                    content.classList.add("active");
                    currentHeader.querySelector(".arrow").textContent = "▲";
                }
            }

            function toggleDropdown(header) {
                const content = header.nextElementSibling;
                const arrow = header.querySelector(".dropdown-arrow");
                if (content.classList.contains("show")) {
                    content.classList.remove("show");
                    arrow.textContent = "▼";
                } else {
                    content.classList.add("show");
                    arrow.textContent = "▲";
                }
            }

            // Initialiser le premier accordéon comme ouvert
            document.addEventListener("DOMContentLoaded", function() {
                // Initialiser les styles et l'affichage pour les pièces spéciales
                document.querySelectorAll('.accordion-header').forEach(header => {
                    const titleSpan = header.querySelector('span');
                    if (titleSpan) {
                        const text = titleSpan.textContent;
                        const isSpecialPart = text.includes('Kit-');
                        
                        if (isSpecialPart) {
                            header.classList.add('special');
                        }
                    }
                });

                const firstAccordion = document.querySelector(".accordion-content");
                const firstHeader = document.querySelector(".accordion-header");
                if(firstAccordion && firstHeader) {
                    firstAccordion.style.display = "block";
                    firstAccordion.classList.add("active");
                    firstHeader.querySelector(".arrow").textContent = "▲";
                }
            });
        </script>
        <?php
        return ob_get_clean();
    }

    define('HTML_STRONG_OPEN', '<strong>');
    define('HTML_STRONG_CLOSE', '</strong>');

    class ProductNotFoundException extends Exception {
        public function __construct($message = "Produit non trouvé", $code = 0, Exception $previous = null) {
            parent::__construct($message, $code, $previous);
        }
    }

    function getProductVariationIdBySku($sku) {
        return ($id = wc_get_product_id_by_sku($sku)) ? $id : 0;
    }

    function isValidPosition($position) {
        $position = ltrim($position, '*');
        return is_numeric($position) && intval($position) >= 1 && intval($position) <= 10000;
    }

    function isEmptyOrSpecialChar($value) {
        return empty(trim($value)) || trim($value) === ' ';
    }

    function afficherCaracteristiquesProduitV2() {
        try {
            $product = wc_get_product(get_the_ID()) ?? $GLOBALS['product'] ?? throw new ProductNotFoundException();

            $cross_ref = '';
            foreach ($product->get_attributes() as $attr) {
                if (is_object($attr) && wc_attribute_label($attr->get_name()) === 'cross_ref') {
                    $cross_ref = implode(', ', $attr->get_options());
                    break;
                }
            }

            $jsonData = json_decode(preg_replace(WHITESPACE_PATTERN, ' ', $cross_ref), true);

            $output = '<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">';
            $output .= '<style>
                * { font-family: "Poppins", sans-serif; font-size: 18px; }
                .position-word, .position-number { display: inline; }
                .special .position-word, .special .position-number { display: none; }
                .add-to-cart-btn { background-color: #e31206 !important; transition: background-color .3s; }
                .add-to-cart-btn:hover { background-color: #B32217 !important; }
                .add-to-cart-btn:focus { box-shadow: 0 0 0 0.25rem rgba(227, 18, 6, 0.5)  }
                .position-text, .product-name { font-weight: 600; font-size: 18px; }
                .scroll-container { max-height: 600px; overflow-y: auto; }
                .zoom-controls.desktop { display: block; }
                @media (max-width: 768px) {
                    .zoom-controls.desktop { display: none !important; }
                    .scroll-container { max-height: 400px !important; }
                    .red-alert { flex-direction: column-reverse; }
                }
                .product-info-row { display: flex; margin: 0; }
                .details-dropdown { margin: 0; }
                .actions-group { display: flex; flex-direction: column; gap: 20px; }
                @media (min-width: 1900px) {
                    .product-content-container { gap: 25px; }
                    .actions-group { flex-direction: row; gap: 10px; }
                }
                .product-content-container { display: flex; flex-direction: column; gap: 20px; }
                .accordion-content > div { display: flex; flex-direction: column; gap: 20px; }
                @media (min-width: 1900px) {
                    .product-info-row { flex-wrap: wrap; }
                    .product-info-row span { word-break: break-word; }
                }
                @media (max-width: 1900px) {
                    .product-info-row { flex-direction: column; }
                    .product-info-row.quantity-row { flex-direction: row; }
                    .product-info-row span { min-width: 100%; }
                    .product-info-row.quantity-row span { min-width: unset; }
                    .actions-container { flex-direction: column; align-items: stretch; gap: 15px; }
                    .scroll-container { max-height: 500px; }
                }
            </style>';

            if (isset($jsonData['table_data'])) {
                $output .= '<div id="scroll-container" class="scroll-container">';
                
                $filtered_data = array_filter($jsonData['table_data'], function($piece) {
                    return isset($piece['position_vue_eclatee']) && isValidPosition($piece['position_vue_eclatee']);
                });

                $special_parts = array_filter($filtered_data, function($piece) {
                    return strpos($piece['position_vue_eclatee'], '*') === 0;
                });
                
                $normal_parts = array_filter($filtered_data, function($piece) {
                    return strpos($piece['position_vue_eclatee'], '*') !== 0;
                });

                usort($special_parts, function($a, $b) {
                    return intval(ltrim($a['position_vue_eclatee'], '*')) - intval(ltrim($b['position_vue_eclatee'], '*'));
                });

                usort($normal_parts, function($a, $b) {
                    return intval($a['position_vue_eclatee']) - intval($b['position_vue_eclatee']);
                });

                $filtered_data = array_merge($special_parts, $normal_parts);

                foreach ($filtered_data as $index => $piece) {
                    $sku = htmlspecialchars($piece['reference_piece']);
                    $nom_piece = htmlspecialchars($piece['nom_piece']);
                    $variation_id = getProductVariationIdBySku($sku);
                    $position = $piece['position_vue_eclatee'];
                    $isSpecialPart = strpos($position, '*') === 0;
                    $displayPosition = ltrim($position, '*');

                    $kitNumber = '';
                    if ($isSpecialPart) {
                        $kitNumber = 'Maintenance - ';  // Suppression de la numérotation
                    }
                    
                    $output .= sprintf('
                    <div class="accordion">
                        <div class="accordion-header %s" onclick="toggleAccordion(%d, event)">
                             <span><strong><span class="position-word">Position </span><span class="position-number">%s - </span>%s</strong><span class="product-name">%s</span></span>
                            <span class="arrow">▼</span>
                        </div>
                        <div id="accordion-%d" class="accordion-content %s">
                            <div class="product-content-container">',
                        $isSpecialPart ? 'special' : 'normal',
                        $index,
                        $displayPosition,
                        $kitNumber,
                        HTML_STRONG_OPEN . $nom_piece . HTML_STRONG_CLOSE,
                        $index,
                        $index === 0 ? 'active' : ''
                    );

                    $output .= implode('', array_map(function($k, $v) {
                        
                        if ($k === 'quantite') {
                            $value = isEmptyOrSpecialChar($v)
                                ? '<span style="color: red; font-weight: bold;">VALEUR N\'EXISTE PAS</span>'
                                : HTML_STRONG_OPEN . htmlspecialchars($v) . HTML_STRONG_CLOSE;

                            return sprintf(
                                '<div class="product-info-row quantity-row" style="display:flex;">
                                    <span style="color:#2c5282">Quantité&nbsp;:&nbsp;</span>
                                    <span>%s</span>
                                </div>',
                                $value
                            );
                        }
                        return '';
                    }, array_keys($piece), $piece));

                    $output .= '
                    <div class="details-dropdown">
                        <div class="dropdown-header" onclick="toggleDropdown(this)">
                            <span>Détails supplémentaires</span>
                            <span class="dropdown-arrow">▼</span>
                        </div>
                        <div class="dropdown-content">';

                    $orderedFields = $isSpecialPart ?
                        ['nom_model', 'reference_model', 'reference_piece'] :
                        ['nom_model', 'reference_model', 'reference_piece', 'contenu_dans_kit'];

                    foreach ($orderedFields as $field) {
                        if (isset($piece[$field])) {
                            $label = [
                                'nom_model' => 'Nom du modèle',
                                'reference_model' => 'Référence modèle',
                                'reference_piece' => 'Référence pièce',
                                'nom_piece' => 'Nom de la pièce',
                                'contenu_dans_kit' => 'Contenu dans le kit'
                            ][$field];
                            
                            if ($field === 'contenu_dans_kit') {
                                $value = isEmptyOrSpecialChar($piece[$field])
                                    ? '<span style="font-weight: bold;">n\'est compris dans aucun kit.</span>'
                                    : HTML_STRONG_OPEN . htmlspecialchars($piece[$field]) . HTML_STRONG_CLOSE;
                            } else {
                                $value = isEmptyOrSpecialChar($piece[$field])
                                    ? '<span style="color: red; font-weight: bold;">VALEUR N\'EXISTE PAS</span>'
                                    : HTML_STRONG_OPEN . htmlspecialchars($piece[$field]) . HTML_STRONG_CLOSE;
                            }
                            
                            $output .= sprintf(
                                '<div class="product-info-row">
                                    <span style="color:#2c5282">%s&nbsp;:&nbsp;</span>
                                    <span>%s</span>
                                </div>',
                                $label,
                                $value
                            );
                        }
                    }

                    $output .= '</div></div>';

                    $output .= '<div class="actions-group">';
                    $output .= sprintf('
                                    <div class="quantity-container" style="display:flex;align-items:center;gap:10px">
                                        <label style="color:#2c5282">Quantité :</label>
                                        <div style="display:flex;align-items:center">
                                            <button onclick="this.nextElementSibling.stepDown()" style="background:#f7fafc;border:1px solid #e2e8f0;padding:5px 9px;cursor:pointer">-</button>
                                            <input type="number" value="1" min="1" aria-label="Quantité" title="Quantité" style="width:50px;text-align:center;border-top:1px solid #e2e8f0;border-bottom:1px solid #e2e8f0;border-left:none;border-right:none;padding:7px 0">
                                            <button onclick="this.previousElementSibling.stepUp()" style="background:#f7fafc;border:1px solid #e2e8f0;padding:5px 9px;cursor:pointer">+</button>
                                        </div>
                                    </div>
                                    <button onclick="ajouterAuPanier(\'%s\',%d)" class="add-to-cart-btn" style="color:white;padding:6px 9px;border:none;border-radius:5px;cursor:pointer;font-weight:bold;display:flex;align-items:center;gap:8px; width: fit-content">
                                        <svg width="16" height="16" viewBox="0 0 24 24" style="stroke:currentColor;fill:none;stroke-width:2"><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/></svg>
                                        Ajouter au panier
                                    </button>
                                </div>
                                <div id="alert-%d" style="display:none;margin-top:10px;padding:15px;border-radius:5px;background-color:#4CAF50;color:white;font-weight:bold;text-align:center">
                                    <div style="display:flex;justify-content:space-between;align-items:center;gap:5px">
                                        <span>✓ Produit ajouté au panier avec succès !</span>
                                        <a href="%s" style="background-color:white;color:#4CAF50;padding:8px 15px;border-radius:4px;text-decoration:none;font-weight:bold;transition:all .3s">Voir le panier</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>',
                    $sku,
                    $variation_id,
                    $variation_id,
                    wc_get_cart_url()
                    );
                }
                $output .= '</div>';

                $output .= '<div class="zoom-controls desktop">
                    <button class="zoom-button" onclick="resetZoom()">Reset</button>
                    <button class="zoom-button" onclick="zoomIn()">-</button>
                    <span id="zoomLevel" style="color: white; margin: 0 10px;">100%</span>
                    <button class="zoom-button" onclick="zoomOut()">+</button>
                </div>';
            }

            $output .= '<script>
                async function ajouterAuPanier(reference, productId) {
                    const btn = event.currentTarget;
                    const qty = btn.parentElement.querySelector("input[type=number]").value;

                    if (productId === 0) {
                        
                        const errorAlert = document.createElement(\'div\');
                        errorAlert.style.cssText = \'margin-top:10px;padding:15px;border-radius:5px;background-color:#FF0000;color:white;font-weight:bold;text-align:center\';
                        errorAlert.innerHTML = `
                            <div class="red-alert" style="display:flex;justify-content:space-between;align-items:center;gap:5px">
                                <span>X Produit non trouvé dans la base de données</span>
                                <a href="https://www.cenov-distribution.fr/nous-contacter/"
                                   style="background-color:white;color:#FF0000;padding:8px 15px;border-radius:4px;text-decoration:none;font-weight:bold;transition:all .3s">
                                   Nous contacter
                                </a>
                            </div>
                        `;
                        
                        btn.parentElement.insertAdjacentElement(\'afterend\', errorAlert);
                        
                        return;
                    }

                    try {
                        const formData = new FormData();
                        formData.append("action", "woocommerce_ajax_add_to_cart");
                        formData.append("product_id", productId);
                        formData.append("quantity", qty);
                        formData.append("add-to-cart", productId);

                        let ajaxUrl = "/wp-admin/admin-ajax.php";
                        if (typeof wc_add_to_cart_params !== "undefined") {
                            ajaxUrl = wc_add_to_cart_params.wc_ajax_url.toString().replace("%%endpoint%%", "add_to_cart");
                        }

                        const response = await fetch(ajaxUrl, {
                            method: "POST",
                            body: formData,
                            credentials: "same-origin"
                        });

                        if (productId !== 0) {
                            console.log("✅ SUCCÈS: Produit ajouté au panier!", {
                                sku: reference,
                                productId: productId
                            });
                            
                            btn.innerHTML = "<div style=\'display:flex;gap:8px;align-items:center;\'><svg width=\'16\' height=\'16\' viewBox=\'0 0 24 24\' style=\'stroke:currentColor;fill:none;stroke-width:2;\'><path d=\'M20 6L9 17l-5-5\'/></svg>Ajouté !</div>";
                            
                            const alertElement = document.getElementById(`alert-${productId}`);
                            if (alertElement) alertElement.style.display = "block";

                            jQuery(document.body).trigger("wc_fragments_refreshed");
                        }

                    } catch (error) {
                        console.log("❌ ERREUR:", error.message);
                    }

                    setTimeout(() => {
                        btn.innerHTML = "<div style=\'display:flex;gap:8px;align-items:center;\'><svg width=\'16\' height=\'16\' viewBox=\'0 0 24 24\' style=\'stroke:currentColor;fill:none;stroke-width:2;\'><path d=\'M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6\'/><circle cx=\'9\' cy=\'21\' r=\'1\'/><circle cx=\'20\' cy=\'21\' r=\'1\'/></svg>Ajouter au panier</div>";
                    }, 2000);
                }
            </script>';

            return $output;

        } catch (ProductNotFoundException $e) {
            return '<div style="color:red">Erreur: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    function addVueEclateeTab($tabs) {
        $tabs['vue_eclatee_tab'] = array(
            'title'    => __('Vue Éclatée', 'woocommerce'),
            'priority' => 50,
            'callback' => 'displayVueEclateeContent'
        );
        return $tabs;
    }

    function displayVueEclateeContent() {
        global $product;
        
        define('HTML_DIV_CLOSE', '</div>');
        
        echo '<style>
            @media (max-width: 768px) {
                .vue-eclatee-container {
                    flex-direction: column !important;
                }
                .vue-eclatee-left {
                    flex: 90 !important;
                }
                .vue-eclatee-right {
                    flex: 10 !important;
                }
            }
            @media (min-width: 1900px) {
                .vue-eclatee-container {
                    gap: 10px !important;
                }
                .vue-eclatee-left {
                    flex: 60 !important;
                }
                .vue-eclatee-right {
                    flex: 40 !important;
                }
            }
        </style>';
        
        echo '<div class="woocommerce-Tabs-panel woocommerce-Tabs-panel--vue-eclatee panel entry-content wc-tab" id="tab-vue-eclatee" role="tabpanel" aria-labelledby="tab-title-vue-eclatee" style="display: block;">';
        
        echo '<div class="vue-eclatee-container" style="display: flex; gap: 15px; align-items: flex-start;">';
        
        echo '<div class="vue-eclatee-left" style="flex: 70;">';
        echo zoomVeFunction();
        echo tooltipVeFunction();
        echo clickShowProductsFunction();
        echo accordionVeFunction();
        echo '<div class="zoom-container">';
        echo HTML_DIV_CLOSE;
        echo HTML_DIV_CLOSE;
        
        echo '<div class="vue-eclatee-right" style="flex: 30;">';
        // Remplacer le shortcode par l'appel direct à la fonction
        echo afficherCaracteristiquesProduitV2();
        echo HTML_DIV_CLOSE;
        
        echo HTML_DIV_CLOSE;
        echo HTML_DIV_CLOSE;
        
    }
    
    add_filter('woocommerce_product_tabs', 'addVueEclateeTab');
}
