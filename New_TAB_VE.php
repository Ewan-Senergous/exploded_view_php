<?php
if (!function_exists('add_vue_eclatee_tab')) {
    function add_vue_eclatee_tab($tabs) {
        $tabs['vue_eclatee_tab'] = array(
            'title'    => __('Vue Éclatée', 'woocommerce'),
            'priority' => 5,
            'callback' => 'display_vue_eclatee_content'
        );
        return $tabs;
    }

    // function add_vue_eclatee_tab($tabs) {
    //     $tabs['vue_eclatee_tab'] = array(
    //         'title'    => __('Vue Éclatée', 'woocommerce'),
    //         'priority' => 5,
    //         'callback' => 'display_vue_eclatee_content'
    //     );
    //     return $tabs;
    // }
    
    function display_vue_eclatee_content() {
        global $product;
        if (!function_exists('vue_eclatee_tab')) {
            echo '<div class="woocommerce-Tabs-panel woocommerce-Tabs-panel--vue-eclatee panel entry-content wc-tab" id="tab-vue-eclatee" role="tabpanel" aria-labelledby="tab-title-vue-eclatee" style="display: block;">';
            echo '<h2>Vue Éclatée</h2>';
            
            // Création du conteneur flex pour l'image et le tableau
            echo '<div style="display: flex; gap: 20px; align-items: flex-start;">';
            
            // Ajout de l'image à gauche
            echo '<div style="flex: 1;">';
            echo '<img src="https://www.service-er.de/public/media/E885.svgz" alt="Vue éclatée" style="max-width: 100%; height: auto;">';
            echo '</div>';
            
            // Ajout du tableau à droite
            echo '<div style="flex: 1;">';
            // Récupération du SKU du produit actuel
            $sku = $product->get_sku();
            // Affichage du shortcode avec le SKU dynamique
            echo do_shortcode('[xyz-ips snippet="TestEwan" cross_ref_sku="' . $sku . '"]');
            echo '</div>';
            
            echo '</div>'; // Fermeture du conteneur flex
            echo '</div>';
        }
    }
    
    add_filter('woocommerce_product_tabs', 'add_vue_eclatee_tab');
}