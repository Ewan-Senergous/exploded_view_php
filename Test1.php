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
            .piece-hover {
                position: absolute;
                cursor: pointer;
                width: 25px;
                height: 25px;
                background-color: rgba(255, 0, 0, 0.3);
                border: 3px solid rgba(255, 0, 0, 5);
                border-radius: 50%;
                transform-origin: center;
                pointer-events: all;
                z-index: 1000;
                margin-left: -7.5px;
                margin-top: -15.5px;
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
                console.log('updateTransform:', {
                    translateX: translateX,
                    translateY: translateY,
                    scale: scale
                });
                updatePointPositions();
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

            function updatePointPositions() {
                const imageRect = image.getBoundingClientRect();
                const transform = window.getComputedStyle(image).transform;
                const matrix = new DOMMatrixReadOnly(transform);

                const scaleX = imageRect.width / 718.949;
                const scaleY = imageRect.height / 493.722;
                const translateX = matrix.e || 0;
                const translateY = matrix.f || 0;

                console.log('updatePointPositions:', {
                    imageRect: imageRect,
                    transform: transform,
                    matrix: matrix,
                    scaleX: scaleX,
                    scaleY: scaleY,
                    translateX: translateX,
                    translateY: translateY
                });

                document.querySelectorAll('.piece-hover').forEach(point => {
                    const originalX = parseFloat(point.getAttribute('data-original-x'));
                    const originalY = parseFloat(point.getAttribute('data-original-y'));

                    const scaledX = (originalX * scaleX * scale) + translateX;
                    const scaledY = (originalY * scaleY * scale) + translateY;

                    point.style.transform = `translate(${scaledX}px, ${scaledY}px) scale(${scale})`;

                    // Afficher les informations pour le premier point uniquement
                    if (point.getAttribute('data-position') === '1') {
                        console.log('Point 1 updated:', {
                            originalX: originalX,
                            originalY: originalY,
                            scaledX: scaledX,
                            scaledY: scaledY,
                            scale: scale
                        });
                    }
                });
            }

            window.addEventListener('resize', updatePointPositions);
        </script>
        <?php
        return ob_get_clean();
    }
}

add_shortcode('zoom_ve', 'zoom_ve_function');
?>
