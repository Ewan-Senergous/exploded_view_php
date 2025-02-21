<?php
if (!function_exists('finalVeFunction')) {
    // Ajout de la fonction getSvgPositions avant finalVeFunction
    function getSvgPositions($svg_url) {
        if (empty($svg_url)) {
            return ['positions' => [], 'width' => 0, 'height' => 0];
        }

        $svg_content = file_get_contents($svg_url);
        if (substr($svg_content, 0, 2) === "\x1f\x8b") {
            $svg_content = gzdecode($svg_content);
        }

        preg_match('/width="([^"]*)"/', $svg_content, $width_matches);
        preg_match('/height="([^"]*)"/', $svg_content, $height_matches);

        $svgWidth = floatval($width_matches[1]);
        $svgHeight = floatval($height_matches[1]);

        $positions = [];
        $pattern = '/<text id="(\d|[1-9]\d|[1-2]\d\d|300)" transform="matrix\(1 0 0 1 ([\d.-]+) ([\d.-]+)\)">/';
        if (preg_match_all($pattern, $svg_content, $matches)) {
            foreach ($matches[1] as $i => $number) {
                $positions[] = [
                    'id' => $number,
                    'x'  => floatval($matches[2][$i]),
                    'y'  => floatval($matches[3][$i])
                ];
            }
        }

        usort($positions, function($a, $b) {
            return $a['id'] <=> $b['id'];
        });
        
        return ['positions' => $positions, 'width' => $svgWidth, 'height' => $svgHeight];
    }

    function finalVeFunction() {
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
        $data = json_decode(preg_replace('/\s+/', ' ', $cross_ref), true);
        $svg_url = $data['svg_url'] ?? '';
        
        if (empty($svg_url)) {
            return 'SVG URL not found';
        }

        // Récupérer les positions depuis le SVG
        $svg_data = getSvgPositions($svg_url);
        $svg_positions = $svg_data['positions'];
        $svgWidth = $svg_data['width'];
        $svgHeight = $svg_data['height'];

        // Ajouter le style pour les points
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

            /* Styles pour les points */
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

                const zoomContainer = document.getElementById('zoomContainer');
                const zoomImage = document.getElementById('zoomImage');
                
                // Dimensions originales du SVG
                const SVG_WIDTH = <?php echo $svgWidth; ?>;
                const SVG_HEIGHT = <?php echo $svgHeight; ?>;
                
                // Fonction pour calculer l'échelle
                function calculateScale() {
                    const imageRect = zoomImage.getBoundingClientRect();
                    const transform = window.getComputedStyle(zoomImage).transform;
                    const matrix = new DOMMatrixReadOnly(transform);
                    
                    return {
                        scaleX: imageRect.width / SVG_WIDTH,
                        scaleY: imageRect.height / SVG_HEIGHT,
                        translateX: matrix.e || 0,
                        translateY: matrix.f || 0,
                        zoom: window.scale || 1
                    };
                }

                // Création des points
                <?php
                if (!empty($svg_positions)) {
                    foreach ($svg_positions as $index => $position) {
                        ?>
                        const point<?php echo $index; ?> = document.createElement('div');
                        point<?php echo $index; ?>.className = 'piece-hover';
                        point<?php echo $index; ?>.setAttribute('data-position', '<?php echo $position['id']; ?>');
                        point<?php echo $index; ?>.setAttribute('data-original-x', '<?php echo $position['x']; ?>');
                        point<?php echo $index; ?>.setAttribute('data-original-y', '<?php echo $position['y']; ?>');
                        zoomContainer.appendChild(point<?php echo $index; ?>);
                        <?php
                    }
                }
                ?>

                // Fonction de mise à jour des positions des points
                function updatePointPositions() {
                    const transforms = calculateScale();
                    // ... Reste du code de updatePointPositions ...
                }

                // Initialisation et observateurs
                updatePointPositions();
                
                const resizeObserver = new ResizeObserver(() => {
                    updatePointPositions();
                });
                resizeObserver.observe(zoomImage);

                // Écouter l'événement de zoom
                window.addEventListener('zoomLevelChanged', () => {
                    requestAnimationFrame(updatePointPositions);
                });
            });
        </script>
        <?php
        return ob_get_clean();
    }
}

add_shortcode('final_ve', 'finalVeFunction');