<?php
if (!function_exists('pagination_vue_eclatee_test')) {
    function pagination_vue_eclatee_test() {
        // Récupérer les données du produit actuel
        $product = wc_get_product(get_the_ID());
        if (!$product) return '';
        
        // Récupérer l'attribut cross_ref
        $cross_ref = '';
        foreach ($product->get_attributes() as $attr) {
            if (is_object($attr) && wc_attribute_label($attr->get_name()) === 'cross_ref') {
                $cross_ref = implode(', ', $attr->get_options());
                break;
            }
        }
        
        $jsonData = json_decode(preg_replace('/\s+/', ' ', $cross_ref), true);
        if (!isset($jsonData['table_data'])) return '';
        
        // Configuration pagination
        $items_per_page = 5;
        $total_items = count($jsonData['table_data']);
        $total_pages = ceil($total_items / $items_per_page);
        $current_page = isset($_GET['vue_page']) ? max(1, min($total_pages, intval($_GET['vue_page']))) : 1;
        
        // Construction de la pagination
        $output = '<div class="pagination-container" style="margin: 20px 0; text-align: center;">';
        
        // Style CSS
        $output .= '<style>
            .pagination-container {
                font-family: "Poppins", sans-serif;
            }
            .pagination-button {
                display: inline-block;
                padding: 8px 15px;
                margin: 0 5px;
                background: #0056B3;
                color: white !important;
                text-decoration: none;
                border-radius: 5px;
                transition: background 0.3s;
            }
            .pagination-button:hover {
                background: #003d7a;
                text-decoration: none;
            }
            .pagination-current {
                background: #003d7a;
                font-weight: bold;
            }
            .pagination-disabled {
                background: #cccccc;
                cursor: not-allowed;
            }
        </style>';

        // Obtenir l'URL de base sans paramètres
        $base_url = strtok($_SERVER["REQUEST_URI"], '?');
        
        // Bouton précédent
        $prev_page = max(1, $current_page - 1);
        $output .= sprintf(
            '<a href="%s?vue_page=%d" class="pagination-button %s" %s>← Précédent</a>',
            $base_url,
            $prev_page,
            $current_page <= 1 ? 'pagination-disabled' : '',
            $current_page <= 1 ? 'onclick="return false;"' : ''
        );
        
        // Numéros de pages
        for ($i = 1; $i <= $total_pages; $i++) {
            $output .= sprintf(
                '<a href="%s?vue_page=%d" class="pagination-button %s">%d</a>',
                $base_url,
                $i,
                $i === $current_page ? 'pagination-current' : '',
                $i
            );
        }
        
        // Bouton suivant
        $next_page = min($total_pages, $current_page + 1);
        $output .= sprintf(
            '<a href="%s?vue_page=%d" class="pagination-button %s" %s>Suivant →</a>',
            $base_url,
            $next_page,
            $current_page >= $total_pages ? 'pagination-disabled' : '',
            $current_page >= $total_pages ? 'onclick="return false;"' : ''
        );
        
        $output .= '</div>';
        
        echo $output;
    }
}
pagination_vue_eclatee_test();