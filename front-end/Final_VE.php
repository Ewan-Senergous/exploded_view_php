<?php
if (!function_exists('finalVeFunction')) {
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

        // Tous les styles CSS combinés
        echo '<style>
            /* Styles communs */
            * { font-family: "Poppins", sans-serif; font-size: 18px; }
            
            /* Styles pour le zoom */
            .zoom-container {
                position: relative;
                overflow: hidden;
                width: 100%;
                max-width: 1000px;
                cursor: grab;
                background: #f5f5f5;
            }
            .zoom-image {
                width: 100%;
                height: 100%;
                object-fit: cover;
                transform-origin: 0 0;
                pointer-events: none;
            }
            
            /* Styles pour l\'accordéon */
            .accordion {
                margin-bottom: 15px;
                border-radius: 8px;
                overflow: hidden;
            }
            .accordion-header {
                color: white;
                padding: 15px 20px;
                cursor: pointer;
                background: #0056B3;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            /* Styles pour les points de la vue éclatée */
            .piece-hover {
                position: absolute;
                cursor: pointer;
                width: 15px;
                height: 15px;
                border: 2px solid;
                transform-origin: center;
                pointer-events: all;
                z-index: 1000;
            }
            
            /* Styles responsive */
            @media (max-width: 768px) {
                .vue-eclatee-container { flex-direction: column !important; }
                .vue-eclatee-left { flex: 90 !important; }
                .vue-eclatee-right { flex: 10 !important; }
            }

            /* ...autres styles... */
        </style>';

        // Structure principale
        echo '<div class="woocommerce-Tabs-panel woocommerce-Tabs-panel--vue-eclatee panel entry-content wc-tab" id="tab-vue-eclatee" role="tabpanel">';
        echo '<div class="vue-eclatee-container" style="display: flex; gap: 15px; align-items: flex-start;">';
        echo '<div class="vue-eclatee-left" style="flex: 70;">';

        // Zone de zoom
        echo '<div class="zoom-wrapper">
                <div class="zoom-container" id="zoomContainer">
                    <div class="image-container">
                        <img src="' . esc_url($svg_url) . '" class="zoom-image" id="zoomImage" alt="Vue éclatée">
                    </div>
                </div>
            </div>';

        // Zone du tableau
        if (isset($data['table_data'])) {
            // ... code du tableau ...
        }

        echo '</div>';
        
        echo '<div class="vue-eclatee-right" style="flex: 30;">';
        $sku = $product->get_sku();
        echo do_shortcode('[xyz-ips snippet="TestEwan" cross_ref_sku="' . $sku . '"]');
        echo '</div>';
        
        echo '</div></div>';

        // Scripts JavaScript combinés
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                // Variables globales
                let scale = 1;
                const ZOOM_STEPS = window.innerWidth <= 768 ? [300] : [100, 200];
                const MAX_ZOOM = window.innerWidth <= 768 ? 3 : 2;
                
                // Fonctions de zoom
                function updateTransform() {
                    // ... code de la fonction ...
                }
                
                function zoomToPoint(x, y, increase) {
                    // ... code de la fonction ...
                }
                
                // Gestionnaires d\'événements
                container.addEventListener("wheel", function(e) {
                    // ... code des événements ...
                });
                
                // Fonctions pour l\'accordéon
                function toggleAccordion(index, event) {
                    // ... code de la fonction ...
                }
                
                // Initialisation
                initializePointColors();
                updatePointPositions();
            });
        </script>';

        return ob_get_clean();
    }
}

// Ajout du shortcode unique
add_shortcode('final_ve', 'finalVeFunction');

// Ajout de l'onglet Vue Éclatée
add_filter('woocommerce_product_tabs', function($tabs) {
    $tabs['vue_eclatee_tab'] = array(
        'title'    => __('Vue Éclatée', 'woocommerce'),
        'priority' => 5,
        'callback' => 'finalVeFunction'
    );
    return $tabs;
});