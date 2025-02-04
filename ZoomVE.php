<?php
if (!function_exists('zoom_ve_function')) {
    function zoom_ve_function() {
        ob_start();
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
                margin-left: auto;
                margin-right: auto;
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
                    <img src="https://www.service-er.de/public/media/E885.svgz" class="zoom-image" id="zoomImage">
                </div>
            </div>
            <div class="zoom-controls">
                <button class="zoom-button" onclick="zoomIn()">-</button>
                <span id="zoomLevel" style="color: white; margin: 0 10px;">100%</span>
                <button class="zoom-button" onclick="zoomOut()">+</button>
                <button class="zoom-button" onclick="resetZoom()">Reset</button>
            </div>
        </div>

        <script>
            let scale = 1;
            const ZOOM_STEPS = [50, 75, 100, 125, 150, 175, 200, 250, 300, 400];
            const MAX_ZOOM = 4;
            const MIN_ZOOM = 0.5;
            
            const container = document.getElementById('zoomContainer');
            const image = document.getElementById('zoomImage');
            let isDragging = false;
            let startX, startY, translateX = 0, translateY = 0;
            let lastX, lastY;
            let momentum = { x: 0, y: 0 };

            function updateTransform() {
                image.style.transform = `translate(${translateX}px, ${translateY}px) scale(${scale})`;
                document.getElementById('zoomLevel').textContent = Math.round(scale * 100) + '%';
            }

            function findNextZoomStep(currentScale, increase) {
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

            function resetZoom() {
                scale = 1;
                translateX = 0;
                translateY = 0;
                updateTransform();
            }
        </script>
        <?php
        return ob_get_clean();
    }
}

add_shortcode('zoom_ve', 'zoom_ve_function');