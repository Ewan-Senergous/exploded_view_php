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
    
    function display_vue_eclatee_content() {
        global $product;
        
        // Suppression de cette condition qui bloque l'exécution
        echo '<div class="woocommerce-Tabs-panel woocommerce-Tabs-panel--vue-eclatee panel entry-content wc-tab" id="tab-vue-eclatee" role="tabpanel" aria-labelledby="tab-title-vue-eclatee" style="display: block;">';
        
        // Conteneur flex
        echo '<div style="display: flex; gap: 20px; align-items: flex-start;">';
        
        // Colonne gauche
        echo '<div style="flex: 1;">';
        echo '<div class="zoom-container">';
        echo do_shortcode('[zoom_ve]');
        echo '</div>';
        echo '</div>';
        
        // Colonne droite
        echo '<div style="flex: 1;">';
        echo do_shortcode('[xyz-ips snippet="paginationVueEclatee"]');
        
        // Récupération et affichage du tableau avec SKU
        $sku = $product->get_sku();
        echo do_shortcode('[xyz-ips snippet="TestEwan" cross_ref_sku="' . $sku . '"]');
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
    }
    
    add_filter('woocommerce_product_tabs', 'add_vue_eclatee_tab');
}