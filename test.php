<?php
if (!function_exists('tooltip_ve_function')) {
function getSvgPositions() {
$svg_url = 'https://www.service-er.de/public/media/E885.svgz';
$svg_content = file_get_contents($svg_url);

if (substr($svg_content, 0, 2) === "\x1f\x8b") {
$svg_content = gzdecode($svg_content);
}

$positions = [];

// Charger le contenu SVG et détecter les "id" dupliqués
$svg = simplexml_load_string($svg_content);
$ids = [];
foreach($svg->xpath('//text') as $text) {
    $currentId = (string)$text['id'];
    if (in_array($currentId, $ids)) {
        // Renommer l'ID pour éviter le doublon
        $text['id'] = $currentId.'_dup';
    } else {
        $ids[] = $currentId;
    }
}

// Regex modifiée pour chercher spécifiquement les IDs de 1 à 300
$pattern = '/<text id="(\d+)" transform="matrix\(1 0 0 1 ([0-9.-]+) ([0-9.-]+)\)">/';

if (preg_match_all($pattern, $svg_content, $matches)) {
foreach ($matches[1] as $i => $number) {
$x = floatval($matches[2][$i]);
$y = floatval($matches[3][$i]);

if ((int)$number > 0) {
$positions[] = [
'id' => (int)$number,
'x' => $x,
'y' => $y
];
}
}
}

usort($positions, function($a, $b) {
    return $a['id'] <=> $b['id'];
});
$positions = array_combine(range(1, count($positions)), array_values($positions));

return $positions;
}

function tooltip_ve_function() {
ob_start();

// Récupérer les positions depuis le SVG
$svg_positions = getSvgPositions();
echo "<script>console.log('Positions SVG chargées:', " . json_encode($svg_positions) . ");</script>";

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

// Nettoyer et décoder le JSON
$data = json_decode(preg_replace('/\s+/', ' ', $cross_ref), true);
?>

<style>
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

.popover-content {
display: none;
position: absolute;
left: calc(100% + 5px); /* Réduit de 10px à 5px */
top: 50%;
transform: translateY(-50%);
background: white;
padding: 15px;
border-radius: 8px;
box-shadow: 0 2px 8px rgba(0,0,0,0.15);
z-index: 1001;
width: 280px;
border: 1px solid #e2e8f0;
pointer-events: auto; /* Permet l'interaction avec le contenu */
}

/* Modification pour permettre l'interaction avec le popover */
.piece-hover:hover .popover-content,
.popover-content:hover {
display: block;
}

.popover-content::before {
content: '';
position: absolute;
left: -6px;
top: 50%;
transform: translateY(-50%);
border-style: solid;
border-width: 6px 6px 6px 0;
border-color: transparent white transparent transparent;
pointer-events: none;
}

/* Ajout d'une zone tampon pour maintenir le hover */
.piece-hover::after {
content: '';
position: absolute;
width: 30px; /* Augmenté de 20px à 30px */
height: 120%; /* Augmenté à 120% pour plus de marge */
right: -15px; /* Ajusté pour centrer la zone tampon */
top: -10%; /* Décalé vers le haut pour compenser la hauteur augmentée */
}

/* Ajout d'une zone tampon à gauche du popover */
.popover-content::after {
content: '';
position: absolute;
width: 15px;
height: 120%;
left: -15px;
top: -10%;
}

.popover-title {
color: #2c5282;
font-weight: 600;
margin-bottom: 10px;
font-size: 14px;
}

.popover-content p {
margin: 5px 0;
font-size: 13px;
color: #4a5568;
}

.quantity-controls {
display: flex;
align-items: center;
gap: 8px;
margin: 12px 0;
}

.quantity-controls button {
background: #f7fafc;
border: 1px solid #e2e8f0;
padding: 4px 8px;
cursor: pointer;
border-radius: 4px;
}

.quantity-input {
width: 40px;
text-align: center;
border: 1px solid #e2e8f0;
border-radius: 4px;
padding: 3px;
}

.add-to-cart-btn-1 {
width: 100%;
background: #FF5733;
color: white;
padding: 8px;
border: none;
border-radius: 4px;
cursor: pointer;
font-weight: 500;
font-size: 13px;
display: flex;
align-items: center;
justify-content: center;
gap: 6px;
transition: background 0.3s;
}

.add-to-cart-btn-1:hover {
background: #FF774D;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const zoomContainer = document.getElementById('zoomContainer');
    const zoomImage = document.getElementById('zoomImage');
    
    // Dimensions originales du SVG
    const SVG_WIDTH = 718.949;
    const SVG_HEIGHT = 493.722;
    
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

        document.querySelectorAll('.piece-hover').forEach(point => {
            const originalX = parseFloat(point.getAttribute('data-original-x'));
            const originalY = parseFloat(point.getAttribute('data-original-y'));
            
            const scaledX = (originalX * transforms.scaleX * transforms.zoom) + transforms.translateX;
            const scaledY = (originalY * transforms.scaleY * transforms.zoom) + transforms.translateY;
            
            point.style.transform = `translate(${scaledX}px, ${scaledY}px) scale(${transforms.zoom})`;
            point.style.left = '0';
            point.style.top = '0';
        });
    }

    if (zoomContainer && zoomImage) {
        <?php 
        // Afficher les cercles pour toutes les positions SVG trouvées
        if(!empty($svg_positions)) {
            foreach($svg_positions as $position_number => $position) {
                // Chercher si des données produit existent pour cette position
                $item = null;
                if(!empty($data['table_data'])) {
                    foreach($data['table_data'] as $product_data) {
                        if(isset($product_data['Position']) && $product_data['Position'] == $position_number) {
                            $item = $product_data;
                            break;
                        }
                    }
                }
        ?>
                const point<?php echo $position_number; ?> = document.createElement('div');
                point<?php echo $position_number; ?>.className = 'piece-hover';
                point<?php echo $position_number; ?>.setAttribute('data-position', '<?php echo $position_number; ?>');
                point<?php echo $position_number; ?>.setAttribute('data-original-x', '<?php echo $position['x']; ?>');
                point<?php echo $position_number; ?>.setAttribute('data-original-y', '<?php echo $position['y']; ?>');
                point<?php echo $position_number; ?>.innerHTML = `
                    <div class="popover-content">
                        <div class="popover-title">Position <?php echo $position_number; ?></div>
                        <?php if($item): ?>
                            <p><strong><?php echo htmlspecialchars($item['Nom_produit']); ?></strong></p>
                            <p>Réf: <?php echo htmlspecialchars($item['Ref_fabriquant']); ?></p>
                            <div class="quantity-controls">
                                <button class="minus-btn">-</button>
                                <input type="number" value="1" min="1" class="quantity-input">
                                <button class="plus-btn">+</button>
                            </div>
                            <button class="add-to-cart-btn-1">
                                <svg width="14" height="14" viewBox="0 0 24 24" style="stroke:currentColor;fill:none;font-weight:bold;stroke-width:2">
                                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                                    <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                                </svg>
                                Ajouter au panier
                            </button>
                        <?php else: ?>
                            <p>Position <?php echo $position_number; ?></p>
                        <?php endif; ?>
                    </div>
                `;
                zoomContainer.appendChild(point<?php echo $position_number; ?>);
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