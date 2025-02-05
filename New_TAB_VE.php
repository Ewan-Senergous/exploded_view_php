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
        
        echo '<div class="woocommerce-Tabs-panel woocommerce-Tabs-panel--vue-eclatee panel entry-content wc-tab" id="tab-vue-eclatee" role="tabpanel" aria-labelledby="tab-title-vue-eclatee" style="display: block;">';
        
        echo '<div style="display: flex; gap: 15px; align-items: flex-start;">';
        
        echo '<div style="flex: 70;">';  
        echo do_shortcode('[zoom_ve]');
        echo do_shortcode('[tooltip_ve]');
        echo do_shortcode('[clickShowProducts]');
        echo '<div class="zoom-container">';
        echo '</div>';
        echo '</div>';
        
        echo '<div style="flex: 30;">'; 
        $sku = $product->get_sku();
        echo do_shortcode('[xyz-ips snippet="TestEwan" cross_ref_sku="' . $sku . '"]');
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
    }
    
    add_filter('woocommerce_product_tabs', 'add_vue_eclatee_tab');
}