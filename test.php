<?php
if (!function_exists('tooltip_ve_function')) {
function getSvgPositions() {
$svg_url = 'https://www.service-er.de/public/media/E885.svgz';
$svg_content = file_get_contents($svg_url);

if (substr($svg_content, 0, 2) === "\x1f\x8b") {
$svg_content = gzdecode($svg_content);
}

$positions = [];

echo "<script>console.log('Analyse du SVG pour positions 1-10...');</script>";

// Regex modifiée pour chercher spécifiquement les IDs de 1 à 10
$pattern = '/<text id="([1-9]|10)" transform="matrix\(1 0 0 1 ([0-9.-]+) ([0-9.-]+)\)">/';

if (preg_match_all($pattern, $svg_content, $matches)) {
foreach ($matches[1] as $i => $number) {
$x = floatval($matches[2][$i]);
$y = floatval($matches[3][$i]);

$positions[$number] = [
'x' => $x,
'y' => $y
];

echo "<script>console.log('Position $number trouvée:', {
x: $x,
y: $y,
raw: " . json_encode($matches[0][$i]) . "
});</script>";
}
}

// Trier les positions par numéro pour s'assurer de l'ordre 1-10
ksort($positions);

echo "<script>console.log('Positions 1-10 trouvées:', " . json_encode($positions) . ");</script>";

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
width: 15px;
height: 15px;
background-color: rgba(255, 0, 0, 0.5);
border: 2px solid rgba(255, 0, 0, 0.8);
border-radius: 50%;
transform-origin: center;
pointer-events: all;
z-index: 1000;
margin-left: -7.5px;
margin-top: -7.5px;
}
.tooltip-content {
display: none;
position: absolute;
left: 25px;
top: 0;
background: white;
border: 1px solid #ddd;
padding: 10px;
border-radius: 5px;
box-shadow: 0 2px 5px rgba(0,0,0,0.2);
z-index: 1001;
min-width: 200px;
}
.piece-hover:hover .tooltip-content {
display: block;
}
.tooltip-content p {
margin: 5px 0;
color: #333;
}
.quantity-section {
margin-top: 10px;
}
.quantity-section button {
padding: 2px 8px;
margin: 0 5px;
cursor: pointer;
}
.quantity-input {
width: 50px;
text-align: center;
}
.add-to-cart {
margin-top: 5px;
padding: 5px 10px;
background-color: #4CAF50;
color: white;
border: none;
border-radius: 3px;
cursor: pointer;
}
/* Supprimer le style .origin-point */
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const zoomContainer = document.getElementById('zoomContainer');
    const zoomImage = document.getElementById('zoomImage');
    
    // Dimensions originales du SVG
    const SVG_WIDTH = 718.949;
    const SVG_HEIGHT = 493.722;
    
    // Debug initial
    console.log('Debug conteneur et image:', {
        container: zoomContainer?.getBoundingClientRect(),
        image: zoomImage?.getBoundingClientRect()
    });
    
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
        // Supprimer la création du point bleu et garder uniquement les points rouges
        <?php if(!empty($data['table_data'])) {
            $limit = min(10, count($data['table_data']));
            for ($index = 0; $index < $limit; $index++) {
                $item = $data['table_data'][$index];
                $position_number = $index + 1;
                $position = isset($svg_positions[$position_number]) 
                    ? $svg_positions[$position_number] 
                    : ['x' => 0, 'y' => 0];
        ?>
                const point<?php echo $index; ?> = document.createElement('div');
                point<?php echo $index; ?>.className = 'piece-hover';
                point<?php echo $index; ?>.setAttribute('data-position', '<?php echo $position_number; ?>');
                point<?php echo $index; ?>.setAttribute('data-original-x', '<?php echo $position['x']; ?>');
                point<?php echo $index; ?>.setAttribute('data-original-y', '<?php echo $position['y']; ?>');
                point<?php echo $index; ?>.innerHTML = `
                    <div class="tooltip-content">
                        <p><strong>Position:</strong> <?php echo $position_number; ?></p>
                        <p><strong>Nom du produit:</strong> <?php echo htmlspecialchars($item['Nom_produit']); ?></p>
                        <p><strong>Référence:</strong> <?php echo htmlspecialchars($item['Ref_fabriquant']); ?></p>
                        <div class="quantity-section">
                            <label>Quantité : </label>
                            <button class="minus-btn">-</button>
                            <input type="number" value="1" min="1" class="quantity-input">
                            <button class="plus-btn">+</button>
                            <button class="add-to-cart">Ajouter au panier</button>
                        </div>
                    </div>
                `;
                zoomContainer.appendChild(point<?php echo $index; ?>);
        <?php
            }
        } ?>

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