<?php
if (!function_exists('addVueEclateeTab')) {
    function addVueEclateeTab($tabs) {
        $tabs['vue_eclatee_tab'] = array(
            'title'    => __('Vue Éclatée', 'woocommerce'),
            'priority' => 5,
            'callback' => 'displayVueEclateeContent'
        );
        return $tabs;
    }

    function displayVueEclateeContent() {
        global $product;
        
        define('HTML_DIV_CLOSE', '</div>');
        
        echo '<style>
            @media (max-width: 768px) {
                .vue-eclatee-container {
                    flex-direction: column !important;
                }
                .vue-eclatee-left {
                    flex: 90 !important;
                }
                .vue-eclatee-right {
                    flex: 10 !important;
                }
            }
            @media (min-width: 1900px) {
                .vue-eclatee-container {
                    gap: 10px !important;
                }
                .vue-eclatee-left {
                    flex: 60 !important;
                }
                .vue-eclatee-right {
                    flex: 40 !important;
                }
            }
        </style>';
        
        echo '<div class="woocommerce-Tabs-panel woocommerce-Tabs-panel--vue-eclatee panel entry-content wc-tab" id="tab-vue-eclatee" role="tabpanel" aria-labelledby="tab-title-vue-eclatee" style="display: block;">';
        
        echo '<div class="vue-eclatee-container" style="display: flex; gap: 15px; align-items: flex-start;">';
        
        echo '<div class="vue-eclatee-left" style="flex: 70;">';
        echo do_shortcode('[zoom_ve]');
        echo do_shortcode('[tooltip_ve]');
        echo do_shortcode('[clickShowProducts]');
        echo do_shortcode('[accordion_ve]');
        echo '<div class="zoom-container">';
        echo HTML_DIV_CLOSE;
        echo HTML_DIV_CLOSE;
        
        echo '<div class="vue-eclatee-right" style="flex: 30;">';
        $sku = $product->get_sku();
        
        echo do_shortcode('[xyz-ips snippet="TestEwan" cross_ref_sku="' . $sku . '"]');
        echo HTML_DIV_CLOSE;
        
        echo HTML_DIV_CLOSE;
        echo HTML_DIV_CLOSE;
        
    }
    
    add_filter('woocommerce_product_tabs', 'addVueEclateeTab');
}
