<?php
function dt_enqueue_styles() {
    $parenthandle = 'divi-style';
    $theme = wp_get_theme();
    wp_enqueue_style( $parenthandle, get_template_directory_uri() . '/style.css',
        array(), // if the parent theme code has a dependency, copy it to here
        $theme->parent()->get('Version')
    );
    wp_enqueue_style( 'child-style', get_stylesheet_uri(),
        array( $parenthandle ),
        $theme->get('Version')
    );
}
add_action( 'wp_enqueue_scripts', 'dt_enqueue_styles' );

function my_theme_enqueue_styles() { 
 wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
}
add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );



//debut ajout arthur 15/01/24 --> objectif import export slug
add_filter( 'woocommerce_product_export_column_names', 'add_slug_export_column' );
add_filter( 'woocommerce_product_export_product_default_columns', 'add_slug_export_column' );

function add_slug_export_column( $columns ) {
	$columns['slug'] = 'Slug';
 
	return $columns;
}

add_filter( 'woocommerce_product_export_product_column_slug'  , 'add_export_data_slug', 10, 2 );
function add_export_data_slug( $value, $product ) {
    $value = $product->get_slug();
	
    return $value;
}

add_filter( 'woocommerce_csv_product_import_mapping_options', 'add_slug_import_option' );
function add_slug_import_option( $options ) {
  $options['slug'] = 'Slug';
 
  return $options;
}

add_filter( 'woocommerce_csv_product_import_mapping_default_columns', 'add_default_slug_column_mapping' );
function add_default_slug_column_mapping( $columns ) {
  $columns['Slug'] = 'slug';
 
  return $columns;
} 

add_filter( 'woocommerce_product_import_pre_insert_product_object', 'process_import_product_slug_column', 10, 2 );
function process_import_product_slug_column( $object, $data ) {
  if ( !empty( $data['slug'] ) ) {
    $object->set_slug( $data['slug'] );
  }
 
  return $object;
}
//fin ajout arthur 15/01/24 --> objectif import export slug
//
//
//
//Début ajout 07.02.2024 --> Objectif régler le problème identifié sous pagespeed.web : L'attribut [user-scalable="no"] --> créé des lenteurs
function remove_my_action() {
remove_action('wp_head', 'et_add_viewport_meta');
}
function custom_et_add_viewport_meta(){
echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=1" />';
}
add_action( 'init', 'remove_my_action');
add_action( 'wp_head', 'custom_et_add_viewport_meta' );
//Fin ajout 07.02.2024 --> Objectif régler le problème identifié sous pagespeed.web : L'attribut [user-scalable="no"]


//------------------ 13.03.2024 Début ajout fonction sommaire en début de page-----------------------------------

function wp_sommaire($t) {
	$c = "<!-- sommaire -->";
	if( !strpos($t, $c) ) { return $t; }
	preg_match_all('~(<h([2-6]))(.*?>(.*)<\/h[2-6]>)~', $t, $h);
	if(count($h[0]) < 2) { return $t; }
	$n = '';
	for ($i = 0; $i < count($h[0]); ++ $i) {
		$a = sanitize_title($h[4][$i]);
		$t = str_replace($h[0][$i], '<h'.$h[2][$i].' class="sommaire-ancre"><span id="'.$a.'"></span>'.$h[4][$i].'</h'.$h[2][$i].'>', $t);
	    $n .= '<li class="titre-h'.$h[2][$i].'"><a href="#'.$a.'">'.$h[4][$i].'</a></li>';
	}    
	$s = '<nav class="wp-sommaire"><ul>'.$n.'</ul></nav>';
	return str_replace($c, $s, $t);
} 
add_filter('the_content', 'wp_sommaire');

//------------------ 13.03.2024 fin ajout fonction sommaire en début de page-------------------------------------



//------------------ 18.03.2024 Add a new custom product tab (pièces détachées)-------------------------------------

add_filter( 'woocommerce_product_tabs', 'ql_new_custom_product_tab' );

function ql_new_custom_product_tab( $tabs ) {

//To add multiple tabs, update the label for each new tab inside the $tabs['xyz'] array, e.g., custom_tab2, my_new_tab, etc.
//
global $product;
if( is_product() && has_term(164,'product_cat') or is_product() && has_term(24582,'product_cat')) {

$tabs['custom_tab'] = array(
'title' => __( 'Pièces détachées & Accessoires', 'woocommerce' ), //change "Custom Product tab" to any text you want
'priority' => 50,
'callback' => 'ql_custom_product_tab_content'
);}
return $tabs;
}

// Add content to a custom product tab

function ql_custom_product_tab_content() {
global $product;
// va chercher les pièces & accessoires liées par la sous-sous famille
 $custom_attribute = $product->get_attribute( 'sous-sous-famille' );
//les attributs ne sont reconnus que si les espaces sont remplacés par des -
$value= str_replace(' ', '-', $custom_attribute );
	

// The custom tab content
//You can add any php code here and it will be shown in your newly created custom tab
$nom_sous_sous_famille = $product->get_attribute( 'sous-sous-famille' );
echo '<h2>Pièces et accessoires pour '.$nom_sous_sous_famille.'</h2>';

// fonction pour passer en h3 les pièces & accessoires liés



echo do_shortcode('[products category="pièces" attribute="sous-sous-famille" terms=”' .$value. '”]');

//echo '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean facilisis in dui eget rutrum. Morbi quis sodales felis.</p>';
//echo '<img src="http://hypernova/wp-content/uploads/2021/10/logo-1.jpg" width="300" height="400" align="center">';

}


//------------------ 18.03.2024 fin Add a new custom product tab-------------------------------------
//
//




//---------------- 16.05.2024 DEBUT Ajout add to cart depuis les listes de produits ---------------------
//
//
//Setting up Woocommerce "Add to Cart" icon button and adding the quantity field.
//

add_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 20 );

//add_filter( 'woocommerce_loop_add_to_cart_link', 'quantity_inputs_for_woocommerce_loop_add_to_cart_link', 10, 2 );

/*
  Override loop template and show quantities next to add to cart buttons
  @link https://gist.github.com/mikejolley/2793710
 */

add_filter( 'woocommerce_loop_add_to_cart_link', 'quantity_inputs_for_woocommerce_loop_add_to_cart_link', 10, 2 );

function quantity_inputs_for_woocommerce_loop_add_to_cart_link( $html, $product ) {
	if ( $product && $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() && ! $product->is_sold_individually() ) {
		$html = '<form action="' . esc_url( $product->add_to_cart_url() ) . '" class="cart" style="margin: 0px 0 14px !important;" method="post" enctype="multipart/form-data">';
		//$html .= woocommerce_quantity_input( array(), $product, false );
		$html .= '<button type="submit" class="button alt">' . esc_html( $product->add_to_cart_text() ) . '</button>';
		$html .= '</form>';
	}
	return $html;
}


//---------------- 16.05.2024 FIN Ajout add to cart depuis les listes de produits ---------------------

//


//---------------- 30.05.2024 DEBUT MODIF DES LISTES DE PRODUITS DE H2 A H3 ---------------------
//WooCommerce Change Title from H2 -> H3
 
function wps_change_products_title() {
	if (get_post_type() == 'post') {echo '<p class="woocommerce-loop-product__title">'. get_the_title() . '</p>';}
	else{
 
    echo '<h3 class="woocommerce-loop-product__title">'. get_the_title() . '</h3>';}
 
}
 
remove_action('woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10);
add_action('woocommerce_shop_loop_item_title', 'wps_change_products_title', 10);

//---------------- 30.05.2024 FIN MODIF DES LISTES DE PRODUITS DE H2 A H3 ---------------------
//
//
//
//code for cart addon
//* Make Font Awesome available



add_filter( 'navbar', 'woo_cart_but_icon', 10, 2 ); // Change menu to suit - example uses 'top-menu'

/**
 * Add WooCommerce Cart Menu Item Shortcode to particular menu
 */
function woo_cart_but_icon ( $items, $args ) {
       $items .=  '[woo_cart_but]'; // Adding the created Icon via the shortcode already created
       
       return $items;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'woo_cart_but_count' );
/**
 * Add AJAX Shortcode when cart contents update
 */
function woo_cart_but_count( $fragments ) {
 
    ob_start();
    
    $cart_count = WC()->cart->cart_contents_count;
    $cart_url = wc_get_cart_url();
    
    ?>
    <a class="cart-contents menu-item" href="<?php echo $cart_url; ?>" title="<?php _e( 'View your shopping cart' ); ?>">
	<?php
    if ( $cart_count > 0 ) {
        ?>
        <span class="cart-contents-count"><?php echo $cart_count; ?></span>
        <?php            
    }
        ?></a>
    <?php
 
    $fragments['a.cart-contents'] = ob_get_clean();
     
    return $fragments;
}


add_shortcode ('woo_cart_but', 'woo_cart_but' );
/**
 * Create Shortcode for WooCommerce Cart Menu Item
 */
function woo_cart_but() {
	ob_start();
 
        $cart_count = WC()->cart->cart_contents_count; // Set variable for cart item count
        $cart_url = wc_get_cart_url();  // Set Cart URL
  
        ?>
        <span><a class="et_pb_menu__icon et_pb_menu__cart-button et_pb_menu__icon__with_count" href="<?php echo $cart_url; ?>" title="My Basket">
	    <?php
        if ( $cart_count > 0 ) {
       ?> 
            <span class="account-cart-items"> :&nbsp;&nbsp;<?php echo $cart_count; ?></span>
        <?php
        }
        ?>
        </a></span>
        <?php
	        
    return ob_get_clean();
 
}

/* Create Shortcode for Red Button */

function cenov_custom_button_styles() {
    ?>
    <style>
    .button.button.alt,
    .single_add_to_cart_button.button.alt {
        background-color: #e31206 !important; 
        color: white !important;
        padding: 0.625rem 1.25rem !important;
        border-radius: 0.5rem !important;
        border: none !important;
        cursor: pointer !important;
        font-weight: bold !important;
        outline: none !important;
        transition: all 0.3s ease !important;
        letter-spacing: 0.5px !important;
    }
    
    .button.button.alt:hover,
.single_add_to_cart_button.button.alt:hover {
    background-color: #B32217 !important;
}

.button.button.alt:focus,
.single_add_to_cart_button.button.alt:focus {
    outline: none !important;
    box-shadow: 0 0 0 0.25rem rgba(227, 18, 6, 0.5) !important;
}

.button.button.alt:active,
.single_add_to_cart_button.button.alt:active {
    transform: translateY(1px) !important; 
}

.button.button.alt::after,
.single_add_to_cart_button.button.alt::after {
    display: none !important;
    content: "" !important;
}
    </style>
    <?php
}
add_action('wp_head', 'cenov_custom_button_styles', 999);

function cenov_get_cart_count() {
    echo WC()->cart->get_cart_contents_count();
    wp_die();
}
add_action('wp_ajax_get_cart_count', 'cenov_get_cart_count');
add_action('wp_ajax_nopriv_get_cart_count', 'cenov_get_cart_count');

// Ajouter les scripts AJAX pour l'ajout au panier
function cenov_ajax_add_to_cart_js() {
    // Ne pas ajouter le script sur les pages de paiement et de commande
    if (is_checkout() || is_account_page()) {
        return;
    }
    ?>
    <script type="text/javascript">
    jQuery(function($) {
        // Fonction pour mettre à jour le compteur du panier
        function updateCartCount() {
            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                data: {action: 'get_cart_count', timestamp: new Date().getTime()},
                cache: false,
                success: function(count) {
                    jQuery('span.cart-contents-count, span.account-cart-items').text(count);
                }
            });
        }

        // Fonction d'ajout au panier AJAX
        async function ajaxAddToCart(e) {
            e.preventDefault();
            
            const $thisbutton = $(this);
            const $form = $thisbutton.closest('form.cart');
            
            // Ne pas traiter si le bouton est désactivé ou en cours de chargement
            if ($thisbutton.hasClass('disabled') || $thisbutton.hasClass('wc-backward') || $thisbutton.hasClass('loading')) {
                return true;
            }
            
            // Récupérer les données du produit
            let product_id = $form.find('input[name=add-to-cart]').val() || $thisbutton.val();
            const quantity = $form.find('input[name=quantity]').val() || 1;
            let variation_id = 0;
            let variation = {};
            
            // Conserver le texte original du bouton
            const originalText = $thisbutton.html();
            
            // Changer l'apparence du bouton
            $thisbutton.addClass('loading').html('Ajout en cours...');
            
            // Vérifier s'il s'agit d'un produit variable
            if ($form.find('input[name=variation_id]').length > 0) {
                variation_id = $form.find('input[name=variation_id]').val();
                
                // Récupérer toutes les variations sélectionnées
                $form.find('select[name^=attribute_]').each(function() {
                    const attribute = $(this).attr('name');
                    variation[attribute] = $(this).val();
                });
            }
            
            // Préparer les données à envoyer
            const formData = new FormData();
            formData.append("action", "woocommerce_ajax_add_to_cart");
            formData.append("product_id", product_id);
            formData.append("quantity", quantity);
            formData.append("add-to-cart", product_id);
            
            if (variation_id > 0) {
                formData.append("variation_id", variation_id);
                
                // Ajouter les variations
                for (const [key, value] of Object.entries(variation)) {
                    formData.append(key, value);
                }
            }
            
            // Déterminer l'URL AJAX à utiliser
            let ajaxUrl = "<?php echo admin_url('admin-ajax.php'); ?>";
            if (typeof wc_add_to_cart_params !== "undefined") {
                ajaxUrl = wc_add_to_cart_params.wc_ajax_url.toString().replace("%%endpoint%%", "add_to_cart");
            }
            
            try {
                // Envoyer la requête AJAX
                const response = await fetch(ajaxUrl, {
                    method: "POST",
                    body: formData,
                    credentials: "same-origin"
                });
                
                const responseData = await response.text();
                
                // Afficher le message de succès
                $thisbutton.html("<div style='display:flex;gap:8px;align-items:center;'><svg width='16' height='16' viewBox='0 0 24 24' style='stroke:currentColor;fill:none;stroke-width:2;'><path d='M20 6L9 17l-5-5' stroke-linecap='round' stroke-linejoin='round'></path></svg>Ajouté</div>");
                
                // Afficher un message de confirmation
                if ($('.woocommerce-notices-wrapper').length) {
                    $('.woocommerce-notices-wrapper').html(
                        '<div class="woocommerce-message" role="alert" style="display:flex;justify-content:space-between;align-items:center;">' + 
                        '<span>✓ Produit ajouté au panier avec succès !</span>' + 
                        '<a href="<?php echo wc_get_cart_url(); ?>" class="button wc-forward" style="margin-left:10px;">Voir le panier</a>' + 
                        '</div>'
                    );
                } else {
                    $('<div class="woocommerce-message" role="alert" style="display:flex;justify-content:space-between;align-items:center;">' + 
                      '<span>✓ Produit ajouté au panier avec succès !</span>' + 
                      '<a href="<?php echo wc_get_cart_url(); ?>" class="button wc-forward" style="margin-left:10px;">Voir le panier</a>' + 
                      '</div>').insertBefore('.product_title, #main');
                }
                
                // Mettre à jour tous les éléments du panier
                updateCartCount();
                $(document.body).trigger("wc_fragments_refresh");
                $(document.body).trigger("added_to_cart");
                $(document.body).trigger("wc_fragment_refresh");
                $(document.body).trigger("update_checkout");
                
                // Rétablir le texte du bouton après un délai
                setTimeout(function() {
                    $thisbutton.removeClass('loading').html(originalText);
                }, 2000);
                
            } catch (error) {
                console.error('Erreur lors de l\'ajout au panier:', error);
                
                // Afficher un message d'erreur
                if ($('.woocommerce-notices-wrapper').length) {
                    $('.woocommerce-notices-wrapper').html(
                        '<div class="woocommerce-error" role="alert">' + 
                        'Erreur lors de l\'ajout au panier. Veuillez réessayer.' + 
                        '</div>'
                    );
                } else {
                    $('<div class="woocommerce-error" role="alert">' + 
                      'Erreur lors de l\'ajout au panier. Veuillez réessayer.' + 
                      '</div>').insertBefore('.product_title, #main');
                }
                
                // Rétablir le bouton
                $thisbutton.removeClass('loading').html(originalText);
            }
        }
        
        // Intercepter le clic sur les boutons d'ajout au panier
        $(document).on('click', 'form.cart .single_add_to_cart_button', ajaxAddToCart);
        
        // Support pour les boutons d'ajout au panier de la boutique
        $(document).on('click', '.ajax_add_to_cart', function(e) {
            $(document.body).trigger('adding_to_cart', [$(this), {}]);
        });
        
        // Ajouter la classe ajax_add_to_cart aux boutons de la boutique
        $('.add_to_cart_button:not(.product_type_variable, .product_type_grouped, .ajax_add_to_cart)').addClass('ajax_add_to_cart');
    });
    </script>
    <?php
}
add_action('wp_footer', 'cenov_ajax_add_to_cart_js');

// Activer l'ajout au panier AJAX sur les boutons de la boutique
function cenov_loop_add_to_cart_ajax_support() {
    add_filter('woocommerce_loop_add_to_cart_args', 'cenov_add_ajax_class_to_add_to_cart', 10, 2);
}
add_action('init', 'cenov_loop_add_to_cart_ajax_support');

// Ajouter la classe ajax_add_to_cart aux boutons de la boutique
function cenov_add_ajax_class_to_add_to_cart($args, $product) {
    if ($product->get_type() == 'simple' && $product->is_purchasable() && $product->is_in_stock()) {
        $args['class'] .= ' ajax_add_to_cart';
    }
    return $args;
}


/* Create Shortcode for ADD 🛒 in red buttons  */

function add🛒RedButtons() {
	?>
<script>
jQuery(document).ready(function($) {
  $('.button.alt, .single_add_to_cart_button').prepend('<svg width="16" height="16" viewBox="0 0 24 24" style="stroke:currentColor;fill:none;stroke-width:2;margin-right:5px;"><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/></svg>');
  
  $('.button.alt, .single_add_to_cart_button').css({
    'display': 'flex',
    'align-items': 'center',
    'justify-content': 'center'
  });
	if (window.innerWidth < 1900) {
    $('.button.alt, .single_add_to_cart_button').css({
      'flex-direction': 'column'
    });
  }
});
</script>
<?php
}
add_action('wp_head', 'add🛒RedButtons');

/* Create Shortcode for ADD > to Breadcrumbs  */

function addQuoteBreadcrumbs() {
	?>
<script>
	jQuery(document).ready(function($) {
		$('.woocommerce-breadcrumb').html(
			$('.woocommerce-breadcrumb').html().replace(/ - /g, ' > ')
		);
	});
</script>
<?php
}
add_action('wp_head', 'addQuoteBreadcrumbs', 999);

/* style-tableau-description  */

function style_tableau_description() {
    ?>
   <style>
   .error-message { color: red; }
    .warning-message { color: orange; }
    .tech-specs-container { margin: 0; margin-bottom: 1.5rem; }
    .tech-specs-title { margin-bottom: 1rem; }
    
    /* Styles pour le tableau responsive */
    .product-table-2, .product-table-3 {
        width: 100%;
        border-collapse: collapse;
    }
    
    .product-table-2 th, .product-table-2 td,
    .product-table-3 th, .product-table-3 td {
        padding: 0.5rem;
        border: 1px solid #ddd;
    }
    
    /* Classes d'affichage conditionnel */
    .mobile-table {
        display: none;
    }
    
    /* Styles des onglets */
    .voltage-tabs {
        display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 1.5rem; margin-top: 1.5rem;
    }
    
    .voltage-tabs .tab {
        padding: 10px 16px; cursor: pointer; font-weight: 500; border-radius: 4px;
        transition: all 0.2s ease; background: #f3f3f3; border: 1px solid #ddd;
        color: #444; user-select: none;
    }
    
    .voltage-tabs .tab:hover {
        background: #f5e5e2; border-color: #de2f19;
    }
    
    .voltage-tabs .tab.active {
        background: #de2f19; color: white; border-color: #c42815;
        box-shadow: 0 2px 5px rgba(222, 47, 25, 0.3);
    }
    
    .voltage-tabs-content .tab-content { display: none; }
    .voltage-tabs-content .tab-content.active { display: block; }
    .attributes-hidden-outside-tab.in-tab-active { display: block !important; }
    
    /* Nouveaux styles de tableaux */
    .product-table-1 {
      height: auto !important;
      width: 100% !important;
      max-width: 27rem !important;
      border-collapse: collapse !important;
      border: 1px solid #000000 !important;
      margin-top: 1rem !important;
    }
    .product-table-2 {
      height: auto !important;
      width: 100% !important;
      border-collapse: collapse !important;
      border: 1px solid #000000 !important;
      margin-top: 1rem !important;
    }
    .product-table-3 {
      height: auto !important;
      width: 100% !important;
      max-width: 55rem !important;
      border-collapse: collapse !important;
      border: 1px solid #000000 !important;
      margin-top: 1rem !important;
    }
    .product-row-1 {
      height: auto !important;
    }
    .product-header-1 {
      height: auto !important;
      width: 25% !important;
      text-align: left !important;
      background-color: #123750 !important;
      padding: 0.5rem !important;
      color: #FFFFFF !important;
      opacity: 0.9 !important;
      border: 1px solid #000000 !important;
    }
    .product-cell-1 {
      height: auto !important;
      width: 25% !important;
      text-align: left !important;
      padding: 0.5rem !important;
      border: 1px solid #000000 !important;
    }
    .product-row-1:not(.alternate) .product-cell-1 {
      background-color: #FFFFFF !important;
    }
    .product-row.alternate-1 .product-cell-1 {
      background-color: #f5f5f5 !important;
    }
    .product-cell-1:nth-child(2) {
      font-weight: bold;
    }
    .mobile-row-1 .product-cell-1 {
      background-color: #FFFFFF !important;
    }
    .mobile-row.alternate-1 .product-cell-1 {
      background-color: #f5f5f5 !important;
    }
    .mobile-table {
      display: none;
    }
    .cta-section-1 {
     margin-top: 1rem !important;
     margin-bottom: 1rem !important;
    }
    
    /* Media queries */
    @media (max-width: 768px) {
        /* Styles pour les onglets */
        .voltage-tabs { flex-direction: column; gap: 5px; }
        .voltage-tabs .tab { width: 100%; text-align: center; }
        
        /* Styles pour les tableaux */
        .desktop-table {
            display: none !important;
        }
        
        .mobile-table {
            display: table !important;
        }
        
        /* Styles spécifiques pour le tableau de roulements en mobile */
        .bearing-mobile-table {
            font-size: 0.9rem !important;
        }
		
		.product-table-1 {
            font-size: 0.9rem !important;
        }
        
        .bearing-mobile-table .product-cell-1 {
            padding: 6px !important;
            font-size: 0.8rem !important;
        }

        .bearing-mobile-table .product-header-1 {
            font-size: 0.8rem !important;
        }
        
        /* Styles spécifiques pour le tableau des caractéristiques complémentaires en mobile */
        .complementary-table .product-cell-1 {
            padding: 5px !important;
            font-size: 0.8rem !important;
        }

        .complementary-table .product-header-1 {
            font-size: 0.8rem !important;
        }
        
        .product-table-2 th, .product-table-2 td,
        .product-table-3 th, .product-table-3 td {
            padding: 0.5rem;
            text-align: left;
        }
        
        .product-table-2 th:first-child, .product-table-2 td:first-child,
        .product-table-3 th:first-child, .product-table-3 td:first-child {
            width: 50% !important;
        }
        
        .product-table-2 th:last-child, .product-table-2 td:last-child,
        .product-table-3 th:last-child, .product-table-3 td:last-child {
            width: 50% !important;
        }
        
        .tech-specs-title { font-size: xx-large !important; }
    }
</style>
    <?php
}
add_action('wp_head', 'style_tableau_description', 999);

add_action('wp_ajax_get_cart_count', 'get_cart_count');
add_action('wp_ajax_nopriv_get_cart_count', 'get_cart_count');
function get_cart_count() {
    echo WC()->cart->get_cart_contents_count();
    wp_die();
}
