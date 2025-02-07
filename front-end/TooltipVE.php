<?php
if (!function_exists('tooltip_ve_function')) {

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
        $SVG_WIDTH = floatval($width_matches[1]);
        $SVG_HEIGHT = floatval($height_matches[1]);

        $positions = [];
        // Regex modifiée pour chercher spécifiquement les IDs de 1 à 300
        $pattern = '/<text id="([1-9]|[1-9][0-9]|[1-2][0-9][0-9]|300)" transform="matrix\(1 0 0 1 ([0-9.-]+) ([0-9.-]+)\)">/';
        if (preg_match_all($pattern, $svg_content, $matches)) {
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

        // Tri des positions par 'id' pour conserver l'ordre
        usort($positions, function($a, $b) {
            return $a['id'] <=> $b['id'];
        });
        
        return ['positions' => $positions, 'width' => $SVG_WIDTH, 'height' => $SVG_HEIGHT];
    }

    function tooltip_ve_function() {
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

        // Récupérer les positions depuis le SVG avec l'URL dynamique
        $svg_data = getSvgPositions($svg_url);
        $svg_positions = $svg_data['positions'];
        $SVG_WIDTH = $svg_data['width'];
        $SVG_HEIGHT = $svg_data['height'];
        echo "<script>console.log('Positions SVG chargées:', " . json_encode($svg_positions) . ");</script>";

        // Nettoyer et décoder le JSON
        $data = json_decode(preg_replace('/\s+/', ' ', $cross_ref), true);
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
            const SVG_WIDTH = <?php echo $SVG_WIDTH; ?>;
            const SVG_HEIGHT = <?php echo $SVG_HEIGHT; ?>;
            
            // Fonction modifiée pour calculer le facteur d'échelle et la translation
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

            // Fonction modifiée pour mettre à jour les positions des points
            function updatePointPositions() {
                const transforms = calculateScale();
                
                // Utiliser l'événement zoom ou la variable globale
                const currentZoom = window.currentZoomLevel || transforms.zoom;

                document.querySelectorAll('.piece-hover').forEach(point => {
                    const originalX = parseFloat(point.getAttribute('data-original-x'));
                    const originalY = parseFloat(point.getAttribute('data-original-y'));
                    const position = parseInt(point.getAttribute('data-position'));
                    
                    const scaledX = (originalX * transforms.scaleX * transforms.zoom) + transforms.translateX;
                    const scaledY = (originalY * transforms.scaleY * transforms.zoom) + transforms.translateY;
                    
                    // Changer la couleur et les dimensions en fonction du zoom
                    if (currentZoom >= 2) { // 200%
                        // Nouvelles dimensions pour zoom 200%
                        if (position >= 10 && position < 100) {
                            point.style.width = '42px'; // Double de 21px
                            point.style.height = '30px'; // Nouvelle hauteur fixe
                            point.style.marginLeft = '-7px'; // Double de -3.5px
                            point.style.marginTop = '-23px'; // Double de -11px
                        } else if (position >= 100) {
                            point.style.width = '54px'; // Double de 27px
                            point.style.height = '30px'; // Nouvelle hauteur fixe
                            point.style.marginLeft = '-7px'; // Double de -3.5px
                            point.style.marginTop = '-23px'; // Double de -11px
                        } else {
                            point.style.width = '30px'; // Double de 15px
                            point.style.height = '30px'; // Nouvelle hauteur fixe
                            point.style.marginLeft = '-7px'; // Double de -3.5px
                            point.style.marginTop = '-23px'; // Double de -11.5px
                        }
                        point.style.backgroundColor = 'rgba(255, 0, 0, 0.3)';
                        point.style.borderColor = 'rgba(255, 0, 0, 0.5)';
                    } else {
                        // Dimensions originales pour zoom normal
                        if (position >= 10 && position < 100) {
                            point.style.width = '21px';
                            point.style.height = '15px';
                            point.style.marginLeft = '-3.5px';
                            point.style.marginTop = '-11px';
                        } else if (position >= 100) {
                            point.style.width = '27px';
                            point.style.height = '15px';
                            point.style.marginLeft = '-3.5px';
                            point.style.marginTop = '-11px';
                        } else {
                            point.style.width = '15px';
                            point.style.height = '15px';
                            point.style.marginLeft = '-3.5px';
                            point.style.marginTop = '-11.5px';
                        }
                        point.style.backgroundColor = 'rgba(255, 0, 0, 0.3)';
                        point.style.borderColor = 'rgba(255, 0, 0, 0.5)';
                    }
                    
                    point.style.transform = `translate(${scaledX}px, ${scaledY}px) scale(${transforms.zoom})`;
                    point.style.left = '0';
                    point.style.top = '0';
                });
            }

            if (zoomContainer && zoomImage) {
                <?php 
                // Afficher les cercles pour toutes les positions SVG trouvées, y compris les doublons
                if (!empty($svg_positions)) {
                    foreach ($svg_positions as $index => $position) {
                        // Chercher si des données produit existent pour cette position
                        $item = null;
                        if (!empty($data['table_data'])) {
                            foreach ($data['table_data'] as $product_data) {
                                if (isset($product_data['Position']) && $product_data['Position'] == $position['id']) {
                                    $item = $product_data;
                                    break;
                                }
                            }
                        }
                ?>
                        const point<?php echo $index; ?> = document.createElement('div');
                        point<?php echo $index; ?>.className = 'piece-hover';
                        point<?php echo $index; ?>.setAttribute('data-position', '<?php echo $position['id']; ?>');
                        point<?php echo $index; ?>.setAttribute('data-original-x', '<?php echo $position['x']; ?>');
                        point<?php echo $index; ?>.setAttribute('data-original-y', '<?php echo $position['y']; ?>');

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
}

add_shortcode('tooltip_ve', 'tooltip_ve_function');
?>