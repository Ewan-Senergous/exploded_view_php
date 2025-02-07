<?php
if (!function_exists('zoom_ve_function')) {
    function zoom_ve_function() {
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
        ?>
        <style>
            .zoom-container {
                position: relative;
                overflow: hidden;
                width: 100%;
                max-width: 1200px;
                cursor: grab;
                background: #f5f5f5;
            }
            .image-container {
                border: 1px solid #ddd;
                width: 100%;
            }
            .zoom-image {
                width: 100%;
                height: auto;
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
                    <img src="<?php echo esc_url($svg_url); ?>" class="zoom-image" id="zoomImage">
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
            const ZOOM_STEPS = window.innerWidth <= 768 ? [400] : [100, 200];
            const MAX_ZOOM = window.innerWidth <= 768 ? 4 : 2;
            const MIN_ZOOM = window.innerWidth <= 768 ? 4 : 1; // Force le zoom minimum à 600% sur mobile
            
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
                    return 4; // 600%
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
                scale = window.innerWidth <= 768 ? 4 : 1; // 600% sur mobile, 100% sur desktop
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
}

add_shortcode('zoom_ve', 'zoom_ve_function');